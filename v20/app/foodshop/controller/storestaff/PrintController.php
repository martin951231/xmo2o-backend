<?php
/**
 * 商家后台打印机管理控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/06 15:36
 */

namespace app\foodshop\controller\storestaff;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\foodshop\model\service\order_print\PrintHaddleService;
class PrintController extends AuthBaseController
{
    /**
     * 店铺打印机列表
     */
    public function getStorePrintList()
    {
        $orderprintService = new OrderprintService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");

        // 获得列表
        $list = $orderprintService->getPrintList($param);
        return api_output(0, $list);
    }

    /**
     * 补打订单
     */
    public function printOrder()
    {
        $printHaddleService = new PrintHaddleService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['order_id'] = $this->request->param("order_id", "0", "intval");
        $param['type'] = $this->request->param("type", "", "");
        $param['repair_print'] = 1;//补打标识

        // 获得列表
        $res = $printHaddleService->printOrder($param, $this->staffUser);
        return api_output(0, L_('打印成功'));
    }

    /**
     * 补打订单
     */
    public function printOrderLabel()
    {
        $printHaddleService = new PrintHaddleService();

        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['order_id'] = $this->request->param("order_id", "0", "intval");

        // 获得列表
        $res = $printHaddleService->printOrderLabel($param);
        return api_output(0, L_('打印成功'));
    }
}
