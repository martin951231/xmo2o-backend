<?php


namespace app\life_tools\model\service;


use app\life_tools\model\db\EmployeeCard;

class EmployeeCardService
{
    /**
     * 员工卡列表
     */
    public function getCardList($param)
    {
        $where=[];
        if(!empty($param['name'])){
           $where=[['name','like','%'.$param['name'].'%']];
        }
        if(!empty($param['mer_id'])){
            array_push($where,['mer_id','=',$param['mer_id']]);
        }
        $list['list']=(new EmployeeCard())->getCardList($where);
        $list['total']=(new EmployeeCard())->getCount($where);
        return $list;
    }

    /**
     * 员工卡编辑
     */
    public function editCard($param)
    {
        $where=[['card_id','=',$param['card_id']]];
        $detail=(new EmployeeCard())->editCard($where);
        if(!empty($detail)){
            $detail['bg_image']=replace_file_domain($detail['bg_image']);
        }
        return $detail;
    }

    /**
     * 员工卡保存
     */
    public function saveCard($param)
    {
        if(empty($param['card_id'])){//添加
           unset($param['card_id']);
           $param['add_time']=time();
           $ret=(new EmployeeCard())->add($param);
        }else{//编辑
            $where=[['card_id','=',$param['card_id']]];
            $param['last_time']=time();
            $ret=(new EmployeeCard())->updateThis($where,$param);
            if($ret!==false){
                $ret=true;
            }else{
                $ret=false;
            }
        }
        return $ret;
    }

    /**
     * 员工卡删除
     */
    public function delCard($param)
    {
        $where=[['card_id','=',$param['card_id']]];
        $ret=(new EmployeeCard())->delCard($where);
        return $ret;
    }
}