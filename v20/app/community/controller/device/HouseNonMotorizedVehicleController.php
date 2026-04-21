<?php

namespace app\community\controller\device;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\HouseNonMotorizedVehicleService;

class HouseNonMotorizedVehicleController extends CommunityBaseController
{
    public function non_motorized_vehicle_index()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $parking_num = $this->request->param('parking_num', '', 'trim');
        if ($parking_num === '') {
            $parking_num = $this->request->param('vehicle_num', '', 'trim');
        }

        $params = [
            'page' => $this->request->param('page', 1, 'intval'),
            'pageSize' => $this->request->param('pageSize', 10, 'intval'),
            'parking_num' => $parking_num,
            'user_keyword' => $this->request->param('user_keyword', '', 'trim'),
            'type' => $this->request->param('type', '', 'trim'),
        ];

        try {
            $result = (new HouseNonMotorizedVehicleService())->nonMotorizedVehicleIndex($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function non_motorized_vehicle_sensor_options()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        try {
            $result = (new HouseNonMotorizedVehicleService())->sensorOptions(
                $village_id,
                $this->request->param('keyword', '', 'trim'),
                $this->request->param('pageSize', 20, 'intval')
            );
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function non_motorized_vehicle_user_options()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        try {
            $result = (new HouseNonMotorizedVehicleService())->userOptions(
                $village_id,
                $this->request->param('keyword', '', 'trim'),
                $this->request->param('pageSize', 20, 'intval')
            );
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function non_motorized_vehicle_add()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $sensors_id = $this->request->param('sensors_id', 0, 'intval');
        if ($sensors_id <= 0) {
            $sensors_id = $this->request->param('sensor_id', 0, 'intval');
        }

        $params = [
            'sensors_id' => $sensors_id,
            'user_id' => $this->request->param('user_id', 0, 'intval'),
            'type' => $this->request->param('type', 1, 'intval'),
        ];

        try {
            $result = (new HouseNonMotorizedVehicleService())->nonMotorizedVehicleAdd($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function non_motorized_vehicle_edit()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $sensors_id = $this->request->param('sensors_id', 0, 'intval');
        if ($sensors_id <= 0) {
            $sensors_id = $this->request->param('sensor_id', 0, 'intval');
        }

        $params = [
            'id' => $this->request->param('id', 0, 'intval'),
            'sensors_id' => $sensors_id,
            'user_id' => $this->request->param('user_id', 0, 'intval'),
            'type' => $this->request->param('type', 1, 'intval'),
        ];

        try {
            $result = (new HouseNonMotorizedVehicleService())->nonMotorizedVehicleEdit($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function non_motorized_vehicle_del()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $id = $this->request->param('id', 0, 'intval');

        try {
            (new HouseNonMotorizedVehicleService())->nonMotorizedVehicleDel($village_id, $id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, []);
    }
}