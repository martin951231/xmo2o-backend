<?php
/**
**
 */

namespace app\community\model\service;
use app\community\model\db\HousePropertyVillageWorker;
use app\community\model\db\HousePropertyVillageWorkerRelation;
use app\community\model\db\HouseWorker;
use app\community\model\db\ProcessSubPlan;
use app\community\model\service\PropertyFrameworkService;
use app\community\model\service\HouseProgrammeService;
use app\community\model\service\workweixin\DepartmentUserService;
use app\community\model\db\PropertyAdmin;
use app\community\model\db\HouseAdmin;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillage;
use app\community\model\db\PropertyGroup;
use think\Exception;
class HousePropertyVillageWorkerService
{

    public function synWorkerToPropertyWorkerInfo($wid=0,$fieldV='',$field='password',$property_id=0)
    {
        if($wid<1 || empty($fieldV)){
            return false;
        }
        $houseWorkerDb= new HouseWorker();
        $whereTmpArr=array('wid'=>$wid);
        $houseWorkerObj=$houseWorkerDb->get_one($whereTmpArr,'*');
        if($houseWorkerObj && !$houseWorkerObj->isEmpty()) {
            $houseWorker = $houseWorkerObj->toArray();
            if($field=='password'){
                $houseWorkerDb->saveOneData(array('wid'=>$houseWorker['wid']),['password'=>$fieldV]);
                if(isset($houseWorker['hw_id']) && $houseWorker['hw_id']>0){
                    $saveData=array('pwd'=>$fieldV);
                    $this->synEditPropertyVillageWorker($houseWorker['hw_id'],$saveData,$property_id);
                }
            }
        }
        return true;
    }
    
    public function synEditPropertyVillageWorker($hw_id=0,$saveData = array(),$property_id=0)
    {
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $whereArr=array('hw_id'=>$hw_id);
        if($property_id>0){
            $whereArr['property_id']=$property_id;
        }
        $workerObj = $housePropertyVillageWorkerDb->getOneData($whereArr,'*');
        $worker=array();
        if($workerObj && !$workerObj->isEmpty()){
            $worker=$workerObj->toArray();
        }
        fdump_api(['hw_id'=>$hw_id,'property_id'=>$property_id,'worker'=>$worker,'saveData'=>$saveData],'00handlePropertyVillageWorkerRelation',1);
        if(!empty($worker) && isset($worker['hw_id'])){
            if($property_id<1 && $worker['property_id']>0){
                $property_id=$worker['property_id'];
            }
            $saveData['update_time']=time();
            $ret = $housePropertyVillageWorkerDb->saveOneData($whereArr, $saveData);
            sleep(1);
            $this->handlePropertyVillageWorkerRelation($hw_id,$property_id,'edit');
            return $hw_id;
        }
        return false;
    }
    
