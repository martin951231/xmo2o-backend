<?php
class Trade_hotelAction extends BaseAction{
	public function index(){
		$condition['mer_id'] = $this->mer_id;
		$condition['cat_fid'] = 0;
		$condition['is_remove'] = 0;
		$cat_list = M('Trade_hotel_category')->where($condition)->order('`cat_sort` DESC,`cat_id` ASC')->select();
		$this->assign('cat_list',$cat_list);
		
		$this->display();
	}
	public function category_add(){
		if(IS_POST){
			if(empty($_POST['cat_name'])){
				$this->assign('error_tips',L_('请填写分类名称'));
			}else{
				$data = $_POST;
				
				$img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
				$rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
			
				foreach($_POST['pic'] as $kp => $vp){
					$tmp_vp = explode(',', $vp);
					$_POST['pic'][$kp] = $rand_num . ',' . $tmp_vp[1];
				}
				$data['cat_pic'] = implode(';', $_POST['pic']) ? : '';
				$data['mer_id']	  = $this->mer_id;
				if(M('Trade_hotel_category')->data($data)->add()){
					unset($_POST);
					$this->assign('ok_tips',L_('添加成功'));
				}else{
					dump(M('Trade_hotel_category'));die;
					$this->assign('error_tips',L_('添加失败，请重试'));
				}
			}
		}
		$this->display();
	}
	public function category_edit(){
		$condition['cat_id'] = $_GET['cat_id'];
		$condition['mer_id'] = $this->mer_id;
			
		if(IS_POST){
			if(empty($_POST['cat_name'])){
				$error_tips = L_('请填写分类名称');
			}else{
				$_POST['cat_pic'] = implode(';', $_POST['pic']) ? : '';
				if(M('Trade_hotel_category')->where($condition)->data($_POST)->save() !== false){
					$ok_tips = L_('编辑成功');
				}else{
					$error_tips = L_('编辑失败，请重试');
				}
			}
		}
		
		if($error_tips){
			$now_cat = $_POST;
			$this->assign('error_tips',$error_tips);
		}else{
			$now_cat = M('Trade_hotel_category')->where($condition)->find();
		}
		
		if($ok_tips){
			$this->assign('ok_tips',$ok_tips);
		}
		$now_cat['pic'] = explode(';',$now_cat['cat_pic']);
		$now_cat['pic_arr'] = $now_cat['cat_pic'] ? explode(';',str_replace(',','/s_',$now_cat['cat_pic'])) : [];
		$this->assign('now_cat',$now_cat);
		$this->display();
	}
	public function category_del(){
		$condition['cat_id'] = $_GET['cat_id'];
		$condition['mer_id'] = $this->mer_id;
		$data['is_remove'] = 1;
		if(M('Trade_hotel_category')->where($condition)->data($data)->save()){
			$condition_son['cat_fid'] = $_GET['cat_id'];
			M('Trade_hotel_category')->where($condition_son)->data($data)->save();
			$this->success(L_('删除成功'));
		}else{
			$this->error(L_('删除失败，请重试'));
		}
	}
	
	
	public function son_cat_list(){
		$condition['cat_id'] = $_GET['cat_id'];
		$condition['mer_id'] = $this->mer_id;
		$now_cat = M('Trade_hotel_category')->where($condition)->find();
		if(empty($now_cat)){
			$this->error(L_('当前房型类别不存在'));
		}
		$this->assign('now_cat',$now_cat);
		
		$condition_son['cat_fid'] = $_GET['cat_id'];
		$condition_son['mer_id'] = $this->mer_id;
		$condition_son['is_remove'] = 0;
		$cat_list = M('Trade_hotel_category')->where($condition_son)->order('`cat_sort` DESC')->select();

		$this->assign('cat_list',$cat_list);
		
		$this->display();
	}
	public function son_category_add(){
		$condition['cat_id'] = $_GET['cat_id'];
		$condition['mer_id'] = $this->mer_id;
		$now_cat = M('Trade_hotel_category')->where($condition)->find();
		if(empty($now_cat)){
			$this->error(L_('当前房型类别不存在'));
		}
		$this->assign('now_cat',$now_cat);
		
		if(IS_POST){
			$_POST['cat_sort'] 		= !empty($_POST['cat_sort']) ? intval($_POST['cat_sort']) : '';
			$_POST['refund_hour'] 	= !empty($_POST['refund_hour']) ? intval($_POST['refund_hour']) : '';
			$_POST['code'] 			= !empty($_POST['code']) ? intval($_POST['code']) : '';
			
			if(empty($_POST['cat_name'])){
				$this->assign('error_tips',L_('请填写分类名称'));
			}else if(empty($_POST['enter_time'])){
				$this->assign('error_tips',L_('请选择入住时间'));
			}else if($_POST['has_refund'] == 2 && empty($_POST['refund_hour'])){
				$this->assign('error_tips',L_('请填写退订规定时间'));
			}else{
				$data['cat_name'] = $_POST['cat_name'];
				$data['cat_sort'] = $_POST['cat_sort'];
				$data['cat_fid']  = $now_cat['cat_id'];
				$data['mer_id']	  = $this->mer_id;
				$data['enter_time'] = $_POST['enter_time'];
				$data['has_receipt'] = $_POST['has_receipt'];
				$data['has_refund']  = $_POST['has_refund'];
				$data['refund_hour'] = $_POST['refund_hour'];
				$data['code'] = $_POST['code'];
				$data['hotelCode'] = $_POST['hotelCode'];
				$data['rateCode'] = $_POST['rateCode'];
				$data['discount_room'] = $_POST['discount_room'];
				$data['book_day'] = $_POST['book_day'];
				$data['cat_info'] = $_POST['cat_info'];
				if(M('Trade_hotel_category')->data($data)->add()){
					unset($_POST);
					$this->assign('ok_tips',L_('添加成功'));
				}else{
					$this->assign('error_tips',L_('添加失败，请重试'));
				}
			}
		}
		$this->display();
	}
	public function son_category_edit(){
		$condition_son['cat_id'] = $_GET['cat_id'];
		$condition_son['mer_id'] = $this->mer_id;
			
		if(IS_POST){
			if(empty($_POST['cat_name'])){
				$error_tips = L_('请填写分类名称');
			}else{
				$data['cat_name'] = trim($_POST['cat_name']);
				$data['cat_sort'] = $_POST['cat_sort'];
				$data['enter_time'] = $_POST['enter_time'];
				$data['has_receipt'] = $_POST['has_receipt'];
				$data['has_refund']  = $_POST['has_refund'];
				$data['refund_hour'] = $_POST['refund_hour'];
				$data['discount_room'] = $_POST['discount_room'];
				$data['book_day'] = $_POST['book_day'];
				$data['code'] = $_POST['code'];
				$data['hotelCode'] = $_POST['hotelCode'];
				$data['rateCode'] = $_POST['rateCode'];
				$data['cat_info'] = $_POST['cat_info'];
				if(M('Trade_hotel_category')->where($condition_son)->data($data)->save()){
					$ok_tips = L_('编辑成功');
				}else{
					$error_tips = L_('编辑失败，请重试');
				}
			}
		}
		
		if($error_tips){
			$now_son_cat = $_POST;
			$this->assign('error_tips',$error_tips);
		}else{
			$now_son_cat = M('Trade_hotel_category')->where($condition_son)->find();
		}
		
		if($ok_tips){
			$this->assign('ok_tips',$ok_tips);
		}
		$this->assign('now_son_cat',$now_son_cat);
		
		
		$condition['cat_id'] = $_GET['cat_fid'];
		$condition['mer_id'] = $this->mer_id;
		$now_cat = M('Trade_hotel_category')->where($condition)->find();
		if(empty($now_cat)){
			$this->error(L_('当前房型类别不存在'));
		}
		$this->assign('now_cat',$now_cat);
		
		$this->display();
	}
	public function son_category_del(){
		$condition['cat_id'] = $_GET['cat_id'];
		$condition['mer_id'] = $this->mer_id;
		$data['is_remove'] = 1;
		if(M('Trade_hotel_category')->where($condition)->data($data)->save()){
			$this->success(L_('删除成功'));
		}else{
			$this->error(L_('删除失败，请重试'));
		}
	}
	public function cat_stock(){
		$condition['cat_id'] = $_GET['cat_fid'];
		$condition['mer_id'] = $this->mer_id;
		$now_cat = M('Trade_hotel_category')->where($condition)->find();
		if(empty($now_cat)){
			$this->error(L_('当前房型类别不存在'));
		}
		$this->assign('now_cat',$now_cat);
		
		
		$condition_son['cat_id'] = $_GET['cat_id'];
		$condition_son['mer_id'] = $this->mer_id;
		$now_son_cat = M('Trade_hotel_category')->where($condition_son)->find();
		$this->assign('now_son_cat',$now_son_cat);
		
		
		$condition_stock['cat_id'] = $now_son_cat['cat_id'];
		$tmp_stock_list = M('Trade_hotel_stock')->field('`stock_day`,`price`,`discount_price`,`stock`')->where($condition_stock)->order('`stock_id` ASC')->select();
		$stock_list = array();
		foreach($tmp_stock_list as $stock_value){
			$stock_value['price'] = floatval($stock_value['price']);
			$stock_value['discount_price'] = floatval($stock_value['discount_price']);
			$tmp_stock_day_str = date('Y-m-d',strtotime($stock_value['stock_day']));
			unset($stock_value['stock_day']);
			$stock_list[$tmp_stock_day_str] = $stock_value;
		}
		$this->assign('stock_list',$stock_list);
		
		$this->display();
	}

