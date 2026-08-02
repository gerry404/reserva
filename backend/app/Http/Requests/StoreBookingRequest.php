<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id'     => ['required', 'integer'],
            'customer_name'  => ['required', 'string', 'min:2', 'max:120'],
            'customer_phone' => ['required', 'string', new PhoneNumber()],
            'customer_email' => ['nullable', 'email:rfc', 'max:255'],
            'date'           => ['required', 'date_format:Y-m-d', 'after_or_equal:today',
                                 'before_or_equal:' . now()->addDays(AvailabilityService::MAX_HORIZON_DAYS)->toDateString()],
            'time_slot'      => ['required', 'date_format:H:i'],
            'notes'          => ['nullable', 'string', 'max:500'],

            // Honeypot: a field no human sees and no human fills. Bots that
            // blind-fill every input in the form give themselves away here.
            'website'        => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.min'      => 'Merci d\'indiquer votre nom complet.',
            'date.after_or_equal'    => 'Impossible de réserver une date passée.',
            'date.before_or_equal'   => 'Cette date est trop éloignée pour être réservée.',
            'time_slot.date_format'  => 'Horaire invalide.',
            'website.prohibited'     => 'Requête invalide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name'  => 'nom',
            'customer_phone' => 'téléphone',
            'customer_email' => 'email',
            'date'           => 'date',
            'time_slot'      => 'horaire',
        ];
    }
}
