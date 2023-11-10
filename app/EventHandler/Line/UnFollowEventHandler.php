<?php

namespace App\EventHandler\Line;

use App\EventHandler\EventHandler;
use App\Models\User;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Webhook\Model\UnFollowEvent;

class UnFollowEventHandler implements EventHandler
{
    private $bot;
    private $event;

    public function __construct(MessagingApiApi $bot, UnFollowEvent $event)
    {
        $this->bot = $bot;
        $this->event = $event;
    }

    public function handle()
    {
        $source = $this->event->getSource();
        $userId = $source->getUserId();

        User::where('line_user_id', $userId)->delete();

        \Log::info('アカウント削除実行');
        \Log::info('userId:' . $userId);
    }
}