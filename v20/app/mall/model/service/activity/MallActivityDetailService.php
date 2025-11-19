<?php

namespace app\mall\model\service\activity;

use app\mall\model\db\MallActivityDetail;

class MallActivityDetailService
{
    /** 批量添加数据
     * @param $data
     */
    public function addActiveDatailAll($data)
    {
        return (new MallActivityDetail())->addAll($data);
    }

    /**删除数据
     * @param $where
     */
    public function delActiveDatailAll($where)
    {
        return (new MallActivityDetail())->delAll($where);
    }

    /**
     * @param $spu_where
     * @param $spu_field
     * @return mixed
     * 获取正在活动中的商品id（activitydetail表有些失效后商品id还在，所以需要连表重新判断）
     * @author zhumengqun
     */
    public function getGoodsInAct($where, $field)
    {
        $arr = (new MallActivityDetail())->getGoodsInAct($where, $field);
        return $arr;
    }
    /**
     * @param $spu_where
     * @param $spu_field
     * @return mixed
     * 获取正在参加活动但是可以选择的商品信息
     * @author zhumengqun
     */
    public function getGoodsOutAct($where, $field)
    {
        $arr = (new MallActivityDetail())->getGoodsOutAct($where, $field);
        return $arr;
    }
}