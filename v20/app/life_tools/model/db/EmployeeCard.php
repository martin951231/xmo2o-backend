<?php


namespace app\life_tools\model\db;


use think\Model;

class EmployeeCard extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 员工卡列表
     */
    public function getCardList($where = [], $field = true,$order='card_id desc',$page=0,$limit=0){
        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $result = $sql->select()->toArray();
        return $result;
    }

    /**
     * 员工卡编辑
     */
    public function editCard($where = [], $field = true, $order = []) {
        $result = $this->field($field)->where($where)->order($order)->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * 员工卡删除
     */
    public function delCard($where)
    {
        $ret=$this->where($where)->delete();
        return $ret;
    }
}