<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 13:16
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\db\HouseAdmin;
use app\community\model\db\HouseVillageCheckauthDetail;
use app\community\model\db\HouseVillagePrintTemplate;
use app\community\model\db\HouseWorker;
use app\community\model\db\ParkPassage;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HousePropertyVillageWorkerService;
use app\community\model\service\OrganizationStreetService;
use app\community\model\service\PropertyAdminService;
use think\facade\Request;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\PackageOrderService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageCheckauthSetService;
use app\community\model\service\HouseVillageVisitorService;
use token\Token;

class IndexController extends BaseController{

    /**
     * 社区管理APP首页
     * @param 传参
     * array (
     *  'village_id'=> '如果选择后必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/4/25 17:34
     * @return \json
     */
    public function index() {
        // 初始化业务层
        $service_login = new ManageAppLoginService();
        $service_house_village = new HouseVillageService();
        $dbParkPassage = new ParkPassage();          //智慧停车场设备
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $site_url = cfg('site_url');
        $static_resources = static_resources(true);
        $village_id  = $this->login_info['village_id'];
        if (empty($village_id)) {
            $village_id = $this->request->param('village_id','0','intval');
        }
        if (empty($village_id)) {
            return api_output(1002,[],'登录信息错误，请重新登录');
        }

        //过滤套餐 2020/11/9 start
        $servicePackageOrder = new PackageOrderService();
        $data = $servicePackageOrder->getPropertyOrderPackage('',$village_id);
        if ($data) {
            $data = $data->toArray();
            $package_content = $data['content'];
        } else {
            $package_content = [];
        }
        //过滤套餐 2020/11/9 end

        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'village_name');
        if ($this->login_info['login_name']) {
            $login_name = '，'.$this->login_info['login_name'];
        } else {
            $login_name = '';
        }
        $welcome_tip = $service_login->time_tip(time(),$login_name,$village_info['village_name']);
        $arr = [
            'welcome_tip' => $welcome_tip
        ];

        // 获取客服联系路径
        $kf_phone = cfg('site_phone');
        $arr['kf_phone'] = trim($kf_phone) ? trim($kf_phone): '';


