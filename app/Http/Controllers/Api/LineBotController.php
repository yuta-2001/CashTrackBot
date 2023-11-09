<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Parser\Exception\InvalidEventRequestException;
use LINE\Parser\Exception\InvalidSignatureException;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;
use Illuminate\Http\Request;

class LineBotController extends Controller
{
    /**
     * @param MessagingApiApi $bot
     * @param Request $request
     * 
     */
    public function callback(Request $request)
    {
        $client = new Client();
        $config = new Configuration();
        $config->setAccessToken(config('line.channel_access_token'));
        $bot = new MessagingApiApi(
            client: $client,
            config: $config,
        );

        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);

        if (empty($signature)) {
            return response('Bad Request', 400);
        }

        // Check request with signature and parse request
        try {
            $secret = config('line.channel_secret');
            $parsedEvents = EventRequestParser::parseEventRequest($request->getContent(), $secret, $signature);
        } catch (InvalidSignatureException $e) {
            return response('Invalid signature', 400);
        } catch (InvalidEventRequestException $e) {
            return response('Invalid event request', 400);
        }

        foreach ($parsedEvents->getEvents() as $event) {
            $reply_token = $event->getReplyToken();
    
            switch (true){
                // メッセージイベント
                case $event instanceof MessageEvent:
                    $message = $event->getMessage();

                    if ($message instanceof TextMessageContent) {
                        $replyText = $message->getText();
                        $bot->replyMessage(new ReplyMessageRequest([
                            'replyToken' => $reply_token,
                            'messages' => [
                                (new TextMessage(['text' => $replyText]))->setType('text'),
                            ],
                        ]));
                    }

                    break;

                default:
                    // $body = $event->getEventBody();
            }
        }

    }
}
