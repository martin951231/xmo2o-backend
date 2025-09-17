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
			if($database_adver_category->data($_POST)->save()){
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
        $assign_type=true;
        $village_id = $this->house_session['village_id'];
		$this->assign('bg_color','white');
        $type=0;
        $condition_adver['_string'] =' (village_id = '.$village_id.')';
        $cat_key_num='三';
		if($_GET['cat_key']){
			$database_adver_category  = D('House_adver_category');
			$condition_adver_category['cat_key'] = $_GET['cat_key'];
			$now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
            if($_GET['cat_key'] == 'house_four_adver'){
                $type=4;
            }elseif ($_GET['cat_key'] == 'house_center_adver'){
                $type=5;
                $cat_key_num='一';
            }elseif ($_GET['cat_key'] == 'house_footer_adver'){
                $type=6;
                $cat_key_num='五';
            }elseif ($_GET['cat_key'] == 'house_app_index_top'){
                $type=1;
            }elseif ($_GET['cat_key'] == 'house_app_index_center'){
                $type=3;
            }
            //todo 可视化广告位
            if($type > 0){
                $assign_=D('House_visualize_assign')->house_visualize_assign($village_id,$type);
                if(isset($assign_['type_'.$type])){
                    $assign_type=$assign_['type_'.$type];
                }
                if(!$assign_['type_0']){
                    $condition_adver['_string'] .= '  or ((village_id =0) and (filter_village_ids is null OR  !FIND_IN_SET('.$village_id.', filter_village_ids )))';
                }
            }
		}else{
			$now_category = $this->check_get_category($_GET['cat_id']);
		}

        $jz_open = D('Config_customization')->getWSXCustomizedOpen();
        if ($jz_open && in_array($_GET['cat_key'], ['house_app_index_top', 'house_app_index_center'])) {
            if ($_GET['cat_key'] == 'house_app_index_top') {
                $chooseSize = [
                    ['label' => '640*278', 'value' => '640*278'],
                    ['label' => '640*558', 'value' => '640*558'],
                ];
                $this->assign('chooseSizeTitle', '轮播广告图尺寸');
            }
            if ($_GET['cat_key'] == 'house_app_index_center') {
                $chooseSize = [
                    ['label' => '640*206', 'value' => '640*206'],
                    ['label' => '640*132', 'value' => '640*132'],
                ];
                $this->assign('chooseSizeTitle', '中间轮播广告图尺寸');
            }
            $this->assign('chooseSize', $chooseSize);
            $this->assign('size_info', $now_category['size_info']);
        }

        $this->assign('now_category', $now_category);
        $many_city = $this->config['many_city'];
        $database_adver = D('House_adver');
        $condition_adver['cat_id'] = $now_category['cat_id'];
        $condition_adver['status'] =  array('neq',4);
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
				$condition_adver['city_id'] = array('in',$area_id);
			}else{
				$condition_adver['city_id'] = $area_id;
			}
        }
        $adver_list = $database_adver->field(true)->where($condition_adver)->order('village_id asc ,id DESC')->select();
        if($adver_list){
            foreach ($adver_list as &$v){
                if(!empty($v['pic']) && substr($v['pic'],0,6) == '000/00'){
                    $v['pic']='/upload/adver/'.$v['pic'];
                }
            }
        }
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
        $this->assign('adver_list', $adver_list);
        $this->assign('many_city', $many_city);

		$now_village = D('House_village')->where(array('village_id'=>$this->house_session['village_id']))->find();
		$where_field = "(";
		if($now_village){
			if($now_village['province_id']){
				$where_field .= " province_id =".$now_village['province_id'];
			}
			if($now_village['city_id']){

				$where_field .= " AND city_id =".$now_village['city_id'];
			}
			if($now_village['area_id']){

				$where_field .= " AND area_id =".$now_village['area_id'];
			}
			if($now_village['street_id']){

				$where_field .= " AND street_id =".$now_village['street_id'];
			}
			if($now_village['community_id']){

				$where_field .= " AND community_id =".$now_village['community_id'];
			}
		}

        $where_field .= " AND cat_id = ".$now_category['cat_id']." AND status=1) OR ( province_id =0 AND city_id = 0 AND area_id=0 AND street_id=0 AND community_id=0  AND cat_id = ".$now_category['cat_id']." AND village_id=0 AND status=1 )";
		$database_adver = D('House_adver');
		$adver_count = $database_adver->where($where_field)->count();
		if($adver_count > 0){
			$this->assign('adver_count',$adver_count);
		}else{
			$this->assign('adver_count',0);
		}

        $now_village_info = D('House_village_info')->where(array('village_id'=>$this->house_session['village_id']))->field('fillet_radius')->find();
        $this->assign('fillet_radius',$now_village_info['fillet_radius'] );

        C('customization', D('Config_customization')->get_config());
        // 营山定制开关
        $huizhisq = !empty(C('customization.huizhisq')) ? true : false;
        $this->assign('yingshan',$huizhisq);
        $this->assign('cat_key_num', $cat_key_num);
        $this->assign('assign_type',$assign_type);
        $this->display();
    }
    
    public function setAdvertSize() {
        $village_id = $this->house_session['village_id'];
        $cat_key = $_POST['cat_key'];
        $size_info = $_POST['size_info'];
        $jz_open = D('Config_customization')->getWSXCustomizedOpen();
        if ($jz_open && in_array($cat_key, ['house_app_index_top', 'house_app_index_center'])) {
            $condition_adver_category['cat_key'] = $cat_key;
            $database_adver_category  = D('House_adver_category');
            $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
            if ($now_category) {
                $database_adver_category->where($condition_adver_category)->setField('size_info', $size_info);
                exit(json_encode(array('status'=>1,'msg'=>'设置成功！')));
            }
        } else {
            exit(json_encode(array('status'=>0,'msg'=>'目前类别不支持设置尺寸。')));
        }
    }

    public function adver_add()
    {
        $many_city = $this->config['many_city'];
        $this->assign('many_city', $many_city);
        $this->assign('bg_color', '#F3F3F3');
        $now_category = $this->frame_check_get_category($_GET['cat_id']);

        $this->assign('now_category', $now_category);

        // 营山定制配置项
        C('customization', D('Config_customization')->get_config());
        // 营山定制开关
        $huizhisq = !empty(C('customization.huizhisq')) ? true : false;
        $this->assign('yingshan',$huizhisq);
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list',$wxapp_list);
        $this->display();
    }

	public function adver_modify(){
		$image = D('Image')->handle($this->house_session['village_id'], 'adver', 0, array('size' => 10), false);
		if (!$image['error']) {
//			$_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
            $_POST = array_merge($_POST, $image['url']);
		} else {
			$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
		}
		
		if($_POST['currency'] == 1 || empty($_POST['province_idss'])){
			$_POST['province_id']	=	0;
			$_POST['city_id']	=	0;
		}else{
			$_POST['province_id']	=	$_POST['province_idss'];
			$_POST['city_id']	=	$_POST['city_idss'];
			unset($_POST['province_idss'],$_POST['city_idss']);
		}
		
		$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
		$_POST['url'] = htmlspecialchars_decode($_POST['url']);
		$_POST['village_id'] = $this->house_session['village_id'];
		$database_adver = D('House_adver');
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

		$database_adver = D('House_adver');
		$condition_adver['id'] = $_GET['id'];
		$now_adver = $database_adver->field(true)->where($condition_adver)->find();
		if(empty($now_adver)){
			$this->frame_error_tips('该广告不存在！');
		}

        if(!empty($now_adver['pic']) && substr($now_adver['pic'],0,6) == '000/00'){
            $now_adver['pic']='/upload/adver/'.$now_adver['pic'];
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

        // 营山定制配置项
        C('customization', D('Config_customization')->get_config());
        // 营山定制开关
        $huizhisq = !empty(C('customization.huizhisq')) ? true : false;
        $this->assign('yingshan',$huizhisq);

		$this->display();
	}

	public function adver_amend(){
		$database_adver = D('House_adver');
		$condition_adver['id'] = $_POST['id'];
        $village_id = $this->house_session['village_id'];
		$now_adver = $database_adver->field(true)->where($condition_adver)->find();
		if(!$now_adver){
            $this->frame_submit_tips(0,'数据不存在');
        }
		if($_FILES['pic']['error'] != 4){
			$image = D('Image')->handle($this->house_session['village_id'], 'adver', 0, array('size' => 10), false);
			if (!$image['error']) {
//				$_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
                $_POST = array_merge($_POST,  $image['url']);
			} else {
				$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
			}
		}
		if($_POST['currency'] == 1){
			$_POST['province_id']	=	0;
			$_POST['city_id']	=	0;
		}else{
			$_POST['province_id']	=	$_POST['province_idss'];
			$_POST['city_id']	=	$_POST['city_idss'];
			unset($_POST['province_idss'],$_POST['city_idss']);
		}
		$_POST['last_time'] = $_SERVER['REQUEST_TIME'];
		$_POST['url'] = htmlspecialchars_decode($_POST['url']);
        $now_adver['url']=htmlspecialchars_decode($now_adver['url']);
		$database_adver = D('House_adver');

        //todo 小区复制平台广告 生成属于小区的广告
        $rel=D('House_link')->get_filter_village_id($village_id,$now_adver);
        if(!empty($rel)){
            $insert_data=$now_adver;
            unset($insert_data['id'],$insert_data['filter_village_ids']);
            $insert_data['village_id']=$village_id;
            $insert_data['name']=empty($_POST['name']) ? $now_adver['name'] : $_POST['name'];
            $insert_data['sub_name']=empty($_POST['sub_name']) ? $now_adver['sub_name'] : $_POST['sub_name'];
            $insert_data['bg_color']=empty($_POST['bg_color']) ? $now_adver['bg_color'] : $_POST['bg_color'];
            $insert_data['pic']=empty($_POST['pic']) ? $now_adver['pic'] : $_POST['pic'];
            $insert_data['sort']=empty($_POST['sort']) ? $now_adver['sort'] : $_POST['sort'];
            $insert_data['last_time']=$_SERVER['REQUEST_TIME'];
            $insert_data['status']=isset($_POST['status']) ? $_POST['status']:0;
            $insert_data['is_platform']=0;
            $insert_data['source_type']=0;
            $insert_data['url']=D('House_link')->replace_village_parameter((empty($_POST['url']) ? $now_adver['url'] : $_POST['url']),$village_id);
            if(isset($_POST['wxapp_id'])){
                $insert_data['wxapp_id']=trim($_POST['wxapp_id']);
                $insert_data['wxapp_page']=trim($_POST['wxapp_page']);
            }
            // 原广告数据写入关联village_ids
            $database_adver->where(['id'=>$now_adver['id']])->data(['filter_village_ids'=>implode(',',$rel)])->save();
            $database_adver->data($insert_data)->add();
            $this->frame_submit_tips(1,'编辑成功！');
        }else{
            $rr=$database_adver->data($_POST)->save();
            if($rr){
                D('Image')->update_table_id('/upload/adver/' . $_POST['pic'], $_POST['id'], 'adver');
                D('Config')->clearCache();
                if($_POST['pic']){
                    //unlink('./upload/adver/'.$now_adver['pic']);
                }
                $this->frame_submit_tips(1,'编辑成功！');
            }else{
                $this->frame_submit_tips(0,'编辑失败！请重试~');
            }
        }

	}

	public function adver_del(){
		$database_adver = D('House_adver');
		$condition_adver['id'] = $_POST['id'];
        $village_id = $this->house_session['village_id'];
		$now_adver = $database_adver->field(true)->where($condition_adver)->find();
        if(!$now_adver){
            $this->error('数据不存在!');
        }
        //todo 小区复制平台广告 生成属于小区的广告
        $rel=D('House_link')->get_filter_village_id($village_id,$now_adver,'del');
        if(!empty($rel)){
            $database_adver->where(['id'=>$now_adver['id']])->data(['filter_village_ids'=>implode(',',$rel)])->save();
            $this->success('删除成功');
        }else{
            if($database_adver->where($condition_adver)->delete()){
                //unlink('./upload/adver/'.$now_adver['pic']);
                D('Config')->clearCache();
                $this->success('删除成功');
            }else{
                $this->error('删除失败！请重试~');
            }
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

	protected function get_category($cat_id){
		$database_adver_category  = D('House_adver_category');
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

    /**
     * 宫格广告推荐位
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 10:17
     */
    public function grid_num_list(){
        $grid_num = remove_xss($_GET['grid_num']);
        $source = remove_xss($_GET['source']);
        $village_id = $this->house_session['village_id'];
        if($source == 'sys'){
            $village_id = 0;
        }

        if(!in_array($grid_num,[3,4])){
            exit('<html><head><script>window.top.msg(0,"宫格参数错误",true,2);window.parent.closeiframe();</script></head></html>');
        }
        if($grid_num == 3){
            $title = '三宫格推荐广告位';
            $type = 9;
        }else{
            $type = 8;
            $title = '四宫格推荐广告位';
        }

        $assign_type=D('House_visualize_assign')->house_visualize_assign($this->house_session['village_id'],$type);

        // 获取宫格列表
        $condition_six_adver = [];
        $database_six_adver  = D('Mall_six_adver');
        $condition_six_adver['_string'] =' village_id = '.$village_id;
        if(!$assign_type['type_0']){
            $condition_six_adver['_string'] .= '  or village_id = 0';
        }
        $condition_six_adver['grid_num'] = $grid_num;
        // 总条数
        $adver_count = $database_six_adver->where($condition_six_adver)->count();

        // 分页
        import('@.ORG.merchant_page');
        $p = new Page($adver_count,10,'page');
        // 列表
        $six_adver = $database_six_adver->field(true)->where($condition_six_adver)->order('sort DESC,id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($six_adver as $key => $value){
            $six_adver[$key]['start_time'] = date('Y-m-d H:i',$value['start_time']);
            $six_adver[$key]['end_time'] = date('Y-m-d H:i',$value['end_time']);
            switch ($value['type']){
                case 'limited':
                    $six_adver[$key]['type'] = '秒杀活动';
                    break;
                case 'bargain':
                    $six_adver[$key]['type'] = '砍价活动';
                    break;
                default:
                    $six_adver[$key]['type'] = '拼团活动';
                    break;
            }
        }
        $result['list'] = $six_adver;
        $result['pagebar'] = $p->show();

        $this->assign('result',$result);
        $this->assign('title',$title);
        $this->assign('grid_num',$grid_num);
        $this->assign('assign_type',$assign_type);
        $this->display();
    }

    /**
     * 宫格广告推荐位新增
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 10:17
     */
    public function grid_num_add(){
        if(IS_POST){

            if((!empty($_FILES['icon_image']) && $_FILES['icon_image']['error'] != 4) || (!empty($_FILES['bg_image']) && $_FILES['bg_image']['error'] != 4)){
                // 多图
                $image = D('Image')->handle($this->house_session['village_id'], 'adver', 0, array('size' => 10), false);
                if (!$image['error']) {
                    $_POST = array_merge($_POST, $image['url']);
                    $_POST['bg_image'] = htmlspecialchars_decode($_POST['bg_image']);
                } else {
                    exit('<html><head><script>window.top.msg(0,'.($image['msg'] ? $image['msg'] : $image['message']).',true,2);window.parent.closeiframe();</script></head></html>');
                }
            }

            $data = $_POST;
            if(!empty($data['bg_color'])){
                $data['bg_color'] = RGBToHex($data['bg_color']);
            }
            if(!empty($data['bg_floor'])){
                $data['bg_floor'] = RGBToHex($data['bg_floor']);
            }
            if(!empty($data['field_color'])){
                $data['field_color'] = RGBToHex($data['field_color']);
            }
            $time_arr = explode(' - ',$data['start_time']);
            $data['start_time'] = strtotime($time_arr[0]);
            $data['end_time'] = strtotime($time_arr[1]);
            if($data['source'] == 'sys'){
                $data['village_id'] = 0;
            }else{
                $data['village_id'] = $this->house_session['village_id'];
            }
            unset($data['source']);
            $database_six_adver  = D('Mall_six_adver');
            if($id = $database_six_adver->data($data)->add()){
                D('Image')->update_table_id($_POST['icon_image'], $id, 'adver');

                D('Config')->clearCache();

                exit('<html><head><script>window.top.msg(1,"添加成功",true,2);window.parent.closeiframe();</script></head></html>');
            }else{
                exit('<html><head><script>window.top.msg(0,"添加失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
            }
        }
        $grid_num = remove_xss($_GET['grid_num']);
        if(!in_array($grid_num,[3,4])){
            exit('<html><head><script>window.top.msg(0,"宫格参数错误",true,2);window.parent.closeiframe();</script></head></html>');
        }
        if($grid_num == 3){
            $title = '三宫格推荐广告位';
        }else{
            $title = '四宫格推荐广告位';
        }
        $this->assign('title',$title);
        $this->assign('grid_num',$grid_num);
        $this->display();
    }

    /**
     * 宫格广告推荐位编辑
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 13:43
     */
    public function grid_num_edit(){
        if(IS_POST){
            $database_six_adver  = D('Mall_six_adver');
            $condition_adver['id'] = remove_xss($_POST['id']);
            $six_adver = $database_six_adver->field('id')->where($condition_adver)->find();
            if(!$six_adver){
                exit('<html><head><script>window.top.msg(0,"数据不存在",true,2);window.parent.closeiframe();</script></head></html>');
            }
            if((!empty($_FILES['icon_image']) && $_FILES['icon_image']['error'] != 4) || (!empty($_FILES['bg_image']) && $_FILES['bg_image']['error'] != 4)){
                // 多图
                $image = D('Image')->handle($this->house_session['village_id'], 'adver', 0, array('size' => 10), false);
                if (!$image['error']) {
                    $_POST = array_merge($_POST,  $image['url']);
                    $_POST['icon_image'] = htmlspecialchars_decode($_POST['icon_image']);
                } else {
                    exit('<html><head><script>window.top.msg(0,'.($image['msg'] ? $image['msg'] : $image['message']).',true,2);window.parent.closeiframe();</script></head></html>');
                }
            }

            unset($_POST['id']);
            unset($_POST['dosubmit']);
            if(!empty($_POST['bg_color']) && strpos($_POST['bg_color'],'rgb') !== false){
                $_POST['bg_color'] = RGBToHex($_POST['bg_color']);
            }
            if(!empty($_POST['bg_floor']) && strpos($_POST['bg_floor'],'rgb') !== false){
                $_POST['bg_floor'] = RGBToHex($_POST['bg_floor']);
            }
            if(!empty($_POST['field_color']) && strpos($_POST['field_color'],'rgb') !== false){
                $_POST['field_color'] = RGBToHex($_POST['field_color']);
            }
            $time_arr = explode(' - ',$_POST['start_time']);
            $_POST['start_time'] = strtotime($time_arr[0]);
            $_POST['end_time'] = strtotime($time_arr[1]);
            if($id = $database_six_adver->where($condition_adver)->data($_POST)->save()){
                if(isset($_POST['icon_image'])){
                    D('Image')->update_table_id($_POST['icon_image'], $_POST['id'], 'adver');
                    D('Config')->clearCache();
                    if($six_adver['icon_image']){
                       // unlink('.'.$six_adver['icon_image']);
                    }
                }
                if(isset($_POST['bg_image'])){
                    D('Image')->update_table_id($_POST['bg_image'], $_POST['id'], 'adver');
                    D('Config')->clearCache();
                    if($six_adver['bg_image']){
                       // unlink('.'.$six_adver['bg_image']);
                    }
                }
                exit('<html><head><script>window.top.msg(1,"添加成功",true,2);window.parent.closeiframe();</script></head></html>');
            }else{
                exit('<html><head><script>window.top.msg(0,"添加失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
            }
        }
        $id = remove_xss($_GET['id']);
        if(!$id){
            exit('<html><head><script>window.top.msg(0,"宫格参数错误",true,2);window.parent.closeiframe();</script></head></html>');
        }
        $database_six_adver  = D('Mall_six_adver');
        $condition_adver['id'] = $id;
        $six_adver = $database_six_adver->where($condition_adver)->find();
        $six_adver['start_time'] = date('Y-m-d H:i:s',$six_adver['start_time']);
        $six_adver['end_time'] = date('Y-m-d H:i:s',$six_adver['end_time']);
        if(empty($six_adver)){
            $this->frame_error_tips('该宫格广告不存在！');
        }

        $this->assign('six_adver',$six_adver);
        $this->display();
    }

    /**
     * 宫格广告推荐位删除
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 14:44
     */
    public function grid_num_del(){
        $database_six_adver  = D('Mall_six_adver');
        $condition_adver['id'] = remove_xss($_POST['id']);
        $six_adver = $database_six_adver->field('id')->where($condition_adver)->find();
        if(!$six_adver){
            exit('<html><head><script>window.top.msg(0,"数据不存在",true,2);window.parent.closeiframe();</script></head></html>');
        }
        if($database_six_adver->where($condition_adver)->delete()){
            if($six_adver['bg_image']){
                unlink('.'.$six_adver['bg_image']);
            }
            if($six_adver['icon_image']){
                unlink('.'.$six_adver['icon_image']);
            }
            D('Config')->clearCache();
            $this->success('删除成功');
        }else{
            $this->error('删除失败！请重试~');
        }
    }

    /**
     * 关联广告位商品页面
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 14:59
     */
    public function grid_num_add_goods(){
        $id = remove_xss($_GET['id']);
        if(!$id){
            exit('<html><head><script>window.top.msg(0,"宫格参数错误",true,2);window.parent.closeiframe();</script></head></html>');
        }

        $this->assign('id',$id);
        $this->display();
    }

    /**
     * 活动商品列表
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 16:06
     */
    public function active_goods_list(){
        $id = remove_xss($_GET['id']);
        $database_six_adver  = D('Mall_six_adver');
        $database_six_adver_goods  = D('Mall_six_adver_goods');
        $condition_adver['id'] = $id;
        $six_adver = $database_six_adver->where($condition_adver)->field('type')->find();
        if(empty($six_adver)){
            $return['code'] = 1;
            $return['msg'] = '该宫格广告不存在!';
            exit(json_encode($return));
        }

        $keywords = remove_xss($_POST['keywords']);
        $isRecommend = 0;
        $start_time = remove_xss($_POST['start_time']);
        $end_time = remove_xss($_POST['end_time']);
        $status = remove_xss($_POST['status']);

        $url = '\app\mall\model\service\MallActivityRecommendService/getGroupRecommendList';
        if($six_adver['type'] == 'limited'){
            $url = '\app\mall\model\service\MallActivityRecommendService/getLimitedRecommendList';
        }elseif ($six_adver['type'] == 'bargain'){
            $url = '\app\mall\model\service\MallActivityRecommendService/getBargainRecommendList';
        }

        $param = [];
        $goods_id = [];
        // 查询已有关联商品ID
        $goods_list = $database_six_adver_goods->where(array('adver_id' => $id))->field('goods_id')->select();
        if(!empty($goods_list)){
            $goods_id = array_column($goods_list,'goods_id');
            if(!empty($status)){
                // 未关联
                if($status == 1){
                    $param['unrelated'] = $goods_id;
                }else{
                    // 已关联
                    $param['related'] = $goods_id;
                }
            }
        }else{
            // 没有关联商品 选择已关联商品时
            if($status == 2){
                $return['code'] = 0;
                $return['msg'] = '商品列表';
                $return['data'] = [];
                $return['count'] = 0;
                exit(json_encode($return));
            }
        }

        $param['keywords'] = $keywords;
        $param['isRecommend'] = $isRecommend;
        $param['start_time'] = $start_time;
        $param['end_time'] = $end_time;
        // 活动商品列表
        $data = invoke_v20_service($url,[$param])['data']['retval'];
        if(!empty($data['list'])){
            foreach ($data['list'] as $key => $value){
                $data['list'][$key]['is_related'] = 0;
                if(!empty($goods_id) && in_array($value['act_goods_id'],$goods_id)){
                    $data['list'][$key]['is_related'] = 1;
                }
            }
        }else{
            $data['list'] = [];
            $data['count'] = 0;
        }

        $return['code'] = 0;
        $return['msg'] = '商品列表';
        $return['data'] = $data['list'];
        $return['count'] = $data['count'];
        exit(json_encode($return));
    }

    /**
     * 关联和取消关联
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 17:56
     */
    public function relateGoods(){
        $id = remove_xss($_GET['id']);
        $goods_id = remove_xss($_POST['goods_id']);
        $type = remove_xss($_POST['type']);

        $database_six_adver_goods  = D('Mall_six_adver_goods');
        $database_six_adver  = D('Mall_six_adver');

        $temp = [
            'goods_id' => $goods_id,
            'adver_id' => $id
        ];
        // 添加
        if($type == 'add'){
            $six_adver = $database_six_adver->where(array('id' => $id))->field('grid_num')->find();
            $count = $database_six_adver_goods->where(array('adver_id' => $id))->count();
            if($six_adver['grid_num'] == 3){
                $max = 3;
            }else{
                $max = 8;
            }
            if($count >= $max){
                exit(json_encode(array('status'=>1,'msg'=>'已达到宫格推荐广告位最大关联商品数量，不能再关联商品')));
            }
            $res = $database_six_adver_goods->data($temp)->add();
        }else{
            $res = $database_six_adver_goods->where($temp)->delete();
        }
        if($res){
            exit(json_encode(array('status'=>0,'msg'=>'操作成功')));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'操作失败')));
        }
    }

}
?>