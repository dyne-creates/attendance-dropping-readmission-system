<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readmission_requests', function (Blueprint $table): void {
            $table->json('excused_dates')->nullable()->after('excused_through');
            $table->timestamp('correction_requested_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('readmission_requests', function (Blueprint $table): void {
            $table->dropColumn(['excused_dates', 'correction_requested_at']);
        });
    }
};
