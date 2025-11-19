<?php


namespace app\community\model\service\workweixin;


use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\User;
use app\community\model\db\WeixinBindUser;
use app\community\model\db\workweixin\WorkWeixinUser;
use app\community\model\service\QywxService;
use app\consts\WorkWeiXinConst;
use think\facade\Cache;
use app\traits\WorkWeiXinToJobTraits;

class WeiXinUnionIdToExternalUserIdService
{
    use WorkWeiXinToJobTraits;

    public function handleTraitsUserToExternalUserId($property_id) {
        $queueData = [
            'property_id'       => $property_id,
            'jobType'           => 'handleUserToExternalUserId',
        ];
//        $job_id = $this->traitCommonWorkWeiXin($queueData);
//        return $job_id;
    }
    
    /**
     * 将对应该物业相关的用户 统一 匹配下企业微信的userid
     * @param $property_id
     * @param $param
     * @return array|object
     */
    public function handleUserToExternalUserId($property_id = 0, $param = []) {
        return false;
        if (!$property_id) {
            $property_id   = isset($param['property_id'])   && $param['property_id']    ? $param['property_id']   : '';
        }
        if (!$property_id) {
            return false;
        }
        $dbHouseVillage = new HouseVillage();
        $whereVillage = [];
        $whereVillage[] = ['property_id', '=', $property_id];
        $whereVillage[] = ['status',      '=', 1];
        $villageIdArr = $dbHouseVillage->getColumn($whereVillage, 'village_id');
        if (!$villageIdArr) {
            return false;
        }
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $whereUserBind = [];
        $whereUserBind[] = ['village_id', 'in', $villageIdArr];
        $whereUserBind[] = ['status',      'in', [1, 2]];
        $uidArr = $dbHouseVillageUserBind->getColumn($whereUserBind, 'uid');
        if (!$uidArr) {
            return false;
        }
        $dbUser = new User();
        $whereUser = [];
        $whereUser[] = ['uid', 'in', $uidArr];
        $whereUser[] = ['openid|wxapp_openid|paotui_openid|web_openid|wxappgroup_openid|app_openid|group_openid', '<>', ''];
        $whereUser[] = ['status', '<>', 4];
        $userList = $dbUser->getList($whereUser, 'uid');
        if ($userList && !is_array($userList)) {
            $userList = $userList->toArray();
        }
        if (!empty($userList)) {
            $keyVal = 0;
            $notUidArr = [];
            $debug   = isset($param['debug'])   && $param['debug']    ? $param['debug']   : false;
            fdump_api(['$debug' => $debug], '$debug');
            foreach ($userList as $key => $user) {
                $notUidArr[] = $user['uid'];
                $queueData = [
                    'property_id'       => $property_id,
                    'uid'               => $user['uid'],
                    'from'              => 'user',
                    'from_id'           => $user['uid'],
                    'jobType'           => 'bindUserToWorkWeiXin',
                ];
                $cacheKey  = WorkWeiXinConst::WORK_WEI_XIN_USER_TO_WORK . md5(\json_encode(['property_id' => $property_id, 'uid' => $user['uid']]));
                $userRedis  = Cache::store('redis')->get($cacheKey);
                if ($userRedis) {
                    continue;
                }
                $time = $keyVal * 5;// 间隔3秒一个进行执行
                $job_id = $this->traitCommonWorkWeiXin($queueData, $time);
                if ($debug) {
                    // todo 临时调试 直接走
                    $this->bindUserToWorkWeiXin($queueData);
                }
                $keyVal++;
            }
            // 处理下物业公众号的用户
            $weixin_bind_user = new WeixinBindUser();
            $whereBindUser = [];
            $whereBindUser[] = ['property_id', '=', $property_id];
            $whereBindUser[] = ['uid', 'not in', $notUidArr];
            $whereBindUser[] = ['openid|wxapp_openid', '<>', ''];
            $bindUserList = $weixin_bind_user->getList($whereBindUser, 'id,uid');
            if ($bindUserList && !is_array($bindUserList)) {
                $bindUserList = $bindUserList->toArray();
            }
            foreach ($bindUserList as $user1) {
                $queueData = [
                    'property_id'       => $property_id,
                    'uid'               => $user1['uid'],
                    'from'              => 'bindUser',
                    'from_id'           => $user1['id'],
                    'jobType'           => 'bindUserToWorkWeiXin',
                ];
                $cacheKey  = WorkWeiXinConst::WORK_WEI_XIN_USER_TO_WORK . md5(\json_encode(['property_id' => $property_id, 'uid' => $user1['uid']]));
                $userRedis  = Cache::store('redis')->get($cacheKey);
                if ($userRedis) {
                    continue;
                }
                $time = $keyVal * 5;// 间隔3秒一个进行执行
                $job_id = $this->traitCommonWorkWeiXin($queueData, $time);
                if ($debug) {
                    // todo 临时调试 直接走
                    $this->bindUserToWorkWeiXin($queueData);
                }
                $keyVal++;
            }
            return ['userList' => $userList, 'bindUserList' => $bindUserList];
        }
        return ['userList' => $userList, 'bindUserList' => []];
    }

