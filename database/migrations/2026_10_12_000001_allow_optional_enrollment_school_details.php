<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AllowOptionalEnrollmentSchoolDetails extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE student_enrollments MODIFY last_school_type ENUM('PRIVATE', 'PUBLIC') NULL");
        DB::statement('ALTER TABLE student_enrollments MODIFY last_school_address VARCHAR(255) NULL');
    }

    public function down()
    {
        if (DB::table('student_enrollments')->whereNull('last_school_type')->orWhereNull('last_school_address')->exists()) {
            throw new RuntimeException('Cannot make enrollment school details required while records contain null values.');
        }

        DB::statement("ALTER TABLE student_enrollments MODIFY last_school_type ENUM('PRIVATE', 'PUBLIC') NOT NULL");
        DB::statement('ALTER TABLE student_enrollments MODIFY last_school_address VARCHAR(255) NOT NULL');
    }
}