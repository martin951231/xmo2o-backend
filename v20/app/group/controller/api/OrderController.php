<?php
/**
 * 团购商品
 * Author: hengtingmei
 * Date Time: 2021/05/20 17:49
 */

namespace app\group\controller\api;

use app\group\model\service\GroupService;
use app\group\model\service\order\GroupOrderService;

class OrderController extends ApiBaseController
{

    /**
     * 获取提交页详情
     */
    public function getSaveOrderDetail()
    {
        $param = $this->request->param();
        try {
            // 获得列表
            $list = (new GroupOrderService())->getSaveOrderDetail($param);
        }catch (\Exception $e){
            return api_output_error($e->getCode(),$e->getMessage());
        }
        

        return api_output(0, $list);
    }

    /**
     * 保存订单
     */
    public function saveOrder()
    {
        $param = $this->request->param();
        try {
            $list = (new GroupOrderService())->saveOrder($param);
        }catch (\Exception $e){
            return api_output_error($e->getCode(),$e->getMessage());
        }
        return api_output(1000, $list, 'success');
    }
       
}
