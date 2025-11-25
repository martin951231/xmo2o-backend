<?php
/**
 * 团购优惠组合控制器
 * Author: hengtingmei
 * Date Time: 2020/11/16 11:16
 */

namespace app\group\controller\api;
use app\group\model\service\order\GroupOrderService;
use app\group\model\service\order\RefundService;

class GroupCombineOrderController extends ApiBaseController
{
    /**
     * 保存店订单
     */
    public function saveOrder()
    {
        $param = $this->request->param();
        $param['user'] = $this->userInfo;

        if(empty($this->userInfo)){
            return api_output_error(1002, L_("未登录"));
        }

        // 保存店铺排序
        $res = (new GroupOrderService())->saveCombineOrder($param);
        return api_output(0, $res);
    }

    /**
     * 订单详情
     */
    public function orderDetail()
    {
        $param = $this->request->param();
        $param['user'] = $this->userInfo;

//        if(empty($this->userInfo)){
//            return api_output_error(1002, L_("未登录"));
//        }

        // 保存店铺排序
        $res = (new GroupOrderService())->getOrderDetail($param);
        return api_output(0, $res);
    }

    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        $param = $this->request->param();
        $param['user'] = $this->userInfo;

//        if(empty($this->userInfo)){
//            return api_output_error(1002, L_("未登录"));
//        }

        // 保存店铺排序
        $res = (new RefundService())->cancelOrder($param);
        return api_output(0, $res);
    }
}
