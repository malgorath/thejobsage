@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('recruiter.jobs.show', $job->id) }}" class="btn btn-secondary btn-sm">← Back to Candidates</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h4 mb-0">Review Extracted Data</h2>
            <p class="mb-0 text-muted mt-1">
                <strong>{{ $job->title }}</strong> &mdash; {{ $job->company }}
            </p>
        </div>
        <span class="badge bg-warning text-dark fs-6">Not yet saved</span>
    </div>

    <div class="card-body">
        <div class="alert alert-info mb-4">
            <i class="bi bi-shield-check me-2"></i>
            Review the anonymized output below. If it looks correct, confirm to save the candidate record.
            Confirming stores only this anonymized data — the original resume file is never saved.
            Cancelling discards everything.
        </div>

        {{-- File info --}}
        <div class="mb-4">
            <h6 class="fw-semibold text-muted text-uppercase small mb-2">File</h6>
            <p class="mb-0">
                <i class="bi bi-file-earmark-text me-1"></i>
                {{ $staged['filename'] }}
                @if($staged['candidate_email'])
                    &mdash;
                    <span class="text-muted">{{ $staged['candidate_email'] }}</span>
                @endif
            </p>
        </div>

        {{-- Match score --}}
        <div class="mb-4">
            <h6 class="fw-semibold text-muted text-uppercase small mb-2">Match Score</h6>
            @if(!is_null($staged['result']['match_score']))
                <div class="d-flex align-items-center gap-3">
                    <div class="progress flex-grow-1" style="height: 12px; max-width: 300px;">
                        <div class="progress-bar bg-{{ $staged['result']['match_score'] >= 70 ? 'success' : ($staged['result']['match_score'] >= 40 ? 'warning' : 'secondary') }}"
                             style="width: {{ $staged['result']['match_score'] }}%"></div>
                    </div>
                    <span class="fs-5 fw-semibold">{{ $staged['result']['match_score'] }}%</span>
                </div>
            @else
                <span class="text-muted">Not calculated (no job skills defined yet)</span>
            @endif
        </div>

        {{-- Extracted skills --}}
        <div class="mb-4">
            <h6 class="fw-semibold text-muted text-uppercase small mb-2">Extracted Skills</h6>
            @if(!empty($staged['result']['skills']))
                <div>
                    @foreach($staged['result']['skills'] as $skill)
                        <span class="badge bg-info text-dark me-1 mb-1">{{ $skill }}</span>
                    @endforeach
                </div>
            @else
                <span class="text-muted">No skills extracted</span>
            @endif
        </div>

        {{-- Anonymized summary --}}
        <div class="mb-4">
            <h6 class="fw-semibold text-muted text-uppercase small mb-2">Anonymized Summary</h6>
            @if(!empty($staged['result']['anonymized_summary']))
                <p class="mb-0">{{ $staged['result']['anonymized_summary'] }}</p>
            @else
                <span class="text-muted">No summary generated</span>
            @endif
        </div>

        <hr>

        <div class="d-flex gap-3 mt-4">
            <form method="POST" action="{{ route('recruiter.upload.confirm', $job->id) }}">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i> Confirm &amp; Save
                </button>
            </form>

            <form method="POST" action="{{ route('recruiter.upload.discard', $job->id) }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-x-lg me-1"></i> Discard
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
