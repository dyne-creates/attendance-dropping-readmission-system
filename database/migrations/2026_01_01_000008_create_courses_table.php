<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id('course_id');

            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('school_year_id');

            $table->string('subject_code', 20);
            $table->string('course_name', 150);
            $table->string('section', 20);

            $table->unsignedInteger('hours_per_week');
            $table->decimal('total_semester_hours', 6, 2);

            $table->timestamps();

            $table->foreign('faculty_id')
                ->references('faculty_id')
                ->on('faculty_members');

            $table->foreign('school_year_id')
                ->references('school_year_id')
                ->on('school_years');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};