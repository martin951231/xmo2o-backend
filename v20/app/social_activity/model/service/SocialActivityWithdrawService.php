<?php

namespace app\social_activity\model\service;

use app\social_activity\model\db\SocialActivityAdmin;
use app\social_activity\model\db\SocialActivityWithdraw;
use think\exception\ValidateException;
use think\facade\Db;

class SocialActivityWithdrawService
{
    /**
     * 获取提现审核列表
     * @param array $param
     * @return array
     */
    public function getWithdrawList($param)
    {
        $is_export = $param['is_export'] ?? 0;
        $where = [];
        
        // 时间范围搜索
        if (!empty($param['start_time'])) {
            $where[] = ['create_time', '>=', $param['start_time']];
        }
        if (!empty($param['end_time'])) {
            $where[] = ['create_time', '<=', $param['end_time'] . ' 23:59:59'];
        }
        
        // 审核状态
        if (isset($param['audit_status']) && $param['audit_status'] >= 0) {
            $where[] = ['audit_status', '=', $param['audit_status']];
        }
        
        $page = $param['page'] ?? 1;
        $pageSize = $param['page_size'] ?? 10;
        
        $model = new SocialActivityWithdraw();
        $query = $model->where($where)
            ->append(['status_text'])
            ->order('id DESC');
        if ($is_export == 1) {
            $list = $query->select()->toArray();
            $title = '提现记录导出列表';
            $xlsCell = [
                ['realname', '提现人'],
                ['amount', '提现金额'],
                ['remark', '备注'],
                ['create_time', '提现时间'],
                ['status_text', '审核状态'],
                ['audit_remark', '审核备注'],
            ];
            $fileName = '提现记录列表_' . date('YmdHis');
            $exportResult = export_excel($title, $xlsCell, $list, $fileName);
            return ['file_url' => $exportResult['url']];
        } else {
            $list = $query->paginate($pageSize)->toArray();
        }
        
        return $list;
    }
    
    /**
     * 提现审核
     * @param array $param
     * @return bool
     */
    public function auditWithdraw($param)
    {
        $systemUser = $param['systemUser'];
        if (empty($param['id'])) {
            throw new ValidateException('提现记录ID不能为空');
        }
        
        if (!isset($param['audit_status']) || !in_array($param['audit_status'], [10, 20])) {
            throw new ValidateException('审核状态无效');
        }
        
        if($param['audit_status'] != 10 && empty($param['audit_remark'])){
            throw new ValidateException('审核备注不能为空！');
        }
        
        $model = new SocialActivityWithdraw();
        $withdraw = $model->where(['id'=>$param['id']])->findOrEmpty()->toArray();
        if (!$withdraw) {
            throw new ValidateException('提现记录不存在');
        }
        
        if ($withdraw['audit_status'] != 0) {
            throw new ValidateException('该提现记录已审核');
        }
        
        $data = [
            'audit_status' => $param['audit_status'],
            'audit_time' => date('Y-m-d H:i:s'),
            'audit_remark' => $param['audit_remark'] ?? '',
            'auditor' => $systemUser['account'],
            'audit_uid' => $systemUser['id'],
        ];
        Db::startTrans();
        try {
            $model->where('id', $param['id'])->update($data);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
    }

    /**
     * 申请提现
     * @param array $params
     * @return array
     */
    public function applyWithdraw(array $params): array
    {
        $amount = $params['amount'];
        $realname = $params['realname'];
        $remark = $params['remark'];
        $admin_uid = $params['admin_uid'];

        $withdrawableAmount = (new SocialActivityOrderService())->getWithdrawableAmount();
        if ($amount > $withdrawableAmount) {
            throw new \think\Exception('提现金额超过可提现金额');
        }

        $data = [
            'amount' => $amount,
            'realname' => $realname,
            'remark' => $remark,
            'audit_status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'admin_uid' => $admin_uid,
        ];

        try {
            $recordId = (new SocialActivityWithdraw())->insertGetId($data);
            return ['id'=>$recordId];
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
    }
}