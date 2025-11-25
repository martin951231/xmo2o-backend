<?php
/**
 * 种草话题service
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:12
 */

namespace app\grow_grass\model\service;

use app\grow_grass\model\db\GrowGrassArticle;
use app\grow_grass\model\db\GrowGrassCategory;
use app\common\model\db\MerchantCategory;

class GrowGrassCategoryService {
    public $growGrassCategoryModel = null;
    public function __construct()
    {
        $this->growGrassCategoryModel = new GrowGrassCategory();
    }

    /**
     * 话题广场分类
     * 汪晨
     * 2021.5.19
    */
    public function topicSquareCategory(){
        // 获取分类
        $return = (new MerchantCategory())->field('cat_id,cat_name')->where(array('cat_status'=>1,'cat_fid'=>0))->order('cat_sort DESC')->select()->toArray();
        return $return;
    }

    /**
     * 话题广场列表
     * 汪晨
     * 2021.5.19
    */
    public function topicSquareList($cat_id){
        // 根据分类ID获取话题列表
        $return = (new GrowGrassCategory())->where(array('cat_id'=>$cat_id,'is_del'=>0))->order('join_num DESC, views_num DESC')->select()->toArray();

        return $return;
    }

    
    /**
     * 根据分类ID获取话题评论数
    */
    public function getReplyCount($cat_id){
        $where = [
            'category_id' => $cat_id,
            'is_del' => 0
        ];
        $return = (new GrowGrassArticleService())->getCountSum($where);

        return $return;
    }


    /**
    * 获取种草详情
    * @param $where array 条件
    * @return array
    */
    public function getDetail($id){
        if(empty($id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询话题详情
        $where = [
            'category_id' => $id,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('话题不存在或已删除'), 1003);
        }

        $returnArr = $this->formatDataDetail($detail);
        
        return $returnArr;
    }  

    /**
     * @param $categoryId
     * @return bool
     * 增加话题浏览数
     */
    public function addViewsNum($categoryId)
    {
        $where = [['category_id', '=', $categoryId]];
        $res = $this->growGrassCategoryModel->where($where)->inc('views_num')->update();
        return $res;
    }

    /**
     * @param $categoryId
     * @return bool
     * 增加话题发布的文章数
     */
    public function addArticleNum($categoryId)
    {
        $where = [['category_id', '=', $categoryId]];
        $res = $this->growGrassCategoryModel->where($where)->inc('article_num')->update();
        return $res;
    }

    /**
     * @param $categoryId
     * @param $uid
     * @return bool
     * 增加参与人数
     */
    public function addJoinNum($categoryId,$uid)
    {
        $where = [
            ['category_id', '=', $categoryId],
            ['uid', '=', $uid],
        ];
        
        // 查看该用户在此话题下发布的文章数
        $count = (new GrowGrassArticleService())->getCount($where);

        if($count == 1){// 该话题下第一次发布文章
            // 更新参与人数
            $where = [
                ['category_id', '=', $categoryId],
            ];
            $this->growGrassCategoryModel->where($where)->inc('join_num')->update();
        }
        return true;
    }
    
    /**
    * 获得用户选择话题列表
    * @param $detail array 话题详情列表
    * @return array
    */
    public function getTopicList($param){
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $where = [];
        if(isset($param['keyword']) && $param['keyword']){
            $where[] = ['name','like', '%'.$param['keyword'].'%'];
        }
        if(isset($param['cat_id']) && $param['cat_id']){
            $where[] = ['cat_id','=', $param['cat_id']];
        }
        $where[] = ['is_del', '=', '0'];
        $returnArr = $this->getCategoryList($where, $page, $pageSize);
        if($returnArr['list']){
            foreach($returnArr['list'] as &$value){
                $value = $this->formatDataDetail($value);
            }
        }
        return $returnArr;
    }

    /**
    * 重组返回给前端的数据
    * @param $detail array 话题详情列表
    * @return array
    */
    public function formatDataDetail($detail){
        $returnArr = [
            'category_id' => $detail['category_id'],//话题id
            'cat_id' => $detail['cat_id'],// 绑定的分类id
            'name' => $detail['name'],// 话题名
            'description' => $detail['description'],// 描述
            'img' => replace_file_domain($detail['img']),// 图片
            'join_num' => $detail['join_num'],// 参与人数
            'views_num' => $detail['views_num'],// 浏览人数
        ];
        return $returnArr;
    }

    /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where){
        $result = $this->growGrassCategoryModel->getOne($where);
        if(!$result) {
            return [];
        }
        return $result;
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $start = ($page-1)*$limit;
        $result = $this->growGrassCategoryModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->growGrassCategoryModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->growGrassCategoryModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return mixed
     * 首页一级分类
     */
    public function indexCategoryList($where,$field,$order){
        $list=(new GrowGrassCategory())->indexCategoryList($where,$field,$order);
        return $list;
    }
    
    /**
     *获取话题列表
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getCategoryList($where = [], $page=0,$pageSize=0){
        // $page = ($page-1)*$pageSize;
        $list = $this->growGrassCategoryModel->getCategoryList($where, $page, $pageSize);
        if (!empty($list)) {
            return $list;
        } else {
            return [];
        }
    }

    /**
     * 保存话题
     * @param $param
     * @return array
     */
    public function getCategoryEdit($param) {
        $id = isset($param['category_id']) ? $param['category_id'] : '0';
        if(empty($param['name'])){
            throw new \think\Exception("请输入搜索词名称！",1005);
        }
        $params = array(
            'name' => $param['name'],
            'description' => $param['description'],
            'cat_id' => $param['cat_id'],
            'status' => $param['status'],
            'sort' => $param['sort'],
            'img' => $param['img'],
            'last_time' => $param['last_time'],
        );
        if($id>0){
            //编辑
            $where = [
                'category_id' =>$id
            ];
            $res = $this->growGrassCategoryModel->getCategoryEdit($where, $params);
        }else{
            // 新增
			$params['add_time'] = time();
            $res = $this->growGrassCategoryModel->getCategoryAdd($params);
        }
        
        if($res===false){
            throw new \think\Exception("操作失败请重试",1005);
            
        }
        return true; 
    } 

    
    /**
    * 获取一条话题
    * @param $where array 条件
    * @return array
    */
    public function getOneDetail($id){
        if(empty($id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询话题详情
        $where = [
            'category_id' => $id,

        ];
        $returnArr = $this->growGrassCategoryModel->getOneDetail($where);
        if(empty($returnArr)){
            return [];
        }
   
        return $returnArr;
    }

    /**
     * 文字话题详情
     */
    public function getArticleCategoryDetails($id)
    {
        $list = $this->growGrassCategoryModel->getArticleCategoryDetails($id);
        return $list;
    }

    /**
     * 话题详情
     */
    public function getCategoryDetail($id)
    {
        $list = $this->growGrassCategoryModel->getCategoryDetail($id);
        return $list;
    }

    /**
     * 话题排序
     */
    public function getCategorySort($id, $sort)
    {
        $list = $this->growGrassCategoryModel->getCategorySort($id, $sort);
        return $list;
    }

    /**
     * 话题删除
     */
    public function getCategoryDel($id)
    {
        $list = $this->growGrassCategoryModel->getCategoryDel($id);
        return $list;
    }
}