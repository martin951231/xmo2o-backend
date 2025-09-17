<?php
/*
 * 订餐管理
 *
 * @  BuildTime  2014/11/18 11:21
 */

class MealAction extends BaseAction
{
    public function index()
    {
    	$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_meal_category = D('Meal_store_category');
		$category = $database_meal_category->field(true)->where(array('cat_id' => $parentid))->find();
		$category_list = $database_meal_category->field(true)->where(array('cat_fid' => $parentid))->order('`cat_sort` DESC,`cat_id` ASC')->select();
		$this->assign('category', $category);
		$this->assign('category_list', $category_list);
		$this->assign('parentid', $parentid);
		$this->display();
    }

	//餐饮首页配置==>guojie
	public function main_page(){

		$where['type'] = $_GET['type']?$_GET['type']:$this->config['meal_main_page_center_type'];
		$slider_list = M('Meal_main_page_pic_slider')->where($where)->order('sort DESC')->select();
		$this->assign('slider_list',$slider_list);
		$this->display();
	}

	//添加餐饮首页中部导航图文 ==郭杰
	public function add_group_center_type_img(){

		if(IS_POST){
			$where['type'] = $_POST['type'];
			$count = M('Meal_main_page_pic_slider')->where($where)->count();
			if($_FILES['pic']['error'] != 4){
				$image = D('Image')->handle($this->system_session['id'], 'slider');
				if (!$image['error']) {
					$_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
				} else {
					$this->frame_submit_tips(0, $image['message']);
				}
			}
			$data['name'] = $_POST['name'];
			$data['sub_name'] = $_POST['sub_name'];
			$data['url'] = $_POST['url'];
			$data['last_time'] = $_SERVER['REQUEST_TIME'];
			$_FILES['pic']['error'] != 4 && $data['pic']    = $_POST['pic'];
			$data['sort']    = $_POST['sort'];
			$data['type']    = $_POST['type'];

			if($_POST['id']){
				$result = M('Meal_main_page_pic_slider')->where(array('id'=>$_POST['id']))->save($data);

			}else{
				if($count==abs($_POST['type'])){
					$this->error_tips(abs($_POST['type']).'图模式只能添加'.abs($_POST['type']).'个');
				}
				$result = M('Meal_main_page_pic_slider')->add($data);

			}

			if($result){
				$this->frame_submit_tips(1,'保存成功！');
			}else{
				$this->frame_submit_tips(0,'保存失败！请重试~');
			}
		}else{
			if($_GET['id']){
				$where['id'] =  $_GET['id'];
				$slider = M('Meal_main_page_pic_slider')->where($where)->find();
				$this->assign('slider',$slider);
			}
			$this->display();
		}
	}
    
