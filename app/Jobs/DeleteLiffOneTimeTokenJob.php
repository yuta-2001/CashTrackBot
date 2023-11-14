<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteLiffOneTimeTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $liffToken;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $liffToken)
    {
        $this->user = $user;
        $this->liffToken = $liffToken;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->user->liff_one_time_token !== $this->liffToken) {
            return;
        }
        $this->user->liff_one_time_token = null;
        $this->user->save();
        \Log::debug('キューの中' . $this->user->liff_one_time_token);
    }
}
