<?php
/**
 * MallMerchantStoreDecorateField.php
 * 店铺装修field model
 * Create on 2020/9/27 10:12
 * Created by zhumengqun
 */
namespace app\mall\model\db;
use think\model;
class MallMerchantStoreDecorateField extends Model{
    /**
     * 删除装修页
     * @param $pageFieldWhere
     * @return bool
     */
    public function delFieldPage($pageFieldWhere){
        $result = $this->where($pageFieldWhere)->delete();
        return $result;
    }

    /**
     * 添加装修页
     * @param $data_page_field
     * @return int|string
     */
    public function addFieldPage($data_page_field){
        $result = $this->insert($data_page_field);
        return $result;
    }

    /**
     * 获取装修页信息
     * @param $fieldWhere
     * @return array
     */
    public function getFieldPage($fieldWhere){
        $arr = $this->field(true)->where($fieldWhere)->order('field_id ASC')->select();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }
}