@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('recruiter.jobs.index') }}" class="btn btn-secondary btn-sm mb-2">← Back</a>
        <h1 class="h3 mb-0">
            {{ $job->title }}
            @if($job->is_closed)
                <span class="badge bg-secondary fs-6 ms-2">Position Filled</span>
            @endif
        </h1>
        <p class="text-muted mb-0">{{ $job->company }} &mdash; {{ $job->location }}</p>
    </div>
    <div class="d-flex gap-2">
        @if(!$job->is_closed)
            <a href="{{ route('recruiter.upload.form', $job->id) }}" class="btn btn-success">
                <i class="bi bi-upload"></i> Upload Resume
            </a>
            <button type="button" class="btn btn-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#closeJobModal">
                <i class="bi bi-check2-all"></i> Mark Filled
            </button>
        @endif
    </div>
</div>

{{-- Mark position filled modal --}}
@if(!$job->is_closed)
<div class="modal fade" id="closeJobModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Position as Filled</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This will mark <strong>{{ $job->title }}</strong> as filled and stop accepting new applications.</p>
                <p class="mb-0 text-muted small">
                    Any active candidates who applied via the portal will receive an email notification.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('recruiter.jobs.close', $job->id) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($candidates->isEmpty())
    <div class="alert alert-info">
        No candidates uploaded for this position yet.
        <a href="{{ route('recruiter.upload.form', $job->id) }}">Upload resumes</a> to begin screening.
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Match Score</th>
                        <th>Skills</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($candidates as $i => $candidate)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                @if(!is_null($candidate->match_score))
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px; min-width: 80px;">
                                            <div class="progress-bar bg-{{ $candidate->match_score >= 70 ? 'success' : ($candidate->match_score >= 40 ? 'warning' : 'secondary') }}"
                                                 style="width: {{ $candidate->match_score }}%"></div>
                                        </div>
                                        <span class="fw-semibold">{{ $candidate->match_score }}%</span>
                                    </div>
                                @elseif($candidate->status === 'pending_analysis')
                                    <span class="text-muted"><i class="bi bi-hourglass-split"></i> Pending</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($candidate->resume && $candidate->resume->skills->isNotEmpty())
                                    @foreach($candidate->resume->skills->take(4) as $skill)
                                        <span class="badge bg-info text-dark me-1">{{ $skill->name }}</span>
                                    @endforeach
                                    @if($candidate->resume->skills->count() > 4)
                                        <span class="text-muted small">+{{ $candidate->resume->skills->count() - 4 }}</span>
                                    @endif
                                @else
                                    <span class="text-muted small">No skills extracted</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ match($candidate->status) {
                                    'shortlisted'     => 'success',
                                    'rejected'        => 'danger',
                                    'analyzed'        => 'info',
                                    default           => 'warning',
                                } }}">{{ ucfirst(str_replace('_', ' ', $candidate->status)) }}</span>
                            </td>
                            <td>
                                {{-- Recruiter can download the raw resume --}}
                                @if($candidate->resume)
                                    <a href="{{ route('recruiter.candidate.download', $candidate->id) }}"
                                       class="btn btn-sm btn-outline-secondary me-1"
                                       title="Download original resume">
                                        <i class="bi bi-download"></i>
                                    </a>
                                @endif

                                {{-- Quick status toggle --}}
                                @if($candidate->status === 'analyzed' || $candidate->status === 'shortlisted' || $candidate->status === 'rejected')
                                    <form action="{{ route('recruiter.candidate.status', $candidate->id) }}"
                                          method="POST" class="d-inline">
                                        @csrf @method('PATCH')
                                        @if($candidate->status !== 'shortlisted')
                                            <button type="submit" name="status" value="shortlisted"
                                                    class="btn btn-sm btn-success me-1">Shortlist</button>
                                        @endif
                                        @if($candidate->status !== 'rejected')
                                            <button type="submit" name="status" value="rejected"
                                                    class="btn btn-sm btn-outline-danger">Reject</button>
                                        @endif
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
