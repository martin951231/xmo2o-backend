<?php

/**
 * 景区体育健身自定义表单model
 */

namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeToolsTicketCustomForm;
use think\facade\Db;
class LifeToolsTicketCustomFormService
{
    public $lifeToolsTicketCustomFormModel = null;

    public function __construct()
    {
        $this->lifeToolsTicketCustomFormModel = new LifeToolsTicketCustomForm();
    }

    /**
     *获取订单提交页显示的自定义表单信息
     * @param array $where 
     * @return array
     */
    public function getUserCustomFormDetailByTicketId($ticketId){
        $where = [
            ['ticket_id', '=', $ticketId],
            ['is_del', '=', 0],
            ['status', '=', 1],
        ];
        $list = $this->getSome($where, true, ['sort'=>'desc','pigcms_id'=>'asc']);
        foreach($list as &$value){
            if($value['type'] == 'select'){// 处理单选内容
                $content = [];
                if($value['content']){
                    $value['content'] = explode(',', $value['content']);
                    foreach($value['content'] as $_select){
                        $content[] = [
                            'label' => $_select,
                            'value' => $_select,
                        ];
                    }
                }

                $value['content'] = $content;
                
            }
        }
        return $list;
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->lifeToolsTicketCustomFormModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }  
}