<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      企业微信第三方应用业务处理
 */

namespace app\community\model\service\workweixin;

use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseWorker;
use app\traits\WorkWeiXinToJobTraits;

class WorkWeiXinTaskService
{
    use WorkWeiXinToJobTraits;

    public function WorkWeiXinTask($queueData) {
        fdump_api(['title' => '队列','line' => __LINE__, 'queueData' => $queueData], 'workWeiXinTask_workWeiXinTaskLog');
        $type = isset($queueData['type']) && $queueData['type'] ? $queueData['type'] : '';
        switch ($type) {
            case 'contact_way_user_job':
                $this->contactWayUserJob($queueData);
                break;
            case 'contact_external_user_id_change':
                $this->contactWayExternalUserIdChange($queueData);
                break;
            case 'contact_way_user_id_change':
                $this->contactWayUserIdChange($queueData);
                break;
            case 'house_work_user_id_change':
                $this->houseWorkUserIdChange($queueData);
                break;
        }
        return true;
    }

    public function contactWayUserJob($queueData = []) {
        $third_suite_id = cfg('enterprise_wx_corpid');
        $enterprise_wx_provider_suiteid = cfg('enterprise_wx_provider_suiteid');
        $enterprise_book_suiteid = cfg('enterprise_book_suiteid');
        if (! $third_suite_id || ! $enterprise_wx_provider_suiteid || ! $enterprise_book_suiteid) {
            return false;
        }
        $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $where = [
            ['bind_type', '=', 0],
        ];
        if (isset($queueData['property_id']) && $queueData['property_id']) {
            $where[] = ['bind_id', '=', intval($queueData['property_id'])];
        } else {
            $where[] = ['bind_id', '>', 0];
        }
        $propertyList = $dbHouseEnterpriseWxBind->getSome($where, 'bind_id');
        if ($propertyList && ! is_array($propertyList)) {
            $propertyList = $propertyList->toArray();
        }
        foreach ($propertyList as $key => $property) {
            $num = $key + 1;
            $queueData = [];
            $queueData['jobType'] = 'workWeiXinTask';
            $queueData['type'] = 'contact_external_user_id_change';
            $queueData['property_id'] = $property['bind_id'];
            try{
                $this->traitCommonWorkWeiXin($queueData, $num * 1);
            }catch (\Exception $e){
                fdump_api(['title' => 'external_userid主体转换错误','line' => __LINE__, 'err' => $e->getMessage()], 'workWeiXinTask/contactWayUserJobErrLog1');
            }
            $queueData = [];
            $queueData['jobType'] = 'workWeiXinTask';
            $queueData['type'] = 'contact_way_user_id_change';
            $queueData['property_id'] = $property['bind_id'];
            try{
                $this->traitCommonWorkWeiXin($queueData, $num * 2);
            }catch (\Exception $e){
                fdump_api(['title' => 'userid主体转换错误','line' => __LINE__, 'err' => $e->getMessage()], 'workWeiXinTask/contactWayUserJobErrLog2');
            }
            $queueData = [];
            $queueData['jobType'] = 'workWeiXinTask';
            $queueData['type'] = 'house_work_user_id_change';
            $queueData['property_id'] = $property['bind_id'];
            try{
                $this->traitCommonWorkWeiXin($queueData, $num * 3);
            }catch (\Exception $e){
                fdump_api(['title' => 'userid主体转换错误','line' => __LINE__, 'err' => $e->getMessage()], 'workWeiXinTask/contactWayUserJobErrLog3');
            }
        }
    }

