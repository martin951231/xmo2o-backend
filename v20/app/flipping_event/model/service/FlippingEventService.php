<?php


namespace app\flipping_event\model\service;


use app\common\model\db\ShopOrder;
use app\common\model\service\UserService;
use app\flipping_event\model\db\FlippingEvent;
use app\flipping_event\model\db\FlippingEventAwards;
use app\flipping_event\model\db\FlippingEventAwardsLog;
use app\flipping_event\model\db\FlippingEventRules;
use think\facade\Db;

class FlippingEventService
{
    /**
     * 活动列表
     * @param $params
     * @return array
     */
    public function getList($params){
        $page = $params['page']?:1;
        $pageSize = $params['pageSize']?:10;
        $status = $params['status'];
        $keywords = $params['keywords'];
        
        $where = [];
        if($keywords){
            $where[] = ['title','like','%'.$keywords.'%'];
        }
        if($status>=0){
            $where[] = ['status','=',$status];
        }
        $data = (new FlippingEvent())->getList($where,'*',$page,$pageSize);
        foreach ($data['data'] as $key=>$item){
            $data['data'][$key]['start_time'] = $item['start_time']?date('Y-m-d H:i:s',$item['start_time']):'';
            $data['data'][$key]['end_time'] = $item['end_time']?date('Y-m-d H:i:s',$item['end_time']):'';
            $data['data'][$key]['status_txt'] = $item['status']==1?'进行中':'关闭';
            $data['data'][$key]['link_url'] = cfg('site_url').'/packapp/plat/pages/activity/mahjongCard/cashPrizeCenter?id='.$item['id'];
        }
        return $data;
    }

