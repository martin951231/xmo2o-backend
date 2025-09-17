<?php
/**
 * 企业付款
 * time：2020/12/14
 * author 衡婷妹
 */

namespace app\common\model\service\companypay;

use app\common\model\db\Companypay;
class CompanypayService
{
    public $companypayModel = null;
    public function __construct()
    {
        $this->companypayModel = new Companypay();

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

        $result = $this->companypayModel->getOne($where,$field);
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
        try {
            $result = $this->companypayModel->getSome($where);
        } catch (\Exception $e) {
            return [];
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

        $data['add_time'] = time();

        $id = $this->companypayModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
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

        $result = $this->companypayModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}