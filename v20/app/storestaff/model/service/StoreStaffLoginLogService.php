<?php
/**
 * 店员登录日志model
 * Author: hengtingmei
 * Date Time: 2020/12/09 16:08
 */

namespace app\storestaff\model\service;
use app\storestaff\model\db\StoreStaffLoginLog;
class StoreStaffLoginLogService {
    public $storeStaffLoginLogModel = null;
    public function __construct()
    {
        $this->storeStaffLoginLogModel = new StoreStaffLoginLog();
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where,$field = true ){
        if(empty($where)){
            return false;
        }

        $result = $this->storeStaffLoginLogModel->getOne($where,$field);
        if(empty($result)){
            return [];
        }

        return $result;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where){
        if(empty($where)){
            return false;
        }

        try {
            $result = $this->storeStaffLoginLogModel->getSome($where);
        } catch (\Exception $e) {
            return false;
        }
        return $result->toArray();
    }

    /**
     *插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $id = $this->storeStaffLoginLogModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

}