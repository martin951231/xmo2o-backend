<?php
/**
 * 用户登录日志
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2021/05/24 17:11
 */
namespace app\common\model\service\user;

use app\common\model\db\AppapiAppLoginLog;

class AppapiAppLoginLogService
{
    public $appapiAppLoginLogModel = null;
    public function __construct()
    {
        $this->appapiAppLoginLogModel = new AppapiAppLoginLog();
    }
	/**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where, $field = true, $order = []){
        $result = $this->appapiAppLoginLogModel->getOne($where, $field , $order);
        if(!$result) {
            return [];
        }
        return $result;
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $start = ($page-1)*$limit;
        $result = $this->appapiAppLoginLogModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->appapiAppLoginLogModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
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

        $result = $this->appapiAppLoginLogModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }


}