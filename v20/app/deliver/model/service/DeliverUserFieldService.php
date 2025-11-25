<?php

namespace app\deliver\model\service;

use app\deliver\model\db\DeliverUserField;

/**
 * 万能表服务
 * @author: 汪晨
 * @date: 2021/3/29
 */
class DeliverUserFieldService
{
    public $deliverUserFieldMod;

    public function __construct()
    {
        $this->deliverUserFieldMod = new DeliverUserField();
    }

    /**
     * 获取配送员注册自定义字段列表
     * @author: 汪晨
     * @date: 2021/3/29
     */
    public function getFielsdList($fields = '*')
    {
        $data = (new DeliverUserField())->field($fields)->select()->toArray();
        $deliverFields = array();
        foreach($data as $v){
            if(!empty($v['type'] == 3)){
                $v['use_field'] = unserialize($v['use_field']);
            }
            $deliverFields[] = $v;
        }
        return $deliverFields;
    }
}
