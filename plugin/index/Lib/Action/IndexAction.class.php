<?php
class IndexAction extends BaseAction{
    public function index(){
        $loginUser = [];
		$returnArr = build_redirect_info();
		
		$condition_plugin = [];
		switch($returnArr['from']){
			case 'merchant':
				$condition_plugin['p.plugin_merchant'] = 1;
                $loginUser = session('merchant');
				break;
			case 'system':
				$condition_plugin['p.plugin_system'] = 1;
                $loginUser = session('system');
				break;
			case 'house':
				$condition_plugin['p.plugin_house'] = 1;
				break;
		}

		$condition_plugin['p.status'] = 1;
		$condition_plugin['p.show'] = 1;
		
		//插件列表
		// $plugin_list = S('plugin_list_'.$_GET['from']);
		if(empty($plugin_list)){
			$plugin_cat = M('Plugin_cat')->order('`cat_sort` DESC,`cat_id` ASC')->select();
			foreach($plugin_cat as $value){
				$plugin_list[$value['cat_id']] = $value;
				$plugin_list[$value['cat_id']]['plugin_list'] = [];
			}
			if($returnArr['from']=='system'){
				$plugin = M('Plugin')->field('p.*,s.component,s.path,s.id as sid')->where($condition_plugin)->join(' as p left join '.C('DB_PREFIX').'system_menu as s on p.system_menu_id = s.id')->order('p.`plugin_sort` DESC,p.`plugin_id` ASC')->select();
			}else{
				$plugin = M('Plugin')->field('p.*,s.component,s.path,s.id as sid')->where($condition_plugin)->join(' as p left join '.C('DB_PREFIX').'new_merchant_menu as s on p.system_menu_id = s.id')->order('p.`plugin_sort` DESC,p.`plugin_id` ASC')->select();
			}

			$meun_nodes = explode(',',$loginUser['menus']);

			//可能会存在商家子商户登录，需要重置权限节点
			$subaccountId = $loginUser['merchant_extends']['mer_subaccount_id'] ?? 0;
			if ($subaccountId && $returnArr['from'] == 'merchant') {
				$sub_menus = M('Merchant_user_account')->alias('a')
					->join(C('DB_PREFIX') . 'merchant_user_stations s ON a.station_id = s.id')
					->where(array('a.id' => $subaccountId, 'a.status' => 1, 'a.is_del' => 0, 's.status' => 1))
					->getfield('menus');
				if (empty($sub_menus)) {
					$meun_nodes = [];
				} else {
					$meun_nodes = array_intersect($meun_nodes, explode(',', $sub_menus));
				}
			}

			foreach($plugin as $value){
				if(!empty($value)){
					$value['plugin_ico_arr'] = array_flip(explode(' ',$value['plugin_ico']));
				}

				//客服单独判断，没权限不给展示
				if ($returnArr['from'] == 'system' && $loginUser['level'] <> 2 && $value['plugin_label'] == 'im' && !in_array(945, $meun_nodes)) {
					continue;
				}else if ($returnArr['from'] == 'merchant' && $value['plugin_label'] == 'im' && !in_array(10596, $meun_nodes)) {
					continue;
				}

				if($value['system_menu_id']){
					// 营销活动 权限
					if($returnArr['from']=='system' && !C('config.wxapp_url') && $value['system_menu_id']==96){
                        continue;
					}

					if(!$value['sid']){
					    // 没有此菜单
                        continue;
                    }
					
					if($returnArr['from']=='system'){
							//没权限的不展示
			            if($loginUser['level']==2 || in_array($value['system_menu_id'],$meun_nodes)){
			                $value['url'] = $this->getMenuPath($value);
			                $value['new_blank'] = 1;
			            }else{
			                continue;
			            }
					}else{
						//没权限的不展示
		                if(in_array($value['system_menu_id'],$meun_nodes)){
		                    $value['url'] = $this->getMenuPath($value);
			                $value['new_tab'] = 1;
		                }else{
		                    continue;
		                }
					}
                    //自定义装修的使用href跳转
                    if (isset($value['url']) && $value['url'] && stripos($value['url'], '/common/platform.custom/index') !== false) {
                        $value['new_blank'] = 1;
                        $value['new_tab'] = null;
                        $value['new_page'] = 1;
                    }
					
				}else{
					$value['url'] = U('detail', array('plugin_id'=>$value['plugin_id'],'from'=>$_GET['from']));
				}
				
                if( strpos($value['plugin_name'], '订餐')!==false){
                    $value['plugin_name'] =  str_replace('订餐',C('config.meal_alias_name'),$value['plugin_name']);
                }else{
                    $value['plugin_name'] =  str_replace('餐饮',C('config.meal_alias_name'),$value['plugin_name']);
                }
                $value['plugin_name'] =  str_replace('快店', C('config.shop_alias_name') ?? '快店', $value['plugin_name']);
                $value['plugin_name'] =  str_replace('外卖', C('config.shop_alias_name') ?? '快店', $value['plugin_name']);
                if(strpos($value['plugin_name'], '社区团购')!==false){
                    $value['plugin_name'] = str_replace('社区团购', C('config.village_group_alias') ?? '社区团购', $value['plugin_name']);
                }elseif (strpos($value['plugin_name'], '团购')!==false){
                    $value['plugin_name'] = str_replace('团购', C('config.group_alias_name') ?? '团购', $value['plugin_name']);
                }
                $value['plugin_name'] = str_replace('预约', C('config.appoint_alias_name') ?? '预约', $value['plugin_name']);
                $value['plugin_name'] = str_replace('优惠买单', C('config.cash_alias_name') ?? '优惠买单', $value['plugin_name']);
                

				$plugin_list[$value['cat_id']]['plugin_list'][] = $value;
			}
			$plugin_list = array_values($plugin_list);
			S('plugin_list_'.$_GET['from'], $plugin_list);
		}
		$this->assign('plugin_list',$plugin_list);
		
		$this->display();
    }
	
	public function detail(){
		if(empty($_GET['from'])){
			die('访问形式不正确');
		}
		
		$plugin_id = intval($_GET['plugin_id']);
		if(empty($plugin_id)){
			die('访问形式不正确');
		}
		$now_plugin = M('Plugin')->where(['plugin_id'=>$plugin_id])->find();
		
		$condition_plugin = ['plugin_id'=>$plugin_id];

		$returnArr = build_redirect_info();
		
		//临时添加使用记录
		$now_plugin_list = M('Plugin_list')->where($condition_plugin)->find();
		if(empty($now_plugin_list)){
			$data_plugin = $condition_plugin;
			$data_plugin['end_time'] = 0;
			M('Plugin_list')->data($data_plugin)->add();
		}
		
		redirect($this->config['site_url'] . '/plugin.php?plugin=' . $now_plugin['plugin_label'].'&c=Index&a=redirect&from=' . $_GET['from']);
	}

	 // 组合前端所需字段
    protected function getMenuPath($value){
        $name = 'menu_'.$value['system_menu_id']; // 菜单name，保持唯一
        if($value['plugin_system']){
            //自定义装修的path处理
            if (isset($value['path']) && $value['path'] && $value['path'] == '/common/platform.custom/index') {
                $value['path'] = $value['path'] . '?source=platform';
            }
        	$path = C('config.site_url').'/v20/public/platform/#'.(isset($value['path'])&&$value['path'] ? $value['path'] : '/common/platform.iframe/'.$name); 
        }else{
        	$path = C('config.site_url').'/v20/public/platform/#'.(isset($value['path'])&&$value['path'] ? $value['path'] : '/merchant/merchant.iframe/'.$name); 
        }
        return $path;
    }
}