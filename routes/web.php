<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CandidatePortalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HrController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecruiterController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ─── Public ──────────────────────────────────────────────────────────────────

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show')
    ->where('job', '[0-9]+');

// ─── Candidate Self-Submission Portal (public, no auth) ───────────────────────
Route::middleware('throttle:20,1')->group(function () {
    Route::get('/jobs/{job}/apply', [CandidatePortalController::class, 'form'])->name('portal.apply');
    Route::post('/jobs/{job}/apply', [CandidatePortalController::class, 'submit'])->name('portal.submit');
});
Route::get('/portal/submitted', [CandidatePortalController::class, 'submitted'])->name('portal.submitted');
Route::get('/submissions/{token}', [CandidatePortalController::class, 'status'])->name('portal.status');

// ─── Authenticated ────────────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {

    // Role-based dashboard redirect (required by Laravel Breeze's post-login redirect)
    Route::get('/dashboard', function () {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->isRecruiter()) {
            return redirect()->route('recruiter.jobs.index');
        }
        if ($user->isHr()) {
            return redirect()->route('hr.jobs.index');
        }

        return redirect()->route('jobs.index');
    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin-only job management
    Route::middleware('admin')->group(function () {
        Route::get('/jobs/create', [JobController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [JobController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [JobController::class, 'edit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [JobController::class, 'update'])->name('jobs.update');
        Route::delete('/jobs/{job}', [JobController::class, 'destroy'])->name('jobs.destroy');
    });

    // ─── Recruiter ────────────────────────────────────────────────────────────
    Route::middleware('recruiter')
        ->prefix('recruiter')
        ->name('recruiter.')
        ->group(function () {
            Route::get('/jobs', [RecruiterController::class, 'index'])
                ->name('jobs.index');
            Route::get('/jobs/{job}/upload', [RecruiterController::class, 'uploadForm'])
                ->name('upload.form');
            Route::post('/jobs/{job}/upload', [RecruiterController::class, 'upload'])
                ->name('upload');
            Route::get('/jobs/{job}/candidates', [RecruiterController::class, 'show'])
                ->name('jobs.show');
            Route::get('/candidates/{candidate}/download', [RecruiterController::class, 'download'])
                ->name('candidate.download');
            Route::patch('/candidates/{candidate}/status', [RecruiterController::class, 'updateStatus'])
                ->name('candidate.status');
            Route::patch('/jobs/{job}/close', [RecruiterController::class, 'closeJob'])
                ->name('jobs.close');
        });

    // ─── HR ───────────────────────────────────────────────────────────────────
    Route::middleware('hr')
        ->prefix('hr')
        ->name('hr.')
        ->group(function () {
            Route::get('/jobs', [HrController::class, 'index'])
                ->name('jobs.index');
            Route::get('/jobs/{job}', [HrController::class, 'show'])
                ->name('jobs.show');
            Route::get('/candidates/{candidate}', [HrController::class, 'candidate'])
                ->name('candidate.show');
            Route::patch('/candidates/{candidate}/status', [HrController::class, 'updateStatus'])
                ->name('candidate.status');
            Route::get('/candidates/{candidate}/reject', [HrController::class, 'rejectForm'])
                ->name('candidate.reject.form');
            Route::post('/candidates/{candidate}/reject', [HrController::class, 'reject'])
                ->name('candidate.reject');
        });

    // ─── Admin ────────────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users.index');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/jobs', [AdminController::class, 'jobs'])->name('jobs.index');
        Route::get('/candidates', [AdminController::class, 'candidates'])->name('candidates.index');

        Route::resource('companies', \App\Http\Controllers\CompanyController::class);
        Route::resource('skills', \App\Http\Controllers\SkillController::class);
        Route::resource('prompts', \App\Http\Controllers\PromptController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
