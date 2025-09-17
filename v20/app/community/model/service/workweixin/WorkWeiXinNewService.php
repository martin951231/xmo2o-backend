<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      企业微信第三方应用业务处理[新适配]
 */

namespace app\community\model\service\workweixin;

use app\common\model\service\ConfigService;
use app\community\model\db\AccessTokenCommonExpires;
use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseServicesHousekeeper;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageDataConfig;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseWorker;
use app\community\model\db\User;
use app\community\model\db\VillageQywxActionLog;
use app\community\model\db\VillageQywxChannelCode;
use app\community\model\db\VillageQywxCodeBindLabel;
use app\community\model\db\VillageQywxCodeLabel;
use app\community\model\db\VillageQywxCodeLabelGroup;
use app\community\model\db\VillageQywxDataCenter;
use app\community\model\db\VillageQywxEngineContent;
use app\community\model\db\workweixin\HouseCorpTag;
use app\community\model\db\workweixin\HouseCorpTagGroup;
use app\community\model\db\workweixin\WorkWeixinUser;
use app\community\model\service\QywxService;
use app\consts\WorkWeiXinConst;
use app\traits\house\WorkWeiXinTraits;
use app\traits\WorkWeiXinToJobTraits;
use file_handle\FileHandle;

class WorkWeiXinNewService
{
    use WorkWeiXinTraits;
    use WorkWeiXinToJobTraits;
    // wo是企业微信类型的外部联系人，wm是微信类型的外部联系人。wb群机器人

    /**
     * 获取服务商应用的凭证suite_access_token
     * @param $new
     * @return array
     */
    public function getAgentCgiBinServiceGetSuiteToken($new = false) {
        $suite_id      = cfg('enterprise_wx_provider_suiteid');
        $suite_secret  = cfg('enterprise_wx_provider_secret');
        $suite_ticket  = (new ConfigService())->getOneField( 'enterprise_wx_suite_ticket');
        if (! $suite_ticket || ! $suite_secret || ! $suite_id) {
            fdump_api([
                'title' => '获取第三方应用凭证（suite_access_token）失败，缺少必要参数', 'suite_ticket' => $suite_ticket,
                'suite_secret' => $suite_secret, 'suite_id' => $suite_id,
            ], 'qiYeWeiXin/getAgentCgiBinServiceGetSuiteTokenErrLog', 1);
            return [
                'code'    => 1001,
                'message' => '获取第三方应用凭证（suite_access_token）失败，缺少必要参数。',
            ];
        }
        $now_time = time();
        $type          = 'suite_access_token';
        $access_id     = md5($suite_id.'_suiteid_'.$suite_secret.'_suite_secret_'.$suite_ticket);
        $where_suite_token = [];
        $where_suite_token[] = ['type',      '=', $type];
        $where_suite_token[] = ['access_id', '=', $access_id];
        $db_access_token_common_expires = new AccessTokenCommonExpires();
        $suite_info = $db_access_token_common_expires->getOne($where_suite_token, true, 'id DESC');
        if ($suite_info && !is_array($suite_info)) {
            $suite_info = $suite_info->toArray();
        }
        $now_time           = $now_time + 1800; // 提前半小时过期 避免
        if (!$new && isset($suite_info['access_token_expire']) && intval($suite_info['access_token_expire']) > intval($now_time)) {
            $suite_access_token = $suite_info['access_token'];
            return ['code' => 0, 'access_token' =>$suite_access_token];
        }
        $param = [
            'suite_id'     => $suite_id,
            'suite_secret' => $suite_secret,
            'suite_ticket' => $suite_ticket,
        ];
        $service_suite_token_result = (new WorkWeiXinRequestService())->cgiBinServiceGetSuiteToken($param);
        if (isset($service_suite_token_result['suite_access_token']) && $service_suite_token_result['suite_access_token']) {
            $suite_access_token = $service_suite_token_result['suite_access_token'];
        } else {
            $errmsg = isset($service_suite_token_result['errmsg']) ? $service_suite_token_result['errmsg'] : '获取第三方应用凭证（suite_access_token）失败';
            $code = isset($service_suite_token_result['errcode']) ? $service_suite_token_result['errcode'] : 1;
            fdump_api([
                'title' => $errmsg, 'code' => $code,
                'param' => $param,'result' => $service_suite_token_result,
            ], 'qiYeWeiXin/getAgentCgiBinServiceGetSuiteTokenErrLog', 1);
            return ['code' => $code, 'errmsg' => $errmsg, 'message' => $this->traitWorkWeiXinErrorCode($code)];
        }
        if (isset($service_suite_token_result['expires_in']) && intval($service_suite_token_result['expires_in']) > 1) {
            $access_token_expire = $now_time + intval($service_suite_token_result['expires_in']);
            $tokenParam = array(
                'access_token'        => $suite_access_token,
                'access_token_expire' => $access_token_expire,
            );
            if ($suite_info && isset($suite_info['id'])) {
                $db_access_token_common_expires->saveOne($where_suite_token, $tokenParam);
            } else {
                $tokenParam['type']      = $type;
                $tokenParam['access_id'] = $access_id;
                $db_access_token_common_expires->addOne($tokenParam);
            }
        }
        return ['code' => 0, 'access_token' =>$suite_access_token];
    }

    /**
     * 获取服务商通讯录授权的凭证suite_access_token
     * @param $new
     * @return array
     */
    public function getBookCgiBinServiceGetSuiteToken($new = false) {
        $suite_id      = cfg('enterprise_book_suiteid');
        $suite_secret  = cfg('enterprise_book_secret');
        $suite_ticket  = (new ConfigService())->getOneField( 'enterprise_book_suite_ticket');
        if (! $suite_ticket || ! $suite_secret || ! $suite_id) {
            fdump_api([
                'title' => '获取服务商通讯录授权凭证（suite_access_token）失败，缺少必要参数', 'suite_ticket' => $suite_ticket,
                'suite_secret' => $suite_secret, 'suite_id' => $suite_id,
            ], 'qiYeWeiXin/getBookCgiBinServiceGetSuiteTokenErrLog', 1);
            return [
                'code'    => 1001,
                'message' => '获取第三方应用凭证（suite_access_token）失败，缺少必要参数。',
            ];
        }
        $now_time = time();
        $type          = 'book_suite_access_token';
        $access_id     = $suite_id.'_suiteid_'.$suite_secret.'_suite_secret_'.$suite_ticket;
        $where_suite_token = [];
        $where_suite_token[] = ['type',      '=', $type];
        $where_suite_token[] = ['access_id', '=', $access_id];
        $db_access_token_common_expires = new AccessTokenCommonExpires();
        $suite_info = $db_access_token_common_expires->getOne($where_suite_token, true, 'id DESC');
        if ($suite_info && !is_array($suite_info)) {
            $suite_info = $suite_info->toArray();
        }
        $now_time           = $now_time + 1800; // 提前半小时过期 避免
        if (!$new && isset($suite_info['access_token_expire']) && intval($suite_info['access_token_expire']) > intval($now_time)) {
            $suite_access_token = $suite_info['access_token'];
            return ['code' => 0, 'access_token' =>$suite_access_token];
        }
        $param = [
            'suite_id'     => $suite_id,
            'suite_secret' => $suite_secret,
            'suite_ticket' => $suite_ticket,
        ];
        $service_suite_token_result = (new WorkWeiXinRequestService())->cgiBinServiceGetSuiteToken($param);
        if (isset($service_suite_token_result['suite_access_token']) && $service_suite_token_result['suite_access_token']) {
            $suite_access_token = $service_suite_token_result['suite_access_token'];
        } else {
            $errmsg = isset($service_suite_token_result['errmsg']) ? $service_suite_token_result['errmsg'] : '获取第三方应用凭证（suite_access_token）失败';
            $code = isset($service_suite_token_result['errcode']) ? $service_suite_token_result['errcode'] : 1;
            fdump_api([
                'title' => $errmsg, 'code' => $code,
                'param' => $param,'result' => $service_suite_token_result,
            ], 'qiYeWeiXin/getBookCgiBinServiceGetSuiteTokenErrLog', 1);
            return ['code' => $code, 'errmsg' => $errmsg, 'message' => $this->traitWorkWeiXinErrorCode($code)];
        }
        if (isset($service_suite_token_result['expires_in']) && intval($service_suite_token_result['expires_in']) > 1) {
            $access_token_expire = $now_time + intval($service_suite_token_result['expires_in']);
            $tokenParam = array(
                'access_token'        => $suite_access_token,
                'access_token_expire' => $access_token_expire,
            );
            if ($suite_info && isset($suite_info['id'])) {
                $db_access_token_common_expires->saveOne($where_suite_token, $tokenParam);
            } else {
                $tokenParam['type']      = $type;
                $tokenParam['access_id'] = $access_id;
                $db_access_token_common_expires->addOne($tokenParam);
            }
        }
        return ['code' => 0, 'access_token' =>$suite_access_token];
    }


