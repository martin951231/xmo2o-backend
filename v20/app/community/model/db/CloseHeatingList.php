<?php
namespace app\community\model\db;

use think\Model;
class CloseHeatingList extends Model
{
    public $auditStatus = [
        '0'=>'待审核',
        '1'=>'审核通过',
        '2'=>'审核不通过'
    ];
    public $isMeasureUp = [
        '1'=>'符合供暖',
        '2'=>'不符合供暖'
    ];
    public $userType = [
        '1'=>'串联用户',
        '2'=>'并联用户'
    ];
    public function getList($where, $field, $order, $limit)
    {
        $list = $this->where($where)
            ->field($field)
            ->order($order)
            ->paginate($limit)
            ->toArray();
        return $list;
    }

    public function addOne($data)
    {
        $add = $this->insert($data);
        return $add;
    }

    public function getOne($where, $field,$order='pigcms_id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        $data = $data ? $data->toArray() : [];
        return $data;
    }

    public function updateThis($where, $date)
    {
        $update = $this->where($where)->update($date);
        return $update;
    }

    public function getAuditStatusMsg($auditStatus)
    {
        return $this->auditStatus[$auditStatus] ?? '';
    }

    public function getIsMeasureUpMsg($isMeasureUp)
    {
        return $this->isMeasureUp[$isMeasureUp] ?? '';
    }
    
    public function getUserTypeMsg($userType)
    {
        return $this->userType[$userType] ?? '';
    }
}