<?php
/**
 *
 */

namespace app\community\controller\platform;

use app\community\controller\platform\AuthBaseController as BaseController;
use  app\community\model\service\HouseCityRegionManageService;
use app\community\model\service\TaskReleaseService;
use net\Http;

class HouseCityRegionManageController extends BaseController
{
    public $street_id=0;
    public $area_id=0;
    public $community_id=0;
    public $county_area_id=0;
    public $city_area_id=0;  //城市id
    public $region_level=4; //1社区 2街道 3区县 4城市
    public $login_region_level=4; //1社区 2街道 3区县 4城市
    public $street_id_arr=array();
    public $village_id_arr=array();
    public $areaStreet=array();
    public $adcode='';  //行政代码
    public $ini_area_adcode='';  //行政代码
    public $area_feature_collection='';
    public function initialize(){
        parent::initialize();
        $this->street_id=$this->systemUser['street_id'] ? $this->systemUser['street_id']:0;
        $this->area_id=$this->systemUser['area_id'] ? $this->systemUser['area_id']:0;
        $city_area_id = $this->request->post('city_area_id', 0, 'intval');
        if($city_area_id>0){
            $this->city_area_id=$city_area_id;
            $this->area_id=$this->city_area_id;
            $this->systemUser['area_id']=$this->city_area_id;
        }
        $this->county_area_id=0;
        if($this->street_id>0){
            $this->region_level=2;
            $this->login_region_level=2;
        }elseif($this->area_id>0){
            $this->getRegionArea();
        }
        
    }
    public function getRegionArea(){
        try{
            $houseCityRegionManageService= new HouseCityRegionManageService();
            $whereArr=array('area_id'=>$this->area_id);
            $fieldStr='area_id,area_pid,area_name,is_open,area_ip_desc,area_type,adcode,area_map_name,area_frame_dot';
            $areaInfo= $houseCityRegionManageService->getOneAreaInfo($whereArr,$fieldStr);
            if($areaInfo){
                if($areaInfo['area_type']==2){
                    $this->region_level=4;
                    $this->login_region_level=4;
                    $this->city_area_id=$this->area_id;
                }else if($areaInfo['area_type']==3){
                    $this->region_level=3;
                    $this->login_region_level=3;
                    $this->county_area_id=$this->area_id;
                }
                $this->ini_area_adcode=$areaInfo['adcode'];
                $this->adcode=$areaInfo['adcode'];
                $this->area_feature_collection=$areaInfo['area_frame_dot'] ? json_decode($areaInfo['area_frame_dot'],true):'';
            }
        }catch (\Exception $e){
          
        }
    }
    public function getCityRegionInfo()
    {
        if($this->_uid<1){
            return api_output_error(1002);
        }
        try{
            $houseCityRegionManageService= new HouseCityRegionManageService();
            $region_manage= $houseCityRegionManageService->getRegionManageInfo($this->systemUser);

            return api_output(0, $region_manage, "成功");
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    public function saveCityRegionInfo(){
        if($this->_uid<1){
            return api_output_error(1002);
        }
        $admin_id=$this->systemUser['id'];
        $area_id=$this->systemUser['area_id'];
        $street_id=$this->systemUser['street_id'];
        $userphone=$this->systemUser['phone'];
        $xtype = $this->request->post('xtype','','trim');
        $saveArr=array();
        if($xtype=='base_set'){
            $region_name = $this->request->post('region_name','','trim');
            $region_address = $this->request->post('region_address','','trim');
            $detail_address = $this->request->post('detail_address','','trim');
            $phone= $this->request->post('phone','','trim');
            $saveArr['detail_address']=$detail_address;
            $saveArr['phone']=$phone ? $phone :$userphone;
            $saveArr['admin_id']=$admin_id;
            $saveArr['street_id']=$street_id;
        }else if($xtype=='nav_set'){
            $visualization_title = $this->request->post('visualization_title','','trim');
            $saveArr['visualization_title']=$visualization_title;
            $navinfo = $this->request->post('navinfo','');
            if($navinfo){
                $saveArr['navinfo']=json_encode($navinfo,JSON_UNESCAPED_UNICODE);
            }else{
                $saveArr['navinfo']='';
            }
            $saveArr['admin_id']=$admin_id;
            $saveArr['street_id']=$street_id;
            $saveArr['phone']=$userphone;
        }
        if($saveArr['phone']){
            $saveArr['phone']=strval($saveArr['phone']);
        }
        try{
        $houseCityRegionManageService= new HouseCityRegionManageService();
        $ret= $houseCityRegionManageService->saveCityRegionInfo($area_id,$street_id,$saveArr);
            return api_output(0, $ret, "保存成功！");
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    private function getAllStreetIds(){
        if($this->street_id>0){
            $this->street_id_arr[]=$this->street_id;
        }else{
            try{
                $houseCityRegionManageService= new HouseCityRegionManageService();
                $street_ids= $houseCityRegionManageService->getAllStreetIds($this->area_id);
                $this->street_id_arr=$street_ids;
            }catch (\Exception $e){
                $this->street_id_arr=array();
            }
        }
        return $this->street_id_arr;
    }

    private function getAllVillageIds(){
        try{
            $taskReleaseService= new TaskReleaseService();
            $xtype=0;
            $area_id= $this->area_id;
            if($this->street_id>0){
                $area_id=$this->street_id;
                $xtype=1;
            }
            if($this->community_id>0){
                $area_id=$this->community_id;
                $xtype=2;
            }
            $village_ids= $taskReleaseService->getAllVillageIds($area_id,$xtype);
            $this->village_id_arr=$village_ids;
        }catch (\Exception $e){
            $this->village_id_arr=array();
        }
        return $this->village_id_arr;
    }
    /**
    *切换处理
     ***/
    public function swapCityRegion()
    {
        $street_id = $this->request->post('street_id', 0, 'intval');
        $county_area_id = $this->request->post('county_area_id', 0, 'intval');
        $community_id = $this->request->post('community_id', 0, 'intval');
        $city_area_id = $this->request->post('city_area_id', 0, 'intval');
        $adcode = $this->request->post('adcode', 0, 'intval');
        if($adcode){
            $this->adcode=$adcode;
            $houseCityRegionManageService= new HouseCityRegionManageService();
            $whereArr=array('adcode'=>$adcode);
            $fieldStr='area_id,area_pid,area_name,is_open,area_ip_desc,area_type,adcode,area_map_name,area_frame_dot';
            $areaInfo= $houseCityRegionManageService->getOneAreaInfo($whereArr,$fieldStr);
            if($areaInfo){
                if($areaInfo['area_type']==2){
                    $this->region_level=4;
                    $city_area_id=$areaInfo['area_id'];
                    $this->area_id=$city_area_id;
                }else if($areaInfo['area_type']==3){
                    $this->region_level=3;
                    $county_area_id=$areaInfo['area_id'];
                }
                $this->area_feature_collection=$areaInfo['area_frame_dot'] ? json_decode($areaInfo['area_frame_dot'],true):'';
            }
        }
        $this->city_area_id=$city_area_id;
        if ($county_area_id > 0) {
            $this->area_id = $county_area_id;
            $this->county_area_id=$county_area_id;
            $this->region_level = 3;
        }
        if ($street_id > 0) {
            $this->street_id = $street_id;
            $this->region_level = 2;
        }
        if ($community_id > 0) {
            $this->community_id = $community_id;
            $this->region_level = 1;
        }
    }
    /**
     * 首页接口
     */
    public function getIndex(){
        
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $houseCityRegionManageService= new HouseCityRegionManageService();
            $this->systemUser['next_area_id']=$this->county_area_id;
            if($this->login_region_level==3){
                $this->systemUser['next_area_id']=0;
            }
            $city_area_name=$this->request->post('city_area_name', '', 'trim');
            $this->systemUser['comfrom_type']='cityRegionManage';
            $this->systemUser['city_area_name']=$city_area_name;
            $this->systemUser['login_region_level']=$this->login_region_level;
            $list = (new TaskReleaseService())->getCommitteeIndex($this->systemUser,$this->street_id_arr,$this->community_id,$this->region_level);
            $nextCityRegion=array();
            if($this->login_region_level>=2){
                $area_id= $this->systemUser['area_id'];
                if($this->systemUser['street_id']>0){
                    $area_id=$this->systemUser['street_id'];
                }
                $nextCityRegion=$houseCityRegionManageService->getNextCityRegion($area_id,$this->login_region_level);
            }
            $list['area_feature_collection']=$this->area_feature_collection;
            if($this->region_level>2 && empty($this->area_feature_collection) && $this->adcode){
                /***
                 * https://datav.aliyun.com/portal/school/atlas/area_selector
                 ****/
                // $url = 'https://geo.datav.aliyun.com/areas_v3/bound/geojson?code=340100_full';
                $url = 'https://geo.datav.aliyun.com/areas_v3/bound/'.$this->adcode.'_full.json';
                if($this->region_level==3){
                    $url = 'https://geo.datav.aliyun.com/areas_v3/bound/' .$this->adcode. '.json';
                }
                $http = new Http();
                $result = $http->curlGet($url,20);
                fdump_api(['adcode'=>$this->adcode,'url'=>$url,'result'=>$result],'00area_selector',1);
                $area_ret = json_decode($result, true);
                if($area_ret){
                    $whereArr=array('adcode'=>$this->adcode);
                    $saveArr=array('area_frame_dot'=>json_encode($area_ret,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
                    $houseCityRegionManageService->saveOneAreaData($whereArr,$saveArr);
                    $list['area_feature_collection']=$area_ret;
                }
            }
            $list['ini_area_adcode']=$this->ini_area_adcode;
            $list['current_adcode']=$this->adcode;
            $list['county_area_id']=$this->county_area_id;
            $list['street_id']=$this->street_id;
            $list['community_id']=$this->community_id;
            $list['next_city_region']=$nextCityRegion;
            $list['region_level']=$this->login_region_level;
            $list['current_region_level']=$this->region_level;
            $list['region_count_info'] = array();
            $taskReleaseService= new TaskReleaseService();
            if ($this->region_level == 3 || $this->region_level == 4) {
                $countV = $taskReleaseService->getStreetOrCommunityTotal($this->area_id, $this->region_level, 0);
                $list['region_count_info'][] = array('key' => '街道数量', 'value' => $countV);
            }
            if ($this->region_level == 3 || $this->region_level == 4 || $this->region_level == 2) {
                $tmp_area_id = $this->region_level == 3 || $this->region_level == 4 ? $this->area_id : $this->street_id;
                $countV = $taskReleaseService->getStreetOrCommunityTotal($tmp_area_id, $this->region_level, 1);
                $list['region_count_info'][] = array('key' => '社区数量', 'value' => $countV);
            }
            $village_ids = $this->getAllVillageIds();
            $countV = $village_ids ? count($village_ids) : 0;
            $list['region_count_info'][] = array('key' => '小区数量', 'value' => $countV);
            $countV = $taskReleaseService->getVillagePeopleTotal($village_ids);
            $list['region_count_info'][] = array('key' => '人员统计', 'value' => $countV);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    public function getNextCityRegions(){
        $region_type = $this->request->post('region_type','','trim');
        $street_id = $this->request->post('street_id',0,'intval');
        $county_area_id = $this->request->post('county_area_id',0,'intval');
        try{
            $list=array();
            $list['county_area_id']=$county_area_id;
            $list['street_id']=$street_id;
            $houseCityRegionManageService= new HouseCityRegionManageService();
            $region_level=0;
            $nextCityRegion=array();
            if($region_type=='street'){
                $region_level=3;
                $nextCityRegion=$houseCityRegionManageService->getNextCityRegion($county_area_id,$region_level);
            }else if($region_type=='community'){
                $region_level=2;
                $nextCityRegion=$houseCityRegionManageService->getNextCityRegion($street_id,$region_level);
            }
            $list['next_city_region']=$nextCityRegion;
            return api_output(0,$list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    /**
     * 展示最新上报的三条工单数据
     * @author: liukezhu
     * @date : 2022/5/6
     * @return \json
     */
    public function getAreaStreetWorkersOrder(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getAreaStreetWorkersOrder($this->street_id_arr,$this->community_id);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *社区党建 党组织架构统计+三会一课+党内咨询+热点新闻
     * @author: liukezhu
     * @date : 2022/5/6
     * @return \json
     */
   
    public function getPartyBuilding(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getPartyBuilding($this->street_id_arr,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 人口分析
     * @author: liukezhu
     * @date : 2022/5/7
     * @return \json
     */
  
    public function getPopulationAnaly(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $this->getAllVillageIds();
            $village_ids=$this->village_id_arr;
            $list = (new TaskReleaseService())->getPopulationAnaly($this->street_id_arr,$this->community_id,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 事件分析 事件上报+社区关怀+社区物业统计+视频监控一+视频监控二
     * @author: liukezhu
     * @date : 2022/5/7
     * @return \json
     */
   
    public function getEventAnaly(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getEventAnaly($this->street_id_arr,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    

    /**
     * 党组织架构统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPartyOrgStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getPartyBranchMethod($this->street_id_arr,$this->community_id);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *社区党建 党员数量统计
     * @author: liukezhu
     * @date : 2022/5/6
     * @return \json
     */
    public function getPartyMemberStatistics(){
        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',10,'intval');
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getPartyMemberStatistics($this->street_id_arr,$this->community_id,$page,$limit);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 社区党建 党建活动
     * @author: liukezhu
     * @date : 2022/5/6
     * @return \json
     */
    public function getPartyActivity(){
        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',10,'intval');
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getPartyActivity($this->street_id_arr,$this->community_id,$page,$limit);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 三会一课统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     *
     */
    public function getPartyMeetingStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getMeetingLessonCategoryMethod($this->street_id_arr,$this->community_id);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 党内咨询
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPartySeekStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getPartyBuildMethod($this->street_id_arr,$this->community_id);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *热点新闻
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPartyNewsStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getStreetNewsMethod($this->street_id_arr,$this->community_id);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 人口信息
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationPersonStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $this->getAllVillageIds();
            $village_ids=$this->village_id_arr;
            $list = (new TaskReleaseService())->getPersonStatisticsMethod($this->street_id_arr,$this->community_id,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *男女比例统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationSexStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $this->getAllVillageIds();
            $village_ids=$this->village_id_arr;
            $list = (new TaskReleaseService())->getUserSexStatisticsMethod($this->street_id_arr,$this->community_id,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 年龄段统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationAgeStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $this->getAllVillageIds();
            $village_ids=$this->village_id_arr;
            $list = (new TaskReleaseService())->getUserAgeStatisticsMethod($this->street_id_arr,$this->community_id,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * 名族统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationNationStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $this->getAllVillageIds();
            $village_ids=$this->village_id_arr;
            $list = (new TaskReleaseService())->getUserNationStatisticsMethod($this->street_id_arr,$this->community_id,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * 居民人口性质统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationUserLabelStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $this->getAllVillageIds();
            $village_ids=$this->village_id_arr;
            $list = (new TaskReleaseService())->getUserLabelStatisticsMethod($this->street_id_arr,$this->community_id,$village_ids);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 教育水平统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationEducateStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $this->getAllVillageIds();
            $village_ids=$this->village_id_arr;
            $list = (new TaskReleaseService())->getUserEducateStatisticsMethod($this->street_id_arr,$this->community_id,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *婚姻状况统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationMarriageStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $this->getAllVillageIds();
            $village_ids=$this->village_id_arr;
            $list = (new TaskReleaseService())->getUserMarriageStatisticsMethod($this->street_id_arr,$this->community_id,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 事件上报
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getEventReportStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getEventMethod($this->street_id_arr,$this->community_id);
            $list['order_url']='';
            $list['task_url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 事件分析  疫情防控数据统计
     * @author: liukezhu
     * @date : 2022/5/7
     * @return \json
     */
    public function getEpidemicPrevent(){
        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',10,'intval');
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getEpidemicPrevent($this->street_id_arr,$this->community_id,$page,$limit);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 社区关怀
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getEventCareStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getCommunityCareMethod($this->street_id_arr,$this->community_id);
            $list['url']='';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 社区物业统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getEventVirtualStatistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getPropertyStatisticsMethod($this->street_id_arr,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 视频监控一
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getEventVideo1Statistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getEventVideoStatistics($this->street_id_arr,$this->community_id,0);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 视频监控二
     * @author: liukezhu
     * @date : 2022/5/11
     * @return \json
     */
    public function getEventVideo2Statistics(){
        $this->swapCityRegion();
        try{
            $this->getAllStreetIds();
            $list = (new TaskReleaseService())->getEventVideoStatistics($this->street_id_arr,$this->community_id,1);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

}