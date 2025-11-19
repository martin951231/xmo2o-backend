<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/5
 * Time: 14:38
 *======================================================
 */

namespace app\community\model\service;


use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\User;
use app\community\model\db\WeixinBind;
use app\community\model\db\WeixinBindUser;
use app\community\model\db\workweixin\HouseContactUserBind;
use app\community\model\db\workweixin\WorkWeixinUser;
use app\community\model\service\workweixin\WorkWeiXinNewService;
use app\community\model\service\workweixin\WorkWeiXinRequestService;
use app\consts\WorkWeiXinConst;

class HouseContactWayUserService
{
    /**
     * 获取企业微信客户详情
     * @param $userId
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserBindInfo($userId,$bind_id,$field = '*'){
        // 查询用户企业微信信息
        $contactWayUser='';
        if($userId){
            $where_user = [];
            $where_user[] = ['ExternalUserID', '=', $userId];
            $where_user[] = ['status', '=', 1];
            $houseContactWayUserService = new HouseContactWayUser();
            $dataObj = $houseContactWayUserService->getOne($where_user,$field);
            if(!empty($dataObj)){
                $contactWayUser = $dataObj->toArray();
            }
        }
        
        // 获取其他相关信息
        $where_user_bind = [];
        $where_user_bind[] = ['pigcms_id', '=', $bind_id];
        $house_village_user_bind = new HouseVillageUserBind();
        $userInfo = $house_village_user_bind->getUserBindInfo($where_user_bind,'u.nickname,u.avatar,hvb.pigcms_id,hvb.name,hvb.phone,hvb.address,hvb.village_id,hvb.uid,hvb.single_id,hvb.layer_id,hvb.floor_id,hvb.vacancy_id');
        if(!empty($userInfo)){
            $userInfo = $userInfo->toArray();
        }
        // 地址
        $houseVillageService = new HouseVillageService();
        $address = $houseVillageService->getSingleFloorRoom($userInfo['single_id'],$userInfo['floor_id'],$userInfo['layer_id'],$userInfo['vacancy_id'],$userInfo['village_id']);
        $userInfo['address'] = !empty($address) ? $address : $userInfo['address'];

        // 小区名称
        $db_house_village_service = new HouseVillageService();
        $village = $db_house_village_service->getHouseVillage($userInfo['village_id'],'village_name,property_id');
        $userInfo['village_name'] = '';
        $property_id=0;
        if(!empty($village)){
            $property_id=$village['property_id'];
            $userInfo['village_name'] = $village['village_name'];
        }
        $enterprise_wx_userInfo=array();
        if($property_id>0 && $userId){
            $workWeiXinNewService = new WorkWeiXinNewService();
            $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
            $enterprise_wx_userInfo = (new WorkWeiXinRequestService())->cgiBinExternalContactGet($userId, $access_token);
        }
        $userInfo['gender']='';
        if(!empty($enterprise_wx_userInfo) && isset($enterprise_wx_userInfo['external_contact']) && $enterprise_wx_userInfo['external_contact']){
            if(isset($enterprise_wx_userInfo['external_contact']['name']) && $enterprise_wx_userInfo['external_contact']['name']){
                $userInfo['name']=$enterprise_wx_userInfo['external_contact']['name'];
            }
            if(isset($enterprise_wx_userInfo['external_contact']['avatar']) && $enterprise_wx_userInfo['external_contact']['avatar']){
                $userInfo['avatar']=$enterprise_wx_userInfo['external_contact']['avatar'];
            }
            if(isset($enterprise_wx_userInfo['external_contact']['gender']) && $enterprise_wx_userInfo['external_contact']['gender']){
                $userInfo['gender']=$enterprise_wx_userInfo['external_contact']['gender'];
            }
        }
        $userInfo['customer_id'] = $contactWayUser ? $contactWayUser['customer_id']:'';
        $userInfo['enterprise_wx_userInfo'] = $enterprise_wx_userInfo;
        return $userInfo;
    }

    /**
     * 获取企业微信通过联系我添加人员相关信息
     * @param $userId
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function gethouseContactWayUser($userId, $field = 'customer_id,uid,UserID,ExternalUserID,property_id', $otherParam = [])
    {
        $houseContactWayUserService = new HouseContactWayUser();
        
        $from_type = isset($otherParam['from_type']) && $otherParam['from_type'] ? $otherParam['from_type'] : 2;
        $from_id = isset($otherParam['from_id']) && $otherParam['from_id'] ? $otherParam['from_id'] : 0;
        $phone = isset($otherParam['phone']) && $otherParam['phone'] ? $otherParam['phone'] : '';
        if($from_type == 1){
            $sHouse_village = new HouseVillageService();
            $village_info = $sHouse_village->getHouseVillageInfo(['village_id'=>$from_id],'property_id');
            $property_id = $village_info['property_id'];
        } else {
            $from_type = 2;
            $property_id =$from_id;
        }
        $nowTime = time();
        $workWeiXinNewService = new WorkWeiXinNewService();
        $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
        $enterprise_wx_userInfo = (new WorkWeiXinRequestService())->cgiBinExternalContactGet($userId, $access_token);
        $externalUserIDArr = [];
        $external_contact = isset($enterprise_wx_userInfo['external_contact']) && $enterprise_wx_userInfo['external_contact'] ? $enterprise_wx_userInfo['external_contact'] : [];
        fdump_api([$external_contact, $otherParam], '$external_contact');
        $external_userid = isset($external_contact['external_userid']) && $external_contact['external_userid'] ? $external_contact['external_userid'] : '';

        $type = isset($external_contact['type']) ? intval($external_contact['type']) : '';
        $name = isset($external_contact['name']) ? trim($external_contact['name']) : '';

        $createtime = '';
        $state = '';
        if (isset($enterprise_wx_userInfo['follow_user']) && $enterprise_wx_userInfo['follow_user']) {
            foreach ($enterprise_wx_userInfo['follow_user'] as $follow_user) {
                $userid = isset($follow_user['userid']) && trim($follow_user['userid']) ? trim($follow_user['userid']) : '';
                $oper_userid = isset($follow_user['oper_userid']) && trim($follow_user['oper_userid']) ? trim($follow_user['oper_userid']) : '';
                if ($userid && $oper_userid) {
                    $where_user = [];
                    $where_user[] = ['ExternalUserID', '=', $userid];
                    $where_user[] = ['property_id', '=', $userid];
                    $where_user[] = ['third_suite_userid', '=', ''];
                    $saveData = [
                        'third_suite_userid' => $oper_userid,
                        'third_suite_userid_time' => $nowTime,
                    ];
                    $houseContactWayUserService->updateWxInfo($where_user, $saveData);
                }
                $userid && !isset($externalUserIDArr[$userid]) && $externalUserIDArr[$userid] = strval($userid);
                ! $createtime && $createtime = isset($follow_user['createtime']) && $follow_user['createtime'] ? $follow_user['createtime'] : '';
                ! $state && $state = isset($follow_user['state']) && $follow_user['state'] ? $follow_user['state'] : '';
            }
            $externalUserIDArr = array_values($externalUserIDArr);
        }
        
        $dbHouseContactUserBind = new HouseContactUserBind();
        $third_suite_id = cfg('enterprise_wx_provider_suiteid');
        $whereContactUserBind = [];
        $whereContactUserBind[] = ['userid', '=', $userId];
        $whereContactUserBind[] = ['third_suite_id', '=', $third_suite_id];
        $whereContactUserBind[] = ['property_id', '=', $property_id];
        $infoHouseContactUserBind = $dbHouseContactUserBind->getOne($whereContactUserBind);
        $data=array();
        // 获取物业信息
        $houseContactWayUser = [];
        $whereWayUser = [];
        if ($external_userid) {
            $whereWayUser[] = ['third_suite_userid', '=', $external_userid];
            $whereWayUser[] = ['status', '=', 1];
            $houseContactWayUser = $houseContactWayUserService->getOne($whereWayUser,true, 'phone desc, uid desc, customer_id desc');
            if ($houseContactWayUser && !is_array($houseContactWayUser)) {
                $data = $houseContactWayUser->toArray();
            }
        }
        $where_user = [];
        if ($type && $name && $createtime && $state) {
            $where_user[] = ['type', '=', $type];
            $where_user[] = ['name', '=', $name];
            $where_user[] = ['CreateTime', '>=', $createtime];
            $where_user[] = ['CreateTime', '<=', $createtime + 3];
            $where_user[] = ['state', '=', $state];
        }
        if (!empty($where_user) && empty($houseContactWayUser)) {
            $houseContactWayUser = $houseContactWayUserService->getOne($where_user,true, 'phone desc, uid desc, customer_id desc');
            if ($houseContactWayUser && !is_array($houseContactWayUser)) {
                $data = $houseContactWayUser->toArray();
            }
        }
        fdump_api([$type,$name,$createtime,$state, $where_user, $whereWayUser, $houseContactWayUser], '$houseContactWayUser');
        $param = [];
        $param['last_time'] = $nowTime;
        // 企微名
        if($enterprise_wx_userInfo && isset($enterprise_wx_userInfo['external_contact']['name']) && $enterprise_wx_userInfo['external_contact']['name']){
            $param['name'] = $enterprise_wx_userInfo['external_contact']['name'];
        } elseif ($name) {
            $param['name'] = $name;
        }
        // 企微头像
        if($enterprise_wx_userInfo && isset($enterprise_wx_userInfo['external_contact']['avatar']) && $enterprise_wx_userInfo['external_contact']['avatar']){
            $param['avatar'] = $enterprise_wx_userInfo['external_contact']['avatar'];
        }
        // 性别
        if($enterprise_wx_userInfo && isset($enterprise_wx_userInfo['external_contact']['gender']) && $enterprise_wx_userInfo['external_contact']['gender']){
            $param['gender'] = $enterprise_wx_userInfo['external_contact']['gender'];
        }
        $wayPhone = isset($data['phone']) && trim($data['phone']) ? trim($data['phone']) : '';
        $dataUid = isset($data['uid']) && intval($data['uid']) > 0 ? intval($data['uid']) : 0;
        $paramName = isset($param['name']) && trim($param['name']) ? trim($param['name']) : '';
        if (!$dataUid && !$phone && $paramName) {
            $dbUser = new User();
            $whereUser = [
                'nickname' => $paramName
            ];
            $userInfo = $dbUser->getOne($whereUser, 'uid, openid, union_id');
            if (! $userInfo || !isset($userInfo['uid']) || !$userInfo['uid']) {
                $dbWeixinBindUser = new WeixinBindUser();
                $whereUser = [
                    'nickName' => $paramName,
                    'property_id' => $property_id,
                ];
                $userInfo = $dbWeixinBindUser->getFind($whereUser, 'uid, openid, union_id');
            }
            if (isset($userInfo['openid']) && isset($userInfo['union_id']) && $userInfo['openid'] && $userInfo['union_id']) {
                $result = (new WorkWeiXinNewService())->unionIdToExternalUserID3rd($userInfo['union_id'], $userInfo['openid'], $property_id);
                fdump_api($result, '$result');
                if (isset($result['external_userid_info']) && $result['external_userid_info']) {
                    foreach ($result['external_userid_info'] as $item) {
                        if (isset($item['external_userid']) && $item['external_userid'] && in_array($item['external_userid'], $externalUserIDArr)) {
                            $param['uid'] = $userInfo['uid'];
                            $data['uid']  = $userInfo['uid'];
                        }
                    }
                }
            }
        }
        $dataUid = isset($data['uid']) && intval($data['uid']) > 0 ? intval($data['uid']) : 0;
        if (!$dataUid && !$phone && $wayPhone) {
            $dbUser = new User();
            $whereUser = [
                'phone' => $wayPhone
            ];
            $userInfo = $dbUser->getOne($whereUser, 'uid, openid, union_id');
            if (isset($userInfo['uid']) && $userInfo['uid']) {
                $param['uid'] = $userInfo['uid'];
                $data['uid']  = $userInfo['uid'];
            }
        }
        $dataUid = isset($data['uid']) && intval($data['uid']) > 0 ? intval($data['uid']) : 0;
        if (!$dataUid && !$phone && isset($infoHouseContactUserBind['phone']) && $infoHouseContactUserBind['phone']) {
            $phone = $infoHouseContactUserBind['phone'];
        }
        $bindPhone = '';
        if ($phone) {
            $dbUser = new User();
            $whereUser = [
                'phone' => $phone
            ];
            $userInfo = $dbUser->getOne($whereUser, 'uid, openid, union_id');
            if (isset($userInfo['uid']) && $userInfo['uid']) {
                $param['uid'] = $userInfo['uid'];
                $data['uid']  = $userInfo['uid'];
                !$wayPhone && $param['phone'] = $phone;
                $data['phone']  = $phone;
                $bindPhone = $phone;
            }
        }
        $data['property_id'] = $property_id;
        $dataBindId = isset($data['bind_id']) && $data['bind_id'] ? $data['bind_id'] : 0;
        $dataUid = isset($data['uid']) && intval($data['uid']) > 0 ? intval($data['uid']) : 0;
        if ((!$dataBindId || $phone) && ($dataUid || $phone || $wayPhone)) {
            $whereVillageColumn = [];
            $whereVillageColumn[] = ['property_id', '=', $property_id];
            $whereVillageColumn[] = ['status', '=', 1];
            $village_id_arr = (new HouseVillageService())->getVillageColumn($whereVillageColumn, 'village_id');
            $dbHouseVillageUserBind = new HouseVillageUserBind();
            $whereUserBind = [];
            $whereUserBind[] = ['village_id', 'in', $village_id_arr];
            $whereUserBind[] = ['type', 'in', [0, 1, 2, 3]];
            $whereUserBind[] = ['status', '=', 1];
            if ($phone) {
                $whereUserBind[] = ['phone', '=', $phone];
            } elseif ($dataUid) {
                $whereUserBind[] = ['uid', '=', $dataUid];
            } elseif ($wayPhone) {
                $whereUserBind[] = ['phone', '=', $wayPhone];
            }
            fdump_api($whereUserBind, '$whereUserBind');
            $userList = $dbHouseVillageUserBind->getList($whereUserBind, true,'pigcms_id desc');
            fdump_api($userList, '$userList');
            if ($userList && !is_array($userList)) {
                $userList = $userList->toArray();
            }
            if (empty($userList)) {
                throw new \think\Exception('所填手机号在物业无住户身份');
            }
            $bindInfo = [];
            $firstInfo = [];
            foreach ($userList as $item) {
                empty($firstInfo) && $firstInfo = $item;
                if (isset($item['type']) && in_array($item['type'], [0, 3])) {
                    $bindInfo = $item;
                    break;
                }
            }
            empty($bindInfo) && $bindInfo = $firstInfo;
            if (isset($bindInfo['uid']) && $bindInfo['uid']) {
                $param['uid'] = $bindInfo['uid'];
                $data['uid'] = $bindInfo['uid'];
                if ($phone) {
                    $bindPhone = $phone;
                }
            }
            if (isset($bindInfo['pigcms_id']) && $bindInfo['pigcms_id']) {
                $param['bind_id'] = $bindInfo['pigcms_id'];
                $data['bind_id'] = $bindInfo['pigcms_id'];
            }
            if (! $data['room_id'] && isset($bindInfo['vacancy_id']) && $bindInfo['vacancy_id']) {
                $param['room_id'] = $bindInfo['vacancy_id'];
                $data['room_id'] = $bindInfo['vacancy_id'];
            }
            if (! $data['floor_id'] && isset($bindInfo['floor_id']) && $bindInfo['floor_id']) {
                $param['floor_id'] = $bindInfo['floor_id'];
                $data['floor_id'] = $bindInfo['floor_id'];
            }
            if (! $data['single_id'] && isset($bindInfo['single_id']) && $bindInfo['single_id']) {
                $param['single_id'] = $bindInfo['single_id'];
                $data['single_id'] = $bindInfo['single_id'];
            }
            if (! $data['village_id'] && isset($bindInfo['village_id']) && $bindInfo['village_id']) {
                $param['village_id'] = $bindInfo['village_id'];
                $data['village_id'] = $bindInfo['village_id'];
            }
        }
        if (!empty($param)) {
            $houseContactWayUserService->updateWxInfo($where_user,$param);
        }
        if ($bindPhone && $userid) {
            if (isset($infoHouseContactUserBind['id']) && $infoHouseContactUserBind['id']) {
                $saveData = [
                    'phone' => $bindPhone,
                    'update_time' => $nowTime,
                ];
                $dbHouseContactUserBind->updateThis($whereContactUserBind, $saveData);
            } else {
                $addData = [
                    'third_suite_id' => $third_suite_id,
                    'property_id' => $property_id,
                    'userid' => $userId,
                    'phone' => $bindPhone,
                    'bind_from' => 'qyweixin_sidebar',
                    'last_ip' => request()->ip(),
                    'add_time' => $nowTime,
                    'update_time' => $nowTime,
                ];
                $dbHouseContactUserBind->add($addData);
            }
        }
        fdump_api(['data' => $data, 'enterprise_wx_userInfo' => $enterprise_wx_userInfo, 'userId' => $userId, 'externalUserIDArr' => $externalUserIDArr],'chatSidebar/gethouseContactWayUser',1);
        return $data;
    }
    
    public function changeUserIds($userId, $agentid, $from_type, $from_id) {
        if($from_type == 1){
            $sHouse_village = new HouseVillageService();
            $village_info = $sHouse_village->getHouseVillageInfo(['village_id'=>$from_id],'property_id');
            $property_id = $village_info['property_id'];
        }else{
            $from_type = 2;
            $property_id =$from_id;
        }
        if (! $agentid) {
            $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
            $data = $db_house_enterprise_wx_bind->getOne(['bind_id'=>$property_id],'corpid, agentid, PlaintextCorpId');
            $serviceQywx = new QywxService();
            $agent_info = $serviceQywx->getAgentByProperty($property_id);
            if($agent_info && $agent_info['agentid']){
                $agentid = $agent_info['agentid'];
            }else{
                $agentid = $data['agentid'];
            }
        }
        $workWeiXinNewService = new WorkWeiXinNewService();
        $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
        $param = [
            'open_userid_list' => [$userId],
            'source_agentid' => $agentid,
        ];
        $result = (new WorkWeiXinRequestService())->cgiBinBatchOpenUserIdToUserId($param, $access_token);
        fdump_api([$param, $access_token, $result], '$changeUserIds');
        return $result;
    }
}