<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnrollmentCompletionFieldsToStudentEnrollments extends Migration
{
    public function up()
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->unsignedInteger('enrolled_student_user_id')->nullable()->after('student_id');
            $table->timestamp('enrolled_at')->nullable()->after('denied_at');
            $table->string('created_student_username')->nullable()->after('account_username');
            $table->timestamp('account_created_notice_at')->nullable()->after('enrolled_at');
            $table->timestamp('account_created_acknowledged_at')->nullable()->after('account_created_notice_at');
            $table->timestamp('temporary_account_delete_at')->nullable()->after('account_created_acknowledged_at');
        });
    }

    public function down()
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'enrolled_student_user_id',
                'enrolled_at',
                'created_student_username',
                'account_created_notice_at',
                'account_created_acknowledged_at',
                'temporary_account_delete_at',
            ]);
        });
    }
}
