<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id('attendance_id');

            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('recorded_by');

            $table->date('session_date');

            $table->enum('status', [
                'present',
                'absent',
                'late',
            ]);

            $table->string('remarks', 255)->nullable();

            $table->boolean('is_out_of_schedule')
                ->default(false);

            $table->timestamps();

            $table->foreign('enrollment_id')
                ->references('enrollment_id')
                ->on('enrollments');

            $table->foreign('recorded_by')
                ->references('faculty_id')
                ->on('faculty_members');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};