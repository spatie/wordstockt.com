<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,png',
                'max:5120',
                Rule::dimensions()->minWidth(100)->minHeight(100),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Please choose an image to upload.',
            'avatar.image' => 'The avatar must be an image.',
            'avatar.mimes' => 'The avatar must be a JPEG or PNG image.',
            'avatar.max' => 'The avatar may not be larger than 5 MB.',
        ];
    }
}
