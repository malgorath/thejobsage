<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')
                ->nullable()
                ->constrained('jobListings')
                ->onDelete('set null');
            $table->foreignId('resume_id')
                ->nullable()
                ->constrained('resumes')
                ->onDelete('set null');
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->onDelete('cascade');
            $table->text('anonymized_summary')->nullable();
            $table->tinyInteger('match_score')->unsigned()->nullable();
            $table->enum('status', ['pending_analysis', 'analyzed', 'shortlisted', 'rejected'])
                ->default('pending_analysis');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
