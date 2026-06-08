@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">

            <div class="mb-4">
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                     style="width: 80px; height: 80px;">
                    <i class="bi bi-check-circle-fill text-success fs-1"></i>
                </div>
            </div>

            <h1 class="h3 mb-3">Application Received</h1>

            <p class="text-muted lead mb-4">
                We've received your application and it's currently being processed.
                You'll receive a confirmation email shortly with your anonymized profile
                and a link to track your application status.
            </p>

            <div class="card border-0 bg-light mb-4">
                <div class="card-body py-3">
                    <p class="mb-0 small text-muted">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        Your personal information has been removed from the copy reviewers will see.
                        You will be evaluated solely on skills and experience.
                    </p>
                </div>
            </div>

            <a href="{{ route('jobs.index') }}" class="btn btn-outline-primary">
                Browse More Positions
            </a>

        </div>
    </div>
</div>
@endsection
