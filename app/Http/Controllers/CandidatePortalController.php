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
     * Creates a Resume and Candidate record, runs the full analysis pipeline
     * synchronously, then queues a CandidateConfirmationMail with the tracking token.
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

        // Extract text before creating the DB record — the raw binary is never stored.
        $rawText = $this->extractor->extractContent(
            file_get_contents($file->getRealPath()),
            $file->getMimeType()
        ) ?? '';

        $resume = Resume::create([
            'user_id'     => null,
            'uploaded_by' => null,
            'filename'    => $safeFilename,
            'mime_type'   => $file->getClientMimeType(),
        ]);

        $candidate = Candidate::create([
            'job_id'           => $job->id,
            'resume_id'        => $resume->id,
            'uploaded_by'      => null,
            'candidate_email'  => $request->candidate_email,
            'submission_token' => Str::uuid()->toString(),
            'status'           => 'pending_analysis',
        ]);

        $this->pipeline->processRaw($candidate, $rawText);
        $candidate->refresh();

        Mail::to($candidate->candidate_email)->queue(new CandidateConfirmationMail($candidate));

        return redirect()->route('portal.submitted');
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
