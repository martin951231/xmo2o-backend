<?php


namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\MeterReadPerson;

use app\community\model\db\HouseVillageUserRecord;
use app\community\model\db\HouseVillageVacancyIccard;
use app\traits\house\HouseTraits;

use customization\customization;
use think\Exception;

class HouseVillageUserVacancyService
{
    use customization;
    use HouseTraits;
    public $houseVillageUserVacancyModel = '';

    /**
     * @var integer 房屋删除时候的状态值
     */
    const ROOM_STATUS_DELETED = 4;
    /**
     * @var integer 房屋删除时候的删除标记值
     */
    const ROOM_DELETE_VALUE = 1;

    public function __construct()
    {
        $this->houseVillageUserVacancyModel = new HouseVillageUserVacancy();
    }

    public function get_room_types(){
        $room_types=array();
        $room_types[]=array('type_id'=>'10','type_name'=>'单间配套');
        $room_types[]=array('type_id'=>'101','type_name'=>'一室一厅');
        $room_types[]=array('type_id'=>'201','type_name'=>'两室一厅');
        $room_types[]=array('type_id'=>'301','type_name'=>'三室一厅');
        return $room_types;
    }
    /**
     * 获取房间列表
     * @author lijie
     * @date_time 2020/07/24
     * @param $where
     * @param bool $field
     * @param $order
     * @param $page
     * @param $limit
     * @return array|\think\Model|null
     */
    public function getRoomListsByUserPhoneOrName($where,$field=true,$order,$page=0,$limit=15)
    {
        $house_village_user_vacancy = new HouseVillageUserVacancy();
        $service_house_village = new HouseVillageService();
        $user_lists = $house_village_user_vacancy->getRoomUserList($where,$field,$order,$page,$limit);
        if (!$user_lists || $user_lists->isEmpty()) {
            $user_lists = [];
        } else {
            foreach ($user_lists as &$val) {
                if (isset($val['bind_number']) && $val['bind_number']) {
                    $val['usernum'] = $val['bind_number'];
                }
                $val['address'] = $service_house_village->getSingleFloorRoom($val['single_id'],$val['floor_id'],$val['layer_id'],$val['v'],$val['village_id']);
            }
        }
        return $user_lists;
    }

    /**
     * 获取条件下房间数量
     * @author lijie
     * @date_time 2020/08/03 13:34
     * @param $where
     * @param string $group
     * @return int
     */
    public function getRoomCount($where,$group='')
    {
        $count = $this->houseVillageUserVacancyModel->getVillageRoomNum($where,$group);
        return $count;
    }

