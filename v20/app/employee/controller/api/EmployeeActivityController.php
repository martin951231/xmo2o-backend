<?php 

namespace app\employee\controller\api;


use app\employee\model\service\EmployeeActivityService;

class EmployeeActivityController extends ApiBaseController
{

    /**
     * 活动首页
     */
    public function activityIndex()
    {
        $param['activity_id'] = $this->request->param('activity_id', 0, 'intval');
        $param['uid']         = $this->_uid;
        if (empty($param['activity_id'])) {
            return api_output_error(1003, '活动ID参数缺失');
        }
        try {
            $data = (new EmployeeActivityService())->activityIndex($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success'); 
    }

    /**
     * 活动首页商品
     */
    public function activityIndexGoods()
    {
        $param['activity_id'] = $this->request->param('activity_id', 0, 'intval');
        $param['store_id']    = $this->request->param('store_id', 0, 'intval');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['uid']         = $this->_uid;
        if (empty($param['activity_id'])) {
            return api_output_error(1003, '活动ID参数缺失');
        }
        try {
            $data = (new EmployeeActivityService())->activityIndexGoods($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }    
    
    /**
    * 活动列表
    */
   public function activityList()
   {
       $param['page'] = $this->request->param('page', 0, 'intval');
       $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
       $param['keyword'] = $this->request->param('keyword', '', 'trim');
       $param['uid']         = $this->_uid;
       if (empty($param['uid'])) {
           return api_output_error(1002, '未登录');
       }
       try {
           $data = (new EmployeeActivityService())->activityList($param);
       } catch (\Exception $e) {
           return api_output_error(1003, $e->getMessage());
       }
       return api_output(0, $data, 'success'); 
   }


}