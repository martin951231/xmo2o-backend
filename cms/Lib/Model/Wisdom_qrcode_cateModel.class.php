<?php
/**
 * Created by PhpStorm.
 * User: win 10
 * Date: 2019/12/18
 * Time: 10:58
 */
class Wisdom_qrcode_cateModel extends Model{

    //todo 添加巡检 同步记录
    public function addOrderTimely($param){
        fdump_api(['参数进来了==='.__LINE__,$param],'timely/addOrderTimely');
        $time=time();
        //查询对应工作人员数据
        $person = $param['qrcode_person'];
        if(empty($person)){
            fdump_api(['缺少工作人员数据==='.__LINE__,$param],'timely/addOrderTimely');
            return true;
        }
        //查询该分类下所有二维码
        $Wisdom_qrcode = D('Wisdom_qrcode')->where([
            'cate_id'=>$param['log']['cate_id']
        ])->getField('id',true);
        if(empty($Wisdom_qrcode)){ //暂无分类数据
            fdump_api(['暂无分类数据==='.__LINE__,D('Wisdom_qrcode')->_sql(),$param],'timely/addOrderTimely');
            return true;
        }
        sort($Wisdom_qrcode);
        //查询已经添加过的巡检记录
        $sql='SELECT
                * 
            FROM (
                SELECT
                    * 
                FROM
                    `'.C('DB_PREFIX').'wisdom_qrcode_record_log` 
                WHERE
                    ( `village_id` = '.$param['village_id'].' ) 
                    AND ( `uid` = '.$param['uid'].' ) 
                    AND ( `qrcode_id` IN ( '.(implode(",",$Wisdom_qrcode)).' ) ) 
                ORDER BY
                    id DESC 
                ) a 
            GROUP BY
                a.qrcode_id 
            ORDER BY
	            a.id DESC ';
        $record_log = M()->query($sql);
        fdump_api(['sql==='.__LINE__,$sql],'timely/addOrderTimely');
        if(empty($record_log)){
            fdump_api(['暂无添加过的巡检记录==='.__LINE__,$sql,$param],'timely/addOrderTimely');
            return true;
        }
        $record_id=[];
        foreach ($record_log as $v){
            $record_id[]=$v['qrcode_id'];
        }
        sort($record_id);
        if($Wisdom_qrcode !== $record_id){
            fdump_api(['巡检记录未添加完==='.__LINE__,$Wisdom_qrcode,$record_id,$param],'timely/addOrderTimely');
            return true;
        }
        $start_time=$end_time='';
        $record_time=empty($person['record_time']) ? [] : explode(',',$person['record_time']);
        $where2=[
            'type'=>2,
            'record_type'=>$person['record_type'],
            'village_id'=>$param['village_id'],
            'order_id'=>$param['order_id'],
            'worker_id'=>$param['worker_id']
        ];
        $startDate=$endDate='';
        switch (intval($person['record_type'])) {
            case 1: //每日
                $start_time=date('Y-m-d '.$person['start_time']);
                $end_time=date('Y-m-d '.$person['end_time']);

                //当天
                $startDate  =  mktime (0,0,0, date ( "m" ), date ( "d" ), date ( "Y" ));
                $endDate  =  mktime (0,0,0, date ( 'm' ), date ( 'd' )+1, date ( 'Y' ))-1;
                break;
            case 2: //每周
                if(empty($record_time)){
                    fdump_api(['$record_time为空==='.__LINE__,$Wisdom_qrcode,$record_id,$param,$record_time],'timely/addOrderTimely');
                    return true;
                }
                if(!in_array(date('N',$time),$record_time)){
                    return true;
                }
                $start_time=date('Y-m-d '.$person['start_time']);
                $end_time=date('Y-m-d '.$person['end_time']);

                //本周
                $w  =  date ( "w" );
                $startDate  =  mktime (0,0,0, date ( "m" ), date ( "d" )- $w +1, date ( "Y" ));
                $endDate = mktime (23,59,59, date ( 'm' ), date ( 'd' )- date ( 'w' )+7, date ( 'Y' ));

                break;
            case 3: //每月
                if(empty($record_time)){
                    return true;
                }
                if(!in_array(date('d',$time),$record_time)){
                    return true;
                }
                $start_time=date('Y-m-d '.$person['start_time']);
                $end_time=date('Y-m-d '.$person['end_time']);

                //本月
                $startDate  =  mktime (0,0,0, date ( "m" ),1, date ( "Y" ));
                $endDate  =  mktime (23,59,59, date ( 'm' ), date ( 't' ), date ( 'Y' ))-1;
                break;
        }
        if(empty($start_time) || empty($end_time)){
            fdump_api(['时间不对==='.__LINE__,$start_time,$end_time,$param],'timely/addOrderTimely');
            return true;
        }
        $start_time=strtotime($start_time);
        $end_time=strtotime($end_time);
        $where2['add_time'] = array('between',array($startDate,$endDate));
        $res=D('House_new_repair_works_order_timely')->where($where2)->getField('id');
        if($res){
            fdump_api(['数据已添加过==='.__LINE__,$start_time,$end_time,$param,$where2,$res],'timely/addOrderTimely');
            return true;
        }
        if($record_log[0]['add_time'] >= $start_time && $record_log[0]['add_time'] <= $end_time){
            $is_status=2;
        }else{
            $is_status=1;
        }
        M('House_new_repair_works_order_timely')->data([
            'village_id'=>$param['village_id'],
            'type'=>2,
            'record_type'=>$person['record_type'],
            'order_id'=>$param['order_id'],
            'group_id'=>$param['group_id'],
            'worker_id'=>$param['worker_id'],
            'is_status'=>$is_status,
            'add_time'=>$time
        ])->add();
        return true;
    }

