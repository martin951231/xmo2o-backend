<?php
/**
 **立林对接数据处理
 * *对接 公网文档 https://iot.leelen.com/showdoc/#/5/36
 */

namespace app\community\model\service;

use app\common\model\service\ConfigService;
use app\common\model\service\image\ImageService;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseVillageLilinPushRecord;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseAdmin;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseFaceImg;
use app\community\model\db\HouseUserdevCallRecord;
use app\community\model\db\HouseVillageVisitEwm;
use app\community\model\db\HouseVillageVisitTag;
use app\community\model\db\HouseFaceSyncLog;
use app\community\model\db\PropertyGroup;
use app\community\model\service\HouseVillageService;
use face\wordencryption;
use file_handle\FileHandle;
use think\Exception;
use app\traits\LilinPfCommunityDeviceTraits;

class HouseVillageLilinPushRecordService
{
    use LilinPfCommunityDeviceTraits;

    public $baseDevApi = 'https://iottest.leelen.net'; //测试环境
    public $baseApi = 'https://iot.leelen.com'; //正式环境
    public $clientId = '';   //接入端id
    public $clientSecret = '';  //接入端Secret
    public $village_id=0;
    public $village_config=array();
    public function __construct()
    {

    }

    public function getOneRecord($whereArr = array())
    {
        $oneRecord = array();
        $houseVillageLilinPushRecordDb = new HouseVillageLilinPushRecord();
        if (!empty($whereArr)) {
            $lilinPushRecordObj = $houseVillageLilinPushRecordDb->getOneData($whereArr);
            if ($lilinPushRecordObj && !$lilinPushRecordObj->isEmpty()) {
                $oneRecord = $lilinPushRecordObj->toArray();
            }
        }
        return $oneRecord;
    }

    public function addOneRecord($addArr = array())
    {
        $houseVillageLilinPushRecordDb = new HouseVillageLilinPushRecord();
        $ret = $houseVillageLilinPushRecordDb->addOneData($addArr);
        return $ret;
    }

    public function saveRecord($whereArr = array(), $saveArr = array())
    {
        $houseVillageLilinPushRecordDb = new HouseVillageLilinPushRecord();
        $ret = $houseVillageLilinPushRecordDb->saveOneData($whereArr, $saveArr);
        return $ret;
    }

    public function getRecordList($where, $field = true, $page = 0, $limit = 15, $order = 'garage_id DESC')
    {
        $houseVillageLilinPushRecordDb = new HouseVillageLilinPushRecord();
        $listDataObj = $houseVillageLilinPushRecordDb->getListData($where, $field, $order, $page, $limit);
        if ($listDataObj && !$listDataObj->isEmpty()) {
            $listData = $listDataObj->toArray();
        } else {
            $listData = array();
        }
        return $listData;
    }

