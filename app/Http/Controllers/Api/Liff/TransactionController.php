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
        $transactions = Transaction::where('user_id', $user->id)->get();

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

    public function update(UpdateRequest $request, int $id)
    {
        $user = $request->attributes->get('user');
        $transaction = Transaction::where('user_id', $user->id)->where('id', $id)->first();
        $data = $request->validated();
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
        Transaction::where('user_id', $user->id)->whereIn('id', $ids)->update(['is_settled' => true]);

        return response()->json([
            'status' => 200,
            'message' => 'success',
        ], 200);
    }

    public function delete(Request $request, int $id)
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
