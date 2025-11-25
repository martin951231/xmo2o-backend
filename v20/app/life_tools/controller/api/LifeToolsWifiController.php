<?php


namespace app\life_tools\controller\api;


use app\life_tools\model\service\LifeToolsWifiService;

class LifeToolsWifiController extends ApiBaseController
{
    /**
     * 用户端获取商家WiFi列表
     * @return \json
     */
    public function getWifiList(){
        //$param['mer_id'] = $this->request->param('mer_id', 0, 'trim,intval');
        $param['lat'] = $this->request->param('lat', 0,'trim');
        $param['lng'] = $this->request->param('lng', 0,'trim');
        $param['keywords'] = $this->request->param('keywords', '', 'trim');

        try {
            $arr = (new LifeToolsWifiService())->wifiList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}