	public function cat_add()
	{
		$this->assign('bg_color','#F3F3F3');
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$this->assign('parentid', $parentid);
		$this->display();
	}
	public function cat_modify()
	{
		if(IS_POST){
			$database_meal_category = D('Meal_store_category');
			if($database_meal_category->data($_POST)->add()){
				$this->success('添加成功！');
			}else{
				$this->error('添加失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function cat_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_meal_category = D('Meal_store_category');
		$condition_now_meal_category['cat_id'] = intval($_GET['cat_id']);
		$now_category = $database_meal_category->field(true)->where($condition_now_meal_category)->find();
		if(empty($now_category)){
			$this->frame_error_tips('没有找到该分类信息！');
		}
		$this->assign('parentid', $parentid);
		$this->assign('now_category',$now_category);
		$this->display();
	}
	public function cat_amend(){
		if(IS_POST){
			$database_meal_category = D('Meal_store_category');
			$where = array('cat_id' => $_POST['cat_id']);
			unset($_POST['cat_id']);
			if($database_meal_category->where($where)->save($_POST)){
				$this->success('编辑成功！');
			}else{
				$this->error('编辑失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function cat_del(){
		if(IS_POST){
			$database_meal_category = D('Meal_store_category');
			$condition_now_meal_category['cat_id'] = intval($_POST['cat_id']);
			
			if ($obj = $database_meal_category->field(true)->where($condition_now_meal_category)->find()) {
				$t_list = $database_meal_category->field(true)->where(array('cat_fid' => $obj['cat_id']))->select();
				if ($t_list) {
					$this->error('该分类下有子分类，先清空子分类，再删除该分类');
				}
			}
			if($database_meal_category->where($condition_now_meal_category)->delete()){
				$database_meal_category_relation = D('Meal_category_relation');
				$condition_meal_category_relation['cat_id'] = intval($_POST['cat_id']);
				$database_meal_category_relation->where($condition_meal_category_relation)->delete();
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	
	public function order()
	{
		$where_store = array('status' => 1);
		if(!empty($_GET['keyword']) && $_GET['searchtype'] == 's_name'){
			$where_store['name'] = array('like', '%'.$_GET['keyword'].'%');
		}
		
		if ($this->system_session['area_id']) {
			$area_index = $this->system_session['level'] == 1 ? 'area_id' : 'city_id';
			$where_store[$area_index] = $this->system_session['area_id'];
			$stores = D('Merchant_store')->field('store_id')->where($where_store)->select();//
		} else {
			$stores = D('Merchant_store')->field('store_id')->where($where_store)->select();
		}
		$store_ids = array();
		foreach ($stores as $row) {
			$store_ids[] = $row['store_id'];
		}
		$where = array('paid' => array('gt', 0));
		if ($store_ids) {
			$where['store_id'] = array('in', $store_ids);
		} else {
			import('@.ORG.system_page');
			$p = new Page(0, 20);
			$this->assign('order_list', null);
			$this->assign('pagebar', $p->show());
			$this->display();
			exit;
		}
		if(!empty($_GET['keyword'])){
			if ($_GET['searchtype'] == 'order_id') {
				$where['order_id'] = intval($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'orderid') {
				$where['orderid'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'name') {
				$where['name'] = htmlspecialchars($_GET['keyword']);
			} elseif ($_GET['searchtype'] == 'phone') {
				$where['phone'] = htmlspecialchars($_GET['keyword']);
			}
		}
		$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
		$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
		$sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
		if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
		if ($type != 'price' && $type != 'pay_time') $type = '';
		$order_sort = '';
		if ($type && $sort) {
			$order_sort .= $type . ' ' . $sort . ',';
			$order_sort .= 'order_id DESC';
		} else {
			$order_sort .= 'order_id DESC';
		}
		
		if ($status != -1) {
			$where['status'] = $status;
		}

		
		$count = D("Meal_order")->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$list = D("Meal_order")->where($where)->order($order_sort)->limit($p->firstRow . ',' . $p->listRows)->select();
		$mer_ids = $store_ids = array();
		foreach ($list as $l) {
			$mer_ids[] = $l['mer_id'];
			$store_ids[] = $l['store_id'];
		}
		$store_temp = $mer_temp = array();
		if ($mer_ids) {
			$merchants = D("Merchant")->where(array('mer_id' => array('in', $mer_ids)))->select();
			foreach ($merchants as $m) {
				$mer_temp[$m['mer_id']] = $m;
			}
		}
		if ($store_ids) {
			$merchant_stores = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_stores as $ms) {
				$store_temp[$ms['store_id']] = $ms;
			}
		}
		foreach ($list as &$li) {
			$li['info'] = unserialize($li['info']);
			$li['merchant_name'] = isset($mer_temp[$li['mer_id']]['name']) ? $mer_temp[$li['mer_id']]['name'] : '';
			$li['store_name'] = isset($store_temp[$li['store_id']]['name']) ? $store_temp[$li['store_id']]['name'] : '';
			if ($li['total_price'] > 0) {
				$li['offline_money'] = (floor($li['total_price'] * 100)-floor($li['minus_price'] * 100)-floor($li['balance_pay'] * 100)-floor($li['merchant_balance'] * 100)-floor($li['coupon_price'] * 100)-floor($li['card_price'] * 100)-floor($li['score_deducte'] * 100)-floor($li['payment_money'] * 100))/100;
			} else {
				$li['offline_money'] = (floor($li['price'] * 100)-floor($li['balance_pay'] * 100)-floor($li['merchant_balance'] * 100)-floor($li['coupon_price'] * 100)-floor($li['card_price'] * 100)-floor($li['score_deducte'] * 100)-floor($li['payment_money'] * 100))/100;
			}
			
		}
		$this->assign('order_list', $list);
		
		$pagebar = $p->show();
		
		$this->assign('pagebar', $pagebar);
		$this->assign(array('type' => $type, 'sort' => $sort, 'status' => $status));
		$this->assign('status_list', D('Meal_order')->status_list);

		$this->display();
		
	}
	
	public function order_detail(){
		$this->assign('bg_color','#F3F3F3');
		
		$database_meal_order = D('Meal_order');
		$where['order_id'] = intval($_GET['order_id']);
		$order = $database_meal_order->field(true)->where($where)->find();
		if(empty($order)){
			$this->frame_error_tips('没有找到该订单的信息！');
		}

		if(!empty($order['coupon_id'])) {
			$system_coupon = D('System_coupon')->get_coupon_info($order['coupon_id']);
			$order['coupon_price'] = $system_coupon['price'];
			$this->assign('system_coupon',$system_coupon);
		}else if(!empty($order['card_id'])) {
			$card = D('Member_card_coupon')->get_coupon_info($order['card_id']);
			$order['coupon_price'] = $card['price'];
			$this->assign('card', $card);
		}
		$order['info'] = unserialize($order['info']);
		if ($order['total_price'] > 0) {
			$order['offline_money'] = round($order['total_price']-round(floatval($order['minus_price'])+floatval($order['balance_pay'])+floatval($order['merchant_balance'])+floatval($order['coupon_price'])+floatval($order['card_price']) +floatval($order['score_deducte'])+floatval($order['payment_money']), 2), 2);
		} else {
			$order['offline_money'] = round($order['price'] - round($order['balance_pay'] + $order['merchant_balance'] + $order['coupon_price'] + $order['card_price'] + $order['score_deducte'] + $order['payment_money'], 2), 2);
		}
		
		$mode = new Model();
		$sql = "SELECT u.name, u.phone FROM " . C('DB_PREFIX') . "deliver_supply AS s INNER JOIN " . C('DB_PREFIX') . "deliver_user AS u ON u.uid=s.uid WHERE s.order_id={$order['order_id']} AND s.item=0";
		$res = $mode->query($sql);
		$res = isset($res[0]) && $res[0] ? $res[0] : '';
		$order['deliver_user_info'] = $res;
		$this->assign('order', $order);
		$this->display();
	}
	
	public function label()
	{
		$labels = D('Store_label')->field(true)->select();
		
		$this->assign('labels', $labels);
		$this->display();
	}
	public function label_add()
	{
		$this->assign('bg_color','#F3F3F3');
		$label = D('Store_label')->field(true)->where(array('id' => intval($_GET['id'])))->find();
		$this->assign('label', $label);
		
		$this->display();
	}
	public function label_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		$label = D('Store_label')->field(true)->where(array('id' => intval($_GET['id'])))->find();
		$this->assign('label', $label);
		
		$this->display();
	}
	
	public function label_modify()
	{
		$name = $_POST['name'] = trim($_POST['name']);
		if (empty($name)) {
			$this->frame_submit_tips(0, '标签名不能为空');
		}
		$len = $this->dstrlen($name);
		if ($len > 4) {
			$this->frame_submit_tips(0, '标签名不能超过四个汉字');
		}
// 		$image = D('Image')->handle($this->system_session['id'], 'store_icon', 0, array('size' => 10), false);
// 		if (!$image['error']) {
// 			$_POST = array_merge($_POST, $image['url']);
// 		} else {
// 			$this->frame_submit_tips(0, $image['msg']);
// 		}
		$_POST['dateline'] = $_SERVER['REQUEST_TIME'];
// 		$_POST['icon'] = htmlspecialchars_decode($_POST['icon']);
		$store_label_db = D('Store_label');
		if($id = $store_label_db->data($_POST)->add()){
// 			D('Image')->update_table_id('/upload/store_icon/' . $_POST['icon'], $id, 'store_icon');
			$this->frame_submit_tips(1,'添加成功！');
		}else{
			$this->frame_submit_tips(0,'添加失败！请重试~');
		}
	}
	
	public function label_amend()
	{
		$name = $_POST['name'] = trim($_POST['name']);
		if (empty($name)) {
			$this->frame_submit_tips(0, '标签名不能为空');
		}
		$len = $this->dstrlen($name);
		if ($len > 4) {
			$this->frame_submit_tips(0, '标签名不能超过四个汉字');
		}
		$store_label_db = D('Store_label');
		$condition['id'] = $_POST['id'];
		$now_label = $store_label_db->field(true)->where($condition)->find();
// 		if($_FILES['icon']['error'] != 4){
// 			$image = D('Image')->handle($this->system_session['id'], 'store_icon', 0, array('size' => 10), false);
// 			if (!$image['error']) {
// 				$_POST = array_merge($_POST, $image['url']);
// 			} else {
// 				$this->frame_submit_tips(0, $image['msg']);
// 			}
// 			$_POST['icon'] = htmlspecialchars_decode($_POST['icon']);
// 		}
		$_POST['dateline'] = $_SERVER['REQUEST_TIME'];
		if($store_label_db->data($_POST)->save()){
// 			D('Image')->update_table_id('/upload/store_icon/' . $_POST['icon'], $_POST['id'], 'store_icon');
// 			if($_POST['icon']){
// 				unlink($now_label['icon']); 
// 			}
			$this->frame_submit_tips(1,'编辑成功！');
		}else{
			$this->frame_submit_tips(0,'编辑失败！请重试~');
		}
	}
	
	public function label_del()
	{
		$store_label_db = D('Store_label');
		$condition['id'] = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$now_label = $store_label_db->field(true)->where($condition)->find();
		if($store_label_db->where($condition)->delete()){
			unlink($now_label['icon']); 
			$this->success('删除成功');
		}else{
			$this->error('删除失败！请重试~');
		}
	}
	// 配置
    public function config(){
		$database_config_group = D('Config_group');

		$condition_config_group['gid'] = '5';
		$group_list = $database_config_group->field(true)->where($condition_config_group)->order('`gsort` DESC,`gid` ASC')->select();
		foreach($group_list as &$gListValue){
			$gListValue['gname'] = str_replace('订餐',$this->config['meal_alias_name'],$gListValue['gname']);
			$gListValue['gname'] = str_replace('团购',$this->config['group_alias_name'],$gListValue['gname']);
			$gListValue['gname'] = str_replace('预约',$this->config['appoint_alias_name'],$gListValue['gname']);
			$gListValue['gname'] = str_replace('礼品',$this->config['gift_alias_name'],$gListValue['gname']);
			$gListValue['gname'] = str_replace('快店',$this->config['shop_alias_name'],$gListValue['gname']);
		}
		$this->assign('group_list',$group_list);
		if(empty($_GET['galias'])){
			$gid = $this->_get('gid');
		}else{
			foreach($group_list as $value){
				if($value['galias'] == $_GET['galias']){
					$gid = $value['gid'];
					break;
				}
			}
			$header_file = $_GET['header'];
			$this->assign('header_file',$header_file);
		}
		if(empty($gid)) $gid = $group_list[0]['gid'];
		$this->assign('gid',$gid);
		$this->assign('galias', $_GET['galias']);

		$database_config = D('Config');
		$condition_config['gid'] = $gid;
		$condition_config['status'] = '1';
		$tmp_config_list = $database_config->where($condition_config)->order('`sort` DESC')->select();

		foreach($tmp_config_list as $key=>$value){
			$value['info'] = str_replace('订餐',$this->config['meal_alias_name'],$value['info']);
			$value['info'] = str_replace('团购',$this->config['group_alias_name'],$value['info']);
			$value['info'] = str_replace('预约',$this->config['appoint_alias_name'],$value['info']);
			$value['info'] = str_replace('礼品',$this->config['gift_alias_name'],$value['info']);
			$value['info'] = str_replace('快店',$this->config['shop_alias_name'],$value['info']);
			
			$config_list[$value['tab_id']]['name'] = $value['tab_name'];
			$config_list[$value['tab_id']]['list'][] = $value;
		}
		$this->assign($this->build_html($config_list));

		if(!empty($_GET['galias'])){
			import('ORG.Util.Dir');
			Dir::delDirnotself('./runtime/Cache/System/');
		}
		$this->display();
	}
	protected function build_html($config_list){ 
		if(is_array($config_list)){
			$config_html = '';
			if(count($config_list) > 1) $has_tab = true;
			if($has_tab) $config_tab_html = '<ul class="tab_ul">';
			$pigcms_auto_key = 1;
			foreach($config_list as $pigcms_key=>$pigcms_value){
				if($has_tab) $config_tab_html .= '<li '.($pigcms_auto_key==1 ? 'class="active"' : '').'><a data-toggle="tab" href="#tab_'.$pigcms_key.'">'.($pigcms_value['name'] ? $pigcms_value['name'] : '基本设置').'</a></li>';

				$config_html .= '<table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_'.$pigcms_key.'">';
				foreach($pigcms_value['list'] as $key=>$value){
					$tmp_type_arr = explode('&',$value['type']);
					$type_arr = array();
					foreach($tmp_type_arr as $k=>$v){
						$tmp_value = explode('=',$v);
						$type_arr[$tmp_value[0]] = $tmp_value[1];
					}

					$config_html .= '<tr>'; 
					if (in_array($value['name'],array('service1','service2'))) {
						$config_html .= '<th width="160" style="color:red;">'.$value['info'].'：</th>' ;
					}else{
						$config_html .= '<th width="160">'.$value['info'].'：</th>' ;
					}
					$config_html .= '<td>'; 
					if($type_arr['type'] == 'text'){
						$size = !empty($type_arr['size']) ? $type_arr['size'] : '60';
						if ($value['name']=='mobile_head_color') {
							$config_html .= '<input type="text" class="input-text" name="'.$value['name'].'" id="config_'.$value['name'].'" value="'.$value['value'].'" size="'.$size.'" validate="'.$type_arr['validate'].'" tips="'.$value['desc'].'"/>&nbsp;&nbsp;<a href="javascript:void(0);" id="choose_color_box1" style="line-height:28px;">点击选择颜色</a></td><script>$("#choose_color_box1").click(function(){window.top.showTopColorPanel("leftmenu_Config_index",$(this).offset().top,$(this).height(),$(this).offset().left,"config_'.$value['name'].'");	});</script>';
						}else{
							$config_html .= '<input type="text" class="input-text" name="'.$value['name'].'" id="config_'.$value['name'].'" value="'.$value['value'].'" size="'.$size.'" validate="'.$type_arr['validate'].'" tips="'.$value['desc'].'"/>';
						}
					}else if($type_arr['type'] == 'textarea'){
						$rows = !empty($type_arr['rows']) ? $type_arr['rows'] : '4';
						$cols = !empty($type_arr['cols']) ? $type_arr['cols'] : '80';
						$config_html .= '<textarea rows="'.$rows.'" cols="'.$cols.'" name="'.$value['name'].'" id="config_'.$value['name'].'" validate="'.$type_arr['validate'].'" tips="'.$value['desc'].'">'.$value['value'].'</textarea>';
					}else if($type_arr['type'] == 'radio'){
						$radio_option = explode('|',$type_arr['value']);
						foreach($radio_option as $radio_k=>$radio_v){
							$radio_one = explode(':',$radio_v);
							if($radio_k == 0){
								$config_html .= '<span class="cb-enable"><label class="cb-enable '.($value['value']==$radio_one[0] ? 'selected' : '').'"><span>'.$radio_one[1].'</span><input type="radio" name="'.$value['name'].'" value="'.$radio_one[0].'" '.($value['value']==$radio_one[0] ? 'checked="checked"' : '').'/></label></span>';
							}else if($radio_k == 1){
								$config_html .= '<span class="cb-disable"><label class="cb-disable '.($value['value']==$radio_one[0] ? 'selected' : '').'"><span>'.$radio_one[1].'</span><input type="radio" name="'.$value['name'].'" value="'.$radio_one[0].'" '.($value['value']==$radio_one[0] ? 'checked="checked"' : '').'/></label></span>';
							}
						}
						if($value['desc']){
							$config_html .= '<em tips="'.$value['desc'].'" class="notice_tips"></em>';
						}
					}else if($type_arr['type'] == 'image'){
						$config_html .= '<span class="config_upload_image_btn"><input type="button" value="上传图片" class="button" style="margin-left:0px;margin-right:10px;"/></span><input type="text" class="input-text input-image" name="'.$value['name'].'" id="config_'.$value['name'].'" value="'.$value['value'].'" size="48" validate="'.$type_arr['validate'].'" tips="'.$value['desc'].'"/> ';
					}else if($type_arr['type'] == 'file'){
						$config_html .= '<span class="config_upload_file_btn" file_validate="'.$type_arr['file'].'"><input type="button" value="上传文件" class="button" style="margin-left:0px;margin-right:10px;"/></span><input type="text" class="input-text input-file" name="'.$value['name'].'" id="config_'.$value['name'].'" value="'.$value['value'].'" size="48" readonly="readonly" validate="'.$type_arr['validate'].'" tips="'.$value['desc'].'"/> ';
					}else if($type_arr['type'] == 'select'){

						if($value['name']=="recharge_level_value"){
							$levelDb=M('User_level');
							$tmparr=M(_view('User_level'))->field(true)->order('`id` ASC')->select();
							$type_arr['value'] = '';
							foreach ($tmparr as $vc) {
								$type_arr['value'] .= $vc['level'].':'.$vc['lname'].'|';
							}
							$type_arr['value']  = trim($type_arr['value'] ,'|');
						}

						$radio_option = explode('|',$type_arr['value']);
						$config_html .= '<select name="'.$value['name'].'">';
						foreach($radio_option as $radio_k=>$radio_v){
							$radio_one = explode(':',$radio_v);
							$config_html .= '<option value="'.$radio_one[0].'" '.($value['value']==$radio_one[0] ? 'selected="selected"' : '').'>'.$radio_one[1].'</option>';
						}
						$config_html .= '</select>';
						if($value['desc']){
							$config_html .= '<em tips="'.$value['desc'].'" class="notice_tips"></em>';
						}
					}else if($type_arr['type'] == 'twoTime'){
						$tmpTime = explode('-',$value['value']);
						$config_html .= '<input type="text" class="input-text" name="'.$value['name'].'[]" value="'.$tmpTime[0].'" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:\''.$type_arr['format'].'\'})"/>&nbsp;-&nbsp;&nbsp;';
						$config_html .= '<input type="text" class="input-text" name="'.$value['name'].'[]" value="'.$tmpTime[1].'" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:\''.$type_arr['format'].'\'})"/>';
					}else if($type_arr['type'] == 'webTpl'){
						$hostArr = parse_url($this->config['site_url']);
						import('ORG.Util.Dir');
						$dirObj = new Dir(TMPL_PATH.C('DEFAULT_GROUP'));
						$radio_option = array();
						foreach($dirObj->_values as $dirValue){
							if($dirValue['isDir']){
								$tpl_theme_ini = parse_ini_file($dirValue['pathname'].'/theme.ini');
								if($tpl_theme_ini){
									$tmpArr = array(
											'name'=>$tpl_theme_ini['name'],
											'path'=>$tpl_theme_ini['path'],
											'isUse'=> ($tpl_theme_ini['path'] == $value['value'] ? true : false)
										);
									if(empty($tpl_theme_ini['domain']) || $tpl_theme_ini['domain'] == $hostArr['host']){
										array_push($radio_option,$tmpArr);
									}
								}
							}
						}
						$config_html .= '<select name="'.$value['name'].'">';
						foreach($radio_option as $radio_k=>$radio_v){
							$config_html .= '<option value="'.$radio_v['path'].'" '.($radio_v['isUse'] ? 'selected="selected"' : '').'>'.$radio_v['name'].'</option>';
						}
						$config_html .= '</select>';
						if($value['desc']){
							$config_html .= '<em tips="'.$value['desc'].'" class="notice_tips"></em>';
						}
					}else if($type_arr['type'] == 'wapTpl'){
						$hostArr = parse_url($this->config['site_url']);
						import('ORG.Util.Dir');
						$dirObj = new Dir(TMPL_PATH.'Wap/');
						$radio_option = array();
						foreach($dirObj->_values as $dirValue){
							if($dirValue['isDir']){
								$tpl_theme_ini = parse_ini_file($dirValue['pathname'].'/theme.ini');
								if($tpl_theme_ini){
									$tmpArr = array(
											'name'=>$tpl_theme_ini['name'],
											'path'=>$tpl_theme_ini['path'],
											'isUse'=> ($tpl_theme_ini['path'] == $value['value'] ? true : false)
										);
									if(empty($tpl_theme_ini['domain']) || $tpl_theme_ini['domain'] == $hostArr['host']){
										array_push($radio_option,$tmpArr);
									}
								}
							}
						}
						$config_html .= '<select name="'.$value['name'].'">';
						foreach($radio_option as $radio_k=>$radio_v){
							$config_html .= '<option value="'.$radio_v['path'].'" '.($radio_v['isUse'] ? 'selected="selected"' : '').'>'.$radio_v['name'].'</option>';
						}
						$config_html .= '</select>';
						if($value['desc']){
							$config_html .= '<em tips="'.$value['desc'].'" class="notice_tips"></em>';
						}
					}
					$config_html .= '</td>';
					$config_html .= '</tr>';
				}
				$config_html .= '</table>';
				$pigcms_auto_key++;
			}
			if($has_tab) $config_tab_html .= '</ul>';

			$return_config['config_html'] = $config_html;
			if($has_tab) $return_config['config_tab_html'] = $config_tab_html;
			return $return_config;
		}
	}

	private function dstrlen($str)
	{
		$count = 0;
		for ($i = 0; $i < strlen($str); $i++) {
			$value = ord($str[$i]);
			if ($value > 127) {
				if ($value >= 192 && $value <= 223) {
					$i++;
				} elseif ($value >= 224 && $value <= 239) {
					$i = $i + 2;
				} elseif ($value >= 240 && $value <= 247) {
					$i = $i + 3;
				}
			}
			$count++;
		}
		return $count;
	}
}