<?php
/**
 * 种草话题controller
 * Author: hengtingmei
 * Date Time: 2021/05/15
 */

namespace app\grow_grass\controller\api;

use app\group\model\service\StoreGroupService;
use app\grow_grass\model\service\GrowGrassArticleCollectService;
use app\grow_grass\model\service\GrowGrassArticleLikeService;
use app\grow_grass\model\service\GrowGrassArticleReplyLikeService;
use app\grow_grass\model\service\GrowGrassArticleReplyService;
use app\grow_grass\model\service\GrowGrassArticleService;
use app\grow_grass\model\service\GrowGrassBindStoreService;
use app\grow_grass\model\service\GrowGrassCategoryService;
use app\grow_grass\model\service\GrowGrassFollowService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\store\MerchantCategoryService;

class ArticleController extends ApiBaseController
{
    /**
     * 获得选择话题时的分类列表
     */
    public function getCatList(){
        $param['keyword'] = $this->request->param('keyword', '', 'trim');

        $result = (new MerchantCategoryService())->getListByTopic($param);

        return api_output(1000, $result);

    }

    /**
     * 获得话题列表
     */
    public function getTopicList(){
        $param['cat_id'] = $this->request->param('cat_id', '0', 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['page'] = $this->request->param('page', '1', 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $result = (new GrowGrassCategoryService())->getTopicList($param);

        return api_output(1000, $result);

    }

    /**
     * 获取店铺列表
     */
    public function getStoreList(){
        $param = $this->request->param();
        $lng = $this->request->param('lng', '', 'trim');
        $lat = $this->request->param('lat', '', 'trim');
        $result = (new MerchantStoreService())->getStoreListLimit($param);
        if($result['list']){
            foreach($result['list'] as &$store){
                $store = (new GrowGrassBindStoreService())->formatStore($store, $lng, $lat);
            }

        }

        return api_output(1000, $result);

    }
    /**
     * 获得商品列表
     */
    public function getGoodsList(){
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['page'] = $this->request->param('page', '1', 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $storeId = $this->request->param('store_id', '0', 'intval');

        $result = (new StoreGroupService())->getStoreGroup([$storeId], $param);
        foreach ($result as $k => $v) {
            $result[$k]['goods_type'] = 'group';
            $result[$k]['goods_id'] = $v['group_id'];
            $picArr = explode(';', $v['pic']);
            $result[$k]['image'] = $picArr ? replace_file_domain($picArr[0]) : '';
        }
        return api_output(1000, ['list'=>$result,'page_size'=>$param['pageSize']]);

    }

    /**
     * 获取文章编辑详情
     */
    public function getArticleEditInfo(){
        $id = $this->request->param('article_id', '0', 'intval');

        $result = (new GrowGrassArticleService())->getArticleEditInfo($id);

        return api_output(1000, $result);

    }

    /**
     * 保存文章
     */
    public function saveArticle(){
        $param = $this->request->param();

        $result = (new GrowGrassArticleService())->saveArticle($param);

        return api_output(1000, $result);

    }
    /**
     * 获得话题详情
     */
    public function getArticleDetail(){
        $id = $this->request->param('article_id', '0', 'intval');
        
        // 增加浏览记录
        (new GrowGrassArticleService())->addViewsNum($id);

        $result = (new GrowGrassArticleService())->getArticleDetail($id);

        return api_output(1000, $result);

    }
   
    /**
     * 话题详情的文章列表
     */
    public function getArticleList(){
        $param = $this->request->param();
        $result = (new GrowGrassArticleService())->getArticleList($param);
        return api_output(1000, $result);

    }

    /**
     * 文章评论列表
     */
    public function getArticleReplyList(){
        $param = $this->request->param();
        $param['pageSize'] = $this->request->param('pageSize', 10 , 'intval');
        $result = (new GrowGrassArticleReplyService())->getCommentListByArticleId($param);
        return api_output(1000, ['list'=>$result,'page_size'=>$param['pageSize'] ?? 10]);

    }


    /**
     * 关注 取消关注
     */
    public function followUser(){
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        $followUid = $this->request->param('follow_uid', '0', 'intval');

        $result = (new GrowGrassFollowService())->setFollow($this->userInfo['uid'],$followUid);
        return api_output(1000, $result);
    }

    /**
     * @return \json
     * @throws \think\Exception
     * 最近使用话题
     */
    public function getMyArticleList(){
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        $list= (new GrowGrassArticleService())->getMyArticleList($this->userInfo['uid']);
        return api_output(1000, $list);
    }
    /**
     * 点赞 取消点赞
     */
    public function likeArticle(){
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        $articleId = $this->request->param('article_id', '0', 'intval');

        $result = (new GrowGrassArticleLikeService())->likeArticle($this->userInfo['uid'],$articleId);
        return api_output(1000, $result);
    }


    /**
     * 收藏 取消收藏
     */
    public function collectArticle(){
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        $articleId = $this->request->param('article_id', '0', 'intval');

        $result = (new GrowGrassArticleCollectService())->collectArticle($this->userInfo['uid'],$articleId);
        return api_output(1000, $result);
    }

    
    /**
     * 添加评论
     */
    public function addReply(){
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        $param =  $this->request->param();
        $param['reply_id'] = $this->request->param('reply_id', '0', 'intval');
        $param['content'] = $this->request->param('content', '', 'trim');

        $result = (new GrowGrassArticleReplyService())->addReply($param);
        return api_output(1000, $result);
    }

    /**
     * 点赞 取消点赞评论
     */
    public function likeReply(){
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        $replyId = $this->request->param('reply_id', '0', 'intval');

        $result = (new GrowGrassArticleReplyLikeService())->likeReply($this->userInfo['uid'],$replyId);
        return api_output(1000, $result);
    }
    
    /**
     * 发布列表
     */
    public function getArticleLists()
    {
        $name = $this->request->param('name', '', 'trim');
        $store_id = $this->request->param('store_id', '');
        $category_id = $this->request->param('category_id', '');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $status = $this->request->param('status', 1, 'intval');
        $type = $this->request->param('type', 1, 'intval');
        $mallGoodsService = new GrowGrassArticleService();
        try {
            $arr = $mallGoodsService->getArticleLists($name, $store_id, $category_id, $page, $pageSize, $status, $type);
            // 关联话题
            $categoryOne[] = array(
                'category_id' => '-1',
                'name' => '关联话题'
            );
            $resultCategory = $mallGoodsService->getRelation(1);
            $arr['categoryList'] = array_merge($categoryOne,$resultCategory);
            // 关联店铺
            $storeOne[] = array(
                'store_id' => '-1',
                'name' => '关联店铺'
            );
            $resultStore = $mallGoodsService->getRelation(2);
            $arr['storeList'] = array_merge($storeOne,$resultStore);
            // print_r($arr);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 设置为发布、不予发布、删除
     */
    public function getEditArticle()
    {
        $id = $this->request->param('id', '', 'intval');
        $type = $this->request->param('type', '', 'intval');
        try {
            $replyService = new GrowGrassArticleService();
            $result = $replyService->getEditArticle($id, $type);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return array
     * @throws \think\Exception
     *草稿箱详情发布
     */
    public function upArticle(){
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        $articleId = $this->request->param('article_id', '0', 'intval');
        if(empty($articleId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $where=[['article_id','=',$articleId]];
        $ret=(new GrowGrassArticleService())->upArticle($where);
        return $ret;
    }
    /**
     * 文章详情
     */
    public function getArticleDetails()
    {
        $id = $this->request->param('id', '', 'intval');
        try {
            $replyService = new GrowGrassArticleService();
            $result = $replyService->getArticleDetails($id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 话题详情
     */
    public function getArticleCategoryDetails()
    {
        $id = $this->request->param('id', '', 'intval');
        try {
            $replyService = new GrowGrassCategoryService();
            $result = $replyService->getArticleCategoryDetails($id);
            return api_output(0, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
