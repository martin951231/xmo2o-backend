<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      发送消息
 */
namespace app\community\model\service\message;

use app\community\model\db\pushMessage\CommunityPushMessage;
use app\community\model\db\pushMessage\CommunityPushMessageClassify;
use app\consts\MessageConst;

class PushMessageHandleService
{
    public function messageClassifyArr() {
        return [
            MessageConst::CONTRACT_TO_WORK => [
                'classify_name' => '合同通知',
                'classify_mark' => MessageConst::CONTRACT_TO_WORK,
                'classify_des' => '合同到期提醒',
            ],
        ];
    }
    
    public function messageBlockBox($classify_mark) {
        $box = [
            MessageConst::CONTRACT_TO_WORK => [
                'href' => '',
                'mark' => MessageConst::CONTRACT_TO_WORK,
                'title' => '合同到期提醒',
                'contract_id' => 0,
                'content' => [
                    ['label' => '合同编号', 'value' => '', 'keywords' => 'contract_number'],
                    ['label' => '合同名称', 'value' => '', 'keywords' => 'contract_name'],
                    ['label' => '合同到期时间', 'value' => '', 'keywords' => 'contract_end_time'],
                ],
            ],
        ];
        return isset($box[$classify_mark]) ? $box[$classify_mark] : [];
    }

    /**
     * 获取推送消息分类.
     */
    public function getPushMessageClassify($classify_mark = '') {
        !$classify_mark && $classify_mark = MessageConst::CONTRACT_TO_WORK;
        $db_community_push_message_classify = new CommunityPushMessageClassify();
        $where = [];
        $where[] = ['classify_mark', '=', $classify_mark];
        $classifyInfo = $db_community_push_message_classify->getOne($where);
        if ($classifyInfo && !is_array($classifyInfo)) {
            $classifyInfo = $classifyInfo->toArray();
        }
        !$classifyInfo && $classifyInfo = [];
        if (!isset($classifyInfo['id']) || !$classifyInfo['id']) {
            $messageClassifyArr = $this->messageClassifyArr();
            if (isset($messageClassifyArr[$classify_mark]) && $messageClassifyArr[$classify_mark]) {
                $classifyInfo = $messageClassifyArr[$classify_mark];
                $classifyInfo['add_time'] = time();
                $id = $db_community_push_message_classify->add($classifyInfo);
                $classifyInfo['id'] = $id;
            }
        }
        return $classifyInfo;
    }

    /**
     * 发送合同消息应用消息.
     */
    public function sendContractMessage($param) {
        $classify_mark = MessageConst::CONTRACT_TO_WORK;
        $classify_id = isset($param['send_classify_id']) ? intval($param['send_classify_id']) : 0;
        if (!$classify_id){
            $classifyInfo = $this->getPushMessageClassify($classify_mark);
            $classify_id = isset($classifyInfo['id']) ? intval($classifyInfo['id']) : 0;
        }
        $param['classify_mark'] = $classify_mark;
        $param['classify_id'] = $classify_id;
        fdump_api(['params' => $param], '$sendContractMessage', 1);
        return $this->sendMessage($param);
    }