	public function cat_stock_mutil(){
        $cat_id = I('cat_id');
	    if (IS_POST) {
            if(isset($_POST['begin_time'])&&isset($_POST['end_time'])){
                if(strtotime($_POST['begin_time'])< strtotime(date('Y-m-d',time())." 00:00:00")){
                    $this->error(L_("开始时间应大于等于当前时间"));
                }
                if ($_POST['begin_time']>$_POST['end_time']) {
                    $this->error(L_("结束时间应大于开始时间"));
                }
            }
			
			$condition_son = [
				'cat_id' => $cat_id
			];
			$now_son_cat = M('Trade_hotel_category')->where($condition_son)->find();
			
			//非对接第三方
			if(!$this->config['baodi_hotel_url'] || !$now_son_cat['code']){
				$begin_time = str_replace('-','',$_POST['begin_time']);
				$end_time = str_replace('-','',$_POST['end_time']);
				$day = (strtotime($_POST['end_time'])-strtotime($_POST['begin_time']))/86400;
				$price = $_POST['price'];
				$discount_price = $_POST['discount_price'];
				if($price<=0){
					$this->error(L_('金额必须大于0'));
				}
				// if($discount_price<=0){
				// 	$this->error(L_('优惠金额必须大于0'));
				// }
				$stock = $_POST['stock'];

				if($price<0||$discount_price<0||$stock<0){
					$this->error(L_("数据有误"));
				}
				$now_stock = M('Trade_hotel_stock')->where(array('cat_id'=>$cat_id))->getField('stock_day',true);
				$begin_time_str = strtotime($_POST['begin_time']);
				for($i=0;$i<=$day;$i++){
					$tmp['cat_id'] = $cat_id;
					$tmp['mer_id'] =  $this->mer_id;
					$tmp['price'] = $price;
					$tmp['discount_price'] = $discount_price;
					$tmp['stock'] = $stock;
					$tmp['stock_day'] = date('Ymd',$begin_time_str+$i*86400);
					if(!in_array($tmp['stock_day'],$now_stock)){
						$date[] = $tmp;
					}else{
						$inarray[] = $i;
					}
				}
			}else{
				//对接第三方
				$condition_son = [
					'cat_id' => $cat_id
				];
				$now_son_cat = M('Trade_hotel_category')->where($condition_son)->find();
				if(empty($now_son_cat['code'])){
					$this->error('该分类没有添加房类代码，无法添加');
				}
				
				import('@.ORG.third_custom.baodi.baodiHotel');
				$baodiHotel = new baodiHotel();
				$result = $baodiHotel->getDayData($_POST['begin_time'], $_POST['end_time'], $now_son_cat['code'], $now_son_cat['hotelCode'], $now_son_cat['rateCode']);

				
				$now_stock = M('Trade_hotel_stock')->where(array('cat_id'=>$cat_id))->getField('stock_day',true);
				$begin_time_str = strtotime($_POST['begin_time']);
				foreach($result as $key => $value){
					$tmp = [];
					$tmp['cat_id'] = $cat_id;
					$tmp['mer_id'] =  $this->mer_id;
					$tmp['price'] = $value['price'];
					$tmp['discount_price'] = $value['price'];
					$tmp['stock'] = $value['stock'];
					$tmp['stock_day'] = date('Ymd', strtotime($key));
					if(!in_array($tmp['stock_day'], $now_stock)){
						$date[] = $tmp;
					}else{
						$inarray[] = $tmp;
					}
				}
			}
			
            if(empty($date) && $_POST['begin_time']){
                $this->error(L_("没有可添加的数据或时间范围内已经有库存了"));
            }else {
                if (M('Trade_hotel_stock')->addAll($date)) {
                    if (!empty($inarray)) {
                        $this->success(L_('一些天数已经有库存了，本次没有修改，其他天数已经添加成功'));die;
                    } else {
                        $this->success(L_('添加成功'));die;
                    }
                }
            }
        } else {
            $this->assign('cat_id',$cat_id);
			
			$condition_son = [
				'cat_id' => $cat_id
			];
			$now_son_cat = M('Trade_hotel_category')->where($condition_son)->find();
			$this->assign('now_son_cat',$now_son_cat);
			
            $this->display();
        }
	}

