<?php
/**
 * 城市社区管委会-可视化
 * @author : liukezhu
 * @date : 2022/5/6
 */

namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\TaskReleaseService;

class CommunityCommitteeController extends CommunityBaseController
{

    public $street_id=0;
    public $community_id=0;
    public $swap_community_id=0;
    public $user_name='';
    public $village_id_arr=array();
    public function initialize(){
        parent::initialize();
        if ($this->adminUser['area_type'] == 1) {
            // 是社区
            $this->street_id=$this->adminUser['area_pid'];
            $this->community_id=$this->adminUser['area_id'];
        }
        else{
            //是街道
            $this->street_id=$this->adminUser['area_id'];
            $this->community_id=0;
        }
        $this->swapCommunity();
    }
    public function swapCommunity()
    {
        $swap_community_id = $this->request->post('swap_community_id', 0, 'intval');
        if ($swap_community_id > 0) {
            $this->swap_community_id = $swap_community_id;
            $this->community_id=$this->swap_community_id;
        }
        return $this->swap_community_id;
    }
    private function getAllVillageIds(){
        try{
            $taskReleaseService= new TaskReleaseService();
            $xtype=0;
            $area_id= 0;
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
     * 首页接口
     * @author: liukezhu
     * @date : 2022/5/6
     * @return \json
     */
    public function getIndex(){
        try{
            $taskReleaseService = new TaskReleaseService();
            $list = $taskReleaseService->getCommitteeIndex($this->adminUser,$this->street_id,$this->community_id);
            $list['is_street_login']=0;
            $list['street_communitys']=array();
            $list['region_count_info'] = array();
            if($this->adminUser['area_type'] == 0 && $this->street_id>0){
                $list['is_street_login']=1;
                $list['street_communitys']=$taskReleaseService->getStreetCommunitys($this->street_id);
                $countV=$list['street_communitys'] ? count($list['street_communitys']):0;
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
    /**
     * 展示最新上报的三条工单数据
     * @author: liukezhu
     * @date : 2022/5/6
     * @return \json
     */
    public function getAreaStreetWorkersOrder(){
        try{
            $list = (new TaskReleaseService())->getAreaStreetWorkersOrder($this->street_id,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    //=================接口暂时废弃===================

    /**
     *社区党建 党组织架构统计+三会一课+党内咨询+热点新闻
     * @author: liukezhu
     * @date : 2022/5/6
     * @return \json
     */
    public function getPartyBuilding(){
        try{
            $list = (new TaskReleaseService())->getPartyBuilding($this->street_id,$this->community_id);
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

        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getPopulationAnaly($this->street_id,$this->community_id,$village_ids);
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
        try{
            $list = (new TaskReleaseService())->getEventAnaly($this->street_id,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    //=================接口暂时废弃 end ===================

    //==================todo 单个模块接口分离===========

    /**
     * 党组织架构统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPartyOrgStatistics(){
        try{
            $list = (new TaskReleaseService())->getPartyBranchMethod($this->street_id,$this->community_id);
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
        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getPartyMemberStatistics($this->street_id,$this->community_id,$page,$limit,$village_ids);
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
        try{
            $list = (new TaskReleaseService())->getPartyActivity($this->street_id,$this->community_id,$page,$limit);
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
        try{
            $list = (new TaskReleaseService())->getMeetingLessonCategoryMethod($this->street_id,$this->community_id);
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
        try{
            $list = (new TaskReleaseService())->getPartyBuildMethod($this->street_id,$this->community_id);
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
        try{
            $list = (new TaskReleaseService())->getStreetNewsMethod($this->street_id,$this->community_id);
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
        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getPersonStatisticsMethod($this->street_id,$this->community_id,$village_ids);
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
        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getUserSexStatisticsMethod($this->street_id,$this->community_id,$village_ids);
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
        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getUserAgeStatisticsMethod($this->street_id,$this->community_id,$village_ids);
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
        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getUserLabelStatisticsMethod($this->street_id,$this->community_id,$village_ids);
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
        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getUserEducateStatisticsMethod($this->street_id,$this->community_id,$village_ids);
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
        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getUserMarriageStatisticsMethod($this->street_id,$this->community_id,$village_ids);
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
        try{
            $list = (new TaskReleaseService())->getEventMethod($this->street_id,$this->community_id);
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
        try{
            $list = (new TaskReleaseService())->getEpidemicPrevent($this->street_id,$this->community_id,$page,$limit);
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
        try{
            $list = (new TaskReleaseService())->getCommunityCareMethod($this->street_id,$this->community_id);
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
        try{
            $list = (new TaskReleaseService())->getPropertyStatisticsMethod($this->street_id,$this->community_id);
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
        try{
            $list = (new TaskReleaseService())->getEventVideoStatistics($this->street_id,$this->community_id,0);
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
        try{
            $list = (new TaskReleaseService())->getEventVideoStatistics($this->street_id,$this->community_id,1);
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
        try{
            $village_ids=array();
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            }
            $list = (new TaskReleaseService())->getUserNationStatisticsMethod($this->street_id,$this->community_id,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
}