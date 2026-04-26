# Laravel Resume & Job Search Platform

A full-featured Laravel application for managing resumes, tracking job applications, and leveraging local LLMs to provide AI-driven resume analysis, skill extraction, and job matching — all without sending data to external APIs.

## Features

- **Resume management** — upload, parse, and analyze PDF and DOCX resumes
- **Job application tracking** — manage and organize your entire job search
- **AI-powered analysis** — resume analysis, skill extraction, and job matching via local Ollama LLM
- **RAG job matching** — combines your resume content with job descriptions for intelligent match scoring
- **Admin panel** — full dashboard for managing users, jobs, and applications
- **110 tests / 370 assertions** — comprehensive test coverage across all features

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

**Job matching** — classic RAG combining the user's resume text (retrieved context) with a job description (query) into an augmented prompt that returns a match score and suggestions:

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

---

## Installation

### Option 1: Docker (Recommended)

**Prerequisites:** Docker 20.10+, Docker Compose 2.0+

```bash
git clone <repository-url>
cd laravel-resume-builder-ai
cp docker.env.example .env
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Open `http://localhost:8080`.

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
| nginx | 8080 | Web server |
| db (MySQL) | 3306 | Database |
| redis | 6379 | Cache & queue |
| queue | — | Queue worker |

### Option 2: Traditional PHP

**Prerequisites:** PHP 8.2+, Composer, Node.js, MySQL

```bash
git clone <repository-url>
cd laravel-resume-builder-ai
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
- **User management** — view all users, roles, and activity stats
- **Job management** — full CRUD on job listings (admin-only posting)
- **Application management** — view all applications and statuses
- **LLM prompt management** — configure prompts and Ollama parameters per-task at `/admin/prompts`

Prompt fields support placeholders (`{{resume_text}}`, `{{job_description}}`, `{{applicant_name}}`) and per-prompt Ollama config (`temperature`, `top_p`, `top_k`, `num_ctx`, etc.). If a DB prompt is missing, built-in defaults are used.

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
| SkillSeeder | Technical and professional skills |
| PromptSeeder | Baseline LLM prompts and Ollama configs |

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

**110 tests / 370 assertions** covering:

| Suite | Tests |
|---|---|
| Authentication | 7 |
| Resume (upload, parse, AI analysis) | 13 |
| Admin (dashboard, users, jobs, auth) | 21 |
| Job applications | 9 |
| Profile & skills | 5 |
| AI services (mocked) | 6 |
| Model relationships | 8 |
| UI / accessibility | 20 |

---

## Roadmap

- **Agentic workflows** — autonomous agents to scrape job boards based on resume keywords
- **Vector database** — migrate to pgvector/ChromaDB for semantic search across large job sets
- **Cover letter generator** — context-aware generation using resume + job description
- **LinkedIn integration** — one-click profile import and sync

---

## License

MIT
