<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2023/10/27 9:20
 */

namespace app\community\model\service;

use app\community\model\db\HouseVillageRobleasePositionOrder;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillage;
use app\community\model\db\User;
use app\community\model\db\HouseVillagePositionSet;
use app\community\model\db\HouseVillageUserBind;
use think\Exception;
use think\facade\Db;

class HouseVillageRobleasePositionOrderService
{
    public function getOneData($where, $field = true)
    {

        $houseVillageRobleasePositionOrderDb = new HouseVillageRobleasePositionOrder();
        $info = $houseVillageRobleasePositionOrderDb->getOneData($where, $field);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        } else {
            $info = array();
        }
        return $info;
    }

    /**
     **计划任务处理失效丢单
     ***/
    public function autoCancelPositionOrder()
    {
        $houseVillageRobleasePositionOrderDb = new HouseVillageRobleasePositionOrder();
        $nowtime = time();
        $nowtime_str = date('Y-m-d H:i:s');
        $whereArr = array();
        $whereArr[] = array('village_id', '>', 0);
        $whereArr[] = array('status', '=', 0);
        $min_time=946656000;
        $whereArr[] = array('expire_time', '>', $min_time);
        $whereArr[] = array('expire_time', '<', $nowtime);
        $positionOrderObj = $houseVillageRobleasePositionOrderDb->getListData($whereArr, 'id,village_id,uid,order_no,status,expire_time', 'expire_time ASC', 1, 20);
        if ($positionOrderObj && !$positionOrderObj->isEmpty()) {
            $positionOrders = $positionOrderObj->toArray();
            if (!empty($positionOrders)) {
                foreach ($positionOrders as $pvv) {
                    $saveArr = array('status' => 2, 'opt_time' => $nowtime, 'remark' => $nowtime_str . '自动失效了');
                    $houseVillageRobleasePositionOrderDb->saveOneData(array('id' => $pvv['id']), $saveArr);
                }
            }
        }
        return true;
    }

    public function getParkingPositionIds($where, $field = '*')
    {
        $houseVillageParkingPositionDb = new HouseVillageParkingPosition();
        $parkingPosition = $houseVillageParkingPositionDb->getList($where, $field, 0);
        $positionids = array();
        if ($parkingPosition && !$parkingPosition->isEmpty()) {
            $positions = $parkingPosition->toArray();
            foreach ($positions as $pvv) {
                $positionids[] = $pvv['position_id'];
            }
            $positionids = array_unique($positionids);
        }
        return $positionids;
    }

    public function getConditionListData($where, $field = '*', $page = 1, $page_size = 10, $orderby = 'po.id DESC')
    {
        $returnArr = array('list' => array(), 'count' => 0, 'page_size' => $page_size);
        $houseVillageRobleasePositionOrderDb = new HouseVillageRobleasePositionOrder();
        $dataObj = $houseVillageRobleasePositionOrderDb->getConditionListData($where, $field, $orderby, $page, $page_size);
        if ($dataObj && !$dataObj->isEmpty()) {
            $nowtime = time();
            $datas = $dataObj->toArray();
            $count = $houseVillageRobleasePositionOrderDb->getConditionListCount($where);
            $statusArr = array('0' => '未办理', '1' => '已办理', '2' => '已失效', '3' => '取消申请', '4' => '取消申请');
            $houseVillageParkingPositionDb = new HouseVillageParkingPosition();
            $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
            $garageArr = array();
            foreach ($datas as $kk => $vv) {
                unset($datas[$kk]['extra_data'],$datas[$kk]['remark']);
                $datas[$kk]['add_time_str'] = date('Y-m-d H:i:s', $vv['add_time']);
                $datas[$kk]['opt_time_str'] = $vv['opt_time'] > 0 ? date('Y-m-d H:i:s', $vv['opt_time']) : '';
                $datas[$kk]['status_str'] = isset($statusArr[$vv['status']]) ? $statusArr[$vv['status']] : '未知';
                $xtype_str = '';
                if ($vv['xtype'] == 1) {
                    $xtype_str = '租赁';
                } else if ($vv['xtype'] == 2) {
                    $xtype_str = '抢购';
                }
                $datas[$kk]['position_num'] = '';
                $datas[$kk]['garage_num'] = '';
                $datas[$kk]['xtype_str'] = $xtype_str;
                $whereTmpArr = array(['village_id', '=', $vv['village_id']], ['position_id', '=', $vv['position_id']]);
                $parkingPosition = $houseVillageParkingPositionDb->getFind($whereTmpArr, 'position_id,garage_id,position_num,village_id');
                if ($parkingPosition && !$parkingPosition->isEmpty()) {
                    $parkingPosition = $parkingPosition->toArray();
                    $datas[$kk]['position_num'] = $parkingPosition['position_num'];
                }
                if (isset($garageArr[$vv['garage_id']]) && !empty($garageArr[$vv['garage_id']])) {
                    $datas[$kk]['garage_num'] = $garageArr[$vv['garage_id']]['garage_num'];
                } else {
                    $whereGarageArr = array(['village_id', '=', $vv['village_id']], ['garage_id', '=', $vv['garage_id']]);
                    $parkingGarage = $houseVillageParkingGarageDb->getOne($whereGarageArr, 'garage_id,garage_num,village_id');
                    if ($parkingGarage && !$parkingGarage->isEmpty()) {
                        $parkingGarage = $parkingGarage->toArray();
                        $datas[$kk]['garage_num'] = $parkingGarage['garage_num'];
                        $garageArr[$vv['garage_id']] = $parkingGarage;
                    }
                }
                $datas[$kk]['residue_time_str'] = '';
                $residue_time = $vv['expire_time'] > 0 ? ($vv['expire_time'] - $nowtime) : 0;
                if ($residue_time > 0) {
                    $datas[$kk]['residue_time_str'] = $this->getDateFormat($residue_time);
                }
            }
            $returnArr['list'] = $datas;
            $returnArr['count'] = $count;
        }
        return $returnArr;
    }

    public function getDateFormat($residue_time = 0)
    {
        $day = 0;
        $hour = 0;
        $minute = 0;
        if ($residue_time >= 86400) {
            $tmpcoun = $residue_time / 86400;
            $day = floor($tmpcoun);
            $residue_time = $residue_time - ($day * 86400);
        }
        if ($residue_time >= 3600) {
            $tmpcoun = $residue_time / 3600;
            $hour = floor($tmpcoun);
            $residue_time = $residue_time - ($hour * 3600);
        }
        if ($residue_time >= 60) {
            $tmpcoun = $residue_time / 60;
            $minute = floor($tmpcoun);
            $residue_time = $residue_time - ($minute * 3600);
        }
        $datetime = '';
        if ($day > 0) {
            $datetime = $day . '天';
        }
        if ($hour > 0) {
            $datetime .= $hour . '小时';
        }
        if ($minute > 0) {
            $datetime .= $minute . '分钟';
        }
        if (empty($datetime)) {
            $datetime = '小于1分钟，即将失效';
        }
        return $datetime;
    }

    public function savePositionSet($village_id = 0, $data = array())
    {
        $houseVillageRobleasePositionOrderDb = new HouseVillageRobleasePositionOrder();
        $whereArr = array('village_id' => $village_id);
        $info = $houseVillageRobleasePositionOrderDb->getOneData($whereArr, '*');
        if ($info && !$info->isEmpty()) {
            $ret = $houseVillageRobleasePositionOrderDb->saveOneData($whereArr, $data);
        } else {
            $data['village_id'] = $village_id;
            $ret = $houseVillageRobleasePositionOrderDb->addOneData($data);
        }
        return $ret;
    }

    public function getPositionOrderDetail($whereArr, $village_id = 0, $isList = 0)
    {
        $houseVillageRobleasePositionOrderDb = new HouseVillageRobleasePositionOrder();
        $info = $houseVillageRobleasePositionOrderDb->getOneData($whereArr, '*');
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
            $info['garage_num'] = '';
            $info['position_num'] = '';
            $info['position_area'] = '';
            $info['position_note'] = '';
            $info['nickname'] = '';
            $info['phone'] = '';
            $houseVillageParkingPositionDb = new HouseVillageParkingPosition();
            $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
            $userDb = new User();
            if ($info['uid']) {
                $userObj = $userDb->getOne(array('uid' => $info['uid']), 'uid,nickname,phone');
                if ($userObj && !$userObj->isEmpty()) {
                    $user = $userObj->toArray();
                    $info['nickname'] = $user['nickname'];
                    $info['phone'] = $user['phone'];
                }
            }
            $statusArr = array('0' => '未办理', '1' => '已办理', '2' => '已失效', '3' => '取消申请', '4' => '取消申请');
            $whereTmpArr = array(['village_id', '=', $village_id], ['position_id', '=', $info['position_id']]);
            $parkingPosition = $houseVillageParkingPositionDb->getFind($whereTmpArr, '*');
            if ($parkingPosition && !$parkingPosition->isEmpty()) {
                $parkingPosition = $parkingPosition->toArray();
                $info['position_num'] = $parkingPosition['position_num'];
                $info['position_area'] = $parkingPosition['position_area'];
                $info['position_note'] = $parkingPosition['position_note'];
                $info['opt_type'] = $parkingPosition['opt_type'];
                $whereGarageArr = array(['village_id', '=', $village_id], ['garage_id', '=', $parkingPosition['garage_id']]);
                $parkingGarage = $houseVillageParkingGarageDb->getOne($whereGarageArr, 'garage_id,garage_num,village_id,garage_position');
                if ($parkingGarage && !$parkingGarage->isEmpty()) {
                    $parkingGarage = $parkingGarage->toArray();
                    $info['garage_num'] = $parkingGarage['garage_num'];
                    $info['garage_position'] = $parkingGarage['garage_position'];
                }
            }
            $xtype_str = '';
            if ($info['xtype'] == 1) {
                $xtype_str = '租赁';
            } else if ($info['xtype'] == 2) {
                $xtype_str = '抢购';
            }
            $info['xtype_str'] = $xtype_str;
            $info['add_time_str'] = date('Y-m-d H:i:s', $info['add_time']);
            $info['opt_time_str'] = $info['opt_time'] > 0 ? date('Y-m-d H:i:s', $info['opt_time']) : '';
            $info['status_str'] = isset($statusArr[$info['status']]) ? $statusArr[$info['status']] : '未知';
            $info['expire_time_str'] = $info['expire_time'] > 0 ? date('Y-m-d H:i:s', $info['expire_time']) : '';
        } else {
            $info = '';
        }
        if ($isList > 0) {
            if (empty($info)) {
                return array('list' => array(),'id'=>0,'order_no'=>'','btn_show'=>0,'xtype'=>'');
            }
            $infoList = array();
            $houseVillageDb = new HouseVillage();
            $whereVillage = array('village_id' => $village_id);
            $houseVillageObj = $houseVillageDb->getInfo($whereVillage, 'village_id,village_name,village_address');
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $infoList[] = array('keyv' => '小区名称', 'value' => $houseVillageObj['village_name']);
            }
            $btn_show=0;
            $infoList[] = array('keyv' => '车库', 'value' => $info['garage_num']);
            $infoList[] = array('keyv' => '车位号', 'value' => $info['position_num']);
            $infoList[] = array('keyv' => '车位面积', 'value' => $info['position_area']);
            $infoList[] = array('keyv' => '备注', 'value' => $info['position_note']);
            $infoList[] = array('keyv' => '订单号', 'value' => $info['order_no']);
            $infoList[] = array('keyv' => '租赁人', 'value' => $info['nickname']);
            $infoList[] = array('keyv' => '手机号', 'value' => $info['phone']);
            $infoList[] = array('keyv' => $info['xtype_str'] . '时间', 'value' => $info['add_time_str']);
            $infoList[] = array('keyv' => '状态', 'value' => $info['status_str']);
            if ($info['status'] > 0 && $info['opt_time_str']) {
                $infoList[] = array('keyv' => '操作时间', 'value' => $info['opt_time_str']);
            }
            if($info['status']<1){
                $btn_show=1;
            }
            return array('list' => $infoList,'id'=>$info['id'],'order_no'=>$info['order_no'],'btn_show'=>$btn_show,'xtype'=>$info['xtype_str']);
        }
        return $info;
    }

    public function saveOneData($whereArr, $data)
    {
        $houseVillageRobleasePositionOrderDb = new HouseVillageRobleasePositionOrder();
        $ret = $houseVillageRobleasePositionOrderDb->saveOneData($whereArr, $data);
        return $ret;
    }

    public function addOneData($data)
    {
        $houseVillageRobleasePositionOrderDb = new HouseVillageRobleasePositionOrder();
        $ret = $houseVillageRobleasePositionOrderDb->addOneData($data);
        return $ret;
    }
    public function handleOrderPosition($type='use',$position_id=0,$village_id=0){
        $houseVillageParkingPositionDb = new HouseVillageParkingPosition();
        $whereArr = array('position_id' => $position_id, 'village_id' => $village_id);
        $fieldStr = 'position_id,garage_id,position_status,opt_type,used_count';
        $positionObj = $houseVillageParkingPositionDb->getFind($whereArr, $fieldStr);
        if($positionObj && !$positionObj->isEmpty()){
            $used_count=$positionObj['used_count'];
            if($type=='use'){
                $used_count=$used_count+1;
            }else if($type=='release'){
                $used_count=$used_count-1;
            }
            $used_count=$used_count>0 ? $used_count:0;
            $saveArr = array('used_count' => $used_count);
            $houseVillageParkingPositionDb->saveOne(array('position_id' => $positionObj['position_id']), $saveArr);
        }
        return $used_count;
    }
    public function addApplyPositionOrder($village_id = 0, $position_id_arr = array(), $otherData = array())
    {
        if ($village_id < 1) {
            throw new \think\Exception("小区ID参数错误！");
        }
        if (empty($position_id_arr)) {
            throw new \think\Exception("请至少勾选一个车位！");
        }
        $xtype = $otherData['xtype'];
        $houseVillageParkingPositionDb = new HouseVillageParkingPosition();
        $houseVillagePositionSetDb = new HouseVillagePositionSet();
        $houseVillageUserBindDb = new HouseVillageUserBind();
        $houseVillageRobleasePositionOrderDb = new HouseVillageRobleasePositionOrder();
        $whereSetArr = array('village_id' => $village_id);
        $infoSetObj = $houseVillagePositionSetDb->getOneData($whereSetArr, '*');
        $infoSet = array();
        if ($infoSetObj && !$infoSetObj->isEmpty()) {
            $infoSet= $infoSetObj->toArray();
            /*
            if($xtype==1 && $infoSet['is_position_lease']<1){
                throw new \think\Exception("很抱歉，车位租赁功能已关闭了！");
            }
            if($xtype==2 && $infoSet['is_position_robbuy']<1){
                throw new \think\Exception("很抱歉，车位抢购功能已关闭了！");
            }
            */
        }
        $positionArr = '';
        $opt_type_str = '';
        if ($xtype == 1) {
            $opt_type_str = '租赁';
        } elseif ($xtype == 2) {
            $opt_type_str = '抢购';
        }
        $fieldStr = 'position_id,garage_id,position_status,opt_type,used_count';
        foreach ($position_id_arr as $position_id) {
            $whereArr = array('position_id' => $position_id, 'village_id' => $village_id, 'opt_type' => $xtype, 'position_status' => 1);
            $positionObj = $houseVillageParkingPositionDb->getFind($whereArr, $fieldStr);
            if ($positionObj && !$positionObj->isEmpty()) {
                $tmpPosition = $positionObj->toArray();
                if ($tmpPosition && $tmpPosition['used_count'] < 1) {
                    $positionArr = $tmpPosition;
                    break;
                }
            }
        }
        $nowtime = time();
        if (empty($positionArr)) {
            return array('err_code' => 1, 'err_msg' => "您选择的车位已经没了，请重新选择车位" . $opt_type_str . "！", 'order_id' => 0, 'order_no' => '');
        } else {
            $addOrderData = $otherData;
            $addOrderData['village_id'] = $village_id;
            $addOrderData['position_id'] = $positionArr['position_id'];
            $addOrderData['garage_id'] = $positionArr['garage_id'];
            $addOrderData['status'] = 0;
            $addOrderData['order_no'] = date('YmdHis') . '000' . rand(100000, 999999);
            $addOrderData['expire_time'] = 0;
            if ($infoSet) {
                $last_day_time = 0;
                if ($xtype == 1 && $infoSet['is_position_lease'] > 0 && $infoSet['last_lease_day'] > 0) {
                    $last_day_time = $nowtime + $infoSet['last_lease_day'] * 24 * 3600;
                } else if ($xtype == 2 && $infoSet['is_position_robbuy'] > 0 && $infoSet['last_robbuy_day'] > 0) {
                    $last_day_time = $nowtime + $infoSet['last_robbuy_day'] * 24 * 3600;
                }
                $addOrderData['expire_time'] = $last_day_time;
                $addOrderData['extra_data'] = json_encode($infoSet, JSON_UNESCAPED_UNICODE);
            }
            if (isset($otherData['bind_pigcms_id']) && ($otherData['bind_pigcms_id'] > 0)) {
                $whereBindArr = array(['village_id', '=', $village_id]);
                $whereBindArr[] = array('pigcms_id', '=', $otherData['bind_pigcms_id']);
                $whereBindArr[] = array('uid', '=', $otherData['uid']);
                $userBindObj = $houseVillageUserBindDb->getOne($whereBindArr, 'pigcms_id,uid,village_id,name,phone,vacancy_id');
                if ($userBindObj && !$userBindObj->isEmpty()) {
                    $addOrderData['vacancy_id'] = $userBindObj['vacancy_id'];
                }
            }
            $addOrderData['add_time'] = $nowtime;
            $ret = $houseVillageRobleasePositionOrderDb->addOneData($addOrderData);
            if ($ret) {
                $used_count = $positionArr['used_count'] + 1;
                $saveArr = array('used_count' => $used_count);
                $houseVillageParkingPositionDb->saveOne(array('position_id' => $positionArr['position_id']), $saveArr);
                return array('err_code' => 0, 'order_id' => $ret, 'order_no' => $addOrderData['order_no'],'err_msg'=>$opt_type_str.'成功！');
            }
            return array('err_code' => 1, 'err_msg' => $opt_type_str . "车位失败了，请重新操作！", 'order_id' => 0, 'order_no' => '');
        }
    }
}
