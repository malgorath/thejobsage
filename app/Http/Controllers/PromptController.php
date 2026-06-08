<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for Ollama prompt templates.
 *
 * Prompts are keyed records (e.g. `pii_strip`, `candidate_summary`) that the
 * OllamaService resolves at runtime. Editing a prompt in the UI changes the
 * model's behaviour without a code deploy.
 *
 * All actions are gated by the `Prompt` policy via `$this->authorize()`.
 */
class PromptController extends Controller
{
    /**
     * List all prompts, ordered alphabetically by title.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Prompt::class);

        $prompts = Prompt::orderBy('title')->paginate(15);

        return view('admin.prompts.index', compact('prompts'));
    }

    /**
     * Show the form for creating a new prompt.
     */
    public function create(): View
    {
        $this->authorize('create', Prompt::class);

        return view('admin.prompts.create');
    }

    /**
     * Store a new prompt record.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Prompt::class);

        $data = $this->validatedData($request);

        Prompt::create($data);

        return redirect()->route('admin.prompts.index')->with('success', 'Prompt created.');
    }

    /**
     * Show the edit form for an existing prompt.
     */
    public function edit(Prompt $prompt): View
    {
        $this->authorize('update', $prompt);

        return view('admin.prompts.edit', compact('prompt'));
    }

    /**
     * Update an existing prompt.
     */
    public function update(Request $request, Prompt $prompt): RedirectResponse
    {
        $this->authorize('update', $prompt);

        $data = $this->validatedData($request, $prompt->id);

        $prompt->update($data);

        return redirect()->route('admin.prompts.index')->with('success', 'Prompt updated.');
    }

    /**
     * Delete a prompt record.
     */
    public function destroy(Prompt $prompt): RedirectResponse
    {
        $this->authorize('delete', $prompt);

        $prompt->delete();

        return redirect()->route('admin.prompts.index')->with('success', 'Prompt deleted.');
    }

    /**
     * Validate and reshape prompt form data, extracting numeric Ollama config
     * fields into a nested `config` array for JSON storage.
     *
     * @param  int|null  $ignoreId  The ID of the prompt being updated (for unique key validation).
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:prompts,key';
        if ($ignoreId) {
            $uniqueRule .= ','.$ignoreId;
        }

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:191', $uniqueRule],
            'title' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string'],
            'temperature' => ['nullable', 'numeric'],
            'top_p' => ['nullable', 'numeric'],
            'top_k' => ['nullable', 'numeric'],
            'repeat_penalty' => ['nullable', 'numeric'],
            'num_ctx' => ['nullable', 'integer'],
            'seed' => ['nullable', 'integer'],
            'max_tokens' => ['nullable', 'integer'],
        ]);

        $config = [];
        foreach (['temperature', 'top_p', 'top_k', 'repeat_penalty'] as $field) {
            if ($request->filled($field)) {
                $config[$field] = (float) $request->input($field);
            }
        }

        foreach (['num_ctx', 'seed', 'max_tokens'] as $field) {
            if ($request->filled($field)) {
                $config[$field] = (int) $request->input($field);
            }
        }

        $validated['config'] = $config;

        return $validated;
    }
}
