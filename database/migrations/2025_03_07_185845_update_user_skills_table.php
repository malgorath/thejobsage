<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_skills', function (Blueprint $table) {
            $table->dropColumn('skill'); // Remove the old skill column
            $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade'); // Reference skills table
        });
    }

    public function down()
    {
        Schema::table('user_skills', function (Blueprint $table) {
            $table->string('skill'); // Restore skill column if rollback
            $table->dropForeign(['skill_id']);
            $table->dropColumn('skill_id');
        });
    }
};
