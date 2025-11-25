<?php
/**
 * 餐饮订单控制器
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/24 13:24
 */

namespace app\foodshop\controller\platform;
use app\foodshop\controller\platform\AuthBaseController;
use app\foodshop\model\service\order\DiningOrderService;
use app\foodshop\model\service\export\ExportService;
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
        $param['start_time'] =$this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['order_status'] = $this->request->param("order_status", "", "intval");
        $param['searchtype'] = $this->request->param("searchtype", "", "trim");
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        $param['province_id'] = $this->request->param("province_id", "", "intval");
        $param['city_id'] = $this->request->param("city_id", "", "intval");
        $param['area_id'] = $this->request->param("area_id", "", "intval");
        $param['street_id'] = $this->request->param("street_id", "", "intval");
        $param['payType'] = $this->request->param("payType", "all", "trim");

        // 获得列表
        $list = $diningOrderService->getOrderListLimit($param,$this->systemUser);

        
        return api_output(0, $list);
    }   

    /**
     * 获得订单详情
     */
    public function orderDetail()
    {
        $diningOrderService = new DiningOrderService();
        
        $orderId = $this->request->param("order_id", "0", "intval");
        $realOrderid = $this->request->param("real_orderid", "", "trim");
        if (empty($orderId) && $realOrderid) {
            $orderId = $diningOrderService->getOrderIdByRealOrderid($realOrderid);
        }
        // 获得列表
        $detail = $diningOrderService->getOrderDetail($orderId,[]);
        
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
        $param['province_id'] = $this->request->param("province_id", "", "intval");
        $param['city_id'] = $this->request->param("city_id", "", "intval");
        $param['area_id'] = $this->request->param("area_id", "", "intval");
        $param['street_id'] = $this->request->param("street_id", "", "intval");
        
        try {
            $result = (new ExportService())->addDiningOrderExport($param, $this->systemUser); 
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    
    
}
