@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('hr.candidate.show', $candidate->id) }}" class="btn btn-secondary btn-sm">
        ← Back to Candidate Profile
    </a>
</div>

<div class="row g-4">

    {{-- Left: Candidate context --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Candidate Profile</h5>
                <small class="text-muted">{{ $candidate->job->title ?? '—' }}</small>
            </div>
            <div class="card-body">
                @if(!is_null($candidate->match_score))
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="progress flex-grow-1" style="height: 10px;">
                            <div class="progress-bar bg-{{ $candidate->match_score >= 70 ? 'success' : ($candidate->match_score >= 40 ? 'warning' : 'secondary') }}"
                                 style="width: {{ $candidate->match_score }}%"></div>
                        </div>
                        <span class="fw-bold">{{ $candidate->match_score }}%</span>
                    </div>
                @endif

                @if($candidate->resume && $candidate->resume->skills->isNotEmpty())
                    <h6 class="text-muted small mb-2">CANDIDATE SKILLS</h6>
                    <div class="mb-3">
                        @foreach($candidate->resume->skills as $skill)
                            <span class="badge bg-info text-dark me-1 mb-1">{{ $skill->name }}</span>
                        @endforeach
                    </div>
                @endif

                @if($candidate->anonymized_summary)
                    <h6 class="text-muted small mb-2">SUMMARY</h6>
                    <p class="small mb-0">{{ $candidate->anonymized_summary }}</p>
                @endif
            </div>
        </div>

        @if(!empty($missingSkills))
            <div class="card border-warning border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 text-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Skill Gaps ({{ count($missingSkills) }})
                    </h6>
                    <small class="text-muted">Skills required but not found in candidate profile</small>
                </div>
                <div class="card-body">
                    @foreach($missingSkills as $skill)
                        <span class="badge bg-warning text-dark me-1 mb-1">{{ $skill }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Right: Rejection form --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h4 class="mb-0 text-danger">
                    <i class="bi bi-x-circle me-1"></i> Reject Candidate
                </h4>
                <small class="text-muted">
                    All fields are required. The feedback note will have personal identifiers automatically removed before being sent.
                </small>
            </div>
            <div class="card-body p-4">

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('hr.candidate.reject', $candidate->id) }}"
                      id="rejection-form">
                    @csrf

                    <div class="mb-4">
                        <label for="rejection_stage" class="form-label fw-semibold">
                            Stage of Rejection <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('rejection_stage') is-invalid @enderror"
                                id="rejection_stage" name="rejection_stage" required>
                            <option value="">— Select stage —</option>
                            <option value="screening" {{ old('rejection_stage') === 'screening' ? 'selected' : '' }}>
                                After initial screening review
                            </option>
                            <option value="interview" {{ old('rejection_stage') === 'interview' ? 'selected' : '' }}>
                                After interview process
                            </option>
                        </select>
                        @error('rejection_stage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="rejection_reason" class="form-label fw-semibold">
                            Primary Reason <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('rejection_reason') is-invalid @enderror"
                                id="rejection_reason" name="rejection_reason" required>
                            <option value="">— Select reason —</option>
                            @foreach($rejectionReasons as $value => $label)
                                <option value="{{ $value }}" {{ old('rejection_reason') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('rejection_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="rejection_note" class="form-label fw-semibold">
                            Feedback Note <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('rejection_note') is-invalid @enderror"
                                  id="rejection_note" name="rejection_note"
                                  rows="5" minlength="20" maxlength="1000"
                                  placeholder="Be specific and objective about what was missing or didn't match the role requirements."
                                  required>{{ old('rejection_note') }}</textarea>
                        <div class="form-text">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            Names, email addresses, company names, and other personal identifiers will be
                            automatically removed before this note is sent to the candidate.
                            Minimum 20 characters.
                        </div>
                        @error('rejection_note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($candidate->candidate_email)
                        <div class="alert alert-info small mb-4">
                            <i class="bi bi-envelope me-1"></i>
                            A notification (with anonymized skill gap analysis and your note) will be sent to the candidate.
                        </div>
                    @else
                        <div class="alert alert-secondary small mb-4">
                            <i class="bi bi-envelope-slash me-1"></i>
                            No email address on file for this candidate — no notification will be sent.
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger"
                                data-bs-toggle="modal" data-bs-target="#confirmModal">
                            <i class="bi bi-x-circle me-1"></i> Confirm Rejection
                        </button>
                        <a href="{{ route('hr.candidate.show', $candidate->id) }}"
                           class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-danger">Confirm Rejection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This action will reject the candidate and cannot be undone.</p>
                @if($candidate->candidate_email)
                    <p class="mb-0">The candidate will receive an automated email with the skill gap analysis and your feedback note (after PII removal).</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Go Back</button>
                <button type="button" class="btn btn-danger"
                        onclick="document.getElementById('rejection-form').submit();">
                    Yes, Reject Candidate
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
