<?php

/**
 * 店铺相关团购业务层
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/10 19:20
 */

namespace app\group\model\service;

use app\common\model\db\MerchantStore;
use app\foodshop\model\service\order\DiningOrderDetailService;
use app\group\model\db\GroupFoodshopPackage as GroupFoodshopPackageModel;
use app\group\model\db\GroupFoodshopPackageData as GroupFoodshopPackageDataModel;
use app\group\model\db\GroupOrder as GroupOrderModel;
use app\group\model\db\GroupPassRelation as GroupPassRelationModel;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\service\InternalCardMoneyBillService;
use Carbon\Carbon;
use think\facade\Db;


class GroupOrderService
{
    public $groupOrderModel = null;

    public function __construct()
    {
        $this->groupOrderModel = new GroupOrderModel();

    }

    /**餐饮套餐核销团购券
     * @param $param
     * @return string
     */
    public function packageVerification($param)
    {
        $condition = [];
        if (isset($param['group_pass']) && $param['group_pass'] !== '') {
            $condition[] = ['group_pass', '=', $param['group_pass']];
        }

        $group_pass_relation_detail = [];
        $group_order_detail = $this->getOne($condition, true);
        if ($group_order_detail) {
            if ($group_order_detail['verify_time'] > 0) {
                throw new \think\Exception(L_("团购券已核销"), 1003);
            }
            $type = 1;
        } else {//有可能是多张团购券
            $groupPassRelationModel = new GroupPassRelationModel();
            $group_pass_relation_detail = $groupPassRelationModel->getOne($condition, true);
            if ($group_pass_relation_detail) {
                if ($group_pass_relation_detail['verify_time'] > 0) {
                    throw new \think\Exception(L_("团购券已核销"), 1003);
                }
                $group_order_detail = $this->getOne($where = ['order_id' => $group_pass_relation_detail['order_id']], true);
            } else {
                throw new \think\Exception(L_("未查询到核销码购买的团购券"), 1003);
            }
            $type = 2;
        }

        $groupFoodshopPackage = (new GroupFoodshopPackageModel())->getSome($where = ['order_id' => $group_order_detail['order_id'], 'group_id' => $group_order_detail['group_id']]);
        if (empty($groupFoodshopPackage)) {
            throw new \think\Exception(L_("团购券暂无核销套餐"), 1003);
        } else {
            //团购券可核销的套餐
            $groupFoodshopPackage = $groupFoodshopPackage->toArray();
            $group_package_ids = array_column($groupFoodshopPackage, 'package_id');
        }

        $diningOrderDetailService = new DiningOrderDetailService();
        $where = [
            ['order_id', '=', $param['order_id']],
            ['package_id', '<>', '0'],
        ];
        //订单中套餐商品
        $order_package_list = $diningOrderDetailService->getOrderDetailByCondition($where);
        foreach ($order_package_list as $key => $order) {
            if ($order['package_num'] - $order['verificNum'] - $order['refundNum'] <= 0) {
                unset($order_package_list[$key]);
            }
        }
        if (empty($order_package_list)) {
            throw new \think\Exception(L_("订单中暂无套餐商品"), 1003);
        } else {
            $order_package_ids = array_unique(array_column($order_package_list, 'package_id'));
        }
        $package_ids = array_intersect($group_package_ids, $order_package_ids);
        if (empty($package_ids)) {
            throw new \think\Exception(L_("暂无可核销套餐"), 1003);
        } else {
            //默认核销第一个套餐
            $first_package_id = $package_ids[0];
            $order_package_list = array_column($order_package_list, NULL, 'package_id');
            $uniqueness_number = $order_package_list[$first_package_id]['uniqueness_number'];
        }
        $where = [
            ['order_id', '=', $param['order_id']],
            ['uniqueness_number', '=', $uniqueness_number],
        ];
        //订单中套餐商品
        $order_package_info = $diningOrderDetailService->getOne($where);
        $where = [
            ['order_id', '=', $param['order_id']],
            ['package_id', '=', $order_package_info['package_id']],
            ['uniqueness_number', '=', $order_package_info['uniqueness_number']],
        ];
        //核销套餐商品
        $verific_package_list = $diningOrderDetailService->getOrderDetailByCondition($where);

        Db::startTrans();
        try {
            $add = [];
            $add['order_id'] = $param['order_id'];
            $add['group_id'] = $group_order_detail['group_id'];
            $add['price'] = $group_order_detail['price'];
            $add['package_id'] = $order_package_info['package_id'];
            $add['uniqueness_number'] = $order_package_info['uniqueness_number'];
            $add['group_pass'] = $param['group_pass'];
            $add['create_time'] = time();
            $groupFoodshopPackageDataModel = new GroupFoodshopPackageDataModel();
            $add_status = $groupFoodshopPackageDataModel->add($add);

            if ($type == 1) {//一张团购券核销券
                $updata = [];
                $updata['status'] = 2;
                $updata['use_time'] = $_SERVER['REQUEST_TIME'];
                $updata['verify_time'] = $_SERVER['REQUEST_TIME'];
                $update_status = $this->groupOrderModel->updateThis($where = ['order_id' => $group_order_detail['order_id']], $updata);
            } else {//多张团购券多张核销券
                $updata = [];
                $updata['status'] = 1;
                $updata['verify_time'] = $_SERVER['REQUEST_TIME'];
                $update_status = $groupPassRelationModel->updateThis($where = ['id' => $group_pass_relation_detail['id']], $updata);
            }

            // 表前缀
            $prefix = config('database.connections.mysql.prefix');

            // 修改商品核销数量
            foreach ($verific_package_list as $value) {
                $where = [
                    'id' => $value['id']
                ];
                Db::table($prefix . 'dining_order_detail')->where($where)->inc('verificNum', 1)->update();
            }

            if ($add_status && $update_status) {
                Db::commit();
                // 计算未验证的验证码数量
                $now_order = $group_order_detail;
                $pass_num = $now_order['pass_num'] > 1 ? intval($now_order['pass_num']) : 1; // 一份团购 对应生成核销券 份数
                $groupPassRelationModel = new GroupPassRelationModel();
                $consume_num = $groupPassRelationModel->getCount($where = ['order_id' => $now_order['order_id'], 'status' => 1]);
                $unconsume_pass_num = $now_order['num'] * $pass_num - $consume_num;
                $now_order['unconsume_pass_num'] = $unconsume_pass_num; // 未验证核销码数量

                //验证增加商家余额
                $now_order['order_type'] = 'group';
                $now_order['verify_all'] = 1;
                $now_order['desc'] = L_('用户购买X1记入收入', $now_order['order_name']);
                invoke_cms_model('SystemBill/bill_method', [$now_order['is_own'], $now_order]);

                $msg = '核销成功';
            } else {
                $msg = '核销失败';
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $msg = $e->getMessage();
            throw new \think\Exception(L_($msg), 1003);
        }
        return $msg;
    }

    /**
     * 根据条件一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $field)
    {
        if (empty($where)) {
            return [];
        }

        $rs = $this->groupOrderModel->getOne($where, $field);
        if (!$rs) {
            return [];
        }

        return $rs->toArray();
    }

    public function merchantStoreStatistic($storeId, $date)
    {
        $time = Carbon::parse($date);
        $startTime = $time->startOfDay()->timestamp;
        $endTime = $time->endOfDay()->timestamp;
        //总支付金额，平台余额支付金额， 支付订单数量
        $payMoney = GroupOrderModel::where([
            ['store_id', '=', $storeId],
            ['paid', '=', 1],
            ['pay_time', '>=', $startTime],
            ['pay_time', '<=', $endTime],
        ])
            ->field(
                'SUM(price) pay_money,
                SUM(balance_pay) balance_pay_money,
                SUM(refund_money) refund_money,
                count(*) cnt'
            )
            ->find();

        //支付宝支付金额
        $aliPayMoney = GroupOrderModel::alias('o')
            ->leftJoin('pay_order_info p', "o.order_id = p.business_order_id AND p.business = 'group'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'alipayh5, alipay'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //微信支付金额
        $weixinPayMoney = GroupOrderModel::alias('o')
            ->leftJoin('pay_order_info p', "o.order_id = p.business_order_id AND p.business = 'group'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.paid', '=', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'weixinh5, weixin'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //一卡通支付金额
        $cardMoney = InternalMembershipCardLog::where([
            ['store_id', '=', $storeId],
            ['operation_type', '=', 2],//支付
            ['business_type', 'IN', 'group'],
            ['create_time', '>=', $startTime],
            ['create_time', '<=', $endTime],
        ])
            ->field('SUM(money) pay_money')
            ->find();
        
        $allPayMoney = bcsub(
            $cardMoney['pay_money'] + $payMoney['balance_pay_money'] + $aliPayMoney['pay_money'] + $weixinPayMoney['pay_money'],
            $payMoney['refund_money'],
            2);
        
        $data = [
            'mer_id' => MerchantStore::where(['store_id' => $storeId, 'status' => 1])->value('mer_id'),
            'store_id' => $storeId,
            'order_type' => 'group',
            'bill_date' => $date,
            'pay_money' => $allPayMoney,
            'pay_order_count' => $payMoney['cnt'],
            'internal_card_pay_money' => $cardMoney['pay_money'] ?: 0,
            'balance_pay_money' => $payMoney['balance_pay_money'] ?: 0,
            'alipay_pay_money' => $aliPayMoney['pay_money'] ?: 0,
            'wechat_pay_money' => $weixinPayMoney['pay_money'] ?: 0,
            'refund_money' => $payMoney['refund_money'] ?: 0,
        ];

        return (new InternalCardMoneyBillService())->saveMerchantStoreDateBill($data);
    }
}