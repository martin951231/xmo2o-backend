<?php

/**
 * @Author: jjc
 * @Date:   2020-06-22 15:48:03
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-22 16:18:17
 */
namespace app\mall\model\db;
use app\mall\model\service\activity\MallNewBargainActService;
use app\mall\model\db\MallNewPeriodicPurchase;
use think\facade\Config;
use Exception;
use think\Model;
class MallActivity extends Model {
    /**
     * @param $where
     * @param string $field
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
	public function getAll($where,$field='*'){
		$arr=$this->where($where)->field($field)->select();
		if(empty($arr)){
            $arr=[];
        }else{
            $arr=$arr->toArray();
        }
        return $arr;
	}

    /**
     * @param $where
     * @param $order
     * @param string $field
     * @return array
     * 获取满足条件活动
     */
    public function getActField($where,$order,$field='*'){
        $arr=$this->where($where)->field($field)->order($order)->select();
        if(empty($arr)){
            $arr=[];
        }else{
            $arr=$arr->toArray();
        }
        return $arr;
    }

    public function getActByGoods($where,$whereOr,$order,$field){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_activity_detail'.' m','s.id = m.activity_id')
            ->where($where)
            ->whereOr($whereOr)
            ->order($order)
            ->select();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where
     * @param $whereOr
     * @param $order
     * @param $field
     * @return array
     *查询一条数据
     */
    public function getActByGoodsID($where,$field){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_activity_detail'.' m','s.id = m.activity_id')
            ->where($where)
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    public function getActByGoodsIDOr($where,$field,$condition_or=[]){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_activity_detail'.' m','s.id = m.activity_id')
            ->where($where)
            ->whereOr($condition_or)
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }
    /**
     * @param $goods_id
     * @param null $activity_id
     * @param null $uid
     * @param null $team_id
     * @return array|mixed
     * @author mrdeng
     * 获取商品活动信息
     * 商品级活动
     */
	public function getActivity($goods_id,$activity_id,$uid,$team_id,$skuid,$source=''){
        $prefix = config('database.connections.mysql.prefix');
        //取热卖商品id
        $condition[] = ['m.goods_id','=',$goods_id];//商品id
        if ($source != 'limited') {
            $condition[] = ['s.start_time','<',time()];//'活动开始时间',
        }
        $condition[] = ['s.end_time','>=',time()];//'活动结束时间',
        $condition[] = ['s.status','<>',2];//'活动状态 0未开始 1进行中 2已失效',
        if(!empty($activity_id)){
            $condition[] = ['s.activity_id','=',$activity_id];
            $condition1[] = ['s.activity_id','=',$activity_id];
        }
        //放进商品表中查询
        $field='s.*,t.price';
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_activity_detail'.' m','s.id = m.activity_id')
            ->join($prefix.'mall_goods'.' t','m.goods_id = t.goods_id')
            ->where($condition)
            ->order('s.sort asc')
            ->select()
            ->toArray();
        return $result;
    }

    /**
     * @param $goods_id
     * @param $activity_id
     * @param $uid
     * @param $team_id
     * @param $skuid
     * @return mixed
     * 周期购商品详情另外判断
     */
    public function getActivityPeriodic($goods_id,$activity_id,$uid,$team_id,$skuid){
        $prefix = config('database.connections.mysql.prefix');
        //取热卖商品id
        $condition[] = ['m.goods_id','=',$goods_id];//商品id
        $condition[] = ['s.status','=',1];//'活动状态 0未开始 1进行中 2已失效',
        $condition[] = ['s.type','=','periodic'];
        if(!empty($activity_id)){
            $condition[] = ['s.activity_id','=',$activity_id];
            $condition1[] = ['s.activity_id','=',$activity_id];
        }
        //放进商品表中查询
        $field='s.*,t.price';
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_activity_detail'.' m','s.id = m.activity_id')
            ->join($prefix.'mall_goods'.' t','m.goods_id = t.goods_id')
            ->where($condition)
            ->order('s.sort asc')
            ->select()
            ->toArray();
        return $result;
    }

    /**
     * @param $goods_id
     * @param $activity_id
     * @param $goods_name
     * @param $goods_image
     * @author mrdeng
     * 查找商品关联的限时活动列表
     */
    public function getLimitedActivity($goods_id,$activity_id,$goods_name,$goods_image,$price,$skuid,$source='')
    {
        $prefix = config('database.connections.mysql.prefix');

        $condition[] = ['s.id','=',$activity_id];
        if ($source != 'limited') {
            $condition[] = ['s.start_time','<',time()];//'活动开始时间',
        }
        $condition[]=[ 's.end_time','>=',time()];
        $condition[]=[ 's.status','<>',2];
        if($goods_id>0){
            $condition[]=[ 'g.goods_id','=',$goods_id];
        }
        if($skuid>0){
            $condition[]=[ 'g.sku_id','=',$skuid];
        }
        //放进商品表中查询
        $field="m.id,s.start_time,s.end_time,m.time_type,m.id,s.store_id,m.time_type,m.cycle_type,
         m.cycle_date,m.cycle_start_time,m.cycle_end_time,m.buy_limit,g.act_stock_num,m.is_discount_share,m.discount_card,m.discount_coupon";
        $result = $this ->alias('s')
            ->join($prefix.'mall_limited_act'.' m','s.act_id = m.id')
            ->join($prefix.'mall_limited_sku'.' g','g.act_id = m.id')
            ->field($field)
            ->where($condition)
            ->find();
		fdump_sql([$goods_id,$activity_id,$goods_name,$goods_image,$price,$skuid, $result, $this->getLastSql()],"limit_data_getLimitedActivity_db");
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        unset($condition);
        return $result;
    }

    /**
     * @param $goods_id
     * @param $skuid
     * @param $type
     * @return \json
     * @author mrdeng
     * 各大活动信息
     */
    public function getAllActivityDetail($goods_id,$skuid,$type,$uid,$team_id){
        $prefix = config('database.connections.mysql.prefix');
        $condition[] = ['m.goods_id','=',$goods_id];
        try{
            switch ($type){
                case 'bargain'://砍价
                    $condition[] = ['m.sku_id','=',$skuid];
                  return (new MallNewBargainAct())->getDetail($condition,$team_id);
                case 'group'://拼团
                    return (new MallNewGroupAct())->getDetail($condition,$team_id);
                case 'limited'://限时优惠
                $condition[] = ['m.sku_id','=',$skuid];
                return (new MallLimitedAct())->getDetail($condition);
                case 'prepare'://预售
                return (new MallPrepareAct())->getDetail($condition);
                case 'periodic'://周期购
                return (new MallNewPeriodicPurchase())->getDetail($condition);
                case 'reached':
                return (new MallReachedAct())->getDetail($condition);
                case 'shipping':
                return (new MallShippingAct())->getDetail($condition);
                case 'give':
                return (new MallFullGiveGiftSku())->getDetail($condition);
                case 'minus_discount':
                return (new MallFullMinusDiscountAct())->getDetail($condition);
            }

        }catch (Exception $e){
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @param $actid
     * @return bool|\json
     * 判断活动是否在进行中
     * @author mrdeng
     */
    public function getActivityExist($actid){
        try{
            $condition[]=['start_time','lt',time()];
            $condition[]=['end_time','egt',time()];
            $condition[]=['act_id','eq',$actid];
            $result=$this->where($condition)
                ->find();
            if(empty($result)){
                return false;
            }
            return true;
        }catch (Exception $e){
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * @author zhumengqun
     * 获取活动的id
     * @param $where
     * @param $field
     * @return array
     */
    public function getActInfo($where, $field)
    {
        $arr = $this->field($field)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 获取满赠活动的id
     */
    public function getFullGiveAct($store_id){
        $where[] = [
            'store_id','=',$store_id,
            'type','=','give',
            'act_type','=',1,
            'start_time','<',time(),
            'end_time','>=',time(),
            'status','<>',2
        ];
        $field='act_id';
        $arr = $this->field($field)->where($where)->find();
        if(!empty($arr)){
            $arr=$arr->toArray();
        }
        return $arr;
    }

    /**
     * @param $act_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *  获取活动详情
     */
    public function getActivityDetail($act_id){
        $where[] = [
            'act_id','=',$act_id
        ];
        $arr = $this->where($where)->find();
        if(!empty($arr)){
            $arr=$arr->toArray();
        }
        return $arr;
    }

    public function getOne($where){
        $arr = $this->where($where)->find();
        if(!empty($arr)){
            $arr=$arr->toArray();
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
     * 获取满赠的有效活动，还没结束的
     */
    public function getActivityEffic($act_id){
        $where[] = [
            'act_id','=',$act_id,
            'type','=','give',
            'act_type','=',1,
            'end_time','egt',time(),
             'status','neq',2
        ];
        $field='act_id';
        $arr = $this->field($field)->where($where)->find()->toArray();
        return $arr;
    }

    /**
     * @param $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 获取门店发起的有效活动
     */
    public function getActivityByStoreId($store_id){
        $where[] = [
            'store_id','=',$store_id,
            'type','=','give',
            'act_type','=',1,
            'end_time','egt',time(),
            'status','neq',2
        ];
        $field='act_id,type,act_type';
        $arr = $this->field($field)->where($where)->order('sort asc')->select()->toArray();
        return $arr;
    }

    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addOne($data) {
        return $this->insertGetId($data);

    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return MallGoods
     */
    public function updateOne($data,$where)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 根据活动id 获取活动基础信息
     * User: chenxiang
     * Date: 2020/10/26 15:55
     * @param $where
     * @param bool $field
     * @return array
     */
    public function getActInfoById($where, $field = true) {

        $result = $this->field($field)->where($where)->find();
        if($result) {
            $result = $result->toArray();
        }
        return $result;
    }

    /**
     * @param $where
     * @param $field
     * 获取正在参加活动的商品id
     */
    public function getGoodsInAct($where, $field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix . 'mall_activity_detail d', 'a.id=d.activity_id', 'left')
            ->field($field)
            ->where($where)
            ->order('sort DESC')
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @param $field
     * 获取正在参加活动的商品id
     */
    public function getActStatus($where, $field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix . 'mall_activity d', 'a.id=d.act_id')
            ->field($field)
            ->where($where)
            ->order('sort DESC')
            ->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $act_id
     * @param $tid
     * 获取拼团连接时效状态
     */
    public function checkGroupTimeMag($act_id,$tid){
        $where[]=['s.act_id','=',$act_id];
	$where[]=['s.type','=','group'];
        $where[]=['t.id','=',$tid];
        $where[]=['t.start_time','<',time()];
        $where[]=['t.end_time','>=',time()];
        $field="s.status as act_status,s.start_time as act_start_time,t.status as tid_status,t.start_time as tid_start_time,t.end_time as tid_end_time,s.end_time as act_end_time";
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('s')
            ->join($prefix . 'mall_new_group_team t', 't.act_id=s.act_id')
            ->field($field)
            ->where($where)
            ->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}
