<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'name')) {
                $table->string('name')->after('id');
            }
            if (! Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('name');
            }
            if (! Schema::hasColumn('companies', 'description')) {
                $table->text('description')->nullable()->after('website');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['name', 'website', 'description']);
        });
    }
};
