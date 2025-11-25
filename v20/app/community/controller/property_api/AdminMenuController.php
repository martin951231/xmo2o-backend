<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/8/3 16:49
 */

namespace app\community\controller\property_api;

use app\community\controller\CommunityBaseController;


use app\community\model\service\PropertyMenuService;
use think\facade\Cache;
use app\community\model\service\HouseVillageService;

class AdminMenuController extends CommunityBaseController{



    /**
     * desc: 返回网站菜单信息,用于菜单展示
     * return :array
     */
    public function menuList(){

        $adminUser = $this->adminUser;
        // 菜单
        $systemMenuService = new PropertyMenuService();
        $property_login_role = $this->login_role;
        $property_uid = $this->normal_admin_id;
        $systemMenu = $systemMenuService->formartMenuList([],$adminUser,$property_login_role,$property_uid);
        $returnArr['systemMenu'] = $systemMenu;
        return api_output(0, $returnArr);
    }

    public function config() {
        $village_id = $this->request->param('village_id','0','intval');
        $property_id = $this->request->param('property_id','0','intval');
        if (!$village_id && !$property_id) {
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