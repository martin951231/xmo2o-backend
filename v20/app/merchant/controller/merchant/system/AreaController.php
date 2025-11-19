<?php
/**
 * 商家后台城市区域获取
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */
namespace app\merchant\controller\merchant\system;

use app\common\controller\CommonBaseController;
use app\common\model\service\AreaStreetService;
use app\merchant\model\service\AreaService;
class AreaController extends CommonBaseController{

    /**
     * desc: 获得当前所在城市区域信息
     * return :array
     */
    public function getLocation(){
        try {
            $result = (new AreaService())->getLocation();
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }

    /**
     * desc: 获得所有省份
     * return :array
     */
    public function getProvinceList(){
        $param['open_limit'] = $this->request->param('open_limit', '', 'intval');
        try {
            $result = (new AreaService())->getProvinceList($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }

    /**
     * desc: 获得所有城市
     * return :array
     */
    public function getCityList(){
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['type'] = $this->request->param('type', '', 'trim');
        try {
            $result = (new AreaService())->getCityList($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }

    /**
     * desc: 获得所有区域
     * return :array
     */
    public function getAreaList(){
        $param['id'] = $this->request->param('id', '', 'intval');
        try {
            $result = (new AreaService())->getAreaList($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }

    /**
     * desc: 获得街道
     * return :array
     */
    public function getStreetList(){
        $param['id'] = $this->request->param('id', '', 'intval');
        try {
            $result = (new AreaStreetService())->getStreetList($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result);
    }


}