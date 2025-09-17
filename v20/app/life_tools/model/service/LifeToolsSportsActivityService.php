<?php


namespace app\life_tools\model\service;
use app\common\model\db\Merchant;
use app\common\model\db\User;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderBindSportsActivity;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsSportsActivityBindTicket;
use app\life_tools\model\db\LifeToolsSportsActivityPeopleNum;
use app\pay\model\service\PayService;
use think\facade\Db;

use app\life_tools\model\db\LifeToolsSportsActivity;
use think\Model;

class LifeToolsSportsActivityService
{
    public $lifeToolsSportsActivity = null;
    public $LifeToolsOrderBindSportsActivity = null;
    public $LifeToolsSportsActivityPeopleNum = null;
    public $LifeToolsOrder = null;
    public $LifeToolsOrderDetail = null;
    public $LifeToolsSportsActivityBindTicket = null;
    public $User = null;

    public function __construct()
    {
        $this->lifeToolsSportsActivity = new LifeToolsSportsActivity();
        $this->LifeToolsOrderBindSportsActivity = new LifeToolsOrderBindSportsActivity();
        $this->LifeToolsSportsActivityPeopleNum = new LifeToolsSportsActivityPeopleNum();
        $this->LifeToolsOrder = new LifeToolsOrder();
        $this->LifeToolsOrderDetail = new LifeToolsOrderDetail();
        $this->LifeToolsSportsActivityBindTicket = new LifeToolsSportsActivityBindTicket();
        $this->User = new User();
    }
    /**
     * 约战列表
     */
    public function getList($param)
    {
        $where=[['s.is_del','=',0]];
        $where=[['s.mer_id','=',$param['mer_id']]];
        if(!empty($param['content'])){
            array_push($where,['s.title|s.desc|tool.phone|tool.address|tool.label','like','%'.$param['content'].'%']);
        }
        $field="s.title,s.group_type,s.status,s.activity_id";
        $order='s.sort desc,s.activity_id desc';
        $list=$this->lifeToolsSportsActivity->getList($where,$field,$order,$param['page'],$param['pageSize']);
        if(!empty($list['data'])){
           foreach ($list['data'] as $k=>$v){
               $str='';
               if(!empty($v['group_type'])){
                   $arr=explode(',',$v['group_type']);
                   if($arr[0]==1){
                       $str=$str."团长请客";
                   }
                   if($arr[0]==2){
                       $str=$str.' '.'AA';
                   }

                   if(isset($arr[1]) && $arr[1]==1){
                       $str=$str.' '."团长请客";
                   }

                   if(isset($arr[1]) && $arr[1]==2){
                       $str=$str.' '."AA";
                   }
               }
               $list['data'][$k]['num']=(new LifeToolsSportsActivityPeopleNum())->getColumn(['activity_id'=>$v['activity_id']],'num');
               $list['data'][$k]['stadium_package']=(new LifeToolsSportsActivityBindTicket())->getBindTicketList(['s.activity_id'=>$v['activity_id']],'tool.title as name,ticket.title as tickect_name','s.pigcms_id asc')['pin_str'];
               $list['data'][$k]['group_type']=$str;
           }
        }
        return $list;
    }

