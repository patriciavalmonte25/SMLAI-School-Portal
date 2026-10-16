<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStudentEnrollmentAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('student_enrollment_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('student_enrollment_id');
            $table->unsignedInteger('user_id');
            $table->string('account_type', 20);
            $table->timestamps();

            $table->foreign('student_enrollment_id', 'sea_enrollment_fk')
                ->references('id')->on('student_enrollments')->onDelete('cascade');
            $table->foreign('user_id', 'sea_user_fk')
                ->references('id')->on('users')->onDelete('cascade');
            $table->unique(['student_enrollment_id', 'user_id'], 'sea_enrollment_user_unique');
        });

        $now = now();
        $studentLinks = DB::table('student_enrollments')
            ->join('users', 'users.id', '=', 'student_enrollments.enrolled_student_user_id')
            ->whereNotNull('student_enrollments.enrolled_student_user_id')
            ->where('users.user_type', 'student')
            ->select('student_enrollments.id as student_enrollment_id', 'users.id as user_id')
            ->get();

        foreach ($studentLinks as $link) {
            DB::table('student_enrollment_accounts')->insertOrIgnore([
                'student_enrollment_id' => $link->student_enrollment_id,
                'user_id' => $link->user_id,
                'account_type' => 'Student',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $parentLinks = DB::table('student_enrollments')
            ->join('student_records', 'student_records.user_id', '=', 'student_enrollments.enrolled_student_user_id')
            ->join('users as parent_users', 'parent_users.id', '=', 'student_records.my_parent_id')
            ->whereNotNull('student_enrollments.enrolled_student_user_id')
            ->where('parent_users.user_type', 'parent')
            ->select('student_enrollments.id as student_enrollment_id', 'parent_users.id as user_id')
            ->distinct()
            ->get();

        foreach ($parentLinks as $link) {
            DB::table('student_enrollment_accounts')->insertOrIgnore([
                'student_enrollment_id' => $link->student_enrollment_id,
                'user_id' => $link->user_id,
                'account_type' => 'Parent',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('student_enrollment_accounts');
    }
}