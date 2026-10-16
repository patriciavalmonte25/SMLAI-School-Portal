<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Qs;
use App\Models\StudentRecord;

class StudentRecordUpdate extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $studentRecordId = Qs::decodeHash($this->route('student'));
        $studentUserId = StudentRecord::whereKey($studentRecordId)->value('user_id');

        return [
            'name' => 'required|string|min:6|max:150',
            'username' => 'sometimes|nullable|alpha_dash|min:3|max:100|unique:users,username,'.$studentUserId,
            'gender' => 'required|string',
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'email' => 'sometimes|nullable|email|max:100|unique:users,id',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'required|string|min:6|max:120',
            'bg_id' => 'sometimes|nullable',
            'my_class_id' => 'required',
            'section_id' => 'required',
            'state_id' => 'required',
            'lga_id' => 'required',
            'nal_id' => 'required',
            'my_parent_id' => 'sometimes|nullable',
        ];
    }

    public function attributes()
    {
        return  [
            'nal_id' => 'Nationality',
            'state_id' => 'State',
            'lga_id' => 'LGA',
            'bg_id' => 'Blood Group',
            'my_parent_id' => 'Parent',
            'my_class_id' => 'Class',
            'section_id' => 'Section',
        ];
    }

    protected function getValidatorInstance()
    {
        $input = $this->all();

        $input['my_parent_id'] = $input['my_parent_id'] ? Qs::decodeHash($input['my_parent_id']) : NULL;

        $this->getInputSource()->replace($input);

        return parent::getValidatorInstance();
    }
}
