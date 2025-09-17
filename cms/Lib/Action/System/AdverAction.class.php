<?php
/*
 * 广告管理
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/06 16:47
 *
 */
class AdverAction extends BaseAction{

	public function index(){
		$database_adver_category  = D('Adver_category');
		$cat_type = isset($_GET['cat_type'])?$_GET['cat_type']:0;
		$keyword  = isset($_GET['keyword'])?$_GET['keyword']:'';
		$where = array();
		if($cat_type){
			$where['cat_type'] = $cat_type-1;
		}
		if($keyword){
			$where['cat_name'] = array('like','%'.$keyword.'%');
		}
		$category_list = $database_adver_category->field(true)->where($where)->order('`cat_id` ASC')->select();
		$this->assign('category_list',$category_list);
		$this->display();
	}

	public function cat_add(){
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}

	public function cat_modify(){
		if(IS_POST){
			$database_adver_category  = D('Adver_category');
			if($database_adver_category->data($_POST)->add()){
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
			$database_adver_category  = D('Adver_category');
			if($database_adver_category->data($_POST)->save() !== false){
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
			$database_adver_category  = D('Adver_category');
			$condition_adver_category['cat_id'] = $_POST['cat_id'];
			$now_category = $database_adver_category->where($condition_adver_category)->field(true)->find();
			if($now_category['is_system']==1){
				$this->error('系统自带广告位，请勿删除！');return false;
			}
			if($database_adver_category->where($condition_adver_category)->delete()){
				//删除所有广告
				$database_adver = D('Adver');
				$condition_adver['cat_id'] = $now_category['cat_id'];
				$adver_list = $database_adver->field(true)->where($condition_adver)->order('`id` DESC')->select();
				foreach($adver_list as $key=>$value){
					unlink('./upload/adver/'.$value['pic']);
				}
				$database_adver->where($condition_adver)->delete();

				D('Config')->clearCache();
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

    public function adver_list()
    {
		$this->assign('bg_color','white');
		if($_GET['cat_key']){
			$database_adver_category  = D('Adver_category');
			$condition_adver_category['cat_key'] = $_GET['cat_key'];
			$now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
		}else{
			$now_category = $this->check_get_category($_GET['cat_id']);
		}
		$this->assign('shop_id', 0);
		if($_GET['parentid']){
			$database_shop_category = D(('shop_category'));
			$category = $database_shop_category->field(true)->where(array('cat_id' => $_GET['parentid']))->find();
			$now_category['cat_name'] = $category['cat_name'].' - '.$now_category['cat_name'];
			$this->assign('shop_id', $_GET['parentid']);
		}
		
        $this->assign('now_category', $now_category);
        $many_city = $this->config['many_city'];
		$database_adver = D('Adver');
		$condition_adver['cat_id'] = $now_category['cat_id'];

		if($_GET['parentid']){
			$condition_adver['shop_id'] = $_GET['parentid'];
		}
        $area_id = $this->system_session['area_id'];
		//如果是区域管理员进来
        if($area_id&&C('customization.open_view_multi_city')!=1){
            if ($this->system_session['level'] == 1) {
                $temp = M('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if ($temp['area_type'] == 1) {
                    $city_list = D('Area')->get_arealist_by_areaPid($temp['area_id'], true);
                    $area_id = array();
                    foreach ($city_list as $value) {
                        $area_id[] = $value['area_id'];
                    }
                } else if ($temp['area_type'] == 2) {
                    $area_id = $temp['area_id'];
                } else {
                    $area_id = $temp['area_pid'];
                }
            }
            if (is_array($area_id)) {
                $condition_adver['city_id'] = array('in', $area_id);
            } else {
                $condition_adver['city_id'] = $area_id;
            }

        }
        $adver_list = $database_adver->field(true)->where($condition_adver)->order('`sort` DESC, `id` DESC')->select();
        if (($many_city == 1||C('customization.open_view_multi_city')==1) && $adver_list) {
            foreach ($adver_list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)&&$v['city_id']!=-1) {
                    $v['city_id'] = '通用';
                } else {
                    if(($this->system_session['open_admin_area']==1&&C('customization.open_view_multi_city')==1)||empty($city)){
                        $v['city_id'] = '不通用';
                    }else{
                        $v['city_id'] = $city['area_name'];
                    }

                }
            }
        }
        $this->assign('adver_list', $adver_list);
        $this->assign('many_city', $many_city);
        $this->display();
    }
    /*
     * 设置首页导航透明度
     */
    public function sliderOpacitySet()
    {
        if(!empty($_POST)){
            if(!is_numeric($_POST['index_slider_opacity'])){
                $this->frame_submit_tips(0, '请正确填写数字！'); 
            }
            D('Config')->where(array('name' => 'index_slider_opacity'))->save(array('value' =>$_POST['index_slider_opacity']));
            $this->frame_submit_tips(1, '设置成功');
        }
        $config = D('Config')->where(array('name' => 'index_slider_opacity'))->field('value')->find();
        $this->assign('index_slider_opacity', $config['value']);

        $this->display();
    }

    /*
    * 首页分类设置
    */
    public function classifySet()
    {
        if(!empty($_POST)){
            D('Config')->where(['name' => 'wap_home_show_classify'])->save(['value' => $_POST['wap_home_show_classify']]);
            D('Config')->where(['name' => 'classify_name_desc'])->save(['value' => $_POST['classify_name_desc']]);
            //删除缓存
            D('Config')->clearDataCacheDir('system_config');
            
            $this->frame_submit_tips(1, '设置成功');
        }
        $classifyConfig = D('Config')
            ->where(['name' =>['IN',['wap_home_show_classify', 'classify_name_desc']]])
            ->field('name, value')
            ->select();

        $classifyConfig = array_column($classifyConfig, 'value', 'name');
        $this->assign($classifyConfig);

        $this->display();
    }
    
    /*
     * 首页轮播图及广告配置
     */
    public function adverSet()
    {
        if(!empty($_POST)){
            if(
                (isset($_FILES['index_background_pic']) && $_FILES['index_background_pic']['error'] != 4)
                ||
                (isset($_FILES['index_adver_pic']) && $_FILES['index_adver_pic']['error'] != 4)
            ){
                $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
                if (!$image['error']) {
                    $_POST = array_merge($_POST,$image['url']);
                } else {
                    $this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
                }
            }
            
            $picConfig = D('Config')
                ->where(['name'=> ['IN', [
                    'index_background_pic',
                    'index_adver_pic',
                ]]])
                ->where(['status' => 1])
                ->field('name, value')
                ->select();
            $picConfig = array_column($picConfig, 'value', 'name');
            if(empty($_POST['index_background_pic'])){
                $_POST['index_background_pic'] = $picConfig['index_background_pic'];
            }
            if(empty($_POST['index_adver_pic'])){
                $_POST['index_adver_pic'] = $picConfig['index_adver_pic'];
            }
            if(empty($_POST['index_background_pic']) && empty($picConfig['index_background_pic'])){
                $this->frame_submit_tips(0, L_('请上传背景图片！'));
            }
            if(empty($_POST['index_adver_pic']) && empty($picConfig['index_adver_pic'])){
                $this->frame_submit_tips(0, L_('请上传广告图片！'));
            }
            $config = [
                'index_background_pic',
                'index_adver_pic',
                'index_adver_pic_jump_url',
                'index_view_pic_status',
                'index_adver_status',
            ];
            $_POST['index_adver_pic_jump_url'] = htmlspecialchars_decode($_POST['index_adver_pic_jump_url']);
            foreach ($config as $v){
                D('Config')->where(array('name' => $v))->save(array('value' => $_POST[$v])); 
            }
            $this->frame_submit_tips(1, '设置成功');
        }
        $config = D('Config')
            ->where(['name' => ['IN', [
                    'index_background_pic', 
                    'index_adver_pic', 
                    'index_adver_pic_jump_url',
                    'index_view_pic_status',
                    'index_adver_status',
                    ]]])
            ->where(['status' => 1])
            ->field('name, value')
            ->select();
        $config = array_column($config, 'value', 'name');
        
        $config['index_background_pic'] && $config['index_background_pic'] = replace_file_domain($config['index_background_pic']);
        $config['index_adver_pic'] && $config['index_adver_pic'] = replace_file_domain($config['index_adver_pic']);
        
        $this->assign($config);
        
        $this->display();
    }
    
    public function adver_add()
    {
        $many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
        $this->assign('bg_color', '#F3F3F3');
        $now_category = $this->frame_check_get_category($_GET['cat_id']);

        $this->assign('now_category', $now_category);
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list', $wxapp_list);
        //ios列表
        $app_list = M('App_other')->field(true)->select();
        $this->assign('app_list', $app_list);
	//是否为区域管理员并且开启可视化多模块设置
        $is_area_many_city = 0;
        if($this->system_session['open_admin_area']==1&&C('customization.open_view_multi_city')==1){
            $is_area_many_city = 1;
        }
        $this->assign('is_area_many_city',$is_area_many_city);
        $customization = D('Config_customization')->get_config();
        $this->assign('customization',$customization);

        $cardLableMerList = M('Employee_card_lable')->join('as label left join pigcms_merchant m ON m.mer_id = label.mer_id')->field('m.mer_id,m.name')
            ->where(['m.status'=>1])->group('label.mer_id')->order('label.mer_id ASC')->select();
        $this->assign('cardLableMerList',$cardLableMerList);
        $this->display();
    }

	public function adver_modify(){
		$image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
		if (!$image['error']) {
			$_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
		} else {
			$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
		}
		
		if($_GET['shop_id']){
			$_POST['shop_id']	=	$_GET['shop_id'];
		}

		if($_POST['currency'] == 1 || (empty($_POST['province_idss'])&&C('customization.open_view_multi_city')!=1)){
			$_POST['province_id']	=	0;
			$_POST['city_id']	=	0;
            $_POST['city_ids'] = '';
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
		
		$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
		$_POST['url'] = htmlspecialchars_decode($_POST['url']);
		$database_adver = D('Adver');
        if(isset($_POST['selectMer']) && !empty($_POST['selectMer'])){
            $staff=[];
            foreach ($_POST['selectMer'] as $value){
                if(isset($_POST['label_'.$value]) && !empty($_POST['label_'.$value])){
                    $arr['mer_id']=$value;
                    $arr['ids']=$_POST['label_'.$value];
                    $staff[]=$arr;
                }
                unset($_POST['label_'.$value]);
            }
            if(!empty($staff)){
                $_POST['assigns_staff']=serialize($staff);
            }
        }
        unset($_POST['selectMer']);
		if($id = $database_adver->data($_POST)->add()){
			D('Image')->update_table_id('/upload/adver/' . $_POST['pic'], $id, 'adver');
			
			D('Config')->clearCache();
			
			$this->frame_submit_tips(1,'添加成功！');
		}else{
			$this->frame_submit_tips(0,'添加失败！请重试~');
		}
	}

	public function adver_edit(){
		$many_city	=	$this->config['many_city'];
		$this->assign('many_city',$many_city);
		$this->assign('bg_color','#F3F3F3');

		$database_adver = D('Adver');
		$condition_adver['id'] = $_GET['id'];
		$now_adver = $database_adver->field(true)->where($condition_adver)->find();
		if(empty($now_adver)){
			$this->frame_error_tips('该广告不存在！');
		}
		$this->assign('now_adver',$now_adver);

		$now_category = $this->frame_check_get_category($now_adver['cat_id']);
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
	
        $customization = D('Config_customization')->get_config();
        $this->assign('customization',$customization);
        if($customization['life_tools']){
            $cardLableMerList = M('Employee_card_lable')->join('as label left join pigcms_merchant m ON m.mer_id = label.mer_id')->field('m.mer_id,m.name')
                ->where(['m.status'=>1])->group('label.mer_id')->order('label.mer_id ASC')->select();
            $this->assign('cardLableMerList',$cardLableMerList);

            if(!empty($now_adver['assigns_staff'])){
                $staffArr=unserialize($now_adver['assigns_staff']);
                $staff_mer=[];
                //$staff_ids=[];
                foreach ($staffArr as $staff){
                    $staff_mer[$staff['mer_id']]['mer_id']=$staff['mer_id'];
                    $staff_mer[$staff['mer_id']]['ids']=$staff['ids'];
                    $staff_mer[$staff['mer_id']]['all_ids']=M('Employee_card_lable')->where(array('mer_id' => $staff['mer_id'], 'is_del' => 0))->select();
                        foreach ($staff_mer[$staff['mer_id']]['all_ids'] as $k=>$all_ids){
                            //$staff_mer[$staff['mer_id']]['all_ids'][$k]['checked']=false;
                            foreach ($staff_mer[$staff['mer_id']]['ids'] as $ids){
                             if($all_ids['id']==$ids){
                                 $staff_mer[$staff['mer_id']]['all_ids'][$k]['checked']=true;
                             }
                        }
                    }
                }
                $now_adver['assigns_staff']=$staff_mer;
                $this->assign('assigns_staff', $now_adver['assigns_staff']);
            }
        }
        $this->display();
	}
    //获取员工标签
    public function ajax_getLabel(){
        $condition_adver['mer_id'] = $_POST['mer_id'];
        $labelList=M('Employee_card_lable')->where(array('mer_id' => $_POST['mer_id'], 'is_del' => 0))->select();
        exit(json_encode($labelList));
    }

	public function adver_amend(){
		$database_adver = D('Adver');
		$condition_adver['id'] = $_POST['id'];
		$now_adver = $database_adver->field(true)->where($condition_adver)->find();

		if($_FILES['pic']['error'] != 4){
			$image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
			if (!$image['error']) {
				$_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
			} else {
				$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
			}
		}
		if($_POST['currency'] == 1 || (empty($_POST['province_idss'])&&C('customization.open_view_multi_city')!=1)){
			$_POST['province_id']	=	0;
			$_POST['city_id']	=	0;
            $_POST['city_ids'] = '';
		}else{
            if(C('customization.open_view_multi_city')==1) {
                $_POST['province_id']	=	0;
                $_POST['city_id']	=	-1;
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
		$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
		$_POST['url'] = htmlspecialchars_decode($_POST['url']);
		$database_adver = D('Adver');
		if(isset($_POST['selectMer']) && !empty($_POST['selectMer'])){
		    $staff=[];
		    foreach ($_POST['selectMer'] as $value){
              if(isset($_POST['label_'.$value]) && !empty($_POST['label_'.$value])){
                  $arr['mer_id']=$value;
                  $arr['ids']=$_POST['label_'.$value];
                  $staff[]=$arr;
              }
                unset($_POST['label_'.$value]);
            }
		    if(!empty($staff)){
                $_POST['assigns_staff']=serialize($staff);
            }else{
	        	$_POST['assigns_staff']= '';
	        }
        }else{
        	$_POST['assigns_staff']= '';
        }
		unset($_POST['selectMer']);
		if($database_adver->data($_POST)->save()){
			D('Image')->update_table_id('/upload/adver/' . $_POST['pic'], $_POST['id'], 'adver');
			D('Config')->clearCache();
			if($_POST['pic']){
				unlink('./upload/adver/'.$now_adver['pic']);
			}
			$this->frame_submit_tips(1,'编辑成功！');
		}else{
			$this->frame_submit_tips(0,'编辑失败！请重试~');
		}
	}

	public function adver_del(){
		$database_adver = D('Adver');
		$condition_adver['id'] = $_POST['id'];
		$now_adver = $database_adver->field(true)->where($condition_adver)->find();
		if($database_adver->where($condition_adver)->delete()){
			unlink('./upload/adver/'.$now_adver['pic']);
			D('Config')->clearCache();
			$this->success('删除成功');
		}else{
			$this->error('删除失败！请重试~');
		}
	}

	# 获取省
	public function ajax_province(){
		$database_area = D('Area');
		$condition_area['area_type'] = 1;
		$condition_area['is_open'] = 1;
		$province_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		if(count($province_list) == 1){
			$return['error'] = 2;
			$return['id'] = $province_list[0]['id'];
			$return['name'] = $province_list[0]['name'];
		}else if(!empty($province_list)){
			$return['error'] = 0;
			$return['list'] = $province_list;
		}else{
			$return['error'] = 1;
			$return['info'] = '没有开启了的省份！请先开启。';
		}
		exit(json_encode($return));
	}

	# 获取市
	public function ajax_city(){
		$database_area = D('Area');
		$condition_area['area_pid'] = intval($_POST['id']);
		$condition_area['is_open'] = 1;
		$city_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
		if(count($city_list) == 1 && !$_POST['type']){
			$return['error'] = 2;
			$return['id'] = $city_list[0]['id'];
			$return['name'] = $city_list[0]['name'];
		}else if(!empty($city_list)){
			$return['error'] = 0;
			$return['list'] = $city_list;
		}else{
			$return['error'] = 1;
			$return['info'] = '［ <b>'.$_POST['name'] .'</b> ］ 省份下没有已开启的城市！请先开启城市或删除此省份';
		}
		exit(json_encode($return));
	}

	/*
	 * app 全屏广告
	 * */
	public function app_fullscreen_adver(){
		$condition = array();
		if ($this->system_session['area_id']) {
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
				$condition['city_id'] = array('in',$area_id);
			}else{
				$condition['city_id'] = $area_id;
			}
        }
		import('@.ORG.system_page');
		$count = M('App_fullscreen_adver')->where($condition)->count();
		$p = new Page($count,20);
		$adver_list = M('App_fullscreen_adver')->where($condition)->limit($p->firstRow,$p->listRows)->select();
		$many_city = $this->config['many_city'];
		if ($many_city == 1 && $adver_list) {
            foreach ($adver_list as &$v) {
                $city = M('Area')->field('area_name')->where(array('area_id' => $v['city_id']))->find();
                if (empty($city)) {
                    $v['city_id'] = '通用';
                } else {
                    $v['city_id'] = $city['area_name'];
                }
            }
        }
		$this->assign('adver_list',$adver_list);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	public function app_fullscreen_add(){
		if(IS_POST){

			if(empty($_POST['name']) || empty($_POST['ios_pic_s']) || empty($_POST['ios_pic_b']) || empty($_POST['ios_pic_bb']) || empty($_POST['android_pic']) || empty($_POST['url'])|| empty($_POST['begin_time'])|| empty($_POST['end_time'])|| empty($_POST['play_time'])){
				$this->error('数据不全');
			}
			if($_POST['play_time']<3 || $_POST['play_time']>9){
				$this->error("播放时间请设置3至9秒的范围");
			}
			$_POST['begin_time'] =strtotime($_POST['begin_time']." 00:00:00");
			$_POST['end_time'] = strtotime($_POST['end_time']." 23:59:59");
            $app_screen_opening_area = C('customization.app_screen_opening_area');
			if($_POST['currency'] == 1 || ((!$app_screen_opening_area&&empty($_POST['province_idss']))||($app_screen_opening_area&&empty($_POST['province_id'])))){
				$_POST['province_id']	=	0;
				$_POST['city_id']	=	0;
                $_POST['area_id']	=	0;
			}else{
                $_POST['province_id']	=	$app_screen_opening_area?$_POST['province_id']:$_POST['province_idss'];
                $_POST['city_id']	=	$app_screen_opening_area?$_POST['city_id']:$_POST['city_idss'];
				unset($_POST['province_idss'],$_POST['city_idss']);
			}
			if ($_POST['begin_time']>$_POST['end_time']) {
				$this->error("结束时间应大于开始时间");
			}
			$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
			if(M('App_fullscreen_adver')->add($_POST)){
				$this->success('添加成功');
			}else{
				$this->error('添加失败');
			}
		}else{
			$many_city = $this->config['many_city'];
        	$this->assign('many_city', $many_city);
			$this->display();
		}
	}

	public function app_fullscreen_edit(){
		if(IS_POST){
			if(empty($_POST['name']) || empty($_POST['ios_pic_s']) || empty($_POST['ios_pic_b']) || empty($_POST['android_pic']) || empty($_POST['url']) || empty($_POST['begin_time'])|| empty($_POST['end_time'])|| empty($_POST['play_time'])){
				$this->error('数据不全');
			}
			$_POST['begin_time'] =strtotime($_POST['begin_time']." 00:00:00");
			$_POST['end_time'] = strtotime($_POST['end_time']." 23:59:59");
			$app_screen_opening_area = C('customization.app_screen_opening_area');
			if($_POST['currency'] == 1 || ((!$app_screen_opening_area&&empty($_POST['province_idss']))||($app_screen_opening_area&&empty($_POST['province_id'])))){
				$_POST['province_id']	=	0;
				$_POST['city_id']	=	0;
                $_POST['area_id']	=	0;
			}else{
				$_POST['province_id']	=	$app_screen_opening_area?$_POST['province_id']:$_POST['province_idss'];
				$_POST['city_id']	=	$app_screen_opening_area?$_POST['city_id']:$_POST['city_idss'];
				unset($_POST['province_idss'],$_POST['city_idss']);
			}
			if ($_POST['begin_time']>$_POST['end_time']) {
				$this->frame_submit_tips(0,"结束时间应大于开始时间");
			}
			$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
			if(M('App_fullscreen_adver')->where(array('id'=>$_POST['id']))->save($_POST)){
				$this->frame_submit_tips(1,'编辑成功');
			}else{

				$this->frame_submit_tips(0,'编辑失败');
			}
		}else{
			$many_city = $this->config['many_city'];
        	$this->assign('many_city', $many_city);
			$now_adver  =M('App_fullscreen_adver')->where(array('id'=>$_GET['id']))->find();
			$this->assign('now_adver',$now_adver);
			$this->display();
		}
	}

	public function app_fullscreen_del(){
		if(IS_POST){
			$database_adver_category  = D('App_fullscreen_adver');
			$condition_adver_category['id'] = $_POST['id'];
			if($database_adver_category->where($condition_adver_category)->delete()){
				$this->success('删除成功');
			}else{
				$this->frame_submit_tips(0,'删除失败！请重试~');
			}
		}else{
			$this->frame_submit_tips(0,'非法提交,请重新提交~');
		}
	}

	protected function get_category($cat_id){
		$database_adver_category  = D('Adver_category');
		$condition_adver_category['cat_id'] = $cat_id;
		$now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
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