<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      机器人-CROSS-队列中转
 */

namespace app\thirdRobot\model\service;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\weixin\TemplateNewsService;
use app\thirdRobot\model\db\ThirdRobotSubscribeMessage;
use app\community\model\db\HouseVillage;
use app\common\model\db\Admin;
use app\community\model\db\HouseWorker;
use app\thirdRobot\model\db\ThirdRobotAlarmNoticeSet;
class ThirdRobotSubscribeMessageService
{

    public function saveOneRobotMessage($where = array(), $saveData = array())
    {
        $thirdRobotSubscribeMessageDb = new ThirdRobotSubscribeMessage();
        $ret = $thirdRobotSubscribeMessageDb->saveOneData($where, $saveData);
        return $ret;
    }

    public function addOneRobotMessage($addTask = array())
    {
        $thirdRobotSubscribeMessageDb = new ThirdRobotSubscribeMessage();
        $inset_id = $thirdRobotSubscribeMessageDb->addOneData($addTask);
        return $inset_id;
    }

    public function sendNoticeToAdmin($item=array(),$msgInfo=array(),$type='alarm'){
        if(empty($item) || empty($msgInfo)){
            return true;
        }
        fdump_api(['item'=>$item,'msgInfo'=>$msgInfo,'type'=>$type],'thirdRobot/00sendNoticeToAdmin',1);
        if($type=='alarm'){
            $adminInfo='';
            $id_type=0;
            if($item && isset($item['id_type']) && $item['id_type']==1){
                $wid=$item['person_id'];
                $id_type=1;
                $whereArr=array();
                $houseWorkerDb= new HouseWorker();
                $whereArr[]=['wid','=',$wid];
                $whereArr[]=['status','=',1];
                $field='wid,village_id,account,name as realname,phone,openid,nickname';
                $adminInfo=$houseWorkerDb->get_one($whereArr,$field);
                if($adminInfo && !$adminInfo->isEmpty()) {
                    $adminInfo = $adminInfo->toArray();
                }
            }else{
                $aid=$item['person_id'];
                $adminDb= new Admin();
                $whereArr=array();
                $whereArr[]=['id','=',$aid];
                $whereArr[]=['status','=',1];
                $field='id,account,realname,phone,openid,nickname,level';
                $adminInfo=$adminDb->getOne($whereArr,$field);
                if($adminInfo && !$adminInfo->isEmpty()) {
                    $adminInfo = $adminInfo->toArray();
                }
            }
            fdump_api(['adminInfo'=>$adminInfo],'thirdRobot/00sendNoticeToAdmin',1);
            if(!empty($adminInfo)){
                $smsService= new SmsService();
                $houseVillageDb = new HouseVillage();
                if (isset($msgInfo['village_id']) && $msgInfo['village_id'] > 0) {
                    $houseVillageInfo = $houseVillageDb->getOne($msgInfo['village_id'], 'village_id,village_name,village_address');
                    if ($houseVillageInfo && !$houseVillageInfo->isEmpty()) {
                        $msgInfo['village_name'] = $houseVillageInfo['village_name'];
                    }
                }
                $start_time=isset($msgInfo['start_time']) && $msgInfo['start_time']>0 ? $msgInfo['start_time']:time();
                if($adminInfo['phone'] && strlen($adminInfo['phone'])>10){
                    $sms_data = array('type' => 'robot_alarm_notice');
                    $sms_data['uid'] = 0;
                    $sms_data['village_id'] = 0;
                    $sms_data['mobile'] = $adminInfo['phone'];
                    $sms_data['sendto'] = 'admin_user';
                    $sms_data['mer_id'] = 0;
                    $sms_data['store_id'] = 0;
                    //模板：尊敬的{1}，您的机器人{2}在{3}有异常告警，请前往{4}处理。

                    $starttimestr=date('H:i',$start_time);
                    $village_name=isset($msgInfo['village_name'])&& !empty($msgInfo['village_name']) ? $msgInfo['village_name']:'';
                    $sms_data['content'] ='尊敬的'.$sms_data['realname'].'，您的机器人'.$msgInfo['robot_name'].'在'.$starttimestr.'有异常告警，请前往'.$village_name.'处理。' ;
                    $sms = $smsService->sendSms($sms_data);
                    fdump_api(['sms_data'=>$sms_data,'ret'=>$sms],'thirdRobot/00sendNoticeToAdmin',1);
                }
                if($adminInfo['openid'] && strlen($adminInfo['openid'])>15){
                    $templateNewsService = new TemplateNewsService();
                    $keyword3='';
                    if(isset($msgInfo['village_name'])&& !empty($msgInfo['village_name'])){
                        $keyword3=$msgInfo['village_name'];
                    }
                    $keyword4='设备编号是'.$msgInfo['robot_code'];
                    $remark='';
                    $datamsg = [
                        'tempKey' => 'OPENTM414750160',
                        'dataArr' => [
                            'href' => '',
                            'wecha_id' => $adminInfo['openid'],
                            'first' => '您的机器人'.$msgInfo['robot_name'].'设备有告警',
                            'keyword1' => $msgInfo['robot_name'].'机器人',
                            'keyword2' => $msgInfo['msg_type_code_title'] ? $msgInfo['msg_type_code_title']:$msgInfo['alarm_name'],
                            'keyword3' => $keyword3,
                            'keyword4' => $keyword4,
                            'keyword5' => date('Y-m-d H:i:s',$start_time),
                            'remark' => $remark
                        ]
                    ];
                    fdump_api(['datamsg'=>$datamsg],'thirdRobot/00sendNoticeToAdmin',1);
                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,0,0);
                }
            }
        }
    }

    public function getNoticeWorkerSetInfo($whereArr,$field='*'){
        $thirdRobotAlarmNoticeSet= new ThirdRobotAlarmNoticeSet();
        $robotAlarmNoticeSetObj=$thirdRobotAlarmNoticeSet->getOneData($whereArr,$field);
        $robotAlarmNoticeSet=array();
        if($robotAlarmNoticeSetObj && !$robotAlarmNoticeSetObj->isEmpty()){
            $robotAlarmNoticeSet= $robotAlarmNoticeSetObj->toArray();
        }
        return $robotAlarmNoticeSet;
    }
    public function  saveNoticeWorkerSet($village_id=0,$save_data=array()){
        $thirdRobotAlarmNoticeSet= new ThirdRobotAlarmNoticeSet();
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereArr[]=array('id_type','=',2);
        $whereArr[]=array('del_time','<',1);
        $robotAlarmNoticeSetObj=$thirdRobotAlarmNoticeSet->getOneData($whereArr,'*');
        if($robotAlarmNoticeSetObj && !$robotAlarmNoticeSetObj->isEmpty()){
            $idd=$robotAlarmNoticeSetObj['id'];
            $save_data['update_time']=time();
            $thirdRobotAlarmNoticeSet->saveOneData(array('id'=>$idd),$save_data);
        }else{
            $save_data['village_id']=$village_id;
            $save_data['levels']='';
            $save_data['robot_code']='';
            $save_data['add_time']=time();
            $idd=$thirdRobotAlarmNoticeSet->addOneData($save_data);
        }
        return array('id'=>$idd,'village_id'=>$village_id);
    }
    public function sendNoticeToHouseWorker($rtype=1,$village_id=0,$infoArr=array()){
        fdump_api(['village_id'=>$village_id,'rtype'=>$rtype,'infoArr'=>$infoArr],'thirdRobot/00sendNoticeToHouseWorker',1);
        $thirdRobotAlarmNoticeSet= new ThirdRobotAlarmNoticeSet();
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereArr[]=array('id_type','=',2);
        $whereArr[]=array('del_time','<',1);
        $robotAlarmNoticeSetObj=$thirdRobotAlarmNoticeSet->getOneData($whereArr,'*');
        if($robotAlarmNoticeSetObj && !$robotAlarmNoticeSetObj->isEmpty()){
            $noticeSet=$robotAlarmNoticeSetObj->toArray();
            fdump_api(['village_id'=>$village_id,'rtype'=>$rtype,'noticeSet'=>$noticeSet],'thirdRobot/00sendNoticeToHouseWorker',1);
            $extra_data=$noticeSet['extra_data'] ? json_decode($noticeSet['extra_data'],true):'';
            if(empty($extra_data)){
                return false;
            }
            $widArr=array();
            foreach ($extra_data as $evv){
                $widArr[]=$evv['wid'];
            }
            $widArr=array_unique($widArr);
            $houseWorkers=array();
            if($rtype==1 && in_array($noticeSet['ai_send_type'],array(1,3))){
                //巡检
                $whereArr=array();
                $houseWorkerDb= new HouseWorker();
                $whereArr[]=['wid','in',$widArr];
                $whereArr[]=['status','=',1];
                $field='wid,village_id,account,name as realname,phone,openid,nickname';
                $houseWorkerObj=$houseWorkerDb->getWorkLists($whereArr,$field,'wid desc',0,100);
                if($houseWorkerObj && !$houseWorkerObj->isEmpty()) {
                    $houseWorkers = $houseWorkerObj->toArray();
                }
                
            }else if($rtype==2 && in_array($noticeSet['ai_send_type'],array(2,3))){
                //ai 识别

                $whereArr=array();
                $houseWorkerDb= new HouseWorker();
                $whereArr[]=['wid','in',$widArr];
                $whereArr[]=['status','=',1];
                $field='wid,village_id,account,name as realname,phone,openid,nickname';
                $houseWorkerObj=$houseWorkerDb->getWorkLists($whereArr,$field,'wid desc',0,100);
                if($houseWorkerObj && !$houseWorkerObj->isEmpty()) {
                    $houseWorkers = $houseWorkerObj->toArray();
                }
            }
            /***
            项目名称{{thing1.DATA}}
            项目类别{{const9.DATA}}
            巡检时间{{time3.DATA}}
            异常类型{{const10.DATA}}
            预警位置{{thing7.DATA}}
             */
            fdump_api(['village_id'=>$village_id,'rtype'=>$rtype,'houseWorkers'=>$houseWorkers],'thirdRobot/00sendNoticeToHouseWorker',1);
            if(!empty($houseWorkers)){
                foreach ($houseWorkers as $hvv){
                    if($hvv['openid'] && strlen($hvv['openid'])>15){
                        $templateNewsService = new TemplateNewsService();
                        $datamsg = [
                            'tempKey' => '52465',
                            'dataArr' => [
                                'href' => '',
                                'wecha_id' => $hvv['openid'],
                                'thing1' => $infoArr['qrcode_name'],
                                'const9' => $infoArr['cate_name'],
                                'time3' => date('Y-m-d H:i:s'),
                                'const10' => $infoArr['title'],
                                'thing7' => $infoArr['point_name'],
                            ]
                        ];
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,0,0);
                    }
                }
            }
        }
    }
}