<?php


namespace app\mall\model\db;

use think\facade\Db;
use think\Model;
use think\facade\Config;
class MallLimitedAct  extends Model
{
    //获取限时活动列表
     public function getLimitedList($cate_id,$page,$uid,$pageSize,$group="k.goods_id",$source='',$goods_id=0){
         $prefix = config('database.connections.mysql.prefix');
         $field = "m.id,s.id as act_id,m.store_id,s.time_type,s.cycle_type,s.notice_type,s.notice_time,
         s.cycle_date,s.cycle_start_time,s.cycle_end_time,m.start_time,m.end_time,k.sku_id,k.sale_num";

         $where[]=[ 'm.is_del','=',0];
         $where[]=[ 'm.status','in',[0,1]];
         $where[]=[ 'ms.status','=',1];
         $where[]=[ 'm.end_time','>=',time()];
         $where[]=[ 'm.type','=','limited'];
         $where[]=['k.is_recommend','=',1];
         $where[]=['k.recommend_start_time','<',time()];
         $where[]=['k.recommend_end_time','>=',time()];
         if($goods_id){
             $where[]=['k.goods_id','=',$goods_id];
         }
         //从主表获取活动
         $return = $this ->alias('s')
             ->join($prefix.'mall_activity'.' m','s.id = m.act_id')
             ->join($prefix.'merchant_store'.' ms','ms.store_id = m.store_id')
             ->join($prefix.'mall_limited_sku'.' k','s.id = k.act_id')
             ->join($prefix.'mall_goods'.' g','g.goods_id = k.goods_id')
             ->field($field)
             ->where($where)
             ->group($group);
             $return = $return
                 ->order('k.sort desc,k.id desc')
                 ->field($field)
                 ->select()
                 ->toArray();
         //从活动明细中获取商品信息
         $goods_list=array();
         $limitedSkuMod = new MallLimitedSku();
         foreach ($return as $key=>$val){
                   $arr=[$val['id'],$val['time_type'],$val['cycle_type'],$val['cycle_date'],$val['cycle_start_time'],
                   $val['cycle_end_time'],$val['start_time'],$val['end_time'],$val['act_id'],$val['notice_type'],$val['notice_time']];
                   $limitedGoodsIds = [];
                   if ($cate_id == 0) {
                     //过滤推荐到首页的
                     $limitedGoodsIds = $limitedSkuMod->where('is_recommend', '=', 1)
                         ->where('act_id', '=', $val['act_id'])
                         ->group('goods_id')
                         ->column('goods_id');
                   }
                   $list=(new MallActivityDetail())->getList($val['id'],$arr,$cate_id,$uid,$page,$pageSize, $limitedGoodsIds,$source,$goods_id);

                   if(!empty($list)){
                       $goods_list[]= $list;
                   }
         }
         $goods_list1=array();
         foreach ($goods_list as $k=>$v){
              foreach ($v as $kv=>$vv){
                  $goods_list1[]=$vv;
              }
         }
         $sort = array_column($goods_list1, 'sort');
         array_multisort($sort, SORT_DESC, $goods_list1);
         return $goods_list1;
     }

    /**
     * @param $act_id
     * @param $goods_id
     * @return array
     * 根据活动和商品id找出显示活动的信息
     */
    public function getLimitedByActID($act_id,$goods_id){
        $prefix = config('database.connections.mysql.prefix');
        $field = "m.id,s.id as act_id,m.store_id,s.time_type,s.cycle_type,s.notice_type,s.notice_time,
         s.cycle_date,s.cycle_start_time,s.cycle_end_time,m.start_time,m.end_time";

        $where[]=[ 'm.is_del','=',0];
        $where[]=[ 'm.status','in',[0,1]];
        $where[]=[ 'm.end_time','>=',time()];
        $where[]=[ 'm.type','=','limited'];
        $where[]=[ 'm.act_id','=',$act_id];
        $where[]=[ 'k.goods_id','=',$goods_id];
        $where[]=['k.is_recommend','=',1];
        $where[]=['k.recommend_start_time','<',time()];
        $where[]=['k.recommend_end_time','>=',time()];
        //从主表获取活动
        $return = $this ->alias('s')
            ->join($prefix.'mall_activity'.' m','s.id = m.act_id')
            ->join($prefix.'mall_limited_sku'.' k','s.id = k.act_id')
            ->field($field)
            ->where($where)
            ->find();
        /*if($cate_id==0){
            //首页
            $return = $return ->select();
        }else{*/
        $goods_list=array();
        if(!empty($return)){
            $val=$return->toArray();
            $cate_id=0;
            $arr=[$val['id'],$val['time_type'],$val['cycle_type'],$val['cycle_date'],$val['cycle_start_time'],
                $val['cycle_end_time'],$val['start_time'],$val['end_time'],$val['act_id'],$val['notice_type'],$val['notice_time']];
            $list=(new MallActivityDetail())->getList($val['id'],$arr,$cate_id);
            if(!empty($list)){
                $goods_list[]= $list[0];
            }
        }
        return $goods_list;
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
            ->join($prefix.'mall_limited_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_goods'.' g','g.goods_id = m.goods_id')
            ->where($condition)
            ->field($field)
            ->order('act_price asc')
            ->select()
            ->toArray();
        $arr=array();
        if(!empty($result)){
            foreach ($result as $key=>$val){
                $li['sku_id']=$val['sku_id'];
                $li['act_price']=$val['act_price'];
                if($val['act_stock_num']<-1){
                    $val['act_stock_num']=0;
                }
                $li['act_stock']=$val['act_stock_num'];
                $arr[]=$li;
            }
        }
        return $arr;
    }

    /**
     * @param $condition
     * @return mixed
     * 根据条件活动活动详情
     */
    public function getDetail($condition){
        $prefix = config('database.connections.mysql.prefix');
        $field='s.start_time,s.end_time,s.end_time,s.status,m.act_price,k.price';
        $result = $this ->alias('s')
            ->join($prefix.'mall_limited_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_goods'.' g','g.goods_id = m.goods_id')
            ->join($prefix.'mall_goods_sku'.' k','k.sku_id = m.sku_id')
            ->field($field)
            ->where($condition)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * 根据id 获取活动信息
     * User: chenxiang
     * Date: 2020/10/26 15:43
     * @param $condition
     * @param bool $field
     * @return array
     */
    public function getInfoById($condition, $field = true) {
        $result = $this->field($field)->where($condition)->find();
        if(!empty($result)){
            return $result->toArray();
        }else{
            return [];
        }
    }

    /**
     * 添加一条数据
     * User: chenxiang
     * Date: 2020/10/26 16:56
     * @param $data
     * @return mixed
     */
    public function addOne($data) {
        return $this->insertGetId($data);
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
            return $result->toArray();
        }else{
            return [];
        }
    }

}