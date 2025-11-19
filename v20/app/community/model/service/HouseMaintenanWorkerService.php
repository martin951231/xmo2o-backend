<?php

namespace app\community\model\service;

use app\community\model\db\HouseMaintenanWorker;


class HouseMaintenanWorkerService
{


    public function getMaintenanWorkerList($where,$field='*')
    {
        $dbHouseWorker = new HouseMaintenanWorker();
        $data = $dbHouseWorker->getAll($where,$field);
        return $data;
    }


    public function getOneData($where,$field='*')
    {
        $dbHouseWorker = new HouseMaintenanWorker();
        $data = $dbHouseWorker->get_one($where,$field);
        return $data;
    }

    public function saveMaintenanWorkers($village_id=0,$worker=array()){
        if($village_id<1){
            return false;
        }
        $nowtime=time();
        $dbHouseWorker = new HouseMaintenanWorker();
        if(empty($worker)){
            $dbHouseWorker->updateField(['village_id'=>$village_id],['is_del_time'=>$nowtime]);
            return true;
        }else{
            $dbHouseWorker->updateField(['village_id'=>$village_id],['is_del_time'=>$nowtime]);
            foreach ($worker as $wvv){
                if(!isset($wvv['wid']) || empty($wvv['wid'])){
                    continue;
                }
                $wid=intval($wvv['wid']);
                if($wid<1){
                    continue;
                }
                $tmpWorker=$dbHouseWorker->get_one(['wid'=>$wid,'village_id'=>$village_id],'id,village_id');
                if(!empty($tmpWorker)){
                    $extra_data=$wvv;
                    $extra_data['update_time']=$nowtime;
                    $savedata=array('is_del_time'=>0,'extra_data'=>json_encode($extra_data,JSON_UNESCAPED_UNICODE));
                    $dbHouseWorker->updateField(['id'=>$tmpWorker['id']],$savedata);
                }else{
                    $extra_data=$wvv;
                    $savedata=array('is_del_time'=>0,'extra_data'=>json_encode($extra_data,JSON_UNESCAPED_UNICODE));
                    $savedata['wid']=$wid;
                    $savedata['village_id']=$village_id;
                    $savedata['addtime']=$nowtime;
                    $dbHouseWorker->addeData($savedata);
                }
            }
        }
        return true;
    }

}
