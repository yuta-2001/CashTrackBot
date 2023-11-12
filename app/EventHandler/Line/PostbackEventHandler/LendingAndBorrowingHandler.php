<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use App\Models\Transaction;
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


class LendingAndBorrowingHandler extends LineBaseEventHandler implements EventHandler
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

        if ($this->params['method'] === 'get_unsettled_lending_list') {
            $this->handleGetUnsettledLendingListMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'get_unsettled_borrowing_list') {
            $this->handleGetUnsettledBorrowingListMethod($replyToken, $userId);
        }

        if ($this->params['method'] === 'get_all_list') {
            $this->handleGetAllListMethod($replyToken, $userId);
        }
    }

    private function handleGetUnsettledLendingListMethod(string $replyToken, string $userId)
    {
        $user = User::with('transactions')->where('line_user_id', $userId)->first();
        $unsettledLendings = Transaction::where('user_id', $user->id)->where('is_settled', false)->where('type', Transaction::TYPE_LENDING)->get();

        if ($unsettledLendings->isEmpty()) {
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
            foreach ($unsettledLendings as $unsettledLending) {
                $item = new CarouselColumn([
                    'title' => $unsettledLending->name,
                    'text' => '作成日' . $unsettledLending->created_at,
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
        $unsettledBorrowings = Transaction::where('user_id', $user->id)->where('is_settled', false)->where('type', Transaction::TYPE_BORROWING)->get();

        if ($unsettledBorrowings->isEmpty()) {
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
            foreach ($unsettledBorrowings as $unsettledBorrowing) {
                $item = new CarouselColumn([
                    'title' => $unsettledBorrowing->name,
                    'text' => '作成日' . $unsettledBorrowing->created_at,
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


    private function handleGetAllListMethod(string $replyToken, string $userId)
    {
        $user = User::with('transactions')->where('line_user_id', $userId)->first();
        $transactions = Transaction::where('user_id', $user->id)->get();

        if ($transactions->isEmpty()) {
            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '貸借り一覧',
                'template' => new ButtonsTemplate([
                    'type' => TemplateType::BUTTONS,
                    'title' => '貸借り一覧',
                    'text' => '登録されている貸借り記録はありません。',
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
            foreach ($transactions as $transaction) {
                if ($transaction->type === Transaction::TYPE_LENDING && !$transaction->is_settled) {
                    $item = new CarouselColumn([
                        'title' => $transaction->name,
                        'text' => '作成日' . $transaction->created_at,
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '清算済みにする',
                                'data' => 'action_type=lending_and_borrowing&method=change_to_settled&item_id=' . $transaction->id,
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '削除',
                                'data' => 'action_type=lending_and_borrowing&method=delete_confirmation&item_id=' . $transaction->id,
                            ]),
                        ],
                    ]);
                } else if ($transaction->type === Transaction::TYPE_BORROWING && !$transaction->is_settled) {
                    $item = new CarouselColumn([
                        'title' => $transaction->name,
                        'text' => '作成日' . $transaction->created_at,
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '清算済みにする',
                                'data' => 'action_type=lending_and_borrowing&method=change_to_settled&item_id=' . $transaction->id,
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '削除',
                                'data' => 'action_type=lending_and_borrowing&method=delete_confirmation&item_id=' . $transaction->id,
                            ]),
                        ],
                    ]);
                } else {
                    $item = new CarouselColumn([
                        'title' => $transaction->name,
                        'text' => '作成日' . $transaction->created_at,
                        'actions' => [
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '未清算に戻す',
                                'data' => 'action_type=lending_and_borrowing&method=change_to_unsettled&item_id=' . $transaction->id,
                            ]),
                            new PostbackAction([
                                'type' => ActionType::POSTBACK,
                                'label' => '削除',
                                'data' => 'action_type=lending_and_borrowing&method=delete_confirmation&item_id=' . $transaction->id,
                            ]),
                        ],
                    ]);
                }

                $items[] = $item;
            }

            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '貸借り一覧',
                'template' => new CarouselTemplate([
                    'type' => TemplateType::CAROUSEL,
                    'columns' => $items,
                ]),
            ]);
        }

        $this->replyMessage($replyToken, $templateMessage);
    }
}
