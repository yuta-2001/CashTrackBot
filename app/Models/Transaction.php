<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'opponent_id',
        'is_settled',
        'name',
        'amount',
        'type',
        'memo',
    ];

    protected $casts = [
        'is_settled' => 'boolean'
    ];

    const TYPE_LENDING = 1;
    const TYPE_BORROWING = 2;

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opponent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Opponent::class);
    }

    public function scopeUnsettledLendings($query)
    {
        return $query->where('is_settled', false)->where('type', self::TYPE_LENDING);
    }

    public function scopeUnsettledBorrowings($query)
    {
        return $query->where('is_settled', false)->where('type', self::TYPE_BORROWING);
    }

    public function scopeSettled($query)
    {
        return $query->where('is_settled', true);
    }

    public function getTypeNameAttribute()
    {
        switch ($this->type) {
            case self::TYPE_LENDING:
                return '貸し';
            case self::TYPE_BORROWING:
                return '借り';
            default:
                return '';
        }
    }
}
