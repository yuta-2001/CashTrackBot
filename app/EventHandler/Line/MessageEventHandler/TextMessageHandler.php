<?php

namespace App\EventHandler\Line\MessageEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ButtonsTemplate;
use LINE\Clients\MessagingApi\Model\PostbackAction;
use LINE\Clients\MessagingApi\Model\TemplateMessage;
use LINE\Clients\MessagingApi\Model\URIAction;
use LINE\Constants\ActionType;
use LINE\Constants\MessageType;
use LINE\Constants\TemplateType;
use LINE\Webhook\Model\MessageEvent;


class TextMessageHandler extends LineBaseEventHandler implements EventHandler
{
    protected $bot;
    private $event;
    private $textMessage;

    public function __construct(MessagingApiApi $bot, MessageEvent $event)
    {
        $this->bot = $bot;
        $this->event = $event;
        $this->textMessage = $event->getMessage();
    }

    public function handle()
    {
        $text = $this->textMessage->getText();
        $replyToken = $this->event->getReplyToken();

        switch ($text) {
            case '相手管理':
                $templateMessage = new TemplateMessage([
                    'type' => MessageType::TEMPLATE,
                    'altText' => '相手管理メニュー',
                    'template' => new ButtonsTemplate([
                        'type' => TemplateType::BUTTONS,
                        'title' => '相手管理メニュー',
                        'text' => 'メニューを選択してください。',
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '相手一覧',
                                'data' => 'action_type=opponent&method=get_list',
                            ]),
                            new URIAction([
                                'type' => ActionType::URI,
                                'label' => '新規作成',
                                'uri' => config('line.liff_urls.opponent_create'),
                            ]),
                        ],
                    ]),
                ]);

                $this->replyMessage($replyToken, $templateMessage);
                break;
        }
    }
}
