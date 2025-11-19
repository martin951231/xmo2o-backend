<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/9/9 13:36
 */
namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseNewEnerpyPileService;
use app\community\model\service\HouseNewPileService;

class PileController extends CommunityBaseController{

    /**
     * 编辑站点信息
     * @author:zhubaodi
     * @date_time: 2022/9/9 14:02
     */
    public function editPileConfig(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['pile_name']=$this->request->param('pile_name','','trim');
        $data['lat'] = $this->request->param('lat', '', 'trim');
        $data['lng'] = $this->request->param('long', '', 'trim');
        $data['capacitance']=$this->request->param('capacitance','','trim');
        $data['park_type']=$this->request->param('park_type',1,'intval');
        $data['pile_phone']=$this->request->param('pile_phone','','trim');
        $data['park_desc']=$this->request->param('park_desc','','trim');
        $data['open_time_desc']=$this->request->param('open_time_desc','','trim');
        $data['work_time_start']=$this->request->param('work_time_start','','trim');
        $data['work_time_end']=$this->request->param('work_time_end','','trim');
        $data['remark']=$this->request->param('remark','','trim');
        $data['img']=$this->request->param('img','','trim');
        $data['min_money']=$this->request->param('min_money','','trim');
        
        if (empty($data['pile_name'])){
            return api_output_error(1001, '站点名称不能为空');
        }
        if (empty($data['lng'])||empty($data['lat'])){
            return api_output_error(1001, '站点位置不能为空');
        }
        if (empty($data['pile_phone'])){
            return api_output_error(1001, '客服电话不能为空');
        }
        if (empty($data['park_type'])){
            return api_output_error(1001, '停车收费类型不能为空');
        }
        if (empty($data['park_desc'])){
            return api_output_error(1001, '停车说明不能为空');
        }
        if (empty($data['img'])){
            return api_output_error(1001, '电站图片不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $id = $pileService->editPileConfig($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id,'操作成功');
        
    }


    /**
     * 查询站点信息
     * @author:zhubaodi
     * @date_time: 2022/9/9 15:00
     */
    public function getPileConfig(){
         $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getPileConfig($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    /**
     * 充电桩设备统计
     * @author:zhubaodi
     * @date_time: 2022/9/9 16:31
     */
    public function countPileEquipment(){
      $data['village_id'] = $this->adminUser['village_id'];
      
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->countPileEquipment($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    /**
     * 查询设备列表
     * @author:zhubaodi
     * @date_time: 2022/9/9 17:06
     */
    public function getEquipmentList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['equipment_name']=$this->request->param('equipment_name','','trim');
        $data['equipment_num']=$this->request->param('equipment_num','','trim');
        $data['type']=$this->request->param('type','','intval');
        $data['status']=$this->request->param('status','','intval');
        $data['page']=$this->request->param('page',0,'intval');
        $data['limit']=$this->request->param('limit',15,'intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getEquipmentList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'查询成功');
    }


    /**
     * 查询设备详情
     * @author:zhubaodi
     * @date_time: 2022/9/13 8:49
     */
    public function getEquipmentDetail(){
       $data['village_id'] = $this->adminUser['village_id'];
       
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id',0,'intval');
        if (empty($data['id'])){
            return api_output_error(1001, '设备id不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getEquipmentDetail($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'查询成功');
    }

    /**
     * 添加设备
     * @author:zhubaodi
     * @date_time: 2022/9/13 8:50
     */
    public function addEquipment(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['brand']=$this->request->param('device_brand','','trim');
        if (empty($data['brand'])){
            return api_output_error(1001, '品牌名称不能为空');
        }
        $data['brandType']=$this->request->param('brand_type','','trim');
        if (empty($data['brandType'])){
            return api_output_error(1001, '品牌类型不能为空');
        }
        $data['equipment_name']=$this->request->param('equipment_name','','trim');
        if (empty($data['equipment_name'])){
            return api_output_error(1001,'设备名称不能为空');
        }
        $data['equipment_num']=$this->request->param('equipment_num','','trim');
        if (empty($data['equipment_num'])){
            return api_output_error(1001,'设备唯一编码（桩编号）不能为空');
        }
        $data['type']=$this->request->param('pile_type','1','intval');//1直流充电桩 2交流充电桩
        if (empty($data['type'])){
            return api_output_error(1001,'充电桩类型不能为空');
        }
        $data['power']=$this->request->param('power','0','intval');//设备功率
        if (empty($data['power'])){
            return api_output_error(1001,'设备功率不能为空');
        }
        if ($data['power']<0){
            return api_output_error(1001,'设备功率不能小于0');
        }
        $data['min_voltage']=$this->request->param('min_voltage','','intval');//最小电压
        if (empty($data['min_voltage'])){
            return api_output_error(1001,'最小电压不能为空');
        }
        if ($data['min_voltage']<0){
            return api_output_error(1001,'设备最小电压不能小于0');
        }
        $data['max_voltage']=$this->request->param('max_voltage','','intval');//最大电压
        if (empty($data['max_voltage'])){
            return api_output_error(1001,'最大电压不能为空');
        }
        if ($data['max_voltage']<0){
            return api_output_error(1001,'设备最大电压不能小于0');
        }
        if ($data['min_voltage']>$data['max_voltage']){
            return api_output_error(1001,'设备最小电压不能大于最大电压');
        }
        $data['min_electric_current']=$this->request->param('min_electric_current','','intval');//最小电流
        if (empty($data['min_electric_current'])){
            return api_output_error(1001,'最小电流不能为空');
        }
        if ($data['min_electric_current']<0){
            return api_output_error(1001,'设备最小电流不能小于0');
        }
        $data['max_electric_current']=$this->request->param('max_electric_current','','intval');//最大电流
        if (empty($data['max_electric_current'])){
            return api_output_error(1001,'最大电流不能为空');
        }
        if ($data['max_electric_current']<0){
            return api_output_error(1001,'设备最大电流不能小于0');
        }
        if ($data['min_electric_current']>$data['max_electric_current']){
            return api_output_error(1001,'设备最小电流不能大于最大电流');
        }
        $data['min_temperature']=$this->request->param('min_temperature','','intval');//最低温度
        if (empty($data['min_temperature'])){
            return api_output_error(1001,'最低温度不能为空');
        }
        $data['max_temperature']=$this->request->param('max_temperature','','intval');//最高温度
        if (empty($data['max_temperature'])){
            return api_output_error(1001,'最高温度不能为空');
        }
        if ($data['max_temperature']<$data['min_temperature']){
            return api_output_error(1001,'设备最高温度不能小于最低温度');
        }
        $data['socket_num']=$this->request->param('socket_num',1,'intval');
        $data['remark']=$this->request->param('remark','','trim');
        
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->addEquipment($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功'); 
    }

    /**
     * 编辑设备
     * @author:zhubaodi
     * @date_time: 2022/9/13 8:50
     */
    public function editEquipment(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id',0,'intval');
        if (empty($data['id'])){
            return api_output_error(1001, '设备id不能为空');
        }
        $data['brand']=$this->request->param('device_brand','','trim');
        if (empty($data['brand'])){
            return api_output_error(1001, '品牌名称不能为空');
        }
        $data['brandType']=$this->request->param('brand_type','','trim');
        if (empty($data['brandType'])){
            return api_output_error(1001, '品牌类型不能为空');
        }
        $data['equipment_name']=$this->request->param('equipment_name','','trim');
        if (empty($data['equipment_name'])){
            return api_output_error(1001,'设备名称不能为空');
        }
        $data['equipment_num']=$this->request->param('equipment_num','','trim');
        if (empty($data['equipment_num'])){
            return api_output_error(1001,'设备唯一编码（桩编号）不能为空');
        }
        $data['type']=$this->request->param('pile_type','1','intval');//1直流充电桩 2交流充电桩
        if (empty($data['type'])){
            return api_output_error(1001,'充电桩类型不能为空');
        }
        $data['power']=$this->request->param('power','0','intval');//设备功率
        if (empty($data['power'])){
            return api_output_error(1001,'设备功率不能为空');
        }
        if ($data['power']<0){
            return api_output_error(1001,'设备功率不能小于0');
        }
        $data['min_voltage']=$this->request->param('min_voltage','','intval');//最小电压
        if (empty($data['min_voltage'])){
            return api_output_error(1001,'最小电压不能为空');
        }
        if ($data['min_voltage']<0){
            return api_output_error(1001,'设备最小电压不能小于0');
        }
        $data['max_voltage']=$this->request->param('max_voltage','','intval');//最大电压
        if (empty($data['max_voltage'])){
            return api_output_error(1001,'最大电压不能为空');
        }
        if ($data['max_voltage']<0){
            return api_output_error(1001,'设备最大电压不能小于0');
        }
        if ($data['min_voltage']>$data['max_voltage']){
            return api_output_error(1001,'设备最小电压不能大于最大电压');
        }
        $data['min_electric_current']=$this->request->param('min_electric_current','','intval');//最小电流
        if (empty($data['min_electric_current'])){
            return api_output_error(1001,'最小电流不能为空');
        }
        if ($data['min_electric_current']<0){
            return api_output_error(1001,'设备最小电流不能小于0');
        }
        $data['max_electric_current']=$this->request->param('max_electric_current','','intval');//最大电流
        if (empty($data['max_electric_current'])){
            return api_output_error(1001,'最大电流不能为空');
        }
        if ($data['max_electric_current']<0){
            return api_output_error(1001,'设备最大电流不能小于0');
        }
        if ($data['min_electric_current']>$data['max_electric_current']){
            return api_output_error(1001,'设备最小电流不能大于最大电流');
        }
        $data['min_temperature']=$this->request->param('min_temperature','','intval');//最低温度
        if (empty($data['min_temperature'])){
            return api_output_error(1001,'最低温度不能为空');
        }
        $data['max_temperature']=$this->request->param('max_temperature','','intval');//最高温度
        if (empty($data['max_temperature'])){
            return api_output_error(1001,'最高温度不能为空');
        }
        if ($data['max_temperature']<$data['min_temperature']){
            return api_output_error(1001,'设备最高温度不能小于最低温度');
        }
        $data['socket_num']=$this->request->param('socket_num','','intval');
        $data['remark']=$this->request->param('remark','','trim');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->editEquipment($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    
    public function delEquipment(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id',0,'intval');
        if (empty($data['id'])){
            return api_output_error(1001, '设备id不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->delEquipment($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }
    
    /**
     * 获取品牌列表
     * @author:zhubaodi
     * @date_time: 2022/9/13 9:22
     */
    public function getBrandlist(){

        $res=[
            [
                'brand_key' => "brand_gt",
                'id' => "114",
                'logo' => null,
                'sub_title' => "工泰",
                'title' => "工泰"
            ]
        ];
        return api_output(0, $res,'查询成功');
    }


    /**
     * 获取品牌类型列表
     * @author:zhubaodi
     * @date_time: 2022/9/13 9:25
     */
    public function getBrandTypeList(){
        $data['brand_id']=$this->request->param('brand_id','','intval');//品牌id
        if (empty($data['brand_id'])){
            return api_output_error(1001,'品牌id不能为空');
        }
        
        $res=[
                ['brand_id'=>"114", 
                'desc' => null,
                'device_type' => "1",
                'hardware_config' => null,
                'icon' => "https://o2o-service.pigcms.com/icon/chongdianzhuang.png",
                'id' => "1117",
                'series_img' => null,
                'series_key' => "GTDC120KW",
                'series_title' => "GTDC120KW",
                'sub_series_key' => "GTDC120KW",
                'sub_series_type' => "4",
                'title' => "GTDC120KW"
                ],
        ];
        return api_output(0, $res,'查询成功');
    }

    /**
     * 查询订单列表
     * @author:zhubaodi
     * @date_time: 2022/9/14 21:01
     */
    public function getOrderList(){
       $data['village_id'] = $this->adminUser['village_id'];
       
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['uid']=$this->request->param('uid','','intval');
        $data['order_no']=$this->request->param('order_no','','trim');
        $data['equipment_name']=$this->request->param('equipment_name','','trim');
        $data['status']=$this->request->param('status',0,'intval');
        $data['start_time']=$this->request->param('start_time','','trim');
        $data['end_time']=$this->request->param('end_time','','trim');
        $data['page']=$this->request->param('page',0,'intval');
        $data['limit']=$this->request->param('limit',15,'intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getOrderList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    /**
     * 查询订单详情
     * @author:zhubaodi
     * @date_time: 2022/9/14 21:01
     */
    public function getOrderDetail(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id',0,'intval');
        if (empty($data['id'])){
            return api_output_error(1001, '订单id不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getOrderDetail($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    /**
     * 查询退款订单列表
     * @author:zhubaodi
     * @date_time: 2022/9/14 21:01
     */
    public function getRefundOrderList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getRefundOrderList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    /**
     * 查询退款订单详情
     * @author:zhubaodi
     * @date_time: 2022/9/14 21:01
     */
    public function getRefundOrderDetail(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id',0,'intval');
        if (empty($data['id'])){
            return api_output_error(1001, '订单id不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getRefundOrderDetail($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    /**
     * 生成设备枪头二维码
     * @author:zhubaodi
     * @date_time: 2022/9/26 20:53
     */
    public function getEquipmentCode(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id',0,'intval');
        if (empty($data['id'])){
            return api_output_error(1001, '设备id不能为空');
        }
        $data['socket']=$this->request->param('socket',0,'intval');
        if (empty($data['socket'])){
            return api_output_error(1001, '枪头编号不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getEquipmentCode($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功'); 
    }


    /**
     * 查询收费标准列表
     * @author:zhubaodi
     * @date_time: 2022/9/29 20:53
     */
    public function getRuleChargeList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['page']=$this->request->param('page',0,'intval');
        $data['limit']=$this->request->param('limit',15,'intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getRuleChargeList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }


    /**
     * 查询设备绑定的收费标准
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getRuleEquipmentList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['rule_id']=$this->request->param('rule_id',0,'intval');
        if (empty($data['rule_id'])){
            return api_output_error(1001, '收费标准id不能为空');
        }
        $data['page']=$this->request->param('page',0,'intval');
        $data['limit']=$this->request->param('limit',15,'intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getRuleEquipmentList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }



    /**
     * 查询设备绑定的收费标准
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function bind(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id',0,'intval');
        if (empty($data['id'])){
            return api_output_error(1001, '设备id不能为空');
        }
        $data['rule_id']=$this->request->param('rule_id',0,'intval');
        if (empty($data['rule_id'])){
            return api_output_error(1001, '收费标准id不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->bind($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }



    /**
     * 查询小区充电账户列表
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getUserMoneyList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['name']=$this->request->param('name','','trim');
        $data['phone']=$this->request->param('phone','','trim');
        $data['card_no']=$this->request->param('card_no','','trim');//逻辑卡
        $data['page']=$this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('limit',15,'intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getUserMoneyList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    /**
     * 充值明细
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getUserMoneyLog(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id','','intval');
        if (empty($data['id'])){
            return api_output_error(1001, '账户id不能为空');
        }
        $data['page']=$this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('limit',15,'intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getUserMoneyLog($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }


    /**
     * 查询充电卡号
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getUserCardInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id','','intval');
        if (empty($data['id'])){
            return api_output_error(1001, '账户id不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getUserCardInfo($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }



    /**
     * 编辑充电卡号
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function editUserCard(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id','','intval');
        if (empty($data['id'])){
            return api_output_error(1001, '账户id不能为空');
        }
        $data['card_no']=$this->request->param('card_no','','trim');
        if (empty($data['card_no'])){
            return api_output_error(1001, '逻辑卡号不能为空');
        }
        $data['pile_card_no']=$this->request->param('pile_card_no','','intval');
        if (empty($data['pile_card_no'])){
            return api_output_error(1001, '物理卡号不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->editUserCard($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }



    /**
     * 查询协议内容
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getNews(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['type']=1;
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getNews($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }


    /**
     * 编辑协议内容
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function editNews(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['type']=$this->request->param('type',1,'intval');
        $data['title']=$this->request->param('title','','trim');
        if (empty($data['title'])){
            return api_output_error(1001, '标题不能为空');
        }
        $data['content']=$this->request->param('content','','trim');
        if (empty($data['content'])){
            return api_output_error(1001, '内容不能为空');
        }
        $data['status']=$this->request->param('status','','intval');
        if (empty($data['status'])){
            return api_output_error(1001, '状态不能为空');
        }
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->editNews($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }

    /**
     * 余额提现
     * @author:zhubaodi
     * @date_time: 2022/10/18 10:38
     */
    public function withdraw(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['current_money'] = $this->request->param('current_money', '', 'trim');
        if (empty($data['current_money'])) {
            return api_output_error(1001, '提现金额不能为空');
        }
        $data['type'] = $this->request->param('type', '', 'intval');//1提现到微信 2提现到平台余额
        if (empty($data['type'])) {
            return api_output_error(1001, '提现金额不能为空');
        }
        $data['id']=$this->request->param('id','','intval');
        if (empty($data['id'])){
            return api_output_error(1001, '账户id不能为空');
        }
        $data['reason']=$this->request->param('reason','','trim');
        $data['true_name']=$this->request->param('true_name','','trim');
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->withdraw($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }



    /**
     * 查询余额提现申请列表
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getWithdrawList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['status']=$this->request->param('status','','intval');
        $data['name']=$this->request->param('name','','trim');
        $data['phone']=$this->request->param('phone','','trim');
        $data['order_id']=$this->request->param('order_id','','intval');
        $data['start_time']=$this->request->param('start_time','','trim');
        $data['end_time']=$this->request->param('end_time','','trim');
        $data['page']=$this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('pageSize',15,'intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getWithdrawList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }




    /**
     * 查询余额提现申请列表
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getWithdrawInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id']=$this->request->param('id','','intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getWithdrawInfo($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }


    /**
     * 提现申请审核
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function checkWithdraw(){
        $data['village_id'] = $this->adminUser['village_id'];
      
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['reason']=$this->request->param('reason','','trim');
        $data['status']=$this->request->param('status','','intval');
        $data['id']=$this->request->param('id','','intval');
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->checkWithdraw($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }
    

    public function startCharging_tcp(){
        $data = $_POST;
        $pileService=new HouseNewEnerpyPileService();
        try {
            $str = $pileService->startCharging_tcp($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $str,'操作成功');
    }

    public function stopCharging_tcp(){

        $data = $_POST;
        $pileService=new HouseNewEnerpyPileService();
        try {
            $str = $pileService->stopCharging_tcp($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $str,'操作成功');
    }
    
    public function setPileTimeAndCharge() {
        $data = $_POST;
        $pileService=new HouseNewEnerpyPileService();
        try {
            $str = $pileService->setPileTimeAndCharge($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $str,'操作成功');
    }

    public function getPileRealTimeInfo(){
        $data = $_POST;
        $pileService=new HouseNewEnerpyPileService();
        try {
            $str = $pileService->getPileRealTimeInfo($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $str,'操作成功');
    }

    public function settlementOrder_tcp(){
        $data = $_POST;
        $pileService=new HouseNewEnerpyPileService();
        try {
            $str = $pileService->settlementOrder_tcp($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $str,'操作成功');
    }

    public function setPileTime(){
       // $data['id'] = $this->request->param('id','','trim');
        $data['id'] =8;
        $pileService=new HouseNewEnerpyPileService();
        try {
            $str = $pileService->setPileTime($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $str,'操作成功');
    }

    public function command_mqtt(){
        $data = $_POST;
        $pileService=new HouseNewPileService();
        try {
            $str = $pileService->command_mqtt($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $str,'操作成功');
    }


    public function test(){
        $str= 'd5f27ff878f75cf1a2ca4d246ed730ca45d945329e6953322cb05af6f0e6a1ae';
        $key='qccdz:898604951121700828215bccc1';
        $pileService=new HouseNewPileService();
        try {
            $str = $pileService->opensslDecrypt($str,$key);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $str,'操作成功');
    }
}