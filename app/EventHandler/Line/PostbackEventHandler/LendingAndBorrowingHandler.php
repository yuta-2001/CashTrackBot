<?php

namespace App\EventHandler\Line\PostbackEventHandler;

use App\EventHandler\EventHandler;
use App\EventHandler\Line\LineBaseEventHandler;
use App\Models\Opponent;
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

        if ($this->params['method'] === 'get_unsettled_list') {
            $this->handleGetUnsettledList($replyToken, $userId);
        }

        if ($this->params['method'] === 'change_to_settled') {
            $this->handleChangeToSettledMethod($replyToken);
        }
    }

    private function handleGetUnsettledList(string $replyToken, string $userId)
    {
        $user = User::where('line_user_id', $userId)->first();
        $page = (int) ($this->params['page'] ?? 1);
        $perPage = config('line.paginate_per_page');

        $totalOpponentsCount = Opponent::where('user_id', $user->id)
            ->whereHas('transactions', function ($query) {
                $query->where('is_settled', false);
            })
            ->count();

        $totalPages = ceil($totalOpponentsCount / $perPage);

        $opponents = Opponent::where('user_id', $user->id)
            ->whereHas('transactions', function ($query) {
                $query->where('is_settled', false);
            })
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        if ($opponents->isNotEmpty()) {
            $items = [];
    
            if ($totalPages > 1 && $page > 1) {
                $items[] = $this->getPrevBtn($this->params['action_type'], $this->params['method'], $page);
            }

            foreach($opponents as $opponent) {
                // 未清算の取引をすべて取得
                $unsettledTransactions = Transaction::where('user_id', $user->id)
                    ->where('opponent_id', $opponent->id)   
                    ->where('is_settled', false)
                    ->get();

                $lendingTotal = 0;
                $borrowingTotal = 0;
                $toSettle = 0;

                foreach($unsettledTransactions as $unsettledTransaction) {
                    if ($unsettledTransaction->type === Transaction::TYPE_LENDING) {
                        $lendingTotal += $unsettledTransaction->amount;
                    } else {
                        $borrowingTotal += $unsettledTransaction->amount;
                    }
                }

                $toSettle = $lendingTotal - $borrowingTotal;
                $settleName = null;

                if ($toSettle === 0) {
                    $settleName = '計';
                } else if ($toSettle > 0) {
                    $settleName = '貸し';
                } else {
                    $settleName = '借り';
                }

                $items[] = new CarouselColumn([
                    'title' => $opponent->name,
                    'text' => '貸し合計: ' . $lendingTotal . "\n" . '借り合計: ' . $borrowingTotal . "\n" . $settleName . ': ' . abs($toSettle),
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::POSTBACK,
                            'label' => '清算済みにする',
                            'data' => 'action_type=lending_and_borrowing&method=change_to_settled&opponent_id=' . $opponent->id,
                        ]),
                    ],
                ]);
            }

            if ($totalPages > $page) {
                $items[] = $this->getNextBtn($this->params['action_type'], $this->params['method'], $page);
            }

            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '貸借り状況一覧',
                'template' => new CarouselTemplate([
                    'type' => TemplateType::CAROUSEL,
                    'columns' => $items,
                ]),
            ]);

        } else {
            $templateMessage = new TemplateMessage([
                'type' => MessageType::TEMPLATE,
                'altText' => '貸借り状況一覧',
                'template' => new ButtonsTemplate([
                    'type' => TemplateType::BUTTONS,
                    'title' => '貸借り状況一覧',
                    'text' => '現在、登録されている貸し借り(未清算)はありません。',
                    'actions' => [
                        new PostbackAction([
                            'type' => ActionType::MESSAGE,
                            'label' => 'メニューに戻る',
                            'text' => config('line.text_from_rich_menu.lending_and_borrowing'),
                        ]),
                    ],
                ]),
            ]);
        }

        $this->replyMessage($replyToken, $templateMessage);
    }


    private function handleChangeToSettledMethod(string $replyToken)
    {
        $transactions = Transaction::where('opponent_id', $this->params['opponent_id'])
            ->where('is_settled', false)
            ->get();

        foreach($transactions as $transaction) {
            $transaction->is_settled = true;
            $transaction->settled_at = now();
            $transaction->save();
        }

        $templateMessage = new TemplateMessage([
            'type' => MessageType::TEMPLATE,
            'altText' => '清算済みにしました',
            'template' => new ButtonsTemplate([
                'type' => TemplateType::BUTTONS,
                'title' => '清算済みに変更しました',
                'text' => '選択した相手の貸し借りを清算済みにしました。',
                'actions' => [
                    new PostbackAction([
                        'type' => ActionType::MESSAGE,
                        'label' => 'メニューに戻る',
                        'text' => config('line.text_from_rich_menu.lending_and_borrowing'),
                    ]),
                ],
            ]),
        ]);

        $this->replyMessage($replyToken, $templateMessage);
    }
}