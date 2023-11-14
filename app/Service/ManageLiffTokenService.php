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

        DeleteLiffOneTimeTokenJob::dispatch($user)->delay(now()->addMinutes(config('line.liff_token_expiration_minutes')));

        return $liffToken;
    }
}
