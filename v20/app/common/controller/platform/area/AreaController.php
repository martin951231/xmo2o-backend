<?php
/**
 * 后台区域管理
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/28 11:57
 */
namespace app\common\controller\platform\area;
use app\common\model\service\AreaService as AreaService;
use app\common\controller\platform\AuthBaseController;
use app\common\model\service\AreaStreetService;

class AreaController extends AuthBaseController{
    public $AreaService = null;
    public function initialize()
    {
        parent::initialize();
        $this->AreaService = new AreaService();
    }

   
    /**
     * desc: 返回用户信息
     * return :array
     */
    public function userInfo(){
    	$returnArr = $this->adminUserServiceObj->formatUserData($this->systemUser);
    	if (!$returnArr) {
           	return api_output_error(1002, "用户不存在或未登录");
    	}
        return api_output(0, $returnArr);
    }

    /**
     * 获得城市区域筛选的省份信息
     * return :array
     */
    public function getSelectProvince()
    {
        $returnArr = $this->AreaService->getSelectProvince($this->systemUser);
        return api_output(0, $returnArr);
    }

    /**
     * 获得城市区域筛选的城市信息
     * return :array
     */
    public function getSelectCity()
    {
       
        $param['id']  = $this->request->param("id", "", "intval");
        $param['name']  = $this->request->param("name", "", "trim");
        $param['type']  = $this->request->param("type", "", "trim");
        $returnArr = $this->AreaService->getSelecCity($param, $this->systemUser);
        return api_output(0, $returnArr);
    }

    /**
     * 获得城市区域筛选的区域信息
     * return :array
     */
    public function getSelectArea()
    {
       
        $param['id']  = $this->request->param("id", "", "intval");
        $param['name']  = $this->request->param("name", "", "trim");
        $returnArr = $this->AreaService->getSelectArea($param, $this->systemUser);
        return api_output(0, $returnArr);
      
    }

    /**
     * 获取乡镇/街道
     * @return \json
     * @author: 张涛
     * @date: 2021/02/04
     */
    public function getSelectStreet()
    {
        $param['id'] = $this->request->param("id", "", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        $lists = (new AreaStreetService())->getStreetByAreaId($param['id']);
        return api_output(0, ['error' => 0, 'list' => $lists]);
    }

    /**
     * 获取所有城市（省市区三级）并以树状图结构返回
     * @author: 张涛
     * @date: 2021/02/26
     */
    public function getAllArea()
    {
        $returnArr = $this->AreaService->getAllArea(0, 'area_id,area_pid,area_name,is_open,area_type');
        return api_output(0, $returnArr);
    }


    /**
     * 获得省份和城市信息
     * return :array
     */
    public function getSelectProvinceAndCity()
    {
        $returnArr = (new AreaService())->getSelectProvinceAndCity($this->systemUser);
        return api_output(0, $returnArr);
    }



}