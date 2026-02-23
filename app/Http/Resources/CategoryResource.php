<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Category
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'icon' => 'shape-outline',
            'accentColor' => '#1E5BB8',
            'cover_image_url' => $this->cover_image ? asset('storage/' . $this->cover_image) : null,
        ];
    }
}
