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
        Schema::table('student_cvs', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('student_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->integer('age')->nullable()->after('last_name');
            $table->string('full_address')->nullable()->after('age');
            $table->string('phone_number')->nullable()->after('full_address');
            $table->string('academic_level')->nullable()->after('phone_number');
            $table->string('email')->nullable()->after('academic_level');
            $table->string('university_email')->nullable()->after('email');
            $table->text('motivation_letter')->nullable()->after('personal_info');
            $table->string('personal_photo')->nullable()->after('motivation_letter');
            
            // Rename existing fields for consistency
            // github_link and linkedin_link already exist
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_cvs', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'age',
                'full_address',
                'phone_number',
                'academic_level',
                'email',
                'university_email',
                'motivation_letter',
                'personal_photo',
            ]);
        });
    }
};
