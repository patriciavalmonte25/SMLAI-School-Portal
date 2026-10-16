<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentGradeSheetsTable extends Migration
{
    public function up()
    {
        Schema::create('student_grade_sheets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('student_user_id')->unique();
            $table->longText('layout')->nullable();
            $table->string('visibility', 20)->default('Teaching Staff');
            $table->timestamps();

            $table->foreign('student_user_id')
                ->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_grade_sheets');
    }
}
