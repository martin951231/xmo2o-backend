<?php

/*
 * 后台管理基础类
 *
 */

class IndexAction extends BaseAction
{

    public function index()
    {
		if(empty($_GET)){
			header('Location: ./v20/public/platform/');
			die;
		}
		
        $mysqlVersion = M()->query('select VERSION()');
        $server_info = array(
            'PHP运行环境' => PHP_OS,
            'PHP运行方式' => php_sapi_name(),
            'PHP版本' => PHP_VERSION,
            'MYSQL版本' => $mysqlVersion[0]['VERSION()'],
            '上传附件限制' => ini_get('upload_max_filesize'),
            '执行时间限制' => ini_get('max_execution_time') . '秒',
            '磁盘剩余空间 ' => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
        );
        if ($this->system_session['area_id'] != 0) {
            $now_area = D('Area')->get_area_by_areaId($this->system_session['area_id']);
            $this->assign('now_area', $now_area);
        }
        $this->assign('server_info', $server_info);
		
		
		// git支持
		if(file_exists('.git/HEAD')){
			$git_branch = str_replace('ref: refs/heads/', '', file_get_contents('.git/HEAD'));
			
			$git_pull = false;
			if(function_exists('shell_exec')){
				$git_pull = true;
			}
			
			$this->assign('git_branch', $git_branch);
			$this->assign('git_pull', $git_pull);
		}


        $this->display();
    }
	
	public function git_pull(){
		// echo 'cd ' . $_SERVER['DOCUMENT_ROOT'] . ' && pwd && git pull 2>&1';
		
		$out = exec('cd ' . $_SERVER['DOCUMENT_ROOT'] . ' && pwd && git pull 2>&1', $output, $return_var);
		
		if(is_array($output)){
			$out_msg = implode('<br/><br/>', $output);
		}else{
			$out_msg = $out;
		}

		exit(json_encode(array('error_code' => false, 'msg' => $out_msg ? $out_msg : "更新成功",'status'=>1000,'data'=>['msg'=>$out_msg ? $out_msg : "更新成功"])));
		
		// dump($out);
		// dump($output);
		// dump($return_var);
		
	}

    public function main()
    {
        if (!$this->system_session['area_id'] && $this->system_session['level'] != 2 && !in_array(9999, $this->system_session['menus'])) {
            $this->redirect(U('Index/profile'));
        }

        $area_id = $this->system_session['area_id'];//区域管理员区域

        $mysqlVersion = M()->query('select VERSION()');
        $server_info = array(
            'PHP运行环境' => PHP_OS,
            'PHP运行方式' => php_sapi_name(),
            'PHP版本' => PHP_VERSION,
            'MYSQL版本' => $mysqlVersion[0]['VERSION()'],
            '上传附件限制' => ini_get('upload_max_filesize'),
            '执行时间限制' => ini_get('max_execution_time') . '秒',
            '磁盘剩余空间 ' => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
        );
        $this->assign('server_info', $server_info);

        if ($area_id) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
        }

        $this->assign('now_area', $now_area);
        //网站统计
        if ($area_id) {
            $pigcms_assign['website_collect_count'] = floatval(M('Merchant_money_list')->join('as l left join ' . C('DB_PREFIX') . 'merchant m ON l.mer_id = m.mer_id')->where('m.' . $area_index . ' = ' . $area_id)->sum('total_money'));
        } else {
            $pigcms_assign['website_collect_count'] = floatval(M('Merchant_money_list')->sum('total_money'));
        }

        // 用户总数
        if ($area_id) {
            if ($area_index == 'area_id') {
                // 区域管理员没有权限
                $pigcms_assign['website_user_count'] = 0;
            }else{
				$website_user_where = [
					$area_index => $area_id,
					'status' => ['neq', 4],
					'_string' => '(openid NOT LIKE "%no_use%" OR openid IS NULL)'
				];
                $pigcms_assign['website_user_count'] = M('User')->where($website_user_where)->count();
            }
        } else {
			$website_user_where = [
				'status' => ['neq', 4],
				'_string' => '(openid NOT LIKE "%no_use%" OR openid IS NULL)'
			];
            $pigcms_assign['website_user_count'] = M('User')->where($website_user_where)->count();
        }

        $where['status'] = 1;
        if ($area_id) {
            $where['_string'] = $area_index . ' = ' . $area_id;
            $pigcms_assign['website_merchant_count'] = M('Merchant')->where($where)->count();
        } else {
            $pigcms_assign['website_merchant_count'] = M('Merchant')->where($where)->count();
        }

        if ($area_id) {
            $where['_string'] = $area_index . ' = ' . $area_id;
            $pigcms_assign['website_merchant_store_count'] = M('Merchant_store')->where($where)->count();
            $area_info = M('Area')->where(array('area_id' => $area_id))->find();
            $this->assign('area_info', $area_info);
        } else {
            $pigcms_assign['website_merchant_store_count'] = M('Merchant_store')->where($where)->count();
        }
        //团购统计

        if ($area_id) {
            $where_group['l.status'] = 1;
            $where_group['_string'] = 'm.' . $area_index . ' = ' . $area_id;
            $pigcms_assign['group_group_count'] = M('Group')->join('as l left join ' . C('DB_PREFIX') . 'merchant m ON l.mer_id = m.mer_id')->where($where_group)->count();
        } else {
            $pigcms_assign['group_group_count'] = M('Group')->where(array('status' => 1))->count();
        }
        //订餐统计
        $condition['s.status'] = 1;
        $condition['s.have_meal'] = 1;
        if ($area_id) {
            $condition['_string'] = 's.' . $area_index . ' = ' . $area_id;
        }

        //$sql = "SELECT count(s.store_id) as count FROM ". C('DB_PREFIX') . "merchant_store AS s INNER JOIN ". C('DB_PREFIX') . "merchant_store_foodshop AS m ON s.store_id=m.store_id WHERE s.status=1";
        $foodshop_count = M('Merchant_store')->join('as s LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store_foodshop AS m ON s.store_id = m.store_id ')->where($condition)->count('s.store_id');
        //$result = D('')->query($sql);
        $pigcms_assign['meal_store_count'] = $foodshop_count;
        unset($condition['s.have_meal']);
        $condition['s.have_shop'] = 1;
        if ($area_id) {
            $condition['_string'] = 's.' . $area_index . ' = ' . $area_id;
        }
        $foodshop_count = M('Merchant_store')->join('as s LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store_shop AS m ON s.store_id = m.store_id ')->where($condition)->count('s.store_id');

        $pigcms_assign['shop_store_count'] = $foodshop_count;
        //预约统计

        $now_time = $_SERVER['REQUEST_TIME'];
        $appoint_where['appoint_status'] = 0;
        $appoint_where['check_status'] = 1;
        $appoint_where['start_time'] = array('lt', $now_time);
        $appoint_where['end_time'] = array('gt', $now_time);
        if ($area_id) {
            $appoint_where['_string'] = 'm.' . $area_index . ' = ' . $area_id;
            $pigcms_assign['appoint_group_count'] = M('Appoint')->join('as l left join ' . C('DB_PREFIX') . 'merchant m ON l.mer_id = m.mer_id')->where($appoint_where)->count();
        } else {
            $pigcms_assign['appoint_group_count'] = M('Appoint')->where($appoint_where)->count();
        }

        //商家待审核
        // $pigcms_assign['merchant_verify_list'] = D('Merchant')->where(array('status'=>'2','reg_time'=>array('gt',$this->system_session['last_time'])))->select();
        if ($this->system_session['area_id']) {
            $area_index = $area_index;
            $pigcms_assign['merchant_verify_count'] = D('Merchant')->where(array('status' => '2', $area_index => $this->system_session['area_id']))->count();
            //店铺待审核
            // $pigcms_assign['merchant_verify_store_list'] = D('Merchant_store')->where(array('status'=>'2','last_time'=>array('gt',$this->system_session['last_time'])))->select();
            $pigcms_assign['merchant_verify_store_count'] = D('Merchant_store')->where(array('status' => 2, $area_index => $this->system_session['area_id']))->count();
            //团购待审核
            // $pigcms_assign['group_verify_list'] = D('Group')->where(array('status'=>'2','last_time'=>array('gt',$this->system_session['last_time'])))->select();
            $merchants = D('Merchant')->field('mer_id')->where(array('status' => '1', $area_index => $this->system_session['area_id']))->select();
            $mer_ids = array();
            foreach ($merchants as $m) {
                if (!in_array($m['mer_id'], $mer_ids))
                    $mer_ids[] = $m['mer_id'];
            }

            $pigcms_assign['group_verify_count'] = 0;
            if ($mer_ids) {
                $pigcms_assign['group_verify_count'] = D('Group')->where(array('status' => '2', 'mer_id' => array('in', $mer_ids)))->count();
            }
        } else {
            $pigcms_assign['merchant_verify_count'] = D('Merchant')->where(array('status' => '2'))->count();
            //店铺待审核
            // $pigcms_assign['merchant_verify_store_list'] = D('Merchant_store')->where(array('status'=>'2','last_time'=>array('gt',$this->system_session['last_time'])))->select();
            $pigcms_assign['merchant_verify_store_count'] = D('Merchant_store')->where(array('status' => 2))->count();
            //团购待审核
            // $pigcms_assign['group_verify_list'] = D('Group')->where(array('status'=>'2','last_time'=>array('gt',$this->system_session['last_time'])))->select();
            $pigcms_assign['group_verify_count'] = D('Group')->where(array('status' => '2'))->count();
        }

        $this->assign('user', $this->ajax_user());
        $this->assign('mer_money', $this->ajax_merchant_money());

