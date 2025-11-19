<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2023/7/25 9:20
 */

namespace app\community\model\service;

use app\community\model\db\HouseAppJpushInfo;
use app\community\model\db\HouseAppJpushLog;
use app\community\model\db\HouseAppJpushMass;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseAppJpushUserLog;
use app\community\model\db\Config;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseAdmin;
use think\app\Url;
use think\facade\Db;
use message\Jpush;
use app\traits\CommonSmallDoTraits;
class HouseAppJpushInfoService
{
    use CommonSmallDoTraits;
    public function getOneAppJpushInfo($whereArr = array())
    {
        $oneJpushInfo = array();
        $houseAppJpushInfoDb = new HouseAppJpushInfo();
        if (!empty($whereArr)) {
            $houseAppJpushInfoObj = $houseAppJpushInfoDb->getOneData($whereArr);
            if ($houseAppJpushInfoObj && !$houseAppJpushInfoObj->isEmpty()) {
                $oneJpushInfo = $houseAppJpushInfoObj->toArray();
            }
        }
        return $oneJpushInfo;
    }
    public function insertToRedisQueue($idd=0,$opt_type='',$village_id=0){
        $queueData=array('idd'=>$idd,'opt_type'=>$opt_type,'village_id'=>$village_id);
        fdump_api($queueData,'00insertToRedisQueue',1);
        if($idd>0 && !empty($opt_type)){
             $redisKey=$this->laterCommonSmallDoToJob($queueData);
            fdump_api(['redisKey'=>$redisKey],'00insertToRedisQueue',1);
            return $redisKey;
        }
        return true;
    }
    public function handleVillageUserJpushInfoSend($queueData=array()){
        if($queueData && isset($queueData['idd']) && $queueData['idd']){
            $houseAppJpushMassDb= new HouseAppJpushMass();
            $weixin_push_jpush_appkey='';
            $weixin_push_jpush_secret='';
            $configDb=new Config();
            $whereCArr=array('name'=>'weixin_push_jpush_appkey');
            $configTmp= $configDb->getOne($whereCArr,'name,value');
            if($configTmp && !$configTmp->isEmpty()){
                $weixin_push_jpush_appkey=$configTmp['value'];
            }
            $whereCArr=array('name'=>'weixin_push_jpush_secret');
            $configTmp= $configDb->getOne($whereCArr,'name,value');
            if($configTmp && !$configTmp->isEmpty()){
                $weixin_push_jpush_secret=$configTmp['value'];
            }
            $whereArr=array('id'=>$queueData['idd']);
            if(isset($queueData['village_id']) && $queueData['village_id']){
                $whereArr=array('village_id'=>$queueData['village_id']);
            }
            if(empty($weixin_push_jpush_appkey) || empty($weixin_push_jpush_secret)){
                return  array('err_code'=>1,'msg'=>'未配置凭他极光推送设置！');
            }
            $opt_type='house_village_user_bind';
            $jpushMass=$houseAppJpushMassDb->getOneData($whereArr);
            if($jpushMass && !$jpushMass->isEmpty()){
                $jpush_works_order_tips=$jpushMass['send_content'] ? trim($jpushMass['send_content']):'';
                if(empty($jpush_works_order_tips)){
                    return  array('err_code'=>1,'msg'=>'发送消息体内容为空！');
                }
                $url=$jpushMass['url'];
                $village_id=$jpushMass['village_id'];
                $whereUserArr=array(['village_id','=',$queueData['village_id']]);
                if($jpushMass['single_id']>0){
                    $whereUserArr[]=array('single_id','=',$jpushMass['single_id']);
                }
                if($jpushMass['floor_id']>0){
                    $whereUserArr[]=array('floor_id','=',$jpushMass['floor_id']);
                }
                $whereUserArr[]=array('status','=',1);
                $whereUserArr[]=array('type','<',4);
                
                $houseVillageUserBindDb= new HouseVillageUserBind();
                $total=$houseVillageUserBindDb->getVillageUserNum($whereUserArr);
                $total=$total>0 ? $total:0;
                $page_size=500;
                $forNum=1;
                if($total>$page_size){
                    $forNum=$total/$page_size;
                    $forNum=ceil($forNum);
                }
                $houseAppJpushMassDb->saveOneData(array('id'=>$jpushMass['id']),array('send_status'=>1,'send_time'=>time()));
                $houseAppJpushLog = new HouseAppJpushLog();
                $houseAppJpushUserLog = new HouseAppJpushUserLog();
                for($ii=1;$ii<=$forNum;$ii++){
                    $nowtime=time();
                    //fdump_api(['forNum'=>$forNum,'whereUserArr'=>$whereUserArr],'00CommonSmallDoJob',1);
                    $bindUserObj=$houseVillageUserBindDb->getBindUserList($whereUserArr,'pigcms_id,uid,vacancy_id','pigcms_id DESC',$ii,$page_size);
                    if($bindUserObj && !$bindUserObj->isEmpty()){
                        $bindUser = $bindUserObj->toArray();
                        //fdump_api(['bindUser'=>$bindUser],'00CommonSmallDoJob',1);
                        $uids=array();
                        $pigcms_ids=array();
                        $pigcms_id_arr=array();
                        foreach ($bindUser as $item){
                            //$uids[]=$item['uid'];
                            $pigcms_ids[]=$item['pigcms_id'];
                            $item['registration_id']='';
                            $pigcms_id_arr[$item['pigcms_id']]=$item;
                        }
                        $whereArr=array(['village_id','=',$queueData['village_id']],['user_table_type','=','house_village_user_bind']);
                        $whereArr[]=array('bind_id','in',$pigcms_ids);
                        $jpushList = $this->getAppJpushInfoList($whereArr, 'id,jpush_registration_id,platform,uid,bind_id,village_id', 1, 500);
                        $status = 2;
                        if (!empty($jpushList)) {
                            $jpush_registration_ids=array();
                            foreach ($jpushList as $jvv) {
                                $jpush_registration_ids[] = $jvv['jpush_registration_id'];
                                $platform = $jvv['platform'] ? strtolower($jvv['platform']) : 'all';
                                if (!in_array($platform, array("android", "ios", "quickapp", "hmos", "all"))) {
                                    $platform = 'all';
                                }
                                $pigcms_id_arr[$jvv['bind_id']]['registration_id']=$jvv['jpush_registration_id'];
                                $platformArr[] = $platform;
                                $info_ids[] = $jvv['id'];
                            }
                            $platformArr=array_unique($platformArr);
                            $jpush_registration_ids = array_unique($jpush_registration_ids);
                            $logArr = array('village_id' => $village_id, 'jpush_registration_ids' => json_encode($jpush_registration_ids, JSON_UNESCAPED_UNICODE));
                            $logArr['platform'] = json_encode($platformArr, JSON_UNESCAPED_UNICODE);
                            $logArr['info_ids'] = json_encode($info_ids, JSON_UNESCAPED_UNICODE);
                            $extra_data=array('pigcms_ids'=>$pigcms_ids);
                            $logArr['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
                            $logArr['send_type']='jpush_house_village_user_bind';
                            
                            $platform_arr = $platformArr;
                            if (empty($platformArr) || in_array('all', $platformArr)) {
                                $platform_arr = 'all';
                            }
                            $audience = array('registration_id' => $jpush_registration_ids);
                            $notification = array('alert' => $jpush_works_order_tips);
                            $notification['android'] = array("alert" => $jpush_works_order_tips, 'title' => $jpush_works_order_tips, 'builder_id' => 1, 'badge_add_num' => 1, "intent"=> array("url"=>'intent:#Intent;action=cn.jiguang.webviewaction;component=com.yuanjino2o.plat/com.yuanjino2o.plat.activity.WebViewActivity;S.url='.$url.';S.start_type=jpush;end') ,'extras' => array('x_type' => $opt_type,'x_url'=>$url));
                            $notification['ios'] = array("alert" => $jpush_works_order_tips, 'sound' => 'default', 'badge' => "+1", 'extras' => array('x_type' => $opt_type,'x_url'=>$url));
                            $notification['hmos'] = array("alert" => $jpush_works_order_tips, 'title' => $jpush_works_order_tips, "badge_add_num" => 1, 'extras' => array('x_type' => $opt_type,'x_url'=>$url));
                            $message = array("msg_content" => $jpush_works_order_tips, "content_type" => "text", "title" => $jpush_works_order_tips, "extras" => array('x_type' => $opt_type,'x_url'=>$url));
                            $jcontent = array("platform" => $platform_arr, 'audience' => $audience, 'notification' => $notification, 'message' => $message);  // 只支持 android, ios,quickapp, hmos  all
                            $logArr['jcontent'] = json_encode($jcontent, JSON_UNESCAPED_UNICODE);
                            $logArr['add_time'] = $nowtime;
                            $idd = $houseAppJpushLog->addOneData($logArr);
                            $jpushObj = new Jpush($weixin_push_jpush_appkey, $weixin_push_jpush_secret);
                            $ret = $jpushObj->send($platform_arr, $audience, $notification, $message, true);
                            
                            if ($ret && isset($ret['status']) && $ret['status']) {
                                $status = 1;
                            }
                            if ($idd > 0) {
                                $saveArr = array('send_time' => time(), 'ret_json' => json_encode($ret, JSON_UNESCAPED_UNICODE), 'status' => $status);
                                $houseAppJpushLog->saveOneData(array('id' => $idd), $saveArr);
                            }
                            
                        }
                        //fdump_api(['pigcms_id_arr'=>$pigcms_id_arr],'00CommonSmallDoJob',1);
                        $nowtime=time();
                        foreach ($pigcms_id_arr as $item){
                            $addArr=array('village_id'=>$village_id,'mass_id'=>$jpushMass['id'],'registration_id'=>$item['registration_id']);
                            $addArr['send_time']=$nowtime;
                            $addArr['bind_id']=$item['pigcms_id'];
                            $addArr['uid']=$item['uid'];
                            $addArr['room_id']=$item['vacancy_id'];
                            $send_status=$status;
                            if(empty($item['registration_id'])){
                                $send_status=2;
                            }
                            $addArr['send_status']=$send_status;
                            $houseAppJpushUserLog->addOneData($addArr);
                        }
                    }
                }
                $houseAppJpushMassDb->saveOneData(array('id'=>$jpushMass['id']),array('send_status'=>2,'send_time'=>time()));
                return  array('err_code'=>1,'msg'=>'发送成功！');
            }
        }
    }
    public function addOneAppJpushInfo($addArr = array())
    {
        $houseAppJpushInfoDb = new HouseAppJpushInfo();
        $ret = $houseAppJpushInfoDb->addOneData($addArr);
        return $ret;
    }

    public function saveAndUpdateAppJpushInfo($whereArr = array(), $saveArr = array())
    {
        $houseAppJpushInfoDb = new HouseAppJpushInfo();
        $houseAppJpushInfoObj = $houseAppJpushInfoDb->getOneData($whereArr);
        $ret = '';
        $idd = 0;
        if ($houseAppJpushInfoObj && !$houseAppJpushInfoObj->isEmpty()) {
            $saveArr['update_time'] = time();
            $ret = $houseAppJpushInfoDb->saveOneData($whereArr, $saveArr);
            $idd = $houseAppJpushInfoObj['id'];
        } else {
            $saveArr['add_time'] = time();
            $ret = $houseAppJpushInfoDb->addOneData($saveArr);
            $idd = $ret;
        }
        return array('ret' => $ret, 'idd' => $idd);
    }

    public function saveAppJpushInfo($whereArr = array(), $saveArr = array())
    {
        $houseAppJpushInfoDb = new HouseAppJpushInfo();
        $ret = $houseAppJpushInfoDb->saveOneData($whereArr, $saveArr);
        return $ret;
    }

    public function delAppJpushInfo($whereArr = array(), $saveArr = array())
    {
        $houseAppJpushInfoDb = new HouseAppJpushInfo();
        $ret = $houseAppJpushInfoDb->updateOneData($whereArr, $saveArr);
        return $ret;
    }

    public function getAppJpushInfoList($where, $field = '*', $page = 0, $limit = 15, $order = 'id DESC')
    {
        $houseAppJpushInfoDb = new HouseAppJpushInfo();
        $listDataObj = $houseAppJpushInfoDb->getListData($where, $field, $order, $page, $limit);
        if ($listDataObj && !$listDataObj->isEmpty()) {
            $listData = $listDataObj->toArray();
        } else {
            $listData = array();
        }
        return $listData;
    }

    public function handleJpushInfoSend($otherData = array(), $opt_type = 'repair_works_order')
    {
        $community_jpush_appkey = cfg('community_jpush_appkey');
        $community_jpush_appkey = trim($community_jpush_appkey);
        $community_jpush_secret = cfg('community_jpush_secret');
        $community_jpush_secret = trim($community_jpush_secret);
        if (empty($otherData) || empty($community_jpush_appkey) || empty($community_jpush_secret)) {
            return true;
        }
        $jpush_works_order_tips = cfg('jpush_works_order_tips');
        $jpush_works_order_tips = $jpush_works_order_tips ? $jpush_works_order_tips : '您有新工单需要处理，请处理';
        if($opt_type=='checkauth_apply_tips'){
            $jpush_works_order_tips = cfg('checkauth_apply_tips');
            $jpush_works_order_tips = $jpush_works_order_tips ? $jpush_works_order_tips : '有您的审批流程待审核，请处理';
        }else if($opt_type=='order_worker_submit'){
            $jpush_works_order_tips = cfg('jpush_submitworks_order_tips');
            $jpush_works_order_tips = $jpush_works_order_tips ? $jpush_works_order_tips : '您有新工单需要指派，请处理';
        }else if($opt_type=='house_order_paid_tpis'){
            $jpush_works_order_tips = cfg('house_order_paid_tpis');
            $jpush_works_order_tips = $jpush_works_order_tips ? $jpush_works_order_tips : '小区有新的订单已支付，请查看';
        }
        $houseAppJpushLog = new HouseAppJpushLog();
        $houseWorker=new HouseWorker();
        $nowtime = time();
        if ( in_array($opt_type,['repair_works_order','checkauth_apply_tips'])  && isset($otherData['wid']) && $otherData['wid'] > 0) {
            $whereArr = array(['wid', '=', $otherData['wid']], ['del_time', '<', 100], ['jpush_registration_id', '<>', '']);
            $village_id = 0;
            if (isset($otherData['village_id']) && $otherData['village_id'] > 0) {
                $whereArr[] = array('village_id', '=', $otherData['village_id']);
                $village_id = $otherData['village_id'];
            }
            $jpushList = $this->getAppJpushInfoList($whereArr, 'id,wid,jpush_registration_id,platform', 1, 1000);
            if (!empty($jpushList)) {
                $jpush_registration_ids = array();
                $platformArr = array();
                $wids = array();
                $info_ids = array();
                foreach ($jpushList as $jvv) {
                    $jpush_registration_ids[] = $jvv['jpush_registration_id'];
                    $platform = $jvv['platform'] ? strtolower($jvv['platform']) : 'all';
                    if (!in_array($platform, array("android", "ios", "quickapp", "hmos", "all"))) {
                        $platform = 'all';
                    }
                    $platformArr[] = $platform;
                    $wids[] = $jvv['wid'];
                    $info_ids[] = $jvv['id'];
                }
                $platformArr=array_unique($platformArr);
                $jpush_registration_ids = array_unique($jpush_registration_ids);
                $logArr = array('village_id' => $village_id, 'jpush_registration_ids' => json_encode($jpush_registration_ids, JSON_UNESCAPED_UNICODE));
                $logArr['platform'] = json_encode($platformArr, JSON_UNESCAPED_UNICODE);
                $logArr['wids'] = json_encode($wids, JSON_UNESCAPED_UNICODE);
                $logArr['info_ids'] = json_encode($info_ids, JSON_UNESCAPED_UNICODE);
                $logArr['send_type']=$opt_type;
                $platform_arr = $platformArr;
                if (empty($platformArr) || in_array('all', $platformArr)) {
                    $platform_arr = 'all';
                }
                $audience = array('registration_id' => $jpush_registration_ids);
                $notification = array('alert' => $jpush_works_order_tips);
                $notification['android'] = array("alert" => $jpush_works_order_tips, 'title' => $jpush_works_order_tips, 'builder_id' => 1, 'badge_add_num' => 1, "intent"=> array("url"=>"intent:#Intent;action=android.intent.action.MAIN;end") ,'extras' => array('x_type' => $opt_type));
                $notification['ios'] = array("alert" => $jpush_works_order_tips, 'sound' => 'default', 'badge' => "+1", 'extras' => array('x_type' => $opt_type));
                $notification['hmos'] = array("alert" => $jpush_works_order_tips, 'title' => $jpush_works_order_tips, "badge_add_num" => 1, 'extras' => array('x_type' => $opt_type));
                $message = array("msg_content" => $jpush_works_order_tips, "content_type" => "text", "title" => $jpush_works_order_tips, "extras" => array('x_type' => $opt_type));
                $jcontent = array("platform" => $platform_arr, 'audience' => $audience, 'notification' => $notification, 'message' => $message);  // 只支持 android, ios,quickapp, hmos  all
                $logArr['jcontent'] = json_encode($jcontent, JSON_UNESCAPED_UNICODE);
                $logArr['add_time'] = $nowtime;
                $idd = $houseAppJpushLog->addOneData($logArr);
                $jpushObj = new Jpush($community_jpush_appkey, $community_jpush_secret);
                $ret = $jpushObj->send($platform_arr, $audience, $notification, $message, true);
                if ($idd > 0) {
                    $status = 2;
                    if ($ret && isset($ret['status']) && $ret['status']) {
                        $status = 1;
                    }
                    $saveArr = array('send_time' => time(), 'ret_json' => json_encode($ret, JSON_UNESCAPED_UNICODE), 'status' => $status);
                    $houseAppJpushLog->saveOneData(array('id' => $idd), $saveArr);
                }
                return $idd;
            }
        }elseif(in_array($opt_type,['order_worker_submit','house_order_paid_tpis']) && isset($otherData['village_id']) && $otherData['village_id'] > 0){
            $whereArr = array(['village_id','=',$otherData['village_id']],['wid', '>', 0], ['del_time', '<', 100], ['jpush_registration_id', '<>', '']);
            $jpushList = $this->getAppJpushInfoList($whereArr, 'id,wid,jpush_registration_id,user_table_type,platform', 1, 1000);
            if (!empty($jpushList)) {
                $jpush_registration_ids = array();
                $platformArr = array();
                $wids = array();
                $info_ids = array();
                $houseAdminDb=new HouseAdmin();
                foreach ($jpushList as $jvv) {
                    if(in_array($opt_type,['order_worker_submit','house_order_paid_tpis']) && $jvv['user_table_type']=='house_worker'){
                        $whereTemArr=array(['wid','=',$jvv['wid']],['status','=',1],['village_id','=',$otherData['village_id']]);
                        $houseAdminObj= $houseAdminDb->getOne($whereTemArr,'id,wid,status','id desc');
                        if(empty($houseAdminObj) || $houseAdminObj->isEmpty()){
                            continue;
                        }
                    }
                    $jpush_registration_ids[] = $jvv['jpush_registration_id'];
                    $platform = $jvv['platform'] ? strtolower($jvv['platform']) : 'all';
                    if (!in_array($platform, array("android", "ios", "quickapp", "hmos", "all"))) {
                        $platform = 'all';
                    }
                    $platformArr[] = $platform;
                    $wids[] = $jvv['wid'];
                    $info_ids[] = $jvv['id'];
                }
                $platformArr=array_unique($platformArr);
                $jpush_registration_ids = array_unique($jpush_registration_ids);
                $logArr = array('village_id' => $otherData['village_id'], 'jpush_registration_ids' => json_encode($jpush_registration_ids, JSON_UNESCAPED_UNICODE));
                $logArr['platform'] = json_encode($platformArr, JSON_UNESCAPED_UNICODE);
                $logArr['wids'] = json_encode($wids, JSON_UNESCAPED_UNICODE);
                $logArr['info_ids'] = json_encode($info_ids, JSON_UNESCAPED_UNICODE);
                $logArr['send_type']=$opt_type;
                $platform_arr = $platformArr;
                if (empty($platformArr) || in_array('all', $platformArr)) {
                    $platform_arr = 'all';
                }
                $audience = array('registration_id' => $jpush_registration_ids);
                $notification = array('alert' => $jpush_works_order_tips);
                $notification['android'] = array("alert" => $jpush_works_order_tips, 'title' => $jpush_works_order_tips, 'builder_id' => 1, 'badge_add_num' => 1, "intent"=> array("url"=>"intent:#Intent;action=android.intent.action.MAIN;end") ,'extras' => array('x_type' => $opt_type));
                $notification['ios'] = array("alert" => $jpush_works_order_tips, 'sound' => 'default', 'badge' => "+1", 'extras' => array('x_type' => $opt_type));
                $notification['hmos'] = array("alert" => $jpush_works_order_tips, 'title' => $jpush_works_order_tips, "badge_add_num" => 1, 'extras' => array('x_type' => $opt_type));
                $message = array("msg_content" => $jpush_works_order_tips, "content_type" => "text", "title" => $jpush_works_order_tips, "extras" => array('x_type' => $opt_type));
                $jcontent = array("platform" => $platform_arr, 'audience' => $audience, 'notification' => $notification, 'message' => $message);  // 只支持 android, ios,quickapp, hmos  all
                $logArr['jcontent'] = json_encode($jcontent, JSON_UNESCAPED_UNICODE);
                $logArr['add_time'] = $nowtime;
                $idd = $houseAppJpushLog->addOneData($logArr);
                $jpushObj = new Jpush($community_jpush_appkey, $community_jpush_secret);
                $ret = $jpushObj->send($platform_arr, $audience, $notification, $message, true);
                if ($idd > 0) {
                    $status = 2;
                    if ($ret && isset($ret['status']) && $ret['status']) {
                        $status = 1;
                    }
                    $saveArr = array('send_time' => time(), 'ret_json' => json_encode($ret, JSON_UNESCAPED_UNICODE), 'status' => $status);
                    $houseAppJpushLog->saveOneData(array('id' => $idd), $saveArr);
                }
                return $idd;
            }
        }
        return true;
    }
}