    /**
     * 第三方服务商在取得企业的永久授权码后，通过此接口可以获取到企业的access_token
     * @param $property_id
     * @param $new
     * @return array|void
     */
    public function getCgiBinServiceGetCorpToken($property_id, $isAgent = true, $suite_access_token = '', $new = false) {
        if (! $suite_access_token && $isAgent) {
            $suite_access_token_info = $this->getAgentCgiBinServiceGetSuiteToken($new);
            $suite_access_token = isset($suite_access_token_info['access_token']) ? $suite_access_token_info['access_token'] : '';
        } elseif (! $suite_access_token && ! $isAgent) {
            $suite_access_token_info = $this->getBookCgiBinServiceGetSuiteToken($new);
            $suite_access_token = isset($suite_access_token_info['access_token']) ? $suite_access_token_info['access_token'] : '';
        }
        $where[] = ['bind_type', '=', 0];
        $where[] = ['bind_id', '=', $property_id];
        $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corpid, permanent_code, book_permanent_code','pigcms_id DESC');
        if ($bindInfo && !is_array($bindInfo)) {
            $bindInfo = $bindInfo->toArray();
        }
        if (! $property_id || empty($bindInfo) || ! $suite_access_token) {
            fdump_api([
                'title' => '通过此接口可以获取到企业的access_token失败，缺少必要参数', 'property_id' => $property_id,
                'bindInfo' => $bindInfo,'isAgent' => $isAgent,
            ], 'qyweixin/getCgiBinServiceGetCorpTokenErrLog', 1);
            return [
                'code'    => 1001,
                'message' => '获取到企业的access_token失败，缺少必要参数。',
            ];
        }
        $auth_corpid = isset($bindInfo['corpid']) && $bindInfo['corpid'] ? $bindInfo['corpid'] : '';
        if ($isAgent) {
            $permanent_code = isset($bindInfo['permanent_code']) && $bindInfo['permanent_code'] ? $bindInfo['permanent_code'] : '';
        } else {
            $permanent_code = isset($bindInfo['book_permanent_code']) && $bindInfo['book_permanent_code'] ? $bindInfo['book_permanent_code'] : '';
        }
        $now_time = time();
        if ($isAgent) {
            $type          = 'agent_access_token';
        } else {
            $type          = 'third_app_access_token';
        }
        $access_id     = md5($auth_corpid.'_auth_corpid_'.$permanent_code.'_permanent_code_'.$suite_access_token);
        $where_third_app_access_token = [];
        $where_third_app_access_token[] = ['type',      '=', $type];
        $where_third_app_access_token[] = ['access_id', '=', $access_id];
        $db_access_token_common_expires = new AccessTokenCommonExpires();
        $third_app_info = $db_access_token_common_expires->getOne($where_third_app_access_token, true, 'id DESC');
        if ($third_app_info && !is_array($third_app_info)) {
            $third_app_info = $third_app_info->toArray();
        }
        $now_time           = $now_time + 1800; // 提前半小时过期 避免


        fdump_api(['msg' => '企业微信同步access_token', 'line' => __LINE__, 'access_id' => $access_id, 'isAgent' => $isAgent, 'type' => $type, 'third_app_info' => $third_app_info], 'qiYeWeiXin/getCgiBinServiceGetCorpTokenLog', 1);
        
        if (!$new && isset($third_app_info['access_token_expire']) && intval($third_app_info['access_token_expire']) > intval($now_time)) {
            $access_token = $third_app_info['access_token'];
            return ['code' => 0, 'access_token' =>$access_token, 'suite_access_token' =>$suite_access_token];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinServiceGetCorpToken($auth_corpid, $permanent_code, $suite_access_token);

        fdump_api(['msg' => '企业微信同步access_token', 'line' => __LINE__, 'property_id' => $property_id, 'auth_corpid' => $auth_corpid, 'permanent_code' => $permanent_code, 'suite_access_token' => $suite_access_token, 'result' => $result], 'qiYeWeiXin/getCgiBinServiceGetCorpTokenLog', 1);
        if (isset($result['access_token']) && $result['access_token']) {
            $access_token = $result['access_token'];
        } else {
            $errmsg = isset($result['errmsg']) ? $result['errmsg'] : '获取到企业的access_token失败失败';
            $code = isset($result['errcode']) ? $result['errcode'] : 1;
            fdump_api([
                'title' => $errmsg, 'code' => $code,'isAgent' => $isAgent,
                'auth_corpid' => $auth_corpid, 'permanent_code' => $permanent_code, 'suite_access_token' => $suite_access_token, 'result' => $result,
            ], 'qiYeWeiXin/getCgiBinServiceGetCorpTokenErrLog', 1);
            return ['code' => $code, 'errmsg' => $errmsg, 'message' => $this->traitWorkWeiXinErrorCode($code)];
        }
        if (isset($result['expires_in']) && intval($result['expires_in']) > 1) {
            $access_token_expire = $now_time + intval($result['expires_in']);
            $tokenParam = array(
                'access_token'        => $access_token,
                'access_token_expire' => $access_token_expire,
            );
            if ($third_app_info && isset($third_app_info['id'])) {
                fdump_api([
                    'title' => '更新','where_third_app_access_token' => $where_third_app_access_token,'tokenParam' => $tokenParam,
                ], 'qiYeWeiXin/getCgiBinServiceGetCorpTokenLog', 1);
                $db_access_token_common_expires->saveOne($where_third_app_access_token, $tokenParam);
            } else {
                $tokenParam['type']      = $type;
                $tokenParam['access_id'] = $access_id;
                fdump_api([
                    'title' => '新增','tokenParam' => $tokenParam,
                ], 'qiYeWeiXin/getCgiBinServiceGetCorpTokenLog', 1);
                $db_access_token_common_expires->addOne($tokenParam);
            }
        }
        return ['code' => 0, 'access_token' =>$access_token, 'suite_access_token' =>$suite_access_token];
    }

    /**
     * 将企业主体下的明文userid转换为服务商主体下的密文userid。
     */
    public function getCgiBinBatchUseridToOpenuserid($property_id, $userids, $whereWork = [], $access_token = '') {
        if (! $access_token) {
            $access_token = $this->commonGetAccessToken($property_id);
        }
        if (! $access_token || ! $userids) {
            fdump_api([
                'title' => 'userid的转换失败', 'property_id' => $property_id,
                'userids' => $userids, 'access_token' => $access_token,
            ], 'qiYeWeiXin/getCgiBinBatchUseridToOpenuseridErrLog', 1);
            return [
                'config_id' => '',
                'qr_code' => '',
                'errcode' => '-1001',
                'errmsg' =>'token获取失败',
            ];
        }
        $toResult = (new WorkWeiXinRequestService())->cgiBinBatchUseridToOpenuserid($userids, $access_token);
        $openUserids = [];
        if (isset($toResult['open_userid_list']) && !empty($toResult['open_userid_list'])) {
            $dbHouseWorker = new HouseWorker();
            foreach ($toResult['open_userid_list'] as $open_userids) {
                $userid = $open_userids['userid'];
                $open_userid = $open_userids['open_userid'];
                $openUserids[] = $open_userid;
                if (! empty($whereWork)) {
                    $whereWork1 = $whereWork;
                    $whereWork1[] = ['qy_id', '=', $userid];
                    $dbHouseWorker->editData($whereWork, ['qy_open_userid' => $open_userid]);
                }
            }
        } else {
            fdump_api([
                'title' => 'userid的转换结果失败', 'property_id' => $property_id,
                'userids' => $userids,'whereWork' => $whereWork, 'access_token' => $access_token, 'toResult' => $toResult,
            ], 'qiYeWeiXin/getCgiBinBatchUseridToOpenuseridErrLog', 1);
        }
        return $openUserids;
    }

    public function contactWayEventBox($param)
    {
        try {
            $param['jobType'] = 'contactWayEvent';
            $job_id = $this->traitCommonWorkWeiXin($param, 1);
            fdump_api(['msg' => '处理服务商客户相关推送-队列', 'param' => $param, 'job_id' => $job_id, 'line' => __LINE__], 'qiYeWeiXin/contactWayEventBoxLog', 1);
        } catch (\Exception $e) {
            fdump_api(['msg' => '处理服务商客户相关推送-捕捉异常盒子', 'param' => $param, 'errLine' => $e->getLine(), 'errMsg' => $e->getMessage(), 'errFile' => $e->getFile(), 'errCode' => $e->getCode(), 'line' => __LINE__], 'qiYeWeiXin/contactWayEventBoxLog', 1);
            return false;
        }
    }
    
    /**
     * 处理服务商客户相关推送,注意这里只处理服务商的内部不走这里.
     */
    public function contactWayEvent($param) {
        $changeType = isset($param['ChangeType']) && $param['ChangeType'] ? trim($param['ChangeType']) : '';
        fdump_api([
            'title' => '客户回调通知', 'param' => $param
        ], 'qiYeWeiXin/'.$changeType, true);
        switch ($changeType) {
            case 'add_half_external_contact':
                // 外部联系人免验证添加成员事件
            case 'add_external_contact':
                // 添加企业客户事件
                $this->addExternalContact($param);
                break;
            case 'edit_external_contact':
                // 编辑企业客户事件
                break;
            case 'del_external_contact':
                // 删除企业客户事件[https://developer.work.weixin.qq.com/document/path/92277#%E7%BC%96%E8%BE%91%E4%BC%81%E4%B8%9A%E5%AE%A2%E6%88%B7%E4%BA%8B%E4%BB%B6]
            case 'del_follow_user':
                // 删除跟进成员事件
                $this->delFollowUser($param);
                break;
            case 'customer_refused':
                // 客户接替失败事件[https://developer.work.weixin.qq.com/document/path/92277#%E5%AE%A2%E6%88%B7%E6%8E%A5%E6%9B%BF%E5%A4%B1%E8%B4%A5%E4%BA%8B%E4%BB%B6]
                break;
            case 'create':
                $infoType = isset($param['InfoType']) ? $param['InfoType'] : '';
                switch ($infoType) {
                    case 'change_external_chat':
                        // 客户群创建事件[https://developer.work.weixin.qq.com/document/path/92277#%E5%AE%A2%E6%88%B7%E7%BE%A4%E5%88%9B%E5%BB%BA%E4%BA%8B%E4%BB%B6]
                        break;
                    case 'change_external_tag':
                        // 企业客户标签创建事件[https://developer.work.weixin.qq.com/document/path/92277#%E4%BC%81%E4%B8%9A%E5%AE%A2%E6%88%B7%E6%A0%87%E7%AD%BE%E5%88%9B%E5%BB%BA%E4%BA%8B%E4%BB%B6]
                        $this->commonChangeExternalTag($param);
                        break;
                }
                break;
            case 'update':
                $infoType = isset($param['InfoType']) ? $param['InfoType'] : '';
                switch ($infoType) {
                    case 'change_external_chat':
                        // 客户群变更事件[https://developer.work.weixin.qq.com/document/path/92277#%E5%AE%A2%E6%88%B7%E7%BE%A4%E5%8F%98%E6%9B%B4%E4%BA%8B%E4%BB%B6]
                        break;
                    case 'change_external_tag':
                        // 企业客户标签变更事件[https://developer.work.weixin.qq.com/document/path/92277#%E4%BC%81%E4%B8%9A%E5%AE%A2%E6%88%B7%E6%A0%87%E7%AD%BE%E5%8F%98%E6%9B%B4%E4%BA%8B%E4%BB%B6]
                        $this->commonChangeExternalTag($param);
                        break;
                }
                break;
            case 'dismiss':
                // 客户群解散事件[https://developer.work.weixin.qq.com/document/path/92277#%E5%AE%A2%E6%88%B7%E7%BE%A4%E8%A7%A3%E6%95%A3%E4%BA%8B%E4%BB%B6]
                break;
            case 'delete':
                $infoType = isset($param['InfoType']) ? $param['InfoType'] : '';
                switch ($infoType) {
                    case 'change_external_tag':
                        // 企业客户标签删除事件[https://developer.work.weixin.qq.com/document/path/92277#%E4%BC%81%E4%B8%9A%E5%AE%A2%E6%88%B7%E6%A0%87%E7%AD%BE%E5%88%A0%E9%99%A4%E4%BA%8B%E4%BB%B6]
                        $this->commonChangeExternalTag($param);
                        break;
                }
                break;
            case 'shuffle':
                // 企业客户标签重排事件[https://developer.work.weixin.qq.com/document/path/92277#%E4%BC%81%E4%B8%9A%E5%AE%A2%E6%88%B7%E6%A0%87%E7%AD%BE%E9%87%8D%E6%8E%92%E4%BA%8B%E4%BB%B6]
                $this->commonChangeExternalTag($param);
                break;
        }
    }

    /**
     * 添加企业客户事件.
     */
    public function addExternalContact($param) {
        $changeType = isset($param['ChangeType']) && $param['ChangeType'] ? trim($param['ChangeType']) : '';
        $externalUserID = isset($param['ExternalUserID']) ? $param['ExternalUserID'] : '';
        $userID = isset($param['UserID']) ? $param['UserID'] : '';
        $add_data = [];
        $add_data['ChangeType'] = $changeType;
        $add_data['UserID'] = $userID;
        $add_data['ExternalUserID'] = $externalUserID;
        $third_suite_id = '';
        if (isset($param['SuiteId']) && $param['SuiteId']) {
            // 第三方应用-第三方应用ID
            $third_suite_id = $param['SuiteId'];
            $add_data['third_suite_id'] = $third_suite_id;
        }
        if (isset($param['AuthCorpId']) && $param['AuthCorpId']) {
            // 第三方应用-授权企业的CorpID
            $add_data['corpid'] = $param['AuthCorpId'];
        }
        $timeStamp = time();
        if (isset($param['TimeStamp']) && $param['TimeStamp']) {
            // 第三方应用-时间戳
            $add_data['CreateTime'] = $param['TimeStamp'];
            $timeStamp = $param['TimeStamp'];
        }
        if (isset($param['InfoType']) && $param['InfoType']) {
            $add_data['Event'] = $param['InfoType'];
        }
        // 是否发送欢迎语
        $is_send = true;
        $village_id = 0;
        $property_id = 0;
        $business_type = '';
        fdump_api([
            'title' => '接收数据初步整理', 'param' => $param, 'add_data' => $add_data
        ], 'qiYeWeiXin/contactWayEventLog', true);
        if (isset($param['State']) && $param['State']) {
            $add_data['State'] = $param['State'];
            $state_arr = explode('|', $param['State']);
            $code_id = 0;
            $housekeeper_id = 0;
            $key_type = [];
            if (isset($state_arr[0]) && $state_arr[0]) {
                $key_type = explode('#', $state_arr[0]);
            }
            if (isset($key_type[0]) && isset($key_type[1]) && $key_type[0] == 'housekeeper' && $key_type[1]) {
                // 来源于 楼栋单元管家
                $housekeeper_id = $key_type[1];
                $add_data['housekeeper_id'] = $housekeeper_id;
                $add_data['business_id'] = $housekeeper_id;
                $business_type = 'house_services_housekeeper';
                $add_data['business_type'] = $business_type;
            } elseif (isset($key_type[0]) && isset($key_type[1]) && $key_type[0] == 'channelCode' && $key_type[1]) {
                $code_id = $key_type[1];
                // 来源于渠道活码
                $add_data['business_id'] = $code_id;
                $business_type = 'village_qywx_channel_code';
                $add_data['business_type'] = $business_type;
            } elseif (isset($key_type[0]) && $key_type[0] && (!isset($key_type[1]) || !$key_type[1])) {
                // 默认来源于 楼栋单元管家
                $housekeeper_id = $key_type[0];
                $add_data['housekeeper_id'] = $housekeeper_id;
                $add_data['business_id'] = $housekeeper_id;
                $business_type = 'house_services_housekeeper';
                $add_data['business_type'] = $business_type;
            }
            fdump_api([
                'title' => '接收数据2次整理','add_data' => $add_data, 'housekeeper_id' => $housekeeper_id,
                'code_id' => $code_id, 'state_arr' => $state_arr, 'key_type' => $key_type, 'business_type' => $business_type
            ], 'qiYeWeiXin/contactWayEventLog',true);
            if ($housekeeper_id > 0) {
                $dbHouseServicesHousekeeper = new HouseServicesHousekeeper();
                $where_housekeeper = [];
                $where_housekeeper[] = ['housekeeper_id', '=', $housekeeper_id];
                $housekeeper_info = $dbHouseServicesHousekeeper->getOne($where_housekeeper);
                if ($housekeeper_info && ! is_array($housekeeper_info)) {
                    $housekeeper_info = $housekeeper_info->toArray();
                }
                if (!empty($housekeeper_info)) {
                    $property_id = isset($housekeeper_info['property_id']) ? intval($housekeeper_info['property_id']) : 0;
                    $village_id = isset($housekeeper_info['village_id']) ? intval($housekeeper_info['village_id']) : 0;
                    $single_id = isset($housekeeper_info['single_id']) ? intval($housekeeper_info['single_id']) : 0;
                    $floor_id = isset($housekeeper_info['floor_id']) ? intval($housekeeper_info['floor_id']) : 0;
                    $add_data['property_id'] = $property_id;
                    $add_data['village_id'] = $village_id;
                    $add_data['single_id'] = $single_id;
                    $add_data['floor_id'] = $floor_id;
                }
                fdump_api([
                    'title' => '楼栋管家', 'housekeeper_info' => $housekeeper_info, 'housekeeper_id' => $housekeeper_id,
                ], 'qiYeWeiXin/contactWayEventLog', true);
            } elseif ($code_id) {
                $dbVillageQywxChannelCode = new VillageQywxChannelCode();
                $where_channel_code = [];
                $where_channel_code[] = ['code_id', '=', $code_id];
                $code_info = $dbVillageQywxChannelCode->getOne($where_channel_code, 'code_id, village_id, property_id, is_send, label_txt');
                if ($code_info && !is_array($code_info)) {
                    $code_info = $code_info->toArray();
                }
                if (!empty($code_info)) {
                    $property_id = isset($code_info['property_id']) ? intval($code_info['property_id']) : 0;
                    $village_id = isset($code_info['village_id']) ? intval($code_info['village_id']) : 0;
                    $add_data['property_id'] = $property_id;
                    $add_data['village_id'] = $village_id;
                }
                if (2 == $code_info['is_send']) {
                    $is_send = false;
                }
                // 客户数累计加1
                $dbVillageQywxChannelCode->setInc($where_channel_code,'customer_num', 1);
                fdump_api([
                    'title' => '渠道活码', 'code_info' => $code_info, 'code_id' => $code_id, 'is_send' => $is_send,
                ], 'qiYeWeiXin/contactWayEventLog', true);
            }
            fdump_api(['title' => '接收数据3次整理', '$add_data' => $add_data], 'qiYeWeiXin/contactWayEventLog',true);
            if (isset($state_arr[1]) && $state_arr[1]) {
                $key_value = explode('#', $state_arr[1]);
                $key = isset($key_value[0]) && $key_value[0] ? $key_value[0] : '';
                if ($key && $key_value[1] && (isset($add_data[$key]) || !$add_data[$key])) {
                    $add_data[$key] = $key_value[1];
                }
            }
        }

        if (!$property_id && isset($param['AuthCorpId']) && $param['AuthCorpId'] && $third_suite_id) {
            $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
            $whereWxBind = array('corpid' => $param['AuthCorpId'], 'bind_type' => 0,'wx_provider_suiteid'=>$third_suite_id);
            $wxBindInfo = $db_house_enterprise_wx_bind->getOne($whereWxBind, 'bind_id');
            $property_id = isset($wxBindInfo['bind_id']) ? intval($wxBindInfo['bind_id']) : 0;
        }
        if ((!isset($add_data['property_id']) || !$add_data['property_id']) && $property_id) {
            $add_data['property_id'] = $property_id;
        }
        $user_msg = (new QywxService())->serviceCgiBinExternalContactGet($externalUserID, $property_id, $village_id);
        fdump_api([
            'title' => '获取客户详情-结果','user_msg' => $user_msg,'property_id' => $property_id,'village_id' => $village_id, 'externalUserID' => $externalUserID,
        ], 'qiYeWeiXin/contactWayEventLog',true);
        if (isset($user_msg) && !empty($user_msg)) {
            if ($user_msg['name']) {
                $add_data['name'] = $user_msg['name'];
            }
            if ($user_msg['type']) {
                $add_data['type'] = $user_msg['type'];
            }
            if ($user_msg['avatar']) {
                $add_data['avatar'] = $user_msg['avatar'];
            }
            if ($user_msg['gender']) {
                $add_data['gender'] = $user_msg['gender'];
            }
        }
        $add_data['status'] = 1;
        $add_data['add_time'] = time();
        if (isset($param['WelcomeCode']) && $param['WelcomeCode']) {
            $add_data['WelcomeCode'] = $param['WelcomeCode'];
        }
        fdump_api([
            'title' => '记录前整理保存前','add_data' => $add_data,'code_info' => $code_info,
        ], 'qiYeWeiXin/contactWayEventLog',true);
        $customer_id = (new HouseContactWayUser())->addFind($add_data);
        fdump_api([
            'title' => '记录前整理保存后','customer_id' => $customer_id,'is_send' => $is_send,
        ], 'qiYeWeiXin/contactWayEventLog',true);
        if ($is_send && isset($add_data['WelcomeCode']) && $add_data['WelcomeCode']) {
            // todo 后续的走队列
            $queueData = [
                'customer_id' => $customer_id,
                'contactData' => $add_data,
                'type' => 'send_welcome_msg',
                'jobType' => 'workWeiXinNew',
            ];
            try{
                $job_id = $this->traitCommonWorkWeiXin($queueData);
                fdump_api([
                    'title' => '发送欢迎语', 'queueData' => $queueData, 'job_id' => $job_id,
                ], 'qiYeWeiXin/contactWayEventLog',true);
            }catch (\Exception $e){
                return [
                    'code'    => 1001,
                    'message' => $e->getMessage(),
                ];
            }
        }
        if (isset($code_info) && isset($code_info['label_txt'])) {
            // todo 走队列 对应打标签
            if ($externalUserID) {
                $queueData = [
                    'code_id' => $code_info['code_id'],
                    'userid' => $userID,
                    'external_userid' => $externalUserID,
                    'property_id' => $property_id,
                    'customer_id' => $customer_id,
                    'contactData' => $add_data,
                    'code_mark_user' => 1,
                    'type' => 'update_corp_tag_info',
                    'jobType' => 'workWeiXinNew',
                ];
                try{
                    $job_id = $this->traitCommonWorkWeiXin($queueData);
                    fdump_api([
                        'title' => '对应打标签', 'queueData' => $queueData, 'job_id' => $job_id,
                    ], 'qiYeWeiXin/contactWayEventLog',true);
                }catch (\Exception $e){
                    fdump_api(['title' => '标签未打上', 'err' => $e->getMessage()], 'qiYeWeiXin/sendWelcomeMsgErrLog',true);
                }
            } else {
                fdump_api(['title' => '标签未打上【缺少参数】', 'contactData' => $add_data], 'qiYeWeiXin/sendWelcomeMsgErrLog',true);
            }
        }
        $queueData = [
            'customer_id' => $customer_id,
            'contactData' => $add_data,
            'userID' => $userID,
            'externalUserID' => $externalUserID,
            'property_id' => $property_id,
            'village_id' => $village_id,
            'timeStamp' => $timeStamp,
            'type' => 'village_qywx_data_statistics_add',
            'jobType' => 'workWeiXinNew',
        ];
        try{
            $job_id = $this->traitCommonWorkWeiXin($queueData, 60);
            fdump_api([
                'title' => '统计', 'job_id' => $job_id,
            ], 'qiYeWeiXin/contactWayEventLog',true);
        }catch (\Exception $e){
            return [
                'code'    => 1001,
                'message' => $e->getMessage(),
            ];
        }
        return true;
    }

    /**
     * 删除企业客户事件+删除跟进成员事件.
     */
    public function delFollowUser($param) {
        $changeType = isset($param['ChangeType']) && $param['ChangeType'] ? trim($param['ChangeType']) : '';
        $externalUserID = isset($param['ExternalUserID']) ? $param['ExternalUserID'] : '';
        $userID = isset($param['UserID']) ? $param['UserID'] : '';
        $authCorpId = isset($param['AuthCorpId']) ? $param['AuthCorpId'] : '';
        $suiteId = isset($param['SuiteId']) ? $param['SuiteId'] : '';
        $timeStamp = isset($param['TimeStamp']) ? $param['TimeStamp'] : time();
        $source = isset($param['Source']) ? $param['Source'] : '';
        $infoType = isset($param['infoType']) ? $param['infoType'] : '';
        $queueData = [
            'userID' => $userID, 'externalUserID' => $externalUserID, 'authCorpId' => $authCorpId,
            'suiteId' => $suiteId, 'timeStamp' => $timeStamp, 'source' => $source, 'infoType' => $infoType,
            'type' => 'village_qywx_data_statistics_reduce',
            'jobType' => 'workWeiXinNew',
        ];
        try{
            $job_id = $this->traitCommonWorkWeiXin($queueData, 60);
            fdump_api([
                'title' => '统计', 'job_id' => $job_id,
            ], 'qiYeWeiXin/contactWayEventLog',true);
        }catch (\Exception $e){
            return [
                'code'    => 1001,
                'message' => $e->getMessage(),
            ];
        }
        return true;
    }

    /**
     * 企业客户标签变动.
     */
    public function commonChangeExternalTag($param) {
        $tagId = isset($param['Id']) && $param['Id'] ? $param['Id'] : '';
        $infoType = isset($param['InfoType']) ? $param['InfoType'] : '';
        $changeType = isset($param['ChangeType']) && $param['ChangeType'] ? trim($param['ChangeType']) : '';
        if (isset($param['SuiteId']) && isset($param['AuthCorpId'])) {
            $whereWxBind = [
                'bind_type' => 0,
                'corpid' => $param['AuthCorpId'],
                'wx_provider_suiteid' => $param['SuiteId'],
            ];
            $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
            $info = $dbHouseEnterpriseWxBind->getOne($whereWxBind, 'bind_id');
            $property_id = isset($info['bind_id']) ? intval($info['bind_id']) : 0;
            $tagIds = [];
            if ($tagId) {
                $tagIds[] = $tagId;
            }
            $result = (new WorkWeiXinNewService())->updateCorpTagInfo($property_id, $tagIds);
            fdump_api([
                'title' => '调取结果', 'result' => $result,
            ], 'qyweixin/contactWayEvent_'.$changeType.'/'.$infoType,true);
        }
        fdump_api([
            'title' => '企业客户标签创建事件', 'tagId' => $tagId,
        ], 'qyweixin/contactWayEvent_'.$changeType.'/'.$infoType,true);
        return true;
    }

    /**
     * 发送欢迎语.
     */
    public function sendWelcomeMsg($params) {
        fdump_api([
            'title' => '发送欢迎语', 'params' => $params,
        ], 'qiYeWeiXin/sendWelcomeMsgLog', true);
        $customer_id = isset($params['customer_id']) && $params['customer_id'] ? $params['customer_id'] : '';
        $contactData = isset($params['contactData']) && $params['contactData'] ? $params['contactData'] : [];
        $dbHouseContactWayUser = new HouseContactWayUser();
        if (empty($contactData) && $customer_id) {
            $contactData = $dbHouseContactWayUser->getOne(['customer_id' => $customer_id]);
            if ($contactData && ! is_array($contactData)) {
                $contactData = $contactData->toArray();
            }
        }
        if (empty($contactData)) {
            fdump_api([
                'title' => '缺少对应【联系我】相关信息', 'params' => $params,
            ], 'qiYeWeiXin/sendWelcomeMsgErrLog', true);
            return false;
        }
        $welcomeCode = isset($contactData['WelcomeCode']) && $contactData['WelcomeCode'] ? $contactData['WelcomeCode'] : '';
        if (!$welcomeCode) {
            fdump_api([
                'title' => '发送欢迎语缺少关键值', 'params' => $params, 'contactData' => $contactData,
            ], 'qiYeWeiXin/sendWelcomeMsgErrLog',true);
            return false;
        }
        $property_id = isset($contactData['property_id']) && $contactData['property_id'] ? $contactData['property_id'] : 0;
        $village_id = isset($contactData['village_id']) && $contactData['village_id'] ? $contactData['village_id'] : 0;
        $housekeeper_id = isset($contactData['housekeeper_id']) && $contactData['housekeeper_id'] ? $contactData['housekeeper_id'] : 0;
        $business_type = isset($contactData['business_type']) && $contactData['business_type'] ? $contactData['business_type'] : '';
        $business_id = isset($contactData['business_id']) && $contactData['business_id'] ? $contactData['business_id'] : 0;
        $userID = $contactData['UserID'] ? $contactData['UserID'] : '';
        $data = [
            'welcome_code' => $welcomeCode
        ];
        $attachments = [];
        fdump_api([
            'title' => '【联系我】推送的相关信息', 'contactData' => $contactData,
        ], 'qiYeWeiXin/sendWelcomeMsgLog', true);
        $notHandle = true;
        if ('house_services_housekeeper' === strval($business_type) || (!$business_type && $housekeeper_id)) {
            $notHandle = false;
            !$housekeeper_id && $housekeeper_id = $business_id;
            // 楼栋单元管家 扫码进入的  信息发送欢迎语
            $dbHouseServicesHousekeeper = new HouseServicesHousekeeper();
            $where_housekeeper = [];
            $where_housekeeper[] = ['housekeeper_id', '=', $housekeeper_id];
            $housekeeper_info = $dbHouseServicesHousekeeper->getOne($where_housekeeper);
            if ($housekeeper_info && !is_array($housekeeper_info)) {
                $housekeeper_info = $housekeeper_info->toArray();
            }
            if (empty($housekeeper_info)) {
                fdump_api([
                    'title' => '缺少对应【楼栋管家】相关信息', 'params' => $params, 'contactData' => $contactData,'housekeeper_id' => $housekeeper_id,
                ], 'qiYeWeiXin/sendWelcomeMsgErrLog', true);
                return false;
            }
            fdump_api([
                'title' => '【楼栋管家】的相关信息', 'housekeeper_info' => $housekeeper_info,
            ], 'qiYeWeiXin/sendWelcomeMsgLog', true);
            !$property_id && $property_id = isset($housekeeper_info['property_id']) ? intval($housekeeper_info['property_id']) : 0;
            !$village_id && $village_id = isset($housekeeper_info['village_id']) ? intval($housekeeper_info['village_id']) : 0;
            $welcome_tip = isset($housekeeper_info['welcome_tip']) && $housekeeper_info['welcome_tip'] ? $housekeeper_info['welcome_tip'] : '';
            if ($welcome_tip) {
                $where_work = [];
                if ($userID) {
                    $where_work[] = ['qy_id|qy_open_userid', '=', $userID];
                } else {
                    $work_arr_string = $housekeeper_info['work_arr'];
                    $work_arr = explode(',', $work_arr_string);
                    $where_work[] = ['wid', 'in', $work_arr];
                    $where_work[] = ['status', 'in', 1];
                    $where_work[] = ['qy_id','<>', ''];
                    $where_work[] = ['qy_status', '=', 1];
                }
                $work = (new HouseWorker())->getOne($where_work, 'wid,name, phone, qy_id');
                if ($work && !is_array($work)) {
                    $work = $work->toArray();
                }
                !$userID && $userID = isset($work['qy_open_userid']) && $work['qy_open_userid'] ? $work['qy_open_userid'] : '';
                !$userID && $userID = isset($work['qy_id']) && $work['qy_id'] ? $work['qy_id'] : '';
                fdump_api([
                    'title' => '【对应工作人员】的相关信息', 'work' => $work,
                ], 'qiYeWeiXin/sendWelcomeMsgLog', true);
                $work_param_word = $this->getSendWelcomeWorkParamWord();
                foreach ($work_param_word as $val) {
                    if ($val && $val['word']) {
                        // 不存在的信息替换成空
                        if (!$work[$val['word']]) {
                            $work[$val['word']] = '';
                        }
                        $welcome_tip = preg_replace('/' . $val['key'] . '/', $work[$val['word']] . $val['name'], $welcome_tip);
                    }
                }
                $data['text'] = [
                    'content' => $welcome_tip
                ];
                if (isset($housekeeper_info['floor_id']) && $housekeeper_info['floor_id'] > 0) {
                    $where_floor = [
                        'floor_id' => $housekeeper_info['floor_id']
                    ];
                    $floorInfo = (new HouseVillageFloor())->getOne($where_floor, 'floor_name');
                    $floor_name = isset($floorInfo['floor_name']) && $floorInfo['floor_name'] ? $floorInfo['floor_name'] : '';
                }
                if (isset($housekeeper_info['single_id']) && $housekeeper_info['single_id']>0) {
                    $where_single = [
                        'id' => $housekeeper_info['single_id']
                    ];
                    $singleInfo = (new HouseVillageSingle())->getOne($where_single, 'single_name');
                    $single_name = isset($singleInfo['single_name']) && $singleInfo['single_name'] ? $singleInfo['single_name'] : '';
                }
                if ($village_id) {
                    $village_info = (new HouseVillage())->getOne($village_id, 'village_logo');
                    $village_logo = isset($village_info['village_logo']) && $village_info['village_logo'] ? $village_info['village_logo'] : '';
                }
                $site_url = cfg('site_url');
                if (isset($village_logo) && $village_logo) {
                    $picurl = replace_file_domain($village_logo);
                } else {
                    $picurl = $site_url. '/static/images/house/qywx/manager.png';
                }
                $title = "加入";
                $wuye = true;
                if (isset($single_name) && $single_name) {
                    $title .= $single_name;
                    $wuye = false;
                }
                if (isset($floor_name) && $floor_name) {
                    $title .= $floor_name;
                    $wuye = false;
                }
                if ($wuye) {
                    $title .= '物业';
                }
                $title .= '管家服务群';
                if ($customer_id) {
                    $url = $site_url . "/wap.php?g=Wap&c=HouseEnterpriseWeixin&a=contact_way_invitation&customer_id={$customer_id}";
                } else {
                    $url = $site_url . "/wap.php?g=Wap&c=HouseEnterpriseWeixin&a=contact_way_invitation&welcome_code={$welcomeCode}";
                }
                if (intval($housekeeper_info['template_type']) === 0 && isset($housekeeper_info['qy_qrcode']) && $housekeeper_info['qy_qrcode']) {
                    $attachments[] = [
                        'msgtype' => 'link',
                        'link' => [
                            'title' => $title,
                            'picurl' => $picurl,
                            'desc' => '快速接收物业通知，邻里沟通',
                            'url' => $url,
                        ]
                    ];
                }
                if (intval($housekeeper_info['template_type']) != 0 && isset($housekeeper_info['effect_img']) && $housekeeper_info['effect_img']) {
                    $attachments[] = [
                        'msgtype' => 'link',
                        'link' => [
                            'title' => $title,
                            'picurl' => $picurl,
                            'desc' => '快速接收物业通知，邻里沟通',
                            'url' => $url,
                        ]
                    ];
                }
                fdump_api([
                    'title' => '【楼栋管家整理后】的相关信息', 'data' => $data,
                ], 'qiYeWeiXin/sendWelcomeMsgLog', true);
            }
        }
        if ('village_qywx_channel_code' === strval($business_type) && $business_id) {
            $notHandle = false;
            $code_id = $business_id;
            $dbVillageQywxChannelCode = new VillageQywxChannelCode();
            $where_channel_code = [];
            $where_channel_code[] = ['code_id', '=', $code_id];
            $code_info = $dbVillageQywxChannelCode->getOne($where_channel_code);
            if ($code_info && !is_array($code_info)) {
                $code_info = $code_info->toArray();
            }
            fdump_api([
                'title' => '【渠道活码】的相关信息', 'code_info' => $code_info,
            ], 'qiYeWeiXin/sendWelcomeMsgLog', true);
            if (2 === intval($code_info['is_send'])) {
                fdump_api([
                    'title' => '不发送欢迎语', 'is_send' => $code_info['is_send'],
                ], 'qiYeWeiXin/sendWelcomeMsgErrLog', true);
                return false;
            }
            if (! $code_info['welcome_tip'] && ! $code_info['welcome_img'] && ! $code_info['engine_content_id']) {
                fdump_api([
                    'title' => '缺少相关数据不发送欢迎语', 'welcome_tip' => $code_info['welcome_tip'],'welcome_img' => $code_info['welcome_img'],'engine_content_id' => $code_info['engine_content_id'],
                ], 'qiYeWeiXin/sendWelcomeMsgErrLog', true);
                return false;
            }
            $welcome_tip = isset($code_info['welcome_tip']) && $code_info['welcome_tip'] ? $code_info['welcome_tip'] : '';
            if ($welcome_tip) {
                $data['text'] = [
                    'content' => $welcome_tip
                ];
            }
            ! $property_id && $property_id = isset($code_info['property_id']) ? intval($code_info['property_id']) : 0;
            ! $village_id && $village_id = isset($code_info['village_id']) ? intval($code_info['village_id']) : 0;

            if (isset($code_info['welcome_img']) && $code_info['welcome_img']) {
                $media_id = $this->uploadServiceMedia($code_info['welcome_img'],$property_id,$village_id);
                fdump_api([
                    'title' => '图片上传', 'media_id' => $media_id, 'welcome_img' => $code_info['welcome_img'], 'property_id' => $property_id, 'village_id' => $village_id
                ], 'qiYeWeiXin/sendWelcomeMsgErrLog',true);
                if ($media_id) {
                    $attachments[] = [
                        'msgtype' => 'image',
                        'image' => [
                            'media_id' => $media_id
                        ]
                    ];
                }
            }
            if (isset($code_info['engine_content_id']) && $code_info['engine_content_id']) {
                $where_engine_content = [
                    'id' => $code_info['engine_content_id']
                ];
                $dbVillageQywxEngineContent = new VillageQywxEngineContent();
                $info = $dbVillageQywxEngineContent->getFind($where_engine_content);
                if ($info && !is_array($info)) {
                    $info = $info->toArray();
                }
                $link = [];
                if (isset($info['title']) && isset($info['content']) && $info['title'] && $info['content']) {
                    $link['title'] = $info['title'];
                    $link['url'] = $info['content'];
                    if (isset($info['share_img']) && $info['share_img']) {
                        $link['picurl'] = replace_file_domain($info['share_img']);
                    }
                    if (isset($info['share_dsc']) && $info['share_dsc']) {
                        $link['desc'] = $info['share_dsc'];
                    }
                }
                if (!empty($link)) {
                    $attachments[] = [
                        'msgtype' => 'link',
                        'link' => $link
                    ];
                }
            }
        }
        if ($notHandle) {
            fdump_api(['title' => '类型有问题', 'params' => $params, 'contactData' => $contactData,], 'qiYeWeiXin/sendWelcomeMsgErrLog',true);
            return false;
        }
        if (!empty($attachments)) {
            $data['attachments'] = $attachments;
        }
        $access_token = $this->commonGetAccessToken($property_id);
        if ($access_token && (isset($data['text']) || isset($data['attachments']))) {
            $result = (new WorkWeiXinRequestService())->cgiBinExternalContactSendWelcomeMsg($data, $access_token);
            if (!is_array($result)) {
                $result = json_decode($result,true);
            }
            fdump_api(['title' => '发送欢迎语结果', 'data' => $data, 'result' => $result,], 'qiYeWeiXin/sendWelcomeMsgLog',true);
        }
        return true;
    }

    /**
     * 打标签整理，主要是新增标签.
     */
    public function codeMarkUser($params) {
        fdump_api(['title' => '打标签处理-开始', 'params' => $params, 'smicrotimes' => microtime(true)], 'qiYeWeiXin/codeMarkUserLog', true);
        $code_id = isset($params['code_id']) && $params['code_id'] ? $params['code_id'] : 0;
        $customer_id = isset($params['customer_id']) && $params['customer_id'] ? $params['customer_id'] : 0;
        $property_id = isset($params['property_id']) && $params['property_id'] ? $params['property_id'] : 0;
        $userid = isset($params['userid']) && $params['userid'] ? $params['userid'] : '';
        $external_userid = isset($params['external_userid']) && $params['external_userid'] ? $params['external_userid'] : '';
        if ((!$userid || !$external_userid || !$property_id) && $customer_id) {
            $dbHouseContactWayUser = new HouseContactWayUser();
            $contactData = $dbHouseContactWayUser->getOne(['customer_id' => $customer_id]);
            if ($contactData && !is_array($contactData)) {
                $contactData = $contactData->toArray();
            }
            $property_id = isset($contactData['property_id']) && $contactData['property_id'] ? $contactData['property_id'] : 0;
            $userid = isset($contactData['userid']) && $contactData['userid'] ? $contactData['userid'] : '';
            $external_userid = isset($contactData['external_userid']) && $contactData['external_userid'] ? $contactData['external_userid'] : '';
        }
        $code_mark_user = isset($params['code_mark_user']) && $params['code_mark_user'] ? $params['code_mark_user'] : 0;
        (!$code_mark_user && $property_id) && $this->updateCorpTagInfo($property_id);
        if (!$code_id || !$customer_id || !$property_id || !$userid || !$external_userid) {
            fdump_api(['title' => '标签未打上【缺少参数】', 'params' => $params], 'qiYeWeiXin/codeMarkUserErrLog', true);
            return false;
        }
        $dbVillageQywxCodeBindLabel = new VillageQywxCodeBindLabel();
        $whereVillageQywxCodeBindLabel = [
            'code_id' => $code_id
        ];
        $bindLabel = $dbVillageQywxCodeBindLabel->getSome($whereVillageQywxCodeBindLabel);
        if ($bindLabel && !is_array($bindLabel)) {
            $bindLabel = $bindLabel->toArray();
        }
        if (empty($bindLabel)) {
            fdump_api(['title' => '标签未打上【渠道活码未绑定标签】', 'params' => $params, 'code_id' => $code_id], 'qiYeWeiXin/codeMarkUserErrLog',true);
            return false;
        }
        $labelIds = array_values(array_unique(array_column($bindLabel, 'label_id')));
        $whereCodeLabel = [];
        $whereCodeLabel[] = ['status', '=', 1];
        $whereCodeLabel[] = ['label_id', 'in', $labelIds];
        $whereCodeLabel[] = ['property_id', '=', $property_id];
        $dbVillageQywxCodeLabel = new VillageQywxCodeLabel();
        $labelList = $dbVillageQywxCodeLabel->getList($whereCodeLabel, 'label_id, label_name, qy_tag_id, label_group_id', 0, 0);
        if ($labelList && !is_array($labelList)) {
            $labelList = $labelList->toArray();
        }
        if (empty($labelList)) {
            fdump_api(['title' => '标签未打上【对应标签不存在】', 'params' => $params, 'code_id' => $code_id, 'whereCodeLabel' => $whereCodeLabel], 'qiYeWeiXin/codeMarkUserErrLog',true);
            return false;
        }
        $labelGroupIds = array_values(array_unique(array_column($labelList, 'label_group_id')));
        
        $dbVillageQywxCodeLabelGroup = new VillageQywxCodeLabelGroup();
        $whereCodeLabelGroup = [];
        $whereCodeLabelGroup[] = ['label_group_id', 'in', $labelGroupIds];
        $labelGroupArr = $dbVillageQywxCodeLabelGroup->getColumn($whereCodeLabelGroup, 'label_group_id, label_group_name, qy_group_id', 'label_group_id');
        if ($labelGroupArr && !is_array($labelGroupArr)) {
            $labelGroupArr = $labelGroupArr->toArray();
        }
        $dbHouseCorpTag = new HouseCorpTag();
        $wherePropertyCorpTag = [];
        $wherePropertyCorpTag[] = ['property_id', '=', $property_id];
        $wherePropertyCorpTag[] = ['qy_deleted', '=', 0];
        $wherePropertyCorpTag[] = ['name', '<>', ''];
        $wherePropertyCorpTag[] = ['group_name', '<>', ''];
        $wherePropertyCorpTag[] = ['tag_id', '<>', ''];
        $corpTagList = $dbHouseCorpTag->getSome($wherePropertyCorpTag, 'tag_group_id, group_id, property_id, tag_id, name, group_name');
        if ($corpTagList && !is_array($corpTagList)) {
            $corpTagList = $corpTagList->toArray();
        }
        $tagGroupIds = array_values(array_unique(array_column($corpTagList, 'tag_group_id')));
        $dbHouseCorpTagGroup = new HouseCorpTagGroup();
        $whereTagGroup = [];
        $whereTagGroup[] = ['tag_group_id', 'in', $tagGroupIds];
        $tagGroupInfoArr = $dbHouseCorpTagGroup->getColumn($whereTagGroup, 'tag_group_id, group_id, group_name', 'tag_group_id');
        
        $corpTagArr = [];
        foreach ($corpTagList as $corpTagItem) {
            $tag_group_id = isset($corpTagItem['tag_group_id']) ? $corpTagItem['tag_group_id'] : 0;
            $tagGroupInfo = isset($tagGroupInfoArr[$tag_group_id]) ? $tagGroupInfoArr[$tag_group_id] : [];

            $group_name = isset($tagGroupInfo['group_name']) ? $tagGroupInfo['group_name'] : '';
            !$group_name && $group_name = isset($corpTagItem['group_name']) ? $corpTagItem['group_name'] : '';
            $name = isset($corpTagItem['name']) ? $corpTagItem['name'] : '';
            if ($group_name && $name) {
                $corpTagItem['group_name'] = $group_name;
                $corpTagArr[$group_name . '_' . $name] = $corpTagItem;
            }
        }

        $tag_group_arr = [];// 需要添加的标签集合
        $add_tag = [];
        foreach ($labelList as $label) {
            $label_id = isset($label['label_id']) && $label['label_id'] ? $label['label_id'] : 0;
            $label_group_id = isset($label['label_group_id']) && $label['label_group_id'] ? $label['label_group_id'] : 0;
            $label_name = isset($label['label_name']) && $label['label_name'] ? $label['label_name'] : '';
            $label_name = $this->checkStr($label_name);
            $labelGroup = isset($labelGroupArr[$label_group_id]) && $labelGroupArr[$label_group_id] ? $labelGroupArr[$label_group_id] : [];
            $label_group_name = isset($labelGroup['label_group_name']) && $labelGroup['label_group_name'] ? $labelGroup['label_group_name'] : '';
            $label_group_name = $this->checkStr($label_group_name);
            $corpTagKey = $label_group_name . '_' . $label_name;
            if (isset($corpTagArr[$corpTagKey]) && $corpTagArr[$corpTagKey]) {
                // 已有的标签直接添加
                $tag_id = $corpTagArr[$corpTagKey]['tag_id'];
                $add_tag[] = $tag_id;
                $whereLabel = ['label_id' => $label_id];
                $dataLabel = ['qy_tag_id' => $tag_id];
                $dbVillageQywxCodeLabel->updateThis($whereLabel, $dataLabel);
            } elseif (!empty($labelGroup)) {
                if (!isset($tag_group_arr[$label_group_id]) || !$tag_group_arr[$label_group_id]) {
                    $tag_group_arr[$label_group_id] = [
                        'label_group_id' => $label_group_id, 'group_name' => $label_group_name, 'name' => $label_name,
                        'order' => $label_group_id, 'tag' => [], 'tagNameArr' => [],
                    ];
                }
                $tag = $tag_group_arr[$label_group_id]['tag'];
                $tagNameArr = $tag_group_arr[$label_group_id]['tagNameArr'];
                $tag[] = [
                    'name' => $label_name,
                    'order' => $label_id,
                ];
                $tagNameArr[$label_name] = $label_id;
                $tag_group_arr[$label_group_id]['tag'] = $tag;
                $tag_group_arr[$label_group_id]['tagNameArr'] = $tagNameArr;
            } else {
                fdump_api(['title' => '标签未打上【对应父级标签不存在】', 'label' => $label, 'labelGroup' => $labelGroup], 'qiYeWeiXin/codeMarkUserErrLog',true);
            }
        }
        if (!empty($tag_group_arr)) {
            $tag_group_arr = array_values($tag_group_arr);
            $workWeiXinRequestService = new WorkWeiXinRequestService();
            $access_token = $this->commonGetAccessToken($property_id);
            if (! $access_token) {
                fdump_api(['title' => '标签未打上【对应帖token不存在】', 'params' => $params, 'code_id' => $code_id, 'tag_group_arr' => $tag_group_arr], 'qiYeWeiXin/codeMarkUserErrLog',true);
                return false;
            }
            foreach ($tag_group_arr as $tag_group) {
                $label_group_id = isset($tag_group['label_group_id']) ? $tag_group['label_group_id'] : 0;
                $add_corp_tag = [];
                $add_corp_tag['group_name'] = $tag_group['group_name'];
                $add_corp_tag['order'] = $tag_group['order'];
                $add_corp_tag['tag'] = $tag_group['tag'];
                $result = $workWeiXinRequestService->cgiBinExternalContactAddCorpTag($add_corp_tag, $access_token);
                if (!is_array($result)) {
                    $result = json_decode($result, true);
                }
                if (isset($result['tag_group']) && !empty($result['tag_group'])) {
                    $tagNameArr = isset($tag_group['tagNameArr']) ? $tag_group['tagNameArr'] : [];
                    $tagArr = $this->updateTagGroup($result['tag_group'], $property_id, $tagNameArr);
                    $tagIdsArr = isset($tagArr['tagIdsArr']) && $tagArr['tagIdsArr'] ? $tagArr['tagIdsArr'] : [];
                    if (!empty($tagIdsArr)) {
                        $add_tag = array_merge($tagIdsArr, $add_tag);
                    }
                    if (isset($result['tag_group']['group_id']) && $result['tag_group']['group_id'] && $label_group_id) {
                        $whereLabelGroup = [
                            'label_group_id' => $label_group_id
                        ];
                        $dataLabelGroup = [
                            'qy_group_id' => $result['tag_group']['group_id'],
                        ];
                        $dbVillageQywxCodeLabelGroup->updateThis($whereLabelGroup, $dataLabelGroup);
                    }
                }
            }
        }
        $queueData = [
            'customer_id' => $customer_id, 'code_id' => $code_id, 'userid' => $userid, 'external_userid' => $external_userid,
            'property_id' => $property_id, 'add_tag' => $add_tag, 'type' => 'mark_tag_contact_way_user',
            'jobType' => 'workWeiXinNew',
        ];
        try{
            $job_id = $this->traitCommonWorkWeiXin($queueData);
        }catch (\Exception $e){
            $job_id = '';
            fdump_api(['title' => '标签未打上', 'err' => $e->getMessage()], 'qiYeWeiXin/codeMarkUserErrLog',true);
        }
        fdump_api(['title' => '打标签处理-结束', 'params' => $params, 'smicrotimes' => microtime(true) ,'job_id' => $job_id], 'qiYeWeiXin/codeMarkUserLog',true);
        return true;
    }

    /**
     * 对应渠道活码扫码进入的进行关联标签
     */
    public function markTagContactWayUser($params) {
        fdump_api(['title' => '对应扫码人员打标签-开始', 'params' => $params, 'smicrotimes' => microtime(true)], 'qiYeWeiXin/markTagContactWayUserLog',true);
        $customer_id = isset($params['customer_id']) && $params['customer_id'] ? $params['customer_id'] : 0;
        $property_id = isset($params['property_id']) && $params['property_id'] ? $params['property_id'] : 0;
        $userid = isset($params['userid']) && $params['userid'] ? $params['userid'] : '';
        $external_userid = isset($params['external_userid']) && $params['external_userid'] ? $params['external_userid'] : '';
        $add_tag = isset($params['add_tag']) && $params['add_tag'] ? $params['add_tag'] : [];
        $remove_tag = isset($params['remove_tag']) && $params['remove_tag'] ? $params['remove_tag'] : [];
        $dbHouseContactWayUser = new HouseContactWayUser();
        $whereConTact = ['customer_id' => $customer_id];
        $contact_way_user_data = $dbHouseContactWayUser->getOne($whereConTact);
        if ($contact_way_user_data && !is_array($contact_way_user_data)) {
            $contact_way_user_data = $contact_way_user_data->toArray();
        }
        !$userid && $userid = isset($contact_way_user_data['UserID']) ? $contact_way_user_data['UserID'] : '';
        !$external_userid && $external_userid = isset($contact_way_user_data['ExternalUserID']) ? $contact_way_user_data['ExternalUserID'] : '';
        !$property_id && $property_id = isset($contact_way_user_data['property_id']) ? $contact_way_user_data['property_id'] : 0;
        if (!$userid || !$external_userid || !$property_id || (empty($add_tag) && empty($remove_tag))) {
            fdump_api([
                'title' => '标签未打上（参数缺失）', 'params' => $params, 'userid' => $userid, 'external_userid' => $external_userid, 'property_id' => $property_id, 'add_tag' => $add_tag, 'remove_tag' => $remove_tag
            ], 'qiYeWeiXin/markTagContactWayUserErrLog', true);
            return false;
        }
        $access_token = $this->commonGetAccessToken($property_id);
        if ($access_token) {
            $param = [];
            $param['userid'] = $userid;
            $param['external_userid'] = $external_userid;
            if ($add_tag) {
                $param['add_tag'] = $add_tag;
            }
            if ($remove_tag) {
                $param['remove_tag'] = $remove_tag;
            }
            $result = (new WorkWeiXinRequestService())->cgiBinExternalContactMarkTag($param, $access_token);
            fdump_api([
                'title' => '编辑客户企业标签', 'params' => $params, 'param' => $param, 'access_token' => $access_token, 'result' => $result
            ], 'qiYeWeiXin/markTagContactWayUserLog', true);
            if (!is_array($result)) {
                $result = json_decode($result, true);
            }
            if (!$result['errcode'] || intval($result['errcode']) === 0) {
                $set = ['tag_data' => serialize($add_tag), 'tag_time' => time()];
                $where = [
                    'customer_id' => $customer_id
                ];
                $dbHouseContactWayUser->where($where)->data($set)->save();
                return true;
            }
        }
        fdump_api(['title' => '对应扫码人员打标签-结束', 'params' => $params, 'smicrotimes' => microtime(true)], 'qiYeWeiXin/markTagContactWayUserLog',true);
        return true;
    }

    /**
     * 获取标签.
     */
    public function getCorpTagList($tagIds = [], $groupIds = [], $property_id = 0, $access_token = '') {
        if (! $access_token && $property_id) {
            $access_token = $this->commonGetAccessToken($property_id);
        }
        if ($access_token) {
            $param = [];
            if (!empty($tagIds)) {
                $param['tag_id'] = $tagIds;
            }
            if (!empty($groupIds)) {
                $param['group_id'] = $groupIds;
            }
            $result = (new WorkWeiXinRequestService())->cgiBinExternalContactGetCorpTagList($param, $access_token);
            if (!is_array($result)) {
                $result = json_decode($result,true);
            }
            fdump_api(['title' => '获取企业标签库-结果', 'param' => $param, 'access_token' => $access_token, 'result' => $result], 'qiYeWeiXin/getCorpTagListLog',true);
            return $result;
        }
        return [];
    }

    /**
     * 更新标签.
     */
    public function updateCorpTagInfo($property_id, $tagIds = [], $groupIds = []) {
        $result = $this->getCorpTagList($tagIds, $groupIds, $property_id);
        fdump_api([
            'title' => '更新标签', 'property_id' => $property_id, 'tagIds' => $tagIds, 'groupIds' => $groupIds, 'result' => $result,
        ], 'qiYeWeiXin/updateCorpTagInfoLog',true);
        if (isset($result['tag_group']) && ! empty($result['tag_group'])) {
            $this->updateTagGroup($result['tag_group'], $property_id);
            fdump_api(['title' => '更新企微标签相关信息', 'property_id' => $property_id,'tagIds' => $tagIds,'groupIds' => $groupIds, 'result' => $result], 'qiYeWeiXin/updateCorpTagInfoLog',true);
        }
        return true;
    }

    /**
     * 注意这里不会校验为空，请先行
     */
    public function updateTagGroup($tagGroupArr, $property_id, $tagNameArr = []) {
        $dbHouseCorpTagGroup = new HouseCorpTagGroup();
        $dbHouseCorpTag = new HouseCorpTag();
        $dbVillageQywxCodeLabel = new VillageQywxCodeLabel();
        $nowTime = time();
        $tagGroupIdsArr = [];// 整合平台记录标签组id
        $corpTagIdsArr = [];// 整合平台记录标签id
        $tagIdsArr = [];// 整合企微标签id
        $groupIdsArr = [];// 整合企微标签组id
        fdump_api(['title' => '更新企微标签相关信息', 'tagGroupArr' => $tagGroupArr, 'property_id' => $property_id, 'tagNameArr' => $tagNameArr], 'qiYeWeiXin/updateTagGroupLog',true);
        foreach ($tagGroupArr as $tag_group) {
            $deleted = isset($tag_group['deleted']) ? $tag_group['deleted'] : false;
            $group_id = isset($tag_group['group_id']) ? $tag_group['group_id'] : '';
            $groupIdsArr[] = $group_id;
            $group_name = isset($tag_group['group_name']) ? $tag_group['group_name'] : '';
            $create_time = isset($tag_group['create_time']) ? $tag_group['create_time'] : '';
            $order = isset($tag_group['order']) ? $tag_group['order'] : 0;
            $tag = isset($tag_group['tag']) ? $tag_group['tag'] : [];
            $tagGroup = [
                'qy_deleted' => $deleted ? 1 : 0, 'group_id' => $group_id, 'property_id' => $property_id,
                'group_name' => $group_name, 'qy_create_time' => $create_time, 'order' => $order, 'last_time' => $nowTime,
            ];
            $whereGroup = [
                'property_id' => $property_id,
            ];
            if ($group_name) {
                $whereGroup['group_name'] = $group_name;
            } else {
                $whereGroup['group_id'] = $group_id;
            }
            $unique_md5_arr = [
                'qy_deleted' => $deleted ? 1 : 0, 'group_id' => $group_id, 'property_id' => $property_id,
                'group_name' => $group_name, 'order' => $order,
            ];
            $unique_md5 = md5(json_encode([$unique_md5_arr], JSON_UNESCAPED_UNICODE));
            $tagGroupInfo = $dbHouseCorpTagGroup->getOne($whereGroup, 'tag_group_id, unique_md5', 'tag_group_id DESC');
            if (isset($tagGroupInfo['tag_group_id']) && isset($tagGroupInfo['unique_md5']) && $tagGroupInfo['unique_md5'] != $unique_md5) {
                // 存在变动 更新下
                $tagGroup['unique_md5'] = $unique_md5;
                $dbHouseCorpTagGroup->updateThis(['tag_group_id' => $tagGroupInfo['tag_group_id']], $tagGroup);
                $tag_group_id = $tagGroupInfo['tag_group_id'];
            } elseif(! isset($tagGroupInfo['tag_group_id'])) {
                // 不存在 添加记录下
                $tagGroup['unique_md5'] = $unique_md5;
                $tagGroup['add_time'] = $nowTime;
                $tag_group_id = $dbHouseCorpTagGroup->add($tagGroup);
            } else {
                $tag_group_id = $tagGroupInfo['tag_group_id'];
            }
            $tagGroupIdsArr[] = intval($tag_group_id);
            foreach ($tag as $tagItem) {
                $deleted = isset($tagItem['deleted']) ? $tagItem['deleted'] : false;
                $id = isset($tagItem['id']) ? $tagItem['id'] : '';
                $tagIdsArr[] = $id;
                $name = isset($tagItem['name']) ? $tagItem['name'] : '';
                $create_time = isset($tagItem['create_time']) ? $tagItem['create_time'] : '';
                $order = isset($tagItem['order']) ? $tagItem['order'] : 0;
                $corpTag = [
                    'qy_deleted' => $deleted ? 1 : 0, 'tag_id' => $id, 'tag_group_id' => $tag_group_id,
                    'group_id' => $group_id, 'property_id' => $property_id, 'name' => $name, 'create_time' => $create_time,
                    'order' => $order, 'last_time' => $nowTime, 'group_name' => $group_name,
                ];
                $whereTag = [
                    'property_id' => $property_id,
                ];
                if ($tag_group_id) {
                    $whereTag['tag_group_id'] = $tag_group_id;
                } else {
                    $whereTag['group_id'] = $group_id;
                }
                if ($name) {
                    $whereTag['name'] = $name;
                } else {
                    $whereTag['tag_id'] = $id;
                }
                $unique_md5_arr = [
                    'qy_deleted' => $deleted ? 1 : 0, 'tag_id' => $id, 'tag_group_id' => $tag_group_id,
                    'group_id' => $group_id, 'property_id' => $property_id, 'name' => $name, 'order' => $order,
                ];
                $unique_md5 = md5(json_encode([$unique_md5_arr], JSON_UNESCAPED_UNICODE));
                $tagInfo = $dbHouseCorpTag->getOne($whereTag, 'corp_tag_id, unique_md5', 'corp_tag_id DESC');
                if (isset($tagInfo['corp_tag_id']) && isset($tagInfo['unique_md5']) && $tagInfo['unique_md5'] != $unique_md5) {
                    // 存在变动 更新下
                    $corpTag['unique_md5'] = $unique_md5;
                    $dbHouseCorpTag->updateThis(['corp_tag_id' => $tagInfo['corp_tag_id']], $corpTag);
                    $corp_tag_id = $tagInfo['corp_tag_id'];
                } elseif(! isset($tagInfo['corp_tag_id'])) {
                    // 不存在 添加记录下
                    $corpTag['unique_md5'] = $unique_md5;
                    $corpTag['add_time'] = $nowTime;
                    $corp_tag_id = $dbHouseCorpTag->add($corpTag);
                } else {
                    $corp_tag_id = $tagInfo['corp_tag_id'];
                }
                $corpTagIdsArr[] = $corp_tag_id;
                if (! empty($tagNameArr)) {
                    $label_id = isset($tagNameArr[$name]) && $tagNameArr[$name] ? $tagNameArr[$name] : $order;
                    $whereLabel = [
                        'label_id' => $label_id
                    ];
                    $dataLabel = [
                        'qy_tag_id' => $id,
                    ];
                    $dbVillageQywxCodeLabel->updateThis($whereLabel, $dataLabel);
                }
            }
        }
        $tagGroupIdsArr = array_values($tagGroupIdsArr);
        $groupIdsArr = array_values($groupIdsArr);
        fdump_api(['title' => '更新企微标签相关信息', 'tagGroupIdsArr' => $tagGroupIdsArr, 'corpTagIdsArr' => $corpTagIdsArr, 'tagIdsArr' => $tagIdsArr, 'groupIdsArr' => $groupIdsArr], 'qiYeWeiXin/updateTagGroupLog',true);
        return ['tagGroupIdsArr' => $tagGroupIdsArr, 'corpTagIdsArr' => $corpTagIdsArr, 'tagIdsArr' => $tagIdsArr, 'groupIdsArr' => $groupIdsArr];
    }

    /**
     * 增加相关统计。
     */
    public function villageQywxDataStatisticsAdd($params) {
        $newHouseholds = isset($params['NewHouseholds']) && $params['NewHouseholds'] ? $params['NewHouseholds'] : 0;
        $customer_id =  isset($params['customer_id']) && $params['customer_id'] ? $params['customer_id'] : 0;
        $contactData =  isset($params['contactData']) && $params['contactData'] ? $params['contactData'] : [];
        $userID =  isset($params['userID']) && $params['userID'] ? $params['userID'] : '';
        $externalUserID =  isset($params['externalUserID']) && $params['externalUserID'] ? $params['externalUserID'] : '';
        $property_id =  isset($params['property_id']) && $params['property_id'] ? $params['property_id'] : 0;
        $village_id =  isset($params['village_id']) && $params['village_id'] ? $params['village_id'] : 0;
        $nowTime =  isset($params['timeStamp']) && $params['timeStamp'] ? $params['timeStamp'] : time();
        ! $property_id && $property_id = isset($contactData['property_id']) && $contactData['property_id'] ? $contactData['property_id'] : 0;
        ! $village_id && $village_id = isset($contactData['village_id']) && $contactData['village_id'] ? $contactData['village_id'] : 0;
        ! $userID && $userID = isset($contactData['UserID']) && $contactData['UserID'] ? $contactData['UserID'] : '';
        ! $externalUserID && $externalUserID = isset($contactData['ExternalUserID']) && $contactData['ExternalUserID'] ? $contactData['ExternalUserID'] : '';
        $dbHouseWorker = new HouseWorker();
        $whereWork = [];
        $whereWork[] = ['is_del', '=', 0];
        $whereWork[] = ['qy_id|qy_open_userid', '=', $userID];
        if ($property_id) {
            $whereWork[] = ['property_id', '=', $property_id];
        }
        $worker_info = $dbHouseWorker->getOne($whereWork);
        if ($worker_info && ! is_array($worker_info)) {
            $worker_info = $worker_info->toArray();
        }
        ! $village_id && $village_id = isset($worker_info['village_id']) && $worker_info['village_id'] ? $worker_info['village_id'] : 0;
        fdump_api([
            'title' => '统计-增加', 'params' => $params, 'worker_info' => $worker_info, 'whereWork' => $whereWork,
        ], 'qiYeWeiXin/villageQywxDataStatisticsAddLog',true);
        if($worker_info){
            $year = date('Y', $nowTime);
            $month = date('Y-m', $nowTime);
            $day = date('Y-m-d', $nowTime);
            $dbVillageQywxDataCenter = new VillageQywxDataCenter();
            $where = [
                'wid' => $worker_info['wid'], 'Y' => $year, 'M' => $month, 'D' => $day
            ];
            $data_center_info = $dbVillageQywxDataCenter->getOne($where);
            if ($data_center_info && !is_array($data_center_info)) {
                $data_center_info = $data_center_info->toArray();
            }
            $dbVillageQywxActionLog = new VillageQywxActionLog();
            if ($data_center_info) {
                if (intval($newHouseholds) === 1) {
                    $dbVillageQywxDataCenter->setInc($where, 'new_house_holds_num');
                    $data = [
                        'center_id' => isset($data_center_info['id']) ? intval($data_center_info['id']) : 0,
                        'add_type' => isset($data_center_info['add_type']) ? intval($data_center_info['add_type']) : 0,
                        'wid' => isset($data_center_info['wid']) ? intval($data_center_info['wid']) : 0,
                        'user_id' => $userID, 'external_user_id' => $externalUserID, 'property_id' => $property_id,
                        'village_id' => $village_id, 'create_time' => $nowTime, 'action_name' => 'NewHouseholds',
                    ];
                    $dbVillageQywxActionLog->addOne($data);
                    fdump_api([
                        'title' => '统计-增加', 'params' => $params, 'data' => $data,
                    ], 'qiYeWeiXin/villageQywxDataStatisticsAddLog',true);
                } else {
                    $dbVillageQywxDataCenter->setInc($where, 'new_non_residents_num');
                    $dbVillageQywxDataCenter->setInc($where, 'apply_friends_num');
                    $addAllData = [];
                    $data = [
                        'center_id' => isset($data_center_info['id']) ? intval($data_center_info['id']) : 0,
                        'add_type' => isset($data_center_info['add_type']) ? intval($data_center_info['add_type']) : 0,
                        'wid' => isset($data_center_info['wid']) ? intval($data_center_info['wid']) : 0,
                        'user_id' => $userID, 'external_user_id' => $externalUserID, 'property_id' => $property_id,
                        'village_id' => $village_id, 'create_time' => $nowTime,
                    ];
                    $data['action_name'] = 'NewNonResidents';
                    $addAllData[] = $data;
                    $data['action_name'] = 'ApplyFriends';
                    $addAllData[] = $data;
                    $dbVillageQywxActionLog->addAll($addAllData);
                    fdump_api([
                        'title' => '统计-增加', 'params' => $params, 'addAllData' => $addAllData,
                    ], 'qiYeWeiXin/villageQywxDataStatisticsAddLog',true);
                }
            } else {
                if (intval($newHouseholds) === 1) {
                    $dataCenter = [
                        'add_type' => isset($worker_info['village_id']) && $worker_info['village_id'] ? 1 : 2,
                        'wid' => $worker_info['wid'], 'user_id' => $userID, 'Y' => $year, 'M' => $month, 'D' => $day,
                        'new_house_holds_num' => 1, 'property_id' => $property_id, 'village_id' => $village_id,
                    ];
                    $center_id = $dbVillageQywxDataCenter->addOne($dataCenter);
                    $data = [
                        'center_id' => isset($data_center_info['id']) ? intval($data_center_info['id']) : 0,
                        'add_type' => isset($data_center_info['add_type']) ? intval($data_center_info['add_type']) : 0,
                        'wid' => isset($data_center_info['wid']) ? intval($data_center_info['wid']) : 0,
                        'user_id' => $userID, 'external_user_id' => $externalUserID, 'property_id' => $property_id,
                        'village_id' => $village_id, 'create_time' => $nowTime, 'action_name' => 'NewHouseholds',
                    ];
                    $dbVillageQywxActionLog->addOne($data);
                    fdump_api([
                        'title' => '统计-增加', 'params' => $params, 'dataCenter' => $dataCenter, 'center_id' => $center_id, 'data' => $data,
                    ], 'qiYeWeiXin/villageQywxDataStatisticsAddLog',true);
                } else {
                    $dataCenter = [
                        'add_type' => isset($worker_info['village_id']) && $worker_info['village_id'] ? 1 : 2,
                        'wid' => $worker_info['wid'], 'user_id' => $userID, 'Y' => $year, 'M' => $month, 'D' => $day,
                        'new_non_residents_num' => 1, 'apply_friends_num' => 1, 'property_id' => $property_id, 'village_id' => $village_id,
                    ];
                    $center_id = $dbVillageQywxDataCenter->addOne($dataCenter);

                    $addAllData = [];
                    $data = [
                        'center_id' => $center_id, 'add_type' => $dataCenter['add_type'], 'wid' => $dataCenter['wid'],
                        'user_id' => $userID, 'external_user_id' => $externalUserID, 'property_id' => $property_id,
                        'village_id' => $village_id, 'create_time' => $nowTime,
                    ];
                    $data['action_name'] = 'NewNonResidents';
                    $addAllData[] = $data;
                    $data['action_name'] = 'ApplyFriends';
                    $addAllData[] = $data;
                    $dbVillageQywxActionLog->addAll($addAllData);
                    fdump_api([
                        'title' => '统计-增加', 'params' => $params, 'dataCenter' => $dataCenter, 'addAllData' => $addAllData,
                    ], 'qiYeWeiXin/villageQywxDataStatisticsAddLog',true);
                }
            }
        } else {
            fdump_api([
                'title' => '统计-增加失败 缺少对应通过人员', 'params' => $params, 'whereWork' => $whereWork,
            ], 'qiYeWeiXin/villageQywxDataStatisticsAddErrLog',true);
        }
        return true;
    }

    /**
     * 减少相关统计。
     */
    public function villageQywxDataStatisticsReduce($params) {
        $userID =  isset($params['userID']) && $params['userID'] ? $params['userID'] : '';
        $externalUserID =  isset($params['externalUserID']) && $params['externalUserID'] ? $params['externalUserID'] : '';
        $property_id =  isset($params['property_id']) && $params['property_id'] ? $params['property_id'] : 0;
        $village_id =  isset($params['village_id']) && $params['village_id'] ? $params['village_id'] : 0;
        $nowTime =  isset($params['timeStamp']) && $params['timeStamp'] ? $params['timeStamp'] : time();
        $authCorpId = isset($params['authCorpId']) && $params['authCorpId'] ? $params['authCorpId'] : '';
        $suiteId = isset($params['suiteId']) && $params['suiteId'] ? $params['suiteId'] : '';
        $source = isset($params['source']) && $params['source'] ? $params['source'] : '';
        $infoType = isset($params['infoType']) && $params['infoType'] ? $params['infoType'] : '';

        $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        if (! $property_id && $suiteId && $authCorpId) {
            $whereWxBind = [
                'bind_type' => 0, 'corpid' => $authCorpId, 'wx_provider_suiteid' => $suiteId,
            ];
            $info = $dbHouseEnterpriseWxBind->getOne($whereWxBind, 'bind_id');
            $property_id = isset($info['bind_id']) ? intval($info['bind_id']) : 0;
        }
        $dbHouseWorker = new HouseWorker();
        $whereWork = [];
        $whereWork[] = ['is_del', '=', 0];
        $whereWork[] = ['qy_id|qy_open_userid', '=', $userID];
        if ($property_id) {
            $whereWork[] = ['property_id', '=', $property_id];
        }
        $worker_info = $dbHouseWorker->getOne($whereWork);
        if ($worker_info && ! is_array($worker_info)) {
            $worker_info = $worker_info->toArray();
        }
        fdump_api([
            'title' => '统计-减少', 'params' => $params, 'worker_info' => $worker_info, 'whereWork' => $whereWork,
        ], 'qiYeWeiXin/villageQywxDataStatisticsReduce',true);
        ! $village_id && $village_id = isset($worker_info['village_id']) && $worker_info['village_id'] ? $worker_info['village_id'] : 0;
        $dbHouseContactWayUser = new HouseContactWayUser();
        $whereContact = [];
        $whereContact[] = ['corpid', '=', $authCorpId];
        $whereContact[] = ['ExternalUserID', '=', $externalUserID];
        $whereContact[] = ['UserID', '=', $userID];
        $whereContact[] = ['status', '<>', 2];
        $wayUserInfo = $dbHouseContactWayUser->getOne($whereContact, 'customer_id');
        if (isset($wayUserInfo['customer_id']) && $wayUserInfo['customer_id']) {
            $set = [
                'status' => 2, 'last_time' => $nowTime,
            ];
            $dbHouseContactWayUser->updateWxInfo(['customer_id' => $wayUserInfo['customer_id']], $set);
        }
        if($worker_info){
            $year = date('Y', $nowTime);
            $month = date('Y-m', $nowTime);
            $day = date('Y-m-d', $nowTime);
            $dbVillageQywxDataCenter = new VillageQywxDataCenter();
            $where = [
                'wid' => $worker_info['wid'], 'Y' => $year, 'M' => $month, 'D' => $day
            ];
            $data_center_info = $dbVillageQywxDataCenter->getOne($where);
            if ($data_center_info && !is_array($data_center_info)) {
                $data_center_info = $data_center_info->toArray();
            }
            if ($data_center_info) {
                $dbVillageQywxDataCenter->setInc($where, 'block_num');

                $dbVillageQywxActionLog = new VillageQywxActionLog();
                $data = [
                    'center_id' => isset($data_center_info['id']) ? intval($data_center_info['id']) : 0,
                    'add_type' => isset($data_center_info['add_type']) ? intval($data_center_info['add_type']) : 0,
                    'wid' => isset($data_center_info['wid']) ? intval($data_center_info['wid']) : 0,
                    'user_id' => $userID, 'external_user_id' => $externalUserID, 'property_id' => $property_id,
                    'village_id' => $village_id, 'create_time' => $nowTime, 'action_name' => 'Block',
                ];
                $dbVillageQywxActionLog->addOne($data);
                fdump_api([
                    'title' => '统计-减少', 'params' => $params, 'data' => $data,
                ], 'qiYeWeiXin/villageQywxDataStatisticsReduce',true);
            } else {
                $dataCenter = [
                    'add_type' => isset($worker_info['village_id']) && $worker_info['village_id'] ? 1 : 2,
                    'wid' => $worker_info['wid'], 'user_id' => $userID, 'Y' => $year, 'M' => $month, 'D' => $day,
                    'block_num' => 1, 'property_id' => $property_id, 'village_id' => $village_id,
                ];
                $center_id = $dbVillageQywxDataCenter->addOne($dataCenter);

                $dbVillageQywxActionLog = new VillageQywxActionLog();
                $data = [
                    'center_id' => $center_id, 'add_type' => $dataCenter['add_type'], 'wid' => $dataCenter['wid'],
                    'user_id' => $userID, 'external_user_id' => $externalUserID, 'property_id' => $property_id,
                    'village_id' => $village_id, 'create_time' => $nowTime, 'action_name' => 'Block',
                ];
                $dbVillageQywxActionLog->addOne($data);
                fdump_api([
                    'title' => '统计-减少', 'params' => $params, 'dataCenter' => $dataCenter, 'data' => $data,
                ], 'qiYeWeiXin/villageQywxDataStatisticsReduce', true);
            }
        } else {
            fdump_api([
                'title' => '统计-减少失败 缺少对应通过人员', 'params' => $params, 'whereWork' => $whereWork,
            ], 'qiYeWeiXin/villageQywxDataStatisticsReduceErrLog',true);
        }
    }

    /**
     * 服务商上传附件资源[第三方应用开发>服务端API>素材管理>上传临时素材].
     */
    public function uploadServiceMedia($fileUrl, $property_id, $village_id, $basename = '', $type = 'image', $is_replace = false) {
        $fileUrl = replace_file_domain($fileUrl);
        $file_handle = new FileHandle();
        $site_url = cfg('site_url');
        if($file_handle->check_open_oss()) {
            $file_handle->download($fileUrl);
            $file_path = $file_handle->get_path($fileUrl);
            $fileUrl = app()->getRootPath() . '../'.$file_path;
        } else{
            $fileUrl = app()->getRootPath() . '../'.str_replace($site_url,'',$fileUrl);
        }
        $access_token = $this->commonGetAccessToken($property_id);
        if ($access_token && $fileUrl) {
            $file  = $fileUrl;
            $fileInfo = pathinfo($file);
            if (!$basename  &&  $fileInfo['basename']) {
                $basename = $fileInfo['basename'];
            }
            if ($fileInfo['extension']) {
                $extension = $fileInfo['extension'];
            } else {
                $extension = '';
            }
            if (class_exists('\CURLFile')) {
                $data['media'] = new \CURLFile(realpath($file),$extension,$basename);
            } else {
                $data['media'] = '@'.realpath($file);
            }
            fdump_api([
                'title' => '上传附件资源', 'data' => $data,'access_token' => $access_token,
            ], 'qiYeWeiXin/uploadServiceMediaLog', true);
            $result = (new WorkWeiXinRequestService())->cgiBinMediaUpload($data, $access_token, $type);
            fdump_api([
                'title' => '上传附件资源-结果', 'result' => $result,
            ], 'qiYeWeiXin/uploadServiceMediaLog', true);
            $media_id = isset($result['media_id']) && $result['media_id'] ? $result['media_id'] : '';
            return $media_id;
        }
        fdump_api([
            'title' => '上传附件资源-结果', 'fileUrl' => $fileUrl,
        ], 'qiYeWeiXin/uploadServiceMediaErrLog', true);
        return false;
    }

    /**
     * 楼栋管家扫码进入-下发队列进行打标签操作
     */
    public function markHousekeeperTagJob($pigcms_id, $customer_id, $housekeeper_id = 0) {
        if (! $pigcms_id || ! $customer_id) {
            fdump_api(['title' => '标签未打上(参数缺失)'], 'qiYeWeiXin/markHousekeeperTagJobErrLog',true);
            return false;
        }
        $queueData = [
            'pigcms_id' => $pigcms_id, 'customer_id' => $customer_id, 'housekeeper_id' => $housekeeper_id,
            'type' => 'mark_housekeeper_contact_way_tag', 'jobType' => 'workWeiXinNew',
        ];
        try{
            $job_id = $this->traitCommonWorkWeiXin($queueData);
        }catch (\Exception $e){
            $job_id = '';
            fdump_api(['title' => '标签未打上', 'err' => $e->getMessage()], 'qiYeWeiXin/markHousekeeperTagJobErrLog',true);
        }
        return true;
    }

    /**
     * 楼栋管家扫码进入后登录绑定后打标签.
     */
    public function markHousekeeperContactWayTagHandle($params) {
        fdump_api(['title' => '楼栋管家打标签-开始', 'params' => $params], 'qiYeWeiXin/markHousekeeperContactWayTagHandleLog',true);
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        $customer_id = isset($params['customer_id']) && $params['customer_id'] ? intval($params['customer_id']) : 0;
        $housekeeper_id = isset($params['housekeeper_id']) && $params['housekeeper_id'] ? intval($params['housekeeper_id']) : 0;
        if (! $pigcms_id || ! $customer_id) {
            fdump_api(['title' => '标签未打上(参数缺失)'], 'qiYeWeiXin/markHousekeeperContactWayTagHandleErrLog',true);
            return false;
        }
        $nowTime = time();
        $dbHouseContactWayUser = new HouseContactWayUser();
        $whereContact = [
            'customer_id' => $customer_id,
        ];
        $contact_way_user = $dbHouseContactWayUser->getOne($whereContact);
        if ($contact_way_user && !is_array($contact_way_user)) {
            $contact_way_user = $contact_way_user->toArray();
        }
        $property_id = isset($contact_way_user['property_id']) && $contact_way_user['property_id'] ? intval($contact_way_user['property_id']) : 0;
        !$housekeeper_id && $housekeeper_id = isset($contact_way_user['housekeeper_id']) && $contact_way_user['housekeeper_id'] ? intval($contact_way_user['housekeeper_id']) : 0;
        $dbHouseServicesHousekeeper = new HouseServicesHousekeeper();
        $whereHousekeeper = ['housekeeper_id' => $housekeeper_id];
        $housekeeper_info = $dbHouseServicesHousekeeper->getOne($whereHousekeeper);
        if ($housekeeper_info && !is_array($housekeeper_info)) {
            $housekeeper_info = $housekeeper_info->toArray();
        }
        !$property_id && $property_id = isset($housekeeper_info['property_id']) && $housekeeper_info['property_id'] ? intval($housekeeper_info['property_id']) : 0;
        if ($property_id) {
            $this->updateCorpTagInfo($property_id);
        }
        $dbHouseVillageDataConfig = new HouseVillageDataConfig();
        $whereDataConfig = ['property_id' => $property_id, 'is_syn' => 1];
        $house_village_data_config = $dbHouseVillageDataConfig->getList($whereDataConfig);
        if ($house_village_data_config && ! is_array($house_village_data_config)) {
            $house_village_data_config = $house_village_data_config->toArray();
        }
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $whereUserBind = ['pigcms_id' => $pigcms_id];
        $user_bind = $dbHouseVillageUserBind->getOne($whereUserBind, 'pigcms_id, village_id, single_id, floor_id, layer_id, vacancy_id, type, authentication_field');
        if ($user_bind && ! is_array($user_bind)) {
            $user_bind = $user_bind->toArray();
        }
        $authentication_field = isset($user_bind['authentication_field']) ? $user_bind['authentication_field'] : '';
        $authentication_field = $authentication_field ? unserialize($authentication_field) : [];
        $add_tag = [];
        $access_token = $this->commonGetAccessToken($property_id);
        if ($house_village_data_config && $authentication_field) {
            $dbHouseCorpTag = new HouseCorpTag();
            $wherePropertyCorpTag = [];
            $wherePropertyCorpTag[] = ['property_id', '=', $property_id];
            $wherePropertyCorpTag[] = ['qy_deleted', '=', 0];
            $wherePropertyCorpTag[] = ['name', '<>', ''];
            $wherePropertyCorpTag[] = ['group_name', '<>', ''];
            $wherePropertyCorpTag[] = ['tag_id', '<>', ''];
            $corpTagList = $dbHouseCorpTag->getSome($wherePropertyCorpTag, 'tag_group_id, group_id, property_id, tag_id, name, group_name');
            if ($corpTagList && !is_array($corpTagList)) {
                $corpTagList = $corpTagList->toArray();
            }
            $tagGroupIds = array_values(array_unique(array_column($corpTagList, 'tag_group_id')));
            $dbHouseCorpTagGroup = new HouseCorpTagGroup();
            $whereTagGroup = [];
            $whereTagGroup[] = ['tag_group_id', 'in', $tagGroupIds];
            $tagGroupInfoArr = $dbHouseCorpTagGroup->getColumn($whereTagGroup, 'tag_group_id, group_id, group_name', 'tag_group_id');

            fdump_api(['title' => '已有标签', 'corpTagList' => $corpTagList, 'tagGroupInfoArr' => $tagGroupInfoArr], 'qiYeWeiXin/markHousekeeperContactWayTagHandleLog',true);
            $corpTagArr = [];
            foreach ($corpTagList as $corpTagItem) {
                $tag_group_id = isset($corpTagItem['tag_group_id']) ? $corpTagItem['tag_group_id'] : 0;
                $tagGroupInfo = isset($tagGroupInfoArr[$tag_group_id]) ? $tagGroupInfoArr[$tag_group_id] : [];

                $group_name = isset($tagGroupInfo['group_name']) ? $tagGroupInfo['group_name'] : '';
                !$group_name && $group_name = isset($corpTagItem['group_name']) ? $corpTagItem['group_name'] : '';
                $name = isset($corpTagItem['name']) ? $corpTagItem['name'] : '';
                if ($group_name && $name) {
                    $corpTagItem['group_name'] = $group_name;
                    $corpTagArr[$group_name . '_' . $name] = $corpTagItem;
                }
            }
            fdump_api(['title' => '整理标签', 'corpTagArr' => $corpTagArr], 'qiYeWeiXin/markHousekeeperContactWayTagHandleLog',true);
            $tag_group_arr = [];
            foreach ($house_village_data_config as $dataConfig) {
                $acid = isset($dataConfig['acid']) && $dataConfig['acid'] ? $dataConfig['acid'] : 0;
                $key = isset($dataConfig['key']) && $dataConfig['key'] ? $dataConfig['key'] : '';
                $group_name = isset($dataConfig['title']) && $dataConfig['title'] ? $dataConfig['title'] : '';
                $labelInfo = isset($authentication_field[$key]) && $authentication_field[$key] ? $authentication_field[$key] : [];
                $name = isset($labelInfo['value']) && $labelInfo['value'] ? trim($labelInfo['value']) : '';
                $group_name = $this->checkStr($group_name);
                $name = $this->checkStr($name);
                $corpTagKey = $group_name . '_' . $name;
                if (isset($corpTagArr[$corpTagKey]) && $corpTagArr[$corpTagKey]) {
                    // 已有的标签直接添加
                    $tag_id = $corpTagArr[$corpTagKey]['tag_id'];
                    $add_tag[] = $tag_id;
                } elseif ($group_name && $name) {
                    if (! isset($tag_group_arr[$acid]) || ! $tag_group_arr[$acid]) {
                        $tag_group_arr[$acid] = [
                            'acid' => $acid, 'group_name' => $group_name, 'name' => $name,
                            'order' => $acid, 'tag' => [],
                        ];
                    }
                    $tag = $tag_group_arr[$acid]['tag'];
                    $tag[] = [
                        'name' => $name,
                        'order' => 0,
                    ];
                    $tag_group_arr[$acid]['tag'] = $tag;
                } else {
                    fdump_api([
                        'title' => '标签未打上【对应父级标签不存在】', 'dataConfig' => $dataConfig, 'labelInfo' => $labelInfo, 'group_name' => $group_name, 'name' => $name
                    ], 'qiYeWeiXin/markHousekeeperContactWayTagHandleErrLog',true);
                }
            }
            fdump_api(['title' => '需要新增标签', 'tag_group_arr' => $tag_group_arr], 'qiYeWeiXin/markHousekeeperContactWayTagHandleLog',true);
            $workWeiXinRequestService = new WorkWeiXinRequestService();
            if (!empty($tag_group_arr)) {
                $tag_group_arr = array_values($tag_group_arr);
                if (! $access_token) {
                    fdump_api([
                        'title' => '标签未打上【对应token不存在】', 'params' => $params, 'customer_id' => $customer_id, 'tag_group_arr' => $tag_group_arr
                    ], 'qiYeWeiXin/markHousekeeperContactWayTagHandleErrLog',true);
                    return false;
                }
                foreach ($tag_group_arr as $tag_group) {
                    $acid = isset($tag_group['acid']) ? $tag_group['acid'] : 0;
                    $add_corp_tag = [];
                    $add_corp_tag['group_name'] = $tag_group['group_name'];
                    $add_corp_tag['order'] = $tag_group['order'];
                    $add_corp_tag['tag'] = $tag_group['tag'];
                    $result = $workWeiXinRequestService->cgiBinExternalContactAddCorpTag($add_corp_tag, $access_token);
                    if (!is_array($result)) {
                        $result = json_decode($result,true);
                    }
                    fdump_api(['title' => '新增标签', 'add_corp_tag' => $add_corp_tag, 'access_token' => $access_token, 'result' => $result], 'qiYeWeiXin/markHousekeeperContactWayTagHandleLog',true);
                    if (isset($result['tag_group']) && ! empty($result['tag_group'])) {
                        $tagArr = $this->updateTagGroup($result['tag_group'], $property_id);
                        $tagIdsArr = isset($tagArr['tagIdsArr']) && $tagArr['tagIdsArr'] ? $tagArr['tagIdsArr'] : [];
                        if (! empty($tagIdsArr)) {
                            $add_tag = array_merge($tagIdsArr, $add_tag);
                        }
                        if (isset($result['tag_group']['group_id']) && $result['tag_group']['group_id'] && $acid) {
                            $whereDataConfig = [
                                'acid' => $acid
                            ];
                            $dataConfigs = [
                                'corp_tag_group_id' => $result['tag_group']['group_id'],
                                'syn_time' => $nowTime, 'corp_tag_status' => 1,
                            ];
                            $dbHouseVillageDataConfig->updateThis($whereDataConfig, $dataConfigs);
                        }
                    }
                }
            }

            $houseTagArr = $this->getHouseTagArr();
            if ($houseTagArr) {
                $house_tag_group_arr = [];
                foreach ($houseTagArr as $houseTag) {
                    $group_name = $houseTag['name'];
                    $order = $houseTag['order'];
                    $tagName = $this->getHouseTagNameArr($houseTag, $user_bind);
                    if (! $tagName) {
                        fdump_api(['title' => '标签未打上(标签名称不存在)', 'houseTag' => $houseTag, 'user_bind' => $user_bind], 'qiYeWeiXin/markHousekeeperContactWayTagHandleErrLog',true);
                        continue;
                    }
                    $group_name = $this->checkStr($group_name);
                    $name = $this->checkStr($tagName);
                    $corpTagKey = $group_name . '_' . $name;
                    if (isset($corpTagArr[$corpTagKey]) && $corpTagArr[$corpTagKey]) {
                        // 已有的标签直接添加
                        $tag_id = $corpTagArr[$corpTagKey]['tag_id'];
                        $add_tag[] = $tag_id;
                    } elseif ($group_name && $name) {
                        if (! isset($house_tag_group_arr[$group_name]) || ! $house_tag_group_arr[$group_name]) {
                            $tag_group_arr[$group_name] = [
                                'group_name' => $group_name,
                                'name' => $name,
                                'order' => $order,
                                'tag' => [],
                            ];
                        }
                        $tag = $house_tag_group_arr[$group_name]['tag'];
                        $tag[] = [
                            'name' => $name,
                            'order' => 0,
                        ];
                        $house_tag_group_arr[$group_name]['tag'] = $tag;
                    }
                }
                foreach ($house_tag_group_arr as $house_tag_group) {
                    $add_corp_tag = [];
                    $add_corp_tag['group_name'] = $house_tag_group['group_name'];
                    $add_corp_tag['order'] = $house_tag_group['order'];
                    $add_corp_tag['tag'] = $house_tag_group['tag'];
                    $result = $workWeiXinRequestService->cgiBinExternalContactAddCorpTag($add_corp_tag, $access_token);
                    if (!is_array($result)) {
                        $result = json_decode($result,true);
                    }
                    fdump_api(['title' => '新增标签', 'add_corp_tag' => $add_corp_tag, 'access_token' => $access_token, 'result' => $result], 'qiYeWeiXin/markHousekeeperContactWayTagHandleLog',true);
                    if (isset($result['tag_group']) && ! empty($result['tag_group'])) {
                        $tagArr = $this->updateTagGroup($result['tag_group'], $property_id);
                        $tagIdsArr = isset($tagArr['tagIdsArr']) && $tagArr['tagIdsArr'] ? $tagArr['tagIdsArr'] : [];
                        if (! empty($tagIdsArr)) {
                            $add_tag = array_merge($tagIdsArr, $add_tag);
                        }
                    }
                }
            }

            // todo 走队列给用户关联标签
            $queueData = [
                'customer_id' => $customer_id, 'userid' => $contact_way_user['UserID'], 'external_userid' => $contact_way_user['ExternalUserID'],
                'property_id' => $property_id, 'add_tag' => $add_tag, 'type' => 'mark_tag_contact_way_user', 'jobType' => 'workWeiXinNew',
            ];
            try{
                $job_id = $this->traitCommonWorkWeiXin($queueData);
            }catch (\Exception $e){
                $job_id = '';
                fdump_api(['title' => '标签未打上', 'err' => $e->getMessage()], 'qiYeWeiXin/markHousekeeperContactWayTagHandleErrLog',true);
            }
            // todo 走数据统计添加
            $queueData = [
                'customer_id' => $customer_id, 'contactData' => $contact_way_user, 'userID' => $contact_way_user['UserID'],
                'externalUserID' => $contact_way_user['ExternalUserID'], 'property_id' => $property_id, 'NewHouseholds' => 1,
                'village_id' => $contact_way_user['village_id'], 'timeStamp' => $nowTime, 'type' => 'village_qywx_data_statistics_add',
                'jobType' => 'workWeiXinNew',
            ];
            try{
                $job_id = $this->traitCommonWorkWeiXin($queueData, 60);
                fdump_api([
                    'title' => '统计', 'job_id' => $job_id,
                ], 'qiYeWeiXin/markHousekeeperContactWayTagHandleLog',true);
            }catch (\Exception $e){
                fdump_api([
                    'title' => '统计失败', 'err' => $e->getMessage(),
                ], 'qiYeWeiXin/markHousekeeperContactWayTagHandleErrLog',true);
            }
        } else {
            fdump_api([
                'title' => '标签未打上(缺少需要打标签数据)', 'whereDataConfig' => $whereDataConfig, 'house_village_data_config' => $house_village_data_config, 'user_bind' => $user_bind
            ], 'qiYeWeiXin/markHousekeeperContactWayTagHandleErrLog',true);
        }
        fdump_api(['title' => '楼栋管家打标签-结束', 'params' => $params], 'qiYeWeiXin/markHousekeeperContactWayTagHandleLog',true);
        return false;
    }

    public function checkUserPassJob($pigcms_id, $uid = 0, $village_id = 0, $property_id = 0) {
        $queueData = [
            'pigcms_id' => $pigcms_id, 'uid' => $uid, 'village_id' => $village_id, 'property_id' => $property_id,
            'timeStamp' => time(), 'type' => 'check_user_pass', 'jobType' => 'workWeiXinNew',
        ];
        try{
            $job_id = $this->traitCommonWorkWeiXin($queueData, 60);
            fdump_api([
                'title' => '住户审核通过自动检测是否可以关联企微外部联系人-队列', 'job_id' => $job_id,
            ], 'qiYeWeiXin/checkUserPassJobLog',true);
        }catch (\Exception $e){
            $job_id = '';
            fdump_api([
                'title' => '住户审核通过自动检测是否可以关联企微外部联系人-失败', 'err' => $e->getMessage(),
            ], 'qiYeWeiXin/checkUserPassJobErrLog',true);
        }
        return $job_id;
    }

    public function checkUserPass($params) {
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $property_id = isset($params['property_id']) && $params['property_id'] ? intval($params['property_id']) : 0;
        $nowTime = isset($params['timeStamp']) && $params['timeStamp'] ? intval($params['timeStamp']) : time();

        $userInfo = (new HouseVillageUserBind())->getOne(['pigcms_id' => $pigcms_id], 'pigcms_id, uid, status, village_id, single_id, floor_id, vacancy_id');
        !$uid && $uid = isset($userInfo['uid']) ? $userInfo['uid'] : 0;
        !$village_id && $village_id = isset($userInfo['village_id']) ? $userInfo['village_id'] : 0;

        $village_info = (new HouseVillage())->getOne($village_id, 'village_id, property_id');
        !$property_id && $property_id = isset($village_info['property_id']) ? $village_info['property_id'] : 0;

        if (!$uid || !$village_id || !$property_id) {
            fdump_api([
                'title' => '住户审核通过自动检测是否可以关联企微外部联系人-缺少必要参数', 'params' => $params,
                'uid' => $uid, 'village_id' => $village_id, 'property_id' => $property_id,
            ], 'qiYeWeiXin/checkUserPassErrLog', true);
            return false;
        }
        $user = (new User())->getOne(['uid' => $uid], 'uid, openid, union_id');
        $unionid = isset($user['union_id']) && $user['union_id'] ? $user['union_id'] : '';
        $openid = isset($user['openid']) && $user['openid'] ? $user['openid'] : '';
        $dbHouseContactWayUser = new HouseContactWayUser();
        $toMark = false;
        if ($unionid && $openid) {
            $result = $this->unionIdToExternalUserID3rd($unionid, $openid, $property_id);
            $external_userid_info = isset($result['external_userid_info']) && $result['external_userid_info'] ? $result['external_userid_info'] : [];
            $suite_corpid = cfg('enterprise_wx_corpid');
            $suite_id = cfg('enterprise_wx_provider_suiteid');
            $dbWorkWeixinUser = new WorkWeixinUser();
            foreach ($external_userid_info as $item) {
                $corpid = isset($item['corpid']) && $item['corpid'] ? $item['corpid'] : '';
                $external_userid = isset($item['external_userid']) && $item['external_userid'] ? $item['external_userid'] : '';
                if (!$corpid || !$external_userid) {
                    continue;
                }
                $whereUnique = [
                    'from' => WorkWeiXinConst::FROM_PROPERTY, 'from_id' => $property_id, 'suite_corpid' => $suite_corpid,
                    'suite_id' => $suite_id, 'auth_corpid' => $corpid, 'external_userid' => $external_userid, 'delete_time' => 0,
                ];
                $workWeiXinUserData = [
                    'from' => WorkWeiXinConst::FROM_PROPERTY, 'from_id' => $property_id, 'suite_corpid' => $suite_corpid,
                    'suite_id' => $suite_id, 'auth_corpid' => $corpid, 'external_userid' => $external_userid,
                    'delete_time' => 0, 'user_openid' => $openid, 'type_openid' => 'openid', 'user_unionid' => $unionid,
                ];
                $workWeixinUser = $dbWorkWeixinUser->getOne($whereUnique, 'id, mobile', 'id DESC');
                if ($workWeixinUser && !is_array($workWeixinUser)) {
                    $workWeixinUser = $workWeixinUser->toArray();
                }
                if ($workWeixinUser && isset($workWeixinUser['id'])) {
                    if (isset($workWeixinUser['mobile']) && $workWeixinUser['mobile'] && isset($workWeixinUser['mobile'])) {
                        unset($workWeiXinUserData['mobile']);
                    }
                    $workWeiXinUserData['update_time'] = $nowTime;
                    $dbWorkWeixinUser->updateThis($whereUnique, $workWeiXinUserData);
                } else {
                    $workWeiXinUserData['add_time'] = $nowTime;
                    $dbWorkWeixinUser->add($workWeiXinUserData);
                }
                $whereContactWayUser = [];
                $whereContactWayUser[] = ['corpid', '=', $corpid];
                $whereContactWayUser[] = ['ExternalUserID', '=', $external_userid];
                $whereContactWayUser[] = ['status', '=', 1];
                if ($property_id) {
                    $whereContactWayUser[] = ['property_id', '=', $property_id];
                }
                if ($village_id) {
                    $whereContactWayUser[] = ['village_id', '=', $village_id];
                }
                $whereContactWayUser[] = ['uid', 'in', [0, $uid]];
                $whereContactWayUser[] = ['bind_id', 'in', [0, $pigcms_id]];
                $order = "bind_id DESC, customer_id DESC";
                $field = 'customer_id, housekeeper_id, UserID, ExternalUserID, code_id';
                $infoContactWayUser = $dbHouseContactWayUser->getOne($whereContactWayUser, $field, $order);
                if (isset($infoContactWayUser['customer_id']) && $infoContactWayUser['customer_id']) {
                    $toMark = true;
                    $customer_id = $infoContactWayUser['customer_id'];
                    $dataSave = [
                        'bind_id' => $pigcms_id,
                    ];
                    if ($property_id) {
                        $dataSave['property_id'] = $property_id;
                    }
                    if ($village_id) {
                        $dataSave['village_id'] = $village_id;
                    }
                    if (isset($userInfo['single_id']) && $userInfo['single_id']) {
                        $dataSave['single_id'] = $userInfo['single_id'];
                    }
                    if (isset($userInfo['floor_id']) && $userInfo['floor_id']) {
                        $dataSave['floor_id'] = $userInfo['floor_id'];
                    }
                    if (isset($userInfo['vacancy_id']) && $userInfo['vacancy_id']) {
                        $dataSave['room_id'] = $userInfo['vacancy_id'];
                    }
                    $dbHouseContactWayUser->updateWxInfo(['customer_id' => $customer_id], $dataSave);
                    // todo 走队列 对应打标签
                    if (isset($infoContactWayUser['housekeeper_id']) && intval($infoContactWayUser['housekeeper_id']) > 0) {
                        $this->markHousekeeperTagJob($pigcms_id, $customer_id, $infoContactWayUser['housekeeper_id']);
                    } elseif ($infoContactWayUser['business_type'] === 'village_qywx_channel_code') {
                        $queueData = [
                            'code_id' => $infoContactWayUser['code_id'], 'userid' => $infoContactWayUser['UserID'],
                            'external_userid' => $infoContactWayUser['ExternalUserID'], 'property_id' => $property_id,
                            'customer_id' => $customer_id, 'code_mark_user' => 1,
                            'type' => 'update_corp_tag_info', 'jobType' => 'workWeiXinNew',
                        ];
                        try {
                            $this->traitCommonWorkWeiXin($queueData);
                        } catch (\Exception $e) {
                            fdump_api(['title' => '标签未打上', 'err' => $e->getMessage()], 'qiYeWeiXin/checkUserPassErrLog', true);
                        }
                    } else {
                        fdump_api(['title' => '标签未打上【缺少可执行必备参数】', 'infoContactWayUser' => $infoContactWayUser], 'qiYeWeiXin/checkUserPassErrLog', true);
                    }
                }
            }
        }
        if (!$toMark) {
            // 拿uid进行匹配
            $where = [];
            $where[] = ['bind_type', '=', 0];
            $where[] = ['bind_id', '=', $property_id];
            $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
            $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corpid', 'pigcms_id DESC');
            $corpid = isset($bindInfo['corpid']) && $bindInfo['corpid'] ? $bindInfo['corpid'] : '';

            $whereContactWayUser = [];
            $whereContactWayUser[] = ['corpid', '=', $corpid];
            $whereContactWayUser[] = ['status', '=', 1];
            $whereContactWayUser[] = ['uid', '=', $uid];
            if ($property_id) {
                $whereContactWayUser[] = ['property_id', '=', $property_id];
            }
            if ($village_id) {
                $whereContactWayUser[] = ['village_id', '=', $village_id];
            }
            $whereContactWayUser[] = ['bind_id', 'in', [0, $pigcms_id]];
            $field = 'customer_id, housekeeper_id, UserID, ExternalUserID, code_id';
            $infoContactWayUser = $dbHouseContactWayUser->getOne($whereContactWayUser, $field, $order);
            if (isset($infoContactWayUser['customer_id']) && $infoContactWayUser['customer_id']) {
                $toMark = true;
                $customer_id = $infoContactWayUser['customer_id'];
                $dataSave = [
                    'bind_id' => $pigcms_id,
                ];
                if ($property_id) {
                    $dataSave['property_id'] = $property_id;
                }
                if ($village_id) {
                    $dataSave['village_id'] = $village_id;
                }
                if (isset($userInfo['single_id']) && $userInfo['single_id']) {
                    $dataSave['single_id'] = $userInfo['single_id'];
                }
                if (isset($userInfo['floor_id']) && $userInfo['floor_id']) {
                    $dataSave['floor_id'] = $userInfo['floor_id'];
                }
                if (isset($userInfo['vacancy_id']) && $userInfo['vacancy_id']) {
                    $dataSave['room_id'] = $userInfo['vacancy_id'];
                }
                $dbHouseContactWayUser->updateWxInfo(['customer_id' => $customer_id], $dataSave);
                // todo 走队列 对应打标签
                if (isset($infoContactWayUser['housekeeper_id']) && intval($infoContactWayUser['housekeeper_id']) > 0) {
                    $this->markHousekeeperTagJob($pigcms_id, $customer_id, $infoContactWayUser['housekeeper_id']);
                } elseif ($infoContactWayUser['business_type'] === 'village_qywx_channel_code') {
                    $queueData = [
                        'code_id' => $infoContactWayUser['code_id'],
                        'userid' => $infoContactWayUser['UserID'],
                        'external_userid' => $infoContactWayUser['ExternalUserID'],
                        'property_id' => $property_id,
                        'customer_id' => $customer_id,
                        'code_mark_user' => 1,
                        'type' => 'update_corp_tag_info',
                        'jobType' => 'workWeiXinNew',
                    ];
                    try {
                        $this->traitCommonWorkWeiXin($queueData);
                    } catch (\Exception $e) {
                        fdump_api(['title' => '标签未打上', 'err' => $e->getMessage()], 'qiYeWeiXin/checkUserPassErrLog', true);
                    }
                } else {
                    fdump_api(['title' => '标签未打上【缺少可执行必备参数】', 'infoContactWayUser' => $infoContactWayUser], 'qiYeWeiXin/checkUserPassErrLog', true);
                }
            }
        }
        return true;
    }

    /**
     * 第三方主体unionid转换为第三方external_userid.
     */
    public function unionIdToExternalUserID3rd($unionid, $openid, $property_id, $corpid = '') {
        $suite_access_token_info = $this->getAgentCgiBinServiceGetSuiteToken();
        $suite_access_token = isset($suite_access_token_info['access_token']) ? $suite_access_token_info['access_token'] : '';
        if ($suite_access_token) {
            if (! $corpid) {
                $where = [];
                $where[] = ['bind_type', '=', 0];
                $where[] = ['bind_id', '=', $property_id];
                $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
                $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corpid','pigcms_id DESC');
                $corpid = isset($bindInfo['corpid']) && $bindInfo['corpid'] ? $bindInfo['corpid'] : '';
            }
        }
        if (! $suite_access_token || ! $unionid || ! $openid || ! $corpid) {
            return false;
        }
        $param = ['unionid' => $unionid, 'openid' => $openid, 'corpid' => $corpid];
        $result = (new WorkWeiXinRequestService())->cgiBinExternalContactUnionIdToExternalUserId3rd($param, $suite_access_token);
        fdump_api(['title' => '获取第三方应用access_token错误', 'suite_access_token' => $suite_access_token], 'qiYeWeiXin/unionIdToExternalUserID3rdLog',true);
        return $result;
    }

    /**
     * 公共返回第三方应用access_token。
     */
    public function commonGetAccessToken($property_id, $isAgent = true) {
        $access_token_info = $this->getCgiBinServiceGetCorpToken($property_id, $isAgent);
        $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
        if ('42001' == $access_token_info['code']) {
            $suite_access_token = isset($access_token_info['suite_access_token']) ? $access_token_info['suite_access_token'] : '';
            $access_token_info = $this->getCgiBinServiceGetCorpToken($property_id, $isAgent, $suite_access_token, true);
            if (isset($access_token_info['access_token'])) {
                $access_token = $access_token_info['access_token'];
            }
        }
        if (! $access_token) {
            fdump_api(['title' => '获取第三方应用access_token错误', 'access_token_info' => $access_token_info], 'qiYeWeiXin/commonGetAccessTokenErrLog',true);
        }
        return $access_token;
    }

    /**
     * 队列公用中转方法
     */
    public function workWeiXinNew($queueData) {
        $type = isset($queueData['type']) && $queueData['type'] ? $queueData['type'] : '';
        switch ($type) {
            case 'send_welcome_msg':
                $this->sendWelcomeMsg($queueData);
                break;
            case 'update_corp_tag_info':
                $property_id = isset($queueData['property_id']) && $queueData['property_id'] ? $queueData['property_id'] : 0;
                if ($property_id) {
                    $this->updateCorpTagInfo($property_id);
                }
                $code_mark_user = isset($queueData['code_mark_user']) && $queueData['code_mark_user'] ? $queueData['code_mark_user'] : 0;
                if ($code_mark_user) {
                    $queueData['jobType'] = 'workWeiXinNew';
                    $queueData['type'] = 'code_mark_user';
                    try{
                        $job_id = $this->traitCommonWorkWeiXin($queueData);
                        fdump_api([
                            'title' => '打标签', 'queueData' => $queueData, 'job_id' => $job_id,
                        ], 'qiYeWeiXin/update_corp_tag_info_log',true);
                    }catch (\Exception $e){
                        fdump_api(['title' => '标签未打上', 'err' => $e->getMessage()], 'qiYeWeiXin/updateCorpTagInfoErrLog',true);
                    }
                }
                break;
            case 'code_mark_user':
                $this->codeMarkUser($queueData);
                break;
            case 'mark_tag_contact_way_user':
                $this->markTagContactWayUser($queueData);
                break;
            case 'village_qywx_data_statistics_add':
                $this->villageQywxDataStatisticsAdd($queueData);
                break;
            case 'village_qywx_data_statistics_reduce':
                $this->villageQywxDataStatisticsReduce($queueData);
                break;
            case 'mark_housekeeper_contact_way_tag':
                $this->markHousekeeperContactWayTagHandle($queueData);
                break;
            case 'check_user_pass':
                $this->checkUserPass($queueData);
                break;
        }
        return true;
    }

    /**
     * 发送欢迎语替换关键字.
     */
    public function getSendWelcomeWorkParamWord() {
        return [
            ['key' => '{姓名}', 'word' => 'name'],
            ['key' => '{手机号}', 'word' => 'phone'],
        ];
    }

    /**
     * 过滤下特殊字符.
     */
    public $nameStrReplaceSearch = [':','*','?','"','<','>','｜'];
    public function checkStr($name) {
        $name = trim($name);
        $name = str_replace($this->nameStrReplaceSearch, '', $name);
        return $name;
    }

    public function getHouseTagArr() {
        return [
            ['name' => '小区','key'=>'village_name', 'order'=>'200'], ['name' => '楼栋','key'=>'single_name', 'order'=>'199'],
            ['name' => '单元','key'=>'floor_name', 'order'=>'198'], ['name' => '楼层','key'=>'layer_name', 'order'=>'197'],
            ['name' => '房间号','key'=>'room', 'order'=>'196'], ['name' => '业主类型','key'=>'type', 'order'=>'195'],
        ];
    }

    /**
     * 获得分类对应描述.
     */
    public function getTypeInfo($type) {
        $open_house_user_name = cfg('open_house_user_name');
        $type_desc = '';
        switch (intval($type)) {
            case 0:
            case 3:
                if ($open_house_user_name) {
                    $type_desc = "房主&社长";
                } else {
                    $type_desc = "房主";
                }
                break;
            case 1:
                if ($open_house_user_name) {
                    $type_desc = "亲友";
                } else {
                    $type_desc = "家属";
                }
                break;
            case 2:
                if ($open_house_user_name) {
                    $type_desc = "租客&社员";
                } else {
                    $type_desc = "租客";
                }
                break;
            case 4:
                $type_desc = "工作人员";
                break;
            case 5:
                $type_desc = "通行证";
                break;
            case 6:
                $type_desc = "访客";
                break;
        }
        return $type_desc;
    }

    /**
     * 获取对应小区相关标签名称
     */
    public function getHouseTagNameArr($houseTag, &$user_bind) {
        $key = strval($houseTag['key']);
        if (isset($user_bind[$key]) && $user_bind[$key]) {
            return $user_bind[$key];
        }
        $name = '';
        switch ($key) {
            case 'village_name':
                $village_id = isset($user_bind['village_id']) ? intval($user_bind['village_id']) : 0;
                $info = (new HouseVillage())->getOne($village_id, 'village_name');
                $name = isset($info['village_name']) ? strval($info['village_name']) : '';
                break;
            case 'single_name':
                $single_id = isset($user_bind['single_id']) ? intval($user_bind['single_id']) : 0;
                $info = (new HouseVillageSingle())->getOne(['id' => $single_id], 'single_name');
                $name = isset($info['single_name']) ? strval($info['single_name']) : '';
                break;
            case 'floor_name':
                $floor_id = isset($user_bind['floor_id']) ? intval($user_bind['floor_id']) : 0;
                $info = (new HouseVillageFloor())->getOne(['floor_id' => $floor_id], 'floor_name');
                $name = isset($info['floor_name']) ? strval($info['floor_name']) : '';
                break;
            case 'layer_name':
                $layer_id = isset($user_bind['layer_id']) ? intval($user_bind['layer_id']) : 0;
                $info = (new HouseVillageLayer())->getOne(['id' => $layer_id], 'layer_name');
                $name = isset($info['layer_name']) ? strval($info['layer_name']) : '';
                break;
            case 'room':
                $vacancy_id = isset($user_bind['vacancy_id']) ? intval($user_bind['vacancy_id']) : 0;
                $info = (new HouseVillageUserVacancy())->getOne(['pigcms_id' => $vacancy_id], 'room');
                $name = isset($info['room']) ? strval($info['room']) : '';
                break;
            case 'type':
                $name = $this->getTypeInfo($user_bind['type']);
                break;
        }
        if ($name) {
            $user_bind[$key] = $name;
        }
        return $name;
    }
}