<?php
/*
 * 团购管理
 *
 */

class Village_groupAction extends BaseAction{
    public function index_(){
		$database_group_category = D('Village_group');
		$condition_group_category['cat_status'] = array('neq','4');
		$count_group_category = $database_group_category->where($condition_group_category)->count();
		import('@.ORG.system_page');
		$p = new Page($count_group_category,20);
		$group_list = $database_group_category->field(true)->where($condition_group_category)->order('group_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$this->assign('group_list',$group_list);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		$this->display();
    }

	public function add_group(){
		if(IS_POST){
			$_POST['begin_time'] = strtotime($_POST['begin_time']);
			$_POST['end_time'] = strtotime($_POST['end_time']);
			if(!$_POST['share_image']){
				unset($_POST['share_image']);
			}
			if($_POST['group_id']){
				$res = M('Village_group')->where(array('group_id'=>$_POST['group_id']))->save($_POST);
			}else{
				$res = M('Village_group')->add($_POST);
			}
			if($res)	{
				$this->success('编辑成功！');
			}else{
				$this->error('编辑失败！请重试~');
			}
		}else{
			$res = M('Village_group')->where(array('group_id'=>$_GET['group_id']))->find();

			$this->assign('group',$res);
			$this->display();
		}
	}

	public function index(){
		$database_group_category = D('Village_group_goods');
		$condition_group_category['status'] = array('neq','4');

		if($_GET['searchstatus']>0){
			switch($_GET['searchstatus']){
				case '1':
					$condition_group_category['_string'] = 'status=1 AND start_time<'.time();

					break;
				case '2': //已售罄
					$condition_group_category['_string'] = 'status=2 or (stock_num<=sell_count AND  start_time<'.time().' AND get_time+86400 > '.time().')';
					break;
				case '3': //还未开始
					$condition_group_category['_string'] = 'status=1 AND start_time>'.time();
					break;

				case '4': //已过期
					$condition_group_category['_string'] = 'status=3 or (end_time<'.time().' and end_time <> 0)';
					break;

				case '5': //结束
					$condition_group_category['_string'] = 'status=5';
					break;
			}

		}else{
			$condition_group_category['_string'] = 'status>0';
		}

		$_GET['cat_fid'] && $condition_group_category['cat_fid'] = $_GET['cat_fid'];
		$_GET['cat_id'] && $condition_group_category['cat_id'] = $_GET['cat_id'];

		if($_GET['searchtype'] &&  $_GET['keyword']!=''){
			if($_GET['searchtype']=='name'){
				$condition_group_category['name'] = array("like","%".$_GET['keyword']."%");
				//unset($condition_group_category['cat_fid']);
			}else{
				$condition_group_category[$_GET['searchtype']] = $_GET['keyword'];
			}

		}

		/* 处理分城市管理 start */
        $city_id = '';
        $origin = '';
        $area_name = '';
        if(C('config.village_group_city_manage')) {
            $city_id = $_GET['city_id'];
            $origin = $_GET['origin'];

            if($origin == 'city_list') { //处理来源 城市商品列表
                $condition_group_category['city_id'] = $city_id;  //只查询当前 区域等级的商品

                $res = M('Village_group_area')->field('area_id')->where(['pigcms_id'=>$city_id])->find();
                if($res) {
                    $area_data = M('Area')->field('area_name,area_ip_desc')->where(['area_id'=>$res['area_id']])->find();
                    if(empty($area_data['area_ip_desc'])) {
                        $area_name = $area_data['area_name'];
                    } else {
                        $area_name = $area_data['area_ip_desc'];
                    }
                }

            } else { //查询当前区域等级 和 下级及下下级  并且只可编辑当前区域等级的商品

                $city_id = M('Village_group_area')->field('pigcms_id')->where([ 'area_id'=>$this->system_session['area_id']])->find();
                $area_arr[] = $city_id['pigcms_id'];
                switch ($this->system_session['area_type']) {
                    case 1 :
                        $city_ids = M('Village_group_area')->field('pigcms_id, area_id')->where([ 'area_pid'=>$this->system_session['area_id']])->select();

                        foreach ($city_ids as $key=>$value) {
                            $area_arr[] = $value['pigcms_id'];
                            $city_idss = M('Village_group_area')->field('pigcms_id, area_id')->where(['area_pid'=>$value['area_id']])->select();
                            if($city_idss) {
                                foreach ($city_idss as $k => $v) {
                                    $area_arr[] = $v['pigcms_id'];
                                }
                            }
                        }
                        break;
                    case 2 :
                        $city_id = M('Village_group_area')->field('pigcms_id')->where([ 'area_id'=>$this->system_session['area_id']])->find();
                        $area_arr[] = $city_id['pigcms_id'];

                        $city_ids = M('Village_group_area')->field('pigcms_id, area_id')->where([ 'area_pid'=>$this->system_session['area_id']])->select();

                        foreach ($city_ids as $key=>$value) {
                            $area_arr[] = $value['pigcms_id'];
                        }
                        break;
                }

                // 处理省市区
                $where_city = [];
                if ($_GET['city_idss'] || $_GET['area_id']) {
                    if ($_GET['city_idss']) {
                        $where_city = M('Village_group_area')->field('pigcms_id')->where(['area_id'=>$_GET['city_idss']])->find();
                    }
                    if ($_GET['area_id']) {
                        $where_city = M('Village_group_area')->field('pigcms_id')->where(['area_id'=>$_GET['area_id']])->find();
                    }
                    $condition_group_category['city_id'] = $where_city['pigcms_id'];  //当前区域等级
                } else {
                    $condition_group_category['city_id'] = ['in',implode(',',$area_arr)];  //当前区域等级
                }
            }
        }

        /* 处理分城市管理 end */

		import('@.ORG.system_page');
    	$count = $database_group_category->where($condition_group_category)->count();
		$p = new Page($count,20);
		$database_group_category->where(array('end_time'=>array('lt',time()),'_string'=>'end_time <> 0','status'=>array('neq',4)))->setField('status',3);
		//$database_group_category->where(array('get_time'=>array('lt',time()-86400),'status'=>array('neq',4),'village_group_type'=>array('neq',1)))->setField('status',5);
		$group_list = $database_group_category->where($condition_group_category)->order('goods_id DESC,status ASC')->limit($p->firstRow.','.$p->listRows)->select();
		$cateids = array_column($group_list, 'cat_fid');
		$cates = M('Village_group_category')->where(['cat_id'=>['in', $cateids]])->field("cat_id, cat_name")->select();
		$cates_arr = array_column($cates, 'cat_name', 'cat_id');

		$merids = array_column($group_list, 'mer_id');
		if($merids){
			$merchants = array_column(M("Merchant")->where(['mer_id'=>['in', $merids]])->select(), 'name', 'mer_id');
		}

		foreach ($group_list as $key => $value) {
			$group_list[$key]['cat_name'] = $cates_arr[$value['cat_fid']] ? $cates_arr[$value['cat_fid']] : "--";
			$group_list[$key]['mername'] = $merchants[$value['mer_id']] ? $merchants[$value['mer_id']] : "--";

            if(C('config.village_group_city_manage') && empty($origin)) {
                $res = M('Village_group_area')->field('area_id')->where(['pigcms_id'=>$value['city_id']])->find();
                if($res) {
                    $area_data = M('Area')->field('area_name,area_ip_desc')->where(['area_id'=>$res['area_id']])->find();
                    if(empty($area_data['area_ip_desc'])) {
                        $area_name = $area_data['area_name'];
                    } else {
                        $area_name = $area_data['area_ip_desc'];
                    }
                }
                $group_list[$key]['area_name'] = !empty($area_name)?$area_name:'';
            } else {
                $group_list[$key]['area_name'] = !empty($area_name)?$area_name:'';
            }
 
			 //单品销量
			$browse_num = (int)$value['browse_num'];
			$group_list[$key]['browse_num'] = $browse_num >= 10000 ? round(($browse_num / 10000), 1) . '万' : $browse_num;
		}

        $cates = M("Village_group_category")->field("cat_id,cat_name")->where(array('fid'=>0,'status'=>array('neq',4)))->order("`sort` desc")->select();
        $this->assign("cates", $cates);

		$this->assign('city_id', $city_id);
		$this->assign('city_manage', C('config.village_group_city_manage'));
		$this->assign('origin', $origin);
        $this->assign('level', $this->system_session['level']);
		$this->assign('group_list',$group_list);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	public function cate_index(){
		$category = M('Village_group_category')->where(array('status'=>['neq', 4], 'fid'=>0))->order('sort DESC,status DESC')->select();
		foreach ($category as &$item) {
			$item['goods_count'] = M('Village_group_goods')->where(array('status'=>['neq', 4], 'cat_fid'=>$item['cat_id'],'cat_id'=>0))->count();
		}
		$this->assign('category',$category);
		$this->display();  

	}

	public function ajax_get_son_category(){
		$cat_id = intval($_POST['cat_fid']);
		if($cat_id){
			$son_category = M('Village_group_category')->where(['fid'=>$cat_id, 'status'=>1])->field('cat_id,cat_name')->order('cat_id desc')->select();
			$this->success($son_category);
		}else{
			$this->error('cat_fid必传');
		}
		
	}

	//查看子分类
	public function cate_son_index(){
		$cat_fid = intval($_GET['cat_fid']);
		$category = M('Village_group_category')->where(array('status'=>['neq', 4], 'fid'=>$cat_fid))->order('sort DESC,status DESC')->select();
		foreach ($category as &$item) {
			$item['goods_count'] = M('Village_group_goods')->where(array('status'=>['neq', 4], 'cat_fid'=>$cat_fid,'cat_id'=>$item['cat_id']))->count();
		}
		$this->assign('cat_fid ',$cat_fid);
		$this->assign('category',$category);
		$this->display();
	}

	public function add_group_product(){

		if(IS_POST){
			if($_POST['is_copy'] == 1){
				$_POST['goods_id'] = 0;
				$_POST['group_id'] = 0;
			}
			$_POST['user_share_money'] = $_POST['user_share_money'] ?: 0.00;
			$_POST['first_share_money'] = !empty($_POST['first_share_money']) ? $_POST['first_share_money']:0.00;
			$_POST['second_share_money'] = !empty($_POST['second_share_money']) ? $_POST['second_share_money']:0.00;
			$_POST['thild_share_money'] = !empty($_POST['thild_share_money']) ? $_POST['thild_share_money']:0.00;

			if(!$_POST['group_id'] && $_POST['search']&& $_POST['name']){
				$_POST['group_id'] = $_POST['search'];
			}
			// if(!$_POST['group_id']){
			// 	$this->error('没有绑定商家团购不能添加'.$this->config['village_group_alias']);
			// }
			
			//社区团购3.0
			if(empty($_POST['start_time'])){
				$this->error('开始时间必填');
			}
			if($_POST['is_auto_offline'] == 1 && empty($_POST['end_time'])){
				$this->error('自动上下架的商品必须设置结束时间！');
			}
			if($_POST['village_group_type'] != '1'){//普通商品时做如下判断
				if(in_array($_POST['arrive_type'],[1,2,3]) && (empty($_POST['arrive_time_normal']) || empty($_POST['arrive_cutting_time_normal']))){
					$this->error('到达时间和截单时间必填');	
				}
				if(in_array($_POST['arrive_type'],[4]) && (empty($_POST['arrive_days']) || empty($_POST['arrive_time_normal4']) || empty($_POST['arrive_cutting_time_normal4']))){
					$this->error('到达天数、到达时间和截单时间必填');	
				}
                // if(!$_POST['send_time']){
                // 	$this->error('发货时间必填！');	
                // }
				if(in_array($_POST['arrive_type'],[5]) && (empty($_POST['arrive_datetime']) || empty($_POST['arrive_cutting_datetime']))){
					$this->error('到达时间、截单时间必填');	
				}
				if(in_array($_POST['arrive_type'],[1,2,3])){
					$_POST['arrive_days'] = $_POST['arrive_type']-1;
					$_POST['arrive_time'] = strtotime(date('Y-m-d '.$_POST['arrive_time_normal']).':00') - strtotime(date('Y-m-d 00:00:00'));	
					$_POST['arrive_cutting_time'] = strtotime(date('Y-m-d '.$_POST['arrive_cutting_time_normal'])) - strtotime(date('Y-m-d 00:00:00'));	
					if($_POST['arrive_type'] == 1 && $_POST['arrive_time'] < $_POST['arrive_cutting_time']){
						$this->error('到达时间不能小于截单时间');	
					}
				}
				if(in_array($_POST['arrive_type'],[4])){
					$_POST['arrive_time'] = strtotime(date('Y-m-d '.$_POST['arrive_time_normal4']).':00') - strtotime(date('Y-m-d 00:00:00'));	
					$_POST['arrive_cutting_time'] = strtotime(date('Y-m-d '.$_POST['arrive_cutting_time_normal4'])) - strtotime(date('Y-m-d 00:00:00'));	
				}
				if(in_array($_POST['arrive_type'],[5])){
					$_POST['arrive_datetime'] = strtotime($_POST['arrive_datetime']);
					$_POST['arrive_cutting_datetime'] = strtotime($_POST['arrive_cutting_datetime']);
					if($_POST['arrive_datetime'] < $_POST['arrive_cutting_datetime']){
						$this->error('到达时间不能小于截单时间');	
					}
				}
			}
			if(!$_POST['mer_id']){
				if($_POST['searchMer']){
					$condition_merchant['name'] = array('eq',trim($_POST['searchMer']));
			    	$condition_merchant['status'] = array("in", [1,3]);
			    	$mer = D('Merchant')->field("mer_id,name")->where($condition_merchant)->select();
			    	if(!$mer){
						$this->error('当前商家"'.$_POST['searchMer'].'"不存在');	
					}
					$_POST['mer_id'] = $mer[0]['mer_id'];
				}
			}
			
			if($_POST['is_merchant_send'] == '1' && empty($_POST['mer_id'])){
				$this->error('商家发货必须选择一个商家！');	
			}


            $start_time = strtotime($_POST['start_time']);
            $end_time   = strtotime($_POST['end_time']);
            $send_time  = strtotime($_POST['send_time']);
            $get_time   = strtotime($_POST['get_time']);

            if($_POST['end_time'] && $end_time<$start_time){
                $this->error('结束时间不能小于开始时间');
            }
            if($_POST['goods_id']){
                $goods = M('Village_group_goods')->where(array('goods_id'=>$_POST['goods_id']))->find();
            } else {
                $goods = array();
            }
			if (($_POST['village_group_type'] && $_POST['village_group_type']==1) || ($goods && $goods['village_group_type']==1)) {
                if (!$_POST['cycle_type'] || !in_array($_POST['cycle_type'], array(1,2,3))) {
                    $this->error('请选择周期购正确的配送频次');
                }
                if ($_POST['is_all_date'] && $_POST['is_all_date']!=1 && !$_POST['delivery_date']) {
                    $this->error('请选择周期购对应的配送日期');
                }
                if (!$_POST['limit_time']) {
                    $this->error('请选择下单时间限制');
                }
                if ($_POST['is_limit_delay'] && $_POST['is_limit_delay']==1) {
                    if (!$_POST['limit_delay_num']) {
                        $this->error('请填写顺延次数');
                    }
                }
                if (!$_POST['delivery_periods']) {
                    $this->error('请填写配送期数');
                }
                if (intval($_POST['cycle_type'])==1) {
                    $_POST['delivery_date'] = 1;
                } elseif (empty($_POST['delivery_date'])) {
                    $this->error('请选择配送日期');
                }
                $village_group_cycle = array(
                    'cycle_type' => intval($_POST['cycle_type']),
                    'is_all_date' => $_POST['is_all_date']>0?1:0,
                    'delivery_date' => $_POST['delivery_date'],
                    'limit_days' => $_POST['limit_days'],
                    'limit_time' => $_POST['limit_time'],
                    'is_limit_delay' => $_POST['is_limit_delay']>0?1:0,
                    'limit_delay_num' => $_POST['limit_delay_num']>0?$_POST['limit_delay_num']:0,
                    'delivery_periods' => $_POST['delivery_periods'],
                    'add_time' => time(),

                );
            } 
            // elseif ($goods && $goods['village_group_type']!=1) {
            //     $village_group_cycle = array();
            //     if( $send_time<$end_time){
            //         $this->error('发货时间不能小于结束时间');
            //     }
            //     if( $get_time<$send_time){
            //         $this->error('到货时间不能小于发货时间');
            //     }
            // }

            $_POST['start_time'] = $start_time;
            $_POST['end_time'] = $end_time;
            $_POST['send_time'] = $send_time;
            $_POST['get_time'] = $get_time;

			$_POST['content'] = html_entity_decode($_POST['content']);
			preg_match_all('/<img.*?src="(.*?)".*?\/?>/',$_POST['content'],$content);
			$file_domain_url = file_domain();
			foreach($content[1] as $v){
				if(strpos($v,'://')===false){
					$_POST['content'] = str_replace($v,$file_domain_url.$v,$_POST['content']);
				}
			}


			$group_image_class = new group_image();
			$all_pic = $group_image_class->get_allImage_by_path($_POST['pic_list']);
			$_POST['image'] = $all_pic[0]['image'];
			if(empty($_POST['image'])){
				$this->error('没有上传图片，请添加图片');
			}

			if($_POST['stock_num']<=0){
				$this->error('库存不能为0~');
			}

			if($_POST['virtual_sell_count']<=0) {
                $_POST['virtual_sell_count'] = 0;
            }


			if($_POST['buy_user_type']==0){
				$_POST['address_list']='';
			}
            $leveloff = isset($_POST['leveloff']) ? $_POST['leveloff'] : false;
            unset($_POST['leveloff']);
            $new_level_off = array();
            if (!empty($leveloff) && $leveloff) {
                foreach ($leveloff as $kk => $vv) {
                    $vv['type'] = intval($vv['type']);
                    $vv['vv'] = intval($vv['vv']);
                    if (($vv['type'] > 0) && ($vv['vv'] > 0)) {
                        $vv['level'] = $kk;
                        $new_level_off[$kk] = $vv;
                    }
                }
            }
            $_POST['leveloff'] = !empty($new_level_off) ? serialize($new_level_off) : '';

            if (C('config.score_round_two') == 1) {
                $_POST['score_percent'] = sprintf('%.2f',floatval($_POST['score_percent']));
            } else {
                $_POST['score_percent'] = round($_POST['score_percent']);
            }

			if(isset($_POST['score_set_type']) && $_POST['score_set_type'] == 0){
				$_POST['score_percent'] = -1;
			}

			if($_POST['goods_id']){
				$goods = M('Village_group_goods')->where(array('goods_id'=>$_POST['goods_id']))->find();
				$_POST['status'] =1 ;
				if($_POST['end_time'] && $_POST['end_time']<time()){
					$_POST['status'] =3;
				}

				if($_POST['get_time'] && $goods['village_group_type']!=1 && $_POST['get_time']<time()){
					$_POST['status'] =5;
				}

				if($_POST['stock_num']<$goods['sell_count']){
					$this->error('修改库存不能比销量小~');
				}

				if($_POST['stock_num']==$goods['sell_count']){
					$_POST['status'] = 2;
				}

				if($_POST['stock_num']> $goods['sell_count']){
					$_POST['status'] = 1;
				}
                $_POST['last_time'] = time();

				$res = M('Village_group_goods')->where(array('goods_id'=>$_POST['goods_id']))->save($_POST);
				D("System_log")->add_log("编辑了".$this->config['village_group_alias']."商品，商品名称：".$goods['name'],$this->config['village_group_alias'],$_POST['goods_id'],$goods, M('Village_group_goods')->where(array('goods_id'=>$_POST['goods_id']))->find());
                if ($village_group_cycle) {
                    M('Village_group_cycle')->where(array('goods_id'=>$_POST['goods_id']))->save($village_group_cycle);
                }

                //处理 区域下的商品绑定关系
                // if (!$_POST['city_id']) {
                //     $this->error('未选择城市');
                // }
//                if (!$_POST['pigcms_id'] && !$_POST['goods_id']) {
//                    $this->error('请选择商品');
//                }
                if (!$_POST['price']) {
                    $this->error('请输入售价');
                }

                if ($_POST['is_add_price'] == 1) {
                    if (floatval($_POST['min_price'])<0 || floatval($_POST['max_price'])<0) {
                        $this->error('加价值不能小于0');
                    }

                    if (floatval($_POST['min_price'])>floatval($_POST['max_price'])) {
                        $this->error('加价最小值不能大于最大值');
                    }
                }else{
                    $_POST['min_price'] = '0.00';
                    $_POST['max_price'] = '0.00';
                }
                if($_POST['buy_user_type']==0){
                    $_POST['address_list']='';
                }else{
                    $address_list = explode(',', trim($_POST['address_list'],','));
                    $_POST['address_list'] = implode(',',array_unique($address_list));
                }

                $res_a = M('Village_group_goods_bind_area')->field('pigcms_id')->where(['city_id'=>$_POST['city_id'], 'goods_id'=>$_POST['goods_id']])->find();

                //获取商品库存提醒值
                $setInfo = D('Config')->field('name','value')->where(['name'=>['in',['village_group_threshold_warning','village_group_threshold_warning_member','village_group_threshold_warning_times']]])->select();
                if($setInfo){
                    $setArr = array_column($setInfo,'value','name');
                    if(($_POST['stock_num']-$goods['sell_count'])>$setArr['village_group_threshold_warning']){
                        D('Warn_notice')->where(['business'=>'village_group','goods_id'=>$_POST['goods_id'],'status'=>1,'type'=>0])->save(['status'=>0]);
                    }
                }
                
                if ($res_a['pigcms_id']) { // 编辑
                    $_POST['pigcms_id'] = $res_a['pigcms_id'];
                    $_POST['last_time'] = time();
                    if (M('Village_group_goods_bind_area')->save($_POST)) {
                        D("System_log")->add_log("编辑了".$this->config['village_group_alias']."城市商品，城市ID：".$_POST['city_id'],$this->config['village_group_alias'],$_POST['pigcms_id']);
//                        $this->success('修改绑定成功');
                    }else{
                        $this->error('修改失败');
                    }
                }

			}else{
				if($_POST['group_id']){
					if( M('Village_group_goods')->where(array('group_id'=>$_POST['group_id'],'status'=>1))->find()){
						$this->error('该团购已被添加为社区团购商品，不可再次绑定');
					}
				}

				if(empty($_POST['end_time'])){
					$_POST['status'] =1;
				}
				elseif($_POST['end_time']>time()){
					$_POST['status'] =1;
				}else{
					$_POST['status'] =3;
				}

                // if (empty($_POST['city_id'])) {
                //     $this->error('未选择城市');
                // }

				$res = M('Village_group_goods')->add($_POST);

                /**  分城市管理开启时  操作添加商品时绑定 区域级别 start   **/
                if(C('config.village_group_city_manage') == 1) { //开启
                    $database_village_group_goods_bind_area = D('Village_group_goods_bind_area');

//                    $_GET['pigcms_id'] && $condition['pigcms_id'] = $_GET['pigcms_id'];

//                    if (!$_POST['city_id']) {
//                        $this->error('未选择城市');
//                    }
//                        if (!$_POST['pigcms_id'] && !$_POST['goods_id']) {
//                            $this->error('请选择商品');
//                        }
//                        if (!$_POST['price']) {
//                            $this->error('请输入售价');
//                        }

                    if ($_POST['is_add_price'] == 1) {
                        if (floatval($_POST['min_price']) < 0 || floatval($_POST['max_price']) < 0) {
                            $this->error('加价值不能小于0');
                        }

                        if (floatval($_POST['min_price']) > floatval($_POST['max_price'])) {
                            $this->error('加价最小值不能大于最大值');
                        }
                    } else {
                        $_POST['min_price'] = '0.00';
                        $_POST['max_price'] = '0.00';
                    }
                    if ($_POST['buy_user_type'] == 0) {
                        $_POST['address_list'] = '';
                    } else {
                        $address_list = explode(',', trim($_POST['address_list'], ','));
                        $_POST['address_list'] = implode(',', array_unique($address_list));
                    }
//                    //验证是否已经绑定商品
//                    $is_exist = M('Village_group_goods_bind_area')->where(array('city_id' => $_POST['city_id'], 'goods_id' => $res))->find();
//                    if ($is_exist) {
//                        $this->error('您已绑定该商品不能重复绑定');
//                    }
                    $_POST['time'] = time();
                    $_POST['last_time'] = time();
                    $_POST['goods_id'] = $res;

                    if ($r = M('Village_group_goods_bind_area')->add($_POST)) {
                        D("System_log")->add_log("增加了" . $this->config['village_group_alias'] . "城市商品，城市ID：" . $_POST['city_id'], $this->config['village_group_alias'], $r);
//                        $this->success('绑定成功');
                    } else {
                        $this->error('添加失败');
                    }

                }

                /** 分城市管理开启时  操作添加商品时绑定 区域级别   end **/

				D("System_log")->add_log("增加了".$this->config['village_group_alias']."商品，商品名称：".$_POST['name'],$this->config['village_group_alias'],$res);
				if ($res && $village_group_cycle) {
                    $village_group_cycle['goods_id'] = $res;
				    M('Village_group_cycle')->add($village_group_cycle);
                }


			}
			if($res){

				import('ORG.Util.Dir');
				Dir::delDirnotself('./runtime/tmpshareImage');

				$this->success('编辑成功！');
			}else{
				
				$this->error('编辑失败！请重试~');
			}
		}else{
            $levelDb = M('User_level');
            $tmparr = M(_view('User_level'))->order('id ASC')->select();
            $levelarr = array();
            if ($tmparr && $this->config['level_onoff']) {
                foreach ($tmparr as $vv) {
                    $vv['vv'] = $vv['boon'];
                    $vv['lid'] = $vv['id'];
                    $levelarr[$vv['level']] = $vv;
                }


            }
            unset($tmparr);
            $this->assign('levelarr', $levelarr);

			$res = M('Village_group_goods')->where(array('goods_id'=>$_GET['goods_id']))->find();
			$res['merchant'] = "";
			if($res['mer_id']){
				$merchant = D("Merchant")->where(['mer_id'=>$res['mer_id']])->field("name")->find();
				$res['merchant'] = $merchant['name'];
			}
			$res['arrive_datetime'] = $res['arrive_datetime'] ? date('Y-m-d H:i',$res['arrive_datetime']) : '';
			$res['arrive_cutting_datetime'] = $res['arrive_cutting_datetime'] ? date('Y-m-d H:i',$res['arrive_cutting_datetime']) : '';
			$res['arrive_time'] = $res['arrive_time'] ? date("H:i", strtotime(date("Y-m-d 00:00:00"))+$res['arrive_time']) : '';
			$res['arrive_cutting_time'] = $res['arrive_cutting_time'] ? date("H:i", strtotime(date("Y-m-d 00:00:00"))+$res['arrive_cutting_time']) : '';
			// 处理等级相关
            $level_off = !empty($res['leveloff']) ? unserialize($res['leveloff']) : array();
            $this->assign('level_off', $level_off);

			$group_image_class = new group_image();
			$res['all_pic'] = $group_image_class->get_allImage_by_path($res['pic_list']);
			$village_list = M('Village_group_pick_address')->where(array('id'=>array('in',trim($res['address_list'],','))))->select();

			//商品分类
			$cates = M("Village_group_category")->field("cat_id,cat_name")->where(array('fid'=>0,'status'=>array('neq',4)))->order("`sort` desc")->select();
			$this->assign("cates", $cates);
			$this->assign("cat_fid", $res['cat_fid'] ? $res['cat_fid'] : ($_GET['cat_id'] ? $_GET['cat_id'] : 0));

			// 周期购
			$cycle_info = M('Village_group_cycle')->where(array('goods_id'=>$_GET['goods_id']))->find();
			if ($cycle_info['delivery_date']) {
                $cycle_info['delivery_date_arr'] = explode(',',$cycle_info['delivery_date']);
            }
            $this->assign('cycle_info',$cycle_info);
            // 相关参数
            $choose_date = D('Village_group')->choose_date;
            $this->assign('choose_date',$choose_date);

            //绑定关系参数
//            $_GET['pigcms_id'] && $condition['pigcms_id'] = $_GET['pigcms_id'];
            $_GET['city_id'] && $condition['city_id'] = $_GET['city_id'];
            $condition['goods_id'] = $_GET['goods_id'];
            $database_village_group_goods_bind_area = D('Village_group_goods_bind_area');
            if ($_GET['city_id'] && $_GET['edit']) {
                $goods_list = $database_village_group_goods_bind_area->get_area_goods_list($condition);
                $goods_detail = $goods_list['list'][0] ? $goods_list['list'][0] : array();
                if (!$goods_detail) {
                    $this->error('信息不存在');
                }
                $village_list = M('Village_group_pick_address')->where(array('id'=>array('in',trim($goods_detail['address_list'],','))))->select();

                $this->assign('village_list',$village_list);
                $this->assign('goods_detail',$goods_detail);
            }
            if ($_GET['city_id']) {
                // 绑定的区域
                $area_info = M('Village_group_area')->where(array('pigcms_id'=>$_GET['city_id']))->find();
                $this->assign('area_info',$area_info);
            }

            if (C('config.score_round_two') == 1) {
                $res['score_percent'] = sprintf('%.2f',floatval($res['score_percent']));
            } else {
                $res['score_percent'] = round($res['score_percent']);
            }

            /** 分城市管理 开关 */
            $city_manage = C('config.village_group_city_manage');
            $this->assign('city_manage', $city_manage);
            $this->assign('city_id', $_GET['city_id']);
            $this->assign('level', $this->system_session['level']);
			$this->assign('village_list',$village_list);
			$this->assign('group',$res);
			$this->assign('label',D('Village_group')->get_label());
			$this->display();
		}

	}


	public function select_village(){
		$condition_string = '';
		if (!empty($_POST['search'])) {
			$condition_string = "name like '%" . $_POST['search'] . "%'";
		}

		if (!empty($_GET['area_id'])) {
			if ($condition_string) {
				$condition_string .= " AND (province_id = {$_GET['area_id']} OR city_id = {$_GET['area_id']} OR area_id = {$_GET['area_id']})";
			}else{
				$condition_string = "province_id = {$_GET['area_id']} OR city_id = {$_GET['area_id']} OR area_id = {$_GET['area_id']}";
			}
		} else {
            if($this->system_session['level'] = 1 && C('config.village_group_city_manage') == 1) {
                $area_id = $this->system_session['area_id'];

                if (!empty($condition_string)) {
                    switch ($this->system_session['area_type']) {
                        case 1 : //省级
                            $condition_string .= " and province_id = {$area_id}";
                            break;
                        case 2 : //市级
                            $condition_string .= " and city_id = {$area_id}";
                            break;
                        case 3 : //区县
                            $condition_string .= " and area_id = {$area_id}";
                            break;
                        default:;
                    }
                }else{
                    switch ($this->system_session['area_type']) {
                        case 1 : //省级
                            $condition_string = "province_id = {$area_id}";
                            break;
                        case 2 : //市级
                            $condition_string = "city_id = {$area_id}";
                            break;
                        case 3 : //区县
                            $condition_string = "area_id = {$area_id}";
                            break;
                        default:;
                    }
                }
            }
        }

		$condition_string .= $condition_string ? " and status=1" : "status=1";

		$order_string = '`id` DESC';
		$database_user = D('Village_group_pick_address');
		$count_user = $database_user->where($condition_string)->count();
		import('@.ORG.system_page');
		$p = new Page($count_user, 5);
		$user_list = $database_user->field(true)->where($condition_string)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

		$this->assign('list', $user_list);
		$this->assign('page', $p->show());
		$this->display();
	}	

	public function select_village_checkbox(){
		$condition_string = '1';
		if (!empty($_POST['search'])) {
			$condition_string .= " AND name like '%" . $_POST['search'] . "%'";
		}

		if (!empty(intval($_GET['province']))) {
			$condition_string .= " AND province_id = {$_GET['province']}";
		}

		if (!empty(intval($_GET['city']))) {
			$condition_string .= " AND city_id = {$_GET['city']}";
		}

		if (!empty(intval($_GET['area']))) {
			$condition_string .= " AND area_id = {$_GET['area']}";
		} elseif($this->system_session['level'] = 1 && C('config.village_group_city_manage') == 1) {
            $area_id = $this->system_session['area_id'];
            $condition_string .= " AND area_id = {$area_id}";
        }

        $condition_string .= $condition_string ? " and status=1" : "status=1";

		$order_string = '`id` DESC';
		$database_user = D('Village_group_pick_address');
		$count_user = $database_user->where($condition_string)->count();
		import('@.ORG.system_page');
		$p = new Page($count_user, 5);
		$village_list = $database_user->field(true)->where($condition_string)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

		$get_list = explode(',', $_GET['village_list']);
        if ($get_list){
            foreach ($village_list as &$item) {
                if(in_array($item['mer_id'],$get_list)){
                    $item['select'] =1;
                }
            }
        }

		$this->assign('list', $village_list);
		$this->assign('page', $p->show());
		$this->display();
	}

	public function select_goods(){
		if (!empty($_POST['search'])) {
			$condition_user['_string'] = "name like '%" . $_POST['search'] . "%'";
		}

		$order_string = '`goods_id` DESC';
		$database_village_group_goods = D('Village_group_goods');
        $condition_user['status'] = array('neq', 4);
		$count = $database_village_group_goods->where($condition_user)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 5);
		$list = $database_village_group_goods->field(true)->where($condition_user)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

		$this->assign('list', $list);
		$this->assign('page', $p->show());
		$this->display();
	}



	public function del_group(){
		$del = M('Village_group_goods')->where(array('goods_id'=>$_POST['goods_id']))->setField('status',4);
		if ($del) {
            $database_village_group_goods_bind_area = D('Village_group_goods_bind_area');
            $condition['goods_id'] = $_POST['goods_id'];

            $goods_detail = $database_village_group_goods_bind_area->where($condition)->select();
            if ($goods_detail) {
                $database_village_group_goods_bind_area->where($condition)->delete();
            }
            D("System_log")->add_log("删除了".$this->config['village_group_alias']."商品，商品ID：".$_POST['goods_id'],$this->config['village_group_alias'],$_POST['goods_id']);
        }

		$this->success('删除成功！');
	}

	public function del_leader(){
        $order_count = M('Village_group_order')->where(['start_uid' => $_POST['id'], '_string' => '(status in (0,1,3,5,6,7,8))'])->count();
        if ($order_count > 0) {
            $this->error('存在未核销/未支付（未超时取消）的订单，不能删除');
        }
		M('Village_group_header')->where(array('id'=>$_POST['id']))->setField('status',4);
		M('Village_group_pick_address')->where(array('header_uid'=>$_POST['id']))->setField('header_uid','');
		D("System_log")->add_log("删除了".$this->config['village_group_alias']."团长，团长ID：".$_POST['id'],$this->config['village_group_alias'],$_POST['id']);
		$this->success('删除成功！');
	}

	public function del_village(){
		M('Village_group_pick_address')->where(array('id'=>$_POST['id']))->setField('status',4);
		M('Village_group_pick_address')->where(array('id'=>$_POST['id']))->setField('header_uid',0);
		D("System_log")->add_log("删除了".$this->config['village_group_alias']."小区，小区ID：".$_POST['id'],$this->config['village_group_alias'],$_POST['id']);
		$this->success('删除成功！');
	}

	public function del_category(){
        $condition_group_category = [];
        $condition_group_category['cat_fid'] = $_POST['cat_id'];
        $condition_group_category['status'] = array('neq','4');
        $database_group_category = D('Village_group_goods');
        $group_list = $database_group_category->field(true)->where($condition_group_category)->order('goods_id DESC,status ASC')->select();
        if (!empty($group_list)) {
            $this->error('请先删除该分类下商品~');
        }
        $condition_group_category = [];
        $condition_group_category['cat_id'] = $_POST['cat_id'];
        $condition_group_category['status'] = array('neq','4');
        $database_group_category = D('Village_group_goods');
        $group_list = $database_group_category->field(true)->where($condition_group_category)->order('goods_id DESC,status ASC')->select();
        if (!empty($group_list)) {
            $this->error('请先删除该分类下商品~');
        }

		M('Village_group_category')->where(array('cat_id'=>$_POST['cat_id']))->setField('status',4);
		D("System_log")->add_log("删除了".$this->config['village_group_alias']."商品分类，分类ID：".$_POST['cat_id'],$this->config['village_group_alias'],$_POST['cat_id']);
		$this->success('删除成功！');
	}

	public function group_search(){
		$group_return = D('Group')->get_group_by_groupId($_POST['w']);
        if( M('Village_group_goods')->where(array('group_id'=>$group_return['group_id'],'status'=>1))->find()){
            $this->error('该团购已被添加为社区团购商品，不可再次绑定');
        }
		if($group_return){
			$group =$group_return;
			$tmp_pic_arr = explode(';',$group['pic']);
			$group_image_class = new group_image();
			$group['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0],'s');
			$this->success(array('group'=>$group));
		}else{
            $this->error('搜索不到相关内容~');
		}
	}

	public function header_list(){
		$database = D('Village_group_header');
		$condition_group_category['h.status'] = array('neq','4');
		// $condition_group_category['a.status'] = array('eq','1');
		
		if($_GET['keyword']){
			switch($_GET['searchtype']){
				case 'nickname':
					$condition_group_category['h.name'] = $_GET['keyword'];
					break;
				case 'phone':
					$condition_group_category['h.phone'] = $_GET['keyword'];
					break;
			}
		}

		if ($this->system_session['level'] == 1) {
            if ($this->system_session['area_type'] == 1) {
                //省份管理员
                $condition_group_category['a.province_id'] =$this->system_session['area_id'];
            }elseif($this->system_session['area_type'] == 2){
            	$condition_group_category['a.city_id'] =$this->system_session['area_id'];
            }elseif($this->system_session['area_type'] == 3){
            	$condition_group_category['a.area_id'] =$this->system_session['area_id'];
            }
        }

		//省市区
		if($_GET['area_id']){
			$condition_group_category['a.area_id'] = $_GET['area_id'];
		}else if($_GET['city_idss']){
			$condition_group_category['a.city_id'] = $_GET['city_idss'];
		}else if($_GET['province_idss']){
			$condition_group_category['a.province_id'] = $_GET['province_idss'];
		}
		
		$count_group_category =  $database->join('as h LEFT JOIN '.C('DB_PREFIX').'village_group_pick_address a ON h.id=a.header_uid')->where($condition_group_category)->count();

		import('@.ORG.system_page');
		$p = new Page($count_group_category,20);
		$header = $database->field('h.* ,a.pick_addr,a.name as pick_name')->join('as h LEFT JOIN '.C('DB_PREFIX').'village_group_pick_address a ON h.id=a.header_uid')->where($condition_group_category)->order('id DESC')->limit($p->firstRow.','.$p->listRows)->select();

		foreach ($header as &$value) {
            $value['sale_num'] = D('Village_group_order')->sum_header_sale_num($value['id'],1);
		}

		$this->assign('header',$header);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		$this->display();
	}

	// 选择团长
	public function select_header_list(){
		$database = D('Village_group_header');
		$search=I('search','','trim');
		$condition_group_category['h.status'] = 1;
		if($search!=''){
			$condition_group_category['_string'] ="h.name like '%".$search."%' or h.phone like '%".$search."%'"; 

		$count_group_category =  $database->join('as h LEFT JOIN '.C('DB_PREFIX').'village_group_pick_address a ON h.pick_id=a.id')->where($condition_group_category)->count();
		import('@.ORG.system_page');
		$p = new Page($count_group_category,20);
		$list = $database->field('h.* ,a.pick_addr')->join('as h LEFT JOIN '.C('DB_PREFIX').'village_group_pick_address a ON h.pick_id=a.id')->where($condition_group_category)->order('id DESC')->limit($p->firstRow.','.$p->listRows)->select();

		$this->assign('list',$list);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
        }
		$this->display();
	}



	public function add_header(){
		if(IS_POST){
			if(!D('User')->get_user($_POST['uid'])){
				$this->error('该用户不存在');
			}
			if(!$_POST['id'] && M('Village_group_header')->where(array('uid'=>$_POST['uid'],'status'=>array('neq',4)))->find()){
				$this->error('该用户已经是团长了，不能再绑定');
			}

			if($_POST['id']){
                // 删除后可以重复成为团长
				$header = M('Village_group_header')->where(array('uid'=>$_POST['uid'],'status'=>array('neq',4)))->find();
				if($header &&   $header['id']!=$_POST['id'] ){
					$this->error('该用户已经是团长了，不能再绑定');
				}
				if($_POST['pick_id']>0){
					M('Village_group_pick_address')->where(array('id'=>$_POST['pick_id']))->setField('header_uid',$_POST['id']);
				}
				$before = M('Village_group_header')->where(array('id'=>$_POST['id']))->find();
				$res = M('Village_group_header')->where(array('id'=>$_POST['id']))->save($_POST);
				$after = M('Village_group_header')->where(array('id'=>$_POST['id']))->find();
				D("System_log")->add_log("编辑了".$this->config['village_group_alias']."团长，团长名称：".$header['name'],$this->config['village_group_alias'],$_POST['id'], $before, $after);
			}else{
				$res = M('Village_group_header')->add($_POST);
				D("System_log")->add_log("增加了".$this->config['village_group_alias']."团长，团长名称：".$_POST['name'],$this->config['village_group_alias'],$res);
			}
			if($res)	{
				$this->success('操作成功！');
			}else{
				$this->error('操作失败！请重试~');
			}
		}else{
			$res = M('Village_group_header')->where(array('id'=>$_GET['id']))->find();
			if($pick_addr =M('Village_group_pick_address')->where(array('header_uid'=>$res['id']))->find()){
				$res['pick_name'] = $pick_addr['name'];
				$res['pick_addr'] = $pick_addr['pick_addr'];

                if ($pick_addr) {
                    if ($pick_addr['province_id']>0 || $pick_addr['city_id']>0 || $pick_addr['area_id']>0){
                        $sale_price = D('Village_group_order')->sum_header_sale_num($_GET['id']);
                        // 如果存在
                        $province_id = $pick_addr['province_id'];
                        $city_id = $pick_addr['city_id'];
                        $area_id = $pick_addr['area_id'];
                        $area_level = array();
                        if ($area_id > 0 && $sale_price>0) {
                            $area_level = M('Village_group_leader_area_level')
                                ->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
                                ->find();
                            if (!$area_level && $sale_price>0) {
                                $area_level = M('Village_group_leader_area_level')
                                    ->where(array('city_id' => $area_id, 'min_sale_num' => array('elt', $sale_price)))
                                    ->order('level DESC')
                                    ->find();
                            }
                        }
                        if ($city_id > 0 && $sale_price>0 && empty($area_level)) {
                            $area_level = M('Village_group_leader_area_level')
                                ->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
                                ->find();
                            if (!$area_level && $sale_price>0) {
                                $area_level = M('Village_group_leader_area_level')
                                    ->where(array('city_id' => $city_id, 'min_sale_num' => array('elt', $sale_price)))
                                    ->order('level DESC')
                                    ->find();
                            }
                        }
                        if ($province_id > 0 && $sale_price>0 && empty($area_level)) {
                            $area_level = M('Village_group_leader_area_level')
                                ->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_price), 'max_sale_num' => array('egt', $sale_price)))
                                ->find();
                            if (!$area_level && $sale_price>0) {
                                $area_level = M('Village_group_leader_area_level')
                                    ->where(array('city_id' => $province_id, 'min_sale_num' => array('elt', $sale_price)))
                                    ->order('level DESC')
                                    ->find();
                            }
                        }
                        $this->assign('area_level',$area_level);

                    }
                }
			}

			$this->assign('group',$res);
			$this->display();
		}
	}

	public function add_category(){
		if(IS_POST){
			if($_POST['hot_recom'] == 1){
				if(empty($_POST['bg_img']) && empty($_POST['bg_color'])){
					$this->error("开启推荐到首页后，背景图和背景色至少选填一项");
				}
			}
			if($_POST['cat_id']){
				$before = M('Village_group_category')->where(['cat_id'=>$_POST['cat_id']])->find();
				$res = M('Village_group_category')->where(array('cat_id'=>$_POST['cat_id']))->save($_POST);
			}else{
				$res = M('Village_group_category')->add($_POST);
			}

			if($res)	{
				if($_POST['cat_id']){
					$after = M('Village_group_category')->where(['cat_id'=>$_POST['cat_id']])->find();
					D("System_log")->add_log("编辑了".$this->config['village_group_alias']."商品分类，分类名称：".$_POST['cat_name'],$this->config['village_group_alias'],$_POST['cat_id'], $before, $after);
				}
				else{
					D("System_log")->add_log("增加了".$this->config['village_group_alias']."商品分类，分类名称：".$_POST['cat_name'],$this->config['village_group_alias'],$res);
				}
				$this->success('操作成功！');
			}else{
				$this->error('操作失败！请重试~');
			}
		}else{
			$res = M('Village_group_category')->where(array('cat_id'=>$_GET['cat_id']))->find();
			$this->assign('category',$res);
			$this->display();
		}
	}

	public function add_son_category(){
		if(IS_POST){
			//dump($_POST);exit;
			if($_POST['cat_id']){
				$before = M('Village_group_category')->where(['cat_id'=>$_POST['cat_id']])->find();
				unset($_POST['fid']);
				$res = M('Village_group_category')->where(array('cat_id'=>$_POST['cat_id']))->save($_POST);
			}else{
				$res = M('Village_group_category')->add($_POST);
			}

			if($res)	{
				if($_POST['cat_id']){
					$after = M('Village_group_category')->where(['cat_id'=>$_POST['cat_id']])->find();
					D("System_log")->add_log("编辑了".$this->config['village_group_alias']."商品分类，分类名称：".$_POST['cat_name'],$this->config['village_group_alias'],$_POST['cat_id'], $before, $after);
				}
				else{
					D("System_log")->add_log("增加了".$this->config['village_group_alias']."商品分类，分类名称：".$_POST['cat_name'],$this->config['village_group_alias'],$res);
				}
				$this->success('操作成功！');
			}else{
				$this->error('操作失败！请重试~');
			}
		}else{
			$res = M('Village_group_category')->where(array('cat_id'=>$_GET['cat_id']))->find();
			$this->assign('category',$res);
			$this->display();
		}
	}


	public function village_list(){
		$database = D('Village_group_pick_address');
		$condition_group_category['v.status'] = array('neq','4');
		
		//关键词
		if($_GET['keyword']){
			switch($_GET['searchtype']){
				case 'name':
					$condition_group_category['v.name'] = array('like','%'.$_GET['keyword'].'%');
					break;
				case 'header_name':
					$condition_group_category['h.name'] = array('like','%'.$_GET['keyword'].'%');
					break;
				case 'header_phone':
					$condition_group_category['h.phone'] = array('like',$_GET['keyword']);
					break;
			}
		}
//		//省市区
//		if($_GET['area_id']){
//			$condition_group_category['v.area_id'] = $_GET['area_id'];
//		}else if($_GET['city_idss']){
//			$condition_group_category['v.city_id'] = $_GET['city_idss'];
//		}else if($_GET['province_idss']){
//			$condition_group_category['v.province_id'] = $_GET['province_idss'];
//		}

        $where_header = array();
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
//                die('区域管理员无法管理用户');
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $where_header[$area_index] = $this->system_session['area_id'];
        }

        if(!empty($where_header)) {
            $condition_group_category['v.'.$area_index] = !empty($_GET['area_id'])?$_GET['area_id']:$this->system_session['area_id'];
        }
		
		$count_group_category = $database->join('as v LEFT JOIN '.C('DB_PREFIX').'village_group_header h ON h.id=v.header_uid')->where($condition_group_category)->count();
		
		import('@.ORG.system_page');
		$p = new Page($count_group_category,20);
		$address = $database->join('as v LEFT JOIN '.C('DB_PREFIX').'village_group_header h ON h.id=v.header_uid')->field('v.*,h.name as header_name,h.phone as header_phone')->where($condition_group_category)->order('id DESC')->limit($p->firstRow.','.$p->listRows)->select();

		$this->assign('address',$address);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);
		$this->display();
	}

	public function add_village(){
		if(IS_POST){
			$_POST['last_time'] = time();

			if(!$_POST['id'] && $_POST['header_uid'] && M('Village_group_pick_address')->where(array('header_uid'=>$_POST['header_uid']))->find()){
				$this->error('团长已绑定其他自提点，不能再绑定');
			}
			$lng_lat = explode(',',$_POST['long_lat']);
			$_POST['long'] = $lng_lat[0];
			$_POST['lat'] = $lng_lat[1];

			if($_POST['id']){
				$_POST['header_uid'] && $header = M('Village_group_pick_address')->where(array('header_uid'=>$_POST['header_uid'], 'status'=>1))->find();
				if( $header &&  $header['id']!=$_POST['id'] ){
					$this->error('团长已绑定其他自提点，不能再绑定');
				}
				$before = M('Village_group_pick_address')->where(array('id'=>$_POST['id']))->find();
				$res = M('Village_group_pick_address')->where(array('id'=>$_POST['id']))->save($_POST);
				$after = M('Village_group_pick_address')->where(array('id'=>$_POST['id']))->find();
				D("System_log")->add_log("编辑了".$this->config['village_group_alias']."小区，小区名称：".$_POST['name'],$this->config['village_group_alias'],$_POST['id'], $before, $after);
			}else{
				$res = M('Village_group_pick_address')->add($_POST);
				D("System_log")->add_log("增加了".$this->config['village_group_alias']."小区，小区名称：".$_POST['name'],$this->config['village_group_alias'],$res);
			}

			if($res){
				$this->success('编辑成功！');
			}else{
				$this->error('编辑失败！请重试~');
			}
		}else{
			if($_GET['id']){

				$res = M('Village_group_pick_address')->where(array('id'=>$_GET['id']))->find();
				$res['header_uid']>0 && $header= D('Village_group')->get_header_info('',$res['header_uid'],true);
				if($header['error_code']==0){
					$res['header'] = $header['msg'];
				}
				$this->assign('village',$res);
			}
			$this->display();
		}
	}

	public function ajax_get_header(){
		$w = $_POST['w'];

		$res = M('Village_group_header')->where(array('id'=>$_POST['w']))->find();
		if($res){
			$this->success($res);
		}else{
			$this->error('未找到团长');
		}
	}

	public function order_list(){
		if($_GET['status']>-1){
			switch($_GET['status']){
				case 1:
					$where['_string'] = '(o.status>0 AND o.status<10 )';
					break;
				case 0:
					$where['o.status'] = 0;
					break;
				case 11:
					$where['_string'] = '(o.status=11 or o.status=12)';
					break;
				case 12:
					$where['_string'] = '(o.status=2 or o.status=3 or o.status=13)';
//					$where['status'] = array('in',array(2,3));
					$where['is_apply_refund'] = 1;
					break;
				case 15:
					$where['_string'] = '(o.status in (4,12))';
					break;
				case 2:
					$where['_string'] = '(o.status in (2,13))';
					break;
                case 200: //查询部分退款订单
                    break;
				default:
					$where['o.status'] = $_GET['status'];
					break;
			}
		}

		if($_GET['deliver_status']>-1 && ($_GET['status']==1 || $_GET['status']==-1)){
			$where['o.status'] = $_GET['deliver_status'];
		}

        $get_header_uid = false;
        $start_uid = 0;
        $start_uids = [];
		if($_GET['keyword']){

			switch($_GET['searchtype']){
				case 'user_phone':
					$user = D('User')->get_user($_GET['keyword'],'phone');

					$where['o.uid'] = $user['uid'];
					break;
				case 'user_id':
					$where['o.uid'] = $_GET['keyword'];
					break;
				case 'header_phone':
					$header = M('Village_group_header')->where(array('phone'=>$_GET['keyword']))->find();
                    $start_uid = $header['id'];
                    $get_header_uid = true;
					break;
				case 'header_uid':
					$header = M('Village_group_header')->where(array('id'=>$_GET['keyword']))->find();
                    $start_uid = $header['id'];
                    $get_header_uid = true;
					break;
				case 'header_name':
					$header = M('Village_group_header')->where(array('name' => ['like', '%' . $_GET['keyword'] . '%'],'status' => 1))->select();
                    $start_uid = $header[0]['id'];
                    if (count($header) > 1) {
                        $start_uids = array_column($header, 'id');
                    }
                    $get_header_uid = true;
					break;
				case 'order_id':
					$where['o.order_id'] = $_GET['keyword'];
					break;
                case 'real_orderid':
                    $where['o.real_orderid'] = $_GET['keyword'];
                    break;
				case 'orderid': //支付流水号
					$where['o.order_id'] = 0;
					$payment_money_order = D('Payment_money_order')->where(['order_type' => 'plat', 'orderid' => trim($_GET['keyword'])])->find();
					if ($payment_money_order) {
						$order_id = D('Plat_order')->where(['order_id' => $payment_money_order['order_id'], 'business_type' => 'village_group'])->getField('business_id');
						if ($order_id) {
							$where['o.order_id'] = $order_id;
						}
					}
					break;
			}
		}

        $where_header = array();
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if (C('config.village_group_city_manage') == 0) {
                if ($now_area['area_type'] == 3) {
                    die('区域管理员无法管理用户');
                } elseif ($now_area['area_type'] == 2) {
                    $area_index = 'city_id';
                } elseif ($now_area['area_type'] == 1) {
                    $area_index = 'province_id';
                }
            } else {
                if ($now_area['area_type'] == 3) {
                    $area_index = 'area_id';
                } elseif ($now_area['area_type'] == 2) {
                    $area_index = 'city_id';
                } elseif ($now_area['area_type'] == 1) {
                    $area_index = 'province_id';
                }
            }
            $where_header[$area_index] = $this->system_session['area_id'];
        }

		// 处理省市区
        if ($_GET['province_idss'] || $_GET['city_idss'] || $_GET['area_id'] || !empty($where_header)) {
            $where_header['header_uid']  = array('gt',0);
            if ($_GET['province_idss']) {
                $where_header['province_id'] = $_GET['province_idss'];
            }
            if ($_GET['city_idss']) {
                $where_header['city_id'] = $_GET['city_idss'];
            }
            if ($_GET['area_id']) {
                $where_header['area_id'] = $_GET['area_id'];
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[]=$val['header_uid'];
            }
            if ($get_header_uid>0 && empty($start_uids)) {
            	if (in_array($start_uid, $header_arr)) {
            		$where['start_uid'] = $start_uid;
            	}else{
            		$where['start_uid'] = 0;
            	}
            }else{
            	$where['start_uid'] = array('in', $header_arr);
            }
        } elseif ($get_header_uid) {
            if (!empty($start_uids)) {
                $where['start_uid'] = array('in', $start_uids);
            } else {
                $where['start_uid'] = $start_uid;
            }
        }

		$pay_type = $_GET['pay_type'];
		if($pay_type){
			if($pay_type=='balance'){
				$where['system_balance']  = array('gt',0);
			}else{
				$where['p.pay_type'] = $pay_type;
			}
		}
        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])&& !empty($_GET['searchtimetype'])){
            $begin_time = strtotime($_GET['begin_time']." 00:00:00");
            $end_time = strtotime($_GET['end_time']." 23:59:59");
            if ($begin_time>$end_time) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array($begin_time,$end_time);
           
            $where['o.'.$_GET['searchtimetype']] = ['between', $period];
        }

        //部分退款问题
        if ($_GET['status'] == 200) {
            //获取已退款订单
            $refundOrderArr = M('Village_group_refund_detail')->field('order_id')->where(['status'=>1])->select();
            $refund_order_ids = array_column($refundOrderArr,'order_id');
            $partRefundOrder = [];
            if($refund_order_ids){
                $where['o.order_id'] = ['IN', $refund_order_ids];
                $partRefundOrder = M('Village_group_order')->alias('o')
                    ->join('LEFT JOIN ' . C('DB_PREFIX') . 'village_group_order_detail od ON od.order_id = o.order_id')
                    ->where($where)
                    ->field('o.order_id,o.real_orderid,SUM(od.num) AS t_total,SUM(od.refundNum) AS r_total')
                    ->group('o.order_id')
                    ->having('r_total > 0 AND t_total > r_total')
                    ->select();
            }
            
            if ($partRefundOrder) {
                $refundOrderIds = array_column($partRefundOrder, 'order_id');
                $where['o.order_id'] = ['IN', $refundOrderIds];
            } else {
                $where['o.order_id'] = 0;
            }
        }
		$count = M('Village_group_order')->join('as o left join '.C('DB_PREFIX').'plat_order p ON p.business_id = o.order_id AND p.business_type="village_group"')->where($where)->count();

		import('@.ORG.system_page');
		$p = new Page($count,20);

		$start_list = M('Village_group_order')->field('o.*,u.phone,u.nickname,p.system_balance')->join('as o left join '.C('DB_PREFIX').'plat_order p ON p.business_id = o.order_id AND p.business_type="village_group" left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')->where($where)->limit($p->firstRow.','.$p->listRows)->order('o.order_id desc')->select();

        if ($start_list) {
            // 单独查询一下 团长信息
            $m_village_group_header = M('Village_group_header');
            $m_village_group_order_detail = M('Village_group_order_detail');
            foreach ($start_list as &$val) {
                $val['header_info'] = $m_village_group_header->where(array('id' => $val['start_uid']))->field('name, phone,address')->find();
                // 查询一下团购购买数量
                $val['num'] = $m_village_group_order_detail->where(array('order_id' => $val['order_id'],'is_delay' => 0))->sum('num');
				$val['note'] = empty($val['note'])?'':mb_strlen($val['note']) > 14 ? substr($val['note'],0,39).'.....':$val['note'];
            }
        }
		$pay_method = D('Config')->get_pay_method('','',1);
		$this->assign('pay_method',$pay_method);
		$this->assign('order_status',D('Village_group')->order_status);
		$this->assign('start_list',$start_list);
		$this->assign('pagebar',$p->show());
		$this->display();
	}
	public function export()
	{

		$param = $_POST;
		$param['type'] = 'village_group';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		$param['system_session']['area_id'] = $this->system_session['area_id'];

		if ($res = D('Order')->order_export($param)) {
			echo json_encode(array('error_code' => 0, 'msg' => '操作导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
		} else {
			echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
		}
	}

	public function send_export()
	{
        $_POST['is_merchant_send'] = 0;
		$param = $_POST;
		$param['type'] = 'village_group_send';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
		$param['system_session']['area_id'] = $this->system_session['area_id'];

		if ($res = D('Order')->order_export($param)) {
			echo json_encode(array('error_code' => 0, 'msg' => '操作导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
		} else {
			echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
		}
	}

	//发货改变订单状态
	public function send(){
		if($_POST['send_all']==1){
			$order_arr =  explode(',',$_POST['order_id']);
			$where['order_id'] = array('in',$order_arr);
		}else{
			$order_arr[] =$_GET['order_id'];
			$where['order_id'] = $_GET['order_id'];
		}

		if($_GET['goods_id']){
            $where['goods_id'] = $_GET['goods_id'];
            $order_id = $_GET['order_id'];
            $info_order = M('Village_group_order')->where(array('order_id' =>$order_id))->find();
            if ($info_order['newest_delivery_periods']) {
                $date_num=$where['date_num'] = $info_order['newest_delivery_periods'];
                $where['is_delay'] = 0;
            } else {
                $date_num=$where['date_num'] = 1;
                $where['is_delay'] = 0;
            }
			if(M('Village_group_order_detail')->where($where)->data(array('status'=>1,'real_send_time' => time()))->save()) {
				foreach ($order_arr as $value) {
					$where['order_id'] = $value;
					if (M('Village_group_order_detail')->where(array('order_id' => $value, 'status' => 0))->find()) {
						$order_status = 7;
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					} else {
						$order_status = 5;
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					}

					$param['goods_id'] = $_GET['goods_id'];
					$param['from_type'] = 1;
					$param['deliver_status'] = 1;
					$param['note'] = '系统管理员点击商品发货';
					$param['name'] = $this->system_session['account'];
                    $param['order_id'] = $value;
                    $param['date_num'] = $date_num;
					D('Village_group')->add_log($param);
					unset($param['deliver_status'],$param['status']);
					$param['status'] = $order_status;
					D('Village_group')->add_log($param);
				}

				$this->success('商品发货状态改变成功');

			}else{
				$this->error('商品发货状态改变失败');
			}
		}else{
			$order = M('Village_group_order')->where($where)->where(array('status'=>1))->select();
			$order_detail = M('Village_group_order_detail')->where($where)->where(array('status'=>0))->select();
			if($order && $order_detail){
				$order_info_arr = M('Village_group_order_detail')->where($where)->where(array('status'=>0))->select();
				$new_order_id = array();
				$cycle_order_id = array();
				$order_info = array();
				foreach ($order_info_arr as $val) {
					if ($val && $val['village_group_type']==1) {
						$cycle_order_id[]=$val['order_id'];
						$order_info[$val['order_id']]=$val;
					} else {
						$new_order_id[]=$val['order_id'];
					}
				}
				if ($cycle_order_id) {
					$where['order_id'] = array('in',$new_order_id);
				}
				if(M('Village_group_order')->where($where)->where(array('status'=>1))->setField('status',5)) {
					foreach ($order_arr as $item) {
						$param = array();
						$where['order_id'] = $item;
						M('Village_group_order_detail')->where($where)->where(array('status'=>0))->data(array('status'=>1,'real_send_time' => time()))->save();
						$param['order_id'] = $item;
						$param['from_type'] = 1;
						$param['status'] = 5;
						$param['note'] = '系统管理员点击订单发货';
						$param['name'] = $this->system_session['account'];
						D('Village_group')->add_log($param);
						unset($param['status']);
						$param['note'] = '系统管理员点击订单发货，批量更新订单商品发货';
						$param['deliver_status'] = 1;
						D('Village_group')->add_log($param);
					}
					// 处理下周期购
					if ($cycle_order_id) {
						foreach ($cycle_order_id as $val) {
							$newest_delivery_periods = $order_info[$val]['newest_delivery_periods'];
							if ($newest_delivery_periods) {
								$date_num = $where['date_num'] = $newest_delivery_periods;
							} else {
								$date_num = $where['date_num'] = 1;
							}
							$where['order_id'] = $val;
							$goods_id = M('Village_group_order_detail')->where($where)->getField('goods_id');
							M('Village_group_order_detail')->where($where)->where(array('status'=>0))->data(array('status'=>1,'real_send_time' => time()))->save();
							$param['order_id'] = $val;
							$param['goods_id'] = $goods_id;
							$param['from_type'] = 1;
							$param['status'] = 5;
							$param['deliver_status'] = 1;
							$param['note'] = '系统管理员点击订单发货';
							$param['name'] = $this->system_session['account'];
							$param['date_num'] = $date_num;
							D('Village_group')->add_log($param);
							if ($date_num>=$order_info[$val]['delivery_periods']) {
								M('Village_group_order')->where(array('order_id' => $val))->where(array('status'=>1))->setField('status',5);
							}
						}
					}

					$this->success('订单发货状态改变成功');
				}else{

					$this->error('订单发货状态改变失败');
				}
			}
			else{
				$this->error('订单发货状态改变失败');
			}

		}

	}

    /**
     * 根据条件查询全部的发货信息
     */
    public function getSendList($param)
    {

        if(empty($param['begin_time'])){
            $param['begin_time'] = '';
        }

        if(empty($param['end_time'])){
            $param['end_time'] = '';
        }
        if($param['goods_id']){
            $now_goods = M('Village_group_goods')->where(array('goods_id'=>$param['goods_id']))->find();
            $where['d.goods_id'] = $param['goods_id'];
        }

        $start_uid = array();
        if($param['searchtype'] &&  $param['keyword']!=''){
            switch($param['searchtype']){
                case 'order_id':
                    $where['o.order_id'] = $param['keyword'];
                    break;
                case 'name':
                    $where['g.name'] = $param['keyword'];
                    break;
                case 'goods_id':
                    $where['g.goods_id'] = $param['keyword'];
                    break;
                case 'mer_id':
                    $where['g.mer_id'] = $param['keyword'];
                    break;
                case 'mer_name':
                    $where['m.name'] = ['like', '%'.$param['keyword'].'%'];
                    break;
                case 'village_name':
                    $village_list = M('Village_group_pick_address')->field('p.header_uid')->join('as p LEFT JOIN '.C('DB_PREFIX').'village_group_header h ON h.id = p.header_uid')->where(array('p.name'=>array('like',"%{$param['keyword']}%"),'p.status'=>1,'h.status'=>1))->select();

                    foreach ($village_list as $v) {
                        $start_uid[] = $v['header_uid'];
                    }
                    $where['o.start_uid']  = array('in',$start_uid);

                    break;
            }
        }

        $where_header = array();
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
//                die('区域管理员无法管理用户');
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $where_header[$area_index] = $this->system_session['area_id'];
        }
        // 处理省市区
        if ($param['province_idss'] || $param['city_idss'] || $param['area_id'] || !empty($where_header)) {
            $where_header['header_uid'] = array('gt',0);
            if ($param['province_idss']) {
                $where_header['province_id'] = $param['province_idss'];
            }
            if ($param['city_idss']) {
                $where_header['city_id'] = $param['city_idss'];
            }
            if ($param['area_id']) {
                $where_header['area_id'] = $param['area_id'];
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[]=$val['header_uid'];
            }
            if ($start_uid) {
                // $header_arr = array_merge($header_arr, $start_uid);
                $header_arr = array_intersect($header_arr,$start_uid); // 不是合并条件而是查询比对出同样的团长id
                $header_arr = array_values($header_arr); // 重新排序
            }
            $where['o.start_uid'] = array('in', $header_arr);
        }
        
        $where['o.village_group_type'] = array('neq',2);
//        $where['o.status'] =array('in','1,2,3,5,6,7,8');
        if(!empty($param['begin_time'])&&!empty($param['end_time'])&& !empty($param['searchtimetype'])){
            $begin_time = strtotime($param['begin_time'].":00");
            $end_time = strtotime($param['end_time'].":00");
            if ($begin_time>$end_time) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array($begin_time,$end_time);
            if($param['searchtimetype'] == 'pay_time'){
                $where['o.'.$param['searchtimetype']] = ['between', $period];
            }
            else{
                $where['d.'.$param['searchtimetype']] = ['between', $period];
            }
        }
        $where['d.is_merchant_send'] = 0;
        $where['_string'] = 'd.date_num = o.newest_delivery_periods AND d.is_delay=0';
        
        if($param['type'] == 'send_selected'){
            $where['d.status'] = 0;
            $where['o.status'] = [['lt',10],['egt',1],'and'];
            $where['d.is_delay'] = ['eq',0];
        }
        if($param['type'] == 'get_selected'){
            $where['d.status'] = 1;
            $where['d.is_delay'] = ['eq',0];
        }
        
        $start_list = M('Village_group_order_detail')->field('d.goods_id,o.order_id')
            ->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
            ->where($where)->order('o.order_id DESC,d.name desc,d.send_time DESC')->select();
        foreach ($start_list as &$v){
            $v = $v['order_id'].'='.$v['goods_id'];
        }
        return $start_list;
    }
	
	//选择性发货
	public function send_selected(){
		if($_POST['send_list'] || $_POST['checkAll']==1){
		    $checkAll = $_POST['checkAll'];
			if (isset($_POST['send_list'])) {
				$send_list = array_filter(explode(',', $_POST['send_list']));
			} else if ($checkAll == 1) { //全部发货
				$param = $_POST;
				$param['type'] = 'send_selected';
				$send_list = $this->getSendList($param);
			} 
			
			if(empty($send_list)) {
				$this->error('请选择发货订单');
			}
			foreach ($send_list as $value) {
				$temp = explode('=', $value);
				$where['goods_id'] = $temp[1];
				$where['order_id'] = $temp[0];
	            $order_id = $temp[0];
	            $info_order = M('Village_group_order')->where(array('order_id' =>$order_id))->find();

				if($info_order['status'] == 11 || $info_order['status'] == 12){
                    $this->error('订单已取消，无需发货');
                }
	            if ($info_order['newest_delivery_periods']) {
	                $date_num=$where['date_num'] = $info_order['newest_delivery_periods'];
	                $where['is_delay'] = 0;
	            } else {
	                $date_num=$where['date_num'] = 1;
	                $where['is_delay'] = 0;
	            }
				if(M('Village_group_order_detail')->where($where)->data(array('status'=>1,'real_send_time' => time()))->save()) {
					$where['order_id'] = $order_id;
					if (M('Village_group_order_detail')->where(array('order_id' => $order_id, 'status' => 0))->find()) {
						$order_status = 7;
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					} else {
						$order_status = 5;
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					}

					$param['goods_id'] = $temp[1];
					$param['from_type'] = 1;
					$param['deliver_status'] = 1;
					$param['note'] = '系统管理员点击商品发货';
					$param['name'] = $this->system_session['account'];
                    $param['order_id'] = $order_id;
                    $param['date_num'] = $date_num;
					// D('Village_group')->add_log($param);
					unset($param['deliver_status'],$param['status']);
					$param['status'] = $order_status;
					D('Village_group')->add_log($param);
					D("System_log")->add_log("发货了".$this->config['village_group_alias']."订单，订单ID：".$temp[0]."，商品ID".$temp[1],$this->config['village_group_alias']);
					//增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'village_group';
                    $params['order_id'] = $order_id;
                    $params['title'] = '商品已发货';
                    $params['content'] = '您的商品已经发货，商品将尽快为你送达';
                    invoke_v20_service($url, [$params]);

				}
				D('System_order')->save_order_status('village_group',$temp[0],1);//发货
			}
			$this->success('商品发货状态改变成功');
		}
		else{
			$this->error('订单发货状态改变失败');
		}
	}

	//选择性到货
	public function get_selected(){
		if($_POST['send_list'] || $_POST['checkAll']==1){
            $checkAll = $_POST['checkAll'];
			if(isset($_POST['send_list'])){
				$send_list = array_filter(explode(',', $_POST['send_list']));
			}else if($checkAll==1){//全部发货
                $param = $_POST;
                $param['type'] = 'get_selected';
                $send_list = $this->getSendList($param);
            }
			if(empty($send_list)) {
				$this->error('请选择到货订单');
			}
			foreach ($send_list as $value) {
				$temp = explode('=', $value);
				$where['goods_id'] = $temp[1];
	            $order_id = $temp[0];
				$where['order_id'] = $order_id;
	            $order = M('Village_group_order')->where(array('order_id' => $order_id))->find();
	            if ($order['village_group_type']==1) {
	                $date_num=$order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1;
	                $where['date_num']=$date_num;
	                $where['is_delay']=0;
	            } else {
	                $date_num=1;
	            }
				if(M('Village_group_order_detail')->where($where)->setField('status',2)) {
					$where['order_id'] = $order_id;
					if (M('Village_group_order_detail')->where(array('order_id' => $order_id, 'status' => array('lt',2)))->find()) {
						$order_status = 8;
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					} else {
						$order_status = 6;
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					}

					$param['goods_id'] = $temp[1];
					$param['from_type'] = 1;
					$param['deliver_status'] = 2;
					$param['note'] = '系统管理员点击商品收货';
					$param['name'] = $this->system_session['account'];

                    $param['date_num'] = $date_num;
					$param['order_id'] = $order_id;
					// D('Village_group')->add_log($param);
					unset($param['deliver_status'],$param['status']);
					$param['status'] = $order_status;
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'village_group';
                    $params['order_id'] = $order_id;
                    $params['title'] = '商品已经到达自提点';
                    $params['content'] = '您的商品已经到达团长自提点，请前往团长处领取商品。';
                    invoke_v20_service($url, [$params]);
					D('Village_group')->add_log($param);
					D("System_log")->add_log("收货了".$this->config['village_group_alias']."订单，订单ID：".$temp[0]."，商品ID".$temp[1],$this->config['village_group_alias']);
                    D("System_order")->save_order_status('village_group', $order_id, 1);
				}
			}
			$this->success('商品收货状态改变成功');
		}
		else{
			$this->error('商品收货状态改变失败');
		}
	}

	public function get_order(){
		if($_POST['get_all']==1){
			$order_arr =  explode(',',$_POST['order_id']);
			$where['order_id'] = array('in',$order_arr);
		}else{
			$order_arr[] =$_GET['order_id'];
			$where['order_id'] = $_GET['order_id'];
		}
		if($_GET['goods_id']){ // 单个商品团长收货
			$where['goods_id'] = $_GET['goods_id'];
			if (empty($_GET['order_id'])) {
                $order_id = M('Village_group_order_detail')->where(array('goods_id' => $_GET['goods_id']))->getField('order_id');
            } else {
                $order_id = $_GET['order_id'];
            }
            $order = M('Village_group_order')->where(array('order_id' => $order_id))->find();
            if ($order['village_group_type']==1) {
                $date_num=$order['newest_delivery_periods']>0?$order['newest_delivery_periods']:1;
                $where['date_num']=$date_num;
                $where['is_delay']=0;
            } else {
                $date_num=1;
            }
			if(M('Village_group_order_detail')->where($where)->setField('status',2)) {
				foreach ($order_arr as $value) {
					$where['order_id'] = $value;
					if (M('Village_group_order_detail')->where(array('order_id' => $value, 'status' => array('lt',2)))->find()) {
						$order_status = 8;
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					} else {
						$order_status = 6;
						$res = M('Village_group_order')->where($where)->setField('status', $order_status);
					}

					$param['goods_id'] = $_GET['goods_id'];
					$param['from_type'] = 1;
					$param['deliver_status'] = 2;
					$param['note'] = '系统管理员点击商品收货';
					$param['name'] = $this->system_session['account'];

                    $param['date_num'] = $date_num;
					$param['order_id'] = $value;
					D('Village_group')->add_log($param);
                    D("System_order")->save_order_status('village_group', $value, 1);
					unset($param['deliver_status'],$param['status']);
					$param['status'] = $order_status;
					D('Village_group')->add_log($param);
					D("System_log")->add_log("收货了".$this->config['village_group_alias']."订单（单个商品团长收货），订单ID：".$value."，商品ID".$_GET['goods_id'],$this->config['village_group_alias']);
				}

				$this->success('商品收货状态改变成功');

			}else{
				$this->error('商品收货状态改变失败');
			}
		}else{
			$order = M('Village_group_order')->where($where)->where(array('status'=>5))->select();
			$order_detail = M('Village_group_order_detail')->where($where)->where(array('status'=>1))->select();
			if($order && $order_detail){
				$order_info_arr = M('Village_group_order_detail')->where($where)->where(array('status'=>1))->select();
				$new_order_id = array();
				$cycle_order_id = array();
				$order_info = array();
				foreach ($order_info_arr as $val) {
					if ($val && $val['village_group_type']==1) {
						$cycle_order_id[]=$val['order_id'];
						$order_info[$val['order_id']]=$val;
					} else {
						$new_order_id[]=$val['order_id'];
					}
				}
				if ($cycle_order_id) {
					$where['order_id'] = array('in',$new_order_id);
				}
				//if(M('Village_group_order')->where($where))
				if(M('Village_group_order')->where($where)->where(array('status'=>5))->setField('status',6)) {
					foreach ($order_arr as $item) {
						$param = array();
						$where['order_id'] = $item;
						M('Village_group_order_detail')->where($where)->where(array('status'=>1))->setField('status',2);
						$param['order_id'] = $item;
						$param['from_type'] = 1;
						$param['status'] = 6;
						$param['note'] = '系统管理员点击订单批量收货';
						$param['name'] = $this->system_session['account'];
						D('Village_group')->add_log($param);
						unset($param['status']);
						$param['note'] = '系统管理员点击订单收货，批量更新订单商品收货';
						$param['deliver_status'] = 2;
						D('Village_group')->add_log($param);
						D("System_log")->add_log("收货了".$this->config['village_group_alias']."订单（订单批量收货），订单ID：".$item,$this->config['village_group_alias']);
					}
					// 处理下周期购
					if ($cycle_order_id) {
						foreach ($cycle_order_id as $val) {
							$newest_delivery_periods = $order_info[$val]['newest_delivery_periods'];
							if ($newest_delivery_periods) {
								$date_num = $where['date_num'] = $newest_delivery_periods;
							} else {
								$date_num = $where['date_num'] = 1;
							}
							$where['order_id'] = $val;
							$goods_id = M('Village_group_order_detail')->where($where)->getField('goods_id');
							M('Village_group_order_detail')->where($where)->data(array('status'=>1,'real_send_time' => time()))->save();
							$param['order_id'] = $val;
							$param['goods_id'] = $goods_id;
							$param['from_type'] = 1;
							$param['deliver_status'] = 2;
							$param['note'] = '系统管理员点击商品收货';
							$param['name'] = $this->system_session['account'];
							$param['date_num'] = $date_num;
							D('Village_group')->add_log($param);
                            D("System_order")->save_order_status('village_group', $val, 1);
							if ($date_num>=$order_info[$val]['delivery_periods']) {
								M('Village_group_order')->where(array('order_id' => $val))->setField('status',5);
							}
						}
					}
					$this->success('订单收货状态改变成功');
				}else{
					$this->error('订单收货状态改变失败');
				}
			}

		}

	}

	public function order_detail(){
		$where['start_id'] = $_GET['start_id'];

		$order_list = M('Village_group_order')->where($where)->select();

		$header= D('Village_group')->get_header_info('',$_GET['header_uid'],true);

		$this->assign('order_list',$order_list);
		$this->assign('header',$header['msg']);
		$this->display();
	}

	public function join_order_detail(){

		$order =D('Village_group')->get_order_detail($_GET['order_id']);
		$user = D('User')->get_user($order['uid']);

		$this->assign('user',$user);
		$this->assign('order',$order);

		$this->assign('order_detail',$order['goods_detail']);


        // 处理周期购信息获取
        if ($order['village_group_type'] && $order['village_group_type']==1){
            $cycle_order_detail=M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id']))->select();
            $goods_all_log = array();
            if ($cycle_order_detail) {
                foreach ($cycle_order_detail as $val) {
                    $goods_all_log[$val['id']] = M('Village_group_order_log')->where(array('deliver_status'=>array('gt',0),'order_id'=>$_GET['order_id'],'goods_id'=>$val['goods_id'],'date_num'=>$val['date_num']))->select();

                    $status_arr = array();
                    foreach ($goods_all_log[$val['id']] as $key => $_log) {
                        if (!in_array($_log['deliver_status'], $status_arr)) {
                            $status_arr[] = $_log['deliver_status'];
                        }else{
                            unset($goods_all_log[$val['id']][$key]);
                        }
                    }
                }
            }
            $cycle_order_count=M('Village_group_order_detail')->where(array('order_id' => $_GET['order_id'],'status'=>array('in', array(1,2,3,4,5))))->count();
            $this->assign('goods_all_log',$goods_all_log);
            $this->assign('cycle_order_count',$cycle_order_count);
            $this->assign('cycle_order_detail',$cycle_order_detail);
        }
		//退款信息
		$dataList = D('Village_group_refund_detail')->where(array('order_id'=>$_GET['order_id'],'status'=>0))->select();
		$goodsLists = array();
		foreach($dataList as $item){
			if($item['goods_id']){
				$goods = explode(',',$item['goods_id']);
				foreach($goods as $val){
					$goodsdetail = D('Village_group_order_detail')->where(array('order_id'=>$_GET['order_id'],'goods_id'=>$val))->find();
					$village_group_goods = D('Village_group_goods')->where(array('goods_id'=>$val))->find();

					$goodsList = array(
						'name'=>$goodsdetail['name'],
						'price'=>$goodsdetail['price'],
						'num'=>$goodsdetail['num'],
						'image'=>$village_group_goods['image'],
					);
					$goodsLists[] = $goodsList;
				}
			}
		}
		$status = array('审核中', '已完成退货', '拒绝退货', '审核中', '取消退货申请');
		foreach ($dataList as &$refund) {

			$refund['image'] = $refund['image'] ? explode(',', $refund['image']) : '';
			$refund['goodsList'] = isset($goodsLists) ?$goodsLists : array();//原来数组
			$refund['showStatus'] = $status[$refund['status']];
		}
		$this->assign('refund_list', $dataList);

		$finishRefunds = D('Village_group_refund_detail')->where(array('order_id'=>$_GET['order_id'],'status'=>1))->select();

		$goodsList = array();
		foreach($finishRefunds as $item){
			if($item['goods_id']){
				$goods = explode(',',$item['goods_id']);
				foreach($goods as $val){
					$goodsdetail = D('Village_group_order_detail')->where(array('order_id'=>$_GET['order_id'],'goods_id'=>$val))->find();
					$village_group_goods = D('Village_group_goods')->where(array('goods_id'=>$val))->find();

					$goods = array(
						'name'=>$goodsdetail['name'],
						'price'=>$goodsdetail['price'],
						'num'=>$goodsdetail['num'],
						'image'=>$village_group_goods['image'],
					);
					$goodsList[] = $goods;
				}
			}
		}
		$this->assign('finishRefunds', $goodsList);
		$this->display();
	}

	//退款申请
	public function replyRefund()
	{

		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
		$type = isset($_POST['type']) ? $_POST['type'] : 'agree';
		$reply_content = $_POST['reply_content'];
		//$refund_price = isset($_POST['refund_price']) ? intval($_POST['refund_price']) : 0;

		$order = D('Village_group_order')->field(true)->where(array('order_id' => $order_id))->find();
		$now_order = $order;
		$order_refund = M('Village_group_refund_detail')->where(array('order_id'=>$order_id,'status'=>0))->find();
		if (empty($order)) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('订单信息错误'))));
		}
		if (!in_array($type, array('agree', 'disagree'))) {
			exit(json_encode(array('errcode' => 1, 'msg' => L_('请选择正确的回复形式'))));
		}

		$platPay = D('Plat_order')->where(array('business_id'=>$_POST['order_id'],'business_type'=>'village_group'))->find();


		if ($type == 'agree') {
			//$nowPayMoney = floatval($refund_price);//当前要退款的金额
			//判断支付方式
			$balance_money = 0;
			if($order_refund['balance_money']>0){
				$balance_money = $order_refund['balance_money'];
			}
			$nowPayMoney = 0;
			if($order_refund['pay_money'] > 0){
				$nowPayMoney = $order_refund['pay_money'];
			}
			$score_used_count = 0;
			if($order_refund['score_used_count']>0){
				$score_used_count = $order_refund['score_used_count'];
			}
			if($balance_money == 0){
				$balance_money = $platPay['system_balance'];
			}
			if($balance_money > 0){
				D('User')->add_money($order['uid'],$balance_money,'社区团购申请售后退款,增加余额，订单编号' . $order['real_orderid']);
			}

			if ($score_used_count != 0) {
				D('User')->add_score($order['uid'], $score_used_count,'社区团购退款, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);

			}


			//除了余额支付以外的支付
			if(!empty($platPay['pay_type']) && $this->config['open_allinyun']==1){
				import('@.ORG.AccountDeposit.AccountDeposit');
				$deposit = new AccountDeposit('Allinyun');
				$allyun = $deposit->getDeposit();

				$allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid']);

				if($allinyun_user['bizUserId']!=''){
					$allyun->setUser($allinyun_user);
					$params['bizOrderNo'] = 'shop_'.$order['orderid'];
					$params['oriBizOrderNo'] = $order['third_id'];
					$params['amount'] = intval($nowPayMoney*100);
					$res = $allyun->refundApply($params);

					if($res['status']=='OK'){
						$data_shop_order['order_id'] = $order['order_id'];
						$data_shop_order['status'] = 4;
						$data_shop_order['last_time'] = time();
						$data_shop_order['refund_detail'] = serialize($res);

						D('Village_group_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
						D('Village_group_order')->data($data_shop_order)->save();
                        //增加消息通知
                        $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                        $params['type'] = 0;
                        $params['business'] = 'village_group';
                        $params['order_id'] = $order_id;
                        $params['title'] = '商家已经同意退款';
                        $params['content'] = '您申请的退款商家已经同意，请查看订单详情';
                        invoke_v20_service($url, [$params]);
						exit(json_encode(array('errcode' => 0, 'msg' =>L_('退款成功'))));
					}else{
						exit(json_encode(array('errcode' => 1, 'msg' =>$res['message'])));
					}
				}
			}
			//在线支付的钱

			if(!empty($platPay['pay_type']) && $platPay['paid'] == 1){//支付状态
				if ($now_order['is_own'] && $now_order['pay_type'] != 'nmgpay') {
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
					//不存在return
					if(empty($merchant_ownpay)){
						return false;
					}
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
						}
					}
					$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
					if ($now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
						$pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
						$pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
						$pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
						$pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
						$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
						$pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
						$pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
						$pay_method['weixin']['config']['is_own'] = 2;
					}
				} else {
					$pay_method = D('Config')->get_pay_method(0, 0, 1);
				}

				if (empty($pay_method)) {
					$this->error_tips('系统管理员没开启任一一种支付方式！');
				}

				$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$platPay['order_id'],'pay_type'=>$platPay['pay_type'],'paid_money'=>array('gt',0),"orderid"=>$platPay['orderid']))->find();
				if($payment_money_order && $payment_money_order['pay_method'] == 'wft'){
					$payment_money = $payment_money_order['payment_money'];
					if($payment_money_order['pay_method'] == 'wft'){
						$pay_method = 'wftpay';
					}
					import('@.ORG.LowFeePay');
					$lowfeepay = new LowFeePay($pay_method);
					$order['payment_refund_money'] = $platPay['pay_money'];
					$order['payment_money'] = $payment_money_order['paid_money'];
					$order['orderNo'] = $payment_money_order['orderNo'];
					$go_refund_param= $lowfeepay->refund($order);
				}
				else{

					$pay_class_name = ucfirst($now_order['pay_type']);
					if ($pay_class_name == 'Alipay' &&  $this->config['new_pay_alipay_app_public_key'] != '' && $this->config['new_pay_alipay_app_appid'] != '' && $this->config['new_pay_alipay_app_private_key'] != '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
					}
                    if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                        $pay_class_name = 'Hqpay';
                        empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                    }
					if (empty($pay_method[$now_order['pay_type']])) {
						$this->error_tips('您选择的支付方式不存在，请更新支付方式！');
					}
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if (empty($import_result)) {
						$this->error_tips('系统管理员暂未开启该支付方式，请更换其他的支付方式');
					}

				$now_order['order_type'] = 'plat';
				$now_order['is_mobile_pay'] = $platPay['is_mobile_pay']; 
				if (!empty($now_order['order_id'])) {
					$now_order['order_id'] = $platPay['orderid'];
				}
				if ($platPay['is_mobile_pay'] == 3) {
					$pay_method[$now_order['pay_type']]['config'] = array(
						'pay_weixin_appid' => $this->config['pay_wxapp_appid'],
						'pay_weixin_key' => $this->config['pay_wxapp_key'],
						'pay_weixin_mchid' => $this->config['pay_wxapp_mchid'],
						'pay_weixin_appsecret' => $this->config['pay_wxapp_appsecret'],
					);

						if($platPay['is_own']){
							$now_merchant_wxapp_ownpay = M('Weixin_app_bind')->where(array('other_id'=>$now_order['mer_id']))->find();
							$pay_method[$now_order['pay_type']]['config'] = array(
								'pay_weixin_appid' => $now_merchant_wxapp_ownpay['appid'],
								'pay_weixin_key' => $now_merchant_wxapp_ownpay['wxpay_key'],
								'pay_weixin_mchid' => $now_merchant_wxapp_ownpay['wxpay_merid'],
								'pay_weixin_appsecret' =>$now_merchant_wxapp_ownpay['appsecret'],
							);
							C('config.pay_weixin_client_cert', $now_merchant_wxapp_ownpay['public_key']);
							C('config.pay_weixin_client_key', $now_merchant_wxapp_ownpay['private_key']);
						}
					}
					$now_order['payment_money'] = $platPay['pay_money'];
					if($nowPayMoney == 0) {
						$nowPayMoney = $platPay['pay_money'];
					}
					//退钱进行钱处理
					$pay_class = new $pay_class_name($now_order, $nowPayMoney, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, $now_order['is_mobile_pay']);
					fdump($pay_class_name,'shequpintuan',true);
					fdump($pay_class,'shequpintuan',true);
					$go_refund_param = $pay_class->refund();
					fdump($go_refund_param,'shequpintuan',true);
				}
			}


			/*重要！！！*/

			if($order_refund['goods_id']){
				$order_refund_goods = explode(',',$order_refund['goods_id']);
				foreach($order_refund_goods as $k=>$v){
					$model = M('village_group_order_detail')->field('mer_id,purchase_price')->where(array('order_id'=>$order_id,'goods_id'=>$v))->find();
					if($model['mer_id'] > 0){
                        $mdata = D('Merchant_money_list')
                            ->field('percent,type,money,system_take')
                            ->where(['type' => 'village_group','income'=>1, 'order_id' => $order_id])
                            ->find();
                        $returnSystemTake = $returnTakePercent = 0;
                        if($mdata['system_take'] > 0){
                            $returnSystemTake = bcmul(bcdiv($model['purchase_price'],$mdata['money'],2), $mdata['system_take'],2);
                            $returnTakePercent = $mdata['percent'];
                        }
						D('Merchant_money_list')->use_money(
							$model['mer_id'], 
							$model['purchase_price'], 
							'village_group',
							'社区拼团退款,减少余额,订单编号' . $order['real_orderid'], 
							$order_id,
							$returnTakePercent,
							$returnSystemTake,
							[],
							0,
							$go_refund_param['refund_param']??[]
						);
					}

				}
			}
			$order_type='village_group';//平台退款标志===>郭先森
			D('Village_group_refund_log')->add(array('refund_id' => $refund_id, 'status' => 1, 'dateline' => time(), 'note' => $reply_content));
			$data = array();
			$data['status'] = 1;
			$data['reply_time'] = time();
			M('Village_group_refund_detail')->where(array('order_id'=>$_POST['order_id'],'status'=>0,'id'=>$refund_id))->save($data);
			//订单表加钱

			$village_group_order =  D('Village_group_order')->where(array('order_id' => $order_id, 'uid' => $order['uid']))->find();
			$refundDetail = M('Village_group_refund_detail')->where(array('order_id'=>$_POST['order_id'],'id'=>$refund_id))->find();
			if($village_group_order['refund_money'] == 0){
				D('Village_group_order')->where(array('order_id' => $order_id, 'uid' => $order['uid'],'is_apply_refund'=>1))->save(array('refund_money'=>$refundDetail['price']));
			}else{
				D('Village_group_order')->where(array('order_id' => $order_id, 'uid' => $order['uid'],'is_apply_refund'=>1))->save(array('refund_money'=>$refundDetail['price']+$village_group_order['refund_money']));
			}
			$count = D('Village_group_order_detail')->where(array('order_id' => $order_id, 'refundNum' => 0))->count();

			$saveData = array('is_apply_refund' => 0);
            $system_status = 0;
			if (empty($count)) {
				$saveData['status'] = 4;
				$system_status = 4;
			}
            bcscale(3);
			//退佣金
			if($refundDetail['price'] >= $now_order['discount_price']){
                $start_user_id = D('Village_group_header')->where(['id' => $now_order['start_uid']])->getField('uid');
                if ($start_user_id) {
                    D('User')->user_money($start_user_id, $now_order['spread_money'], L_("商品退货，扣除团长佣金。订单编号X2", array("X2" => $now_order['real_orderid'])));
                }

				if(!empty($now_order['share_user_commission'])){
                    D('User')->user_money($now_order['share_uid'], $now_order['share_user_commission'], L_("商品退货，扣除推广佣金。订单编号X2",array("X2" => $now_order['real_orderid'])));
                    D('Village_group_order')->where(array('order_id' => $order_id))->save(['share_user_commission' => 0]);
                }
			}
			else{
				$tmoney = getFormatNumber(($refundDetail['price']/$now_order['discount_price'])*$now_order['spread_money']);
				D('User')->user_money($now_order['start_uid'], $tmoney, L_("商品部分退货，扣除团长佣金。订单编号X2",array("X2" => $now_order['real_orderid'])));
				
                if(!empty($now_order['share_user_commission'])){
                    $commission = getFormatNumber(($refundDetail['price']/$now_order['discount_price'])*$now_order['share_user_commission']);
                    D('User')->user_money($now_order['share_uid'], $commission, L_("商品退货，扣除推广佣金。订单编号X2",array("X2" => $now_order['real_orderid'])));
                    
                    D('Village_group_order')->where(array('order_id' => $order_id))->save(['share_user_commission' => bcsub($now_order['share_user_commission'],$commission)]);
                }
			}

			D('Village_group_order')->where(array('order_id' => $order_id))->save($saveData);
			/*更新平台订单状态*/
            if($system_status){
                $saveData['system_status'] = $system_status;
            }
			D('System_order')->save_order_info($order_type,$order_id,$saveData);
            //增加消息通知
            $url = '\app\common\model\service\user\UserNoticeService/addNotice';
            $params['type'] = 0;
            $params['business'] = 'village_group';
            $params['order_id'] = $order_id;
            $params['title'] = '商家已经同意退款';
            $params['content'] = '您申请的退款商家已经同意，请查看订单详情';
            invoke_v20_service($url, [$params]);
			exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));
		} else {
			D('Village_group_refund_log')->add(array('refund_id' => $refund_id, 'status' => 2, 'dateline' => time(), 'note' => $reply_content));
			$refundDetailList = D('Village_group_refund_detail')->where(array('id' => $refund_id,'status'=>0))->find();
			$data['status'] = 2;
			D('Village_group_refund_detail')->where(array('id' => $refund_id,'status'=>0))->save($data);
			D('Village_group_order')->where(array('order_id' => $order_id))->save(array('is_apply_refund' => 0));
			exit(json_encode(array('errcode' => 0, 'msg' => 'ok')));
		}
	}



	public function config(){
		redirect(U('Config/index',array('galias'=>'village_group','header'=>'Village_group/config')));
	}

	public function send_list(){

		if(empty($_GET['begin_time'])){
			$_GET['begin_time'] = '';
		}

		if(empty($_GET['end_time'])){
			$_GET['end_time'] = '';
		}
		if($_GET['goods_id']){
			$now_goods = M('Village_group_goods')->where(array('goods_id'=>$_GET['goods_id']))->find();
			$where['d.goods_id'] = $_GET['goods_id'];
		}

        $start_uid = array();
		if($_GET['searchtype'] &&  $_GET['keyword']!=''){
			switch($_GET['searchtype']){
				case 'order_id':
					$where['o.order_id'] = $_GET['keyword'];
					break;
				case 'name':
					$where['g.name'] = $_GET['keyword'];
					break;
				case 'goods_id':
					$where['g.goods_id'] = $_GET['keyword'];
					break;
				case 'mer_id':
					$where['g.mer_id'] = $_GET['keyword'];
					break;
				case 'mer_name':
					$where['m.name'] = ['like', '%'.$_GET['keyword'].'%'];
					break;
				case 'village_name':
					$village_list = M('Village_group_pick_address')->field('p.header_uid')->join('as p LEFT JOIN '.C('DB_PREFIX').'village_group_header h ON h.id = p.header_uid')->where(array('p.name'=>array('like',"%{$_GET['keyword']}%"),'p.status'=>1,'h.status'=>1))->select();

					foreach ($village_list as $v) {
						$start_uid[] = $v['header_uid'];
					}
					$where['o.start_uid']  = array('in',$start_uid);

					break;
			}
		}

        $where_header = array();
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
//                die('区域管理员无法管理用户');
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $where_header[$area_index] = $this->system_session['area_id'];
        }
        // 处理省市区
        if ($_GET['province_idss'] || $_GET['city_idss'] || $_GET['area_id'] || !empty($where_header)) {
            $where_header['header_uid'] = array('gt',0);
            if ($_GET['province_idss']) {
                $where_header['province_id'] = $_GET['province_idss'];
            }
            if ($_GET['city_idss']) {
                $where_header['city_id'] = $_GET['city_idss'];
            }
            if ($_GET['area_id']) {
                $where_header['area_id'] = $_GET['area_id'];
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[]=$val['header_uid'];
            }
            if ($start_uid) {
                // $header_arr = array_merge($header_arr, $start_uid);
                $header_arr = array_intersect($header_arr,$start_uid); // 不是合并条件而是查询比对出同样的团长id
                $header_arr = array_values($header_arr); // 重新排序
            }
            $where['o.start_uid'] = array('in', $header_arr);
        }

