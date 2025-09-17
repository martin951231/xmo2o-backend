<?php


namespace app\community\controller\manage_api\v1;

use app\common\model\service\send_message\SmsService;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\Car\HouseVillageCarFlowService;
use app\community\model\service\Device\HikGateWayEventService;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseVillageParkingPayService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\PileOrderPayService;
use app\community\model\service\PileUserService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
class ParkingController extends BaseController
{
    public $car_color = array(
        array(
            'id' => 'A',
            'car_color' => '白色'
        ),
        array(
            'id' => 'B',
            'car_color' => '灰色'
        ),
        array(
            'id' => 'C',
            'car_color' => '黄色'
        ),
        array(
            'id' => 'D',
            'car_color' => '粉色'
        ),
        array(
            'id' => 'E',
            'car_color' => '红色'
        ),
        array(
            'id' => 'F',
            'car_color' => '紫色'
        ),
        array(
            'id' => 'G',
            'car_color' => '绿色'
        ),
        array(
            'id' => 'H',
            'car_color' => '蓝色'
        ),
        array(
            'id' => 'I',
            'car_color' => '棕色'
        ),
        array(
            'id' => 'J',
            'car_color' => '黑色'
        ),
        array(
            'id' => 'Z',
            'car_color' => '其他'
        ),

    );

