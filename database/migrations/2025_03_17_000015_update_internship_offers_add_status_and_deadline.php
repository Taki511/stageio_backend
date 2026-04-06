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
        Schema::table('internship_offers', function (Blueprint $table) {
            $table->enum('status', ['open', 'closed'])->default('open')->after('duration');
            $table->integer('max_students')->default(1)->after('status');
            $table->date('deadline')->nullable()->after('max_students');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internship_offers', function (Blueprint $table) {
            $table->dropColumn(['status', 'max_students', 'deadline']);
        });
    }
};
