<?php
/**
 * MallHomeDecorateController.php
 * 新版商城首页装修控制器
 * Create on 2020/10/20 15:05
 * Created by zhumengqun
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\MallHomeDecorateService;

class MallHomeDecorateController extends AuthBaseController
{
    //轮播图、导航列表、单图广告
    /**
     * 获取列表
     * @return \json
     */
    public function getList()
    {
        $cat_key = $this->request->param('cat_key', 0, 'trim');
        $service = new MallHomeDecorateService();
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
        $param['currency'] = $this->request->param('currency', '', 'trim');
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
        $param['wxapp_ad_unit_id'] = $this->request->param('wxapp_ad_unit_id', '', 'trim');
        $param['open_wxapp_ad'] = $this->request->param('open_wxapp_ad', '', 'intval');
        $param['last_time'] = time();
        $service = new MallHomeDecorateService();
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
        $id = $this->request->param('id', '', 'intval');
        $service = new MallHomeDecorateService();
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
        $cat_id = $this->request->param('cat_id', '', 'intval');
        $service = new MallHomeDecorateService();
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
        $id = $this->request->param('id', '', 'intval');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->getDel($id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //六宫格

    /**
     * 获取六宫格列表
     * @return \json
     */
    public function getSixList()
    {
        $service = new MallHomeDecorateService();
        try {
            $arr = $service->getSixList(0, 2);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取某个活动的商品
     * @return \json
     */
    public function getActGoods()
    {

        $param['record_id'] = $this->request->param('record_id', '', 'intval');
        $param['source'] = $this->request->param('source', 'platform', 'trim');
        $param['cat_id'] = $this->request->param('cat_id', '', 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['page'] = $this->request->param('page', '', 'intval');
        $param['pageSize'] = $this->request->param('pageSize', '', 'intval');
        $service = new MallHomeDecorateService();
        try {
            $arr = $service->getActGoods($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加或编辑六宫格
     * @return \json
     */
    public function addOrEditSixAdver()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['subname'] = $this->request->param('subname', '', 'trim');
        $param['type'] = $this->request->param('type', '', 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['sort'] = $this->request->param('sort', '', 'intval');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->addOrEditSixAdver($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加关联商品
     */
    public function addRelatedGoods()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['goods_ids'] = $this->request->param('goods_ids');
        $param['source'] = $this->request->param('source', 'platform', 'trim');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->addRelatedGoods($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑或查看是获取参数
     * @return \json
     */
    public function getSixEdit()
    {
        $id = $this->request->param('id', '', 'intval');
        $service = new MallHomeDecorateService();
        try {
            $arr = $service->getSixEdit($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除六宫格
     * @return \json
     */
    public function delSixAdver()
    {
        $id = $this->request->param('id', '', 'intval');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->delSixAdver($id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //猜你喜欢装修（推荐）

    /**
     * @return \json
     * 获取推荐模块和关联商品列表
     */
    public function getRecList()
    {
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $service = new MallHomeDecorateService();
        try {
            $arr = $service->getRecList([], $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 推荐模块编辑或添加
     */
    public function addOrEditRec()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['subname'] = $this->request->param('subname', '', 'trim');
        $param['sort'] = $this->request->param('sort', '', 'intval');
        $param['status'] = $this->request->param('status', 1, 'intval');
        $param['style'] = $this->request->param('style', 2, 'intval');
        $param['is_display'] = $this->request->param('is_display', 1, 'intval');
        $param['create_time'] = time();
        $service = new MallHomeDecorateService();
        try {
            $res = $service->addOrEditRec($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 推荐的编辑信息
     */
    public function getRecEdit()
    {
        $id = $this->request->param('id', '', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $service = new MallHomeDecorateService();
        try {
            $arr = $service->getRecEdit($id, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除
     * @return \json
     */
    public function delRecAdver()
    {
        $id = $this->request->param('id', '', 'intval');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->delRecAdver($id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 推荐模块是否在首页展示
     */
    public function recDisplay()
    {
        $isDisplay = $this->request->param('is_display', 1, 'intval');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->recDisplay($isDisplay);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取装修页面渲染的信息
     */
    public function getUrlAndRecSwitch()
    {
        $service = new MallHomeDecorateService();
        try {
            $res = $service->getUrlAndRecSwitch();
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
        $type = $this->request->param('type', '', 'intval');
        $id = $this->request->param('id', '', 'intval');
        $sort = $this->request->param('sort', 1, 'intval');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->saveRelatedSort($type, $id, $sort);
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
        $id = $this->request->param('id', '', 'intval');
        $type = $this->request->param('type', '', 'intval');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->delOne($id, $type);
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
        $type = $this->request->param('type', '', 'intval');
        $dec_id = $this->request->param('dec_id', '', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $service = new MallHomeDecorateService();
        try {
            $res = $service->getRelatedList($type, $dec_id, $page, $pageSize=100000);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}