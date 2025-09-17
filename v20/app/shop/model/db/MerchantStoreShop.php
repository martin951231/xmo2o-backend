<?php
/**
 * 外卖店铺model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 09:35
 */

namespace app\shop\model\db;
use think\Model;
use think\facade\Env;
class MerchantStoreShop extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据条件获取店铺列表
     * @param $where
     * @param $order 排序
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    // public function getStoreListByCondition($where,$order='') {
    //    if(empty($where)) {
    //         return false;
    //     }

    //     $result = $this->where($where)->order($order)->select();
    //     return $result;
    // }

    // 获取店铺列表
    public function getShopList($store, $fields='*'){
        $where = '';
        foreach($store as $key=>$v){
            if(empty($key)){
                $where = 'store_id = '.$v['store_id'];
            }else{
                $where = $where.' or store_id = '.$v['store_id'];
            }
        }

        $result = $this->whereRaw($where)->field($fields)->select()->toArray();

        return $result;
    }

    
    /**
     * 根据店铺id获取店铺
     * @param $storeId
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreByStoreId($storeId) {
        if(empty($storeId)) {
            return false;
        }
        
        $where = [
            'store_id' => $storeId
        ];

        $result = $this->where($where)->find();
        return $result;
    }
}