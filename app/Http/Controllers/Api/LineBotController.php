<?php

namespace App\Http\Controllers\Api;

use App\EventHandler\Line\FollowEventHandler;
use App\EventHandler\Line\UnFollowEventHandler;
use App\Http\Controllers\Controller;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Parser\Exception\InvalidEventRequestException;
use LINE\Parser\Exception\InvalidSignatureException;
use LINE\Webhook\Model\FollowEvent;
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

                default:
                    // $body = $event->getEventBody();
            }

            if ($handler !== null) {
                $handler->handle();
            }
        }
    }
}
