<?php

namespace App\Http\Controllers\Api;

use App\EventHandler\Line\FollowEventHandler;
use App\EventHandler\Line\InvalidEventHandler;
use App\EventHandler\Line\MessageEventHandler\TextMessageHandler;
use App\EventHandler\Line\PostbackEventHandler\CancelHandler;
use App\EventHandler\Line\PostbackEventHandler\ExplainHandler;
use App\EventHandler\Line\PostbackEventHandler\LendingAndBorrowingHandler;
use App\EventHandler\Line\PostbackEventHandler\OpponentHandler;
use App\EventHandler\Line\UnFollowEventHandler;
use App\Http\Controllers\Controller;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Parser\Exception\InvalidEventRequestException;
use LINE\Parser\Exception\InvalidSignatureException;
use LINE\Webhook\Model\FollowEvent;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\PostbackEvent;
use LINE\Webhook\Model\TextMessageContent;
use LINE\Webhook\Model\UnfollowEvent;
use Illuminate\Http\Request;

class LineBotController extends Controller
{
    /**
     * LINEプラットフォームからのリクエストを受け取り適切なリプライを返却する。
     * このメソッドはLINEプラットフォームからのリクエストを受け取り、ハンドラーに処理を委譲する。
     * 
     * @param MessagingApiApi $bot
     * @param Request $request
     */
    public function callback(MessagingApiApi $bot, Request $request)
    {
        // LINEプラットフォームからのリクエストかを検証する
        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
        if (empty($signature)) {
            return response('Bad Request', 400);
        }
        try {
            $secret = config('line.channel_secret');
            $parsedEvents = EventRequestParser::parseEventRequest($request->getContent(), $secret, $signature);
        } catch (InvalidSignatureException $e) {
            return response('Invalid signature', 400);
        } catch (InvalidEventRequestException $e) {
            return response('Invalid event request', 400);
        }

        // リクエストされたイベントをもとに処理をハンドラーに委譲する
        foreach ($parsedEvents->getEvents() as $event) {
            $handler = null;
            switch (true) {
                // フォローイベント
                case $event instanceof FollowEvent:
                    $handler = new FollowEventHandler($bot, $event);
                    break;

                // アンフォローイベント
                case $event instanceof UnfollowEvent:
                    $handler = new UnFollowEventHandler($bot, $event);
                    break;

                // メッセージイベント
                // リッチメニュークリック時に発火する
                case $event instanceof MessageEvent:
                    $message = $event->getMessage();
                    if ($message instanceof TextMessageContent) {
                        $text = $message->getText();
                        if (in_array($text, array_values(config('line.text_from_rich_menu'))) || in_array($text, array_values(config('line.text_from_liff')))) {
                            $handler = new TextMessageHandler($bot, $event);
                        }
                    }
                    break;

                // テンプレートメニューをクリックした時に発火する
                case $event instanceof PostbackEvent:
                    $postback = $event->getPostback();
                    $data = $postback->getData();
                    parse_str($data, $params);
                    if ($params['action_type'] === 'lending_and_borrowing') {
                        $handler = new LendingAndBorrowingHandler($bot, $event, $params);
                    }

                    if ($params['action_type'] === 'opponent') {
                        $handler = new OpponentHandler($bot, $event, $params);
                    }

                    if ($params['action_type'] === 'explanation') {
                        $handler = new ExplainHandler($bot, $event, $params);
                    }

                    if ($params['action_type'] === 'cancel') {
                        $handler = new CancelHandler($bot, $event);
                    }

                    break;
            }

            if (is_null($handler)) {
                $handler = new InvalidEventHandler($bot, $event);
            }

            $handler->handle();
        }
    }
}
