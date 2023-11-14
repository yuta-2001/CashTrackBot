<?php

namespace App\Http\Controllers\Liff;

use App\Http\Controllers\Controller;
use App\Models\Opponent;
use App\Models\User;
use App\Service\LineLoginApiService;
use Illuminate\Http\Request;

class OpponentController extends Controller
{
    public function showCreateScreen(Request $request)
    {
        if ($request->has('liff_state')) {
            $queryInfo = $request->query('liff_state');
            parse_str(ltrim($queryInfo, '?'), $params);
            $liffToken = $params['liff_token'] ?? null;
        }

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
        $liffToken = $request->input('liff_token');
        $accessToken = $request->input('access_token');
        $name = $request->input('name');

        if (empty($liffToken) || empty($accessToken) || empty($name)) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        // ユーザーのaccessTokenを検証
        $accessTokenVerifyResult = LineLoginApiService::verifyAccessToken($accessToken);

        if ($accessTokenVerifyResult['status'] === 'error') {
            return response()->json([
                'message' => $accessTokenVerifyResult['message'],
            ], 400);
        }

        // accessTokenからユーザー情報を取得
        $response = LineLoginApiService::getProfileFromAccessToken($accessToken);
        if ($response['status'] === 'error') {
            return response()->json([
                'message' => $response['message'],
            ], 400);
        }

        $lineUserId = $response['data']['userId'];
        $user = User::where('liff_one_time_token', $liffToken)->where('line_user_id', $lineUserId)->first();

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
            $liffToken = $params['liff_token'] ?? null;
            $opponentId = $params['itemId'] ?? null;
        }

        if ($request->has('liff_token') && $request->has('itemId')) {
            $opponentId = $request->query('itemId');
            $liffToken = $request->query('liff_token');
        }

        $user = User::where('liff_one_time_token', $liffToken)->first();
        $opponent = Opponent::where('id', $opponentId)->where('user_id', $user->id)->first();

        if (!$user || !$opponent) {
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        return view('liff.opponent.edit', compact('opponent'));
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $opponentId = $request->input('opponent_id');
        $liffToken = $request->input('liff_token');
        $accessToken = $request->input('access_token');
        $name = $request->input('name');

        if (empty($opponentId) || empty($name) || empty($liffToken) || empty($accessToken)) {
            \Log::debug('パラメーターが不足しています。');
            return response()->json([
                'message' => 'error',
            ], 400);
        }

        // ユーザーのaccessTokenを検証
        $accessTokenVerifyResult = LineLoginApiService::verifyAccessToken($accessToken);
        \Log::debug($accessTokenVerifyResult);
        if ($accessTokenVerifyResult['status'] === 'error') {
            return response()->json([
                'message' => $accessTokenVerifyResult['message'],
            ], 400);
        }

        // accessTokenからユーザー情報を取得
        $response = LineLoginApiService::getProfileFromAccessToken($accessToken);
        \Log::debug($response);
        if ($response['status'] === 'error') {
            return response()->json([
                'message' => $response['message'],
            ], 400);
        }

        $lineUserId = $response['data']['userId'];
        \Log::debug('ユーザーID: ' . $lineUserId);

        $user = User::where('line_user_id', $lineUserId)->where('liff_one_time_token', $liffToken)->first();
        $opponent = Opponent::where('id', $opponentId)->where('user_id', $user->id)->first();

        if (!$user || !$opponent) {
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
