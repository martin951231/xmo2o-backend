<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      海康云眸-企业内部应用开发-社区
 */

namespace app\community\model\service\Device;

use app\community\model\db\Device\HikInfoVisionCommunityRegions;
use app\community\model\db\HouseDeviceChannel;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseFaceImg;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillage;
use app\consts\DeviceConst;
use app\consts\HikConst;
use app\traits\house\DeviceTraits;
use face\hikvision\infoVisionICommunity;
use app\traits\HikInfoVisionICommunityTraits;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageUserBind;
use face\wordencryption;

class HikInfoVisionICommunityService
{
    use HikInfoVisionICommunityTraits,DeviceTraits;

    /**
     * 获取根组织 然后获取其下属组织或小区.
     */
    public function getICommunityRegions()
    {
        $infoVisionICommunity = new infoVisionICommunity();
        $config = $infoVisionICommunity->getConfig();
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $where = [];
        $where[] = ['app_key', '=', $config['app_key']];
        $where[] = ['from', '=', 'root'];
        $where[] = ['index_code', '<>', ''];
        $rootOrgInfo = $dbHikInfoVisionCommunityRegions->getOne($where, 'id, index_code, name, parent_index_code, region_extra');
        if ($rootOrgInfo && !is_array($rootOrgInfo)) {
            $rootOrgInfo = $rootOrgInfo->toArray();
        }
        $indexCode = isset($rootOrgInfo['index_code']) && $rootOrgInfo['index_code'] ? $rootOrgInfo['index_code'] : '';
        $region_extra = isset($rootOrgInfo['region_extra']) && $rootOrgInfo['region_extra'] ? json_decode($rootOrgInfo['region_extra'], true) : [];
        if (!$indexCode) {
            $rootOrg = $infoVisionICommunity->getRegionsOrg();
            if (isset($rootOrg['data'])) {
                $data = $rootOrg['data'];
                $indexCode = isset($data['indexCode']) && $data['indexCode'] ? $data['indexCode'] : 'root000000';
                $name = isset($data['name']) && $data['name'] ? $data['name'] : '根节点';
                $parentIndexCode = isset($data['parentIndexCode']) && $data['parentIndexCode'] ? $data['parentIndexCode'] : '-1';
                $region_extra_info = unsetParam($data, ['indexCode', 'name', 'parentIndexCode']);
                $region_extra = array_merge($region_extra, $region_extra_info);
                $data = [
                    'app_key' => $config['app_key'],
                    'from' => 'root',
                    'index_code' => $indexCode,
                    'name' => $name,
                    'parent_index_code' => $parentIndexCode,
                    'region_extra' => json_encode($region_extra, JSON_UNESCAPED_UNICODE),
                    'available' => 0,
                    'leaf' => 0,
                ];
                if (isset($rootOrgInfo['id']) && $rootOrgInfo['id']) {
                    $data['update_time'] = time();
                    $dbHikInfoVisionCommunityRegions->updateThis(['id' => $rootOrgInfo['id']], $data);
                } else {
                    $data['add_time'] = time();
                    $dbHikInfoVisionCommunityRegions->add($data);
                }
            }
        }
        if (!$indexCode) {
            return [];
        }
        $result = $this->handleRootChild($indexCode, 1, 100);
        return $result;
    }

    /**
     * 获取某个区域或小区或楼栋下的区域信息
     */
    public function handleRootChild($indexCode, $pageNo = 1, $pageSize = 100, $source = 'root')
    {
        // 直接查询下一级
        $infoVisionICommunity = new infoVisionICommunity();
        $config = $infoVisionICommunity->getConfig();
        $app_key = $config['app_key'];
        $parentIndexCodes = [$indexCode];
        $resourceType = 'region';
        $isSubRegion = false;
        $result = $infoVisionICommunity->getRegionNodesByParams($parentIndexCodes, $resourceType, $pageNo, $pageSize, $isSubRegion);
        if (isset($result['data']['list'])) {
            $list = $result['data']['list'];
            $data = [];
            foreach ($list as $item) {
                $param = $this->handleSingleRegion($item, $app_key, $source);
                $data[] = $param;
                if (isset($param['childSource']) && $param['childSource'] && isset($param['index_code']) && $param['index_code']) {
                    // todo 走队列
                    $datas = $this->handleRootChild($param['index_code'], 1, 100, $param['childSource']);
                    $data = array_merge($data, $datas);
                }
            }
            fdump_api(['app_key' => $app_key, 'source' => $source, 'data' => $data], 'infoVisionICommunity/handleRootChildLog',1);
        }
        $total = isset($result['data']['total']) && intval($result['data']['total']) ? intval($result['data']['total']) : 0;
        if ($total > ($pageNo * $pageSize)) {
            $pageNo = $pageNo + 1;
            // todo 转成队列执行
            return $this->handleRootChild($indexCode, $pageNo, $pageSize);
        }
        return $data;
    }

    /**
     * 整理记录相关区域信息.
     */
    public function handleSingleRegion($region, $app_key, $source = 'root', $param = [])
    {
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $index_code = isset($region['indexCode']) && $region['indexCode'] ? $region['indexCode'] : '';
        $name = isset($region['name']) && $region['name'] ? $region['name'] : '';
        $region_path = isset($region['regionPath']) && $region['regionPath'] ? $region['regionPath'] : '';
        $parent_index_code = isset($region['parentIndexCode']) && $region['parentIndexCode'] ? $region['parentIndexCode'] : '';
        $cascade_type = isset($region['cascadeType']) ? intval($region['cascadeType']) : 0;
        $sort = isset($region['sort']) && $region['sort'] ? $region['sort'] : '';
        $region_create_time = isset($region['createTime']) && $region['createTime'] ? $region['createTime'] : '';
        $region_update_time = isset($region['updateTime']) && $region['updateTime'] ? $region['updateTime'] : '';
        $region_code = isset($region['regionCode']) && $region['regionCode'] ? $region['regionCode'] : '';
        $available = isset($region['available']) ? $region['available'] : false;
        $available = $available ? 1 : 0;
        $leaf = isset($region['leaf']) ? $region['leaf'] : false;
        $leaf = $leaf ? 1 : 0;
        $catalog_type = isset($region['catalogType']) ? intval($region['catalogType']) : 13;
        $from = 'org';
        $data = [];
        $parentRegionCode = isset($param['parentRegionCode']) && $param['parentRegionCode'] ? $param['parentRegionCode'] : '';
        $term = isset($param['term']) && $param['term'] ? $param['term'] : '';
        $build_no = isset($param['build_no']) && $param['build_no'] ? $param['build_no'] : '';
        $unit_no = isset($param['unit_no']) && $param['unit_no'] ? $param['unit_no'] : '';
        $community_index_code = isset($param['community_index_code']) && $param['community_index_code'] ? $param['community_index_code'] : '';
        $build_index_code = isset($param['build_index_code']) && $param['build_index_code'] ? $param['build_index_code'] : '';
        $unit_index_code = isset($param['unit_index_code']) && $param['unit_index_code'] ? $param['unit_index_code'] : '';
        $childSource = '';
        switch ($source) {
            case 'root':
            case 'org':
                // 从根目录和组织获取下级的信息中 catalogType=12 的为小区
                $childSource = 'org';
                if ($catalog_type === 12) {
                    $from = 'house';
                    $childSource = 'house';
                    $community_index_code = $index_code;
                }
                break;
            case 'house':
                // 从小区获取下级的信息中 catalogType=12 的为小区期数
                if ($catalog_type === 12) {
                    $from = 'term';
                    if (!$parentRegionCode || !$community_index_code) {
                        $where = [];
                        $where[] = ['app_key', '=', $app_key];
                        $where[] = ['from', '=', $source];
                        $where[] = ['index_code', '=', $parent_index_code];
                        $parentInfo = $dbHikInfoVisionCommunityRegions->getOne($where, 'id, region_code, index_code, community_index_code');
                        !$parentRegionCode && $parentRegionCode = isset($parentInfo['region_code']) && trim($parentInfo['region_code']) ? trim($parentInfo['region_code']) : '';
                        !$community_index_code && $community_index_code = isset($parentInfo['index_code']) && trim($parentInfo['index_code']) ? trim($parentInfo['index_code']) : '';
                        !$community_index_code && $community_index_code = isset($parentInfo['community_index_code']) && trim($parentInfo['community_index_code']) ? trim($parentInfo['community_index_code']) : '';
                    }
                    if (!$term && $parentRegionCode && $region_code) {
                        $start_region_code = rtrim(strval($region_code), '0');
                        $start_region_code = 'start' . $start_region_code;
                        $term = str_replace('start' . $parentRegionCode, '', $start_region_code);
                    }
                    if (strlen($term) == 1) {
                        //如果是 10，上面会处理成1 其实应该是10
                        $term = $term . '0';
                    }
                }
                $childSource = 'term';
                break;
            case 'term':
                // 从小区期数获取下级的信息中 catalogType=12 的为楼栋
                if ($catalog_type === 12) {
                    $from = 'build';
                    $build_index_code = $index_code;
                    if (!$parentRegionCode || !$term || !$community_index_code) {
                        $where = [];
                        $where[] = ['app_key', '=', $app_key];
                        $where[] = ['from', '=', $source];
                        $where[] = ['index_code', '=', $parent_index_code];
                        $parentInfo = $dbHikInfoVisionCommunityRegions->getOne($where, 'id, region_code, term, community_index_code');
                        !$parentRegionCode && $parentRegionCode = isset($parentInfo['region_code']) && trim($parentInfo['region_code']) ? trim($parentInfo['region_code']) : '';
                        !$term && $term = isset($parentInfo['term']) && trim($parentInfo['term']) ? trim($parentInfo['term']) : '';
                        !$community_index_code && $community_index_code = isset($parentInfo['community_index_code']) && trim($parentInfo['community_index_code']) ? trim($parentInfo['community_index_code']) : '';
                    }
                    if ($parentRegionCode && $region_code) {
                        $parentRegionCode = rtrim(strval($parentRegionCode), '0');
                        $start_region_code = rtrim(strval($region_code), '0');
                        $start_region_code = 'start' . $start_region_code;
                        $build_no = str_replace('start' . $parentRegionCode, '', $start_region_code);
                        if (strlen($build_no) == 2) {
                            //如果是 20，上面会处理成02 其实应该是020
                            $build_no = $build_no . '0';
                        } elseif (strlen($build_no) == 1) {
                            $build_no = $build_no . '00';
                        }
                    }
                }
                $childSource = 'build';
                break;
            case 'build':
                // 从楼栋获取下级的信息中 catalogType=12 的为单元
                if ($catalog_type === 12) {
                    $from = 'unit';
                    $unit_index_code = $index_code;
                    if (!$parentRegionCode || !$build_no || !$community_index_code || !$build_index_code) {
                        $where = [];
                        $where[] = ['app_key', '=', $app_key];
                        $where[] = ['from', '=', $source];
                        $where[] = ['index_code', '=', $parent_index_code];
                        $parentInfo = $dbHikInfoVisionCommunityRegions->getOne($where, 'id, region_code, term, build_no, community_index_code, build_index_code');
                        !$parentRegionCode && $parentRegionCode = isset($parentInfo['region_code']) && trim($parentInfo['region_code']) ? trim($parentInfo['region_code']) : '';
                        !$term && $term = isset($parentInfo['term']) && trim($parentInfo['term']) ? trim($parentInfo['term']) : '';
                        !$build_no && $build_no = isset($parentInfo['build_no']) && trim($parentInfo['build_no']) ? trim($parentInfo['build_no']) : '';
                        !$community_index_code && $community_index_code = isset($parentInfo['community_index_code']) && trim($parentInfo['community_index_code']) ? trim($parentInfo['community_index_code']) : '';
                        !$build_index_code && $build_index_code = isset($parentInfo['build_index_code']) && trim($parentInfo['build_index_code']) ? trim($parentInfo['build_index_code']) : '';
                    }
                    if ($parentRegionCode && $region_code) {
                        $parentRegionCode = rtrim(strval($parentRegionCode), '0');
                        $start_region_code = rtrim(strval($region_code), '0');
                        $start_region_code = 'start' . $start_region_code;
                        $unit_no = str_replace('start' . $parentRegionCode, '', $start_region_code);
                        if (strlen($unit_no) == 1) {
                            //如果是 10，上面会处理成1 其实应该是10
                            $unit_no = $unit_no . '0';
                        }
                    }
                }
                break;
        }
        $term && $data['term'] = $term;
        $build_no && $data['build_no'] = $build_no;
        $unit_no && $data['unit_no'] = $unit_no;
        $community_index_code && $data['community_index_code'] = $community_index_code;
        $build_index_code && $data['build_index_code'] = $build_index_code;
        $unit_index_code && $data['unit_index_code'] = $unit_index_code;

        $data['name'] = $name;
        $data['region_path'] = $region_path;
        $data['parent_index_code'] = $parent_index_code;
        $data['cascade_type'] = $cascade_type;
        $data['sort'] = $sort;
        $data['region_create_time'] = $region_create_time;
        $data['region_update_time'] = $region_update_time;
        $data['region_code'] = $region_code;
        $data['available'] = $available;
        $data['leaf'] = $leaf;
        $data['catalog_type'] = $catalog_type;
        $data['app_key'] = $app_key;
        $data['from'] = $from;
        $data['index_code'] = $index_code;
        $where = [];
        $where[] = ['app_key', '=', $app_key];
        $where[] = ['from', '=', $from];
        $where[] = ['index_code', '=', $index_code];
        $orgOrHouseInfo = $dbHikInfoVisionCommunityRegions->getOne($where, 'id, index_code, name, parent_index_code, region_extra');
        if (isset($orgOrHouseInfo['id']) && $orgOrHouseInfo['id']) {
            $data['update_time'] = time();
            $dbHikInfoVisionCommunityRegions->updateThis(['id' => $orgOrHouseInfo['id']], $data);
        } else {
            $data['add_time'] = time();
            $dbHikInfoVisionCommunityRegions->add($data);
        }
        $data['childSource'] = $childSource;
        return $data;
    }

    public function laterQueueGetRegionsHouses($village_id = 0)
    {
        $retInfo = $this->traitCommonHikInfoVisionCommunity(['village_id' => $village_id, 'handle_type' => 'getRegionsHouses']);
        return array('retInfo' => $retInfo);
    }

    public function getRegionsHousesOnQueue($data = array())
    {
        $village_id = 0;
        if (isset($data['village_id'])) {
            $village_id = $data['village_id'];
        }
        $root = dirname(dirname(dirname(__DIR__)));
        $root = dirname(dirname(dirname($root)));
        $basePath = rtrim($root, '/');
        $_SERVER['DOCUMENT_ROOT'] = $basePath;
        $this->getRegionsHouses(1, 1000, $village_id);
    }

