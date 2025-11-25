<?php
/**
 * DecoratePageController.php
 * 自定义装修页控制器
 * Create on 2021/2/19 13:27
 * Created by zhumengqun
 */

namespace app\common\controller\common;

use app\BaseController;
use app\common\model\service\AdverCategoryService;
use app\common\model\service\AreaService;
use app\common\model\service\decorate\DecorateService;
use app\mall\controller\api\ApiBaseController;
use think\helper\Arr;

class DecoratePageController extends ApiBaseController
{
    /**
     * 个人中心装修
     */
    public function addOrEditpersonalDec()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $param['type'] = $this->request->param('type', 1, 'intval');
        $param['head_style'] = $this->request->param('head_style', 1, 'intval');
        $param['head_style_value'] = $this->request->param('head_style_value', '', 'trim');
        $param['font_color'] = $this->request->param('font_color', 1, 'intval');
        $param['vip_display'] = $this->request->param('vip_display', 1, 'intval');
        $param['vip_stored_value_display'] = $this->request->param('vip_stored_value_display', 1, 'intval');
        $param['vip_store_value_subname'] = $this->request->param('vip_store_value_subname', '', 'trim');
        $param['adver'] = $this->request->param('adver');
        $param['activity'] = $this->request->param('activity');
        $param['business'] = $this->request->param('business');
        $decService = new DecorateService();
        try {
            $result = $decService->addOrEditpersonalDec($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取个人中心装修信息
     */
    public function getpersonalDec()
    {
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $type= $this->request->param('type', 1, 'intval');
        $uid =$this->request->log_uid??'';
        if($param['source'] =="platform"){//平台
            $coupon_type=1;
        }elseif ($param['source'] =="merchant" || $param['source'] =="store"){
            $coupon_type=0;
        }
        $decService = new DecorateService();
        try {
            $result = $decService->getpersonalDec($param,$uid,$coupon_type,$type);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 底部导航编辑或添加
     */
    public function addOrEditNavBottom()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $param['bg_color'] = $this->request->param('bg_color', '', 'trim');
        $param['select_color'] = $this->request->param('select_color', '', 'trim');
        $param['nav_font_color'] = $this->request->param('nav_font_color', '', 'trim');
        $param['content'] = $this->request->param('content');
        $param['is_open'] = $this->request->param('is_open', 1, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->addOrEditNavBottom($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取底部导航装修信息
     */
    public function getNavBottomDec()
    {
        $param['source'] = $this->request->param('source', 'platform', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getNavBottomDec($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 悬浮窗编辑或添加
     */
    public function addOrEditSuspendedWindow()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $param['apply_page'] = $this->request->param('apply_page', 2, 'intval');
        $param['content'] = $this->request->param('content');
        $param['is_open'] = $this->request->param('is_open', 1, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->addOrEditSuspendedWindow($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取悬浮窗装修信息
     */
    public function getSuspendedWindow()
    {
        $param['source'] = $this->request->param('source', 'platform', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getSuspendedWindow($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 微页面编辑或添加
     */
    public function addOrEditMicroPage()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $param['type'] = $this->request->param('type', 2, 'intval');
        $param['page_title'] = $this->request->param('page_title', '', 'trim');
        $param['title_color'] = $this->request->param('title_color');
        $param['bg_color_style'] = $this->request->param('bg_color_style', 1, 'intval');
        $param['bg_color'] = $this->request->param('bg_color', '', 'trim');
        $param['bg_color_nav_style'] = $this->request->param('bg_color_nav_style', 1, 'intval');
        $param['bg_color_nav'] = $this->request->param('bg_color_nav', '', 'trim');
        $param['nav_bottom_display'] = $this->request->param('nav_bottom_display', 1, 'intval');
        $param['share_title'] = $this->request->param('share_title', '', 'trim');
        $param['share_desc'] = $this->request->param('share_desc', '', 'trim');
        $param['share_image_wechat'] = $this->request->param('share_image_wechat', '', 'trim');
        $param['share_image_h5'] = $this->request->param('share_image_h5', '', 'trim');
        $param['custom'] = $this->request->param('custom');
        $param = array_merge($param, Arr::only(input('post.'),[
            'bg_image', 'bg_image_nav', 'bg_color_nav_opacity'
        ]));
        if(!isset($param['title_color'])){
            return api_output_error(1003, L_("缺少页面标题背景颜色"));
        }
        $decService = new DecorateService();
        try {
            $result = $decService->addOrEditMicroPage($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \think\response\Json
     * 首页装修展示
     */
    public function getIndexPage()
    {
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['id'] = $this->request->param('id', '', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $param['from'] = $this->request->param('from', '', 'intval');//1=用户端 2=后台
        $decService = new DecorateService();
        try {
            $result = $decService->getIndexPage($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    public function getPageId()
    {
        $param['source'] = input('source', '', 'trim');
        $param['source_id'] = input('source_id', 0, 'intval');
        try {
            $result = (new DecorateService())->getPageId($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * @return \json
     * 获取编辑的微页面
     */
    public function getEditMicoPage()
    {
        $param['source'] = $this->request->param('source', '', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['type'] = $this->request->param('type', '1', 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getEditMicoPage($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取列表
     */
    public function getMicroPageList()
    {
        $param['source'] = $this->request->param('source', 'platform', 'trim');
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['page'] = $this->request->param('page', 0, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getMicroPageList($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取编辑的微页面
     */
    public function getMicroPage()
    {
        $id = $this->request->param('id', '', 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getMicroPage($id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 4.6.4店铺头部（店铺所属组件)
     */
    public function getMerchantStoreMsg(){
        $param['source_id'] = $this->request->param('source_id', 0, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getMerchantStoreMsg($param);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * @return \json
     * 删除微页面
     */
    public function delMicroPage()
    {
        $id = $this->request->param('id', '', 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->delMicroPage($id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 设为主页
     */
    public function setHomePage()
    {
        $id = $this->request->param('id', '', 'intval');
        $source = $this->request->param('source', '', 'trim');
        $source_id = $this->request->param('source_id', '', 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->setHomePage($id, $source, $source_id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 查看微页面（预览的二维码生成）
     */
    public function getPreview()
    {
        $id = $this->request->param('id', '', 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getPreview($id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取用过的图片
     */
    public function getImages()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getImages($source, $source_id, ($page - 1) * $pageSize, $pageSize);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取优惠券列表
     */
    public function getCoupons()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getCoupons($source, $source_id, $keyword);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 领取优惠券
     */
    public function addCoupons(){
        $coupon_data = array();
        $coupon_data['coupon_id'] =$this->request->param('coupon_id');
        $coupon_data['num'] =$this->request->param('num', 1, 'intval');
        $coupon_data['uid']  =$this->request->log_uid??'';
        //$coupon_data['status'] = 0;
        $coupon_data['receive_time'] =time();
        $coupon_style=$this->request->param('coupon_style','', 'trim');
        $decService = new DecorateService();
        try {
            $result = $decService->addCoupons($coupon_data,$coupon_style);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * @return \json
     * 营销组件活动获取
     */
    public function getActInfo()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $hdType = $this->request->param('hd_type', 'limited', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getActInfo($source, $source_id, $keyword, $hdType, $page, $pageSize);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 商城活动
     */
    public function getMallActInfo()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $hdType = $this->request->param('hd_type', 'limited', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getMallActInfo($source, $source_id, $keyword, $hdType, $page, $pageSize);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \think\response\Json
     * 商城商品列表
     */
    public function getMallGoods()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $decService = new DecorateService();
        try {
            $result = $decService->getMallGoods($source, $source_id, $keyword, input('page_size',10), input());
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \think\response\Json
     * 商城商品分组列表
     */
    public function getMallGoodsGroup()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $decService = new DecorateService();
        try {
            $result = $decService->getMallGoodsGroup($source, $source_id, $keyword, input('page_size',10), input());
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \think\response\Json
     * 外卖商品列表
     */
    public function getShopGoods()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $decService = new DecorateService();
        try {
            $result = $decService->getShopGoods($source, $source_id, $keyword, input('page_size',10), input());
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * @return \json
     * 外卖店铺列表
     */
    public function getStore()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getStore($source, $source_id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \think\response\Json
     * 外卖商品分组
     */
    public function getShopGoodsGroup()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $decService = new DecorateService();
        try {
            $result = $decService->getShopGoodsGroup($source, $source_id, $keyword, input('page_size', 10), input());
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     *商家或店铺链接库
     */
    public function getMerOrStoreLink()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $decService = new DecorateService();
        try {
            $result = $decService->getMerOrStoreLink($source, $source_id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取列表
     * @return \json
     */
    public function getHomeDecorateList()
    {
        $cat_key = $this->request->param('cat_key', 0, 'trim');
        $service = new AdverCategoryService();
        try {
            $arr = $service->getList($cat_key, $this->systemUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除
     * @return \json
     */
    public function getHomeDecorateDel()
    {
        $id = $this->request->param('id', '', 'intval');
        $service = new AdverCategoryService();
        try {
            $res = $service->getDel($id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑或查看是获取参数
     * @return \json
     */
    public function getHomeDecorateEdit()
    {
        $id = $this->request->param('id', '', 'intval');
        $service = new AdverCategoryService();
        try {
            $arr = $service->getEdit($id);
            return api_output(0, $arr, 'success');
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
     * 新增或编辑
     * @return \json
     */
    public function homeDecorateaddOrEdit()
    {
        $param['id'] = $this->request->param('id', '', 'intval');
        $param['cat_key'] = $this->request->param('cat_key', '', 'trim');
        $param['sort'] = $this->request->param('sort', '', 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['sub_name'] = $this->request->param('subname', '', 'trim');
        $param['pic'] = $this->request->param('pic', '', 'trim');
//        $param['currency'] = $this->request->param('currency', '', 'trim');
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
        $service = new AdverCategoryService();
        try {
            $res = $service->addOrEdit($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}