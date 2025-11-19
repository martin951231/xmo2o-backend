<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/22 15:41
 */

namespace app\community\controller\street_community;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\AreaStreetConfig;
use app\community\model\service\AreaService;
use app\community\model\service\AreaStreetService;


class ConfigController extends CommunityBaseController{

    public function index() {
        $info = $this->adminUser;
        $service_area = new AreaService();
        $service_area_street = new AreaStreetService();
        $out = [];
        if (0==$info['area_type']) {
            $info['name'] = $info['area_name'];
            $save = [];
            if (empty($info['area'])) {
                $info['area'] = $info['area_pid'];
                $save['area'] = $info['area_pid'];
            }
            $area_info = $service_area->getAreaOne(['area_id'=>$info['area']],'area_pid,area_name');
            if (empty($info['city_id'])) {
                $info['city_id'] = $area_info['area_pid'];
                $save['city_id'] = $area_info['area_pid'];
            }
            $city_pid=$info['city_id'];
            $area_info0811 = $service_area->getAreaOne(['area_id'=>$info['city_id']],'area_pid,area_name');
            if($area_info0811 && !empty($area_info0811['area_pid'])){
                $city_pid=$area_info0811['area_pid'];
            }

            // 省市区 中 区名称
            $info['area_name'] = $area_info['area_name'];
            $city_info = $service_area->getAreaOne(['area_id'=>$info['city_id']],'area_pid,area_name');
            if (empty($info['province_id'])) {
                $info['province_id'] = $city_info['area_pid'];
                $save['province_id'] = $city_info['area_pid'];
            }
            // 省市区 中 市名称
            $info['city_name'] = $city_info['area_name'];
            $province_info = $service_area->getAreaOne(['area_id'=>$city_pid],'area_name');
            // 省市区 中 省名称
            $info['province_name'] = $province_info['area_name'];

            $choose_area = [];
            $choose_area[] = intval($info['province_id']);
            $choose_area[] = intval($info['city_id']);
            $choose_area[] = intval($info['area']);

            $out['choose_area'] = $choose_area;

            $where = [
                ['c.status', 'in', '1,2'],
                ['b.status', 'in', '1,2'],
                ['a.area_id', '=', $info['area_id']],
            ];
            $count = $service_area_street->getStreetVillageUserNum($where);
            $info['population'] = $count;
        } elseif (1==$info['area_type']) {
            $info['name'] = $info['area_name'];
            $fid_street = $service_area_street->getAreaStreet(['area_id'=>$info['area_pid']],'area_id,area_pid,area_name');
            $save = [];
            if (empty($info['area']) && $fid_street) {
                $info['area'] = $fid_street['area_pid'];
                $save['area'] = $fid_street['area_pid'];
            }
            $area_info = $service_area->getAreaOne(['area_id'=>$info['area']],'area_pid,area_name');
            if (empty($info['city_id'])) {
                $info['city_id'] = $area_info['area_pid'];
                $save['city_id'] = $area_info['area_pid'];
            }
            $city_pid=$info['city_id'];
            $area_info0811 = $service_area->getAreaOne(['area_id'=>$info['city_id']],'area_pid,area_name');
            if($area_info0811 && !empty($area_info0811['area_pid'])){
                $city_pid=$area_info0811['area_pid'];
            }
            // 省市区 中 区名称
            $info['area_name'] = $area_info['area_name'];
            $city_info = $service_area->getAreaOne(['area_id'=>$info['city_id']],'area_pid,area_name');
            if (empty($info['province_id'])) {
                $info['province_id'] = $city_info['area_pid'];
                $save['province_id'] = $city_info['area_pid'];
            }
            // 省市区 中 市名称
            $info['city_name'] = $city_info['area_name'];
            $province_info = $service_area->getAreaOne(['area_id'=>$city_pid],'area_name');
            // 省市区 中 省名称
            $info['province_name'] = $province_info['area_name'];

            $choose_area = [];
            $choose_area[] = intval($info['province_id']);
            $choose_area[] = intval($info['city_id']);
            $choose_area[] = intval($info['area']);

            $out['choose_area'] = $choose_area;

            $where = [
                ['c.status', 'in', '1,2'],
                ['b.status', 'in', '1,2'],
                ['a.area_id', '=', $info['area_id']],
            ];
            $service_area_street = new AreaStreetService();
            $count = $service_area_street->getCommunityVillageUserNum($where);
            $info['population'] = $count;
        }

        // 默认写死
        $area_options = [];
        $area_options[] = [
            'value' => $info['province_id'],
            'label' => $info['province_name'],
            'children' => [
                [
                    'value' => $info['city_id'],
                    'label' => $info['city_name'],
                    'children' => [
                        [
                            'value' => $info['area'],
                            'label' => $info['area_name'],
                        ]
                    ]
                ]
            ]
        ];
        $out['area_options'] = $area_options;

        if (isset($info['detail_address'])) {
            $detail_address = $info['detail_address'];
            $info['address'] = $detail_address;
        }
        if (isset($info['logo'])) {
            $info['logo_img'] = $info['logo'];
            $info['logo'] = replace_file_domain($info['logo']);
        } else {
            $info['logo_img'] = '';
        }
        $info['visualize_nav'] = $service_area_street->getAreaStreetVisualizeNav($info);

        $configCustomizationService=new ConfigCustomizationService();
        $vr720yunCustomized = $configCustomizationService->getVr720yunCustomized();
        $info['visualize_nav']['have_vr_720yun']=0;
        if ($vr720yunCustomized) {
            $info['visualize_nav']['have_vr_720yun']=1;
            $dbAreaStreetConfig = new AreaStreetConfig();
            $whereConfig = [];
            if (isset($this->adminUser['area_id']) && $this->adminUser['area_id'] > 0) {
                $whereConfig[] = ['area_id', '=', $this->adminUser['area_id']];
            } else {
                $whereConfig[] = ['area_id', '=', $this->adminUser['log_uid']];
            }
            $vr720yunConfig = $dbAreaStreetConfig->getFind($whereConfig, 'area_id, vr_720yun_switch, vr_720yun_url');
            if (empty($vr720yunConfig)) {
                $vr720yunConfig = [
                    'area_id' => 0, 'vr_720yun_switch' => 0, 'vr_720yun_url' => '',
                ];
            }
            $info['visualize_nav']['vr720yunConfig'] = $vr720yunConfig;
        }else{
            $info['visualize_nav']['vr720yunConfig'] = array('area_id' => 0, 'vr_720yun_switch' => 0, 'vr_720yun_url' => '');
        }
        
        $info['visualize_nav_status']=($info['area_type'] == 1) ? true : false;
        unset($info['nav_info']);
        $out['info'] = $info;
        return api_output(0, $out);
    }

