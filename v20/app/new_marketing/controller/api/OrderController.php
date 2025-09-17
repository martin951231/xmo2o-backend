<?php
namespace app\new_marketing\controller\api;

use app\new_marketing\model\service\MarketingOrderService;
use app\community\model\service\PackageOrderService;

class OrderController extends ApiBaseController
{
	// 物业订单列表
    public function getPropertyOrderList(){
        $params['uid'] = $this->request->log_uid ?? 0;
		// $params['uid'] = '18';
        if($params['uid'] < 1){
            return api_output_error(1002,"获取用户信息失败,请重新登录");
        }
		$params['team_id'] = $this->request->param('team_id', 0, 'trim');
		$params['person_id'] = $this->request->param('person_id', 0, 'trim');
		$params['start_time'] = $this->request->param('start_time', '', 'trim');
		$params['end_time'] = $this->request->param('end_time', '', 'trim');
		$params['page'] = $this->request->param('page', 1, 'intval');
		$params['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $msg = (new MarketingOrderService())->getPropertyOrderList($params);
        return api_output(0, $msg, '获取成功');
    }

	// 店铺订单列表
    public function getShopOrderList(){
        $params['uid'] = $this->request->log_uid ?? 0;
		// $params['uid'] = '18';
        if($params['uid'] < 1){
            return api_output_error(1002,"获取用户信息失败,请重新登录");
        }
		$params['team_id'] = $this->request->param('team_id', 0, 'trim');
		$params['person_id'] = $this->request->param('person_id', 0, 'trim');
		$params['start_time'] = $this->request->param('start_time', '', 'trim');
		$params['end_time'] = $this->request->param('end_time', '', 'trim');
		$params['page'] = $this->request->param('page', 1, 'intval');
		$params['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $msg=(new MarketingOrderService())->getShopOrderList($params);
        return api_output(0, $msg, '获取成功');
    }

	// 社区订单详情
	public function getPropertyOrderDetail()
	{
		try {
			$params['order_id'] = $this->request->param('order_id',0,'trim');
			if($params['order_id'] < 1){
				return api_output_error(1003, "缺少参数");
			}
			$where[] = ['order_id','=',$params['order_id']];
			$arr = (new PackageOrderService())->getPackageOrderInfo($where);
			$list = (new MarketingOrderService())->getCommunityOrderDetail($params);
			$list['order_id'] = $arr['order_id'];
			$list['orderid'] = $arr['order_no'];
			$list['property_name'] = $arr['property_name'];
			$list['property_id'] = $arr['property_id'];
			$list['property_phone'] = $arr['property_tel'];
			$list['package_name'] = $arr['package_title'];
			$list['pay_time'] = $arr['pay_time'];
			$list['create_time'] = date('Y-m-d',$arr['create_time']);
			$list['pay_type'] = $arr['pay_type'];
			$list['order_money'] = $arr['order_money'];
			$list['mer_address'] = '';
			$list['shop_num'] = $arr['num'];
			$list['transaction_no'] = $arr['transaction_no'];
			$list['years'] = $arr['package_period'];
			$list['package_end_time'] = $arr['package_end_time'];
			$list['package_detail'] = array(
				'name' => $arr['details_info']['package_title'],
				'num' => $arr['details_info']['num'],
				'price' => $arr['details_info']['price'],
				'room_num' => $arr['details_info']['room_num'],
			);
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	// 店铺订单详情
	public function getShopOrderDetail()
	{
		try {
			$params['order_id'] = $this->request->param('order_id',0,'trim');
			if($params['order_id'] < 1){
				return api_output_error(1003, "缺少参数");
			}
			$arr = (new MarketingOrderService())->teamManagementSavageDetail($params);
			$arr['create_time'] = date('Y-m-d',$arr['add_time']);
			$arr['package_detail'] = $arr['pack_detail'];
			$arr['package_name'] = $arr['pack_name'];
			$arr['order_money'] = $arr['total_price'];
			if($arr['artisan_list']){
				$name = ''; $proportion = 0; $price = 0;
				foreach($arr['artisan_list'] as $k=>$v){
					if($k == 0){
						$name = $v['name'];
					}else{
						$name = $name.'、'.$v['name'];
					}
					$proportion = $v['proportion'];
					$price = $v['price'];
				}
				$arr['artisan_list'] = array(
					'name' => $name,
					'proportion' => $proportion,
					'price' => $price,
				);
			}
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}
}