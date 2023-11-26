<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\Transaction\StoreRequest;
use App\Http\Requests\Transaction\UpdateRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');
        $transactions = Transaction::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();

        return TransactionResource::collection($transactions);
    }


    public function store(StoreRequest $request)
    {
        $user = $request->attributes->get('user');
        $data = $request->validated();
        $data['user_id'] = $user->id;
        $transaction = Transaction::create($data);

        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => new TransactionResource($transaction),
        ], 200);
    }


    public function update(int $id, UpdateRequest $request)
    {
        $user = $request->attributes->get('user');
        $transaction = Transaction::where('user_id', $user->id)->where('id', $id)->first();
        $data = $request->validated();

        // is_settledがfalseからtrueに変わった場合、settled_atを更新する
        if ($transaction->is_settled === false && $data['is_settled'] === true) {
            $data['settled_at'] = now();
        }

        // is_settledがtrueからfalseに変わった場合、settled_atをnullにする
        if ($transaction->is_settled === true && $data['is_settled'] === false) {
            $data['settled_at'] = null;
        }

        $transaction->update($data);

        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => new TransactionResource($transaction),
        ], 200);
    }


    public function batchSettle(Request $request)
    {
        $user = $request->attributes->get('user');
        $ids = $request->input('ids');
        Transaction::where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->where('is_settled', false)
            ->update([
                'is_settled' => true,
                'settled_at' => now(),
            ]);

        return response()->json([
            'status' => 200,
            'message' => 'success',
        ], 200);
    }


    public function delete(int $id, Request $request)
    {
        $user = $request->attributes->get('user');
        $transaction = Transaction::where('user_id', $user->id)->where('id', $id)->first();
        $transaction->delete();

        return response()->json([
            'status' => 200,
            'message' => 'success',
        ], 200);
    }


    public function batchDelete(Request $request)
    {
        $user = $request->attributes->get('user');
        $ids = $request->input('ids');
        Transaction::where('user_id', $user->id)->whereIn('id', $ids)->delete();

        return response()->json([
            'status' => 200,
            'message' => 'success',
        ], 200);
    }
}