//        $where['d.status'] = 0;
        $where['o.village_group_type'] = array('neq',2);
        $where['o.status'] =array('in','1,2,3,5,6,7,8');
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])&& !empty($_GET['searchtimetype'])){
            $begin_time = strtotime($_GET['begin_time'].":00");
            $end_time = strtotime($_GET['end_time'].":00");
            if ($begin_time>$end_time) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array($begin_time,$end_time);
            if($_GET['searchtimetype'] == 'pay_time'){
                $where['o.'.$_GET['searchtimetype']] = ['between', $period];
            }
            else{
                $where['d.'.$_GET['searchtimetype']] = ['between', $period];
            }
			// $where['_string']= "(g.village_group_type=1 AND (d.send_time  BETWEEN ".$period[0].' AND '.$period[1].") AND d.date_num<=o.newest_delivery_periods) OR (g.village_group_type=0 AND ((g.send_time BETWEEN ".$period[0].' AND '.$period[1].") OR (g.send_time=0 AND o.add_time BETWEEN ".($period[0]-86400).' AND '.($period[1]-86400).")))";
		}
        $where['d.is_merchant_send'] = 0;
        $where['_string'] = 'd.date_num = o.newest_delivery_periods AND d.is_delay=0';
		$count = M('Village_group_order_detail')->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id  left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id')->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count,20);

		$start_list = M('Village_group_order_detail')->field('d.id,o.note,o.delivery_periods,o.newest_delivery_periods,o.pick_addr,d.increase_price,d.area_price,d.village_group_type,d.name,d.send_time as d_send_time,d.real_send_time,d.service_time,d.date_num,d.is_delay,d.delay_time,d.delay_user,u.uid,o.order_id,o.add_time,o.pay_time,o.status,d.status as deliver_status,o.start_uid,d.goods_id,d.price,d.purchase_price,d.num,g.mer_id,m.name as merchant_name,u.phone,u.nickname,g.start_time,g.end_time,g.send_time,g.get_time,d.arrive_cutting_time,d.arrive_time')
				->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
				->where($where)->order('o.order_id DESC,d.name desc,d.send_time DESC')->limit($p->firstRow.','.$p->listRows)->select();
		if ($start_list) {
			// 单独查询一下 团长信息
			$m_village_group_header = D('Village_group');

			foreach ($start_list as &$val) {
				$header_tmp = $m_village_group_header->get_header_info( '',$val['start_uid'],true);
				$val['header_info'] = $header_tmp['msg'];
				// 查询一下团购购买数量
				$val['all_num'] = $val['num'];
				$increase_price = $val['increase_price']>0?$val['increase_price']:0;
				if ($val['area_price']>0) {
                    $val['all_price'] = ($val['area_price']+$increase_price)*$val['num'];
                } else {
                    $val['all_price'] = ($val['price']+$increase_price)*$val['num'];
                }
				// $val['note'] = empty($val['note'])?'':mb_strlen($val['note']) > 14 ? substr($val['note'],0,39).'.....':$val['note'];
				$val['all_purchase_price'] = $val['purchase_price']*$val['num'];
                if ($val['delivery_periods']>1) {
                    $val['all_price'] = round_number($val['all_price']/$val['delivery_periods'],2);
                    $val['all_purchase_price'] = round_number($val['all_purchase_price']/$val['delivery_periods'],2);
                }
			}
		}

		$pay_method = D('Config')->get_pay_method('','',1);

		$this->assign('pay_method',$pay_method);
		$this->assign('order_status',D('Village_group')->order_status);
		$this->assign('start_list',$start_list);
		$this->assign('now_goods',$now_goods);
		$this->assign('pagebar',$p->show());
        $this->assign('is_menu_hide',$_GET['is_menu_hide']?:0);


		$this->display();
	}


	public function send_list1(){

		if(empty($_GET['begin_time'])){
			$_GET['begin_time'] = date('Y-m-d');
		}

		if(empty($_GET['end_time'])){
			$_GET['end_time'] = date('Y-m-d');
		}
		if($_GET['goods_id']){
			$now_goods = M('Village_group_goods')->where(array('goods_id'=>$_GET['goods_id']))->find();
			$where['d.goods_id'] = $_GET['goods_id'];
		}

		if($_GET['searchtype'] &&  $_GET['keyword']!=''){
			switch($_GET['searchtype']){
				case 'order_id':
					$where['o.order_id'] = $_GET['keyword'];
					break;
				case 'name':
					$where['g.name'] = $_GET['keyword'];
					break;
				case 'goods_id':
					$where['g.goods_id'] = $_GET['keyword'];
					break;
			}
		}

//		$where['d.status'] =0;
		$where['o.status'] =array('in','1,2,3,5,6,7,8');
		if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
			if ($_GET['begin_time']>$_GET['end_time']) {
				$this->error_tips("结束时间应大于开始时间");
			}

			$period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
			$where['_string']= "((g.send_time BETWEEN ".$period[0].' AND '.$period[1].") OR (g.send_time=0 AND o.add_time BETWEEN ".($period[0]-86400).' AND '.($period[1]-86400)."))";
		}
		$count = M('Village_group_order_detail')->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id  left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id')->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count,20);

		$start_list = M('Village_group_order_detail')->field('d.name,u.uid,o.order_id,o.add_time,o.pay_time,o.status,d.status as deliver_status,o.start_uid,d.goods_id,d.price,d.purchase_price,d.num,u.phone,u.nickname,g.start_time,g.end_time,g.send_time,g.get_time')
			->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
			->where($where)->order('o.order_id DESC,d.name desc')->limit($p->firstRow.','.$p->listRows)->select();

		if ($start_list) {
			// 单独查询一下 团长信息
			$m_village_group_header = D('Village_group');

			foreach ($start_list as &$val) {
				$header_tmp = $m_village_group_header->get_header_info( '',$val['start_uid']);
				$val['header_info'] = $header_tmp['msg'];
				// 查询一下团购购买数量
				$val['all_num'] = $val['num'];
				$val['all_price'] = $val['price']*$val['num'];
				$val['all_purchase_price'] = $val['purchase_price']*$val['num'];
			}
		}

		$pay_method = D('Config')->get_pay_method('','',1);
		$this->assign('pay_method',$pay_method);
		$this->assign('order_status',D('Village_group')->order_status);
		$this->assign('start_list',$start_list);
		$this->assign('now_goods',$now_goods);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	public function pre_print(){
		if(empty($_GET['begin_time'])){
			$_GET['begin_time'] = date('Y-m-d').' 00:00';
		}

		if(empty($_GET['end_time'])){
			$_GET['end_time'] = date('Y-m-d').' 23:59';
		}
		if($_GET['goods_id']){
			$now_goods = M('Village_group_goods')->where(array('goods_id'=>$_GET['goods_id']))->find();
			$where['d.goods_id'] = $_GET['goods_id'];
		}

        $start_uid = array();
        if($_GET['searchtype'] &&  $_GET['keyword']!=''){
            switch($_GET['searchtype']){
                case 'order_id':
                    $where['o.order_id'] = $_GET['keyword'];
                    break;
                case 'name':
                    $where['g.name'] = $_GET['keyword'];
                    break;
                case 'goods_id':
                    $where['g.goods_id'] = $_GET['keyword'];
                    break;
				case 'mer_id':
					$where['g.mer_id'] = $_GET['keyword'];
					break;
				case 'mer_name':
					$where['m.name'] = ['like', '%'.$_GET['keyword'].'%'];
					break;
                case 'village_name':
                    $village_list = M('Village_group_pick_address')->field('p.header_uid')->join('as p LEFT JOIN '.C('DB_PREFIX').'village_group_header h ON h.id = p.header_uid')->where(array('p.name'=>array('like',"%{$_GET['keyword']}%"),'p.status'=>1,'h.status'=>1))->select();

                    foreach ($village_list as $v) {
                        $start_uid[] = $v['header_uid'];
                    }
                    $where['o.start_uid']  = array('in',$start_uid);

                    break;
            }
        }
        // 处理省市区
        if (($_GET['province_idss'] || $_GET['city_idss'] || $_GET['area_id']) && empty($where['o.start_uid'])) {
            $where_header = array(
                'header_uid' => array('gt',0)
            );
            if ($_GET['province_idss']) {
                $where_header['province_id'] = $_GET['province_idss'];
            }
            if ($_GET['city_idss']) {
                $where_header['city_id'] = $_GET['city_idss'];
            }
            if ($_GET['area_id']) {
                $where_header['area_id'] = $_GET['area_id'];
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[]=$val['header_uid'];
            }
            if ($start_uid) {
                $header_arr = array_merge($header_arr, $start_uid);
            }
            $where['o.start_uid'] = array('in', $header_arr);
        }

        $where['d.status'] = 0;
		$where['o.status'] =array('in','1,2,3,5,6,7,8');
        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])&& !empty($_GET['searchtimetype'])){
            $begin_time = strtotime($_GET['begin_time'].":00");
            $end_time = strtotime($_GET['end_time'].":00");
            if ($begin_time>$end_time) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array($begin_time,$end_time);
            if($_GET['searchtimetype'] == 'pay_time'){
                $where['o.'.$_GET['searchtimetype']] = ['between', $period];
            }
            else{
                $where['d.'.$_GET['searchtimetype']] = ['between', $period];
            }
			// $where['_string']= "(g.village_group_type=1 AND (d.send_time  BETWEEN ".$period[0].' AND '.$period[1].") AND d.date_num<=o.newest_delivery_periods) OR (g.village_group_type=0 AND ((g.send_time BETWEEN ".$period[0].' AND '.$period[1].") OR (g.send_time=0 AND o.add_time BETWEEN ".($period[0]-86400).' AND '.($period[1]-86400).")))";
		}
        $where['d.is_merchant_send'] = 0;
		$count = M('Village_group_order_detail')->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id  left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id')->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count,20);

		$start_list = M('Village_group_order_detail')->field('o.note,o.delivery_periods,o.newest_delivery_periods,o.pick_addr,o.pick_name,o.pick_phone,d.increase_price,d.area_price,d.village_group_type,d.name,d.send_time as d_send_time,d.real_send_time,d.service_time,d.date_num,d.is_delay,d.delay_time,d.delay_user,u.uid,o.order_id,o.add_time,o.pay_time,o.status,d.status as deliver_status,o.start_uid,d.goods_id,d.price,d.purchase_price,d.num,g.mer_id,m.name as merchant_name,u.phone,u.nickname,g.start_time,g.end_time,g.send_time,g.get_time')
				->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
				->where($where)->order('o.order_id DESC,d.name desc')->limit($p->firstRow.','.$p->listRows)->select();

        $arr = array();
		if ($start_list) {
			// 单独查询一下 团长信息
			$m_village_group_header = D('Village_group');

			foreach ($start_list as &$val) {
				$header_tmp = $m_village_group_header->get_header_info( '',$val['start_uid'],true);
				$val['header_info'] = $header_tmp['msg'];
				// 查询一下团购购买数量
				$val['all_num'] = $val['num'];
                $increase_price = $val['increase_price']>0?$val['increase_price']:0;
                if ($val['area_price']>0) {
                    $val['all_price'] = ($val['area_price']+$increase_price)*$val['num'];
                } else {
                    $val['all_price'] = ($val['price']+$increase_price)*$val['num'];
                }
                $val['all_purchase_price'] = $val['purchase_price']*$val['num'];
                if ($val['delivery_periods']>1) {
                    $val['all_price'] = round_number($val['all_price']/$val['delivery_periods'],2);
                    $val['all_purchase_price'] = round_number($val['all_purchase_price']/$val['delivery_periods'],2);
                }
                $arr[$val['order_id']][] = $val;
			}
		}

		$pay_method = D('Config')->get_pay_method('','',1);
		$this->assign('pay_method',$pay_method);
		$this->assign('order_status',D('Village_group')->order_status);
		$this->assign('start_list',$arr);
		$this->assign('now_goods',$now_goods);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	public function leader_print(){


//		$where['d.status'] =0;
        $where['o.status'] =array('in','1,2,3,5,6,7,8');
		$where['o.start_uid'] =$_GET['leader_id'];
		$where['d.status'] =0;
        $where['_string']= "(g.village_group_type=1 AND d.date_num<=o.newest_delivery_periods) OR (g.village_group_type=0 AND  d.date_num=1)";

        $count = M('Village_group_order_detail')->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id  left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id')->where($where)->count();
		import('@.ORG.system_page');


		$start_list = M('Village_group_order_detail')->field('o.delivery_periods,o.newest_delivery_periods,o.pick_addr,d.increase_price,d.area_price,d.village_group_type,d.name,d.send_time as d_send_time,d.real_send_time,d.service_time,d.date_num,d.is_delay,d.delay_time,d.delay_user,u.uid,o.order_id,o.add_time,o.pay_time,o.status,d.status as deliver_status,o.start_uid,d.goods_id,d.price,d.purchase_price,d.num,u.phone,u.nickname,g.start_time,g.end_time,g.send_time,g.get_time')
				->join('as d left join '.C('DB_PREFIX').'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id  left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')
				->where($where)->order('o.order_id DESC,d.name desc')->select();

		$m_village_group_header = D('Village_group');
		if ($start_list) {
			// 单独查询一下 团长信息

			foreach ($start_list as &$val) {

				// 查询一下团购购买数量
				$val['all_num'] = $val['num'];
				$val['all_price'] = $val['price']*$val['num'];
				$val['all_purchase_price'] = $val['purchase_price']*$val['num'];
                $increase_price = $val['increase_price']>0?$val['increase_price']:0;
                if ($val['area_price']>0) {
                    $val['all_price'] = ($val['area_price']+$increase_price)*$val['num'];
                } else {
                    $val['all_price'] = ($val['price']+$increase_price)*$val['num'];
                }
                $val['all_purchase_price'] = $val['purchase_price']*$val['num'];
                if ($val['delivery_periods']>1) {
                    $val['all_price'] = round_number($val['all_price']/$val['delivery_periods'],2);
                    $val['all_purchase_price'] = round_number($val['all_purchase_price']/$val['delivery_periods'],2);
                }
			}
		}

		$header_tmp = $m_village_group_header->get_header_info( '',$_GET['leader_id'],true);
		$header = $header_tmp['msg'];
		
		$pay_method = D('Config')->get_pay_method('','',1);
		$this->assign('pay_method',$pay_method);
		$this->assign('order_status',D('Village_group')->order_status);
		$this->assign('start_list',$start_list);
		$this->assign('header',$header);
		$this->display();
	}

	public function label_list(){
		$list = M('Village_group_label')->where(array('status'=>array('neq',4)))->order('id desc')->select();

		$this->assign('label_list',$list);
		$this->display();
	}

	public function add_label(){

		if(IS_POST){
			$image = D('Image')->handle($this->system_session['id'], 'village_group_label', 0, array('size' => 10), false);

			if (!$image['error']) {
				$_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
			} else {
//				$this->frame_submit_tips(0, $image['message']);
			}
//			$_POST['color'] = RGBToHex($_POST['color']);
			if($_POST['id']){
				$before = M('Village_group_label')->where(array('id'=>$_POST['id']))->find();
				$res = M('Village_group_label')->where(array('id'=>$_POST['id']))->save($_POST);
				$after = M('Village_group_label')->where(array('id'=>$_POST['id']))->find();
				D("System_log")->add_log("编辑了".$this->config['village_group_alias']."标签，标签名称：".$_POST['name'],$this->config['village_group_alias'],$_POST['id'], $before, $after);
			}else{
				$res = M('Village_group_label')->add($_POST);
				D("System_log")->add_log("增加了".$this->config['village_group_alias']."标签，标签名称：".$_POST['name'],$this->config['village_group_alias'],$res);
			}

			if($res){
				$this->frame_submit_tips(1,'操作成功！');
			}else{
				$this->frame_submit_tips(0,'操作失败！请重试~');
			}
		}else{
			$res = M('Village_group_label')->where(array('id'=>$_GET['id']))->find();

			$this->assign('label',$res);
			$this->display();
		}
	}

	public function del_label(){
		M('Village_group_label')->where(array('id'=>$_POST['id']))->setField('status',4);
		D("System_log")->add_log("删除了".$this->config['village_group_alias']."标签，标签ID：".$_POST['id'],$this->config['village_group_alias'],$_POST['id']);
		$this->success('删除成功！');
	}

	// 区域列表
	public function area_list(){
        $database_village_group_area = D('Village_group_area');
        $database_area = D('Area');

//        var_dump($this->system_session['area_id']);
//        var_dump($this->system_session['area_type']);

        if(C('config.village_group_city_manage') && $this->system_session['level'] == 1) {
            $condition_area = [
                'area_id' => $this->system_session['area_id'],
                'area_type' => $this->system_session['area_type'],
            ];
        } else {

            if (empty($_GET['type'])) $_GET['type'] = 1;
            $_GET['pid'] = intval($_GET['pid']);


            $condition_area['area_pid'] = $_GET['pid'];
            $condition_area['area_type'] = $_GET['type'];
            $city_id = 0;
            $province_id = 0;
            if ($_GET['pid'] > 0) {
                if ($this->system_session['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $city_id = $now_area['area_pid'];
                    } elseif ($now_area['area_type'] == 2) {
                        $city_id = $this->system_session['area_id'];
                    }
                }
            } else {
                if ($this->system_session['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $now_area = D('Area')->field(true)->where(array('area_id' => $now_area['area_pid']))->find();
                        $province_id = $now_area['area_pid'];
                    } elseif ($now_area['area_type'] == 2) {
                        $province_id = $now_area['area_pid'];
                    } elseif ($now_area['area_type'] == 1) {
                        $province_id = $this->system_session['area_id'];
                    }
                }

            }
            if ($province_id > 0 || $city_id > 0) {
                $area_id = $province_id > 0 ? $province_id : $city_id;

                $now_area = $database_area->field(true)->where(array('area_id' => $area_id))->find();
                if ($now_area['area_type']) {
                    $_GET['type'] = $now_area['area_type'];
                }
                if ($now_area['area_pid']) {
                    $_GET['pid'] = $now_area['area_pid'];
                }
                $condition_area['area_id'] = $area_id;
            } else {
                $now_area = $database_area->field(true)->where(array('area_id' => $_GET['pid']))->find();
            }
            $this->assign('now_area', $now_area);
        }
		$order = '`area_sort` DESC,`area_id` ASC';
		$area_list = $database_village_group_area->get_area($condition_area);
		$this->assign('area_list',$area_list);
		switch($_GET['type']){
			case 1:
				$now_type_str = '省份';
				break;
			case 2:
				$now_type_str = '城市';
				break;
			case 3:
				$now_type_str = '区(县)';
				break;
		}
		
		$this->assign('now_type_str',$now_type_str);

		$city_manage = C('config.village_group_city_manage');
		$this->assign('city_manage', $city_manage);
		$this->assign('level', $this->system_session['level']);

		$this->display();
    }

    //添加省份或城市
    public function area_add(){
		$database_village_group_area =D('Village_group_area');
		$database_area =D('Area');
		$type = $_GET['type'];
		$area_pid = $_GET['pid'];
		
		if (IS_POST) {
			if (!$_POST['area_id']) {
				$this->error('请选择城市');
			}
			if ($database_village_group_area->where(['area_id' => $_POST['area_id']])->find()) {
                $this->error('该城市已存在，请勿重复添加');
            }
			$insert_id = $database_village_group_area->add($_POST);
			if($insert_id){
				D("System_log")->add_log("增加了".$this->config['village_group_alias']."城市，城市ID：".$_POST['area_id'],$this->config['village_group_alias'],$insert_id);
				$this->success('添加成功');
			}else{
				$this->success('添加失败');
			}
		}else{
			if ($type == 1) { //添加省份
				// 获得所有省份
				$area_bind_list = $database_village_group_area->get_area(array('is_open' => 1));
				$area_bind_ids = array();
				foreach ($area_bind_list as $area) {
					$area_bind_ids[] = $area['area_id'];
				}
                $province_id = 0;
                if ($this->system_session['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('is_open' => 1, 'area_id' => $this->system_session['area_id']))->find();
                    if($now_area['area_type']==3){
                        $now_area = D('Area')->field(true)->where(array('is_open' => 1, 'area_id' => $now_area['area_pid']))->find();
                        $province_id = $now_area['area_pid'];
                    } elseif($now_area['area_type']==2){
                        $province_id = $now_area['area_pid'];
                    }elseif($now_area['area_type']==1){
                        $province_id = $this->system_session['area_id'];
                    }
                }
                if ($province_id>0) {
                    $area_where = array(
                        'area_id' => $province_id,
                        'area_type' => 1,
                        'is_open' => 1,
                    );
                    $area_list = $database_area->field(true)->where($area_where)->select();
                } else {
                    $area_list = $database_area->get_all_city(1,1,$province_id);
                }
				foreach ($area_list as$key => $value) {
					if (in_array($value['area_id'], $area_bind_ids) || $value['is_open']!=1) {
						unset($area_list[$key]);;
					}
				}
				if (empty($area_list)) {
                    $this->frame_error_tips('当前您已经没有可添加的省份了！');
                }
			}else{
				$area_bind_list = $database_village_group_area->get_area();
				$area_bind_ids = array();
				foreach ($area_bind_list as $area) {
					$area_bind_ids[] = $area['area_id'];
				}
                $city_id = 0;
                if ($this->system_session['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('is_open' => 1, 'area_id' => $this->system_session['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $city_id = $now_area['area_pid'];
                    } elseif($now_area['area_type']==2){
                        $city_id = $this->system_session['area_id'];
                    }
                }
                if ($city_id>0) {
                    $area_where = array(
                        'area_id' => $city_id,
                        'area_type' => 2,
                        'is_open' => 1,
                    );
                    $area_list = $database_area->field(true)->where($area_where)->select();
                    if (empty($area_list)) {
                        $this->frame_error_tips('当前您已经没有可添加的城市了！');
                    }
                } else {
                    // 获得该省份下所有城市
                    $area_list = $database_area->get_arealist_by_areaPid($area_pid);
                }

				foreach ($area_list as $key => $value) {
					if (in_array($value['area_id'], $area_bind_ids) || $value['is_open']!=1) {
						unset($area_list[$key]);;
					}
				}
			}
			$this->assign('area_list',$area_list);
			$this->display();
		}
	}

    //编辑省份或城市
	public function area_edit(){
		$database_village_group_area =D('Village_group_area');
		$database_area =D('Area');
		$pigcms_id = $_GET['pigcms_id'];
		
		$area_detail = $database_village_group_area->get_area(array('pigcms_id'=>$pigcms_id));
		$area_detail = $area_detail[0];
		$this->assign('area_detail',$area_detail);
		if (!$area_detail) {
			$this->error('该地区不存在');
		}
		if (IS_POST) {
			$upd = $database_village_group_area->save($_POST);
			if($upd){
				D("System_log")->add_log("编辑了".$this->config['village_group_alias']."城市，城市ID：".$_POST['area_id'],$this->config['village_group_alias'],$_POST['pigcms_id']);
				$this->success('编辑成功');
			}else{
				$this->error('编辑失败');
			}
		}else{
			
			$this->display();
		}
	}

	//同步平台城市
    public function copyArea(){
        //获取已添加的城市区域
        $village_area = D('Village_group_area')->getField('area_id',true);
        //获取未添加的城市
        //获取省
        $plat_pro_area = D('Area')->field('area_id,area_pid,area_name,area_type')->where(['is_open'=>1,'area_type'=>1])->select();
        $pro_ids = array_column($plat_pro_area,'area_id');
        $city_ids = [];
        if($pro_ids){
            //获取市
            $plat_city_area = D('Area')->field('area_id,area_pid,area_name,area_type')->where(['is_open'=>1,'area_type'=>2,'area_pid'=>['in',$pro_ids]])->select();
            $city_ids = array_column($plat_city_area,'area_id');
            $plat_pro_area = array_merge($plat_pro_area,$plat_city_area);
        }
        if($city_ids){
            //获取区县
            $plat_area = D('Area')->field('area_id,area_pid,area_name,area_type')->where(['is_open'=>1,'area_type'=>2,'area_pid'=>['in',$city_ids]])->select();
            $plat_pro_area = array_merge($plat_pro_area,$plat_area);
        }
        $save_area = [];
        foreach ($plat_pro_area as $v){
            if(!in_array($v['area_id'],$village_area)){
                $save_area[] = [
                    'area_id' => $v['area_id'],
                    'area_pid' => $v['area_pid'],
                    'area_type' => $v['area_type'],
                ];
            }
        }
        if($save_area){
            if(D('Village_group_area')->addAll($save_area)){
                exit(json_encode(['status'=>0,'data'=>'同步成功！']));
            }else{
                exit(json_encode(['status'=>1,'data'=>'同步失败！']));
            }
        }else{
            exit(json_encode(['status'=>2,'data'=>'没有需要同步的城市！']));
        }
    }

	// 社区拼团商品绑定城市
	public function area_goods_list(){
		$database_village_group_goods_bind_area = D('Village_group_goods_bind_area');
		$condition_group_category['status'] = array('neq','4');

		$_GET['city_id'] && $condition['city_id'] = $_GET['city_id'];
		$goods_list = $database_village_group_goods_bind_area->get_area_goods_list($condition);

		$city_manage = C('config.village_group_city_manage'); // 开启 分城市管理 开关
		if($city_manage) {
		    $this->assign('city_manage', $city_manage);
        }

		$this->assign('goods_list',$goods_list);
		$this->display();
	}

	// 社区拼团商品绑定城市
	public function area_goods_bind(){
		$database_village_group_goods_bind_area = D('Village_group_goods_bind_area');

		$_GET['pigcms_id'] && $condition['pigcms_id'] = $_GET['pigcms_id'];
		if (IS_POST) {
			if (!$_POST['city_id']) {
				$this->error('未选择城市');
			}
			if (!$_POST['pigcms_id'] && !$_POST['goods_id']) {
				$this->error('请选择商品');
			}
			if (!$_POST['price']) {
				$this->error('请输入售价');
			}

			if ($_POST['is_add_price'] == 1) {
				if (floatval($_POST['min_price'])<0 || floatval($_POST['max_price'])<0) {				
					$this->error('加价值不能小于0');
				}

				if (floatval($_POST['min_price'])>floatval($_POST['max_price'])) {
					$this->error('加价最小值不能大于最大值');
				}
			}else{
				$_POST['min_price'] = '0.00';
				$_POST['max_price'] = '0.00';
			}
			if($_POST['buy_user_type']==0){
				$_POST['address_list']='';
			}else{
				$address_list = explode(',', trim($_POST['address_list'],','));
				$_POST['address_list'] = implode(',',array_unique($address_list));
			}
			if ($_POST['pigcms_id']) { // 编辑
				$_POST['last_time'] = time();
				if (M('Village_group_goods_bind_area')->save($_POST)) {
					D("System_log")->add_log("编辑了".$this->config['village_group_alias']."城市商品，城市ID：".$_POST['city_id'],$this->config['village_group_alias'],$_POST['pigcms_id']);
					$this->success('修改绑定成功');
				}else{
					$this->error('修改绑定失败');
				}
			}else{
				//验证是否已经绑定商品
				$is_exist = M('Village_group_goods_bind_area')->where(array('city_id'=>$_POST['city_id'],'goods_id'=>$_POST['goods_id']))->find();
				if ($is_exist) {
					$this->error('您已绑定该商品不能重复绑定');
				}
				$_POST['time'] = time();
				$_POST['last_time'] = time();
				if ($r = M('Village_group_goods_bind_area')->add($_POST)) {
					D("System_log")->add_log("增加了".$this->config['village_group_alias']."城市商品，城市ID：".$_POST['city_id'],$this->config['village_group_alias'],$r);
					$this->success('绑定成功');
				}else{
					$this->error('绑定失败');
				}
			}
		}else{
			if ($_GET['pigcms_id']) { // 编辑
				$goods_list = $database_village_group_goods_bind_area->get_area_goods_list($condition);
				$goods_detail = $goods_list['list'][0] ? $goods_list['list'][0] : array();
				if (!$goods_detail) {
					$this->error('信息不存在');
				}
				$village_list = M('Village_group_pick_address')->where(array('id'=>array('in',trim($goods_detail['address_list'],','))))->select();

				$this->assign('village_list',$village_list);
				$this->assign('goods_detail',$goods_detail);
			}
			if ($_GET['city_id']) {
				// 绑定的区域
				$area_info = M('Village_group_area')->where(array('pigcms_id'=>$_GET['city_id']))->find();
				$this->assign('area_info',$area_info);
			}
			
		}
		
		$this->display();
	}

	// 社区拼团商品绑定城市删除
	public function area_goods_bind_del(){
		$database_village_group_goods_bind_area = D('Village_group_goods_bind_area');
		$pigcms_id =intval($_POST['pigcms_id']);

		if (!$pigcms_id) {
			$this->error('参数传输错误');
		}
		$condition['pigcms_id'] = $pigcms_id;

		$goods_detail = $database_village_group_goods_bind_area->where($condition)->find();
		if (!$goods_detail) {
			$this->error('信息不存在');
		}

		$del = $database_village_group_goods_bind_area->where($condition)->delete();
		if ($del) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	// 社区拼团佣金等级相关配置信息列表
    public function leader_area_level_list(){
        $levelDb = D('Village_group_leader_area_level');
        $condition = array();
        $_GET['city_id'] && $condition['city_id'] = $_GET['city_id'];
        $count = $levelDb->where($condition)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $tmpdatas = $levelDb->order('id ASC')->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('level_list', $tmpdatas);
        $this->display();
    }

	// 社区拼团佣金等级相关配置
    public function add_leader_area_level(){
        $levelDb = M('Village_group_leader_area_level');
        $condition = array();
        $_GET['city_id'] && $condition['city_id'] = $_GET['city_id'];
        $_POST['city_id'] && $condition['city_id'] = $_POST['city_id'];
        $tmparr = $levelDb->where($condition)->order('level DESC')->find();
        $level = 0;
        if (!empty($tmparr)) {
            $level = $tmparr['level'];
        }
        $level = $level + 1;

        if (IS_POST) {
            if (!$_POST['city_id']) {
                $this->error('请选择城市');
            }
            if ($_POST['min_sale_num']< 0) {
                $this->error('请填写正确最低销售数量');
            }
            if (!$_POST['max_sale_num'] || $_POST['max_sale_num']<= 0) {
                $this->error('请填写正确最高销售数量');
            }
            if ($_POST['min_sale_num'] > $_POST['max_sale_num']) {
                $this->error('最高销售数量不能小于最低销售数量');
            }
            $condition['_string'] = '(min_sale_num<='.$_POST['min_sale_num'].' AND max_sale_num>='.$_POST['min_sale_num'].') OR (min_sale_num<='.$_POST['max_sale_num'].' AND max_sale_num>='.$_POST['max_sale_num'].')';
            $tmparr = $levelDb->where($condition)->order('level DESC')->find();
            if ($tmparr && (!$_POST['lid'] || $_POST['lid'] != $tmparr['id'])) {
                if ($tmparr['lname']) {
                    $this->error("销售区间范围请勿与等级 {$tmparr['lname']} 重合");
                }
                $this->error('销售区间范围请勿重合');
            }

            if (!$_POST['leader_money']) {
                $this->error('请填写正确佣金比率');
            }
            if ($_POST['lid']) {
                $lid = intval($_POST['lid']);
                $tmpdata = $levelDb->where(array('id' => $lid))->find();
                if (empty($tmpdata)) {
                    $this->error('当前修改信息不存在或者已经被删除');
                }
                $_POST['last_time'] = time();
                $save_tmpdata = $levelDb->where(array('id' => $lid))->data($_POST)->save();
                if ($save_tmpdata) {
                    $this->success('编辑成功');
                } else {
                    $this->error('编辑失败');
                }
            } else {
                $_POST['level'] = $level;
                $_POST['add_time'] = time();
                $add_tmpdata = $levelDb->data($_POST)->add();
                if ($add_tmpdata) {
                    $this->success('添加成功');
                } else {
                    $this->error('添加失败');
                }
            }
        } else {
            $lid = intval($_GET['lid']);
            $tmpdata = $levelDb->where(array('id' => $lid))->find();
            if (empty($tmpdata)) {
                $tmpdata = array('id' => 0, 'level' => $level, 'lname' => '', 'integral' => '', 'icon' => '', 'boon' => '', 'type' => 0, 'description' => '', 'city_id' => $_GET['city_id']);
            }
            $this->assign('leveldata', $tmpdata);
            $this->display();
        }
    }

    // 删除等级
    public function del_leader_area_level() {
        $levelDb = M('Village_group_leader_area_level');
        $lid = intval($_POST['lid']);
        $tmpdata = $levelDb->where(array('id' => $lid))->find();
        if (empty($tmpdata)) {
            $this->error('当前修改信息不存在或者已经被删除');
        }
        if($levelDb->where(array('id' => $lid))->delete()){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！请重试~');
        }
    }


    // 用户延期
    public function village_group_delay(){
        $detail_id = $_POST['id'] > 0 ? intval($_POST['id']) : 0;
        if (!$detail_id) {
            $this->error('缺少必要参数');
        }
        if (!$this->system_session['id']) {
            $this->error('请先登录');
        }
        // 延期状态修改
        $detail = M('Village_group_order_detail')->where(array('id'=>$detail_id))->find();
        if (!$detail) {
            $this->error('需要延期的信息不存在或者已经被删除');
        }
        if ($detail['status']>0 && $detail['status']!=6) {
            $this->error('该订单已经发货，无法延期');
        }
        if ($detail['is_delay'] || $detail['status']==6) {
            $this->error('该次配送已经延期');
        }
        $order = M('Village_group_order')->where(array('order_id' => $detail['order_id']))->find();
        $delay_user = 2;
        // 查询下用户自身延期次数 是否受限
        $delay_num = M('Village_group_order_detail')->where(array('order_id' => $detail['order_id'],'is_delay'=>1,'delay_user'=>0))->count();
        if ($delay_user==0 && $order['is_limit_delay']==1 && $delay_num>=$delay_num['limit_delay_num']){
            $this->error('你延期次数已受限制，当前不可再延期！');
        }

        // 计算从当前天数计算备货日期节点时间
        $limit_days = intval($order['limit_days']);
        $limit_time = $order['limit_time']?$order['limit_time']:'18:00';
        $now_time = strtotime(date('Y-m-d '.$limit_time));
        if (time()>$now_time) {
            $limit_days += 1;
        }
        $now_time_day = strtotime(date('Y-m-d '));
        $now_day = strtotime("+".$limit_days." day", $now_time_day);
        $send_time = $detail['send_time'];
        if ($now_day>$send_time){
            $this->error('当前商家已经开始准备备货，无法进行延期操作！');
        }
        $save = array(
            'is_delay'=>1,
            'status'=>6,
            'delay_user'=>$delay_user,
            'delay_time'=>time()
        );
        if (M('Village_group_order_detail')->where(array('id'=>$detail_id))->data($save)->save()) {
            // 查询周期购最后一条
            $cycle_info = M('Village_group_order_detail')->where(array('order_id' => $detail['order_id']))->order('date_num DESC, send_time DESC')->find();
            if ($detail['date_num']>=$order['delivery_periods'] || M('Village_group_order_detail')->where(array('order_id' => $detail['order_id'],'is_delay'=>0,'status'=>0,'date_num'=>array('gt',$detail['date_num'])))->setDec('date_num')) {
                // 延期后的期数所有统一减一期  再在最后一期后面再生成一期
                $create_data = $detail;
                unset($create_data['id']);
                $d_village_group=D('Village_group');
                $create_data['send_time'] = $d_village_group->order_lately_cycle_time($order,$detail['goods_id'],$order['delivery_date'],true,1,$cycle_info['send_time'],$cycle_info['send_time']);
                $create_data['is_delay']=0;
                $create_data['status']=0;
                $create_data['village_group_type']=1;
                $create_data['date_num']=$order['delivery_periods'];
                $info = M('Village_group_order_detail')->add($create_data);
                if ($info) {
                    $this->success('延期成功！');
                } else {
                    $del_save = array(
                        'is_delay'=>0,
                        'status'=>$detail['status'],
                        'delay_user'=>0,
                        'delay_time'=>0
                    );
                    M('Village_group_order_detail')->where(array('id'=>$detail_id))->data($del_save)->save();
                    $this->error('操作失败，请重试');
                }
            } else {
                $del_save = array(
                    'is_delay'=>0,
                    'status'=>$detail['status'],
                    'delay_user'=>0,
                    'delay_time'=>0
                );
                M('Village_group_order_detail')->where(array('id'=>$detail_id))->data($del_save)->save();
                M('Village_group_order_detail')->where(array('order_id' => $detail['order_id'],'is_delay'=>0,'status'=>0,'date_num'=>array('gt',$detail['date_num'])))->setInc('date_num');

                $this->error('操作失败，请重试');
            }
        } else {
            $this->error('操作失败，请重试');
        }
    }



    // 配送列表发货-只能同一订单统一发货(只查询配送相关订单)
    public function delivery_order_list(){
        if($_GET['status']>-1){
            switch($_GET['status']){
                case 1:
                    $where['_string'] = '(o.status>0 AND o.status<10 )';
                    break;
                case 0:
                    $where['o.status'] = 0;
                    break;
                case 11:
                    $where['_string'] = '(o.status=11 or o.status=12)';
                    break;
                default:
                    $where['o.status'] = $_GET['status'];
                    break;
            }
        }

        if($_GET['deliver_status']>-1 && ($_GET['status']==1 || $_GET['status']==-1)){
            $where['o.status'] = $_GET['deliver_status'];
        }

        $get_header_uid = false;
        $start_uid = 0;
        if($_GET['keyword']){

            switch($_GET['searchtype']){
                case 'user_phone':
                    $user = D('User')->get_user($_GET['keyword'],'phone');

                    $where['o.uid'] = $user['uid'];
                    break;
                case 'user_id':
                    $where['o.uid'] = $_GET['keyword'];
                    break;
                case 'header_phone':
                    $header = M('Village_group_header')->where(array('phone'=>$_GET['keyword']))->find();
                    $start_uid = $header['id'];
                    $get_header_uid = true;
                    break;
                case 'header_uid':
                    $header = M('Village_group_header')->where(array('id'=>$_GET['keyword']))->find();
                    $start_uid = $header['id'];
                    $get_header_uid = true;
                    break;
                case 'header_name':
                    $header = M('Village_group_header')->where(array('name'=>$_GET['keyword']))->find();
                    $start_uid = $header['id'];
                    $get_header_uid = true;
                    break;
                case 'order_id':
                    $where['o.order_id'] = $_GET['keyword'];
                    break;
            }
        }
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
        }
        // 处理省市区
        if ($_GET['province_idss'] || $_GET['city_idss'] || $_GET['area_id']) {
            $where_header = array(
                'header_uid' => array('gt',0)
            );
            if ($area_index) {
                $where_header[$area_index] = $this->system_session['area_id'];
                if ($area_index=='province_id' && $_GET['city_idss']) {
                    $where_header['city_id'] = $_GET['city_idss'];
                }
                if (($area_index=='province_id' || $area_index=='city_id') && $_GET['area_id']) {
                    $where_header['area_id'] = $_GET['area_id'];
                }
            } else {
                if ($_GET['province_idss']) {
                    $where_header['province_id'] = $_GET['province_idss'];
                }
                if ($_GET['city_idss']) {
                    $where_header['city_id'] = $_GET['city_idss'];
                }
                if ($_GET['area_id']) {
                    $where_header['area_id'] = $_GET['area_id'];
                }
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[]=$val['header_uid'];
            }
            if ($start_uid>0) {
                $header_arr[]=$start_uid;
            }
            $where['start_uid'] = array('in', $header_arr);
        }elseif ($get_header_uid) {
            $where['start_uid'] = $start_uid;
        } elseif ($area_index) {
            $where_header = array(
                'header_uid' => array('gt',0)
            );
            $where_header[$area_index] = $this->system_session['area_id'];
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[]=$val['header_uid'];
            }
            if ($start_uid>0) {
                $header_arr[]=$start_uid;
            }
            $where['start_uid'] = array('in', $header_arr);
        }

        $pay_type = $_GET['pay_type'];
        if($pay_type){
            if($pay_type=='balance'){
                $where['system_balance']  = array('gt',0);
            }else{
                $where['p.pay_type'] =$pay_type;
            }
        }
        $where['o.village_group_type'] = 2;

        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            $begin_time = strtotime($_GET['begin_time']." 00:00:00");
            $end_time = strtotime($_GET['end_time']." 23:59:59");
            if ($begin_time>$end_time) {
                $this->error("结束时间应大于开始时间");
            }

            $period = array($begin_time,$end_time);
            $where['_string']= $where['_string']?$where['_string']." AND (o.add_time BETWEEN ".$period[0].' AND '.$period[1].")":" (o.add_time BETWEEN ".$period[0].' AND '.$period[1].")";
        }
        $count = M('Village_group_order')->join('as o left join '.C('DB_PREFIX').'plat_order p ON p.business_id = o.order_id AND p.business_type="village_group"')->where($where)->count();


        import('@.ORG.system_page');
        $p = new Page($count,20);

        $start_list = M('Village_group_order')->field('o.*,u.phone,u.nickname,p.system_balance')->join('as o left join '.C('DB_PREFIX').'plat_order p ON p.business_id = o.order_id AND p.business_type="village_group" left join '.C('DB_PREFIX').'user AS u ON u.uid=o.uid')->where($where)->order('o.order_id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        if ($start_list) {
            // 单独查询一下 团长信息
            $m_village_group_header = M('Village_group_header');
            $m_village_group_order_detail = M('Village_group_order_detail');
            foreach ($start_list as &$val) {
                $val['header_info'] = $m_village_group_header->where(array('id' => $val['start_uid']))->field('name, phone')->find();
                // 查询一下团购购买数量
                $val['num'] = $m_village_group_order_detail->where(array('order_id' => $val['order_id'],'is_delay' => 0))->sum('num');
            }
        }

        $pay_method = D('Config')->get_pay_method('','',1);
        $this->assign('pay_method',$pay_method);
        $this->assign('order_status',D('Village_group')->order_status);
        $this->assign('start_list',$start_list);
        $this->assign('pagebar',$p->show());
        $this->display();
    }


    //配送订单只能整体发货-改变订单状态
    public function delivery_send(){
        $where['order_id'] = $_GET['order_id'];
        $order_info = M('Village_group_order')->where($where)->find();
        if (!$order_info) {
            $this->error('订单信息错误！');
        }
        if ($order_info['village_group_type']!=2) {
            $this->error('改定单不是配送订单无法此处发货');
        }
        if ($order_info['status']!=1) {
            $this->error('当前订单状态不能发货！');
        }

        if($where){
            if(M('Village_group_order_detail')->where($where)->data(array('status'=>1,'real_send_time' => time()))->save()) {
                $detail_info = M('Village_group_order_detail')->where($where)->select();
                M('Village_group_order')->where($where)->setField('status', 5);

                foreach ($detail_info as $value) {
                    $param['goods_id'] = $value['goods_id'];
                    $param['from_type'] = 1;
                    $param['deliver_status'] = 1;
                    $param['note'] = '系统管理员点击商品发货';
                    $param['name'] = $this->system_session['account'];
                    $param['order_id'] = $value['order_id'];
                    $param['date_num'] = 1;
                    D('Village_group')->add_log($param);
                    unset($param['deliver_status'],$param['status']);
                    $param['status'] = 5;
                    D('Village_group')->add_log($param);
                }
                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'village_group';
                $params['order_id'] = $_GET['order_id'];
                $params['title'] = '商品已发货';
                $params['content'] = '您的商品已经发货，商品将尽快为你送达';
                invoke_v20_service($url, [$params]);

                $this->success('商品发货状态改变成功');

            }else{
                $this->error('商品发货状态改变失败');
            }
        }

    }

    //配送订单只能整体收货-改变订单状态
    public function delivery_get_order(){
        $where['order_id'] = $_GET['order_id'];
        $order_info = M('Village_group_order')->where($where)->find();
        if (!$order_info) {
            $this->error('订单信息错误！');
        }
        if ($order_info['village_group_type']!=2) {
            $this->error('改定单不是配送订单无法此处收货');
        }
        if ($order_info['status']!=5) {
            $this->error('当前订单状态不能收货！');
        }
        if($where){ // 整个订单中所有商品团长收货
            if(M('Village_group_order_detail')->where($where)->data(array('status'=>2,'header_arrival_time' => time()))->save()) {
                $detail_info = M('Village_group_order_detail')->where($where)->select();
                M('Village_group_order')->where($where)->data(array('status'=>6,'header_arrival_time' => time()))->save();

                foreach ($detail_info as $value) {
                    $param['goods_id'] = $value['goods_id'];
                    $param['from_type'] = 1;
                    $param['deliver_status'] = 2;
                    $param['note'] = '系统管理员点击商品收货';
                    $param['name'] = $this->system_session['account'];

                    $param['order_id'] = $value['order_id'];
                    D('Village_group')->add_log($param);
                    D("System_order")->save_order_status('village_group', $value['order_id'], 1);
                    unset($param['deliver_status'],$param['status']);
                    $param['status'] = 6;
                    D('Village_group')->add_log($param);
                }
                D('Deliver_supply')->saveOrder($_GET['order_id'], null,8,'village_group_order');
                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'village_group';
                $params['order_id'] = $_GET['order_id'];
                $params['title'] = '商品已经到达自提点';
                $params['content'] = '您的商品已经到达团长自提点，请前往团长处领取商品。';
                invoke_v20_service($url, [$params]);

                $this->success('商品收货状态改变成功');

            }else{
                $this->error('商品收货状态改变失败');
            }
        }

    }

    //交易汇总
    public function trade(){

        $pay_method = D('Config')->get_pay_method();
        $this->assign('pay_method',$pay_method);
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $this->assign('now_area',$now_area);
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
                $_GET['area_id']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
                $_GET['city_idss']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
                $_GET['province_idss'] =$now_area['area_id'];
            }
        }
        if(!isset($_GET['selectTimeType']) && !isset($_GET['begin_time'])  && !isset($_GET['end_time'])){
            $_GET['selectTimeType'] = 1;
        }
        $res = D('Village_group_order')->sell_order_date(1,$_GET);
        $this->assign($res);
        $this->display();
    }

    public function trade_export(){
    	$param = $_POST;
		$param['type'] = 'village_group_trade';
		$param['rand_number'] = $_SERVER['REQUEST_TIME'];
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $param['area_id']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $param['city_idss']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $param['province_idss'] =$now_area['area_id'];
            }
        }
		$param['area_id'] = $this->system_session['area_id'];
        if(!isset($_POST['selectTimeType']) && !isset($_POST['begin_time'])  && !isset($_POST['end_time'])){
            $param['selectTimeType'] = 1;
        }

		if ($res = D('Order')->order_export($param)) {
			echo json_encode(array('error_code' => 0, 'msg' => '操作导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
		} else {
			echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
		}
    }

    public function searchMerchant(){
    	$keywords = trim(I("term"));
    	$condition_merchant['name'] = array('like','%'.$keywords.'%');
    	$condition_merchant['status'] = array("in", [1,3]);
    	$data = D('Merchant')->field("mer_id as id,name as value")->where($condition_merchant)->select();
    	echo json_encode($data);
    	exit;
    }

    //社区拼团活动列表页
    public function activity(){
    	$database = D('Village_group_activity');
    	$where = [];
    	$type = I("type");
    	if($type){
    		$where['type'] = $type;
    	}
        $area_id = $this->system_session['area_id'];
    	switch ($this->system_session['area_type']) {
            case 1 : //省级
                $where['province_id'] = $area_id;
                break;
            case 2 : //省级
                $where['city_id'] = $area_id;
                break;
            case 3 : //省级
                $where['area_id'] = $area_id;
                break;
            default:;
        }
    	$provice_id = I('province_idss');
    	if($provice_id) {
    	    $where['province_id'] = $provice_id;;
        }
        $city_id = I('city_idss');
        if($city_id) {
            $where['city_id'] = $city_id;
        }
        $area_id = I('area_id');
        if($area_id) {
            $where['area_id'] = $area_id;
        }
    	$this->assign("type", $type);
    	$keyword = trim(I("keyword"));
    	if($keyword){
    		$where['activity_name'] = ['like', '%'.$keyword.'%'];
    	}
    	$this->assign("keyword", $keyword);
    	$time_type = I("time_type");
    	$this->assign("time_type", $time_type);
    	$start_time = I("start_time");
    	$this->assign("start_time", $start_time);
    	$end_time = I("end_time");
    	$this->assign("end_time", $end_time);
    	$field = "";
    	if($time_type == '1'){
    		//以开始时间搜索
    		$field = 'start_time';
    	}else{
    		//以结束时间搜索
    		$field = 'end_time';
    	}
    	if($start_time && $end_time){
    		$where[$field] = ['between', [strtotime($start_time), strtotime($end_time)]];
    	}elseif($start_time){
    		$where[$field] = ['egt', strtotime($start_time)];
    	}elseif($end_time){
    		$where[$field] = ['elt', strtotime($end_time)];
    	}
    	
    	import('@.ORG.system_page');
    	$count = $database->where($where)->count();
		$p = new Page($count,20);
		$data = $database->where($where)->order('create_time DESC')->limit($p->firstRow.','.$p->listRows)->select();
		if($data){
			foreach ($data as $key => $value) {
				if(time() < $value['start_time']){//未开始
					$data[$key]['status_txt'] = $value['status'] == '1' ? '未开始' : '关闭';
					$data[$key]['font_color'] = $value['status'] == '1' ? '#bbb' : 'red';
				}
				elseif(time() < $value['end_time']){
					$data[$key]['status_txt'] = $value['status'] == '1' ? '进行中' : '关闭';
					$data[$key]['font_color'] = $value['status'] == '1' ? 'green' : 'red';
				}
				else{
					$data[$key]['status_txt'] = $value['status'] == '1' ? '已结束' : '关闭';
					$data[$key]['font_color'] = $value['status'] == '1' ? 'red' : 'red';	
				}
				if($value['type'] == '1'){
					$data[$key]['type_txt'] = '每日必买';
				}elseif($value['type'] == '2'){
					$data[$key]['type_txt'] = '限时抢购';
				}
				$data[$key]['start_datetime'] = date('Y-m-d H:i', $value['start_time']);
				$data[$key]['end_datetime'] = date('Y-m-d H:i', $value['end_time']);

                $res_area = [];
				if($value['area_id']) {
				    $res_area = M('Area')->field('area_name, area_ip_desc')->where(['area_id'=>$value['area_id']])->find();
                } elseif($value['city_id']) {
				    $res_area = M('Area')->field('area_name, area_ip_desc')->where(['city_id'=>$value['area_id']])->find();
                } elseif($value['province_id']) {
                    $res_area = M('Area')->field('area_name, area_ip_desc')->where(['province_id'=>$value['area_id']])->find();
                }

                if(!empty($res_area['area_ip_desc'])) {
                    $data[$key]['area_name'] = $res_area['area_ip_desc'];
                } elseif(!empty($res_area['area_name'])) {
                    $data[$key]['area_name'] = $res_area['area_name'];
                } else {
                    $data[$key]['area_name'] = '';
                }
			}
		}

		$pagebar = $p->show();

		$this->assign('data', $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
    }

    public function add_activity(){
    	$activity_id = I("activity_id");
    	$db = M("Village_group_activity");

    	if(IS_POST){
    		$data = [
    			'activity_name' => trim(I('activity_name')),
                'province_id' => intval(I('province_id')),
                'city_id' => intval(I('city_id')),
                'area_id' => intval(I('area_id')),
    			'status' => I('status'),
    			'is_top' => I('is_top'),
    			'sub_name' => trim(I('sub_name')),
    			'img' => I('img'),
    			'share_img' => I('share_img'),
    			'share_title' => trim(I('share_title')),
    			'create_time' => date('Y-m-d H:i:s')
    		];
    		$start_time = I('start_time');
    		$end_time = I('end_time');
    		$type = I("type");
    		if($start_time && $end_time){
    			$start_time = strtotime($start_time);
    			$end_time = strtotime($end_time);
    			if($start_time >= $end_time){
    				$this->error("结束时间不能小于开始时间！");
    			}
    			if($start_time < time() && $data['status'] == 1){
    				$this->error("开始时间不能小于当前时间！");
    			}
    			if(date('Y-m-d',$start_time) != date('Y-m-d',$end_time)){
    				$this->error("时间跨度必须为同一天！");	
    			}
    			$data['start_time'] = $start_time;
    			$data['end_time'] = $end_time;
    		}
    		if($type){
    			$data['type'] = $type;
    		}
    		if($data['is_top'] == 1){
    			$db->where(['is_top'=>1, 'type'=>1])->data(['is_top'=>0])->save();
    		}
    		if($data['type'] == 2){
	    		//添加限购活动时， 活动之间时间不能有重叠；
	    		$_where = [];
	    		$_where['type'] = 2;
	    		$_where['_string'] = '(start_time <= '.$data['start_time'].' and end_time >= '.$data['start_time'].') or ( start_time <='.$data['end_time'].' and end_time >= '.$data['end_time'].')';
	    		if($activity_id){
	    			$_where['activity_id'] = ['neq', $activity_id];	
	    		}
	    		if($db->where($_where)->find()){
	    			$this->error("当前时间区间内有重复的限时抢购活动，请核对后再操作！");
	    		}
	    	}

    		if(empty($activity_id)){
	    		if($activity_id = $db->data($data)->add()){
	    			D("System_log")->add_log("增加了".$this->config['village_group_alias']."活动，活动名称：".$_POST['activity_name'],$this->config['village_group_alias'],$activity_id);
	    			$this->success("添加成功！");
	    		}
	    		else{
	    			$this->error("添加失败！");
	    		}
	    	}
	    	else{
	    		unset($data['create_time']);
	    		$before = $db->where(['activity_id'=>$activity_id])->find();
	    		if($db->data($data)->where(['activity_id'=>$activity_id])->save()){
	    			$after = $db->where(['activity_id'=>$activity_id])->find();
	    			D("System_log")->add_log("编辑了".$this->config['village_group_alias']."活动，活动名称：".$_POST['activity_name'],$this->config['village_group_alias'],$activity_id, $before, $after);
	    			$this->success("编辑成功！");
	    		}
	    		else{
	    			$this->error("编辑失败！");
	    		}
	    	}
    	}
    	$edit_this = true;
    	if($activity_id){
    		$detail = $db->where(['activity_id'=>$activity_id])->find();
    		if($detail['start_time'] < time() && $detail['status'] == 1){
    			$edit_this = false;
    		}
    		$detail['start_time'] = date('Y-m-d H:i', $detail['start_time']);
    		$detail['end_time'] = date('Y-m-d H:i', $detail['end_time']);
    		$this->assign('detail', $detail);
    	}
    	$this->assign("edit_this", $edit_this);
    	$this->display();
    }

    //活动商品列表
    public function activity_goods(){
    	$activity_id = I("activity_id");
    	if(empty($activity_id)){
    		$this->error("非法操作！");
    	}
    	$db = M("Village_group_activity_goods");
    	$activity_db = M("Village_group_activity");
    	$activity_info = $activity_db->where(['activity_id'=>$activity_id])->find();
    	if(empty($activity_info)){
    		$this->error("没有找到当前活动！");
    	}
    	$keyword = trim(I("keyword"));
    	$where = [
    		'a.activity_id' => $activity_id,
    		'a.is_del' => 0
    	];
    	if($keyword){
    		$where['b.name'] = ['like', '%'.$keyword.'%'];
    		$this->assign("keyword", $keyword);
    	}
    	$data = $db->alias('a')->join("left join ".C("DB_PREFIX")."village_group_goods as b on a.goods_id=b.goods_id")->field("a.id,a.buy_limit,a.buy_price,a.before_activity,a.after_activity,b.*")->order("a.addtime desc")->where($where)->select();

    	$this->assign("activity_id", $activity_id);
    	$this->assign("activity_info", $activity_info);
    	$this->assign("data", $data);
    	$this->display();
    }

    //选择添加活动商品
    public function add_activity_goods(){
    	$activity_id = I("activity_id");
    	$input_goods_id = I("goods_id");
    	$goods_id = [];
    	if($input_goods_id){
    		$goods_id = explode(',', $input_goods_id);
    	}elseif($activity_id){
    		$db = M("Village_group_activity_goods");
    		$has = $db->where(['activity_id' => $activity_id, 'is_del' => 0])->field("goods_id")->select();
    		$goods_id = array_column($has, 'goods_id');
    	}
    	$database = D('Village_group_goods');
		$condition['status'] = array('neq','4');

		if($_GET['searchstatus']>0){
			switch($_GET['searchstatus']){
				case '1':
					$condition['_string'] = 'status=1 AND start_time<'.time();
					break;
				case '2': //已售罄
					$condition['_string'] = 'status=2 or stock_num<=sell_count';
					break;
				case '3': //还未开始
					$condition['_string'] = 'status=1 AND start_time>'.time();
					break;

				case '4': //已过期
					$condition['_string'] = 'status=3 or (end_time <> 0 && end_time<'.time().')';
					break;
			}

		}else{
			$condition['_string'] = 'status>0';
		}

		if($_GET['keyword']!=''){
			$condition['name'] = array("like","%".$_GET['keyword']."%");
		}
		import('@.ORG.system_page');
    	$count = $database->where($condition)->count();
		$p = new Page($count,6);
		$data = $database->field(true)->where($condition)->order('goods_id DESC,status ASC')->limit($p->firstRow.','.$p->listRows)->select();
		foreach($data as $key => $val){
			$data[$key]['checked'] = in_array($val['goods_id'], $goods_id) ? 1 : 0;
		}

		$this->assign("activity_id", $activity_id);
		$this->assign("goods_id", implode(',', $goods_id));
		$this->assign("data", $data);
		$this->assign("pagebar", $p->show());
    	$this->display();
    }

    //添加活动商品处理
    public function add_activity_goods_submit(){
    	$goods_id = explode(',',I("goods_id"));
    	$activity_id = I("activity_id");
    	if(empty($activity_id)){
    		$this->error("非法操作");
    	}
    	$activity_db = M("Village_group_activity");
    	$activity_info = $activity_db->where(['activity_id'=>$activity_id])->find();
    	if(empty($activity_info)){
    		$this->error("没有找到当前活动！");
    	}
    	$count = 0;
    	$db = M("Village_group_activity_goods");
    	$has = $db->where(['activity_id' => $activity_id, 'is_del' => 0])->field("id,goods_id")->select();
    	foreach ($has as $key => $value) {
    		if(!in_array($value['goods_id'], $goods_id)){
    			if($db->where(['id'=>$value['id']])->data(['is_del'=>1])->save()){
    				$count++;
    			}
    		}
    	}
    	$old = array_column($has, 'goods_id');
    	$goods_arr = D("Village_group_goods")->where(['goods_id'=>['in',$goods_id]])->field('goods_id,price')->select();
    	$goods_arr = array_column($goods_arr, 'price', 'goods_id');
    	foreach ($goods_id as $value) {
    		if(!in_array($value, $old)){
    			$data = [
    				'goods_id' => $value,
    				'activity_id' => $activity_id,
    				'buy_limit' => 0,//默认是0
    				'buy_price' => $activity_info['type'] == '2' ? ($goods_arr[$value] ? $goods_arr[$value] : 0) : 0 ,
    				'before_activity' => $activity_info['type'] == '2' ? 1 : 0,
    				'after_activity' => $activity_info['type'] == '2' ? 1 : 0,
    				'addtime' => date('Y-m-d H:i:s'),
    				'is_del' => 0
    			];
    			if($db->data($data)->add()){
    				$count++;
    			}
    		}
    	}
    	if($count > 0){
    		$this->success("编辑成功！");
    	}
    	else{
    		$this->error("编辑失败！");	
    	}
    }

    //删除活动中的商品
    public function del_activity_goods(){
    	$ids = I("ids");
    	if(empty($ids)) $this->error("非法操作！");
    	$db = M("Village_group_activity_goods");
    	if($db->where(['id'=>['in',$ids]])->data(['is_del'=>1])->save()){
    		$this->success("删除成功！");
    	}
    	else{
    		$this->error("删除失败！");	
    	}
    }

    //编辑活动中商品的信息
    public function update_activity_goods_info(){
    	$id = I("id");
    	$val = I("val");
    	$field = I("field");
    	if(empty($id)) $this->error("非法操作！");
    	$db = M("Village_group_activity_goods");
    	if($db->where(['id'=>['eq',$id]])->data([$field=>$val])->save()){
    		$this->success("修改成功！");
    	}
    	else{
    		$this->error("修改失败！");	
    	}
    }

    //更换订单团长
    public function change_leader(){
    	$order_id = I("order_id");
    	$start_uid = I("start_uid");
    	$order = M("Village_group_order")->where(['order_id'=>$order_id])->find();
    	if(IS_POST){
	    	if($order['status'] != '1'){
	    		$this->error("当前订单不是待发货状态不能修改团长！");	
	    	}
	    	if($order['start_uid'] == $start_uid){
	    		$this->success("无变动！");	
	    	}
	    	$data = [
	    		'start_uid' => $start_uid
	    	];
	    	$address = M('Village_group_pick_address')->where(array('header_uid'=>$start_uid))->find();
            $data['pick_addr'] = $address['pick_addr'] ? : "";
            $data['pick_province_id'] = $address['province_id'] ? : 0;
            $data['pick_city_id'] = $address['city_id'] ? : 0;
            $data['pick_area_id'] = $address['area_id'] ? : 0;
            $data['pick_lat'] = $address['lat'] ? : 0;
            $data['pick_long'] = $address['long'] ? : 0;
	    	if(M("Village_group_order")->where(['order_id'=>$order_id])->data($data)->save()){
	    		D("System_log")->add_log("更换了".$this->config['village_group_alias']."订单团长，订单ID：".$_POST['order_id'],$this->config['village_group_alias'],$_POST['order_id'], $order, M("Village_group_order")->where(['order_id'=>$order_id])->find());
	    		$this->success("变更成功！");	
	    	}
	    	else{
	    		$this->error("变更失败！");		
	    	}
    	}
    	if($start_uid){
    		$order['start_uid'] = $start_uid;
    	}

    	$database = D('Village_group_header');
		$condition_group_category['h.status'] = array('eq','1');
		
		if($_GET['keyword']){
			switch($_GET['searchtype']){
				case 'nickname':
					$condition_group_category['h.name'] = $_GET['keyword'];
					break;
				case 'phone':
					$condition_group_category['h.phone'] = $_GET['keyword'];
					break;
				case 'hid':
					$condition_group_category['h.id'] = $_GET['keyword'];
					break;
				case 'address':
					$condition_group_category['a.pick_addr'] = ['like','%'.$_GET['keyword'].'%'];
					break;
			}
		}
		
		$count_group_category =  $database->join('as h LEFT JOIN '.C('DB_PREFIX').'village_group_pick_address a ON h.id=a.header_uid')->where($condition_group_category)->count();

		import('@.ORG.system_page');
		$p = new Page($count_group_category,20);
		$header = $database->field('h.* ,a.pick_addr,a.name as pick_name')->join('as h LEFT JOIN '.C('DB_PREFIX').'village_group_pick_address a ON h.id=a.header_uid')->where($condition_group_category)->order('id DESC')->limit($p->firstRow.','.$p->listRows)->select();

		foreach ($header as &$value) {
            $value['sale_num'] = D('Village_group_order')->sum_header_sale_num($value['id']);
		}

		$this->assign('header',$header);
		$pagebar = $p->show();
		$this->assign('pagebar',$pagebar);

    	$this->assign("order", $order);
    	$this->display();
    }

    //修改未发货订单的团长
    public function change_leader_submit(){
    	
    }
}



