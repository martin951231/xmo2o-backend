<?php
/**
 * MallMerchantStoreDecorate.php
 * 店铺装修model
 * Create on 2020/9/27 10:07
 * Created by zhumengqun
 */
namespace app\mall\model\db;
use think\Model;
class MallMerchantStoreDecorate extends Model{
    /**
     * 添加装修页
     * @param $data_page
     * @return int|string
     */
    public function addPage($data_page){
        $result = $this->insertGetId($data_page);
        return $result;
    }

    /**
     * 更新装修页
     * @param $pageWhere
     * @param $data_page
     */
    public function updatePage($pageWhere,$data_page){
        $result = $this->where($pageWhere)->update($data_page);
        return $result;
    }

    /**
     * 获取装修页信息
     * @param $store_id
     * @return array
     */
    public function getPage($store_id){
        $arr = $this->field(true)->where(['store_id'=>$store_id])->find();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    public function getStoreDecorate($where){
        $arr = $this->where($where)->find();
        if(!empty(($arr))){
            return $arr->toArray();
        }else{
            return [];
        }
    }

}