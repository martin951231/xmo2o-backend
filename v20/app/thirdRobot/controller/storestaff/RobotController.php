<?php

namespace app\thirdRobot\controller\storestaff;

use app\common\model\db\ShopOrder;
use app\http\exceptions\ParametersException;
use app\merchant\model\service\MerchantStoreService;
use app\thirdRobot\model\db\ThirdRobotDeliveryOrder;
use app\thirdRobot\model\db\ThirdRobotDeliveryOrderLog;
use app\thirdRobot\model\db\ThirdRobotMerchantStoreRobot;
use app\thirdRobot\model\service\ThirdRobotDeliveryOrderService;
use app\thirdRobot\model\service\ThirdRobotDeviceService;
use app\thirdRobot\validate\MerchantStoreRobot;
use think\facade\Db;
use think\helper\Arr;
use think\helper\Str;

class RobotController extends AuthBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常

    /**
     * 参数验证
     * @param array $params
     * @param string $scenario
     * @return array
     */
	protected function validateParameter(array $params = [], string $scenario = ''): array
    {
        empty($params) && $params = input('post.');

        $validate = validate(MerchantStoreRobot::class);
        if (!$validate->scene($scenario ?: 'staff_' . Str::snake(request()->action()))->check($params)) {
            throw_exception($validate->getError(), ParametersException::class);
        }
        return $params;
    }
    //endregion
    
    //region 员工召唤机器人
    public function createOrder()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'robot_code',
            'end_point_id',
            'shop_order_id'
        ]));

        $params['uid'] = $this->staffId;
        $params['role'] = 'staff';
        
        $shopOrder  = app(ShopOrder::class)->getOne([
            'order_id' => $params['shop_order_id']
        ],'address, username, userphone, store_id, freight_charge');
        $store = app(MerchantStoreService::class)->getStoreInfo($shopOrder['store_id']);
        
        $storeRobot = ThirdRobotMerchantStoreRobot::where([
            'store_id' => $store['store_id'],
            'robot_code' => $params['robot_code'],
            'is_del' => ThirdRobotMerchantStoreRobot::IS_DEL_0
        ])->find();
        
        empty($storeRobot) && throw_exception('请先配置店铺机器人！');

        $params = array_merge($params, [//店铺信息
            'start_point_id'          => $storeRobot['map_point_id'],
            'start_address_name'      => $store['adress'],
            'start_address_user_name' => $store['name'],
            'start_address_phone'     => $store['phone'],

            'end_address_name'      => $shopOrder['address'],
            'end_address_user_name' => $shopOrder['username'],
            'end_address_phone'     => $shopOrder['userphone'],
            'delivery_price'        => $shopOrder['freight_charge'],//订单配送费
        ]);
        
        $order = Db::transaction(function ()use($params) {
            $deliveryOrder = app(ThirdRobotDeliveryOrderService::class)->createOrder($params);
            app(ShopOrder::class)->updateOrder([//更新订单配送方式/状态
                'order_id' => $params['shop_order_id']
            ],[
                'is_pick_in_store'        => ShopOrder::IS_PICK_IN_STORE_4,
                'order_status'            => ShopOrder::ORDER_STATUS_2,
                'robot_delivery_order_id' => $deliveryOrder->id,
            ]);
            
            app(ThirdRobotDeliveryOrderService::class)->changeOrderStatus(
                $deliveryOrder->id, ThirdRobotDeliveryOrder::ORDER_STATUS_30, [
                'from_type'   => ThirdRobotDeliveryOrderLog::FROM_TYPE_3,
                'operate_uid' => $this->staffId
            ]);
            return $deliveryOrder;
        });
        
        return api_output(self::STATUS_OK, [
            'order_type' => 'robot',
            'order_id'   => $order->id,
        ]);
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
        $params['uid'] = $this->staffId;
        $params['order_role'] = 'staff';
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

    /**
     * 机器人列表
     */
    public function storeRobotList()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'name',
            'store_id'
        ]));

        $storeRobotId = ThirdRobotMerchantStoreRobot::where([
            'store_id' => $params['store_id'],
            'is_del'   => ThirdRobotMerchantStoreRobot::IS_DEL_0
        ])->column('robot_id');
        $where = [
            ['del_time', '=', 0],
        ];
        !empty($params['name']) && $where[] = ['name', 'like', '%' .  $params['name'] . '%'];
        empty($storeRobotId) && throw_exception('店铺未绑定机器人');
            
        $where[] =  ['id','IN', $storeRobotId];

        $result = (new ThirdRobotDeviceService())->getRobotList(
            $where, true,
            input('page',0),
            input('page_size',0),
            'id DESC', true
        );

        return api_output(0, $result);
    }
   //endregion
}