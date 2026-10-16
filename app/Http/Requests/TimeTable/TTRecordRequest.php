<?php

namespace App\Http\Requests\TimeTable;

use Illuminate\Foundation\Http\FormRequest;

class TTRecordRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules()
    {
        return [
            'target_type' => 'required|in:class,teacher',
            'my_class_id' => 'required_if:target_type,class|nullable|exists:my_classes,id',
            'owner_user_id' => 'required_if:target_type,teacher|nullable|exists:users,id',
        ];
    }

    public function attributes()
    {
        return  [
            'target_type' => 'Timetable Type',
            'my_class_id' => 'Class',
            'owner_user_id' => 'Teacher/Administrator',
        ];
    }

}
