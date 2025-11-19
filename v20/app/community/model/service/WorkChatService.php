<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/10/26
 * Time: 14:02
 *======================================================
 */

namespace app\community\model\service;


use app\community\model\db\WorkMsgAuditInfoGroup;

class WorkChatService
{
    /**
     * 获取客户群信息
     * @param array $where
     * @param bool $field
     * @return array
     */
    public function getWorkChatInfo($where = [],$field = true){
        $db_work_chat = new WorkMsgAuditInfoGroup();
        $data = $db_work_chat->getList($where,$field);
        if(!empty($data)){
            $data->toArray();
            foreach ($data as &$value){
                $value['color'] = '#ccffff';
                $value['field_color'] = '#2681F3';
            }
            return $data;
        }else{
            return [];
        }
    }
}