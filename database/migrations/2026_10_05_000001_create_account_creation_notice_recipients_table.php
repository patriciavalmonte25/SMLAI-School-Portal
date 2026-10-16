<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountCreationNoticeRecipientsTable extends Migration
{
    public function up()
    {
        Schema::create('account_creation_notice_recipients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_creation_notice_id');
            $table->unsignedInteger('user_id');
            $table->string('account_type', 20);
            $table->string('username', 100);
            $table->timestamps();

            $table->foreign('account_creation_notice_id', 'acnr_notice_fk')
                ->references('id')->on('account_creation_notices')->onDelete('cascade');
            $table->foreign('user_id', 'acnr_user_fk')
                ->references('id')->on('users')->onDelete('cascade');
            $table->unique(['account_creation_notice_id', 'user_id'], 'acnr_notice_user_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_creation_notice_recipients');
    }
}
