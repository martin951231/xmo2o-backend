<?php
/**
 * 后台首页
 * author by hengtingmei
 */
namespace app\common\controller\platform;

use app\common\model\service\IndexService;

class MainController extends AuthBaseController {
    public function initialize()
    {
        parent::initialize();
    }

    /**
	 * author by xiaohei
     * desc: 系统后台 配置信息、常用功能 两块不会轮询的功能。
     * return :object
     */
	public function getMainBasicData(){
        $returnArr = (new IndexService())->getConfigSetSpeedProgress($this->systemUser);
        $returnArr['statistics_data'] = (new IndexService())->getSmallStatisticsData($this->systemUser);
		return api_output(0, $returnArr);
	}

    /**
	 * author by 衡婷妹
     * 获得中部部小块统计数据显示
     * return :object
     */
	public function getMiddleStatisticsData(){
		$param = $this->request->param();
        $returnArr = (new IndexService())->getMiddleStatisticsData($param);
		return api_output(0, $returnArr);
	}

    /**
     * author by 衡婷妹
     * 待办事项
     * return :object
     */
    public function getBacklog(){
        $param = $this->request->param();
        $returnArr = (new IndexService())->getBacklog($param);
        return api_output(0, $returnArr);
    }

    /**
     * 是否展示关闭老版商城和餐饮的按钮
     */
    public function closeOldShow()
    {
        try {
            $arr = (new IndexService())->closeOldShow();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 关闭老版商城和餐饮
     */
    public function closeOld()
    {
        $business = $this->request->param('business', ['mall','meal']);//需要删除的业务，mall:老版商城，meal:老版餐饮
        try {
            $arr = (new IndexService())->closeOld($business);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}