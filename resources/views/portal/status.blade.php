@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="mb-4">
                <h1 class="h3 mb-1">Application Status</h1>
                <p class="text-muted mb-0">
                    {{ $candidate->job->title ?? 'Position' }}
                    @if($candidate->job)
                        &mdash; {{ $candidate->job->company }}
                    @endif
                </p>
            </div>

            {{-- Status banner --}}
            @php
                $job = $candidate->job;
                $isFilled = $job && $job->is_closed && $candidate->status !== 'rejected';
            @endphp

            @if($candidate->status === 'pending_analysis')
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <i class="bi bi-hourglass-split fs-5"></i>
                    <div>
                        <strong>Processing your application</strong>
                        <div class="small">Your resume is being anonymized and analyzed. This usually takes a moment.</div>
                    </div>
                </div>

            @elseif($candidate->status === 'analyzed')
                <div class="alert alert-info d-flex align-items-center gap-2">
                    <i class="bi bi-search fs-5"></i>
                    <div>
                        <strong>Under review</strong>
                        <div class="small">Your application has been processed and is currently being reviewed.</div>
                    </div>
                </div>

            @elseif($candidate->status === 'shortlisted')
                <div class="alert alert-success d-flex align-items-center gap-2">
                    <i class="bi bi-star-fill fs-5"></i>
                    <div>
                        <strong>Congratulations — you've been shortlisted</strong>
                        <div class="small">The hiring team will be in touch regarding next steps.</div>
                    </div>
                </div>

            @elseif($candidate->status === 'rejected')
                <div class="alert alert-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-x-circle fs-5"></i>
                    <div>
                        @if($candidate->rejection_stage === 'interview')
                            <strong>We've decided to move forward with other candidates</strong>
                            <div class="small">Thank you for the time you invested in the interview process.</div>
                        @else
                            <strong>Not selected for this position</strong>
                            <div class="small">Thank you for your interest. We encourage you to apply for future openings.</div>
                        @endif
                    </div>
                </div>

            @elseif($isFilled)
                <div class="alert alert-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-briefcase fs-5"></i>
                    <div>
                        <strong>This position has been filled</strong>
                        <div class="small">We appreciate your interest and encourage you to apply for future openings.</div>
                    </div>
                </div>
            @endif

            {{-- Rejection details --}}
            @if($candidate->status === 'rejected' && ($candidate->skill_gap_summary || $candidate->rejection_note))
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Feedback</h5>
                    </div>
                    <div class="card-body">
                        @if($candidate->skill_gap_summary)
                            <h6 class="text-muted">Skill Gap Analysis</h6>
                            <p>{{ $candidate->skill_gap_summary }}</p>
                        @endif
                        @if($candidate->rejection_note)
                            <h6 class="text-muted">Reviewer Notes</h6>
                            <p class="mb-0">{{ $candidate->rejection_note }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Anonymized profile --}}
            @if($candidate->status !== 'pending_analysis')
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Anonymized Profile</h5>
                        @if(!is_null($candidate->match_score))
                            <span class="badge fs-6 bg-{{ $candidate->match_score >= 70 ? 'success' : ($candidate->match_score >= 40 ? 'warning text-dark' : 'secondary') }}">
                                {{ $candidate->match_score }}% skill match
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light py-2 mb-3 small">
                            <i class="bi bi-eye-slash me-1"></i>
                            This is exactly what reviewers see — no names, emails, or employer names.
                        </div>

                        @if($candidate->resume && $candidate->resume->skills->isNotEmpty())
                            <h6 class="text-muted mb-2">Skills</h6>
                            <div class="mb-3">
                                @foreach($candidate->resume->skills as $skill)
                                    <span class="badge bg-info text-dark me-1 mb-1">{{ $skill->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($candidate->anonymized_summary)
                            <h6 class="text-muted mb-2">Summary</h6>
                            <p class="mb-0">{{ $candidate->anonymized_summary }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="text-center">
                <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary">
                    Browse More Positions
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
