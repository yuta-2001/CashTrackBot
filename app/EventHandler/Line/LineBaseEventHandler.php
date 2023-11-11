<?php

namespace App\EventHandler\Line;

use LINE\Clients\MessagingApi\Model\Message;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Constants\MessageType;

class LineBaseEventHandler
{
    protected function replyMessage(string $replyToken, Message $message)
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

    protected function replyText(string $replyToken, string $text)
    {
        $textMessage = (new TextMessage(['text' => $text, 'type' => MessageType::TEXT]));
        return $this->replyMessage($replyToken, $textMessage);
    }
}