    /**
     * 扫码（渠道活码+楼栋管家码）进入的对应外部联系人external_userid转换.
     * 将企业主体下的external_userid转换为服务商主体下的external_userid。
     */
    public function contactWayExternalUserIdChange($queueData) {
        $dbHouseContactWayUser = new HouseContactWayUser();
        $third_suite_id = cfg('enterprise_wx_corpid');
        $enterprise_wx_provider_suiteid = cfg('enterprise_wx_provider_suiteid');
        $property_id = isset($queueData['property_id']) && $queueData['property_id'] ? intval($queueData['property_id']) : 0;
        fdump_api(['title' => 'external_userid转换数据', 'line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayExternalUserIdChangeLog');
        if (! $third_suite_id || ! $enterprise_wx_provider_suiteid || ! $property_id) {
            fdump_api(['title' => 'external_userid转换错误', 'err' => '参数缺少','line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayExternalUserIdChangeErrLog1');
            return false;
        }
        $whereWayUser1 = [
            ['status', 'in', [1, 3]],
            ['third_suite_id', '<>', $third_suite_id],
            ['property_id', '=', $property_id],
        ];
        $whereWayUser2 = [
            ['status', 'in', [1, 3]],
            ['third_suite_external_userid', '=', ''],
            ['property_id', '=', $property_id],
        ];
        $field = 'customer_id, corpid, ExternalUserID, property_id';
        $wayUserList = $dbHouseContactWayUser->getWhereOrPage([$whereWayUser1, $whereWayUser2], $field, 1, 100);
        if ($wayUserList && ! is_array($wayUserList)) {
            $wayUserList = $wayUserList->toArray();
        }
        if (empty($wayUserList)) {
            fdump_api(['title' => 'external_userid转换错误', 'err' => '没有需要转换的', 'line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayExternalUserIdChangeErrLog2');
            return true;
        }
        $workWeiXinNewService = new WorkWeiXinNewService();
        $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
        if (! $access_token) {
            fdump_api(['title' => 'external_userid转换错误', 'err' => 'access_token错误','line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayExternalUserIdChangeErrLog3');
            return true;
        }
        $externalUserIDArr = [];
        $external_userid_list = [];
        foreach ($wayUserList as $wayUser) {
            $externalUserID = isset($wayUser['ExternalUserID']) ? trim($wayUser['ExternalUserID']) : '';
            if ($externalUserID) {
                if (isset($externalUserIDArr[$externalUserID]) && !empty($externalUserIDArr[$externalUserID])) {
                    $externalUserIDArr[$externalUserID][] = $wayUser['customer_id'];
                    continue;
                }
                $externalUserIDArr[$externalUserID] = [];
                $externalUserIDArr[$externalUserID][] = $wayUser['customer_id'];
                $external_userid_list[] = $externalUserID;
            }
        }
        if (empty($external_userid_list)) {
            fdump_api(['title' => 'external_userid转换错误', 'err' => '没有需要转换的','line' => __LINE__, 'wayUserList' => $wayUserList, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayExternalUserIdChangeErrLog4');
            return true;
        }
        $param = [
            "external_userid_list" => $external_userid_list
        ];
        $result = (new WorkWeiXinRequestService())->cgiBinExternalContactGetNewExternalUserId($param, $access_token);
        $nowTime = time();
        if (isset($result['items']) && ! empty($result['items'])) {
            fdump_api(['title' => 'external_userid转换数据', 'line' => __LINE__, 'items' => $result['items'], 'param' => $param, 'access_token' => $access_token, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayExternalUserIdChangeLog',1);
            foreach ($result['items'] as $item) {
                $external_userid = isset($item['external_userid']) && $item['external_userid'] ? $item['external_userid'] : '';
                $new_external_userid = isset($item['new_external_userid']) && $item['new_external_userid'] ? $item['new_external_userid'] : '';
                if ($external_userid && $new_external_userid) {
                    $customerIds = isset($externalUserIDArr[$external_userid]) && !empty($externalUserIDArr[$external_userid]) ? $externalUserIDArr[$external_userid] : [];
                    $whereWayUser = [];
                    if (! empty($customerIds)) {
                        $whereWayUser[] = ['customer_id', 'in', $customerIds];
                    } else {
                        $whereWayUser[] = ['property_id', '=', $property_id];
                        $whereWayUser[] = ['ExternalUserID', '=', $external_userid];
                    }
                    $saveData = [
                        'third_suite_id' => $third_suite_id,
                        'third_suite_external_userid' => $new_external_userid,
                        'third_suite_external_time' => $nowTime,
                    ];
                    $dbHouseContactWayUser->updateWxInfo($whereWayUser, $saveData);
                }
            }
        } else {
            fdump_api(['title' => 'external_userid转换错误', 'err' => '转换结果无数据','line' => __LINE__, 'result' => $result, 'param' => $param, 'access_token' => $access_token, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayExternalUserIdChangeErrLog5');
        }
        return true;
    }

    /**
     * 扫码（渠道活码+楼栋管家码）进入的对应工作人员userid转换.
     * 将企业主体下的明文userid转换为服务商主体下的密文userid。
     */
    public function contactWayUserIdChange($queueData) {
        $dbHouseContactWayUser = new HouseContactWayUser();
        $third_suite_id = cfg('enterprise_wx_corpid');
        $enterprise_wx_provider_suiteid = cfg('enterprise_wx_provider_suiteid');
        $property_id = isset($queueData['property_id']) && $queueData['property_id'] ? intval($queueData['property_id']) : 0;
        fdump_api(['title' => 'userid转换数据', 'line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayUserIdChangeLog');
        if (! $third_suite_id || ! $enterprise_wx_provider_suiteid || !$property_id) {
            fdump_api(['title' => 'userid转换错误', 'err' => '参数缺少','line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayUserIdChangeErrLog1');
            return false;
        }
        $whereWayUser1 = [
            ['status', 'in', [1, 3]],
            ['third_suite_id', '<>', $third_suite_id],
            ['property_id', '=', $property_id],
        ];
        $whereWayUser2 = [
            ['status', 'in', [1, 3]],
            ['third_suite_userid', '=', ''],
            ['property_id', '=', $property_id],
        ];
        $field = 'customer_id, corpid, UserID, property_id';
        $wayUserList = $dbHouseContactWayUser->getWhereOrPage([$whereWayUser1, $whereWayUser2], $field, 1, 100);
        if ($wayUserList && ! is_array($wayUserList)) {
            $wayUserList = $wayUserList->toArray();
        }
        if (empty($wayUserList)) {
            fdump_api(['title' => 'userid转换错误', 'err' => '无处理对象','line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayUserIdChangeErrLog2');
            return true;
        }
        $workWeiXinNewService = new WorkWeiXinNewService();
        $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
        if (! $access_token) {
            fdump_api(['title' => 'userid转换错误', 'err' => 'access_token错误','line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayUserIdChangeErrLog3');
            return true;
        }
        $userIDArr = [];
        $userid_list = [];
        foreach ($wayUserList as $wayUser) {
            $userID = isset($wayUser['UserID']) ? trim($wayUser['UserID']) : '';
            if ($userID) {
                if (isset($userIDArr[$userID]) && !empty($userIDArr[$userID])) {
                    $userIDArr[$userID][] = $wayUser['customer_id'];
                    continue;
                }
                $userIDArr[$userID] = [];
                $userIDArr[$userID][] = $wayUser['customer_id'];
                $userid_list[] = $userID;
            }
        }
        if (empty($userid_list)) {
            fdump_api(['title' => 'userid转换错误', 'err' => '没有需要转换的','line' => __LINE__, 'wayUserList' => $wayUserList, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayUserIdChangeErrLog4');
            return true;
        }
        $result = (new WorkWeiXinRequestService())->cgiBinBatchUseridToOpenuserid($userid_list, $access_token);
        $nowTime = time();
        if (isset($result['open_userid_list']) && ! empty($result['open_userid_list'])) {
            fdump_api(['title' => 'userid转换数据', 'line' => __LINE__, 'open_userid_list' => $result['open_userid_list'], 'userid_list' => $userid_list, 'access_token' => $access_token, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayUserIdChangeLog', 1);
            foreach ($result['open_userid_list'] as $item) {
                $userid = isset($item['userid']) && $item['userid'] ? $item['userid'] : '';
                $open_userid = isset($item['open_userid']) && $item['open_userid'] ? $item['open_userid'] : '';
                if ($userid && $open_userid) {
                    $customerIds = isset($userIDArr[$userid]) && !empty($userIDArr[$userid]) ? $userIDArr[$userid] : [];
                    $whereWayUser = [];
                    if (! empty($customerIds)) {
                        $whereWayUser[] = ['customer_id', 'in', $customerIds];
                    } else {
                        $whereWayUser[] = ['property_id', '=', $property_id];
                        $whereWayUser[] = ['UserID', '=', $userid];
                    }
                    $saveData = [
                        'third_suite_id' => $third_suite_id,
                        'third_suite_userid' => $open_userid,
                        'third_suite_userid_time' => $nowTime,
                    ];
                    $dbHouseContactWayUser->updateWxInfo($whereWayUser, $saveData);
                }
            }
        } else {
            fdump_api(['title' => 'userid转换错误', 'err' => '转换结果无数据','line' => __LINE__, 'result' => $result, 'userid_list' => $userid_list, 'access_token' => $access_token, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/contactWayUserIdChangeErrLog5');
        }
        return true;
    }


    /**
     * 对应工作人员userid转换.
     * 将企业主体下的明文userid转换为服务商主体下的密文userid。
     */
    public function houseWorkUserIdChange($queueData) {
        $dbHouseWorker = new HouseWorker();
        $third_suite_id = cfg('enterprise_wx_corpid');
        $enterprise_wx_provider_suiteid = cfg('enterprise_wx_provider_suiteid');
        $property_id = isset($queueData['property_id']) && $queueData['property_id'] ? intval($queueData['property_id']) : 0;
        fdump_api(['title' => 'userid转换数据', 'line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/houseWorkUserIdChangeLog');
        if (! $third_suite_id || ! $enterprise_wx_provider_suiteid || !$property_id) {
            fdump_api(['title' => 'userid转换错误', 'err' => '参数缺少','line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/houseWorkUserIdChangeErrLog1');
            return false;
        }
        $whereWorkUser1 = [
            ['status', '=', 1],
            ['third_suite_id', '<>', $third_suite_id],
            ['property_id', '=', $property_id],
            ['qy_id', '<>', ''],
        ];
        $whereWorkUser2 = [
            ['status', '=', 1],
            ['qy_open_userid', '=', ''],
            ['property_id', '=', $property_id],
            ['qy_id', '<>', ''],
        ];
        $field = 'wid, qy_id, property_id';
        $workUserList = $dbHouseWorker->getWhereOrPage([$whereWorkUser1, $whereWorkUser2], $field, 1, 100);
        if ($workUserList && ! is_array($workUserList)) {
            $workUserList = $workUserList->toArray();
        }
        if (empty($workUserList)) {
            fdump_api(['title' => 'userid转换错误', 'err' => '无处理对象','line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/houseWorkUserIdChangeErrLog2');
            return true;
        }
        $workWeiXinNewService = new WorkWeiXinNewService();
        $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
        if (! $access_token) {
            fdump_api(['title' => 'userid转换错误', 'err' => 'access_token错误','line' => __LINE__, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/houseWorkUserIdChangeErrLog3');
            return true;
        }
        $userIDArr = [];
        $userid_list = [];
        foreach ($workUserList as $workUser) {
            $userID = isset($workUser['UserID']) ? trim($workUser['UserID']) : '';
            if ($userID) {
                if (isset($userIDArr[$userID]) && !empty($userIDArr[$userID])) {
                    $userIDArr[$userID][] = $workUser['customer_id'];
                    continue;
                }
                $userIDArr[$userID] = [];
                $userIDArr[$userID][] = $workUser['wid'];
                $userid_list[] = $userID;
            }
        }
        if (empty($userid_list)) {
            fdump_api(['title' => 'userid转换错误', 'err' => '没有需要转换的','line' => __LINE__, 'WorkUserList' => $workUserList, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/houseWorkUserIdChangeErrLog4');
            return true;
        }
        $result = (new WorkWeiXinRequestService())->cgiBinBatchUseridToOpenuserid($userid_list, $access_token);
        $nowTime = time();
        if (isset($result['open_userid_list']) && ! empty($result['open_userid_list'])) {
            fdump_api(['title' => 'userid转换数据', 'line' => __LINE__, 'open_userid_list' => $result['open_userid_list'], 'userid_list' => $userid_list, 'access_token' => $access_token, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/houseWorkUserIdChangeLog', 1);
            foreach ($result['open_userid_list'] as $item) {
                $userid = isset($item['userid']) && $item['userid'] ? $item['userid'] : '';
                $open_userid = isset($item['open_userid']) && $item['open_userid'] ? $item['open_userid'] : '';
                if ($userid && $open_userid) {
                    $wIds = isset($userIDArr[$userid]) && !empty($userIDArr[$userid]) ? $userIDArr[$userid] : [];
                    $whereWorkUser = [];
                    if (! empty($wIds)) {
                        $whereWorkUser[] = ['wid', 'in', $wIds];
                    } else {
                        $whereWorkUser[] = ['property_id', '=', $property_id];
                        $whereWorkUser[] = ['qy_id', '=', $userid];
                    }
                    $saveData = [
                        'third_suite_id' => $third_suite_id,
                        'qy_open_userid' => $open_userid,
                        'third_suite_userid_time' => $nowTime,
                    ];
                    $dbHouseWorker->editData($whereWorkUser, $saveData);
                }
            }
        } else {
            fdump_api(['title' => 'userid转换错误', 'err' => '转换结果无数据','line' => __LINE__, 'result' => $result, 'userid_list' => $userid_list, 'access_token' => $access_token, 'queueData' => $queueData, 'third_suite_id' => $third_suite_id, 'enterprise_wx_provider_suiteid' => $enterprise_wx_provider_suiteid], 'workWeiXinTask/houseWorkUserIdChangeErrLog5');
        }
        return true;
    }
}