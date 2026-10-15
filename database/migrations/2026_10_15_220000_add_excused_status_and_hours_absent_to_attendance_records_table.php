<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_records', 'hours_absent')) {
                $table->decimal('hours_absent', 4, 2)->nullable()->after('status');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'hours_absent')) {
                $table->dropColumn('hours_absent');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present', 'absent', 'late') NOT NULL DEFAULT 'present'");
        }
    }
};
