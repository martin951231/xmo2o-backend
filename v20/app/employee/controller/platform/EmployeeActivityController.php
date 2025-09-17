<?php
/**
 * 员工专区控制器
 */

namespace app\employee\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\common\model\service\AreaService;
use app\employee\model\db\EmployeeActivity;
use app\employee\model\db\EmployeeActivityAdver;
use app\employee\model\db\EmployeeActivityGoods;
use app\employee\model\db\EmployeeCardLable;
use app\employee\model\service\EmployeeActivityBindLableService;
use app\employee\model\service\EmployeeActivityService;
use app\employee\model\service\EmployeeCardUserService;

class EmployeeActivityController extends AuthBaseController
{

    /**
     * 获取列表
     * @return \json
     */
    public function getActivityList()
    {
        $param['keyword']  = $this->request->param('keyword', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        try {
            $arr = (new EmployeeActivityService())->getActivityList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取活动商品列表
     * @return \json
     */
    public function getActivityGoods()
    {
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['activity_id'] = $this->request->param('activity_id', 0, 'intval');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        try {
            $arr = (new EmployeeActivityService())->getActivityGoods($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取活动信息
     * @return \json
     */
    public function getActivityEdit()
    {
        $param['pigcms_id'] = $this->request->param('pigcms_id', 0, 'intval');
        if (empty($param['pigcms_id'])) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new EmployeeActivity())->getOne(['pigcms_id' => $param['pigcms_id']])->toArray();
            $arr['start_time'] = $arr['start_time'] ? date('Y-m-d', $arr['start_time']) : '';
            $arr['end_time'] = $arr['end_time'] ? date('Y-m-d', $arr['end_time']) : '';
            $arr['cover_image'] = $arr['cover_image'] ? replace_file_domain($arr['cover_image'] ) : '';

            // 员工标签
            $lableArr = (new EmployeeActivityBindLableService())->getSome(['activity_id'=>$arr['pigcms_id']], true, ['id'=>'asc']);
            $lableFormat = [];
            foreach($lableArr as $value){
                if(isset($lableFormat[$value['mer_id']])){
                    $lableFormat[$value['mer_id']]['lables'][] = $value['lable_id'];
                }else{
                    $lableFormat[$value['mer_id']]['mer_id'] = $value['mer_id'];
                    $lableFormat[$value['mer_id']]['lables'][] = $value['lable_id'];
                }
            }
            $arr['lable_arr'] = array_values($lableFormat);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加或编辑活动
     * @return \json
     */
    public function employActivityAddOrEdit()
    {
        $param['pigcms_id'] = $this->request->param('pigcms_id', 0, 'intval');
        $param['status'] = $this->request->param('status', 0, 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['cover_image'] = $this->request->param('cover_image', '', 'trim');
        $param['company'] = $this->request->param('company', '', 'trim');
        $param['lable_arr'] = $this->request->param('lable_arr', '', 'trim');
 	$param['open_free_jump'] = $this->request->param('open_free_jump', 0, 'intval');
        $param['free_jump_url'] = $this->request->param('free_jump_url', '', 'trim');
        $param['is_temp'] = $this->request->param('is_temp', '', 'trim');
        if (empty($param['name'])) {
            return api_output_error(1003, '活动名称必填');
        }
        try {
            $arr = (new EmployeeActivityService())->employActivityAddOrEdit($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除
     * @return \json
     */
    public function delActivity()
    {
        $param['pigcms_id'] = $this->request->param('pigcms_id', 0, 'intval');
        $param['is_temp'] = $this->request->param('is_temp', 0, 'intval');
        if (empty($param['pigcms_id'])) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new EmployeeActivity())->updateThis(['pigcms_id' => $param['pigcms_id'],'is_temp'=>$param['is_temp']], ['is_del' => 1]);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取活动轮播图列表
     * @return \json
     */
    public function getActivityAdverList()
    {
        $activity_id = $this->request->param('activity_id', 0, 'trim');
        if (empty($activity_id)) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new EmployeeActivityService())->getActivityAdverList($activity_id, $this->systemUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除活动轮播图
     * @return \json
     */
    public function activityAdverDel()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new EmployeeActivityAdver())->where(['id' => $id])->delete();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function getAllArea()
    {
        $type = $this->request->param('type', 0, 'trim');
        try {
            $arr = (new AreaService())->getAllArea($type);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    public function getActivityAdver()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            $arr = (new EmployeeActivityService())->getActivityAdver($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 新增或编辑轮播图
     * @return \json
     */
    public function addOrEditActivityAdver()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['activity_id'] = $this->request->param('activity_id', 0, 'trim');
        $param['sort'] = $this->request->param('sort', '', 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['subname'] = $this->request->param('subname', '', 'trim');
        $param['pic'] = $this->request->param('pic', '', 'trim');
        $param['currency'] = $this->request->param('currency', '1', 'trim');
        $param['areaList'] = $this->request->param('areaList');
        $param['bg_color'] = $this->request->param('bg_color', '', 'trim');
        $param['url'] = $this->request->param('url', '', 'trim');
        $param['wxapp_open_type'] = $this->request->param('wxapp_open_type', '', 'trim');
        $param['wxapp_id'] = $this->request->param('wxapp_id', '', 'trim');
        $param['wxapp_page'] = $this->request->param('wxapp_page', '', 'trim');
        $param['app_open_type'] = $this->request->param('app_open_type', '', 'intval');
        $param['ios_app_name'] = $this->request->param('ios_app_name', '', 'trim');
        $param['ios_app_url'] = $this->request->param('ios_app_url', '', 'trim');
        $param['android_app_name'] = $this->request->param('android_app_name', '', 'trim');
        $param['android_app_url'] = $this->request->param('android_app_url', '', 'trim');
        $param['app_wxapp_id'] = $this->request->param('app_wxapp_id', '', 'trim');
        $param['app_wxapp_page'] = $this->request->param('app_wxapp_page', '', 'trim');
        $param['app_wxapp_username'] = $this->request->param('app_wxapp_username', '', 'trim');
        $param['status'] = $this->request->param('status', 1, 'intval');
        $param['complete'] = $this->request->param('complete', '', 'intval');
        $param['lable_arr'] = $this->request->param('lable_arr', '', 'trim');
        $param['last_time'] = time();
        try {
            $res = (new EmployeeActivityService())->addOrEditActivityAdver($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取商品列表
     * @return \json
     */
    public function getShopGoodsList()
    {
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['activity_id'] = $this->request->param('activity_id', 0, 'intval');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        if (empty($param['activity_id'])) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new EmployeeActivityService())->getShopGoodsList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加活动商品列表
     * @return \json
     */
    public function addActivityShopGoods()
    {
        $param['goods_ids']     = $this->request->param('goods_ids', '', 'trim');
        $param['activity_id'] = $this->request->param('activity_id', 0, 'intval');
        if (empty($param['activity_id']) || empty($param['goods_ids'])) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new EmployeeActivityService())->addActivityShopGoods($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除
     * @return \json
     */
    public function setActivityGoodsSort()
    {
        $param['pigcms_id'] = $this->request->param('pigcms_id', 0, 'intval');
        $param['sort']      = $this->request->param('sort', 0, 'intval');
        if (empty($param['pigcms_id'])) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new EmployeeActivityGoods())->updateThis(['pigcms_id' => $param['pigcms_id']], ['sort' => $param['sort']]);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除活动商品
     * @return \json
     */
    public function delActivityGoods()
    {
        $param['pigcms_id'] = $this->request->param('pigcms_id', '', 'trim');
        if (empty($param['pigcms_id'])) {
            return api_output_error(1003, '参数有误');
        }
        try {
            $arr = (new EmployeeActivityGoods())->updateThis([['pigcms_id', 'in', $param['pigcms_id']]], ['is_del' => 1]);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    

    /**
     * 获取列表
     * @return \json
     */
    public function getlableAll()
    {
        $labelArr = (new EmployeeCardUserService())->getlableAll([], 0, true);
        return api_output(0, $labelArr, 'success');
    }

    /**
     * 获取自提点配置信息
     */
    public function getPickTimeSetting()
    {
        try {
            $data = (new EmployeeActivityService())->getPickTimeSetting();
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        
        return api_output(0, $data, 'success');
    }

    /**
     * 编辑自提点配置
     */
    public function pickTimeSetting()
    {
        $params = [];
        $params['open_pick_time'] = $this->request->post('open_pick_time');
        $params['pick_time'] = $this->request->post('pick_time');
        try {
            $data = (new EmployeeActivityService())->pickTimeSetting($params);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

}