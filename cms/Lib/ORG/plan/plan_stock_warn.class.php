<?php

/**
 * 商品库存告警（外卖、商城、团购）、售后超时未处理告警（外卖、商城）
 *
 * @author: zt
 * @date: 2023/02/14
 */
class plan_stock_warn extends plan_base
{
    public function runTask()
    {
        //每天7点之后推送，尽量白天推送
        $hour = date('H');
        if ($hour < 7) {
            return true;
        }

        $stockWarnMod = M('Stock_warn');
        $where = [
            'status' => 1,
            'business' => ['IN', ['mall', 'shop', 'group']],
        ];
        $warnSetting = $stockWarnMod->where($where)->order('last_notice_time ASC,mer_id ASC')->limit(15)->select();
        if (empty($warnSetting)) {
            return true;
        }

        $tm = time();
        $zeroTime = strtotime(date('Ymd'));
        $week = date('w');
        $days = 10;  //售后订单通知取最近10天申请未处理的
        $shopGoodsMod = M('Shop_goods');
        $shopOrderRefundMod = M('Shop_order_refund');
        $mallGoodsMod = M('Mall_goods');
        $mallOrderRefundMod = M('Mall_order_refund');
        $groupMod = M('Group');
        $noticeMod = M('Warn_notice');
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $pushMerUsers = [];

        try {
            foreach ($warnSetting as $w) {
                $warnGoods = [];
                $warnOrders = [];

                //不管是否能通知成功 先更新最后通知时间尽可能避免重复执行
                $stockWarnMod->where(['pigcms_id' => $w['pigcms_id']])->save(['last_notice_time' => time()]);

                if ($w['business'] == 'mall') {
                    $tag = C('config.mall_alias_name_new') ? C('config.mall_alias_name_new') : '商城';
                    $tagField = 'is_warn_mall';
                    //新版商城库存告警
                    if ($w['min_num'] >= 0) {
                        $warnGoods = $mallGoodsMod->alias('g')->join('INNER JOIN ' . C('DB_PREFIX') . 'mall_goods_sku sku ON g.goods_id=sku.goods_id INNER JOIN ' . C('DB_PREFIX') . 'merchant_store s ON g.store_id=s.store_id')
                            ->where(['s.have_mall' => 1, 'g.status' => 1, 'g.audit_status' => 1, 'g.is_del' => 0, 'sku.stock_num' => [['EGT', 0], ['ELT', $w['min_num']]], 's.mer_id' => $w['mer_id']])
                            ->group('g.goods_id')
                            ->field('g.goods_id,g.name,sku.stock_num,s.store_id,s.name AS store_name,s.mer_id')
                            ->select();
                    }

                    //商城售后告警
                    if ($w['apply_after_sales_time'] >= 0) {
                        $mallOrderRefundMod->alias('r')->join('INNER JOIN ' . C('DB_PREFIX') . 'mall_order o ON r.order_id=o.order_id')
                            ->where(['r.status' => 0, 'o.status' => [['egt', 60], ['lt', 70]], 'o.mer_id' => $w['mer_id'], 'r.create_time' => [['egt', $tm - $days * 86400], ['lt', $tm - $w['apply_after_sales_time'] * 60]]])
                            ->field('o.mer_id,o.store_id,o.order_id,r.create_time,o.order_no AS real_orderid')
                            ->group('o.order_id')
                            ->select();
                    }
                } else if ($w['business'] == 'shop') {
                    $tag = C('config.shop_alias_name') ? C('config.shop_alias_name') : '快店';
                    $tagField = 'is_warn_shop';
                    //外卖库存告警
                    if ($w['min_num'] >= 0) {
                        $warnGoods = $shopGoodsMod->alias('g')->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant_store s ON g.store_id=s.store_id')
                            ->where(['s.have_shop' => 1, 's.status' => 1, 's.mer_id' => $w['mer_id'], 'g.status' => 1, 'g.audit_status' => 1, 'g.stock_num' => [['EGT', 0], ['ELT', $w['min_num']]]])
                            ->field('g.goods_id,g.name,g.stock_num,s.store_id,s.name AS store_name,s.mer_id')
                            ->select();
                    }

                    //外卖售后告警
                    if ($w['apply_after_sales_time'] >= 0) {
                        $warnOrders = $shopOrderRefundMod->alias('r')->join('INNER JOIN ' . C('DB_PREFIX') . 'shop_order o ON o.order_id=r.order_id')
                            ->where(['r.status' => 0, 'o.mer_id' => $w['mer_id'], 'r.applytime' => [['egt', $tm - $days * 86400], ['lt', $tm - $w['apply_after_sales_time'] * 60]]])
                            ->field('o.mer_id,o.store_id,o.order_id,r.applytime as create_time,o.real_orderid')
                            ->group('o.order_id')
                            ->select();
                    }
                } else if ($w['business'] == 'group') {
                    $tag = C('config.group_alias_name') ? C('config.group_alias_name') : '团购';
                    $tagField = 'is_warn_group';
                    //团购库存告警
                    if ($w['min_num'] >= 0) {
                        $whereSql = "g.status=1 AND g.group_cate='normal' AND m.mer_id={$w['mer_id']} AND ((g.is_sku=1 AND g.count_num>=0 AND (g.count_num-g.sale_count) <= {$w['min_num']}) OR (g.is_sku=0 AND spec.count_num>=0 AND (spec.count_num-spec.sale_count) <= {$w['min_num']}))";
                        $warnGoods = $groupMod->alias('g')->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant m ON g.mer_id=m.mer_id LEFT JOIN ' . C('DB_PREFIX') . 'group_specifications spec ON spec.group_id = g.group_id')
                            ->where($whereSql)
                            ->field('g.group_id AS goods_id,g.s_name AS name,g.count_num-g.sale_count AS stock_num,m.mer_id')
                            ->group('g.group_id')
                            ->select();
                    }
                } else {
                    //带扩展其他业务
                    continue;
                }

                //获取配置的推送人
                $pushUsers = [];
                if (isset($pushMerUsers[$w['mer_id']])) {
                    $pushUsers = $pushMerUsers[$w['mer_id']];
                } else {
                    $pushUsers = M('Warn_user')->alias('wu')
                        ->join('INNER JOIN ' . C('DB_PREFIX') . 'user u ON u.phone=wu.phone')
                        ->where(['wu.is_del' => 0, 'wu.status'=>1,'u.status' => 1, 'u.openid' => ['NEQ', ''], 'mer_id' => $w['mer_id']])
                        ->field('u.uid,u.nickname,u.phone,u.openid,u.wxapp_openid,wu.*')
                        ->select();

                    //排除掉当天不上班的
                    $pushUsers = array_filter($pushUsers, function ($r) use ($week) {
                        $workTime = json_decode($r['work_time'], true);
                        $workWeek = $workTime ? array_column($workTime, 'week') : [];
                        return in_array($week, $workWeek);
                    });
                    $pushMerUsers[$w['mer_id']] = $pushUsers;
                }


                //商品库存推送告警，一天推一次就行了
                foreach ($warnGoods as $wg) {
                    $old = $noticeMod->where(['business' => $w['business'], 'type' => 0, 'goods_id' => $wg['goods_id'], 'create_time' => ['gt', $zeroTime]])->find();
                    if ($old) {
                        continue;
                    }
                    $noticeData = [
                        'business' => $w['business'],
                        'type' => 0,
                        'mer_id' => $w['mer_id'],
                        'store_id' => $wg['store_id'] ?? 0,
                        'goods_id' => $wg['goods_id'],
                        'goods_sku_id' => 0,
                        'order_id' => 0,
                        'is_warn_only_once' => $w['is_warn_only_once'],
                        'status' => 1,
                        'create_time' => $tm,
                        'desc' => '',
                        'title' => sprintf("【%s】库存不足", $tag),
                        'content' => isset($wg['store_name']) ? sprintf("您的【%s】店铺里【%s】商品库存小于【%d】,请及时补充。", $wg['store_name'], $wg['name'], $w['min_num']) : sprintf("您的【%s】商品库存小于【%d】,请及时补充。", $wg['name'], $w['min_num'])
                    ];
                    M('Warn_notice')->add($noticeData);

                    //模板消息推送
                    foreach ($pushUsers as $pu) {
                        if ($pu[$tagField] != 1) {
                            continue;
                        }
                        $wxData = [
                            'href' => '',
                            'wecha_id' => $pu['openid'],
                            'wxapp_openid' => $pu['wxapp_openid'],
                            'first' =>  '',
                            'keyword1' => $noticeData['title'],
                            'keyword2' => $noticeData['content'],
                            'keyword3' => date('Y-m-d H:i:s', $tm),
                            'remark' => L_('请登录商家中心PC端进行处理')
                        ];
                        $model->sendTempMsg('OPENTM400166399', $wxData, 0);
                    }
                }

                //售后订单推送告警，一天推一次就行了
                foreach ($warnOrders as $wo) {
                    $old = $noticeMod->where(['business' => $w['business'], 'type' => 1, 'order_id' => $wo['order_id'], 'create_time' => ['gt', $zeroTime]])->find();
                    if ($old) {
                        continue;
                    }
                    $noticeData = [
                        'business' => $w['business'],
                        'type' => 1,
                        'mer_id' => $w['mer_id'],
                        'store_id' => $wo['store_id'] ?? 0,
                        'goods_id' => $wo['goods_id'] ?? 0,
                        'goods_sku_id' => 0,
                        'order_id' => $wo['order_id'],
                        'is_warn_only_once' => $w['is_warn_only_once'],
                        'status' => 1,
                        'create_time' => $tm,
                        'desc' => '',
                        'title' => sprintf("【%s】用户申请售后", $tag),
                        'content' => sprintf("有买家申请售后，订单号：%s，请及时处理。", $wo['real_orderid'])
                    ];
                    M('Warn_notice')->add($noticeData);

                    //模板消息推送
                    foreach ($pushUsers as $pu) {
                        if ($pu[$tagField] != 1) {
                            continue;
                        }
                        $wxData = [
                            'href' => '',
                            'wecha_id' => $pu['openid'],
                            'wxapp_openid' => $pu['wxapp_openid'],
                            'first' =>  '',
                            'keyword1' => $noticeData['title'],
                            'keyword2' => $noticeData['content'],
                            'keyword3' => date('Y-m-d H:i:s', $tm),
                            'remark' => L_('请登录商家中心PC端进行处理')
                        ];
                        $model->sendTempMsg('OPENTM400166399', $wxData, 0);
                    }
                }
            }
        } catch (Exception $e) {
            fdump_api($e->getMessage(), 'plan_stock_warn');
        }
        return true;
    }
}
