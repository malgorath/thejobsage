@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('jobs.index') }}" class="btn btn-secondary">← Back to Jobs</a>
</div>

<div class="card">
    <div class="card-header">
        <h2>{{ $job->title }}</h2>
        <p class="mb-0 text-muted">{{ $job->company }} &mdash; {{ $job->location }}</p>
    </div>
    <div class="card-body">
        @if($job->listingSkills->isNotEmpty())
            <div class="mb-3">
                <h6 class="mb-2">Required Skills</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($job->listingSkills as $skill)
                        <span class="badge bg-info text-dark">{{ $skill->name }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <h5>Description</h5>
        <p>{{ $job->description }}</p>

        @if($job->requirements)
            <h5>Requirements</h5>
            <ul>
                @foreach($job->requirements as $req)
                    <li>{{ $req }}</li>
                @endforeach
            </ul>
        @endif

        {{-- Public apply button for portal candidates --}}
        @if($job->is_closed)
            <hr>
            <span class="badge bg-secondary fs-6 px-3 py-2">
                <i class="bi bi-briefcase me-1"></i> Position Filled
            </span>
        @else
            @guest
                <hr>
                <a href="{{ route('portal.apply', $job->id) }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-send"></i> Apply for This Position
                </a>
            @endguest
            @auth
                @if(!auth()->user()->isRecruiter() && !auth()->user()->isHr() && !auth()->user()->isAdmin())
                    <hr>
                    <a href="{{ route('portal.apply', $job->id) }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-send"></i> Apply for This Position
                    </a>
                @endif
            @endauth
        @endif

        @auth
            @if(auth()->user()->isRecruiter())
                <hr>
                <h5>Upload Candidates</h5>
                <p class="text-muted">Upload resumes for this position. All PII will be stripped before HR review.</p>
                @if(!$job->is_closed)
                    <a href="{{ route('recruiter.upload.form', $job->id) }}" class="btn btn-success">
                        <i class="bi bi-upload"></i> Upload Candidate Resumes
                    </a>
                @endif
                <a href="{{ route('recruiter.jobs.show', $job->id) }}" class="btn btn-outline-secondary ms-2">
                    View Candidates
                </a>
            @endif

            @if(auth()->user()->isAdmin())
                <hr>
                <h5>Admin Actions</h5>
                <a href="{{ route('jobs.edit', $job->id) }}" class="btn btn-warning">Edit Job</a>
                <form action="{{ route('jobs.destroy', $job->id) }}" method="POST"
                      class="d-inline"
                      onsubmit="return confirm('Delete this job listing?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Job</button>
                </form>
            @endif
        @endauth
    </div>
</div>
@endsection
