<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsSportsActivity extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 约战列表
     */
    public function getList($where,$fields='*',$order=[],$page,$pageSize){
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->join($prefix.'life_tools_sports_activity_bind_ticket'.' lt','lt.activity_id = s.activity_id')
            ->join($prefix.'life_tools_sports_activity_people_num'.' lp','lp.activity_id = s.activity_id')
            ->join($prefix.'life_tools'.' tool','tool.tools_id = lt.tools_id')
            ->join($prefix.'life_tools_ticket'.' ticket','ticket.ticket_id = lt.ticket_id')
            ->field($fields)
            ->group('s.activity_id')
            ->order($order)
            ->where($where)
            ->paginate($limit)
            ->toArray();
        return $result;
    }


    /**
     * @param $where
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 获取单个信息
     */
    public function getDetailMsg($where){
        $msg=$this->where($where)->find();
        if(empty($msg)){
            $msg=[];
        }else{
            $msg=$msg->toArray();
        }
        return $msg;
    }


}