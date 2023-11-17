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
            'id' => $this->id,
            'opponent_id' => $this->opponent_id,
            'is_settled' => $this->is_settled,
            'type' => $this->type,
            'name' => $this->name,
            'amount' => $this->amount,
            'memo' => $this->memo,
        ];
    }
}
