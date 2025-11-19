<?php

	/**
	 * ======================================================
	 * Desc: 智慧平安小区每10分钟自动检测住户出入数据推送
	 *  ======================================================
	 */
	namespace app\task;

	use yunwuxin\cron\Task;

	class ChinapopinAutoTask extends Task
	{
		public function configure ()
		{
            // 10分钟执行一次
            $this->everyTenMinutes();
		}

		public function execute ()
		{
            try {
                invoke_cms_model('House_village_third_user_info/allPersonInoutInfo');
            }catch (Exception $e){
                fdump_api([$e->getFile(), $e->getLine(), $e->getMessage()], 'task/ChinapopinAutoTask');
            }
		}
	}