<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A booking as the owning merchant sees it.
 *
 * Never expose this to the public endpoints — it carries the customer's full
 * contact details. Those get PublicBookingResource instead.
 *
 * @mixin \App\Models\Booking
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference'      => $this->reference,
            'customer_name'  => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'notes'          => $this->notes,
            'date'           => $this->date->toDateString(),
            'time_slot'      => $this->time_slot,
            'ends_at_time'   => $this->ends_at?->format('H:i'),
            'duration'       => $this->duration,
            'price'          => (int) $this->price,
            'formatted_price' => Money::format($this->price, $this->business?->currency),
            'status'         => $this->status,
            'status_label'   => $this->status_label,
            'status_color'   => $this->status_color,
            'is_cancellable' => $this->isCancellable(),
            'service'        => new ServiceResource($this->whenLoaded('service')),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
