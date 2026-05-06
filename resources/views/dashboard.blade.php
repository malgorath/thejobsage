@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
    <span class="text-muted">Welcome back, {{ $user->name }}</span>
</div>

{{-- Stats row --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-file-earmark-person fs-2 text-primary mb-2"></i>
                <div class="display-6 fw-bold">{{ $resumes->count() }}</div>
                <div class="text-muted small">Resume{{ $resumes->count() !== 1 ? 's' : '' }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-briefcase fs-2 text-success mb-2"></i>
                <div class="display-6 fw-bold">{{ $applications->count() }}</div>
                <div class="text-muted small">Application{{ $applications->count() !== 1 ? 's' : '' }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-patch-check fs-2 text-info mb-2"></i>
                <div class="display-6 fw-bold">{{ $user->userSkills->count() }}</div>
                <div class="text-muted small">Skill{{ $user->userSkills->count() !== 1 ? 's' : '' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Resumes --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-file-earmark-person me-2"></i>My Resumes</h5>
                <a href="{{ route('resumes.upload.form') }}" class="btn btn-sm btn-primary">Upload</a>
            </div>
            <div class="card-body p-0">
                @if($resumes->count())
                    <ul class="list-group list-group-flush">
                        @foreach($resumes as $resume)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-earmark me-1 text-muted"></i>
                                    <a href="{{ route('resumes.show', $resume->id) }}" data-loading-overlay>
                                        {{ $resume->filename }}
                                    </a>
                                    @if($resume->is_primary)
                                        <span class="badge bg-primary ms-1">Primary</span>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $resume->ai_analysis ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $resume->ai_analysis ? 'Analyzed' : 'Pending' }}
                                    </span>
                                    <small class="text-muted">{{ $resume->created_at->format('d M Y') }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-file-earmark fs-1 d-block mb-2"></i>
                        No resumes uploaded yet.
                        <a href="{{ route('resumes.upload.form') }}" class="d-block mt-2">Upload your first resume</a>
                    </div>
                @endif
            </div>
            @if($resumes->count())
                <div class="card-footer text-end">
                    <a href="{{ route('resumes.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Applications --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>My Applications</h5>
                <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-success">Browse Jobs</a>
            </div>
            <div class="card-body p-0">
                @if($applications->count())
                    <ul class="list-group list-group-flush">
                        @foreach($applications->take(8) as $application)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('applications.show', $application->id) }}">
                                        {{ $application->job->title ?? 'Unknown Job' }}
                                    </a>
                                    <div class="text-muted small">{{ $application->job->company ?? '' }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-{{ $application->status === 'accepted' ? 'success' : ($application->status === 'rejected' ? 'danger' : ($application->status === 'reviewed' ? 'info' : 'warning')) }}">
                                        {{ ucfirst($application->status) }}
                                    </span>
                                    <small class="text-muted">{{ $application->created_at->format('d M Y') }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-briefcase fs-1 d-block mb-2"></i>
                        No applications yet.
                        <a href="{{ route('jobs.index') }}" class="d-block mt-2">Browse jobs to get started</a>
                    </div>
                @endif
            </div>
            @if($applications->count())
                <div class="card-footer text-end">
                    <a href="{{ route('applications.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Profile --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Profile</h5>
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8">{{ $user->name }}</dd>

                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8">{{ $user->email }}</dd>

                    @if($user->userDetail?->phone)
                        <dt class="col-sm-4 text-muted">Phone</dt>
                        <dd class="col-sm-8">{{ $user->userDetail->phone }}</dd>
                    @endif

                    @if($user->userDetail?->address)
                        <dt class="col-sm-4 text-muted">Address</dt>
                        <dd class="col-sm-8">{{ $user->userDetail->address }}</dd>
                    @endif

                    @if($user->userDetail?->linkedin)
                        <dt class="col-sm-4 text-muted">LinkedIn</dt>
                        <dd class="col-sm-8">
                            <a href="{{ $user->userDetail->linkedin }}" target="_blank" rel="noopener">
                                {{ $user->userDetail->linkedin }}
                            </a>
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Skills --}}
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-patch-check me-2"></i>My Skills</h5>
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-secondary">Manage</a>
            </div>
            <div class="card-body">
                @if($user->userSkills->count())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($user->userSkills as $userSkill)
                            <span class="badge bg-secondary fs-6 fw-normal">
                                {{ $userSkill->skill->name ?? 'Unknown' }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-patch-check fs-1 d-block mb-2"></i>
                        No skills added yet.
                        <a href="{{ route('profile.edit') }}" class="d-block mt-2">Add skills to your profile</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection
