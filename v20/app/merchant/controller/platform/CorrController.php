<?php
/**
 * CorrController.php
 * 营业信息纠错controller
 * Create on 2021/5/8
 * Created by wangchen
 */

namespace app\merchant\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\merchant\model\service\MerchantCorrService;
use think\App;

class CorrController extends AuthBaseController
{
    /**
     * 按条件查询
     * @return \json
     */
    public function searchCorr()
    {
        $type = $this->request->param('type', '', 'intval');
        $content = $this->request->param('content', '', 'trim');
        $begin_time = $this->request->param('begin_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        $status = $this->request->param('status', '2', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $replyService = new MerchantCorrService();
            $arr = $replyService->searchCorr($type, $content, $begin_time, $end_time, $status,$page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 查看评论详情
     * @return \json
     */
    public function getCorrDetails()
    {
        $id = $this->request->param('id', '', 'intval');
        try {
            $replyService = new MerchantCorrService();
            $arr = $replyService->getCorrDetails($id);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 设置为已处理
     * @return \json
     */
    public function getEditCorr()
    {
        $id = $this->request->param('id', '', 'intval');
        try {
            $replyService = new MerchantCorrService();
            $result = $replyService->getEditCorr($id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }
}

