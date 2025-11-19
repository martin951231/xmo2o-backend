<?php
/**
 * 餐饮商品分类service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/26 15:20
 */

namespace app\foodshop\model\service\store;
use app\foodshop\model\db\MealStoreCategory as MealStoreCategoryModel;
class MealStoreCategoryService {
    public $mealStoreCategoryModel = null;
    public $showMethod = [];
    public $catStatus = [];
    public function __construct()
    {
        $this->mealStoreCategoryModel = new MealStoreCategoryModel();

        $this->showMethod = [
            '0' => L_('不显示'),
            '1' => L_('正常显示'),
            '2' => L_('靠后显示'),
        ];
        $this->catStatus = [
            '0' => L_('关闭'),
            '1' => L_('开启'),
        ];
    }

    /**
     * 添加编辑分类
     * @param $param
     * @return array
     */
    public function editSort($param) {
        $catId = isset($param['cat_id']) ? $param['cat_id'] : '0';
        $catFid = isset($param['cat_fid']) ? $param['cat_fid'] : '0';
        if($catId){
            //编辑
            $where = [
                'cat_id' =>$catId
            ];
            $res = $this->updateSort($where, $param);
        }else{
            // 新增
            $res = $this->add($param);
        }
        
        if($res===false){
            throw new \think\Exception("操作失败请重试");
            
        }
        return true; 
    } 
    
    /**
     * 删除分类
     * @param $param
     * @return array
     */
    public function delSort($param) {
        $catId = isset($param['cat_id']) ? $param['cat_id'] : '0';
        if(!$catId){
            throw new \think\Exception("参数错误");
        }

        $cate = $this->getCategoryByCatId($catId);
        if(!$cate){
            throw new \think\Exception("分类已删除或不存在");
        }

        $subCate = $this->getCategoryByCatFid($catId);
        if($subCate){
            throw new \think\Exception("该分类下有子分类，先清空子分类，再删除该分类");
        }
        
        $where['cat_id'] = $catId;
        $res = $this->del($where);
        if(!$res){
            throw new \think\Exception("删除失败请重试");
        }
        return true; 
    } 
    
    /**
    * 获得添加页面所需数据
    * @param $where
    * @return array
    */
   public function getEditInfo($param) {
       $catId = isset($param['cat_id']) ? $param['cat_id'] : '0';

       $returnArr = [];

       // 获得父级分类列表
       $where['cat_fid'] = 0;
       $returnArr['categoryList'] = $this->getCategoryByCondition($where);
       

        $returnArr['showMethod'] = [
            '0' => '不营业不显示',
            '1' => '不营业正常显示',
            '2' => '不营业靠后显示',
        ];

        $returnArr['detail'] = [];
        if($catId){
            $returnArr['detail'] = $this->getCategoryByCatId($catId);
        }
       $returnArr['detail'] = $returnArr['detail'] ? $returnArr['detail'] : '';
       return $returnArr; 
   }

    /**
     * 获得树状分类
     * @param $where
     * @return array
     */
    public function getCategoryTree($param) {
        
        $mealStoreCategory = $this->getCategoryByCondition([], []);
        if(!$mealStoreCategory) {
            return [];
        }

        $returnArr = [];
        
        // 以分类id为键值的分类数组
        $tmpMap = array();
        // 所有父分类id
        $idsArray = array();
        foreach($mealStoreCategory as $cate){
            $idsArray[$cate['cat_id']] = $cate['cat_fid'];
            $tempCate = [
                'key' => $cate['cat_id'],
                'cat_id' => $cate['cat_id'],
                'cat_fid' => $cate['cat_fid'],
                'cat_name' => $cate['cat_name'],
                'show_method' => $this->showMethod[$cate['show_method']],
                // 'cat_status' => $this->catStatus[$cate['cat_status']],
                'cat_status' => $cate['cat_status'],
            ];
            $tmpMap[$cate['cat_id']] = $tempCate;
        }

        $returnArr = array();
        foreach ($mealStoreCategory as $cate) {
            if ($cate['cat_fid'] && isset($tmpMap[$cate['cat_fid']])) {
                $tmpMap[$cate['cat_fid']]['children'][$cate['cat_id']] = &$tmpMap[$cate['cat_id']];
            } elseif(!$cate['cat_fid']) {
                $returnArr[$cate['cat_id']] = &$tmpMap[$cate['cat_id']];
            }
        }

        foreach ($returnArr as &$cate) {
            isset($cate['children']) && $cate['children'] = array_values($cate['children']);
        }

        return array_values($returnArr); 
    }

