<?php

namespace App\Helpers;

class AccountCredentials
{
    public static function randomPassword($length = 8)
    {
        $groups = [
            'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz',
            '23456789',
            '!@#$%^&*()-_=+',
        ];
        $characters = implode('', $groups);
        $passwordCharacters = [];

        foreach ($groups as $group) {
            $passwordCharacters[] = $group[random_int(0, strlen($group) - 1)];
        }

        while (count($passwordCharacters) < $length) {
            $passwordCharacters[] = $characters[random_int(0, strlen($characters) - 1)];
        }

        for ($index = count($passwordCharacters) - 1; $index > 0; $index--) {
            $swapIndex = random_int(0, $index);
            [$passwordCharacters[$index], $passwordCharacters[$swapIndex]] = [$passwordCharacters[$swapIndex], $passwordCharacters[$index]];
        }

        return implode('', $passwordCharacters);
    }
}