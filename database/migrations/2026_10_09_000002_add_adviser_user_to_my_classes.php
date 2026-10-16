<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdviserUserToMyClasses extends Migration
{
    public function up()
    {
        Schema::table('my_classes', function (Blueprint $table) {
            $table->unsignedInteger('adviser_user_id')->nullable()->after('class_type_id');
            $table->foreign('adviser_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('my_classes', function (Blueprint $table) {
            $table->dropForeign(['adviser_user_id']);
            $table->dropColumn('adviser_user_id');
        });
    }
}