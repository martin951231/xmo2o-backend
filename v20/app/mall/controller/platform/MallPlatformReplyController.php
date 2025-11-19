<?php
/**
 * MallPlatformReplyController.php
 * 平台后台-商品评价controller
 * Create on 2020/9/11 17:33
 * Created by zhumengqun
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\MallPlatformReplyService;
use think\App;

class MallPlatformReplyController extends AuthBaseController
{
    /**
     * 按条件查询
     * @return \json
     */
    public function searchReply()
    {
        $type = $this->request->param('type', '', 'intval');
        $content = $this->request->param('content', '', 'trim');
        $begin_time = $this->request->param('begin_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        $status = $this->request->param('status', '2', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $replyService = new MallPlatformReplyService();
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
            $replyService = new MallPlatformReplyService();
            $arr = $replyService->getReplyDetails($rpl_id);
            return api_output(0, $arr, 'success');

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
            $replyService = new MallPlatformReplyService();
            $result = $replyService->delReply($rpl_id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 展示/不展示评价 
     * @return \json
     */
    public function isShowReply()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        try {
            $replyService = new MallPlatformReplyService();
            $result = $replyService->isShowReply($rpl_id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导出评价
     * @return \json
     */
    public function exportReplyList()
    {
        ini_set('memory_limit', '2048M');
        $type = $this->request->param('type', '', 'intval');
        $content = $this->request->param('content', '', 'trim');
        $begin_time = $this->request->param('begin_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        $status = $this->request->param('status', '2', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $replyService = new MallPlatformReplyService();
            $arr = $replyService->searchReply($type, $content, $begin_time, $end_time, $status,$page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}

