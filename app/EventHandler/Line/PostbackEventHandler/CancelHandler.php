<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\Message;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Constants\MessageType;
use LINE\Webhook\Model\PostbackEvent;


class CancelHandler implements EventHandler
{
    private $bot;
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

    private function replyMessage(string $replyToken, Message $message)
    {
        $request = new ReplyMessageRequest([
            'replyToken' => $replyToken,
            'messages' => [$message],
        ]);

        try {
            $this->bot->replyMessage($request);
        } catch (\LINE\Clients\MessagingApi\ApiException $e) {
            \Log::error('BODY:' . $e->getResponseBody());
            throw $e;
        }
    }

    private function replyText(string $replyToken, string $text)
    {
        $textMessage = (new TextMessage(['text' => $text, 'type' => MessageType::TEXT]));
        return $this->replyMessage($replyToken, $textMessage);
    }
}