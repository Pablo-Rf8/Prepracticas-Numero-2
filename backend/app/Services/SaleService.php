<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function save(array $data, ?Sale $sale = null): Sale
    {
        return DB::transaction(function () use ($data, $sale): Sale {
            $sale = $sale ? Sale::lockForUpdate()->findOrFail($sale->id) : new Sale(['reference' => 'V-'.Str::uuid(), 'total_cents' => 0]);
            $sale->fill(array_intersect_key($data, array_flip(['user_id', 'status', 'notes'])));
            if (array_key_exists('items', $data)) {
                $products = Product::whereIn('id', array_column($data['items'], 'product_id'))->lockForUpdate()->get()->keyBy('id');
                $items = [];
                foreach ($data['items'] as $item) {
                    $product = $products->get($item['product_id']);
                    if (! $product || ! $product->active) {
                        throw ValidationException::withMessages(['items' => ['Uno de los productos no está disponible.']]);
                    }
                    $items[] = ['product_id' => $product->id, 'quantity' => $item['quantity'], 'unit_price_cents' => $product->price_cents, 'line_total_cents' => $product->price_cents * $item['quantity']];
                }
                $sale->total_cents = array_sum(array_column($items, 'line_total_cents'));
                $sale->save();
                $sale->items()->delete();
                $sale->items()->createMany($items);
            } else {
                $sale->save();
            }

            return $sale->load(['user', 'items.product']);
        });
    }
}
