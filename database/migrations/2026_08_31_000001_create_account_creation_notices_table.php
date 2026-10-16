<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountCreationNoticesTable extends Migration
{
    public function up()
    {
        Schema::create('account_creation_notices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('student_enrollment_id');
            $table->unsignedInteger('sent_by')->nullable();
            $table->json('accounts');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('temporary_account_delete_at')->nullable();
            $table->timestamps();

            $table->foreign('student_enrollment_id')->references('id')->on('student_enrollments')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_creation_notices');
    }
}
