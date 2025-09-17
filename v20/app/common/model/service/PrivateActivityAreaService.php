<?php

namespace app\common\model\service;

use app\common\model\db\PrivateActivityArea;

/**
 * 私域流程指定地区
 * @package app\common\model\service
 */
class PrivateActivityAreaService
{
    /**
     * 根据活动ID获取指定的区域ID列表
     * @param $id
     * @author: 张涛
     * @date: 2021/02/26
     */
    public function getAreaIdsByAid($id)
    {
        return (new PrivateActivityArea())->where('aid', $id)->column('area_id');
    }
}