    /**
     * @return \json
     * 修改状态
     */
    public function updateStatus($param)
    {
        $where=[['activity_id','=',$param['activity_id']]];

        $data=[];
        if(isset($param['status'])){
            $data['status']=$param['status'];
        }

        if(isset($param['is_del'])){
            $data['is_del']=$param['is_del'];
            $this->LifeToolsSportsActivityBindTicket->where('activity_id', $param['activity_id'])->delete();
        }

        if(empty($data)){
            return false;
        }
        $data['update_time']=time();
        $ret=$this->lifeToolsSportsActivity->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 添加编辑保存
     */
    public function addSportsActivity($param)
    {
        //启动事务
        Db::startTrans();
        try {
            $activity['mer_id']=$param['mer_id'];
            $activity['title']=$param['title'];
            if(!in_array($param['leader_back_type'],[0,1,2])){
                throw new \think\Exception("未知的团长退款设置类型", 1003);
            }
            if(!in_array($param['other_back_type'],[0,1,2])){
                throw new \think\Exception("未知的团员退款设置类型", 1003);
            }
            $activity['leader_back_type']=$param['leader_back_type'];
            $activity['leader_back_time']=$param['leader_back_time'];
            $activity['other_back_type']=$param['other_back_type'];
            $activity['other_back_time']=$param['other_back_time'];
            $people_num=[];
            $bind_ticket=[];
            $people_num_primary=[];
            if(!empty($param['group_type'])){
                $param['group_type']=implode(',',$param['group_type']);
            }
            $activity['group_type']=$param['group_type'];
            $activity['desc']=$param['desc'];
            $activity['update_time']=time();
            $activity['is_only_sports_activity']=$param['is_only_sports_activity'] ? 1 : 0;
            if(!empty($param['num'])){
                foreach ($param['num'] as $k2=>$v2){
                    $people_num_primary[]=$v2;
                }
                $param['num']=$people_num_primary;
            }else{
                throw new \think\Exception("缺少约战人数", 1003);
            }

            if($param['activity_id']){
                $retActivity=$param['activity_id'];
                (new LifeToolsSportsActivityPeopleNum())->where(['activity_id'=>$param['activity_id']])->delete();
                (new LifeToolsSportsActivityBindTicket())->where(['activity_id'=>$param['activity_id']])->delete();
                (new LifeToolsSportsActivity())->updateThis(['activity_id'=>$param['activity_id']],$activity);
            }else {
                $activity['add_time']=time();
                $retActivity=(new LifeToolsSportsActivity())->add($activity);
            }

            if($retActivity){
                /**
                 * 运动约战人数设置
                 */
                if(!empty($param['num'])){
                    foreach ($param['num'] as $k=>$v){
                        $num['num']=$v;
                        $num['mer_id']=$param['mer_id'];
                        $num['activity_id']=$retActivity;
                        $people_num[]=$num;
                    }
                    $ret_people_num=(new LifeToolsSportsActivityPeopleNum())->addAll($people_num);
                    if(!$ret_people_num){
                        Db::rollback();
                        throw new \think\Exception("保存失败", 1003);
                    }
                }
                /**
                 * 运动约战绑定门票
                 */
                if(!empty($param['goods'])){
                    foreach ($param['goods'] as $k1=>$v1){
                        $ticket['tools_id']=$v1['tools_id'];
                        $ticket['ticket_id']=$v1['ticket_id'];
                        $ticket['mer_id']=$param['mer_id'];
                        $ticket['activity_id']=$retActivity;
                        $bind_ticket[]=$ticket;
                    }
                    $ret_ticket=(new LifeToolsSportsActivityBindTicket())->addAll($bind_ticket);
                    if(!$ret_ticket){
                        Db::rollback();
                        throw new \think\Exception("保存失败", 1003);
                    }
                }
            }else{
                Db::rollback();
                throw new \think\Exception("保存失败", 1003);
            }
            Db::commit();

            return true;
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception($e->getMessage(), 1003);
        }
    }

    /**
     * 编辑
     */
    public function editSportsActivity($param)
    {
        $where=[['activity_id','=',$param['activity_id']]];
        $detail=(new LifeToolsSportsActivity())->getDetailMsg($where);
        if(empty($detail)){
            return [];
        }else{
            $list['data']=$detail;
            if(!empty($detail['group_type'])){
                $list['data']['group_type']=explode(",",$detail['group_type']);
            }
            $list['num']=(new LifeToolsSportsActivityPeopleNum())->getColumn(['activity_id'=>$detail['activity_id']],'num');
            if(!empty($list['num'])){
                $arr=[];
                foreach ($list['num'] as $k=>$v){
                    $arr[]=$v;
                }
                $list['num']=$arr;
            }
            $list['goods']=(new LifeToolsSportsActivityBindTicket())->getBindTicketList(['s.activity_id'=>$detail['activity_id']],'s.pigcms_id,s.activity_id,s.tools_id,s.ticket_id,tool.title as name,ticket.title as tickect_name,ticket.price','s.pigcms_id asc')['list'];
            if(!empty($list['goods'])){
                foreach ($list['goods'] as $k2=>$v2){
                    $list['goods'][$k2]['sku_info']=[];
                    foreach ($list['num'] as $ks=>$vs){
                        $sku['pin_num']=$vs.'人';
                        if(in_array(1,$list['data']['group_type'])){
                            $sku['group_price']=get_number_format($v2['price']);
                            $sku['aa_price']='----';
                            $sku['aa_group_price']='----';
                        }else{
                            $sku['group_price']='----';
                            $sku['aa_price']='----';
                            $sku['aa_group_price']='----';
                        }

                        if(in_array(2,$list['data']['group_type'])) {
                            $sku['aa_price']=(floor($v2['price'] / $vs * 100) / 100);
                            $sku['aa_group_price']=get_number_format($v2['price'] - (floor($v2['price'] / $vs * 100) / 100) * ($vs - 1));
                        }
                        $list['goods'][$k2]['sku_info'][]=$sku;
                    }
                }
            }

            return $list;
        }
    }

    /**
     *获取订单列表
     * @param $param array
     * @return array
     */
    public function getSportsActivityOrderList($param) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['o.activity_type', '=', 'sports_activity']
        ];
        if (!empty($param['mer_id'])) {
            $where[] = ['o.mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['keyword'])) {
            switch ($param['search_type']) {
                case 1:
                    $search_type = 'r.title';
                    break;
                case 2:
                    $search_type = 'a.title';
                    break;
                case 3:
                    $search_type = 'o.ticket_title';
                    break;
                case 4:
                    $search_type = 'o.real_orderid|o.orderid';
                    break;
                case 5: //手机号
                    $search_type = 'o.phone';
                    break;
            }
            $where[] = [$search_type, 'like', '%' . $param['keyword'] . '%'];
        }
        $result = $this->LifeToolsOrderBindSportsActivity->getList($where, $limit);
        $pay_type_arr = [
            'alipay' => '支付宝', 'wechat' => '微信', 'unionpay' => '银联', 'balance' => '余额抵扣', 'meterPay' => '预交金抵扣','ebankpay'=>'日照银行'
        ];
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['tools_title'] = $v['tools_title'] . '-' . $v['ticket_title'];
                $result['data'][$k]['group_type_val'] = $this->LifeToolsOrderBindSportsActivity->group_type[$v['group_type']] ?? '未知方式';
                $result['data'][$k]['group_status_val'] = $this->LifeToolsOrderBindSportsActivity->group_status[$v['group_status']];
                if($v['order_status'] == 45 && in_array($v['group_status'],[10, 20])){
                    $result['data'][$k]['group_status_val'] .= '退款中';
                }
                if($v['order_status'] == 50 && in_array($v['group_status'],[10, 20])){
                    $result['data'][$k]['group_status_val'] .= '已退款';
                }
                $result['data'][$k]['add_time']    = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                $result['data'][$k]['pay_type_txt'] = $pay_type_arr[$v['pay_type']] ?? '';
            }
        }
        return $result;
    }

    /**
     * 获取发起约战提交页面数据
     */
    public function getSportsActivityDetail($param = [])
    {
        $data = $this->lifeToolsSportsActivity->getOne(['activity_id' => $param['activity_id']]);
        if (empty($data)) {
            throw new \think\Exception('参数有误！');
        }
        $data = $data->toArray();
        $arr  = [
            'activity_id' => $data['activity_id'],
            'desc'        => $data['desc'],
            'is_public'   => [
                [
                    'value' => 1,
                    'title' => '公开'
                ],
                [
                    'value' => 0,
                    'title' => '私密'
                ]
            ]
        ];
        $group_type = explode(',', $data['group_type']);
        if (count($group_type) == 1) {
//            if (!empty($param['group_type']) && $group_type[0] != $param['group_type']) {
//                throw new \think\Exception('不支持该付款方式！');
//            }
            $param['group_type'] = $group_type[0];
            $arr['group_type'] = [[
                'value' => $group_type[0],
                'title' => $group_type[0] == 1 ? '团长请客' : 'AA'
            ]];
        } else if (count($group_type) == 2) {
            $arr['group_type'] = [
                [
                    'value' => 1,
                    'title' => '团长请客'
                ],
                [
                    'value' => 2,
                    'title' => 'AA'
                ]
            ];
        } else {
            throw new \think\Exception('付款方式有误！');
        }
        $arr['people_num'] = $this->LifeToolsSportsActivityPeopleNum->where(['activity_id' => $param['activity_id']])->column('num');
        $param['people_num'] == 0 && $param['people_num'] = $arr['people_num'][0];
        if ($param['people_num'] > 0 && !in_array($param['people_num'], $arr['people_num'])) {
            throw new \think\Exception('不支持该约战人数！');
        }
        $arr['price'] = $param['group_type'] == 2 ? $param['price'] - (floor($param['price'] / $param['people_num'] * 100) / 100) * ($param['people_num'] - 1) : $param['price'];
        return $arr;
    }

    /**
     * 约战列表
     * @param $param array
     */
    public function sportsActivityList($param) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['o.activity_type', '=', 'sports_activity'],
            ['r.group_status', '>=', 10],
            ['r.group_status', '<', 60]
        ];
        if (!empty($param['keyword'])) {
            $where[] = ['a.title', 'like', '%' . $param['keyword'] . '%'];
        }
        if (!empty($param['is_my']) && $param['is_my'] == 1) {
            $orderIds = $this->LifeToolsOrder->where(['uid' => $param['uid']])->where('activity_type', 'sports_activity')->column('order_id');
            $where[] = ['r.order_id', 'in', $orderIds];
        }else{
            $where[] = ['r.is_public', '=', 1];
            $where[] = ['r.leader_order_id', '=', 0];
        }
        if ($param['status'] == 2) {
            $where[] = ['r.group_status', 'exp', Db::raw('= 20 or o.ticket_time < ' . date('Y-m-d'))];
        } else {
            $where[] = ['r.group_status', 'exp', Db::raw('= 10 and o.ticket_time >= ' . date('Y-m-d'))];
            $where[] = ['act.status', '=', 1];
            $where[] = ['act.is_del', '=', 0];
        }
        $field = 'r.*,r.leader_order_id,r.group_price as total_price,o.order_status,o.ticket_title,o.uid,o.nickname,o.phone,o.ticket_time,o.add_time,a.title as tools_title,a.cover_image,a.long,a.lat';
        // $order = 'o.add_time asc';
        $order = 'o.ticket_time DESC,o.add_time DESC';
        $result = $this->LifeToolsOrderBindSportsActivity->getList($where, $limit, $field, $order);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['cover_image'] = replace_file_domain($v['cover_image']);
                $result['data'][$k]['distance']    = '';
                if (!empty($param['long']) && !empty($param['lat']) && empty($v['distance'])) { //计算距离
                    $result['data'][$k]['distance'] = (new LifeToolsService())->getDistance($param['long'], $param['lat'], $v['long'], $v['lat']);
                    if ($result['data'][$k]['distance'] < 1) {
                        $result['data'][$k]['distance'] = $result['data'][$k]['distance'] * 1000 . 'm';
                    } else {
                        $result['data'][$k]['distance'] .= 'km';
                    }
                }
