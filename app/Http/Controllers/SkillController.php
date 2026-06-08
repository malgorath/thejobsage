<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for the global skills dictionary.
 *
 * All actions require the `admin` middleware (enforced in constructor).
 * Skills are referenced by the resume_skill pivot and drive candidate scoring.
 */
class SkillController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Paginated list of all skills.
     */
    public function index(): View
    {
        $skills = Skill::latest()->paginate(20);

        return view('admin.skills.index', compact('skills'));
    }

    /**
     * Show the form for creating a new skill.
     */
    public function create(): View
    {
        return view('admin.skills.create');
    }

    /**
     * Store a newly created skill.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:skills,name',
        ]);

        Skill::create($validated);

        return redirect()->route('admin.skills.index')->with('success', 'Skill created successfully.');
    }

    /**
     * Show the edit form for an existing skill.
     */
    public function edit(Skill $skill): View
    {
        return view('admin.skills.edit', compact('skill'));
    }

    /**
     * Update an existing skill's name.
     */
    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:skills,name,'.$skill->id,
        ]);

        $skill->update($validated);

        return redirect()->route('admin.skills.index')->with('success', 'Skill updated successfully.');
    }

    /**
     * Delete a skill record.
     */
    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        return redirect()->route('admin.skills.index')->with('success', 'Skill deleted successfully.');
    }
}
