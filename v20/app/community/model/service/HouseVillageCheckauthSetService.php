<?php


namespace app\community\model\service;

use app\community\model\db\HouseVillageCheckauthSet;
use app\community\model\db\HouseWorker;
class HouseVillageCheckauthSetService
{

    public function getOneCheckauthSet($where,$field = true) {

        $db_villageCheckauthSet = new HouseVillageCheckauthSet();
        $info = $db_villageCheckauthSet->getOne($where,$field);
        if (!empty($info) && !empty($info['set_datas'])) {
            $info['set_datas'] = json_decode($info['set_datas'],1);
            $checkLevel=array();
            if(!empty($info['set_datas'])){
                foreach ($info['set_datas'] as $vv){
                    $checkLevel[$vv['level_v']]=$vv;
                }
                ksort($checkLevel);
            }
            $info['check_level']=$checkLevel;
        }
        return $info;
    }

    public function isOpenSet($where) {

        $db_villageCheckauthSet = new HouseVillageCheckauthSet();
        $info = $db_villageCheckauthSet->getOne($where,'is_open');
        $is_open=0;  //关闭
        if (!empty($info)) {
            $is_open = intval($info['is_open']);
        }
        return $is_open;
    }

    public function checkUserAuthLevel($wid=0,$village_id=0,$xtype='order_refund_check',$property_id=0) {
        if($wid<0 || $village_id<0 || empty($xtype)){
            return false;
        }
        $whereArr=array('village_id'=>$village_id,'xtype'=>$xtype);
        if($property_id>0){
            $whereArr['property_id']=$property_id;
        }
        $db_villageCheckauthSet = new HouseVillageCheckauthSet();
        $info = $db_villageCheckauthSet->getOne($whereArr);
        if (!empty($info) && !empty($info['set_datas'])) {
            if($info['is_open']<1){
                return false;
            }
            $info['set_datas'] = json_decode($info['set_datas'],1);
            $checkLevel=array();
            if(empty($info['set_datas'])){
                return false;
            }
            $wid_level=-1;
            foreach ($info['set_datas'] as $vv){
                $checkLevel[$vv['level_v']]=$vv;
                if($vv['level_wid']==$wid){
                    $wid_level=$vv['level_v'];
                }
            }
            if($wid_level<0){
                return false;
            }
            ksort($checkLevel);
            $info['check_level']=$checkLevel;
            $info['wid']=$wid;
            $info['wid_level']=$wid_level;
            $db_houseWorker = new HouseWorker();
            $whereArr=array('wid'=>$wid,'village_id'=>$info['village_id']);
            $worker=$db_houseWorker->get_one($whereArr);
            if (!$worker || $worker->isEmpty()) {
                $worker = [];
            }else{
                $worker=$worker->toArray();
            }
            $info['worker']=$worker;
            return $info;
        }else{
            return false;
        }

    }

    public function getNextAuthLevel($wid=0,$village_id=0,$xtype='order_refund_check',$property_id=0) {
        if($wid<0 || $village_id<0 || empty($xtype)){
            return false;
        }
        $whereArr=array('village_id'=>$village_id,'xtype'=>$xtype);
        if($property_id>0){
            $whereArr['property_id']=$property_id;
        }
        $db_villageCheckauthSet = new HouseVillageCheckauthSet();
        $info = $db_villageCheckauthSet->getOne($whereArr);
        if (!empty($info) && !empty($info['set_datas'])) {
            $info['set_datas'] = json_decode($info['set_datas'],1);
            $checkLevel=array();
            $wid_level=-1;
            if($info['set_datas']){
                foreach ($info['set_datas'] as $vv){
                    $checkLevel[$vv['level_v']]=$vv;
                    if($vv['level_wid']==$wid){
                        $wid_level=$vv['level_v'];
                    }
                }
            }
            ksort($checkLevel);
            $info['check_level']=$checkLevel;
            $info['wid']=$wid;
            $info['wid_level']=$wid_level;
            $info['next_wid_level']=0;
            $info['next_wid']=0;
            $info['next_worker']=array();
            if($wid_level>-1){
                $db_houseWorker = new HouseWorker();
                $whereArr=array('wid'=>$wid,'village_id'=>$info['village_id']);
                $worker=$db_houseWorker->get_one($whereArr);
                if (!$worker || $worker->isEmpty()) {
                    $worker = [];
                }else{
                    $worker=$worker->toArray();
                }
                $info['worker']=$worker;
                $next_wid_level=$wid_level+1;
                if(isset($checkLevel[$next_wid_level])){
                    $info['next_wid_level']=$next_wid_level;
                    $info['next_wid']=$checkLevel[$next_wid_level]['level_wid'];
                }else{

                }
                if($info['next_wid']>0){
                    $whereArr=array('wid'=>$info['next_wid'],'village_id'=>$info['village_id']);
                    $worker=$db_houseWorker->get_one($whereArr);
                    if (!$worker || $worker->isEmpty()) {
                        $worker = [];
                    }else{
                        $worker=$worker->toArray();
                    }
                    $info['next_worker']=$worker;
                }
            }
            return $info;
        }else{
            return false;
        }

    }
}