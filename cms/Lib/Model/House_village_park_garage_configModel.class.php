<?php


class House_village_park_garage_configModel extends Model
{

    public function getVillageParkConfig($garage_id,$village_id=0){
        $parkConfigInfo=array();
        if($garage_id>0){
            $garageConfigWhere=array('garage_id'=>$garage_id,'village_id'=>$village_id);
            if($village_id>0){
                $garageConfigWhere['village_id']=$village_id;
            }
            $parkConfigInfo=$this->where($garageConfigWhere)->find();
        }
        if(empty($parkConfigInfo) && $village_id>0){
            $parkWhere=array();
            $parkWhere['village_id']=$village_id;
            $village_park_config=M('House_village_park_config')->where($parkWhere)->find();
            if(!empty($village_park_config)){
                $parkConfigInfo=$village_park_config;
            }
        }
        if(empty($parkConfigInfo)&& $village_id>0){
            $parkWhere=array();
            $parkWhere['village_id']=$village_id;
            $parkConfigInfo=$this->where($parkWhere)->order('id desc')->find();
        }
        return  $parkConfigInfo;
    }

    public function getVillageParkConfigByWhere($configWhere=array(),$garage_id=0,$field='*',$orderby='id DESC'){
        $parkConfigInfo=array();
        if(!empty($configWhere)){
            $configGarageWhere=$configWhere;
            if($garage_id>0){
                $configGarageWhere[]=array('garage_id','=',$garage_id);
            }
            $parkConfigInfo=$this->where($configGarageWhere)->field($field)->order($orderby)->find();
        }
        if(empty($parkConfigInfo) && !empty($configWhere)){
            $village_park_config=M('House_village_park_config')->where($configWhere)->field($field)->find();
            if(!empty($village_park_config)){
                $parkConfigInfo=$village_park_config;
            }
        }
        return  $parkConfigInfo;
    }
}
