<?php

namespace App\Http\Controllers\Liff;

use App\Models\Opponent;
use App\Models\User;
use Illuminate\Http\Request;

class OpponentController extends Controller
{
    public function showCreateScreen(Request $request)
    {
        $liffToken = null;

        if ($request->has('liff_token')) {
            $liffToken = $request->query('liff_token');
        }

        $user = User::where('liff_one_time_token', $liffToken)->first();

        if (!$user) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        return view('liff.opponent.create');
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
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

    public function showEditScreen(Request $request)
    {
        if ($request->has('liff_state')) {
            $queryInfo = $request->query('liff_state');
            parse_str(ltrim($queryInfo, '?'), $params);
            $opponentId = $params['itemId'] ?? null;
        }

        if ($request->has('itemId')) {
            $opponentId = $request->query('itemId');
        }

        $opponent = Opponent::find($opponentId);

        if (!$opponent) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        return view('liff.opponent.edit', compact('opponent'));
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $opponentId = $request->query('opponentId');
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
