<?php

/**
 * 计划任务
 * Author: hengtingmei
 * Date Time: 2020/07/01 17:48
 */

namespace app\common\model\service\plan;

use app\common\model\db\ProcessPlanMsg;
use app\common\model\db\AppPushMsg;
use app\common\model\service\AppPushMsgService;

class PlanMsgService
{

    public $planMsgModel = null;
    public function __construct()
    {
        $this->planMsgModel = new ProcessPlanMsg();
    }
    /*
	 *  添加任务
	 *
	 *  param array 消息参数
	 *
	 *  param.type   1为短信通知，2为模板消息，3为图文消息，4为APP通知
	 *
	 *
	 *
	 *  param.content 不同的业务，不同的值，传数组
	 *
	 *  param.send_time  发送时间
	 *
	 *
	 *
	 *
	 */
    public function addTask($param, $is_sub = 0)
    {
        $data['type'] = $param['type'] ?? '';
        $data['label'] = $param['label'] ?? '';
        $data['label'] = is_array($data['label']) ? implode(',', $data['label']) : $data['label'];

        if (!is_array($param['content'])) {
            $param['content'] = array($param['content']);
        } else if (empty($param['content'][0])) {
            $param['content'] = array($param['content']);
        }
        $data['content'] = serialize($param['content']);

        if (isset($param['send_time']) && $param['send_time']) {
            $data['send_time'] = $param['send_time'];
        } else if (cfg('sms_send_type')) {        // 如果开启了计划任务推送消息，则需要消息类的优先发送给用户。
            $data['send_time'] = time() - 3600;
        } else {
            $data['send_time'] = time();
        }

        $data['add_time'] = time();

        //额外记录推送表，供配送员3.0使用http轮询查询使用
        $appPushMsg = [];
        $originContent = $param['content'][0];
        $tm = time();
        $serializeParam = serialize($data);
        if ($param['type'] == 4 && isset($originContent['from']) && $originContent['from'] == 'delivery') {
            foreach ($originContent['audience']['tag'] as $v) {
                $appPushMsg[] = [
                    'device_id' => $v,
                    'status' => 0,
                    'platform' => $originContent['platform'][0] ?? '',
                    'business_type' => $originContent['business_type'] ?? '',
                    'add_time' => $tm,
                    'data' => $serializeParam
                ];
            }
        }

        if ($param['type'] == 4 && $originContent['from'] == 'storestaff' && isset($originContent['business_type']) && $originContent['business_type']) {
            foreach ($originContent['audience']['tag'] as $v) {
                $appPushMsg[] = [
                    'device_id' => $v,
                    'status' => 0,
                    'platform' => $originContent['platform'][0] ?? '',
                    'business_type' => $originContent['business_type'] ?? '',
                    'add_time' => $tm,
                    'data' => $serializeParam
                ];
            }
        }

        if ($param['type'] == 4 && $originContent['from'] == 'seckill_remind' && isset($originContent['business_type']) && $originContent['business_type']) {
            foreach ($originContent['audience']['tag'] as $v) {
                $appPushMsg[] = [
                    'device_id' => $v,
                    'status' => 0,
                    'platform' => $originContent['platform'][0] ?? '',
                    'business_type' => $originContent['business_type'] ?? '',
                    'add_time' => $tm,
                    'data' => $serializeParam
                ];
            }
        }

        if ($appPushMsg) {
            (new AppPushMsgService())->addAll($appPushMsg);
            return false;
        }

        $task_id = $this->add($data);

        $param = array(
            'file' => 'msg',
            'plan_time' => $data['send_time'],
            'param' => array(
                'id' => $task_id,
            ),
        );
        (new PlanService())->addTask($param, $is_sub);

        return true;
    }

    public function addRewardTask($param,$is_sub=0){
        $data['type'] = $param['type'] ?? '';
        $data['label'] = $param['label'] ?? '';
        $data['label'] = is_array($data['label']) ? implode(',',$data['label']) : $data['label'];

        if(!is_array($param['content'])){
            $param['content'] = array($param['content']);
        }else if(empty($param['content'][0])){
            $param['content'] = array($param['content']);
        }
        $data['content'] = serialize($param['content']);

        if(isset($param['send_time']) && $param['send_time']){
            $data['send_time'] = $param['send_time'];
        }else if(cfg('sms_send_type')){		// 如果开启了计划任务推送消息，则需要消息类的优先发送给用户。
            $data['send_time'] = time() - 3600;
        }else{
            $data['send_time'] = time();
        }

        $data['add_time'] = time();

        //额外记录推送表，供配送员3.0使用http轮询查询使用
        $appPushMsg = [];
        $originContent = $param['content'][0];
        $tm = time();

        $serializeParam = serialize($data);

        if ($param['type'] == 4 && $originContent['from'] == 'deliverreward') {     // 打赏推送消息
            foreach ($originContent['audience']['tag'] as $k=>$v) {
                $appPushMsg[$k] = [
                    'device_id' => $v,
                    'status' => 0,
                    'platform' => $originContent['platform'][0] ?? '',
                    'business_type' => $originContent['business_type'] ?? '',
                    'add_time' => $tm,
                    'data' => $serializeParam
                ];

                (new AppPushMsg())->save($appPushMsg[$k]);
            }
        }elseif($param['type'] == 4 && $originContent['from'] == 'deliverreminder'){    // 催单推送消息
            foreach ($originContent['audience']['tag'] as $k=>$v) {
                $appPushMsg[$k] = [
                    'device_id' => $v,
                    'status' => 0,
                    'platform' => $originContent['platform'][0] ?? '',
                    'business_type' => $originContent['business_type'] ?? '',
                    'add_time' => $tm,
                    'data' => $serializeParam
                ];

                (new AppPushMsg())->save($appPushMsg[$k]);
            }
        }

        return true;
    }


    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $result = $this->planMsgModel->save($data);
        if (!$result) {
            return false;
        }

        return $this->planMsgModel->id;
    }
}
