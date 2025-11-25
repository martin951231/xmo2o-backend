<?php
/**
 * 业务员管理
 * @author lumin
 * 
 */
class BdAction extends BaseAction{

	//业务经理列表
	public function bd_list(){
		if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                $area_index = 'area_id';
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $this->assign('admin_area',$now_area['area_type']);
            $condition_merchant['m.'.$area_index] = $this->system_session['area_id'];
        }

        if($_GET['province_idss']){
            $condition_merchant['m.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $condition_merchant['m.city_id'] = $_GET['city_idss'];
        }
        if($_GET['area_id']){
            $condition_merchant['m.area_id'] = $_GET['area_id'];
        }
        $condition_merchant['m.is_del'] = 0;
        if($_GET['keyword']){
            $condition_merchant['m.bd_name'] = ['like', '%'.trim($_GET['keyword']).'%'];
        }

        $sort = I("sort") ? I("sort") : 'addtime';
        $orderby = 'm.addtime desc';
        if($sort == 'all_money_desc'){
        	$orderby = 'm.all_money desc';
        	$this->assign('sort', 'all_money_asc');
        }
        elseif($sort == 'all_money_asc'){
        	$orderby = 'm.all_money asc';
        	$this->assign('sort', 'all_money_desc');
        }
        else{
        	$this->assign('sort', 'all_money_desc');
        }
        $condition_merchant['m.type'] = 2;

        import('@.ORG.system_page');

        $count = M('Bd')->alias('m')->where($condition_merchant)->count();
        $p = new Page($count, 20);
        $data = M('Bd')->alias('m')->where($condition_merchant)->limit($p->firstRow, $p->listRows)->order($orderby)->select();
        
        $data = $this->deal_bd_list($data);

        $this->assign('pagebar', $p->show());
        $this->assign('data', $data);
        $this->display();
	}

	public function deal_bd_list($data){
		$areaids = array_merge(array_column($data, 'province_id'),array_column($data, 'city_id'),array_column($data, 'area_id'));
		$areas = array_column(M("Area")->where(['area_id'=>['in',$areaids]])->field("area_id, area_name")->select(), 'area_name','area_id');
		foreach ($data as $key => $value) {
			$data[$key]['area_name'] = '';
			if($value['area_id']){
				$data[$key]['area_name'] = $areas[$value['area_id']] ?? '';
			}
			elseif($value['city_id']){
				$data[$key]['area_name'] = $areas[$value['city_id']] ?? '';
			}
			elseif($value['province_id']){
				$data[$key]['area_name'] = $areas[$value['province_id']] ?? '';
			}
			else{
				$data[$key]['area_name'] = '全国';
			}
			$data[$key]['son_nums'] = $value['type'] == '2' ? M("Bd")->where(['is_del'=>0,'pbd_id'=>$value['bd_id']])->count() : 0;
			$data[$key]['status_txt'] = $value['status'] == '1' ? '开启' : '关闭';
			$data[$key]['recom_merchant_nums'] = M("Merchant")->where(['bd_id'=>$value['bd_id']])->count();//我下级的商家
			$sons = array_column(M("Bd")->where(['pbd_id'=>$value['bd_id'], 'is_del'=>0])->field("bd_id")->select(),'bd_id');
			$data[$key]['son_recom_merchant_nums'] = M("Merchant")->where(['bd_id'=>['in', $sons]])->count() ?: 0;
			$data[$key]['pbd_name'] = M("Bd")->where(['bd_id'=>$value['pbd_id']])->getField("bd_name");
		}
		return $data;
	}

