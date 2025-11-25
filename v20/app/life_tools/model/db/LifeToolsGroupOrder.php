<?php
/**
 * 景区订单子表-团体票
 */

namespace app\life_tools\model\db;
use think\Model;

class LifeToolsGroupOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public $status_text = [
        0   =>    '待提交', //'待提交审核',
        10  =>    '待审核',//'已提交审核',
        20  =>    '审核成功',//'审核通过',
        30  =>    '审核失败',//'审核不通过',
        40  =>    '已过期',//'已过期'
    ];
    
    /**
     * 获取订单数据或数量
     */
    public function getDataOrNumByCondition($condition, $type = 1, $page_size = 10)
    {
        $_this = $this->alias('go')
                    ->field(['go.id group_order_id', 'go.group_status', 'go.submit_audit_time','go.verify_num','go.audit_msg','l.tools_id', 'l.title', 't.ticket_id', 'l.cover_image', 't.title ticket_title','o.order_id', 'o.num', 'o.ticket_time', 'o.total_price', 'o.price', 'o.add_time', 'o.nickname', 'o.phone','o.order_status','gt.group_price'])
                    ->join('life_tools_order o', 'go.order_id = o.order_id')
                    ->join('life_tools l', 'o.tools_id = l.tools_id')
                    ->join('life_tools_ticket t', 'o.ticket_id = t.ticket_id')
                    ->join('life_tools_group_ticket gt', 'gt.ticket_id = t.ticket_id and gt.is_del = 0')
                    ->where($condition);
        if($type == 1){
            $data = $_this->order('o.add_time DESC')->paginate($page_size);
        }else{
            $data = $_this->count();
        }
        return $data;
    }

    /**
     * 根据订单ID获取一条记录
     */
    public function getOne($order_id)
    {
        return $this->alias('go')
            ->field(['go.id group_order_id', 'go.group_status', 'go.submit_audit_time','l.tools_id', 'l.title', 't.ticket_id', 'l.cover_image', 't.title ticket_title','o.order_id', 'o.num', 'o.ticket_time', 'o.total_price', 'o.price', 'o.add_time', 'o.mer_id'])
            ->join('life_tools_order o', 'go.order_id = o.order_id')
            ->join('life_tools l', 'o.tools_id = l.tools_id')
            ->join('life_tools_ticket t', 'o.ticket_id = t.ticket_id')
            ->where('o.order_id', $order_id)
            ->find();
    }

    /**
     * 获取指定商户的团体票审核订单列表
     * @author nidan
     * @date 2022/3/24
     */
    public function getAuditList($where,$field,$order,$page_size)
    {
        $data = $this->alias('a')
            ->field($field)
            ->join('life_tools_group_travel_agency b', 'a.travel_agency_id = b.id')
            ->join('life_tools_order c', 'a.order_id = c.order_id')
            ->join('life_tools d', 'c.tools_id = d.tools_id')
            ->where($where)
            ->order($order)
            ->paginate($page_size);
        return $data;
    }

    /**
     * 通过订单id获取团体篇用户信息
     * @author Nd
     * @date 2022/4/24
     * @param $order_id
     */
    public function getUserInfo($where,$field)
    {
        $data = $this->field($field)->where($where)->find();
        return $data;
    }
}