<?php
/*
 * 返佣营收
 */

class LiveAction extends BaseAction{
	public function shop_order(){
		$type = I("type") ? : 'liveshow';
		$this->assign("now_type", $type);
		$searchtype = I("searchtype");
		$keyword = trim(I('keyword'));
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		$status = I("status");
		$where = [
			'mer_id' => $this->merchant_session['mer_id'],
			'order_from' => ['neq', 1]
		];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		else{
			$where['o.is_livevideo'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_video as l on o.livevideo_id=l.video_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.video_id,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		if($keyword){
			switch ($searchtype) {
				case 'uid':
					$where['l.uid'] = $keyword;
					break;
				case 'liveno':
					if($type == 'liveshow'){
						$where['l.live_no'] = $keyword;
					}
					break;
				case 'real_orderid':
					$where['o.real_orderid'] = $keyword;
					break;
				case 'orderid':
					$where['o.orderid'] = $keyword;
					break;
				case 'customer_id':
					$where['o.uid'] = $keyword;
					break;
			}
		}
		if($begin_time && $end_time){
			$begin_time = strtotime($begin_time.' 00:00:00');
			$end_time = strtotime($end_time.' 23:59:59');
			$where['o.create_time'] = ['between', [$begin_time, $end_time]];
		}
		if($status){
			$where['o.status'] = $status == '1' ? ['in', [2,3]] : ['not in', [2,3]];
		}
		$count = M("Shop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$order_list = M("Shop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field($field)->limit($p->firstRow,$p->listRows)->order("o.create_time desc")->select();
		foreach ($order_list as $key => $value) {
			$order_list[$key]['type'] = $type == 'liveshow' ? "直播" : "短视频";
			$order_list[$key]['create_time'] = date('Y-m-d H:i', $value['create_time']);
			$order_list[$key]['order_money'] = $value['total_price'];
		}
		$pagebar = $p->show();
		$this->assign("order_list", $order_list);
		$this->assign('pagebar', $pagebar);

		//统计
		$all = M("Shop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field("sum(o.live_merchant_money) as total_merchant_money,sum(o.live_author_money) as total_author_money")->find();
		$this->assign("total_order_nums",$count);
		$this->assign("total_merchant_money",$all['total_merchant_money']);
		$this->assign("total_author_money",$all['total_author_money']);
		$this->display();
	}

	public function mall_order(){
		$type = I("type") ? : 'liveshow';
		$this->assign("now_type", $type);
		$searchtype = I("searchtype");
		$keyword = trim(I('keyword'));
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		$status = I("status");
		$where = [
			'mer_id' => $this->merchant_session['mer_id'],
			'order_from' => ['eq', 1]
		];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		else{
			$where['o.is_livevideo'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_video as l on o.livevideo_id=l.video_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.video_id,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		if($keyword){
			switch ($searchtype) {
				case 'uid':
					$where['l.uid'] = $keyword;
					break;
				case 'liveno':
					if($type == 'liveshow'){
						$where['l.live_no'] = $keyword;
					}
					break;
				case 'real_orderid':
					$where['o.real_orderid'] = $keyword;
					break;
				case 'orderid':
					$where['o.orderid'] = $keyword;
					break;
				case 'customer_id':
					$where['o.uid'] = $keyword;
					break;
			}
		}
		if($begin_time && $end_time){
			$begin_time = strtotime($begin_time.' 00:00:00');
			$end_time = strtotime($end_time.' 23:59:59');
			$where['o.create_time'] = ['between', [$begin_time, $end_time]];
		}
		if($status){
			$where['o.status'] = $status == '1' ? ['in', [2,3]] : ['not in', [2,3]];
		}
		$count = M("Shop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$order_list = M("Shop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field($field)->limit($p->firstRow,$p->listRows)->order("o.create_time desc")->select();
		foreach ($order_list as $key => $value) {
			$order_list[$key]['type'] = $type == 'liveshow' ? "直播" : "短视频";
			$order_list[$key]['create_time'] = date('Y-m-d H:i', $value['create_time']);
			$order_list[$key]['order_money'] = $value['total_price'];
		}
		$pagebar = $p->show();
		$this->assign("order_list", $order_list);
		$this->assign('pagebar', $pagebar);

		//统计
		$all = M("Shop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field("sum(o.live_merchant_money) as total_merchant_money,sum(o.live_author_money) as total_author_money")->find();
		$this->assign("total_order_nums",$count);
		$this->assign("total_merchant_money",$all['total_merchant_money']);
		$this->assign("total_author_money",$all['total_author_money']);
		$this->display();
	}

	public function mall3_order(){
		$type = I("type") ? : 'liveshow';
		$this->assign("now_type", $type);
		$searchtype = I("searchtype");
		$keyword = trim(I('keyword'));
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		$status = I("status");
		$where = [
			'mer_id' => $this->merchant_session['mer_id']
		];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.order_no as real_orderid,o.create_time,o.money_total as total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		else{
			$where['o.is_livevideo'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_video as l on o.livevideo_id=l.video_id";
			$field = 'o.order_id,o.order_no as real_orderid,o.create_time,o.money_total as total_price,l.video_id,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		if($keyword){
			switch ($searchtype) {
				case 'uid':
					$where['l.uid'] = $keyword;
					break;
				case 'liveno':
					if($type == 'liveshow'){
						$where['l.live_no'] = $keyword;
					}
					break;
				case 'real_orderid':
					$where['o.order_no'] = $keyword;
					break;
				case 'orderid':
					$where['o.pay_orderno'] = $keyword;
					break;
				case 'customer_id':
					$where['o.uid'] = $keyword;
					break;
			}
		}
		if($begin_time && $end_time){
			$begin_time = strtotime($begin_time.' 00:00:00');
			$end_time = strtotime($end_time.' 23:59:59');
			$where['o.create_time'] = ['between', [$begin_time, $end_time]];
		}
		if($status){
			$where['o.status'] = $status == '1' ? ['between', [30,59]] : ['between', [10,29]];
		}
		$count = M("Mall_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$order_list = M("Mall_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field($field)->limit($p->firstRow,$p->listRows)->order("o.create_time desc")->select();
		foreach ($order_list as $key => $value) {
			$order_list[$key]['type'] = $type == 'liveshow' ? "直播" : "短视频";
			$order_list[$key]['create_time'] = date('Y-m-d H:i', $value['create_time']);
			$order_list[$key]['order_money'] = $value['total_price'];
		}
		$pagebar = $p->show();
		$this->assign("order_list", $order_list);
		$this->assign('pagebar', $pagebar);

		//统计
		$all = M("Mall_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field("sum(o.live_merchant_money) as total_merchant_money,sum(o.live_author_money) as total_author_money")->find();
		$this->assign("total_order_nums",$count);
		$this->assign("total_merchant_money",$all['total_merchant_money']);
		$this->assign("total_author_money",$all['total_author_money']);
		$this->display();
	}

	public function group_order(){
		$type = I("type") ? : 'liveshow';
		$this->assign("now_type", $type);
		$searchtype = I("searchtype");
		$keyword = trim(I('keyword'));
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		$status = I("status");
		$where = [
			'mer_id' => $this->merchant_session['mer_id']
		];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.real_orderid,o.add_time as create_time,o.total_money as total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		else{
			$where['o.is_livevideo'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_video as l on o.livevideo_id=l.video_id";
			$field = 'o.order_id,o.real_orderid,o.add_time as create_time,o.total_money as total_price,l.video_id,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		if($keyword){
			switch ($searchtype) {
				case 'uid':
					$where['l.uid'] = $keyword;
					break;
				case 'liveno':
					if($type == 'liveshow'){
						$where['l.live_no'] = $keyword;
					}
					break;
				case 'real_orderid':
					$where['o.real_orderid'] = $keyword;
					break;
				case 'orderid':
					$where['o.orderid'] = $keyword;
					break;
				case 'customer_id':
					$where['o.uid'] = $keyword;
					break;
			}
		}
		if($begin_time && $end_time){
			$begin_time = strtotime($begin_time.' 00:00:00');
			$end_time = strtotime($end_time.' 23:59:59');
			$where['o.add_time'] = ['between', [$begin_time, $end_time]];
		}
		if($status){
			$where['o.status'] = $status == '1' ? ['in', [1,2]] : ['not in', [1,2]];
		}
		$count = M("Group_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$order_list = M("Group_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field($field)->limit($p->firstRow,$p->listRows)->order("o.add_time desc")->select();
		foreach ($order_list as $key => $value) {
			$order_list[$key]['type'] = $type == 'liveshow' ? "直播" : "短视频";
			$order_list[$key]['create_time'] = date('Y-m-d H:i', $value['create_time']);
			$order_list[$key]['order_money'] = $value['total_price'];
		}
		$pagebar = $p->show();
		$this->assign("order_list", $order_list);
		$this->assign('pagebar', $pagebar);

		//统计
		$all = M("Group_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field("sum(o.live_merchant_money) as total_merchant_money,sum(o.live_author_money) as total_author_money")->find();
		$this->assign("total_order_nums",$count);
		$this->assign("total_merchant_money",$all['total_merchant_money']);
		$this->assign("total_author_money",$all['total_author_money']);
		$this->display();
	}


	public function meal_order(){
		$type = I("type") ? : 'liveshow';
		$this->assign("now_type", $type);
		$searchtype = I("searchtype");
		$keyword = trim(I('keyword'));
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		$status = I("status");
		$where = [
			'mer_id' => $this->merchant_session['mer_id']
		];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		else{
			$where['o.is_livevideo'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_video as l on o.livevideo_id=l.video_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.video_id,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		if($keyword){
			switch ($searchtype) {
				case 'uid':
					$where['l.uid'] = $keyword;
					break;
				case 'liveno':
					if($type == 'liveshow'){
						$where['l.live_no'] = $keyword;
					}
					break;
				case 'real_orderid':
					$where['o.real_orderid'] = $keyword;
					break;
				case 'customer_id':
					$where['o.uid'] = $keyword;
					break;
			}
		}
		if($begin_time && $end_time){
			$begin_time = strtotime($begin_time.' 00:00:00');
			$end_time = strtotime($end_time.' 23:59:59');
			$where['o.create_time'] = ['between', [$begin_time, $end_time]];
		}
		if($status){
			$where['o.status'] = $status == '1' ? ['in', [3,4]] : ['not in', [3,4]];
		}
		$count = M("Foodshop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$order_list = M("Foodshop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field($field)->limit($p->firstRow,$p->listRows)->order("o.create_time desc")->select();
		foreach ($order_list as $key => $value) {
			$order_list[$key]['type'] = $type == 'liveshow' ? "直播" : "短视频";
			$order_list[$key]['create_time'] = date('Y-m-d H:i', $value['create_time']);
			$order_list[$key]['order_money'] = $value['total_price'];
		}
		$pagebar = $p->show();
		$this->assign("order_list", $order_list);
		$this->assign('pagebar', $pagebar);

		//统计
		$all = M("Foodshop_order")->alias("o")->join($join)->join('left join '.C("DB_PREFIX").'user as u on u.uid=l.uid')->where($where)->field("sum(o.live_merchant_money) as total_merchant_money,sum(o.live_author_money) as total_author_money")->find();
		$this->assign("total_order_nums",$count);
		$this->assign("total_merchant_money",$all['total_merchant_money']);
		$this->assign("total_author_money",$all['total_author_money']);
		$this->display();
	}
}