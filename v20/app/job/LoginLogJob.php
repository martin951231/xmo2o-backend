<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */

	namespace app\job;

	use app\common\model\service\AdminLoginLogService;
	use app\community\model\service\UserLogExtendService;
	use think\Exception;
	use think\queue\Job;

	class LoginLogJob
	{
		/**
		 * 总后台用户登录日志
		 * @param Job $job
		 * @param     $data
		 */
		public function adminLoginLog(Job $job,$data)
		{
			if ($job->attempts() >= 1){
				$job->delete();
			}

			(new AdminLoginLogService())->addLog($data);
		}

		/**
		 * 社区 PC 普通用户登录日志
		 * @param Job $job
		 * @param     $data
		 */
		public function userLoginLog(Job $job ,$data)
		{
			if ($job->attempts() >= 1){
				$job->delete();
			}

			(new UserLogExtendService())->addLog($data);
		}


		public function failed($data)
		{
		}
	}