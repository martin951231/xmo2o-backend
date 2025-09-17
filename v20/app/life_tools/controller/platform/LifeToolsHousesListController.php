<?php


namespace app\life_tools\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsHousesListService;

class LifeToolsHousesListController extends AuthBaseController
{
    /**
     * 楼盘列表
     */
    public function getHousesFloorList(){
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        try {
            $data=(new LifeToolsHousesListService())->getHousesFloorList($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * @return \json
     * 获取楼盘信息
     */
    public function getHousesFloorMsg(){
        $param['houses_id'] = $this->request->param('houses_id', 0, 'intval');
        try {
            $data=(new LifeToolsHousesListService())->getHousesFloorMsg($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * @return \json
     * 获取楼盘户型信息
     */
    public function getHousesFloorPlanMsg()
    {
        $param['pigcms_id'] = $this->request->param('pigcms_id', 0, 'intval');
        if (empty($param['pigcms_id'])) {
            return api_output_error(1003, '缺少必要参数');
        }
        try {
            $data=(new LifeToolsHousesListService())->getHousesFloorPlanMsg($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * @return \json
     * 修改状态/删除
     */
    public function updateHousesFloorStatus()
    {
        $param=$this->request->param();
        if (empty($param['houses_id'])) {
            return api_output_error(1003, '缺少必要参数');
        }
        $ret = (new LifeToolsHousesListService())->updateStatus($param);
        if ($ret) {
            return api_output(0, []);
        } else {
            return api_output_error(1003, '修改失败');
        }
    }

    /**
     *楼盘户型列表
     */
    public function getChildList(){
        $param['houses_id'] = $this->request->param('houses_id', 0, 'intval');
        $ret['list'] =(new LifeToolsHousesListService())->getChildList($param);
        return api_output(0, $ret);
    }

    /**
     *楼盘新增编辑
     */
    public function editHouseFloor(){
        $param['houses_id'] = $this->request->param('houses_id', 0, 'intval');
        $param['title'] = $this->request->param('title', '', 'trim');
        $param['price'] = $this->request->param('price', '', 'trim');
        $param['acreage'] = $this->request->param('acreage', '', 'trim');
        $param['long'] = $this->request->param('long', '', 'trim');
        $param['lat'] = $this->request->param('lat', '', 'trim');
        $param['province_id'] = $this->request->param('province_id', 0, 'intval');
        $param['city_id'] = $this->request->param('city_id', 0, 'intval');
        $param['area_id'] = $this->request->param('area_id', 0, 'intval');
        $param['address'] = $this->request->param('address', '', 'trim');
        $param['phone'] = $this->request->param('phone', '', 'trim');
        $param['cover_image'] = $this->request->param('cover_image', '', 'trim');
        $param['images'] = $this->request->param('images', '', 'trim');
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['longlat'] = $this->request->param('longlat', '', 'trim');
        $ret=(new LifeToolsHousesListService())->editHouseFloor($param);
        if($ret){
            return api_output(0, []);
        }else{
            return api_output_error(1003, '保存失败');
        }
    }
    /**
     *楼盘户型新增编辑
     */
    public function editHouseFloorPlan(){
        $param['houses_id'] = $this->request->param('houses_id', 0, 'intval');
        if(empty($param['houses_id'])){
            return api_output_error(1003, '缺少必要参数');
        }
        $param['pigcms_id'] = $this->request->param('pigcms_id', 0, 'intval');
        $param['title'] = $this->request->param('title', '', 'trim');
        $param['image'] = $this->request->param('image', '', 'trim');
        $param['acreage'] = $this->request->param('acreage', '', 'trim');
        $ret=(new LifeToolsHousesListService())->editHouseFloorPlan($param);
        if($ret){
            return api_output(0, []);
        }else{
            return api_output_error(1003, '保存失败');
        }
    }

    /**
     * @return \json
     * 删除户型
     */
    public function updateHousesFloorPlanStatus()
    {
        $param=$this->request->param();
        if (empty($param['pigcms_id'])) {
            return api_output_error(1003, '缺少必要参数');
        }
        $ret = (new LifeToolsHousesListService())->updateHousesFloorPlanStatus($param);
        if ($ret) {
            return api_output(0, []);
        } else {
            return api_output_error(1003, '修改失败');
        }
    }
}