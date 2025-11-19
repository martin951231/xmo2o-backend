<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeScenicLimitedSku extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @return array
     * 通过id获取sku
     */
    public function getListBySkuId($where)
    {
        $arr = $this->field(true)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
    /**
     * @param $where
     * @param string $field
     * @return float
     * 统计某个条件信息
     */
    public function getSum($where, $field = '*')
    {
        return $this->where($where)->sum($field);
    }

    /**
     * @param $where
     * @param bool $fields
     * @param array $order
     * @return mixed
     * 活动门票列表
     */
    public function getActTickectList($where,$fields=true,$order=[]){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'life_tools_ticket'.' t','t.ticket_id = s.ticket_id')
            ->join($prefix.'life_tools'.' l','l.tools_id = s.tool_id')
            ->field($fields)
            ->order($order)
            ->where($where);
        $list['total']=$result->count();
        $list['list']=$result->select()->toArray();
        return $list;
    }

}