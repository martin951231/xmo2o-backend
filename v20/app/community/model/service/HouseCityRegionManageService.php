<?php
/**
 * 
**/

namespace app\community\model\service;
use app\community\model\db\AdminRegionManage;
use app\community\model\db\Area;
use app\community\model\db\AreaStreet;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
class HouseCityRegionManageService
{
    public function getOneInfo($where,$field='*')
    {
        $adminRegionManageDb = new AdminRegionManage();
        $resObj = $adminRegionManageDb->getOneData($where,$field);
        $res=array();
        if($resObj && !$resObj->isEmpty()){
            $res=$resObj->toArray();
        }
        return $res;
    }
    public function getRegionManageInfo($systemUser=array())
    {
        $admin_id=$systemUser['id'];
        $area_id=$systemUser['area_id'];
        $street_id=$systemUser['street_id'];
        $phone=$systemUser['phone'];
        $whereArr=array();
        $whereArr[]=array('area_id','=',$area_id);
        $adminRegionManageDb = new AdminRegionManage();
        $resObj = $adminRegionManageDb->getOneData($whereArr);
        $region_manage=array();
        if($resObj && !$resObj->isEmpty()){
            $region_manage=$resObj->toArray();
            if($region_manage){
                if(empty($region_manage['phone']) && $phone){
                    $region_manage['phone']=$phone;
                }
                if(empty($region_manage['area_id']) && $area_id>0){
                    $region_manage['area_id']=$area_id;
                }
                if(empty($region_manage['street_id']) && $street_id>0){
                    $region_manage['street_id']=$street_id;
                }
                if($region_manage['navinfo']){
                    $region_manage['navinfo']=json_decode($region_manage['navinfo'],true);
                }else{
                    $region_manage['navinfo']=array('nav1'=>'社区党建','nav2'=>'人口分析','nav3'=>'事件分析','nav4'=>'民族统计');
                }
            }
        }
            $visualization_title='智慧社区管理平台';
            if(empty($region_manage)){
                $region_manage['admin_id']=$admin_id;
                $region_manage['area_id']=$area_id;
                $region_manage['street_id']=$street_id;
                $region_manage['phone']=$phone;
                $region_manage['detail_address']='';
                $region_manage['visualization_title']='';
                $region_manage['navinfo']=array('nav1'=>'社区党建','nav2'=>'人口分析','nav3'=>'事件分析','nav4'=>'民族统计');
            }
            $region_manage['region_address']='';
            $region_manage['region_name']='';
            $region_manage['lat']=0;
            $region_manage['lng']=0;
            $region_manage['area_type']=-1;
            $whereAreaArr=array();
            $whereAreaArr[]=array('area_id','=',$region_manage['area_id']);
            $oneAreaInfo= $this->getOneAreaInfo($whereAreaArr);
            if($oneAreaInfo){
                $region_manage['region_name']=$oneAreaInfo['area_name'];
                $region_manage['region_address']=$oneAreaInfo['area_ip_desc'];
                $visualization_title=$oneAreaInfo['area_name'].'智慧社区管理平台';
                $region_manage['area_type']=$oneAreaInfo['area_type'];
                $region_manage['area_pid']=$oneAreaInfo['area_pid'];
                if($oneAreaInfo['area_lat']>0){
                    $region_manage['lat']=$oneAreaInfo['area_lat'];
                }
                if($oneAreaInfo['area_lng']>0){
                    $region_manage['lng']=$oneAreaInfo['area_lng'];
                }
            }
            if(empty($region_manage['visualization_title'])){
                $region_manage['visualization_title']=$visualization_title;
            }
        return $region_manage;
    }
    public function getOneAreaInfo($where,$field='*')
    {
        $areaDb = new Area();
        $resObj = $areaDb->getOne($where,$field);
        $res=array();
        if($resObj && !$resObj->isEmpty()){
            $res=$resObj->toArray();
        }
        return $res;
    }
    public function saveOneAreaData($where,$data=array())
    {
        $areaDb = new Area();
        $res = $areaDb->saveOneData($where,$data);
        return $res;
    }
    public function saveCityRegionInfo($area_id=0,$street_id=0,$saveArr=array()){
        if($area_id<1){
            return false;
        }
        $whereArr=array();
        $whereArr[]=array('area_id','=',$area_id);
        //$whereArr[]=array('street_id','=',$street_id);
        $adminRegionManageDb = new AdminRegionManage();
        $resObj = $adminRegionManageDb->getOneData($whereArr,'*');
        if($resObj && !$resObj->isEmpty()){
            $res=$adminRegionManageDb->saveOneData($whereArr,$saveArr);
            return $res;
        }else{
            $saveArr['area_id']=$area_id;
            $saveArr['add_time']=time();
            $res=$adminRegionManageDb->addOneData($saveArr);
            return $res;
        }
    }

