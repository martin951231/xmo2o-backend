<?php

/**
 * 团队接龙活动表
 * @author: 张涛
 * @date: 2020/9/22
 */
class Team_solitaire_activityModel extends Model
{

    /**
     * 获取列表
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function getLists($query)
    {
        $where = [];
        if ($query['store_id']) {
            $where['a.store_id'] = $query['store_id'];
        }
        if ($query['id']) {
            $where['a.id'] = $query['id'];
        }
        if (isset($query['status'])) {
            $where['a.status'] = $query['status'];
        }
        if ($query['keyword']) {
            $where['_complex'] = [
                'a.name' => ['LIKE', '%' . $query['keyword'] . '%'],
                'g.name' => ['LIKE', '%' . $query['keyword'] . '%'],
                '_logic' => 'or'
            ];
        }
        import('@.ORG.merchant_page');
        $count = $this->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'shop_goods g ON a.goods_id = g.goods_id')
            ->field('a.*,g.name AS goods_name,g.price AS goods_price')
            ->where($where)
            ->count();
        $page = new Page($count, 10);
        $activityies = $this->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'shop_goods g ON a.goods_id = g.goods_id')
            ->field('a.*,g.name AS goods_name,g.price AS goods_price')
            ->where($where)
            ->order('a.id DESC')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $time = time();
        foreach ($activityies as $k => $v) {
            $activityies[$k]['settings'] = $this->getSettingByAid($v['id']);
            if ($v['status'] == 0) {
                $activityies[$k]['status_desc'] = '已取消';
            } else if ($time < $v['start_time']) {
                $activityies[$k]['status_desc'] = '未开始';
            } else if ($time >= $v['start_time'] && $time < $v['end_time']) {
                $activityies[$k]['status_desc'] = '进行中';
            } else if ($time >= $v['end_time']) {
                $activityies[$k]['status_desc'] = '已结束';
            }
        }
        return ['lists' => $activityies, 'page_bar' => $page->show(), 'total_count' => $page->totalRows];
    }

    /**
     * 根据活动ID获取配置
     * @param $aid
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function getSettingByAid($aid)
    {
        $settings = M('Team_solitaire_activity_setting')->where(['aid' => $aid])->order('sort ASC')->select();
        return $settings;
    }


    /**
     * 添加
     * @param $post
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function addActivity($post)
    {
        if (empty($post['store_id'])) {
            throw new Exception(L_('店铺ID参数有误'));
        }
        if (empty($post['name'])) {
            throw new Exception(L_('活动名称不能为空'));
        }
        if (empty($post['description'])) {
            throw new Exception(L_('活动描述不能为空'));
        }
        if (empty($post['begin_time']) || empty($post['end_time'])) {
            throw new Exception(L_('请选择活动时间'));
        }
        if (strtotime($post['begin_time']) > strtotime($post['end_time'])) {
            throw new Exception(L_('活动起始时间不能大于结束时间'));
        }
        if (empty($post['goods_ids'])) {
            throw new Exception(L_('请选择活动商品'));
        }

        $numbers = $post['number'];
        $discount = $post['discount'];
        $activityMod = D('Team_solitaire_activity');
        $settingMod = D('Team_solitaire_activity_setting');

        $this->startTrans();
        try {
            $activity = [
                'store_id' => $post['store_id'],
                'name' => $post['name'],
                'description' => $post['description'],
                'start_time' => strtotime($post['begin_time']),
                'end_time' => strtotime($post['end_time']),
                'update_time' => time()
            ];
            foreach ($post['goods_ids'] as $goodsId) {
                $activity['goods_id'] = $goodsId;
                $aid = $activityMod->add($activity);

                $setting = [];
                foreach ($numbers[$goodsId] as $k => $v) {
                    if ($v <= 0 || empty($discount[$goodsId][$k])) {
                        continue;
                    }
                    $setting[] = [
                        'aid' => $aid,
                        'number' => $v,
                        'discount' => 100 - $discount[$goodsId][$k],
                        'sort' => $k + 1
                    ];
                }
                if ($setting) {
                    $settingMod->addAll($setting);
                } else {
                    throw new Exception(L_('阶梯人数设置有误'));
                }
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 修改活动
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function editActivity($post)
    {
        if (empty($post['id'])) {
            throw new Exception(L_('请选择一条记录'));
        }
        if (empty($post['store_id'])) {
            throw new Exception(L_('店铺ID参数有误'));
        }
        if (empty($post['name'])) {
            throw new Exception(L_('活动名称不能为空'));
        }
        if (empty($post['description'])) {
            throw new Exception(L_('活动描述不能为空'));
        }
        if (empty($post['begin_time']) || empty($post['end_time'])) {
            throw new Exception(L_('请选择活动时间'));
        }
        if (strtotime($post['begin_time']) > strtotime($post['end_time'])) {
            throw new Exception(L_('活动起始时间不能大于结束时间'));
        }

        $numbers = $post['number'];
        $discount = $post['discount'];
        $activityMod = D('Team_solitaire_activity');
        $settingMod = D('Team_solitaire_activity_setting');

        $this->startTrans();
        try {
            $activity = [
                'name' => $post['name'],
                'description' => $post['description'],
                'start_time' => strtotime($post['begin_time']),
                'end_time' => strtotime($post['end_time']),
                'update_time' => time()
            ];
            $activityMod->where(['id' => $post['id']])->save($activity);
            $settingMod->where(['aid' => $post['id']])->delete();
            $setting = [];
            foreach ($numbers as $k => $v) {
                if ($v <= 0 || empty($discount[$k])) {
                    continue;
                }
                $setting[] = [
                    'aid' => $post['id'],
                    'number' => $v,
                    'discount' => 100 - $discount[$k],
                    'sort' => $k + 1
                ];
            }
            if ($setting) {
                $settingMod->addAll($setting);
            } else {
                throw new Exception(L_('阶梯人数设置有误'));
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 获取当前活动参与的团购接龙活动
     * @param $goodsId
     * @author: 张涛
     * @date: 2020/9/24
     */
    public function getCurrentActivityByGoodsId($goodsId)
    {
        $goods = M('Shop_goods')->where(['goods_id' => $goodsId, 'cat_fid' => ['gt', 0], 'is_properties' => 0, 'spec_value' => ''])->find();
        if (empty($goods)) {
            return [];
        }

        $tm = time();
        $where = [
            'status' => 1,
            'goods_id' => $goodsId,
            'start_time' => ['lt', $tm],
            'end_time' => ['gt', $tm],
        ];
        $activity = $this->where($where)->find();
        if (empty($activity)) {
            return [];
        }
        $activity['setting'] = $this->getSettingByAid($activity['id']);

        //获取当前阶梯价
        $basePrice = $goods['price'];
        $floor = M('Team_solitaire_floor')->where(['aid' => $activity['id']])->getField('floor');
        $activity['current_floor'] = intval($floor);
        $activity['current_floor_price'] = $basePrice;

        foreach ($activity['setting'] as $v) {
            if ($activity['current_floor'] >= $v['number']) {
                $activity['current_floor_price'] = round($basePrice * $v['discount'] / 100, 2);
            }
        }
        return $activity;
    }

