<?php

namespace App\Services\Auth;

/**
 * One password policy for every place a password is set (API reset, web reset, sign-up later):
 * at least 8 characters, at least one number, at least one capital letter.
 * The Flutter new-password screen shows the same three checks live.
 */
class PasswordRules
{
    public static function rules(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'max:128',
            'regex:/[0-9]/',
            'regex:/[A-Z]/',
            'confirmed',
        ];
    }
}
