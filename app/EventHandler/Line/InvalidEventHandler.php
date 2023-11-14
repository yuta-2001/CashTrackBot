<?php

namespace App\EventHandler\Line;

use App\EventHandler\EventHandler;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Webhook\Model\Event;

class InvalidEventHandler extends LineBaseEventHandler implements EventHandler
{
    protected $bot;
    private $event;

    public function __construct(MessagingApiApi $bot, Event $event)
    {
        $this->bot = $bot;
        $this->event = $event;
    }

    public function handle()
    {
        $this->replyText($this->event->getReplyToken(), 'そのアクションには対応していません。');
    }
}