<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('readmission_requests', function (Blueprint $table): void {
            $table->string('outcome', 30)->nullable()->after('status');
            $table->date('excused_from')->nullable()->after('outcome');
            $table->date('excused_through')->nullable()->after('excused_from');
            $table->decimal('excused_hours_deducted', 6, 2)->nullable()->after('excused_through');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readmission_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'outcome',
                'excused_from',
                'excused_through',
                'excused_hours_deducted',
            ]);
        });
    }
};
