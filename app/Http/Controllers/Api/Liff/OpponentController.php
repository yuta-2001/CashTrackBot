<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Models\Opponent;
use App\Http\Resources\OpponentResource;
use Illuminate\Http\Request;

class OpponentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');
        $opponents = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();

        return OpponentResource::collection($opponents);
    }
}
