<?php
/**
 * 智能电表后台左侧目录
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/4/13 9:48
 */

namespace app\community\model\service;

use app\community\model\db\HouseMeterAdminUser;

class HouseMeterMenuService
{

    public function formartMenuList($admin_id) {

        if (!$admin_id) {
            return [];
        }
        $service_admin_user = new HouseMeterAdminUser();
        $admin_user_info = $service_admin_user->getInfo(['id' => $admin_id]);

        $returnArr = [];

        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_index';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'meter_0';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '控制台',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['path'] = '/meter/adminUser/editPassword';
        $returnArr[] = $tmpMenu;


        //修改密码
        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_index';
        $tmpMenu['parentId'] = 'meter_0';
        $tmpMenu['id'] = 'meter_1';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '修改密码',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'editPassword';
        $tmpMenu['path'] = '/meter/adminUser/editPassword';
        $returnArr[] = $tmpMenu;

        //管理员列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_admin_user';
        $tmpMenu['parentId'] = 'meter_0';
        $tmpMenu['id'] = 'meter_2';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '管理员列表',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'adminUserList';
        $tmpMenu['path'] = '/meter/adminUser/adminUserList';
        $returnArr[] = $tmpMenu;

        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_electric_list';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'meter_9';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '智能电表',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['path'] = '/meter/electric/electricList';
        $returnArr[] = $tmpMenu;




        // 智能电表管理-电表管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_electric_list';
        $tmpMenu['parentId'] = 'meter_9';
        $tmpMenu['id'] = 'meter_6';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '电表管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'electricList';
        $tmpMenu['path'] = '/meter/electric/electricList';
        $returnArr[] = $tmpMenu;


        // 智能电表管理-分组列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_electric_group';
        $tmpMenu['parentId'] = 'meter_9';
        $tmpMenu['id'] = 'meter_7';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '集中器列表',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'groupList';
        $tmpMenu['path'] = '/meter/electric/groupList';
        $returnArr[] = $tmpMenu;


        // 智能电表管理-电表设置
        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_electric_set';
        $tmpMenu['parentId'] = 'meter_9';
        $tmpMenu['id'] = 'meter_8';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '电表设置',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'electricSet';
        $tmpMenu['path'] = '/meter/electric/set';
        $returnArr[] = $tmpMenu;


        // 收费标准管理-城市列表
        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_electric_area';
        $tmpMenu['parentId'] = '0';
        $tmpMenu['id'] = 'meter_3';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '收费标准管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'electricAreaList';
        $tmpMenu['path'] = '/meter/electricPrice/getAreaList';
        $returnArr[] = $tmpMenu;


        // 收费标准管理-添加收费标准
        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_electric_price';
        $tmpMenu['parentId'] = '0';
        $tmpMenu['id'] = 'meter_4';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '添加收费标准',
            'show' => false,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'electricPrice';
        $tmpMenu['path'] = '/meter/electricPrice/addElectricPrice';
        $returnArr[] = $tmpMenu;

        // 账单管理
        $tmpMenu = [];
        $tmpMenu['name'] = 'meter_order_list';
        $tmpMenu['parentId'] = '0';
        $tmpMenu['id'] = 'meter_5';
        $tmpMenu['meta'] = [
            'icon' => 'tasks',
            'title' => '账单管理',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'orderList';
        $tmpMenu['path'] = '/meter/payment/orderList';
        $returnArr[] = $tmpMenu;





        return $returnArr;
    }

}