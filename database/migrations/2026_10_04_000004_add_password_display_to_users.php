<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPasswordDisplayToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('password_encrypted')->nullable();
        });

        DB::table('users')
            ->where('user_type', 'parent')
            ->update(['password_encrypted' => encrypt('cj')]);
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_encrypted');
        });
    }
}