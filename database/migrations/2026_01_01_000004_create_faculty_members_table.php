<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_members', function (Blueprint $table) {
            $table->id('faculty_id');

            $table->unsignedBigInteger('user_id')->unique();

            $table->string('full_name', 150);
            $table->string('department', 100);

            $table->boolean('suppress_schedule_warnings')
                ->default(false);

            $table->timestamps();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_members');
    }
};