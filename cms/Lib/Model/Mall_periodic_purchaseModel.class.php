<?php
/**
 * Created by PigCMS.
 * User: jjc.
 * Date: 2019/6/18.
 * Time: 14:17.
 */

/**
 * Mall_periodic_purchaseModel 商城商品周期购设置类
 */
class Mall_periodic_purchaseModel extends Model{

    //新增&编辑周期购数据（商家后台）
    public function edit($data,$id=0){
        $time = time();
        if($id){
            $data['update_time'] = $time;
            return $this->where(array('id'=>$id))->data($data)->save();
        }else{
            $data['add_time'] = time();
            $data['update_time'] = $time;
            return $this->data($data)->add();
        }  
    }

    //根据商品goods_id获取商品周期购详情
    public function get_goods_one($goods_id,$deal=false){
        if(!$goods_id) return '';
        $where = array();
        $where['goods_id'] = $goods_id;
        $where['status'] = 0;
        $data = $this->where($where)->find();
        return $deal?$this->deal_one($data):$data;
    }

    //处理一下单条周期购数据，方便前端显示
    protected function deal_one($data){
        if($data){  
            $periodic_date = explode(',', $data['periodic_date']);
            $periodic_date_list = array();//配送周期列表
            switch ($data['periodic_type']) {
                case '1':
                    $data['periodic_text'] = '每日一期，共'.$data['periodic_count'].'期';
                    $temp=array('text'=>'每天','val'=>'0');
                    $periodic_date_list[] = $temp;
                    break;
                case '2':
                    $data['periodic_text'] = '每周一期，共'.$data['periodic_count'].'期';
                    $week_config = array('周日','周一','周二','周三','周四','周五','周六');
                    foreach ($periodic_date as $key => $value) {
                        $temp = array();
                        $temp['text'] = $week_config[$value];
                        $temp['val']  = $value;
                        $periodic_date_list[] = $temp;
                    }
                    break;
                case '3':
                    $data['periodic_text'] = '每月一期，共'.$data['periodic_count'].'期';
                    foreach ($periodic_date as $key => $value) {
                        $temp = array();
                        $temp['text'] = $value.'号';
                        $temp['val']  = $value;
                        $periodic_date_list[] = $temp;
                    }
                    break;
                default:
                     $data['periodic_text'] = '数据错误';
                    break;
            }
            $data['periodic_date_list'] = $periodic_date_list;
            $data['default_periodic_list'] = $this->create_preriodic_date($data,time(),0);//当前下单最快的配送周期列表
        }
        return $data;
    }    

    //根据id获取商品的周期购详情
    public function get_one($id){
        if(!$id) return '';
        $where = array();
        $where['p.id'] = $id;
        $where['p.status'] = 0;
        $field = 'p.*,g.name as goods_name';
        return M('Mall_periodic_purchase as p')->join('left join pigcms_shop_goods as g on p.goods_id=g.goods_id')->field($field)->where($where)->find(); 
    }

     /**
     * @desc 未选中配送日期的情况下，获取最快的配送日期
     * @param $info 本表中的一条记录
     * @param $start_time：开始计算的时间（注意：这里的开始时间，是可以配送的第一天，而不是下单支付时间）
     * @return int 周几或者几号
     */
    protected function get_first_appont_day($info,$start_time){
        if(!$info||!$start_time) return 0;
        $num = 0;
        if($info['periodic_type']==2||$info['periodic_type']==3){
            $periodic_date = explode(',', $info['periodic_date']);//可选择的配送日期
            sort($periodic_date);
            $max = max($periodic_date);
            if($info['periodic_type']==2){
                $current = date("w",$start_time);
            }
            if($info['periodic_type']==3){
                $current = date("d",$start_time);
            }
            if($current>=$max){
                $num = $periodic_date[0];
            }else{
                foreach ($periodic_date as $k => $v) {
                    if($v>=$current){
                        $num = $v;
                        break;
                    }
                }
            }
        }    
        return $num;
    }

