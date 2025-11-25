<?php
/**
 * 自动返回用户姓名 电话 号码等信息Controller
 * Author: zhumengqun
 * Date Time: 2020/8/25
 */

namespace app\mall\controller\api;

use app\BaseController;
use app\mall\model\service\AutoAddressService;
use app\mall\model\service\MallDeliveryService;
use think\response\Json;


class AutoAddressController extends BaseController
{

    /**
     * 自动返回用户姓名 电话 号码等信息
     * @param $info
     * @return \json
     */
    public function formatInfo()
    {
        $info = $this->request->param("info", "", "trim");
        $format = new AutoAddressService();
        try {
            $result = $format->formatInfo($info);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$result);

    }

    /**
     * 验证定位是否在配送范围
     */
    public function checkRangeOut(){
        $params['lat'] = $this->request->param("lat", "", "trim,string");
        $params['lng'] = $this->request->param("lng", "", "trim,string");
        try {
            $result = (new MallDeliveryService())->checkLatLngRangeOut($params);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$result);
    }
}