@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="mb-4">
                <a href="{{ route('jobs.show', $job->id) }}" class="text-decoration-none text-muted">
                    ← Back to job listing
                </a>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="bg-primary bg-opacity-10 rounded p-3">
                            <i class="bi bi-eye-slash fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Blind Screening Process</h5>
                            <p class="text-muted mb-0 small">
                                Your name, contact information, school names, and employer names are
                                automatically removed before any reviewer sees your application.
                                Reviewers see only your skills, experience, and accomplishments.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-4">
                    <h2 class="h4 mb-1">Apply for {{ $job->title }}</h2>
                    <p class="text-muted mb-0">{{ $job->company }} &mdash; {{ $job->location }}</p>

                    @if($job->listingSkills->isNotEmpty())
                        <div class="mt-3">
                            <small class="text-muted fw-semibold d-block mb-2">Required Skills</small>
                            @foreach($job->listingSkills as $skill)
                                <span class="badge bg-info text-dark me-1 mb-1">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="card-body p-4">

                    @if(session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('portal.submit', $job->id) }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="candidate_email" class="form-label fw-semibold">
                                Your Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control form-control-lg @error('candidate_email') is-invalid @enderror"
                                   id="candidate_email"
                                   name="candidate_email"
                                   value="{{ old('candidate_email') }}"
                                   placeholder="you@example.com"
                                   required>
                            <div class="form-text">
                                Used only to send you a confirmation and status updates.
                                Never shared with reviewers.
                            </div>
                            @error('candidate_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="resume" class="form-label fw-semibold">
                                Resume <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                   class="form-control @error('resume') is-invalid @enderror"
                                   id="resume"
                                   name="resume"
                                   accept=".pdf,.doc,.docx"
                                   required>
                            <div class="form-text">PDF, DOC, or DOCX — max 10 MB.</div>
                            @error('resume')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
