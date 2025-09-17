<?php


namespace app\group\controller\platform;


use app\group\model\service\GroupHomeMenuService;

class GroupHomeMenuController extends AuthBaseController
{
    /**
     * 团购首页配置-附近好店是否展示
     */
    public function changeShow(){
        $is_show = $this->request->param('is_show', 1, 'intval');
        $service = new GroupHomeMenuService();
        try {
            $res = $service->changeShow($is_show);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购首页配置-附近好店显示状态,活动推荐配置
     * @return \json
     */
    public function getShow(){
        $service = new GroupHomeMenuService();
        try {
            $res = $service->getShow();
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取团购首页顶部配置
     * @return \think\response\Json
     */
    public function getHomeTopConfig(){
        $service = new GroupHomeMenuService();
        try {
            $res = $service->getHomeTopConfig();
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购首页顶部配置-编辑
     * @return \think\response\Json
     */
    public function editHomeTopConfig(){
        $params['group_home_top_bg_style'] = $this->request->param('group_home_top_bg_style','0','trim,string');
        $params['group_home_top_bg_value'] = $this->request->param('group_home_top_bg_value','','trim,string');
        $service = new GroupHomeMenuService();
        try {
            $res = $service->editHomeTopConfig($params);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购首页配置-活动推荐选择
     */
    public function changeActivityType(){
        $parms['group_activity_recommendations'] = $this->request->param('group_activity_recommendations', 0, 'intval');
        $service = new GroupHomeMenuService();
        try {
            $res = $service->changeActivityType($parms);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}