    /**
     * 获取房间列表
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $worker_id
     * @param int $village_id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserVacancy($where,$field=true,$order = 'pigcms_id desc',$worker_id=0,$village_id=0)
    {
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $db_meter_read_person = new MeterReadPerson();
        $data = $db_house_village_user_vacancy->getList($where,$field,$order);
        $dataList = [];
        foreach($data as $k=>$v){
            $list['page'] = $k+1;
            $list['room_id'] = $v['pigcms_id'];
            $list['floor_id'] = $v['floor_id'];
            $list['village_id'] = $v['village_id'];
            $list['layer'] = $v['layer'];
            $list['room'] = $v['room'];
            $list['pigcms_id'] = $v['pigcms_id'];
            $list['room_info'] = $v['layer'] . '#'.$v['room'];
            //类别
            $read_person = $db_meter_read_person->getLists(array('uid'=>$worker_id,'village_id'=>$village_id,'status'=>1));
            if(empty($read_person)){
                $this->returnCode('20090243');
            }
            foreach($read_person as $k1=>$v1){
                $read_persons[] = $v1['cate_id'];
            }
            $wheres[] = array('in',$read_persons);
            $water = $db_meter_read_person->getOne(['village_id'=>$v['village_id'],'cat_name'=>'水费'],$wheres);
            $electric = $db_meter_read_person->getOne(['village_id'=>$v['village_id'],'cat_name'=>'电费'],$wheres);
            $gas = $db_meter_read_person->getOne(['village_id'=>$v['village_id'],'cat_name'=>'燃气费'],$wheres);

            $water_data['village_id'] = $electric_data['village_id'] = $gas_data['village_id'] = $v['village_id'];
            $water_data['floor_id'] =$electric_data['floor_id'] =$gas_data['floor_id'] = $v['floor_id'];
            $water_data['room_id']=$electric_data['room_id']=$gas_data['room_id'] = $v['pigcms_id'];

            $water_data['cate_id'] = $water['id'];
            $electric_data['cate_id'] = $electric['id'];
            $gas_data['cate_id'] = $gas['id'];

            $water_record = $db_meter_read_person->getOne($water_data,array(),'','id DESC');
            $electric_record = $db_meter_read_person->getOne($electric_data,array(),'','id DESC');
            $gas_record = $db_meter_read_person->getOne($gas_data,array(),'','id DESC');

            if($water_record && $electric_record && $gas_record){
                if(date('d',$water_record['add_time']) >= $water['cycle_time']){

                    if(date('d',$electric_record['add_time']) >= $electric['cycle_time']){

                        if(date('d',$gas_record['add_time']) >= $gas['cycle_time']){
                            $list['status'] = 2;
                            $list['statusDesc'] = '已抄完';
                        }else{
                            $list['status'] = 1;
                            $list['statusDesc'] = '未抄完';
                        }
                    }else{
                        $list['status'] = 1;
                        $list['statusDesc'] = '未抄完';
                    }
                }else{
                    $list['status'] = 1;
                    $list['statusDesc'] = '未抄完';
                }

            }else{
                $list['status'] = 1;
                $list['statusDesc'] = '未抄完';
            }


            $dataList[] = $list;
        }
        $arr['vacancy_list'] = $dataList;
        if($data)
            $arr['total_page'] = count($data);
        else
            $arr['total_page'] = 0;
        return $arr;
    }

    /**
     * 房间详情
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool|string $field
     * @param array $param
     * [
     *   'getAddress' => true, // 获取下楼栋单元楼层和对应地址信息
     * ]
     * @return array|\think\Model|null
     */
    public function getUserVacancyInfo($where,$field=true,$param=[])
    {
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $data = $db_house_village_user_vacancy->getOne($where,$field);
        if (isset($param['getAddress'])&&$param['getAddress']&&isset($data['single_id'])&&isset($data['floor_id'])&&isset($data['layer_id'])) {
            $houseVillageSignService = new HouseVillageSingleService();
            $village_id = isset($data['village_id'])?$data['village_id']:0;
            $where = [
                'id' => $data['single_id']
            ];
            if ($village_id) {
                $where['village_id'] = $village_id;
            }
            $singleInfo = $houseVillageSignService->getSingleInfo($where,'id,single_name');
            if (isset($singleInfo['single_name'])) {
                $data['single_name'] = $singleInfo['single_name'];
            }
            $whereFloor = [
                'floor_id'   => $data['floor_id']
            ];
            if ($village_id) {
                $whereFloor['village_id'] = $village_id;
            }
            $floorInfo = $houseVillageSignService->getFloorInfo($whereFloor,'floor_id,floor_name');
            if (isset($floorInfo['floor_name'])) {
                $data['floor_name'] = $floorInfo['floor_name'];
            }
            $whereLayer = [
                'id'         => $data['layer_id']
            ];
            if ($village_id) {
                $whereLayer['village_id'] = $village_id;
            }
            $layerInfo = $houseVillageSignService->getLayerInfo($whereLayer,'id,layer_name');
            if (isset($layerInfo['layer_name'])) {
                $data['layer_name'] = $layerInfo['layer_name'];
            }
        }
        if (isset($param['getContractTime'])&&$param['getContractTime']&&isset($data['contract_time_start'])&&isset($data['contract_time_end'])) {

        }
        return $data;
    }
    /**
     * 房间详情 编辑
     */
    public function getUserVacancyEditInfo($where, $field = true)
    {
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $dataObj = $db_house_village_user_vacancy->getOne($where, $field);
        $vacancyInfo = array();
        if ($dataObj && !$dataObj->isEmpty()) {
            $vacancyInfo = $dataObj->toArray();
        }
        $houseVillageSignService = new HouseVillageSingleService();
        $village_id = isset($vacancyInfo['village_id']) ? $vacancyInfo['village_id'] : 0;

        $vacancyInfo['single_name'] = '';
        $vacancyInfo['single_number'] = '';
        $vacancyInfo['contract_time_start_str']='';
        $vacancyInfo['contract_time_end_str']='';
        if($vacancyInfo['contract_time_start']>100){
            $vacancyInfo['contract_time_start_str']=date('Y-m-d',$vacancyInfo['contract_time_start']);
        }
        $vacancyInfo['user_status'] = $vacancyInfo['user_status']>0 ? strval($vacancyInfo['user_status']):"0";
        $vacancyInfo['house_type'] = $vacancyInfo['house_type']>0 ? strval($vacancyInfo['house_type']):"0";
        $vacancyInfo['sell_status'] = $vacancyInfo['sell_status']>0 ? strval($vacancyInfo['sell_status']):"1";
        $vacancyInfo['status'] = $vacancyInfo['status']>0 ? strval($vacancyInfo['status']):"0";
        if(isset($vacancyInfo['room_type']) && ($vacancyInfo['room_type']>0)){
            $vacancyInfo['room_type']=strval($vacancyInfo['room_type']);
        }else{
            $vacancyInfo['room_type']="0";
        }
        if($vacancyInfo['contract_time_end']>100){
            $vacancyInfo['contract_time_end_str']=date('Y-m-d',$vacancyInfo['contract_time_end']);
        }
        $where = [
            'id' => $vacancyInfo['single_id']
        ];
        if ($village_id) {
            $where['village_id'] = $village_id;
        }
        $singleInfo = $houseVillageSignService->getSingleInfo($where, 'id,single_name,single_number,contract_time_start,contract_time_end');
        $vacancyInfo['single_info']=array();
        if ($singleInfo && !$singleInfo->isEmpty()) {
            $singleData = $singleInfo->toArray();
            $vacancyInfo['single_info']=$singleData;
            $vacancyInfo['single_name'] = $singleData['single_name'];
            $vacancyInfo['single_number'] = $singleData['single_number'];
            if(empty($vacancyInfo['contract_time_start_str']) && $singleData['contract_time_start']>100){
                $vacancyInfo['contract_time_start_str']=date('Y-m-d',$singleData['contract_time_start']);
                $vacancyInfo['contract_time_end_str']=date('Y-m-d',$singleData['contract_time_end']);
            }
        }
        $whereFloor = [
            'floor_id' => $vacancyInfo['floor_id']
        ];
        if ($village_id) {
            $whereFloor['village_id'] = $village_id;
        }
        $vacancyInfo['floor_name'] = '';
        $vacancyInfo['floor_number'] = '';
        $floorInfo = $houseVillageSignService->getFloorInfo($whereFloor, 'floor_id,floor_name,floor_number');
        if ($floorInfo && !$floorInfo->isEmpty()) {
            $floorData = $floorInfo->toArray();
            $vacancyInfo['floor_name'] = $floorData['floor_name'];
            $vacancyInfo['floor_number'] = $floorData['floor_number'];
        }
        $whereLayer = [
            'id' => $vacancyInfo['layer_id']
        ];
        if ($village_id) {
            $whereLayer['village_id'] = $village_id;
        }
        $vacancyInfo['layer_name'] = '';
        $vacancyInfo['layer_number'] = '';
        $layerInfo = $houseVillageSignService->getLayerInfo($whereLayer, 'id,layer_name,layer_number');
        if ($layerInfo && !$layerInfo->isEmpty()) {
            $layerData = $layerInfo->toArray();
            $vacancyInfo['layer_name'] = $layerData['layer_name'];
            $vacancyInfo['layer_number'] = $layerData['layer_number'];
        }
        if(empty($vacancyInfo['property_number'])){
            $vacancyInfo['property_number']=$vacancyInfo['usernum'];
        }
        return $vacancyInfo;
    }
    //编辑房间
    public function saveRoomEdit($pigcms_id=0,$village_id=0,$savedata=array()){
        if($pigcms_id<1 || $village_id<1 || empty($savedata)){
            return false;
        }
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['pigcms_id', '=', $pigcms_id];
        $where[] = ['is_public_rental', '=', 1];
        $roomData=$this->getUserVacancyEditInfo($where);
        if(empty($roomData)){
            throw new \think\Exception("房间信息不存在！");
        }
        $single_info=$roomData['single_info'];
        $contract_time_end='';
        if(!empty($single_info) && $single_info['contract_time_end']>100){
            $contract_time_end=$single_info['contract_time_end'];
        }
        if(empty($contract_time_end) && $savedata['contract_time_start']>0){
            throw new \think\Exception("请先去楼栋设置合同时间！");
        }
        if(!empty($savedata['contract_time_start'])&&!empty($contract_time_end) && $savedata['contract_time_start']>=$contract_time_end){
            throw new \think\Exception("合同开始时间不能大于合同结束时间！");
        }
        if(!empty($savedata['contract_time_start']) && $savedata['contract_time_start'] < $single_info['contract_time_start']){
            throw new \think\Exception("房间合同开始时间不能大于楼栋合同开始时间！");
        }
        $savedata['contract_time_end']=$contract_time_end;
        if(!empty($savedata['contract_time_start'])&&!empty($savedata['contract_time_end'])){
            $where_time=[];
            $where_time['village_id']=$village_id;
            $where_time['order_type']='property';
            $where_time['room_id']=$pigcms_id;
            $house_new_order_log = new HouseNewOrderLog();
            $order_log_obj = $house_new_order_log->getOne($where_time,true,'id DESC');
            $order_log=array();
            if($order_log_obj && !$order_log_obj->isEmpty()){
                $order_log=$order_log_obj->toArray();
            }
            //合同结束时间不能小于最大的物业服务结束时间
            if (!empty($order_log) && $order_log['service_end_time']>$savedata['contract_time_end']){
                throw new \think\Exception('合同结束时间不能小于'.date('Y-m-d',$order_log['service_end_time']));
            }
            //合同开始时间不能大于最小的物业服务结束时间
            if (!empty($order_log) && $order_log['service_end_time']< $savedata['contract_time_start']){
                throw new \think\Exception('合同开始时间不能大于'.date('Y-m-d',$order_log['service_end_time']));
            }
        }
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();

        if ($savedata['room']!=$roomData['room']) {
            // 房屋好进行了变动 查询下当前楼层下是否有重复房屋号
            $where_vacancy = [];
            $where_vacancy[]=['pigcms_id','<>', $pigcms_id];
            $where_vacancy[]=['room','=',$savedata['room']];
            $where_vacancy[]=['single_id','=', $roomData['single_id']];
            $where_vacancy[]=['floor_id','=', $roomData['floor_id']];
            $where_vacancy[]=['layer_id','=', $roomData['layer_id']];
            $where_vacancy[]=['village_id','=', $village_id];
            $where_vacancy[]=['is_del','=', 0];
            $dataObj = $db_house_village_user_vacancy->getOne($where_vacancy, 'pigcms_id,room');
            if ($dataObj && !$dataObj->isEmpty()) {
                throw new \think\Exception('当前房屋已经存在 请确认后再进行修改！');
            }
        }
        $where_vacancy_bind = [];
        $where_vacancy_bind[]=['vacancy_id','=',$pigcms_id];
        $where_vacancy_bind[]=['type','in',[0,3]];
        $where_vacancy_bind[]=['status','not in',[3,4]];
        $db_house_village_user_bind = new HouseVillageUserBind();
        $field = 'pigcms_id,type,village_id,room_number,bind_number,vacancy_id,status';
        $user_bind_obj = $db_house_village_user_bind->getOne($where_vacancy_bind,$field);
        if($user_bind_obj && !$user_bind_obj->isEmpty() ){
            $user_bind=$user_bind_obj->toArray();
            if ($roomData['status']==0 && $roomData['uid']>0 && $savedata['status'] == 1) {
                if ($user_bind && $user_bind['status'] == 1) {
                    $savedata['status'] = 3;
                } elseif ($user_bind && $user_bind['status'] == 2) {
                    $savedata['status'] = 2;
                } else {
                    $savedata['status']  =  1;
                }
            } elseif ($savedata['status'] == 0 || $roomData['status'] == 0) {
                if ($roomData['status'] == 1) {
                    if ($user_bind && $user_bind['status'] == 1) {
                        $savedata['status'] = 3;
                    } elseif ($user_bind && $user_bind['status'] == 2) {
                        $savedata['status'] = 2;
                    }
                }
            } else {
                $savedata['status'] =  $roomData['status'];
                if ($roomData['status'] == 1) {
                    if ($user_bind && $user_bind['status'] == 1) {
                        $savedata['status'] = 3;
                    } elseif ($user_bind && $user_bind['status'] == 2) {
                        $savedata['status'] = 2;
                    } else {
                        $savedata['status']  =  1;
                    }
                }
            }
        }

        if (isset($savedata['room_number']) && !empty($savedata['room_number'])) {
            $check_number = "/^[0-9]+$/";
            $room_number = trim($savedata['room_number']);
            if(!preg_match($check_number, $room_number)){
                throw new \think\Exception('房屋编号只允许数字！');
            }
            if(intval($room_number)<=0 || intval($room_number)>9999){
                throw new \think\Exception('房屋编号必须为1-9999的数字！');
            }
            if (strlen($room_number)>4) {
                throw new \think\Exception('房屋编号只允最多4位数字！');
            } elseif (strlen($room_number)<2) {
                // 不足2位 补足2位
                $room_number = str_pad($room_number,2,"0",STR_PAD_LEFT);
            }
            $savedata['room_number'] = $room_number;
        }
        if (empty($savedata['room_number']) && !empty($savedata['room'])) {
            $pattern_number = "/[^0-9]/";
            $room_number = preg_replace($pattern_number,'',$savedata['room']);
            // 房屋编号 暂时同一个小区下仅限1-99数字编号
            if ($room_number) {
                if (strlen($room_number)>2) {
                    // 超过2位 截取后2位
                    $room_number = substr($room_number, -2,2);
                } elseif (strlen($room_number)<2) {
                    // 不足2位 补足2位
                    $room_number = str_pad($room_number,2,"0",STR_PAD_LEFT);
                }
                $savedata['room_number'] = $room_number;
            }
        }
        if ($savedata['room_number']) {
            $where_repeat = [];
            $where_repeat[]=['pigcms_id','<>', $pigcms_id];
            $where_repeat[]=['room_number','=',$savedata['room_number']];
            $where_repeat[]=['single_id','=', $roomData['single_id']];
            $where_repeat[]=['floor_id','=', $roomData['floor_id']];
            $where_repeat[]=['layer_id','=', $roomData['layer_id']];
            $where_repeat[]=['village_id','=', $village_id];
            $where_repeat[]=['status','<>', 4];
            $where_repeat[]=['is_del','=', 0];
            $dataObj = $db_house_village_user_vacancy->getOne($where_repeat, 'pigcms_id,room_number');
            if ($dataObj && !$dataObj->isEmpty()) {
                throw new \think\Exception('该房屋编号已经存在，请填写1-9999的楼层编号！');
            }
            $room_number_str=intval($savedata['room_number']);
            $houseVillageSignService = new HouseVillageSingleService();
            if($room_number_str < 100){
                $room_number_str = str_pad($room_number_str, 2, "0", STR_PAD_LEFT);

                $whereLayer = [
                    'id' => $roomData['layer_id']
                ];
                if ($village_id) {
                    $whereLayer['village_id'] = $village_id;
                }
                $layerInfo = $houseVillageSignService->getLayerInfo($whereLayer, 'id,layer_name,layer_number');
                if ($layerInfo && !$layerInfo->isEmpty()) {
                    $layerData = $layerInfo->toArray();
                    $layer_number=$layerData['layer_number'];
                    $layer_number = str_pad($layer_number, 2, "0", STR_PAD_LEFT);
                    $savedata['room_number'] = $layer_number.$room_number_str;
                }
            }
        } else {
            throw new \think\Exception('请填写1-99的不重复房屋编号！');
        }
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['pigcms_id', '=', $pigcms_id];
        $where[] = ['is_public_rental', '=', 1];
        $ret=$db_house_village_user_vacancy->saveOne($where,$savedata);
        //echo $db_house_village_user_vacancy->getLastSql();
        if($ret){
            // 修改了房屋面积，且在绑定表格中存在对应的数据，同步更改其中的房屋面积 (更改对象包括房主，家属，租客及更新房主)
            if ($savedata['housesize']>0) {
                $bind_data_info = [];
                $bind_data_info['housesize'] = $savedata['housesize'];
                $db_house_village_user_bind->saveOne(array('vacancy_id' => $pigcms_id),$bind_data_info);

            }
            if ($savedata['room_number']) {
                $change_param = [
                    'village_id' => $village_id,
                    'pigcms_id' => $pigcms_id,
                    'room_number' => $savedata['room_number'],
                ];
                $this->changeUserBindNumber($change_param);
            }
            return array('status'=>1,'msg'=>'修改成功！');
        }
        return array('status'=>1,'msg'=>'修改成功！');
    }
    /**
     * 房间列表
     * @author lijie
     * @date_time 2020/11/03
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|\think\Model|null
     */
    public function getVacancyList($where,$field = true,$order='pigcms_id desc')
    {
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $data = $db_house_village_user_vacancy->getList($where,$field,$order,0,0);
        return $data;
    }

