<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      系统后台小区广告审核配置控制器
 */

namespace app\community\controller\platform\advert;

use app\common\model\service\admin_user\AdminUserService;
use app\community\controller\platform\AuthBaseController as BaseController;
use app\community\model\service\advert\AdvertCheckConfigService;

class CheckConfigController extends BaseController
{
    /**
     * 获取广告审核配置信息
     */
    public function getAdvertCheckSetInfo()
    {
        if (!$this->_uid) {
            return api_output_error(1002);
        }
        $xtype = $this->request->post('xtype', 'house_advert_check', 'trim');
        !$xtype && $xtype = 'house_advert_check';
        $xtype = trim($xtype);
        $xtype = htmlspecialchars($xtype, ENT_QUOTES);
        $param = array('village_id' => 0, 'property_id' => 0);
        $param['xtype'] = $xtype;
        try {
            $result['info'] = (new AdvertCheckConfigService())->getAdvertCheckConfigSetInfo($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 获取管理员列表
     */
    public function getAdminList() {
        try {
            $where = [];
            $where[] = ['status', '=', 1];
            $list = (new AdminUserService())->getList($where);
            $adminUser = [];
            foreach ($list as $user) {
                $label = isset($user['realname']) ? trim($user['realname']) : '';
                !$label && $label = "账号:{$user['account']}";
                $adminUser[] = [
                    'id' => $user['id'],
                    'name' => isset($user['realname']) ? trim($user['realname']) : '',
                    'account' => trim($user['account']),
                    'label' => $label,
                    'value' => $user['id'],
                ];
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['list' => $adminUser], "成功");
    }

    /**
     * 设置广告审核配置.
     */
    public function advertCheckSet() {
        if (!$this->_uid) {
            return api_output_error(1002);
        }
        $check_open = $this->request->post('check_open', 0, 'intval');
        $check_level = $this->request->post('check_level', []);
        $xtype = $this->request->post('xtype', 'house_advert_check', 'trim');
        !$xtype && $xtype = 'house_advert_check';
        $xtype = trim($xtype);
        $xtype = htmlspecialchars($xtype, ENT_QUOTES);
        $param = array('village_id' => 0, 'property_id' => 0);
        $param['xtype'] = $xtype;
        if ($xtype == 'house_advert_check') {
            $param['tname'] = '小区广告审核设置';
        }
        $setData = array();
        if ($check_open == 1 && empty($check_level)) {
            return api_output(1001, [], '请至少选择设置一项审批人！');
        }
        $param = array('village_id' => 0, 'property_id' => 0);
        $param['xtype'] = $xtype;
        $checkInfo = (new AdvertCheckConfigService())->getAdvertCheckConfigSetInfo($param);
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
        $param['is_open'] = $check_open;
        $param['set_datas'] = json_encode($setData, JSON_UNESCAPED_UNICODE);
        try {
            $result['result'] = (new AdvertCheckConfigService())->advertCheckSet($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}