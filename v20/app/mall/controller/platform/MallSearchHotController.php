<?php
/**
 * MallSearchHotController.php
 * 商城3.0 搜索热词controller
 * Create on 2020/10/16 11:59
 * Created by zhumengqun
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\SearchHotMallNewService;

class MallSearchHotController extends AuthBaseController
{
    /**
     * 获取热搜词列表
     * @return \json
     */
    public function getSearchHotList()
    {
        $page = $this->request->param('page',1,'trim');
        $pageSize = $this->request->param('pageSize',10,'trim');
        $hotSearchService = new SearchHotMallNewService();
        try {
            $arr = $hotSearchService->getSearchHotList($page,$pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑或添加热搜词
     * @return \json
     */
    public function addOrEditSearchHot()
    {
        $param['id'] = $this->request->param('id','','intval');
        $param['name'] = $this->request->param('name','','trim');
        $param['url'] = $this->request->param('url','','trim');
        $param['type'] = $this->request->param('type',0,'trim');
        $param['is_first'] = $this->request->param('is_first',0,'trim');
        $param['hottest'] = $this->request->param('hottest',0,'trim');
        $param['sort'] = $this->request->param('sort',1,'intval');
        $param['add_time'] = time();
        $hotSearchService = new SearchHotMallNewService();
        try {
            $res = $hotSearchService->addOrEditSearchHot($param);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 获取热搜词
     * @return \json
     */
    public function getEditSearchHot()
    {
        $id = $this->request->param('id','','intval');
        $hotSearchService = new SearchHotMallNewService();
        try {
            $arr = $hotSearchService->getEditSearchHot($id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 删除热搜词
     * @return \json
     */
    public function delSearchHot()
    {
        $ids = $this->request->param('ids');
        $hotSearchService = new SearchHotMallNewService();
        try {
            $res = $hotSearchService->delSearchHot($ids);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 保存排序
     * @return \json
     */
    public function saveSort()
    {
        $id = $this->request->param('id','','intval');
        $sort = $this->request->param('sort',0,'intval');
        $hotSearchService = new SearchHotMallNewService();
        try {
            $arr = $hotSearchService->saveSort($sort,$id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取热搜记录top
     * @return \json
     */
    public function getHotRecord()
    {
        $start_time = $this->request->param('start_time','','trim');
        $end_time = $this->request->param('end_time','','trim');
        $hotSearchService = new SearchHotMallNewService();
        try {
            $arr = $hotSearchService->getHotRecord($start_time,$end_time);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}