	public function add(){
		$pbd_point_id = I("pbd_point_id");
		$this->assign("pbd_point_id", $pbd_point_id);
		if($pbd_point_id){
			$pbd_info = M("Bd")->where(['bd_id'=>$pbd_point_id])->find();
			$this->assign('pbd_info', $pbd_info);
		}
		if(IS_POST){
			$user_phone = $_POST['user_phone'];
			$user = M("User")->where(['phone' => $user_phone, 'uid' => $user_phone, '_logic' => 'or'])->field("uid")->find();
			if(!$user){
				$this->error("未匹配到用户，请检查输入是否正确！");
			}
			if($r = M("Bd")->where(['uid'=>$user['uid'], 'is_del'=>0])->find()){
				$this->error("当前用户已经是".($r['type'] == '2' ? '业务经理' : '业务员')."了！");	
			}
			$_POST['uid'] = $user['uid'];
			$_POST['addtime'] = date('Y-m-d H:i:s');
			$_POST['is_del'] = 0;
			$_POST['invitation_code'] = $this->get_invitation_code($user['uid']);
			if($this->system_session['area_id']){
				$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
				if($now_area['area_type']==3){
                    $_POST['area_id'] = $now_area['area_id'];
                    $now_city = D('Area')->field(true)->where(array('area_id' => $now_area['area_pid']))->find();
                    $_POST['city_id'] = $now_city['area_id'] ?? 0;
                    $_POST['province_id'] = $now_city['area_pid'] ?? 0;
                }elseif($now_area['area_type']==2){
                    $_POST['area_id'] = 0;
                    $_POST['city_id'] = $now_area['area_id'] ?? 0;
                    $_POST['province_id'] = $now_area['area_pid'] ?? 0;
                }elseif($now_area['area_type']==1){
                    $_POST['area_id'] = 0;
                    $_POST['city_id'] = 0;
                    $_POST['province_id'] = $now_area['area_id'];
                }
			}
			else{
				$_POST['area_id'] = 0;
                $_POST['city_id'] = 0;
                $_POST['province_id'] = 0;
			}
			if(M("Bd")->data($_POST)->add()){
				$this->success("添加成功！");
			}
			else{
				$this->error("添加失败！");	
			}
		}
		if($_GET['type'] == '1'){
            $_where = ['is_del'=>0, 'status'=>1, 'type'=>2];
            if($this->system_session['area_id']){
                $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if($now_area['area_type']==3){
                    $_where['area_id'] = $now_area['area_id'];
                }elseif($now_area['area_type']==2){
                    $_where['city_id'] = $now_area['area_id'];
                }elseif($now_area['area_type']==1){
                    $_where['province_id'] = $now_area['area_id'];
                }
            }
			$pbd = M("Bd")->where($_where)->select();
			$this->assign('pbd',$pbd);
		}
		$this->assign('customization',C('customization'));
		$this->display();	
	}

	public function edit(){
		$bd_id = I("bd_id");

		if(IS_POST){
			$user_phone = $_POST['user_phone'];
			$user = M("User")->where(['phone' => $user_phone, 'uid' => $user_phone, '_logic' => 'or'])->field("uid")->find();
			if(!$user){
				$this->error("未匹配到用户，请检查输入是否正确！");
			}
			if($r = M("Bd")->where(['uid'=>$user['uid'], 'is_del'=>0, 'bd_id' => ['neq', $bd_id]])->find()){
				$this->error("当前用户已经是".($r['type'] == '2' ? '业务经理' : '业务员')."了！");  
			}
			$_POST['uid'] = $user['uid'];
			// if($this->system_session['area_id']){
			// 	$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			// 	if($now_area['area_type']==3){
   //                  $_POST['area_id'] = $now_area['area_id'];
   //                  $now_city = D('Area')->field(true)->where(array('area_id' => $now_area['area_pid']))->find();
   //                  $_POST['city_id'] = $now_city['area_id'] ?? 0;
   //                  $_POST['province_id'] = $now_province['area_pid'] ?? 0;
   //              }elseif($now_area['area_type']==2){
   //                  $_POST['area_id'] = 0;
   //                  $_POST['city_id'] = $now_area['area_id'] ?? 0;
   //                  $_POST['province_id'] = $now_area['area_pid'] ?? 0;
   //              }elseif($now_area['area_type']==1){
   //                  $_POST['area_id'] = 0;
   //                  $_POST['city_id'] = 0;
   //                  $_POST['province_id'] = $now_area['area_id'];
   //              }
			// }
			// else{
			// 	$_POST['area_id'] = 0;
   //              $_POST['city_id'] = 0;
   //              $_POST['province_id'] = 0;
			// }
			if(M("Bd")->data($_POST)->where(['bd_id'=>$bd_id])->save() !== false){
				$this->success("修改成功！");
			}
			else{
				$this->error("修改失败！");	
			}
		}
		$bd_info = M("Bd")->where(['bd_id'=>$bd_id])->find();
		$this->assign("bd_info",$bd_info);

		if($bd_info['type'] == '1'){
			$pbd = M("Bd")->where(['is_del'=>0, 'status'=>1, 'type'=>2])->select();
			$this->assign('pbd',$pbd);
		}
        $this->assign('user', D('User')->get_user($bd_info['uid']));
		$this->assign('customization',C('customization'));
		$this->display();
	}


