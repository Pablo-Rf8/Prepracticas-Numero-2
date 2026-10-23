<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,100'], 'page' => ['sometimes', 'integer', 'min:1']]);

        return JsonResource::collection(Product::orderBy('id')->paginate($data['per_page'] ?? 10));
    }

    public function show(Product $product): JsonResource
    {
        return new JsonResource($product);
    }

    public function store(Request $request): JsonResource
    {
        return new JsonResource(Product::create($request->validate($this->rules($request))));
    }

    public function update(Request $request, Product $product): JsonResource
    {
        $product->update($request->validate($this->rules($request, $product)));

        return new JsonResource($product->fresh());
    }

    public function destroy(Product $product): Response
    {
        abort_if($product->saleItems()->exists(), 409, 'El producto está relacionado con ventas.');
        $product->delete();

        return response()->noContent();
    }

    private function rules(Request $request, ?Product $product = null): array
    {
        $required = ! $product || $request->isMethod('put') ? 'required' : 'sometimes';

        return [
            'sku' => [$required, 'string', 'max:100', Rule::unique('products')->ignore($product)], 'name' => [$required, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'], 'price_cents' => [$required, 'integer', 'between:0,1000000000'], 'active' => [$required, 'boolean'],
        ];
    }
}
