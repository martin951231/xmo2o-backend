<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/8 19:06
 */

namespace app\community\controller\village_api;
use app\community\controller\CommunityBaseController;
use app\community\model\service\VillageMenuService;
use think\facade\Cache;
use app\community\model\service\HouseVillageService;

class AdminMenuController extends CommunityBaseController{

    public function getVillagePropertyId()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        if($village_id){
            $href = '/user/community/login';
            $data = ['href'=>$href,'name' => 'villageLogin','params' => ['village' => $village_id]];
        }else{
            $href = '/user/community/login';
            $data = ['href'=>$href,'name' => 'propertyLogin','params' => ['property' => $property_id]];
        }
        return api_output(0,$data);
    }
    /**
     * desc: 返回网站菜单信息,用于菜单展示
     * return :array
     */
    public function menuList(){

        $adminUser = $this->adminUser;
        if (!in_array($this->login_role,$this->villageRole)) {
            return api_output(1002, [], "小区登录信息不存在");
        }
        $property_login_role = $this->login_role;
        $property_uid = $this->normal_admin_id;
        if(in_array($this->login_role,$this->villageOrderCheckRole) && $adminUser && isset($adminUser['menus']) && $adminUser['menus']){
            //工作人员登录
            if (is_array($adminUser['menus'])) {
                $adminUser['menus'][] = 112163;
            }else{
                $adminUser['menus'] = $adminUser['menus'].',112163';
            }
        }
        // 菜单
        $systemMenuService = new VillageMenuService();
        $systemMenu = $systemMenuService->formartMenuList([],$adminUser,$property_login_role,$property_uid) ;
        $returnArr['systemMenu'] = $systemMenu;
        return api_output(0, $returnArr);
    }

    public function config() {
        $village_id = $this->request->param('village_id','0','intval');
        $property_id = $this->request->param('property_id','0','intval');
        if (!$village_id && !$property_id && !empty($this->adminUser)) {
            $village_id = $this->adminUser['village_id'];
            $property_id =  $this->adminUser['property_id'];
            $login_role = $this->login_role;
            if (in_array($login_role,$this->propertyRole)) {
                $type = 2;//物业
            } else {
                $type = 1;//小区
            }
        } elseif ($village_id) {
            $type = 1;//小区
        } elseif ($property_id) {
            $type = 2;//物业
        } else {
            return api_output(0,[]);
        }
        $HouseVillageService = new HouseVillageService();
        try{
            $list = $HouseVillageService->houseConfig($type, $property_id, $village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

}