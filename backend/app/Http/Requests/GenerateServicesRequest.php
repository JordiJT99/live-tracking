<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth is handled by the sanctum middleware on the route
    }

    public function rules(): array
    {
        return [
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
