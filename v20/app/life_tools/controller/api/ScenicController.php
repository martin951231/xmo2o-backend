<?php
/**
 * 景区接口控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\db\LifeToolsMember;
use app\life_tools\model\service\HomeDecorateService;
use app\life_tools\model\service\LifeToolsOrderService;
use app\life_tools\model\service\LifeToolsService;

class ScenicController extends ApiBaseController
{
    /**
     * 景区列表
     */
    public function scenicList()
    {
        $param['cat_id']   = $this->request->param('cat_id', 0, 'intval');
        $param['long']     = $this->request->param('long', '', 'trim');
        $param['lat']      = $this->request->param('lat', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('page_size', 10, 'intval');
        try {
            $arr = (new LifeToolsService())->getToolsList($param, 'scenic');
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 景区首页接口
     */
    public function index()
    {
        $Homeservice = new HomeDecorateService();
        $return = [];//返回的数据集合
        //顶部轮播图
        $return['rotation'] = $Homeservice->getAdverByCatKey('wap_life_tools_index_top', 10);
        //导航栏导航列表
        $return['slider']   = $Homeservice->getAdverByCatKey('wap_life_tools_slider', 20);
        //排名
        $return['ranking']  = $Homeservice->getIndexRecommend(5);
        //滚动公告
        $return['information'] = $Homeservice->getIndexRecommend(6);
        //热门推荐
        $return['hot_recommend'] = $Homeservice->getScenicHotRecommendByUser();
        //景区首页分享标题和图片
        $return['share_info'] = [
            'title'=>cfg('life_tools_scenic_home_share_title'),
            'img'=>cfg('life_tools_scenic_home_share_img'),
        ];
        return api_output(0, $return);
    }

    /**
     * 景区首页-推荐美食酒店
     */
    public function indexRecList()
    {
        $param['type']     = $this->request->param('type', 'all', 'trim'); //‘all’=推荐，‘store’=周边美食，‘hotel’=住宿好店
        $param['long']     = $this->request->param('long', '', 'trim');
        $param['lat']      = $this->request->param('lat', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('page_size', 10, 'intval');
        $param['order']    = 'distance ASC';
        try {
            $arr = (new LifeToolsService())->getScenicRecList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 景区订单转赠
     */
    public function giveOrder()
    {
        $this->checkLogin();
        $code = $this->request->param('code', '', 'trim');
        if (empty($code)) {
            return api_output_error(1003, '参数错误');
        }
        try {
            $arr = (new LifeToolsOrderService())->giveOrder($code, $this->userInfo);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 景区投诉建议列表
     */
    public function complaintAdviceList()
    {
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('page_size', 10, 'intval');
        $param['tools_id'] = $this->request->param('tools_id', 0, 'intval');
        $param['uid'] = $this->_uid;
        $param['from'] = 'api';
        try {
            $arr = (new LifeToolsService())->complaintAdviceList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 景区投诉建议详情
     */
    public function complaintAdviceDetail()
    {
        $pigcms_id = $this->request->param('pigcms_id', 1, 'intval');
        try {
            $arr = (new LifeToolsService())->complaintAdviceDetail($pigcms_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 景区提交投诉建议
     */
    public function complaintAdviceSave()
    {
        $this->checkLogin();
        $param['tools_id'] = $this->request->param('tools_id', 0, 'intval');
        $param['content']  = $this->request->param('content', '', 'trim');
        $param['images']   = $this->request->param('images', '', 'trim');
        $param['long']     = $this->request->param('long', '', 'trim');
        $param['lat']      = $this->request->param('lat', '', 'trim');
        $param['uid']      = $this->_uid;
        try {
            (new LifeToolsService())->complaintAdviceSave($param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}