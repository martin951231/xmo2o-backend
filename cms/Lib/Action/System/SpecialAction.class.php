<?php
/*
 * 专题管理
 */
class SpecialAction extends BaseAction{
	public function shop(){
		$province_id = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
        $city_id = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
                $area_id = $this->system_session['area_id'];
                $city_id = $now_area['area_pid'];
                $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                $province_id = $temp_area['area_pid'];
            } elseif($now_area['area_type'] == 2) {
                $area_index = 'city_id';
                $city_id = $this->system_session['area_id'];
                $province_id = $now_area['area_pid'];
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
                $province_id = $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }

        if ($province_id) {
            $condition_special['province_id'] = $province_id;
        }
        if ($city_id) {
            $condition_special['city_id'] = $city_id;
        }

		$database_special = D('Special');
		$condition_special['type'] = '1';
        $count_special = $now_area['area_type'] == 3 ? 0 : $database_special->where($condition_special)->count();
        import('@.ORG.system_page');
        $p = new Page($count_special, 15);
        $special_list = $now_area['area_type'] == 3 ? [] :$database_special->field(true)->where($condition_special)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $cityids = array_column($special_list, 'city_id');
        $citys_column = [];
        if($cityids){
	        $citysinfo = D('Area')->where(['area_id'=>['in',$cityids]])->field("area_name,area_id")->select();
	        $citys_column = array_column($citysinfo, 'area_name', 'area_id');
	    }
        if($special_list){
			foreach($special_list as &$v){
				$v['url']	= $this->config['site_url'].D('House_village')->base_url.'pages/shop_new/shopSpecial/shopSpecial?id='.$v['pigcms_id'];
				$v['city_name'] = $citys_column[$v['city_id']];
			}
        }
		$this->assign('special_list', $special_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

		$this->display();
	}

