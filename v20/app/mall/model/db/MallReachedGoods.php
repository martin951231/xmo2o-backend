<?php


namespace app\mall\model\db;

use think\facade\Db;
use think\Model;
class MallReachedGoods extends Model
{
    /**
     * @param $act_id
     * @return bool
     * N元N件活动详情及商品列表
     */
    public function getGoodsList($act_id,$uid=0){
        $condition[]=['act_id','=',$act_id];
        $prefix = config('database.connections.mysql.prefix');
        $field="s.goods_id,g.name as goods_name,g.image as goods_image,g.price as goods_price,g.sale_num as payed,g.store_id,g.mer_id";
        $result=$this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_goods'.' g','s.goods_id = g.goods_id')
            ->where($condition)
            ->select();
        if(!empty($result)){
            $result=$result->toArray();
        }
        if(!empty($result)){
            foreach ($result as $key=>$val){
                $result[$key]['label_list']=(new MallGoods())->globelLabel($val['store_id'],$val['goods_id'],$val['mer_id'],$uid);//商品标签
                $result[$key]['goods_image'] = $val['goods_image'] ? replace_file_domain($val['goods_image']) : '';
                $result[$key]['goods_price'] = get_format_number($val['goods_price']);
            }
        }else{
            return false;
        }

        return $result;
    }

    /**批量添加商品信息
     * @param $data
     * @return int
     */
    public function addAll($data)
    {
         $res=$this->insertAll($data);
        return $res;
    }

    /**
     * 查询spu信息
     * User: chenxiang
     * Date: 2020/11/10 15:06
     * @param $condition
     * @param bool $field
     * @return array
     */
    public function getSpuInfo($condition, $field = true) {
        $result= $this->field($field)->where($condition)->select();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

}