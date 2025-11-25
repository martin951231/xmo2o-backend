<?php
/**
 * 用户model
 * Author: chenxiang
 * Date Time: 2020/5/25 14:01
 */

namespace app\common\model\service;

use app\common\model\db\Audit;

class AuditService
{
    public $auditModel = null;
    public function __construct()
    {
        $this->auditModel = new Audit();
    }

    /**
     * 增加审核日志记录
     */
    public function addLog($param)
    {
        if(!isset($param['admin_id']) || !$param['admin_id'] || !isset($param['audit_object_ids']) || !$param['audit_object_ids'] || !isset($param['type']) || !$param['type']){
            throw new \think\Exception('参数缺失！');
        }
        $param['audit_status'] = $param['audit_status']??1;
        $param['audit_msg'] = $param['audit_msg']??'';
        $data = [];
        foreach ($param['audit_object_ids'] as $id){
            $data[] = [
                'admin_id' => $param['admin_id'],
                'audit_object_id' => $id,
                'type' => $param['type'],
                'audit_status' => $param['audit_status'],
                'audit_msg' => $param['audit_msg'],
                'audit_time' => time()
            ];
        }
        $add = $this->auditModel->insertAll($data);
        if(!$add){
            throw new \think\Exception('添加审核日志失败！');
        }
        return true;
    }
}
