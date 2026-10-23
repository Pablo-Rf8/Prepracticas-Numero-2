<?php

namespace App\Http\Resources;

use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'reference' => $this->reference, 'user_id' => $this->user_id, 'user' => new UserResource($this->whenLoaded('user')),
            'status' => $this->status, 'notes' => $this->notes, 'currency' => 'GTQ', 'total_cents' => $this->total_cents,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (SaleItem $item): array => [
                'product_id' => $item->product_id, 'product' => $item->product,
                'quantity' => $item->quantity, 'unit_price_cents' => $item->unit_price_cents, 'line_total_cents' => $item->line_total_cents,
            ])), 'created_at' => $this->created_at,
        ];
    }
}
