<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/9/26 10:22
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\AockpitService;
use app\community\model\service\AreaService;
use app\community\model\service\HardwareBrandService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageNewsService;
use app\community\model\service\ConfigDataService;
use think\facade\Cache;
use app\community\model\service\AdminLoginService;

class AockpitController extends CommunityBaseController{


    /**
     * 首页接口统计
     * @author: liukezhu
     * @date : 2021/9/26
     */
    public function getHomeStatistic(){
        $village_id = $this->adminUser['village_id'];
        $returnArr = (new AdminLoginService())->formatUserData($this->adminUser, $this->login_role);
        if (!$returnArr) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        $serviceAockpit = new AockpitService();
        try{
            //小区信息
            $res['village_info'] = $serviceAockpit->getVillageData($village_id);
            $res['village_info']['login_name']=$returnArr['login_name'];

            //查询报警信息
            $res['alarm_list'] = $serviceAockpit->getAlarmList($village_id,1,10);

            //设施管理
            $res['device_list'] = $serviceAockpit->getFacilities($village_id);
            //车辆信息
            $res['vehicle_list'] = $serviceAockpit->getVehicle($village_id);
            //查询房屋信息
            $res['vacancy_count'] = $serviceAockpit->getHouseCountInfo($village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 首页报警信息查询
     * @author: liukezhu
     * @date : 2021/9/26
     */
    public function getAlarmList(){
        $village_id = $this->adminUser['village_id'];
       // $village_id=50;
        $page = $this->request->param('page','1','intval');
        $serviceAockpit = new AockpitService();
        try{
            $limit=10;
            //查询报警信息
            $res['alarm_list'] = $serviceAockpit->getAlarmList($village_id,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    /**
     * 小区人口信息
     * @author lijie
     * @date_time 2021/09/26
     * @return \json
     */
    public function getPopulationStatistic()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_village_user_bind = new HouseVillageUserBindService();
        try{
            $population_count = $service_house_village_user_bind->getUserCount([['village_id','=',$village_id],['status','=',1],['type','in','0,1,2,3']]);
            $owner_count = $service_house_village_user_bind->getUserCount([['village_id','=',$village_id],['status','=',1],['type','in','0,1,3']]);
            $tenant_count = $service_house_village_user_bind->getUserCount([['village_id','=',$village_id],['status','=',1],['type','=',2]]);
            $info = $service_house_village_user_bind->getUserCountByBirth([['village_id','=',$village_id],['status','=',1],['type','in','0,1,2,3']],'birth');
            $res['img'] = cfg('site_url').'/static/images/cockpit/pop.png';
            $res['population'][0]['img'] = cfg('site_url').'/static/images/cockpit/1_0011.png';
            $res['population'][0]['title'] = '人口总数';
            $res['population'][0]['value'] = $population_count;
            $res['population'][1]['img'] = cfg('site_url').'/static/images/cockpit/1_0022.png';
            $res['population'][1]['title'] = '户籍人口总数';
            $res['population'][1]['value'] = $owner_count;
            $res['population'][2]['img'] = cfg('site_url').'/static/images/cockpit/1_0009.png';
            $res['population'][2]['title'] = '外来人口总数';
            $res['population'][2]['value'] = $tenant_count;
            $res['age']['population_count'] = $population_count;
            $res['age']['level1'] = $population_count ? getFormatNumber( $info['level1'] / $population_count*100) : 100;
            $res['age']['level2'] = $population_count ? getFormatNumber($info['level2'] / $population_count*100) : 100;
            $res['age']['level3'] = $population_count ? getFormatNumber($info['level3'] / $population_count*100) : 100;
            $res['age']['level4'] = $population_count ? getFormatNumber($info['level4'] / $population_count*100) : 100;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 获取环境信息
     * @author lijie
     * @date_time 2021/09/26
     * @return \json
     */
    public function getWeather()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_village = new HouseVillageService();
        $service_area = new AreaService();
        $service_aockpit = new AockpitService();
        try{
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'city_id');
            if(empty($village_info['city_id']))
                return api_output_error(1001, '无法查询小区所在城市');
            $area_info = $service_area->getAreaOne(['area_id'=>$village_info['city_id']],'area_name');
            if(empty($area_info['area_name'])){
                $area_info['area_name'] = '仁怀市';
            }

            $weather_info_key='weather_info_city_'.$village_info['city_id'];
            $service_config_data = new ConfigDataService();
            $weather_info=$service_config_data->get_one(array('name'=>$weather_info_key));
            $weather_data=array();
            $data=array();
            if($weather_info && !empty($weather_info['value'])){
                $weather_data=json_decode($weather_info['value'],1);
                $data=$weather_data['weather'];
            }
            //$data = Cache::get($area_info['area_name']);
            $nowtime=time();
            $expiretime=$nowtime-7200;
            if(empty($weather_data) || ($weather_data['expiretime']<$expiretime)){
                $data_tmp = $service_aockpit->weather($area_info['area_name']);
                if(!empty($data_tmp)){
                    $data=$data_tmp;
                    $weather_info_tmp=array('expiretime'=>$nowtime,'weather'=>$data_tmp);
                    $config_data=array('value'=>json_encode($weather_info_tmp,JSON_UNESCAPED_UNICODE));
                    if(!empty($weather_info)){
                        $service_config_data->updateConfig(array('name'=>$weather_info_key),$config_data);
                    }else{
                        $config_data['name']=$weather_info_key;
                        $service_config_data->addConfig($config_data);
                    }
                }
               // Cache::set($area_info['area_name'], $data, 7200);
            }

            if(empty($data)){
                return api_output(0,[]);
            }
            $res['img'] = cfg('site_url').'/static/images/cockpit/evo.png';
            $res['weather'][0]['img'] = cfg('site_url').'/static/images/cockpit/info.png';
            $res['weather'][0]['value'] = $data['info'];
            $res['weather'][1]['img'] = cfg('site_url').'/static/images/cockpit/temperature.png';
            $res['weather'][1]['value'] = $data['temperature'];
            $res['weather'][1]['company'] = '℃';
            $res['weather'][2]['img'] = cfg('site_url').'/static/images/cockpit/humidity.png';
            $res['weather'][2]['value'] = $data['humidity'];
            $res['weather'][2]['company'] = '%';
            $res['weather'][3]['img'] = cfg('site_url').'/static/images/cockpit/direct.png';
            $res['weather'][3]['value'] = $data['direct'];
            $res['weather'][4]['img'] = cfg('site_url').'/static/images/cockpit/power.png';
            $res['weather'][4]['value'] = preg_replace('/([\x80-\xff]*)/i','',$data['power']);
            $res['weather'][4]['company'] = '级';
            $res['pm'][0]['img'] = cfg('site_url').'/static/images/cockpit/pm2.5.png';
            $res['pm'][0]['name'] = 'pm2.5';
            $res['pm'][0]['value'] = $data['pm25'];
            $res['pm'][0]['rate'] = $data['pm25'].'%';
            $res['pm'][0]['company'] = 'μg/m³';
            $res['pm'][1]['img'] = cfg('site_url').'/static/images/cockpit/pm10.png';
            $res['pm'][1]['name'] = 'pm10';
            $res['pm'][1]['value'] = $data['pm10'];
            $res['pm'][1]['rate'] = $data['pm10'].'%';
            $res['pm'][1]['company'] = 'μg/m³';
            $res['level']['value'] = $data['quality'];
            $res['level']['level'] = $data['level'];
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 点击单个楼栋返回信息
     * @author: liukezhu
     * @date : 2021/9/27
     */
    public function getBuildingInfo(){
        $village_id = $this->adminUser['village_id'];
        $single_id = $this->request->param('single_id','','intval');
        if (empty($single_id)){
            return api_output(1001,[],'楼栋id不能为空！');
        }
        $serviceAockpit = new AockpitService();
        try{
            $res=$serviceAockpit->getBuilding($village_id,$single_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询楼栋下信息总览
     * @author: liukezhu
     * @date : 2021/9/27
     * @return \json
     */
    public function getBuildingData(){
        $village_id = $this->adminUser['village_id'];
        $single_id = $this->request->param('single_id','','intval');
        if (empty($single_id)){
            return api_output(1001,[],'楼栋id不能为空！');
        }
        $serviceAockpit = new AockpitService();
        try{
            $res=$serviceAockpit->getBuildingData($village_id,$single_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     *查询单元下房间
     * @author: liukezhu
     * @date : 2021/9/27
     * @return \json
     */
    public function getVacancyData(){
        $village_id = $this->adminUser['village_id'];
        $single_id = $this->request->param('single_id','','intval');
        $floor_id = $this->request->param('floor_id',0,'intval');
        if (empty($single_id)){
            return api_output(1001,[],'楼栋id不能为空！');
        }
        $param=[
            'vacancy_id'=>$this->request->param('vacancy_id',''),
            'house_type'=>$this->request->param('house_type',''),
            'room'=>$this->request->param('room',''),
        ];
        $serviceAockpit = new AockpitService();
        try{
            $res=$serviceAockpit->getVacancyData($village_id,$single_id,$floor_id,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     *智能设施
     * @author: liukezhu
     * @date : 2021/9/29
     */
    public function getFacilitiesData(){
        $village_id = $this->adminUser['village_id'];
        $type=$this->request->param('type',1,'intval');
        $device_type=$this->request->param('device_type','','trim');
        $sub_series_type=$this->request->param('sub_series_type','');
        $cate_id=$this->request->param('cate_id','','trim');
        $device_status=$this->request->param('device_status','','');  //0所有 1在线 2离线
        if($device_status && strpos($device_status,',')){
            $device_status=0;
        }else{
            $device_status=intval($device_status);
        }
        $extra_data=array();
        $extra_data['cate_id']=htmlspecialchars($cate_id,ENT_QUOTES);
        $extra_data['device_status']=intval($device_status);
        $serviceAockpit = new AockpitService();
        try{
            $res= $serviceAockpit->getFacilitiesData($village_id,$type,$device_type,$sub_series_type,$extra_data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     *智能设施
     * @author: liukezhu
     * @date : 2021/9/29
     */
    public function getFacilitiesData1(){
        $village_id = $this->adminUser['village_id'];
        $serviceAockpit = new AockpitService();
        try{
            $res= $serviceAockpit->getFacilitiesData1($village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 设备点位信息
     * @author: liukezhu
     * @date : 2021/9/29
     * @return \json
     */
    public function getFacilitiesDetails(){
        $village_id = $this->adminUser['village_id'];
       // $village_id = 50;
        $coordinate_id=$this->request->param('id','1','intval');
        $device_id=$this->request->param('device_id','1','intval');
        if (empty($coordinate_id)){
            return api_output(1001,[],'点位id不能为空！');
        }
        $serviceAockpit = new AockpitService();
        try{
            $res= $serviceAockpit->getFacilitiesDetails($village_id,$coordinate_id,$device_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 天使之眼设备
     * @author: liukezhu
     * @date : 2021/9/29
     * @return \json
     */
    public function getMonitorDevice(){
         $village_id = $this->adminUser['village_id'];
       // $village_id = 50;
        $type=3;
        $device_type=$this->request->param('device_type','');
        $sub_series_type=$this->request->param('sub_series_type','');
        $cate_id=$this->request->param('cate_id','','trim');
        $device_status=$this->request->param('device_status',0,'int');  //0所有 1在线 2离线
        $extra_data=array();
        $extra_data['cate_id']=htmlspecialchars($cate_id,ENT_QUOTES);
        $extra_data['device_status']=intval($device_status);
        $serviceAockpit = new AockpitService();
        try{
            $res= $serviceAockpit->getFacilitiesData($village_id,$type,$device_type,$sub_series_type,$extra_data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     *天使之眼点位接口
     * @author: liukezhu
     * @date : 2021/10/18
     * @return \json
     */
    public function getMonitorSpot(){
        $village_id = $this->adminUser['village_id'];
        $coordinate_id=$this->request->param('id','1','intval');
        $device_id=$this->request->param('device_id','1','intval');
        $serviceAockpit = new AockpitService();
        try{
            $res= $serviceAockpit->getMonitorDevice($village_id,$coordinate_id,$device_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 新闻公告分类
     * @author lijie
     * @date_time 2021/09/29
     * @return \json
     */
    public function getNewsCategory()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_village_news = new HouseVillageNewsService();
        $where[] = ['village_id','=',$village_id];
        $where[] = ['cat_status','=',1];
        $where[] = ['cat_name','notlike','%周边%'];
        try{
            $data = $service_house_village_news->getNewsCategoryLists($where,true);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 社区服务分类
     * @author lijie
     * @date_time 2021/09/29
     * @return \json
     */
    public function getServiceCategory()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_village_news = new HouseVillageNewsService();
        $where[] = ['village_id','=',$village_id];
        $where[] = ['cat_status','=',1];
        $where[] = ['cat_name','like','%周边%'];
        try{
            $data = $service_house_village_news->getNewsCategoryLists($where,true,0,0,$order='cat_sort DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 新闻列表
     * @author lijie
     * @date_time 2021/09/29
     * @return \json
     */
    public function getNewsLists()
    {
        $cat_id = $this->request->get('cat_id',0);
        if(!$cat_id)
            return api_output_error(1001,'缺少必传参数');
        $page = $this->request->get('page',1);
        $service_house_village_news = new HouseVillageNewsService();
        $where[] = ['cat_id','=',$cat_id];
        $where[] = ['status','=',1];
        $field = 'title,add_time,news_id,content';
        try{
            $data = $service_house_village_news->getNewsLists($where,$field,$page,12,'news_id DESC');
            $count = $service_house_village_news->getNewsCount($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['list'] = $data;
        $res['count'] = $count;
        return api_output(0,$res);
    }

    /**
     * 新闻详情
     * @author lijie
     * @date_time 2021/09/30
     * @return \json
     */
    public function getNewsDetail()
    {
        $village_id = $this->adminUser['village_id'];
        $news_id = $this->request->get('news_id',0);
        if(!$news_id)
            return api_output_error(1001,'缺少必传参数');
        $service_house_village_news = new HouseVillageNewsService();
        $where[] = ['news_id','=',$news_id];
        $field = true;
        try{
            $news_info = $service_house_village_news->getNewsDetail($where,$field);
            if(empty($news_info))
                return api_output_error(1001,'数据异常');
            $last_news = $service_house_village_news->getNewsDetail([['news_id','>',$news_id],['cat_id','=',$news_info['cat_id']],['status','=',1]],true,'news_id ASC');
            $max_ids = $service_house_village_news->getNewsDetail([['news_id','<',$news_id],['cat_id','=',$news_info['cat_id']],['status','=',1]],'max(news_id) as news_id');
            if($max_ids['news_id']){
                $next_news = $service_house_village_news->getNewsDetail([['news_id','=',$max_ids['news_id']]],true,'news_id DESC');
            }
            if(!isset($last_news['news_id'])){
                $last_news['news_id'] = 0;
                $last_news['title'] = '没有上一篇了';
            }
            if(!isset($next_news['news_id'])){
                $next_news['news_id'] = 0;
                $next_news['title'] = '没有下一篇了';
            }
            $data['info'] = $news_info;
            $data['last_news'] = $last_news;
            $data['next_news'] = $next_news;
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 新闻公告
     * @author lijie
     * @date_time 2021/09/29
     * @return \json
     */
    public function getNotice()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_village_news = new HouseVillageNewsService();
        $where[] = ['village_id','=',$village_id];
        $where[] = ['cat_status','=',1];
        $where[] = ['cat_name','notlike','%周边%'];
        $field='cat_id';
        try{
            $cate_list = $service_house_village_news->getNewsCategoryLists($where,$field,0,0,$order='cat_sort DESC');
            $cat_ids = [];
            foreach ($cate_list as $v){
                $cat_ids[] = $v['cat_id'];
            }
            $data = $service_house_village_news->getNewsLists([['cat_id','in',$cat_ids],['status','=',1]],'title,add_time,content,news_id,cat_id',1,3,'news_id DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 添加楼栋区域绑定
     * @author:zhubaodi
     * @date_time: 2021/10/11 17:11
     */
    public function addVillgeArea(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['area'] = $this->request->post('area','','trim');//区域点位
        $data['single_id'] = $this->request->post('single_id',0);//楼栋id
        $data['img'] = $this->request->post('img',0);//绘制的图片

        if(empty($data['area'])){
            return api_output_error(1001,'区域点位不能为空');
        }
        if(empty($data['single_id'])){
            return api_output_error(1001,'楼栋id不能为空');
        }


        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->addVillgeArea($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 添加楼栋区域绑定
     * @author:zhubaodi
     * @date_time: 2021/10/11 17:11
     */
    public function editVillgeArea(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['area'] = $this->request->post('area','','trim');//区域点位
        $data['single_id'] = $this->request->post('single_id',0);//楼栋id
        if(empty($data['area'])){
            return api_output_error(1001,'区域点位不能为空');
        }
        if(empty($data['single_id'])){
            return api_output_error(1001,'楼栋id不能为空');
        }


        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->editVillgeArea($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);

    }


    /**
     * 查询楼栋区域列表
     * @author:zhubaodi
     * @date_time: 2021/10/11 17:11
     */
    public function getVillgeAreaList(){
        $village_id = $this->adminUser['village_id'];
        $village_name = $this->adminUser['village_name'];
        $single_id = $this->request->post('single_id',0,'intval');
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getVillgeAreaList($village_id,$village_name,$single_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);

    }
    /**
     * 查询楼栋区域详情
     * @author:zhubaodi
     * @date_time: 2021/10/11 17:11
     */
    public function getVillgeAreaInfo(){
        $village_id = $this->adminUser['village_id'];
        $single_id = $this->request->post('single_id',0);//楼栋id
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getVillgeAreaInfo($village_id,$single_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 查询楼栋列表
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getSingleList(){
        $village_id = $this->adminUser['village_id'];
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getSingleList($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询小区底图
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getVillageArea(){
        $village_id = $this->adminUser['village_id'];
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getVillageArea($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }
    /**
     * 查询小区底图
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getVillgePayOrder(){
        $village_id = $this->adminUser['village_id'];
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getVillgePayOrder($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 查询小区底图
     * @author:zhubaodi
     * @date_time: 2021/10/11 18:19
     */
    public function getVillgeRepair(){
        $village_id = $this->adminUser['village_id'];
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getVillgeRepair($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 更新userbind表birth字段
     * @author lijie
     * @date_time 2021/10/18
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateBirth()
    {
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $where[] = ['status','in','1,2'];
        $where[] = ['id_card','<>',''];
        $where[] = ['birth','=',''];
        $field = 'pigcms_id,id_card';
        $userInfoLists = $service_house_village_user_bind->getList($where,$field);
        if($userInfoLists){
            foreach ($userInfoLists as $v){
                $birth = substr($v['id_card'],6,4).'-'.substr($v['id_card'],10,2).'-'.substr($v['id_card'],12,2);
                $service_house_village_user_bind->saveUserBind(['pigcms_id'=>$v['pigcms_id']],['birth'=>$birth]);
            }
        }
        return api_output(0,[]);
    }

    /**
     * 获取天使之眼的底图
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     */
    public function getAngeleyeImg(){
        $village_id = $this->adminUser['village_id'];
        // $village_id=50;
        $type = $this->request->post('type',0);
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getAngeleyeImg($village_id,$type);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 添加天使之眼定位坐标
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     */
    public function addCoordinate(){
         $village_id = $this->adminUser['village_id'];
      //  $village_id=50;
        $type = $this->request->post('type',0);
        $coordinate = $this->request->post('coordinate',0);
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->addCoordinate($village_id,$coordinate,$type);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 绑定设备
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     */
    public function addDeviceCoordinate(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['device_id'] = $this->request->post('device_id',0);
        $data['coordinateX'] = $this->request->post('coordinateX',0);
        $data['coordinateY'] = $this->request->post('coordinateY',0);
        $data['img'] = $this->request->post('img',0);
        $data['type'] = $this->request->post('type',0);
        $data['real_device_id'] = $this->request->post('new_id',0);
        $data['id'] = $this->request->post('id',0);
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->addDeviceCoordinate($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除天使之眼定位坐标
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     */
    public function delCoordinate(){
         $village_id = $this->adminUser['village_id'];
        $coordinate = $this->request->post('coordinate',0);//楼栋id
        $type = $this->request->post('type',0);
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->delCoordinate($village_id,$coordinate,$type);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 编辑天使之眼定位坐标
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     */
    public function editCoordinate(){
        $village_id = $this->adminUser['village_id'];
        $coordinate = $this->request->post('coordinate',0);//楼栋id
        $device_id = $this->request->post('device_id',0);//楼栋id
        $type = $this->request->post('type',0);
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->editCoordinate($village_id,$coordinate,$device_id,$type);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询设备列表
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     */
    public function getDeviceList(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['devicce_no'] = $this->request->post('devicce_no');
        $data['devicce_name'] = $this->request->post('devicce_name',0);
        $data['devicce_type'] = $this->request->post('devicce_type',0);
        $data['type'] = $this->request->post('type',0);
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getDeviceList($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    public function test(){
        $service_hardware_brand=new HardwareBrandService();
        try{
            $res = $service_hardware_brand->getType();
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 一键同步设备
     * @author:zhubaodi
     * @date_time: 2022/4/26 16:54
     */
    public function getDeviceInfo(){
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getDeviceInfo();
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }


    public function test1(){
        $data['floor_photo_coordinate']=json_encode([0,0]);//原有的left,top坐标
        $data['floor_photo_size']=json_encode([1980,1080]);//原有的图片尺寸
        $data['floor_photo_coordinate_new']=json_encode([124.1234,523.1478]);//现有的left,top坐标
        $data['floor_photo_size_new']=json_encode([1200,800]);//现有的图片尺寸
        $data['coordinate']=json_encode([425.1236,687.5874]);//原有的点位坐标

        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->count_coordinate($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 添加绘制区域
     * @author:zhubaodi
     * @date_time: 2022/5/17 19:22
     */
    public function addCoordinatefloor(){
        $data['village_id'] = $this->adminUser['village_id'];

        $data['singleArr']=$this->request->post('singleArr','','trim');
        $data['floor_photo_coordinate']=$this->request->post('floor_photo_coordinate','','trim');//图片的left,top坐标
        $data['floor_photo_size']=$this->request->post('floor_photo_size','','trim');//图片的图片尺寸
        $serviceAockpit = new AockpitService();
        fdump_api([$data,$data['village_id']],'addCoordinatefloor-0518',1);
        try{
            $res = $serviceAockpit->addCoordinatefloor($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 绑定楼顶区域
     * @author:zhubaodi
     * @date_time: 2022/5/17 19:24
     */
    public function addAreaSingle(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['single_id']=$this->request->post('single_id',0,'intval');
        $data['area_id']=$this->request->post('area_id',0,'intval');

        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->addAreaSingle($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 删除区域
     * @author:zhubaodi
     * @date_time: 2022/5/17 19:24
     */
    public function delArea(){
        $data['village_id'] = $this->adminUser['village_id'];
       // $data['single_id']=$this->request->post('single_id',0,'intval');
        $data['area_id']=$this->request->post('area_id',0,'intval');
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->delArea($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 绘制操作查询区域点位
     * @author:zhubaodi
     * @date_time: 2022/5/18 8:13
     */
    public function getAreaCoordinate(){
        $data['village_id'] = $this->adminUser['village_id'];

        $data['floor_photo_coordinate']=$this->request->post('floor_photo_coordinate','','trim');//图片的left,top坐标
        $data['floor_photo_size']=$this->request->post('floor_photo_size','','trim');//图片的图片尺寸
     //   print_r($data);die;
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getAreaCoordinate($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 查询已绑定区域点位
     * @author:zhubaodi
     * @date_time: 2022/5/18 8:13
     */
    public function getSingleAreaCoordinate(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['floor_photo_coordinate']=$this->request->post('floor_photo_coordinate','','trim');//图片的left,top坐标
        $data['floor_photo_size']=$this->request->post('floor_photo_size','','trim');//图片的图片尺寸
        $serviceAockpit = new AockpitService();
        try{
            $res = $serviceAockpit->getSingleAreaCoordinate($data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$res);
    }

}