    public function editPropertyVillageWorker($saveData = array(),$hw_id=0,$property_id=0)
    {
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $whereArr=array('hw_id'=>$hw_id,'property_id'=>$property_id);
        $workerObj = $housePropertyVillageWorkerDb->getOneData($whereArr,'*');
        $worker=array();
        if($workerObj && !$workerObj->isEmpty()){
            $worker=$workerObj->toArray();
        }
        if(empty($worker) || !isset($worker['hw_id']) || $worker['del_time']>1){
            throw new \think\Exception("未查找到数据，无法修改！");
        }
        $saveData['update_time']=time();
        $ret = $housePropertyVillageWorkerDb->saveOneData($whereArr, $saveData);
        sleep(1);
        $this->handlePropertyVillageWorkerRelation($hw_id,$property_id,'edit');
        return $hw_id;
    }
    public function handlePropertyVillageWorkerRelation($hw_id,$property_id=0,$xtype='edit'){
        //处理相关数据
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $whereArr=array('hw_id'=>$hw_id);
        if($property_id>0){
            $whereArr['property_id']=$property_id;
        }
        $workerObj = $housePropertyVillageWorkerDb->getOneData($whereArr,'*');
        if($workerObj && !$workerObj->isEmpty()){
            $nowtime=time();
            $worker=$workerObj->toArray();
            fdump_api(['xtype'=>$xtype,'worker'=>$worker],'00handlePropertyVillageWorkerRelation',1);
            $houseWorkerDb= new HouseWorker();
            $propertyAdminDb=new PropertyAdmin();
            $houseAdminDb=new HouseAdmin();
            $houseProgrammeService= new HouseProgrammeService();
            $departmentUserService= new DepartmentUserService();
            $houseVillageUserBindDb=new HouseVillageUserBind();
            $processSubPlanDb=new ProcessSubPlan();
            $housePropertyVillageWorkerRelation=new HousePropertyVillageWorkerRelation();
            $whereArr=array(['hw_id','=',$hw_id],['property_id','=',$property_id]);
            $whereArr[]=array('del_time','<',1);
            $workerRelationObj=$housePropertyVillageWorkerRelation->getListData($whereArr,'*','id ASC',0,100);
            if($workerRelationObj && !$workerRelationObj->isEmpty()){
                $workerRelation=$workerRelationObj->toArray();
                fdump_api(['xtype'=>$xtype,'workerRelation'=>$workerRelation],'00handlePropertyVillageWorkerRelation',1);
                $houseWorkerUpdate=array('phone'=>$worker['phone'],'name'=>$worker['name'],'job_number'=>$worker['job_number']);
                $houseWorkerUpdate['id_card']=$worker['id_card'];
                $houseWorkerUpdate['gender']=$worker['gender'];
                $houseWorkerUpdate['account']=$worker['account'];
                $houseWorkerUpdate['password']=$worker['pwd'];
                $houseWorkerUpdate['job_create_time']=$worker['job_create_time'];
                foreach ($workerRelation as $rvv){
                    $del_plan_data=array();
                    if($rvv['wid']>0){
                        $whereTmpArr=array('wid'=>$rvv['wid']);
                        $houseWorkerObj=$houseWorkerDb->get_one($whereTmpArr,'*');
                        if($houseWorkerObj && !$houseWorkerObj->isEmpty()){
                            $houseWorker=$houseWorkerObj->toArray();
                            fdump_api(['xtype'=>$xtype,'houseWorker'=>$houseWorker],'00handlePropertyVillageWorkerRelation',1);
                            $updateData=$houseWorkerUpdate;
                            if($worker['remarks'] && empty($houseWorker['remarks'])){
                                
                                $updateData['note']=$worker['remarks'];
                            }
                            if($worker['openid'] && empty($houseWorker['openid'])){
                                $updateData['openid']=$worker['openid'];
                            }
                            if($worker['nickname'] && empty($houseWorker['nickname'])){
                                $updateData['nickname']=$worker['nickname'];
                            }
                            if($worker['status']==4){
                                $updateData['status']=4;
                                $updateData['remarks']=$worker['remarks'];
                                $updateData['note']=$worker['remarks'];
                                $updateData['openid']='';
                                $updateData['nickname']='';
                            }
                            $disable_time=$nowtime;
                            if($worker['disable_time']>1){
                                $disable_time=$worker['disable_time'];
                            }
                            $disable_time_str=date('Y-m-d H:i:s',$disable_time);
                            if($worker['status']==0 && $houseWorker['status']!=4){
                                $updateData['status']=4;
                                $updateData['openid']='';
                                $updateData['note']='物业后台在'.$disable_time_str.'禁用了账号';
                                $updateData['remarks']='物业后台在'.$disable_time_str.'禁用了账号';
                            }
                            if($worker['del_time']>1){
                                $updateData['is_del']=1;
                                $updateData['update_time']=$worker['del_time'];
                                $updateData['status']=4;
                                $updateData['openid']='';
                                $updateData['note']='物业后台在'.date('Y-m-d H:i:s',$worker['del_time']).'删除了账号';
                                $updateData['remarks']='物业后台在'.date('Y-m-d H:i:s',$worker['del_time']).'删除了账号';
                            }
                            $houseWorkerDb->saveOneData(array('wid'=>$houseWorker['wid']),$updateData);
                            fdump_api(['xtype'=>$xtype,'updateData'=>$updateData,'wid'=>$houseWorker['wid']],'00handlePropertyVillageWorkerRelation',1);
                            if($houseWorker['xtype']==1 && $houseWorker['relation_id']>0){
                                //物业员工
                                $propertyAdminObj=$propertyAdminDb->get_one(['id'=>$houseWorker['relation_id'],'property_id'=>$property_id],'*');
                                $propertyAdmin=array();
                                if($propertyAdminObj && !$propertyAdminObj->isEmpty()){
                                    $propertyAdmin=$propertyAdminObj->toArray();
                                }else{
                                    //数据已经不存在了
                                    continue;
                                }
                                if($worker['del_time']>1){
                                    //删除逻辑
                                    $propertyAdminDb->delPropertyAdmin(['id'=>$houseWorker['relation_id'],'property_id'=>$property_id]);
                                    $departmentUserService->syncSingleWorkToWorkWeiXin(['wid' => $houseWorker['wid']]);
                                }else{
                                    //更新数据
                                    $property_admin=array('account'=>$worker['account'],'pwd'=>$worker['pwd'],'realname'=>$worker['name'],'phone'=>$worker['phone']);
                                    if($worker['remarks'] && empty($propertyAdmin['remarks'])){
                                        $property_admin['remarks']=$worker['remarks'];
                                    }
                                    if($worker['status']==0 && $xtype='disable' ){
                                        $property_admin['status']=2;
                                        $property_admin['remarks']='物业后台在'.$disable_time_str.'禁用了账号';
                                    }
                                    if($worker['status']==4){
                                        $property_admin['status']=4;
                                        $property_admin['remarks']=$worker['remarks'];
                                    }
                                    $propertyAdminDb->save_one(['id'=>$houseWorker['relation_id'],'property_id'=>$property_id],$property_admin);
                                }
                                
                            }else if($houseWorker['xtype']==2 && $houseWorker['relation_id']>0){
                                //小区员工
                                if($worker['del_time']>1){
                                    //删除逻辑
                                    $whereTmp=array('id'=>$houseWorker['relation_id'],'village_id'=>$houseWorker['village_id']);
                                    //$houseAdminDb->delHouseAdmin($whereTmp);
                                    $houseAdminDb->save_one($whereTmp,array('status'=>4,'remarks'=>'账号在'.date('Y-m-d H:i:s').'被删除了'));
                                    //同步删除权限方案工作人员标签
                                    $houseProgrammeService->programmeRelationDel([
                                        ['village_id','=',$houseWorker['village_id']],
                                        ['wid','=',$houseWorker['wid']]
                                    ]);
                                    
                                    if($houseWorker['phone'] && !empty($houseWorker['village_id'])){
                                        $bind_user=$houseVillageUserBindDb->getOne([
                                            ['phone','=',$houseWorker['phone']],
                                            ['village_id','=',$houseWorker['village_id']],
                                            ['type','=',4],
                                        ]);
                                        if($bind_user && !$bind_user->isEmpty()){
                                            $bind_user=$bind_user->toArray();
                                            $houseVillageUserBindDb->saveOne(['pigcms_id'=>$bind_user['pigcms_id']],['status'=>4]);
                                            $del_plan_data[]=[
                                                'param'=>serialize([
                                                    'type'=>'org_del_sys_face_door',
                                                    'param'=>[
                                                        'pigcms_id'=>$bind_user['pigcms_id'],
                                                        'village_id'=>$houseWorker['village_id'],
                                                        'bind_user'=>$bind_user
                                                    ]
                                                ]),
                                                'plan_time'     =>  -110,
                                                'space_time'    =>  0,
                                                'add_time'      =>  $nowtime,
                                                'file'          =>  'sub_d5_park',
                                                'time_type'     =>  1,
                                                'unique_id'     =>  'org_del_1_'.$bind_user['pigcms_id'].'_'.$nowtime.'_'.rand(100,999999),
                                                'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
                                            ];
                                        }
                                    }
                                    $departmentUserService->syncSingleWorkToWorkWeiXin(['wid' => $houseWorker['wid']]);
                                }else{
                                    //更新数据 
                                    $house_admin=array('account'=>$worker['account'],'pwd'=>$worker['pwd'],'realname'=>$worker['name'],'phone'=>$worker['phone']);
                                    if($worker['remarks'] && empty($houseWorker['remarks'])){
                                        $house_admin['remarks']=$worker['remarks'];
                                    }
                                    if($worker['status']==0 && $xtype='disable'){
                                        $house_admin['status']=2;
                                        $house_admin['remarks']='物业后台在'.$disable_time_str.'禁用了账号';
                                    }
                                    if($worker['status']==4){
                                        $house_admin['status']=4;
                                        $house_admin['remarks']=$worker['remarks'];
                                    }
                                    $houseAdminDb->save_one(['id'=>$houseWorker['relation_id'],'village_id'=>$houseWorker['village_id']],$house_admin);
                                }
                                
                            }
                            
                        }
                    }
                    if($del_plan_data){
                        $processSubPlanDb->addAll($del_plan_data);
                    }
                }
            }
        }
    }
    
