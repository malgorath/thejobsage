@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">All Candidates</h1>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Job</th>
                    <th>Uploaded By</th>
                    <th>Match Score</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidates as $candidate)
                    <tr>
                        <td class="text-muted">{{ $candidate->id }}</td>
                        <td>{{ $candidate->job->title ?? '—' }}</td>
                        <td>{{ $candidate->uploader->name ?? '—' }}</td>
                        <td>
                            @if(!is_null($candidate->match_score))
                                <span class="badge bg-{{ $candidate->match_score >= 70 ? 'success' : ($candidate->match_score >= 40 ? 'warning' : 'secondary') }}">
                                    {{ $candidate->match_score }}%
                                </span>
                            @else
                                <span class="text-muted">—</span>
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
                        <td><small class="text-muted">{{ $candidate->created_at->diffForHumans() }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No candidates uploaded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-3">
    {{ $candidates->links() }}
</div>
@endsection
