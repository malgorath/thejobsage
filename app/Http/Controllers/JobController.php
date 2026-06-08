<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Services\JobSkillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles the public job-listings board and admin CRUD for job listings.
 *
 * Create / edit / update / destroy are admin-only (enforced via middleware).
 * The public index and show routes are accessible to everyone, including guests.
 * Listing skills are extracted from the description lazily on first show().
 */
class JobController extends Controller
{
    public function __construct(private JobSkillService $jobSkillService)
    {
        $this->middleware('admin')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a paginated, searchable list of job listings.
     *
     * @param  Request  $request  Supports ?search= query parameter.
     */
    public function index(Request $request): View
    {
        $query = Job::with('listingSkills');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $jobs = $query->orderByDesc('created_at')->paginate(15);

        return view('jobs.index', compact('jobs'));
    }

    /**
     * Show the form for creating a new job listing.
     */
    public function create(): View
    {
        $companies = Company::all();

        return view('jobs.create', compact('companies'));
    }

    /**
     * Store a new job listing and trigger skill extraction.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'requirements' => 'nullable|string',
        ]);

        if (! empty($validated['requirements'])) {
            $validated['requirements'] = array_values(array_filter(
                array_map('trim', explode("\n", $validated['requirements']))
            ));
        } else {
            $validated['requirements'] = null;
        }

        $job = Job::create($validated);
        $this->jobSkillService->extractAndAttach($job);

        return redirect()->route('jobs.index')->with('success', 'Job listing created successfully.');
    }

    /**
     * Display a single job listing and ensure listing skills are extracted.
     *
     * Skills are extracted on first view via JobSkillService — this is safe
     * to call repeatedly because extractAndAttach() is a no-op once skills exist.
     */
    public function show(Job $job): View
    {
        $job->load('listingSkills');
        $this->jobSkillService->extractAndAttach($job);
        $job->load('listingSkills');

        return view('jobs.show', compact('job'));
    }

    /**
     * Show the edit form for an existing job listing.
     */
    public function edit(int $id): View
    {
        $job = Job::findOrFail($id);
        $companies = Company::all();

        return view('jobs.edit', compact('job', 'companies'));
    }

    /**
     * Update an existing job listing.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $job = Job::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'requirements' => 'nullable|string',
        ]);

        if (! empty($validated['requirements'])) {
            $validated['requirements'] = array_values(array_filter(
                array_map('trim', explode("\n", $validated['requirements']))
            ));
        } else {
            $validated['requirements'] = null;
        }

        $job->update($validated);

        return redirect()->route('jobs.show', $job->id)->with('success', 'Job listing updated successfully.');
    }

    /**
     * Delete a job listing.
     */
    public function destroy(int $id): RedirectResponse
    {
        Job::findOrFail($id)->delete();

        return redirect()->route('jobs.index')->with('success', 'Job listing deleted successfully.');
    }
}
