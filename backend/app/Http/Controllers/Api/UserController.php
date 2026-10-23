<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,100'], 'page' => ['sometimes', 'integer', 'min:1']]);

        return UserResource::collection(User::orderBy('id')->paginate($data['per_page'] ?? 10));
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function store(Request $request): UserResource
    {
        return new UserResource(User::create($request->validate($this->rules($request))));
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate($this->rules($request, $user));
        $user->update($data);
        if (isset($data['password'])) {
            $user->tokens()->delete();
        }

        return new UserResource($user->fresh());
    }

    public function destroy(Request $request, User $user): Response
    {
        abort_if($request->user()->id === $user->id || $user->is_admin || $user->sales()->exists(), 409, 'No se puede eliminar esta cuenta administradora o relacionada con ventas.');
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }

    private function rules(Request $request, ?User $user = null): array
    {
        $required = ! $user || $request->isMethod('put') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'], 'email' => [$required, 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'sometimes' : 'required', 'string', 'min:8', 'max:128'], 'is_admin' => ['prohibited'],
        ];
    }
}
