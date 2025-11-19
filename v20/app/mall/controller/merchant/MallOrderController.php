<?php
/**
 * MallOrderController.php
 * 平台后台-订单管理
 * Create on 2020/9/16 15:32
 * Created by zhumengqun
 */

namespace app\mall\controller\merchant;

use app\mall\model\db\MallOrderDetail;
use app\mall\model\service\activity\MallNewGroupTeamService;
use app\mall\model\service\ExpressService;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\order_print\PrintHaddleService;
use app\mall\validate\OrderDelivery;
use app\merchant\controller\merchant\AuthBaseController;
use think\App;
use think\facade\Db;

class MallOrderController extends AuthBaseController
{
    /**
     * 获取该商店的店铺
     * @return \think\response\Json
     */
    public function getStores()
    {
        $mer_id = $this->merId;
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getStores1($mer_id);;
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取列表按条件 
     * @return \think\response\Json
     */
    public function searchOrders()
    {
        $param['type'] = 'pc';
        $param['search_type'] = $this->request->param('search_type', '', 'intval');
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['search_time_type'] = $this->request->param('search_time_type', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['express_type'] = $this->request->param('express_type', '', 'intval');
        $param['act'] = $this->request->param('act', '', 'trim');
        $param['pay'] = $this->request->param('pay', '', 'trim');
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['mer_id'] = $this->merId;
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['province_id'] = $this->request->param('province_id', '', 'intval');
        $param['city_id'] = $this->request->param('city_id', '', 'intval');
        $param['area_id'] = $this->request->param('area_id', '', 'intval');
        $param['status'] = $this->request->param('status', 3, 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['re_type'] = 'merchant';
        $param['use_type'] = 1; //1=列表使用  2= 导出使用
        $param['verify'] = $this->request->param('verify', 3, 'trim');//核销方式
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->searchOrders($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取详情
     * @return \think\response\Json
     */
    public function getOrderDetails()
    {
        $order_id = $this->request->param('order_id');
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $refund_id = $this->request->param('refund_id', '', 'intval');
        $re_type = 'merchant';
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getOrderDetails($order_id, $periodic_order_id, $re_type, $refund_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取各种和
     * @return \think\response\Json
     */
    public function getCollect()
    {
        $mer_id = $this->merId;
        $re_type = 'merchant';
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getCollect($mer_id, $re_type);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取优惠信息
     * @return \think\response\Json
     */
    public function getDiscount()
    {
        $order_id = $this->request->param('order_id');
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getDiscount($order_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出商品
     * @return \think\response\Json
     */
    public function exportOrder()
    {
        $param['type'] = 'pc';
        $param['search_type'] = $this->request->param('search_type', '', 'intval');
        $param['search_time_type'] = $this->request->param('search_time_type', '', 'trim');
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['act'] = $this->request->param('act', '', 'trim');
        $param['pay'] = $this->request->param('pay', '', 'trim');
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['mer_id'] = $this->merId;
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['province_id'] = $this->request->param('province_id', '', 'intval');
        $param['city_id'] = $this->request->param('city_id', '', 'intval');
        $param['area_id'] = $this->request->param('area_id', '', 'intval');
        $param['status'] = $this->request->param('status', '1', 'intval');
        $param['re_type'] = 'merchant';
        $param['use_type'] = 2; //1=列表使用  2= 导出使用
        $param['columns'] =  $this->request->param('columns', '', 'trim');
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->addOrderExport($param, [], $this->merchantUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 店员设置价格
     * @return \think\response\Json
     */
    public function editMoney()
    {
        $order_id = $this->request->param('order_id', '', 'intval');
        $before_money = $this->request->param('before_money', '', 'trim');
        $after_money = $this->request->param('after_money', '', 'trim');
        $orderService = new MallOrderService();
        try {
            $result = $orderService->editMoney($order_id, $before_money, $after_money);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取快递列表
     * @return \think\response\Json
     */
    public function getExpress()
    {
        $expressService = new ExpressService();
        try {
            $result = $expressService->getExpress();
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 查看物流/骑手轨迹
     */
    public function viewLogistics()
    {
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $order_id = $this->request->param('order_id', '', 'intval');
        $order_type = $this->request->param('order_type', '', 'trim');
        $express_style = $this->request->param('express_style', '', 'trim');
        $mallStorestaffService = new MallOrderService();
        try {
            $res = $mallStorestaffService->viewLogistics($order_id, $periodic_order_id, $order_type, $express_style);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 补打小票
     */
    public function printOrder()
    {
        $order_id = $this->request->post('order_id', 0, 'intval');
        $print_type = $this->request->post('print_type', 'bill_account', 'trim');
        try {
            if(empty($order_id)){
                throw new \think\Exception('order_id不能为空');
            }
              // 支付成功后打印小票
            $param = [
                'order_id' => $order_id,
                'print_type' => $print_type
            ];
            $data = (new PrintHaddleService)->printOrder($param, 2);
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
		return api_output(0, $data, 'success');
    }

    /**
     * 订单多包裹发货
     */
    public function deliverGoodsByExpress()
    {
        $params = array_merge([
            'periodic_order_id' => 0,
            'current_periodic'  => 0,
            'periodic_count'    => 0,
            'activity_type'     => '',
            'store_id'          => 0
        ], input('post.'));
        
        try {
            $data = (new MallOrderService())->orderDelivery($params);
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取发起拼团订单列表
     * @return \think\response\Json
     */
    public function getGroupOrderList(){
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $params['search_type'] = $this->request->param('search_type','','trim,string');
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['status'] = $this->request->param('status',-1,'trim');
        $params['start_date'] = $this->request->param('start_date','','trim,string');
        $params['end_date'] = $this->request->param('end_date','','trim,string');
        $params['active_id'] = $this->request->param('active_id',0,'trim,intval');
        $params['mer_id'] = $this->merId;

        try {
            $data = (new MallNewGroupTeamService())->getGroupOrderList($params);
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 机器人参团
     */
    public function joinRobot(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new MallNewGroupTeamService())->joinRobot($params);
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 拼团成员
     * @return \think\response\Json
     */
    public function getTeamUser(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new MallNewGroupTeamService())->getTeamUser($params);
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}