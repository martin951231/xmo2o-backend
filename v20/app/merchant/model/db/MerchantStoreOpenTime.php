<?php
/**
 * 店铺营业时间model
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/23 17:24
 */

namespace app\merchant\model\db;
use think\Model;
class MerchantStoreOpenTime extends Model {
    
    use \app\common\model\db\db_trait\CommonFunc;

    public function getList($where = [], $order = [], $field = true){
        if(is_array($order)){
            $result = $this->field($field)->where($where)->order($order)->select();
        }else{
            // 自定义排序
            $result = $this->field($field)->where($where)->orderRaw($order)->select();
        }
    	return $result;
    }

}