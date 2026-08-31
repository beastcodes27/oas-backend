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
        Schema::table('schools', function (Blueprint $table) {
            $table->string('joining_instruction_url', 500)->nullable()->after('home_features_subtitle');
            $table->string('joining_instruction_name', 200)->nullable()->after('joining_instruction_url');
            $table->text('joining_instruction_note')->nullable()->after('joining_instruction_name');
            $table->timestamp('joining_instruction_published_at')->nullable()->after('joining_instruction_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'joining_instruction_url',
                'joining_instruction_name',
                'joining_instruction_note',
                'joining_instruction_published_at',
            ]);
        });
    }
};
