<?php


namespace app\thirdRobot\controller\platform;

use app\http\exceptions\ParametersException;
use app\thirdRobot\model\service\ThirdRobotDeliveryOrderService;
use app\thirdRobot\validate\DeliveryOrder;
use think\helper\Arr;
use think\helper\Str;

class ThirdRobotDeliveryOrderController extends AuthBaseController
{
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
        if (!$validate->scene($scenario ?: 'platform_' . Str::snake(request()->action()))->check($params)) {
            throw_exception($validate->getError(), ParametersException::class);
        }
        return $params;
    }
    //endregion
    
    //region 订单处理
    /**
     * 订单列表
     * @return \think\response\Json
     */
    public function orderList()
    {
        $data = app(ThirdRobotDeliveryOrderService::class)->orderList(input('post.'));
        return api_output(self::STATUS_OK, $data);
    }

    /**
     * 设置机器人配送费
     * @return \think\response\Json
     */
    public function setRobotDeliveryPrice()
    {
        $robotPriceArr = input('post.robot_price_arr');

        foreach ($robotPriceArr as $v){
            $params = $this->validateParameter(Arr::only($v, [
                'robot_id',
                'delivery_price',
                'call_price',
            ]));
            app(ThirdRobotDeliveryOrderService::class)->setRobotDeliveryPrice($params);
        }

        return api_output(self::STATUS_OK, true);
    }

    /**
     * 取消订单
     * @return \think\response\Json
     */
    public function cancelOrder()
    {
        $params = $this->validateParameter(Arr::only(input('post.'), [
            'order_id',
            'uid'
        ]));
        $params['refund_remark'] = '管理员取消订单';
        $data = app(ThirdRobotDeliveryOrderService::class)->cancelOrder($params);

        return api_output(self::STATUS_OK, $data);
    }
    //endregion
}