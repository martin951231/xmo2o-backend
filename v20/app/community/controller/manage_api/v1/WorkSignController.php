<?php

declare(strict_types=1);
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */

namespace app\community\controller\manage_api\v1;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\workorder\HouseCommunityAboutService;
use app\consts\HouseWorkConst;
use app\consts\WorkOrderConst;

class WorkSignController  extends BaseController
{
    /**
     * 获取当前签到信息.
     */
    public function getSignInfo() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->post('village_id',0);
        !$village_id && $village_id = isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0;
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $is_supply_station = 0;
        if ($jz_open) {
            $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
            if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                $is_supply_station = 1;
            }
        }
        $arr['jz_open'] = $jz_open;
        if ($is_supply_station != 1) {
            return api_output_error(1008,'当前小区工作人员不支持签到');
        }
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        if ($login_role != HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
            return api_output_error(1008,'仅支持工作人员进行签到');
        }
        $login_phone = isset($arr['user']['login_phone']) ? trim($arr['user']['login_phone']) : '';
        $property_id = isset($arr['user']['property_id']) ? intval($arr['user']['property_id']) : 0;
        $result = [];
        try{
            $params = [
                'worker_id' => $worker_id,
                'login_role' => $login_role,
                'village_id' => $village_id,
                'login_phone' => $login_phone,
                'property_id' => $property_id,
                'last_sign_type' => 0,
            ];
            $sign = (new HouseCommunityAboutService())->getSignInfo($params);
            $result['sign'] = $sign;
            if (empty($sign)) {
                // signIn 签到
                $result['signType'] = WorkOrderConst::WORK_ORDER_SIGN_TYPE_IN;
                $result['signText'] = '打卡签到';
            } else {
                // signOut 签退
                $result['signType'] = WorkOrderConst::WORK_ORDER_SIGN_TYPE_OUT;
                $result['signText'] = '打卡签退';
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$result);
    }

    /**
     * 签到或者签退.
     */
    public function workSign() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->post('village_id',0);
        !$village_id && $village_id = isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0;
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $is_supply_station = 0;
        if ($jz_open) {
            $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
            if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                $is_supply_station = 1;
            }
        }
        $arr['jz_open'] = $jz_open;
        if ($is_supply_station != 1) {
            return api_output_error(1008,'当前小区工作人员不支持签到');
        }
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        if ($login_role != HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
            return api_output_error(1008,'仅支持工作人员进行签到');
        }
        $login_phone = isset($arr['user']['login_phone']) ? trim($arr['user']['login_phone']) : '';
        $property_id = isset($arr['user']['property_id']) ? intval($arr['user']['property_id']) : 0;
        $signType = $this->request->post('signType','', 'trim');
        $sign_address = $this->request->post('sign_address','', 'trim');
        $long = $this->request->post('long');
        $lat = $this->request->post('lat');
        $result = [];
        try{
            $params = [
                'worker_id' => $worker_id,
                'login_role' => $login_role,
                'village_id' => $village_id,
                'login_phone' => $login_phone,
                'property_id' => $property_id,
                'signType' => $signType,
                'sign_address' => $sign_address,
                'long' => $long,
                'lat' => $lat,
            ];
            $sign = (new HouseCommunityAboutService())->workSign($params);
            $result['result'] = $sign;
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$result);
    } 
}