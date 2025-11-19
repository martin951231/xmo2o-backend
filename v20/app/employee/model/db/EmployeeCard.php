<?php


namespace app\employee\model\db;


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

    public function getBgImageTextAttr($value, $data)
    {
        return replace_file_domain($data['bg_image']);
    }
    
    /**
     * 获取某个信息
     */
    public function getVal($where,$field)
    {
        $ret=$this->where($where)->value($field);
        return $ret;
    }

    
    /**
     * 是否提醒
     */
    public function isNotice($card_id)
    {
        $card = $this->where('card_id', $card_id)->find();
        if(!$card){
            return false;
        }
        if($card->next_clear_time == -1 || $card->next_clear_time == 0){
            return false;
        }
        $clearTime = $card->next_clear_time;
        $time = strtotime("+{$card->clear_notice_date} day");
        if($clearTime <= $time){
            return true;
        }
        return false;
    }

    /**
     * 获取商家员工卡列表
     */
    public function getMerCardList($where, $field)
    {
        return $this->alias('e')
            ->field($field)
            ->join('merchant m', 'e.mer_id = m.mer_id')
            ->where($where)
            ->select()
            ->toArray();
    }
}