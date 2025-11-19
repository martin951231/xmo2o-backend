<?php
/**
 * 打印机
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/17 13:57
 */

namespace app\merchant\model\service\print_order;
use app\merchant\model\db\OrderprintList as OrderprintListModel;
class OrderprintListService {
    public $orderprintListModel = null;
    public function __construct()
    {
        $this->orderprintListModel = new OrderprintListModel();
    }

    /**
     * 添加数据
     * @param $orderId int 
     * @param $data array 
     * @return array
     */
    public function add($data) {
        if(empty($data)){
           return false;
        }
       
        $result = $this->orderprintListModel->save($data);
        if(!$result) {
            return false;
        }
        
        return $result; 
    }

    /**
     * 更新数据
     * @param $orderId int 
     * @param $data array 
     * @return array
     */
    public function updateData($where, $data) {
        if(empty($where) || empty($data)){
           return false;
        }
       
        $result = $this->orderprintListModel->where($where)->update($data);
        if(!$result) {
            return false;
        }
        
        return $result; 
    }

    /**
     * 根据条件一条数据
     * @param $where array 
     * @return array
     */
    public function getOne($where,$field=true, $order=[]) {
        if(empty($where)){
           return [];
        }

        $print = $this->orderprintListModel->getOne($where, $field, $order);
        if(!$print) {
            return [];
        }
        
        return $print->toArray(); 
    }

    /**
     * 根据条件获取数据
     * @param $where array 
     * @return array
     */
    public function getList($where, $order=[]) {
        if(empty($where)){
           return [];
        }

        $printList = $this->orderprintListModel->getList($where, $order);
        if(!$printList) {
            return [];
        }
        
        return $printList->toArray(); 
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->orderprintListModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}