<?php

namespace Database\Seeders;

use App\Models\Prompt;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    public function run(): void
    {
        $defaultConfig = [
            'temperature' => 0.7,
            'top_p' => 0.9,
            'top_k' => 40,
            'repeat_penalty' => 1.1,
            'num_ctx' => 4096,
            'seed' => null,
            'max_tokens' => 800,
        ];

        $prompts = [
            [
                'key' => 'skill_extraction',
                'title' => 'Skill Extraction',
                'body' => "Extract technical and professional skill words from the following resume text and return as a comma separated array only, trimming off extra white spaces as needed, only the data no extra characters or text:\n\n{{resume_text}}",
            ],
            [
                'key' => 'job_skill_extraction',
                'title' => 'Job Skill Extraction',
                'body' => "Extract concise skill keywords from the following job description. Return a comma separated list of skill names only (no sentences, no extra text). Keep them lowercase and trim whitespace:\n\n{{job_description}}",
            ],
            [
                'key' => 'pii_strip',
                'title' => 'PII Anonymization',
                'body' => <<<'PROMPT'
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
PROMPT,
            ],
            [
                'key' => 'candidate_summary',
                'title' => 'Anonymized Candidate Summary',
                'body' => <<<'PROMPT'
You are an HR assistant writing an anonymized candidate brief for a hiring manager. Based solely on the anonymized resume below, produce a structured three-section assessment. Never include names, dates, school names, employer names, or any other identifying information.

Use EXACTLY this format — include the bold section headers:

**Professional Summary**
[2–3 sentences: the candidate's overall experience level, professional domain, and career focus.]

**Key Skills & Strengths**
[2–3 sentences: the most notable technical skills, tools, and areas of depth.]

**Job Fit Assessment**
[2–3 sentences: a direct comparison of this candidate's background against the {{job_title}} role at {{job_company}}. Reference specific required skills ({{job_skills}}) and state whether the candidate appears well-suited, partially suited, or not well-matched.]

Job Title: {{job_title}}
Company: {{job_company}}
Required Skills: {{job_skills}}

Anonymized Resume:
{{anonymized_text}}
PROMPT,
            ],
            [
                'key' => 'skill_gap_summary',
                'title' => 'Skill Gap Summary',
                'body' => <<<'PROMPT'
Given a position requiring these skills: {{job_skills}}
And a candidate whose profile includes: {{candidate_skills}}

Write a concise 2-3 sentence professional explanation of the key skill gaps.
Focus on what is missing or underdeveloped relative to the role requirements.
Do not mention names, companies, dates, or any identifying information.
Write in language appropriate to share directly with the candidate.
PROMPT,
            ],
        ];

        foreach ($prompts as $data) {
            Prompt::updateOrCreate(
                ['key' => $data['key']],
                [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'config' => $defaultConfig,
                ]
            );
        }
    }
}
