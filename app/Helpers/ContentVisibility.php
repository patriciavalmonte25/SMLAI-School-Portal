<?php

namespace App\Helpers;

class ContentVisibility
{
    private const TYPES = [
        'registrar' => 'Registrars',
        'accountant' => 'Accountants',
        'teacher' => 'Teachers',
        'student' => 'Students',
        'parent' => 'Parents',
    ];

    public static function types()
    {
        return self::TYPES;
    }

    public static function defaults()
    {
        return array_keys(self::TYPES);
    }

    public static function normalize($types)
    {
        return collect(is_array($types) ? $types : [])
            ->filter(function ($type) {
                return array_key_exists($type, self::TYPES);
            })
            ->unique()
            ->values()
            ->all();
    }

    public static function allows(array $visibility, $userType)
    {
        if (in_array($userType, ['admin', 'administrator'], true)) {
            return true;
        }

        return in_array($userType, self::normalize($visibility), true);
    }
}
