<?php
//景区行程规划
class Scenic_travel_planAction extends BaseAction
{
	private $arrange_start_time;
	private $arrange_end_time;
	private $user_info;
	private $user;
	public function  __construct(){
		parent::__construct();
		$this->arrange_start_time = 28800;//8h
		$this->arrange_end_time = 75600;//21h.
		$this->config['now_city'] = $_POST['scenic_city'];
		$ticket = I('ticket', false);
		if($ticket){
			$info = ticket::get($ticket, $this->DEVICE_ID, true);
			if($info){
			$this->user_info = $info['uid'];
			$this->user = D('User')->field(true)->where(array('uid'=>$info['uid']))->find();
			}
		}else{
			$this->returnCode('20044013');
		}
	}
	/**
	* @return 选取景区列表
	*/
	public function select_scenic(){
		$now_city_id = $this->config['now_city'];
		$now_city = M('Area')->where(array('area_id' => $now_city_id))->find();
		$scenic_list = D('Scenic_list')->get_all_scenic(array('city_id' => $now_city_id));
		$list	=	array();
		if($scenic_list){
			$merchant_image_class = new scenic_image();
			foreach($scenic_list as $v){
				$tmp_pic_arr = explode(';',$v['scenic_pic']);
				$list[]	=	array(
					'scenic_id'	=>	$v['scenic_id'],
					'scenic_title'	=>	$v['scenic_title'],
					'area_name'	=>	$v['area_name'],
					'now_city'	=>	$now_city['area_name'],
					'pic'	=>	$merchant_image_class->get_image_by_path($tmp_pic_arr[0],$this->config['site_url'],'config','1'),
				);
			}
		}else{
			$list	=	array();
		}
		$this->returnCode(0,$list);
	}
	/**
	* @return 搜索景点
	*/
	public function ajax_search_scenic(){
		$now_city_id = $this->config['now_city'];
		$where['scenic_title'] = array('like', '%' . $_POST['keyword'] . '%');
		$where['city_id'] = $now_city_id;
		$scenic_list = D('Scenic_list')->get_all_scenic($where);
		$now_city = M('Area')->where(array('area_id' => $now_city_id))->find();
		if(!empty($scenic_list)){
			$merchant_image_class = new scenic_image();
			foreach($scenic_list as $v){
				$tmp_pic_arr = explode(';',$v['scenic_pic']);
				$list[]	=	array(
					'scenic_id'	=>	$v['scenic_id'],
					'scenic_title'	=>	$v['scenic_title'],
					'area_name'	=>	$v['area_name'],
					'now_city'	=>	$now_city['area_name'],
					'pic'	=>	$merchant_image_class->get_image_by_path($tmp_pic_arr[0],$this->config['site_url'],'config','1'),
				);
			}
			$this->returnCode(0,$list);
		}else{
			$this->returnCode('40000036');
		}
	}
	/**
	* @return 规划详情
	*/
	public function travel_plan_detial(){
		$uid = $this->user_info;
		$plan = M('Scenic_travel_plan')->where(array('id' => $_POST['plan_id'], 'uid' => $uid))->find();
		$spending	=	$plan['money'];
		if(empty($plan)){
			$this->returnCode('40000054');
		}
		import('ORG.Net.Http');
		$http = new Http();
		$plan_detial = M('Scenic_travel_plan_detial')->join('as d left join ' . C('DB_PREFIX') . 'scenic_list as l ON d.scenic_id = l.scenic_id')->where(array('d.fid' => $_POST['plan_id']))->order('id ASC')->select();
		$lat = $_POST['lat'];
		$long = $_POST['long'];
		if($plan['status'] == 2){
			foreach($plan_detial as &$p){
				$url = 'http://api.map.baidu.com/direction/v1?mode=driving&origin=' . $lat . ',' . $long . '&destination='
				. $p['scenic_lat'] . ',' . $p['scenic_long'] . '&origin_region=' . $lat . ',' . $long
				. '&destination_region=' . $p['scenic_lat'] . ','
				. $p['scenic_long'] . '&output=json&ak=' . $this->config['baidu_map_ak'];
				$result = $http->curlGet($url);
				$result = json_decode($result, true);
				$p['transport_time'] = $this->time2second($result['result']['routes'][0]['duration']);
				$p['distance'] = $this->wapFriendRange(getDistance($lat, $long, $p['scenic_lat'], $p['scenic_long']));
				$p['scenic_pic'] = explode(';', str_replace(',', '/', $p['scenic_pic']));
				$p['hotel'] = $this->ajax_hotel_around($p['scenic_lat'], $p['scenic_long']);
				$tmp[$p['day']][] = $p;
				$lat = $p['scenic_lat'];
				$long = $p['scenic_long'];
			}
		}else{
			foreach($plan_detial as $v){
				$v['scenic_pic'] = explode(';', str_replace(',', '/', $v['scenic_pic']));
				$v['distance'] = $this->wapFriendRange($v['distance']);
				$v['hotel'] = $this->ajax_hotel_around($v['scenic_lat'], $v['scenic_long']);
				$v['transport_time'] = $this->time2second($v['transport_time']);
				$tmp[$v['day']][] = $v;
			}
		}
		$tmp_arr	=	array();
		$tmp_arrs	=	array();
		$merchant_image_class = new scenic_image();
		foreach($tmp as $k=>&$vv){
			foreach($vv as &$kk){
				foreach($kk['scenic_pic'] as $ll){
					$kk['pic'][]	=	$merchant_image_class->get_image_by_path($ll,$this->config['site_url'],'config','1');
				}
				$tmp_arr[$k][]	=	$kk['scenic_title'];
				$tmp_arrs[]	=	$kk['scenic_title'];
				$kk['plan_id']	=	$kk['id'];
				$kk['scenic_explain']	=	htmlspecialchars_decode($kk['scenic_explain']);
				unset($kk['id'],$kk['fid'],$kk['uid'],$kk['origin_id'],$kk['start_time'],$kk['end_time'],$kk['play_time'],$kk['transport_type'],$kk['money'],$kk['update_time'],$kk['company_id'],$kk['scenic_brief'],$kk['scenic_name'],$kk['scenic_map_pic'],$kk['scenic_phone'],$kk['scenic_address'],$kk['scenic_explain'],$kk['long'],$kk['lat'],$kk['province_id'],$kk['city_id'],$kk['area_id'],$kk['panorama_map'],$kk['level'],$kk['scenic_status'],$kk['is_broadcast'],$kk['is_parking'],$kk['parking_price'],$kk['is_guide'],$kk['guide_price'],$kk['reply_count'],$kk['score_all'],$kk['score_mean'],$kk['now_money'],$kk['add_time'],$kk['hits'],$kk['sort'],$kk['is_hot'],$kk['spread_rate'],$kk['scenic_pic']);
			}
		}
		$now_city_id = $plan['city_id'];
		$now_city = M('Area')->where(array('area_id' => $now_city_id))->find();
		$tmp = array_values($tmp);
		$tmp_arr = array_values($tmp_arr);
		$arr	=	array(
			'plan_detial'	=>	$tmp,
			'spending'	=>	$spending,
			'scenic_city'	=>	$now_city['area_name'],
			'summary'	=>	$tmp_arr,
			'trip'	=>	$tmp_arrs,
		);
		$this->returnCode(0,$arr);
	}
    /**
     * @return ajax得到附近的酒店
     */
    public function ajax_hotel_around($lat, $long)
    {
        $where['g.cat_fid'] = 2;
        $where['s.store_id'] = array('neq', '');
        $where['m.store_id'] = array('neq', '');
        $where['g.status'] = 1;
        $where['g.end_time'] = array('gt',$_SERVER['REQUEST_TIME']);
        $nember_6 = 6378.138 * 2;
        $lat_pi = $long * pi() / 180;
        $lnt_pi = $lat * pi() / 180;
        $fields = "g.group_id,g.name,g.intro,g.price,m.lat,m.long,ROUND({$nember_6}* ASIN(SQRT(POW(SIN(({$lat_pi}-`m`.`lat`*PI()/180)/2),2)+COS({$lat_pi})*COS(`m`.`lat`*PI()/180)*POW(SIN(({$lnt_pi}-`m`.`long`*PI()/180)/2),2)))*1000) AS distance";
        $group = M('Group')->join('as g left join pigcms_group_store s ON s.group_id = g.group_id left join ' . C('DB_PREFIX') . 'merchant_store m ON s.store_id=m.store_id')->field($fields)->where($where)->order('distance ASC')->limit(3)->select();

        return $group;
    }

