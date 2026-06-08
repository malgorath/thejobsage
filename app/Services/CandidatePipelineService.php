<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Skill;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the full anonymization and scoring pipeline for a single candidate.
 *
 * Pipeline steps (all synchronous):
 *   1. Extract raw text from the resume binary (PDF/DOCX).
 *   2. Strip PII via OllamaService::stripPii().
 *   3. Extract skills from anonymized text via OllamaService::extractSkills().
 *   4. Persist skills and attach them to the resume via the resume_skill pivot.
 *   5. Ensure the job has listing skills (JobSkillService::extractAndAttach()).
 *   6. Calculate skill-overlap match score (0–100, or null if no job skills).
 *   7. Generate a 3-5 sentence anonymized candidate summary.
 *   8. Update the Candidate record: anonymized_summary, match_score, status = analyzed.
 *
 * On early-exit conditions (no resume, no extractable text) the candidate is
 * still transitioned to `analyzed` so HR can review it manually.
 */
class CandidatePipelineService
{
    public function __construct(
        private OllamaService $ollamaService,
        private JobSkillService $jobSkillService,
        private ResumeTextExtractor $extractor,
    ) {}

    /**
     * Run the full pipeline for one candidate.
     *
     * Transitions status from `pending_analysis` → `analyzed` on completion.
     * Safe to call multiple times — idempotent once analyzed (no guard needed;
     * the caller controls when to invoke this).
     */
    public function process(Candidate $candidate): void
    {
        $candidate->loadMissing(['resume', 'job.listingSkills']);

        $resume = $candidate->resume;
        if (! $resume) {
            Log::warning("Candidate {$candidate->id} has no resume; marking analyzed.");
            $candidate->status = 'analyzed';
            $candidate->save();

            return;
        }

        // 1. Extract raw text
        $rawText = $this->extractor->extract($resume);
        if (empty(trim((string) $rawText))) {
            Log::warning("No text extracted for candidate {$candidate->id} (resume {$resume->id}).");
            $candidate->status = 'analyzed';
            $candidate->save();

            return;
        }

        // 2. Strip PII
        $anonymizedText = $this->ollamaService->stripPii($rawText);

        // 3. Extract skills from anonymized text only (never from raw text)
        $skillNames = $this->ollamaService->extractSkills($anonymizedText);
        $skillNames = array_filter(array_map('trim', $skillNames));

        // 4. Persist skills and attach to resume via resume_skill pivot
        $skillIds = [];
        foreach ($skillNames as $name) {
            if ($name === '') {
                continue;
            }
            $skill = Skill::firstOrCreate(['name' => mb_strtolower($name)]);
            $skillIds[] = $skill->id;
        }
        if ($skillIds) {
            $resume->skills()->syncWithoutDetaching($skillIds);
        }

        // 5. Ensure job has listing skills (triggers Ollama extraction if missing)
        $job = $candidate->job;
        if ($job) {
            $this->jobSkillService->extractAndAttach($job);
            $job->load('listingSkills');
        }

        // 6. Calculate skill-overlap match score
        $matchScore = $this->calculateMatchScore($job, $skillNames);

        // 7. Generate anonymized candidate summary
        $summary = $this->generateSummary($anonymizedText);

        // 8. Persist results
        $candidate->anonymized_summary = $summary;
        $candidate->match_score = $matchScore;
        $candidate->status = 'analyzed';
        $candidate->save();
    }

    /**
     * Compute a 0–100 skill overlap percentage between a job's required skills
     * and a candidate's extracted skill set.
     *
     * Returns null when the job has no listing skills, since a score of 0
     * would be misleading (no basis for comparison, not a genuine zero match).
     *
     * @param  string[]  $candidateSkills
     */
    private function calculateMatchScore(?object $job, array $candidateSkills): ?int
    {
        if (! $job || $job->listingSkills->isEmpty()) {
            return null;
        }

        $jobSkills = $job->listingSkills
            ->pluck('name')
            ->map(fn ($s) => mb_strtolower($s))
            ->unique();

        if ($jobSkills->isEmpty()) {
            return null;
        }

        $candidateSkillsNorm = collect($candidateSkills)
            ->map(fn ($s) => mb_strtolower($s))
            ->unique();

        $overlap = $jobSkills->intersect($candidateSkillsNorm)->count();

        return (int) round(($overlap / $jobSkills->count()) * 100);
    }

    /**
     * Generate a 3-5 sentence anonymized professional summary via Ollama.
     *
     * Returns an empty string on failure; callers treat '' as "no summary available."
     */
    private function generateSummary(string $anonymizedText): string
    {
        [$prompt, $config] = $this->ollamaService->promptPayload(
            'candidate_summary',
            ['anonymized_text' => $anonymizedText],
            "Write a 3-5 sentence professional summary of this candidate based on the anonymized resume below. Focus only on skills, experience level, and key accomplishments. Do not include or infer any names, dates, company names, school names, or other identifying information.\n\n{{anonymized_text}}"
        );

        try {
            $response = $this->ollamaService->postToOllama($prompt, $config);
        } catch (\Throwable $e) {
            Log::error("Candidate summary generation failed: {$e->getMessage()}");

            return '';
        }

        return $response->json()['response'] ?? '';
    }
}
