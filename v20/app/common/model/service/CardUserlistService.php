<?php
/**
 * 新商家会员卡 用户领取
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 13:41
 */

namespace app\common\model\service;

use app\common\model\db\CardUserlist;

class CardUserlistService
{
    public $cardUserlistObj = null;
    public function __construct()
    {
        $this->cardUserlistObj = new CardUserlist();
    }

    /**
     * 更新某个字段
     * User: chenxiang
     * Date: 2020/6/1 14:01
     * @param array $where
     * @param string $field
     * @param string $value
     * @return mixed
     */
    public function setField($where = [], $field = '', $value = '') {
        $result = $this->cardUserlistObj->setField($where, $field, $value);
        return $result;
    }
}