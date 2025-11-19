<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsHousesList extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 在售楼盘前端列表
     */
    public function getApiList($where,$fields='*',$order=[],$page,$pageSize){
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $result = $this
            ->field($fields)
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
     * 获取信息
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