<?php
/**
 * 体育赛事活动审核model
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsCompetitionAudit extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    
    protected $json = ['audit_info'];
    protected $jsonAssoc = true;
    
    public function getAuditTimeAttr($value, $data)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function getSubmitTimeAttr($value, $data)
    {
        return $data['add_time'] ? date('Y-m-d H:i:s', $data['add_time']) : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $statusMap = ['待审核', '审核通过', '审核失败'];
        return $statusMap[$data['status']] ?: '';
    }

    public function competition()
    {
        return $this->belongsTo(LifeToolsCompetition::class, 'competition_id', 'competition_id');
    }

    public function getDetail($id)
    {
        return $this->alias('a')
                ->field(['a.*','a.status as audit_status', 'o.*'])
                ->join('life_tools_competition_join_order o', 'a.order_id = o.pigcms_id')
                ->with(['competition'])
                ->where('id', $id)
                ->find();
    }
}