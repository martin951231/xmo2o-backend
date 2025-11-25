<?php
/*
 * 直播管理
 *
 * @  Writers    lumin
 * @  BuildTime  
 *
 */
class LiveAction extends BaseAction{

	public function config(){
		redirect(U('Config/index',array('galias'=>'live','header'=>'Live/config')));
	}

	//直播列表
	public function liveshow(){
		$condition = [];
		$keyword = I("keyword");
		$searchtype = I("searchtype");
		$searchTimeType = I("searchTimeType");
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		$status = I("status");
		if(!empty($keyword)){
			switch ($searchtype) {
				case 'liveshow_no':
					$condition['live_no'] = $keyword;
					break;
				case 'uid':
					$condition['uid'] = $keyword;
					break;
				case 'nickname':
					$condition['live_nickname'] = ['like', $keyword];
					break;
			}
		}

		if(!empty($begin_time) && !empty($end_time)){
			$begin_time .= ' 00:00:00';
			$end_time .= ' 23:59:59';
			switch ($searchTimeType) {
				case 'create_time':
					$condition['create_time'] = ['between', [$begin_time, $end_time]];
					break;
				case 'estimate_time':
					$condition['estimate_time'] = ['between', [$begin_time, $end_time]];
					break;
			}
		}

		if($status > 0){
			switch ($status) {
				case 1:
					$condition['_string'] = 'UNIX_TIMESTAMP(real_start_time) = 0';
					break;
				case 2:
					$condition['_string'] = 'UNIX_TIMESTAMP(real_start_time) > 0 and UNIX_TIMESTAMP(real_end_time) = 0';
					break;
				case 3:
					$condition['_string'] = 'UNIX_TIMESTAMP(real_end_time) > 0';
					break;
			}
		}
		$condition['is_del'] = 0;

		import('@.ORG.system_page');
		$count = D("Live_show")->where($condition)->count();
		$p = new Page($count,20);
		$data_list = D("Live_show")->where($condition)->order('create_time DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$categoryids = array_unique(array_merge(array_column($data_list, 'category_1'),array_column($data_list, 'category_2')));
		if($categoryids){
			$categorys = M("Live_show_category")->field("id, name")->where(['id'=>['in',$categoryids]])->select();
			$categorys = array_column($categorys, 'name', 'id');
		}
		foreach ($data_list as $key => $value) {
			$data_list[$key]['category'] = "";
			if($value['category_1']){
				$data_list[$key]['category'] .= $categorys[$value['category_1']];
			}
			if($value['category_2']){
				$data_list[$key]['category'] .= ' > '.$categorys[$value['category_2']];
			}
			$data_list[$key]['area_name'] = $value['area_id'] ? D("Area")->name($value['area_id']) : "-";
			$record_result = unserialize($value['record_result']);
			$data_list[$key]['video_url'] = '';
			if($record_result['video_url']){
				$data_list[$key]['video_url'] = $record_result['video_url'];
			}
		}
		$this->assign('data_list',$data_list);
		$this->assign('pagebar',$p->show());

		$this->display();
	}

	//直播回放详情
	public function liveshow_replay(){
		$live_id = I("live_id");
		$live_info = D("Live_show")->where(['live_id'=>$live_id])->find();
		$record_result = unserialize($live_info['record_result']);
		$video_url = "";
		if($record_result['video_url']){
			$video_url = $record_result['video_url'];
		}
		$this->assign("video_url", $video_url);
		$this->display();
	}

	public function setup(){
		$live_id = I("live_id");
		if(IS_POST){
			$database = D("Live_show");
			if($database->data($_POST)->save()){
				$this->success('设置成功！');
			}else{
				$this->error('设置失败！请重试~');
			}
		}else{
			$live = D("Live_show")->where(['live_id'=>$live_id])->find();
			$this->assign("live", $live);
			$this->display();
		}
	}

	//直播分类列表
	public function show_category(){
		$fid = I("fid", "0");
		$where = [
			'is_del' => 0
		];
		if($fid){
			$where['fid'] = $fid;
			$parent = M("Live_show_category")->where(['id' => $fid])->find();
			$this->assign("parent", $parent);
		}
		else{
			$where['fid'] = 0;
		}
		$data_list = M("Live_show_category")->where($where)->order("orderby desc")->select();
		$this->assign("data_list", $data_list);
		$this->display();
	}	

	//直播分类添加
	public function add_liveshow_category(){
		if (IS_POST) {
			if(M("Live_show_category")->where(['is_del'=>0, 'name'=>$_POST['name']])->find()){
				$this->error('当前分类已存在，请勿重复添加~');
			}
			if (M("Live_show_category")->add($_POST)) {
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！请重试~');
			}
		}
		$fid = I("fid", "0");
		$where = [
			'is_del' => 0, 
			'fid' => 0
		];
		if($fid){
			$this->assign("fid", $fid);
		}
		$first_category = M("Live_show_category")->field("id,name")->where($where)->order('orderby desc')->select();
		$this->assign("first_category", $first_category);
		$this->display();
	}

	//直播分类编辑
	public function edit_liveshow_category(){
		if (IS_POST) {
			if(M("Live_show_category")->where(['is_del'=>0, 'name'=>$_POST['name']])->find()){
				$this->error('当前分类已存在，请勿重复~');
			}
			if (M("Live_show_category")->where(['id'=>$_POST['id']])->save($_POST)) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！请重试~');
			}
		}
		$id = I("id");
		$where = [
			'is_del' => 0, 
			'fid' => 0
		];
		$first_category = M("Live_show_category")->field("id,name")->where($where)->order('orderby desc')->select();
		$this->assign("first_category", $first_category);

		$info = M("Live_show_category")->where(['id'=> $id])->find();
		$this->assign("info", $info);
		$this->display();
	}

