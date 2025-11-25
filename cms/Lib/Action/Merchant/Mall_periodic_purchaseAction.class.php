<?php
/**
 * Created by PigCMS.
 * User: jjc.
 * Date: 2019/6/14.
 * Time: 14:17.
 */

/**
 * Class Mall_periodic_purchase 商城周期购模块功能
 */
class Mall_periodic_purchaseAction extends BaseAction
{

	// 周期购管理
	public function periodic_purchase_index(){

		$store_id=I('store_id','','intval');
		$store_id = $store_id?$store_id:$_POST['store_id'];
		$where = array();
		$keyword=I('keyword','','trim');
		if ($keyword) {
			$where['gd.name'] = array('like','%'.$keyword.'%');
		}
		$where['gd.store_id'] = $store_id;
		$where['p.status'] = 0;
		$count=M('mall_periodic_purchase')->alias('p')->join(C('DB_PREFIX').'shop_goods gd on p.goods_id=gd.goods_id')->where($where)->count();

		import('@.ORG.merchant_page');
		$page= new Page($count,30);
		$list=M('mall_periodic_purchase')->alias('p')->join(C('DB_PREFIX').'shop_goods gd on p.goods_id=gd.goods_id')->field('p.*,gd.sell_day,gd.original_stock,gd.name,gd.price,gd.stock_num,gd.sell_count')->where($where)->limit($page->firstRow,$page->listRows)->select();
		$list = $this->periodic_deal_list($list);
		
		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//删除商品周期购
	public function periodic_del(){
		$id=I('id','','intval');
		$r = M('Mall_periodic_purchase')->where(array('id'=>$id))->data(array('status'=>1))->save();
		if($r){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	public function periodic_deal_list($data){
		if($data){
			foreach ($data as $key => $val) {
				switch ($val['periodic_type']) {
					case '1':
						$data[$key]['periodic_text'] = '每日一期，共'.$val['periodic_count'].'期';
						$data[$key]['periodic_data_text'] = '每天';
						break;
					case '2':
						$data[$key]['periodic_text'] = '每周一期，共'.$val['periodic_count'].'期';
						$week = array('周日','周一','周二','周三','周四','周五','周六');
						$periodic_date = explode(',', $val['periodic_date']);
						$show_data = array();
						foreach ($periodic_date as $v) {
							$show_data[] = $week[$v];
						}
						$data[$key]['periodic_data_text'] = implode(',', $show_data);
						break;
					case '3':
						$data[$key]['periodic_text'] = '每月一期，共'.$val['periodic_count'].'期';
						$periodic_date = explode(',', $val['periodic_date']);
						$show_data = array();
						foreach ($periodic_date as $v) {
							$show_data[] = $v.'号';
						}
						$data[$key]['periodic_data_text'] = implode(',', $show_data);
						break;
					default:
						$data[$key]['periodic_text'] = '未知';
						$data[$key]['periodic_data_text'] = '未知';
						break;
				}
				$data[$key]['deliver_text'] = '需提前'.$val['pre_delivery_day'].'天，并在'.$val['pre_delivery_time'].'时之前下单';
				$data[$key]['update_time'] = date('Y-m-d h:i:s',$val['update_time']);
			}
		}
		return $data;
	}

	// 新增周期购
	public function periodic_add(){

		if(IS_POST){
			
			$model = D('Mall_periodic_purchase');
			$set = $model->get_goods_one($_POST['goods_id']);
			if($set){
				$this->error('该商品已设置成周期购，无需再添加！');exit;
			}
			$_POST['periodic_date'] = implode(',', $_POST['periodic_date']);
			$r = $model->edit($_POST);
			if($r){
				$this->success('添加成功');exit;
			}else{
				$this->error('添加失败');exit;
			}
		}
		$this->display();
	}    

	//编辑周期购
	public function periodic_edit(){
		$id=I('id','','intval');
		$model = D('Mall_periodic_purchase');
		$info = $model->get_one($id);
		if(IS_POST){
			$model = D('Mall_periodic_purchase');
			$_POST['periodic_date'] = implode(',', $_POST['periodic_date']);
			$r = $model->edit($_POST,$id);
			if($r){
				$this->success('编辑成功');exit;
			}else{
				$this->error('编辑失败');exit;
			}
		}
		$info = $model->get_one($id);
		$info['periodic_date'] = json_encode(explode(',', $info['periodic_date']));
		$this->assign('info',$info);
		$this->display('periodic_add');
	}

   

	    
	





}
