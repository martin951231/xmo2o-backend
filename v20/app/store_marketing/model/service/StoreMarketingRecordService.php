<?php
/**
 * liuruofei
 * 2021/09/13
 * 分销员改价
 */
namespace app\store_marketing\model\service;

use app\common\model\db\User;
use app\common\model\db\UserMoneyList;
use app\group\model\db\Group;
use app\group\model\db\GroupOrder;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallOrder;
use app\store_marketing\model\db\StoreMarketingPerson;
use app\store_marketing\model\db\StoreMarketingPersonStore;
use app\store_marketing\model\db\StoreMarketingRatio;
use app\store_marketing\model\db\StoreMarketingRecord;
use app\store_marketing\model\db\StoreMarketingShareLog;

class StoreMarketingRecordService
{

    /**
     * 保存分销记录
     * @param int $share_id 分享记录id
     * @param int $store_id 店铺id
     * @param int $order_id 订单id
     * @param int $uid 下单用户uid
     * @param int $goods_id 商品ID
     * @param int $goods_type 商品类型:1=团购,2=新版商城
     * @param int $goods_name 商品名称
     * @param int $goods_num 商品数量
     * @param int $pay_money 下单商品金额
     * @param int $create_time 下单时间
     */
    public function addRecord($param)
    {
        $data = [
            'order_id' => $param['order_id'],
            'uid' => $param['uid'],
            'store_id' => $param['store_id'],
            'goods_id' => $param['goods_id'],
            'goods_type' => $param['goods_type'],
            'goods_name' => $param['goods_name'],
            'goods_num' => $param['goods_num'],
            'pay_money' => $param['pay_money'],
            'create_time' => $param['create_time']
        ];
        $data['person_id'] = (new StoreMarketingShareLog())->where(['id' => $param['share_id']])->value('person_id');
        $personData = (new StoreMarketingPersonStore())->where([['person_id', '=', $data['person_id']], ['store_id', '=', $data['store_id']], ['is_del', '=', 0]])->find();
        $data['ratio'] = 0;
        if ($personData) {
            if ($personData['ratio_type'] == 1) {//统一比例
                $data['ratio'] = $personData['ratio'];
            } else if ($personData['ratio_type'] == 2) {//商品比例
                if ($data['goods_type'] == 1) {//团购商品
                    $data['ratio'] = (new StoreMarketingRatio())->where([['goods_id', '=', $data['goods_id']], ['person_id', '=', $data['person_id']], ['type', '=', 1], ['is_del', '=', 0]])->value('ratio');
                    if (empty($data['ratio'])) {
                        $data['ratio'] = (new Group())->where(['group_id' => $data['goods_id']])->value('marketing_ratio');
                    }
                } else if ($data['goods_type'] == 2) {//新版商城商品
                    $data['ratio'] = (new StoreMarketingRatio())->where([['goods_id', '=', $data['goods_id']], ['person_id', '=', $data['person_id']], ['type', '=', 2], ['is_del', '=', 0]])->value('ratio');
                    if (empty($data['ratio'])) {
                        $data['ratio'] = (new MallGoods())->where(['goods_id' => $data['goods_id']])->value('marketing_ratio');
                    }
                }
            }
            $data['percentage'] = $data['pay_money'] * $data['ratio'] / 100;
            (new StoreMarketingRecord())->insert($data);
        }
        return true;
    }

    /**
     * 到账
     * @param int $order_id 订单id
     * @param int $goods_type 商品类型:1=团购,2=新版商城
     */
    public function doArrival($param)
    {
        $recordList = (new StoreMarketingRecord())->where([
            ['order_id', '=', $param['order_id']],
            ['goods_type', '=', $param['goods_type']],
            ['is_arrival', '=', 0]
        ])->select();
        if ($recordList) {
            $time = time();
            $recordList = $recordList->toArray();
            foreach ($recordList as $v) {
                $uid = (new StoreMarketingPerson())->where(['id' => $v['person_id']])->value('uid');
                (new User())->setInc(['uid' => $uid], 'now_money', $v['percentage']);
                (new UserMoneyList())->insert([
                    'uid' => $uid,
                    'type' => 1,
                    'money' => $v['percentage'],
                    'time' => $time,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'desc' => '分销员分享商品到账',
                    'ask_id' => $v['id']
                ]);
                (new StoreMarketingRecord())->where(['id' => $v['id']])->update([
                    'is_arrival' => 1,
                    'arrival_time' => $time
                ]);
                (new StoreMarketingPersonStore())->setInc([['person_id', '=', $v['person_id']], ['store_id', '=', $v['store_id']]], 'all_achievement', $v['pay_money']);
                (new StoreMarketingPersonStore())->setInc([['person_id', '=', $v['person_id']], ['store_id', '=', $v['store_id']]], 'all_percentage', $v['percentage']);
            }
        }
        return true;
    }

    /**
     * 删除退款订单的记录（后台分销记录列表接口请求和用户端佣金记录接口请求）
     */
    public function delRecord()
    {
        $list = (new StoreMarketingRecord())->where([
            ['is_arrival', '=', 0],
            ['is_del', '=', 0]
        ])->select();
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $v) {
                if ($v['goods_type'] == 2) {
                    $order_status = (new MallOrder())->where(['order_id' => $v['order_id']])->value('status');
                    if (in_array($order_status, [50,51,52,60,70])) {
                        (new StoreMarketingRecord())->where(['id' => $v['id']])->update(['is_del' => 1]);
                    }
                } else if ($v['goods_type'] == 1) {
                    $order_status = (new GroupOrder())->where(['order_id' => $v['order_id']])->value('status');
                    if (in_array($order_status, [3,4,5,6])) {
                        (new StoreMarketingRecord())->where(['id' => $v['id']])->update(['is_del' => 1]);
                    }
                }
            }
        }
        return true;
    }

}