        $this->assign($pigcms_assign);
        $this->display();
    }

    public function get_alias_name()
    {
        $alias_name = array('group', 'meal', 'shop', 'appoint', 'store', 'weidian', 'wxapp', 'waimai');
        if (!isset($this->config['appoint_alias_name'])) {
            $key = array_search('appoint', $alias_name);
            unset($alias_name[$key]);
        }
        if (!isset($this->config['waimai_alias_name'])) {
            $key = array_search('waimai', $alias_name);
            unset($alias_name[$key]);
        }
        return $alias_name;
    }

    public function get_alias_c_name()
    {
        $alias_name = array(
            'all' => '全部',
            'group' => $this->config['group_alias_name'],
            'shop' => $this->config['shop_alias_name'],
            'meal' => $this->config['meal_alias_name'],
            'appoint' => $this->config['appoint_alias_name'],
            'waimai' => $this->config['waimai_alias_name'],
            'store' => '到店',
            'weidian' => '微店',
            'wxapp' => '营销',
            'withdraw' => '提现'
        );
        if (!isset($this->config['appoint_alias_name'])) {
            unset($alias_name['appoint']);
        }
        if (!isset($this->config['waimai_alias_name'])) {
            unset($alias_name['waimai']);
        }
        return $alias_name;
    }

    public function ajax_all_date()
    {
        ini_set('memory_limit', '512M');
        $_POST['day'] = I('day');
        $_POST['period'] = I('period');
        $_POST['province_idss'] = I('province_idss');
        $_POST['city_idss'] = I('city_idss');
        $_POST['area_id'] = I('area_id');
        $type = I('type');
        if (empty($_POST['day']) && empty($_POST['period'])) {
            $_POST['day'] = 1;
        }
        $area_id = $this->system_session['area_id'];//区域管理员区域
        if ($area_id) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
        }

        if (intval(I('province_idss')) && $this->config['many_city']) {
            $area_index = 'province_id';
            $area_id = I('province_idss');
        }
        if (intval(I('city_idss'))) {
            $area_index = 'city_id';
            $area_id = I('city_idss');
        }
        if (intval(I('area_id'))) {
            $area_index = 'area_id';
            $area_id = I('area_id');
        }


        $alias_name = $this->get_alias_name();
        $today_zero_time = mktime(0, 0, 0, date('m', $_SERVER['REQUEST_TIME']), date('d', $_SERVER['REQUEST_TIME']), date('Y', $_SERVER['REQUEST_TIME']));
        $period = false;
        $where_supply['type'] = 0;
        $where_supply['status'] = array('gt', 0);
        if (isset($_POST['period']) && !empty($_POST['period'])) {
            $period = explode(' - ', $_POST['period']);
            $_POST['begin_time'] = $period[0];
            $_POST['end_time'] = $period[1];
            if (strtotime($_POST['begin_time']) > strtotime($_POST['end_time'])) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $time_condition = " (o.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $time_condition_mer = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition_merchant_request['_string'] = $time_condition;
            $where_supply['_string'] = " (create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition_mer_request['_string'] = $time_condition_mer;
            $period = true;
        }
        if ($period && $_POST['begin_time'] == $_POST['end_time']) {
            $_POST['day'] = 1;
        }


        if ($_POST['day'] == 1 && !$period) {
            $condition_merchant_request['pay_time'] = array(array('egt', $today_zero_time), array('elt', time()));
            $condition_mer_request['use_time'] = array(array('egt', $today_zero_time), array('elt', time()));
            $where_supply['create_time'] = $condition_merchant_request['pay_time'];
        } else {
            if (!$period) {
                if ($_POST['day'] == 2) {
                    //本月
                    $today_zero_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
                    $condition_merchant_request['pay_time'] = array(array('egt', $today_zero_time), array('elt', $_SERVER['REQUEST_TIME']));
                    $condition_mer_request['use_time'] = array(array('egt', $today_zero_time), array('elt', $_SERVER['REQUEST_TIME']));
                } else {
                    $condition_merchant_request['pay_time'] = array(array('egt', $today_zero_time - (($_POST['day'] - 1) * 86400)), array('elt', time()));
                    $condition_mer_request['use_time'] = array(array('egt', $today_zero_time - (($_POST['day'] - 1) * 86400)), array('elt', time()));
                }
                $where_supply['create_time'] = array(array('egt', strtotime("-{$_POST['day']} day")), array('elt', time()));
            }
        }
        if ($area_id) {
            if ($condition_mer_request['_string']) {
                $condition_mer_request['_string'] .= " AND  (m.{$area_index}= {$area_id})";
            } else {
                $condition_mer_request['_string'] = "(m.{$area_index} = {$area_id})";
            }

            if ($condition_merchant_request['_string']) {
                $condition_merchant_request['_string'] .= " AND  (m.{$area_index}= {$area_id})";
            } else {
                $condition_merchant_request['_string'] = "(m.{$area_index} = {$area_id})";
            }

            $where_supply[$area_index] = $area_id;
        }

        $tmp_array = array();
        $condition_merchant_request['paid'] = 1;
        $condition_merchant_request['o.status'] = array('lt', 3);
        $res_group = M('Group_order')->field('total_money as money,payment_money,pay_type ,pay_time')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->select();

        $where_foodshop['o.paid'] = 1;
        $where_foodshop['o.business_type'] = 'foodshop';
        $condition_merchant_request['pay_time'] && $where_foodshop['o.pay_time'] = $condition_merchant_request['pay_time'];
        $condition_merchant_request['_string'] && $where_foodshop['_string'] = $condition_merchant_request['_string'];
//        $res_meal = M('Plat_order')->field('o.total_money as money,pay_money as payment_money,o.pay_type ,o.pay_time')
//            ->join(' as o left join '.C('DB_PREFIX').'foodshop_order as fs ON o.business_id = fs.order_id left join '.C('DB_PREFIX').'merchant m ON m.mer_id = fs.mer_id')
//            ->where($where_foodshop)->select();

