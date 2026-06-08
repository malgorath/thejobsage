<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for company records.
 *
 * Index and show are accessible to any authenticated user; create/store/edit/
 * update/destroy require the `admin` middleware (enforced in constructor).
 */
class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin')->except(['index', 'show']);
    }

    /**
     * Paginated list of all companies.
     */
    public function index(): View
    {
        $companies = Company::latest()->paginate(20);

        return view('admin.companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new company.
     */
    public function create(): View
    {
        return view('admin.companies.create');
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
        ]);

        Company::create($validated);

        return redirect()->route('admin.companies.index')->with('success', 'Company created successfully.');
    }

    /**
     * Display a single company.
     */
    public function show(Company $company): View
    {
        return view('admin.companies.show', compact('company'));
    }

    /**
     * Show the edit form for an existing company.
     */
    public function edit(Company $company): View
    {
        return view('admin.companies.edit', compact('company'));
    }

    /**
     * Update an existing company.
     */
    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
        ]);

        $company->update($validated);

        return redirect()->route('admin.companies.index')->with('success', 'Company updated successfully.');
    }

    /**
     * Delete a company record.
     */
    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->route('admin.companies.index')->with('success', 'Company deleted successfully.');
    }
}
