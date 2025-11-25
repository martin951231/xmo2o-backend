<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      系统后台合同
 */

namespace app\community\controller\platform\contract;

use app\community\controller\platform\AuthBaseController as BaseController;
use app\community\model\service\contract\ContractHandleService;
use app\community\model\service\HouseVillageService;
use app\consts\ContractConst;

class IndexController extends BaseController
{
    /**
     * 获取合同类别[列表筛选/添加选择类别/编辑选择类别]
     */
    public function getClassifyList()
    {
        try {
            $result = [];
            $where = [];
            $where[] = ['status', '=', 1];
            $where[] = ['delete_time', '=', 0];
            $param = [
                'where' => $where, 'from' => 'system', 'field' => 'id, name, status', 'order' => 'add_time DESC', 'page' => 0, 'pageSize' => 0, 'isHandle' => 0,
            ];
            $list = (new ContractHandleService())->getContractClassifyList($param);
            $selectList = [];
            foreach ($list as $item) {
                $selectList[] = [
                    'label' => $item['name'],
                    'value' => $item['id'],
                ];
            }
            $result['selectList'] = $selectList;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 获取合同列表.
     */
    public function getList() {
        if (!$this->_uid) {
            return api_output_error(1002);
        }
        $page     = $this->request->param('page',    '0','intval');
        $pageSize = $this->request->param('pageSize','15','intval');
        $contract_number = $this->request->param('contract_number','','trim');
        $contract_name = $this->request->param('contract_name','','trim');
        $village_name = $this->request->param('village_name','','trim');
        $status = $this->request->param('status','','trim');
        $classify_id = $this->request->param('classify_id',0,'intval');
        $result = [];
        $where = [];
        try {
            $nowTime = time();
            $classify_id && $where[] = ['classify_id', '=', $classify_id];
            switch ($status) {
                case ContractConst::CONTRACT_NOT_EFFECTIVE:
                    $where[] = ['contract_start_time', '>', $nowTime];
                    break;
                case ContractConst::CONTRACT_IN_EFFECTIVE:
                    $where[] = ['contract_start_time', '<=', $nowTime];
                    $where[] = ['contract_end_time', '>', $nowTime];
                    break;
                case ContractConst::CONTRACT_LOSE_EFFICACY:
                    $where[] = ['contract_end_time', '<', $nowTime];
                    break;
            }
            if ($village_name) {
                $whereVillageColumn = [];
                $whereVillageColumn[] = ['village_name', 'like', "%" . $village_name . "%"];
                $whereVillageColumn[] = ['status', '=', 1];
                $village_id_arr = (new HouseVillageService())->getVillageColumn($whereVillageColumn, 'village_id');
                $where[] = ['village_id', 'in', $village_id_arr];
            }
            if ($contract_number) {
                $where[] = ['contract_number', 'like', "%" . $contract_number . "%"];
            }
            if ($contract_name) {
                $where[] = ['contract_name', 'like', "%" . $contract_name . "%"];
            }
            $where[] = ['delete_time', '=', 0];
            $result['count'] = (new ContractHandleService())->getContractCount($where);
            $param = [
                'where' => $where, 'from' => 'system', 'order' => 'add_time DESC', 'page' => $page, 'pageSize' => $pageSize, 'isHandle' => 1,
            ];
            $result['list'] = (new ContractHandleService())->getContractList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $result['statusSelect'] = [
            ['label' => '全部', 'value' => ContractConst::CONTRACT_ALl],
            ['label' => '待生效', 'value' => ContractConst::CONTRACT_NOT_EFFECTIVE],
            ['label' => '生效中', 'value' => ContractConst::CONTRACT_IN_EFFECTIVE],
            ['label' => '已失效', 'value' => ContractConst::CONTRACT_LOSE_EFFICACY],
        ];
        return api_output(0, $result);
    }

    /**
     * 添加/更新
     */
    public function setContractInfo() {
        $id = $this->request->param('id', 0, 'intval');
        $contract_number = $this->request->param('contract_number', '', 'trim');
        $contract_name = $this->request->param('contract_name', '', 'trim');
        $classify_id = $this->request->param('classify_id', 0, 'intval');
        $contract_start_time = $this->request->param('contract_start_time', 0, 'trim');
        $contract_end_time = $this->request->param('contract_end_time', 0, 'trim');
        $contract_content = $this->request->param('contract_content', '', 'trim');
        $village_id = $this->request->param('village_id', 0, 'intval');
        $work_id = $this->request->param('work_id', 0, 'intval');
        $phone = $this->request->param('phone', '', 'trim');
        $contract_appendix = $this->request->param('contract_appendix');
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
                'contract_number' => $contract_number,'contract_name' => $contract_name, 'from_user' => $from_user, 'id' => $id,
                'classify_id' => $classify_id, 'contract_start_time' => $contract_start_time, 'contract_end_time' => $contract_end_time,
                'contract_content' => $contract_content, 'village_id' => $village_id, 'work_id' => $work_id, 'phone' => $phone,
                'contract_appendix' => $contract_appendix, 'from' => 'system',
            ];
            $result['result'] = (new ContractHandleService())->setContract($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 添加和更新都要请求下.
     */
    public function getAddUpdateContractConfig() {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $result = [];
            $param = [
                'id' => $id, 'from' => 'system',
            ];
            $list = (new ContractHandleService())->getContractConfig($param);
            $result['result'] = $list;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 详情 合同
     */
    public function getContractInfo() {
        $id = $this->request->param('id',0,'intval');
        try {
            $where = [];
            $where[] = ['id', '=', $id];
            $result['result'] = (new ContractHandleService())->getContractInfo($where, '', 1);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 删除 合同
     */
    public function deleteContractInfo() {
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
                'is_del' => 1, 'from' => 'system', 'from_user' => $from_user, 'id' => $id
            ];
            $result['result'] = (new ContractHandleService())->setContract($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 获取配置.
     */
    public function getConfig() {
        try {
            $params = ['village_id' => 0, 'from' => 'system'];
            $result['config'] = (new ContractHandleService())->getConfig($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 设置配置.
     */
    public function setConfig() {
        $expire_to_report_day = $this->request->param('expire_to_report_day',0,'intval');
        try {
            $params = [];
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
            $params['from'] = 'system';
            $params['village_id'] = 0;
            $params['from_user'] = $from_user;
            $params['expire_to_report_day'] = $expire_to_report_day;
            $result['config'] = (new ContractHandleService())->setConfig($params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}