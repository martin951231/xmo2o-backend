<?php
/**
 * 硬件设置
 **/
class HardwareAction extends BaseAction
{
	public function index()
	{
	    $where = array('mer_id' => $this->merchant_session['mer_id']);
	    if ($this->merchant_session['store_id']) {
	        $where['store_id'] = $this->merchant_session['store_id'];
	    }
		$list = D('Orderprinter')->field(true)->where($where)->select();
		$stores = D('Merchant_store')->field('store_id, name')->where($where)->select();
		$tmp = array();
		foreach ($stores as $s) {
			$tmp[$s['store_id']] = $s;
		}
		$names = array(L_('下单成功后打印'), L_('支付成功后打印'), L_('验证消费后打印'), L_('退款成功后打印'), L_('打印总单'), L_('接单成功后打印（快店）'),L_('自动打印预结账单（用户或店员下单成功后）----- 餐饮行业'), L_('自动打印结账单（用户支付成功后，含店员收款成功后）----- 餐饮行业'));
		foreach ($list as &$o) {
			if ($o['is_main']) {
				$o['name'] .= L_('(主打印机)');
			} else {
				$o['name'] = $o['name'] ? $o['name'] : L_('打印机').'-' . $o['pigcms_id'];
			}
			$o['str'] = '';
			if ($o['paid'] != '') {
				$o['paid'] = explode(',', $o['paid']);
				foreach ($o['paid'] as $id) {
					$names[$id]&&$o['str'] .= L_('【') . $names[$id] . L_('】');
				}
			}
			
			$o['store_name'] = isset($tmp[$o['store_id']]['name']) ? $tmp[$o['store_id']]['name'] : '';
		}
		$this->assign('list', $list);
		$this->display();		
	}
	
	public function addprint()
	{
		$pigcms_id = isset($_REQUEST['pigcms_id']) ? intval($_REQUEST['pigcms_id']) : 0;
		$pigcms_id && $orderprint = D('Orderprinter')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->find();
		if (IS_POST) {
		    if ($this->merchant_session['store_id']) {
	          	$_POST['store_id'] = $this->merchant_session['store_id'];
	      	}
			$data['print_type'] = isset($_POST['print_type']) ? intval($_POST['print_type']) : 0;
			$data['print_way'] = isset($_POST['print_way']) ? intval($_POST['print_way']) : 1;
			$data['is_print_menu'] = intval($_POST['is_print_menu']);
			$data['feie_ukey'] = isset($_POST['feie_ukey']) ? trim($_POST['feie_ukey']) : '';
			$data['feie_user'] = isset($_POST['feie_user']) ? trim($_POST['feie_user']) : '';
			$data['language'] = isset($_POST['language']) ? trim($_POST['language']) : '';
			$data['store_id'] = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
			$data['username'] = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
			$data['name'] = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
			$data['mp'] = isset($_POST['mp']) ? htmlspecialchars(trim($_POST['mp'])) : '';
			$data['mcode'] = isset($_POST['mcode']) ? htmlspecialchars(trim($_POST['mcode'])) : '';
			$data['mkey'] = isset($_POST['mkey']) ? htmlspecialchars(trim($_POST['mkey'])) : '';
			$data['qrcode'] = isset($_POST['qrcode']) ? trim($_POST['qrcode']) : '';
			$data['count'] = isset($_POST['count']) ? intval($_POST['count']) : 1;
			$data['is_main'] = isset($_POST['is_main']) ? intval($_POST['is_main']) : 0;
			$data['image'] = isset($_POST['image']) ? intval($_POST['image']) : 0;
			$data['paper'] = isset($_POST['paper']) ? intval($_POST['paper']) : 0;
			$data['is_big'] = isset($_POST['is_big']) ? intval($_POST['is_big']) : 0;
			$data['version'] = isset($_POST['version']) ? intval($_POST['version']) : '';
			$data['is_print_report'] = isset($_POST['is_print_report']) ? intval($_POST['is_print_report']) : 0;
			$data['paid'] = isset($_POST['paid']) ? $_POST['paid'] : '';
			$data['mall_print'] = isset($_POST['mall_print']) ? $_POST['mall_print'] : '';
			$data['store_print'] = isset($_POST['store_print']) ? $_POST['store_print'] : '';
			$data['mer_id'] = intval($this->merchant_session['mer_id']);
			$data['add_time'] = time();
			$data['shop_offline_order_print'] = intval($_POST['shop_offline_order_print']);
			$data['staff_uids'] = $_POST['staff_uids']?implode(',',$_POST['staff_uids']):'';
			$data['trenditiot_appid'] = isset($_POST['trenditiot_appid']) ? trim($_POST['trenditiot_appid']) : '';
			$data['trenditiot_appkey'] = isset($_POST['trenditiot_appkey']) ? trim($_POST['trenditiot_appkey']) : '';

            //预订单打印分类
            $bookTypes = $_POST['booking_order_print_arr'] ?? [];
            $data['booking_order_print'] = 0;
            if ($bookTypes) {
                foreach ($bookTypes as $v) {
                    $data['booking_order_print'] |= $v;
                }
            }

			if ($data['count'] < 1 || $data['count'] > 100) {
			    $this->error(L_('份数在1~100之间的数字'));
			}
			if ($data['paid']) $data['paid'] = implode(',', $data['paid']);
			if ($data['mall_print']) $data['mall_print'] = implode(',', $data['mall_print']);
			if ($data['store_print']) $data['store_print'] = implode(',', $data['store_print']);
			//if ($tobj = D('Orderprinter')->field(true)->where("`mer_id`='{$this->merchant_session['mer_id']}' AND `store_id`='{$data['store_id']}' AND `pigcms_id`<>'{$pigcms_id}'")->find()) $this->error('该店铺已经添加了打印机');
			if ($orderprint) {
				D('Orderprinter')->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->save($data);
			} else {
				D('Orderprinter')->add($data);
			}
			$this->success(L_('操作成功'), U('Hardware/index'));
		} else {
		    $where = array('mer_id' => $this->merchant_session['mer_id'], 'status' => 1);
		    if ($this->merchant_session['store_id']) {
		        $where['store_id'] = $this->merchant_session['store_id'];
		    }
	        $stores = D('Merchant_store')->field('store_id, name')->where($where)->select();
			
			$orderprint['paid'] != '' && $orderprint['paid'] = explode(',', $orderprint['paid']);
			$orderprint['mall_print'] != '' && $orderprint['mall_print'] = explode(',', $orderprint['mall_print']);
			$orderprint['store_print'] != '' && $orderprint['store_print'] = explode(',', $orderprint['store_print']);
			$this->assign('orderprint', $orderprint);
			$this->assign('stores', $stores);
            $this->assign('shop_offline_staff_print', (int)(C('customization.shop_offline_staff_print')?:0));
			$this->display();
		}
	}
	
