<?php

class Group_combine_activityModel extends Model
{


    /**
     * 获得团购商品参加的活动
     * @param array $param
     */
    public function getActivityByGroup($param = [])
    {
        $returnArr = [];
        $groupId = $param['group_id'];
        $join = ' as a join '.C('DB_PREFIX').'group_combine_activity_goods as g on g.combine_id=a.combine_id and g.group_id='.$groupId;
        $where = [
            'a.status' => 1,
            'a.start_time' => ['lt' ,time()],
            'a.end_time' => ['gt' ,time()],
            '_string' => 'a.stock_num > a.sell_count'
        ];
        $order = 'a.sell_count DESC';
        $res = $this->join($join)
            ->where($where)
            ->order($order)
            ->find();
        return $res;
    }

    /**
     * 通过消费码搜索团购组合
     * @param array $param
     */
    public function getActivityOrderBySearch($param = [])
    {
        $returnArr = [];
        $groupPass = $param['group_pass'] ?? 0;
        $orderId = $param['order_id'] ?? 0;
        $realOrderid = $param['real_orderid'] ?? 0;
        $merId = $param['mer_id'] ?? 0;
        $storeId = $param['store_id'] ?? 0;
        if((empty($groupPass) && empty($orderId) && empty($realOrderid)) || empty($merId) || empty($storeId)){
            $returnArr['error'] = 1;
            $returnArr['msg'] = '缺少参数';
            return $returnArr;
        }

        $join = ' as o join '.C('DB_PREFIX').'group_combine_activity_order_goods as g on g.order_id=o.order_id and g.mer_id='.$merId.' join '.C('DB_PREFIX').'group_combine_activity as a on o.combine_id=a.combine_id';

        $where = [
            'o.status' => ['in','0,5'],
            'o.paid' => 1,
        ];
        if($orderId){
            $where['o.order_id'] = $orderId;
        }elseif ($groupPass){
            $where['o.group_pass'] = $groupPass;
        }elseif ($realOrderid){
            $where['o.real_orderid'] = $realOrderid;
        }
        $field = 'o.*,a.title,g.group_id,g.use_count,g.used_count,g.name as group_name,g.cost_price,g.id as goods_id,g.group_id';
        $order = M('Group_order')->join($join)
            ->field($field)
            ->where($where)
            ->find();
        if($order){
            // 验证店铺
            $bindStore = array_column(M('Group_store')->where(['group_id'=>$order['group_id']])->select(), 'store_id');
            if(!in_array($storeId, $bindStore)){
                $returnArr['data'] = [];
                $returnArr['error'] = 1;
                $returnArr['msg'] = '该团购未绑定此店铺';
                return $returnArr;
            }

            // 验证是否过期
            if($order['pay_time'] + $order['can_use_day']*86400 < time()){
                $returnArr['data'] = [];
                $returnArr['error'] = 1;
                $returnArr['msg'] = '团购使用有效期已过，不能再使用';
                return $returnArr;
            }

            // 验证总次数次数
            $used_count_total = $this->getOrderUsedCount($order['order_id']);
            if($order['can_use_count'] <= $used_count_total ){
                $returnArr['data'] = [];
                $returnArr['error'] = 1;
                $returnArr['msg'] = '团购次数已使用完';
                return $returnArr;
            }

            // 验证使用次数
            if($order['use_count'] > 0 && $order['use_count'] <= $order['used_count'] ){
                $returnArr['data'] = [];
                $returnArr['error'] = 1;
                $returnArr['msg'] = '该团购使用次数已达上限';
                return $returnArr;
            }
        }else{
            $returnArr['error'] = 1;
            $returnArr['msg'] = '订单不存在';
            return $returnArr;
        }
        $returnArr['data'] = $order;
        $returnArr['error'] = 0;
        return $returnArr;
    }

