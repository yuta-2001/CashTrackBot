<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\Transaction\GenerateBillRequest;
use App\Http\Requests\Transaction\StoreRequest;
use App\Http\Requests\Transaction\UpdateRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;


class TransactionController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $user = $request->attributes->get('user');
        $transactions = Transaction::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();

        return TransactionResource::collection($transactions);
    }


    public function store(StoreRequest $request): \Illuminate\Http\JsonResponse
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


    public function update(int $id, UpdateRequest $request): \Illuminate\Http\JsonResponse
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


    public function batchSettle(Request $request): \Illuminate\Http\JsonResponse
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


    public function delete(int $id, Request $request): \Illuminate\Http\JsonResponse
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


    public function generateBill(GenerateBillRequest $request): \Illuminate\Http\JsonResponse
    {
        $opponentId = $request->input('opponent_id');
        $opponentName = $request->input('opponent_name');
        $totalAmount = $request->input('total_amount');
        $borrowAmount = $request->input('borrow_amount');
        $lendAmount = $request->input('lend_amount');
        $createdAt = now()->format('Y/m/d');
        $fileCreatedAt = now()->format('Ymd');

        $userId = $request->attributes->get('user')->id;

        // 情報をファイル名に含める
        // => 同じ情報が渡ってきた場合、すでに生成済みの画像を返す
        $filename = preg_replace('/[ \\/:*?"<>|]+/', '', $opponentId . '_' .  $opponentName . '_' . $totalAmount . '_' . $borrowAmount . '_' . $lendAmount . '_' . $fileCreatedAt . '.png');

        if (Storage::disk('public')->exists('bills/' . $userId . '/' . $filename)) {
            return response()->json([
                'status' => 200,
                'message' => 'success',
                'data' => [
                    'url' => Storage::disk('public')->url('bills/' . $userId . '/' . $filename),
                ],
            ], 200);
        };

        $image = ImageManager::gd()->read(public_path('template.png'));

        $image->text($opponentName, 210, 238, function ($font) {
            $font->filename(public_path('fonts/NotoSansJP-SemiBold.ttf'));
            $font->color('#000000');
            $font->align('center');
            $font->size(40);
        });

        $image->text($createdAt, 905, 178, function ($font) {
            $font->filename(public_path('fonts/NotoSansJP-Medium.ttf'));
            $font->color('#000000');
            $font->align('center');
            $font->size(22);
        });

        $image->text('¥ ' . $totalAmount, 670, 378, function ($font) {
            $font->filename(public_path('fonts/NotoSansJP-SemiBold.ttf'));
            $font->color('#000000');
            $font->align('center');
            $font->size(48);
        });

        $image->text('¥ ' . $borrowAmount, 645, 590, function ($font) {
            $font->filename(public_path('fonts/NotoSansJP-Medium.ttf'));
            $font->color('#000000');
            $font->align('center');
            $font->size(30);
        });

        $image->text('¥ ' . $lendAmount, 645, 650, function ($font) {
            $font->filename(public_path('fonts/NotoSansJP-Medium.ttf'));
            $font->color('#000000');
            $font->align('center');
            $font->size(30);
        });

        $storeFolderPath = storage_path('app/public/bills/' . $userId);

        // フォルダがなければ作成する
        if (!file_exists($storeFolderPath)) {
            mkdir($storeFolderPath, 0755, true);
        }

        $image->toPng()->save($storeFolderPath . '/' . $filename);


        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => [
                'url' => Storage::disk('public')->url('bills/' . $userId . '/' . $filename),
            ],
        ], 200);
    }
}