    /**
     * 发送内部应用消息.
     */
    public function sendMessage($param) {
        $classify_mark = isset($param['classify_mark']) ? trim($param['classify_mark']) : '';
        $classify_id = isset($param['classify_id']) ? intval($param['classify_id']) : 0;
        $send_to = isset($param['send_to']) ? trim($param['send_to']) : '';
        $send_id = isset($param['send_id']) ? intval($param['send_id']) : 0;
        $send_name = isset($param['send_name']) ? trim($param['send_name']) : '';
        $message_url = isset($param['message_url']) ? trim($param['message_url']) : '';
        $send_time = isset($param['send_time']) ? intval($param['send_time']) : time();
        $message_belong = isset($param['message_belong']) ? trim($param['message_belong']) : '';
        $belong_id = isset($param['belong_id']) ? intval($param['belong_id']) : 0;
        $send_content = $this->messageBlockBox($classify_mark);
        if (empty($send_to) || !$send_id || !$classify_id || empty($send_content)) {
            // 缺少推送消息必备条件参数
            fdump_api(['$param' => $param], '$sendContractMessage', 1);
            return false;
        }
        foreach ($send_content as $key1 => $item1) {
            if (isset($param[$key1]) && $param[$key1]) {
                $send_content[$key1] = $param[$key1];
            }
            if ($key1 == 'href' && !$send_content[$key1] && $message_url) {
                $send_content[$key1] = $message_url;
            } elseif ($key1 == 'href' && $send_content[$key1] && !$message_url) {
                $message_url = $send_content[$key1];
            }
            if ($key1 == 'content') {
                foreach ($item1 as $key2 => $item2) {
                    $keywords = isset($item2['keywords']) ? trim($item2['keywords']) : 'keyword' . $key2;
                    $value = isset($param[$keywords]) ? $param[$keywords] : '';
                    $send_content[$key1][$key2]['value'] = $value;
                    if (isset($send_content[$key1][$key2]['keywords'])) {
                        unset($send_content[$key1][$key2]['keywords']);
                    }
                }
            }
        }
        $data = [
            'classify_id' => $classify_id, 'send_to' => $send_to, 'send_id' => $send_id, 'send_name' => $send_name,
            'send_content' => json_encode($send_content, JSON_UNESCAPED_UNICODE), 'message_url' => $message_url,
            'send_time' => $send_time, 'message_belong' => $message_belong, 'belong_id' => $belong_id,
        ];
        $db_community_push_message = new CommunityPushMessage();
        fdump_api(['$data' => $data], '$sendContractMessage', 1);
        return $db_community_push_message->add($data);
    }

    /**
     * @param $work_id
     * @return int
     */
    public function getHouseWorkMessageNum($work_id) {
        $db_community_push_message = new CommunityPushMessage();
        $nowTime = time();
        $where = [];
        $where[] = ['send_to', '=', MessageConst::SEND_TO_HOUSE_WORK];
        $where[] = ['send_id', '=', $work_id];
        $where[] = ['send_time', '<=', $nowTime];
        $where[] = ['view_time', '=', 0];
        return $db_community_push_message->getCount($where);
    }
    
    public function getHouseWorkClassify() {
        return [
            MessageConst::CONTRACT_TO_WORK,
        ];
    }

    /**
     * 获取消息类型.
     */
    public function getHouseWorkMessageClassifyList($work_id) {
        $db_community_push_message_classify = new CommunityPushMessageClassify();
        $db_community_push_message = new CommunityPushMessage();
        $classify_mark_arr = $this->getHouseWorkClassify();
        $where = [];
        $where[] = ['classify_mark', 'in', $classify_mark_arr];
        $messageClassifyList = $db_community_push_message_classify->getList($where, 'id, classify_name, classify_des, add_time');
        if ($messageClassifyList && !is_array($messageClassifyList)) {
            $messageClassifyList = $messageClassifyList->toArray();
        }
        !$messageClassifyList && $messageClassifyList = [];
        $nowTime = time();
        $http = cfg('site_url').'/v20/public/static/community/images/statistics_column/';
        foreach ($messageClassifyList as &$message) {
            $classify_id = isset($message['id']) ? intval($message['id']) : 0;
            $whereCount = [];
            $whereCount[] = ['classify_id', '=', $classify_id];
            $whereCount[] = ['send_to', '=', MessageConst::SEND_TO_HOUSE_WORK];
            $whereCount[] = ['send_id', '=', $work_id];
            $whereCount[] = ['send_time', '<=', $nowTime];
            $whereCount[] = ['view_time', '=', 0];
            $messageNew = $db_community_push_message->getOne($whereCount, 'send_time', 'send_time DESC');
            $message_time =  isset($messageNew['send_time']) ? intval($messageNew['send_time']) : $message['add_time'];
            if (isset($message['add_time'])) {
                unset($message['add_time']);
            }
            $message_time = date('Y-m-d H:i:s', $message_time);
            $message['message_time'] = $message_time;
            $messageCount = $db_community_push_message->getCount($whereCount);
            $message['count'] = $messageCount;
            $message['ico'] = $http . 'message_contract.png';
        }
        return $messageClassifyList;
    }