    //获取房间在列表展示
    public function getVillageVacancyList($where,$field = true,$order='pigcms_id desc',$page=1,$limit=20)
    {
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $dataObj = $db_house_village_user_vacancy->getLists($where,$field,$page=1,$limit=20,$order);
        $vacancys=array('list'=>array(),'count'=>0,'total_limit'=>$limit);
        if($dataObj && !$dataObj->isEmpty()){
            $list=$dataObj->toArray();
            if($list){
                foreach ($list as $kk=>$vv){
                    $list[$kk]['user_status_str']='无';
                    $list[$kk]['status_str']='关闭';
                    if($list[$kk]['property_number']){
                        $list[$kk]['usernum'] =$list[$kk]['property_number'];
                    }
                    $serviceTimeInfo = $this->getRoomEndTime($vv['pigcms_id'],$vv['village_id'],0,true);
                    if (isset($serviceTimeInfo['propertyStartTime']) && $serviceTimeInfo['propertyStartTime']>1){
                        $service_start_time = date('Y-m-d',$serviceTimeInfo['propertyStartTime']);
                    }else{
                        $service_start_time = '未设置';
                    }
                    if (isset($serviceTimeInfo['propertyEndTime']) && $serviceTimeInfo['propertyEndTime']>1){
                        $service_end_time = date('Y-m-d',$serviceTimeInfo['propertyEndTime']);
                    }else{
                        $service_end_time = '未设置';
                    }
                    $list[$kk]['service_cycle'] = $service_start_time.' - '.$service_end_time;

                    if($vv['user_status']==1){
                        $list[$kk]['user_status_str']='业主入住';
                    }elseif ($vv['user_status']==2){
                        $list[$kk]['user_status_str']='未入住';
                    }elseif ($vv['user_status']==3){
                        $list[$kk]['user_status_str']='租客入住';
                    }
                    if($vv['status']==1){
                        $list[$kk]['status_str']='空置';
                    }elseif ($vv['status']==2){
                        $list[$kk]['status_str']='审核中';
                    }elseif ($vv['status']==3){
                        $list[$kk]['status_str']='已绑定业主';
                        if($vv['uid']==0 && !empty($vv['name'])&& !empty($vv['phone'])){
                            $list[$kk]['status_str']=='已绑定业主[未注册]';
                        }
                    }
                }
            }
            $vacancys['list']=!empty($list) ? $list:array();
            $count=$db_house_village_user_vacancy->count($where);
            $vacancys['count']=$count>0 ? $count:0;
        }
        return $vacancys;
    }
    /**
     * Notes: 更新用户绑定物业编号
     * @param $change_param
     * {
     *     'pigcms_id' => '1', // 修改用户编号的 用户小区绑定id 即用户绑定小区表格中pigcms_id
     * }
     * @return bool
     * @author: wanzy
     * @date_time: 2021/1/13 14:30
     */
    public function changeUserBindNumber($change_param) {
        if (empty($change_param) || !$change_param['pigcms_id']) {
            return false;
        }
        $pigcms_id = intval($change_param['pigcms_id']);
        $db_house_village_user_bind = new HouseVillageUserBind();
        $where_user_bind = [];
        $where_user_bind[] = ['pigcms_id','=', $pigcms_id];
        $field = 'pigcms_id, type, village_id, room_number, bind_number, vacancy_id';
        $user_bind = $db_house_village_user_bind->getOne($where_user_bind,$field);
        if (!$user_bind) {
            return false;
        }
        $user_bind = $user_bind->toArray();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $save_data = [];
        if (in_array($user_bind['type'],[0,1,2,3]) && $user_bind['vacancy_id']) {
            // 如果是 业主 家属 租客 对应查询房屋 组成新的编号 然后记录上
            $vacancy_id = intval($user_bind['vacancy_id']);
            $where_vacancy = [];
            $where_vacancy[] = ['pigcms_id','=',$vacancy_id];
            $property_number_info = $db_house_village_user_vacancy->getOne($where_vacancy,'property_number');
            if (!$property_number_info) {
                fdump_api('房屋的物业编号未生成>>'.__LINE__,'house/change_user_bind_number_err_log',true);
                fdump_api($change_param,'house/change_user_bind_number_err_log',true);
                fdump_api($user_bind,'house/change_user_bind_number_err_log',true);
                return false;
            }
            $property_number_info = $property_number_info->toArray();
            $property_number = $property_number_info['property_number'];

            if ($user_bind['room_number']) {
                $bind_number = $property_number . $user_bind['room_number'];
                $where_repeat = [];
                $where_repeat[] = ['village_id','=',$user_bind['village_id']];
                $where_repeat[] = ['bind_number','=',$bind_number];
                $where_repeat[] = ['pigcms_id','<>', $pigcms_id];
                $repeat_id = $db_house_village_user_bind->getOne($where_repeat, 'pigcms_id');
                if ($repeat_id) {
                    $where_vacancy_max = [];
                    $where_vacancy_max[] = ['vacancy_id'=>$vacancy_id];
                    $where_vacancy_max[] = ['pigcms_id','<>', $pigcms_id];
                    $where_vacancy_max[] = ['status','<>', 4];
                    $room_number_info = $db_house_village_user_bind->getOne($where_vacancy_max,'MAX(room_number) as room_number_max');
                    $room_number_info = $room_number_info->toArray();
                    if ($room_number_info) {
                        $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                    } else {
                        $room_number_max = 0;
                    }
                    $room_number = intval($room_number_max) + 1;
                    $bind_number = $property_number . $room_number;
                    $save_data['room_number'] = $room_number;
                    if ($bind_number && $bind_number!=$user_bind['bind_number']) {
                        $save_data['bind_number'] = $bind_number;
                    }
                } elseif ($bind_number && $bind_number!=$user_bind['bind_number']) {
                    $save_data['bind_number'] = $bind_number;
                }
            } else {
                $where_vacancy_max = [];
                $where_vacancy_max[] = ['vacancy_id','=', $vacancy_id];
                $where_vacancy_max[] = ['pigcms_id','<>', $pigcms_id];
                $where_vacancy_max[] = ['status','<>', 4];
                $room_number_info = $db_house_village_user_bind->getOne($where_vacancy_max,'MAX(room_number) as room_number_max');
                $room_number_info = $room_number_info->toArray();
                if ($room_number_info) {
                    $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                } else {
                    $room_number_max = 0;
                }
                $room_number = intval($room_number_max) + 1;
                $bind_number = $property_number . $room_number;
                $save_data['room_number'] = $room_number;
                if ($bind_number && $bind_number!=$user_bind['bind_number']) {
                    $save_data['bind_number'] = $bind_number;
                }
            }
            if ($save_data) {
                $where_user_bind = [];
                $where_user_bind[] = ['pigcms_id','=', $pigcms_id];
                $user = $db_house_village_user_bind->saveOne($where_user_bind, $save_data);
                if (!$user) {
                    fdump_api('更改用户编号错误>>'.__LINE__,'house/change_user_bind_number_err_log',true);
                    fdump_api($pigcms_id,'house/change_user_bind_number_err_log',true);
                    fdump_api($vacancy_id,'house/change_user_bind_number_err_log',true);
                    fdump_api($property_number,'house/change_user_bind_number_err_log',true);
                    fdump_api($user_bind,'house/change_user_bind_number_err_log',true);
                    fdump_api($save_data,'house/change_user_bind_number_err_log',true);
                    fdump_api($db_house_village_user_bind->getDbError(),'house/change_user_bind_number_err_log',true);
                    fdump_api($db_house_village_user_bind->_sql(),'house/change_user_bind_number_err_log',true);
                }
            }
            return true;
        } else {
            // 当前其他身份 只处理 工作人员
            if (4==$user_bind['type']) {
                // 楼栋（00)单元（00）楼层（99） 楼层默认99
                $property_number = '000099';
                if ($user_bind['room_number']) {
                    $bind_number = $property_number . $user_bind['room_number'];
                    // 查询同一小区是否存在重复物业编号
                    $where_repeat = [];
                    $where_repeat[] = ['village_id','=',$user_bind['village_id']];
                    $where_repeat[] = ['bind_number','=',$bind_number];
                    $where_repeat[] = ['type','=', 4];
                    $where_repeat[] = ['pigcms_id','<>', $pigcms_id];
                    $repeat_id = $db_house_village_user_bind->getOne($where_repeat,'pigcms_id');
                    if ($repeat_id) {
                        $where_vacancy_max = [];
                        $where_vacancy_max[] = ['village_id','=',$user_bind['village_id']];
                        $where_vacancy_max[] = ['pigcms_id','<>', $pigcms_id];
                        $where_vacancy_max[] = ['type','in', '0,3'];
                        $where_vacancy_max[] = ['status','=', 1];
                        $room_number_info = $db_house_village_user_bind->getOne($where_vacancy_max,'MAX(room_number) as room_number_max');
                        $room_number_info = $room_number_info->toArray();
                        if ($room_number_info) {
                            $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                        } else {
                            $room_number_max = 0;
                        }
                        $room_number = intval($room_number_max) + 1;
                        $bind_number = $property_number . $user_bind['room_number'];
                        $save_data['room_number'] = $room_number;
                        if ($bind_number && $bind_number!=$user_bind['bind_number']) {
                            $save_data['bind_number'] = $bind_number;
                        }
                    } elseif ($bind_number && $bind_number!=$user_bind['bind_number']) {
                        $save_data['bind_number'] = $bind_number;
                    }
                } else {
                    $where_vacancy_max = [];
                    $where_vacancy_max[] = ['village_id','=',$user_bind['village_id']];
                    $where_vacancy_max[] = ['pigcms_id','<>', $pigcms_id];
                    $where_vacancy_max[] = ['type','=', 4];
                    $where_vacancy_max[] = ['status','<>', 4];
                    $room_number_info = $db_house_village_user_bind->getOne($where_vacancy_max,'MAX(room_number) as room_number_max');
                    $room_number_info = $room_number_info->toArray();
                    if ($room_number_info) {
                        $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                    } else {
                        $room_number_max = 0;
                    }
                    $room_number = intval($room_number_max) + 1;
                    $bind_number = $property_number . $user_bind['room_number'];
                    $save_data['room_number'] = $room_number;
                    if ($bind_number && $bind_number!=$user_bind['bind_number']) {
                        $save_data['bind_number'] = $bind_number;
                    }
                }
                if ($save_data) {
                    $where_user_bind = [];
                    $where_user_bind[] = ['pigcms_id','=', $pigcms_id];
                    $user = $db_house_village_user_bind->saveOne($where_user_bind, $save_data);
                    if (!$user) {
                        fdump_api('更改用户编号错误>>'.__LINE__,'house/change_user_bind_number_err_log',true);
                        fdump_api($pigcms_id,'house/change_user_bind_number_err_log',true);
                        fdump_api($property_number,'house/change_user_bind_number_err_log',true);
                        fdump_api($user_bind,'house/change_user_bind_number_err_log',true);
                        fdump_api($save_data,'house/change_user_bind_number_err_log',true);
                        fdump_api($db_house_village_user_bind->getDbError(),'house/change_user_bind_number_err_log',true);
                        fdump_api($db_house_village_user_bind->_sql(),'house/change_user_bind_number_err_log',true);
                    }
                }
                return true;
            }
        }
        return true;
    }

