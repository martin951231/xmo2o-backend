<?php
/**
 * 餐饮商品分类service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/26 15:20
 */

namespace app\foodshop\model\service\store;
use app\foodshop\model\db\SearchHotFoodshop as SearchHotFoodshopModel;
class SearchHotFoodshopService {
    public $searchHotFoodshop = null;
    public function __construct()
    {
        $this->searchHotFoodshop = new SearchHotFoodshopModel();
    }

    /**
     * 获取热门搜索词列表
     * @return array
     */
	public function getAllSearchList(){
        $order['sort'] = 'DESC';
        $order['id'] = 'ASC';
        $searchList = $this->searchHotFoodshop->getAllSearchList($order);
        if(!$searchList) {
            return [];
        }
        return $searchList->toArray(); 

    }
    
    /**
     * 获取热门搜索词列表
     * @return array
     */
	public function getWapSearchList(){
        $searchList = $this->getAllSearchList();
        if(!$searchList) {
            return [];
        }
        return $searchList;
    }
    
    /**
     * 获取热门搜索词列表
     * @param $param array 参数
     * @return array
     */
	public function getSearchHotList($param){
        $page = $param['page'];
        $pageSize = $param['pageSize'];
        
        $searchList = $this->getSearchHotListBycondition([], [], $page, $pageSize);
        if(!$searchList) {
            return [];
        }

        foreach($searchList as &$word){
            $word['key'] = $word['id'];
        }
        $count = $this->getCount([]);
        $returnArr['list'] = $searchList;
        $returnArr['total'] = $count;
        return $returnArr; 

    }

    /**
     * 获取热门搜索词详情
     * @param $param array 参数
     * @return array
     */
	public function getSearchHotDetail($param){
        $id = isset($param['id']) ? $param['id'] : '0';

        // 详情
        $where = ['id' => $id];
        $detail = $this->getOne($where);
        if(!$detail){
            return []; 
        }
         
        return $detail; 

    }
        
    /**
     * 保存排序
     * @param $param array 
     * @return array
     */
    public function saveSort($param) {
		$id = isset($param['id']) ? $param['id'] : 0;
		$sort = isset($param['sort']) ? $param['sort'] : 0;

		$where = ['id' => $id];
		$data = ['sort' => $sort];
		$res = $this->updateSearchHot($where, $data);

		if(!$res){
			throw new \think\Exception("更新失败");
		}
        return $res;
    }

    /**
     * 保存热门搜索词
     * @param $param
     * @return array
     */
    public function saveSearchHot($param) {
        $id = isset($param['id']) ? $param['id'] : '0';
        if(empty($param['name'])){
            throw new \think\Exception("请输入搜索词名称！",1005);
        }
        if($id>0){
            //编辑
            $where = [
                'id' =>$id
            ];
            $res = $this->updateSearchHot($where, $param);
        }else{
            // 新增
            $res = $this->add($param);
        }
        
        if($res===false){
            throw new \think\Exception("操作失败请重试",1005);
            
        }
        return true; 
    } 
    
    /**
     * 删除分类
     * @param $param
     * @return array
     */
    public function delSearchHot($param) {
        $id = isset($param['id']) ? $param['id'] : '0';
        if(!$id){
            throw new \think\Exception("参数错误",1001);
        }
       
        $where = ['id' => $id];
        $detail = $this->getOne($where);
        if(!$detail){
            throw new \think\Exception("已删除或不存在",1003);
        }
        
        $where['id'] = $id;
        $res = $this->del($where);
        if(!$res){
            throw new \think\Exception("删除失败请重试",1005);
        }
        return true; 
    } 

    /**
     * 获取热门搜索词列表
     * @param $where array 条件
     * @param $order array 排序
     * @param $page array 页码
     * @param $pageSize array 每页显示数量
     * @return array
     */
	public function getSearchHotListBycondition($where, $order=[], $page = '1', $pageSize= '10'){
        if(!$order){
            $order['sort'] = 'DESC';
            $order['id'] = 'DESC';
        }
        $searchList = $this->searchHotFoodshop->getSearchHotListBycondition($where, $order, $page , $pageSize);
        if(!$searchList) {
            return [];
        }
         
        return $searchList->toArray(); 

    } 
        
    /**
     * 获取热门搜索词总数
     * @param $where array 条件
     * @return array
     */
	public function getCount($where){

        $count = $this->searchHotFoodshop->getCount($where);
        if(!$count) {
            return 0;
        }
         
        return $count; 

    } 

    /**
     * 获得一条记录
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        
        $result = $this->searchHotFoodshop->getOne($where);
        if(!$result) {
            return false;
        }

        return $result->toArray();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $is_hot['add_time'] = time();
        $result = $this->searchHotFoodshop->save($data);
        if(!$result) {
            return false;
        }

        return $this->searchHotFoodshop->id;
    }

    
    
    /**
     * 编辑记录
     * @param $data array 数据
     * @return array
     */
    public function updateSearchHot($where, $data){
        
        $result = $this->searchHotFoodshop->where($where)->update($data);
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
        $result = $this->searchHotFoodshop->where($where)->delete();
        if(!$result) {
            return false;
        }

        return $result;
    }




    

}