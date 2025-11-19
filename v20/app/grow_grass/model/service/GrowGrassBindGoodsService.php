<?php
/**
 * 种草绑定店铺
 * Author: hengtingmei
 * Date Time: 2021/5/17 11:12
 */

namespace app\grow_grass\model\service;
use app\grow_grass\model\db\GrowGrassBindGoods;

class GrowGrassBindGoodsService {
    public $growGrassBindGoodsModel = null;
    public function __construct()
    {
        $this->growGrassBindGoodsModel = new GrowGrassBindGoods();
    }

    /**
     *获取文章关联的商品列表
     * @param int $articleId 
     * @return array
     */
    public function getBindGoodsList($articleId){
        $where['article_id'] = $articleId;
        $where['is_del'] = 0;
        $list = $this->getSome($where, true, ['id'=>'ASC']);
      
        return $list;
    } 
    
    /**
    *批量插入数据
    * @param $data array 
    * @return array
    */
   public function addAll($data){
       if(empty($data)){
           return false;
       }

       try {
           // insertAll方法添加数据成功返回添加成功的条数
           $result = $this->growGrassBindGoodsModel->insertAll($data);
       } catch (\Exception $e) {
           return false;
       }
       
       return $result;
   }

    /**
    * 删除
    * @param $where array 条件
    * @return array
    */
    public function del($where){
        if(empty($where)){
            return false;
        }
        $data = ['is_del' => 1];
        $result = $this->growGrassBindGoodsModel->updateThis($where, $data);
        return $result;
    }

    /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where){
        $result = $this->growGrassBindGoodsModel->getOne($where);
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
        $result = $this->growGrassBindGoodsModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->growGrassBindGoodsModel->getCount($where);
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

        $result = $this->growGrassBindGoodsModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}