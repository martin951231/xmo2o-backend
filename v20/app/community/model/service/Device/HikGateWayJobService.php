<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      海康网关-队列
 */

namespace app\community\model\service\Device;

class HikGateWayJobService
{
    public function jobToMethod($params)
    {
        $handleType = isset($params['handleType']) ? trim($params['handleType']) : '';
        switch ($handleType)
        {
            case 'addDeviceBox':
                (new HikGateWayDeviceService())->addDeviceBox($params);
                break;
            case 'addUsersBox':
                (new HikGateWayDeviceService())->addUsersBox($params);
                break;
            case 'userToAllBelongFaceDevice':
                $pigcms_id = isset($params['pigcms_id']) ? intval($params['pigcms_id']) : 0;
                $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
                (new HikGateWayDeviceService())->userToAllBelongFaceDevice($pigcms_id, $village_id);
                break;
            case 'userToFaceDeviceBox':
                (new HikGateWayDeviceService())->userToFaceDeviceBox($params);
                break;
            case 'addCardBox':
                (new HikGateWayDeviceService())->addCardBox($params);
                break;
            case 'addFaceDataBox':
                (new HikGateWayDeviceService())->addFaceDataBox($params);
                break;
            case 'inParkVisitorToDeviceBox':
                (new HouseDeviceQrcodeService())->inParkVisitorToDeviceBox($params);
                break;
            case 'outParkVisitorToDeviceBox':
                (new HouseDeviceQrcodeService())->outParkVisitorToDeviceBox($params);
                break;
            case 'subscribeDeviceMgmt':
                $deviceInfo = isset($params['deviceInfo']) ? $params['deviceInfo'] : [];
                if (isset($params['deviceInfo'])) {
                    unset($params['deviceInfo']);
                }
                (new HikGateWayDeviceService())->subscribeDeviceMgmt($deviceInfo, $params);
                break;
        }
        return true;
    }
}