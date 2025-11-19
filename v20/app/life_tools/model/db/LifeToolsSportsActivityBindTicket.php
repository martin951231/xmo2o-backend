<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsSportsActivityBindTicket extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param $where
     * @param $fields
     * @param $order
     * @return mixed
     * 绑定门票列表
     */
    public function getBindTicketList($where,$fields,$order){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'life_tools'.' tool','tool.tools_id = s.tools_id')
            ->join($prefix.'life_tools_ticket'.' ticket','ticket.ticket_id = s.ticket_id')
            ->field($fields)
            ->order($order)
            ->where($where)
            ->select()
            ->toArray();
        $list['list']=$result;
        $list['pin_str']="";
        if(!empty($result)){
          $pin_arr=[];
          foreach ($result as $k=>$v){
              $pin_arr[]=$v['name'].'-'.$v['tickect_name'];
          }
          $list['pin_str']=implode(" ",$pin_arr);
        }
        return $list;
    }

    //查询绑定门票信息
    public function getActivityInfo($where,$fields)
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('a')
            ->join($prefix.'life_tools_sports_activity b','a.activity_id = b.activity_id')
            ->field($fields)
            ->where($where)
            ->find();
        return $result;
    }
}