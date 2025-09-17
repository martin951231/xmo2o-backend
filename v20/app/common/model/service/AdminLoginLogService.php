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

	namespace app\common\model\service;
	use app\common\model\db\AdminLogExtend;

	class AdminLoginLogService
	{
		public function addLog($data)
		{
			return (new AdminLogExtend())->insert($data);
		}

	}