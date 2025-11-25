<?php

class House_app_jpush_infoModel extends Model
{
    public function saveJpushInfo($registrationId='',$village_id=0,$uid=0,$jpush_info=array()){
        if(empty($registrationId) || $village_id<1 || $uid<1 || empty($jpush_info)){
            return false;
        }
        $whereArr=array('village_id'=>$village_id,'jpush_registration_id'=>$registrationId,'uid'=>$uid);
        $saveArr=array('village_id'=>$village_id,'jpush_registration_id'=>$registrationId,'uid'=>$uid);
        if(isset($jpush_info['platform'])){
            $whereArr['platform']=$jpush_info['platform'];
            $saveArr['platform']=$jpush_info['platform'];
        }
        if(isset($jpush_info['bind_id']) && $jpush_info['bind_id']){
            $whereArr['bind_id']=$jpush_info['bind_id'];
            $saveArr['bind_id']=$jpush_info['bind_id'];
        } 
        if(isset($jpush_info['user_table_type'])){
            $saveArr['user_table_type']=$jpush_info['user_table_type'];
        }
        $nowtime=time();
        $infoTmp=$this->where($whereArr)->field('*')->order('id desc')->find();
        $insert_id=0;
        if($infoTmp && isset($infoTmp['id'])){
            $insert_id=$infoTmp['id'];
        }
        if(empty($infoTmp)){
            $saveArr['add_time']=$nowtime;
            $saveArr['user_id']=$uid;
            $insert_id= $this->data($saveArr)->add();
        }else if(!empty($infoTmp) && $infoTmp['del_time']>10){
            $this->where(array('id'=>$infoTmp['id']))->data(array('update_time'=>$nowtime,'del_time'=>'0'))->save();
        }
        return  $insert_id;
    }
}

?>