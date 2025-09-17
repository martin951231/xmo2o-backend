<?php


namespace app\life_tools\controller\merchant;


use app\merchant\controller\merchant\AuthBaseController;
use app\life_tools\model\service\LifeToolsCarParkService;

class LifeToolsCarParkController extends AuthBaseController
{
    /**
     * 获取停车场列表
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCarParkList(){
        $service = new LifeToolsCarParkService();
        $param = $this->request->param();
        $param['mer_id'] = $this->merId;
        try {
            $arr = $service->getCarParkList($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }

    /**
     * 停车场-添加/编辑
     */
    public function addCarPark(){
        $service = new LifeToolsCarParkService();
        $param = $this->request->param();
        $param['mer_id'] = $this->merId;
        try {
            $arr = $service->addCarPark($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }

    /**
     * 停车场-详情
     * @return \json
     */
    public function showCarPark(){
        $service = new LifeToolsCarParkService();
        $param = $this->request->param();
        $param['mer_id'] = $this->merId;
        try {
            $arr = $service->showCarPark($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }

    /**
     * 停车场-状态修改
     */
    public function statusCarPark(){
        $service = new LifeToolsCarParkService();
        $param = $this->request->param();
        try {
            $arr = $service->statusCarPark($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }

    /**
     * 停车场-获取景区/场馆、课程列表
     * @return \json
     */
    public function getToolsList(){
        $service = new LifeToolsCarParkService();
        $param = $this->request->param();
        $param['mer_id'] = $this->merId;
        try {
            $arr = $service->getToolsList($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }

    /**
     * 停车场-删除
     * @return \json
     */
    public function deleteCarPark(){
        $service = new LifeToolsCarParkService();
        $param = $this->request->param();
        try {
            $arr = $service->deleteCarPark($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }
}