	public function get_invitation_code($uid){
		$chars = [
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
		];
		$len = 4;
		$code = '';
		for($i = 0; $i < $len; $i++){
			$rand = rand(0,count($chars)-1);
			$code .= $chars[$rand];
		}
		return $code.$uid;
	}

	//业务经理列表
	public function bd_son_list(){
		$pbd_id = I("pbd_id");
		if($pbd_id){
			$pbd_info = M("Bd")->where(['bd_id'=>$pbd_id])->find();
			$this->assign('pbd_info', $pbd_info);
			$condition_merchant['m.pbd_id'] = $pbd_id;
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
            $this->assign('admin_area',$now_area['area_type']);
            $condition_merchant['m.'.$area_index] = $this->system_session['area_id'];
        }

        if($_GET['province_idss']){
            $condition_merchant['m.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $condition_merchant['m.city_id'] = $_GET['city_idss'];
        }
        if($_GET['area_id']){
            $condition_merchant['m.area_id'] = $_GET['area_id'];
        }
        $condition_merchant['m.is_del'] = 0;
        if($_GET['keyword']){
            $condition_merchant['m.bd_name'] = ['like', '%'.trim($_GET['keyword']).'%'];
        }

        $sort = I("sort") ? I("sort") : 'addtime';
        $orderby = 'm.addtime desc';
        if($sort == 'all_money_desc'){
        	$orderby = 'm.all_money desc';
        	$this->assign('sort', 'all_money_asc');
        }
        elseif($sort == 'all_money_asc'){
        	$orderby = 'm.all_money asc';
        	$this->assign('sort', 'all_money_desc');
        }
        else{
        	$this->assign('sort', 'all_money_desc');
        }
        $condition_merchant['m.type'] = 1;

        import('@.ORG.system_page');

        $count = M('Bd')->alias('m')->where($condition_merchant)->count();
        $p = new Page($count, 20);
        $data = M('Bd')->alias('m')->where($condition_merchant)->limit($p->firstRow, $p->listRows)->order($orderby)->select();
        
        $data = $this->deal_bd_list($data);

        $this->assign('pagebar', $p->show());
        $this->assign('data', $data);
        $this->display();
	}

	//业务员佣金记录
	public function commission_record(){
		$bd_id = intval(I("bd_id"));
		$pbd_id = intval(I("pbd_id"));
		$export = intval(I("export", 0));
		$where = [
			'a.is_del' => 0
		];
		if($bd_id){
			$bd_info = M("Bd")->where(['bd_id'=>$bd_id])->find();
			$title = ($bd_info['type'] == 2 ? "业务经理--" : "业务员--"). $bd_info['bd_name']."的";
            $where['a.type'] = 0;
			$this->assign('title',$title);
			$this->assign('bd_info',$bd_info);
			$this->assign('this_url',U('commission_record',['bd_id' => $bd_id]));
			$where['a.bd_id'] = $bd_id;
			$merchant_where = ['bd_id'=>$bd_id];
            $this->assign('current_bdid', $bd_id);
            $this->assign('current_pbdid', 0);
		}
		elseif($pbd_id){
			$pbd_info = M("Bd")->where(['bd_id'=>$pbd_id])->find();
			$sons = array_column(M("Bd")->where(['pbd_id'=>$pbd_id])->select(), 'bd_id');
			$where['a.bd_id'] = $pbd_id;
            $where['a.type'] = 1;
			$title = "业务经理--". $pbd_info['bd_name']."的下级";
			$this->assign('title',$title);
			$this->assign('pbd_info',$pbd_info);
			$this->assign('this_url',U('commission_record',['pbd_id' => $pbd_id]));
			$merchant_where = ['bd_id'=>['in', $sons]];
            $this->assign('current_bdid', 0);
            $this->assign('current_pbdid', $pbd_id);
		}
		else{
			$this->error_tips("参数非法！");
		}
        $merchant_nums = M('Merchant')->where($merchant_where)->count();
        $all_commission = M('Bd_commission_record')->alias('a')->where($where)->sum("a.commission");
        $this->assign("merchant_nums", $merchant_nums ?? 0);
        $this->assign("all_commission", $all_commission ?? 0);

		$get_alias_c_name = D("Merchant_money_list")->get_alias_c_name();
        $this->assign("get_alias_c_name", $get_alias_c_name);

        $searchtype = trim($_GET['searchtype']);
        $keyword = trim($_GET['keyword']);
        switch ($searchtype) {
            case 'mer_name':
                $where['m.name'] = ['like', '%'.$keyword.'%'];
                break;
            case 'store_name':
                $where['s.name'] = ['like', '%'.$keyword.'%'];
                break;
            case 'order_id':
                $where['a.order_id'] = ['like', '%'.$keyword.'%'];
                break;
        }
        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
        if($begin_time && $end_time){
            if ($begin_time>$end_time) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $begin_date = date('Y-m-d H:i:s', strtotime($begin_time));   
            $end_date = date('Y-m-d H:i:s', strtotime($end_time)+24*3600);   
            $where['a.addtime'] = ['between', [$begin_date, $end_date]];
        }
        $this->assign('begin_time', $begin_time);
        $this->assign('end_time', $end_time);
        $order_type = $_GET['order_type'];
        if($order_type && $order_type != 'all'){
            $where['a.order_type'] = $order_type;
        }

        import('@.ORG.system_page');

        $count = M('Bd_commission_record')->alias('a')->join(C("DB_PREFIX")."merchant as m on m.mer_id=a.mer_id")->join(C("DB_PREFIX")."merchant_store as s on s.store_id=a.store_id")->where($where)->count();
        $p = new Page($count, 20);
        $data = M('Bd_commission_record')
            ->alias('a')
            ->join(C("DB_PREFIX")."merchant as m on m.mer_id=a.mer_id")
            ->join(C("DB_PREFIX")."merchant_store as s on s.store_id=a.store_id")
            ->field("a.*,m.name as mer_name, s.name as store_name")
            ->where($where);
            if(empty($export)){
                $data = $data->limit($p->firstRow, $p->listRows);
            }
        $data = $data->order('a.addtime desc')
            ->select();
        foreach ($data as $key => $value) {
            $data[$key]['order_type_txt'] = $get_alias_c_name[$value['order_type']] ?? '无';
        }
        if($export){
            if(empty($data)){
                $this->ajaxReturn([
                    'error_msg'=> L_('暂无记录'),
                    'code' => 500
                ],'JSON');
            }
            $title = '佣金记录';
            $filename = $title.'-'.date("Y-m-d", time()).'_'.time();
            $params = [
                'title'  => $title,
                'cellName' =>  [
                    ['order_type_txt', '业务类型'],
                    ['order_id', '订单号'],
                    ['mer_name', '商家名称'],
                    ['store_name', '店铺名称'],
                    ['commission', '获得佣金(元)'],
                    ['addtime', '佣金结算时间'],
                ],
                'cellData'  => $data,
                'fileName' => $filename
            ];
           $excel = invoke_v20_service('\app\common\model\service\export\ExportService/exportExcel', $params);
           if($excel['data']['error_no']){
               $this->ajaxReturn([
                   'error_msg'=> L_($excel['data']['error_msg']),
                   'code' => 500
               ],'JSON');
           }
           $this->ajaxReturn([
               'url'=> C('config.site_url') . '/v20/runtime/' . $filename.'.xlsx',
               'code' => 200
           ],'JSON');
        }
        $this->assign('pagebar', $p->show());
        $this->assign('data', $data);
		$this->display();
	}
}