<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $private = ! $request->is('api/public/*') && ($request->user()?->is_admin || $request->user()?->id === $this->id);

        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->when($private, $this->email), 'is_admin' => $this->when($private, $this->is_admin), 'created_at' => $this->created_at];
    }
}
