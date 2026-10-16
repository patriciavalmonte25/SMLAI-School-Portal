<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTimeTableRecordsForTimetableTargets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->string('target_type', 20)->default('class')->after('name');
            $table->unsignedInteger('owner_user_id')->nullable()->after('my_class_id');
            $table->unsignedInteger('my_class_id')->nullable()->change();

            $table->index(['target_type', 'my_class_id']);
            $table->index(['target_type', 'owner_user_id']);
        });

        DB::table('time_table_records')->update([
            'target_type' => 'class',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_table_records', function (Blueprint $table) {
            $table->dropIndex(['target_type', 'my_class_id']);
            $table->dropIndex(['target_type', 'owner_user_id']);
            $table->dropColumn(['target_type', 'owner_user_id']);
            $table->unsignedInteger('my_class_id')->nullable(false)->change();
        });
    }
}
