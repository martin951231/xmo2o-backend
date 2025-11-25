<?php

/**
 * app消息轮询超时写入到推送表
 *
 * @author: 张涛
 * @date: 2020/10/13
 */
class plan_app_push_expire extends plan_base
{
	public function runTask()
	{
		$appPushMsgMod = M('App_push_msg');
		$planMsgMod = M('Process_plan_msg');
		$tm = time();
		$where = [
			'add_time' => ['LT', $tm - 30],
			'status' => 0
		];
		$expireMsg = $appPushMsgMod->where($where)->limit(50)->order('id DESC')->select();

		import('@.ORG.plan');
		$plan_class = new plan();
		foreach ($expireMsg as $value) {
			$data = unserialize($value['data']);
			$taskId = $planMsgMod->add($data);
			$param = array(
				'file' => 'msg',
				'plan_time' => $data['send_time'],
				'param' => array(
					'id' => $taskId,
				),
			);
			$plan_class->addTask($param, 0);

			$appPushMsgMod->where(['id' => $value['id']])->save(['status' => 1, 'send_type' => 'push', 'send_time' => $tm]);
		}
		return true;
	}
}
