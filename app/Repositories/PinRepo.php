<?php

namespace App\Repositories;

use App\Models\Pin;

class PinRepo
{
    public function countValid()
    {
        return Pin::where('used', 0)->count();
    }

    public function getValid()
    {
        return Pin::where('used', 0)->orderBy('id', 'desc')->get();
    }

    public function getInValid()
    {
        return Pin::where('used', 1)->orderBy('id', 'desc')->get();
    }

    public function findValidCode($code)
    {
        return Pin::where(['code' => $code, 'used' => 0]);
    }

    public function getUserPin($code, $user_id, $student_id)
    {
        return Pin::where(['code' => $code, 'user_id' => $user_id, 'student_id' => $student_id, 'used' => 0]);
    }

    public function update($id, array $data)
    {
        return Pin::find($id)->update($data);
    }

    public function create(array $data)
    {
        return Pin::insert($data);
    }

    public function deleteUsed()
    {
        return Pin::where('used', 1)->delete();
    }
}
