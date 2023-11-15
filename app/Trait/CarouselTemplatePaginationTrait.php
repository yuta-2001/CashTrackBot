<?php

namespace App\Trait;

use LINE\Clients\MessagingApi\Model\CarouselColumn;
use LINE\Clients\MessagingApi\Model\PostbackAction;
use LINE\Constants\ActionType;

trait CarouselTemplatePaginationTrait
{
    public function getPrevBtn($actionType, $method, $currentPage) {
        $prevPage = $currentPage - 1;
        $prevPageBtn = new CarouselColumn([
            'title' => '前のページを表示',
            'text' => 'prev',
            'actions' => [
                new PostbackAction([
                    'type' => ActionType::POSTBACK,
                    'label' => '表示',
                    'data' => 'action_type=' . $actionType . '&method=' . $method . '&page=' . $prevPage,
                ]),
                new PostbackAction([
                    'type' => ActionType::POSTBACK,
                    'label' => 'キャンセル',
                    'data' => 'action_type=cancel',
                ]),
            ],
        ]);

        return $prevPageBtn;
    }

    public function getNextBtn($actionType, $method, $currentPage) {
        $nextPage = $currentPage + 1;
        $nextPageBtn = new CarouselColumn([
            'title' => '次のページを表示',
            'text' => 'next',
            'actions' => [
                new PostbackAction([
                    'type' => ActionType::POSTBACK,
                    'label' => '表示',
                    'data' => 'action_type=' . $actionType . '&method=' . $method . '&page=' . $nextPage,
                ]),
                new PostbackAction([
                    'type' => ActionType::POSTBACK,
                    'label' => 'キャンセル',
                    'data' => 'action_type=cancel',
                ]),
            ],
        ]);

        return $nextPageBtn;
    }
}
