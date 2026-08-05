<?php

namespace App\Http\Resources;

use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The business as an anonymous visitor may see it.
 *
 * Everything internal (owner, plan, notification preferences, contact email)
 * stays out. What is here is only what the booking page needs to render.
 *
 * @mixin \App\Models\Business
 */
class PublicBusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'category'       => $this->category,
            'city'           => $this->city,
            'country'        => $this->country,
            'address'        => $this->address,
            'timezone'       => $this->timezone,
            'currency'       => $this->currency,
            'logo'           => $this->imageUrl($this->logo),
            'cover_image'    => $this->imageUrl($this->cover_image),
            'working_hours'  => $this->working_hours,
            'slot_duration'  => $this->slot_duration,
            'booking_notice' => $this->booking_notice,
            'accent_color'   => $this->accent_color,
            'whatsapp'       => $this->whatsapp ?: $this->phone,
            'services'       => ServiceResource::collection($this->whenLoaded('services')),
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        return Uploads::url($path);
    }
}
