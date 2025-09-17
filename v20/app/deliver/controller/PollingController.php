<?php
/*
 * @Descripttion: 
 * @Author: wangchen
 * @Date: 2021-01-22 10:08:11
 * @LastEditors: wangchen
 * @LastEditTime: 2021-03-04 10:29:20
 */

namespace app\deliver\controller;

use app\common\model\service\AppPushMsgService;
use app\deliver\Code;
use app\deliver\model\service\DeliverSupplyService;
use app\deliver\model\service\DeliverUserService;
use Exception;
use map\Coordinate;
use think\Request;

/**
 * 轮询接口
 * @author: 张涛
 * @date: 2020/9/16
 */
class PollingController extends ApiBaseController
{

    /**
     * 获取推送消息
     * @author: 张涛
     * @date: 2020/9/16
     */
    public function pushMessage()
    {
        $deviceId = $this->deviceId;
        $deviceId = str_replace('-', '', $deviceId);
        $appType = $this->request->param('app_type', '', 'trim');
        $businessType = $this->request->param('business_type', '', 'trim');
        $lng = $this->request->param('lng', null);
        $lat = $this->request->param('lat', null);
        $coordinateType = $this->request->param('coordinate_type', 'bd09');
        $uid = $this->request->log_uid;
        try {
            if ((new DeliverUserService())->detectMultiDevicesLogin($uid, $this->deviceId)) {
                return api_output(Code::MULTI_DEVICE_LOGIN, ['message' => "您的账号正在另外一个设备上登录，当前设备已退出。请确认是否本人行为"],"您的账号正在另外一个设备上登录，当前设备已退出。请确认是否本人行为");
            }

            $arr = (new AppPushMsgService())->getAppPushMessage($deviceId, $appType, $businessType);

            //上报位置
            if ($uid > 0 && !is_null($lng) && !is_null($lat) && $lat != 4.9E-324 && $lng != 4.9E-324) {
                if ($coordinateType == 'wgs84') {
                    list($lng, $lat) = Coordinate::wgs84ToBd09($lng, $lat);
                }
                (new DeliverUserService())->reportLocation($uid, $lat, $lng);
            }
            return api_output(0, $arr?: new \stdClass());
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 获取推送消息
     * @author: 汪晨
     * @date: 2021/2/4
     */
    public function pushRewardMessage()
    {
        // print_r($this->request->param());
        $deviceId = $this->deviceId;
        $deviceId = str_replace('-', '', $deviceId);
        $appType = $this->request->param('app_type', '', 'trim');
        $businessType = $this->request->param('business_type', '', 'trim');
        $lng = $this->request->param('lng', null);
        $lat = $this->request->param('lat', null);
        $coordinateType = $this->request->param('coordinate_type', 'bd09');
        $uid = $this->request->log_uid;
        try {
            if ((new DeliverUserService())->detectMultiDevicesLogin($uid, $this->deviceId)) {
                return api_output(Code::MULTI_DEVICE_LOGIN, ['msg' => "您的账号正在另外一个设备上登录，当前设备已退出。请确认是否本人行为"],"您的账号正在另外一个设备上登录，当前设备已退出。请确认是否本人行为");
            }

            $arr = (new AppPushMsgService())->getAppPushRewardMessage($deviceId, $appType, $businessType);

            //上报位置
            if ($uid > 0 && !is_null($lng) && !is_null($lat)) {
                if ($coordinateType == 'wgs84') {
                    list($lng, $lat) = Coordinate::wgs84ToBd09($lng, $lat);
                }
                (new DeliverUserService())->reportLocation($uid, $lat, $lng);
            }
            return api_output(0, $arr?: new \stdClass());
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 获取配送员未读消息
     *
     * @return void
     * @date: 2021/09/01
     */
    public function getImUnReadMsg()
    {
        $supplyId = $this->request->param('supply_id', 0, 'intval');
        $uid = $this->request->log_uid;
        try {
            //$rs = (new DeliverSupplyService)->getImUnReadMsg($uid, $supplyId);
            $rs = ['im_unread_message_count' => (new \app\im\model\ImConversation())->getUnreadMsgCount('deliver_' . $uid, '')];
            return api_output(0, $rs);
        } catch (Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }
}
