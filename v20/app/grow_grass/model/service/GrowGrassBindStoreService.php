<?php
/**
 * 种草绑定店铺
 * Author: hengtingmei
 * Date Time: 2021/5/17 11:12
 */

namespace app\grow_grass\model\service;

use app\common\model\service\AreaService;
use app\grow_grass\model\db\GrowGrassBindStore;
use app\merchant\model\service\store\MerchantCategoryService;
use app\merchant\model\service\storeImageService;
use map\longLat;

class GrowGrassBindStoreService {
    public $growGrassBindStoreModel = null;
    public function __construct()
    {
        $this->growGrassBindStoreModel = new GrowGrassBindStore();
    }

    /**
     *获取文章关联的店铺列表
     * @param int $articleId 
     * @param string $lng 
     * @param string $lat 
     * @return array
     */
    public function getBindStoreList($articleId, $lng = '', $lat = ''){
        $where['b.article_id'] = $articleId;
        $where['b.is_del'] = 0;
        $list = $this->getSome($where, ['b.id'=>'ASC']);
        $returnArr = [];
        foreach($list as $store){
            $returnArr[] = $this->formatStore($store, $lng, $lat); 
        }
        return $returnArr;
    } 

    /**
    * 处理返回给前端的数据
    * @param $store array 
    * @return array
    */
    public function formatStore($store, $lng = '', $lat = ''){
        $tempStore = [];
        if(isset($store['article_id'])){
            $tempStore['article_id'] = $store['article_id'];
        }
        $tempStore['store_id'] = $store['store_id'];
        $tempStore['url'] = cfg('site_url')."/packapp/platn/pages/store/v1/home/index?store_id=".$store['store_id'];
        $tempStore['name'] = $store['name'];
        //评分
        $tempStore['score'] = $store['score']==0 ? 5.0 : $store['score'];
        // 店铺图片
        $images = (new storeImageService())->getAllImageByPath($store['pic_info']);
        $tempStore['image'] = $images ? thumb(array_shift($images),180) : '';

        //距离
        if($lng>0 && $lat>0){
            $location2 = (new longLat())->gpsToBaidu($store['lat'], $store['long']);//转换腾讯坐标到百度坐标
            $jl = get_distance($location2['lat'], $location2['lng'], $lat, $lng);
            $tempStore['range'] = get_range($jl);
        }else{
            $tempStore['range'] = '';
        }

        // 店铺分类
        $tempStore['cate_name'] = '';
        if(isset($store['cat_fid']) && $store['cat_fid']){
            $cate = (new MerchantCategoryService())->getOne(['cat_id'=>$store['cat_fid']]);
            $tempStore['cate_name'] = $cate['cat_name'] ?? '';
        }
        if(isset($store['cat_id']) && $store['cat_id']){
            $cate = (new MerchantCategoryService())->getOne(['cat_id'=>$store['cat_id']]);
           $cate && $tempStore['cate_name'] = $tempStore['cate_name'] ? $tempStore['cate_name'].'/'.$cate['cat_name'] : $cate['cat_name'];
        }

        // 商圈
        $tempStore['area_name'] = "";
        if(isset($store['cat_id']) && $store['circle_id']){
            $area = (new AreaService())->getAreaByAreaId($store['circle_id']);
            $tempStore['area_name'] = $area['area_name'];
        }

        return $tempStore;
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
           $result = $this->growGrassBindStoreModel->insertAll($data);
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
        $result = $this->growGrassBindStoreModel->updateThis($where, $data);
        return $result;
    }

    /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where){
        $result = $this->growGrassBindStoreModel->getOne($where);
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
    public function getSome($where = [], $order=true,$page=0,$limit=0){
        $start = ($page-1)*$limit;
        $result = $this->growGrassBindStoreModel->getSome($where, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->growGrassBindStoreModel->getCount($where);
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

        $result = $this->growGrassBindStoreModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}