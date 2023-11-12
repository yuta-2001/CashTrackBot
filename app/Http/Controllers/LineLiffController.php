<?php

namespace App\Http\Controllers;

use App\Models\Opponent;
use App\Models\Transaction;
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

    public function updateOpponent(Request $request): \Illuminate\Http\JsonResponse
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

    public function showLendingAndBorrowingCreateScreen(Request $request)
    {
        if ($request->has('liff_state')) {
            $queryInfo = $request->query('liff_state');
            parse_str(ltrim($queryInfo, '?'), $params);
            $lineUserId = $params['line_user_id'] ?? null;
        }

        if ($request->has('line_user_id')) {
            $lineUserId = $request->query('line_user_id');
        }

        $user = User::where('line_user_id', $lineUserId)->first();
        $opponents = Opponent::where('user_id', $user->id)->get();

        return view('liff.lending_and_borrowing.create', compact('opponents'));
    }

    public function createLendingAndBorrowing(Request $request)
    {
        $lineUserId = $request->input('line_user_id');
        $opponentId = $request->input('opponent_id');
        $settled = (int)$request->input('settled');
        $amount = $request->input('amount');
        $type = (int)$request->input('type');
        $name = $request->input('name');
        $memo = $request->input('memo');

        if (empty($lineUserId) || empty($opponentId) || empty($amount) || empty($type) || empty($settled) || empty($name)) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        if ($type !== Transaction::TYPE_LENDING && $type !== Transaction::TYPE_BORROWING) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        $user = User::where('line_user_id', $lineUserId)->first();
        $opponent = Opponent::where('id', $opponentId)->where('user_id', $user->id)->first();

        if (!$user || !$opponent) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        Transaction::create([
            'user_id' => $user->id,
            'opponent_id' => $opponent->id,
            'is_settled' => $settled,
            'type' => $type,
            'amount' => $amount,
            'name' => $name,
            'memo' => $memo,
        ]);

        return response()->json([
            'message' => 'success',
        ], 200);
    }
}
