<?php
/**
 * GoodsReplyController.php
 * 商品评价
 * Create on 2020/9/8 14:48
 * Created by zhumengqun
 */

namespace app\mall\controller\api;

use app\BaseController;
use app\mall\model\service\GoodsReplyService;

class GoodsReplyController extends ApiBaseController
{
    /**
     * 商品评价列表
     * @return \json
     */
    public function goodsCommentList()
    {
        $goods_id = $this->request->param('goods_id', '', 'intval');
        $goods_sku = $this->request->param('goods_sku', '', 'trim');
        $goods_sku_dec = $this->request->param('goods_sku_dec', '', 'trim');
        $mark = $this->request->param('mark', '', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        try {
            $service = new GoodsReplyService();
            $arr = $service->getCommentList($goods_id, $goods_sku, $goods_sku_dec, $mark, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }

    }

    /**
     * 添加评价
     * @return \json
     */
    public function addGoodsComment()
    {
        //多商品提交评价
        $arr = array();
        $info = $this->request->param('info', '');
        if (empty($info)) {
            return api_output_error('1003', '评论信息为空');
        }
        foreach ($info as $val) {
            $arr = [
                'uid' => request()->log_uid ? request()->log_uid : 0,
                'reply_mv' => $val['reply_mv'] ? $val['reply_mv'] : '',
                'reply_pic' => $val['reply_pic'] ? $val['reply_pic'] : '',
                'order_id' => $val['order_id'] ? intval($val['order_id']) : '',
                'order_detail_id' => $val['order_detail_id'] ? intval($val['order_detail_id']) : '',
                'comment' => isset($val['comment']) ? trim($val['comment']) : '',
                'logistics_score' => $val['logistics_score'] ? intval($val['logistics_score']) : '',
                'service_score' => $val['service_score'] ? intval($val['service_score']) : '',
                'goods_score' => $val['goods_score'] ? intval($val['goods_score']) : '',
                'anonymous' => intval($val['anonymous'] ?? 1)
            ];
            try {
                $service = new GoodsReplyService();
                $result = $service->addGoodsComment($arr);

            } catch (\Exception $e) {
                return api_output_error('1003', $e->getMessage());
            }
        }
        return api_output(0, $result, 'success');
    }

    /**
     * 点击去评价
     * @return \json
     */
    public function goToComment()
    {
        $info = $this->request->param('info', '');
        try {
            $service = new GoodsReplyService();
            $arr = $service->goToComment($info);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 用户回复商家评论
     */
    public function userReplyMerchant()
    {
        if(!$this->_uid){
            throw new \think\Exception('请先登录！');
        }
        $rpl_id = $this->request->post('rpl_id', 0, 'intval');
        $content = $this->request->post('content', '', 'trim');
        try {
            $service = new GoodsReplyService();
            $arr = $service->userReplyMerchant($rpl_id, $content, $this->_uid);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }


}
//goodsCommentList  //商品评价列表
//addGoodsComment   //发表评价
//goToComment      //去评价
