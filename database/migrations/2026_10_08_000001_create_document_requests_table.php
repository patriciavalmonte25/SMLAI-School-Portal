<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('requester_user_id');
            $table->unsignedInteger('student_user_id')->nullable();
            $table->string('student_name');
            $table->string('school_year_last_attended');
            $table->string('grade_section');
            $table->json('request_items');
            $table->string('form_137_grade')->nullable();
            $table->string('form_138_grade')->nullable();
            $table->text('other_request')->nullable();
            $table->text('purpose');
            $table->string('information_name');
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->string('contact_messenger');
            $table->string('father_name');
            $table->string('father_occupation');
            $table->string('mother_name');
            $table->string('mother_occupation');
            $table->text('parents_address');
            $table->string('status', 20)->default('pending');
            $table->date('release_date')->nullable();
            $table->string('fee')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('request_received_by')->nullable();
            $table->string('request_prepared_by')->nullable();
            $table->string('request_released_by')->nullable();
            $table->dateTime('collection_date')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->foreign('requester_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'created_at']);
            $table->index(['requester_user_id', 'student_user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_requests');
    }
}