    /**
     * 处理下对应用户对应企业微信的部分信息
     * @param $param
     * @return bool
     */
    public function bindUserToWorkWeiXin($param) {
        return false;
        $this->dbWorkWeixinUser          = new WorkWeixinUser();
        $this->sWorkWeiXinRequestService = new WorkWeiXinRequestService();
        $this->sWorkWeiXinSuiteService   = new WorkWeiXinSuiteService();
        $this->now_time                  = time();
        $property_id   = isset($param['property_id'])   && $param['property_id']    ? $param['property_id']   : '';
        $uid           = isset($param['uid'])           && $param['uid']            ? $param['uid']           : '';
        $from          = isset($param['from'])          && $param['from']           ? $param['from']          : 'user';
        $from_id       = isset($param['from_id'])       && $param['from_id']        ? $param['from_id']       : '';
        if (!$property_id || !$uid) {
            return false;
        }
        if ($from == 'user') {
            $dbUser = new User();
            $whereUser = [];
            $whereUser[] = ['uid', '=', $uid];
            $user = $dbUser->getOne($whereUser, 'uid, phone, openid, wxapp_openid, paotui_openid, web_openid, wxappgroup_openid, app_openid, group_openid');
            if ($user && !is_array($user)) {
                $user = $user->toArray();
            }
        } elseif ($from == 'bindUser' && $from_id) {
            $weixin_bind_user = new WeixinBindUser();
            $whereBindUser = [];
            $whereBindUser[] = ['id', '=', $from_id];
            $whereBindUser[] = ['property_id', '=', $property_id];
            $user = $weixin_bind_user->getFind($whereBindUser, 'id, uid, openid, wxapp_openid');
            if ($user && !is_array($user)) {
                $user = $user->toArray();
            }
        } else {
            return false;
        }
        fdump_api(['$user' => $user], '$user', 1);
        $openid            = isset($user['openid'])            && $user['openid']            ? $user['openid']            : '';
        $wxapp_openid      = isset($user['wxapp_openid'])      && $user['wxapp_openid']      ? $user['wxapp_openid']      : '';
        $paotui_openid     = isset($user['paotui_openid'])     && $user['paotui_openid']     ? $user['paotui_openid']     : '';
        $web_openid        = isset($user['web_openid'])        && $user['web_openid']        ? $user['web_openid']        : '';
        $wxappgroup_openid = isset($user['wxappgroup_openid']) && $user['wxappgroup_openid'] ? $user['wxappgroup_openid'] : '';
        $app_openid        = isset($user['app_openid'])        && $user['app_openid']        ? $user['app_openid']        : '';
        $group_openid      = isset($user['group_openid'])      && $user['group_openid']      ? $user['group_openid']      : '';
        $access_token = (new QywxService())->getQywxAccessToken($property_id);
        fdump_api(['$access_token' => $access_token], '$user', 1);
        $bind = false;
        if ($openid) {
            $bind = $this->bindWorkWeiXinUser($openid, $property_id, $user,'openid', $access_token);
        }
        if ($wxapp_openid && !$bind) {
            $bind = $this->bindWorkWeiXinUser($wxapp_openid, $property_id, $user,'wxapp_openid', $access_token);
        }
        if ($paotui_openid && !$bind) {
            $bind = $this->bindWorkWeiXinUser($paotui_openid, $property_id, $user,'paotui_openid', $access_token);
        }
        if ($web_openid && !$bind) {
            $bind = $this->bindWorkWeiXinUser($web_openid, $property_id, $user,'web_openid', $access_token);
        }
        if ($wxappgroup_openid && !$bind) {
            $bind = $this->bindWorkWeiXinUser($wxappgroup_openid, $property_id, $user,'wxappgroup_openid', $access_token);
        }
        if ($app_openid && !$bind) {
            $bind = $this->bindWorkWeiXinUser($app_openid, $property_id, $user,'app_openid', $access_token);
        }
        if ($group_openid && !$bind) {
            $bind = $this->bindWorkWeiXinUser($group_openid, $property_id, $user,'group_openid', $access_token);
        }
        return $bind;
    }

