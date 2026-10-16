<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AllowBillingNoticesWithoutStudentRecords extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE billing_notices MODIFY student_record_id INT UNSIGNED NULL');
        DB::statement('ALTER TABLE billing_notices MODIFY section_id INT UNSIGNED NULL');
    }

    public function down()
    {
        if (DB::table('billing_notices')->whereNull('student_record_id')->orWhereNull('section_id')->exists()) {
            throw new RuntimeException('Cannot make billing notice student references required while notices without records exist.');
        }

        DB::statement('ALTER TABLE billing_notices MODIFY student_record_id INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE billing_notices MODIFY section_id INT UNSIGNED NOT NULL');
    }
}