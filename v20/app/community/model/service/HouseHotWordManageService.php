<?php
/**
 * 热词service
**/
namespace app\community\model\service;

use app\community\model\db\HouseHotwordManage;
use app\community\model\db\HouseHotwordUrllist;
use app\community\model\service\HouseVillageService;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseVillageNewsCategory;
use app\community\model\db\PluginMaterialDiyTemplate;
use app\community\model\db\HouseVillageActivity;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseServiceCategory;
use app\community\model\db\AreaStreet;
use app\community\model\db\HouseHotwordMaterialContent;
use app\community\model\db\HouseHotwordMaterialCategory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\Exception;
class HouseHotWordManageService
{


    public function getOneHotwordManage($where,$field=true)
    {
        $dbHouseHotwordManage = new HouseHotwordManage();
        $dataObj = $dbHouseHotwordManage->get_one($where,$field);
        if ($dataObj && !$dataObj->isEmpty()) {
            $data=$dataObj->toArray();
            return $data;
        }
        return array();
    }
    
    public function getOneHotword($where,$field=true)
    {
        $dbHouseHotwordManage = new HouseHotwordManage();
        $dataObj = $dbHouseHotwordManage->get_one($where,$field);
        if ($dataObj && !$dataObj->isEmpty()) {
            $data=$dataObj->toArray();
            $data['audio_url']='';
            $data['word_imgs']=array();
            $data['categoryname']='';
            $data['material_type']='';
            $data['material_info']='';
            
            if(empty($data['xcontent'])){
                $data['xcontent']='';
            }
            if($data['comfrom']==1){
                $houseHotwordMaterialCategory= new HouseHotwordMaterialCategory();
                $houseHotwordMaterialContent= new HouseHotwordMaterialContent();
                if($data['cate_id']>0){
                    $fieldStr = 'cate_id,categoryname,xtype,village_id';
                    $data['material_type']='material_category';
                    $whereArr=array();
                    $whereArr[]=array('cate_id','=',$data['cate_id']);
                    $whereArr[]=array('village_id','=',$data['village_id']);
                    $whereArr[]=array('del_time','<',1);
                    $materialCategoryObj=$houseHotwordMaterialCategory->get_one($whereArr,$fieldStr);
                    if($materialCategoryObj && !$materialCategoryObj->isEmpty()){
                        $materialCategory=$materialCategoryObj->toArray();
                        $data['material_info']=$materialCategory;
                        $data['categoryname']=$materialCategory['categoryname'];
                    }
                }
                if($data['material_id']>0){
                    $data['xcontent']='';
                    $data['material_info']='';
                    $data['material_type']='material_content';
                    $fieldStr='material_id,cate_id,xtype,village_id,xcontent,xname';
                    $whereArr=array();
                    $whereArr[]=array('material_id','=',$data['material_id']);
                    $whereArr[]=array('village_id','=',$data['village_id']);
                    $whereArr[]=array('del_time','<',1);
                    $materialContentObj=$houseHotwordMaterialContent->get_one($whereArr,$fieldStr);
                    if($materialContentObj && !$materialContentObj->isEmpty()){
                        $materialContent=$materialContentObj->toArray();
                        $materialContent['audio_url']='';
                        $materialContent['word_imgs']=array();
                        if($materialContent['xtype']==2){
                            $audio_url=htmlspecialchars_decode($materialContent['xcontent'],ENT_QUOTES);
                            $audio_url=replace_file_domain($audio_url);
                            $data['audio_url']=$audio_url;
                            $materialContent['audio_url']=$audio_url;
                        }elseif ($materialContent['xtype']==3){
                            $img_url=htmlspecialchars_decode($materialContent['xcontent'],ENT_QUOTES);
                            $tmpimgs=explode(',',$img_url);
                            $img_url_arr=array();
                            foreach ($tmpimgs as $imgv){
                                $newimgsrc=replace_file_domain($imgv);
                                $img_url_arr[]=$newimgsrc;
                            }
                            $data['word_imgs']=$img_url_arr;
                            $materialContent['word_imgs']=$img_url_arr;
                        }elseif ($materialContent['xtype']==1){
                            $data['xcontent']=htmlspecialchars_decode($materialContent['xcontent'],ENT_QUOTES);
                            $materialContent['xcontent']=$data['xcontent'];
                        }

                        $data['material_info']=$materialContent;
                    }
                }
            }else{
                if($data['xtype']==2){
                    $data['audio_url']=$data['xcontent'] ? replace_file_domain($data['xcontent']):'';
                }else if($data['xtype']==3){
                    $tmpimgs=explode(',',$data['xcontent']);
                    foreach ($tmpimgs as $imgv){
                        $newimgsrc=replace_file_domain($imgv);
                        $data['word_imgs'][]=$newimgsrc;
                    }
                }
            }
            $data['xtype']=strval($data['xtype']);
            $data['comfrom']=intval($data['comfrom']);
            $data['urllist']=array();
            if($data['xtype']<1){
                $dbHouseHotwordUrllist = new HouseHotwordUrllist();
                $whereArr=array('word_id'=>$data['id'],'village_id'=>$data['village_id']);
                $urllistObj=$dbHouseHotwordUrllist->getAll($whereArr);
                if($urllistObj && !$urllistObj->isEmpty()){
                    $data['urllist']=$urllistObj->toArray();
                }
            }
        }else{
            $data = [];
        }
        return $data;
    }

