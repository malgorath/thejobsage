@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Screening Overview</h1>
    <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary">Browse Job Listings</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Pending</th>
                    <th class="text-center">Analyzed</th>
                    <th class="text-center">Shortlisted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td>
                            <strong>{{ $job->title }}</strong>
                            @if($job->is_closed)
                                <span class="badge bg-secondary ms-1">Filled</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $job->company }}</td>
                        <td class="text-center">{{ $job->candidates_count }}</td>
                        <td class="text-center">
                            @if($job->pending_count > 0)
                                <span class="badge bg-warning text-dark">{{ $job->pending_count }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $job->analyzed_count }}</td>
                        <td class="text-center">
                            @if($job->shortlisted_count > 0)
                                <span class="badge bg-success">{{ $job->shortlisted_count }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('recruiter.jobs.show', $job->id) }}"
                               class="btn btn-sm btn-outline-primary me-1">View</a>
                            @if(!$job->is_closed)
                                <a href="{{ route('recruiter.upload.form', $job->id) }}"
                                   class="btn btn-sm btn-success">Upload</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No job listings found. <a href="{{ route('jobs.index') }}">Browse jobs</a> to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-3">
    {{ $jobs->links() }}
</div>
@endsection
