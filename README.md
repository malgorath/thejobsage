# JobSage — Blind HR Screening Platform

> Eliminate hiring bias at the source. HR never sees a name.

---

## The Problem

### Unconscious bias in hiring

Studies consistently show that identical resumes receive fewer callbacks when the
applicant's name signals a minority background. Screening decisions are made in
seconds, driven by pattern-matching against the reviewer's mental model — not merit.

### ATS keyword failure

Applicant tracking systems reject qualified candidates who describe the same skill
with different words. A nurse with "medication administration" experience fails a
search for "medication management." The resume never reaches a human.

### Candidate ghosting

Most applicants receive no feedback. No rejection email, no reason, no skill gap
explanation. Candidates waste months reapplying without knowing what to fix.

---

## The Solution

JobSage is a **blind HR screening platform** built for small-to-mid-sized hiring
teams. Every resume that enters the system is immediately stripped of all PII
by a local LLM before any human reviewer sees it.

HR sees:
- An anonymized skill-based candidate summary
- A 0–100 skill match score against the job's requirements
- Extracted skill chips, ranked by relevance

HR does **not** see:
- Names, email addresses, phone numbers
- School names, graduation years
- Employer names, location details
- Any field that correlates with protected characteristics

Recruiters retain access to the original resume file for extending offers to
shortlisted candidates — but only after the blind screening decision has been made.

---

## Compliance

### EEOC (US Equal Employment Opportunity Commission)
Blind screening directly addresses the EEOC's guidance on reducing disparate
treatment in hiring. By removing race- and gender-correlated identifiers before
any scoring or shortlisting decision, the platform supports defensible,
criteria-based selection.

### GDPR (EU General Data Protection Regulation)
- Candidate PII is stripped at ingest — the minimal data principle
- Rejection notes authored by HR users are passed through the PII stripper
  before being stored or emailed, preventing accidental re-identification
- Candidates can track their status via a tokenized link without creating an account
- No tracking cookies or third-party analytics on the candidate portal

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Docker Compose                        │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  jobsage-app │  │ jobsage-nginx │  │  jobsage-db      │  │
│  │  Laravel 12  │  │  Nginx 1.25  │  │  MySQL 8.0       │  │
│  │  PHP 8.2     │  │              │  │                  │  │
│  └──────┬───────┘  └──────────────┘  └──────────────────┘  │
│         │                                                    │
│  ┌──────┴───────┐  ┌──────────────────────────────────────┐ │
│  │ jobsage-queue│  │  jobsage-redis                       │ │
│  │ queue:work   │  │  Sessions · Cache · Queue            │ │
│  └──────────────┘  └──────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
                    ┌─────────▼─────────┐
                    │  Ollama (host)     │
                    │  llama3.2 / etc.  │
                    │  Port 11434       │
                    └───────────────────┘
```

**Key services:**

| Service | Purpose |
|---|---|
| `jobsage-app` | PHP-FPM application container |
| `jobsage-nginx` | Reverse proxy, serves static assets |
| `jobsage-db` | MySQL 8 — all persistent data |
| `jobsage-redis` | Sessions, cache, and the queue backend |
| `jobsage-queue` | Dedicated `queue:work` worker for mail |
| Ollama (host) | Local LLM — PII stripping, skill extraction, summaries |

**Pipeline flow (per candidate upload):**

1. Resume binary stored in MySQL BLOB (`resumes.file_data`)
2. `ResumeTextExtractor` parses PDF/DOCX to plain text
3. `OllamaService::stripPii()` removes all identifiers → anonymized text
4. `OllamaService::extractSkills()` extracts skill keywords
5. Skills attached to `resume_skill` pivot
6. Skill-overlap match score calculated (0–100)
7. `OllamaService` generates 3-5 sentence anonymized summary
8. `Candidate` record updated: `status = analyzed`
9. HR can now see the candidate — no PII was persisted

---

## Roles

| Role | Access |
|---|---|
| **admin** | Full platform access — users, jobs, prompts, candidates, skills |
| **recruiter** | Upload resumes, download originals, close positions, manage pipeline |
| **hr** | View anonymized candidates only — no names, no raw files, no downloads |

Middleware aliases are defined in `bootstrap/app.php`. HR users are
**architecturally prevented** from accessing the recruiter download endpoint —
the `recruiter` middleware blocks any other role.

---

## Local Setup

### Prerequisites

- Docker + Docker Compose
- [Ollama](https://ollama.ai) running on the host machine with a model pulled:
  ```bash
  ollama pull llama3.2
  ```

### Start the stack

```bash
# Clone and enter the project
git clone <repo-url> thejobsage
cd thejobsage