//                $v['group_type'] == 1 && $v['uid'] != $param['uid'] && $result['data'][$k]['total_price'] = 0;
                $result['data'][$k]['group_type_val']   = $this->LifeToolsOrderBindSportsActivity->group_type[$v['group_type']] ?? '未知方式';
                $result['data'][$k]['group_status_val'] = $this->LifeToolsOrderBindSportsActivity->group_status[$v['group_status']] ?? '未知状态';
                $result['data'][$k]['add_time'] = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                $orderId = $v['leader_order_id'] ? : $v['order_id'];
                $uids = $this->LifeToolsOrderBindSportsActivity->getList([['r.leader_order_id' ,'=', $orderId],['r.group_status', 'IN', [0,10,20,50]]], 0, 'o.uid', 'o.add_time',[['r.order_id','=',$orderId],['r.group_status', 'IN', [0,10,20,50]]]);
                $uids = array_column($uids, 'uid');
                $avatar = $this->User->where([['uid', 'in', $uids]])->field('avatar,uid')->select()->toArray();
                $img = [];
                foreach ($avatar as $ak => $av) {
                    if (empty($av['avatar'])) {
                        $img[$av['uid']] = cfg('site_url') . '/static/images/user_avatar.jpg';
                    } else {
                        $img[$av['uid']] = replace_file_domain($av['avatar']);
                    }
                }
                $avatarImg = [];
                foreach ($uids as $uid){
                    $avatarImg[] = $img[$uid] ?? cfg('site_url') . '/static/images/user_avatar.jpg';
                }
                $result['data'][$k]['avatar']  = $avatarImg;
                $result['data'][$k]['is_add']  = 0; //当前用户是否已加入该拼团，0=未加入1=发起人2=参与人
                if (in_array($param['uid'], $uids)) {
                    // $result['data'][$k]['is_add'] = $v['uid'] == $param['uid'] ? 1 : 2;
                    $result['data'][$k]['is_add'] = $v['leader_order_id'] == 0 ? 1 : 2;
                }
                if ($param['status'] == 1) {
                    $result['data'][$k]['btn_value'] = 1;
                    $result['data'][$k]['btn_title'] = '立即拼团';
                } else if ($v['group_status'] == 20 && strtotime($v['ticket_time']) >= strtotime(date('Y-m-d'))) {
                    $result['data'][$k]['btn_value'] = 2;
                    $result['data'][$k]['btn_title'] = '已成团';
                } else {
                    $result['data'][$k]['btn_value'] = 3;
                    $result['data'][$k]['btn_title'] = '已结束';
                }
            }
        }
        return $result;
    }

    /**
     * 约战详情
     * @param $param array
     */
    public function sportsActivityDetail($param) {
        $field = 'r.*,r.group_price as total_price,o.order_status,o.real_orderid,o.orderid,o.ticket_id,o.ticket_title,o.uid,o.nickname,o.phone,o.ticket_time,o.add_time,a.title as tools_title,a.cover_image,a.long,a.lat,a.time_txt,a.address,a.phone as tools_phone,a.tools_id,t.start_time';
        $result = $this->LifeToolsOrderBindSportsActivity->getDetail(['r.order_id' => $param['order_id']], $field);
        if (empty($result)) {
            throw new \think\Exception('参数有误！');
        }
        if($result['leader_order_id']){//查询团长订单
            $result = $this->LifeToolsOrderBindSportsActivity->getDetail(['r.order_id' => $result['leader_order_id']], $field);
        }
        if($result['group_status'] == 40 || $result['group_status'] == 60){
            throw new \think\Exception('约战已解散！');
        }
        $result['cover_image']      = replace_file_domain($result['cover_image']);
        $result['group_type_val']   = $this->LifeToolsOrderBindSportsActivity->group_type[$result['group_type']] ?? '未知方式';
        $result['group_status_val'] = $this->LifeToolsOrderBindSportsActivity->group_status[$result['group_status']] ?? '未知状态';
        $result['add_time'] = !empty($result['add_time']) ? date('Y-m-d H:i:s', $result['add_time']) : '无';
        //如果自己不是团长，则替换成团长的order_id
        $nowOrder = $this->LifeToolsOrderBindSportsActivity->where('order_id', $result['order_id'])->find();
        $order_id = $result['order_id'];
        if($nowOrder->leader_order_id != 0){
            $order_id = $nowOrder->leader_order_id;
        }
//        $uids = $this->LifeToolsOrderBindSpor tsActivity->getList([['r.leader_order_id', '=', $order_id], ['r.group_status', '<>', 30]], 0, 'o.uid', 'o.add_time asc');
        $uids = $this->LifeToolsOrderBindSportsActivity->getList([['r.leader_order_id', '=', $order_id], ['r.group_status', 'IN', [0,10,20,50]]], 0, 'o.uid', 'o.add_time asc');
        $uids = array_column($uids, 'uid');
        $avatar = $this->User->where([['uid', 'in', $uids]])->column('avatar');
        foreach ($avatar as $ak => $av) {
            if (empty($av)) {
                $avatar[$ak] = cfg('site_url') . '/static/images/user_avatar.jpg';
            } else {
                $avatar[$ak] = replace_file_domain($av);
            }
        }

        $field = 'o.uid';
        $leader_uid = $this->LifeToolsOrderBindSportsActivity->getDetail(['r.order_id' => $order_id], $field);
        $leader_avatar = $this->User->where([['uid', '=', $leader_uid['uid']]])->value('avatar') ?? '';
        $leader_avatar = !empty($leader_avatar) ? replace_file_domain($leader_avatar) : cfg('site_url') . '/static/images/user_avatar.jpg';
        $result['avatar']    = array_merge([$leader_avatar], $avatar); //团长头像放在第一个
//        $result['real_num']  = $result['num'] - $result['group_num']; //剩余邀请人数
        $result['real_num']  = $result['num'] - count($uids) - 1; //剩余邀请人数
        $result['real_time'] = strtotime($result['ticket_time']) + 86400 - time() < 0 ? 0 : strtotime($result['ticket_time']) + 86400 - time(); //剩余时间
        $result['is_add']    = 0; //当前用户是否已加入该拼团，0=未加入1=发起人2=参与人
        if (in_array($param['uid'], array_merge([$result['uid']], $uids))) {
            $result['is_add'] = $result['uid'] == $param['uid'] ? 1 : 2;
        }
        $payInfo = []; // 获得支付信息
        if ($result['orderid']) {
            $payInfo = (new PayService())->getPayOrderData([$result['orderid']]);
            $payInfo = $payInfo[$result['orderid']] ?? [];
            $payInfo['pay_type_chanel'] = '';
            if (isset($payInfo['pay_type'])) {
                $payInfo['pay_type_chanel'] = ($payInfo['pay_type'] ? $payInfo['pay_type_txt'] : '').($payInfo['channel'] ? '('.$payInfo['channel_txt'].')' : '');
            }
        }
        $result['pay_info'] = $payInfo;
        //判断是否可以退出
        $result['is_back'] = 0;//默认不允许退出
        if($result['is_add'] > 0){
            //查询约战配置
            $sportsActivitySet = $this->lifeToolsSportsActivity->getOne(['activity_id'=>$result['activity_id']]);
            if(!$sportsActivitySet){
                throw new \think\Exception('未查询到约战配置信息！');
            }
            $startTime = strtotime($result['ticket_time'].' '.$result['start_time']);
            unset($result['start_time']);
            //查询价格日历，查到开始时间
            if($result['is_add'] == 1 &&
                ($sportsActivitySet['leader_back_type'] == 1 || ($sportsActivitySet['leader_back_type'] == 2 && $startTime-time() >= $sportsActivitySet['leader_back_time'] * 60 * 60))){//团长
                $result['is_back'] = 1;
            }elseif($result['is_add'] == 2 &&
                ($sportsActivitySet['other_back_type'] == 1 || ($sportsActivitySet['other_back_type'] == 2 && $startTime-time() >= $sportsActivitySet['other_back_time'] * 60 * 60))){//团员
                $result['is_back'] = 1;
            }
        }
        return $result;
    }

    /**
     * 加入约战
     */
    public function addSportsActivityOrder($leader_order_id, $userInfo) {
        $activityDetail = $this->sportsActivityDetail(['order_id' => $leader_order_id, 'uid' => $userInfo['uid']]);
        $orderNo  = (new PayService())->createOrderNo();
        $now_time = time();
//        $price    = $activityDetail['group_type'] == 2 ? get_format_number($activityDetail['total_price'] / $activityDetail['num']) : 0;
        $price    = $activityDetail['total_price'];
        $order    = [
            'real_orderid'  => $orderNo,
            'orderid'       => $orderNo,
            'mer_id'        => $activityDetail['mer_id'],
            'tools_id'      => $activityDetail['tools_id'],
            'ticket_id'     => $activityDetail['ticket_id'],
            'ticket_title'  => $activityDetail['ticket_title'],
            'num'           => 1,
            'uid'           => $userInfo['uid'],
            'nickname'      => $userInfo['nickname'],
            'phone'         => $userInfo['phone'],
            'ticket_time'   => $activityDetail['ticket_time'],
            'member_id'     => 0,
            'total_price'   => $price,
            'price'         => $price,
            'coupon_id'     => 0,
            'coupon_price'  => 0,
            'card_id'       => 0,
            'card_price'    => 0,
            'order_status'  => 10,
            'last_time'     => $now_time,
            'add_time'      => $now_time,
            'activity_type' => 'sports_activity'
        ];
        Db::startTrans();
        try {
            $order_id  = $this->LifeToolsOrder->add($order);
            $detailArr = [
                'order_id'  => $order_id,
                'old_price' => $price,
                'price'     => $price,
                'code'      => createRandomStr(12),
                'status'    => 1,
                'last_time' => $now_time
            ];
            $this->LifeToolsOrderDetail->add($detailArr);
            $this->LifeToolsOrderBindSportsActivity->add([
                'mer_id'          => $order['mer_id'],
                'activity_id'     => $activityDetail['activity_id'],
                'order_id'        => $order_id,
                'leader_order_id' => $leader_order_id,
                'title'           => $activityDetail['title'],
                'num'             => $activityDetail['num'],
                'group_num'       => 0, //支付时更新
                'group_type'      => $activityDetail['group_type'],
                'is_public'       => $activityDetail['is_public'],
                'group_status'    => 0,
            ]);
            (new LifeToolsOrderService())->changeOrderStatus($order_id, 10, '订单下单成功');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return [
            'order_type' => 'lifetools',
            'order_id'   => $order_id
        ];
    }

    /**
     * 退出约战
     */
    public function backSportsActivityOrder($leader_order_id, $userInfo)
    {
        //查询约战订单信息
        $activityDetail = $this->sportsActivityDetail(['order_id' => $leader_order_id, 'uid' => $userInfo['uid']]);
        if(!$activityDetail){
            throw new \think\Exception('未查询到约战信息！');
        }
        //判断当前是否可以退出
        if($activityDetail['is_back'] == 0){
            throw new \think\Exception('不满足退款条件，无法退款！');
        }
        //判断希望退出约战的是团长还是团员
        $leader = $activityDetail['is_add'];
        $status = $activityDetail['group_status'] == 20 ? 40 : 60;//判断是否成团
        //查询全部约战进行中的订单
        $orderInfo = $this->LifeToolsOrderBindSportsActivity->where(['order_id|leader_order_id'=>$leader_order_id])->where([['group_status','>=',10],['group_status','<=',20]])->field('order_id')->select();
        if(!$orderInfo){
            throw new \think\Exception('未查询到约战订单！');
        }
        $orderIdAry = [];
        $openIdAry = [];
        foreach ($orderInfo as $v){
            $orderIdAry[] = $v['order_id'];
        }
        if($leader == 1){//团长退出
            //解散约战//全部退款
            //查询需要退款的订单
            $where[] = ['order_id','IN',$orderIdAry];
            $orderIdArr = $orderIdAry;
            $note = '解散约战';
            //查询所有人的openid
            $userInfo = $this->LifeToolsOrderBindSportsActivity->getUserInfo($leader_order_id);
        }elseif($leader ==2){//团员退出
            //退出约战//退款
            //查询需要退款的订单
            $orderInfo = $this->LifeToolsOrderBindSportsActivity->getOtherOrder($leader_order_id,$userInfo['uid']);
            if(!$orderInfo){
                throw new \think\Exception('未查询到约战订单！');
            }
            $orderId = $orderInfo['order_id'];
            $where[] = ['order_id','=',$orderId];
            $orderIdArr = [$orderId];
            $note = '退出约战';
        }else{
            throw new \think\Exception('未加入约战，无法退款！');
        }


        Db::startTrans();
        try {
            $back = $this->LifeToolsOrderBindSportsActivity->where($where)->update(['group_status'=>$status]);
            if(!$back){
                throw new \think\Exception('操作失败！');
            }
            $dec = $this->LifeToolsOrderBindSportsActivity->setDec([['order_id','IN',$orderIdAry]], 'group_num', 1); //减少
            if(!$dec){
                throw new \think\Exception('操作失败！');
            }
            //修改订单退款金额
            if(!$orderIdArr){
                return true;
            }
            $orderIdStr = implode(',',$orderIdArr);
            Db::execute("UPDATE `pigcms_life_tools_order` SET `refund_money` = `price` WHERE `order_id` IN (".$orderIdStr.")");
            (new LifeToolsOrderService())->agreeRefund($orderIdArr,$note);
            if($leader == 1){
                //发送公众号消息
                // 获得用户的openID
                if ($openIdAry) {
                    foreach ($openIdAry as $openid){
                        // 通过微信公众号发送审核通知
                        $msgDataWx = [
                            'href' => '',
                            'wecha_id' => $openid,
                            'first' => $activityDetail['title'].'解散',
                            'keyword1' => cfg('site_name'),
                            'keyword2' => $activityDetail['title'].'解散',
                            'keyword3' => date("Y-m-d H:i"),
                            'remark' => '',

                        ];
                        $res = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
                    }
                }
            }elseif($leader == 2 && $activityDetail['group_status'] == 20){//团员退出，已成团的修改为未成团
                $this->LifeToolsOrderBindSportsActivity->where(['order_id'=>$leader_order_id])->update(['group_status'=>10]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }
}