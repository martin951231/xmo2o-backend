<?php
/**
 *已支付表 记录流水
 */

namespace app\common\model\db;
use think\Model;

class PaidOrderRecord extends Model
{

    public function getSum($where, $field)
    {
        $data = $this->where($where)->sum($field);
        return $data;
    }

    public function getListData($where, $field = true, $orderby = 'id DESC', $page = 1, $page_size = 10)
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }

    public function getCount($where = [])
    {
        $result = $this->where($where)->count();
        return $result;
    }

    public function getOneData($where, $field = true, $orderby = 'id DESC')
    {
        $data = $this->field($field)->where($where)->order($orderby)->find();
        return $data;
    }

    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    public function updateOneData($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    public function addOneData($data)
    {
        $result = $this->insertGetId($data);
        return $result;
    }
    public function paidOrderRefundHandle($id=0,$pay_order_info_id=0,$pay_order_no='',$save_record_data=array()){
        $whereArr=array();
        if($id>0){
            $whereArr[]=array('id','=',$id);
        }
        if($pay_order_info_id>0){
            $whereArr[]=array('pay_order_info_id','=',$pay_order_info_id);
        }
        if(!empty($pay_order_no)){
            $whereArr[]=array('pay_order_no','=',$pay_order_no);
        }
        if(!empty($whereArr) && !empty($save_record_data)){
           $paid_order_record = $this->field('*')->where($whereArr)->order('id DESC')->find();
           if($paid_order_record && !$paid_order_record->isEmpty()){
               $this->where($whereArr)->save($save_record_data);
           }
        }
        return true;
    }
}