<?php

/**
 * 淼迈云推送通知
 */
class MiaomaiyunAction extends BaseAction
{

    /**
     * 积分变动推送
     * @author: 张涛
     * @date: 2021/04/15
     */
    public function syncPoints()
    {
        $jsonData = file_get_contents("php://input");
        $callbackData = $_POST ?: json_decode($jsonData, true);
        fdump_sql([$_POST, $callbackData], 'miaomaiyun_pushPoints');

        $phone = $callbackData['mobile'];
        $changePoint = $callbackData['change_points'];
        $remark = $callbackData['remark'] ?: '淼迈云积分变动同步';
        $type = $changePoint > 0 ? 1 : 2;

        //放置O2O同步的再次触发，陷入死循环的逻辑
        if ($phone && strpos($remark, 'O2O系统同步') === false) {
            $user = M('User')->where(['phone' => $phone])->find();
            if ($user['miaomaiyun_member_id']) {
                if ($type == 1) {
                    D("User")->add_score($user['uid'], abs($changePoint), $remark, 0, [], true);
                } else {
                    D('User')->user_score($user['uid'], abs($changePoint), $remark, $type, true);
                }
            }
        }
        echo 'success';
    }


    /**
     * 库存同步
     * @author: 张涛
     * @date: 2021/04/15
     */
    public function syncStock()
    {
        $jsonData = file_get_contents("php://input");
        $callbackData = $_POST ?: json_decode($jsonData, true);
        fdump_sql([$_POST, $callbackData], 'miaomaiyun_pushStock');

        $storeId = $callbackData['stores_id'] ?? 0;
        $onlyCode = $callbackData['only_coding'];
        $xnum = $callbackData['xnum'];
        $gmid = $callbackData['g_m_id'];
        $piciOrder = $callbackData['pici_order'];
        $prefix = substr($piciOrder, 0, 2);

        //查询关联的门店id
        if ($prefix != 'BG' && $storeId && $onlyCode && $xnum && $gmid) {
            $storeIds = M('Merchant_store')->where(['miaomaiyun_erp_storeid' => $storeId])->getField('store_id', true);
            if ($storeIds) {
                M('Shop_goods')->where(['store_id' => ['IN', $storeIds], 'miaomaiyun_g_m_id' => $gmid])->setInc('stock_num', $xnum);
                M('Shop_goods')->where(['store_id' => ['IN', $storeIds], 'miaomaiyun_g_m_id' => $gmid, 'stock_num' => ['elt' => 0]])->save(['status' => 0]);
            }
        }
        echo 'success';
    }
}
