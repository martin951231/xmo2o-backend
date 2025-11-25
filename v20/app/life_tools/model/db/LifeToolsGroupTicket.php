<?php
/**
 * 景区团体票门票信息
 */

namespace app\life_tools\model\db;
use think\Model;

class LifeToolsGroupTicket extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    // 获得已选中的门票列表
    public function getTicketList($where = [], $field = true,$order=true,$page=1,$pageSize=10){
        $prefix = config('database.connections.mysql.prefix');
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $arr = $this->alias('g')
            ->field($field)
            ->join($prefix . 'life_tools_ticket t', 't.ticket_id = g.ticket_id')
            ->join($prefix . 'life_tools l', 't.tools_id = l.tools_id')
            ->where($where)
            ->order($order)
            ->group('g.ticket_id')
            ->paginate($limit)->toArray();
        return $arr;
    }

    // 用户获得景区列表
    public function getUserToolsList($where = [], $field = true,$order=true,$page=1,$pageSize=10){
        $prefix = config('database.connections.mysql.prefix');
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $arr = $this->alias('g')
            ->field($field)
            ->join($prefix . 'life_tools_ticket t', 't.ticket_id = g.ticket_id')
            ->join($prefix . 'life_tools l', 't.tools_id = l.tools_id')
            ->where($where)
            ->order($order)
            ->group('l.tools_id')
            ->paginate($limit)->toArray();
        return $arr;
    }

    /**
     * 获取热门景区列表
     * @author nidan
     * @date 2022/3/23
     */
    public function getHotMerchantList($where,$field,$pageSize,$order,$uid)
    {
        $result = $this->alias('a')
            ->where($where)
            ->field($field)
            ->join('merchant b', 'a.mer_id = b.mer_id')
            ->leftjoin('life_tools_group_travel_agency c', 'b.mer_id = c.mer_id AND c.uid=' . $uid)
            ->group('b.mer_id')
            ->order($order)
            ->paginate($pageSize)->toArray();
        return $result;
    }
}