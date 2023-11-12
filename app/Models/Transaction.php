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
        'is_borrower',
        'name',
        'amount',
        'type',
        'memo',
    ];

    const TYPE_LENDING = 1;
    const TYPE_BORROWING = 2;
}
