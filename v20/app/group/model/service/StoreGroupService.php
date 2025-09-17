<?php

/**
 * 店铺相关团购业务层
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/10 19:20
 */
namespace app\group\model\service;

use app\group\model\db\GroupStore;

class StoreGroupService
{
    public $groupStorerModel = null;
    public function __construct()
    {
        $this->groupStorerModel = new GroupStore();
    }

    /**
     * 店铺获取团购
     * @author: wanziyang
     * @date_time: 2020/6/10 19:37}
     * @param string|array $store_arr [1,2,3]|1,2,3
     * @return array|mixed
     */
    public function getStoreGroup($store_arr,$extro = []) {
        $db_group_store = new GroupStore();
        $where = [];
        $where[] = ['store_id','in',$store_arr];

        if ($extro) {
            $where[] = ['g.status', '=', 1];
            $where[] = ['g.end_time', '>', time()];
            if (isset($extro['keyword']) && $extro['keyword']) {
                $where[] = ['g.name', 'like', '%' . $extro['keyword'] . '%'];
            }
        }

        $page = $extro['page'] ?? 0;
        $pageSize = $extro['pageSize'] ?? 0;

        $list = $db_group_store->getStoreGroupList($where, 'a.store_id, g.group_id, g.name,g.sale_count,g.pic,g.price,g.old_price', [], $page, $pageSize);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * 店铺获取团购
     * @author: wanziyang
     * @date_time: 2020/6/10 19:37}
     * @param string|array $store_arr [1,2,3]|1,2,3
     * @return array|mixed
     */
    public function getNormalStoreGroup($store_arr) {
        $db_group_store = new GroupStore();
        $where = [];
        $where[] = ['a.store_id','in',$store_arr];
        $where[] = ['end_time','>',time()];
        $where[] = ['g.status','=',1];
        $where[] = ['g.type','=',1];
        $where[] = ['g.begin_time','<',time()];
        $list = $db_group_store->getStoreGroupList($where,'a.*, g.*');
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * 通过团购获取店铺
     * @author: wanziyang
     * @date_time: 2020/6/10 19:37}
     * @param string|array $store_arr [1,2,3]|1,2,3
     * @return array|mixed
     */
    public function getStoreByGroup($param) {
        $groupIds = $param['group_ids'] ?? 0;//团购id
        $lat = $param['lat'] ?? 0;//维度
        $lng = $param['lng'] ?? 0;//经度
        $db_group_store = new GroupStore();

        $where = [];
        $where[] = ['g.group_id','in',implode(',',$groupIds)];
        if(isset($param['status'])){
            $where[] = ['s.status','=',$param['status']];
        }

        $field = 'g.group_id,g.package_id,s.store_id,s.province_id,s.city_id,s.area_id,s.adress,s.phone,s.name,s.score,s.long,s.lat,s.open_store_marketing';
        $order = [];
        if ($lat>0 && $lng>0) {
            $field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli";

            $order['juli'] = 'ASC';
        }

        $list = $db_group_store->getStoreByGroup($where, $field , $order);

        if (!$list) {
            $list = [];
        } else {
            $list = $list->toArray();
        }
        return $list;
    }

/**
     * 获得店铺推荐
     * @author: hengtingmei
     * Date Time: 2021/05/13 
     * @param array $param
     * @return array
     */
    public function getStoreRecommend($param) {
        $groupId = $param['group_id'] ?? 0;
        if(empty($groupId)){
            throw new \think\Exception("缺少参数", 1001);    
        }

        $where = [
            ['group_id', '=', $groupId],
            ['is_rec', '=', 1]
        ];
        $list = $this->getSome($where);
        return array_column($list,'store_id');
    }

    /**
     * 设置店铺推荐
     * @author: hengtingmei
     * Date Time: 2021/05/13 
     * @param array $param
     * @return array
     */
    public function setStoreRecommend($param) {
        
        $storeIdArr = $param['store_id_arr'] ?? [];
        $groupId = $param['group_id'] ?? 0;
        if(empty($groupId)){
            throw new \think\Exception("缺少参数", 1001);    
        }
        // 保存添加的推荐
        $where = [
            ['store_id', 'in', implode(',', $storeIdArr)],
            ['group_id', '=', $groupId]
        ];
        $saveData = [
            'is_rec' => 1
        ];
        $this->updateThis($where, $saveData);

        // 删除推荐
        $where = [
            ['store_id', 'not in', implode(',', $storeIdArr)],
            ['group_id', '=', $groupId]
        ];
        $saveData = [
            'is_rec' => 0
        ];
        $this->updateThis($where, $saveData);


        return true;
    }
    

    /**
     * 获取一条数据
     * @author: hengtingmei
     * @date_time: 2021/5/11
     * @param array $where
     * @return array
     */
    public function getOne($where) {
        
        $count = $this->groupStorerModel->getOne($where);
        if(empty($count)){
            return [];
        }
        return $count;
    }
    
    /**
     * 获取多条数据
     * @author: hengtingmei
     * @date_time: 2021/5/13
     * @param array $where
     * @param string $field
     * @param array $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0) {
        
        $result = $this->groupStorerModel->getSome($where,$field,$order,$page,$limit);
        if(empty($result)){
            return [];
        }
        return $result->toArray();
    }

    /**
     * 获取总数
     * @author: hengtingmei
     * @date_time: 2021/5/11
     * @param array $where
     * @return int
     */
    public function getCount($where) {
        
        $count = $this->groupStorerModel->getCount($where);
        if(empty($count)){
            return 0;
        }
        return $count;
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
            $result = $this->groupStorerModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }

    /**
     * 批量更新数据
     * @param $where array 
     * @param $data array 
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        $result = $this->groupStorerModel->updateThis($where, $data);
        
        return $result;
    }


    /**
     * 删除数据
     * @param array $where
     * @return bool
     */
    public function del($where){
        if(empty($where)){
            return false;
        }
        $res = $this->groupStorerModel->where($where)->delete();

        return $res;

    }
}