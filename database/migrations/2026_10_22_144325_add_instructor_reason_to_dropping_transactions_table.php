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
        Schema::table('dropping_transactions', function (Blueprint $table) {
            $table->text('instructor_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dropping_transactions', function (Blueprint $table) {
            $table->dropColumn('instructor_reason');
        });
    }
};
