<?php
/**
 * 订单详情页推荐商品管理
 */

namespace app\shop\controller\platform;

use app\shop\model\service\goods\RecommendService;

class RecommendController extends AuthBaseController
{
    /**
     * 商品推荐组列表
     */
    public function getRecommendList()
    {
        $params = [];
        $params['pageSize'] = $this->request->post('pageSize', 10, 'trim,intval');
        $params['page'] = $this->request->post('page', 1, 'trim,intval');
        try {
            $data = (new RecommendService())->getRecommendList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 查询外卖店铺分类
     */
    public function getCategory()
    {
        try {
            $data = (new RecommendService())->getCategory();
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 添加商品推荐组
     */
    public function addRecommend()
    {
        $params = [];
        $params['name'] = $this->request->post('name', '','trim');
        $params['cat_id_ary'] = $this->request->post('cat_id_ary', []);//分类数组
        $params['scale'] = $this->request->post('scale', 0, 'intval');//推荐占比
        $params['status'] = $this->request->post('status', 0, 'intval');
        try {
            $data = (new RecommendService())->addRecommend($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 编辑商品推荐组
     */
    public function editRecommend()
    {
        $params = [];
        $params['id'] = $this->request->post('id', 0,'intval');
        $params['name'] = $this->request->post('name', '','trim');
        $params['cat_id_ary'] = $this->request->post('cat_id_ary', []);//分类数组
        $params['scale'] = $this->request->post('scale', 0, 'intval');//推荐占比
        $params['status'] = $this->request->post('status', 0, 'intval');
        $params['update_type'] = $this->request->post('update_type', 0, 'intval');//修改类型（0基本信息，1状态，2占比）
        try {
            $data = (new RecommendService())->editRecommend($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取商品推荐组详情
     */
    public function getRecommendDetail()
    {
        $params = [];
        $params['id'] = $this->request->post('id', 0,'intval');
        try {
            $data = (new RecommendService())->getRecommendDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 批量删除商品推荐组
     */
    public function delRecommend()
    {
        $params = [];
        $params['ids'] = $this->request->post('ids', '','trim');
        try {
            $data = (new RecommendService())->delRecommend($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 查询推荐商品列表
     */
    public function getRecommendGoodsList()
    {
        $params = [];
        $params['pageSize'] = $this->request->post('pageSize', 10, 'trim,intval');
        $params['page'] = $this->request->post('page', 1, 'trim,intval');
        $params['id'] = $this->request->post('id', 0,'trim,intval');
        $params['serch_type'] = $this->request->post('serch_type', 0, 'trim,intval');//查询类型（1：商品名称/文章标题）
        $params['keyword'] = $this->request->post('keyword', '', 'trim');
        $params['business'] = $this->request->post('business', '', 'trim');//查询类型（shop：外卖，mall：商城，group：团购，grow_grass：种草）
        try {
            $data = (new RecommendService())->getRecommendGoodsList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 查询业务商品列表
     */
    public function getGoodsList()
    {
        $params = [];
        $params['pageSize'] = $this->request->post('pageSize', 10, 'trim,intval');
        $params['page'] = $this->request->post('page', 1, 'trim,intval');
        $params['business'] = $this->request->post('business', 'shop', 'trim');//查询类型（shop：外卖，mall：商城，group：团购，grow_grass：种草）
        $params['serch_type'] = $this->request->post('serch_type', 0, 'trim,intval');//查询类型（1：商品名称，2：商家名称，3.文字标题（仅种草有效），4.文章作者（仅种草有效））
        $params['keyword'] = $this->request->post('keyword', '', 'trim');
        $params['id'] = $this->request->post('id', 0, 'trim,intval');
        try {
            $data = (new RecommendService())->getGoodsList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 添加推荐商品
     */
    public function addRecommendGoods()
    {
        $params = [];
        $params['id'] = $this->request->post('id', '', 'trim');
        $params['goods_ids'] = $this->request->post('goods_ids', '', 'trim');
        $params['business'] = $this->request->post('business', '', 'trim');//类型（shop：外卖，mall：商城，group：团购，grow_grass：种草）
        try {
            $data = (new RecommendService())->addRecommendGoods($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 编辑推荐商品信息
     */
    public function editRecommendGoods()
    {
        $params = [];
        $params['id'] = $this->request->post('id', '', 'trim');
        $params['update_type'] = $this->request->post('update_type', 0, 'trim');//修改类型（1状态，2排序）
        $params['status'] = $this->request->post('status', 0, 'trim');
        $params['sort'] = $this->request->post('sort', 0, 'trim');
        try {
            $data = (new RecommendService())->editRecommendGoods($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    
    /**
     * 删除推荐商品信息
     */
    public function delRecommendGoods()
    {
        $params = [];
        $params['ids'] = $this->request->post('ids', '', 'trim');
        try {
            $data = (new RecommendService())->delRecommendGoods($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}