    public function updatePropertyVillageWorker($where = array(), $saveData = array())
    {
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $ret = $housePropertyVillageWorkerDb->saveOneData($where, $saveData);
        return $ret;
    }
    public function getOnePropertyVillageWorker($where = array(),$field="*")
    {
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $ret = $housePropertyVillageWorkerDb->getOneData($where,$field);
        if($ret && !$ret->isEmpty()){
            $ret=$ret->toArray();
            if(isset($ret['pwd'])){
                unset($ret['pwd']); 
            }
            if(isset($ret['job_create_time'])){
                $ret['job_create_time']=$ret['job_create_time']>0 ? date('Y-m-d',$ret['job_create_time']):'';
            }
        }else{
            $ret=array();
        }
        return $ret;
    }
    public function addPropertyVillageWorker($addWorker = array(),$property_id=0)
    {
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $whereArr=array(['account','=',$addWorker['account']],['del_time','<',1],['status','<>',4]);
        $ret = $housePropertyVillageWorkerDb->getOneData($whereArr);
        if($ret && !$ret->isEmpty()){
            throw new \think\Exception("账号【".$addWorker['account']."】已经存在了，请换一个账号！");
        }
        $addWorker['property_id']=$property_id;
        $addWorker['create_time']=time();
        $inset_id = $housePropertyVillageWorkerDb->addOneData($addWorker);
        return $inset_id;
    }
    public function  getPropertyWorkerList($where, $field, $page=0, $page_size=10,$xfrom='',$village_id=0){
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $returnArr=array('list'=>array(),'count'=>0,'total_limit'=>$page_size);
        $dataListObj=$housePropertyVillageWorkerDb->getListData($where,$field,'hw_id DESC',$page,$page_size);
        if($dataListObj && !$dataListObj->isEmpty()){
            $dataList=$dataListObj->toArray();
            $statusArr=array('已禁用','正常','','','已注销');
            $housePropertyVillageWorkerRelation=new HousePropertyVillageWorkerRelation();
            foreach($dataList as $lvv){
                $tmpArr=array('hw_id'=>$lvv['hw_id'],'property_id'=>$lvv['property_id']);
                $tmpArr['name']=$lvv['name'];
                $tmpArr['phone']=$lvv['phone'];
                $tmpArr['openid']=$lvv['openid'];
                $tmpArr['id_card']=$lvv['id_card'];
                $tmpArr['status']=$lvv['status'];
                $tmpArr['status_str']=$statusArr[$lvv['status']];
                $tmpArr['gender']=$lvv['gender'];
                $tmpArr['gender_str']=$lvv['gender']==1 ? '男':($lvv['gender']==2 ? '女' :'未知' );
                $tmpArr['account']=$lvv['account'];
                $tmpArr['job_create_time']=$lvv['job_create_time'];
                $tmpArr['job_create_time_str']=$lvv['job_create_time']>0 ? date('Y-m-d',$lvv['job_create_time']):'';
                $tmpArr['create_time']=$lvv['create_time'];
                $tmpArr['create_time_str']=$lvv['create_time']>0 ? date('Y-m-d H:i:s',$lvv['create_time']):'';
                $tmpArr['remarks']=$lvv['remarks'];
                $tmpArr['job_number']=$lvv['job_number'];
                $tmpArr['is_added']=0;
                if($xfrom){
                    $whereRArr=array();
                    $whereRArr[]=array('hw_id','=',$lvv['hw_id']);
                    $whereRArr[]=array('property_id','=',$lvv['property_id']);
                    $whereRArr[]=array('del_time','<',1);
                    if($xfrom=='property'){
                        $whereRArr[]=array('xtype','=',1);
                        $whereRArr[]=array('village_id','=',0);
                    }else if($xfrom=='village'){
                        $whereRArr[]=array('xtype','=',2);
                        $whereRArr[]=array('village_id','=',$village_id);
                    }
                    $workerRelation= $housePropertyVillageWorkerRelation->getOneData($whereRArr,'*');
                    if($workerRelation && !$workerRelation->isEmpty()){
                        $tmpArr['is_added']=1;
                    }
                }
                $returnArr['list'][]=$tmpArr;
            }
            $count=$housePropertyVillageWorkerDb->getCount($where);
            $returnArr['count']=$count>0 ? $count:0;
        }
        return $returnArr;
    }
    public function getPropertyGroupInfo($group_id=0,$property_id=0,$village_id=0){
        $propertyGroupDb= new PropertyGroup();
        $whereArr=array('id'=>$group_id);
        if($property_id>0){
            $whereArr['property_id']=$property_id;
        }
        if($village_id>0){
            $whereArr['village_id']=$village_id;
        }
        $fieldStr='id,fid,name,type,status,sort,property_id,village_id,section_type,is_del';
        $propertyGroup=$propertyGroupDb->getOne($whereArr,$fieldStr);
        if ($propertyGroup && !$propertyGroup->isEmpty()) {
            $propertyGroup=$propertyGroup->toArray();
            $propertyGroup['typeStr']='';
            $xfrom='';
            if($propertyGroup['type']==99){
                $propertyGroup['typeStr']='物业';
                $xfrom='property';
            }else if($propertyGroup['type']==2){
                $propertyGroup['typeStr']='物业部门';
                $xfrom='property';
            }else if($propertyGroup['type']==1){
                $propertyGroup['typeStr']='小区部门';
                $xfrom='village';
            }else if($propertyGroup['type']==0){
                $propertyGroup['typeStr']='小区';
                $xfrom='village';
            }
            $propertyGroup['xfrom']=$xfrom;
        }else{
            $propertyGroup=array();
        }
        return $propertyGroup;
    }
    public function  disableWorkerAccount($hw_id=0,$property_id=0){

        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $whereArr=array('hw_id'=>$hw_id,'property_id'=>$property_id);
        $workerObj = $housePropertyVillageWorkerDb->getOneData($whereArr,'*');
        $worker=array();
        if($workerObj && !$workerObj->isEmpty()){
            $worker=$workerObj->toArray();
        }
        if(empty($worker) || !isset($worker['hw_id']) || $worker['del_time']>1){
            throw new \think\Exception("未查找到数据，无法修改！");
        }
        $saveData=array('disable_time'=>time(),'status'=>0);
        $ret = $housePropertyVillageWorkerDb->saveOneData($whereArr, $saveData);
        $this->handlePropertyVillageWorkerRelation($hw_id,$property_id,'disable');
        return $hw_id;
    }

