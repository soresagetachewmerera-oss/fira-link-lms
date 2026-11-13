<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLanguageRequest extends FormRequest
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
            'code' => [
                'required',
                'alpha',
                'size:2',
                function ($attribute, $value, $fail) {
                    $code = strtolower($value);
                    if ($code === 'vendor') {
                        $fail('The language code cannot be "vendor".');
                    }
                    if (is_dir(resource_path('lang/' . $code))) {
                        $fail('This language already exists.');
                    }
                },
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtolower($this->code),
            ]);
        }
    }
}