//        $res_meal = M('Foodshop_order')->field('o.total_money as money,pay_money as payment_money,o.pay_type ,o.pay_time')
//            ->join(' as fs left join '.C('DB_PREFIX').'plat_order as o  ON o.business_id = fs.order_id left join '.C('DB_PREFIX').'merchant m ON m.mer_id = fs.mer_id')
//            ->where($where_foodshop)->select();

        $res_meal = M('Foodshop_order')->field('fs.price as money,pay_money as payment_money,o.pay_type ,fs.pay_time')
            ->join(' as fs left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = fs.mer_id left join ' . C('DB_PREFIX') . "plat_order as o  ON o.business_id = fs.order_id AND o.business_type='foodshop' ")
            ->where($where_foodshop)->select();


        $condition_merchant_request['o.status'] = array('lt', 4);
        $condition_merchant_request['o.platform'] = 0;
        $condition_merchant_request['o.order_from'] = ['neq', 1];
        $res_shop = M('Shop_order')->field('price as money,payment_money ,pay_type,pay_time')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->select();

        unset($condition_merchant_request['o.status'], $condition_merchant_request['o.platform']);
        $res_appoint = M('Appoint_order')->field('payment_money as money ,pay_money as payment_money,pay_type,pay_time,product_id,product_payment_price')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->select();
        $res_wxapp = M('Wxapp_order')->field('o.money,payment_money,pay_type ,pay_time')->where($condition_merchant_request)->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->select();
        $res_weidian = M('Weidian_order')->field('o.money as money,payment_money,pay_type ,pay_time')->where($condition_merchant_request)->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->select();
        $res_store = M('Store_order')->field('total_price as money,payment_money,pay_type ,pay_time')->where($condition_merchant_request)->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->select();

        $count['group'] = M('Group_order')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->count();

        $condition_merchant_request['o.status'] = array('lt', 4);
        $condition_merchant_request['o.platform'] = 0;
        $count['shop'] = M('Shop_order')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->count();
        unset($condition_merchant_request['o.platform']);
        $count['appoint'] = M('Appoint_order')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->count();
        $count['wxapp'] = M('Wxapp_order')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->count();
        $count['weidian'] = M('Weidian_order')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->count();
        unset($condition_merchant_request['paid']);
        $count['meal'] = M('Foodshop_order')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->count();

        // 到店
        unset($condition_merchant_request['o.status']);
        $condition_merchant_request['paid'] = 1;
        $count['store'] = M('Store_order')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_merchant_request)->count();
        $count['all'] = $count['group'] + $count['meal'] + $count['shop'] + $count['appoint'] + $count['wxapp'] + $count['weidian'] + $count['store'];

        $condition_mer_request['type'] = array('neq', 'withdraw');
        $mer_money = M('Merchant_money_list')->field('`o`.*')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_mer_request)->select();
        $mer_count = M('Merchant_money_list')->join(' as o left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = o.mer_id')->where($condition_mer_request)->group('o.type')->getField('type,count(o.id) as count');

        $deliver['count'] = M('Deliver_supply')->where($where_supply)->count();
        $deliver['money'] = M('Deliver_supply')->where($where_supply)->sum('freight_charge');
        $deliver['money'] = empty($deliver['money']) ? 0 : $deliver['money'];
        $shop_deliver = M('Deliver_supply')->where($where_supply)->select();
        $mer_count['all'] = 0;
        foreach ($alias_name as $mc) {
            if (!isset($mer_count[$mc])) {
                $mer_count[$mc] = 0;
            }
            $mer_count['all'] += $mer_count[$mc];
        }
        $weixin_pay = 0;
        $alipay_pay = 0;
        $system_take = $weixin = $alipay = array();
        $weixin['all'] = $alipay['all'] = 0;
        foreach ($alias_name as $rv) {
            $tmp = 'res_' . $rv;
            $weixin[$rv] = $alipay[$rv] = 0;
            foreach ($$tmp as $value) {

                if ($rv == 'appoint' && $value['product_id'] > 0) {
                    $value['money'] = $value['product_payment_price'];
                }

                if ($rv == 'appoint' && $value['product_id'] > 0) {
                    $value['money'] = $value['product_payment_price'];
                }
                if ($value['pay_type'] == 'weixin') {
                    $weixin_pay += $value['payment_money'];
                    $weixin[$rv] += $value['payment_money'];
                    $weixin['all'] += $value['payment_money'];
                } elseif ($value['pay_type'] == 'alipay' || $value['pay_type'] == 'alipayh5') {
                    $alipay_pay += $value['payment_money'];
                    $alipay[$rv] += $value['payment_money'];
                    $alipay['all'] += $value['payment_money'];
                }

                // $system_take+=$value['system_take'];
                if ($_POST['day'] == 2) {
                    $tmp_time = date('d', $value['pay_time']);
                } else if ($_POST['day'] == 1 || ($period && ($_POST['end_time'] == $_POST['begin_time']))) {
                    $tmp_time = date('G', $value['pay_time']);
                } else {
                    $tmp_time = date('ymd', $value['pay_time']);
                }
                if (!isset($tmp_array['all_count'][$tmp_time])) {
                    $tmp_array['all_count'][$tmp_time] = 0;
                    $tmp_array[$rv][$tmp_time]['count'] = 0;
                }
                $tmp_array['all_income'][$tmp_time] += $value['money'];
                $tmp_array['all_count'][$tmp_time] += 1;
                $tmp_array[$rv][$tmp_time]['income'] += $value['money'];
                $tmp_array[$rv][$tmp_time]['count'] += 1;
            }
        }


        $tmp_mer = array();
        foreach ($mer_money as $value) {
            if ($value['type'] == 'cash') {
                $value['type'] = 'store';
            }
            if ($_POST['day'] == 2) {
                $tmp_time = date('d', $value['use_time']);
            } else if ($_POST['day'] == 1) {
                $tmp_time = date('G', $value['use_time']);
            } else {
                $tmp_time = date('ymd', $value['use_time']);
            }

            if (!isset($tmp_mer['mer_count'][$tmp_time])) {
                $tmp_mer['mer_count'][$tmp_time] = 0;
                $tmp_mer['mer_count_by_type'][$value['type']][$tmp_time] = 0;
            }
            $tmp_mer['mer_income'][$tmp_time] += $value['money'];
            $tmp_mer['mer_count'][$tmp_time] += 1;
            $tmp_mer['mer_income_by_type'][$value['type']][$tmp_time] += pow(-1, $value['income'] + 1) * $value['money'];
            $tmp_mer['mer_system_take_by_type'][$value['type']][$tmp_time] += $value['system_take'];
            $tmp_mer['mer_count_by_type'][$value['type']][$tmp_time] += 1;
            $tmp_mer['mer_system_take'][$tmp_time] += $value['system_take'];

            $system_take[$value['type']] += $value['system_take'];
            $system_take['all'] += $value['system_take'];
        }
        $tmp_shop_deliver = array();

        foreach ($shop_deliver as $vv) {
            if ($_POST['day'] == 2) {
                $tmp_time = date('d', $vv['create_time']);
            } else if ($_POST['day'] == 1) {
                $tmp_time = date('G', $vv['create_time']);
            } else {
                $tmp_time = date('ymd', $vv['create_time']);
            }

            if (!isset($tmp_mer['mer_count'][$tmp_time])) {
                $tmp_shop_deliver['deliver_money'][$tmp_time] = 0;
                $tmp_shop_deliver['deliver_count'][$tmp_time] = 0;

            }
            $tmp_shop_deliver['shop_deliver_money'][$tmp_time] += round($vv['freight_charge'], 2);
            $tmp_shop_deliver['shop_deliver_count'][$tmp_time] += 1;
        }


        if (($_POST['day'] == 1 && !$period) || ($period && ($_POST['end_time'] == $_POST['begin_time']))) {
            $day = date('H', time());
            for ($i = 0; $i <= date('H', time()) + 1; $i++) {
                $pigcms_list['xAxis_arr'][] = $i . '时';
                $pigcms_list['xAxis_arr_e'][] = $i . '时';
                $time_arr[] = $i;
            }
        } else {
            if ($_POST['day'] == 2) {
                $day = date('d', time());
                for ($i = 1; $i <= $day; $i++) {
                    $pigcms_list['xAxis_arr'][] = $i . '日';
                    $pigcms_list['xAxis_arr_e'][] = date('Y/m/d', time() - $i * 86400);
                    $time_arr[] = $i;
                }
            } else {
                //$now_day =date('d',$today_zero_time);
                $day = $_POST['day'];
                for ($i = $day - 1; $i >= 0; $i--) {
                    $pigcms_list['xAxis_arr'][] = date('m/d', $today_zero_time - $i * 86400);
                    $pigcms_list['xAxis_arr_e'][] = date('Y/m/d', $today_zero_time - $i * 86400);
                    $time_arr[] = date('ymd', $today_zero_time - $i * 86400);
                }
            }
        }


        if ($period) {
            unset($pigcms_list['xAxis_arr']);
            unset($time_arr);
            $start_day = strtotime($_POST['end_time']);
            $day = (strtotime($_POST['end_time']) - strtotime($_POST['begin_time'])) / 86400;
            if ($day == 0) {
                for ($i = 0; $i < 24; $i++) {
                    $pigcms_list['xAxis_arr'][] = $i . '时';
                    $pigcms_list['xAxis_arr_e'][] = $i . '时';
                    $time_arr[] = $i;
                }
            } else {
                for ($i = $day; $i >= 0; $i--) {
                    $pigcms_list['xAxis_arr'][] = date('m/d', $start_day - $i * 86400) . '';
                    $pigcms_list['xAxis_arr_e'][] = date('Y/m/d', $start_day - $i * 86400) . '';
                    $time_arr[] = date('ymd', $start_day - $i * 86400);
                }
            }
        }


        foreach ($time_arr as $v) {
            $pigcms_list['all_income'][] = round($tmp_array['all_income'][$v], 2);
            $pigcms_list['all_count'][] = floatval($tmp_array['all_count'][$v]);
            $pigcms_list['all_income_all'] += round(floatval($tmp_array['all_income'][$v]), 2);
            $pigcms_list['all_mer_income'][] = round($tmp_mer['mer_income'][$v], 2);
            $pigcms_list['all_mer_income_all'] += round($tmp_mer['mer_income'][$v], 2);
            $pigcms_list['all_mer_count'] [] = floatval($tmp_mer['mer_count'][$v]);
            $pigcms_list['all_mer_system_take'] [] = round($tmp_mer['mer_system_take'][$v], 2);

            foreach ($alias_name as $a) {
                $pigcms_list[$a . '_income'][] = round($tmp_array[$a][$v]['income'], 2);
                $pigcms_list[$a . '_count'][] = floatval($tmp_array[$a][$v]['count']);
                $pigcms_list[$a . '_mer_income'][] = round($tmp_mer['mer_income_by_type'][$a][$v], 2);
                $pigcms_list[$a . '_mer_count'][] = round($tmp_mer['mer_count_by_type'][$a][$v], 2);
                $pigcms_list[$a . '_mer_system_take'][] = round($tmp_mer['mer_system_take_by_type'][$a][$v], 2);
                if ($a == 'shop') {
                    $pigcms_list['shop_deliver_money'][] = round($tmp_shop_deliver['shop_deliver_money'][$v], 2);
                    $pigcms_list['shop_deliver_count'][] = floatval($tmp_shop_deliver['shop_deliver_count'][$v]);

                }
            }
        }


        //数据组装
        $pigcms_list['xAxis_txt'] = $pigcms_list['xAxis_arr'];

        foreach ($alias_name as $n) {
            $pigcms_list[$n]['income_txt'] = $pigcms_list[$n . '_income'];
            $pigcms_list[$n]['count_txt'] = $pigcms_list[$n . '_count'];
            $pigcms_list[$n]['mer_income_txt'] = $pigcms_list[$n . '_mer_income'];
            $pigcms_list[$n]['mer_count_txt'] = $pigcms_list[$n . '_mer_count'];
            $pigcms_list[$n]['mer_system_take_txt'] = $pigcms_list[$n . '_mer_system_take'];
            if ($n == 'shop') {
                $pigcms_list[$n]['deliver_money_txt'] = $pigcms_list['shop_deliver_money'];
                $pigcms_list[$n]['deliver_count_txt'] = $pigcms_list['shop_deliver_count'];
                unset($pigcms_list['shop_deliver_count']);
                unset($pigcms_list['shop_deliver_money']);
            }
            unset($pigcms_list[$n . '_mer_income']);
            unset($pigcms_list[$n . '_income']);
            unset($pigcms_list[$n . '_count']);
            unset($pigcms_list[$n . '_mer_count']);
            unset($pigcms_list[$n . '_mer_system_take']);
        }
        $pigcms_list['all']['income_txt'] = $pigcms_list['all_income'];
        $pigcms_list['all']['mer_income_txt'] = $pigcms_list['all_mer_income'];
        $pigcms_list['all']['count_txt'] = $pigcms_list['all_count'];
        $pigcms_list['all']['mer_count_txt'] = $pigcms_list['all_mer_count'];
        $pigcms_list['all']['mer_system_take_txt'] = $pigcms_list['all_mer_system_take'];
        $pigcms_list['count'] = array('sales_count' => $count, 'consume' => $mer_count);
        $pigcms_list['alias_name'] = $this->get_alias_c_name();
        $pigcms_list['system_take'] = $system_take;
        $pigcms_list['weixin'] = $weixin;
        $pigcms_list['alipay'] = $alipay;
        $pigcms_list['deliver'] = $deliver;


        if (IS_GET) {
            $this->export($pigcms_list, $type, $_POST['day'], $_POST['period']);
        }
        $pigcms_list['pay_type'] = array('weixin' => $weixin_pay, 'alipay' => $alipay_pay);
        unset($pigcms_list['all_income']);
        unset($pigcms_list['all_income']);
        unset($pigcms_list['all_mer_income']);
        unset($pigcms_list['xAxis_arr']);
        $this->ajaxReturn($pigcms_list);
    }


    public function export($result, $type_name, $day, $peroid)
    {
        $type = 'analysis';
        $title = '';
        $alias_name = $this->get_alias_name();
        array_unshift($alias_name, 'all');
        foreach ($alias_name as $kn => $na) {
            if ($na == $type_name) {
                unset($alias_name[$kn]);
                array_unshift($alias_name, $type_name);
                break;
            }
        }

        $alias_c_name = $this->get_alias_c_name();
        if ($day) {
            $p_title = $day . '天内';
        } elseif ($peroid) {
            $p_title = $peroid;
        }
        $title = '数据分析(' . $p_title . ' ' . date("Y-m-d H'i's") . ')';

        $ua = $_SERVER['HTTP_USER_AGENT'];
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua)) { //判断是否为IE或Edge浏览器
            $title = urlencode($title);
            $title = str_replace("(", "%28", $title);
            $title = str_replace("'", "%27", $title);
            $title = str_replace(".", "%29", $title);
            $title = str_replace(" ", urlencode(' '), $title);
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $cell_analysis = array('date' => '日期', 'income_txt' => '订单总金额', 'consume' => '消费量总数', 'sales_count' => '订单量总数', 'income_mer_txt' => '消费总金额', 'mer_system_take_txt' => '抽成总金额', 'deliver_money_txt' => '配送费', 'deliver_count_txt' => '配送单数量');

        //打印条件

        // 设置当前的sheet
        $sheet = 0;

        foreach ($alias_name as $ca) {
            if ($sheet > 0) {
                $objExcel->createSheet();
            }
            $objExcel->setActiveSheetIndex($sheet);
            $objExcel->getActiveSheet()->setTitle($result['alias_name'][$ca] ? $result['alias_name'][$ca] : '');
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->getDefaultRowDimension()->setRowHeight(30);
            $sheet++;
            // 开始填充头部
            $cell_name = 'cell_' . $type;
            $cell_count = count($$cell_name);
            $cell_start = 1;
            $col_char = array();
            for ($f = 'A'; $f <= 'Z'; $f++, $cell_start++) {
                if ($cell_start > $cell_count) {
                    break;
                }
                $col_char[] = $f;
            }
            $col_k = 0;
            foreach ($$cell_name as $key => $v) {
                if ($ca != 'shop' && $ca != 'all' && ($key == 'deliver_money_txt' || $key == 'deliver_count_txt')) {
                    continue;
                }
                $objActSheet->getColumnDimension($col_char[$col_k])->setWidth(20);
                $objActSheet->setCellValue($col_char[$col_k] . '1', $v);
                $col_k++;

            }
            $i = 2;

            foreach ($result['xAxis_arr_e'] as $t => $row) {
                $col_k = 0;
                foreach ($$cell_name as $k => $vv) {
                    if ($ca != 'shop' && $ca != 'all' && ($k == 'deliver_money_txt' || $k == 'deliver_count_txt')) {
                        continue;
                    }
                    switch ($k) {
                        case 'date':
                            $objActSheet->setCellValue($col_char[$col_k] . $i, $row . ' ');
                            break;
                        case 'income_txt':
                            $objActSheet->setCellValue($col_char[$col_k] . $i, round($result[$ca]['income_txt'][$t], 2));
                            break;
                        case 'income_mer_txt':
                            $objActSheet->setCellValue($col_char[$col_k] . $i, round(floatval($result[$ca]['mer_income_txt'][$t]), 2));
                            break;
                        case 'sales_count':
                            $objActSheet->setCellValue($col_char[$col_k] . $i, $result[$ca]['count_txt'][$t] . ' ');
                            break;
                        case 'consume':
                            $objActSheet->setCellValue($col_char[$col_k] . $i, $result[$ca]['mer_count_txt'][$t] . ' ');
                            break;
                        case 'mer_system_take_txt':
                            $objActSheet->setCellValue($col_char[$col_k] . $i, round(floatval($result[$ca]['mer_system_take_txt'][$t]), 2));
                            break;
                        case 'deliver_money_txt':
                            $objActSheet->setCellValue($col_char[$col_k] . $i, round(floatval($result['shop']['deliver_money_txt'][$t]), 2));
                            break;
                        case 'deliver_count_txt':
                            $objActSheet->setCellValue($col_char[$col_k] . $i, $result['shop']['deliver_count_txt'][$t] . ' ');
                            break;

                    }
                    $col_k++;
                }
                $i++;
            }
        }
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();

    }

    public function get_sales_for_rader_chart()
    {
        $alias_name = $this->get_alias_name();
        $alias_c_name = $this->get_alias_c_name();
        $money_list = D('Merchant_money_list');
        $res = $money_list->field('type,count(id) as sales,SUM(money) as money')->where(array('income' => 1))->group('type')->select();
        $money = array();
        $sales = array();
        foreach ($res as $r) {
//            $tmp[$r['type']]['money']=$r['money'];
            $tmp[$r['type']]['sales'] = $r['sales'];
        }
        foreach ($alias_name as $v) {
//            $money[$v] = $tmp[$v]['money']?$tmp[$v]['money']:0;
            $sales[$v] = $tmp[$v]['sales'] ? $tmp[$v]['sales'] : 0;
        }
        //$max = trim(max($money),'"');
        $max = trim(max($sales), '"');
        //$max = $max_money>$max_sales?$max_money:$max_sales ;
        $this->ajaxReturn(array('c_key' => $alias_c_name, 'sales' => $sales, 'max' => $max));
    }

    public function ajax_user()
    {
        $user_count = M('User')->where(array('status'=>['neq',4], '_string'=>'(openid NOT LIKE "%no_use%" OR openid IS NULL)'))->count();
        $weixin_user = M('User')->where(array('openid' => array('neq', ''), 'status'=>['neq',4], '_string'=>'(openid NOT LIKE "%no_use%" OR openid IS NULL)'))->count();
        $app_user = M('User')->where(array('app_openid' => array('neq', ''), 'status'=>['neq',4], '_string'=>'(openid NOT LIKE "%no_use%" OR openid IS NULL)'))->count();
        $phone_user = M('User')->where(array('phone' => array('neq', ''), 'status'=>['neq',4], '_string'=>'(openid NOT LIKE "%no_use%" OR openid IS NULL)'))->count();
        $men_user = M('User')->where(array('sex' => 1, 'status'=>['neq',4], '_string'=>'(openid NOT LIKE "%no_use%" OR openid IS NULL)'))->count();
        $women_user = M('User')->where(array('sex' => 2, 'status'=>['neq',4], '_string'=>'(openid NOT LIKE "%no_use%" OR openid IS NULL)'))->count();
        $unkonw_user = M('User')->where(array('sex' => 0, 'status'=>['neq',4], '_string'=>'(openid NOT LIKE "%no_use%" OR openid IS NULL)'))->count();
        return array(
            'weixin' => $weixin_user,
            'app' => $app_user,
            'phone' => $phone_user,
            'men' => $men_user,
            'women' => $women_user,
            'unknow_user' => $unkonw_user,
            'user_count' => $user_count
        );
    }

    public function ajax_merchant_money($area_id = '')
    {
        if ($area_id) {
            if ($area_id) {
                $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if ($now_area['area_type'] == 3) {
                    $area_index = 'area_id';
                } elseif ($now_area['area_type'] == 2) {
                    $area_index = 'city_id';
                } elseif ($now_area['area_type'] == 1) {
                    $area_index = 'province_id';
                }
            }
            $condition_merchant_request['_string'] = "m.{$area_index} = " . $area_id;
            $all_money = M('')->query('SELECT SUM(power(-1,1+income)*money) AS all_money FROM ' . C('DB_PREFIX') . 'merchant_money_list l inner join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id= l.mer_id where ' . $condition_merchant_request['_string']);
            $all_money = floatval($all_money[0]['all_money']);
            $condition_merchant_request['type'] = array('neq', 'withdraw');
            $all_count = M('Merchant_money_list')->join('as l inner join' . C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id')->where($condition_merchant_request)->count();
            $all_mer_money = M('Merchant')->where("{$area_index} = " . $area_id)->sum('money');

            unset($condition_merchant_request['type']);
            $condition_merchant_request['status'] = 0;
            $all_need_pay = M('Merchant_withdraw')->join('as l inner join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = l.mer_id')->where($condition_merchant_request)->sum('money');

            $condition_merchant_request['l.type'] = 'mer';
            $all_need_pay += M('Withdraw_list')->join('as l inner join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = l.pay_id')->where($condition_merchant_request)->sum('l.money');

        } else {
            $all_money = M('')->query('SELECT SUM(power(-1,1+income)*money) AS all_money FROM ' . C('DB_PREFIX') . 'merchant_money_list ');
            $all_money = floatval($all_money[0]['all_money']);
            $all_count = M('Merchant_money_list')->where(array('type' => array('neq', 'withdraw')))->count();
            $all_mer_money = M('Merchant')->sum('money');
            $all_need_pay = M('Merchant_withdraw')->where(array('status' => 0))->sum('money');
            $all_need_pay_tmp = M('Withdraw_list')->where(array('status' => 0, 'type' => 'mer'))->sum('money');
            $all_need_pay += $all_need_pay_tmp;
        }

        $all_mer_money = $all_mer_money + $all_need_pay / 100;
        return array(
            'all_money' => $all_mer_money > 0 ? $all_mer_money : 0,
            'all_mer_money' => $all_mer_money > 0 ? $all_mer_money : 0,
            'all_need_pay' => $all_need_pay > 0 ? $all_need_pay : 0,
            'all_count' => $all_count > 0 ? $all_count : 0,
        );
    }


    public function pass()
    {
        $this->display();
    }

    public function amend_pass()
    {
        $old_pass = $this->_post('old_pass');
        $new_pass = $this->_post('new_pass');
        $re_pass = $this->_post('re_pass');
        $old_pass=str_replace("amp;","",$old_pass);
        if(strpos($new_pass,'amp;')!==false){
            $new_pass=str_replace("amp;","",$new_pass);
            $re_pass=str_replace("amp;","",$re_pass);
        }

        if ($old_pass == '') {
            $this->error('请填写旧密码！');
        } else if ($new_pass != $re_pass) {
            $this->error('两次新密码填写不一致！');
        } else if ($old_pass == $new_pass) {
            $this->error('新旧密码不能一样！');
        }

        $database_admin = D('Admin');
        $condition_admin['id'] = $this->system_session['id'];
        $admin = $database_admin->field('`id`,`pwd`')->where($condition_admin)->find();
        if ($admin['pwd'] != md5($old_pass)) {
            $this->error('旧密码错误！');
        } else {
            $data_admin['id'] = $admin['id'];
            $data_admin['pwd'] = md5($new_pass);
            if ($database_admin->data($data_admin)->save()) {
                $this->success('密码修改成功！');
            } else {
                $this->error('密码修改失败！请重试。');
            }
        }
    }

    public function profile()
    {
        $database_admin = D('Admin');
        $condition_admin['id'] = $this->system_session['id'];
        $admin = $database_admin->where($condition_admin)->find();
        $sort_menus = explode(';', $admin['sort_menus']);
        $sort_menus_son = array();
        foreach ($sort_menus as &$v) {
            $exp = explode(',', $v);
            $sort_menus_son[$exp[0]] = $exp[1];
        }
        $this->assign('sort_menus_son', $sort_menus_son);
        $this->assign('admin', $admin);
        $this->display();
    }

    public function amend_profile()
    {
        $database_admin = D('Admin');
        $data_admin['id'] = $this->system_session['id'];
        $data_admin['realname'] = $this->_post('realname');
        $data_admin['email'] = $this->_post('email');
        $data_admin['qq'] = $this->_post('qq');
        $data_admin['phone'] = $this->_post('phone');
        $data_admin['sort_menus'] = $this->_post('system_menu');
        $data_admin['phone_country_type'] = $this->_post('phone_country_type');
		$data_admin['phone'] = phone_format($data_admin['phone_country_type'], $data_admin['phone']);
		
        if ($database_admin->data($data_admin)->save()) {
            $this->success('资料修改成功！');
        } else {
            $this->error('资料修改失败！请检查是否有修改内容后再重试。');
        }
    }

    public function cache()
    {
        import('ORG.Util.Dir');
        Dir::delDirnotself('./runtime');
        Dir::delDirnotself('./source/plan/time/');
        Dir::delDirnotself('./source/plan/sub_plan/');
		
		M('')->execute('repair table '.C('DB_PREFIX').'process_sub_plan');
		
		//清空SQL版的S数据
		M('Cache_sql')->where('1=1')->data(['expire' => '1'])->save();
		
		//删除常规日志
		$dir_class = new Dir('./api/log/');
		$dir_class->listFile('./api/log/');
		if($dir_class->_values){
			foreach($dir_class->_values as $value){
				if($value['type'] == 'dir' && preg_match("/^\d*$/",$value['filename']) && $value['filename'] < date('Ymd', time()-15*24*60*60)){
					Dir::delDir('./api/log/' . $value['filename'], false);
				}
			}
		}
		
		//删除开门日志
		$dir_class = new Dir('./api/door_log/');
		$dir_class->listFile('./api/door_log/');
		if($dir_class->_values){
			foreach($dir_class->_values as $value){
				if($value['type'] == 'dir' && preg_match("/^\d*$/",$value['filename']) && $value['filename'] < date('Ymd', time()-15*24*60*60)){
					Dir::delDir('./api/door_log/' . $value['filename'], false);
				}
			}
		}
		
		//删除智能调度日志
		$dir_class = new Dir('./api/smart_deliver_log/');
		$dir_class->listFile('./api/smart_deliver_log/');
		if($dir_class->_values){
			foreach($dir_class->_values as $value){
				if($value['type'] == 'dir' && preg_match("/^\d*$/",$value['filename']) && $value['filename'] < date('Ymd', time()-15*24*60*60)){
					Dir::delDir('./api/smart_deliver_log/' . $value['filename'], false);
				}
			}
		}
		
        $this->frame_main_ok_tips('清除缓存成功！');
    }

    public function menu()
    {
        $this->assign('bg_color', '#F3F3F3');
        $database = D('Admin');
        $condition['id'] = intval($_GET['admin_id']);
        $admin = $database->field(true)->where($condition)->find();
//        if (empty($admin)) {
//            // $this->frame_error_tips('数据库中没有查询到该管理员的信息！');
//        }
        $admin['menus'] = explode(',', $admin['menus']);
        $this->assign('admin', $admin);

        if ($this->system_session['level'] < 2 && $this->system_session['menus']) {
            $menus_in = $this->system_session['menus'];
            $menus = D('System_menu')->where(array('id' => array('in', $menus_in)))->order('fid ASC')->select();
        } else {
            $menus = D('System_menu')->where(array('status' => 1))->order('fid ASC')->select();
        }
        /*设置了权限且设置时间未更新*/
        $fid_menus = [];
        if (!empty($admin['menus'])) {
            foreach ($menus as $k => $v) {

                if ($v['module'] == 'shop' && $v['action'] == '') {
                    if (in_array($v['id'], $admin['menus'])) {
                        $is_shop = 1;
                    } else {
                        $is_shop = 0;
                    }
                    break;
                }
                if ($v['fid']) {
                    $fid_menus[$v['id']] = $v;
                }
            }
            foreach ($menus as $k => $v) {
                if ($is_shop == 1 && ($v['id'] == 293 || $v['fid'] == 293)) {
                    $admin['menus'][] = $v['id'];
                }
                if ($v['fid'] && !isset($fid_menus[$v['id']])) {
                    $fid_menus[$v['id']] = $v;
                }
            }
            $this->assign('admin', $admin);
        }

        $list = array();
        foreach ($menus as $menu) {
            if (empty($menu['fid'])) {
                if (isset($list[$menu['id']])) {
                    $list[$menu['id']] = array_merge($list[$menu['id']], $menu);
                } else {
                    $list[$menu['id']] = $menu;
                }
            } else {
                if ($menu['fid'] && !isset($fid_menus[$menu['id']])) {
                    $fid_menus[$v['id']] = $menu;
                }
                if (isset($list[$menu['fid']])) {
                    $list[$menu['fid']]['lists'][] = $menu;
                } else {
                    if ($fid_menus[$menu['fid']] && $fid_menus[$menu['fid']]['fid']) {
                        $ffid = $fid_menus[$menu['fid']]['fid'];
                        if (isset($list[$ffid])) {
                            $list[$ffid]['lists'][] = $menu;
                        } else {
                            $list[$ffid]['lists'] = array($menu);
                        }
                    } else {
                        $list[$menu['fid']]['lists'] = array($menu);
                    }
                }
            }
        }

        $this->assign('menus', $list);

        $this->display();
    }

    public function savemenu()
    {
        if (IS_POST) {
            $admin_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;
            $menus = isset($_POST['menus']) ? $_POST['menus'] : [];
            if(!in_array('7', $menus)){// 修改密码的权限默认都有
                $menus[] = '7';
            }
            $menus = implode(',', $menus);
            $_SESSION['menus'] = $menus;
            $database = D('Admin');
            $data = array();
            $data['menus'] = $menus;
            $data['root_edit_time'] = time();
            $database->where(array('id' => $admin_id))->save($data);
            $this->success('权限设置成功！');
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function get_area_arr($fid, $level = 2, $tmp = array())
    {
        if ($level > 3) {
            return true;
        }
        $area = M('Area')->where(array('area_pid' => $fid, 'is_open' => 1))->select();
        foreach ($area as $v) {

            $tmp[] = $v['area_id'];
            if ($level < 3) {
                $tmp = $this->get_area_arr($v['area_id'], $v['area_type'] + 1, $tmp);
            }
        }
        return $tmp;
    }

    public function account()
    {
        if ($_GET['area_id'] > 0) {
            $where['a.area_id'] = $_GET['area_id'];
        } else if ($_GET['city_idss'] > 0) {
            $area_arr = $this->get_area_arr($_GET['city_idss'], 3);
            $area_arr = array_merge($area_arr,array($_GET['city_idss']));
            $where['a.area_id'] = array('in', $area_arr);
        } else if ($_GET['province_idss'] > 0) {
            $area_arr = $this->get_area_arr($_GET['province_idss'], 2);
            $area_arr = array_merge($area_arr,array($_GET['province_idss']));
            $where['a.area_id'] = array('in', $area_arr);
        }
        if ($_GET['street_id'] > 0) {
            $where['a.street_id'] = intval($_GET['street_id']);
        }
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'admin_id') {
                $where['a.id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'account') {
                $where['a.account'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'realname') {
                $where['a.realname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $where['a.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        if ($this->system_session['level'] != 2) {
            $where['a.level'] = array('neq', '2');
            if(C('customization.life_tools')){// 城投生活通定制 商家绑定管理员
                $adminIdArr = D('Admin')->getAdminByFid($this->system_session['id']);
                if($adminIdArr){
                    $where['a.id'] = ['in', array_merge($adminIdArr,[$this->system_session['id']])];
                }
            }
        }

        $system_area_id = $this->system_session['area_id'];

        if ($system_area_id) {
            $system_area = M('Area')->where(array('area_id' => $system_area_id))->find();
            $area_arr = $this->get_area_arr($system_area_id, $system_area['area_type'] + 1);
            $where['a.area_id'] = array('in', $area_arr);
        }

// 		import('ORG.Net.IpLocation');
// 		$IpLocation = new IpLocation();

        $count = D('Admin')->join('AS a left join ' . C('DB_PREFIX') . 'area ar ON a.area_id = ar.area_id ')->where($where)->count();
        import('@.ORG.system_page');
        import('@.ORG.system_page');
        $p = new Page($count, 15);

        $admins = D('Admin')->field('a.*,ar.area_name,ar.area_type,ar.area_ip_desc,ar.area_type,st.area_name AS street_name,p.realname AS super_admin')
            ->join('AS a left join ' . C('DB_PREFIX') . 'area ar ON a.area_id = ar.area_id LEFT JOIN '.C('DB_PREFIX').'area_street AS st ON st.area_id=a.street_id')
	    ->join('left join ' . C('DB_PREFIX') . 'admin p ON a.fid = p.id ')
            ->where($where)->limit($p->firstRow, $p->listRows)->select();

        $direct_city = array('1', '21', '42', '62');
        $sprecial_city = array('3235', '3239', '3242', '2792', '1989', '2103', '2873', '566');
        foreach ($admins as &$value) {
            //$last_location = $IpLocation->getlocation(long2ip($value['last_ip']));
            //$value['last_ip_txt'] = iconv('GBK','UTF-8',$last_location['country']);
            switch ($value['level']) {
                case 0:
                    $value['level_name'] = '普通管理员';
                    break;
                case 1:
                    $value['level_name'] = '区域管理员';
                    break;
                case 2:
                    $value['level_name'] = '超级管理员';
                    break;
            }
        }
        $this->assign('system_session', $this->system_session);
        $this->assign('admins', $admins);
        $this->assign('direct_city', $direct_city);
        $this->assign('sprecial_city', $sprecial_city);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function account_del()
    {
        $where['id'] = $_POST['id'];
        $delete = D('Admin')->where($where)->delete();
        if ($delete) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！请重试~');
        }
    }

    public function admin()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $admin = D('Admin')->field(true)->where(array('id' => $id))->find();
        $admin['area_id'] && $admin['area'] = D('Area')->get_parents_area_by_areaid($admin['area_id']);
        $admin['area'] = D('Area')->get_parents_area_by_areaid($admin['area_id']);
        if ($_SESSION['system']['area_id']) {
            $system_area = M('Area')->where(array('area_id' => $_SESSION['system']['area_id']))->find();
            if ($system_area['area_type'] == 3) {
                $this->frame_error_tips('区级管理员无法再添加管理员');
            }
            $_SESSION['system']['area_type'] = $system_area['area_type'];
        }
        $list = M('Authority_group')->where(array('gid' => 0))->select();

        //获取上级管理员
        $where = ['status' => 1];
        $disable = false;
        if (C('customization.life_tools') && $this->system_session['level'] != 2) {
            if ($id > 0 && $this->system_session['id'] == $id) {
                $where['id'] = $this->system_session['fid'];
                $disable = true;
            } else {
                $adminIdArr = D('Admin')->getAdminByFid($this->system_session['id']);
                if ($adminIdArr) {
                    $where['id'] = ['in', array_diff($adminIdArr, [$id])];
                }
            }
        }
        $super_admins = D('Admin')->field('id,IF(realname=\'\',account,realname) AS show_name')->where($where)->select();
        $this->assign('super_admins', $super_admins);
        $this->assign('disable', $disable);

        $this->assign('authority_group', $list);
        $this->assign('admin', $admin);
        $this->assign('system', $_SESSION['system']);
        $this->assign('bg_color', '#F3F3F3');
        $this->display();
    }

    public function saveAdmin()
    {
        if (IS_POST) {
            $database_area = D('Admin');
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $account = htmlspecialchars($_POST['account']);
            if ($database_area->where("`id`<>'{$id}' AND `account`='{$account}'")->find()) {
                $this->error('数据库中已存在相同的账号，请更换。');
            }
            if ($_SESSION['system']['area_type'] == 1 && $_POST['city_idss'] == 0) {
                $this->error('不能添加同级管理员');
            } else if ($_SESSION['system']['area_type'] == 2 && $_POST['area_id'] == 0) {
                $this->error('不能添加同级管理员');
            } else if ($_SESSION['system']['area_type'] == 3) {
                $this->error('区级管理员无法再添加管理员');
            }
            if ($_POST['area_id'] > 0) {
                $_POST['area_id'] = $_POST['area_id'];
            } else if ($_POST['city_idss'] > 0) {
                $_POST['area_id'] = $_POST['city_idss'];
            } else {
                $_POST['area_id'] = $_POST['province_idss'];
            }
            if($_POST['open_admin_area'] && !$_POST['area_id']){
                $this->error('请选择管理员所在区域！');
            }
            !$_POST['open_admin_area'] && $_POST['area_id'] = 0;
            if ($_POST['area_id']) {
                $_POST['level'] = 1;
            } else {
                $_POST['level'] = 0;
            }
            if ($_SESSION['menus']) {
                $_POST['menus'] = $_SESSION['menus'];
                unset($_SESSION['menus']);
            }
            if ($_POST['authority_group_id'] > 0) {
                $group = M('Authority_group')->where(array('id' => $_POST['authority_group_id']))->find();
                $_POST['menus'] = $group['menus'];
            }

            unset($_POST['id']);
            if ($id) {
                if ($_POST['invit_code'] != '' && M('Admin')->where(array('invit_code' => $_POST['invit_code'], 'id' => array('neq', $id)))->find()) {
                    $this->error('该邀请码绑定，请更换。');
                }
                if ($_POST['pwd']) {
                    $_POST['pwd'] = md5($_POST['pwd']);
                    $_POST['update_pwd_time'] = time();
                } else {
                    unset($_POST['pwd']);
                }
                $database_area->where(array('id' => $id))->data($_POST)->save();
                $this->success('修改成功！');
            } else {
                if ($_POST['invit_code'] != '' && M('Admin')->where(array('invit_code' => $_POST['invit_code']))->find()) {
                    $this->error('该邀请码绑定，请更换。');
                }
                if (empty($_POST['pwd'])) {
                    $this->error('密码不能为空~');
                }
                $_POST['pwd'] = md5($_POST['pwd']);
                if ($database_area->data($_POST)->add()) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！请重试~');
                }
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    /* 网站地图 */
    public function sitemap()
    {
        $xmlfilepath = './' . str_replace('.', '_', $_SERVER['HTTP_HOST']) . 'sitemap.xml';
        $this->assign('xmlfilepath', $xmlfilepath);
        $this->display();
    }

    /***执行网站地图*****
     * *<loc>www.example1.com</loc>该页的网址。该值必须少于256个字节(必填项)。格式为<loc>您的url地址</loc>
     * *<lastmod>2010-01-01</lastmod>该文件上次修改的日期(选填项)。格式为<lastmod>年-月-日</lastmod>
     * *<changefreq> always </changefreq>页面可能发生更改的频率(选填项)
     * *有效值为：always、hourly、daily、weekly、monthly、yearly、never
     * *<priority>1.0</priority >此网页的优先级。有效值范围从 0.0 到 1.0 (选填项) 。0.0优先级最低、1.0最高。
     * *
     * */
    public function exeGenerate()
    {
        set_time_limit('100');
        /*         * **寻找网址*** */
        $UrlSetArr = array();
        $siteurl = $this->config['site_url'];
        $siteurl = rtrim($siteurl, '/') . '/';
        $UrlSetArr[] = array('loc' => $siteurl, 'lastmod' => date('Y-m-d'), 'changefreq' => 'always', 'priority' => '1.0');
        /*         * **团购***** */
        $UrlSetArr[] = array('loc' => $siteurl . 'category/all', 'lastmod' => date('Y-m-d'), 'changefreq' => 'always', 'priority' => '0.9');
        $urldatatmp = M('Group_category')->field('cat_id,cat_fid,cat_name,cat_url')->where(array('cat_status' => '1'))->order('cat_id ASC')->select();
        if (!empty($urldatatmp)) {
            foreach ($urldatatmp as $vv) {
                $UrlSetArr[] = array('loc' => $siteurl . 'category/' . $vv['cat_url'], 'lastmod' => date('Y-m-d'), 'changefreq' => 'always', 'priority' => '0.7');
            }
        }

        $jointable = C('DB_PREFIX') . 'merchant';
        $GroupDb = M('Group');
        $GroupDb->join('as grp LEFT JOIN ' . $jointable . ' as mer on grp.mer_id=mer.mer_id');
        $urldatatmp = $GroupDb->field('grp.group_id,grp.mer_id,grp.last_time')->where('grp.status="1" AND mer.status="1"')->order('grp.group_id  DESC')->select();
        if (!empty($urldatatmp)) {
            foreach ($urldatatmp as $vv) {
                $UrlSetArr[] = array('loc' => $siteurl . 'group/' . $vv['group_id'] . '.html', 'lastmod' => !empty($vv['last_time']) ? date('Y-m-d', $vv['last_time']) : date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.9');
            }
        }

        /*         * **订餐***** */
        $UrlSetArr[] = array('loc' => $siteurl . 'meal/all', 'lastmod' => date('Y-m-d'), 'changefreq' => 'always', 'priority' => '0.9');

        $urldatatmp = M('Meal_store_category')->field('cat_id,cat_fid,cat_name,cat_url')->where(array('cat_status' => '1'))->order('cat_id ASC')->select();
        if (!empty($urldatatmp)) {
            foreach ($urldatatmp as $vv) {
                $UrlSetArr[] = array('loc' => $siteurl . 'meal/' . $vv['cat_url'] . '/all', 'lastmod' => date('Y-m-d'), 'changefreq' => 'always', 'priority' => '0.7');
            }
        }
        $urldatatmp = M('Merchant_store')->field('store_id,mer_id')->where(array('have_meal' => '1', 'status' => '1'))->order('store_id ASC')->select();
        if (!empty($urldatatmp)) {
            foreach ($urldatatmp as $vv) {
                $UrlSetArr[] = array('loc' => $siteurl . 'meal/' . $vv['store_id'] . '.html', 'lastmod' => date('Y-m-d'), 'changefreq' => 'always', 'priority' => '0.9');
            }
        }
        /*         * **分类信息***** */
        $UrlSetArr[] = array('loc' => $siteurl . 'classify/', 'lastmod' => date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.9');
        $UrlSetArr[] = array('loc' => $siteurl . 'classify/selectsub.html', 'lastmod' => date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.5');
        $urldatatmp = M('Classify_category')->field('cid,fcid,subdir,updatetime')->where(array('cat_status' => '1'))->select();
        if (!empty($urldatatmp)) {
            foreach ($urldatatmp as $vv) {
                if (($vv['subdir'] == 1) && ($vv['fcid'] == 0)) {
                    $UrlSetArr[] = array('loc' => $siteurl . 'classify/subdirectory-' . $vv['cid'] . '.html', 'lastmod' => !empty($vv['updatetime']) ? date('Y-m-d', $vv['updatetime']) : date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.5');
                } elseif (($vv['subdir'] == 2) && ($vv['fcid'] > 0)) {
                    $UrlSetArr[] = array('loc' => $siteurl . 'classify/list-' . $vv['cid'] . '.html', 'lastmod' => !empty($vv['updatetime']) ? date('Y-m-d', $vv['updatetime']) : date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.8');
                } elseif (($vv['subdir'] == 3) && ($vv['fcid'] > 0)) {
                    $UrlSetArr[] = array('loc' => $siteurl . 'classify/list-' . $vv['fcid'] . '-' . $vv['cid'] . '.html', 'lastmod' => !empty($vv['updatetime']) ? date('Y-m-d', $vv['updatetime']) : date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.8');
                }
            }
        }

        $urldatatmp = M('Classify_userinput')->field('id,cid,addtime')->where(array('status' => '1'))->order('id DESC')->limit(5000)->select();
        if (!empty($urldatatmp)) {
            foreach ($urldatatmp as $vv) {
                $UrlSetArr[] = array('loc' => $siteurl . 'classify/' . $vv['id'] . '.html', 'lastmod' => date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.9');
            }
        }

        /*         * *****商家中心********* */
        $urldatatmp = M('Merchant')->field('mer_id')->where(array('ismain' => 1, 'status' => 1))->select();
        if (!empty($urldatatmp)) {
            foreach ($urldatatmp as $vv) {
                $UrlSetArr[] = array('loc' => $siteurl . 'merindex/' . $vv['mer_id'] . '.html', 'lastmod' => date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.3');
            }
        }
        /*         * ******活动********** */
        $UrlSetArr[] = array('loc' => $siteurl . 'activity/', 'lastmod' => date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.6');
        $urldatatmp = M('Extension_activity_list')->field('pigcms_id')->where(array('status' => '1'))->select();
        if (!empty($urldatatmp)) {
            foreach ($urldatatmp as $vv) {
                $UrlSetArr[] = array('loc' => $siteurl . 'activity/' . $vv['pigcms_id'] . '.html', 'lastmod' => date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.5');
            }
        }
        $this->exeGenerateFile($UrlSetArr);
    }

    private function exeGenerateFile($UrlSetArr)
    {
        if (!empty($UrlSetArr)) {
            $xmlfilepath = './' . str_replace('.', '_', $_SERVER['HTTP_HOST']) . 'sitemap.xml';
            $fp = fopen($xmlfilepath, "wb");
            if ($fp) {
                fwrite($fp, '<?xml version="1.0" encoding="utf-8"?>' . chr(10) . '<urlset>');
                foreach ($UrlSetArr as $uv) {
                    $linestr = chr(10) . '<url>' . chr(10) . '<loc>' . $uv ['loc'] . '</loc>' . chr(10) . '<lastmod>' . $uv['lastmod'] . '</lastmod>' . chr(10) . '<changefreq>' . $uv ['changefreq'] . '</changefreq>' . chr(10) . '<priority>' . $uv['priority'] . '</priority>' . chr(10) . '</url>';
                    fwrite($fp, $linestr);
                }
                fwrite($fp, chr(10) . '</urlset>');
                fclose($fp);
                $this->dexit(array('error' => 0, 'msg' => '生成完成！'));
            } else {
                $this->dexit(array
                ('error' => 1, 'msg' => '网站根目录下' . $xmlfilepath . '文件不可写！'));
            }
        }
        $this->dexit(array('error' => 1, 'msg' => '没有可生成的数据'));
    }

    /*     * json 格式封装函数* */

    private function dexit($data = '')
    {
        if (is_array($data)) {
            echo json_encode($data);
        } else {
            echo $data;
        }
        exit();
    }

    public function ajax_help($group, $module, $action)
    {
        $url = strtolower($group . '_' . $module . '_' . $action);
        $url = 'http://o2o-service.pigcms.com/workorder/serviceAnswerApi.php?url=' . $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $content = curl_exec($ch);
        curl_close($ch);

        echo $content;
    }

    public function help()
    {
        $this->assign('answer_id', $_GET['answer_id']);
        $this->display();
    }

    public function check_account()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $admin = D('Admin')->field(true)->where(array('id' => $id))->find();
        if (empty($admin)) {
            exit(json_encode(array('error_code' => 1, 'msg' => 'no')));
        }
        if ($admin['openid']) {
            exit(json_encode(array('error_code' => 0, 'msg' => 'ok', 'nickname' => $admin['nickname'], 'avatar' => $admin['avatar'])));
        } else {
            exit(json_encode(array('error_code' => 1, 'msg' => 'no')));
        }
    }

    public function cancel_account()
    {
        if ($this->system_session['level'] != 2) exit(json_encode(array('error_code' => 1, 'msg' => '没有权限取消')));
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (D('Admin')->where(array('id' => $id))->save(array('openid' => '', 'avatar' => '', 'nickname' => ''))) {
            exit(json_encode(array('error_code' => 0, 'msg' => 'ok')));
        } else {
            exit(json_encode(array('error_code' => 1, 'msg' => '取消失败')));
        }
    }

    public function authority_group()
    {
        $list = M('Authority_group')->where(array('gid' => 0))->select();
        $this->assign('list', $list);
        $this->display();
    }

    public function authority_add()
    {
        if (IS_POST) {
            if (empty($_POST['name'])) {
                $this->error('名称不能为空');
            }
            $menus = isset($_POST['menus']) ? $_POST['menus'] : '';
            $date['menus'] = implode(',', $menus);
            $date['name'] = $_POST['name'];
            $date['add_time'] = $_SERVER['REQUEST_TIME'];
            if ($_POST['id']) {
                $where_authority_id['authority_group_id'] = $_POST['id'];
                $result = M('Authority_group')->where(array('id' => $_POST['id']))->save($date);
            } else {
                $result = M('Authority_group')->add($date);
                $where_authority_id['authority_group_id'] = $result;
            }
            if ($result) {
                M("Admin")->where($where_authority_id)->setField('menus', $date['menus']);
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $menus = D('System_menu')->where(array('status' => 1))->select();
            $fid_menus = [];
            foreach ($menus as $k => $v) {
                if ($v['fid']) {
                    $fid_menus[$v['id']] = $v;
                }
            }
            foreach ($menus as $k => $v) {
                if ($v['fid'] && !isset($fid_menus[$v['id']])) {
                    $fid_menus[$v['id']] = $v;
                }
            }
            $list = array();
            foreach ($menus as $menu) {
                if (empty($menu['fid'])) {
                    if (isset($list[$menu['id']])) {
                        $list[$menu['id']] = array_merge($list[$menu['id']], $menu);
                    } else {
                        $list[$menu['id']] = $menu;
                    }
                } else {
                    if ($menu['fid'] && !isset($fid_menus[$menu['id']])) {
                        $fid_menus[$v['id']] = $menu;
                    }
                    if (isset($list[$menu['fid']])) {
                        $list[$menu['fid']]['lists'][] = $menu;
                    } else {
                        if ($fid_menus[$menu['fid']] && $fid_menus[$menu['fid']]['fid']) {
                            $ffid = $fid_menus[$menu['fid']]['fid'];
                            if (isset($list[$ffid])) {
                                $list[$ffid]['lists'][] = $menu;
                            } else {
                                $list[$ffid]['lists'] = array($menu);
                            }
                        } else {
                            $list[$menu['fid']]['lists'] = array($menu);
                        }
                    }
                }
            }
            $group = M('Authority_group')->where(array('id' => $_GET['id']))->find();
            $menu = explode(',', $group['menus']);
            $this->assign('group', $group);
            $this->assign('menu', $menu);
            $this->assign('menus', $list);
            $this->display();
        }
    }

    public function loginKeep()
    {

    }

    //业务员流水
    public function salesman_list()
    {

        if (!$this->config['many_city']) {
            redirect(U('admin_money_list'));
        }

        $direct_city = array('1', '21', '42', '62');

        $condition['invit_code'] = array('neq', '');
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'admin_id') {
                $condition['id'] = $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'realname') {
                $condition['realname'] = array('like', "%{$_GET['keyword']}%");
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = $_GET['keyword'];
            }
        }
        if (empty($_GET['pid'])) {
            if ($this->system_session['area_id']) {
                $system_area_id = $this->system_session['area_id'];
                if ($system_area_id) {
                    $system_area = M('Area')->where(array('area_id' => $system_area_id))->find();
                    $area_arr = $this->get_area_arr($system_area_id, $system_area['area_type'] + 1);
                    $condition_area['area_id'] = array('in', $area_arr);
                }
            } else {
                $area_arr = $this->get_area_arr(0, 3);
                $condition_area['area_id'] = array('in', $area_arr);
            }
        }


        if ($_GET['province_idss'] && $this->config['many_city']) {
            // $_GET['pid'] =$_GET['province_idss'];
            $condition_area['area_id'] = $_GET['province_idss'];
            $_GET['type'] = 1;
        }

        if ($_GET['city_idss']) {
            //            $_GET['pid']=$where['city_id']= $_GET['city_idss'];
            $condition_area['area_id'] = $_GET['city_idss'];
            $_GET['type'] = 2;
        }

        if ($_GET['area_id']) {
            //            $_GET['pid'] =$where['area_id']= $_GET['area_id'];
            $condition_area['area_id'] = $_GET['area_id'];
            $_GET['type'] = 3;
        }

        if ($_GET['province_idss'] && $_GET['city_idss'] == 0) {
            if ($_GET['is_city'] == 1) {

                $condition_area['area_pid'] = $_GET['province_idss'];
                $_GET['type'] = 2;
                unset($condition_area['area_id']);
            } else {
                $condition_area['area_id'] = $_GET['province_idss'];
                $_GET['type'] = 1;
                // unset($condition_area['area_id']);
            }
        }

        // }

        $database_area = D('Area');

        $_GET['pid'] && $condition_area['area_pid'] = $_GET['pid'];
//        $tempArea['area_id'] && $condition_area['area_id'] = $tempArea['area_id'];
        $_GET['type'] && $condition_area['area_type'] = $_GET['type'];
        $condition_area['is_open'] = 1;
        if ($condition_area['area_pid'] == $condition_area['area_id']) {
            unset($condition_area['area_id']);
            unset($condition_area['area_type']);
        }

        $now_area = $database_area->field(true)->where(array('area_id' => $_GET['pid']))->find();
        $this->assign('now_area', $now_area);

        if ($_GET['type'] == 4) {
            $order = '`area_sort` DESC,`is_open` DESC,`first_pinyin` ASC';
        } else {
            $order = '`area_sort` DESC,`is_open` DESC,`area_id` ASC';
        }


        $area_list = $database_area->field(true)->where($condition_area)->order($order)->select();
        switch ($area_list[0]['area_type']) {
            case 1:
                $now_type_str = '省份';
                break;
            case 2:
                $now_type_str = '城市';
                break;
            case 3:
                $now_type_str = '区域';
                break;
            default:
                $now_type_str = '商圈';
        }
        $this->assign('now_type_str', $now_type_str);
        $all_money = 0;

        foreach ($area_list as &$vv) {
            if ($vv['area_id'] && $vv['area_type'] < 3) {
                $area_arr = array();
                $area_arr = $this->get_area_arr($vv['area_id'], $vv['area_type'] + 1);
//				if($vv['area_type']==2){
                $area_arr[] = $vv['area_id'];
//				}
                $condition['area_id'] = array('in', $area_arr);
            } else {
                $condition['area_id'] = $vv['area_id'];

            }


            $condition['invit_code'] = array('neq', '');
            $admin_list = M('Admin')->where($condition)->select();

            $vv['spread_count'] = 0;
            $vv['spread_money'] = 0;
            $vv['spread_count'] = count($admin_list);
            $next_area_id = array('area_id' => $vv['area_id']);
            $now_admin = M('Admin')->where($next_area_id)->find();

            if ($vv['spread_count'] > 0 && empty($now_admin)) {
                $vv['admin_name'] = $now_type_str . '管理员';
            } else {
                $vv['admin_name'] = $now_admin['realname'];
            }
//            if(in_array($vv['area_id'],$direct_city)){
//                $next_area_id = $vv['area_id']+1;
//                $where_now_admin['_string']=" area_id = {$vv['area_id']} OR area_id = {$next_area_id}";
//            }else{
//                $next_area_id =array('area_id'=>$vv['area_id']);
//            }

//			$vv['admin_name']=$now_admin['realname'];


            foreach ($admin_list as &$v) {
                $where['invit_code'] = $v['invit_code'];
                $where['status'] = 1;

                // $v['spread_count'] = M('Merchant')->where($where)->count();
                unset($where['status']);
                $where['m.status'] = 1;
                $v['spread_money'] = M('Merchant')->join('as m LEFT JOIN ' . C('DB_PREFIX') . 'merchant_money_list as l ON m.mer_id = l.mer_id')
                    ->where($where)->sum('total_money');

                $vv['spread_money'] += $v['spread_money'];
                $all_money += $v['spread_money'];
                unset($where['m.status']);
            }
        }

        $this->assign('area_list', $area_list);

        if ($_GET['type'] == 4) {
            $circle_category = D('Circle_category')->getField('id,name');
            $this->assign('circle_category', $circle_category);
        }
        if (0 == $this->config['is_open_merchant_foodshop_discount']) {
            $this->assign('merchant_close', '1');
        }
        $this->assign('all_money', $all_money);
        $this->display();
    }

    public function admin_money_list()
    {
        $condition['invit_code'] = array('neq', '');
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'admin_id') {
                $condition['id'] = $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'realname') {
                $condition['realname'] = array('like', "%{$_GET['keyword']}%");
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = $_GET['keyword'];
            }
        }

        if ($_GET['pid']) {
            $system_area = M('Area')->where(array('area_id' => $_GET['pid']))->find();
            $area_arr = $this->get_area_arr($_GET['pid'], $system_area['area_type'] + 1);
            $area_arr[] = $_GET['pid'];
            $condition['area_id'] = array('in', $area_arr);
        }

        $count = M('Admin')->where($condition)->count();
        $condition['a.area_id'] = $condition['area_id'];
        unset($condition['area_id']);
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $direct_city = array('1', '21', '42', '62');
        $sprecial_city = array('3235', '3239', '3242', '2792', '1989', '2103', '2873', '566');
        $this->assign('direct_city', $direct_city);
        $this->assign('sprecial_city', $sprecial_city);
        $admin_list = M('Admin')->field('a.*,ar.area_name,ar.area_type,ar.area_ip_desc')->join('AS a left join ' . C('DB_PREFIX') . 'area ar ON a.area_id = ar.area_id ')->where($condition)->limit($p->firstRow, $p->listRows)->order('id DESC')->select();

        $all_money = 0;
        foreach ($admin_list as &$v) {
            $where['invit_code'] = $v['invit_code'];
            $where['status'] = 1;
            $v['spread_count'] = M('Merchant')->where($where)->count();

            unset($where['status']);
            $where['m.status'] = 1;
            $v['spread_money'] = M('Merchant')->join('as m LEFT JOIN ' . C('DB_PREFIX') . 'merchant_money_list as l ON m.mer_id = l.mer_id')
                ->where($where)->sum('total_money');
            $all_money += $v['spread_money'];
            unset($where['m.status']);
        }

        $this->assign('pagebar', $p->show());
        $this->assign('all_money', $all_money);
        $this->assign('admin_list', $admin_list);
        $this->display();
    }

    //业务员推广商家列表

    public function admin_spread_merchant()
    {
        $invit_code = $_GET['invit_code'];
        $condition = array('invit_code' => $invit_code);
        $condition['status'] = 1;
        $count = M('Merchant')->where($condition)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $merchant_list = M('Merchant')->where($condition)->limit($p->firstRow, $p->listRows)->select();
        $all_money = 0;
        foreach ($merchant_list as &$v) {
            $where['m.mer_id'] = $v['mer_id'];
            $v['spread_money'] = M('Merchant')->join('as m LEFT JOIN ' . C('DB_PREFIX') . 'merchant_money_list as l ON m.mer_id = l.mer_id')
                ->where($where)->sum('total_money');
            $all_money += $v['spread_money'];
        }
        $this->assign('pagebar', $p->show());
        $this->assign('all_money', $all_money);
        $this->assign('merchant_list', $merchant_list);
        $this->display();
    }

    private function allArea($areaId)
    {
        static $allAreas;
        if ($allAreas) {

        } else {
            $condition_area['is_open'] = array('neq', 2);
            $areaList = D('Area')->field(true)->where($condition_area)->order($order)->select();
            $tmpMap = array();
            foreach ($areaList as $area) {
                $tmpMap[$area['area_id']] = $area['area_pid'];
            }
            return array_reverse($this->ids($tmpMap, $areaId));//[父ID，子ID, 孙ID, ..., $sortId（指定的ID）]
        }
    }

    private function ids($idsArray, $areaId, $ids = array())
    {
        $ids[] = $areaId;
        if (isset($idsArray[$areaId]) && $idsArray[$areaId]) {
            return $this->ids($idsArray, $idsArray[$areaId], $ids);
        } else {
            return $ids;
        }
    }

    public function salesman_list_export()
    {
        fdump($_POST);
        $param = $_POST;
        $param['type'] = 'salesman_list';
        $param['rand_number'] = time();
        $param['system_session']['area_id'] = $this->system_session['area_id'];
        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            fdump(M());
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }

    }

    public function admin_money_list_export()
    {
        $param = $_POST;
        $param['type'] = 'admin_money_list';
        $param['rand_number'] = time();

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }

    }

    //操作日志
    public function system_log(){
        import('@.ORG.system_page');
        $where = [];
        $searchtype = I("searchtype");
        $keyword = trim(I("keyword"));
        $begin_time = trim(I("begin_time"));
        $end_time = trim(I("end_time"));
        if($keyword){
            switch ($searchtype) {
                case 'content':
                    $where['l.content'] = ['like', '%'.$keyword.'%'];
                    break;
                case 'admin':
                    $where['a.realname'] = ['eq', $keyword];
                    break;
                case 'dataid':
                    $where['l.data_id'] = ['eq', $keyword];
                    break;
            }
        }
        if($begin_time && $end_time){
            $begin_time = strtotime($begin_time);
            $end_time = strtotime($end_time)+86400;
            $where['l.addtime'] = ['between', [$begin_time, $end_time]];
        }
        $count = D("System_log")->alias("l")->join("left join ".C("DB_PREFIX")."admin as a on a.id=l.admin_id")->where($where)->count();
        $p = new Page($count, 15);
        $data = D("System_log")->alias("l")->join("left join ".C("DB_PREFIX")."admin as a on a.id=l.admin_id")->field("l.*,a.realname")->where($where)->limit($p->firstRow, $p->listRows)->order("l.addtime desc")->select();
        $this->assign('pagebar', $p->show());
        $this->assign('data', $data);
        $this->display();
    }

    //操作日志详情
    public function system_log_detail(){
        $log_id = I("log_id");
        if (empty($log_id)) {
            die("非法操作！");
        }
        $info = D("System_log")->alias("l")->join("left join ".C("DB_PREFIX")."admin as a on a.id=l.admin_id")->field("l.*,a.realname")->where(['l.id' => $log_id])->find();
        $info['post_content'] = $info['post_content'] ? unserialize($info['post_content']) : [];
        $info['before_form'] = $info['before_form'] ? unserialize($info['before_form']) : [];
        $info['after_form'] = $info['after_form'] ? unserialize($info['after_form']) : [];
        $this->assign("info", $info);
        $this->display();
    }


	public function login_log()
	{
		import('@.ORG.system_page');
		$where = [];
		$count = D("Admin_log_extend")->where($where)->count();
		$p = new Page($count, 25);
		$data = D("Admin_log_extend")->where($where)->limit($p->firstRow, $p->listRows)->order("add_time desc")->select();
		$this->assign('pagebar', $p->show());
		$this->assign('data', $data);
		$this->display();
	}

	public function login_log_detail()
	{
		$log_id = I("log_id");
		if (empty($log_id)) {
			die("非法操作！");
		}
		$info = D("Admin_log_extend")->where(['id' => (int)$log_id])->find();
		$this->assign("info", $info);
		$this->display();
	}

	public function update(){
        $list1 = M()->query('ALTER TABLE `pigcms_house_village_info`  ADD `visualization_page_type` tinyint(4) NOT NULL DEFAULT \'0\' COMMENT \'可视化页面类型 0是老版 1是新版\';');
        $list2 = M()->query('ALTER TABLE `pigcms_house_village_info`  ADD `template_top` tinyint(4) NOT NULL DEFAULT \'1\' COMMENT \'顶部模板\';');
        $list3 = M()->query('ALTER TABLE `pigcms_house_village_info`  MODIFY COLUMN `visualization_page_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT \'可视化页面类型 0是老版 1是新版\';');
        $list4 = M()->query('ALTER TABLE `pigcms_house_village_info` MODIFY COLUMN `template_top` tinyint(4) NOT NULL DEFAULT 1 COMMENT \'顶部模板\';');
        $list5 = M()->query('UPDATE `pigcms_house_village_info`  SET `visualization_page_type` = \'0\'  WHERE village_id > 0;');
        $list6 = M()->query('UPDATE `pigcms_house_village_info`  SET `template_top` = \'1\'  WHERE village_id > 0;');
        fdump_api(['操作成功=='.__LINE__,$list1,$list2,$list3,$list4,$list5,$list6],'nav_update',1);
        echo '操作成功';
    }
    
    /**
     * 选择区域
     * @author Nd
     * @date 2022/6/15
     */
    public function check_area()
    {
        $type = $_GET['type']??'';
        $id = $_GET['id']??'';
        $cat_key = $_GET['cat_key']??'';
        switch ($type){
            case 'admin':
                //管理员的指定区域
//            $list = D('Admin_area')->field('area_id as aid')->where(array('aid' => $id))->select();
                $adminInfo = D('Admin')->field('area_ids')->where(array('id' => $id))->find();
                if(!$adminInfo){
                    $this->error('未查询到管理员信息！');
                }
                $list = $adminInfo['area_ids'] ? explode(',',$adminInfo['area_ids']) : [];
                break;
            case 'adver':
                //可视化设置轮播图/4张小图广告列表/中间栏轮播图
                if($cat_key){
                    $condition_adver_category['cat_key'] = $cat_key;
                    $now_category = D('Adver_category')->field(true)->where($condition_adver_category)->find();
                    if(!$now_category){
                        $this->error('分类不存在！');
                    }
                }else{
                    $now_category = $this->adver_check_get_category($_GET['cat_id']);
                }
                
                $condition_adver['id'] = $id;
                $condition_adver['cat_id'] = $now_category['cat_id'];
                $adverInfo = D('Adver')->field('city_id,city_ids')->where($condition_adver)->find();
                if(!$adverInfo){
                    $this->error('信息不存在！');
                }
                $list = $adverInfo['city_ids'] ? explode(',',$adverInfo['city_ids']) : [];
                break;
            case 'slider':
                //可视化设置导航栏导航列表
                if($cat_key){
                    $condition_slider_category['cat_key'] = $cat_key;
                    $now_category = D('Slider_category')->field(true)->where($condition_slider_category)->find();
                    if(!$now_category){
                        $this->error('分类不存在！');
                    }
                }else{
                    $now_category = $this->check_get_category($_GET['cat_id']);
                }
                $condition_adver['id'] = $id;
                $condition_adver['cat_id'] = $now_category['cat_id'];
                $sliderInfo = D('Slider')->field('city_id,city_ids')->where($condition_adver)->find();
                if(!$sliderInfo){
                    $this->error('信息不存在！');
                }
                $list = $sliderInfo['city_ids'] ? explode(',',$sliderInfo['city_ids']) : [];
                break;
            case 'plat_active':
                //可视化设置超值抢购
                $condition['pigcms_id'] = $id;
                $platInfo = D('Plat_buy_active_group')->field('city_id,city_ids')->where($condition)->find();
                if(!$platInfo){
                    $this->error('信息不存在！');
                }
                $list = $platInfo['city_ids'] ? explode(',',$platInfo['city_ids']) : [];
                break;
            case 'plat_five_adver':
                //可视化设置五图广告位
                $condition['pigcms_id'] = $id;
                $platInfo = D('Plat_five_adver_group')->field('city_id,city_ids')->where($condition)->find();
                if(!$platInfo){
                    $this->error('信息不存在！');
                }
                $list = $platInfo['city_ids'] ? explode(',',$platInfo['city_ids']) : [];
                break;
            default :
                $this->error('类型参数有误！');
        }
        $where = array('area_type' => array('lt', 3), 'is_open' => 1);
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(['area_id' => $this->system_session['area_id']])->find();
            if ($now_area['area_type'] == 3) {
                $where['area_id'] = $this->system_session['area_id'];
            } elseif ($now_area['area_type'] == 2) {
                $where['area_id'] = ['IN', [$now_area['area_id'], $now_area['area_pid']]];
            } elseif ($now_area['area_type'] == 1) {
                $where['_string'] = "area_id = {$now_area['area_id']} or area_pid = {$now_area['area_id']}";
            } else {
                $where['area_pid'] = $this->system_session['area_id'];
            }
        }
        //dump($where);exit();
        $areaList = D('Area')->field('area_id, area_pid, area_name')->where($where)->select();
//        $list = D('Shop_discount_area')->field('aid')->where(array('did' => $id))->select();
        $pids = [];
        foreach ($areaList as $item){
            $pids[$item['area_pid']] = $item['area_pid'];
        }
        $pids = array_values($pids);
        $areaParentList = D('Area')->field('area_id, area_pid, area_name')->where(['area_id'=>['in',$pids]])->select();
        $areaList = array_merge($areaList,$areaParentList);
        $areaIds = array();
        foreach ($list as $row) {
            $areaIds[] = $row;
        }

        $tmpMap = array();
        foreach ($areaList as $item) {
            $item['select'] = 0;
            if (in_array($item['area_id'], $areaIds)) {
                $item['select'] = 1;
            }
            $tmpMap[$item['area_id']] = $item;
        }

        $list = array();
        foreach ($areaList as $item) {
            if (isset($tmpMap[$item['area_pid']])) {
                $tmpMap[$item['area_pid']]['son_list'][$item['area_id']] = &$tmpMap[$item['area_id']];
            } else {
                $list[$item['area_id']] = &$tmpMap[$item['area_id']];
            }
        }

        foreach ($list as $key => $value) {
            foreach ($value['son_list'] as $k => $val) {
                foreach($val['son_list'] as $son){
                    if($son['select'] == 1){
                        $list[$key]['son_list'][$k]['select'] = 1;
                        $list[$key]['select'] = 1;
                    }
                }
                if($val['select'] == 1){
                    $list[$key]['select'] = 1;
                }
            }
        }
        $this->assign('type', $type);
        $this->assign('area_list', $list);
        $this->assign('cat_key',$cat_key);
        $this->display();
    }

    protected function check_get_category($cat_id){
        $now_category = $this->get_category($cat_id);
        if(empty($now_category)){
            $this->error_tips('分类不存在！');
        }else{
            return $now_category;
        }
    }

    protected function get_category($cat_id){
        $database_slider_category  = D('Slider_category');
        $condition_slider_category['cat_id'] = $cat_id;
        $now_category = $database_slider_category->field(true)->where($condition_slider_category)->find();
        return $now_category;
    }

    protected function adver_check_get_category($cat_id){
        $now_category = $this->adver_get_category($cat_id);
        if(empty($now_category)){
            $this->error_tips('分类不存在！');
        }else{
            return $now_category;
        }
    }

    protected function adver_get_category($cat_id){
        $database_adver_category  = D('Adver_category');
        $condition_adver_category['cat_id'] = $cat_id;
        $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
        return $now_category;
    }

    /**
     * 保存选择的区域
     */
    public function save_area()
    {
        if(!IS_POST){
            $this->error('传递参数有误！');
        }
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $areaArray = $_POST['areaIds'];
        $type = $_GET['type']??''; //类型
        $cat_key = $_GET['cat_key']??''; //广告类url标示
        $data = array();
        $street_ids = [];
        foreach ($areaArray as $row) {
            $arr = explode('-', $row);
            if (count($arr) == 1) {
                $data[$arr[0]] = 1;
            } elseif (count($arr) == 2) {
                unset($data[$arr[0]]);
                $data[$arr[1]] = 2;
            } elseif (count($arr) == 3) {
                unset($data[$arr[0]], $data[$arr[1]]);
                $data[$arr[2]] = 3;
            } elseif (count($arr) == 4) {
                unset($data[$arr[0]], $data[$arr[1]]);
                $data[$arr[2]] = 3;
                $street_ids[] = $arr[3];
            }
        }
        if (empty($data)) {
            $this->error_tips('请选择区域');
        }
        $areaIds = array();
        foreach ($data as $aid => $level) {
            $areaIds[] = $aid;
        }
        $areaIds = $areaIds ? implode(',',$areaIds) : '';
        switch ($type){
            case 'admin':
                //管理员指定区域
                // 查询区域等级
                $area_list = D('Area')->where(array('area_id'=>array('in',array_keys($data))))->select();
                $area_level_arr = array();
                foreach ($area_list as $key => $value) {
                    $area_level_arr[$value['area_id']] = $value['area_type'];
                }
                D()->startTrans();
                try {
                    D('Admin')->where(array('id' => $id))->save(array('area_ids' => $areaIds));
                    D()->commit();
                }catch (\Exception $exception){
                    D()->rollback();
                    $this->error($exception->getMessage());
                }
                $url = U("account");
//            $adminAreaDB = D('admin_area');
//            //删除已经设置的区域
//            $adminAreaDB->where(array('aid' => $id))->delete();
//            $provinceIds = array();
//            $cityIds = array();
//            $areaIds = array();
//            $areaIds = implode(',',$data);
//            foreach ($data as $aid => $level) {
//                if ($level == 1) {
//                    $provinceIds[] = $aid;
//                } elseif ($level == 2) {
//                    $cityIds[] = $aid;
//                } elseif ($level == 3) {
//                    $areaIds[] = $aid;
//                }
//                $add = $adminAreaDB->add(array('aid' => $id, 'area_id' => $aid, 'area_type' => $area_level_arr[$aid],'create_time'=>time()));
//                if(!$add){
//                    D()->rollback();
//                    $this->error_tips('设置区域失败');
//                }
//            }
                break;
            case 'adver':
                //可视化设置轮播图
                if($cat_key){
                    $condition_adver_category['cat_key'] = $cat_key;
                    $now_category = D('Adver_category')->field(true)->where($condition_adver_category)->find();
                    if(!$now_category){
                        $this->error('分类不存在！');
                    }
                }else{
                    $now_category = $this->adver_check_get_category($_GET['cat_id']);
                }
                
                $condition_adver['id'] = $id;
                $condition_adver['cat_id'] = $now_category['cat_id'];
                $adverInfo = D('Adver')->field('city_id,city_ids')->where($condition_adver)->find();
                if(!$adverInfo){
                    $this->error('信息不存在！');
                }
                D()->startTrans();
                try {
                    D('Adver')->where($condition_adver)->save(['city_ids' => $areaIds]);
                    D()->commit();
                }catch (\Exception $exception){
                    D()->rollback();
                    $this->error($exception->getMessage());
                }
                $url = U("Adver/adver_list",array('cat_key'=>$cat_key,'frame_show'=>true,'from_page'=>'viewpage','frame'=>1,'cat_id'=>$_GET['cat_id']));
                break;
            case 'slider':
                //可视化设置导航栏导航列表/导航栏“更多”图标按钮导航列表
                if($cat_key){
                    $condition_slider_category['cat_key'] = $cat_key;
                    $now_category = D('Slider_category')->field(true)->where($condition_slider_category)->find();
                    if(!$now_category){
                        $this->error('分类不存在！');
                    }
                }else{
                    $now_category = $this->check_get_category($_GET['cat_id']);
                }
                $condition_slider['id'] = $id;
                $condition_slider['cat_id'] = $now_category['cat_id'];
                $sliderInfo = D('Slider')->field('city_id,city_ids')->where($condition_slider)->find();
                if(!$sliderInfo){
                    $this->error('信息不存在！');
                }
                D()->startTrans();
                try {
                    D('Slider')->where($condition_slider)->save(['city_ids' => $areaIds]);
                    D()->commit();
                }catch (\Exception $exception){
                    D()->rollback();
                    $this->error($exception->getMessage());
                }
                $url = U("Slider/slider_list",array('cat_key'=>$cat_key,'frame_show'=>true,'from_page'=>'viewpage','frame'=>1, 'cat_id'=>$_GET['cat_id']));
                break;
            case 'plat_active':
                //可视化设置超值抢购
                $condition['pigcms_id'] = $id;
                $platInfo = D('Plat_buy_active_group')->field('city_id,city_ids')->where($condition)->find();
                if(!$platInfo){
                    $this->error('信息不存在！');
                }
                D()->startTrans();
                try {
                    D('Plat_buy_active_group')->where($condition)->save(['city_ids' => $areaIds]);
                    D()->commit();
                }catch (\Exception $exception){
                    D()->rollback();
                    $this->error($exception->getMessage());
                }
                $url = U("Plat_active/index");
                break;
            case 'plat_five_adver':
                //可视化设置五图广告位
                $condition['pigcms_id'] = $id;
                $platInfo = D('Plat_five_adver_group')->field('city_id,city_ids')->where($condition)->find();
                if(!$platInfo){
                    $this->error('信息不存在！');
                }
                D()->startTrans();
                try {
                    D('Plat_five_adver_group')->where($condition)->save(['city_ids' => $areaIds]);
                    D()->commit();
                }catch (\Exception $exception){
                    D()->rollback();
                    $this->error($exception->getMessage());
                }
                $url = U("Plat_five_adver/index");
                break;
            default :
                $this->error('类型参数有误！');
        }

        $this->success('设置成功',$url);
    }

}

?>