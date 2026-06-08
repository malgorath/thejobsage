<?php

namespace App\Http\Controllers;

use App\Mail\PositionFilledMail;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\Resume;
use App\Services\CandidatePipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Recruiter interface for uploading resumes and managing the candidate pipeline.
 *
 * Recruiters can upload raw resume files (PDF/DOCX), download originals, and
 * mark positions as filled. The analysis pipeline (PII stripping, scoring) runs
 * synchronously on upload. HR never has access to the raw files — only the
 * anonymized summaries produced here.
 */
class RecruiterController extends Controller
{
    public function __construct(private CandidatePipelineService $pipeline) {}

    /**
     * List all jobs with candidate pipeline counts for the recruiter overview.
     */
    public function index(): View
    {
        $jobs = Job::withCount([
            'candidates',
            'candidates as pending_count' => fn ($q) => $q->where('status', 'pending_analysis'),
            'candidates as analyzed_count' => fn ($q) => $q->where('status', 'analyzed'),
            'candidates as shortlisted_count' => fn ($q) => $q->where('status', 'shortlisted'),
        ])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('recruiter.jobs.index', compact('jobs'));
    }

    /**
     * Show the file upload form scoped to a specific job.
     */
    public function uploadForm(Job $job): View
    {
        return view('recruiter.jobs.upload', compact('job'));
    }

    /**
     * Accept a single resume upload with an optional candidate email for notifications.
     *
     * After upload the full CandidatePipelineService::process() runs synchronously,
     * transitioning the candidate from pending_analysis to analyzed before redirecting.
     */
    public function upload(Request $request, Job $job): RedirectResponse
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'candidate_email' => 'nullable|email|max:255',
        ]);

        try {
            $file = $request->file('resume');
            $slug = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $safeFilename = $slug.'_'.time().'.'.$file->getClientOriginalExtension();

            $resume = Resume::create([
                'user_id' => null,
                'uploaded_by' => Auth::id(),
                'filename' => $safeFilename,
                'mime_type' => $file->getClientMimeType(),
                'file_data' => file_get_contents($file->getRealPath()),
            ]);

            $candidate = Candidate::create([
                'job_id' => $job->id,
                'resume_id' => $resume->id,
                'uploaded_by' => Auth::id(),
                'candidate_email' => $request->filled('candidate_email') ? $request->candidate_email : null,
                'status' => 'pending_analysis',
            ]);

            $this->pipeline->process($candidate);

            return redirect()
                ->route('recruiter.jobs.show', $job)
                ->with('success', 'Candidate processed successfully.');
        } catch (\Throwable $e) {
            Log::error("Candidate upload failed for job {$job->id}: {$e->getMessage()}");

            return redirect()
                ->route('recruiter.jobs.show', $job)
                ->with('error', 'Upload failed — check logs for details.');
        }
    }

    /**
     * List all candidates for a job, ordered by match score descending.
     */
    public function show(Job $job): View
    {
        $candidates = Candidate::with(['resume.skills'])
            ->where('job_id', $job->id)
            ->orderByDesc('match_score')
            ->get();

        return view('recruiter.jobs.show', compact('job', 'candidates'));
    }

    /**
     * Stream the original raw resume binary to the authenticated recruiter.
     *
     * Access is restricted to the uploading recruiter and admins. HR users
     * cannot reach this endpoint — it is behind the `recruiter` middleware.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 if not authorized, 404 if no resume.
     */
    public function download(Candidate $candidate)
    {
        if (
            Auth::id() !== $candidate->uploaded_by
            && ! Auth::user()->isAdmin()
        ) {
            abort(403, 'You do not have permission to download this file.');
        }

        $resume = $candidate->resume;
        if (! $resume) {
            abort(404, 'Resume file not found.');
        }

        return response($resume->file_data)
            ->header('Content-Type', $resume->mime_type)
            ->header('Content-Disposition', 'attachment; filename="'.$resume->filename.'"');
    }

    /**
     * Update a candidate's screening status.
     */
    public function updateStatus(Request $request, Candidate $candidate): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:shortlisted,rejected,analyzed',
        ]);

        $candidate->update(['status' => $request->status]);

        return back()->with('success', 'Candidate status updated.');
    }

    /**
     * Mark a job as filled and queue position-filled notifications to all
     * active candidates who provided an email address.
     *
     * Rejected candidates are excluded from the notification — they already
     * received a rejection email at rejection time.
     */
    public function closeJob(Job $job): RedirectResponse
    {
        if ($job->is_closed) {
            return redirect()
                ->route('recruiter.jobs.show', $job)
                ->with('info', 'This position is already marked as filled.');
        }

        $job->update(['is_closed' => true, 'closed_at' => now()]);

        $notifiable = Candidate::where('job_id', $job->id)
            ->whereNotNull('candidate_email')
            ->where('status', '!=', 'rejected')
            ->get();

        foreach ($notifiable as $candidate) {
            Mail::to($candidate->candidate_email)->queue(new PositionFilledMail($candidate));
        }

        $notifiedCount = $notifiable->count();
        $message = 'Position marked as filled.';
        if ($notifiedCount > 0) {
            $message .= " {$notifiedCount} candidate(s) notified.";
        }

        return redirect()
            ->route('recruiter.jobs.show', $job)
            ->with('success', $message);
    }
}
