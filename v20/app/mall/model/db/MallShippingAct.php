<?php
namespace app\mall\model\db;
use think\facade\Db;
use think\Model;
class MallShippingAct extends Model
{
    public function getOne($goods_id,$store_id){
    $where[] = ['','exp',Db::raw("FIND_IN_SET($goods_id,goods_id)")];
    $result1=[];
    $arr= $this->where($where)->find();
    if($arr){
        $result1['act_type']=0;
        $result1['name']="活动";
        $result1['msg']="满".round($arr['price'])."包邮";
    }
    return $result1;
   }

    /**
     * @param $condition
     * @return mixed
     * 根据条件活动活动详情
     */
    public function getDetail($condition){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'mall_shipping_act_goods'.' m','s.id = m.act_id')
            ->where($condition)
            ->find();
        if(!empty($result)){
            $result=$result ->toArray();
        }
        return $result;
    }

    /**
     * @param $act_id
     * @return bool
     * 满包邮活动详情及商品列表
     */
    public function getOneAct($condition1){
        $result1 = $this
            ->where($condition1)->find();
        if(!empty($result1)){
            $result1=$result1->toArray();
        }
        return $result1;
    }

     /** 添加数据 获取插入的数据id
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function addShipping($data) {
        return $this->insertGetId($data);
    }


    /** 修改数据
     * Date: 2020-10-19
     * @param $data
     * @return int|string
     */
    public function updateShipping($data,$where) {
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
                $result=$result->toArray();
            }
            return $result;
    }

}