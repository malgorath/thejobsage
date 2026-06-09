@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h1 class="display-5 fw-bold mb-4">JobSage</h1>

                        <div class="mb-4">
                            <div class="text-start">
                                <p class="lead mb-3">
                                    <strong>JobSage</strong> is a blind HR screening platform that removes personally identifiable information from every resume before your team sees it — so hiring decisions are made on skills and experience, not names, schools, or backgrounds.
                                </p>

                                <p class="mb-3">
                                    Every resume submitted through JobSage passes through a local AI pipeline that strips names, contact details, school names, employer names, and any other field that correlates with protected characteristics. HR reviewers see only an anonymized candidate summary, a skill match score, and extracted skill chips — never the raw file.
                                </p>

                                <p class="mb-3">
                                    <strong>How it works:</strong>
                                </p>
                                <ul class="mb-3">
                                    <li><strong>Recruiter uploads a resume</strong> (PDF or Word) against a specific job opening — or candidates apply directly through the self-submission portal.</li>
                                    <li><strong>The AI pipeline runs immediately:</strong> text is extracted, all PII is stripped, skills are identified, and a 0–100 match score is calculated against the job's required skills.</li>
                                    <li><strong>The raw file is discarded immediately</strong> — it is never written to disk or stored in the database. Only the anonymized output is retained.</li>
                                    <li><strong>Recruiters preview before saving:</strong> a review step shows the anonymized summary, extracted skills, and match score before anything is committed to the database — and can cancel to discard everything.</li>
                                    <li><strong>HR sees only anonymized data:</strong> a professional summary, skill chips, and a match score. No name. No school. No employer history.</li>
                                    <li><strong>Shortlist or reject with structured feedback:</strong> HR selects a rejection reason and writes a free-text note — the note is also PII-stripped before being stored or sent to the candidate.</li>
                                    <li><strong>Candidates are notified automatically:</strong> confirmation on submission, a skill gap explanation on rejection, and a position-filled notice when the role closes.</li>
                                </ul>

                                <p class="mb-0">
                                    JobSage runs entirely on your own infrastructure — no candidate data is ever sent to third-party AI providers. It supports <strong>Ollama</strong> natively and any <strong>OpenAI-compatible local model server</strong> (llama.cpp, LM Studio, vLLM, LocalAI) via a single environment variable. Prompt templates for PII stripping, skill extraction, and candidate summaries are editable by admins at runtime, giving your team full control over AI behaviour without a code deployment.
                                </p>
                            </div>
                        </div>

                        {{-- Privacy guarantee callout --}}
                        <div class="alert alert-success border-0 mt-4 mb-4" role="note">
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-lock-fill fs-4 text-success flex-shrink-0 mt-1"></i>
                                <div>
                                    <strong class="d-block mb-1">Resume files are never stored</strong>
                                    Raw resumes are processed entirely in memory and discarded the moment text extraction completes.
                                    No original file is written to disk or saved to the database — only anonymized extracted data persists.
                                    Recruiters review exactly what will be saved before confirming, and can cancel at any time to discard everything with no record created.
                                </div>
                            </div>
                        </div>

                        <div class="clearfix"></div>

                        <div class="mt-4 pt-4 border-top">
                            <div class="row">
                                <div class="col-md-3 text-center mb-3 mb-md-0">
                                    <i class="bi bi-shield-check fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-semibold">PII Stripped at Ingest</h5>
                                    <p class="text-muted small">Names, schools, employers, and dates are removed before any human reviewer sees the candidate</p>
                                </div>
                                <div class="col-md-3 text-center mb-3 mb-md-0">
                                    <i class="bi bi-file-earmark-x fs-1 text-success mb-2"></i>
                                    <h5 class="fw-semibold">Files Never Stored</h5>
                                    <p class="text-muted small">Raw resumes are discarded immediately after processing — only anonymized text and skills are saved</p>
                                </div>
                                <div class="col-md-3 text-center mb-3 mb-md-0">
                                    <i class="bi bi-bar-chart-line fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-semibold">Skill-Based Scoring</h5>
                                    <p class="text-muted small">Every candidate is ranked by skill overlap against the job's requirements — not by subjective impression</p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <i class="bi bi-hdd-network fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-semibold">Local AI, Private by Design</h5>
                                    <p class="text-muted small">Runs on Ollama or any OpenAI-compatible server (llama.cpp, LM Studio, vLLM) — no data leaves your network</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
