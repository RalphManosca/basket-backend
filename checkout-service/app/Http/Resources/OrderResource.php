<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => $this->id,
                'user_email' => $this->user_email,
                'total_amount' => $this->total_amount,
                'status' => $this->status,
                'items' => OrderItemResource::collection($this->items),
                'created_at' => $this->created_at->toIso8601String(),
            ],
        ];
    }
}