    private $dbWorkWeixinUser;
    private $sWorkWeiXinRequestService;
    private $sWorkWeiXinSuiteService;
    private $db_house_enterprise_wx_bind;
    private $now_time;
    public function bindWorkWeiXinUser($openid, $property_id, $user, $type_openid, $access_token = '') {
        if (!$this->dbWorkWeixinUser) {
            $this->dbWorkWeixinUser = new WorkWeixinUser();
        }
        if (!$this->sWorkWeiXinRequestService) {
            $this->sWorkWeiXinRequestService = new WorkWeiXinRequestService();
        }
        if (!$this->sWorkWeiXinSuiteService) {
            $this->sWorkWeiXinSuiteService = new WorkWeiXinSuiteService();
        }
        if (!$this->db_house_enterprise_wx_bind) {
            $this->db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
        }
        if (!$this->now_time) {
            $this->now_time = time();
        }
        if (!empty($access_token)) {
            $access_token = $this->sWorkWeiXinSuiteService->getToken($property_id);
        }
        if (!$access_token) {
            return false;
        }
        $result       = $this->sWorkWeiXinRequestService->cgiBinUserConvertToUserid($openid, $access_token);
        fdump_api(['$openid' => $openid, '$property_id' => $property_id, '$user' => $user, '$type_openid' => $type_openid, '$access_token' => $access_token, '$result' => $result], '$bindWorkWeiXinUser', 1);
        $cacheTag  = WorkWeiXinConst::WORK_WEI_XIN_JOB_REDIS_TAG;
        $cacheKey  = WorkWeiXinConst::WORK_WEI_XIN_USER_TO_WORK . md5(\json_encode(['property_id' => $property_id, 'uid' => $user['uid']]));
        if (isset($result['userid']) && $result['userid']) {
            $whereEnterpriseWxBind = [];
            $whereEnterpriseWxBind[] = ['bind_type','=',0];
            $whereEnterpriseWxBind[] = ['bind_id','=',$property_id];
            $qywxBind = $this->db_house_enterprise_wx_bind->getOne($whereEnterpriseWxBind,'corpid,wx_provider_suiteid,permanent_code,contact_way_secret');
            if ($qywxBind && !is_array($qywxBind)) {
                $qywxBind = $qywxBind->toArray();
            }
            $corpid       = isset($qywxBind['corpid'])       && $qywxBind['corpid']       ? $qywxBind['corpid']       : '';
            $whereUnique = [
                'from'        => WorkWeiXinConst::FROM_PROPERTY,
                'from_id'     => $property_id,
                'auth_corpid' => $corpid,
                'user_id'     => $result['userid'],
                'delete_time' => 0,
            ];
            $workWeiXinUserData = [
                'from'        => WorkWeiXinConst::FROM_PROPERTY,
                'from_id'     => $property_id,
                'auth_corpid' => $corpid,
                'user_id'     => $result['userid'],
                'delete_time' => 0,
            ];
            if (isset($user['phone']) && $user['phone']) {
                $workWeiXinUserData['mobile'] = $user['phone'];
            }
            if (isset($user['uid']) && $user['uid']) {
                $workWeiXinUserData['user_uid'] = $user['uid'];
            }
            $userInfo = $this->dbWorkWeixinUser->getOne($whereUnique, 'id, mobile', 'id DESC');
            if ($userInfo && !is_array($userInfo)) {
                $userInfo = $userInfo->toArray();
            }
            $workWeiXinUserData['user_openid'] = $openid;
            $workWeiXinUserData['type_openid'] = $type_openid;
            if ($userInfo && isset($userInfo['id'])) {
                if (isset($userInfo['mobile']) && $userInfo['mobile'] && isset($workWeiXinUserData['mobile'])) {
                    unset($workWeiXinUserData['mobile']);
                }
                $workWeiXinUserData['update_time'] = $this->now_time;
                $this->dbWorkWeixinUser->updateThis($whereUnique, $workWeiXinUserData);
            } else {
                $workWeiXinUserData['add_time'] = $this->now_time;
                $this->dbWorkWeixinUser->add($workWeiXinUserData);
            }
            Cache::store('redis')->tag($cacheTag)->set($cacheKey, $result['userid'],7200-mt_rand(10,30));
            return true;
        } else {
            return false;
        }
    }
}