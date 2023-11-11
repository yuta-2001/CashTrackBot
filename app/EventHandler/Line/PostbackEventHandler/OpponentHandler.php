<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use App\Models\Opponent;
use App\Models\User;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ButtonsTemplate;
use LINE\Clients\MessagingApi\Model\CarouselColumn;
use LINE\Clients\MessagingApi\Model\CarouselTemplate;
use LINE\Clients\MessagingApi\Model\PostbackAction;
use LINE\Clients\MessagingApi\Model\TemplateMessage;
use LINE\Clients\MessagingApi\Model\URIAction;
use LINE\Constants\ActionType;
use LINE\Constants\MessageType;
use LINE\Constants\TemplateType;
use LINE\Webhook\Model\PostbackEvent;

class OpponentHandler extends LineBaseEventHandler implements EventHandler
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
        $replyToken = $this->event->getReplyToken();
        $source = $this->event->getSource();
        $userId = $source->getUserId();

        // 相手の一覧を表示する
        if ($this->params['method'] === 'get_list') {
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
                                'uri' => config('line.liff_urls.opponent_edit') . '?itemId=' . $partner->id,
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '削除',
                                'data' => 'action_type=opponent&method=delete_confirmation&item_id=' . $partner->id,
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
        }


        // 相手の削除を確認する
        if ($this->params['method'] === 'delete_confirmation') {
            $opponentId = $this->params['item_id'] ?? null;

            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '確認',
                'template' => new ButtonsTemplate([
                    'type' => TemplateType::BUTTONS,
                    'title' => '確認',
                    'text' => 'この相手を削除した場合、該当する相手との貸借り情報も削除されます。削除を実行しますか？',
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => 'はい',
                            'data' => 'action_type=opponent&method=delete_confirmed&item_id=' . $opponentId,
                        ]),
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => 'キャンセル',
                            'data' => 'action_type=cancel',
                        ]),
                    ],
                ]),
            ]);

            $this->replyMessage($replyToken, $templateMessage);
        }


        // 相手の削除を実行する
        if ($this->params['method'] === 'delete_confirmed') {
            $opponentId = $this->params['item_id'] ?? null;

            $user = User::with('opponents')->where('line_user_id', $userId)->first();
            $opponent = Opponent::where('id', $opponentId)->where('user_id', $user->id)->first();

            if ($opponent === null) {
                $this->replyText($replyToken, '相手が見つかりませんでした。');
                return;
            }

            $opponent->delete();
            $this->replyText($replyToken, '相手を削除しました。');
        }
    }
}
