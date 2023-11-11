<?php

namespace App\EventHandler\Line;

use App\EventHandler\EventHandler;
use App\Models\User;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ButtonsTemplate;
use LINE\Clients\MessagingApi\Model\CarouselColumn;
use LINE\Clients\MessagingApi\Model\CarouselTemplate;
use LINE\Clients\MessagingApi\Model\Message;
use LINE\Clients\MessagingApi\Model\PostbackAction;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\TemplateMessage;
use LINE\Clients\MessagingApi\Model\URIAction;
use LINE\Constants\ActionType;
use LINE\Constants\MessageType;
use LINE\Constants\TemplateType;
use LINE\Webhook\Model\PostbackEvent;

class PostbackEventHandler implements EventHandler
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
        $postback = $this->event->getPostback();
        $data = $postback->getData();
        $replyToken = $this->event->getReplyToken();
        $source = $this->event->getSource();
        $userId = $source->getUserId();

        switch ($data) {
            case 'action=partner_list':
                $user = User::with('opponents')->where('line_user_id', $userId)->first();
                $partners = $user->opponents;

                if ($partners->isEmpty()) {
                    $templateMessage = new TemplateMessage([
                        'type' => MessageType::TEMPLATE,
                        'altText' => '相手一覧',
                        'template' => new ButtonsTemplate([
                            'type' => TemplateType::BUTTONS,
                            'title' => '相手一覧',
                            'text' => '相手が登録されていません。',
                            'actions' => [
                                new PostbackAction([
                                    'type' => ActionType::MESSAGE,
                                    'label' => 'メニューに戻る',
                                    'text' => '相手管理',
                                ]),
                            ],
                        ]),
                    ]);
                } else {
                    $items = [];
                    foreach ($partners as $partner) {
                        $item = new CarouselColumn([
                            'title' => $partner->name,
                            'text' => '作成日' . $partner->created_at,
                            'actions' => [
                                new URIAction([
                                    'type' => ActionType::URI,
                                    'label' => '編集',
                                    'uri' => 'https://line.me',
                                ]),
                                new PostbackAction([
                                    'type' => ActionType::POSTBACK,
                                    'label' => '削除',
                                    'data' => 'action=partner_delete&itemid=' . $partner->id,
                                ]),
                                new PostbackAction([
                                    'type' => ActionType::MESSAGE,
                                    'label' => 'メニューに戻る',
                                    'text' => '相手管理',
                                ]),
                            ],
                        ]);
                        $items[] = $item;
                    }

                    $templateMessage = new TemplateMessage([
                        'type' => MessageType::TEMPLATE,
                        'altText' => 'Button alt text',
                        'template' => new CarouselTemplate([
                            'type' => TemplateType::CAROUSEL,
                            'columns' => $items,
                        ]),
                    ]);
                }

                $this->replyMessage($replyToken, $templateMessage);
                break;
        }
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
}
