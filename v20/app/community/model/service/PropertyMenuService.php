<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/8/3 16:58
 */

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\PropertyAdminAuth;
use app\community\model\service\PackageOrderService;

use app\community\model\db\ApplicationBind;
use app\community\model\db\HouseEnterpriseWxBind;
use app\common\model\service\ConfigService;
use customization\customization;
class PropertyMenuService
{
    use customization;

    /**
     * Notes: 返回前端需要的菜单格式
     * @param array $menuList
     * @param array $adminUser
     * @param int $property_login_role
     *@param int $property_uid
     * @return array
     * @author: wanzy
     * @date_time: 2020/8/3 16:58
     */
    public function formartMenuList($menuList = [],$adminUser=[],$property_login_role=0,$property_uid=0) {
        //2020/8/28 start 套餐功能
        $property_id = $adminUser['property_id'];
        $servicePackageOrder=new PackageOrderService();
        $package_content = $servicePackageOrder->getPackageContent($property_id);
        $content_id = $package_content['content'];
        if(!$content_id){
            $content_id = [0];
        }
        $adminLoginService = new AdminLoginService();
        $roleJudge = in_array($property_login_role,$adminLoginService->propertyUserArr);
        if($roleJudge && $property_uid){
            $db_property_admin_auth = new PropertyAdminAuth();
            $auth_info = $db_property_admin_auth->getOne(['admin_id'=>$property_uid,'property_id'=>$property_id],'menus');
            if($auth_info){
                $auth_info = $auth_info->toArray();
            }
            if($auth_info){
                $property_menus = explode(',',$auth_info['menus']);
            }
        }
        //2020/8/28 end 套餐功能
        $returnArr = [];

        //****************首页*********************/
        $tmpMenu = [];
        $tmpMenu['name'] = 'property_index';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'property_1';
        $tmpMenu['meta'] = [
            'icon' => 'iconindex1',
            'title' => '首页',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'System';
        $tmpMenu['path'] = '/property/property.iframe/property_index';
        $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewProperty&a=index_new&iframe=true');
        $returnArr[] = $tmpMenu;

        //****************物业设置*********************/
        if(true){
            $tmpMenu = [];
            $tmpMenu['name'] = 'property_control_box';
            $tmpMenu['parentId'] = 0;
            $tmpMenu['id'] = 'property_2';
            $tmpMenu['meta'] = [
                'icon' => 'iconkongzhitai',
                'title' => '物业设置',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'RouteView';
            $tmpMenu['redirect'] = '/property/property.iframe/property_config';
            $returnArr[] = $tmpMenu;

	        if(!$roleJudge){
		        //新版基本信息
		        $tmpMenu = [];
		        $tmpMenu['name'] = 'propertyConfig';
		        $tmpMenu['parentId'] = 'property_2';
		        $tmpMenu['id'] = 'property_2_11';
		        $tmpMenu['meta'] = [
			        'icon' => '',
			        'title' => '基本信息',
			        'show' => true,
			        'permission' => 'system',
		        ];
		        $tmpMenu['component'] = 'propertyConfigSet';
		        $tmpMenu['path'] = '/property/property/Property/config';
		        $returnArr[] = $tmpMenu;
	        }

	        $tmpMenu = [];
	        $tmpMenu['name'] = 'propertyPassword';
	        $tmpMenu['parentId'] = 'property_2';
	        $tmpMenu['id'] = 'property_2_12';
	        $tmpMenu['meta'] = [
		        'icon' => '',
		        'title' => '修改密码',
		        'show' => true,
		        'permission' => 'system',
	        ];
	        $tmpMenu['component'] = 'propertyPassword';
	        $tmpMenu['path'] = '/property/property/Property/password';
	        $returnArr[] = $tmpMenu;


            if(!$roleJudge || (isset($property_menus) && in_array('property_2_2',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'frameworkList';
                $tmpMenu['parentId'] = 'property_2';
                $tmpMenu['id'] = 'property_2_2';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '组织架构',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'frameworkList';
                $tmpMenu['path'] = '/property/framework/frameworkList';
                $returnArr[] = $tmpMenu;

            }

            if(!$roleJudge || (isset($property_menus) && in_array('property_2_3',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'powerList';
                $tmpMenu['parentId'] = 'property_2';
                $tmpMenu['id'] = 'property_2_3';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '权限管理',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'powerList';
                $tmpMenu['path'] = '/property/power/powerList';
                $returnArr[] = $tmpMenu;
            }
            $configCustomizationService= new ConfigCustomizationService();
            $newOrganizationWorker=$configCustomizationService->getWSXCustomizedOpen('organizationWorker');
            if($newOrganizationWorker && (!$roleJudge || (isset($property_menus) && in_array('property_2_3',$property_menus)))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'propertyWorker';
                $tmpMenu['parentId'] = 'property_2';
                $tmpMenu['id'] = 'property_2_4';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '人员管理',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'propertyWorker';
                $tmpMenu['path'] = '/property/framework/propertyWorker';
                $returnArr[] = $tmpMenu;
            }

            if(!$roleJudge || (isset($property_menus) && in_array('property_20',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_packages_buy';
                $tmpMenu['parentId'] = 'property_2';
                $tmpMenu['id'] = 'property_20';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '物业服务套餐',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'RouteView';
                $tmpMenu['path'] = '/property/property/package/packagesBuy';
                $returnArr[] = $tmpMenu;
            }

            if(!$roleJudge || (isset($property_menus) && in_array('property_21',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_packages_order_buy';
                $tmpMenu['parentId'] = 'property_20';
                $tmpMenu['id'] = 'property_21';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '订购功能套餐',
                    'show' => true,
                    'permission' => 'system',
                    'keepAlive' => false
                ];
                $tmpMenu['component'] = 'propertyPackages';
                $tmpMenu['path'] = '/property/property/package/packagesBuy';
                $returnArr[] = $tmpMenu;
            }
            if(!$roleJudge || (isset($property_menus) && in_array('property_21_1',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_packages_Room_order_buy';
                $tmpMenu['parentId'] = 'property_20';
                $tmpMenu['id'] = 'property_21_1';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '订购房间套餐',
                    'show' => true,
                    'permission' => 'system',
                    'keepAlive' => false
                ];
                $tmpMenu['component'] = 'propertyPackagesRoomOrderBuy';
                $tmpMenu['path'] = '/property/property/package/packagesRoomOrderBuy';
                $returnArr[] = $tmpMenu;
            }

            if(!$roleJudge || (isset($property_menus) && in_array('property_22',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_packages_room_order_buy';
                $tmpMenu['parentId'] = 'property_20';
                $tmpMenu['id'] = 'property_22';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '功能套餐-购买订单详情',
                    'show' => false,
                    'permission' => 'system',
                    'keepAlive' => false
                ];
                $tmpMenu['component'] = 'propertyPackagesRoomBuy';
                $tmpMenu['path'] = '/property/property/package/packagesRoomBuy';
                $returnArr[] = $tmpMenu;
            }

            if(!$roleJudge || (isset($property_menus) && in_array('property_24',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_packages_single_buy_order';
                $tmpMenu['parentId'] = 'property_20';
                $tmpMenu['id'] = 'property_24';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '订购房间套餐',
                    'show' => false,
                    'permission' => 'system',
                    'keepAlive' => false
                ];
                $tmpMenu['component'] = 'propertyPackagesRoomSingleBuyOrder';
                $tmpMenu['path'] = '/property/property/package/packagesRoomSingleBuyOrder';
                $returnArr[] = $tmpMenu;
            }

            if(!$roleJudge || (isset($property_menus) && in_array('property_25',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_packages_buy_order_list';
                $tmpMenu['parentId'] = 'property_20';
                $tmpMenu['id'] = 'property_25';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '已购功能套餐',
                    'show' => true,
                    'permission' => 'system'
                ];
                $tmpMenu['component'] = 'propertyPackageBuyList';
                $tmpMenu['path'] = '/property/property/package/packageBuyList';
                $returnArr[] = $tmpMenu;
            }

            if(!$roleJudge || (isset($property_menus) && in_array('property_26',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_packages_room_buy_order_list';
                $tmpMenu['parentId'] = 'property_20';
                $tmpMenu['id'] = 'property_26';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '已购房间套餐',
                    'show' => true,
                    'permission' => 'system'
                ];
                $tmpMenu['component'] = 'propertyPackageRoomBuyList';
                $tmpMenu['path'] = '/property/property/package/packageRoomBuyList';
                $returnArr[] = $tmpMenu;
            }

        }
		

        //****************数据中心*********************/
        $tmpMenu = [];
        $tmpMenu['name'] = 'property_data_center';
        $tmpMenu['parentId'] = 0;
        $tmpMenu['id'] = 'property_3';
        $tmpMenu['meta'] = [
            'icon' => 'iconshujux',
            'title' => '数据中心',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'RouteView';
        $tmpMenu['redirect'] = '/property/property.iframe/property_village_list';
        $returnArr[] = $tmpMenu;

        $tmpMenu = [];
        $tmpMenu['name'] = 'property_village_list';
        $tmpMenu['parentId'] = 'property_3';
        $tmpMenu['id'] = 'property_3_1';
        $tmpMenu['meta'] = [
            'icon' => '',
            'title' => (cfg('house_name')?:'小区').'列表',
            'show' => true,
            'permission' => 'system',
        ];
        $tmpMenu['component'] = 'System';
        $tmpMenu['path'] = '/property/property.iframe/property_village_list';
        $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewProperty&a=index&iframe=true');
        $returnArr[] = $tmpMenu;

        if(!$roleJudge || (isset($property_menus) && in_array('property_3_2',$property_menus))) {
            $tmpMenu = [];
            $tmpMenu['name'] = 'property_village_owner_info';
            $tmpMenu['parentId'] = 'property_3';
            $tmpMenu['id'] = 'property_3_2';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '业主档案设置',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'System';
            $tmpMenu['path'] = '/property/property.iframe/property_village_owner_info';
            $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewProperty&a=house_village_owner_info&iframe=true');
            $returnArr[] = $tmpMenu;
        }
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();

        if($jz_wsxdsj_net_customized_open && (!$roleJudge || (isset($property_menus) && in_array('property_3_4_1',$property_menus)))) {
            $tmpMenu = [];
            $tmpMenu['name'] = 'warrantyDetails';
            $tmpMenu['parentId'] = 'property_3';
            $tmpMenu['id'] = 'property_3_4_1';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '质保明细',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'warrantyDetails';
            $tmpMenu['path'] = '/property/property/center/warrantyDetails';
            $returnArr[] = $tmpMenu;
        }

        if(!$roleJudge || (isset($property_menus) && in_array('property_3_4',$property_menus))){
            //todo 菜单栏【社区可视化界面】开启关闭广告设置
            $switch=  (new ConfigService())->getOneField( 'house_visualize_switch');
            if(intval($switch) < 1){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_advert_list';
                $tmpMenu['parentId'] = 'property_3';
                $tmpMenu['id'] = 'property_3_4';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '广告设置',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_advert_list';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewProperty&a=house_advert_list&iframe=true');
                $returnArr[] = $tmpMenu;
            }
        }
		
        //****************缴费管理*********************/

        if(!$roleJudge || (isset($property_menus) && in_array('property_4',$property_menus))) {
            $serviceHouseNewPorperty = new HouseNewPorpertyService();
            $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($property_id);
            $tmpMenu = [];
            $tmpMenu['name'] = 'chargeTimeSet';
            $tmpMenu['parentId'] = 0;
            $tmpMenu['id'] = 'property_4';
            $tmpMenu['meta'] = [
                'icon' => 'iconyue1',
                'title' => '缴费管理',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'RouteView';
            $tmpMenu['redirect'] = '/property/property/charge/chargeTimeSet';
            $returnArr[] = $tmpMenu;
        }

        if (empty($takeEffectTimeJudge)){
            $tmpMenu = [];
            $tmpMenu['name'] = 'chargeTimeSet11';
            $tmpMenu['parentId'] = 'property_4';
            $tmpMenu['id'] = 'property_4_1';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '收费设置',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'chargeTimeSet';
            $tmpMenu['path']  = '/property/property/charge/chargeTimeSet';
            $returnArr[] = $tmpMenu;
        }

        if(!$roleJudge || (isset($property_menus) && in_array('property_4_2',$property_menus))) {
            $tmpMenu = [];
            $tmpMenu['name'] = 'budgetList';
            $tmpMenu['parentId'] = 'property_4';
            $tmpMenu['id'] = 'property_4_6';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '预算管理',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'budgetList';
            $tmpMenu['path'] = '/property/property/charge/budgetList';
            $returnArr[] = $tmpMenu;
        }

        if(!$roleJudge || (isset($property_menus) && in_array('property_4_2',$property_menus))) {
            $tmpMenu = [];
            $tmpMenu['name'] = 'budgetCategoryList';
            $tmpMenu['parentId'] = 'property_4';
            $tmpMenu['id'] = 'property_4_7';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '预算分类管理',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'budgetCategoryList';
            $tmpMenu['path'] = '/property/property/charge/budgetCategoryList';
            $returnArr[] = $tmpMenu;
        }

        if(!$roleJudge || (isset($property_menus) && in_array('property_4_2',$property_menus))) {
            $tmpMenu = [];
            $tmpMenu['name'] = 'chargeNumberList';
            $tmpMenu['parentId'] = 'property_4';
            $tmpMenu['id'] = 'property_4_2';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '收费科目管理',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'chargeNumberList';
            $tmpMenu['path'] = '/property/property/charge/chargeNumberList';
            $returnArr[] = $tmpMenu;
        }

        if(!$roleJudge || (isset($property_menus) && in_array('property_4_3',$property_menus))) {
            $tmpMenu = [];
            $tmpMenu['name'] = 'offlinePayList';
            $tmpMenu['parentId'] = 'property_4';
            $tmpMenu['id'] = 'property_4_3';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '线下支付方式管理',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'offlinePayList';
            $tmpMenu['path'] = '/property/property/charge/offlinePayList';
            $returnArr[] = $tmpMenu;
        }
        $isAihorseCustomized = $configCustomizationService->getAihorseCustomizedJudge();
        if(!$roleJudge || (isset($property_menus) && in_array('property_4_4',$property_menus))) {
            $tmpMenu = [];
            $tmpMenu['name'] = 'countFeeList';
            $tmpMenu['parentId'] = 'property_4';
            $tmpMenu['id'] = 'property_4_4';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '费用统计分析',
                'show' => true,
                'permission' => 'system',
            ];
            if ($isAihorseCustomized) {
                $tmpMenu['meta']['title']='收费率';
            }
            $tmpMenu['component'] = 'countFeeList';
            $tmpMenu['path'] = '/property/property/charge/countFeeList';
            $returnArr[] = $tmpMenu;
        }
       
  
            if(!$roleJudge || (isset($property_menus) && in_array('property_4_5',$property_menus))) {
                $tmpMenu = [];
                $tmpMenu['name'] = 'order_ticket_number';
                $tmpMenu['parentId'] = 'property_4';
                $tmpMenu['id'] = 'property_4_5';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '票据编号管理',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'orderTicketNumber';
                $tmpMenu['path'] = '/property/property/charge/orderTicketNumber';
                $returnArr[] = $tmpMenu;
            } 
            
        if ($isAihorseCustomized) {
            if(!$roleJudge || (isset($property_menus) && in_array('property_41_1',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'villages_billing_report';
                $tmpMenu['parentId'] = 'property_4';
                $tmpMenu['id'] = 'property_41_1';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '小区账单报表',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'villageMonthlyBill';
                $tmpMenu['path'] = '/property/property/charge/village_monthly_bill';
                $returnArr[] = $tmpMenu;
            }

            if(!$roleJudge || (isset($property_menus) && in_array('property_41_1',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'villages_room_report';
                $tmpMenu['parentId'] = 'property_4';
                $tmpMenu['id'] = 'property_41_2';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '费用费率统计',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'villageRoomFeeRate';
                $tmpMenu['path'] = '/property/property/charge/villageRoomFeeRate';
                $returnArr[] = $tmpMenu;
            }
        }
        /*
        if(!$roleJudge || (isset($property_menus) && in_array('property_3_3',$property_menus))){
            $tmpMenu = [];
            $tmpMenu['name'] = 'property_bill_all';
            $tmpMenu['parentId'] = 'property_4';
            $tmpMenu['id'] = 'property_3_3';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '账单服务',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'RouteView';
            $tmpMenu['path'] = '/property/property/package/property_bill_list';
            $returnArr[] = $tmpMenu;
        }
        if(!$roleJudge || (isset($property_menus) && in_array('property_33_1',$property_menus))){
            $tmpMenu = [];
            $tmpMenu['name'] = 'property_bill_list';
            $tmpMenu['parentId'] = 'property_3_3';
            $tmpMenu['id'] = 'property_33_1';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '收支流水',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'System';
            $tmpMenu['path'] = '/property/property.iframe/property_bill_list';
            $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=Bill&a=index_list&iframe=true');
            $returnArr[] = $tmpMenu;
        }
        
        if(!$roleJudge || (isset($property_menus) && in_array('property_33_2',$property_menus))){
            $tmpMenu = [];
            $tmpMenu['name'] = 'property_money_list';
            $tmpMenu['parentId'] = 'property_3_3';
            $tmpMenu['id'] = 'property_33_2';
            $tmpMenu['meta'] = [
                'icon' => '',
                'title' => '物业流水',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'System';
            $tmpMenu['path'] = '/property/property.iframe/property_money_list';
            $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=PropertyWater&a=money_list&iframe=true');
            $returnArr[] = $tmpMenu;
        }
        */

        //****************功能应用库*********************/
        if($property_login_role != 4 || (isset($property_menus) && in_array('property_12',$property_menus))){
            $tmpMenu = [];
            $tmpMenu['name'] = 'property_application_index';
            $tmpMenu['parentId'] = 0;
            $tmpMenu['id'] = 'property_12';
            $tmpMenu['meta'] = [
                'icon' => 'iconyingyongziliao',
                'title' => '功能应用库',
                'show' => true,
                'permission' => 'system',
            ];
            $tmpMenu['component'] = 'System';
            $tmpMenu['path'] = '/property/property.iframe/property_application_index';
            $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=PropertyApplication&a=index&iframe=true');
            $returnArr[] = $tmpMenu;
        }

	    if(!$roleJudge || (isset($property_menus) && in_array('property_13',$property_menus))){
		    $tmpMenu = [];
		    $tmpMenu['name'] = 'property_data_center';
		    $tmpMenu['parentId'] = 0;
		    $tmpMenu['id'] = 'property_13';
		    $tmpMenu['meta'] = [
			    'icon' => 'icontongji',
			    'title' => '可视化大数据',
			    'show' => true,
			    'permission' => 'system',
		    ];
		    $tmpMenu['component'] = 'PropertyDataCenter';
		    $tmpMenu['path'] = '/property/property/communityData';
		    $tmpMenu['src'] = '';
		    $returnArr[] = $tmpMenu;
	    }
		
        //****************企业微信*********************/

        $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $where_bind[] = ['bind_type','=',0];
        $where_bind[] = ['bind_id','=',$property_id];
        $wxBind = $dbHouseEnterpriseWxBind->getOne($where_bind,'corpid');


        if(in_array(4,$content_id)) {


            $serviceApplication_list = new ApplicationService();
            $where_bind =[];
            $where_bind[] = ['from','=',1];
            $where_bind[] = ['use_id','=',$property_id];
            $where_bind[] = ['status','=',0];
            $bind_arr = $serviceApplication_list->get_application_id_arr($where_bind);
            if (!in_array(37,$bind_arr)) {
                if(!$roleJudge || (isset($property_menus) && in_array('property_10',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_enterprise_weixin_box';
                    $tmpMenu['parentId'] = 0;
                    $tmpMenu['id'] = 'property_10';
                    $tmpMenu['meta'] = [
                        'icon' => 'iconqiyeweixin',
                        'title' => '企业微信SCRM',
                        'show' => true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'RouteView';
                    $tmpMenu['redirect'] = '/property/property.iframe/property_enterprise_weixin';
                    $returnArr[] = $tmpMenu;
                    $src =  urlencode(cfg('site_url') . '/shequ.php?g=House&c=EnterpriseWeixin&a=index&iframe=true&application_id=37');
                }

                if(!$roleJudge){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_enterprise_weixin';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_1';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '基本配置',
                        'show' => true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.iframe/property_enterprise_weixin';
                    $tmpMenu['src'] = $src;
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_2',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_code_index';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_2';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '渠道活码',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.workWx.channelCode/codeIndex';
                    $tmpMenu['src'] = $src;
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_3',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_add_code';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_3';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '添加渠道码',
                        'show' => false,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.workWx.channelCode/addcode';
                    $tmpMenu['src'] = $src;
                    $returnArr[] = $tmpMenu;
                }


                if(!$roleJudge || (isset($property_menus) && in_array('property_10_4',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_engine_index';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_4';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '话术素材库',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.workWx.contentEngine/engineIndex';
                    $tmpMenu['src'] = $src;
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_5',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_message_list';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_5';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '群发消息',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.workWx.sendMessage/messageList';
                    $tmpMenu['src'] = $src;
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_chat_sidebar';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_7';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '聊天侧边栏',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.workWx.contentEngine/chatSidebar';
                    $tmpMenu['src'] = $src;
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_8',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_data_work_center';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_8';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '数据运营中心',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.workWx.dataCenter/dataCenter';
                    $tmpMenu['src'] = $src;
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_9',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_session_file_index';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_9';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '会话存档',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.workWx.sessionFile/sessionFileIndex';
                    $tmpMenu['src'] = $src;
                    $returnArr[] = $tmpMenu;
                }
            } elseif (empty($wxBind) || !$wxBind['corpid']){
                if(!$roleJudge || (isset($property_menus) && in_array('property_10',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_enterprise_weixin_box';
                    $tmpMenu['parentId'] = 0;
                    $tmpMenu['id'] = 'property_10';
                    $tmpMenu['meta'] = [
                        'icon' => 'iconqiyeweixin',
                        'title' => '企业微信SCRM',
                        'show' => true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'RouteView';
                    $tmpMenu['redirect'] = '/property/property.iframe/property_enterprise_weixin';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_enterprise_weixin';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_1';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '基本配置',
                        'show' => true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.iframe/property_enterprise_weixin';
                    $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=EnterpriseWeixin&a=index&iframe=true');
                    $returnArr[] = $tmpMenu;
                }
            } else {
                if(!$roleJudge || (isset($property_menus) && in_array('property_10',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_enterprise_weixin_box';
                    $tmpMenu['parentId'] = 0;
                    $tmpMenu['id'] = 'property_10';
                    $tmpMenu['meta'] = [
                        'icon' => 'iconqiyeweixin',
                        'title' => '企业微信SCRM',
                        'show' => true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'RouteView';
                    $tmpMenu['redirect'] = '/property/property.iframe/property_enterprise_weixin';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_enterprise_weixin';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_1';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '基本配置',
                        'show' => true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'System';
                    $tmpMenu['path'] = '/property/property.iframe/property_enterprise_weixin';
                    $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=EnterpriseWeixin&a=index&iframe=true');
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_2',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_code_index';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_2';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '渠道活码',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertyCodeIndex';
                    $tmpMenu['path'] = '/property/property.workWx.channelCode/codeIndex';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_3',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_add_code';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_3';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '添加渠道码',
                        'show' => false,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertyAddCode';
                    $tmpMenu['path'] = '/property/property.workWx.channelCode/addcode';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_4',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_engine_index';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_4';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '话术素材库',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertyEngineIndex';
                    $tmpMenu['path'] = '/property/property.workWx.contentEngine/engineIndex';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_5',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_message_list';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_5';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '群发消息',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertyMessageList';
                    $tmpMenu['path'] = '/property/property.workWx.sendMessage/messageList';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_6',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_add_qywx_message';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_6';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '添加消息',
                        'show' => false,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertyAddQywxMessage';
                    $tmpMenu['path'] = '/property/property.workWx.sendMessage/addQywxMessage';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_chat_sidebar';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_7';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '聊天侧边栏',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertyChatSidebar';
                    $tmpMenu['path'] = '/property/property.workWx.contentEngine/chatSidebar';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_8',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_data_work_center';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_8';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '数据运营中心',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertyDataCenter';
                    $tmpMenu['path'] = '/property/property.workWx.dataCenter/dataCenter';
                    $returnArr[] = $tmpMenu;
                }

                if(!$roleJudge || (isset($property_menus) && in_array('property_10_9',$property_menus))){
                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_session_file';
                    $tmpMenu['parentId'] = 'property_10';
                    $tmpMenu['id'] = 'property_10_9';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '会话存档',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'RouteView';
                    $tmpMenu['redirect'] = '/property/property.workWx.sessionFile/sessionFileSet';
                    $returnArr[] = $tmpMenu;

                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_session_file_base';
                    $tmpMenu['parentId'] = 'property_10_9';
                    $tmpMenu['id'] = 'property_10_91';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '基本配置',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertySessionFileSet';
                    $tmpMenu['path'] = '/property/property.workWx.sessionFile/sessionFileSet';
                    $returnArr[] = $tmpMenu;

                    $tmpMenu = [];
                    $tmpMenu['name'] = 'property_session_file_index';
                    $tmpMenu['parentId'] = 'property_10_9';
                    $tmpMenu['id'] = 'property_10_92';
                    $tmpMenu['meta'] = [
                        'icon' => '',
                        'title' => '会话存档',
                        'show' => true,
                        'keepAlive'=> true,
                        'permission' => 'system',
                    ];
                    $tmpMenu['component'] = 'propertySessionFileIndex';
                    $tmpMenu['path'] = '/property/property.workWx.sessionFile/sessionFileIndex';
                    $returnArr[] = $tmpMenu;
                }
            }
        }
        //****************公众号*********************/
        if(in_array(2,$content_id)) {
            if(!$roleJudge || (isset($property_menus) && in_array('property_8',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_weixin_box';
                $tmpMenu['parentId'] = 0;
                $tmpMenu['id'] = 'property_8';
                $tmpMenu['meta'] = [
                    'icon' => 'iconGroup',
                    'title' => '微信公众号',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'RouteView';
                $tmpMenu['redirect'] = '/property/property.iframe/property_weixin_index';
                $returnArr[] = $tmpMenu;
            }
            if(!$roleJudge || (isset($property_menus) && in_array('property_81',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_weixin_index';
                $tmpMenu['parentId'] = 'property_8';
                $tmpMenu['id'] = 'property_81';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '公众号绑定',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_weixin_index';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewWeixin&a=index&iframe=true');
                $returnArr[] = $tmpMenu;
            }
            if(!$roleJudge || (isset($property_menus) && in_array('property_82',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_weixin_template';
                $tmpMenu['parentId'] = 'property_8';
                $tmpMenu['id'] = 'property_82';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '模板消息',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_weixin_template';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewWeixin&a=template&iframe=true');
                $returnArr[] = $tmpMenu;
            }
            if(!$roleJudge || (isset($property_menus) && in_array('property_83',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_weixin_auto';
                $tmpMenu['parentId'] = 'property_8';
                $tmpMenu['id'] = 'property_83';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '自动回复',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_weixin_auto';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewWeixin&a=auto&iframe=true');
                $returnArr[] = $tmpMenu;
            }
            if(!$roleJudge || (isset($property_menus) && in_array('property_83',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_weixin_menu';
                $tmpMenu['parentId'] = 'property_8';
                $tmpMenu['id'] = 'property_83';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '自定义菜单',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_weixin_menu';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewWeixin&a=menu&iframe=true');
                $returnArr[] = $tmpMenu;
            }
            if(!$roleJudge || (isset($property_menus) && in_array('property_84',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_weixin_txt';
                $tmpMenu['parentId'] = 'property_8';
                $tmpMenu['id'] = 'property_84';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '关键词回复',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_weixin_txt';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewWeixin&a=txt&iframe=true');
                $returnArr[] = $tmpMenu;
            }
            if(!$roleJudge || (isset($property_menus) && in_array('property_84',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_weixin_article';
                $tmpMenu['parentId'] = 'property_8';
                $tmpMenu['id'] = 'property_84';
                $tmpMenu['meta'] = [
                    'icon' => '',
                    'title' => '图文素材',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_weixin_article';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewWeixin&a=article&iframe=true');
                $returnArr[] = $tmpMenu;
            }

        }
        //****************小程序管理*********************/
        if(in_array(3,$content_id)) {
            if(!$roleJudge || (isset($property_menus) && in_array('property_9',$property_menus))){
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_weixinapp_index';
                $tmpMenu['parentId'] = 0;
                $tmpMenu['id'] = 'property_9';
                $tmpMenu['meta'] = [
                    'icon' => 'iconxiaochengxu',
                    'title' => '微信小程序',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_weixinapp_index';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=NewWeixinApp&a=index&iframe=true');
                $returnArr[] = $tmpMenu;
		
		
	        $tmpMenu = [];
	        $tmpMenu['name'] = 'property_pay_config_index';
	        $tmpMenu['parentId'] = 0;
	        $tmpMenu['id'] = 'property_100';
	        $tmpMenu['meta'] = [
	            'icon' => 'iconzhifu',
	            'title' => '支付配置',
	            'show' => true,
	            'permission' => 'system',
	        ];
	        $tmpMenu['component'] = 'PayPlatformConfig';
	        $tmpMenu['path'] = '/property/property/pay/config';
	        $returnArr[] = $tmpMenu;
	        $tmpMenu = [];
	        $tmpMenu['name'] = 'property_pay_type_index';
	        $tmpMenu['parentId'] = 0;
	        $tmpMenu['id'] = 'property_101';
	        $tmpMenu['meta'] = [
	            'icon' => 'iconxiaochengxu',
	            'title' => '单个支付方式配置首页',
	            'show' => false,
	            'permission' => 'system',
	        ];
	        $tmpMenu['component'] = 'PayPlatformPayTypeIndex';
	        $tmpMenu['path'] = '/property/property/pay/paytype';
	        $returnArr[] = $tmpMenu;
            }
        }

        //****************商家管理*********************/
        if(!$roleJudge || (isset($property_menus) && in_array('property_7',$property_menus))){
            if(in_array(1,$content_id)) {
                $tmpMenu = [];
                $tmpMenu['name'] = 'property_merchant_index';
                $tmpMenu['parentId'] = 0;
                $tmpMenu['id'] = 'property_7';
                $tmpMenu['meta'] = [
                    'icon' => 'iconshangjia',
                    'title' => '商家管理',
                    'show' => true,
                    'permission' => 'system',
                ];
                $tmpMenu['component'] = 'System';
                $tmpMenu['path'] = '/property/property.iframe/property_merchant_index';
                $tmpMenu['src'] = urlencode(cfg('site_url') . '/shequ.php?g=House&c=Merchant&a=index&iframe=true');
                $returnArr[] = $tmpMenu;
            }
        }

	    //****************安全中心*********************/
	    /**
	    if(!$roleJudge || (isset($property_menus) && in_array('property_5',$property_menus))) {
		    $tmpMenu = [];
		    $tmpMenu['name'] = 'securityCenter';
		    $tmpMenu['parentId'] = 0;
		    $tmpMenu['id'] = 'property_5';
		    $tmpMenu['meta'] = [
			    'icon' => 'iconguanjiancikuozhan',
			    'title' => '安全中心',
			    'show' => true,
			    'permission' => 'system',
		    ];
		    $tmpMenu['component'] = 'RouteView';
		    $tmpMenu['redirect'] = '/property/property/security/loginLog';
		    $returnArr[] = $tmpMenu;
	    }
	    if(!$roleJudge || (isset($property_menus) && in_array('property_5',$property_menus))) {
		    $tmpMenu = [];
		    $tmpMenu['name'] = 'securityCenter';
		    $tmpMenu['parentId'] = 'property_5';
		    $tmpMenu['id'] = 'property_5_1';
		    $tmpMenu['meta'] = [
			    'icon' => '',
			    'title' => '系统日志',
			    'show' => true,
			    'permission' => 'system',
		    ];
		    $tmpMenu['component'] = 'propertyCommonLog';
		    $tmpMenu['path'] = '/property/property/security/propertyCommonLog';
		    $returnArr[] = $tmpMenu;
	    }
	    if(!$roleJudge || (isset($property_menus) && in_array('property_5',$property_menus))) {
		    $tmpMenu = [];
		    $tmpMenu['name'] = 'securityCenterLogin';
		    $tmpMenu['parentId'] = 'property_5';
		    $tmpMenu['id'] = 'property_5_2';
		    $tmpMenu['meta'] = [
			    'icon' => '',
			    'title' => '登录日志',
			    'show' => true,
			    'permission' => 'system',
		    ];
		    $tmpMenu['component'] = 'propertyLoginLog';
		    $tmpMenu['path'] = '/property/property/security/propertyLoginLog';
		    $returnArr[] = $tmpMenu;
	    }
	     * */
	   
        return $returnArr;
    }


}