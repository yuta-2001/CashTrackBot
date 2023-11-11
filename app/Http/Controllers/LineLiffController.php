<?php

namespace App\Http\Controllers;

use App\Models\Opponent;
use App\Models\User;
use Illuminate\Http\Request;

class LineLiffController extends Controller
{
    public function showOpponentCreateScreen()
    {
        return view('liff.opponent.create');
    }

    public function createOpponent(Request $request): \Illuminate\Http\JsonResponse
    {
        $lineUserId = $request->input('line_user_id');
        $name = $request->input('name');

        $user = User::where('line_user_id', $lineUserId)->first();

        if (empty($lineUserId) || empty($name)) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        if (!$user) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        Opponent::create([
            'user_id' => $user->id,
            'name' => $name,
        ]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }
}
