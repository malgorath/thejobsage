<?php

namespace App\Services;

use App\Models\Prompt;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gateway to the local Ollama LLM API.
 *
 * All AI operations on the platform route through this service:
 * PII stripping, skill extraction, skill gap summaries, and prompt rendering.
 * Prompt templates are resolved from the `prompts` database table at runtime,
 * falling back to hardcoded defaults when no record exists for a given key.
 * Connection details are read from config/ollama.php (env: OLLAMA_API_URL, etc.).
 */
class OllamaService
{
    protected string $baseUrl;

    protected string $llmModel;

    protected string $apiType;

    protected array $defaultConfig = [
        'temperature' => 0.7,
        'top_p' => 0.9,
        'top_k' => 40,
        'repeat_penalty' => 1.1,
        'num_ctx' => 4096,
        'seed' => null,
        'max_tokens' => 800,
    ];

    public function __construct()
    {
        $this->baseUrl  = config('ollama.api_url');
        $this->llmModel = config('ollama.llm_model');
        $this->apiType  = config('ollama.api_type', 'ollama');
    }

    /**
     * Strip all PII and indirect identifiers from raw resume text.
     *
     * Falls back to returning the original `$text` unchanged on Ollama failure
     * so the pipeline can continue without crashing (PII may be present, but
     * the candidate still enters the queue for manual review).
     *
     * @param  string  $text  Raw extracted resume text.
     * @return string Anonymized text, or original on failure.
     */
    public function stripPii(string $text): string
    {
        [$prompt, $config] = $this->promptPayload(
            'pii_strip',
            ['resume_text' => $text],
            $this->piiStripFallbackTemplate()
        );

        try {
            $response = $this->postToOllama($prompt, $config);
        } catch (\Throwable $e) {
            Log::error("PII stripping request failed: {$e->getMessage()}");

            return $text;
        }

        if (! $response->successful()) {
            Log::error("PII stripping HTTP error: status {$response->status()}");

            return $text;
        }

        $result = $this->extractResponseText($response);

        return $result !== '' ? $result : $text;
    }

    /**
     * Extract technical and professional skill keywords from anonymized resume text.
     *
     * Returns a flat array of trimmed skill name strings. Returns an empty array
     * on Ollama failure so callers degrade gracefully (no skills = score of null).
     *
     * @param  string  $text  Anonymized resume text.
     * @return string[] Skill names extracted from the text.
     */
    public function extractSkills(string $text): array
    {
        [$prompt, $config] = $this->promptPayload(
            'skill_extraction',
            ['resume_text' => $text],
            "Extract technical and professional skill words from the following resume text and return as comma separated array only, trimming off extra white spaces as needed, only the data no extra characters or text: \n\n{{resume_text}}"
        );

        try {
            $response = $this->postToOllama($prompt, $config);
        } catch (\Throwable $e) {
            Log::error("Skill extraction request failed: {$e->getMessage()}");

            return [];
        }

        if ($response->successful()) {
            $text = $this->extractResponseText($response);
            if ($text !== '') {
                return $this->parseSkillList($text);
            }
        }

        return [];
    }

