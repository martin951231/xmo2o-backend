<?php


namespace app\mall\model\db;
use Exception;
use think\Model;
use think\facade\Config;
class MallNewBargainTeam extends Model
{
    //好友助力列表
    public function helpList($tid)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field1 = 'm.user_logo,m.nickname,s.bar_price,s.bar_time';
        /*$condition[] = ['t.is_start', '=', 1];*/
        $condition[] = ['s.tid', '=', $tid];
        $condition[] = ['s.is_start', '=', 0];
        /*if (!empty($team_id)) {
            $condition[] = ['t.tid', '=', $team_id];
        }*/
        $result1 = (new MallNewBargainTeamUser())->alias('s')
            ->field($field1)
            ->join($prefix . 'user' . ' m', 'm.uid = s.user_id')
            ->where($condition)
            ->order('s.bar_price asc')
            ->select()->toArray();
       /* if(!empty($result1)){
            $result1=$result1->toArray();
        }*/
        return $result1;
    }

    public function getBargainSuccessList()
    {
        $prefix = config('database.connections.mysql.prefix');
        //成功砍价列表
        $field1 = 'u.avatar as member_icon,u.nickname as member_name,m.name as goods_name,
        o.create_time as bargain_success_date,m.name as goods_name,m.image as goods_image,m.price as goods_price';
        $condition1[] = ['s.status', 'in', [1,2,3]];
        $result= $this->alias('s')
            ->field($field1)
            ->join($prefix . 'mall_order' . ' o', 's.order_id = o.order_id')
            ->join($prefix . 'mall_order_detail' . ' m', 's.order_id = m.order_id')
            ->join($prefix . 'user' . ' u', 'u.uid = s.user_id')
            ->where($condition1)
            ->select();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $team_id
     * @param $user_id
     * @author mrdeng
     * 获取砍价价格及插入砍价信息
     */
    public function getBargainPrice($team_id, $user_id)
    {
        $where[] = ['id', '=', $team_id];
        $team = $this->where($where)->find();
        if(!empty($team)){
            $team=$team->toArray();
        }
        return $team;
    }


    /**
     * @param $actid
     * @return bool|\json
     * 判断活动是否在进行中
     * @author mrdeng
     */
    public function getActivityExist($actid,$uid)
    {
       /* $condition[] = ['start_time', '<', time()];*/
        $condition[] = ['end_time', '>=', time()];
        $condition[] = ['act_id', '=', $actid];
        $condition[] = ['user_id', '=', $uid];
        $result = $this->where($condition)
            ->find();
        if(!empty($result)){
            return true;
        }else{
            return false;
        }
    }

    public function getActivityExist2($condition)
    {
        $result = $this->where($condition)
            ->find();
        if(!empty($result)){
            return true;
        }else{
            return false;
        }
    }
    /**
     * @param $tid
     * @author mrdeng
     * 成团砍价活动关联商品的价格
     */
    public function getActivityGoods($tid,$sku_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field1 = 's.id,g.price as money_total,d.name as goods_name,d.goods_id,d.image as goods_image';
        $condition[] = ['s.id', '=', $tid];
       /* $condition[] = ['g.sku_id', '=', $sku_id];*/
        $condition[] = ['m.sku_id', '=', $sku_id];
        $result1 = $this->alias('s')
            ->field($field1)
            ->join($prefix . 'mall_new_bargain_sku' . ' m', 's.act_id = m.act_id')
            ->join($prefix . 'mall_goods' . ' d', 'm.goods_id = d.goods_id')
            ->join($prefix . 'mall_goods_sku' . ' g', 'm.sku_id = g.sku_id')
            ->where($condition)
            ->find();
        if(!empty($result1)){
            $result1=$result1->toArray();
        }
        return $result1;
    }

    /**
     * @param $team_id //团队id
     * @author mrdeng
     */
    public function getMyBargainDetail($team_id, $act_id)
    {
        //砍价信息
        $prefix = config('database.connections.mysql.prefix');
        $condition[] = ['s.id', '=', $team_id];
        $condition[] = ['b.id', '=', $act_id];
        $condition[] = ['a.type', '=', 'bargain'];
        $condition[] = ['a.act_id', '=', $act_id];
        $field = 'u.avatar,u.nickname,m.name as goods_name,m.goods_id,
        gsku.image as goods_image,s.bar_total_price as already_bargain,
        (gsku.price-s.floor_price-s.bar_total_price) as left_bargain,(s.end_time-s.start_time) as left_time,
        s.status as activity_status,a.end_time as activity_end,s.end_time as team_end,s.order_id,s.user_id';
        $result = $this->alias('s')
            ->field($field)
            ->join($prefix . 'mall_new_bargain_act' . ' b', 's.act_id = b.id')
            ->join($prefix . 'mall_activity' . ' a', 'a.act_id = b.id')
            ->join($prefix . 'mall_new_bargain_sku' . ' sku', 'sku.act_id = b.id')
            ->join($prefix . 'mall_goods' . ' m', 'sku.goods_id = m.goods_id')
            ->join($prefix . 'mall_goods_sku' . ' gsku', 'gsku.sku_id = sku.sku_id')
            ->join($prefix . 'user' . ' u', 'u.uid = s.user_id')
            ->where($condition)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
            $result['goods_image']=$result['goods_image'] ? replace_file_domain($result['goods_image']) : '';
        }
        return $result;
    }


    /**
     * @param $condition1
     * @param $team
     * 根据条件保存数据
     * @author mrdeng
     */

    public function saveData($condition1, $team)
    {
       $arr= $this->where($condition1)->save($team);
       return $arr;
    }

    /**
     * @return int
     * @author mrdeng
     * 成功砍价人数
     */
    public function getBargainNumSucess()
    {
        //成功砍价人数
        $condition1[] = ['status', 'in', [1,2,3]];
        $nums = $this->where($condition1)->count();
        return $nums;
    }

    /**
     * @return int
     * @author mrdeng
     * 成功砍价人数
     */
    public function getBargainNum($condition1)
    {
        //成功砍价人数
        $nums = $this->where($condition1)->count();
        return $nums;
    }

    /**
     * @return int
     * @author mrdeng
     * 进行中砍价人数
     */
    public function getBargainNumInAct()
    {
        //成功砍价人数
        $condition1[] = ['status', '=', 0];
        $condition1[] = ['start_time', '<', time()];
        $condition1[] = ['end_time', '>=', time()];
        $nums = $this->where($condition1)->count();
        return $nums;
    }

    /**
     * @param $uid
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 是否发起团队
     */
    public function isManInBarginTeam($act_id,$uid)
    {
        if(!empty($act_id)){
            $condition2[] = ['act_id', '=', $act_id];
        }
        $condition2[] = ['start_time', '<', time()];
        $condition2[] = ['end_time', '>=', time()];
        $condition2[] = ['user_id', '=', $uid];
        $condition2[] = ['status', '=', 0];
        $field1 = "id";
        $result = $this
            ->field($field1)
            ->where($condition2)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $uid
     * @param $act_id
     * @param $sku_id
     * @return mixed
     *  * 判断是否成团
     */
    public function isManInBarginTeamGoodsAct($uid,$act_id,$sku_id)
    {
        $condition2[] = ['s.start_time', '<', time()];
        $condition2[] = ['s.end_time', '>=', time()];
        $condition2[] = ['s.user_id', '=', $uid];
        $condition2[] = ['s.act_id', '=', $act_id];
        $condition2[] = ['msku.sku_id', '=', $sku_id];
        $condition2[] = ['s.status', '=', 0];
        $field1 = "s.id";
        $result = $this->alias('s')->field($field1)
            ->join('mall_new_bargain_act' . ' m', 's.act_id = m.id')
            ->join('mall_new_bargain_sku' . ' msku', 'msku.goods_id = m.goods_id')
            ->where($condition2)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }else{
            $result=[];
        }
        return $result;
    }

    /**
     * @param $uid
     * @param $act_id
     * @param $sku_id
     * @return mixed
     *  获取参与次数
     */
    public function barginTeaNums($uid,$act_id,$sku_id)
    {
        $condition2[] = ['s.user_id', '=', $uid];
        $condition2[] = ['s.act_id', '=', $act_id];
        $condition2[] = ['msku.sku_id', '=', $sku_id];
        $condition2[] = ['msku.act_id', '=', $act_id];
        $field1 = "s.id";
        $result = $this->alias('s')->field($field1)
            ->join('mall_new_bargain_act' . ' m', 's.act_id = m.id')
            ->join('mall_new_bargain_sku' . ' msku', 'msku.goods_id = m.goods_id')
            ->where($condition2)
            ->count();
        return $result;
    }
    /**
     * 添加一项
     * @param $where
     * @param $data
     */
    public function addOne($data)
    {
        return $result = $this->insertGetId($data);

    }

    /**
     * 根据id 获取活动数据
     * User: mrdeng
     * Date: 2020/11/2 10:43
     * @param $condition
     * @param bool $field
     * @return array
     */
    public function getInfoById($condition, $field = "*") {

        $result = $this->field($field)->where($condition)->find();
        if(!empty($result)) {
            $result = $result->toArray();
        }
        return $result;
    }
    /**
     * @param $where
     * @param $field
     * @return mixed
     * 返回砍价团队关联活动的信息
     */
    public function myBargainListPrice($where,$field,$page,$limit,$where1,$whereOr=[]){
        $prefix = config('database.connections.mysql.prefix');
        if($limit==2){
            $limit=2;
        }else{
            $limit= Config::get('api.page_size');
        }

        $result = $this->alias('s')
            ->field($field)
            ->join($prefix . 'mall_new_bargain_act' . ' m', 's.act_id = m.id')
            ->join($prefix . 'mall_new_bargain_sku' . ' msku', 'msku.act_id = s.act_id')
            ->join($prefix . 'mall_goods_sku' . ' mg', 'mg.sku_id = msku.sku_id')
            ->join($prefix . 'mall_goods' . ' mgd', 'mgd.goods_id = msku.goods_id')
            ->join($prefix . 'mall_activity' . ' ma', 'ma.act_id = s.act_id')
            ->where($where);
        if($whereOr){
            $result = $result->whereOr($whereOr);
        }
        $result = $result->group('s.id')
            ->order('s.id desc');

        $arr=$result->select();
        $count=$result->count();
        $list=array();
        if(!empty($arr)){
                $list = $result->page($page, $limit)
                    ->select()->toArray();
        }
        $return = [
            'total_count' =>  $count,
            'page_count'  =>  intval(ceil($count/Config::get('api.page_size'))),
            'now_page'    =>  $page,
            'list'        =>  $list
        ];

        return $return;
    }
    
    //获取超时5天的团队砍价完成未下单的数据
    public function getFiveDaysList(){
        $time=time()-86400*5;
        $condition=[['success_time','<',$time],['success_time','>',0],['status','=',1]];
        $result = $this->where($condition)->select()->toArray();
        return $result;
    }


    /**
     * @param $where
     * @param string $field
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 查询符合条件的数据
     */
    public function getAll($where,$field='*'){
        $arr=$this->where($where)->field($field)->select()->toArray();
        return $arr;
    }
}