<?php
/**
 * 团队管理Controller
 * User: wangchen
 * Date: 2021/8/19
 */
namespace app\new_marketing\controller\platform;

use app\new_marketing\model\service\MarketingPersonService;
use app\new_marketing\model\service\MarketingTeamService;
use app\new_marketing\model\service\MarketingOrderService;
use app\new_marketing\model\service\MarketingPersonMerService;
use app\community\model\service\PackageOrderService;
use app\common\model\service\MerchantService;
use app\common\model\service\MerchantStoreService;
use app\common\model\service\AreaService;
use think\facade\Db;

class TeamManagementController extends AuthBaseController
{

	// 团队列表
	public function teamManagementList()
	{
		try {
			$page = $this->request->param('page',1,'trim');
			$pageSize = $this->request->param('pageSize',10,'trim');
			$name = $this->request->param('name','','trim');
			$type = $this->request->param('type',0,'trim');
			$area = $this->request->param('area',[],'trim');
			$area_uid = $this->request->param('area_uid',0,'trim');
			$begin_time = $this->request->param('begin_time','','trim');
			$end_time = $this->request->param('end_time','','trim');
			// 条件
			$where = [['g.is_del','=',0]];
			// 区域
			if(!empty($area)){
				if(!empty($area[0])){
					$where[] = ['g.province_id', '=', $area[0]];
					if(!empty($area[1])){
						$where[] = ['g.city_id', '=', $area[1]];
						if(!empty($area[2])){
							$where[] = ['g.area_id', '=', $area[2]];
						}
					}
				}
			}
			// 名称类型 1团队名称 2业务经理名称
			if($type == 1){
				// 团队名称
				if($name != ''){
					$where[] = ['g.name', 'like', '%'.$name.'%'];
				}
			}elseif($type == 2){
				// 业务经理名称
				if($name != ''){
					$where[] = ['a.name', 'like', '%'.$name.'%'];
				}
			}
			// 区域代理
			if($area_uid != ''){
				$where[] = ['g.area_uid', '=', $area_uid];
			}
			// 创建时间
			if($begin_time != '' && $end_time != ''){
				$arr = [['g.add_time', '>=', strtotime($begin_time.' 00:00:00')], ['g.add_time', '<=', strtotime($end_time.' 23:59:59')]];
				$where = array_merge($where, $arr);
			}
			// 字段
			$field = 'g.*, a.name as manager_name, b.name as area_name';
			// 条件
			$order = 'g.id DESC';
			$arr = (new MarketingTeamService())->getMarketingManagementList($where, $field, $order, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 总业绩
	public function teamPerformance()
	{
		try {
			$id = $this->request->param('id',[],'trim');
			if(empty($id)){
				$arr['total_count'] = 0;
				$arr['total_achievement'] = 0;
			}else{
				$arr['total_count'] = count($id);
				$arr['total_achievement'] = (new MarketingTeamService())->teamPerformance($id);
			}
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 区域列表
	public function serviceAreaList()
	{
		try {
			$where = ['is_del'=>0,'identity'=>2];
			$arr = (new MarketingTeamService())->serviceAreaList($where);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 业务经理列表（全部）
	public function serviceManagerList()
	{
		try {
			$where = ['g.is_del'=>0,'g.is_manager'=>1];
			$field ='g.*,b.add_time';
			$arr = (new MarketingPersonService())->serviceManagerList($where, $field);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 业务经理列表（未绑定团队）
	public function serviceManagerNoList()
	{
		try {
			$params['area_uid'] = $this->request->param('area_uid',0,'trim');
			$where = [['is_del','=',0],['team_id','=',0]];
			$arr = (new MarketingPersonService())->serviceManagerNoList($where, $params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 区域代理列表
	public function regionalAgentList()
	{
		$params['area'] = $this->request->param('area',[],'trim');
		try {
			if(!empty($params['area'])){
				if(!empty($params['area'][0])){
					$where[] = ['c.province_id','=',$params['area'][0]];
				}
				if(!empty($params['area'][1])){
					$where[] = ['c.city_id','=',$params['area'][1]];
				}
				if(!empty($params['area'][2])){
					$where[] = ['c.area_id','=',$params['area'][2]];
				}
			}
			$where[] = ['g.is_del','=',0];
			$where[] = ['g.is_agency','=',1];
			$field ='g.*,c.add_time';
			$arr = (new MarketingPersonService())->serviceManagerList($where, $field);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 技术人员列表
	public function artisanList()
	{
		try {
			$where = ['status'=>0];
			$arr = (new MarketingPersonService())->artisanList($where);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队列表
	public function teamList()
	{
		try {
			$where = ['is_del'=>0];
			$arr = (new MarketingTeamService())->teamList($where);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队业务员列表
	public function teamBusinessList()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$where = ['a.is_del'=>0, 'a.team_id'=>$params['id']];
			$field = 'g.*,a.add_time';
			$arr = (new MarketingPersonService())->serviceManagerList($where, $field);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 创建团队
	public function teamManagementAdd()
	{
		try {
			$params['name'] = $this->request->param('name','','trim');
			$params['area'] = $this->request->param('area',[],'trim');
			$params['area_uid'] = $this->request->param('area_uid',0,'trim');
			$params['manager_uid'] = $this->request->param('manager_uid',0,'trim');
			$params['manager_percent'] = $this->request->param('manager_percent',0,'trim');
			$params['personnel_percent'] = $this->request->param('personnel_percent',0,'trim');
			$params['village_manager_percent'] = $this->request->param('village_manager_percent',0,'trim');
			$params['village_personnel_percent'] = $this->request->param('village_personnel_percent',0,'trim');
			$params['artisan'] = $this->request->param('artisan',[],'trim');
			$params['technology_percent'] = $this->request->param('technology_percent',0,'trim');
			if($params['name'] == '' || $params['manager_uid'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingTeamService())->teamManagementAdd($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 解散团队（需为成员选择新团队）
	public function teamManagementDiss()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['team_id'] = $this->request->param('team_id',0,'trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingTeamService())->teamManagementDiss($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息
	public function teamManagementBasic()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingTeamService())->teamManagementBasic($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 编辑团队
	public function teamManagementEdit()
	{
		Db::startTrans();
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['name'] = $this->request->param('name','','trim');
			$params['area'] = $this->request->param('area',[],'trim');
			$params['area_uid'] = $this->request->param('area_uid',0,'trim');
			$params['manager_uid'] = $this->request->param('manager_uid',0,'trim');
			$params['personnel_percent'] = $this->request->param('personnel_percent',0,'trim');
			$params['village_personnel_percent'] = $this->request->param('village_personnel_percent',0,'trim');
			$params['manager_percent'] = $this->request->param('manager_percent',0,'trim');
			$params['village_manager_percent'] = $this->request->param('village_manager_percent',0,'trim');
			$params['artisan'] = $this->request->param('artisan',[],'trim');
			$params['technology_percent'] = $this->request->param('technology_percent',0,'trim');
			if($params['id'] < 1){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingTeamService())->teamManagementEdit($params);
			Db::commit();
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
			Db::rollback();
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息提成编辑批量
	public function teamMemberEdit()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['personnel_percent'] = $this->request->param('personnel_percent',0,'trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingPersonService())->teamMemberEdit($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息提成编辑单条
	public function teamMemberFindEdit()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['personnel_percent'] = $this->request->param('personnel_percent',0,'trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingPersonService())->teamMemberFindEdit($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息用户验证
	public function teamMemberCode()
	{
		try {
			$params['uid'] = $this->request->param('uid',0,'trim');
			if($params['uid'] == 0){
				return api_output(0,['uid'=>''],"success");
			}
			$arr = (new MarketingPersonService())->teamMemberCode($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息成员操作
	public function teamMemberAdd()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['team_id'] = $this->request->param('team_id',0,'trim');
			$params['name'] = $this->request->param('name','','trim');
			$params['uid'] = $this->request->param('uid',0,'trim');
			$params['phone'] = $this->request->param('phone','','trim');
			$params['shop_percent'] = $this->request->param('shop_percent',0,'trim');
			$params['village_percent'] = $this->request->param('village_percent',0,'trim');
			$params['note'] = $this->request->param('note','','trim');
			if($params['id'] > 0){
				if($params['name'] == '' || $params['uid'] == 0){
					return api_output_error(1003, "缺少参数");
				}
			}else{
				if($params['team_id'] == '' || $params['name'] == '' || $params['uid'] == 0 || $params['phone'] == ''){
					return api_output_error(1003, "缺少参数");
				}
			}
			$arr = (new MarketingPersonService())->teamMemberAdd($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息成员信息
	public function teamMembeInfo()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingPersonService())->teamMembeInfo($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息更换团队（当前业务归零，重新计算）
	public function teamManagementReplace()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['team_id'] = $this->request->param('team_id',0,'trim');
			$arr = (new MarketingPersonService())->teamManagementReplace($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息升级（业务员升级业务经理）
	public function teamManagementUpgrade()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['area'] = $this->request->param('area',[],'trim');
			$params['note'] = $this->request->param('note','','trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingPersonService())->teamManagementUpgrade($params);
			if($arr == false){
				return api_output_error(1003, "该成员已是业务经理");
			}
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息移除
	public function teamManagementDel()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingPersonService())->teamManagementDel($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队基本信息业务转移
	public function teamManagementTransfer()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['person_id'] = $this->request->param('per_id',0,'trim');
			if($params['id'] == 0){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingPersonService())->teamManagementTransfer($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 团队业绩详情列表
	public function teamManagementSavage()
	{
		try {
			$team_id = $this->request->param('team_id',0,'trim');
			$page = $this->request->param('page',1,'trim');
			$pageSize = $this->request->param('pageSize',10,'trim');
			$person_id = $this->request->param('person_id',0,'trim');
			$name = $this->request->param('name','','trim');
			$type = $this->request->param('type',-1,'trim');
			$order_business = $this->request->param('order_business',-1,'trim');
			$begin_time = $this->request->param('begin_time','','trim');
			$end_time = $this->request->param('end_time','','trim');
			if($team_id < 1){
				return api_output_error(1003, "缺少参数");
			}
			// 条件
			$where = [];
			if($team_id > 0){
				$where[] = ['t.team_id','=',$team_id];
			}
			// 业务员id
			if($person_id > 0){
				$where[] = ['a.person_id', '=', $person_id];
			}
			// 商家名称
			if($name != ''){
				$where[] = ['c.name', 'like', '%'.$name.'%'];
			}
			// 状态类型
			if($type > -1){
				$where[] = ['g.order_type', '=', $type];
			}
			// 订单业务
			if($order_business > -1){
				$where[] = ['t.order_type', '=', $order_business];
			}
			// 下单时间
			if($begin_time != '' && $end_time != ''){
				$arr = [['g.add_time', '>=', strtotime($begin_time.' 00:00:00')], ['g.add_time', '<=', strtotime($end_time.' 23:59:59')]];
				$where = array_merge($where, $arr);
			}
			// 字段
			$field = 'g.*, b.name as per_name, c.name as mer_name, t.order_type as order_business';
			// 条件
			$order = 'g.order_id DESC';
			$arr = (new MarketingOrderService())->teamManagementSavage($where, $field, $order, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 店铺订单详情
	public function teamManagementSavageDetail()
	{
		try {
			$params['order_id'] = $this->request->param('order_id',0,'trim');
			if($params['order_id'] < 1){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingOrderService())->teamManagementSavageDetail($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 社区订单详情
	public function getCommunityOrderDetail()
	{
		try {
			$params['order_id'] = $this->request->param('order_id',0,'trim');
			if($params['order_id'] < 1){
				return api_output_error(1003, "缺少参数");
			}
			$where[] = ['order_id','=',$params['order_id']];
			$arr = (new PackageOrderService())->getPackageOrderInfo($where);
			if(!$arr){
				return api_output_error(1003, "社区订单不存在");
			}
			$list = (new MarketingOrderService())->getCommunityOrderDetail($params);
			$list['order_id'] = $arr['order_id'];
			$list['orderid'] = $arr['order_no'];
			$list['electronic'] = '';
			$list['mer_name'] = $arr['property_name'];
			$list['mer_id'] = $arr['property_id'];
			$list['order_type_status'] = '新订单';
			$list['order_business_status'] = '社区';
			$list['pay_time'] = $arr['pay_time'];
			$list['pay_type'] = $arr['pay_type'];
			$list['total_price'] = $arr['order_money'];
			$list['mer_phone'] = $arr['property_tel'];
			$list['mer_address'] = '';
			$list['shop_num'] = $arr['num'];
			$list['transaction_no'] = $arr['transaction_no'];
			$list['years'] = $arr['package_period'];
			$list['pack_name'] = $arr['package_title'];
			$list['package_end_time'] = $arr['package_end_time'];

			$list['total_num'] = $arr['num'] ;
			$list['pack_detail'] = [];
			if($arr['details_info']){
				$total_num = $arr['num'] * $arr['details_info']['num'];
				$list['total_num'] = $total_num;
				$list['pack_detail'] = array(
					'name' => $arr['details_info']['package_title'],
					'num' => $arr['details_info']['num'],
					'price' => $arr['details_info']['price'],
					'room_num' => $arr['details_info']['room_num'],
				);
			}
			
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 注册商家列表
	public function teamManagementMerchantList()
	{
		try {
			$team_id = $this->request->param('team_id',0,'trim');
			$page = $this->request->param('page',1,'trim');
			$pageSize = $this->request->param('pageSize',10,'trim');
			$person_id = $this->request->param('person_id',0,'trim');
			$name = $this->request->param('name','','trim');
			$begin_time = $this->request->param('begin_time','','trim');
			$end_time = $this->request->param('end_time','','trim');
			if($team_id < 1){
				return api_output_error(1003, "缺少参数");
			}
			// 条件
			$where[] = ['g.status','=',0];
			$where[] = ['g.team_id','=',$team_id];
			// 业务员id
			if($person_id > 0){
				$where[] = ['g.person_id', '=', $person_id];
			}
			// 商家名称
			if($name != ''){
				$where[] = ['c.name', 'like', '%'.$name.'%'];
			}
			// 下单时间
			if($begin_time != '' && $end_time != ''){
				$arr = [['g.add_time', '>=', strtotime($begin_time.' 00:00:00')], ['g.add_time', '<=', strtotime($end_time.' 23:59:59')]];
				$where = array_merge($where, $arr);
			}
			// 字段
			$field = 'g.*, b.name as per_name, c.name as mer_name';
			// 条件
			$order = 'g.id DESC';
			$arr = (new MarketingPersonMerService())->teamManagementMerchantList($where, $field, $order, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 商家业务转移团队成员列表
	public function teamManagementMerchantTransferList()
	{
		try {
			$params['team_id'] = $this->request->param('team_id',0,'trim');
			$arr = (new MarketingPersonMerService())->teamManagementMerchantTransferList($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 商家业务转移操作
	public function teamManagementMerchantTransferCreate()
	{
		try {
			$params['id'] = $this->request->param('id',0,'trim');
			$params['team_id'] = $this->request->param('team_id',0,'trim');
			$params['person_id'] = $this->request->param('per_id',0,'trim');
			if($params['team_id'] < 1 || $params['person_id'] < 1 || $params['id'] < 1){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingPersonMerService())->teamManagementMerchantTransferCreate($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 商家订单信息
	public function teamMerchantOrderList()
	{
		try {
			$mer_id = $this->request->param('mer_id',0,'trim');
			$page = $this->request->param('page',1,'trim');
			$pageSize = $this->request->param('pageSize',10,'trim');
			$person_id = $this->request->param('person_id',0,'trim');
			$name = $this->request->param('name','','trim');
			$type = $this->request->param('type',-1,'trim');
			$order_business = $this->request->param('order_business',-1,'trim');
			$begin_time = $this->request->param('begin_time','','trim');
			$end_time = $this->request->param('end_time','','trim');
			if($mer_id < 1){
				return api_output_error(1003, "缺少参数");
			}
			// 条件
			$where[] = ['g.mer_id','=',$mer_id];
			$where = [];
			// 业务员id
			if($person_id > 0){
				$where[] = ['a.person_id', '=', $person_id];
			}
			// 商家名称
			if($name != ''){
				$where[] = ['c.name', 'like', '%'.$name.'%'];
			}
			// 状态类型
			if($type > -1){
				$where[] = ['g.order_type', '=', $type];
			}
			// 订单业务
			if($order_business > -1){
				$where[] = ['t.order_type', '=', $order_business];
			}
			// 下单时间
			if($begin_time != '' && $end_time != ''){
				$arr = [['g.add_time', '>=', strtotime($begin_time.' 00:00:00')], ['g.add_time', '<=', strtotime($end_time.' 23:59:59')]];
				$where = array_merge($where, $arr);
			}
			// 字段
			$field = 'g.*, b.name as per_name, c.name as mer_name, t.order_type as order_business';
			// 条件
			$order = 'g.order_id DESC';
			$arr = (new MarketingOrderService())->teamManagementSavage($where, $field, $order, $page, $pageSize);
			$mer = (new MerchantService())->getInfo($mer_id);
			$storeCount = (new MerchantStoreService())->getCount($mer_id);
			$area = (new AreaService())->getAreaString([['area_id','in',[$mer['province_id'], $mer['city_id'], $mer['area_id']]]],'area_name');
			$addre = '';
			foreach($area as $v){
				$addre = $addre.$v['area_name'];
			}
			$address = $addre.$mer['address'];
			if($mer){
				$arr['basic'] = array(
					'mer_id' => $mer['mer_id'],
					'name' => $mer['name'],
					'reg_time' => date('Y-m-d H:i:s',$mer['reg_time']),
					'store_num' => $storeCount,
					'phone' => $mer['phone'],
					'address' => $address,
				);
			}else{
				$arr['basic'] = [];
			}
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}
}