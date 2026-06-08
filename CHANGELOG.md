# Changelog

All notable changes to JobSage are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased] — 2026-06-08

### Platform Pivot: Personal Job-Hunting Tool → Blind HR Screening Platform

This release represents a complete architectural pivot. JobSage was originally a
personal job-seeker tool (resume management, skill tracking, cover letter
generation, job-match scoring for individual users). It has been rebuilt from the
ground up as a B2B blind HR screening platform.

The core design principle: **HR users never see a name.**

---

### Added

#### Blind Screening Pipeline
- `CandidatePipelineService` — orchestrates the full per-candidate pipeline
  synchronously: text extraction → PII stripping → skill extraction →
  skill attachment → match scoring → anonymized summary generation
- `ResumeTextExtractor` — dedicated service for PDF (smalot/pdfparser) and
  DOCX (phpoffice/phpword) text extraction, extracted from controller logic
- `OllamaService::stripPii()` — strips all PII and indirect identifiers (names,
  schools, employers, dates, URLs) before any HR-visible data is persisted
- `OllamaService::generateSkillGapSummary()` — generates a candidate-facing
  skill gap explanation at rejection time

#### Candidate Model & Migration
- `Candidate` model with full pipeline lifecycle: `pending_analysis`, `analyzed`,
  `shortlisted`, `rejected`
- Fields: `candidate_email`, `submission_token` (UUID, portal submissions only),
  `anonymized_summary`, `match_score`, `rejection_stage`, `rejection_reason`,
  `rejection_note`, `skill_gap_summary`
- `uploaded_by` FK nullable — portal self-submissions have no associated user

#### Job Model Additions
- `is_closed` boolean and `closed_at` timestamp — set when recruiter marks
  position filled
- Migration: `add_is_closed_to_job_listings`

#### Candidate Self-Submission Portal (public, no auth)
- `CandidatePortalController` with routes: apply form, submit, submitted
  confirmation, token-based status page
- Duplicate email guard per job — prevents re-submission from the same address
- Rate-limited routes (throttle:20,1) to prevent abuse
- Views: `portal/apply.blade.php`, `portal/submitted.blade.php`, `portal/status.blade.php`
- Apply button added to `jobs/show.blade.php` for guests and non-staff users

#### HR Rejection Workflow
- `HrController::rejectForm()` — structured rejection form with missing-skill
  pre-population, dropdown + free-text note
- `HrController::reject()` — strips PII from HR note via Ollama, generates
  skill gap summary, updates candidate, queues rejection email
- `resources/views/hr/candidates/reject.blade.php` — two-column form with
  confirmation modal before submitting
- `updateStatus()` restricted to `shortlisted` only — rejections require the
  dedicated form

#### Recruiter Job Closing
- `RecruiterController::closeJob()` — marks job `is_closed`, queues
  `PositionFilledMail` to all active (non-rejected) candidates with email on file
- Close-job button + confirmation modal in `recruiter/jobs/show.blade.php`
- "Filled" badge and disabled upload button in `recruiter/jobs/index.blade.php`

#### Queued Notification Mailables
- `CandidateConfirmationMail` (ShouldQueue) — sent after portal submission;
  includes anonymized profile and token-based status link
- `CandidateRejectedMail` (ShouldQueue) — sent after HR rejection; includes
  PII-stripped note and skill gap summary
- `PositionFilledMail` (ShouldQueue) — sent to active candidates when position
  is closed
- Mail views: `mail/candidate-confirmation.blade.php`,
  `mail/candidate-rejected.blade.php`, `mail/position-filled.blade.php`

#### Queue Infrastructure
- Redis queue backend (`QUEUE_CONNECTION=redis`)
- Dedicated `jobsage-queue` Docker container running `php artisan queue:work`
- `QUEUE_RETRY_AFTER=90` to handle Ollama timeout scenarios
- `docker-compose.yml` and `docker.env.example` updated with all queue variables

#### Prompt Seeder
- Added `skill_gap_summary` prompt entry with default template

#### Demo Dataset
- `DemoSeeder` — 3 realistic jobs (software engineer, marketing manager,
  accounting specialist), 8 candidates per job at various pipeline stages,
  mix of portal/recruiter submissions, some with emails, some without
- Wired into `DatabaseSeeder` behind `APP_ENV=demo` environment check

