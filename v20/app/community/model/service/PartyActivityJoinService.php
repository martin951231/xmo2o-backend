<?php
/**
 * Created by PhpStorm.
 * User: gxs
 * Date: 2020/6/1
 * Time: 16:58
 */
namespace app\community\model\service;

use app\community\model\db\PartyActivityJoin;
use app\community\model\db\PartyActivity;

class PartyActivityJoinService{


    public function getPartyActivityJoinService($where,$page=0,$field =true,$order='id ASC',$page_size=10)
    {
        $party_activity_join = new PartyActivityJoin();
        $list = $party_activity_join->getList($where,$page,$field,$order,$page_size);
        return $list;
    }

    public function editPartyActivityJoin($where,$data)
    {
        $party_activity_join = new PartyActivityJoin();
        return $party_activity_join->saveOne($where,$data);
    }


    public function infoPartyActivityJoin($where){
        $party_activity_join = new PartyActivityJoin();
        return $party_activity_join->getOne($where);
    }

    public function delPartyActivityJoin()
    {
        
    }

    /**
     * 添加活动报名
     * @author lijie
     * @date_time 2020/10/14
     * @param $data
     * @return int|string
     */
    public function addPartyActivityJoin($data)
    {
        $party_activity_join = new PartyActivityJoin();
        return $party_activity_join->addOne($data);
    }

    /**
     * 报名人数+1
     * @param $where
     * @return mixed
     */
    public function setOne($where)
    {
        $party_activity = new PartyActivity();
        $res = $party_activity->setOne($where);
        return $res;
    }

}