<?php

namespace app\thirdRobot\controller\user;

use app\http\exceptions\ParametersException;
use app\thirdRobot\model\db\ThirdRobotDeliveryOrder;
use app\thirdRobot\model\service\ThirdRobotDeliveryOrderService;
use app\thirdRobot\model\service\ThirdRobotDeviceService;
use app\thirdRobot\model\service\ThirdRobotMerchantStoreService;
use app\thirdRobot\validate\DeliveryOrder;
use think\helper\Arr;
use think\helper\Str;

class RobotDeliveryOrderController extends ApiBaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->checkLogin();
    }
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常

    /**
     * 参数验证
     * @param array $params
     * @param string $scenario
     * @return array
     */
    private function validateParameter(array $params = [], string $scenario = ''): array
    {
        empty($params) && $params = input('post.');

        $validate = validate(DeliveryOrder::class);
        if (!$validate->scene($scenario ?: 'user_' . Str::snake(request()->action()))->check($params)) {
            throw_exception($validate->getError(), ParametersException::class);
        }
        return $params;
    }
    //endregion
    //region 订单处理
    
    /**
     * 创建订单
     * @return \think\response\Json
     */
    public function createOrder()
    {
        $type = $this->request->post('type','','trim');
        if ($type == 'call') {
            $params = $this->validateParameter(Arr::only(input('post.'), [
                'village_id',
                'robot_id',
                'start_point_id',
                'start_address_name',
                'start_address_user_name',
                'start_address_phone',
            ]));
        } else {
            $params = $this->validateParameter(Arr::only(input('post.'), [
                'village_id',
                'robot_id',
                'start_point_id',
                'start_address_name',
                'start_address_user_name',
                'start_address_phone',
                'end_point_id',
                'end_address_name',
                'end_address_user_name',
                'end_address_phone',
            ]));
        }

        $params['uid'] = $this->_uid;

        $robotInfo = app(ThirdRobotDeviceService::class)->getOneDataInfo([
            'id' => $params['robot_id']
        ], 'robot_code, name, robot_type, delivery_price, call_price');

        
        $params = array_merge($params, $robotInfo);
        if ($type && (!isset($params['type']) || !$params['type'])) {
            $params['type'] = $type;
        }

        $order = app(ThirdRobotDeliveryOrderService::class)->createOrder($params);

        return api_output(self::STATUS_OK, [
            'order_type' => 'robot',
            'order_id'   => $order->id,
        ]);
    }

    /**
     * 订单详情
     * @return \think\response\Json
     */
    public function orderDetail()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'order_id',
        ]));
       
        $params['uid'] = $this->_uid;
        $data = app(ThirdRobotDeliveryOrderService::class)->orderDetail($params);

        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 取消订单
     * @return \think\response\Json
     */
    public function cancelOrder()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'order_id',
        ]));
        $params['uid'] = $this->_uid;
        $params['refund_remark'] = '用户主动取消订单';
        
        $data = app(ThirdRobotDeliveryOrderService::class)->cancelOrder($params);

        return api_output(self::STATUS_OK, $data);
    }
    //endregion
    //region 店铺机器人
    /**
     * 店铺机器人列表
     * @return \think\response\Json
     */
    public function merchantStoreRobotList()
    {
        $params = input('post.');
        $data = app(ThirdRobotMerchantStoreService::class)->storeRobotList($params);
        return api_output(self::STATUS_OK, $data);
    }
   

    /**
     * 机器人运行信息
     * @return \think\response\Json
     */
    public function robotInfo()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'robot_code',
        ]));
       
        $data = app(ThirdRobotDeviceService::class)->getThirdRobotInfo($params['robot_code']);
        
        !empty($data['data']['curLocation']) && 
        $data['data']['curLocation'] = json_decode($data['data']['curLocation'],true);
        
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 修改机器人状态
     * @return \think\response\Json
     */
    public function changeRobotStatus()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'order_id',
            'status'
        ]));
        $params['uid'] = $this->_uid;
        
        switch ($params['status']){
            case ThirdRobotDeliveryOrderService::ROBOT_ARRIVED_START_POINT:
                $data = app(ThirdRobotDeliveryOrderService::class)->changeOrderStatus(
                    $params['order_id'], ThirdRobotDeliveryOrder::ORDER_STATUS_40, $params
                );
                break;
            case ThirdRobotDeliveryOrderService::ROBOT_START_DELIVERY:
                $data = app(ThirdRobotDeliveryOrderService::class)->changeOrderStatus(
                    $params['order_id'], ThirdRobotDeliveryOrder::ORDER_STATUS_50, $params
                );
                break;
            case ThirdRobotDeliveryOrderService::ROBOT_ARRIVED_END_POINT:
                $data = app(ThirdRobotDeliveryOrderService::class)->changeOrderStatus(
                    $params['order_id'], ThirdRobotDeliveryOrder::ORDER_STATUS_60, $params
                );
                break;
            default:
                throw_exception('状态错误！');
        }
        
        return api_output(self::STATUS_OK, $data);
    }
    //endregion
}