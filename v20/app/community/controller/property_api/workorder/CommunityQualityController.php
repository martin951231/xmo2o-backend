<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物业端质保明细
 */

namespace app\community\controller\property_api\workorder;

use app\community\controller\CommunityBaseController;
use app\community\model\service\workorder\HouseCommunityQualityService;

class CommunityQualityController extends CommunityBaseController
{
    /**
     * 添加或更新质保类别
     */
    public function addCommunityQuality()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->post('id', 0, 'intval');
        $category_name = $this->request->post('category_name', '', 'trim');
        $fid = $this->request->post('fid', 0, 'intval');
        $remark = $this->request->post('remark', '', 'trim');
        if (!$category_name) {
            return api_output(1001, [], '请填写质保类别名称！');
        }
        $from_user = ['type' => 'property_admin', 'admin_id' => $this->adminUser['property_id'], 'account' => $this->adminUser['account']];
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
        $result = [];
        try {
            $params = [
                'id' => $id,
                'fid' => $fid,
                'category_name' => $category_name,
                'remark' => $remark,
                'from_user' => $from_user,
                'property_id' => $property_id,
            ];
            $result['info'] = (new HouseCommunityQualityService())->updateOrAddHouseCommunityQuality($params);
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
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
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
     * 获取质保类别列表.
     */
    public function getList() {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $page = $this->request->post('page', 1, 'intval');
        $pageSize = $this->request->post('pageSize', 20, 'intval');
        !$pageSize && $pageSize = 20;
        $result = [];
        try {
            $where = [];
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['delete_time', '=', 0];
            $result['count'] = (new HouseCommunityQualityService())->getCount($where);
            $params = [
                'page' => $page,
                'pageSize' => $pageSize,
                'property_id' => $property_id,
            ];
            $result['list'] = (new HouseCommunityQualityService())->getList($params);
            $result['page'] = $page;
            $result['pageSize'] = $pageSize;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 获取质保分类详细信息.
     */
    public function getCommunityQualityDetail()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->post('id', 0, 'intval');
        $result = [];
        try {
            $params = [
                'id' => $id,
                'property_id' => $property_id,
            ];
            $result['detail'] = (new HouseCommunityQualityService())->getCommunityQualityDetail($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    public function deleteCommunityQuality() {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->post('id', 0, 'intval');
        $result = [];
        try {
            $delete_user = ['type' => 'property_admin', 'admin_id' => $this->adminUser['property_id'], 'account' => $this->adminUser['account']];
            if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
                $delete_user['name'] = $this->adminUser['user_name'];
            } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
                $delete_user['name'] = $this->adminUser['adminName'];
            }
            if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
                $delete_user['phone'] = $this->adminUser['phone'];
            }
            if ($this->_uid) {
                $delete_user['_userId'] = $this->_uid;
            }
            if ($this->login_userId) {
                $delete_user['login_userId'] = $this->login_userId;
            }
            if ($this->login_role) {
                $delete_user['login_role'] = $this->login_role;
            }
            $delete_user = json_encode($delete_user, JSON_UNESCAPED_UNICODE);
            $params = [
                'id' => $id,
                'property_id' => $property_id,
                'delete_user' => $delete_user,
            ];
            $result['delete'] = (new HouseCommunityQualityService())->deleteCommunityQuality($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 获取申请列表.
     */
    public function applyCommunityQualityList() {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $page = $this->request->post('page', 1, 'intval');
        $pageSize = $this->request->post('pageSize', 20, 'intval');
        !$pageSize && $pageSize = 20;
        $result = [];
        try {
            $where = [];
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['apply_delete_time', '=', 0];
            $where[] = ['check_delete_time', '=', 0];
            $result['count'] = (new HouseCommunityQualityService())->getApplyCount($where);
            $params = [
                'page' => $page,
                'pageSize' => $pageSize,
                'property_id' => $property_id,
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
     * 获取申请详情.
     */
    public function getApplyDetail()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->post('id', 0, 'intval');
        $result = [];
        try {
            $params = [
                'id' => $id,
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
     * 审核申请.
     */
    public function checkApply() {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->post('id', 0, 'intval');
        $status = $this->request->post('status', '', 'intval');
        $refuse_reason = $this->request->post('refuse_reason', '', 'trim');
        $result = [];
        try {
            $checkStatus = (new HouseCommunityQualityService())->getCheckStatus();
            if (!in_array($status, $checkStatus)) {
                return api_output(1001, [], '审核状态有误！');
            }
            if ($status == 2 && !$refuse_reason) {
                return api_output(1001, [], '审核拒绝请填写理由！');
            }
            $from_user = ['type' => 'property_admin', 'admin_id' => $this->adminUser['property_id'], 'account' => $this->adminUser['account']];
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
                'id' => $id,
                'property_id' => $property_id,
                'status' => $status,
                'refuse_reason' => $refuse_reason,
                'check_user' => $from_user,
            ];
            $result['detail'] = (new HouseCommunityQualityService())->checkApply($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 删除申请.
     */
    public function deleteChangeApply() {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $id = $this->request->post('id', '', 'intval');
        $result = [];
        try {
            $from_user = ['type' => 'property_admin', 'admin_id' => $this->adminUser['property_id'], 'account' => $this->adminUser['account']];
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
                'id' => $id,
                'property_id' => $property_id,
                'check_delete_user' => $from_user,
            ];
            $result['result'] = (new HouseCommunityQualityService())->deleteChangeApply($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}