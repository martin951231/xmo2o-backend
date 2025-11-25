<?php
/**
 * liuruofei
 * 2021/08/24
 * 套餐管理
 */
namespace app\new_marketing\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\new_marketing\model\service\MarketingPackageService;
use app\new_marketing\model\service\MarketingPackageRegionService;
use app\new_marketing\model\service\MerchantCategoryService;
use think\App;

class MarketingPackageController extends AuthBaseController
{

    /**
     * 套餐列表
     */
    public function getSearchList(){
        $param = [
            ['is_del', '=', 0]
        ];
        $name = $this->request->param('name', '', 'trim');
        $start_time = $this->request->param('start_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        if ($name) {
            $param[] = ['name', 'like', '%' . $name . '%'];
        }
        if($start_time){
            $param[] = ['create_time', '>=', strtotime($start_time)];
        }
        if ($end_time) {
            $param[] = ['create_time', '<', strtotime($end_time) + 86400];
        }
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        try {
            $list = (new MarketingPackageService)->getSearchList($param, $limit);
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取店铺类型列表
     */
    public function getStoreTypeList() {
        $where = [
            ['cat_status', '=', 1]
        ];
        try {
            $list = (new MerchantCategoryService)->getStoreTypeList($where);
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 创建套餐
     */
    public function add(){
        $param = $this->request->param();
        unset($param['system_type']);
        $param['create_time'] = time();
        $param['update_time'] = time();
        $param['name'] = trim($param['name']);
        if (!$param['name']) {
            return api_output_error(1003, '套餐名称不能为空');
        }
        if (!$param['store_detail'] || !is_array($param['store_detail'])) {
            return api_output_error(1003, '请选择店铺类型');
        }
        $param['all_num'] = 0;
        foreach ($param['store_detail'] as $k => $v) {
            $param['all_num'] += $v['num'];
        }
        $param['store_detail'] = json_encode($param['store_detail'], true);
        try {
            (new MarketingPackageService)->saveData(0, $param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取编辑套餐数据
     * get
     */
    public function edit() {
        $param = $this->request->param();
        try {
            $data = (new MarketingPackageService)->getData(['id' => $param['id']]);
            $data['create_time'] = date('Y-m-d H:i:s',$data['create_time']);
            $data['store_detail'] = $data['store_detail'] ? json_decode($data['store_detail'], true) : [];
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑套餐保存
     * post
     */
    public function editPost() {
        $param = $this->request->param();
        if (!$param['id']) {
            return api_output_error(1003, '参数错误');
        }
        unset($param['system_type']);
        $param['update_time'] = time();
        if (!empty($param['store_detail'])) {
            $param['all_num'] = 0;
            foreach ($param['store_detail'] as $k => $v) {
                $param['all_num'] += $v['num'];
            }
            $param['store_detail'] = json_encode($param['store_detail'], true);
        }
        try {
            (new MarketingPackageService)->saveData($param['id'], $param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 更新套餐状态
     */
    public function setStatus() {
        $id = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        if (!$id) {
            return api_output_error(1003, '参数错误');
        }
        try {
            (new MarketingPackageService)->setStatus($id,$status);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除套餐
     */
    public function del() {
        $id = $this->request->param('id', 0, 'intval');
        if (!$id) {
            return api_output_error(1003, '参数错误');
        }
        try {
            (new MarketingPackageService)->del($id);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 区域套餐列表
     */
    public function getAreaSearchList(){
        $param = [
            ['is_del', '=', 0]
        ];
        $area_id = $this->request->param('area_id', 0, 'intval');
//        $name = $this->request->param('name', '', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        if (!$area_id) {
            return api_output_error(1003, '区域ID不存在');
        }
//        if ($name) {
//            $param[] = ['name', 'like', '%' . $name . '%'];
//        }
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        try {
            $list = (new MarketingPackageService)->getAreaSearchList($param, $limit, $area_id);
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取编辑区域套餐数据
     * get
     */
    public function editArea() {
        $package_id = $this->request->param('package_id', 0, 'intval');
        $area_id = $this->request->param('area_id', 0, 'intval');
        if (!$package_id || !$area_id) {
            return api_output_error(1003, '参数错误');
        }
        try {
            $data = (new MarketingPackageRegionService)->getData([
                ['region_id', '=', $area_id],
                ['package_id', '=', $package_id]
            ]);
            if ($data) {
                $data['manual_price'] = $data['manual_price'] ? json_decode($data['manual_price'], true) : [];
            }
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑区域套餐保存
     * post
     */
    public function editAreaPost() {
        $param = $this->request->param();
        if (!$param['package_id'] || !$param['area_id']) {
            return api_output_error(1003, '参数错误');
        }
        $param['region_id'] = $param['area_id'];
        unset($param['system_type'],$param['area_id']);
        $param['update_time'] = time();
        if (!empty($param['manual_price'])) {
            $param['manual_price'] = json_encode($param['manual_price'], true);
        }
        try {
            (new MarketingPackageRegionService)->saveData($param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 更新区域套餐状态
     */
    public function setAreaStatus() {
        $package_id = $this->request->param('package_id', 0, 'intval');
        $area_id = $this->request->param('area_id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        if (!$package_id || !$area_id) {
            return api_output_error(1003, '参数错误');
        }
        try {
            (new MarketingPackageRegionService)->setStatus([['region_id', '=', $area_id], ['package_id', '=', $package_id]], $status);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}