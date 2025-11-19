<?php
/*
 * 自有打印机
 *
 * 
 */
class OwnprintAction extends BaseAction{
	public function index(){
		M('Orderprint_list')->where(['print_time'=>'0' , 'add_time'=> ['lt',strtotime(date('Y-m-d'))]])->data(['print_time'=>'1'])->save();
		
		$orderprint_count = M('Orderprint_list')->where(['add_time'=> ['egt', strtotime(date('Y-m-d'))]])->count();
		$this->assign('orderprint_count', $orderprint_count);
		
		$orderprint_list = M('Orderprint_list')->where(['print_time'=>'0', 'add_time'=> ['lt', time()-10]])->order('`add_time` DESC')->select();
		foreach($orderprint_list as &$value){
			$value['staff'] = M('Merchant_store_staff')->where(['device_id' => $value['mkey']])->find();
			if($value['staff']){
				$value['staff']['city_name'] = M('Area')->where(['area_id' => M('Merchant_store')->where(['store_id' => $value['staff']['store_id']])->getField('city_id')])->getField('area_name');
			}
		}
		
		$this->assign('orderprint_list', $orderprint_list);
		$this->display();
	}
	public function del(){
		M('Orderprint_list')->where(['print_id'=>$_GET['print_id']])->delete();
		
		redirect(U('index'));
	}
}