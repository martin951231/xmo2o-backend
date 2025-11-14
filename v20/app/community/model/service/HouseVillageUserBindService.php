<?php
/**
 * 小区和用户之间关系表
 * @datetime 2020/07/14 14:23
**/

namespace app\community\model\service;

use app\common\model\service\export\ExportService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\community\model\db\Area;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseVillage;
use app\community\model\service\HouseVillageService;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageDataConfig;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageFloorType;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillagePaymentStandardBind;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePropertyPaylist;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserAttribute;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserLabel;
use app\community\model\db\HouseVillageUserVacancy;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
use think\facade\Cache;

class HouseVillageUserBindService
{
    public $houseVillageUserBindModel  = '';
    public function __construct()
    {
        $this->houseVillageUserBindModel = new HouseVillageUserBind();
    }

    public function getFind()
    {
        $dbHouseVillageUserBind = new HouseVillageUserBind();

    }

    /**
     * 获取小区用户数量
     * @author lijie
     * @date_time 2020/08/03 13:20
     * @param $where
     * @return array|\think\Model|null
     */
    public function getUserCount($where)
    {
        $count = $this->houseVillageUserBindModel->getVillageUserNum($where);
        return $count;
    }

    public function getUserCountByBirth($where=[],$field=true)
    {
        $db_house_village_user_bind = new HouseVillageUserBind();
        $data = $db_house_village_user_bind->getList($where,$field);
        $level1 = $level2 = $level3 = $level4 = 0;
        if($data){
            foreach ($data as $v){
                if(empty($v['birth']))
                    continue;
                $age = $this->birthday($v['birth']);
                if($age < 18){
                    $level1+=1;
                }
                if(18 <= $age && $age <= 40){
                    $level2 += 1;
                }
                if($age >= 41 && $age <= 60){
                    $level3 += 1;
                }
                if($age > 60){
                    $level4 += 1;
                }
            }
        }
        return ['level1'=>$level1,'level2'=>$level2,'level3'=>$level3,'level4'=>$level4];
    }

    public function birthday($mydate){
        $birth=$mydate;
        list($by,$bm,$bd)=explode('-',$birth);
        $cm=date('n');
        $cd=date('j');
        $age=date('Y')-$by-1;
        if ($cm>$bm || $cm==$bm && $cd>$bd) $age++;
        return $age;
    }

    public function getVillageUserBindNum($where)
    {
        $count = $this->houseVillageUserBindModel->getVillageUserBindNum($where);
        return $count;
    }

    /**
     *
     * @param $where
     * @param string $field
     * @return float
     */
    public function getSumBill($where,$field)
    {
        $house_village_user_bind = new HouseVillageUserBind();
        $data = $house_village_user_bind->getSumBill($where,$field);
        return $data;
    }

    /**
     * 用户信息
     * @author lijie
     * @date_time 2020/10/23
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getUserBindInfo($where,$field=true)
    {
        $house_village_user_bind = new HouseVillageUserBind();
        $service_house_village = new HouseVillageService();
        $dataObj = $house_village_user_bind->getUserBindInfo($where,$field);
        if(!empty($dataObj) && !$dataObj->isEmpty()){
            $data=$dataObj->toArray();
            $data['address']=$service_house_village->getSingleFloorRoom($data['single_id'],$data['floor_id'],$data['layer_id'],$data['vacancy_id'],$data['village_id']);
            $data['authentication_field'] = unserialize($data['authentication_field']);
            $data['sex'] = isset($data['authentication_field']['sex'])?$data['authentication_field']['sex']['value']:'';
            $data['birthday'] = isset($data['authentication_field']['birthday'])?$data['authentication_field']['birthday']['value']:'';
            $data['nation'] = isset($data['authentication_field']['nation'])?$data['authentication_field']['nation']['value']:'';
            $native_place_arr = explode('#',$data['authentication_field']['native_place']['value']);
            $areaDb=new Area();
            $native_place='';
            if($native_place_arr && $native_place_arr[0]){
                $tmpArea=$areaDb->getOne(array('area_id'=>$native_place_arr[0]),'area_id,area_name');
                $native_place=$tmpArea && !$tmpArea->isEmpty() ? $tmpArea['area_name']:'';
            }
            if($native_place_arr && $native_place_arr[1]){
                $tmpArea=$areaDb->getOne(array('area_id'=>$native_place_arr[1]),'area_id,area_name');
                $native_place .=$tmpArea && !$tmpArea->isEmpty() ? $tmpArea['area_name']:'';
            }
            $data['native_place'] = $native_place;
	   	    $data['authentication_field']['native_place']['value'] = $native_place;
        }else{
            $data=array();
        }
        return $data;
    }

    /**
     * 获取user_bind信息
     * @author lijie
     * @date_time 2020/11/03
     * @param $where
     * @param bool|string $field
     * @return array|\think\Model|null
     */
    public function getBindInfo($where,$field=true)
    {
        $house_village_user_bind = new HouseVillageUserBind();
        $data = $house_village_user_bind->getOne($where,$field);
        return $data;
    }

