<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeDocumentRequestStudentOptional extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE document_requests MODIFY student_user_id INT UNSIGNED NULL');
    }

    public function down()
    {
        if (DB::table('document_requests')->whereNull('student_user_id')->exists()) {
            throw new RuntimeException('Cannot require a student for document requests while unassigned requests exist.');
        }

        DB::statement('ALTER TABLE document_requests MODIFY student_user_id INT UNSIGNED NOT NULL');
    }
}