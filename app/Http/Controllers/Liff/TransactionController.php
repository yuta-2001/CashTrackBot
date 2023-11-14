<?php

namespace App\Http\Controllers\Liff;

use App\Http\Controllers\Controller;
use App\Models\Opponent;
use App\Models\Transaction;
use App\Models\User;
use App\Service\LineLoginApiService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function showLendingAndBorrowingCreateScreen(Request $request)
    {
        $user = null;
        if ($request->has('liff_token')) {
            $liff_one_time_token = $request->query('liff_token');
            $user = User::where('liff_one_time_token', $liff_one_time_token)->first();
        }

        $opponents = Opponent::where('user_id', $user->id)->get();

        return view('liff.lending_and_borrowing.create', compact('opponents'));
    }

    public function createLendingAndBorrowing(Request $request)
    {
        $accessToken = $request->input('access_token');
        $liffToken = $request->input('liff_token');
        $opponentId = $request->input('opponent_id');
        $settled = (int)$request->input('settled');
        $amount = $request->input('amount');
        $type = (int)$request->input('type');
        $name = $request->input('name');
        $memo = $request->input('memo');

        if (empty($accessToken) ||
            empty($liffToken) ||
            empty($opponentId) || 
            empty($amount) || 
            empty($type) || 
            !isset($settled) || 
            $settled === '' ||
            $settled === null || 
            empty($name)
        ) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        // ユーザーのaccessTokenを検証
        $accessTokenVerifyResult = LineLoginApiService::verifyAccessToken($accessToken);

        if ($accessTokenVerifyResult['status'] === 'error') {
            return response()->json([
                'message' => 'invalid access token',
            ], 400);
        }

        if ($type !== Transaction::TYPE_LENDING && $type !== Transaction::TYPE_BORROWING) {
            return response()->json([
                'message' => 'invalid transaction type',
            ], 400);
        }

        // accessTokenからユーザー情報を取得
        $response = LineLoginApiService::getProfileFromAccessToken($accessToken);
        if ($response['status'] === 'error') {
            return response()->json([
                'message' => 'invalid access token',
            ], 400);
        }

        $lineUserId = $response['userId'];
        $user = User::where('liff_one_time_token', $liffToken)->where('line_user_id', $lineUserId)->first();
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

        $user->liff_one_time_token = null;
        $user->save();

        return response()->json([
            'message' => 'success',
        ], 200);
    }
}
