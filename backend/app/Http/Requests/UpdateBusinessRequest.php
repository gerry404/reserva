<?php

namespace App\Http\Requests;

use App\Models\Business;
use App\Rules\PhoneNumber;
use App\Rules\WorkingHours;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->business !== null;
    }

    public function rules(): array
    {
        $businessId = $this->user()->business->id;

        return [
            'name'        => ['sometimes', 'string', 'min:2', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'category'    => ['sometimes', 'string', 'max:100'],
            'address'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'city'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'country'     => ['sometimes', 'string', 'size:2'],
            'timezone'    => ['sometimes', 'string', 'timezone'],
            'currency'    => ['sometimes', 'string', 'size:3', function ($attribute, $value, $fail) {
                if (! Money::isSupported($value)) {
                    $fail('Cette devise n\'est pas encore prise en charge.');
                }
            }],

            'phone'    => ['sometimes', 'nullable', 'string', new PhoneNumber()],
            'whatsapp' => ['sometimes', 'nullable', 'string', new PhoneNumber()],

            'working_hours'  => ['sometimes', 'array', new WorkingHours()],
            'slot_duration'  => ['sometimes', 'integer', Rule::in(Business::SLOT_DURATIONS)],
            // Up to a week of notice; beyond that the merchant is closed, not busy.
            'booking_notice' => ['sometimes', 'integer', 'min:0', 'max:10080'],

            'notifications_whatsapp' => ['sometimes', 'boolean'],
            'notifications_sms'      => ['sometimes', 'boolean'],
            'notifications_email'    => ['sometimes', 'boolean'],
            'is_active'              => ['sometimes', 'boolean'],

            'accent_color' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],

            'logo'        => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'slug' => [
                'sometimes',
                'string',
                'min:3',
                'max:60',
                // Lowercase, digits and single inner hyphens: it has to survive
                // being written on a shopfront and typed from memory.
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(Business::RESERVED_SLUGS),
                Rule::unique('businesses', 'slug')->ignore($businessId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Le lien ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
            'slug.not_in' => 'Ce lien est réservé, choisissez-en un autre.',
            'slug.unique' => 'Ce lien est déjà utilisé par un autre commerce.',
            'accent_color.regex' => 'La couleur doit être au format hexadécimal, ex : #6366f1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge(['slug' => mb_strtolower(trim((string) $this->input('slug')))]);
        }

        // Multipart requests arrive with everything stringified, so JSON fields
        // and booleans need decoding before the rules above can judge them.
        if (is_string($this->input('working_hours'))) {
            $decoded = json_decode((string) $this->input('working_hours'), true);
            if (is_array($decoded)) {
                $this->merge(['working_hours' => $decoded]);
            }
        }

        foreach (['notifications_whatsapp', 'notifications_sms', 'notifications_email', 'is_active'] as $flag) {
            if ($this->has($flag)) {
                $this->merge([$flag => filter_var($this->input($flag), FILTER_VALIDATE_BOOL)]);
            }
        }
    }
}
