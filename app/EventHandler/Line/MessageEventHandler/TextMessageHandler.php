<?php

namespace App\EventHandler\Line\MessageEventHandler;

use App\Models\Opponent;
use App\Models\User;
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
        $source = $this->event->getSource();
        $userId = $source->getUserId();
        $user = User::where('line_user_id', $userId)->first();

        switch ($text) {
            case config('line.text_from_rich_menu.lending_and_borrowing'):
                $opponents = Opponent::where('user_id', $user->id)->get();
                if (!$opponents->isEmpty()) {
                    $templateMessage = new TemplateMessage([
                        'type' => MessageType::TEMPLATE,
                        'altText' => '貸借り管理',
                        'template' => new ButtonsTemplate([
                            'type' => TemplateType::BUTTONS,
                            'title' => '貸借り管理',
                            'text' => '下のボタンから貸し借り管理ページへ遷移します',
                            'actions' => [
                                new URIAction([
                                    'type' => ActionType::URI,
                                    'label' => '貸し借り管理ページへ',
                                    'uri' => config('line.liff_urls.lending_and_borrowing'),
                                ]),
                            ],
                        ]),
                    ]);

                    $this->replyMessage($replyToken, $templateMessage);
                } else {
                    $this->replyText($replyToken, '貸し借りを管理するには、相手を先に登録してください。');
                }

                break;

            case config('line.text_from_rich_menu.opponent'):
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
                                'data' => 'action_type=opponent&method=get_list&page=1',
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '新規作成',
                                'data' => 'action_type=opponent&method=create',
                            ]),
                        ],
                    ]),
                ]);

                $this->replyMessage($replyToken, $templateMessage);
                break;

            case config('line.text_from_rich_menu.how_to_use'):
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

            default:
                return 'recieve success message';
                break;
        }
    }
}
