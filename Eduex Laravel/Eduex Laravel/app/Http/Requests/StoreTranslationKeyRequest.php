<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'group' => ['required', 'string', function ($attribute, $value, $fail) {
                $path = resource_path("lang/en/{$value}.php");
                if (!file_exists($path)) {
                    $fail('Invalid translation group selected.');
                }
            }],
            'key' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
                function ($attribute, $value, $fail) {
                    $group = $this->input('group');
                    if ($group) {
                        $translations = @include(resource_path("lang/en/{$group}.php"));
                        if (is_array($translations) && array_key_exists($value, $translations)) {
                            $fail('This translation key already exists.');
                        }
                    }
                }
            ],
            'value' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'key.regex' => 'The translation key may only contain letters, numbers, dashes, underscores, and dots.',
        ];
    }
}
