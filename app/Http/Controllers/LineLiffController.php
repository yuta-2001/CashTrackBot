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

    public function showOpponentEditScreen(Request $request)
    {
        $opponentId = $request->query('opponent_id');
        $opponent = Opponent::find($opponentId);

        if (!$opponent) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        return view('liff.opponent.edit');
    }

    public function updateOpponent(Request $request): \Illuminate\Http\JsonResponse
    {
        $opponentId = $request->query('opponent_id');
        $lineUserId = $request->input('line_user_id');
        $name = $request->input('name');

        if (empty($opponentId) || empty($name) || empty($lineUserId)) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        $user = User::where('line_user_id', $lineUserId)->first();
        $opponent = Opponent::where('id', $opponentId)->where('user_id', $user->id)->first();

        if (!$opponent) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        $opponent->name = $name;
        $opponent->save();

        return response()->json([
            'message' => 'success',
        ], 200);
    }
}
