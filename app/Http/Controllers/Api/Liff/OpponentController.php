<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opponent\StoreRequest;
use App\Http\Requests\Opponent\UpdateRequest;
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

    public function store(StoreRequest $request)
    {
        $user = $request->attributes->get('user');
        $opponent = Opponent::create([
            'user_id' => $user->id,
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => new OpponentResource($opponent),
        ], 200);
    }


    public function update(int $id, UpdateRequest $request)
    {
        $user = $request->attributes->get('user');
        $opponent = Opponent::where('user_id', $user->id)->where('id', $id)->first();
        $opponent->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => new OpponentResource($opponent),
        ], 200);
    }

    public function delete(int $id, Request $request)
    {
        $user = $request->attributes->get('user');
        $opponent = Opponent::where('user_id', $user->id)->where('id', $id)->first();
        $opponent->delete();

        return response()->json([
            'status' => 200,
            'message' => 'success',
        ], 200);
    }
}
