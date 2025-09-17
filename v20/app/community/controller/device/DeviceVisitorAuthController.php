<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      设备-访客访问非业主权限
 */

namespace app\community\controller\device;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\HouseDeviceQrcodeService;

class DeviceVisitorAuthController extends CommunityBaseController
{
    /**
     * 获取访客访问非业主设备权限。
     */
    public function getVisitorAuthList()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page = $this->request->post('page', 1);
        $pageSize = $this->request->post('pageSize', 15);
        $result = [];
        try {
            $sHouseDeviceQrcodeService = new HouseDeviceQrcodeService();
            $params = [
                'page' => $page, 'pageSize' => $pageSize, 'village_id' => $village_id
            ];
            $count = $sHouseDeviceQrcodeService->getVisitorAuthCount($params);
            $list = $sHouseDeviceQrcodeService->getVisitorAuthList($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $result['list'] = $list;
        $result['totalRows'] = $count;
        $result['pageSize'] = $pageSize;
        return api_output(0, $result);
    }

    /**
     * 更换开关.
     */
    public function switchChangeOpenAuth() 
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id = $this->request->post('device_id', 1);
        $result = [];
        try {
            $params = [
                'device_id' => $device_id, 'village_id' => $village_id
            ];
            $sHouseDeviceQrcodeService = new HouseDeviceQrcodeService();
            $sHouseDeviceQrcodeService->switchChangeOpenAuth($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }	
}