    /**
     * 获取店铺在线的活动
     * @param $storeId
     * @author: 张涛
     * @date: 2020/9/25
     */
    public function solitaireGoodsLists($storeId)
    {
        $tm = time();
        $where = [
            'a.status' => 1,
            'a.store_id' => $storeId,
            'a.start_time' => ['lt', $tm],
            'a.end_time' => ['gt', $tm],
            'g.status' => 1,
        ];
        $goods = $this->alias('a')->join(C('DB_PREFIX') . 'shop_goods g ON a.goods_id = g.goods_id')->field('a.*')->where($where)->select();

        $otherGoods = [];
        $shopGoodsMod = D('Shop_goods');
        $settingMod = M('Team_solitaire_activity_setting');
        foreach ($goods as $g) {
            $lowSetting = $settingMod->where(['aid' => $g['id']])->order('discount ASC')->find();
            if (!$lowSetting || $lowSetting['discount'] <= 0) {
                continue;
            }

            $info = $shopGoodsMod->get_goods_by_id($g['goods_id'], null);
            if ($info) {
                $info['low_price'] = getFormatNumber($info['price'] * $lowSetting['discount'] / 100);
                $otherGoods[] = $info;
            }
        }
        return $otherGoods;
    }


    /**
     * 订单支付成功，写入接龙活动
     * @author: 张涛
     * @date: 2020/9/25
     */
    public function writeRecord($orderId)
    {
        $tm = time();
        $records = [];
        $floorMod = M('Team_solitaire_floor');
        $recordMod = M('Team_solitaire_goods_record');

        $order = M('Shop_order')->where(['order_id' => $orderId])->find();
        if (!$orderId) {
            return false;
        }

        $orderGoods = M('Shop_order_detail')->field('goods_id,sum(num) AS buy_num,price')->where(['order_id' => $orderId])->group('goods_id')->select();

        $this->startTrans();
        try {
            foreach ($orderGoods as $g) {
                $activity = $this->getCurrentActivityByGoodsId($g['goods_id']);
                if (empty($activity)) {
                    continue;
                }
                if ($order['uid'] != $order['share_uid'] && $order['share_uid'] > 0) {
                    M('Team_solitaire_share_log')->where(['uid' => $order['uid'], 'share_uid' => $order['share_uid'], 'goods_id' => $g['goods_id'], 'aid' => $activity['id']])->save(['is_buy' => 1]);
                }
                $updateRs = $floorMod->where(['aid' => $activity['id'], 'goods_id' => $g['goods_id']])->setInc('floor', 1);
                if (!$updateRs) {
                    $data = [
                        'goods_id' => $g['goods_id'],
                        'floor' => 1,
                        'activity_info' => serialize($activity),
                        'aid' => $activity['id'],
                        'create_time' => $tm
                    ];
                    $recId = $floorMod->add($data);
                } else {
                    $recId = $floorMod->where(['aid' => $activity['id'], 'goods_id' => $g['goods_id']])->getField('id');
                }

                $records[] = [
                    'rec_id' => $recId,
                    'order_id' => $orderId,
                    'num' => $g['buy_num'],
                    'uid' => $order['uid'],
                    'buy_price' => $g['price'],
                    'from_uid' => $order['from_uid'] ?? 0
                ];
            }
            $records && $recordMod->addAll($records);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            fdump_sql(['order_id' => $orderId, 'error_msg' => $e->getMessage()], 'team_solitaire_write_record_fail');
            return false;
        }
    }

