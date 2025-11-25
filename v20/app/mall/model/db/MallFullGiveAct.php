<?php


namespace app\mall\model\db;

use think\Model;
class MallFullGiveAct extends Model
{
    /**
     * @param $condition
     * @return mixed
     * 根据条件活动活动详情
     */
    public function getDatail($condition){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'mall_full_give_sku'.' m','s.id = m.act_id')
            ->where($condition)
            ->find();
        if(!empty($result)){
            $result=$result ->toArray();
        }
        return $result;
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
    public function getGoodsListByActId($act_id,$uid=0)
    {
        $where= [
            ['s.id', '=', $act_id],
            ['m.type', '=', 'give'],
        ];
        $field = 'g.goods_id';
        $arr = $this ->alias('s')->field($field)
            ->join('mall_activity'.' m','s.id = m.act_id')
            ->join('mall_activity_detail'.' d','m.id = d.activity_id')
            ->join('mall_goods'.' g','g.goods_id = d.goods_id')
            ->where($where)->select()->toArray();
        $list = array();
        foreach ($arr as $key => $val) {
            $list[$key] = (new MallGoods())->getGoodsByGoodsId($val['goods_id'],$uid);
        }
        return $list;
    }

    /**
     * @param $actid
     * @return mixed
     * 根据活动id获取赠品列表
     */
    public function getGiveList($actid){
        $prefix = config('database.connections.mysql.prefix');
        $condition[]=['s.id','=',$actid];
        /*$condition[]=['l.act_stock_num','<>',0];*/
        $field='m.id,s.full_type,m.level_money,l.goods_id,l.sku_id,l.act_stock_num,l.gift_num';
        $result = $this ->alias('s')
            ->join($prefix.'mall_full_give_level'.' m','s.id = m.act_id')
            ->join($prefix.'mall_full_give_gift_sku'.' l','l.level_num = m.id')
            ->field($field)
            ->where($condition)
            ->order('m.level_sort asc')
            ->select();
        if(!empty($result)){
            $result=$result ->toArray();
        }
        return $result;
    }

    /**
     * @param $full_type
     * @param $level_money
     * @param $goods_id
     * @param $sku_id
     * @param $act_stock_num
     * @return array
     * @author mrdeng
     * 获取赠送商品的信息
     */
    public function getList($full_type,$level_money,$goods_id,$sku_id,$act_stock_num){
        $goodslist=array();
        if($full_type==1){
            $goodslist['name']="满".$level_money.'得赠品';
        }else{
            $goodslist['name']="满".$level_money.'件得赠品';
        }
        $goodslist['left_nums']=$act_stock_num;
        $arr=(new MallGoods())->getGoodsNameAndSku($goods_id,$sku_id);
        $goodslist['goods_name']=$arr['name'];
        $goodslist['spec_str']=$arr['sku_str'];
        $goodslist['goods_img']=$arr['image'] ? replace_file_domain($arr['image']) : '';
        return $goodslist;
    }

    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addGive($data) {
        return $this->insertGetId($data);
    }

    /** 更新数据 
     * Date: 2020-10-16 15:42:29
     * @param array $data
     * @param array|mixed $where
     * @return boolean
     */
    public function updateGive($data,$where) {
        $result = $this->where($where)->update($data);
        return $result;
    }

     /**
     * @param $where
     * @return array
     * 查询活动基本信息
     */
    public function getInfo($where,$fields='*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'mall_activity'.' m','s.id = m.act_id')
            ->field($fields)
            ->where($where)->find();
        if(!empty($result)){
            $result=$result ->toArray();
        }
        return $result;
    }

}