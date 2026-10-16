<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateParentStudentLinksTable extends Migration
{
    public function up()
    {
        Schema::create('parent_student_links', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_user_id');
            $table->unsignedInteger('student_user_id');
            $table->timestamps();

            $table->foreign('parent_user_id', 'parent_student_parent_fk')
                ->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_user_id', 'parent_student_student_fk')
                ->references('id')->on('users')->onDelete('cascade');
            $table->unique(['parent_user_id', 'student_user_id'], 'parent_student_link_unique');
        });

        $now = now();
        $legacyLinks = DB::table('student_records')
            ->whereNotNull('my_parent_id')
            ->select('my_parent_id as parent_user_id', 'user_id as student_user_id')
            ->get();

        foreach ($legacyLinks as $link) {
            DB::table('parent_student_links')->insertOrIgnore([
                'parent_user_id' => $link->parent_user_id,
                'student_user_id' => $link->student_user_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('student_enrollment_accounts')) {
            $enrollmentLinks = DB::table('student_enrollment_accounts as accounts')
                ->join('student_enrollments as enrollments', 'enrollments.id', '=', 'accounts.student_enrollment_id')
                ->where('accounts.account_type', 'Parent')
                ->whereNotNull('enrollments.enrolled_student_user_id')
                ->select('accounts.user_id as parent_user_id', 'enrollments.enrolled_student_user_id as student_user_id')
                ->distinct()
                ->get();

            foreach ($enrollmentLinks as $link) {
                DB::table('parent_student_links')->insertOrIgnore([
                    'parent_user_id' => $link->parent_user_id,
                    'student_user_id' => $link->student_user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('parent_student_links');
    }
}
