<?php
/*
 * 直播
 */

class LiveshowAction extends BaseAction{
	public function shop_goods_list(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'shop',
			'a.mer_id' => $this->merchant_session['mer_id']
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.business_id=b.goods_id")->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.business_id=b.goods_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		$storeids = array_column($data, 'store_id');
		$stores = M("Merchant_store")->where(['store_id'=>['in', $storeids]])->field("store_id,name")->select();
		$stores = array_column($stores, 'name', 'store_id');
		foreach ($data as $key => $value) {
			$data[$key]['store_name'] = $stores[$value['store_id']];
			$data[$key]['percent_txt'] = $value['is_sys'] == 1 ? "-1(使用平台佣金".C('config.live_show_commission_percent')."%)" :$value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function mall_goods_list(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'mall',
			'a.mer_id' => $this->merchant_session['mer_id']
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.business_id=b.goods_id")->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.business_id=b.goods_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		$storeids = array_column($data, 'store_id');
		$stores = M("Merchant_store")->where(['store_id'=>['in', $storeids]])->field("store_id,name")->select();
		$stores = array_column($stores, 'name', 'store_id');
		foreach ($data as $key => $value) {
			$data[$key]['store_name'] = $stores[$value['store_id']];
            $data[$key]['percent_txt'] = $value['is_sys'] == 1 ? "-1(使用平台佣金".C('config.live_show_commission_percent')."%)" :$value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

    public function mall3_goods_list(){
        $where = [
            'a.status' => 1, 
            'a.business' => 'mall3',
            'a.mer_id' => $this->merchant_session['mer_id']
        ];
        $keyword = trim(I("keyword"));
        if($keyword){
            $where['b.name'] = ['like', '%'.$keyword.'%'];
        }
        $field = "a.*, b.name,b.price";
        $count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."mall_goods as b on a.business_id=b.goods_id")->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 20);
        $data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."mall_goods as b on a.business_id=b.goods_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

        $storeids = array_column($data, 'store_id');
        $stores = M("Merchant_store")->where(['store_id'=>['in', $storeids]])->field("store_id,name")->select();
        $stores = array_column($stores, 'name', 'store_id');
        foreach ($data as $key => $value) {
            $data[$key]['store_name'] = $stores[$value['store_id']];
            $data[$key]['percent_txt'] = $value['is_sys'] == 1 ? "-1(使用平台佣金".C('config.live_show_commission_percent')."%)" :$value['percent']."%";
        }


        $pagebar = $p->show();
        $this->assign("goods_list", $data);
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

	public function meal_goods_list(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'meal',
			'a.mer_id' => $this->merchant_session['mer_id']
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."foodshop_goods as b on a.business_id=b.goods_id")->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."foodshop_goods as b on a.business_id=b.goods_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		$storeids = array_column($data, 'store_id');
		$stores = M("Merchant_store")->where(['store_id'=>['in', $storeids]])->field("store_id,name")->select();
		$stores = array_column($stores, 'name', 'store_id');
		foreach ($data as $key => $value) {
			$data[$key]['store_name'] = $stores[$value['store_id']];
            $data[$key]['percent_txt'] = $value['is_sys'] == 1 ? "-1(使用平台佣金".C('config.live_show_commission_percent')."%)" :$value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function group_goods_list(){
		$where = [
			'a.status' => 1, 
			'a.business' => 'group',
			'a.mer_id' => $this->merchant_session['mer_id']
		];
		$keyword = trim(I("keyword"));
		if($keyword){
			$where['b.name'] = ['like', '%'.$keyword.'%'];
		}
		$field = "a.*, b.name,b.price";
		$count = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."group as b on a.business_id=b.group_id")->where($where)->count();
		import('@.ORG.merchant_page');
		$p = new Page($count, 20);
		$data = M("Live_goods")->alias("a")->join("left join ".C("DB_PREFIX")."group as b on a.business_id=b.group_id")->field($field)->where($where)->order("a.id desc")->limit($p->firstRow,$p->listRows)->order("create_time desc")->select();

		foreach ($data as $key => $value) {
            $data[$key]['percent_txt'] = $value['is_sys'] == 1 ? "-1(使用平台佣金".C('config.live_show_commission_percent')."%)" :$value['percent']."%";
		}


		$pagebar = $p->show();
		$this->assign("goods_list", $data);
		$this->assign('pagebar', $pagebar);
		$this->display();
	}

	public function select_goods(){
        $type = I("type");
        $this->assign('type', $type);


        import('@.ORG.diypage');
        $condition = array();
        $database = null;
        if($type == 'mall'){//商城商品
            $database = D(_view('Shop_goods'));
            $condition['g.cat_fid']=array('gt',0);
            $condition['s.mer_id'] = $this->merchant_session['mer_id'];
            $condition['s.status'] = 1;
            $condition['g.status'] = 1;
            if($_GET['keyword']){
                $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'g.goods_id, g.name, g.last_time, g.price,s.name as store_name';
            $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        elseif($type == 'mall3'){
            $database = D('Mall_goods');
            $condition['s.mer_id'] = $this->merchant_session['mer_id'];
            $condition['g.status'] = 1;
            $condition['s.status'] = 1;
            $condition['g.is_del'] = 0;
            if($_GET['keyword']){
                $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'g.goods_id, g.name, g.update_time as last_time, g.price,s.name as store_name';
            $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        elseif($type == 'shop'){
            $database = D(_view('Shop_goods'));
            $condition['g.goods_type']=array('neq',1);
            $condition['s.mer_id'] = $this->merchant_session['mer_id'];
            $condition['s.status'] = 1;
            $condition['g.status'] = 1;
            if($_GET['keyword']){
                $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'g.goods_id, g.name, g.last_time, g.price,s.name as store_name';
            $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        elseif($type == 'group'){
            $database = D('Group');
            $condition['mer_id'] = $this->merchant_session['mer_id'];
            $condition['status'] = ['eq', 1];
            $condition['begin_time'] = ['lt', time()];
            $condition['end_time'] = ['gt', time()];
            if($_GET['keyword']){
                $condition['name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'group_id as goods_id, name, last_time, price';
            $count = $database->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        elseif($type == 'meal'){
            $database = D('Foodshop_goods');
            $storeids = array_column(M("Merchant_store")->where(['mer_id'=>$this->merchant_session['mer_id']])->field("store_id")->select(), 'store_id');
            $goods_list = [];
            $count = 0;
            if($storeids){
	            $condition['store_id'] = ['in', $storeids];
	            if($_GET['keyword']){
	                $condition['name'] = array('like','%'.$_GET['keyword'].'%');
	            }
	            $field = 'goods_id, name, last_time, price';
	            $count = $database->where($condition)->count();
	        	$p = new Page($count, 10);
	            $goods_list = $database->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
	        }
	        else{
	        	$p = new Page($count, 10);
	        }
        }

        $selected = [];
        $where = [];
        $where['business'] = $type;
        $where['status'] = 1;
        $where['mer_id'] = $this->merchant_session['mer_id'];
        $selected = array_column(M("Live_goods")->where($where)->field("business_id")->select(), 'business_id');
        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['select'] = 0;
            if(in_array($value['goods_id'], $selected)){
                $goods_list[$key]['select'] = 1;
            }
        }

        $this->assign("keyword", $_GET['keyword']);
        $this->assign("good_list", $goods_list);
        $this->assign("pagebar", $p->show());

        $this->display();
    }

    //添加商品
    public function ajax_add_goods(){
    	$type = I("type");
    	$selected = trim(I("selected"),',');
    	if(empty($type) || empty($selected)){
    		echo json_encode(['errcode'=>1,'msg'=>'未检测到新增']);
    		exit;
    	}
    	$selected = explode(',', $selected);
    	foreach ($selected as $goods_id) {
    		$has = M("Live_goods")->where(['business'=>$type,'business_id'=>$goods_id,'mer_id'=>$this->merchant_session['mer_id']])->find();
    		if($has){
    			$save = [
    				'create_time' => date('Y-m-d H:i:s'),
    				'status' => 1
    			];
    			M("Live_goods")->where(['id'=>$has['id']])->data($save)->save();
    		}
    		else{
    			$data = [
    				'business' => $type,
    				'business_id' => $goods_id,
    				'show_sales' => 0,
    				'video_sales' => 0,
    				'mer_id' => $this->merchant_session['mer_id'],
    				'store_id' => 0,
    				'status' => 1,
    				'percent' => 0,
    				'create_time' => date('Y-m-d H:i:s'),
    			];
    			if($type == 'shop' || $type == 'mall'){
    				$data['store_id'] = M("Shop_goods")->where(['goods_id'=>$goods_id])->getField("store_id");
    			}
    			elseif($type == 'meal'){
    				$data['store_id'] = M("Foodshop_goods")->where(['goods_id'=>$goods_id])->getField("store_id");
    			}
                elseif($type == 'mall3'){
                    $data['store_id'] = M("Mall_goods")->where(['goods_id'=>$goods_id])->getField("store_id");
                }
    			M("Live_goods")->data($data)->add();
    		}
    	}
    	echo json_encode(['errcode'=>0,'msg'=>'','redirect_url'=>U($type."_goods_list")]);
    	exit;
    }

    public function goods_del(){
    	$id = I("id");
    	M("Live_goods")->where(['id'=>$id])->data(['status'=>2])->save();
    	$this->success(L_('删除成功！'));
    }

    public function ajax_change_percent(){
    	$id = I("id");
    	$percent = I("percent");
    	if($percent < -1 || $percent > 100 || $percent != floatval($percent)){
    		echo json_encode(['errcode'=>1,'msg'=>'请输入-1 - 100以内的数字']);
    		exit;
    	}
    	$is_sys = $percent == -1 ? 1 : 0;
        $percent == -1 && $percent = C('config.live_show_commission_percent');
        M("Live_goods")->where(['id'=>$id])->data(['percent' => $percent,'is_sys' => $is_sys])->save();
    	echo json_encode(['errcode'=>0,'msg'=>'']);
    	exit;
    }
}