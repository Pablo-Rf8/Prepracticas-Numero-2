<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return $this->listing($request);
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        return $this->listing($request, $request->user()->id);
    }

    private function listing(Request $request, ?int $userId = null): AnonymousResourceCollection
    {
        $data = $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,100'], 'page' => ['sometimes', 'integer', 'min:1']]);

        return SaleResource::collection(Sale::with(['user', 'items.product'])->when($userId, fn (Builder $query) => $query->where('user_id', $userId))->orderBy('id')->paginate($data['per_page'] ?? 10));
    }

    public function show(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load(['user', 'items.product']));
    }

    public function store(Request $request, SaleService $service): SaleResource
    {
        return new SaleResource($service->save($request->validate($this->rules($request))));
    }

    public function update(Request $request, Sale $sale, SaleService $service): SaleResource
    {
        return new SaleResource($service->save($request->validate($this->rules($request, true)), $sale));
    }

    public function destroy(Sale $sale): Response
    {
        $sale->delete();

        return response()->noContent();
    }

    private function rules(Request $request, bool $updating = false): array
    {
        $required = ! $updating || $request->isMethod('put') ? 'required' : 'sometimes';

        return [
            'user_id' => [$required, 'integer', 'exists:users,id'], 'status' => [$required, Rule::in(['pending', 'paid', 'cancelled'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'], 'items' => [$required, 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'], 'items.*.quantity' => ['required', 'integer', 'between:1,10000'],
            'total_cents' => ['prohibited'],
        ];
    }
}
