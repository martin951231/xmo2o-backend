<?php

/**
 * 微信模板消息
 */

namespace app\common\model\service\weixin;
use app\common\model\db\Tempmsg;
class TempmsgService
{

    public $tempmsgModel = null;
    public function __construct()
    {
        $this->tempmsgModel = new Tempmsg();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->tempmsgModel->insertGetId($data);
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
            $result = $this->tempmsgModel->updateThis($where, $data);
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

        $result = $this->tempmsgModel->getOne($where, $order);
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
        $result = $this->tempmsgModel->getSome($where, $field, $order, $page, $limit);
        return $result->toArray();
    }
}
