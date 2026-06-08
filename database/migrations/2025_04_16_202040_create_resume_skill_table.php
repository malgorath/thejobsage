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
        Schema::create('resume_skill', function (Blueprint $table) {
            // Define the foreign key for the Resume model
            $table->foreignId('resume_id')
                ->constrained('resumes') // Explicitly link to 'resumes' table
                ->onDelete('cascade');   // If a resume is deleted, remove related skills links

            // Define the foreign key for the Skill model
            $table->foreignId('skill_id')
                ->constrained('skills')   // Explicitly link to 'skills' table (assuming it's named 'skills')
                ->onDelete('cascade');   // If a skill is deleted, remove its links to resumes

            // Define a composite primary key to prevent duplicate resume-skill pairs
            $table->primary(['resume_id', 'skill_id']);

            // Optional: Add timestamps if you need to track when a skill was added to a resume
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resume_skill');
    }
};