    /**
     * 小区房间模糊搜索
     * User: zhanghan
     * Date: 2022/1/5
     * Time: 13:30
     * @param $param
     * @param string $order
     * @return array
     */
    public function getUserVacancySearchList($param,$order = 'pigcms_id desc'){
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();

        $where = [];
        $where[] = ['village_id','=',$param['village_id']];
        $field = 'distinct property_number as text';
        switch ($param['option_type']){
            case 2:
                $field = 'distinct name as text';
                $where[] = ['name','like','%'.$param['search_keyword'].'%'];
                break;
            case 3:
                $field = 'distinct phone as text';
                $where[] = ['phone','like','%'.$param['search_keyword'].'%'];
                break;
            default:
                if(strpos($param['search_keyword'],'-') !== false){
                    $where = $this->propertyNumberMatching($where,$param['search_keyword'],$param['village_id']);
                }else{
                    $where[] = ['property_number|usernum','like','%'.$param['search_keyword'].'%'];
                }
                break;
        }
        $data = $db_house_village_user_vacancy->getList($where,$field,$order);
        return $data;
    }

    public function getVacancySearchByAliasIdList($param,$order = 'pigcms_id desc'){
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        if(empty($param['search_keyword'])){
            return array();
        }
        $db_single = new HouseVillageSingle();
        $db_floor = new HouseVillageFloor();
        $db_layer = new HouseVillageLayer();
        $where = [];
        $where[] = ['village_id','=',$param['village_id']];
        $where[] = ['is_del','=',0];
        $where[] = ['status','in',[1,2,3]];
        $where[] = ['room_alias_id','like','%'.$param['search_keyword'].'%'];
        $field='pigcms_id,usernum,floor_id,layer,room,village_id,uid,name,phone,single_id,layer_id,room_number,room_alias_id';
        $dataObj = $db_house_village_user_vacancy->getList($where,$field,$order,20);
        if($dataObj && !$dataObj->isEmpty()){
            $listArr=array();
            $vacancyLists=$dataObj->toArray();
            $text='';
            foreach ($vacancyLists as $vv){
                $singleObj=$db_single->getOne(['id'=>$vv['single_id']],'id,single_name');
                if($singleObj && !$singleObj->isEmpty()){
                    $single=$singleObj->toArray();
                    $single_name=$this->traitAutoFixLouDongTips($single['single_name'],true);
                    $text=$single_name;
                }
                $floorObj=$db_floor->getOne(['floor_id'=>$vv['floor_id']],'floor_id,floor_name');
                if($floorObj && !$floorObj->isEmpty()){
                    $floor=$floorObj->toArray();
                    $floor_name=$this->traitAutoFixDanyuanTips($floor['floor_name'],true);
                    $text.=$floor_name;
                }
                $layerObj=$db_layer->getOne(['id'=>$vv['layer_id']],'id,layer_name');
                if($layerObj && !$layerObj->isEmpty()){
                    $layer=$layerObj->toArray();
                    $floor_name=$this->traitAutoFixLoucengTips($layer['layer_name'],true);
                    $text.=$floor_name;
                }
                if($vv['room']){
                    $text.=$vv['room'];
                }
                $room_alias_id=!empty($vv['room_alias_id']) ? $vv['room_alias_id']:'';
                $listArr[]=array('text'=>$text,'room_id'=>$vv['pigcms_id'],'room_alias_id'=>$room_alias_id);
            }
            return $listArr;
        }else{
            return array();
        }
    }

