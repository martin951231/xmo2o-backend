<?php
class MiaomaiAction extends BaseAction
{
    public function order()
    {
        $orderMod = M('Miaomai_order_sync');
        $where = [];

        if ($_GET['order_code']) {
            $where['order_code'] = trim($_GET['order_code']);
        }
        if ($_GET['stores_name']) {
            $where['stores_name'] = trim($_GET['stores_name']);
        }
        if ($_GET['cas_name']) {
            $where['cas_name'] = trim($_GET['cas_name']);
        }

        $searchTimeType = $_GET['searchTimeType'] ?? '';
        if ($searchTimeType) {
            if ($_GET['begin_time']) {
                $where[$searchTimeType][] = ['gt', $_GET['begin_time']];
            };
            if ($_GET['end_time']) {
                $where[$searchTimeType][] = ['lt', $_GET['end_time'] . ' 23:59:59'];
            };
        }

        $count = $orderMod->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $orders = $orderMod->field(true)->where($where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($orders as &$order) {
            $order['goods_arr'] = $order['goods'] ? json_decode($order['goods'], true) : [];
            $order['pays_arr'] = $order['pays'] ? json_decode($order['pays'], true) : [];

            $refund = D('Miaomai_order_refund_sync')->where(['order_code' => $order['order_code']])->find();
            $order['has_refund'] = $refund ? true : false;
        }

        $this->assign('page', $p->show());
        $this->assign('orders', $orders);
        $this->display();
    }

    public function order_detail()
    {
        $id = $_GET['id'] ?? 0;
        $thisOrder = M('Miaomai_order_sync')->where(['id' => $id])->find();
        if (empty($thisOrder)) {
            $this->error('订单不存在');
        }
        $thisOrder['goods_arr'] = $thisOrder['goods'] ? json_decode($thisOrder['goods'], true) : [];
        $thisOrder['pays_arr'] = $thisOrder['pays'] ? json_decode($thisOrder['pays'], true) : [];
        $thisOrder['discounts_arr'] = $thisOrder['discounts'] ? json_decode($thisOrder['discounts'], true) : [];
        $thisOrder['sales_clerk_arr'] = $thisOrder['sales_clerk'] ? json_decode($thisOrder['sales_clerk'], true) : [];

        $this->assign('order', $thisOrder);
        $this->display();
    }

    public function order_refund_detail()
    {
        $orderCode = I('get.order_code', '', 'trim');
        $refundId = I('get.refund_id', 0, 'intval');
        $records = D('Miaomai_order_refund_sync')->where(['order_code' => $orderCode])->order('addtime ASC')->select();
        $times = count($records);
        $refundIds = array_column($records, 'id');
        if ($times > 0) {
            if ($refundId) {
                $refundDetail = array_values(array_filter($records, function ($item) use ($refundId) {
                    return $refundId == $item['id'];
                }));
                $refundDetail = $refundDetail[0] ?? [];
            } else {
                $refundDetail = $records[0];
            }
            $refundDetail['goods_arr'] = $refundDetail['goods'] ? json_decode($refundDetail['goods'], true) : [];
            $refundDetail['pays_arr'] = $refundDetail['pays'] ? json_decode($refundDetail['pays'], true) : [];
        }
        
        $this->assign('refundIds', $refundIds);
        $this->assign('order', $refundDetail);
        $this->assign('refundTimes', $times);
        $this->display();
    }
}
