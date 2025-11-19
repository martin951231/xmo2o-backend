<?php

namespace app\common\model\service;

use app\common\model\db\PrivateActivityStore;

/**
 * 私域流程指定城市
 * @package app\common\model\service
 */
class PrivateActivityStoreService
{

    /**
     * 根据活动ID获取指定的店铺ID列表
     * @param $id
     * @author: 张涛
     * @date: 2021/02/26
     */
    public function getStoreIdsByAid($id)
    {
        return (new PrivateActivityStore())->where('aid', $id)->column('store_id');
    }
}