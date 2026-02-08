<?php

namespace App\Http\Requests\Dictionary;

use Illuminate\Foundation\Http\FormRequest;

class RequestWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'word' => ['required', 'string', 'min:2', 'max:50', 'alpha'],
            'language' => ['required', 'string', 'in:nl,en'],
        ];
    }
}
