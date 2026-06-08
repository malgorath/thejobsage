<?php

namespace App\Policies;

use App\Models\Prompt;
use App\Models\User;

class PromptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Prompt $prompt): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Prompt $prompt): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Prompt $prompt): bool
    {
        return $user->isAdmin();
    }
}
