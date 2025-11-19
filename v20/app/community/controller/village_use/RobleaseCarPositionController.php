<?php
/**
 * Created by PhpStorm.
 * Author: LiHongShun
 * Date Time: 2023/10/31 14:18
 */

namespace app\community\controller\village_use;
use app\community\controller\UserBaseController;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseVillagePositionSetService;
use app\community\model\service\HouseVillageRobleasePositionOrderService;
use think\App;

class RobleaseCarPositionController extends UserBaseController
{
    
    public function getGarageList()
    {
        $village_id = $this->request->param('village_id', 0, 'intval');
        $type = $this->request->post('type', 0, 'intval');//1加上请选择
        if ($village_id < 1) {
            return api_output_error(1001, '缺少小区ID参数');
        }
        $db_house_new_parking_service = new HouseNewParkingService();
        try {
            $res = $db_house_new_parking_service->getGarageList($village_id, $type);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    public function robCarPositionList()
    {
        $village_id = $this->request->param('village_id', 0, 'intval');
        $garage_id = $this->request->param('garage_id', 0, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $limit = 10;
        $login_uid = $this->request->log_uid;
        if ($login_uid < 1) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        if ($village_id < 1) {
            return api_output_error(1001, '缺少小区ID参数');
        }
        try {
            $opt_type = 2; //2可抢购
            $db_house_new_parking_service = new HouseNewParkingService();
            $res = $db_house_new_parking_service->getRobOrLeaseCarPositionList($village_id, $garage_id, $opt_type, $page, $limit);
            $houseVillagePositionSetService = new HouseVillagePositionSetService();
            $whereArr = array('village_id' => $village_id);
            $positionSet = $houseVillagePositionSetService->getOneData($whereArr, '*');
            $res['opt_position_num'] = 3;
            $res['robbuy_protocol_title'] = '';
            $res['is_protocol_open'] = 0;
            $res['robbuy_protocol'] = '';
            if ($positionSet) {
                $res['opt_position_num'] = $positionSet['opt_position_num'];
                $res['is_protocol_open'] = intval($positionSet['is_protocol_open']);
                $res['robbuy_protocol_title'] = $positionSet['robbuy_protocol_title'];
                $res['robbuy_protocol'] = htmlspecialchars_decode($positionSet['robbuy_protocol'], ENT_QUOTES);
            }
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function leaseCarPositionList()
    {
        $village_id = $this->request->param('village_id', 0, 'intval');
        $garage_id = $this->request->param('garage_id', 0, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $limit = 10;
        $login_uid = $this->request->log_uid;
        if ($login_uid < 1) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        if ($village_id < 1) {
            return api_output_error(1001, '缺少小区ID参数');
        }
        try {
            $opt_type = 1; //1可租赁
            $db_house_new_parking_service = new HouseNewParkingService();
            $res = $db_house_new_parking_service->getRobOrLeaseCarPositionList($village_id, $garage_id, $opt_type, $page, $limit);
            $houseVillagePositionSetService = new HouseVillagePositionSetService();
            $whereArr = array('village_id' => $village_id);
            $positionSet = $houseVillagePositionSetService->getOneData($whereArr, '*');
            $res['opt_position_num'] = 3;
            $res['robbuy_protocol_title'] = '';
            $res['robbuy_protocol'] = '';
            $res['is_protocol_open'] = 0;
            if ($positionSet) {
                $res['opt_position_num'] = $positionSet['opt_position_num'];
                $res['is_protocol_open'] = intval($positionSet['is_protocol_open']);
                $res['robbuy_protocol_title'] = $positionSet['robbuy_protocol_title'];
                $res['robbuy_protocol'] = htmlspecialchars_decode($positionSet['robbuy_protocol'], ENT_QUOTES);
            }
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


    public function myCarPositionorder()
    {
        $village_id = $this->request->param('village_id', 0, 'intval');
        $uid = $this->request->param('uid', 0, 'intval');
        $xtype = $this->request->param('xtype', 0, 'intval');  //1租赁2抢购
        $uid = $this->request->log_uid;
        if ($uid < 1) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $page = $this->request->param('page', 1, 'intval');
        $limit = 10;
        if ($village_id < 1) {
            return api_output_error(1001, '缺少小区ID参数');
        }
        try {
            $houseVillageRobleasePositionOrderService = new HouseVillageRobleasePositionOrderService();
            $whereArr = array();
            $whereArr[] = array('po.village_id', '=', $village_id);
            $whereArr[] = array('po.uid', '=', $uid);
            if(in_array($xtype,array(1,2))){
                $whereArr[] = array('po.xtype', '=', $xtype);
            }
            $res = $houseVillageRobleasePositionOrderService->getConditionListData($whereArr, 'po.*,u.nickname,u.phone', $page, 10);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getPositionOrderDetail()
    {
        $id = $this->request->param('id', 0, 'intval');
        $order_no = $this->request->param('order_no', 0, 'trim');
        $village_id = $this->request->param('village_id', 0, 'intval');
        if ($village_id < 1) {
            return api_output_error(1001, '缺少小区ID参数');
        }
        if ($id < 1 || empty($order_no)) {
            return api_output_error(1001, '缺失ID参数！');
        }
        try {
            $houseVillageRobleasePositionOrderService = new HouseVillageRobleasePositionOrderService();
            $whereArr = array();
            $whereArr[] = array('id', '=', $id);
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('order_no', '=', $order_no);
            $ret = $houseVillageRobleasePositionOrderService->getPositionOrderDetail($whereArr, $village_id, 1);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function cancelPositionOrderApply()
    {
        $id = $this->request->param('id', 0, 'intval');
        $order_no = $this->request->param('order_no', 0, 'trim');
        $village_id = $this->request->param('village_id', 0, 'intval');
        $uid = $this->request->log_uid;
        if ($uid < 1) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        if (empty($village_id)) {
            return api_output_error(1001, '缺少小区ID参数');
        }
        if ($id < 1 || empty($order_no)) {
            return api_output_error(1001, '参数错误，操作失败！');
        }
        try {
            $houseVillageRobleasePositionOrderService = new HouseVillageRobleasePositionOrderService();
            $whereArr = array();
            $whereArr[] = array('id', '=', $id);
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('order_no', '=', $order_no);
            $positionOrder = $houseVillageRobleasePositionOrderService->getOneData($whereArr);
            if(empty($positionOrder)){
                return api_output_error(1001, '数据信息不存在！');
            }
            if($positionOrder['uid']!=$uid){
                return api_output_error(1001, '您不能取消他人的申请数据！');
            }
            if ($positionOrder && $positionOrder['status'] > 0) {
                $err_msg = '订单状态错误！';
                if ($positionOrder['status'] == 1) {
                    $err_msg = '订单已经被办理了！';
                } else if ($positionOrder['status'] == 2) {
                    $err_msg = '订单已经失效了！';
                } else if ($positionOrder['status'] == 3 || $positionOrder['status'] == 4) {
                    $err_msg = '订单已被取消了！';
                }
                return api_output_error(1001, $err_msg);
            }
            $save_data = array('status' => 4, 'opt_time' => time(), 'remark' => '客户取消申请');
            $ret = $houseVillageRobleasePositionOrderService->saveOneData($whereArr, $save_data);
            $releaseret = $houseVillageRobleasePositionOrderService->handleOrderPosition('release',$positionOrder['position_id'],$positionOrder['village_id']);
            return api_output(0, array('order_result'=>$ret,'position_result'=>$releaseret));
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


    public function positionOrderApply()
    {
        $positionids = $this->request->param('positionids', '', 'trim');
        $xtype = $this->request->param('xtype', 0, 'intval');  //1租赁2抢购
        $village_id = $this->request->param('village_id', 0, 'intval');
        $bind_pigcms_id = $this->request->param('pigcms_id', 0, 'intval'); //用户身份id
        $uid = $this->request->param('uid', 0, 'intval');
        $uid = $this->request->log_uid;
        if ($uid < 1) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        if (empty($village_id)) {
            return api_output_error(1001, '缺少小区ID参数');
        }
        if (empty($positionids)) {
            return api_output_error(1001, '请至少勾选一个车位！');
        }
        if(!in_array($xtype,array(1,2))){
            return api_output_error(1001, '类型参数错误，操作失败！');
        }
        $position_id_arr=explode(',',$positionids);
        try {
            $houseVillageRobleasePositionOrderService = new HouseVillageRobleasePositionOrderService();
            $otherData=array('bind_pigcms_id'=>$bind_pigcms_id,'uid'=>$uid,'xtype'=>$xtype);
            $ret=$houseVillageRobleasePositionOrderService->addApplyPositionOrder($village_id,$position_id_arr,$otherData);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}
