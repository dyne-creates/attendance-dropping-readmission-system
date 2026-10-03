<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id('student_id');

            $table->unsignedBigInteger('user_id')->unique();

            $table->string('student_number', 50)->unique();
            $table->string('last_name', 100);
            $table->string('first_name', 100);

            $table->enum('honorifics', [
                'Mr.',
                'Ms.',
                'Mx.',
                'Other',
            ]);

            $table->string('department', 100);
            $table->integer('year_level');
            $table->string('program', 100);

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
