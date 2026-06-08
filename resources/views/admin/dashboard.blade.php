@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Admin Dashboard</h1>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Users</h5>
                <h2 class="mb-0">{{ $stats['total_users'] }}</h2>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary mt-2">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Jobs</h5>
                <h2 class="mb-0">{{ $stats['total_jobs'] }}</h2>
                <a href="{{ route('admin.jobs.index') }}" class="btn btn-sm btn-outline-primary mt-2">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Candidates</h5>
                <h2 class="mb-0">{{ $stats['total_candidates'] }}</h2>
                <a href="{{ route('admin.candidates.index') }}" class="btn btn-sm btn-outline-primary mt-2">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Pending Analysis</h5>
                <h2 class="mb-0 {{ $stats['pending_analysis'] > 0 ? 'text-warning' : '' }}">
                    {{ $stats['pending_analysis'] }}
                </h2>
                <small class="text-muted">{{ $stats['shortlisted'] }} shortlisted</small>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Recent Users</h5></div>
            <div class="card-body">
                @forelse($stats['recent_users'] as $user)
                    <div class="mb-2">
                        <strong>{{ $user->name }}</strong><br>
                        <small class="text-muted">{{ $user->email }}</small>
                        <span class="badge bg-secondary ms-1">{{ $user->role }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No users yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Recent Jobs</h5></div>
            <div class="card-body">
                @forelse($stats['recent_jobs'] as $job)
                    <div class="mb-2">
                        <strong>{{ $job->title }}</strong><br>
                        <small class="text-muted">{{ $job->company }} — {{ $job->location }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0">No jobs yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Recent Candidates</h5></div>
            <div class="card-body">
                @forelse($stats['recent_candidates'] as $candidate)
                    <div class="mb-2">
                        <strong>{{ $candidate->job->title ?? 'Unknown Job' }}</strong><br>
                        <small class="text-muted">Uploaded by {{ $candidate->uploader->name ?? '—' }}</small>
                        <span class="badge ms-1 bg-{{ match($candidate->status) {
                            'shortlisted'     => 'success',
                            'rejected'        => 'danger',
                            'analyzed'        => 'info',
                            default           => 'warning',
                        } }}">{{ ucfirst(str_replace('_', ' ', $candidate->status)) }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No candidates yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Quick Actions</h5></div>
            <div class="card-body">
                <a href="{{ route('jobs.create') }}" class="btn btn-primary me-2">Post New Job</a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary me-2">Manage Users</a>
                <a href="{{ route('admin.jobs.index') }}" class="btn btn-secondary me-2">Manage Jobs</a>
                <a href="{{ route('admin.candidates.index') }}" class="btn btn-secondary me-2">All Candidates</a>
                <a href="{{ route('admin.prompts.index') }}" class="btn btn-outline-info">Manage AI Prompts</a>
            </div>
        </div>
    </div>
</div>
@endsection
