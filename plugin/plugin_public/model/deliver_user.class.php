<?php
class deliver_user{

    //配送员交通工具
    public $traffic = [];

    public function __construct(){
        $this->traffic = [
            'electric_bicycle' => [
                'text' => L_('电动自行车'),
                'speed' => '40',//速度 km/h
            ],
            'motorcycle' => [
                'text' => L_('摩托车'),
                'speed' => '60',//速度 km/h
            ],
            'car' => [
                'text' => L_('汽车'),
                'speed' => '60',//速度 km/h
            ],
        ];
    }
    /**
     * 检测是否有配送员可接单
     * @param float $lat
     * @param float $lng
     * @return boolean
     */
    public function hasUser($lat, $lng)
    {
        $where = "`group`=1 AND `status`=1 AND `is_notice`=0 AND `last_time`<>0 AND ((`delivery_range_type`=0 AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`lng`*PI()/180)/2),2)))*1000) <= `range`*1000)";
        $where .= " OR (`delivery_range_type`=1 AND MBRContains(PolygonFromText(`delivery_range_polygon`),PolygonFromText('Point({$lng} {$lat})'))>0))";
        $list = M('Deliver_user')->field(true)->where($where)->select();  
        // 开启配送员等级
        if (C('config.open_deliver_level')) {
            foreach ($list as $key => $value) {
                $now_level = $this->get_user_level($value['score']);
                if ($now_level['order_num'] >=0 ) {
                    // 今日接单量
                    $where = array();
                    $where['uid'] = $value['uid'];
                    $where['start_time'] = array('between',array(strtotime(date("Y-m-d")),time()));
                    $todayNum = M('Deliver_supply')->where($where)->count();
                    if ($todayNum>=$now_level['order_number']) {
                        // 今日接单量已达上限！
                        unset($list[$key]);
                    }
                }
            }
        }

        return $list;
    }

    // 获得当前配送员等级
    public function get_user_level($score)
    {
        $level_list = M('Deliver_level')->order('score asc')->select();
        $now_level = array(
            'level' => 0,
            'score' => $score,
            'now_score' => 0,
            'name' => L_('暂无等级'),
            'next_score' => 0,
            'order_number' => -1, // 没有等级不限制接单量
        );
        if ($level_list) {
            foreach ($level_list as $key => $value) {
                if ($score>=$value['score']) {
                    if ($now_level['now_score'] <= $value['score']) {
                        $now_level['now_score'] = $value['score'];
                        $now_level['name'] = $value['name'];
                        $now_level['level'] = $value['level'];
                        $now_level['order_number'] = $value['order_number'];
                    }
                }else{
                    if ($value['score'] < $now_level['next_score'] || !$now_level['next_score']) {
                        $now_level['next_score'] = $value['score'];
                    }
                }
            }
        }
        return $now_level;
    }

    /*增加配送员的积分*/
    public function add_score($uid,$score,$desc,$from,$param=array()){
        if (!C('config.open_deliver_level')) {
            return array('error_code' => true, 'msg' => L_('平台没有开启配送员等级配置'));
        }

        if(C('config.score_round_two')==1){
            $score = sprintf('%.2f',floatval($score));
        }else{
            $score = round($score);
        }

        if($score>0){
            $condition_user['uid'] = $uid;
            if(M('Deliver_user')->where($condition_user)->setInc('score',$score)){
                plugin_model('deliver_score_log')->add_row($uid,1,$from,$score,$desc,0,$param);                
                return array('error_code' =>false,'msg' =>L_('积分增加成功！'));
            }else{
                return array('error_code' => true, 'msg' => L_('添加积分失败！请联系管理员协助解决。'));
            }
        }else{
            return array('error_code' => true, 'msg' => L_('积分数据有误'));
        }
    }

    /*增加配送员的积分*/
    public function deduct_score($uid,$score,$desc,$from,$param=array()){
        if (!C('config.open_deliver_level')) {
            return array('error_code' => true, 'msg' => L_('平台没有开启配送员等级配置'));
        }

        if(C('config.score_round_two')==1){
            $score = sprintf('%.2f',floatval($score));
        }else{
            $score = round($score);
        }

        if($score>0){
            $condition_user['uid'] = $uid;
            if(M('Deliver_user')->where($condition_user)->setDec('score',$score)){
                plugin_model('deliver_score_log')->add_row($uid,2,$from,$score,$desc,0,$param);                
                return array('error_code' =>false,'msg' =>L_('积分扣除成功！'));
            }else{
                return array('error_code' => true, 'msg' => L_('扣除积分失败！请联系管理员协助解决。'));
            }
        }else{
            return array('error_code' => true, 'msg' => L_('积分数据有误'));
        }
    }
}