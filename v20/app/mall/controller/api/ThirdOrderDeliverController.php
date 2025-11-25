<?php


namespace app\mall\controller\api;


use app\mall\model\service\third_order_deliver\ThirdOrderDeliverService;

class ThirdOrderDeliverController extends ApiBaseController
{
    /**
     * 星展第三方配送修改状态
     */
    public function statusChange()
    {
        $param = [
            'key' => $this->request->param('key', '', 'trim,string'), //密钥
            'thirdPlatform' => $this->request->param('thirdPlatform', '', 'trim,string'), //平台标识
            'orderId' => $this->request->param('orderId', '', 'trim,string'), //订单编号
            'status' => $this->request->param('status', 0, 'trim,intval'), //11-备货中，14-备货完成，22-骑手接单，23-取货中，24-配送中，33-已送达
            'operatingTime' => $this->request->param('operatingTime', '', 'trim,string'), //操作时间
            'operator' => $this->request->param('operator', '', 'trim,string'), //操作人
            'operatorPhone' => $this->request->param('operatorPhone', '', 'trim,string'), //操作人手机号
            'lng' => $this->request->param('lng', '', 'trim,string'), //操作人位置经度
            'lat' => $this->request->param('lat', '', 'trim,string'), //操作人位置纬度
            'extends' => $this->request->param('extends', '', 'trim,string'), //扩展字段
            'businessType' => $this->request->param('businessType', 'mall', 'trim,string'), //mall-商城，service-跑腿（服务快派）
        ];
        try {
            $result = (new ThirdOrderDeliverService())->changeStatus($param);
            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => $result
            ])->code(200);
        } catch (\Exception $e) {
			fdump_api($e->getMessage(),'mall_third_deliver/changeStatus',1);
            return json([
                'code' => 404,
                'msg' => $e->getMessage(),
            ])->code(200);
        }
    }
}