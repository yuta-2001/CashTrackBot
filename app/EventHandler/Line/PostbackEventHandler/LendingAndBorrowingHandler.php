<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use App\Models\Transaction;
use App\Models\User;
use App\Trait\CarouselTemplatePaginationTrait;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ButtonsTemplate;
use LINE\Clients\MessagingApi\Model\CarouselColumn;
use LINE\Clients\MessagingApi\Model\CarouselTemplate;
use LINE\Clients\MessagingApi\Model\PostbackAction;
use LINE\Clients\MessagingApi\Model\TemplateMessage;
use LINE\Constants\ActionType;
use LINE\Constants\MessageType;
use LINE\Constants\TemplateType;
use LINE\Webhook\Model\PostbackEvent;


class LendingAndBorrowingHandler extends LineBaseEventHandler implements EventHandler
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

        if ($this->params['method'] === 'get_unsettled_lending_list') {
            $this->handleGetUnsettledLendingListMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'get_unsettled_borrowing_list') {
            $this->handleGetUnsettledBorrowingListMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'get_settled_list') {
            $this->handleGetSettledListMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'change_to_settled') {
            $this->handleChangeToSettledMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'change_to_unsettled') {
            $this->handleChangeToUnsettledMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'delete_confirmation') {
            $this->handleDeleteConfirmationMethod($replyToken);
        }

        if ($this->params['method'] === 'delete_confirmed') {
            $this->handleDeleteConfirmedMethod($replyToken, $userId);
        }
    }

    private function handleGetUnsettledLendingListMethod(string $replyToken, string $userId)
    {
        $user = User::with('transactions')->where('line_user_id', $userId)->first();

        if (!Transaction::where('user_id', $user->id)->unsettledLending()->exists()) {
            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '貸し(未清算)一覧',
                'template' => new ButtonsTemplate([
                    'type' => TemplateType::BUTTONS,
                    'title' => '貸し(未清算)一覧',
                    'text' => '現在、登録されている貸し(未清算)はありません。',
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::MESSAGE,
                            'label' => 'メニューに戻る',
                            'text' => '貸借り管理',
                        ]),
                    ],
                ]),
            ]);
        } else {
            $items = [];
            $page = (int)$this->params['page'] ?? 1;
            $unsettledLendingCount = Transaction::where('user_id', $user->id)->unsettledLending()->count();
            $prevPageBtn = null;
            $nextPageBtn = null;
            $unsettledLendings = null;

            if ($page == 1 && $unsettledLendingCount <= config('line.paginate_per_page')) {
                $unsettledLendings = Transaction::where('user_id', $user->id)->unsettledLending()->orderBy('created_at', 'DESC')->get();
            }

            if ($page == 1 && $unsettledLendingCount > config('line.paginate_per_page')) {
                $unsettledLendings = Transaction::where('user_id', $user->id)->unsettledLending()->orderBy('created_at', 'DESC')->take(5)->get();
                $nextPageBtn = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
            }

            if ($page > 1) {
                $displayedCount = config('line.paginate_per_page') * ($page - 1);
                $remainingCount = $unsettledLendingCount - $displayedCount;

                if ($remainingCount <= config('line.paginate_per_page')) {
                    $unsettledLendings = Transaction::where('user_id', $user->id)->unsettledLending()->orderBy('created_at', 'DESC')->skip($displayedCount)->take($remainingCount)->get();
                    $prevPageBtn = $this->getPrevBtn($this->params['action_type'], $this->params['method'], $page);
                } else {
                    $unsettledLendings = Transaction::where('user_id', $user->id)->unsettledLending()->orderBy('created_at', 'DESC')->skip($displayedCount)->take(config('line.paginate_per_page'))->get();
                    $prevPageBtn = $this->getPrevBtn($this->params['action_type'], $this->params['method'], $page);
                    $nextPageBtn = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
                }
            }

            if (!is_null($prevPageBtn)) {
                $items[] = $prevPageBtn;
            }

            foreach ($unsettledLendings as $unsettledLending) {
                $item = new CarouselColumn([
                    'title' => $unsettledLending->name,
                    'text' => '相手: ' . $unsettledLending->opponent->name . "\n" . '金額: ' . $unsettledLending->amount,
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '清算済みにする',
                            'data' => 'action_type=lending_and_borrowing&method=change_to_settled&item_id=' . $unsettledLending->id,
                        ]),
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '削除',
                            'data' => 'action_type=lending_and_borrowing&method=delete_confirmation&item_id=' . $unsettledLending->id,
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
                'altText' => '貸し(未清算)一覧',
                'template' => new CarouselTemplate([
                    'type' => TemplateType::CAROUSEL,
                    'columns' => $items,
                ]),
            ]);
        }

        $this->replyMessage($replyToken, $templateMessage);
    }


    private function handleGetUnsettledBorrowingListMethod(string $replyToken, string $userId)
    {
        $user = User::with('transactions')->where('line_user_id', $userId)->first();

        if (!Transaction::where('user_id', $user->id)->unsettledBorrowing()->exists()) {
            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '借り(未清算)一覧',
                'template' => new ButtonsTemplate([
                    'type' => TemplateType::BUTTONS,
                    'title' => '借り(未清算)一覧',
                    'text' => '現在、登録されている借り(未清算)はありません。',
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::MESSAGE,
                            'label' => 'メニューに戻る',
                            'text' => '貸借り管理',
                        ]),
                    ],
                ]),
            ]);
        } else {
            $items = [];
            $page = (int)$this->params['page'] ?? 1;
            $unsettledBorrowingCount = Transaction::where('user_id', $user->id)->unsettledBorrowing()->count();
            $prevPageBtn = null;
            $nextPageBtn = null;
            $unsettledBorrowings = null;
    
            if ($page == 1 && $unsettledBorrowingCount <= config('line.paginate_per_page')) {
                $unsettledBorrowings = Transaction::where('user_id', $user->id)->unsettledBorrowing()->orderBy('created_at', 'DESC')->get();
            }
    
            if ($page == 1 && $unsettledBorrowingCount > config('line.paginate_per_page')) {
                $unsettledBorrowings = Transaction::where('user_id', $user->id)->unsettledBorrowing()->orderBy('created_at', 'DESC')->take(5)->get();
                $nextPageBtn = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
            }
    
            if ($page > 1) {
                $displayedCount = config('line.paginate_per_page') * ($page - 1);
                $remainingCount = $unsettledBorrowingCount - $displayedCount;
    
                if ($remainingCount <= config('line.paginate_per_page')) {
                    $unsettledBorrowings = Transaction::where('user_id', $user->id)->unsettledBorrowing()->orderBy('created_at', 'DESC')->skip($displayedCount)->take($remainingCount)->get();
                    $prevPageBtn = $this->getPrevBtn($this->params['action_type'], $this->params['method'], $page);
                } else {
                    $unsettledBorrowings = Transaction::where('user_id', $user->id)->unsettledBorrowing()->orderBy('created_at', 'DESC')->skip($displayedCount)->take(config('line.paginate_per_page'))->get();
                    $prevPageBtn = $this->getPrevBtn($this->params['action_type'], $this->params['method'], $page);
                    $nextPageBtn = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
                }
            }

            if (!is_null($prevPageBtn)) {
                $items[] = $prevPageBtn;
            }
            foreach ($unsettledBorrowings as $unsettledBorrowing) {
                $item = new CarouselColumn([
                    'title' => $unsettledBorrowing->name,
                    'text' => '相手: ' . $unsettledBorrowing->opponent->name . "\n" . '金額: ' . $unsettledBorrowing->amount,
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '清算済みにする',
                            'data' => 'action_type=lending_and_borrowing&method=change_to_settled&item_id=' . $unsettledBorrowing->id,
                        ]),
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '削除',
                            'data' => 'action_type=lending_and_borrowing&method=delete_confirmation&item_id=' . $unsettledBorrowing->id,
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
                'altText' => '借り(未清算)一覧',
                'template' => new CarouselTemplate([
                    'type' => TemplateType::CAROUSEL,
                    'columns' => $items,
                ]),
            ]);
        }

        $this->replyMessage($replyToken, $templateMessage);
    }


    private function handleGetSettledListMethod(string $replyToken, string $userId)
    {
        $user = User::with('transactions')->where('line_user_id', $userId)->first();

        if (!Transaction::where('user_id', $user->id)->settled()->exists()) {
            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '清算済み一覧',
                'template' => new ButtonsTemplate([
                    'type' => TemplateType::BUTTONS,
                    'title' => '清算済み一覧',
                    'text' => '登録されている清算済みの記録はありません。',
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::MESSAGE,
                            'label' => 'メニューに戻る',
                            'text' => '貸借り管理',
                        ]),
                    ],
                ]),
            ]);
        } else {
            $items = [];
            $page = (int)$this->params['page'] ?? 1;
            $settledTransactionCount = Transaction::where('user_id', $user->id)->settled()->count();
            $prevPageBtn = null;
            $nextPageBtn = null;
            $settledTransactions = null;
    
            if ($page == 1 && $settledTransactionCount <= config('line.paginate_per_page')) {
                $settledTransactions = Transaction::where('user_id', $user->id)->settled()->orderBy('created_at', 'DESC')->get();
            }
    
            if ($page == 1 && $settledTransactionCount > config('line.paginate_per_page')) {
                $settledTransactions = Transaction::where('user_id', $user->id)->settled()->orderBy('created_at', 'DESC')->take(5)->get();
                $nextPageBtn = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
            }
    
            if ($page > 1) {
                $displayedCount = config('line.paginate_per_page') * ($page - 1);
                $remainingCount = $settledTransactionCount - $displayedCount;
                $prevPageBtn = $this->getPrevBtn($this->params['action_type'], $this->params['method'], $page);
    
                if ($remainingCount <= config('line.paginate_per_page')) {
                    $settledTransactions = Transaction::where('user_id', $user->id)->settled()->orderBy('created_at', 'DESC')->skip($displayedCount)->take($remainingCount)->get();
                } else {
                    $settledTransactions = Transaction::where('user_id', $user->id)->settled()->orderBy('created_at', 'DESC')->skip($displayedCount)->take(config('line.paginate_per_page'))->get();
                    $nextPageBtn = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
                }
            }
    
            if (!is_null($prevPageBtn)) {
                $items[] = $prevPageBtn;
            }

            foreach ($settledTransactions as $settledTransaction) {
                $item = new CarouselColumn([
                    'title' => $settledTransaction->name . ' [' . $settledTransaction->type_name . ']',
                    'text' => '相手: ' . $settledTransaction->opponent->name . "\n" . '金額: ' . $settledTransaction->amount,
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '未清算に戻す',
                            'data' => 'action_type=lending_and_borrowing&method=change_to_unsettled&item_id=' . $settledTransaction->id,
                        ]),
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '削除',
                            'data' => 'action_type=lending_and_borrowing&method=delete_confirmation&item_id=' . $settledTransaction->id,
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
                'altText' => '清算済み一覧',
                'template' => new CarouselTemplate([
                    'type' => TemplateType::CAROUSEL,
                    'columns' => $items,
                ]),
            ]);
        }

        $this->replyMessage($replyToken, $templateMessage);
    }


    private function handleChangeToSettledMethod(string $replyToken)
    {
        $transaction = Transaction::find($this->params['item_id']);
        $transaction->is_settled = true;
        $transaction->save();

        $templateMessage = new TemplateMessage([
            'type' => MessageType::TEMPLATE,
            'altText' => '清算済みにしました',
            'template' => new ButtonsTemplate([
                'type' => TemplateType::BUTTONS,
                'title' => '清算済みに変更しました',
                'text' => '[' . $transaction->name . ']を清算済みに変更しました。',
                'actions' => [
                    new PostbackAction([
                        'type' => ActionType::MESSAGE,
                        'label' => 'メニューに戻る',
                        'text' => '貸借り管理',
                    ]),
                ],
            ]),
        ]);

        $this->replyMessage($replyToken, $templateMessage);
    }


    private function handleChangeToUnsettledMethod(string $replyToken)
    {
        $transaction = Transaction::find($this->params['item_id']);
        $transaction->is_settled = false;
        $transaction->save();

        $templateMessage = new TemplateMessage([
            'type' => MessageType::TEMPLATE,
            'altText' => '未清算に戻しました',
            'template' => new ButtonsTemplate([
                'type' => TemplateType::BUTTONS,
                'title' => '未清算に戻しました',
                'text' => '[' . $transaction->name . ']を未清算に戻しました。',
                'actions' => [
                    new PostbackAction([
                        'type' => ActionType::MESSAGE,
                        'label' => 'メニューに戻る',
                        'text' => '貸借り管理',
                    ]),
                ],
            ]),
        ]);

        $this->replyMessage($replyToken, $templateMessage);
    }


    private function handleDeleteConfirmationMethod($replyToken)
    {
        $transactionId = $this->params['item_id'] ?? null;

        $templateMessage = new TemplateMessage([
            'type' => MessageType::TEMPLATE,
            'altText' => '確認',
            'template' => new ButtonsTemplate([
                'type' => TemplateType::BUTTONS,
                'title' => '確認',
                'text' => '一度削除を実行すると、データの復元はできません。削除を実行しますか？',
                'actions' => [
                    new PostbackAction([
                        'type' => ActionType::POSTBACK,
                        'label' => 'はい',
                        'data' => 'action_type=lending_and_borrowing&method=delete_confirmed&item_id=' . $transactionId,
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
        $transactionId = $this->params['item_id'] ?? null;

        $user = User::where('line_user_id', $userId)->first();
        $transaction = Transaction::where('id', $transactionId)->where('user_id', $user->id)->first();

        if ($transaction === null) {
            $this->replyText($replyToken, '取引記録が見つかりませんでした。');
            return;
        }

        $transaction->delete();
        $this->replyText($replyToken, '取引記録を削除しました。');
    }
}
