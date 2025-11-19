<?php
	/**
	 * ======================================================
	 * Author: cc
	 * Created by PhpStorm.
	 * Copyright (c)  cc Inc. All rights reserved.
	 * Desc: 每 分钟执行一次的任务
	 *  ======================================================
	 */

	namespace app\task;
    use think\Exception;
	use yunwuxin\cron\Task;
    use app\community\model\service\HouseVillageRobleasePositionOrderService;
    use app\community\model\service\HouseNewPayOrderService;
	class EveryOneMinuteSysTask extends Task
	{
		public function configure ()
		{
            $this->everyMinute();  //每分钟
		}

		public function execute ()
		{
			try {
                $houseVillageRobleasePositionOrderService= new HouseVillageRobleasePositionOrderService();
                $houseVillageRobleasePositionOrderService->autoCancelPositionOrder();
                $houseNewPayOrderService=new HouseNewPayOrderService();
                /*** 客户不要这个定制功能了 先注释掉
                $houseNewPayOrderService->handle_room_water_balance_order();
                **/
			}catch (Exception $e){
                //fdump_api(['type'=>'每分钟','err_msg'=>$e->getMessage(),'err_file'=>$e->getFile(),'err_line'=>$e->getLine()],'00EveryOneMinuteSysTask',1);
			}
		}
	}