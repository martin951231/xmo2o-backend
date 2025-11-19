<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      设施类别
 */

class House_community_aboutModel extends Model
{
    public function getFacilitiesTypeSwitch() {
        return [
            ['label' => '供暖站', 'value' => 'supply_station'],
            ['label' => '开发商', 'value' => 'developer'],
        ];
    }
    
    public function getFacilitiesTypeText($value) {
        $typeArr = array_column($this->getFacilitiesTypeSwitch(), 'label', 'value');
        return isset($typeArr[$value]) ? $typeArr[$value] : '';
    }

    /**
     * 获取对应开发商或者供暖站列表.
     */
    public function getAllFacilitiesTypeList($facilities_type = 'supply_station') {
        $where = [
            'a.facilities_type' => $facilities_type,
            'a.village_id' => ['gt', 0],
            'a.delete_time' => 0,
        ];
        $villageIdArrs = $this->join(" as a left JOIN " . C('DB_PREFIX') . "house_village_config as b ON (a.village_id=b.village_id)")->where($where)->field('a.village_id')->select();
        $villageIds = [];
        foreach ($villageIdArrs as $villlageInfo) {
            $villageIds[] = $villlageInfo['village_id'];
        }
        $where = [
            'village_id' => ['in', $villageIds],
        ];
        $villageInfo = M('House_village')->where($where)->getField('village_id, village_id, village_name');
        $village_arr = [];
        foreach ($villageIds as $village_id) {
            if (isset($villageInfo[$village_id])) {
                $data = $villageInfo[$village_id];
                $data['facilities_type_text'] = $this->getFacilitiesTypeText($facilities_type);
                $village_arr[] = $data;
            }
        }
        return $village_arr;
    }

    /**
     * 绑定的设施记录.
     */
    public function villageBindFacilities($village_id, $bind_village_id, $data = [], $facilities_type = 'supply_station') {
        $property_id = isset($data['property_id']) ? intval($data['property_id']) : 0;
        $from = isset($data['from']) ? trim($data['from']) : '';
        $from_user = isset($data['from_user']) ? $data['from_user'] : '';
        $where = [
            'village_id' => $village_id,
            'bind_facilities_type' => $facilities_type,
        ];
        $db_house_community_about_bind = M('House_community_about_bind');
        $info = $db_house_community_about_bind->where($where)->field('id')->find();
        $dbData = [
            'village_id' => $village_id, 'bind_village_id' => $bind_village_id, 'bind_facilities_type' => $facilities_type,
        ];
        if ($property_id) {
            $dbData['property_id'] = $property_id;
        }
        if ($from) {
            $dbData['from'] = $from;
        }
        if ($from_user) {
            if (is_array($from_user)) {
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
            }
            $dbData['from_user'] = $from_user;
        }
        if (isset($info['id']) && $info['id']) {
            $dbData['bind_time'] = time();
            $db_house_community_about_bind->where($where)->save($dbData);
            $id = $info['id'];
        } else {
            $dbData['add_time'] = time();
           $id = $db_house_community_about_bind->add($dbData);
        }
        return $id;
    }


    /**
     * 获取绑定的设施记录.
     */
    public function getVillageBindFacilitiesInfo($village_id, $facilities_type = 'supply_station') {
        $where = [
            'village_id' => $village_id,
            'bind_facilities_type' => $facilities_type,
        ];
        $db_house_community_about_bind = M('House_community_about_bind');
        return $db_house_community_about_bind->where($where)->find();
    }
    
    /**
     * 删除关联.
     */
    public function deleteHouseCommunityAbout($village_id) {
        $where = [
            'village_id' => $village_id,
        ];
        return $this->where($where)->save(['delete_time' => time()]);
    }
    
    public function getCommunityAboutInfo($where) {
        return $this->where($where)->find();
    }
}