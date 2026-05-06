# JobSage

A full-featured Laravel application for managing resumes, tracking job applications, and leveraging local LLMs to provide AI-driven resume analysis, skill extraction, and job matching — all without sending data to external APIs.

## Features

- **Resume management** — upload, parse, and analyze PDF and DOCX resumes
- **Job application tracking** — manage and organize your entire job search
- **AI-powered analysis** — resume analysis, skill extraction, and job matching via local Ollama LLM
- **RAG job matching** — combines your resume content with job descriptions for intelligent match scoring
- **Job skill extraction** — Ollama automatically extracts and tags required skills from job descriptions on first view
- **Job match percentage** — calculates overlap between your profile skills and each job's extracted skill tags
- **Resume comparison modal** — compare your primary resume against a job description via AI with a single click
- **Admin-managed prompts** — configure every LLM prompt and Ollama parameters (temperature, top_p, etc.) through the admin UI without code changes
- **Resume loading overlay** — visual feedback while AI analysis runs in the background
- **Graceful AI degradation** — all Ollama calls fall back gracefully when the service is unavailable
- **Admin panel** — full dashboard for managing users, jobs, companies, skills, prompts, and applications
- **Comprehensive test coverage** — 125+ tests across all features and services

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | PHP 8.2 / Laravel |
| Frontend | React, Vite, Bootstrap |
| Database | MySQL |
| Cache & Queue | Redis |
| LLM Backend | Ollama (local) |
| PDF Parsing | smalot/pdfparser |
| DOCX Parsing | phpoffice/phpword |
| Containerization | Docker / Docker Compose |

---

## AI Architecture: Document Parsing & RAG

This application uses a **Retrieval-Augmented Generation (RAG)** approach to analyze resumes and provide intelligent recommendations.

### Document Parsing

| Format | Library | Notes |
|---|---|---|
| PDF | smalot/pdfparser | Extracts text from binary storage on demand |
| DOC/DOCX | phpoffice/phpword | Iterates sections and elements for full text |

**PDF parsing flow:**
```
Upload → Binary Storage → On-Demand Parsing → Text Extraction → AI Analysis
```

```php
$parser = new Parser();
$pdf = $parser->parseContent($binaryContent);
$text = $pdf->getText();
```

### RAG Implementation

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Resume Upload  │────▶│  Text Extraction │────▶│  Store in DB    │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                                          │
                                                          ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  AI Response    │◀────│  Ollama LLM      │◀────│  Build Prompt   │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

**Resume analysis** — retrieves stored document, extracts text, sends to Ollama with an analysis prompt, stores result for future reference.

**Skill extraction** — LLM extracts technical and professional skills from resume text, parses the response, and stores skills in the `skills` table linked via `user_skills` pivot.

**Job skill extraction** — when a job listing is first viewed and has no skill tags, `JobSkillService` sends the job description to Ollama using the `job_skill_extraction` prompt, then parses the comma-separated response into `job_listing_skills` records linked to the job via a many-to-many pivot.

**Job match percentage** — `JobMatchService::calculateMatch()` intersects the job's extracted skill tags with the authenticated user's profile skills and returns a 0-100 integer displayed on the jobs index page.

**Job comparison** — `POST /jobs/{job}/compare-resume` retrieves the user's primary resume (`is_primary = true`), builds a RAG prompt with the resume text and job description, and returns a JSON report from Ollama:

```php
public function matchJob($resumeText, $jobDescription)
{
    $prompt = "Compare this resume with the job description and provide
               a match score (0-100) along with suggestions:

               Resume: $resumeText

               Job Description: $jobDescription";

    return $this->callOllama($prompt);
}
```

### Admin-Managed Prompt System

Every AI prompt and Ollama configuration parameter is stored in the `prompts` table and editable through the admin UI at `/admin/prompts`. If a DB entry for a given `key` is missing, a built-in fallback template is used automatically.

| Prompt Key | Description |
|---|---|
| `resume_analysis` | Full resume analysis and feedback |
| `skill_extraction` | Extract comma-separated skills from resume text |
| `job_match` | Score and compare resume against a job description |
| `cover_letter` | Generate a tailored cover letter |
| `job_skill_extraction` | Extract required skills from a job description |

**Prompt template variables** — wrap field names in `{{double_braces}}`:

| Variable | Used In |
|---|---|
| `{{resume_text}}` | resume_analysis, skill_extraction, job_match, cover_letter |
| `{{job_description}}` | job_match, cover_letter, job_skill_extraction |
| `{{applicant_name}}` | cover_letter |

**Per-prompt Ollama configuration** — stored as JSON in the `config` column and merged over the service defaults at call time:

```json
{
  "temperature": 0.3,
  "top_p": 0.9,
  "top_k": 40,
  "num_ctx": 4096,
  "max_tokens": 512
}
```

### Configuring the AI Backend

```env
# .env
OLLAMA_API_URL=http://localhost:11434/api/generate
OLLAMA_LLM_MODEL=gemma3:4b

# Docker environments
# OLLAMA_API_URL=http://host.docker.internal:11434/api/generate
```

**Recommended models:**

| Model | Size | Best For |
|---|---|---|
| `gemma3:4b` | 4B | Fast, lightweight analysis |
| `llama3.2` | 3B | Good balance of speed/quality |
| `mistral` | 7B | Higher quality analysis |
| `llama3.1:8b` | 8B | Best quality, slower |

All Ollama calls are wrapped in try/catch — when Ollama is unavailable the application degrades gracefully (stores a fallback message or silently skips extraction) rather than surfacing an unhandled exception to the user.

