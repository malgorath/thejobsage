@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Job Listings</h1>
    @auth
        @if(auth()->user()->isAdmin())
            <a href="{{ route('jobs.create') }}" class="btn btn-primary">Post New Job</a>
        @endif
    @endauth
</div>

<form method="GET" action="{{ route('jobs.index') }}" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control"
               placeholder="Search jobs..." value="{{ request('search') }}">
        <button class="btn btn-outline-secondary" type="submit">Search</button>
    </div>
</form>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($jobs->count() > 0)
    <div class="row">
        @foreach($jobs as $job)
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $job->title }}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            {{ $job->company }} &mdash; {{ $job->location }}
                        </h6>
                        @if($job->listingSkills->isNotEmpty())
                            <div class="mb-2">
                                @foreach($job->listingSkills->take(5) as $skill)
                                    <span class="badge bg-info text-dark me-1">{{ $skill->name }}</span>
                                @endforeach
                                @if($job->listingSkills->count() > 5)
                                    <span class="text-muted small">+{{ $job->listingSkills->count() - 5 }} more</span>
                                @endif
                            </div>
                        @endif
                        <p class="card-text">{{ Str::limit($job->description, 150) }}</p>
                        <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-primary">View Details</a>
                        @auth
                            @if(auth()->user()->isRecruiter())
                                <a href="{{ route('recruiter.upload.form', $job->id) }}"
                                   class="btn btn-sm btn-outline-success ms-1">Upload Candidates</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-center">
        {{ $jobs->links() }}
    </div>
@else
    <div class="alert alert-info">No job listings found.</div>
@endif
@endsection
