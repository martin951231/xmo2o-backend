<?php
/**
 **立林对接数据处理
 * cloud.mrsqfw.com 对接模式
 *  对接文档 https://iot.leelen.com/showdoc/#/199/1711
 */

namespace app\community\model\service\Device;

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
use app\community\model\service\UserService;
use app\community\model\db\HouseVillageVisitor;
use face\wordencryption;
use file_handle\FileHandle;
use think\Exception;
use app\traits\LilinPfCommunityDeviceTraits;
use think\facade\Cache;

class LiLinDevAccessApiService
{
    use LilinPfCommunityDeviceTraits;

    public $baseDevApi = 'https://scd-test.leelen.net/rest/scd'; //测试环境
    public $baseApi = 'https://scd.leelen.com/rest/scd'; //正式环境
    public $clientId = '';   //接入端id
    public $clientSecret = '';  //接入端Secret
    public $village_id=0;
    public $village_config=array();
    public $lilin_village_no='';  //对接小区号
    public function __construct($village_id=0)
    {
        $this->village_id=$village_id;
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
                $this->village_id=$hVillage['village_id'];
            }
        }
        return $hVillage;
    }


    /**
     * 推送过来的新增楼栋 单元
     **/
    public function pushAddOrEditBuilding($pdata = array())
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $xtype = isset($pdata['nodeType']) ? intval($pdata['nodeType']) : 0;
        $opt_type = 'optSingle'; //3楼栋节点
        if ($xtype == 4) {
            //单元节点
            $opt_type = 'optFloor';
        } 
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['id'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['type'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        $houseVillageDb = new HouseVillage();
        $pdata['neighNo'] = $neigh_no;
        switch ($xtype) {
            case 2:
                //片区
                $lilin_pf_zone=isset($rVillage['lilin_pf_zone'])  && $rVillage['lilin_pf_zone'] ? json_decode($rVillage['lilin_pf_zone'],true):'';
                $lilin_pf_zone=$lilin_pf_zone ? $lilin_pf_zone:array();
                $lilin_pf_zone[]=$pdata['structId'];
                $lilin_pf_zone=array_unique($lilin_pf_zone);
                $lilin_pf_zone_str=json_encode($lilin_pf_zone,JSON_UNESCAPED_UNICODE);
                $houseVillageDb->saveOne(array('village_id'=>$rVillage['village_id']),array('lilin_pf_zone'=>$lilin_pf_zone_str));
                break;
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
        $single_name = strval($pdata['nodeName']);
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
        $whereArr[] = array('third_single_index_id', '=', $pdata['structId']);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
        if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
            $saveArr = array('single_name' => $single_name, 'update_time' => time());
            if ($coordinateArr) {
                $saveArr['long'] = $coordinateArr['0'];
                $saveArr['lat'] = $coordinateArr['1'];
            }
           
            $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageSingleDb->saveOne(array('id' => $tmpSingleObj['id']), $saveArr);

        } else {
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('single_name', '=', $single_name);
            $whereArr[] = array('status', '<', 4);
            $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
            if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
                $saveArr = array('single_name' => $single_name, 'update_time' => time());
                if ($coordinateArr) {
                    $saveArr['long'] = $coordinateArr['0'];
                    $saveArr['lat'] = $coordinateArr['1'];
                }
                $saveArr['third_single_index_id'] = $pdata['structId'];
                $saveArr['third_single_source'] = 'lilin_pf';
                $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $houseVillageSingleDb->saveOne(array('id' => $tmpSingleObj['id']), $saveArr);
            }
            /**
            $saveArr = array('single_name' => $single_name, 'village_id' => $rVillage['village_id']);
            $saveArr['status'] = 1;
            if ($coordinateArr) {
                $saveArr['long'] = $coordinateArr['0'];
                $saveArr['lat'] = $coordinateArr['1'];
            }
            
            $saveArr['third_single_index_id'] = $pdata['structId'];
            $saveArr['third_single_source'] = 'lilin_pf';
            $saveArr['update_time'] = time();
            $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageSingleDb->addOne($saveArr);
           */ 
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = $pdata['nodeType'];
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
        $floor_name = strval($pdata['nodeName']);
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
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['type'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有查找到此单元关联的楼栋';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        $houseVillageFloorDb = new HouseVillageFloor();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_floor_source', '=', 'lilin_pf');
        $whereArr[] = array('single_id', '=', $single_id);
        $whereArr[] = array('third_floor_index_id', '=', $pdata['structId']);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageFloorDb->getOne($whereArr, '*');
        if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
            $saveArr = array('floor_name' => $floor_name, 'update_time' => time());
            if ($coordinateArr) {
                $saveArr['long'] = $coordinateArr['0'];
                $saveArr['lat'] = $coordinateArr['1'];
            }
            if(isset($pdata['callNoFirst4']) && $pdata['callNoFirst4']){
                $saveArr['call_no_first4'] =$pdata['callNoFirst4'];
            }
            $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageFloorDb->saveOne(array('floor_id' => $tmpSingleObj['floor_id']), $saveArr);

        } else {
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('floor_name', '=', $floor_name);
            $whereArr[] = array('single_id', '=', $single_id);
            $whereArr[] = array('status', '<', 4);
            $tmpSingleObj = $houseVillageFloorDb->getOne($whereArr, '*');
            if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
                $saveArr = array('floor_name' => $floor_name, 'update_time' => time());
                if ($coordinateArr) {
                    $saveArr['long'] = $coordinateArr['0'];
                    $saveArr['lat'] = $coordinateArr['1'];
                }
                if(isset($pdata['callNoFirst4']) && $pdata['callNoFirst4']){
                    $saveArr['call_no_first4'] =$pdata['callNoFirst4'];
                }
                $saveArr['third_floor_index_id'] = $pdata['structId'];
                $saveArr['third_floor_source'] = 'lilin_pf';
                $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $houseVillageFloorDb->saveOne(array('floor_id' => $tmpSingleObj['floor_id']), $saveArr);
            }
            
            /***
            $saveArr = array('floor_name' => $floor_name, 'village_id' => $rVillage['village_id']);
            $saveArr['status'] = 1;
            if ($coordinateArr) {
                $saveArr['long'] = $coordinateArr['0'];
                $saveArr['lat'] = $coordinateArr['1'];
            }
            if(isset($pdata['callNoFirst4']) && $pdata['callNoFirst4']){
                $saveArr['call_no_first4'] =$pdata['callNoFirst4'];
            }
            $saveArr['single_id'] = $single_id;
            $saveArr['third_floor_index_id'] = $pdata['structId'];
            $saveArr['third_floor_source'] = 'lilin_pf';
            $saveArr['add_time'] = time();
            $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageFloorDb->addOne($saveArr);
             **/
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = $pdata['nodeType'];
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
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => 0, 'func_type' => 'building');
            $addData['opt_type'] = 'del';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        
        $houseVillageFloorDb = new HouseVillageFloor();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_floor_source', '=', 'lilin_pf');
        $whereArr[] = array('third_floor_index_id', '=', $pdata['structId']);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageFloorDb->getOne($whereArr, '*');
        $delType = '';
        if ($pdata['nodeType']==4 && $tmpSingleObj && !$tmpSingleObj->isEmpty()) {
            $saveArr = array('status' => 4, 'update_time' => time());
            $houseVillageFloorDb->saveOne(array('floor_id' => $tmpSingleObj['floor_id']), $saveArr);
            $delType = 'delFloor';
        }
        if (empty($delType) && $pdata['nodeType']==3) {
            $houseVillageSingleDb = new HouseVillageSingle();
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('third_single_source', '=', 'lilin_pf');
            $whereArr[] = array('third_single_index_id', '=', $pdata['structId']);
            $whereArr[] = array('status', '<', 4);
            $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
            if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
                $saveArr = array('status' => 4, 'update_time' => time());
                $houseVillageSingleDb->saveOne(array('id' => $tmpSingleObj['structId']), $saveArr);
                $delType = 'delSingle';
            }
        }
        if (empty($delType) && $pdata['nodeType']==6) {
            $houseVillageUserVacancy = new HouseVillageUserVacancy();
            $whereArr = array(['village_id', '=', $rVillage['village_id']]);
            $whereArr[] = array('third_room_source', '=', 'lilin_pf');
            $whereArr[] = array('third_room_id', '=', $pdata['structId']);
            $whereArr[] = array('is_del', '<', 1);
            $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
            if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
                //编辑
                $vacancyData = array('is_del' => 1, 'del_time' => time());
                $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancyObj['pigcms_id']), $vacancyData);
                $delType = 'delRoom';
            }
        }
        if (empty($delType)) {
            //其他删除
            $delType = 'del';
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => 0, 'func_type' => 'building');
        $addData['opt_type'] = $delType;
        $addData['xtype'] = $pdata['nodeType'];
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }

    public function pushAddOrEditHouse($pdata)
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $opt_type = 'optHouse';
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => $pdata['pid'], 'func_type' => 'building');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['type'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
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
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => $pdata['pid'], 'func_type' => 'houseOpt');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['type'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有查找到此房屋关联的单元';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
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
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('floor_id', '=', $floor_id);
            $whereArr[] = array('layer_number', '=', $pdata['floor']);
            $whereArr[] = array('status', '<', 4);
            $layerObj = $houseVillageLayer->getOne($whereArr, '*');
            if ($layerObj && !$layerObj->isEmpty()) {
                $layer_id = $layerObj['id'];
                $layerSave = array('third_layer_source' => 'lilin_pf');
                $houseVillageLayer->saveOne(array('id'=>$layer_id),$layerSave);
            }else{
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '没有查找到此房屋关联的楼层！';
                return $returnArr;
            }
            
            /***
            $layerAdd = array('layer_name' => $pdata['floor'] . '层', 'status' => 1, 'single_id' => $single_id, 'floor_id' => $floor_id, 'village_id' => $rVillage['village_id']);
            $layerAdd['layer_number'] = $pdata['floor'];
            $layerAdd['third_layer_source'] = 'lilin_pf';
            $layerAdd['add_time'] = time();
            $layer_id = $houseVillageLayer->addOne($layerAdd);
             **/
        }
        $vacancyData = array('usernum' => $pdata['callNo']);
        $vacancyData['houseId'] = $pdata['callNo'];
        $vacancyData['floor_id'] = $floor_id;
        $vacancyData['layer'] = $pdata['floor'] . '层';
        $vacancyData['room_number'] = $pdata['houseCode'];
        $vacancyData['room'] = $pdata['houseName'];
        $vacancyData['status'] = 1;
        $vacancyData['village_id'] = $rVillage['village_id'];
        $vacancyData['name'] = isset($pdata['houseOwner']) && $pdata['houseOwner'] ? $pdata['houseOwner'] : '';
        $vacancyData['type'] = 0;
        $memo = '';
        if (isset($pdata['houseShape']) && $pdata['houseShape']) {
            $memo = $pdata['houseShape'];
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
        if (empty($memo) && isset($pdata['describe']) && $pdata['describe']) {
            $memo = $pdata['describe'];
        }
        
        $house_type = 1;  //1住宅 2商铺 3办公
        if (isset($pdata['houseType']) && $pdata['houseType'] == 3) {
            $house_type = 2;
        }elseif (isset($pdata['houseType']) && $pdata['houseType'] == 2){
            $house_type = 3;
        }
        if (isset($pdata['nodeType']) && $pdata['nodeType'] == 10) {
            $house_type = 3;
        }
        $sell_status = 1;
        if (isset($pdata['houseState']) && $pdata['houseState'] == 2) {
            $sell_status = 3; //3出租中
        }
        $vacancyData['memo'] = $memo;
        $vacancyData['housesize'] = $housesize;
        $vacancyData['house_type'] = $house_type;
        $vacancyData['user_status'] = 2;
        $vacancyData['sell_status'] = $sell_status;
        $vacancyData['single_id'] = $single_id;
        $vacancyData['layer_id'] = $layer_id;
        $vacancyData['property_number'] = $single_id . $floor_id . $layer_id . $pdata['houseCode'];
        $vacancyData['room_alias_id'] = $pdata['houseCode'];
        $vacancyData['third_room_id'] = $pdata['structId'];
        $vacancyData['third_room_source'] = 'lilin_pf';

        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_room_source', '=', 'lilin_pf');
        $whereArr[]=  array('floor_id','=',$floor_id);
        $whereArr[] = array('third_room_id', '=', $pdata['structId']);
        $whereArr[] = array('is_del', '<', 1);
        $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
        $room_id = 0;
        if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
            //编辑
            $room_id = $userVacancyObj['pigcms_id'];
            $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancyObj['pigcms_id']), $vacancyData);
        } else {
            
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('room', '=', $pdata['houseName']);
            $whereArr[]=['single_id','=',$single_id];
            $whereArr[]=['layer_id','=',$layer_id];
            $whereArr[]=['floor_id','=',$floor_id];
            $whereArr[] = array('is_del', '<', 1);
            $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
            if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
                //编辑
                $room_id = $userVacancyObj['pigcms_id'];
                $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancyObj['pigcms_id']), $vacancyData);
            }
            /**
            $vacancyData['add_time'] = time();
            $room_id = $houseVillageUserVacancy->addOne($vacancyData);
             **/
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => $pdata['pid'], 'func_type' => 'houseOpt');
        $addData['opt_type'] = $opt_type;
        $addData['xtype'] = $pdata['nodeType'];
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;

    }


    public function pushAddOrEditDevice($pdata)
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $opt_type = 'optDevice';
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => 0, 'n_pid' => 0, 'func_type' => 'deviceOpt');
            $addData['opt_type'] = $opt_type;
            $addData['dev_id']=isset($pdata['deviceId']) ? $pdata['deviceId']:0;
            $addData['xtype'] = isset($pdata['deviceType']) ? $pdata['deviceType']:0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        $deviceType = array(4097, 4141, 4103, 4112, 4128, 4133, 4142, 4100,8195);  //对接门禁类型 4097门禁主机 4141独立门禁机   4128道闸人脸门禁机 4112门禁控制器 4128道闸人脸门禁机
        
        if (!isset($pdata['deviceType']) || !in_array($pdata['deviceType'], $deviceType)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '不是门禁设备，设备类型不对！';
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => 0, 'n_pid' => 0, 'func_type' => 'deviceOpt');
            $addData['dev_id']=isset($pdata['deviceId']) ? $pdata['deviceId']:0;
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = $pdata['deviceType'];
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '不是门禁设备，设备类型不对！';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        if(!isset($pdata['deviceId']) || empty($pdata['deviceId'])){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '门禁设备deviceId为空值！';
            return $returnArr;
        }
        $houseFaceDevice = new HouseFaceDevice();
        $saveArr = array('device_name' => $pdata['deviceName'], 'device_type' => $pdata['deviceType']);
        if (isset($pdata['name']) && $pdata['name']) {
            $saveArr['device_name'] = $pdata['name'];
        }
        $saveArr['device_sn'] = isset($pdata['did']) && $pdata['did'] ? $pdata['did'] : '';
        $saveArr['village_id'] = $rVillage['village_id'];
        $saveArr['device_status'] = 1;
        if(isset($pdata['status'])){
            $saveArr['device_status'] = $pdata['status'] == 1 ? 1 : 2;
            if ($pdata['deviceType'] == 4097 && $pdata['status'] == 3) {
                $saveArr['device_status'] = 1;
            }
        }
        
        $saveArr['thirdDeviceTypeStr'] = $pdata['deviceType'];
        $saveArr['device_direction'] = 2;
        if (isset($pdata['ip']) && $pdata['ip']) {
            $saveArr['device_ip'] = $pdata['ip'];
        }
        if (isset($pdata['port']) && $pdata['port']) {
            $saveArr['thirdPort'] = $pdata['port'];
        }
        if ($pdata['deviceType'] == 4142) {
            $saveArr['device_model'] = 1;
        }
        $saveArr['device_brand'] = 'lilin_pf';
        $saveArr['thirdAddress'] = isset($pdata['mac']) ? $pdata['mac']:'';
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
        $saveArr['third_dev_id'] = $pdata['deviceId'];
        
        $floor_id = 0;
        $single_id = 0;
        /*
        $structPositionParentId = $pdata['structPositionParentId'];
        $houseVillageFloorDb = new HouseVillageFloor();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_floor_source', '=', 'lilin_pf');
        $whereArr[] = array('third_floor_index_id', '=', $structPositionParentId);
        $whereArr[] = array('status', '<', 4);
        $tmpSingleObj = $houseVillageFloorDb->getOne($whereArr, '*');
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
         */
        $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('third_dev_id', '=', $pdata['deviceId']);
        $whereArr[] = array('is_del', '<', 4);
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        $device_id = 0;
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            $saveArr['last_time'] = time();
            $device_id = $faceDeviceObj['device_id'];
            /*
            if($floor_id){
                $saveArr['floor_id'] = $floor_id;
            }
            if($single_id){
                $saveArr['single_id'] = $single_id;
            }
            */
            $houseFaceDevice->saveData(array('device_id' => $faceDeviceObj['device_id']), $saveArr);
        } else {
            $saveArr['add_time'] = time();
            $saveArr['floor_id'] = $floor_id;
            $saveArr['single_id'] = $single_id;
            $device_id = $houseFaceDevice->addData($saveArr);
        }

        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => 0, 'n_pid' =>0, 'func_type' => 'deviceOpt');
        $addData['dev_id']=isset($pdata['deviceId']) ? $pdata['deviceId']:0;
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
            /**
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => 0, 'func_type' => 'deviceOpt');
            $addData['opt_type'] = 'updateStatus';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
             **/
            return $returnArr;
        }
        $houseFaceDevice = new HouseFaceDevice();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('third_dev_id', '=', $pdata['deviceId']);
        $whereArr[] = array('is_del', '<', 4);
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        $nowTime = time();
        $device_id = 0;
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            //编辑
            $updateData = array('notify_time' => $nowTime);
            $updateData['device_status'] = $pdata['status'] == 1 ? 1 : 2;
            if (isset($pdata['deviceType']) && ($pdata['deviceType'] == 4097) && $pdata['status'] == 3) {
                $updateData['device_status'] = 1;
            }
            if (isset($pdata['deviceType']) && $pdata['deviceType']) {
                $updateData['device_type'] = $pdata['deviceType'];
            }
            if (isset($pdata['deviceName']) && $pdata['deviceName']) {
                $updateData['device_name'] = $pdata['deviceName'];
            }
            $device_id = $faceDeviceObj['device_id'];
            if (isset($pdata['deviceIp']) && $pdata['deviceIp']) {
                $updateData['device_ip'] = $pdata['deviceIp'];
            }
            if (isset($pdata['ip']) && $pdata['ip']) {
                $updateData['device_ip'] = $pdata['ip'];
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
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => 0, 'func_type' => 'deviceOpt');
            $addData['opt_type'] = 'delDevice';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        $houseFaceDevice = new HouseFaceDevice();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
        $whereArr[] = array('third_dev_id', '=', $pdata['deviceId']);
        $whereArr[] = array('is_del', '<', 4);
        $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
        if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
            //编辑
            $delData = array('is_del' => 4, 'last_time' => time());
            $houseFaceDevice->saveData(array('device_id' => $faceDeviceObj['device_id']), $delData);
        }
        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['structId'], 'n_pid' => 0, 'func_type' => 'deviceOpt');
        $addData['opt_type'] = 'delDevice';
        $addData['xtype'] = 0;
        $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $addData['bak'] = date('Y-m-d H:i:s') . ' 处理成功！';
        $addData['is_handle'] = 1;  //0 未处理 1已处理
        $addData['add_time'] = time();
        $this->addOneRecord($addData);
        return $returnArr;
    }
    public function downloadImg($imgUrl='',$savePath='lilin_img/face')
    {
        if(empty($imgUrl)){
            return '';
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
        try {
            $imgSavePath = '/upload/' . $savePath . '/' . date('Ymd');
            $chl = curl_init();
            curl_setopt($chl, CURLOPT_URL, $imgUrl);
            curl_setopt($chl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($chl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($chl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($chl, CURLOPT_SSL_VERIFYHOST, FALSE);
            $results = curl_exec($chl);

            $imgName = trim(strrchr($imgUrl, '/'), '/');
            $imgPathReturn = $imgSavePath . '/' . $imgName;
            $rootPath = app()->getRootPath() . '..';
            $image_big_root = $rootPath . $imgPathReturn;
            $fp = fopen($image_big_root, 'a');
            fwrite($fp, $results);
            curl_close($chl);
            fclose($fp);
        } catch (\Exception $e) {
            fdump_api(['msg' => '图片下载出错', 'message' => $e->getMessage()], 'liLinDevAccess/downloadImg', true);
            return $imgUrl;
        }
        fdump_api(['imgSavePath' => $imgSavePath, 'imgPath' => $imgPathReturn, 'image_big_root' => $image_big_root], 'liLinDevAccess/downloadImg', true);
        if ($image_big_root && $fileHandle->check_open_oss()) {
            try {
                $info = $fileHandle->upload($image_big_root);
                if (isset($info['error']) && !$info['error']) {
                    @unlink($image_big_root);
                }
            } catch (\Exception $e) {
                fdump_api(['msg' => '图片处理出错', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root], 'liLinDevAccess/downloadImg', true);
            } catch (\Throwable $e) {
                fdump_api(['msg' => '图片处理出错', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root], 'liLinDevAccess/downloadImg', true);
            } catch (\RuntimeException $e) {
                fdump_api(['msg' => '图片处理出错', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root], 'liLinDevAccess/downloadImg', true);
            } catch (\InvalidArgumentException $e) {
                fdump_api(['msg' => '图片处理出错', 'message' => $e->getMessage(), 'image_big_root' => $image_big_root], 'liLinDevAccess/downloadImg', true);
            }
        }
        if(file_exists($image_big_root)){
            return $imgPathReturn;
        }else{
            return $imgUrl;
        }
    }

    public function pushAddOrEditHousehold($pdata)
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $opt_type = 'optRoomUser';
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'roomUser');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        $houseInfos = isset($pdata['houseInfos']) ? $pdata['houseInfos'] : array();
        $bindUser = array('village_id' => $rVillage['village_id'], 'usernum' => '');
        $bindUser['name'] = $pdata['personName'];
        $phone = isset($pdata['phoneNumber']) ? $pdata['phoneNumber'] : '';
        if(empty($phone)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '手机号不存在！';
            return $returnArr;
        }
        $bindUser['phone']=$phone;
        $bindUser['uid']=0;
        $service_user = new UserService();
        $userOneObj=$service_user->getUserOne(array('phone'=>$phone),'*');
        if($userOneObj && isset($userOneObj['uid']) && $userOneObj['uid']){
            $bindUser['uid']=$userOneObj['uid'];
        }
        $bindUser['type'] = 0;
        if ($pdata['personType'] == 1) {
            $bindUser['type'] = 0;
        } elseif ($pdata['personType'] == 2) {
            //租客
            $bindUser['type'] = 2;
        } else {
            $bindUser['type'] = 1;
        }
        $bindUser['id_card'] = isset($pdata['certificatesNumber']) ? $pdata['certificatesNumber'] : '';
        $bindUser['birth'] = isset($pdata['birthDate']) ? $pdata['birthDate'] : '';
        $bindUser['memo'] = isset($pdata['remark']) ? $pdata['remark'] : '';
        $bindUser['third_type'] = 'lilin_pf';

        $facePhotoUrl='';
        if (isset($pdata['facePhotoUrl']) && !empty($pdata['facePhotoUrl'])) {
            $facePhotoUrl = $pdata['facePhotoUrl'];
            
        }elseif (isset($pdata['certificatesUrl']) && !empty($pdata['certificatesUrl'])){
            //$facePhotoUrl = $pdata['certificatesUrl'];
        }
        $img_url = $facePhotoUrl;
        $facePhotoUrl= $this->downloadImg($facePhotoUrl);
        if ( isset($pdata['cardList']) && !empty($pdata['cardList'])) {
            foreach ($pdata['cardList'] as $cvv) {
                if ($cvv['cardType'] == 2 && $cvv['cardNo']) {
                    $bindUser['id_card'] = $cvv['cardNo'];
                }
                if (in_array($cvv['cardType'], [1, 5]) && $cvv['cardNo']) {
                    $bindUser['ic_card'] = $cvv['cardNo'];
                }
            }
        }
        $bindUser['uid_third_id'] = $pdata['personId'];
        $bindUser['extra_data']=json_encode($pdata,JSON_UNESCAPED_UNICODE);
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $houseVillageUserVacancy = new HouseVillageUserVacancy();
        $noFindArr = array();
        $houseFaceImg = new HouseFaceImg();
        $nowTime = time();
        foreach ($houseInfos as $room) {
            $whereArr = array(['village_id','=',$rVillage['village_id']]);
            $whereArr[] = array('third_room_source', '=', 'lilin_pf');
            $whereArr[] = array('third_room_id', '=', $room['houseStructId']);
            $whereArr[] = array('is_del', '<', 1);
            $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
            if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
                $userVacancy = $userVacancyObj->toArray();
                //找到房间
                $tmpBindUser = $bindUser;
                $tmpBindUser['bind_third_id'] = $room['houseStructId'];
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
                $updateArr = array('name' => $tmpBindUser['name'], 'phone' => $tmpBindUser['phone'], 'type' => $tmpBindUser['type'], 'user_status' => 1,'uid'=>$tmpBindUser['uid']);
                if (isset($room['personType']) && $room['personType'] == 1) {
                    $tmpBindUser['type'] = 0;
                } elseif (isset($room['personType']) && $room['personType'] == 2) {
                    //租客
                    $tmpBindUser['type'] = 2;
                }else if(isset($room['personType']) && $room['personType']){
                    $tmpBindUser['type'] = 1;
                }
                if (isset($room['effectTime']) && $room['effectTime']) {
                    $tmpBindUser['property_starttime'] = strtotime($room['effectTime']);
                    $updateArr['property_starttime'] = $tmpBindUser['property_starttime'];
                }
                if (isset($room['expireTime']) && $room['expireTime']) {
                    $tmpBindUser['property_endtime'] = strtotime($room['expireTime']);
                    $updateArr['property_endtime'] = $tmpBindUser['property_endtime'];
                }
                $whereArr = array(['village_id','=',$rVillage['village_id']]);
                $whereArr[] = array('vacancy_id', '=', $userVacancy['pigcms_id']);
                $whereArr[] = array('status', '<', 4);
                $whereArr[] = array('type', 'in', array(0,3));
                $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
                $fz_pigcms_id=0;
                if($userBindObj && !$userBindObj->isEmpty()){
                    $fz_pigcms_id=$userBindObj['pigcms_id'];
                }
                
                $whereArr = array(['village_id','=',$rVillage['village_id']]);
                $whereArr[] = array('third_type', '=', 'lilin_pf');
                $whereArr[] = array('uid_third_id', '=', $pdata['personId']);
                $whereArr[] = array('type', '<', 4);
                $whereArr[] = array('vacancy_id', '=', $userVacancy['pigcms_id']);
                $whereArr[] = array('status', '<', 4);
                $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
                if ($userBindObj && !$userBindObj->isEmpty()) {
                    $updateArr['uid'] = $userBindObj['uid'];
                    if($fz_pigcms_id>0 && $userBindObj['pigcms_id']!=$fz_pigcms_id){
                        $tmpBindUser['parent_id']=$fz_pigcms_id;
                        if($tmpBindUser['type']==0){
                            $tmpBindUser['type']=1;
                        }
                    }
                    $houseVillageUserBindDb->saveOne(array('pigcms_id' => $userBindObj['pigcms_id']), $tmpBindUser);
                    if ($facePhotoUrl) {
                        $saveImgArr = array('face_img_id' => 0, 'uid' => $userBindObj['uid'], 'img_url' =>$facePhotoUrl );
                        $saveImgArr['status'] = 0;
                        $saveImgArr['a2_face_door_img'] = $img_url;
                        $saveImgArr['bind_pigcms_id'] = $userBindObj['pigcms_id'];

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
                    $whereArr = array(['village_id','=',$rVillage['village_id']]);
                    $whereArr[] = array('phone', '=', $phone);
                    $whereArr[] = array('type', '<', 4);
                    $whereArr[] = array('vacancy_id', '=', $userVacancy['pigcms_id']);
                    $whereArr[] = array('status', '<', 4);
                    $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
                    if ($userBindObj && !$userBindObj->isEmpty()) {
                        $updateArr['uid'] = $userBindObj['uid'];
                        if($fz_pigcms_id>0 && $userBindObj['pigcms_id']!=$fz_pigcms_id){
                            $tmpBindUser['parent_id']=$fz_pigcms_id;
                            if($tmpBindUser['type']==0){
                                $tmpBindUser['type']=1;
                            }
                        }
                        $houseVillageUserBindDb->saveOne(array('pigcms_id' => $userBindObj['pigcms_id']), $tmpBindUser);
                        if ($facePhotoUrl) {
                            $saveImgArr = array('face_img_id' => 0, 'uid' => $userBindObj['uid'], 'img_url' =>$facePhotoUrl );
                            $saveImgArr['status'] = 0;
                            $saveImgArr['a2_face_door_img'] = $img_url;
                            $saveImgArr['bind_pigcms_id'] = $userBindObj['pigcms_id'];

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
                    }else{
                        $tmpBindUser['add_time'] = $nowTime;
                        $pigcms_id = $houseVillageUserBindDb->addOne($tmpBindUser);
                        if ($img_url) {
                            $saveImgArr = array('face_img_id' => 0, 'uid' => 0, 'img_url' =>$facePhotoUrl , 'add_time' => $nowTime);
                            $saveImgArr['status'] = 0;

                            $saveImgArr['a2_face_door_img'] = $img_url;
                            $saveImgArr['bind_pigcms_id'] = $pigcms_id;

                            $houseFaceImg->addOne($saveImgArr);
                        }
                    }
                }
                if($tmpBindUser['type']==0 || $fz_pigcms_id<1){
                    $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancy['pigcms_id']), $updateArr);
                }
                
            } else {
                $noFindArr[] = $room['houseStructId'];

            }
        }
        $err_code = 0;
        $err_msg = date('Y-m-d H:i:s') . '处理成功！';
        if (!empty($noFindArr)) {
            $err_code = 1;
            $err_msg = '房屋节点ID为' . implode('，', $noFindArr) . '的房屋信息未找到，未入住成功！';

        }
        $returnArr['err_code'] = $err_code;
        $returnArr['err_msg'] = $err_msg;
        $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'roomUser');
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
            /*
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'roomUser');
            $addData['opt_type'] = 'delRoomUser';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            */
            return $returnArr;
        }
        if(!isset($pdata['houseStructIds']) || empty($pdata['houseStructIds'])){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '缺少当前删除的关联房屋节点houseStructIds！';
            return $returnArr;
        }
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_type', '=', 'lilin_pf');
        $whereArr[] = array('uid_third_id', '=', $pdata['personId']);
        $whereArr[] = array('bind_third_id', 'in', $pdata['houseStructIds']);
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

    public function pushAddOrEditWorker($pdata)
    {
        $neigh_no = isset($pdata['neighNo']) && $pdata['neighNo'] ? trim($pdata['neighNo']) : '';
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $rVillage = $this->getRelateVillage($neigh_no);
        $opt_type = 'optVillageWorker';
        if (empty($rVillage)) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '没有获取到关联的小区！';
            /**
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'villageWorker');
            $addData['opt_type'] = $opt_type;
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
            **/
            return $returnArr;
        }
        $houseWorkerDb = new HouseWorker();
        $bindUser = array('village_id' => $rVillage['village_id'], 'usernum' => '');
        $bindUser['name'] = $pdata['personName'];
        $bindUser['phone'] = isset($pdata['phoneNumber']) ? $pdata['phoneNumber'] : '';
        $bindUser['type'] = 4;

        $bindUser['id_card'] = isset($pdata['certificatesNumber']) ? $pdata['certificatesNumber'] : '';
        $bindUser['birth'] = isset($pdata['birthDate']) ? $pdata['birthDate'] : '';
        $bindUser['memo'] = isset($pdata['remark']) ? $pdata['remark'] : '';
        $facePhotoUrl='';
        if (isset($pdata['facePhotoUrl']) && !empty($pdata['facePhotoUrl'])) {
            $facePhotoUrl = $pdata['facePhotoUrl'];

        }elseif (isset($pdata['certificatesUrl']) && !empty($pdata['certificatesUrl'])){
            //$facePhotoUrl = $pdata['certificatesUrl'];
        }
        $img_url = $facePhotoUrl;
        $facePhotoUrl= $this->downloadImg($facePhotoUrl);
        if ( isset($pdata['cardList']) && !empty($pdata['cardList'])) {
            foreach ($pdata['cardList'] as $cvv) {
                if ($cvv['cardType'] == 2 && $cvv['cardNo']) {
                    $bindUser['id_card'] = $cvv['cardNo'];
                }
                if (in_array($cvv['cardType'], [1, 5]) && $cvv['cardNo']) {
                    $bindUser['ic_card'] = $cvv['cardNo'];
                }
            }
        }
        $bindUser['usernum'] = 'lilin' . $pdata['personId'];
        $bindUser['third_type'] = 'lilin_pf';
        $bindUser['uid_third_id'] = $pdata['personId'];
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $houseFaceImg = new HouseFaceImg();

        $workerArr = array('village_id' => $rVillage['village_id'], 'phone' => $bindUser['phone'], 'name' => $bindUser['name']);
        $workerArr['status'] = 1;
        $workerArr['type'] = $pdata['personType'];
        $workerArr['id_card'] = $bindUser['id_card'];
        $workerArr['gender'] = $pdata['sex'] > 0 ? $pdata['sex'] : 0;
        $workerArr['note'] = $bindUser['memo'];
        $workerArr['property_id'] = $rVillage['property_id'];
        $workerArr['account'] = 'lilin' . $pdata['personId'];
        $workerArr['password'] = md5('123abc');
        $workerArr['third_source'] = 'lilin_pf';
        $workerArr['third_source_id'] = $pdata['personId'];
        $workerArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $whereArr = array(['village_id','=',$rVillage['village_id']]);
        $whereArr[] = array('third_source', '=', 'lilin_pf');
        $whereArr[] = array('third_source_id', '=', $pdata['personId']);
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
            $whereArr[] = array('uid_third_id', '=', $pdata['personId']);
            $whereArr[] = array('type', '=', 4);
            $whereArr[] = array('wid', '=', $wid);
            $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
            if ($userBindObj && !$userBindObj->isEmpty()) {
                $pigcms_id = $userBindObj['pigcms_id'];
                $houseVillageUserBindDb->saveOne(array('pigcms_id' => $userBindObj['pigcms_id']), $bindUser);
                if ($facePhotoUrl) {
                    $saveImgArr = array('face_img_id' => 0, 'uid' => $userBindObj['uid'], 'img_url' => $facePhotoUrl);
                    $saveImgArr['status'] = 0;
                    $saveImgArr['a2_face_door_img'] = $img_url;
                    $saveImgArr['bind_pigcms_id'] = $userBindObj['pigcms_id'];
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
                if ($facePhotoUrl) {
                    $saveImgArr = array('face_img_id' => 0, 'uid' => 0, 'img_url' => $facePhotoUrl, 'add_time' => $nowTime);
                    $saveImgArr['status'] = 0;
                    $saveImgArr['a2_face_door_img'] = $img_url;
                    $saveImgArr['bind_pigcms_id'] = $pigcms_id;
                    $houseFaceImg->addOne($saveImgArr);
                }
            }
            $houseWorkerDb->saveOneData(array('wid' => $wid), array('bind_pigcms_id' => $pigcms_id));
        }

        $returnArr['err_code'] = 0;
        $returnArr['err_msg'] = '处理成功！';
        $addData = array('village_id' => $rVillage['village_id'], 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'villageWorker');
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
            /***
            $addData = array('village_id' => 0, 'neigh_no' => $neigh_no, 'n_id' => $pdata['personId'], 'n_pid' => 0, 'func_type' => 'villageWorker');
            $addData['opt_type'] = 'delVillageWorker';
            $addData['xtype'] = 0;
            $addData['pdata'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $addData['bak'] = '没有获取到关联的小区';
            $addData['is_handle'] = 0;  //0 未处理 1已处理
            $addData['add_time'] = time();
            $this->addOneRecord($addData);
             **/
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
        $houseUserLog = new HouseUserLog();
        $saveArr = array('village_id' => $rVillage['village_id'], 'log_business_id' => $rVillage['village_id']);
        /* 1 IC卡2身份证3二维码4指纹5蓝牙6 CPU卡7人脸识别8动态密码9远程开门10 APP远程开门11静态密码开门12开门按钮 13IC密码 14身份证密码 15CPU密码 16IC人脸 17身份证人脸 18CPU人脸**/
        $cardTypeFrom = array('1' => '3', '2' => '11', '3' => '31', '4' => '45', '5' => '21', '6' => '22', '7' => '1', '8' => '42', '9' => '2', '10' => '2', '11' => '41', '12' => '23', '14' => '11', '15' => '22', '16' => '1', '17' => '1', '18' => '1','19'=>'2','20'=>'52','21'=>'52');
        $saveArr['log_from'] = 1;
        if (isset($pdata['recognitionType']) && isset($cardTypeFrom[$pdata['recognitionType']])) {
            $saveArr['log_from'] = $cardTypeFrom[$pdata['recognitionType']];
        }
        $saveArr['card_number'] = isset($pdata['cardNo']) ? $pdata['cardNo']:'';
        /**1IC卡2身份证3指纹特征4CPU卡5人脸图片6虚拟卡类型7指纹图片8动态密码卡类型9NB锁挟持指纹10普通密码11周期密码12一次性密码13NB锁普通指纹****/
        $saveArr['card_number_type'] = isset($pdata['cardType']) && $pdata['cardType'] ? $pdata['cardType'] : 1;
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $saveArr['person_name'] = isset($pdata['personName']) && $pdata['personName'] ? $pdata['personName'] : '';
        if (isset($pdata['personId']) && $pdata['personId']) {
            $saveArr['person_code'] = $pdata['personId'];
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

        } else if ($pdata['recognitionType'] == 1 && isset($pdata['cardNo']) && $pdata['cardNo']) {
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
            return $returnArr;
        }
        $saveArr['device_id'] = $device_id;
        $saveArr['device_sn'] = $pdata['did'];
        $nowtime = time();
        $saveArr['log_time'] = isset($pdata['captureTime']) && $pdata['captureTime'] ? ($pdata['captureTime'] / 1000) : $nowtime;

        $saveArr['log_status'] = 0;
        if (isset($pdata['isUnlock']) && in_array($pdata['isUnlock'], [1, 3, 5, 7])) {
            $saveArr['log_status'] = 1;
        }
        $saveArr['direction_type'] = 1;
        if (isset($pdata['isUnlock']) && in_array($pdata['isUnlock'], [3, 4, 7, 8])) {
            $saveArr['direction_type'] = 2;  //2出
        }
        $photoUrl='';
        if (isset($pdata['photoUrl']) && $pdata['photoUrl']) {
            $photoUrl= $this->downloadImg($pdata['photoUrl'],'lilin_img/face_access');
        }else if(isset($pdata['cutPhotoUrl']) && $pdata['cutPhotoUrl']){
            $photoUrl= $this->downloadImg($pdata['cutPhotoUrl'],'lilin_img/face_access');
        }
        $saveArr['cut_image'] = $photoUrl;
        $saveArr['log_name'] = isset($pdata['deviceName']) && $pdata['deviceName'] ? $pdata['deviceName'] : '';
        $saveArr['log_detail'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $saveArr['record_log'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $saveArr['add_time'] = $nowtime;
        if (isset($pdata['eventId']) && $pdata['eventId']) {
            $saveArr['eventId'] = $pdata['eventId'];
            $whereArr = array('village_id' => $rVillage['village_id'], 'device_id' => $device_id, 'eventId' => $pdata['eventId']);
            $userLogObj = $houseUserLog->getFind($whereArr, 'log_id');
            if ($userLogObj && !$userLogObj->isEmpty()) {
                $houseUserLog->updateThis(array('log_id' => $userLogObj['log_id']), $saveArr);
            } else{
                $houseUserLog->addData($saveArr);
            }
        }else{
            $houseUserLog->addData($saveArr);
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
    
    //处理公共参数
    private function getCommonParameter($version = '1.0')
    {

        $houseVillageDb = new HouseVillage();
        $whereArr = array('village_id' => $this->village_id);
        $lilin_village_no='';
        $lilin_pf_client_id='';
        $lilin_pf_client_secret='';
        $houseVillageObj = $houseVillageDb->getInfo($whereArr, 'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no');
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $hVillage = $houseVillageObj->toArray();
            $this->baseApi = $hVillage['lilin_pf_base_api'];
            $lilin_pf_client_id = $hVillage['lilin_pf_client_id'];
            $lilin_pf_client_secret = $hVillage['lilin_pf_client_secret'];
            $this->village_config['village_id'] = $this->village_id;
            $this->village_config['lilin_pf_base_api'] = $this->baseApi;
            $this->village_config['lilin_pf_client_id'] = $lilin_pf_client_id;
            $this->village_config['lilin_pf_client_secret'] = $lilin_pf_client_secret;
            $this->village_config['lilin_village_no'] = $hVillage['lilin_village_no'];
            $lilin_village_no = $hVillage['lilin_village_no'] ? $hVillage['lilin_village_no']*1:'';
        }

        $this->clientId = $lilin_pf_client_id ? trim($lilin_pf_client_id) : '';
        $this->clientSecret = $lilin_pf_client_secret ? trim($lilin_pf_client_secret) : '';
        $this->lilin_village_no= $lilin_village_no ? $lilin_village_no : '';
        if ($this->baseApi && strpos($this->baseApi, 'http') === false) {
            $this->baseApi = 'https://' . $this->baseApi;
        }
        $paramsCommon = array();
        $paramsCommon['version'] = $version;
        $paramsCommon['clientId'] = $this->clientId;
        $timestamp = time();
        $timestamp = $timestamp . rand(101, 931);
        $paramsCommon['timestamp'] = $timestamp;
        $paramsCommon['nonce'] = uniqid('lee', true) . rand(100000, 999999);
        $paramsCommon['signatureVersion'] = '2.0';
        $paramsCommon['neighNo']=$lilin_village_no;
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
        $retParam['data'] = null;
        if (!empty($datas)) {
            ksort($datas);
            $dataJosn = json_encode($datas, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $dataStr = $dataStr . '&data=' . $dataJosn;
            $retParam['data'] = $datas;
        }
        $signStr = hash_hmac('sha1', $dataStr, $this->clientSecret);
        $signStr = base64_encode($signStr);
        $retParam['signature'] = $signStr;
        return $retParam;
    }

    public function httpCurlPost($url = '', $data = array(), $methodName = '', $version = '1.0')
    {
        $params = $this->getIncludeSignData($data, $methodName);
        $postparams = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $requestUrl = $this->baseApi . $url;  //正式环境
        if(empty($this->clientId) || empty($this->clientSecret) || empty($this->lilin_village_no)){
            return array('result'=>400,'message'=>'立林对接参数未配置正确','data'=>'');
        }
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
        fdump_api(['requestUrl' => $requestUrl, 'postparams' => $postparams, 'version' => $version, 'result' => $result], 'LiLinDevAccess/httpCurlPost' . date('H'),true);
        $return = json_decode($result, true);
        //关闭curl
        curl_close($ch);
        if (empty($return)) {
            return '';
        }
        return $return;
    }
    
    public function getUserOpenCode($pigcms_id=0,$village_id=0,$is_visitor=0){
        fdump_api(['pigcms_id' =>$pigcms_id, 'village_id' => $village_id,'is_visitor'=>$is_visitor], 'LiLinDevAccess/getUserOpenCode',1);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'qrCode' => '');
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $whereArr = array(['village_id','=',$village_id]);
        $whereArr[] = array('pigcms_id', '=', $pigcms_id);
        $whereArr[] = array('status', '<', 4);
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
        $userBind='';
        if($userBindObj && !$userBindObj->isEmpty()){
            $userBind= $userBindObj->toArray();
        }
        if(empty($userBind)){
            $returnArr['err_code']=1;
            $returnArr['err_msg']='用户信息不存在！';
            return $returnArr;
        }
        if($userBind['third_type']=='lilin_pf' && $userBind['uid_third_id']){
            $keyCache='lilin_pf_open_ewm_code'.$village_id.'_'.$pigcms_id;
            $qrCodeArr = Cache::get($keyCache);
            $nowtime=time();
            if (!empty($qrCodeArr) && isset($qrCodeArr['endTime']) && $qrCodeArr['endTime']>($nowtime+1800)){
                $startTime=$nowtime-600;
                if($userBind['type']==6){
                    $startTime=$qrCodeArr['startTime'];
                }
                $returnArr['qrCode']=$qrCodeArr['qrCode'];
                $returnArr['err_code']=0;
                $returnArr['bar_code'] = $qrCodeArr['qrCode'];
                $codeUrl = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $qrCodeArr['qrCode'];
                $returnArr['codeUrl']=$codeUrl;
                $returnArr['effectTimeTip']="起效时间：".date('Y-m-d H:i:s',$startTime);
                $returnArr['expireTimeTip']='失效时间：'.date('Y-m-d H:i:s',$qrCodeArr['endTime']);
                $returnArr['openTimesTip']='';
                $returnArr['tip']='如果二维码过期了，可以点击图片可刷新';
                $returnArr['title']='请将二维码对准设备识别位置';
                return $returnArr;
            }
            
            $startTime=$nowtime-600;
            $endTimeStr=date('Y-m-d').' 23:59:59';
            $endTime=strtotime($endTimeStr);
            if($userBind['type']==6 && $userBind['property_starttime']>100){
                $startTime=$userBind['property_starttime'];
                $endTime=$userBind['property_endtime'];
            }
            
            $cardNo=strval($userBind['pigcms_id']);

            $paramArr=array('cardNo'=> $cardNo,'startTime'=>$startTime*1000,'endTime'=>$endTime*1000);
            $ret=$this->getThirdQrCode($paramArr);
            if($ret['data'] && isset($ret['data']['qrCode'])){
                $returnArr['qrCode']=$ret['data']['qrCode'];
                $returnArr['err_code']=0;
                $returnArr['bar_code'] = $ret['data']['qrCode'];
                $codeUrl = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $ret['data']['qrCode'];
                $returnArr['codeUrl']=$codeUrl;
                $returnArr['effectTimeTip']="起效时间：".date('Y-m-d H:i:s',$startTime);
                $returnArr['expireTimeTip']='失效时间：'.$endTimeStr;
                $returnArr['openTimesTip']='';
                $returnArr['tip']='如果二维码过期了，可以点击图片可刷新';
                $returnArr['title']='请将二维码对准设备识别位置';
                $qrCodeArr=array('pigcms_id'=>$pigcms_id,'qrCode'=>$ret['data']['qrCode'],'startTime'=>$startTime,'endTime'=>$endTime);
                Cache::set($keyCache,$qrCodeArr,7200);
                return $returnArr;
            }else{
                $returnArr['err_code']=1;
                $returnArr['err_msg']=$ret['err_msg'] ? $ret['err_msg']:'获取开门二维码失败！';
                return $returnArr;
            }
        }else{
            $returnArr['err_code']=1;
            $returnArr['err_msg']='您暂无开门二维码权限，请联系物业管理人员！';
            return $returnArr;
        }
    }

    /***
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.2.1.获取呼叫号码前四位
     **/
    public function getCallNoFirst4($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/archives/structure/getCallNoFirst4';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getCallNoFirst4');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.2.2.新增/编辑片区/楼栋/单元/住家
     **/
    public function addEditBuilding($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/archives/structure/addEdit';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'addEdit');
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
            $err_msg = '';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;

    }
    
    /***
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.2.3.删除片区/楼栋/单元/住家
     **/
    public function deleteBuilding($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $retParam = $this->getCommonParameter();
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if(empty($this->clientSecret) || empty($this->clientId) || empty($this->lilin_village_no)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '立林门禁对接参数未配置！';
            return $returnArr;
        }
        $uri = '/third/archives/structure/delete';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'delete');
        fdump_api([__LINE__, 'village_id' => $village_id, 'params'=>$datas,'resultinfo' => $resultinfo], 'LiLinDevAccess/deleteBuilding', 1);
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.2.5.人脸质量校验
     **/
    public function checkImgFace($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/archives/common/checkFace';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'checkFace');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.2.4.上传人脸
     **/
    public function imgfileUpload($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/archives/common/fileUpload';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'fileUpload');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.2.6.新增/编辑住户（会进行人脸质量校验）
     **/
    public function addEditHousehold($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/archives/household/addEdit';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'addEdit');
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
            $err_msg = '';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = '同步住户失败：'.$resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;

    }
    /***
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.2.8.新增/编辑访客（会进行人脸质量校验）
     **/
    public function addEditVisitor($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/archives/visitor/addEdit';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'addEdit');
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
            $err_msg = '';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;

    }
    
    /***
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.2.7.删除住户
     **/
    public function deleteHousehold($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/archives/household/delete';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'delete');
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
            $err_msg = '删除失败了';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = '删除住户失败：'.$resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;

    }
    
    public function delLiLinPfHousehold($village_id=0,$pigcms_id=0,$room_id=0){
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        if($village_id<1 || $pigcms_id<1){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg']='请求参数错误！';
            return $returnArr;
        }
        fdump_api(['village_id' =>$village_id,'pigcms_id'=>$pigcms_id,'room_id'=>$room_id], 'LiLinDevAccess/delLiLinPfHousehold' , 1);
        $hVillage=$this->getVillagInfo($village_id);
        if(!empty($hVillage['lilin_pf_client_id']) && !empty($hVillage['lilin_pf_client_secret']) && !empty($hVillage['lilin_village_no'])){
            $houseVillageUserBind = new HouseVillageUserBind();
            $whereBindArr=array(['pigcms_id','=',$pigcms_id],['village_id','=',$village_id]);
            $oneDataObj = $houseVillageUserBind->getOne($whereBindArr, '*', 'pigcms_id desc');
            $personId='';
            if ($oneDataObj && !$oneDataObj->isEmpty()) {
                $userBind = $oneDataObj->toArray();
                $personId=$userBind['third_type']=='lilin_pf' && $userBind['uid_third_id'] ? $userBind['uid_third_id']:'';
                $room_id=$userBind['vacancy_id'];
            }
            if(empty($personId)){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg']='不是立林平台住户！';
                return $returnArr;
            }
            $houseVillageUserVacancy=  new HouseVillageUserVacancy();
            $villageRoomObj=$houseVillageUserVacancy->getOne(array('pigcms_id'=>$room_id,'village_id'=>$village_id));
            $structId='';
            if($villageRoomObj && !$villageRoomObj->isEmpty()) {
                $villageRoom = $villageRoomObj->toArray();
                $structId=$villageRoom['third_room_source']=='lilin_pf' && $villageRoom['third_room_id'] ? $villageRoom['third_room_id']:'';
            }
            if(empty($structId)){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg']='不是立林平台房间！';
                return $returnArr;
            }
            $delParamArr=array('personId'=>$personId*1,'structId'=>$structId*1);
            $ret=$this->deleteHousehold($delParamArr,$village_id);
            if($ret && $ret['err_code']<1){
                $returnArr['err_msg']='删除立林平台住户成功';
            }else{
                $returnArr['err_code'] = 1;
                $returnArr['err_msg']=$ret['err_msg'];
            }
            fdump_api(['village_id' =>$village_id,'pigcms_id'=>$pigcms_id,'delParamArr'=>$delParamArr,'ret'=>$ret], 'LiLinDevAccess/delLiLinPfHousehold' , 1);
            return $returnArr;
        }else{
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '未配置对接参数！';
            return $returnArr;
        }
    }
    /***
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.2.查询树结构（全部）
     **/
    public function getTreeInfo($village_id=0,$datas=array())
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getTreeInfo';
        if(empty($datas)){
            $datas=array('pager'=>array('pageNum'=>1,'pageSize'=>5));
        }
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getTreeInfo');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.4.查询片区档案（分页）
     **/
    public function getAreaInfoPage($village_id=0,$datas=array())
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getAreaInfoPage';
        if(empty($datas)){
            $datas=array('pager'=>array('pageNum'=>1,'pageSize'=>5));
        }
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getAreaInfoPage');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.18.查询二维码信息
     **/
    public function getThirdQrCode($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getQrCode';
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.16.查询人员门禁详情
     **/
    public function getPersonAccessInfo($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getPersonAccessInfo';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getPersonAccessInfo');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.5.1.第三方远程开门
     **/
    public function remoteOpenDoor($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/other/device/remoteOpen';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'remoteOpen');
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
            $err_msg = '开门失败！';
            if (!empty($resultinfo) && isset($resultinfo['message']) && $resultinfo['message']) {
                $err_msg = $resultinfo['message'];
            }
            $returnArr['err_msg'] = $err_msg;
        }
        return $returnArr;
    }
    
    public function remoteOpenOpt($deviceId='',$village_id=0,$direction=1,$openType=1){
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if(empty($deviceId) || empty($village_id)){
            $returnArr['err_code']=1;
            $returnArr['err_msg']='参数错误！';
        }
        $paramArr=array('deviceId'=>$deviceId*1,'direction'=>$direction,'openType'=>$openType);
        $ret=$this->remoteOpenDoor($paramArr,$village_id);
        if($ret['err_code']==0){
            $returnArr['err_msg']='开门成功';
            return $returnArr;
        }else{
            $returnArr['err_code']=1;
            $returnArr['err_msg']=$ret['err_msg'] ? $ret['err_msg']:'开门失败！';
            return $returnArr;
        }
        return $returnArr;
    }
    /***
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.5.2.二维码在线校验 解析二维码数据
     **/
    public function checkQrCode($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/other/device/checkQrCode';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'checkQrCode');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.7.查询房屋档案（分页）
     **/
    public function getHouseInfoPage($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getHouseInfoPage';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getHouseInfoPage');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.5.查询楼栋档案（分页）
     **/
    public function getBuildingInfoPage($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getBuildingInfoPage';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getBuildingInfoPage');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.6.查询单元档案（分页）
     **/
    public function getUnitInfoPage($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getUnitInfoPage';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getUnitInfoPage');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.8.查询设备档案（分页）
     **/
    
    public function getDeviceInfoPage($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getDeviceInfoPage';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getDeviceInfoPage');
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
     * https://iot.leelen.com/showdoc/#/199/1711
     **4.3.15.查询人员档案（住户/访客/工作人员）（分页）
     **/

    public function getPersonInfoPage($datas = array(),$village_id=0)
    {
        $this->village_id=$village_id>0 ? $village_id:$this->village_id;
        $uri = '/third/query/archives/getPersonInfoPage';
        $resultinfo = $this->httpCurlPost($uri, $datas, 'getPersonInfoPage');
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
     **获取关联的小区
     **/
    public function getVillagInfo($village_id = 0)
    {
        $hVillage = array();
        $houseVillageDb = new HouseVillage();
        if ($village_id>0) {
            $whereArr = array('village_id' => $village_id);
            $houseVillageObj = $houseVillageDb->getInfo($whereArr);
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $hVillage = $houseVillageObj->toArray();
                $this->village_id=$hVillage['village_id'];
            }
        }
        return $hVillage;
    }

    /****
     * 批量同步人员信息到立林平台
     * 优先同在我们这边创建的用户信息
     * 人员和设备的关联不需要我们处理，我们也没接口 可处理
     **/
    public function massVillageUserSycToLilinPf($village_id = 0,$pigcms_id=0)
    {
       
        $this->village_id=$village_id;
        $pigcms_id=$pigcms_id ? $pigcms_id:0;
        $hVillage=$this->getVillagInfo($village_id);
        if(!empty($hVillage['lilin_pf_client_id']) && !empty($hVillage['lilin_pf_client_secret']) && !empty($hVillage['lilin_village_no'])){
            $dataParam = array('village_id' => $village_id,'pigcms_id'=>$pigcms_id);
            $dataParam['handle_type'] = 'lilinPfMassUserSyc';
            $this->updateLilinPfZone($village_id,$hVillage);
            $redisExpire=120;
            if($pigcms_id){
                $redisExpire=10;
            }
            $job_id = $this->traitLilinPfCommunityDevice($dataParam, 3, $redisExpire);
            fdump_api(['village_id' =>$village_id,'dataParam'=>$dataParam,'job_id'=>$job_id], 'LiLinDevAccess/lilinPfMassUserSyc' . date('H') , 1);
            return array('job_id' => $job_id);
        }else{
            return array('job_id' => '','err_msg'=>'未配置对接参数！');
        }
    }

    public function updateLilinPfZone($village_id=0,$hVillage=array()){
        $lilin_pf_zone=$hVillage['lilin_pf_zone'] ? json_decode($hVillage['lilin_pf_zone'],true):'';
        $this->village_id=$village_id;
        if(!empty($lilin_pf_zone)){
            return true;
        }
        $lilin_pf_zone=array();
        $returnInfo= $this->getAreaInfoPage($village_id);
        if($returnInfo && !empty($returnInfo['data']) && isset($returnInfo['data']['list']) && $returnInfo['data']['list']){
            foreach ($returnInfo['data']['list'] as $zvv){
                if($zvv['neighNo']==$hVillage['lilin_village_no']){
                    $lilin_pf_zone[]=$zvv['structId'];
                }
            }
            $houseVillageDb = new HouseVillage();
            if($lilin_pf_zone){
                $lilin_pf_zone=array_unique($lilin_pf_zone);
                $lilin_pf_zone_str=json_encode($lilin_pf_zone,JSON_UNESCAPED_UNICODE);
                $houseVillageDb->saveOne(array('village_id'=>$village_id),array('lilin_pf_zone'=>$lilin_pf_zone_str));
            }
        }
        return true;
    }


    public function lilinPfMassUserSyc($village_id=0,$data = array())
    {
        if ($village_id < 1) {
            return true;
        }
        $pigcms_id=isset($data['pigcms_id']) ? $data['pigcms_id']:0;
        $houseVillageUserBind = new HouseVillageUserBind();
        $fieldStr = 'pigcms_id,village_id,vacancy_id';
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        if($pigcms_id>0){
            $whereArr[] = array('pigcms_id', '=', $pigcms_id);
        }
        $whereArr[] = array('status', '<>', 4);
        $whereArr[] = array('name', '<>', '');
        $whereArr[] = array('name', '<>', '');
        $whereArr[] = array('type', '<', 4);

        $allDatasObj = $houseVillageUserBind->getAllBindUserList($whereArr, $fieldStr,'','uid_third_id asc');
        $allDatas=array();
        if ($allDatasObj && !$allDatasObj->isEmpty()) {
            $allDatas=$allDatasObj->toArray();
        }
        if(empty($allDatas)){
            return true;
        }
        fdump_api([__LINE__, 'data' => $data, 'whereArr' => $whereArr], 'LiLinDevAccess/sycUserInfoToLiLin', 1);
        foreach ($allDatas as $kk => $uvv) {
            $uvv['handle_type'] = 'sycUserInfoToLiLin';
            $houseVillageUserBind->saveOne(array('village_id' => $village_id, 'pigcms_id' => $uvv['pigcms_id']),array('face_img_status'=>5));
            $job_id = $this->traitLilinPfCommunityUserInfo($uvv, $kk, 20);
        }
        return true;
    }
    

    public function sycUserInfoToLiLin($village_id, $data = array())
    {
        if ($village_id < 1 || !isset($data['pigcms_id']) || $data['pigcms_id'] < 1) {
            return true;
        }
        $pigcms_id = $data['pigcms_id'];
        $houseVillageDb = new HouseVillage();
        $whereArr = array('village_id' => $village_id);
        $houseVillageObj = $houseVillageDb->getInfo($whereArr, 'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no,lilin_pf_zone');
        $lilin_village_no = '';
        $lilin_pf_zone_id = 0;
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $hVillage = $houseVillageObj->toArray();
            $lilin_village_no = $hVillage['lilin_village_no'];
            $this->village_id = $hVillage['village_id'];
            $this->village_config = $hVillage;
            $lilin_pf_zone = $hVillage['lilin_pf_zone'] ? json_decode($hVillage['lilin_pf_zone'], 1) : '';
            if ($lilin_pf_zone) {
                $lilin_pf_zone_id = $lilin_pf_zone['0'];
            }
        }
        $houseVillageUserBind = new HouseVillageUserBind();
        $whereBindArr = array('village_id' => $village_id, 'pigcms_id' => $pigcms_id);
        if (empty($lilin_village_no)) {
            $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => '未获取到立林小区号', 'add_face_time' => time()));
            fdump_api([__LINE__, 'village_id' => $village_id, 'data' => $data, 'msg' => '未获取到立林小区号'], 'LiLinDevAccess/sycUserInfoToLiLin', 1);
            return true;
        }
        $fieldStr = '*';
        $oneDataObj = $houseVillageUserBind->getOne($whereBindArr, $fieldStr, 'pigcms_id desc');
        if ($oneDataObj && !$oneDataObj->isEmpty()) {
            $userBind = $oneDataObj->toArray();
            fdump_api([__LINE__, 'village_id' => $village_id, 'userBind' => $userBind], 'LiLinDevAccess/sycUserInfoToLiLin', 1);
            $todayStr = date('Y-m-d');
            $todayTime = strtotime($todayStr);
            $callNoFirst4 = '';
            $single_number = '';
            $third_room_id = '';
            $synUser = array('personType' => 1);
            $houseInfoList = array();
            $accessInfo = array();

            $singleInfo = $this->checkSingleSycToLiLin($userBind['single_id'], $village_id, $lilin_pf_zone_id);
            fdump_api([__LINE__, 'village_id' => $village_id, 'data' => $data, 'singleInfo' => $singleInfo, 'funcStep' => 'checkSingleSycToLiLin'], 'LiLinDevAccess/sycUserInfoToLiLin', 1);

            if ($singleInfo['err_code'] < 1 && $singleInfo['data'] && $singleInfo['data']['single_number']) {
                $single_number = $singleInfo['data']['single_number'];
                $single_number=intval($single_number);
                $single_number = $single_number < 10 ? '0' . $single_number : $single_number;
            } else if ($singleInfo['err_code'] > 0) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => $singleInfo['err_msg'], 'add_face_time' => time()));
                return true;
            }
            $floorInfo = $this->checkFloorSycToLiLin($userBind['floor_id'], $village_id);
            fdump_api([__LINE__, 'village_id' => $village_id, 'data' => $data, 'floorInfo' => $floorInfo, 'funcStep' => 'checkFloorSycToLiLin'], 'LiLinDevAccess/sycUserInfoToLiLin', 1);
            if ($floorInfo['err_code'] < 1 && $floorInfo['data'] && $floorInfo['data']['floor_number']) {
                if ($floorInfo['data']['call_no_first4']) {
                    $callNoFirst4 = $floorInfo['data']['call_no_first4'];
                } else {
                    $floor_number = $singleInfo['data']['floor_number'];
                    $floor_number=intval($floor_number);
                    $floor_number = $floor_number < 10 ? '0' . $floor_number : $floor_number;
                    $callNoFirst4 = $single_number . $floor_number;
                }
            } else if ($floorInfo['err_code'] > 0) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => $floorInfo['err_msg'], 'add_face_time' => time()));
                return true;
            }
            if (empty($callNoFirst4)) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => '楼栋编号或者单元编号填写错误！', 'add_face_time' => time()));
                return true;
            }
            if (strlen($callNoFirst4)>4) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => '楼栋编号或者单元编号错误，不是2位的纯数字！', 'add_face_time' => time()));
                return true;
            }
            $roomInfo = $this->checkRoomSycToLiLin($userBind['vacancy_id'], $village_id, $callNoFirst4);
            fdump_api([__LINE__, 'village_id' => $village_id, 'data' => $data, 'roomInfo' => $roomInfo, 'funcStep' => 'checkRoomSycToLiLin'], 'LiLinDevAccess/sycUserInfoToLiLin', 1);
            if ($roomInfo['err_code'] < 1 && $roomInfo['data'] && $roomInfo['data']['third_room_id']) {
                $third_room_id = $roomInfo['data']['third_room_id']*1;
            } else if ($roomInfo['err_code'] > 0) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => $roomInfo['err_msg'], 'add_face_time' => time()));
                return true;
            }
            $houseInfo = array('houseStructId' => $third_room_id);

            $startTime = time() - 86400;
            //$synUser['endTime'] = 4133779200000;  //2100-12-30 00:00:00
            $endTime = 2145924000;  //2038-01-01 10:00:00
            if ($userBind['type'] == 6) {
                $startTime = $todayTime;
                $tomorrow_time = $todayTime + 24 * 3600 + 600;
                $endTime = $tomorrow_time;
                if ($userBind['property_starttime'] > $todayTime) {
                    $startTime = $userBind['property_starttime'];
                    $endTime = $userBind['property_endtime'];
                }
            } elseif ($userBind['type'] != 4) {
                $propertyServiceTime = $this->getPropertyServiceTime($village_id, $userBind['vacancy_id'], $userBind['pigcms_id']);
                $startTime = $propertyServiceTime['startTime'];
                $endTime = $propertyServiceTime['endTime'];
            }
            $houseInfo['effectTime'] = date('Y-m-d H:i:s', $startTime);
            $houseInfo['expireTime'] = date('Y-m-d H:i:s', $endTime);
            ksort($houseInfo);
            $houseInfoList[] = $houseInfo;
            if ($userBind['third_type'] != 'lilin_pf' || empty($userBind['uid_third_id'])) {
                //新增
            } else {
                $synUser['personId'] = $userBind['uid_third_id'] * 1;
            }

            $synUser['personName'] = trim($userBind['name']);
            $synUser['phoneNumber'] = $userBind['phone'];
            $synUser['sex'] = 1;   //1男2女
            $synUser['certificatesType'] = 1;  //1居民身份证
            $synUser['certificatesNumber'] = $userBind['id_card'] ? $userBind['id_card'] : '';
            //$synUser['certificatesUrl']='';
            $synUser['birthDate'] = '';
            $synUser['facePhotoUrl'] = '';
            if ($userBind['id_card']) {
                $idCardInfo = idCardAnalysis($userBind['id_card']);
                if ($idCardInfo && is_array($idCardInfo)) {
                    $synUser['birthDate'] = $idCardInfo['birthday'] ? str_replace('-', '', $idCardInfo['birthday']) : '';
                    $sex = intval($idCardInfo['sex']);
                    $synUser['sex'] = $sex == 1 ? 1 : 2;
                }
            }
            $accessInfo['cardInfoList']=array(array('cardNo' => strval($userBind['pigcms_id']), 'cardType' => 6));  //虚拟卡类型 可使用蓝牙和二维码
            if ($userBind['ic_card']) {
                $cardInfo = array('cardNo' => strval($userBind['ic_card']), 'cardType' => 1);  //1IC卡 
                $accessInfo['cardInfoList'][] = $cardInfo;
            }
            $dbHouseFaceImg = new HouseFaceImg();
            $fieldStr = 'id,uid,img_url,status,bind_pigcms_id';
            $whereArr = array();
            $whereArr[] = ['status', 'in', [0, 3]];
            $whereArr[] = ['img_url', '<>', ''];
            $whereArr[] = ['bind_pigcms_id', '=', $userBind['pigcms_id']];
            $img_url = '';
            $faceImg = $dbHouseFaceImg->getOne($whereArr, $fieldStr, 'id desc');
            if ((empty($faceImg) || $faceImg->isEmpty()) && $userBind['uid'] > 0) {
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

                $synUser['facePhotoUrl'] = $img_url;
            }
            ksort($synUser);
            $householdUser = array( 'houseInfoList' => $houseInfoList,'personInfo' => $synUser);
            if (!empty($accessInfo)) {
                $householdUser['accessInfo'] = $accessInfo;
            }
            $ret = $this->addEditHousehold($householdUser, $village_id);
            if ($ret['data'] && isset($ret['data']['personId'])) {
                $saveData = array('third_type' => 'lilin_pf', 'uid_third_id' => $ret['data']['personId'], 'face_img_status' => 1, 'face_img_reason' => '', 'add_face_time' => time());
                $userBind['third_type'] = 'lilin_pf';
                $userBind['uid_third_id'] = $ret['data']['personId'];
                $houseVillageUserBind->saveOne($whereBindArr, $saveData);
            } else {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => $ret['err_msg'], 'add_face_time' => time()));
            }

        }
        return true;
    }
    
    public function checkSingleSycToLiLin($single_id=0,$village_id=0,$lilin_pf_zone_id=0){
        $houseVillageSingle=  new HouseVillageSingle();
        $villageSingleObj=$houseVillageSingle->getOne(array('id'=>$single_id,'village_id'=>$village_id),'*');
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if($villageSingleObj && !$villageSingleObj->isEmpty()){
            $villageSingle=$villageSingleObj->toArray();
            if($villageSingle['third_single_source']=='lilin_pf' && $villageSingle['third_single_index_id']){
                $returnArr['data']=$villageSingle;
                return $returnArr;
            }
            if(empty($villageSingle['single_number']) || $villageSingle['single_number']<1 || $villageSingle['single_number']>99){
                $returnArr['err_code']=1;
                $returnArr['err_msg']='楼栋编号不合法，请填写1到99之间的数字！';
                return $returnArr;
            }
            $pid=1;
            $pidType=1;
            if($lilin_pf_zone_id){
                //片区
                $pid=$lilin_pf_zone_id*1;
                $pidType=2;
            }
            $singleAdd=array('type'=>3,'pid'=>$pid,'pidType'=>$pidType,'name'=>strval($villageSingle['single_name']));
            $ret=$this->addEditBuilding($singleAdd,$village_id);
            if($ret['data']&& isset($ret['data']['structId'])){
                $saveData=array('third_single_source'=>'lilin_pf','third_single_index_id'=>$ret['data']['structId']);
                $villageSingle['third_single_source']='lilin_pf';
                $villageSingle['third_single_index_id']=$ret['data']['structId'];
                $returnArr['data']=$villageSingle;
                $houseVillageSingle->saveOne(array('id'=>$single_id,'village_id'=>$village_id),$saveData);
                return $returnArr;
            }else{
                $returnArr['err_code']=1;
                $returnArr['err_msg']=$ret['err_msg'] ? '楼栋同步失败：'.$ret['err_msg']:'新增楼栋同步失败！';
                return $returnArr;
            }
        }
        $returnArr['err_code']=1;
        $returnArr['err_msg']='房间所属楼栋信息不存在！';
        return $returnArr;
    }

    public function checkFloorSycToLiLin($floor_id=0,$village_id=0){
        $houseVillageSingle=  new HouseVillageSingle();
        $houseVillageFloor=  new HouseVillageFloor();
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $villageFloorObj=$houseVillageFloor->getOne(array('floor_id'=>$floor_id,'village_id'=>$village_id));
        if($villageFloorObj && !$villageFloorObj->isEmpty()){
            $villageFloor=$villageFloorObj->toArray();
            if($villageFloor['third_floor_source']=='lilin_pf' && $villageFloor['third_floor_index_id']){
                $returnArr['data']=$villageFloor;
                return $returnArr;
            }
            if(empty($villageFloor['floor_number']) || $villageFloor['floor_number']<1 || $villageFloor['floor_number']>99){
                $returnArr['err_code']=1;
                $returnArr['err_msg']='单元编号不合法，请填写1到99之间的数字！';
                return $returnArr;
            }
            $villageSingleObj=$houseVillageSingle->getOne(array('id'=>$villageFloor['single_id'],'village_id'=>$village_id),'*');
            if($villageSingleObj && !$villageSingleObj->isEmpty()) {
                $villageSingle = $villageSingleObj->toArray();
                if ($villageSingle['third_single_source'] != 'lilin_pf' || empty($villageSingle['third_single_index_id'])) {
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = '单元所在楼栋没有同步到平台！';
                    return $returnArr;
                }
            }else{
                $returnArr['err_code']=1;
                $returnArr['err_msg']='单元所在楼栋数据不存在！';
                return $returnArr;
            }
            $pid=$villageSingle['third_single_index_id']*1;
            $pidType=3;
            $floorAdd=array('type'=>4,'pid'=>$pid,'pidType'=>$pidType,'name'=>strval($villageFloor['floor_name']));
            $ret=$this->addEditBuilding($floorAdd,$village_id);
            if($ret['data'] && isset($ret['data']['structId']) ){
                $saveData=array('third_floor_source'=>'lilin_pf','third_floor_index_id'=>$ret['data']['structId']);
                $villageFloor['third_floor_source']='lilin_pf';
                $villageFloor['third_floor_index_id']=$ret['data']['structId'];
                $returnArr['data']=$villageFloor;
                $houseVillageFloor->saveOne(array('floor_id'=>$floor_id,'village_id'=>$village_id),$saveData);
                return $returnArr;
            }else{
                $returnArr['err_code']=1;
                $returnArr['err_msg']=$ret['err_msg'] ? '单元同步失败：'.$ret['err_msg']:'新增单元同步失败！';
                return $returnArr;
            }
        }
        $returnArr['err_code']=1;
        $returnArr['err_msg']='房间所属单元信息不存在！';
        return $returnArr;
    }
    
    public function checkRoomSycToLiLin($room_id=0,$village_id=0,$callNoFirst4=''){
        $houseVillageUserVacancy=  new HouseVillageUserVacancy();
        $houseVillageFloor=  new HouseVillageFloor();
        
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        $villageRoomObj=$houseVillageUserVacancy->getOne(array('pigcms_id'=>$room_id,'village_id'=>$village_id));
        if($villageRoomObj && !$villageRoomObj->isEmpty()){
            $villageRoom=$villageRoomObj->toArray();
            if($villageRoom['third_room_source']=='lilin_pf' && $villageRoom['third_room_id']){
                $returnArr['data']=$villageRoom;
                return $returnArr;
            }
            if(empty($villageRoom['room_number']) || $villageRoom['room_number']<100 || $villageRoom['room_number']>9999){
                $returnArr['err_code']=1;
                $returnArr['err_msg']='房间编号不合法，请填写100到9999之间的数字！';
                return $returnArr;
            }
            $villageFloorObj=$houseVillageFloor->getOne(array('floor_id'=>$villageRoom['floor_id'],'village_id'=>$village_id));
            if($villageFloorObj && !$villageFloorObj->isEmpty()) {
                $villageFloor = $villageFloorObj->toArray();
                if ($villageFloor['third_floor_source'] != 'lilin_pf' || empty($villageFloor['third_floor_index_id'])) {
                    $returnArr['err_code'] = 1;
                    $returnArr['err_msg'] = '房屋所在单元没有同步到平台！';
                    return $returnArr;
                }
                if($villageFloor['call_no_first4']){
                    $callNoFirst4= $villageFloor['call_no_first4'];
                }
            }else{
                $returnArr['err_code']=1;
                $returnArr['err_msg']='房屋所在单元数据不存在！';
                return $returnArr;
            }
            if(empty($callNoFirst4)){
                $returnArr['err_code']=1;
                $returnArr['err_msg']='楼栋编号或者单元编号填写有误！';
                return $returnArr;
            }
            $pid=$villageFloor['third_floor_index_id']*1;
            $pidType=4;
            $roomAdd=array('type'=>6,'pid'=>$pid,'pidType'=>$pidType,'name'=>strval($villageRoom['room']),'houseCode'=>strval($villageRoom['room_number']));
            $roomAdd['callNoFirst4']=$callNoFirst4;
            $roomAdd['houseArea']=$villageRoom['housesize'] ? strval($villageRoom['housesize']):'0';
            $roomAdd['houseState']=1;
            $ret=$this->addEditBuilding($roomAdd,$village_id);
            if($ret['data'] && isset($ret['data']['structId'])){
                $saveData=array('third_room_source'=>'lilin_pf','third_room_id'=>$ret['data']['structId']);
                $villageRoom['third_room_source']='lilin_pf';
                $villageRoom['third_room_id']=$ret['data']['structId'];
                $returnArr['data']=$villageRoom;
                $houseVillageUserVacancy->saveOne(array('pigcms_id'=>$room_id,'village_id'=>$village_id),$saveData);
                return $returnArr;
            }else{
                $returnArr['err_code']=1;
                $returnArr['err_msg']=$ret['err_msg'] ? '房屋同步失败：'.$ret['err_msg']:'新增房屋同步失败！';
                return $returnArr;
            }
        }
        $returnArr['err_code']=1;
        $returnArr['err_msg']='房间信息不存在！';
        return $returnArr;
    }

    public function visitorAddEditToLiLin($village_id=0, $visitor_id=0)
    {
        $returnArr = array('err_code' => 1, 'err_msg' => '访客添加失败', 'data' => '');
        if ($village_id < 1  || $visitor_id < 1) {
            $returnArr['err_code']=1;
            $returnArr['err_msg']='访客参数错误！';
            return $returnArr;
        }
        $this->village_id=$village_id;
        $houseVillageVisitorDb=new HouseVillageVisitor();
        $visitorObj=$houseVillageVisitorDb->get_one(array('id'=>$visitor_id,'village_id'=>$village_id),'*');
        $visitorInfo='';
        if($visitorObj && !$visitorObj->isEmpty()){
            $visitorInfo= $visitorObj->toArray();
        }
        if(empty($visitorInfo) || !isset($visitorInfo['visitor_bind_id'])){
            $returnArr['err_code']=1;
            $returnArr['err_msg']='访客信息不存在！';
            return $returnArr;
        }
        $pigcms_id = $visitorInfo['visitor_bind_id'];
        $houseVillageDb = new HouseVillage();
        $whereArr = array('village_id' => $village_id);
        $houseVillageObj = $houseVillageDb->getInfo($whereArr, 'village_id,lilin_pf_base_api,lilin_pf_client_id,lilin_pf_client_secret,lilin_village_no,lilin_pf_zone');
        $lilin_village_no = '';
        $lilin_pf_zone_id = 0;
        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
            $hVillage = $houseVillageObj->toArray();
            $lilin_village_no = $hVillage['lilin_village_no'];
            $this->village_id = $hVillage['village_id'];
            $this->village_config = $hVillage;
            $lilin_pf_zone = $hVillage['lilin_pf_zone'] ? json_decode($hVillage['lilin_pf_zone'], 1) : '';
            if ($lilin_pf_zone) {
                $lilin_pf_zone_id = $lilin_pf_zone['0'];
            }
        }
        $houseVillageUserBind = new HouseVillageUserBind();
        $whereBindArr = array('village_id' => $village_id, 'pigcms_id' => $pigcms_id,'type'=>6);
        if (empty($lilin_village_no)) {
            $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => '未获取到立林小区号', 'add_face_time' => time()));
            fdump_api([__LINE__, 'village_id' => $village_id, 'visitor_id' => $visitor_id, 'msg' => '未获取到立林小区号'], 'LiLinDevAccess/visitorAddEditToLiLin', 1);
            return true;
        }
        $fieldStr = '*';
        $oneDataObj = $houseVillageUserBind->getOne($whereBindArr, $fieldStr, 'pigcms_id desc');
        if ($oneDataObj && !$oneDataObj->isEmpty()) {
            $userBind = $oneDataObj->toArray();
            if($userBind['single_id']<1 || $userBind['floor_id']<1 || $userBind['vacancy_id']<1){
                $returnArr['err_code']=1;
                $returnArr['err_msg']='访客拜访的业主数据错误！';
                return $returnArr;
            }
            $todayStr = date('Y-m-d');
            $todayTime = strtotime($todayStr);
            $callNoFirst4 = '';
            $single_number = '';
            $third_room_id = '';
            $synUser = array('personType' => 5);
            $accessInfo = array();

            $singleInfo = $this->checkSingleSycToLiLin($userBind['single_id'], $village_id, $lilin_pf_zone_id);
            fdump_api([__LINE__, 'village_id' => $village_id, 'visitor_id' => $visitor_id, 'singleInfo' => $singleInfo], 'LiLinDevAccess/visitorAddEditToLiLin', 1);

            if ($singleInfo['err_code'] < 1 && $singleInfo['data'] && $singleInfo['data']['single_number']) {
                $single_number = $singleInfo['data']['single_number'];
                $single_number = $single_number < 10 ? '0' . $single_number : $single_number;
            } else if ($singleInfo['err_code'] > 0) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => $singleInfo['err_msg'], 'add_face_time' => time()));
                return true;
            }
            $floorInfo = $this->checkFloorSycToLiLin($userBind['floor_id'], $village_id);
            fdump_api([__LINE__, 'village_id' => $village_id,'visitor_id' => $visitor_id, 'floorInfo' => $floorInfo], 'LiLinDevAccess/visitorAddEditToLiLin', 1);
            if ($floorInfo['err_code'] < 1 && $floorInfo['data'] && $floorInfo['data']['floor_number']) {
                if ($floorInfo['data']['call_no_first4']) {
                    $callNoFirst4 = $floorInfo['data']['call_no_first4'];
                } else {
                    $floor_number = $singleInfo['data']['floor_number'];
                    $floor_number = $floor_number < 10 ? '0' . $floor_number : $floor_number;
                    $callNoFirst4 = $single_number . $floor_number;
                }
            } else if ($floorInfo['err_code'] > 0) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => $floorInfo['err_msg'], 'add_face_time' => time()));
                return true;
            }
            if (empty($callNoFirst4)) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => '楼栋编号或者单元编号填写错误！', 'add_face_time' => time()));
                return true;
            }
            $roomInfo = $this->checkRoomSycToLiLin($userBind['vacancy_id'], $village_id, $callNoFirst4);
            fdump_api([__LINE__, 'village_id' => $village_id, 'visitor_id' => $visitor_id, 'roomInfo' => $roomInfo], 'LiLinDevAccess/visitorAddEditToLiLin', 1);
            if ($roomInfo['err_code'] < 1 && $roomInfo['data'] && $roomInfo['data']['third_room_id']) {
                $third_room_id = $roomInfo['data']['third_room_id']*1;
            } else if ($roomInfo['err_code'] > 0) {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => $roomInfo['err_msg'], 'add_face_time' => time()));
                return true;
            }
            $houseInfo = array('houseStructId' => $third_room_id);

            $startTime = $todayTime;
            $tomorrow_time = $todayTime + 24 * 3600 + 600;
            $endTime = $tomorrow_time;
            if ($userBind['property_starttime'] > $todayTime) {
                $startTime = $userBind['property_starttime'];
                $endTime = $userBind['property_endtime'];
            }

            $houseInfo['effectTime'] = date('Y-m-d H:i:s', $startTime);
            $houseInfo['expireTime'] = date('Y-m-d H:i:s', $endTime);

            if ($userBind['third_type'] != 'lilin_pf' || empty($userBind['uid_third_id'])) {
                //新增
            } else {
                $synUser['personId'] = $userBind['uid_third_id'] * 1;
            }

            $synUser['personName'] = trim($userBind['name']);
            $synUser['phoneNumber'] = $userBind['phone'];
            $synUser['sex'] = 1;   //1男2女
            $synUser['certificatesType'] = 1;  //1居民身份证
            $synUser['certificatesNumber'] = $userBind['id_card'] ? $userBind['id_card'] : '';
            $synUser['birthDate'] = '';
            $synUser['facePhotoUrl'] = '';
            if ($userBind['id_card']) {
                $idCardInfo = idCardAnalysis($userBind['id_card']);
                if ($idCardInfo && is_array($idCardInfo)) {
                    $synUser['birthDate'] = $idCardInfo['birthday'] ? str_replace('-', '', $idCardInfo['birthday']) : '';
                    $sex = intval($idCardInfo['sex']);
                    $synUser['sex'] = $sex == 1 ? 1 : 2;
                }
            }
            $accessInfo['cardInfoList']=array(array('cardNo' => strval($userBind['pigcms_id']), 'cardType' => 6));  //虚拟卡类型 可使用蓝牙和二维码
            if ($userBind['ic_card']) {
                $cardInfo = array('cardNo' => strval($userBind['ic_card']), 'cardType' => 1);  //1IC卡 
                $accessInfo['cardInfoList'][] =$cardInfo;
            }
            $dbHouseFaceImg = new HouseFaceImg();
            $fieldStr = 'id,uid,img_url,status,bind_pigcms_id';
            $whereArr = array();
            $whereArr[] = ['status', 'in', [0, 3]];
            $whereArr[] = ['img_url', '<>', ''];
            $whereArr[] = ['bind_pigcms_id', '=', $userBind['pigcms_id']];
            $img_url = '';
            $faceImg = $dbHouseFaceImg->getOne($whereArr, $fieldStr, 'id desc');
            if ((empty($faceImg) || $faceImg->isEmpty()) && $userBind['uid'] > 0) {
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

                $synUser['facePhotoUrl'] = $img_url;
            }
            ksort($synUser);
            ksort($houseInfo);
            $householdUser = array('personInfo' => $synUser, 'visitorAccessPerm' => $houseInfo);
            if (!empty($accessInfo)) {
                $householdUser['accessInfo'] = $accessInfo;
            }
            $ret = $this->addEditVisitor($householdUser, $village_id);
            if ($ret['data'] && isset($ret['data']['personId'])) {
                $saveData = array('third_type' => 'lilin_pf', 'uid_third_id' => $ret['data']['personId'], 'face_img_status' => 1, 'face_img_reason' => '', 'add_face_time' => time());
                $userBind['third_type'] = 'lilin_pf';
                $userBind['uid_third_id'] = $ret['data']['personId'];
                $houseVillageUserBind->saveOne($whereBindArr, $saveData);
                $returnArr['err_code']=0;
                $returnArr['err_msg']='访客信息添加成功！';
                $returnArr['data']=$ret['data'];
            } else {
                $houseVillageUserBind->saveOne($whereBindArr, array('face_img_status' => 2, 'face_img_reason' => $ret['err_msg'], 'add_face_time' => time()));
            }

        }
        return $returnArr;
    }

    public function laterQueueGetLiLinPfVillageRoomHouses($village_id=0){
        $arr=['village_id' => $village_id, 'handle_type' => 'getLiLinPfVillageRooms','pageNum'=>1];
        $job_id = $this->traitLilinPfCommunityUserInfo($arr, 5, 120);
        fdump_api([__LINE__, 'village_id' => $village_id, 'job_id' =>$job_id], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
        return array('retInfo' => $job_id);
    }

    public function getLiLinPfVillageRooms($village_id = 0, $data = array())
    {
        $this->village_id = $village_id;
        $pageNum = isset($data['pageNum']) && $data['pageNum'] ? intval($data['pageNum']) : 1;
        $pageSize = 100;
        fdump_api([__LINE__, 'village_id' => $village_id, 'data' => $data], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
        $param = array('pager' => array('pageNum' => $pageNum, 'pageSize' => $pageSize));
        $ret = $this->getHouseInfoPage($param, $village_id);
        fdump_api([__LINE__, 'village_id' => $village_id, 'ret' => $ret], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if ($ret['data'] && isset($ret['data']['list']) && $ret['data']['list']) {
            $totalPage = $ret['data']['totalPage'];
            if ($pageNum == 1 && $totalPage > 1) {
                for ($ii = 2; $ii <= $totalPage; $ii++) {
                    $arr = ['village_id' => $village_id, 'handle_type' => 'getLiLinPfVillageRooms', 'pageNum' => $ii];
                    $time_index = $ii - 1;
                    $job_id = $this->traitLilinPfCommunityUserInfo($arr, $time_index * 200, $time_index * 200);
                    fdump_api([__LINE__, 'village_id' => $village_id, 'job_id' => $job_id, 'time_index' => $time_index], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
                }
            }
            $houseVillageFloorDb = new HouseVillageFloor();
            $houseVillageLayer = new HouseVillageLayer();
            $houseVillageSingleDb = new HouseVillageSingle();
            foreach ($ret['data']['list'] as $rvv) {
                if (!isset($rvv['structUnitId']) || empty($rvv['structUnitId'])) {
                    continue;
                }
                $whereArr = array(['village_id', '=', $village_id]);
                $whereArr[] = array('third_floor_source', '=', 'lilin_pf');
                $whereArr[] = array('third_floor_index_id', '=', $rvv['structUnitId']);
                $whereArr[] = array('status', '<', 4);
                $tmpFloorObj = $houseVillageFloorDb->getOne($whereArr, '*');
                $floor_id = 0;
                $single_id = 0;
                if (!empty($tmpFloorObj) && !$tmpFloorObj->isEmpty()) {
                    $floor_id = $tmpFloorObj['floor_id'];
                    $single_id = $tmpFloorObj['single_id'];
                }
                if ($floor_id < 1) {
                    $paramF = array('pager' => array('pageNum' => 1, 'pageSize' => 10), 'params' => array('structId' => $rvv['structUnitId']*1));
                    $floorRet = $this->getUnitInfoPage($paramF);
                    fdump_api([__LINE__, 'village_id' => $village_id, 'paramF' => $paramF, 'floorRet' => $floorRet], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
                    if ($floorRet['data'] && isset($floorRet['data']['list']) && $floorRet['data']['list']) {
                        $floorInfo = $floorRet['data']['list']['0'];
                        if (!isset($floorInfo['structBuildingId']) || empty($floorInfo['structBuildingId'])) {
                            continue;
                        }
                        $whereArr = array(['village_id', '=', $village_id]);
                        $whereArr[] = array('third_single_source', '=', 'lilin_pf');
                        $whereArr[] = array('third_single_index_id', '=', $floorInfo['structBuildingId']);
                        $whereArr[] = array('status', '<', 4);
                        $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
                        $single_id = 0;
                        if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
                            $single_id = $tmpSingleObj['id'];
                        }
                        $callNoFirst4 = '';
                        if ($single_id < 1) {
                            $paramS = array('pager' => array('pageNum' => 1, 'pageSize' => 10), 'params' => array('structId' => $floorInfo['structBuildingId']*1));
                            $singleRet = $this->getBuildingInfoPage($paramS, $village_id);
                            fdump_api([__LINE__, 'village_id' => $village_id, 'paramS' => $paramS, 'floorRet' => $singleRet], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
                            if ($singleRet['data'] && isset($singleRet['data']['list']) && $singleRet['data']['list']) {
                                $singleInfo = $singleRet['data']['list']['0'];
                                $whereArr = array(['village_id', '=', $village_id]);
                                $whereArr[] = array('single_name', '=', $singleInfo['buildingName']);
                                $whereArr[] = array('status', '<', 4);
                                if (isset($singleInfo['callNoFirst4']) && $singleInfo['callNoFirst4']) {
                                    $callNoFirst4 = $singleInfo['callNoFirst4'];
                                }
                                $tmpSingleObj = $houseVillageSingleDb->getOne($whereArr, '*');
                                if ($tmpSingleObj && !$tmpSingleObj->isEmpty()) {
                                    $saveArr = array('single_name' => $singleInfo['buildingName'], 'update_time' => time());
                                    $saveArr['third_single_index_id'] = $singleInfo['structId'];
                                    $saveArr['third_single_source'] = 'lilin_pf';
                                    $saveArr['extra_data'] = json_encode($singleInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    $houseVillageSingleDb->saveOne(array('id' => $tmpSingleObj['id']), $saveArr);
                                    $single_id = $tmpSingleObj['id'];
                                } else {
                                    $saveArr = array('single_name' => $singleInfo['buildingName'], 'village_id' => $village_id);
                                    $saveArr['status'] = 1;
                                    $saveArr['third_single_index_id'] = $singleInfo['structId'];
                                    $saveArr['third_single_source'] = 'lilin_pf';
                                    $saveArr['update_time'] = time();
                                    $saveArr['extra_data'] = json_encode($singleInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    $single_id = $houseVillageSingleDb->addOne($saveArr);
                                }

                            } else {
                                //没有楼栋
                                continue;
                            }
                        }
                        fdump_api([__LINE__, 'village_id' => $village_id, 'single_id' => $single_id], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
                        if ($single_id < 1) {
                            continue;
                        }
                        if (isset($floorInfo['callNoFirst4']) && $floorInfo['callNoFirst4']) {
                            $callNoFirst4 = $floorInfo['callNoFirst4'];
                        }
                        $whereArr = array(['village_id', '=', $village_id]);
                        $whereArr[] = array('floor_name', '=', $floorInfo['unitName']);
                        $whereArr[] = array('single_id', '=', $single_id);
                        $whereArr[] = array('status', '<', 4);
                        $tmpFloorObj = $houseVillageFloorDb->getOne($whereArr, '*');
                        if ($tmpFloorObj && !$tmpFloorObj->isEmpty()) {
                            $saveArr = array('floor_name' => $floorInfo['unitName'], 'update_time' => time());

                            if ($callNoFirst4) {
                                $saveArr['call_no_first4'] = $callNoFirst4;
                            }
                            $saveArr['third_floor_index_id'] = $floorInfo['structId'];
                            $saveArr['third_floor_source'] = 'lilin_pf';
                            $saveArr['extra_data'] = json_encode($floorInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $houseVillageFloorDb->saveOne(array('floor_id' => $tmpFloorObj['floor_id']), $saveArr);
                            $floor_id = $tmpFloorObj['floor_id'];
                        } else {

                            $saveArr = array('floor_name' => $floorInfo['unitName'], 'village_id' => $village_id);
                            $saveArr['status'] = 1;
                            if ($callNoFirst4) {
                                $saveArr['call_no_first4'] = $callNoFirst4;
                            }
                            $saveArr['single_id'] = $single_id;
                            $saveArr['third_floor_index_id'] = $floorInfo['structId'];
                            $saveArr['third_floor_source'] = 'lilin_pf';
                            $saveArr['add_time'] = time();
                            $saveArr['extra_data'] = json_encode($floorInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $floor_id = $houseVillageFloorDb->addOne($saveArr);

                        }


                    } else {
                        //没有单元
                        continue;
                    }
                }
                fdump_api([__LINE__, 'village_id' => $village_id, 'floor_id' => $floor_id, 'single_id' => $single_id], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
                if ($floor_id < 1 || $single_id < 1) {
                    continue;
                }

                $layer_floor = isset($rvv['floor']) && $rvv['floor'] ? $rvv['floor'] : 1;
                $layer_id = 0;
                $whereArr = array(['village_id', '=', $village_id]);
                $whereArr[] = array('third_layer_source', '=', 'lilin_pf');
                $whereArr[] = array('floor_id', '=', $floor_id);
                $whereArr[] = array('layer_number', '=', $layer_floor);
                $whereArr[] = array('status', '<', 4);
                $layerObj = $houseVillageLayer->getOne($whereArr, '*');
                if ($layerObj && !$layerObj->isEmpty()) {
                    $layer_id = $layerObj['id'];
                } else {
                    $whereArr = array(['village_id','=',$village_id]);
                    $whereArr[] = array('floor_id', '=', $floor_id);
                    $whereArr[] = array('layer_number', '=', $layer_floor);
                    $whereArr[] = array('status', '<', 4);
                    $layerObj = $houseVillageLayer->getOne($whereArr, '*');
                    if ($layerObj && !$layerObj->isEmpty()) {
                        $layer_id = $layerObj['id'];
                        $layerSave = array('third_layer_source' => 'lilin_pf');
                        $houseVillageLayer->saveOne(array('id'=>$layer_id),$layerSave);
                    }else{
                        $layerAdd = array('layer_name' => $layer_floor . '层', 'status' => 1, 'single_id' => $single_id, 'floor_id' => $floor_id, 'village_id' => $village_id);
                        $layerAdd['layer_number'] = $layer_floor;
                        $layerAdd['third_layer_source'] = 'lilin_pf';
                        $layerAdd['add_time'] = time();
                        $layer_id = $houseVillageLayer->addOne($layerAdd);
                    }
                }
                $vacancyData = array('usernum' => $rvv['callNo']);
                $vacancyData['houseId'] = $rvv['callNo'];
                $vacancyData['floor_id'] = $floor_id;
                $vacancyData['layer'] = $layer_floor . '层';
                $vacancyData['room_number'] = $rvv['houseCode'];
                $vacancyData['room'] = $rvv['houseName'];
                $vacancyData['status'] = 1;
                $vacancyData['village_id'] = $village_id;
                $vacancyData['name'] = isset($rvv['houseOwner']) && $rvv['houseOwner'] ? $rvv['houseOwner'] : '';
                $vacancyData['type'] = 0;
                $memo = '';
                if (isset($rvv['houseShape']) && $rvv['houseShape']) {
                    $memo = $rvv['houseShape'];
                }
                if (isset($rvv['orientation']) && $rvv['orientation']) {
                    $memo = $memo ? $memo . ' ' . $rvv['orientation'] : $rvv['orientation'];
                }
                $housesize = 0;
                if (isset($rvv['houseArea']) && $rvv['houseArea']) {
                    $memo = $memo ? $memo . ' ' . $rvv['houseArea'] : $rvv['houseArea'];
                    $housesize = floatval($rvv['houseArea']);
                }
                if (isset($rvv['houseName']) && $rvv['houseName']) {
                    $memo = $memo ? $memo . ' ' . $rvv['houseName'] : $rvv['houseName'];
                }
                if (empty($memo) && isset($rvv['describe']) && $rvv['describe']) {
                    $memo = $rvv['describe'];
                }

                $house_type = 1;  //1住宅 2商铺 3办公
                if (isset($rvv['houseType']) && $rvv['houseType'] == 3) {
                    $house_type = 2;
                }elseif (isset($rvv['houseType']) && $rvv['houseType'] == 2){
                    $house_type = 3;
                }
                if (isset($rvv['nodeType']) && $rvv['nodeType'] == 10) {
                    $house_type = 3;
                }
                $sell_status = 1;
                if (isset($rvv['houseState']) && $rvv['houseState'] == 2) {
                    $sell_status = 3; //3出租中
                }
                $vacancyData['memo'] = $memo;
                $vacancyData['housesize'] = $housesize;
                $vacancyData['house_type'] = $house_type;
                $vacancyData['user_status'] = 2;
                $vacancyData['sell_status'] = $sell_status;
                $vacancyData['single_id'] = $single_id;
                $vacancyData['layer_id'] = $layer_id;
                $vacancyData['property_number'] = $single_id . $floor_id . $layer_id . $rvv['houseCode'];
                $vacancyData['room_alias_id'] = $rvv['houseCode'];
                $vacancyData['third_room_id'] = $rvv['structId'];
                $vacancyData['third_room_source'] = 'lilin_pf';

                $houseVillageUserVacancy = new HouseVillageUserVacancy();
                $whereArr = array(['village_id','=',$village_id]);
                $whereArr[] = array('third_room_source', '=', 'lilin_pf');
                $whereArr[]=  array('floor_id','=',$floor_id);
                $whereArr[] = array('third_room_id', '=', $rvv['structId']);
                $whereArr[] = array('is_del', '<', 1);
                $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
                $room_id = 0;
                if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
                    //编辑
                    $room_id = $userVacancyObj['pigcms_id'];
                    $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancyObj['pigcms_id']), $vacancyData);
                } else {

                    $whereArr = array(['village_id','=',$village_id]);
                    $whereArr[] = array('room', '=', $rvv['houseName']);
                    $whereArr[]=['single_id','=',$single_id];
                    $whereArr[]=['layer_id','=',$layer_id];
                    $whereArr[]=['floor_id','=',$floor_id];
                    $whereArr[] = array('is_del', '<', 1);
                    $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
                    if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
                        //编辑
                        $room_id = $userVacancyObj['pigcms_id'];
                        $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancyObj['pigcms_id']), $vacancyData);
                    }else{
                        $vacancyData['add_time'] = time();
                        $room_id = $houseVillageUserVacancy->addOne($vacancyData);  
                    }
                    
                }
            }
        }else{
            $returnArr['err_code']=1;
            $returnArr['err_msg']=$ret['err_msg'] ? $ret['err_msg']:'没有获取到房屋数据！';

        }
        fdump_api([__LINE__, 'village_id' => $village_id, 'returnArr' => $returnArr], 'LiLinDevAccess/getLiLinPfVillageRooms', 1);
        return true;
    }
    
    /****
     * 拉取立林平台设备数据
     **/
    public function getLilinPfDevices($village_id = 0)
    {

        $this->village_id=$village_id;
        $hVillage=$this->getVillagInfo($village_id);
        if(!empty($hVillage['lilin_pf_client_id']) && !empty($hVillage['lilin_pf_client_secret']) && !empty($hVillage['lilin_village_no'])){
            $dataParam = array('village_id' => $village_id,'pageNum'=>1);
            $dataParam['handle_type'] = 'getLilinPfDeviceList';
            $job_id = $this->traitLilinPfCommunityDevice($dataParam, 3, 120);
            fdump_api(['village_id' =>$village_id,'job_id'=>$job_id], 'LiLinDevAccess/getLilinPfDeviceList' , 1);
            return array('job_id' => $job_id);
        }else{
            return array('job_id' => '','err_msg'=>'未配置对接参数！');
        }
    }

    public function getLilinPfDeviceList($village_id = 0,$data=array()){
        $this->village_id = $village_id;
        $pageNum = isset($data['pageNum']) && $data['pageNum'] ? intval($data['pageNum']) : 1;
        $pageSize = 100;
        fdump_api([__LINE__, 'village_id' => $village_id, 'data' => $data], 'LiLinDevAccess/getLilinPfDeviceList', 1);
        $param = array('pager' => array('pageNum' => $pageNum, 'pageSize' => $pageSize));
        $ret = $this->getDeviceInfoPage($param, $village_id);
        fdump_api([__LINE__, 'village_id' => $village_id, 'ret' => $ret], 'LiLinDevAccess/getLilinPfDeviceList', 1);
        $returnArr = array('err_code' => 0, 'err_msg' => '', 'data' => '');
        if ($ret['data'] && isset($ret['data']['list']) && $ret['data']['list']) {
            $totalPage = $ret['data']['totalPage'];
            if ($pageNum == 1 && $totalPage > 1) {
                for ($ii = 2; $ii <= $totalPage; $ii++) {
                    $arr = ['village_id' => $village_id, 'handle_type' => 'getLilinPfDeviceList', 'pageNum' => $ii];
                    $time_index = $ii - 1;
                    $job_id = $this->traitLilinPfCommunityDevice($arr, $time_index * 200, $time_index * 200);
                    fdump_api([__LINE__, 'village_id' => $village_id, 'job_id' => $job_id, 'time_index' => $time_index], 'LiLinDevAccess/getLilinPfDeviceList', 1);
                }
            }
            $deviceType = array(4097, 4141, 4103, 4112, 4128, 4133, 4142, 4100,8195);  //对接门禁类型 4097门禁主机 4141独立门禁机   4128道闸人脸门禁机 4112门禁控制器 4128道闸人脸门禁机
            foreach ($ret['data']['list'] as $pdata) {
                if (!isset($pdata['deviceType']) || !in_array($pdata['deviceType'], $deviceType)) {
                    continue;
                }
                if(!isset($pdata['deviceId']) || empty($pdata['deviceId'])){
                    continue;
                }
                $houseFaceDevice = new HouseFaceDevice();
                $saveArr = array('device_name' => $pdata['deviceName'], 'device_type' => $pdata['deviceType']);
                if (isset($pdata['name']) && $pdata['name']) {
                    $saveArr['device_name'] = $pdata['name'];
                }
                $saveArr['device_sn'] = isset($pdata['did']) && $pdata['did'] ? $pdata['did'] : '';
                $saveArr['village_id'] = $village_id;
                $saveArr['device_status'] = 1;
                if(isset($pdata['status'])){
                    $saveArr['device_status'] = $pdata['status'] == 1 ? 1 : 2;
                    if ($pdata['deviceType'] == 4097 && $pdata['status'] == 3) {
                        $saveArr['device_status'] = 1;
                    }
                }
                $saveArr['thirdDeviceTypeStr'] = $pdata['deviceType'];
                $saveArr['device_direction'] = 2;
                if (isset($pdata['ip']) && $pdata['ip']) {
                    $saveArr['device_ip'] = $pdata['ip'];
                }
                if (isset($pdata['port']) && $pdata['port']) {
                    $saveArr['thirdPort'] = $pdata['port'];
                }
                if ($pdata['deviceType'] == 4142) {
                    $saveArr['device_model'] = 1;
                }
                $saveArr['device_brand'] = 'lilin_pf';
                $saveArr['thirdAddress'] = isset($pdata['mac']) ? $pdata['mac']:'';
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
                $saveArr['third_dev_id'] = $pdata['deviceId'];

                $floor_id = 0;
                $single_id = 0;

                $saveArr['extra_data'] = json_encode($pdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $whereArr = array(['village_id','=',$village_id]);
                $whereArr[] = array('third_dev_source', '=', 'lilin_pf');
                $whereArr[] = array('third_dev_id', '=', $pdata['deviceId']);
                $whereArr[] = array('is_del', '<', 4);
                $faceDeviceObj = $houseFaceDevice->getOne($whereArr, '*');
                $device_id = 0;
                if ($faceDeviceObj && !$faceDeviceObj->isEmpty()) {
                    $saveArr['last_time'] = time();
                    $device_id = $faceDeviceObj['device_id'];

                    $houseFaceDevice->saveData(array('device_id' => $faceDeviceObj['device_id']), $saveArr);
                } else {
                    $saveArr['add_time'] = time();
                    $saveArr['floor_id'] = $floor_id;
                    $saveArr['single_id'] = $single_id;
                    $device_id = $houseFaceDevice->addData($saveArr);
                }
                
            }
            
        }
        return true;
    }
    
    /****
     * 拉取立林平台住户数据
     * ptype 1业主 2租客
     * r_type 1小区住户 2来访人员3工作人员6临时住户
     **/
    public function getLilinPfUserDatas($village_id = 0,$ptype=1)
    {
        $this->village_id=$village_id;
        $hVillage=$this->getVillagInfo($village_id);
        if(!empty($hVillage['lilin_pf_client_id']) && !empty($hVillage['lilin_pf_client_secret']) && !empty($hVillage['lilin_village_no'])){
            $dataParam = array('village_id' => $village_id,'pageNum'=>1,'ptype'=>$ptype,'r_type'=>1);
            $dataParam['handle_type'] = 'getLilinPfUserList';
            $job_id = $this->traitLilinPfCommunityDevice($dataParam, 3, 120);
            fdump_api(['village_id' =>$village_id,'job_id'=>$job_id], 'LiLinDevAccess/getLilinPfUserList' , 1);
            return array('job_id' => $job_id);
        }else{
            return array('job_id' => '','err_msg'=>'未配置对接参数！');
        }
    }
    
    public function getLilinPfUserList($village_id = 0,$data=array()){

        $this->village_id = $village_id;
        $pageNum = isset($data['pageNum']) && $data['pageNum'] ? intval($data['pageNum']) : 1;
        $ptype= isset($data['ptype']) && $data['ptype'] ? intval($data['ptype']) : 1;
        $r_type= isset($data['r_type']) && $data['r_type'] ? intval($data['r_type']) : 1;
        $pageSize = 100;
        fdump_api([__LINE__, 'village_id' => $village_id, 'data' => $data], 'LiLinDevAccess/getLilinPfUserList', 1);
        $param = array('pager' => array('pageNum' => $pageNum, 'pageSize' => $pageSize), 'params' => array('personType' => $ptype,'registerType'=>$r_type));
        $ret = $this->getPersonInfoPage($param, $village_id);
        fdump_api([__LINE__, 'village_id' => $village_id, 'ret' => $ret], 'LiLinDevAccess/getLilinPfUserList', 1);

        if ($ret['data'] && isset($ret['data']['list']) && $ret['data']['list']) {
            $totalPage = $ret['data']['totalPage'];
            if ($pageNum == 1 && $totalPage > 1) {
                for ($ii = 2; $ii <= $totalPage; $ii++) {
                    $arr = ['village_id' => $village_id, 'handle_type' => 'getLilinPfUserList', 'pageNum' => $ii,'ptype'=>$ptype,'r_type'=>1];
                    $time_index = $ii - 1;
                    $job_id = $this->traitLilinPfCommunityDevice($arr, $time_index * 200, $time_index * 200);
                    fdump_api([__LINE__, 'village_id' => $village_id, 'job_id' => $job_id, 'time_index' => $time_index], 'LiLinDevAccess/getLilinPfUserList', 1);
                }
            }
            $service_user = new UserService();
            $houseVillageUserBindDb = new HouseVillageUserBind();
            $houseVillageUserVacancy = new HouseVillageUserVacancy();
            $houseFaceImg = new HouseFaceImg();
            foreach ($ret['data']['list'] as $pdata) {
                $houseInfos = isset($pdata['houseInfos']) ? $pdata['houseInfos'] : array();
                $bindUser = array('village_id' => $village_id, 'usernum' => '');
                $bindUser['name'] = $pdata['personName'];
                $phone = isset($pdata['phoneNumber']) ? $pdata['phoneNumber'] : '';
                if(empty($phone)){
                    continue;
                }
                $bindUser['phone']=$phone;
                $bindUser['uid']=0;
                $userOneObj=$service_user->getUserOne(array('phone'=>$phone),'*');
                if($userOneObj && isset($userOneObj['uid']) && $userOneObj['uid']){
                    $bindUser['uid']=$userOneObj['uid'];
                }
                $bindUser['type'] = 0;
                if ($pdata['personType'] == 1) {
                    $bindUser['type'] = 0;
                } elseif ($pdata['personType'] == 2) {
                    //租客
                    $bindUser['type'] = 2;
                } else {
                    $bindUser['type'] = 1;
                }
                $bindUser['id_card'] = isset($pdata['certificatesNumber']) ? $pdata['certificatesNumber'] : '';
                $bindUser['birth'] = isset($pdata['birthDate']) ? $pdata['birthDate'] : '';
                $bindUser['memo'] = isset($pdata['remark']) ? $pdata['remark'] : '';
                $bindUser['third_type'] = 'lilin_pf';

                $facePhotoUrl='';
                if (isset($pdata['facePhotoUrl']) && !empty($pdata['facePhotoUrl'])) {
                    $facePhotoUrl = $pdata['facePhotoUrl'];

                }elseif (isset($pdata['certificatesUrl']) && !empty($pdata['certificatesUrl'])){
                    //$facePhotoUrl = $pdata['certificatesUrl'];
                }
                $img_url = $facePhotoUrl;
                $facePhotoUrl= $this->downloadImg($facePhotoUrl);
                if ( isset($pdata['cardList']) && !empty($pdata['cardList'])) {
                    foreach ($pdata['cardList'] as $cvv) {
                        if ($cvv['cardType'] == 2 && $cvv['cardNo']) {
                            $bindUser['id_card'] = $cvv['cardNo'];
                        }
                        if (in_array($cvv['cardType'], [1, 5]) && $cvv['cardNo']) {
                            $bindUser['ic_card'] = $cvv['cardNo'];
                        }
                    }
                }
                $bindUser['uid_third_id'] = $pdata['personId'];
                $bindUser['extra_data']=json_encode($pdata,JSON_UNESCAPED_UNICODE);
                $noFindArr = array();
                $nowTime = time();
                foreach ($houseInfos as $room) {
                    $whereArr = array(['village_id','=',$village_id]);
                    $whereArr[] = array('third_room_source', '=', 'lilin_pf');
                    $whereArr[] = array('third_room_id', '=', $room['houseStructId']);
                    $whereArr[] = array('is_del', '<', 1);
                    $userVacancyObj = $houseVillageUserVacancy->getOne($whereArr, '*');
                    if ($userVacancyObj && !$userVacancyObj->isEmpty()) {
                        $userVacancy = $userVacancyObj->toArray();
                        //找到房间
                        $tmpBindUser = $bindUser;
                        $tmpBindUser['bind_third_id'] = $room['houseStructId'];
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
                        $updateArr = array('name' => $tmpBindUser['name'], 'phone' => $tmpBindUser['phone'], 'type' => $tmpBindUser['type'], 'user_status' => 1,'uid'=>$tmpBindUser['uid']);

                        if (isset($room['personType']) && $room['personType'] == 1) {
                            $tmpBindUser['type'] = 0;
                        } elseif (isset($room['personType']) && $room['personType'] == 2) {
                            //租客
                            $tmpBindUser['type'] = 2;
                        }else if(isset($room['personType']) && $room['personType']){
                            $tmpBindUser['type'] = 1;
                        }
                        
                        if (isset($room['effectTime']) && $room['effectTime']) {
                            $tmpBindUser['property_starttime'] = strtotime($room['effectTime']);
                            $updateArr['property_starttime'] = $tmpBindUser['property_starttime'];
                        }
                        if (isset($room['expireTime']) && $room['expireTime']) {
                            $tmpBindUser['property_endtime'] = strtotime($room['expireTime']);
                            $updateArr['property_endtime'] = $tmpBindUser['property_endtime'];
                        }
                        $whereArr = array(['village_id','=',$village_id]);
                        $whereArr[] = array('vacancy_id', '=', $userVacancy['pigcms_id']);
                        $whereArr[] = array('status', '<', 4);
                        $whereArr[] = array('type', 'in', array(0,3));
                        $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
                        $fz_pigcms_id=0;
                        if($userBindObj && !$userBindObj->isEmpty()){
                            $fz_pigcms_id=$userBindObj['pigcms_id'];
                        }

                        $whereArr = array(['village_id','=',$village_id]);
                        $whereArr[] = array('third_type', '=', 'lilin_pf');
                        $whereArr[] = array('uid_third_id', '=', $pdata['personId']);
                        $whereArr[] = array('type', '<', 4);
                        $whereArr[] = array('vacancy_id', '=', $userVacancy['pigcms_id']);
                        $whereArr[] = array('status', '<', 4);
                        $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
                        if ($userBindObj && !$userBindObj->isEmpty()) {
                            $updateArr['uid'] = $userBindObj['uid'];
                            if($fz_pigcms_id>0 && $userBindObj['pigcms_id']!=$fz_pigcms_id){
                                $tmpBindUser['parent_id']=$fz_pigcms_id;
                                if($tmpBindUser['type']==0){
                                    $tmpBindUser['type']=1;
                                }
                            }
                            $houseVillageUserBindDb->saveOne(array('pigcms_id' => $userBindObj['pigcms_id']), $tmpBindUser);
                            if ($facePhotoUrl) {
                                $saveImgArr = array('face_img_id' => 0, 'uid' => $userBindObj['uid'], 'img_url' =>$facePhotoUrl );
                                $saveImgArr['status'] = 0;
                                $saveImgArr['a2_face_door_img'] = $img_url;
                                $saveImgArr['bind_pigcms_id'] = $userBindObj['pigcms_id'];

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
                            $whereArr = array(['village_id','=',$village_id]);
                            $whereArr[] = array('phone', '=', $phone);
                            $whereArr[] = array('type', '<', 4);
                            $whereArr[] = array('vacancy_id', '=', $userVacancy['pigcms_id']);
                            $whereArr[] = array('status', '<', 4);
                            $userBindObj = $houseVillageUserBindDb->getOne($whereArr, '*', 'pigcms_id desc');
                            if ($userBindObj && !$userBindObj->isEmpty()) {
                                $updateArr['uid'] = $userBindObj['uid'];
                                if($fz_pigcms_id>0 && $userBindObj['pigcms_id']!=$fz_pigcms_id){
                                    $tmpBindUser['parent_id']=$fz_pigcms_id;
                                    if($tmpBindUser['type']==0){
                                        $tmpBindUser['type']=1;
                                    }
                                }
                                $houseVillageUserBindDb->saveOne(array('pigcms_id' => $userBindObj['pigcms_id']), $tmpBindUser);
                                if ($facePhotoUrl) {
                                    $saveImgArr = array('face_img_id' => 0, 'uid' => $userBindObj['uid'], 'img_url' =>$facePhotoUrl );
                                    $saveImgArr['status'] = 0;
                                    $saveImgArr['a2_face_door_img'] = $img_url;
                                    $saveImgArr['bind_pigcms_id'] = $userBindObj['pigcms_id'];

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
                            }else{
                                $tmpBindUser['add_time'] = $nowTime;
                                $pigcms_id = $houseVillageUserBindDb->addOne($tmpBindUser);
                                if ($img_url) {
                                    $saveImgArr = array('face_img_id' => 0, 'uid' => 0, 'img_url' =>$facePhotoUrl , 'add_time' => $nowTime);
                                    $saveImgArr['status'] = 0;

                                    $saveImgArr['a2_face_door_img'] = $img_url;
                                    $saveImgArr['bind_pigcms_id'] = $pigcms_id;

                                    $houseFaceImg->addOne($saveImgArr);
                                }
                            }
                        }
                        if($tmpBindUser['type']==0 || $fz_pigcms_id<1){
                            $houseVillageUserVacancy->saveOne(array('pigcms_id' => $userVacancy['pigcms_id']), $updateArr);
                        }
                    } else {
                        $noFindArr[] = $room['houseStructId'];
                    }
                }
            }

        }
        return true;
    }

    /***********如下是云平台接口以及相应逻辑******************/
    
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
            $propertyServiceTime['startTime'] = $propertyServiceTime['startTime'];
            $propertyServiceTime['endTime'] = $propertyServiceTime['endTime'];
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