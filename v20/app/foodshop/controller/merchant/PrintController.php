<?php
/**
 * 商家后台打印机管理控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/06 15:36
 */

namespace app\foodshop\controller\merchant;
use app\foodshop\model\service\order_print\DiningPrintRuleService;
use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\print_order\OrderprintService;
use app\merchant\model\service\print_order\LabelPrinterService;
class PrintController extends AuthBaseController
{
    /**
     * 店铺打印机列表
     */
    public function getStorePrintList()
    {
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['is_bind_rule'] = $this->request->param("is_bind_rule", "0", "intval");
        $param['id'] = $this->request->param("id", "0", "intval");
        $reciept_type = $this->request->param("reciept_type", "1", "intval");


        if ($reciept_type == 1) {
            $orderprintService = new OrderprintService();
            // 获得列表
            $list = $orderprintService->getPrintList($param, $this->merchantUser);

            // 返回是否有打印机权限
            $list['have_print_role'] = 0;
            if(in_array('49',explode(',',$this->merchantUser['menus']))){
                $list['have_print_role'] = 1;
            }
        } else {
            $labelPrinterService = new LabelPrinterService();
            // 获得列表
            $list = $labelPrinterService->getPrintList($param, $this->merchantUser);

            // 返回是否有打印机权限
            $list['have_print_role'] = 0;
            if(in_array('10101',explode(',',$this->merchantUser['menus']))){
                $list['have_print_role'] = 1;
            }
        }


        return api_output(0, $list);
    }

    /**
     * 店铺打印机规则列表
     */
    public function getPrintRuleList()
    {
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");

        // 获得列表
        $list = (new DiningPrintRuleService())->getPrintRuleList($param, $this->merchantUser);
        return api_output(0, $list);
    }



    /**
     * 获得打印机选择商品列表
     */
    public function getPrintGoodsList()
    {
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['id'] = $this->request->param("id", "0", "intval");
        $param['sort_id'] = $this->request->param("sort_id", "0", "intval");
        $param['keywords'] = $this->request->param("keywords", "", "trim");

        // 获得列表
        $list = (new DiningPrintRuleService())->getPrintGoodsList($param, $this->merchantUser);
        return api_output(0, $list);
    }
    /**
     * 店铺打印机规则详情
     */
    public function getPrintRuleDetail()
    {
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['id'] = $this->request->param("id", "0", "intval");

        // 获得列表
        $list = (new DiningPrintRuleService())->getDetail($param, $this->merchantUser);
        return api_output(0, $list);
    }

    /**
     * 添加编辑规则
     */
    public function editPrintRule()
    {
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $list = (new DiningPrintRuleService())->editPrintRule($param);
        return api_output(0, $list);
    }

    /**
     * 删除规则
     */
    public function delPrintRule()
    {
        // 分类ID
        $param['id'] = $this->request->param("id", "", "intval");
        // 店铺id
        $param['store_id'] = $this->request->param("store_id", "", "intval");

        $list = (new DiningPrintRuleService())->delthis($param);

        return api_output(0, $list);
    }
}
