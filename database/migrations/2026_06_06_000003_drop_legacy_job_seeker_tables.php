<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ai_suggestions references users and resumes — drop first
        Schema::dropIfExists('ai_suggestions');

        // user_skills references users and skills
        Schema::dropIfExists('user_skills');

        // user_details references users
        Schema::dropIfExists('user_details');
    }

    public function down(): void
    {
        // Restoration is intentionally not implemented.
        // These tables belong to the retired job-seeker model.
    }
};
