<?php
/**
 * 门票价格日历service
 * @date 2021-12-16 
 */

namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeToolsTicketSaleDay;
class LifeToolsTicketSaleDayService
{
    public $lifeToolsTicketSaleDayModel = null;
    public function __construct()
    {
        $this->lifeToolsTicketSaleDayModel = new LifeToolsTicketSaleDay();
    }

    /**
     *批量插入数据
     * @param $data array
     * @return int|bool
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        $id = $this->lifeToolsTicketSaleDayModel->addAll($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
    
    /**
     *批量删除数据
     * @param array $where 
     * @return int|bool
     */
    public function del($where){
        if(empty($where)){
            return false;
        }

        $res = $this->lifeToolsTicketSaleDayModel->where($where)->delete();
        return $res;
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->lifeToolsTicketSaleDayModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }
}