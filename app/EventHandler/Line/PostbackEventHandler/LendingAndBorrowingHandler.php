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

        if ($this->params['method'] === 'get_settled_list') {
            $this->handleGetSettledListMethod($replyToken, $userId);
        }
    }

    private function handleGetUnsettledLendingListMethod(string $replyToken, string $userId)
    {
        $user = User::with('transactions')->where('line_user_id', $userId)->first();
        $unsettledLendings = Transaction::where('user_id', $user->id)->unsettledLendings()->get();

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
                    'text' => '相手: ' . $unsettledLending->opponent->name . "\n" . '金額: ' . $unsettledLending->amount . "\n" . '作成日' . $unsettledLending->created_at,
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
        $unsettledBorrowings = Transaction::where('user_id', $user->id)->unsettledBorrowing()->get();

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
                    'text' => '相手: ' . $unsettledBorrowing->opponent->name . "\n" . '金額: ' . $unsettledBorrowing->amount . "\n" . '作成日' . $unsettledBorrowing->created_at,
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


    private function handleGetSettledListMethod(string $replyToken, string $userId)
    {
        $user = User::with('transactions')->where('line_user_id', $userId)->first();
        $settledTransactions = Transaction::where('user_id', $user->id)->settled()->get();

        if ($settledTransactions->isEmpty()) {
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
            foreach ($settledTransactions as $settledTransaction) {
                $item = new CarouselColumn([
                    'title' => $settledTransactions->name,
                    'text' => '相手: ' . $settledTransaction->opponent->name . "\n" . '金額: ' . $settledTransaction->amount . "\n" . '作成日' . $settledTransaction->created_at,
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
}
