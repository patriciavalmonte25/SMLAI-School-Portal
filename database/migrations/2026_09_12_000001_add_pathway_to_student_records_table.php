<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPathwayToStudentRecordsTable extends Migration
{
    public function up()
    {
        Schema::table('student_records', function (Blueprint $table) {
            $table->string('pathway')->nullable()->after('section_id');
        });
    }

    public function down()
    {
        Schema::table('student_records', function (Blueprint $table) {
            $table->dropColumn('pathway');
        });
    }
}