	public function cat_stock_mutil_edit(){
		$cat_id = I('cat_id');
		$this->assign('cat_id', $cat_id);
		if(IS_POST) {


			if (isset($_POST['begin_time']) && isset($_POST['end_time'])) {
				if (strtotime($_POST['begin_time']) < strtotime(date('Y-m-d', time()) . " 00:00:00")) {
					$this->error(L_("开始时间应大于等于当前时间"));
				}
				if ($_POST['begin_time'] > $_POST['end_time']) {
					$this->error(L_("结束时间应大于开始时间"));
				}
			}
			
			
			$condition_son = [
				'cat_id' => $cat_id
			];
			$now_son_cat = M('Trade_hotel_category')->where($condition_son)->find();
			
			
			$begin_time = str_replace('-', '', $_POST['begin_time']);
			$end_time = str_replace('-', '', $_POST['end_time']);
			$day = (strtotime($_POST['end_time']) - strtotime($_POST['begin_time'])) / 86400;
			
			if(!$this->config['baodi_hotel_url'] || !$now_son_cat['code']){
				$price = $_POST['price'];
				if($price<=0){
					$this->error(L_('金额必须大于0'));
				}
				$discount_price = $_POST['discount_price'];
				// if($discount_price<=0){
				// 	$this->error(L_('优惠金额必须大于0'));
				// }
				$stock = $_POST['stock'];
				if ($price < 0 || $discount_price < 0 || $stock < 0) {
					$this->error(L_("数据有误"));
				}
			}

			$begin_time_str = strtotime($_POST['begin_time']);
			$where = array('cat_id' => $cat_id);
			$where['_string'] = 'stock_day >= ' . (date('Ymd', $begin_time_str)) . ' AND stock_day<=' . (date('Ymd', $begin_time_str + $day * 86400));
			$res = M('Trade_hotel_stock')->where($where)->delete();
		
			//非对接第三方
			if(!$this->config['baodi_hotel_url']){
				for ($i = 0; $i <= $day; $i++) {
					$tmp['cat_id'] = $cat_id;
					$tmp['mer_id'] = $this->mer_id;
					$tmp['price'] = $price;
					$tmp['discount_price'] = $discount_price;
					$tmp['stock'] = $stock;
					$tmp['stock_day'] = date('Ymd', $begin_time_str + $i * 86400);
					$date[] = $tmp;
				}
			}else{
				//对接第三方
				$condition_son = [
					'cat_id' => $cat_id
				];
				$now_son_cat = M('Trade_hotel_category')->where($condition_son)->find();
				if(empty($now_son_cat['code'])){
					$this->error('该分类没有添加房类代码，无法添加');
				}
				
				import('@.ORG.third_custom.baodi.baodiHotel');
				$baodiHotel = new baodiHotel();
				$result = $baodiHotel->getDayData($_POST['begin_time'], $_POST['end_time'], $now_son_cat['code'], $now_son_cat['hotelCode'], $now_son_cat['rateCode']);
				
				foreach($result as $key => $value){
					$tmp = [];
					$tmp['cat_id'] = $cat_id;
					$tmp['mer_id'] =  $this->mer_id;
					$tmp['price'] = $value['price'];
					$tmp['discount_price'] = $value['price'];
					$tmp['stock'] = $value['stock'];
					$tmp['stock_day'] = date('Ymd', strtotime($key));
					$date[] = $tmp;
				}
			}
			
			if (M('Trade_hotel_stock')->addAll($date)) {
				$this->success(L_('更新成功'));
				die;
			}

		}else{
			$condition_son = [
				'cat_id' => $cat_id
			];
			$now_son_cat = M('Trade_hotel_category')->where($condition_son)->find();
			$this->assign('now_son_cat',$now_son_cat);
		}
		
		$this->display();
	}

