<?php

use App\Helpers\ContentVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVisibilityToEventsAndAnnouncements extends Migration
{
    public function up()
    {
        foreach (['events', 'announcements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->json('visibility')->nullable();
            });

            DB::table($tableName)->whereNull('visibility')->update([
                'visibility' => json_encode(ContentVisibility::defaults()),
            ]);
        }
    }

    public function down()
    {
        foreach (['events', 'announcements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('visibility');
            });
        }
    }
}