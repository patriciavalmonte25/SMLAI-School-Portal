<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingNoticesTables extends Migration
{
    public function up()
    {
        Schema::create('billing_notices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('student_user_id');
            $table->unsignedInteger('student_record_id')->nullable();
            $table->unsignedInteger('section_id')->nullable();
            $table->string('student_name');
            $table->string('student_class');
            $table->string('student_section')->nullable();
            $table->json('layout');
            $table->unsignedInteger('sent_by');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('student_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('sent_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('billing_notice_recipients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('billing_notice_id');
            $table->unsignedInteger('user_id');
            $table->string('parent_name');
            $table->timestamps();

            $table->foreign('billing_notice_id', 'billing_notice_recipient_notice_fk')
                ->references('id')->on('billing_notices')->onDelete('cascade');
            $table->foreign('user_id', 'billing_notice_recipient_user_fk')
                ->references('id')->on('users')->onDelete('cascade');
            $table->unique(['billing_notice_id', 'user_id'], 'billing_notice_recipient_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('billing_notice_recipients');
        Schema::dropIfExists('billing_notices');
    }
}