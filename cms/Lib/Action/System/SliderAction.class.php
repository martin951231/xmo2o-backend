<?php
/*
 * 导航管理
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/12/02 9:43
 * 
 */
class SliderAction extends BaseAction{
	public function index(){
		$database_slider_category  = D('Slider_category');
		$category_list = $database_slider_category->field(true)->order('`cat_id` ASC')->select();
		$this->assign('category_list',$category_list);
		$this->display();
	}
	public function cat_add(){
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}
	public function cat_modify(){
		if(IS_POST){
			$database_slider_category  = D('Slider_category');
			if($database_slider_category->data($_POST)->add()){
				$this->success('添加成功！');
			}else{
				$this->error('添加失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function cat_edit(){
		$this->assign('bg_color','#F3F3F3');
		$now_category = $this->frame_check_get_category($_GET['cat_id']);
		$this->assign('now_category',$now_category);
		
		$this->display();
	}
	public function cat_amend(){
		if(IS_POST){
			$database_slider_category  = D('Slider_category');
			if($database_slider_category->data($_POST)->save() !== false){
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
			$database_slider_category  = D('Slider_category');
			$condition_slider_category['cat_id'] = $_POST['cat_id'];
			if($database_slider_category->where($condition_slider_category)->delete()){
				//删除所有广告
				$database_slider = D('Slider');
				$condition_slider['cat_id'] = $_POST['cat_id'];
				$slider_list = $database_slider->field(true)->where($condition_slider)->order('`id` DESC')->select();
				foreach($slider_list as $key=>$value){
					unlink('./upload/slider/'.$value['pic']); 
				}
				$database_slider->where($condition_slider)->delete();
				
				S('slider_list_'.$_POST['cat_id'],NULL);
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
	public function slider_list(){
		$this->assign('bg_color','white');
		if($_GET['cat_key']){
			$database_slider_category  = D('Slider_category');
			$condition_slider_category['cat_key'] = $_GET['cat_key'];
			$now_category = $database_slider_category->field(true)->where($condition_slider_category)->find();
		}else{
			$now_category = $this->check_get_category($_GET['cat_id']);
		}

		if($_GET['parentid']){
			$database_shop_category = D(('shop_category'));
			$category = $database_shop_category->field(true)->where(array('cat_id' => $_GET['parentid']))->find();
			$now_category['cat_name'] = $category['cat_name'].' - '.$now_category['cat_name'];
			$this->assign('shop_id', $_GET['parentid']);
		}

		$this->assign('now_category',$now_category);
		
		$database_slider = D('Slider');
        $area_id = $this->system_session['area_id'];
        //如果是区域管理员进来
        if($area_id&&C('customization.open_view_multi_city')!=1){
            $area_id = $this->system_session['area_id'];
            if ($this->system_session['level'] == 1) {
                $temp = M('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
				if($temp['area_type'] == 1){
					$city_list = D('Area')->get_arealist_by_areaPid($temp['area_id'],true);
					$area_id = array();
					foreach($city_list as $value){
						$area_id[] = $value['area_id'];
					}
				}else if($temp['area_type'] == 2){
					$area_id = $temp['area_id'];
				}else{
					$area_id = $temp['area_pid'];
				}
            }
			if(is_array($area_id)){
				$condition_slider['city_id'] = array('in',$area_id);
			}else{
				$condition_slider['city_id'] = $area_id;
			}
        }
		$condition_slider['cat_id'] = $now_category['cat_id'];

		if($_GET['parentid']){
			$condition_slider['shop_id'] = $_GET['parentid'];
		}		
		$slider_list = $database_slider->field(true)->where($condition_slider)->order('`sort` DESC,`id` ASC')->select();
		$citys = array_filter(array_column($slider_list, 'city_id'));
		$citynames = array_column(M("Area")->where(['area_id'=>['in',$citys]])->select(),'area_name','area_id');
		foreach($slider_list as &$val){
			$val['cityname'] = $val['city_id'] ? $citynames[$val['city_id']] : '通用';
		}
		$this->assign('slider_list',$slider_list);

		$this->display();
	}
	public function slider_add(){
		$many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
		$this->assign('bg_color','#F3F3F3');
		$now_category = $this->frame_check_get_category($_GET['cat_id']);
		$this->assign('now_category',$now_category);
		
		//小程序列表
		$wxapp_list = M('Wxapp_other')->field(true)->select();
		$this->assign('wxapp_list',$wxapp_list);
        //ios列表
        $app_list = M('App_other')->field(true)->select();
        $this->assign('app_list', $app_list);

        //是否为区域管理员并且开启可视化多模块设置
        $is_area_many_city = 0;
        if($this->system_session['open_admin_area']==1&&C('customization.open_view_multi_city')==1){
            $is_area_many_city = 1;
        }
        $this->assign('is_area_many_city',$is_area_many_city);
		
		$this->display();
	}
	public function slider_modify(){
		if($_FILES['pic']['error'] != 4){
			$image = D('Image')->handle($this->system_session['id'], 'slider');
			if (!$image['error']) {				
				$_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
			} else {
				$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
			}
		}
        
		if($_GET['shop_id']){
			$_POST['shop_id']	=	$_GET['shop_id'];
		}

		if($_POST['currency'] == 1 || (empty($_POST['province_idss'])&&C('customization.open_view_multi_city')!=1)){
			$_POST['province_id']	=	0;
			$_POST['city_id']	=	0;
		}else{
            if(C('customization.open_view_multi_city')==1){
                $_POST['province_id']	=	0;
                $_POST['city_id']	=	-1;
                $_POST['city_ids'] = '';
            }else{
                $_POST['province_id']	=	$_POST['province_idss'];
                $_POST['city_id']	=	$_POST['city_idss'];
            }
            unset($_POST['province_idss'],$_POST['city_idss']);
		}
		// app打开其他小程序需要获取原始id
		if($_POST['app_wxapp_id']){
        	$wxapp = M('Wxapp_other')->field(true)->where(['appid'=>$_POST['app_wxapp_id']])->find();
        	$_POST['app_wxapp_username'] = $wxapp['username'];
		}
		
		$_POST['url'] = htmlspecialchars_decode($_POST['url']);
		$_POST['last_time'] = $_SERVER['REQUEST_TIME'];

        //时间展示
        if ($_POST['effect_date_type'] == 1) {
            $_POST['start_date'] = $_POST['end_date'] = null;
            $_POST['week'] = [];
        }
        if ($_POST['effect_time_type'] == 1) {
            $_POST['start_time'] = $_POST['end_time'] = null;
        }
        $_POST['week'] = implode(',', $_POST['week']);
		$database_slider = D('Slider');
		if($id = $database_slider->data($_POST)->add()){
			D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');
			S('slider_list_'.$_POST['cat_id'],NULL);
			// 多语言
            $lang_model = D('Lang');
            $_POST['id'] = $id;
            $lang_model->add_lang_data('Slider',$_POST);

			$this->frame_submit_tips(1,'添加成功！');
		}else{
			$this->frame_submit_tips(0,'添加失败！请重试~');
		}
	}
	public function slider_edit(){
		$many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
		$this->assign('bg_color','#F3F3F3');
		
		$database_slider = D('Slider');
		$condition_slider['id'] = $_GET['id'];
		$now_slider = $database_slider->field(true)->where($condition_slider)->find();
		if(empty($now_slider)){
			$this->frame_error_tips('该导航不存在！');
		}
		$this->assign('now_slider',$now_slider);
		$now_category = $this->frame_check_get_category($now_slider['cat_id']);
		$this->assign('now_category',$now_category);

		//小程序列表
		$wxapp_list = M('Wxapp_other')->field(true)->select();
		$this->assign('wxapp_list',$wxapp_list);
        //ios列表
        $app_list = M('App_other')->field(true)->select();
        $this->assign('app_list', $app_list);

        //是否为区域管理员并且开启可视化多模块设置
        $is_area_many_city = 0;
        if($this->system_session['open_admin_area']==1&&C('customization.open_view_multi_city')==1){
            $is_area_many_city = 1;
        }
        $this->assign('is_area_many_city',$is_area_many_city);
		
		$this->display();
	}
	
	public function slider_amend(){
		$database_slider = D('Slider');
		$condition_slider['id'] = $_POST['id'];
		$now_slider = $database_slider->field(true)->where($condition_slider)->find();
		
		if($_FILES['pic']['error'] != 4){
			$image = D('Image')->handle($this->system_session['id'], 'slider');
			if (!$image['error']) {
				$_POST = array_merge($_POST, str_replace('/upload/slider/', '', $image['url']));
			} else {
				$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
			}
		}
		if($_POST['currency'] == 1 || (empty($_POST['province_idss'])&&C('customization.open_view_multi_city')!=1)){
			$_POST['province_id']	=	0;
			$_POST['city_id']	=	0;
		}else{
            if(C('customization.open_view_multi_city')==1) {
                $_POST['province_id']	=	0;
                $_POST['city_id']	=	-1;
                $_POST['city_ids'] = '';
            }else{
                $_POST['province_id']	=	$_POST['province_idss'];
                $_POST['city_id']	=	$_POST['city_idss'];
            }
			unset($_POST['province_idss'],$_POST['city_idss']);
		}
		// app打开其他小程序需要获取原始id
		if($_POST['app_wxapp_id']){
        	$wxapp = M('Wxapp_other')->field(true)->where(['appid'=>$_POST['app_wxapp_id']])->find();
        	$_POST['app_wxapp_username'] = $wxapp['username'];
		}
		$_POST['url'] = htmlspecialchars_decode($_POST['url']);
		$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
        //时间展示
        if ($_POST['effect_date_type'] == 1) {
            $_POST['start_date'] = $_POST['end_date'] = null;
            $_POST['week'] = [];
        }
        if ($_POST['effect_time_type'] == 1) {
            $_POST['start_time'] = $_POST['end_time'] = null;
        }
        $_POST['week'] = implode(',', $_POST['week']);
		if($database_slider->data($_POST)->save()){
			D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $_POST['id'], 'slider');
			S('slider_list_'.$_POST['cat_id'],NULL);
			if($_POST['pic']){
				if(strpos($now_slider['pic'],'2014/') === false){
					unlink('./upload/slider/'.$now_slider['pic']); 
				}
			}
            // 多语言
            $lang_model = D('Lang');
            $lang_model->edit_lang_data('Slider',$_POST);
			$this->frame_submit_tips(1,'编辑成功！');
		}else{
			$this->frame_submit_tips(0,'编辑失败！请重试~');
		}
	}
	
	public function slider_del(){
		$database_slider = D('Slider');
		$condition_slider['id'] = $_POST['id'];
		$now_slider = $database_slider->field(true)->where($condition_slider)->find();
		if($database_slider->where($condition_slider)->delete()){
			S('slider_list_'.$now_slider['cat_id'],NULL);
			if($now_slider['pic']){
				if(strpos($now_slider['pic'],'2014/') === false){
					unlink('./upload/slider/'.$now_slider['pic']); 
				}
			}
			$this->success('删除成功');
		}else{
			$this->error('删除失败！请重试~');
		}
	}
	
	protected function get_category($cat_id){
		$database_slider_category  = D('Slider_category');
		$condition_slider_category['cat_id'] = $cat_id;
		$now_category = $database_slider_category->field(true)->where($condition_slider_category)->find();
		return $now_category;
	}
	protected function frame_check_get_category($cat_id){
		$now_category = $this->get_category($cat_id);
		if(empty($now_category)){
			$this->frame_error_tips('分类不存在！');
		}else{
			return $now_category;
		}
	}
	protected function check_get_category($cat_id){
		$now_category = $this->get_category($cat_id);
		if(empty($now_category)){
			$this->error_tips('分类不存在！');
		}else{
			return $now_category;
		}
	}
}
?>