    /***
     **获取关联的小区
     **/
    public function getRelateVillage($neigh_no = '')
    {
        $hVillage = array();
        $houseVillageDb = new HouseVillage();
        if (!empty($neigh_no)) {
            $neigh_no = trim($neigh_no);
            $whereArr = array('lilin_village_no' => $neigh_no);
            $houseVillageObj = $houseVillageDb->getInfo($whereArr);
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $hVillage = $houseVillageObj->toArray();
            }
        }
        return $hVillage;
    }


    /**
     * 推送过来的新增楼栋 单元
     **/
    public function pushAddOrEditBuilding($pdata = array(), $otype = 'add')
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $xtype = isset($pdata['type']) ? intval($pdata['type']) : 0;
        $opt_type = 'addSingle';
        if ($xtype == 3 && $otype == 'edit') {
            $opt_type = 'editSingle';
        } else if ($xtype == 4 && $otype == 'add') {
            $opt_type = 'addFloor';
        } else if ($xtype == 4 && $otype == 'edit') {
            $opt_type = 'editFloor';
        }
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['type'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $pdata['neighNo'] = $neigh_no;
        switch ($xtype) {
            case 3:
                $returnArr = $this->villageSingleHandle($pdata, $rVillage, $returnArr, $opt_type);
                break;
            case 4:
                $returnArr = $this->villageFloorHandle($pdata, $rVillage, $returnArr, $opt_type);
                break;
            case 5:
                //地点节点
                break;
            default:

        }

        return $returnArr;
    }

    public function villageSingleHandle($pdata, $rVillage, $returnArr, $opt_type = '')
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $single_name = strval($pdata['label']);
        $single_name = trim($single_name);
        $coordinateArr = array();
        if (isset($pdata['coordinate']) && $pdata['coordinate']) {
            if (strpos($pdata['coordinate'], ',') !== false) {
                $coordinateArr = explode(',', $pdata['coordinate']);
            } else if (strpos($pdata['coordinate'], '，') !== false) {
                $coordinateArr = explode('，', $pdata['coordinate']);
            }

        }
        $houseVillageSingleDb = new HouseVillageSingle();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_single_source', '=', 'lilin_pf');
        $whereArr[] = array('third_single_index_id', '=', $pdata['id']);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
        if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
            $saveArr = array('single_name' => $single_name, 'update_time' => time());
            if ($coordinateArr) {
                $saveArr['long'] = $coordinateArr['0'];
                $saveArr['lat'] = $coordinateArr['1'];
            }
            $saveArr['single_number'] = $single_name;
            $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageSingleDb->saveOne(array('id' => $tmpSingleObj['id']), $saveArr);

        } else {
            $saveArr = array('single_name' => $single_name, 'village_id' => $rVillage['village_id']);
            $saveArr['status'] = 1;
            if ($coordinateArr) {
                $saveArr['long'] = $coordinateArr['0'];
                $saveArr['lat'] = $coordinateArr['1'];
            }
            $saveArr['single_number'] = $single_name;
            $saveArr['third_single_index_id'] = $pdata['id'];
            $saveArr['third_single_source'] = 'lilin_pf';
            $saveArr['update_time'] = time();
            $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageSingleDb->addOne($saveArr);
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = $pdata['type'];
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    public function villageFloorHandle($pdata, $rVillage, $returnArr, $opt_type = '')
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $floor_name = strval($pdata['label']);
        $floor_name = trim($floor_name);
        $coordinateArr = array();
        if (isset($pdata['coordinate']) && $pdata['coordinate']) {
            if (strpos($pdata['coordinate'], ',') !== false) {
                $coordinateArr = explode(',', $pdata['coordinate']);
            } else if (strpos($pdata['coordinate'], '，') !== false) {
                $coordinateArr = explode('，', $pdata['coordinate']);
            }
        }
        $houseVillageSingleDb = new HouseVillageSingle();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_single_source', '=', 'lilin_pf');
        $whereArr[] = array('third_single_index_id', '=', $pdata['pid']);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
        $single_id = 0;
        if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
            $single_id = $tmpSingleObj['id'];
        }
        if ($single_id < 1) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有查找到此单元关联的楼栋！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['type'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有查找到此单元关联的楼栋';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseVillageFloorDb = new HouseVillageFloor();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_floor_source', '=', 'lilin_pf');
        $whereArr[] = array('third_floor_index_id', '=', $pdata['id']);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageFloorDb->getOne($whereArr, '*');
        if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
            $saveArr = array('floor_name' => $floor_name, 'update_time' => time());
            if ($coordinateArr) {
                $saveArr['long'] = $coordinateArr['0'];
                $saveArr['lat'] = $coordinateArr['1'];
            }
            $saveArr['floor_number'] = $floor_name;
            $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageFloorDb->saveOne(array('floor_id' => $tmpSingleObj['floor_id']), $saveArr);

        } else {
            $saveArr = array('floor_name' => $floor_name, 'village_id' => $rVillage['village_id']);
            $saveArr['status'] = 1;
            if ($coordinateArr) {
                $saveArr['long'] = $coordinateArr['0'];
                $saveArr['lat'] = $coordinateArr['1'];
            }
            $saveArr['single_id'] = $single_id;
            $saveArr['floor_number'] = $floor_name;
            $saveArr['third_floor_index_id'] = $pdata['id'];
            $saveArr['third_floor_source'] = 'lilin_pf';
            $saveArr['add_time'] = time();
            $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageFloorDb->addOne($saveArr);
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = $pdata['type'];
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    /**
     * 推送过来的新增楼栋
     **/
    public function pushDelBuilding($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
            $addData['opt_type'] = 'del';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseVillageFloorDb = new HouseVillageFloor();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_floor_source', '=', 'lilin_pf');
        $whereArr[] = array('third_floor_index_id', '=', $pdata['id']);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageFloorDb->getOne($whereArr, '*');
        $delType = '';
        if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
            $saveArr = array('status' => 4, 'update_time' => time());
            $houseVillageFloorDb->saveOne(array('floor_id' => $tmpSingleObj['floor_id']), $saveArr);
            $delType = 'delFloor';
        }
        if (empty($delType)) {
            $houseVillageSingleDb = new HouseVillageSingle();
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('third_single_source', '=', 'lilin_pf');
            $whereArr[] = array('third_single_index_id', '=', $pdata['id']);
            $whereArr[] = array('status', '<', 4);
            $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
            if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
                $saveArr = array('status' => 4, 'update_time' => time());
                $houseVillageSingleDb->saveOne(array('id' => $tmpSingleObj['id']), $saveArr);
                $delType = 'delSingle';
            }
        }
        if (empty($delType)) {
            //其他删除
            $delType = 'del';
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => 0, 'func_type' => 'building');
        $addData['opt_type'] = $delType;
        $addData['xtype'] = 0;
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    public function pushAddOrEditHouse($pdata, $otype = 'add')
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $opt_type = 'addHouse';
        if ($otype == 'edit') {
            $opt_type = 'editHouse';
        }
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['type'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseVillageFloorDb = new HouseVillageFloor();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_floor_source', '=', 'lilin_pf');
        $whereArr[] = array('third_floor_index_id', '=', $pdata['pid']);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageFloorDb->getOne($whereArr, '*');
        $floor_id = 0;
        $single_id = 0;
        if (!empty($tmpSingleObj) && !$tmpSingleObj->isEmpty()) {
            $floor_id = $tmpSingleObj['floor_id'];
            $single_id = $tmpSingleObj['single_id'];
        }
        if ($floor_id < 1) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有查找到此房屋关联的单元！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'houseOpt');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['type'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有查找到此房屋关联的单元';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseVillageLayer = new HouseVillageLayer();
        $pdata['floor'] = isset($pdata['floor']) && $pdata['floor'] ? $pdata['floor'] : 1;
        $layer_id = 0;
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_layer_source', '=', 'lilin_pf');
        $whereArr[] = array('floor_id', '=', $floor_id);
        $whereArr[] = array('layer_number', '=', $pdata['floor']);
        $whereArr[] = array('status', '<', 4);
        $layerObj = $houseVillageLayer->getOne($whereArr, '*');
        if ($layerObj && !$layerObj->isEmpty()) {
            $layer_id = $layerObj['id'];
        } else {
            $layerAdd = array('layer_name' => $pdata['floor'] . '层', 'status' => 1, 'single_id' => $single_id, 'floor_id' => $floor_id, 'village_id' => $rVillage['village_id']);
            $layerAdd['layer_number'] = $pdata['floor'];
            $layerAdd['third_layer_source'] = 'lilin_pf';
            $layerAdd['add_time'] = time();
            $layer_id = $houseVillageLayer->addOne($layerAdd);
        }
        $vacancyData = array('usernum' => $pdata['homeNo']);
        $vacancyData['houseId'] = $pdata['homeNo'];
        $vacancyData['floor_id'] = $floor_id;
        $vacancyData['layer'] = $pdata['floor'] . '层';
        $vacancyData['room'] = $pdata['houseCode'];
        $vacancyData['status'] = 1;
        $vacancyData['village_id'] = $rVillage['village_id'];
        $vacancyData['name'] = isset($pdata['houseOwner']) && $pdata['houseOwner'] ? $pdata['houseOwner'] : '';
        $vacancyData['type'] = 0;
        $memo = '';
        if (isset($pdata['houseLayout']) && $pdata['houseLayout']) {
            $memo = $pdata['houseLayout'];
        }
        if (isset($pdata['orientation']) && $pdata['orientation']) {
            $memo = $memo ? $memo . ' ' . $pdata['orientation'] : $pdata['orientation'];
        }
        $housesize = 0;
        if (isset($pdata['houseArea']) && $pdata['houseArea']) {
            $memo = $memo ? $memo . ' ' . $pdata['houseArea'] : $pdata['houseArea'];
            $housesize = floatval($pdata['houseArea']);
        }
        if (isset($pdata['houseName']) && $pdata['houseName']) {
            $memo = $memo ? $memo . ' ' . $pdata['houseName'] : $pdata['houseName'];
        }
        $house_type = 1;  //1住宅 2商铺 3办公
        if (isset($pdata['houseType']) && $pdata['houseType'] == 3) {
            $house_type = 2;
        }
        if (isset($pdata['type']) && $pdata['type'] == 10) {
            $house_type = 3;
        }
        $sell_status = 1;
        if (isset($pdata['houseState']) && $pdata['houseState'] == 2) {
            $sell_status = 3;
        }
        $vacancyData['memo'] = $memo;
        $vacancyData['housesize'] = $housesize;
        $vacancyData['house_type'] = $house_type;
        $vacancyData['user_status'] = 2;
        $vacancyData['sell_status'] = $sell_status;
        $vacancyData['single_id'] = $single_id;
        $vacancyData['layer_id'] = $layer_id;
        $vacancyData['room_number'] = $pdata['homeNo'];
        $vacancyData['property_number'] = $single_id . $floor_id . $layer_id . $pdata['houseCode'];
        $vacancyData['room_alias_id'] = $pdata['homeNo'];
        $vacancyData['third_room_id'] = $pdata['id'];
        $vacancyData['third_room_source'] = 'lilin_pf';

        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_room_source', '=', 'lilin_pf');
        $whereArr[] = array('third_room_id', '=', $pdata['id']);
        $whereArr[] = array('is_del', '<', 1);
        $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
        $room_id = 0;
        if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
            //编辑
            $room_id = $userVacancyObj['pigcms_id'];
            $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancyObj['pigcms_id']), $vacancyData);
        } else {
            $vacancyData['add_time'] = time();
            $room_id = $houseVillageUserVacancy->addOne($vacancyData);
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'houseOpt');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = $pdata['type'];
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;

    }

    public function pushDelHouse($pdata)
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => 0, 'func_type' => 'houseOpt');
            $addData['opt_type'] = 'delHouse';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_room_source', '=', 'lilin_pf');
        $whereArr[] = array('third_room_id', '=', $pdata['id']);
        $whereArr[] = array('is_del', '<', 1);
        $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
        if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
            //编辑
            $vacancyData = array('is_del' => 1, 'del_time' => time());
            $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancyObj['pigcms_id']), $vacancyData);
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => 0, 'func_type' => 'houseOpt');
        $addData['opt_type'] = 'delHouse';
        $addData['xtype'] = 0;
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    public function pushAddOrEditDevice($pdata, $otype = 'add')
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $opt_type = 'addDevice';
        if ($otype == 'edit') {
            $opt_type = 'editDevice';
        }
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['structPositionId'], 'n_pid' => $pdata['structPositionParentId'], 'func_type' => 'deviceOpt');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['deviceType'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $deviceType = array(4097, 4141, 4103, 4112, 4128, 4133, 4142, 4100);  //对接门禁类型 4097门禁主机 4141独立门禁机   4128道闸人脸门禁机 4112门禁控制器 4128道闸人脸门禁机

        $deviceCategory = array(1); //所属系统
        if (!in_array($pdata['deviceType'], $deviceType)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '不是门禁设备，设备类型不对！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['structPositionId'], 'n_pid' => $pdata['structPositionParentId'], 'func_type' => 'deviceOpt');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['deviceType'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '不是门禁设备，设备类型不对！';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }

        $houseFaceDevice = new HouseFaceDevice();
        $saveArr = array('device_name' => $pdata['deviceName'], 'device_type' => $pdata['deviceType']);
        if (isset($pdata['name']) && $pdata['name']) {
            $saveArr['device_name'] = $pdata['name'];
        }
        $saveArr['device_sn'] = isset($pdata['did']) && $pdata['did'] ? $pdata['did'] : '';
        $saveArr['village_id'] = $rVillage['village_id'];
        $saveArr['device_status'] = $pdata['status'] == 1 ? 1 : 2;
        if ($pdata['deviceType'] == 4097 && $pdata['status'] == 3) {
            $saveArr['device_status'] = 1;
        }
        $saveArr['thirdDeviceCategory'] = $pdata['deviceCategory'];
        $saveArr['thirdDeviceTypeStr'] = $pdata['deviceType'];
        $saveArr['device_direction'] = 2;
        $saveArr['device_ip'] = $pdata['deviceIp'];
        if (isset($pdata['port']) && $pdata['port']) {
            $saveArr['thirdPort'] = $pdata['port'];
        }
        if ($pdata['deviceType'] == 4142) {
            $saveArr['device_model'] = 1;
        }
        $saveArr['device_brand'] = 'lilin_pf';
        $saveArr['thirdAddress'] = $pdata['address'];
        $saveArr['remark'] = $pdata['address'];
        if (isset($pdata['username']) && $pdata['username']) {
            $saveArr['thirdLoginName'] = $pdata['username'];
        }
        if (isset($pdata['password']) && $pdata['password']) {
            $saveArr['thirdLoginPassword'] = $pdata['password'];
        }
        if (isset($pdata['deviceNo']) && $pdata['deviceNo']) {
            $saveArr['device_no'] = $pdata['deviceNo'];
        }
        if (isset($pdata['model']) && $pdata['model']) {
            $saveArr['thirdModel'] = $pdata['model'];
            $saveArr['brand_type'] = $pdata['model'];
        }
        if (isset($pdata['remark']) && $pdata['remark']) {
            $saveArr['remark'] = $pdata['remark'];
        }
        $saveArr['third_dev_source'] = 'lilin_pf';
        $saveArr['third_dev_id'] = $pdata['id'];

        $structPositionParentId = $pdata['structPositionParentId'];
        $houseVillageFloorDb = new HouseVillageFloor();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_floor_source', '=', 'lilin_pf');
        $whereArr[] = array('third_floor_index_id', '=', $structPositionParentId);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageFloorDb->getOne($whereArr, '*');
        $floor_id = 0;
        $single_id = 0;
        if (!empty($tmpSingleObj) && !$tmpSingleObj->isEmpty()) {
            $floor_id = $tmpSingleObj['floor_id'];
            $single_id = $tmpSingleObj['single_id'];
        }
        if ($floor_id < 1) {
            $houseVillageSingleDb = new HouseVillageSingle();
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('third_single_source', '=', 'lilin_pf');
            $whereArr[] = array('third_single_index_id', '=', $structPositionParentId);
            $whereArr[] = array('status', '<', 4);
            $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
            if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
                $single_id = $tmpSingleObj['id'];
            }
        }
        
        $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('third_dev_id', '=', $pdata['id']);
        $whereArr[] = array('is_del', '<', 4);
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        $device_id = 0;
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            $saveArr['last_time'] = time();
            $device_id = $faceDeviceObj['device_id'];
            if($floor_id){
                $saveArr['floor_id'] = $floor_id;
            }
            if($single_id){
                $saveArr['single_id'] = $single_id;
            }
            $houseFaceDevice->saveData(array('device_id' => $faceDeviceObj['device_id']), $saveArr);
        } else {
            $saveArr['add_time'] = time();
            $saveArr['floor_id'] = $floor_id;
            $saveArr['single_id'] = $single_id;
            $device_id = $houseFaceDevice->addData($saveArr);
        }

        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['structPositionId'], 'n_pid' => $pdata['structPositionParentId'], 'func_type' => 'deviceOpt');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = $pdata['deviceType'];
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;

    }

    public function pushDeviceStatus($pdata)
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => 0, 'func_type' => 'deviceOpt');
            $addData['opt_type'] = 'updateStatus';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseFaceDevice = new HouseFaceDevice();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('third_dev_id', '=', $pdata['id']);
        $whereArr[] = array('is_del', '<', 4);
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        $nowTime = time();
        $device_id = 0;
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            //编辑
            $updateData = array('notify_time' => $nowTime);
            $updateData['device_status'] = $pdata['status'] == 1 ? 1 : 2;
            if ($faceDeviceObj['device_type'] == 4097 && $pdata['status'] == 3) {
                $updateData['device_status'] = 1;
            }
            $device_id = $faceDeviceObj['device_id'];
            if (isset($pdata['deviceIp']) && $pdata['deviceIp']) {
                $updateData['device_ip'] = $pdata['deviceIp'];
            }
            if (isset($pdata['deviceNo']) && $pdata['deviceNo']) {
                $updateData['device_no'] = $pdata['deviceNo'];
            }
            if (isset($pdata['did']) && $pdata['did']) {
                $updateData['device_sn'] = $pdata['did'];
            }
            if (isset($pdata['model']) && $pdata['model']) {
                $updateData['thirdModel'] = $pdata['model'];
                $updateData['brand_type'] = $pdata['model'];
            }
            $updateData['device_info'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseFaceDevice->saveData(array('device_id' => $faceDeviceObj['device_id']), $updateData);
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        fdump_api(['device_id' => $device_id, 'pdata' => $pdata], 'liLinAccess/pushDeviceStatus');
        return $returnArr;
    }

    public function pushDelDevice($pdata)
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => 0, 'func_type' => 'deviceOpt');
            $addData['opt_type'] = 'delDevice';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseFaceDevice = new HouseFaceDevice();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('third_dev_id', '=', $pdata['id']);
        $whereArr[] = array('is_del', '<', 4);
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            //编辑
            $delData = array('is_del' => 4, 'last_time' => time());
            $houseFaceDevice->saveData(array('device_id' => $faceDeviceObj['device_id']), $delData);
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => 0, 'func_type' => 'deviceOpt');
        $addData['opt_type'] = 'delDevice';
        $addData['xtype'] = 0;
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    public function pushAddOrEditHousehold($pdata, $otype = 'add')
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $opt_type = 'addRoomUser';
        if ($otype == 'edit') {
            $opt_type = 'editRoomUser';
        }
        $personInfo = isset($pdata['personInfo']) ? $pdata['personInfo'] : array();
        $houseInfo = isset($pdata['houseInfo']) ? $pdata['houseInfo'] : array();
        $accessInfo = isset($pdata['accessInfo']) ? $pdata['accessInfo'] : array();
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $personInfo['id'], 'n_pid' => 0, 'func_type' => 'roomUser');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $bindUser = array('village_id' => $rVillage['village_id'], 'usernum' => '');
        $bindUser['name'] = $personInfo['personName'];
        $bindUser['phone'] = isset($personInfo['phoneNumber']) ? $personInfo['phoneNumber'] : '';
        $bindUser['type'] = 0;
        if ($personInfo['personType'] == 1) {
            $bindUser['type'] = 0;
        } elseif ($personInfo['personType'] == 2) {
            $bindUser['type'] = 2;
        } else {
            $bindUser['type'] = 1;
        }
        $bindUser['id_card'] = isset($personInfo['certificatesNumber']) ? $personInfo['certificatesNumber'] : '';
        $bindUser['birth'] = isset($personInfo['birthYear']) ? $personInfo['birthYear'] : '';
        $bindUser['memo'] = isset($personInfo['remark']) ? $personInfo['remark'] : '';
        $bindUser['third_type'] = 'lilin_pf';
        $img_url = '';
        $extra_data_img = array();
        if (!empty($accessInfo) && isset($accessInfo['facePhotoUrl']) && !empty($accessInfo['facePhotoUrl'])) {
            $facePhotoUrl = strtolower($accessInfo['facePhotoUrl']);
            $faceArr = explode('certificate/face/', $facePhotoUrl);
            $img_url = '/upload/lilin_img/face/' . $faceArr['1'];
            $extra_data_img['facePhotoUrl'] = $accessInfo['facePhotoUrl'];
            $extra_data_img['facePhotoName'] = $accessInfo['facePhotoName'];
        }
        if (!empty($accessInfo) && isset($accessInfo['accessCardList']) && !empty($accessInfo['accessCardList'])) {
            foreach ($accessInfo['accessCardList'] as $cvv) {
                if ($cvv['accessCardType'] == 2 && $cvv['accessCardNo']) {
                    $bindUser['id_card'] = $cvv['accessCardNo'];
                }
                if (in_array($cvv['accessCardType'], [1, 5]) && $cvv['accessCardNo']) {
                    $bindUser['ic_card'] = $cvv['accessCardNo'];
                }
            }
        }
        $bindUser['uid_third_id'] = $personInfo['id'];
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $noFindArr = array();
        $houseFaceImg = new HouseFaceImg();
        $nowTime = time();
        foreach ($houseInfo as $room) {
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('third_room_source', '=', 'lilin_pf');
            $whereArr[] = array('third_room_id', '=', $room['homeStructId']);
            $whereArr[] = array('is_del', '<', 1);
            $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
            if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
                $userVacancy = $userVacancyObj->toArray();
                //找到房间
                $tmpBindUser = $bindUser;
                $tmpBindUser['bind_third_id'] = $room['homeStructId'];
                $tmpBindUser['usernum'] = $userVacancy['usernum'];
                $tmpBindUser['housesize'] = $userVacancy['housesize'];
                $tmpBindUser['room_addrss'] = $userVacancy['room'];
                $tmpBindUser['floor_id'] = $userVacancy['floor_id'];
                $tmpBindUser['vacancy_id'] = $userVacancy['pigcms_id'];
                $tmpBindUser['single_id'] = $userVacancy['single_id'];
                $tmpBindUser['layer_id'] = $userVacancy['layer_id'];
                $tmpBindUser['room_number'] = $userVacancy['room_number'];
                $tmpBindUser['bind_number'] = $userVacancy['property_number'];
                $tmpBindUser['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $updateArr = array('name' => $tmpBindUser['name'], 'phone' => $tmpBindUser['phone'], 'type' => $tmpBindUser['type'], 'user_status' => 1);
                if (isset($room['accessEffectTime']) && $room['accessEffectTime']) {
                    $tmpBindUser['property_starttime'] = strtotime($room['accessEffectTime']);
                    $updateArr['property_starttime'] = $tmpBindUser['property_starttime'];
                }
                if (isset($room['accessExpireTime']) && $room['accessExpireTime']) {
                    $tmpBindUser['property_endtime'] = strtotime($room['accessExpireTime']);
                    $updateArr['property_endtime'] = $tmpBindUser['property_endtime'];
                }
                $whereArr = array(['village_id','=',$rVillage['village_id']]);
                $whereArr[] = array('third_type', '=', 'lilin_pf');
                $whereArr[] = array('uid_third_id', '=', $personInfo['id']);
                $whereArr[] = array('type', '<', 4);
                $whereArr[] = array('vacancy_id', '=', $userVacancy['pigcms_id']);
                $whereArr[] = array('status', '<', 4);
                $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
                if ($userBindObj && !$userBindObj->isEmpty()) {
                    $updateArr['uid'] = $userBindObj['uid'];
                    $houseVillageUserBindDb->saveOne(array('pigcms_id' => $userBindObj['pigcms_id']), $tmpBindUser);
                    if ($img_url) {
                        $saveImgArr = array('face_img_id' => 0, 'uid' => $userBindObj['uid'], 'img_url' => $img_url);
                        $saveImgArr['status'] = 0;
                        $saveImgArr['a2_face_door_img_name'] = $extra_data_img['facePhotoName'];
                        $saveImgArr['a2_face_door_img'] = $extra_data_img['facePhotoUrl'];
                        $saveImgArr['bind_pigcms_id'] = $userBindObj['pigcms_id'];
                        $saveImgArr['extra_data'] = json_encode($extra_data_img, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $whereImgArr = array('bind_pigcms_id' => $userBindObj['pigcms_id']);
                        $faceImgObj = $houseFaceImg->getOne($whereImgArr, '*');
                        if ($faceImgObj && !$faceImgObj->isEmpty()) {
                            $saveImgArr['last_time'] = $nowTime;
                            $houseFaceImg->saveOne(array('id' => $faceImgObj['id']), $saveImgArr);
                        } else {
                            $saveImgArr['add_time'] = $nowTime;
                            $houseFaceImg->addOne($saveImgArr);
                        }

                    }
                } else {
                    $tmpBindUser['add_time'] = $nowTime;
                    $pigcms_id = $houseVillageUserBindDb->addOne($tmpBindUser);
                    if ($img_url) {
                        $saveImgArr = array('face_img_id' => 0, 'uid' => 0, 'img_url' => $img_url, 'add_time' => $nowTime);
                        $saveImgArr['status'] = 0;
                        $saveImgArr['a2_face_door_img_name'] = $extra_data_img['facePhotoName'];
                        $saveImgArr['a2_face_door_img'] = $extra_data_img['facePhotoUrl'];
                        $saveImgArr['bind_pigcms_id'] = $pigcms_id;
                        $saveImgArr['extra_data'] = json_encode($extra_data_img, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $houseFaceImg->addOne($saveImgArr);
                    }
                }
                $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancy['pigcms_id']), $updateArr);

            } else {
                $noFindArr[] = $room['homeStructId'];

            }
        }
        $err_code = 0;
        $err_msg = date('Y-m-d H:i:s') . '处理成功！';
        if (!empty($noFindArr)) {
            $err_code = 1;
            $err_msg = '住家节点ID为' . implode('，', $noFindArr) . '的房屋信息未找到，未入住成功！';

        }
        $returnArr['err_code'] = $err_code;
        $returnArr['err_msg'] = $err_msg;
        $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $personInfo['id'], 'n_pid' => 0, 'func_type' => 'roomUser');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = 0;
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = $err_msg;
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;

    }

    public function pushDelHousehold($pdata)
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'roomUser');
            $addData['opt_type'] = 'delRoomUser';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_type', '=', 'lilin_pf');
        $whereArr[] = array('uid_third_id', '=', $pdata['personId']);
        $whereArr[] = array('bind_third_id', '=', $pdata['homeStructId']);
        $delSave = array('status' => 4);
        $houseVillageUserBindDb->saveOne($whereArr, $delSave);

        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'roomUser');
        $addData['opt_type'] = 'delRoomUser';
        $addData['xtype'] = 0;
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . '处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    public function pushAddOrEditWorker($pdata, $otype = 'add')
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $opt_type = 'addVillageWorker';
        if ($otype == 'edit') {
            $opt_type = 'editVillageWorker';
        }
        $personInfo = isset($pdata['personInfo']) ? $pdata['personInfo'] : array();
        $accessInfo = isset($pdata['accessInfo']) ? $pdata['accessInfo'] : array();
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $personInfo['id'], 'n_pid' => 0, 'func_type' => 'villageWorker');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseWorkerDb = new HouseWorker();
        $bindUser = array('village_id' => $rVillage['village_id'], 'usernum' => '');
        $bindUser['name'] = $personInfo['personName'];
        $bindUser['phone'] = isset($personInfo['phoneNumber']) ? $personInfo['phoneNumber'] : '';
        $bindUser['type'] = 4;

        $bindUser['id_card'] = isset($personInfo['certificatesNumber']) ? $personInfo['certificatesNumber'] : '';
        $bindUser['birth'] = isset($personInfo['birthYear']) ? $personInfo['birthYear'] : '';
        $bindUser['memo'] = isset($personInfo['remark']) ? $personInfo['remark'] : '';
        if (!empty($accessInfo) && isset($accessInfo['accessCardList']) && !empty($accessInfo['accessCardList'])) {
            foreach ($accessInfo['accessCardList'] as $cvv) {
                if ($cvv['accessCardType'] == 2 && $cvv['accessCardNo']) {
                    $bindUser['id_card'] = $cvv['accessCardNo'];
                }
                if (in_array($cvv['accessCardType'], [1, 5]) && $cvv['accessCardNo']) {
                    $bindUser['ic_card'] = $cvv['accessCardNo'];
                }
            }
        }
        $bindUser['usernum'] = 'lilin' . $personInfo['id'];
        $bindUser['third_type'] = 'lilin_pf';
        $bindUser['uid_third_id'] = $personInfo['id'];
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $houseFaceImg = new HouseFaceImg();
        $img_url = '';
        $extra_data_img = array();
        if (!empty($accessInfo) && isset($accessInfo['facePhotoUrl']) && !empty($accessInfo['facePhotoUrl'])) {
            $facePhotoUrl = strtolower($accessInfo['facePhotoUrl']);
            $faceArr = explode('certificate/face/', $facePhotoUrl);
            $img_url = '/upload/lilin_img/face/' . $faceArr['1'];
            $extra_data_img['facePhotoUrl'] = $accessInfo['facePhotoUrl'];
            $extra_data_img['facePhotoName'] = $accessInfo['facePhotoName'];
        }
        $workerArr = array('village_id' => $rVillage['village_id'], 'phone' => $bindUser['phone'], 'name' => $bindUser['name']);
        $workerArr['status'] = 1;
        $workerArr['type'] = $personInfo['personType'];
        $workerArr['id_card'] = $bindUser['id_card'];
        $workerArr['gender'] = $personInfo['sex'] > 0 ? $personInfo['sex'] : 0;
        $workerArr['note'] = $bindUser['memo'];
        $workerArr['property_id'] = $rVillage['property_id'];
        $workerArr['account'] = 'lilin' . $personInfo['id'];
        $workerArr['password'] = md5('123abc');
        $workerArr['third_source'] = 'lilin_pf';
        $workerArr['third_source_id'] = $personInfo['id'];
        $workerArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_source', '=', 'lilin_pf');
        $whereArr[] = array('third_source_id', '=', $personInfo['id']);
        $whereArr[] = array('is_del', '<', 1);
        $houseWorkerObj = $houseWorkerDb->get_one($whereArr, '*');
        $wid = 0;
        $nowTime = time();
        if ($houseWorkerObj && !$houseWorkerObj->isEmpty()) {
            $wid = $houseWorkerObj['wid'];
            $houseWorkerDb->saveOneData(array('wid' => $wid), $workerArr);
        } else {
            $workerArr['create_time'] = $nowTime;
            $propertyGroup= new PropertyGroup();
            $workerArr['department_id'] = 0;
            $workerArr['people_type'] = 0;
            $whereGroupArr=array(['village_id','=',$rVillage['village_id']],['type','=','0'],['status','=',1]);
            $propertyGroupObj=$propertyGroup->getOne($whereGroupArr,'*','id desc');
            if($propertyGroupObj && !$propertyGroupObj->isEmpty()){
                $workerArr['department_id']=$propertyGroupObj['id'];
            }
            $workerArr['xtype'] = 2;
            $wid = $houseWorkerDb->addData($workerArr);
        }
        if ($wid > 0) {
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('third_type', '=', 'lilin_pf');
            $whereArr[] = array('uid_third_id', '=', $personInfo['id']);
            $whereArr[] = array('type', '=', 4);
            $whereArr[] = array('wid', '=', $wid);
            $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
            if ($userBindObj && !$userBindObj->isEmpty()) {
                $pigcms_id = $userBindObj['pigcms_id'];
                $houseVillageUserBindDb->saveOne(array('pigcms_id' => $userBindObj['pigcms_id']), $bindUser);
                if ($img_url) {
                    $saveImgArr = array('face_img_id' => 0, 'uid' => $userBindObj['uid'], 'img_url' => $img_url);
                    $saveImgArr['status'] = 0;
                    $saveImgArr['a2_face_door_img_name'] = $extra_data_img['facePhotoName'];
                    $saveImgArr['a2_face_door_img'] = $extra_data_img['facePhotoUrl'];
                    $saveImgArr['bind_pigcms_id'] = $userBindObj['pigcms_id'];
                    $saveImgArr['extra_data'] = json_encode($extra_data_img, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $whereImgArr = array('bind_pigcms_id' => $userBindObj['pigcms_id']);
                    $faceImgObj = $houseFaceImg->getOne($whereImgArr, '*');
                    if ($faceImgObj && !$faceImgObj->isEmpty()) {
                        $saveImgArr['last_time'] = $nowTime;
                        $houseFaceImg->saveOne(array('id' => $faceImgObj['id']), $saveImgArr);
                    } else {
                        $saveImgArr['add_time'] = $nowTime;
                        $houseFaceImg->addOne($saveImgArr);
                    }

                }
            } else {
                $bindUser['add_time'] = $nowTime;
                $pigcms_id = $houseVillageUserBindDb->addOne($bindUser);
                if ($img_url) {
                    $saveImgArr = array('face_img_id' => 0, 'uid' => 0, 'img_url' => $img_url, 'add_time' => $nowTime);
                    $saveImgArr['status'] = 0;
                    $saveImgArr['a2_face_door_img_name'] = $extra_data_img['facePhotoName'];
                    $saveImgArr['a2_face_door_img'] = $extra_data_img['facePhotoUrl'];
                    $saveImgArr['bind_pigcms_id'] = $pigcms_id;
                    $saveImgArr['extra_data'] = json_encode($extra_data_img, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $houseFaceImg->addOne($saveImgArr);
                }
            }
            $houseWorkerDb->saveOneData(array('wid' => $wid), array('bind_pigcms_id' => $pigcms_id));
        }

        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $personInfo['id'], 'n_pid' => 0, 'func_type' => 'villageWorker');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = 0;
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . '处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    public function pushDelWorker($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'villageWorker');
            $addData['opt_type'] = 'delVillageWorker';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            return $returnArr;
        }
        $houseWorkerDb = new HouseWorker();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_source', '=', 'lilin_pf');
        $whereArr[] = array('third_source_id', '=', $pdata['personId']);
        $whereArr[] = array('is_del', '<', 1);
        $houseWorkerObj = $houseWorkerDb->get_one($whereArr, '*');
        $houseAdminDb = new HouseAdmin();
        $houseVillageUserBindDb = new HouseVillageUserBind();
        if ($houseWorkerObj && !$houseWorkerObj->isEmpty()) {
            $wid = $houseWorkerObj['wid'];
            $houseWorkerDb->saveOneData(array('wid' => $wid), array('status' => 4, 'is_del' => 1, 'del_time' => time()));
            if ($houseWorkerObj['xtype'] == 2 && $houseWorkerObj['relation_id'] > 0) {
                $whereTmp = array('id' => $houseWorkerObj['relation_id'], 'village_id' => $rVillage['village_id']);
                $houseAdminDb->delHouseAdmin($whereTmp);
            }
            $whereArr = array(['village_id' ,'=', $rVillage['village_id']]);
            $whereArr[] = array('third_type', '=', 'lilin_pf');
            $whereArr[] = array('uid_third_id', '=', $pdata['personId']);
            $whereArr[] = array('type', '=', 4);
            $whereArr[] = array('wid', '=', $wid);
            $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
            if ($userBindObj && !$userBindObj->isEmpty()) {
                $houseVillageUserBindDb->delOne(array('pigcms_id' => $userBindObj['pigcms_id']));
            }
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'villageWorker');
        $addData['opt_type'] = 'delVillageWorker';
        $addData['xtype'] = 0;
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . '处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    public function pushAccessEvent($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'accessEvent');
            $addData['opt_type'] = 'accessEvent';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        fdump_api(['pdata' => $pdata], 'liLinAccess/pushAccessEvent');
        $houseUserLog = new HouseUserLog();
        $saveArr = array('village_id' => $rVillage['village_id'], 'log_business_id' => $rVillage['village_id']);
        /* 1 IC卡2身份证3二维码4指纹5蓝牙6 CPU卡7人脸识别8动态密码9远程开门10 APP远程开门11静态密码开门12开门按钮 13IC密码 14身份证密码 15CPU密码 16IC人脸 17身份证人脸 18CPU人脸**/
        $cardTypeFrom = array('1' => '3', '2' => '11', '3' => '31', '4' => '45', '5' => '21', '6' => '22', '7' => '1', '8' => '42', '9' => '2', '10' => '2', '11' => '41', '12' => '23', '14' => '11', '15' => '22', '16' => '1', '17' => '1', '18' => '1');
        $saveArr['log_from'] = 1;
        if (isset($pdata['cardType']) && isset($cardTypeFrom[$pdata['cardType']])) {
            $saveArr['log_from'] = $cardTypeFrom[$pdata['cardType']];
        }
        $saveArr['card_number'] = $pdata['cardNo'];
        $houseVillageUserBindDb = new HouseVillageUserBind();
        if (isset($pdata['personId']) && $pdata['personId']) {
            $saveArr['person_code'] = $pdata['personId'];
            $saveArr['person_name'] = isset($pdata['personName']) && $pdata['personName'] ? $pdata['personName'] : '';
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('third_type', '=', 'lilin_pf');
            $whereArr[] = array('uid_third_id', '=', $pdata['personId']);
            $whereArr[] = array('status', '<', 4);
            $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
            if ($userBindObj && !$userBindObj->isEmpty()) {
                $saveArr['uid'] = $userBindObj['uid'];
                $saveArr['log_bind_id'] = $userBindObj['pigcms_id'];
                $saveArr['person_name'] = $userBindObj['name'];
            }
        } else if (isset($pdata['accountId']) && $pdata['accountId']) {
            $tmpLen=strlen($pdata['accountId']);
            if($tmpLen>10){
                //访客
                $saveArr['person_name']='访客';
            }else{
                $whereArr = array(['village_id' ,'=', $rVillage['village_id']]);
                $whereArr[] = array('pigcms_id', '=', $pdata['accountId']);
                $whereArr[] = array('status', '<', 4);
                $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
                if ($userBindObj && !$userBindObj->isEmpty()) {
                    $saveArr['uid'] = $userBindObj['uid'];
                    $saveArr['log_bind_id'] = $userBindObj['pigcms_id'];
                    $saveArr['person_name'] = $userBindObj['name'];
                }
            }

        } else if ($pdata['cardType'] == 1 && isset($pdata['cardNo']) && $pdata['cardNo']) {
            $whereArr = array(['village_id' ,'=', $rVillage['village_id']]);
            $whereArr[] = array('ic_card', '=', $pdata['cardNo']);
            $whereArr[] = array('status', '<', 4);
            $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
            if ($userBindObj && !$userBindObj->isEmpty()) {
                $saveArr['uid'] = $userBindObj['uid'];
                $saveArr['log_bind_id'] = $userBindObj['pigcms_id'];
                $saveArr['person_name'] = $userBindObj['name'];
            }
        }
        /**1住户卡2物业卡3巡更卡4访客卡5管理卡10失效卡（己注册，但被注销或过期）11非法卡（该卡号不存在，就是陌生人）12异常卡（有开启防复制情况下，但不满足加密要求，就是复制卡开门) 13正常卡****/
        $saveArr['card_number_type'] = isset($pdata['card_number_type']) && $pdata['card_number_type'] ? $pdata['card_number_type'] : 1;
        $device_id = 0;
        $houseFaceDevice = new HouseFaceDevice();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('is_del', '<', 4);
        if (isset($pdata['deviceId']) && $pdata['deviceId']) {
            $whereArr[] = array('third_dev_id', '=', $pdata['deviceId']);
        } else {
            $whereArr[] = array('device_sn', '=', $pdata['did']);
        }
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            $device_id = $faceDeviceObj['device_id'];
        } else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '设备不存在！';
            fdump_api(['pdata' => $pdata, 'whereArr' => $whereArr], 'liLinAccess/pushAccessEvent', true);
            return $returnArr;
        }
        $saveArr['device_sn'] = $pdata['did'];
        $nowtime = time();
        $saveArr['log_time'] = isset($pdata['eventTime']) && $pdata['eventTime'] ? ($pdata['eventTime'] / 1000) : $nowtime;
        if (isset($pdata['eventId']) && $pdata['eventId']) {
            $saveArr['eventId'] = $pdata['eventId'];
        }
        $saveArr['log_status'] = 0;
        if (isset($pdata['isUnlock']) && in_array($pdata['isUnlock'], [1, 3, 5, 7])) {
            $saveArr['log_status'] = 1;
        }
        $saveArr['direction_type'] = 1;
        if (isset($pdata['isUnlock']) && in_array($pdata['isUnlock'], [3, 4, 7, 8])) {
            $saveArr['direction_type'] = 2;  //2出
        }
        if (isset($pdata['catchPhotos']) && $pdata['catchPhotos']) {
            $cut_image = $pdata['catchPhotos']['0']['photoName'];
            $photoArr = explode('_', $cut_image);
            $imgTime = $photoArr['1'];
            $imgSavePath = '/upload/lilin_img/face_access';
            if ($imgTime > 0) {
                $imgTimeStr = strval($imgTime);
                if (strlen($imgTimeStr) > 10) {
                    $imgTime = $imgTime / 1000;
                    $imgTime = intval($imgTime);
                }
                $imgSavePath = $imgSavePath . '/' . date('Ymd', $imgTime);
            }
            $imgSavePath = $imgSavePath . '/';
            $imgPath = $imgSavePath . $cut_image;
            $saveArr['cut_image'] = $imgPath;
        }
        $saveArr['log_name'] = isset($pdata['deviceName']) && $pdata['deviceName'] ? $pdata['deviceName'] : '';
        $third_log_id = $pdata['id'];
        $saveArr['log_detail'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $saveArr['record_log'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $saveArr['add_time'] = $nowtime;
        $whereArr = array('village_id' => $rVillage['village_id'], 'device_id' => $device_id, 'third_log_id' => $third_log_id);
        $userLogObj = $houseUserLog->getFind($whereArr, '*');
        if ($userLogObj && !$userLogObj->isEmpty()) {
            $houseUserLog->updateThis(array('log_id' => $userLogObj['log_id']), $saveArr);
        } else {
            $houseUserLog->addData($saveArr);
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        return $returnArr;
    }

    public function pushPhoto($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';

            return $returnArr;
        }
        if (empty($pdata['photoName']) || empty($pdata['data'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '图片数据错误！';

            return $returnArr;
        }
        fdump_api(['pdata' => $pdata], 'liLinAccess/pushPhoto');
        $imageFile = $pdata['data'];
        $type = $pdata['type'];
        /**（1：小区住户2：工作人员3：访客登记记录4：门禁记录5对讲记录6车辆出入记录****/
        if (!in_array($type, [1, 2, 4])) {
            $returnArr['err_code'] = 0;
            $returnArr['err_msg'] = '处理成功了！';
        }
        $service_image = new ImageService();
        $fileHandle = new FileHandle();
        /*
        $image_data = [];
        $image_data['imgPath'] = $imageFile;
        $image_data['with'] = 600;
        $image_data['heiht'] = 800;
        $imageFileCompress = $service_image->encodeImgToDataUrl($image_data);
        if (!empty($imageFileCompress)) {
            $imageFile = str_replace('data:image/jpeg;base64,', '', $imageFileCompress);
        }
        */

        $imgSavePath = '/upload/lilin_img/face';
        if ($type == 4) {
            /****垃圾图片推送多，好多无用的*****/
            $returnArr['err_code'] = 0;
            $returnArr['err_msg'] = '处理成功！';
            return $returnArr;  
            $imgSavePath = '/upload/lilin_img/face_access';
            $photoArr = explode('-', $pdata['photoName']);
            $did = $photoArr['0'];
            $imgTime = $photoArr['1'];
            if ($imgTime > 0) {
                $imgTimeStr = strval($imgTime);
                if (strlen($imgTimeStr) > 10) {
                    $imgTime = $imgTime / 1000;
                    $imgTime = intval($imgTime);
                }
                $imgSavePath = $imgSavePath . '/' . date('Ymd', $imgTime);
            }
        }
        if ($type == 1 || $type == 2) {
            $faceArr = explode('certificate/face/', $pdata['filePath']);
            $imgSavePath = $imgSavePath . '/' . $faceArr['1'];
        }

        $imgSavePath = $imgSavePath . '/';
        $imgPath = $imgSavePath . $pdata['photoName'];
        $rootPath = app()->getRootPath() . '..';
        $imgSavePath = $rootPath . $imgSavePath;
        $image_big_root = base64_to_img($imgSavePath, $pdata['photoName'], $imageFile, '');
        fdump_api(['imgSavePath' => $imgSavePath, 'imgPath' => $imgPath, 'image_big_root' => $image_big_root], 'liLinAccess/pushPhoto', true);
        if ($image_big_root && $fileHandle->check_open_oss()) {
            try {
                $info = $fileHandle->upload($image_big_root);
                if (isset($info['error']) && !$info['error']) {
                    @unlink($image_big_root);
                }
            } catch (\Exception $e) {
                fdump_api(['msg' => '图片处理出错', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root], 'liLinAccess/pushPhoto', true);
            } catch (\Throwable $e) {
                fdump_api(['msg' => '图片处理出错', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root], 'liLinAccess/pushPhoto', true);
            } catch (\RuntimeException $e) {
                fdump_api(['msg' => '图片处理出错', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root], 'liLinAccess/pushPhoto', true);
            } catch (\InvalidArgumentException $e) {
                fdump_api(['msg' => '图片处理出错', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root], 'liLinAccess/pushPhoto', true);
            }
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        return $returnArr;
    }

    public function pushAddVisitor($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';

            return $returnArr;
        }
        fdump_api(['pdata' => $pdata], 'liLinAccess/pushAddVisitor');
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        return $returnArr;
    }

    public function pushCallEvent($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';

            return $returnArr;
        }
        fdump_api(['pdata' => $pdata], 'liLinAccess/pushCallEvent');

        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_room_source', '=', 'lilin_pf');
        $houseId = $pdata['dstNo'] * 1;
        $whereArr[] = array('houseId', '=', $houseId);
        $whereArr[] = array('is_del', '<', 1);
        $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
        $userVacancy = array();
        if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
            $userVacancy = $userVacancyObj->toArray();
        }
        if (empty($userVacancy)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没查到对讲的房间数据！';
            return $returnArr;
        }
        $houseUserdevCallRecord = new HouseUserdevCallRecord();
        $saveArr = array('village_id' => $rVillage['village_id'], 'from_type' => 'lilin_pf', 'device_type' => 'door');
        $talkTimeStr = strval($pdata['talkTime']);
        $talkTime = $pdata['talkTime'];
        if (strlen($talkTimeStr) > 10) {
            $talkTime = $pdata['talkTime'] / 1000;
            $talkTime = intval($talkTime);
        }
        $saveArr['room_id'] = $userVacancy['pigcms_id'];
        $saveArr['talk_time'] = $talkTime;
        $saveArr['is_answer'] = $pdata['isAnswer'] == 1 ? 1 : 0; //1已应答
        $saveArr['is_open'] = $pdata['isUnlock'] == 1 ? 1 : 0; //已开门
        if (isset($pdata['callId']) && $pdata['callId']) {
            $saveArr['event_id'] = $pdata['callId'];
        }
        if (isset($pdata['unlockTime']) && $pdata['unlockTime']) {
            $unlockTime = $pdata['unlockTime'];
            $unlockTimeStr = strval($pdata['unlockTime']);
            if (strlen($unlockTimeStr) > 10) {
                $unlockTime = $unlockTime / 1000;
                $unlockTime = intval($unlockTime);
            }
            $saveArr['open_time'] = $unlockTime;
        }
        $saveArr['dst_no'] = $pdata['dstNo'];
        $saveArr['src_no'] = $pdata['srcNo'];
        $houseFaceDevice = new HouseFaceDevice();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('device_no', '=', $pdata['srcNo']);
        $whereArr[] = array('is_del', '<', 4);
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            $saveArr['device_id'] = $faceDeviceObj['id'];
            $saveArr['device_sn'] = $faceDeviceObj['device_sn'];
        }
        $saveArr['third_record_id'] = $pdata['id'];
        $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $whereArr = array(['village_id' ,'=', $rVillage['village_id']]);
        $whereArr[] = array('from_type', '=', 'lilin_pf');
        $whereArr[] = array('room_id', '=', $userVacancy['pigcms_id']);
        $whereArr[] = array('third_record_id', '=', $pdata['id']);

        $userdevCallRecordObj = $houseUserdevCallRecord->getOneData($whereArr, '*');
        if ($userdevCallRecordObj && !$userdevCallRecordObj->isEmpty()) {
            $houseUserdevCallRecord->saveOneData(array('id' => $userdevCallRecordObj['id']), $saveArr);
        } else {
            $saveArr['add_time'] = time();
            $houseUserdevCallRecord->addOneData($saveArr);
        }

        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        return $returnArr;
    }
    public function getCallRecordList($whereArr=array(),$field='*',$page=1,$limit=10){
        $houseUserdevCallRecord = new HouseUserdevCallRecord();
        $returnArr = array('list' =>array(), 'page_size' => $limit, 'count' => 0);
        $count=$houseUserdevCallRecord->getCount($whereArr);
        if($count>0){
            $houseVillageService=new HouseVillageService();
            $houseVillageDb = new HouseVillage();
            $returnArr['count']=$count;
            $callRecordsObj= $houseUserdevCallRecord->getListData($whereArr,$field,'talk_time desc',$page,$limit);
            if($callRecordsObj && !$callRecordsObj->isEmpty()){
                $callRecords=$callRecordsObj->toArray();
                $room_address_arr=array();
                $village_arr=array();
                foreach ($callRecords as $kk=>$vv){
                    $village_name='';
                    if(isset($village_arr[$vv['village_id']])){
                        $village_name=$village_arr[$vv['village_id']];
                    }else{
                        $wherehArr = array('village_id' => $vv['village_id']);
                        $houseVillageObj = $houseVillageDb->getInfo($wherehArr,'village_id,village_name');
                        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                            $hVillage = $houseVillageObj->toArray();
                            $village_arr[$vv['village_id']]=$hVillage['village_name'];
                            $village_name=$hVillage['village_name'];
                        } 
                    }
                    if(isset($room_address_arr[$vv['room_id']])){
                        $callRecords[$kk]['room_address']=$room_address_arr[$vv['room_id']];
                    }else{
                        $room_address=$houseVillageService->getSingleFloorRoom(0,0,0,$vv['room_id'],$vv['village_id']);
                        $room_address_arr[$vv['room_id']]=$village_name.$room_address;
                        $callRecords[$kk]['room_address']=$village_name.$room_address;
                    }
                    $callRecords[$kk]['talk_time_str']=date('Y-m-d H:i:s',$vv['talk_time']);
                    $callRecords[$kk]['open_time_str']=$vv['open_time'] >100 ? date('Y-m-d H:i:s',$vv['open_time']):'';
                }
            }
        }
        return $returnArr;
    }
    
    public function pushDoorStateEvent($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';

            return $returnArr;
        }
        fdump_api(['pdata' => $pdata], 'liLinAccess/pushDoorStateEvent');
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        return $returnArr;
    }

    public function pushDeviceAlarmEvent($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';

            return $returnArr;
        }
        fdump_api(['pdata' => $pdata], 'liLinAccess/pushDeviceAlarmEvent');
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        return $returnArr;
    }

    //处理公共参数
    private function getCommonParameter($version = '1.0')
    {
        $configService =new ConfigService();
        $lilin_pf_base_api= $configService ->getOneField('lilin_pf_base_api');
        $lilin_pf_base_api=$lilin_pf_base_api ? trim($lilin_pf_base_api):'';
        if($lilin_pf_base_api){
            $this->baseApi=$lilin_pf_base_api;
        }
        $lilin_pf_client_id= $configService ->getOneField('lilin_pf_client_id');
        $lilin_pf_client_secret= $configService ->getOneField('lilin_pf_client_secret');
        if(!empty($this->village_config) && isset($this->village_config['lilin_pf_base_api']) && $this->village_config['lilin_pf_base_api'] && isset($this->village_config['lilin_pf_client_id']) && $this->village_config['lilin_pf_client_id'] && $this->village_config['village_id']==$this->village_id){
            $this->baseApi=$this->village_config['lilin_pf_base_api'];
            $lilin_pf_client_id=$this->village_config['lilin_pf_client_id'];
            $lilin_pf_client_secret=$this->village_config['lilin_pf_client_secret'];
        }elseif($this->village_id>0){
            $houseVillageDb = new HouseVillage();
            $whereArr = array('village_id' => $this->village_id);
            $houseVillageObj = $houseVillageDb->getInfo($whereArr,'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no');
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $hVillage = $houseVillageObj->toArray();
                $this->baseApi=$hVillage['lilin_pf_base_api'];
                $lilin_pf_client_id=$hVillage['lilin_pf_client_id'];
                $lilin_pf_client_secret=$hVillage['lilin_pf_client_secret'];
                $this->village_config['village_id']=$this->village_id;
                $this->village_config['lilin_pf_base_api']=$this->baseApi;
                $this->village_config['lilin_pf_client_id']=$lilin_pf_client_id;
                $this->village_config['lilin_pf_client_secret']=$lilin_pf_client_secret;
                $this->village_config['lilin_village_no']=$hVillage['lilin_village_no'];;
            }
        }
        $this->clientId=$lilin_pf_client_id ? trim($lilin_pf_client_id):'';
        $this->clientSecret=$lilin_pf_client_secret ? trim($lilin_pf_client_secret):'';
        if($this->baseApi && strpos($this->baseApi,'http')===false){
            $this->baseApi='https://'.$this->baseApi;
        }
		$this->clientId='6260cb21-6403-4928-bec9-fc36910e5dba';
        $this->clientSecret='65c60449-5fe0-4cb8-9fac-b6805190aab1';
		//$this->baseApi="http://192.168.0.200:9999/Esafe-server-Web/rest/third/common";
		$this->baseApi="http://74ca62da.r16.cpolar.top/Esafe-Server-Web/rest/third/common";
        $paramsCommon = array();
        $paramsCommon = array();
        $paramsCommon['version'] = $version;
        $paramsCommon['clientId'] = $this->clientId;
        $timestamp = time();
        $timestamp = $timestamp . '' . rand(101, 931);
        $paramsCommon['timestamp'] = $timestamp;
        $paramsCommon['nonce'] = uniqid('lee', true) . rand(100000, 999999);
        $paramsCommon['signatureVersion'] = '1.0';
        return $paramsCommon;
    }

    /**
     * 数据签名.
     */
    public function getIncludeSignData($datas = array(), $methodName = '', $version = '1.0')
    {
        $retParam = $this->getCommonParameter($version);
        $dataStr = 'clientId=' . $retParam['clientId'] . '&method=' . $methodName . '&timestamp=' . $retParam['timestamp'] . '&nonce=' . $retParam['nonce'];
        $dataJosn = '';
        if (!empty($datas)) {
            krsort($datas);
            $dataJosn = json_encode($datas, JSON_UNESCAPED_UNICODE);
            $dataStr = $dataStr . '&data=' . $dataJosn;
        }
        $signStr = hash_hmac('sha1', $dataStr, $this->clientSecret);
        $signStr = base64_encode($signStr);
        $retParam['signature'] = $signStr;
        $retParam['data'] = $datas;
        return $retParam;
    }

    public function httpCurlPost($url = '', $data = array(), $methodName = '', $version = '1.0')
    {
        $params = $this->getIncludeSignData($data, $methodName, $version);
        $postparams = json_encode($params, JSON_UNESCAPED_UNICODE);
		print_r($postparams);
        $requestUrl = $this->baseApi . $url;  //正式环境
        $ch = curl_init();
        $headers[] = "Accept-Charset: utf-8";
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36');
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postparams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        fdump_api(['requestUrl' => $requestUrl, 'postparams' => $postparams, 'version' => $version, 'result' => $result], 'liLinAccess/httpCurlPost' . date('H'),true);
        $return = json_decode($result, true);
        //关闭curl
        curl_close($ch);
        if (empty($return)) {
            return '';
        }
        return $return;
    }
	 public function imgFileDownload($datas = array()){
		$uri = '/fileDownload';
		$resultinfo = $this->httpCurlPost($uri, $datas, 'getMonitorParams');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
		print_r($returnArr);
        return $returnArr;
	 }
    /***
     **第三方获取SDK设备监视参数
     **/
    public function getMonitorParams($datas = array())
    {
        $uri = '/third/an/getMonitorParams';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getMonitorParams');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.4. 第三方远程开门
     **/
    public function openDoor($deviceSn='',$phoneNumber='',$param = array())
    {
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if(empty($deviceSn) && isset($param['village_id']) && $param['village_id'] && !empty($param['device_no'])){
            $houseVillageDb = new HouseVillage();
            $whereArr = array('village_id' => $param['village_id']);
            $houseVillageObj = $houseVillageDb->getInfo($whereArr,'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no');
            $lilin_village_no = '';
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $hVillage = $houseVillageObj->toArray();
                $lilin_village_no = $hVillage['lilin_village_no'];
                $this->village_id=$param['village_id'];
                $this->village_config=$hVillage;
            }
            if(empty($lilin_village_no)){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '未获取到立林小区号,无法开门！';
                return $returnArr;
            }
            $deviceSn=$lilin_village_no.$param['device_no'];
        }else if(isset($param['village_id']) && $param['village_id']){
            $houseVillageDb = new HouseVillage();
            $whereArr = array('village_id' => $param['village_id']);
            $houseVillageObj = $houseVillageDb->getInfo($whereArr,'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no');
            $lilin_village_no = '';
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $hVillage = $houseVillageObj->toArray();
                $lilin_village_no = $hVillage['lilin_village_no'];
                $this->village_id=$param['village_id'];
                $this->village_config=$hVillage;
            }
        }
        if($this->village_id<1){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '小区信息错误！';
            return $returnArr;
        }
        if(empty($deviceSn) || empty($phoneNumber)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '参数错误,无法开门！';
            return $returnArr;
        }
        $datas=array('deviceSn'=>$deviceSn,'phoneNumber'=>$phoneNumber);
        $uri = '/third/an/opendoor';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'opendoor');
        
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            //请求是成功的
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '操作成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '操作失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.5. 第三方添加设备蓝牙
     **/
    public function addBluetooth($datas = array())
    {
        $uri = '/third/an/addBluetooth';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'addBluetooth');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }


    /***
     **4.6. 第三方添加设备二维码(二维码添加成功后，在4.11获取二维码字符串，可在设备扫码开门)
     **/
    public function addQrCode($datas = array())
    {
        $uri = '/third/an/addQrCode';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'addQrCode');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.7. 第三方获取设备动态密码 (不支持智慧社区)
     **/
    public function getDynamicPassword($datas = array())
    {
        $uri = '/third/an/getDynamicPassword';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getDynamicPassword');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.8. 第三方人脸图片上传
     **/
    public function photoUploadV2($datas = array())
    {
        $uri = '/third/an/photoUploadV2';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'photoUploadV2');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.9. 第三方添加设备人脸识别
     **/
    public function addFaceRecognize($datas = array())
    {
        $uri = '/third/an/addFaceRecognize';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'addFaceRecognize');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }


    /***
     **4.10. 第三方添加设备IC卡
     **/
    public function addIcCard($datas = array())
    {
        $uri = '/third/an/addIcCard';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'addIcCard');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }


    /***
     **4.11. 第三方获取二维码信息（需要先调用4.6添加二维码卡）
     **/
    public function getQrCode($datas = array())
    {
        $uri = '/third/an/getQrCode';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getQrCode');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }


    /***
     **4.12. 第三方删除设备IC卡
     **/
    public function removeIcCard($datas = array())
    {
        $uri = '/third/an/removeIcCard';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'removeIcCard');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.13. 第三方删除蓝牙到设备
     **/
    public function removeBluetooth($datas = array())
    {
        $uri = '/third/an/removeBluetooth';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'removeBluetooth');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.14. 第三方删除设备二维码
     **/
    public function removeQrCode($datas = array())
    {
        $uri = '/third/an/removeQrCode';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'removeQrCode');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.15. 第三方删除设备动态密码（不支持智慧社区）
     **/
    public function removeDynamicPassword($datas = array())
    {
        $uri = '/third/an/removeDynamicPassword';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'removeDynamicPassword');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.16. 第三方删除设备人脸识别
     **/
    public function removeFaceRecognize($datas = array())
    {
        $uri = '/third/an/removeFaceRecognize';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'removeFaceRecognize');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.17. 第三方根据设备sn获取设备名称（用于蓝牙开门）
     **/
    public function getDeviceName($datas = array())
    {
        $uri = '/third/an/getDeviceName';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getDeviceName');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.22. 获取住家门禁列表（仅用于蓝牙开门）
     **/
    public function getDoorList($datas = array())
    {
        $uri = '/third/an/getDoorList';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getDoorList');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.23. 获取门禁记录图片
     **/
    public function getEntranceUrls($datas = array())
    {
        $uri = '/third/getEntranceUrls';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getEntranceUrls');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }


    /***
     **4.24. 获取对讲记录图片
     **/
    public function getTelecomUrls($datas = array())
    {
        $uri = '/third/getTelecomUrls';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getTelecomUrls');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.25. 添加动态密码（仅支持智慧社区）
     **/
    public function getCustomPassword($datas = array())
    {
        $uri = '/third/an/getCustomPassword';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getCustomPassword');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    /***
     **4.26. 删除动态密码（仅支持智慧社区）
     **/
    public function delCustomPassword($datas = array())
    {
        $uri = '/third/an/delCustomPassword';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'delCustomPassword');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (!empty($resultinfo) && isset($resultinfo['result']) && $resultinfo['result'] == 1) {
            if (!empty($resultinfo) && isset($resultinfo['data']) && !empty($resultinfo['data'])) {
                //请求是成功的
                $returnArr['data'] = $resultinfo['data'];
            }
            $returnArr['err_msg'] = '请求成功！';
        } else {
            $returnArr['data'] = '';
            $returnArr['err_code'] = 1;
            $err_msg = '请求失败了！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }

    public function getPropertyServiceTime($village_id = 0, $vacancy_id = 0, $pigcms_id = 0)
    {
        $field1 = 'owe_property_open_door,owe_property_open_door_day,property_id';
        $db_house_village = new HouseVillage();
        $villageInfo = $db_house_village->getOne($village_id, $field1);
        $propertyServiceTime = array();
        $yesterday_time = time() - 86400;
        $yesterday_time = $yesterday_time;
        $propertyServiceTime['startTime'] = $yesterday_time;
        //$propertyServiceTime['endTime'] = 4133779200;  //2100-12-30 00:00:00
        $propertyServiceTime['endTime'] = 2145924000;  //2038-01-01 10:00:00
        if (empty($villageInfo) || $villageInfo->isEmpty()) {
            return $propertyServiceTime;
        }
        if (empty($vacancy_id) || $vacancy_id < 1) {
            $propertyServiceTime['startTime'] = $propertyServiceTime['startTime'] * 1000;
            $propertyServiceTime['endTime'] = $propertyServiceTime['endTime'] * 1000;
            return $propertyServiceTime;
        }
        // $propertyServiceTime['startTime'] = $yesterday_time-3600;
        // $propertyServiceTime['endTime'] = $yesterday_time;  

        $db_house_new_order_log = new HouseNewOrderLog();
        $whereNewOrder = array('room_id' => $vacancy_id, 'order_type' => 'property');
        //查当前房间的服务结束时间
        $order_log = $db_house_new_order_log->getOne($whereNewOrder);
        //查当前房间的服务开始时间
        $order_log_start = $db_house_new_order_log->getOne($whereNewOrder, true, 'id ASC');
        $arr = array();
        if ($order_log_start && isset($order_log_start['service_start_time']) && ($order_log_start['service_start_time'] > 100)) {
            $arr['propertyStartTime'] = $order_log_start['service_start_time'];
            $arr['propertyEndTime'] = $order_log_start['service_end_time'];
            if ($order_log && isset($order_log['service_end_time']) && ($order_log['service_end_time'] > $arr['propertyEndTime'])) {
                $arr['propertyEndTime'] = $order_log['service_end_time'];
            }
            if ($arr['propertyEndTime'] < $arr['propertyStartTime']) {
                $arr['propertyEndTime'] = $arr['propertyStartTime'];
            }
        }
        if (isset($villageInfo['owe_property_open_door']) && $villageInfo['owe_property_open_door'] && isset($arr['propertyEndTime']) && isset($villageInfo['owe_property_open_door_day']) && $villageInfo['owe_property_open_door_day'] && $arr['propertyEndTime'] > 100) {
            $arr['propertyEndTime'] += intval($villageInfo['owe_property_open_door_day']) * 86400;
        } else if (isset($villageInfo['owe_property_open_door']) && $villageInfo['owe_property_open_door'] && isset($villageInfo['owe_property_open_door_day']) && !$villageInfo['owe_property_open_door_day']) {
            // 允许延期开门 且为0 默认不限制 2038
            if (!isset($arr['propertyStartTime'])) {
                $arr['propertyStartTime'] = $yesterday_time;
            }
            //$arr['propertyEndTime'] = 4133779200;
            $arr['propertyEndTime'] = 2145924000;  //2038-01-01 10:00:00
            
        }
        if (isset($arr['propertyStartTime']) && ($arr['propertyStartTime'] > 846656000)) {
            $propertyServiceTime['startTime'] = $arr['propertyStartTime'];
        }
        if (isset($arr['propertyEndTime']) && ($arr['propertyEndTime'] > 846656000)) {
            $propertyServiceTime['endTime'] = $arr['propertyEndTime'];
        }
        //转换成毫秒级
        $propertyServiceTime['startTime'] = $propertyServiceTime['startTime'] * 1000;
        $propertyServiceTime['endTime'] = $propertyServiceTime['endTime'] * 1000;
        return $propertyServiceTime;
    }

    public function createAndGetQrCode($village_id = 0, $pigcms_id = 0, $visitor_id = 0)
    {
        $qrCodeData = array('floorType' => 0, 'neighNo' => '');
        $houseVillageDb = new HouseVillage();
        $whereArr = array('village_id' => $village_id);
        $houseVillageObj = $houseVillageDb->getInfo($whereArr,'village_id,village_name,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no');
        $lilin_village_no = '';
        $village_name='';
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $hVillage = $houseVillageObj->toArray();
            $lilin_village_no = $hVillage['lilin_village_no'];
            $village_name=$hVillage['village_name'];
            $this->village_id=$village_id;
            $this->village_config=$hVillage;
        }
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if (empty($lilin_village_no)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '未获取到立林小区号,无法生成二维码！';
            return $returnArr;
        }

        $qrCodeData['neighNo'] = $lilin_village_no;
        $whereArr = array('village_id' => $village_id, 'pigcms_id' => $pigcms_id);
        $houseVillageUserBind = new HouseVillageUserBind();
        $fieldStr = 'pigcms_id,village_id,uid,name,phone,floor_id,type,vacancy_id,id_card,ic_card,single_id,property_starttime,property_endtime,door_code,extra_data';
        $oneDataObj = $houseVillageUserBind->getOne($whereArr, $fieldStr, 'pigcms_id desc');
        $userBind = array();
        if ($oneDataObj && !$oneDataObj->isEmpty()) {
            $userBind = $oneDataObj->toArray();
        }
        if (empty($userBind)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '业主身份数据不存在,无法生成二维码！';
            return $returnArr;
        }
        $houseFaceDevice = new HouseFaceDevice();
        $whereArr = array(['village_id','=' ,$village_id]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('device_no', '<>', '');
        $whereArr[] = array('is_del', '<', 4);
        $faceDevices = $houseFaceDevice->getDeviceLists($whereArr, 'device_id,device_no,device_name,floor_id,single_id', 'device_id desc', 0);
        $device_no_arr = array();
        $device_list = array();
        if ($faceDevices) {
            foreach ($faceDevices as $fvv) {
                $floorIdArr = array();
                if (isset($device['floor_id']) && $device['floor_id'] && $device['floor_id'] != '-1' && $device['floor_id'] != '0') {
                    $floorIdArr = explode(',', $device['floor_id']);
                }
                if (!empty($floorIdArr) && $userBind['type'] != 4 && $userBind['floor_id'] > 0 && in_array($userBind['floor_id'], $floorIdArr)) {
                    $device_no_arr[] = $fvv['device_no'];
                    if(!isset($device_list[$fvv['device_id']])){
                        $device_list[$fvv['device_id']]=array('device_id'=>$fvv['device_id'],'device_no'=>$fvv['device_no'],'device_name'=>$fvv['device_name']);
                    }
                } else if ($userBind['type'] != 4 && $userBind['single_id'] > 0 && $userBind['single_id'] == $fvv['single_id']) {
                    $device_no_arr[] = $fvv['device_no'];
                    if(!isset($device_list[$fvv['device_id']])){
                        $device_list[$fvv['device_id']]=array('device_id'=>$fvv['device_id'],'device_no'=>$fvv['device_no'],'device_name'=>$fvv['device_name']);
                    }
                } else if ($userBind['type'] == 4) {
                    $device_no_arr[] = $fvv['device_no'];
                    if(!isset($device_list[$fvv['device_id']])){
                        $device_list[$fvv['device_id']]=array('device_id'=>$fvv['device_id'],'device_no'=>$fvv['device_no'],'device_name'=>$fvv['device_name']);
                    }
                }
                if ((empty($fvv['floor_id']) || $fvv['floor_id'] == '0') && $fvv['single_id'] == 0) {
                    //公共的
                    $device_no_arr[] = $fvv['device_no'];
                    if(!isset($device_list[$fvv['device_id']])){
                        $device_list[$fvv['device_id']]=array('device_id'=>$fvv['device_id'],'device_no'=>$fvv['device_no'],'device_name'=>$fvv['device_name']);
                    }
                }
                
            }
        }
        if (empty($device_no_arr)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有可生成二维码的设备！';
            return $returnArr;
        }
        $device_list=array_values($device_list);
        $device_no_arr = array_unique($device_no_arr);
        $houseVillageVisitEwm=new HouseVillageVisitEwm();
        $qr_id='';
        if ($visitor_id > 0) {
            //分享访客要生成的二维码
            $visitor_id_str = strval($visitor_id);
            $tmpLen = strlen($visitor_id_str);
            $phoneNumber = '22' . str_pad($visitor_id_str, 12, '0', STR_PAD_LEFT);
            $qrCodeData['phoneNumber'] = $phoneNumber;
            $qr_id=$phoneNumber;
            $visitEwmObj=$houseVillageVisitEwm->getOneData(array('id'=>$visitor_id),'*');
            if($visitEwmObj && !$visitEwmObj->isEmpty()){
                $visitEwm=$visitEwmObj->toArray();
                $startTime =$visitEwm['start_time']*1000;
                $endTime = $visitEwm['end_time']*1000;
                $qrCodeData['startTime'] = $startTime;
                $qrCodeData['endTime'] = $endTime;
            }else{
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '访客邀请数据不存在，无法生二维码！';
                return $returnArr;  
            }
        } else {
            $extra_data_arr=$userBind['extra_data'] ? json_decode($userBind['extra_data'],true):array();
            if($extra_data_arr && isset($extra_data_arr['lilin_ewm'])&& $extra_data_arr['lilin_ewm'] && isset($extra_data_arr['lilin_ewm']['qrCode'])){
                $retQrCode=array('err_code'=>0,'err_msg'=>'');
                $retQrCode['qrCode']=$extra_data_arr['lilin_ewm']['qrCode'];
                $retQrCode['startTime']=date('Y-m-d H:i',$extra_data_arr['lilin_ewm']['startTime']);
                $retQrCode['endTime']=date('Y-m-d H:i',$extra_data_arr['lilin_ewm']['endTime']);
                $retQrCode['village_name']=$village_name;
                $retQrCode['device_list']=$device_list;
                return $retQrCode;
            }
            //业主自己的二维码
            $startTime = time() - 86400;
            $startTime = $startTime * 1000;
            //$endTime = 4133779200000;  //2100-12-30 00:00:00
            $endTime = 2145924000;  //2038-01-01 10:00:00
            $propertyServiceTime = $this->getPropertyServiceTime($village_id, $userBind['vacancy_id'], $userBind['pigcms_id']);
            if ($propertyServiceTime && isset($propertyServiceTime['startTime'])) {
                $startTime = $propertyServiceTime['startTime'];
                $endTime = $propertyServiceTime['endTime'];
            }
            $qrCodeData['startTime'] = $startTime;
            $qrCodeData['endTime'] = $endTime;
            $qrCodeData['phoneNumber'] = $userBind['pigcms_id'];
        }
        //不管以前有没有添加过 先删除
        foreach ($device_no_arr as $dov) {
            $deviceSn = $lilin_village_no . $dov;
            $delCodeData = array('phoneNumber' => $qrCodeData['phoneNumber'], 'neighNo' => $lilin_village_no, 'deviceSn' => $deviceSn);
            $this->removeQrCode($delCodeData);
            usleep(10000);
        }
        //再添加
        foreach ($device_no_arr as $dov) {
            $deviceSn = $lilin_village_no . $dov;
            $qrCodeData['deviceSn'] = $deviceSn;
            $this->addQrCode($qrCodeData);
            usleep(10000);
        }

        //再获取二维码
        $startTime = $qrCodeData['startTime'] + 2000;
        $endTime = $qrCodeData['endTime'] - 2000;
        $getCodeData = array('phoneNumber' => $qrCodeData['phoneNumber'], 'neighNo' => $lilin_village_no, 'startTime' => $startTime, 'endTime' => $endTime);
        $retQrCode = $this->getQrCode($getCodeData);
        if($retQrCode['data'] && isset($retQrCode['data']['qrCode'])){
            if($visitor_id>0){
                $visitSave=array('qr_id'=>$qr_id,'qr_code'=>$retQrCode['data']['qrCode'],'extra_data'=>json_encode($retQrCode,JSON_UNESCAPED_UNICODE));
                $houseVillageVisitEwm->saveOneData(array('id'=>$visitor_id),$visitSave);
            }else{
                $extra_data=array();
                if($userBind['extra_data']){
                    $extra_data=json_decode($userBind['extra_data'],true);
                }
                $extra_data= $extra_data ? $extra_data :array();
                $startTime=$startTime/1000;
                $startTime=intval($startTime);
                $endTime=$endTime/1000;
                $endTime=intval($endTime);
                $extra_data['lilin_ewm']=array('qrCode'=>$retQrCode['data']['qrCode'],'startTime'=>$startTime,'endTime'=>$endTime);
                $whereArr = array('village_id' => $village_id, 'pigcms_id' => $pigcms_id);
                $extra_data_str=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
                $saveQrArr=array('extra_data'=>$extra_data_str,'door_code'=>$retQrCode['data']['qrCode']);
                $houseVillageUserBind->saveOne($whereArr,$saveQrArr);
                $retQrCode['qrCode']=$retQrCode['data']['qrCode'];
                $retQrCode['startTime']=date('Y-m-d H:i',$startTime);
                $retQrCode['endTime']=date('Y-m-d H:i',$endTime);
                $retQrCode['village_name']=$village_name;
                $retQrCode['device_list']=$device_list; 
            }

        }
        return $retQrCode;
    }
    public function createShareVisitEwm($village_id=0,$data=array()){
        $houseVillageVisitEwm=new HouseVillageVisitEwm();
        $houseVillageVisitTag=new HouseVillageVisitTag();
        $visitEwmInfo=array('village_id'=>$village_id);
        $visitEwmInfo['uid']= isset($data['uid']) && $data['uid'] ? $data['uid']:0;
        $visitEwmInfo['room_id']= isset($data['room_id']) && $data['room_id'] ? $data['room_id']:0;
        $visitEwmInfo['bind_pigcms_id']= isset($data['pigcms_id']) && $data['pigcms_id'] ? $data['pigcms_id']:0;
        $pass_type= isset($data['pass_type']) && $data['pass_type'] ? $data['pass_type']:0;
        $visitEwmInfo['pass_type']=$pass_type;
        $nowtime=time();
        $today=date('Y-m-d').' 00:00:00';
        $start_time=strtotime($today);
        $end_time=$start_time+24*3600-1;
        if($pass_type==1){
            $start_time=$start_time+24*3600;
            $end_time=$start_time+24*3600-1;
        }else if($pass_type==2){
            $start_time=$start_time+2*24*3600;
            $end_time=$start_time+24*3600-1;
        }
        $visitEwmInfo['start_time']=$start_time;
        $visitEwmInfo['end_time']=$end_time;
        $visitEwmInfo['third_source']='lilin_pf';
        if( isset($data['tag_id']) && $data['tag_id'] ){
            $visitEwmInfo['tag_id']=$data['tag_id'];
            $visitTagObj=$houseVillageVisitTag->getOneData(array('id'=>$data['tag_id']),'*');
            if($visitTagObj && !$visitTagObj->isEmpty()){
                $visitEwmInfo['tag_name']=$visitTagObj['tag_name'];
            }
        }
        $visitEwmInfo['add_time']=$nowtime;
        $visitor_id = $houseVillageVisitEwm->addOneData($visitEwmInfo);
        return $visitor_id;
    }
    public function updateShareVisitEwm($whereArr=array(),$saveArr=array()){
        $houseVillageVisitEwm=new HouseVillageVisitEwm();
        $ret=$houseVillageVisitEwm->saveOneData($whereArr,$saveArr);
        return  $ret;
    }
    public function getShareVisitEwm($whereArr=array(),$field='*'){
        $houseVillageVisitEwm=new HouseVillageVisitEwm();
        $retObj=$houseVillageVisitEwm->getOneData($whereArr,$field);
        if($retObj && !$retObj->isEmpty()){
            $ret=$retObj->toArray();
            return $ret;
        }
        return array();
    }
    public function delShareVisitEwmList($whereArr){
        $houseVillageVisitEwm=new HouseVillageVisitEwm();
        $ret=$houseVillageVisitEwm->saveOneData($whereArr,array('del_time'=>time()));
        return  $ret;
    }
    public function getShareVisitEwmList($whereArr=array(),$field='*',$page=1,$limit=10){
        $houseVillageVisitEwm=new HouseVillageVisitEwm();
        $returnArr = array('list' =>array(), 'page_size' => $limit, 'count' => 0);
        $count=$houseVillageVisitEwm->getCount($whereArr);
        if($count>0){
            $houseVillageService=new HouseVillageService();
            $houseVillageDb = new HouseVillage();
            $returnArr['count']=$count;
            $visitEwmRecordsObj= $houseVillageVisitEwm->getListData($whereArr,$field,'id desc',$page,$limit);
            if($visitEwmRecordsObj && !$visitEwmRecordsObj->isEmpty()){
                $visitEwmRecords=$visitEwmRecordsObj->toArray();
                $room_address_arr=array();
                $village_arr=array();
                foreach ($visitEwmRecords as $kk=>$vv){
                    $village_name='';
                    if(isset($village_arr[$vv['village_id']])){
                        $village_name=$village_arr[$vv['village_id']];
                    }else{
                        $wherehArr = array('village_id' => $vv['village_id']);
                        $houseVillageObj = $houseVillageDb->getInfo($wherehArr,'village_id,village_name');
                        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                            $hVillage = $houseVillageObj->toArray();
                            $village_arr[$vv['village_id']]=$hVillage['village_name'];
                            $village_name=$hVillage['village_name'];
                        }
                    }
                    if(isset($room_address_arr[$vv['room_id']])){
                        $visitEwmRecords[$kk]['room_address']=$room_address_arr[$vv['room_id']];
                    }else{
                        $room_address=$houseVillageService->getSingleFloorRoom(0,0,0,$vv['room_id'],$vv['village_id']);
                        $room_address_arr[$vv['room_id']]=$village_name.$room_address;
                        $visitEwmRecords[$kk]['room_address']=$village_name.$room_address;
                    }
                    $visitEwmRecords[$kk]['start_time_str']=date('Y-m-d H:i',$vv['start_time']);
                    $visitEwmRecords[$kk]['end_time_str']=date('Y-m-d H:i',$vv['end_time']);
                    $visitEwmRecords[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                    $visitEwmRecords[$kk]['open_num_str']='不限';
                }
            }
        }
        return $returnArr;
    }
    //设备用户批量同步

    public function lilinPfDeviceUserSyc($village_id = 0, $device_id = 0, $device_no = '')
    {
        $dataParam = array('village_id' => $village_id, 'device_id' => $device_id, 'device_no' => $device_no);
        $dataParam['handle_type'] = 'deviceMassUserSyc';
        $job_id = $this->traitLilinPfCommunityDevice($dataParam, 3, 120);
        return array('job_id' => $job_id);
    }

    public function lilinPfSycUserToDevice($village_id = 0, $pigcms_id = 0)
    {
        $dataParam = array('village_id' => $village_id, 'pigcms_id' => $pigcms_id);
        $dataParam['handle_type'] = 'sycUserToDevice';
        $job_id = $this->traitLilinPfCommunityDevice($dataParam, 3, 120);
        return array('job_id' => $job_id);
    }

    public function deviceMassUserSyc($data = array())
    {
        $houseFaceDevice = new HouseFaceDevice();
        $village_id = 0;
        if ($data && isset($data['village_id'])) {
            $village_id = intval($data['village_id']);
        }
        if ($village_id < 1) {
            return true;
        }
        $houseVillageDb = new HouseVillage();
        $whereArr = array('village_id' => $village_id);
        $houseVillageObj = $houseVillageDb->getInfo($whereArr,'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no');
        $lilin_village_no = '';
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $hVillage = $houseVillageObj->toArray();
            $lilin_village_no = $hVillage['lilin_village_no'];
            $this->village_id=$hVillage['village_id'];
            $this->village_config=$hVillage;
        }
        if (empty($lilin_village_no)) {
            fdump_api([__LINE__, 'village_id' => $village_id, 'msg' => '未获取到立林小区号'], 'liLinAccess/deviceMassUserSyc', 1);
            return true;
        }
        $whereArr = array(['village_id','=',$village_id]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('device_id', '=', $data['device_id']);
        $whereArr[] = array('device_no', '=', $data['device_no']);
        $whereArr[] = array('is_del', '<', 4);
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('status', '<>', 4);
            $whereArr[] = array('name', '<>', '');
            if (!empty($faceDeviceObj['floor_id'])) {
                $floor_id_arr = explode(',', $faceDeviceObj['floor_id']);
                $whereArr[] = array('floor_id', 'in', $floor_id_arr);
            } else if (isset($faceDeviceObj['single_id']) && $faceDeviceObj['single_id']) {
                $whereArr[] = array('single_id', '=', $faceDeviceObj['single_id']);
            }
            $houseVillageUserBind = new HouseVillageUserBind();
            $fieldStr = 'pigcms_id,village_id,uid,name,phone,floor_id,type,vacancy_id,id_card,ic_card,single_id,property_starttime,property_endtime';
            $allDatas = $houseVillageUserBind->getList($whereArr, $fieldStr);
            fdump_api([__LINE__, 'whereArr' => $whereArr], 'liLinAccess/deviceMassUserSyc', 1);
            if ($allDatas && !$allDatas->isEmpty()) {
                $allDatas = $allDatas->toArray();
                $todayStr = date('Y-m-d');
                $todayTime = strtotime($todayStr);
                foreach ($allDatas as $kk => $uvv) {
                    $synUser = array('neighNo' => $lilin_village_no, 'village_id' => $village_id, 'pigcms_id' => $uvv['pigcms_id']);
                    $synUser['phoneNumber'] = intval($uvv['pigcms_id']);
                    $synUser['deviceSn'] = $lilin_village_no . $faceDeviceObj['device_no'];
                    $synUser['device_no'] = $faceDeviceObj['device_no'];
                    $synUser['floorType'] = 0;
                    $synUser['startTime'] = time() - 86400;
                    $synUser['startTime'] = $synUser['startTime'] * 1000;
                   // $synUser['endTime'] = 4133779200000;  //2100-12-30 00:00:00
                    $synUser['endTime'] = 2145924000;  //2038-01-01 10:00:00
                    $synUser['photoUrl'] = '';
                    $synUser['ic_card'] = $uvv['ic_card'];
                    $synUser['old_ic_card'] = '';
                    if ($uvv['type'] == 6) {
                        $synUser['startTime'] = $todayTime * 1000;
                        $tomorrow_time = $todayTime + 24 * 3600 + 600;
                        $synUser['endTime'] = $tomorrow_time * 1000;
                        if ($uvv['property_starttime'] > $todayTime) {
                            $synUser['startTime'] = $uvv['property_starttime'] * 1000;
                            $synUser['endTime'] = $uvv['property_endtime'] * 1000;
                        }
                    } elseif ($uvv['type'] != 4) {
                        $propertyServiceTime = $this->getPropertyServiceTime($village_id, $uvv['vacancy_id'], $uvv['pigcms_id']);
                        $synUser['startTime'] = $propertyServiceTime['startTime'];
                        $synUser['endTime'] = $propertyServiceTime['endTime'];
                    }

                    $dbHouseFaceImg = new HouseFaceImg();
                    $fieldStr = 'id,uid,img_url,status,bind_pigcms_id';
                    $whereArr = array();
                    $whereArr[] = ['status', 'in', [0, 3]];
                    $whereArr[] = ['img_url', '<>', ''];
                    $whereArr[] = ['bind_pigcms_id', '=', $uvv['pigcms_id']];
                    $img_url = '';
                    $faceImg = $dbHouseFaceImg->getOne($whereArr, $fieldStr, 'id desc');
                    if((empty($faceImg) || $faceImg->isEmpty()) && $uvv['uid']>0){
                        $whereArr = array();
                        $whereArr[] = ['status', 'in', [0, 3]];
                        $whereArr[] = ['img_url', '<>', ''];
                        $whereArr[] = ['uid', '=', $uvv['uid']];
                        $faceImg = $dbHouseFaceImg->getOne($whereArr, $fieldStr, 'id desc');
                    }
                    $wordEncryption = new wordencryption();
                    if (!empty($faceImg) && !$faceImg->isEmpty()) {
                        $item = $faceImg->toArray();
                        if (3 == $item['status']) {
                            // 加密的图片解密
                            $img_url = $wordEncryption->text_decrypt($item['img_url']);
                            $img_url = replace_file_domain($img_url);
                        } else {
                            $img_url = replace_file_domain($item['img_url']);
                        }

                        $synUser['photoUrl'] = $img_url;
                    }
                    $synUser['handle_type'] = 'sycUserInfoToDevice';
                    $job_id = $this->traitLilinPfCommunityDevice($synUser, $kk, 5);
                }
            }
        }
        return true;
    }

    //单个人同步
    public function sycUserToDevice($village_id = 0, $pigcms_id = 0)
    {
        $houseFaceDevice = new HouseFaceDevice();
        if ($village_id < 1 || $pigcms_id < 1) {
            return true;
        }
        $houseVillageDb = new HouseVillage();
        $whereArr = array('village_id' => $village_id);
        $houseVillageObj = $houseVillageDb->getInfo($whereArr,'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no');
        $lilin_village_no = '';
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $hVillage = $houseVillageObj->toArray();
            $lilin_village_no = $hVillage['lilin_village_no'];
            $this->village_id=$hVillage['village_id'];
            $this->village_config=$hVillage;
        }
        if (empty($lilin_village_no)) {
            fdump_api([__LINE__, 'village_id' => $village_id, 'msg' => '未获取到立林小区号'], 'liLinAccess/deviceMassUserSyc', 1);
            return true;
        }
        $whereArr = array('village_id' => $village_id, 'pigcms_id' => $pigcms_id);
        $houseVillageUserBind = new HouseVillageUserBind();
        $fieldStr = 'pigcms_id,village_id,uid,name,phone,floor_id,type,vacancy_id,id_card,ic_card,single_id,property_starttime,property_endtime';
        $oneDataObj = $houseVillageUserBind->getOne($whereArr, $fieldStr, 'pigcms_id desc');
        if ($oneDataObj && !$oneDataObj->isEmpty()) {
            $userBind = $oneDataObj->toArray();
            $todayStr = date('Y-m-d');
            $todayTime = strtotime($todayStr);

            $whereArr = array(['village_id' ,'=', $village_id]);
            $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
            $whereArr[] = array('device_no', '<>', '');
            $whereArr[] = array('is_del', '<', 4);
            $faceDevices = $houseFaceDevice->getDeviceLists($whereArr, 'device_id,device_no,device_sn,floor_id,single_id', 'device_id desc', 0);
            $device_no_arr = array();
            $device_arr= array();
            if ($faceDevices) {
                foreach ($faceDevices as $fvv) {
                    $floorIdArr = array();
                    if (isset($device['floor_id']) && $device['floor_id'] && $device['floor_id'] != '-1' && $device['floor_id'] != '0') {
                        $floorIdArr = explode(',', $device['floor_id']);
                    }
                    if (!empty($floorIdArr) && $userBind['type'] != 4 && $userBind['floor_id'] > 0 && in_array($userBind['floor_id'], $floorIdArr)) {
                        $device_no_arr[] = $fvv['device_no'];
                         $device_arr[$fvv['device_id']]=$fvv;
                    } else if ($userBind['type'] != 4 && $userBind['single_id'] > 0 && $userBind['single_id'] == $fvv['single_id']) {
                        $device_no_arr[] = $fvv['device_no'];
                        $device_arr[$fvv['device_id']]=$fvv;
                    } else if ($userBind['type'] == 4) {
                        $device_no_arr[] = $fvv['device_no'];
                        $device_arr[$fvv['device_id']]=$fvv;
                    }
                    if ((empty($fvv['floor_id']) || $fvv['floor_id'] == '0') && $fvv['single_id'] == 0) {
                        //公共的
                        $device_no_arr[] = $fvv['device_no'];
                        $device_arr[$fvv['device_id']]=$fvv;
                    }
                }

            }
            if (empty($device_no_arr)) {
                fdump_api([__LINE__, 'village_id' => $village_id, 'msg' => '没有可同步的设备'], 'liLinAccess/deviceMassUserSyc', 1);
                return true;
            }
            $device_no_arr = array_unique($device_no_arr);
            $device_no = $device_no_arr['0'];
            $synUser = array('neighNo' => $lilin_village_no, 'village_id' => $village_id, 'pigcms_id' => $userBind['pigcms_id']);
            $synUser['phoneNumber'] = intval($userBind['pigcms_id']);
            $synUser['deviceSn'] = $lilin_village_no . $device_no;
            $synUser['device_no'] = $device_no;
            $synUser['device_arr'] = array_values($device_arr);
            $synUser['floorType'] = 0;
            $synUser['startTime'] = time() - 86400;
            $synUser['startTime'] = $synUser['startTime'] * 1000;
            //$synUser['endTime'] = 4133779200000;  //2100-12-30 00:00:00
            $synUser['endTime'] = 2145924000;  //2038-01-01 10:00:00
            $synUser['photoUrl'] = '';
            $synUser['ic_card'] = $userBind['ic_card'];
            $synUser['old_ic_card'] = '';
            if ($userBind['type'] == 6) {
                $synUser['startTime'] = $todayTime * 1000;
                $tomorrow_time = $todayTime + 24 * 3600 + 600;
                $synUser['endTime'] = $tomorrow_time * 1000;
                if ($userBind['property_starttime'] > $todayTime) {
                    $synUser['startTime'] = $userBind['property_starttime'] * 1000;
                    $synUser['endTime'] = $userBind['property_endtime'] * 1000;
                }
            } elseif ($userBind['type'] != 4) {
                $propertyServiceTime = $this->getPropertyServiceTime($village_id, $userBind['vacancy_id'], $userBind['pigcms_id']);
                $synUser['startTime'] = $propertyServiceTime['startTime'];
                $synUser['endTime'] = $propertyServiceTime['endTime'];
            }

            $dbHouseFaceImg = new HouseFaceImg();
            $fieldStr = 'id,uid,img_url,status,bind_pigcms_id';
            $whereArr = array();
            $whereArr[] = ['status', 'in', [0, 3]];
            $whereArr[] = ['img_url', '<>', ''];
            $whereArr[] = ['bind_pigcms_id', '=', $userBind['pigcms_id']];
            $img_url = '';
            $faceImg = $dbHouseFaceImg->getOne($whereArr, $fieldStr, 'id desc');
            if((empty($faceImg) || $faceImg->isEmpty()) && $userBind['uid']>0){
                $whereArr = array();
                $whereArr[] = ['status', 'in', [0, 3]];
                $whereArr[] = ['img_url', '<>', ''];
                $whereArr[] = ['uid', '=', $userBind['uid']];
                $faceImg = $dbHouseFaceImg->getOne($whereArr, $fieldStr, 'id desc');
            }
            $wordEncryption = new wordencryption();
            if (!empty($faceImg) && !$faceImg->isEmpty()) {
                $item = $faceImg->toArray();
                if (3 == $item['status']) {
                    // 加密的图片解密
                    $img_url = $wordEncryption->text_decrypt($item['img_url']);
                    $img_url = replace_file_domain($img_url);
                } else {
                    $img_url = replace_file_domain($item['img_url']);
                }

                $synUser['photoUrl'] = $img_url;
            }
            $synUser['handle_type'] = 'sycUserInfoToDevice';
            $job_id = $this->traitLilinPfCommunityDevice($synUser, 1, 5);

        }
        return true;
    }

    /*****
     * 请求接口 同步人脸
     */
    public function sycUserInfoToDevice($data = array())
    {

        $village_id = 0;
        if ($data && isset($data['village_id'])) {
            $village_id = intval($data['village_id']);
        }
        $neighNo = '';
        if ($data && isset($data['neighNo'])) {
            $neighNo = $data['neighNo'];
        }
        if (empty($neighNo) || $village_id < 1) {
            return true;
        }
        $houseVillageDb = new HouseVillage();
        $whereArr = array('village_id' => $village_id);
        $houseVillageObj = $houseVillageDb->getInfo($whereArr,'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no');
        $lilin_village_no = '';
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $hVillage = $houseVillageObj->toArray();
            $lilin_village_no = $hVillage['lilin_village_no'];
            $this->village_id=$hVillage['village_id'];
            $this->village_config=$hVillage;
        }
        $pigcms_id=$data['pigcms_id'];
        $houseFaceDevice = new HouseFaceDevice();
        $deviceSns = array();
        $deviceArr=array();
        if (isset($data['device_arr']) && $data['device_arr']) {
            foreach ($data['device_arr'] as $dvv) {
                $deviceSns[] = $neighNo . $dvv['device_no'];
                $deviceArr[$neighNo . $dvv['device_no']]=$dvv;
            }
        } else {
            $deviceSns = array($data['deviceSn']);
            //公共区域的 取出来
            $whereArr = array(['village_id' ,'=', $village_id]);
            $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
            $whereArr[] = array('device_no', '<>', '');
            $whereArr[] = array('is_del', '<', 4);
            $whereArr[] = array('floor_id', '=', '0');
            $whereArr[] = array('single_id', '=', '0');
            $faceDeviceObj = $houseFaceDevice->getWhereList($whereArr, 'device_id,device_no,device_sn', 'device_id desc', 0, 0);
            if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
                $faceDevices = $faceDeviceObj->toArray();
                foreach ($faceDevices as $dvv) {
                    $deviceSns[] = $neighNo . $dvv['device_no'];
                    $deviceArr[$neighNo . $dvv['device_no']]=$dvv;
                }
            }
        }
        $deviceSns = array_unique($deviceSns);
        //先删除掉原来已添加的全部数据 ，不全部删除无法添加，没有更新接口
        foreach ($deviceSns as $d_sn) {
            $pdata = array('phoneNumber' => $data['phoneNumber'], 'deviceSn' => $d_sn, 'neighNo' => $neighNo);
            $this->removeFaceRecognize($pdata);
            if (isset($data['old_ic_card']) && $data['old_ic_card']) {
                $pdata = array('cardNo' => $data['old_ic_card'], 'deviceSn' => $d_sn, 'neighNo' => $neighNo);
                $this->removeIcCard($pdata);
            }
            usleep(50000);
        }
        $houseFaceSyncLog=new HouseFaceSyncLog();
        $nowtime=time();
        // 再添加
        foreach ($deviceSns as $d_sn) {
            //删完了 在添加
            $saveLogArr=array('pigcms_id'=>$pigcms_id,'device_id'=>$deviceArr[$d_sn]['device_id'],'device_sn'=>$deviceArr[$d_sn]['device_sn'],'log_time'=>$nowtime,'status'=>1,'msg'=>'','last_time'=>$nowtime);
            if (isset($data['photoUrl']) && $data['photoUrl']) {
                //需要同步人脸
                $pdata = array('floorType' => 0, 'deviceSn' => $d_sn, 'neighNo' => $neighNo, 'phoneNumber' => $data['phoneNumber'], 'photoUrl' => $data['photoUrl'], 'startTime' => $data['startTime'], 'endTime' => $data['endTime']);
                $retFace=$this->addFaceRecognize($pdata);
                if($retFace['err_code']<1){
                    $saveLogArr['status']=1;
                }else {
                    $saveLogArr['status']=2;
                    $saveLogArr['msg']=$retFace['err_msg'];
                }
            }
            if (isset($data['ic_card']) && $data['ic_card']) {
                //需要同步IC卡号
                $pdata = array('floorType' => 0, 'cardNo' => $data['ic_card'], 'deviceSn' => $d_sn, 'neighNo' => $neighNo, 'startTime' => $data['startTime'], 'endTime' => $data['endTime']);
                $retCard= $this->addIcCard($pdata);
                $saveLogArr['device_info']=json_encode(array('addIcCard'=>$retCard),JSON_UNESCAPED_UNICODE);
            }
            $whereLogArr=array('pigcms_id'=>$pigcms_id,'device_id'=>$deviceArr[$d_sn]['device_id']);
            $faceSyncLog=$houseFaceSyncLog->getOne($whereLogArr,'*','log_id desc');
            if($faceSyncLog && !$faceSyncLog->isEmpty()){
                $houseFaceSyncLog->updateThis(array('log_id'=>$faceSyncLog['log_id']),$saveLogArr);
            }else{
                $houseFaceSyncLog->add($saveLogArr);
            }
            usleep(50000);
        }
        $houseVillageUserBind = new HouseVillageUserBind();
        $updateBindParam = [
            'face_img_status' => 1,
            'face_img_reason' => '',
        ];
        $whereUserBind = [];
        $whereUserBind[] = ['village_id', '=', $village_id];
        $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
        
        $fieldStr = 'pigcms_id,village_id,uid,type,vacancy_id,door_code,extra_data';
        $oneDataObj = $houseVillageUserBind->getOne($whereUserBind, $fieldStr, 'pigcms_id desc');
        if($oneDataObj && !$oneDataObj->isEmpty()){
            $updateBindParam['door_code']='';
            $extra_data=$oneDataObj['extra_data'];
            $extra_data= $extra_data ? json_decode($extra_data,true):'';
            if($extra_data && isset($extra_data['lilin_ewm'])){
                $extra_data['old_lilin_ewm']=$extra_data['lilin_ewm'];
                $extra_data['lilin_ewm']=array();
                $updateBindParam['extra_data']=json_encode($extra_data,JSON_UNESCAPED_UNICODE);
            }
        }
        $houseVillageUserBind->saveOne($whereUserBind, $updateBindParam);
        return true;
    }

}