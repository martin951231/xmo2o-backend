<?php
namespace app\thirdAccess\model\service;

use app\thirdAccess\model\db\HouseThirdConfig;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HouseVillageService;

class ThirdAccessCheckService {

    public $thirdType; // 三方类型 目前楼宇  linghui
    public $businessType; // 父级业务类型  默认小区 village
    public $businessId; // 父级对应业务id
    public $village_id;// 小区id
    public $property_id;// 物业id

    public function getThirdConfig($appid,$appkey,$appsecret) {
        if (!$appid || !$appkey) {
            return false;
        }
        $where = [];
        if ($appid) {
            $where[] = ['thirdAppid','=',$appid];
        }
        if ($appkey) {
            $where[] = ['thirdAppkey','=',$appkey];
        }
        if ($appsecret) {
            $where[] = ['checkId','=',$appsecret];
        }
//        if (isset($param['businessType'])&&$param['businessType']) {
//            $where[] = ['businessType' => $param['businessType']];
//        }
//        if (isset($param['businessId'])&&$param['businessId']) {
//            $where[] = ['businessId' => $param['businessId']];
//        }
//        if (isset($param['thirdType'])&&$param['thirdType']) {
//            $where[] = ['thirdType' => $param['thirdType']];
//        }
        $infoHouseThirdConfig = $this->getThirdConfigOne($where);
        fdump([$infoHouseThirdConfig],'$infoHouseThirdConfig');
        if (!isset($infoHouseThirdConfig['third_id'])||!$infoHouseThirdConfig['third_id']) {
            return [];
        } else {
            return $infoHouseThirdConfig;
        }
    }

    /**
     * 单个信息查询
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @param array|string $order 排序
     * @return array|\think\Model
     */
    public function getThirdConfigOne($where,$field = true, $order=[]) {
        $dbHouseThirdConfig = new HouseThirdConfig();
        $infoHouseThirdConfig = $dbHouseThirdConfig->getOne($where,$field, $order );
        return $infoHouseThirdConfig;
    }


    public function addThirdConfig($data) {
        $dbHouseThirdConfig = new HouseThirdConfig();
        $third_id = $dbHouseThirdConfig->add($data);
        return $third_id;
    }
    public function saveThirdConfig($where,$data) {
        $dbHouseThirdConfig = new HouseThirdConfig();
        $thirdSave = $dbHouseThirdConfig->updateThis($where,$data);
        return $thirdSave;
    }
    public function getThirdConfigSome($where,$field=true,$order=true,$page=0,$limit=0) {
        $dbHouseThirdConfig = new HouseThirdConfig();
        $thirdList = $dbHouseThirdConfig->getSome($where,$field,$order,$page,$limit);
        return $thirdList;
    }

