<?php
/**
 * MallOrderController.php
 * 平台后台-订单管理
 * Create on 2020/9/16 15:32
 * Created by zhumengqun
 */

namespace app\mall\controller\platform;

use app\BaseController;
use app\common\model\service\AreaService;
use app\mall\model\service\activity\MallNewPeriodicPurchaseOrderService;
use app\mall\model\service\MallOrderService;
use app\merchant\model\service\LoginService;
use app\merchant\model\service\storestaff\LoginService as StaffLoginService;
use app\common\controller\platform\AuthBaseController;
use think\App;
use app\mall\model\service\order_print\PrintHaddleService;
use map\Coordinate;

class MallOrderController extends AuthBaseController
{
    /**
     * 获取所有商店
     * @return \json
     */
    public function getStores()
    {
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getStores();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取所有商家
     * @return \json
     */
    public function getMers()
    {
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getMers();
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
        $param['search_time_type'] = $this->request->param('search_time_type', '', 'trim');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['express_type'] = $this->request->param('express_type', '', 'intval');
        $param['act'] = $this->request->param('act', '', 'trim');
        $param['pay'] = $this->request->param('pay', '', 'trim');
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['store_name'] = $this->request->param('store_name', '', 'trim');
        $param['mer_id'] = $this->request->param('mer_id', '', 'intval');
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $areaList = $this->request->param('areaList');
        $param['province_id'] = isset($areaList[0]) ? $areaList[0] : 0;
        $param['city_id'] = isset($areaList[1]) ? $areaList[1] : 0;
        $param['area_id'] = isset($areaList[2]) ? $areaList[2] : 0;
        $param['status'] = $this->request->param('status', 3, 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['re_type'] = 'platform';
        $param['use_type'] = 1; //1=列表使用  2= 导出使用
        $param['verify'] = $this->request->param('verify', '', 'trim'); //核销方式
        $param['userInfo'] = $this->systemUser;
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
        $order_id = $this->request->param('order_id', '', 'intval');
        $periodic_order_id = $this->request->param('periodic_order_id', '', 'intval');
        $refund_id = $this->request->param('refund_id', '', 'intval');
        $re_type = 'platform';
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getOrderDetails($order_id, $periodic_order_id, $re_type, $refund_id);
            if ($arr && cfg('tianditu_web_key')) {
                list($arr['address_lng'], $arr['address_lat']) = Coordinate::bd09ToWgs84($arr['address_lng'], $arr['address_lat']);
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 商家登录
     * @return \json
     */
    public function loginMer()
    {
        $param['mer_id'] = $this->request->param("mer_id", "0", "intval");
        $orderService = new LoginService();
        try {
            $arr = $orderService->autoLogin($param, $this->systemUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 店员登录
     * @return \json
     */
    public function loginStore()
    {
        $orderService = new StaffLoginService();
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        try {
            $arr = $orderService->autoLogin($param, $this->systemUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 日志
     * @return \json
     */
    public function getOrderLog()
    {
        $order_id = $this->request->param('order_id');
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getOrderLog($order_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取各种和
     * @return \json
     */
    public function getCollect()
    {
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->getCollect();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取优惠信息
     * @return \json
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
     * @return \json
     */
    public function exportOrder()
    {
        $param['type'] = 'pc';
        $param['search_type'] = $this->request->param('search_type', '', 'intval');
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['express_type'] = $this->request->param('express_type', '', 'intval');
        $param['act'] = $this->request->param('act', '', 'trim');
        $param['pay'] = $this->request->param('pay', '', 'trim');
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['search_time_type'] = $this->request->param('search_time_type', '', 'trim');
        $param['mer_id'] = $this->request->param('mer_id', '', 'intval');
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $areaList = $this->request->param('areaList');
        $param['province_id'] = isset($areaList[0]) ? $areaList[0] : 0;
        $param['city_id'] = isset($areaList[1]) ? $areaList[1] : 0;
        $param['area_id'] = isset($areaList[2]) ? $areaList[2] : 0;
        $param['status'] = $this->request->param('status', '1', 'intval');
        $param['re_type'] = 'platform';
        $param['use_type'] = 2; //1=列表使用  2= 导出使用
        $param['store_name'] =  $this->request->param('store_name', '', 'trim');
        $param['columns'] =  $this->request->param('columns', '', 'trim');
        $orderService = new MallOrderService();
        try {
            $arr = $orderService->addOrderExport($param, $this->systemUser, []);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    public function getAllArea()
    {
        $type = $this->request->param('type', 0, 'trim');
        $areaService = new AreaService();
        try {
            $arr = $areaService->getAllArea($type,'*', 'children',false,'area_id', 'area_name',true);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
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
     * @return \json
     * 周期购期数展示
     * @author 邓远辉
     */
    public function getPeriodicList()
    {
        $order_id = $this->request->param("order_id", 0, "intval");//订单id
        if (empty($order_id)) {
            throw new \think\Exception('参数缺失');
        }
        $where = [
            ['order_id', '=', $order_id],
            ['is_complete', '<>', 2]
        ];
        $all_list = (new MallNewPeriodicPurchaseOrderService())->getPeriodicOrderList($where);//总期数
        $return['nums'] = count($all_list);//总期数
        $where1 = [
            ['order_id', '=', $order_id],
            ['is_complete', 'in', [3,4]],
        ];
        $complete_list = (new MallNewPeriodicPurchaseOrderService())->getPeriodicOrderList($where1);//完成期数
        $return['complete_num'] = count($complete_list);//完成期数
        $return['deliver_msg'] = (new MallNewPeriodicPurchaseOrderService())->getStoreManPreiodic($order_id);
        return api_output(0, $return);
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

}