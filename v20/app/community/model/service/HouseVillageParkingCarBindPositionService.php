<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2023/7/25 9:20
 */

namespace app\community\model\service;
use app\community\model\db\HouseVillageParkingCarBindPosition;
use think\facade\Db;

class HouseVillageParkingCarBindPositionService
{

    public function getOneCarBindPosition($whereArr=array()){
        $oneCarBindPosition=array();
        $houseVillageParkingCarBindPositionDb=new HouseVillageParkingCarBindPosition();
        if(!empty($whereArr)){
            $oneCarBindPositionObj=$houseVillageParkingCarBindPositionDb->getOneData($whereArr);
            if($oneCarBindPositionObj && !$oneCarBindPositionObj->isEmpty()){
                $oneCarBindPosition=$oneCarBindPositionObj->toArray();
            }
        }
        return  $oneCarBindPosition;
    }
    public function addOneCarBindPosition($addArr=array()){
        $houseVillageParkingCarBindPositionDb=new HouseVillageParkingCarBindPosition();
        $ret=$houseVillageParkingCarBindPositionDb->addOneData($addArr);
        return $ret;
    }
    public function saveCarBindPosition($whereArr=array(),$saveArr=array()){
        $houseVillageParkingCarBindPositionDb=new HouseVillageParkingCarBindPosition();
        $ret=$houseVillageParkingCarBindPositionDb->saveData($whereArr,$saveArr);
        return $ret;
    }
    public function delCarBindPosition($whereArr=array(),$saveArr=array()){
        $houseVillageParkingCarBindPositionDb=new HouseVillageParkingCarBindPosition();
        $ret=$houseVillageParkingCarBindPositionDb->updateAllData($whereArr,$saveArr);
        return $ret;
    }
    public function getCarBindPositionList($where,$field=true,$page=0,$limit=15,$order='garage_id DESC'){
        $houseVillageParkingCarBindPositionDb=new HouseVillageParkingCarBindPosition();
        $listDataObj=$houseVillageParkingCarBindPositionDb->getListData($where,$field,$order,$page,$limit);
        if($listDataObj && !$listDataObj->isEmpty()){
            $listData=$listDataObj->toArray();
        }else{
            $listData=array();
        }
        return $listData;
    }
    
}