    /**
     * 根据ID返回分类
     * @param $where
     * @return array
     */
    public function getCategoryByCatId($catId) {
        if(empty($catId)){
           return [];
        }

        $mealStoreCategory = $this->mealStoreCategoryModel->getCategoryByCatId($catId);
        if(!$mealStoreCategory) {
            return [];
        }
        
        return $mealStoreCategory->toArray(); 
    }

    
    /**
     * 根据父ID返回分类列表
     * @param $catFid
     * @return array
     */
    public function getCategoryByCatFid($catFid) {
        if(empty($catFid)){
           return [];
        }

        $mealStoreCategoryList = $this->mealStoreCategoryModel->getCategoryByCatFid($catFid);
        if(!$mealStoreCategoryList) {
            return [];
        }
        
        return $mealStoreCategoryList->toArray(); 
    }

    /**
     * 根据条件返回分类列表
     * @param $where
     * @return array
     */
    public function getCategoryByCondition($where, $order = []) {
        if(empty($order)){
            $order['cat_sort'] = 'DESC';
            $order['cat_id'] = 'DESC';
        }
        
        $mealStoreCategoryList = $this->mealStoreCategoryModel->getCategoryByCondition($where, $order);
        if(!$mealStoreCategoryList) {
            return [];
        }
        
        return $mealStoreCategoryList->toArray(); 
    }

    
	/*得到列表所有分类*/
	public function getAllCategory($isWap = 0){
        // 获取所有显示的分类
        $where[] = ['cat_status' , '=' , '1'];
        $tmpGroupCategory = $this->getCategoryByCondition($where);
		$groupCategory = array();
		$tmpCategory = array();//顶级
		foreach($tmpGroupCategory as $key=>$value){
			if(empty($value['cat_fid'])){
				$tmpCategory[$value['cat_id']] = $key;
				$value['cat_count'] = 0;
				$groupCategory[$key] = $value;
				unset($tmpGroupCategory[$key]);
			}
        }
        
		foreach($tmpGroupCategory as $key=>$value){
		    if(isset($tmpCategory[$value['cat_fid']])){
                $groupCategory[$tmpCategory[$value['cat_fid']]]['cat_count'] += 1;
                $groupCategory[$tmpCategory[$value['cat_fid']]]['category_list'][$key] = $value;
            }
		}

		foreach($groupCategory as $key=>$value){
			if(empty($value['cat_id'])){
				unset($groupCategory[$key]);
			}
			if(isset($groupCategory[$key]['category_list'])){
                $groupCategory[$key]['category_list'] = array_values($value['category_list']);
            }else{
                $groupCategory[$key]['category_list'] = [];
            }
            if($isWap){
                $all = [];
                $all[] = [
                    'cat_fid' => $value['cat_id'],
                    'cat_id' => $value['cat_id'],
                    'cat_name' => $value['cat_name'].'(全部)'
                ];
                $groupCategory[$key]['category_list'] = array_merge($all, $groupCategory[$key]['category_list']);
            }
        }
        
        $groupCategory = array_values($groupCategory);
		return $groupCategory;

    }
    
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        
        $result = $this->mealStoreCategoryModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->mealStoreCategoryModel->id;
    }

    
    
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function updateSort($where, $data){
        
        $result = $this->mealStoreCategoryModel->where($where)->update($data);
        if($result===false) {
            return false;
        }

        return $result;
    }

    /**
     * 删除一条记录
     * @param $data array 数据
     * @return array
     */
    public function del($where){
        if(!$where){
            return false;
        }
        $result = $this->mealStoreCategoryModel->where($where)->delete();
        if(!$result) {
            return false;
        }

        return $result;
    }
}