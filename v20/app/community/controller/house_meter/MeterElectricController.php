<?php

/**
 * 权限管理
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/9 11:47
 */

namespace app\community\controller\house_meter;

use app\common\controller\common\UploadFileController;
use app\common\model\service\UploadFileService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterRedisService;
use app\community\model\service\HouseMeterService;
use app\community\model\service\HouseMeterUserService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MeterElectricController extends CommunityBaseController
{


    /**
     * 获取电表列表
     * @param 传参
     * array (
     *  'province_id'=> '省id',
     *  'city_id'=> '市id',
     *  'area_id'=> '区id',
     *  'street_id'=> '街道id',
     *  'community_id'=> '社区id',
     *  'village_id'=> '小区id',
     *  'single_id'=> '楼栋id',
     *  'floor_id'=> '单元id',
     *  'layer_id'=> '楼层id',
     *  'vacancy_id'=> '房屋id',
     *  'group_id'=> '分组id',
     *  'electric_name'=> '电表名称',
     * )
     * @author:zhubaodi
     * @date_time: 2021/4/9 11:49
     */
    public function meterElectricList()
    {
        // 获取登录信息
        $data['admin_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']) {
            return api_output(1002, [], '请先登录！');
        }
        $data['province_id'] = $this->request->param('province', '', 'intval');
        $data['city_id'] = $this->request->param('city', '', 'intval');
        $data['area_id'] = $this->request->param('area', '', 'intval');
        $data['street_id'] = $this->request->param('street', '', 'intval');
        $data['community_id'] = $this->request->param('community', '', 'intval');
        $data['village_id'] = $this->request->param('village', '', 'intval');
        $data['single_id'] = $this->request->param('single', '', 'intval');
        $data['floor_id'] = $this->request->param('floor', '', 'intval');
        $data['layer_id'] = $this->request->param('layer', '', 'intval');
        $data['vacancy_id'] = $this->request->param('vacancy', '', 'intval');
        $data['group_name'] = $this->request->param('group_name', '', 'intval');
        $data['electric_name'] = $this->request->param('electric_name', '', 'trim');
        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['limit'] = 20;
        $serviceHouseMeter = new HouseMeterService();
        $electricList = $serviceHouseMeter->getMeterElectricList($data);
        $electricList['total_limit'] = $data['limit'];
        return api_output(0, $electricList);
    }


    /**
     * 添加电表
     * @param 传参
     * array (
     *  'electric_name'=> '电表名称 必传',
     *  'device_id'=> '设备ID',
     *  'province_id'=> '省id',
     *  'city_id'=> '市id',
     *  'area_id'=> '区id',
     *  'street_id'=> '街道id',
     *  'community_id'=> '社区id',
     *  'village_id'=> '小区id',
     *  'single_id'=> '楼栋id',
     *  'floor_id'=> '单元id',
     *  'layer_id'=> '楼层id',
     *  'vacancy_id'=> '房屋id',
     *  'group_id'=> '分组id',
     *  'electric_price_id'=> '收费标准',
     * )
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/4/9 17:20
     */
    public function meterElectricAdd()
    {
        // 获取登录信息
        $data['admin_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }

        $data['electric_name'] = $this->request->param('electric_name', '', 'trim');
        if (empty($data['electric_name'])) {
            return api_output(1001, [], '请上传电表名称！');
        }
        $data['electric_address'] = $this->request->param('electric_address', '', 'trim');
        if (empty($data['electric_address'])) {
            return api_output(1001, [], '请上传电表地址！');
        }
        $data['province_id'] = $this->request->param('province_id', '', 'intval');
        if (empty($data['province_id'])) {
            return api_output(1001, [], '请选择省份！');
        }
        $data['city_id'] = $this->request->param('city_id', '', 'intval');
        if (empty($data['city_id'])) {
            return api_output(1001, [], '请选择市！');
        }
        $data['area_id'] = $this->request->param('area_id', '', 'intval');
        if (empty($data['area_id'])) {
            return api_output(1001, [], '请选择区！');
        }
        $data['street_id'] = $this->request->param('street_id', '', 'intval');
        if (empty($data['street_id'])) {
            return api_output(1001, [], '请选择街道！');
        }
        $data['community_id'] = $this->request->param('community_id', '', 'intval');
        if (empty($data['community_id'])) {
            return api_output(1001, [], '请选择社区！');
        }
        $data['village_id'] = $this->request->param('village_id', '', 'intval');
        if (empty($data['village_id'])) {
            return api_output(1001, [], '请选择小区！');
        }
        $data['single_id'] = $this->request->param('single_id', '', 'intval');
        if (empty($data['single_id'])) {
            return api_output(1001, [], '请选择楼栋！');
        }
        $data['floor_id'] = $this->request->param('floor_id', '', 'intval');
        if (empty($data['floor_id'])) {
            return api_output(1001, [], '请选择单元！');
        }
        $data['layer_id'] = $this->request->param('layer_id', '', 'intval');
        if (empty($data['layer_id'])) {
            return api_output(1001, [], '请选择楼层！');
        }
        $data['vacancy_id'] = $this->request->param('vacancy_id', '', 'intval');
        if (empty($data['vacancy_id'])) {
            return api_output(1001, [], '请选择房间！');
        }
        $data['group_id'] = $this->request->param('group_id', '', 'intval');
        if (empty($data['group_id'])) {
            return api_output(1001, [], '请选择分组！');
        }
        $data['measure_id'] = $this->request->param('measure_id', '', 'intval');
        if (empty($data['measure_id'])) {
            return api_output(1001, [], '请选择测量点！');
        }
        $data['electric_type'] = $this->request->param('electric_type', '', 'intval');
        if (empty($data['electric_type'])) {
            return api_output(1001, [], '请选择电表类型！');
        }
        $data['electric_price_id'] = $this->request->param('electric_price_id', '', 'intval');

        $data['unit_price'] = $this->request->param('unit_price', '', 'trim');
        $data['rate'] = $this->request->param('rate', '', 'trim');

        if (!empty($data['unit_price'])&&is_numeric($data['unit_price'])==''){
            return api_output(1001,[],'请正确上传单价！');
        }
        if (!empty($data['rate'])&&is_numeric($data['rate'])==''){
            return api_output(1001,[],'请正确上传倍率！');
        }

        $serviceHouseMeter = new HouseMeterService();
        try {
            $electric_id = $serviceHouseMeter->addMeterElectric($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        if ($electric_id) {
            $serviceRedis = new HouseMeterRedisService();
            $bang = $serviceRedis->download_bang($electric_id);
            if (!is_numeric($bang)) {
                $serviceHouseMeter->editElectricStatus($electric_id,1);
            }
            return api_output(0, ['electric_id' => $electric_id], '添加成功');
        } else {
            return api_output(1003, [], '添加失败！');
        }
    }


    /**
     * 编辑电表
     * @param 传参
     * array (
     * 'electric_id'=> '电表id 必传',
     *  'electric_name'=> '电表名称 必传',
     *  'device_id'=> '设备ID',
     *  'province_id'=> '省id',
     *  'city_id'=> '市id',
     *  'area_id'=> '区id',
     *  'street_id'=> '街道id',
     *  'community_id'=> '社区id',
     *  'village_id'=> '小区id',
     *  'single_id'=> '楼栋id',
     *  'floor_id'=> '单元id',
     *  'layer_id'=> '楼层id',
     *  'vacancy_id'=> '房屋id',
     *  'group_id'=> '分组id',
     *  'electric_price_id'=> '收费标准',
     * )
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/4/9 17:20
     */
    public function meterElectricEdit()
    {
        // 获取登录信息
        $data['admin_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }

        $data['electric_id'] = $this->request->param('id', '', 'intval');
        if (empty($data['electric_id'])) {
            return api_output(1001, [], '请上传电表id！');
        }
        $data['electric_name'] = $this->request->param('electric_name', '', 'trim');
        if (empty($data['electric_name'])) {
            return api_output(1001, [], '请上传电表名称！');
        }
        $data['electric_address'] = $this->request->param('electric_address', '', 'trim');
        if (empty($data['electric_address'])) {
            return api_output(1001, [], '请上传电表地址！');
        }
        $data['province_id'] = $this->request->param('province_id', '', 'intval');
        if (empty($data['province_id'])) {
            return api_output(1001, [], '请选择省份！');
        }
        $data['city_id'] = $this->request->param('city_id', '', 'intval');
        if (empty($data['city_id'])) {
            return api_output(1001, [], '请选择市！');
        }
        $data['area_id'] = $this->request->param('area_id', '', 'intval');
        if (empty($data['area_id'])) {
            return api_output(1001, [], '请选择区！');
        }
        $data['street_id'] = $this->request->param('street_id', '', 'intval');
        if (empty($data['street_id'])) {
            return api_output(1001, [], '请选择街道！');
        }
        $data['community_id'] = $this->request->param('community_id', '', 'intval');
        if (empty($data['community_id'])) {
            return api_output(1001, [], '请选择社区！');
        }
        $data['village_id'] = $this->request->param('village_id', '', 'intval');
        if (empty($data['village_id'])) {
            return api_output(1001, [], '请选择小区！');
        }
        $data['single_id'] = $this->request->param('single_id', '', 'intval');
        if (empty($data['single_id'])) {
            return api_output(1001, [], '请选择楼栋！');
        }
        $data['floor_id'] = $this->request->param('floor_id', '', 'intval');
        if (empty($data['floor_id'])) {
            return api_output(1001, [], '请选择单元！');
        }
        $data['layer_id'] = $this->request->param('layer_id', '', 'intval');
        if (empty($data['layer_id'])) {
            return api_output(1001, [], '请选择楼层！');
        }
        $data['vacancy_id'] = $this->request->param('vacancy_id', '', 'intval');
        if (empty($data['vacancy_id'])) {
            return api_output(1001, [], '请选择房间！');
        }
        $data['group_id'] = $this->request->param('group_id', '', 'intval');
        if (empty($data['group_id'])) {
            return api_output(1001, [], '请选择分组！');
        }
        $data['measure_id'] = $this->request->param('measure_id', '', 'intval');
        if (empty($data['measure_id'])) {
            return api_output(1001, [], '请选择测量点！');
        }
        $data['electric_type'] = $this->request->param('electric_type', '', 'intval');
        if (empty($data['electric_type'])) {
            return api_output(1001, [], '请选择电表类型！');
        }
        $data['electric_price_id'] = $this->request->param('electric_price_id', '', 'intval');

        $data['unit_price'] = $this->request->param('unit_price', '', 'trim');
        $data['rate'] = $this->request->param('rate', '', 'trim');

        if (!empty($data['unit_price'])&&is_numeric($data['unit_price'])==''){
            return api_output(1001,[],'请正确上传单价！');
        }
        if (!empty($data['rate'])&&is_numeric($data['rate'])==''){
            return api_output(1001,[],'请正确上传倍率！');
        }

        $serviceHouseMeter = new HouseMeterService();
        try {
            $electric_id = $serviceHouseMeter->editMeterElectric($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($electric_id) {
            return api_output(0, ['electric_id' => $electric_id], '编辑成功');
        } else {
            return api_output(1003, [], '编辑失败！');
        }
    }


    /**
     * 删除电表
     * @param integer $id
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/4/8 17:40
     */
    public function meterElectricDelete()
    {
        // 获取登录信息
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }

        $electric_id = $this->request->param('electric_id', '', 'intval');
        if (empty($electric_id)) {
            return api_output(1001, [], '请选择电脑！');
        }
        $serviceHouseMeter = new HouseMeterService();

        try {
            $electric_id = $serviceHouseMeter->deleteMeterElectric($admin_id, $electric_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($electric_id) {
            return api_output(0, ['electric_id' => $electric_id], '删除成功');
        } else {
            return api_output(1002, [], '删除失败');
        }
    }


    /**
     * 获取电表信息详情
     * @param integer $id
     * @date_time: 2021/4/8 16:31
     * @return \json
     * @author: zhubaodi
     */
    public function meterElectricInfo()
    {
        // 获取登录信息
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '请先登录！');
        }
        $id = $this->request->param('id', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $groupInfo = $serviceHouseMeter->getElectricInfo($admin_id, $id);
        return api_output(0, $groupInfo);

    }

    /**
     * 查询电表设置信息
     * @author:zhubaodi
     * @date_time: 2021/4/10 11:27
     */
    public function meterElectricSetInfo()
    {
        // 获取登录信息
        $data['admin_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']) {
            return api_output(1002, [], '请先登录！');
        }
        $serviceHouseMeter = new HouseMeterService();
        $electricSetInfo = $serviceHouseMeter->getMeterElectricSetInfo($data['admin_id']);
        return api_output(0, $electricSetInfo);
    }

    /**
     * 编辑电表设置信息
     * @param 传参
     * array (
     * 'electric_set'=> '断闸可二次激活电量 必传',
     *  'price_electric_set'=> '断闸后需缴纳电费的电量 必传',
     *  'meter_reading_type'=> '自动抄表扣费类型 必填',
     *  'meter_reading_date'=> '自动抄表扣费时间 必填',
     * )
     * @author:zhubaodi
     * @date_time: 2021/4/10 11:33
     */
    public function meterElectricSetEdit()
    {
        // 获取登录信息
        $data['admin_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$data['admin_id']) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }

        $data['electric_set'] = $this->request->param('electric_set', '', 'trim');
        /*if (empty($data['electric_set'])) {
            return api_output(1001, [], '请上传断闸可二次激活电量！');
        }*/

        if (is_numeric($data['electric_set'])==''&&!empty($data['electric_set'])){
            return api_output(1001, [], '请正确上传断闸可二次激活电量！');
        }

        $data['price_electric_set'] = $this->request->param('price_electric_set', '', 'trim');
       /* if (empty($data['price_electric_set'])) {
            return api_output(1001, [], '请上传断闸后需缴纳电费的电量！');
        }*/
        if (is_numeric($data['price_electric_set'])==''&&!empty($data['price_electric_set'])){
            return api_output(1001, [], '请正确上传断闸后需缴纳电费的电量！');
        }

        if ($data['price_electric_set']>=$data['electric_set']){
            return api_output(1001, [], '断闸后需缴纳电费的电量需小于断闸可二次激活电量！');
        }
        $data['meter_reading_type'] = $this->request->param('date_type', '', 'intval');
        if (empty($data['meter_reading_type'])) {
            return api_output(1001, [], '请选择自动抄表扣费类型！');
        }
        if ($data['meter_reading_type'] == 1) {
            $data['dateMouth'] = $this->request->param('dateMouth', '', 'trim');
            if (empty($data['dateMouth'])) {
                return api_output(1001, [], '请选择自动抄表扣费日期！');
            }
            $data['meter_reading_date'] = $this->request->param('dateDay', '', 'trim');
        }
        if ($data['meter_reading_type'] == 2) {
            $data['meter_reading_date'] = $this->request->param('dateDay', '', 'trim');
        }
        if (empty($data['meter_reading_date'])) {
            return api_output(1001, [], '请选择自动抄表扣费时间！');
        }


        $serviceHouseMeter = new HouseMeterService();


        try {
            $set_id = $serviceHouseMeter->editMeterElectricSet($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($set_id) {
            return api_output(0, ['set_id' => $set_id], '编辑成功');
        } else {
            return api_output(1003, [], '编辑失败！');
        }
    }


    /**
     * 查询测量点列表
     * @param integer $group_id
     * @author:zhubaodi
     * @date_time: 2021/4/19 17:33
     */
    public function getMeasureList()
    {
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        //  $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }

        $group_id = $this->request->param('group_id', '', 'intval');
        if (empty($group_id)) {
            return api_output(1001, [], '请选择分组！');
        }
        $page = $this->request->param('page', '', 'intval');
        $page=$page==0?1:$page;
        $serviceHouseMeter = new HouseMeterService();
        $measure_id = $serviceHouseMeter->getMeasureList($admin_id, $group_id,$page);
        return api_output(0, $measure_id);
    }

    /**
     * 开关闸
     * @param integer $electric_id 电表id
     * @param string $switch_type
     * @author:zhubaodi
     * @date_time: 2021/4/20 14:23
     */
    public function switch()
    {
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }
        $electric_id = $this->request->param('electric_id', '', 'intval');
        if (empty($electric_id)) {
            return api_output(1001, [], '请上传电表id！');
        }
        $switch_type = $this->request->param('switch_type', '', 'trim');
        if (empty($switch_type)) {
            return api_output(1001, [], '请选择开闸或是关闸！');
        }
        $serviceRedis = new HouseMeterRedisService();
        $switch = $serviceRedis->download_switch($electric_id, $switch_type);


        if (is_numeric($switch)) {
            return api_output(0, $switch, '指令下发成功');
        } else {
            return api_output(1003, [], '指令下发失败');
        }

    }

    /**
     * 开关闸
     * @param integer $electric_id 电表id
     * @author:zhubaodi
     * @date_time: 2021/4/20 14:23
     */
    public function bang()
    {
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $admin_id=1;
        if (!$admin_id) {
            return api_output(1002, [], '用户未登录或登录已失效，请先登录！！');
        }
        $electric_id = $this->request->param('electric_id', '', 'intval');
        if (empty($electric_id)) {
            return api_output(1001, [], '请上传电表id！');
        }
        $serviceRedis = new HouseMeterRedisService();
        $bang = $serviceRedis->download_bang($electric_id);


        if (is_numeric($bang)) {
            return api_output(0, $bang, '指令下发成功');
        } else {
            return api_output(1003, [], '指令下发失败');
        }

    }




    /**
     * 实时电量/日抄表
     * @author:zhubaodi
     * @date_time: 2021/4/20 14:23
     */
    public function now_reading()
    {
        $serviceRedis = new HouseMeterRedisService();
        $data['id']=$this->request->param('id', '', 'intval');
        $data['tcp_address']=$this->request->param('tcp_address', '', 'trim');
        $reading = $serviceRedis->download_meter_reading($data['id'],$data['tcp_address']);
        if (is_numeric($reading)) {
            return api_output(0, $reading, '指令下发成功');
        } else {
            return api_output(1003, [], '指令下发失败');
        }

    }

    /**
     * 单个电表实时电量/日抄表
     * @author:zhubaodi
     * @date_time: 2021/4/20 14:23
     */
    public function now_reading_electric()
    {
        $serviceRedis = new HouseMeterRedisService();
        $data['id']=$this->request->param('electric_id', '', 'intval');

        try {
            $reading = $serviceRedis->download_meter_reading_electric($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        if (is_numeric($reading)) {
            return api_output(0, $reading, '指令下发成功');
        } else {
            return api_output(1003, [], '指令下发失败');
        }

    }


    /**
     * 实时电量/日抄表
     * @author:zhubaodi
     * @date_time: 2021/4/20 14:23
     */
    public function upload_data()
    {
        $serviceRedis = new HouseMeterRedisService();
        $reading = $serviceRedis->upload_data();
        if (is_numeric($reading)) {
            return api_output(0, $reading, '数据获取成功');
        } else {
            return api_output(1003, [], '数据获取失败');
        }

    }

    /**
     * 查询省市区
     * @param integer $type
     * @author:zhubaodi
     * @date_time: 2021/4/25 16:40
     */
    public  function getAreaList(){
        $type=$this->request->param('type', '', 'intval');
        $pid=$this->request->param('pid', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $measure_id = $serviceHouseMeter->getAreasList($type, $pid);
        return api_output(0, $measure_id);
    }

    /**
     * 查询街道社区
     * @param integer $type
     * @author:zhubaodi
     * @date_time: 2021/4/25 16:40
     */
    public  function getCommunityList(){
        $type=$this->request->param('type', '', 'intval');
        $pid=$this->request->param('pid', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $measure_id = $serviceHouseMeter->getCommunityList($type, $pid);
        return api_output(0, $measure_id);
    }

    /**
     * 查询小区
     * @param integer $pid
     * @author:zhubaodi
     * @date_time: 2021/4/25 16:40
     */
    public  function getVillageList(){
        $pid=$this->request->param('pid', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $measure_id = $serviceHouseMeter->getVillageList($pid);
        return api_output(0, $measure_id);
    }

    /**
     * 查询楼栋
     * @param integer $pid
     * @author:zhubaodi
     * @date_time: 2021/4/25 16:40
     */
    public  function getSingleList(){
        $pid=$this->request->param('pid', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $measure_id = $serviceHouseMeter->getSingleList($pid);
        return api_output(0, $measure_id);
    }

    /**
     * 查询单元
     * @param integer $pid
     * @author:zhubaodi
     * @date_time: 2021/4/25 16:40
     */
    public  function getFloorList(){
        $pid=$this->request->param('pid', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $measure_id = $serviceHouseMeter->getFloorList($pid);
        return api_output(0, $measure_id);
    }
    /**
     * 查询楼层
     * @param integer $pid
     * @author:zhubaodi
     * @date_time: 2021/4/25 16:40
     */
    public  function getLayerList(){
        $pid=$this->request->param('pid', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $measure_id = $serviceHouseMeter->getLayerList($pid);
        return api_output(0, $measure_id);
    }
    /**
     * 查询房间
     * @param integer $pid
     * @author:zhubaodi
     * @date_time: 2021/4/25 16:40
     */
    public  function getVacancyList(){
        $pid=$this->request->param('pid', '', 'intval');
        $serviceHouseMeter = new HouseMeterService();
        $measure_id = $serviceHouseMeter->getVacancyList($pid);
        return api_output(0, $measure_id);
    }

    public function getMeterReadingList(){
        $electric_id = $this->request->param('electric_id','','intval');
        $time = $this->request->param('time','','trim');
        $page = $this->request->param('page','','intval');
        $limit = 10;
        $serviceHouseMeter = new HouseMeterService();
        $charging_list = $serviceHouseMeter->getMeterReadingList($time,$electric_id,$page,$limit);
        return api_output(0,$charging_list);
    }


    /**
     * 上传文件
     * @return \json
     */
    public function uploadFile()
    {
        $service = new HouseMeterService();
       // $service->saveExcel(11,22,'批量导入电表失败数据列表');
        $file = $this->request->file('file');
        $upload_dir = $this->request->param('upload_dir') . '/'. date('Ymd');
        try {
            $savenum = $service->upload($file, $upload_dir);
            return api_output(0, $savenum, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    public function meter_reading_order(){
        $service = new HouseMeterService();

        $time = $this->request->param('time') ;
        try {
          //   $savenum = $service->meter_reading_order($time);
            $savenum = $service->meter_reading1();

            return api_output(0, $savenum, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
