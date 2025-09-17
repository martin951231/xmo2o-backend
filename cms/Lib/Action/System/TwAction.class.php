<?php

/**
 * 图维收银订单
 *
 * @author: zt
 * @date: 2023/12/05
 */
class TwAction extends BaseAction
{
    public function order()
    {
        $twOrderMod = M('Tw_orders');
        $where = [];
        if ($_GET['searchtype'] && $_GET['keyword']) {
            switch ($_GET['searchtype']) {
                case 'tw_order_no':
                    $where['tw_order_no'] = trim($_GET['keyword']);
                    break;
                case 'cust_phone':
                    $where['cust_phone'] = trim($_GET['keyword']);
                    break;
                default:
                    break;
            }
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

        if ($_GET['status']) {
            $where['status'] = $_GET['status'];
        }

        $count = $twOrderMod->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $orders = $twOrderMod->field(true)->where($where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('page', $p->show());
        $this->assign('orders', $orders);
        $this->display();
    }


    public function order_detail()
    {
        $id = $_GET['id'] ?? 0;
        $thisOrder = M('Tw_orders')->where(['id' => $id])->find();
        if (empty($thisOrder)) {
            $this->error('订单不存在');
        }
        $thisOrder['discount_arr'] = $thisOrder['discount'] ? json_decode($thisOrder['discount'], true) : [];
        $thisOrder['products_arr'] = $thisOrder['products'] ? json_decode($thisOrder['products'], true) : [];
        $this->assign('order', $thisOrder);
        $this->display();
    }
}
