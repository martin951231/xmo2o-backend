<?php
/**
 * MallOrderAutoTakingService.php
 * 店员端自动接单计划任务
 * Create on 2020/11/4 14:00
 * Created by zhumengqun
 */

namespace app\common\model\service\plan\file;

use app\mall\model\db\MallOrder;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\MallRiderService;
use think\facade\Db;

class MallOrderAutoTakingService
{
    /**
     * @param $order_id
     * 自动接单计划任务
     */
    public function runTask()
    {
        $this->mallOrderService = new MallOrderService();
        if (cfg('mall_platform_order_taking_open') == 1) {
            $time = cfg('mall_order_auto_taking_time') * 60;
            $where = [
                ['status', '=', 10],
                ['create_time', '<', time() - $time],
            ];
            // 启动事务
            Db::startTrans();
            try {
                $order_ids = (new MallOrder())->getSome('order_id,goods_activity_type', $where);
                if (!empty($order_ids)) {
                    foreach ($order_ids as $val) {
                        if ($val['goods_activity_type'] == 'periodic') {
//                            $periodicInfo = (new MallActivityService())->returnNowPeriodicOrderAndActBefore($val['order_id'], 3);
//                            if (!empty($periodicInfo)) {
//                                foreach ($periodicInfo as $v) {
//                                    $note = '第' . $v['current_periodic'] . '期已自动接单，当前处于该笔订单正在备货中';
//                                    (new MallOrderService())->orderTaking($val['order_id'], $v['purchase_order_id'], $v['current_periodic'], $v['periodic_count'], $note);
//                                }
//                            }
                        } else {
                            //把状态置成11  记录日志
                            $note = '已自动接单，该笔订单正在备货中';
                            (new MallOrderService())->changeOrderStatus($val['order_id'], 11, $note);
                            //骑手纪录店员接单
                            (new MallRiderService())->addRecord('order', $val['order_id'], 0, '店员已接单', '', '', []);
                        }
                    }
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        }
    }
}