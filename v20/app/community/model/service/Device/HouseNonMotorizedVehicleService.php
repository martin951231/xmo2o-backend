<?php

namespace app\community\model\service\Device;

use app\community\model\db\Device\HouseNonMotorVehicle;
use app\community\model\db\Device\HouseSmartAccessSensors;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\service\HouseVillageService;
use think\facade\Db;

class HouseNonMotorizedVehicleService
{
    /**
     * @var HouseNonMotorVehicle
     */
    protected $vehicleModel;

    /**
     * @var HouseVillageService
     */
    protected $houseVillageService;

    public function __construct()
    {
        $this->vehicleModel = new HouseNonMotorVehicle();
        $this->houseVillageService = new HouseVillageService();
    }

    public function nonMotorizedVehicleIndex($village_id, $params = [])
    {
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = max(1, min(500, intval($params['pageSize'] ?? 10)));

        $count = $this->buildQuery($village_id, $params)->count('n.id');
        $list = $this->buildQuery($village_id, $params)
            ->field('n.id,n.user_id,n.sensors_id,n.type,n.createdAt,n.updatedAt,u.name as user_name,u.phone as user_phone,u.single_id,u.floor_id,u.layer_id,u.vacancy_id,s.parking_num,s.code as sensor_code')
            ->order('n.id DESC')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        if (empty($list)) {
            $list = [];
        }

        foreach ($list as &$item) {
            $item['room'] = $this->buildRoomText($item, $village_id);
            $item = $this->formatItem($item);
        }

        return [
            'list' => $list,
            'count' => $count,
            'pageSize' => $pageSize,
        ];
    }

    public function nonMotorizedVehicleAdd($village_id, $params = [])
    {
        $data = $this->buildSaveData($village_id, $params);
        $now = date('Y-m-d H:i:s');
        $data['createdAt'] = $now;
        $data['updatedAt'] = $now;
        $id = $this->vehicleModel->add($data);
        return ['id' => $id];
    }

    public function nonMotorizedVehicleEdit($village_id, $params = [])
    {
        $id = intval($params['id'] ?? 0);
        if ($id <= 0) {
            throw new \Exception('映射ID不能为空');
        }

        $detail = $this->getDetail($village_id, $id);
        if (empty($detail)) {
            throw new \Exception('映射信息不存在');
        }

        $data = $this->buildSaveData($village_id, $params, $id);
        $data['updatedAt'] = date('Y-m-d H:i:s');
        $this->vehicleModel->updateThis([['id', '=', $id]], $data);

        return ['id' => $id];
    }

    public function nonMotorizedVehicleDel($village_id, $id)
    {
        $id = intval($id);
        if ($id <= 0) {
            throw new \Exception('映射ID不能为空');
        }

        $detail = $this->getDetail($village_id, $id);
        if (empty($detail)) {
            throw new \Exception('映射信息不存在');
        }

        $this->vehicleModel->where([['id', '=', $id]])->delete();
        return true;
    }

