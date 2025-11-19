<?php


namespace app\mall\model\db;
use think\facade\Db;
use think\Model;
class MallReachedAct extends Model
{
    public function getOne($goods_id,$store_id){
        $where[] = ['','exp',Db::raw("FIND_IN_SET($goods_id,goods_id)")];
        $result1=[];
        $arr= $this->where($where)->find();
        if($arr){
            $result1['act_type']=0;
            $result1['name']="活动";
            $result1['msg']="满".round($arr['goods_money'])."送".round($arr['nums'])."件";
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
            ->join($prefix.'mall_reached_goods'.' m','s.id = m.act_id')
            ->join($prefix.'mall_goods'.' g','g.goods_id = m.goods_id')
            ->where($condition)
            ->find()
            ->toArray();
        return $result;
    }

    /**
     * @param $act_id
     * @return bool
     * N元N件活动详情及商品列表
     */
    public function getReachedList($act_id,$uid=0){
        $prefix = config('database.connections.mysql.prefix');
        $condition1[]=['s.id','=',$act_id];
        $condition1[]=['m.start_time','<',time()];
        $condition1[]=['m.end_time','>=',time()];
        $condition1[]=['m.type','=','reached'];
        $condition1[]=['m.status','=',1];
        $result1 = $this ->alias('s')
            ->join($prefix.'mall_activity'.' m','s.id = m.act_id')
            ->where($condition1)->find();
        if(empty($result1)){
            $result['goods_list'] = [];
            $result['rule_msg'] = [];
            $result['left_time'] = 0;
           // return false;
        }else{
            $result1=$result1->toArray();
            $msg['msg']="满".get_format_number($result1['money'])."元任选".$result1['nums'].'件';
            $result['rule_msg'][]=$msg;
            $result['left_time']=$result1['end_time']-time();//剩余时间
        }
        $result['goods_list']=(new MallReachedGoods())->getGoodsList($act_id,$uid);//商品列表
        return $result;
    }

    /**
     * 添加数据 获取插入的数据id
     * User: chenxiang
     * Date: 2020/10/13 16:38
     * @param $data
     * @return int|string
     */
    public function add($data) {
        $res = $this->insertGetId($data);
        return $res;
    }

    /**
     * 获取活动信息
     * User: chenxiang
     * Date: 2020/11/10 14:46
     * @param $where
     * @param string $fields
     * @return mixed
     */
    public function getInfo($where,$fields='*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('r')
            ->join($prefix.'mall_activity'.' m','m.act_id = r.id')
            ->field($fields)
            ->where($where)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * 获取活动信息
     * User: chenxiang
     * Date: 2020/11/10 14:46
     * @param $where
     * @param string $fields
     * @return mixed
     */
    public function getInfoList($where,$fields='*',$order)
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('r')
            ->join($prefix.'mall_activity'.' m','m.act_id = r.id')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->select()
            ->toArray();
        return $result;
    }

}