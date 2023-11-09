<?php

namespace App\EventHandler\Line;

use App\EventHandler\EventHandler;
use App\Models\User;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Constants\MessageType;
use LINE\Webhook\Model\FollowEvent;

class FollowEventHandler implements EventHandler
{
    private $bot;
    private $event;

    public function __construct(MessagingApiApi $bot, FollowEvent $event)
    {
        $this->bot = $bot;
        $this->event = $event;
    }

    public function handle()
    {
        $userId = $this->event->getSource()['userId'];

        User::create([
            'line_user_id' => $userId,
        ]);

        $request = new ReplyMessageRequest([
            'replyToken' => $this->event->getReplyToken(),
            'messages' => [
                new TextMessage([
                    'type' => MessageType::TEXT,
                    'text' => '友達追加ありがとう！'
                ]),
            ],
        ]);

        $this->bot->replyMessage($request);
    }
}