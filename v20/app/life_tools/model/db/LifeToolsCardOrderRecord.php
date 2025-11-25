<?php


namespace app\life_tools\model\db;

use think\Model;

class LifeToolsCardOrderRecord extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     *获取列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = 20, $field = 'r.*,o.title as card_title,o.nickname,o.phone,o.orderid', $order = 'r.add_time desc') {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix . 'life_tools_card_order o', 'o.order_id = r.order_id', 'left')
                ->join($prefix . 'life_tools_card c', 'c.pigcms_id = r.card_id', 'left')
//                ->join($prefix . 'life_tools a', 'r.tools_id = a.tools_id', 'left')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix . 'life_tools_card_order o', 'o.order_id = r.order_id', 'left')
                ->join($prefix . 'life_tools_card c', 'c.card_id = r.card_id', 'left')
//                ->join($prefix . 'life_tools a', 'r.tools_id = a.tools_id', 'left')
                ->where($where)
                ->order($order)
                ->select();
            if (!empty($arr)) {
                $arr = $arr->toArray();
            } else {
                $arr = [];
            }
        }
        return $arr;
    }

    /**
     * 核销列表
     */
    public function getVerifyList($where, $page_size = 20, $field='*',$order = 'r.add_time desc')
    {
        $arr = $this->alias('r')
        ->field($field)
        ->join('life_tools_card_order o', 'o.order_id = r.order_id', 'left')
        ->join('life_tools_card c', 'c.pigcms_id = r.card_id', 'left')
//                ->join($prefix . 'life_tools a', 'r.tools_id = a.tools_id', 'left')
        ->where($where)
        ->order($order)
        ->paginate($page_size);
        
        return $arr;
    }

}