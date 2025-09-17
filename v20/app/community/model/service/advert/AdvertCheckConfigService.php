<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物业和小区相关信息
 */
namespace app\community\model\service\advert;

use app\community\model\db\HouseVillageCheckauthApply;
use app\community\model\db\HouseVillageCheckauthSet;

class AdvertCheckConfigService
{
    /**
     * 获取广告审核配置信息
     */
    public function getAdvertCheckConfigSetInfo($params)
    {
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $xtype = isset($params['xtype']) ? trim($params['xtype']) : 'house_advert_check';
        // 检查是否有待审核申请 禁止修改
        $checkHasApply = isset($params['checkHasApply']) ? intval($params['checkHasApply']) : 1;
        $where = [];
        $data = [];
        if ($id) {
            $where['id'] = $id;
        }
        if ($property_id) {
            $where['property_id'] = $property_id;
            $data['property_id'] = $property_id;
        }
        if ($village_id) {
            $where['village_id'] = $village_id;
            $data['village_id'] = $village_id;
        }
        $where['xtype'] = $xtype;
        $data['xtype'] = $xtype;
        $dbHouseVillageCheckauthSet = new HouseVillageCheckauthSet();
        $info = $dbHouseVillageCheckauthSet->getOne($where);
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (isset($info['is_open'])) {
            $data['check_open'] = intval($info['is_open']) == 1 ? 1 : 0;
        } else {
            $data['check_open'] = 0;
        }
        if (isset($info['set_datas']) && $info['set_datas']) {
            $data['check_level'] = json_decode($info['set_datas'], true);
        } else {
            $data['check_level'] = 0;
        }
        if ($checkHasApply == 1) {
            $whereOrderModifyArr = [];
            $whereOrderModifyArr[] = ['village_id', '=', 0];
            $whereOrderModifyArr[] = ['property_id', '=', 0];
            // 后续拓展这里改成 in 查找
            $whereOrderModifyArr[] = ['xtype', '=', 'house_advert'];
            $whereOrderModifyArr[] = ['status', 'in', [0,1]];
            $applyModify = (new HouseVillageCheckauthApply())->getOne($whereOrderModifyArr);
            if (isset($applyModify['xtype']) && $applyModify['xtype'] == 'house_advert') {
                $data['msg'] = '由' . $applyModify['apply_name'] . '发起的广告申请审核，广告ID为' . $applyModify['order_id'];
            }
        }
        return $data;
    }
    
    /**
     * 设置广告审核配置.
     */
    public function advertCheckSet($params)
    {
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $xtype = isset($params['xtype']) ? trim($params['xtype']) : 'house_advert_check';
        $tname = isset($params['tname']) ? trim($params['tname']) : '小区广告审核设置';
        $is_open = isset($params['is_open']) ? intval($params['is_open']) : 0;
        $set_datas = isset($params['set_datas']) ? $params['set_datas'] : '';
        $dbHouseVillageCheckauthSet = new HouseVillageCheckauthSet();
        $where = [];
        $data = [];
        if ($id) {
            $where['id'] = $id;
        }
        if ($property_id) {
            $where['property_id'] = $property_id;
            $data['property_id'] = $property_id;
        }
        if ($village_id) {
            $where['village_id'] = $village_id;
            $data['village_id'] = $village_id;
        }
        $where['xtype'] = $xtype;
        $data['xtype'] = $xtype;
        $data['tname'] = $tname;
        $data['is_open'] = $is_open;
        if ($set_datas) {
            if (is_array($set_datas)) {
                $set_datas = json_encode($set_datas, JSON_UNESCAPED_UNICODE);
            }
            $data['set_datas'] = $set_datas;
        }
        $info = $dbHouseVillageCheckauthSet->getOne($where, 'id');
        if (isset($info['id']) && $info['id']) {
            $data['update_time'] = time();
            $id = $info['id'];
            $dbHouseVillageCheckauthSet->saveOne(['id' => $id], $data);
        } else {
            $data['add_time'] = time();
            $id = $dbHouseVillageCheckauthSet->add($data);
        }
        return $id;
    }
}