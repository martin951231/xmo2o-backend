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

use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\VillageQywxMessage;
use app\community\model\db\VillageQywxMessageDetail;
use app\community\model\db\VillageQywxMessageRecord;
use app\consts\WorkWeiXinConst;
use app\traits\WorkWeiXinToJobTraits;
use think\facade\Cache;

class WorkWeiXinGroupMsgService
{
    use WorkWeiXinToJobTraits;

    public function workWeiXinGroupMsg($queueData) {
        $type = isset($queueData['type']) && $queueData['type'] ? $queueData['type'] : '';
        switch ($type) {
            case 'get_group_msg_send_result':
                $this->getGroupMsgSendResult();
                break;
            case 'send_group_message':
                $this->sendGroupMessage();
                break;
            case 'remind_group_msg_send':
                $this->remindGroupMsgSend($queueData);
                break;
        }
        return true;
    }

    /**
     * 获取企业群发成员执行结果并对应记录结果.
     */
    public function getGroupMsgSendResult() {
        $where = [];
        $where[] = ['send_status', 'in', '2,3,4'];
        $where[] = ['is_del', '=', '0'];
        $where[] = ['is_del', '=', 0];
        $where[] = ['msgid', '<>', ''];
        $db_village_qywx_message = new VillageQywxMessage();
        $list = $db_village_qywx_message->getList($where, true, 1, 30);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            return true;
        }
        $nowTime = time();
        $db_village_qywx_message_record = new VillageQywxMessageRecord();
        $db_house_contact_way_user = new HouseContactWayUser();
        $workWeiXinNewService = new WorkWeiXinNewService();
        foreach ($list as $item) {
            $property_id = isset($item['property_id']) ? intval($item['property_id']) : 0 ;
            $send_type = isset($item['send_type']) ? intval($item['send_type']) : 0 ;
            $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
            switch ($send_type) {
                case 1:
                    // 1-业主
                case 2:
                    // 2-业主群
                case 3:
                    // 3-企业成员
                    $msg_id_arr = explode(',', $item['msgid']);
                    $actual_num = 0;
                    $senderArr = explode(',', $item['sender']);
                    $sendListArr = [];
                    $hasErr = false;
                    $where_work = [
                        ['property_id','=',$property_id]
                    ];
                    fdump_api([
                        'title' => '群发[结果]', 'item' => $item, 'senderArr' => $senderArr,  'where_work' => $where_work
                    ], 'qiYeWeiXin/getGroupMsgSendResultLog', 1);
                    $openUserids = (new WorkWeiXinNewService())->getCgiBinBatchUseridToOpenuserid($property_id, $senderArr, $where_work, $access_token);
                    fdump_api([
                        'title' => '群发[结果]', 'senderArr' => $senderArr,'openUserids' => $openUserids, 'access_token' => $access_token,
                    ], 'qiYeWeiXin/getGroupMsgSendResultLog', 1);
                    foreach ($openUserids as $sender) {
                        foreach ($msg_id_arr as $msgid) {
                            $params = [
                                'msgid' => $msgid,
                                'userid' => $sender,
                            ];
                            $sendListArr = $this->pageCgiBinExternalContactGetGroupMsgSendResult($params, $access_token, $sendListArr, $hasErr);
                        }
                    }
                    $total_num = count($sendListArr);
                    $txt = '人';
                    $fail_list_json = [];
                    foreach ($sendListArr as $groupValue) {
                        if (!isset($groupValue['chat_id'])) {
                            continue;
                        }
                        if (intval($groupValue['status']) === 1) {
                            $actual_num += 1;
                        }
                        $external_userid = isset($groupValue['external_userid']) ? trim($groupValue['external_userid']) : '';
                        $userid = isset($groupValue['userid']) ? trim($groupValue['userid']) : '';
                        $chat_id = isset($groupValue['chat_id']) ? trim($groupValue['chat_id']) : '';
                        $send_time = isset($groupValue['send_time']) ? trim($groupValue['send_time']) : 1;
                        $status = isset($groupValue['status']) ? intval($groupValue['status']) : 0;

                        $whereRecord = [
                            'message_id' => $item['id']
                        ];
                        if ($chat_id) {
                            $txt = '群';
                            $whereRecord['chat_id'] = $chat_id;
                        }
                        if ($userid) {
                            $whereRecord['user_id'] = $userid;
                        }
                        if ($external_userid) {
                            $whereRecord['external_user_id'] = $external_userid;
                            if (intval($groupValue['status']) !== 1) {
                                $fail_list_json[] = $external_userid;
                            }
                        }
                        $record_info = $db_village_qywx_message_record->getOne($whereRecord);
                        $dataParam = [
                            'status' => $status, 'send_time' => $send_time
                        ];
                        if ($external_userid) {
                            $dataParam['external_user_id'] = $external_userid;
                        }
                        if ($userid) {
                            $dataParam['user_id'] = $userid;
                        }
                        if ($chat_id) {
                            $dataParam['chat_id'] = $chat_id;
                        }
                        if ($external_userid && $userid) {
                            $where = [
                                ['ExternalUserID', '=', $external_userid],
                                ['UserID', '=', $userid],
                                ['status', 'in', '1,3']
                            ];
                            $contact_user_info = $db_house_contact_way_user->getOne($where, 'customer_id');
                            if (isset($contact_user_info['customer_id']) && $contact_user_info['customer_id']) {
                                $dataParam['contact_user_id'] = $contact_user_info['customer_id'];
                            }
                        }
                        if ($record_info) {
                            $saveData = $dataParam;
                            $saveData['last_time'] = $nowTime;
                            $db_village_qywx_message_record->saveOne(['id' => $record_info['id']], $saveData);
                        } else {
                            $addData = $dataParam;
                            $addData['message_id'] = $item['id'];
                            $addData['add_time'] = $nowTime;
                            $db_village_qywx_message_record->addOne($addData);
                        }
                    }
                    $saveData = [
                        'send_res' => '预计发送' . $total_num . '个'.$txt.'，实际发送' . $actual_num . '个'.$txt,
                        'success_send' => $actual_num,
                        'fail_send' => $total_num - $actual_num
                    ];
                    if ($actual_num >= $total_num) {
                        $saveData['fail_send'] = 0;
                        ! $hasErr && $saveData['send_status'] = 1;
                    }
                    if (! empty($fail_list_json) && ! $item['fail_list_json']) {
                        $saveData['fail_list_json'] = json_encode($fail_list_json,JSON_UNESCAPED_UNICODE);
                    }
                    $db_village_qywx_message->saveOne(['id' => $item['id']], $saveData);
                    break;
            }
        }
    }

    /**
     * 避免存在多页数据递归查询出所有结果.
     */
    public function pageCgiBinExternalContactGetGroupMsgSendResult($params, $access_token, $sendListArr, &$hasErr) {
        $msg_result = (new WorkWeiXinRequestService())->cgiBinExternalContactGetGroupMsgSendResult($params, $access_token);
        if (!is_array($msg_result)) {
            $msg_result = json_decode($msg_result, true);
        }
        if ($msg_result['errcode'] == 0 && isset($msg_result['send_list']) && $msg_result['send_list']) {
            if (empty($sendListArr)) {
                $sendListArr = $msg_result['send_list'];
            } else {
                $sendListArr = array_merge($msg_result['send_list'], $sendListArr);
            }
            $next_cursor = isset($msg_result['next_cursor']) ? $msg_result['next_cursor'] : '';
            if ($next_cursor) {
                $params['cursor'] = $next_cursor;
                return $this->pageCgiBinExternalContactGetGroupMsgSendResult($params, $access_token, $sendListArr, $hasErr);
            }
        } else {
            $hasErr = true;
        }
        return $sendListArr;
    }

    /**
     * 发送群发消息.
     */
    public function sendGroupMessage() {
        $nowTime = time();
        $where = [];
        $where[] = ['send_status', 'in', '2,3,4'];
        $where[] = ['is_del', '=', '0'];
        $where[] = ['is_del', '=', 0];
        $where[] = ['msgid', '=', ''];
        $where[] = ['send_time', '<=', $nowTime];
        $db_village_qywx_message = new VillageQywxMessage();
        $list = $db_village_qywx_message->getList($where, true, 1, 30);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            return true;
        }
        $workWeiXinNewService = new WorkWeiXinNewService();
        $workWeiXinRequestService = new WorkWeiXinRequestService();
        $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
        foreach ($list as $item) {
            $property_id = isset($item['property_id']) ? intval($item['property_id']) : 0 ;
            $village_id = isset($item['village_id']) ? intval($item['village_id']) : 0 ;
            $send_type = isset($item['send_type']) ? intval($item['send_type']) : 0 ;
            $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
            if (! $access_token) {
                fdump_api(['title' => 'access_token确实', 'item' => $item,'line' => __LINE__, 'property_id' => $property_id], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
            }
            switch ($send_type) {
                case 1:
                    // 1-业主
                    $postParams = [];
                    // 群发任务的类型，默认为single，表示发送给客户，group表示发送给客户群
                    $postParams['chat_type'] = 'single';
                    $external_userid_arr = explode(',', $item['external_userid']);
                    if (empty($external_userid_arr)) {
                        fdump_api(['title' => '缺少具体发送对象[external_userid]','line' => __LINE__, 'item' => $item], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                        continue;
                    }
                    $postParams['external_userid'] = $external_userid_arr;

                    $contentDetail = $this->commonMessageDetailHandle($item);
                    if (empty($contentDetail)) {
                        continue;
                    }
                    if (isset($contentDetail['text']) && $contentDetail['text']) {
                        $postParams['text'] = $contentDetail['text'];
                    }
                    if (isset($contentDetail['attachments']) && ! empty($contentDetail['attachments'])) {
                        $postParams['attachments'] = $contentDetail['attachments'];
                    }
                    $result = $workWeiXinRequestService->cgiBinExternalContactAddMsgTemplate($postParams, $access_token);
                    if (!is_array($result)) {
                        $result = json_decode($result, true);
                    }
                    fdump_api([
                        'title' => '群发[业主]', 'item' => $item, 'postParams' => $postParams, 'access_token' => $access_token, 'result' => $result
                    ], 'qiYeWeiXin/sendGroupMessageLog', 1);
                    if (isset($result['errcode']) && intval($result['errcode']) == 0) {
                        $fail_list = isset($result['fail_list']) ? $result['fail_list'] : [];
                        $fail_list_json = json_encode($fail_list,JSON_UNESCAPED_UNICODE);
                        $err_count = count($fail_list);
                        $external_userid_count = count($external_userid_arr);
                        $sendNum = $external_userid_count - $err_count;
                        $saveData = [
                            'msgid' => $result['msgid'], 'send_status' => 2,
                            'send_res' => '预计发送' . $external_userid_count . '个客户，实际发送' . $sendNum . '个客户',
                            'success_send' => $sendNum,
                            'fail_send' => $err_count,
                            'fail_list_json' => $fail_list_json,
                        ];
                        $db_village_qywx_message->saveOne(['id' => $item['id']], $saveData);
                        // 推送提醒
                        $queueData = [];
                        $queueData['jobType'] = 'workWeiXinGroupMsg';
                        $queueData['type'] = 'remind_group_msg_send';
                        $queueData['property_id'] = $property_id;
                        $queueData['msgid'] = $result['msgid'];
                        try{
                            $this->traitCommonWorkWeiXin($queueData, 30);
                        }catch (\Exception $e){
                            fdump_api(['title' => '推送提醒队列错误', 'err' => $e->getMessage()], 'qiYeWeiXin/sendGroupMessageErrLog',true);
                        }
                    }
                    break;
                case 2:
                    // 2-业主群
                    $postParams = [];
                    // 群发任务的类型，默认为single，表示发送给客户，group表示发送给客户群
                    $postParams['chat_type'] = 'group';
                    $senderArr = explode(',', $item['sender']);
                    if (empty($senderArr)) {
                        fdump_api(['title' => '缺少具体发送对象[sender]','line' => __LINE__, 'item' => $item], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                        continue;
                    }

                    $contentDetail = $this->commonMessageDetailHandle($item);
                    if (empty($contentDetail)) {
                        continue;
                    }
                    if (isset($contentDetail['text']) && $contentDetail['text']) {
                        $postParams['text'] = $contentDetail['text'];
                    }
                    if (isset($contentDetail['attachments']) && ! empty($contentDetail['attachments'])) {
                        $postParams['attachments'] = $contentDetail['attachments'];
                    }
                    $where_work = [
                        ['property_id','=',$property_id]
                    ];
                    fdump_api([
                        'title' => '群发[业主群]', 'item' => $item, 'senderArr' => $senderArr,  'where_work' => $where_work
                    ], 'qiYeWeiXin/sendGroupMessageLog', 1);
                    $openUserids = (new WorkWeiXinNewService())->getCgiBinBatchUseridToOpenuserid($property_id, $senderArr, $where_work, $access_token);
                    fdump_api([
                        'title' => '群发[业主群]', 'senderArr' => $senderArr,'openUserids' => $openUserids, 'access_token' => $access_token, 'result' => $result
                    ], 'qiYeWeiXin/sendGroupMessageLog', 1);
                    $fail_list = [];
                    $msgidArr = [];
                    foreach ($openUserids as $sender) {
                        $postParams['sender'] = $sender;
                        $result = $workWeiXinRequestService->cgiBinExternalContactAddMsgTemplate($postParams, $access_token);
                        if (!is_array($result)) {
                            $result = json_decode($result, true);
                        }
                        fdump_api([
                            'title' => '群发[业主群]', 'item' => $item, 'postParams' => $postParams, 'access_token' => $access_token, 'result' => $result
                        ], 'qiYeWeiXin/sendGroupMessageLog', 1);
                        if (!is_array($result)) {
                            $result = json_decode($result, true);
                        }
                        if (isset($result['msgid']) && $result['msgid']) {
                            // 推送提醒
                            $queueData = [];
                            $queueData['jobType'] = 'workWeiXinGroupMsg';
                            $queueData['type'] = 'remind_group_msg_send';
                            $queueData['property_id'] = $property_id;
                            $queueData['msgid'] = $result['msgid'];
                            try{
                                $this->traitCommonWorkWeiXin($queueData, 30);
                            }catch (\Exception $e){
                                fdump_api(['title' => '推送提醒队列错误', 'err' => $e->getMessage()], 'qiYeWeiXin/sendGroupMessageErrLog',true);
                            }
                            $msgidArr[] = $result['msgid'];
                        }
                        if (isset($result['fail_list']) && ! empty($result['fail_list'])) {
                            $fail_list = array_merge($result['fail_list'], $fail_list);
                        }
                    }
                    $saveData = [
                        'send_status' => 2
                    ];
                    if (! empty($msgidArr)) {
                        $msgid = implode(',', $msgidArr);
                        $saveData['msgid'] = $msgid;
                    }
                    $db_village_qywx_message->saveOne(['id' => $item['id']], $saveData);
                    break;
                case 3:
                    // 3-企业成员
                    $db_village_qywx_message_detail = new VillageQywxMessageDetail();
                    $info = $db_house_enterprise_wx_bind->getOne(['bind_id' => $property_id, 'bind_type' => 0], 'agentid');
                    $agentid = $info['agentid'];
                    $content_info = $db_village_qywx_message_detail->getOne(['message_id' => $item['id']], true);
                    if ($content_info && !is_array($content_info)) {
                        $content_info = $content_info->toArray();
                    }
                    $content_info_type = isset($content_info['type']) ? intval($content_info['type']) : 0;
                    $content = isset($content_info['content']) ? trim($content_info['content']) : '';
                    if (empty($content)) {
                        fdump_api(['title' => '缺少具体发送内容[content]','line' => __LINE__, 'item' => $item], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                        continue;
                    }
                    $where_work = [];
                    $where_work[] = ['property_id','=',$property_id];
                    $where_work[] = ['status','=',1];
                    $where_work[] = ['qy_id','<>',''];
                    $where_work[] = ['qy_status','=',1];
                    $userid_list = explode(',', $item['sender']);
                    if (! $userid_list) {
                        fdump_api(['title' => '转换加密id缺少对象[sender]','line' => __LINE__, 'item' => $item], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                        continue;
                    }
                    $openUserids = (new WorkWeiXinNewService())->getCgiBinBatchUseridToOpenuserid($property_id, $userid_list, $where_work);
                    if (empty($openUserids)) {
                        fdump_api([
                            'title' => '转换加密id失败', 'item' => $item, 'property_id' => $property_id,'line' => __LINE__,
                            'userid_list' => $userid_list, 'where_work' => $where_work, 'openUserids' => $openUserids,
                        ], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                        $saveData = [
                            'send_status' => 4, 'send_res' =>'转换加密id失败[未发送成功]' ,
                            'success_send' => 0, 'fail_send' => 0
                        ];
                        $db_village_qywx_message->saveOne(['id' => $item['id']], $saveData);
                        continue;
                    }
                    $touser = implode('|', $openUserids);

                    $postParams = [];
                    switch ($content_info_type) {
                        case 1:
                            $postParams['touser'] = $touser;
                            $postParams['msgtype'] = 'text';
                            $postParams['agentid'] = $agentid;
                            $postParams['text'] = ['content' => $content];
                            break;
                        case 2:
                            $media_id = $workWeiXinNewService->uploadServiceMedia($content, $property_id, $village_id, $item['message_name']);
                            if (! $media_id) {
                                fdump_api(['title' => '上传临时素材失败', 'item' => $item, 'line' => __LINE__, 'media_id' => $media_id], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                                return [];
                            }
                            $postParams['touser'] = $touser;
                            $postParams['msgtype'] = 'image';
                            $postParams['agentid'] = $agentid;
                            $postParams['image'] = ['media_id' => $media_id];
                            break;
                        case 4:
                            $media_id = $workWeiXinNewService->uploadServiceMedia($content, $property_id, $village_id, $item['message_name'], 'video');
                            if (! $media_id) {
                                fdump_api(['title' => '上传临时素材失败', 'item' => $item,'line' => __LINE__, 'media_id' => $media_id], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                                return [];
                            }
                            $postParams['touser'] = $touser;
                            $postParams['msgtype'] = 'video';
                            $postParams['agentid'] = $agentid;
                            $postParams['video'] = [
                                'media_id' => $media_id,
                                'title' => $item['message_name'],
                            ];
                            break;
                        case 5:
                            $media_id = $workWeiXinNewService->uploadServiceMedia($content, $property_id, $village_id, $item['message_name'], 'file');
                            if (! $media_id) {
                                fdump_api(['title' => '上传临时素材失败', 'item' => $item,'line' => __LINE__, 'media_id' => $media_id], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                                return [];
                            }
                            $postParams['touser'] = $touser;
                            $postParams['msgtype'] = 'file';
                            $postParams['agentid'] = $agentid;
                            $postParams['file'] = [
                                'media_id' => $media_id,
                            ];
                            break;
                        default:
                            fdump_api(['title' => '发送消息类别有误[content_info_type]', 'item' => $item,'line' => __LINE__, 'content_info' => $content_info], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                            continue;
                    }
                    $result = (new WorkWeiXinRequestService())->cgiBinMessageSend($postParams, $access_token);
                    if (!is_array($result)) {
                        $result = json_decode($result, true);
                    }
                    fdump_api([
                        'title' => '群发[企业成员]', 'item' => $item, 'postParams' => $postParams, 'access_token' => $access_token, 'result' => $result
                    ], 'qiYeWeiXin/sendGroupMessageLog', 1);
                    $fail_list_json = [];
                    $invaliduser = isset($result['invaliduser']) && $result['invaliduser'] ? $result['invaliduser'] : [];
                    $invaliduserCount = 0;
                    if ($invaliduser && !is_array($invaliduser) && strpos($invaliduser, '|')) {
                        $invaliduserArr = explode('|', $invaliduser);
                        $fail_list_json = array_merge($invaliduserArr, $fail_list_json);
                        $invaliduserCount = count($invaliduserArr);
                    } elseif (is_array($invaliduser)) {
                        $invaliduserCount = count($invaliduser);
                    }
                    $openUseridsCount = count($openUserids);
                    $unlicenseduser = isset($result['unlicenseduser']) ? $result['unlicenseduser'] : '';
                    $msgid = isset($result['msgid']) ? $result['msgid'] : '';
                    $unlicenseduserCount = 0;
                    if ($unlicenseduser && !is_array($unlicenseduser) && strpos($unlicenseduser, '|')) {
                        $unlicenseduserArr = explode('|', $unlicenseduser);
                        $unlicenseduserCount = count($unlicenseduserArr);
                        $fail_list_json = array_merge($unlicenseduserArr, $fail_list_json);
                    } elseif (is_array($unlicenseduser)) {
                        $unlicenseduserCount = count($unlicenseduser);
                    }
                    $fail_send = $invaliduserCount + $unlicenseduserCount;
                    $success_send = $openUseridsCount - $fail_send;
                    $send_res = '预计发送' . $openUseridsCount . '个客户，实际发送' . $success_send . '个客户';
                    $send_status = 1;
                    if (empty($result) || intval($result['errcode']) > 0) {
                        $send_status = 4;
                        if (!empty($result) && $result['errmsg']) {
                            $send_res .= "[{$result['errmsg']}]";
                        } else {
                            $send_res .= "[发送失败]";
                        }
                    }
                    $saveData = [
                        'send_status' => $send_status, 'send_res' => $send_res,
                        'success_send' => $success_send, 'fail_send' => $fail_send
                    ];
                    if ($msgid) {
                        $saveData['msgid'] = $msgid;
                        // 推送提醒
                        $queueData = [];
                        $queueData['jobType'] = 'workWeiXinGroupMsg';
                        $queueData['type'] = 'remind_group_msg_send';
                        $queueData['property_id'] = $property_id;
                        $queueData['msgid'] = $msgid;
                        try{
                            $this->traitCommonWorkWeiXin($queueData, 30);
                        }catch (\Exception $e){
                            fdump_api(['title' => '推送提醒队列错误', 'err' => $e->getMessage()], 'qiYeWeiXin/sendGroupMessageErrLog',true);
                        }
                    }
                    if (! empty($fail_list_json)) {
                        $saveData['fail_list_json'] = json_encode($fail_list_json,JSON_UNESCAPED_UNICODE);
                    }
                    $db_village_qywx_message->saveOne(['id' => $item['id']], $saveData);
            }
        }
        return true;
    }

    /**
     * 获取发送的具体消息整合.
     */
    protected function commonMessageDetailHandle($item) {
        $property_id = isset($item['property_id']) ? intval($item['property_id']) : 0 ;
        $village_id = isset($item['village_id']) ? intval($item['village_id']) : 0 ;
        $db_village_qywx_message_detail = new VillageQywxMessageDetail();
        $workWeiXinNewService = new WorkWeiXinNewService();
        $content_list = $db_village_qywx_message_detail->getList(['message_id' => $item['id']], true);
        if ($content_list && !is_array($content_list)) {
            $content_list = $content_list->toArray();
        }
        if (empty($content_list)) {
            // 缺少具体发送信息跳过
            fdump_api(['title' => '缺少具体发送信息跳过','line' => __LINE__, 'item' => $item], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
            return [];
        }
        $text = [];
        $attachments = [];
        foreach ($content_list as $val) {
            $type = intval($val['type']);
            $content = trim($val['content']);
            if (! $content) {
                fdump_api(['title' => '缺少关键值[content]','line' => __LINE__, 'item' => $item], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                return [];
            }
            // 消息类型 1文本 2图片 3功能 4视频 5文件 6图文
            switch ($type) {
                case 1:
                    $text = ['content' => $val['content']];
                    break;
                case 2:
                    $media_id = $workWeiXinNewService->uploadServiceMedia($content, $property_id, $village_id, $val['message_name']);
                    if (! $media_id) {
                        fdump_api(['title' => '上传临时素材失败', 'item' => $item,'line' => __LINE__, 'media_id' => $media_id], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                        return [];
                    }
                    $attachments[]= [
                        'msgtype' => 'image',
                        'image' => [
                            'media_id' => $media_id,
                            'pic_url' => dispose_url($content),
                        ],
                    ];
                    break;
                case 3:
                    if (! isset($val['share_url']) || ! $val['share_url']) {
                        fdump_api(['title' => '缺少关键值[share_url]','line' => __LINE__, 'item' => $item], 'qiYeWeiXin/sendGroupMessageErrLog_'.$item['id']);
                        continue;
                    }
                    $link = [
                        'title' => $content,
                        'url' => $val['share_url'],
                    ];
                    if (isset($val['share_img']) && $val['share_img']) {
                        $link['picurl'] = $val['share_img'];
                    }
                    $attachments[]= [
                        'msgtype' => 'link',
                        'link' => $link,
                    ];
                    break;
            }
        }
        return [
            'text' => $text,
            'attachments' => $attachments,
        ];
    }

    /**
     * 提醒成员群发.
     * 企业和第三方应用可调用此接口，重新触发群发通知，提醒成员完成群发任务，24小时内每个群发最多触发三次提醒。
     */
    public function remindGroupMsgSend($params) {
        $msgid = isset($params['msgid']) && $params['msgid'] ? $params['msgid'] : '';
        $property_id = isset($params['property_id']) && $params['property_id'] ? intval($params['property_id']) : 0;
        if (! $msgid && ! $property_id) {
            return false;
        }
        $cacheTag  = WorkWeiXinConst::WORK_WEI_XIN_JOB_REDIS_TAG;
        $cacheKey = md5(json_encode(['property_id' => $property_id, 'msgid' => $msgid]));
        $remindRedis  = Cache::store('redis')->get($cacheKey);
        if ($remindRedis) {
            return true;
        }
        $param = [
            'msgid' => $msgid,
        ];
        $workWeiXinNewService = new WorkWeiXinNewService();
        $access_token = $workWeiXinNewService->commonGetAccessToken($property_id);
        $result = (new WorkWeiXinRequestService())->cgiBinExternalContactRemindGroupMsgSend($param, $access_token);
        if (isset($result['errcode']) && intval($result['errcode']) === 0) {
            Cache::store('redis')->tag($cacheTag)->set($cacheKey,$msgid, 28800);
        }
        return true;
    }
}