        if (5==$this->login_role || $village_id) {
            // 登录身份为 小区工作人员
            $village_count_block = [];
            // 房间数
            $where = [
                ['village_id', '=', $village_id],
                ['is_del', '=', 0 ]
            ];
            $room_count = $service_house_village->getVillageRoomNum($where);
            $room_block = [
                'title' => ((new HouseVillageService())->getHouseAlias('room_name',$village_id)?:'房间'),
                'count' => $room_count,
                'url' => ''
            ];
            $village_count_block[] = $room_block;

            // 业主数
            $where_owner = [
                ['village_id', '=', $village_id],
                ['vacancy_id','>',0],
                ['status','=',1],
                ['type','in',array(0,3)],
                ['name|phone|uid','<>','']
            ];
            $owner_count = $service_house_village->getVillageUserNum($where_owner);
            $owner_block = [
                'title' => '业主',
                'count' => $owner_count,
                'url' => ''
            ];
            $village_count_block[] = $owner_block;

            // 家属数
            $where_family = [
                ['village_id', '=', $village_id],
                ['status','=',1],
                ['type','=',1]
            ];
            $family_count = $service_house_village->getVillageUserNum($where_family);
            $family_block = [
                'title' => '家属',
                'count' => $family_count,
                'url' => ''
            ];
            $village_count_block[] = $family_block;

            // 租客数
            $where_tenant = [
                ['village_id', '=', $village_id],
                ['status','=',1],
                ['type','=',2]
            ];
            $tenant_count = $service_house_village->getVillageUserNum($where_tenant);
            $tenant_block = [
                'title' => '租客',
                'count' => $tenant_count,
                'url' => ''
            ];
            $village_count_block[] = $tenant_block;

            // 车辆数
            $where_car = [
                ['village_id', '=', $village_id]
            ];
            $car_count = $service_house_village->get_village_car_num($where_car);
            $car_block = [
                'title' => '车辆',
                'count' => $car_count,
                'url' => ''
            ];
            $village_count_block[] = $car_block;

            // 车位数
//            $where_park = [
//                ['village_id', '=', $village_id]
//            ];
            $where_park[] =['village_id', '=', $village_id];
            $parkDeviceWheres[] = ['village_id','=',$village_id];
            $parkCountStatus = $dbParkPassage->getCount($parkDeviceWheres);
            if($parkCountStatus){
                $where_park[] = ['position_pattern', '=', 1];//固定车位
            }
            $park_count = $service_house_village->get_village_park_position_num($where_park);
            $park_block = [
                'title' => '车位',
                'count' => $park_count,
                'url' => ''
            ];
            $village_count_block[] = $park_block;

            $arr['village_count_block'] = $village_count_block;

            // 待办事项
            $todo_info = [];
            $tip = '正在开发中';
            $base_url = '/packapp/community/';
            if (empty($this->auth) || in_array(101,$this->auth)) {
                // 待审核业主
                $where_audit_user = [
                    ['type', 'in', '0,3'],
                    ['status', '=', '2'],
                    ['village_id', '=', $village_id],
                ];
                $where_audit_user[] = ['uid|phone|name', 'not in', [0,'']];
                $audit_user_count = $service_house_village->getVillageUserNum($where_audit_user);
                if($audit_user_count && $audit_user_count>0) {
                    $block_audit_user = [
                        'title' => '待审核业主',
                        'tip' => $tip,
                        'url' => $site_url.$base_url.'pages/Community/index/auditManagement?index_key=0&type=1',
                        'count' => $audit_user_count,
                        'icon_img' => $site_url . $static_resources . 'images/property_app/'.'owner_to_be_reviewed.png',
                        'type'=>'owner_to_be_reviewed'
                    ];
                    $todo_info[] = $block_audit_user;
                }
            }
            if (empty($this->auth) || in_array(103,$this->auth)) {
                // 待审核家属
                $where_child_count = [
                    ['type', '=', '1'],
                    ['status', '=', '2'],
                    ['village_id', '=', $village_id],
                ];
                $audit_child_count = $service_house_village->getVillageUserNum($where_child_count);
                if($audit_child_count && $audit_child_count>0) {
                    $block_audit_child = [
                        'title' => '待审核家属',
                        'tip' => $tip,
                        'url' => $site_url . $base_url . 'pages/Community/index/auditManagement?index_key=0&type=2',
                        'count' => $audit_child_count,
                        'icon_img' => $site_url . $static_resources . 'images/property_app/' . 'family_to_be_reviewed.png',
                        'type' => 'family_to_be_reviewed'
                    ];
                    $todo_info[] = $block_audit_child;
                }
            }
            if (empty($this->auth) || in_array(410,$this->auth)) {
                // 待审核租客
                $where_tenant_count = [
                    ['type', '=', '2'],
                    ['status', '=', '2'],
                    ['village_id', '=', $village_id],
                ];
                $audit_tenant_count = $service_house_village->getVillageUserNum($where_tenant_count);
                if($audit_tenant_count && $audit_tenant_count>0) {
                    $block_tenant_count = [
                        'title' => '待审核租客',
                        'tip' => $tip,
                        'url' => $site_url . $base_url . 'pages/Community/index/auditManagement?index_key=0&type=3',
                        'count' => $audit_tenant_count,
                        'icon_img' => $site_url . $static_resources . 'images/property_app/' . 'tenants_to_be_reviewed.png',
                        'type' => 'tenants_to_be_reviewed'
                    ];
                    $todo_info[] = $block_tenant_count;
                }
            }
            if (empty($this->auth) || in_array(107,$this->auth)) {
                // 待申请解绑
                $where_unbind = [
                    ['status', '=', '1'],
                    ['village_id', '=', $village_id],
                ];
                $unbind_count = $service_house_village->get_village_unbind_user_num($where_unbind);
                if($unbind_count && $unbind_count>0) {
                    $block_unbind = [
                        'title' => '解绑申请',
                        'tip' => $tip,
                        'url' => $site_url . $base_url . 'pages/Community/index/auditManagement?index_key=0&type=4',
                        'count' => $unbind_count,
                        'icon_img' => $site_url . $static_resources . 'images/property_app/' . 'to_be_released.png',
                        'type' => 'to_be_released'
                    ];
                    $todo_info[] = $block_unbind;
                }
            }
                // 带审核文章
//            if (empty($this->auth) || in_array(127,$this->auth)) {
//                // 待审核文章
//                $where_bbs = [
//                    ['a.aricle_status', '=', 2],
//                    ['b.bbs_id', '=', $village_id],
//                ];
//                $bbs_count = $service_house_village->get_bbs_aricle_num($where_bbs);
//                if($bbs_count && $bbs_count>0) {
//                    $block_bbs = [
//                        'title' => '待审核文章',
//                        'tip' => $tip,
//                        'url' => '',
//                        'count' => $bbs_count,
//                        'icon_img' => $site_url . $static_resources . 'images/property_app/'.'artical_to_be_reviewed.png',
//                        'type'=>'artical_to_be_reviewed'
//                    ];
//                    $todo_info[] = $block_bbs;
//                }
//            }
            //过滤购买套餐包含功能 判断
            if(in_array(16,$package_content)) {
                if (empty($this->auth) || in_array(219, $this->auth)) {
                    // 待处理报修
                    $where_baoxiu = [
                        ['type', '=', 1],
                        ['status', '=', 0],
                        ['village_id', '=', $village_id]
                    ];
                    $baoxiu_count = $service_house_village->get_repair_list_num($where_baoxiu);
                    if ($baoxiu_count && $baoxiu_count > 0) {
                        $block_baoxiu = [
                            'title' => '待处理报修',
                            'tip' => $tip,
                            'url' => $site_url . $base_url . 'pages/Community/onlineReport/onlineReport?genre=1&type=1',
                            'count' => $baoxiu_count,
                            'icon_img' => $site_url . $static_resources . 'images/property_app/pending_repair.png?t=01',
                            'type' => 'pending_repair'
                        ];
                        $todo_info[] = $block_baoxiu;
                    }
                }
            }
            //过滤购买套餐包含功能 判断
            if(in_array(19,$package_content)) {
                if (empty($this->auth) || in_array(224, $this->auth)) {
                    // 待处理投诉建议
                    $where_suggest = [
                        ['type', '=', 3],
                        ['status', '=', 0],
                        ['village_id', '=', $village_id]
                    ];
                    $suggest_count = $service_house_village->get_repair_list_num($where_suggest);
                    if ($suggest_count && $suggest_count > 0) {
                        $block_suggest = [
                            'title' => '待处理投诉建议',
                            'tip' => $tip,
                            'url' => $site_url . $base_url . 'pages/Community/onlineReport/onlineReport?genre=3&type=1',
                            'count' => $suggest_count,
                            'icon_img' => $site_url . $static_resources . 'images/property_app/' . 'suggestion_to_be_reviewed.png?t=01',
                            'type' => 'suggestion_to_be_reviewed'

                        ];
                        $todo_info[] = $block_suggest;
                    }
                }
            }
            //过滤购买套餐包含功能 判断
            if(in_array(15,$package_content)) {
                if (empty($this->auth) || in_array(207, $this->auth)) {
                    // 待处理快递
                    $where_express = [
                        ['status', '=', 0],
                        ['village_id', '=', $village_id]
                    ];
                    $express_count = $service_house_village->get_village_express_num($where_express);
                    if ($express_count && $express_count > 0) {
                        $block_express = [
                            'title' => '待处理快递',
                            'tip' => $tip,
                            'url' => $site_url . $base_url . 'pages/Community/expressManagement/expressManagement?type=0',
                            'count' => $express_count,
                            'icon_img' => $site_url . $static_resources . 'images/property_app/' . 'express_to_be_reviewed.png?t=01',
                            'type' => 'express_to_be_reviewed'
                        ];
                        $todo_info[] = $block_express;
                    }
                }
            }
            //过滤购买套餐包含功能 判断
            if(in_array(18,$package_content)) {
                if (empty($this->auth) || in_array(222, $this->auth)) {
                    // 待处理水电煤气上报
                    $where_water = [
                        ['type', '=', 2],
                        ['status', '=', 0],
                        ['village_id', '=', $village_id]
                    ];
                    $water_count = $service_house_village->get_repair_list_num($where_water);
                    if ($water_count && $water_count > 0) {
                        $block_water = [
                            'title' => '待处理水电煤气上报',
                            'tip' => $tip,
                            'url' => $site_url . $base_url . 'pages/Community/onlineReport/onlineReport?genre=2&type=1',
                            'count' => $water_count,
                            'icon_img' => $site_url . $static_resources . 'images/property_app/' . 'water_to_be_reviewed.png?t=01',
                            'type' => 'water_to_be_reviewed'
                        ];
                        $todo_info[] = $block_water;
                    }
                }
            }
            $house_new_repair_works_order_service = new HouseNewRepairService();
            $worker_id = $this->_uid;
            $login_role = $this->login_role;
            $repair_order_count = $house_new_repair_works_order_service->workGetWorksOrderLists($worker_id,$login_role,'todo','',0,0,true);

            $block_order = [
                'title' => '工单处理中心',
                'tip' => $tip, // url 没有值时候的提示
                'url' => $site_url . $base_url . 'pages/CommunityPages/workOrder/eventList',
                'count' => $repair_order_count,
                'icon_img' => $site_url . $static_resources . 'images/property_app/pending_repair.png?t=01',
                'type' => 'repair_order'
            ];
            $todo_info[] = $block_order;

            // 作废、退款审核 小区物业管理人员 增加（我的审批）入口
            if(5 == $this->login_role && $village_id >0){
                // 统计 默认为0
                $my_check_num = 0;

                $db_house_admin = new HouseAdmin();
                $db_house_worker = new HouseWorker();

                $wid = 0;
                $where_house_admin = [];
                $where_house_admin[] = ['id','=',$this->_uid];
                // 查询物业管理人员表与工作人员的关系
                $info = $db_house_admin->getOne($where_house_admin,'wid,phone,village_id');
                if($info && !$info->isEmpty()) {
                    $info = $info->toArray();
                    if (!empty($info['wid'])) {
                        $wid = $info['wid'];
                    } else {
                        $where_house_worker = [];
                        $where_house_worker[] = ['phone', '=', $info['phone']];
                        $where_house_worker[] = ['village_id', '=', $info['village_id']];
                        // 查询物业管理人员表与工作人员的关系
                        $infoWorker = $db_house_worker->getOne($where_house_worker, 'wid');
                        if ($infoWorker && !$infoWorker->isEmpty()) {
                            $infoWorker = $infoWorker->toArray();
                            $wid = $infoWorker['wid'];
                        }
                    }
                }
                if ($wid > 0) {
                    $houseVillageCheckauthSetService = new HouseVillageCheckauthSetService();
                    $whereArr = array('village_id' => $village_id, 'is_open' => 1);
                    $is_open = $houseVillageCheckauthSetService->isOpenSet($whereArr);
                    if ($is_open > 0) {
                        $db_checkauth_detail = new HouseVillageCheckauthDetail();
                        $where_check = [];
                        $where_check[] = ['wid', '=', $wid];
                        $where_check[] = ['village_id', '=', $village_id];
                        $where_check[] = ['status', '=', 0];
                        $my_check_num = $db_checkauth_detail->statisticsCheck($where_check);

                        $block_unbind = [
                            'title' => '我的审批',
                            'tip' => $tip,
                            'url' => $site_url . $base_url . 'pages/Community/reviewRefund/myApproval',
                            'count' => $my_check_num,
                            'icon_img' => $site_url . $static_resources . 'images/property_app/' . 'to_be_released.png',
                            'type' => 'my_check'
                        ];
                        $todo_info[] = $block_unbind;
                    }
                }
            }

            $arr['todo_info'] = $todo_info;
        } else {
            // 其他身份暂时不给予其他 默认给予数量 均为0
            $room_block = [
                'title' => ((new HouseVillageService())->getHouseAlias('room_name',$village_id)?:'房间'),
                'count' => 0,
                'url' => ''
            ];
            $village_count_block[] = $room_block;
            $owner_block = [
                'title' => '业主',
                'count' => 0,
                'url' => ''
            ];
            $village_count_block[] = $owner_block;
            $family_block = [
                'title' => '家属',
                'count' => 0,
                'url' => ''
            ];
            $village_count_block[] = $family_block;
            $tenant_block = [
                'title' => '租客',
                'count' => 0,
                'url' => ''
            ];
            $village_count_block[] = $tenant_block;
            $car_block = [
                'title' => '车辆',
                'count' => 0,
                'url' => ''
            ];
            $village_count_block[] = $car_block;
            $park_block = [
                'title' => '车位',
                'count' => 0,
                'url' => ''
            ];
            $village_count_block[] = $park_block;

            $arr['village_count_block'] = $village_count_block;
        }
        $arr['show_village_visitor_count']=0;
        $arr['village_visitor_count']=0;
        $is_show_village_visitor_count=cfg('is_show_village_visitor_count');
        $is_show_village_visitor_count=$is_show_village_visitor_count ? intval($is_show_village_visitor_count):0;
        if(5==$this->login_role && $is_show_village_visitor_count>0){
            $arr['show_village_visitor_count']=1;
            $houseVillageVisitorService=new HouseVillageVisitorService();
            $visitorWhere=array(array('village_id','=',$village_id));
            //$visitorWhere[]=array('status','<>',3);
            $visitorWhere[]=array('add_time','>=',strtotime(date('Y-m-d').' 00:00:00'));
            $visitorWhere[]=array('add_time','<=',strtotime(date('Y-m-d').' 23:59:59'));
            $visitorCount= $houseVillageVisitorService->getVisitorCount($visitorWhere);
            $arr['village_visitor_count']=$visitorCount;
        }