    /**
     * @return 行程管理
     */
    public function manager_travel()
    {
        $uid = $_SESSION['user']['uid'];
        $now_city_id = $this->config['now_city'];
        $plan = M('Scenic_travel_plan')->field('p.*,a.area_name as city_name')->join('as p left join ' . C('DB_PREFIX') . 'area a ON a.area_id = p.city_id')->where(array('p.uid' => $uid))->order('p.id DESC')->select();
        $scenic_list = M('Scenic_list')->getField('scenic_id,scenic_title,scenic_pic');
        foreach ($plan as &$v) {
            $travel_detial = M('Scenic_travel_plan_detial')->where(array('fid' => $v['id']))->select();
            foreach ($travel_detial as $t) {
                $v['scenic_list'] .= $scenic_list[$t['scenic_id']]['scenic_title'] . ' - ';
            }
            $v['scenic_list'] = substr($v['scenic_list'], 0, -3);
            if (empty($v['scenic_pic'])) {
                $tmp = explode(';', str_replace(',', '/', $scenic_list[$travel_detial[0]['scenic_id']]['scenic_pic']));
                $v['scenic_pic'] = $tmp[0];
            }
        }
        $this->assign('plan_detial', $plan);
        $this->display();
    }

    /**
     * @return 删除行程
     */
    public function del_travel()
    {
        $id = $_POST['id'];
        M('Scenic_travel_plan')->where(array('id' => $id))->delete();
        M('Scenic_travel_plan_detial')->where(array('fid' => $id))->delete();
        $this->ajaxReturn(array('error_code' => 0, 'msg' => L_('删除成功')));
        exit;
    }

