<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2023/7/25 9:20
 */

namespace app\community\model\service;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkGarageConfig;
use app\community\model\db\HouseVillageParkingGarage;
use think\facade\Db;

class HouseVillageParkGarageConfigService
{

    public function getVillageParkConfig($garage_id,$village_id=0,$orderby='id DESC'){
        $parkConfigInfo=array();
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        if(!empty($garage_id)){
            $garageConfigWhere=array(['garage_id','=',$garage_id],['village_id','=',$village_id]);
            if(is_array($garage_id)){
                $garageConfigWhere=array(['garage_id','in',$garage_id],['village_id','=',$village_id]);
            }
            $garage_config_obj=$db_house_village_park_garage_config->getOneData($garageConfigWhere,'*',$orderby);
            if($garage_config_obj && !$garage_config_obj->isEmpty()){
                $parkConfigInfo=$garage_config_obj->toArray();
            }
        }
        if(empty($parkConfigInfo) && $village_id>0){
            $parkWhere=array();
            $parkWhere['village_id']=$village_id;
            $info=$db_house_village_park_config->getFind($parkWhere);
            if($info && !$info->isEmpty()){
                $parkConfigInfo=$info->toArray();
            }
        }
        if(empty($parkConfigInfo) && $village_id>0){
            $parkWhere=array();
            $parkWhere['village_id']=$village_id;
            $garage_config_obj=$db_house_village_park_garage_config->getOneData($parkWhere);
            if($garage_config_obj && !$garage_config_obj->isEmpty()){
                $parkConfigInfo=$garage_config_obj->toArray();
            }
        }
        return  $parkConfigInfo;
    }
    public function saveVillageParkGarageConfig($whereArr=array(),$saveArr=array()){
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        $ret=$db_house_village_park_garage_config->saveOneData($whereArr,$saveArr);
        return $ret;
    }
    public function getVillageParkConfigByWhere($configWhere=array(),$garage_id=0,$field='*',$orderby='id DESC'){
        $parkConfigInfo=array();
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        if(!empty($configWhere)){
            $configGarageWhere=$configWhere;
            if($garage_id>0){
                $configGarageWhere[]=array('garage_id','=',$garage_id);
            }
            $garage_config_obj=$db_house_village_park_garage_config->getOneData($configGarageWhere,$field,$orderby);
            if($garage_config_obj && !$garage_config_obj->isEmpty()){
                $parkConfigInfo=$garage_config_obj->toArray();
            }
        }
        if(empty($parkConfigInfo) && !empty($configWhere)){
            $db_house_village_park_config=new HouseVillageParkConfig();
            $info=$db_house_village_park_config->getFind($configWhere,$field);
            if($info && !$info->isEmpty()){
                $parkConfigInfo=$info->toArray();
            }
        }
        return  $parkConfigInfo;
    }
    public function getVillageParkGarageConfig($garage_id=0,$village_id=0,$field='*'){
        $parkConfigInfo=array();
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        if($garage_id>0){
            $garageConfigWhere=array('garage_id'=>$garage_id,'village_id'=>$village_id);
            if($village_id>0){
                $garageConfigWhere['village_id']=$village_id;
            }
            $garage_config_obj=$db_house_village_park_garage_config->getOneData($garageConfigWhere,$field);
            if($garage_config_obj && !$garage_config_obj->isEmpty()){
                $parkConfigInfo=$garage_config_obj->toArray();
            }
        }
        return  $parkConfigInfo;
    }

    public function getVillageParkGarageConfigByWhere($garageConfigWhere=array(),$field='*',$orderby='id DESC'){
        $parkConfigInfo=array();
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        if(!empty($garageConfigWhere)){
            $garage_config_obj=$db_house_village_park_garage_config->getOneData($garageConfigWhere,$field,$orderby);
            if($garage_config_obj && !$garage_config_obj->isEmpty()){
                $parkConfigInfo=$garage_config_obj->toArray();
            }
        }
        return  $parkConfigInfo;
    }

    public function getListData($where, $field = '*', $orderby = 'id DESC',$page=0,$page_size=10,$groupby='',$isnewolny=false)
    {
        $configArr=array();
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        $dataObj=$db_house_village_park_garage_config->getListData($where,$field,$orderby,$page,$page_size,$groupby='');
        if($dataObj && !$dataObj->isEmpty()){
            $configArr=$dataObj->toArray();
        }
        if(empty($configArr) && !$isnewolny){
            $have_garage_id=0;
            foreach ($where as $vv){
                if($vv && $vv['0']=='garage_id'){
                    $have_garage_id=1;
                    break;
                }
            }
            if($have_garage_id>0){
                return $configArr;
            }
            $db_house_village_park_config=new HouseVillageParkConfig();
            $info=$db_house_village_park_config->getFind($where,'*');
            if($info && !$info->isEmpty()){
                $parkConfigInfo=$info->toArray();
                $configArr[]=$parkConfigInfo;
            }
        }
        return $configArr;
    }
    public function getVillageParkingList($where,$field=true,$page=0,$limit=0,$order='garage_id DESC'){
        $houseVillageParkingGarage=new HouseVillageParkingGarage();
        $parkingGarage=$houseVillageParkingGarage->getLists($where,$field,$page,$limit,$order);
        if($parkingGarage && !$parkingGarage->isEmpty()){
            $parkingGarage=$parkingGarage->toArray();
        }else{
            $parkingGarage=array();
        }
        return $parkingGarage;
    }
    public function delParkGarageConfig($where=array()){
        $db_house_village_park_garage_config=new HouseVillageParkGarageConfig();
        return  $db_house_village_park_garage_config->delParkGarageConfig($where);
    }
}
