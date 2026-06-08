<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable(); // In case they provide a different contact email
            $table->string('linkedin')->nullable();
            $table->string('website')->nullable();
            $table->string('github')->nullable();
            $table->text('other_contact')->nullable(); // For other contact methods
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