    /**
     * @return 更新游玩状态
     */
    public function ajax_update_travel()
    {
        if($_POST['travel_id']) {
            $where['id'] = $_POST['travel_id'];
            M('Scenic_travel_plan_detial')->where($where)->setField('status', 1);
        }
        $ids['status'] = 1;
        $ids['fid'] = $_POST['fid'];
        $count_traveled = M('Scenic_travel_plan_detial')->where($ids)->count();
        $where_['id'] = $_POST['fid'];
        $plan = M('Scenic_travel_plan')->where($where_)->find();
        if($_POST['status']&&$_POST['status']==3){
            M('Scenic_travel_plan')->where($where_)->setField('status', 3);//开始游玩
        }elseif ($plan['scenic_num'] == $count_traveled) {
            M('Scenic_travel_plan')->where($where_)->setField('status', 1);//已经有部分玩过了
        } else {
            M('Scenic_travel_plan')->where($where_)->setField('status', 2);//已经全部玩过了
        }
        $this->ajaxReturn(array('error_code' => 0, 'msg' => L_('更新成功')));
        exit;
    }

    /**
     * @return 显示地图导航
     */
    public function map()
    {
        $this->display();
    }

    /**
     * @return 安排行程
     */
    public function arrange_travel()
    {
        $this->arrange_compute();
    }

