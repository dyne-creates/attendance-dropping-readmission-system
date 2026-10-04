<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id('guardian_id');

            $table->unsignedBigInteger('student_id');

            $table->string('full_name', 150);
            $table->string('relationship', 50);

            $table->string('email', 255)->nullable();

            $table->string('phone_number', 20);

            $table->boolean('is_verified')->default(false);

            $table->string('verification_method', 50)->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->foreign('student_id')
                ->references('student_id')
                ->on('students');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
