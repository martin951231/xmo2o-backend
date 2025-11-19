<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsHousesFloorPlan extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
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