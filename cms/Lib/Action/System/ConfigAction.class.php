<?php
/*
 * 修改站点配置
 *
 */
class ConfigAction extends BaseAction {

    public function index(){
		$database_config_group = D('Config_group');
		if(empty($_GET['galias'])){
			$condition_config_group['status'] = '1';
		}

		$condition_config_group['gid'] = array('not in',array(5));
		$group_list = $database_config_group->field(true)->where($condition_config_group)->order('`gsort` DESC,`gid` ASC')->select();
		foreach($group_list as &$gListValue){
			$gListValue['gname'] = str_replace('订餐',$this->config['meal_alias_name'],$gListValue['gname']);
            if(strpos($gListValue['gname'], '社区团购')!==false){
                $gListValue['gname'] = str_replace('社区团购', $this->config['village_group_alias'] ?? '社区团购', $gListValue['gname']);
            }elseif(strpos($gListValue['gname'], '社区拼团')!==false){
                $gListValue['gname'] = str_replace('社区拼团', $this->config['village_group_alias'] ?? '社区拼团', $gListValue['gname']);
            }elseif (strpos($gListValue['gname'], '团购')!==false){
                $gListValue['gname'] = str_replace('团购',$this->config['group_alias_name'],$gListValue['gname']);
            }
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
		
		if($this->config['oss_first_loaded'] && $this->config['oss_first_loaded'] == '1'){
			$data_first_loaded = array(
				'type'=>'type=show',
				'value'=>'同步已经完成',
				'info'=>'阿里云OSS同步情况',
				'gid'=>'61',
				'sort'=>'100',
				'tab_id'=>'oss_info',
				'tab_name'=>'阿里云OSS',
			);
			$database_config->where(array('name'=>'oss_first_loaded'))->data($data_first_loaded)->save();
		}
		
		$condition_config['gid'] = $gid;
		$condition_config['status'] = '1';
		$tmp_config_list = $database_config->where($condition_config)->order('`sort` DESC')->select();
		foreach($tmp_config_list as $key=>$value){
			$value['info'] = str_replace('订餐',$this->config['meal_alias_name'],$value['info']);
            if(strpos($value['info'], '社区团购')!==false){
                $value['info'] = str_replace('社区团购', $this->config['village_group_alias'] ?? '社区团购', $value['info']);
            }elseif (strpos($value['info'], '团购')!==false){
                $value['info'] = str_replace('团购',$this->config['group_alias_name'],$value['info']);
            }
			$value['info'] = str_replace('预约',$this->config['appoint_alias_name'],$value['info']);
			$value['info'] = str_replace('礼品',$this->config['gift_alias_name'],$value['info']);
			$value['info'] = str_replace('快店',$this->config['shop_alias_name'],$value['info']);

			$value['tab_name'] = str_replace('订餐',$this->config['meal_alias_name'],$value['tab_name']);
            if(strpos($value['tab_name'], '社区团购')!==false){
                $value['tab_name'] = str_replace('社区团购', $this->config['village_group_alias'] ?? '社区团购', $value['tab_name']);
            }elseif (strpos($value['tab_name'], '团购')!==false){
                $value['tab_name'] = str_replace('团购',$this->config['group_alias_name'],$value['tab_name']);
            }
			$value['tab_name'] = str_replace('预约',$this->config['appoint_alias_name'],$value['tab_name']);
			$value['tab_name'] = str_replace('礼品',$this->config['gift_alias_name'],$value['tab_name']);
			$value['tab_name'] = str_replace('快店',$this->config['shop_alias_name'],$value['tab_name']);

			if($value['name'] == 'search_first_type'){
				$value['type'] = str_replace('订餐',$this->config['meal_alias_name'],$value['type']);
                if(strpos($value['type'], '社区团购')!==false){
                    $value['type'] = str_replace('社区团购', $this->config['village_group_alias'] ?? '社区团购', $value['type']);
                }elseif (strpos($value['type'], '团购')!==false){
                    $value['type'] = str_replace('团购',$this->config['group_alias_name'],$value['type']);
                }
				$value['type'] = str_replace('预约',$this->config['appoint_alias_name'],$value['type']);
				$value['type'] = str_replace('礼品',$this->config['gift_alias_name'],$value['type']);
				$value['type'] = str_replace('快店',$this->config['shop_alias_name'],$value['type']);
			}

			if(in_array($value['name'], array('shop_sort', 'store_shop_auth', 'shop_goods_score_edit', 'shop_goods_spread_edit'))){
				$value['desc'] = str_replace('快店',$this->config['shop_alias_name'],$value['desc']);
			}
			
            if ($value['name']!='merchant_recharge_agreement' || ($value['name']=='merchant_recharge_agreement' && $this->config['open_company_advance_deposit'])) {
				$config_list[$value['tab_id']]['name'] = $value['tab_name'];
				$config_list[$value['tab_id']]['list'][] = $value;
            }

            if ($value['name']=='employee_register_agreement') {
                $src = '<img src="'.file_domain().'/';
                $value['value'] = str_replace('<img src="/', $src, $value['value']);
                $value['value'] = str_replace('<img alt="" src="/', $src, $value['value']);
                $value['value'] = str_replace('img src=&quot;/', 'img src=&quot;'.file_domain().'/', $value['value']);
            }
		}

		$this->assign($this->build_html($config_list));

		if(!empty($_GET['galias'])){
			import('ORG.Util.Dir');
			Dir::delDirnotself('./runtime/Cache/System/');
		}

		$this->display();
	}

	public function amend(){

		if(IS_POST){
//			var_dump($_POST);die;
		    $isEditSendTime = false;
			$database_config = D('Config');

            if($_POST['is_open_goods_default_image'] == 1 && empty($_POST['goods_default_image'])){
                $this->error('开启商品默认图片，需要您同时上传一张商品默认图。');
            } 

            if($_POST['open_spread_rand'] == 1){
            	if($_POST['spread_rand_min']<=0  || $_POST['spread_rand_max']<=0){
                	$this->error('开启一级推广随机奖励，每次随机金额最小值与最大值必须设置大于0的数值。');
            	}

            	if($_POST['spread_rand_min'] >= $_POST['spread_rand_max']){
                	$this->error('开启一级推广随机奖励，每次随机金额最小值不能大于最大值。');
            	}

            }
            $_POST['house_name'] = str_replace(['{', '}'], '', $_POST['house_name']);//特殊字符过滤
            $_POST['single_name'] = str_replace(['{', '}'], '', $_POST['single_name']);//特殊字符过滤
            $_POST['floor_name'] = str_replace(['{', '}'], '', $_POST['floor_name']);//特殊字符过滤
            $_POST['room_name'] = str_replace(['{', '}'], '', $_POST['room_name']);//特殊字符过滤
            // 处理页面交互传错参数问题
            if (!isset($_POST['site_url'])&&(isset($_POST['mobile_head_color'])||isset($_POST['mobile_head_floor_color']))) {
                unset($_POST['mobile_head_color']);
                unset($_POST['mobile_head_floor_color']);
            }
            if (isset($_POST['house_name']) && trim($_POST['house_name']) == '') {
                unset($_POST['house_name']);
            }

            if(isset($_POST['wechat_qrcode'])){
                $_POST['wechat_qrcode'] = $_POST['wechat_qrcode'] ? replace_file_domain($_POST['wechat_qrcode']) : '';
            }
//            if($_POST['waimai_time_interval'] < 15 OR $_POST['waimai_time_interval'] > 600){
//                $this->error('配送/自提可选时间段间隔 限制15-600分钟');
//            }
			
			//判断提交了国际化手机字段，则强行将系统时区设置为默认城市时区一次，如果默认城市无时区，则自动设置为 PRC，北京时间
			if($_POST['country_code']){
				$db_file = 'conf/db.php';
				//判断文件可写
				if(is_writable($db_file)){
					$db_config = include($db_file);
					$now_city_timezone = M('Area')->where(['area_id'=>$this->config['now_city']])->getField('timezone');
					if(!$now_city_timezone){
						$now_city_timezone = 'PRC';
					}
					if($db_config['DEFAULT_TIMEZONE'] != $now_city_timezone){
						$db_config['DEFAULT_TIMEZONE'] = $now_city_timezone;
						$db_content = '<?php '.PHP_EOL.'return '.var_export($db_config,true).';'.PHP_EOL.PHP_EOL;
						file_put_contents($db_file,$db_content);
						D('Config')->clearCache();
					}
				}
			}

            //推广分佣设置  区间配置
            $money_start_arr = $_POST['money_start'];
            $money_end_arr = $_POST['money_end'];
            $percent_arr = $_POST['money_percent'];
            if($money_start_arr){
            	if($_POST['merchant_spread']){// 商家推广分佣
            		$spreadDb = M('Percent_spread_merchant_detail');
            	}else{// 平台推广分佣
            		$spreadDb = M('Percent_spread_detail');
            	}
            	foreach ($money_start_arr as $key => $v) {
					if(empty($v) && empty($money_end_arr[$key]) && empty($percent_arr[$key])){
						continue;
					}
					
	                if (!is_numeric($v)||!is_numeric($money_end_arr[$key])||!is_numeric($percent_arr[$key])||$v < 0 || empty($money_end_arr[$key]) || $money_end_arr[$key] < 0 || empty($percent_arr[$key]) || $percent_arr[$key] < 0) {
	                    $this->error('数据设置有误，请检查');
	                } else if ($v >= $money_end_arr[$key]) {
	                    $this->error('推广人数范围设置有误，推广人数范围结束不能比推广人数范围起始小');
	                }
	                if ($tmp != 0 && $money_start_arr[$key] < $tmp) {
	                    $this->error('推广人数范围设置有误，推广人数范围起始不能比上一级的推广人数范围结束小');
	                }
	                $spread_data['money_start'] = $v;
	                $spread_data['money_end'] = $money_end_arr[$key];
	                $spread_data['percent'] = $percent_arr[$key];
	                $spread_data_arr[] = $spread_data;
	                $tmp = $money_end_arr[$key];
	            }
	            $count = $spreadDb->count();
	            if($count)
	            {
	                $spreadDb->where(['id'=>['gt',0]])->delete();
	            }
	            $res = $spreadDb->addAll($spread_data_arr);
            }elseif($percent_arr){// 修改其他业务
            	$spread_where = ['fid'=>0];
            	 $spread_data[$_POST['use_spread_type'].'_percent_detail'] = implode(',',$percent_arr);
                if(M('Percent_spread_detail_by_type')->where($spread_where)->find()){
                    $result = M('Percent_spread_detail_by_type')->where($spread_where)->save($spread_data);
                }else{
                    $spread_data['fid'] = 0;
                    $result = M('Percent_spread_detail_by_type')->add($spread_data);
                }
            }

            if(isset($_POST['add_score_by_system_commission']) && $_POST['add_score_by_system_commission']){
            	if($_POST['add_score_by_system_commission_merchant_percent'] + $_POST['add_score_by_system_commission_user_percent'] > 100){
                	$this->error('商家占比与用户占比之和不能大于100');
            	}
            }
             
			foreach($_POST as $key=>$value){
				$data['name'] = $key;

				if ($key == 'live_show_commission_percent') {
                    D('Live_goods')->where(['is_sys' => 1])->data(['percent' => $value])->save();
                }

				if(is_array($value)){
				    if($key == 'delivery_time' || $key == 'delivery_time2' || $key == 'delivery_time3'){
						$data['value'] = implode('-',$value);
					}
                    if($key == 'service_delivery_time' || $key == 'service_delivery_time2'){
                        $data['value'] = implode('-',$value);
                    }
                    if($key == 'service_buy_delivery_time' || $key == 'service_buy_delivery_time2'){
                        $data['value'] = implode('-',$value);
                    }
                    if (($key == 'service_buy_weight_info' || $key == 'service_give_weight_info') && $value) {
				        $info = array();
				        foreach ($value as $val) {
				            if ($val && $val['start_weight']>=0 && $val['start_weight']!='' && $val['end_weight'] && $val['weight_price']>=0 && $val['weight_price']!='') {
                                $info[] = $val;
                            }
                        }
				        if ($info) {
                            $data['value'] = serialize($info);
                        }
                    }
				}else{
					$data['value'] = trim(stripslashes(htmlspecialchars_decode($value)));

				}
				if($key=='village_group_threshold_warning_member'){
					$data['value'] = $_POST['village_group_threshold_warning_member_value'];
					unset($_POST['village_group_threshold_warning_member_value']);
				}

				if($key=='site_url'){
					$data['value'] = trim($value);
					$data['value'] = rtrim($data['value'],'/');
				}
				
				//关闭自动开门，则关闭用户原有的自动开门设置
				if($key == 'house_village_auto_door' && $value == 0){
					M('User')->where(array('auto_door_open'=>'1'))->data(array('auto_door_open'=>'0'))->save();
				}

				//【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
				if($key == 'live_show_new_user_open' && $value == 1){
					if(M("Config")->where(['name' =>'live_show_new_user_opened'])->field("value")->find()['value'] == '0'){//必须第一次开启，否则不行
						M('User')->where(array('open_live_show'=>'0'))->data(array('open_live_show'=>'1'))->save();
						M("Config")->where(['name' =>'live_show_new_user_opened'])->data(['value'=>1])->save();
					}
				}
				
				//推广营销-平台活动页面开关
				if($key == 'is_open_activity'){
					$data['value'] = trim($value);
					M('System_menu')->where(array('module'=>'Platform','action'=>'manage'))->data(array('status'=>$value['status']))->save();
				}
				//定制-从业者功能开关
                if($key == 'employee_switch'){
                    $data['value'] = trim($value);
                    M('System_menu')->where(array('module'=>'Employee','action'=>'index'))->data(array('status'=>$value['status']))->save();
                }
                //定制-用户推广商家
                if($key == 'open_c2b_spread'){
				   M('System_menu')->where(array('module'=>'User','action'=>'user_spread_list'))->data(array('status'=>$value))->save();
                }
                //定制-通联支付营销
                if($key == 'pay_allinpay_mer_open'){
                    M('System_menu')->where(array('module'=>'Allinpay','action'=>'discount'))->data(array('status'=>$value))->save();
                }
                //定制-商家推广商家佣金
                if($key == 'merchant_recommend_wholesale_switch'){
                    M('System_menu')->where(array('module'=>'Merchant','action'=>'recommend_index'))->data(array('status'=>$value))->save();
                    M('New_merchant_menu')->where(array('module'=>'Merchant_recommend','action'=>'index'))->data(array('status'=>$value))->save();
                }
                //定制-商家类型
                if($key == 'merchant_type_switch'){
                    M('System_menu')->where(array('module'=>'Merchant','action'=>'type_index'))->data(array('status'=>$value))->save();
                }
                //定制-发现
                if($key == 'find_msg'){
                    M('System_menu')->where(array('module'=>'Discover','action'=>'index'))->data(array('status'=>$value))->save();
                }
                //定制-快跑者配送
                if($key == 'keloop_is_open'){
                    if($_POST['dada_is_open']==1){
                        $data['value'] = 0;
                    }
                }

                //定制-隐藏部分前端内容
                if($key == 'close_system_lottery'){
				    if($value==1){
				        $status = 0;
				    }else{
                        $status = 1;
                    }
                    M('System_menu')->where(array('module'=>'Lottery','action'=>'index'))->data(array('status'=>$status))->save();
                    M('Config')->where(array('gid'=>'47','tab_id'=>'share_lottery'))->data(array('status'=>$status))->save();
                }
				
				if($key == 'alipay_app_prikey' || $key == 'pay_alipayh5_merchant_private_key' || $key == 'pay_alipayh5_public_key'){
					$data['value'] = str_replace([PHP_EOL, ' ', '	'], '', $value);
				}
				
				if($key == 'new_pay_alipay_app_public_key' || $key == 'new_pay_alipay_app_private_key'){
					$data['value'] = str_replace([PHP_EOL, ' ', '	'], '', $value);
				}
				
				if($key == 'mobile_code_private_key'){
					$data['value'] = str_replace([PHP_EOL, ' ', '	'], '', $value);
				}
				
				if($_POST['many_city'] && (strpos($this->config['site_url'],'weihubao.com') || strpos($this->config['site_url'],'dazhongbanben.com'))){
					$this->error('临时域名不能开启多城市功能');
				}
				
				if($_POST['many_city'] && strpos($_POST['many_city_main_domain'],'.') !== FALSE){
					$this->error('请正式填写您的主域名的二级域名部分，用来跳转。一般为 www，不能带有 “ . ”（符号点）');
				}

				if(strpos($key,'score_max') && strpos($value,'%')  ){
					$tmp_v = floatval(str_replace('%','',$value));
					if( $tmp_v>100){
						$this->error('积分使用数据不能大于100%');
					}
				}

				$num = $database_config->where(array('name'=>$key))->count();
				//分润开关，计划任务初始化到第二天凌晨
				if($key == 'open_score_fenrun'){
					M('Process_plan')->where(array('file'=>'user_fenrun'))->setField('plan_time',(strtotime(date("Y-m-d",$_SERVER['REQUEST_TIME']))+86400));
				}
				
				if($num>0){
					$before = $database_config->where('name="'.$key.'"')->find();
                    $tmp_type_arr = explode('&',$before['type']);
                    $type_arr = array();
                    foreach($tmp_type_arr as $k=>$v){
                        $tmp_value = explode('=',$v);
                        $type_arr[$tmp_value[0]] = $tmp_value[1];
                    }
                    if($type_arr['type'] == 'multiSelect'){
                        $value_arr = $value ? explode(',', $value) : [0];
                        foreach ($value_arr as $item){
                            if($item == 0){
                                $value_arr = [0];
                                break;
                            }
                        }
                        $data['value'] = $value_arr ? implode(',', $value_arr) : 0;
                    }
					if($before['gid']){
						$before_group = M("Config_group")->where(['gid'=>$before['gid']])->find();
					}
					$database_config->data($data)->save();
					if($before['value'] != $data['value']){
						D("System_log")->add_log("编辑了配置项:".($before['tab_name'] ?$before['tab_name'] : "").($before['info'] ? ($before['tab_name'] ? "/" : "").$before['info'] : ""),'平台配置项',$key);
					}
				}else{
					$database_config->data($data)->add();
				}
				
				if ($key == 'deliver_send_time') {
				    if ($_POST['deliver_send_time'] != $this->config['deliver_send_time']) {
				        $isEditSendTime = true;
				    }
				}
                if (in_array($data['name'], ['jg_im_appkey', 'jg_im_masterkey'])) {
                    $filterKey = str_replace('jg_', '', $data['name']);
                    $old = D('Config_data')->where(['name' => $filterKey])->find();
                    if ($old) {
                        D('Config_data')->where(['name' => $filterKey])->save(['value' => $data['value']]);
                    } else {
                        D('Config_data')->add(['name' => $filterKey, 'value' => $data['value']]);
                    }
                }
				if($key == 'jd_price_add_percent'){
                    C('config.jd_price_add_percent',$value);
                }elseif ($key == 'jd_price_add_percent_gt1000'){
                    C('config.jd_price_add_percent_gt1000',$value);
                }elseif ($key == 'jd_price_add_percent_gt2000'){
                    C('config.jd_price_add_percent_gt2000',$value);
                }elseif ($key == 'jd_price_add_percent_gt3000'){
                    C('config.jd_price_add_percent_gt3000',$value);
                    D('Shop_goods')->jdPriceUpdate();
                }

				if ($key == 'wechat_sourceid') {
					$data['name'] = 'wechat_token';
					$data['value'] = md5('pigcms_wechat_token' . $data['value']);
					$database_config->data($data)->save();
				}
				if($key == 'weidian_url' && $value && !file_put_contents('./api/weidian.urls',$data['value'])){
					$this->error('配置保存失败，请检查网站根目录下的api文件夹是否拥有可写权限。');
				}
				if($key == 'appoint_site_url' && $value && !file_put_contents('./api/appoint.urls',$data['value'])){
					$this->error('配置保存失败，请检查网站根目录下的api文件夹是否拥有可写权限。');
				}
				if($key == 'portal_switch' && !file_put_contents('./api/default.urls',$data['value'])){
					$this->error('配置保存失败，请检查网站根目录下的api文件夹是否拥有可写权限。');
				}
			}
			//更新平台客服用户昵称和头像
            hook::hook_exec('site_info.after_update',array('type'=>'plat'));

			//第三方实名认证--开启商城商品实名认证时   店铺列表的实名认证就要失效
            if($_POST['open_mall_check_id_card'] == 1) {
                $checked_store_ids = M('Merchant_store')->field('store_id')->where(['is_authentication'=>1])->select();

                if($checked_store_ids) {
                    foreach ($checked_store_ids as $value) {
                        M('Merchant_store')->where(['store_id'=>$value['store_id']])->save(['is_authentication'=>0]);
                    }
                }
            }

			//删除缓存
			D('Config')->clearDataCacheDir('system_config');

            // 清除v20缓存
            invoke_v20_service('\app\common\model\service\CacheSqlService/deleteCache',['param' => ['name'=>'o2oconfig']]);

			if ($isEditSendTime) {
		        $sql = "UPDATE pigcms_merchant_store_shop SET ";
		        $sql .= "send_time={$_POST['deliver_send_time']}, s_send_time={$_POST['deliver_send_time']}, sort_time = CASE ";
		        $sql .= "WHEN send_time_type =0 THEN {$_POST['deliver_send_time']} + work_time ";
		        $sql .= "WHEN send_time_type =1 THEN {$_POST['deliver_send_time']} + work_time *60 ";
		        $sql .= "WHEN send_time_type =2 THEN {$_POST['deliver_send_time']} + work_time *1440 ";
		        $sql .= "WHEN send_time_type =3 THEN {$_POST['deliver_send_time']} + work_time *10080 ";
		        $sql .= "WHEN send_time_type =4 THEN {$_POST['deliver_send_time']} + work_time *43200 END";
		        $sql .= " WHERE (`deliver_type`=0 OR `deliver_type`=3) AND (`s_send_time`=0 OR `s_send_time`={$this->config['deliver_send_time']})";
		        D()->execute($sql);
			}
            // 参数存在调取下消息获取
            if(isset($_POST['wx_house_appid']) && $_POST['wx_house_appid'] && isset($_POST['wx_house_token']) && $_POST['wx_house_token'] && isset($_POST['wx_house_appsecret']) && $_POST['wx_house_appsecret'] && isset($_POST['wx_house_encodingaeskey']) && $_POST['wx_house_encodingaeskey']) {
                $component_appid = $_POST['wx_house_appid'];
                $component_secret = $_POST['wx_house_appsecret'];
                D('Access_token_common_expires')->api_start_push_ticket($component_appid, $component_secret);
            }

            //todo a185触发下发配置命令
            D('Face_a185_request_record')->a185_sync_command(true,false,$this->config);
			$this->success('修改成功!');
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}

	public  function ajax_upload_wx_media(){
		if($_FILES['imgFile']['error'] != 4){
			$path = $_GET['path'] ?$_GET['path']:'system';
			$image = D('Image')->handle(rand(1,100), $path, 1);
			if ($image['error']) {
				exit(json_encode($image));
			} else {
				$url = $_SERVER['DOCUMENT_ROOT'] . $image['url']['imgFile'];
				if (!file_exists($url)) {
					file_put_contents($url, file_get_contents(replace_file_domain($image['url']['imgFile'])));
				}
				$mode = D('Access_token_expires');
				$res = $mode->get_access_token();
				import('ORG.Net.Http');
				$http = new Http();
				$file  = $url;
				$return = $http->curlUploadFile('https://api.weixin.qq.com/cgi-bin/media/upload?type=image&access_token='.$res['access_token'],$file,1);
				$return = json_decode($return,true);
				
				if($return['errcode'] && $return['errmsg']){
					exit(json_encode(array('error' => 1,'message' =>'微信返回报错：' . $return['errmsg'])));
				}

				exit(json_encode(array('error' => 0, 'url' => $return['media_id'],'title' => $image['url']['imgFile'])));
			}

		} else {
			exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
		}
	}

	public  function ajax_upload_pic_wx(){
		if($_FILES['imgFile']['error'] != 4){
			$path = $_GET['path'] ?$_GET['path']:'merchant';
			$image = D('Image')->handle(rand(1), $path, 1);
			if ($image['error']) {
				exit(json_encode($image));
			} else {
				$url = $_SERVER['DOCUMENT_ROOT'].$image['url']['imgFile'];
				$mode = D('Access_token_expires');
				$res = $mode->get_access_token();
				import('ORG.Net.Http');
				$http = new Http();
				$file  = $url;
				$return = $http->curlUploadFile('https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$res['access_token'],$file,1);
				$return = json_decode($return,true);
				exit(json_encode(array('error' => 0, 'url' => $return['url'])));
			}

		} else {
			exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
		}
	}

	public function show(){
		$this->assign('bg_color','#F3F3F3');
		$config = D('Config')->get_config();
		$this->display();
	}

	public function get_order_by_phone(){
		$pass =md5(md5(time()));
		$config = M('Config');
		$res = $config->where(array('name'=>'order_get_key'))->find();
		if($_GET['change']==1){
			if($res){
				$config->where(array('name'=>'order_get_key'))->save('value',$pass);

			}else{
				$data['name'] = 'order_get_key';
				$data['value'] = $pass;
				$config->add($data);

			}
			$res['value'] = $pass;
		}

		$this->assign('pass',$res['value']);
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
						$config_html .= '<th class="title_'.$value['name'].'" width="160" style="color:red;">'.$value['info'].'：</th>' ;
                        $config_html .= '<td class="info_'.$value['name'].'">';
					}elseif(in_array($value['name'],array('service_buy_weight_info','service_give_weight_info')) && !$this->config['service_weight_multilevel_switch']){
                        $config_html .= '<th class="title_'.$value['name'].'" width="160" style="display: none;">'.$value['info'].'：</th>' ;
                        $config_html .= '<td class="info_'.$value['name'].'" style="display: none;">';
                    } elseif (in_array($value['name'],array('service_basic_weight','service_basic_weight_price', 'service_bounds_weight')) && $this->config['service_weight_multilevel_switch']){
                        $config_html .= '<th class="title_'.$value['name'].'" width="160" style="display: none;">'.$value['info'].'：</th>' ;
                        $config_html .= '<td class="info_'.$value['name'].'" style="display: none;">';
                    } elseif (in_array($value['name'],array('service_basic_km','service_basic_km_time', 'service_give_basic_distance', 'service_give_delivery_fee', 'service_give_per_km_price')) && $this->config['service_buy_deliver_times_switch']){
                        $config_html .= '<th class="title_'.$value['name'].'" width="160" style="display: none;">'.$value['info'].'：</th>' ;
                        $config_html .= '<td class="info_'.$value['name'].'" style="display: none;">';
                    } elseif (in_array($value['name'],array('service_buy1','service_buy2', 'service_buy_delivery_fee2', 'service_buy_basic_distance2', 'service_buy_per_km_price2', 'service_buy_delivery_time', 'service_buy_delivery_time2', 'service_buy_delivery_fee', 'service_buy_basic_distance', 'service_buy_per_km_price')) && !$this->config['service_buy_deliver_times_switch']){
                        if (in_array($value['name'],array('service_buy1','service_buy2'))) {
                            $config_html .= '<th class="title_'.$value['name'].'" width="160" style="display: none;color:red;">'.$value['info'].'：</th>' ;
                        } else {
                            $config_html .= '<th class="title_'.$value['name'].'" width="160" style="display: none;">'.$value['info'].'：</th>' ;
                        }
                        $config_html .= '<td class="info_'.$value['name'].'" style="display: none;">';
                    } elseif (in_array($value['name'],array('service_buy1', 'service_buy2'))){
                        $config_html .= '<th class="title_'.$value['name'].'" width="160" style="color:red;">'.$value['info'].'：</th>' ;
                        $config_html .= '<td class="info_'.$value['name'].'">';
                    }else{
						$config_html .= '<th class="title_'.$value['name'].'" width="160">'.$value['info'].'：</th>' ;
                        $config_html .= '<td class="info_'.$value['name'].'">';
					}

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
							if (!$radio_one[0]) {
							    // 无值的时候默认为0
                                $radio_one[0] = 0;
                            }
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
					    if ($type_arr['write']) {
                            $config_html .= '<span class="config_upload_file_btn" file_validate="'.$type_arr['file'].'"><input type="button" value="上传文件" class="button" style="margin-left:0px;margin-right:10px;"/></span><input type="text" class="input-text input-file" name="'.$value['name'].'" id="config_'.$value['name'].'" value="'.$value['value'].'" size="48" validate="'.$type_arr['validate'].'" tips="'.$value['desc'].'"/> ';
                        } else {
                            $config_html .= '<span class="config_upload_file_btn" file_validate="'.$type_arr['file'].'"><input type="button" value="上传文件" class="button" style="margin-left:0px;margin-right:10px;"/></span><input type="text" class="input-text input-file" name="'.$value['name'].'" id="config_'.$value['name'].'" value="'.$value['value'].'" size="48" readonly="readonly" validate="'.$type_arr['validate'].'" tips="'.$value['desc'].'"/> ';
                        }
					}else if($type_arr['type'] == 'select'){

						if($value['name']=="recharge_level_value" || $value['name']=="youzhanzhu_level"){
							$levelDb=M(_view('User_level'));
							$tmparr=$levelDb->field(true)->order('`id` ASC')->select();
							$type_arr['value'] = '';
							foreach ($tmparr as $vc) {
								$type_arr['value'] .= $vc['level'].':'.$vc['lname'].'|';
							}
							$type_arr['value']  = trim($type_arr['value'] ,'|');
						}elseif ($value['name']=="village_group_threshold_warning_member"){
                            $adminDb=M(_view('Admin'));
                            $tmparr = $adminDb->field(true)->where(['status'=>1])->order('`id` ASC')->select();
                            $type_arr['value'] = '';
                            foreach ($tmparr as $vc) {
                                $type_arr['value'] .= $vc['id'].':'.$vc['account'].'|';
                            }
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
					} else if($type_arr['type'] == 'textArr') {
					    $textArr = unserialize($value['value']);
                        $return_config['count_text_arr'] = count($textArr);
                        $index_key = 0;

                        if ($textArr) {
                            foreach ($textArr as $k => $v) {
                                $config_html .= '<p class="config_text_arr_' . $k . '"><span>';
                                $config_html .= '<input type="text" oninput = "value=value.replace(/[^\d]/g,'."''".')" class="input-text" name="'.$value['name'].'['.$k.'][start_weight]" value="'.$v['start_weight'].'" size="5"> 公斤至 ';
                                $config_html .= '<input type="text" oninput = "value=value.replace(/[^\d]/g,'."''".')" class="input-text" name="'.$value['name'].'['.$k.'][end_weight]" value="'.$v['end_weight'].'" size="5"> 公斤，费用为 ';
                                $config_html .= '<input type="text" oninput = "value=value.replace(/[^\d]/g,'."''".')" class="input-text" name="'.$value['name'].'['.$k.'][weight_price]" value="'.$v['weight_price'].'" size="5"> '.C('config.Currency_txt');
                                if ($index_key == 0) {
                                    $config_html .= '<button data-item="'.$k.'" data-name="'.$value['name'].'" type="button" class="plus_recharge"></button>';
                                } else {
                                    $config_html .= '<button data-item="'.$k.'" data-name="'.$value['name'].'" type="button" class="reduce_recharge"></button>';
                                }
                                $config_html .= '</span></p>';
                                $index_key++;
                            }
                        } else {
                            $config_html .= '<p class="config_text_arr_0"><span>';
                            $config_html .= '<input type="text" oninput = "value=value.replace(/[^\d]/g,'."''".')" class="input-text" name="'.$value['name'].'[0][start_weight]" value="" size="5"> 公斤至 ';
                            $config_html .= '<input type="text" oninput = "value=value.replace(/[^\d]/g,'."''".')" class="input-text" name="'.$value['name'].'[0][end_weight]" value="" size="5"> 公斤，费用为 ';
                            $config_html .= '<input type="text" oninput = "value=value.replace(/[^\d]/g,'."''".')" class="input-text" name="'.$value['name'].'[0][weight_price]" value="" size="5"> 元';
                            $config_html .= '<button data-item="0" data-name="'.$value['name'].'" type="button" class="plus_recharge"></button>';
                            $config_html .= '</span></p>';
                        }
                    }else if($type_arr['type'] == 'show'){
						$config_html .= ''.$value['value'];
					}else if($type_arr['type'] == 'multiSelect'){
                        $radio_option = explode('|',$type_arr['value']);
                        $value_option = explode(',',$value['value']);
                        $config_html .= '<div class="show-select-value"><div class="select-list">';
                        foreach($radio_option as $radio_k=>$radio_v) {
                            $radio_one = explode(':', $radio_v);
                            foreach ($value_option as $vo) {
                                if($vo == $radio_one[0]){
                                    $config_html .= '<div class="item" data-id="'.$vo.'">'.$radio_one[1].'<span class="icon">×</span></div>';
                                }
                            }
                        }
                        $config_html .= '</div><span class="select-icon"></span><ul class="list">';
                        foreach($radio_option as $radio_k=>$radio_v){
                            $radio_one = explode(':',$radio_v);
                            $config_html .= '<li class="item" data-id="'.$radio_one[0].'">'.$radio_one[1].'</li>';
                        }
                        $config_html .= '</ul><input class="hidden_multi_select" type="hidden" name="'.$value['name'].'" value="'.$value['value'].'""></div>';
                    }
					$config_html .= '</td>';
					$config_html .= '</tr>';
				}
				$config_html .= '</table>';
				$pigcms_auto_key++;
			}
			if($has_tab) $config_tab_html .= '</ul>';
			//echo $config_html;die;
			$return_config['config_html'] = $config_html;
			if($has_tab) $return_config['config_tab_html'] = $config_tab_html;
			return $return_config;
		}
	}

	public function ajax_upload_pic(){
		if($_FILES['imgFile']['error'] != 4){
			if($_GET['type']){
				$type = $_GET['type'];
			}elseif ($_GET['dir']=='media'){
                $type =  'video';
            }else{
				$type =  'config';
			}
			$image = D('Image')->handle($this->system_session['id'], $type, 0, array('size' => 10), false);
			if (!$image['error']) {
				exit(json_encode(array('error' => 0,'url' => $image['url']['imgFile'], 'title' => $image['title']['imgFile'])));
			}
			exit(json_encode($image));
		}else{
			exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
		}
	}

	public function ajax_upload_apk(){
		if($_FILES['imgFile']['error'] != 4){
			$img_admin_id = sprintf("%09d", $this->system_session['id']);

			$upload_dir = "./upload/apk/";
			if(!is_dir($upload_dir)){
				mkdir($upload_dir,0777,true);
			}
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxSize = 200*1024*1024;
			$upload->allowExts = array('apk');
			$upload->savePath = $upload_dir;
			$upload->uploadReplace = 1;
			$upload->saveRule = '';
			if($upload->upload()){
				$uploadList = $upload->getUploadFileInfo();
				$title =$uploadList[0]['savename'];
				exit(json_encode(array('error' => 0,'url' =>$this->config['site_url'].'/upload/apk/'.$uploadList[0]['savename'],'title'=>$title)));
			}else{
				exit(json_encode(array('error' => 1,'message' =>$upload->getErrorMsg())));
			}
		}else{
			exit(json_encode(array('error' => 1,'message' =>'没有选择文件')));
		}
	}

	public function ajax_upload_file(){
		if(empty($_GET['name'])){
			exit(json_encode(array('error' => 1,'message' =>'不知道您要上传到哪个配置项，请重试。')));
		}
		$now_config = D('Config')->field('`name`,`type`')->where(array('name'=>$_GET['name']))->find();
		if(empty($now_config)){
			exit(json_encode(array('error' => 1,'message' =>'您正在上传的配置项不存在，请重试。')));
		}
		$tmp_type_arr = explode('&',$now_config['type']);
		$type_arr = array();
		foreach($tmp_type_arr as $k=>$v){
			$tmp_value = explode('=',$v);
			$type_arr[$tmp_value[0]] = $tmp_value[1];
		}
		$allowExts = array_key_exists('file',$type_arr) ? explode(',',$type_arr['file']) : array();
		if($_FILES['imgFile']['error'] != 4){
 
			$img_admin_id = sprintf("%09d", $this->system_session['id']);
			$rand_num = substr($img_admin_id,0,3) . '/' . substr($img_admin_id,3,3) . '/' . substr($img_admin_id,6,3);
			$upload_dir = "./upload/files/{$rand_num}/";
			if(!is_dir($upload_dir)){
				mkdir($upload_dir,0777,true);
			}
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxSize = 10*1024*1024;
			$upload->allowExts = $allowExts;
			$upload->savePath = $upload_dir;
			$upload->saveRule = 'uniqid';
			if($upload->upload()){
				$uploadList = $upload->getUploadFileInfo();
				$title = $rand_num.','.$uploadList[0]['savename'];
				exit(json_encode(array('error' => 0,'url' =>'./upload/files/'.$rand_num.'/'.$uploadList[0]['savename'],'title'=>$title)));
			}else{
				exit(json_encode(array('error' => 1,'message' =>$upload->getErrorMsg())));
			}
		}else{
			exit(json_encode(array('error' => 1,'message' =>'没有选择文件')));
		}
	}
	public function live_service(){
		if (empty($this->config['site_url'])) {
			exit(json_encode(array('error_code' => true, 'msg' => '先填写您网站的域名')));
		}
		$live_service = new live_service();
		$live_service->create();
	}
	public function im(){
		if (empty($this->config['site_url'])) {
			exit(json_encode(array('error_code' => true, 'msg' => '先填写您网站的域名')));
		}
		if (empty($this->config['wechat_appid']) || empty($this->config['wechat_appsecret'])) {
			exit(json_encode(array('error_code' => true, 'msg' => '先设置站点的微信公众号信息')));
		}
		$im = new im();
		$im->create();
	}
	public function change_https(){
		$parse_url = parse_url($this->config['site_url']);
		if(empty($parse_url) || empty($parse_url['scheme']) || empty($parse_url['host'])){
			exit(json_encode(array('error_code' => true, 'msg' => '网站网址配置不正确')));
		}
		if($parse_url['scheme'] == 'https'){
			exit(json_encode(array('error_code' => true, 'msg' => '网站网址已经是https的，无法再次替换')));
		}
		$sql = "UPDATE `pigcms_adver` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_config` SET `value`=REPLACE(`value`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_diymenu_class` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_home_menu` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_goods_category_banner` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_slider` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_image_text` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_platform` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_group` SET `content`=REPLACE(`content`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_appoint` SET `appoint_pic_content`=REPLACE(`appoint_pic_content`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_home_share` SET `a_href`=REPLACE(`a_href`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_classify` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_chanel_msg_content` SET `img`=REPLACE(`img`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_chanel_msg_content` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_user_spread_qrcode` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_flash` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_system_coupon` SET `wx_share_img`=REPLACE(`wx_share_img`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_system_coupon` SET `img`=REPLACE(`img`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_system_coupon` SET `img_coupon`=REPLACE(`img_coupon`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_house_village_nav` SET `url`=REPLACE(`url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_house_service_category` SET `cat_url`=REPLACE(`cat_url`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_user` SET `avatar`=REPLACE(`avatar`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_footer_link` SET `content`=REPLACE(`content`, 'http://USE_DOMAIN', 'https://USE_DOMAIN'); 
UPDATE `pigcms_system_news` SET `content`=REPLACE(`content`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_card_new_coupon` SET `img`=REPLACE(`img`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_village_group_goods` SET `image`=REPLACE(`image`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_user` SET `avatar`=REPLACE(`avatar`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');
UPDATE `pigcms_invitation` SET `store_image`=REPLACE(`store_image`, 'http://USE_DOMAIN', 'https://USE_DOMAIN');";
		$sql = str_replace('USE_DOMAIN',$parse_url['host'],$sql);
		$sqls = explode(';',$sql);
		foreach($sqls as $value){
			$value = trim($value);
			if(!empty($value)){
				D('')->execute($value);
			}
		}
		exit(json_encode(array('error_code' => false, 'msg' => '整站替换HTTPS成功，请F5刷新页面后查看网站网址是否是https来判断是否替换成功。')));
	}
	
	public function check_baidu_map(){
		$url = 'http://api.map.baidu.com/geoconv/v1/?coords=116.433105,39.892880&from=3&to=5&ak=' . C('config.baidu_map_ak') . '&output=json';
	    import('ORG.Net.Http');
	    $http = new Http();
	    $result = $http->curlGet($url);
	    if ($result) {
	        $result = json_decode($result, true);
	        if ($result['status'] == 0) {
				exit(json_encode(array('error_code' => false, 'msg' => '百度地图key配置正确')));
	        }else{
				exit(json_encode(array('error_code' => true, 'msg' => '配置可能有误。返回信息：'.$result['message'])));
			}
	    }
	}
	
	public function get_wechat_code(){
		if(empty($this->config['wechat_appid']) || empty($this->config['wechat_appsecret'])){
			return(array('error_code'=>true,'msg'=>'请联系管理员配置【AppId】【 AppSecret】'));
		}
		
		//微信授权获得access_token
		$access_token_array = D('Access_token_expires')->get_access_token();
		if ($access_token_array['errcode']) {
			exit(json_encode(array('error_code' => true, 'msg' => '获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg'])));
		}else{
			exit(json_encode(array('error_code' => false, 'msg' => '公众号配置正常。仅能检查AppId、 AppSecret、IP白名单等。公众号后台配置参数需另行检查。')));
		}
	}
	
	public function repair_pc_qrcode(){
		D('')->execute('DELETE FROM `pigcms_login_qrcode`');
		D('')->execute('ALTER TABLE `pigcms_login_qrcode` AUTO_INCREMENT=1000000999;');
		D('')->execute('DELETE FROM `pigcms_im_qrcode`');
		D('')->execute('ALTER TABLE `pigcms_im_qrcode` AUTO_INCREMENT=1200020999;');
		exit(json_encode(array('error_code' => false, 'msg' => '执行成功')));
	}
	
	public function repair_store_time(){
		D('Process_plan')->where(['file'=> 'store_time'])->data(['plan_time'=> time() - 86400])->save();
		exit(json_encode(array('error_code' => false, 'msg' => '执行成功')));
	}
	
	/*签到设置*/
	public function sign_set_list(){
		$where=array();
		$where['is_del']=0;
		$model=M('sign_setting');
		$count=$model->where($where)->count(1);
		import('@.ORG.system_page');
		$p = new Page($count,30);
		$list=$model->where($where)->limit($p->firstRow,$p->listRows)->order('id asc')->select();
		$coupon_list=M('System_coupon')->getfield('coupon_id,order_money,discount,is_discount,discount_value',true);
		$page=I('page',1,'intval');
		foreach ($list as $k => $v) {
			$reward_arr=json_decode($v['reward_info'],true);
			if($v['type']==1){
				$v['desc']=$reward_arr['jifen'].'积分';
			}else{
				$coupon_id=$reward_arr['coupon_id'];
				$coupon_arr=$coupon_list[$coupon_id];
				if($coupon_arr['is_discount']==1){
					$v['desc']='平台优惠券(满'.$coupon_arr['order_money'].'打'.floatval($coupon_arr['discount_value']).'折)';
				}else{
					$v['desc']='平台优惠券(满'.$coupon_arr['order_money'].'减'.$coupon_arr['discount'].C('config.Currency_txt').')';						
				}
			}
			$v['day']='第'.($k+$page).'天';
			$list[$k]=$v;
		}
		$pagebar = $p->show();
		$this->assign('list',$list);
		$this->assign('pagebar',$pagebar);
		$this->display();
	}

	/*自定义活动设置*/
	public function sign_setting(){
		$id=I('id',0,'intval');
		$model=M('sign_setting');
		if(IS_POST){
			$type=I('type',1,'intval');
			$reward_info=I('reward_info','','trim');
			fdump($reward_info,1);
			if($type==1 && $reward_info['jifen']<=0){
				$this->error('请填写正确的签到积分！');
			}
			if($type==2  && $reward_info['coupon_id']<=0){
				$this->error('前选择签到可领取的平台优惠券！');
			}
			$now_time=time();
			$data=array();
			$data['id']=$id;
			$data['type']=$type;
			$data['reward_info']=json_encode($reward_info);
			$data['update_time']=$now_time;
			if($id>0){
				$data['id']=$id;
				$res=$model->data($data)->save();
				if($res!==false){
					$this->success('编辑成功！');
				}else{
					$this->error('抱歉，编辑失败，请重试！');
				}
			}else{
				$data['add_time']=$now_time;
				$res=$model->data($data)->add();
				if($res){
					$this->success('添加成功！');
				}else{
					$this->error('抱歉，添加失败，请重试！');
				}
			}
		}else{
			$info=array();
			if($id>0){
				$where=array();
				$where['id']=$id;
				$where['is_del']=0;
				$info=$model->where($where)->find();
				if(empty($info)){
					$this->error('抱歉，该配置不存在或已删除！');
				}
				$reward_arr=json_decode($info['reward_info'],true);	
				if($info['type']==2){
					$coupon_id=$reward_arr['coupon_id'];
					$info['show_name']=$this->reg_coupon_name($coupon_id);
				}
				$info['reward_info']=$reward_arr;
			}
			if($info['id']>0){
				$where['id']=array('lt',$info['id']);
			}else{
				$info['type']=1;
			}
			$where['is_del']=0;
			$count=$model->where($where)->count(1);
			$info['day']=$count+1;
			$this->assign('info',$info);
			$is_edit=I('is_edit',1);
			$this->assign('is_edit',$is_edit);
			$this->display();
		}		
	}

	/*格式化优惠券的名称*/
	private function reg_coupon_name($coupon_id){
		$coupon_arr=M('System_coupon')->where(array('coupon_id'=>$coupon_id))->find();
		$return =  D('System_coupon')->cate_platform(); //模板中定义相关中文名字

		$coupon_arr['platform'] = unserialize($coupon_arr['platform']);
		$coupon_arr['platform'] = implode(',',$coupon_arr['platform']);
		$coupon_arr['cate_name'] = $coupon_arr['cate_name']=='all'?'全部类别':$return['category'][$coupon_arr['cate_name']];
		if(empty($coupon_arr['cate_id'])) {
			$coupon_arr['cate_id'] = '全部分类';
		}else{
			$coupon_arr['cate_id'] = unserialize($coupon_arr['cate_id']);
			$coupon_arr['cate_id'] = $coupon_arr['cate_id']['cat_name'];
		}
		if($coupon_arr['is_discount']==1){
			$show_name=$coupon_arr['name'].' -- '.date('Y年m月d日',$coupon_arr['start_time']).'到'.date('Y年m月d日',$coupon_arr['end_time']).' -- '.'满'.$coupon_arr['order_money'].'打'.floatval($coupon_arr['discount']).'折'.' -- '.$coupon_arr['platform'].' '.$coupon_arr['cate_name'].' '.$coupon_arr['cate_id'].' '.$coupon_arr['num'];
		}else{
			$show_name=$coupon_arr['name'].' -- '.date('Y年m月d日',$coupon_arr['start_time']).'到'.date('Y年m月d日',$coupon_arr['end_time']).' -- '.'满'.$coupon_arr['order_money'].'减'.$coupon_arr['discount'].C('config.Currency_txt').' -- '.$coupon_arr['platform'].' '.$coupon_arr['cate_name'].' '.$coupon_arr['cate_id'].' '.$coupon_arr['num'];
		}
		return $show_name;
	}

	//删除自定义签到设置
	public function sign_set_del(){
		$id=I('get.id','','intval');
		if(empty($id)){
			$this->error('缺少参数！');
		}
		$data=array();
		$data['id']=$id;
		$data['is_del']=1;
		$res=M('sign_setting')->data($data)->save();
		if($res){
			$this->success('删除成功！');
		}else{
			$this->error('删除失败，请重试！');
		}
	}
}