	//直播分类删除
	public function del_liveshow_category(){
		$id = I("id");
		M("Live_show_category")->where(['id'=>$id])->data(['is_del'=>1])->save();
		$this->success('删除成功！');
	}

	public function delete_show(){
		$id = I("id");
		M("Live_show")->where(['live_id'=>$id])->data(['is_del'=>1])->save();
		$this->success('删除成功！');	
	}

    public function forbid_show(){
        $id = I("id");
        $now_live = M('Live_show')->where(['live_id'=>$id])->find();
        if($now_live['status'] != 1){
            $this->error('当前直播不在直播中，无法禁播！');
        }
        import('@.ORG.TencentIM');
        $lib_im = new TencentIM;
		$r = $lib_im->send_system_notify($now_live['live_no'], 'forbid_live', ['msg'=>'抱歉！平台已提前结束您的直播']);
        if($r['errcode'] == '0'){
            M("Live_show")->where(['live_id'=>$id])->data(['forbid_status'=>1, 'status'=>3, 'real_end_time'=>date('Y-m-d H:i:s'), ])->save();
            $this->success('操作成功！');
        }
        else{
            $this->error($r['errmsg']);
        }
    }

    public function delete_video(){
		$id = I("id");
		M("Live_video")->where(['video_id'=>$id])->data(['is_del'=>1])->save();
		$this->success('删除成功！');	
	}

	//首页分类
	public function live_category(){
		$where = [
			'is_del' => 0
		];
		$data_list = M("Live_category")->where($where)->order("sort desc")->select();
		$this->assign("data_list", $data_list);
		$this->display();
	}

	//首页分类添加
	public function add_live_category(){
		if (IS_POST) {
			$categorys = $_POST['categorys'];
			if($categorys){
				 $_POST['categorys'] =','.implode(',', $categorys).',';
			}
			 $_POST['addtime'] = time();
			if(M("Live_category")->where(['is_del'=>0, 'name'=>$_POST['name']])->find()){
				$this->error('当前分类已存在，请勿重复添加~');
			}
			if (M("Live_category")->add($_POST)) {
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！请重试~');
			}
		}
		$this->display();
	}

