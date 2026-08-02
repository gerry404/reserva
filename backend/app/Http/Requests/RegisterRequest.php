<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'min:2', 'max:120'],
            'email'             => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            // Password::defaults() is configured once in AppServiceProvider so
            // registration, reset and change all enforce the same policy.
            'password'          => ['required', 'confirmed', Password::defaults()],
            'phone'             => ['required', 'string', new PhoneNumber()],
            'business_name'     => ['required', 'string', 'min:2', 'max:120'],
            'business_category' => ['required', 'string', 'max:100'],
            'business_city'     => ['required', 'string', 'max:100'],
            'business_country'  => ['nullable', 'string', 'size:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'         => 'Un compte existe déjà avec cet email.',
            'password.confirmed'   => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'              => 'nom',
            'email'             => 'email',
            'password'          => 'mot de passe',
            'phone'             => 'téléphone',
            'business_name'     => 'nom du commerce',
            'business_category' => 'catégorie',
            'business_city'     => 'ville',
        ];
    }

    /** ISO-3166 alpha-2, uppercased; Cameroon stays the default market. */
    public function country(): string
    {
        return strtoupper($this->input('business_country') ?: 'CM');
    }

    /** @return array{timezone: string, currency: string} */
    public function localeDefaults(): array
    {
        return Business::defaultsForCountry($this->country());
    }
}