    /**
     * 街道用户user_bind列表
     * @author lijie
     * @date_time 2020/10/24
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @param string $order
     * @return mixed
     */
    public function getUserBindList($where,$field=true,$page,$limit,$order='hvb.pigcms_id DESC')
    {

        $service_house_village = new HouseVillageService();
        $data = $this->houseVillageUserBindModel->getStreetUserBind($where,$field,$page,$limit,$order);

        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['address']=$service_house_village->getSingleFloorRoom($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
                if ($data[$k]['address']!='暂无房间(数据异常)'){
                    $data[$k]['address']=$v['village_name'].$data[$k]['address'];
                }
                $data[$k]['authentication_field'] = unserialize($v['authentication_field']);
                $data[$k]['sex'] = isset($v['authentication_field']['sex'])?$v['authentication_field']['sex']['value']:'';
                $data[$k]['birthday'] = isset($v['authentication_field']['birthday'])?$v['authentication_field']['birthday']['value']:'';
                $data[$k]['nation'] = isset($v['authentication_field']['nation'])?$v['authentication_field']['nation']['value']:'';
                if($page>0 && isset($v['phone']) && !empty($v['phone'])){
                    $data[$k]['phone']=phone_desensitization($v['phone']);
                }
            }
        }
        $count = $this->houseVillageUserBindModel->getStreetUserBindCount($where);
        $res['list'] = $data;
        $res['count'] = $count;
        return $res;
    }

    /**
     * 获取街道用户数量
     * @author lijie
     * @date_time 2020/01/06
     * @param $where
     * @return mixed
     */
    public function getStreetUserBindCount($where)
    {
        $count = $this->houseVillageUserBindModel->getStreetUserBindCount($where);
        return $count;
    }


    /**
     * 修改user_bind用户信息
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveUserBind($where,$data)
    {
        $res = $this->houseVillageUserBindModel->saveOne($where,$data);
        return $res;
    }

    /**
     * Notes: 获取小区列表
     * @author lijie
     * @date_time 2020/11/21
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array|\think\Model|null
     * @date_time: 2020/11/23 11:12
     */
    public function getHouseUserBindList($where,$field=true,$page=1,$limit=20,$order='pigcms_id DESC')
    {
        $db_house_village_user_bind  = new HouseVillageUserBind();
        $data = $db_house_village_user_bind->getLimitUserList($where,$page,$field,$order,$limit);
        return $data;
    }

    /**
     * 获取房间下住户信息
     * @author lijie
     * @date_time 2020/01/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $vacancy_id
     * @return array|\think\Model|null
     */
    public function getRoomUserBindList($where,$field=true,$page=1,$limit=20,$order='pigcms_id DESC',$vacancy_id)
    {
        $db_house_village_user_bind  = new HouseVillageUserBind();
        $service_house_village = new HouseVillageService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $HouseVillage = new HouseVillage();
        $room_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$vacancy_id]);
        $street_id=0;
        $community_id=0;
        if(isset($room_info['village_id'])){
            $house_village_info=$HouseVillage->getOne($room_info['village_id'],'street_id,community_id');
            if ($house_village_info && !$house_village_info->isEmpty()){
                $street_id=$house_village_info['street_id'];
                $community_id=$house_village_info['community_id'];
            }
        }
        $data = $db_house_village_user_bind->getLimitUserList($where,$page,$field,$order,$limit);
        $a = $b =  $c = $d = $e =  $f = $g = $h =  0;
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['street_id'] =$street_id;
                $data[$k]['community_id'] =$community_id;
                $data[$k]['address'] = $service_house_village->getSingleFloorRoom($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
                if($v['type'] == 0 || $v['type'] == 3){
                    $data[$k]['relation'] = '业主';
                    $a += 1;
                }elseif($v['type'] == 2){
                    if($room_info['house_type'] == 1)
                        $data[$k]['relation'] = '租客';
                    else
                        $data[$k]['relation'] = '租客/员工';
                    $h += 1;
                }else{
                    switch ($v['relatives_type']){
                        case 1:
                            $data[$k]['relation'] = '配偶';
                            $b += 1;
                            break;
                        case 3:
                            $data[$k]['relation'] = '子女';
                            $c += 1;
                            break;
                        case 4:
                            $data[$k]['relation'] = '亲朋好友';
                            $d += 1;
                            break;
                        case 5:
                            $data[$k]['relation'] = '负责人';
                            $e += 1;
                            break;
                        case 6:
                            $data[$k]['relation'] = '人事';
                            $f += 1;
                            break;
                        case 7:
                            $data[$k]['relation'] = '财务';
                            $g += 1;
                            break;
                        default:
                            $a += 1;
                            $data[$k]['relation'] = '业主';
                    }
                }
            }
        }
        $res['list'] = $data;
        $res['house_type'] = $room_info['house_type'];
        $res['count'] = array($a,$b,$c,$d,$e,$f,$g,$h);
        return $res;
    }

    /**
     * 更新用户费用
     * @author lijie
     * @date_time 2020/11/30
     * @param $where
     * @param $field
     * @param $money
     * @return mixed
     */
    public function updateUserFee($where,$field,$money)
    {
        $db_house_village_user_bind  = new HouseVillageUserBind();
        $res = $db_house_village_user_bind->updateFee($where,$field,$money);
        return $res;
    }

    /**
     * 获取用户列表不分页
     * @param $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field=true)
    {
        $db_house_village_user_bind  = new HouseVillageUserBind();
        $data = $db_house_village_user_bind->getList($where,$field);
        return $data;
    }

    public function getVillageBindInfo($where=[],$field=true)
    {
        $db_house_village_user_bind = new HouseVillageUserBind();
        $data = $db_house_village_user_bind->getInfo($where, $field);
        return $data;
    }

    /**
     * 查询街道业主
     * @author: liukezhu
     * @date : 2021/11/8
     * @param $where
     * @param bool $field
     * @param $order
     * @param $page
     * @param $limit
     * @return array
     */
    public function getStreetUserBindList($where, $field = true, $order, $page, $limit)
    {
        $db_house_village_user_bind = new HouseVillageUserBind();
        $list = $db_house_village_user_bind->getUserBindList($where, $field, $order, $page, $limit);
        if ($list && !$list->isEmpty()) {
            $user_identity = [
                0 => '业主',
                1 => '家属',
                2 => '租客',
                3 => '业主',
            ];
            $list = $list->toArray();
            foreach ($list as &$v) {
                $v['user_identity'] = isset($user_identity[$v['type']]) ? $user_identity[$v['type']] : '';
            }
        }
        $count = $db_house_village_user_bind->getUserBindCount($where);
        return ['list' => $list, 'count' => $count];
    }

    // 添加虚拟业主 如果存在返回当前存在业主
    public function addVirtualHomeowner($roomId,$roomInfo=[]) {
        // 查询下该房间的房主
        $whereHomeowner = [];
        $whereHomeowner[] = ['type','in',[0,3]];
        $whereHomeowner[] = ['status','=',1];
        $whereHomeowner[] = ['vacancy_id','=',$roomId];
        $homeowner = $this->getBindInfo($whereHomeowner);
        if(!empty($homeowner)||(isset($homeowner['pigcms_id'])&&$homeowner['pigcms_id'])) {
            return $homeowner['pigcms_id'];
        } else {
            if (!$roomInfo || !isset($roomInfo['village_id']) || !isset($roomInfo['single_id']) || !isset($roomInfo['floor_id']) || !isset($roomInfo['layer_id'])) {
                $whereRooms = [];
                $whereRooms[] = ['pigcms_id','=', $roomId];
                $roomFields = "pigcms_id,uid,name,phone,floor_id,village_id,layer,room,housesize,single_id,layer_id,usernum,property_number,room,room_number";
                $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
                $roomInfo = $houseVillageUserVacancyService->getUserVacancyInfo($whereRooms, $roomFields);
            }
            // 如果没有业主 添加虚拟业主
            $dataVirtualHomeowner = [
                'village_id' => $roomInfo['village_id'],
                'name' => '',
                'phone' => '',
                'uid' => 0,
                'vacancy_id' => $roomId,
                'single_id' => $roomInfo['single_id'],
                'floor_id' => $roomInfo['floor_id'],
                'layer_id' => $roomInfo['layer_id'],
                'status' => 1,
                'add_time' => time(),
            ];
            $pigcms_id = $this->addUserBindOne($dataVirtualHomeowner);
            return $pigcms_id;
        }
    }
    public function getUserRoomInfoByUid($uid=0,$xtype=0,$bind_user_id=0) {
        $uRoomInfos=array('bind_user_id'=>$bind_user_id,'roominfo'=>array(),'roomlist'=>array());
        if($uid>0){
            $vacancyList = [];
            $whereArr=array(['ub.uid','=',$uid]);
            $whereArr[]=array('ub.status','=',1);
            $whereArr[]=array('ub.vacancy_id','>',0);
            if($xtype==1){
                //业主的
                $whereArr[]=array('ub.type','in',array(0,3));
            }else if($xtype==2){
                //家人亲属
                $whereArr[]=array('ub.type','=',1);
            }else if($xtype==3){
                //租客
                $whereArr[]=array('ub.type','=',2);
            }else{
                $whereArr[]=array('ub.type','<',4);
            }
            $whereArr[]=array('uv.is_del','=',0);
            if($bind_user_id>0){
                $whereArr[]=array('ub.pigcms_id','=',$bind_user_id);
            }
            $houseVillageDb=new HouseVillage();
            $house_village_service = new HouseVillageService();
            $fieldStr='ub.pigcms_id as bind_user_id,ub.vacancy_id,ub.village_id,ub.uid,ub.type,uv.single_id,uv.floor_id,uv.layer_id,uv.room,uv.room_number,uv.housesize,uv.house_type';
            $vacancyListObj=$this->houseVillageUserBindModel->getUserBindVacancyList($whereArr,$fieldStr,'ub.pigcms_id desc',0,100);
            if($vacancyListObj && !$vacancyListObj->isEmpty()){
                $vacancyList = $vacancyListObj->toArray();
                $houseVillageArr=array();
                foreach ($vacancyList as $kk=>&$vvv){
                    $vacancyList[$kk]['village_name']='';
                    $vacancyList[$kk]['village_address']='';
                    $vacancyList[$kk]['lng']='';
                    $vacancyList[$kk]['lat']='';
                    $vacancyList[$kk]['village_logo']='';
                    
                    if(isset($houseVillageArr[$vvv['village_id']])){
                        $vacancyList[$kk]['village_name']=$houseVillageArr[$vvv['village_id']]['village_name'];
                        $vacancyList[$kk]['village_address']=$houseVillageArr[$vvv['village_id']]['village_address'];
                        $vacancyList[$kk]['lng']=$houseVillageArr[$vvv['village_id']]['long'];
                        $vacancyList[$kk]['lat']=$houseVillageArr[$vvv['village_id']]['lat'];
                        $vacancyList[$kk]['village_logo']=$houseVillageArr[$vvv['village_id']]['village_logo'];
                    }else{
                        $whereHArr=array('village_id'=>$vvv['village_id']);
                        $houseVillage=$houseVillageDb->getInfo($whereHArr,'village_name,village_address,`long`,lat,village_logo');
                        if($houseVillage && !$houseVillage->isEmpty()){
                            $houseVillage = $houseVillage->toArray();
                            $houseVillageArr[$vvv['village_id']]=$houseVillage;
                            $vacancyList[$kk]['village_name']=$houseVillage['village_name'];
                            $vacancyList[$kk]['village_address']=$houseVillage['village_address'];
                            $vacancyList[$kk]['lng']=$houseVillage['long'];
                            $vacancyList[$kk]['lat']=$houseVillage['lat'];
                            $vacancyList[$kk]['village_logo']=$houseVillage['village_logo'];
                        }
                    }
                    $vvv['house_type_text'] = HouseVillageUserVacancy::$houseType[$vvv['house_type']] ?? '未知';
                    if($vacancyList[$kk]['village_logo']){
                        $vacancyList[$kk]['village_logo']= replace_file_domain($vacancyList[$kk]['village_logo']);
                    }
                    $house_address_txt = $house_village_service->getSingleFloorRoom($vvv['single_id'], $vvv['floor_id'], $vvv['layer_id'], 0, $vvv['village_id']);
                    $vacancyList[$kk]['house_address']=$house_address_txt;
                }
            }
            $uRoomInfos['roomlist']=$vacancyList;
            if($bind_user_id>0 && $vacancyList){
                $uRoomInfos['roominfo']=$vacancyList['0'];
            }
        }
        return $uRoomInfos;
    }

    public function addUserBindOne($data) {
        // 初始化 数据层
        $pigcms_id = $this->houseVillageUserBindModel->addOne($data);
        return $pigcms_id;
    }

    public function getUserBindRawInfo($whereRaw,$field=true)
    {
        $data = $this->houseVillageUserBindModel->getRawOne($whereRaw,$field);
        return $data;
    }

    //查询用户手机号
    public function getUserBindInfos($value,$type){
        $data=[];
        $where[] = ['status','=',1];
        $where[] = ['type','in',[0,3]];
        if($type == 'owner'){
            $where[] = ['pigcms_id','=',$value];
        }elseif ($type == 'room'){
            $where[] = ['vacancy_id','=',$value];
        }
        if(empty($where)){
            throw new \think\Exception("缺少必要条件");
        }
        $field='pigcms_id,name,phone';
        $userBind = (new HouseVillageUserBind())->getOne($where,$field,'type asc,pigcms_id asc');
        if($userBind && !$userBind->isEmpty()){
            $data=$userBind->toArray();
        }
        return ['status'=>($data ? 1 : 0),'data'=>$data];
    }

    public function getCheckUserBindInfo($whereArr, $pigcms_id = 0)
    {
        $field = 'pigcms_id,name,phone,type';
        $isGetNew = true;
        $houseVillageUserBind = new HouseVillageUserBind();
        if ($pigcms_id > 0) {
            $whereTmpArr[] = array('pigcms_id', '=', $pigcms_id);
            $userBindObj = $houseVillageUserBind->getOne($whereTmpArr, $field);
            if (empty($userBindObj) || $userBindObj->isEmpty()) {
                $isGetNew = false;
            }
        } else {
            $pigcms_id = 0;
        }
        if ($isGetNew) {
            $userBindObj = $houseVillageUserBind->getLimitUserList($whereArr, 0, $field, 'vacancy_id desc,type asc');
            if ($userBindObj && !$userBindObj->isEmpty()) {
                $userBinds = $userBindObj->toArray();
                $pigcms_idArr = array();
                foreach ($userBinds as $ub) {
                    $pigcms_idArr[$ub['type']] = $ub['pigcms_id'];
                }
                arsort($pigcms_idArr);
                if (isset($pigcms_idArr['0'])) {
                    $pigcms_id = $pigcms_idArr['0'];
                } elseif (isset($pigcms_idArr['3'])) {
                    $pigcms_id = $pigcms_idArr['3'];
                } elseif (isset($pigcms_idArr['1'])) {
                    $pigcms_id = $pigcms_idArr['1'];
                } else {
                    foreach ($userBinds as $key => $uv) {
                        $pigcms_id = $uv;
                        break;
                    }
                }
            }
        }
        return $pigcms_id;
    }
    
    public function exportUserDataForLouYu($village_id,$village_name)
    {
        set_time_limit(0);
        ini_set('memory_limit','-1');;

//        $start = microtime(true);
        
        $filePath = root_path() . '../static/file/village/owner_export_to_louyu.xlsx';
        Calculation::getInstance()->setCalculationCacheEnabled(false);// 禁用公式的预计算
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rooms = Db::table('pigcms_house_village_user_bind')
            ->pk('pigcms_id')
            ->where('village_id',$village_id)
            ->where('status',1)
            ->whereIn('type',[0,3])
            ->field('pigcms_id,village_id,uid,usernum,name,phone,single_id,floor_id,layer_id,vacancy_id,id_card,address')
            ->cursor();
        //先组装 请使用楼宇/楼层/房间的组合，多个房间用半角逗号隔开（选填）
        $userDatas = [];
        foreach($rooms as $row=>$room){
            $single_id = $room['single_id'];
            $singleName = Cache::get('single_id:'.$single_id);
            if (empty($singleName)){
                $result= Db::table('pigcms_house_village_single')->where('id',$single_id)->field('id,single_name')->find();
                $singleName = $result['single_name'];
                Cache::set('single_id:'.$single_id,$singleName,3600);
            }
            $floor_id = $room['floor_id'];
            $floorName = Cache::get('floor_id:'.$floor_id);
            if (empty($floorName)){
                $result= Db::table('pigcms_house_village_floor')->where('floor_id',$floor_id)->field('floor_id,floor_name')->find();
                $floorName = $result['floor_name'];
                Cache::set('floor_id:'.$floor_id,$floorName,3600);
            }
            $layer_id = $room['layer_id'];
            $layerName = Cache::get('layer_id:'.$layer_id);
            if (empty($layerName)){
                $result= Db::table('pigcms_house_village_layer')->where('id',$layer_id)->field('id,layer_name')->find();
                $layerName = $result['layer_name'];
                Cache::set('layer_id:'.$layer_id,$layerName,3600);
            }
            $vacancy_id = $room['vacancy_id'];
            $vacancyName = Cache::get('vacancy_id:'.$vacancy_id);
            if (empty($vacancyName)){
                $result= Db::table('pigcms_house_village_user_vacancy')->where('pigcms_id',$vacancy_id)->field('pigcms_id,room')->find();
                $vacancyName = $result['room'];
                Cache::set('vacancy_id:'.$vacancy_id,$vacancyName,3600);
            }

            $uid = $room['uid'];
            if (empty($uid) && !empty($room['phone'])){
                $uid = $room['phone'];
            }
            if (empty($uid)){
                $uid = $room['pigcms_id'];
            }
            if ($singleName){
                $userDatas[$uid]['roomList'][$row] = $singleName.'['.$floorName.']/'.$layerName.'/'.$vacancyName;  
            }else{
                $userDatas[$uid]['roomList'][$row] = '';
            }
          
            $userDatas[$uid]['room'] = $room;

//              
        }
        unset($rooms);
        $row = 0;
        $startRow = 4;
        foreach ($userDatas as $k=>$user){
            $roomslist = implode(',',array_values($user['roomList']));

            $currentRow = $row + $startRow;
            $column = 'A';
            $data = [
                'usertype'=>'个人', //业主类型
                'username' => $user['room']['name'], //业主姓名
                'lianxiren'=>$user['room']['name'], //联系人
                'phone'=>$user['room']['phone'] ? strval($user['room']['phone']):'', //联系电话
                'id_card'=>$user['room']['id_card'] ? strval($user['room']['id_card']):'', //证件号码
                'usernum'=>$user['room']['usernum']? strval($user['room']['usernum']):'', //业主编号
                'address'=>$user['room']['address'], //联系人通讯地址
                'villagename'=>$village_name, //绑定小区
                'roomslist'=>ltrim($roomslist,','), //绑定房源 【1栋[1单元]/1楼/101,1栋[1单元]/1楼/102 】

            ];

            foreach ($data as  $value){
                $worksheet->setCellValueExplicit($column++ . $currentRow, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
               /*
                $cell = $worksheet->getCell($column++ . $currentRow);
                $cell->setValue($value);
               */
            }
            //手动释放
            $worksheet->getCell($column.$currentRow)->setValue(null);
            $row++;
        }

        $filename =   'owner_export_to_louyu-'.date('YmdHi').'.xlsx';
        (new ExportService())->phpSpreadsheet($filename, $spreadsheet);
        $arr = (new HouseNewParkingService())->downloadExportFile($filename);

//        $end = microtime(true);
//        fdump(['exportUserDataForLouYu 总耗时'=>($end - $start),'消耗内存'=>round(memory_get_usage()/1024/1024, 2).'MB','峰值'=>round(memory_get_peak_usage()/1024/1024, 2).'MB'],'nnnnnnnnnn');
        return $arr;
    } 
    
    public function exportUserJsDataForLouYu($village_id,$village_name,$type)
    {
        set_time_limit(0);
        ini_set('memory_limit','-1');;
        $filePath = root_path() . '../static/file/village/owner_home_or_tenant_to_loyu.xlsx';
        Calculation::getInstance()->setCalculationCacheEnabled(false);// 禁用公式的预计算
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        /**
         * type
         * 业主 = 0,3
         * 家人 = 1
         * 租客 = 2
         */

        $shenfen = '';
        if ($type == 1){
            $shenfen = '家属';
        }elseif($type == 2){
            $shenfen = '租客';
        }
        
        $rooms = Db::table('pigcms_house_village_user_bind')
            ->pk('pigcms_id')
            ->where('village_id',$village_id)
            ->where('status',1)
            ->where('type',$type)
            ->field('pigcms_id,village_id,uid,usernum,name,phone,single_id,floor_id,layer_id,vacancy_id,id_card,address,parent_id')
            ->cursor();
      
        $startRow = 2;
        foreach($rooms as $row=>$room){
            $currentRow = $row + $startRow;
            $column = 'A';

            $parent_id = $room['parent_id'];
            $ownerName = Cache::get('parent_id:'.$parent_id); //业主名称
            if (empty($ownerName)){
                $result= Db::table('pigcms_house_village_user_bind')->where('pigcms_id',$parent_id)->field('pigcms_id,name')->find();
                $ownerName = $result['name'];
                Cache::set('single_id:'.$parent_id,$ownerName,3600);
            }

            $data = [
                'ownerName'=>$ownerName, //租客名称
                'single_name' => $room['name'], //员工姓名
                'phone' => $room['phone'] ? strval($room['phone']):'', //员工电话
                'shenfen'=>$shenfen, //员工身份
                'email'=>'', //邮箱
                'lougao'=>'身份证', //证件类型（身份证、港澳台、护照）
                'baojia'=>$room['id_card'] ? strval($room['id_card']):'', //证件号码
                'dijia'=>'', //性别（男/女）
            ];
            
            foreach ($data as  $value){
                $worksheet->setCellValueExplicit($column++ . $currentRow, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                /*
                $cell = $worksheet->getCell($column++ . $currentRow);
                $cell->setValueExplicit($column++ . $currentRow, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
               // $cell->setValue($value);
                */
            }
            //手动释放
            $worksheet->getCell($column.$currentRow)->setValue(null);
        }
        
        $filename =   'owner_home_or_tenant_to_loyu-'.date('YmdHi').'.xlsx';
        (new ExportService())->phpSpreadsheet($filename, $spreadsheet);
        $arr = (new HouseNewParkingService())->downloadExportFile($filename);
        return $arr;
    }
    
    /**
     * 导出用户列表
     */
    public function exportUserData($village_id,$village_name,$property_id,$data)
    {
        set_time_limit(0);
        fdump_api(['village_id'=>$village_id,'village_name'=>$village_name,'property_id'=>$property_id,$data],'00exportUserData',1);
        ini_set('memory_limit','-1');
        $find_type = $data['find_type'] ?? '';
        $find_value = $data['find_value'] ?? '';
        $is_platform = ($data['is_platform'] ?? 0) + 0;
        $property_endtime_start = $data['property_endtime_start'] ?? '';
        $property_endtime_end = $data['property_endtime_end'] ?? '';
        $is_real = $data['is_real'] ?? '';
        $single_id = $data['single_id'] ?? '';
        $floor_id = $data['floor_id'] ?? '';
        $vacancy_id = $data['vacancy_id'] ?? '';
        $layer_id = $data['layer_id'] ?? '';
        $is_bind_weixin=isset($data['is_bind_weixin']) ? intval($data['is_bind_weixin']):0;
        $source_comefrom=isset($data['source_comefrom']) ? intval($data['source_comefrom']):0;
        $where[] = ['ub.status','=',1];//用户绑定状态
        $whereRaw = 'ub.village_id ='.$village_id;
        if($find_value && $find_type == 1){//物业编号用户编号查询
            $where[] = ['ub.usernum|ub.bind_number','like', '%' . $find_value . '%'];
        }
        if($find_value && $find_type == 2){//用户姓名查询
            $where[] = ['ub.name','like', '%' . str_replace(' ','',$find_value) . '%'];
        }
        if($find_value && $find_type == 3){//手机号查询
            $where[] = ['ub.phone','like', '%' . $find_value . '%'];
        }
        if($find_value && $find_type == 4){//地址查询
            $where[] = ['ub.address','like', '%' . $find_value . '%'];
        }
        if($is_platform == 1){
            $where[] = ['ub.uid','>',0];
        }elseif ($is_platform == 2) {
            $where[] = ['ub.uid','=',0];
        }
        //根据物业服务时间查询
        /*
        if($property_endtime_start && $property_endtime_end){
            $start_time = strtotime($property_endtime_start);
            $end_time = strtotime($property_endtime_end.'23:59:59');
            $where[] = ['ub.property_endtime','between',[$start_time,$end_time]];
        }else if($property_endtime_start){
            $property_endtime_start = strtotime($property_endtime_start);
            $where[] = ['ub.property_endtime','>=',$property_endtime_start];
        }else if($property_endtime_end){
            $property_endtime_end = strtotime($property_endtime_end.'23:59:59');
            $where[] = ['ub.property_endtime','<',$property_endtime_end];
        }
        */
        //是否为真实用户
        if($is_real == 1){
            $whereRaw .= "(ub.uid not in (0,'') OR ub.phone not in (0,'') OR ub.name not in (0,''))";
        }elseif($is_real == 2){
            $whereRaw .= " AND (ub.uid in (0,'') AND ub.phone in (0,'') AND ub.name in (0,''))";
        }
        if($single_id){
            $where[] = ['ub.single_id','=',$single_id];
        }
        if($floor_id){
            $where[] = ['ub.floor_id','=',$floor_id];
        }
        if($vacancy_id){
            $where[] = ['ub.vacancy_id','=',$vacancy_id];
        }else{
            $where[] = ['ub.vacancy_id','>',$vacancy_id];
        }
        if($layer_id){
            $where[] = ['ub.layer_id','=',$layer_id];
        }
        //$where[] = ['ub.parent_id','=',0];
        $where[] = ['ub.type','in',[0,3]];

        //校验是否开启新版物业收费
        $service_house_new_property = new HouseNewPorpertyService();
        $is_new_charge = $service_house_new_property->getTakeEffectTimeJudge($property_id);
        $param = [
            'is_bind_weixin' => $is_bind_weixin,//是否绑定微信(1:是，2：否)
            'source_comefrom' => $source_comefrom,//注册来源source_comefrom 1公众号 2小程序3小区自动注册4其他
            'is_new_charge' => $is_new_charge,
            'property_id' => $property_id
        ];
        try {
            fdump_api(['line'=>__LINE__],'00exportUserData',1);
            $user_info_list = $this->getUserList($village_id,$where,$whereRaw,$param);
            fdump_api(['line'=>__LINE__],'00exportUserData',1);
            $fileUrl = $this->createExcel($user_info_list,$village_name,$is_new_charge,$property_id);
            fdump_api(['line'=>__LINE__],'00exportUserData',1);
            return $fileUrl;
        } catch (\Exception $e) {
            fdump_api(['line'=>__LINE__,'error_msg'=>$e->getMessage()],'00exportUserData',1);
            return null;
        }
    }

    // 1住宅 2商铺 3办公
    public $house_type_arr = [1=>'住宅',2=>'商铺',3=>'办公'];
    public function getUserList($village_id,$where,$whereRaw,$param)
    {
        if (!$village_id) {
            return null;
        }
        $is_bind_weixin = $param['is_bind_weixin'];
        $source_comefrom = $param['source_comefrom'];
        //是否绑定微信
        if($is_bind_weixin == 1){
            $whereRaw .= ' AND ((us.openid IS NOT NULL AND us.openid!="") OR (us.wxapp_openid IS NOT NULL AND us.wxapp_openid!=""))';
        }elseif($is_bind_weixin == 2){
            $whereRaw .= ' AND (ub.uid<1 OR ((us.openid IS NULL OR us.openid="") AND (us.wxapp_openid IS NULL OR us.wxapp_openid="")))';
        }
        //注册来源source_comefrom 1公众号 2小程序3小区自动注册4其他
        if($source_comefrom == 1){
            $where[] = ['ub.uid','>',0];
            $where[] = ['us.source','=','weixin'];
        }elseif($source_comefrom == 2){
            $where[] = ['ub.uid','>',0];
            $where[] = ['us.source','in',['wxapp','wxcapp']];
        }elseif($source_comefrom == 3){
            $where[] = ['ub.uid','>',0];
            $where[] = ['us.source','=','houseautoreg'];
        }elseif($source_comefrom == 5){
            $where[] = ['ub.uid','>',0];
            $where[] = ['us.source','=','wap'];
        }elseif($source_comefrom == 4){
            $whereRaw .= ' AND (ub.uid<1 OR (us.source not in ("weixin","wxapp","wxcapp","houseautoreg","wap")))';
        }
        
        $houseVillageUserBind = new HouseVillageUserBind();
        $field = 'ub.pigcms_id,ub.uid,us.source,us.openid as us_openid,us.wxapp_openid as us_wxapp_openid,ub.single_id,ub.floor_id,ub.layer_id,ub.vacancy_id,ub.layer_num,ub.room_addrss,ub.attribute,ub.add_time,ub.type,ub.parent_id,ub.property_starttime,ub.property_endtime,ub.authentication_field,ub.bind_number,ub.usernum,ub.name,ub.phone,ub.id_card,ub.ic_card,ub.housesize,ub.water_price,ub.electric_price,ub.gas_price,ub.park_price';
        $user_list = $houseVillageUserBind->getUserBind($where,$whereRaw,$field);

        $service_house_village = new HouseVillageService();
        $attribute_list = (new HouseVillageUserAttribute())->getList(['village_id'=>$village_id],'pigcms_id,name');
        $attribute_list = $attribute_list ? $attribute_list->toArray() : [];
        $attribute_list = $attribute_list ? array_column($attribute_list,'name','pigcms_id') : [];

        //小区信息
        $village_info = (new HouseVillageInfo())->getOne(['village_id'=>$village_id],'contract_time_start,contract_time_end');
        
        //楼栋数组
        $sing_arr = (new HouseVillageSingle())->getList(['village_id'=>$village_id],'single_name,contract_time_start,contract_time_end,id');
        $sing_arr = $sing_arr ? $sing_arr->toArray() : [];
        $sing_arr = array_column($sing_arr,null,'id');
//        $sing_arr = (new HouseVillageSingle())->getOneColumn(['village_id'=>$village_id],'single_name','id');

        //单元数组
        $floor_info_ary = (new HouseVillageFloor())->getList(['village_id'=>$village_id],'floor_name,floor_layer,floor_id');
        $floor_info_ary = $floor_info_ary ? $floor_info_ary->toArray() : [];
        $floor_info_ary = array_column($floor_info_ary,null,'floor_id');
        
        //单元类型数组
        $floor_type_name_ary = (new HouseVillageFloorType())->getColumn(['village_id'=>$village_id],'name','id');
        
        //楼层数组
        $layer_ary = (new HouseVillageLayer())->getOneColumn(['village_id'=>$village_id],'layer_name','id');
        
        //房间数组
        $vacancy_ary = (new HouseVillageUserVacancy())->getList([['village_id','=',$village_id]],'house_type,layer_id,room,single_id,contract_time_start,contract_time_end,pigcms_id','pigcms_id',0,0);
        $vacancy_ary = $vacancy_ary ? $vacancy_ary->toArray() : [];
        $vacancy_ary = array_column($vacancy_ary,null,'pigcms_id');
        
        //车辆数组
        $carAll = (new HouseVillageBindCar())->getUserCar([['b.village_id','=',$village_id],['b.user_id','>',0],['c.car_number','<>','null']],'c.province,c.car_number,b.user_id');
        $car_ary = [];
        foreach ($carAll as $car){
            $car_ary[$car['user_id']][] = $car['province'].$car['car_number'];
        }
        
        //车位数组
        $positionAll = (new HouseVillageBindPosition())->getUserPosition([['b.village_id','=',$village_id],['b.user_id','>',0],['p.position_num','<>','null']],'p.position_num,b.user_id');
        $position_ary = [];
        foreach ($positionAll as $position){
            $position_ary[$position['user_id']][] = $position['position_num'];
        }
        
        $cunstom_arr = [];
        
        foreach ($user_list as $Key => $user){
            $user_list[$Key]['car_str'] = isset($car_ary[$user['pigcms_id']]) && $car_ary[$user['pigcms_id']] ? implode(';',$car_ary[$user['pigcms_id']]) : '';
            $user_list[$Key]['position_str'] = isset($position_ary[$user['pigcms_id']]) && $position_ary[$user['pigcms_id']] ? implode(';',$position_ary[$user['pigcms_id']]) : '';
            $vacancy_room_info = $vacancy_ary[$user['vacancy_id']] ?? [];
            if (!isset($vacancy_room_info['house_type']) || !$vacancy_room_info['house_type']) {
                $house_type=1;
            } else {
                $house_type = $vacancy_room_info['house_type'];
            }
            if($vacancy_room_info && isset($vacancy_room_info['room'])  && $vacancy_room_info['room']){
                $user['room_addrss']=$vacancy_room_info['room'];
            }
            $user_list[$Key]['is_bind_weixin']='否';
            $user_list[$Key]['source_comefrom']='其他';
            $user_list[$Key]['house_type'] = $house_type;
            $user_list[$Key]['floor_type_name'] = $this->house_type_arr[$house_type]??'';
            
            if($user['uid']>0){
                $user_list[$Key]['is_bind_weixin'] = !empty($user['us_openid']) || !empty($user['us_wxapp_openid']) ? '是' : '否';
                if($user['source']=='weixin'){
                    $user_list[$Key]['source_comefrom']='公众号';
                }else if($user['source']=='wxapp' || $user['source']=='wxcapp'){
                    $user_list[$Key]['source_comefrom']='小程序';
                }elseif($user['source']=='houseautoreg'){
                    $user_list[$Key]['source_comefrom']='小区自动注册';
                }elseif($user['source']=='wap'){
                    $user_list[$Key]['source_comefrom']='H5';
                }
            }

            $floor_info =  isset($floor_info_ary[$user['floor_id']]) && $floor_info_ary[$user['floor_id']] ? $floor_info_ary[$user['floor_id']] : [];
            if($user['floor_id']){
                $floor_type_name =  $floor_type_name_ary[$user['floor_id']] ?? '';
                $house_type = $vacancy_room_info['house_type'] ?? 0;
                if (empty($floor_type_name)){
                    if ($house_type==1){
                        $user_list[$Key]['floor_type_name']='住宅';
                    }elseif($house_type==2){
                        $user_list[$Key]['floor_type_name']='商铺';
                    }else{
                        $user_list[$Key]['floor_type_name']='办公';
                    }
                }else{
                    $user_list[$Key]['floor_type_name']=$floor_type_name;
                }
//                $user_list[$Key]['address'] = $floor_info['floor_layer'] . $floor_info['floor_name'] . $user['layer_num']  . '#' . $user['room_addrss'];
            }

            $layer_id = $user['layer_id'] ?: ((isset($vacancy_room_info['house_type']) && $vacancy_room_info['layer_id']) ? $vacancy_room_info['house_type'] : 0);
            $layer_num = $layer_id && isset($layer_ary[$layer_id]) ? $layer_ary[$layer_id] : $user['layer_num'];
            $single_id = $user['single_id'] ? $user['single_id'] : ((isset($vacancy_room_info['single_id']) && $vacancy_room_info['single_id'] ) ? $vacancy_room_info['single_id'] : 0);
            $singleName = $sing_arr[$single_id]['single_name']??'';
            $floorName = ($floor_info && $floor_info['floor_name']) ? $floor_info['floor_name'] : '';
            if(!$singleName && !empty($floor_info)){
                $singleName = $floor_info['floor_layer'];
            }
            $user_list[$Key]['address'] = $service_house_village->word_replce_msg(array('single_name'=>$singleName,'floor_name'=>$floorName,'layer'=>$layer_num,'room'=>$user['room_addrss']),$village_id);
            
            $user_list[$Key]['attribute_name'] = isset($attribute_list[$user['attribute']]) && $attribute_list[$user['attribute']] ? $attribute_list[$user['attribute']] : '' ;

            $user_list[$Key]['openid'] = $user['us_openid'] ?: '';

            $property_month_time = 0;
            $property_month_time_arr = (new HouseVillagePayOrder())->getColumn(['village_id'=>$village_id,'paid'=>1,'uid'=>$user['uid']],'(property_month_num+presented_property_month_num) as property_month_time,order_id');
            foreach($property_month_time_arr as $row){
                $property_month_time += $row['property_month_time'];
            }
            
            $user_list[$Key]['property_month_time'] = $user['add_time'] + $property_month_time * 30 * 24 * 3600;

            //自定义项欠费
            $payment_list = (new HouseVillagePaymentStandardBind())->getList([
                ['psb.pigcms_id','=',$user['pigcms_id']],
                ['psb.village_id','=',$village_id],
                ['psb.start_time','<',time()]
            ],
                'p.payment_id = psb.payment_id AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND ps.standard_id = psb.standard_id',
                'psb.cycle_sum,psb.paid_cycle,p.payment_name,psb.payment_id,ps.cycle_type,ps.pay_cycle,ps.pay_type,ps.pay_money,psb.metering_mode_val,psb.start_time');
            
            //车位缴费
            $position_payment_list = (new HouseVillageBindPosition())->getUserPositionPaymentList([
                'b.user_id'=>$user['pigcms_id']
            ],'psb.cycle_sum,psb.paid_cycle,pa.payment_name,pa.payment_id,ps.cycle_type,ps.pay_cycle,ps.pay_type,ps.pay_money,psb.metering_mode_val,psb.start_time');

            $payment_list = array_merge($payment_list, $position_payment_list);
            $user_list[$Key]['cunstom_money'] = 0;
            if ($payment_list) {
                $cunstom_pay_arr = [];
                foreach ($payment_list as $key => $item) {
                    if ($item['cycle_sum'] - $item['paid_cycle'] <= 0 ) {
                        // 签订周期已缴完
                        $cunstom_list[$key]['payment_name'] = $item['payment_name'];
                        $cunstom_list[$key]['payment_money'] = 0;
                        $cunstom_arr[$item['payment_id']] = [
                            'payment_id' => $item['payment_id'],
                            'payment_name' => $item['payment_name']
                        ];
                        if (!$cunstom_pay_arr[$item['payment_id']] || !$cunstom_pay_arr[$item['payment_id']]['total_money']) {
                            $cunstom_pay_arr[$item['payment_id']]['total_money'] = 0;
                        }
                        $cunstom_pay_arr[$item['payment_id']] = [
                            'payment_id' => $item['payment_id'],
                            'payment_name' => $item['payment_name'],
                            'total_money' => $cunstom_pay_arr[$item['payment_id']]['total_money'] + $item['payment_money']
                        ];
                        continue;
                    }
                    // 已缴到期时间
                    switch ($item['cycle_type']) {
                        case 'Y':
                            $end_time = $item['start_time'] + $item['pay_cycle']*$item['paid_cycle']*86400*365;
                            break;
                        case 'M':
                            $end_time = $item['start_time'] + $item['pay_cycle']*$item['paid_cycle']*86400*30;
                            break;
                        case 'D':
                            $end_time = $item['start_time'] + $item['pay_cycle']*$item['paid_cycle']*86400;
                            break;
                    }
//                    $end_time = strtotime(date('Y-m-d',$item['start_time']).'+'.$item['pay_cycle']*$item['paid_cycle'].$cycle_type[$item['cycle_type']]);


                    if ($end_time<time()) {
                        $num = (new HouseVillagePaymentService())->getTimeNum($end_time,time(),$item['cycle_type']);
                        // 除以周期值 向上取整
                        $num = ceil($num/$item['pay_cycle']);
                        // 除以周期 取计算出来的周期和剩余周期取最小值
                        $num = min($num,$item['cycle_sum']);
                        if ($item['pay_type']==1) {
                            $item['payment_money'] = round($item['pay_money'] * $num,2);
                        }else{
                            $item['payment_money'] = round($item['pay_money'] * $num * $item['metering_mode_val'],2);
                        }
                        $user_list[$Key]['cunstom_money'] += $item['payment_money'];
                        $cunstom_arr[$item['payment_id']] = [
                            'payment_id' => $item['payment_id'],
                            'payment_name' => $item['payment_name']
                        ];
                        if (!isset($cunstom_pay_arr[$item['payment_id']]) || !$cunstom_pay_arr[$item['payment_id']] || !isset($cunstom_pay_arr[$item['payment_id']]['total_money']) ||  !$cunstom_pay_arr[$item['payment_id']]['total_money']) {
                            $cunstom_pay_arr[$item['payment_id']]['total_money'] = 0;
                        }
                        $cunstom_pay_arr[$item['payment_id']] = [
                            'payment_id' => $item['payment_id'],
                            'payment_name' => $item['payment_name'],
                            'total_money' => $cunstom_pay_arr[$item['payment_id']]['total_money'] + $item['payment_money']
                        ];
                    }
                }
                $user_list[$Key]['cunstom_pay_arr'] = $cunstom_pay_arr;
                $user_list[$Key]['cunstom_money'] = round( $user_list[$Key]['cunstom_money'],2);
            }
            
            if($param['is_new_charge'] == 1){
                //获取物业服务起始时间
                $userTimes = $this->getUserEndTime([
                    'type'=>$user['type'],
                    'pigcms_id'=>$user['pigcms_id'],
                    'parent_id'=>$user['parent_id'],
                    'property_id'=>$param['property_id'],
                    'vacancy_id'=>$user['vacancy_id'],
                    'vacancy_info'=>$vacancy_room_info,
                    'single_info'=>$sing_arr[$single_id]??[],
                    'village_info'=>$village_info,
                    'startTime'=>$user['property_starttime'],
                    'endTime'=>$user['property_endtime'],
                ]);
                if (isset($userTimes['propertyEndTime'])) {
                    $user['property_endtime'] = intval($userTimes['propertyEndTime']);
                } else {
                    $user['property_endtime'] = 0;
                }
                if (isset($userTimes['propertyStartTime'])) {
                    $user['property_starttime'] = intval($userTimes['propertyStartTime']);
                } else {
                    $user['property_endtime'] = 0;
                }
            }else{
                if (!$user['property_endtime']) {
                    $end_time = (new HouseVillagePropertyPaylist())->getValues(array('bind_id'=>$user['pigcms_id']),'end_time','add_time desc');
                    $user['property_endtime'] = $end_time;
                }

                if (!$user['property_starttime']) {
                    $start_time = (new HouseVillagePropertyPaylist())->getValues(array('bind_id'=>$user['pigcms_id']),'start_time','add_time asc');
                    $user['property_starttime'] = $start_time;
                }
            }
            $user_list[$Key]['property_starttime'] = $user['property_starttime'];
            $user_list[$Key]['property_endtime'] = $user['property_endtime'];
            // 查看有无车位
            $user_list[$Key]['bind_position'] = (new HouseVillageBindPosition())->getCount(array('user_id' => $user['pigcms_id'], 'village_id' => $village_id));
            if ($user['authentication_field']) {
                $user_list[$Key]['authentication_field_arr'] = unserialize($user['authentication_field']);
            } else {
                $user_list[$Key]['authentication_field_arr'] = [];
            }
        }
        if ($user_list) {
            $return['user_list'] = $user_list;
            $return['cunstom_arr'] = $cunstom_arr;
        }
        return $return;
    }
    
    /**
     * 获取物业服务起始时间
     */
    public function getUserEndTime($param)
    {
        $arr = [];
        if (isset($param['type']) && 4==$param['type']) {
            // 工作人员无时间限制
            $arr['propertyEndTime'] = 0;
            $arr['propertyStartTime'] = 0;
            $arr['parentInfo'] = [];
            return $arr;
        }
        if (isset($param['type']) && isset($param['parent_id']) && $param['parent_id'] && in_array($param['type'],[1,2,5]) && empty($parentInfo)) {
            $parentField='pigcms_id,uid,phone,status,property_starttime,property_endtime';
            $parentInfo = (new HouseVillageUserBind())->getOne(array('pigcms_id' => $param['parent_id']),$parentField);
        }
        if (isset($param['property_id'])&&$param['property_id']) {
            // 存在物业id，查询下物业新版收费生效与否 确定物业服务时间截止
            // 新版生效了  查询下
            $whereNewOrder = array('room_id'=>$param['vacancy_id'], 'order_type'=>'property');
            //查当前房间的服务结束时间
            $order_log_service_end_time = (new HouseNewOrderLog())->getValueInfo($whereNewOrder,'service_end_time','id DESC');
            //查当前房间的服务开始时间
            $order_log_service_start_time = (new HouseNewOrderLog())->getValueInfo($whereNewOrder,'service_start_time','id ASC');
            
            //查询当前房间的合同时间
            $contract_time = [
                'contract_time_start'=>0,
                'contract_time_end'=>0,
            ];
            $vacancy_info = $param['vacancy_info'];
            if (!empty($vacancy_info)&&$vacancy_info['contract_time_start']>1){
                $contract_time['contract_time_start']=$vacancy_info['contract_time_start'];
                $contract_time['contract_time_end']=$vacancy_info['contract_time_end'];
            }
            $single_info = $param['single_info'];
            if (!empty($single_info)&&$single_info['contract_time_start']>1){
                $contract_time['contract_time_start']=$single_info['contract_time_start'];
                $contract_time['contract_time_end']=$single_info['contract_time_end'];
            }
            $village_info = $param['village_info'];
            if (!empty($village_info)&&$village_info['contract_time_start']>1){
                $contract_time['contract_time_start']=$village_info['contract_time_start'];
                $contract_time['contract_time_end']=$village_info['contract_time_end'];
            }
            if ($contract_time['contract_time_start']==0&&empty($order_log_service_end_time)){
                $arr['propertyStartTime'] = 0;
                $arr['propertyEndTime'] = 0;
            }elseif($contract_time['contract_time_start']==0&&!empty($order_log_service_end_time)){
                $arr['propertyStartTime'] = $order_log_service_start_time;
                $arr['propertyEndTime'] = $order_log_service_end_time;
            }elseif($contract_time['contract_time_start']!=0&&empty($order_log_service_end_time)){
                $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                $arr['propertyEndTime'] = $contract_time['contract_time_end'];
            }elseif($contract_time['contract_time_start']!=0&&!empty($order_log_service_end_time)){
                if ($contract_time['contract_time_start']<$order_log_service_start_time){
                    $arr['propertyStartTime'] = $order_log_service_start_time;
                }else{
                    $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                }
                $arr['propertyEndTime'] = $order_log_service_end_time;
            }
            if(isset($arr['propertyEndTime']) && $arr['propertyStartTime'] && $arr['propertyEndTime']<$arr['propertyStartTime']){
                $arr['propertyEndTime']=$arr['propertyStartTime'];
            }
            return $arr;
        }
        
        if(isset($param['type']) && isset($param['parent_id']) && $param['parent_id'] && in_array($param['type'],[1,2,5])){
            if (!$parentInfo) $parentInfo = [];
            $arr['parentInfo'] = $parentInfo;
            if ($parentInfo) {
                // 0标识 无限制
                $arr['propertyEndTime'] = isset($parentInfo['property_endtime'])&&$parentInfo['property_endtime']?$parentInfo['property_endtime']:0;
                $arr['propertyStartTime'] = isset($parentInfo['property_starttime'])&&$parentInfo['property_starttime']?$parentInfo['property_starttime']:0;
            }
            $bind_id = $param['parent_id'];
        } else {
            $bind_id = $param['pigcms_id'];
            if (isset($param['startTime'])) {
                $arr['propertyStartTime'] = isset($param['startTime'])&&$param['startTime']?$param['startTime']:0;
            }
            if (isset($param['endTime'])) {
                $arr['propertyEndTime'] = isset($param['endTime'])&&$param['endTime']?$param['endTime']:0;
            }
        }
        // 计算 当前时间到物业截止时间的天数
        if (isset($arr['propertyEndTime']) && $arr['propertyEndTime']>0) {
            $end_time = (new HouseVillagePropertyPaylist())->getValues(array('bind_id'=>$bind_id),'end_time','add_time desc');
            if ($end_time>$arr['propertyEndTime']) {
                $arr['propertyEndTime'] = $end_time;
            }
        }
        if (!isset($arr['parentInfo']) || !$arr['parentInfo']) {
            $arr['parentInfo'] = [];
        }
        if (!isset($arr['propertyEndTime'])) {
            $arr['propertyEndTime'] = 0;
        }
        if (!isset($arr['propertyStartTime'])) {
            $arr['propertyStartTime'] = 0;
        }
        return $arr;
    }
    
    /**
     * 创建excel
     */
    public function createExcel($data,$village_name,$is_new_charge,$property_id)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $title = $village_name . '社区-业主列表';
        //设置单元格内容
        $columnTitleAry = [
            '业主编号',
            '姓名',
            '手机号',
            '身份证号',
            'IC卡号',
            '房屋类型',
            '住址',
            '物业服务开始时间',
            '物业服务结束时间',
            '停车位',
            '房子大小',
            '车辆信息',
            '车位信息',
            '是否绑定微信',
            '注册来源'
        ];
        if(!$is_new_charge){
            $columnTitleAry = [
                '业主编号',
                '姓名',
                '手机号',
                '身份证号',
                'IC卡号',
                '房屋类型',
                '住址',
                '物业服务开始时间',
                '物业服务结束时间',
                '水费',
                '电费',
                '燃气费',
                '停车费',
//                '停车位',
//                '房子大小',
//                '车辆信息',
//                '车位信息',
//                '是否绑定微信',
//                '注册来源'
            ];
            if(isset($data['cunstom_arr']) && $data['cunstom_arr']){
                foreach ($data['cunstom_arr'] as $vs) {
                    $columnTitleAry[] = $vs['payment_name'];
                }
            } else {
                $columnTitleAry[] = '自定义项费';
            }
            $columnTitleAry[] = '停车位';
            $columnTitleAry[] = '房子大小';
            $columnTitleAry[] = '车辆信息';
            $columnTitleAry[] = '车位信息';
            $columnTitleAry[] = '是否绑定微信';
            $columnTitleAry[] = '注册来源';
        }
        $where_data_config = array(
            'village_id'=>0,
            'property_id'=>$property_id,
            'is_display'=>1,
            'is_close'=>0
        );
        $dataList = (new HouseVillageDataConfig())->getList($where_data_config,'title,key','sort desc');
        $dataList = $dataList ? $dataList->toArray() : [];
        if (!empty($dataList)) {
            foreach ($dataList as $dataKey) {
                $columnTitleAry[] = $dataKey['title'];
            }
        }
        $area_arr = (new Area())->getColumns([],'area_id,area_name');
        $area_arr = array_column($area_arr,null,'area_id');
        $columnWord = [];
        for ($ii='A';$ii<='Z';$ii++){
            $columnWord[] = $ii;
        }
        //设置单元格样式
        $worksheet->getStyle('A1:DZ1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:DZ')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:DZ1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $res = [];
        foreach ($columnTitleAry as $k=>$vTitle){
            $worksheet->setCellValueByColumnAndRow($k+1, 1, $vTitle);
            $spreadsheet->getActiveSheet()->getColumnDimension($columnWord[$k])->setWidth(26);
        }
        
        $letterArr=['A','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF'];
        $len = count($data['user_list']);
        $i = 0;
        $row = 0;
        foreach ($data['user_list'] as $key => $value){
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $bind_number = isset($value['bind_number']) && $value['bind_number'] ? $value['bind_number'] : $value['usernum'];
                $columnIndex = 1;
                $bind_number = $bind_number ? ($bind_number."\t") : '';
                $worksheet->setCellValueExplicit($letterArr[$columnIndex].$j, $bind_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                //$worksheet->setCellValueByColumnAndRow($columnIndex, $j, $bind_number);
                $columnIndex ++;
                $value['name'] = $value['name'] ? ($value['name']."\t") : '';
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['name']?:'');
                $columnIndex ++;
                $value['phone']=$value['phone'] ? strval($value['phone']):'';
                $worksheet->setCellValueExplicit($letterArr[$columnIndex].$j, $value['phone'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                //$worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['phone']?:'');
                $columnIndex ++;
                $value['id_card'] = $value['id_card'] ? strval($value['id_card'])."\t" : '';
                $worksheet->setCellValueExplicit($letterArr[$columnIndex].$j, $value['id_card'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                //$worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['id_card']?$value['id_card']:'无');
                $columnIndex ++;
                $value['ic_card'] = $value['ic_card'] ? strval($value['ic_card'])."\t" : '';
                $worksheet->setCellValueExplicit($letterArr[$columnIndex].$j, $value['ic_card'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
               // $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['ic_card']?$value['ic_card']:'无');
                $columnIndex ++;
                if(isset($value['floor_type_name'])){
                    $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['floor_type_name']?:'');
                }else{
                    $worksheet->setCellValueByColumnAndRow($columnIndex, $j, '暂无');
                }
                $columnIndex ++;
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['address']);
                if (isset($value['property_starttime']) && intval($value['property_starttime'])>0) {
                    $property_starttime = date('Y-m-d',$value['property_starttime']);
                } else {
                    $property_starttime = '无';
                }
                if (isset($value['property_endtime']) && intval($value['property_endtime'])>0) {
                    $property_endtime = date('Y-m-d',$value['property_endtime']);
                } else {
                    $property_endtime = '无';
                }

                $columnIndex ++;
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $property_starttime);
                $columnIndex ++;
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $property_endtime);
                
                if(!$is_new_charge){
                    // 自定义缴费
                    $water_price = floatval($value['water_price']);
                    $electric_price = floatval($value['electric_price']);
                    $gas_price = floatval($value['gas_price']);
                    $park_price = floatval($value['park_price']);
                    $columnIndex ++;
                    $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $water_price);
                    $columnIndex ++;
                    $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $electric_price);
                    $columnIndex ++;
                    $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $gas_price);
                    $columnIndex ++;
                    $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $park_price);

                    $cunstom_pay_arr = $value['cunstom_pay_arr'] ?? [];
                    if (isset($data['cunstom_arr']) && $data['cunstom_arr']) {
                        foreach ($data['cunstom_arr'] as $ks1 => $vs1) {
                            $columnIndex ++;
                            $total1 = (isset($cunstom_pay_arr[$ks1]) && $cunstom_pay_arr[$ks1]) ? $cunstom_pay_arr[$ks1]['total_money'] : '-';
                            $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $total1);
                        }
                    } else {
                        $columnIndex ++;
                        $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['cunstom_money']);
                    }
                }


                $columnIndex ++;
                if(isset($value['bind_position']) && $value['bind_position']){
                    $worksheet->setCellValueByColumnAndRow($columnIndex, $j, '有');
                }else{
                    $worksheet->setCellValueByColumnAndRow($columnIndex, $j, '无');
                }
                $columnIndex ++;
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['housesize']);

                // 车辆信息
                $columnIndex ++;
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['car_str']);
                // 车位信息
                $columnIndex ++;
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $value['position_str']);

                $is_bind_weixin='';
                if(isset($value['is_bind_weixin'])){
                    $is_bind_weixin=$value['is_bind_weixin'];
                }
                $columnIndex ++;
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $is_bind_weixin);

                $source_comefrom='';
                if(isset($value['source_comefrom'])){
                    $source_comefrom=$value['source_comefrom'];
                }
                $columnIndex ++;
                $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $source_comefrom);
                
                if($dataList){
                    foreach ($dataList as $dataKey1) {
                        $keyVal = '-';
                        if (isset($value['authentication_field_arr'][$dataKey1['key']]['value']) && $value['authentication_field_arr'][$dataKey1['key']]['value']) {
                            $keyVal = $value['authentication_field_arr'][$dataKey1['key']]['value'];
                            if ($dataKey1['key'] == 'native_place'){
                                $native_place = explode('#',$keyVal);
                                $province = $native_place[0];
                                $city = $native_place[1];
                                if (!$province && !$city) {
                                    $keyVal = '-';
                                } else {
                                    $keyVal = '';
                                    if ($province && $area_arr && isset($area_arr[$province])) {
                                        $area_name = $area_arr[$province]['area_name'] ?? '';
                                        $keyVal .= $area_name . ' ';
                                    }
                                    if ($city && $area_arr && isset($area_arr[$city])) {
                                        $area_name = $area_arr[$city]['area_name'] ?? '';
                                        $keyVal .= $area_name . ' ';
                                    }
                                    if (!$keyVal) {
                                        $keyVal = '未知';
                                    }
                                }
                            }
                        }

                        $columnIndex ++;
                        $worksheet->setCellValueByColumnAndRow($columnIndex, $j, $keyVal);
                    }
                }
                $i++;
            }
            $row++;
        }
        $title=str_replace('/','',$title);
        $title=str_replace('\\','',$title);
        $filename = $title.'-'.date("Y-m-d h_i_sa", time()) . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        $returnArr = [
            'url'=>cfg('site_url') . '/v20/runtime/' . $filename,
            'error'=>0
        ];
        return $returnArr;
    }
}
