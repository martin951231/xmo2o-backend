<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/28 11:58
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;

use app\community\model\service\ConfigService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\PackageOrderService;

class MyController extends BaseController{

    /**
     * 个人中心信息
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/28 11:58
     * @return \json
     */
    public function index() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->login_info['village_id'];
        $property_id = $this->login_info['property_id'];
        $property_id = $property_id>0 ? $property_id:0;
        $app_type = $this->request->post('app_type','');
        if (empty($village_id)) {
            $village_id = $this->request->param('village_id','','intval');
            if (empty($village_id) && !in_array($this->login_role,array(3,4))) {
                return api_output(1001,[],'缺少对应小区！');
            }else if(in_array($this->login_role,array(3,4)) && $property_id<1){
                return api_output(1001,[],'缺少对应物业id！');
            }
        }
        if(in_array($this->login_role,array(3,4))){
            $village_id=0;
        }
        $village_id=$village_id>0 ? $village_id:0;
        //套餐过滤 start
        $servicePackageOrder = new PackageOrderService();
        $dataPackage = $servicePackageOrder->getPropertyOrderPackage($property_id,$village_id);
        if ($dataPackage) {
            $dataPackage = $dataPackage->toArray();
            $package_content = $dataPackage['content'];
        } else {
            $package_content  = [];
        }
        //套餐过滤 end
        //小区信息
        $login_info = $arr['user'];
        $service_house_village_user = new HouseVillageUserService();
        $service_house_village = new HouseVillageService();
        $where_work = [];
        $where_work[] = ['village_id', '=', $village_id];
        $where_work[] = ['is_del', '=', 0];
        $ishaveWorker=false;
        if(isset($login_info['wid']) && ($login_info['wid']>0)){
            $where_work[] = ['wid', '=', $login_info['wid']];
            $ishaveWorker=true;
        }
        if ($login_info['login_phone']) {
            $where_work[] = ['phone', '=', $login_info['login_phone']];
            $ishaveWorker=true;
        }
        if($ishaveWorker){
            $work = $service_house_village_user->getHouseWorker($where_work,'job_number');
            // 赋予登录者编号
            if ($work && isset($work['job_number'])) {
                $login_info['login_number'] = $work['job_number'];
            }else{
                $login_info['login_number'] ='';
            } 
        }
        if($village_id>0){
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'village_address');
            $login_info['address'] = $village_info['village_address'];
        }
        $arr = [];
        if (!$this->config['site_url']) {
            // 初始化 配置 业务层
            $service_config = new ConfigService();
            $where_config = [];
            $where_config[] = ['name', '=', "site_url"];
            $config = $service_config->get_config_list($where_config);
            $this->config['site_url'] = $config['site_url'];
        }
        $site_url = $this->config['site_url'];
        $static_resources = static_resources(true);
        if (isset($login_info['address'])&&$login_info['address']) {
            $address = str_replace('&quot;','',$login_info['address']);
        } else {
            $address = '';
        }
        if(in_array($this->login_role,array(3,4))){
            $address = $this->login_info['property_address'];
        }
        $login_user = [
            'login_name' => isset($login_info['login_name'])&&$login_info['login_name'] ? $login_info['login_name'] : $login_info['village_name'],
            'login_phone' => isset($login_info['login_phone'])&&$login_info['login_phone'] ? $login_info['login_phone'] : '',
            'login_number' => isset($login_info['login_number'])&&$login_info['login_number'] ? $login_info['login_number'] : '',
            'login_avatar' => $site_url . $static_resources . 'images/avatar.png',
            'login_role' => $this->login_role,
            'uid' => $this->_uid,
            'address' => $address ? $address : '',
            'property_id'=>$property_id,
            'village_id'=>$village_id,
        ];
        $arr['login_user'] = $login_user;

        // 目录
        $catalog = [];
        // url 没有值时候的提示
        $tip = '开发工程师正在加班奔跑中...';
        $time = 1;
        //套餐过滤
        $hardware_intersect = array_intersect([7,8],$package_content);
        if(0 && $hardware_intersect && count($hardware_intersect)>0) {
            // 人脸识别的上传图片
            if($app_type != 'ios' && $app_type != 'android'){
                $catalog[] = [
                    'type' => 'upload_pic',
                    'title' => '上传图片',
                    'tip' => $tip,
                    'url' => '',
                    'icon' => $site_url . $static_resources . 'images/my/upload_img.png?time=' . $time
                ];
                // 开门
                $catalog[] = [
                    'type' => 'open_door',
                    'title' => '一键开门',
                    'tip' => $tip,
                    'url' => '',
                    'icon' => $site_url . $static_resources . 'images/my/door_open.png?time=' . $time
                ];
            }
        }
        // 设置 暂时只有APP显示
       //  if($app_type == 'ios' || $app_type == 'android'){
            $catalog[] = [
                'type'  => 'setting',
                'title' => '设置',
                'tip'   => $tip,
                'url'   => $site_url.'/packapp/plat/pages/Community/index/setup',
                'icon'  => $site_url . $static_resources . 'images/my/setting.png?time='.$time
            ];
   //      }

        $judgeIsPropertyOrVillage = (new ManageAppLoginService())->judgeIsPropertyOrVillage($this->login_role, $village_id, $property_id);
        if ($judgeIsPropertyOrVillage) {
            $catalog[] = [
                'type'  => 'camera',
                'title' => '视频监控',
                'tip'   => $tip,
                'url'   => $site_url.'/packapp/community/pages/Community/videoSurveillance/hawkEye',
                'icon'  => $site_url . $static_resources . 'images/my/video_camera.png?time='.$time
            ];
        }

        // 退出
        $catalog[] = [
            'type'  => 'login_out',
            'title' => '退出登录',
            'tip'   => $tip,
            'url'   => '',
            'icon'  => $site_url . $static_resources . 'images/my/login_out.png?time='.$time
        ];

        $arr['catalog'] = $catalog;

        return api_output(0,$arr);
    }
}