    /**
     * 获取车库列表
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/16 14:17
     */
    public function getParkingGarage()
    {
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 0);
        $village_id = $this->request->post('village_id', 0);
        $garage_num = $this->request->post('garage_num', '');
        if (!$village_id)
            return api_output_error(1001, '必传参数缺失');
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];
        if (!empty($garage_num) || strlen($garage_num) > 0) {
            $where[] = ['garage_num', 'like', '%' . $garage_num . '%'];
        }
        $house_village_parking_garage = new HouseVillageParkingService();
        $lists = $house_village_parking_garage->getParkingGarageLists($where, 'garage_id,garage_num,garage_position,garage_remark', $page, $limit);
        return api_output(0, $lists, '获取成功');
    }

    /**
     * 获取车库详情
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/24
     */
    public function detailParkingGarage()
    {
        $garage_id = $this->request->post('garage_id', 0, 'int');
        if (!$garage_id)
            return api_output_error(1001, '必传参数缺失');
        $where['garage_id'] = $garage_id;
        $house_village_parking_garage = new HouseVillageParkingService();
        $res = $house_village_parking_garage->getParkingGarageByCondition($where);
        return api_output(0, $res, '获取成功');
    }


    /**
     * 编辑车库信息
     * @author lijie
     * @date_time 2020/07/16 14:31
     */
    public function editParkingGarage()
    {
        $garage_id = $this->request->post('garage_id', 0, 'int');
        $village_id = $this->request->post('village_id', 0);
        $post_params = $this->request->post();
        if (!$garage_id || empty($post_params['garage_num']) || empty($post_params['garage_position'])){
            return api_output_error(1001, '请将信息输入完整');
        }
        $position_count=$this->request->post('position_count', 0,'intval'); //车位总数
        $position_month_count=$this->request->post('position_month_count', 0,'intval'); //月租车位数
        $position_temp_count=$this->request->post('position_temp_count', 0,'intval'); //临时车位数
        $is_month_access=$this->request->post('is_month_access', 0,'intval'); //临时车是否进入  1是0否
        $is_month_charge=$this->request->post('is_month_charge', 0,'intval'); //临时车是否收费  1是0否
        $lat=$this->request->post('lat', '','trim'); //车库地址 纬度
        $lng=$this->request->post('lng', '','trim'); //车库地址 经度
        
        $where=array();
        if($village_id){
            $where['village_id']=$village_id;
        }
        
        $where['garage_id'] = $garage_id;
        $data['garage_num'] = $post_params['garage_num'];
        $data['garage_position'] = $post_params['garage_position'];
        $data['garage_remark'] = $post_params['garage_remark'];
        $data['is_month_access']=$is_month_charge;
        $data['is_month_charge']=$is_month_access;
        if($lat && $lng){
            $data['lat']=$lat;
            $data['lng']=$lng;
        }
        $house_village_parking_garage = new HouseVillageParkingService();
        if($position_count>0){
            if($position_month_count>$position_count){
                return api_output_error(1001, '车位总数不能小于月租车位总数');
            }
            $data['position_count']=$position_count;
            $data['position_month_count']=$position_month_count;
            $data['position_temp_count']=$position_count-$position_month_count;
            $whereArr=array();
            $whereArr[] = ['garage_id', '=', $garage_id];
            $whereArr[] = ['village_id', '=', $village_id];
            $parking_garage = $house_village_parking_garage->getParkingGarageByCondition($whereArr,'*');
            if ($parking_garage['detail'] && $parking_garage['detail']['position_count']>$position_count){
                return api_output_error(1001, '车位总数不能小于当前车位总数');
            }
        }
        $where_con=array();
        $where_con[] = ['garage_num', '=', $data['garage_num']];
        $where_con[] = ['garage_id', '<>', $garage_id];
        $where_con[] = ['village_id', '=', $village_id];
        $where_con[] = ['status', '=', 1];
        $rest = $house_village_parking_garage->getParkingGarageByCondition($where_con,'*');
        if ($rest['detail']){
            return api_output_error(1003, '该编号车库已存在');
        }
        $res = $house_village_parking_garage->editParkingGarage($where, $data);
        return api_output(0, [], '编辑成功');
    }

    /**
     * 添加车库
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/16 14:42
     */
    public function addParkingGarage()
    {
        $post_params = $this->request->post();
        if (empty($post_params['garage_num']) || empty($post_params['garage_position']) || empty($post_params['village_id']))
            return api_output_error(1001, '提示请将信息输入完整');
        $data['garage_num'] = $post_params['garage_num'];
        $data['garage_position'] = $post_params['garage_position'];
        $data['garage_remark'] = !empty($post_params['garage_remark']) ? $post_params['garage_remark'] : '';
        $data['village_id'] = $post_params['village_id'];
        $data['garage_addtime'] = time();
        $house_village_parking_garage = new HouseVillageParkingService();
        $res = $house_village_parking_garage->getParkingGarageByCondition(['garage_num' => $data['garage_num']]);
        if ($res['detail'])
            return api_output_error(1003, '该编号车库已存在');
        $res = $house_village_parking_garage->addParkingGarage($data);
        if ($res)
            return api_output(0, '', '添加成功');
        else
            return api_output_error(1003, '服务异常');
    }

    /**
     * 删除车库
     * @author lijie
     * @date_time 2020/07/16 14:34
     */
    public function delParkingGarage()
    {
        $garage_id = $this->request->post('garage_id', 0, 'int');
        if (!$garage_id) {
            return api_output_error(1001, '必传参数缺失');
        }
        $where['garage_id'] = $garage_id;
        $house_village_parking_garage = new HouseVillageParkingService();
        $data = $house_village_parking_garage->getParkingPositionLists(['pp.garage_id' => $garage_id], 'pp.garage_id,pp.position_id');
        if (!empty($data)) {
            return api_output_error(1001, '该车库有车位');
        }
        $res = $house_village_parking_garage->delParkingGarage($where);
        if ($res) {
            return api_output(0, [], '删除成功');
        } else {
            return api_output_error(1003, '服务异常');
        }
    }

    /**
     * 获取车位列表
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/17 10:00
     */
    public function getParkingPosition()
    {
        $page = $this->request->post('page', 1);
        $limit = trim($this->request->post('limit', 0));
        $village_id = $this->request->post('village_id', 0);
        $position_num = $this->request->post('position_num', '');
        $garage_id = $this->request->post('garage_id', 0,'intval');
        if (!$village_id)
            return api_output_error(1001, '必传参数缺失');
        $house_village_parking_position = new HouseVillageParkingService();
        $where=array();
        $where[] = ['pp.village_id', '=', $village_id];
        if($garage_id>0){
            $where[] = ['pp.garage_id', '=', $garage_id];
        }
        if (!empty($position_num) || strlen($position_num) > 0) {
            $where[] = ['pp.position_num', 'like', '%' . $position_num . '%'];
        }
        $field = 'pp.children_type,pp.parent_position_id,pp.position_id,pp.position_num,pp.position_area,pp.position_note,pp.position_status,pp.garage_id';
        $data = $house_village_parking_position->getParkingPositionLists($where, $field, $page, $limit);
        return api_output(0, $data, '获取成功');
    }

    /**
     * 获取车位详情
     * @return \json
     * @throws \think\Exception
     * @author lijie
     * @date_time 2020/07/17 10:33
     */
    public function parkingPositionDetail()
    {
        $position_id = $this->request->post('position_id', 0);
        $app_type = $this->request->post('app_type', '');
        if (!$position_id)
            return api_output_error(1001, '必传参数缺失');
        $house_village_parking_position = new HouseVillageParkingService();
        $where['pp.position_id'] = $position_id;
        $field = 'pp.children_type,pp.position_num,pp.position_area,pp.position_note,pg.garage_num,pp.position_id,pg.garage_id,ub.name,ub.phone';
        $data = $house_village_parking_position->getParkingPositionDetail($where, $field);
        return api_output(0, $data, '获取成功');
    }

    /**
     * 编辑车位
     * @return \json
     * @author lijie
     * @date_time 2020/07/17 13:42
     */
    public function parkingPositionEdit()
    {
        $position_id = $this->request->post('position_id', 0);
        $pigcms_id = $this->request->post('pigcms_id', 0);
        $village_id = $this->request->post('village_id', 0);
        $post_params = $this->request->post();
        if (!$position_id || empty($post_params['position_num']) || empty($post_params['garage_id']))
            return api_output_error(1001, '必传参数缺失');
        $where['position_id'] = $position_id;
        $data['position_num'] = $post_params['position_num'];
        if(!empty($post_params['position_area'])){
            $data['position_area'] = $post_params['position_area'];
        }
        $data['garage_id'] = $post_params['garage_id'];
        $data['position_id'] = $position_id;
        $data['position_note'] = $post_params['position_note'];
        if (!empty($post_params['children_type'])){
            $data['children_type'] =$post_params['children_type'];//字母车位  1母车位 2子车位
        }
        if(empty($village_id)){
            return api_output_error(1001, '必传参数缺失');
        }
        $data['village_id'] = $village_id;
        $house_village_parking_position = new HouseVillageParkingService();
        $where_con[] = ['pp.position_num', '=', $post_params['position_num']];
        $where_con[] = ['pp.position_id', '<>', $position_id];
        $where_con[] = ['pp.garage_id', '=',  $post_params['garage_id']];
        $where_con[] = ['pp.village_id', '=', $village_id];
        $res = $house_village_parking_position->getParkingPositionByCondition($where_con, 'pp.position_id');
        if ($res){
            return api_output_error(1003, '该编号车位已存在');
        }
        try {
            $res = $house_village_parking_position->editParkingPosition($where, $data);
            if ($pigcms_id && $village_id) {
                $data = $house_village_parking_position->getBindPosition(['position_id' => $position_id, 'user_id' => $pigcms_id, 'village_id' => $village_id]);
                if (empty($data)) {
                    $house_village_parking_position->delBindPosition(['position_id' => $position_id]);
                    $res = $house_village_parking_position->addBindPosition(['position_id' => $position_id, 'user_id' => $pigcms_id, 'village_id' => $village_id]);
                }
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], '编辑成功');
    }

    /**
     * 添加车位
     * @return \json
     * @author lijie
     * @date_time 2020/07/17 13:45
     */
    public function parkingPositionAdd()
    {
        $post_params = $this->request->post();
        if (empty($post_params['position_num']) || empty($post_params['position_area']) || empty($post_params['garage_id']) || empty($post_params['village_id']))
            return api_output_error(1001, '必传参数缺失');
        $house_village_parking_position = new HouseVillageParkingService();
        $data['position_num'] = $post_params['position_num'];
        $data['position_area'] = $post_params['position_area'];
        $data['garage_id'] = $post_params['garage_id'];
        $data['position_note'] = $post_params['position_note'];
        $data['village_id'] = $post_params['village_id'];
        if (!empty($post_params['children_type'])){
            $data['children_type'] =$post_params['children_type'];//字母车位  1母车位 2子车位
        }
        /*$data['start_time'] = $post_params['start_time'];
        $data['end_time'] = $post_params['end_time'];*/
        $where['pp.position_num'] = $post_params['position_num'];
        $where['pp.village_id'] = $post_params['village_id'];
        $res = $house_village_parking_position->getParkingPositionByCondition($where, 'pp.position_id');
        if ($res){
            return api_output_error(1003, '该编号车位已存在');
        }
        try {
            $position_id = $house_village_parking_position->addParkingPosition($data);
            if ($position_id) {
                $res = $house_village_parking_position->addBindPosition(['position_id' => $position_id, 'user_id' => $post_params['pigcms_id'], 'village_id' => $post_params['village_id']]);
                return api_output(0, [], '添加成功');
            } else {
                return api_output_error(1003, '服务异常');
            }
        } catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        } 

    }

    /**
     * 删除车位
     * @return \json
     * @throws \Exception
     * @author lijie
     * @date_time 2020/07/17 13:48
     */
    public function parkingPositionDel()
    {
        $position_id = $this->request->post('position_id', 0);
        if (!$position_id)
            return api_output_error(1001, '必传参数缺失');
        $house_village_parking_position = new HouseVillageParkingService();
        $where['position_id'] = $position_id;
        $res = $house_village_parking_position->delParkingPosition($where);
        if ($res)
            return api_output(0, [], '删除成功');
        else
            return api_output_error(1003, '服务异常');
    }
    /**
     * 获取车辆列表
     * @return \json|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/074/18 9:47
     */
    public function getParkingCar()
    {
        $village_id = $this->request->post('village_id', 0);
        if (!$village_id){
            return api_output_error(1001, '必传参数缺失');
        }
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 15);
        $con = $this->request->post('con', '');
        $where[] = ['village_id', '=', $village_id];
        if (!empty($con) || strlen($con) > 0) {
            $province = mb_substr($con, 0, 1);
            $car_no = mb_substr($con, 1);
            
            $where[] = ['car_number|car_user_name|car_user_phone', 'like', '%' . $car_no . '%'];
        }
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(array('village_id'=>$village_id),'*');
        $field = 'car_id,province,car_number,car_stop_num,car_user_name,car_user_phone,end_time';
        $house_village_parking_car = new HouseVillageParkingService();
        $data = $house_village_parking_car->getParkingCarLists($where, $field, $page, $limit, 'car_id DESC');
        if(!empty($data) && !$data->isEmpty()){
            $data=$data->toArray();
            foreach ($data as &$v){
                if(!empty($v['province']) && !strpos($v['car_number'], $v['province'])){
                    $v['car_number']=$v['province'].$v['car_number'];
                }

                if($v['end_time']>100){
                    $v['end_time']=date('Y-m-d H:i:s',$v['end_time']);
                }else{
                    $v['end_time']='';
                }
                if(isset($v['car_user_phone']) && !empty($v['car_user_phone'])){
                    $v['car_user_phone']=$service_house_village->phoneDesensitizationV($v['car_user_phone'],$village_id,$village_info);
                }else{
                    $v['car_user_phone']='';
                }
            }
            unset($v);
        }
        return api_output(0, $data, '获取成功');
    }

    /**
     * 编辑车辆信息
     * @return \json
     * @author lijie
     * @date_time 2020/07/18 9:57
     */
    public function editParkingCar()
    {
        $post_params = $this->request->post();
        if (empty($post_params['car_id']) || empty($post_params['car_position_id']) || empty($post_params['car_number']) || empty($post_params['car_color']) ){
            return api_output_error(1001, '必传参数缺失'); 
        }
        $where=array();
        $where['car_id'] = $post_params['car_id'];
        if(empty($post_params['village_id'])){
            return api_output_error(1001, '缺少小区的ID参数');
        }
        $village_id=$post_params['village_id'];
        $where['village_id']=$village_id;
        $data=array();
        $data['car_position_id'] = $post_params['car_position_id'];
        $data['car_number'] = $post_params['car_number'] ? trim($post_params['car_number']):'';
        $data['car_user_name'] = isset($post_params['car_user_name']) ? $post_params['car_user_name']:'';
        $data['car_user_phone'] = isset($post_params['car_user_phone']) ? $post_params['car_user_phone']:'';
        $data['car_color'] = $post_params['car_color'];
        $data['equipment_no'] = $post_params['equipment_no'];
        $data['car_stop_num'] = $post_params['car_stop_num'] ? $post_params['car_stop_num']:'';
        $data['car_brands'] = $post_params['car_brands'];
        $relationship =0;//与车主关系
        if(isset($post_params['relationship'])){
            $relationship=intval($post_params['relationship']);
        }
        if(empty($data['car_number'])){
            return api_output_error(1001, '车牌不能为空！');
        }
        $pigcms_id=0; //业主身份id
        if(isset($post_params['pigcms_id'])){
            $pigcms_id=intval($post_params['pigcms_id']);
        }
        $room_id=0;
        if(isset($post_params['room_id'])){
            $data['room_id']=!empty($post_params['room_id']) ? intval($post_params['room_id']):0;
            $room_id=$data['room_id'];
        }
        $binding_type=0;
        $province = mb_substr($data['car_number'], 0, 1);
        if( preg_match('/[\x{4e00}-\x{9fa5}]/iu', $province) ){
            $data['car_number']= mb_substr($data['car_number'], 1);
            $data['province']= $province;
        }
        if(isset($post_params['binding_type'])){
            $data['binding_type'] = intval($post_params['binding_type']); //绑定对象 0公共 1房间 2业主
            $binding_type=$data['binding_type'];
            if($data['binding_type']!=2){
                if(empty($post_params['car_user_name'])){
                    return api_output_error(1001, '请填写车主姓名');
                }
                if(empty($post_params['car_user_phone'])){
                    return api_output_error(1001, '请填写车主手机号');
                }
            }
        }
        $house_village_parking_car = new HouseVillageParkingService();
        $res = $house_village_parking_car->editParkingCar($where, $data);
        if($binding_type>0){
            $pdata=array('pigcms_id'=>$pigcms_id,'room_id'=>$room_id,'relationship'=>$relationship,'car_id'=>$post_params['car_id']);
            $houseNewParkingService =new HouseNewParkingService();
            $houseNewParkingService->saveCarOtherInfo($village_id,$binding_type,$pdata);
//            $house_village_parking_car->saveCarOtherInfo($village_id,$binding_type,$pdata);
        }
        $house_village_parking_car->editParkingPosition(['position_id' => $post_params['car_position_id']], ['position_status' => 2]);
        return api_output(0, [], '编辑成功');
    }

    /**
     * 添加车辆
     * @return \json
     * @author lijie
     * @date_time 2020/07/18 10:00
     */
    public function addParkingCar()
    {
        $post_params = $this->request->post();
        if (empty($post_params['village_id']) || empty($post_params['car_position_id']) || empty($post_params['car_number'])){
            return api_output_error(1001, '必传参数缺失');
        }
        $data['village_id'] = $post_params['village_id'];
        $village_id=$data['village_id'];
        $data['car_position_id'] = $post_params['car_position_id'];
        $province = mb_substr($post_params['car_number'], 0, 1);
        $car_no = mb_substr($post_params['car_number'], 1);
        $data['province'] =$province ;
        $data['car_number'] = $car_no;
        $data['car_user_name'] = isset($post_params['car_user_name']) ? $post_params['car_user_name']:'';
        $data['car_user_phone'] = isset($post_params['car_user_phone']) ? $post_params['car_user_phone']:'';
        $data['car_color'] = $post_params['car_color'];
        $data['equipment_no'] = $post_params['equipment_no'];
        $data['car_stop_num'] = $post_params['car_stop_num'] ? $post_params['car_stop_num']:'';
        $data['car_brands'] = $post_params['car_brands'];
        $relationship =0;//与车主关系
        if(isset($post_params['relationship'])){
            $relationship=intval($post_params['relationship']);
        }
        $pigcms_id=0; //业主身份id
        if(isset($post_params['pigcms_id'])){
            $pigcms_id=intval($post_params['pigcms_id']);
        }
        $room_id=0;
        if(isset($post_params['room_id'])){
            $data['room_id']=!empty($post_params['room_id']) ? intval($post_params['room_id']):0;
            $room_id=$data['room_id'];
        }
        $binding_type=0;
        if(isset($post_params['binding_type'])){
            $data['binding_type'] = intval($post_params['binding_type']); //绑定对象 0公共 1房间 2业主
            $binding_type=$data['binding_type'];
            if($data['binding_type']!=2){
                if(empty($post_params['car_user_name'])){
                    return api_output_error(1001, '请填写车主姓名');
                }
                if(empty($post_params['car_user_phone'])){
                    return api_output_error(1001, '请填写车主手机号');
                }
            }
        }
        
        $house_village_parking_car = new HouseVillageParkingService();
        $res = $house_village_parking_car->addParkingCar($data);
        $house_village_parking_car->editParkingPosition(['position_id' => $post_params['car_position_id']], ['position_status' => 2]);
        if ($res){
            if($binding_type>0){
                $pdata=array('pigcms_id'=>$pigcms_id,'room_id'=>$room_id,'relationship'=>$relationship,'car_id'=>$res);
                $houseNewParkingService = new HouseNewParkingService();
                $houseNewParkingService->saveCarOtherInfo($village_id,$binding_type,$pdata);
//                $house_village_parking_car->saveCarOtherInfo($village_id,$binding_type,$pdata);
            }
            return api_output(0, [], '添加成功');
        } else{
            return api_output_error(1003, '服务异常');
        }
            
    }

    /**
     * 获取车辆详情
     * @return \json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/18 10:06
     */
    public function parkingCarDetail()
    {
        $car_id = $this->request->post('car_id', 0, 'int');
        if (!$car_id)
            return api_output_error(1001, '必传参数缺失');
        $where['pc.car_id'] = $car_id;
        $field = 'pp.position_num,pc.end_time,pp.garage_id,pp.village_id,pc.province,pc.car_number,pc.car_stop_num,pc.car_user_phone,pc.car_user_name,pc.car_color,pc.equipment_no,pc.car_id,pc.car_position_id,pc.car_brands';
        $house_village_parking_car = new HouseVillageParkingService();
        $data = $house_village_parking_car->getParkingCarDetail($where, $field);
        return api_output(0, $data, '获取成功');
    }

    public function parkingCarDel()
    {
        $car_id = $this->request->post('car_id', 0, 'int');
        if (!$car_id)
            return api_output_error(1001, '必传参数缺失');
        $house_village_parking_car = new HouseVillageParkingService();
        $field = 'pp.position_num,pp.garage_id,pp.village_id,pc.car_number,pc.car_stop_num,pc.car_user_phone,pc.car_user_name,pc.car_color,pc.equipment_no,pc.car_id,pc.car_position_id';
        $data = $house_village_parking_car->getParkingCarDetail(['pc.car_id' => $car_id], $field);
        $where['car_id'] = $car_id;
        $res = $house_village_parking_car->delParkingCar($where);
        if ($res) {
            $count = $house_village_parking_car->getCarNum(['car_position_id' => $data['detail']['car_position_id']]);
            if ($count == 0) {
                $house_village_parking_car->editParkingPosition(['position_id' => $data['detail']['car_position_id']], ['position_status' => 1]);
            }
            return api_output(0, [], '删除成功');
        } else {
            return api_output_error(1003, '服务异常');
        }
    }

    /**
     * 车辆颜色
     * @return \json
     * @author lijie
     * @date_time 2020/11/13
     */
    public function carColor()
    {
        $color_list = $this->car_color;
        return api_output(0, $color_list);
    }

    /**
     * 车辆品牌
     * @return \json
     * @author lijie
     * @date_time 2020/11/13
     */
    public function carBrands()
    {
        $house_village_parking_car = new HouseVillageParkingService();
        $list = $house_village_parking_car->getCarBrands();
        return api_output(0, $list, '获取成功');
    }


    /**
     * 定制停车-获取停车场列表
     * @author:zhubaodi
     * @date_time: 2021/8/21 13:36
     */
    public function getParkingList()
    {
        $data=array();
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', 0, 'intval');
        $data['park_name'] = $this->request->param('park_name', '', 'trim');
        $data['lat'] = $this->request->param('lat', '', 'trim');
        $data['lng'] = $this->request->param('lng', '', 'trim');
        $data['page'] = $this->request->param('page', 0, 'intval');
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $list = $house_village_parking_car->getParkingList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }


    /**
     * 定制停车-获取缴费纪录
     * @author:zhubaodi
     * @date_time: 2021/8/21 13:36
     */
    public function getInParkingList()
    {
        $uid = $this->request->log_uid;
        // $uid=112358769;
        $village_id = $this->request->param('village_id', 0, 'intval');
        $page = $this->request->param('page', 0, 'intval');
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $list = $house_village_parking_car->getInParkingList($uid,$village_id, $page);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['list' => $list]);
    }


    /**
     * Notes:对应手机号发送验证码
     * @return \json
     * @author: zhubaodi
     * @date_time: 2021/11/3 15:24
     */
    public function sendCode()
    {
        //临时车登记
        $from = 'park_temp';
        $param = $this->request->param();
        try {
            $result = (new SmsService())->sendTelSms($param, $from);
            return api_output(0, $result, '发送成功');
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }


    /**
     * 定制停车-获取停车纪录
     * @author:zhubaodi
     * @date_time: 2021/8/21 13:36
     */
    public function add_visitor_list()
    {
        $data['uid'] = $this->request->log_uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['username'] = $this->request->param('username', '', 'trim');
        $data['phone'] = $this->request->param('phone', '', 'trim');
        $data['car_id'] = $this->request->param('car_number', '', 'trim');
        $data['code'] = $this->request->param('code', '', 'trim');
        $data['channel_id'] = $this->request->param('channel_id', '', 'trim');
        $data['is_visit_owner'] = $this->request->param('is_visit_owner', '0', 'intval');
        $data['visit_owner_switch'] = $this->request->param('visit_owner_switch');
        $data['vacancy_id'] = $this->request->param('vacancy_id', '0', 'intval');
        $data['layer_id'] = $this->request->param('layer_id', '0', 'intval');
        $data['floor_id'] = $this->request->param('floor_id', '0', 'intval');
        $data['single_id'] = $this->request->param('single_id', '0', 'intval');
        
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->add_visitor_list($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);
    }


    /**
     * 定制停车-获取停车纪录
     * @author:zhubaodi
     * @date_time: 2021/8/21 13:36
     */
    public function SetPushAddress()
    {
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->SetPushAddress();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);
    }

    /**
     * 定制停车-获取停车收费规则
     * @author:zhubaodi
     * @date_time: 2021/8/21 13:36
     */
    public function getParkChargeRule()
    {
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->getParkChargeRule();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);
    }

    /**
     * 定制停车-临时车计算停车费
     * @author:zhubaodi
     * @date_time: 2021/11/3 16:09
     */
    public function get_temp_pay()
    {
        $data['uid'] = $this->request->log_uid;
       //  $data['uid']=112358769;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['car_number'] = $this->request->param('car_number', '', 'trim');
        if (empty($data['car_number'])) {
            return api_output_error(1001, '请输入车牌号');
        }
        if (strpos($data['car_number'],'-')!=false){
            $count=strpos($data['car_number'],'-');
            $data['car_number']=substr_replace($data['car_number'],"",$count,1);
        }
        try {
            $house_village_parking_car = new HouseVillageParkingService();
            $res = $house_village_parking_car->get_park_money($data);
        } catch (\Exception $e) {
            if ($e->getMessage()=='没有停车纪录'){
                return api_output(0, ['list'=>[]],$e->getMessage());
            }else{
                return api_output_error(-1, $e->getMessage());
            }


        }
        return api_output(0,['list'=>$res]);
    }

    /**
     * 定制停车-临时车出场缴费
     * @author:zhubaodi
     * @date_time: 2021/4/11 10:49
     */
    public function orderPay()
    {
        $data['uid'] = $this->request->log_uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['type'] = $this->request->param('type', '0', 'intval');//1临时车出场缴费  2购买月卡
        $data['car_number'] = $this->request->param('car_number', '', 'trim');
        if (empty($data['car_number'])) {
            return api_output_error(1001, '请上传车牌号');
        }
        if (strpos($data['car_number'],'-')!=false){
            $count=strpos($data['car_number'],'-');
            $data['car_number']=substr_replace($data['car_number'],"",$count,1);
        }
        $data['money'] = $this->request->param('money', '', 'trim');
        $serviceHouseMeter = new HouseVillageParkingPayService();
        $order_id = $serviceHouseMeter->addOrderInfo($data);
        $link = get_base_url('pages/pay/check?order_type=park&order_id=' . $order_id);


        return api_output(0, $link);
    }


    /**
     * 临时车缴费页面
     * @author:zhubaodi
     * @date_time: 2021/11/11 15:06
     */
    public function temp_payment(){
        $data['uid'] = $this->request->log_uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['pigcms_id'] = $this->request->param('pigcms_id', '0', 'intval');
        try {
            $houseVillageUserBindService=new HouseVillageUserBindService();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$data['village_id']);
            $whereArr[]=array('uid','=',$data['uid']);
            $whereArr[]=array('vacancy_id','>',0);
            $whereArr[]=array('status','=',1);
            $new_pigcms_id=$houseVillageUserBindService->getCheckUserBindInfo($whereArr,$data['pigcms_id']);
            if($new_pigcms_id && $new_pigcms_id!=$data['pigcms_id']){
                $data['new_pigcms_id']=$new_pigcms_id;
            }
            $house_village_parking_car = new HouseVillageParkingService();
            $res = $house_village_parking_car->get_temp_payment_info($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 返回移动端停车模块底部导航
     * @author: liukezhu
     * @date : 2022/1/15
     * @return \json
     */
    public function getParkNav(){
        $village_id = $this->request->param('village_id', '0', 'intval');
        try {
            $house_village_parking_car = new HouseVillageParkingService();
            $res = $house_village_parking_car->getParkNav($village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 获取车标列表
     * @author:zhubaodi
     * @date_time: 2021/11/11 13:23
     */
    public function get_car_logo_list(){
        try {
            $house_village_parking_car = new HouseVillageParkingService();
            $res = $house_village_parking_car->get_car_logo_list();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 获取用户历史登记记录
     * @author:zhubaodi
     * @date_time: 2021/11/12 11:34
     */
    public function get_visitor_list(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $list = $house_village_parking_car->get_visitor_list($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }


    /**
     * 添加车辆绑定
     * @author:zhubaodi
     * @date_time: 2021/11/12 13:21
     */
    public function add_bind_car(){
        $data=array();
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['pigcms_id'] = $this->request->param('pigcms_id', '0', 'intval');//身份id
       // $data['province'] = $this->request->param('province', '', 'trim');//车牌（省份）
        $data['car_number'] = $this->request->param('car_number', '', 'trim');//车牌号（填写部分）
        $data['relationship'] = $this->request->param('relationship', '', 'intval');//与车主关系
        $data['brands'] = $this->request->param('brands', '', 'trim');//车辆品牌
        $data['car_type'] = $this->request->param('car_type', '', 'trim');//车辆型号
        $data['car_color'] = $this->request->param('car_color', '', 'trim');//车辆颜色
        $data['equipment_no'] = $this->request->param('equipment_no', '', 'trim');//车辆设备号
        $data['car_user_name'] = $this->request->param('car_user_name', '', 'trim');//车主姓名
        $data['car_user_phone'] = $this->request->param('car_user_phone', '', 'trim');//车主手机号
        $is_public_car = $this->request->param('is_public_car',0,'intval'); //大众的车
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['village_id'])){
            return api_output_error(1001,'小区id不能为空');
        }
        if (empty($data['car_number'])){
            return api_output_error(1001,'车牌号不能为空');
        }
        if(iconv_strlen($data['car_number'],"UTF-8") < 7){
            return api_output_error(1001,'请输入完整车辆号码');
        }
        if ($is_public_car <1 && empty($data['relationship'])){
            return api_output_error(1001,'与车主关系不能为空');
        }
        if($is_public_car==1){
            $data['relationship']=7;
        }
        if ($data['relationship']!=1){
            if (empty($data['car_user_name'])){
                return api_output_error(1001,'车主姓名不能为空');
            }
            if (empty($data['car_user_phone'])){
                return api_output_error(1001,'车主手机号不能为空');
            }
        }
        if (!empty($data['equipment_no'])&&preg_match('/[^A-Za-z0-9]/', $data['equipment_no'])){
            return api_output_error(1001,'车辆设备号只能允许字母或者数字，请正确输入');
        }
        if (strpos($data['car_number'],'-')!=false){
            $count=strpos($data['car_number'],'-');
            $data['car_number']=substr_replace($data['car_number'],"",$count,1);
        }
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->add_bind_car($data,$is_public_car);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);

    }

    /**
     * 车辆绑定页面
     * @author:zhubaodi
     * @date_time: 2021/11/12 13:21
     */
    public function bind_car(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $house_village_parking_car = new HouseVillageParkingService();
        $list = $house_village_parking_car->get_bind_car($data);
        return api_output(0, $list);
    }

    /**
     * 设置默认车辆
     * @author:zhubaodi
     * @date_time: 2021/11/12 16:04
     */
    public function set_car_default(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['car_number'] = $this->request->param('car_number', '', 'trim');
        $data['car_id'] = $this->request->param('car_id', '', 'intval');
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['village_id'])){
            return api_output_error(1001,'小区id不能为空');
        }
        if (empty($data['car_number'])){
            return api_output_error(1001,'车牌号不能为空');
        }
        if (strpos($data['car_number'],'-')!=false){
            $count=strpos($data['car_number'],'-');
            $data['car_number']=substr_replace($data['car_number'],"",$count,1);
        }
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->set_car_default($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);
    }

    /**
     * 查询绑定车辆详情
     * @author:zhubaodi
     * @date_time: 2021/11/12 16:04
     */
    public function get_bind_car_info(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['car_id'] = $this->request->param('car_id', '', 'trim');
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['car_id'])){
            return api_output_error(1001,'车辆id不能为空');
        }
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->get_bind_car_info($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);
    }
    public function get_car_bind_position_list(){
        $data=array();
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['car_id'] = $this->request->param('car_id', '', 'trim');
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['car_id'])){
            return api_output_error(1001,'车辆id不能为空');
        }
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $listData = $house_village_parking_car->get_car_bind_position_list($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $listData);
    }

    /**
     * 编辑车辆绑定
     * @author:zhubaodi
     * @date_time: 2021/11/12 13:21
     */
    public function edit_bind_car(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['car_id'] = $this->request->param('car_id', '0', 'intval');
        $data['pigcms_id'] = $this->request->param('pigcms_id', '0', 'intval');//房间id
       // $data['province'] = $this->request->param('province', '', 'trim');//车牌（省份）
        $data['car_number'] = $this->request->param('car_number', '', 'trim');//车牌号（填写部分）
        $data['relationship'] = $this->request->param('relationship', '', 'intval');//与车主关系
        $data['brands'] = $this->request->param('brands', '', 'trim');//车辆品牌
        $data['car_type'] = $this->request->param('car_type', '', 'trim');//车辆型号
        $data['car_color'] = $this->request->param('car_color', '', 'trim');//车辆颜色
        $data['equipment_no'] = $this->request->param('equipment_no', '', 'trim');//车辆设备号
        $data['car_user_name'] = $this->request->param('car_user_name', '', 'trim');//车主姓名
        $data['car_user_phone'] = $this->request->param('car_user_phone', '', 'trim');//车主手机号
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['village_id'])){
            return api_output_error(1001,'小区id不能为空');
        }
        if (empty($data['car_number'])){
            return api_output_error(1001,'车牌号不能为空');
        }
        if (empty($data['car_id'])){
            return api_output_error(1001,'车辆id不能为空');
        }
      /*  if (empty($data['province'])){
            return api_output_error(1001,'车牌号省份不能为空');
        }*/
        if (empty($data['relationship'])){
            return api_output_error(1001,'与车主关系不能为空');
        }
        if ($data['relationship']!=1){
            if (empty($data['car_user_name'])){
                return api_output_error(1001,'车主姓名不能为空');
            }
            if (empty($data['car_user_phone'])){
                return api_output_error(1001,'车主手机号不能为空');
            }
        }
        if (!empty($data['equipment_no'])&&preg_match('/[^A-Za-z0-9]/', $data['equipment_no'])){
            return api_output_error(1001,'车辆设备号只能允许字母或者数字，请正确输入');
        }
        if (strpos($data['car_number'],'-')!=false){
            $count=strpos($data['car_number'],'-');
            $data['car_number']=substr_replace($data['car_number'],"",$count,1);
        }
        $house_village_parking_car = new HouseVillageParkingService();
        $msg='已提交绑定车辆信息，等待工作人员审核';
        try {
            $whereArr=array('car_id'=>$data['car_id']);
            $parking_car=$house_village_parking_car->get_parking_car_info($whereArr);
            if(!empty($parking_car) && $parking_car['examine_status']==1){
                $msg='编辑成功';
            }
            $id = $house_village_parking_car->edit_bind_car($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $msg);

    }


    /**
     * 设置默认车辆
     * @author:zhubaodi
     * @date_time: 2021/11/12 16:04
     */
    public function del_bind_car(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['id'] = $this->request->param('id', '0', 'intval');
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['village_id'])){
            return api_output_error(1001,'小区id不能为空');
        }
        if (empty($data['id'])){
            return api_output_error(1001,'绑定不能为空');
        }
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->del_bind_car($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);
    }


    /**
     * 获取月卡列表
     * @author:zhubaodi
     * @date_time: 2021/11/12 19:58
     */
    public function get_mouth_list(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['car_number'] = $this->request->param('car_number', '', 'trim');
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['village_id'])){
            return api_output_error(1001,'小区id不能为空');
        }
        if (strpos($data['car_number'],'-')!=false){
            $count=strpos($data['car_number'],'-');
            $data['car_number']=substr_replace($data['car_number'],"",$count,1);
        }
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $res = $house_village_parking_car->get_mouth_list($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 车辆储值页面
     * @author:zhubaodi
     * @date_time: 2021/11/12 19:58
     */
    public function payment_car(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['car_number'] = $this->request->param('car_number', '', 'trim');
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['village_id'])){
            return api_output_error(1001,'小区id不能为空');
        }
        if (strpos($data['car_number'],'-')!=false){
            $count=strpos($data['car_number'],'-');
            $data['car_number']=substr_replace($data['car_number'],"",$count,1);
        }
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $res = $house_village_parking_car->payment_car($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }


    public function add_visitor_id(){
       // $data['uid'] = $this->request->log_uid;
        $data['id'] = $this->request->param('id', '0', 'intval');
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->add_visitor_id($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);
    }

    public function add_nolicence(){
        $uid = $this->request->log_uid;
        $village_id = $this->request->param('village_id', '0', 'intval');
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $car_number = $house_village_parking_car->add_nolicence($village_id,$uid);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $car_number);
    }

    public function getParkSet(){
        $village_id = $this->request->param('village_id', '0', 'intval');
        $garage_id = $this->request->param('garage_id', '0', 'intval');
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $car_number = $house_village_parking_car->getParkSet($village_id,$garage_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $car_number);
    }

    /**
     * 临时车登记页面获取相关信息
     * @author:zhubaodi
     * @date_time: 2022/4/19 9:17
     */
    public function getCarInfo(){
        $data['uid'] = $this->request->log_uid;
        $data['village_id'] = $this->request->param('village_id', '0', 'intval');
        $data['pigcms_id'] = $this->request->param('pigcms_id', '0', 'intval');//绑定id
        $data['channel_id'] = $this->request->param('channel_id', '0', 'intval');//通道id
        if (empty($data['uid'])){
            return api_output_error(1002,'请先登录');
        }
        if (empty($data['village_id'])){
            return api_output_error(1001,'小区id不能为空');
        }
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $res = $house_village_parking_car->getCarInfo($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 查询车场配置项
     * @author:zhubaodi
     * @date_time: 2022/7/28 19:25
     */
    public function getParkConfig(){
        $data['village_id'] = $this->request->post('village_id', 0);
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getParkConfigInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    public function test(){
        $house_village_parking_car = new HouseVillageParkingService();
        try {
            $id = $house_village_parking_car->test();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $id);
    }

    /**
     * 异步定时查询 订单状态
     * @author: liukezhu
     * @date : 2022/8/16
     * @return \json
     */
    public function timingQuery(){
        $village_id = $this->request->param('village_id', '0', 'intval');
        $car_number = $this->request->param('car_number', '', 'trim');
        $query_order_no = $this->request->param('query_order_no', '', 'trim');
        if(empty($car_number) && empty($query_order_no)){
            return api_output_error(1001, '车牌号不能为空');
        }
        if (strpos($car_number,'-')!=false){
            $count=strpos($car_number,'-');
            $car_number=substr_replace($car_number,"",$count,1);
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'car_number'=>$car_number,
                'query_order_no'=>$query_order_no,
            ];
            $res = (new HouseVillageParkingService())->timingQuery($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }
    public function getAddParkCarInfo(){
        $village_id = $this->request->param('village_id', 0, 'intval');
        if(empty($village_id)){
            return api_output_error(1001, '必传参数缺失');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try{
            $res = $db_house_new_parking_service->getAddCarInfo($village_id,true);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
}
