<?php
/**
 * 景区首页装修控制器
 */

namespace app\life_tools\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\common\model\service\AreaService;
use app\common\model\service\ConfigDataService;
use app\life_tools\model\service\HomeDecorateService;

class HomeDecorateController extends AuthBaseController
{

    /**
     * 获取列表
     * @return \json
     */
    public function getList()
    {
        $cat_key = $this->request->param('cat_key', 0, 'trim');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getList($cat_key, $this->systemUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 新增或编辑
     * @return \json
     */
    public function addOrEdit()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['cat_key'] = $this->request->param('cat_key', '', 'trim');
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
        $param['last_time'] = time();
        $service = new HomeDecorateService();
        try {
            $res = $service->addOrEdit($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑或查看是获取参数
     * @return \json
     */
    public function getEdit()
    {
        $id      = $this->request->param('id', '', 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getEdit($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加时的一些选项的获取
     * @return \json
     */
    public function getAdd()
    {
        $cat_id  = $this->request->param('cat_id', '', 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getAdd($cat_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除
     * @return \json
     */
    public function getDel()
    {
        $id      = $this->request->param('id', '', 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->getDel($id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取全部店铺分类
     * @return \json
     */
    public function getCateList()
    {
        $param['title']    = $this->request->param('title', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getCateList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取全部公告
     * @return \json
     */
    public function getInfoList()
    {
        $param['title']    = $this->request->param('title', '', 'trim');
        $param['type']     = $this->request->param('type', 'sports', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getInfoList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取全部课程
     * @return \json
     */
    public function getCourseList()
    {
        $param['title']    = $this->request->param('title', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getCourseList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取全部景区
     * @return \json
     */
    public function getScenicList()
    {
        $param['title']    = $this->request->param('title', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getScenicList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取全部门票推荐
     * @return \json
     */
    public function getToolsList()
    {
        $param['title']    = $this->request->param('title', '', 'trim');
        $param['type']     = $this->request->param('type', 'scenic', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            if(!empty($param['type'] && $param['type'] == 'appoint')){
                $arr = $service->getAppointList($param);
            }else{
                $arr = $service->getToolsList($param);
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取全部活动
     * @return \json
     */
    public function getCompetitionList()
    {
        $param['title']    = $this->request->param('title', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getCompetitionList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 添加关联分类
     */
    public function addRelatedCate()
    {
        $cat_id  = $this->request->param('cat_id', '', 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->addRelatedCate($cat_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加关联公告
     */
    public function addRelatedInfo()
    {
        $information_id = $this->request->param('information_id', '', 'intval');
        if (empty($information_id)) {
            return api_output_error(1003, '未选择公告');
        }
        $service = new HomeDecorateService();
        try {
            $res = $service->addRelatedInfo($information_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加关联课程
     */
    public function addRelatedCourse()
    {
        $tools_id = $this->request->param('tools_id', '', 'intval');
        if (empty($tools_id)) {
            return api_output_error(1003, '未选择课程');
        }
        $service = new HomeDecorateService();
        try {
            $res = $service->addRelatedCourse($tools_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加关联课程
     */
    public function addRelatedScenic()
    {
        $tools_id = $this->request->param('tools_id', '', 'intval');
        if (empty($tools_id)) {
            return api_output_error(1003, '未选择景区');
        }
        $service = new HomeDecorateService();
        try {
            $res = $service->addRelatedScenic($tools_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加关联门票
     */
    public function addRelatedTools()
    {
        $tools_id = $this->request->param('tools_id', '', 'intval');
        $type = $this->request->post('type', '', 'trim');
        if (empty($tools_id)) {
            return api_output_error(1003, '未选择');
        }
        $service = new HomeDecorateService();
        try {
            $res = $service->addRelatedTools($tools_id, $type);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加关联活动
     */
    public function addRelatedCompetition()
    {
        $competition_id = $this->request->param('competition_id', '', 'intval');
        if (empty($competition_id)) {
            return api_output_error(1003, '未选择活动');
        }
        $service = new HomeDecorateService();
        try {
            $res = $service->addRelatedCompetition($competition_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面渲染的信息（景区）
     */
    public function getUrlAndRecSwitch()
    {
        $service = new HomeDecorateService();
        try {
            $res = $service->getUrlAndRecSwitch();
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面渲染的信息（体育健身）
     */
    public function getUrlAndRecSwitchSport()
    {
        $service = new HomeDecorateService();
        try {
            $res = $service->getUrlAndRecSwitchSport();
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面渲染的信息（门票预约）
     */
    public function getUrlAndRecSwitchTicket()
    {
        $service = new HomeDecorateService();
        try {
            $res = $service->getUrlAndRecSwitchTicket();
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联商品排序
     */
    public function saveRelatedSort()
    {
        $cat_id  = $this->request->param('cat_id', '', 'intval');
        $sort    = $this->request->param('sort', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->saveRelatedSort($cat_id, $sort);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联公告排序
     */
    public function saveRelatedInfoSort()
    {
        $pigcms_id = $this->request->param('pigcms_id', '', 'intval');
        $sort      = $this->request->param('sort', 1, 'intval');
        $service   = new HomeDecorateService();
        try {
            $res = $service->saveRelatedInfoSort($pigcms_id, $sort);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联课程排序
     */
    public function saveRelatedCourseSort()
    {
        $tools_id = $this->request->param('tools_id', '', 'intval');
        $sort     = $this->request->param('sort', 1, 'intval');
        $service  = new HomeDecorateService();
        try {
            $res = $service->saveRelatedCourseSort($tools_id, $sort);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联景区排序
     */
    public function saveRelatedScenicSort()
    {
        $tools_id = $this->request->param('tools_id', '', 'intval');
        $sort     = $this->request->param('sort', 1, 'intval');
        $service  = new HomeDecorateService();
        try {
            $res = $service->saveRelatedScenicSort($tools_id, $sort);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联门票排序
     */
    public function saveRelatedToolsSort()
    {
        $id = $this->request->param('id', '', 'intval');
        $sort     = $this->request->param('sort', 1, 'intval');
        $service  = new HomeDecorateService();
        try {
            $res = $service->saveRelatedToolsSort($id, $sort);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联活动排序
     */
    public function saveRelatedCompetitionSort()
    {
        $competition_id = $this->request->param('competition_id', '', 'intval');
        $sort    = $this->request->param('sort', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->saveRelatedCompetitionSort($competition_id, $sort);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联商品排序
     */
    public function delOne()
    {
        $cat_id  = $this->request->param('cat_id', '', 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->delOne($cat_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除公告
     */
    public function delInfo()
    {
        $pigcms_id = $this->request->param('pigcms_id', '', 'intval');
        $service   = new HomeDecorateService();
        try {
            $res = $service->delInfo($pigcms_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除课程
     */
    public function delCourse()
    {
        $tools_id = $this->request->param('tools_id', '', 'intval');
        $service  = new HomeDecorateService();
        try {
            $res = $service->delCourse($tools_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除景区
     */
    public function delScenic()
    {
        $tools_id = $this->request->param('tools_id', '', 'intval');
        $service  = new HomeDecorateService();
        try {
            $res = $service->delScenic($tools_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除门票
     */
    public function delTools()
    {
        $id = $this->request->param('id', '', 'intval');
        $service  = new HomeDecorateService();
        try {
            $res = $service->delTools($id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除活动
     */
    public function delCompetition()
    {
        $competition_id = $this->request->param('competition_id', '', 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->delCompetition($competition_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面关联商品列表信息
     */
    public function getRelatedList()
    {
        $page    = $this->request->param('page', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->getRelatedList($page, 100000);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面关联滚动公告列表
     */
    public function getRelatedInfoList()
    {
        $page    = $this->request->param('page', 1, 'intval');
        $type    = $this->request->param('type', 'sports', 'trim');
        $service = new HomeDecorateService();
        try {
            $res = $service->getRelatedInfoList($page, 100000, $type);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面关联课程列表
     */
    public function getRelatedCourseList()
    {
        $page    = $this->request->param('page', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->getRelatedCourseList($page, 100000);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面关联景区列表
     */
    public function getRelatedScenicList()
    {
        $page    = $this->request->param('page', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->getRelatedScenicList($page, 100000);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 获取装修页面关联门票列表
     */
    public function getRelatedToolsList()
    {
        $page    = $this->request->param('page', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->getRelatedToolsList($page, 100000);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面关联滚动公告列表
     */
    public function getRelatedCompetitionList()
    {
        $page    = $this->request->param('page', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->getRelatedCompetitionList($page, 100000);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 设置门票预约推荐主标题
     */
    public function setToolsName()
    {
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['show'] = $this->request->param('show', 1, 'intval');
        $service  = new HomeDecorateService();
        try {
            $res = $service->setToolsName($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function getAllArea()
    {
        $type = $this->request->param('type', 0, 'trim');
        $areaService = new AreaService();
        try {
            $arr = $areaService->getAllArea($type);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    
    /**
     * 获得体育首页热门推荐信息
     */
    public function getSportsIndexHotRecommond()
    {
        $return = (new ConfigDataService())->getSportsIndexHotRecommond('sports_index');
        return api_output(0, $return, 'success');
        
    }    
    
    /**
    * 获取体育热门推荐已选择的商品
    * @return \json
    */
   public function getSportsHotRecommendSelectedList()
   {
       $param['type']    = $this->request->param('type', '', 'trim');
       $param['page']     = $this->request->param('page', 1, 'intval');
       $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
       $service = new HomeDecorateService();
       try {
           $arr = $service->getSportsHotRecommendSelectedList($param);
           return api_output(0, $arr, 'success');
       } catch (\Exception $e) {
           return api_output_error(1003, $e->getMessage());
       }
   }
    
    /**
     * 获取体育热门推荐商品
     * @return \json
     */
    public function getSportsHotRecommendList()
    {
        $param['title']    = $this->request->param('title', '', 'trim');
        $param['type']    = $this->request->param('type', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getSportsHotRecommendList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 添加体育热门推荐商品
     * @return \json
     */
    public function addSportsHotRecommendList()
    {
        $param['recommend_id']    = $this->request->param('recommend_id', '', 'trim');
        $param['type']    = $this->request->param('type', '', 'trim');
        $service = new HomeDecorateService();
        try {
            $arr = $service->addSportsHotRecommendList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 删除体育热门推荐商品
     * @return \json
     */
    public function delSportsHotRecommendList()
    {
        $param['id']    = $this->request->param('id', '', 'trim');
        $service = new HomeDecorateService();
        try {
            $arr = $service->delSportsHotRecommendList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联活动排序
     */
    public function saveSportsHotRecommendSort()
    {
        $competition_id = $this->request->param('id', '', 'intval');
        $sort    = $this->request->param('sort', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->saveSportsHotRecommendSort($competition_id, $sort);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 获得景区首页热门推荐信息
     */
    public function getScenicIndexHotRecommond()
    {
        $return = (new ConfigDataService())->getScenicIndexHotRecommond('scenic_index');
        return api_output(0, $return, 'success');

    }

    /**
     * 获取体育热门推荐已选择的商品
     * @return \json
     */
    public function getScenicHotRecommendSelectedList()
    {
        $param['type']    = $this->request->param('type', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getScenicHotRecommendSelectedList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取体育热门推荐商品
     * @return \json
     */
    public function getScenicHotRecommendList()
    {
        $param['title']    = $this->request->param('title', '', 'trim');
        $param['type']    = $this->request->param('type', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $service = new HomeDecorateService();
        try {
            $arr = $service->getScenicHotRecommendList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加体育热门推荐商品
     * @return \json
     */
    public function addScenicHotRecommendList()
    {
        $param['recommend_id']    = $this->request->param('recommend_id', '', 'trim');
        $param['type']    = $this->request->param('type', '', 'trim');
        $service = new HomeDecorateService();
        try {
            $arr = $service->addScenicHotRecommendList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除体育热门推荐商品
     * @return \json
     */
    public function delScenicHotRecommendList()
    {
        $param['id']    = $this->request->param('id', '', 'trim');
        $service = new HomeDecorateService();
        try {
            $arr = $service->delScenicHotRecommendList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改关联活动排序
     */
    public function saveScenicHotRecommendSort()
    {
        $competition_id = $this->request->param('id', '', 'intval');
        $sort    = $this->request->param('sort', 1, 'intval');
        $service = new HomeDecorateService();
        try {
            $res = $service->saveScenicHotRecommendSort($competition_id, $sort);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}