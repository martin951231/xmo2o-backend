<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物业质保信息
 */

namespace app\community\model\service\workorder;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageCheckauthApply;
use app\community\model\db\HouseVillageCheckauthSet;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\workorder\HouseCommunityQuality;
use app\community\model\db\workorder\HouseCommunityQualityApply;
use app\community\model\db\workorder\HouseCommunityQualityBindRepairCate;
use app\community\model\service\HouseVillageService;

class HouseCommunityQualityService
{
    public function getCheckStatus() {
        return [1, 2];
    }

    public function getOrderCheckSetInfo($params) {
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $xtype = isset($params['xtype']) ? trim($params['xtype']) : 'work_order_transfer_check';
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
            $data['work_order_transfer_check'] = intval($info['is_open']) == 1 ? 1 : 0;
        } else {
            $data['work_order_transfer_check'] = 0;
        }
        if (isset($info['set_datas']) && $info['set_datas']) {
            $data['order_check_level'] = json_decode($info['set_datas'], true);
        } else {
            $data['order_check_level'] = 0;
        }
        if ($checkHasApply == 1) {
            $whereOrderModifyArr = [];
            $whereOrderModifyArr[] = ['village_id', '=', $village_id];
            $property_id && $whereOrderModifyArr[] = ['property_id', '=', $property_id];
            // 后续拓展这里改成 in 查找
            $whereOrderModifyArr[] = ['xtype', '=', 'transfer_other_work'];
            $whereOrderModifyArr[] = ['status', 'in', [0,1]];
            $applyModify = (new HouseVillageCheckauthApply())->getOne($whereOrderModifyArr);
            if (isset($applyModify['xtype']) && $applyModify['xtype'] == 'transfer_other_work') {
                $data['msg'] = '由' . $applyModify['apply_name'] . '发起的转单申请审核，订单ID为' . $applyModify['order_id'];
            }
        }
        return $data;
    }
    
    /**
     * 工单转单审核设置.
     */
    public function orderCheckSet($params) {
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $xtype = isset($params['xtype']) ? trim($params['xtype']) : 'work_order_transfer_check';
        $tname = isset($params['tname']) ? trim($params['tname']) : '工单转单审核设置';
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
                $set_datas = json_encode($set_datas,JSON_UNESCAPED_UNICODE);
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
    
    /**
     * 添加或更新质保类别
     */
    public function updateOrAddHouseCommunityQuality($params) {
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $category_name = isset($params['category_name']) ? trim($params['category_name']) : '';
        $fid = isset($params['fid']) ? intval($params['fid']) : 0;
        $remark = isset($params['remark']) ? trim($params['remark']) : '';
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        $dbHouseCommunityQuality = new HouseCommunityQuality();
        $where = [];
        if ($id) {
            $where[] = ['id', '<>', $id];
        }
        $where[] = ['fid', '=', $fid];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['category_name', '=', $category_name];
        $where[] = ['village_id', '=', 0];
        $where[] = ['delete_time', '=', 0];
        $info = $dbHouseCommunityQuality->getOne($where, 'id');
        if (isset($info['id']) && $info['id']) {
            throw new \think\Exception("质保类别名称【{$category_name}】已存在不允许重复");
        }
        if ($id) {
            $where = [];
            $where[] = ['id', '=', $id];
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['village_id', '=', 0];
            $where[] = ['delete_time', '=', 0];
            $info = $dbHouseCommunityQuality->getOne($where, 'id, fid');
            if (!isset($info['id']) || !$info['id']) {
                throw new \think\Exception("更新的质保类别不存在或者已经被删除");
            }
            if (isset($info['fid']) && $info['fid'] != $fid) {
                $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
                $whereBind = [];
                $whereBind[] = ['property_id', '=', $property_id];
                $whereBind[] = ['quality_id|quality_fid', '=', $id];
                $whereBind[] = ['delete_time', '=', 0];
                $bindInfo = $dbHouseCommunityQualityBindRepairCate->getOne($whereBind, 'id');
                if (isset($bindInfo['id']) && $bindInfo['id']) {
                    throw new \think\Exception("该质保已经绑定了分类不能更改归属父级");
                }
            }
            if ($fid > 0) {
                $where1 = [];
                $where1[] = ['fid', '=', $id];
                $where1[] = ['property_id', '=', $property_id];
                $where1[] = ['village_id', '=', 0];
                $where1[] = ['delete_time', '=', 0];
                $info1 = $dbHouseCommunityQuality->getOne($where1, 'id');
                if (isset($info1['id']) && $info1['id']) {
                    throw new \think\Exception("该质保已经为其他质保类型父级不允许变更为子级");
                }
            }
        }
        $data = ['category_name' => $category_name, 'category_name_source' => $category_name, 'property_id' => $property_id];
        $data['fid'] = $fid;
        $data['remark'] = $remark;
        if ($from_user) {
            if (is_array($from_user)) {
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
            }
            $data['from_user'] = $from_user;
        }
        if ($id) {
            $data['update_time'] = time();
            $where = [];
            $where[] = ['id', '=', $id];
            $dbHouseCommunityQuality->updateThis($where, $data);
        } else {
            $data['add_time'] = time();
            $id = $dbHouseCommunityQuality->add($data);
        }
        return $id;
    }

    /**
     * 获取符合条件的质保类别列表.
     */
    public function getCommunityQualityList($params) {
        $fid = isset($params['fid']) ? intval($params['fid']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $this->tryCatchAutoHandleAddQualityBox($params);
        $dbHouseCommunityQuality = new HouseCommunityQuality();
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['village_id', '=', 0];
        $where[] = ['fid', '=', $fid];
        $where[] = ['delete_time', '=', 0];
        $field = 'id, property_id, category_name, fid, remark, add_time';
        $list = $dbHouseCommunityQuality->getList($where, $field, 'id DESC');
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * 获取质保类别数量.
     */
    public function getCount($where) {
        $dbHouseCommunityQuality = new HouseCommunityQuality();
        return $dbHouseCommunityQuality->getCount($where);
    }
    
    /**
     * 获取质保类别列表.
     */
    public function getList($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $this->tryCatchAutoHandleAddQualityBox($params);
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 20;
        $getChild = isset($params['getChild']) ? intval($params['getChild']) : 0;
        $field = isset($params['field']) ? $params['field'] : 'id, property_id, category_name, fid, remark, add_time';
        $dbHouseCommunityQuality = new HouseCommunityQuality();
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['delete_time', '=', 0];
        if ($getChild == 1) {
            $where[] = ['fid', '>', 0];
        }
        $list = $dbHouseCommunityQuality->getList($where, $field, 'id DESC', $page, $pageSize);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        $fidArr = array_unique(array_column($list,'fid'));
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['id', 'in', $fidArr];
        $fidColumn = $dbHouseCommunityQuality->getColumn($where, 'category_name', 'id');
        foreach ($list as &$item) {
            $fid = isset($item['fid']) ? intval($item['fid']) : 0;
            if (isset($fidColumn[$fid]) && $fidColumn[$fid]) {
                $item['fid_category_name'] = $fidColumn[$fid];
            } else {
                $item['fid_category_name'] = '-';
            }
            if (isset($item['add_time']) && intval($item['add_time']) > 1) {
                $item['add_time_text'] = date('Y-m-d H:i:s', $item['add_time']);
            }
        }
        return $list;
    }

    /**
     * 获取质保分类详细信息.
     */
    public function getCommunityQualityDetail($params) {
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $this->tryCatchAutoHandleAddQualityBox($params);
        $dbHouseCommunityQuality = new HouseCommunityQuality();
        $where = [];
        if ($property_id) {
            $where[] = ['property_id', '=', $property_id];
        }
        $where[] = ['id', '=', $id];
        $field = 'id, property_id, category_name, fid, remark, add_time';
        $info = $dbHouseCommunityQuality->getOne($where, $field);
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (isset($info['fid']) && $info['fid']) {
            $where = [];
            if ($property_id) {
                $where[] = ['property_id', '=', $property_id];
            }
            $where[] = ['id', '=', $info['fid']];
            $fidInfo = $dbHouseCommunityQuality->getOne($where);
            if ($fidInfo && !is_array($fidInfo)) {
                $fidInfo = $fidInfo->toArray();
            }
            if (isset($fidInfo['category_name']) && $fidInfo['category_name']) {
                $info['fid_category_name'] = $fidInfo['category_name'];
            }
        } else {
            $info['fid_category_name'] = '';
        }
        if (isset($info['add_time']) && intval($info['add_time']) > 1) {
            $info['add_time_text'] = date('Y-m-d H:i:s', $info['add_time']);
        }
        return $info;
    }

    /**
     * 删除.
     */
    public function deleteCommunityQuality($params) {
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $delete_user = isset($params['delete_user']) ? $params['delete_user'] : '';
        $dbHouseCommunityQuality = new HouseCommunityQuality();
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['id', '=', $id];
        $where[] = ['delete_time', '=', 0];
        $info = $dbHouseCommunityQuality->getOne($where);
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (!isset($info['id']) || !$info['id']) {
            throw new \think\Exception("删除对象不存在或已经被删除！");
        }
        $nowTime = time();
        $deleteParam = [
            'delete_time' => $nowTime
        ];
        if ($delete_user) {
            if (is_array($delete_user)) {
                $delete_user = json_encode($delete_user,JSON_UNESCAPED_UNICODE);
            }
            $deleteParam['delete_user'] = $delete_user;
        }
        $delete = $dbHouseCommunityQuality->updateThis($where, $deleteParam);
        if ($delete === false) {
            throw new \think\Exception("删除失败！");
        }
        if (isset($info['fid']) && intval($info['fid']) <= 0) {
            // 同步【删除】子分类
            $whereFid = [];
            $whereFid[] = ['property_id', '=', $property_id];
            $whereFid[] = ['fid', '=', $id];
            $whereFid[] = ['delete_time', '=', 0];
            $delete = $dbHouseCommunityQuality->updateThis($whereFid, $deleteParam);
            // 同步【审核拒绝】分类和子分类的申请
            $dbHouseCommunityQualityApply = new HouseCommunityQualityApply();
            $whereApply = [];
            $whereApply[] = ['property_id', '=', $property_id];
            $whereApply[] = ['quality_id|quality_fid', '=', $id];
            $whereApply[] = ['status', '=', 0];
            $applyData = [
                'status' => 2,
                'refuse_reason' => '对应质保分类已经被删除',
                'update_time' => $nowTime,
                'check_time' => $nowTime,
            ];
            isset($deleteParam['delete_user']) && $applyData['check_user'] = $deleteParam['delete_user'];
            $dbHouseCommunityQualityApply->updateThis($whereApply, $applyData);
            // 同步【删除】已经绑定的工单分类记录
            $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
            $whereBind = [];
            $whereBind[] = ['property_id', '=', $property_id];
            $whereBind[] = ['quality_id|quality_fid', '=', $id];
            $whereBind[] = ['delete_time', '=', 0];
            $bindParams = [
                'update_time' => $nowTime,
                'delete_time' => $nowTime,
            ];
            $dbHouseCommunityQualityBindRepairCate->updateThis($whereBind, $bindParams);
        }
        return $info['id'];
    }
    
    /**
     * 修改质保类别名称申请
     */
    public function applyChangeCommunityQualityName($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $quality_id = isset($params['quality_id']) ? intval($params['quality_id']) : 0;
        $change_name = isset($params['change_name']) ? trim($params['change_name']) : '';
        $reason = isset($params['reason']) ? trim($params['reason']) : '';
        $apply_user = isset($params['apply_user']) ? $params['apply_user'] : '';
        $apply_name = isset($params['apply_name']) ? $params['apply_name'] : '';
        $apply_login_userId = isset($params['apply_login_userId']) ? $params['apply_login_userId'] : '';
        $apply_login_role = isset($params['apply_login_role']) ? $params['apply_login_role'] : '';
        if (!$change_name) {
            throw new \think\Exception("请填写申请修改名称");
        }
        if (!$reason) {
            throw new \think\Exception("请填写申请修改原因");
        }
        $dbHouseCommunityQuality = new HouseCommunityQuality();
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['id', '=', $quality_id];
        $where[] = ['delete_time', '=', 0];
        $qualityInfo = $dbHouseCommunityQuality->getOne($where, 'id, fid, category_name');
        if ($qualityInfo && !is_array($qualityInfo)) {
            $qualityInfo = $qualityInfo->toArray();
        }
        if (!isset($qualityInfo['id']) || !$qualityInfo['id']) {
            throw new \think\Exception("申请对象不存在");
        }
        $quality_fid = isset($qualityInfo['fid']) ? intval($qualityInfo['fid']) : 0;
        if ($quality_fid <= 0) {
            throw new \think\Exception("申请对象只能是二级质保类别");
        }
        $where = [];
        $where[] = ['id', '<>', $quality_id];
        $where[] = ['fid', '=', $quality_fid];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['category_name', '=', $change_name];
        $where[] = ['village_id', '=', 0];
        $where[] = ['delete_time', '=', 0];
        $info = $dbHouseCommunityQuality->getOne($where, 'id');
        if (isset($info['id']) && $info['id']) {
            throw new \think\Exception("质保类别名称【{$change_name}】已存在不允许重复");
        }
        $dbHouseCommunityQualityApply = new HouseCommunityQualityApply();
        $whereApply = [];
        $whereApply[] = ['quality_id', '=', $quality_id];
        $whereApply[] = ['property_id', '=', $property_id];
        $whereApply[] = ['village_id', '=', $village_id];
        $whereApply[] = ['status', '=', 0];
        $whereApply[] = ['apply_delete_time', '=', 0];
        $applyInfo = $dbHouseCommunityQualityApply->getOne($whereApply, 'id');
        if (isset($applyInfo['id']) && $applyInfo['id']) {
            throw new \think\Exception("当前质保类别已经申请处于审核中！");
        }
        $category_name = isset($qualityInfo['category_name']) ? trim($qualityInfo['category_name']) : '';
        $applyData = [
            'quality_id' => $quality_id,
            'quality_fid' => $quality_fid,
            'property_id' => $property_id,
            'village_id' => $village_id,
            'change_name' => $change_name,
            'category_name' => $category_name,
            'reason' => $reason,
            'status' => 0,
            'apply_name' => $apply_name,
            'apply_login_userId' => $apply_login_userId,
            'apply_login_role' => $apply_login_role,
            'apply_time' => time()
        ];
        if ($apply_user) {
            if (is_array($apply_user)) {
                $apply_user = json_encode($apply_user,JSON_UNESCAPED_UNICODE);
            }
            $applyData['apply_user'] = $apply_user;
        }
        return $dbHouseCommunityQualityApply->add($applyData);
    }
    
    /**
     * 获取状态信息.
     */
    public function getApplyStatus($status) {
        $statusArr = [0 => '待审核', 1 => '审核通过', 2 => '审核拒绝'];
        $status = intval($status);
        return isset($statusArr[$status]) ? $statusArr[$status] : '';
    }

    /**
     * 获取申请数量.
     */
    public function getApplyCount($where) {
        $dbHouseCommunityQualityApply = new HouseCommunityQualityApply();
        return $dbHouseCommunityQualityApply->getCount($where);
    }
    
    /**
     * 获取申请列表.
     */
    public function getApplyList($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) ? intval($params['pageSize']) : 20;
        $dbHouseCommunityQualityApply = new HouseCommunityQualityApply();
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        if ($village_id > 0) {
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['apply_delete_time', '=', 0];
            $order = "apply_time DESC, id DESC";
        } else {
            $where[] = ['apply_delete_time', '=', 0];
            $where[] = ['check_delete_time', '=', 0];
            $order = "status ASC, apply_time DESC, id DESC";
        }
        $field = 'id, quality_id,quality_fid, property_id, village_id, change_name, category_name, reason, status, refuse_reason, apply_name, apply_time, check_time';
        $list = $dbHouseCommunityQualityApply->getList($where, $field, $order, $page, $pageSize);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        $villageIdArr = array_unique(array_column($list,'village_id'));
        $where = [];
        $where[] = ['village_id', 'in', $villageIdArr];
        $villageColumn = (new HouseVillage())->getColumn($where, 'village_name', 'village_id');

        $qualityFidArr = array_unique(array_column($list,'quality_fid'));
        $where1 = [];
        $where1[] = ['id', 'in', $qualityFidArr];
        $qualityFidNameArr = (new HouseCommunityQuality())->getColumn($where1, 'category_name', 'id');
        
        foreach ($list as &$item) {
            $village_id = isset($item['village_id']) ? intval($item['village_id']) : 0;
            $item['village_name'] = isset($villageColumn[$village_id]) ? trim($villageColumn[$village_id]) : '';

            $quality_fid = isset($item['quality_fid']) ? intval($item['quality_fid']) : 0;
            $qualityFidName = isset($qualityFidNameArr[$quality_fid]) ? trim($qualityFidNameArr[$quality_fid]) : '';
            $qualityFidName && $item['category_name'] = $qualityFidName .'/'.$item['category_name'];
            $qualityFidName && $item['change_name'] = $qualityFidName .'/'.$item['change_name'];
            
            if (isset($item['apply_time']) && intval($item['apply_time']) > 1) {
                $item['apply_time_text'] = date('Y-m-d H:i:s', $item['apply_time']);
            }
            if (isset($item['status'])) {
                $item['status_text'] = $this->getApplyStatus($item['status']);
            }
            if (isset($item['check_time']) && intval($item['check_time']) > 1) {
                $item['check_time_text'] = date('Y-m-d H:i:s', $item['check_time']);
            }
        }
        return $list;
    }
    
    /**
     * 删除申请.
     */
    public function deleteChangeApply($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $apply_delete_user = isset($params['apply_delete_user']) ? $params['apply_delete_user'] : '';
        $check_delete_user = isset($params['check_delete_user']) ? $params['check_delete_user'] : '';
        $dbHouseCommunityQualityApply = new HouseCommunityQualityApply();
        $whereApply = [];
        $whereApply[] = ['id', '=', $id];
        $whereApply[] = ['property_id', '=', $property_id];
        if ($village_id > 0) {
            $whereApply[] = ['village_id', '=', $village_id];
            $whereApply[] = ['apply_delete_time', '=', 0];
        } else {
            $whereApply[] = ['check_delete_time', '=', 0];
        }
        $applyInfo = $dbHouseCommunityQualityApply->getOne($whereApply, 'id, status');
        if (!isset($applyInfo['id']) || !$applyInfo['id']) {
            throw new \think\Exception("当前申请不存在或者已经被删除！");
        }
        if ($village_id <= 0 && isset($applyInfo['status']) && intval($applyInfo['status']) == 0) {
            throw new \think\Exception("当前申请【待审核】不允许删除！");
        }
        $nowTime = time();
        $applyData = ['update_time' => $nowTime];
        if ($village_id > 0) {
            $applyData['apply_delete_time'] = $nowTime;
        } else {
            $applyData['check_delete_time'] = $nowTime;
        }
        if ($apply_delete_user) {
            if (is_array($apply_delete_user)) {
                $apply_delete_user = json_encode($apply_delete_user,JSON_UNESCAPED_UNICODE);
            }
            $applyData['apply_delete_user'] = $apply_delete_user;
        }
        if ($check_delete_user) {
            if (is_array($check_delete_user)) {
                $check_delete_user = json_encode($check_delete_user,JSON_UNESCAPED_UNICODE);
            }
            $applyData['check_delete_user'] = $check_delete_user;
        }
        return $dbHouseCommunityQualityApply->updateThis($whereApply, $applyData);
    }

    /**
     * 获取申请详情.
     */
    public function getApplyDetail($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $isHandle = isset($params['isHandle']) ? intval($params['isHandle']) : 0;
        $dbHouseCommunityQualityApply = new HouseCommunityQualityApply();
        $whereApply = [];
        $whereApply[] = ['id', '=', $id];
        $whereApply[] = ['property_id', '=', $property_id];
        if ($village_id > 0) {
            $whereApply[] = ['village_id', '=', $village_id];
            $whereApply[] = ['apply_delete_time', '=', 0];
        } else {
            $whereApply[] = ['apply_delete_time', '=', 0];
            $whereApply[] = ['check_delete_time', '=', 0];
        }
        $field = 'id, quality_id,quality_fid, property_id, village_id, change_name, category_name, reason, status, refuse_reason, apply_name, apply_time, check_time';
        $applyInfo = $dbHouseCommunityQualityApply->getOne($whereApply, $field);
        if (!isset($applyInfo['id']) || !$applyInfo['id']) {
            throw new \think\Exception("当前申请不存在或者已经被删除！");
        }
        if (isset($applyInfo['apply_time']) && intval($applyInfo['apply_time']) > 1) {
            $applyInfo['apply_time_text'] = date('Y-m-d H:i:s', $applyInfo['apply_time']);
        }
        if (isset($applyInfo['status'])) {
            $applyInfo['status_text'] = $this->getApplyStatus($applyInfo['status']);
        }
        if (isset($applyInfo['check_time']) && intval($applyInfo['check_time']) > 1) {
            $applyInfo['check_time_text'] = date('Y-m-d H:i:s', $applyInfo['check_time']);
        }
        $apply_village_id = isset($applyInfo['village_id']) ? intval($applyInfo['village_id']) : 0;
        $villageColumn = (new HouseVillage())->getOne($apply_village_id, 'village_id, village_name');
        $applyInfo['village_name'] = isset($villageColumn['village_name']) ? trim($villageColumn['village_name']) : '';


        $apply_quality_fid = isset($applyInfo['quality_fid']) ? intval($applyInfo['quality_fid']) : 0;
        $where1 = [];
        $where1[] = ['id', '=', $apply_quality_fid];
        $qualityFidNameArr = (new HouseCommunityQuality())->getOne($where1, 'id, category_name');
        $fid_category_name = isset($qualityFidNameArr['category_name']) ? trim($qualityFidNameArr['category_name']) : '';
        $category_name = isset($applyInfo['category_name']) ? $applyInfo['category_name'] : '';
        $change_name = isset($applyInfo['change_name']) ? $applyInfo['change_name'] : '';
        if ($fid_category_name) {
            $applyInfo['fid_category_name'] = $fid_category_name;
            $applyInfo['category_name'] = $fid_category_name . '/' . $category_name;
            $applyInfo['change_name'] = $fid_category_name . '/' . $change_name;
        }
        if ($isHandle) {
            // 整理成便于前端渲染的结构
            $handleArr = [];
            $house_name = cfg('house_name');
            !$house_name && $house_name = '小区';
            $handleArr[] = [
                'label' => $house_name . '名称', 'value' => $applyInfo['village_name'],
            ];
            $handleArr[] = [
                'label' => '类别名称', 'value' => isset($applyInfo['category_name']) ? $applyInfo['category_name'] : '',
            ];
            $handleArr[] = [
                'label' => '修改后类别名称', 'value' => isset($applyInfo['change_name']) ? $applyInfo['change_name'] : '',
            ];
            $handleArr[] = [
                'label' => '申请原因', 'value' => isset($applyInfo['reason']) ? $applyInfo['reason'] : '',
            ];
            $handleArr[] = [
                'label' => '申请时间', 'value' => isset($applyInfo['apply_time_text']) ? $applyInfo['apply_time_text'] : '',
            ];
            $handleArr[] = [
                'label' => '申请人', 'value' => isset($applyInfo['apply_name']) ? $applyInfo['apply_name'] : '',
            ];
            $handleArr[] = [
                'label' => '状态', 'value' => isset($applyInfo['status_text']) ? $applyInfo['status_text'] : '',
            ];
            if (isset($applyInfo['status']) && intval($applyInfo['status']) > 0) {
                $handleArr[] = [
                    'label' => '审核时间', 'value' => isset($applyInfo['check_time_text']) ? $applyInfo['check_time_text'] : '',
                ];
                if (isset($applyInfo['refuse_reason']) && trim($applyInfo['refuse_reason'])) {
                    $handleArr[] = [
                        'label' => '审核拒绝原因', 'value' => $applyInfo['refuse_reason'],
                    ];
                }
            }
            $applyInfo['handleArr'] = $handleArr;
        }
        if (isset($applyInfo['status']) && intval($applyInfo['status']) == 0 && $village_id == 0) {
            $applyInfo['isCheck'] = 1;
        } else {
            $applyInfo['isCheck'] = 0;
        }
        return $applyInfo;
    }

    /**
     * 审核申请.
     */
    public function checkApply($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $status = isset($params['status']) ? intval($params['status']) : 0;
        $refuse_reason = isset($params['refuse_reason']) ? trim($params['refuse_reason']) : '';
        $check_user = isset($params['check_user']) ? $params['check_user'] : '';
        $checkStatus = $this->getCheckStatus();
        if (!in_array($status, $checkStatus)) {
            throw new \think\Exception("审核状态有误！");
        }
        if ($status == 2 && !$refuse_reason) {
            throw new \think\Exception("审核拒绝请填写理由！");
        }
        $dbHouseCommunityQualityApply = new HouseCommunityQualityApply();
        $whereApply = [];
        $whereApply[] = ['id', '=', $id];
        $whereApply[] = ['property_id', '=', $property_id];
        $whereApply[] = ['apply_delete_time', '=', 0];
        $whereApply[] = ['check_delete_time', '=', 0];
        $field = 'id, quality_id, change_name, category_name, status';
        $applyInfo = $dbHouseCommunityQualityApply->getOne($whereApply, $field);
        if (!isset($applyInfo['id']) || !$applyInfo['id']) {
            throw new \think\Exception("当前申请不存在或者已经被删除！");
        }
        if (in_array($applyInfo['status'], $checkStatus)) {
            throw new \think\Exception("申请已经审核过了！");
        }
        $nowTime = time();
        $applyData = ['update_time' => $nowTime];
        $applyData['check_time'] = $nowTime;
        $applyData['status'] = $status;
        $status != 1 && $applyData['refuse_reason'] = $refuse_reason;
        if ($check_user) {
            if (is_array($check_user)) {
                $check_user = json_encode($check_user,JSON_UNESCAPED_UNICODE);
            }
            $applyData['check_user'] = $check_user;
        }
        $check = $dbHouseCommunityQualityApply->updateThis($whereApply, $applyData);
        if ($check === false) {
            throw new \think\Exception("审核失败！");
        }
        $quality_id = isset($applyInfo['quality_id']) ? intval($applyInfo['quality_id']) : 0;
        if ($status == 1 && $quality_id > 0) {
            // 审核成功改写质保类目名称
            $dbHouseCommunityQuality = new HouseCommunityQuality();
            $where = [];
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['id', '=', $quality_id];
            $updateParam = ['category_name' => $applyInfo['change_name'], 'update_time' => $nowTime];
            $dbHouseCommunityQuality->updateThis($where, $updateParam);
        }
        return $check;
    }
    
    /**
     * 获取楼栋质保信息.
     */
    public function getSingleCommunityQualityInfo($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $single_id = isset($params['single_id']) ? intval($params['single_id']) : 0;
        $edit_quality_id = isset($params['quality_id']) ? intval($params['quality_id']) : 0;
        // 查询结果去除已经绑定的质保类别
        $notBind = isset($params['notBind']) ? intval($params['notBind']) : 0;
        $dbHouseVillageSingle = new HouseVillageSingle();
        $whereSingle = [];
        $whereSingle[] = ['village_id', '=', $village_id];
        $whereSingle[] = ['id', '=', $single_id];
        $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id, status');
        if (!isset($singleInfo['id']) || !$singleInfo['id']) {
            throw new \think\Exception("当前操作楼栋不存在！");
        }
        if (intval($singleInfo['status']) == 4) {
            throw new \think\Exception("当前操作楼栋已删除！");
        }
        if (intval($singleInfo['status']) != 1) {
            throw new \think\Exception("当前操作楼栋状态禁止操作！");
        }
        $params = [
            'page' => 0,
            'pageSize' => 0,
            'property_id' => $property_id,
            'getChild' => 1,
            'field' => 'id, category_name, fid',
        ];
        $list = $this->getList($params);

        $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
        $qualityIdArr = array_unique(array_column($list,'id'));
        $whereBind = [];
        $whereBind[] = ['village_id', '=', $village_id];
        $whereBind[] = ['single_id', '=', $single_id];
        $whereBind[] = ['room_id', '=', 0];
        $whereBind[] = ['quality_id', 'in', $qualityIdArr];
        $bindList = $dbHouseCommunityQualityBindRepairCate->getColumn($whereBind, 'id, repair_cate_id, repair_cate_fid, quality_id, quality_start_time, quality_end_time, status, delete_time,extra_desc', 'quality_id');
        if ($bindList && !is_array($bindList)) {
            $bindList = $bindList->toArray();
        }
        $repairCateIdArr = array_unique(array_column($bindList,'repair_cate_id'));
        $repairCateFidArr = array_unique(array_column($bindList,'repair_cate_fid'));
        $cateArr = array_merge($repairCateIdArr, $repairCateFidArr);
        $dbHouseNewRepairCate = new HouseNewRepairCate();
        $whereCate = [];
        $whereCate[] = ['village_id', '=', $village_id];
        $whereCate[] = ['id', 'in', $cateArr];
        $cateColumnArr = $dbHouseNewRepairCate->getColumn($whereCate, 'id, cate_name, parent_id', 'id');
        
        foreach ($list as $key=>&$item) {
            $quality_id = isset($item['id']) ? intval($item['id']) : 0;
            if (isset($bindList[$quality_id]) && $bindList[$quality_id]) {
                $bind = $bindList[$quality_id];
                $delete_time = isset($bind['delete_time']) ? intval($bind['delete_time']) : 0;
                if ($delete_time > 100) {
                    unset($list[$key]);
                    continue;
                }
                $repair_cate_id = isset($bind['repair_cate_id']) ? intval($bind['repair_cate_id']) : 0;
                $repair_cate_fid = isset($bind['repair_cate_fid']) ? intval($bind['repair_cate_fid']) : 0;
                $status = isset($bind['status']) ? intval($bind['status']) : 0;
                if ($notBind && ($repair_cate_id > 0 || $status != 1) && $quality_id != $edit_quality_id) {
                    // 已经绑定的过滤 状态非开启的过滤
                    unset($list[$key]);
                    continue;
                }
                $item['cate_name'] = isset($cateColumnArr[$repair_cate_id]['cate_name']) ? trim($cateColumnArr[$repair_cate_id]['cate_name']) : '';
                $item['cate_fname'] = isset($cateColumnArr[$repair_cate_fid]['cate_name']) ? trim($cateColumnArr[$repair_cate_fid]['cate_name']) : '';

                $item['repair_cate_id'] = $repair_cate_id;
                $item['repair_cate_fid'] = $repair_cate_fid;
                $item['quality_start_time'] = isset($bind['quality_start_time']) ? intval($bind['quality_start_time']) : 0;
                $item['quality_end_time'] = isset($bind['quality_end_time']) ? intval($bind['quality_end_time']) : 0;
                $item['status'] = $status;
                $extra_desc='';
                if(isset($bind['extra_desc']) && $bind['extra_desc']){
                    $extra_desc= json_decode($bind['extra_desc'],true);
                }
                if(empty($extra_desc)){
                    $extra_desc=array();
                    $extra_desc[]=array(array('p_key'=>'p_brand','p_value'=>'','p_key_name'=>'品牌','xsort'=>1),array('p_key'=>'p_model_number','p_value'=>'','p_key_name'=>'型号','xsort'=>2),array('p_key'=>'construction_unit','p_value'=>'','p_key_name'=>'施工单位','xsort'=>3));
                }
                $item['extra_desc'] =$extra_desc;
            } else {
                $extra_desc=array();
                $extra_desc[]=array(array('p_key'=>'p_brand','p_value'=>'','p_key_name'=>'品牌','xsort'=>1),array('p_key'=>'p_model_number','p_value'=>'','p_key_name'=>'型号','xsort'=>2),array('p_key'=>'construction_unit','p_value'=>'','p_key_name'=>'施工单位','xsort'=>3));
                $item['extra_desc'] =$extra_desc;
                $item['quality_start_time'] = 0;
                $item['quality_end_time'] = 0;
                $item['repair_cate_id'] = 0;
                $item['repair_cate_fid'] = 0;
                $item['status'] = 1;
                $item['cate_name'] = '';
                $item['cate_fname'] = '';
            }
            if ($item['quality_start_time'] > 1) {
                $item['quality_start_time_text'] = date('Y-m-d H:i:s', $item['quality_start_time']);
            } else {
                $item['quality_start_time_text'] = '';
            }
            if ($item['quality_end_time'] > 1) {
                $item['quality_end_time_text'] = date('Y-m-d H:i:s', $item['quality_end_time']);
            } else {
                $item['quality_end_time_text'] = '';
            }
        }
        $list = array_values($list);
        return $list;
    }

    /**
     * 设置楼栋质保信息.
     */
    public function setSingleCommunityQuality($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $single_id = isset($params['single_id']) ? intval($params['single_id']) : 0;
        $qualityParams = isset($params['qualityParams']) ? $params['qualityParams'] : [];
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        if ($from_user) {
            if (is_array($from_user)) {
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
            }
        }
        
        $paramLists = [
            'property_id' => $property_id,
            'village_id' => $village_id,
            'single_id' => $single_id,
        ];
        $list = $this->getSingleCommunityQualityInfo($paramLists);
        $qualityArr = [];
        foreach ($qualityParams as $quality) {
            $id = isset($quality['id']) ? intval($quality['id']) : 0;
            $qualityArr[$id] = $quality;
        }
        $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
        $nowTime = time();
        $setArr = [];
        foreach ($list as $item) {
            $id = isset($item['id']) ? intval($item['id']) : 0;
            $fid = isset($item['fid']) ? intval($item['fid']) : 0;
            
            $quality = isset($qualityArr[$id]) ? $qualityArr[$id] : [];
            $quality_start_time = isset($quality['quality_start_time_text']) && $quality['quality_start_time_text'] ? strtotime($quality['quality_start_time_text']) : 0;
            !$quality_start_time && $quality_start_time = isset($quality['quality_start_time']) && $quality['quality_start_time'] ? intval($quality['quality_start_time']) : 0;
            $quality_end_time = isset($quality['quality_end_time_text']) && $quality['quality_end_time_text'] ? strtotime($quality['quality_end_time_text']) : 0;
            !$quality_end_time && $quality_end_time = isset($quality['quality_end_time']) && $quality['quality_end_time'] ? intval($quality['quality_end_time']) : 0;
            $status = isset($quality['status']) ? intval($quality['status']) : 1;
            $bindRepairCate = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'single_id' => $single_id,
                'quality_id' => $id,
                'quality_fid' => $fid,
                'quality_json' => json_encode($item,JSON_UNESCAPED_UNICODE),
                'from_user' => $from_user,
                'status' => $status,
                'extra_desc'=>'',
            ];
            if(isset($quality['extra_desc']) && $quality['extra_desc']){
                $bindRepairCate['extra_desc']= json_encode($quality['extra_desc'],JSON_UNESCAPED_UNICODE);
            }
            if ($quality_start_time > 0) {
                $quality_start_time = date('Y-m-d 00:00:00', $quality_start_time);
                $quality_start_time = strtotime($quality_start_time);
            }
            if ($quality_end_time > 0) {
                $quality_end_time = date('Y-m-d 23:59:59', $quality_end_time);
                $quality_end_time = strtotime($quality_end_time);
            }
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['single_id', '=', $single_id];
            $where[] = ['room_id', '=', 0];
            $where[] = ['quality_id', '=', $id];
            $where[] = ['delete_time', '=', 0];
            $info = $dbHouseCommunityQualityBindRepairCate->getOne($where, 'id');
            if (isset($info['id']) && $info['id']) {
                $quality_start_time > 0 && $bindRepairCate['quality_start_time'] = $quality_start_time;
                $quality_end_time > 0 && $bindRepairCate['quality_end_time'] = $quality_end_time;
                $bindRepairCate['update_time'] = $nowTime;
                $infoId = $info['id'];
                $dbHouseCommunityQualityBindRepairCate->updateThis(['id' => $infoId], $bindRepairCate);
            } else {
                $bindRepairCate['quality_start_time'] = $quality_start_time;
                $bindRepairCate['quality_end_time'] = $quality_end_time;
                $bindRepairCate['add_time'] = $nowTime;
                $infoId = $dbHouseCommunityQualityBindRepairCate->add($bindRepairCate);
            }
            $setArr[] = $infoId;
        }
        return $setArr;
    }
    
    /**
     * 获取房屋质保信息.
     */
    public function getRoomCommunityQualityInfo($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $room_id = isset($params['room_id']) ? intval($params['room_id']) : 0;
        $edit_quality_id = isset($params['quality_id']) ? intval($params['quality_id']) : 0;
        $houseVillageUserBind=new HouseVillageUserBind();
        $isReturnObj=false;
        if(isset($params['isReturnObj']) && $params['isReturnObj']){
            $isReturnObj=true;
        }
        if($room_id<1 && isset($params['pigcms_id']) && $params['pigcms_id']){
            $whereBind=array('village_id'=>$village_id,'pigcms_id'=>$params['pigcms_id']);
            $userBindObj=$houseVillageUserBind->getOne($whereBind,'pigcms_id,pigcms_id,village_id,uid,name,phone,address,vacancy_id');
            if($userBindObj && !$userBindObj->isEmpty()){
                $room_id=$userBindObj['vacancy_id'];
            }
        }
        $village_name='';
        if ($village_id >0) {
            $houseVillageDb = new HouseVillage();
            $houseVillageObj = $houseVillageDb->getInfo(array('village_id' => $village_id), 'village_id,village_name,property_id');
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $property_id = $houseVillageObj['property_id'];
                $village_name=$houseVillageObj['village_name'];
            }
        }
        // 查询结果去除已经绑定的质保类别
        $notBind = isset($params['notBind']) ? intval($params['notBind']) : 0;
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereRoom = [];
        $whereRoom[] = ['village_id', '=', $village_id];
        $whereRoom[] = ['pigcms_id', '=', $room_id];
        $roomInfo = $dbHouseVillageUserVacancy->getOne($whereRoom, '*');
        if (!isset($roomInfo['pigcms_id']) || !$roomInfo['pigcms_id']) {
            throw new \think\Exception("当前操作房屋不存在！");
        }
        if (intval($roomInfo['is_del']) == 1) {
            throw new \think\Exception("当前操作房屋已删除！");
        }
        if (intval($roomInfo['status']) == 4) {
            throw new \think\Exception("当前操作房屋状态禁止操作！");
        }
        $single_id = isset($roomInfo['single_id']) ? intval($roomInfo['single_id']) : 0;
        $service_house_village = new HouseVillageService();
        $address='';
        if($single_id>0) {
            $address = $service_house_village->getSingleFloorRoom($roomInfo['single_id'], $roomInfo['floor_id'], $roomInfo['layer_id'], $roomInfo['pigcms_id'], $roomInfo['village_id']);
        }
        $is_show=0;
        if(isset($params['is_show'])){
            $is_show=$params['is_show'];
        }
        $paramsWhere = [
            'page' => 0,
            'pageSize' => 0,
            'property_id' => $property_id,
            'getChild' => 1,
            'field' => 'id, category_name, fid',
        ];
        $list = $this->getList($paramsWhere);
        $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
        $qualityIdArr = array_unique(array_column($list,'id'));
        $whereBind = [];
        $whereBind[] = ['village_id', '=', $village_id];
        $whereBind[] = ['single_id', '=', $single_id];
        $whereBind[] = ['room_id', '=', $room_id];
        $whereBind[] = ['quality_id', 'in', $qualityIdArr];
        $bindList = $dbHouseCommunityQualityBindRepairCate->getColumn($whereBind, 'id, repair_cate_id, repair_cate_fid, quality_id, quality_start_time, quality_end_time, status, delete_time,extra_desc', 'quality_id');
        if ($bindList && !is_array($bindList)) {
            $bindList = $bindList->toArray();
        }
        $whereRoomBind = [];
        $whereRoomBind[] = ['village_id', '=', $village_id];
        $whereRoomBind[] = ['single_id', '=', $single_id];
        $whereRoomBind[] = ['room_id', '=', 0];
        $whereRoomBind[] = ['quality_id', 'in', $qualityIdArr];
        $singleBindList = $dbHouseCommunityQualityBindRepairCate->getColumn($whereRoomBind, 'id, repair_cate_id, repair_cate_fid, quality_id, quality_start_time, quality_end_time, status, delete_time,extra_desc', 'quality_id');
        if ($singleBindList && !is_array($singleBindList)) {
            $singleBindList = $singleBindList->toArray();
        }
        
        $repairCateIdArr = array_unique(array_column($bindList,'repair_cate_id'));
        $repairCateFidArr = array_unique(array_column($bindList,'repair_cate_fid'));
        
        $singleRepairCateIdArr = array_unique(array_column($singleBindList,'repair_cate_id'));
        $singleRepairCateFidArr = array_unique(array_column($singleBindList,'repair_cate_fid'));
        
        $cateArr = array_merge($repairCateIdArr, $repairCateFidArr, $singleRepairCateIdArr, $singleRepairCateFidArr);
        $dbHouseNewRepairCate = new HouseNewRepairCate();
        $whereCate = [];
        $whereCate[] = ['village_id', '=', $village_id];
        $whereCate[] = ['id', 'in', $cateArr];
        $cateColumnArr = $dbHouseNewRepairCate->getColumn($whereCate, 'id, cate_name, parent_id', 'id');
        
        foreach ($list as $key=>&$item) {
            $quality_id = isset($item['id']) ? intval($item['id']) : 0;
            if (isset($bindList[$quality_id]) && $bindList[$quality_id]) {
                $bind = $bindList[$quality_id];
                $delete_time = isset($bind['delete_time']) ? intval($bind['delete_time']) : 0;
                if ($delete_time > 100) {
                    unset($list[$key]);
                    continue;
                }
                $singleBind = isset($singleBindList[$quality_id]) && $singleBindList[$quality_id] ? $singleBindList[$quality_id] : [];
                $repair_cate_id = isset($singleBind['repair_cate_id']) ? intval($singleBind['repair_cate_id']) : 0;
                $repair_cate_fid = isset($singleBind['repair_cate_fid']) ? intval($singleBind['repair_cate_fid']) : 0;
                $status = isset($bind['status']) ? intval($bind['status']) : 0;
                if($is_show==1 && $status==2){
                    unset($list[$key]);
                    continue;
                }
                if ($notBind && ($repair_cate_id > 0 || $status != 1) && $quality_id != $edit_quality_id) {
                    // 已经绑定的过滤 状态非开启的过滤
                    unset($list[$key]);
                    continue;
                }
                $item['cate_name'] = isset($cateColumnArr[$repair_cate_id]['cate_name']) ? trim($cateColumnArr[$repair_cate_id]['cate_name']) : '';
                $item['cate_fname'] = isset($cateColumnArr[$repair_cate_fid]['cate_name']) ? trim($cateColumnArr[$repair_cate_fid]['cate_name']) : '';

                $item['repair_cate_id'] = $repair_cate_id;
                $item['repair_cate_fid'] = $repair_cate_fid;
                if (isset($bind['quality_start_time']) && intval($bind['quality_start_time']) > 0 && isset($bind['quality_end_time']) && intval($bind['quality_end_time']) > 0) {
                    $item['quality_start_time'] = isset($bind['quality_start_time']) ? intval($bind['quality_start_time']) : 0;
                    $item['quality_end_time'] = isset($bind['quality_end_time']) ? intval($bind['quality_end_time']) : 0;
                } else {
                    $item['quality_start_time'] = isset($singleBind['quality_start_time']) ? intval($singleBind['quality_start_time']) : 0;
                    $item['quality_end_time'] = isset($singleBind['quality_end_time']) ? intval($singleBind['quality_end_time']) : 0;
                }
                $item['status'] = $status;
                $extra_desc=array();
                if(isset($bind['extra_desc']) && $bind['extra_desc']){
                    $extra_desc= json_decode($bind['extra_desc'],true);
                }
                if(empty($extra_desc) && $is_show<1){
                    $extra_desc=array();
                    $extra_desc[]=array(array('p_key'=>'p_brand','p_value'=>'','p_key_name'=>'品牌','xsort'=>1),array('p_key'=>'p_model_number','p_value'=>'','p_key_name'=>'型号','xsort'=>2),array('p_key'=>'construction_unit','p_value'=>'','p_key_name'=>'施工单位','xsort'=>3));
                }
                $item['extra_desc'] =$extra_desc;
            } 
            elseif (isset($singleBindList[$quality_id]) && $singleBindList[$quality_id]) {
                $bind = $singleBindList[$quality_id];
                $delete_time = isset($bind['delete_time']) ? intval($bind['delete_time']) : 0;
                if ($delete_time > 100) {
                    unset($list[$key]);
                    continue;
                }
                $repair_cate_id = isset($bind['repair_cate_id']) ? intval($bind['repair_cate_id']) : 0;
                $repair_cate_fid = isset($bind['repair_cate_fid']) ? intval($bind['repair_cate_fid']) : 0;
                $status = isset($bind['status']) ? intval($bind['status']) : 0;
                if($is_show==1 && $status==2){
                    unset($list[$key]);
                    continue;
                }
                if ($notBind && ($repair_cate_id > 0 || $status != 1) && $quality_id != $edit_quality_id) {
                    // 已经绑定的过滤 状态非开启的过滤
                    unset($list[$key]);
                    continue;
                }
                $item['cate_name'] = isset($cateColumnArr[$repair_cate_id]['cate_name']) ? trim($cateColumnArr[$repair_cate_id]['cate_name']) : '';
                $item['cate_fname'] = isset($cateColumnArr[$repair_cate_fid]['cate_name']) ? trim($cateColumnArr[$repair_cate_fid]['cate_name']) : '';

                $item['repair_cate_id'] = $repair_cate_id;
                $item['repair_cate_fid'] = $repair_cate_fid;
                $item['quality_start_time'] = isset($bind['quality_start_time']) ? intval($bind['quality_start_time']) : 0;
                $item['quality_end_time'] = isset($bind['quality_end_time']) ? intval($bind['quality_end_time']) : 0;
                $item['status'] = $status;
                $extra_desc=array();
                if(isset($bind['extra_desc']) && $bind['extra_desc']){
                    $extra_desc= json_decode($bind['extra_desc'],true);
                }
                if(empty($extra_desc) && $is_show<1){
                    $extra_desc=array();
                    $extra_desc[]=array(array('p_key'=>'p_brand','p_value'=>'','p_key_name'=>'品牌','xsort'=>1),array('p_key'=>'p_model_number','p_value'=>'','p_key_name'=>'型号','xsort'=>2),array('p_key'=>'construction_unit','p_value'=>'','p_key_name'=>'施工单位','xsort'=>3));
                }
                $item['extra_desc'] =$extra_desc;
            }
            else {
                $extra_desc=array();
                if($is_show<1){
                    $extra_desc[]=array(array('p_key'=>'p_brand','p_value'=>'','p_key_name'=>'品牌','xsort'=>1),array('p_key'=>'p_model_number','p_value'=>'','p_key_name'=>'型号','xsort'=>2),array('p_key'=>'construction_unit','p_value'=>'','p_key_name'=>'施工单位','xsort'=>3)); 
                }
                $item['extra_desc'] =$extra_desc;
                $item['quality_start_time'] = 0;
                $item['quality_end_time'] = 0;
                $item['repair_cate_id'] = 0;
                $item['repair_cate_fid'] = 0;
                $item['status'] = 1;
                $item['cate_name'] = '';
                $item['cate_fname'] = '';
            }
            if ($item['quality_start_time'] > 1) {
                $item['quality_start_time_text'] = date('Y-m-d', $item['quality_start_time']);
            } else {
                $item['quality_start_time_text'] = '';
            }
            if ($item['quality_end_time'] > 1) {
                $item['quality_end_time_text'] = date('Y-m-d', $item['quality_end_time']);
            } else {
                $item['quality_end_time_text'] = '';
            }
        }
        $list = array_values($list);
        if($isReturnObj){
            return array('list'=>$list,'room_address'=>$village_name.$address,'is_show'=>$is_show);
        }
        return $list;
    }
    
    /**
     * 设置房屋质保信息.
     */
    public function setRoomCommunityQuality($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $room_id = isset($params['room_id']) ? intval($params['room_id']) : 0;
        $qualityParams = isset($params['qualityParams']) ? $params['qualityParams'] : [];
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        if ($from_user) {
            if (is_array($from_user)) {
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
            }
        }

        $paramLists = [
            'property_id' => $property_id,
            'village_id' => $village_id,
            'room_id' => $room_id,
        ];
        $list = $this->getRoomCommunityQualityInfo($paramLists);
        $qualityArr = [];
        foreach ($qualityParams as $quality) {
            $id = isset($quality['id']) ? intval($quality['id']) : 0;
            $qualityArr[$id] = $quality;
        }
        $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
        $nowTime = time();
        $setArr = [];
        
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereRoom = [];
        $whereRoom[] = ['village_id', '=', $village_id];
        $whereRoom[] = ['pigcms_id', '=', $room_id];
        $roomInfo = $dbHouseVillageUserVacancy->getOne($whereRoom, 'pigcms_id, status, single_id');
        $single_id = isset($roomInfo['single_id']) ? intval($roomInfo['single_id']) : 0;
        
        foreach ($list as $item) {
            $id = isset($item['id']) ? intval($item['id']) : 0;
            $fid = isset($item['fid']) ? intval($item['fid']) : 0;

            $quality = isset($qualityArr[$id]) ? $qualityArr[$id] : [];
            $quality_start_time = isset($quality['quality_start_time_text']) && $quality['quality_start_time_text'] ? strtotime($quality['quality_start_time_text']) : 0;
            !$quality_start_time && $quality_start_time = isset($quality['quality_start_time']) && $quality['quality_start_time'] ? intval($quality['quality_start_time']) : 0;
            $quality_end_time = isset($quality['quality_end_time_text']) && $quality['quality_end_time_text'] ? strtotime($quality['quality_end_time_text']) : 0;
            !$quality_end_time && $quality_end_time = isset($quality['quality_end_time']) && $quality['quality_end_time'] ? intval($quality['quality_end_time']) : 0;
            $status = isset($quality['status']) ? intval($quality['status']) : 1;
            $bindRepairCate = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'single_id' => $single_id,
                'room_id' => $room_id,
                'quality_id' => $id,
                'quality_fid' => $fid,
                'quality_json' => json_encode($item,JSON_UNESCAPED_UNICODE),
                'from_user' => $from_user,
                'status' => $status,
                'extra_desc'=>'',
            ];
            if(isset($quality['extra_desc']) && $quality['extra_desc']){
                $bindRepairCate['extra_desc']= json_encode($quality['extra_desc'],JSON_UNESCAPED_UNICODE);
            }
            if ($quality_start_time > 0) {
                $quality_start_time = date('Y-m-d 00:00:00', $quality_start_time);
                $quality_start_time = strtotime($quality_start_time);
            }
            if ($quality_end_time > 0) {
                $quality_end_time = date('Y-m-d 23:59:59', $quality_end_time);
                $quality_end_time = strtotime($quality_end_time);
            }
            $whereSingle = [];
            $whereSingle[] = ['village_id', '=', $village_id];
            $whereSingle[] = ['single_id', '=', $single_id];
            $whereSingle[] = ['room_id', '=', 0];
            $whereSingle[] = ['quality_id', '=', $id];
            $whereSingle[] = ['delete_time', '=', 0];
            $singleInfo = $dbHouseCommunityQualityBindRepairCate->getOne($whereSingle, 'id, quality_id, quality_start_time, quality_end_time');
            $single_quality_start_time = isset($singleInfo['quality_start_time']) ? intval($singleInfo['quality_start_time']) : 0;
            $single_quality_end_time = isset($singleInfo['quality_start_time']) ? intval($singleInfo['quality_end_time']) : 0;
            if (($quality_start_time > 0 || $quality_end_time > 0) && ($single_quality_start_time <= 100 || $single_quality_end_time <= 100)) {
                $quality_id = isset($singleInfo['quality_id']) ? intval($singleInfo['quality_id']) : 0;
                $params = [
                    'id' => $quality_id,
                ];
                $detail = $this->getCommunityQualityDetail($params);
                $fid_category_name =  isset($detail['fid_category_name']) && trim($detail['fid_category_name']) ? trim($detail['fid_category_name']) : '';
                $category_name =  isset($detail['category_name']) && trim($detail['category_name'])  ? trim($detail['category_name']) : '';
                $msg = "请先设置对应楼栋质保类别报修期限";
                if ($fid_category_name || $category_name) {
                    $fid_category_name && $tip = $fid_category_name . '/' . $category_name;
                    !$fid_category_name && $tip = $category_name;
                    $msg = "请先设置【{$tip}】对应楼栋质保类别报修期限";
                }
                throw new \think\Exception($msg);
            } elseif ($single_quality_start_time > $quality_start_time || $single_quality_end_time < $quality_end_time) {
                // 房屋质保信息必须  房屋的质保类别保修期限不能超过楼栋设置的范围
                throw new \think\Exception('房屋的质保类别保修期限不能超过楼栋设置的范围');
            }
            fdump_api([
                'single_quality_start_time' => $single_quality_start_time, 'quality_start_time' => $quality_start_time,
                'single_quality_end_time' => $single_quality_end_time,  'quality_end_time' => $quality_end_time, ], '$detail',1);
            
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['single_id', '=', $single_id];
            $where[] = ['room_id', '=', $room_id];
            $where[] = ['quality_id', '=', $id];
            $where[] = ['delete_time', '=', 0];
            $info = $dbHouseCommunityQualityBindRepairCate->getOne($where, 'id');
            if (isset($info['id']) && $info['id']) {
                $quality_start_time > 0 && $bindRepairCate['quality_start_time'] = $quality_start_time;
                $quality_end_time > 0 && $bindRepairCate['quality_end_time'] = $quality_end_time;
                $bindRepairCate['update_time'] = $nowTime;
                $infoId = $info['id'];
                $dbHouseCommunityQualityBindRepairCate->updateThis(['id' => $infoId], $bindRepairCate);
            } else {
                $bindRepairCate['quality_start_time'] = $quality_start_time;
                $bindRepairCate['quality_end_time'] = $quality_end_time;
                $bindRepairCate['add_time'] = $nowTime;
                $infoId = $dbHouseCommunityQualityBindRepairCate->add($bindRepairCate);
            }
            $setArr[] = $infoId;
        }
        return $setArr;
    }
    
    /**
     * 工单分类绑定质保.
     */
    public function repairCateBindSingleQuality($params) {
        fdump_api(['$params' =>$params, 'line' => __LINE__],'repairCateBindSingleQuality');
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $single_id = isset($params['single_id']) ? intval($params['single_id']) : 0;
        $quality_id = isset($params['quality_id']) ? intval($params['quality_id']) : 0;
        $repair_cate_id = isset($params['repair_cate_id']) ? intval($params['repair_cate_id']) : 0;
        $repair_cate_fid = isset($params['repair_cate_fid']) ? intval($params['repair_cate_fid']) : 0;
        $not_timely_report_switch = isset($params['not_timely_report_switch']) ? intval($params['not_timely_report_switch']) : 0;
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        // 先处理清除的关联
        $nowTime = time();
        $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
        if ($quality_id > 0 && $single_id > 0 && $repair_cate_id <= 0) {
            $where = [];
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['single_id', '=', $single_id];
            $where[] = ['room_id', '=', 0];
            $where[] = ['quality_id', '=', $quality_id];
            $where[] = ['delete_time', '=', 0];
            $updateNoBind = [
                'update_time' => $nowTime,
                'repair_cate_id' => 0,
                'repair_cate_fid' => 0,
            ];
            $dbHouseCommunityQualityBindRepairCate->updateThis($where, $updateNoBind);
            fdump_api(['$where' =>$where, '$updateNoBind' => $updateNoBind, 'line' => __LINE__],'repairCateBindSingleQuality', 1);
            return $params;
        }
        if (($quality_id <= 0 || $single_id <= 0) && $repair_cate_id > 0) {
            $where = [];
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['repair_cate_id', '=', $repair_cate_id];
            $where[] = ['delete_time', '=', 0];
            $updateNoBind = [
                'update_time' => $nowTime,
                'quality_id' => 0,
                'quality_fid' => 0,
                'single_id' => 0,
                'floor_id' => 0,
                'layer_id' => 0,
                'room_id' => 0,
                'quality_json' => '',
                'quality_start_time' => '',
                'quality_end_time' => '',
                'not_timely_report_switch' => $not_timely_report_switch,
            ];
            $info = $dbHouseCommunityQualityBindRepairCate->getOne($where);
            if ($info && !is_array($info)) {
                $info = $info->toArray();
            }
            if (empty($info)) {
                $updateNoBind['village_id'] = $village_id;
                $updateNoBind['property_id'] = $property_id;
                $updateNoBind['repair_cate_id'] = $repair_cate_id;
                if ($from_user) {
                    if (is_array($from_user)) {
                        $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
                    }
                }
                $updateNoBind['from_user'] = $from_user;
                $dbHouseCommunityQualityBindRepairCate->add($updateNoBind);
            } else {
                $dbHouseCommunityQualityBindRepairCate->updateThis($where, $updateNoBind);
            }
            fdump_api(['$where' =>$where, '$updateNoBind' => $updateNoBind, 'line' => __LINE__],'repairCateBindSingleQuality', 1);
            return $params;
        }
        if ($quality_id <= 0 || $single_id <= 0 || $repair_cate_id <= 0) {
            fdump_api(['err' => '参数异常', 'params' => $params, 'line' => __LINE__],'repairCateBindSingleQuality', 1);
            return false;
        }
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['village_id', '=', $village_id];
//        $where[] = ['single_id', '=', $single_id];
        $where[] = ['room_id', '=', 0];
        $where[] = ['repair_cate_id', '=', $repair_cate_id];
        $where[] = ['quality_id', '<>', $quality_id];
        $where[] = ['delete_time', '=', 0];
        $info = $dbHouseCommunityQualityBindRepairCate->getOne($where);
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        fdump_api(['where' => $where, 'info' => $info, 'line' => __LINE__],'repairCateBindSingleQuality', 1);
        $whereOther = [];
        $whereOther[] = ['property_id', '=', $property_id];
        $whereOther[] = ['village_id', '=', $village_id];
        $whereOther[] = ['single_id', '=', $single_id];
        $whereOther[] = ['room_id', '=', 0];
        $whereOther[] = ['quality_id', '=', $quality_id];
        $whereOther[] = ['delete_time', '=', 0];
        $otherInfo = $dbHouseCommunityQualityBindRepairCate->getOne($whereOther);
        if ($otherInfo && !is_array($otherInfo)) {
            $otherInfo = $otherInfo->toArray();
        }
        fdump_api(['$whereOther' => $whereOther, '$otherInfo' => $otherInfo, 'line' => __LINE__],'repairCateBindSingleQuality', 1);
        if (empty($info) && empty($otherInfo)) {
            $autoParams = [
                'village_id' => $village_id,
                'property_id' => $property_id,
                'single_id' => $single_id,
                'from_user' => $from_user,
            ];
            try {
                $this->setSingleCommunityQuality($autoParams);
            } catch (\Exception $e) {
                fdump_api(['params' => $params, 'autoParams' => $autoParams,'errline' => $e->getLine(), 'err' => $e->getMessage(), 'line' => __LINE__],'repairCateBindSingleQuality', 1);
                return false;
            }
            // 新增的分类一定没有绑定以质保id做查询更新
            $updateBind = [
                'update_time' => $nowTime,
                'repair_cate_id' => $repair_cate_id,
                'repair_cate_fid' => $repair_cate_fid,
                'not_timely_report_switch' => $not_timely_report_switch,
            ];
            $dbHouseCommunityQualityBindRepairCate->updateThis($whereOther, $updateBind);
            fdump_api(['$whereOther' => $whereOther, '$updateBind' => $updateBind, 'line' => __LINE__],'repairCateBindSingleQuality', 1);
        } else {
            $whereUpdate = [];
            $updateBind = [
                'update_time' => $nowTime,
                'repair_cate_id' => $repair_cate_id,
                'repair_cate_fid' => $repair_cate_fid,
                'not_timely_report_switch' => $not_timely_report_switch,
            ];
            $whereDelete = [];
            $updateDelete = ['delete_time' => $nowTime];
            $db_house_community_quality = new HouseCommunityQuality();
            $whereQuality = [];
            $whereQuality[] = ['id', '=', $quality_id];
            $qualityInfo = $db_house_community_quality->getOne($whereQuality, 'id, fid');
            $quality_fid = isset($qualityInfo['fid']) ? intval($qualityInfo['fid']) : 0;
            if (!empty($otherInfo) && !empty($info)) {
                $otherInfoId = isset($otherInfo['id']) ? intval($otherInfo['id']) : 0;
                $infoId = isset($info['id']) ? intval($info['id']) : 0;
                $other_quality_id = isset($otherInfo['quality_id']) ? intval($otherInfo['quality_id']) : 0;
                $info_quality_id = isset($info['quality_id']) ? intval($info['quality_id']) : 0;
                if ($otherInfoId > $infoId) {
                    $whereUpdate = $whereOther;
                    $whereDelete = $where;
                    if ($quality_id != $other_quality_id) {
                        $updateBind['quality_id'] = $quality_id;
                        $updateBind['quality_fid'] = $quality_fid;
                    }
                } elseif ($infoId > $otherInfoId) {
                    $whereUpdate = $where;
                    $whereDelete = $whereOther;
                    if ($quality_id != $info_quality_id) {
                        $updateBind['quality_id'] = $quality_id;
                        $updateBind['quality_fid'] = $quality_fid;
                    }
                }
                fdump_api(['$whereOther' => $otherInfoId, '$updateBind' => $infoId, 'line' => __LINE__],'repairCateBindSingleQuality', 1);
            } elseif ($otherInfo) {
                $whereUpdate = $whereOther;
            } elseif ($info) {
                $whereUpdate = $where;
            }
            fdump_api(['whereUpdate' => $whereUpdate, 'updateBind' => $updateBind, 'whereDelete' => $whereDelete, 'updateDelete' => $updateDelete, 'line' => __LINE__],'repairCateBindSingleQuality', 1);
            $dbHouseCommunityQualityBindRepairCate->updateThis($whereUpdate, $updateBind);
            if (!empty($whereDelete)) {
                $dbHouseCommunityQualityBindRepairCate->updateThis($whereDelete, $updateDelete);
            }
        }
        return $params;
    }

    /**
     * 通过工单分类id获取对应绑定的质保信息
     */
    public function getRepairCateQuality($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $repair_cate_id = isset($params['repair_cate_id']) ? intval($params['repair_cate_id']) : 0;
        $single_id = isset($params['single_id']) ? intval($params['single_id']) : 0;
        $room_id = isset($params['room_id']) ? intval($params['room_id']) : 0;
        $quality_id = isset($params['quality_id']) ? intval($params['quality_id']) : 0;
        $where = [];
        if ($property_id) {
            $where[] = ['property_id', '=', $property_id];
        }
        $where[] = ['village_id', '=', $village_id];
        if (isset($params['single_id'])) {
            $where[] = ['single_id', '=', $single_id];
        }
        if (isset($params['room_id'])) {
            $where[] = ['room_id', '=', $room_id];
        }
        if (isset($params['repair_cate_id'])) {
            $where[] = ['repair_cate_id', '=', $repair_cate_id];
        }
        if (isset($params['quality_id'])) {
            $where[] = ['quality_id', '=', $quality_id];
        }
        $where[] = ['delete_time', '=', 0];
        $dbHouseCommunityQualityBindRepairCate = new HouseCommunityQualityBindRepairCate();
        $field = "id, property_id, village_id, single_id, repair_cate_id, quality_id, quality_start_time, quality_end_time, not_timely_report_switch";
        $info = $dbHouseCommunityQualityBindRepairCate->getOne($where, $field);
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        return $info;
    }
    
    public function judgeRepairCateQualityFee($params) {
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $repair_cate_id = isset($params['repair_cate_id']) ? intval($params['repair_cate_id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $single_id = isset($params['single_id']) ? intval($params['single_id']) : 0;
        $room_id = isset($params['room_id']) ? intval($params['room_id']) : 0;
        $nowTime = time();
        if (isset($params['jz_open'])) {
            $jz_open = $params['jz_open'];
        } else {
            $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        }
        // 是否为免费
        $hasFee = false;
        if ($jz_open) {
            $params = [
                'village_id' => $village_id,
                'single_id' => $single_id,
                'room_id' => 0,
                'repair_cate_id' => $repair_cate_id,
            ];
            $qualityInfo = (new HouseCommunityQualityService())->getRepairCateQuality($params);
            if ($room_id > 0) {
                $roomParams = [
                    'village_id' => $village_id,
                    'single_id' => $single_id,
                    'room_id' => $room_id,
                    'quality_id' => $qualityInfo['quality_id'],
                ];
                $roomQualityInfo = (new HouseCommunityQualityService())->getRepairCateQuality($roomParams);
                $room_quality_start_time = isset($roomQualityInfo['quality_start_time']) ? intval($roomQualityInfo['quality_start_time']) : 0;
                $room_quality_end_time = isset($roomQualityInfo['quality_end_time']) ? intval($roomQualityInfo['quality_end_time']) : 0;
            } else {
                $room_quality_start_time = 0;
                $room_quality_end_time = 0;
            }
            if ($room_quality_start_time > 100 && $room_quality_end_time > 100) {
                $quality_start_time = $room_quality_start_time;
                $quality_end_time = $room_quality_end_time;
            } else {
                $quality_start_time = isset($qualityInfo['quality_start_time']) ? intval($qualityInfo['quality_start_time']) : 0;
                $quality_end_time = isset($qualityInfo['quality_end_time']) ? intval($qualityInfo['quality_end_time']) : 0;
            }
            if ($quality_start_time <= $nowTime && $quality_end_time > $nowTime) {
                $hasFee = true;
            }
        }
        return $hasFee;
    }

    /**
     * 处理自动添加默认质保.
     */
    public function tryCatchAutoHandleAddQualityBox($params) {
        try{
            return $this->autoHandleAddQuality($params);
        }catch (\Exception $e){
            fdump_api(['err' => '异常捕捉盒子报错-处理自动添加默认质保', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCatchAutoHandleAddQualityBoxLog', 1);
            return false;
        }
    }
    
    /**
     * 处理自动添加默认质保.
     */
    protected function autoHandleAddQuality($params) {
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        if (!$property_id) {
            return false;
        }
        $dbHouseCommunityQuality = new HouseCommunityQuality();
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['village_id', '=', 0];
        $where[] = ['is_system_auto', '=', 1];
        $info = $dbHouseCommunityQuality->getOne($where, 'id');
        if (isset($info['id']) && $info['id']) {
            return true;
        }
        $qualityArr = $this->getSystemAutoQuality();
        $nowTime = time();
        foreach ($qualityArr as $quality) {
            $category_name = isset($quality['category_name']) ? trim($quality['category_name']) : '';
            $category_name_source = isset($quality['category_name_source']) ? trim($quality['category_name_source']) : $category_name;
            $remark = isset($quality['remark']) ? trim($quality['remark']) : '';
            $is_system_auto = isset($quality['is_system_auto']) ? intval($quality['is_system_auto']) : 1;
            $data = [
                'property_id' => $property_id, 'village_id' => 0, 'category_name' => $category_name,
                'category_name_source' => $category_name_source, 'fid' => 0, 'remark' => $remark,
                'add_time' => $nowTime, 'is_system_auto' => $is_system_auto,
            ];
            $child_fid = $dbHouseCommunityQuality->add($data);
            if (!$child_fid) {
                continue;
            }
            $child = isset($quality['child']) ? $quality['child'] : [];
            foreach ($child as $childQuality) {
                $child_category_name = isset($childQuality['category_name']) ? trim($childQuality['category_name']) : '';
                $child_category_name_source = isset($childQuality['category_name_source']) ? trim($childQuality['category_name_source']) : $child_category_name;
                $child_remark = isset($childQuality['remark']) ? trim($childQuality['remark']) : '';
                $child_is_system_auto = isset($childQuality['is_system_auto']) ? intval($childQuality['is_system_auto']) : 1;
                $child_data = [
                    'property_id' => $property_id, 'village_id' => 0, 'category_name' => $child_category_name,
                    'category_name_source' => $child_category_name_source, 'fid' => $child_fid, 'remark' => $child_remark,
                    'add_time' => $nowTime, 'is_system_auto' => $child_is_system_auto,
                ];
                $dbHouseCommunityQuality->add($child_data);
            }
        }
        return  true;
    }
    
    public function getSystemAutoQuality() {
        $quality = [
            ['category_name' => '装饰装修工程', 'category_name_source' => '装饰装修工程', 'remark' => '', 'is_system_auto' => 1,
                'child' => [
                    ['category_name' => '入户门', 'category_name_source' => '入户门', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '窗户', 'category_name_source' => '窗户', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '屋顶', 'category_name_source' => '屋顶', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '墙面', 'category_name_source' => '墙面', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '地面', 'category_name_source' => '地面', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '精装类', 'category_name_source' => '精装类', 'remark' => '', 'is_system_auto' => 1],
                ],
            ],
            ['category_name' => '防水工程', 'category_name_source' => '防水工程', 'remark' => '', 'is_system_auto' => 1,
                'child' => [
                    ['category_name' => '外墙', 'category_name_source' => '外墙', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '室内', 'category_name_source' => '室内', 'remark' => '', 'is_system_auto' => 1],
                ]
            ],
            ['category_name' => '给排水', 'category_name_source' => '给排水', 'remark' => '', 'is_system_auto' => 1,
                'child' => [
                    ['category_name' => '给排水', 'category_name_source' => '给排水', 'remark' => '', 'is_system_auto' => 1],
                ]
            ],
            ['category_name' => '安防工程', 'category_name_source' => '安防工程', 'remark' => '', 'is_system_auto' => 1,
                'child' => [
                    ['category_name' => '强电控制箱', 'category_name_source' => '强电控制箱', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '弱电控制箱', 'category_name_source' => '弱电控制箱', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '插座', 'category_name_source' => '插座', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '开关', 'category_name_source' => '开关', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '灯具', 'category_name_source' => '灯具', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '室内防护栏', 'category_name_source' => '室内防护栏', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '室内可视对讲机', 'category_name_source' => '室内可视对讲机', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '烟感', 'category_name_source' => '烟感', 'remark' => '', 'is_system_auto' => 1],
                    ['category_name' => '喷淋', 'category_name_source' => '喷淋', 'remark' => '', 'is_system_auto' => 1],
                ]
            ],
            ['category_name' => '供暖设施', 'category_name_source' => '供暖设施', 'remark' => '', 'is_system_auto' => 1,
                'child' => [
                    ['category_name' => '供暖设施', 'category_name_source' => '供暖设施', 'remark' => '', 'is_system_auto' => 1],
                ]
            ],
            ['category_name' => '空调系统', 'category_name_source' => '空调系统', 'remark' => '', 'is_system_auto' => 1,
                'child' => [
                    ['category_name' => '空调系统', 'category_name_source' => '空调系统', 'remark' => '', 'is_system_auto' => 1],
                ]
            ],
        ];
        return $quality;
    }
}