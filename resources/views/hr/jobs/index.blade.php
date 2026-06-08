@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">HR Review — Open Positions</h1>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($jobs->isEmpty())
    <div class="alert alert-info">No positions with analyzed candidates yet.</div>
@else
    <div class="row">
        @foreach($jobs as $job)
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $job->title }}</h5>
                        <p class="card-subtitle text-muted mb-2">{{ $job->company }} &mdash; {{ $job->location }}</p>

                        @if($job->listingSkills->isNotEmpty())
                            <div class="mb-3">
                                @foreach($job->listingSkills->take(5) as $skill)
                                    <span class="badge bg-info text-dark me-1">{{ $skill->name }}</span>
                                @endforeach
                                @if($job->listingSkills->count() > 5)
                                    <span class="text-muted small">+{{ $job->listingSkills->count() - 5 }} more</span>
                                @endif
                            </div>
                        @endif

                        <div class="d-flex gap-3 mb-3">
                            <div class="text-center">
                                <div class="fw-bold text-info">{{ $job->analyzed_count }}</div>
                                <small class="text-muted">To Review</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold text-success">{{ $job->shortlisted_count }}</div>
                                <small class="text-muted">Shortlisted</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold text-danger">{{ $job->rejected_count }}</div>
                                <small class="text-muted">Rejected</small>
                            </div>
                        </div>

                        <a href="{{ route('hr.jobs.show', $job->id) }}" class="btn btn-primary btn-sm">
                            Review Candidates
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-center mt-2">
        {{ $jobs->links() }}
    </div>
@endif
@endsection
