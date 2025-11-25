<?php


namespace app\mall\model\db;

use think\Model;
use think\facade\Db;

class MallFullGiveGiftSku extends Model
{
    /**
     * @param $goods_id
     * @param $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 赠送活动id
     */
    public function getActId($goods_id, $store_id)
    {
        $where[] = [
            'goods_id', '=', $goods_id,
        ];
        $field = 'act_id';
        $arr = $this->field($field)->where($where)->find();
        if (empty($arr)) {
            $arr = (new MallActivity())->getFullGiveAct($store_id);
        } else {
            $arr=$arr->toArray();
            //判断活动的有效性
            $arr1 = (new MallActivity())->getActivityEffic($arr['act_id']);
            if (empty($arr1)) {
                $arr = [];
            }
        }
        return $arr;
    }

    /**
     * @param $act_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 根据活动id查询参与活动的商品
     */
    public function getGoodsListByActId($act_id)
    {
        $where[] = [
            'act_id', '=', $act_id,
        ];
        $field = 'goods_id';
        $arr = $this->field($field)->where($where)->select()->toArray();
        $list = array();
        foreach ($arr as $key => $val) {
            $list[$key] = (new MallGoods())->getGoodsByGoodsId($val['goods_id']);
        }
        return $list;
    }

    /**
     * @param $condition
     * @return array
     * 查询商品参与的活动详情
     */
    public function getDetail($condition)
    {
        return [];
    }


    /** 添加数据
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        return $this->insertGetId($data);
    }

    /** 更新数据
     * Date: 2020-10-16 15:42:29
     * @param array $data
     * @param array|mixed $where
     * @return boolean
     */
    public function updateOne($data, $where)
    {
        return $this->where($where)->update($data);
    }

    /**
     * @param $where
     * @return array
     * 通过id获取sku
     */
    public function getBySkuId($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $fields
     * @param $where
     * @return mixed
     * @author qiandashuang
     */
    public function getGiftInfo($fields, $where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $res = $this->alias('g')
            ->join([$prefix . 'mall_activity' => 'a'], 'g.act_id = a.act_id', 'LEFT')
            ->join([$prefix . 'mall_goods' => 'mg'], 'g.goods_id = mg.goods_id', 'LEFT')
            ->join([$prefix . 'mall_goods_sku' => 'sku'], 'g.sku_id = sku.sku_id', 'LEFT')
            ->field($fields)
            ->where($where)
            ->select();
        if(!empty($res)){
            $res=$res->toArray();
        }
        return $res;
    }

    /**
     * @param $fields
     * @param $where
     * @return mixed
     * @author zhumengqun
     */
    public function getGiftInfo2($fields, $where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $res = $this->alias('g')
            ->join([$prefix . 'mall_activity' => 'a'], 'g.act_id = a.act_id', 'inner')
            ->join([$prefix . 'mall_goods_sku' => 'sku'], 'g.sku_id = sku.sku_id', 'inner')
            ->join([$prefix . 'mall_activity_detail' => 'de'], 'de.goods_id = g.goods_id', 'inner')
            ->field($fields)
            ->where($where)
            ->select();
        if (!empty($res)) {
            $res = $res->toArray();
        }
        return $res;
    }

    /**
     * @param $where
     * @param string $field
     * @return float
     * 统计某个条件信息
     */
    public function getMsg($where)
    {
        $arr=$this->where($where)->find();
        if(!empty($arr)){
            $arr=$arr->toArray();
        }
        return $arr;
    }


    /**
     * @param $where
     * @param string $field
     * @return float
     * 统计某个条件信息
     */
    public function getMsgList($where)
    {
        $arr=$this->where($where)->select()->toArray();
        return $arr;
    }
    /**
     * 删除数据
     * @param $where
     * @return boolean
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}