<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Job;
use App\Models\Skill;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the full anonymization and scoring pipeline for a single candidate.
 *
 * Public API:
 *   runPipeline(rawText, job)          — pure AI processing; returns result array, no DB writes.
 *   persistResult(candidate, result)   — writes pipeline result to candidate + resume skills.
 *   processRaw(candidate, rawText)     — runPipeline + persistResult in one call.
 *   process(candidate)                 — legacy: extracts text from Resume->file_data, then processRaw.
 *   reevaluate(candidate)              — re-scores and re-summarises using stored data (no file needed).
 *
 * Pipeline steps inside runPipeline:
 *   1. Strip PII from raw text via OllamaService.
 *   2. Extract skill keywords from anonymized text.
 *   3. Ensure the job has listing skills (JobSkillService — no-op if already extracted).
 *   4. Calculate 0–100 skill-overlap match score.
 *   5. Generate a 3-5 sentence anonymized candidate summary.
 */
class CandidatePipelineService
{
    public function __construct(
        private OllamaService $ollamaService,
        private JobSkillService $jobSkillService,
        private ResumeTextExtractor $extractor,
        private SkillSplitter $skillSplitter,
    ) {}

    /**
     * Run the AI processing pipeline against raw resume text.
     *
     * Pure function: makes Ollama HTTP calls and returns a result array.
     * No database writes occur here — call persistResult() to commit.
     *
     * @param  string  $rawText  Plain text extracted from the resume (may still contain PII).
     * @param  Job  $job  The job the candidate applied for.
     * @return array{anonymized_text: string, skills: string[], match_score: int|null, anonymized_summary: string}
     */
    public function runPipeline(string $rawText, Job $job): array
    {
        $anonymizedText = $this->ollamaService->stripPii($rawText);

        $skillNames = $this->ollamaService->extractSkills($anonymizedText);
        $skillNames = array_values(array_filter(array_map('trim', $skillNames)));
        $skillNames = $this->skillSplitter->split($skillNames);

        $this->jobSkillService->extractAndAttach($job);
        $job->load('listingSkills');

        $matchScore = $this->calculateMatchScore($job, $skillNames);
        $summary    = $this->generateSummary($anonymizedText);

        return [
            'anonymized_text'    => $anonymizedText,
            'skills'             => $skillNames,
            'match_score'        => $matchScore,
            'anonymized_summary' => $summary,
        ];
    }

    /**
     * Write a pipeline result array to the database.
     *
     * Attaches extracted skills to the resume via the resume_skill pivot,
     * stores anonymized_text + summary + score on the candidate, and
     * transitions status to 'analyzed'.
     *
     * @param  Candidate  $candidate  The candidate to update (must have resume loaded or loadable).
     * @param  array  $result  Return value from runPipeline().
     */
    public function persistResult(Candidate $candidate, array $result): void
    {
        $candidate->loadMissing('resume');

        $skillIds = [];
        foreach ($result['skills'] as $name) {
            if ($name === '') {
                continue;
            }
            $skill      = Skill::firstOrCreate(['name' => mb_strtolower($name)]);
            $skillIds[] = $skill->id;
        }

        if ($skillIds && $candidate->resume) {
            $candidate->resume->skills()->syncWithoutDetaching($skillIds);
        }

        $candidate->anonymized_text    = $result['anonymized_text'];
        $candidate->anonymized_summary = $result['anonymized_summary'];
        $candidate->match_score        = $result['match_score'];
        $candidate->status             = 'analyzed';
        $candidate->save();
    }

    /**
     * Run the pipeline from raw text and immediately persist the result.
     *
     * Preferred entry point for controllers: text is extracted before this call
     * (and the raw file is never stored).
     *
     * @param  Candidate  $candidate  Candidate record (must have job loadable).
     * @param  string  $rawText  Plain text extracted from the resume.
     */
    public function processRaw(Candidate $candidate, string $rawText): void
    {
        $candidate->loadMissing(['job.listingSkills']);

        $job = $candidate->job;

        if (! $job) {
            Log::warning("Candidate {$candidate->id} has no associated job; marking analyzed.");
            $candidate->status = 'analyzed';
            $candidate->save();

            return;
        }

        if (empty(trim($rawText))) {
            Log::warning("Empty raw text for candidate {$candidate->id}; marking analyzed.");
            $candidate->status = 'analyzed';
            $candidate->save();

            return;
        }

        $result = $this->runPipeline($rawText, $job);
        $this->persistResult($candidate, $result);
    }