    public function getAllStreetIds($area_id=0){
        $street_id_arr=array();
        $areaDb = new Area();
        $whereArr=array(['area_id','=',$area_id]);
        $resObj = $areaDb->getOne($whereArr,'area_id,area_pid,area_type');
        if($resObj && !$resObj->isEmpty()){
            $res=$resObj->toArray();
            $county_area_ids=array();
            //2城市，3区域
            if($res['area_type']==2){
                $whereArr=array(['area_pid','=',$res['area_id']]);
                $whereArr[]=array('area_type','=',3);
                //$whereArr[]=array('is_open','=',1);
                $areaObj= $areaDb->getList($whereArr,'area_id,area_pid,area_type','area_id ASC');
                if($areaObj && !$areaObj->isEmpty()){
                    $areas=$areaObj->toArray();
                    foreach ($areas as $avv){
                        $county_area_ids[]=$avv['area_id'];
                    }
                }
            }else if($res['area_type']==3){
                $county_area_ids[]=$res['area_id'];
            }
            
            if($county_area_ids){
                $county_area_ids=array_unique($county_area_ids);
                $areaStreetDb=new AreaStreet();
                $whereStreetArr=array(['area_type','=','0']);
                $whereStreetArr[]=array('area_pid','in',$county_area_ids);
                //$whereStreetArr[]=array('is_open','=',1);
                $areaStreetObj=$areaStreetDb->getLists($whereStreetArr,'area_id');
                if($areaStreetObj && !$areaStreetObj->isEmpty()){
                    $areaStreets=$areaStreetObj->toArray();
                    foreach ($areaStreets as $svv){
                        $street_id_arr[]=$svv['area_id'];
                    }
                    $street_id_arr=array_unique($street_id_arr);
                }
            }
        }
        return $street_id_arr;
    }


    public function getNextCityRegion($area_id=0,$region_level=4){
        $nextRegionArr=array();
        if($region_level==4){
            $areaDb = new Area();
            $whereArr = array(['area_pid', '=', $area_id]);
            $whereArr[]=array('area_type','=','3');
            //$whereArr[]=array('is_open','=',1);
            $resObj = $areaDb->getList($whereArr, 'area_id,area_name,area_pid,area_type,adcode,area_map_name');
            if($resObj && !$resObj->isEmpty()){
                $nextRegionArr=$resObj->toArray();
            }
        }elseif($region_level==3){
            $areaStreetDb=new AreaStreet();
            $whereStreetArr=array(['area_type','=','0']);
            $whereStreetArr[]=array('area_pid','=',$area_id);
            //$whereStreetArr[]=array('is_open','=',1);
            $areaStreetObj=$areaStreetDb->getLists($whereStreetArr,'area_id,area_name,area_pid,area_type');
            if($areaStreetObj && !$areaStreetObj->isEmpty()){
                $nextRegionArr=$areaStreetObj->toArray();
            }
        }elseif($region_level==2){
            $areaStreetDb=new AreaStreet();
            $whereStreetArr=array(['area_type','=','1']);
            $whereStreetArr[]=array('area_pid','=',$area_id);
            //$whereStreetArr[]=array('is_open','=',1);
            $areaCommunityObj=$areaStreetDb->getLists($whereStreetArr,'area_id,area_name,area_pid,area_type');
            if($areaCommunityObj && !$areaCommunityObj->isEmpty()){
                $nextRegionArr=$areaCommunityObj->toArray();
            }
        }
        return $nextRegionArr;
    }
}