    public function getRoomRawInfo($whereRaw,$field=true)
    {
        $data = $this->houseVillageUserVacancyModel->getRawOne($whereRaw,$field);
        return $data;
    }

    public function saveRoomsInfo($where, $save) {
        $id = $this->houseVillageUserVacancyModel->saveOne($where, $save);
        return $id;
    }

    public function addRoomsInfo($param)
    {
        $id = $this->houseVillageUserVacancyModel->addOne($param);
        return $id;
    }

    /**
     * 物业编号快速匹配  // 延华定制
     * User: zhanghan
     * Date: 2022/2/24
     * Time: 11:11
     * @param $where
     * @param $search_keyword
     * @return mixed
     */
    public function propertyNumberMatching($where,$search_keyword,$village_id){
        $house_village_user_bind = new HouseVillageUserBind();

        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $room_arr = explode('-', $search_keyword);
        if ($jz_open && count($room_arr) == 3 && $room_arr[0] && $room_arr[1] && $room_arr[2]) {
            $whereArr = array(['village_id', '=', $village_id]);
            $whereArr[] = array('bind_number|usernum', '=', $search_keyword);
            $whereArr[] = array('status', '=', 1);
            $bindInfoObj = $house_village_user_bind->getOne($whereArr, 'room_number,vacancy_id');
            if ($bindInfoObj && !is_array($bindInfoObj)) {
                $bindInfo = $bindInfoObj->toArray();
            }
            if (isset($bindInfo['vacancy_id']) && $bindInfo['vacancy_id']) {
                // 如果能直接查询到房间 直接返回对应房间信息
                $where[] = ['pigcms_id', '=', $bindInfo['vacancy_id']];
                return $where;
            }

            $db_single = new HouseVillageSingle();
            $single_number = $room_arr[0]; // 楼栋编号
            $single1 = intval($single_number);
            $single2 = $single_number;
            $whereSingleRaw = "village_id={$village_id}";
            if (strlen($single_number) < 2) {
                $single3 = str_pad($single_number,2,"0",STR_PAD_LEFT);
                $single4 = str_pad($single_number,3,"0",STR_PAD_LEFT);
                $whereSingleRaw .= " AND (single_number={$single1} OR single_number={$single2} OR single_number={$single3} OR single_number={$single4})";
            } elseif (strval($single1) !== strval($single2)) {
                $whereSingleRaw .= " AND (single_number={$single1} OR single_number={$single2})";
            } else {
                $whereSingleRaw .= " AND single_number={$single1}";
            }
            $singleIds = $db_single->getWhereRawColumn($whereSingleRaw, 'id');
            if (empty($singleIds)) {
                // 楼栋都没查询到 默认查询不到信息
                $where[] = ['single_id', '=', -1];
                return $where;
            }
            $db_floor = new HouseVillageFloor();
            $floor_number = $room_arr[1];
            $floor1 = intval($floor_number);
            $floor2 = $floor_number;
            $singleStringIds = implode(',', $singleIds);
            $whereFloorRaw = "village_id={$village_id} AND single_id in ({$singleStringIds})";
            if (strlen($floor_number) < 2) {
                $floor3 = str_pad($floor_number,2,"0",STR_PAD_LEFT);
                $whereFloorRaw .= " AND (floor_number={$floor1} OR floor_number={$floor2} OR floor_number={$floor3})";
            } elseif (strval($floor1) !== strval($floor2)) {
                $whereFloorRaw .= " AND (floor_number={$floor1} OR floor_number={$floor2})";
            } else {
                $whereFloorRaw .= " AND floor_number={$floor1}";
            }
            $floorIds = $db_floor->getWhereRawColumn($whereFloorRaw, 'floor_id');
            if (empty($floorIds)) {
                // 单元都没查询到 默认查询不到信息
                $where[] = ['floor_id', '=', -1];
                return $where;
            }
            $db_vacancy = new HouseVillageUserVacancy();
            $room_number = $room_arr[2];
            $room1 = intval($room_number);
            $room2 = $room_number;
            $floorStringIds = implode(',', $floorIds);
            $whereRoomRaw = "village_id={$village_id} AND floor_id in ({$floorStringIds})";
            if (strlen($room_number) < 2) {
                $room3 = str_pad($room_number,2,"0",STR_PAD_LEFT);
                $room4 = str_pad($room_number,4,"0",STR_PAD_LEFT);
                $whereRoomRaw .= " AND (room_number={$room1} OR room_number={$room2} OR room_number={$room3} OR room_number={$room4})";
            } elseif (strlen($room_number) < 4) {
                $room3 = str_pad($room_number,4,"0",STR_PAD_LEFT);
                $whereRoomRaw .= " AND (room_number={$room1} OR room_number={$room2} OR room_number={$room3})";
            } elseif (strval($room1) !== strval($room2)) {
                $whereRoomRaw .= " AND (room_number={$room1} OR room_number={$room2})";
            } else {
                $whereRoomRaw .= " AND room_number={$room1}";
            }
            $vacancyIds = $db_vacancy->getWhereRawColumn($whereRoomRaw, 'pigcms_id');
            if (empty($vacancyIds)) {
                // 单元都没查询到 默认查询不到信息
                $where[] = ['pigcms_id', '=', -1];
                return $where;
            }
            $where[] = ['pigcms_id', 'in', $vacancyIds];
            return $where;
        }

        $whereArr = array(['village_id', '=', $village_id]);
        $whereArr[] = array('bind_number|usernum', '=', $search_keyword);
        $whereArr[] = array('status', '=', 1);
        $bindInfoObj = $house_village_user_bind->getOne($whereArr, 'room_number,vacancy_id');
        if ($bindInfoObj && !$bindInfoObj->isEmpty()) {
            $bindInfo = $bindInfoObj->toArray();
            $where[] = ['pigcms_id', '=', $bindInfo['vacancy_id']];
        } else {
            $where[] = ['property_number|usernum', '=', $search_keyword];
        }
        return $where;
        

        $floor_name = ''; // 单元名称
        $layer_name = ''; // 楼层名称
        $single_name = $room_arr[0]; // 楼栋名称
        $room_name = $room_arr[count($room_arr)-1]; // 房间名称
        if (count($room_arr) == 3){
            $floor_name = $room_arr[1];
        }elseif (count($room_arr) == 4){
            $floor_name = $room_arr[1];
            $layer_name = $room_arr[2];
        }

        // 查询楼栋ID
        $single_id = $db_single->getOneColumn([['single_name', '=', $single_name],['village_id', '=', $village_id]]);
        if($single_id){
            $where[] = ['single_id','in',$single_id];
        }
        // 查询单元ID
        if(!empty($floor_name)){
            $floor_id = $db_floor->getOneColumn([['single_id', 'in', $single_id],['floor_name', '=', $floor_name],['village_id', '=', $village_id]]);
            if($floor_id){
                $where[] = ['floor_id','in',$floor_id];
            }
            
        }
        // 查询楼层ID
        if(!empty($layer_name)){
            $layer_id = $db_layer->getOneColumn([['single_id', 'in', $single_id],['layer_name', '=', $layer_name],['village_id', '=', $village_id]]);
            if($layer_id){
                $where[] = ['layer_id','in',$layer_id];
            }
        }
        if(!empty($room_name)){
            $where[] = ['room','=',$room_name];
        }
        $where[] = ['property_number|usernum','=',$search_keyword];
        return $where;
    }


