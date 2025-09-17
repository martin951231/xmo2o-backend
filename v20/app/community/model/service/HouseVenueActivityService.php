<?php
/**
 * @author : liukezhu
 * @date : 2021/10/8
 */
namespace app\community\model\service;

use app\community\model\db\HouseVenueActivity;
use app\community\model\db\HouseVenueAppointRecord;
use app\community\model\db\HouseVenueClassify;
use app\community\model\db\HouseVenueCollectRecord;
use app\community\model\db\HouseVenueNewsRecord;
use app\community\model\db\HouseVillageUserBind;
use think\facade\Db;
use app\common\model\service\weixin\TemplateNewsService;
use map\longLat;

class HouseVenueActivityService
{
    public $base_map='';//暂无图片 占位
    public $recordStatus=[];
    public $appointMsg=[];
    protected $HouseVenueActivity;
    protected $HouseVenueCollectRecord;
    protected $HouseVenueAppointRecord;
    protected $HouseVillageUserBind;
    protected $HouseVenueNewsRecord;
    protected $HouseVenueClassify;
    protected $time;

    public function __construct()
    {
        $this->HouseVenueActivity = new HouseVenueActivity();
        $this->HouseVenueCollectRecord = new HouseVenueCollectRecord();
        $this->HouseVenueAppointRecord = new HouseVenueAppointRecord();
        $this->HouseVillageUserBind = new HouseVillageUserBind();
        $this->HouseVenueNewsRecord = new HouseVenueNewsRecord();
        $this->HouseVenueClassify = new HouseVenueClassify();
        $this->time=time();
        $this->recordStatus=[
            0=>'未审核',
            1=>'审核通过',
            2=>'审核不通过',
            3=>'取消预约'
        ];
        $this->appointMsg=[
            0=>'未审核',
            1=>'已结束',
            2=>'已拒绝',
            3=>'已取消',
            4=>'取消',
            5=>'已通过',
        ];
        $this->base_map=cfg('site_url').'/static/images/no_data.jpg';
    }

    //==============================todo api接口=========================================================

    //todo 预约时间到期，自动审核不通过
    public function appointRecord($village_id=0){
        if(intval($village_id) < 1){
            return false;
        }
        $where[] = ['r.village_id','=',$village_id];
        $where[] = ['r.status','=',0];
        $where[] = ['a.is_examine','=',1];
        $where['appoint_time']=$this->time;
        $list=$this->HouseVenueAppointRecord->getList($where,'r.id as record_id,r.uid,r.pigcms_id,r.status,r.appoint_time,a.title,r.start_time,r.end_time')->toArray();
        if(!$list){
            return false;
        }
        foreach ($list as $v){
            self::recordSave(['id'=>$v['record_id']],['status'=>2,'cancel_msg'=>'无人审核预约时间超时,自动审核拒绝']);
            self::add_news($v['pigcms_id'],'您预约失败【'.$v['title'].'】',$v['appoint_time'],$v['start_time'],$v['end_time'],2,$v['uid']);
            self::send_notice($v['pigcms_id'],$village_id,$v['record_id'],'场馆预约','【'.$v['title'].'】活动','已拒绝');
        }
        return true;
    }

    public function getUserBind($village_id,$pigcms_id,$filed='*'){
        $where[] = ['village_id','=',$village_id];
        $where[] = ['pigcms_id','=',$pigcms_id];
        $where[] = ['status','=',1];
        $data=$this->HouseVillageUserBind->getOne($where,$filed);
        if($data){
            $data=$data->toArray();
        }
        return $data;
    }