    /**
     * Parse an LLM skill-list response into a flat array of individual skill strings.
     *
     * Handles all common LLM output styles:
     *   - Comma-separated:       "PHP, Laravel, MySQL"
     *   - Newline-separated:     "PHP\nLaravel\nMySQL"
     *   - Numbered list:         "1. PHP\n2. Laravel"
     *   - Bullet list:           "- PHP\n• Laravel\n* MySQL"
     *   - Semicolon-separated:   "PHP; Laravel; MySQL"
     *   - Mixed:                 "PHP, Laravel\nMySQL; Vue"
     */
    private function parseSkillList(string $text): array
    {
        // Strip leading list markers on each line: "1. ", "2) ", "- ", "• ", "* "
        // Flags: m = multiline (^ matches each line start), u = UTF-8 (handles • and similar bullets)
        $text = (string) preg_replace('/^\s*(?:\d+[\.\)]\s*|[-•*]\s*)/mu', '', $text);
        // Split on commas, semicolons, or any run of newlines/carriage-returns
        $parts = (array) preg_split('/[,;]+|[\r\n]+/', $text);

        return array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));
    }

    /**
     * Generate a concise skill gap explanation for a rejected candidate.
     *
     * Returns an empty string when `$jobSkillNames` is empty (no job skills =
     * no meaningful gap to explain) or on Ollama failure. Callers should treat
     * an empty string as "omit this section" rather than an error.
     *
     * @param  string[]  $jobSkillNames  Required skills for the position.
     * @param  string[]  $candidateSkillNames  Skills found in the candidate's resume.
     * @return string Prose gap explanation, or '' on failure / no job skills.
     */
    public function generateSkillGapSummary(array $jobSkillNames, array $candidateSkillNames): string
    {
        if (empty($jobSkillNames)) {
            return '';
        }

        $jobSkillsStr = implode(', ', $jobSkillNames);
        $candidateSkillsStr = implode(', ', $candidateSkillNames) ?: 'none identified';

        [$prompt, $config] = $this->promptPayload(
            'skill_gap_summary',
            [
                'job_skills' => $jobSkillsStr,
                'candidate_skills' => $candidateSkillsStr,
            ],
            <<<'PROMPT'
Given a position requiring these skills: {{job_skills}}
And a candidate whose profile includes: {{candidate_skills}}

Write a concise 2-3 sentence professional explanation of the key skill gaps.
Focus on what is missing or underdeveloped relative to the role requirements.
Do not mention names, companies, dates, or any identifying information.
Write in language appropriate to share directly with the candidate.
PROMPT
        );

        try {
            $response = $this->postToOllama($prompt, $config);
        } catch (\Throwable $e) {
            Log::error("Skill gap summary generation failed: {$e->getMessage()}");

            return '';
        }

        return $this->extractResponseText($response);
    }

    /**
     * Resolve a prompt template from the database and render it with variables.
     *
     * When no DB record exists for `$key`, `$fallbackTemplate` is used verbatim.
     * The returned config merges the service defaults with any per-prompt overrides.
     *
     * @param  string  $key  The prompt key (e.g. 'pii_strip').
     * @param  array<string,string>  $variables  Placeholder values for template rendering.
     * @param  string  $fallbackTemplate  Template used when no DB record exists.
     * @return array{0: string, 1: array} [rendered prompt string, config array]
     */
    public function promptPayload(string $key, array $variables, string $fallbackTemplate): array
    {
        $prompt = Prompt::where('key', $key)->first();
        $template = $prompt?->body ?? $fallbackTemplate;
        $config = array_merge($this->defaultConfig, $prompt->config ?? []);

        return [$this->renderTemplate($template, $variables), $config];
    }

    /**
     * Post a prompt to the configured AI backend.
     *
     * Supports two wire formats controlled by OLLAMA_API_TYPE:
     *
     *  'ollama'  — Ollama native: {"model", "prompt", "stream": false, ...config}
     *              Expects {"response": "..."} in the reply.
     *
     *  'openai'  — OpenAI-compatible: {"model", "messages": [{"role":"user","content":"..."}], "stream": false, ...config}
     *              Expects {"choices": [{"message": {"content": "..."}}]} in the reply.
     *              Compatible with llama.cpp --server, LM Studio, vLLM, LocalAI,
     *              and Ollama's own /v1/chat/completions endpoint.
     *
     * @param  string  $prompt  The fully rendered prompt string.
     * @param  array   $config  Model parameters (temperature, top_p, etc.).
     *
     * @throws \Illuminate\Http\Client\ConnectionException  When the server is unreachable.
     */
    public function postToOllama(string $prompt, array $config): Response
    {
        if ($this->apiType === 'openai') {
            $base = [
                'model'    => $this->llmModel,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'stream'   => false,
            ];
        } else {
            $base = [
                'model'  => $this->llmModel,
                'prompt' => $prompt,
                'stream' => false,
            ];
        }

        return Http::timeout(config('ollama.timeout', 120))->post($this->baseUrl, array_merge($base, $this->filterConfig($config)));
    }

    /**
     * Extract the assistant reply text from an AI backend response.
     *
     * Reads from `choices[0].message.content` for OpenAI-compatible responses,
     * or from `response` for Ollama native responses.
     * Returns an empty string when the field is absent or the response is malformed.
     */
    public function extractResponseText(Response $response): string
    {
        if ($this->apiType === 'openai') {
            return $response->json()['choices'][0]['message']['content'] ?? '';
        }

        return $response->json()['response'] ?? '';
    }

    /**
     * Replace `{{key}}` placeholders in a template string with provided values.
     *
     * @param  array<string,string>  $variables
     */
    private function renderTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{'.$key.'}}', $value, $template);
        }

        return $template;
    }

    /**
     * Remove null values from a config array before sending to Ollama.
     *
     * Ollama rejects null-valued parameters, so they must be stripped rather
     * than serialized as JSON null.
     */
    private function filterConfig(array $config): array
    {
        return array_filter($config, fn ($value) => ! is_null($value));
    }

    /**
     * Hardcoded fallback PII stripping template used when no `pii_strip` prompt
     * record exists in the database.
     */
    private function piiStripFallbackTemplate(): string
    {
        return <<<'PROMPT'
You are a data anonymization assistant for a blind HR screening platform. Strip ALL personally identifiable information and indirect identifiers from the resume text below.

Remove or replace:
- Full names, first names, last names — omit entirely
- Email addresses, phone numbers — omit entirely
- Physical addresses (street, city, state, zip/postal code) — omit entirely
- LinkedIn, GitHub, portfolio, personal website, or any personal URLs — omit entirely
- University, college, or school names — replace with "University" or "Institution"
- Graduation years and specific calendar dates — omit entirely; replace with relative phrasing where needed (e.g. "approximately 3 years of experience")
- Any "X years of experience" statements that could imply birth year or current age — rephrase to describe skill level only (e.g. "experienced in" or "proficient in")
- Employer or company names — replace with "Company A", "Company B", etc. ordered oldest to newest
- Military discharge dates, service branch names that imply specific dates — omit dates; keep branch name only if relevant to skills
- Fraternity, sorority, or alumni association memberships — omit entirely
- Religious organization memberships that could imply protected characteristics — omit entirely
- Any other information that could directly or indirectly reveal age, race, gender, national origin, religion, or disability status

Preserve:
- Job titles and role descriptions
- Technical skills, tools, frameworks, languages
- Responsibilities and accomplishments (without employer names or identifying metrics tied to named entities)
- Relative durations of roles (e.g. "2 years", "18 months")

Return ONLY the anonymized text. No preamble, no explanation, no commentary.

Resume:
{{resume_text}}
PROMPT;
    }
}