    public function getHouseWorkMessageCount($work_id, $classify_id, $params = []) {
        $db_community_push_message = new CommunityPushMessage();
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
        $where = [];
        $where[] = ['classify_id', '=', $classify_id];
        $where[] = ['send_to', '=', MessageConst::SEND_TO_HOUSE_WORK];
        $where[] = ['send_id', '=', $work_id];
        $where[] = ['send_time', '<=', $nowTime];
        $where[] = ['delete_time', '=', 0];
        return $db_community_push_message->getCount($where);
    }
    
    /**
     * 获取消息.
     */
    public function getHouseWorkMessage($work_id, $classify_id, $params = []) {
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 15;
        $field = isset($params['field']) && trim($params['field']) ? trim($params['field']) : '';
        !$field && $field = 'id, classify_id, send_to, send_id, send_name, send_content, message_url, send_time';
        $db_community_push_message = new CommunityPushMessage();
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
        $where = [];
        $where[] = ['classify_id', '=', $classify_id];
        $where[] = ['send_to', '=', MessageConst::SEND_TO_HOUSE_WORK];
        $where[] = ['send_id', '=', $work_id];
        $where[] = ['send_time', '<=', $nowTime];
        $where[] = ['delete_time', '=', 0];
        $list = $db_community_push_message->getList($where, $field, 'send_time DESC, id DESC', $page, $pageSize);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        !$list && $list = [];
        foreach ($list as &$item) {
            $message_url = isset($item['message_url']) && $item['message_url'] ? $item['message_url'] : '';
            $item['send_content'] = isset($item['send_content']) && $item['send_content'] ? json_decode($item['send_content'], true) : [];
            if ($message_url && (!isset($item['send_content']['href']) || !$item['send_content']['href'])) {
                $item['send_content']['href'] = $message_url;
            }
            if (isset($item['send_time']) && $item['send_time']) {
                $item['send_time_text'] = date('Y-m-d H:i:s', $item['send_time']);
            }
        }
        return $list;
    }

    /**
     * 标记消息为已读.
     */
    public function viewWorkMessage($work_id, $message_id) {
        $where = [];
        $where[] = ['id', '=', $message_id];
        $where[] = ['send_to', '=', MessageConst::SEND_TO_HOUSE_WORK];
        $where[] = ['send_id', '=', $work_id];
        $where[] = ['view_time', '=', 0];
        $db_community_push_message = new CommunityPushMessage();
        $info = $db_community_push_message->getOne($where, 'id');
        $nowTime = time();
        if (isset($info['id']) && $info['id']) {
            $db_community_push_message->updateThis($where, ['view_time' => $nowTime]);
        }
        return $nowTime;
    }

    /**
     * 标记消息为已删除.
     */
    public function deleteWorkMessage($work_id, $message_id) {
        $where = [];
        $where[] = ['id', '=', $message_id];
        $where[] = ['send_to', '=', MessageConst::SEND_TO_HOUSE_WORK];
        $where[] = ['send_id', '=', $work_id];
        $where[] = ['delete_time', '=', 0];
        $db_community_push_message = new CommunityPushMessage();
        $info = $db_community_push_message->getOne($where, 'id');
        $nowTime = time();
        if (isset($info['id']) && $info['id']) {
            $db_community_push_message->updateThis($where, ['delete_time' => $nowTime]);
        }
        return $nowTime;
    }
}