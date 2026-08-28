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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 100)->nullable();
            $table->string('monogram', 10)->nullable();
            $table->string('motto', 150)->nullable();
            $table->string('type', 60)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('rating', 10)->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->json('forms')->nullable();
            $table->json('streams')->nullable();
            $table->json('programs')->nullable();
            $table->json('contact')->nullable();
            $table->string('window', 120)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
