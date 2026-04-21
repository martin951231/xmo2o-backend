<?php

namespace app\community\controller\device;

use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\HouseAccessRecordsService;

class HouseAccessRecordsController extends CommunityBaseController
{
    public function records_index()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $card_id = $this->request->param('card_id', '', 'trim');
        if ($card_id === '') {
            $card_id = $this->request->param('sensors_id', '', 'trim');
        }
        $serial_id = $this->request->param('serial_id', '', 'trim');
        if ($serial_id === '') {
            $serial_id = $this->request->param('device_id', '', 'trim');
        }
        $mac = $this->request->param('MAC', '', 'trim');
        if ($mac === '') {
            $mac = $this->request->param('mac', '', 'trim');
        }

        $params = [
            'page' => $this->request->param('page', 1, 'intval'),
            'pageSize' => $this->request->param('pageSize', 10, 'intval'),
            'type' => $this->request->param('type', '', 'trim'),
            'reader' => $this->request->param('reader', '', 'trim'),
            'status' => $this->request->param('status', '', 'trim'),
            'card_id' => $card_id,
            'serial_id' => $serial_id,
            'MAC' => $mac,
        ];

        try {
            $result = (new HouseAccessRecordsService())->recordsIndex($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function records_add()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $card_id = $this->request->param('card_id', '', 'trim');
        if ($card_id === '') {
            $card_id = $this->request->param('sensors_id', 0, 'intval');
        } else {
            $card_id = intval($card_id);
        }
        $serial_id = $this->request->param('serial_id', '', 'trim');
        if ($serial_id === '') {
            $serial_id = $this->request->param('device_id', 0, 'intval');
        } else {
            $serial_id = intval($serial_id);
        }
        $mac = $this->request->param('MAC', '', 'trim');
        if ($mac === '') {
            $mac = $this->request->param('mac', '', 'trim');
        }

        $params = [
            'type' => $this->request->param('type', 0, 'intval'),
            'reader' => $this->request->param('reader', 0, 'intval'),
            'status' => $this->request->param('status', 0, 'intval'),
            'card_id' => $card_id,
            'serial_id' => $serial_id,
            'MAC' => $mac,
            'access_time' => $this->request->param('access_time', '', 'trim'),
        ];

        try {
            $result = (new HouseAccessRecordsService())->recordsAdd($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function records_edit()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $card_id = $this->request->param('card_id', '', 'trim');
        if ($card_id === '') {
            $card_id = $this->request->param('sensors_id', 0, 'intval');
        } else {
            $card_id = intval($card_id);
        }
        $serial_id = $this->request->param('serial_id', '', 'trim');
        if ($serial_id === '') {
            $serial_id = $this->request->param('device_id', 0, 'intval');
        } else {
            $serial_id = intval($serial_id);
        }
        $mac = $this->request->param('MAC', '', 'trim');
        if ($mac === '') {
            $mac = $this->request->param('mac', '', 'trim');
        }

        $params = [
            'id' => $this->request->param('id', 0, 'intval'),
            'type' => $this->request->param('type', 0, 'intval'),
            'reader' => $this->request->param('reader', 0, 'intval'),
            'status' => $this->request->param('status', 0, 'intval'),
            'card_id' => $card_id,
            'serial_id' => $serial_id,
            'MAC' => $mac,
            'access_time' => $this->request->param('access_time', '', 'trim'),
        ];

        try {
            $result = (new HouseAccessRecordsService())->recordsEdit($village_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $result);
    }

    public function records_del()
    {
        $village_id = intval($this->adminUser['village_id'] ?? 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $id = $this->request->param('id', 0, 'intval');

        try {
            (new HouseAccessRecordsService())->recordsDel($village_id, $id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, []);
    }
}