<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Webhook\Model\PostbackEvent;


class CancelHandler extends LineBaseEventHandler implements EventHandler
{
    protected $bot;
    private $event;

    public function __construct(MessagingApiApi $bot, PostbackEvent $event)
    {
        $this->bot = $bot;
        $this->event = $event;
    }

    public function handle()
    {
        $replyToken = $this->event->getReplyToken();

        $this->replyText($replyToken, 'アクションをキャンセルしました。');
    }
}
