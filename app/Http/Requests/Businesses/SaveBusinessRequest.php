<?php

namespace App\Http\Requests\Businesses;

use App\Enums\Country;
use App\Rules\BusinessName;
use App\Rules\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SaveBusinessRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new BusinessName],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone_country' => ['nullable', new Enum(Country::class)],
            'phone' => ['nullable', 'string', new Phone('phone_country')],
        ];
    }
}
