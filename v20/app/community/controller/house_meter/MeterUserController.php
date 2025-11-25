<?php

/**
 * 收费标准管理
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/10 11:47
 */

namespace app\community\controller\house_meter;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterRedisService;
use app\community\model\service\HouseMeterUserService;
use app\community\model\service\HouseMeterPayService;

class MeterUserController extends CommunityBaseController{


    /**
     * 获取设备列表
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param integer $uid 用户uid
     * @param integer $page 页码
     */
    public function deviceList() {
        $uid = $this->request->log_uid;

        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $page = $this->request->param('page','','intval');
        if (empty($page)){
            $page=1;
        }
        $serviceHouseMeter = new HouseMeterUserService();
        $device_list = $serviceHouseMeter->getDeviceList($uid,$page);
        return api_output(0,$device_list);
    }

    /**
     * 获取用户的缴费纪录
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @param integer $page 页码
     */
    public function userPaymentList() {
        $uid = $this->request->log_uid;
        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $electric_id = $this->request->param('electric_id','','intval');
        $page = $this->request->param('page','','intval');
        if (empty($page)){
            $page=1;
        }
        $serviceHouseMeter = new HouseMeterUserService();
        $payment_list = $serviceHouseMeter->getUserPaymentList($uid,$electric_id,$page);
        return api_output(0,$payment_list);
    }

    /**
     * 获取用户的扣费纪录
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @param integer $page 页码
     */
    public function userChargingList() {
        $uid = $this->request->log_uid;
        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $electric_id = $this->request->param('electric_id','','intval');
        $page = $this->request->param('page','','intval');
        if (empty($page)){
            $page=1;
        }
        $serviceHouseMeter = new HouseMeterUserService();
        $charging_list = $serviceHouseMeter->getUserChargingList($uid,$electric_id,$page);
        return api_output(0,$charging_list);
    }

    /**
     * 获取用户的扣费详情
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param integer $uid 用户uid
     * @param integer $id 扣费id
     * @param integer $electric_id 电表id
     */
    public function userChargingInfo() {
        $uid = $this->request->log_uid;

        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $id = $this->request->param('id','','intval');
        $electric_id = $this->request->param('electric_id','','intval');

        $serviceHouseMeter = new HouseMeterUserService();
        $charging_info = $serviceHouseMeter->getUserChargingInfo($uid,$id,$electric_id);
        return api_output(0,$charging_info);
    }


    /**
     * 获取用户实时用电纪录
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param integer $uid 用户uid
     * @param string $time 筛选时间
     * @param integer $electric_id 电表id
     */
    public function meterReadingList() {
        $uid = $this->request->log_uid;
        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $electric_id = $this->request->param('electric_id','','intval');
        $time = $this->request->param('time','','trim');
        if (empty($time)){
            $time=date("Y-m-d");
        }
        $serviceHouseMeter = new HouseMeterUserService();
        $charging_list = $serviceHouseMeter->getMeterReadingList($time,$electric_id);
        return api_output(0,$charging_list);
    }

    /**
     * 获取用户最新的缴费纪录
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     */
    public function paymentLastInfo() {
        $uid = $this->request->log_uid;
        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $electric_id = $this->request->param('electric_id','','intval');
        $serviceHouseMeter = new HouseMeterUserService();
        $charging_list = $serviceHouseMeter->getPaymentLastInfo($uid,$electric_id);
        return api_output(0,$charging_list);
    }

    /**
     * 获取用户当前电表的使用信息
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     */
    public function electricUseInfo() {
        $uid = $this->request->log_uid;
        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $electric_id = $this->request->param('electric_id','','intval');
        if (!$electric_id) {
            return api_output_error(1001,'缺少电表对象');
        }
        $serviceHouseMeter = new HouseMeterUserService();
        $charging_list = $serviceHouseMeter->getElectricUseInfo($uid,$electric_id);
        return api_output(0,$charging_list);
    }


    /**
     * 获取预计充电量
     * @author:zhubaodi
     * @date_time: 2021/4/10 14:49
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @param string $charge_price 充值金额
     */
    public function expectElectric() {
        $uid = $this->request->log_uid;
        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $electric_id = $this->request->param('electric_id','','intval');
        $charge_price = $this->request->param('charge_price','0','trim');
        $arr = [];
        if (!$charge_price) {
            $expect_electric = 0;
            $arr['expect_electric'] = $expect_electric;
            return api_output(0,$arr);
        }else{
            $serviceHouseMeter = new HouseMeterUserService();

            try {
                $expect_electric = $serviceHouseMeter->getExpectElectric($uid,$electric_id,$charge_price);
            } catch (\Exception $e) {
                return api_output_error(-1, $e->getMessage());
            }


            $arr['expect_electric'] = $expect_electric;
            return api_output(0,$arr);
        }

    }


    /**
     * 缴费
     * @author:zhubaodi
     * @date_time: 2021/4/11 10:49
     * @param integer $uid 用户uid
     * @param integer $electric_id 电表id
     * @param string $charge_price 充值金额
     */
    public function orderPay() {


        $uid = $this->request->log_uid;
        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $electric_id = $this->request->param('electric_id','','intval');
        $charge_price = $this->request->param('charge_price','','trim');
        $serviceHouseMeter = new HouseMeterPayService();
        $order_id = $serviceHouseMeter->addOrderInfo($uid,$electric_id,$charge_price);
        $link= cfg('site_url').get_base_url().'pages/pay/check?order_type=house_meter&order_id='.$order_id;
        return api_output(0,$link);
    }

    /**
     * 开关闸
     * @param integer $electric_id 电表id
     * @param string $switch_type open：开闸 close：关闸
     * @author:zhubaodi
     * @date_time: 2021/4/20 14:23
     */
    public function switch()
    {
        $uid = $this->request->log_uid;
        if (!$uid) {
            return api_output_error(1002,'未登陆或者登陆失效');
        }
        $electric_id = $this->request->param('electric_id', '', 'intval');
        if (empty($electric_id)) {
            return api_output(1001, [], '请上传电表id！');
        }
        $switch_type = $this->request->param('switch_type', '', 'trim');
        if (empty($switch_type)) {
            return api_output(1001, [], '请选择开闸或是关闸！');
        }
        $serviceRedis = new HouseMeterRedisService();
        $switch = $serviceRedis->download_switch($electric_id, $switch_type);


        if (is_numeric($switch)) {
            $serviceHouseMeter = new HouseMeterUserService();
            $data=$serviceHouseMeter->getElectricInfo($electric_id);
            $data['disabled']=true;
            return api_output(0, $data, '指令下发成功');
        } else {
            $data['disabled']=false;
            return api_output(1003, $data, '指令下发失败');
        }

    }
}
