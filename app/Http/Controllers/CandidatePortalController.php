<?php

namespace App\Http\Controllers;

use App\Mail\CandidateConfirmationMail;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\Resume;
use App\Services\CandidatePipelineService;
use App\Services\ResumeTextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public candidate self-submission portal.
 *
 * No authentication is required. Candidates submit their resume via email
 * address and receive a unique submission_token link to track their status.
 * All routes are rate-limited (throttle:20,1) in web.php to prevent abuse.
 */
class CandidatePortalController extends Controller
{
    public function __construct(
        private CandidatePipelineService $pipeline,
        private ResumeTextExtractor $extractor,
    ) {}

    /**
     * Show the public application form for a job.
     *
     * Redirects to the jobs index if the position has been closed.
     */
    public function form(Job $job): View|RedirectResponse
    {
        if ($job->is_closed) {
            return redirect()->route('jobs.index')
                ->with('info', 'This position is no longer accepting applications.');
        }

        $job->load('listingSkills');

        return view('portal.apply', compact('job'));
    }

    /**
     * Handle portal application submission.
     *
     * Runs the full LLM pipeline (PII stripping, skill extraction, summary
     * generation) synchronously and stages the anonymized result in the
     * session for the candidate to review. No database record is created
     * here — the candidate must explicitly accept the reviewed profile
     * (see review() / confirmSubmission()) before anything is persisted.
     * Duplicate applications (same email + job) are blocked with a friendly message.
     */
    public function submit(Request $request, Job $job): RedirectResponse
    {
        if ($job->is_closed) {
            return redirect()->route('jobs.index')
                ->with('info', 'This position is no longer accepting applications.');
        }

        $request->validate([
            'candidate_email' => 'required|email|max:255',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $alreadyApplied = Candidate::where('job_id', $job->id)
            ->where('candidate_email', $request->candidate_email)
            ->exists();

        if ($alreadyApplied) {
            return back()->with('info', "You've already applied for this position. Check your email for your status link.");
        }

        $file         = $request->file('resume');
        $slug         = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $safeFilename = $slug.'_'.time().'.'.$file->getClientOriginalExtension();

        // Extract text before creating any DB record — the raw binary is never stored.
        $rawText = $this->extractor->extractContent(
            file_get_contents($file->getRealPath()),
            $file->getMimeType()
        ) ?? '';

        if (empty(trim($rawText))) {
            return back()->withErrors([
                'resume' => 'Could not extract text from this file. Ensure the file is not password-protected or corrupted.',
            ]);
        }

        $result = $this->pipeline->runPipeline($rawText, $job);

        session([
            'portal_submission_review' => [
                'job_id'          => $job->id,
                'filename'        => $safeFilename,
                'mime_type'       => $file->getClientMimeType(),
                'candidate_email' => $request->candidate_email,
                'result'          => $result,
            ],
        ]);

        return redirect()->route('portal.review', $job);
    }

    /**
     * Show the staged, anonymized profile so the candidate can review it
     * before deciding to save or discard it.
     *
     * Redirects back to the application form when there is no staged
     * result in session or when it does not belong to this job.
     */
    public function review(Job $job): View|RedirectResponse
    {
        $staged = session('portal_submission_review');

        if (! $staged || ($staged['job_id'] ?? null) !== $job->id) {
            return redirect()
                ->route('portal.apply', $job)
                ->with('info', 'No pending submission to review. Please submit your resume first.');
        }

        return view('portal.review', compact('job', 'staged'));
    }

    /**
     * Persist the staged, anonymized profile and clear the session.
     *
     * Redirects back to the application form when the session is missing or stale.
     */
    public function confirmSubmission(Job $job): RedirectResponse
    {
        $staged = session('portal_submission_review');

        if (! $staged || ($staged['job_id'] ?? null) !== $job->id) {
            return redirect()->route('portal.apply', $job);
        }

        $alreadyApplied = Candidate::where('job_id', $job->id)
            ->where('candidate_email', $staged['candidate_email'])
            ->exists();

        if ($alreadyApplied) {
            session()->forget('portal_submission_review');

            return redirect()
                ->route('portal.apply', $job)
                ->with('info', "You've already applied for this position. Check your email for your status link.");
        }

        session()->forget('portal_submission_review');

        $resume = Resume::create([
            'user_id'     => null,
            'uploaded_by' => null,
            'filename'    => $staged['filename'],
            'mime_type'   => $staged['mime_type'],
        ]);

        $candidate = Candidate::create([
            'job_id'           => $job->id,
            'resume_id'        => $resume->id,
            'uploaded_by'      => null,
            'candidate_email'  => $staged['candidate_email'],
            'submission_token' => Str::uuid()->toString(),
            'status'           => 'pending_analysis',
        ]);

        $this->pipeline->persistResult($candidate, $staged['result']);

        Mail::to($candidate->candidate_email)->queue(new CandidateConfirmationMail($candidate));

        return redirect()->route('portal.submitted');
    }

    /**
     * Reject the staged, anonymized profile and discard it entirely.
     *
     * No Resume or Candidate record is ever created — the staged session
     * data (including the anonymized result) is simply dropped, and the
     * candidate is sent back to the job listing.
     */
    public function rejectSubmission(Job $job): RedirectResponse
    {
        session()->forget('portal_submission_review');

        return redirect()
            ->route('jobs.show', $job)
            ->with('info', 'Submission discarded. Nothing was saved.');
    }

    /**
     * Show the generic "application received, check your email" confirmation page.
     */
    public function submitted(): View
    {
        return view('portal.submitted');
    }

    /**
     * Look up a candidate by their submission token and show their current status.
     *
     * Returns 404 if the token is not found so that tokens cannot be enumerated.
     *
     * @param  string  $token  The UUID submission token from the confirmation email.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function status(string $token): View
    {
        $candidate = Candidate::where('submission_token', $token)
            ->with(['resume.skills', 'job.listingSkills'])
            ->firstOrFail();

        return view('portal.status', compact('candidate'));
    }
}