    public function getVenueActivity($village_id,$activity_id,$field='*'){
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$activity_id];
        $where[] = ['is_del','=',0];
        $activity = $this->HouseVenueActivity->getOnes($where,$field);
        if(!$activity){
            throw new \think\Exception("该活动不存在");
        }
        return $activity->toArray();
    }

    /**
     *获取活动列表
     * @author: liukezhu
     * @date : 2021/10/8
     * @param $where
     * @param $field
     * @param $page
     * @param $limit
     * @param $order
     * @return mixed
     */
    public function getActivityList($where,$field,$page,$limit,$order){
        $list = $this->HouseVenueActivity->getList($where,$field,$page,$limit,$order);
        $count=$this->HouseVenueActivity->getCount($where);
        if($list){
            foreach ($list as &$v){
                if(isset($v['img'])){
                    $img=$this->base_map;
                    if(!empty($v['img'])){
                        $img_=unserialize($v['img']);
                        if(isset($img_[0]) && !empty($img_[0])){
                            $img=$img_[0];
                        }
                    }
                    if(!empty($img) && is_string($img)){
                        $v['img']=replace_file_domain($img);
                    }else{
                        $v['img']=$this->base_map;
                    }
                }
            }
            unset($v);
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     *活动详情
     * @author: liukezhu
     * @date : 2021/10/8
     * @param $where
     * @param $field
     * @param int $pigcms_id
     * @return mixed
     */
    public function getActivityDetails($where,$field,$pigcms_id=0){
        $data = $this->HouseVenueActivity->getOne($where,$field);
        if($data){
            $img_data=[];
            if(!empty($data['img'])){
                $img=unserialize($data['img']);
                if(!empty($img)){
                    foreach (array_values($img) as &$v){

                        if(!empty($v) && is_string($v)){
                            $v=replace_file_domain($v);
                        }else{
                            $v=$this->base_map;
                        }
                        $img_data[]=$v;
                    }
                }
            }
            else{
                $img_data=[
                    $this->base_map
                ];
            }
            $data['img']=$img_data;
//            $data['contacts']=htmlspecialchars($data['contacts']);
            $is_user_collect=0;
            if(intval($pigcms_id) > 0){
                $where2=[];
                $where2[] = ['pigcms_id','=',$pigcms_id];
                $where2[] = ['activity_id','=',$data['activity_id']];
                $collect_record=$this->HouseVenueCollectRecord->getOne($where2,'id');
                if($collect_record){
                    $is_user_collect=1;
                }
            }
            $data['is_user_collect']=$is_user_collect;
            $data['start_day']=date('Y-m-d',$this->time);
            $data['end_day']=date('Y-m-d',strtotime(date('Y-m-d',$this->time)." +".(abs(intval($data['appoint_cycle'])-1))." day"));
            if(intval($data['status']) == 0 && intval($data['close_time']) < $this->time){
                $data['status']=0;
            }
            else{
                $data['status']=1;
                $data['close_msg']='';
            }
            //关闭活动时，不可预约
            if(intval($data['status']) == 0){
                $data['is_appoint']=0;
            }
            $gcjLongLat = (new longLat())->baiduToGcj02($data['lat'], $data['long']);
            $data['lat']=(string)$gcjLongLat['lat'];
            $data['long']=(string)$gcjLongLat['lng'];
            $data['content']=replace_file_domain_content($data['content']);
            unset($data['add_time'],$data['close_time'],$data['appoint_cycle']);
        }
        return $data;
    }

    /**
     *活动收藏
     * @author: liukezhu
     * @date : 2021/10/8
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function activityCollect($param){
        $activity=self::getVenueActivity($param['village_id'],$param['activity_id']);
        if(intval($activity['is_collect']) == 0){
            throw new \think\Exception("该活动暂不可操作或取消收藏");
        }
        $where=[];
        $where[] = ['pigcms_id','=',$param['pigcms_id']];
        $where[] = ['activity_id','=',$activity['id']];
        $where[] = ['village_id','=',$activity['village_id']];
        $collect_record=$this->HouseVenueCollectRecord->getOne($where,'id');
        $data=[];
        if($param['type'] == 1){ //1:收藏 2：取消收藏
            if($collect_record){
                $data['status']=0;
                $data['cancel_msg']='您已收藏过,无须重复操作';
            }else{
                $this->HouseVenueCollectRecord->add([
                    'uid'=>$param['uid'],
                    'pigcms_id'=>$param['pigcms_id'],
                    'village_id'=>$activity['village_id'],
                    'activity_id'=>$activity['id'],
                    'add_time'=>$this->time
                ]);
                $data['status']=1;
                $data['msg']='收藏成功';
            }
        }else{
            if(!$collect_record){
                $data['status']=0;
                $data['msg']='您没有收藏过,无须重复操作';
            }else{
                $where=[];
                $where[] = ['pigcms_id','=',$param['pigcms_id']];
                $where[] = ['id','=',$collect_record['id']];
                $this->HouseVenueCollectRecord->del($where);
                $data['status']=1;
                $data['msg']='取消收藏成功';
            }
        }
        return $data;
    }

    /**
     * 返回预约时间段
     * @author: liukezhu
     * @date : 2021/10/8
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function choiceDate($param){
        $activity=self::getVenueActivity($param['village_id'],$param['activity_id'],'id,is_appoint,appoint_cycle,appoint_num,appoint_time,add_time,status,close_time');
        if(intval($activity['is_appoint']) == 0){
            throw new \think\Exception("该活动不支持预约");
        }
        $activity['appoint_cycle']=intval($activity['appoint_cycle']);
        $appoint_time=!empty($activity['appoint_time']) ? unserialize($activity['appoint_time']) : [];
        $day=[];
        $sql=[];
        $close_time=(empty($activity['close_time']) || ($activity['close_time'] < $this->time)) ? $this->time : $activity['close_time'];
        if(intval($activity['status']) == 0 && strtotime($param['day']) >= $close_time){
            return $day;
        }
        for ($x=0; $x< $activity['appoint_cycle']; $x++) {
            $day_=date('Y-m-d',strtotime(date('Y-m-d',$this->time)." +".$x." day"));
            if($day_ != $param['day']){
                continue;
            }
            if($appoint_time){
                foreach ($appoint_time as $v){
                    $start=$day_.' '.$v['start'];
                    $end=$day_.' '.$v['end'];
                    $day[]=[
                        'time'=>$v['start'].'-'.$v['end']
                    ];
                    $sql[]='(SELECT
                                COUNT( * ) AS n 
                            FROM
                                `pigcms_house_venue_appoint_record` 
                            WHERE
                                `activity_id` = '.$activity['id'].' 
                                AND `status` = 1 
                                AND `appoint_time` >= '.strtotime($start).' 
                                AND `appoint_time` < '.strtotime($end).')';
                }
            }
        }
        if($sql){
            $rr=$this->HouseVenueAppointRecord->query_sql(implode('UNION ALL',$sql));
            foreach ($day as $k=>&$v){
                $v['appoint_num']=$rr[$k]['n'];
                $v['appoint_total']=$activity['appoint_num'];
                $v['appoint_status']=($rr[$k]['n'] >= $activity['appoint_num'])? 0 : 1;
            }
            unset($v);
        }
        return $day;
    }

    /**
     *点击预约活动
     * @author: liukezhu
     * @date : 2021/10/9
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function subAppoint($param){
        $data=[];
        $activity=self::getVenueActivity($param['village_id'],$param['activity_id'],'id,title,is_appoint,appoint_cycle,appoint_num,appoint_time,is_examine,status,close_time,add_time');
        if(empty($param['user_arr'])){
            throw new \think\Exception("该用户不存在");
        }
        if(intval($activity['is_appoint']) == 0){
            throw new \think\Exception("该活动不支持预约");
        }
        if(intval($activity['appoint_num']) <= 0){
            throw new \think\Exception("该活动预约人数不足");
        }
        $appoint_time=!empty($activity['appoint_time']) ? unserialize($activity['appoint_time']) : [];
        if(empty($appoint_time)){
            throw new \think\Exception("该活动暂未分配时间段预约");
        }
        $check_day=false;
        for ($x=0; $x< $activity['appoint_cycle']; $x++) {
            $day_=date('Y-m-d',strtotime(date('Y-m-d',$this->time)." +".$x." day"));
            if($day_ != $param['day']){
                continue;
            }
            $check_day=true;
        }
        if(!$check_day){
            throw new \think\Exception("该活动预约日期不存在");
        }
        $start='';
        $end='';
        foreach ($appoint_time as $v){
            if((isset($param['time'][0]) && $v['start'] == $param['time'][0]) && (isset($param['time'][1]) && $v['end'] == $param['time'][1])){
                $start= $v['start'];
                $end  = $v['end'];
            }
        }
        if(empty($start) || empty($end)){
            throw new \think\Exception("预约的时间段不存在");
        }
        $start_time=strtotime($param['day'].' '.$start);
        $end_time=strtotime($param['day'].' '.$end);
        if($end_time < $this->time){
            throw new \think\Exception("该预约时间段已过期,请重新选择");
        }
        if($activity['status'] == 0 && $start_time >= $activity['close_time']){
            throw new \think\Exception("该预约于".(date('Y-m-d',$activity['close_time'])).'关闭，请选择其他时间段');
        }
        $ids=mt_rand(1,9);
        $record_number='HDCG-'.date('Ymd',$this->time).substr(microtime(), 2, 9 - strlen($ids)).$ids;
        Db::startTrans();
        try {
            $where[] = ['activity_id','=',$activity['id']];
            $where[] = ['pigcms_id','=',$param['pigcms_id']];
            $where[] = ['appoint_time','>=',$start_time];
            $where[] = ['appoint_time','<',$end_time];
            $where[] = [ 'status','in',[0,1]];
            $record_uid=$this->HouseVenueAppointRecord->getCount($where);
            if(intval($record_uid) > 0){
                throw new \think\Exception("该时间段您已预约");
            }
            $where=[];
            $where[] = ['activity_id','=',$activity['id']];
            $where[] = ['appoint_time','>=',$start_time];
            $where[] = ['appoint_time','<',$end_time];
            $where[] = ['status','=',1];
            $record_num=$this->HouseVenueAppointRecord->getCount($where);
            if($record_num >= intval($activity['appoint_num'])){
                throw new \think\Exception("该活动预约人数已满");
            }
            unset($activity['appoint_time']);
            $rr=[
                'uid'=>$param['user_arr']['uid'],
                'pigcms_id'=>$param['pigcms_id'],
                'village_id'=>$param['village_id'],
                'record_number'=>$record_number,
                'activity_id'=>$param['activity_id'],
                'appoint_time'=>$start_time,
                'start_time'=>$start,
                'end_time'=>$end,
                'name'=>$param['user_arr']['name'],
                'phone'=>$param['user_arr']['phone'],
                'info'=>serialize($activity),
                'remarks'=>$param['remarks'],
                'add_time'=>$this->time
            ];
            if($activity['is_examine'] == 1){
                //自动审核
                $rr['status']=1;
                $rr['examine_msg']='系统开启自动审核通过';
                $data['status']=1;
                $data['msg']='预约成功';

            }
            else{
                //手动审核
                $rr['status']=0;
                $data['status']=2;
                $data['msg']='提交成功，等待管理员审核';
            }
            $res=$this->HouseVenueAppointRecord->add($rr);
            if($activity['is_examine'] == 1 && $res){
                self::add_news($param['pigcms_id'],'您预约成功【'.$activity['title'].'】',$start_time,$start,$end,1,$rr['uid']);
                $remark='预约日期：'.(date('Y-m-d',$start_time)).' '.$start.'-'.$end;
                self::send_notice($param['pigcms_id'],$param['village_id'],$res,'场馆预约','【'.$activity['title'].'】活动','已通过',$remark);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $data['status']=0;
            $data['msg']='预约失败';
            throw new \think\Exception($e->getMessage());
        }
        return $data;
    }

    //发送通知
    public function send_notice($pigcms_id,$village_id,$record,$first,$title,$msg,$remark=''){
        $site_url = cfg('site_url');
        $templateNewsService =new TemplateNewsService();
        $service_login = new ManageAppLoginService();
        $base_url = $service_login->base_url;
        $href=$site_url . $base_url . "pages/houseMeter/venuerese/appointdetail?pigcms_id=".$pigcms_id."&village_id=".$village_id.'&record_id='.$record;
        $user = (new HouseVillageUserBind())->getUserBindInfo(['hvb.pigcms_id' => $pigcms_id],'u.uid,u.openid');
        if ($user && $user['openid']) {
            $data = [
                'tempKey' => 'OPENTM405462911',
                'dataArr' => [
                    'href'      => $href,
                    'wecha_id'  => $user['openid'],
                    'first'     => $first,
                    'keyword1'  => $title,
                    'keyword2'  => $msg,
                    'keyword3'  => date('H时i分',$_SERVER['REQUEST_TIME']),
                    'remark'    => (empty($remark) ? '\n请点击查看详细信息！' : $remark)
                ]
            ];
            $rr=$templateNewsService->sendTempMsg($data['tempKey'],$data['dataArr']);
            fdump_api(['发送通知--'.__LINE__,$rr,$data],'venue/send_log',1);
    }

//        $login_log=\think\facade\Db::name('appapi_app_login_log')->where([
//            'uid' => $user['uid'],
//            'device_id'=>['neq','packapp']
//        ])->where('client <> 0')->order('pigcms_id desc')->find();
//
//        $new_arr=[
//            'href'=>'',
//            'title'=>'',
//            'msg'=>''
//        ];
//        if($login_log){
//            $tmp[]  =   str_replace('-','',$login_log['device_id']);
//            $audience   =   array('tag'=>$tmp);
//            invoke_cms_model('Group_order/AuroraMass', [
//                $login_log['client'], $new_arr['title'], $new_arr['msg'], $new_arr,$audience
//            ]);
//        }
        return true;
    }

    //todo 写入消息记录
    public function add_news($pigcms_id,$title,$appoint_time,$start_time,$end_time,$status,$uid=0){
        if($uid == 0){
            $user_bin=$this->HouseVillageUserBind->getOne(['pigcms_id'=>$pigcms_id],'uid');
            if($user_bin){
                $uid=$user_bin['uid'];
            }
        }
        $param=[
            'pigcms_id'=>$pigcms_id,
            'uid'=>$uid,
            'type'=>1,
            'title'=>$title,
            'content'=>'时间：'.(date('Y-m-d',$appoint_time).' '.$start_time.'至'.$end_time),
            'status'=>$status,
            'add_time'=>$this->time
        ];
        return $this->HouseVenueNewsRecord->add($param);
    }

    /**
     *查询我的预约
     * @author: liukezhu
     * @date : 2021/10/9
     * @param $where
     * @param $field
     * @param $page
     * @param $limit
     * @param $order
     * @return mixed
     */
    public function getMyAppoint($where,$field,$page,$limit,$order){
        $list = $this->HouseVenueAppointRecord->getLists($where,$field,$page,$limit,$order);
        $count=$this->HouseVenueAppointRecord->getCounts($where);
        if($list){
            $list=$list->toarray();
            foreach ($list as &$v){
                $appoint_status=self::check_appoint($v['status'],$v);
                $v['date']=date('Y-m-d',$v['appoint_time']).' '.$v['start_time'].'至'.$v['end_time'];
                $v['appoint_status']=$appoint_status;
                $v['appoint_msg']=$this->appointMsg[$appoint_status];
                unset($v['info'],$v['appoint_time'],$v['start_time'],$v['end_time'],$v['status'],$v['close_status'],$v['close_time'],$v['is_del']);
            }
            unset($v);
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    //todo 校验返回活动状态
    private function check_appoint($status,$d,$type=0){
        $appoint_status=$status;
        if((intval($d['close_status']) == 0 && $d['close_time'] > $this->time) || (intval($d['close_status']) == 1)){
            if(intval($d['is_del']) == 0 && $d['appoint_time'] > $this->time && $d['status'] == 1){
                if($type == 1){
                    $appoint_status=5;
                }else{
                    $appoint_status=4;
                }
            }
        }
        return $appoint_status;
    }


    /**
     * 查询预约数据
     * @author: liukezhu
     * @date : 2021/10/9
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getAppointDetails($where,$field){
        $data= $this->HouseVenueAppointRecord->getOne($where,$field);
        if(!$data){
            throw new \think\Exception("该预约不存在!");
        }
        $gcjLongLat = (new longLat())->baiduToGcj02($data['lat'], $data['long']);
        $data['cancel_time']=empty($data['cancel_time']) ? '' : date('Y-m-d H:i:s',$data['cancel_time']);
        if($data['status'] == 2){
            $data['cancel_time']=$data['cancel_msg']='';
        }
        elseif ($data['status'] == 3){
            $data['examine_msg']='';
        }
        $appoint_status=self::check_appoint($data['status'],$data,1);
        $data['appoint_day']=date('Y-m-d',$data['appoint_time']);
        $data['appoint_time']=$data['start_time'].' 至 '.$data['end_time'];
        $data['lat']=(string)$gcjLongLat['lat'];
        $data['long']=(string)$gcjLongLat['lng'];
        $data['appoint_msg']=$this->appointMsg[$appoint_status];
        $data['appoint_status']=($appoint_status == 5) ? 4 : $appoint_status;
        unset($data['start_time'],$data['status'],$data['end_time'],$data['close_status'],$data['close_time'],$data['is_del']);
        return $data;
    }

    //todo 预约数据
    public function getAppointInfo($where,$field=true,$type=1){
        $data= $this->HouseVenueAppointRecord->getOne($where,$field);
        $rr=[];
        if(empty($data)){
            throw new \think\Exception("该预约不存在!");
        }
        if(intval($data['close_status']) == 0 && $data['close_time'] < $this->time){
            throw new \think\Exception("该活动已关闭，不可取消预约!");
        }
        if(($data['status'] != 1) || ($data['appoint_time'] < $this->time) || (intval($data['is_del']) > 0)){
            throw new \think\Exception("该预约不可取消!");
        }
        if($type == 1){
            $rr['record_id']=$data['id'];
            $rr['name']=$data['name'];
            $rr['cancel_time']=date('Y-m-d H:i:s',$this->time);
        }else{
            $rr=$data;
        }
        return $rr;
    }

    /**
     * 取消预约
     * @author: liukezhu
     * @date : 2021/10/11
     * @param $where
     * @param $reason
     * @return array
     * @throws \think\Exception
     */
    public function subCancelAppoint($where,$reason){
        $data=self::getAppointInfo($where,'r.id,r.uid,r.village_id,r.pigcms_id,r.status,r.appoint_time,r.start_time,r.end_time,a.title,a.status as close_status,a.close_time,a.is_del',2);
        $rr=self::recordSave(['id'=>$data['id']],['status'=>3,'cancel_msg'=>$reason,'cancel_time'=>$this->time]);
        if($rr){
            self::add_news($data['pigcms_id'],'您已取消预约【'.$data['title'].'】',$data['appoint_time'],$data['start_time'],$data['end_time'],3,$data['uid']);
            $remark='预约日期：'.(date('Y-m-d',$data['appoint_time'])).' '.$data['start_time'].'-'.$data['end_time'];
            self::send_notice($data['pigcms_id'],$data['village_id'],$data['id'],'场馆预约','【'.$data['title'].'】活动','已取消',$remark);
            return ['status'=>1,'msg'=>'取消成功'];
        }else{
            return ['status'=>0,'msg'=>'取消失败'];
        }
    }

    //todo 消息类型数据
    public function appointNewsType($source_type){
        $data=[
            array(
                'key'=>1,
                'value'=>'场馆预约提醒',
                'children'=>array(
                    array(
                        'key'=>1,
                        'value'=>'审核通过'
                    ),
                    array(
                        'key'=>2,
                        'value'=>'审核不通过'
                    ),
                    array(
                        'key'=>3,
                        'value'=>'取消预约'
                    )
                )
            ),
            array(
                'key'=>2,
                'value'=>'装修预约提醒',
                'children'=>array(
                    array(
                        'key'=>1,
                        'value'=>'审核通过'
                    ),
                    array(
                        'key'=>2,
                        'value'=>'审核不通过'
                    )
                )
            )
        ];
        if($source_type == 3){
            $data=[
                array(
                    'key'=>3,
                    'value'=>'预约服务',
                    'children'=>array(
                        array(
                            'key'=>1,
                            'value'=>'审核通过'
                        ),
                        array(
                            'key'=>2,
                            'value'=>'审核不通过'
                        )
                    )
                )
            ];
        }
        return ['list'=>$data];
    }

    /**
     *我的消息数据
     * @author: liukezhu
     * @date : 2021/10/11
     * @param $where
     * @param $field
     * @param $page
     * @param $limit
     * @param $order
     * @return mixed
     */
    public function myAppointNews($where,$field,$page,$limit,$order){
        $list = $this->HouseVenueNewsRecord->getList($where,$field,$page,$limit,$order);
        if($list){
            foreach ($list as &$v){
                $icon='';
                switch (intval($v['status'])) {
                    case 1:
                        $icon=cfg('site_url').'/static/images/venue/success_icon.png';
                        break;
                    case 2:
                        $icon=cfg('site_url').'/static/images/venue/fail_icon.png';
                        break;
                    case 3:
                        $icon=cfg('site_url').'/static/images/venue/cancel.png';
                        break;
                }
                $v['icon']=$icon;
            }
            unset($v);
        }
        $count=$this->HouseVenueNewsRecord->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 我的收藏
     * @author: liukezhu
     * @date : 2021/10/12
     * @param $where
     * @param $field
     * @param $page
     * @param $limit
     * @param $order
     * @return mixed
     */
    public function myCollectList($where,$field,$page,$limit,$order){
        $list = $this->HouseVenueCollectRecord->getList($where,$field,$page,$limit,$order);
        $count=$this->HouseVenueCollectRecord->getCount($where);
        if($list){
            foreach ($list as &$v){
                $img=$this->base_map;
                if(!empty($v['img'])){
                    $img_=unserialize($v['img']);
                    if(isset($img_[0]) && !empty($img_[0])){
                        $img=$img_[0];
                    }
                }
                if(!empty($img) && is_string($img)){
                    $v['img']=replace_file_domain($img);
                }else{
                    $v['img']=$this->base_map;
                }
            }
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }


    //====================todo 后台接口==========================================


    //活动编辑
    public function activitySave($where,$param){
        return $this->HouseVenueActivity->save_one($where,$param);
    }

    //活动分类编辑
    public function classifySave($where,$param){
        return $this->HouseVenueClassify->save_one($where,$param);
    }

    //预约记录编辑
    public function recordSave($where,$param){
        return $this->HouseVenueAppointRecord->saveOne($where,$param);
    }


    //todo 活动列表
    public function HtActivityList($where,$field,$page,$limit,$order){
        $list = $this->HouseVenueActivity->getLists($where,$field,$page,$limit,$order);
        if($list){
            foreach ($list as &$v){
                $close_msg='';
                if(isset($v['close_time']) && !empty($v['close_time'])){
                    if($v['status'] == 0 &&  $v['close_time'] < $this->time){
                        $close_msg='关闭';
                    }elseif ($v['status'] == 0 &&  $v['close_time'] >= $this->time){
                        $close_msg='【'.date('Y-m-d',$v['close_time']).'】触发关闭';
                    }
                }
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
                $v['close_msg']=$close_msg;
            }
            unset($v);
        }
        $count=$this->HouseVenueActivity->getCounts($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    //todo 获取活动分类数据
    public function HtClassify($where,$field,$order){
        return $this->HouseVenueClassify->getList($where,$field,0,0,$order);
    }


    //二维数组去掉重复值
    public function a_array_unique($array){
        $out = array();
        if(empty($array)){
            return $out;
        }
        foreach ($array as $key=>$value) {
            if (!in_array($value, $out)){
                $out[$key] = $value;
            }
        }
        $out = array_values($out);
        return $out;
    }


    //活动数据整合
    private function activityHandle($param){
        if(!empty($param['img'])){
            $param['img']=serialize($param['img']);
        }else{
            $param['img']='';
        }
        if(empty($param['adress'])){
            throw new \think\Exception("请输入场馆地址");
        }
        $appoint_time='';
        if(($param['start'] && $param['end']) && intval($param['is_appoint']) == 1){
            $param['start']=array_values($param['start']);
            $param['end']=array_values($param['end']);
            $times=[];
            foreach ($param['start'] as $k=>$v){
                if(!empty($v) && (isset($param['end'][$k]) && !empty(isset($param['end'][$k])))){
                    if($v >= $param['end'][$k]){
                        continue;
                    }
                    $times[]=[
                        'start'=>$v,
                        'end'=>$param['end'][$k]
                    ];
                }
            }
            $times=self::a_array_unique($times);
            if(empty($times)){
                throw new \think\Exception("设置的时间段不合法，请重新设置!");
            }
            $appoint_time=serialize($times);
        }
        $param['appoint_time']=$appoint_time;
        unset($param['start'],$param['end']);
        return $param;
    }

    //todo 添加活动
    public function HtActivityAdd($param){
        $param=self::activityHandle($param);
        $param['add_time']=$this->time;
        $param['status']=1;
        $param['is_examine']=1;
        $param['is_del']=0;
        return $this->HouseVenueActivity->add($param);
    }


    /**
     * 查询活动数据
     * @author: liukezhu
     * @date : 2021/11/1
     * @param $where
     * @param $field
     * @param int $type
     * @return array
     * @throws \think\Exception
     */
    public function HtActivityEdit($where,$field,$type=1){
        $data=$this->HouseVenueActivity->getOnes($where,$field);
        if(!$data){
            throw new \think\Exception("该活动不存在!");
        }
        $data=$data->toArray();
        if($type == 1){
            $appoint_time=[];
            $startTime=[];
            $endTime=[];
            $img=[];
            if(!empty($data['appoint_time'])){
                $appoint_time=unserialize($data['appoint_time']);
                if(!empty($appoint_time)){
                    foreach ($appoint_time as $v){
                        if($v['start']){
                            $startTime[]=$v['start'];
                        }
                        if($v['end']){
                            $endTime[]=$v['end'];
                        }
                    }
                }
            }
            if(!empty($data['img'])){
                $data['img']=unserialize($data['img']);
                foreach ($data['img'] as $k=>$v){
                    if(!empty($v) && is_string($v)){
                        $img[]=array(
                            'uid'=>$k,
                            'name'=>'图片'.$k.'.png',
                            'status'=>'done',
                            'thumbUrl'=>replace_file_domain($v),
                            'response'=>replace_file_domain($v)
                        );
                    }

                }
                unset($v);
            }
            if(empty($data['appoint_cycle'])){
                $data['appoint_cycle']='';
            }
            if(empty($data['appoint_num'])){
                $data['appoint_num']='';
            }
            $data['img']=[];
            unset($data['appoint_time']);
            $param=[
                'itemcount'=>empty($appoint_time) ? 1 : count($appoint_time),
                'startTime'=>$startTime,
                'endTime'=>$endTime,
                'is_appoint'=>intval($data['is_appoint']) ? true : false,
                'fileList'=>$img
            ];
            return ['data'=>$data,'param'=>$param];
        }
        else{
            return $data;
        }
    }

    //todo 查询活动数据
    private function getActivity($id,$village_id,$field=true){
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$id];
        $where[] = ['is_del','=',0];
        return self::HtActivityEdit($where,$field,2);
    }

    //todo 提交活动数据
    public function HtActivitySub($param){
        self::getActivity($param['id'],$param['village_id'],'id');
        $param=self::activityHandle($param);
        $where[] = ['village_id','=',$param['village_id']];
        $where[] = ['id','=',$param['id']];
        $where[] = ['is_del','=',0];
        $param['update_time']=$this->time;
        unset($param['id']);
        return self::activitySave($where,$param);
    }

    //todo 分类列表
    public function HtClassifyList($where,$field,$page,$limit,$order){
        $list = $this->HouseVenueClassify->getList($where,$field,$page,$limit,$order);
        $count=$this->HouseVenueClassify->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    //todo 添加活动
    public function HtClassifyAdd($param){
        $param['add_time']=$this->time;
        $param['status']=1;
        return $this->HouseVenueClassify->add($param);
    }

    //todo 查询分类数据
    public function HtClassifyEdit($id,$village_id){
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$id];
        $where[] = ['status','=',1];
        $field='id,title,sort';
        $data=$this->HouseVenueClassify->getOnes($where,$field);
        if(!$data){
            throw new \think\Exception("该数据不存在!");
        }
        return $data;
    }

    //todo 活动分类提交
    public function HtClassifySub($param){
        self::HtClassifyEdit($param['id'],$param['village_id']);
        $where[] = ['village_id','=',$param['village_id']];
        $where[] = ['id','=',$param['id']];
        $where[] = ['status','=',1];
        $param['update_time']=$this->time;
        unset($param['id']);
        return self::classifySave($where,$param);
    }

    //todo 预约列表
    public function HtRecordList($where,$field,$page,$limit,$order){
        $list = $this->HouseVenueAppointRecord->getListss($where,$field,$page,$limit,$order);
        if($list){
            $list=$list->toArray();
            foreach ($list as &$v){
                $v['appoint_time']=date('Y-m-d',$v['appoint_time']);
                $v['times']=$v['start_time'].'至'.$v['end_time'];
                $v['status_msg']=$this->recordStatus[$v['status']];
                unset($v['start_time'],$v['end_time']);
            }
            unset($v);
        }
        $count=$this->HouseVenueAppointRecord->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    //todo 查询预约数据
    public function HtRecordEdit($id,$village_id,$type=1){
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$id];
        $field='id,uid,pigcms_id,activity_id,record_number,name,phone,start_time,end_time,appoint_time,status,cancel_msg,remarks,cancel_time,examine_msg';
        $data=$this->HouseVenueAppointRecord->getOnes($where,$field);
        if(!$data){
            throw new \think\Exception("该数据不存在!");
        }
        $data=$data->toArray();
        if($type == 1){
            $data['appoint_time']=date('Y-m-d',$data['appoint_time']);
            $data['times']=$data['start_time'].'至'.$data['end_time'];
            $data['status_msg']=$this->recordStatus[$data['status']];
            $data['remarks']=(empty($data['remarks']) && $data['status'] > 0) ? '暂无备注' : $data['remarks'];
            $data['examine_status']=false;
            if(intval($data['status']) > 0){
                $data['examine_status']=true;
            }
            $data['cancel_time']=date('Y-m-d H:i:s',$data['cancel_time']);;
            unset($data['start_time'],$data['end_time']);
        }
        return $data;
    }

    //todo 预约审核
    public function HtRecordSub($param){
        $record=self::HtRecordEdit($param['id'],$param['village_id'],2);
        $where[] = ['id','=',$record['activity_id']];
        $where[] = ['is_del','=',0];
        $field='id,title';
        $activity=self::HtActivityEdit($where,$field,2);
        if(intval($record['status']) > 0){
            return true;
        }
        if(intval($record['status']) == 0 && intval($param['status']) == 0){
            throw new \think\Exception("请选择审核状态!");
        }
        if(!in_array($param['status'],[1,2])){
            throw new \think\Exception("审核参数不合法!");
        }
        $where=[];
        $where[] = ['village_id','=',$param['village_id']];
        $where[] = ['id','=',$param['id']];
        if(!empty($param['examine_msg'])){
            $rr['examine_msg']=$param['examine_msg'];
        }
        $rr['status']=$param['status'];
        $rr['update_time']=$this->time;
        if($param['status'] == 1){
            //审核通过
            self::add_news($record['pigcms_id'],'您预约成功【'.$activity['title'].'】',$record['appoint_time'],$record['start_time'],$record['end_time'],1,$record['uid']);
            $remark='预约日期：'.(date('Y-m-d',$record['appoint_time'])).' '.$record['start_time'].'-'.$record['end_time'];
            self::send_notice($record['pigcms_id'],$param['village_id'],$param['id'],'场馆预约','【'.$activity['title'].'】活动','已通过',$remark);
        }
        else{
            //审核不通过
            self::add_news($record['pigcms_id'],'您预约失败【'.$activity['title'].'】',$record['appoint_time'],$record['start_time'],$record['end_time'],2,$record['uid']);
            self::send_notice($record['pigcms_id'],$param['village_id'],$param['id'],'场馆预约','【'.$activity['title'].'】活动','已拒绝',trim($param['examine_msg']));
        }
        return self::recordSave($where,$rr);
    }

    //todo 删除活动
    public function activityDel($id,$village_id){
        $where=[];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$id];
        $where[] = ['is_del','=',0];
        $param['is_del']=$this->time;
        $param['update_time']=$this->time;
        //活动删除
        self::activitySave($where,$param);
        $where=[];
        $where[] = ['r.village_id','=',$village_id];
        $where[] = ['r.activity_id','=',$id];
        $where[] = ['r.status','=',0];
        $field='r.id,r.uid,r.village_id,r.pigcms_id,start_time,r.end_time,r.appoint_time,a.title';
        $list = $this->HouseVenueAppointRecord->getLists($where,$field,0,0);
        if($list){
            $list=$list->toArray();
            foreach ($list as $v){
                $where=[];
                $where[] = ['id','=',$v['id']];
                $where[] = ['status','=',0];
                $param=[];
                $param['status']=2;
                $param['examine_msg']='审核失败';
                $param['update_time']=$this->time;
                //todo 删除活动 已预约的处理审核不通过
                self::recordSave($where,$param);
                self::add_news($v['pigcms_id'],'您预约失败【'.$v['title'].'】',$v['appoint_time'],$v['start_time'],$v['end_time'],2,$v['uid']);
                self::send_notice($v['pigcms_id'],$v['village_id'],$v['id'],'场馆预约','【'.$v['title'].'】活动','已拒绝','');
            }
        }
        return true;
    }

    //todo 活动关闭
    public function activityClose($param){
        $activity=self::getActivity($param['id'],$param['village_id'],'status');
        if($activity['status'] != 1){
            throw new \think\Exception("该活动已关闭!");
        }
        $where=[];
        $where[] = ['village_id','=',$param['village_id']];
        $where[] = ['id','=',$param['id']];
        $where[] = ['is_del','=',0];
        $rr['status']=0;
        $rr['close_time']=$param['close_time'];
        $rr['close_msg']=$param['close_msg'];
        $rr['update_time']=$this->time;
        //活动状态修改
        $rel=self::activitySave($where,$rr);
        //待审核核销
        self::activityExpire($param['id']);
        return $rel;
    }

    //todo 活动开启
    public function activityOpen($id,$village_id){
        $activity=self::getActivity($id,$village_id,'status');
        if($activity['status'] != 0){
            throw new \think\Exception("该活动已开启!");
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$id];
        $where[] = ['is_del','=',0];
        $where[] = ['status','=',0];
        $rr['status']=1;
        $rr['close_time']=0;
        $rr['close_msg']=null;
        $rr['update_time']=$this->time;
        //活动关闭
        return self::activitySave($where,$rr);
    }

    //todo 活动关闭执行预约失效
    public function activityExpire($activity_id){
        $where[] = ['a.id','=',$activity_id];
        $where[] = ['a.status','=',0];
        $where[] = ['a.is_del','=',0];
        $where[] = ['r.status','in',[0,1]];
        $list=$this->HouseVenueActivity->getAppointRecord($where,'r.id,r.uid,r.village_id,r.pigcms_id,a.title,r.start_time,r.end_time,r.appoint_time,r.status,a.close_msg');
        if(!$list){
            return false;
        }
        foreach ($list as $v){
            $where=[];
            $where[] = ['id','=',$v['id']];
            $where[] = [ 'status','in',[0,1]];
            $param=[];
            $param['update_time']=$this->time;
            if($v['status'] == 0){
                //todo 关闭活动 待审核处理 审核拒绝
                $param['status']=2;
                $param['examine_msg']=$v['close_msg'];
                self::recordSave($where,$param);
                self::add_news($v['pigcms_id'],'您预约失败【'.$v['title'].'】',$v['appoint_time'],$v['start_time'],$v['end_time'],2,$v['uid']);
                self::send_notice($v['pigcms_id'],$v['village_id'],$v['id'],'场馆预约','【'.$v['title'].'】活动','已拒绝','');
            }elseif ($v['status'] == 1){
                //todo 关闭活动 审核通过处理 取消预约
                $param['status']=3;
                $param['cancel_time']=$this->time;
                $param['cancel_msg']=$v['close_msg'];
                self::recordSave($where,$param);
                self::add_news($v['pigcms_id'],'您的预约取消【'.$v['title'].'】',$v['appoint_time'],$v['start_time'],$v['end_time'],3,$v['uid']);
                self::send_notice($v['pigcms_id'],$v['village_id'],$v['id'],'场馆预约','【'.$v['title'].'】活动','已取消','');
            }
        }
        return true;
    }

    //todo 活动默认经纬度
    public function HtActivityLocation(){
        return [
            'lng'=>'106.35586',
            'lat'=>'27.759588'
        ];
    }

}

