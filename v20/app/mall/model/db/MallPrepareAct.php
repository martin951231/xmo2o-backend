<?php


namespace app\mall\model\db;

use think\Model;
use think\facade\Config;
class MallPrepareAct extends Model
{
    /**
     * @param $condition
     * @return array
     * 查询商品参与的活动详情
     */
    public function getDetail($condition)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field = "min('m.bargain_price') as bargain_price,min('m.rest_price') as rest_price,a.start_time,a.end_time,s.*,g.*,m.*";
        $result = $this->alias('s')
            ->join($prefix . 'mall_activity' . ' a', 's.id = a.act_id')
            ->join($prefix . 'mall_prepare_act_sku' . ' m', 's.id = m.act_id')
            ->join($prefix . 'mall_goods' . ' g', 'g.goods_id = m.goods_id')
            ->where($condition)
            ->field($field)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where1
     * @return mixed
     * 获取预售订单
     */
    public function getPrepare($where1){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->join($prefix . 'mall_prepare_order m', 'm.act_id = s.id')
            ->where($where1)
            ->select()
            ->toArray();
        return $result;
    }
    /**
     * @param $condition
     * @return array
     * 活动商品规格
     */
    public function getSkuList($condition)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field='m.*';
        $result = $this ->alias('s')
            ->join($prefix.'mall_prepare_act_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_goods'.' g','g.goods_id = m.goods_id')
            ->where($condition)
            ->field($field)
            // ->order('act_price asc')
            ->select()
            ->toArray();
        $arr=array();
        if(!empty($result)){
            foreach ($result as $key=>$val){
                $li['sku_id']=$val['sku_id'];
                $li['bargain_price']=get_format_number($val['bargain_price']);//定金
                $li['rest_price']=get_format_number($val['rest_price']);//尾款
                $li['discount_price']=get_format_number($val['discount_price']);//抵扣价
                $li['act_price']=get_format_number($val['bargain_price']+$val['rest_price']);//预售价
                $li['act_stock']=$val['act_stock_num'];//库存
                $arr[]=$li;
            }
        }
        return $arr;
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
            $result=$result->toArray();
        }
        return $result;
    }


    /** 添加数据 获取插入的数据id
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function addPrepare($data)
    {
        return $this->insertGetId($data);
    }


    /** 修改数据
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function updatePrepare($data,$where) {
        $result = $this->where($where)->update($data);
        return $result;
    }
}