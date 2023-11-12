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
            case '貸借り管理':
                $templateMessage = new TemplateMessage([
                    'type' => MessageType::TEMPLATE,
                    'altText' => '貸借り管理メニュー',
                    'template' => new ButtonsTemplate([
                        'type' => TemplateType::BUTTONS,
                        'title' => '貸借り管理メニュー',
                        'text' => 'メニューを選択してください。',
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '貸し(未清算)',
                                'data' => 'action_type=lending_and_borrowing&method=get_unsettled_lending_list',
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '借り(未清算)',
                                'data' => 'action_type=lending_and_borrowing&method=get_unsettled_borrowing_list',
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '全て',
                                'data' => 'action_type=lending_and_borrowing&method=get_all_list',
                            ]),
                            new URIAction([
                                'type' => ActionType::URI,
                                'label' => '新規作成',
                                'uri' => config('line.liff_urls.lending_and_borrowing_create') . '?line_user_id=' . $this->event->getSource()->getUserId(),
                            ]),
                        ],
                    ]),
                ]);

                $this->replyMessage($replyToken, $templateMessage);
                break;

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

            case '使い方':
                $templateMessage = new TemplateMessage([
                    'type' => MessageType::TEMPLATE,
                    'altText' => '使い方メニュー',
                    'template' => new ButtonsTemplate([
                        'type' => TemplateType::BUTTONS,
                        'title' => '使い方メニュー',
                        'text' => 'メニューを選択してください。',
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => config('line.explanation.overview.title'),
                                'data' => 'action_type=explanation&method=overview',
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => config('line.explanation.how_to_manage_lending_and_borrowing.title'),
                                'data' => 'action_type=explanation&method=how_to_manage_lending_and_borrowing',
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => config('line.explanation.how_to_manage_opponent.title'),
                                'data' => 'action_type=explanation&method=how_to_manage_opponent',
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => config('line.explanation.caution.title'),
                                'data' => 'action_type=explanation&method=caution',
                            ]),
                        ],
                    ]),
                ]);

                $this->replyMessage($replyToken, $templateMessage);
                break;
        }
    }
}
