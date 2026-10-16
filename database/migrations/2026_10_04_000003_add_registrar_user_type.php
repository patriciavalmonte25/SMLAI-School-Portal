<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddRegistrarUserType extends Migration
{
    public function up()
    {
        DB::table('user_types')->updateOrInsert(
            ['title' => 'registrar'],
            ['name' => 'Registrar', 'level' => 7, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down()
    {
        DB::table('user_types')->where('title', 'registrar')->delete();
    }
}