    public function getRegionsHouses($pageNo = 1, $pageSize = 1000, $village_id = 0)
    {
        $infoVisionICommunity = new infoVisionICommunity();
        $config = $infoVisionICommunity->getConfig();
        $app_key = $config['app_key'];
        $result = $infoVisionICommunity->getRegionsHouses($pageNo, $pageSize);
        if (isset($result['data']['list'])) {
            $list = $result['data']['list'];
            $data = [];
            foreach ($list as $item) {
                $param = $this->handleSingleHouse($item, $app_key, $village_id);
                $data[] = $param;
            }
            fdump_api(['app_key' => $app_key, 'data' => $data, 'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT']], 'infoVisionICommunity/getRegionsHousesLog',1);
        }
        $total = isset($result['data']['total']) && intval($result['data']['total']) ? intval($result['data']['total']) : 0;
        if ($total > ($pageNo * $pageSize)) {
            $pageNo = $pageNo + 1;
            return $this->getRegionsHouses($pageNo, $pageSize, $village_id);
        }
        return $result;
    }

    /**
     * 单个处理房间.
     */
    public function handleSingleHouse($house, $app_key, $village_id = 0)
    {
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $from = 'room';
        $index_code = isset($house['houseIndexCode']) && $house['houseIndexCode'] ? $house['houseIndexCode'] : '';
        $gate_house_no = isset($house['gateHouseNo']) && $house['gateHouseNo'] ? $house['gateHouseNo'] : '';
        $name = isset($house['houseName']) && $house['houseName'] ? $house['houseName'] : $gate_house_no;
        $living_type = isset($house['livingType']) && $house['livingType'] ? $house['livingType'] : '';
        $address = isset($house['address']) && $house['address'] ? $house['address'] : '';
        $floor_no = isset($house['floor']) && $house['floor'] ? $house['floor'] : '';
        $unit_no = isset($house['unit']) && $house['unit'] ? $house['unit'] : '';
        $build_no = isset($house['buildingNo']) && $house['buildingNo'] ? $house['buildingNo'] : '';
        $term = isset($house['term']) && $house['term'] ? $house['term'] : '';
        $community_index_code = isset($house['communityIndexCode']) && $house['communityIndexCode'] ? $house['communityIndexCode'] : '';

        $termArr = [];
        $termArr[] = $term;
        $termArr[] = strval(intval($term));
        $termArr[] = '0' . $term;
        $termArr[] = '00' . $term;
        $termArr = array_values(array_unique($termArr));
        $buildNoArr = [];
        $buildNoArr[] = $build_no;
        $buildNoArr[] = strval(intval($build_no));
        $buildNoArr[] = '0' . $build_no;
        $buildNoArr[] = '00' . $build_no;
        $buildNoArr = array_values(array_unique($buildNoArr));
        $unitNoArr = [];
        $unitNoArr[] = $unit_no;
        $unitNoArr[] = strval(intval($unit_no));
        $unitNoArr[] = '0' . $unit_no;
        $unitNoArr[] = '00' . $unit_no;
        $unitNoArr = array_values(array_unique($unitNoArr));
        // 查找单元
        $where = [];
        $where[] = ['app_key', '=', $app_key];
        $where[] = ['from', '=', 'unit'];
        $where[] = ['community_index_code', '=', $community_index_code];
        $where[] = ['term', 'in', $termArr];
        $where[] = ['build_no', 'in', $buildNoArr];
        $where[] = ['unit_no', 'in', $unitNoArr];
        $unitInfo = $dbHikInfoVisionCommunityRegions->getOne($where);
        if ($unitInfo && !is_array($unitInfo)) {
            $unitInfo = $unitInfo->toArray();
        }
        if (empty($unitInfo) || empty($community_index_code)) {
            // 没有归属单元的不记录
            return [];
        }
        $houseWhere = array();
        $houseWhere[] = ['app_key', '=', $app_key];
        $houseWhere[] = ['from', '=', 'house'];
        $houseWhere[] = ['index_code', '=', $community_index_code];
        $houseInfo = $dbHikInfoVisionCommunityRegions->getOne($houseWhere);
        if ($houseInfo && !$houseInfo->isEmpty() && $houseInfo['village_id'] == $village_id) {
            //已绑定
        } else {
            //未绑定
            $village_id = 0;
        }
        fdump_api(['where' => $where, 'unitInfo' => $unitInfo, 'village_id' => $village_id], 'infoVisionICommunity/handleSingleHouse',1);
        $data = [];
        $data['app_key'] = $app_key;
        $data['from'] = $from;
        $data['index_code'] = $index_code;
        $data['name'] = $name;
        $data['parent_index_code'] = isset($unitInfo['index_code']) && trim($unitInfo['index_code']) ? trim($unitInfo['index_code']) : '';
        if (isset($unitInfo['region_path']) && trim($unitInfo['region_path']) && $index_code) {
            $data['region_path'] = trim($unitInfo['region_path']) . $index_code . '@';
        }
        $data['cascade_type'] = 0;
        $data['catalog_type'] = 12;
        if (isset($unitInfo['region_code']) && trim($unitInfo['region_code']) && $floor_no && $gate_house_no) {
            $house_no = substr($gate_house_no, -2);
            $data['region_code'] = trim($unitInfo['region_code']) . $floor_no . $house_no;
        }
        $data['community_index_code'] = $community_index_code;
        $data['build_index_code'] = isset($unitInfo['build_index_code']) && trim($unitInfo['build_index_code']) ? trim($unitInfo['build_index_code']) : '';
        $data['unit_index_code'] = isset($unitInfo['unit_index_code']) && trim($unitInfo['unit_index_code']) ? trim($unitInfo['unit_index_code']) : '';
        $data['house_index_code'] = $index_code;
        $data['gate_house_no'] = $gate_house_no;
        $data['floor_no'] = $floor_no;
        $data['unit_no'] = $unit_no;
        $data['build_no'] = $build_no;
        $data['living_type'] = $living_type;
        $data['address'] = $address;
        $data['term'] = $term;
        $whereRoom = [];
        $whereRoom[] = ['app_key', '=', $app_key];
        $whereRoom[] = ['from', '=', $from];
        $whereRoom[] = ['index_code', '=', $index_code];
        $fieldStr = 'id, index_code, name, parent_index_code,village_id,single_id,floor_id,layer_id,room_id';
        $single_id = 0;
        $floor_id = 0;
        $layer_id = 0;
        $room_id = 0;
        $id = 0;
        if ($village_id > 0) {
            $data['village_id'] = $village_id;
        }
        $orgOrHouseInfo = $dbHikInfoVisionCommunityRegions->getOne($whereRoom, $fieldStr);
        if (isset($orgOrHouseInfo['id']) && $orgOrHouseInfo['id']) {
            $data['update_time'] = time();
            $dbHikInfoVisionCommunityRegions->updateThis(['id' => $orgOrHouseInfo['id']], $data);
            $single_id = $orgOrHouseInfo['single_id'];
            $floor_id = $orgOrHouseInfo['floor_id'];
            $layer_id = $orgOrHouseInfo['layer_id'];
            $room_id = $orgOrHouseInfo['room_id'];
            $id = $orgOrHouseInfo['id'];
        } else {
            $data['add_time'] = time();
            $id = $dbHikInfoVisionCommunityRegions->add($data);
        }
        if ($village_id > 0) {
            //处理房间
            $data['id'] = $id;
            $data['single_id'] = $single_id;
            $data['floor_id'] = $floor_id;
            $data['layer_id'] = $layer_id;
            $data['room_id'] = $room_id;
            $data['floor_name'] = $unitInfo['name'];
            $this->saveHouseVillageUserVacancy($village_id, $data);
        }
        return $unitInfo;
    }

    public function saveHouseVillageUserVacancy($village_id = 0, $data = array())
    {
        fdump_api(['village_id' => $village_id, 'data' => $data], 'infoVisionICommunity/saveHouseVillageUserVacancy', 1);
        $houseVillageUserVacancyDb = new HouseVillageUserVacancy();
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $is_new_room = true;
        if ($data['room_id'] > 0 && $data['layer_id'] > 0 && $data['floor_id'] > 0 && $data['single_id'] > 0) {
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $data['room_id']);
            $whereArr[] = array('layer_id', '=', $data['layer_id']);
            $whereArr[] = array('floor_id', '=', $data['floor_id']);
            $whereArr[] = array('single_id', '=', $data['single_id']);
            $vacancyData = $houseVillageUserVacancyDb->getOne($whereArr);
            if ($vacancyData && !$vacancyData->isEmpty()) {
                $vacancyData = $vacancyData->toArray();
                if ($village_id == $vacancyData['village_id']) {
                    $saveRoomArr = array('third_room_id' => $data['index_code']);
                    $houseVillageUserVacancyDb->saveOne(['pigcms_id' => $vacancyData['pigcms_id']], $saveRoomArr);
                    $is_new_room = false;
                } else if ($village_id != $vacancyData['village_id']) {
                    /*
                    $saveData=array('village_id'=>$village_id,'single_id'=>0,'floor_id'=>0,'layer_id'=>0,'room_id'=>0);
                    $dbHikInfoVisionCommunityRegions->updateThis(['id' => $data['id']], $saveData);
                    */
                    $houseVillageUserVacancyDb->saveOne(['pigcms_id' => $vacancyData['pigcms_id']], ['third_room_id' => '']);
                }
            }
        }
        $nowtime = time();
        if ($is_new_room && $data['build_index_code']) {
            $houseVillageSingleDb = new HouseVillageSingle();
            $houseVillageFloorDb = new HouseVillageFloor();
            $houseVillageLayerDb = new HouseVillageLayer();
            $whereSarr = array();
            $whereSarr[] = array('village_id', '=', $village_id);
            $whereSarr[] = array('status', 'in', array(0, 1));
            $whereSarr[] = array('third_single_index_id', '=', '');
            $hTmpSingle = $houseVillageSingleDb->getOne($whereSarr);
            if (empty($hTmpSingle) || $hTmpSingle->isEmpty()) {
                $whereSarr = array();
                $whereSarr[] = array('village_id', '=', $village_id);
                $whereSarr[] = array('status', 'in', array(0, 1));
                $whereSarr[] = array('third_single_index_id', '=', $data['build_index_code']);
                $hSingle = $houseVillageSingleDb->getOne($whereSarr);
                $single_id = 0;
                $floor_id = 0;
                $layer_id = 0;
                if ($hSingle && !$hSingle->isEmpty()) {
                    $single_id = $hSingle['id'];
                } else {
                    //没有楼栋 自动创建
                    $build_index_id = 0;
                    $hSingleArr = array('single_name' => '', 'village_id' => $village_id, 'single_number' => $data['build_no'], 'status' => 1, 'third_single_index_id' => $data['build_index_code'], 'update_time' => $nowtime);
                    if (isset($data['single_name']) && !empty($data['single_name'])) {
                        $hSingleArr['single_name'] = $data['single_name'];
                    } else {
                        $buildWhere = array();
                        $buildWhere[] = ['app_key', '=', $data['app_key']];
                        $buildWhere[] = ['from', '=', 'build'];
                        $buildWhere[] = ['index_code', '=', $data['build_index_code']];
                        $buildInfo = $dbHikInfoVisionCommunityRegions->getOne($buildWhere, 'id,name,single_id');
                        if ($buildInfo && !$buildInfo->isEmpty()) {
                            $hSingleArr['single_name'] = $buildInfo['name'];
                            $build_index_id = $buildInfo['id'];
                        }
                    }
                    $single_id = $houseVillageSingleDb->addOne($hSingleArr);
                    $buildWhere = array();
                    if ($build_index_id > 0) {
                        $floorWhere[] = ['id', '=', $build_index_id];
                        $buildWhere[] = ['from', '=', 'build'];
                    } else {
                        $buildWhere[] = ['app_key', '=', $data['app_key']];
                        $buildWhere[] = ['from', '=', 'build'];
                        $buildWhere[] = ['index_code', '=', $data['build_index_code']];
                    }
                    $dbHikInfoVisionCommunityRegions->updateThis($buildWhere, ['single_id' => $single_id, 'village_id' => $village_id]);
                }
                //单元处理
                $whereFarr = array();
                $whereFarr[] = array('village_id', '=', $village_id);
                $whereFarr[] = array('status', 'in', array(0, 1));
                $whereFarr[] = array('single_id', '=', $single_id);
                $whereFarr[] = array('third_floor_index_id', '=', $data['build_index_code']);
                $hFloor = $houseVillageFloorDb->getOne($whereFarr);
                if ($hFloor && !$hFloor->isEmpty()) {
                    $floor_id = $hFloor['floor_id'];
                } else {
                    //没有单元 自动创建
                    $floor_index_id = 0;
                    $hFloorArr = array('village_id' => $village_id, 'floor_name' => '', 'floor_number' => $data['unit_no'], 'status' => 1, 'third_floor_index_id' => $data['unit_index_code']);
                    $hFloorArr['add_time'] = $nowtime;
                    $hFloorArr['single_id'] = $single_id;
                    if (isset($data['floor_name']) && !empty($data['floor_name'])) {
                        $hFloorArr['floor_name'] = $data['floor_name'];
                    } else {
                        $floorWhere = array();
                        $floorWhere[] = ['app_key', '=', $data['app_key']];
                        $floorWhere[] = ['from', '=', 'unit'];
                        $floorWhere[] = ['index_code', '=', $data['unit_index_code']];
                        $floorInfo = $dbHikInfoVisionCommunityRegions->getOne($floorWhere, 'id,name,single_id,floor_id');
                        if ($floorInfo && !$floorInfo->isEmpty()) {
                            $hFloorArr['floor_name'] = $floorInfo['name'];
                            $floor_index_id = $floorInfo['id'];
                        }
                    }
                    $floor_id = $houseVillageFloorDb->addOne($hFloorArr);
                    $floorWhere = array();
                    if ($floor_index_id > 0) {
                        $floorWhere[] = ['id', '=', $floor_index_id];
                        $floorWhere[] = ['from', '=', 'unit'];
                    } else {
                        $floorWhere[] = ['app_key', '=', $data['app_key']];
                        $floorWhere[] = ['from', '=', 'unit'];
                        $floorWhere[] = ['index_code', '=', $data['unit_index_code']];
                    }
                    $dbHikInfoVisionCommunityRegions->updateThis($floorWhere, ['single_id' => $single_id, 'village_id' => $village_id, 'floor_id' => $floor_id]);
                }

                //楼层处理
                $whereLarr = array();
                $whereLarr[] = array('village_id', '=', $village_id);
                $whereLarr[] = array('status', 'in', array(0, 1));
                $whereLarr[] = array('single_id', '=', $single_id);
                $whereLarr[] = array('floor_id', '=', $floor_id);
                $whereLarr[] = array('layer_number', '=', $data['floor_no']);
                $hLayer = $houseVillageLayerDb->getOne($whereLarr);
                if ($hLayer && !$hLayer->isEmpty()) {
                    $layer_id = $hLayer['id'];
                } else {
                    //没有层 自动创建
                    $hLayerArr = array('village_id' => $village_id, 'layer_name' => intval($data['floor_no']), 'layer_number' => $data['floor_no'], 'status' => 1);
                    $hLayerArr['add_time'] = $nowtime;
                    $hLayerArr['single_id'] = $single_id;
                    $hLayerArr['floor_id'] = $floor_id;
                    $layer_id = $houseVillageLayerDb->addOne($hLayerArr);
                }
                //添加房间数据
                $village_vacancy_arr = array();
                $property_number = $village_id . $data['build_no'] . $data['unit_no'] . $data['floor_no'];
                $village_vacancy_arr['usernum'] = isset($data['region_code']) && !empty($data['region_code']) ? $data['region_code'] : $property_number;
                $village_vacancy_arr['village_id'] = $village_id;
                $village_vacancy_arr['single_id'] = $single_id;
                $village_vacancy_arr['floor_id'] = $floor_id;
                $village_vacancy_arr['layer_id'] = $layer_id;
                $village_vacancy_arr['layer'] = intval($data['floor_no']);
                $village_vacancy_arr['room'] = $data['gate_house_no'];
                $village_vacancy_arr['status'] = 1;
                $village_vacancy_arr['add_time'] = $nowtime;
                $village_vacancy_arr['house_type'] = 1;
                //living_type 1：自住，2：出租，3：空置
                $village_vacancy_arr['sell_status'] = 1;
                if ($data['living_type'] == 2) {
                    $village_vacancy_arr['sell_status'] = 3;
                }
                $village_vacancy_arr['room_number'] = $data['gate_house_no'];
                $village_vacancy_arr['third_room_id'] = $data['index_code'];
                $vacancy_id = $houseVillageUserVacancyDb->addOne($village_vacancy_arr);
                fdump_api(['village_id' => $village_id, 'vacancy_id' => $vacancy_id, 'village_vacancy_arr' => $village_vacancy_arr], 'infoVisionICommunity/saveHouseVillageUserVacancy', 1);
                $updateTmpArr = array();
                $updateTmpArr['village_id'] = $village_id;
                $updateTmpArr['single_id'] = $single_id;
                $updateTmpArr['floor_id'] = $floor_id;
                $updateTmpArr['layer_id'] = $layer_id;
                $updateTmpArr['room_id'] = $vacancy_id;
                $updateTmpArr['bind_time'] = $nowtime;
                $dbHikInfoVisionCommunityRegions->updateThis(['id' => $data['id']], $updateTmpArr);
            }

        }
        return true;
    }

    public function getCommunitySingle($village_id = 0, $third_single_index_id = '')
    {
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $houseWhere = array();
        $houseWhere[] = ['app_key', '=', $app_key];
        $houseWhere[] = ['from', '=', 'house'];
        $houseWhere[] = ['village_id', '=', $village_id];
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $houseInfo = $dbHikInfoVisionCommunityRegions->getOne($houseWhere);
        if ($houseInfo && !$houseInfo->isEmpty()) {
            $buildWhere = array();
            $buildWhere[] = ['app_key', '=', $app_key];
            $buildWhere[] = ['from', '=', 'build'];
            $buildWhere[] = ['community_index_code', '=', $houseInfo['index_code']];
            $singleInfo = $dbHikInfoVisionCommunityRegions->getListData($buildWhere, 'id,index_code,name,single_id');
            if ($singleInfo && !$singleInfo->isEmpty()) {
                $singleInfo = $singleInfo->toArray();
                foreach ($singleInfo as $skk => $svv) {
                    $singleInfo[$skk]['is_disabled'] = false;
                    if ($svv['single_id'] > 0 && $svv['index_code'] != $third_single_index_id) {
                        $singleInfo[$skk]['is_disabled'] = true;
                        $singleInfo[$skk]['name'] = $svv['name'] . '(已被绑定)';
                    }
                }
                return $singleInfo;
            }
        }
        return array();
    }

    public function getCommunitySingleFloor($village_id = 0, $single_id = 0, $third_floor_index_id = '')
    {
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $buildWhere = array();
        $buildWhere[] = ['app_key', '=', $app_key];
        $buildWhere[] = ['from', '=', 'build'];
        $buildWhere[] = ['village_id', '=', $village_id];
        $buildWhere[] = ['single_id', '=', $single_id];
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $buildInfo = $dbHikInfoVisionCommunityRegions->getOne($buildWhere);
        if ($buildInfo && !$buildInfo->isEmpty()) {
            $unitWhere = array();
            $unitWhere[] = ['app_key', '=', $app_key];
            $unitWhere[] = ['from', '=', 'unit'];
            $unitWhere[] = ['build_index_code', '=', $buildInfo['index_code']];
            $unitInfo = $dbHikInfoVisionCommunityRegions->getListData($unitWhere, 'id,index_code,name,single_id,floor_id');
            if ($unitInfo && !$unitInfo->isEmpty()) {
                $unitInfo = $unitInfo->toArray();
                foreach ($unitInfo as $ukk => $uvv) {
                    $unitInfo[$ukk]['is_disabled'] = false;
                    if ($uvv['floor_id'] > 0 && $uvv['index_code'] != $third_floor_index_id) {
                        $unitInfo[$ukk]['is_disabled'] = true;
                        $unitInfo[$ukk]['name'] = $uvv['name'] . '(已被绑定)';
                    }
                }
                return $unitInfo;
            }
        }
        return array();
    }

    public function getCommunitySingleFloorRooms($village_id = 0, $otherData = array())
    {
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $single_id = isset($otherData['single_id']) ? $otherData['single_id'] : 0;
        $floor_id = isset($otherData['floor_id']) ? $otherData['floor_id'] : 0;
        $layer_id = isset($otherData['layer_id']) ? $otherData['layer_id'] : 0;
        $vacancy_id = isset($otherData['vacancy_id']) ? $otherData['vacancy_id'] : 0;
        $third_room_id = isset($otherData['third_room_id']) ? $otherData['third_room_id'] : 0;
        if ($village_id > 0 && $single_id > 0 && $floor_id > 0) {
            $roomWhere = array();
            $roomWhere[] = ['app_key', '=', $app_key];
            $roomWhere[] = ['from', '=', 'room'];
            $roomWhere[] = ['village_id', '=', $village_id];
            $roomWhere[] = ['single_id', '=', $single_id];
            $roomWhere[] = ['floor_id', '=', $floor_id];
            $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
            $roomInfos = $dbHikInfoVisionCommunityRegions->getListData($roomWhere, 'id,index_code,name,single_id,floor_id,layer_id,room_id,unit_index_code');
            if ($roomInfos && !$roomInfos->isEmpty()) {
                $roomInfos = $roomInfos->toArray();
                foreach ($roomInfos as $rkk => $rvv) {
                    $roomInfos[$rkk]['is_disabled'] = false;
                    if ($rvv['room_id'] > 0 && $rvv['index_code'] != $third_room_id) {
                        $roomInfos[$rkk]['is_disabled'] = true;
                        $roomInfos[$rkk]['name'] = $rvv['name'] . '(已被绑定)';
                    }
                }
                return $roomInfos;

            }
        }
        return array();
    }

    public function saveChangeHouseVillageUserVacancy($village_id = 0, $data = array())
    {
        fdump_api(['village_id' => $village_id, 'data' => $data], 'infoVisionICommunity/saveHouseVillageUserVacancy', 1);
        $houseVillageUserVacancyDb = new HouseVillageUserVacancy();
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $nowtime = time();
        if ($data['build_index_code']) {
            $houseVillageSingleDb = new HouseVillageSingle();
            $houseVillageFloorDb = new HouseVillageFloor();
            $houseVillageLayerDb = new HouseVillageLayer();
            $single_id = 0;
            $floor_id = 0;
            $layer_id = 0;
            if (isset($data['single_id']) && $data['single_id'] > 0) {
                $single_id = $data['single_id'];
            }
            if ($single_id < 1) {
                //楼栋必须有
                return true;
            }
            if (isset($data['floor_id']) && $data['floor_id'] > 0) {
                $floor_id = $data['floor_id'];
            }
            if (isset($data['layer_id']) && $data['layer_id'] > 0) {
                $layer_id = $data['layer_id'];
            }
            //单元处理
            if ($floor_id < 1) {
                //没有单元 自动创建
                $floor_index_id = 0;
                $hFloorArr = array('village_id' => $village_id, 'floor_name' => '', 'floor_number' => $data['unit_no'], 'status' => 1, 'third_floor_index_id' => $data['unit_index_code']);
                $hFloorArr['add_time'] = $nowtime;
                $hFloorArr['single_id'] = $single_id;
                if (isset($data['floor_name']) && !empty($data['floor_name'])) {
                    $hFloorArr['floor_name'] = $data['floor_name'];
                } else {
                    $floorWhere = array();
                    $floorWhere[] = ['app_key', '=', $data['app_key']];
                    $floorWhere[] = ['from', '=', 'unit'];
                    $floorWhere[] = ['index_code', '=', $data['unit_index_code']];
                    $floorInfo = $dbHikInfoVisionCommunityRegions->getOne($floorWhere, 'id,name,single_id,floor_id');
                    if ($floorInfo && !$floorInfo->isEmpty()) {
                        $hFloorArr['floor_name'] = $floorInfo['name'];
                        $floor_index_id = $floorInfo['id'];
                    }
                }
                $floor_id = $houseVillageFloorDb->addOne($hFloorArr);
                $floorWhere = array();
                if ($floor_index_id > 0) {
                    $floorWhere[] = ['id', '=', $floor_index_id];
                    $floorWhere[] = ['from', '=', 'unit'];
                } else {
                    $floorWhere[] = ['app_key', '=', $data['app_key']];
                    $floorWhere[] = ['from', '=', 'unit'];
                    $floorWhere[] = ['index_code', '=', $data['unit_index_code']];
                }
                $dbHikInfoVisionCommunityRegions->updateThis($floorWhere, ['single_id' => $single_id, 'village_id' => $village_id, 'floor_id' => $floor_id]);
            }

            //楼层处理
            if ($layer_id < 1) {
                //没有层 自动创建
                $hLayerArr = array('village_id' => $village_id, 'layer_name' => intval($data['floor_no']), 'layer_number' => $data['floor_no'], 'status' => 1);
                $hLayerArr['add_time'] = $nowtime;
                $hLayerArr['single_id'] = $single_id;
                $hLayerArr['floor_id'] = $floor_id;
                $layer_id = $houseVillageLayerDb->addOne($hLayerArr);
            }
            //添加房间数据
            $village_vacancy_arr = array();
            $property_number = $village_id . $data['build_no'] . $data['unit_no'] . $data['floor_no'];
            $village_vacancy_arr['usernum'] = isset($data['region_code']) && !empty($data['region_code']) ? $data['region_code'] : $property_number;
            $village_vacancy_arr['village_id'] = $village_id;
            $village_vacancy_arr['single_id'] = $single_id;
            $village_vacancy_arr['floor_id'] = $floor_id;
            $village_vacancy_arr['layer_id'] = $layer_id;
            $village_vacancy_arr['layer'] = intval($data['floor_no']);
            $village_vacancy_arr['room'] = $data['gate_house_no'];
            $village_vacancy_arr['status'] = 1;
            $village_vacancy_arr['add_time'] = $nowtime;
            $village_vacancy_arr['house_type'] = 1;
            //living_type 1：自住，2：出租，3：空置
            $village_vacancy_arr['sell_status'] = 1;
            if ($data['living_type'] == 2) {
                $village_vacancy_arr['sell_status'] = 3;
            }
            $village_vacancy_arr['room_number'] = $data['gate_house_no'];
            $village_vacancy_arr['third_room_id'] = $data['index_code'];
            $vacancy_id = $houseVillageUserVacancyDb->addOne($village_vacancy_arr);
            fdump_api(['village_id' => $village_id, 'vacancy_id' => $vacancy_id, 'village_vacancy_arr' => $village_vacancy_arr], 'infoVisionICommunity/saveHouseVillageUserVacancy', 1);
            $updateTmpArr = array();
            $updateTmpArr['village_id'] = $village_id;
            $updateTmpArr['single_id'] = $single_id;
            $updateTmpArr['floor_id'] = $floor_id;
            $updateTmpArr['layer_id'] = $layer_id;
            $updateTmpArr['room_id'] = $vacancy_id;
            $updateTmpArr['bind_time'] = $nowtime;
            $dbHikInfoVisionCommunityRegions->updateThis(['id' => $data['id']], $updateTmpArr);

        }
        return true;
    }

    public function checkSingleIsBinded($third_single_index_id = '', $single_id = 0)
    {
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $buildWhere = array();
        $buildWhere[] = ['app_key', '=', $app_key];
        $buildWhere[] = ['from', '=', 'build'];
        $buildWhere[] = ['index_code', '=', $third_single_index_id];
        $buildInfo = $dbHikInfoVisionCommunityRegions->getOne($buildWhere);
        if ($buildInfo && isset($buildInfo['single_id']) && $buildInfo['single_id'] > 0 && $buildInfo['single_id'] != $single_id) {
            return true;
        }
        return false;
    }

    public function checkFloorIsBinded($village_id = 0, $third_floor_index_id = '', $floor_id = 0)
    {
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $buildWhere = array();
        $buildWhere[] = ['app_key', '=', $app_key];
        $buildWhere[] = ['from', '=', 'unit'];
        $buildWhere[] = ['village_id', '=', $village_id];
        $buildWhere[] = ['index_code', '=', $third_floor_index_id];
        $buildInfo = $dbHikInfoVisionCommunityRegions->getOne($buildWhere);
        if ($buildInfo && isset($buildInfo['floor_id']) && $buildInfo['floor_id'] > 0 && $buildInfo['floor_id'] != $floor_id) {
            return true;
        }
        return false;
    }

    public function checkRoomIsBinded($village_id = 0, $third_room_id = '', $vacancy_id = 0)
    {
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $buildWhere = array();
        $buildWhere[] = ['app_key', '=', $app_key];
        $buildWhere[] = ['from', '=', 'room'];
        $buildWhere[] = ['village_id', '=', $village_id];
        $buildWhere[] = ['index_code', '=', $third_room_id];
        $buildInfo = $dbHikInfoVisionCommunityRegions->getOne($buildWhere);
        if ($buildInfo && isset($buildInfo['room_id']) && $buildInfo['room_id'] > 0 && $buildInfo['room_id'] != $vacancy_id) {
            return true;
        }
        return false;
    }

    public function handleHikInfoVisionCommunityVacancy($data = array())
    {
        $village_id = 0;
        if (isset($data['village_id'])) {
            $village_id = $data['village_id'];
        }
        $app_key = cfg('hik_info_vision_i_community_app_key');
        /*
        $root=dirname(dirname(dirname(__DIR__)));
        $root=dirname(dirname(dirname($root)));
        $basePath =  rtrim($root,'/');
        $_SERVER['DOCUMENT_ROOT']=$basePath;
        */
        $handle_type = $data['handle_type'];
        $single_id = $data['single_id'];
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        if ($handle_type == 'handleSingleVacancy') {
            $old_third_single_index_id = $data['old_third_single_index_id'];
            $third_single_index_id = $data['third_single_index_id'];
            $houseVillageFloorDb = new HouseVillageFloor();
            if ($old_third_single_index_id) {
                //将原来的关系解除
                $singleWhere = array();
                $singleWhere[] = ['app_key', '=', $app_key];
                $singleWhere[] = ['from', '=', 'build'];
                $singleWhere[] = ['index_code', '=', $old_third_single_index_id];
                $updateArr = array('single_id' => 0, 'village_id' => $village_id);
                $dbHikInfoVisionCommunityRegions->updateThis($singleWhere, $updateArr);

                //将此楼栋下的 单元和房间解除绑定，等待新的手动绑定
                $tmpWhere = array();
                $tmpWhere[] = ['app_key', '=', $app_key];
                $tmpWhere[] = ['from', 'in', array('unit', 'room')];
                $tmpWhere[] = ['build_index_code', '=', $old_third_single_index_id];
                $updateArr = array('single_id' => 0, 'village_id' => $village_id, 'floor_id' => 0, 'layer_id' => 0, 'room_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
            }

            if ($third_single_index_id) {
                $singleWhere = array();
                $singleWhere[] = ['app_key', '=', $app_key];
                $singleWhere[] = ['from', '=', 'build'];
                $singleWhere[] = ['index_code', '=', $third_single_index_id];
                $updateArr = array('single_id' => $single_id, 'village_id' => $village_id);
                $dbHikInfoVisionCommunityRegions->updateThis($singleWhere, $updateArr);
                //将此楼栋下的 单元和房间解除绑定，等待新的手动绑定
                $tmpWhere = array();
                $tmpWhere[] = ['app_key', '=', $app_key];
                $tmpWhere[] = ['from', 'in', array('unit', 'room')];
                $tmpWhere[] = ['build_index_code', '=', $third_single_index_id];
                $updateArr = array('single_id' => $single_id, 'village_id' => $village_id, 'floor_id' => 0, 'layer_id' => 0, 'room_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
            }
            $whereArr = array('single_id' => $single_id, 'village_id' => $village_id);
            $houseVillageFloorDb->saveOne($whereArr, ['third_floor_index_id' => '']);

            $houseVillageUserVacancy = new HouseVillageUserVacancy();
            $whereArr = array('single_id' => $single_id, 'village_id' => $village_id);
            $houseVillageUserVacancy->saveOne($whereArr, ['third_room_id' => '']);

            if ($third_single_index_id) {
                $whereArr = array(['single_id', '=', $single_id], ['village_id', '=', $village_id]);
                $whereArr[] = array('status', 'in', array(0, 1));
                $hFloor = $houseVillageFloorDb->getOne($whereArr);
                if (empty($hFloor) || $hFloor->isEmpty()) {
                    //下面没有单元
                    $tmpWhere = array();
                    $tmpWhere[] = ['app_key', '=', $app_key];
                    $tmpWhere[] = ['from', '=', 'room'];
                    $tmpWhere[] = ['build_index_code', '=', $third_single_index_id];
                    $rooms = $dbHikInfoVisionCommunityRegions->getListData($tmpWhere, '*');;
                    if ($rooms && !$rooms->isEmpty()) {
                        $rooms = $rooms->toArray();
                        foreach ($rooms as $rv) {
                            $rv['single_id'] = $single_id;
                            $this->saveChangeHouseVillageUserVacancy($village_id, $rv);
                        }
                    }

                }
            }

        } else if ($handle_type == 'handleFloorVacancy') {
            $floor_id = $data['floor_id'];
            $old_third_floor_index_id = $data['old_third_floor_index_id'];
            $third_floor_index_id = $data['third_floor_index_id'];
            if ($old_third_floor_index_id) {
                //将原来的关系解除
                $singleWhere = array();
                $singleWhere[] = ['app_key', '=', $app_key];
                $singleWhere[] = ['from', '=', 'unit'];
                $singleWhere[] = ['single_id', '=', $single_id];
                $singleWhere[] = ['index_code', '=', $old_third_floor_index_id];
                $updateArr = array('village_id' => $village_id, 'floor_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($singleWhere, $updateArr);

                //将此单元下的 单元和房间解除绑定，等待新的手动绑定
                $tmpWhere = array();
                $tmpWhere[] = ['app_key', '=', $app_key];
                $tmpWhere[] = ['from', '=', 'room'];
                $tmpWhere[] = ['single_id', '=', $single_id];
                $tmpWhere[] = ['unit_index_code', '=', $old_third_floor_index_id];
                $updateArr = array('village_id' => $village_id, 'floor_id' => 0, 'layer_id' => 0, 'room_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
            }
            if ($third_floor_index_id) {
                $singleWhere = array();
                $singleWhere[] = ['app_key', '=', $app_key];
                $singleWhere[] = ['from', '=', 'unit'];
                $singleWhere[] = ['single_id', '=', $single_id];
                $singleWhere[] = ['index_code', '=', $third_floor_index_id];
                $updateArr = array('village_id' => $village_id, 'floor_id' => $floor_id);
                $dbHikInfoVisionCommunityRegions->updateThis($singleWhere, $updateArr);
                //将此单元下的 单元和房间解除绑定，等待新的手动绑定
                $tmpWhere = array();
                $tmpWhere[] = ['app_key', '=', $app_key];
                $tmpWhere[] = ['from', 'in', 'room'];
                $tmpWhere[] = ['single_id', '=', $single_id];
                $tmpWhere[] = ['unit_index_code', '=', $third_floor_index_id];
                $updateArr = array('village_id' => $village_id, 'floor_id' => $floor_id, 'layer_id' => 0, 'room_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
            }
            $houseVillageUserVacancy = new HouseVillageUserVacancy();
            $whereArr = array('single_id' => $single_id, 'floor_id' => $floor_id, 'village_id' => $village_id);
            $houseVillageUserVacancy->saveOne($whereArr, ['third_room_id' => '']);
            if ($third_floor_index_id) {
                $whereArr = array(['single_id', '=', $single_id], ['village_id', '=', $village_id]);
                $whereArr[] = array('status', 'in', array(0, 1));
                $whereArr[] = array('floor_id', '=', $floor_id);
                $houseVillageLayerDb = new HouseVillageLayer();
                $hLayer = $houseVillageLayerDb->getOne($whereArr);
                if (empty($hLayer) || $hLayer->isEmpty()) {
                    //下面没有楼层
                    $tmpWhere = array();
                    $tmpWhere[] = ['app_key', '=', $app_key];
                    $tmpWhere[] = ['from', '=', 'room'];
                    $tmpWhere[] = ['single_id', '=', $single_id];
                    $tmpWhere[] = ['unit_index_code', '=', $third_floor_index_id];
                    $rooms = $dbHikInfoVisionCommunityRegions->getListData($tmpWhere, '*');;
                    if ($rooms && !$rooms->isEmpty()) {
                        $rooms = $rooms->toArray();
                        foreach ($rooms as $rv) {
                            $rv['single_id'] = $single_id;
                            $rv['floor_id'] = $floor_id;
                            $this->saveChangeHouseVillageUserVacancy($village_id, $rv);
                        }
                    }

                }
            }
        } else if ($handle_type == 'handleLayerVacancy') {

        } else if ($handle_type == 'handleRoomVacancy') {
            $floor_id = $data['floor_id'];
            $layer_id = $data['layer_id'];
            $old_third_room_id = $data['old_third_room_id'];
            $third_room_id = $data['third_room_id'];
            $vacancy_id = $data['vacancy_id'];
            if ($single_id > 0 && $floor_id > 0 && $layer_id > 0 && $vacancy_id > 0) {
                if ($old_third_room_id) {
                    //将此原来房间的 解除绑定
                    $tmpWhere = array();
                    $tmpWhere[] = ['app_key', '=', $app_key];
                    $tmpWhere[] = ['from', '=', 'room'];
                    $tmpWhere[] = ['village_id', '=', $village_id];
                    $tmpWhere[] = ['single_id', '=', $single_id];
                    $tmpWhere[] = ['floor_id', '=', $floor_id];
                    $tmpWhere[] = ['index_code', '=', $old_third_room_id];
                    $updateArr = array('layer_id' => 0, 'room_id' => 0);
                    $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
                }
                if ($third_room_id) {
                    $tmpWhere = array();
                    $tmpWhere[] = ['app_key', '=', $app_key];
                    $tmpWhere[] = ['from', '=', 'room'];
                    $tmpWhere[] = ['village_id', '=', $village_id];
                    $tmpWhere[] = ['single_id', '=', $single_id];
                    $tmpWhere[] = ['floor_id', '=', $floor_id];
                    $tmpWhere[] = ['index_code', '=', $third_room_id];
                    $updateArr = array('layer_id' => $layer_id, 'room_id' => $vacancy_id);
                    $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
                }
            }
        }
        return true;
    }

    public function unbindHikInfoVisionCommunityVacancy($village_id, $data = array())
    {
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $handle_type = $data['handle_type'];
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        if ($village_id < 1 && isset($data['village_id']) && !empty($data['village_id'])) {
            $village_id = $data['village_id'];
        }
        if ($handle_type == 'handleSingleVacancy') {
            $single_id = $data['single_id'];
            $third_single_index_id = $data['third_single_index_id'];
            if ($single_id > 0 && $third_single_index_id) {
                //将原来的关系解除
                $singleWhere = array();
                $singleWhere[] = ['app_key', '=', $app_key];
                $singleWhere[] = ['from', '=', 'build'];
                $singleWhere[] = ['index_code', '=', $third_single_index_id];
                $updateArr = array('single_id' => 0, 'village_id' => $village_id);
                $dbHikInfoVisionCommunityRegions->updateThis($singleWhere, $updateArr);

                //将此楼栋下的 单元和房间解除绑定，等待新的手动绑定
                $tmpWhere = array();
                $tmpWhere[] = ['app_key', '=', $app_key];
                $tmpWhere[] = ['from', 'in', array('unit', 'room')];
                $tmpWhere[] = ['build_index_code', '=', $third_single_index_id];
                $updateArr = array('single_id' => 0, 'village_id' => $village_id, 'floor_id' => 0, 'layer_id' => 0, 'room_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
            }
            $houseVillageFloorDb = new HouseVillageFloor();
            $whereArr = array('single_id' => $single_id, 'village_id' => $village_id);
            $houseVillageFloorDb->saveOne($whereArr, ['third_floor_index_id' => '']);

            $houseVillageUserVacancy = new HouseVillageUserVacancy();
            $whereArr = array('single_id' => $single_id, 'village_id' => $village_id);
            $houseVillageUserVacancy->saveOne($whereArr, ['third_room_id' => '']);

        } else if ($handle_type == 'handleFloorVacancy') {
            $single_id = $data['single_id'];
            $floor_id = $data['floor_id'];
            $third_floor_index_id = $data['third_floor_index_id'];
            if ($floor_id > 0 && $third_floor_index_id) {
                $tmpWhere = array();
                $tmpWhere[] = ['app_key', '=', $app_key];
                $tmpWhere[] = ['from', 'in', array('room', 'unit')];
                $tmpWhere[] = ['village_id', '=', $village_id];
                $tmpWhere[] = ['unit_index_code', '=', $third_floor_index_id];
                if ($single_id > 0) {
                    $tmpWhere[] = ['single_id', '=', $single_id];
                }
                if ($floor_id > 0) {
                    $tmpWhere[] = ['floor_id', '=', $floor_id];
                }
                $updateArr = array('floor_id' => 0, 'layer_id' => 0, 'room_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
            }
            $houseVillageUserVacancy = new HouseVillageUserVacancy();
            $whereArr = array('floor_id' => $floor_id, 'village_id' => $village_id);
            if ($single_id > 0) {
                $whereArr['single_id'] = $single_id;
            }
            $houseVillageUserVacancy->saveOne($whereArr, ['third_room_id' => '']);
        } else if ($handle_type == 'handleLayerVacancy') {
            $single_id = $data['single_id'];
            $floor_id = $data['floor_id'];
            $layer_id = $data['layer_id'];
            if ($layer_id > 0) {
                $tmpWhere = array();
                $tmpWhere[] = ['app_key', '=', $app_key];
                $tmpWhere[] = ['from', '=', 'room'];
                $tmpWhere[] = ['village_id', '=', $village_id];
                $tmpWhere[] = ['layer_id', '=', $layer_id];
                if ($single_id > 0) {
                    $tmpWhere[] = ['single_id', '=', $single_id];
                }
                if ($floor_id > 0) {
                    $tmpWhere[] = ['floor_id', '=', $floor_id];
                }
                $updateArr = array('layer_id' => 0, 'room_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
            }
            $houseVillageUserVacancy = new HouseVillageUserVacancy();
            $whereArr = array('layer_id' => $layer_id, 'village_id' => $village_id);
            if ($floor_id > 0) {
                $whereArr['floor_id'] = $floor_id;
            }
            $houseVillageUserVacancy->saveOne($whereArr, ['third_room_id' => '']);
        } else if ($handle_type == 'handleRoomVacancy') {
            $single_id = $data['single_id'];
            $floor_id = $data['floor_id'];
            $layer_id = $data['layer_id'];
            $third_room_id = $data['third_room_id'];
            $vacancy_id = $data['vacancy_id'];
            if ($vacancy_id > 0 && !empty($third_room_id)) {
                $tmpWhere = array();
                $tmpWhere[] = ['app_key', '=', $app_key];
                $tmpWhere[] = ['from', '=', 'room'];
                $tmpWhere[] = ['village_id', '=', $village_id];
                if ($single_id > 0) {
                    $tmpWhere[] = ['single_id', '=', $single_id];
                }
                if ($floor_id > 0) {
                    $tmpWhere[] = ['floor_id', '=', $floor_id];
                }
                $tmpWhere[] = ['room_id', '=', $vacancy_id];
                $tmpWhere[] = ['index_code', '=', $third_room_id];
                $updateArr = array('layer_id' => 0, 'room_id' => 0);
                $dbHikInfoVisionCommunityRegions->updateThis($tmpWhere, $updateArr);
            }
        }
        return true;
    }

    public function handleBindUserToHikCommunity($village_id = 0, $pigcms_id = 0, $userBind = array())
    {
        $houseVillageUserBind = new HouseVillageUserBind();
        if (empty($userBind) && $pigcms_id > 0) {
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $pigcms_id);
            $whereArr[] = array('village_id', '=', $village_id);
            $userBind = $houseVillageUserBind->getOne($whereArr, '*');
            if ($userBind && !$userBind->isEmpty()) {
                $userBind = $userBind->toArray();
            }
        }
        $returnArr = array('err_code' => 0, 'err_msg' => '');
        if (empty($userBind) || !isset($userBind['pigcms_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '房间人员信息不存在！';
            return $returnArr;
        }

        $hikCommunityUser = array();
        $hikCommunityUser['personId'] = strval($userBind['pigcms_id']);
        $hikCommunityUser['personName'] = '';
        if (empty($userBind['name'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '房间人员姓名不存在';
            return $returnArr;
        }
        $relatedType = 6;  //0户主，1租户，3夫妻，4子女，5父母，6朋友，7其他
        if ($userBind['type'] == 0 || $userBind['type'] == 3) {
            $relatedType = 0;
        } else if ($userBind['type'] == 1) {
            $relatedType = 4;
        } else if ($userBind['type'] == 2) {
            $relatedType = 1;
        } else if ($userBind['type'] == 6) {
            //访客
            $relatedType = 6;
        } else if ($userBind['type'] == 4) {
            //工作人员
            $relatedType = 7;
        }
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = ['app_key', '=', $app_key];
        if ($userBind['vacancy_id'] > 0) {
            $whereArr[] = array('from', '=', 'room');
            $whereArr[] = array('single_id', '=', $userBind['single_id']);
            $whereArr[] = array('floor_id', '=', $userBind['floor_id']);
            $whereArr[] = array('layer_id', '=', $userBind['layer_id']);
            $whereArr[] = array('room_id', '=', $userBind['vacancy_id']);
        } else {
            $whereArr[] = array('from', '=', 'house');
        }
        $hikCommunityUser['orgIndexCode'] = '';
        $communityRegions = $dbHikInfoVisionCommunityRegions->getOne($whereArr);
        if ($communityRegions && !$communityRegions->isEmpty()) {
            $communityRegions = $communityRegions->toArray();
            $hikCommunityUser['orgIndexCode'] = $communityRegions['community_index_code'];
        } else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '未绑定海康智慧社区管理平台房屋';
            return $returnArr;
        }
        $infoVisionICommunity = new infoVisionICommunity();
        $hikCommunityUser['personName'] = $userBind['name'];
        $hikCommunityUser['gender'] = 0;  //1：男；2：女；0：未知
        if(!empty($userBind['phone']) && strlen($userBind['phone'])==11){
            $hikCommunityUser['phoneNo'] = $userBind['phone'];
        }
        $hikCommunityUser['tagId'] = $infoVisionICommunity->tag_id;
        if ($userBind['id_card']) {
             $hikCommunityUser['certificateType'] = '111';
             $hikCommunityUser['certificateNo'] = $userBind['id_card'];
            $idCardInfo = idCardAnalysis($userBind['id_card']);
            if ($idCardInfo && is_array($idCardInfo)) {
                $hikCommunityUser['birthday'] = $idCardInfo['birthday'];
                $sex = intval($idCardInfo['sex']);
                $hikCommunityUser['gender'] = $sex == 1 ? 1 : 2;
            }
        }
        $hik_community_person_info = array();
        if ($userBind && isset($userBind['hik_community_person_info']) && !empty($userBind['hik_community_person_info'])) {
            $hik_community_person_info = json_decode($userBind['hik_community_person_info'], true);
            $hik_community_person_info = $hik_community_person_info ? $hik_community_person_info : array();
        }
        $retTmp = $infoVisionICommunity->addPersonResource($hikCommunityUser);
        fdump_api(['village_id' => $village_id, 'url' => '/api/resource/v2/person/single/add', 'post' => $hikCommunityUser, 'retTmp' => $retTmp], 'infoVisionICommunity/00handleBindUserToHikCommunity', 1);
        if ($retTmp && isset($retTmp['data']) && !empty($retTmp['data'])) {
            $personId = $retTmp['data']['personId'];
            $houseVillageUserBind->saveOne(['pigcms_id' => $userBind['pigcms_id']], ['hik_community_person_id' => $personId]);
            $personHouses = array();
            $personHouses['personId'] = $personId;
            $personHouses['roomCode'] = $communityRegions['index_code'];
            $personHouses['relatedType'] = $relatedType;
            $personHouses['checkInTime'] = date('Y-m-d');
            $personHouses['expireTime'] = '2038-12-31';
            if ($userBind['type'] == 6) {
                $personHouses['checkInTime'] = date('Y-m-d');
                $tomorrow_time = strtotime($personHouses['checkInTime'])+25*3600;
                $personHouses['expireTime'] = date('Y-m-d',$tomorrow_time);
                $today_time = strtotime($personHouses['checkInTime']) - 1000;
                if ($userBind['property_starttime'] > $today_time) {
                    $personHouses['checkInTime'] = date('Y-m-d', $userBind['property_starttime']);
                    $tomorrow_time = strtotime($personHouses['checkInTime'])+25*3600;
                    $personHouses['expireTime'] = date('Y-m-d',$tomorrow_time);
                }
            }elseif ($userBind['type'] != 4){
                $propertyServiceTime=$this->getPropertyServiceTime($village_id , $pigcms_id, $userBind );
                $personHouses['checkInTime'] = $propertyServiceTime['checkInTime'];
                $personHouses['expireTime'] = $propertyServiceTime['expireTime'];
            }
            $hPersonRet = $infoVisionICommunity->updateHousesPerson($personHouses);
            if ($hPersonRet && ($hPersonRet['code'] === 0 || $hPersonRet['code'] === '0')) {
                $hik_community_person_info['updateHousesPerson'] = $hPersonRet;
            } else {
                $hPersonRet = $infoVisionICommunity->addHousesPerson($personHouses);
                $hik_community_person_info['addHousesPerson'] = $hPersonRet;
            }
            $userBind['hik_community_person_id'] = $personId;
            $hik_community_person_info_str = json_encode($hik_community_person_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageUserBind->saveOne(['pigcms_id' => $userBind['pigcms_id']], ['hik_community_person_info' => $hik_community_person_info_str]);
            $userBind['hik_community_person_info'] = $hik_community_person_info_str;
            if(!empty($userBind['ic_card'])){
                $openCardRet=$this->openCardBindings($village_id , $pigcms_id , $userBind,$userBind['ic_card']);
            }
            $this->addFaceImgData($village_id, $pigcms_id, $userBind);
        } else if ($retTmp && ($retTmp['code'] == '0x00052301' && (strpos($retTmp['msg'], 'Person') !== false || strpos($retTmp['msg'], 'certificateNo') !== false)&& strpos($retTmp['msg'], 'Exists') !== false)) {
            $personId='';
            if(strpos($retTmp['msg'], 'Person') !== false){
                $personId = strval($userBind['pigcms_id']);
            }
            $qCondition=array();
            if(strpos($retTmp['msg'], 'certificateNo') !== false){
                $personId='';
                $qCondition['orgIndexCodes']=$hikCommunityUser['orgIndexCode'];
                $qCondition['certificateType']='111';
                $qCondition['certificateNo']=$hikCommunityUser['certificateNo'];
                $qCondition['pageNo']=1;
                $qCondition['pageSize']=10;
                $personList=$infoVisionICommunity->getPersonInfoByCondition($qCondition);
                if($personList && isset($personList['data']) && !empty($personList['data']) && isset($personList['data']['list']) && !empty($personList['data']['list'])){
                    $onePerson=$personList['data']['list']['0'];
                    $personId=$onePerson['personId'];
                }
            }
            if(empty($personId)){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '添加人员失败！';
                $returnArr['response_data'] = $retTmp;
                return $returnArr;
            }
            if ($userBind['hik_community_person_id']) {
                $personId = $userBind['hik_community_person_id'];
            } else {
                $houseVillageUserBind->saveOne(['pigcms_id' => $userBind['pigcms_id']], ['hik_community_person_id' => $personId]);
            }
            $personHouses = array();
            $personHouses['personId'] = $personId;
            $personHouses['roomCode'] = $communityRegions['index_code'];
            $personHouses['relatedType'] = $relatedType;
            $personHouses['checkInTime'] = date('Y-m-d');
            $personHouses['expireTime'] = '2038-12-31';
            if ($userBind['type'] == 6) {
                $personHouses['checkInTime'] = date('Y-m-d');
                $tomorrow_time = strtotime($personHouses['checkInTime'])+25*3600;
                $personHouses['expireTime'] = date('Y-m-d',$tomorrow_time);
                $today_time = strtotime($personHouses['checkInTime']) - 1000;
                if ($userBind['property_starttime'] > $today_time) {
                    $personHouses['checkInTime'] = date('Y-m-d', $userBind['property_starttime']);
                    $tomorrow_time = strtotime($personHouses['checkInTime'])+25*3600;
                    $personHouses['expireTime'] = date('Y-m-d',$tomorrow_time);
                }
            }elseif ($userBind['type'] != 4){
                $propertyServiceTime=$this->getPropertyServiceTime($village_id , $pigcms_id, $userBind );
                $personHouses['checkInTime'] = $propertyServiceTime['checkInTime'];
                $personHouses['expireTime'] = $propertyServiceTime['expireTime'];
            }
            $hPersonRet = $infoVisionICommunity->updateHousesPerson($personHouses);
            if ($hPersonRet && ($hPersonRet['code'] === 0 || $hPersonRet['code'] === '0')) {
                $hik_community_person_info['updateHousesPerson'] = $hPersonRet;
            } else {
                $hPersonRet = $infoVisionICommunity->addHousesPerson($personHouses);
                $hik_community_person_info['addHousesPerson'] = $hPersonRet;
            }
            $userBind['hik_community_person_id'] = $personId;
            $hik_community_person_info_str = json_encode($hik_community_person_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $houseVillageUserBind->saveOne(['pigcms_id' => $userBind['pigcms_id']], ['hik_community_person_info' => $hik_community_person_info_str]);
            $userBind['hik_community_person_info'] = $hik_community_person_info_str;
            if(!empty($userBind['ic_card'])){
                $openCardRet=$this->openCardBindings($village_id , $pigcms_id , $userBind,$userBind['ic_card']);
            }
            $retFace = $this->addFaceImgData($village_id, $pigcms_id, $userBind);
        } else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = $retTmp['msg'];
            $returnArr['response_data'] = $retTmp;
            return $returnArr;
        }
    }
    public function  getPropertyServiceTime($village_id = 0, $pigcms_id = 0, $userBind=array()){
        $field1 = 'owe_property_open_door,owe_property_open_door_day,property_id';
        $db_house_village=new HouseVillage();
        $villageInfo=$db_house_village->getOne($village_id,$field1);
        $propertyServiceTime=array();
        $yesterday_time=time()-86400;
        $propertyServiceTime['checkInTime'] = date('Y-m-d',$yesterday_time);
        $propertyServiceTime['expireTime'] = '2038-12-31';
        if (empty($villageInfo) || $villageInfo->isEmpty()){
            return $propertyServiceTime;
        }
        $houseVillageUserBind = new HouseVillageUserBind();
        if (empty($userBind) && $pigcms_id > 0) {
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $pigcms_id);
            $whereArr[] = array('village_id', '=', $village_id);
            $userBind = $houseVillageUserBind->getOne($whereArr, '*');
            if ($userBind && !$userBind->isEmpty()) {
                $userBind = $userBind->toArray();
            }
        }
        if (empty($userBind)){
            return $propertyServiceTime;
        }
        $db_house_new_order_log=new HouseNewOrderLog();
        $whereNewOrder = array('room_id'=>$userBind['vacancy_id'], 'order_type'=>'property');
        //查当前房间的服务结束时间
        $order_log = $db_house_new_order_log->getOne($whereNewOrder);
        //查当前房间的服务开始时间
        $order_log_start = $db_house_new_order_log->getOne($whereNewOrder,true,'id ASC');
        $arr=array();
        if($order_log_start && isset($order_log_start['service_start_time']) && ($order_log_start['service_start_time']>100)){
            $arr['propertyStartTime'] = $order_log_start['service_start_time'];
            $arr['propertyEndTime'] = $order_log_start['service_end_time'];
            if($order_log && isset($order_log['service_end_time']) && ($order_log['service_end_time']>$arr['propertyEndTime'])){
                $arr['propertyEndTime'] = $order_log['service_end_time'];
            }
            if($arr['propertyEndTime']<$arr['propertyStartTime']){
                $arr['propertyEndTime'] = $arr['propertyStartTime'];
            }
        }
        if(isset($userBind['open_door_day']) && $userBind['open_door_day']>0){
            $villageInfo['owe_property_open_door_day']=$userBind['open_door_day'];
        }
        if (isset($villageInfo['owe_property_open_door']) && $villageInfo['owe_property_open_door'] && isset($arr['propertyEndTime']) && isset($villageInfo['owe_property_open_door_day']) && $villageInfo['owe_property_open_door_day'] && $arr['propertyEndTime']>100) {
            $arr['propertyEndTime'] +=  intval($villageInfo['owe_property_open_door_day']) * 86400;
        }else if (isset($villageInfo['owe_property_open_door'])&& $villageInfo['owe_property_open_door']  && isset($villageInfo['owe_property_open_door_day']) && !$villageInfo['owe_property_open_door_day']) {
            // 允许延期开门 且为0 默认不限制 2038
            if(!isset($arr['propertyStartTime'])){
                $arr['propertyStartTime']=$yesterday_time;
            }
            $arr['propertyEndTime'] =  2177337600;
        }
        if(isset($arr['propertyStartTime']) && ($arr['propertyStartTime']>846656000)){
            $propertyServiceTime['checkInTime']=date('Y-m-d',$arr['propertyStartTime']);
        }
        if(isset($arr['propertyEndTime']) && ($arr['propertyEndTime']>846656000)){
            $propertyServiceTime['expireTime']=date('Y-m-d',$arr['propertyEndTime']);
        }
        return  $propertyServiceTime;
    }
    public function addOrUpdateHousesPerson($village_id = 0, $pigcms_id = 0, $userBind=array(),$personHouses = array(), $isAddFaceImg = false)
    {
        $houseVillageUserBind = new HouseVillageUserBind();
        if ($pigcms_id > 0 && empty($userBind)) {
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $pigcms_id);
            $whereArr[] = array('village_id', '=', $village_id);
            $userBind = $houseVillageUserBind->getOne($whereArr, '*');
            if ($userBind && !$userBind->isEmpty()) {
                $userBind = $userBind->toArray();
            }
        }
        $returnArr = array('err_code' => 0, 'err_msg' => '');
        if (empty($userBind) || !isset($userBind['pigcms_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '房间人员信息不存在！';
            return $returnArr;
        }
        if (empty($userBind['hik_community_person_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '房间人员未添加！';
            return $returnArr;
        }
        $personHouses['personId'] = $userBind['hik_community_person_id'];
        $relatedType = 6;  //0户主，1租户，3夫妻，4子女，5父母，6朋友，7其他
        if ($userBind['type'] == 0 || $userBind['type'] == 3) {
            $relatedType = 0;
        } else if ($userBind['type'] == 1) {
            $relatedType = 4;
        } else if ($userBind['type'] == 2) {
            $relatedType = 1;
        } else if ($userBind['type'] == 6) {
            //访客
            $relatedType = 6;
        } else if ($userBind['type'] == 4) {
            //工作人员
            $relatedType = 7;
        }
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = ['app_key', '=', $app_key];
        if ($userBind['vacancy_id'] > 0) {
            $whereArr[] = array('from', '=', 'room');
            $whereArr[] = array('single_id', '=', $userBind['single_id']);
            $whereArr[] = array('floor_id', '=', $userBind['floor_id']);
            $whereArr[] = array('layer_id', '=', $userBind['layer_id']);
            $whereArr[] = array('room_id', '=', $userBind['vacancy_id']);
        } else {
            $whereArr[] = array('from', '=', 'house');
        }
        $community_index_code='';
        $communityRegions = $dbHikInfoVisionCommunityRegions->getOne($whereArr);
        if ($communityRegions && !$communityRegions->isEmpty()) {
            $communityRegions = $communityRegions->toArray();
            $personHouses['roomCode'] = $communityRegions['index_code'];
            $community_index_code=$communityRegions['community_index_code'];
        } else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '房间未对接！';
            return $returnArr;
        }
        $is_update=0;
        if($personHouses && isset($personHouses['is_update'])){
            $is_update=1;
            unset($personHouses['is_update']);
        }
        $infoVisionICommunity = new infoVisionICommunity();
        if($is_update==1){
            $hikCommunityUser = array();
            $hikCommunityUser['personId'] = strval($userBind['pigcms_id']);
            $hikCommunityUser['personName'] = $userBind['name'];
            $hikCommunityUser['gender'] = 0;  //1：男；2：女；0：未知
            if ($userBind['phone']){
                $hikCommunityUser['phoneNo'] = $userBind['phone'];
            }
            if (!empty($community_index_code)){
                $hikCommunityUser['orgIndexCode'] = $community_index_code;
            }
            $hikCommunityUser['tagId'] = $infoVisionICommunity->tag_id;
            if ($userBind['id_card']) {
                $hikCommunityUser['certificateType'] = '111';
                $hikCommunityUser['certificateNo'] = $userBind['id_card'];
                $idCardInfo = idCardAnalysis($userBind['id_card']);
                if ($idCardInfo && is_array($idCardInfo)) {
                    $hikCommunityUser['birthday'] = $idCardInfo['birthday'];
                    $sex = intval($idCardInfo['sex']);
                    $hikCommunityUser['gender'] = $sex == 1 ? 1 : 2;
                }
            }
            $updatePersonRet=$infoVisionICommunity->updatePersonResource($hikCommunityUser);
            fdump_api(['updatePersonRet'=>$updatePersonRet,'hikCommunityUser'=>$hikCommunityUser], 'infoVisionICommunity/000UpdateHousesPerson',1);
        }
        
        $hik_community_person_info = array();
        if ($userBind && isset($userBind['hik_community_person_info']) && !empty($userBind['hik_community_person_info'])) {
            $hik_community_person_info = json_decode($userBind['hik_community_person_info'], true);
            $hik_community_person_info = $hik_community_person_info ? $hik_community_person_info : array();
        }
        $personHouses['relatedType'] = $relatedType;
        if (!isset($personHouses['checkInTime']) || empty($personHouses['checkInTime'])) {
            $personHouses['checkInTime'] = date('Y-m-d');
        }
        if (!isset($personHouses['expireTime']) || empty($personHouses['expireTime'])) {
            $personHouses['expireTime'] = '2038-12-31';
        }
        if ($userBind['type'] == 6) {
            $personHouses['checkInTime'] = date('Y-m-d');
            $tomorrow_time = strtotime($personHouses['checkInTime'])+25*3600;
            $personHouses['expireTime'] = date('Y-m-d',$tomorrow_time);
            $today_time = strtotime($personHouses['checkInTime']) - 1000;
            if ($userBind['property_starttime'] > $today_time) {
                $personHouses['checkInTime'] = date('Y-m-d', $userBind['property_starttime']);
                $tomorrow_time = strtotime($personHouses['checkInTime'])+25*3600;
                $personHouses['expireTime'] = date('Y-m-d',$tomorrow_time);
            }
        }elseif ($userBind['type'] != 4){
            $propertyServiceTime=$this->getPropertyServiceTime($village_id , $pigcms_id, $userBind );
            $personHouses['checkInTime'] = $propertyServiceTime['checkInTime'];
            $personHouses['expireTime'] = $propertyServiceTime['expireTime'];
        }
        $hPersonRet = $infoVisionICommunity->updateHousesPerson($personHouses);
        if ($hPersonRet && ($hPersonRet['code'] === 0 || $hPersonRet['code'] === '0')) {
            $hik_community_person_info['updateHousesPerson'] = $hPersonRet;
        } else {
            $hPersonRet = $infoVisionICommunity->addHousesPerson($personHouses);
            $hik_community_person_info['addHousesPerson'] = $hPersonRet;
        }
        $hik_community_person_info_str = json_encode($hik_community_person_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $houseVillageUserBind->saveOne(['pigcms_id' => $userBind['pigcms_id']], ['hik_community_person_info' => $hik_community_person_info_str]);
        $userBind['hik_community_person_info'] = $hik_community_person_info_str;
        if($userBind['ic_card']){
            $openCardRet=$this->openCardBindings($village_id , $pigcms_id , $userBind,$userBind['ic_card']);
        }
        if ($isAddFaceImg) {
            $retFace = $this->addFaceImgData($village_id, $pigcms_id, $userBind);
        }
        if ($hPersonRet && ($hPersonRet['code'] === 0 || $hPersonRet['code'] === '0')) {
            $returnArr['err_code'] = 0;
            $returnArr['err_msg'] = '新增入住信息成功！';
            $returnArr['response_data'] = $hPersonRet;
            return $returnArr;
        } else {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '新增入住信息失败！';
            $returnArr['response_data'] = $hPersonRet;
            return $returnArr;
        }
    }

    public function addFaceImgData($village_id = 0, $pigcms_id = 0, $userBind = array())
    {
        $houseVillageUserBind = new HouseVillageUserBind();
        if (empty($userBind) && $pigcms_id > 0) {
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $pigcms_id);
            $whereArr[] = array('village_id', '=', $village_id);
            $userBind = $houseVillageUserBind->getOne($whereArr, '*');
            if ($userBind && !$userBind->isEmpty()) {
                $userBind = $userBind->toArray();
            }
        }
        $returnArr = array('err_code' => 0, 'err_msg' => '');
        if (empty($userBind) || !isset($userBind['pigcms_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '房间人员信息不存在！';
            return $returnArr;
        }
        if (empty($userBind['hik_community_person_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '房间人员未添加！';
            return $returnArr;
        }
        $infoVisionICommunity = new infoVisionICommunity();
        $faceArr = array();
        $faceArr['personId'] = $userBind['hik_community_person_id'];
        $dbHouseFaceImg = new HouseFaceImg();
        $fieldStr = 'id,uid,img_url,status,hik_community_face_id,bind_pigcms_id';
        $whereArr = array();
        $whereArr[] = ['status', 'in', [0, 3]];
        $whereArr[] = ['img_url', '<>', ''];
        $whereArr[] = ['hik_community_face_id', '=', ''];
        if($userBind['uid']>0){
            $whereArr[] = ['uid', '=', $userBind['uid']];
        }else{
            $whereArr[] = ['bind_pigcms_id', '=', $userBind['pigcms_id']];
        }
        $list = $dbHouseFaceImg->getList($whereArr, $fieldStr, 'id desc', 0, 100);
        $wordEncryption = new wordencryption();
        $site_url = cfg('site_url');
        $site_url = rtrim($site_url, '/');
        $root = dirname(dirname(dirname(__DIR__)));
        $root = dirname(dirname(dirname($root)));
        $basePath = rtrim($root, '/');
        $nowtime = time();
        if (!empty($list) && !$list->isEmpty()) {
            $success_num = 0;
            $err_face_Arr = array();
            $list=$list->toArray();
            foreach ($list as $item) {
                if (3 == $item['status']) {
                    // 加密的图片解密
                    $item['img_url'] = $wordEncryption->text_decrypt($item['img_url']);
                }
                $tmp_img_url=$this->getUserFaceImg($item['uid'],$item['img_url'],['width'=>800,'height'=>650,'is_not_download_upload'=>1]);
                $saveFace = array();
                $extra_data = array('request_data' => array('personId' => $userBind['hik_community_person_id']), 'opt_time' => date('Y-m-d H:i:s'));
                if(!empty($tmp_img_url)){
                    $item['img_url'] = $tmp_img_url;
                }else{
                    $item['img_url'] = replace_file_domain($item['img_url']);
                }
                $imgbase64Str = '';
                if (strpos($item['img_url'], $site_url) !== false) {
                    $img_url = str_replace($site_url, '', $item['img_url']);
                    $img_url = str_replace('//', '/', $img_url);
                    $img_locl_url = $basePath . $img_url;
                    $extra_data['request_data']['img'] = $img_locl_url;
                    if (file_exists($img_locl_url)) {
                        $imgbase64Str = file_get_contents($img_locl_url);
                    }
                }
                if (empty($imgbase64Str) && !empty($item['img_url'])) {
                    $extra_data['request_data']['img'] = $item['img_url'];
                    $imgbase64Str = file_get_contents($item['img_url']);
                }
                if ($imgbase64Str) {
                    $faceArr['faceData'] = base64_encode($imgbase64Str);
                    $retFaceTmp = $infoVisionICommunity->addPersonFaceInfo($faceArr);
                    $extra_data['response_data'] = $retFaceTmp;
                    if ($retFaceTmp && isset($retFaceTmp['data']) && !empty($retFaceTmp['data'])) {
                        $saveFace['last_time'] = $nowtime;
                        $saveFace['hik_community_face_id'] = $retFaceTmp['data']['faceId'];
                        $success_num++;
                    } elseif ($retFaceTmp && isset($retFaceTmp['msg']) && !empty($retFaceTmp['msg']) && (strpos($retFaceTmp['msg'],'ersonFace')!==false &&  strpos($retFaceTmp['msg'],'Exists')!==false)) {
                        //人脸只能传一张，已经存在了
                        $whereArrTmp = array();
                        $whereArrTmp[] = ['uid', '=', $item['uid']];
                        $whereArrTmp[] = ['hik_community_face_id', '<>', ''];
                        $oneFaceImg = $dbHouseFaceImg->getOne($whereArrTmp, '*');
                        if($oneFaceImg && !$oneFaceImg->isEmpty()){
                            $faceId=$oneFaceImg['hik_community_face_id'];
                            $updateFaceArr['faceId']=$faceId;
                            $updateFaceArr['faceData']=$faceArr['faceData'];
                            $updateFaceTmp = $infoVisionICommunity->updatePersonFaceInfo($updateFaceArr);
                            if($updateFaceTmp && isset($updateFaceTmp['data']) && isset($updateFaceTmp['data']['faceId'])){
                                $saveFace['last_time'] = $nowtime;
                                $saveFace['hik_community_face_id'] = $updateFaceTmp['data']['faceId'];
                                $extra_data['response_data'] = $updateFaceTmp;
                                
                                $saveDelArr=array('hik_community_face_id'=>'');
                                $extra_data_tmp=$oneFaceImg['extra_data'] ? json_decode($oneFaceImg['extra_data'],true):array();
                                $extra_data_tmp['del_time']=date('Y-m-d H:i:s');
                                $saveDelArr['extra_data']=json_encode($extra_data_tmp,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                                $dbHouseFaceImg->saveOne(['id'=>$oneFaceImg['id']],$saveDelArr);
                                $success_num++;
                            }
                        }
                    } elseif ($retFaceTmp && isset($retFaceTmp['msg']) && !empty($retFaceTmp['msg'])) {
                        $err_face_Arr[] = $retFaceTmp['msg'];
                    }
                }
                $saveFace['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $dbHouseFaceImg->saveOne(['id' => $item['id']], $saveFace);
            }
            $face_save_arr = array();
            $face_save_arr['add_face_time'] = $nowtime;
            if ($success_num > 0) {
                $hik_community_person_info = array();
                if ($userBind && isset($userBind['hik_community_person_info']) && !empty($userBind['hik_community_person_info'])) {
                    $hik_community_person_info = is_array($userBind['hik_community_person_info']) ? $userBind['hik_community_person_info'] : json_decode($userBind['hik_community_person_info'], true);
                    $hik_community_person_info = $hik_community_person_info ? $hik_community_person_info : array();
                }
                $err_face_msg = '';
                if (!empty($err_face_Arr)) {
                    $err_face_msg = implode('；', $err_face_msg);
                }
                $face_save_arr['face_img_reason'] = '';
                $hik_community_person_info['uploadFaceImg'] = array('success_num' => $success_num, 'err_msg' => $err_face_msg);
                $face_save_arr['hik_community_person_info'] = json_encode($hik_community_person_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $face_save_arr['face_img_status'] = 1;
            } else {
                $err_face_msg = '人脸图片上传失败了!';
                if (!empty($err_face_Arr)) {
                    $err_face_msg = implode('；', $err_face_msg);
                }
                $face_save_arr['face_img_status'] = 2;
                $face_save_arr['face_img_reason'] = $err_face_msg;
            }
            $houseVillageUserBind->saveOne(['pigcms_id' => $userBind['pigcms_id']], $face_save_arr);
        } else {
            $returnArr['err_code'] = 0;
            $returnArr['err_msg'] = '没有人脸图片可添加！';
            return $returnArr;
        }
    }

    /**
     * 查人可以IC卡
     */
    public function getAdvanceCardList($personId = '')
    {
        $infoVisionICommunity = new infoVisionICommunity();
        $personId = strval($personId);
        $cardCondition = array('personIds' => $personId);
        $cardCondition['pageNo'] = 1;
        $cardCondition['pageSize'] = 1000;
        $cardList = $infoVisionICommunity->GetCardListByCondition($cardCondition);
        if ($cardList && isset($cardList['list']) && !empty($cardList['list'])) {
            return $cardList['list'];
        }
        return '';
    }

    /***
     * duration 分钟算
     * maxLock 最大次数 是4
     **/
    public function getDoorbarCodeStr($village_id = 0, $pigcms_id = 0,$uid=0, $duration = 180, $userBind = array())
    {
        $infoVisionICommunity = new infoVisionICommunity();
        $houseVillageUserBind = new HouseVillageUserBind();
        if (empty($userBind) && $pigcms_id > 0) {
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $pigcms_id);
            $whereArr[] = array('village_id', '=', $village_id);
            $userBind = $houseVillageUserBind->getOne($whereArr, '*');
            if ($userBind && !$userBind->isEmpty()) {
                $userBind = $userBind->toArray();
            }
        }
        $duration=$duration>60 ? $duration:60;
        $returnArr = array('err_code' => 0, 'err_msg' => '');
        if (empty($userBind) || !isset($userBind['pigcms_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '人员信息不存在！';
            return $returnArr;
        }
        if ($userBind['uid']!=$uid) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '住户与当前登录人员身份不符！';
            return $returnArr;
        }
        if (empty($userBind['hik_community_person_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '人员未添加到海康智慧社区管理平台！';
            return $returnArr;
        }
        $hik_community_person_info = array();
        if ($userBind && isset($userBind['hik_community_person_info']) && !empty($userBind['hik_community_person_info'])) {
            $hik_community_person_info = is_array($userBind['hik_community_person_info']) ? $userBind['hik_community_person_info'] : json_decode($userBind['hik_community_person_info'], true);
            $hik_community_person_info = $hik_community_person_info ? $hik_community_person_info : array();
        }
        $nowtime=time();
        if (!empty($userBind['hik_community_card_no']) && isset($hik_community_person_info['doorbarCode']) && isset($hik_community_person_info['doorbarCode']['start_time'])) {
            $old_start_time=$hik_community_person_info['doorbarCode']['start_time'];
            $old_duration=$hik_community_person_info['doorbarCode']['duration'];
            $expireTime=$old_start_time+$old_duration*60;
            if($expireTime>$nowtime){
                //没过期
                $returnArr['err_code'] = 0;
                $returnArr['bar_code'] = $userBind['hik_community_card_no'];
                $codeUrl = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $userBind['hik_community_card_no'];
                $returnArr['codeUrl']=$codeUrl;
                $returnArr['effectTimeTip']="起效时间：".date('Y-m-d H:i:s',$old_start_time);
                $returnArr['expireTimeTip']='失效时间：'.date('Y-m-d H:i:s',$expireTime);
                $returnArr['openTimesTip']='';
                $returnArr['tip']='如果二维码过期了，可以点击图片可刷新';
                $returnArr['title']='请将二维码对准设备识别位置';
                return $returnArr;
            }
        }
        $whereDeleteArr = array();
        $whereDeleteArr[] = array('village_id', '=', $village_id);
        $whereDeleteArr[] = array('type', '=', 6);
        $whereDeleteArr[] = array('vacancy_id', '=', $userBind['vacancy_id']);
        $whereDeleteArr[] = array('property_endtime', '<', $nowtime);
        $whereDeleteArr[] = array('hik_community_person_id', '<>', '');
        $fieldStr = 'pigcms_id,village_id,hik_community_person_id';
        $userBinds=$houseVillageUserBind->getList($whereDeleteArr,$fieldStr);
        if($userBinds && !$userBinds->isEmpty()){
            $userBinds=$userBinds->toArray();
            $hik_community_person_ids=array();
            foreach ($userBinds as $bvv){
                $hik_community_person_ids[]=$bvv['hik_community_person_id'];
            }
            if(!empty($hik_community_person_ids)){
                $deleteRet= $this->deleteHikCommunityPerson($hik_community_person_ids,$village_id);
                $returnArr['deleteRet']=$deleteRet;
            }
        }
        
        $personId = $userBind['hik_community_person_id'];
        $barCodeArr = array('maxLock' => 4);
        $barCodeArr['personId'] = $personId;
        $barCodeArr['duration'] = $duration;
        sleep(1);
        $barCodeRet = $infoVisionICommunity->GetDoorbarCodeStr($barCodeArr);
        $err_msg = '生成二维码失败了！';
        if ($barCodeRet && isset($barCodeRet['data']) && !empty($barCodeRet['data'])) {
            $barCode = $barCodeRet['data']['barCode'];
            $cardNo = $barCodeRet['data']['cardNo'];
            $returnArr['err_code'] = 0;
            $returnArr['bar_code'] = $barCode;
            $returnArr['card_no'] = $cardNo;
            $start_time=$nowtime;
            $hik_community_person_info['doorbarCode'] = array('card_no' => $cardNo, 'bar_code' => $barCode, 'duration' => $duration, 'start_time' => $start_time);
            $hik_community_person_info_str = json_encode($hik_community_person_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $save_bind_data = array('hik_community_person_info' => $hik_community_person_info_str);
            $save_bind_data['hik_community_card_no'] = $barCode;
            $houseVillageUserBind->saveOne(['pigcms_id' => $userBind['pigcms_id']], $save_bind_data);
            $codeUrl = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $barCode;
            $returnArr['codeUrl']=$codeUrl;
            $returnArr['effectTimeTip']="起效时间：".date('Y-m-d H:i:s');
            $expireTime=$start_time+$duration*60;
            $returnArr['expireTimeTip']='失效时间：'.date('Y-m-d H:i:s',$expireTime);
            $returnArr['openTimesTip']='';
            $returnArr['tip']='如果二维码过期了，可以点击图片可刷新';
            $returnArr['title']='请将二维码对准设备识别位置';
            return $returnArr;
        } elseif ($barCodeRet && isset($barCodeRet['msg']) && !empty($barCodeRet['msg'])) {
            $err_msg = $barCodeRet['msg'];
        }
        $returnArr['err_code'] = 1;
        $returnArr['err_msg'] = $err_msg;
        return $returnArr;
    }

    /**
     *开卡
     **/
    public function openCardBindings($village_id = 0, $pigcms_id = 0, $userBind = array(),$ic_card_num='')
    {
        $infoVisionICommunity = new infoVisionICommunity();
        $houseVillageUserBind = new HouseVillageUserBind();
        if (empty($userBind) && $pigcms_id > 0) {
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $pigcms_id);
            $whereArr[] = array('village_id', '=', $village_id);
            $userBind = $houseVillageUserBind->getOne($whereArr, '*');
            if ($userBind && !$userBind->isEmpty()) {
                $userBind = $userBind->toArray();
            }
        }
        $returnArr = array('err_code' => 0, 'err_msg' => '');
        if (empty($userBind) || !isset($userBind['pigcms_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['param'] = array('village_id' => $village_id, 'pigcms_id' => $pigcms_id);
            $returnArr['err_msg'] = '人员信息不存在！';
            return $returnArr;
        }
        if (empty($userBind['hik_community_person_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['err_msg'] = '人员未添加！';
            return $returnArr;
        }
        
        $cardDataPost = array();
        $cardItem = array();
        if ($userBind['type'] == 6) {
            //临时人员
            $cardItem['cardNo'] = '70009900' . $userBind['pigcms_id'];
        } else {
            if($ic_card_num){
                $cardItem['cardNo'] = $ic_card_num;
            }elseif  (!empty($userBind['ic_card'])) {
                $ic_card_num=$userBind['ic_card'];
                $cardItem['cardNo'] = $userBind['ic_card'];
            } else {
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = '没有IC卡号！';
                return $returnArr;
            }
        }
        $hik_community_person_info = array();
        if ($userBind && isset($userBind['hik_community_person_info']) && !empty($userBind['hik_community_person_info'])) {
            $hik_community_person_info = is_array($userBind['hik_community_person_info']) ? $userBind['hik_community_person_info'] : json_decode($userBind['hik_community_person_info'], true);
            $hik_community_person_info = $hik_community_person_info ? $hik_community_person_info : array();
            if(isset($hik_community_person_info['openCard']) && $ic_card_num && isset($hik_community_person_info['openCard'][$ic_card_num])){
                $returnArr['err_code'] = 1;
                $returnArr['err_msg'] = 'IC卡号为'.$ic_card_num.'已经开过卡了！';
                return $returnArr;
            }
        }
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = ['app_key', '=', $app_key];
        $whereArr[] = array('from', '=', 'house');
        $roomCode = '';
        $communityRegions = $dbHikInfoVisionCommunityRegions->getOne($whereArr);
        if ($communityRegions && !$communityRegions->isEmpty()) {
            $communityRegions = $communityRegions->toArray();
            $roomCode = $communityRegions['index_code'];
        }
        $cardItem['personId'] = $userBind['hik_community_person_id'];
        if ($roomCode) {
            $cardItem['orgIndexCode'] = $roomCode;
        }
        $cardItem['cardType'] = 1;
        $cardDataPost['cardList'] = array();
        $cardDataPost['cardList'][] = $cardItem;
        $openCard = $infoVisionICommunity->addOpenCard($cardDataPost);
        fdump_api(['cardDataPost'=>$cardDataPost,'ret'=>$openCard], 'infoVisionICommunity/openCardBindings',1);
        $err_msg = '开卡失败了';
        if ($openCard && isset($openCard['data']) && !empty($openCard['data'])) {
            $cardId = $openCard['data']['0']['cardId'];
            $cardNo = $openCard['data']['0']['cardNo'];
            $returnArr['err_code'] = 0;
            $returnArr['card_no'] = $cardNo;
            $returnArr['bar_code'] = $cardNo;
            $returnArr['card_id'] = $cardId;
            $returnArr['person_id'] = $userBind['hik_community_person_id'];
            if(isset($hik_community_person_info['openCard']) && !empty($hik_community_person_info['openCard'])){
                $hik_community_person_info['openCard'][$cardNo] = array('card_no' => $cardNo,'ic_card_num'=>$ic_card_num, 'card_id' => $cardId);
            }else{
                $hik_community_person_info['openCard']=array();
                $hik_community_person_info['openCard'][$cardNo] = array('card_no' => $cardNo,'ic_card_num'=>$ic_card_num, 'card_id' => $cardId);
            }
            $hik_community_person_info_str = json_encode($hik_community_person_info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $save_bind_data = array('hik_community_person_info' => $hik_community_person_info_str);
            if($userBind['type'] == 6){
                $save_bind_data['hik_community_card_no'] = $cardNo;
            }
            $houseVillageUserBind->saveOne(['pigcms_id' => $userBind['pigcms_id']], $save_bind_data);

            return $returnArr;
        } elseif ($openCard && isset($openCard['msg']) && !empty($openCard['msg'])) {
            $err_msg = $openCard['msg'];
        }
        $returnArr['err_code'] = 1;
        $returnArr['err_msg'] = $err_msg;
        return $returnArr;
    }
    public function sycBindUserToHikCommunity($village_id=0,$bind_pigcms_id=0,$floor_id_str='',$device_id=0){
        $dataParam=array('village_id'=>$village_id,'bind_pigcms_id'=>$bind_pigcms_id);
        $dataParam['floor_id_str']=$floor_id_str;
        $dataParam['device_id']=$device_id;
        $dataParam['handle_type']='sycBindUserToHikCommunity';
        $retInfo = $this->traitCommonHikInfoVisionCommunity($dataParam);
        return array('retInfo' => $retInfo);
    }
    /***
     * 对列同步人员 人脸信息
    */
    public function pushBindUserToHikCommunity($datas = array())
    {

        $village_id = 0;
        if ($datas && isset($datas['village_id'])) {
            $village_id = intval($datas['village_id']);
        }
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $root = dirname(dirname(dirname(__DIR__)));
        $root = dirname(dirname(dirname($root)));
        $basePath = rtrim($root, '/');
        $_SERVER['DOCUMENT_ROOT'] = $basePath;
        fdump_api(['app_key' => $app_key, 'datas' => $datas,'basePath'=>$basePath], 'infoVisionICommunity/pushBindUserToHikCommunity',1);

        if ($village_id < 1) {
            return true;
        }
        $bind_pigcms_id = 0;
        if ($datas && isset($datas['bind_pigcms_id'])) {
            $bind_pigcms_id = intval($datas['bind_pigcms_id']);
        }
        $floor_id_str = '';
        if ($datas && isset($datas['floor_id_str'])) {
            $floor_id_str = trim($datas['floor_id_str']);
        }
        $device_id = 0;
        if ($datas && isset($datas['device_id'])) {
            $device_id = trim($datas['device_id']);
        }
        if ($bind_pigcms_id > 0) {
            $houseVillageUserBind = new HouseVillageUserBind();
            $whereArr = array();
            $whereArr[] = array('pigcms_id', '=', $bind_pigcms_id);
            $whereArr[] = array('village_id', '=', $village_id);
            $userBind = $houseVillageUserBind->getOne($whereArr, '*');
            if ($userBind && !$userBind->isEmpty()) {
                $userBind = $userBind->toArray();
            }
            if(empty($userBind)){
                fdump_api(['datas' => $datas,'err_msg'=>'房间人员信息不存在！'], 'infoVisionICommunity/pushBindUserToHikCommunity',1);
                return true;
            }
            $returnArr='';
            if(!empty($userBind['hik_community_person_id'])){
                $personHouses=array('is_update'=>1);
                $returnArr = $this->addOrUpdateHousesPerson($village_id,$bind_pigcms_id,$userBind,$personHouses,true);
            }else{
                $returnArr = $this->handleBindUserToHikCommunity($village_id, $bind_pigcms_id,$userBind);
            }
            fdump_api(['returnArr' => $returnArr], 'infoVisionICommunity/pushBindUserToHikCommunity',1);
        } else {
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('status', '<>', 4);
            $whereArr[] = array('name', '<>', '');
            $whereArr[] = array('hik_community_person_id', '=', '');
            if ($floor_id_str) {
                $floor_id_arr = explode(',', $floor_id_str);
                $whereArr[] = array('floor_id', 'in', $floor_id_arr);
            }
            $app_key = cfg('hik_info_vision_i_community_app_key');
            $houseVillageUserBind = new HouseVillageUserBind();
            $fieldStr = 'pigcms_id,village_id,uid,name,phone,floor_id,parent_id,type,vacancy_id,id_card,single_id,layer_id,hik_community_person_id';
            $allDatas = $houseVillageUserBind->getList($whereArr, $fieldStr);
            fdump_api([__LINE__,'whereArr' => $whereArr], 'infoVisionICommunity/pushBindUserToHikCommunity',1);
            if ($allDatas && !$allDatas->isEmpty()) {
                $allDatas = $allDatas->toArray();
                $person_batch_add = array();
                $addCount = 0;
                $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
                $infoVisionICommunity = new infoVisionICommunity();
                foreach ($allDatas as $kk => $uvv) {
                        $hikCommunityUser = array();
                        $hikCommunityUser['personId'] = strval($uvv['pigcms_id']);
                        $hikCommunityUser['personName'] = '';
                        $whereArr = array();
                        $whereArr[] = array('village_id', '=', $village_id);
                        $whereArr[] = ['app_key', '=', $app_key];
                        if ($uvv['vacancy_id'] > 0) {
                            $whereArr[] = array('from', '=', 'room');
                            $whereArr[] = array('single_id', '=', $uvv['single_id']);
                            $whereArr[] = array('floor_id', '=', $uvv['floor_id']);
                            $whereArr[] = array('layer_id', '=', $uvv['layer_id']);
                            $whereArr[] = array('room_id', '=', $uvv['vacancy_id']);
                        } else {
                            $whereArr[] = array('from', '=', 'house');
                        }
                        $hikCommunityUser['orgIndexCode'] = '';
                        $communityRegions = $dbHikInfoVisionCommunityRegions->getOne($whereArr);
                        if ($communityRegions && !$communityRegions->isEmpty()) {
                            $communityRegions = $communityRegions->toArray();
                            $hikCommunityUser['orgIndexCode'] = $communityRegions['community_index_code'];
                        } else {
                            fdump_api(['bindUser' => $uvv,'err_msg'=>'未绑定海康智慧社区管理平台房屋'], 'infoVisionICommunity/pushBindUserToHikCommunity',1);
                            continue;
                        }
                        $hikCommunityUser['personName'] = $uvv['name'];
                        $hikCommunityUser['gender'] = 0;  //1：男；2：女；0：未知
                        if ($uvv['phone']){
                            $hikCommunityUser['phoneNo'] = $uvv['phone'];
                        }
                        $hikCommunityUser['tagId'] = $infoVisionICommunity->tag_id;
                        if ($uvv['id_card']) {
                            $hikCommunityUser['certificateType'] = '111';
                            $hikCommunityUser['certificateNo'] = $uvv['id_card'];
                            $idCardInfo = idCardAnalysis($uvv['id_card']);
                            if ($idCardInfo && is_array($idCardInfo)) {
                                $hikCommunityUser['birthday'] = $idCardInfo['birthday'];
                                $sex = intval($idCardInfo['sex']);
                                $hikCommunityUser['gender'] = $sex == 1 ? 1 : 2;
                            }
                        }
                        $hikCommunityUser['clientId']=$uvv['pigcms_id'];
                        $addCount++;
                        $person_batch_add[] = $hikCommunityUser;
                        if($addCount>800){
                            $person_batch_add_datas=$person_batch_add;
                            $person_batch_add=array();
                            $addCount=0;
                            $retBatchTmp = $infoVisionICommunity->addBatchPersonInfo($person_batch_add_datas);
                            unset($person_batch_add_datas);
                            fdump_api(['retBatchTmp'=>$retBatchTmp], 'infoVisionICommunity/pushBindUserToHikCommunity',1);
                            if($retBatchTmp && isset($retBatchTmp['data']) && !empty($retBatchTmp['data'])){
                                $successes=isset($retBatchTmp['data']['successes']) ? $retBatchTmp['data']['successes']:'';
                                $failures=isset($retBatchTmp['data']['failures']) ? $retBatchTmp['data']['failures']:'';
                                if(!empty($successes) && is_array($successes)){
                                    foreach ($successes as $svv) {
                                        $pigcms_id_tmp=$svv['clientId'];
                                        $whereArr=array('pigcms_id'=>$pigcms_id_tmp);
                                        $houseVillageUserBind->saveOne($whereArr,['hik_community_person_id'=>$svv['personId']]);
                                    }
                                }
                            }
                        }
                }
                unset($allDatas);
                if(!empty($person_batch_add)){
                    $retBatchTmp = $infoVisionICommunity->addBatchPersonInfo($person_batch_add);
                    fdump_api(['retBatchTmp'=>$retBatchTmp], 'infoVisionICommunity/pushBindUserToHikCommunity',1);
                    if($retBatchTmp && isset($retBatchTmp['data']) && !empty($retBatchTmp['data'])){
                        $successes=isset($retBatchTmp['data']['successes']) ? $retBatchTmp['data']['successes']:'';
                        $failures=isset($retBatchTmp['data']['failures']) ? $retBatchTmp['data']['failures']:'';
                        if(!empty($successes) && is_array($successes)){
                            foreach ($successes as $svv) {
                                $pigcms_id_tmp=$svv['clientId'];
                                $whereArr=array('pigcms_id'=>$pigcms_id_tmp);
                                $houseVillageUserBind->saveOne($whereArr,['hik_community_person_id'=>$svv['personId']]);
                            }
                        }
                    }
                }
                unset($person_batch_add);
            }
            
            //添加人脸和新增人员
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('status', '<>', 4);
            $whereArr[] = array('hik_community_person_id', '<>', '');
            if ($floor_id_str) {
                $floor_id_arr = explode(',', $floor_id_str);
                $whereArr[] = array('floor_id', 'in', $floor_id_arr);
            }
            $houseVillageUserBind = new HouseVillageUserBind();
            $fieldStr = 'pigcms_id,village_id,uid,name,phone,floor_id,parent_id,type,vacancy_id,property_endtime,property_starttime,add_face_time,face_img_status,face_img_reason,id_card,ic_card,single_id,layer_id,hik_community_person_id,hik_community_person_info,hik_community_card_no';
            $allDatas = $houseVillageUserBind->getList($whereArr, $fieldStr);
            if ($allDatas && !$allDatas->isEmpty()) {
                $allDatas = $allDatas->toArray();
                foreach ($allDatas as $bvv){
                    $retInfo=$this->addOrUpdateHousesPerson($village_id,$bvv['pigcms_id'],$bvv,array(),true);
                    fdump_api(['UpdateHousesPerson'=>$retInfo], 'infoVisionICommunity/pushBindUserToHikCommunity',1);
                }
            }
        }
        return true;
    }
    public function  updateBindUserToHikCommunity($village_id=0,$pigcms_id=0){
        $houseVillageUserBind = new HouseVillageUserBind();
        $whereArr = array();
        $whereArr[] = array('pigcms_id', '=', $pigcms_id);
        $whereArr[] = array('village_id', '=', $village_id);
        $userBind = $houseVillageUserBind->getOne($whereArr, '*');
        if ($userBind && !$userBind->isEmpty()) {
            $userBind = $userBind->toArray();
        }
        $returnArr = array('err_code' => 0, 'err_msg' => '');
        if (empty($userBind) || !isset($userBind['pigcms_id'])) {
            $returnArr['err_code'] = 1;
            $returnArr['param'] = array('village_id' => $village_id, 'pigcms_id' => $pigcms_id);
            $returnArr['err_msg'] = '人员信息不存在！';
            return $returnArr;
        }
        if(empty($userBind['hik_community_person_id'])){
            $returnArr = $this->handleBindUserToHikCommunity($village_id, $pigcms_id,$userBind);
        }else{
            $personHouses=array('is_update'=>1);
            $returnArr = $this->addOrUpdateHousesPerson($village_id,$pigcms_id,$userBind,$personHouses,true);
        }
        return $returnArr;
    }
    public function  deleteBindUserOfHikCommunity($village_id=0,$bind_pigcms_id=''){
        $returnArr = array('err_code' => 0, 'err_msg' => '');
        if($village_id<1 || empty($bind_pigcms_id)){
            $returnArr['err_code'] = 1;
            $returnArr['err_msg']='删除操作参数错误！';
            return $returnArr;
        }
        fdump_api(['village_id'=>$village_id,'bind_pigcms_id' => $bind_pigcms_id], 'infoVisionICommunity/deleteBindUserOfHikCommunity',1);
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        if (!is_array($bind_pigcms_id) && $bind_pigcms_id > 0) {
            $whereArr[] = array('pigcms_id', '=', $bind_pigcms_id);
        }elseif(is_array($bind_pigcms_id) && !empty($bind_pigcms_id)){
            $whereArr[] = array('pigcms_id', 'in', $bind_pigcms_id);
        }
        $whereArr[] = array('hik_community_person_id', '<>', '');
        $houseVillageUserBind = new HouseVillageUserBind();
        $fieldStr = 'pigcms_id,village_id,hik_community_person_id';
        $userBinds=$houseVillageUserBind->getList($whereArr,$fieldStr);
        if($userBinds && !$userBinds->isEmpty()){
            $userBinds=$userBinds->toArray();
            $hik_community_person_ids=array();
            $deleteCount = 0;
            foreach ($userBinds as $bvv){
                $hik_community_person_ids[]=$bvv['hik_community_person_id'];
                $deleteCount++;
                if($deleteCount>800){
                    $person_ids=$hik_community_person_ids;
                    $hik_community_person_ids=array();
                    $deleteCount=0;
                    $deleteRet= $this->deleteHikCommunityPerson($person_ids,$village_id);
                    $returnArr=$deleteRet;
                }
            }
            if(!empty($hik_community_person_ids)){
                $deleteRet= $this->deleteHikCommunityPerson($hik_community_person_ids,$village_id);
                $returnArr=$deleteRet;
            }
            return $returnArr;
        }
        $returnArr['err_code'] = 1;
        $returnArr['err_msg']='没有已对接的人员数据可删除！';
        return $returnArr;
    }
    
    public function deleteHikCommunityPerson($hik_community_person_ids=array(),$village_id=0){
        $returnArr = array('err_code' => 1, 'err_msg' => '删除失败！');
        if(!empty($hik_community_person_ids)){
            $houseVillageUserBind = new HouseVillageUserBind();
            $person_id_arr=array_unique ($hik_community_person_ids);
            $personIds=array('personIds'=>$person_id_arr);
            $infoVisionICommunity = new infoVisionICommunity();
            $retBatchTmp = $infoVisionICommunity->deletePersonInfoByCondition($personIds);
            fdump_api(['hik_community_person_ids'=>$personIds,'retBatchTmp' => $retBatchTmp], 'infoVisionICommunity/deleteBindUserOfHikCommunity',1);
            if($retBatchTmp && isset($retBatchTmp['code']) && $retBatchTmp['code']=='0' &&  $retBatchTmp['msg']=='success'){
                $returnArr['err_code']=0;
                $returnArr['err_msg']='删除成功！';
                $failPersonIds=array();
                if(isset($retBatchTmp['data']) && !empty($retBatchTmp['data'])){
                    foreach ($retBatchTmp['data'] as $dvv){
                        if(isset($dvv['message']) && $dvv['message'] && (strpos($dvv['message'], 'PersonId Is Not Exists') !== false)){
                             //不存在了 删掉
                        }else{
                            $failPersonIds[]=$dvv['personId'];
                        }
                        
                    }
                }
                foreach ($person_id_arr as $person_id){
                    if(!in_array($person_id,$failPersonIds)){
                        $whereArr=array();
                        $whereArr[]=array('village_id','=',$village_id);
                        $whereArr[]=array('hik_community_person_id','=',$person_id);
                        $saveArr=array('hik_community_person_id'=>'');
                        $hik_community_person_info=array();
                        $hik_community_person_info['delete']=array('deltime'=>date('Y-m-d H:i:s'),'hik_community_person_id'=>$person_id);
                        $saveArr['hik_community_person_info']=json_encode($hik_community_person_info,JSON_UNESCAPED_UNICODE);
                        $houseVillageUserBind->saveOne($whereArr,$saveArr);
                    }
                }
            }else{
                $returnArr['err_code']=1;
                if($retBatchTmp && isset($retBatchTmp['msg']) && !empty($retBatchTmp['msg'])){
                    $returnArr['err_msg']=$retBatchTmp['msg'];
                }
            }
        }
        return $returnArr;
    }
    public function delHouseFaceImg($village_id=0,$id=0){
        $returnArr = array('err_code' => 0, 'err_msg' => '');
        if($village_id>0 && $id>0){
            $dbHouseFaceImg = new HouseFaceImg();
            $whereArr = array();
            $whereArr[] = ['id', '=', $id];
            $oneFaceImg = $dbHouseFaceImg->getOne($whereArr, '*');
            if($oneFaceImg && !$oneFaceImg->isEmpty()){
                $oneFaceImg=$oneFaceImg->toArray();
                if(in_array($oneFaceImg['status'],array(1,2,4)) && $oneFaceImg['hik_community_face_id']){
                    $infoVisionICommunity = new infoVisionICommunity();
                    $delFaceData=array('faceId'=>$oneFaceImg['hik_community_face_id']);
                    $retDelFace = $infoVisionICommunity->deletePersonFaceInfo($delFaceData);
                    $returnArr['del']=$retDelFace;
                    if($retDelFace && isset($retDelFace['code']) && ($retDelFace['code']===0|| $retDelFace['code']==='0')){
                        $saveDelArr=array('hik_community_face_id'=>'','del_time'=>time());
                        $dbHouseFaceImg->saveOne(['id'=>$id],$saveDelArr);
                    }
                }
            }
        }
        return $returnArr;
    }
    /**
     * 查询门禁列表v2.
     */
    public function getAcsDoorSearch($pageNo = 1, $pageSize = 100, $paramSearch = []) {
        $infoVisionICommunity = new infoVisionICommunity();
        $config = $infoVisionICommunity->getConfig();
        $app_key = $config['app_key'];
        $result = $infoVisionICommunity->getAcsDoorSearch($pageNo, $pageSize);
        $nowTime = time();
        $a3_device_id = isset($paramSearch['a3_device_id']) && $paramSearch['a3_device_id'] ? $paramSearch['a3_device_id'] : '';
        !$a3_device_id && $a3_device_id = md5(uniqid().$app_key.$nowTime);
        $has = false;
        if (isset($result['data']['list'])) {
            $list = $result['data']['list'];
            $data = [];
            foreach ($list as $item) {
                $has = true;
                $item['a3_device_id'] = $a3_device_id;
                $param = $this->handleSingleDoor($item, $app_key, $nowTime);
                $data[] = $param;
            }
            fdump_api(['app_key' => $app_key,  'data' => $data], 'infoVisionICommunity/handleSingleDoorLog',1);
        }
        $total = isset($result['data']['total']) && intval($result['data']['total']) ? intval($result['data']['total']) : 0;
        $last = true;
        if ($total > ($pageNo * $pageSize)) {
            // todo 转成队列执行
            $last = false;
            $pageNo = $pageNo + 1;
            $this->traitCommonHikInfoVisionCommunity(['pageNo' => $pageNo, 'pageSize' => $pageSize, 'a3_device_id' => $a3_device_id, 'handle_type' => 'getAcsDoorSearch']);
        }
        if ($last && $has && $a3_device_id) {
            $whereDel = [];
            $whereDel[] =  ['thirdProtocol', '=', DeviceConst::HIK_INFO_VISION_I_COMMUNITY];
            $whereDel[] =  ['a3_device_id', '<>', $a3_device_id];
            $del_data = [
                'is_del' => 4,
                'last_time' => $nowTime,
                'device_sn' => '~no_use',
                'is_syn_del' => 1,
            ];
            (new HouseFaceDevice())->saveData($whereDel, $del_data);
        }
        return $result;
    }

    /**
     * 单个处理更新设备信息.
     */
    public function handleSingleDoor($door, $app_key, $nowTime = 0) {
        $keyArr = [];
        $indexCode = isset($door['indexCode']) && $door['indexCode'] ? trim($door['indexCode']) : '';
        $keyArr[] = 'indexCode';
        $name = isset($door['name']) && $door['name'] ? trim($door['name']) : '';
        $keyArr[] = 'name';
        $ip = isset($door['ip']) && $door['ip'] ? trim($door['ip']) : '';
        $keyArr[] = 'ip';
        $port = isset($door['port']) && $door['port'] ? trim($door['port']) : '';
        $keyArr[] = 'port';
        $regionIndexCode = isset($door['regionIndexCode']) && $door['regionIndexCode'] ? trim($door['regionIndexCode']) : '';
        $keyArr[] = 'regionIndexCode';
        
        $dataVersion = isset($door['dataVersion']) && $door['dataVersion'] ? trim($door['dataVersion']) : '';
        $keyArr[] = 'dataVersion';
        $createTime = isset($door['createTime']) && $door['createTime'] ? strtotime($door['createTime']) : 0;
        $keyArr[] = 'createTime';
        $updateTime = isset($door['updateTime']) && $door['updateTime'] ? strtotime($door['updateTime']) : 0;
        $keyArr[] = 'updateTime';
        $devTypeDesc = isset($door['devTypeDesc']) && $door['devTypeDesc'] ? trim($door['devTypeDesc']) : '';
        $keyArr[] = 'devTypeDesc';
        $devSerialNum = isset($door['devSerialNum']) && $door['devSerialNum'] ? trim($door['devSerialNum']) : '';
        $manufacturer = isset($door['manufacturer']) && $door['manufacturer'] ? trim($door['manufacturer']) : '';
        $a3_device_id = isset($door['a3_device_id']) && $door['a3_device_id'] ? trim($door['a3_device_id']) : '';
        $regionPathName = isset($door['regionPathName']) && $door['regionPathName'] ? trim($door['regionPathName']) : '';
        $keyArr[] = 'manufacturer';
        
        $thirdProtocol = DeviceConst::HIK_INFO_VISION_I_COMMUNITY;
        $whereUnique = [];
        $whereUnique[] = ['thirdProtocol', '=', $thirdProtocol];
        $whereUnique[] = ['cloud_device_id', '=', $indexCode];
        $db_house_face_device = new HouseFaceDevice();
        $deviceInfo = $db_house_face_device->getOne($whereUnique, 'device_id');
        $deviceData = [];
        $a3_device_id && $deviceData['a3_device_id'] = $a3_device_id;
        $name && $deviceData['device_name'] = $name;
        $name && $deviceData['cloud_device_name'] = $name;
        $deviceData['device_type'] = 5;
        $devSerialNum && $deviceData['device_sn'] = $devSerialNum;
        $regionPathName && $deviceData['thirdAddress'] = $regionPathName;

        $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
        $houseWhere = array();
        $houseWhere[] = ['app_key', '=', $app_key];
        $houseWhere[] = ['index_code', '=', $regionIndexCode];
        $houseInfo = $dbHikInfoVisionCommunityRegions->getOne($houseWhere);
        isset($houseInfo['village_id']) && $houseInfo['village_id'] && $deviceData['village_id'] = $houseInfo['village_id'];
        isset($houseInfo['floor_id']) && $houseInfo['floor_id'] && $deviceData['floor_id'] = $houseInfo['floor_id'];
        if ((isset($houseInfo['village_id']) && !$houseInfo['village_id']) && $houseInfo['community_index_code']) {
            $communityWhere = array();
            $communityWhere[] = ['app_key', '=', $app_key];
            $communityWhere[] = ['index_code', '=', $houseInfo['community_index_code']];
            $communityInfo = $dbHikInfoVisionCommunityRegions->getOne($communityWhere);
            isset($communityInfo['village_id']) && $communityInfo['village_id'] && $deviceData['village_id'] = $communityInfo['village_id'];
        }
        if ($ip && $port) {
            $deviceData['device_domain'] = $ip . ':' . $port;
        }
        $ip && $deviceData['device_ip'] = $ip;
        $port && $deviceData['thirdPort'] = $port;
        $dataVersion && $deviceData['sdk_version'] = $dataVersion;
        
        !$nowTime && $nowTime = time();
        $deviceData['login_time'] = $nowTime;
        $deviceData['add_time'] = $createTime ? $createTime : $nowTime;
        $deviceData['last_time'] = $updateTime ? $updateTime : $nowTime;
        $deviceData['cloud_device_id'] = $indexCode;
        $devTypeDesc && $deviceData['cloud_device_model'] = $devTypeDesc;
        $regionIndexCode && $deviceData['cloud_group_id'] = $regionIndexCode;

        $deviceData['is_del'] = 0;
        $deviceData['device_brand'] = 'brand_haikang';
        $manufacturer && $deviceData['brand_type'] = $manufacturer;
        $deviceData['thirdProtocol'] = $thirdProtocol;
        $device_info = unsetParam($door, $keyArr);
        $device_info && $deviceData['device_info'] = json_encode($device_info, JSON_UNESCAPED_UNICODE);
        
        if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
            $db_house_face_device->saveData(['device_id' => $deviceInfo['device_id']], $deviceData);
            $deviceData['device_id'] = $deviceInfo['device_id'];
        } else {
            $deviceData['device_id'] = $db_house_face_device->addData($deviceData);
        }
        return $deviceData;
    }

    /**
     * 获取对应门禁设备点(通道).
     */
    public function getDoorSearch($pageNo = 1, $pageSize = 100, $parentIndexCode = '', $expressions = [], $paramSearch = []) {
        $infoVisionICommunity = new infoVisionICommunity();
        $config = $infoVisionICommunity->getConfig();
        $app_key = $config['app_key'];
        $result = $infoVisionICommunity->getDoorSearch($pageNo, $pageSize, $parentIndexCode, $expressions);
        $nowTime = time();
        $data = [];
        $ipcSerial = isset($paramSearch['ipcSerial']) && $paramSearch['ipcSerial'] ? $paramSearch['ipcSerial'] : '';
        !$ipcSerial && $ipcSerial = md5(uniqid().$app_key.$nowTime);
        $has = false;
        if (isset($result['data']['list'])) {
            $list = $result['data']['list'];
            foreach ($list as $item) {
                $has = true;
                $item['ipcSerial'] = $ipcSerial;
                $param = $this->handleSingleDoorChannel($item, $app_key, $nowTime);
                $data[] = $param;
            }
            fdump_api(['app_key' => $app_key,  'data' => $data], 'infoVisionICommunity/handleSingleDoorChannelLog',1);
        }

        $last = true;
        $total = isset($result['data']['total']) && intval($result['data']['total']) ? intval($result['data']['total']) : 0;
        if ($total > ($pageNo * $pageSize)) {
            $last = false;
            $pageNo = $pageNo + 1;
            // todo 转成队列执行
            return $this->getDoorSearch($pageNo, $pageSize, $parentIndexCode, $expressions);
        }
        if ($last && $has && $ipcSerial) {
            $whereChannel = [];
            $whereChannel[] = ['device_type', '=', 'house_face_device'];
            $whereChannel[] = ['deviceId', '=', $parentIndexCode];
            $whereChannel[] = ['isDel', '=', 0];
            $whereChannel[] = ['ipcSerial', '<>', $ipcSerial];
            $delData = [
                'isDel' => $nowTime,
            ];
            (new HouseDeviceChannel())->updateThis($whereChannel, $delData);
        }
        return $data;
    }

    /**
     * 处理记录单个通道记录
     */
    public function handleSingleDoorChannel($channel, $app_key, $nowTime) {
        ! $nowTime && $nowTime = time();
        $keyArr = [];
        $indexCode = isset($channel['indexCode']) && $channel['indexCode'] ? $channel['indexCode'] : '';
        $name = isset($channel['name']) && $channel['name'] ? $channel['name'] : '';
        $keyArr[] = 'name';
        $channelNo = isset($channel['channelNo']) && $channel['channelNo'] ? $channel['channelNo'] : '';
        $keyArr[] = 'channelNo';
        $parentIndexCode = isset($channel['parentIndexCode']) && $channel['parentIndexCode'] ? $channel['parentIndexCode'] : '';
        $createTime = isset($channel['createTime']) && $channel['createTime'] ? strtotime($channel['createTime']) : $nowTime;
        $keyArr[] = 'createTime';
        $updateTime = isset($channel['updateTime']) && $channel['updateTime'] ? strtotime($channel['updateTime']) : $nowTime;
        $keyArr[] = 'updateTime';
        $ipcSerial = isset($channel['ipcSerial']) && $channel['ipcSerial'] ? $channel['ipcSerial'] : '';
        $keyArr[] = 'ipcSerial';
        $whereDevice = [];
        $whereDevice[] = ['cloud_device_id', '=', $parentIndexCode];
        $db_house_face_device = new HouseFaceDevice();
        $deviceInfo = $db_house_face_device->getOne($whereDevice, 'device_id, village_id, device_sn');
        $whereUnique = [
            'device_type' => 'house_face_device',
            'deviceId' => $parentIndexCode,
            'isDel' => 0,
            'channelId' => $indexCode,
        ];
        $db_house_device_channel = new HouseDeviceChannel();
        $channelInfo = $db_house_device_channel->getOne($whereUnique, 'channel_id');
        $channel = [
            'device_type' => 'house_face_device',
            'deviceId' => $parentIndexCode,
            'isDel' => 0,
            'channelId' => $indexCode,
            'channelName' => $name,
            'channelNo' => $channelNo,
            'add_time' => $createTime,
            'update_time' => $updateTime,
            'ipcSerial' => $ipcSerial,
        ];
        isset($deviceInfo['device_id']) && $deviceInfo['device_id'] && $channel['device_id'] = $deviceInfo['device_id'];
        isset($deviceInfo['village_id']) && $deviceInfo['village_id'] && $channel['village_id'] = $deviceInfo['village_id'];
        isset($deviceInfo['device_sn']) && $deviceInfo['device_sn'] && $channel['deviceSerial'] = $deviceInfo['device_sn'];
        $channel_txt = unsetParam($channel, $keyArr);
        $channel_txt && $channel['channel_txt'] = json_encode($channel_txt, JSON_UNESCAPED_UNICODE);
        if (isset($channelInfo['channel_id']) && $channelInfo['channel_id']) {
            $db_house_device_channel->updateThis(['channel_id' => $channelInfo['device_id']], $channel);
            $channel['device_id'] = $channelInfo['channel_id'];
        } else {
            $channel['device_id'] = $db_house_device_channel->add($channel);
        }
        $channel['indexCode'] = $indexCode;
        $channel['parentIndexCode'] = $parentIndexCode;
        return $channel;
    }

    /**
     * 获取设备状态.
     */
    public function getDoorState($device_id, $doorIndexCode = '') {
        $thirdProtocol = DeviceConst::HIK_INFO_VISION_I_COMMUNITY;
        if (! $doorIndexCode) {
            $whereDevice = [];
            $whereDevice[] = ['thirdProtocol', '=', $thirdProtocol];
            $whereDevice[] = ['device_id', '=', $device_id];
            $device_info = (new HouseFaceDevice())->getOne($whereDevice, 'device_id, cloud_device_id');
            $doorIndexCode = isset($device_info['cloud_device_id']) && $device_info['cloud_device_id'] ? $device_info['cloud_device_id'] : '';
        }
        $result = $this->getDoorSearch(1, 100, $doorIndexCode);
        if (! empty($doorIndexCode)) {
            $doorIndexCodes = array_diff(array_unique(array_column($result, 'indexCode')), [0, '0', null]);
        } else {
            return [
                'code' => 1,
                'msg' => '缺少门禁点信息'
            ];
        }
        $infoVisionICommunity = new infoVisionICommunity();
        $result = $infoVisionICommunity->getDoorStates('', $doorIndexCodes);
        if (isset($result['data']['authDoorList'])) {
            $authDoorList = $result['data']['authDoorList'];
            $db_house_face_device = new HouseFaceDevice();
            $db_house_device_channel = new HouseDeviceChannel();
            $nowTime = time();
            $device_status = 2;
            foreach ($authDoorList as $door) {
                $whereUnique = [
                    'isDel' => 0,
                    'device_type' => 'house_face_device',
                    'deviceId' => $doorIndexCode,
                    'channelId' => $door['doorIndexCode'],
                ];
                $channel = [
                    'channelStatus' => isset($door['doorState']) ? intval($door['doorState']) : -1,
                ];
                $db_house_device_channel->updateThis($whereUnique, $channel);
                if (isset($door['doorState']) && intval($door['doorState']) != 3) {
                    $device_status = 1;
                }
            }
            $whereDevice = [];
            $whereDevice[] = ['thirdProtocol', '=', $thirdProtocol];
            $whereDevice[] = ['device_id', '=', $device_id];
            $deviceInfo = $db_house_face_device->getOne($whereDevice, 'device_id');
            if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                $deviceData = [];
                // 门状态，0 初始状态，1 开门状态，2关门状态，3离线状态
                $deviceData['device_status'] = $device_status;
                $deviceData['login_time'] = $nowTime;
                $deviceData['thirdChnNum'] = count($authDoorList);
                $db_house_face_device->saveData(['device_id' => $deviceInfo['device_id']], $deviceData);
            }
        }
        return $result;
    }

    /**
     * 遍历执行获取设备最新状态.
     */
    public function getAllDeviceState() {
        $thirdProtocol = DeviceConst::HIK_INFO_VISION_I_COMMUNITY;
        $whereDevice = [];
        $whereDevice[] = ['thirdProtocol', '=', $thirdProtocol];
        $whereDevice[] = ['is_del', '=', 0];
        $whereDevice[] = ['cloud_device_id', '<>', ''];
        $deviceList = (new HouseFaceDevice())->getList($whereDevice, 'device_id, cloud_device_id', 1);
        foreach ($deviceList as $key => $device) {
            $this->traitCommonHikInfoVisionCommunity(['device_id' => $device['device_id'], 'doorIndexCode' => $device['cloud_device_id'], 'handle_type' => 'getDoorState'], $key * 10);
        }
        return true;
    }
    
    /**
     * 远程开门.
     */
    public function controlDoorState($channel_id, $doorIndexCode = '') {
        if (! $channel_id && ! $doorIndexCode) {
            return [
                'code' => 1,
                'msg' => '缺少门禁'
            ];
        }
        if (! $doorIndexCode) {
            $whereUnique = [
                'device_type' => 'house_face_device',
                'channel_id' => $channel_id,
            ];
            $db_house_device_channel = new HouseDeviceChannel();
            $channelInfo = $db_house_device_channel->getOne($whereUnique, 'channel_id, channelId');
            $doorIndexCode = isset($channelInfo['channelId']) && $channelInfo['channelId'] ? $channelInfo['channelId'] : '';
        }
        $infoVisionICommunity = new infoVisionICommunity();
        $result = $infoVisionICommunity->doorDoControl($doorIndexCode);
        if (isset($result['data']) && $result['data']) {
            $data = reset($result['data']);
            if (intval($data['controlResultCode']) > 0) {
                $msg = intval($data['controlResultCode']) == 49348914 ? '设备不在线' : $data['controlResultDesc'];
                return [
                    'code' => $data['controlResultCode'],
                    'msg' => $msg
                ];
            }
        }
        return $result;
    }

    /**
     * 获取相关事件.
     */
    public function getDoorEvents() {
        $infoVisionICommunity = new infoVisionICommunity();
        $config = $infoVisionICommunity->getConfig();
        $app_key = $config['app_key'];
        if (! $app_key) {
            return [];
        }
        $db_house_user_log = new HouseUserLog();
        $whereUserLog = [];
        $whereUserLog[] = ['third_protocol', '=', DeviceConst::HIK_INFO_VISION_I_COMMUNITY];
        $whereUserLog[] = ['log_time', '>', 100];
        $log_time_max = $db_house_user_log->getMax($whereUserLog,'log_time');
        if ($log_time_max > 100) {
            $startTime = date('Y-m-d\TH:i:s',$log_time_max) . '+08:00';
            $endTime = date('Y-m-d\TH:i:s') . '+08:00';
        } else {
            $startTime = '';
            $endTime = '';
        }
        $hostname = $config['hostname'];
        $port = $config['port'];
        $result = $infoVisionICommunity->getDoorEvents(1, 100, $startTime, $endTime);
        $nowTime = time();
        $data = [];
        if (isset($result['data']['list'])) {
            $list = $result['data']['list'];
            foreach ($list as $item) {
                $param = $this->handleSingleDoorEvents($item, $app_key, $nowTime, $hostname, $port);
                $data[] = $param;
            }
            fdump_api(['app_key' => $app_key,  'data' => $data], 'infoVisionICommunity/getDoorEventsLog',1);
        }
        return $data;
    }

    /**
     * 单个处理时间记录.
     */
    public function handleSingleDoorEvents($event, $app_key, $nowTime, $hostname, $port) {
        !$nowTime && $nowTime = time();
        $keyArr = [];
        $eventId = isset($event['eventId']) && trim($event['eventId']) ? trim($event['eventId']) : '';
        $keyArr[] = 'eventId';
        $eventTime = isset($event['eventTime']) && trim($event['eventTime']) ? trim($event['eventTime']) : '';
        $receiveTime = isset($event['receiveTime']) && trim($event['receiveTime']) ? trim($event['receiveTime']) : '';
        $personId = isset($event['personId']) && trim($event['personId']) ? trim($event['personId']) : '';
        $cardNo = isset($event['cardNo']) && trim($event['cardNo']) ? trim($event['cardNo']) : '';
        $keyArr[] = 'cardNo';
        $personName = isset($event['personName']) && trim($event['personName']) ? trim($event['personName']) : '';
        $keyArr[] = 'personName';
        $doorName = isset($event['doorName']) && trim($event['doorName']) ? trim($event['doorName']) : '';
        $keyArr[] = 'doorName';
        $doorIndexCode = isset($event['doorIndexCode']) && trim($event['doorIndexCode']) ? trim($event['doorIndexCode']) : '';
        $doorRegionIndexCode = isset($event['doorRegionIndexCode']) && trim($event['doorRegionIndexCode']) ? $event['doorRegionIndexCode'] : '';
        $picUri = isset($event['picUri']) && trim($event['picUri']) ? trim($event['picUri']) : '';
        $eventType = isset($event['eventType']) && trim($event['eventType']) ? trim($event['eventType']) : '';
        //进出类型(1：进 0：出 -1:未知 要求：进门读卡器拨码设置为1，出门读卡器拨码设置为2 )
        $inAndOutType = isset($event['inAndOutType']) && trim($event['inAndOutType']) ? trim($event['inAndOutType']) : '';
        $devIndexCode = isset($event['devIndexCode']) && trim($event['devIndexCode']) ? trim($event['devIndexCode']) : '';
        $devName = isset($event['devName']) && trim($event['devName']) ? trim($event['devName']) : '';
        $identityCardUri = isset($event['identityCardUri']) && trim($event['identityCardUri']) ? trim($event['identityCardUri']) : '';

        $log_time = $eventTime ? strtotime($eventTime) : '';
        !$log_time && $log_time = $receiveTime ? strtotime($receiveTime) : '';
        !$log_time && $log_time = $nowTime;
        
        $third_protocol = DeviceConst::HIK_INFO_VISION_I_COMMUNITY;
        $log = [
            'eventId' => $eventId,
            'log_time' => $log_time,
            'third_protocol' => $third_protocol,
        ];
        // 1 人脸识别门禁 2用户操作开门 3 IC卡开门 6 摄像机抓拍  21 蓝牙开门记录  31 二维码开门   51 高空抛物
        $infoVisionICommunity = new infoVisionICommunity();
        $faceDoorSuccess = $infoVisionICommunity->getFaceDoorSuccess();
        $faceDoorErr = $infoVisionICommunity->getFaceDoorErr();
        $icCardSuccess = $infoVisionICommunity->getUserIcCardDoorSuccess();
        $icCardErr = $infoVisionICommunity->getUserIcCardDoorErr();
        $eventType = intval($eventType);
        if (in_array($eventType, $faceDoorSuccess)) {
            $log_from = 1;
            $log_status = 0;
        } elseif (in_array($eventType, $faceDoorErr)) {
            $log_from = 1;
            $log_status = 1;
        } elseif ($eventType == 198919) {
            // 远程软件开门
            $log_from = 2;
            $log_status = 0;
        } elseif ($eventType == 198922) {
            // 远程软件常开	198922
            $log_from = 71;
            $log_status = 0;
        } elseif ($eventType == 198922) {
            // 远程软件常闭	199172
            $log_from = 72;
            $log_status = 0;
        } elseif ($eventType == 198922) {
            // 正常关门	199169
            $log_from = 73;
            $log_status = 0;
        } elseif ($eventType == 198922) {
            // 远程软件关门	199171
            $log_from = 73;
            $log_status = 0;
        } elseif (in_array($eventType, $icCardErr)) {
            $log_from = 3;
            $log_status = 1;
        } elseif (in_array($eventType, $icCardSuccess)) {
            $log_from = 2;
            $log_status = 0;
        }  elseif ($eventType == 199425) {
            // 胁迫卡比对通过	199425
            $log_from = 81;
            $log_status = 0;
        } elseif ($eventType == 983299) {
            // 刷卡开锁	983299
            $log_from = 3;
            $log_status = 0;
        } elseif ($eventType == 983308) {
            // 二维码开锁	983308
            $log_from = 31;
            $log_status = 0;
        }  elseif ($eventType == 983307) {
            // 蓝牙开锁	983307
            $log_from = 21;
            $log_status = 0;
        } else {
            return [];
        }
        $personName && $log['person_name'] = $personName;
        $personId && $log['person_code'] = $personId;
        $cardNo && $log['card_number'] = $cardNo;
        if (intval($inAndOutType) == 1) {
            $log['direction_type'] = 1;
        } elseif (intval($inAndOutType) == 0) {
            $log['direction_type'] = 2;
        }
        if ($personId || $cardNo) {
            $db_house_village_user_bind = new HouseVillageUserBind();
            $whereUserBind = [];
            $personId && $whereUserBind[] = ['hik_community_person_id', '=', intval($personId)];
            ! $personId && $cardNo && $whereUserBind[] = ['ic_card', '=', $cardNo];
            $userInfo = $db_house_village_user_bind->getOne($whereUserBind, 'pigcms_id, uid, village_id, name');
            if (isset($userInfo['pigcms_id']) && $userInfo['pigcms_id']) {
                $log['log_bind_id'] = $userInfo['pigcms_id'];
                isset($userInfo['uid']) && intval($userInfo['uid']) > 0 && $log['uid'] = $userInfo['uid'];
                isset($userInfo['village_id']) && intval($userInfo['village_id']) > 0 && $log['log_business_id'] = $userInfo['village_id'];
                isset($userInfo['name']) && trim($userInfo['name']) && !isset($log['person_name']) && $log['person_name'] = trim($userInfo['name']);
            }
        }
        $log['log_name'] = $doorName ? $doorName : $devName;
        $log['log_from'] = $log_from;
        $log['log_status'] = $log_status;

        $whereUnique = [
            'device_type' => 'house_face_device',
            'isDel' => 0,
            'channelId' => $doorIndexCode,
        ];
        $db_house_device_channel = new HouseDeviceChannel();
        $channelInfo = $db_house_device_channel->getOne($whereUnique, 'channel_id, village_id, device_id, deviceSerial');
        $findBelong = false; 
        if (isset($channelInfo['channel_id']) && $channelInfo['channel_id']) {
            $findBelong = true;
            $log['deviceChannelId'] = $channelInfo['channel_id'];
            !isset($log['log_business_id']) && isset($channelInfo['village_id']) && $channelInfo['village_id'] && $log['log_business_id'] = $channelInfo['village_id'];
            !isset($log['device_id']) && isset($channelInfo['device_id']) && $channelInfo['device_id'] && $log['device_id'] = $channelInfo['device_id'];
            !isset($log['device_sn']) && isset($channelInfo['deviceSerial']) && $channelInfo['deviceSerial'] && $log['device_sn'] = $channelInfo['deviceSerial'];
        }
        if (! $findBelong && $devIndexCode) {
            $whereUnique = [];
            $whereUnique[] = ['thirdProtocol', '=', $third_protocol];
            $whereUnique[] = ['cloud_device_id', '=', $devIndexCode];
            $db_house_face_device = new HouseFaceDevice();
            $deviceInfo = $db_house_face_device->getOne($whereUnique, 'device_id, village_id, device_sn');
            if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                $findBelong = true;
                !isset($log['device_id']) && $log['device_id'] = $deviceInfo['device_id'];
                !isset($log['log_business_id']) && isset($deviceInfo['village_id']) && $deviceInfo['village_id'] && $log['log_business_id'] = $deviceInfo['village_id'];
                !isset($log['device_sn']) && isset($deviceInfo['device_sn']) && $deviceInfo['device_sn'] && $log['device_sn'] = $deviceInfo['device_sn'];
            }
        }
        if (! $findBelong && $doorRegionIndexCode) {
            $dbHikInfoVisionCommunityRegions = new HikInfoVisionCommunityRegions();
            $whereRegion = [];
            $whereRegion[] = ['app_key', '=', $app_key];
            $whereRegion[] = ['index_code', '=', $doorRegionIndexCode];
            $regionInfo = $dbHikInfoVisionCommunityRegions->getOne($whereRegion, 'village_id');
            !isset($log['log_business_id']) && isset($regionInfo['village_id']) && $regionInfo['village_id'] && $log['log_business_id'] = $regionInfo['village_id'];
        }
        
        $log_detail = unsetParam($event, $keyArr);
        if ($picUri) {
            $picUri =  "https://" . $hostname . ':' . $port . $picUri; // 拼接图片地址
            fdump_api(['event' => $event, 'picUri' => $picUri], 'event', 1);
            $identityCardUri && $identityCardUri =  "https://" . $hostname . ':' . $port . $identityCardUri; // 拼接图片地址
            try {
                $face_img = $this->imgOnLineToLocal($picUri, '', $eventId . '.jpg');
                $identityCardUri && $identity_img = $this->imgOnLineToLocal($identityCardUri, '', $eventId . 'id.jpg');
            } catch (\Exception $e) {
                $face_img = $picUri;
                $identityCardUri && $identity_img = $identityCardUri;
            }
            $log_detail['face_img'] = $face_img;
            $log['cut_image'] = $log_detail['face_img'];
            isset($identity_img) && $identity_img && $log_detail['identity_img'] = $identity_img;
        }
        $log_detail['nowTime'] = $nowTime;
        $log['log_detail'] = json_encode($log_detail, JSON_UNESCAPED_UNICODE);
        if (isset($log_detail['face_img']) && trim($log_detail['face_img'])) {
            $log['cut_image'] = trim($log_detail['face_img']);
        }
        
        $db_house_user_log = new HouseUserLog();
        $whereUserLog = [];
        $whereUserLog[] = ['third_protocol', '=', $third_protocol];
        $whereUserLog[] = ['eventId', '=', $eventId];
        $logInfo = $db_house_user_log->getFind($whereUserLog, 'log_id');
        if (! isset($logInfo['log_id']) || !$logInfo['log_id']) {
            $log_id = $db_house_user_log->addData($log);
            $log['log_id'] = $log_id;
        } else {
            $log['log_id'] = $logInfo['log_id'];
        }
        return $log;
    }
    
    public function imgToLocal($picUri) {
        return $this->imgOnLineToLocal($picUri, '', '000019.jpg');
    }
}