	/**
	 * 查询房间的物业服务时间是否大于设置合同时间
	 * @param $data
	 *
	 * @return array
	 */
	public function checkVacancyServiceTime($data){
		$error  =   [];
		$error['status']    =   1;
		$error['msg']       =   '合同时间设置符合设置规则';

		if (!empty($data['contract_time_start'])&&!empty($data['contract_time_end'])){


			if (!empty($data['village_id']) && empty($data['single_id'])){
				$where=[];
				$where['village_id']    =   $data['village_id'];
				$where['order_type']    =   'property';
				$getMaxServiceTime = (new HouseVillageService())->getHouseNewOrderLogServiceTime($where,'service_end_time,service_start_time','service_end_time DESC');
				$getMinServiceTime = (new HouseVillageService())->getHouseNewOrderLogServiceTime($where,'service_end_time,service_start_time','service_end_time ASC');

				//合同结束时间不能小于最大的物业服务结束时间
				if (!empty($getMaxServiceTime)
					&& $getMaxServiceTime['service_end_time'] > 100
					&& $getMaxServiceTime['service_end_time'] > $data['contract_time_end']){
					$error['status'] = 0;
					$error['msg']  = '合同结束时间不能小于最大的物业服务结束时间【'.date('Y-m-d',$getMaxServiceTime['service_end_time']).'】';
					return $error;
				}
				//合同开始时间不能大于最小的物业服务结束时间
				if (!empty($getMinServiceTime)
					&& $getMinServiceTime['service_end_time'] > 100
					&& $getMinServiceTime['service_end_time'] < $data['contract_time_start']){
					$error['status']=0;
					$error['msg']='合同开始时间不能大于最小的物业服务结束时间 '.date('Y-m-d',$getMinServiceTime['service_end_time']);
					return $error;
				}
			}
			if (!empty($data['village_id']) && !empty($data['single_id'])){
				$where=[];
				$where['village_id'] = $data['village_id'];
				$where['single_id']  = $data['single_id'];
				$roomid_arr = (new HouseVillageUserVacancy())->getColumn($where,'pigcms_id');

				if (!empty($roomid_arr)){
					$where=[];
					$where['village_id'] = $data['village_id'];
					$where['order_type'] = 'property';
					$where['room_id']   = $roomid_arr;
					$getMaxServiceTime = (new HouseVillageService())->getHouseNewOrderLogServiceTime($where,'service_end_time,service_start_time','service_end_time DESC');
					$getMinServiceTime = (new HouseVillageService())->getHouseNewOrderLogServiceTime($where,'service_end_time,service_start_time','service_end_time ASC');

					//合同结束时间不能小于最大的物业服务结束时间
					if (!empty($getMaxServiceTime)&& $getMaxServiceTime['service_end_time'] >100 &&$getMaxServiceTime['service_end_time']>$data['contract_time_end']){
						$error['status']=0;
						$error['msg']='合同结束时间不能小于最大的物业服务结束时间 '.date('Y-m-d',$getMaxServiceTime['service_end_time']);
						return $error;
					}
					//合同开始时间不能大于最小的物业服务结束时间
					if (!empty($getMinServiceTime)&& $getMinServiceTime['service_end_time'] >100 &&$getMinServiceTime['service_end_time']<$data['contract_time_start']){
						$error['status']=0;
						$error['msg']='合同开始时间不能大于最小的物业服务结束时间 '.date('Y-m-d',$getMinServiceTime['service_end_time']);
						return $error;
					}
				}

			}
		}else{
			$error['status'] = 0;
			$error['msg']    = '请先设置合同时间';
			return $error;
		}
		return $error;
	}