    /**
     * 活动详情
     * @param $params
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDetail($params){
        $id = $params['id']?:0;
        if(!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $detail = (new FlippingEvent())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$detail){
            throw new \think\Exception(L_('信息不存在！'));
        }
        $detail['img'] = $detail['img']?replace_file_domain($detail['img']):'';
        //获取奖项列表
        $awards = (new FlippingEventAwards())->field('')->where(['event_id'=>$id])->select()->toArray();
        foreach ($awards as $key=>$item){
            $awards[$key]['img'] = $item['img']?replace_file_domain($item['img']):'';
        }
        $detail['awards'] = $awards?:[];
        return $detail;
    }

    /**
     * 保存信息
     */
    public function saveData($params){
        $id = $params['id'];
        $awards = $params['awards'];
        $save_data = [
            'title' => $params['title'],
            'start_time' => $params['start_time']?strtotime($params['start_time']):0,
            'end_time' => $params['end_time']?strtotime($params['end_time']):0,
            'img' => $params['img'],
            'per_order_draw_num' => $params['per_order_draw_num'],
            'per_day_draw_num' => $params['per_day_draw_num'],
            'content' => $params['content'],
            'status' => $params['status']
        ];
        if(!$save_data['title']){
            throw new \think\Exception(L_('活动名称不能为空！'));
        }
        if(!$save_data['start_time']||!$save_data['end_time']){
            throw new \think\Exception(L_('请填写开始时间和结束时间！'));
        }
        if($save_data['start_time']>$save_data['end_time']){
            throw new \think\Exception(L_('结束时间要大于开始时间！'));
        }

        if($save_data['per_order_draw_num']<=0){
            throw new \think\Exception(L_('每单抽奖次数必须大于0！'));
        }
        if($save_data['per_day_draw_num']<0){
            throw new \think\Exception(L_('每日抽奖次数限制不能小于0！'));
        }
        if(!$save_data['content']){
            throw new \think\Exception(L_('活动详情不能为空！'));
        }
        if(!$awards||!is_array($awards)){
            throw new \think\Exception(L_('请设置抽卡奖品！'));
        }
        $win_odds = 0;
        $awards_title = [];
        foreach ($awards as $item){
            if(in_array($item['title'],$awards_title)){
                throw new \think\Exception(L_($item['title'].' 奖品名称重复！'));
            }
            if(!in_array($item['type'],[0,1,2])){
                throw new \think\Exception(L_($item['title'].' 奖品类型错误！'));
            }
            if($item['win_odds']<=0){
                throw new \think\Exception(L_($item['title'].' 奖品概率必须大于0！'));
            }
            if($item['type']!=2&&!$item['stocks_num']){
                throw new \think\Exception(L_($item['title'].' 奖品库存必须大于0！'));
            }
            if($item['type']==2){
                $item['can_give'] = 0;
            }
            $awards_title[] = $item['title'];
            $win_odds = bcadd($item['win_odds'],$win_odds,2);
        }

        if($win_odds<100||$win_odds>100){
            throw new \think\Exception(L_('奖品概率总和要等于100！'));
        }
        
        $start_check_where = [
            ['start_time','between',[$save_data['start_time'],$save_data['end_time']]]
        ];
        $end_check_where = [
            ['end_time','between',[$save_data['start_time'],$save_data['end_time']]]
        ];
        if($id){
            $start_check_where[] = ['id','<>',$id];
            $end_check_where[] = ['id','<>',$id];
        }
        if((new FlippingEvent())->where($start_check_where)->findOrEmpty()->toArray()){
            throw new \think\Exception(L_('该时间段已有活动！'));
        }
        if((new FlippingEvent())->where($end_check_where)->findOrEmpty()->toArray()){
            throw new \think\Exception(L_('该时间段已有活动！'));
        }
        
        Db::startTrans();
        try {
            if($id){
                if(!$info = (new FlippingEvent())->where(['id'=>$id])->findOrEmpty()->toArray()){
                    throw new \think\Exception(L_('修改信息不存在！'));
                }
                if((new FlippingEvent())->where([['title','=',$save_data['title']],['id','<>',$id]])->findOrEmpty()->toArray()){
                    throw new \think\Exception(L_('活动名称已存在'));
                }
                $save_data['update_time'] = time();
                (new FlippingEvent())->where(['id'=>$id])->save($save_data);
            }else{
                if((new FlippingEvent())->where(['title'=>$save_data['title']])->findOrEmpty()->toArray()){
                    throw new \think\Exception(L_('活动名称已存在'));
                }
                $save_data['create_time'] = time();
                $id = (new FlippingEvent())->insertGetId($save_data);
            }
            //添加奖项
            $awards_list = (new FlippingEventAwards())->where(['event_id'=>$id])->column('*','id');
            foreach ($awards as $val){
                if(!empty($val['id'])){
                    if(isset($awards_list[$val['id']])){
                        unset($awards_list[$val['id']]);
                    }
                    (new FlippingEventAwards())->where(['id'=>$val['id']])->save($val);
                }else{
                    if(isset($val['id'])){
                        unset($val['id']);
                    }
                    $val['event_id'] = $id;
                    (new FlippingEventAwards())->save($val);
                }
            }
            $del_ids = $awards_list?array_keys($awards_list):[];
            if($del_ids){
                (new FlippingEventAwards())->where([['id','in',$del_ids]])->delete();
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollBack();
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 删除活动
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function delete($params){
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $detail = (new FlippingEvent())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$detail){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        Db::startTrans();
        try {
            (new FlippingEvent())->where(['id'=>$id])->delete();
            (new FlippingEventAwards())->where(['event_id'=>$id])->delete();
            (new FlippingEventRules())->where(['event_id'=>$id])->delete();
            Db::commit();
        }catch (\Exception $e){
            Db::rollBack();
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 当前订单是否可以抽奖
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function checkOrder($params){
        $order_id = $params['order_id']?:0;
        $uid = $params['uid'];
        if(!$order_id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $order = (new ShopOrder())->where(['order_id'=>$order_id,'uid'=>$uid])->findOrEmpty()->toArray();
        if(!$order){
            throw new \think\Exception(L_('订单不存在！'));
        }
        $data = [
            'can_select' => 0,
            'img' => '',
            'id' => 0,
        ];
        if($order['paid']!=1){
            return $data;
        }
        if(in_array($order['status'],[4,5,6])){
            return $data;
        }
        $day_start = strtotime(date('Y-m-d'));
        //是否有活动
        $activity = (new FlippingEvent())->where([['start_time','<',time()],['end_time','>',time()]])->findOrEmpty()->toArray();
        if(!$activity){
            return $data;
        }
        $day_start = $day_start>$activity['start_time']?$day_start:$activity['start_time'];
        if($order['pay_time']<$day_start){
            return $data;
        }
        
        //查询是否已参与
        $user_activity_num = (new FlippingEventAwardsLog())->where([['uid','=',$uid],['order_id','=',$order_id]])->count();
        if($user_activity_num>=$activity['per_order_draw_num']){
            return $data;
        }
        
        if($user_activity_num<=0){
            //查询是否已超过当天可以参与次数
            $total_num = (new FlippingEventAwardsLog())->where([['uid','=',$uid],['create_time','>=',$day_start]])->group('order_id')->count();
            if($total_num>=$activity['per_day_draw_num']){
                return $data;
            }
        }
        $data['can_select'] = 1;
        $data['img'] = $activity['img']?replace_file_domain($activity['img']):'';
        $data['id'] = $activity['id'];
        $data['link_url'] = cfg('site_url').'/packapp/plat/pages/activity/mahjongCard/index?id='.$activity['id'].'&order_id='.$order_id;
        return $data;
    }

    /**
     * 获取活动信息
     * @param $params
     */
    public function getActivity($params){
        $res = $this->checkOut($params);
        $data = [
            'id' => $res['id'],
            'title' => $res['title'],
            'have_num' => $res['have_num'],
            'content' => $res['content']
        ];
        return $data;
    }

    /**
     * 抽奖
     * @param $params
     * @return int[]
     * @throws \think\Exception
     */
    public function Alottery($params){
        $info = $this->checkOut($params);
        
        if($info['have_num']<1){
            throw new \think\Exception(L_('抽奖次数已用完！'));
        }
        $awards_list = (new FlippingEventAwards())->where(['event_id'=>$info['id']])->select();
        if(!$awards_list){
            throw new \think\Exception(L_('未设置奖品！'));
        }
        
        $prize_arr = []; //有奖品
        $out_rate = $rand_num = $is_out = 0;
        foreach($awards_list as $key=>$item){
            if($item['stocks_num']<1){
                $out_rate += $item['win_odds'];
            }else{
                $prize_arr[] = $item;
            }
            if($item['type']!=2&&$item['stocks_num']>0){
                $is_out = 1;
            }
        }
        if(!$is_out){
            throw new \think\Exception(L_('奖品已抽完！'));
        }
        $avger_rate = 0;
        if(count($prize_arr)>0){
            $avger_rate = round(($out_rate/count($prize_arr)),2);
        }
        foreach ($prize_arr as $key=>$item){
            $prize_arr[$key]['min_num'] = $rand_num;
            $rand_num += 10000*($item['win_odds']+$avger_rate)/100;
            $prize_arr[$key]['max_num'] = $rand_num;
        }
        $number = rand(1,$rand_num);
        $user_prize = [];
        foreach($prize_arr as $key=>$item){
            if($number>$item['min_num']&&$number<=$item['max_num']){
                $user_prize = $item;
                break;
            }
        }

        if(!$user_prize){
            throw new \think\Exception(L_('抽奖失败，请重试！'));
        }

        $saveData = [
            'uid' => $params['uid'],
            'order_id' => $params['order_id'],
            'event_id' => $info['id'],
            'awards_id' => $user_prize['id'],
            'awards_title' => $user_prize['title'],
            'awards_type' => $user_prize['type'],
            'create_time' => time(),
            'status' => 0,
        ];
        if($user_prize['type']==1){
            $saveData['money'] = $info['online_money']?:0;
        }

        //订单已完成且是普通卡状态是已解封，不是普通卡的都改成已解封
        if(($user_prize['type']==0&&in_array($info['order_status'],[2,3]))||$user_prize['type']!=0){
            $saveData['status'] = 1;
        }

        //记录抽奖
        Db::startTrans();
        try {
            $id = (new FlippingEventAwardsLog())->insertGetId($saveData);
            (new FlippingEventAwards())->where(['id'=>$user_prize['id']])->inc('win_num',1)->update();
            (new FlippingEventAwards())->where(['id'=>$user_prize['id']])->dec('stocks_num',1)->update();

            if($user_prize['type']==1){
                //免单返余额
                (new UserService())->addMoney($params['uid'],$info['online_money'],'抽奖免单增加余额',24,$id);
            }
            
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        
        $data = [
            'have_num' => $info['have_num']-1,
            'awards_title' => $user_prize['title'],
            'img' => $user_prize['img']?replace_file_domain($user_prize['img']):'',
            'is_hit' => $user_prize['type']==2?0:1,
            'link_url' => cfg('site_url').'/packapp/plat/pages/activity/mahjongCard/userCard',
        ];
        return $data;
    }
    
    private function checkOut($params){
        $order_id = $params['order_id'];
        $id = $params['id'];
        $uid = $params['uid'];
        if(!$order_id){
            throw new \think\Exception(L_('参数错误！'));
        }
        if(!$id){
            throw new \think\Exception(L_('活动不存在！'));
        }
        $order = (new ShopOrder())->where(['order_id'=>$order_id,'uid'=>$uid])->findOrEmpty()->toArray();
        if(!$order){
            throw new \think\Exception(L_('订单不存在！'));
        }
        if($order['paid']!=1){
            throw new \think\Exception(L_('订单未支付！'));
        }
        if(in_array($order['status'],[4,5,6])){
            throw new \think\Exception(L_('订单已退款/已取消！'));
        }
        $day_start = strtotime(date('Y-m-d'));
        //是否有活动
        $activity = (new FlippingEvent())->where([['id','=',$id],['status','=',1]])->findOrEmpty()->toArray();
        if(!$activity){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        if($activity['start_time']>time()){
            throw new \think\Exception(L_('活动未开始！'));
        }
        if($activity['end_time']<time()){
            throw new \think\Exception(L_('活动已结束！'));
        }

        $user_activity_num = (new FlippingEventAwardsLog())->where([['uid','=',$uid],['order_id','=',$order_id]])->count();
        if($user_activity_num<=0){
            //查询是否已超过当天可以参与次数
            $total_num = (new FlippingEventAwardsLog())->where([['uid','=',$uid],['create_time','>=',$day_start]])->group('order_id')->count();
            if($total_num>=$activity['per_day_draw_num']){
                throw new \think\Exception(L_('今日参与次数已用完！'));
            }
        }
        $activity['have_num'] = ($activity['per_order_draw_num']-$user_activity_num)>0?($activity['per_order_draw_num']-$user_activity_num):0;
        $activity['online_money'] = $order['payment_money'];
        $activity['order_status'] = $order['status'];
        return $activity;
    }
}