<?php

namespace app\community\model\service\Device;

use app\community\model\db\Device\HouseSmartAccessDevice;
use app\community\model\db\Device\HouseSmartAccessRecords;
use app\community\model\db\Device\HouseSmartAccessSensorDeviceWhitelist;
use app\community\model\db\Device\HouseSmartAccessSensors;
use think\facade\Db;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HouseAccessSensorsService
{
    /**
     * @var HouseSmartAccessSensors
     */
    protected $sensorModel;

    /**
     * @var HouseSmartAccessSensorDeviceWhitelist
     */
    protected $sensorWhitelistModel;

    /**
     * @var HouseSmartAccessDevice
     */
    protected $deviceModel;

    public function __construct()
    {
        $this->sensorModel = new HouseSmartAccessSensors();
        $this->sensorWhitelistModel = new HouseSmartAccessSensorDeviceWhitelist();
        $this->deviceModel = new HouseSmartAccessDevice();
    }

    public function sensorsList($village_id, $params = [])
    {
        $village_id = intval($village_id);
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = max(1, min(500, intval($params['pageSize'] ?? 10)));

        $list = $this->buildSensorsListQuery($village_id, $params)
            ->field('s.*,n.user_id as bind_user_id,u.name as owner_name,u.phone as owner_phone')
            ->order('s.id DESC')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        if (empty($list)) {
            $list = [];
        }

        $sensorIds = $this->normalizeIds(array_map(function ($item) {
            return intval($item['id'] ?? 0);
        }, $list));
        $aggregateStatusMap = $this->refreshWhitelistAggregateStatus($village_id, $sensorIds);
        $deviceStatusMap = $this->getWhitelistDeviceStatusMap($village_id, $sensorIds);

        foreach ($list as &$item) {
            $sensorId = intval($item['id'] ?? 0);
            if (isset($aggregateStatusMap[$sensorId])) {
                $item['is_add_to_whitelist'] = intval($aggregateStatusMap[$sensorId]);
            }
            $item = array_merge($item, $deviceStatusMap[$sensorId] ?? $this->buildEmptyWhitelistDeviceStatus());
            $item = $this->formatItem($item);
        }

        return [
            'list' => $list,
            'count' => $this->buildSensorsListQuery($village_id, $params)->count('s.id'),
            'pageSize' => $pageSize,
        ];
    }

    public function sensorsAdd($village_id, $params = [])
    {
        $data = $this->buildSaveData($village_id, $params);
        $now = date('Y-m-d H:i:s');
        $data['createdAt'] = $now;
        $data['updatedAt'] = $now;
        $id = $this->sensorModel->add($data);
        $this->refreshWhitelistAggregateStatus(intval($village_id), [$id]);
        return ['id' => $id];
    }

    public function sensorsEdit($village_id, $params = [])
    {
        $id = intval($params['id'] ?? 0);
        if ($id <= 0) {
            throw new \Exception('感应器ID不能为空');
        }

        $where = [
            ['id', '=', $id],
            ['village_id', '=', intval($village_id)],
        ];
        $detail = $this->sensorModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }
        if (empty($detail)) {
            throw new \Exception('感应器信息不存在');
        }

        $data = $this->buildSaveData($village_id, $params, $id);
        if (intval($data['enable'] ?? 0) === 1) {
            $this->assertSensorBindOwner($village_id, $id);
        }
        $data['updatedAt'] = date('Y-m-d H:i:s');
        $shouldResetWhitelist = (
            (string)($detail['code'] ?? '') !== (string)$data['code'] ||
            (string)($detail['parking_num'] ?? '') !== (string)$data['parking_num'] ||
            intval($detail['enable'] ?? 0) !== intval($data['enable'] ?? 0)
        );
        if ($shouldResetWhitelist) {
            $data['is_add_to_whitelist'] = 0;
        }

        $this->sensorModel->updateThis($where, $data);
        if ($shouldResetWhitelist) {
            $this->resetWhitelistDeviceRecordsBySensorIds([$id]);
        }
        $this->refreshWhitelistAggregateStatus(intval($village_id), [$id]);

        return ['id' => $id];
    }

    public function sensorsDel($village_id, $id)
    {
        $id = intval($id);
        if ($id <= 0) {
            throw new \Exception('感应器ID不能为空');
        }

        $where = [
            ['id', '=', $id],
            ['village_id', '=', intval($village_id)],
        ];
        $detail = $this->sensorModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }
        if (empty($detail)) {
            throw new \Exception('感应器信息不存在');
        }

        $recordCount = (new HouseSmartAccessRecords())->getCount([['card_id', '=', $id]]);
        if ($recordCount > 0) {
            throw new \Exception('当前感应器已有出入记录，暂不支持删除');
        }

        $this->resetWhitelistDeviceRecordsBySensorIds([$id]);
        $this->sensorModel->where($where)->delete();
        return true;
    }

    public function sensorsImport($village_id, $file)
    {
        $village_id = intval($village_id);
        if ($village_id <= 0) {
            throw new \Exception('请先登录到小区后台！');
        }
        if (empty($file)) {
            throw new \Exception('请上传导入文件');
        }

        $rows = $this->readImportFileRows($file);
        if (count($rows) <= 1) {
            throw new \Exception('导入文件不能为空');
        }

        $now = date('Y-m-d H:i:s');
        $codeMap = [];
        $validRows = [];
        $failRows = [];
        foreach ($rows as $index => $row) {
            if (intval($index) === 1) {
                continue;
            }
            $rowNumber = intval($index);
            $parking_num = $this->cleanImportCell($row['B'] ?? '');
            $code = $this->cleanImportCell($row['E'] ?? '');
            if ($parking_num === '' && $code === '') {
                continue;
            }
            if ($parking_num === '' || $code === '') {
                $failRows[] = ['row' => $rowNumber, 'msg' => '关联编号或感应器编码为空'];
                continue;
            }
            if (strlen($parking_num) > 50 || strlen($code) > 50) {
                $failRows[] = ['row' => $rowNumber, 'msg' => '关联编号或感应器编码长度不能超过50位'];
                continue;
            }
            if (isset($codeMap[$code])) {
                $failRows[] = ['row' => $rowNumber, 'msg' => '文件内感应器编码重复'];
                continue;
            }
            $codeMap[$code] = true;
            $validRows[] = [
                'row' => $rowNumber,
                'village_id' => $village_id,
                'code' => $code,
                'parking_num' => $parking_num,
                'enable' => 0,
                'createdAt' => $now,
                'updatedAt' => $now,
            ];
        }

        if (empty($validRows)) {
            throw new \Exception('没有可导入的数据');
        }

        $existingCodes = $this->getExistingSensorCodes($village_id, array_column($validRows, 'code'));
        $insertRows = [];
        foreach ($validRows as $row) {
            if (isset($existingCodes[$row['code']])) {
                $failRows[] = ['row' => $row['row'], 'msg' => '感应器编码已存在'];
                continue;
            }
            unset($row['row']);
            $insertRows[] = $row;
        }

        if (empty($insertRows)) {
            throw new \Exception('没有可导入的数据');
        }

        foreach (array_chunk($insertRows, 500) as $chunk) {
            $this->sensorModel->addAll($chunk);
        }

        return [
            'success_count' => count($insertRows),
            'fail_count' => count($failRows),
            'fail_rows' => array_slice($failRows, 0, 50),
        ];
    }

    protected function readImportFileRows($file)
    {
        $filePath = method_exists($file, 'getPathname') ? $file->getPathname() : '';
        if ($filePath === '' || !is_file($filePath)) {
            throw new \Exception('导入文件读取失败');
        }

        $reader = IOFactory::createReaderForFile($filePath);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return is_array($rows) ? $rows : [];
    }

    protected function getExistingSensorCodes($village_id, array $codes)
    {
        $codes = array_values(array_unique(array_filter($codes, function ($code) {
            return $code !== '';
        })));
        if (empty($codes)) {
            return [];
        }

        $existing = [];
        foreach (array_chunk($codes, 500) as $chunk) {
            $list = $this->sensorModel
                ->where([['village_id', '=', intval($village_id)]])
                ->where('code', 'in', $chunk)
                ->column('code');
            foreach ($list as $code) {
                $existing[(string)$code] = true;
            }
        }

        return $existing;
    }

    protected function cleanImportCell($value)
    {
        return trim((string)$value);
    }

    public function getEnabledSensorByCode($code, $village_id = 0)
    {
        $code = trim((string)$code);
        if ($code === '') {
            return [];
        }

        $where = [
            ['code', '=', $code],
            ['enable', '=', 1],
        ];
        if ($village_id > 0) {
            $where[] = ['village_id', '=', intval($village_id)];
        }
        $detail = $this->sensorModel->getOne($where);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }

        return $detail ?: [];
    }

    public function sensorsOptions($village_id, $pageSize = 500)
    {
        $pageSize = max(1, min(500, intval($pageSize)));
        $list = $this->sensorModel
            ->where([['village_id', '=', intval($village_id)]])
            ->field('id,code,parking_num,enable')
            ->order('id DESC')
            ->limit($pageSize)
            ->select();

        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            $list = [];
        }

        foreach ($list as &$item) {
            $item = [
                'id' => intval($item['id'] ?? 0),
                'code' => (string)($item['code'] ?? ''),
                'parking_num' => (string)($item['parking_num'] ?? ''),
                'enable' => intval($item['enable'] ?? 0),
            ];
        }

        return [
            'list' => $list,
        ];
    }

    public function getPendingWhitelistSensors($village_id, $device_id, $limit = 40)
    {
        $village_id = intval($village_id);
        $device_id = intval($device_id);
        $limit = max(1, min(40, intval($limit)));
        if ($village_id <= 0 || $device_id <= 0) {
            return [];
        }

        $successSensorIds = $this->sensorWhitelistModel
            ->where([
                ['village_id', '=', $village_id],
                ['device_id', '=', $device_id],
                ['send_status', '=', 1],
            ])
            ->column('sensor_id');
        $successSensorIds = $this->normalizeIds($successSensorIds);

        $query = $this->sensorModel
            ->where([
                ['village_id', '=', $village_id],
                ['is_add_to_whitelist', '=', 0],
            ])
            ->field('id,code,parking_num,enable,is_add_to_whitelist')
            ->order('id ASC');
        if (!empty($successSensorIds)) {
            $query->where('id', 'not in', $successSensorIds);
        }

        $list = $query->limit($limit)->select();
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            return [];
        }

        $result = [];
        foreach ($list as $item) {
            $code = trim((string)($item['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $normalizedCode = preg_replace('/\D+/', '', $code);
            if ($normalizedCode === '') {
                continue;
            }

            $item['id'] = intval($item['id'] ?? 0);
            $item['code'] = $normalizedCode;
            $item['parking_num'] = trim((string)($item['parking_num'] ?? ''));
            $item['enable'] = intval($item['enable'] ?? 0);
            $item['is_add_to_whitelist'] = intval($item['is_add_to_whitelist'] ?? 0);
            $result[] = $item;
        }

        return $result;
    }

    public function markWhitelistCommandSentForDevice($village_id, $device_id, $device_serial_num, array $sensor_ids, $index_cmd = '', $cmd_value = '')
    {
        $village_id = intval($village_id);
        $device_id = intval($device_id);
        $sensor_ids = $this->normalizeIds($sensor_ids);
        if ($village_id <= 0 || $device_id <= 0 || empty($sensor_ids)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($sensor_ids as $sensor_id) {
            $this->saveWhitelistDeviceRecord($village_id, $device_id, $device_serial_num, $sensor_id, [
                'send_status' => 0,
                'last_result' => 'pending',
                'last_index_cmd' => (string)$index_cmd,
                'last_cmd_value' => (string)$cmd_value,
                'last_send_at' => $now,
                'updatedAt' => $now,
            ]);
        }

        return count($sensor_ids);
    }

    public function markWhitelistResultForDevice($village_id, $device_id, $device_serial_num, array $sensor_ids, $index_cmd = '', $cmd_value = '', $isSuccess = false)
    {
        $village_id = intval($village_id);
        $device_id = intval($device_id);
        $sensor_ids = $this->normalizeIds($sensor_ids);
        if ($village_id <= 0 || $device_id <= 0 || empty($sensor_ids)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($sensor_ids as $sensor_id) {
            $this->saveWhitelistDeviceRecord($village_id, $device_id, $device_serial_num, $sensor_id, [
                'send_status' => $isSuccess ? 1 : 0,
                'last_result' => $isSuccess ? 'success' : 'failed',
                'last_index_cmd' => (string)$index_cmd,
                'last_cmd_value' => (string)$cmd_value,
                'last_result_at' => $now,
                'updatedAt' => $now,
            ]);
        }

        $statusMap = $this->refreshWhitelistAggregateStatus($village_id, $sensor_ids);
        $updatedCount = 0;
        foreach ($sensor_ids as $sensor_id) {
            if (intval($statusMap[$sensor_id] ?? 0) === 1) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    public function refreshWhitelistAggregateStatus($village_id, array $sensor_ids = [])
    {
        $village_id = intval($village_id);
        $sensor_ids = $this->normalizeIds($sensor_ids);
        if ($village_id <= 0) {
            return [];
        }

        if (empty($sensor_ids)) {
            $sensor_ids = $this->normalizeIds(
                $this->sensorModel
                    ->where([['village_id', '=', $village_id]])
                    ->column('id')
            );
        }
        if (empty($sensor_ids)) {
            return [];
        }

        $enabledDevices = $this->getEnabledDevices($village_id);
        $deviceIds = array_keys($enabledDevices);
        $deviceCount = count($deviceIds);
        $successCountMap = [];

        if ($deviceCount > 0) {
            $rows = $this->sensorWhitelistModel
                ->where([
                    ['village_id', '=', $village_id],
                    ['sensor_id', 'in', $sensor_ids],
                    ['device_id', 'in', $deviceIds],
                    ['send_status', '=', 1],
                ])
                ->field('sensor_id,device_id')
                ->select();
            if ($rows && !is_array($rows)) {
                $rows = $rows->toArray();
            }
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $sensorId = intval($row['sensor_id'] ?? 0);
                    if ($sensorId > 0) {
                        $successCountMap[$sensorId] = intval($successCountMap[$sensorId] ?? 0) + 1;
                    }
                }
            }
        }

        $statusMap = [];
        $completedIds = [];
        $pendingIds = [];
        foreach ($sensor_ids as $sensor_id) {
            $isCompleted = $deviceCount > 0 && intval($successCountMap[$sensor_id] ?? 0) >= $deviceCount;
            $statusMap[$sensor_id] = $isCompleted ? 1 : 0;
            if ($isCompleted) {
                $completedIds[] = $sensor_id;
            } else {
                $pendingIds[] = $sensor_id;
            }
        }

        if (!empty($completedIds)) {
            $this->sensorModel->updateThis([
                ['village_id', '=', $village_id],
                ['id', 'in', $completedIds],
                ['is_add_to_whitelist', '=', 0],
            ], [
                'is_add_to_whitelist' => 1,
            ]);
        }
        if (!empty($pendingIds)) {
            $this->sensorModel->updateThis([
                ['village_id', '=', $village_id],
                ['id', 'in', $pendingIds],
                ['is_add_to_whitelist', '=', 1],
            ], [
                'is_add_to_whitelist' => 0,
            ]);
        }

        return $statusMap;
    }

    public function resetWhitelistDeviceRecordsBySensorIds(array $sensor_ids)
    {
        $sensor_ids = $this->normalizeIds($sensor_ids);
        if (empty($sensor_ids)) {
            return 0;
        }

        return $this->sensorWhitelistModel
            ->where([
                ['sensor_id', 'in', $sensor_ids],
            ])
            ->delete();
    }

    public function resetWhitelistDeviceRecordsByDeviceIds(array $device_ids)
    {
        $device_ids = $this->normalizeIds($device_ids);
        if (empty($device_ids)) {
            return 0;
        }

        return $this->sensorWhitelistModel
            ->where([
                ['device_id', 'in', $device_ids],
            ])
            ->delete();
    }

    protected function assertSensorBindOwner($village_id, $sensor_id)
    {
        $bindCount = Db::name('house_non_motor_vehicle')
            ->where('village_id', '=', intval($village_id))
            ->where('sensors_id', '=', intval($sensor_id))
            ->where('user_id', '>', 0)
            ->count('id');
        if (intval($bindCount) <= 0) {
            throw new \Exception('没有绑定业主不能启用');
        }
    }
    protected function buildSaveData($village_id, $params = [], $exclude_id = 0)
    {
        $code = trim((string)($params['code'] ?? ''));
        if ($code === '') {
            throw new \Exception('感应器编码不能为空');
        }
        if (strlen($code) > 50) {
            throw new \Exception('感应器编码长度不能超过50位');
        }

        $parking_num = trim((string)($params['parking_num'] ?? ''));
        if ($parking_num === '') {
            throw new \Exception('关联编号不能为空');
        }
        if (strlen($parking_num) > 50) {
            throw new \Exception('关联编号长度不能超过50位');
        }

        $enable = intval($params['enable'] ?? 1);
        if (!in_array($enable, [0, 1], true)) {
            throw new \Exception('启用状态参数错误');
        }

        $repeatWhere = [
            ['village_id', '=', intval($village_id)],
            ['code', '=', $code],
        ];
        if ($exclude_id > 0) {
            $repeatWhere[] = ['id', '<>', intval($exclude_id)];
        }
        $repeat = $this->sensorModel->getOne($repeatWhere, 'id');
        if ($repeat && !is_array($repeat)) {
            $repeat = $repeat->toArray();
        }
        if (!empty($repeat)) {
            throw new \Exception('该感应器编码已存在');
        }

        return [
            'village_id' => intval($village_id),
            'code' => $code,
            'parking_num' => $parking_num,
            'enable' => $enable,
        ];
    }

    protected function formatItem($item)
    {
        $item['id'] = intval($item['id'] ?? 0);
        $item['enable'] = intval($item['enable'] ?? 0);
        $item['is_add_to_whitelist'] = intval($item['is_add_to_whitelist'] ?? 0);
        $item['bind_user_id'] = intval($item['bind_user_id'] ?? 0);
        $item['code'] = (string)($item['code'] ?? '');
        $item['parking_num'] = (string)($item['parking_num'] ?? '');
        $item['owner_name'] = (string)($item['owner_name'] ?? '');
        $item['owner_phone'] = (string)($item['owner_phone'] ?? '');
        $item['is_bind_owner'] = $item['bind_user_id'] > 0 ? 1 : 0;
        $item['owner_bind_text'] = $this->buildOwnerBindText($item);
        $item['createdAt'] = (string)($item['createdAt'] ?? '');
        $item['updatedAt'] = (string)($item['updatedAt'] ?? '');
        $item['whitelist_device_list'] = is_array($item['whitelist_device_list'] ?? null) ? $item['whitelist_device_list'] : [];
        $item['whitelist_device_names'] = (string)($item['whitelist_device_names'] ?? '');
        $item['whitelist_device_count'] = intval($item['whitelist_device_count'] ?? 0);
        $item['whitelist_target_device_count'] = intval($item['whitelist_target_device_count'] ?? 0);
        $item['whitelist_progress'] = (string)($item['whitelist_progress'] ?? '0/0');
        return $item;
    }

    protected function buildSensorsListQuery($village_id, $params = [])
    {
        $query = Db::name('house_smart_access_sensors')->alias('s')
            ->leftJoin('house_non_motor_vehicle n', 'n.sensors_id = s.id AND n.village_id = s.village_id')
            ->leftJoin('house_village_user_bind u', 'u.pigcms_id = n.user_id AND u.village_id = s.village_id')
            ->where('s.village_id', '=', intval($village_id));

        $code = trim((string)($params['code'] ?? ''));
        if ($code !== '') {
            $query->where('s.code', 'like', '%' . $code . '%');
        }

        $parking_num = trim((string)($params['parking_num'] ?? ''));
        if ($parking_num !== '') {
            $query->where('s.parking_num', 'like', '%' . $parking_num . '%');
        }

        if ($this->hasValue($params, 'enable')) {
            $query->where('s.enable', '=', intval($params['enable']));
        }

        if ($this->hasValue($params, 'is_bind_owner')) {
            if (intval($params['is_bind_owner']) === 1) {
                $query->where('n.user_id', '>', 0);
            } else {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('n.user_id')->whereOr('n.user_id', '=', 0);
                });
            }
        }

        return $query;
    }

    protected function buildOwnerBindText($item)
    {
        if (intval($item['bind_user_id'] ?? 0) <= 0) {
            return '未绑定';
        }

        $ownerName = trim((string)($item['owner_name'] ?? ''));
        $ownerPhone = trim((string)($item['owner_phone'] ?? ''));
        if ($ownerName !== '' && $ownerPhone !== '') {
            return $ownerName . '(' . $ownerPhone . ')';
        }
        if ($ownerName !== '') {
            return $ownerName;
        }
        if ($ownerPhone !== '') {
            return $ownerPhone;
        }
        return '已绑定';
    }

    protected function hasValue($params, $key)
    {
        return isset($params[$key]) && $params[$key] !== '' && $params[$key] !== null;
    }

    protected function normalizeIds($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    protected function saveWhitelistDeviceRecord($village_id, $device_id, $device_serial_num, $sensor_id, array $data = [])
    {
        $village_id = intval($village_id);
        $device_id = intval($device_id);
        $sensor_id = intval($sensor_id);
        if ($village_id <= 0 || $device_id <= 0 || $sensor_id <= 0) {
            return false;
        }

        $where = [
            ['village_id', '=', $village_id],
            ['sensor_id', '=', $sensor_id],
            ['device_id', '=', $device_id],
        ];
        $detail = $this->sensorWhitelistModel->getOne($where, 'id');
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }

        $payload = array_merge([
            'village_id' => $village_id,
            'sensor_id' => $sensor_id,
            'device_id' => $device_id,
            'device_serial_num' => (string)$device_serial_num,
        ], $data);
        $payload['updatedAt'] = (string)($payload['updatedAt'] ?? date('Y-m-d H:i:s'));

        if (empty($detail)) {
            $payload['createdAt'] = (string)($payload['createdAt'] ?? $payload['updatedAt']);
            $this->sensorWhitelistModel->add($payload);
            return true;
        }

        $this->sensorWhitelistModel->updateThis([
            ['id', '=', intval($detail['id'])],
        ], $payload);
        return true;
    }

    protected function getWhitelistDeviceStatusMap($village_id, array $sensor_ids)
    {
        $sensor_ids = $this->normalizeIds($sensor_ids);
        if (intval($village_id) <= 0 || empty($sensor_ids)) {
            return [];
        }

        $enabledDevices = $this->getEnabledDevices($village_id);
        $targetDeviceCount = count($enabledDevices);
        $result = [];
        foreach ($sensor_ids as $sensor_id) {
            $result[$sensor_id] = $this->buildEmptyWhitelistDeviceStatus($targetDeviceCount);
        }
        if (empty($enabledDevices)) {
            return $result;
        }

        $rows = $this->sensorWhitelistModel
            ->where([
                ['village_id', '=', intval($village_id)],
                ['sensor_id', 'in', $sensor_ids],
                ['device_id', 'in', array_keys($enabledDevices)],
                ['send_status', '=', 1],
            ])
            ->field('sensor_id,device_id,device_serial_num')
            ->order('device_id ASC')
            ->select();
        if ($rows && !is_array($rows)) {
            $rows = $rows->toArray();
        }
        if (empty($rows)) {
            return $result;
        }

        foreach ($rows as $row) {
            $sensorId = intval($row['sensor_id'] ?? 0);
            $deviceId = intval($row['device_id'] ?? 0);
            if ($sensorId <= 0 || $deviceId <= 0 || empty($enabledDevices[$deviceId])) {
                continue;
            }
            $device = $enabledDevices[$deviceId];
            $result[$sensorId]['whitelist_device_list'][] = [
                'device_id' => $deviceId,
                'serial_num' => (string)$device['serial_num'],
                'address' => (string)$device['address'],
                'label' => $this->buildDeviceDisplayName($device),
            ];
        }

        foreach ($result as &$item) {
            $labels = [];
            foreach ($item['whitelist_device_list'] as $deviceItem) {
                $labels[] = (string)($deviceItem['label'] ?? '');
            }
            $labels = array_values(array_filter($labels));
            $item['whitelist_device_count'] = count($item['whitelist_device_list']);
            $item['whitelist_target_device_count'] = $targetDeviceCount;
            $item['whitelist_device_names'] = empty($labels) ? '' : implode('，', $labels);
            $item['whitelist_progress'] = $item['whitelist_device_count'] . '/' . $item['whitelist_target_device_count'];
        }

        return $result;
    }

    protected function buildEmptyWhitelistDeviceStatus($targetDeviceCount = 0)
    {
        $targetDeviceCount = intval($targetDeviceCount);
        return [
            'whitelist_device_list' => [],
            'whitelist_device_names' => '',
            'whitelist_device_count' => 0,
            'whitelist_target_device_count' => $targetDeviceCount,
            'whitelist_progress' => '0/' . $targetDeviceCount,
        ];
    }

    protected function getEnabledDevices($village_id)
    {
        $rows = $this->deviceModel
            ->where([
                ['village_id', '=', intval($village_id)],
                ['enable', '=', 1],
            ])
            ->field('id,serial_num,address')
            ->order('id ASC')
            ->select();
        if ($rows && !is_array($rows)) {
            $rows = $rows->toArray();
        }
        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $deviceId = intval($row['id'] ?? 0);
            if ($deviceId <= 0) {
                continue;
            }
            $result[$deviceId] = [
                'id' => $deviceId,
                'serial_num' => (string)($row['serial_num'] ?? ''),
                'address' => (string)($row['address'] ?? ''),
            ];
        }

        return $result;
    }

    protected function buildDeviceDisplayName(array $device)
    {
        $serial = trim((string)($device['serial_num'] ?? ''));
        $address = trim((string)($device['address'] ?? ''));
        if ($address !== '' && $serial !== '') {
            return $address . '(' . $serial . ')';
        }
        if ($address !== '') {
            return $address;
        }
        return $serial;
    }
}