	//商城专题页面
	public function mall(){
		$database_special = D('Special');
		$condition_special['type'] = 3;
        $count_special = $database_special->where($condition_special)->count();
        import('@.ORG.system_page');
        $p = new Page($count_special, 15);
        $special_list = $database_special->field(true)->where($condition_special)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if($special_list){
			foreach($special_list as &$v){
				$v['url']	=	$this->config['site_url'].'/wap.php?c=Special&id='.$v['pigcms_id'];
			}
        }
		$this->assign('special_list', $special_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
	}	

	# 专题二维码 2016-12-13 hanlu
	public function shop_qr_code(){
		$pigcms_id	=	$_GET['pigcms_id'];
		$special	=	M('Special')->field(true)->where(array('pigcms'=>$pigcms_id))->find();
		if($special){
			$special['url']	=	$this->config['site_url'].'/wap.php?c=Special&id='.$special['pigcms_id'];
		}
		$this->assign('special', $special);
		$this->display();
	}
	public function shop_add(){
		$many_city	=	$this->config['many_city'];
		$this->assign('many_city',$many_city);
		$this->display();
	}
	public function motify(){
		$data_special['name'] = $_POST['name'];
		$data_special['desc'] = $_POST['desc'];
		if($_POST['currency'] == 1){
			$data_special['province_id'] = 0;
			$data_special['city_id'] = 0;
		}
		else{
			$data_special['province_id'] = $_POST['provinceid'] ? $_POST['provinceid'] : 0;
			$data_special['city_id'] = $_POST['cityid'] ? $_POST['cityid'] : 0;
		}
		$data_special['desc'] = $_POST['desc'];
		$data_special['bgcolor'] = $_POST['bgcolor'];
		$data_special['image'] = $_POST['image'];
		$data_special['coupon'] = $_POST['coupon'] ? serialize($_POST['coupon']) : '';
		$data_special['product_list'] = serialize($_POST['product_list']);
		$data_special['type'] = $_POST['type'];
		$data_special['status'] = '1';
		$data_special['last_time'] = $_SERVER['REQUEST_TIME'];
		if(M('Special')->data($data_special)->add()){
			$this->success('添加成功');
		}else{
			$this->error('添加失败，请重试');
		}
	}
	public function amend(){
		$condition_special['pigcms_id'] = $_POST['pigcms_id'];
		$data_special['name'] = $_POST['name'];
		$data_special['desc'] = $_POST['desc'];
		if($_POST['currency'] == 1){
			$data_special['province_id'] = 0;
			$data_special['city_id'] = 0;
		}
		else{
			$data_special['province_id'] = $_POST['provinceid'];
			$data_special['city_id'] = $_POST['cityid'];
		}
		$data_special['bgcolor'] = $_POST['bgcolor'];
		$data_special['image'] = $_POST['image'];
		$data_special['coupon'] = $_POST['coupon'] ? serialize($_POST['coupon']) : '';
		$data_special['product_list'] = serialize($_POST['product_list']);
		$data_special['last_time'] = $_SERVER['REQUEST_TIME'];
		if(M('Special')->where($condition_special)->data($data_special)->save()){
			$this->success('编辑成功');
		}else{
			$this->error('编辑失败，请重试');
		}
	}
	public function del(){
		$condition_special['pigcms_id'] = $_POST['id'];
		if(M('Special')->where($condition_special)->delete()){
			D("System_log")->add_log("删除了".$this->config['mall_alias_name']."专题页面",$this->config['mall_alias_name'],$_POST['id']);
			$this->success('删除成功');
		}else{
			$this->error('删除失败，请重试');
		}
	}
	public function shop_edit(){
		$database_special = D('Special');
		$condition_special['pigcms_id'] = $_GET['id'];
        $now_special = $database_special->where($condition_special)->find();
        if($now_special['province_id'] && $now_special['city_id']){
        	$now_special['currency'] = 2;
        }
        else{
        	$now_special['currency'] = 1;	
        }
		if($now_special['coupon']){
			$now_special['coupon'] = unserialize($now_special['coupon']);
		}
		$now_special['product_list'] = unserialize($now_special['product_list']);
		$now_special['image']=replace_file_domain($now_special['image']);
		foreach($now_special['product_list'] as $key=>$value){
			foreach ($value['product'] as $k2 => $v2) {
				$v2['name'] = trim($v2['name']);
				$v2['name'] = rtrim($v2['name'], ' ');
				$v2['image'] = replace_file_domain($v2['image']);
				$value['product'][$k2]=$v2;
			}
			$value['name'] = trim($value['name']);
			$value['name'] = rtrim($value['name'],'	');
			$now_special['product_list'][$key]['name'] = trim($value['name']);
		}
        $product_list_json = json_encode($now_special['product_list'], JSON_UNESCAPED_UNICODE|JSON_HEX_APOS);
		$product_list_json = str_replace(array("\\t"), "", $product_list_json);
		$this->assign('now_special', $now_special);
		$this->assign('product_list_json', $product_list_json);
		$this->assign('now_special', $now_special);
		$this->display();
	}

	//添加商城专题
	public function mall_save(){
		$pigcms_id=I('id','','intval');
		$model=M('Special');
		if(IS_POST){
			$data_special=array();
			$data_special['name'] = $_POST['name'];
			$data_special['type'] = 3;
			$data_special['desc'] = $_POST['desc'];
			$data_special['bgcolor'] = $_POST['bgcolor'];
			$data_special['image'] = $_POST['image'];
			$data_special['coupon'] = $_POST['coupon'] ? serialize($_POST['coupon']) : '';
			$data_special['product_list'] = serialize($_POST['product_list']);
			$data_special['last_time'] = $_SERVER['REQUEST_TIME'];
			if($pigcms_id>0){
				$data_special['pigcms_id']=$pigcms_id;
				$res=$model->data($data_special)->save();
				if($res){
					D("System_log")->add_log("编辑了".$this->config['mall_alias_name']."专题页面",$this->config['mall_alias_name'],$res);
					$this->success('编辑成功');
				}else{
					$this->error('编辑失败，请重试');
				}
			}else{
				$data_special['status']=1;
				$res=$model->data($data_special)->add();
				if($res){
					D("System_log")->add_log("添加了".$this->config['mall_alias_name']."专题页面",$this->config['mall_alias_name'],$res);
					$this->success('添加成功');
				}else{
					$this->error('添加失败，请重试');
				}
			}
		}else{
			if($pigcms_id>0){
				$info=$model->where(array('pigcms_id'=>$pigcms_id))->find();
				if($info['coupon']){
					$info['coupon']=unserialize($info['coupon']);
				}
				$info['product_list']=unserialize($info['product_list']);
				$info['image']=replace_file_domain($info['image']);
				foreach ($info['product_list'] as $k => $v) {
					foreach ($v['product'] as $k2 => $v2) {
						$v2['image']=replace_file_domain($v2['image']);
						$v['product'][$k2]=$v2;
					}
					$v['name']=trim($v['name'],' ');
					$info['product_list'][$k]=$v;
				}
				$product_list_json=json_encode($info['product_list'], JSON_UNESCAPED_UNICODE|JSON_HEX_APOS);
				$this->assign('now_special',$info);
				$this->assign('product_list_json',$product_list_json);
			}
			$this->display();	
		}
	}

	public function choose_shop(){
		$province_id = I("province_id");
		$city_id = I("city_id");
		$this->assign('bg_color','#F3F3F3');

		$database_merchant_store = D('Merchant_store');
		$condition_merchant_store['have_shop'] = '1';
		$condition_merchant_store['status'] = '1';
		$where="`s`.`store_id`=`ss`.`store_id` AND `s`.`status`='1' AND `s`.`have_shop`='1'";
		if($province_id){
			$where .= " and `s`.province_id=".$province_id;
		}
		if($city_id){
			$where .= " and `s`.city_id=".$city_id;
		}
		if($_GET['search']){
			$where.=" AND s.name like '%{$_GET['search']}%'";
		}
		$count_store = M()->table(array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant_store_shop'=>'ss'))->where($where)->count();

		import('@.ORG.system_page');
		$p = new Page($count_store, 30);

		$store_list = M()->field("`s`.`store_id`, `s`.`pic_info`, `s`.`name`, `s`.`adress`, `s`.`phone`, `s`.`sort`,`ss`.`sale_count`,`ss`.`deliver_type`")->table(array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant_store_shop'=>'ss'))->where($where)->order("`s`.`sort` DESC,`s`.`store_id` ASC")->limit($p->firstRow . ',' . $p->listRows)->select();

		$store_image_class = new store_image();
		foreach($store_list as &$value){
			$tmp_pic_arr = explode(';',$value['pic_info']);
			$value['image'] = $store_image_class->get_image_by_path($tmp_pic_arr[0]);
		}

		$this->assign('store_list', $store_list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function choose_shop_coupon(){
		$province_id = I("province_id");
		$city_id = I("city_id");
		$where = [];
        if ($province_id || $city_id) {
            $commomCond = '(province_id = 0 AND city_id = 0)';
            $specialCond = '';
            if ($province_id) {
                $specialCond .= 'province_id = ' . $province_id;
            }
            if ($city_id) {
                if ($specialCond) {
                    $specialCond .= ' AND city_id = ' . $city_id;
                } else {
                    $specialCond .= 'city_id = ' . $city_id;
                }
            }
            $where['_string'] = '(' . $commomCond . ' OR (' . $specialCond . '))';
        }

		$this->assign('bg_color','#F3F3F3');
		$coupon  = D('System_coupon');
		$coupon_list = $coupon->get_coupon_list($where);
		$this->assign('coupon_list', $coupon_list);
		$this->display();
	}

	//选择商城商品
	public function choice_mall_goods(){
		$this->assign('bg_color','#F3F3F3');
		$where=array();
		//店铺状态
		$where['st.status']=1;
		$where['st.have_shop']=1;
		$where['gd.cat_fid']=array('gt',0);
		$where['gd.status']=1;
		//关键字筛选
		$search=I('search','','trim');
		if(!empty($search)){
			$where['gd.name']=array('like','%'.$search.'%');
		}
		$count=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on st.store_id=gd.store_id')->where($where)->count(1);
		import('@.ORG.system_page');
		$page= new Page($count, 20);
		$list=M('shop_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'merchant_store st on st.store_id=gd.store_id')->where($where)->limit($page->firstRow,$page->listRows)->field('gd.goods_id,gd.name,gd.image,gd.price,gd.sell_count')->order('gd.sell_count desc')->select();
		$goods_image=new goods_image();
		foreach ($list as $k => $v) {
			$img_arr=D('Shop_goods')->reg_good_img($v['image'],$goods_image);
			$v['image']= is_array($img_arr['0']['url']) ? $img_arr['0']['url']['s_image'] : $img_arr['0']['url'];
			$list[$k]=$v;
		}
		$this->assign('list',$list);
		$pagebar = $page->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function ajax_upload_pic(){
		$dom_id = $_POST['id'];
		if($_FILES['file']['error'] != 4){
			$image = D('Image')->handle($this->system_session['id'], 'special');
			if ($image['error']) {
				die('{"jsonrpc":"2.0","result":{"error_code":1001,"message":"'.$image['msg'].'"},"id":"'.$dom_id.'"}');
			} else {
				echo json_encode(array('error_code'=>0,'url'=>$image['url']['file']));
				// die('{"jsonrpc":"2.0","result":{"error_code":0,"pigcms_id":'.$pigcms_id.',"url":"'.$image['url']['file'].'"},"id":"'.$dom_id.'"}');
			}
		}else{
			die('{"jsonrpc":"2.0","result":{"error_code":1000,"message":"没有选择图片！"},"id":"'.$dom_id.'"}');
		}
	}


	/**批发市场-专题*/
    public function wholesale(){
        $database_special = D('Special');
        $condition_special['type'] = 4;
        $count_special = $database_special->where($condition_special)->count();
        import('@.ORG.system_page');
        $p = new Page($count_special, 15);
        $special_list = $database_special->field(true)->where($condition_special)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if($special_list){
            foreach($special_list as &$v){
                $v['url']	=	$this->config['site_url'].'/wap.php?c=Special&id='.$v['pigcms_id'];
            }
        }
        $this->assign('special_list', $special_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }
    //添加批发专题
    public function wholesale_save(){
        $pigcms_id=I('id','','intval');
        $model=M('Special');
        if(IS_POST){
            $data_special=array();
            $data_special['name'] = $_POST['name'];
            $data_special['type'] = 4;
            $data_special['desc'] = $_POST['desc'];
            $data_special['bgcolor'] = $_POST['bgcolor'];
            $data_special['image'] = $_POST['image'];
            $data_special['coupon'] = $_POST['coupon'] ? serialize($_POST['coupon']) : '';
            $data_special['product_list'] = serialize($_POST['product_list']);
            $data_special['last_time'] = $_SERVER['REQUEST_TIME'];
            if($pigcms_id>0){
                $data_special['pigcms_id']=$pigcms_id;
                $res=$model->data($data_special)->save();
                if($res){
                    $this->success('编辑成功');
                }else{
                    $this->error('编辑失败，请重试');
                }
            }else{
                $data_special['status']=1;
                $res=$model->data($data_special)->add();
                if($res){
                    $this->success('添加成功');
                }else{
                    $this->error('添加失败，请重试');
                }
            }
        }else{
            if($pigcms_id>0){
                $info=$model->where(array('pigcms_id'=>$pigcms_id))->find();
                if($info['coupon']){
                    $info['coupon']=unserialize($info['coupon']);
                }
                $info['product_list']=unserialize($info['product_list']);
                $info['image']=replace_file_domain($info['image']);
                foreach ($info['product_list'] as $k => $v) {
                    foreach ($v['product'] as $k2 => $v2) {
                        $v2['image']=replace_file_domain($v2['image']);
                        $v['product'][$k2]=$v2;
                    }
                    $v['name']=trim($v['name'],' ');
                    $info['product_list'][$k]=$v;
                }
                $product_list_json=json_encode($info['product_list'], JSON_UNESCAPED_UNICODE|JSON_HEX_APOS);
                $this->assign('now_special',$info);
                $this->assign('product_list_json',$product_list_json);
            }
            $this->display();
        }
    }
    //选择商城商品
    public function choice_wholesale_goods(){
        $this->assign('bg_color','#F3F3F3');
        //店铺状态
        $data['status']=1;
        //关键字筛选
        $search=I('search','','trim');
        if(!empty($search)){
            $data['key'] = $search;
        }

        $result = D('Market_goods')->getList($data, 'other');
        if (!empty($result) && $result['orders']) {
            $list = $result['orders'];
        } else {
            $list = array();
        }

        $this->assign('search',$search);
        $this->assign('list',$list);
        $this->assign('pagebar', $result['pagebar']);
        $this->display();
    }

	//预约专题页面
	public function appoint(){
		$database_special = D('Special');
		$condition_special['type'] = 5;
		$count_special = $database_special->where($condition_special)->count();
		import('@.ORG.system_page');
		$p = new Page($count_special, 15);
		$special_list = $database_special->field(true)->where($condition_special)->order('`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
		if($special_list){
			foreach($special_list as &$v){
				$v['url']	=	$this->config['site_url'].'/wap.php?c=Special&id='.$v['pigcms_id'];
			}
		}
		$this->assign('special_list', $special_list);
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	//添加预约专题
	public function appoint_save(){
		$pigcms_id=I('id','','intval');
		$model=M('Special');
		if(IS_POST){
			$data_special=array();
			$data_special['name'] = $_POST['name'];
			$data_special['type'] = 5;
			$data_special['desc'] = $_POST['desc'];
			$data_special['bgcolor'] = $_POST['bgcolor'];
			$data_special['image'] = $_POST['image'];
			$data_special['coupon'] = $_POST['coupon'] ? serialize($_POST['coupon']) : '';
			$data_special['product_list'] = serialize($_POST['product_list']);
			$data_special['last_time'] = $_SERVER['REQUEST_TIME'];
			if($pigcms_id>0){
				$data_special['pigcms_id']=$pigcms_id;
				$res=$model->data($data_special)->save();
				if($res){
					$this->success('编辑成功');
				}else{
					$this->error('编辑失败，请重试');
				}
			}else{
				$data_special['status']=1;
				$res=$model->data($data_special)->add();
				if($res){
					$this->success('添加成功');
				}else{
					$this->error('添加失败，请重试');
				}
			}
		}else{
			if($pigcms_id>0){
				$info=$model->where(array('pigcms_id'=>$pigcms_id))->find();
				if($info['coupon']){
					$info['coupon']=unserialize($info['coupon']);
				}
				$info['product_list']=unserialize($info['product_list']);
				$info['image']=replace_file_domain($info['image']);
				foreach ($info['product_list'] as $k => $v) {
					foreach ($v['product'] as $k2 => $v2) {
						$v2['image']=replace_file_domain($v2['image']);
						$v['product'][$k2]=$v2;
					}
					$v['name']=trim($v['name'],' ');
					$info['product_list'][$k]=$v;
				}
				$product_list_json=json_encode($info['product_list'], JSON_UNESCAPED_UNICODE|JSON_HEX_APOS);
				$this->assign('now_special',$info);
				$this->assign('product_list_json',$product_list_json);
			}
			$this->display();
		}
	}


	public function choice_appoint_goods(){
		$this->assign('bg_color','#F3F3F3');
		$where=array();
		//店铺状态

		$where['appoint_status'] = 0;
		$where['check_status'] = 1;
		//关键字筛选
		$search=I('search','','trim');
		if(!empty($search)){
			$where['appoint_name']=array('like','%'.$search.'%');
		}
		$count = M('Appoint')->where($where)->count();
		import('@.ORG.system_page');
		$page= new Page($count, 20);
		$list = M('Appoint')->where($where)->limit($page->firstRow,$page->listRows)->order('appoint_id desc')->select();
		$group_image_class = new appoint_image();
		foreach ($list as $k => &$v) {
			$v['all_pic'] = $group_image_class->get_allImage_by_path($v['pic']);
			$v['sell_count'] = M('Appoint_order')->where(array('appoint_id'=>$v['appoint_id']))->count();
		}

		$this->assign('list',$list);
		$pagebar = $page->show();
		$this->assign('pagebar', $pagebar);
		$this->display();
	}
}