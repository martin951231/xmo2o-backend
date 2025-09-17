<?php

/**
 * 收费标准管理
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/10 11:47
 */

namespace app\community\controller\house_meter;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterService;

class MeterElectricPriceController extends CommunityBaseController{


    /**
     * 获取城市列表
     * @author:zhubaodi
     * @date_time: 2021/4/10 9:57
     */
    public function getAreaList() {
        // 获取登录信息
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
       //  $admin_id=1;
        if (!$admin_id){
            return api_output(1002,[],'请先登录！');
        }
        $page = $this->request->param('page',0,'intval');
        $limit = 20;
        $serviceHouseMeter = new HouseMeterService();
        $area_list = $serviceHouseMeter->getAreaList($page,$limit);
        $area_list['total_limit'] = $limit;
        return api_output(0,$area_list);
    }

    /**
     * 获取城市下的收费标准
     * @author:zhubaodi
     * @date_time: 2021/4/10 9:57
     * @param integer city_id 城市id
     */
    public function getAreaPriceList() {
        // 获取登录信息
        $admin_id=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id){
            return api_output(1002,[],'请先登录！');
        }
        $city_id=$this->request->param('city_id','','intval');
        $serviceHouseMeter = new HouseMeterService();
        $price_list = $serviceHouseMeter->getAreaPriceList($city_id);
        return api_output(0,$price_list);
    }


    /**
     * 添加收费标准数据
     * @param 传参
     * array (
     *  'type'=> array(
     *          'unit_price'=>'城市名称 必传',
     *          'rate'=>'倍率',
     *
     *         ),
     *  'area_id'=> '城市id',
     * )
     * @author: zhubaodi
     * @date_time: 2021/4/10 10:20
     * @return \json
     */
    public function meterElectricPriceAdd() {
        // 获取登录信息
        $data['admin_id']=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']){
            return api_output(1002,[],'用户未登录或登录已失效，请先登录！！');
        }

        $data['city_id'] = $this->request->param('city_id','','intval');
        if (empty( $data['city_id'])) {
            return api_output(1001,[],'请上传城市id！');
        }
        $data['type'] = $this->request->param('type','','trim');
        $data['price']=[];
        if (empty( $data['type'])) {
            return api_output(1001,[],'请上传收费标准数据！');
        }

        if (empty($data['type']['house_price'])&&empty($data['type']['shop_price'])&&empty($data['type']['work_price'])){
            return api_output(1001,[],'请上传收费标准数据！');
        }
        if (!empty($data['type']['house_price'])){

            if (is_numeric($data['type']['house_price'])==''){
                return api_output(1001,[],'请正确上传单价！');
            }
            if (is_numeric($data['type']['house_rate'])==''){
                return api_output(1001,[],'请正确上传倍率！');
            }
            if (positive_integer($data['type']['house_price'],true,false)=='true'){
                $data['price'][1]['unit_price']=$data['type']['house_price'];
            }else{
                return api_output(1001,[],'单价不能为负数！');
            }
            if (positive_integer($data['type']['house_rate'],true,false)=='true'){
                $data['price'][1]['rate']=$data['type']['house_rate'];
            }else{
                return api_output(1001,[],'倍率不能为负数！');
            }

        }
        if (!empty($data['type']['shop_price'])){

            if (is_numeric($data['type']['shop_price'])==''){
                return api_output(1001,[],'请正确上传单价！');
            }
            if (is_numeric($data['type']['shop_rate'])==''){
                return api_output(1001,[],'请正确上传倍率！');
            }
            if (positive_integer($data['type']['shop_price'],true,false)=='true'){
                $data['price'][2]['unit_price']=$data['type']['shop_price'];
            }else{
                return api_output(1001,[],'单价不能为负数！');
            }

            if (positive_integer($data['type']['shop_rate'],true,false)=='true'){
                $data['price'][2]['rate']=$data['type']['shop_rate'];
            }else{
                return api_output(1001,[],'倍率不能为负数！');
            }
        }
        if (!empty($data['type']['work_price'])){

            if (is_numeric($data['type']['work_price'])==''){
                return api_output(1001,[],'请正确上传单价！');
            }
            if (is_numeric($data['type']['work_rate'])==''){
                return api_output(1001,[],'请正确上传倍率！');
            }

            if (positive_integer($data['type']['work_price'],true,false)=='true'){
                $data['price'][3]['unit_price']=$data['type']['work_price'];
            }else{
                return api_output(1001,[],'倍率不能为负数！');
            }
            if (positive_integer($data['type']['work_rate'],true,false)=='true'){
                $data['price'][3]['rate']=$data['type']['work_rate'];
            }else{
                return api_output(1001,[],'单价不能为负数！');
            }

        }
        $serviceHouseMeter = new HouseMeterService();
        try {
            $price_id = $serviceHouseMeter->addMeterElectricPrice($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($price_id){
            return api_output(0,['price_id' => $price_id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }


    }


    /**
     * 编辑收费标准数据
     * @param 传参
     * array (
     *  'type'=> array(
     *          'unit_price'=>'城市名称 必传',
     *          'rate'=>'倍率',
     *          'id'=>'收费标准id',
     *
     *         ),
     *  'area_id'=> '城市id',
     * )
     * @author: zhubaodi
     * @date_time: 2021/4/10 10:20
     * @return \json
     */
    public function meterElectricPriceEdit() {
        // 获取登录信息
        $data['admin_id']=isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']){
            return api_output(1002,[],'用户未登录或登录已失效，请先登录！！');
        }

        $data['city_id'] = $this->request->param('city_id','','intval');
        if (empty( $data['city_id'])) {
            return api_output(1001,[],'请上传城市id！');
        }
        $data['type'] = $this->request->param('type','','trim');
        if (empty( $data['type'])) {
            return api_output(1001,[],'请上传收费标准数据！');
        }
        if ($data['type']){
            foreach ($data['type'] as $value){
                if (empty($value['unit_price'])){
                    return api_output(1001,[],'请上传单价！');
                }
                if (empty($value['id'])){
                    return api_output(1001,[],'请上传收费标准id！');
                }
            }
        }

        $serviceHouseMeter = new HouseMeterService();

        try {
            $price_id = $serviceHouseMeter->editMeterElectricPrice($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        if ($price_id){
            return api_output(0,['price_id' => $price_id],'编辑成功');
        }else{
            return api_output(1003,[],'编辑失败！');
        }
    }

}
