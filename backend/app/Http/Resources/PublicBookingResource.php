<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A booking as the customer who made it sees it.
 *
 * Reachable with only a reference plus a phone number, so it deliberately
 * repeats back nothing the holder did not already supply: no email, no notes,
 * no internal id — just enough to recognise the appointment.
 *
 * @mixin \App\Models\Booking
 */
class PublicBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $business = $this->business;

        return [
            'reference'       => $this->reference,
            'customer_name'   => $this->customer_name,
            'service'         => $this->service?->name,
            'date'            => $this->date->toDateString(),
            'date_label'      => $this->date->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'time'            => $this->time_slot,
            'ends_at_time'    => $this->ends_at?->format('H:i'),
            'duration'        => $this->duration,
            'formatted_price' => Money::format($this->price, $business?->currency),
            'status'          => $this->status,
            'status_label'    => $this->status_label,
            'can_cancel'      => $this->isCancellable(),
            'business_name'   => $business?->name,
            'business_phone'  => $business?->whatsapp ?: $business?->phone,
            'business_slug'   => $business?->slug,
        ];
    }
}
