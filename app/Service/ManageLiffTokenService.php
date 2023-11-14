<?php

namespace App\Service;

use App\Jobs\DeleteLiffOneTimeTokenJob;
use App\Models\User;

class ManageLiffTokenService
{
    public static function generateLiffToken(User $user)
    {
        $liffToken = \Str::random(32);
        $user->liff_one_time_token = $liffToken;
        $user->save();
        \Log::debug($liffToken);
        \Log::debug($user->liff_one_time_token);

        \Log::debug(config('line.liff_token_expiration_minutes'));
        DeleteLiffOneTimeTokenJob::dispatch($user, $liffToken)->delay(now()->addMinutes(config('line.liff_token_expiration_minutes')));
        \Log::debug($user->liff_one_time_token);

        return $liffToken;
    }
}
