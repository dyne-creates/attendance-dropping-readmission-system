<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('full_name', 150)->nullable()->change();
            $table->string('relationship', 50)->nullable()->change();
            $table->string('phone_number', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('guardians')
            ->whereNull('full_name')
            ->orWhereNull('relationship')
            ->orWhereNull('phone_number')
            ->exists()) {
            throw new RuntimeException('Email-only guardian records must be completed before this migration can be rolled back.');
        }

        Schema::table('guardians', function (Blueprint $table) {
            $table->string('full_name', 150)->nullable(false)->change();
            $table->string('relationship', 50)->nullable(false)->change();
            $table->string('phone_number', 20)->nullable(false)->change();
        });
    }
};
