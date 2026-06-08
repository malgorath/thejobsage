<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make uploaded_by nullable to support portal self-submissions (no recruiter account)
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable()->change();
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Add communication and rejection fields
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('candidate_email')->nullable()->after('uploaded_by');
            $table->uuid('submission_token')->nullable()->unique()->after('candidate_email');
            $table->enum('rejection_stage', ['screening', 'interview'])->nullable()->after('status');
            $table->enum('rejection_reason', [
                'skill_gap',
                'experience_level',
                'culture_fit',
                'overqualified',
                'other',
            ])->nullable()->after('rejection_stage');
            $table->text('rejection_note')->nullable()->after('rejection_reason');
            $table->text('skill_gap_summary')->nullable()->after('rejection_note');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'candidate_email',
                'submission_token',
                'rejection_stage',
                'rejection_reason',
                'rejection_note',
                'skill_gap_summary',
            ]);
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable(false)->change();
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