	public function stock_save(){
		$condition_stock['cat_id'] = $_POST['cat_id'];
		$condition_stock['mer_id'] = $this->mer_id;
		M('Trade_hotel_stock')->where(array($condition_stock))->delete();
		$stock = array();
		foreach($_POST['stock'] as $value){
			if($value['date_num'] >= date('Ymd',$_SERVER['REQUEST_TIME'])){
				$data = array(
					'cat_id'		=>	$_POST['cat_id'],
					'mer_id'		=>	$this->mer_id,
					'stock_day'		=>	$value['date_num'],
					'price'			=>	$value['price'],
					'discount_price'=>	$value['discount_price'],
					'stock'			=>	$value['stock'],
				);
                $stock[] = $data;
			}
		}
		if ($stock) {
            if(!M('Trade_hotel_stock')->addAll($stock)){
                $this->error(L_('保存失败，请重试'));
            }
        }


		$this->success(L_('保存成功'));
	}
	public function ajax_upload_pic() {
        if ($_FILES['file']['error'] != 4) {

        	$width = '760,380';
        	$height = '450,225';
			
			$param = array('size' => $this->config['group_pic_size']);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
            $param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = $width;
            $param['thumbMaxHeight'] = $height;
            $param['thumbRemoveOrigin'] = false;
			$image = D('Image')->handle($this->merchant_session['mer_id'], 'trade_hotel', 1, $param);
			if ($image['error']) {
				exit(json_encode(array('error' => 1, 'info' => $image['msg'] ? : $image['message'])));
			} else {
				$title = $image['title']['file'];
				$trade_hotel_image_class = new trade_hotel_image();
				$url = $trade_hotel_image_class->get_image_by_path($title, 's');
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			}
		}else{
			exit(json_encode(array('error' => 1,'info' => L_('没有选择图片'))));
		}
	}
	public function ajax_del_pic() {
		$trade_hotel_image_class = new trade_hotel_image();
		$trade_hotel_image_class->del_image_by_path($_POST['path']);
	}
}
?>