<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Webhook\Model\PostbackEvent;


class ExplainHandler extends LineBaseEventHandler implements EventHandler
{
    protected $bot;
    private $event;
    private $params;

    public function __construct(MessagingApiApi $bot, PostbackEvent $event, array $params)
    {
        $this->bot = $bot;
        $this->event = $event;
        $this->params = $params;
    }

    public function handle()
    {
        $method = $this->params['method'];
        $title = config("line.explanation.{$method}.title");
        $explanation = config("line.explanation.{$method}.content");

        $message = $title . "\n\n" . $explanation;

        $this->replyText($this->event->getReplyToken(), $message);
    }
}

