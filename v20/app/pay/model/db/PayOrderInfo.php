<?php
/**
 * 支付信息表
 * Created by lumin.
 */

namespace app\pay\model\db;

use think\Model;
use think\facade\Db;
class PayOrderInfo extends Model{
    use \app\common\model\db\db_trait\CommonFunc;
    public function add($data){
    	return $this->json(['pay_config'])->insert($data);
    }
    public function addItemData($data){
        return $this->insertGetId($data);
    }
    public function getByOrderNo($order_no){
    	return $this->where('orderid', $order_no)->find();
    }
    public function getOneOrder($whereArr, $fieldStr = '*'){
        return $this->where($whereArr)->field($fieldStr)->find();
    }
    public function updateById($id, $data){
    	return $this->where('id',$id)->save($data);
    }

    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){

        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
}