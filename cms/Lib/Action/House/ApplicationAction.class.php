<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2020/1/9
 * Time: 13:19
 */

class ApplicationAction extends BaseAction
{
    use customization;
    public function index(){
        // 营山域名限制
        $domain_restrict_huizhisq = $this->hasCloudIntercom();
        // 杂淘域名限制
        $domain_restrict_zatao = $this->hasThirdZatao();

        $condition_application = array();
        $from = 0; // 默认所属社区应用
        $condition_application['from'] = $from;
        // 过滤固定应用
        $condition_application['application_type'] = 0;

        $works_order_switch=false;
        $villag_info = M('House_village_info')->where(['village_id'=>$this->house_session['village_id']])->field('works_order_switch')->find();
        if ($villag_info && $villag_info['works_order_switch'] == 1){
            $works_order_switch=true;
        }
        $oldWorksOrderApplication = [16,19];// 老版工单的目录
        //插件列表
//        $application_list = S('application_list_'.$from);
        $takeEffectTimeJudge = false;
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        if ($is_new_charge) {
            // 设置了生效时间 并且 当前时间大于生效时间 返回true 其他返回false
            $takeEffectTimeJudge = true;
        }
        $oldVersionChargeApplication = [8,9,18];// 老版收费本目录过滤 当前 抄表录入+移动抄表
        if(empty($application_list)){
            $application_cat = M('Application_cat')->where(['cat_from'=>0])->order('`cat_sort` DESC,`cat_id` ASC')->select();
            foreach($application_cat as $value){
                $application_list[$value['cat_id']] = $value;
                $application_list[$value['cat_id']]['application_list'] = [];
            }

            //2020/8/26 start 过滤没有购买的功能应用
            $property_id = session('house.property_id');
            $packageDatas = D('PrivilegePackage')->getPackageContent($property_id);

            // 公安人脸 营山定制是需要一直显示的
            if($domain_restrict_huizhisq){
                $packageDatas['application_id'][] = 47;
            }else{
                $packageDatas['application_id'] = array_diff($packageDatas['application_id'],[47]);
            }

            $package_application_id = $packageDatas['application_id'];
            if(!$package_application_id || count($package_application_id)<=0){
                $package_application_id = [0];
            }
            //2020/8/26 end

            $database_house_menu = D('House_menu_new');
            $condition_house_menu['status'] = 1;
            $condition_house_menu['show'] = 1;
            $condition_house_menu['type'] = array('neq',2);
            $condition_house_menu['application_id'] = array('neq',0);
            //2020/8/26 start 过滤没有购买的功能应用
            $condition_house_menu['application_id'] = ['in',$package_application_id];
            //2020/8/26 end
            $house_menu = $database_house_menu->field(true)->where($condition_house_menu)->getField('application_id,id,name');
            $application = M('Application_list')->where($condition_application)->order('`application_sort` DESC,`application_id` ASC')->select();
          //  print_r($_SERVER['HTTP_HOST']);exit;
            foreach($application as $value){
                if ($takeEffectTimeJudge && in_array($value['application_id'],$oldVersionChargeApplication)) {
                    continue;
                }
                if ($works_order_switch && in_array($value['application_id'],$oldWorksOrderApplication)) {
                    continue;
                }
                if ($house_menu && !$house_menu[$value['application_id']] && ( $value['application_house_type'] != 4)) {
                    continue;
                }
                if(!empty($value)){
                    $value['application_ico_arr'] = array_flip(explode(' ',$value['application_ico']));
                }
                // 公安人脸 营山定制 暂不放开
                if ($value['application_id']==47&&!$domain_restrict_huizhisq){
                    continue;
                }
                // 杂淘 杂淘定制 暂不放开
                if ($value['application_id']==46&&!$domain_restrict_zatao){
                    continue;
                }
                $application_list[$value['cat_id']]['application_list'][] = $value;
            }
            $application_list = array_values($application_list);
            S('application_list_'.$from, $application_list);
        }
        $this->assign('application_list',$application_list);

        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display();
    }
    // 应用详情页面共用的一部分
    public function page_public() {
        $application_id = I('application_id');
        $condition_application = array(
            'application_id' => $application_id
        );
        $application = M('Application_list')->where($condition_application)->find();
        if ($application['application_detail']) {
            $application['application_detail'] = replace_file_domain_content($application['application_detail']);
        }
        if ($application['application_img']) {
            $application['application_img'] = replace_file_domain($application['application_img']);
        } else {
            if (!$application['application_label']) {
                $application['application_label'] = 'default';
            }
            $application['application_img'] = $this->config['site']."/static/application/img/{$application['application_label']}_detail.png?t=001";
        }
        // 查询下是否已经添加到我的应用
        $village_id = $this->house_session['village_id'];
        $property_id = $this->house_session['property_id'];
        $where = array(
            'application_id' => $application_id,
            'use_id' => $village_id,
            'from' => 0,
            'status' => 0,
        );
        if ($property_id>0) {
            $where['other_id'] = $property_id;
        }
        $bind = M('Application_bind')->where($where)->find();
        if ($bind) {
            $application['is_bind'] = true;
        } else {
            $application['is_bind'] = false;
        }
        // 是否是v20环境
        $is_v20 = true;
        $this->assign('is_v20',$is_v20);
        $menu_new = M('House_menu_new')->where(array('application_id' => $application_id))->find();
        if ($application['application_type']!=1) {
            if ($is_v20) {
                if(!empty($menu_new['module'])){
                    $path_name = $path_name = 'house_'.strtolower($menu_new['module']).'_'.strtolower($menu_new['action']);
                    $url = "/village/village.iframe/{$path_name}";
                }else{
                    // 直接使用 v20新路由
                    $url = $menu_new['path'];
                }
            } elseif($application['application_house_type']==1){
                $url = '/shequ.php?g=House&c=Plugin&a='.$menu_new['action'].'&url='.urlencode($this->config['site_url'].$menu_new['jump_url']);
            } elseif ($application['application_house']) {
                $url = $application['application_house'];
            } else {
                $url = U($menu_new['module'].'/'.$menu_new['action']);
            }
            $this->assign('url',$url);
        }
        $property_id = session('house.property_id');
        $menus = session('house.menus');
        $packageDatas = D('PrivilegePackage')->getPackageContent($property_id);
        if ($packageDatas && $packageDatas['application_id'] && in_array($application_id, $packageDatas['application_id'])) {
            if ($menus && $menu_new && in_array($menu_new['id'], $menus)) {
                $is_auth = true;
            } else {
                $is_auth = false;
            }
        } else {
            $is_auth = false;
        }
        $this->assign('is_auth',$is_auth);

        $this->assign('application_detail',$application);
    }
    public function detail() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display();
    }
	public function add_my_applicat() {
        $application_id = I('application_id');
        $menusId = I('menusId');
        $condition_application = array(
            'application_id' => $application_id
        );
        $application = M('Application_list')->where($condition_application)->find();
		if (empty($application)) {
            $this->error('添加的应用不存在！');
		}
    	$village_id = $this->house_session['village_id'];
		$property_id = $this->house_session['property_id'];
		$where = array(
			'application_id' => $application_id,
			'use_id' => $village_id,
			'from' => 0,
			'status' => 0,
		);
        if ($property_id>0) {
            $where['other_id'] = $property_id;
        }
        $bind = M('Application_bind')->where($where)->find();
        $v20_arr = [33,37,45];
		if (!empty($bind)) {
            if ($application['application_type'] == 1) {
                $status = 2;
                if ($menusId==665 && $application_id==33) {
                    // 暂时只针对 33【收费管理】 应用
                    $url = '/village/village.charge.standard/chargeSet';
                } elseif ($menusId=='111165' && $application_id==33) {
                    // 暂时只针对 33【收费管理】 应用
                    $url = '/village/village.charge.summary/feeSummaryList';
                } elseif (in_array($application['application_house_type'],array(0,1)) && $application['application_house']) {
                    $url = $this->config['site_url'].$application['application_house'];
                } elseif (in_array($application['application_house_type'],array(2,3)) && $application['application_house']) {
                    $url = $application['application_house'];
                }
                if (in_array($application_id, $v20_arr)) {
                    $is_v20 = true;
                    if(!empty($url) && strpos($url,'village.workOrder.repairCate') && strpos($url,'repairSubjectList')){
                        $url='/v20/public/platform/#/village/village.workOrder.repairCate/newRepairCateList';
                    }
                } else {
                    $is_v20 = false;
                }
                if ($application_id==33) {
                    $status = 3;
                }
                if ($url) {
                    S('house_application_list_' .$village_id.'_'. $this->config['system_lang'],null);
                    $this->calculation_bind_num($application_id);
                    D('Application_list')->set_config($application_id,$village_id,true);
                    $msg = array(
                        'message' => '添加成功！',
                        'status' => $status,
                        'url' => $url,
                        'is_v20' => $is_v20
                    );
                    invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);
                    $this->ajaxReturn($msg);
                } elseif ($is_v20) {
                    $msg = array(
                        'message' => '该应用已经添加，正在跳转！',
                        'status' => $status,
                        'is_v20' => $is_v20
                    );
                    $this->ajaxReturn($msg);
                }
            }
            invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);
            $this->error('该应用已经添加！');
		}
		$data = array(
			'application_id' => $application_id,
			'use_id' => $village_id,
			'add_time' => time()
		);
        if ($property_id>0) {
            $data['other_id'] = $property_id;
        }
		$bind_id = M('Application_bind')->data($data)->add();
		if ($bind_id) {
            $status = 2;
            S('house_application_list_' .$village_id.'_'. $this->config['system_lang'],null);
            if ($application['application_type'] == 1) {
                if ($menusId=='665' && $application_id==33) {
                    // 暂时只针对 33【收费管理】 应用
                    $url = '/village/village.charge.standard/chargeSet';
                } elseif ($menusId=='111165' && $application_id==33) {
                    // 暂时只针对 33【收费管理】 应用
                    $url = '/village/village.charge.summary/feeSummaryList';
                } elseif (in_array($application['application_house_type'],array(0,1)) && $application['application_house']) {
                    $url = $application['application_house'];
                } elseif (in_array($application['application_house_type'],array(2,3)) && $application['application_house']) {
                    $url = $application['application_house'];
                }
                if (in_array($application_id, $v20_arr)) {
                    $is_v20 = true;
                    if(!empty($url) && strpos($url,'village.workOrder.repairCate') && strpos($url,'repairSubjectList')){
                        $url='/v20/public/platform/#/village/village.workOrder.repairCate/newRepairCateList';
                    }
                } else {
                    $is_v20 = false;
                }
                if ($application_id==33) {
                    $status = 3;
                }
                if ($url) {
                    $msg = array(
                        'message' => '已将该功能成功添加至我的应用，可前去体验！',
                        'status' => $status,
                        'url' => $url,
                        'is_v20' => $is_v20
                    );
                    $this->calculation_bind_num($application_id);
                    D('Application_list')->set_config($application_id,$village_id,true);
                    invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);
                    $this->ajaxReturn($msg);
                } elseif ($is_v20) {
                    $msg = array(
                        'message' => '已将该功能成功添加至我的应用，正在跳转！',
                        'status' => $status,
                        'is_v20' => $is_v20
                    );
                    invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);
                    $this->ajaxReturn($msg);
                }
            }
            $msg = array(
				'message' => '已将该功能成功添加至我的应用，可前去体验！',
				'status' => 1
			);
            $this->calculation_bind_num($application_id);
            D('Application_list')->set_config($application_id,$village_id,true);
            invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);
			$this->ajaxReturn($msg);
		} else {
            $this->error('添加失败！');
		}
	}
    public function remove_my_application() {
        $application_id = I('application_id');
        $condition_application = array(
            'application_id' => $application_id
        );
        $application = M('Application_list')->where($condition_application)->find();
        if (empty($application)) {
            $this->error('移除的应用不存在！');
        }
        $village_id = $this->house_session['village_id'];
        $property_id = $this->house_session['property_id'];
        $where = array(
            'application_id' => $application_id,
            'use_id' => $village_id,
            'from' => 0,
            'status' => 0,
        );
        if ($property_id>0) {
            $where['other_id'] = $property_id;
        }
        $bind = M('Application_bind')->where($where)->find();
        if (empty($bind)) {
            invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);
            $this->error('该应用已经被移出！');
        }
        $data = array(
            'status' => 4,
            'del_time' => time(),
        );
        $bind_id = M('Application_bind')->where(array('bind_id' => $bind['bind_id']))->data($data)->save();
        if ($bind_id) {
            S('house_application_list_' .$village_id.'_'. $this->config['system_lang'],null);
            $msg = array(
                'message' => '已将该功能成功移出我的应用，如有需要，可再次添加！',
                'status' => 1
            );
            D('Application_list')->set_config($application_id,$village_id,false);
            invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);
            $this->ajaxReturn($msg);
        } else {
            invoke_v20_service('\app\community\model\service\VillageMenuService/clearCache',['village_id' => $village_id]);
            $this->error('移出失败！');
        }

    }
    public function my_application() {
        $village_id = $this->house_session['village_id'];
        $property_id = $this->house_session['property_id'];
        $application_id = I('application_id');
        $d_application_list = D('Application_list');
        $application_list = $d_application_list->my_left($village_id, $property_id, $application_id);
        $this->assign('application_list',$application_list);
        $this->display();
    }
    // 蓝牙门禁
    public function Door_door_list() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
    // 人脸识别门禁
    public function Face_door_door_list() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
    // 人脸识别摄像机
    public function Face_door_camera_list() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
    // 智能快递柜
    public function Intelligence_express_express_list() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
    // 智慧充电桩
    public function SmartCharge_index() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
	// 智慧电瓶车
    public function Electric_device_index() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
    // 智慧停车
    public function Park_park_month_list() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
    // 企业微信
    public function qyWeixin_index() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
    // 工单管理
    public function repairSubjectList() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }
    // ↓新版收费未使用应用时候的页面展示应用信息
    // 收费设置
    public function setCharge() {
        $this->newChargeApplicationDetail();
    }
    // 收费项目管理
    public function projectList() {
        $this->newChargeApplicationDetail();
    }
    // 收费标准管理
    public function chargeStandardAll() {
        $this->newChargeApplicationDetail();
    }
    // 已删除收费标准
    public function chargeStandardAllDel() {
        $this->newChargeApplicationDetail();
    }
    // 收银台
    public function cashierOrderList() {
        $this->newChargeApplicationDetail();
    }
    // 	应收账单
    public function receivableOrderList() {
        $this->newChargeApplicationDetail();
    }
    // 已缴账单
    public function payableOrderList() {
        $this->newChargeApplicationDetail();
    }
    // 作废账单
    public function cancelOrderList() {
        $this->newChargeApplicationDetail();
    }
    // 	抄表管理
    public function getMeterProject() {
        $this->newChargeApplicationDetail();
    }
    // 退款账单
    public function refundOrderList() {
        $this->newChargeApplicationDetail();
    }
    // 第三方支付账单
    public function onlineOrderList() {
        $this->newChargeApplicationDetail();
    }
    // 新版打印模板设置
    public function printTemplateSet() {
        $this->newChargeApplicationDetail();
    }
    // 打印模板设置
    public function Cashier_print_template_list() {
        $this->newChargeApplicationDetail();
    }

    // 新老版收费公用
    public function newChargeApplicationDetail() {
        $this->page_public();
        $page_magin = 1;
        $this->assign('page_magin',$page_magin);
        $this->display('detail');
    }

    // 老版
    //物业余额-新老版通用
    public function Village_money_money_list() {
        $this->newChargeApplicationDetail();
    }
    // 收银台
    public function Cashier_cashier() {
        $this->newChargeApplicationDetail();
    }
    // 收银台已缴账单
    public function Cashier_cashier_paid_list() {
        $this->newChargeApplicationDetail();
    }
    // 收费设置
    public function Charge_set_pay_set() {
        $this->newChargeApplicationDetail();
    }
    // 物业费标准管理
    public function Unit_propertyfee_list() {
        $this->newChargeApplicationDetail();
    }
    // 物业自定义费用
    public function Cashier_payment_item() {
        $this->newChargeApplicationDetail();
    }
    // 物业费周期管理
    public function Unit_preferential_list() {
        $this->newChargeApplicationDetail();
    }
    // 物业已缴总账单
    public function Unit_pay_order() {
        $this->newChargeApplicationDetail();
    }
    // 物业未缴总账单
    public function Unit_cashier_unpaid() {
        $this->newChargeApplicationDetail();
    }
    // 计算应用使用数量
    private function calculation_bind_num($application_id) {
        $where = array(
            'application_id' => $application_id,
            'from' => 0
        );
        $select = M('Application_bind')->where($where)->distinct(true)->field('use_id,other_id')->select();
        if (!$select) {
            $count = 0;
        } else {
            $count = count($select);
        }
        M('Application_list')->where($where)->setField('application_num', $count);
    }
}