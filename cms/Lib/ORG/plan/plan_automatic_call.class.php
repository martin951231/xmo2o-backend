<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2021/9/14
 * Time: 18:24
 */

class plan_automatic_call extends plan_base
{
    public function runTask(){
        if (!empty(C('DISABLE_OLD_ALL_DB_PLAN')) && C('DISABLE_OLD_ALL_DB_PLAN') == true){
            return ;
        }
        $mdH='1'.date('H');
        $mdH=intval($mdH);
        if($mdH>110){
            return true;
        }
        $model = '\app\community\model\service\HouseNewCashierService/automaticCall';
        $v20res = invoke_v20_service($model);
        if($v20res['status'] == 1000){
            $data = $v20res['data']['retval'];
            $datestr=date('ymd');
            foreach ($data as $v){
                $this->keepThread();
                $arr = array();
                $arr['param'] = serialize($v);
                $arr['plan_time'] = -505;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_automatic_call';
                $arr['time_type'] = 1;
                $unique_id='sub_automatic_call_'.time().rand(1,9999);
                if(isset($v['id']) && !empty($v['id'])){
                    //防止同一秒中 rand重复导致 unique_id值重复 往表里插不进数据
                    $unique_id='sub_automatic_call_'.$v['id'].'_'.$datestr;
                }
                $arr['unique_id'] = $unique_id;
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();
            }
        }
        return true;
    }
}