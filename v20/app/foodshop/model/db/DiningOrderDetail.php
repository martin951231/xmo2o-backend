<?php
/**
 * 餐饮订单详情model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:28
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningOrderDetail extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    
    /**
     *根据订单id获取商品列表
     * @param $orderId int  
     * @return array
     */
    public function getOrderDetailByOrderId($orderId){
        if(empty($orderId)){
            return false;
        }

        $where = [
            'order_id' => $orderId
        ];
        $result = $this->where($where)->select();
        return $result;
    }    
    
    /**
    *根据条件获取商品列表
    * @param $where array  
    * @return array
    */
   public function getOrderDetailByCondition($where){
       if(empty($where)){
           return false;
       }

       $result = $this->where($where)->select();
       return $result;
   }
   
    
    /**
     *获取一条信息
     * @param $where 
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }
        
        $result = $this->where($where)->order($order)->find();
        return $result;
    }

    public function getGoodsDetail($orderId)
    {
        return self::where('order_id', $orderId)
            ->field('goods_id, name as goods_name, price, num as goods_num')
            ->select();
    }
}