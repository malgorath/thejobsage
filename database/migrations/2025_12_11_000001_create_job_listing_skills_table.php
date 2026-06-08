<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_listing_skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('job_job_listing_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')
                ->constrained('jobListings')
                ->onDelete('cascade');
            $table->foreignId('job_listing_skill_id')->constrained('job_listing_skills')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['job_id', 'job_listing_skill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_job_listing_skill');
        Schema::dropIfExists('job_listing_skills');
    }
};
