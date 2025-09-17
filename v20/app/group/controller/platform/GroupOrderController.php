<?php
/**
 * 团购商品
 * Author: hengtingmei
 * Date Time: 2020/11/17 17:09
 */

namespace app\group\controller\platform;
use app\group\controller\platform\AuthBaseController;
use app\group\model\service\export\ExportService;
use app\group\model\service\order\GroupOrderService;

class GroupOrderController extends AuthBaseController
{

    /**
     * 获得团购优惠组合可选择的商品列表
     */
    public function getGroupCombineOrderList()
    {
        $param = $this->request->param();

        // 获得列表
        $list = (new GroupOrderService())->getGroupCombineOrderList($param);

        return api_output(0, $list);
    }

    /**
     * 获得团购优惠组合可选择的商品列表
     */
    public function getOrderDetail()
    {
        $param = $this->request->param();

        // 获得列表
        $list = (new GroupOrderService())->getOrderDetail($param);

        return api_output(0, $list);
    }

    // 导出
    public function exportCombineOrder(){
        $param = $this->request->param();

        try {
            $result = (new ExportService())->addExport('combine_order',$param, $this->systemUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 修改订单备注
     */
    public function editOrderNote()
    {
        $param = $this->request->param();
        // 获得列表
        $returnArr = (new GroupOrderService())->editOrderNote($param);
        return api_output(0, $returnArr);
    }

    /**
     * 获得团购优惠组合可选择的商品列表
     */
    public function getPayMethodList()
    {
        // 获得列表
        $returnArr = (new GroupOrderService())->getPayMethodList();
        return api_output(0, $returnArr);
    }
}
