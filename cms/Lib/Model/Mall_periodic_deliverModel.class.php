<?php
/**
 * Created by PigCMS.
 * User: jjc.
 * Date: 2019/6/20.
 * Time: 14:17.
 */

/**
 * Mall_periodic_deliverModel 商城商品周期购配置表
 */
class Mall_periodic_deliverModel extends Model{

    //新增
    public function add_deliver($order_id,$good_id,$data_list){
        foreach ($data_list as $key => $value) {
            $data = array();
            $data['order_id'] = $order_id;
            $data['goods_id'] = $good_id;
            $data['deliver_time'] = $value;
            $data['deliver_num'] = $key+1;//配送期数
            $data['status'] = 0;
            $data['add_time'] = time();
            $data['update_time'] = time();
            $this->data($data)->add();
        }
        return true;
    }

    //获取配送时间列表
    public function get_order_deliver($order_id,$deal=true){
        if(!$order_id) return array();
        $where = array();
        $where['order_id'] = $order_id;
        $order = 'deliver_num asc';
        $data = $this->where($where)->order($order)->select();
        return $deal?$this->deal_data($data):$data;
    }

    public function deal_data($data){
        if($data){
            $periodic_purchase  = M('Mall_periodic_purchase')->where(array('goods_id'=>$data[0]['goods_id'],'status'=>0))->find();
            foreach ($data as $key => $val) {
                $deliver_text = '';
                $front_class = '';//前端样式的class
                $delay_text = '我要延期'; 
                $info_list = array();
                $delay_type = '';//延期类型
                $delay_class = 'delay_submit';
                switch ($val['status']) {
                    case '0':
                        $deliver_text = '待配送';
                        $front_class = 'static';
                        $delay_type = 'reply';//申请取消
                        if ($periodic_purchase['delay_limit']==0) {
                            // 不能延期，置灰
                            $delay_class = 'delay_unable';
                        }
                        break;
                    case '1':
                        $deliver_text = '配送中';
                        $front_class = 'static';
                        $delay_text = '确认收货';
                        $delay_class = 'confirm_submit';
                        $info_list[] = array(
                                        'text' => '配送员已发货',
                                        'time' => date('Y-m-d H:i:s',$val['accept_time'])
                                        );
                        break;
                    case '2':
                        $deliver_text = '已配送';
                        $front_class = 'is-delivery';
                        $info_list[] = array(
                                        'text' => '配送员已发货',
                                        'time' => date('Y-m-d H:i:s',$val['accept_time'])
                                        );
                        $info_list[] = array(
                                        'text' => '已到货',
                                        'time' => date('Y-m-d H:i:s',$val['arrive_time'])
                                        );
                        break;
                    case '3':
                        $deliver_text = '配送失败';
                        $front_class = 'is-delivery';
                        break;
                    case '4':
                        $deliver_text = '已延期';
                        $front_class = 'static is-delayed';
                        $delay_text = '取消延期';
                        $delay_type = 'cancle';
                        break;            
                    default:
                        $deliver_text = '未知状态';
                        $front_class = 'is-delivery';
                        break;
                }
                $data[$key]['update_time'] = date('Y-m-d H:i:s',$val['update_time']);
                $data[$key]['deliver_time_day'] = date('Y-m-d',$val['deliver_time']);
                $data[$key]['text'] = $deliver_text;
                $data[$key]['front'] = $front_class;
                $data[$key]['delay_text'] =$delay_text;
                $data[$key]['info_list'] = $info_list;
                $data[$key]['delay_type'] = $delay_type;
                $data[$key]['delay_class'] = $delay_class;
            }
        }
        return $data;
    }

    /**
     * @desc 延期&&取消延期
     * @param $id 配送id
     * @param $type 延期or取消延期
     * @param $user_type 0用户操作，1店员操作（店员无延期次数限制）
     * @return array
     */
    public function delay($id,$type='reply',$user_type=0){
        $where = array();
        $where['pd.id'] = $id;
        $field = 'pd.*,so.order_id,so.periodic_type,so.periodic_appoint_day,so.periodic_delay_limit,so.periodic_delay_count';
        // 当前期数
        $info = M('Mall_periodic_deliver')->alias('as pd')->join('left join pigcms_shop_order as so on pd.order_id=so.order_id')->where($where)->field($field)->find();
        $return = array();
        if(!$user_type){//用户操作
            if($info['periodic_delay_count']>=$info['periodic_delay_limit']&&$info['periodic_delay_limit']<>-1){
                $return['status'] = 0;
                $return['info'] = '抱歉：您延期与取消延期最多只可操作'.$info['periodic_delay_limit'].'次';
                return $return;
            }
            if($info['delay_type']==1){
                $return['status'] = 0;
                $return['info'] = '抱歉：当前操作为店员操作，用户不可撤销';
                return $return;
            }
        }
        
        if(!empty($info)){
            $w = array();
            $w['order_id'] = $info['order_id'];
            $w['goods_id'] = $info['goods_id'];
            $list = $this->where($w)->order('deliver_time desc')->select();//获取所有的配送列表,倒序排序，第一个是最晚的配送周期
            $last = $list[0];//最后一期详情

            $periodic_purchase  = M('Mall_periodic_purchase')->where(array('goods_id'=>$info['goods_id'],'status'=>0))->find();
            $diff = ceil(($info['deliver_time']-time())/86400);
            if ($info['deliver_time']<time() || $diff<$periodic_purchase['pre_delivery_day'] || date('H') > $periodic_purchase['pre_delivery_time']) {
                $return['status'] = 0;
                $return['info'] = '临近配送时期 暂不可顺延或取消延期';
                return $return;
            }
            if($type=='cancle'){//取消延迟
                if($info['status']<>4){
                    $return['status'] = 0;
                    $return['info'] = '抱歉：当前不是延期状态';
                // }elseif($info['deliver_time']<time()+60*60*24){
                //     $return['status'] = 0;
                //     $return['info'] = '抱歉：已过配送时间，无法取消';
                }else{
                    $this->cancle_delay($last,$info,$user_type);
                    if($user_type==0){
                        M('Shop_order')->where(array('order_id'=>$info['order_id']))->setInc('periodic_delay_count');
                    }
                    
                    $return['status'] = 1;
                    $return['info'] = '取消延期成功！';
                }
            }elseif($type=='reply'){//必须是待配送状态
                if($info['status']<>0){
                    $return['status'] = 0;
                    $return['info'] = '抱歉：当前周期配送状态无法延期！';
                }else{
                    $this->reply_delay($last,$info,$user_type);
                    if($user_type==0){
                        M('Shop_order')->where(array('order_id'=>$info['order_id']))->setInc('periodic_delay_count');
                    }
                    $return['status'] = 1;
                    $return['info'] = '延期成功！';
                }
            }else{
                $return['status'] = 0;
                $return['info'] = '系统错误！';
            }
        }else{
            $return['status'] = 0;
            $return['info'] = '抱歉：配送周期不存在！';
        }
        return $return;
    }

