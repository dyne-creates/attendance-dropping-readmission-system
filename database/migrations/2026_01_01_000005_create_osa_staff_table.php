<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osa_staff', function (Blueprint $table) {
            $table->id('osa_id');

            $table->unsignedBigInteger('user_id')->unique();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osa_staff');
    }
};
