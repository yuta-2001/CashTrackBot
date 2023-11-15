<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use App\Models\Opponent;
use App\Models\User;
use App\Service\ManageLiffTokenService;
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

        if ($this->params['method'] === 'get_list') {
            $this->handleGetListMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'delete_confirmation') {
            $this->handleDeleteConfirmationMethod($replyToken);
        }

        if ($this->params['method'] === 'delete_confirmed') {
            $this->handleDeleteConfirmedMethod($replyToken, $userId);
        }
    }


    private function handleGetListMethod(string $replyToken, string $userId)
    {
        $user = User::with('opponents')->where('line_user_id', $userId)->first();

        if (!Opponent::where('user_id', $user->id)->exists()) {
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
            $liffOneTimeToken = ManageLiffTokenService::generateLiffToken($user);
            $page = (int)$this->params['page'] ?? 1;
            $opponentCount = Opponent::where('user_id', $user->id)->count();
            $prevPageBtn = null;
            $nextPageBtn = null;
            $partners = null;

            // 1ページ目かつ相手が10件以下の場合は全件表示
            if ($page == 1 && $opponentCount <= 10) {
                $partners = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();
            }

            // 1ページ目かつ相手が11件以上の場合は9件表示+次の10件を表示ボタンを表示
            if ($page == 1 && $opponentCount > 10) {
                $partners = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->take(9)->get();
                $nextPageBtn = new CarouselColumn([
                    'title' => '次のページを表示',
                    'text' => 'next',
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '表示',
                            'data' => 'action_type=opponent&method=get_list&page=' . ($page + 1),
                        ]),
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => 'キャンセル',
                            'data' => 'action_type=cancel',
                        ]),
                    ],
                ]);
            }

            // 2ページ目以降はこれまで表示した個数を計算し、最後のページかどうかを判定する
            // 最後の場合は次へボタンを表示しない
            // 最後ではない場合は前のページを表示ボタンと次のページを表示ボタンを表示する
            if ($page > 1) {
                $displayedCount = 8 * ($page - 2) + 9;
                $remainingCount = $opponentCount - $displayedCount;

                if ($remainingCount < 10) {
                    $partners = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->skip($displayedCount)->take($remainingCount)->get();
                    $prevPageBtn = new CarouselColumn([
                        'title' => '前のページを表示',
                        'text' => 'prev',
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '表示',
                                'data' => 'action_type=opponent&method=get_list&page=' . ($page - 1),
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => 'キャンセル',
                                'data' => 'action_type=cancel',
                            ]),
                        ],
                    ]);
                } else {
                    $partners = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->skip($displayedCount)->take(8)->get();
                    $prevPageBtn = new CarouselColumn([
                        'title' => '前のページを表示',
                        'text' => 'prev',
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '表示',
                                'data' => 'action_type=opponent&method=get_list&page=' . ($page - 1),
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => 'キャンセル',
                                'data' => 'action_type=cancel',
                            ]),
                        ],
                    ]);
                    $nextPageBtn = new CarouselColumn([
                        'title' => '次のページを表示',
                        'text' => 'next',
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '表示',
                                'data' => 'action_type=opponent&method=get_list&page=' . ($page + 1),
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => 'キャンセル',
                                'data' => 'action_type=cancel',
                            ]),
                        ],
                    ]);
                }
            }

            if (!is_null($prevPageBtn)) {
                $items[] = $prevPageBtn;
            }

            foreach ($partners as $partner) {
                $item = new CarouselColumn([
                    'title' => $partner->name,
                    'text' => '作成日' . $partner->created_at,
                    'actions' => [
                        new URIAction([
                            'type' => ActionType::URI,
                            'label' => '編集',
                            'uri' => config('line.liff_urls.opponent_edit') . '?itemId=' . $partner->id . '&liff_token=' . $liffOneTimeToken,
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

            if (!is_null($nextPageBtn)) {
                $items[] = $nextPageBtn;
            }

            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '相手一覧',
                'template' => new CarouselTemplate([
                    'type' => TemplateType::CAROUSEL,
                    'columns' => $items,
                ]),
            ]);
        }

        $this->replyMessage($replyToken, $templateMessage);
    }

    private function handleDeleteConfirmationMethod(string $replyToken)
    {
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

    private function handleDeleteConfirmedMethod(string $replyToken, string $userId)
    {
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
