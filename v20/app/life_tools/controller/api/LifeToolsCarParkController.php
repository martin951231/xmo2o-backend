<?php


namespace app\life_tools\controller\api;


use app\life_tools\model\service\LifeToolsCarParkService;

class LifeToolsCarParkController extends ApiBaseController
{
    /**
     * 用户端获取当前景区/场馆/课程停车场
     * @return \json
     */
    public function getToolsCarPark(){
        $tools_id = $this->request->param('tools_id', 0, 'intval');
        $lat = $this->request->param('lat', 0);
        $lng = $this->request->param('lng', 0);
        if (empty($tools_id)) {
            return api_output_error(1003, '参数错误');
        }
        if(!$lat||!$lng){
            return api_output_error(1003, '参数错误');
        }
        try {
            $arr = (new LifeToolsCarParkService())->getToolsCarPark($tools_id, $lat,$lng);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}