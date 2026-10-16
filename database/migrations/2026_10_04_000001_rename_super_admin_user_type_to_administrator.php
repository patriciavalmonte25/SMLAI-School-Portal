<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenameSuperAdminUserTypeToAdministrator extends Migration
{
    public function up()
    {
        DB::table('users')->where('user_type', 'super_admin')->update(['user_type' => 'administrator']);
        DB::table('user_types')->where('title', 'super_admin')->update([
            'title' => 'administrator',
            'name' => 'Administrator',
        ]);
    }

    public function down()
    {
        DB::table('users')->where('user_type', 'administrator')->update(['user_type' => 'super_admin']);
        DB::table('user_types')->where('title', 'administrator')->update([
            'title' => 'super_admin',
            'name' => 'Super Admin',
        ]);
    }
}