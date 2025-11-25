<?php

namespace app\new_marketing\controller\platform;

use app\BaseController;
use app\common\model\service\AreaService;
use app\new_marketing\model\service\RegionalAgencyService;

class RegionalAgencyController extends AuthBaseController
{
    /**
     * @return \json
     * 区域代理列表
     */
    public function regionalAgencyList()
    {
        $regionalAgencyService = new RegionalAgencyService();
        try {
            $param['province_id'] = $this->request->param('province_id', 0, 'intval');
            $param['city_id'] = $this->request->param('city_id', 0, 'intval');
            $param['area_id'] = $this->request->param('area_id', 0, 'intval');
            $param['uid'] = $this->request->param('uid', 0, 'intval');
            $param['start_time'] = $this->request->param('start_time', '', 'trim');
            $param['end_time'] = $this->request->param('end_time', '', 'trim');
            $param['put_text'] = $this->request->param('put_text', '', 'trim');
            $arr = $regionalAgencyService->regionalAgencyList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 查找区域
     */
    public function findArea()
    {
        $area = (new AreaService())->getAllArea(2, "*,area_id as value,area_name as label");
        return api_output(0, $area, 'success');
    }

    /**
     * @return \json
     * 新增查看用户是否合法
     */
    public function findRight()
    {
        $regionalAgencyService = new RegionalAgencyService();
        $param['phone'] = $this->request->param('phone', 0, 'trim');
        $where = [['phone', '=', $param['phone']]];
        $msg = $regionalAgencyService->findRight($where);
        return api_output(0, $msg, 'success');
    }

    /**
     * @return \json
     * @throws \think\Exception
     * 编辑查看用户是否合法
     */
    public function findRightEdit()
    {
        $regionalAgencyService = new RegionalAgencyService();
        $param['phone'] = $this->request->param('phone', 0, 'intval');
        $param['id'] = $this->request->param('id', 0, 'intval');
        $where = [['phone', '=', $param['phone']]];
        $msg = $regionalAgencyService->findRightEdit($where, $param);
        return api_output(0, $msg, 'success');
    }

    /**
     * @return \json
     * 新增区域代理
     */
    public function addRegionalAgency()
    {
        $regionalAgencyService = new RegionalAgencyService();
        try {

            $param['province_id'] = $this->request->param('province_id', 0, 'intval');
            $param['city_id'] = $this->request->param('city_id', 0, 'intval');
            $param['area_id'] = $this->request->param('area_id', 0, 'intval');
            $param['store_percent'] = $this->request->param('store_percent', 0, 'intval');
            $param['village_percent'] = $this->request->param('village_percent', 0, 'intval');
            $param['add_time'] = time();
            $param['note'] = $this->request->param('note', '', 'trim');

            $param['uid'] = $this->request->param('uid', 0, 'trim');
            $param['phone'] = $this->request->param('phone', 0, 'trim');
            $param['name'] = $this->request->param('name', '', 'trim');
            $arr = $regionalAgencyService->addRegionalAgency($param);
            if ($arr['status']) {
                return api_output(0, [], 'success');
            } else {
                return api_output_error(1003, $arr['msg']);
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *
     *编辑区代理
     */
    public function editRegionalAgency()
    {
        $regionalAgencyService = new RegionalAgencyService();
        try {
            $param['id'] = $this->request->param('id', 0, 'intval');
            if (!$param['id']) {
                return api_output_error(1003, "缺少需要修改的信息id");
            } else {
                $where = [['s.id', '=', $param['id']]];
                $field = "s.*,r.*,u.phone";
                $arr = $regionalAgencyService->editRegionalAgency($where, $field);
                return api_output(0, $arr, 'success');
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 修改区域代理
     */
    public function saveRegionalAgency()
    {
        $regionalAgencyService = new RegionalAgencyService();
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['province_id'] = $this->request->param('province_id', 0, 'intval');
        $param['city_id'] = $this->request->param('city_id', 0, 'intval');
        $param['area_id'] = $this->request->param('area_id', 0, 'intval');
        $param['store_percent'] = $this->request->param('store_percent', 0, 'intval');
        $param['village_percent'] = $this->request->param('village_percent', 0, 'intval');
        $param['note'] = $this->request->param('note', '', 'trim');
        $param['phone'] = $this->request->param('phone', '', 'trim');

        $param['name'] = $this->request->param('name', '', 'trim');
        $param['uid'] = $this->request->param('uid', 0, 'intval');
        if (empty($param['id'])) {
            return api_output_error(1003, "缺少需要修改的信息id");
        } else {
            $arr = $regionalAgencyService->saveRegionalAgency($param);
            if ($arr['status']) {
                return api_output(0, [], 'success');
            } else {
                return api_output_error(1003, $arr['msg']);
            }
        }
    }

    /**
     * @return \json
     * 删除区域代理
     */
    public function delRegionalAgency()
    {
        $regionalAgencyService = new RegionalAgencyService();
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['sel_id'] = $this->request->param('sel_id', 0, 'intval');
        if (empty($param['id'])) {
            return api_output_error(1003, "缺少需要删除的信息id");
        } else {
            $arr = $regionalAgencyService->delRegionalAgency($param);
            if ($arr) {
                return api_output(0, $arr, 'success');
            } else {
                return api_output_error(1003, "删除失败");
            }
        }
    }

    public function reduceWin()
    {
        $regionalAgencyService = new RegionalAgencyService();
        try {
            $id = $this->request->param('id', 0, 'intval');
            $arr = $regionalAgencyService->reduceWin($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 降级
     */
    public function addReduce()
    {
        $regionalAgencyService = new RegionalAgencyService();
        try {
            $param['sel_id'] = $this->request->param('sel_id', 0, 'intval');
            $param['id'] = $this->request->param('id', 0, 'intval');
            $param['identity'] = $this->request->param('identity', 0, 'intval');
            $param['team_id'] = $this->request->param('team_id', 0, 'intval');
            $param['percent'] = $this->request->param('percent', 0, 'intval');
            if ($param['identity'] == 1 && empty($param['team_id'])) {
                return api_output_error(1003, "业务员必须选择团队");
            }
            $arr = $regionalAgencyService->addReduce($param);
            if ($arr) {
                return api_output(0, $arr, 'success');
            } else {
                return api_output_error(1003, "降级失败");
            }
        } catch (\Exception $e) {

        }
    }

    public function getTeamList()
    {
        $regionalAgencyService = new RegionalAgencyService();
        try {
            $param['id'] = $this->request->param('id', 0, 'intval');

            if (!$param['id']) {
                return api_output_error(1003, "缺少区域代理id");
            }
            $arr = $regionalAgencyService->getTeamList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            dd($e);
            //return api_output_error(1003, $e->getMessage());
        }
    }

    public function updatePercent()
    {
        $regionalAgencyService = new RegionalAgencyService();
        try {
            $param['data'] = $this->request->param('data', 0, 'trim');
            $param['length'] = $this->request->param('length', 0, 'intval');
            $param['discount_ratio'] = $this->request->param('discount_ratio', 0, 'intval');
            $param['discount_ratio1'] = $this->request->param('discount_ratio1', 0, 'intval');
            $arr = $regionalAgencyService->updatePercent($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}