	//首页分类编辑
	public function edit_live_category(){
		if (IS_POST) {
			$categorys = $_POST['categorys'];
			if($categorys){
				 $_POST['categorys'] =','.implode(',', $categorys).',';
			}
			if(M("Live_category")->where(['is_del'=>0, 'name'=>$_POST['name'], 'id'=>['neq',$_POST['id']]])->find()){
				$this->error('当前话题已存在，请勿重复~');
			}
			if (M("Live_category")->where(['id'=>$_POST['id']])->save($_POST) !== false) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！请重试~');
			}
		}
		$id = I("id");
		$where = [
			'is_del' => 0
		];

		$info = M("Live_category")->where(['id'=> $id])->find();

		if($info['categorys']){
			$where = [
				'id' => ['in', trim($info['categorys'],',')],
				'is_del' => 0
			];
			if($info['type'] == 1){
				$categorys = M('Live_show_category')->where($where)->select();
			}else{
				$categorys = M('Live_video_category')->where($where)->select();
			}
		}
		$this->assign("categorys", $categorys);
		$this->assign("info", $info);
		$this->display();
	}

	//首页分类删除
	public function del_live_category(){
		
		$id = I("id");
		if(empty($id)){
				$this->error('缺少参数');
		}

		$data = [
			'is_del' => 1
		];
		$res = M("Live_category")->where(['id'=> $id])->data($data)->save();

		if($res){
			$this->success('删除成功！');
		}else{
			$this->error('删除成失败');
		}
	}

	//供选择的直播分类列表
	public function select_live_show_category(){
		$where = [
			'is_del' => 0
		];
		$where['fid'] = 0;
		
		$data_list = M("Live_show_category")->where($where)->order("orderby desc")->select();
		$this->assign("list", $data_list);
		$this->display();
	}	

	//供选择的短视频话题列表
	public function select_live_video_category(){
		$where = [
			'is_del' => 0
		];
		$where['fid'] = 0;
		
		$data_list = M("Live_video_category")->where($where)->order("orderby desc")->select();
		$this->assign("list", $data_list);
		$this->display();
	}

	//短视频话题
	public function video_category(){
		$where = [
			'is_del' => 0
		];
		$data_list = M("Live_video_category")->where($where)->order("orderby desc")->select();
		$this->assign("data_list", $data_list);
		$this->display();
	}

	//短视频话题添加
	public function add_livevideo_category(){
		if (IS_POST) {
			if(M("Live_video_category")->where(['is_del'=>0, 'name'=>$_POST['name']])->find()){
				$this->error('当前话题已存在，请勿重复添加~');
			}
			if (M("Live_video_category")->add($_POST)) {
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！请重试~');
			}
		}
		$this->display();
	}

	//短视频话题编辑
	public function edit_livevideo_category(){
		if (IS_POST) {
			if(M("Live_video_category")->where(['is_del'=>0, 'name'=>$_POST['name'], 'id'=>['neq',$_POST['id']]])->find()){
				$this->error('当前话题已存在，请勿重复~');
			}
			if (M("Live_video_category")->where(['id'=>$_POST['id']])->save($_POST)) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！请重试~');
			}
		}
		$id = I("id");
		$where = [
			'is_del' => 0
		];

		$info = M("Live_video_category")->where(['id'=> $id])->find();
		$this->assign("info", $info);
		$this->display();
	}

	//短视频话题删除
	public function del_livevideo_category(){
		$id = I("id");
		M("Live_video_category")->where(['id'=>$id])->data(['is_del'=>1])->save();
		$this->success('删除成功！');
	}

	//举报列表
	public function report(){
		$Model = D("Live_report");
		$this->assign("category", $Model->category);
		$this->assign("business", $Model->business);


		$condition = [];
		$keyword = I("keyword");
		$searchtype = I("searchtype");
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		$business = I("business");
		$category = I("category");
		if(!empty($keyword)){
			switch ($searchtype) {
				case 'nickname':
					$condition['u.nickname'] = ['like', '%'.$keyword.'%'];
					break;
				case 'uid':
					$condition['r.uid'] = $keyword;
					break;
				case 'phone':
					$condition['u.phone'] = ['like', $keyword];
					break;
			}
		}

		if(!empty($begin_time) && !empty($end_time)){
			$begin_time .= ' 00:00:00';
			$end_time .= ' 23:59:59';
			$condition['r.create_time'] = ['between', [$begin_time, $end_time]];
		}

		if($business > 0){
			$condition['r.business'] = $business;
		}
		if($category > 0){
			$condition['r.category'] = $category;
		}

		import('@.ORG.system_page');
		$count = $Model->alias("r")->join("left join ".C("DB_PREFIX")."user as u on u.uid=r.uid")->where($condition)->count();
		$p = new Page($count,20);
		$data_list = $Model->alias("r")->join("left join ".C("DB_PREFIX")."user as u on u.uid=r.uid")->field("r.*,u.nickname")->where($condition)->order('r.create_time DESC')->limit($p->firstRow.','.$p->listRows)->select();
		foreach ($data_list as $key => $value) {
			$data_list[$key]['business_txt'] = $Model->business[$value['business']];
			$data_list[$key]['category_txt'] = $Model->category[$value['category']];
		}
		$this->assign('data_list',$data_list);
		$this->assign('pagebar',$p->show());
		$this->display();
	}

	public function report_detail(){
		$report_id = I("report_id");
		$Model = D("Live_report");
		$info = $Model->alias("r")->join("left join ".C("DB_PREFIX")."user as u on u.uid=r.uid")->field("r.*,u.nickname")->where(['r.id'=>$report_id])->find();
		$info['business_txt'] = $Model->business[$info['business']];
		$info['category_txt'] = $Model->category[$info['category']];
		if($info['business'] == '1'){
			$info['report_obj'] = "直播间ID：".D("Live_show")->where(['live_id'=>$info['business_id']])->getField("live_no");
		}
		if($info['business'] == '2'){
			$info['report_obj'] = "无";
		}
		$info['imgarr'] = [];
		if($info['imgs']){
			$imgarr = unserialize($info['imgs']);
			foreach ($imgarr as $value) {
				$info['imgarr'][] = replace_file_domain($value);
			}
			
		}
		$this->assign("info", $info);
		$this->display();
	}

	public function shop_order(){
		$type = I("type") ? : 'liveshow';
		$this->assign("now_type", $type);
		$searchtype = I("searchtype");
		$keyword = trim(I('keyword'));
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		$status = I("status");
		$where = [
			'order_from' => ['neq', 1]
		];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		elseif($type == 'livevideo'){
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
		import('@.ORG.system_page');
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
			'order_from' => ['eq', 1]
		];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		elseif($type == 'livevideo'){
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
					$where['l.live_no'] = $keyword;
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
		import('@.ORG.system_page');
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
		$where = [];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.order_no as real_orderid,o.create_time,o.money_total as total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		elseif($type == 'livevideo'){
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
					$where['l.live_no'] = $keyword;
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
		import('@.ORG.system_page');
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
		$where = [];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.real_orderid,o.add_time as create_time,o.total_money as total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		elseif($type == 'livevideo'){
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
					$where['l.live_no'] = $keyword;
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
		import('@.ORG.system_page');
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
		$where = [];
		$join = "";
		$field = "";
		if($type == 'liveshow'){
			$where['o.is_liveshow'] = '1';
			$join = "left join ".C("DB_PREFIX")."live_show as l on o.liveshow_id=l.live_id";
			$field = 'o.order_id,o.real_orderid,o.create_time,o.total_price,l.live_id,l.live_no,l.uid as author_id,u.nickname as author,o.live_merchant_money,o.live_author_money,o.status';
		}
		elseif($type == 'livevideo'){
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
					$where['l.live_no'] = $keyword;
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
		import('@.ORG.system_page');
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

	public function shop_goods(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'shop',
            'c.status' => 1
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price,c.name as store_name";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.business_id=b.goods_id")->join("left join ".C("DB_PREFIX")."merchant_store as c on a.store_id=c.store_id")->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.business_id=b.goods_id")->join("left join ".C("DB_PREFIX")."merchant_store as c on a.store_id=c.store_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		$storeids = array_column($data, 'store_id');
//		$stores = M("Merchant_store")->where(['store_id'=>['in', $storeids]])->field("store_id,name")->select();
//		$stores = array_column($stores, 'name', 'store_id');
		foreach ($data as $key => $value) {
//			$data[$key]['store_name'] = $stores[$value['store_id']];
			$data[$key]['percent_txt'] = $value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function mall_goods(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'mall'
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.business_id=b.goods_id")->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.business_id=b.goods_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		$storeids = array_column($data, 'store_id');
		$stores = M("Merchant_store")->where(['store_id'=>['in', $storeids]])->field("store_id,name")->select();
		$stores = array_column($stores, 'name', 'store_id');
		foreach ($data as $key => $value) {
			$data[$key]['store_name'] = $stores[$value['store_id']];
			$data[$key]['percent_txt'] = $value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function mall3_goods(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'mall3'
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."mall_goods as b on a.business_id=b.goods_id")->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."mall_goods as b on a.business_id=b.goods_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		$storeids = array_column($data, 'store_id');
		$stores = M("Merchant_store")->where(['store_id'=>['in', $storeids]])->field("store_id,name")->select();
		$stores = array_column($stores, 'name', 'store_id');
		foreach ($data as $key => $value) {
			$data[$key]['store_name'] = $stores[$value['store_id']];
			$data[$key]['percent_txt'] = $value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function meal_goods(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'meal'
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."foodshop_goods as b on a.business_id=b.goods_id")->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."foodshop_goods as b on a.business_id=b.goods_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		$storeids = array_column($data, 'store_id');
		$stores = M("Merchant_store")->where(['store_id'=>['in', $storeids]])->field("store_id,name")->select();
		$stores = array_column($stores, 'name', 'store_id');
		foreach ($data as $key => $value) {
			$data[$key]['store_name'] = $stores[$value['store_id']];
			$data[$key]['percent_txt'] = $value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function group_goods(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'group'
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."group as b on a.business_id=b.group_id")->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."group as b on a.business_id=b.group_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		foreach ($data as $key => $value) {
			$data[$key]['percent_txt'] = $value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}


	//短视频列表
	public function livevideo(){
		$condition = [];
		$keyword = I("keyword");
		$searchtype = I("searchtype");
		$begin_time = I("begin_time");
		$end_time = I("end_time");
		if(!empty($keyword)){
			switch ($searchtype) {
				case 'uid':
					$condition['v.uid'] = $keyword;
					break;
				case 'nickname':
					$condition['u.nickname'] = ['like', "%".$keyword."%"];
					break;
			}
		}

		if(!empty($begin_time) && !empty($end_time)){
			$begin_time .= ' 00:00:00';
			$end_time .= ' 23:59:59';
			$condition['v.addtime'] = ['between', [$begin_time, $end_time]];
		}

		$condition['v.is_del'] = 0;

		import('@.ORG.system_page');
		$count = D("Live_video")->alias("v")->join("left join ".C("DB_PREFIX")."user as u on u.uid=v.uid")->where($condition)->count();
		$p = new Page($count,20);
		$data_list = D("Live_video")->alias("v")->join("left join ".C("DB_PREFIX")."user as u on u.uid=v.uid")->field("v.*,u.nickname")->where($condition)->order('v.addtime DESC')->limit($p->firstRow.','.$p->listRows)->select();

		$this->assign('data_list',$data_list);
		$this->assign('pagebar',$p->show());

		$this->display();
	}

	//直播回放详情
	public function livevideo_replay(){
		$video_id = I("video_id");
		$live_info = D("Live_video")->where(['video_id'=>$video_id])->find();
		$this->assign("video_url", $live_info['play_url']);
		$this->display();
	}
}