    /**
     * 验证消费
     * @param array $param
     */
    public function groupCombineVerify($param = [])
    {
        $returnArr = [];
        $merId = $param['mer_id'];
        $orderId = $param['order_id'];

        $returnArr['msg'] = '';
        $returnArr['error'] = 0;
        if(empty($merId) || empty($orderId)){
            $returnArr['msg'] = '缺少参数';
            $returnArr['error'] = 0;
            return $returnArr;
        }

        // 验证订单是否可消费
        $nowOrder = $this->getActivityOrderBySearch($param);
        if($nowOrder['error'] == 1){
            return $nowOrder;
        }

        // 订单详情
        $nowOrder = $nowOrder['data'];

        $condition_group_order['order_id'] = $nowOrder['order_id'];

        // 是否完成订单
        $verify_all = 0;

        // 查看已核销次数，如果已核销次数等于总的可使用次数，则把状态改为为已完成 2
        $usedCountTotal = $this->getOrderUsedCount($nowOrder['order_id']);
        if($usedCountTotal+1 >= $nowOrder['can_use_count']) {
            $data_group_order['status'] = '1';
            $verify_all = 1;
        }

        $data_group_order['store_id'] = $this->store['store_id'];
        $data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
        $data_group_order['last_staff'] = $param['last_staff'];
        $data_group_order['staff_name'] = $param['staff_name'];
        $data_group_order['verify_time'] = $_SERVER['REQUEST_TIME'];
        $data_group_order['combine_used_count'] = $nowOrder['combine_used_count'] + 1;
        $data_group_order['last_random'] = mt_rand(100000000,999999999);
        $data_group_order['group_pass'] = '';
        fdump_sql(['order_id'=>$nowOrder['order_id'],'usedCountTotal'=>$usedCountTotal,'verify_all'=>$verify_all,'data_group_order'=>$data_group_order],'group_combine');
        if(!D('Group_order')->where($condition_group_order)->data($data_group_order)->save()){
            $returnArr['msg'] = '验证失败，请稍后重试';
            $returnArr['error'] = 1;
            return $returnArr;
        }

        // 修改子订单信息
        $where = [
            'id' => $nowOrder['goods_id']
        ];
        M('Group_combine_activity_order_goods')->where($where)->setInc('used_count',1);

        // 获得主订单信息
        $where = [
            'order_id' => $param['order_id'],
        ];
        $order = M('Group_order')->where($where)->find();

        // 生成新的子订单
        $dataGroupOrder = $order;
        unset($dataGroupOrder['order_id']);
        $dataGroupOrder['status'] = 2;
        $dataGroupOrder['order_name'] = $nowOrder['group_name'] . '【' . $nowOrder['title'] .'】';
        $dataGroupOrder['combine_order_id'] = $nowOrder['order_id'];
        $dataGroupOrder['price'] = $nowOrder['cost_price'];
        $dataGroupOrder['total_money'] = $nowOrder['cost_price'];
        $dataGroupOrder['real_orderid'] = build_real_orderid($nowOrder['uid']);
        $dataGroupOrder['can_use_day'] = 0;
        $dataGroupOrder['can_use_count'] = 1;
        $dataGroupOrder['mer_id'] = $merId;
        $dataGroupOrder['store_id'] = $param['store_id'];
        $dataGroupOrder['group_id'] = $nowOrder['group_id'];
        $dataGroupOrder['group_pass'] = $nowOrder['group_pass'];
        $dataGroupOrder['last_staff'] = $param['last_staff'];
        $dataGroupOrder['staff_name'] = $param['staff_name'];
        $dataGroupOrder['use_time'] = time();
        $dataGroupOrder['verify_time'] = time();
        $dataGroupOrder['last_random'] = mt_rand(100000000,999999999);
        $childOrderId = D('Group_order')->add($dataGroupOrder);

        //验证增加商家余额 使用子订单对账
        $dataGroupOrder['order_type'] = 'group_combine';
        $dataGroupOrder['order_id'] = $childOrderId;
        $dataGroupOrder['desc']=L_('验证X1--优惠组合订单X2的消费码X3记入收入',['X1'=>C('config.group_alias_name'),'X2'=>$dataGroupOrder['real_orderid'],'X3'=>$dataGroupOrder['group_pass']]);
        D('SystemBill')->bill_method($dataGroupOrder['is_own'],$dataGroupOrder);

        // 修改总订单状态
        if($data_group_order['status'] == 1){
            D("System_order")->save_reg_order_info('group',$nowOrder['order_id'],array(),2);
        }

        // 生成新的消费码
        $data = D('Group_order')->createGroupPass($nowOrder);
        D('Group_order')->where($condition_group_order)->data($data)->save();

        // 通知用户
        D('Group_order')->group_notice($nowOrder,$verify_all);

        $returnArr['data'] = $dataGroupOrder;
        $returnArr['error'] = 0;
        $returnArr['msg'] = L_('验证消费成功');
        return $returnArr;
    }


    /**
     * 获得订单已消费的次数
     * @param array $param
     */
    public function getOrderUsedCount($orderId)
    {
        $where = [
            'combine_order_id' => $orderId,
        ];
        $count = M('Group_order')->where($where)->count();
        return $count;
    }

    /**
     * 优惠组合订单添加一条支付记录
     * @param array $param
     */
    public function addBuyLog($param = [])
    {
        $where = [
            'combine_id' => $param['combine_id'],
        ];
        $combine = $this->where($where)->find();

        $data = [
            'combine_id' => $param['combine_id'],
            'order_id' => $param['order_id'],
            'user_name' => $param['nickname'],
            'avatar' => $param['avatar'],
            'group_name' => $combine['title'],
            'price' => $param['price'],
            'pay_time' => $param['pay_time'],
            'create_time' => time(),
        ];
        $res = M('Group_combine_activity_buy_log')->add($data);
        return true;
    }

    /**
     * 获得详情
     * @param array $param
     */
    public function getdetailByCombineId($combineId)
    {
        $where = [
            'combine_id' => $combineId,
        ];
        $detail =$this->where($where)->find();
        return $detail;
    }

    /**
     * 分享获得佣金
     * @param array $nowOrder
     */
    public function sendSpreadMoney($nowOrder)
    {
        fdump($nowOrder,'sendSpreadMoney');
        if(empty($nowOrder['share_uid']) || $nowOrder['spread_money'] <=0){
           return false;
        }

        $user = D('User')->get_user($nowOrder['uid']);

        $combineDetail = $this->getdetailByCombineId($nowOrder['combine_id']);


        $data = [
            'combine_id' => $nowOrder['combine_id'],
            'order_id' => $nowOrder['order_id'],
            'group_name' => $combineDetail['title'],
            'avatar' => $user['avatar'],
            'user_name' => $nowOrder['nickname'],
            'uid' => $nowOrder['share_uid'],
            'spread_money' => $nowOrder['spread_money'],
            'spread_num' => 1,
            'create_time' => time(),
        ];
        fdump($data,'sendSpreadMoney',1);
        $res = M('Group_combine_activity_spread_list')->add($data);

        if(!$res){
            return false;
        }

        // 给分享者加钱
        $desc = L_("X1用户购买了你分享的X2获得佣金",array("X1" => $user['nickname'],"X2" =>$combineDetail['title'])); 
        $res = D('User')->add_money($nowOrder['share_uid'], $nowOrder['spread_money'], $desc);
        fdump($res,'sendSpreadMoney',1);
        if($res['error_code']){
            return false;
        }

        return true;
    }
}

?>