    public function  delPropertyWorker($hw_id=0,$property_id=0){

        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $whereArr=array('hw_id'=>$hw_id,'property_id'=>$property_id);
        $workerObj = $housePropertyVillageWorkerDb->getOneData($whereArr,'*');
        $worker=array();
        if($workerObj && !$workerObj->isEmpty()){
            $worker=$workerObj->toArray();
        }
        if(empty($worker) || !isset($worker['hw_id']) || $worker['del_time']>1){
            throw new \think\Exception("未查找到数据，无法修改！");
        }
        $saveData=array('del_time'=>time());
        $ret = $housePropertyVillageWorkerDb->saveOneData($whereArr, $saveData);
        $this->handlePropertyVillageWorkerRelation($hw_id,$property_id,'del');
        return $hw_id;
    }
    /***
     *loginType 1 物业登录 2小区登录
     ***/
    public function addPropertyWorkerToGroup($group_id=0,$selected_hw_ids=array(),$loginType=0,$property_id=0,$village_id=0){
        $propertyGroupDb= new PropertyGroup();
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $housePropertyVillageWorkerRelation=new HousePropertyVillageWorkerRelation();
        $houseWorkerDb=new HouseWorker();
        $propertyAdminDb=new PropertyAdmin();
        $whereArr=array('id'=>$group_id);
        $xfrom='';
        if($loginType==1 && $property_id>0){
            $xfrom='property';
            $whereArr['property_id']=$property_id;
        }
        if($loginType==2 && $village_id>0){
            $xfrom='village';
            $whereArr['village_id']=$village_id;
        }
        $nowtime=time();
        $returnArr=array('err_code'=>0,'err_msg'=>'','fail_count'=>0,'success_count'=>0);
        $err_msg_arr=array();
        $fieldStr='id,fid,name,type,status,sort,property_id,village_id,section_type,section_status,is_del';
        $propertyGroup=$propertyGroupDb->getOne($whereArr,$fieldStr);
        if ($propertyGroup && !$propertyGroup->isEmpty()) {
            $propertyGroup=$propertyGroup->toArray();
            if($propertyGroup['property_id'] && $property_id<1){
                $property_id=$propertyGroup['property_id'];
            }
            if($propertyGroup['village_id'] && $village_id<1){
                $village_id=$propertyGroup['village_id'];
            }
            if($propertyGroup['type']==99){
                $xfrom='property';
            }else if($propertyGroup['type']==2){
                $xfrom='property';
            }else if($propertyGroup['type']==1){
                $xfrom='village';
            }else if($propertyGroup['type']==0){
                $xfrom='village';
            }
        }
        if(empty($propertyGroup)){
            $returnArr['err_code']=1;
            $returnArr['err_msg']='部门组织信息不存在！';
            return $returnArr;
        }
        if($selected_hw_ids && is_array($selected_hw_ids)){
            $selected_hw_ids=array_unique($selected_hw_ids);
            foreach ($selected_hw_ids as $v_id){
                if(empty($v_id)){
                    continue;
                }
                $hw_id=intval($v_id);
                if($hw_id<1){
                    continue;
                }
                $whereArr=array(['hw_id','=',$hw_id],['del_time','<',1]);
                if($property_id>0){
                    $whereArr[]=['property_id','=',$property_id];
                }
                $workerObj = $housePropertyVillageWorkerDb->getOneData($whereArr,'*');
                $worker=array();
                if($workerObj && !$workerObj->isEmpty()){
                    $worker=$workerObj->toArray();
                    if($worker['property_id'] && $property_id<1){
                        $property_id=$worker['property_id'];
                    }
                }
                if(empty($worker)){
                    $returnArr['fail_count']++;
                    continue;
                }
                $whereRArr=array(['hw_id','=',$hw_id],['property_id','=',$property_id]);
                $whereRArr[]=array('del_time','<',1);
                if($xfrom=='village'){
                    $whereRArr[]=array('village_id','=',$village_id);
                    $whereRArr[]=array('xtype','=',2);
                }else if($xfrom=='property'){
                    $whereRArr[]=array('village_id','=',0);
                    $whereRArr[]=array('xtype','=',1);
                }
                $workerRelationObj=$housePropertyVillageWorkerRelation->getOneData($whereRArr,'*');
                if($workerRelationObj && !$workerRelationObj->isEmpty()){
                    $err_msg_arr[]='账号为【'.$worker['account'].'】的'.$worker['name'].'已经添加过了。';
                    $returnArr['fail_count']++;
                    continue;
                }
                $houseWhere=array();
                $houseWhere[]=array('account','=',$worker['account']);
                $houseWhere[]=array('is_del','<',1);
                $houseWhere[]=array('status','<',4);
                if($xfrom=='village'){
                    $houseWhere[]=array('village_id','=',$village_id);
                }else if($xfrom=='property'){
                    $houseWhere[]=array('village_id','=',0);
                    $houseWhere[]=array('property_id','=',$property_id);
                }
                $houseWorkerObj=$houseWorkerDb->get_one($houseWhere,'*');
                if($houseWorkerObj && !$houseWorkerObj->isEmpty()){
                    $err_msg_arr[]='账号为【'.$worker['account'].'】的'.$worker['name'].'已经存在了。';
                    $returnArr['fail_count']++;
                    continue;
                }
                if($xfrom=='property'){
                    $propertyWhere=array('account'=>$worker['account'],'status'=>1);
                    $propertyArr=$propertyAdminDb->get_one($propertyWhere);
                    if ($propertyArr && !$propertyArr->isEmpty()) {
                        $err_msg_arr[]='账号为【'.$worker['account'].'】的'.$worker['name'].'已经存在了！';
                        $returnArr['fail_count']++;
                        continue;
                    }
                }
                $tmpType=-1;
                if($propertyGroup['section_status'] !== null && $propertyGroup['section_status']>=0){
                    $tmpType=intval($propertyGroup['section_status']);
                }
                $house_worker_data=array('village_id'=>$village_id,'reply_count'=>0,'score_all'=>0,	'score_mean'=>0,'num'=>0);
                $house_worker_data['property_id']=$property_id;
                $house_worker_data['phone']=$worker['phone'];
                $house_worker_data['name']=$worker['name'];
                $house_worker_data['openid']=$worker['openid'];
                $house_worker_data['nickname']=$worker['nickname'];
                $house_worker_data['avatar']=$worker['avatar'];
                $house_worker_data['status']=1;
                $house_worker_data['create_time']=$nowtime;
                $house_worker_data['type']=$tmpType;
                $house_worker_data['id_card']=$worker['id_card'];
                $house_worker_data['ic_card']='';
                $house_worker_data['job_number']=$worker['job_number'];
                $house_worker_data['job_create_time']=$worker['job_create_time'];
                $house_worker_data['gender']=$worker['gender'];
                $house_worker_data['note']=$house_worker_data['remarks']=$worker['remarks'];
                $house_worker_data['people_type']=$propertyGroup['type'];
                $house_worker_data['department_id']=$group_id;
                $house_worker_data['account']=$worker['account'];
                $house_worker_data['password']=$worker['pwd'];
                $house_worker_data['hw_id']=$worker['hw_id'];
                $wid=$houseWorkerDb->addData($house_worker_data);
                if($wid<1){
                    $err_msg_arr[]='账号为【'.$worker['account'].'】的'.$worker['name'].'添加失败了！';
                    $returnArr['fail_count']++;
                    continue;
                }
                $relation_id=0;
                if($xfrom=='property'){
                    $property_admin_arr=array();
                    $property_admin_arr['property_id']=$property_id;
                    $property_admin_arr['account']=$worker['account'];
                    $property_admin_arr['pwd']=$worker['pwd'];
                    $property_admin_arr['realname']=$worker['name'];
                    $property_admin_arr['phone']=$worker['phone'];
                    $property_admin_arr['remarks']=$worker['remarks'];
                    $property_admin_arr['time']=$nowtime;
                    $property_admin_arr['wid']=$wid;
                    $relation_id = $propertyAdminDb->addData($property_admin_arr);
                    if($relation_id>0){
                        $houseWorkerDb->editData(array('wid'=>$wid),array('xtype'=>1,'relation_id'=>$relation_id));
                    }
                }else if($xfrom=='village'){
                    
                    
                }
                $worker_relation=array('hw_id'=>$worker['hw_id'],'property_id'=>$property_id);
                $worker_relation['village_id']=$village_id;
                $worker_relation['wid']=$wid;
                if($xfrom=='property'){
                    $worker_relation['xtype']=1;
                    $worker_relation['pad_id']=$relation_id;
                }else if($xfrom=='village'){
                    $worker_relation['xtype']=2;
                    $worker_relation['had_id']=$relation_id;
                }
                $worker_relation['add_time']=$nowtime;
                $housePropertyVillageWorkerRelation->addOneData($worker_relation);
                $returnArr['success_count']++;
            }
        }
        if($err_msg_arr){
            $returnArr['err_code']=1;
            $returnArr['err_msg']=implode('',$err_msg_arr);
        }
        return $returnArr;
    }
    public function  canSynHouseWorkerToProperty($village_id=0,$property_id=0){
        $isCanSyn=0;
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $whereArr=array(['from_village_id','=',$village_id]);
        if($property_id>0){
            $whereArr[]=['property_id','=',$property_id];
        }
        $workerObj = $housePropertyVillageWorkerDb->getOneData($whereArr,'*');
        if($workerObj && !$workerObj->isEmpty()){
            return $isCanSyn;  //已经同步过 不可再以同步
        }
        $houseWorkerDb=new HouseWorker();
        $whereWArr=array(['village_id','=',$village_id]);
        $whereWArr[]=array('hw_id','<',1);
        $whereWArr[]=array('status','=',1);
        $whereWArr[]=array('is_del','<',1);
        $houseWorkerObj=$houseWorkerDb->get_one($whereWArr,'*');
        if($houseWorkerObj && !$houseWorkerObj->isEmpty()){
            $isCanSyn=1;
            return $isCanSyn;
        }
        return $isCanSyn;
    }
    public function  synHouseWorkerToProperty($village_id=0,$property_id=0){
        $houseWorkerDb=new HouseWorker();
        $housePropertyVillageWorkerDb = new HousePropertyVillageWorker();
        $housePropertyVillageWorkerRelation=new HousePropertyVillageWorkerRelation();
        $whereWArr=array(['village_id','=',$village_id]);
        $whereWArr[]=array('hw_id','<',1);
        $whereWArr[]=array('status','=',1);
        $whereWArr[]=array('is_del','<',1);
        $fieldStr='wid,village_id,phone,name,openid,nickname,status,id_card,job_number,job_create_time,gender,property_id,account,password,xtype,relation_id';
        $houseWorkerObj=$houseWorkerDb->getWorkLists($whereWArr,$fieldStr,'wid asc',0,1000);
       
        $houseVillageDb= new HouseVillage();
        $village_name='';
        $houseVillageInfo=$houseVillageDb->getInfo(['village_id'=>$village_id],'village_id,village_name,property_id');
        if($houseVillageInfo && !$houseVillageInfo->isEmpty()){
             $property_id=$houseVillageInfo['property_id'];
             $village_name=$houseVillageInfo['village_name'];
        }
        if($property_id<1){
            throw new \think\Exception("小区所属物业信息错误，无法同步！");
        }
        $returnArr=array('err_code'=>0,'err_msg'=>'','fail_count'=>0,'success_count'=>0);
        $nowtime=time();
        $success_count=0;
        $fail_count=0;
        if($houseWorkerObj && !$houseWorkerObj->isEmpty()){
            $houseWorkers=$houseWorkerObj->toArray();
            //123abc
            foreach ($houseWorkers as $wv){
                $account=$wv['account'] ? $wv['account']:$wv['phone'];
                $account=strval($account);
                $account=trim($account);
                if(empty($account)){
                    $fail_count++;
                    continue;
                }
                if(empty($wv['account'])){
                    $account=$account.'f'.rand(1000,9999);
                }
                $whereArr=array(['account','=',$account]);
                $whereArr[]=array('del_time','<',100);
                $workerObj = $housePropertyVillageWorkerDb->getOneData($whereArr,'*');
                if($workerObj && !$workerObj->isEmpty()){
                    //已经有相同账号数据了
                    $worker=$workerObj->toArray();
                    if($worker['property_id']!=$property_id){
                        //不同物业的
                        $fail_count++;
                        continue;
                    }
                    $villageWorker=array('from_village_id'=>$village_id);
                    $updateWorkerArr=array();
                    if($wv['phone']){
                        $villageWorker['phone']=$wv['phone'];
                    }else{
                        $updateWorkerArr['phone']=$worker['phone'];
                    }
                    if($wv['name']){
                        $villageWorker['name']=$wv['name'];
                    }else{
                        $updateWorkerArr['name']=$worker['name'];
                    }
                    if($wv['openid']){
                        $villageWorker['openid']=$wv['openid'];
                    }elseif($worker['openid']){
                        $updateWorkerArr['openid']=$worker['openid'];
                    }
                    if($wv['nickname']){
                        $villageWorker['nickname']=$wv['nickname'];
                    }elseif($worker['nickname']){
                        $updateWorkerArr['nickname']=$worker['nickname'];
                    }
                    if($wv['job_create_time']){
                        $villageWorker['job_create_time']=$wv['job_create_time'];
                    }else if($worker['job_create_time']){
                        $updateWorkerArr['job_create_time']=$worker['job_create_time'];
                    }
                    if($wv['job_number']){
                        $villageWorker['job_number']=$wv['job_number'];
                    }elseif($worker['job_number']){
                        $updateWorkerArr['job_number']=$worker['job_number'];
                    }
                    if($wv['id_card']){
                        $villageWorker['id_card']=$wv['id_card'];
                    }elseif($worker['id_card']){
                        $updateWorkerArr['id_card']=$worker['id_card'];
                    }
                    if($wv['gender']>0){
                        $villageWorker['gender']=$wv['gender'];
                    }elseif($worker['gender']){
                        $updateWorkerArr['gender']=$worker['gender'];
                    }
                    if($wv['password']){
                        $villageWorker['pwd']=$wv['password'];
                    }elseif($worker['pwd']){
                        $updateWorkerArr['password']=$worker['pwd'];
                    }
                    $villageWorker['extra_info']='来自【'.$village_name.'】小区工作人员数据迁移更新';
                    $housePropertyVillageWorkerDb->saveOneData(array('hw_id'=>$worker['hw_id']),$villageWorker);
                    
                    $updateWorkerArr['hw_id']=$worker['hw_id'];
                    $updateWorkerArr['account']=$account;
                    $houseWorkerDb->saveOneData(array('wid'=>$wv['wid']),$updateWorkerArr);
                    $this->handlePropertyVillageWorkerRelation($worker['hw_id'],$worker['property_id']);
                    $worker_relation=array('hw_id'=>$worker['hw_id'],'property_id'=>$property_id);
                    $worker_relation['village_id']=$village_id;
                    $worker_relation['wid']=$wv['wid'];
                    $worker_relation['xtype']=2;
                    $worker_relation['had_id']=$wv['relation_id'];
                    $worker_relation['add_time']=$nowtime;
                    $housePropertyVillageWorkerRelation->addOneData($worker_relation);
                    $success_count++;
                }else{
                    //添加
                    $villageWorker=array('from_village_id'=>$village_id);
                    $villageWorker['property_id']=$property_id;
                    $villageWorker['create_time']=$nowtime;
                    $villageWorker['remarks']='来自【'.$village_name.'】小区工作人员数据迁移';
                    $villageWorker['extra_info']='来自【'.$village_name.'】小区工作人员数据迁移';
                    $villageWorker['account']=$account;
                    if($wv['phone']){
                        $villageWorker['phone']=$wv['phone'];
                    }
                    if($wv['name']){
                        $villageWorker['name']=$wv['name'];
                    }
                    if($wv['openid']){
                        $villageWorker['openid']=$wv['openid'];
                    }
                    if($wv['nickname']){
                        $villageWorker['nickname']=$wv['nickname'];
                    }
                    if($wv['job_create_time']){
                        $villageWorker['job_create_time']=$wv['job_create_time'];
                    }
                    if($wv['job_number']){
                        $villageWorker['job_number']=$wv['job_number'];
                    }
                    if($wv['id_card']){
                        $villageWorker['id_card']=$wv['id_card'];
                    }
                    if($wv['gender']>0){
                        $villageWorker['gender']=$wv['gender'];
                    }
                    if($wv['password']){
                        $villageWorker['pwd']=$wv['password'];
                    }else{
                        $villageWorker['pwd']=md5('123abc');
                    }
                    $password=$villageWorker['pwd'];
                    $hw_id=$housePropertyVillageWorkerDb->addOneData($villageWorker);
                    if($hw_id>0){
                        $updateWorkerArr=array();
                        $updateWorkerArr['hw_id']=$hw_id;
                        $updateWorkerArr['account']=$account;
                        $updateWorkerArr['password']=$password;
                        $houseWorkerDb->saveOneData(array('wid'=>$wv['wid']),$updateWorkerArr);

                        $worker_relation=array('hw_id'=>$hw_id,'property_id'=>$property_id);
                        $worker_relation['village_id']=$village_id;
                        $worker_relation['wid']=$wv['wid'];
                        $worker_relation['xtype']=2;
                        $worker_relation['had_id']=$wv['relation_id'];
                        $worker_relation['add_time']=$nowtime;
                        $housePropertyVillageWorkerRelation->addOneData($worker_relation);
                        $success_count++;
                    }
                }
            }
        }
        $returnArr['fail_count']=$fail_count;
        $returnArr['success_count']=$success_count;
        return $returnArr;
    }
}