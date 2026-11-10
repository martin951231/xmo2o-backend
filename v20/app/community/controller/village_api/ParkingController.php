<?php
/**
 * @author : liukezhu
 * @date : 2021/6/10
 */
namespace app\community\controller\village_api;

use app\common\model\service\CacheSqlService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterService;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\Park\A11Service;
use app\community\model\service\Park\QinLinCloudService;
use file_handle\FileHandle;
use app\community\model\service\HouseVillageService;
use app\community\model\service\ParkPassageService;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\HouseVillagePositionSetService;
use app\community\model\service\HouseVillageRobleasePositionOrderService;
use app\community\model\service\HouseVillageCarWorkerRecordService;
use customization\customization;
class ParkingController extends CommunityBaseController{
    use customization;
    /**
     *
     * @author: 
     * @date : 
     */
    public function getVisitorTmpParkingList(){
        $begin_time = $this->request->param('begin_time');
        $begin_time= !empty($begin_time) ? strtotime($begin_time):0;
        $end_time = $this->request->param('end_time');
        $end_time= !empty($end_time) ? strtotime($end_time.' 23:59:59'):0;
        $page = $this->request->param('page','1','int');
        $village_id = $this->adminUser['village_id'];
        $_string='car_id!="" AND car_id IS NOT NULL ';
        if($begin_time>0 && ($end_time<=0)){
            $_string .=' AND add_time>='.$begin_time;
        }elseif ($end_time>0 && ($begin_time<=0)){
            $_string .=' AND add_time<='.$end_time;
        }elseif ($end_time>0 && $begin_time>0){
            $_string .=' AND add_time>='.$begin_time.' AND add_time<='.$end_time;
        }
        //$whereArr=array('owner_uid'=>0,'is_car'=>1,'owner_bind_id'=>0,'village_id'=>$village_id,'_string'=>$_string);
        $whereStr='owner_uid ="0" AND is_car="1" AND owner_bind_id="0" AND village_id='.$village_id.' AND '.$_string;
        $db_house_visitor_service = new HouseVillageParkingService();
        try{
            $field='id,car_id,visitor_name,visitor_phone,add_time,pass_time,is_car';
            $list = $db_house_visitor_service->getVisitorTmpParkingLists($whereStr,'*',$page,20);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }



    /**
     * 添加车库信息
     * @author:zhubaodi
     * @date_time: 2022/3/8 13:31
     */
    public function addParkingGarage(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['garage_num'] = $this->request->post('garage_num');//车库名称
        $data['garage_position'] = $this->request->post('garage_position');//车库地址
        $data['position_count'] = $this->request->post('position_count',0,'intval');//车位总数
        $data['position_month_count'] = $this->request->post('position_month_count',0,'intval');//月租车位数
        $data['add_position_type']=$this->request->post('add_position_type',0,'intval');//是否自动生成车位 1是 2否
        $data['garage_remark'] = $this->request->post('garage_remark','','trim');//备注
        $data['fid'] = $this->request->post('parent_garage','','intval');//父级车库
        $data['is_month_charge'] = $this->request->post('is_month_charge',0,'intval');
        $data['is_month_access'] = $this->request->post('is_month_access',0,'intval');
        if (!empty($data['add_position_type'])&&$data['add_position_type']==1){
            $data['rule_first'] = $this->request->post('rule_first','','trim');//生成规则的头部设置
            $data['rule_last'] = $this->request->post('rule_last','','trim');//生成规则的尾部设置
            if (empty($data['rule_first'])||empty($data['rule_last'])){
                return api_output_error(1001, '请填写完整的生成规则信息');
            }
        }
        if (empty($data['garage_num'])){
            return api_output_error(1001, '车库名称不能为空');
        }
        if (empty($data['garage_position'])){
            return api_output_error(1001, '车库地址不能为空');
        }
        if (empty($data['position_count'])){
            return api_output_error(1001, '车位总数不能为空');
        }
        if (empty($data['position_month_count'])){
            return api_output_error(1001, '月租车位数不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addParkingGarage($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 添加车库信息
     * @author:zhubaodi
     * @date_time: 2022/3/8 13:31
     */
    public function editParkingGarage(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['garage_id'] = $this->request->post('garage_id',0,'intval');//车库id
        $data['garage_num'] = $this->request->post('garage_num','','trim');//车库名称
        $data['garage_position'] = $this->request->post('garage_position','','trim');//车库地址
        $data['position_count'] = $this->request->post('position_count',0,'intval');//车位总数
        $data['position_month_count'] = $this->request->post('position_month_count',0,'intval');//月租车位数
        $data['garage_remark'] = $this->request->post('garage_remark','','trim');//备注
        $data['fid'] = $this->request->post('parent_garage',0,'intval');//父级车库
        $data['is_month_charge'] = $this->request->post('is_month_charge',0,'intval');
        $data['is_month_access'] = $this->request->post('is_month_access',0,'intval');
        $data['lat'] = $this->request->post('lat',0,'trim');
        $data['lng'] = $this->request->post('lng',0,'trim');
        if (empty($data['garage_id'])){
            return api_output_error(1001, '车库编号不能为空');
        }
        if (empty($data['garage_num'])){
            return api_output_error(1001, '车库名称不能为空');
        }
        if (empty($data['garage_position'])){
            return api_output_error(1001, '车库地址不能为空');
        }
        if (empty($data['position_count'])){
            return api_output_error(1001, '车位总数不能为空');
        }
        if (empty($data['position_month_count'])){
            return api_output_error(1001, '月租车位数不能为空');
        }
        if($data['fid']>0 && $data['fid']==$data['garage_id']){
            return api_output_error(1001, '父级车库不能选择自己！');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editParkingGarage($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除车库信息
     * @author:zhubaodi
     * @date_time: 2022/3/8 13:31
     */
    public function delParkingGarage(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['garage_id'] = $this->request->post('garage_id',0,'intval');//车库id

        if (empty($data['garage_id'])){
            return api_output_error(1001, '车库编号不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delParkingGarage($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 查询车库列表
     * @author:zhubaodi
     * @date_time: 2022/3/9 11:13
     */
    public function getParkGarageList(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['garage_num'] = $this->request->param('garage_name','','trim');//车库名称
        $data['garage_status'] = $this->request->param('garage_status',0,'intval');//车库绑定状态
        $data['page'] = $this->request->param('page',1,'intval');//车库绑定状态
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkGarageList($data);
            $houseVillageService=new HouseVillageService();
            $res['role_addgarage']=$houseVillageService->checkPermissionMenu(112118,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_garageset']=$houseVillageService->checkPermissionMenu(112119,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_garageparam']=$houseVillageService->checkPermissionMenu(112120,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_garagemanage']=$houseVillageService->checkPermissionMenu(112122,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_editgarage']=$houseVillageService->checkPermissionMenu(112121,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delgarage']=$houseVillageService->checkPermissionMenu(112123,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        }catch (\Exception $e){
            if (1501==$e->getCode()) {
                $data = [
                    'title' => '提示',
                    'tip' => $e->getMessage() . ',点击前往',
                    'url' => cfg('site_url').'/v20/public/platform/#/village/village.charge.standard/projectList',
                ];
                return api_output(1501, $data);
            }
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 查询车库详情
     * @author:zhubaodi
     * @date_time: 2022/3/9 11:13
     */
    public function getParkGarageInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }

        $data['garage_id'] = $this->request->param('garage_id',0,'intval');//车库绑定状态
        if (empty($data['garage_id'])){
            return api_output_error(1001, '车库id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkGarageInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询车库详情
     * @author:zhubaodi
     * @date_time: 2022/3/9 11:13
     */
    public function getParkConfigInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $garage_id=$this->request->post('garage_id',0,'trim');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkConfigInfo($data,$garage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加/编辑车场
     * @author:zhubaodi
     * @date_time: 2022/3/8 20:02
     */
    public function addParkConfig(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        $data['park_versions'] = $this->request->post('park_versions',0,'intval');//是否开启智慧停车功能
        $data['park_show'] = $this->request->post('park_show',0,'intval');//是否展示在用户端车场列表页
        $data['is_park_month_type']=$this->request->post('is_park_month_type',0,'intval');//该小区是否支持月租车
        $data['is_temporary_park_type'] = $this->request->post('is_temporary_park_type');//是否开启储值车功能
        $data['visitor_money'] = $this->request->post('visitor_money','','trim');//是否开启储值车功能
        $data['park_sys_type']=$this->request->post('park_sys_type','','trim');//停车设备类型
        $data['free_park_time'] = $this->request->post('free_park_time',0,'intval');//缴费后免费停留时间(单位：分钟)
        $data['park_position_type']=$this->request->post('park_position_type',0,'intval');//一位多车设置 1：是，0：否
        $data['in_park_type'] = $this->request->post('in_park_type',0,'intval');//是否允许车辆重复进场1：是，0：否
        $data['temp_in_park_type']=$this->request->post('temp_in_park_type',0,'intval');//车位已满是否允许临时车入场 1：是，0：否
        $data['free_open_gate'] = $this->request->post('free_open_gate',0,'intval');//不在场车辆是否免费放行 1：是，0：否
        $data['not_inPark_money']=$this->request->post('not_inPark_money',0,'trim');//不在场车辆放行需交费用
        $data['park_month_day']=$this->request->post('park_month_day',0,'intval');//月租车到期天数提醒
        $data['children_position_type']=$this->request->post('children_position_type',0,'intval');//是否开启字母车位 1是 0否
        $car_type_month_to_temp=$this->request->post('car_type_month_to_temp','');//月租车到期使用临时车卡类标准
        $garage_id = $this->request->post('garage_id',0,'intval');  //车库id
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
       if (empty($data['park_sys_type'])){
           return api_output_error(1001, '停车设备类型不能为空');
       }
       if ($data['visitor_money']<0){
           return api_output_error(1001, '请正确填写储值车最小储值金额');
       }
       if($data['is_park_month_type'] == 1){
           if($data['park_month_day'] < 0 || $data['park_month_day'] > 30){
               return api_output_error(1001, '月租车到期天数请选择0~30天范围内');
           }
       }else{
           $data['park_month_day']=0;

       }
        if ($data['park_sys_type'] == "D3" || $data['park_sys_type'] == "A11") {
            $data['register_type'] = $this->request->post('register_type','','trim');//备注
            $data['register_day'] = $this->request->post('register_day','','trim');//备注
            $data['out_park_time'] = $this->request->post('out_park_time','','trim');//允许重复离场时长（单位：分钟）
            $data['temp_in_park_type'] = $this->request->post('temp_in_park_type',0,'intval');//禁止临时车入场 0：是 1：否
            $data['expire_month_car_type'] = $this->request->post('expire_month_car_type',0,'intval');//月租车过期处理方式
            $data['expire_month_car_day'] = $this->request->post('expire_month_car_day',0,'intval');//过期多少天后禁止入场
            if (!empty($data['register_day'])){
                if ($data['register_day']<0){
                    return api_output_error(1001, '临时车免登记时长请输入正整数');
                }
            }
            if (!empty($data['out_park_time'])){
                if ($data['out_park_time']<0){
                    return api_output_error(1001, '允许重复离场请输入正整数');
                }
                if(floor($data['out_park_time'])!=$data['out_park_time']){
                    return api_output_error(1001, '允许重复离场请输入正整数');
                }
            }
        }elseif ($data['park_sys_type'] == 'D5'){
            $data['d5_url'] = $this->request->post('d5_url');
            $data['d5_name'] = $this->request->post('d5_name');
            $data['d5_pass'] = $this->request->post('d5_pass');
            if(empty($data['d5_url'])){
                return api_output_error(1001, '请输入设备请求url');
            }
            if(empty($data['d5_name'])){
                return api_output_error(1001, '请输入设备账号名');
            }
            if(empty($data['d5_pass'])){
                return api_output_error(1001, '请输入设备账号密码');
            }
        }
        elseif ($data['park_sys_type'] == 'D7'){
            $data['d7_park_id'] = $this->request->post('d7_park_id');
            if(empty($data['d7_park_id'])){
                return api_output_error(1001, '请输入停车场编号');
            }
        }
        else {
            $data['comid'] = $this->request->post('comid');
        }
        $data['union_id'] = $this->request->post('union_id');
        $data['ckey'] = $this->request->post('ckey');
        $new_car_type_month_to_temp=array();
        if($car_type_month_to_temp && is_array($car_type_month_to_temp)){
            foreach ($car_type_month_to_temp as $vv){
                if($vv['month_car_type']>0 && $vv['temp_car_type']>0){
                    $new_car_type_month_to_temp[]=$vv;
                }
            }
        }
        if(!empty($new_car_type_month_to_temp)){
            $data['car_type_month_to_temp']=json_encode($new_car_type_month_to_temp,JSON_UNESCAPED_UNICODE);
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addParkConfig($data,$garage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加车位
     * @author:zhubaodi
     * @date_time: 2022/3/9 11:51
     */
    public function addParkPosition(){

        $data['village_id'] = $this->adminUser['village_id'];
        $data['garage_id'] = $this->request->post('garage_id',0,'intval');//车库id
        $data['position_num'] = $this->request->post('position_num','','trim');//车位号
        $data['position_area'] = $this->request->post('position_area','','trim');//车位面积
        $data['position_note'] = $this->request->post('position_note','','trim');//备注
        $data['pigcms_id'] = $this->request->post('pigcms_id','','trim');//是否开启智慧停车功能
        $data['position_status'] = $this->request->post('position_status',0,'intval');//租售状态 1未售出 2已售出已交付 3已售出未交付 4销控已售 5销控未售
        $data['children_type'] = $this->request->post('children_type',0,'intval');//字母车位  1母车位 2子车位
        $data['opt_type'] = $this->request->post('opt_type',0,'intval'); //0无 1可租赁2可抢购
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        if (empty($data['garage_id'])){
            return api_output_error(1001, '请选择车库');
        }
        if ($data['position_num']==='' || $data['position_num']===null){
            return api_output_error(1001, '车位号不能为空');
        }
        if (!in_array($data['position_status'], [1, 2, 3, 4, 5])){
            return api_output_error(1001, '请选择租售状态');
        }
        if (strlen($data['position_num'])>30){
            return api_output_error(1001, '车位号不能超过30位');
        }
        /*
        if (preg_match('/[\x7f-\xff]/', $data['position_num'])) {
            return api_output_error(1001, '车位号不能使用中文');
        }
        */
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addParkPosition($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0,$res);
    }

    /**
     * 编辑车位
     * @author:zhubaodi
     * @date_time: 2022/3/9 14:48
     */
    public function editParkPosition(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        $data['position_id'] = $this->request->post('position_id',0,'intval');//车位id
        $data['garage_id'] = $this->request->post('garage_id',0,'intval');//车库id
        $data['position_num'] = $this->request->post('position_num','','trim');//车位号
        $data['position_area'] = $this->request->post('position_area','','trim');//车位面积
        $data['position_note'] = $this->request->post('position_note','','trim');//备注
        $data['pigcms_id'] = $this->request->post('pigcms_id','','trim');//是否开启智慧停车功能
        $data['position_status'] = $this->request->post('position_status',0,'intval');//租售状态 1未售出 2已售出已交付 3已售出未交付 4销控已售 5销控未售
        $data['end_time'] = $this->request->post('end_time','','trim');//是否开启智慧停车功能
        $data['children_type'] = $this->request->post('children_type',0,'intval');//字母车位  1母车位 2子车位
        $data['opt_type'] = $this->request->post('opt_type',0,'intval'); //0无 1可租赁2可抢购
        $position_pattern = $this->request->post('position_pattern',1,'intval');//1真实车位 2虚拟车位
        $data['bind_cars'] = $this->request->post('bind_cars');//绑定的车辆id
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        if (empty($data['garage_id'])){
            return api_output_error(1001, '请选择车库');
        }
        if (empty($data['position_id'])){
            return api_output_error(1001, '车位id不能为空');
        }
        if ($data['position_num']==='' || $data['position_num']===null){
            return api_output_error(1001, '车位号不能为空');
        }
        if (!in_array($data['position_status'], [1, 2, 3, 4, 5])){
            return api_output_error(1001, '请选择租售状态');
        }
        if (strlen($data['position_num'])>30){
            return api_output_error(1001, '车位号不能超过30位');
        }
        /*
        if ($position_pattern!=2 && preg_match('/[\x7f-\xff]/', $data['position_num'])) {
            return api_output_error(1001, '车位号不能使用中文');
        }
        */
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editParkPosition($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0,$res);
    }

    /**
     * 删除车位
     * @author:zhubaodi
     * @date_time: 2022/3/9 14:48
     */
    public function delParkPosition(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['position_id'] = $this->request->post('position_id',0,'intval');//车位id

        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        if (empty($data['position_id'])){
            return api_output_error(1001, '车位id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delParkPosition($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        (new CacheSqlService())->clearCache();
        return api_output(0,$res);
    }

    public function delPosition(){
        return $this->delParkPosition();
    }
    /**
     * 批量删除车位
     * @author:zhubaodi
     * @date_time: 2022/3/9 14:48
     */
    public function delAllParkPosition(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['position_id'] = $this->request->post('position_id','','trim');//车位id

        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        if (empty($data['position_id'])){
            return api_output_error(1001, '车位id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delParkPosition($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0,$res);
    }

    /**
     * 查询车位信息
     * @author:zhubaodi
     * @date_time: 2022/3/9 14:47
     */
    public function getPositionInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['position_id'] = $this->request->param('position_id',0,'intval');//车位id
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        if (empty($data['position_id'])){
            return api_output_error(1001, '车位id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getPositionInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0,$res);
    }

    /**
     * 下拉框查询车库列表信息
     * @author:zhubaodi
     * @date_time: 2022/3/9 15:16
     */
    public function getGarageList(){
        $data['village_id'] = $this->adminUser['village_id'];
        $type = $this->request->post('type',0,'intval');//1加上请选择
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getGarageList($data['village_id'],$type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0,$res);
    }

    public function getPositionList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['garage_id'] = $this->request->post('garage_id','','trim');//车库名称
        $data['position_num'] = $this->request->post('position_num','','trim');//车库名称
        $data['position_status'] = $this->request->post('position_status',0,'intval');//车库绑定状态
        $data['position_pattern'] = $this->request->param('position_pattern',0);//1真实车位 2虚拟车位
        $data['children_type'] = $this->request->param('children_type',0);//车位类型 1母车位 2子车位
        $data['page'] = $this->request->post('page',1,'intval');//车库绑定状态
        $data['position_car_status'] = $this->request->post('position_car_status',0,'intval');//租售状态
        $data['limit']=$this->request->post('pageSize',10,'intval');//车库绑定状态
        if (empty($data['position_pattern'])){
            $data['position_pattern']=1;
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getPositionList($data);
            $houseVillageService=new HouseVillageService();
            $res['role_addposition']=$houseVillageService->checkPermissionMenu(112124,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_importposition']=$houseVillageService->checkPermissionMenu(112125,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_exportposition']=$houseVillageService->checkPermissionMenu(112126,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_editposition']=$houseVillageService->checkPermissionMenu(112127,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delposition']=$houseVillageService->checkPermissionMenu(112128,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $configCustomizationService=new ConfigCustomizationService();
            $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
            $res['jz_wsxdsj_customized']=$isWsxCustomizedOpen ? 1:0;
            $res['position_set']='';
            $res['position_bind_car']=0;
            if($this->hasCloudIntercom()){
                $res['position_bind_car']=1;
            }
            if($isWsxCustomizedOpen){
                $houseVillagePositionSetService=new HouseVillagePositionSetService();
                $whereArr=array('village_id'=>$this->adminUser['village_id']);
                $positionSet = $houseVillagePositionSetService->getOneData($whereArr,'village_id,is_position_lease,last_lease_day,is_position_robbuy,last_robbuy_day,opt_position_num');
                if($positionSet){
                    $res['position_set']=$positionSet;
                }
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加车辆页面的信息查询
     * @author:zhubaodi
     * @date_time: 2022/3/9 17:16
     */
    public function getAddCarInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getAddCarInfo($data['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 校验停车卡类 返回是否能修改车辆到期时间
     * @author : lkz
     * @date : 2022/11/22
     * @return \json
     */
    public function checkParkingCarType(){
        $village_id = $this->adminUser['village_id'];
        $type_id = $this->request->post('type_id',0,'intval');//停车卡类
        try{
            $res = (new HouseNewParkingService())->checkParkingCarType($village_id,$type_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function getUserInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['value'] = $this->request->param('value','','trim');//车库名称
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getUserInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加车辆
     * @author:zhubaodi
     * @date_time: 2022/3/15 16:13
     */
    public function addCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['car_type'] = $this->request->post('car_type',0,'intval');//车辆类型 1电瓶车
        $car_type=$data['car_type'];
        $data['car_number'] = $this->request->post('car_number','','trim');//车牌号码
        $data['province'] = $this->request->post('province','','trim');//车牌号码 省
        $data['binding_type'] = $this->request->post('binding_type',0,'intval');//绑定对象 0公共 1房间 2业主
        $data['relationship'] = $this->request->post('relationship',0,'intval');//与车主关系
        $data['car_position_id'] = $this->request->post('car_position_id','');//车库名称
        $data['garage_id']=$this->request->post('garage_id',0,'intval');
        $data['car_position_id_path'] = $this->request->post('car_position_id_path');//车库绑定状态
        $data['car_stop_num'] = $this->request->post('car_stop_num');//车库名称
        $data['car_user_name'] = $this->request->post('car_user_name');//车主姓名
        $data['car_user_phone'] = $this->request->post('car_user_phone');//车主手机号
        $data['room_data'] = $this->request->post('room_data',0,'intval');//绑定房间id
        $data['pigcms_id'] = $this->request->post('pigcms_id',0,'intval');//绑定业主id
        $data['end_time'] = $this->request->post('end_time');//到期时间
        $data['parking_car_type'] = $this->request->post('parking_car_type');//停车卡类
        $data['car_color'] = $this->request->post('car_color');//车辆颜色
        $data['car_brands'] = $this->request->post('car_brands');//品牌型号
        $data['equipment_no'] = $this->request->post('equipment_no');//设备号

       /* if (empty($data['car_type'])){
            return api_output_error(1001, '请选择车辆类型');
        }*/
        if (empty($data['car_number'])){
            return api_output_error(1001, '车牌号码不能为空');
        }
        if (empty($data['garage_id']) && $car_type<1){
            return api_output_error(1001, '请选择车库');
        }
        if (empty($data['province'])){
            return api_output_error(1001, '请选择车牌省份');
        }
        if ($data['binding_type']>0 && empty($data['relationship'])){
            return api_output_error(1001, '请选择与车主关系');
        }
        if ($data['relationship']!=1){
            if (empty($data['car_user_name'])){
                return api_output_error(1001, '请填写车主姓名');
            }
            if (empty($data['car_user_phone'])){
                return api_output_error(1001, '请填写车主手机号');
            }
        }

        if($data['binding_type']==1){
            if (empty($data['room_data'])){
                return api_output_error(1001, '请选择绑定的房间');
            }
        }
        if($data['binding_type']==2){
            if (empty($data['pigcms_id'])){
                return api_output_error(1001, '请选择绑定的业主');
            }
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addCar($data);
        }catch (\Exception $e){
            fdump_api(['执行错误'=>$e->getMessage(),'data'=>$data, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], '$addCar');
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑车辆
     * @author:zhubaodi
     * @date_time: 2022/3/15 16:13
     */
    public function editCar(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['car_id'] = $this->request->post('car_id');//车辆id
        $data['car_type'] = $this->request->post('car_type');//车辆类型
        $car_type=$data['car_type'];
        $data['car_number'] = $this->request->post('car_number');//车牌号码
        $data['province'] = $this->request->post('province');//车牌号码 省
        $data['binding_type'] = $this->request->post('binding_type',0,'intval');//绑定对象 1房间 2业主 0公众
        $data['relationship'] = $this->request->post('relationship',0,'intval');//与车主关系
        $data['car_position_id'] = $this->request->post('car_position_id');//车位id
        $data['car_position_id_path'] = $this->request->post('car_position_id_path');//车库绑定状态
        $data['car_stop_num'] = $this->request->post('car_stop_num');//车库名称
        $data['car_user_name'] = $this->request->post('car_user_name');//车主姓名
        $data['car_user_phone'] = $this->request->post('car_user_phone');//车主手机号
        $data['room_data'] = $this->request->post('room_data',0);//绑定房间id
        $data['pigcms_id'] = $this->request->post('pigcms_id',0);//绑定业主id
        $data['end_time'] = $this->request->post('end_time','','trim');//到期时间
        $data['parking_car_type'] = $this->request->post('parking_car_type');//停车卡类
        $data['car_color'] = $this->request->post('car_color');//车辆颜色
        $data['car_brands'] = $this->request->post('car_brands');//品牌型号
        $data['equipment_no'] = $this->request->post('equipment_no','','trim');//设备号
        $data['examine_status'] = $this->request->post('examine_status');//审核状态
        $data['examine_response'] = $this->request->post('examine_response','','trim');//审核说明
        $data['garage_id']=$this->request->post('garage_id'); //可能是数组
        $name=$this->request->post('name','','trim');
        $phone=$this->request->post('phone','','trim');
        if (empty($data['car_id'])){
            return api_output_error(1001, '车辆id不能为空');
        }
        if ($data['examine_status']==1 && empty($data['garage_id']) && $car_type<1){
            return api_output_error(1001, '请选择车库！');
        }
        if ($data['examine_status']==1 && empty($data['car_position_id']) && $car_type<1){
            return api_output_error(1001, '请选择车位！');
        }
       /* if (empty($data['car_type'])){
            return api_output_error(1001, '请选择车辆类型');
        }*/
        if (empty($data['car_number'])){
            return api_output_error(1001, '车牌号码不能为空');
        }
        if (empty($data['province'])){
            return api_output_error(1001, '请选择车牌省份');
        }

        if ($data['examine_status']==1 && $data['binding_type']>0 && empty($data['relationship'])){
            return api_output_error(1001, '请选择与车主关系');
        }
        if($data['binding_type']==1){
            if ($data['examine_status']==1 && empty($data['room_data'])){
                return api_output_error(1001, '请选择绑定的房间');
            }
        }
        if($data['binding_type']==2){
            if ($data['examine_status']==1 && empty($data['pigcms_id'])){
                return api_output_error(1001, '请选择绑定的业主');
            }
        }
        if($name && $data['relationship']==1){
            $data['car_user_name']=$name;
        }
        if($phone && $data['relationship']==1){
            $data['car_user_phone']=$phone;
        }
        if ($data['examine_status']==2&& empty($data['examine_response'])){
            return api_output_error(1001, '请输入审核原因');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editCar($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除车辆
     * @author:zhubaodi
     * @date_time: 2022/3/15 16:13
     */
    public function delCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['car_id'] = $this->request->post('car_id');//车辆id
        if (empty($data['car_id'])){
            return api_output_error(1001, '车辆id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
//            $res = $db_house_new_parking_service->delCar($data);
            $res = $db_house_new_parking_service->delCarAll($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function delRecordCar(){
        $data=array();
        $data['park_id'] = $this->adminUser['village_id'];
        if (empty($data['park_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['record_id'] = $this->request->post('record_id','0','intval');//车辆id
        $out_record_id = $this->request->post('out_record_id','0','intval');//出场记录 id
        if($out_record_id>0){
            $data['record_id']=$out_record_id;
        }
        if (empty($data['record_id'])){
            return api_output_error(1001, '车辆记录id不能为空');
        }
        try{
            $db_house_new_parking_service = new HouseNewParkingService();
            $res=$db_house_new_parking_service->delRecordCar($data);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 查询车辆信息
     * @author:zhubaodi
     * @date_time: 2022/3/15 16:13
     */
    public function getCarInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['car_id'] = $this->request->param('car_id');//车辆id
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getCarInfo($data);
            $res['is_garage_multiple']=0;
            if($this->hasCloudIntercom()){
                $res['is_garage_multiple']=1;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询车辆列表
     * @author:zhubaodi
     * @date_time: 2022/3/9 20:59
     */
    public function getCarlist(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['search_type'] = $this->request->param('search_type','','trim');//筛选项 1车牌号 2使用状态 3车位号
        $data['search_value'] = $this->request->param('search_value','','trim');//筛选项对应值
        $data['page'] = $this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $data['pass_date']=$this->request->param('pass_date',''); //审核时间
        $data['record_date']=$this->request->param('record_date',''); //录入时间
        $data['checkType']=$this->request->param('checkType',''); //审核类型
        $data['position_id']=$this->request->param('position_id',0,'intval'); //车位id
        $db_house_new_parking_service = new HouseNewParkingService();
        try{

            $res = $db_house_new_parking_service->getCarlist($data,true);
            $houseVillageService=new HouseVillageService();
            $res['role_addcar']=$houseVillageService->checkPermissionMenu(112129,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_importcar']=$houseVillageService->checkPermissionMenu(112130,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_exportcar']=$houseVillageService->checkPermissionMenu(112131,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_editcar']=$houseVillageService->checkPermissionMenu(112132,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delcar']=$houseVillageService->checkPermissionMenu(112133,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delcar_all']=$houseVillageService->checkPermissionMenu(1113019,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['is_garage_multiple']=0;
            if($this->hasCloudIntercom()){
                $res['is_garage_multiple']=1;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    public function getCarBindPositionList(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['position_id']=$this->request->param('position_id',0,'intval'); //车位id
        $data['car_id']=$this->request->param('car_id',0,'intval'); //车量id
        $data['garage_id']=$this->request->param('garage_id'); //车库id
        $data['xtype']=$this->request->param('xtype','','trim');  //position 车位
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getCarBindPositionList($data);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    /**
     * 添加停车通道
     * @author:zhubaodi
     * @date_time: 2022/3/10 11:12
     */
    public function addPassage(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['passage_name'] = $this->request->post('passage_name','','trim');//筛选项 1车牌号 2使用状态 3车位号
        $data['channel_number'] = $this->request->post('channel_number','','trim');//筛选项对应值
        $data['device_number'] = $this->request->post('device_number','','trim');//筛选项对应值
        $data['long_lat'] = $this->request->post('long_lat','','trim');//筛选项对应值
        $data['status'] = $this->request->post('status','','trim');//筛选项对应值
        $data['passage_direction'] = $this->request->post('passage_direction','','trim');//筛选项对应值
        $data['passage_label'] = $this->request->post('passage_label','','trim');//筛选项对应值
        $data['passage_area'] = $this->request->post('passage_area','','trim');//筛选项对应值
        $data['area_type'] = $this->request->post('area_type',0,'intval');//筛选项对应值
        $data['d7_channelId'] = $this->request->post('d7_channelId','','trim');//筛选项对应值
        $data['device_type'] = $this->request->post('device_type',0,'intval');//筛选项对应值
        $data['deviceSetting'] = $this->request->post('deviceSetting',[]);
        $data['passage_type'] = $this->request->post('passage_type',0,'intval');//车刀片类型
        $data['passage_relation'] = $this->request->post('passage_relation',0,'intval');//关联车道id
        $data['garage_id']= $this->request->post('garage_id',0,'intval');//车库id
        $data['device_protocol']= $this->request->post('device_protocol',0,'intval');//0http 1mqtt
        $data['device_ip']= $this->request->post('device_ip','','trim');
       // $data['device_port']= $this->request->post('device_port','','trim');
        $data['device_pwd']= $this->request->post('device_pwd','','trim');
        $data['device_account']= $this->request->post('device_account','','trim');
        $data['camera_channel_id']= $this->request->post('camera_channel_id',0,'intval');
        $data['public_v_url']= $this->request->post('public_v_url','','trim'); //视频播放地址
        if (empty($data['passage_name'])){
            return api_output_error(1001, '通道名称不能为空');
        }
        if (!is_numeric($data['channel_number'])){
            return api_output_error(1001, '通道编号只能为数字');
        }
        if ($data['garage_id']<1){
            return api_output_error(1001, '请选择所属车库！');
        }
        /*
        if($data['device_port'] && $data['device_port']<10000){
            return api_output_error(1001, '请填写大于10000以上的没被用的端口号！');
        }
        $device_port_arr=array(7661,7091,7664,7665,7666,7663,9080,9554,8080,9022,18083,18080,1883,6379,9000,9001,9002,9003,30164,9502,32768);
        if($data['device_port'] && in_array($data['device_port'],$device_port_arr)){
            return api_output_error(1001, '此端口号已被占用了！');
        }
        */
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addPassage($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑停车通道
     * @author:zhubaodi
     * @date_time: 2022/3/10 11:12
     */
    public function editPassage(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id'] = $this->request->post('id');//筛选项 1车牌号 2使用状态 3车位号
        $data['passage_name'] = $this->request->post('passage_name','','trim');//筛选项 1车牌号 2使用状态 3车位号
        $data['channel_number'] = $this->request->post('channel_number','','trim');//筛选项对应值
        $data['device_number'] = $this->request->post('device_number','','trim');//筛选项对应值
        $data['long_lat'] = $this->request->post('long_lat','','trim');//筛选项对应值
        $data['status'] = $this->request->post('status','','trim');//筛选项对应值
        $data['passage_direction'] = $this->request->post('passage_direction','','trim');//筛选项对应值
        $data['passage_label'] = $this->request->post('passage_label','','trim');//筛选项对应值
        $data['passage_area'] = $this->request->post('passage_area','','trim');//筛选项对应值
        $data['area_type'] = $this->request->post('area_type',0,'intval');//筛选项对应值
        $data['d7_channelId'] = $this->request->post('d7_channelId','','trim');//筛选项对应值

        $data['device_type'] = $this->request->post('device_type',0,'intval');//筛选项对应值
        $data['deviceSetting'] = $this->request->post('deviceSetting',[]);
        $data['passage_type'] = $this->request->post('passage_type',0,'intval');//车刀片类型
        $data['passage_relation'] = $this->request->post('passage_relation',0,'intval');//关联车道id
        $data['garage_id']= $this->request->post('garage_id',0,'intval');//车库id
        $data['mac_address'] = $this->request->post('mac_address','','trim');//设备MAC
        $data['device_protocol']= $this->request->post('device_protocol',0,'intval');//0http 1mqtt
        $data['device_ip']= $this->request->post('device_ip','','trim');
       // $data['device_port']= $this->request->post('device_port','','trim');
        $data['device_pwd']= $this->request->post('device_pwd','','trim');
        $data['device_account']= $this->request->post('device_account','','trim');
        $data['camera_channel_id']= $this->request->post('camera_channel_id',0,'intval');
        $data['public_v_url']= $this->request->post('public_v_url','','trim'); //视频播放地址
        if (empty($data['id'])){
            return api_output_error(1002, '通道id不能为空');
        }
        if (empty($data['passage_name'])){
            return api_output_error(1002, '通道名称不能为空');
        }
        if ($data['garage_id']<1){
            return api_output_error(1002, '请选择所属车库！');
        }
        /*
        $device_port_arr=array(7661,7091,7664,7665,7666,7663,9080,9554,8080,9022,18083,18080,1883,6379,9000,9001,9002,9003,30164,9502,32768);
        if($data['device_port'] && $data['device_port']<10000){
            return api_output_error(1001, '请填写大于10000以上的没被用的端口号！');
        }
        if($data['device_port'] && in_array($data['device_port'],$device_port_arr)){
            return api_output_error(1001, '此端口号已被占用了！');
        }
        */
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editPassage($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除停车通道
     * @author:zhubaodi
     * @date_time: 2022/3/10 11:12
     */
    public function delPassage(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id'] = $this->request->param('id');//筛选项 1车牌号 2使用状态 3车位号
        if (empty($data['id'])){
            return api_output_error(1002, '通道id不能为空');
        }

        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delPassage($data['id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 查询通道详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 10:52
     */
    public function getPassageInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id'] = $this->request->param('id',0,'intval');
        if (empty($data['id'])){
            return api_output_error(1001, '车道id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getPassageInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询通道列表
     * @author:zhubaodi
     * @date_time: 2022/3/11 10:52
     */
    public function getPassageList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['garage_id'] = $this->request->param('garage_id',0,'intval');
        $data['page'] = $this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getPassageList($data);
            
            foreach ($res['list'] as &$re){
                if (!empty($re['mac_address']) && $re['park_sys_type']=='A11'){
                  $mac_address =   str_replace(':','_',$re['mac_address'] );
                  $re['mac_address_uri'] = "https://{$mac_address}.lsqv.com:96/login";
                }else{
                    $re['mac_address_uri'] = '';
                }
            }
            
            $houseVillageService=new HouseVillageService();
            $res['role_addcarlane']=$houseVillageService->checkPermissionMenu(112134,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_editcarlane']=$houseVillageService->checkPermissionMenu(112135,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delcarlane']=$houseVillageService->checkPermissionMenu(112136,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_ewmcarlane']=$houseVillageService->checkPermissionMenu(112137,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_screenset']=$houseVillageService->checkPermissionMenu(112138,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_liftingrod']=$houseVillageService->checkPermissionMenu(112139,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_recordcar']=$houseVillageService->checkPermissionMenu(112140,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['device_protocol_change']=1;
            $res['device_detail_info']=1;
            $res['device_detail_info_port']=0;
            $res['device_protocol_change_tips']=1;
            if($this->hasCloudIntercom()){
                $res['device_protocol_change_tips']=0;
                $res['device_detail_info_port']=1;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 获取通道二维码
     * @author:zhubaodi
     * @date_time: 2022/3/11 11:14
     */
     public function getQrcodePassage(){
         $data['village_id'] = $this->adminUser['village_id'];
         if (empty($data['village_id'])){
             return api_output_error(1002, '请先登录小区后台');
         }
         $data['passage_id'] = $this->request->param('passage_id',0,'intval');
         if (empty($data['passage_id'])){
             return api_output_error(1001, '通道编号不能为空');
         }
         $db_house_new_parking_service = new HouseNewParkingService();
         try{
             $res = $db_house_new_parking_service->getQrcodePassage($data);
         }catch (\Exception $e){
             return api_output_error(-1, $e->getMessage());
         }
         return api_output(0,$res);
     }

    /**
     * 获取预付二维码
     * @author:zhubaodi
     * @date_time: 2022/3/11 11:14
     */
    public function getQrcodeSpread(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['garage_id'] = $this->request->post('garage_id',0,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getQrcodeSpread($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 优惠券二维码
     * @author:zhubaodi
     * @date_time: 2022/3/11 11:14
     */
    public function getQrcodeCoupons(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['coupons_id'] = $this->request->param('coupons_id',0,'intval');
        if (empty($data['coupons_id'])){
            return api_output_error(1001, '优惠券id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getQrcodeCoupons($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 店铺管理二维码
     * @author:zhubaodi
     * @date_time: 2022/3/11 11:14
     */
    public function getQrcodeShop(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['m_id'] = $this->request->param('m_id',0,'intval');
        if (empty($data['m_id'])){
            return api_output_error(1001, '店铺id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getQrcodeShop($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加标签分类
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:55
     */
    public function addLabelCat(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_function'] = $this->request->post('cat_function','','trim');//关联功能
        $data['cat_name'] = $this->request->post('cat_name','','trim');//分类名称
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addLabelCat($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 移动/编辑标签分类
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:55
     */
    public function editLabelCat(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_function'] = $this->request->post('cat_function','','trim');//关联功能
        $data['cat_name'] = $this->request->post('cat_name','','trim');//分类名称
        $data['cat_id'] = $this->request->post('cat_id',0,'intval');//分类id
        if ($data['cat_id']==99999){
            return api_output_error(1001, '当前分类不能编辑');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editLabelCat($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 移动/编辑标签分类
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:55
     */
    public function getLabelCatInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_id'] = $this->request->param('cat_id',0,'intval');//分类id
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getLabelCatInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除标签分类
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:56
     */
    public function delLabelCat(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_id'] = $this->request->param('cat_id',0,'intval');//分类id
        if (empty($data['cat_id'])){
            return api_output_error(1001, '分类id不能为空');
        }

        if ($data['cat_id']==99999){
            return api_output_error(1001, '当前分类不能删除');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delLabelCat($data['cat_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     *添加标签
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:17
     */
    public function addLabel(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_id'] = $this->request->post('cat_id');//分类id
        $data['label_name'] = $this->request->post('label_name','','trim');//标签名称
        if (empty($data['label_name'])){
            return api_output_error(1001, '标签名称不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addLabel($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑标签
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:17
     */
    public function editLabel(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_id'] = $this->request->post('cat_id');//分类id
        $data['label_id'] = $this->request->post('label_id');//标签id
        $data['label_name'] = $this->request->post('label_name','','trim');//标签名称
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editLabel($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 移动标签
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:17
     */
    public function moveLabel(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_id'] = $this->request->param('cat_id');//分类id
        $data['label_id'] = $this->request->param('label_id');//标签id
        if (empty($data['cat_id'])){
            return api_output_error(1001, '分类id不能空');
        }
        if (empty($data['label_id'])){
            return api_output_error(1001, '标签id不能空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->moveLabel($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除标签分类
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:56
     */
    public function delLabel(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['label_id'] = $this->request->param('label_id',0,'intval');//标签id
        if (empty($data['label_id'])){
            return api_output_error(1001, '标签id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delLabel($data['label_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 批量删除标签
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:56
     */
    public function delAllLabel(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['label_id'] = $this->request->param('label_id','','trim');//标签id
        if (empty($data['label_id'])){
            return api_output_error(1001, '标签id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delLabel($data['label_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 查询标签分类列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:18
     */
    public function getLabelCatList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getLabelCatList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询标签分类列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:18
     */
    public function getLabelCatsList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getLabelCatsList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询标签列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:18
     */
    public function getLabelList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_id'] = $this->request->param('cat_id',0,'intval');
        $data['page'] = $this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getLabelList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询标签列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 15:18
     */
    public function getLabelInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['label_id'] = $this->request->param('label_id',0,'intval');
        if (empty($data['label_id'])){
            return api_output_error(1002, '标签id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getLabelInfo($data['label_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加免费车
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:09
     */
    public function addFreeCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['park_type'] = $this->request->post('park_type',0,'intval');//车牌类型
        $data['first_name'] = $this->request->post('first_name','','trim');//车牌开头包含
        $data['last_name'] = $this->request->post('last_name','','trim');//车牌结尾包含
        $data['free_park'] = $this->request->post('free_park','','trim');//完整车牌号
        if (empty($data['park_type'])){
            return api_output_error(1001, '请先选择车牌类型');
        }
        if (empty( $data['first_name'])&&empty( $data['last_name'])&&empty( $data['free_park'])){
            return api_output_error(1001, '请输入车牌信息');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addFreeCar($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑免费车
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:09
     */
    public function editFreeCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id'] = $this->request->post('free_id');
        $data['park_type'] = $this->request->post('park_type');//车牌类型
        $data['first_name'] = $this->request->post('first_name');//车牌开头包含
        $data['last_name'] = $this->request->post('last_name');//车牌结尾包含
        $data['free_park'] = $this->request->post('free_park');//完整车牌号
        if (empty($data['park_type'])){
            return api_output_error(1001, '请先选择车牌类型');
        }
        if (empty($data['id'])){
            return api_output_error(1001, '免费车id不能为空');
        }

        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editFreeCar($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 删除免费车
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:56
     */
    public function delFreeCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['free_id'] = $this->request->param('free_id',0,'intval');//标签id
        if (empty($data['free_id'])){
            return api_output_error(1001, '免费车id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delFreeCar($data['free_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询免费车详情
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:28
     */
    public function getFreeCarInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['free_id'] = $this->request->param('free_id',0,'intval');//标签id
        if (empty($data['free_id'])){
            return api_output_error(1001, '免费车id不能为空');
        }

        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getFreeCarInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询免费车列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:28
     */
    public function getFreeCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['park_type'] = $this->request->param('park_type');//车牌类型
        $data['free_park'] = $this->request->param('free_name');//完整车牌号
        $data['page'] = $this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getFreeCar($data);
            $houseVillageService=new HouseVillageService();
            $res['role_addfreecar']=$houseVillageService->checkPermissionMenu(112160,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_editfreecar']=$houseVillageService->checkPermissionMenu(112161,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delfreecar']=$houseVillageService->checkPermissionMenu(112162,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function checkOperationFreeCar(){
        $village_id = $this->adminUser['village_id'];
        try{
            $result = (new A11Service('A11'))->getVillageParkConfigMethod($village_id);
            $result=[
                'status'=>$result['error'] ? false : true
            ];
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }


    /**
     * 添加黑名单
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:09
     */
    public function addBlackCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['user_name'] = $this->request->post('user_name','','trim');//车牌类型
        $data['phone'] = $this->request->post('phone','','trim');//车牌类型
        $data['car_number'] = $this->request->post('car_number','','trim');//车牌类型
        $data['city_arr']=$this->request->post('city_arr','','trim');//车牌类型
        $data['remark']=$this->request->post('remark','','trim');//车牌类型
        if (empty($data['city_arr'])||empty($data['car_number'] )){
            return api_output_error(1001, '车牌号不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->addBlackCar($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑黑名单
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:09
     */
    public function editBlackCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['id'] = $this->request->post('black_id');
        $data['user_name'] = $this->request->post('user_name');//车牌类型
        $data['phone'] = $this->request->post('phone');//车牌类型
        $data['car_number'] = $this->request->post('car_number');//车牌类型
        $data['city_arr']=$this->request->post('city_arr');//车牌类型
        $data['remark']=$this->request->post('remark');//车牌类型
        if (empty($data['city_arr'])||empty($data['car_number'] )){
            return api_output_error(1001, '车牌号不能为空');
        }

        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editBlackCar($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 删除黑名单
     * @author:zhubaodi
     * @date_time: 2022/3/10 14:56
     */
    public function delBlackCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['black_id'] = $this->request->param('black_id',0,'intval');//标签id
        if (empty($data['black_id'])){
            return api_output_error(1001, '免费车id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->delBlackCar($data['black_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询黑名单列表
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:28
     */
    public function getBlackCar(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['car_number'] = $this->request->param('car_number','','trim');
        $data['user_name'] = $this->request->param('user_name','','trim');
        $data['page'] = $this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getBlackCar($data,true);
            $houseVillageService=new HouseVillageService();
            $res['role_addblack']=$houseVillageService->checkPermissionMenu(112157,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_editblack']=$houseVillageService->checkPermissionMenu(112158,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delblack']=$houseVillageService->checkPermissionMenu(112159,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询黑名单详情
     * @author:zhubaodi
     * @date_time: 2022/3/10 16:28
     */
    public function getBlackCarInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['black_id'] = $this->request->param('black_id',0,'intval');//标签id
        if (empty($data['black_id'])){
            return api_output_error(1001, '免费车id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getBlackCarInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    /**
     * 根据店铺名称搜索店铺列表
     * @author:zhubaodi
     * @date_time: 2022/3/11 13:35
     */
    public function shop_search(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['m_name'] = $this->request->param('m_name');//标签id
        /*if (empty($data['m_name'])){
            return api_output_error(1001, '店铺名称不能为空');
        }*/
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->shop_search($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加店铺
     * @author:zhubaodi
     * @date_time: 2022/3/16 10:51
     */
    public function add_park_shop(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['m_name'] = $this->request->post('m_name');//标签id
        $data['bind_m_id'] = $this->request->post('bind_m_id');//标签id
        $data['type'] = $this->request->post('type');//标签id
        $data['remark'] = $this->request->post('remark');//标签id
        if (empty($data['m_name'])){
            return api_output_error(1001, '店铺名称不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->add_park_shop($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function edit_park_shop(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['m_name'] = $this->request->post('m_name');//标签id
        $data['bind_m_id'] = $this->request->post('bind_m_id');//标签id
        $data['type'] = $this->request->post('type');//标签id
        $data['remark'] = $this->request->post('remark');//标签id
        $data['m_id'] = $this->request->post('m_id');//标签id
        if (empty($data['m_id'])){
            return api_output_error(1001, '店铺id不能为空');
        }
        if (empty($data['m_name'])){
            return api_output_error(1001, '店铺名称不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->edit_park_shop($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function del_park_shop(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['m_id'] = $this->request->param('m_id');//标签id
        if (empty($data['m_id'])){
            return api_output_error(1001, '店铺id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->del_park_shop($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function getParkShopList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['date'] = $this->request->param('date');
        $data['m_name'] = $this->request->param('m_name');
        $data['page'] = $this->request->param('page',1,'intval');
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkShopList($data);
            $houseVillageService=new HouseVillageService();
            $res['role_addstore']=$houseVillageService->checkPermissionMenu(112141,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_editstore']=$houseVillageService->checkPermissionMenu(112142,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delstore']=$houseVillageService->checkPermissionMenu(112143,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_managecoupon']=$houseVillageService->checkPermissionMenu(112144,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['can_send_coupon']=0;
            if($this->hasCloudIntercom()){
                $res['can_send_coupon']=1;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }

    public function getParkShopInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['m_id'] = $this->request->param('m_id');//标签id
        if (empty($data['m_id'])){
            return api_output_error(1001, '店铺id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkShopInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加优惠券
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function add_park_coupons(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['c_title'] = $this->request->post('c_title','','trim');//标签id
        $data['c_price'] = $this->request->post('c_price',0);//标签id
        $data['c_free_price'] = $this->request->post('c_free_price',0);//标签id
        $data['status'] = $this->request->post('status',0,'intval');//标签id
        $data['remark'] = $this->request->post('remark','','trim');//标签id remark
        $data['garage_id'] = $this->request->post('garage_id',0,'intval');//车场id
        if (!$data['c_title']) {
            return api_output_error(1001, '请填写优惠券名称');
        }
        if (!$data['c_price']) {
            return api_output_error(1001, '请填写优惠券价格');
        }
        if (!$data['c_free_price']) {
            return api_output_error(1001, '请填写每一次停车免费金额');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->add_park_coupons($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑优惠券
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function edit_park_coupons(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['c_id'] = $this->request->post('c_id');//标签id
        $data['c_title'] = $this->request->post('c_title');//标签id
        $data['c_price'] = $this->request->post('c_price',0);//标签id
        $data['c_free_price'] = $this->request->post('c_free_price',0);//标签id
        $data['status'] = $this->request->post('status');//标签id
        $data['remark'] = $this->request->post('remark');//标签id remark
        $data['garage_id'] = $this->request->post('garage_id',0,'intval');//车场id
        if (!$data['c_id']) {
            return api_output_error(1001, '优惠券id不能为空');
        }
        if (!$data['c_title']) {
            return api_output_error(1001, '请填写优惠券名称');
        }
        if (!$data['c_price']) {
            return api_output_error(1001, '请填写优惠券价格');
        }
        if (!$data['c_free_price']) {
            return api_output_error(1001, '请填写每一次停车免费金额');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->edit_park_coupons($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除优惠券
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function del_park_coupons(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['c_id'] = $this->request->param('c_id');//标签id
        if (!$data['c_id']) {
            return api_output_error(1001, '优惠券id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->del_park_coupons($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询优惠券详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function get_park_coupons_info(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['c_id'] = $this->request->param('c_id');//标签id
        if (!$data['c_id']) {
            return api_output_error(1001, '优惠券id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->get_park_coupons_info($data);
            if (isset($res['garage_id']) && intval($res['garage_id']) <= 0) {
                $res['garage_id'] = null;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询优惠券列表
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function getParkCouponsList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['date'] = $this->request->param('date');
        $data['c_title'] = $this->request->param('c_title');
        $data['page'] = $this->request->param('page',0,'intval');
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkCouponsList($data);
            $houseVillageService=new HouseVillageService();
            $res['role_addcoupon']=$houseVillageService->checkPermissionMenu(112145,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_editcoupon']=$houseVillageService->checkPermissionMenu(112146,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_delcoupon']=$houseVillageService->checkPermissionMenu(112147,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询优惠券列表
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function getParkCouponsLists(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['date'] = $this->request->param('date');
        $data['c_title'] = $this->request->param('c_title');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkCouponsLists($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 添加店铺优惠券信息
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function add_park_shop_coupons(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cid'] = $this->request->post('cid',0,'intval');//标签id
        $data['mid'] = $this->request->post('mid',0,'intval');//标签id
        $data['status'] = $this->request->post('status',0,'intval');//标签id
        $data['free_money'] = $this->request->post('free_money','','trim');//标签id
        $data['num'] = $this->request->post('num',0,'intval');//标签id
        $data['receivable_money'] = $this->request->post('receivable_money','','trim');//标签id
        $data['paid_money'] = $this->request->post('paid_money','','trim');//标签id
        $data['remark'] = $this->request->post('remark','','trim');//标签id
        if (empty($data['cid'])) {
            return api_output_error(1001, '请选择优惠券');
        }
        if (empty($data['free_money'])) {
            return api_output_error(1001, '每次停车免费金额异常');
        }
        if (empty($data['num'])) {
            return api_output_error(1001, '请输入添加数量');
        }
        if ($data['num']<0) {
            return api_output_error(1001, '请正确输入添加数量');
        }
        if (empty($data['receivable_money'])) {
            return api_output_error(1001, '请输入应收金额');
        }
        if ($data['receivable_money']<0) {
            return api_output_error(1001, '请正确输入应收金额');
        }
        if (empty($data['paid_money'])) {
            return api_output_error(1001, '请输入实付金额');
        }
        if ($data['paid_money']<0) {
            return api_output_error(1001, '请正确输入实付金额');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->add_park_shop_coupons($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询店铺优惠券列表信息
     * @author:zhubaodi
     * @date_time: 2022/3/11 15:14
     */
    public function getShopCouponsList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['m_id'] = $this->request->param('m_id');//标签id
        $data['page'] = $this->request->param('page',1,'intval');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getShopCouponsList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    /***
    * 发券
     **/
    public function sendCouponToCarUse(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['car_number'] = $this->request->post('car_number','','trim');//车牌号
        $data['cid'] = $this->request->post('cid','0','intval');
        $data['idd'] = $this->request->post('idd','0','intval');
        $data['mid'] = $this->request->post('mid','0','intval');
        $data['send_num'] = $this->request->post('send_num','1','intval'); //数量
        $data['garage_id'] = $this->request->post('garage_id','0','intval'); //车库id
        if($data['idd']<1){
            return api_output_error(1001, '优惠券参数错误');
        }
        if(empty($data['car_number'])){
            return api_output_error(1001, '请填写车牌！');
        }
        try{
            $db_house_new_parking_service = new HouseNewParkingService();
            $res = $db_house_new_parking_service->sendCouponToCarUse($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    /**
     * 优惠券购买记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getPayCouponsList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['date'] = $this->request->param('date');//标签id
        $data['param'] = $this->request->param('param');//标签id
        $data['title'] = $this->request->param('title');//标签id
        $data['page'] = $this->request->param('page',1,'intval');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getPayCouponsList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 优惠券领取记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getReceiveCouponsList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['coupons_id'] = $this->request->param('coupons_id');//标签id
        if (empty($data['coupons_id'])){
            return api_output_error(1001, '店铺优惠券id不能为空');
        }
        $data['page'] = $this->request->param('page',1,'intval');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getReceiveCouponsList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 在场车辆记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getInParkList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id 1车主姓名 2车主手机号 3车牌号
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//标签id
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getInParkList($data,true);
            $houseVillageService=new HouseVillageService();
            $res['role_export1car']=$houseVillageService->checkPermissionMenu(112150,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_edit1car']=$houseVillageService->checkPermissionMenu(112148,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_del1car']=$houseVillageService->checkPermissionMenu(112149,$this->adminUser,$this->login_role,$this->dismissPermissionRole);

        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 在场车辆详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getInParkInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['record_id'] = $this->request->param('record_id');//标签id
        if (empty($data['record_id'])){
            return api_output_error(1001, '纪录id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getCarInParkInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 在场车辆详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function editInParkInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['record_id'] = $this->request->param('record_id');//标签id
        if (empty($data['record_id'])){
            return api_output_error(1001, '纪录id不能为空');
        }
        $data['car_number'] = $this->request->param('car_number');//标签id
        if (empty($data['car_number'])){
            return api_output_error(1001, '车牌号不能为空');
        }
        $data['user_phone'] = $this->request->param('user_phone');//标签id
       /* if (empty($data['user_phone'])){
            return api_output_error(1001, '车主手机号不能为空');
        }*/
        $data['accessTime'] = $this->request->param('accessTime');//标签id
        if (empty($data['accessTime'])){
            return api_output_error(1001, '车辆入场时间不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editInParkInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 月租车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getMonthParkList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');// 1用户姓名,2用户手机号3车牌号
        $data['value'] = $this->request->param('value');//搜索的值
        $data['date'] = $this->request->param('date');//进场时间
        $data['outdate'] = $this->request->param('outdate');//出场时间
        $data['date_type']= $this->request->param('date_type',1,'intval');//1进场 2出场
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getMonthParkList($data,true);
            $houseVillageService=new HouseVillageService();
            $res['role_export2car']=$houseVillageService->checkPermissionMenu(112151,$this->adminUser,$this->login_role,$this->dismissPermissionRole);

        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 月租车进出详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getMonthParkInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['record_id'] = $this->request->param('record_id');//标签id
        if (empty($data['record_id'])){
            return api_output_error(1001, '纪录id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getMonthParkInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 临时车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTempParkList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//进场时间
        $data['outdate'] = $this->request->param('outdate');//出场时间
        $data['page'] = $this->request->param('page');//标签id
        $data['place_type'] = $this->request->param('place_type');//1车场名称 2车道名称
        $data['place_type'] = !empty($data['place_type']) ? intval($data['place_type']):0;
        $data['place_value'] = $this->request->param('place_value');//车场 或 车道 名称值
        $data['place_value'] = !empty($data['place_value']) ? htmlspecialchars($data['place_value'],ENT_QUOTES):'';
        $data['date_type']= $this->request->param('date_type',1,'intval');//1进场 2出场
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $data['stop_time']=$this->request->param('stop_time',0,'trim');  //多少小时内
        $data['pay_status']=$this->request->param('pay_status','','trim');  //all 所有 paied收费 free免费
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getTempParkList($data,true);
            $houseVillageService=new HouseVillageService();
            $res['role_export3car']=$houseVillageService->checkPermissionMenu(112152,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_deltempcar']=$houseVillageService->checkPermissionMenu(112153,$this->adminUser,$this->login_role,$this->dismissPermissionRole);

        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 临时车进出详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTempParkInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['record_id'] = $this->request->param('record_id',0,'intval');//标签id
        $data['out_record_id'] = $this->request->param('out_record_id',0,'intval');//标签id
        if (empty($data['record_id'])){
            return api_output_error(1001, '纪录id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getTempParkInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    /**
     * 手动开闸记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getOpenGateList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//标签id
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getOpenGateList($data,true);
            $houseVillageService=new HouseVillageService();
            $res['role_export4car']=$houseVillageService->checkPermissionMenu(112154,$this->adminUser,$this->login_role,$this->dismissPermissionRole);

        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 手动开闸详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getOpenGateInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['record_id'] = $this->request->param('record_id');//标签id
        if (empty($data['record_id'])){
            return api_output_error(1001, '纪录id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getTempParkInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 不在场车辆记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */

    public function getOutParkList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//标签id
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getOutParkList($data,true);
            $houseVillageService=new HouseVillageService();
            $res['role_export5car']=$houseVillageService->checkPermissionMenu(112155,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $res['role_deloutcar']=$houseVillageService->checkPermissionMenu(112156,$this->adminUser,$this->login_role,$this->dismissPermissionRole);

        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 不在场车辆详情
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getOutParkInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['record_id'] = $this->request->param('record_id');//标签id
        if (empty($data['record_id'])){
            return api_output_error(1001, '纪录id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getOutParkInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 给不在场车辆打标签
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function editOutParkInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['record_id'] = $this->request->param('record_id');//标签id
        $data['label_id'] = $this->request->param('label_id');//标签id
        if (empty($data['record_id'])){
            return api_output_error(1001, '纪录id不能为空');
        }
        if (empty($data['label_id'])){
            return api_output_error(1001, '标签id不能为空');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->editOutParkInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 电瓶车出入记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getElectricParkList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//标签id
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getElectricParkList($data,true);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    /**
     * 储值车出入记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTemporaryParkList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['value']=$data['value']?trim($data['value']):'';
        $data['date'] = $this->request->param('date');//标签id
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getTemporaryParkList($data,true);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    /**
     * 储值车充值记录
     * @author:zhubaodi
     * @date_time: 2022/3/11 16:33
     */
    public function getTemporaryPayList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//标签id
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getTemporaryPayList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function getParkType(){
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkType();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function getParkProvice(){
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkProvice();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 导入车位
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function uplodePosition(){
        $village_id=$this->request->param('village_id');//village_id
        if (empty($village_id)){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        $file = $this->request->file('file');

       //  $upload_dir = $this->request->param('upload_dir');
        $upload_dir='park/file';
        try {
            $savenum = $db_house_new_parking_service->uplodePosition($village_id,$file, $upload_dir);
            return api_output(0, $savenum, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 导出车位
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downPosition(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['garage_id'] = $this->request->post('garage_id','','trim');//车库名称
        $data['position_num'] = $this->request->post('position_num','','trim');//车库名称
        $data['position_status'] = $this->request->post('position_status',0,'intval');//车库绑定状态
        $data['position_car_status'] = $this->request->post('position_car_status',0,'intval');//租售状态
        $data['position_pattern'] = $this->request->param('position_pattern',0);//1真实车位 2虚拟车位
        if (empty($data['position_pattern'])){
            $data['position_pattern']=1;
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downPosition($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 导入文件
     * @return \json
     */
    public function uploadFiles()
    {
        $file = $this->request->file('file');
        if(!$file){
            return api_output_error(1001,'请上传文件');
        }
        try {
            validate(['file' => [
                'fileSize' => 1024 * 1024 * 20,
                'fileExt' => 'xls,xlsx',
            ]])->check(['file' => $file]);

            $fileName = $file->getOriginalName();

            $file_arr = explode('.',$fileName);
            if(count($file_arr)==2)
            {
                $file_type = $file_arr[1];
            }else{
                return api_output_error(1001,'请上传有效文件');
            }

            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( 'park/file',$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data['url'] = '/upload/'.$savename;
            $data['name'] = $fileName;
            $data['file_type'] = $file_type;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");

    }

    /**

     * 导入车辆
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function uplodeCar(){
      //   $data['village_id'] = $this->adminUser['village_id'];
        $data['village_id'] = $this->request->param('village_id');//标签id
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        $file = $this->request->file('file');

        if(!$file){
            return api_output_error(1001,'请上传文件');
        }
      //   $upload_dir = $this->request->param('upload_dir');
        $upload_dir='park/file';

        try {
            $savenum = $db_house_new_parking_service->uplodeCar( $data['village_id'],$file, $upload_dir);
            return api_output(0, $savenum, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出车辆
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downCar(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['search_type'] = $this->request->param('search_type','','trim');//筛选项 1车牌号 2使用状态 3车位号
        $data['search_value'] = $this->request->param('search_value','','trim');//筛选项对应值
        $data['pass_date']=$this->request->param('pass_date',''); //审核时间
        $data['record_date']=$this->request->param('record_date',''); //录入时间
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downCar($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    public function getParkConfig(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkConfig();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function getLabelFunction(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getLabelFunction();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询归属区域
     * @author:zhubaodi
     * @date_time: 2022/3/23 16:48
     */
    public function getAreaList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $data['isHandle'] = 1;
            $res = $db_house_new_parking_service->getAreaList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function getParkingGarageSelectList()
    {
        $data = [];
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])) {
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        $result = [];
        try {
            $data['isHandle'] = 1;
            $result['select'] = $db_house_new_parking_service->getParkingGarageSelectList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }


    public function getParkCouponGarage()
    {
        $data = [];
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])) {
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['c_id'] = $this->request->post('c_id');//标签id
        $db_house_new_parking_service = new HouseNewParkingService();
        $result = [];
        try {
            $data['isHandle'] = 1;
            $result['info'] = $db_house_new_parking_service->getParkCouponGarage($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 查询不在场标签
     * @author:zhubaodi
     * @date_time: 2022/4/21 18:53
     */
    public function getParkLabelList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_function'] = 'outPark';
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkLabelList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询车道标签
     * @author:zhubaodi
     * @date_time: 2022/4/21 18:53
     */
    public function getPassageLabelList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['cat_function'] = 'passage';
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkLabelList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 导出在场车辆记录
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downInPark(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//标签id
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downInPark($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 导出月租车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downMonthPark(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//搜索的值
        $data['date'] = $this->request->param('date');//进场时间
        $data['outdate'] = $this->request->param('outdate');//出场时间
        $data['date_type']= $this->request->param('date_type',1,'intval');//1进场 2出场
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downMonthPark($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 导出临时车进出记录
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downTempPark(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//车牌
        $data['date'] = $this->request->param('date');//入场日期范围
        $data['outdate'] = $this->request->param('outdate');//进场日期范围
        $data['place_type'] = $this->request->param('place_type');//1车场名称 2车道名称
        $data['place_type'] = !empty($data['place_type']) ? intval($data['place_type']):0;
        $data['place_value'] = $this->request->param('place_value');//车场 或 车道 名称值
        $data['place_value'] = !empty($data['place_value']) ? htmlspecialchars($data['place_value'],ENT_QUOTES):'';
        $data['date_type']= $this->request->param('date_type',1,'intval');//1进场 2出场
        $data['stop_time']=$this->request->param('stop_time',0,'trim');  //多少小时内
        $data['pay_status']=$this->request->param('pay_status','','trim');  //all 所有 paied收费 free免费
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downTempPark($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 导出手动开闸记录
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downOpenGate(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//标签id
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downOpenGate($data, true);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 导出不在场车辆记录
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downOutPark(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['param'] = $this->request->param('param');//标签id
        $data['value'] = $this->request->param('value');//标签id
        $data['date'] = $this->request->param('date');//标签id
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downOutPark($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 导出车位模板
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downPositionModel(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downPositionModel();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 导出车辆模板
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function downCarModel(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->downCarModel();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 智慧停车场批量同步
     * @author:zhubaodi
     * @date_time: 2022/3/19 15:49
     */
    public function sysParkCarDevice(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $xtype = $this->request->param('xtype','','trim');
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->sysParkCarDevice($data,$xtype);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 获取语音配置内容
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function getVoiceSet(){
        $village_id = $this->adminUser['village_id'];
        $passage_id = $this->request->param('passage_id',0);
        if (empty($village_id)){
            return api_output_error(1002, '请先登录');
        }
        if (empty($passage_id)){
            return api_output_error(1001, '通道id不能为空');
        }
        $db_house_visitor_service = new HouseVillageParkingService();
        try{
            $list = $db_house_visitor_service->getVoiceSet($village_id,$passage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取显屏配置内容
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function getScreenSet(){
        $village_id = $this->adminUser['village_id'];
        $passage_id = $this->request->param('passage_id',0);
        if (empty($village_id)){
            return api_output_error(1002, '请先登录');
        }
        if (empty($passage_id)){
            return api_output_error(1001, '通道id不能为空');
        }
        $db_house_visitor_service = new HouseVillageParkingService();
        try{
            $list = $db_house_visitor_service->getScreenSet($village_id,$passage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取显屏配置内容
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function setVoiceSet(){
        $village_id = $this->adminUser['village_id'];
        $passage_id = $this->request->param('passage_id',0);
        $temp_id = $this->request->param('temp_id',0);
        $mouth_id = $this->request->param('mouth_id',0);
        if (empty($village_id)){
            return api_output_error(1002, '请先登录');
        }
        if (empty($passage_id)){
            return api_output_error(1001, '通道id不能为空');
        }
        $db_house_visitor_service = new HouseVillageParkingService();
        try{
            $list = $db_house_visitor_service->setVoiceSet($village_id,$passage_id,$temp_id,$mouth_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 设置显屏配置内容
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function setScreenSet(){
        $village_id = $this->adminUser['village_id'];
        $passage_id = $this->request->param('passage_id',0);
        $content = $this->request->param('content','');
        if (empty($village_id)){
            return api_output_error(1002, '请先登录');
        }
        if (empty($passage_id)){
            return api_output_error(1001, '通道id不能为空');
        }
        $db_house_visitor_service = new HouseVillageParkingService();
        try{
            $list = $db_house_visitor_service->setScreenSet($village_id,$passage_id,$content);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 手动抬竿
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function open_gate(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['id'] = $this->request->param('id',0);
        $data['car_number'] = $this->request->param('car_number','','trim');
        $data['open_type'] = $this->request->param('open_type',0);
        $data['pay_type'] = $this->request->param('pay_type',0);
        $data['park_time'] = $this->request->param('park_time','','trim');
        $data['price'] = $this->request->param('price',0);

        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        if (empty($data['id'])){
            return api_output_error(1001, '通道id不能为空');
        }
        if (empty($data['car_number'])){
            return api_output_error(1001, '车牌号不能为空');
        }
        if($data['open_type']==1) {
            if (empty($data['park_time'])) {
                return api_output_error(1001, '停车时长不能为空');
            }
            if ($data['park_time'] < 0) {
                return api_output_error(1001, '停车时长不能小于0');
            }
            if (floor($data['park_time']) != $data['park_time']) {
                return api_output_error(1001, '停车时长请输入正整数');
            }
            if (empty($data['price'])) {
                return api_output_error(1001, '停车费用不能为空');
            }
            if ($data['price'] < 0) {
                return api_output_error(1001, '停车费用不能小于0');
            }
        }else{
            $data['price']=0;
            $data['park_time']=0;
        }
        $db_house_visitor_service = new HouseNewParkingService();
        try{
            $list = $db_house_visitor_service->open_gate($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 手动抬竿
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function test1(){
        /*$service_image=new ImageService();
        $fileHandle   = new FileHandle();
        $image_data['imgPath']='/home/pigcms/shequ-test.pigcms.com/upload/park_log/20221128/277c9082-75128b22/20221128034952_image_small_京AF0236.jpg';
        $faceImg = $image_data['imgPath'];
        $res=$fileHandle->upload($faceImg);
        var_dump($res);die;
        if($fileHandle->check_open_oss()) {
            $res=$fileHandle->unlink($image_data['imgPath']);
           
        }
        $park_data['in_image_small'] = $fileHandle->get_path($faceImg);
        print_r($park_data['in_image_small']);die;*/
        
        
       //  $data['serialno']='202207091101206188359528';
        $db_house_visitor_service =new A11Service();
        $car_number='京AF0236';
        $passage_info=array (
            'id' => 65,
            'village_id' => 1,
            'passage_name' => 'A11测试车道',
            'long' => '31.817704',
            'lat' => '117.216257',
            'status' => 1,
            'passage_direction' => 1,
            'device_number' => '277c9082-75128b22',
            'channel_number' => 1111454,
            'park_sys_type' => 'A11',
            'park_brand' => '',
            'park_type' => '',
            'start_time' => 0,
            'park_param' => '',
            'park_remarks' => '',
            'device_type' => 1,
            'passage_label' => '',
            'passage_area' => '238',
            'area_type' => 2,
            'last_heart_time' => 1669614986,
            'd7_channelId' => '',
        );
        $village_info=array (
            'village_name' => '小区演示-version',
        );
        $param = [
            'parkTime' => '2022112913',
            'car_number' => '京AF0236',
            'serialno' => '277c9082-75128b22',
        ];
        $data['AlarmInfoPlate']['result']['PlateResult']=['imageFragmentFile' => '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',
            'imageFragmentFileLen' => 26783,
            'isoffline' => 0,
            'license' => '京AF0236',
            'license_ext_type' => 0,];
        try{
            $list = $db_house_visitor_service->out_park($car_number,$data,$village_info,$passage_info,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }


    /**
     * 根据车库id查询车位列表
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function getPositionLists(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        $data['garage_id'] = $this->request->param('garage_id');
        $data['car_id'] = $this->request->param('car_id',0,'intval');
        $data['position_ids'] = $this->request->param('position_ids','');
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        if (empty($data['garage_id'])){
            return api_output(0,[]);
        }
        $db_house_visitor_service = new HouseNewParkingService();
        try{
            $list = $db_house_visitor_service->getPositionLists($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function getPositionBindCarList(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        $data['car_ids'] = $this->request->param('car_ids','');
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $db_house_visitor_service = new HouseNewParkingService();
        try{
            $list = $db_house_visitor_service->getPositionBindCarList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * 设置显屏配置内容
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function test(){
        $db_house_visitor_service = new HouseNewParkingService();
        try{
            $list = $db_house_visitor_service->test();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 查询车辆缴费列表
     * @author:zhubaodi
     * @date_time: 2022/5/17 10:46
     */
    public function getOrderList(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id',0);
        if (empty($data['uid'])||empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $list = $db_house_new_park_service->getOrderList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);

    }

    /**
     * 查询车辆缴费详情
     * @author:zhubaodi
     * @date_time: 2022/5/17 10:47
     */
    public function getOrderDetail(){
       $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id',0);
        $data['order_id'] = $this->request->param('order_id',0);
       /* $data['uid'] = 7356;
        $data['village_id']=1;
        $data['order_id'] =80371;*/
        if (empty($data['uid'])||empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        if (empty($data['order_id'])){
            return api_output_error(1002, '订单id不能为空');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $list = $db_house_new_park_service->getOrderDetail($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    //查询车主手机号和姓名
    public function getParkUserInfo(){
        $value = $this->request->param('value',0,'int');
        $type = $this->request->param('type','','trim');
        if (!$value || !$type){
            return api_output_error(1002, '缺少必要参数');
        }
        try{
            $list = (new HouseVillageUserBindService())->getUserBindInfos($value,$type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * 华夏T85一键同步白名单 HXT85
     */
    public function allAddHXWhiteList(){
        $village_id = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $parkDevInfoHandleService=  new \app\community\model\service\Park\ParkDevInfoHandleService();
        try{
            $res = $parkDevInfoHandleService->batchMonthCarToDev($village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * D3一键同步白名单
     * @author:zhubaodi
     * @date_time: 2022/6/25 14:23
     */
    public function allAddParkWhite(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->synAddParkWhite($data['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * D7一键同步车辆信息
     * @author:zhubaodi
     * @date_time: 2022/6/25 14:23
     */
    public function allAddWhiteList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->allAddCarD7($data['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function addParkInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['village_name'] = $this->adminUser['village_name'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['id'] = $this->request->param('id',0,'int');//设备id
        $data['car_number'] = $this->request->param('car_number','','trim');//车牌号
        $data['accessTime'] = $this->request->param('accessTime','','trim');//通行时间
        $data['park_time'] = $this->request->param('park_time','','trim');//停车时长(分钟)
        $data['user_name'] = $this->request->param('username','','trim');//用户姓名
        $data['user_phone'] = $this->request->param('phone','','trim');//用户手机号
        $data['price'] = $this->request->param('price','','trim');//停车费
        $data['img'] = $this->request->param('img','','trim');//图片

        if (empty($data['id'])){
            return api_output_error(1001, '通道id不能为空');
        }
        if (empty($data['car_number'])){
            return api_output_error(1001, '车牌号不能为空');
        }
        if (empty($data['accessTime'])){
            return api_output_error(1001, '通行时间不能为空');
        }
        if (!empty($data['park_time'])) {
            if ($data['park_time'] < 0) {
                return api_output_error(1001, '停车时长不能小于0');
            }
            if (floor($data['park_time']) != $data['park_time']) {
                return api_output_error(1001, '停车时长请输入正整数');
            }
        }else{
            $data['park_time']=0;
        }
        if (!empty($data['price'])) {
            if ($data['price'] < 0) {
                return api_output_error(1001, '停车费用不能小于0');
            }
            if (floor($data['price']) != $data['price']) {
                return api_output_error(1001, '停车费用请输入正整数');
            }
            $data['price']=floor($data['price']);
        }else{
            $data['price']=0;
        }
        $db_house_new_park_service = new HouseVillageParkingService();
        try{
            $data['park_time']=$data['park_time']*60; //转成秒
            $data['comfrom']='pc_admin';
            $res = $db_house_new_park_service->addParkInfo($data);
        }catch (\Exception $e){
            $errMsg=$e->getMessage();
            if($errMsg && strpos($errMsg,'Duplicate entry') && strpos($errMsg,'car_number_accessType_accessTime')){
                return api_output_error(1001, '此车此时间的记录已经存在了');
            }
            return api_output_error(-1,$errMsg);
        }
        return api_output(0,$res);
    }


    public function getD7ChannelList(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        $garage_id=$this->request->param('garage_id',0,'int');
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getD7ChannelList($data['village_id'],$garage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function getChildrenPositionList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['position_id'] = $this->request->param('position_id',0,'intval');
        if (empty($data['position_id'])){
            return api_output_error(1001, '母车位id不能为空');
        }
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $house_village_parking_service = new HouseVillageParkingService();
        try{
            $list = $house_village_parking_service->getChildrenPositionList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 解除子母车位的绑定关系
     * @author:zhubaodi
     * @date_time: 2022/8/1 8:31
     */
    public function unBindChildrenPosition(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['position_id'] = $this->request->param('position_id',0,'intval');
        if (empty($data['position_id'])){
            return api_output_error(1001, '车位id不能为空');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->unBindChildrenPosition($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 查询数据统计页面显示页签
     * @author:zhubaodi
     * @date_time: 2022/8/30 14:12
     */
    public function getRecordShow(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getRecordShow($data['village_id']);
            }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
            
    }

    /**
     * 绑定子母车位
     * @author:zhubaodi
     * @date_time: 2022/8/1 8:55
     */
    public function bindChildrenPosition(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['position_id'] = $this->request->param('position_id',0,'intval');
        $data['parent_position_id'] = $this->request->param('parent_position_id',0,'intval');
        if (empty($data['position_id'])){
            return api_output_error(1001, '车位id不能为空');
        }
        if (empty($data['parent_position_id'])){
            return api_output_error(1001, '母车位id不能为空');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->bindChildrenPosition($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function testD7(){
        $service_qinlin=new QinLinCloudService();
        $data=[];
        $data['parkId']=10023061;
        $data['orderNo']='1002306125505530857193881688';
        $data['outTradeNo']='100230611660975699';
        $data['tradeStatus']='SUCCESS';
        $data['totalAmount']='100';
        $data['payTime']=1660976463;
        $data['payType']=0;
        $data['payMethod']='CASH';
        $res = $service_qinlin->notifyBill($data);
        print_r($res);die;
    }


    public function testD71(){
        $service_qinlin=new QinLinCloudService();
        $data=[];
        $data['parkId']=10023061;
        $res = $service_qinlin->queryParkingBill(10023061,1,'1feff0dd27d34d44adb6869f98d61b6b');
        print_r($res);die;
    }

    public function getParentPositionList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['position_id'] = $this->request->param('position_id',0,'intval');
        if (empty($data['position_id'])){
            return api_output_error(1001, '车位id不能为空');
        }
        $data['position_num'] = $this->request->param('position_num','','trim');

        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getParentPositionList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,['list'=>$res]);
    }


    public function getParkDeviceSetting(){
        $id = (int) $this->request->param('id',0,'intval');
        try{
            $res =(new ParkPassageService())->getParkDeviceSetting($this->adminUser['village_id'],$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询正常的车库列表
     * @author:zhubaodi
     * @date_time: 2022/11/17 16:34
     */
    public function getGarageStatusList(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        $garage_id=$this->request->param('garage_id',0,'intval');
        $data['garage_id']=$garage_id;
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getGarageStatusList($data,$garage_id,true);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * A11停车卡类
     * @author:zhubaodi
     * @date_time: 2022/11/21 15:28
     */
    public function getA11CarType(){
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getA11CarType();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,['list'=>$res]);
    }

    /**
     * 查询A11收费标准
     * @author:zhubaodi
     * @date_time: 2022/11/21 15:28
     */
    public function getChargeCarType(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['type_id'] = $this->request->param('type_id',0,'intval');
        if (empty($data['type_id'])){
            return api_output_error(1001, '停车卡类不能为空');
        }
        $data['page'] = $this->request->param('page');//标签id
        $data['limit']=$this->request->param('pageSize',10,'intval');
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getChargeCarType($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加绑定A11停车卡类收费标准
     * @author:zhubaodi
     * @date_time: 2022/11/21 17:58
     */
    
    public function addChargeCarType(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['type_id'] = $this->request->param('type_id',0,'intval');
        if (empty($data['type_id'])){
            return api_output_error(1001, '停车卡类不能为空');
        }
        $data['rule_id'] = $this->request->param('rule_id',0,'intval');//收费标准id
        if (empty($data['rule_id'])){
            return api_output_error(1001, '收费标准不能为空');
        }
        $garage_id = $this->request->param('garage_id',0,'intval');//收费车库
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->addChargeCarType($data,$garage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 解除绑定A11停车卡类收费标准
     * @author:zhubaodi
     * @date_time: 2022/11/21 17:59
     */
    public function delChargeCarType(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['type_id'] = $this->request->param('type_id',0,'intval');
        if (empty($data['type_id'])){
            return api_output_error(1001, '停车卡类不能为空');
        }
        $data['rule_id'] = $this->request->param('rule_id',0,'intval');//收费标准id
        if (empty($data['rule_id'])){
            return api_output_error(1001, '收费标准不能为空');
        }
        $garage_id = $this->request->param('garage_id',0,'intval');//收费车库
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->delChargeCarType($data,$garage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res); 
    }

    /**
     * 修改车道状态
     * @author:zhubaodi
     * @date_time: 2022/12/1 9:29
     */
    public function editPassageStatus(){
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['id'] = $this->request->param('id',0,'intval');//车道id
        if (empty($data['id'])){
            return api_output_error(1001, '车道id不能为空');
        }
        $data['status'] = $this->request->param('status',0,'intval');//车道状态
        if (empty($data['status'])){
            return api_output_error(1001, '车道状态不能为空');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->editPassageStatus($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    
    public function editCouponStatus(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['id'] = $this->request->param('id',0,'intval');//优惠券id
        if (empty($data['id'])){
            return api_output_error(1001, '优惠券id不能为空');
        }
        $data['status'] = $this->request->param('status',0,'intval');//状态
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->editCouponStatus($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res); 
    }


    /**
     * 查询A11停车车牌类型
     * @author:zhubaodi
     * @date_time: 2022/12/13 10:00
     */
    public function getCarTypeA11(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['type_id'] = $this->request->param('type_id',0,'intval');
        if (empty($data['type_id'])){
            return api_output_error(1001, '停车卡类不能为空');
        }
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getCarTypeA11($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 添加绑定A11停车卡类收费标准
     * @author:zhubaodi
     * @date_time: 2022/11/21 17:58
     */

    public function addCarTypeA11(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['type_id'] = $this->request->param('type_id',0,'intval');
        if (empty($data['type_id'])){
            return api_output_error(1001, '停车卡类不能为空');
        }
        $data['car_number_type'] = $this->request->param('car_number_type',0,'intval');//车牌类型
        $garage_id = $this->request->param('garage_id',0,'intval');//收费车库
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->addCarTypeA11($data,$garage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 解除绑定A11停车卡类收费标准
     * @author:zhubaodi
     * @date_time: 2022/11/21 17:59
     */
    public function delCarTypeA11(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['type_id'] = $this->request->param('type_id',0,'intval');
        if (empty($data['type_id'])){
            return api_output_error(1001, '停车卡类不能为空');
        }
        $garage_id = $this->request->param('garage_id',0,'intval');//收费车库
        $data['car_number_type'] = $this->request->param('car_number_type',0,'intval');//车牌类型
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->delCarTypeA11($data,$garage_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询关联车道
     * @author:zhubaodi
     * @date_time: 2022/12/13 14:41
     */
    public function getPassageTypeList(){
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录');
        }
        $data['passage_direction'] = $this->request->param('passage_direction',0,'intval');
        $data['passage_type'] = $this->request->param('passage_type',0,'intval');//车牌类型
        $data['id'] = $this->request->param('id',0,'intval');//车道id
        $data['garage_id']= $this->request->param('garage_id',0,'intval');//车库id
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getPassageTypeList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    
    public function parkingPositionSet(){
        $data=array();
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output_error(1002, '请先登录');
        }
        $data['is_position_lease'] = $this->request->param('is_position_lease',0,'intval');
        $data['is_position_robbuy'] = $this->request->param('is_position_robbuy',0,'intval');
        $data['last_lease_day'] = $this->request->param('last_lease_day',0,'intval');
        $data['last_robbuy_day'] = $this->request->param('last_robbuy_day',0,'intval');
        $data['opt_position_num'] = $this->request->param('opt_position_num',0,'intval');
        $xtype = $this->request->param('xtype','','trim');
        $robbuy_protocol = $this->request->param('robbuy_protocol','','trim');
        if($xtype=='protocol'){
            $data=array();
            $data['robbuy_protocol']=$robbuy_protocol;
        }
        $data['update_time']=time();
        try{
            $houseVillagePositionSetService=new HouseVillagePositionSetService();
            $res = $houseVillagePositionSetService->savePositionSet($village_id,$data);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        
    }
    public function  getRobleasePositionOrderList(){

        $garage_id = $this->request->param('garage_id',0,'intval');
        $nickname = $this->request->param('nickname','','trim');
        $order_no = $this->request->param('order_no','','trim');
        $page = $this->request->param('page',1,'intval');
        $phone = $this->request->param('phone','','trim');
        $position_num = $this->request->param('position_num','','trim');
        $status = $this->request->param('status','','intval');
        
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output_error(1002, '请先登录');
        }
        $whereArr=array(['po.village_id','=',$village_id]);
        if(!empty($order_no)){
            $whereArr[]=array(['po.order_no','like','%'.$order_no.'%']);
        }
        if(!empty($nickname)){
            $whereArr[]=array(['u.nickname','like','%'.$nickname.'%']);
        }
        if(!empty($phone)){
            $whereArr[]=array(['u.phone','=',$phone]);
        }
        if(!empty($garage_id)){
            $whereArr[]=array(['po.garage_id','=',$garage_id]);
        }
        if(!empty($status) && $status>0){
            $status=$status-1;
            if($status<3){
                $whereArr[]=array(['po.status','=',$status]);
            }else{
                $whereArr[]=array(['po.status','in',array(3,4)]);
            }
        }
        $page=$page>0 ?$page:1;
        try{
            $houseVillageRobleasePositionOrderService=new HouseVillageRobleasePositionOrderService();
            if(!empty($position_num)){
                $whereTmpPosition=array(['village_id','=',$village_id],['position_num','=',$position_num]);
                $parkingPositionIds=$houseVillageRobleasePositionOrderService->getParkingPositionIds($whereTmpPosition,'position_id,garage_id');
                if($parkingPositionIds && is_array($parkingPositionIds)){
                    $whereArr[]=array(['po.position_id','in',$parkingPositionIds]);
                }
            }
            $res = $houseVillageRobleasePositionOrderService->getConditionListData($whereArr,'po.*,u.nickname,u.phone',$page,10);
            $site_url=cfg('site_url');
            $site_url=trim($site_url,'/');
            $res['protocolSetUrl']=$site_url.'/shequ.php?g=House&c=Unit&a=position_protocol_set&village_id='.$village_id;
            $configCustomizationService=new ConfigCustomizationService();
            $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
            $res['jz_wsxdsj_customized']=$isWsxCustomizedOpen ? 1:0;
            $res['position_set']='';
            if($isWsxCustomizedOpen){
                $houseVillagePositionSetService=new HouseVillagePositionSetService();
                $whereArr=array('village_id'=>$this->adminUser['village_id']);
                $positionSet = $houseVillagePositionSetService->getOneData($whereArr,'village_id,is_position_lease,last_lease_day,is_position_robbuy,last_robbuy_day,opt_position_num');
                if($positionSet){
                    $res['position_set']=$positionSet;
                }
            }
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function  handlePositionOrderStatus(){
        $id = $this->request->param('id',0,'intval');
        $opt_type = $this->request->param('opt_type',0,'intval');
        $order_no = $this->request->param('order_no',0,'trim');
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output_error(1002, '请先登录');
        }
        if($id<1 || !in_array($opt_type,[1,3]) || empty($order_no)){
            return api_output_error(1001, '参数错误，操作失败！');
        }
        try{
            $houseVillageRobleasePositionOrderService=new HouseVillageRobleasePositionOrderService();
            $whereArr=array();
            $whereArr[]=array('id','=',$id);
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('order_no','=',$order_no);
            $positionOrder = $houseVillageRobleasePositionOrderService->getOneData($whereArr);
            
            $save_data=array('status'=>$opt_type,'opt_time'=>time());
            $ret=$houseVillageRobleasePositionOrderService->saveOneData($whereArr,$save_data);
            if($opt_type==3){
                $releaseret = $houseVillageRobleasePositionOrderService->handleOrderPosition('release',$positionOrder['position_id'],$positionOrder['village_id']);
            }

            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function  getRobleasePositionOrderDetail(){
        $id = $this->request->param('id',0,'intval');
        $order_no = $this->request->param('order_no',0,'trim');
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output_error(1002, '请先登录');
        }
        if($id<1 || empty($order_no)){
            return api_output_error(1001, '参数错误！');
        }
        try{
            $houseVillageRobleasePositionOrderService=new HouseVillageRobleasePositionOrderService();
            $whereArr=array();
            $whereArr[]=array('id','=',$id);
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('order_no','=',$order_no);
            $ret=$houseVillageRobleasePositionOrderService->getPositionOrderDetail($whereArr,$village_id);
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    /**
     * 查询车位号绑定车辆数量
     */
    public function getPositionBindNum()
    {
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        $data['car_position_id'] = $this->request->param('car_position_id','');
        $db_house_new_park_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_park_service->getPositionBindNum($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    public function getSentryWatchList(){
        
        $village_id = $this->adminUser['village_id'];
        $wid = $this->request->post('wid',0,'intval');
        $start_time_str          = $this->request->post('start_time','','trim');
        $end_time_str          = $this->request->post('end_time','','trim');
        $page= $this->request->param('page');//标签id
        $page=$page>0 ? $page:1;
        $limit=$this->request->param('pageSize',10,'intval');
        try {
            $whereArr=array(['village_id','=',$village_id]);
            $whereArr[]=array('login_role','in',[5,6,401]);
            if($wid>0){
                $whereArr[]=array('wid','=',$wid);
            }
            if($start_time_str){
                $start_time=strtotime($start_time_str);
                if($start_time>0){
                    $whereArr[]=array('start_time','>=',$start_time);
                }
            }
            if($end_time_str){
                $end_time=strtotime($end_time_str);
                if($end_time>0){
                    $whereArr[]=array('end_time','<=',$end_time);
                }
            }
            $carWorkerRecordService= new HouseVillageCarWorkerRecordService();
            $sentryWatchList=$carWorkerRecordService->getSentryWatchList($whereArr,$page,$limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$sentryWatchList);
    }
}
