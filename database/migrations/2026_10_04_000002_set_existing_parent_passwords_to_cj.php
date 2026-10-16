<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SetExistingParentPasswordsToCJ extends Migration
{
    public function up()
    {
        DB::table('users')
            ->where('user_type', 'parent')
            ->update([
                'password' => Hash::make('cj'),
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        // Previous password hashes cannot be restored after this reset.
    }
}