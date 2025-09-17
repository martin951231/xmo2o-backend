<?php
/**
 * 工作台
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/28 9:23
 */

namespace app\community\controller\manage_api\v1;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\ApplicationService;
use app\community\model\service\ConfigService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\PackageOrderService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\HouseNewPorpertyService;

class WorkBenchController extends BaseController{

    /**
     * 工作台数据
     * @param 传参
     * array (
     *  'village_id'=> '小区id,必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/28 11:36
     * @return \json
     */
    public function index() {
    // 获取登录信息
    $arr = $this->getLoginInfo(true);
    if (isset($arr['status']) && $arr['status']!=1000) {
        return api_output($arr['status'],[],$arr['msg']);
    }
    $service_login = new ManageAppLoginService();
    //$base_url = $service_login->base_url;
    $base_url ='/packapp/community/';
    $site_url = cfg('site_url');
    $village_id = $this->login_info['village_id'];
    if (empty($village_id)) {
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            return api_output(1001,[],'缺少对应小区！');
        }
    }
    $service_application = new ApplicationService();
    $where = [];
    $where[] = ['from','=',0];
    $where[] = ['status','=',0];
//        $where[] = ['use_id','=',$village_id];
    $application_arr = $service_application->get_application_id_arr($where);

    $servicePackageOrder = new PackageOrderService();
    $dataPackage = $servicePackageOrder->getPropertyOrderPackage('',$village_id);
    if ($dataPackage) {
        $dataPackage = $dataPackage->toArray();
        $package_content = $dataPackage['content'];
    } else {
        $package_content = [];
    }

    // 工作台
    $work_bench = [];
    $village_manage = [];
    $village_manage['title'] = ((new HouseVillageService())->getHouseAlias('house_name',$village_id)?:'小区').'管理';
    $village_manage['child'] = [];

    $tip = '开发工程师正在加班奔跑中...';

    $static_resources = static_resources(true);
    if (!$this->config['site_url']) {
        // 初始化 配置 业务层
        $service_config = new ConfigService();
        $where_config = [];
        $where_config[] = ['name', '=', "site_url"];
        $config = $service_config->get_config_list($where_config);
        $this->config['site_url'] = $config['site_url'];
    }
    $site_url = $this->config['site_url'];
    if (!$this->auth || (in_array(37,$this->auth)||in_array(111185,$this->auth))) {
        $village_manage['child'][] = [
            'title' => ((new HouseVillageService())->getHouseAlias('room_name',$village_id)?:'房间').'管理',
            'tip' => $tip, // url 没有值时候的提示
            'url' => $site_url.$base_url.'pages/Community/Workbench/selectRooms',
            'icon' => $site_url . $static_resources . 'images/room_manage.png',
            'type'=>'room_manage'
        ];
    }
    if (!$this->auth || in_array(45,$this->auth)|| in_array(112024,$this->auth)) {
        $village_manage['child'][] = [
            'title' => '车位管理',
            'tip' => $tip, // url 没有值时候的提示
            'url' => $site_url.$base_url.'pages/Community/carManagement/carManagement',
            'icon' => $site_url . $static_resources . 'images/parking_management.png',
            'type'=>'car_position_manage'
        ];
    }
    if (!$this->auth || in_array(55,$this->auth) || in_array(112025,$this->auth)) {
        $village_manage['child'][] = [
            'title' => '车辆管理',
            'tip' => $tip, // url 没有值时候的提示
            'url' => $site_url.$base_url.'pages/Community/carManagement/vehicleManagement',
            'icon' => $site_url . $static_resources . 'images/vehicle_management.png',
            'type'=>'car_manage'
        ];
    }

