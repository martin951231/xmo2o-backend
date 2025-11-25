<?php

namespace app\internal_card\model\service;

use app\job\Miaomai\MiaomaiSyncJob;
use think\facade\Queue;

class MiaomaiGoodsSyncService
{
    public function queueSyncGoods($merId,$storeId,$maxPage): bool
    {
        for ($page = 1;$page <= $maxPage;$page++) {
            $data = [
                'mer_id' => $merId,
                'store_id' => $storeId,
                'page' => $page,
                'type' => 'goods'
            ];
            Queue::later(random_int(1,60), MiaomaiSyncJob::class, $data,'sync_miaomai_goods');
        }
                
        return true;
    }
}