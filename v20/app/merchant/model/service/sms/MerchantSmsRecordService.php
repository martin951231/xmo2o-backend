<?php
/**
 * 商家短信service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/03 10:57
 */

namespace app\merchant\model\service\sms;
use app\merchant\model\db\MerchantSmsRecord as MerchantSmsRecordModel;
class MerchantSmsRecordService {
    public $merchantSmsRecordModel = null;
    public function __construct()
    {
        $this->merchantSmsRecordModel = new MerchantSmsRecordModel();
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }

        $result = $this->merchantSmsRecordModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->merchantSmsRecordModel->id;
    }

    /**
     * 更新数据
     * @param $where array 条件
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->merchantSmsRecordModel->updateThis($where, $data);
        if(!$result) {
            return false;
        }

        return $result;
    }

     /**
      * 获得最后一条数据
      * @param $where array 条件
      * @return array
      */
    public function getLastOne($where) {
        $result = $this->merchantSmsRecordModel->getLastOne($where);
        if(!$result) {
            return false;
        }

        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->merchantSmsRecordModel->getOne($where);
        // var_dump($this->merchantSmsRecordModel->getLastSql());
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }


}