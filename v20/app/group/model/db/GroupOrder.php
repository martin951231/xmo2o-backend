<?php

/**
 * 团购订单
 * Created by PhpStorm.
 * Author: 衡婷妹
 * Date Time: 2020/11/18 17:10
 */
namespace app\group\model\db;

use think\Model;
use think\facade\Db;
class GroupOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $json = ['internal_card_pay'];
    protected $jsonAssoc = true;
    /**
     * 根据条件返回优惠组合订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getGroupCombineOrderListByJoin($where, $field, $order, $page, $pageSize) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if($pageSize==0){
            $result = $this ->alias('o')
                ->where($where)
                ->where('o.is_group_combine','=', '1')
                ->where('o.combine_order_id','=', '0')
                ->field($field)
                ->leftJoin($prefix.'group_combine_activity g','g.combine_id=o.combine_id')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->order($order)
                ->group('o.order_id')
                ->select();

        }else{
            $result = $this ->alias('o')
                ->where($where)
                ->where('o.is_group_combine','=', '1')
                ->where('o.combine_order_id','=', '0')
                ->field($field)
                ->leftJoin($prefix.'group_combine_activity g','g.combine_id=o.combine_id')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->order($order)
                ->group('o.order_id')
                ->page($page,$pageSize)
                ->select();

        }

        return $result;
    }
    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getGroupOrderListByJoin($where, $field, $order, $page, $pageSize) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if($pageSize==0){
            $result = $this ->alias('o')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'group g','g.group_id=o.group_id')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->leftJoin($prefix.'merchant_store s','s.store_id = o.store_id')
                ->order($order)
                ->group('o.order_id')
                ->select();

        }else{
            $result = $this ->alias('o')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'group g','g.group_id=o.group_id')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->leftJoin($prefix.'merchant_store s','s.store_id = o.store_id')
                ->order($order)
                ->group('o.order_id')
                ->page($page,$pageSize)
                ->select();

        }

        return $result;
    }

    /**
     * 根据条件返回优惠组合订单总数
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getGroupCombineOrderCountByJoin($where) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('o')
            ->where($where)
            ->where('o.is_group_combine','=', '1')
            ->where('o.combine_order_id','=', '0')
            ->leftJoin($prefix.'group_combine_activity g','g.combine_id=o.combine_id')
            ->leftJoin($prefix.'user u','u.uid = o.uid')
            ->count();
        return $result;
    }

    /**
     * 得到拼团商品的正在拼团列表
     * @param array $where  查询条件
     * @param array $field  查询字段
     * @param array $order  排序
     * @param array $page  页码
     * @param array $limit  每页显示数量 0为查询所有
     * @return object
     */
    public function getPinOrderList($where=[],$field=true,$order=[],$page=0,$limit=0){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        //need_num还差几人成团
        $fieldNew = 'DISTINCT o.order_id,g.id,g.start_time,(g.complete_num - g.num) as need_num,u.avatar,u.nickname';
        if($field){
            $fieldNew .= ','.$field;
        }
        $sql = $this ->alias('o')
            ->field($fieldNew)
            ->where($where)
            ->where('o.is_head','>', '0')
            ->where('g.status','=', '0')
            ->join($prefix.'group_start g','g.id=o.is_head')
            ->leftJoin($prefix.'user u','u.uid = o.uid')
            ->leftJoin($prefix.'group_buyer_list buy','buy.fid = o.is_head')
            ->order($order);

        if($limit)
        {
            $sql->page($page,$limit);
        }
        $result =  $sql->select();
        return $result;
    }

    /**
     * 订单列表
     * @where $where array 条件
     * @return array|bool|Model|null
     */
    public function getGroupOrderList($where,$order=[],$page=0,$pageSize=0) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        // field字段
        $fieldNew = 'DISTINCT o.*,g.s_name,g.effective_type,g.deadline_time,u.nickname as user_name,u.uid as user_id,u.phone as user_phone';
        // 订单列表
        if($pageSize==0){
            $list = $this ->alias('o')
                ->where($where)
                ->field($fieldNew)
                ->leftJoin($prefix.'group g','g.group_id=o.group_id')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->order($order)
                ->group('o.order_id')
                ->select();
        }else{
            $list = $this ->alias('o')
                ->where($where)
                ->field($fieldNew)
                ->leftJoin($prefix.'group g','g.group_id=o.group_id')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->order($order)
                ->group('o.order_id')
                ->page($page,$pageSize)
                ->select();
        }
        // 总条数
        $total = $this ->alias('o')
            ->where($where)
            ->field($fieldNew)
            ->leftJoin($prefix.'group g','g.group_id=o.group_id')
            ->leftJoin($prefix.'user u','u.uid = o.uid')
            ->order($order)
            ->group('o.order_id')
            ->count();

        // 操作订单列表
        foreach($list as $key=>$v){
            $list[$key]['addTime'] =  empty($v['add_time']) ? $v['add_time'] : date('Y-m-d H:i:s',$v['add_time']);
            $list[$key]['payTime'] =  empty($v['pay_time']) ? $v['pay_time'] : date('Y-m-d H:i:s',$v['pay_time']);
            $list[$key]['s_name'] =  empty($v['order_name']) ? $v['s_name'] : $v['order_name'];
        }
        $returnArr['list'] = $list;
        $returnArr['total'] = $total;
        return $returnArr;
    }

    /**
     * 修改备注
     */
    public function editNoteInfo($note_info, $id) {
        if ($this->where(array('order_id' => $id))->update(array('note_info' => $note_info))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 订单详情
     * @author mrdeng
     * Date Time: 2021/05/28
     */
    public function getGoodsOrderDetail($where){
        $list = $this ->alias('o')
            ->where($where)
            ->field("g.pass_num,o.*,u.nickname,u.phone as user_phone,u.now_money,g.s_name,g.deadline_time,g.effective_type")
            ->join('group g','g.group_id=o.group_id')
            ->join('user u','u.uid=o.uid')
            ->find();
        if(!empty($list)){
            $list=$list->toArray();
        }
        return $list;
    }

    /**
     * @param $where
     * @param string $field
     * @return float
     * 统计某个条件信息
     */
    public function getSum($where, $field = '*')
    {
        return $this->where($where)->sum($field);
    }

    /**
     * 团购券列表
     * @where $where array 条件
     * @return array|bool|Model|null
     */
    public function getGroupCouponList($where,$order=[],$page=0,$pageSize=0,$whereRaw=[]) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        // field字段
        $fieldNew = 'gp.verify_time,o.verify_time as o_verify_time,o.staff_name as o_staff_name,g.s_name as name,gp.group_pass,gp.status,o.real_orderid,u.nickname,u.phone,gp.verify_type,gp.staff_name,g.deadline_time,g.effective_type,o.pay_time,o.order_id,gp.id,o.store_id,o.tuan_type,o.num';
        // 订单列表
        if($pageSize==0){
            $list = $this ->alias('o')
                ->where($where)
                ->field($fieldNew)
                ->leftJoin($prefix.'group g','g.group_id=o.group_id')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->leftJoin($prefix.'group_pass_relation gp','o.order_id = gp.order_id')
                ->order($order)
                ->group('gp.id');
        }else{
            $list = $this ->alias('o')
                ->where($where)
                ->field($fieldNew)
                ->leftJoin($prefix.'group g','g.group_id=o.group_id')
                ->leftJoin($prefix.'user u','u.uid = o.uid')
                ->leftJoin($prefix.'group_pass_relation gp','o.order_id = gp.order_id')
                ->order($order)
                ->group('gp.id')
                ->page($page,$pageSize);
        }
        if($whereRaw){
            $list = $list->whereRaw($whereRaw);
        }
        $list = $list->select();
        // 总条数
        $total = $this ->alias('o')
            ->where($where)
            ->field($fieldNew)
            ->leftJoin($prefix.'group g','g.group_id=o.group_id')
            ->leftJoin($prefix.'user u','u.uid = o.uid')
            ->leftJoin($prefix.'group_pass_relation gp','o.order_id = gp.order_id')
            ->order($order)
            ->group('gp.id')
            ->count();

        // 操作订单列表
        foreach($list as $key=>$v){
            $list[$key]['addTime'] =  empty($v['add_time']) ? $v['add_time'] : date('Y-m-d H:i:s',$v['add_time']);
            $list[$key]['payTime'] =  empty($v['pay_time']) ? $v['pay_time'] : date('Y-m-d H:i:s',$v['pay_time']);
        }
        $returnArr['list'] = $list;
        $returnArr['total'] = $total;
        return $returnArr;
    }
    
    public function getOrderReply($where,$field='',$page=0,$pageSize=0){
        $list = $this ->alias('o')
            ->where($where)
            ->field($field)
            ->join('reply r','r.order_id=o.order_id and r.order_type = 0')
            ->join('user u','u.uid = r.uid','left')
            ->order('r.pigcms_id desc')
            ->paginate($pageSize)->toArray();
        return $list;
    }

    public function getGoodsDetail($orderId)
    {
        return self::where('order_id', $orderId)
            ->field('group_id goods_id, order_name as goods_name, price, num as goods_num')
            ->select();
    }
    
    /**
     * 一个团购券查询核销记录
     */
    public function getGroupOnePassList($where,$field,$order=[])
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $list = $this->alias('o')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'group g','g.group_id=o.group_id')
            ->leftJoin($prefix.'user u','u.uid = o.uid')
            ->order($order)
            ->select()
            ->toArray();
        return $list;

    }
} 