    //套餐过滤 2020/11/9
    if (isset($arr['user']) && isset($arr['user']['property_id']) && $arr['user']['property_id']) {
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($arr['user']['property_id']);
    } else {
        $takeEffectTimeJudge = false;
    }
        $configCustomizationService=new ConfigCustomizationService();
        $jz_wsxdsj_customized_open = $configCustomizationService->getWSXCustomizedOpen();
        $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
    if(in_array(5,$package_content)) {
        if ((!$this->auth && $this->login_role!=5) || in_array(85, $this->auth)) {
            if ($takeEffectTimeJudge) {
                $village_manage['child'][] = [
                    'title' => '应收账单',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url.$base_url.'pages/Community/NewCollectMoney/AccountsReceivable',
                    'icon' => $site_url . $static_resources . 'images/icon_yingshou.png',
                    'type'=>'accounts_receivable_new'
                ];
            } else {
                $village_manage['child'][] = [
                    'title' => '未缴账单',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url.$base_url.'pages/Community/unpaidBills/unpaidBillsList',
                    'icon' => $site_url . $static_resources . 'images/icon_yingshou.png',
                    'type'=>'no_pay_bill'
                ];
            }
            if ($jz_wsxdsj_customized_open || $is_aihorse_customized) {
                $village_manage['child'][] = [
                    'title' => '历史欠费账单',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url.$base_url.'pages/Community/NewCollectMoney/Historypaid',
                    'icon' => $site_url . $static_resources . 'images/icon_qianfei.png',
                    'type'=>'hang_no_pay_bill'
                ];
            }
        }else if($takeEffectTimeJudge && (!$this->auth || in_array(667, $this->auth))){
            $village_manage['child'][] = [
                'title' => '应收账单',
                'tip' => $tip, // url 没有值时候的提示
                'url' => $site_url.$base_url.'pages/Community/NewCollectMoney/AccountsReceivable',
                'icon' => $site_url . $static_resources . 'images/icon_yingshou.png',
                'type'=>'accounts_receivable_new'
            ];
            if ($jz_wsxdsj_customized_open || $is_aihorse_customized) {
                $village_manage['child'][] = [
                    'title' => '历史欠费账单',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url.$base_url.'pages/Community/NewCollectMoney/Historypaid',
                    'icon' => $site_url . $static_resources . 'images/icon_qianfei.png',
                    'type'=>'hang_no_pay_bill'
                ];
            }
        }
        if($takeEffectTimeJudge && (!$this->auth || in_array(111152, $this->auth))){
            $village_manage['child'][] = [
                'title' => '已缴账单',
                'tip' => $tip, // url 没有值时候的提示
                'url' => $site_url.$base_url.'pages/Community/NewCollectMoney/billPaid',
                'icon' => $site_url . $static_resources . 'images/cashier_unpaid.png',
                'type'=>'accounts_billpaid_new'
            ];
        }
        if ((!$this->auth && $this->login_role!=5) || in_array(85, $this->auth)) {
            if (!$takeEffectTimeJudge) {
                $village_manage['child'][] = [
                    'title' => '收银台账单',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url.$base_url.'pages/Community/unpaidBills/CollectMoneyBills',
                    'icon' => $site_url . $static_resources . 'images/cashier.png',
                    'type'=>'cashier_bill'
                ];
            }
        }
        if ((!$this->auth && $this->login_role!=5) || in_array(1113003, $this->auth)) {
            $village_manage['child'][] = [
                'title' => '停暖管理',
                'tip' => $tip, // url 没有值时候的提示
                'url' => $site_url.$base_url.'pages/Community/stopFeating/index',
                'icon' => $site_url . $static_resources . 'images/tn.png',
                'type'=>'close_heating'
            ];
        }
        if ((!$this->auth && $this->login_role!=5) || in_array(1113004, $this->auth)) {
            $village_manage['child'][] = [
                'title' => '流量计费管理',
                'tip' => $tip, // url 没有值时候的提示
                'url' => $site_url.$base_url.'pages/Community/stopFeating/pages/trafficBilling',
                'icon' => $site_url . $static_resources . 'images/lljf.png',
                'type'=>'flow_charging'
            ];
        }
        
    }

    if (!$this->auth || in_array(85,$this->auth)) {
        $village_manage['child'][] = [
            'title' => ((new HouseVillageService())->getHouseAlias('house_name',$village_id)?:'小区').'公告',
            'tip' => $tip, // url 没有值时候的提示
            'url' => $site_url.$base_url.'pages/Community/villageNew/newindex',
            'icon' => $site_url . $static_resources . 'images/news.png',
            'type'=>'village_notice'
        ];
    }
    if ($village_manage['child']) {
        $work_bench[] = $village_manage;
    }

    $property_manage = [];
    $property_manage['title'] = '物业服务';
    $property_manage['child'] = [];
    if ($application_arr && in_array(28,$application_arr)) {
        if (!$this->auth || in_array(216,$this->auth)) {
            $property_manage['child'][] = [
                'title' => '访客登记',
                'tip' => $tip, // url 没有值时候的提示
                'url' => $site_url . $base_url . 'pages/Community/visitorRegistration/visitorRegistration',
                'icon' => $site_url . $static_resources . 'images/visitor_list.png',
                'type' => 'village_manage'
            ];
        }
    }

