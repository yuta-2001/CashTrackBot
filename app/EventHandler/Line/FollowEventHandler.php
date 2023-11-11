<?php

namespace App\EventHandler\Line;

use App\EventHandler\EventHandler;
use App\Models\User;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Webhook\Model\FollowEvent;

class FollowEventHandler extends LineBaseEventHandler implements EventHandler
{
    protected $bot;
    private $event;

    public function __construct(MessagingApiApi $bot, FollowEvent $event)
    {
        $this->bot = $bot;
        $this->event = $event;
    }

    public function handle()
    {
        $source = $this->event->getSource();
        $userId = $source->getUserId();

        User::create([
            'line_user_id' => $userId,
        ]);

        $this->replyText($this->event->getReplyToken(), '友達追加ありがとう！');
    }
}