    /**
     * @desc 根据下单时间，生成周期购配送时间
     * @param $pay_time 订单支付时间戳
     * @param $info：本表中的一条记录
     * @param $appont_day 指定某一天(周几0表示周日，或者每月几号)
     * @return array
     */
    public function create_preriodic_date($info,$pay_time,$appont_day){
        $count = $info['periodic_count'];//总的期数
        $pre_delivery_day  = $info['pre_delivery_day'];//用户需要提前支付的天数
        $pre_delivery_time = $info['pre_delivery_time'];//用户需要提前支付的当天的时间
        $now_hour =  date("H",$pay_time);//当前下单的小时数
        $first_count = $now_hour<$pre_delivery_time?$pre_delivery_day:$pre_delivery_day+1;//最早配送的时间距离今天的天数
        $periodic_date = 0;//每期的配送时间（如周几，几号）;
        $return = array();

        switch ($info['periodic_type']) {
            case '1'://每日
                for ($i=1; $i <= $count; $i++) { 
                    if($i==1){//第一期的时间
                        $day = $pay_time+$first_count*60*60*24;//第一期的时间戳
                    }else{
                        $day += 60*60*24;
                    }
                    $return[] = $day;
                }
                break;
            case '2'://每周
                $day_temp = strtotime(date("Y-m-d",$pay_time+$first_count*60*60*24));
                $periodic_date = $appont_day>=0?$appont_day:$this->get_first_appont_day($info,$day_temp);//
                for ($i=1; $i <= $count; $i++) {
                    if($i==1){//第一期的时间
                        
                        $day_week = date("w",$day_temp);
                        if($day_week==$periodic_date){
                            $day = $day_temp;
                        }elseif($day_week<$periodic_date){//当前的周几小于设置的，则还是本周
                            $day = $day_temp+($periodic_date-$day_week)*60*60*24;
                        }else{//当前的周几大于设置的，则第一期的时间戳得到下一周
                            $day = $day_temp+(7+$periodic_date-$day_week)*60*60*24;
                        }
                    }else{
                        $day += 60*60*24*7;
                    }
                    $return[] = $day;
                }
                break;
            case '3'://每月
                $day_temp = strtotime(date("Y-m-d",$pay_time+($first_count*60*60*24)));
                $periodic_date = $appont_day?$appont_day:$this->get_first_appont_day($info,$day_temp);
                for ($i=1; $i <= $count; $i++) { 
                    if($i==1){//第一期的时间
                        $t_day         = date("t",$day_temp);//当月共有多少天  
                        $current_day   = date("d",$day_temp);//当前日
                        if($current_day==$periodic_date){//刚好是当天
                            $day = $day_temp;
                        }elseif($current_day<$periodic_date||$periodic_date>=$t_day){//当前号数还没到设置的号数，或者设置的号数已经超出了本月最大号数，则都还在本月
                            if($periodic_date>=$t_day){//本月最后一天
                                $day = $this->get_month_last_day($day_temp);
                            }else{
                                $day_format = date("Y-m-$periodic_date", $day_temp);
                                $day = strtotime($day_format);
                            }
                        }else{//第一期时间在下个月
                            $day = $this->get_appoint_month_day($day_temp,$periodic_date);
                        }
                    }else{ 
                        $day = $this->get_appoint_month_day($day,$periodic_date);
                    }
                    $return[] = $day;
                }
                break;
            default:
                $return = array();
                break;
        }
        return $return;
    }

    //根据时间戳获取当月最后一天时间戳
    protected function get_month_last_day($time){
       $firstday = date('Y-m-01', $time);
       $lastday = strtotime("$firstday +1 month -1 day");
       return $lastday;
    }

    /**
     * @desc 根据当前时间戳，获取后面几个月指定号数的时间戳
     * @param $time 当前时间戳
     * @param $appoint_day 指定某一天，1-31号
     * @param $count 指定某个月，1表示下个月，2表示下两个月
     * @return int 时间戳
     */
    protected function get_appoint_month_day($time,$appoint_day,$count=1){
        $now = date('Y-m-01', $time);
        $appint_month = strtotime("$now +$count month");//指定的月份时间戳
        $appint_month_count = date("t",$appint_month);//指定的月份总计天数
        if($appoint_day>=$appint_month_count){//获取当月最后一天
            return $this->get_month_last_day($appint_month);
        }else{//获取当月指定的日期
            $day_format = date('Y-m-'.$appoint_day, $appint_month);
            return strtotime($day_format);
        }
    }

    //支付成功后，生成配送时间表
    public function create_deliver($order_id,$goods_id,$appoint_day){
        $info = $this->get_goods_one($goods_id);
        if(!empty($info)){
            $data_list = $this->create_preriodic_date($info,time(),$appoint_day);
            return D('Mall_periodic_deliver')->add_deliver($order_id,$goods_id,$data_list);
        }
        return false;
    }

}
