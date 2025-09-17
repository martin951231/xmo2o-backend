<?php

class plan_sub_village_qywx_send_message extends plan_base
{
    public function runTask(){
        $model = '\app\community\model\service\VillageQywxMessageService/sendMessage';
        $where[] = ['send_status','in','2,3,4'];
        $params = [
            'where' => $where,
            'field' => true,
            'page'=>0,
            'limit'=>0,
            'order'=>'id DESC'
        ];
        $this->keepThread();
        $v20res = invoke_v20_service($model, $params);
        return true;
    }
}