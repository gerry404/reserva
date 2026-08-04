<?php

namespace App\Http\Resources;

use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Service
 */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'description'        => $this->description,
            'duration'           => $this->duration,
            'price'              => (int) $this->price,
            'formatted_price'    => $this->formatted_price,
            'formatted_duration' => $this->formatted_duration,
            'color'              => $this->color,
            'icon'               => $this->icon,
            'category'           => $this->category,
            'is_active'          => $this->is_active,
            /*
             * Both halves, deliberately.
             *
             * `url` is what an <img> needs and only the server can build it,
             * it depends on whether uploads sit on a local disk or in a bucket.
             * `path` is the identifier the edit form sends back in
             * `existing_images` to say which pictures to keep. Returning URLs
             * alone made that round trip impossible.
             */
            'images' => collect($this->images ?? [])
                ->map(fn (string $path) => [
                    'path' => $path,
                    'url'  => Uploads::url($path),
                ])
                ->values()
                ->all(),
        ];
    }
}