---

## Installation

### Option 1: Docker (Recommended)

**Prerequisites:** Docker 20.10+, Docker Compose 2.0+

```bash
git clone <repository-url>
cd jobsage
cp docker.env.example .env
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Open `http://localhost:7500`.

**Make commands:**

```bash
make setup        # Full initial setup
make up           # Start containers
make down         # Stop containers
make logs         # View logs
make shell        # Open shell in app container
make fresh        # Fresh database with seeders
make test         # Run test suite
make artisan cmd="migrate"
make composer cmd="require package/name"
make npm cmd="run dev"
```

**Docker services:**

| Service | Port | Description |
|---|---|---|
| app (PHP-FPM) | 9000 | Application |
| nginx | 7500 | Web server |
| db (MySQL) | 3306 | Database |
| redis | 6379 | Cache & queue |
| queue | — | Queue worker |

### Option 2: Traditional PHP

**Prerequisites:** PHP 8.2+, Composer, Node.js, MySQL

```bash
git clone <repository-url>
cd jobsage
composer install
npm install
cp .env.example .env
php artisan key:generate
# Configure DB credentials in .env
php artisan migrate
npm run build
php artisan serve
```

---

## Admin Panel

Access at `/admin/dashboard` (admin role required).

**Capabilities:**
- **User management** — view all users, roles, and activity stats; edit or remove accounts (self-deletion protected)
- **Job management** — full CRUD on job listings (admin-only posting)
- **Company management** — full CRUD on company records
- **Skill management** — full CRUD on the global skills catalog
- **Application management** — view all applications and statuses
- **LLM prompt management** — configure prompts and Ollama parameters per-task at `/admin/prompts`

Routes are protected by `EnsureUserIsAdmin` middleware — non-admin access returns 403.

---

## Database Seeding

```bash
# Docker
docker compose exec app php artisan db:seed

# Fresh seed
docker compose exec app php artisan migrate:fresh --seed
```

**Seeded data:**

| Seeder | Contents |
|---|---|
| UserSeeder | Admin + test users |
| JobSeeder | 15 realistic job listings across 5 companies |
| SkillSeeder | Technical and professional skills (truncates user_skills first) |
| PromptSeeder | Baseline LLM prompts and Ollama configs for all 5 prompt keys |

Default credentials (change after first login):
- Admin: `admin@example.com` / `admin123`
- Test user: `test@home.net` / `password`

---

## Testing

```bash
# Docker
docker compose exec app php artisan test

# Local
php artisan test

# Specific test
php artisan test --filter ResumeTest

# With coverage (requires Xdebug)
php artisan test --coverage
```

**125+ tests** covering:

| Suite | File(s) | Focus |
|---|---|---|
| Authentication | `Auth/AuthenticationTest`, `Auth/RegistrationTest`, `Auth/AuthUITest`, `Auth/PasswordResetTest`, `Auth/PasswordConfirmationTest`, `Auth/EmailVerificationTest`, `Auth/PasswordUpdateTest` | Login, register, password flows, UI |
| Password change | `PasswordChangeTest` | Current-password confirmation, update |
| Resume | `ResumeTest`, `ResumeOverlayTest` | Upload, parse, AI analysis, loading overlay |
| Admin panel | `AdminTest` | Dashboard, auth, job/app management |
| Admin CRUD | `CompanyCrudTest`, `SkillCrudTest`, `JobCrudTest`, `UserManagementTest` | Full CRUD + self-delete protection |
| Job applications | `JobApplicationTest` | Apply, status updates |
| Profile & skills | `ProfileTest`, `ProfileSkillsTest` | Edit, skill add/remove |
| AI services | `AiServiceTest` | Mocked Ollama responses |
| Ollama service (unit) | `OllamaServiceTest` | Fallback on connection failure, DB prompt + config injection |
| Job skill extraction | `JobSkillExtractionTest` | Extract on create, lazy-extract on first view, display on detail page |
| Job match display | `JobMatchDisplayTest` | Match percent calculation and display on jobs index |
| Resume comparison | `JobCompareResumeTest` | Primary resume selection, AI report returned as JSON |
| Prompt management | `PromptAdminTest`, `PromptSeederTest` | Admin CRUD, non-admin blocked, seeder baseline |
| Skill seeder | `SkillSeederTest` | Truncate user_skills, re-seed without FK errors |
| Navbar | `NavbarTest` | Link presence for all roles |
| Model relationships | `ModelRelationshipTest` | Eloquent relation integrity |

---

## Data Models

| Model | Key Relations | Notes |
|---|---|---|
| `User` | hasMany Resume, UserSkill, Application | `role` column: `user` / `admin` |
| `Resume` | belongsTo User | `is_primary` flag; binary PDF/DOCX storage |
| `Job` | belongsToMany JobListingSkill | Skill pivot: `job_job_listing_skill` |
| `JobListingSkill` | belongsToMany Job | Extracted from description by Ollama |
| `Skill` | belongsToMany User (via UserSkill) | Global catalog; seeded by `SkillSeeder` |
| `Application` | belongsTo User, Job | Status tracking |
| `Company` | hasMany Job | Admin-managed |
| `Prompt` | — | `key`, `body`, `config` (JSON Ollama overrides) |

---

## Roadmap

- **Agentic workflows** — autonomous agents to scrape job boards based on resume keywords
- **Vector database** — migrate to pgvector/ChromaDB for semantic search across large job sets
- **Cover letter generator** — context-aware generation using resume + job description
- **LinkedIn integration** — one-click profile import and sync

---

## License

MIT
