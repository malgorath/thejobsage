<?php

namespace App\Http\Controllers;

use App\Mail\PositionFilledMail;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\Resume;
use App\Services\CandidatePipelineService;
use App\Services\ResumeTextExtractor;
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
 * Raw resume files are NEVER stored to disk or database. Text is extracted from
 * the uploaded file in-memory; only the AI-processed anonymized data is persisted.
 * The optional review-before-saving step stages pipeline results in the session
 * so the recruiter can inspect extracted skills and the anonymized summary before
 * the candidate record is written to the database.
 */
class RecruiterController extends Controller
{
    public function __construct(
        private CandidatePipelineService $pipeline,
        private ResumeTextExtractor $extractor,
    ) {}

    /**
     * List all jobs with candidate pipeline counts for the recruiter overview.
     */
    public function index(): View
    {
        $jobs = Job::withCount([
            'candidates',
            'candidates as pending_count'    => fn ($q) => $q->where('status', 'pending_analysis'),
            'candidates as analyzed_count'   => fn ($q) => $q->where('status', 'analyzed'),
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
     * Accept a resume upload, run the pipeline in-memory, then either stage the
     * result for review or save it directly.
     *
     * The raw file binary is NEVER written to the database. Text is extracted
     * from the uploaded temp file, processed through the AI pipeline, and then
     * either staged in session (when review_before_saving is truthy) or
     * persisted via saveCandidate() immediately.
     */
    public function upload(Request $request, Job $job): RedirectResponse
    {
        $request->validate([
            'resume'               => 'required|file|mimes:pdf,doc,docx|max:10240',
            'candidate_email'      => 'nullable|email|max:255',
            'review_before_saving' => 'nullable|boolean',
        ]);

        try {
            $file    = $request->file('resume');
            $content = file_get_contents($file->getRealPath());
            $rawText = $this->extractor->extractContent($content, $file->getMimeType());

            if (empty(trim((string) $rawText))) {
                return back()->withErrors([
                    'resume' => 'Could not extract text from this file. Ensure the file is not password-protected or corrupted.',
                ])->withInput();
            }

            $result = $this->pipeline->runPipeline($rawText, $job);

            $slug          = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $safeFilename  = $slug.'_'.time().'.'.$file->getClientOriginalExtension();
            $mimeType      = $file->getClientMimeType();
            $email         = $request->filled('candidate_email') ? $request->candidate_email : null;

            if ($request->boolean('review_before_saving', true)) {
                session([
                    'upload_preview' => [
                        'job_id'          => $job->id,
                        'filename'        => $safeFilename,
                        'mime_type'       => $mimeType,
                        'candidate_email' => $email,
                        'result'          => $result,
                    ],
                ]);

                return redirect()->route('recruiter.upload.preview', $job);
            }

            $this->saveCandidate($job, $safeFilename, $mimeType, $email, $result);

            return redirect()
                ->route('recruiter.jobs.show', $job)
                ->with('success', 'Candidate processed and saved.');
        } catch (\Throwable $e) {
            Log::error("Candidate upload failed for job {$job->id}: {$e->getMessage()}");

            return back()
                ->with('error', 'Upload failed — check logs for details.')
                ->withInput();
        }
    }

    /**
     * Show the staged pipeline result so the recruiter can review before confirming.
     *
     * Redirects back to the upload form when there is no staged result in session
     * or when the staged job does not match the current job.
     */
    public function previewUpload(Job $job): View|RedirectResponse
    {
        $staged = session('upload_preview');

        if (! $staged || ($staged['job_id'] ?? null) !== $job->id) {
            return redirect()
                ->route('recruiter.upload.form', $job)
                ->with('info', 'No pending upload to preview. Please upload a file first.');
        }

        return view('recruiter.jobs.upload_preview', compact('job', 'staged'));
    }

    /**
     * Persist the staged pipeline result and clear the session.
     *
     * Redirects back to the upload form when the session is missing or stale.
     */
    public function confirmUpload(Job $job): RedirectResponse
    {
        $staged = session('upload_preview');

        if (! $staged || ($staged['job_id'] ?? null) !== $job->id) {
            return redirect()->route('recruiter.upload.form', $job);
        }

        session()->forget('upload_preview');

        $this->saveCandidate(
            $job,
            $staged['filename'],
            $staged['mime_type'],
            $staged['candidate_email'],
            $staged['result'],
        );

        return redirect()
            ->route('recruiter.jobs.show', $job)
            ->with('success', 'Candidate saved successfully.');
    }

    /**
     * Discard the staged pipeline result and clear the session.
     */
    public function discardUpload(Job $job): RedirectResponse
    {
        session()->forget('upload_preview');

        return redirect()
            ->route('recruiter.upload.form', $job)
            ->with('info', 'Upload discarded. Nothing was saved.');
    }

    /**
     * Re-run scoring and summary generation from stored data (no file needed).
     *
     * Useful after a job's required skills have been updated post-upload.
     * Updates match_score and, when anonymized_text is on record, regenerates
     * the summary. Extracted skills on the resume pivot are preserved as-is.
     */
    public function reevaluate(Candidate $candidate): RedirectResponse
    {
        try {
            $this->pipeline->reevaluate($candidate);
        } catch (\Throwable $e) {
            Log::error("Re-evaluation failed for candidate {$candidate->id}: {$e->getMessage()}");

            return back()->with('error', 'Re-evaluation failed — check logs for details.');
        }

        return redirect()
            ->route('recruiter.jobs.show', $candidate->job_id)
            ->with('success', 'Candidate re-evaluated.');
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
        $message       = 'Position marked as filled.';
        if ($notifiedCount > 0) {
            $message .= " {$notifiedCount} candidate(s) notified.";
        }

        return redirect()
            ->route('recruiter.jobs.show', $job)
            ->with('success', $message);
    }

    /**
     * Create a Resume record (no file_data) and a Candidate from a pipeline result.
     *
     * Called from upload() (direct save path) and confirmUpload() (preview path).
     * Raw file binary is intentionally omitted — it was never stored anywhere.
     *
     * @param  array  $result  Return value from CandidatePipelineService::runPipeline().
     */
    private function saveCandidate(
        Job $job,
        string $filename,
        string $mimeType,
        ?string $email,
        array $result,
    ): void {
        $resume = Resume::create([
            'user_id'     => null,
            'uploaded_by' => Auth::id(),
            'filename'    => $filename,
            'mime_type'   => $mimeType,
        ]);

        $candidate = Candidate::create([
            'job_id'          => $job->id,
            'resume_id'       => $resume->id,
            'uploaded_by'     => Auth::id(),
            'candidate_email' => $email,
            'status'          => 'pending_analysis',
        ]);

        $this->pipeline->persistResult($candidate, $result);
    }
}
