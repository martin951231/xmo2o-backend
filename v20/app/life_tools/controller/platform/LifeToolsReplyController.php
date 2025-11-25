<?php


namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\LifeToolsReplyService;

class LifeToolsReplyController extends AuthBaseController
{
    /**
     * 按条件查询
     * @return \json
     */
    public function searchReply()
    {
        $type = $this->request->param('type', 0, 'intval');//0是未选择 1景区 2 场馆 3 课程
        $content = $this->request->param('content', '', 'trim');
        $begin_time = $this->request->param('begin_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        $status = $this->request->param('status', '2', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $replyService = new LifeToolsReplyService();
            $arr = $replyService->searchReply($type, $content, $begin_time, $end_time, $status,$page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 查看评论详情
     * @return \json
     */
    public function getReplyDetails()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        try {
            $replyService = new LifeToolsReplyService();
            $arr = $replyService->getReplyDetails($rpl_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            dd($e);
            //return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 回复评价
     */
    public function subReply(){
        $id = $this->request->param('id', 0, 'intval');
        if(empty($id)){
            return api_output_error(1003, "缺少必要参数");
        }
        $content = $this->request->param('reply_content', '', 'trim');
        if(empty($content)){
            return api_output_error(1003, "缺少回复内容");
        }
        $replyService = new LifeToolsReplyService();
        $where=[['reply_id','=',$id]];
        $data['replys_content']=$content;
        $data['replys_time']=time();
        $ret = $replyService->subReply($where,$data);
        if($ret!==false){
            return api_output(0, [], 'success');
        }else{
            return api_output_error(1003, "回复失败");
        }
    }

    /**
     * 获得评价回复内容
     */
    public function getReplyContent(){
        $id = $this->request->param('id', 0, 'intval');
        if(empty($id)){
            return api_output_error(1003, "缺少必要参数");
        }
        $replyService = new LifeToolsReplyService();
        $arr['reply_content'] = $replyService->getReplyContent($id);
        return api_output(0, $arr, 'success');
    }

    /**
     * 展示/不展示评价
     * @return \json
     */
    public function isShowReply()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        try {
            $replyService = new LifeToolsReplyService();
            $result = $replyService->isShowReply($rpl_id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除评价
     * @return \json
     */
    public function delReply()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        try {
            $replyService = new LifeToolsReplyService();
            $result = $replyService->delReply($rpl_id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }
}