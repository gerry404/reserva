<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared rules for creating and updating a service.
 *
 * On create everything required is required; on update the same fields become
 * optional, so both routes cannot drift apart.
 */
class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->business !== null;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') && ! $this->route('service') ? 'required' : 'sometimes';

        return [
            'name'        => [$required, 'string', 'min:2', 'max:120'],
            'duration'    => [$required, 'integer', 'min:' . Service::MIN_DURATION, 'max:' . Service::MAX_DURATION],
            'price'       => [$required, 'numeric', 'min:0', 'max:99999999'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'category'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'color'       => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_active'   => ['sometimes', 'boolean'],

            'images'   => ['sometimes', 'array', 'max:' . Service::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // Paths the merchant chose to keep; anything omitted gets deleted.
            'existing_images'   => ['sometimes', 'array', 'max:' . Service::MAX_IMAGES],
            'existing_images.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration.min' => 'Un service doit durer au moins ' . Service::MIN_DURATION . ' minutes.',
            'duration.max' => 'Un service ne peut pas dépasser ' . (Service::MAX_DURATION / 60) . ' heures.',
            'images.max'   => 'Vous pouvez ajouter au maximum ' . Service::MAX_IMAGES . ' photos.',
            'color.regex'  => 'La couleur doit être au format hexadécimal, ex : #6366f1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'nom',
            'duration' => 'durée',
            'price'    => 'prix',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOL)]);
        }
    }
}
