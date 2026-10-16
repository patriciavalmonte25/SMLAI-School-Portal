<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class EnrollStudentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'surname' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'sometimes|nullable|string|max:100',
            'dob' => 'required|date',
            'place_of_birth_house' => 'sometimes|nullable|string|max:50',
            'place_of_birth_street' => 'sometimes|nullable|string|max:100',
            'place_of_birth_barangay' => 'sometimes|nullable|string|max:100',
            'place_of_birth_town' => 'required|string|max:100',
            'place_of_birth_province' => 'sometimes|nullable|string|max:100',
            'gender' => 'required|string|in:Male,Female',
            'religion' => 'required|string|max:100',
            'home_house' => 'sometimes|nullable|string|max:50',
            'home_street' => 'sometimes|nullable|string|max:100',
            'home_barangay' => 'sometimes|nullable|string|max:100',
            'home_town' => 'required|string|max:100',
            'home_province' => 'sometimes|nullable|string|max:100',
            'father_name' => 'sometimes|nullable|string|max:150',
            'father_occupation' => 'sometimes|nullable|string|max:150',
            'mother_name' => 'sometimes|nullable|string|max:150',
            'mother_occupation' => 'sometimes|nullable|string|max:150',
            'guardian_name' => 'sometimes|nullable|string|max:150',
            'guardian_occupation' => 'sometimes|nullable|string|max:150',
            'guardian_relation' => 'sometimes|nullable|string|max:100',
            'contact_number' => 'required|string|max:20',
            'last_school_name' => 'sometimes|nullable|string|max:200',
            'last_school_type' => 'sometimes|nullable|in:PRIVATE,PUBLIC',
            'last_school_address' => 'sometimes|nullable|string|max:200',
            'grade_enrolling' => 'required|string|max:50',
            'student_grade' => 'required|string|in:Kinder,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6,Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12',
            'my_class_id' => 'required|integer|exists:my_classes,id',
            'pathway' => 'sometimes|nullable|string|in:Arts and Social Sciences,Accountancy and Entrepreneurship,Architecture and Engineering,Healthcare and Safety|required_if:student_grade,Grade 11,Grade 12',
            'student_id' => 'sometimes|nullable|string|max:100|unique:users,username',
            'source_enrollment_id' => 'sometimes|nullable|integer|exists:student_enrollments,id',
            'username' => 'required|string|max:100|unique:users,username',
        ];
    }

    public function messages()
    {
        return [
            'last_school_type.in' => 'The last school type must be either PRIVATE or PUBLIC.',
        ];
    }

}
