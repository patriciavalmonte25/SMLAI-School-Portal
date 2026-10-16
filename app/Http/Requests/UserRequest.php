<?php

namespace App\Http\Requests;

use App\Helpers\Qs;
use App\Models\UserType;
use App\User;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        $storeType = $this->getStoreUserTypeTitle();

        $store =  [
            'name' => 'required|string|min:6|max:150',
            'password' => 'nullable|string|min:8|max:30',
            'user_type' => 'required',
            'dob' => 'sometimes|nullable|date',
            'gender' => 'sometimes|nullable|string',
            'relationship_to_children' => 'sometimes|nullable|string|max:100',
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'occupation' => 'sometimes|nullable|string|max:100',
            'email' => 'sometimes|nullable|email|max:100|unique:users',
            'username' => 'sometimes|nullable|alpha_dash|min:8|max:100|unique:users',
            'source_enrollment_id' => 'sometimes|nullable|integer|exists:student_enrollments,id',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'required|string|min:6|max:120',
            'state_id' => 'sometimes|nullable',
            'lga_id' => 'sometimes|nullable',
            'nal_id' => 'sometimes|nullable',
            'roles' => 'sometimes|array',
            'roles.*' => 'sometimes|nullable|string|min:2|max:100',
            'children' => 'sometimes|array',
            'children.*.class_id' => 'sometimes|nullable|integer|exists:my_classes,id',
            'children.*.student_id' => 'sometimes|nullable|string',
            'adviser_class_id' => 'sometimes|nullable|integer|exists:my_classes,id',
        ];

        if(!$this->isLeanProfileStore() && $storeType !== 'parent') {
            $store['state_id'] = 'required';
            $store['lga_id'] = 'required';
            $store['nal_id'] = 'required';
        }

        if($storeType === 'administrator') {
            $store['roles'] = 'required|array|min:1';
            $store['roles.*'] = 'required|string|min:2|max:100';
        }

        if($storeType === 'parent') {
            if($this->filled('source_enrollment_id')) {
                $store['username'] = 'required|string|alpha_dash|min:8|max:100|unique:users,username';
            } else {
                $store['children'] = 'required|array|min:1';
                $store['children.*.class_id'] = 'required|integer|exists:my_classes,id';
                $store['children.*.student_id'] = 'required|string';
            }
        }

        $update =  [
            'name' => 'sometimes|nullable|string|min:2|max:150',
            'surname' => 'sometimes|nullable|string|max:100',
            'first_name' => 'sometimes|nullable|string|max:100',
            'middle_name' => 'sometimes|nullable|string|max:100',
            'gender' => 'sometimes|nullable|string',
            'phone' => 'sometimes|nullable|string|min:6|max:20',
            'phone2' => 'sometimes|nullable|string|min:6|max:20',
            'occupation' => 'sometimes|nullable|string|max:100',
            'relationship_to_children' => 'sometimes|nullable|string|max:100',
            'email' => 'sometimes|nullable|email|max:100|unique:users,email,'.$this->user,
            'username' => 'sometimes|nullable|alpha_dash|min:3|max:100|unique:users,username,'.Qs::decodeHash($this->route('user')),
            'password' => 'sometimes|nullable|string|min:8|max:30',
            'roles' => 'sometimes|array',
            'roles.*' => 'sometimes|nullable|string|min:2|max:100',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address' => 'sometimes|nullable|string|min:1|max:120',
            'state_id' => 'sometimes|nullable',
            'lga_id' => 'sometimes|nullable',
            'nal_id' => 'sometimes|nullable',
            'my_class_id' => 'sometimes|nullable|exists:my_classes,id',
            'adviser_class_id' => 'sometimes|nullable|exists:my_classes,id',
            'section_id' => 'sometimes|nullable|exists:sections,id',
            'children' => 'sometimes|array',
            'children.*.class_id' => 'required_with:children|integer|exists:my_classes,id',
            'children.*.student_id' => 'required_with:children|string',
            'pathway' => 'sometimes|nullable|string|max:100',
            'adm_no' => 'sometimes|nullable|alpha_num|min:3|max:150|unique:student_records,adm_no,'.Qs::decodeHash($this->route('user')).',user_id',
            'dob' => 'sometimes|nullable|string|max:50',
            'place_of_birth_town' => 'sometimes|nullable|string|max:150',
            'religion' => 'sometimes|nullable|string|max:100',
            'home_town' => 'sometimes|nullable|string|max:200',
            'grade_enrolling' => 'sometimes|nullable|string|max:50',
            'student_grade' => 'sometimes|nullable|string|max:50',
            'student_id' => 'sometimes|nullable|string|max:100',
            'father_name' => 'sometimes|nullable|string|max:150',
            'father_occupation' => 'sometimes|nullable|string|max:150',
            'mother_name' => 'sometimes|nullable|string|max:150',
            'mother_occupation' => 'sometimes|nullable|string|max:150',
            'guardian_name' => 'sometimes|nullable|string|max:150',
            'guardian_occupation' => 'sometimes|nullable|string|max:150',
            'guardian_relation' => 'sometimes|nullable|string|max:100',
            'contact_number' => 'sometimes|nullable|string|max:50',
            'last_school_name' => 'sometimes|nullable|string|max:150',
            'last_school_type' => 'sometimes|nullable|string|max:50',
            'last_school_address' => 'sometimes|nullable|string|max:200',
        ];
        return ($this->method() === 'POST') ? $store : $update;
    }

    protected function isLeanProfileStore(): bool
    {
        return in_array($this->getStoreUserTypeTitle(), ['administrator', 'teacher']);
    }

    protected function getStoreUserTypeTitle(): string
    {
        if($this->method() !== 'POST') {
            return '';
        }

        $typeInput = $this->input('user_type');
        if(!$typeInput) {
            return '';
        }

        $typeId = is_numeric($typeInput) ? (int) $typeInput : (int) Qs::decodeHash($typeInput);
        if(!$typeId) {
            return '';
        }

        $type = UserType::find($typeId);
        return strtolower($type->title ?? '');
    }

    public function attributes()
    {
        return  [
            'nal_id' => 'Nationality',
            'state_id' => 'State',
            'lga_id' => 'LGA',
            'user_type' => 'User',
            'phone2' => 'Telephone',
        ];
    }

    public function messages()
    {
        $messages = [];

        if($this->method() === 'POST' && $this->filled('source_enrollment_id') && $this->filled('username')) {
            $existing = User::where('username', $this->input('username'))->first();
            if($existing) {
                $messages['username.unique'] = 'An account already exists for ' . $existing->name . ' with username ' . $existing->username . '.';
            }
        }

        return $messages;
    }

    protected function getValidatorInstance()
    {
        if($this->method() === 'POST'){
            $input = $this->all();

            $input['user_type'] = Qs::decodeHash($input['user_type']);

            $this->getInputSource()->replace($input);

        }

        if($this->method() === 'PUT'){
            $this->user = Qs::decodeHash($this->user);
        }

        return parent::getValidatorInstance();

    }
}
