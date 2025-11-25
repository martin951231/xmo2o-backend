<?php
/**
 * 标签打印机硬件设置
 **/
class Label_printerAction extends BaseAction
{
	public function index()
	{
	    $where = array('mer_id' => $this->merchant_session['mer_id']);
	    if ($this->merchant_session['store_id']) {
	        $where['store_id'] = $this->merchant_session['store_id'];
	    }
		$list = D('Label_printer')->field(true)->where($where)->select();
		$stores = D('Merchant_store')->field('store_id, name')->where($where)->select();
		$tmp = array();
		foreach ($stores as $s) {
			$tmp[$s['store_id']] = $s;
		}
		foreach ($list as &$o) {
			$o['store_name'] = isset($tmp[$o['store_id']]['name']) ? $tmp[$o['store_id']]['name'] : '';
		}
		$this->assign('list', $list);
		$this->display();		
	}
	
	public function addprint()
	{
		$pigcms_id = isset($_REQUEST['pigcms_id']) ? intval($_REQUEST['pigcms_id']) : 0;

		if($pigcms_id){
			// 打印机详情
			$orderprint = D('Label_printer')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->find();

			// 打印机绑定的商品
			$goods_list = M('Label_printer_bind_goods')->where(['pring_id' => $pigcms_id])->select();
			if($goods_list){
				$goods_list = M('Shop_goods')->field('goods_id,name,price')->where(['goods_id'=>['in',array_column($goods_list, 'goods_id')]])->select();
			}
			$this->assign('goods_list',$goods_list);

		}
		if (IS_POST) {
		    if ($this->merchant_session['store_id']) {
	          	$_POST['store_id'] = $this->merchant_session['store_id'];
	      	}
			$data['print_type'] = isset($_POST['print_type']) ? intval($_POST['print_type']) : 0;
			$data['data_type'] = isset($_POST['data_type']) ? intval($_POST['data_type']) : 0;
			$data['feie_user'] = isset($_POST['feie_user']) ? $_POST['feie_user'] : '';
			$data['feie_ukey'] = isset($_POST['feie_ukey']) ? $_POST['feie_ukey'] : '';
			$data['store_id'] = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
			$data['name'] = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
			$data['mcode'] = isset($_POST['mcode']) ? htmlspecialchars(trim($_POST['mcode'])) : '';
			$data['mkey'] = isset($_POST['mkey']) ? htmlspecialchars(trim($_POST['mkey'])) : '';
			$data['count'] = 1;
			$data['mer_id'] = intval($this->merchant_session['mer_id']);
			$data['add_time'] = time();


			if ($orderprint) {
				D('Label_printer')->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->save($data);
			} else {
				$pigcms_id = D('Label_printer')->add($data);
			}

			// 保存绑定的商品
			if($_POST['goods_id'] && $pigcms_id){

				M('Label_printer_bind_goods')->where(['pring_id' => $pigcms_id])->delete();
				foreach ($_POST['goods_id'] as $key => $value) {
					$goods_data[] = [
						'pring_id' => $pigcms_id,
						'goods_id' => $value,
					];
				}
				M('Label_printer_bind_goods')->addAll($goods_data);
			}
			$this->success(L_('操作成功'), U('index'));
		} else {
		    $where = array('mer_id' => $this->merchant_session['mer_id'], 'status' => 1);
		    if ($this->merchant_session['store_id']) {
		        $where['store_id'] = $this->merchant_session['store_id'];
		    }
	        $stores = D('Merchant_store')->field('store_id, name')->where($where)->select();
			
			$this->assign('orderprint', $orderprint);
			$this->assign('stores', $stores);
			$this->display();
		}
	}
	
	public function delprint()
	{
		$pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
		if ($orderprint = D('Label_printer')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->find()) {
			fdump($orderprint,'api/log/printer_auto_del_log',true);
			D('Label_printer')->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->delete();
			$this->success(L_('打印机删除设置成功'), U('Label_printer/index'));
		} else {
			$this->error(L_('不合法的参数请求'), U('Label_printer/index'));
		}
	}
}
?>