# Copy environment files
cp docker.env.example .env

# Generate app key
docker compose run --rm jobsage-app php artisan key:generate

# Build and start all containers
docker compose up -d

# Run migrations
docker compose exec jobsage-app php artisan migrate

# Seed base data (prompts, skills, admin user)
docker compose exec jobsage-app php artisan db:seed

# Optional: seed a full demo dataset
docker compose exec jobsage-app php artisan db:seed --class=DemoSeeder
```

The application is available at **http://localhost:7500**.

### Default admin credentials (from UserSeeder)

Check `database/seeders/UserSeeder.php` for the seeded admin email/password.

### Demo mode (full dataset)

Set `APP_ENV=demo` in your `.env` and run `php artisan db:seed` to also execute
`DemoSeeder`, which creates 3 realistic job listings with 8 candidates each at
various pipeline stages.

---

## Environment Variables Reference

### Core Application

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `JobSage` | Application display name |
| `APP_ENV` | `local` | Set to `demo` to enable DemoSeeder |
| `APP_KEY` | — | Generate with `php artisan key:generate` |
| `APP_DEBUG` | `true` | Set `false` in production |
| `APP_URL` | `http://localhost:7500` | Public URL |

### Database

| Variable | Default | Description |
|---|---|---|
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | `db` | Docker service name |
| `DB_DATABASE` | `resume_builder` | |
| `DB_USERNAME` | `laravel` | |
| `DB_PASSWORD` | `secret` | |

### Queue

| Variable | Default | Description |
|---|---|---|
| `QUEUE_CONNECTION` | `redis` | All notification mails are queued |
| `QUEUE_RETRY_AFTER` | `90` | Seconds before a stuck job is retried |

### Mail

| Variable | Default | Description |
|---|---|---|
| `MAIL_MAILER` | `log` | Use `smtp` for real delivery |
| `MAIL_HOST` | `127.0.0.1` | SMTP server (e.g. Mailtrap sandbox) |
| `MAIL_PORT` | `2525` | Mailtrap sandbox port |
| `MAIL_FROM_ADDRESS` | `noreply@jobsage.example.com` | Sender address |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Sender display name |

### Ollama

| Variable | Default | Description |
|---|---|---|
| `OLLAMA_API_URL` | `http://host.docker.internal:11434/api/generate` | Ollama API endpoint |
| `OLLAMA_LLM_MODEL` | `llama3.2` | Model name (must be pulled in Ollama) |
| `OLLAMA_TIMEOUT` | `120` | HTTP timeout in seconds |

---

## Candidate Portal

The self-submission portal is publicly accessible — no account required.

- `GET /jobs` — browse open positions
- `GET /jobs/{job}/apply` — application form (redirects if position is closed)
- `POST /jobs/{job}/apply` — submit resume + email address
- `GET /portal/submitted` — confirmation page
- `GET /submissions/{token}` — check application status by token (emailed on submit)

Rate-limited to 20 requests/minute per IP.

---

## Notification Emails

Three queued mailables fire automatically:

| Mailable | Trigger | Recipient |
|---|---|---|
| `CandidateConfirmationMail` | Candidate submits via portal | Candidate |
| `CandidateRejectedMail` | HR completes rejection form | Candidate (if email on file) |
| `PositionFilledMail` | Recruiter marks position filled | All active (non-rejected) candidates with email |

Rejection notes are passed through `OllamaService::stripPii()` before being stored
or included in any email. The skill gap summary is LLM-generated and contains
no PII by construction.

---

## Editing AI Prompts

All LLM prompt templates are editable at runtime via **Admin → Prompts**. No
code deployment required. Key prompt records:

| Key | Purpose |
|---|---|
| `pii_strip` | Resume anonymization (EEOC compliance) |
| `candidate_summary` | 3-5 sentence anonymized profile summary |
| `skill_gap_summary` | Candidate-facing rejection explanation |
| `job_skill_extraction` | Extract required skills from job description |
| `skill_extraction` | Extract skills from anonymized resume text |

---

## Screenshots

_Screenshots will be added here._

---

## License

MIT License. See [LICENSE](LICENSE) for details.
