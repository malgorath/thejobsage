@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('hr.jobs.show', $candidate->job_id) }}" class="btn btn-secondary btn-sm">
        ← Back to Candidates
    </a>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h4 mb-0">Anonymous Candidate Profile</h2>
            <p class="text-muted mb-0 mt-1">
                Position: <strong>{{ $candidate->job->title ?? '—' }}</strong>
            </p>
        </div>
        <div class="text-end">
            @if(!is_null($candidate->match_score))
                <div class="display-6 fw-bold text-{{ $candidate->match_score >= 70 ? 'success' : ($candidate->match_score >= 40 ? 'warning' : 'secondary') }}">
                    {{ $candidate->match_score }}%
                </div>
                <small class="text-muted">Skill Match</small>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-secondary py-2 mb-3" role="note">
            <i class="bi bi-eye-slash"></i>
            All personal information has been removed from this profile. You are reviewing skills
            and experience only.
        </div>

        @if($candidate->resume && $candidate->resume->skills->isNotEmpty())
            <h5 class="mt-3">Skills</h5>
            <div class="mb-3">
                @foreach($candidate->resume->skills as $skill)
                    <span class="badge bg-info text-dark me-1 mb-1 fs-6">{{ $skill->name }}</span>
                @endforeach
            </div>
        @endif

        @if($candidate->anonymized_summary)
            <h5>Summary</h5>
            <p>{{ $candidate->anonymized_summary }}</p>
        @else
            <p class="text-muted">No summary available.</p>
        @endif

        <hr>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <form action="{{ route('hr.candidate.status', $candidate->id) }}" method="POST">
                @csrf @method('PATCH')
                @if($candidate->status !== 'shortlisted')
                    <button type="submit" name="status" value="shortlisted"
                            class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Shortlist
                    </button>
                @else
                    <span class="badge bg-success fs-6 p-2"><i class="bi bi-check-circle"></i> Shortlisted</span>
                @endif
            </form>

            @if($candidate->status !== 'rejected')
                <a href="{{ route('hr.candidate.reject.form', $candidate->id) }}"
                   class="btn btn-outline-danger">
                    <i class="bi bi-x-circle"></i> Reject
                </a>
            @else
                <span class="badge bg-danger fs-6 p-2"><i class="bi bi-x-circle"></i> Rejected</span>
                @if($candidate->rejection_note || $candidate->skill_gap_summary)
                    <a href="{{ route('hr.candidate.reject.form', $candidate->id) }}"
                       class="btn btn-sm btn-outline-secondary">View Rejection Details</a>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
