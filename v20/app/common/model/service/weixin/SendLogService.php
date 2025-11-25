<?php

/**
 * 群发
 * Author: hengtingmei
 * Date Time: 2020/12/18
 */
namespace app\common\model\service\weixin;
use app\common\model\db\SendLog;
class SendLogService
{

    public $sendLogModel = null;
    public function __construct()
    {
        $this->sendLogModel = new SendLog();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->sendLogModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->sendLogModel->updateThis($where, $data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return [];
        }

        $result = $this->sendLogModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->sendLogModel->getSome($where, $field, $order, $page, $limit);
        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $count = $this->sendLogModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }
}
