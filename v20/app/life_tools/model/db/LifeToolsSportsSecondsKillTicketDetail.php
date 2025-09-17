<?php

/**
 * 体育限时秒杀活动门票记录表
 */
namespace app\life_tools\model\db;
use think\Model;

class LifeToolsSportsSecondsKillTicketDetail extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $spu_where
     * @param $spu_field
     * 获取正在参加活动的商品id
     */
    public function getGoodsInAct($where, $field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('d')
            ->join($prefix . 'life_tools_sports_seconds_kill'.' a' , 'a.id=d.activity_id')
            ->field($field)
            ->where($where)
            ->order('a.sort DESC')
            ->select()->toArray();
            return $arr;
    }

    /**删除数据
     * @param $where
     */
    public function delActiveDatailAll($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * @param $where
     * @param $field
     * @return array
     * 活动详情
     */
    public function getActDetail($where,$field){
        //找出活动商品列表
        $return = $this->alias('s')
            ->join('life_tools_sports_seconds_kill' . ' la', 'la.id = s.activity_id')
            ->join('life_tools_sports_seconds_kill_detail' . ' act', 'act.id = la.act_id')
            ->join('life_tools_sports_seconds_kill_ticket_sku' . ' sku', 'sku.ticket_id = s.ticket_id')
            ->join('life_tools_ticket' . ' lt', 'lt.ticket_id = s.ticket_id')
            ->join('life_tools' . ' l', 'l.tools_id = lt.tools_id')
            ->field($field)
            ->order('act.time_type asc,sku.id desc')
            ->where($where)
            ->find();
        if(!empty($return)){
            $return=$return->toArray();
            $ret=$this->getSportStatus($return);
            $where_act=[['act_id','=',$return['id']],['tool_id','=',$return['tools_id']]];
            if(isset($return['ticket_id'])){
                array_push($where_act,['ticket_id','=',$return['ticket_id']]);
            }

            if($return['stock_type']==1){//活动总库存
                $return['act_stock_num']=(new LifeToolsSportsSecondsKillTicketSku())->getSum($where_act,'act_stock_num');
            }else{//活动每日库存
                $return['act_stock_num']=(new LifeToolsSportsSecondsKillTicketSku())->getSum($where_act,'day_stock_num');

            }

            $return['act_stock_num']= $return['act_stock_num'] < 0 ? 0 : $return['act_stock_num'];
            if($ret['limited_status']==1){
                $return['act_type']='limited';
            }elseif($ret['limited_status']==0 && $ret['notice_type']==2){
                $return['act_type']='limited';
            }else{
                $return['act_type']='normal';
            }
            $return['limited_status']=$ret['limited_status'];
            $return['left_time']=$ret['left_time'];
        }else{
            $return['limited_status']=2;
            $return['act_type']='normal';
            $return['act_stock_num']=0;
            $return['start_time']=0;
            $return['left_time']=0;
            $return['id']=0;
        }
        return $return;
    }
    /**
     * @param $where1
     * @param $arr
     * @return string
     * @author mrdeng
     * 活动列表
     */
    public function getSportStatus($arr)
    {
        $left = array();
        $left['notice_type']=$arr['notice_type'];
        $left['notice_time']=$arr['notice_time'];
        $left['limited_status'] =2;
        $left['left_time'] =0;
        if ($arr['time_type'] == 1) {
            //按固定时间计算秒数
            $left['left_time'] = $arr['end_time'] - $arr['start_time'];
            //按固定时间计算秒数
            if (time() >= $arr['start_time'] && time() <= $arr['end_time']) {
                $left['left_time'] = $arr['end_time'] - time();
                $left['limited_status'] = 1;
            } elseif (time() < $arr['start_time']) {
                $left['left_time'] = $arr['start_time'] - time();
                $left['limited_status'] = 0;
                if(($arr['notice_type']==2 && ($arr['start_time']-time())<=$arr['notice_time']*3600)){
                    $left['limited_status'] = 0;
                }else{
                    $left['limited_status'] = 2;
                }
            } elseif (time() > $arr['end_time']) {
                //结束
                $left['limited_status']=2;
            }
        }
        else {
            //按周期
            //周期类型
            $startTimeNum = strtotime(date('Y-m-d',$arr['start_time'])) + $arr['cycle_start_time'];
            $endTimeNum = strtotime(date('Y-m-d',$arr['end_time'])) + $arr['cycle_end_time'];
            $sec = $this->sec();//计算当前时间秒数
            if ($arr['cycle_type'] == 1) {//每日
//                $sec = $this->sec();//计算当前时间秒数
//                if ($sec < $arr['cycle_start_time']) {
//                    $left['left_time'] = $arr['cycle_start_time'] - $sec;
//                    $left['limited_status'] = 0;
//                    if(($arr['notice_type']==2 && ($arr['cycle_start_time']-$sec)<=$arr['notice_time']*3600)){
//                        $left['limited_status'] = 0;
//                    }else{
//                        $left['limited_status'] = 2;
//                    }
//                } else if ($sec > $arr['cycle_start_time'] && $sec < $arr['cycle_end_time']) {
//                    $left['left_time'] = $arr['cycle_end_time'] - $sec;
//                    $left['limited_status'] = 1;
//                } else {
//                    $left['limited_status']=2;
//                    if($arr['notice_type']==2 && $arr['notice_time']>24){
//                        $left['limited_status'] = 0;
//                    }
//                }
                if(time() < $startTimeNum){
                    $left['left_time'] = $startTimeNum - time();
                    $left['limited_status']=2;
                    if(($arr['notice_type']==2 && ($startTimeNum-time())<=$arr['notice_time']*3600)){
                        $left['limited_status'] = 0;
                    }
                }elseif(time() >= $startTimeNum && time() < $endTimeNum){
                    if ($sec < $arr['cycle_start_time']) {
                        $left['left_time'] = $arr['cycle_start_time'] - $sec;
                        $left['limited_status'] = 0;
                        if(($arr['notice_type']==2 && ($arr['cycle_start_time']-$sec)<=$arr['notice_time']*3600)){
                            $left['limited_status'] = 0;
                        }else{
                            $left['limited_status'] = 2;
                        }
                    } else if ($sec >= $arr['cycle_start_time'] && $sec < $arr['cycle_end_time']) {
                        $left['left_time'] = $arr['cycle_end_time'] - $sec;
                        $left['limited_status'] = 1;
                    } else {
                        $left['limited_status']=2;
                        $nextTimeNum = strtotime(date("Y-m-d",strtotime("+1 day"))) + $arr['cycle_start_time'];
                        if($arr['notice_type']==2 && ($nextTimeNum * 1 - time())<=$arr['notice_time']*3600){
//                        if($arr['notice_type']==2 && $arr['notice_time']>24){
                            $left['left_time'] = $nextTimeNum * 1 - time();
                            $left['limited_status'] = 0;
                        }
                    }
                }else{
                    //结束
                    $left['limited_status']=2;
                }
            } elseif ($arr['cycle_type'] == 2) {//每周
                $weekEnglish = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                if (empty($arr['cycle_date'])) {
                    //数据不正常
                    $left['left_time'] = 0;//结束了
                    $left['limited_status'] = 2;//结束了
                    //return $left;
                } else {
                    $time = explode(',', $arr['cycle_date']);
                    $week = date('w');
                    if (in_array($week, $time)) {//本日期在活动中
//                        $sec = $this->sec();
//                        if ($sec < $arr['cycle_end_time'] && $sec >= $arr['cycle_start_time']) {
//                            //还在活动时间内
//                            $left['limited_status'] = 1;
//                            $left['left_time'] = $arr['cycle_end_time'] - $sec;
//                        } else if ($sec < $arr['cycle_start_time'] && $arr['notice_type'] == 2 && ($arr['cycle_start_time'] - $sec) < $arr['notice_time']*3600) {
//                            $left['left_time'] = $arr['cycle_start_time'] - $sec;
//                            $left['limited_status'] = 0;
//                        } else {
//                            //结束了
//                            $left['limited_status']=2;
//
//                        }
                        if(time() < $startTimeNum){
                            $left['left_time'] = $startTimeNum - time();
                            $left['limited_status']=2;
                            if(($arr['notice_type']==2 && ($startTimeNum-time())<=$arr['notice_time']*3600)){
                                $left['limited_status'] = 0;
                            }
                        }elseif(time() >= $startTimeNum && time() < $endTimeNum){
                            if ($sec < $arr['cycle_end_time'] && $sec >= $arr['cycle_start_time']) {
                                //还在活动时间内
                                $left['limited_status'] = 1;
                                $left['left_time'] = $arr['cycle_end_time'] - $sec;
                            } else if ($sec < $arr['cycle_start_time'] && $arr['notice_type'] == 2 && ($arr['cycle_start_time'] - $sec) < $arr['notice_time']*3600) {
                                $left['left_time'] = $arr['cycle_start_time'] - $sec;
                                $left['limited_status'] = 0;
                            } else {
                                //结束了
                                $left['limited_status']=2;

                            }
                        }else{
                            //结束
                            $left['limited_status']=2;
                        }
                    } else {
                        $wk = $this->timeDiff($week, $time);
                        //获取周几的时间戳
                        $time = strtotime($weekEnglish[$wk]) * 1 + $arr['cycle_start_time'] * 1;
                        $left['limited_status'] = 0;
                        $left['left_time'] = $time * 1 - time();
                    }
                }
            } else {//每月
                //获取当前是几号
                $date = date('Y-m-d');
                $time = explode('-', $date);
                if (empty($arr['cycle_date'])) {
//                if (empty($arr[3])) {
                    //数据不正常
                    //return $ret;
                    $left['limited_status'] = 0;
                } else {
                    //判断是否是正常数据
                    $days = explode(",", $arr['cycle_date']);
                    $dates_num = $time[2] * 1;//取当前是几号
                    if (in_array($dates_num, $days)) {//判断是否在这个时间内
                        //符合这个日期，当前执行
                        $sec = $this->sec();
                        if ($sec < $arr['cycle_end_time']) {
                            //还在活动时间内
                            $left['left_time'] = $arr['cycle_end_time'] - $sec;
                            $left['limited_status'] = 1;
                        } else {
                            //不在活动时间内了
                            //return $ret;
                            $left['limited_status'] = 0;
                        }
                    } else {
                        if ($dates_num < max($days)) {
                            //在这个时间段内,判断当年2月份天数
                            if ($this->leapYear($time[0])) {
                                $maxday = 29;
                            } else {
                                $maxday = 28;
                            }
                            //比较
                            if ($time[1] * 1 == 2 && max($days) > $maxday) {
                                //2月份，跳到下一月
                                $left['left_time'] = $this->strNextMonth($time[0] * 1, $time[1] * 1, $days, $arr['cycle_start_time']);
                                if (!$left['left_time']) {
                                    //数据错误
                                    //return $ret;
                                    $left['limited_status'] = 0;
                                } else {
                                    $left['limited_status'] = 0;
                                }
                            } else {
                                //正常范围内正常执行
                                $days = $this->timeDiff($time[2] * 1, $days);
                                $left['left_time'] = $this->strThisMonth($time[0] * 1, $time[1] * 1, $days, $arr['cycle_start_time']);
                                if (!$left['left_time']) {
                                    //数据错误
                                    // return $ret;
                                    $left['limited_status'] = 0;
                                } else {
                                    $left['limited_status'] = 0;
                                }
                            }
                        } else {
                            //不在范围内取下个月的最小日期
                            $num = min($days);//活动开始最近日期
                            $mon = $time[1];//月份
                            $year = $time[0];
                            $left['left_time'] = $this->strNextMonth($year, $mon, $num, $arr['cycle_start_time']);
                            $left['limited_status'] = 0;
                        }
                    }
                }

            }
        }
        return $left;
    }

    /**
     * @param $x
     * @param $arr1
     * @return int|mixed
     * @author mrdeng
     * 取最接近的天数
     */
    function timeDiff($x, $arr1)
    {
        $num = 0;
        $count = count($arr1);
        $max = max($arr1);
        if ($x < $max) {
            //当数值夹在中间
            for ($i = 0; $i < $count; $i++) {
                if ($x < $arr1[$i]) {
                    $num = $arr1[$i];
                    break;
                }
            }
        } else {
            //当前星期数值大于最大值，取最小值最接近
            $num = min($arr1);
        }
        return $num;
    }

    /**
     * @return float|int
     * @author mrdeng
     * 计算当前时间秒数
     */
    public function sec()
    {
        $times = date('H:i:s', time());
        $time = explode(':', $times);
        $sec = $time[0] * 3600 + $time[1] * 60 + $time[2] * 1;//当前秒数
        return $sec;
    }

    /**
     * @param $year
     * @param $mon
     * @param $day
     * @param $start
     * @return bool|false|float|int
     * @author mrdeng
     * 推到下个月执行
     */
    public function strNextMonth($year, $mon, $day, $start)
    {
        //当前月份是几月份，12月份就要年份加一
        if ($mon < 12) {
            $mon = $mon + 1;
            if ($day < 10) {
                $day = '0' . $day;
            }

            if ($mon < 10) {
                $mon = '0' . $mon;
            }
            $time = $year . $mon . $day;
            $timestr = strtotime($time) + $start * 1;
        } else {
            $mon = '01';
            $year = $year * 1 + 1;
            if ($day < 10) {
                $day = '0' . $day;
            }
            $time = $year . $mon . $day;
            $timestr = strtotime($time) + $start * 1;
        }
        $over = $timestr - time();
        if ($over > 0) {
            return $over;
        } else {
            return false;
        }
    }

    /**
     * @param $year
     * @param $mon
     * @param $day
     * @param $start
     * @return bool|false|float|int
     * @author mrdeng
     * 计算在本月内最接近当前时间执行的活动
     */
    public function strThisMonth($year, $mon, $day, $start)
    {
        $year = $year;
        if ($day < 10) {
            $day = '0' . $day;
        }
        if ($mon < 10) {
            $mon = '0' . $mon;
        }
        $time = $year . '-' . $mon . '-' . $day;
        $timestr = strtotime($time) + $start * 1;
        $over = $timestr - time();
        if ($over > 0) {
            return $over;
        } else {
            return false;
        }
    }
}