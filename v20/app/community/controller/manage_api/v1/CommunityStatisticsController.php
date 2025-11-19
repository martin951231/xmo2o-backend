<?php
/**
 * 社区管理app 统计相关
 * @author weili
 * @date 2020/10//12
 */
namespace app\community\controller\manage_api\v1;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\CommunityStatisticsService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\PackageOrderService;

class CommunityStatisticsController extends BaseController
{
    //统计首页
    public function index()
    {
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
//        $property_id = $this->login_info['property_id'];
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        if (3==$this->login_role || 4==$this->login_role) {
            if (isset($this->login_info) && isset($this->login_info['property_id'])) {
                $property_id =  $this->login_info['property_id'];
            } else {
                $property_id = $this->request->param('property_id','','intval');
            }
            if(3==$this->login_role){
                $property_id =  $this->login_info['id'];
            }
            if(!$property_id){
                return api_output_error(1002,'登录失效，请重新登录！');
            }
        } else {
            return api_output_error(1002,'请重新登录！');
        }
        if (isset($this->login_info) && isset($this->login_info['login_name'])) {
            $login_name = ','.$this->login_info['login_name'];
        }
        $app_type = $this->request->post('app_type','');
        $serviceCommunityStatistics = new CommunityStatisticsService();
        $service_house_new_property = new HouseNewPorpertyService();
        $is_new_version = $service_house_new_property->getTakeEffectTimeJudge($property_id); //true 是新版
        try {
            //$oldlist = $serviceCommunityStatistics->getData($property_id,$login_name,$app_type);
            $menus_village=array();
            if($this->login_role==4 && isset($this->login_info['menus_village']) && !empty($this->login_info['menus_village'])){
                //普通管理员 后台分配的 小区
                $menus_village=$this->login_info['menus_village'];
            }
            $extra_data=array('login_uid'=>$this->_uid,'login_role'=>$this->login_role,'menus_village'=>$menus_village);
            $newlist=$serviceCommunityStatistics->getNewData($property_id,$login_name,$extra_data,$app_type);
            $list=$newlist;
            //$list=array_merge($oldlist,$newlist);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        // 获取客服联系路径
        $kf_phone = cfg('site_phone');
        $list['kf_phone'] = trim($kf_phone) ? trim($kf_phone): '';
        $list['is_new_version'] = $is_new_version;
        $list['login_role'] = $this->login_role;
        
        return api_output(0, $list, "成功");
    }
    /**
     * Notes:费用统计(收入)
     * @return \json
     * @author: weili
     * @datetime: 2020/10/13 15:03
     */
    public function costIncomeStatistics()
    {
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
//        $property_id = $this->login_info['village_id'];
//        $property_id = $this->request->param('property_id','','intval');
        $info = $this->getLoginInfo();
        if (isset($this->login_info) && isset($this->login_info['property_id'])) {
            $property_id =  $this->login_info['property_id'];
        } else {
            $property_id = $this->request->param('property_id','','intval');
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $village_id = $this->request->param('village_id','0','intval');
        $date = $this->request->param('date','','trim');
        $type = $this->request->param('type','','intval');//1表示按月 2表示按年
        $app_type = $this->request->param('app_type','','trim');
        if(preg_match('/[\x7f-\xff]/', $date)){
            $date='';
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        //过滤套餐 2020/11/9 start
        $package_content = $this->getOrderPackage($property_id);
        if(!in_array(5,$package_content)) {
            return api_output_error(1001,'套餐不包含此功能');
        }
        //过滤套餐 2020/11/9 end
        $serviceCommunityStatistics = new CommunityStatisticsService();
        try {
            $menus_village=array();
            if($this->login_role==4 && isset($this->login_info['menus_village']) && !empty($this->login_info['menus_village'])){
                //普通管理员 后台分配的 小区
                $menus_village=$this->login_info['menus_village'];
            }
            $list = $serviceCommunityStatistics->costStatistics($village_id, $date, $type,$property_id,$app_type,$this->login_role,$menus_village);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }
    //过滤套餐 2020/11/9 start
    public function getOrderPackage($property_id)
    {
        $servicePackageOrder = new PackageOrderService();
        $dataPackage = $servicePackageOrder->getPropertyOrderPackage($property_id,'');
        if($dataPackage) {
            $dataPackage = $dataPackage->toArray();
            $package_content = $dataPackage['content'];
        }else{
            $package_content = [];
        }
        return $package_content;
    }
    //过滤套餐 2020/11/9 end
    /**
     * Notes:费用统计(支出)
     * @return \json
     * @author: weili
     * @datetime: 2020/10/13 15:03
     */
    public function costSpendStatistics()
    {
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
//        $property_id = $this->login_info['property_id'];

//        $property_id = $this->request->param('property_id','','intval');
        $info = $this->getLoginInfo();
        if (isset($this->login_info) && isset($this->login_info['property_id'])) {
            $property_id =  $this->login_info['property_id'];
        } else {
            $property_id = $this->request->param('property_id','','intval');
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $village_id = $this->request->param('village_id','0','intval');
        $date = $this->request->param('date','','trim');
        $type = $this->request->param('type','','intval');//1表示按月 2表示按年
        $app_type = $this->request->param('app_type','','trim');
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }

        //过滤套餐 2020/11/9 start
        $package_content = $this->getOrderPackage($property_id);
        if(!in_array(5,$package_content)) {
            return api_output_error(1001,'套餐不包含次功能');
        }
        //过滤套餐 2020/11/9 end

        if(preg_match('/[\x7f-\xff]/', $date)){
            $date='';
        }

        $serviceCommunityStatistics = new CommunityStatisticsService();
        try {
            $list = $serviceCommunityStatistics->spendStatistics($village_id, $date, $type,$property_id,$app_type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }
    /**
     * Notes:获取小区列表
     * @return \json
     * @author: weili
     * @datetime: 2020/10/13 15:03
     */
    public function getHouseVillage()
    {
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
//        $property_id = $this->login_info['village_id'];

//        $property_id = $this->request->param('property_id','','intval');
        $info = $this->getLoginInfo();
        if (isset($this->login_info) && isset($this->login_info['property_id'])) {
            $property_id =  $this->login_info['property_id'];
        } else {
            $property_id = $this->request->param('property_id','','intval');
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceCommunityStatistics = new CommunityStatisticsService();
        try {
            $menus_village=array();
            if($this->login_role==4 && isset($this->login_info['menus_village']) && !empty($this->login_info['menus_village'])){
                //普通管理员 后台分配的 小区
                $menus_village=$this->login_info['menus_village'];
            }
            $list = $serviceCommunityStatistics->getHouseVillageList($property_id,$menus_village,$this->login_role);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }
    /**
     * Notes:硬件统计
     * @return \json
     * @author: weili
     * @datetime: 2020/10/14 15:22
     */
    public function getHardwareStatistics()
    {
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
//        $property_id = $this->login_info['property_id'];

//        $property_id = $this->request->param('property_id','','intval');
        $info = $this->getLoginInfo();
        if (isset($this->login_info) && isset($this->login_info['property_id'])) {
            $property_id =  $this->login_info['property_id'];
        } else {
            $property_id = $this->request->param('property_id','','intval');
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $village_id = $this->request->param('village_id','','intval');
        $app_type = $this->request->param('app_type','','trim');
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        //过滤套餐 2020/11/9 start
        $package_content = $this->getOrderPackage($property_id);
        $hardware_intersect = array_intersect([6,7,8,9,10,11],$package_content);
        if(!$hardware_intersect || count($hardware_intersect)<=0) {
            return api_output_error(1001,'套餐不包含次功能');
        }
        //过滤套餐 2020/11/9 end
        $serviceCommunityStatistics = new CommunityStatisticsService();
        try {
            $menus_village=array();
            if($this->login_role==4 && isset($this->login_info['menus_village']) && !empty($this->login_info['menus_village'])){
                //普通管理员 后台分配的 小区
                $menus_village=$this->login_info['menus_village'];
            }
            $list = $serviceCommunityStatistics->getHardware($property_id,$village_id,$app_type,$this->login_role,$menus_village);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }
    /**
     * Notes:车场统计
     * @return \json
     * @author: weili
     * @datetime: 2020/10/16 11:39
     */
    public function getParkingStatistics()
    {
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
//        $property_id = $this->login_info['property_id'];

//        $property_id = $this->request->param('property_id','','intval');
        $info = $this->getLoginInfo();
        if (isset($this->login_info) && isset($this->login_info['property_id'])) {
            $property_id =  $this->login_info['property_id'];
        } else {
            $property_id = $this->request->param('property_id','','intval');
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $village_id = $this->request->param('village_id','','intval');
        $app_type = $this->request->param('app_type','','trim');
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceCommunityStatistics = new CommunityStatisticsService();
        try {
            $list = $serviceCommunityStatistics->getParking($property_id,$village_id,$app_type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }
    /**
     * Notes:人流量统计
     * @return \json
     * @author: weili
     * @datetime: 2020/10/16 11:39
     */
    public function getTrafficStatistics()
    {
//        $arr = $this->getLoginInfo();
//        if (isset($arr['status']) && $arr['status']!=1000) {
//            return api_output($arr['status'],[],$arr['msg']);
//        }
//        $property_id = $this->login_info['village_id'];

//        $property_id = $this->request->param('property_id','','intval');
        $info = $this->getLoginInfo();
        if (isset($this->login_info) && isset($this->login_info['property_id'])) {
            $property_id =  $this->login_info['property_id'];
        } else {
            $property_id = $this->request->param('property_id','','intval');
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $village_id = $this->request->param('village_id','','intval');
        $app_type = $this->request->param('app_type','','trim');
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceCommunityStatistics = new CommunityStatisticsService();
        try {
            $menus_village=array();
            if($this->login_role==4 && isset($this->login_info['menus_village']) && !empty($this->login_info['menus_village'])){
                //普通管理员 后台分配的 小区
                $menus_village=$this->login_info['menus_village'];
            }
            $list = $serviceCommunityStatistics->getTraffic($property_id,$village_id,$app_type,$this->login_role,$menus_village);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * Notes: 工单统计
     * @return \json
     * @author: weili
     * @datetime: 2020/10/19 9:14
     */
    public function getRepairOrderStatistics()
    {
//        $property_id = $this->request->param('property_id','','intval');
        $info = $this->getLoginInfo();
        if (isset($this->login_info) && isset($this->login_info['property_id'])) {
            $property_id =  $this->login_info['property_id'];
        } else {
            $property_id = $this->request->param('property_id','','intval');
        }
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $village_id = $this->request->param('village_id','','intval');
        $app_type = $this->request->param('app_type','','trim');
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        //过滤套餐 2020/11/9 start
        $package_content = $this->getOrderPackage($property_id);
        $work_order_intersect = array_intersect([16,18,19],$package_content);
        if(!$work_order_intersect || count($work_order_intersect)<=0) {
            return api_output_error(1001,'套餐不包含次功能');
        }
        //过滤套餐 2020/11/9 end
        $serviceCommunityStatistics = new CommunityStatisticsService();
        try {
            $list = $serviceCommunityStatistics->getRepairOrder($property_id,$village_id,$app_type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }
}