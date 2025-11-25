<?php
/**
 * 种草话题controller
 * Author: hengtingmei
 * Date Time: 2021/05/15
 */

namespace app\grow_grass\controller\api;

use app\common\model\service\diypage\DiypageModelService;
use app\grow_grass\model\db\GrowGrassArticle;
use app\grow_grass\model\service\GrowGrassArticleService;
use app\grow_grass\model\service\GrowGrassCategoryService;
use app\merchant\model\service\MerchantStoreService;

class CategoryController extends ApiBaseController
{
    /**
     * 话题广场分类
     * 汪晨
     * 2021.5.19
     */
    public function topicSquareCategory(){
        try {
            $arr = (new GrowGrassCategoryService())->topicSquareCategory();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 话题广场列表
     * 汪晨
     * 2021.5.19
     */
    public function topicSquareList(){
        $cat_id = $this->request->param('cat_id',0,'trim');
        if(empty($cat_id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        try {
            $arr = (new GrowGrassCategoryService())->topicSquareList($cat_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 话题列表
     */
    public function getCategoryList(){
        $page = $this->request->param('page',1,'trim');
        $pageSize = $this->request->param('pageSize',10,'trim');
        $cat_id = $this->request->param('cat_id',0,'trim');
        $status = $this->request->param('status',0,'trim');
        $content = $this->request->param('content','','trim');
        $where = [['is_del','=',0]];
        // 关联分类
        if($cat_id > -1){
            $where[] = ['cat_id', '=', $cat_id];
        }
        // 状态
        if($status > -1){
            $where[] = ['status', '=', $status];
        }
        if($content != ''){
            $where[] = ['name', 'like', '%'.$content.'%'];
        }
        try {
            $arr = (new GrowGrassCategoryService())->getCategoryList($where,$page,$pageSize);
            foreach($arr['list'] as &$val){
                $val['reply_num'] = (new GrowGrassCategoryService())->getReplyCount($val['category_id']);
            }
            // 获取分类
            $catOneList[] = array(
                'cat_id' => '-1',
                'cat_name' => '关联分类'
            );
            $catList = [];
            $catList = (new GrowGrassCategoryService())->topicSquareCategory();
            $arr['catList'] = array_merge($catOneList,$catList);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 保存话题
     */
    public function getCategoryEdit()
    {
        
        $growGrassCategoryService = new GrowGrassCategoryService();

        $param['category_id'] = $this->request->param("id", "0", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['description'] = $this->request->param("description", "", "trim");
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        $param['status'] = $this->request->param("status", "1", "intval");
        $param['sort'] = $this->request->param("sort", "0", "intval");
        $param['last_time'] = time();   
        $img = $this->request->param("img");
        $param['img'] = '';
        if(!empty($img)){
            foreach($img as $k=>$v){
                if($k==0){
                    $param['img'] .= $v;
                }else{
                    $param['img'] .= ';'.$v;
                }
            }
        }
        // 获得列表
        $list = $growGrassCategoryService->getCategoryEdit($param);

        return api_output(0, $list);
    }  

    /**
     * 获得一条话题
     */
    public function getOneDetail(){
        $id = $this->request->param('category_id', '0', 'intval');
        $result = (new GrowGrassCategoryService())->getOneDetail($id);
        return api_output(1000, $result);

    }

    /**
     * 话题分类
     */
    public function getCategoryClass($type=0){
        $result = (new GrowGrassCategoryService())->topicSquareCategory();
        if($type){
            $catOne[] = array(
                'cat_id' => '0',
                'cat_name' => '关联分类'
            );
            $arr = array_merge($catOne,$result);
            return api_output(1000, $arr);
        }else{
            return api_output(1000, $result);
        }
    }

    /**
     * 话题排序
     */
    public function getCategorySort(){
        $sort = $this->request->param('sort', '0', 'intval');
        $id = $this->request->param('id', '0', 'intval');
        $result = (new GrowGrassCategoryService())->getCategorySort($id, $sort);
        return api_output(1000, $result);
    }

    /**
     * 删除话题
     */
    public function getCategoryDel(){
        $id = $this->request->param('id', '0', 'intval');
        // 判断话题下是否有文章
        $where = ['category_id'=>$id,'is_del'=>0,'is_system_del'=>0, 'is_manuscript' => 0];
        $articleCount = (new GrowGrassArticleService())->getCount($where);
        if($articleCount > 0){
            throw new \think\Exception(L_('删除失败！话题内有发布'), 1003);
        }

        $result = (new GrowGrassCategoryService())->getCategoryDel($id);
        return api_output(1000, $result);

    }

    /**
     * 获得话题详情
     */
    public function getDetail(){
        $id = $this->request->param('category_id', '0', 'intval');
        $result = (new GrowGrassCategoryService())->getDetail($id);
        return api_output(1000, $result);

    }
   
    /**
     * 话题详情的文章列表
     */
    public function getArticleList(){
        $param = $this->request->param();
        $result = (new GrowGrassArticleService())->getArticleList($param,1);//只要发布的
        return api_output(1000, $result);

    }

    /**
     * 话题详情的店铺列表
     */
    public function getCategoryStoreList(){
        $param['page'] = $this->request->param('page', '1', 'intval');
        $param['user_long'] = $this->request->param('user_lng', '', 'trim');
        $param['user_lat'] = $this->request->param('user_lat', '', 'trim');
        $categoryId = $this->request->param('category_id', '0', 'intval');
        $where = 'a.category_id='.$categoryId;
        //$where = 'a.category_id', '=', $categoryId;
        $result['list'] = (new MerchantStoreService())->getGrowGrassStoreList($where, $param['user_long'], $param['user_lat'], $param['page'], 10);;
        $result['page_size'] = 10;
        return api_output(1000, $result);

    }

    /**
     * 获得话题详情
     */
    public function getCategoryDetail(){
        $id = $this->request->param('category_id', '0', 'intval');
        $result = (new GrowGrassCategoryService())->getCategoryDetail($id);
        return api_output(1000, $result);

    }
}
