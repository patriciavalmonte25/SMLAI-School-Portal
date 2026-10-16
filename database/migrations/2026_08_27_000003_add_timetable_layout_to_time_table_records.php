<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimetableLayoutToTimeTableRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->longText('timetable_layout')->nullable()->after('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->dropColumn('timetable_layout');
        });
    }
}
