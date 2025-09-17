<?php
/**
 * 投票活动
 */

namespace app\community\controller\platform;

use app\community\controller\platform\AuthBaseController as BaseController;
use app\community\model\service\VoteService;

class VoteController extends BaseController
{
    /**
     * 获取投票活动列表
     */
    public function getVoteList()
    {
        $param['page'] = $this->request->param('page',0,'int');
        $param['pageSize']=$this->request->param('pageSize',10,'int');
        $param['village_name']=$this->request->param('village_name','','trim');
        $param['vote_title']=$this->request->param('vote_title','','trim');
        $param['start_time']=$this->request->param('start_time','','trim');
        $param['end_time']=$this->request->param('end_time','','trim');
        $param['sorter']=$this->request->param('sorter','','trim');
        $voteService = new VoteService();
        try {
            $data = $voteService->getVoteList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
    
    /**
     * 获取投票活动详情
     */
    public function getVoteDetail()
    {
        $param = [];
        $param['vote_id'] = $this->request->param('vote_id',0,'int');
        $voteService = new VoteService();
        try {
            $data = $voteService->getVoteDetail($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
    
    /**
     * 获取投票活动结果
     */
    public function getVoteResult()
    {
        $param = [];
        $param['vote_id'] = $this->request->param('vote_id',0,'int');
        $voteService = new VoteService();
        try {
            $data = $voteService->getVoteResult($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
    
    /**
     * 获取投票活动结果详情
     */
    public function getVoteResultOne()
    {
        $param = [];
        $param['block_id'] = $this->request->param('block_id',0,'int');
        $param['page'] = $this->request->param('page',0,'int');
        $param['pageSize']=$this->request->param('pageSize',10,'int');
        $voteService = new VoteService();
        try {
            $data = $voteService->getVoteResultOne($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "success");
    }
    
}