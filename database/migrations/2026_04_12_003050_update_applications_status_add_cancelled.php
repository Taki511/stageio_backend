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
        // Modify the status column to include 'cancelled'
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'accepted', 'refused', 'validated', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM values
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'accepted', 'refused', 'validated') DEFAULT 'pending'");
    }
};
