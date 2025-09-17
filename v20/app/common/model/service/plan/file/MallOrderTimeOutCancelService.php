<?php
/**
 * 新版商城订单超时取消订单
 * Created by lumin
 */

namespace app\common\model\service\plan\file;

use app\common\model\service\user\UserNoticeService;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\MallOrderCombine;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\MallOrderCombineService;
use app\mall\model\service\activity\MallActivityService;
use think\facade\Db;

class MallOrderTimeOutCancelService
{
    /**
     * @param $order_id
     */
    public function runTask()
    {	
    	$MallOrderService = new MallOrderService;
    	$timeout = time() - cfg('mall_order_obligations_time')*60;
    	$where = [
    		['status', '=', 0],
    		['create_time', '<', $timeout]
    	];
    	$MallOrder = new MallOrder;
        $data = $MallOrder->getSome('order_id', $where, 'order_id asc', 0, 5);
    	if($data){
            fdump_sql(['data'=>$data,'sql'=>$MallOrder->getLastSql()],"MallOrderDataAutoCancel");
    		foreach ($data as $key => $value) {
	    		try{
                    $order_id = $value['order_id'];
                    $MallOrderService->autoCancelOrder($order_id, 51, '超时取消订单（计划任务）');
                } catch (\Exception $e) {
	    		    fdump_sql(['msg'=>"订单ID：".$order_id."：错误：".$e->getMessage()],"lumin01044MallOrderTimeOutCancel");
                    fdump("订单ID：".$order_id.$e->getMessage(),"lumin01044",1);
                }
	    	}
    	}
        return true;
    }

}