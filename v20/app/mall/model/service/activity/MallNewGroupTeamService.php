<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallNewGroupAct;
use app\mall\model\db\MallNewGroupOrder;
use app\mall\model\db\MallNewGroupTeam;
use app\mall\model\db\MallNewGroupTeamUser;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallRobotList;
use app\mall\model\db\User;
use app\mall\model\service\MallOrderService;
use think\Model;

class MallNewGroupTeamService
{
    /**
     * @param $uid
     * @param $tid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 获取用户的在团队的信息
     */
  public function getIsInTeamStatus($uid,$tid){
      $where=[['user_id','=',$uid],['id','=',$tid]];
      $field="*";
      $list=(new MallNewGroupTeam())->getTeamList($where,$field);
      return $list;
  }

    /**
     * @param $tid
     * @return array|\think\Model|null
     * 获取团队新新
     */
  public function getOne($tid){
      if(empty($tid)){
          throw new \think\Exception(L_('团队id参数缺失'), 1003);
      }
      $list=(new MallNewGroupTeam())->getOne($tid);
      $where=[['r.tid','=',$tid],['s.tid','=',$tid],['t.status','=',0],['r.status','<',2]];
      $user_list=(new MallNewGroupTeamUser())->getTeamUserList($where,$field="*");
      $return['status']=0;//默认未占满位置
      $return['pay_mans']=0;
      if($list['complete_num']<=$user_list){
          $return['status']=1;//已占满位置
      }
      $where1=[['r.tid','=',$tid],['s.tid','=',$tid],['r.act_id','=',$list['act_id']],['t.status','=',0],['r.status','=',0]];
      $user_list1=(new MallNewGroupTeamUser())->getTeamUserList($where1,$field="*");
      $return['pay_mans']=$user_list1;//正在支付人数
      $return['user_id']=$list['user_id'];//拼主id
      return $return;
  }

    /**
     * 获取团长拼团订单列表
     * @param $params
     */
    public function getGroupOrderList($params){
        $page = $params['page']?:0;
        $pageSize = $params['pageSize']?:10;
        $keywords = $params['keywords']?:'';
        $start_date = $params['start_date']?:'';
        $end_date = $params['end_date']?:'';
        $status = $params['status'];
        $active_id = $params['active_id'];
        $mer_id = $params['mer_id'];
        $search_type = $params['search_type'];
        if(!$active_id){
            throw new \think\Exception(L_('活动不存在'));
        }
        $where = [];
        $where[] = ['t.act_id','=',$active_id];
        $where[] = ['t.mer_id','=',$mer_id];
        if($start_date&&$end_date){
            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date.' 23:59:59');
            $where[] = ['t.start_time','between',[$start_date,$end_date]];
        }
        if($keywords){
            if($search_type=='username'){
                $where[] = ['u.nickname','like','%'.$keywords.'%'];
            }elseif($search_type=='orderno'){
                $where[] = ['o.order_no','=',$keywords];
            }
        }
        if(in_array($status,[0,1,2])){
            $where[] = ['t.status','=',$status];
        }
        
        $list = (new MallNewGroupTeam())->getTeamOrderList($where,'',$page,$pageSize);
        