	public function print_status()
	{
		$pigcms_id = intval($_POST['id']);
		$database = D('Orderprinter');
		if (!($orderprint = $database->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->find())){
			exit('0');
		}
		
		$condition['pigcms_id'] = $pigcms_id;
		$data['is_main'] = $_POST['type'] == 'open' ? 1 : 0;
		if($database->where($condition)->data($data)->save()){
			exit('1');
		}else{
			exit;
		}
	}
	
	public function delprint()
	{
		$pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
		if ($orderprint = D('Orderprinter')->field(true)->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->find()) {
			fdump($orderprint,'api/log/printer_auto_del_log',true);
			D('Orderprinter')->where(array('mer_id' => $this->merchant_session['mer_id'], 'pigcms_id' => $pigcms_id))->delete();
			$this->success(L_('打印机删除设置成功'), U('Hardware/index'));
		} else {
			$this->error(L_('不合法的参数请求'), U('Hardware/index'));
		}
	}
	
	/* 微信WIFI */
	public function wifi(){
		$this->display();
	}

    /**
     * 获取店铺店员列表
     */
	public function getStaffList(){
        $id = intval($_POST['id']);
        $store_id = intval($_POST['store_id']);
        $return_arr = [
            'status' => 1,
            'data' => [],
        ];
        if(!$id&&!$store_id){
            return $return_arr;
        }
        if(!$store_id){
            return $return_arr; 
        }
        $staff_user = D('Merchant_store_staff')->field('id,name')->where(['store_id'=>$store_id])->select();
        $staff_uids = [];
        if($id){
            $print = D('orderprinter')->where(['pigcms_id'=>$id])->find();
            if($print){
                $staff_uids = $print['staff_uids']?explode(',',$print['staff_uids']):[];
            }
        }
        if($staff_uids){
            foreach ($staff_user as &$item){
                $item['is_check'] = in_array($item['id'],$staff_uids)?1:0;
            }
        }
        $return_arr['data'] = $staff_user;
        $return_arr['status'] = 0;
        exit(json_encode($return_arr));
    }
}
?>