@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('recruiter.jobs.show', $job->id) }}" class="btn btn-secondary">← Back to Candidates</a>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="h4 mb-0">Upload Candidate Resume</h2>
        <p class="mb-0 text-muted mt-1">
            <strong>{{ $job->title }}</strong> &mdash; {{ $job->company }}
        </p>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bi bi-shield-check"></i>
            Uploaded resumes are automatically anonymized by AI before HR review.
            Names, contact details, school names, and employer names are stripped.
            Only skills and experience are shown to reviewers.
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ route('recruiter.upload', $job->id) }}"
              enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="resume" class="form-label fw-semibold">
                    Resume File <span class="text-danger">*</span>
                </label>
                <input type="file"
                       class="form-control @error('resume') is-invalid @enderror"
                       id="resume"
                       name="resume"
                       accept=".pdf,.doc,.docx"
                       required>
                <div class="form-text">Accepted: PDF, DOC, DOCX. Max 10 MB.</div>
                @error('resume')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="candidate_email" class="form-label fw-semibold">
                    Candidate Email <span class="text-muted fw-normal">(optional)</span>
                </label>
                <input type="email"
                       class="form-control @error('candidate_email') is-invalid @enderror"
                       id="candidate_email"
                       name="candidate_email"
                       value="{{ old('candidate_email') }}"
                       placeholder="candidate@example.com">
                <div class="form-text">
                    If provided, the candidate will receive a status notification email when
                    they are rejected or the position is filled. Their email is never shared
                    with HR reviewers.
                </div>
                @error('candidate_email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-success">
                <i class="bi bi-upload"></i> Upload &amp; Process
            </button>
            <a href="{{ route('recruiter.jobs.show', $job->id) }}" class="btn btn-outline-secondary ms-2">
                Cancel
            </a>
        </form>
    </div>
</div>
@endsection