        $data = $list['data'];
        $stats_txt = [0=>'拼团中',1=>'已成团',2=>'已解散'];
        foreach ($data as $key=>$item){
            $data[$key]['start_time'] = $item['start_time']?date('Y-m-d H:i:s',$item['start_time']):'';
            $data[$key]['end_time'] = $item['end_time']?date('Y-m-d H:i:s',$item['end_time']):'';
            $data[$key]['money'] = (new MallNewGroupOrder())->alias('go')->join('mall_order o','go.order_id = o.order_id')->where(['go.tid'=>$item['id']])->sum('money_goods');
            $data[$key]['stats_txt'] = $stats_txt[$item['status']]??'未知';
            $data[$key]['avatar'] = $item['avatar']?replace_file_domain($item['avatar']):'';
        }
        $list['data'] = $data;
        return $list;
    }

    /**
     * 手动机器人参团
     * @param $params
     */
    public function joinRobot($params){
        $id = $params['id'];
        $mer_id = $params['mer_id'];
        if(!$id){
            throw new \think\Exception(L_('参数错误'));
        }
        $where[]=['m.start_time','<',time()];
        $where[]=['m.end_time','>=',time()];
        $where[]=['t.status','=',0];
        $where[]=['t.start_time','<',time()];
        $where[]=['t.id','=',$id];
        $where[]=['t.mer_id','=',$mer_id];
        $list=(new MallNewGroupAct())->getGroupTeamMasg($where,$field="*");
        if(empty($list)){
            throw new \think\Exception(L_('当前团队不符合'));
        }
        $this->robotJoinTeam($list,1,0);
        return true;
    }

    /**
     * 拼团成员
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTeamUser($params){
        $id = $params['id']?:0;
        $mer_id = $params['mer_id'];
        if(!$id){
            throw new \think\Exception(L_('拼团不存在'));
        }
        $team = (new MallNewGroupTeam())->where(['id'=>$id,'mer_id'=>$mer_id])->findOrEmpty()->toArray();
        if(!$team){
            throw new \think\Exception(L_('拼团不存在'));
        }

        $where[] = ['tid','=',$id];
        $list = (new MallNewGroupTeamUser())->where($where)->order('id asc')->select()->toArray();
        foreach ($list as $key=>$item){
            $list[$key]['add_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
            $list[$key]['member_txt'] = '成员';
            $list[$key]['order_type'] = '';
            $list[$key]['refund_id'] = 0;
            $list[$key]['user_name'] = '';
            $list[$key]['phone'] = '';
            $list[$key]['avatar'] = '';
            if($item['type']==0){
                $user = (new User())->where(['uid'=>$item['user_id']])->field('nickname,phone,avatar')->findOrEmpty()->toArray();
                $order = (new MallOrder())->where(['order_id'=>$item['order_id']])->value('order_id,goods_activity_type');
                if (stripos($order['goods_activity_type'], 'bargain') !== false) {
                    $list[$key]['order_type_txt'] = '砍价订单';
                    $list[$key]['order_type'] = 'bargain';
                } elseif (stripos($order['goods_activity_type'], 'group') !== false) {
                    $list[$key]['order_type_txt'] = '拼团订单';
                    $list[$key]['order_type'] = 'group';
                } elseif (stripos($order['goods_activity_type'], 'limited') !== false) {
                    $list[$key]['order_type_txt'] = '秒杀订单';
                    $list[$key]['order_type'] = 'limited';
                } elseif (stripos($order['goods_activity_type'], 'prepare') !== false) {
                    $list[$key]['order_type_txt'] = '预售订单';
                    $list[$key]['order_type'] = 'prepare';
                } elseif (stripos($order['goods_activity_type'], 'periodic') !== false) {
                    $list[$key]['order_type_txt'] = '周期购订单';
                    $list[$key]['order_type'] = 'periodic';
                } else {
                    $list[$key]['order_type_txt'] = '普通订单';
                    $list[$key]['order_type'] = '';
                }
                $list[$key]['user_name'] = $user['nickname']?:'';
                $list[$key]['phone'] = $user['phone']?phone_show($user['phone']):'';
                $list[$key]['avatar'] = $user['avatar']?replace_file_domain($user['avatar']):'';
            }else{
                $user_robot = (new MallRobotList())->where(['id'=>$item['user_id']])->field('robot_name,avatar')->findOrEmpty()->toArray();
                $list[$key]['user_name'] = $user_robot['robot_name']?:'';
                $list[$key]['avatar'] = $user_robot['avatar']?replace_file_domain($user_robot['avatar']):'';
            }
            if($team['user_id']==$item['user_id']){
                $list[$key]['member_txt'] = '团长';
            }
        }
        return $list;
    }

    /**
     * 机器人参团
     * @param $list
     * @param int $is_throw_error
     * @return bool
     * @throws \think\Exception
     */
    public function robotJoinTeam($list,$is_throw_error=0,$is_plan=1){
        foreach ($list as $key=>$val){
            if($val['team_end_time']<time()){
                if($is_throw_error){
                    throw new \think\Exception(L_('成团时间已过！'));
                }else{
                    continue;
                }
            }
            $status = (new MallNewGroupTeam())->where(['id'=>$val['team_id']])->value('status');
            if($status!=0){
                continue;
            }
            $where2=[['mer_id','=',$val['mer_id']]];
            $count=(new MallRobotList())->getRobotCount($where2);//机器数量
            if($count<($val['complete_num']-$val['num'])){//机器人数量少
                if($is_throw_error){
                    throw new \think\Exception(L_('机器人数量不足'));
                }else{
                    continue;
                }
            }
            $over_num=$val['complete_num']-$val['num'];//暂时缺几人
            $where=[
                ['s.tid','=',$val['team_id']],
                ['s.status','=',1],
                ['s.act_id','=',$val['group_act_id']]
            ];
            $pay_time_msg=(new MallNewGroupOrder())->getOrderList($where,"s.id,od.pay_time,od.status","od.pay_time desc");
            if(!$pay_time_msg){
                if($is_throw_error){
                    throw new \think\Exception(L_('订单未支付，无法成团！'));
                }else{
                    continue;
                }
            }
            if(($pay_time_msg['status']>=50&&$pay_time_msg['status']<60)||$pay_time_msg['status']==70){
                (new MallNewGroupOrder())->where(['id'=>$pay_time_msg['id']])->save(['status'=>2]);
                if($is_throw_error) {
                    throw new \think\Exception(L_('订单已退款，无法成团！'));
                }else{
                    continue;
                }
            }
            if($val['simulate_group_num']==0 || empty($val['simulate_group_num'])){//机器人参与人数大于等于几人的团；若为空，机器人则参与所有未拼成的团
                if($is_plan&&($val['machine_into_time']+$pay_time_msg['pay_time'])>time()){//不满足跳过
                    if($is_throw_error){
                        throw new \think\Exception(L_('超过机器人介入成团时间'));
                    }else{
                        continue;
                    }
                }
            }else{
                if($is_plan){
                    if($val['num']<$val['simulate_group_num']){//机器人参与人数大于等于几人的团；不够跳过
                        if($is_throw_error){
                            throw new \think\Exception(L_('机器人参与人数要大于等于'.$val['simulate_group_num'].'人'));
                        }else{
                            continue;
                        }
                    }else{
                        if(($val['machine_into_time']+$pay_time_msg['pay_time'])>time()){//不满足跳过
                            if($is_throw_error){
                                throw new \think\Exception(L_('超过机器人介入成团时间'));
                            }else{
                                continue;
                            }
                        }
                    }
                }
            }
            $data['tid']=$val['team_id'];
            $data['order_id']=0;
            $data['type']=1;
            $where1=[['mer_id','=',$val['mer_id']]];
            $rob_list=(new MallRobotList())->getRobotList($over_num,$where1);//随机几个机器人
            foreach($rob_list as $k=>$v){
                $data['user_id']=$v['id'];
                (new MallNewGroupTeamUser())->addTeamUser($data);//机器人加入拼团队伍
            }
            $wheres=[['id','=',$val['team_id']]];
            $data1['status']=1;
            $data1['num']=$val['complete_num'];
            (new MallNewGroupTeam())->updateGroupStatus($wheres,$data1);
            $where_order=[['tid','=',$val['team_id']],['status','=',1]];
            $orderList=(new MallNewGroupOrder())->getList($where_order,$field="order_id");
            foreach($orderList as $keys=>$vals){
                (new MallOrderService())->changeOrderStatus($vals['order_id'],10,'拼团机器人自动加入拼团完成后改变订单状态(计划任务)');
            }
        }
        return true;
    }
}