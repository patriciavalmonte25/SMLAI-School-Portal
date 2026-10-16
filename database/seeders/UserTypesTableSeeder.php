<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        $types = [
            ['title' => 'administrator', 'name' => 'Administrator', 'level' => 1],
            ['title' => 'registrar', 'name' => 'Registrar', 'level' => 7],
            ['title' => 'admin', 'name' => 'Admin', 'level' => 2],
            ['title' => 'teacher', 'name' => 'Teacher', 'level' => 3],
            ['title' => 'parent', 'name' => 'Parent', 'level' => 4],
            ['title' => 'accountant', 'name' => 'Accountant', 'level' => 5],
            ['title' => 'librarian', 'name' => 'Librarian', 'level' => 6],
        ];

        foreach ($types as $t) {
            DB::table('user_types')->updateOrInsert(
                ['title' => $t['title']],
                ['name' => $t['name'], 'level' => $t['level'], 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
