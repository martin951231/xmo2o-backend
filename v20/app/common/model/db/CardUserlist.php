<?php
/**
 * 新商家会员卡用户领取 model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 13:49
 */

namespace app\common\model\db;

use think\Model;

class CardUserlist extends Model
{
    /**
     * 更新某个字段
     * User: chenxiang
     * Date: 2020/6/1 13:59
     * @param array $where
     * @param string $field
     * @param string $value
     * @return mixed
     */
    public function setField($where = [], $field = '', $value = '') {
        $result = $this->where($where)->update([$field => $value]);
        return $result;
    }

    //add by lumin
    public function getOne($where = []){
        return $this->where($where)->find();
    }

    //add by lumin
    public function getUserCard($where = []){
        $res = $this->alias('u')
            ->leftJoin('card_new c', 'u.mer_id=c.mer_id')
            ->field('u.*,c.discount')
            ->where($where)
            ->find();
        return $res;
    }

    /**
     * 获取商家会员卡列表
     */
    public function getCardList($where, $field='*')
    {
        $res = $this->alias('cl')
            ->leftJoin('merchant m', 'm.mer_id = cl.mer_id')
            ->leftJoin('card_new c', 'm.mer_id = c.mer_id')
            ->field($field)
            ->where($where)
            ->group('cl.mer_id')
            ->select();
        if($res){
            $res = $res->toArray();
        }
        return $res;
    }
}