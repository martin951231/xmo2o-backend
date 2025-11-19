<?php


namespace app\mall\model\db;

use think\Model;

class MallLimitedSku extends Model
{
    //查找最低价
    public function limitPrice($goods_id)
    {
        $where[] = ['goods_id', '=', $goods_id];
        return $this->where($where)->min('act_price');
    }

    //查找最低价
    public function limitMinPrice($act_id,$goods_id)
    {
        $where[] = ['act_id', '=', $act_id];
        $where[] = ['goods_id', '=', $goods_id];
        return $this->where($where)->min('act_price');
    }

    /**
     * @param $act_id
     * @return float
     * 获取某个活动秒杀商品的剩余库存
     */
    public function getRestActSum($act_id,$field)
    {
        $return=$this->where($act_id)->sum($field);
        return $return;
    }

    /**
     * @param $act_id
     * @return float
     * 获取某个活动秒杀商品的剩余库存
     */
    public function getLimitActSum($act_id,$field)
    {
        $minStock=$this->getRestActMinStock($act_id,0);
        if($minStock==-1){
            $return=-1;
        }else{
            $where=[['act_id','=',$act_id]];
            $return=$this->where($where)->sum($field);
        }
        return $return;
    }

    /**
     * 编辑其中一项
     * @param $where
     * @param $data
     * @return MallGoods
     */
    public function updateOne($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
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
     * @param $where
     * @return array
     * 通过id获取sku
     */
    public function getListBySkuId($where)
    {
        $arr = $this->field(true)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getCategoryList()
    {
        $prefix = config('database.connections.mysql.prefix');
        $field = "gd.cate_second,cy.cat_name,gd.goods_id,cy.cat_id";
        $where = [
            ['mty.status', 'in', [0, 1]],
            ['mty.start_time', '<', time()],
            ['mty.end_time', '>=', time()],
            ['mty.type', '=', 'limited']
        ];
        //从主表获取活动
        $return = $this->alias('s')
            ->join($prefix . 'mall_limited_act' . ' lct', 'lct.id = s.act_id')
            ->join($prefix . 'mall_activity' . ' mty', 'mty.act_id = lct.id')
            ->join($prefix . 'mall_goods' . ' gd', 'gd.goods_id = s.goods_id')
            ->join($prefix . 'mall_category' . ' cy', 'cy.cat_id = gd.cate_second')
            ->field($field)
            ->where($where)
            ->group('gd.cate_second')
            ->select();
        if(!empty($return)){
            $return=$return->toArray();
        }

        $category_list = array();
        $category_list[0] = ["cate_second" => 0,
            "cat_name" => "首页",
            "goods_id" => 0,
            "cat_id" => 0];
        //从活动明细中获取商品信息
        foreach ($return as $key => $val) {
            $count = $this->alias('s')
                ->join('mall_limited_act' . ' lct', 'lct.id = s.act_id')
                ->join('mall_activity' . ' mty', 'mty.act_id = lct.id')
                ->join('mall_goods' . ' gd', 'gd.goods_id = s.goods_id')
                ->where($where)
                ->where('gd.cate_second', '=', $val['cate_second'])
                ->count('distinct s.goods_id');
            if ($count >= 10) {
                $category_list[] = $return[$key];
            }
        }
        return $category_list;
    }

    /**
     * 根据act_id连表查出sku信息
     */
    public function getSkuByActId($act_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('l')
            ->join($prefix . 'mall_goods_sku gs', 'l.sku_id=gs.sku_id')
            ->join($prefix . 'mall_goods g', 'l.goods_id=g.goods_id')
            ->where(['l.act_id' => $act_id])
            ->order('l.act_price ASC')
            ->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $act_id
     * @return float
     * 获取某个活动秒杀商品的剩余库存
     */
    public function getRestActStock($act_id,$goods_id)
    {
        $return=$this->where(['act_id' => $act_id,'goods_id'=>$goods_id])->sum('act_stock_num');
        if($return<0){
            $return=-1;
        }
        return $return;
    }

    /**
     * @param $act_id
     * @return float
     * 获取某个活动秒杀商品规格的剩余库存
     */
    public function getRestActStockBySkuId($act_id,$goods_id,$sku_id)
    {
        if($sku_id){
            $return=$this->where(['act_id' => $act_id,'goods_id'=>$goods_id,'sku_id'=>$sku_id])->sum('act_stock_num');
        }else{
            $return=$this->where(['act_id' => $act_id,'goods_id'=>$goods_id])->sum('act_stock_num');
        }
        return $return;
    }

    /**
     * @param $act_id
     * @return float
     * 获取某个活动秒杀商品最小库存
     */
    public function getRestActMinStock($act_id,$goods_id)
    {
        if($goods_id){
            $return=$this->where(['act_id' => $act_id,'goods_id'=>$goods_id])->min('act_stock_num');
        }else{
            $return=$this->where(['act_id' => $act_id])->min('act_stock_num');
        }
        return $return;
    }

    /**
     * @param $act_id
     * @param $goods_id
     * @return mixed
     * 查找最小的库存
     */
    public function getRestActMinStockByGoodsId($act_id,$goods_id)
    {
        $return1=$this->where(['act_id' => $act_id,'goods_id'=>$goods_id])->min('act_stock_num');
        return $return1;
    }
}