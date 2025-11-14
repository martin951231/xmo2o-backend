<?php


namespace app\mall\model\db;

use app\common\model\service\GoodsScoreService;
use think\Model;
use think\facade\Config;

class MallActivityDetail extends Model
{

    /**
     * @param $where
     * @param $field
     * @return array
     * 获取活动商品信息总数
     */
    public function getActGoodsCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        return $this->alias('d')
            ->join($prefix . 'mall_activity' . ' act', 'act.id=d.activity_id')
            ->join($prefix . 'mall_goods' . ' gd', 'gd.goods_id=d.goods_id')
            ->where($where)
            ->count();
    }

    /**
     * @param $where
     * @param $field
     * @return array
     * 获取活动商品信息
     */
    public function getActGoods($where, $field, $order = 'sort DESC', $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        if($page == 0){
            $arr = $this->alias('d')
            ->join($prefix . 'mall_activity' . ' act', 'act.id=d.activity_id')
            ->join($prefix . 'mall_goods' . ' gd', 'gd.goods_id=d.goods_id')
            ->field($field)
            ->where($where)
            ->order($order)
            ->select();
        }else{
            $arr = $this->alias('d')
            ->join($prefix . 'mall_activity' . ' act', 'act.id=d.activity_id')
            ->join($prefix . 'mall_goods' . ' gd', 'gd.goods_id=d.goods_id')
            ->field($field)
            ->where($where)
            ->order($order)
            ->page($page, $pageSize)
            ->select();
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
    /**
     * @param $where1
     * @param $arr
     * @return string
     * @author mrdeng
     * 活动列表
     */
    public function getList($activityid, $arr,$cate_id,$uid=0,$page=1,$pageSize=10,$filterGoodsIds = [],$source='',$goods_id=0)
    {
        if($cate_id>0){
            $where1 = [
                ['m.cate_second','=',$cate_id],
                ['s.activity_id','=',$activityid],
            ];
        }else{
            $where1 = [
                ['s.activity_id','=',$activityid],
            ];
        }

        if(!empty($goods_id)){
           array_push($where1,['s.goods_id','=',$goods_id]);
        }

        if ($filterGoodsIds) {
            $where1[] = ['m.goods_id', 'IN', $filterGoodsIds];
        }


        //找出活动商品列表
        $field = "s.activity_id,s.goods_id,m.price,m.name,m.image,msku.sort,m.score_percent,m.goods_type,msku.is_first";
        $return = $this->alias('s')
            ->leftJoin('mall_goods' . ' m', 's.goods_id = m.goods_id')
            ->leftJoin('pigcms_mall_activity' . ' ma', 's.activity_id = ma.id')
            ->leftJoin('mall_limited_sku' . ' msku', 's.goods_id = msku.goods_id and ma.act_id = msku.act_id')
            ->field($field)
            ->where($where1)
            ->group('s.goods_id')
            ->select()->toArray();
        $left = array();
        $ret = array();
        $left['notice_type']=$arr[9];
        $left['notice_time']=$arr[10];
        if ($arr[1] == 1) {
            //按固定时间计算秒数
            $left['left_time'] = $arr[7] - $arr[6];
            //按固定时间计算秒数
            if (time() > $arr[6] && time() <= $arr[7]) {
                $left['left_time'] = $arr[7] - time();
                $left['limited_status'] = 1;
            } elseif (time() < $arr[6]) {
                $left['left_time'] = $arr[6] - time();
                $left['limited_status'] = 0;
            } elseif (time() > $arr[7]) {
                //结束
                //$left['limited_status']=2;
                return $ret;
            }
        }
        else {
            //按周期
            //周期类型
            if ($arr[2] == 1) {//每日
                $sec = $this->sec();//计算当前时间秒数
                if ($sec < $arr[4]) {
                    $left['left_time'] = $arr[4] - $sec;
                    $left['limited_status'] = 0;
                } else if ($sec > $arr[4] && $sec < $arr[5]) {
                    $left['left_time'] = $arr[5] - $sec;
                    $left['limited_status'] = 1;
                } else {
                    $left['left_time'] = 0;//结束了
                    return $ret;
                }
            } elseif ($arr[2] == 2) {//每周
                /*$arr=[$val['id'],$val['time_type'],$val['cycle_type'],$val['cycle_date'],$val['cycle_start_time'],
            $val['cycle_end_time'],$val['start_time'],$val['end_time'],$val['act_id']];*/
                $weekEnglish = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                if (empty($arr[3])) {
                    //数据不正常
                    $left['left_time'] = 0;//结束了
                    $left['limited_status'] = 2;//结束了
                    return $ret;
                } else {
                    $time = explode(',', $arr[3]);
                    $week = date('w');
                    if (in_array($week, $time)) {//本日期在活动中
                        $sec = $this->sec();
                        if ($sec < $arr[5] && $sec >= $arr[4]) {
                            //还在活动时间内
                            $left['limited_status'] = 1;
                            $left['left_time'] = $arr[5] - $sec;
                        } else if ($sec < $arr[4] && $arr[9] == 2 && ($arr[4] - $sec) < $arr[10]*3600) {
                            $left['left_time'] = $arr[4] - $sec;
                            $left['limited_status'] = 0;
                        } else {
                            //结束了
                            return $ret;
                        }
                    } else {
                        $wk = $this->timeDiff($week, $time);
                        //获取周几的时间戳
                        $time = strtotime($weekEnglish[$wk]) * 1 + $arr[4] * 1;
                        $left['limited_status'] = 0;
                        $left['left_time'] = $time * 1 - time();
                    }
                }
            } else {//每月
                //获取当前是几号
                $date = date('Y-m-d');
                $time = explode('-', $date);
                if (empty($arr[3])) {
                    //数据不正常
                    return $ret;
                } else {
                    //判断是否是正常数据
                    $days = explode(",", $arr[3]);
                    $dates_num = $time[2] * 1;//取当前是几号
                    if (in_array($dates_num, $days)) {//判断是否在这个时间内
                        //符合这个日期，当前执行
                        $sec = $this->sec();
                        if ($sec < $arr[5]) {
                            //还在活动时间内
                            $left['left_time'] = $arr[5] - $sec;
                            $left['limited_status'] = 1;
                        } else {
                            //不在活动时间内了
                            return $ret;
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
                                $left['left_time'] = $this->strNextMonth($time[0] * 1, $time[1] * 1, $days, $arr[4]);
                                if (!$left['left_time']) {
                                    //数据错误
                                    return $ret;
                                } else {
                                    $left['limited_status'] = 0;
                                }
                            } else {
                                //正常范围内正常执行
                                $days = $this->timeDiff($time[2] * 1, $days);
                                $left['left_time'] = $this->strThisMonth($time[0] * 1, $time[1] * 1, $days, $arr[4]);
                                if (!$left['left_time']) {
                                    //数据错误
                                    return $ret;
                                } else {
                                    $left['limited_status'] = 0;
                                }
                            }
                        } else {
                            //不在范围内取下个月的最小日期
                            $num = min($days);//活动开始最近日期
                            $mon = $time[1];//月份
                            $year = $time[0];
                            $left['left_time'] = $this->strNextMonth($year, $mon, $num, $arr[4]);
                            $left['limited_status'] = 0;
                        }
                    }
                }

            }
        }
        /*根据每个goods_id查找最低价*/
        $rs = array();
        foreach ($return as $key => $val) {
            $retn=array();
            $retn['goods_id'] = $val['goods_id'];
            if($uid){
                $where=[
                    ['goods_id','=',$val['goods_id']],
                    ['act_id','=',$arr[8]],
                    ['uid','=',$uid],
                ];
                $retn['notice_status'] =(new MallLimitedActNotice())->getNoticeStatus($where,"id")?1: 0;
            }else{
                $retn['notice_status'] =0;
            }
            if(empty($source)){
                $retn['id'] = $val['activity_id'];
            }else{
                $retn['id'] = $arr[8];
            }
            $retn['goods_name'] = $val['name'];
            $retn['start_time'] = $arr[6];
            $retn['goods_image'] =$val['image'] ? thumb_img($val['image'],500,500,'fill') : '';
            $retn['limited_status'] = $left['limited_status'];
            $min_stock=(new MallLimitedSku())->getRestActMinStock($arr[8],$val['goods_id']);
            $stock=(new MallLimitedSku())->getRestActStock($arr[8],$val['goods_id']);

            $retn['act_stock_num']=$stock;
            if($min_stock==-1){
                $retn['act_stock_num'] =-1;
            }
	    
            if($left['limited_status']==0 && $left['notice_type']==2 && $left['left_time']>$left['notice_time']*3600){
              continue;
            }
            $retn['left_time'] = $left['left_time'];////距离结束时间秒
            $retn['lowest_price'] = get_format_number($val['price']);
            $limited_price=(new MallLimitedSku())->limitMinPrice($arr[8],$val['goods_id']);
            $retn['limited_price'] = get_format_number($limited_price);
            $retn['sale_num'] =(new MallOrderDetail())->where([['goods_id','=',$val['goods_id']],['activity_type','=','limited'],['activity_id','=',$arr[8]],['status','>=',0],['status','<',50]])->sum('num');
            $retn['sort'] = $val['sort'];//排序
            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($val['price'],'mall',($val['goods_type']=='sku'?true:false),$val['score_percent']);
            $retn['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $retn['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            $rs[]=$retn;
        }
        return array_slice($rs,($page - 1) * $pageSize,$pageSize);
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

    public function sec1()
    {
       /* $times = date('H:i:s', time());
        $time = explode(':', $times);
        $sec = $time[0] * 3600 + $time[1] * 60 + $time[2] * 1;//当前秒数*/
        return time();
    }

    /**
     * @param $year
     * @return int
     * @author mrdeng
     * 判断润平年
     */
    public function leapYear($year)
    {
        $time = mktime(20, 20, 20, 2, 1, $year);//取得一个日期的 Unix 时间戳;
        if (date("t", $time) == 29) { //格式化时间，并且判断2月是否是29天；
            return 1;//是29天就输出时闰年；
        } else {
            return 0;
        }
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

    /** 批量添加数据
     * @param $data
     */
    public function addAll($data)
    {
         $res=$this->insertAll($data);
        //fdump($this->getLastSql(),"dededed12312312312312",1);
        return $res;
    }

    /** 删除数据
     * @param $where
     */
    public function delAll($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * @param $spu_where
     * @param $spu_field
     * 获取正在参加活动的商品id
     * @author  zhumengqun
     */
    public function getGoodsInAct($where, $field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('d')
            ->join($prefix . 'mall_activity'.' a' , 'a.id=d.activity_id')
            ->field($field)
            ->where($where)
            ->order('sort DESC')
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getGoodsOutAct($where, $field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('d')
            ->join($prefix . 'mall_activity'.' a' , 'a.id=d.activity_id')
            ->field($field)
            ->whereOr($where)
            ->order('sort DESC')
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
    /**
     * @param $where
     * @param $order
     * @param string $field
     * @return array
     * 获取满足条件活动字段值数组
     */
    public function getActField($where,$field='*'){
        $arr=$this->where($where)->column($field);
        return $arr;
    }

    /**
     * @param $where
     * @param string $field
     * @return mixed
     * 活动明细表的字段值
     */
    public function getActGoodsId($where,$field='*'){
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('d')
            ->join($prefix . 'mall_activity'.' a' , 'a.id=d.activity_id')
            ->where($where)->column($field);
        return $arr;
    }
}