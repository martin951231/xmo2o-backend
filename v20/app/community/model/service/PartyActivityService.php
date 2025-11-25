<?php
/**
 * Created by PhpStorm.
 * User: guoxiansen
 * Date: 2020/5/30
 * Time: 16:43
 */
namespace app\community\model\service;


use app\community\model\db\PartyActivity;

class PartyActivityService{


    public static function getPartyActivityList($where,$page=0,$field =true,$order='sort DESC,activity_id ASC',$page_size=10)
    {
        $list = PartyActivity::getPartyActivityModel($where,$page,$field,$order,$page_size);
        return $list;
    }

    public static function addPartyActivityService($data)
    {
        $partyActivity = new PartyActivity();
        return $partyActivity->addPartyActivityDb($data);
    }

    public function editPartyActivityService($where,$data)
    {
        $partyActivity = new PartyActivity();
        $set = $partyActivity->saveOne($where,$data);
        return $set;
    }

    public function delPartyActivityService($where)
    {
        $partyActivity = new PartyActivity();
        return $partyActivity->delOne($where);
    }

    public function getInfo($where)
    {
        $partyActivity = new PartyActivity();
        return $partyActivity->find($where);
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