        return api_output(0,$arr);
    }

    /**
     * Notes: 业主家属租客待处理事项 头部 和首页相同返回
     * @return \json
     * @author: wanzy
     * @date_time: 2020/11/20 9:37
     */
    public function toBeReviewed()
    {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->login_info['village_id'];
        if (empty($village_id)) {
            $village_id = $this->request->param('village_id','0','intval');
        }
        $tabBars = [];
        $service_house_village = new HouseVillageService();
        if (empty($this->auth) || in_array(101,$this->auth)) {
            // 待审核业主
            $where_audit_user = [
                ['is_del', '=', '0'],
                ['status', '=', '2'],
                ['village_id', '=', $village_id],
            ];
            $audit_user_count = $service_house_village->getVillageRoomNum($where_audit_user);
            if($audit_user_count && $audit_user_count>0) {
                $tabBars[] = [
                    'title'=>'待审核业主',
                    'type'=>'owner_to_be_reviewed'
                ];
            }
        }
        if (empty($this->auth) || in_array(103,$this->auth)) {
            // 待审核家属
            $where_child_count = [
                ['type', '=', '1'],
                ['status', '=', '2'],
                ['village_id', '=', $village_id],
            ];
            $audit_child_count = $service_house_village->getVillageUserNum($where_child_count);
            if($audit_child_count && $audit_child_count>0) {
                $tabBars[] = [
                    'title'=>'待审核家属',
                    'type'=>'family_to_be_reviewed'
                ];
            }
        }
        if (empty($this->auth) || in_array(410,$this->auth)) {
            // 待审核租客
            $where_tenant_count = [
                ['type', '=', '2'],
                ['status', '=', '2'],
                ['village_id', '=', $village_id],
            ];
            $audit_tenant_count = $service_house_village->getVillageUserNum($where_tenant_count);
            if($audit_tenant_count && $audit_tenant_count>0) {
                $tabBars[] = [
                    'title'=>'待审核租客',
                    'type'=>'tenants_to_be_reviewed'
                ];
            }
        }
        if (empty($this->auth) || in_array(107,$this->auth)) {
            // 待申请解绑
            $where_unbind = [
                ['status', '=', '1'],
                ['village_id', '=', $village_id],
            ];
            $unbind_count = $service_house_village->get_village_unbind_user_num($where_unbind);
            if($unbind_count && $unbind_count>0) {
                $tabBars[] = [
                    'title'=>'解绑申请',
                    'type'=>'to_be_released'
                ];
            }
        }
        return api_output(0,$tabBars);
    }

    public function footer_list(){
        $village_id  = $this->login_info['village_id'];
        if (empty($village_id)) {
            $village_id = $this->request->param('village_id','0','intval');
        }
        if (empty($village_id)) {
            return api_output(1002,[],'登录信息错误，请重新登录');
        }
        $res['hidden']= true;
        $res["backgroundColor"]= "#FAFAFC";
        $res["color"]= "#777777";
        $res["selectedColor"]= "#06c1ae";
        $service_house_village = new HouseVillageService();
        $list=$service_house_village->footer_list($village_id);
        $res['list']=$list;

        return api_output(0,$res);

    }

    /**
     * 获取当前登录人员的手机号
     * @author:zhubaodi
     * @date_time: 2022/4/1 10:14
     */
    public function getAdminPhone(){
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $now_login = $this->login_info;
        $data = [
            'phone' => $now_login['login_phone']?$now_login['login_phone']:'',
            'name' => $now_login['login_name']?$now_login['login_name']:'',
        ];
        try{
            $arr = [];
            $arr['info'] = $data;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 修改当前工作人员的登录密码
     * @author:zhubaodi
     * @date_time: 2022/4/1 10:40
     */
    public function editPwd(){
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        $now_login = $this->login_info;
        $new_pwd = $this->request->param('new_pwd','','strval');
        $old_pwd = $this->request->param('old_pwd','','strval');
        $phone = $this->request->param('phone','','trim');
        if (empty($phone)){
            return api_output(1001,[],'请填写手机号码！');
        }
       if (empty($old_pwd)){
           return api_output(1001,[],'请先填写原密码！');
       }
        if (empty($new_pwd)){
            return api_output(1001,[],'请先填写新密码！');
        }
        if ($new_pwd==$old_pwd){
            return api_output(1001,[],'请重新输入新密码！');
        }
        // 获取的密码md5
        $new_pwd = md5($new_pwd);
        $old_pwd = md5($old_pwd);
        $service_login = new ManageAppLoginService();
        $service_house_village = new HouseVillageService();
        if (1== $login_role) {
            $serviceOrganizationStreet = new OrganizationStreetService();
            $where = [];
            $where[] = ['worker_id','=',$worker_id];
            $where[] = ['work_status','<>',4];
            $login_info = $serviceOrganizationStreet->getMemberDetail($where);
            if (empty($login_info)) {
                return api_output(1001,[],'人员信息不存在！');
            }
            if($old_pwd != $login_info['work_passwd']){
                return api_output(1003,[],'原密码错误！');
            }
            unset($login_info['work_passwd']);
            if($login_info['work_status'] != 1){
                return api_output(1003,[],'当前账号被禁止！');
            }
            // 处理登录记录和返回
            $data = [];
            $data['work_phone'] = $phone;
            $data['work_passwd'] = $new_pwd;
            $add_id = $serviceOrganizationStreet->saveStreetWorker($where,$data);
            if($add_id){
                return api_output(0,$add_id);
            } else{
                return api_output(1003,[],'修改失败！');
            }

        }
        elseif (3 == $login_role) {
            $condition_house_property['id'] = $worker_id;
            $house_property = $service_house_village->get_house_property_where($condition_house_property);
            if (empty($house_property)) {
                return api_output(1003, [], '人员信息不存在！');
            }
            if($old_pwd != $house_property['password']){
                return api_output(1003,[],'原密码错误！');
            }
            if ($house_property['status'] != 1) {
                return api_output(1003, [], '当前账号被禁止！');
            }

            // 处理登录记录和返回
            $serviceHouseProperty = new HousePropertyService();
            $data = [];
            $data['last_time'] = $_SERVER['REQUEST_TIME'];
            $data['property_phone'] = $phone;
            $data['password'] = $new_pwd;
            $add_id = $serviceHouseProperty->editData(['id'=>$house_property['id']],$data);
            if($add_id){
                return api_output(0,$add_id);
            } else{
                return api_output(1003,[],'账号或密码错误！');
            }
        }
        elseif (4 == $login_role) {
            $housePropertyVillageWorkerService= new HousePropertyVillageWorkerService();
            $village_id = $this->request->param('village_id', '', 'intval');
            $condition_house_property['id'] = $worker_id;
            $house_property = $service_login->get_property_admin_where($condition_house_property);
            if(empty($house_property)){
                return api_output(1003, [], '人员信息不存在！');
            }
            if($old_pwd != $house_property['pwd']){
                return api_output(1003,[],'原密码错误！');
            }
            if($house_property['status'] != 1){
                return api_output(1003,[],'当前账号被禁止！');
            }
            // 处理登录记录和返回
            $servicePropertyAdminService = new PropertyAdminService();
            $data = [];
            $data['last_time'] = time();
            $data['phone'] = $phone;
            $data['pwd'] = $new_pwd;
            $add_id = $servicePropertyAdminService->editData(['id'=>$house_property['id']],$data);
            if($add_id){
                if(isset($house_property['wid']) && $house_property['wid']>0){
                    $housePropertyVillageWorkerService->synWorkerToPropertyWorkerInfo($house_property['wid'],$new_pwd,'password',$house_property['property_id']);
                }
                return api_output(0,$add_id);
            } else{
                return api_output(1003,[],'修改失败！');
            }
        }
        elseif (5 == $login_role) {
            $village_id = $this->request->param('village_id', '', 'intval');
            $condition_house = [
                'status' => 1
            ];
            if($village_id>0){
                $condition_house['village_id'] = $village_id;
            }
            $now_house = $service_house_village->getList($condition_house);
            $housePropertyVillageWorkerService= new HousePropertyVillageWorkerService();
            if ($now_house) {
                $condition_house['id'] = $worker_id;
                // 获取到的是 小区工作人员
                $role = $service_login->get_house_admin_where($condition_house,'*');
                if(empty($role)){
                    return api_output(1003, [], '人员信息不存在！');
                }
                if($old_pwd != $role['pwd']){
                    return api_output(1003,[],'原密码错误！');
                }
                // 处理登录记录和返回
                $data = [];
                $data['last_time'] = $_SERVER['REQUEST_TIME'];
                $data['phone'] = $phone;
                $data['pwd'] = $new_pwd;
                $add_id = $service_login->edit_house_admin(['id' => $role['id']],$data);
                if($add_id){
                    if(isset($role['wid']) && $role['wid']>0){
                        $housePropertyVillageWorkerService->synWorkerToPropertyWorkerInfo($role['wid'],$new_pwd,'password');
                    }
                    return api_output(0,$add_id);
                } else{
                    return api_output(1003,[],'修改失败！');
                }
            } else {
                $condition_admin = [];
                $condition_admin[] = ['a.status', '=', 1];
                $condition_admin[] = ['a.id', '=', $worker_id];
                if($village_id>0){
                    $condition_admin[] = ['a.village_id', '=', $village_id];
                }
                $field = 'a.*,hv.village_name';
                $login_admin = $service_login->get_admin_village_list($condition_admin,$field);
                if(empty($login_admin)){
                    return api_output(1003, [], '人员信息不存在！');;
                }
                $login_admin =  $login_admin[0];
                
                if($old_pwd != $login_admin['pwd']){
                    return api_output(1003,[],'原密码错误！');
                }
                if($login_admin['status'] == 2){
                    return api_output(1003,[],'当前账号被禁止！');
                }
                // 处理登录记录和返回
                $data = [];
                $data['last_time'] = $_SERVER['REQUEST_TIME'];
                $data['phone'] = $phone;
                $data['pwd'] = $new_pwd;
                $add_id = $service_login->edit_house_admin(['id' => $login_admin['id']],$data);
                if($add_id){
                    if(isset($login_admin['wid']) && $login_admin['wid']>0){
                        $housePropertyVillageWorkerService->synWorkerToPropertyWorkerInfo($login_admin['wid'],$new_pwd,'password');
                    }
                    return api_output(0,$add_id);
                } else{
                    return api_output(1003,[],'修改失败！');
                }
            }
        }
        elseif (6 == $login_role){//小区物业工作人员 2020/10/20 start  新增
            $village_id = $this->request->param('village_id', '', 'intval');
            $data = [
                'village_id'=>$village_id,
                'wid'=>$worker_id,
            ];
            $housePropertyVillageWorkerService= new HousePropertyVillageWorkerService();
            $now_house_worker = $service_house_village->getHouseWorker($data);
            if ($now_house_worker) {
                if($old_pwd != $now_house_worker['password']){
                    return api_output(1003,[],'原密码错误！');
                }
                // 处理登录记录和返回
                $data = [];
                $data['login_time'] = time();
                $data['phone'] = $phone;
                $data['password'] = $new_pwd;
                $res = $service_house_village->saveHouseWorker($now_house_worker['wid'],$data);
                if($res){
                    if(isset($now_house_worker['hw_id']) && $now_house_worker['hw_id']>0){
                        $saveData=array('pwd'=>$new_pwd);
                        $housePropertyVillageWorkerService->synEditPropertyVillageWorker($now_house_worker['hw_id'],$saveData);
                    }
                    $db_house_admin = new HouseAdmin();
                    if($now_house_worker['xtype'] == 2 && $now_house_worker['relation_id'] > 0){
                        $whereArr = array();
                        $whereArr['id'] = $now_house_worker['relation_id'];
                        $updateArr=array();
                        $updateArr['pwd'] = $new_pwd;
                        $updateArr['phone'] = $phone;
                        $upres = $db_house_admin->save_one($whereArr, $updateArr);
                    }
                    return api_output(0,$res);
                }else{
                    return api_output(1003,[],'修改失败！');
                }
            } else {
                return api_output(1003,[],'修改失败！');
            }
        }
        elseif (11== $login_role || 21 == $login_role) {
            // 街道和社区登录保留 值往后延
            // 街道登录
            //  初始化 社区 业务层
            $service_area_street = new AreaStreetService();
            $where['area_id'] = $worker_id;
            $login_info = $service_area_street->getAreaStreet($where);
            if(empty($login_info)){
                return api_output(1003, [], '人员信息不存在！');
            }
            if($old_pwd != $login_info['pwd']){
                return api_output(1003,[],'原密码错误！');
            }
            // 处理登录记录和返回
            $data = [];
            $data['area_id'] = $login_info['area_id'];
            $data['phone'] = $phone;
            $data['pwd'] = $new_pwd;
            $data['add_time'] = $_SERVER['REQUEST_TIME'];

            $add_id = $service_area_street->addIndex($data);
            if($add_id){
                return api_output(0,$add_id);
            } else{
                return api_output(1003,[],'修改失败！');
            }
        }
        else {
            return api_output(1001,[],'修改失败！');
        }
    }


}