    public function  getHotwordList($whereArr,$field='*',$page=1,$limit=20)
    {
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $dbHouseHotwordManage = new HouseHotwordManage();
        $count = $dbHouseHotwordManage->getCount($whereArr);
        if ($count > 0) {
            $dataArr['count'] = $count;
            $resObj = $dbHouseHotwordManage->getHotwordLists($whereArr, $field,'id desc', $page, $limit);
            if (!empty($resObj) && !$resObj->isEmpty()) {
                $res=$resObj->toArray();
                foreach ($res as $kk => $vv) {
                    $res[$kk]['status']=intval($vv['status']);
                    $res[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                    $res[$kk]['update_time_str']= $vv['update_time']>0 ? date('Y-m-d H:i:s',$vv['update_time']):'';
                    $res[$kk]['status_str']= $vv['status']>0 ? '已启用':'已禁用';
                    $res[$kk]['xtype_str']='功能链接';
                    if($vv['xtype']==1){
                        $res[$kk]['xtype_str']='文字回复';
                    }else if($vv['xtype']==2){
                        $res[$kk]['xtype_str']='音频回复';
                    }else if($vv['xtype']==3){
                        $res[$kk]['xtype_str']='图片回复';
                    }
                }
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }
    public function getHotWordTipsByVillageId($village_id=0){
        $tips='';
        if($village_id>0){
            $dbHouseHotwordManage = new HouseHotwordManage();
            $whereArr=array(['village_id', '=', $village_id]);
            $whereArr[] = ['del_time', '<', 1];
            $resObj = $dbHouseHotwordManage->getHotwordLists($whereArr, 'wordname','', 1, 300);
            if (!empty($resObj) && !$resObj->isEmpty()) {
                $res=$resObj->toArray();
                $total=count($res);
                $randArr=array();
                if($total>1){
                    $kkArr=array_rand($res,2);
                    $randArr[]=$res[$kkArr[0]];
                    $randArr[]=$res[$kkArr[1]];
                }else{
                    $randArr=$res;
                }
                foreach ($randArr as $kk => $vv) {
                    
                    $tips .=!empty($tips) ? '，“'.$vv['wordname'].'”':'“'.$vv['wordname'].'”';
                }
            }
        }
        return $tips;
    }

    public function getHotWordIdentifyInfo($village_id = 0, $keywords = '')
    {
        $searchList = array('func_url' => array(), 'text_reply' => array(), 'audio_reply' => array(), 'img_reply' => array(), 'tcount' => 0);
        if ($village_id > 0 && !empty($keywords)) {
            $dbHouseHotwordManage = new HouseHotwordManage();
            $whereArr = array(['village_id', '=', $village_id]);
            $whereArr[] = ['del_time', '<', 1];
            $whereArr[] = ['status', '=', 1];
            /**
             *instr(str,substr) locate（substr,str）
             **/
            $whereRaw = " (wordname like '%" . $keywords . "%' OR LOCATE(wordname,'" . $keywords . "')) ";
            //$whereRaw=" (wordname like '%".$keywords."%' OR instr('".$keywords."',wordname)) ";
            $tcount = 0;
            $resObj = $dbHouseHotwordManage->getHotwordLists($whereArr, '*', 'id desc', 0, 0, $whereRaw);
            if (!empty($resObj) && !$resObj->isEmpty()) {
                $res = $resObj->toArray();
                $dbHouseHotwordUrllist = new HouseHotwordUrllist();
                $houseHotwordMaterialCategory = new HouseHotwordMaterialCategory();
                $houseHotwordMaterialContent = new HouseHotwordMaterialContent();
                foreach ($res as $kk => $vv) {
                    if ($vv['comfrom'] == 1) {
                        $vv['xcontent'] = '';
                        /***素材库选择素材****/
                        if ($vv['material_id'] > 0) {
                            $fieldStr = 'material_id,cate_id,xtype,village_id,xcontent,xname';
                            $whereArr = array();
                            $whereArr[] = array('material_id', '=', $vv['material_id']);
                            $whereArr[] = array('village_id', '=', $vv['village_id']);
                            $whereArr[] = array('xtype', '=', $vv['xtype']);
                            $whereArr[] = array('del_time', '<', 1);
                            $materialContentObj = $houseHotwordMaterialContent->get_one($whereArr, $fieldStr);
                            if ($materialContentObj && !$materialContentObj->isEmpty()) {
                                $materialContent = $materialContentObj->toArray();
                                $vv['showtitle']= $materialContent['xname'];
                                $vv['xcontent'] = $materialContent['xcontent'];
                            }
                        } elseif ($vv['cate_id'] > 0) {
                            $fieldStr = 'material_id,cate_id,xtype,village_id,xcontent,xname';
                            $whereArr = array();
                            $whereArr[] = array('cate_id', '=', $vv['cate_id']);
                            $whereArr[] = array('village_id', '=', $vv['village_id']);
                            $whereArr[] = array('xtype', '=', $vv['xtype']);
                            $whereArr[] = array('del_time', '<', 1);
                            $isgetmaterial = false;
                            $materialContentObj = $houseHotwordMaterialContent->getAllMaterial($whereArr, 'material_id');
                            if ($materialContentObj && !$materialContentObj->isEmpty()) {
                                $materialContentArr = $materialContentObj->toArray();
                                if (!empty($materialContentArr)) {
                                    $materialContentKey = array_rand($materialContentArr);  //随机取一条数据 直接返回key
                                    $materialContentOne = $materialContentArr[$materialContentKey];
                                    $whereArr[] = array('material_id', '=', $materialContentOne['material_id']);
                                    $isgetmaterial = true;
                                }
                            }
                            if (!$isgetmaterial) {
                                continue;
                            }
                            $materialContentOneObj = $houseHotwordMaterialContent->get_one($whereArr, $fieldStr);
                            if ($materialContentOneObj && !$materialContentOneObj->isEmpty()) {
                                $materialContent = $materialContentOneObj->toArray();
                                $vv['showtitle']= $materialContent['xname'];
                                $vv['xcontent'] = $materialContent['xcontent'];
                            }
                        } else {
                            continue;
                        }
                    }

                    if ($vv['xtype'] == 0) {
                        $whereArr = array('word_id' => $vv['id'], 'village_id' => $vv['village_id']);
                        $urllistObj = $dbHouseHotwordUrllist->getAll($whereArr);
                        if ($urllistObj && !$urllistObj->isEmpty()) {
                            $urllist = $urllistObj->toArray();
                            $func_url = array();
                            foreach ($urllist as $uvv) {
                                $tmpArr = array();
                                if(!empty($uvv['showtitle']) && !empty($uvv['jumpurl'])){
                                    $tmpArr['label'] = $uvv['showtitle'];
                                    $tmpArr['url'] = $uvv['jumpurl'];
                                    $tmpArr['value'] = $uvv['word_id'] . '_' . $uvv['id'];
                                    $searchList['func_url'][] = $tmpArr;
                                }
                            }
                            $tcount++;
                        }
                    } else if ($vv['xtype'] == 1) {
                        /***文本***/
                        if (empty($vv['xcontent'])) {
                            continue;
                        }
                        $text_reply = htmlspecialchars_decode($vv['xcontent'], ENT_QUOTES);
                        $searchList['text_reply'][] = ['title'=>'','value' => $text_reply];
                        $tcount++;
                    } else if ($vv['xtype'] == 2) {
                        /**音频**/
                        if (empty($vv['xcontent'])) {
                            continue;
                        }
                        $audio_reply = htmlspecialchars_decode($vv['xcontent'], ENT_QUOTES);
                        $audio_reply = replace_file_domain($audio_reply);
                        $searchList['audio_reply'][] = ['title'=>$vv['showtitle'],'value' => $audio_reply];
                        $tcount++;
                    } else if ($vv['xtype'] == 3) {
                        /**图片**/
                        if (empty($vv['xcontent'])) {
                            continue;
                        }
                        $img_reply = htmlspecialchars_decode($vv['xcontent'], ENT_QUOTES);
                        $tmpimgs = explode(',', $img_reply);
                        $img_reply_arr = array();
                        foreach ($tmpimgs as $imgv) {
                            $newimgsrc = replace_file_domain($imgv);
                            $img_reply_arr[] = $newimgsrc;
                        }
                        $searchList['img_reply'][] = ['title'=>'','value' => $img_reply_arr];
                        $tcount++;
                    }

                }
                $searchList['tcount'] = $tcount;
            }
        }
        return $searchList;
    }
    
    public function  getHotwordAllList($whereArr,$field='*',$page=1,$limit=20)
    {
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $dbHouseHotwordManage = new HouseHotwordManage();
        $count = $dbHouseHotwordManage->getCount($whereArr);
        if ($count > 0) {
            $dataArr['count'] = $count;
            $resObj = $dbHouseHotwordManage->getHotwordLists($whereArr, $field,'id desc', $page, $limit);
            if (!empty($resObj) && !$resObj->isEmpty()) {
                $res=$resObj->toArray();
                $dbHouseHotwordUrllist = new HouseHotwordUrllist();
                foreach ($res as $kk => $vv) {
                    $res[$kk]['add_time_str']=date('Y-m-d H:i:s',$vv['add_time']);
                    $res[$kk]['update_time_str']= $vv['update_time']>0 ? date('Y-m-d H:i:s',$vv['update_time']):'';
                    $res[$kk]['default_str']= $vv['is_default']>0 ? '默认关键词':'';
                    $res[$kk]['urllist']=array();
                    $whereArr=array('word_id'=>$vv['id'],'village_id'=>$vv['village_id']);
                    $urllistObj=$dbHouseHotwordUrllist->getAll($whereArr);
                    if($urllistObj && !$urllistObj->isEmpty()){
                        $res[$kk]['urllist']=$urllistObj->toArray();
                    }
                }
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }
    
    public function addHotword($addArr = array(),$wordurllist=array())
    {
        $dbHouseHotwordManage = new HouseHotwordManage();
        $idd=0;
        if($addArr){
            $dbHouseHotwordUrllist = new HouseHotwordUrllist();
            $nowtime=time();
            $addArr['add_time']=$nowtime;
            $addArr['update_time']=$nowtime;
            $idd=$dbHouseHotwordManage->addData($addArr);
            if($wordurllist && is_array($wordurllist)){
                foreach ($wordurllist as $uuv){
                    $uuv['word_id']=$idd;
                    $uuv['update_time']=$nowtime;
                    $dbHouseHotwordUrllist->addData($uuv);
                }
            }
        }
        return $idd;
    }
    //更新数据
    public function updateHotwordAndUrl($where = array(),$updateArr=array(), $wordurllist = array())
    {
        $dbHouseHotwordManage = new HouseHotwordManage();
        $dataObj = $dbHouseHotwordManage->get_one($where);
        $hotword='';
        if($dataObj && !$dataObj->isEmpty()){
            $hotword=$dataObj->toArray();
        }
        if(empty($hotword)){
            throw new \think\Exception("修改失败，关键词信息不存在！");
        }
        if($updateArr){
            $nowtime=time();
            $dbHouseHotwordUrllist = new HouseHotwordUrllist();
            $updateArr['update_time']=$nowtime;
            $idd=$dbHouseHotwordManage->editData($where,$updateArr);
            if($hotword['xtype']==0){
                $whereArr=array('word_id'=>$hotword['id'],'village_id'=>$hotword['village_id']);
                $dbHouseHotwordUrllist->delData($whereArr);
            }
            if($wordurllist && is_array($wordurllist)){
                foreach ($wordurllist as $uuv){
                    $uuv['word_id']=$hotword['id'];
                    $uuv['update_time']=$nowtime;
                    $dbHouseHotwordUrllist->addData($uuv);
                }
            }
        }
        return $hotword;
    }
    //设置 默认 取消默认
    public function  setHotWordDataStatus($village_id=0,$word_id=0,$status=0){
        
        $dbHouseHotwordManage = new HouseHotwordManage();
        $whereArr=array('id'=>$word_id,'village_id'=>$village_id);
        $dataObj = $dbHouseHotwordManage->get_one($whereArr);
        $hotword='';
        if($dataObj && !$dataObj->isEmpty()){
            $hotword=$dataObj->toArray();
        }
        if(empty($hotword)){
            throw new \think\Exception("默认操作失败，关键词信息不存在！");
        }
        $updateArr=array('status'=>$status);
        $dbHouseHotwordManage->editData($whereArr,$updateArr);
        return $hotword;
    }
    
    public function  deleteHotWord($village_id=0,$word_id=0){
        $dbHouseHotwordManage = new HouseHotwordManage();
        $whereArr=array('id'=>$word_id,'village_id'=>$village_id);
        $dataObj = $dbHouseHotwordManage->get_one($whereArr);
        $hotword='';
        if($dataObj && !$dataObj->isEmpty()){
            $hotword=$dataObj->toArray();
        }
        if(empty($hotword)){
            throw new \think\Exception("删除失败，关键词信息不存在！");
        }
        $updateArr=array('del_time'=>time());
        $dbHouseHotwordManage->editData($whereArr,$updateArr);
        return $hotword;
    }
    
    public function getFuncApplication($village_id=0,$adminUser=array())
    {
        $house_village_service = new HouseVillageService();
        //$base_url = $house_village_service->base_url;
        $base_url = '/packapp/village/';
        $site_url=cfg('site_url');
        $site_url=rtrim($site_url,'/');
        $url=$site_url.$base_url;
        $app_arr=array();
        $is_close_help_info=0;
        $village_info=array();
        if($village_id>0){
            $village_info_obj=$house_village_service->getHouseVillageInfo(['village_id'=>$village_id]);
            if($village_info_obj && !$village_info_obj->isEmpty()){
                $village_info=$village_info_obj->toArray();
            }
            $village_info_extend=$house_village_service->getHouseVillageInfoExtend(['village_id'=>$village_id],'help_info');
            if($village_info_extend && $village_info_extend['help_info']){
                $help_info=unserialize($village_info_extend['help_info']);
                if($help_info && isset($help_info['is_close'])){
                    $is_close_help_info=$help_info['is_close'];
                }
            }
        }
        $app_arr[] = ['title'=>'小区列表','url'=>$url.'pages/village/my/villagelist','sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'常用电话列表','url'=>$url.'pages/village/commonly_used/house_phone?village_id='.$village_id,'sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'小区新闻分类','url'=>$url.'pages/village/index/notice?village_id=' . $village_id,'sub'=>true,'module'=>'HousevillageNewsCatelist'];
        $app_arr[] = ['title'=>'装修申请单','url'=>'','sub'=>true,'module'=>'RenovationApplyList'];
        $app_arr[] = ['title'=>'个人中心','url'=>$url.'pages/village_menu/my','sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'绑定家属','url'=>$url.'pages/village/my/bindFamily?village_id=' . $village_id,'sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'绑定家属列表','url'=>$url.'pages/village/my/myVillage','sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'便民服务','url'=>$url.'pages/village_menu/convenient?village_id='.$village_id,'sub'=>true,'module'=>'HouseserviceserviceCategory'];
        $app_arr[] = ['title'=>'小区活动','url'=>$site_url.'/wap.php?g=Wap&c=House&a=village_activitylist&village_id='.$village_id,'sub'=>true,'module'=>'HousevillageActivity'];
        $app_arr[] = ['title'=>'小区管家','url'=>$site_url.'/wap.php?g=Wap&c=House&a=village_manager_list&village_id='.$village_id,'sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'我的小区','url'=>$url.'pages/village/my/myVillage','sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'工单','url'=>$url.'pages/houseMeter/workOrder/workOrder?village_id='.$village_id,'sub'=>true,'module'=>'WorkOrder'];
        //todo 寻求帮助
        if($is_close_help_info<1){
            $app_arr[] = ['title'=>'寻求帮助','url'=>$url.'pages/houseMeter/workOrder/dayNight?village_id='.$village_id,'sub'=>false,'module'=>''];
        }
        
        $app_arr[] = ['title'=>'缴费','url'=>$url.'pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id='.$village_id,'sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'访客登记','url'=>$url.'pages/village_my/visitorRegistration?village_id='.$village_id,'sub'=>false,'module'=>''];
        $houseFaceDeviceDb= new HouseFaceDevice();
        $face_device_obj=$houseFaceDeviceDb->getOne(array('device_type' => 26),'device_id');
        if($face_device_obj && !$face_device_obj->isEmpty()){
            $face_device=$face_device_obj->toArray();
            if($face_device && $face_device['device_id']){
                $app_arr[] = ['title'=>'扫码开门','url'=>$url.'pages/houseMeter/VisitorInvitation/visitorOpenDoor?type=2','sub'=>false,'module'=>''];
                $app_arr[] = ['title'=>'访客邀请','url'=>$url.'pages/houseMeter/VisitorInvitation/visitorPass','sub'=>false,'module'=>''];
            }
        }
        
        $app_arr[] = ['title'=>'邻里','url'=>$url.'pages/village_menu/resident','sub'=>false,'module'=>''];
        $app_arr[] = ['title'=>'人脸识别门禁','url'=>$url.'pages/village_my/faceAccessControl','sub'=>false,'module'=>''];
        if($village_info && $village_info['street_id']>0){
            $app_arr[] = ['title'=>'街道首页','url'=>$url.'pages/street/street_index?area_street_id='.$village_info['street_id'].'&linkFrom=street','sub'=>false,'module'=>''];
        }
        if($village_info && $village_info['community_id']>0){
            $app_arr[] = ['title'=>'社区首页','url'=>$url.'pages/street/street_index?area_street_id='.$village_info['community_id'].'&linkFrom=community','sub'=>false,'module'=>''];
        }
        $is_pile=cfg('pile');
        if($is_pile){
            $app_arr[] = ['title'=>'充电桩管理','url'=>$url.'pages/village/smartCharge/chargeList','sub'=>false,'module'=>''];
        }
        $app_arr[] = ['title'=>'智能停车','url'=>$url.'pages/parkingLot/index','sub'=>false,'module'=>''];
        $hawkEyeName = cfg('hawkEyeName');
        if(empty($hawkEyeName)){
            $hawkEyeName='鹰眼服务';
        }
        $hawkEyeUrl = $url. "pages/village_my/hawkEye?url_type=village&village_id=".$village_id;
        $app_arr[] = ['title'=>$hawkEyeName,'url'=>$hawkEyeUrl,'sub'=>false,'module'=>''];
        if($village_info && !empty($village_info['touch_alarm_phone'])) {
            $app_arr[] = ['title' => '一键报警', 'url' => $url . 'pages/village/index/oneClickAlarm', 'sub' => false, 'module' => ''];
        }
        $data=array();
        $data['list'] = $app_arr;
        return $data;
    }
    
    public function getFuncApplicationDetail($village_id=0,$xtype='',$page=1){
        $house_village_service = new HouseVillageService();
        //$base_url = $house_village_service->base_url;
        $base_url = '/packapp/village/';
        $site_url=cfg('site_url');
        $site_url=rtrim($site_url,'/');
        $url=$site_url.$base_url;
        $limit=10;
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        if($xtype=='HousevillageNewsCatelist'){
            $houseVillageNewsCategory=new HouseVillageNewsCategory();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('cat_status','=',1);
            $newsCategoryObj=$houseVillageNewsCategory->getLists($whereArr,'*',$page,$limit);
            if($newsCategoryObj && !$newsCategoryObj->isEmpty()){
                $newsCategory=$newsCategoryObj->toArray();
                if($newsCategory){
                    $count=$houseVillageNewsCategory->getCount($whereArr);
                    $dataArr['count']=$count;
                    $list=array();
                    foreach ($newsCategory as $vv){
                        $list[]=[
                            'id'=>$vv['cat_id'],
                            'title'=>$vv['cat_name'],
                            'url'=>$url.'pages/village/index/notice?village_id=' . $village_id.'&cat_id='.$vv['cat_id']
                        ];
                    }
                    $dataArr['list']=$list;
                }
            }
        } elseif ($xtype == 'RenovationApplyList') {
            $pluginMaterialDiyTemplate = new PluginMaterialDiyTemplate();
            $whereArr = array();
            $whereArr[] = array('from_id', '=', $village_id);
            $whereArr[] = array('template_status', '=', 0);
            $whereArr[] = array('from', '=', 'house');
            $materialDiyTemplateObj = $pluginMaterialDiyTemplate->getList($whereArr, '*','template_id desc', $page, $limit);
            if ($materialDiyTemplateObj && is_object($materialDiyTemplateObj) && !$materialDiyTemplateObj->isEmpty()) {
                $materialDiyTemplate = $materialDiyTemplateObj->toArray();
            } else {
                $materialDiyTemplate = $materialDiyTemplateObj;
            }
            if ($materialDiyTemplate) {
                $count = $pluginMaterialDiyTemplate->getCount($whereArr);
                $dataArr['count'] = $count;
                $list = array();
                foreach ($materialDiyTemplate as $vv) {
                    $list[] = [
                        'id' => $vv['template_id'],
                        'title' => $vv['template_title'],
                        'url' => $site_url . '/wap.php?g=Wap&c=Renovation&a=user_know&template_id=' . $vv['template_id'] . '&village_id=' . $village_id
                    ];
                }
                $dataArr['list'] = $list;
            }
        }elseif($xtype == 'HouseserviceserviceCategory'){
            $houseServiceCategory=new HouseServiceCategory();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('parent_id','>',0);
            $whereArr[]=array('status','=',1);
            $houseServiceCategoryObj=$houseServiceCategory->getList($whereArr,'id,cat_name,cat_url','id desc',$page,$limit);
            if($houseServiceCategoryObj && !$houseServiceCategoryObj->isEmpty()){
                $serviceCategory=$houseServiceCategoryObj->toArray();
                if($serviceCategory){
                    $count=$houseServiceCategory->getCount($whereArr);
                    $dataArr['count']=$count;
                    $list=array();
                    foreach ($serviceCategory as $vv){
                        $list[]=[
                            'id'=>$vv['id'],
                            'title'=>$vv['cat_name'],
                            'url'=>$site_url . '/wap.php?g=Wap&c=Houseservice&a=cat_list&id=' . $vv['id'] . '&village_id=' . $village_id
                        ];
                    }
                    $dataArr['list']=$list;
                }
            }
        }elseif($xtype == 'HousevillageActivity'){
            $houseVillageActivity=new HouseVillageActivity();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('status','=',1);
            $villageActivityObj=$houseVillageActivity->getList($whereArr,'*','id desc',$page,$limit);
            if($villageActivityObj && !$villageActivityObj->isEmpty()){
                $villageActivity=$villageActivityObj->toArray();
                if($villageActivity){
                    $count=$houseVillageActivity->getCount($whereArr);
                    $dataArr['count']=$count;
                    $list=array();
                    foreach ($villageActivity as $vv){
                        $list[]=[
                            'id'=>$vv['id'],
                            'title'=>$vv['title'],
                            'url'=>$url.'pages/village/index/activitySignup?village_id='.$vv['village_id'].'&id='.$vv['id']
                        ];
                    }
                    $dataArr['list']=$list;
                }
            }
        }elseif($xtype == 'WorkOrder'){
            $houseNewRepairCate=new HouseNewRepairCate();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('parent_id','=',0);
            $whereArr[]=array('status','=',1);
            $houseNewRepairCateObj=$houseNewRepairCate->getList($whereArr,'id,cate_name',$page,$limit,'id desc');
            if($houseNewRepairCateObj && !$houseNewRepairCateObj->isEmpty()){
                $repairCate=$houseNewRepairCateObj->toArray();
                if($repairCate){
                    $count=$houseNewRepairCate->getCount($whereArr);
                    $dataArr['count']=$count;
                    $list=array();
                    foreach ($repairCate as $vv){
                        $list[]=[
                            'id'=>$vv['id'],
                            'title'=>$vv['cate_name'],
                            'url'=>$url.'pages/houseMeter/workOrder/eventList?village_id=' . $village_id.'&category_id='.$vv['id'].'&subject_name='.$vv['cate_name']
                        ];
                    }
                    $dataArr['list']=$list;
                }
            }
        }
        return $dataArr;
    }
    
    /**
     * 获取街道社区数据
     */
    public function  getAreaStreetCommunity($xtype='',$pid=0){
        $dataArr=['list' => array()];
        if($pid<1){
            return $dataArr;
        }
        $whereArr=array();
        $whereArr[]=array('is_open','=',1);
        $whereArr[] = array('area_pid','=',$pid);
        if($xtype=='street'){
            $whereArr[]=array('area_type','=',0);
        }elseif($xtype=='community'){
            $whereArr[]=array('area_type','=',1);
        }else{
            return $dataArr;
        }
        $areaStreet=new AreaStreet();
        $areaObj=$areaStreet->getLists($whereArr,'area_id,area_name',0,0,'area_sort DESC,area_id ASC');
        if($areaObj && !$areaObj->isEmpty()){
            $dataArr['list']=$areaObj->toArray();
        }
        return $dataArr;
    }

    public function  getAllVillages($condition_where,$fieldStr,$page, $limit, $order='village_id DESC'){
        $houseVillageService = new HouseVillageService();

        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $list = $houseVillageService->getList($condition_where, $fieldStr, $page, $limit, $order,1);
        if ($list && !$list->isEmpty()) {
            $list = $list->toArray();
            foreach ($list as $kk => $vv) {
                $list[$kk]['key'] = $vv['village_id'];
            }
            $dataArr['list'] = $list;
            $count = $houseVillageService->getVillageNum($condition_where);
            $dataArr['count'] = $count;
        }
        return $dataArr;
    }
    
    /**
     * 从一个小区复制
     */
    public function  copyAvillageKeywords($village_id,$other_village_id){
        $dataArr = ['msg' => '操作成功！','hotWord'=>array()];
        if($village_id<1 || $other_village_id<1 || $other_village_id==$village_id){
            return $dataArr;
        }
        $whereArr = array(['village_id', '=', $other_village_id]);
        $whereArr[] = ['del_time', '<', 1];
        $whereArr[] = ['xtype', '<', 1];
        $whereArr[] = ['status', '=', 1];
        
        $dbHouseHotwordManage = new HouseHotwordManage();
        $resObj = $dbHouseHotwordManage->getHotwordLists($whereArr, "*",'id ASC', 0);
        $nowtime=time();
        $dbHouseHotwordUrllist = new HouseHotwordUrllist();
        if($resObj && !$resObj->isEmpty()){
            $hotwords=$resObj->toArray();
            if($hotwords){
                foreach ($hotwords as $hvv){
                    unset($hvv['is_default']);
                    $word_id=$hvv['id'];
                    unset($hvv['id']);
                    unset($hvv['village_id']);
                    $whereMyArr=array(['village_id', '=', $village_id]);
                    $whereMyArr[] = ['del_time', '<', 1];
                    $whereMyArr[] = ['copy_id', '=', $word_id];
                    $whereMyArr[] = ['xtype', '<', 1];
                    $oneObj=$dbHouseHotwordManage->get_one($whereMyArr);
                    if($oneObj && !$oneObj->isEmpty()){
                        $oneword=$oneObj->toArray();
                        if($oneword){
                            $new_word_id=$oneword['id'];
                            //以前复制过了 不做处理
                        }
                    }else {
                        $addArr=array();
                        $addArr['wordname']=$hvv['wordname'];
                        $addArr['village_id']=$village_id;
                        $addArr['showtitle']=$hvv['showtitle'];
                        $jumpurl=urldecode($hvv['jumpurl']);
                        $jumpurl=htmlspecialchars_decode($hvv['jumpurl'],ENT_QUOTES);
                        //详细的带自身id的无法复制
                        if(false !== strstr($jumpurl, 'index/notice') && false !== strstr($jumpurl, 'cat_id=')){
                            continue;
                        }
                        if(false !== strstr($jumpurl, 'a=user_know') && false !== strstr($jumpurl, 'template_id=')){
                            continue;
                        }
                        if(false !== strstr($jumpurl, 'a=cat_list') && false !== strstr($jumpurl, 'id=')){
                            continue;
                        }
                        if(false !== strstr($jumpurl, 'index/activitySignup') && false !== strstr($jumpurl, 'id=')){
                            continue;
                        }
                        if(false !== strstr($jumpurl, 'workOrder/eventList') && false !== strstr($jumpurl, 'category_id=')){
                            continue;
                        }
                        $addArr['jumpurl']=$this->urlVillageIdReplace($village_id,$hvv['jumpurl']);
                        $addArr['copy_id']=$word_id;
                        $addArr['add_time']=$nowtime;
                        $addArr['update_time']=$nowtime;
                        $new_word_id=$dbHouseHotwordManage->addData($addArr);
                        $dataArr['hotWord'][]=$hvv['wordname'];
                        //拷贝链接
                        $whereArr=array('word_id'=>$word_id,'village_id'=>$other_village_id);
                        $urllistObj=$dbHouseHotwordUrllist->getAll($whereArr);
                        if($urllistObj && !$urllistObj->isEmpty()){
                            $urllist=$urllistObj->toArray();
                            if($urllist){
                                foreach ($urllist as $vv){
                                    $jumpurl=urldecode($vv['jumpurl']);
                                    $jumpurl=htmlspecialchars_decode($vv['jumpurl'],ENT_QUOTES);
                                    //详细的带自身id的无法复制
                                    if(false !== strstr($jumpurl, 'index/notice') && false !== strstr($jumpurl, 'cat_id=')){
                                        continue;
                                    }
                                    if(false !== strstr($jumpurl, 'a=user_know') && false !== strstr($jumpurl, 'template_id=')){
                                        continue;
                                    }
                                    if(false !== strstr($jumpurl, 'a=cat_list') && false !== strstr($jumpurl, 'id=')){
                                        continue;
                                    }
                                    if(false !== strstr($jumpurl, 'index/activitySignup') && false !== strstr($jumpurl, 'id=')){
                                        continue;
                                    }
                                    if(false !== strstr($jumpurl, 'workOrder/eventList') && false !== strstr($jumpurl, 'category_id=')){
                                        continue;
                                    }
                                    $tmpUrlArr=$vv;
                                    $tmpUrlArr['jumpurl']=$this->urlVillageIdReplace($village_id,$tmpUrlArr['jumpurl']);
                                    $tmpUrlArr['copy_id']=$tmpUrlArr['id'];
                                    unset($tmpUrlArr['id']);
                                    $tmpUrlArr['word_id']=$new_word_id;
                                    $tmpUrlArr['village_id']=$village_id;
                                    $tmpUrlArr['update_time']=$nowtime;
                                    $dbHouseHotwordUrllist->addData($tmpUrlArr);
                                }
                            }
                        }
                    }
                }
                return $dataArr;
            }else{
                 $dataArr['msg']='此小区没有关键词数据！';
                return $dataArr;
            }
        }
        $dataArr['msg']='此小区没有关键词数据！';
        return $dataArr;
    }

    public function urlVillageIdReplace($village_id=0,$url='')
    {

        if($village_id<1 || empty($url)){
            return $url;
        }
        $url=urldecode($url);
        $url=htmlspecialchars_decode($url,ENT_QUOTES);
        $house_village_service = new HouseVillageService();
        $village_info = array();
        if ($village_id > 0) {
            $village_info_obj = $house_village_service->getHouseVillageInfo(['village_id' => $village_id]);
            if ($village_info_obj && !$village_info_obj->isEmpty()) {
                $village_info = $village_info_obj->toArray();
            }
            if (false !== strstr($url, 'village_id=')) {
                $url = preg_replace('/village_id=(\d+)/i', 'village_id=' . $village_id, $url);
            }
            if (false !== strstr($url, 'linkFrom=street')) {
                $url = preg_replace('/area_street_id=(\d+)/i', 'area_street_id=' . $village_info['street_id'], $url);
            }
            if (false !== strstr($url, 'linkFrom=community')) {
                $url = preg_replace('/area_street_id=(\d+)/i', 'area_street_id=' . $village_info['community_id'], $url);
            }
        }
        return $url;
    }

    public function uploadMaterial($file='',$village_id=0,$xtype=1){
        $uploadfile = urldecode($file);
        $uploadfile=trim($uploadfile);
        $uploadfile=ltrim($uploadfile,'/');
        $file_arr = explode('.',$uploadfile);
        $xcount=count($file_arr);
        $xcount=$xcount-1;
        $file_type=$file_arr[$xcount];
        $file_type=strtolower($file_type);
        $file_type=trim($file_type);
        if(!in_array($file_type,array('xlsx','xls'))){
            throw new \think\Exception("表格格式不对，请上传扩展名为xlsx或者是xls的Excel表格！");
        }
        if($xtype!=1){
            throw new \think\Exception("参数出错，请确认导入的是文字素材！");
        }
        $filepath=$_SERVER['DOCUMENT_ROOT'] .'/'. $uploadfile;
        $file_type=ucfirst($file_type);
        $reader = IOFactory::createReader($file_type); //设置以Excel5格式(Excel97-2003工作簿)
        $PHPExcel = $reader->load($filepath); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $datas = [];
        $filed = [
            'A' => 'categoryname',
            'B' => 'xcontent',
        ];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= $highestColumm; $column++) //列数是以A列开始
            {
                if(!isset($datas[$row])){
                    $datas[$row]=array();
                }
                if(!isset($filed[$column])){
                    continue;
                }
                $datas[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
            }
        }
        if($datas){
            $houseHotwordMaterialCategory=new HouseHotwordMaterialCategory();
            $houseHotwordMaterialContent=new HouseHotwordMaterialContent();
            $nowtime=time();
            $materialCategoryArr=array();
            foreach ($datas as $fvv){
                $categoryname=trim($fvv['categoryname']);
                $categoryname = htmlspecialchars($categoryname, ENT_QUOTES);
                $xcontent=trim($fvv['xcontent']);
                $xcontent = htmlspecialchars($xcontent, ENT_QUOTES);
                if (!empty($categoryname) && !empty($xcontent)) {
                    $cate_id = 0;
                    $saveArr = array('cate_id' => 0, 'village_id' => $village_id, 'xtype' => $xtype);
                    $saveArr['xcontent'] = $xcontent;
                    $saveArr['add_time'] = $nowtime;
                    $saveArr['update_time'] = $nowtime;
                    if ($materialCategoryArr && isset($materialCategoryArr[$categoryname]) && $materialCategoryArr[$categoryname]) {
                        $cate_id = $materialCategoryArr[$categoryname];
                    }
                    if ($cate_id < 1) {
                        $fieldStr = 'cate_id';
                        $whereArr = array();
                        $whereArr[] = array('categoryname', '=', $categoryname);
                        $whereArr[] = array('village_id', '=', $village_id);
                        $whereArr[] = array('xtype', '=', $xtype);
                        $whereArr[] = array('del_time', '<', 1);
                        $materialCategoryObj = $houseHotwordMaterialCategory->get_one($whereArr, $fieldStr);
                        if ($materialCategoryObj && !$materialCategoryObj->isEmpty()) {
                            $materialCategory = $materialCategoryObj->toArray();
                            $cate_id = $materialCategory['cate_id'];
                            $materialCategoryArr[$categoryname] = $cate_id;
                        } else {
                            $materialCategorySave = array('village_id' => $village_id, 'xtype' => $xtype);
                            $materialCategorySave['categoryname'] = $categoryname;
                            $materialCategorySave['add_time'] = $nowtime;
                            $materialCategorySave['update_time'] = $nowtime;
                            $cate_id = $houseHotwordMaterialCategory->addData($materialCategorySave);
                            $materialCategoryArr[$categoryname] = $cate_id;
                        }
                    }
                    $saveArr['cate_id'] = $cate_id;
                    $ret=$houseHotwordMaterialContent->addData($saveArr);
                    $whereCategory=array();
                    $whereCategory[]=array('cate_id','=',$cate_id);
                    $whereCategory[]=array('village_id','=',$village_id);
                    $houseHotwordMaterialCategory->updateFieldPlusNum($whereCategory,'subcount',1);
                }
            }
            return ['error'=>true,'msg'=>'导入成功','data'=>[]];
        }
        return ['error'=>true,'msg'=>'导入失败，请检查表格数据！','data'=>[]];
    }
}
