<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only dashboard and user/job/candidate management.
 *
 * All routes using this controller are protected by the `admin` middleware.
 */
class AdminController extends Controller
{
    /**
     * Render the admin overview dashboard with platform-wide statistics.
     */
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_jobs' => Job::count(),
            'total_resumes' => Resume::count(),
            'total_candidates' => Candidate::count(),
            'pending_analysis' => Candidate::where('status', 'pending_analysis')->count(),
            'shortlisted' => Candidate::where('status', 'shortlisted')->count(),
            'recent_users' => User::latest()->take(5)->get(),
            'recent_jobs' => Job::latest()->take(5)->get(),
            'recent_candidates' => Candidate::with(['job', 'uploader'])
                ->latest()
                ->take(5)
                ->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Paginated list of all users with their uploaded candidate counts.
     */
    public function users(): View
    {
        $users = User::withCount('uploadedCandidates')->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the edit form for a specific user.
     */
    public function editUser(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update a user's name, email, and role.
     *
     * Roles are restricted to `recruiter`, `hr`, and `admin`.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:recruiter,hr,admin',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user account. Admins cannot delete their own account.
     */
    public function destroyUser(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Paginated list of all job listings with candidate counts.
     */
    public function jobs(): View
    {
        $jobs = Job::withCount('candidates')->latest()->paginate(20);

        return view('admin.jobs.index', compact('jobs'));
    }

    /**
     * Paginated list of all candidates across all jobs with their
     * associated job and uploader eager-loaded.
     */
    public function candidates(): View
    {
        $candidates = Candidate::with(['job', 'uploader'])
            ->latest()
            ->paginate(30);

        return view('admin.candidates.index', compact('candidates'));
    }
}
