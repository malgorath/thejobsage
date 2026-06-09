<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw resume files are never persisted — file_data is now optional.
        Schema::table('resumes', function (Blueprint $table) {
            $table->binary('file_data')->nullable()->change();
        });

        // Store the PII-stripped text so re-evaluate can regenerate summaries
        // without needing the original file (which is discarded at ingest).
        Schema::table('candidates', function (Blueprint $table) {
            $table->text('anonymized_text')->nullable()->after('anonymized_summary');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('anonymized_text');
        });

        Schema::table('resumes', function (Blueprint $table) {
            $table->binary('file_data')->nullable(false)->change();
        });
    }
};
