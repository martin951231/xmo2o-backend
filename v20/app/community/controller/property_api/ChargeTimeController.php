<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/6/10 16:30
 */
namespace app\community\controller\property_api;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseAbchinaSet;
use app\community\model\service\HouseMeterService;
use app\community\model\service\HouseNewChargeService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\PropertyMenuService;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
class ChargeTimeController extends CommunityBaseController{

    use CommonLogTraits;
    use AdminLoginTraits;
    /**
     * 新版收费管理时间设置
     * @param  string $take_effect_time,
     * @author:zhubaodi
     * @date_time: 2021/6/10 15:33
     */
    public function takeEffectTimeSet()
    {
        // 获取登录信息
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        //  $admin_id=1;
        if (empty($property_id)){
            return api_output(1002, [], '请先登录到物业后台！');
        }

        $take_effect_time = $this->request->param('take_effect_time', '', 'trim');

        $serviceHouseNewPorperty = new HouseNewPorpertyService();

        try {
            $set_id = $serviceHouseNewPorperty->takeEffectTimeSet($property_id,$take_effect_time);
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
     * 查询新版收费管理时间设置
     * @author:zhubaodi
     * @date_time: 2021/6/10 16:27
     */
    public function takeEffectTimeInfo()
    {
        // 获取登录信息
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $serviceHouseNewPorperty = new HouseNewPorpertyService();

        try {
            $takeEffectTimeInfo = $serviceHouseNewPorperty->getTakeEffectTimeInfo($property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $takeEffectTimeInfo);
    }


    /**
     * 查询收费科目列表
     * @author:zhubaodi
     * @date_time: 2021/6/11 14:01
     */
    public function chargeNumberList() {
        // 获取登录信息
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $whereArr=array();
        $charge_number_name = $this->request->param('charge_number_name','','trim');
        $charge_status= $this->request->param('charge_status',0,'intval'); //0全部 1开启 2关闭
        $charge_type = $this->request->param('charge_type','','trim');
        $page = $this->request->param('page',0,'intval');
        $limit = 20;
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            if($charge_number_name){
                $whereArr[]=array('charge_number_name','like','%'.$charge_number_name.'%');
            }
            if($charge_status){
                $whereArr[]=array('status','=',$charge_status);
            }
            if($charge_type){
                $whereArr[]=array('charge_type','=',$charge_type);
            }
            $number_list = $serviceHouseNewPorperty->getChargeNumberList($property_id,$page,$limit,$whereArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$number_list);
    }


    /**
     * 查询收费科目详情
     * @author: zhubaodi
     * @param integer $id
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function chargeNumberInfo() {
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->param('id','','intval');
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $number_info = $serviceHouseNewPorperty->getChargeNumberInfo($id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$number_info);

    }

    /**
     * 添加收费科目
     * @param 传参
     * array (
     *  'name'=> '收费科目名称',
     *  'charge_type'=> '收费类别',
     *  'status'=> '收费科目状态 1:正常 2:关闭',
     * )
     * @author: zhubaodi
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function addChargeNumber() {
        // 获取登录信息
       // $data['property_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $data['property_id'] =  $this->adminUser['property_id'];
        if (! $data['property_id']) {
            return api_output(1002, [], '请先登录到物业后台！');
        }

        $data['charge_number_name'] = $this->request->param('charge_number_name','','trim');
        if (empty( $data['charge_number_name'])) {
            return api_output(1001,[],'请上传收费科目名称！');
        }
        $data['charge_type'] = $this->request->param('charge_type','','trim');
        if (empty( $data['charge_type'])) {
            return api_output(1001,[],'请上传收费类别！');
        }
        $data['status'] = $this->request->param('status','','intval');
        if (empty($data['status'])) {
            return api_output(1001,[],'请选择收费科目状态！');
        }
        $data['water_type'] = $this->request->param('water_type',0,'intval');
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $number_id = $serviceHouseNewPorperty->addChargeNumber($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($number_id){
            return api_output(0,['electric_id' => $number_id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }
    }


    /**
     * 编辑收费科目
     * @param 传参
     * array (
     * 'id'=> '收费科目id',
     *  'name'=> '收费科目名称',
     *  'charge_type'=> '收费类别',
     *  'status'=> '收费科目状态 1:正常 2:关闭',
     * )
     * @author: zhubaodi
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function editChargeNumber() {
       // $data['property_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $data['property_id'] =  $this->adminUser['property_id'];
        if (! $data['property_id']) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $data['id'] = $this->request->param('id','','intval');
        if (empty( $data['id'])) {
            return api_output(1001,[],'请上传收费科目id！');
        }
        $data['charge_number_name'] = $this->request->param('charge_number_name','','trim');
        $data['charge_type'] = $this->request->param('charge_type','','trim');
        $data['status'] = $this->request->param('status','','intval');
        $data['water_type'] = $this->request->param('water_type',0,'intval');
        $data['rule_id'] = $this->request->param('rule_id','','trim');
        $data['adminUser'] = $this->adminUser;
        $data['uid'] = isset($this->adminUser['adminId']) && $this->adminUser['adminId'] ? $this->adminUser['adminId'] : $this->_uid;
        $data['login_role'] = $this->login_role;
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $group_id = $serviceHouseNewPorperty->editChargeNumber($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($group_id){
            return api_output(0,['group_id' => $group_id],'编辑成功');
        }else{
            return api_output(1003,[],'编辑失败！');
        }
    }



    /**
     * 查询线下支付列表
     * @author:zhubaodi
     * @date_time: 2021/6/11 14:01
     */
    public function offlinePayList() {
        // 获取登录信息
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $page = $this->request->param('page',0,'intval');
        $limit = 20;
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $number_list = $serviceHouseNewPorperty->getOfflinePayList($property_id,$page,$limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$number_list);
    }


    /**
     * 查询线下支付详情
     * @author: zhubaodi
     * @param integer $id
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function offlinePayInfo() {
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->param('id','','intval');
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $number_info = $serviceHouseNewPorperty->getOfflinePayInfo($id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$number_info);

    }

    /**
     * 添加线下支付
     * @param 传参
     * array (
     *  'name'=> '收费科目名称',
     *  'charge_type'=> '收费类别',
     *  'status'=> '收费科目状态 1:正常 2:关闭',
     * )
     * @author: zhubaodi
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function addOfflinePay() {
        // 获取登录信息
       // $data['property_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $data['property_id'] =  $this->adminUser['property_id'];
        if (! $data['property_id']) {
            return api_output(1002, [], '请先登录到物业后台！');
        }

        $data['name'] = $this->request->param('name','','trim');
        if (empty( $data['name'])) {
            return api_output(1001,[],'请上传线下支付方式名称！');
        }
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $pay_id = $serviceHouseNewPorperty->addOfflinePay($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($pay_id){
            return api_output(0,['pay_id' => $pay_id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }
    }


    /**
     * 编辑线下支付
     * @param 传参
     * array (
     * 'id'=> '收费科目id',
     *  'name'=> '线下支付方式名称',
     * )
     * @author: zhubaodi
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function editOfflinePay() {
      //  $data['property_id'] = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $data['property_id'] =  $this->adminUser['property_id'];
        if (! $data['property_id']) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $data['id'] = $this->request->param('id','','intval');
        if (empty( $data['id'])) {
            return api_output(1001,[],'请上传收费科目id！');
        }
        $data['name'] = $this->request->param('name','','trim');
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $pay_id = $serviceHouseNewPorperty->editOfflinePay($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($pay_id){
            return api_output(0,['group_id' => $pay_id],'编辑成功');
        }else{
            return api_output(1003,[],'编辑失败！');
        }
    }


    /**
     * 删除线下支付方式
     * @author: zhubaodi
     * @param integer $id
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function delOfflinePay() {
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->param('id','','intval');
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $number_info = $serviceHouseNewPorperty->delOfflinePay($id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$number_info);

    }
    /**
     * 删除线下支付方式
     * @author: zhubaodi
     * @param integer $id
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function getChargeType() {
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $serviceHouseNewPorperty = new HouseNewChargeService();
        $res=$serviceHouseNewPorperty->charge_type_arr;
        $configCustomizationService = new ConfigCustomizationService();
        $config=$configCustomizationService->getCarPileJudge();
        if ($config==false) {
            foreach ($res as $k=>$v){
                if ($v['key']=='pile'){
                    unset($res[$k]); break;
                }
            }
        }
        return api_output(0,$res);

    }

    /**
     * 小区费用统计
     * @author:zhubaodi
     * @date_time: 2021/7/6 9:58
     */
    public function countVillageFee(){
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        $villageids =  isset($this->adminUser['menus']) ? $this->adminUser['menus']:'';
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $productDate = $this->request->param('productDate');
        $payDate = $this->request->param('payDate');
        $service_start_time = $this->request->param('service_start_time', '', 'trim');
        $service_end_time = $this->request->param('service_end_time', '', 'trim');
        $project_id = $this->request->param('project_id');
        $optxtype = $this->request->param('optxtype','','trim');
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $params = [
                'productDate' => $productDate, 'payDate' => $payDate, 
                'service_start_time' => $service_start_time, 'service_end_time' => $service_end_time,
                'project_id' => $project_id,'optxtype'=>$optxtype
            ];
            $count_list = $serviceHouseNewPorperty->countVillageFee($property_id,$villageids, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$count_list);

    }

    public function village_login(){
        $village_id = $this->request->param('village_id','','intval');
        $admin_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        if(isset($this->adminUser['id'])){
            $admin_id=$this->adminUser['id'];
        }
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $res = $serviceHouseNewPorperty->village_login($village_id,$property_id,$admin_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 定制水费类型
     * @author: liukezhu
     * @date : 2022/7/25
     * @return \json
     */
    public function chargeWaterType(){
        try {
            $res = (new HouseNewPorpertyService())->checkChargeWaterType();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    
    /**
     * 查询缴费编号
     * @author: zhubaodi
     * @param integer $id
     * @date_time: 2021/6/11 14:01
     * @return \json
     */
    public function getChargeTypeCode() {
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $ePayCode = (new HouseAbchinaSet())->where('property_id',$property_id)->value('epay_code');
        $ePayCodeAry = $ePayCode ? json_decode($ePayCode,true) : [];
        $ePayCodeAry = array_column($ePayCodeAry,'code','key');
        
        $serviceHouseNewPorperty = new HouseNewChargeService();
        $res=$serviceHouseNewPorperty->charge_type_arr;
        $configCustomizationService = new ConfigCustomizationService();
        $config=$configCustomizationService->getCarPileJudge();
        if ($config==false) {
            foreach ($res as $k=>$v){
                if ($v['key']=='pile'){
                    unset($res[$k]); break;
                }
            }
        }
        foreach ($res as $k=>$v){
            $res[$k]['code'] = $ePayCodeAry[$v['key']]??'';
        }
        return api_output(0,$res);

    }
    
    /**
     * 保存缴费编号
     */
    public function editChargeTypeCode()
    {
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $chargeList = $this->request->param('charge_list');
        $ePayCode = json_encode($chargeList);
        $id = (new HouseAbchinaSet())->where('property_id',$property_id)->value('pigcms_id');
        if($id){
            (new HouseAbchinaSet())->where('pigcms_id',$id)->update(['epay_code'=>$ePayCode]);
        }else{
            (new HouseAbchinaSet())->insert([
                'property_id'=>$property_id, 
                'epay_code'=>$ePayCode
            ]);
        }
        return api_output(0,'success');
    }
    
    /**
     * 查询是否展示农行参数设置
     */
    public function showAbChina()
    {
        $res = [
            'abchina'=>customization('abchina') ? 1 : 0
        ];
        return api_output(0,$res);
    }
    
    /**
     * 查询农行参数
     */
    public function setAbChina()
    {
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $ePayCode = (new HouseAbchinaSet())->where('property_id',$property_id)->field('merchant_id,merchant_pfx,trust_pay_cer,abchina_prikey,pay_url,epay_code')->find();
        if(!$ePayCode){
            $ePayCode = [
                'pay_url'=>'',
                'merchant_id'=>'',
                'merchant_pfx'=>'',
                'trust_pay_cer'=>'',
                'abchina_prikey'=>'',
                'epay_code'=>'',
            ];
        }
        return api_output(0,$ePayCode);
    }
    /**
     * 保存农行参数
     */
    public function editAbChina()
    {
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $payUrl = $this->request->param('pay_url');
        $merchantId = $this->request->param('merchant_id');
        $epayCode = $this->request->param('epay_code','');
        $merchantPfx = $this->request->param('merchant_pfx');
        $trustPayCer = $this->request->param('trust_pay_cer');
        $abchinaPrikey = $this->request->param('abchina_prikey');
        if(!$merchantId){
            return api_output(1001, [], '商户编号不能为空');
        }
        if(!$epayCode){
            return api_output(1001, [], '缴费项目编号不能为空');
        }
        if(!$merchantPfx){
            return api_output(1001, [], '私钥密码不能为空');
        }
        if(!$trustPayCer){
            return api_output(1001, [], '商户证书不能为空');
        }
        if(!$abchinaPrikey){
            return api_output(1001, [], '农行公钥证书不能为空');
        }
        $pigcmsId = (new HouseAbchinaSet())->where('property_id',$property_id)->value('pigcms_id');
        if($pigcmsId){
            $updateData=[
                'pay_url'=>$payUrl,
                'merchant_id'=>$merchantId,
                'merchant_pfx'=>$merchantPfx,
                'trust_pay_cer'=>$trustPayCer,
                'abchina_prikey'=>$abchinaPrikey,
                'epay_code'=>$epayCode,
            ];
            (new HouseAbchinaSet())->where('property_id',$property_id)->update($updateData);
            
            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
            $queuData = [
                'logData' => [
                    'tbname' => '物业设置-基本信息',
                    'table'  => 'house_abchina_set',
                    'client' => '物业后台',
                    'trigger_path' => '基本信息->农行参数设置',
                    'trigger_type' => $this->getUpdateNmae(),
                    'addtime'      => time(),
                    'village_id'   => 0,
                    'property_id' =>$property_id,
                    'op_id'   => $parseInfo['op_id'],
                    'op_type'   => $parseInfo['op_type'],
                    'op_name'   => $parseInfo['op_name'],
                ],
                'newData' => $updateData,
                'oldData' =>''
            ];
            $this->laterLogInQueue($queuData);
            
        }else{
            (new HouseAbchinaSet())->insert([
                'property_id'=>$property_id,
                'pay_url'=>$payUrl,
                'merchant_id'=>$merchantId,
                'merchant_pfx'=>$merchantPfx,
                'trust_pay_cer'=>$trustPayCer,
                'abchina_prikey'=>$abchinaPrikey,
                'epay_code'=>$epayCode,
            ]);
            
        }
        return api_output(0,'success');
    }

    /**
     * 查询是否展示建行支付配置项
     */
    public function getConstructionStatus()
    {
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $res =  [
            'jz_wsxdsj_net_customized_open' => $jz_wsxdsj_net_customized_open==1 ? 1 : 0
        ];
        return api_output(0,$res);
    }
    
    /**
     * 变更记录
     */
    public function getChargeNumberLogInfo()
    {
        $property_id = isset($this->request->log_uid) ? $this->request->log_uid : 0;
        $property_id =  $this->adminUser['property_id'];
        if (!$property_id) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $param['id'] = $this->request->param('id','','intval');
        $param['page'] = $this->request->param('page',0,'intval');
        $param['limit'] = 10;
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        try {
            $number_info = $serviceHouseNewPorperty->getChargeNumberLogInfo($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$number_info);
    }
}