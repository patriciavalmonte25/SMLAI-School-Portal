<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentEnrollmentsTable extends Migration
{
    public function up()
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('surname');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('dob');
            $table->string('place_of_birth_house')->nullable();
            $table->string('place_of_birth_street')->nullable();
            $table->string('place_of_birth_barangay')->nullable();
            $table->string('place_of_birth_town');
            $table->string('place_of_birth_province')->nullable();
            $table->string('gender');
            $table->string('religion');
            $table->string('home_house')->nullable();
            $table->string('home_street')->nullable();
            $table->string('home_barangay')->nullable();
            $table->string('home_town');
            $table->string('home_province')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_occupation')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('contact_number');
            $table->string('last_school_name');
            $table->enum('last_school_type', ['PRIVATE', 'PUBLIC']);
            $table->string('last_school_address');
            $table->string('grade_enrolling');
            $table->unsignedInteger('my_class_id')->nullable();
            $table->unsignedInteger('section_id')->nullable();
            $table->string('pathway')->nullable();
            $table->string('student_id')->nullable();
            $table->string('account_username')->nullable();
            $table->string('password')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('denied_by')->nullable();
            $table->text('denied_reason')->nullable();
            $table->timestamp('denied_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_enrollments');
    }
}
