<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id('school_year_id');
            $table->string('year_label', 20);
            $table->string('semester', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_archived')->default(false);
            $table->date('archived_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_years');
    }
};