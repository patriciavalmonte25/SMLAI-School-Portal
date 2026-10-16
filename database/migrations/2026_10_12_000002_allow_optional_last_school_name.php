<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AllowOptionalLastSchoolName extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE student_enrollments MODIFY last_school_name VARCHAR(255) NULL');
    }

    public function down()
    {
        if (DB::table('student_enrollments')->whereNull('last_school_name')->exists()) {
            throw new RuntimeException('Cannot require last school names while enrollment records contain null values.');
        }

        DB::statement('ALTER TABLE student_enrollments MODIFY last_school_name VARCHAR(255) NOT NULL');
    }
}