    /**
     * @param $param
     * [
     *     'village_id' => '小区id 必传',
     *     'property_id' => '物业id 选传',
     *     'floor_id' => '单元id 选传',
     * ]
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
	public function floorLayerRooms($param) {
        $village_id = isset($param['village_id'])?$param['village_id']:0;
        $floor_id = isset($param['floor_id'])?$param['floor_id']:0;
        $houseVillageInfoService = new HouseVillageService();
        $whereLayer = [];
        $whereLayer[] = ['village_id','=',$village_id];
        if ($floor_id) {
            $whereLayer[] = ['floor_id','=',$floor_id];
        }
        $whereLayer[] = ['is_public_rental','=',0];
        $whereLayer[] = ['status','<>',4];
        $count = $houseVillageInfoService->getLayerCount($whereLayer);
        $fieldLayer = 'id,layer_name,status,single_id,floor_id,village_id';
        $oderby = ['status'=>'DESC', 'sort'=>'DESC','id'=>'ASC'];
        $list = $houseVillageInfoService->getHouseVillageLayerList($whereLayer,$fieldLayer,$oderby);
        $layer = [];
        $data = [
            'count'    => $count,
            'layer' => $layer,
        ];
        if ($list){
            $layer = $list->toArray();
            $layerRooms = [];
            if (!empty($layer)) {
                $whereVacancyList = [];
                $whereVacancyList[] = ['village_id','=',$village_id];
                if ($floor_id) {
                    $whereVacancyList[] = ['floor_id','=',$floor_id];
                }
                $whereVacancyList[] = ['is_del','=',0];
                if(isset($param['house_type']) && $param['house_type']){
                    $whereVacancyList[] = ['house_type','=',$param['house_type']];
                }
                if(isset($param['sell_status']) && $param['sell_status']){
                    $whereVacancyList[] = ['sell_status','=',$param['sell_status']];
                }
                $vacancyIdArr = (new HouseVillageUserVacancy())->getColumn($whereVacancyList,'pigcms_id');
                // ↓ 查询符合条件的所有住户
                $whereBind = [];
                $whereBind[] = ['village_id','=',$village_id];
                $whereBind[] = ['vacancy_id','in',$vacancyIdArr];
                $whereBind[] = ['status','=',1];
                $whereBind[] = ['type','in',[0,3,1,2]];
                $fieldBind = 'pigcms_id,vacancy_id,name';
                $userBindList = (new HouseVillageUserBind())->getList($whereBind,$fieldBind);
                $layerRoomUsers = [];
                if ($userBindList) {
                    $userBinds = $userBindList->toArray();
                    foreach ($userBinds as $user) {
                        if (isset($layerRoomUsers[$user['vacancy_id']])) {
                            $layerRoomUsers[$user['vacancy_id']][] = $user['name'];
                        } else{
                            $layerRoomUsers[$user['vacancy_id']] = [];
                            $layerRoomUsers[$user['vacancy_id']][] = $user['name'];
                        }
                    }
                }

                // ↓ 获取房屋对应是否欠费 并整合计费和前端展示标签色
                $db_house_new_pay_order= new HouseNewPayOrder();
                $where_money = [];
                $where_money[] = ['village_id','=',$village_id];
                $where_money[] = ['room_id','in',$vacancyIdArr];
                $where_money[] = ['is_paid','=',2];
                $where_money[] = ['is_discard','=',1];
                $fieldOrder = 'order_id,room_id,modify_money';
                $roomMoneyList = $db_house_new_pay_order->getPayLists($where_money,$fieldOrder,0);
                $layerRoomMoneys = [];
                if ($roomMoneyList) {
                    if (isset($param['property_id'])&&$param['property_id']) {
                        $digit_info = (new HousePropertyDigitService())->get_one_digit(['property_id'=>$param['property_id']]);
                    } else {
                        $digit_info = [];
                    }
                    $roomMoneys = $roomMoneyList->toArray();
                    foreach ($roomMoneys as $money) {
                        if (isset($layerRoomMoneys[$money['room_id']])) {
                            $layerRoomMoneys[$money['room_id']]['moneys'] += floatval($money['modify_money']);
                        } else {
                            $layerRoomMoneys[$money['room_id']] = [];
                            $layerRoomMoneys[$money['room_id']]['tag'] = '欠费';
                            $layerRoomMoneys[$money['room_id']]['tagColor'] = 'red';
                            $layerRoomMoneys[$money['room_id']]['moneys'] = $money['modify_money'];
                        }
                    }
                }
                // ↓ 查询符合条件的所有房间并整合上面查询到的信息到对应房屋
                $whereVacancyList = [];
                $whereVacancyList[] = ['pigcms_id','in',$vacancyIdArr];
                $fieldVacancy = 'pigcms_id,room,housesize,house_type,user_status,sell_status,status,layer_id,single_id,floor_id,village_id';
                $isAihorseCustomizedJudge = (new ConfigCustomizationService())->getAihorseCustomizedJudge();
                if($isAihorseCustomizedJudge){
                    $fieldVacancy.=',lease_time_end';
                }
                $oderby = ['sort'=>'DESC','pigcms_id'=>'DESC'];
                $house_type_arr = $houseVillageInfoService->house_type_arr;
                $user_status_arr = $houseVillageInfoService->user_status_arr;
                $sell_status_arr= $houseVillageInfoService->sell_status_arr;
                $vacancyList = $this->getVacancyList($whereVacancyList , $fieldVacancy , $oderby);
                if ($vacancyList) {
                    $vacancy = $vacancyList->toArray();
                    foreach ($vacancy as $room) {
                        $roomProps = [];
                        if (isset($layerRoomUsers[$room['pigcms_id']])) {
                            $room['users'] = implode('，', $layerRoomUsers[$room['pigcms_id']]);
                            $room['userCounts'] = count($layerRoomUsers[$room['pigcms_id']]);
                        } else {
                            $room['users'] = '无';
                            $room['userCounts'] = 0;
                        }
                        $housesize = isset($room['housesize'])&&$room['housesize']?$room['housesize']:0;
                        $roomProps[] = ['label'=> '住户', 'value'=> $room['users']];
                        $roomProps[] = ['label'=> '人数', 'value'=> $room['userCounts']];
                        $roomProps[] = ['label'=> '面积', 'value'=> floatval($housesize).'㎡'];
                        if (isset($house_type_arr[$room['house_type']])) {
                            $roomProps[] = ['label'=> '房屋用途', 'value'=> $house_type_arr[$room['house_type']]];
                        }
                        if (isset($user_status_arr[$room['user_status']])) {
                            $roomProps[] = ['label'=> '房屋状态', 'value'=> $user_status_arr[$room['user_status']]];
                        }
                        if (isset($layerRoomMoneys[$room['pigcms_id']])) {
                            if (isset($layerRoomMoneys[$room['pigcms_id']]['moneys'])&&isset($digit_info['other_digit'])) {
                                $moneys = formatNumber($layerRoomMoneys[$room['pigcms_id']]['moneys'],$digit_info['other_digit'],$digit_info['type']);
                            } elseif (isset($layerRoomMoneys[$room['pigcms_id']]['moneys'])) {
                                $moneys = formatNumber($layerRoomMoneys[$room['pigcms_id']]['moneys'],2,1);
                            }
                            if (isset($moneys)) {
                                $layerRoomMoneys[$room['pigcms_id']]['moneys'] = $moneys;
                                $roomProps[] = ['label'=> '欠费', 'value'=> $moneys.'元' ,'color'=>'red'];
                            } else {
                                $roomProps[] = ['label'=> '欠费状态', 'value'=> '欠费' ,'color'=>'red'];
                            }
                            $room['money'] = $layerRoomMoneys[$room['pigcms_id']];
                        } else {
                            $room['money'] = [];
                        }
                        if (isset($sell_status_arr[$room['sell_status']])) {
                            $roomProps[] = ['label'=> '出售状态', 'value'=> $sell_status_arr[$room['sell_status']]];
                        }
                        if(isset($room['lease_time_end']) && $room['lease_time_end']>100 ){
                            $lease_time_end_str=date('Y-m-d',$room['lease_time_end']);
                            $color='red';
                            $tmptime=time();
                            if($room['lease_time_end']>$tmptime){
                                $color='orange';
                            }
                            $tmptime=$tmptime+30*24*3600;
                            if($room['lease_time_end']>$tmptime){
                                $color='green';
                            }
                            $room['lease_tips']= ['label'=> '租赁到期时间', 'value'=> $lease_time_end_str,'color'=>$color];
                        }
                        $room['roomProps'] = $roomProps;
                        $room['allowEdit'] = true;
                        $room['allowDelete'] = true;
                        if (isset($layerRooms[$room['layer_id']])) {
                            $layerRooms[$room['layer_id']][] = $room;
                        } else {
                            $layerRooms[$room['layer_id']] = [];
                            $layerRooms[$room['layer_id']][] = $room;
                        }
                    }
                }
            }
            //  allowEdit 是否支持【编辑】 allowDelete 是否支持【删除】
            // ↓ 整合对应信息到楼层数据中
            foreach ($layer as &$item){
                $item['allowEdit'] = true;
                $item['allowDelete'] = true;
                if (isset($layerRooms[$item['id']])) {
                    $item['rooms'] = $layerRooms[$item['id']];
                } else {
                    $item['rooms'] = [];
                }
                $item['layer_name'] = $item['layer_name'] ? $item['layer_name'] : '';
            }
            $data['layer'] = $layer;
        }
        return $data;
    }

    /**
     * @param $param
     * param {
     *     'village_id' => '小区id 必传',
     *     'property_id' => '物业id 选传 建议传参',
     *     'page' => '分页时候的页数 取第2页数据传2即可',
     *     'limit' => '每页条数 默认20条',
     *     'status' => '查询不同装填房屋 不建议传4（已删除）',
     *     'is_del' => '查询是否删除状态房屋  默认未删除 0',
     * }
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoomList($param) {
        $village_id   = isset($param['village_id'])?$param['village_id']:0;
        $property_id  = isset($param['property_id'])?$param['property_id']:0;
        $page  = isset($param['page'])?$param['page']:0;
        $limit  = isset($param['limit'])?$param['limit']:0;
        $status  = isset($param['status'])?$param['status']:-1;
        $is_del  = isset($param['is_del'])?$param['is_del']:0;

        $whereRoom = [];
        $whereRoom[] = ['village_id', '=', $village_id];
        if ($status!=-1 && $status!=self::ROOM_STATUS_DELETED) {
            $whereRoom[] = ['status', '=', $status];
        } elseif ($status!=-1 && $is_del==self::ROOM_DELETE_VALUE) {
            $whereRoom[] = ['is_del', '=', self::ROOM_DELETE_VALUE];
        } else {
            $whereRoom[] = ['is_del', '<>', self::ROOM_DELETE_VALUE];
        }
        $rooms = [];
        $arr = [];
        $field = '';
        $orderBy = ['sort'=>'DESC','pigcms_id'=>'DESC'];
        $list = $this->houseVillageUserVacancyModel->getRoomList($whereRoom,$field,$orderBy,$page,$limit);
        if ($page&&$limit) {
            $count = $this->houseVillageUserVacancyModel->getCount($whereRoom);
            $arr['count'] = $count;
            $arr['page'] = $page;
            $arr['limit'] = $limit;
        }
        if ($list) {
            $rooms = $list->toArray();
            $singleIdArr = $this->houseVillageUserVacancyModel->getColumn($whereRoom,'single_id');
            $singleIdArr = array_unique($singleIdArr);
            $singleIdArr = array_values($singleIdArr);
            $db_floor = new HouseVillageFloor();
            $db_layer = new HouseVillageLayer();
            $db_single = new HouseVillageSingle();
            // 查询楼栋ID
            if(!empty($singleIdArr)) {
                $whereSingle = [];
                $whereSingle[] = ['village_id', '=', $village_id];
                $whereSingle[] = ['id', 'in', $singleIdArr];
                $singleInfoArrId = $db_single->getOneColumn($whereSingle, 'id,single_name', 'id');
            } else {
                $singleArrId = [];
            }
            // 查询单元ID
            if(!empty($singleIdArr)) {
                $whereFloor = [];
                $whereFloor[] = ['village_id', '=', $village_id];
                $whereFloor[] = ['single_id', 'in', $singleIdArr];
                $floorInfoArrId = $db_floor->getOneColumn($whereFloor,'floor_id,floor_name','floor_id');
            } else {
                $floorArrId = [];
            }
            // 查询楼层ID
            if(!empty($singleIdArr)) {
                $whereFloor = [];
                $whereFloor[] = ['village_id', '=', $village_id];
                $whereFloor[] = ['single_id', 'in', $singleIdArr];
                $layerInfoArrId = $db_layer->getOneColumn($whereFloor,'id,layer_name','id');
            } else {
                $layerInfoArrId = [];
            }
            $service_house_new_property = new HouseNewPorpertyService();
            if($property_id){
                $is_new_charge = $service_house_new_property->getTakeEffectTimeJudge($property_id);
            }else{
                $is_new_charge = false;
            }
            $house_village_service=new HouseVillageService();
            foreach ($rooms as &$item) {
                $serviceTimeInfo = $this->getRoomEndTime($item['pigcms_id'],$village_id,$property_id,$is_new_charge);
                if (isset($serviceTimeInfo['propertyStartTime']) && $serviceTimeInfo['propertyStartTime']>1){
                    $item['service_start_time'] = date('Y-m-d',$serviceTimeInfo['propertyStartTime']);
                }else{
                    $item['service_start_time'] = '未设置';
                }
                if (isset($serviceTimeInfo['propertyEndTime']) && $serviceTimeInfo['propertyEndTime']>1){
                    $item['service_end_time'] = date('Y-m-d',$serviceTimeInfo['propertyEndTime']);
                }else{
                    $item['service_end_time'] = '未设置';
                }
                $item['service_cycle'] = $item['service_start_time'].' - '.$item['service_end_time'];
                if (isset($floorInfoArrId[$item['floor_id']])) {
                    $item['floor_name'] = $floorInfoArrId[$item['floor_id']]['floor_name'];
                }
                if (isset($singleInfoArrId[$item['single_id']])) {
                    $item['single_name'] = $singleInfoArrId[$item['single_id']]['single_name'];
                }
                if (isset($layerInfoArrId[$item['layer_id']])) {
                    $item['layer_name'] = $layerInfoArrId[$item['layer_id']]['layer_name'];
                }
                if (isset($item['single_name'])&&isset($item['floor_name'])&&isset($item['layer_name'])&&isset($item['room'])) {
                    $addressParam = array('single_name'=>$item['single_name'],'floor_name'=>$item['floor_name'],'layer'=>$item['layer_name'],'room'=>$item['room']);
                    $room_diy_name =$house_village_service->word_replce_msg($addressParam,$village_id);
                    $item['address'] = $room_diy_name;
                }
            }
        }
        $arr['room'] = $rooms;
	    return $arr;
    }


    /**
     * 整合获取单个房屋的 物业服务时间开始和结束（只处理新版收费的，老版的直接使用记录数据）
     * @param int $room_id 房屋id
     * @param int $village_id 小区id
     * @param int $property_id 物业id
     * @param bool $is_new_charge 是否新版收费
     * @param false $isDelay 是否要计算允许延迟开门时间
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoomEndTime($room_id,$village_id=0,$property_id=0,$is_new_charge=true,$isDelay=false)
    {
        if (!$village_id) {
            $conditionRoom = [];
            $conditionRoom['pigcms_id'] = $room_id;
            $village_id = (new HouseVillageUserVacancy())->getColumn($conditionRoom,'village_id');
        }
        if (!$property_id) {
            $conditionVillage = [];
            $conditionVillage['village_id'] = $village_id;
            $property_id = (new HouseVillage())->getColumn($conditionVillage,'property_id');
        }
        // 存在物业id，查询下物业新版收费生效与否 确定物业服务时间截止
        $arr = [];
        $arr['village_id'] = $village_id;
        $arr['property_id'] = $property_id;
        if ($is_new_charge) {
            // 新版生效了  查询下
            $whereNewOrder = array('room_id' => $room_id, 'order_type' => 'property');
            //查当前房间的服务结束时间
            $house_new_order_log = new HouseNewOrderLog();
            $order_log = $house_new_order_log->getOne($whereNewOrder,true,'id DESC');
            if($order_log && isset($order_log['order_id']) && $order_log['order_id']<1){
                $order_log_start=$order_log;
            }else{
                //查当前房间的服务开始时间
                $order_log_start = $house_new_order_log->getOne($whereNewOrder,true,'id ASC');
            }
            $contract_time = (new HouseNewCashierService())->getContractTime($room_id);
            if ($contract_time['contract_time_start'] == 0 && empty($order_log)) {
                $arr['propertyStartTime'] = 0;
                $arr['propertyEndTime'] = 0;
            } elseif ($contract_time['contract_time_start'] == 0 && !empty($order_log)) {
                $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                $arr['propertyEndTime'] = $order_log['service_end_time'];
            } elseif ($contract_time['contract_time_start'] != 0 && empty($order_log)) {
                $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                $arr['propertyEndTime'] = $contract_time['contract_time_end'];
            } elseif ($contract_time['contract_time_start'] != 0 && !empty($order_log)) {
                if ($contract_time['contract_time_start'] < $order_log_start['service_start_time']) {
                    $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                } else {
                    $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                }
                $arr['propertyEndTime'] = $order_log['service_end_time'];
            }
            if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && $param['owe_property_open_door_day'] && $param['owe_property_open_door'] && $arr['propertyEndTime'] > 0) {
                $arr['propertyEndTime'] += intval($param['owe_property_open_door_day']) * 86400;
            } else if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && !$param['owe_property_open_door_day'] && $param['owe_property_open_door'] && $arr['propertyEndTime'] > 0) {
                // 允许延期开门 且为0 默认不限制 2035
                $arr['propertyEndTime'] = 2082729599;
            }
        }
        if($arr['propertyEndTime']<$arr['propertyStartTime']){
            $arr['propertyStartTime']= $arr['propertyEndTime'];
        }
        return $arr;
    }

    //删除房间
    public function deleteRoom($village_id=0,$del_pigcms_ids=array()){
        if($village_id<1 || empty($del_pigcms_ids)){
            return false;
        }
        $where_vacancy_bind = [];
        $where_vacancy_bind[]=['vacancy_id','in',$del_pigcms_ids];
        $where_vacancy_bind[]=['village_id','=',$village_id];
        $where_vacancy_bind[]=['type','in',[0,3]];
        $where_vacancy_bind[]=['status','in',[0,1,2]];
        $db_house_village_user_bind = new HouseVillageUserBind();
        $bind_num= $db_house_village_user_bind->getVillageUserNum($where_vacancy_bind);
        if($bind_num>0){
            $errmsg='有房间已绑定业主，无法删除！请先删除业主。';
            if(count($del_pigcms_ids)<2){
                $errmsg='房间已绑定业主，无法删除！请先删除业主。';
            }
            throw new \think\Exception($errmsg);
        }
        $nowtime=time();
        $delset = [
            'is_del' => 1,
            'del_time' => $nowtime
        ];
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['pigcms_id', 'in', $del_pigcms_ids];
        $ret=$db_house_village_user_vacancy->saveOne($where,$delset);
        return true;
    }

    public function getUserRecordList($whereArr=array(),$field='*',$page=1,$limit=20,$order='id DESC'){
        $houseVillageUserRecord = new HouseVillageUserRecord();
        $userRecord=array('list'=>array(),'count'=>0,'total_limit'=>$limit);
        if(empty($whereArr)){
            return $userRecord;
        }
        $count=$houseVillageUserRecord->getCount($whereArr);
        if($count>0){
            $userRecord['count']=$count;
        }
        $listObj=$houseVillageUserRecord->getList($whereArr,$field,$page=1,$limit=20);
        if($listObj && !$listObj->isEmpty()){
            $list=$listObj->toArray();
            if($list){
                foreach ($list as $kk=>$vv){
                    $list[$kk]['check_in_time_str']='';
                    if($vv['check_in_time']>100){
                        $list[$kk]['check_in_time_str']=date('Y-m-d H:i:s',$vv['check_in_time']);
                    }
                    $list[$kk]['add_time_str']='';
                    if($vv['add_time']>100){
                        $list[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                    }
                    $list[$kk]['type_str']='';
                    if($vv['type']==0 || $vv['type']==3){
                        $list[$kk]['type_str']='房主';
                    }elseif($vv['type']==1){
                        $list[$kk]['type_str']='家属';
                    }elseif($vv['type']==2){
                        $list[$kk]['type_str']='租客';
                    }
                }
                $userRecord['list']=$list;
            }
        }
        return $userRecord;
    }

    public function getRoomIcCardList($whereArr=array(),$field='*',$page=1,$limit=20,$order='id DESC'){
        $houseVillageVacancyIccard = new HouseVillageVacancyIccard();
        $icCardList=array('list'=>array(),'count'=>0,'total_limit'=>$limit);
        if(empty($whereArr)){
            return $icCardList;
        }
        $count=$houseVillageVacancyIccard->getCount($whereArr);
        if($count>0){
            $icCardList['count']=$count;
        }
        $listObj=$houseVillageVacancyIccard->getList($whereArr,$field,$page=1,$limit=20);
        if($listObj && !$listObj->isEmpty()){
            $list=$listObj->toArray();
            if($list){
                foreach ($list as $kk=>$vv){
                    $list[$kk]['add_time_str']='';
                    if($vv['add_time']>100){
                        $list[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                    }

                }
                $icCardList['list']=$list;
            }
        }
        return $icCardList;
    }

    public function  deleteRoomIcCard($idd=0,$village_id=0){
        if($idd<1 || $village_id<1){
            return false;
        }
        $param=array('id'=>$idd,'village_id'=>$village_id);
        $res = invoke_cms_model('House_village_user_vacancy/vacancy_iccard_del', $param);
        return !empty($res) && isset($res['retval']) ? $res['retval']: false;
    }
}