<?php

namespace app\community\controller\device;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\HouseAccessSensorsService;

class HouseAccessSensorsController extends CommunityBaseController
{
    public function sensors_list()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $params = [
            'page' => $this->request->param('page', 1, 'intval'),
            'pageSize' => $this->request->param('pageSize', 10, 'intval'),
            'code' => $this->request->param('code', '', 'trim'),
            'parking_num' => $this->request->param('parking_num', '', 'trim'),
            'enable' => $this->request->param('enable', '', 'trim'),
        ];

        try {
            $result = (new HouseAccessSensorsService())->sensorsList($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function sensors_options()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '璇峰厛鐧诲綍鍒板皬鍖哄悗鍙帮紒');
        }

        try {
            $result = (new HouseAccessSensorsService())->sensorsOptions(
                $village_id,
                $this->request->param('pageSize', 500, 'intval')
            );
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function sensors_add()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $params = [
            'code' => $this->request->param('code', '', 'trim'),
            'parking_num' => $this->request->param('parking_num', '', 'trim'),
            'enable' => $this->request->param('enable', 1, 'intval'),
        ];

        try {
            $result = (new HouseAccessSensorsService())->sensorsAdd($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function sensors_edit()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $params = [
            'id' => $this->request->param('id', 0, 'intval'),
            'code' => $this->request->param('code', '', 'trim'),
            'parking_num' => $this->request->param('parking_num', '', 'trim'),
            'enable' => $this->request->param('enable', 1, 'intval'),
        ];

        try {
            $result = (new HouseAccessSensorsService())->sensorsEdit($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function sensors_del()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $id = $this->request->param('id', 0, 'intval');

        try {
            (new HouseAccessSensorsService())->sensorsDel($village_id, $id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, []);
    }
}