    //套餐过滤
    if(in_array(15,$package_content)) {
        if ($application_arr && in_array(14, $application_arr)) {
            if (!$this->auth || in_array(207,$this->auth)) {
                $property_manage['child'][] = [
                    'title' => '快递代收',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url . $base_url . 'pages/Community/expressManagement/expressManagement',
                    'icon' => $site_url . $static_resources . 'images/express_service.png',
                    'type' => 'express_collection'
                ];
            }
        }
    }
    //套餐过滤
    if(in_array(16,$package_content)) {
        if ($application_arr && in_array(16, $application_arr)) {
            if (!$this->auth || in_array(219,$this->auth)) {
                $property_manage['child'][] = [
                    'title' => '在线报修',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url . $base_url . 'pages/Community/onlineReport/onlineReport?genre=1',
                    'icon' => $site_url . $static_resources . 'images/repair.png',
                    'type' => 'online_repair'
                ];
            }
        }
    }
    //套餐过滤
    if(in_array(19,$package_content)) {
        if ($application_arr && in_array(19, $application_arr)) {
            if (!$this->auth || in_array(224,$this->auth)) {
                $property_manage['child'][] = [
                    'title' => '投诉建议',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url . $base_url . 'pages/Community/onlineReport/onlineReport?genre=3',
                    'icon' => $site_url . $static_resources . 'images/village_suggest.png',
                    'type' => 'suggestion'
                ];
            }
        }
    }
    //套餐过滤
    if(in_array(18,$package_content)) {
        if ($application_arr && in_array(18, $application_arr)) {
            if (!$this->auth|| in_array(222,$this->auth)) {
                $property_manage['child'][] = [
                    'title' => '水电煤上报',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url . $base_url . 'pages/Community/onlineReport/onlineReport?genre=2',
                    'icon' => $site_url . $static_resources . 'images/water.png',
                    'type' => 'water_online'
                ];
            }
        }
    }
    $property_manage['child'][] = [
        'title' => '工单处理中心',
        'tip' => $tip, // url 没有值时候的提示
        'url' => $site_url . $base_url . 'pages/CommunityPages/workOrder/eventList',
        'icon' => $site_url . $static_resources . 'images/repair.png',
        'type' => 'repair_order'
    ];
    $agent = $this->request->agent;
    if(!in_array($agent,['iosapp','androidapp']) && in_array(35,$package_content)) {
        $property_manage['child'][] = [
            'title' => '四川人口人脸审核',
            'tip' => $tip, // url 没有值时候的提示
            'url' => 'https://mianyangpt.91-ec.com:19151/wechat/views/gatekeeper-mobile/index.html#!/login',
            'icon' => $site_url . $static_resources . 'images/visitor_list.png',
            'type' => 'thirdsichuan_check'
        ];
    }
    //套餐过滤

    /*if(in_array(14,$package_content)) {
        if ($application_arr && in_array(9, $application_arr)) {
            $property_manage['child'][] = [
                'title' => '移动抄表',
                'tip' => $tip, // url 没有值时候的提示
                'url' => '',
                'icon' => $site_url . $static_resources . 'images/meter_read.png'
            ];
        }
    }
    //套餐过滤
    if(in_array(20,$package_content)) {
        if ($application_arr && in_array(23, $application_arr)) {
            $property_manage['child'][] = [
                'title' => '智慧二维码',
                'tip' => $tip, // url 没有值时候的提示
                'url' => '',
                'icon' => $site_url . $static_resources . 'images/wisdom_qrcode.png'
            ];
        }
    }*/
//        if ($application_arr && in_array(9,$application_arr)) {
//            $property_manage['child'][] = [
//                'title' => '移动抄表',
//                'tip' => $tip, // url 没有值时候的提示
//                'url' => '',
//                'icon' => $site_url . $static_resources . 'images/meter_read.png',
//                'type'=>'meter_record'
//            ];
//        }
//        if ($application_arr && in_array(23,$application_arr)) {
//            $property_manage['child'][] = [
//                'title' => '智慧二维码',
//                'tip' => $tip, // url 没有值时候的提示
//                'url' => '',
//                'icon' => $site_url . $static_resources . 'images/wisdom_qrcode.png',
//                'type'=>'smart_qr_code'
//            ];
//        }
    if ($property_manage['child']) {
        $work_bench[] = $property_manage;
    }

    $pay_manage = [];
    $pay_manage['title'] = ((new HouseVillageService())->getHouseAlias('house_name',$village_id)?:'小区').'缴费';
    $pay_manage['child'] = [];

