<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'createdAt' => $this->created_at,
            'blockId' => $this->index_id,
            'transaction' => $this->transaction,
            'type' => $this->type,
        ];
    }
}
