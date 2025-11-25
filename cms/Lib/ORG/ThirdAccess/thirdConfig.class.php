<?php
/**
 * 开放客户三方对接
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2022/1/15 11:45
 */
class thirdConfig{

    public $paramData = [];
    /**
     * 构造函数
     *
     * @param   array   配置数组 : $config = array('businessType'=>'','businessId'=>'','thirdType'=>'','thirdSite'=>''...);
     */
    public function __construct() {

    }

    public $limitThirdTypeBtnArr = ['linghui'];
    public $thirdTypeTitle = [
        'linghui' => '智慧楼宇管理系统',
    ];

    public function setHouseThirdConfig($paramData = array()) {
        // ↑ 操作类型 add 添加  edit编辑
        $operation = isset($paramData['operation'])&&trim($paramData['operation'])?trim($paramData['operation']):'';
        $nowTime = isset($paramData['nowTime'])&&trim($paramData['nowTime'])?trim($paramData['nowTime']):time();
        $businessId = trim($paramData['businessId']);
        $thirdType = trim($paramData['thirdType']);
        $thirdSite = trim($paramData['thirdSite']);
        $businessType = trim($paramData['businessType']);
        $thirdId = trim($paramData['thirdId'])?$paramData['thirdId']:0;
        $thirdName = trim($paramData['thirdName'])?$paramData['thirdName']:'';
        $checkId = trim($paramData['checkId'])?$paramData['checkId']:md5(uniqid().$nowTime.'_'.$businessId);
        $db_house_third_config = D('House_third_config');
        if ($businessType && $businessId && $thirdType) {
            $whereRepeat = [
                'businessType' => $businessType,
                'businessId' => $businessId,
                'thirdType' => $thirdType,
            ];
            if (isset($paramData['thirdId'])) {
                // 传递了参数就以该参数错误条件查询
                $whereRepeat['thirdId'] = $thirdId;
            }
            $repeatInfo = $db_house_third_config->where($whereRepeat)->find();
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
                if (isset($paramData['thirdAppid']) && isset($paramData['thirdAppkey']) && trim($paramData['thirdAppid']) && trim($paramData['thirdAppkey'])) {
                    // 配置参数 必须同时存在记录
                    $thirdConfig['thirdAppid'] = trim($paramData['thirdAppid']);
                    $thirdConfig['thirdAppkey'] = trim($paramData['thirdAppkey']);
                }
                $db_house_third_config->add($thirdConfig);
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
                if ($saveData) {
                    $saveData['updateTime'] = $nowTime;
                    $data['updateTime'] = $saveData['updateTime'];
                    $db_house_third_config->where($whereRepeat)->save($saveData);
                }
            }
            return $data;
        } else {
            throw new \Exception('必要参数缺失');
        }
    }

    public function getHouseThirdConfig($whereParam) {
        $whereParam['status'] = ['neq',4];
        $db_house_third_config = D('House_third_config');
        $infoHouseThirdConfig = $db_house_third_config->where($whereParam)->find();
        if (isset($infoHouseThirdConfig['checkId'])&&$infoHouseThirdConfig['checkId']) {
            $infoHouseThirdConfig['appsecret'] = $infoHouseThirdConfig['checkId'];
        }
        if (isset($infoHouseThirdConfig['thirdAppid'])&&$infoHouseThirdConfig['thirdAppid']) {
            $infoHouseThirdConfig['appid'] = $infoHouseThirdConfig['thirdAppid'];
        }
        if (isset($infoHouseThirdConfig['thirdAppkey'])&&$infoHouseThirdConfig['thirdAppkey']) {
            $infoHouseThirdConfig['appkey'] = $infoHouseThirdConfig['thirdAppkey'];
        }
        return $infoHouseThirdConfig;
    }
}
