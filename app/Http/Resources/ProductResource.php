<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $dataId = $this->transaction['data_id'];

        $data = Transaction::whereIn('type', ["create_data", "update_data"])
            ->orWhere(function ($query) use ($dataId) {
                $query->where('type', 'create_data');
                $query->where('transaction->uid', $dataId);
            })
            ->orWhere(function ($query) use ($dataId) {
                $query->where('type', 'update_data');
                $query->where('transaction->data_id', $dataId);
            })
            ->orderByDesc('index_id')
            ->first();


        return [
            'uid' => $this->uid,
            'createdAt' => $this->created_at,
            'blockId' => $this->index_id,
            'transaction' => $this->transaction,
            'type' => $this->type,
            'data' => $data->transaction,
        ];
    }
}
