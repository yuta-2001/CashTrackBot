<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use App\Models\Opponent;
use App\Models\User;
use App\Service\ManageLiffTokenService;
use App\Trait\CarouselTemplatePaginationTrait;
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
    use CarouselTemplatePaginationTrait;

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

        if ($this->params['method'] === 'create') {
            $this->handleCreateMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'edit') {
            $this->handleEditMethod($replyToken, $userId);
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
                            'text' => config('line.text_from_rich_menu.opponent'),
                        ]),
                    ],
                ]),
            ]);
        } else {
            $items = [];
            $page = (int)$this->params['page'] ?? 1;
            $opponentCount = Opponent::where('user_id', $user->id)->count();
            $prevPageBtn = null;
            $nextPageBtn = null;
            $partners = null;

            if ($page == 1 && $opponentCount <= config('line.paginate_per_page')) {
                $partners = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();
            }

            if ($page == 1 && $opponentCount > config('line.paginate_per_page')) {
                $partners = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->take(5)->get();
                $nextPageBtn = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
            }

            if ($page > 1) {
                $displayedCount = config('line.paginate_per_page') * ($page - 1);
                $remainingCount = $opponentCount - $displayedCount;

                if ($remainingCount <= config('line.paginate_per_page')) {
                    $partners = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->skip($displayedCount)->take($remainingCount)->get();
                    $prevPageBtn = $this->getPrevBtn($this->params['action_type'], $this->params['method'], $page);
                } else {
                    $partners = Opponent::where('user_id', $user->id)->orderBy('created_at', 'DESC')->skip($displayedCount)->take(config('line.paginate_per_page'))->get();
                    $prevPageBtn = $this->getPrevBtn($this->params['action_type'], $this->params['method'], $page);
                    $nextPageBtn = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
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
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '編集',
                            'data' => 'action_type=opponent&method=edit&item_id=' . $partner->id,
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


    private function handleCreateMethod(string $replyToken, string $userId)
    {
        $user = User::where('line_user_id', $userId)->first();
        $liffOneTimeToken = ManageLiffTokenService::generateLiffToken($user);

        $templateMessage = new TemplateMessage([
            'type' => MessageType::TEMPLATE,
            'altText' => '相手新規作成',
            'template' => new ButtonsTemplate([
                'type' => TemplateType::BUTTONS,
                'title' => '相手新規作成',
                'text' => '「新規作成画面に進む」から相手を登録してください。',
                'actions' => [
                    new URIAction([
                        'type' => ActionType::URI,
                        'label' => '新規作成画面に進む',
                        'uri' => config('line.liff_urls.opponent_create') . '?liff_token=' . $liffOneTimeToken,
                    ]),
                    new PostbackAction([
                        'type' => ActionType::MESSAGE,
                        'label' => 'メニューに戻る',
                        'text' => config('line.text_from_rich_menu.opponent'),
                    ]),
                ],
            ]),
        ]);

        $this->replyMessage($replyToken, $templateMessage);
    }


    private function handleEditMethod(string $replyToken, string $userId)
    {
        $user = User::where('line_user_id', $userId)->first();
        $opponentId = $this->params['item_id'];
        $liffOneTimeToken = ManageLiffTokenService::generateLiffToken($user);
        
        $templateMessage = new TemplateMessage([
            'type' => MessageType::TEMPLATE,
            'altText' => '相手編集',
            'template' => new ButtonsTemplate([
                'type' => TemplateType::BUTTONS,
                'title' => '相手編集',
                'text' => '「編集画面に進む」から相手を登録してください。',
                'actions' => [
                    new URIAction([
                        'type' => ActionType::URI,
                        'label' => '編集画面に進む',
                        'uri' => config('line.liff_urls.opponent_edit') . '?itemId=' . $opponentId . '&liff_token=' . $liffOneTimeToken,
                    ]),
                    new PostbackAction([
                        'type' => ActionType::MESSAGE,
                        'label' => 'メニューに戻る',
                        'text' => config('line.text_from_rich_menu.opponent'),
                    ]),
                ],
            ]),
        ]);

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