    /**
     * Legacy entry point: extract text from the stored Resume binary then call processRaw.
     *
     * Used by the integration test suite and any code path that still has a Resume
     * with file_data populated. New controller code should call processRaw() directly
     * after extracting text themselves.
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

        $rawText = $this->extractor->extract($resume);
        if (empty(trim((string) $rawText))) {
            Log::warning("No text extracted for candidate {$candidate->id} (resume {$resume->id}).");
            $candidate->status = 'analyzed';
            $candidate->save();

            return;
        }

        $this->processRaw($candidate, $rawText);
    }

    /**
     * Re-score and re-summarise a candidate using already-stored data.
     *
     * Re-calculates the match score from the resume's existing skill pivot records
     * (so changes to a job's required skills are reflected without re-uploading).
     * Regenerates the summary from the stored anonymized_text when available.
     * Does NOT re-run PII stripping or skill extraction — the resume file no longer exists.
     *
     * If anonymized_text is absent (candidate processed before this feature was added)
     * only the score is updated; the existing summary is left unchanged.
     */
    public function reevaluate(Candidate $candidate): void
    {
        $candidate->loadMissing(['resume.skills', 'job.listingSkills']);

        $job = $candidate->job;

        if ($job) {
            $this->jobSkillService->extractAndAttach($job);
            $job->load('listingSkills');
        }

        $resumeSkillNames = $candidate->resume
            ? $candidate->resume->skills->pluck('name')->toArray()
            : [];

        $candidate->match_score = $this->calculateMatchScore($job, $resumeSkillNames);

        if (! empty($candidate->anonymized_text)) {
            $newSummary = $this->generateSummary($candidate->anonymized_text);
            if ($newSummary !== '') {
                $candidate->anonymized_summary = $newSummary;
            }
        }

        $candidate->save();
    }

    /**
     * Compute a 0–100 skill overlap percentage.
     *
     * Returns null when the job has no listing skills (a score of 0 would be
     * misleading — no comparison basis, not a genuine zero match).
     *
     * Matching is fuzzy: case-insensitive, ignores parentheses/punctuation,
     * and considers token-level overlap so "PHP (Laravel)" matches "laravel".
     *
     * @param  string[]  $candidateSkills
     */
    private function calculateMatchScore(?object $job, array $candidateSkills): ?int
    {
        if (! $job || $job->listingSkills->isEmpty()) {
            return null;
        }

        $jobSkillNames = $job->listingSkills
            ->pluck('name')
            ->filter(fn ($s) => trim((string) $s) !== '')
            ->unique()
            ->values();

        if ($jobSkillNames->isEmpty()) {
            return null;
        }

        $candidateSkills = array_values(array_filter(
            $candidateSkills,
            fn ($s) => trim((string) $s) !== ''
        ));

        $overlap = $jobSkillNames->filter(function (string $jobSkill) use ($candidateSkills) {
            foreach ($candidateSkills as $candidateSkill) {
                if ($this->skillsMatch($jobSkill, $candidateSkill)) {
                    return true;
                }
            }

            return false;
        })->count();

        return (int) round(($overlap / $jobSkillNames->count()) * 100);
    }

    /**
     * Return true when two skill strings are considered equivalent for scoring.
     *
     * Matches when any word-token from one skill appears in the token set of the
     * other after normalization — handles case, parentheticals, and punctuation
     * (e.g. "PHP (Laravel)" matches "laravel"; "React.js" matches "react").
     * Word-level comparison prevents false positives like "java" ≠ "javascript".
     */
    private function skillsMatch(string $a, string $b): bool
    {
        $tokensA = $this->skillTokens($a);
        $tokensB = $this->skillTokens($b);

        if (empty($tokensA) || empty($tokensB)) {
            return false;
        }

        foreach ($tokensA as $token) {
            if (in_array($token, $tokensB, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize a skill name into word tokens for fuzzy matching.
     *
     * Steps: lowercase → strip parentheses/brackets (content kept) →
     * strip misc punctuation (preserving '+' and '#' for C++/C#) →
     * split on whitespace.
     *
     * Examples:
     *   "PHP (Laravel)"   → ["php", "laravel"]
     *   "React.js"        → ["react", "js"]
     *   "C++"             → ["c++"]
     *   "Node JS"         → ["node", "js"]
     */
    private function skillTokens(string $skill): array
    {
        $s = mb_strtolower($skill);
        $s = preg_replace('/[()[\]{}<>]/', ' ', $s);   // strip brackets, keep content
        $s = preg_replace('/[^\w\s#+]/', ' ', $s);      // strip misc punctuation
        $s = trim(preg_replace('/\s+/', ' ', $s));

        return array_values(array_filter(explode(' ', $s), fn ($t) => $t !== ''));
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

        return $this->ollamaService->extractResponseText($response);
    }
}
