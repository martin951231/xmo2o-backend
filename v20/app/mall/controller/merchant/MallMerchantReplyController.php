<?php
/**
 * MallMerchantReplyService.php
 * 商家后台-商品评价controller
 * Create on 2020/9/11 9:55
 * Created by zhumengqun
 */

namespace app\mall\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\mall\model\service\MallMerchantReplyService;
use think\App;

class MallMerchantReplyController extends AuthBaseController
{

    /**
     * 获取该商家的全部店铺
     * @return \json
     */
    public function getStores()
    {
        $mer_id = $this->merId;
        $replyService = new MallMerchantReplyService();
        try {
            $arr = $replyService->getStores($mer_id);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 按条件查询
     * @return \json
     */
    public function searchReply()
    {
        $mer_id = $this->merId;
        $goods_name = $this->request->param('goods_name', '', 'trim');
        $store_id = $this->request->param('store_id', '', 'trim');
        $begin_time = $this->request->param('begin_time', '', 'trim');
        $end_time = $this->request->param('end_time', '', 'trim');
        $status = $this->request->param('status', '2', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $replyService = new MallMerchantReplyService();
            $arr = $replyService->searchReply($mer_id, $store_id, $goods_name, $begin_time, $end_time,$status, $page, $pageSize);
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
            $replyService = new MallMerchantReplyService();
            $arr = $replyService->getReplyDetails($rpl_id);
            return api_output(0, $arr, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 商家提交回复
     * @return \json
     */
    public function merchantReply()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        $merchant_reply_content = $this->request->param('merchant_reply_content', '', 'trim');
        try {
            $replyService = new MallMerchantReplyService();
            $result = $replyService->merchantReply($rpl_id, $merchant_reply_content);
            return api_output(0, $result, 'success');

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 设置展示主页
     * @return \json
     */
    public function getShowHomePage()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        try {
            $replyService = new MallMerchantReplyService();
            $result = $replyService->getQualityHome($rpl_id, 1);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 设置优质评论
     * @return \json
     */
    public function getQualityReviews()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        try {
            $replyService = new MallMerchantReplyService();
            $result = $replyService->getQualityHome($rpl_id, 2);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 取消展示主页
     * @return \json
     */
    public function getShowHomePageCancel()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        try {
            $replyService = new MallMerchantReplyService();
            $result = $replyService->getQualityHome($rpl_id, 1, 1);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 取消优质评论
     * @return \json
     */
    public function getQualityReviewsCancel()
    {
        $rpl_id = $this->request->param('rpl_id', '', 'intval');
        try {
            $replyService = new MallMerchantReplyService();
            $result = $replyService->getQualityHome($rpl_id, 2, 1);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
//getStores()  获取搜索框的店铺
//searchReply()    按条件查询
//getReplyDetails()  获取评价详情
//MerchantReply()    商家回复

