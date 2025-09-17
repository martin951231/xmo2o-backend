<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      系统后台合同类型
 */

namespace app\community\controller\platform\contract;

use app\community\controller\platform\AuthBaseController as BaseController;
use app\community\model\service\contract\ContractHandleService;

class ClassifyController extends BaseController
{
    /**
     * 获取合同分类列表.
     */
    public function getClassifyList() {
        if (!$this->_uid) {
            return api_output_error(1002);
        }
        $page     = $this->request->param('page',    '0','intval');
        $name = $this->request->param('name','','trim');
        $pageSize = $this->request->param('pageSize','15','intval');
        try {
            $result = [];
            $where = [];
            if ($name) {
                $where[] = ['name', 'like', "%" . $name . "%"];
            }
            $where[] = ['delete_time', '=', 0];
            $result['count'] = (new ContractHandleService())->getContractClassifyCount($where);
            $param = [
                'where' => $where, 'field' => 'id, name, status, add_time, update_time, delete_time',
                'order' => 'add_time DESC', 'page' => $page, 'pageSize' => $pageSize, 'isHandle' => 1,
            ];
            $result['list'] = (new ContractHandleService())->getContractClassifyList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 添加/更新 合同类型
     */
    public function setClassifyInfo() {
        $id = $this->request->param('id',0,'intval');
        $name = $this->request->param('name','','trim');
        $status = $this->request->param('status');
        if (strval($status) == 2 || strval($status) == 'false') {
            $status = 2;
        } else {
            $status = 1;
        }
        try {
            $from_user = ['type' => 'system_admin', 'admin_id' => $this->systemUser['id'], 'account' => $this->systemUser['account']];
            if (isset($this->systemUser['realname']) && $this->systemUser['realname']) {
                $from_user['name'] = $this->systemUser['realname'];
            } elseif (isset($this->systemUser['show_account']) && $this->systemUser['show_account']) {
                $from_user['name'] = $this->systemUser['show_account'];
            }
            if (isset($this->systemUser['phone']) && $this->systemUser['phone']) {
                $from_user['phone'] = $this->systemUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            if (isset($this->systemUser['level']) && $this->systemUser['level']) {
                $from_user['level'] = $this->systemUser['level'];
            }
            if (isset($this->systemUser['last_ip']) && $this->systemUser['last_ip']) {
                $from_user['last_ip'] = $this->systemUser['last_ip'];
            }
            $result = [];
            $param = [
                'name' => $name, 'status' => $status, 'from_user' => $from_user, 'id' => $id
            ];
            $result['result'] = (new ContractHandleService())->setContractClassify($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 详情 合同类型
     */
    public function getClassifyInfo() {
        $id = $this->request->param('id',0,'intval');
        try {
            $where = [];
            $where[] = ['id', '=', $id];
            $result['result'] = (new ContractHandleService())->getContractClassifyInfo($where);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 删除 合同类型
     */
    public function deleteClassifyInfo() {
        $id = $this->request->param('id',0,'intval');
        try {
            $from_user = ['type' => 'system_admin', 'admin_id' => $this->systemUser['id'], 'account' => $this->systemUser['account']];
            if (isset($this->systemUser['realname']) && $this->systemUser['realname']) {
                $from_user['name'] = $this->systemUser['realname'];
            } elseif (isset($this->systemUser['show_account']) && $this->systemUser['show_account']) {
                $from_user['name'] = $this->systemUser['show_account'];
            }
            if (isset($this->systemUser['phone']) && $this->systemUser['phone']) {
                $from_user['phone'] = $this->systemUser['phone'];
            }
            if ($this->_uid) {
                $from_user['_userId'] = $this->_uid;
            }
            if (isset($this->systemUser['level']) && $this->systemUser['level']) {
                $from_user['level'] = $this->systemUser['level'];
            }
            if (isset($this->systemUser['last_ip']) && $this->systemUser['last_ip']) {
                $from_user['last_ip'] = $this->systemUser['last_ip'];
            }
            $result = [];
            $param = [
                'is_del' => 1, 'from_user' => $from_user, 'id' => $id
            ];
            $result['result'] = (new ContractHandleService())->setContractClassify($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}