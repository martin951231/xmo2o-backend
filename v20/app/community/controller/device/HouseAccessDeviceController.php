<?php

namespace app\community\controller\device;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\HouseAccessDeviceService;

class HouseAccessDeviceController extends CommunityBaseController
{
    public function device_index()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $params = [
            'page' => $this->request->param('page', 1, 'intval'),
            'pageSize' => $this->request->param('pageSize', 10, 'intval'),
            'serial_num' => $this->request->param('serial_num', '', 'trim'),
            'address' => $this->request->param('address', '', 'trim'),
            'enable' => $this->request->param('enable', '', 'trim'),
        ];

        try {
            $result = (new HouseAccessDeviceService())->deviceIndex($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function device_options()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '璇峰厛鐧诲綍鍒板皬鍖哄悗鍙帮紒');
        }

        try {
            $result = (new HouseAccessDeviceService())->deviceOptions(
                $village_id,
                $this->request->param('pageSize', 500, 'intval')
            );
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function device_add()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $params = [
            'serial_num' => $this->request->param('serial_num', '', 'trim'),
            'address' => $this->request->param('address', '', 'trim'),
            'enable' => $this->request->param('enable', 1, 'intval'),
        ];

        try {
            $result = (new HouseAccessDeviceService())->deviceAdd($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function device_edit()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $params = [
            'id' => $this->request->param('id', 0, 'intval'),
            'serial_num' => $this->request->param('serial_num', '', 'trim'),
            'address' => $this->request->param('address', '', 'trim'),
            'enable' => $this->request->param('enable', 1, 'intval'),
        ];

        try {
            $result = (new HouseAccessDeviceService())->deviceEdit($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function device_del()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $id = $this->request->param('id', 0, 'intval');

        try {
            (new HouseAccessDeviceService())->deviceDel($village_id, $id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, []);
    }
}