    /**
     * @return 按挑选的景点进行行程规划
     */
    public function arrange_compute()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
        $long = $long_lat['long'];
        $lat = $long_lat['lat'];
        $uid = $_SESSION['user']['uid'];
        $now_city_id = $this->config['now_city'];
        //$scenic_id_list = '1,5,6,21,22,23';
        if (!empty($_POST['scenic_id_list'])) {
            $scenic_id_list = $_POST['scenic_id_list'];
            $where['scenic_id'] = array('in', $scenic_id_list);
        }
        $where['city_id'] = $now_city_id;
        $scenic_list = M('Scenic_list')->where($where)
            ->getField('scenic_id,lat,long,sugest_time,start_time,end_time,money');

        $min_distance = 0; //start_distances
        import('ORG.Net.Http');
        $http = new Http();
        $all_money = 0;
        foreach ($scenic_list as $d) {
            $distance = getDistance($lat, $long, $d['lat'], $d['long']);
            //$all_money += $d['money'];
            if ($min_distance > $distance || $min_distance == 0) {
                $min_distance = $distance;
                $start_scenic = $d;
                $start_id = $d['scenic_id'];
            }
        }

        $transport_time_arr = array();//景点到景点间的距离和驾车时间
        foreach ($scenic_list as $v) {
            foreach ($scenic_list as $vv) {
                if ($v['scenic_id'] != $vv['scenic_id'] && !$transport_time_arr[$v['scenic_id']][$vv['scenic_id']]) {
                    $url = 'http://api.map.baidu.com/direction/v1?mode=driving&origin=' . $v['lat'] . ','
                        . $v['long'] . '&destination=' . $vv['lat'] . ',' . $vv['long'] . '&origin_region='
                        . $v['lat'] . ',' . $v['long'] . '&destination_region=' . $vv['lat'] . ','
                        . $vv['long'] . '&output=json&ak=' . $this->config['baidu_map_ak'];
                    $result = $http->curlGet($url);
                    $result = json_decode($result, true);

                    $transport_time_arr[$v['scenic_id']][$vv['scenic_id']]['distance'] = getDistance($v['lat'], $v['long'], $vv['lat'], $vv['long']);
                    //$tmp[$v['scenic_id']][$vv['scenic_id']]['distance']= $result['result']['routes'][0]['distance'];
                    $transport_time_arr[$v['scenic_id']][$vv['scenic_id']]['time'] = $result['result']['routes'][0]['duration'];
                    $transport_time_arr[$v['scenic_id']][$vv['scenic_id']]['money'] = $vv['money'];
                }
            }
        }

        $url = 'http://api.map.baidu.com/direction/v1?mode=driving&origin=' . $lat . ',' . $long . '&destination='
            . $start_scenic['lat'] . ',' . $start_scenic['long'] . '&origin_region=' . $lat . ',' . $long
            . '&destination_region=' . $start_scenic['lat'] . ','
            . $start_scenic['long'] . '&output=json&ak=' . $this->config['baidu_map_ak'];
        $result = $http->curlGet($url);
        $result = json_decode($result, true);
        $here_to_start_scenic_time = $result['result']['routes'][0]['duration'];