    public function get_robot_position_record_list($robot_where=array()){
        $field='*,id as index_id';
        $count_user = D('Wisdom_qrcode_position_index')->where($robot_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 8, 'page');
        $dataList = D('Wisdom_qrcode_position_index')->where($robot_where)
            ->field($field)->order('id desc')
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        if($dataList){
            foreach ($dataList as &$v){
                if($v['update_time']){
                    $duration=intval($v['update_time']-$v['add_time']);
                    $duration=D('House_village_car_access_record')->check_park_time($duration);
                    $update_time=date('Y-m-d H:i:s',$v['update_time']);
                }else{
                    $duration=$update_time='--';
                }
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                $v['duration']=$duration;
                $v['update_time']=$update_time;
            }
            unset($v);
        }
        return ['dataList'=>$dataList,'pagebar'=>$p->show()];
    }
    //todo 查询巡检定位记录
    public function get_position_record_list($where){
        $field='b.name,a.id,a.cate_id,a.status,a.add_time,a.update_time,a.id as index_id';
        $count_user = D('Wisdom_qrcode_position_index')
            ->join('as a left join '.C('DB_PREFIX').'house_worker as b ON a.wid = b.wid')
            ->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 8, 'page');
        $dataList = D('Wisdom_qrcode_position_index')
            ->join('as a left join '.C('DB_PREFIX').'house_worker as b ON a.wid = b.wid')
            ->where($where)
            ->field($field)->order('a.id desc')
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        if($dataList){
            foreach ($dataList as &$v){
                if($v['update_time']){
                    $duration=intval($v['update_time']-$v['add_time']);
                    $duration=D('House_village_car_access_record')->check_park_time($duration);
                    $update_time=date('Y-m-d H:i:s',$v['update_time']);
                }else{
                    $duration=$update_time='--';
                }
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                $v['duration']=$duration;
                $v['update_time']=$update_time;
            }
            unset($v);
        }
        return ['dataList'=>$dataList,'pagebar'=>$p->show()];
    }

    //todo 获取当天巡检记录
    public function get_position_record($village_id,$cate_id,$index_id){
        $wisdom_qrcode=D('Wisdom_qrcode');
        $qrcode=$wisdom_qrcode->where([
            'cate_id'=>$cate_id,
            'long'=>['neq', ''],
            'lat'=>['neq', '']
        ])->order('id asc')->field('long,lat')->find();
        $wisdom_qrcode_position_record=D('Wisdom_qrcode_position_record');
        $dataList = $wisdom_qrcode_position_record->where([
            'village_id'=>$village_id,
            'cate_id'=>$cate_id,
            'index_id'=>$index_id
            ])->field('long,lat')->order('id asc')->select();
        $data=[];
        if($dataList){
            foreach ($dataList as $k=>$v){
                $end_long=(float)$dataList[$k]['long'];
                $end_lat=(float)$dataList[$k]['lat'];
                if(isset($dataList[$k+1])){
                    //continue;
                    $end_long=(float)$dataList[$k+1]['long'];
                    $end_lat=(float)$dataList[$k+1]['lat'];
                }
                if($k == 0){
                    $qrcode['long']=$v['long'];
                    $qrcode['lat']=$v['lat'];
                }
                $data[]=[
                    'start_long'=> (float)$v['long'],
                    'start_lat' => (float)$v['lat'],
                    'end_long'  => $end_long,
                    'end_lat'   => $end_lat,
                ];
            }
        }
        return [
            'point'=>['long'=>(float)$qrcode['long'],'lat'=>(float)$qrcode['lat']],
            'list'=>json_encode($data,JSON_UNESCAPED_UNICODE)
        ];
    }

    //todo 监听定位轨迹超时
    public function monitor_position_record(){
        $time=time();
        $sql='SELECT
                    * 
                FROM
                    (
                    SELECT
                        a.id,
                        b.index_id,
                        b.id as bid,
                        IF (b.add_time, b.add_time, 0 ) as add_time,
                        FROM_UNIXTIME( b.add_time, \'%Y-%m-%d %H:%i:%s\' ) AS add_time1
                    FROM
                        `'.C('DB_PREFIX').'wisdom_qrcode_position_index` AS a
                        LEFT JOIN `'.C('DB_PREFIX').'wisdom_qrcode_position_record` `b` ON `b`.`index_id` = `a`.`id` 
                    WHERE
                        a.status = 0 
                        AND a.del_time = 0 
                    ORDER BY
                        b.id DESC 
                    ) a 
                GROUP BY
                    a.id 
                HAVING
                    a.add_time <= ('.$time.' - 120) 
                ORDER BY
                    a.index_id ASC';
        $list = M()->query($sql);
        fdump_api(['进来了'.__LINE__,$sql,$list],'wisdom_qrcode/monitor_log');
        if(!empty($list)){
            $idd=array_column($list, 'id');
            fdump_api(['有数据'.__LINE__,$sql,$idd,$list],'wisdom_qrcode/monitor_log');
            if($idd){
                D('Wisdom_qrcode_position_index')->where([
                    'id'=>['in', $idd]
                ])->data(['status'=>1,'update_time'=>$time])->save();
            }
        }
        return true;
    }

}