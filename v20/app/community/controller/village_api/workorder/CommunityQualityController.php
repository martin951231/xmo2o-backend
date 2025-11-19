<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      小区端质保明细
 */

namespace app\community\controller\village_api\workorder;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\workorder\HouseCommunityQualityService;

class CommunityQualityController extends CommunityBaseController
{
    public function getOrderCheckSetInfo() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $xtype = $this->request->post('xtype', 'work_order_transfer_check', 'trim');
        !$xtype && $xtype = 'work_order_transfer_check';
        $xtype = trim($xtype);
        $xtype = htmlspecialchars($xtype, ENT_QUOTES);
        $param = array('village_id' => $village_id, 'property_id' => $property_id);
        $param['xtype'] = $xtype;
        try {
            $result['info'] = (new HouseCommunityQualityService())->getOrderCheckSetInfo($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 获取工作人员.
     */
    public function getWorkList() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $whereWork = array(['village_id', '=', $village_id], ['is_del', '=', 0]);
        $whereWork[] = ['status', '=', 1];
        $whereWork[] = ['department_id', '>', 0];
        $fieldStr = 'wid,type,phone,name,openid,nickname';
        try {
            $workList = (new HouseWorkerService())->get_all_worker_list($whereWork, $fieldStr, 0);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if (isset($workList['list']) && $workList['list'] && !is_array($workList['list'])) {
            $workList = $workList['list']->toArray();
        } else {
            $workList = isset($workList['list']) ? $workList['list'] : [];
        }
        $selects = [];
        foreach ($workList as $work) {
            $selects[] = [
                'value' => $work['wid'],
                'label' => $work['name'],
                'phone' => $work['phone'],
                'type_name' => isset($work['type_name']) ? trim($work['type_name']) : '',
            ];
        }
        return api_output(0, ['work_list' => $selects]);
    }
    
    /**
     * 工单转单审核设置.
     */
    public function orderCheckSet() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $work_order_transfer_check_open = $this->request->post('work_order_transfer_check', 0, 'intval');
        $check_level = $this->request->post('order_check_level', []);
        $xtype = $this->request->post('xtype', 'work_order_transfer_check', 'trim');
        !$xtype && $xtype = 'work_order_transfer_check';
        $xtype = trim($xtype);
        $xtype = htmlspecialchars($xtype, ENT_QUOTES);
        $param = array('village_id' => $village_id, 'property_id' => $property_id);
        $param['xtype'] = $xtype;
        if ($xtype == 'work_order_transfer_check') {
            $param['tname'] = '工单转单审核设置';
        }
        $setData = array();
        if ($work_order_transfer_check_open == 1 && empty($check_level)) {
            return api_output(1001, [], '请至少选择设置一项审批人！');
        }
        $param = array('village_id' => $village_id, 'property_id' => $property_id);
        $param['xtype'] = $xtype;
        $checkInfo = (new HouseCommunityQualityService())->getOrderCheckSetInfo($param);
        if (isset($checkInfo['msg']) && $checkInfo['msg']) {
            return api_output(1001, [], "有在审核的流程未结束（{$checkInfo['msg']}），不可改动相关设置");
        }
        if (!empty($check_level)) {
            foreach ($check_level as $vv) {
                $setData[$vv['level_v']] = $vv;
            }
            ksort($setData);
        }
        if (count($setData) > 1) {
            $chooseWids = [];
            foreach ($check_level as $key => $level) {
                $num = $key + 1;
                if (!isset($level['level_wid']) || !$level['level_wid']) {
                    return api_output(1001, [], "请选择第{$num}项审批人！");
                }
                if (in_array($level['level_wid'], $chooseWids)) {
                    return api_output(1001, [], "第{$num}项审批人已经选择过了，请选择其他人！");
                }
                $chooseWids[] = $level['level_wid'];
            }
        }
        $param['is_open'] = $work_order_transfer_check_open;
        $param['set_datas'] = json_encode($setData, JSON_UNESCAPED_UNICODE);
        try {
            $result['result'] = (new HouseCommunityQualityService())->orderCheckSet($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    
    /**
     * 获取符合条件的质保类别列表.
     */
    public function getCommunityQualityList()
    {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $fid = $this->request->post('fid', 0, 'intval');
        $result = [];
        try {
            $params = [
                'fid' => $fid,
                'property_id' => $property_id,
            ];
            $result['list'] = (new HouseCommunityQualityService())->getCommunityQualityList($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 修改质保类别名称申请
     */
    public function applyChangeCommunityQualityName() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $quality_id = $this->request->post('quality_id', '', 'intval');
        $change_name = $this->request->post('change_name', '', 'trim');
        $reason = $this->request->post('reason', '', 'trim');
        $result = [];
        try {
            $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
            $apply_name = $this->adminUser['village_name'];
            if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
                $from_user['name'] = $this->adminUser['user_name'];
                $apply_name = $this->adminUser['user_name'];
            } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
                $from_user['name'] = $this->adminUser['adminName'];
                $apply_name = $this->adminUser['adminName'];
            }
            if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
                $from_user['phone'] = $this->adminUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            $apply_login_userId = '';
            if ($this->login_userId) {
                $from_user['login_userId'] = $this->login_userId;
                $apply_login_userId = $this->login_userId;
            }
            $apply_login_role = '';
            if ($this->login_role) {
                $from_user['login_role'] = $this->login_role;
                $apply_login_role = $this->login_role;
            }
            $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
            $params = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'quality_id' => $quality_id,
                'change_name' => $change_name,
                'reason' => $reason,
                'apply_user' => $from_user,
                'apply_name' => $apply_name,
                'apply_login_userId' => $apply_login_userId,
                'apply_login_role' => $apply_login_role,
            ];
            $result['result'] = (new HouseCommunityQualityService())->applyChangeCommunityQualityName($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 获取申请列表.
     */
    public function applyCommunityQualityList() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $page = $this->request->post('page', 1, 'intval');
        $pageSize = $this->request->post('pageSize', 20, 'intval');
        !$pageSize && $pageSize = 20;
        $result = [];
        try {
            $where = [];
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['apply_delete_time', '=', 0];
            $result['count'] = (new HouseCommunityQualityService())->getApplyCount($where);
            $params = [
                'page' => $page,
                'pageSize' => $pageSize,
                'property_id' => $property_id,
                'village_id' => $village_id,
            ];
            $result['list'] = (new HouseCommunityQualityService())->getApplyList($params);
            $result['page'] = $page;
            $result['pageSize'] = $pageSize;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 删除申请.
     */
    public function deleteChangeApply() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $id = $this->request->post('id', '', 'intval');
        $result = [];
        try {
            $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
            if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
                $from_user['name'] = $this->adminUser['user_name'];
            } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
                $from_user['name'] = $this->adminUser['adminName'];
            }
            if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
                $from_user['phone'] = $this->adminUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            if ($this->login_userId) {
                $from_user['login_userId'] = $this->login_userId;
            }
            if ($this->login_role) {
                $from_user['login_role'] = $this->login_role;
            }
            $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
            $params = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'id' => $id,
                'apply_delete_user' => $from_user,
            ];
            $result['result'] = (new HouseCommunityQualityService())->deleteChangeApply($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 获取申请详情.
     */
    public function getApplyDetail()
    {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $id = $this->request->post('id', 0, 'intval');
        $result = [];
        try {
            $params = [
                'id' => $id,
                'village_id' => $village_id,
                'property_id' => $property_id,
                'isHandle' => 1,
            ];
            $result['detail'] = (new HouseCommunityQualityService())->getApplyDetail($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 获取楼栋质保信息.
     */
    public function getSingleCommunityQualityInfo() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $single_id        = $this->request->post('single_id', '', 'intval');
        $result = [];
        try {
            $params = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'single_id' => $single_id,
            ];
            $result['list'] = (new HouseCommunityQualityService())->getSingleCommunityQualityInfo($params);
            $result['applyChangeName'] = true;
            $result['applyChangeNameLog'] = true;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 设置楼栋质保信息.
     */
    public function setSingleCommunityQuality() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $single_id        = $this->request->post('single_id', '', 'intval');
        $qualityParams    = $this->request->post('qualityParams');
        $result = [];
        try {
            if (empty($qualityParams)){
                return api_output(1001, [], '缺少更改对象信息！');
            }
            $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
            if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
                $from_user['name'] = $this->adminUser['user_name'];
            } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
                $from_user['name'] = $this->adminUser['adminName'];
            }
            if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
                $from_user['phone'] = $this->adminUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            if ($this->login_userId) {
                $from_user['login_userId'] = $this->login_userId;
            }
            if ($this->login_role) {
                $from_user['login_role'] = $this->login_role;
            }
            $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
            $params = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'single_id' => $single_id,
                'qualityParams' => $qualityParams,
                'from_user' => $from_user,
            ];
            $result['result'] = (new HouseCommunityQualityService())->setSingleCommunityQuality($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 获取房屋质保信息.
     */
    public function getRoomCommunityQualityInfo() 
    {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $room_id        = $this->request->post('room_id', '', 'intval');
        $result = [];
        try {
            $params = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'room_id' => $room_id,
            ];
            $result['list'] = (new HouseCommunityQualityService())->getRoomCommunityQualityInfo($params);
            $result['applyChangeName'] = true;
            $result['applyChangeNameLog'] = true;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 设置房屋质保信息.
     */
    public function setRoomCommunityQuality() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $room_id        = $this->request->post('room_id', '', 'intval');
        $qualityParams    = $this->request->post('qualityParams');
        $result = [];
        try {
            if (empty($qualityParams)){
                return api_output(1001, [], '缺少更改对象信息！');
            }
            $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
            if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
                $from_user['name'] = $this->adminUser['user_name'];
            } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
                $from_user['name'] = $this->adminUser['adminName'];
            }
            if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
                $from_user['phone'] = $this->adminUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            if ($this->login_userId) {
                $from_user['login_userId'] = $this->login_userId;
            }
            if ($this->login_role) {
                $from_user['login_role'] = $this->login_role;
            }
            $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
            $params = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'room_id' => $room_id,
                'qualityParams' => $qualityParams,
                'from_user' => $from_user,
            ];
            $result['result'] = (new HouseCommunityQualityService())->setRoomCommunityQuality($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 获取楼栋.
     */
    public function getSingleList()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $result = [];
        try {
            $serviceHouseMeter = new HouseMeterService();
            $single_list = $serviceHouseMeter->getSingleList($village_id);
            $selects = [];
            $selects[] = [
                'value' => 0,
                'label' => '不绑定',
                'single_name' => '',
                'village_id' => 0,
            ];
            foreach ($single_list as $single) {
                $selects[] = [
                    'value' => $single['id'],
                    'label' => $single['name'],
                    'single_name' => isset($single['single_name']) ? trim($single['single_name']) : '',
                    'village_id' => isset($single['village_id']) ? intval($single['village_id']) : 0,
                ];
            }
            $result['list'] = $selects;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 获取楼栋下正常的未绑定的.
     */
    public function getSingleCommunityQualityList() {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $property_id      = $this->adminUser['property_id'];
        $single_id        = $this->request->post('single_id', '', 'intval');
        $quality_id       = $this->request->post('quality_id', '', 'intval');
        $result = [];
        try {
            $params = [
                'property_id' => $property_id,
                'village_id' => $village_id,
                'single_id' => $single_id,
                'quality_id' => $quality_id,
                'notBind' => 1,
            ];
            $quality_list = (new HouseCommunityQualityService())->getSingleCommunityQualityInfo($params);
            $selects = [];
            $selects[] = [
                'value' => 0,
                'label' => '不绑定',
                'single_name' => '',
                'village_id' => 0,
            ];
            $selectTips = [];
            foreach ($quality_list as $quality) {
                $category_name = isset($quality['category_name']) ? trim($quality['category_name']) : '';
                $fid_category_name = isset($quality['fid_category_name']) ? trim($quality['fid_category_name']) : '';
                $label = $fid_category_name && $category_name ? $fid_category_name . '/' . $category_name : $category_name;
                $selects[] = [
                    'value' => $quality['id'],
                    'label' => $label,
                    'category_name' => $category_name,
                    'fid_category_name' => $fid_category_name,
                    'fid' => isset($quality['fid']) ? intval($quality['fid']) : 0,
                ];
                if (isset($quality['quality_start_time_text']) && $quality['quality_start_time_text'] && isset($quality['quality_end_time_text']) && $quality['quality_end_time_text']) {
                    $selectTips[$quality['id']] = "保修期限: {$quality['quality_start_time_text']} 至 {$quality['quality_end_time_text']}";
                } else {
                    $selectTips[$quality['id']] = "保修期限暂未设置，可前往【人车房管理】>【楼栋管理】>对应楼栋【质检明细】进行设置";
                }
            }
            $result['list'] = $selects;
            $result['selectTips'] = $selectTips;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}