    /**
     * 获取漏层信息
     * @author: 张涛
     * @date: 2020/9/25
     */
    public function getFloor($aid, $limit = 0)
    {
        $recId = M('Team_solitaire_floor')->where(['aid' => $aid])->getField('id');
        if ($recId < 1) {
            return [];
        }
        $orders = M('Team_solitaire_goods_record')->alias('r')
            ->join(C('DB_PREFIX') . 'shop_order o ON o.order_id = r.order_id')
            ->join(C('DB_PREFIX') . 'user u ON o.uid = u.uid')
            ->where(['r.rec_id' => $recId])
            ->field('r.*,u.avatar,u.nickname,o.create_time')
            ->order('r.id ASC')
            ->limit($limit)
            ->select();
        $detailMod = M('Shop_order_detail');
        foreach ($orders as $k => $v) {
            $orders[$k]['floor'] = $k + 1;
            //多语言转化
            $orders[$k]['detail'] = $detailMod->alias('ts')->join('inner join '.C("DB_PREFIX")._view('shop_goods').' gd on ts.goods_id=gd.goods_id')->where(['ts.order_id' => $v['order_id']])->field('gd.*,ts.num')->select();
        }
        return $orders;
    }

    /**
     * 获取楼层用户信息
     * @param $aid
     * @return array
     * @author: 张涛
     * @date: 2020/9/25
     */
    public function getFloorUser($aid)
    {
        $recId = M('Team_solitaire_floor')->where(['aid' => $aid])->getField('id');
        if ($recId < 1) {
            return [];
        }
        $users = M('Team_solitaire_goods_record')->alias('r')
            ->join(C('DB_PREFIX') . 'user u ON r.uid = u.uid')
            ->where(['r.rec_id' => $recId])
            ->field('u.avatar,u.nickname')
            ->order('r.id ASC')
            ->select();
        return $users;
    }


