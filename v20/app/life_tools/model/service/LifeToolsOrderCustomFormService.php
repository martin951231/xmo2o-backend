<?php

/**
 * 景区体育健身自定义表单用户提交数据
 */

namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeToolsOrderCustomForm;
class LifeToolsOrderCustomFormService
{
    public $lifeToolsOrderCustomFormModel = null;

    public function __construct()
    {
        $this->lifeToolsOrderCustomFormModel = new LifeToolsOrderCustomForm();
    }

    /**
     * 格式化数据
     * @param array $where 
     * @return array
     */
    public function formatData($data){
        foreach($data as &$value){
            $value['content'] = json_decode($value['content'], true);
        }
        return $data;
    }

    /**
     *保存订单提交页显示的自定义表单信息
     * @param int $ticketId 门票id 
     * @param int $orderId 订单id 
     * @param array $customData 用户提交的数据 
     * @return bool
     */
    public function saveUserCustomFormDetailByTicketId($ticketId, $orderId, $customData){
        if(empty($customData) || empty($ticketId) || empty($orderId)){
            return false;
        }

        // 保存数据
        $data = [];
        foreach($customData as $value){

            $data[] = [
                'order_id' => $orderId,
                'ticket_id' => $ticketId,
                'content' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'add_time' => time()
            ];
        }

        $res = $this->lifeToolsOrderCustomFormModel->addAll($data);
        return $res;
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=['pigcms_id' => 'asc'],$page=0,$limit=0){
        $result = $this->lifeToolsOrderCustomFormModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }  
}