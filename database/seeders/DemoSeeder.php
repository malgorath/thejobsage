<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Job;
use App\Models\JobListingSkill;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a realistic demo dataset for the blind screening platform.
 *
 * Creates:
 *  - 3 job listings (software engineer, marketing manager, accounting specialist)
 *  - 1 recruiter + 1 HR user for the demo company
 *  - 8 candidates per job at various pipeline stages
 *  - A mix of portal-submitted (no uploaded_by) and recruiter-uploaded candidates
 *  - Some candidates with emails, some without
 *  - Realistic anonymized summaries and skill sets
 *
 * Only called when APP_ENV=demo (wired via DatabaseSeeder).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $recruiter = User::firstOrCreate(
            ['email' => 'recruiter@demo.jobsage.test'],
            [
                'name' => 'Demo Recruiter',
                'password' => bcrypt('password'),
                'role' => 'recruiter',
            ]
        );

        User::firstOrCreate(
            ['email' => 'hr@demo.jobsage.test'],
            [
                'name' => 'Demo HR Manager',
                'password' => bcrypt('password'),
                'role' => 'hr',
            ]
        );

        $this->seedJob(
            $recruiter,
            'Senior Software Engineer',
            'Acme Technologies',
            'Remote',
            'We are seeking an experienced Senior Software Engineer to join our platform team. '
            .'You will design and build scalable microservices, mentor junior engineers, and drive '
            .'technical roadmap decisions. Strong experience with PHP, Laravel, Docker, and REST API '
            .'design is required. Knowledge of React, Redis, and CI/CD pipelines is a plus.',
            ['php', 'laravel', 'docker', 'rest api', 'mysql', 'redis', 'git', 'react'],
        );

        $this->seedJob(
            $recruiter,
            'Marketing Manager',
            'Bright Horizon Media',
            'New York, NY',
            'Bright Horizon Media is hiring a Marketing Manager to lead our digital and content '
            .'marketing initiatives. You will manage a team of specialists, own the editorial calendar, '
            .'and drive brand awareness through SEO, paid media, and social campaigns. '
            .'Proven B2B SaaS marketing experience required.',
            ['content marketing', 'seo', 'paid media', 'social media', 'hubspot', 'google analytics', 'copywriting'],
        );

        $this->seedJob(
            $recruiter,
            'Accounting Specialist',
            'Meridian Financial Group',
            'Chicago, IL',
            'Meridian Financial Group is looking for a detail-oriented Accounting Specialist to '
            .'join our finance team. Responsibilities include accounts payable/receivable, monthly '
            .'reconciliations, payroll support, and assisting with audits. Proficiency in QuickBooks, '
            .'Excel, and GAAP principles is required.',
            ['quickbooks', 'excel', 'accounts payable', 'accounts receivable', 'gaap', 'payroll', 'reconciliation'],
        );
    }

    private function seedJob(User $recruiter, string $title, string $company, string $location, string $description, array $skills): void
    {
        $job = Job::create([
            'title' => $title,
            'company' => $company,
            'location' => $location,
            'description' => $description,
        ]);

        foreach ($skills as $name) {
            $skill = JobListingSkill::firstOrCreate(['name' => $name]);
            $job->listingSkills()->syncWithoutDetaching([$skill->id]);
        }

        $this->seedCandidates($job, $recruiter, $skills);
    }

    private function seedCandidates(Job $job, User $recruiter, array $jobSkills): void
    {
        $candidates = $this->candidateProfiles($job->title, $jobSkills);

        foreach ($candidates as $profile) {
            $resume = Resume::create([
                'user_id' => null,
                'uploaded_by' => $profile['uploaded_by'] === 'recruiter' ? $recruiter->id : null,
                'filename' => Str::slug($profile['label']).'_resume.pdf',
                'mime_type' => 'application/pdf',
                'file_data' => '% Placeholder PDF binary for '.$profile['label'],
            ]);

            foreach ($profile['skills'] as $skillName) {
                $skill = Skill::firstOrCreate(['name' => mb_strtolower($skillName)]);
                $resume->skills()->syncWithoutDetaching([$skill->id]);
            }

            $matchScore = $this->calculateScore($jobSkills, $profile['skills']);

            $candidate = Candidate::create([
                'job_id' => $job->id,
                'resume_id' => $resume->id,
                'uploaded_by' => $profile['uploaded_by'] === 'recruiter' ? $recruiter->id : null,
                'candidate_email' => $profile['email'],
                'submission_token' => $profile['uploaded_by'] === 'portal' ? Str::uuid()->toString() : null,
                'anonymized_summary' => $profile['summary'],
                'match_score' => $matchScore,
                'status' => $profile['status'],
                'rejection_stage' => $profile['rejection_stage'] ?? null,
                'rejection_reason' => $profile['rejection_reason'] ?? null,
                'rejection_note' => $profile['rejection_note'] ?? null,
                'skill_gap_summary' => $profile['skill_gap_summary'] ?? null,
            ]);
        }
    }

    private function calculateScore(array $jobSkills, array $candidateSkills): int
    {
        if (empty($jobSkills)) {
            return 0;
        }
        $job = array_map('mb_strtolower', $jobSkills);
        $cand = array_map('mb_strtolower', $candidateSkills);

        return (int) round(count(array_intersect($job, $cand)) / count($job) * 100);
    }

    private function candidateProfiles(string $jobTitle, array $jobSkills): array
    {
        $topSkills = array_slice($jobSkills, 0, 5);
        $midSkills = array_slice($jobSkills, 0, 3);
        $lowSkills = array_slice($jobSkills, 0, 1);

        return [
            // Strong portal submission — shortlisted
            [
                'label' => 'strong-portal-shortlisted',
                'uploaded_by' => 'portal',
                'email' => 'candidate.strong@mailtest.example',
                'skills' => array_merge($topSkills, ['communication', 'agile']),
                'status' => 'shortlisted',
                'summary' => "Experienced professional with a strong match across the core skill requirements for the {$jobTitle} role. Demonstrated track record of delivering high-quality work in fast-paced environments. Proficient in all primary technical areas with additional strengths in communication and agile methodologies.",
                'rejection_stage' => null,
                'rejection_reason' => null,
                'rejection_note' => null,
                'skill_gap_summary' => null,
            ],
            // Good recruiter upload — shortlisted
            [
                'label' => 'good-recruiter-shortlisted',
                'uploaded_by' => 'recruiter',
                'email' => 'candidate.good@mailtest.example',
                'skills' => array_merge($topSkills, ['project management']),
                'status' => 'shortlisted',
                'summary' => "Solid candidate with experience across most required areas for the {$jobTitle} position. Has shown consistent growth in technical expertise and brings a background in project management that could add cross-functional value to the team.",
                'rejection_stage' => null,
                'rejection_reason' => null,
                'rejection_note' => null,
                'skill_gap_summary' => null,
            ],
            // Medium match, portal, just analyzed — awaiting HR decision
            [
                'label' => 'medium-portal-analyzed',
                'uploaded_by' => 'portal',
                'email' => 'candidate.medium@mailtest.example',
                'skills' => array_merge($midSkills, ['communication']),
                'status' => 'analyzed',
                'summary' => "Candidate with foundational skills in several areas relevant to the {$jobTitle} role. Demonstrates clear communication and team collaboration skills. Some gaps exist in the more advanced technical requirements but the core foundation is present.",
                'rejection_stage' => null,
                'rejection_reason' => null,
                'rejection_note' => null,
                'skill_gap_summary' => null,
            ],
            // Medium match, recruiter upload, analyzed — no email
            [
                'label' => 'medium-recruiter-no-email',
                'uploaded_by' => 'recruiter',
                'email' => null,
                'skills' => $midSkills,
                'status' => 'analyzed',
                'summary' => "Candidate demonstrates competency in several core areas of the {$jobTitle} role. The profile reflects a practical, applied skill set with room for growth in higher-complexity scenarios. No email on file — notifications cannot be sent.",
                'rejection_stage' => null,
                'rejection_reason' => null,
                'rejection_note' => null,
                'skill_gap_summary' => null,
            ],
            // Portal submission still pending analysis
            [
                'label' => 'pending-portal',
                'uploaded_by' => 'portal',
                'email' => 'candidate.pending@mailtest.example',
                'skills' => [],
                'status' => 'pending_analysis',
                'summary' => null,
                'rejection_stage' => null,
                'rejection_reason' => null,
                'rejection_note' => null,
                'skill_gap_summary' => null,
            ],
            // Rejected at screening with email — skill gap
            [
                'label' => 'rejected-screening-email',
                'uploaded_by' => 'portal',
                'email' => 'candidate.rejected.a@mailtest.example',
                'skills' => $lowSkills,
                'status' => 'rejected',
                'summary' => "Candidate has entry-level exposure to some areas but lacks the breadth of experience required for the {$jobTitle} role at this time.",
                'rejection_stage' => 'screening',
                'rejection_reason' => 'skill_gap',
                'rejection_note' => 'The profile does not meet the minimum technical requirements for this position. The role requires broader experience across the full skill set.',
                'skill_gap_summary' => 'The position requires proficiency across '.implode(', ', $jobSkills).'. The candidate\'s profile demonstrates limited experience in most of these areas. We encourage further skill development before reapplying.',
            ],
            // Rejected at interview — no email
            [
                'label' => 'rejected-interview-no-email',
                'uploaded_by' => 'recruiter',
                'email' => null,
                'skills' => $midSkills,
                'status' => 'rejected',
                'summary' => 'Candidate showed solid foundational knowledge during screening but the interview revealed gaps in depth of experience for the seniority level required.',
                'rejection_stage' => 'interview',
                'rejection_reason' => 'experience_level',
                'rejection_note' => 'Strong foundational background, but interview demonstrated the experience level is not yet appropriate for this senior position.',
                'skill_gap_summary' => 'While the foundational skill set aligns with the role, the depth of hands-on experience in a senior capacity was not demonstrated. Further professional experience in a leading technical role would strengthen the candidacy.',
            ],
            // Weak recruiter upload — rejected at screening with email
            [
                'label' => 'weak-recruiter-rejected',
                'uploaded_by' => 'recruiter',
                'email' => 'candidate.weak@mailtest.example',
                'skills' => ['communication', 'microsoft office'],
                'status' => 'rejected',
                'summary' => "Candidate has general office and communication skills but lacks the specific technical background required for the {$jobTitle} position.",
                'rejection_stage' => 'screening',
                'rejection_reason' => 'skill_gap',
                'rejection_note' => 'The technical skills required for this role are not present in the candidate profile. The submission was not a match for the position.',
                'skill_gap_summary' => 'This role requires specific technical expertise in '.implode(', ', array_slice($jobSkills, 0, 4)).'. The candidate profile shows general professional skills but does not demonstrate the required technical background. Targeted upskilling in these areas would be needed before this type of role becomes accessible.',
            ],
        ];
    }
}
