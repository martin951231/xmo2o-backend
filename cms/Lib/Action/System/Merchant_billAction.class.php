<?php
/*
 * 商家对账
 * @  BuildTime  2021/10/09 11:36
 */
class Merchant_billAction extends BaseAction{

    public function index(){
		$database_merchant = D('Merchant');

		// 管理员区域
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				$area_index = 'area_id';
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$this->assign('admin_area',$now_area['area_type']);
			$condition_merchant[$area_index] = $this->system_session['area_id'];
		}


		// 商家名称
		$condition_merchant['name'] = array('like','%'.trim($_GET['keyword']).'%');

		// 查询时间
		if($_GET['search_month']){
			$month = $_GET['search_month'];
		}else{
			$_GET['search_month'] = $month = date('Y-m');
		}

		// 时间
		$firstday = $month.'-01';
   		$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));

		// 商家状态
		$condition_merchant['status'] = array('lt', 4);//4：假删除状态

		$order = 'mer_id DESC';

		$count_merchant = $database_merchant->where($condition_merchant)->count();

		import('@.ORG.system_page');
		$p = new Page($count_merchant,15);
		$merchant_list = $database_merchant->field(true)->where($condition_merchant)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		foreach ($merchant_list as &$v) {
			$mer_id = $v['mer_id'];
			// 	总销售额
			$condition = [];
			$condition['mer_id'] = $mer_id; // 商家id
			$condition['type'] = ['not in',['gift']];
			$condition['paid'] = 1;
			$condition['create_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];
			$v['total_price'] = M('System_order')->where($condition)->sum('price') ?: 0;

			// 总佣金
			$condition = [];
			$condition['mer_id'] = $mer_id; // 商家id
			$condition['add_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];
			$v['spread_money'] = M('merchant_spread_list')->where($condition)->sum('money') ?: 0;

			// 总退款
			$v['refund_money'] = D('System_order')->getRefundMoney($mer_id, $firstday, $lastday);

			// 总提现
			$condition = [];
			$condition['mer_id'] = $mer_id; // 商家id
			$condition['status'] = 1; // 已提现
			$condition['withdraw_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];
			$withdraw = M('Merchant_withdraw')->where($condition)->sum('old_money');
			$v['withdraw_money'] = $withdraw/100;


			// 总抽成
			$condition = [];
			$condition['mer_id'] = $mer_id; // 商家id
			$condition['use_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];
			$system_take = D('Merchant_money_list')->where($condition)->sum('system_take');
			$v['system_take'] =  getFormatNumber($system_take);

			// 平台余额
			$condition = [];
			$condition['mer_id'] = $mer_id; // 商家id
			$condition['income'] = 1; // 进
			$condition['use_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];
			$income_money = D('Merchant_money_list')->where($condition)->sum('money');
			$condition['income'] = 2; // 出
			$spending_money = D('Merchant_money_list')->where($condition)->sum('money');
			$v['income_money'] = getFormatNumber($income_money - $spending_money);
		}

		$this->assign('merchant_list',$merchant_list);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		$this->display();
    }

    // 对账订单列表
    public function order_list(){

		// 查询时间
		if($_GET['search_month']){
			$month = $_GET['search_month'];
		}

		if($_GET['mer_id']){
			$mer_id = $_GET['mer_id'];
		}

		if(empty($month) || !$mer_id){
			$this->error('缺少参数');
		}

		$list = D('System_order')->getMerchantBillList($_GET);

		$this->assign($list);
		$this->display();

    }


    // 对账订单列表
    public function refund_order_list(){

		// 查询时间
		if($_GET['search_month']){
			$month = $_GET['search_month'];
		}

		if($_GET['mer_id']){
			$mer_id = $_GET['mer_id'];
		}

		if(empty($month) || !$mer_id){
			$this->error('缺少参数');
		}



		// 时间
		$firstday = $month.'-01';
   		$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));

		$order = 'id desc';


		$list = D('System_order')->getRefundOrderList($mer_id, $firstday, $lastday);

		$this->assign($list);
		$this->display();

    }


    public function merchant_money_list(){

		// 查询时间
		if($_GET['search_month']){
			$month = $_GET['search_month'];
		}

		if($_GET['mer_id']){
			$mer_id = $_GET['mer_id'];
		}

		if(empty($month) || !$mer_id){
			$this->error('缺少参数');
		}

		if($_GET['type']){
			$type = $_GET['type'];
		}


		// 时间
		$firstday = $month.'-01';
   		$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));


		// 商家id
		$condition['mer_id'] = $mer_id;
		$condition['use_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];

		if($type){
			if($type == 'system_take'){
				$condition['system_take'] = ['gt', 0];
			}else{
				$condition['type'] = $type;
			}
		}

		$order = 'id desc';

		$pageSize = 15;
    	import('@.ORG.system_page');
		$count = D('Merchant_money_list')->where($condition)->count();

		$p = new Page($count,$pageSize);
		$list = D('Merchant_money_list')->where($condition)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$this->assign('list', $list);
		$this->assign('pagebar', $p->show());
		$this->display();

    }




    public function merchant_spread_list(){

		// 查询时间
		if($_GET['search_month']){
			$month = $_GET['search_month'];
		}

		if($_GET['mer_id']){
			$mer_id = $_GET['mer_id'];
		}

		if(empty($month) || !$mer_id){
			$this->error('缺少参数');
		}

		if($_GET['type']){
			$type = $_GET['type'];
		}


		// 时间
		$firstday = $month.'-01';
   		$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));


		// 商家id
		$condition['mer_id'] = $mer_id;
		$condition['add_time'] = ['between',[strtotime($firstday),strtotime($lastday)]];

		if($type){
			$condition['type'] = $type;
		}

		$order = 'id desc';

		$pageSize = 15;
    	import('@.ORG.system_page');
		$count = D('Merchant_spread_list')->where($condition)->count();

		$p = new Page($count,$pageSize);
		$list = D('Merchant_spread_list')->where($condition)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		$this->assign('list', $list);
		$this->assign('pagebar', $p->show());
		$this->display();

    }

    // 执行对账
    public function doBill(){
    	$orderIds = $_POST['order_ids'];

    	if(!$orderIds){
    		$this->error('请选择对账的订单');
    	}

    	$res = M('System_order')->where(['id' =>['in', $orderIds]])->setField('is_bill',1);
    	if($res === false){
    		$this->error('操作失败，请稍后重试');
    	}

    	$this->success('操作成功');
    }

    // 导出对账详情
    public function export(){
        $param = $_GET;

        $param['type'] = 'merchant_bill';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];

        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
    }
}
?>