        $return = $this->get_route_queue($scenic_list, $transport_time_arr, count($transport_time_arr), $start_id, $here_to_start_scenic_time + $scenic_list['start_id']['sugest_time'] * 3600, $_POST['day'], $_POST['total_money'] / $_POST['people_num']);//已知各个景点间的距离跟交通时间
        //array_unshift($return[1],array(1=>$start_id));//将起点并入数组
        if (empty($return[1])) {
            $return[1] = array($start_id);
        } else {
            array_unshift($return[1], (int)$start_id);//将起点并入数组
        }
        ksort($return);
        $data['uid'] = $uid;
        $data['city_id'] = $now_city_id;
        $data['scenic_num'] = count($scenic_list);
        $day_ = end(array_keys($return));
        $data['day'] = $day_;
        $data['people_num'] = 1;
        $data['money'] = 0;
        $data['type'] = 1;
        $data['add_time'] = $_SERVER['REQUEST_TIME'];
        $data['update_time'] = $_SERVER['REQUEST_TIME'];
        if ($fid = M('Scenic_travel_plan')->add($data)) {
            $tmp_key = 0;
            $arr_in = array();
            foreach ($return as $kr => $r) {
                foreach ($r as $krr => $rr) {
                    if ($kr == 1 && $krr == 0) {
                        $origin_id = 0;
                        $transport_time = $here_to_start_scenic_time;
                        $distance = $min_distance;
                        $origin_lat = $lat;
                        $origin_long = $long;
                    } elseif ($krr == 0) {
                        $origin_id = $tmp_key;
                        if ($rr == $tmp_key) {
                            $transport_time = 0;
                            $distance = 0;
                        } else {
                            $distance = $transport_time_arr[$origin_id][$rr]['distance'];
                            $transport_time = $transport_time_arr[$origin_id][$rr]['time'];
                        }
                        $origin_lat = $scenic_list[$tmp_key]['lat'];
                        $origin_long = $scenic_list[$tmp_key]['long'];
                    } else {
                        $origin_id = $r[$krr - 1];
                        $transport_time = $transport_time_arr[$origin_id][$rr]['time'];
                        $distance = $transport_time_arr[$origin_id][$rr]['distance'];
                        $origin_lat = $scenic_list[$origin_id]['lat'];
                        $origin_long = $scenic_list[$origin_id]['long'];
                    }
                    $tmp_key = $rr;
                    //if(!in_array($rr,$arr_in)) {
                   // $arr_in[] = $rr;
                        $data_detial[] = array(
                            'fid' => $fid,
                            'uid' => $uid,
                            'day' => $kr,
                            'origin_id' => $origin_id,
                            'scenic_id' => $rr,
                            'origin_lat' => $origin_lat,
                            'origin_long' => $origin_long,
                            'scenic_lat' => $scenic_list[$rr]['lat'],
                            'scenic_long' => $scenic_list[$rr]['long'],
                            'play_time' => $scenic_list[$rr]['sugest_time'],
                            'distance' => $distance,
                            'transport_type' => 1,
                            'transport_time' => $transport_time,
                            'money' => $scenic_list[$rr]['money'],
                        );
                        $all_money += $scenic_list[$rr]['money'];
                    //}
                }
            }
            M('Scenic_travel_plan')->where(array('id' => $fid))->setField('money', $all_money);
            if (M('Scenic_travel_plan_detial')->addAll($data_detial)) {
                $this->ajaxReturn(array('error_code' => 0, 'msg' => L_('规划成功'), 'id' => $fid));
                exit;
            } else {
                $this->ajaxReturn(array('error_code' => 1, 'msg' => L_('规划失败')));
                exit;
            }
        } else {
            $this->ajaxReturn(array('error_code' => 1, 'msg' => L_('规划失败')));
            exit;
        }
    }

    /**
     * @return 递归得到景点顺序
     */
    public function get_route_queue($scenic_list, $arr, $count, $start_id, $use_time, $limit_day = '', $limit_money = ''
        , $arr_in = array(), $queue = array(), $day = 1, $num = 0, $money = 0)
    {
        if ($day >= $limit_day && $limit_day != '') {
            return $queue;
        }

        foreach ($arr as $key => $v) {
            if ($key == $start_id && !in_array($start_id, $arr_in)) {
                $arr_in[] = (int)$start_id;
                $tmp = sortArrayAsc($v, 'distance');
                //$tmp_ = sortArrayAsc($v, 'money');
                foreach ($tmp as $vv) {
                    if (!in_array($vv['array_key'], $queue[$day]) && ($money <= $limit_money || empty($limit_money)) && ($day <= $limit_day || empty($limit_day))) {
                        if ($scenic_list[$vv['array_key']]['end_time'] == '00:00:00') {
                            $scenic_list[$vv['array_key']]['end_time'] = '24:00:00';
                        }
                        $tmp_use_time = $arr[$key][$vv['array_key']]['time'] + $scenic_list[$vv['array_key']]['sugest_time'] * 3600;
                        $time = $this->arrange_start_time + $use_time + $tmp_use_time;
                        if (($time > $scenic_list[$vv['array_key']]['end_time'] * 3600 || $time > $this->arrange_end_time)) {
                            if (($scenic_list[$vv['array_key']]['money'] + $money) >= $limit_money && !empty($limit_money)) {
                                continue;
                            }
                            if ($scenic_list[$vv['array_key']]['sugest_time'] / 24 >= 1) {
                                $loop = ceil($scenic_list[$vv['array_key']]['sugest_time'] / 24);
                                for ($i = 0; $i < $loop; $i++) {
                                    $day++;
                                    if ($day > $limit_day && !empty($limit_day)) {
                                        break;
                                    }
                                    $queue[$day][] = $vv['array_key'];
                                    if ($i == 1) {
                                        $money += $scenic_list[$vv['array_key']]['money'];
                                    }
                                }
                            } else {
                                $day++;
                            }
                            $use_time = 0;
                            $this->get_route_queue($scenic_list, $arr, $vv['array_key'], $use_time, $limit_day, $limit_money, $arr_in, $queue, $day, $num);
                        } else {
                            $use_time += $tmp_use_time;
                            $queue[$day][] = $vv['array_key'];
                            $money += $scenic_list[$v['array_key']]['money'];

                        }
                        $num++;
                        if ($num == $count || ($day >= $limit_day && !empty($limit_day)) || ($money >= $limit_money && !empty($limit_money))) {
                            break;
                        } else {
                            $this->get_route_queue($scenic_list, $arr, $vv['array_key'], $use_time, $limit_day, $limit_money, $arr_in, $queue, $day, $num);
                        }
                    }
                }
            }
        }
        return $queue;
    }

    /**
     * @return 根据用户的时间，金钱规划路线
     */
    public function arrange_travel_rand()
    {
        $now_city_id = $this->config['now_city'];
        $now_city = M('Area')->where(array('area_id' => $now_city_id))->find();
        if (IS_POST) {
            if (empty($_POST['start_time'])) {
                $this->ajaxReturn(array('error_code' => true, 'msg' => L_('没有选择开始日期')));
                exit;
            }
            if (!is_numeric($_POST['day']) || $_POST['day'] < 0) {
                $this->ajaxReturn(array('error_code' => true, 'msg' => L_('天数填写有误')));
                exit;
            }
            if (!is_numeric($_POST['total_money']) || $_POST['total_money'] < 0) {
                $this->ajaxReturn(array('error_code' => true, 'msg' => L_('预算填写有误')));
                exit;
            }
            if (!is_numeric($_POST['people_num']) || $_POST['people_num'] < 1 || floor($_POST['people_num']) != $_POST['people_num']) {
                $this->ajaxReturn(array('error_code' => true, 'msg' => L_('人数填写有误')));
                exit;
            }
            $start_time = $_POST['start_time'];
            $day = $_POST['day'];
            $driving = $_POST['driving'];//bool
            $total_money = $_POST['total_money'];//预算
            $people_num = $_POST['people_num'];
            $xz = $_POST['xz'];
            $this->arrange_compute();
        } else {
            $this->assign('now_city', $now_city);
            $this->display();
        }
    }

    /**
     * @return 秒转天时分
     */
    function time2second($seconds)
    {
        $seconds = (int)$seconds;
        if ($seconds < 86400) {
            $time = explode(':', gmstrftime('%H:%M', $seconds));
            if ($time[0] != '00') {
                $format_time = $time[0] . L_('时') . $time[1] . L_(L_('分'));
            } else {
                $format_time = $time[1] . '分';
            }
        } else {
            $time = explode(' ', gmstrftime('%j %H %M', $seconds));
            $format_time = ($time[0] - 1) . L_('天') . $time[1] . L_('时') . $time[2] . L_('分');
        }
        return $format_time;
    }
}

?>