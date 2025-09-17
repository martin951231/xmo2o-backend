<?php
/**
 * 自动确认收货
 */

namespace app\common\model\service\plan\file;

use app\common\model\service\order\SystemOrderService;
use app\common\model\service\UserService;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\MallOrderLog;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\MallOrderService;
use think\facade\Db;

class MallOrderAutoConfirmReceiving
{
    /**
     * @param $order_id
     * 自动确认收货
     */
    public function runTask()
    {
        //加个缓存避免重复执行
        $cacheKey = 'MallOrderAutoConfirmReceivingTask';
        if (cache($cacheKey)) {
            return true;
        }
        cache($cacheKey, 1, 25);

        $autoTime = cfg('mall_order_finished_time') * 24 * 60 * 60;
        $nowTime = time();
        $distenceTiem = $nowTime - $autoTime;
        //发货后算起 超出后台配置的自动确认收货时间后计划任务自动确认收货
        $where = [['last_uptime', '<=', $distenceTiem], ['status', '>=', 20], ['status', '<', 30]];
        $arr = (new MallOrder())->getSome('uid,money_real,order_id,goods_activity_type,mer_id,store_id', $where);
        if (!empty($arr)) {
                foreach ($arr as $val) {
                    Db::startTrans();
                    try {
                        $updateOrder = ['status' => 30, 'last_uptime' => time(), 'complete_time' => time()];
                        //更新主订单
                        (new MallOrder())->updateThis(['order_id' => $val['order_id']], $updateOrder);
                        //更新子订单
                        $update = ['status' => 30, 'last_uptime' => time()];
                        (new MallOrderDetail())->updateThis(['order_id' => $val['order_id']], $update);
                        //更新周期购
                        if ($val['goods_activity_type'] == 'periodic') {
                            (new MallActivityService())->updateSurePeriodic($val['order_id']);
                        }
                        //给商家加余额
                        (new SystemOrderService)->completeOrder('mall3', $val['order_id']);
                        (new MallOrderService())->merchantAddMoney($val['order_id']);
                        //更新订单日志
                        $data = ['order_id' => $val['order_id'], 'note' => '计划任务自动确认收货', 'status' => 30, 'addtime' => time()];
                        (new MallOrderLog())->addOne($data);
                        if (cfg('user_score_get') > 0) { //积分设置--消费1元获得积分数
                            if (cfg('open_score_get_percent')) { //百分比
                                $score = round($val['money_real'] * cfg('user_score_get') / 100);
                            } else { //积分
                                $score = round(cfg('user_score_get') * $val['money_real']);
                            }
                            (new UserService())->addScore($val['uid'], $score, '商城消费获得积分', 0, [
                                'mer_id' => $val['mer_id'],
                                'store_id' => $val['store_id'],
                                'order_id' => $val['order_id'],
                                'order_type' => 'mall',
                            ]);
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        fdump_api([$val['order_id'], $e->getFile(), $e->getMessage()], 'MallOrderAutoConfirmReceivingErr', true);
                        Db::rollback();
                    }
                }
        }
        cache($cacheKey, null);
    }
}