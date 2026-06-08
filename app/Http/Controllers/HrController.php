<?php

namespace App\Http\Controllers;

use App\Mail\CandidateRejectedMail;
use App\Models\Candidate;
use App\Models\Job;
use App\Services\OllamaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * HR user interface for blind candidate screening.
 *
 * HR users see only anonymized summaries, skill chips, and match scores.
 * Raw resume files and candidate PII are never exposed through this controller.
 * Rejection emails are queued and use PII-stripped content only.
 */
class HrController extends Controller
{
    public function __construct(private OllamaService $ollama) {}

    /**
     * List all jobs that have at least one screened (non-pending) candidate,
     * with per-status counts for the HR overview.
     */
    public function index(): View
    {
        $jobs = Job::withCount([
            'candidates as screened_count' => fn ($q) => $q->whereIn('status', ['analyzed', 'shortlisted', 'rejected']),
            'candidates as analyzed_count' => fn ($q) => $q->where('status', 'analyzed'),
            'candidates as shortlisted_count' => fn ($q) => $q->where('status', 'shortlisted'),
            'candidates as rejected_count' => fn ($q) => $q->where('status', 'rejected'),
        ])
            ->having('screened_count', '>', 0)
            ->with('listingSkills')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('hr.jobs.index', compact('jobs'));
    }

    /**
     * Show all screened candidates for a job, ranked by match score descending.
     *
     * Pending-analysis candidates are excluded — they are not ready for HR review.
     */
    public function show(Job $job): View
    {
        $job->load('listingSkills');

        $candidates = Candidate::with(['resume.skills'])
            ->where('job_id', $job->id)
            ->whereIn('status', ['analyzed', 'shortlisted', 'rejected'])
            ->orderByDesc('match_score')
            ->get();

        return view('hr.jobs.show', compact('job', 'candidates'));
    }

    /**
     * Show the full anonymized profile for a single candidate.
     *
     * Redirects to the job's candidate list if the candidate has not yet been
     * processed by the analysis pipeline.
     */
    public function candidate(Candidate $candidate): View|RedirectResponse
    {
        if (! $candidate->isAnalyzed()) {
            return redirect()
                ->route('hr.jobs.show', $candidate->job_id)
                ->with('info', 'This candidate is still being processed.');
        }

        $candidate->load(['resume.skills', 'job']);

        return view('hr.candidates.show', compact('candidate'));
    }

    /**
     * Shortlist a candidate. Rejections must go through the dedicated reject form.
     */
    public function updateStatus(Request $request, Candidate $candidate): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:shortlisted',
        ]);

        $candidate->update(['status' => $request->status]);

        return back()->with('success', 'Candidate shortlisted.');
    }

    /**
     * Show the structured rejection form pre-populated with skill gap analysis.
     *
     * Redirects back to the candidate list if the candidate is already rejected.
     */
    public function rejectForm(Candidate $candidate): View|RedirectResponse
    {
        if ($candidate->status === 'rejected') {
            return redirect()
                ->route('hr.jobs.show', $candidate->job_id)
                ->with('info', 'This candidate has already been rejected.');
        }

        $candidate->loadMissing(['job.listingSkills', 'resume.skills']);

        $jobSkills = $candidate->job?->listingSkills->pluck('name')->toArray() ?? [];
        $candidateSkills = $candidate->resume?->skills->pluck('name')->toArray() ?? [];
        $missingSkills = array_values(array_diff(
            array_map('mb_strtolower', $jobSkills),
            array_map('mb_strtolower', $candidateSkills)
        ));

        $rejectionReasons = [
            'skill_gap' => 'Skill gap / technical requirements not met',
            'experience_level' => 'Experience level mismatch',
            'culture_fit' => 'Culture / team fit',
            'overqualified' => 'Overqualified',
            'other' => 'Other',
        ];

        return view('hr.candidates.reject', compact(
            'candidate',
            'jobSkills',
            'candidateSkills',
            'missingSkills',
            'rejectionReasons'
        ));
    }

    /**
     * Process the rejection: strip PII from the HR note, generate a skill gap
     * summary, update the candidate record, and optionally queue a notification.
     *
     * The rejection note is passed through OllamaService::stripPii() before
     * being stored so that accidental PII typed by the HR user is never saved
     * or emailed to the candidate.
     */
    public function reject(Request $request, Candidate $candidate): RedirectResponse
    {
        $request->validate([
            'rejection_stage' => 'required|in:screening,interview',
            'rejection_reason' => 'required|in:skill_gap,experience_level,culture_fit,overqualified,other',
            'rejection_note' => 'required|string|min:20|max:1000',
        ]);

        $candidate->loadMissing(['job.listingSkills', 'resume.skills']);

        $jobSkills = $candidate->job?->listingSkills->pluck('name')->map(fn ($s) => mb_strtolower($s))->toArray() ?? [];
        $candidateSkills = $candidate->resume?->skills->pluck('name')->map(fn ($s) => mb_strtolower($s))->toArray() ?? [];

        $strippedNote = $this->ollama->stripPii($request->rejection_note);
        $skillGapSummary = $this->ollama->generateSkillGapSummary($jobSkills, $candidateSkills);

        $candidate->update([
            'status' => 'rejected',
            'rejection_stage' => $request->rejection_stage,
            'rejection_reason' => $request->rejection_reason,
            'rejection_note' => $strippedNote,
            'skill_gap_summary' => $skillGapSummary,
        ]);

        if ($candidate->candidate_email) {
            Mail::to($candidate->candidate_email)->queue(new CandidateRejectedMail($candidate->fresh()));
        }

        $noticeMsg = 'Candidate rejected. '.($candidate->candidate_email
            ? 'Notification queued.'
            : 'No email on file — no notification sent.');

        return redirect()
            ->route('hr.jobs.show', $candidate->job_id)
            ->with('success', $noticeMsg);
    }
}