#### PHPDoc Audit
- Complete PHPDoc coverage across all `app/Services`, `app/Http/Controllers`,
  `app/Models`, and `app/Mail` classes
- Every public method documented with `@param`, `@return`, `@throws` where applicable
- Non-obvious logic blocks annotated with inline comments

#### New Feature Tests
- `CandidatePortalTest` — 9 tests covering portal apply form, submission,
  confirmation mail queuing, duplicate guard, closed-job block, validation,
  token status page, 404 on bad token
- `CandidateNotificationTest` — 5 tests covering all three mailables and their
  guard conditions
- `HrRejectionTest` — 12 tests covering form rendering, validation, PII
  stripping, mail queuing, shortlist-only `updateStatus`, role access guards

---

### Changed

#### Role System
- Roles simplified to `recruiter`, `hr`, `admin` — `job_seeker` role removed
- Admin user-edit form updated to reflect new role set
- `AdminController::updateUser()` validation: `required|in:recruiter,hr,admin`

#### OllamaService Refactor
- Removed `analyzeResume()` (wrote to deleted `user_skills` table)
- Removed `generateCoverLetter()` (job-seeker feature)
- Kept: `extractSkills()`, `promptPayload()`, `postToOllama()`, `renderTemplate()`

#### RecruiterController Upload
- Changed from multi-file bulk upload to single-file upload with optional
  `candidate_email` field for notifications

#### Environment Configuration
- `.env.example` fully rewritten with all mail, queue, and Ollama variables,
  each with explanatory comments
- `docker.env.example` fully rewritten with Docker-specific defaults and
  queue worker explanation

#### PSR-12 Compliance
- All PHP files formatted via Laravel Pint (PSR-12 + opinionated Laravel style)

---

### Removed

#### Models
- `UserSkill` — replaced by `resume_skill` pivot (skills belong to resumes, not users)
- `UserDetail` — job-seeker profile data, not applicable to HR platform
- `AiSuggestion` — job-seeker AI feature

#### Controllers
- `UserSkillController` — replaced by pipeline-level skill attachment
- `UserDetailController` — job-seeker profile management
- `AiSuggestionController` — job-seeker AI suggestions
- `ApplicationController` — job-seeker application flow
- `ResumesController` — replaced by `RecruiterController` upload flow

#### Migrations (dropped tables)
- `user_skills` table dropped
- `user_details` table dropped
- `ai_suggestions` table dropped

#### Services
- `JobMatchService` — match scoring logic absorbed into `CandidatePipelineService`

#### Views
- `dashboard.blade.php` — job-seeker dashboard replaced by role-based views
- `applications/` directory (create, index, show)
- `resumes/` directory (index, show, upload)
- `layouts/navigation.blade.php` — replaced by role-aware navbar partial

#### Routes
- All `/resumes/*` routes
- All `/applications/*` routes
- `/profile/skills/*` routes
- `/jobs/{job}/compare-resume`
- `/resumes/{id}/generate-cover-letter`
- `/resumes/{id}/match-job`
- `/user/{id}/details`

---

### Fixed

- `CandidateConfirmationMail` PHP syntax error — `??` operator cannot be used
  inside double-quoted string interpolation; extracted to variable first
- Portal views rendered blank — views incorrectly extended `layouts.guest`
  (slot-based Blade component) instead of `layouts.app` (`@yield`-based layout)
- `Job::factory()->create()` returned `null` for `is_closed` in tests — fixed
  by calling `->fresh()` to reload from DB where column default applies
- `HrRejectionTest` skill gap summary empty — test job had no listing skills;
  fixed by attaching `JobListingSkill::factory()->count(3)->create()` in `beforeEach()`

---

### Migration Notes

If upgrading from the job-seeker version of JobSage:

1. Run `php artisan migrate` — this drops `ai_suggestions`, `user_skills`,
   `user_details` and adds `candidates`, `candidate_communication_fields`, `is_closed`
2. Existing `users` with role `job_seeker` will need to be updated to
   `recruiter`, `hr`, or `admin` before they can log in
3. Existing `resumes` records are preserved but `user_id` is now nullable;
   `uploaded_by` is the new FK for recruiter uploads
4. The `JobMatchService` class has been deleted — update any custom code
   that references it to use `CandidatePipelineService` instead