    public function addIndex() {
        $address = $this->request->param('address', '', 'trim');
        $visualize_nav = $this->request->param('visualize_nav', []);
        if(empty($address)){
            return api_output_error(1001,'请上传地址！');
        }
        $area_covered = $this->request->param('area_covered', '', 'floatval');
        $phone = $this->request->param('phone', '', 'trim');
        $age = $this->request->param('age', '', 'trim');
        $logo = $this->request->param('logo', '', 'trim');
        $info = $this->adminUser;
        $data = [
            'address' => $address,
            'area_covered' => $area_covered,
            'phone' => $phone,
            'area_id' => $info['area_id'],
            'age' => $age,
            'logo'=>$logo,
            'last_time'=>time()
        ];
        if(intval($this->adminUser['area_type']) == 0 && !empty($visualize_nav)){
           $data['nav_info']=json_encode([
               'visualize_title'=>isset($visualize_nav['visualize_title']) ? $visualize_nav['visualize_title'] : '',
               'visualize_nav1'=>isset($visualize_nav['visualize_nav1']) ? $visualize_nav['visualize_nav1'] : '',
               'visualize_nav2'=>isset($visualize_nav['visualize_nav2']) ? $visualize_nav['visualize_nav2'] : '',
               'visualize_nav3'=>isset($visualize_nav['visualize_nav3']) ? $visualize_nav['visualize_nav3'] : '',
               'visualize_nav4'=>isset($visualize_nav['visualize_nav4']) ? $visualize_nav['visualize_nav4'] : '',
           ],JSON_UNESCAPED_UNICODE);
        }
        $service_area_street = new AreaStreetService();
        $area_id = $service_area_street->addIndex($data);
        if($area_id) {
            if (isset($visualize_nav['vr720yunConfig'])) {
                $vr720yunConfig = $visualize_nav['vr720yunConfig'] ? $visualize_nav['vr720yunConfig'] : [];
                if (! isset($vr720yunConfig['area_id']) || ! $vr720yunConfig['area_id']) {
                    $vr720yunConfig['area_id'] = $area_id;
                }
                $service_area_street->updateVr720yunConfig($vr720yunConfig);
            }
            return api_output(0, ['area_id'=>$area_id], "更新成功");
        } else {
            return api_output_error(-1, "更新失败,请检查是否有更新");
        }
    }
    //上传街道logo
    public function upload(){
        $file = $this->request->file('img');
        $service_area_street = new AreaStreetService();
        try{
            $imgurl = $service_area_street->uploads($file);
            return api_output(0, $imgurl, "成功");
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    //富文本上传图片
    public function weditorUpload()
    {
        $file = $this->request->file('img');
        $service_area_street = new AreaStreetService();
        $putFile = 'weditor';
        try{
            $imgurl = $service_area_street->uploads($file,$putFile);
            $data['url'] = dispose_url($imgurl);
            return json($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getStreetComunityList(){
        $comunityName = $this->request->param('comunityName','','trim');
        
        $whereArr=array();
        $street_id=0;
        if ($this->adminUser['area_type'] == 1) {
            // 是社区
            $street_id=$this->adminUser['area_pid'];
            $community_id=$this->adminUser['area_id'];
        }
        else{
            //是街道
            $street_id=$this->adminUser['area_id'];
            $community_id=0;
        }
        $page = $this->request->post('page',1,'intval');
        $whereArr[]=array('area_pid','=',$street_id);
        $whereArr[]=array('area_type','=',1);
        if($comunityName){
            $whereArr[]=array('area_name','like','%'.$comunityName.'%');
        }
        try{
            $areaStreetService = new AreaStreetService();
            $field='area_id,area_pid,area_name,area_type,is_open,is_hot,long,lat,add_time,address,phone,detail_address,logo';
            $comunityList=$areaStreetService->getAreaStreetLists($whereArr,$field,$page,20);
            return api_output(0, $comunityList);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        
    }

    public function getStreetComunityHouseList(){
        $houseName = $this->request->param('houseName','','trim');

        $whereArr=array();
        $street_id=0;
        $whereArr[]=array('village_id','>',0);
        if ($this->adminUser['area_type'] == 1) {
            // 是社区
            $street_id=$this->adminUser['area_pid'];
            $community_id=$this->adminUser['area_id'];
            $whereArr[]=array('street_id','=',$street_id);
            $whereArr[]=array('community_id','=',$community_id);
        }
        else{
            //是街道
            $street_id=$this->adminUser['area_id'];
            $community_id=0;
            $whereArr[]=array('street_id','=',$street_id);
        }
        $page = $this->request->post('page',1,'intval');
        
        $whereArr[]=array('status','in',[0,1,2,3,4]);
        if($houseName){
            $whereArr[]=array('village_name','like','%'.$houseName.'%');
        }
        try{
            $areaStreetService = new AreaStreetService();
            $field='village_id,village_name,last_time,village_address,property_name,property_phone,property_address,province_id,city_id,area_id,circle_id,status,property_id,street_id,community_id';
            $comunityList=$areaStreetService->getAreaStreetVillageLists($whereArr,$field,$page,20);
            return api_output(0, $comunityList);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getConfigCustomization(){
        $street_id= $this->adminUser && isset($this->adminUser['area_id']) ? $this->adminUser['area_id']:0;
        $area_type= $this->adminUser && isset($this->adminUser['area_type']) ? $this->adminUser['area_type']:0;
        $datas=array('street_id'=>$street_id,'area_type'=>$area_type);
        $configCustomizationService=new ConfigCustomizationService();
        $islantiancom=$configCustomizationService->getFuncZhihuilantiancomSpecial();
        $datas['street_worker_party_member']=0;
        if($islantiancom){
            $datas['street_worker_party_member']=1;
        }
        return api_output(0,$datas);
    }
}