    //套餐过滤
    if(in_array(5,$package_content)) {
        if ($application_arr && in_array(10, $application_arr)) {
            $pay_manage['child'][] = [
                'title' => '预存款管理',
                'tip' => $tip, // url 没有值时候的提示
                'url' => '',
                'icon' => $site_url . $static_resources . 'images/deposit.png',
                'type'=>'advance_deposit_manage'
            ];
        }
        if ($application_arr && in_array(12, $application_arr)) {
            if (!$this->auth || in_array(41,$this->auth)) {
                $pay_manage['child'][] = [
                    'title' => '押金管理',
                    'tip' => $tip, // url 没有值时候的提示
                    'url' => $site_url . $base_url . 'pages/Community/depositManagement/depositManagement',
                    'icon' => $site_url . $static_resources . 'images/deposit_management.png',
                    'type' => 'deposit_manage'
                ];
            }
        }
    }
    $lakala_pay_switch=cfg('lakala_pay_switch');
    $lakala_pay_switch = $lakala_pay_switch ? intval($lakala_pay_switch):0;
    if($lakala_pay_switch==1 && $village_id>0){
        $lakalaQianzhangtongArchivesService=  new   \app\common\model\service\LakalaQianzhangtongArchivesService();
        $whereArr=array(['item_type','=','village'],['item_id','=',$village_id]);
        $outMemberNo = 'village' . $village_id;
        $apply=$lakalaQianzhangtongArchivesService->getOneData($whereArr);
        $jump_url='';
        if (empty($apply) || empty($apply['member_no'])) {
            //没有入网过
            if(empty($apply)){
                $data = [
                    'item_type' => 'village',
                    'item_id' => $village_id,
                    'out_member_no' => $outMemberNo
                ];
                $lakalaQianzhangtongArchivesService->addOneData($data);
            }
            try {
                $lakalaService=  new   \app\pay\model\service\channel\LakalaService();
                $result = $lakalaService->register($outMemberNo);
                fdump_api(array('outMemberNo'=>$outMemberNo,'result'=>$result),'00WorkBench',1);
                if($result && isset($result['url'])){
                    $jump_url=$result['url'];
                }
            } catch (\Exception $e) {
                fdump_api(array('e_msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()),'00WorkBench',1);
            }
        } else {
            //有入网过
            $jump_url=$site_url.'/wap.php?c=Qianzhangtong&a=register&id=' . $apply['id'] . '&token=' . md5($outMemberNo);
        }
        if($jump_url){
            $pay_manage['child'][] = [
                'title' => '钱账通入网',
                'tip' => $tip, // url 没有值时候的提示
                'url' => $jump_url,
                'icon' => $site_url . $static_resources . 'images/lakala_qzt_pay.png',
                'type' => 'lakala_qzt_pay'
            ];
        }
    }
    if ($pay_manage['child']) {
        $work_bench[] = $pay_manage;
    }


    $intelligence_manage = [];
    $intelligence_manage['title'] = '智能硬件';
    $intelligence_manage['child'] = [];

    //套餐过滤
    $hardware_intersect = array_intersect([6,7,8,9,10,11],$package_content);
    if(cfg('manage_resident_faces_switch')!=1 && $hardware_intersect && count($hardware_intersect)>0) {
        if (!$this->auth || in_array(226, $this->auth)) {
            //套餐过滤
            if(in_array(6,$package_content)) {
                if (!$this->auth || in_array(226,$this->auth)) {
                    $intelligence_manage['child'][] = [
                        'title' => '蓝牙门禁',
                        'tip' => $tip, // url 没有值时候的提示
                        'url' => $site_url . $base_url . 'pages/Community/Workbench/accessControl',
                        'icon' => $site_url . $static_resources . 'images/ly_door.png',
                        'type' => 'blue_tooth_door'
                    ];
                }
            }
            //套餐过滤
            if(in_array(7,$package_content)) {
                if (!$this->auth || in_array(285,$this->auth)) {
                    $intelligence_manage['child'][] = [
                        'title' => '人脸门禁',
                        'tip' => $tip, // url 没有值时候的提示
                        'url' => $site_url . $base_url . 'pages/Community/faceAccessControl/faceAccessControl',
                        'icon' => $site_url . $static_resources . 'images/ly_door.png',
                        'type' => 'face_door'
                    ];
                }
            }
        }
    }
    if ($intelligence_manage['child']) {
        $work_bench[] = $intelligence_manage;
    }

    return api_output(0,$work_bench);
}

    public function getRoleOptPermission(){
        $arr = $this->getLoginInfo(true);
        $village_id = $this->login_info['village_id'];
        $permission_type = $this->request->post('permission_type','','trim');
        $permissionInfo=array('permission_type'=>$permission_type,'village_id'=>$village_id);
        //$permissionInfo['permission_auth']=$this->auth;
        $permissionInfo['login_role']=$this->login_role;
        $permissionInfo['log_uid']=$this->_uid;
        if (!empty($village_id)) {
            switch ($permission_type){
                case 'parking_car':
                    $permissionInfo['role_add_car']=0;
                    $permissionInfo['role_edit_car']=0;
                    $permissionInfo['role_del_car']=0;
                    if (empty($this->auth) || in_array(112129,$this->auth)) {
                        $permissionInfo['role_add_car']=1;
                    }
                    if (empty($this->auth)|| in_array(112132,$this->auth)) {
                        $permissionInfo['role_edit_car']=1;
                    }
                    if (empty($this->auth) || in_array(112133,$this->auth)) {
                        $permissionInfo['role_del_car']=1;
                    }
                    break;
                case 'parking_position':
                    $permissionInfo['role_add_position']=0;
                    $permissionInfo['role_edit_position']=0;
                    $permissionInfo['role_del_position']=0;
                    if (empty($this->auth) || in_array(112124,$this->auth)) {
                        $permissionInfo['role_add_position']=1;
                    }
                    if (empty($this->auth)|| in_array(112127,$this->auth)) {
                        $permissionInfo['role_edit_position']=1;
                    }
                    if (empty($this->auth) || in_array(112128,$this->auth)) {
                        $permissionInfo['role_del_position']=1;
                    }
                    break;
                case 'parking_garage':
                    $permissionInfo['role_add_garage']=0;
                    $permissionInfo['role_edit_garage']=0;
                    $permissionInfo['role_del_garage']=0;
                    if (empty($this->auth) || in_array(112118,$this->auth)) {
                        $permissionInfo['role_add_garage']=1;
                    }
                    if (empty($this->auth)|| in_array(112121,$this->auth)) {
                        $permissionInfo['role_edit_garage']=1;
                    }
                    if (empty($this->auth) || in_array(112123,$this->auth)) {
                        $permissionInfo['role_del_garage']=1;
                    }
                    break;
                case 'no_pay_order':
                    $permissionInfo['role_mdy_money']=0;
                    $permissionInfo['role_discard']=0;
                    $permissionInfo['role_hang_order']=0;
                    $permissionInfo['role_call_pay']=0;
                    $permissionInfo['role_payment_opt']=0;
                    if (empty($this->auth) || in_array(112083,$this->auth)) {
                        $permissionInfo['role_mdy_money']=1;
                    }
                    if (empty($this->auth)|| in_array(112099,$this->auth)) {
                        $permissionInfo['role_discard']=1;
                    }
                    if (empty($this->auth) || in_array(1113038,$this->auth)) {
                        $permissionInfo['role_hang_order']=1;
                    }
                    if (empty($this->auth) || in_array(112098,$this->auth)) {
                        $permissionInfo['role_call_pay']=1;
                    }
                    if (empty($this->auth) || in_array(112082,$this->auth)) {
                        $permissionInfo['role_payment_opt']=1;
                    }
                    break;
                case 'cashier_order':
                    $permissionInfo['role_mdy_money']=0;
                    $permissionInfo['role_discard']=0;
                    $permissionInfo['role_hang_order']=0;
                    $permissionInfo['role_call_pay']=0;
                    $permissionInfo['role_payment_opt']=0;
                    if (empty($this->auth) || in_array(112083,$this->auth)) {
                        $permissionInfo['role_mdy_money']=1;
                    }
                    if (empty($this->auth)|| in_array(112084,$this->auth)) {
                        $permissionInfo['role_discard']=1;
                    }
                    if (empty($this->auth) || in_array(1113038,$this->auth)) {
                        $permissionInfo['role_hang_order']=1;
                    }
                    if (empty($this->auth) || in_array(112098,$this->auth)) {
                        $permissionInfo['role_call_pay']=1;
                    }
                    if (empty($this->auth) || in_array(112082,$this->auth)) {
                        $permissionInfo['role_payment_opt']=1;
                    }
                case 'no_pay_hang_order':
                    $permissionInfo['role_mdy_money']=0;
                    $permissionInfo['role_payment_opt']=0;
                    if (empty($this->auth) || in_array(112083,$this->auth)) {
                        $permissionInfo['role_mdy_money']=1;
                    }
                    if (empty($this->auth) || in_array(112082,$this->auth)) {
                        $permissionInfo['role_payment_opt']=1;
                    }
                    break;
            }
        }
        return api_output(0,$permissionInfo);
    }
    
}