    //申请延期
    protected function reply_delay($last,$info,$user_type){
        $periodic_type        = $info['periodic_type'];//类型
        $periodic_appoint_day = $info['periodic_appoint_day'];//指定日期
        $data = array();
        $data['order_id'] = $info['order_id'];
        $data['goods_id'] = $info['goods_id'];
        $data['deliver_num'] = $last['deliver_num'];//配送期数
        $data['status'] = 0;
        $data['delay_time'] = time();
        $data['delay_type'] = $user_type;
        $data['update_time'] = time();
        $deliver_time = 0;//新增一期的配送时间
        switch ($periodic_type) {
            case '1'://每日
                $deliver_time = $last['deliver_time']+60*60*24;
                break;
            case '2'://每周
                $deliver_time = $last['deliver_time']+60*60*24*7;
                break;
            case '3'://每月
                $deliver_time = $this->get_next_month_day($last['deliver_time'],$periodic_appoint_day);
                break;
            default:
                $deliver_time = 0;
                break;
        }
        $data['deliver_time'] = $deliver_time;
        if($insert_id = $this->add($data)){
            $r1 = $this->where(array('id'=>$info['id']))->data(array('status'=>4,'delay_type'=>$user_type))->save();
            $r2 = $this->where(array('order_id' => $info['order_id'],'status'=>array('in','0,4'),'deliver_num'=>array('gt',$info['deliver_num']),'id'=>array('neq',$insert_id)))->setDec('deliver_num');

            if($rl && $r2){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }

    //根据时间戳获取当月最后一天时间戳
    protected function get_month_last_day($time){
       $firstday = date('Y-m-01', $time);
       $lastday = strtotime("$firstday +1 month -1 day");
       return $lastday;
    }

    //获取下个月同一天时间
    protected function get_next_month_day($now,$appoint_day){
        $now = date('Y-m-01', $now);
        $appint_month = strtotime("$now +1 month");//指定的月份时间戳
        $appint_month_count = date("t",$appint_month);//指定的月份总计天数
        if($appoint_day>=$appint_month_count){//获取当月最后一天
            return $this->get_month_last_day($appint_month);
        }else{//获取当月指定的日期
            $day_format = date('Y-m-'.$appoint_day, $appint_month);
            return strtotime($day_format);
        }
    }

    //取消延期
    protected function cancle_delay($last,$info,$user_type){
        if ($this->where(array('id'=>$last['id']))->delete()) { 
            $r1 = $this->where(array('id'=>$info['id']))->save(array('status'=>0,'delay_time'=>time(),'delay_type'=>$user_type));
            $r2 = $this->where(array('order_id' => $info['order_id'],'status'=>array('in','0,4'),'deliver_num'=>array('gt',$info['deliver_num']-1),'id'=>array('neq',$info['id'])))->setInc('deliver_num');

            if($rl && $r2){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    //确认收货
    public function confirm_arrive($id){
        $return = array();
        $data = array();
        $data['arrive_time'] = time();
        $data['update_time'] = time();

        $info = $this->where(array('id'=>$id))->find();
        //获得最近需要配送的周期
        $periodic_deliver = M('Mall_periodic_deliver')->where(array('order_id'=>$info['order_id'],'status'=>0))->order('deliver_num asc')->find();
        $shop_order = D("Shop_order")->where(array('order_id' => $order_id))->find();
        $now_user = D('User')->get_user($shop_order['uid']);

        // 修改本期信息
        $data['status'] = 2;
        $result = $this->where(array('id'=>$id))->data($data)->save();
        
        if($result){
            // 进入下一周期 配送状态更新
            if ($periodic_deliver) {
                $o_data['order_status'] = 0;
                $o_data['status'] = 0;
                D("Shop_order")->where(array('order_id' => $info['order_id']))->data($o_data)->save();
                D('Shop_order_log')->add_log(array('order_id' => $info['order_id'], 'status' => 36,'note'=>'确认收货成功，本周期完成'));
            }else{
                $o_data['status'] = 2;
                D("Shop_order")->where(array('order_id' => $info['order_id']))->data($o_data)->save();
                $info = $this->where(array('id'=>$id))->field('order_id')->find();
                D('Shop_order_log')->add_log(array('order_id' => $info['order_id'], 'status' => 6, 'name' => '', 'phone' => ''));
            }
            $return['status'] = '1';
            $return['info'] = '操作成功';
           
        }else{
            $return['status'] = '1';
            $return['info'] = '操作失败';
        }
        return $return;
    }

}
