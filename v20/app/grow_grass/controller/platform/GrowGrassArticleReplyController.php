<?php


namespace app\grow_grass\controller\platform;


use app\grow_grass\model\db\GrowGrassArticleReply;
use app\grow_grass\model\service\GrowGrassArticleReplyService;
use app\grow_grass\model\service\GrowGrassArticleService;

class GrowGrassArticleReplyController extends AuthBaseController
{
    /**
     *
     * 评论列表
     */
    public function getCommentList()
    {
        $page = $this->request->param('page', '1', 'intval');
        $pageSize = $this->request->param('pageSize', '10', 'intval');
        $param['status'] = $this->request->param('status', '', 'trim');
        $param['comment'] = $this->request->param('comment', '', 'trim');
        $assign=(new GrowGrassArticleReplyService())->getCommentList($param,[],$page,$pageSize);
        return api_output(0, $assign);
    }

    /**
     * @return \json
     * 更新到店预约状态
     */
    public function updateGrowGrassArticleReply()
    {
        $reply_id = $this->request->param('reply_id', '', 'intval');
        $status = $this->request->param('status', '', 'intval');
        try {
            if (empty($reply_id) || empty($status)) {
                return api_output(1003, "参数缺失,请刷新下页面");
            }
            $thisReply = (new GrowGrassArticleReplyService())->getOneById($reply_id);
            if(empty($thisReply)){
                return api_output(1003, "评论不存在");
            }
            $where = [['reply_id', '=', $reply_id]];
            $data['status'] = $status;
            $ret = (new GrowGrassArticleReply())->updateThis($where,$data);
            //同步文章评论数
            (new GrowGrassArticleService())->syncReplyNum($thisReply['article_id']);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }
}