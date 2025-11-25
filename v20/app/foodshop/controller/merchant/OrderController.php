<?php
/**
 * 商家后台餐饮订单控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/24 13:24
 */

namespace app\foodshop\controller\merchant;
use app\merchant\controller\merchant\AuthBaseController;
use app\foodshop\model\service\order\DiningOrderService;
use app\foodshop\model\service\export\ExportService;
use shop_order_reply_image;

class OrderController extends AuthBaseController
{
    
    /**
     * 获得订单列表
     */
    public function orderList()
    {
        $diningOrderService = new DiningOrderService();
        
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['order_status'] = $this->request->param("order_status", "", "intval");
        $param['searchtype'] = $this->request->param("searchtype", "", "trim");
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        $param['store_id'] = $this->request->param("store_id", "", "intval");
        $param['payType'] = $this->request->param("payType", "all", "trim");
        // 获得列表
        $list = $diningOrderService->getOrderListLimit($param, [], $this->merchantUser);

        
        return api_output(0, $list);
    }   

    /**
     * 获得订单详情
     */
    public function OrderDetail()
    {
        $diningOrderService = new DiningOrderService();
        
        $param['order_id'] = $this->request->param("order_id", "0", "intval");

        // 获得列表
        $detail = $diningOrderService->getOrderDetail($param, []);
        
        return api_output(0, $detail);
    }   
    

    // 导出
	public function export(){
        
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['order_status'] = $this->request->param("order_status", "", "intval");
        $param['searchtype'] = $this->request->param("searchtype", "", "trim");
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        $param['store_id'] = $this->request->param("store_id", "", "intval");
        
        try {
            $result = (new ExportService())->addDiningOrderExport($param, [], $this->merchantUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

}
