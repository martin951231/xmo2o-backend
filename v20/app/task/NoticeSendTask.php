<?php

	/**
	 * 系统后台-小区新闻微信模板消息推送
	 */
	namespace app\task;
    use app\community\model\service\HouseVillageNewsService;
    use yunwuxin\cron\Task;

	class NoticeSendTask extends Task
	{
		public function configure ()
		{
            $this->everyMinute();
		}

		public function execute ()
		{
            try {
                $houseVillageNewsService = new HouseVillageNewsService();
                $houseVillageNewsService->sendMsg(); //微信模板消息推送、app消息推送
                $houseVillageNewsService->clearRecord(); //清除1周前的已操作的记录
            }catch (Exception $e){
                fdump_api([$e->getMessage(),$e->getFile(),$e->getLine()],'NoticeSendTask',1);
            }
		}
	}