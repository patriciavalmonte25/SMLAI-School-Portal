<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowMultipleClassTimetablesPerYear extends Migration
{
    public function up()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->index('my_class_id', 'time_table_records_my_class_id_index');
        });

        Schema::table('time_table_records', function (Blueprint $table) {
            $table->dropUnique(['my_class_id', 'exam_id', 'year']);
        });
    }

    public function down()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->unique(['my_class_id', 'exam_id', 'year']);
        });

        Schema::table('time_table_records', function (Blueprint $table) {
            $table->dropIndex('time_table_records_my_class_id_index');
        });
    }
}