    public function sensorOptions($village_id, $keyword = '', $pageSize = 20)
    {
        $pageSize = max(1, min(20, intval($pageSize)));
        $query = Db::name('house_smart_access_sensors')
            ->where('village_id', '=', intval($village_id))
            ->where('parking_num', '<>', '');

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('code', 'like', '%' . $keyword . '%')
                    ->whereOr('parking_num', 'like', '%' . $keyword . '%');
            });
        }

        $list = $query
            ->field('id,code,parking_num')
            ->order('id DESC')
            ->limit($pageSize)
            ->select()
            ->toArray();

        if (empty($list)) {
            $list = [];
        }

        foreach ($list as &$item) {
            $item = [
                'id' => intval($item['id'] ?? 0),
                'code' => (string)($item['code'] ?? ''),
                'parking_num' => (string)($item['parking_num'] ?? ''),
            ];
        }

        return [
            'list' => $list,
        ];
    }

    public function userOptions($village_id, $keyword = '', $pageSize = 20)
    {
        $pageSize = max(1, min(20, intval($pageSize)));
        $query = Db::name('house_village_user_bind')->alias('u')
            ->leftJoin('house_village_user_vacancy v', 'v.pigcms_id = u.vacancy_id')
            ->where('u.village_id', '=', intval($village_id))
            ->where('u.status', 'in', [0, 1, 2])
            ->where('u.type', 'in', [0, 1, 2, 3]);

        $keyword = trim((string)$keyword);
        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('u.name', 'like', '%' . $keyword . '%')
                    ->whereOr('u.phone', 'like', '%' . $keyword . '%')
                    ->whereOr('v.room', 'like', '%' . $keyword . '%');
            });
        }

        $list = $query
            ->field('u.pigcms_id,u.name,u.phone,u.single_id,u.floor_id,u.layer_id,u.vacancy_id')
            ->order('u.pigcms_id DESC')
            ->limit($pageSize)
            ->select()
            ->toArray();

        if (empty($list)) {
            $list = [];
        }

        foreach ($list as &$item) {
            $item['room'] = $this->buildRoomText($item, $village_id);
            $item = [
                'pigcms_id' => intval($item['pigcms_id'] ?? 0),
                'name' => (string)($item['name'] ?? ''),
                'phone' => (string)($item['phone'] ?? ''),
                'room' => (string)($item['room'] ?? ''),
            ];
        }

        return [
            'list' => $list,
        ];
    }

    protected function buildQuery($village_id, $params = [])
    {
        $query = Db::name('house_non_motor_vehicle')->alias('n')
            ->leftJoin('house_village_user_bind u', 'u.pigcms_id = n.user_id')
            ->leftJoin('house_smart_access_sensors s', 's.id = n.sensors_id AND s.village_id = n.village_id')
            ->leftJoin('house_village_user_vacancy v', 'v.pigcms_id = u.vacancy_id')
            ->where('n.village_id', '=', intval($village_id));

        $parkingNum = trim((string)($params['parking_num'] ?? ''));
        if ($parkingNum !== '') {
            $query->where(function ($subQuery) use ($parkingNum) {
                $subQuery->where('s.parking_num', 'like', '%' . $parkingNum . '%')
                    ->whereOr('s.code', 'like', '%' . $parkingNum . '%')
                    ->whereOr('n.sensors_id', 'like', '%' . $parkingNum . '%');
            });
        }

        if ($this->hasValue($params, 'type')) {
            $query->where('n.type', '=', intval($params['type']));
        }

        $userKeyword = trim((string)($params['user_keyword'] ?? ''));
        if ($userKeyword !== '') {
            $query->where(function ($subQuery) use ($userKeyword) {
                $subQuery->where('u.name', 'like', '%' . $userKeyword . '%')
                    ->whereOr('u.phone', 'like', '%' . $userKeyword . '%')
                    ->whereOr('v.room', 'like', '%' . $userKeyword . '%');
            });
        }

        return $query;
    }

    protected function buildSaveData($village_id, $params = [], $excludeId = 0)
    {
        $type = intval($params['type'] ?? 1);
        if (!in_array($type, [1, 2, 3, 99], true)) {
            throw new \Exception('车辆类型参数错误');
        }

        $userId = intval($params['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new \Exception('请选择用户');
        }

        $userInfo = (new HouseVillageUserBind())->getOne([
            ['pigcms_id', '=', $userId],
            ['village_id', '=', intval($village_id)],
            ['status', 'in', [0, 1, 2]],
            ['type', 'in', [0, 1, 2, 3]],
        ], 'pigcms_id');
        if ($userInfo && !is_array($userInfo)) {
            $userInfo = $userInfo->toArray();
        }
        if (empty($userInfo)) {
            throw new \Exception('所选用户不存在');
        }

        $sensorsId = intval($params['sensors_id'] ?? $params['sensor_id'] ?? 0);
        if ($sensorsId <= 0) {
            throw new \Exception('请选择车牌编号');
        }

        $sensorInfo = (new HouseSmartAccessSensors())->getOne([
            ['id', '=', $sensorsId],
            ['village_id', '=', intval($village_id)],
        ], 'id');
        if ($sensorInfo && !is_array($sensorInfo)) {
            $sensorInfo = $sensorInfo->toArray();
        }
        if (empty($sensorInfo)) {
            throw new \Exception('所选车牌编号不存在');
        }

        $repeatWhere = [
            ['village_id', '=', intval($village_id)],
            ['sensors_id', '=', $sensorsId],
        ];
        if ($excludeId > 0) {
            $repeatWhere[] = ['id', '<>', intval($excludeId)];
        }
        $repeat = $this->vehicleModel->getOne($repeatWhere, 'id');
        if ($repeat && !is_array($repeat)) {
            $repeat = $repeat->toArray();
        }
        if (!empty($repeat)) {
            throw new \Exception('该车牌编号已存在映射，请直接编辑原记录');
        }

        return [
            'village_id' => intval($village_id),
            'user_id' => $userId,
            'sensors_id' => $sensorsId,
            'type' => $type,
        ];
    }

    protected function getDetail($village_id, $id)
    {
        $detail = $this->vehicleModel->getOne([
            ['id', '=', intval($id)],
            ['village_id', '=', intval($village_id)],
        ]);
        if ($detail && !is_array($detail)) {
            $detail = $detail->toArray();
        }
        return $detail ?: [];
    }

    protected function buildRoomText($item, $village_id)
    {
        $vacancyId = intval($item['vacancy_id'] ?? 0);
        if ($vacancyId <= 0) {
            return '';
        }

        return (string)$this->houseVillageService->getSingleFloorRoom(
            intval($item['single_id'] ?? 0),
            intval($item['floor_id'] ?? 0),
            intval($item['layer_id'] ?? 0),
            $vacancyId,
            intval($village_id)
        );
    }

    protected function formatItem($item)
    {
        $item['id'] = intval($item['id'] ?? 0);
        $item['user_id'] = intval($item['user_id'] ?? 0);
        $item['sensors_id'] = intval($item['sensors_id'] ?? 0);
        $item['type'] = intval($item['type'] ?? 0);
        $item['parking_num'] = (string)($item['parking_num'] ?? '');
        $item['sensor_code'] = (string)($item['sensor_code'] ?? '');
        $item['user_name'] = (string)($item['user_name'] ?? '');
        $item['user_phone'] = (string)($item['user_phone'] ?? '');
        $item['room'] = (string)($item['room'] ?? '');
        $item['createdAt'] = (string)($item['createdAt'] ?? '');
        $item['updatedAt'] = (string)($item['updatedAt'] ?? '');
        return $item;
    }

    protected function hasValue($params, $key)
    {
        return isset($params[$key]) && $params[$key] !== '' && $params[$key] !== null;
    }
}