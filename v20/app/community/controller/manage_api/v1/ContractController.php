<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      移动管理端合同
 */
namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\contract\ContractHandleService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\PropertyAdminService;
use app\consts\ContractConst;
use app\consts\HouseWorkConst;

class ContractController extends BaseController
{
    /**
     * 获取合同列表.
     */
    public function getList() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $page     = $this->request->param('page',    '0','intval');
        $pageSize = $this->request->param('pageSize','15','intval');
        $search = $this->request->param('search','','trim');
        $status = $this->request->param('status','','trim');
        $result = [];
        $where = [];
        $login_role = $this->login_role;
        $_uid = $this->_uid;
        if ($login_role == HouseWorkConst::MANAGE_APP_STREET_WORK) {
            return api_output_error(1008,'当前不支持街道社区人员查看');
        }
        if ($login_role == HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN) {
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $_uid];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
            $where[] = ['village_id', 'in', $villageIds];
        }
        if ($login_role == HouseWorkConst::MANAGE_APP_PROPERTY_USER) {
            $where_admin = [];
            $where_admin[] = ['id', '=', $_uid];
            $propertyAdminInfo = (new PropertyAdminService())->getFind($where_admin,'property_id');
            $property_id = isset($propertyAdminInfo['property_id']) ? intval($propertyAdminInfo['property_id']) : 0;
            $whereVillage = [];
            $whereVillage[] = ['property_id', '=', $property_id];
            $whereVillage[] = ['status', '=', 1];
            $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
            $where[] = ['village_id', 'in', $villageIds];
        }
        if ($login_role == HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN) {
            $village_id  = isset($this->login_info['village_id']) ? $this->login_info['village_id'] : 0;
            $where[] = ['village_id', '=', $village_id];
        }
        if ($login_role == HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
            $where[] = ['work_id', '=', $_uid];
            if (isset($login_info['village_id']) && $login_info['village_id']) {
                $where[] = ['village_id', '=', $village_id];
            }
        }
        try {
            $nowTime = time();
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
            if ($search) {
                $where[] = ['contract_number|contract_name', 'like', "%" . $search . "%"];
            }
            $where[] = ['delete_time', '=', 0];
            $result['count'] = (new ContractHandleService())->getContractCount($where);
            $param = [
                'where' => $where, 'order' => 'add_time DESC', 'page' => $page, 'pageSize' => $pageSize, 'isHandle' => 1, 'from' => 'village',
            ];
            $param['field'] = 'id, contract_number, contract_name, classify_id, classify_name, contract_start_time, contract_end_time, village_id, from, add_time';
            $data = (new ContractHandleService())->getContractList($param);
            $list = [];
            foreach ($data as $item) {
                $content = [
                    ['label' => '合同编号', 'value' => $item['contract_number'], 'font_style' => 'font-weight: 800;'],
                    ['label' => '合同名称', 'value' => $item['contract_name'], 'font_style' => ''],
                    ['label' => '合同时间', 'value' => $item['contract_start_time_text'] . '至' . $item['contract_end_time_text'], 'font_style' => ''],
                ];
                $item['content'] = $content;
                $list[] = $item;
            }
            $result['list'] = $list;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $result['statusSelect'] = [
            ['label' => '待生效', 'value' => ContractConst::CONTRACT_NOT_EFFECTIVE],
            ['label' => '生效中', 'value' => ContractConst::CONTRACT_IN_EFFECTIVE],
            ['label' => '已失效', 'value' => ContractConst::CONTRACT_LOSE_EFFICACY],
            ['label' => '全部', 'value' => ContractConst::CONTRACT_ALl],
        ];
        return api_output(0, $result);
    }


    /**
     * 详情 合同
     */
    public function getContractInfo() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $id = $this->request->param('id',0,'intval');
        $where = [];
        try {
            $login_role = $this->login_role;
            $_uid = $this->_uid;
            if ($login_role == HouseWorkConst::MANAGE_APP_STREET_WORK) {
                return api_output_error(1008,'当前不支持街道社区人员查看');
            }
            if ($login_role == HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN) {
                $whereVillage = [];
                $whereVillage[] = ['property_id', '=', $_uid];
                $whereVillage[] = ['status', '=', 1];
                $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
                $where[] = ['village_id', 'in', $villageIds];
            }
            if ($login_role == HouseWorkConst::MANAGE_APP_PROPERTY_USER) {
                $where_admin = [];
                $where_admin[] = ['id', '=', $_uid];
                $propertyAdminInfo = (new PropertyAdminService())->getFind($where_admin,'property_id');
                $property_id = isset($propertyAdminInfo['property_id']) ? intval($propertyAdminInfo['property_id']) : 0;
                $whereVillage = [];
                $whereVillage[] = ['property_id', '=', $property_id];
                $whereVillage[] = ['status', '=', 1];
                $villageIds = (new HouseVillageService())->getVillageColumn($whereVillage, 'village_id');
                $where[] = ['village_id', 'in', $villageIds];
            }
            if ($login_role == HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN) {
                $village_id  = isset($this->login_info['village_id']) ? $this->login_info['village_id'] : 0;
                $where[] = ['village_id', '=', $village_id];
            }
            if ($login_role == HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
                $where[] = ['work_id', '=', $_uid];
                if (isset($login_info['village_id']) && $login_info['village_id']) {
                    $where[] = ['village_id', '=', $village_id];
                }
            }
            $where[] = ['id', '=', $id];
            $result['result'] = (new ContractHandleService())->getContractInfo($where, '', 2);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}