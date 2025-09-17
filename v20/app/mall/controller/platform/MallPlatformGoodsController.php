<?php
/**
 * MallPlatformGoodsController.php
 * 平台商品操作
 * Create on 2020/9/14 15:40
 * Created by zhumengqun
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\common\model\service\CommonService;
use app\mall\model\service\MallGoodsService;

class MallPlatformGoodsController extends AuthBaseController
{
    /**
     * 平台后台-按条件获取商品列表
     * @return \json
     */
    public function getGoodsList()
    {
        $keyword = $this->request->param('keyword', '', 'trim');
        $merList = $this->request->param('merList', '');
        $storeList = $this->request->param('storeList', '');
        $cat_id = $this->request->param('cat_id', '');
        $param['browse'] = $this->request->param('browse', 0);//根据浏览量排序（1-倒序，2-正序）
        $param['browseToday'] = $this->request->param('browse_today', 0);//根据今日浏览量排序（1-倒序，2-正序）
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');//查询开始时间
        $param['end_time'] = $this->request->param('end_time', '', 'trim');//查询结束时间
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->getPlatformGoodsBrowseList($keyword, $merList, $storeList, $page, $pageSize,$cat_id, false,$param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 按条件获取商品列表
     * @return \json
     */
    public function getGoodsListByName()
    {
        $keyword = $this->request->param('keyword1', '', 'trim');
        $merList = $this->request->param('merList', '');
        $storeList = $this->request->param('storeList', '');
        $cat_id = $this->request->param('cat_id', '');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->getPlatformGoodsListByName($keyword, $merList, $storeList, $page, $pageSize,$cat_id, false);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 设置积分
     * @return \json
     */
    public function setIntegral()
    {
        $goods_id = $this->request->param('goods_id');
        $price = $this->request->param('min_price');
        $score_percent = $this->request->param('score_percent', 0, 'trim');
        $score_max = $this->request->param('score_max', '', 'trim');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->setIntegral($goods_id, $price, $score_percent, $score_max);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 设置虚拟销量
     * @return \json
     */
    public function setVirtual()
    {
        $goods_id = $this->request->param('goods_id');
        $sales = $this->request->param('sales');
        $virtual_set = $this->request->param('virtual_set');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->setVirtual($goods_id, $sales, $virtual_set);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 设置佣金
     * @return \json
     */
    public function setCommission()
    {
        $goods_id = $this->request->param('goods_id', '', 'intval');
        $spread_rate = $this->request->param('spread_rate', -1, 'trim');
        $sub_spread_rate = $this->request->param('sub_spread_rate', 0, 'trim');
        $third_spread_rate = $this->request->param('third_spread_rate', 0, 'trim');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->setCommission($goods_id, $spread_rate, $sub_spread_rate, $third_spread_rate);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 设置上下架
     * @return \json
     */
    public function setStatus()
    {
        $status = $this->request->param('status', 0, 'intval');
        $goods_id = $this->request->param('goods_id');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->setStatus($goods_id, $status);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 设置排序
     * @return \json
     */
    public function setSort()
    {
        $sort = $this->request->param('sort', 0, 'intval');
        $goods_id = $this->request->param('goods_id');
        $type = 'platform';
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->setSort($goods_id, $sort, $type);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 设置置顶
     * @return \json
     */
    public function setFirst()
    {
        $is_first = $this->request->param('is_first', 0, 'intval');
        $cat_id = $this->request->param('cat_id', 0, 'intval');
        $goods_id = $this->request->param('goods_id');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->setFirst($goods_id, $is_first, $cat_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取店铺或商家列表
     * @return \json
     */
    public function getMerOrStoreList()
    {
        $search = $this->request->param('search', '', 'trim');
        $type = $this->request->param('type', '', 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->getMerOrStoreList($type, $search, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 导出商品
     * @return \json
     */
    public function exportGoods()
    {
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['merList'] = $this->request->param('merList', '');
        $param['storeList'] = $this->request->param('storeList', '');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->addGoodsExportPlatForm($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 平台商品编辑跳转到商家商品编辑
     */
    public function merchantGoodsEdit()
    {
        $mer_id = $this->request->param('mer_id', '', 'intval');
        $ticket = $this->request->param('ticket_mer', '', 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->merchantGoodsEdit($ticket, $mer_id, $this->systemUser);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * @return \json
     * 设置推荐
     */
    public function setRecommend(){
        $goods_id = $this->request->param('goods_id');
        if(empty($goods_id)){
            return api_output_error('1003', "推荐失败,请重新尝试");
        }
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->setRecommend($goods_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * @return \json
     * 取消推荐
     */
    public function cancelRecommend(){
        $goods_id = $this->request->param('goods_id');
        if(empty($goods_id)){
            return api_output_error('1003', "取消推荐失败,请重新尝试");
        }
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->cancelRecommend($goods_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 商品审核列表
     * @return \json
     */
    public function getAuditGoodsList()
    {
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['audit_status'] = $this->request->param('audit_status', null, 'trim');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->getAuditGoodsList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }
    /**
     * 商品审核详情
     * @return \json
     */
    public function getAuditGoodsDetail()
    {
        $goods_id = $this->request->param('goods_id', '', 'intval');
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->getEditGoods($goods_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 商品审核
     * @return \json
     */
    public function auditGoods()
    {
        $param['goods_ids'] = $this->request->param('goods_ids', '', 'trim');
        $param['audit_status'] = $this->request->param('audit_status', 1, 'intval');
        $param['audit_msg'] = $this->request->param('audit_msg', '', 'trim');
        $param['admin_id'] = $this->systemUser['id'];
        $mallGoodsService = new MallGoodsService();
        try {
            $arr = $mallGoodsService->auditGoods($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 伪登录
     */
    public function loginMerchant()
    {
        $mer_id = $this->request->param('mer_id', 0, 'intval');
        $result = (new CommonService())->platformLoginMerchant($mer_id);
        return api_output(0,  $result, 'success');
    }
}