    public function setHouseThirdConfig($paramData) {
        // ↑ 操作类型 add 添加  edit编辑
        $operation = isset($paramData['operation'])&&trim($paramData['operation'])?trim($paramData['operation']):'';
        $nowTime = isset($paramData['nowTime'])&&trim($paramData['nowTime'])?trim($paramData['nowTime']):time();
        $businessId = trim($paramData['businessId']);
        $thirdType = trim($paramData['thirdType']);
        $thirdSite = trim($paramData['thirdSite']);
        $businessType = trim($paramData['businessType']);
        $thirdId = isset($paramData['thirdId'])&&trim($paramData['thirdId'])?$paramData['thirdId']:0;
        $thirdFid = isset($paramData['thirdFid'])&&trim($paramData['thirdFid'])?$paramData['thirdFid']:0;
        $thirdName = isset($paramData['thirdName'])&&trim($paramData['thirdName'])?$paramData['thirdName']:'';
        $checkId = isset($paramData['checkId'])&&trim($paramData['checkId'])?$paramData['checkId']:md5(uniqid().$nowTime.'_'.$businessId);
        if ($businessType && $businessId && $thirdType) {
            $whereRepeat = [];
            $whereRepeat[] = ['businessType','=',$businessType];
            $whereRepeat[] = ['businessId','=',$businessId];
            $whereRepeat[] = ['thirdType','=',$thirdType];
            if (isset($paramData['thirdId'])) {
                // 传递了参数就以该参数错误条件查询
                $whereRepeat[] = ['thirdId','=',$thirdId];
            }
            $repeatInfo = $this->getThirdConfigOne($whereRepeat);
            if (!$repeatInfo && 'edit'==$operation) {
                // 如果是编辑但是编辑数据不存在
                throw new \Exception('对应更新对象不存在');
            } elseif (!$repeatInfo) {
                $thirdConfig = [
                    'checkId' => $checkId,
                    'businessType' => $businessType,
                    'businessId' => $businessId,
                    'thirdType' => $thirdType,
                    'thirdSite' => $thirdSite,
                    'thirdName' => $thirdName,
                    'thirdId' => $thirdId,
                    'addTime' => $nowTime,
                    'status' => 1,
                ];
                if (isset($paramData['thirdFid'])) {
                    $thirdConfig['thirdFid'] = $thirdFid;
                }
                if (isset($paramData['thirdAppid']) && isset($paramData['thirdAppkey']) && trim($paramData['thirdAppid']) && trim($paramData['thirdAppkey'])) {
                    // 配置参数 必须同时存在记录
                    $thirdConfig['thirdAppid'] = trim($paramData['thirdAppid']);
                    $thirdConfig['thirdAppkey'] = trim($paramData['thirdAppkey']);
                }
                $third_id = $this->addThirdConfig($thirdConfig);
                if ($third_id && $operation=='ProductSecretKey') {
                    // 生成秘钥
                    $whereRepeat = [];
                    $whereRepeat[] = ['third_id','=',$third_id];
                    $saveData = [];
                    $thirdAppid = 10000000 + intval($third_id);
                    $saveData['thirdAppid'] = $thirdAppid;
                    $thirdAppkey = create_random_str(20);
                    $saveData['thirdAppkey'] = $thirdAppkey;
                    $this->saveThirdConfig($whereRepeat,$saveData);
                    $thirdConfig['appid'] = $thirdAppid;
                    $thirdConfig['appkey'] = $thirdAppkey;
                }
                $data = $thirdConfig;
            } else {
                $saveData = [];
                $data = $repeatInfo;
                if ($thirdSite) {
                    $saveData['thirdSite'] = $thirdSite;
                    $data['thirdSite'] = $saveData['thirdSite'];
                }
                if ($thirdName) {
                    $saveData['thirdName'] = $thirdName;
                    $data['thirdName'] = $saveData['thirdName'];
                }
                if (isset($paramData['thirdAppid']) && isset($paramData['thirdAppkey']) && trim($paramData['thirdAppid']) && trim($paramData['thirdAppkey'])) {
                    // 配置参数 必须同时存在记录
                    $saveData['thirdAppid'] = trim($paramData['thirdAppid']);
                    $saveData['thirdAppkey'] = trim($paramData['thirdAppkey']);
                    $data['thirdAppid'] = $saveData['thirdAppid'];
                    $data['thirdAppkey'] = $saveData['thirdAppkey'];
                }
                if ($operation=='ProductSecretKey'&&!$repeatInfo['thirdAppid']) {
                    // 生成秘钥
                    $thirdAppid = 10000000 + $repeatInfo['third_id'];
                    $saveData['thirdAppid'] = $thirdAppid;
                    $thirdAppkey = create_random_str(20);
                    $saveData['thirdAppkey'] = $thirdAppkey;
                    $data['appid'] = $thirdAppid;
                    $data['appkey'] = $thirdAppkey;
                } elseif ($operation=='ProductSecretKey') {
                    $data['appid'] = $repeatInfo['thirdAppid'];
                    $data['appkey'] = $repeatInfo['thirdAppkey'];
                }

                if ($saveData) {
                    $saveData['updateTime'] = $nowTime;
                    $data['updateTime'] = $saveData['updateTime'];
                    $this->saveThirdConfig($whereRepeat,$saveData);
                }
            }
            return $data;
        } else {
            throw new \Exception('必要参数缺失');
        }
    }

    public function setDataConfig($paramData) {
        $this->thirdType = trim($paramData['thirdType']);
        $this->property_id = isset($paramData['property_id'])&&intval($paramData['property_id'])?intval($paramData['property_id']):0;
        $this->village_id = isset($paramData['village_id'])&&intval($paramData['village_id'])?intval($paramData['village_id']):0;
        if ($paramData['businessType']=='property') {
            // 查询父级过滤权限
            $this->businessType = 'system';
            $this->businessId = -999;
            $thirdAccessConfig = $this->filterVillageAuth();
            if (!isset($paramData['thirdName']) || !$paramData['thirdName']) {
                $where = [
                    'id' => $this->property_id
                ];
                $detail = (new HousePropertyService)->getFind($where,'property_name');
                if (isset($detail['property_name'])&&$detail['property_name']) {
                    $paramData['thirdName']=$detail['property_name'];
                }
            }
        } elseif ($paramData['businessType']=='village') {
            // 查询父级过滤权限
            if (!isset($paramData['thirdName']) || !$paramData['thirdName'] || !isset($paramData['property_id']) || !$paramData['property_id']) {
                $detail = (new HouseVillageService)->getHouseVillageByVillageId($this->village_id,'village_name,property_id');
                if (isset($detail['village_name'])&&$detail['village_name']) {
                    $paramData['thirdName']=$detail['village_name'];
                }
                if (isset($detail['property_id'])&&$detail['property_id']) {
                    $this->property_id = $paramData['property_id'] = $detail['property_id'];
                }
            }
            $this->businessType = 'property';
            $this->businessId = $this->property_id;
            $thirdAccessConfig = $this->filterVillageAuth();
        } elseif ($paramData['businessType']=='system') {
            $thirdAccessConfig = [];
        }
        if (isset($thirdAccessConfig['third_id'])&&$thirdAccessConfig['third_id']) {
            $paramData['thirdFid'] = $thirdAccessConfig['third_id'];
        }
        $data = $this->setHouseThirdConfig($paramData);
        return $data;
    }

    // 小区操作权限过滤
    public function filterVillageAuth() {
        $whereThirdConfig = [];
        $whereThirdConfig[] = ['thirdType','=',$this->thirdType];
        $whereThirdConfig[] = ['businessType','=',$this->businessType];
        $whereThirdConfig[] = ['businessId','=',$this->businessId];
        $thirdAccessConfig = $this->getThirdConfigOne($whereThirdConfig);
        if (empty($thirdAccessConfig)||!isset($thirdAccessConfig['third_id'])||!$thirdAccessConfig['third_id']) {
            throw new \Exception('没有权限进行当前操作');
        }
        return $thirdAccessConfig;
    }
}