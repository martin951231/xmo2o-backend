<?php


namespace app\life_tools\controller\merchant;
use app\life_tools\model\service\LifeToolsSportsActivityService;
use app\merchant\controller\merchant\AuthBaseController;

class LifeToolsSportsActivityController extends AuthBaseController
{
    /**
     * 约战列表
     */
   public function getSportsActivityList(){
       $param['page'] = $this->request->param('page', 1, 'intval');
       $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
       $param['mer_id'] = $this->merId;
       $param['content'] = $this->request->param('keyword', '', 'trim');
       try {
           $data=(new LifeToolsSportsActivityService())->getList($param);
       } catch (\Exception $e) {
           return api_output_error(1001, $e->getMessage());
       }
       return api_output(0, $data);
   }

    /**
     * @return \json
     * 修改状态/删除
     */
    public function updateSportsActivityStatus()
    {
        $param=$this->request->param();
        $param['mer_id'] = $this->merId;
        if (empty($param['activity_id'])) {
            return api_output_error(1003, '缺少必要参数');
        }
        $ret = (new LifeToolsSportsActivityService())->updateStatus($param);
        if ($ret) {
            return api_output(0, []);
        } else {
            return api_output_error(1003, '修改失败');
        }
    }

    /**
     * 编辑
     */
    public function editSportsActivity()
    {
        $param['activity_id'] =$this->request->param('activity_id', 0, 'intval');
        $param['mer_id'] = $this->merId;
        if (empty($param['activity_id'])) {
            return api_output_error(1003, '缺少必要参数');
        }
        $detail=(new LifeToolsSportsActivityService())->editSportsActivity($param);
        return api_output(0, $detail);
    }

    /**
     * 添加编辑保存
     */
    public function addSportsActivity(){
        $param['activity_id'] =$this->request->param('activity_id', 0, 'intval');
        $param['goods'] = $this->request->param('goods');
        $param['mer_id'] = $this->merId;
        $param['title'] = $this->request->param('title', '', 'trim');
        $param['group_type'] = $this->request->param('group_type', '', 'trim');
        $param['num'] =$this->request->param('num', 0, 'intval');
        $param['desc'] = $this->request->param('desc', '', 'trim');
        $param['leader_back_type'] = $this->request->param('leader_back_type', 0, 'trim');
        $param['leader_back_time'] = $this->request->param('leader_back_time', 0, 'trim');
        $param['other_back_type'] = $this->request->param('other_back_type', 0, 'trim');
        $param['other_back_time'] = $this->request->param('other_back_time', 0, 'trim');
        $param['is_only_sports_activity'] = $this->request->param('is_only_sports_activity', 0, 'intval');
        $ret=(new LifeToolsSportsActivityService())->addSportsActivity($param);
        if ($ret) {
            return api_output(0, []);
        } else {
            return api_output_error(1003, '保存失败');
        }
    }

    /**
     * 获取约战订单列表
     * @return \json
     */
    public function getSportsActivityOrderList()
    {
        $param['mer_id']      = $this->merId;
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        try {
            $arr = (new LifeToolsSportsActivityService())->getSportsActivityOrderList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}