    /**
     * 活动结束，退回差价
     * @author: 张涛
     * @date: 2020/9/27
     */
    public function returnDiffMoneyAfterActivityEnd($aid)
    {
        $floor = M('Team_solitaire_floor')->where(['aid' => $aid])->find();
        $setting = M('Team_solitaire_activity_setting')->where(['aid' => $aid, 'number' => ['elt', $floor['floor']]])->order('number DESC')->find();
        $goods = M('Shop_goods')->where(['goods_id' => $floor['goods_id']])->find();

        if (empty($floor) || empty($setting) || empty($goods)) {
            return false;
        }
        $floorPrice = round($goods['price'] * $setting['discount'] / 100, 2);
        M('Team_solitaire_floor')->where(['aid' => $aid])->save(['floor_price' => $floorPrice, 'status' => 1]);


        $tm = time();
        $recordMod = M('Team_solitaire_goods_record');
        $shopOrderMod = M('Shop_order');
        $records = $recordMod->where(['rec_id' => $floor['id']])->select();
        foreach ($records as $v) {
            if ($floorPrice < $v['buy_price']) {
                $refundMoney = round(($v['buy_price'] - $floorPrice) * $v['num'], 2);
                $data = ['refund_money' => $refundMoney, 'refund_time' => $tm];
                $order = $shopOrderMod->where(['order_id' => $v['order_id']])->find();
                if ($order['balance_pay'] >= $refundMoney) {
                    $data['refund_balance_pay'] = $refundMoney;
                    $data['refund_payment_money'] = 0;
                } else {
                    $data['refund_balance_pay'] = $order['balance_pay'];
                    $data['refund_payment_money'] = $refundMoney - $order['balance_pay'];
                }
                $recordMod->where(['id' => $v['id']])->save($data);
            } else {
                $data['is_process'] = 1;
                $recordMod->where(['id' => $v['id']])->save($data);
            }
        }
    }

    /**
     * 增加我介绍用户记录
     * @param $uid
     * @param $fromUid
     * @author: 张涛
     * @date: 2020/9/27
     */
    public function addShareLog($uid, $shareUid, $goodsId)
    {
        if ($uid == $shareUid || $uid < 1 || $shareUid < 1) {
            return false;
        }
        $shareMod = M('Team_solitaire_share_log');
        $currentActivity = $this->getCurrentActivityByGoodsId($goodsId);
        if (empty($currentActivity)) {
            return false;
        }

        if ($shareMod->where(['uid' => $uid, 'share_uid' => $shareUid, 'goods_id' => $goodsId, 'aid' => $currentActivity['id']])->find()) {
            return true;
        } else {
            $data = [
                'uid' => $uid,
                'share_uid' => $shareUid,
                'goods_id' => $goodsId,
                'aid' => $currentActivity['id'],
                'create_time' => time()
            ];
            return $shareMod->add($data);
        }
    }

    /**
     * 获取我的助力
     * @author: 张涛
     * @date: 2020/9/27
     */
    public function getMyShare($uid, $goodsId, $limit = 0, $order = 'id DESC')
    {
        $where = [
            'l.share_uid' => $uid,
            'l.goods_id' => $goodsId
        ];
        $currentActivity = $this->getCurrentActivityByGoodsId($goodsId);
        if (empty($currentActivity)) {
            return [];
        }
        $where['l.aid'] = $currentActivity['id'];
        $res = M('Team_solitaire_share_log')->alias('l')
            ->join(C('DB_PREFIX') . 'user as u ON l.uid=u.uid')
            ->field('l.*,u.avatar,u.nickname')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();
        return $res;
    }
}