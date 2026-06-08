@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('hr.jobs.index') }}" class="btn btn-secondary btn-sm">← All Positions</a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="h4 mb-0">{{ $job->title }}</h2>
        <p class="text-muted mb-0">{{ $job->company }} &mdash; {{ $job->location }}</p>
    </div>
    @if($job->listingSkills->isNotEmpty())
        <div class="card-body pb-2">
            <h6 class="mb-2">Required Skills</h6>
            @foreach($job->listingSkills as $skill)
                <span class="badge bg-info text-dark me-1 mb-1">{{ $skill->name }}</span>
            @endforeach
        </div>
    @endif
</div>

<h4 class="mb-3">
    Candidates
    <small class="text-muted fs-6">ranked by skill match — no identifying information shown</small>
</h4>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($candidates->isEmpty())
    <div class="alert alert-info">No analyzed candidates for this position yet.</div>
@else
    @foreach($candidates as $i => $candidate)
        <div class="card mb-3 {{ $candidate->status === 'shortlisted' ? 'border-success' : ($candidate->status === 'rejected' ? 'border-danger opacity-75' : '') }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="text-muted fw-semibold">Candidate {{ $i + 1 }}</span>
                            @if(!is_null($candidate->match_score))
                                <span class="badge fs-6 bg-{{ $candidate->match_score >= 70 ? 'success' : ($candidate->match_score >= 40 ? 'warning text-dark' : 'secondary') }}">
                                    {{ $candidate->match_score }}% match
                                </span>
                            @endif
                            <span class="badge bg-{{ match($candidate->status) {
                                'shortlisted' => 'success',
                                'rejected'    => 'danger',
                                default       => 'light text-dark border',
                            } }}">{{ ucfirst($candidate->status) }}</span>
                        </div>

                        @if($candidate->resume && $candidate->resume->skills->isNotEmpty())
                            <div class="mb-2">
                                @foreach($candidate->resume->skills as $skill)
                                    <span class="badge bg-info text-dark me-1 mb-1">{{ $skill->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($candidate->anonymized_summary)
                            <p class="text-muted mb-2" style="font-size: 0.9rem;">
                                {{ Str::limit($candidate->anonymized_summary, 200) }}
                            </p>
                        @endif
                    </div>

                    <div class="ms-3 d-flex flex-column gap-2 align-items-end">
                        <a href="{{ route('hr.candidate.show', $candidate->id) }}"
                           class="btn btn-sm btn-outline-primary">Full Profile</a>

                        @if($candidate->status !== 'shortlisted')
                            <form action="{{ route('hr.candidate.status', $candidate->id) }}"
                                  method="POST" class="d-block mb-1">
                                @csrf @method('PATCH')
                                <button type="submit" name="status" value="shortlisted"
                                        class="btn btn-sm btn-success w-100">Shortlist</button>
                            </form>
                        @endif
                        @if($candidate->status !== 'rejected')
                            <a href="{{ route('hr.candidate.reject.form', $candidate->id) }}"
                               class="btn btn-sm btn-outline-danger d-block">Reject</a>
                        @else
                            <span class="badge bg-danger w-100 py-2">Rejected</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
