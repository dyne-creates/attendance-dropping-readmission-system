<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id('enrollment_id');

            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');

            $table->enum('status', [
                'enrolled',
                'dropped',
                'readmitted',
            ])->default('enrolled');

            $table->integer('cycle_number')->default(1);

            $table->timestamps();

            $table->unique(['student_id', 'course_id']);

            $table->foreign('student_id')
                ->references('student_id')
                ->on('students');

            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};