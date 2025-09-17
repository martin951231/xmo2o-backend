<?php
/*
 * 直播短视频项目接口代码
 * errorCode 
 * 		0     正常返回 
 * 		1001  普通业务错误（可以弹窗提示）
 * 		1002  用户未登录 (需要跳转到登录界面)
 * 		1003  必填参数传递错误（不用提示给用户，在联调阶段就应该规避）
 * 		1004  服务器端错误，可以提示用户（系统异常）
 *
 */
class LiveAction extends BaseAction {

	//配置项接口
	public function config(){
		$output['tencent_SDKAppID'] = $this->config['tencent_SDKAppID'];
        $output['zhibo_version'] = empty($this->config['zhibo_version'])?0:$this->config['zhibo_version'];//直播样式，1新版样式
		$this->returnCode(0, $output);
	}

	//首页信息接口
	public function index(){
		
		// 首页分类
		$where = ['status'=>1];
		$categoryList = D('Live_category')->getList($where);

		$returnArr['category_list'] = $categoryList;
		$this->returnCode(0, $returnArr);
	}

	// 点击首页自定义分类获得直播或者短视频列表
	public function category_info_list(){
		$page = I("page", 1);
		$mer_id = I("mer_id", 0);
		$id = I("id", 1);
		$limit = 10;//默认10条

		$limit = $limit*($page-1).','.$limit;
		// 返回数组
		$returnArr = [
			'list' => [],
			'category' => (object)[],
		];

		// 分类信息
		$where = ['id'=>$id];
		$category = D('Live_category')->where($where)->find();
		if(empty($category) || empty($category['categorys'])){
			$this->returnCode(0, $returnArr);
		}
		
		// 绑定的直播分类id或短视频话题id
		$categorys = explode(',', trim($category['categorys'],','));


		// 搜索条件
		$where = [
			's.is_del' => 0
		];
		
		switch ($category['type']) {
			case '1'://直播分类
				$where['s.category_1'] = ['in',$categorys];

				if($mer_id){//私域入口 即A商家私域入口进入后 只能浏览 包含A商家商品的视频（视频列表页，和 看视频时前后滑动视频去到上一个 下一个视频 ）

					$where['g.mer_id'] = $mer_id;
					$where['bg.is_del'] = 0;
					$list = D("Live_show")->alias("s")->field('s.*')->join(' LEFT JOIN '.C('DB_PREFIX').'live_show_goods as bg on bg.live_id = s.live_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($where)->order("s.status asc,s.live_id desc")->limit($limit)->group('s.live_id')->select();
				}else{
					$list = D("Live_show")->alias('s')->where($where)->order("status asc,live_id desc")->limit($limit)->select();
				}

				foreach ($list as $key => $value) {
					$list[$key]['img'] = replace_file_domain($value['thumbimg_1']);
					$list[$key]['img2'] = replace_file_domain($value['thumbimg_2']);
					$list[$key]['nums'] = M("Live_show_room_user")->where(['live_show_id'=>$value['live_id']])->count()+$value['live_virtual_views'];//在线人数
					$zhubo = D("User")->get_user($value['uid']);
					$list[$key]['avatar'] = $zhubo['avatar'];
					$list[$key]['nickname'] = $value['live_nickname'];
					$list[$key]['remind'] = 0;
					$list[$key]['follow'] = M("Live_follow")->where(['uid' => $this->_uid, 'follow_uid'=> $value['uid'], 'is_del'=>0])->find() ? 1 : 0;
					$area = D("Area")->where(['area_id'=>$value['area_id']])->field("area_pid,area_name")->find();
					$parent = D("Area")->name($area['area_pid']);
					$list[$key]['area'] = $parent == $area['area_name'] ? $area['area_name'] : $parent.$area['area_name'];
					$list[$key]['estimate_time'] = $this->deal_estimate_time($value['estimate_time']);
					$goodids = implode(',',array_column(M("Live_show_goods")->where(['live_id'=>$value['live_id'], 'is_del'=>0])->field("goods_id")->select(), 'goods_id'));
					$list[$key]['goods_list'] = D("Live_goods")->get_by_ids($goodids, ['img', 'price', 'name']);
					$list[$key]['zan'] = $value['zan'] > 9999 ? ($value['zan']/10000)."万" : $value['zan'];
					$list[$key]['play_url'] = D("Live_show")->get_play_url($value['live_no']);//播放地址
				}
				break;
			
			case '2'://短视频话题
				foreach ($categorys as $key => $value) {
					if($key == 0){
						// $string = 'FIND_IN_SET("'.$value.'",s.categorys)';
						$string = 's.categorys like "%,'.$value.',%"';
					}else{
						// $string .= ' OR FIND_IN_SET("'.$value.'",s.categorys)';
						$string .= ' OR s.categorys like "%,'.$value.',%"';
					}
				}

				$where['_string'] = $string;

				if($mer_id){//私域入口 即A商家私域入口进入后 只能浏览 包含A商家商品的视频（视频列表页，和 看视频时前后滑动视频去到上一个 下一个视频 ）
					$where['g.mer_id'] = $mer_id;
					$where['bg.is_del'] = 0;
					$list = D("Live_video")->alias("s")->field('s.*')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($where)->order("s.video_id desc")->limit($limit)->group('s.video_id')->select();
				}else{
					$list = D("Live_video")->alias('s')->where($where)->order("video_id desc")->limit($limit)->select();
				}

				$list = $this->deal_video_list($list, $mer_id);
				break;
		}

		$returnArr['list'] = $list;
		$returnArr['category'] = $category;
		$this->returnCode(0, $returnArr);
	}

	//上传图片
	public function upload_img(){
		$errors = [
            UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
            UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败'
        ];
        if ($_FILES['imgFile']['error'] === UPLOAD_ERR_OK) {
        	$filename = pathinfo($_FILES['imgFile']['name']);
        	if(empty($filename['extension'])){
        		switch ($_FILES['imgFile']['type']) {
        			case 'image/png':
        				$_FILES['imgFile']['name'] .= '.png';
        				break;
        			case 'image/jpg':
        				$_FILES['imgFile']['name'] .= '.jpg';
        				break;
        			case 'image/jpeg':
        				$_FILES['imgFile']['name'] .= '.jpg';
        				break;
        			case 'image/gif':
        				$_FILES['imgFile']['name'] .= '.gif';
        				break;
        		}
        	}
            $image = D('Image')->handle(0, 'live', 2, array('size' => 10), false);
            if ($image['error']) {
                $this->returnCode(1001, [], $image['message']);
            } else {
                $imageUrl = replace_file_domain($image['url']['imgFile']);
                $this->returnCode(0, ['url' => $imageUrl, 'path' => $image['url']['imgFile']]);
            }
        } else {
            $this->returnCode(1001, [], isset($errors[$_FILES['imgFile']['error']]) ? $errors[$_FILES['imgFile']['error']] : '上传失败');
        }
	}

	//区域级联接口(只取第三级)
	public function area(){
		$all_area = D("Area")->where(['is_open'=>1, 'area_type'=>['elt', 3]])->field("area_id,area_pid,area_name,area_type")->order("area_type asc,area_sort desc")->select();
		$parents = array_column($all_area, 'area_pid', 'area_id');//知道每个人的父亲是谁
		$areas = [];
		foreach ($all_area as $key => $value) {
			if($value['area_type'] == '1'){
				$areas[$value['area_id']] = [
					'area_id' => $value['area_id'],
					'area_name' => $value['area_name'],
					'son_list' => []
				];
			}
			elseif($value['area_type'] == '2'){
				if(isset($areas[$value['area_pid']])){
					$areas[$value['area_pid']]['son_list'][$value['area_id']] = [
						'area_id' => $value['area_id'],
						'area_name' => $value['area_name'],
						'son_list' => []
					];
				}
			}
			elseif($value['area_type'] == '3'){
				if(isset($areas[$parents[$value['area_pid']]]['son_list'][$value['area_pid']])){
					$areas[$parents[$value['area_pid']]]['son_list'][$value['area_pid']]['son_list'][] = [
						'area_id' => $value['area_id'],
						'area_name' => $value['area_name']
					];
				}
			}
		}
		foreach ($areas as $key => $value) {
			if(empty($value['son_list'])){
				unset($areas[$key]);
			}
			else{
				foreach ($areas[$key]['son_list'] as $key_city => $value_city) {
					if(empty($value_city['son_list'])){
						unset($areas[$key]['son_list'][$key_city]);
					}
				}
				$areas[$key]['son_list'] = array_values($areas[$key]['son_list']);
			}
		}
		$areas = array_values($areas);
		$this->returnCode(0, ['area' => $areas]);
	}

	//直播分类级联
	public function show_category(){
		$data = M("Live_show_category")->where(['is_del' => 0])->order("fid asc,orderby desc")->select();
		$categorys = [];
		foreach ($data as $key => $value) {
			if($value['fid'] == '0'){
				$categorys[$value['id']] = [
					'id' => $value['id'],
					'name' => $value['name'],
					'son_list' => []
				];
			}
			else{
				$categorys[$value['fid']]['son_list'][] = [
					'id' => $value['id'],
					'name' => $value['name']
				];
			}
		}
		foreach ($categorys as $key => $value) {
			if(!isset($value['name']) || empty($value['name'])){
				unset($categorys[$key]);
			}
		}
		$categorys = array_values($categorys);
		$this->returnCode(0, ['category' => $categorys]);
	}

	//创建直播-显示商品（图片）
	public function get_goods_img(){
		$goodids = I("goodids");
		if(empty($goodids)){
			$this->returnCode(1003, [], '商品IDS未传递');
		}
		$Model = D("Live_goods");
		$data = $Model->get_by_ids($goodids, ['img']);
		$this->returnCode(0, ['goods'=>$data]);
	}

	//创建直播-判断自有店铺
	public function check_own(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$return = [
			'check_result' => 0,
			'list' => [
				[
					'id' => 1,
					'text' => '自有店铺'
				],
				[
					'id' => 2,
					'text' => '商家联盟'
				],
			],
			'mer_id' => 0
		];
		$check = $this->check_mer();
		if(!$check){
			$this->returnCode(0, $return);
		}	
		
		$return['check_result'] = 1;
		$return['mer_id'] =  $check;
		$this->returnCode(0, $return);
	}

	//判断是不是自有商户
	private function check_mer(){
		$now_user = D("User")->get_user($this->_uid);
		if(empty($now_user['phone'])){
			return false;
		}
		//比对当前登录用户的手机号与商家手机号是否一致，如果一致，则包含自有店铺
		$where = [
			'status' => ['in', [1, 3]],
			'phone' => $now_user['phone']
		];
		$merchant = M("Merchant")->where($where)->field("mer_id")->find();
		if($merchant){
			return $merchant['mer_id'];
		}
		return false;
	}

	//创建直播-店铺列表
	public function store_list(){
		$page = I("page", '1');
		$limit = 10;//默认10条
		$mer_id = I("mer_id");
		$keyword = trim(I("keyword"));

		$where = [];
		if($mer_id){
			$where['s.mer_id'] = $mer_id;
		}
		if($keyword){
			$where['s.name'] = ['like', '%'.$keyword.'%'];
		}
		$where['g.store_id'] = ['neq', ''];

		$limit = $limit*($page-1).','.$limit;
		$store_list = M("Merchant_store")->alias("s")->join("left join ".C("DB_PREFIX")."live_goods as g on g.store_id=s.store_id")->where($where)->field("s.store_id,s.name,s.pic_info as img,s.mer_id")->limit($limit)->order("s.sort desc")->group('s.store_id')->select();
		import("@.ORG.store_image");
		$store_image = new store_image();

		$check = $this->check_mer();
		foreach ($store_list as $key => $value) {
			$store_list[$key]['img'] = $store_image->get_image_by_path($value['img']);
			$store_list[$key]['can_select'] = D("Live_goods")->get_store_goods_nums($value['store_id']);
			$store_list[$key]['is_own'] = 0;
			if($value['mer_id'] == $check){
				$store_list[$key]['is_own'] = 1;
			}
		}
		$this->returnCode(0, ['store_list' => $store_list]);
	}

	//创建直播-商品列表
	public function select_goods_list(){
		$page = I("page", '1');
		$limit = 10;//默认10条
		$mer_id = I("mer_id");
		$store_id = I("store_id");
		$keyword = trim(I("keyword"));
		$orderby = I("orderby", 1);//1=佣金降序 2=佣金升序 3=销量降序 4=销量升序 5=价格降序 6=价格升序
		$business = I("business", 'shop');

		$return = [
			'business' => [
				[
					'no' => 'shop',
					'current' => $business == 'shop' ? 1 : 0,
					'text' => $this->config['shop_alias_name']
				],
				[
					'no' => 'mall3',
					'current' => $business == 'mall3' ? 1 : 0,
					'text' => $this->config['mall_alias_name_new']
				],
				[
					'no' => 'group',
					'current' => $business == 'group' ? 1 : 0,
					'text' => $this->config['group_alias_name']
				],
                [
                    'no' => 'village_group',
                    'current' => $business == 'village_group' ? 1 : 0,
                    'text' => L_('社区团购')
                ],
			],
			'goods_list' => []
		];
		if($store_id){
            unset($return['business'][2]);
			unset($return['business'][3]);//店铺选择下面没有团购业务
			$return['business'] = array_values($return['business']);
		}

		$where = [];
		if($mer_id){
			$where['b.mer_id'] = $mer_id;
		}
		if($store_id){
			$where['b.store_id'] = $store_id;
		}
		$where['b.business'] = $business;
		$where['b.status'] = 1;
		$limit = $limit*($page-1).','.$limit;
		
		switch ($business) {
			case 'shop':
			case 'mall':
				if($keyword){
					$where['g.name'] = ['like', '%'.$keyword.'%'];
				}
				switch ($orderby) {
					case 1:
						$orderby = 'g.price*b.percent/100 desc';
						break;
					case 2:
						$orderby = 'g.price*b.percent/100 asc';
						break;
					case 3:
						$orderby = 'g.sell_count desc';
						break;
					case 4:
						$orderby = 'g.sell_count asc';
						break;
					case 5:
						$orderby = 'g.price desc';
						break;
					case 6:
						$orderby = 'g.price asc';
						break;
				}
				$data = M("Live_goods")->alias("b")->join("left join ".C("DB_PREFIX")."shop_goods as g on g.goods_id=b.business_id")->where($where)->field("b.id as goods_id,b.store_id,g.price,g.name,g.image as img,g.stock_num as stock,b.percent,g.status")->order($orderby)->limit($limit)->select();
				break;
			case 'mall3':
				if($keyword){
					$where['g.name'] = ['like', '%'.$keyword.'%'];
				}
				switch ($orderby) {
					case 1:
						$orderby = 'g.price*b.percent/100 desc';
						break;
					case 2:
						$orderby = 'g.price*b.percent/100 asc';
						break;
					case 3:
						$orderby = 'g.sale_num desc';
						break;
					case 4:
						$orderby = 'g.sale_num asc';
						break;
					case 5:
						$orderby = 'g.price desc';
						break;
					case 6:
						$orderby = 'g.price asc';
						break;
				}
				$data = M("Live_goods")->alias("b")->join("left join ".C("DB_PREFIX")."mall_goods as g on g.goods_id=b.business_id")->where($where)->field("b.id as goods_id,b.store_id,g.price,g.name,g.image as img,g.stock_num as stock,b.percent,g.status")->order($orderby)->limit($limit)->select();
				break;
			case 'group':
				import('@.ORG.group_image');
				$group_image_class = new group_image();
				if($keyword){
					$where['g.name'] = ['like', '%'.$keyword.'%'];
				}
				switch ($orderby) {
					case 1:
						$orderby = 'g.price*b.percent/100 desc';
						break;
					case 2:
						$orderby = 'g.price*b.percent/100 asc';
						break;
					case 3:
						$orderby = 'g.sale_count desc';
						break;
					case 4:
						$orderby = 'g.sale_count asc';
						break;
					case 5:
						$orderby = 'g.price desc';
						break;
					case 6:
						$orderby = 'g.price asc';
						break;
				}
				$data = M("Live_goods")->alias("b")->join("left join ".C("DB_PREFIX")."group as g on g.group_id=b.business_id")->where($where)->field("b.id as goods_id,b.store_id,g.price,g.s_name as name,g.pic as img,g.count_num as stock,g.sale_count,b.percent,g.status")->order($orderby)->limit($limit)->select();
				break;
			case 'village_group':
				if($keyword){
					$where['g.name'] = ['like', '%'.$keyword.'%'];
				}
				switch ($orderby) {
					case 1:
						$orderby = 'g.price*b.percent desc';
						break;
					case 2:
						$orderby = 'g.price*b.percent asc';
						break;
					case 3:
						$orderby = 'g.sale_count desc';
						break;
					case 4:
						$orderby = 'g.sale_count asc';
						break;
					case 5:
						$orderby = 'g.price desc';
						break;
					case 6:
						$orderby = 'g.price asc';
						break;
				}
				$data = M("Live_goods")
                    ->alias("b")
                    ->join("INNER JOIN ".C("DB_PREFIX")."village_group_goods as g on g.goods_id = b.business_id")
                    ->where($where)
                    ->field(
                        "b.id as goods_id,b.store_id,g.price,g.name,g.image as img,g.stock_num as stock,
                        g.sell_count sale_count,b.percent,g.status"
                    )
                    ->order($orderby)
                    ->limit($limit)
                    ->select();
				break;
			case 'meal':
				import('@.ORG.foodshop_goods_image');
				$foodshop_image_class = new foodshop_goods_image();
				if($keyword){
					$where['g.name'] = ['like', '%'.$keyword.'%'];
				}
				switch ($orderby) {
					case 1:
						$orderby = 'g.price*b.percent/100 desc';
						break;
					case 2:
						$orderby = 'g.price*b.percent/100 asc';
						break;
					case 3:
						$orderby = 'g.sell_count desc';
						break;
					case 4:
						$orderby = 'g.sell_count asc';
						break;
					case 5:
						$orderby = 'g.price desc';
						break;
					case 6:
						$orderby = 'g.price asc';
						break;
				}
				$data = M("Live_goods")->alias("b")->join("left join ".C("DB_PREFIX")."foodshop_goods as g on g.goods_id=b.business_id")->where($where)->field("b.id as goods_id,b.store_id,g.price,g.name,g.image as img,g.stock_num as stock,b.percent")->order($orderby)->limit($limit)->select();
				break;
		}

		foreach ($data as $key => $value) {
		    $data[$key]['offline'] = 0;
			if($business == 'group'){
				if(strpos($value['img'],';') !== false){
					$value['img'] = explode(';', $value['img'])[0];
				}
				$data[$key]['img'] = $group_image_class->get_image_by_path($value['img'])['image'];
                if($value['status'] != 1){
                    $data[$key]['offline'] = 1;
                }
				$data[$key]['stock'] = $value['stock'] <= 0 ? -1 : ($value['stock'] - $value['sale_count'] >= 0 ? $value['stock'] - $value['sale_count'] : 0);
			}
			elseif($business == 'meal'){
				$data[$key]['img'] = $foodshop_image_class->get_image_by_path($value['img'],'s');
			}
			else{
				$imgs = $value['img'] ? explode(';', $value['img']) : [];
				if($imgs[0]){
					$data[$key]['img'] = replace_file_domain($imgs[0]);
				}
				if($value['status'] != 1){
                    $data[$key]['offline'] = 1;
                }
			}
			$data[$key]['img'] = $data[$key]['img'] ? thumb_img($data[$key]['img'],'240','240','fill') : $this->config['site_url'].'/static/images/live_goods_default.png';
			$data[$key]['commission'] = $value['price']*$value['percent']/100;
		}
		$return['goods_list'] = $data;
		$this->returnCode(0, $return);
	}

	//创建直播-提交
	public function create_show(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$thumbimg_1 = I("thumbimg_1");
		$thumbimg_2 = I("thumbimg_2");
		$nickname = trim(I("nickname"));
		$title = trim(I("title"));
		$desc = trim(I("desc"));
		$estimate_time = I("estimate_time");
		$category_1 = I("category_1");
		$category_2 = I("category_2");
		$area_id = I("area_id");
		$goodids = I("goodids");
		$lng = I("lng");
		$lat = I("lat");
		if(empty($thumbimg_1) || empty($thumbimg_2)){
			$this->returnCode(1003, [], '封面图必须上传哦');
		}
		if(empty($nickname)){
			$this->returnCode(1003, [], '必需要一个直播昵称');
		}
		if(empty($title)){
			$this->returnCode(1003, [], '必需要一个直播标题');
		}
		if (empty($estimate_time) && C('config.close_estart_time') == 0) {//close_estart_time=0开启直播预开始时间
			 $this->returnCode(1003, [], '需要一个预计开播时间');
		}
		if(empty($category_1) || empty($category_2)){
			$this->returnCode(1003, [], '分类必填');
		}
		if(empty($goodids)){
			$this->returnCode(1003, [], '请选择直播宝贝');
		}

		if($lng && $lat){// 通过经纬度获取区域id
			$area = D('Area')->cityMatching($lat, $lng);
			if($area['area_info']['area_id']){
				$area_id = $area['area_info']['area_id'];
			}
		}

		$Model = D("Live_show");
		$data = [
			'uid' => $this->_uid,
			'live_nickname' => $nickname,
			'thumbimg_1' => $thumbimg_1,
			'thumbimg_2' => $thumbimg_2,
			'title' => $title,
			'desc' => $desc ? : "",
			'estimate_time' => $estimate_time,
			'real_start_time' => '00-00-00 00:00:00',
			'real_end_time' => '00-00-00 00:00:00',
			'is_playback' => 1,
			'is_comment' => 1,
			'category_1' => $category_1,
			'category_2' => $category_2,
			'area_id' => $area_id ? : 0,
			'lng' => $lng,
			'lat' =>$lat,
			'address' =>I("address") ? : '',
			'create_time' => date('Y-m-d H:i:s'),
			'is_del' => 0,
			'zan' => 0
		];
		$insert_id = $Model->data($data)->add();
		if($insert_id){
			$live_show_no = 100000 + $insert_id;
			$Model->where(['live_id'=>$insert_id])->data(['live_no'=> $live_show_no])->save();//更新他的房间号
			$goods_arr = explode(',', $goodids);
			for($i = 0 ; $i < count($goods_arr); $i++){
				$add = [
					'live_id' => $insert_id,
					'goods_id' => $goods_arr[$i],
					'is_del' => 0,
					'no' => $i+1,
					'recording' => 0
				];
				M("Live_show_goods")->data($add)->add();
			}

			//给关注着发消息
			$guanzhu = M("Live_follow")->where(['follow_uid'=>$this->_uid, 'is_del'=>0])->field("uid")->select();
			if($guanzhu){
				$users = M("User")->where(['uid'=>['in', array_column($guanzhu, 'uid')]])->field('openid')->select();
				foreach ($users as $u) {
					if($u['openid'] && strpos($u['openid'], 'no_use') === false){
						$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			            $href = C('config.site_url'). D('House_village')->base_url ."pages/live_and_video/live/viewer/main_page?live_id=".$insert_id;
			            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $u['openid'],'first' => '您关注的'.$nickname.'主播有新的直播上线，点击立即参与直播~', 'keyword1' => $title, 'keyword2' => '将于'.$estimate_time.'开始直播', 'keyword3' => '', 'keyword4' => $data['live_nickname'], 'remark' => '\n请点击查看详细信息！'));
					}
				}
			}

			$this->returnCode(0, ['submit' => 1, 'live_id' => $insert_id]);
		}
		else{
			$this->returnCode(1004, [], '创建失败!');
		}
	}

	//实名认证判断
	public function check_authen(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$type = I("type", 'liveshow');
		$now_user = D("User")->get_user($this->_uid);
		if($type == 'liveshow' && $now_user['open_live_show'] == '0'){
			$this->returnCode(1001, [], '您没有开启直播权限，请联系管理员');
		}
		if($type == 'livevideo' && $now_user['open_live_video'] == '0'){
			$this->returnCode(1001, [], '您没有开启短视频权限，请联系管理员');
		}
		$this->returnCode(0, ['check_result' => $this->check_auth()]);
	}


	//直播列表-推荐、关注
	public function show_list(){
		$type = I("type", 1);//1=推荐 2=关注
		$page = I("page", 1);
		$mer_id = I("mer_id", 0);
		if($type == 2 && empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
        $return['slider'] = D("Adver")->get_adver_by_key("live_show_list_slider", 6);
        $return['live_show_list'] = [];
        // 搜索条件
        $where['s.is_del'] = 0;
        $return['zhibo_version'] = empty($this->config['zhibo_version'])?0:$this->config['zhibo_version']*1;//直播样式，1新版样式
        if($return['zhibo_version']){
            $long = I("lng", 0);//经度
            $lat = I("lat", 0);//纬度
            if(empty($long) || empty($lat)){
                $now_city=I("now_city", 0);//城市
                //城市经纬度
                $area_where=['area_id'=>$now_city];
                $city_info = M("Area")->where($area_where)->find();
                $long=$city_info['area_lng'];
                $lat=$city_info['area_lat'];
            }
            $nember_6 = 6378.138 * 2;
            $lat_pi = $lat* pi() / 180;
            $lnt_pi = $long * pi() / 180;

            $return['header']=[
                [
                    'type'=>1,
                    'name'=>'推荐',
                    'video_id'=>0,
                    'author_id'=>0
                ],
                [
                    'type'=>3,
                    'name'=>'附近',
                    'video_id'=>0,
                    'author_id'=>0
                ],
                [
                    'type'=>2,
                    'name'=>'关注',
                    'video_id'=>0,
                    'author_id'=>0
                ],
                [
                    'type'=>4,
                    'name'=>'直播',
                    'video_id'=>0,
                    'author_id'=>0
                ],
            ];

            $return['bottom']=[
                [
                    'show_type'=>'homeIndex',
                    'name'=>'首页',
                    'url'=>'',
                ],
                [
                    'show_type'=>'live',
                    'name'=>'直播',
                    'url'=>'',
                ],
                [
                    'show_type'=>'shortVideo',
                    'name'=>'短视频',
                    'url'=>'',
                ],
                [
                    'show_type'=>'message',
                    'name'=>'消息',
                    'url'=>build_store_im_conversation_url('user_'.$this->_uid,'user2user'),
                ],
                [
                    'show_type'=>'personalCenter',
                    'name'=>'个人中心',
                    'url'=>'',
                ],
            ];
            $limit='0,1';
            $arr_type=[1,2,3,4];//各种状态
            foreach ($arr_type as $k=>$v){
                switch ($v){
                    case 1://推荐
                        // 首页分类信息
                        $show_list = D("Live_video")->alias("s")->where($where)->order("s.video_id desc")->limit($limit)->select();

                        $return['header'][0]['video_id']=$show_list[0]['video_id'];
                        //$return['header'][0]['author_id']=$show_list[0]['uid'];
                        break;
                    case 2://关注
                        $where['f.is_del'] = 0;
                        $where['f.uid'] = $this->_uid;
                        if($mer_id){
                            $where['g.mer_id'] = $mer_id;
                            $where['bg.is_del'] = 0;
                            $show_list = M("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_video as s on s.uid=f.follow_uid  LEFT JOIN '.C('DB_PREFIX').'live_show_goods as bg on bg.live_id = s.live_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id")->where($where)->field("s.*")->order("s.video_id asc")->limit($limit)->select();
                        }else{
                            $show_list = M("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_video as s on s.uid=f.follow_uid ")->where($where)->order("s.video_id asc")->limit($limit)->select();
                        }
                        if(!empty($show_list)){
                            $return['header'][2]['video_id']=$show_list[0]['video_id'];
                            //$return['header'][2]['author_id']=$show_list[0]['uid'];
                        }
                        break;
                    case 3://附近
                        /*$dis="ROUND({$nember_6}* ASIN(SQRT(POW(SIN(({$lat_pi}-`s`.`lat`*PI()/180)/2),2)+COS({$lat_pi})*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lnt_pi}-`s`.`lng`*PI()/180)/2),2))))<=10";
                        $where1=[$dis,'s.is_del'=>0];
                        $show_list = D("Live_video")->alias("s")->field("s.*,ROUND({$nember_6}* ASIN(SQRT(POW(SIN(({$lat_pi}-`s`.`lat`*PI()/180)/2),2)+COS({$lat_pi})*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lnt_pi}-`s`.`lng`*PI()/180)/2),2)))) AS distance")->where($where1)->order("distance asc")->limit($limit)->select();
                        */
                        unset($where['f.is_del']);
                        unset($where['f.uid']);
                        $show_list = D("Live_video")->alias("s")->where($where)->order("s.video_id asc")->limit($limit)->select();
                        if(!empty($show_list)){
                            $return['header'][1]['video_id']=$show_list[0]['video_id'];
                            //$return['header'][1]['author_id']=$show_list[0]['uid'];
                        }
                        break;
                    default:
                        unset($where['f.is_del']);
                        unset($where['f.uid']);
                        $show_list = D("Live_show")->alias("s")->field("s.*")->where($where)->order("live_id desc")->limit($limit)->select();
                        if(!empty($show_list)){
                            $return['header'][3]['video_id']=$show_list[0]['live_id'];
                            //$return['header'][3]['author_id']=$show_list[0]['uid'];
                        }
                        break;
                }
            }
        }
        $limit = 10;//默认10条
        $limit = ($limit*($page-1)).','.$limit;
      /*  else{*/
            $id = I("id", 0);//分类id
            if($type == 1){
                // 首页分类信息
                if($id){
                    $return['slider'] = [];
                    $whereCategory = ['id'=>$id];
                    $category = D('Live_category')->where($whereCategory)->find();
                    if(empty($category) || empty($category['categorys'])){
                        $this->returnCode(0, $return);
                    }

                    // 绑定的直播分类id或短视频话题id
                    $categorys = explode(',', trim($category['categorys'],','));

                    $where['s.category_1'] = ['in',$categorys];

                }

                if($mer_id){//私域入口 即A商家私域入口进入后 只能浏览 包含A商家商品的视频（视频列表页，和 看视频时前后滑动视频去到上一个 下一个视频 ）

                    $where['g.mer_id'] = $mer_id;
                    $where['bg.is_del'] = 0;
                    $show_list = D("Live_show")->alias("s")->field('s.*')->join(' LEFT JOIN '.C('DB_PREFIX').'live_show_goods as bg on bg.live_id = s.live_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($where)->order("s.status asc,s.live_id desc")->limit($limit)->group('s.live_id')->select();
                }else{
                    $show_list = D("Live_show")->alias("s")->where($where)->order("s.status asc,live_id desc")->limit($limit)->select();
                }
            }
            else{
                $where['f.is_del'] = 0;
                $where['f.uid'] = $this->_uid;
                if($mer_id){
                    $where['g.mer_id'] = $mer_id;
                    $where['bg.is_del'] = 0;
                    $show_list = M("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_show as s on s.uid=f.follow_uid  LEFT JOIN '.C('DB_PREFIX').'live_show_goods as bg on bg.live_id = s.live_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id")->where($where)->field("s.*")->order("s.status asc")->limit($limit)->select();
                }else{
                    $show_list = M("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_show as s on s.uid=f.follow_uid ")->where($where)->order("s.status asc")->limit($limit)->select();
                }
            }
            foreach ($show_list as $key => $value) {
                $show_list[$key]['img'] = replace_file_domain($value['thumbimg_1']);
                $show_list[$key]['img2'] = replace_file_domain($value['thumbimg_2']);
                $show_list[$key]['nums'] = M("Live_show_room_user")->where(['live_show_id'=>$value['live_id']])->count()+$value['live_virtual_views'];//在线人数
                $zhubo = D("User")->get_user($value['uid']);
                $show_list[$key]['avatar'] = $zhubo['avatar'];
                $show_list[$key]['nickname'] = $value['live_nickname'];
                $show_list[$key]['remind'] = 0;
                if($type == 1){
                    $show_list[$key]['follow'] = M("Live_follow")->where(['uid' => $this->_uid, 'follow_uid'=> $value['uid'], 'is_del'=>0])->find() ? 1 : 0;
                }
                else{
                    $show_list[$key]['follow'] = 1;//已关注
                }
                $area = D("Area")->where(['area_id'=>$value['area_id']])->field("area_pid,area_name")->find();
                $parent = D("Area")->name($area['area_pid']);
                $show_list[$key]['area'] = $parent == $area['area_name'] ? $area['area_name'] : $parent.$area['area_name'];
                $show_list[$key]['estimate_time'] = $this->deal_estimate_time($value['estimate_time']);

                $goodids = implode(',',array_column(M("Live_show_goods")->where(['live_id'=>$value['live_id'], 'is_del'=>0])->field("goods_id")->select(), 'goods_id'));

                $show_list[$key]['goods_list'] = D("Live_goods")->get_by_ids($goodids, ['img', 'price', 'name']);
                $show_list[$key]['zan'] = $value['zan'] > 9999 ? ($value['zan']/10000)."万" : $value['zan'];
                $show_list[$key]['play_url'] = D("Live_show")->get_play_url($value['live_no']);//播放地址
            }
            $return['live_show_list'] = $show_list;
        /*}*/

		$this->returnCode(0, $return);
	}

	//公共的处理预告时间的方法
	private function deal_estimate_time($estimate){
		$estimate_time = strtotime($estimate);
		if(!$estimate_time){
			return "未知";
		}
		$today_night = strtotime(date('Y-m-d 23:59:59'));
		if($estimate_time > $today_night && $estimate_time < ($today_night+86400)){//在明天
			return "明天 ".date('H:i',$estimate_time);
		}
		elseif($estimate_time > time() && $estimate_time < $today_night){
			return "今天 ".date('H:i',$estimate_time);	
		}
		else{
			return date('m月d号 H:i', $estimate_time);
		}
	}

	//主播与作者主页
	public function my_index(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$where['u.uid']=array('gt',0);
		$follow=D("Live_follow")->alias("s")->field('u.nickname,u.uid,u.avatar')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'user as u on u.uid = s.follow_uid')->where(['s.uid'=>$this->_uid, 's.is_del'=>0,'u.uid'=>array('gt',0)])
                ->group('s.follow_uid')
                ->select();
		
		$fans = D("Live_follow")->alias("s")->field('u.nickname,u.uid,u.avatar')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'user as u on u.uid = s.uid')->where(['s.follow_uid'=>$this->_uid, 's.is_del'=>0,'u.uid'=>array('gt',0)])
                ->group('s.uid')
                ->select();
		$zhubo = D("User")->get_user($this->_uid);
		$my_info = [
			'nickname' => $zhubo['nickname'],
			'avatar' => $zhubo['avatar'],
			'zan' => M("Live_show")->where(['uid'=>$this->_uid])->sum("zan"),
			'follow' => count($follow),//关注数
            'fans'=>count($fans),//粉丝数
			'authen' => $this->check_auth(),
			'auth' => [
				'live_show' => $zhubo['open_live_show'] == '1' ? 1 : 0,
				'live_video' => $zhubo['open_live_video'] == '1' ? 1 : 0,
			]
		];
		$my_info['zan'] = $my_info['zan'] ? : 0;
		$url_link = [
			'my_commission_url' => $this->config['site_url']."/wap.php?g=Wap&c=My&a=my_money",
			'my_order_url' => ""
		];
		
		$appConfig  =   D('Appapi_app_config')->get();
		$url_link['android_app_url'] = $appConfig['android_download_url'] ? : '';
		$url_link['ios_app_url'] = $appConfig['ios_download_url'] ? : '';

		$this->returnCode(0, ['my_info' => $my_info, 'url_link' => $url_link]);		
	}

    /**
     * 关注列表粉丝列表
     */
	public function follow_list(){
        if(empty($this->_uid)){
            $this->returnCode(1002, [], '未登录状态不可访问该接口');
        }
        $type = I("type", 1);//1=关注列表 2=粉丝列表
        $content=I("content", "");
        $page = I("page", 1);//页码
        $pageSize = I("pageSize", 10);//每页数量
        $limit = $pageSize*($page-1).','.$pageSize;

        $where_follow=['s.uid'=>$this->_uid,'s.is_del'=>0];
        $out['follow_num']=$this->retNum($where_follow)*1;

        $where_fans=['s.follow_uid'=>$this->_uid,'s.is_del'=>0];
        $out['fans_num']=$this->retNum($where_fans)*1;
        $where1['u.uid']=$where['u.uid']=array('gt',0);
        if($type==1){//关注列表
            $where['s.uid']=$this->_uid;
            $where['s.is_del']=0;
            if(!empty($content)){
                $where_follow['u.nickname']=$where['u.nickname']=array("like",'%'.$content.'%');
                $out['follow_num']=$this->retNum($where_follow)*1;
            }
            $follow_list = D("Live_follow")->alias("s")->field('u.nickname,u.uid,u.avatar')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'user as u on u.uid = s.follow_uid')->where($where)
                ->limit($limit)
                ->group('s.follow_uid')
                ->select();
        }else{//粉丝列表
            $where1['s.follow_uid']=$this->_uid;
            $where1['s.is_del']=0;
            if(!empty($content)){
                $where_fans['u.nickname']=$where1['u.nickname']=array("like",'%'.$content.'%');
                $out['fans_num']=$this->retNum($where_fans)*1;
            }
            $follow_list = D("Live_follow")->alias("s")->field('u.nickname,u.uid,u.avatar')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'user as u on u.uid = s.uid')->where($where1)
                ->limit($limit)
                ->group('s.uid')
                ->select();
        }
        $user_arr=[];
        $out['show_list']=[];
        if(!empty($follow_list)){
            foreach ($follow_list as $k=>$v){
                 $avatar=M('User')->where(['uid'=>$v['uid']])->find();
                 $arr['uid']=$v['uid'];
                 $arr['name']=$v['nickname'];
                 $arr['avatar']=empty($avatar)?"":replace_file_domain($avatar['avatar']);
                /* if($type==1){*/
                     $arr['status']= D("Live_follow")->where(['uid'=>$this->_uid, 'is_del'=>0, 'follow_uid'=>$v['uid']])->find() ? 1 : 0;
                 /*}else{
                     $arr['status']= D("Live_follow")->where(['uid'=>$this->_uid, 'is_del'=>0, 'follow_uid'=>$v['uid']])->find() ? 1 : 0;
                 }*/
                 $user_arr[]=$arr;
            }
        }
        $out['show_list']=$user_arr;
        $this->returnCode(0, $out);
    }

    /**
     * @param $where
     * 返回粉丝关注数量
     */
    public function retNum($where){
        $follow_num=D("Live_follow")->alias("s")->field('u.nickname,u.uid,u.avatar')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'user as u on u.uid = s.uid')->where($where)
            ->count();
        return $follow_num;
    }
    /**
     * 搜索返回列表
     */
    public function search_list(){
        $type = I("type", 1);//1=推荐 2=关注 3=附近 4=直播
        $content = I("content", '我的');
        if(empty($content)){
            $this->returnCode(0, []);
        }
        $where = [
            's.is_del' => 0,
            's.title'=>array('like','%'.$content.'%')
        ];
        $list=[];
        switch ($type){
            case 1://推荐
                    $show_list = D("Live_video")->alias("s")->where($where)->order("s.video_id desc")->select();
                break;
            case 2://关注
                $where2['f.is_del'] = 0;
                $where2['f.uid'] = $this->_uid;
                $where2['s.title'] = array('like','%'.$content.'%');
                $show_list = D("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_video as s on s.uid=f.follow_uid ")->where($where2)->order("s.video_id asc")->select();
                break;
            case 3://附近
                /*$long = I("lng", 0);//经度
                $lat = I("lat", 0);//纬度
                if(empty($long) || empty($lat)){
                    $now_city=I("now_city", 0);//城市
                    //城市经纬度
                    $area_where=['area_id'=>$now_city];
                    $city_info = M("Area")->where($area_where)->find();
                    $long=$city_info['area_lng'];
                    $lat=$city_info['area_lat'];
                }
                $nember_6 = 6378.138 * 2;
                $lat_pi = $lat* pi() / 180;
                $lnt_pi = $long * pi() / 180;*/

                /*$dis="ROUND({$nember_6}* ASIN(SQRT(POW(SIN(({$lat_pi}-`s`.`lat`*PI()/180)/2),2)+COS({$lat_pi})*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lnt_pi}-`s`.`lng`*PI()/180)/2),2))))<=10";
                $where1=[$dis,'s.is_del'=>0,'s.title'=>array('like','%'.$content.'%')];
                $show_list = D("Live_video")->alias("s")->field("s.*,ROUND({$nember_6}* ASIN(SQRT(POW(SIN(({$lat_pi}-`s`.`lat`*PI()/180)/2),2)+COS({$lat_pi})*COS(`s`.`lat`*PI()/180)*POW(SIN(({$lnt_pi}-`s`.`lng`*PI()/180)/2),2)))) AS distance")->where($where1)->order("distance asc")->select();
                */
                $show_list = D("Live_video")->alias("s")->where($where)->order("s.video_id desc")->select();
                break;
            default:
                unset($where['f.is_del']);
                unset($where['f.uid']);
                $show_list = D("Live_show")->alias("s")->field("s.*")->where($where)->order("live_id asc")->select();
                break;
        }
        if(!empty($show_list)){
            foreach ($show_list as $k=>$v){
                if($type==4){
                    $arr['live_id']=$v['live_id'];
                }else{
                    $arr['live_id']=$v['video_id'];
                }
                $arr['title']=$v['title'];
                $list[]=$arr;
            }
        }
        $this->returnCode(0, $list);
    }
    /**
     * 热榜
     */
    public function hot_list(){
        $page = I("page", '1');
        $limit = 10;//默认10条

        $where = [
            'is_del' => 0,
        ];
        $list=[];
        $limit = $limit*($page-1).','.$limit;
        $show_list = D("Live_video")->where($where)->order("zan desc")->limit($limit)->select();
        if(!empty($show_list)){
            foreach ($show_list as $k=>$v){
                $arr['live_id']=$v['video_id'];
                $arr['title']=$v['title'];
                $arr['type']=1;
                $arr['zan']=$v['zan']>=10000 ? $v['zan']/10000 .'w' : $v['zan'];
                $list[]=$arr;
            }
        }
        $this->returnCode(0, $list);
    }
    //判断实名认证
	private function check_auth(){
		$where['uid'] = $this->_uid;
		$authen = M('User_authentication')->where($where)->find();
		if(empty($authen)){
			return 3;
		}
		if($authen['authentication_status'] == 2){
			return 4;
		}
		elseif($authen['authentication_status'] == 0){
			return 2;
		}
		else{
			return 1;
		}
	}

	//我的直播列表
	public function my_live_show(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$page = I("page", '1');
		$limit = 10;//默认10条

		$where = [
			'is_del' => 0,
			'uid' => $this->_uid
		];
		
		$limit = $limit*($page-1).','.$limit;
		$show_list = D("Live_show")->where($where)->order("create_time desc")->limit($limit)->select();
		foreach ($show_list as $key => $value) {
			$show_list[$key]['img'] = replace_file_domain($value['thumbimg_1']);
			$show_list[$key]['category'] = [M("Live_show_category")->where(['id'=>$value['category_1']])->getField("name"),M("Live_show_category")->where(['id'=>$value['category_2']])->getField("name")];
			$show_list[$key]['estimate_time'] = $this->deal_estimate_time($value['estimate_time']);
			
			$show_list[$key]['goods_nums'] = M("Live_show_goods")->where(['live_id'=>$value['live_id'], 'is_del'=>0])->count();
			$show_list[$key]['duration'] = 0;
			if(strtotime($value['real_start_time']) && strtotime($value['real_end_time'])){
				$show_list[$key]['duration'] = strtotime($value['real_end_time']) - strtotime($value['real_start_time']);
			}
			$show_list[$key]['video_url'] = '';
			if($value['record_status']){
				$record_result = unserialize($value['record_result']);
				if($record_result['video_url']){
					$show_list[$key]['video_url'] = $record_result['video_url'];
				}
			}
		}
		$return['live_show_list'] = $show_list;
		$this->returnCode(0, $return);
	}

	//删除我的直播
	public function del_my_live_show(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}

		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();

		if($now_live['uid'] != $this->_uid || empty($now_live)){
			$this->returnCode(1003, [], '无权删除该直播');
		}

		M("Live_show")->where(['live_id' => $live_id])->data(['is_del' => 1])->save();
		$this->returnCode(0, ['del' => 1]);
	}

	//开始直播（获取推流地址）
	public function begin_push(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		if($now_live['uid'] != $this->_uid || empty($now_live)){
			$this->returnCode(1003, [], '无权开始该直播');
		}
		if($now_live['status'] == '3'){
			$this->returnCode(1003, [], '当前直播不在预告中，不允许请求此接口');
		}

		$output = [
			'push_url' => D("Live_show")->get_push_url($now_live['live_no']),//推流地址
			'anchor_usersig' => '',//主播usersig
		];

		//修改主表
		$save = [
			'status' => 1,
			'real_start_time' => date('Y-m-d H:i:s')
		];

		import("@.ORG.TencentIM");
		$im = new TencentIM();
		if($now_live['status'] == '2'){
			//开IM聊天室
			$group = $im->create_group($now_live['live_no']);
			if($group['errcode']){
				$this->returnCode(1001, [], $group['errmsg']);
			}
			$save['im_group_id'] = $group['group_id'];

			//开始录制视频
			// import("@.ORG.TencentLive");
			// $live = new TencentLive();
			// $record_result = $live->create_live_record($now_live['live_no'], 0, date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime("+1 day")), 'liveno='.$now_live['live_no'].'&type=1');//默认一天总够了吧
			// if($record_result['errcode']){
			// 	$this->returnCode(1001, [], $record_result['errmsg']);
			// }
			// $save['record_taskid'] = $record_result['retdata'];
			// 这里先注释， 让腾讯云后台设置的录制模板来自动录制，因为那边的录制模板会把断流/分片的视频做统一整合，如果这里创建录制任务，那就跟那里重复了，但如果腾讯云后台没有配置录制模板，就不会有回放视频

			D("Live_show")->where(['live_id'=>$live_id])->data($save)->save();
			

		}
		elseif($now_live['status'] == '1'){
			$group['group_id'] = $now_live['live_no'];
			$send = $im->send_system_notify($now_live['im_group_id'], "begin_push");
		}

		//得到主播usersig，用于主播登录IM
		$im_userid = $now_live['live_no'].$this->_uid;
		$output['anchor_usersig'] = $im->get_usersig($im_userid);//用房间号和uid来作为用户唯一标识
		$output['anchor_userid'] = $im_userid;//用房间号和uid来作为用户唯一标识
		$output['im_group_id'] = $group['group_id'];

		//把商户下面的所有店铺都变成“直播中”
		$current_mer_id = $this->check_mer();
		if($current_mer_id){
			$stores = M("Merchant_store")->where(['mer_id'=>$current_mer_id])->data(['current_liveshow_id'=>$live_id])->save();
			$live_goods = D("Live_show_goods")->get_goods_by_liveid($live_id);
			D("Live_goods")->update_goods_live_status(array_column($live_goods, 'goods_id'), $live_id);
		}
		
		$this->returnCode(0, $output);
	}

	//主播端-直播详情页
	public function author_liveshow_detail(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$now_user = D("User")->get_user($now_live['uid']);
		
		$output = [
			'basic_info' => [//基础信息
				"live_id" => $live_id,
				"area" => "",
				'live_no' => $now_live['live_no'],
				'status' => $now_live['status'],
				'estimate_time' => strtotime($now_live['estimate_time']),
				'estimate_time_txt' => $this->deal_estimate_time($now_live['estimate_time']),
				'online_nums' => 0,
				'watch_nums' => 0,
				'zan' => $now_live['zan'],
				'title' => $now_live['title'],
				'nickname' => $now_live['live_nickname'],
				'goods_nums' => 0,
				'img_1' => replace_file_domain($now_live['thumbimg_1']),
				'img_2' => replace_file_domain($now_live['thumbimg_2']),
				'author_img' => $now_user['avatar'],
				'video_url' => "",
                'forbid_status' => $now_live['forbid_status']
			],
			'update_info' => [
				'thumbimg_1' => $now_live['thumbimg_1'],
				'thumbimg_2' => $now_live['thumbimg_2'],
				'img_1' => replace_file_domain($now_live['thumbimg_1']),
				'img_2' => replace_file_domain($now_live['thumbimg_2']),
				'nickname' => $now_live['live_nickname'],
				'title' => $now_live['title'],
				'desc' => $now_live['desc'],
				'estimate_time' => date('Y-m-d H:i',strtotime($now_live['estimate_time'])),
				'category_1' => $now_live['category_1'],
				'category_2' => $now_live['category_2'],
				'category_txt' => [M("Live_show_category")->where(['id'=>$now_live['category_1']])->getField("name"),M("Live_show_category")->where(['id'=>$now_live['category_2']])->getField("name")],
				'area_id' => $now_live['area_id'],
				'lng' => $now_live['lng'],
				'lat' => $now_live['lat'],
				'address' => $now_live['address'],
				'area_name' => [],
				'goodsids' => [],
			]
		];
		if($now_live['record_status']){
			$record_result = unserialize($now_live['record_result']);
			if($record_result['video_url']){
				$output['basic_info']['video_url'] = $record_result['video_url'];
			}
		}
		$output['basic_info']['online_nums'] = D("Live_show_room_user")->get_user_num($live_id);
		$output['basic_info']['watch_nums'] = D("Live_show_room_user")->get_user_num($live_id,'viewer');
		$output['basic_info']['goods_nums'] = M("Live_show_goods")->where(['live_id'=>$live_id, 'is_del'=>0])->count();
		$area = D("Area")->where(['area_id'=>$now_live['area_id']])->field("area_pid,area_name")->find();
		$parent = D("Area")->name($area['area_pid']);
		$output['basic_info']['area'] = $parent == $area['area_name'] ? $area['area_name'] : $parent.$area['area_name'];
		$output['update_info']['area_name'] = $parent == $area['area_name'] ? [$area['area_name']] : [$parent,$area['area_name']];
		$output['update_info']['goodsids'] = array_column(D("Live_show_goods")->get_goods_by_liveid($live_id), 'goods_id');

		$this->returnCode(0, $output);
	}

	//主播端-直播商品list
	public function show_goods_list(){
		$live_id = I("live_id");
		$mer_id = I("mer_id",0);
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$type = I("type", 1);
		$order = $type == 1 ? "no asc" : "no desc";
		$data = D("Live_show_goods")->get_goods_by_liveid($live_id, $order);
		$goods = D("Live_goods")->get_by_ids(array_column($data, 'goods_id'), ['img','name','price','stock'], false);
		$output = [
			'goods_list' => []
		];
		$sales = array_column($data, 'sale_nums', 'goods_id');
		foreach ($data as $key => $value) {
			if($mer_id && $goods[$value['goods_id']]['mer_id'] != $mer_id){
				continue;
			}
			if(empty($goods[$value['goods_id']])){
				continue;
			}
			$temp = [
				'no' => $value['no'],
				'id' => $value['goods_id'],
				'img' => $goods[$value['goods_id']]['img'],
				'name' => $goods[$value['goods_id']]['name'],
				'price' => $goods[$value['goods_id']]['price'],
				'stock' => $goods[$value['goods_id']]['stock'],
				'store_name' => $goods[$value['goods_id']]['store_name'],//店铺名称
				'address' => $goods[$value['goods_id']]['address'],//店铺地址
				'long' => $goods[$value['goods_id']]['long'],//店铺经度
				'lat' => $goods[$value['goods_id']]['lat'],//店铺维度
				'discount' => D("Card_new_coupon")->check_has_live_coupon($goods[$value['goods_id']]['mer_id'],$goods[$value['goods_id']]['store_id'],$goods[$value['goods_id']]['mer_id'],$goods[$value['goods_id']]['business']),
				'recording' => $value['recording'],
				'commission' => 0,
				'sales' => $sales[$value['goods_id']]['sale_nums'],
                'offline' => $goods[$value['goods_id']]['offline'],
				'business' => $goods[$value['goods_id']]['business'],
			];
			//统计佣金
			//外卖+商城
			if($goods[$value['goods_id']]['business'] == 'shop' || $goods[$value['goods_id']]['business'] == 'mall'){
				$temp['commission'] = M("Shop_order_detail")->where(['goods_id'=>$goods[$value['goods_id']]['real_goods_id'], 'is_liveshow'=>1, 'liveshow_id'=>$live_id])->sum("liveshow_money");
			}
			elseif($goods[$value['goods_id']]['business'] == 'mall3'){
				$temp['commission'] = M("Mall_order_detail")->where(['goods_id'=>$goods[$value['goods_id']]['real_goods_id'], 'is_liveshow'=>1, 'liveshow_id'=>$live_id])->sum("live_author_money");
			}
			elseif($goods[$value['goods_id']]['business'] == 'group'){
				$temp['commission'] = M("Group_order")->where(['group_id'=>$goods[$value['goods_id']]['real_goods_id'], 'is_liveshow'=>1, 'liveshow_id'=>$live_id])->sum("live_author_money");
			}elseif($goods[$value['goods_id']]['business'] == 'village_group'){
                $temp['commission'] = M("Village_group_order_detail")->where(['goods_id'=>$goods[$value['goods_id']]['goods_id'], 'is_liveshow'=>1, 'liveshow_id'=>$live_id])->sum("live_commission") ?: 0;
            }
			$temp['video_url'] = "";//视频地址
			if($value['record_status'] == 1 && $value['recording'] == 1){
				$record_result = unserialize($value['record_result']);
				if($record_result['video_url']){
					$temp['video_url'] = $record_result['video_url'];
				}
			}
			$output['goods_list'][] = $temp;
		}
		$output['goods_nums'] = count($output['goods_list']);
		$output['commission'] = array_sum(array_column($output['goods_list'], 'commission'));
		$output['sales_nums'] = array_sum(array_column($output['goods_list'], 'sales'));
		$this->returnCode(0, $output);
	}

	//主播端-商品优惠券list
	public function goods_discount_list(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			$this->returnCode(1003, [], 'goods_id未填写');
		}
		$real_goods_info = D("Live_goods")->get_info($goods_id);
		$discount_list = D("Card_new_coupon")->get_live_coupon($real_goods_info['mer_id'], $real_goods_info['store_id'], $real_goods_info['business']);
		foreach ($discount_list as $key => $value) {
            $discount_list[$key]['send'] = M("Live_coupon_send_record")->where(['coupon_id' => $value['coupon_id'], 'live_id'=>$live_id])->find() ? 1 : 0;
		}
		$this->returnCode(0, ['nums' => count($discount_list), 'discount_list' => $discount_list]);
	}

	//主播端-修改直播商品
	public function live_change_goods(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$goodids = explode(',',trim(I("goodids"),','));
		if(empty($goodids)){
			$this->returnCode(1003, [], '至少要选择一些商品');
		}
		$already_goods = D("Live_show_goods")->get_goods_by_liveid($live_id);
		foreach ($already_goods as $key => $value) {
			if(!in_array($value['goods_id'], $goodids)){
				D("Live_show_goods")->where(['id'=>$value['id']])->data(['is_del'=>1])->save();
			}
		}
		foreach ($goodids as $good_id) {
			if(!in_array($good_id, array_column($already_goods, 'goods_id')) && !empty($good_id)){
				$add = [
					'live_id' => $live_id,
					'goods_id' => $good_id,
					'is_del' => 0,
					'no' => 0,
					'recording' => 0
				];
				D("Live_show_goods")->data($add)->add();
			}
		}
		//将所有的商品重新排序
		$now_goods_list = D("Live_show_goods")->where(['live_id'=> $live_id, 'is_del' => 0])->order("id desc")->select();
		foreach ($now_goods_list as $key => $value) {
			$save['no'] = $key + 1;
			D("Live_show_goods")->where(['id'=> $value['id']])->data($save)->save();
		}
		// $now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$data = D("Live_show_goods")->get_goods_by_liveid($live_id);
		$goods = D("Live_goods")->get_by_ids(array_column($data, 'goods_id'), ['img','name','price','stock'], false);
		$output = [
			'goods_list' => []
		];
		foreach ($data as $key => $value) {
			$temp = [
				'no' => $value['no'],
				'id' => $value['goods_id'],
				'img' => $goods[$value['goods_id']]['img'],
				'name' => $goods[$value['goods_id']]['name'],
				'price' => $goods[$value['goods_id']]['price'],
				'stock' => $goods[$value['goods_id']]['stock'],
				'discount' => D("Card_new_coupon")->check_has_live_coupon($goods[$value['goods_id']]['mer_id'],$goods[$value['goods_id']]['store_id'],$goods[$value['goods_id']]['mer_id'],$goods[$value['goods_id']]['business']),
				'recording' => $value['recording']
			];
			$output['goods_list'][] = $temp;
		}
		$this->returnCode(0, $output);
	}

	//主播端-查看在线用户
	public function live_online_user(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$page = I("page", 1);
		$limit = 10;
		$limit = $limit*($page-1).','.$limit;
		$keyword = trim(I("keyword"));
		$where = [
			'a.live_show_id' => $live_id,
			'a.status' => 1
		];
		if($keyword){
			$where['b.nickname'] = ['like', '%'.$keyword.'%'];
		}
		$count = M("Live_show_room_user")->alias("a")->join("left join ".C("DB_PREFIX")."user as b on b.uid=a.uid")->where($where)->count();
		$data = M("Live_show_room_user")->alias("a")->join("left join ".C("DB_PREFIX")."user as b on b.uid=a.uid")->where($where)->field('a.is_admin,a.zan,a.buy,b.nickname,b.avatar,b.uid,a.no_speaking')->order("a.is_admin desc,a.id asc")->limit($limit)->select();
		foreach ($data as $key => $value) {
			$data[$key]['avatar'] = $value['avatar'] ? : $this->config['site_url'].'/static/images/tx.png';
			$data[$key]['is_anchor'] = $value['uid'] == $now_live['uid'] ? 1 : 0;
		}
		$this->returnCode(0, ['user_nums' => $count, 'user_list' => $data]);
	}

	//设置用户为管理员
	public function set_user_to_admin(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$uid = I("uid");
		if(empty($uid)){
			$this->returnCode(1003, [], 'uid未填写');
		}
		$cancel = I("cancel", 0);
		$where = [
			'live_show_id' => $live_id,
			'uid' => $uid
		];
		$data = ['is_admin' => !$cancel ? '1' : '0'];
		if(M("Live_show_room_user")->where($where)->data($data)->save()){
			import("@.ORG.TencentIM");
			$im = new TencentIM();
			//发送"录制开始"的通知
			$send = $im->send_system_notify($now_live['im_group_id'], "set_admin", ['is_admin' => $cancel == 1 ? '0' : '1'], [$now_live['live_no'].$uid]);
			$this->returnCode(0, ['submit' => 1]);
		}
		else{
			$this->returnCode(0, ['submit' => 0]);
		}

	}

	//主播端-修改直播间信息
	public function change_live_info(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$thumbimg_1 = I("thumbimg_1");
		$thumbimg_2 = I("thumbimg_2");
		$nickname = trim(I("nickname"));
		$title = trim(I("title"));
		$desc = trim(I("desc"));
		$category_1 = I("category_1");
		$category_2 = I("category_2");
		$area_id = I("area_id");

		$data = [];
		if($thumbimg_1){
			$data['thumbimg_1'] = $thumbimg_1;
		}
		if($thumbimg_2){
			$data['thumbimg_2'] = $thumbimg_2;
		}
		if($nickname){
			$data['nickname'] = $nickname;
		}
		if($title){
			$data['title'] = $title;
		}
		if($desc){
			$data['desc'] = $desc;
		}
		if($category_1){
			$data['category_1'] = $category_1;
		}
		if($category_2){
			$data['category_2'] = $category_2;
		}
		if($area_id){
			$data['area_id'] = $area_id;
		}

		$Model = D("Live_show");
		$save = $Model->where(['live_id'=>$live_id])->data($data)->save();
		$this->returnCode(0, ['submit' => $save ? '1' : '2']);
	}

	//主播端-实时直播数据
	public function live_real_count(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$output = [
			'part_1' => [
				'watch_nums' => D("Live_show_room_user")->get_user_num($live_id,'viewer'),
				'online_nums' => D("Live_show_room_user")->get_user_num($live_id),
				'new_follow' => 0,
				'top_watch' => 0
			],
			'part_2' => []
		];
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$output['part_1']['top_watch'] = $now_live['top_watch_nums'];

		//直播期间的关注人数
		$where = [
			'follow_uid' => $this->_uid,
			'is_del' => 0,
			'business' => 'liveshow',
			'business_id' => $live_id
		];
		// if(strtotime($now_live['real_end_time'])){
		// 	$where['_string'] = 'unix_timestamp(create_time) > '.strtotime($now_live['real_start_time']).' AND unix_timestamp(create_time) < '.strtotime($now_live['real_end_time']);
		// }
		// else{
		// 	$where['_string'] = 'unix_timestamp(create_time) > '.strtotime($now_live['real_start_time']).' AND unix_timestamp(create_time) < '.time();
		// }
		$output['part_1']['new_follow'] = M("Live_follow")->where($where)->count();

		$goods = M("Live_show_goods")->field("goods_id,no,sale_nums as buy")->where(['live_id'=>$live_id,'is_del'=>0])->order("no asc")->select();
		$goods_info = D("Live_goods")->get_by_ids(array_column($goods, 'goods_id'), ['name'],false);
		foreach ($goods as $key => $value) {
			$goods[$key]['name'] = $goods_info[$value['goods_id']]['name'];
		}
		array_multisort(array_column($goods, 'buy'),SORT_NUMERIC,SORT_DESC,$goods);
		$output['part_2'] = $goods;

		$this->returnCode(0, $output);
	}

	//主播端-直播结束动作
	public function live_ending(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		import("@.ORG.TencentIM");
		$im = new TencentIM();
		//发送"录制开始"的通知
		$send = $im->send_system_notify($now_live['im_group_id'], "live_ending", ['live_id' => $live_id]);
		if($send['errcode']){
			$this->returnCode(1001, [], $send['errmsg']);
		}
		//解散群组
		$group = $im->destroy_group($now_live['live_no']);
		if($group['errcode']){
			$this->returnCode(1001, [], $group['errmsg']);
		}
		//关闭录制
		if($now_live['is_playback'] == '1'){
			// $taskid = $now_live['record_taskid'];
			//结束录制视频
			// import("@.ORG.TencentLive");
			// $live = new TencentLive();
			// $record_result = $live->stop_live_record($now_live['live_no'] , $taskid);//最长半小时
			// if($record_result['errcode']){
			// 	$this->returnCode(1001, [], $record_result['errmsg']);
			// }
		}
		
		$data['real_end_time'] = date('Y-m-d H:i:s');
		$data['status'] = 3;
		$data['is_interrupt'] = 0;
		M("Live_show")->where(['live_id'=>$live_id])->data($data)->save();
		$output = [
			'time' => strtotime($data['real_end_time']) - strtotime($now_live['real_start_time']),
			'count' => [
				'watch' => D("Live_show_room_user")->get_user_num($live_id,'viewer'),
				'buy' => D("Live_show_goods")->where(['live_id'=>$live_id, 'is_del'=>0])->sum("sale_nums") ?: 0,
				'share' => M("Live_show_share")->where(['live_id'=>$live_id])->count(),
				'comment' => M("Live_show_comment")->where(['live_id'=>$live_id])->count(),
				'zan' => $now_live['zan']
			],
			'goods_list' => []
		];
		$goods = M("Live_show_goods")->field("goods_id,no,sale_nums as buy")->where(['live_id'=>$live_id,'is_del'=>0])->order("no asc")->select();
		$goods_info = D("Live_goods")->get_by_ids(array_column($goods, 'goods_id'), ['name'],false);
		foreach ($goods as $key => $value) {
			$goods[$key]['name'] = $goods_info[$value['goods_id']]['name'];
		}
		array_multisort(array_column($goods, 'buy'),SORT_NUMERIC,SORT_DESC,$goods);
		$output['goods_list'] = $goods;
		
		//把商户下面的所有店铺“直播中”变成结束
		$current_mer_id = $this->check_mer();
		if($current_mer_id){
			$stores = M("Merchant_store")->where(['mer_id'=>$current_mer_id])->data(['current_liveshow_id'=>0])->save();
			$live_goods = D("Live_show_goods")->get_goods_by_liveid($live_id);
			D("Live_goods")->update_goods_live_status(array_column($live_goods, 'goods_id'), 0);
		}

		$this->returnCode(0, $output);
	}

	//直播端-录制开始
	public function start_recording(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			$this->returnCode(1003, [], '商品ID未填写');
		}
		$data = D("Live_show_goods")->where(['live_id'=>$live_id,'goods_id'=>$goods_id,'is_del'=>0,'recording'=>0])->find();
		if(empty($data)){
			$this->returnCode(1001, [], '该商品已经录制或不存在');
		}
		if(D("Live_show_goods")->where(['recording'=>2,'is_del'=>0,'live_id'=>$live_id])->find()){
			$this->returnCode(1003, [], '当前还有其他未完成的讲解，请先完成讲解');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		import("@.ORG.TencentIM");
		$im = new TencentIM();
		//发送"录制开始"的通知
		$send = $im->send_system_notify($now_live['im_group_id'], "start_recording", ['goods_id' => $goods_id]);
		if($send['errcode']){
			$this->returnCode(1001, [], $send['errmsg']);
		}
		$save = ['recording' => 2, 'recording_start_time' => date('Y-m-d H:i:s')];

		//开始录制视频
		import("@.ORG.TencentLive");
		$live = new TencentLive();
		$record_result = $live->create_live_record($now_live['live_no'], 1, '', '', 'liveno='.$now_live['live_no'].'&type=2&goodsid='.$goods_id);//最长半小时
		if($record_result['errcode']){
			$this->returnCode(1001, [], $record_result['errmsg']);
		}
		$save['record_taskid'] = $record_result['retdata'];
		$result = D("Live_show_goods")->where(['id'=>$data['id']])->data($save)->save();
		if($result){
			$this->returnCode(0, ['submit' => 1]);
		}
		else{
			$this->returnCode(1004, [], '录制失败');
		}
	}

	//获取商品信息
	public function get_goods_info(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			$this->returnCode(1003, [], '商品ID未填写');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$goods_info = D("Live_goods")->get_by_ids($goods_id, ['img','price','name'], false);
		$no = D("Live_show_goods")->where(['goods_id'=>$goods_id,'live_id'=>$live_id])->field("no")->find();

		$this->returnCode(0, ['goods_info' => ['no' => $no['no'],'goods_id' => $goods_id, 'name' => $goods_info[$goods_id]['name'], 'price' => $goods_info[$goods_id]['price'], 'img' => $goods_info[$goods_id]['img']]]);
	}

	//主播端-完成讲解（录制）
	public function end_recording(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			$this->returnCode(1003, [], '商品ID未填写');
		}
		$data = D("Live_show_goods")->where(['live_id'=>$live_id,'goods_id'=>$goods_id,'is_del'=>0])->find();
		if(empty($data)){
			$this->returnCode(1001, [], '该商品未录制或不存在');
		}
		if($data['recording'] == '0'){
			$this->returnCode(1001, [], '该商品未开始录制');
		}
		if($data['recording'] == '1'){
			$this->returnCode(1001, [], '该商品已录制成功，原因可能是录制时间超过30分钟自动终止');
		}
		$taskid = $data['record_taskid'];
		//结束录制视频
		import("@.ORG.TencentLive");
		$live = new TencentLive();
		$record_result = $live->stop_live_record($now_live['live_no'] , $taskid);//最长半小时
		if($record_result['errcode']){
			$this->returnCode(1001, [], $record_result['errmsg']);
		}
		$save = ['recording' => 1, 'recording_end_time' => date('Y-m-d H:i:s')];
		$result = D("Live_show_goods")->where(['id'=>$data['id']])->data($save)->save();

		import("@.ORG.TencentIM");
		$im = new TencentIM();
		//发送"录制结束"的通知
		$send = $im->send_system_notify($now_live['im_group_id'], "end_recording", ['goods_id' => $goods_id]);
		if($result){
			$this->returnCode(0, ['submit' => 1]);
		}
		else{
			$this->returnCode(1004, [], '失败');
		}
	}

	//IM获取UserSig
	public function im_login(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		import("@.ORG.TencentIM");
		$im = new TencentIM();

		$now_user = D("User")->get_user($this->_uid);
		//得到usersig，用于登录IM
		$im_userid = $now_live['live_no'].$this->_uid;//用房间号和uid来作为用户唯一标识
		$output['user_info']['usersig'] = $im->get_usersig($im_userid);
		$output['user_info']['nickname'] = $now_user['nickname'];
		$output['user_info']['userid'] = $im_userid;
		$output['user_info']['im_group_id'] = $now_live['im_group_id'];
		$output['user_info']['play_url'] = D("Live_show")->get_play_url($now_live['live_no']);
		$this->returnCode(0, $output);
	}

	//播放端-设置开播提醒
	public function setup_remind(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$where = [
			'uid' => $this->_uid,
			'live_id' => $live_id,
			'is_del' => 0
		];
		$has = M("Live_show_remind")->where($where)->find();
		if($has){
			M("Live_show_remind")->where($where)->data(['is_del'=>1])->save();
		}
		else{
			$data = [
				'uid' => $this->_uid,
				'live_id' => $live_id,
				'create_time' => date('Y-m-d H:i:s'),
				'is_del' => 0
			];
			M("Live_show_remind")->data($data)->add();
		}
		
		$this->returnCode(0, ['submit' => 1]);
	}

	//播放端-关注主播
	public function follow_live(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id", 0);
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$uid = I("uid");
		$business = I("business") ? : 'liveshow';
		$video_id = I("video_id", 0);
		$now_video = M("Live_video")->where(['video_id' => $video_id])->find();
		if(empty($live_id) && empty($uid) && empty($video_id)){
			$this->returnCode(1003, [], 'live_id与video_id至少填写一个');
		}
		$type = I("type", 1);//1=关注 2=取消
        $follow_uid=$now_live['uid'] ? $now_live['uid'] : ($uid ? : $now_video['uid']);
        $business_id= $live_id ? : $video_id;
		if($type == 1){
			if($live_id){
				$user = D("User")->get_user($this->_uid);
				if($now_live['im_group_id']){
					import("@.ORG.TencentIM");
					$im = new TencentIM();
					//发送"录制开始"的通知
					$send = $im->send_system_notify($now_live['im_group_id'], "follow_live", ['nickname' => $user['nickname']]);
				}
			}
            $msg=M("Live_follow")->where(['uid'=>$this->_uid,'follow_uid'=>$follow_uid,'is_del'=>0,'business_id'=>$business_id])->find();
            if(empty($msg)){
                $data = [
                    'uid' => $this->_uid,
                    'follow_uid' => $now_live['uid'] ? $now_live['uid'] : ($uid ? : $now_video['uid']),
                    'business' => $business,
                    'business_id' => $live_id ? : $video_id,
                    'create_time' => date('Y-m-d H:i:s'),
                    'is_del' => 0
                ];
                M("Live_follow")->data($data)->add();
            }
		}
		else{
			$where = [
				'uid' => $this->_uid,
				'follow_uid' => $now_live['uid'] ? $now_live['uid'] : ($uid ? : $now_video['uid']),
			];
			M("Live_follow")->data(['is_del'=>1])->where($where)->save();
		}
		$this->returnCode(0, ['submit' => 1]);
	}

	//播放端-去购买按钮
	public function goto_buy(){
		/* if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		} */
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			$this->returnCode(1003, [], '商品ID未填写');	
		}
		
		if($this->_uid){
			$user = D("User")->get_user($this->_uid);
			import("@.ORG.TencentIM");
			$im = new TencentIM();
			//发送"录制开始"的通知
			$send = $im->send_system_notify($now_live['im_group_id'], "goto_buy", ['nickname' => $user['nickname']]);
		}
		
		// if($send['errcode']){
		// 	$this->returnCode(1001, [], $send['errmsg']);
		// }这里不用管有没有发送成功，因为有可能是结束之后去购买的
		$live_goods = M("Live_goods")->where(['id'=>$goods_id])->find();
		$output = [
			'submit' => 1,
			'type' => $live_goods['business'],
			'live_id' => $live_id,
			'shop_redirect_info' => [
				'store_id' => 0,
				'cate_id' => 0,
				'goods_id' => 0
			],
			'redirect_url' => ""
		];
		switch ($live_goods['business']) {
			case 'shop':
				$real_goodsinfo = D("Shop_goods")->where(['goods_id'=>$live_goods['business_id']])->find();
				$output['shop_redirect_info']['store_id'] = $real_goodsinfo['store_id'];
				$output['shop_redirect_info']['cate_id'] = $real_goodsinfo['sort_id'];
				$output['shop_redirect_info']['goods_id'] = $live_goods['business_id'];
				break;
			case 'mall':
				$output['redirect_url'] = $this->config['site_url']."/wap.php?g=Wap&c=Mall&a=detail&goods_id=".$live_goods['business_id']."&current_liveshow_id=".$live_id;
				break;
			case 'mall3':
				$output['redirect_url'] = C('config.site_url'). D('House_village')->base_url ."pages/shopmall_third/commodity_details?goods_id=".$live_goods['business_id']."&current_liveshow_id=".$live_id;
				break;
            case 'village_group':
                $output['redirect_url'] = $this->config['site_url']."/packapp/community_group/index.html?c=Village_group&a=index/#/goods?goodsId={$live_goods['business_id']}&live_id=$live_id&live_type=live_show";
                break;
			case 'group':
				$output['redirect_url'] = $this->config['site_url']."/wap.php?g=Wap&c=Group&a=detail&group_id=".$live_goods['business_id']."&current_liveshow_id=".$live_id;
				break;
		}
		$this->returnCode(0, $output);
	}

	//播放端-直播详情页
	public function liveshow_detail(){
		$mer_id = I("mer_id",0);
        $type = I("type",1);
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$author_id = I("author_id");
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$author_user = D("User")->get_user($now_live['uid']);
		$output = [
			'basic_info' => [//基础信息
                'share'=>D("Live_show_share")->alias("s")->where(['s.live_id'=>$live_id])->count(),
                "live_id" => $live_id,
				'live_no' => $now_live['live_no'],
				'author_id' => $now_live['uid'],
				'status' => $now_live['status'],
				'estimate_time' => strtotime($now_live['estimate_time']) > time() ? strtotime($now_live['estimate_time']) - time() : 0,
				'area' => "",
				'nickname' => $now_live['live_nickname'],
				'avatar' => $author_user['avatar'],
				'title' => $now_live['title'],
				'follow' => D("Live_follow")->where(['uid'=>$this->_uid, 'is_del'=>0, 'follow_uid'=>$author_user['uid']])->find() ? 1 : 0,
				'remind' => D("Live_show_remind")->where(['uid'=>$this->_uid,'is_del'=>0,'live_id'=>$live_id])->find() ? 1 : 0, 
				'nickname' => $now_live['live_nickname'],
				'goods_nums' => 0,
				'img_1' => replace_file_domain($now_live['thumbimg_1']),
				'img_2' => replace_file_domain($now_live['thumbimg_2']),
				'appoint' => D("Live_show_remind")->where(['is_del'=>0,'live_id'=>$live_id])->count(),
				'im_group_id' => $now_live['im_group_id'],
				'follow_nums' => M("Live_follow")->where(['follow_uid'=>$now_live['uid'],'is_del'=>0])->count(),
				'zan' => $now_live['zan'],
				'video_url' => "",//结束的时候的回放视频地址
				'play_url' => D("Live_show")->get_play_url($now_live['live_no']),//播放地址
				'seen' => M("Live_show_room_user")->where(['live_show_id'=>$now_live['live_id']])->count(),//观看人数
			],
			'current_goods_detail' => [
				'explain' => 0,//1=正在讲解 0=没有讲解的商品只是默认第一个
				'goods_info' =>[]
			],//当前上屏的商品
			'slider' =>[//上下滑动
				'pre_live' => [
				],
				'next_live' => [
				]
			],
			'is_admin' => '0',//是否是管理员
			'no_speaking' => '0',//是否禁言
		];
		$room_user_info = M("Live_show_room_user")->where(['live_show_id'=>$live_id, 'uid'=>$this->_uid])->find();
		if($room_user_info['is_admin'] == '1'){
			$output['is_admin'] = '1';
		}
		if($room_user_info['no_speaking'] == '1'){
			$output['no_speaking'] = '1';
		}

		//获取上一个和下一个直播
		$condition = [
			's.is_del' => 0
		];
		if($author_id){
			$condition['s.uid'] = $author_id;
		}
		$condition['s.live_id'] = ['gt',$live_id];

		if($mer_id){
			$condition['g.mer_id'] = $mer_id;
			$condition['bg.is_del'] = 0;
		}
		$pre_live = D("Live_show")->alias("s")->field('s.live_id,s.thumbimg_2')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'live_show_goods as bg on bg.live_id = s.live_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')
            ->where($condition)->order("s.live_id desc")->limit(5)->group('s.live_id')->select();
		if($pre_live){
			foreach ($pre_live as $key => $value) {
				$_temp['live_id'] = $value['live_id'];
				$_temp['live_img'] = replace_file_domain($value['thumbimg_2']);
				$output['slider']['pre_live'][] = $_temp;
			}			
		}
		$condition['s.live_id'] = ['lt',$live_id];
		$next_live = D("Live_show")->alias("s")->field('s.live_id,s.thumbimg_2')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'live_show_goods as bg on bg.live_id = s.live_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')
            ->where($condition)->order("s.live_id desc")->limit(5)->group('s.live_id')->select();
		if($next_live){
			foreach ($next_live as $key => $value) {
				$_temp['live_id'] = $value['live_id'];
				$_temp['live_img'] = replace_file_domain($value['thumbimg_2']);
				$output['slider']['next_live'][] = $_temp;
			}
		}
		if($now_live['record_status']){
			$record_result = unserialize($now_live['record_result']);
			if($record_result['video_url']){
				$output['basic_info']['video_url'] = $record_result['video_url'];
			}
		}

		// 查询商品条件
		$conditionGoods = [
			'bg.is_del' => 0, 
			'bg.live_id'=> $live_id
		];
		if($mer_id){
			$conditionGoods['g.mer_id'] = $mer_id;
		}
		$all_goods = D("Live_show_goods")->alias("bg")->field('bg.*')->join(' LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($conditionGoods)->order("bg.no asc")->select();

        $current_goods_id = 0;
		/*$current_goods_id = $all_goods[0]['goods_id'];
		$current_goods_no = $all_goods[0]['no'];*/ //产品说默认不展示商品
		//优先已上屏商品(录制)
		$recording2 = false;
		foreach ($all_goods as $key => $value) {
			if($value['recording'] == 2){
				$output['current_goods_detail']['explain'] = 1;
				$recording2 = true;
				$current_goods_id = $value['goods_id'];
				$current_goods_no = $value['no'];
			}
		}
        //其次是正在讲解得商品
		$on_screen_display = false;
		if(!$recording2){
            foreach ($all_goods as $key => $value) {
                if($value['on_screen_display'] == 1){
                    $output['current_goods_detail']['explain'] = 0;
                    $on_screen_display = true;
                    $current_goods_id = $value['goods_id'];
                    $current_goods_no = $value['no'];
                }
            }
        }
		//最后列表第一个商品
		if(!$on_screen_display){
			// $current_goods = M("Live_show_goods")->where(['is_del' => 0, 'live_id'=> $live_id, '_string' => 'unix_timestamp(recording_end_time) > 0'])->order("recording_end_time desc")->find();
			$conditionGoodsRecording = $conditionGoods;
			$conditionGoodsRecording['_string'] = 'unix_timestamp(recording_end_time) > 0';
			$current_goods = M("Live_show_goods")->alias("bg")->field('bg.*')->join(' LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($conditionGoodsRecording)->order("bg.no asc")->find();
			if($current_goods){
				$output['current_goods_detail']['explain'] = 1;
				$current_goods_id = $current_goods['goods_id'];
				$current_goods_no = $current_goods['no'];
			}
		}
		$goods_info = D("Live_goods")->get_by_ids($current_goods_id, ['img','price','name'], false);
		if($current_goods_id){
			$output['current_goods_detail']['goods_info'] = [
				'no' => $current_goods_no,
				'goods_id' => $current_goods_id,
				'name' => $goods_info[$current_goods_id]['name'],
				'price' => $goods_info[$current_goods_id]['price'],
				'img' => $goods_info[$current_goods_id]['img'],
				'store_name' => $goods_info[$current_goods_id]['store_name'],//店铺名称
				'address' => $goods_info[$current_goods_id]['address'],//店铺地址
				'long' => $goods_info[$current_goods_id]['long'],//店铺经度
				'lat' => $goods_info[$current_goods_id]['lat'],//店铺维度
			];
		}else{
			$output['current_goods_detail']['goods_info'] = null;
		}
		

		$area = D("Area")->where(['area_id'=>$now_live['area_id']])->field("area_pid,area_name")->find();
		$parent = D("Area")->name($area['area_pid']);
		$output['basic_info']['area'] = $parent == $area['area_name'] ? $area['area_name'] : $parent.$area['area_name'];
		$output['basic_info']['goods_nums'] = M("Live_show_goods")->alias("bg")->join(' LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($conditionGoods)->count();
		
		if($this->config['is_hark_system']){
			$output['redirect_h5_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/live_and_video/live/viewer/main_page?live_id=' . $live_id . '&is_wxpp_redirect=1';
		}
		$this->returnCode(0, $output);
	}

	//播放端-优惠券列表
	public function liveshow_coupon(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}

		//变态需求，需要知道每个优惠券所能支持的商品
		$goods = M("Live_show_goods")->alias("a")->join("left join ".C("DB_PREFIX")."live_goods as b on a.goods_id=b.id")->where(['a.live_id'=>$live_id,'a.is_del'=>0])->order("no asc")->field("b.mer_id,b.store_id,b.business,a.no")->select();//把所有的商品取出来
		foreach ($goods as $key => $value) {//把每个商品所能支持的优惠券取出
			$goods[$key]['couponidarr'] = D("Card_new_coupon")->get_live_couponid($value['mer_id'], $value['store_id'], $value['business']);
		}

		$data = D("Live_coupon_send_record")->where(['live_id'=>$live_id])->order("id desc")->select();
		$coupons = D("Card_new_coupon")->getCouponInfoByIds(array_column($data, 'coupon_id'));
		$output['coupon_nums'] = count($coupons);
		$output['coupon_list'] = [];
		foreach ($coupons as $key => $value) {
			$gids = [];
			foreach ($goods as $g) {
				if(in_array($value['coupon_id'], $g['couponidarr'])){
					$gids[] = $g['no']."号";
				}
			}
			$temp = [
				'coupon_id' => $value['coupon_id'],
				'money' => $value['discount'],
				'title' => $value['name'],
				'discount_title' => "满".$value['order_money']."减".$value['discount'],
				'status' => 0,
				'limit_tips' => $gids ? "限".implode('、', $gids)."商品可用" : "",
			];
			if($value['had_pull'] >= $value['num']){
				$temp['status'] = 1;
			}
			$get = M("Card_new_coupon_hadpull")->where(['coupon_id'=>$value['coupon_id'], 'uid'=> $this->_uid])->find();
			if($get){
				$temp['status'] = 2;
			}
			$output['coupon_list'][] = $temp;
		}
		$this->returnCode(0, $output);
	}

	//播放端-点赞
	public function liveshow_zan(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$zan = I("zan");
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$output = [
			'zan' => $now_live['zan']
		];
		if($zan != '0'){
			if(D("Live_show")->where(['live_id'=>$live_id])->setInc("zan", $zan)){
				D("Live_show_room_user")->where(['live_show_id'=>$live_id,'uid'=>$this->_uid])->setInc("zan", $zan);
				$output['zan'] += $zan;
				import("@.ORG.TencentIM");
				$im = new TencentIM();
				//发送"录制开始"的通知
				$send = $im->send_system_notify($now_live['im_group_id'], "liveshow_zan", ['zan' => $output['zan']]);
				$this->returnCode(0, $output);
			}
			else{
				fdump(D("Live_show")->getlastsql(),"lumin0331",1);
				$this->returnCode(1004, [], '操作失败');
			}
		}
		$this->returnCode(0, $output);
	}

	//播放端-举报项
	public function liveshow_report_item(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$Model = D("Live_report");
		$items = [];
		foreach ($Model->category as $key => $value) {
			$temps[] = [
				'id' => $key,
				'text' => $value
			];
		}
		$this->returnCode(0, ['items' => $temps]);
	}

	//播放端-举报提交
	public function liveshow_report_submit(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		$video_id = I("video_id");
		if(empty($live_id) && empty($video_id)){
			$this->returnCode(1003, [], 'ID未填写');
		}
		$type = I("type");
		$img = trim(I("img"), ',');
		if(empty($type) || empty($img)){
			$this->returnCode(1003, [], '必填参数未填写');	
		}
		$reason = I("reason");
		$data = [
			'business' => $live_id ? 1 : 2,
			'business_id' => $live_id ? : $video_id,
			'category' => $type,
			'content' => $reason,
			'imgs' => serialize(explode(',', $img)),
			'create_time' => date('Y-m-d H:i:s'),
			'uid' => $this->_uid
		];
		M("Live_report")->data($data)->add();
		$this->returnCode(0, ['submit' => 1]);
	}

	//播放端-主播主页
	public function liveshow_author_index(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		$author_id = I("author_id");
		if(empty($live_id) && empty($author_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		if($live_id){
			$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		}
		if($author_id){
			$now_live['uid'] = $author_id;	
		}
		$anchor_info = D("User")->get_user($now_live['uid']);
		$output = [
			'liveshow' => [
				'playing' => [],
				'play_notice' => []
			],
			'livevideo' => [],
			'anchor_info' => [
				'avatar' => $anchor_info['avatar'],
				'author_id' => $now_live['uid'],
				'nickname' => $anchor_info['nickname'],
				'follow' => M("Live_follow")->where(['follow_uid'=>$now_live['uid'],'is_del'=>0])->count()
			]
		];
		$playing = M("Live_show")->where(['status' => 1, 'is_del' => 0, 'uid' => $now_live['uid']])->select();
		foreach ($playing as $key => $value) {
			$output['liveshow']['playing'][] = [
				'live_id' => $value['live_id'],
				'title' => $value['title'],
				'watch_nums' => $value['top_watch_nums']
			];
		}
		$play_notice = M("Live_show")->where(['status' => 2, 'is_del' => 0, 'uid' => $now_live['uid']])->select();
		foreach ($play_notice as $key => $value) {
			$output['liveshow']['play_notice'][] = [
				'live_id' => $value['live_id'],
				'title' => $value['title'],
				'remind' => M("Live_show_remind")->where(['uid'=>$this->_uid, 'live_id'=>$value['live_id'], 'is_del'=>0])->find() ? '1' : '0', 
				'estimate_time' => strtotime($value['estimate_time']) > time() ? strtotime($value['estimate_time']) - time() : 0
			];
		}
		$this->returnCode(0, $output);
	}

	//播放端-主播主页回放列表
	public function liveshow_author_backlist(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		$author_id = I("author_id");
		if(empty($live_id) && empty($author_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		if($live_id){
			$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		}
		if($author_id){
			$now_live['uid'] = $author_id;	
		}
		$page = I("page");
		$limit = 10;
		$limit = $limit*($page-1).','.$limit;
		$backlist = M("Live_show")->where(['status' => 3, 'is_del' => 0, 'uid' => $now_live['uid']])->order("live_id desc")->limit($limit)->select();
		foreach ($backlist as $key => $value) {
			$backlist[$key]['watch_nums'] = $value['top_watch_nums'];
			$backlist[$key]['goods_nums'] = M("Live_show_goods")->where(['live_id'=>$live_id, 'is_del'=>0])->count();
			$backlist[$key]['img_1'] = replace_file_domain($value['thumbimg_1']);
			$backlist[$key]['date'] = date('m-d',strtotime($value['real_start_time']));
			$backlist[$key]['video_url'] = "";
			$record_result = unserialize($value['record_result']);
			if($record_result['video_url']){
				$backlist[$key]['video_url'] = $record_result['video_url'];
			}
		}
		$this->returnCode(0, ['backlist' => $backlist]);
	}

	//播放端-领取优惠券
	public function get_coupon(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$coupon_id = I("coupon_id");
		if(empty($coupon_id)){
			$this->returnCode(1003, [], 'coupon ID未填写');
		}
		$get = D("Card_new_coupon")->had_pull($coupon_id, $this->_uid);
		if($get['error_code'] > 0){
			switch ($get['error_code']) {
				case 1:
					$this->returnCode(1001, [], '优惠券不存在');
					break;
				case 2:
					$this->returnCode(1001, [], '优惠券已过期');
					break;
				case 3:
					// $this->returnCode(1001, [], '当前优惠券领完了');
					$this->returnCode(0, ['submit' => 2]);
					break;
				case 4:
					$this->returnCode(1001, [], '当前优惠券只允许新用户');
					break;
				case 5:
					$this->returnCode(1001, [], '您已经领取过不能在领取了');
					break;
				
				default:
					$this->returnCode(1001, [], '未知错误');
					break;
			}
		}
		$this->returnCode(0, ['submit' => 1]);
	}

	//主播端-关闭直播回放
	public function live_cutdown(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();

		if($now_live['is_playback'] == '0'){
			$this->returnCode(1001, [], '当前直播已经关闭了回放，请勿重复操作');
		}
		$taskid = $now_live['record_taskid'];
		//结束录制视频
		import("@.ORG.TencentLive");
		$live = new TencentLive();
		$record_result = $live->stop_live_record($now_live['live_no'] , $taskid);//最长半小时
		// if($record_result['errcode']){
		// 	$this->returnCode(1001, [], $record_result['errmsg']);
		// }
		D("Live_show")->where(['live_id'=>$live_id])->data(['is_playback' => 0])->save();
		$this->returnCode(0, ['submit' => 1]);
	}

	//主播端-上屏操作
	public function upper_screen(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$goods_id = I("goods_id");
		if(empty($goods_id)){
			$this->returnCode(1003, [], '商品ID未填写');
		}
		//修改商品状态
        D('Live_show_goods')->where(['live_id'=>$live_id])->save(['on_screen_display'=>0]);
        D('Live_show_goods')->where(['live_id'=>$live_id,'goods_id'=>$goods_id])->save(['on_screen_display'=>1]);
		import("@.ORG.TencentIM");
		$im = new TencentIM();
		//发送"录制开始"的通知
		$send = $im->send_system_notify($now_live['im_group_id'], "upper_screen", ['goods_id' => $goods_id]);
		if($send['errcode']){
			$this->returnCode(1001, [], $send['errmsg']);
		}
		$this->returnCode(0, ['submit' => 1]);
	}

	//主播端-发送优惠券
	public function send_coupon(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$coupon_id = I("coupon_id");
		if(empty($coupon_id)){
			$this->returnCode(1003, [], '优惠券ID未填写');
		}
		$has = M("Live_coupon_send_record")->where(['live_id'=>$live_id, 'coupon_id' => $coupon_id])->find();
		if(!$has){
			$data = [
				'coupon_id' => $coupon_id,
				'live_id' => $live_id,
				'create_time' => date("Y-m-d H:i:s")
			];
			M("Live_coupon_send_record")->data($data)->add();
		}
		import("@.ORG.TencentIM");
		$im = new TencentIM();
		//发送"录制开始"的通知
		$send = $im->send_system_notify($now_live['im_group_id'], "send_coupon", ['coupon_id' => $coupon_id]);
		if($send['errcode']){
			$this->returnCode(1001, [], $send['errmsg']);
		}
		$this->returnCode(0, ['submit' => 1]);
	}

	//获取优惠券信息
	public function get_coupon_info(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$coupon_id = I("coupon_id");
		if(empty($coupon_id)){
			$this->returnCode(1003, [], '优惠券ID未填写');
		}

		//变态需求，需要知道每个优惠券所能支持的商品
		$goods = M("Live_show_goods")->alias("a")->join("left join ".C("DB_PREFIX")."live_goods as b on a.goods_id=b.id")->where(['a.live_id'=>$live_id,'a.is_del'=>0])->order("no asc")->field("b.mer_id,b.store_id,b.business,a.no")->select();//把所有的商品取出来
		foreach ($goods as $key => $value) {//把每个商品所能支持的优惠券取出
			$goods[$key]['couponidarr'] = D("Card_new_coupon")->get_live_couponid($value['mer_id'], $value['store_id'], $value['business']);
		}

		$gids = [];
		foreach ($goods as $g) {
			if(in_array($coupon_id, $g['couponidarr'])){
				$gids[] = $g['no']."号";
			}
		}

		$coupon_info = D("Card_new_coupon")->get_coupon($coupon_id,false);
		$get = M("Card_new_coupon_hadpull")->where(['coupon_id' => $coupon_id, 'uid'=>$this->_uid])->find();
		$this->returnCode(0, ['coupon_info' => ['coupon_id' => $coupon_id, 'order_money' => "满".$coupon_info['order_money']."元可使用", 'discount' => $coupon_info['discount'], 'get' => $get ? 1 : 0, 'limit_tips' => $gids ? "限".implode('、', $gids)."商品可用" : ""]]);
	}

	//主播端-禁言
	public function no_speaking(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$point_uid = I("point_uid");
		if(empty($point_uid)){
			$this->returnCode(1003, [], '禁言对象ID未填写');
		}
		$cancel = I("cancel", 0);

		$data = ['no_speaking' => ($cancel == '1' ? 0 : 1)];
		M("Live_show_room_user")->where(['live_show_id'=>$live_id, 'uid' => $point_uid])->data($data)->save();
		import("@.ORG.TencentIM");
		$im = new TencentIM();
		//发送"录制开始"的通知
		$send = $im->send_system_notify($now_live['im_group_id'], "set_speaking", ['is_speaking' => $cancel == 1 ? '0' : '1'], [$now_live['live_no'].$point_uid]);
		$this->returnCode(0, ['submit' => 1]);
	}

	//播放端-分享
	public function share(){
		$mer_id = I("mer_id", 0);
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$now_live = M("Live_show")->where(['live_id' => $live_id])->find();
		$user = D("User")->get_user($this->_uid);
		if($now_live['im_group_id'] && $now_live['status'] == 1){
			import("@.ORG.TencentIM");
			$im = new TencentIM();
			//发送"录制开始"的通知
			$send = $im->send_system_notify($now_live['im_group_id'], "share_live", ['nickname' => $user['nickname']]);
			M("Live_show_share")->data(['live_id'=>$live_id,'uid'=>$this->_uid,'create_time'=>date('Y-m-d H:i:s')])->add();
		}

		$output = [
			'wechat' => [],//微信好友
			'poster' => []//海报
		];
        $zhibo_version = empty($this->config['zhibo_version'])?0:$this->config['zhibo_version']*1;//直播样式，1新版样式
		if($now_live['poster'] && file_exists('.'.$now_live['poster'])){
			$output['poster']['img'] = $this->config['site_url'].$now_live['poster'];
		}
		else{
			//画海报
			$poster = $this->draw_poster($now_live);
			if($poster){
				$output['poster']['img'] = $this->config['site_url'].$poster;
			}
			else{
				$this->returnCode(1001, [], '画海报失败');
			}
		}
		if($now_live['status'] == 1 || $now_live['status'] == 3){
			$output['wechat']['share_title'] = '【'.$now_live['live_nickname'].'】正在直播，快来一起看看';
			$output['wechat']['share_content'] = '点击进入直播，查看更多优惠商品';
		}
		elseif($now_live['status'] == 2){
			$output['wechat']['share_title'] = '【直播预告】'.$now_live['title'];
			$output['wechat']['share_content'] = '点击进入直播，查看更多优惠商品';
		}
		$output['wechat']['share_url'] = $this->config['site_url']. D('House_village')->base_url ."pages/live_and_video/live/viewer/main_page?live_id=".$now_live['live_id'].'&mer_id='.$mer_id.'&is_share=1';
        if($zhibo_version){
            $output['wechat']['share_url'] =$this->config['site_url']. D('House_village')->base_url ."pages/live_and_video/live/viewer/main_page?live_id=".$now_live['live_id'].'&mer_id='.$mer_id.'&type=4&is_share=1';
        }
		$output['wechat']['share_image'] =replace_file_domain($now_live['thumbimg_1']);
		$output['wechat']['share_wx'] = (($this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username']) || $_POST['app_type'] == 'wxapp') ? 'wxapp' : 'h5';
		$output['wechat']['share_wx_info'] = [];
		if($output['wechat']['share_wx'] == 'wxapp' || $_POST['app_type'] == 'wxapp'){
		    if($zhibo_version){
                $output['wechat']['share_wx_info'] = [
                    'userName' 	=> $this->config['pay_wxapp_username'],
                    'path' 		=> $this->config['site_url']. D('House_village')->base_url ."pages/live_and_video/live/viewer/main_page?live_id=".$now_live['live_id'].'&mer_id='.$mer_id.'&type=4',
                    'path_mini' => "/pages/live_and_video/live/viewer/main_page?is_share=1&live_id=".$now_live['live_id'].'&mer_id='.$mer_id.'&type=4',
                    'image' 	=> replace_file_domain($now_live['thumbimg_1']),
                    'title' 	=> ''
                ];
            }else{
                $output['wechat']['share_wx_info'] = [
                    'userName' 	=> $this->config['pay_wxapp_username'],
                    'path' 		=> $this->config['site_url']. D('House_village')->base_url ."pages/live_and_video/live/viewer/main_page?live_id=".$now_live['live_id'].'&mer_id='.$mer_id,
                    'path_mini' => "/pages/live_and_video/live/viewer/main_page?is_share=1&live_id=".$now_live['live_id'].'&mer_id='.$mer_id,
                    'image' 	=> replace_file_domain($now_live['thumbimg_1']),
                    'title' 	=> ''
                ];
            }

			if($now_live['status'] == 1 || $now_live['status'] == 3){
				$output['wechat']['share_wx_info']['title'] = '【'.$now_live['live_nickname'].'】正在直播，快来一起看看';
			}
			elseif($now_live['status'] == 2){
				$output['wechat']['share_wx_info']['title'] = '【直播预告】'.$now_live['title'];
			}
		}
		$this->returnCode(0, $output);
	}

	private function write_img($img){
		if(!file_exists('./runtime/tempimg')){
            mkdir('./runtime/tempimg',0777,true);
        }
        $path = './runtime/tempimg/'.uniqid().'.jpg';
    	file_put_contents($path,$img);
    	return $path;
	}

	//画海报
	private function draw_poster($now_live){
		if(empty($now_live)) return false;
		$anchor_user = D("User")->get_user($now_live['uid']);
		//画海报
		$top_img_path = $now_live['thumbimg_1'];

		$nickname = mb_strlen($now_live['live_nickname']) <= 12 ? $now_live['live_nickname'] : mb_substr($now_live['live_nickname'], 0, 12);//主播本次直播昵称
		$title = mb_strlen($now_live['title']) <= 12 ? $now_live['title'] : mb_substr($now_live['title'], 0, 12);//直播标题
        $_url = '/pages/live_and_video/live/viewer/main_page?live_id='.$now_live['live_id'];
		//本地化二维码
        $zhibo_version = empty($this->config['zhibo_version'])?0:$this->config['zhibo_version']*1;//直播样式，1新版样式
        if($zhibo_version){
            $_url = '/pages/live_and_video/live/viewer/main_page?live_id='.$now_live['live_id'].'&type=4';
        }
		$wechat_mini_path = D('House_village')->home_url . '?redirect=page&page='.urlencode($_url);
		$qrcode = D("Village_group")->get_wxapp_qrcode($wechat_mini_path,55);//小程序二维码
		if(strpos($qrcode,'{"') === 0){
            $qrcodeArr = json_decode($qrcode,true);
            if($qrcodeArr && $qrcodeArr['errcode']){
				$this->returnCode(1001, [], '小程序二维码生成失败。'.var_export($qrcodeArr, true));
            }
        }
        $qrcode_path = $this->write_img($qrcode);

        //本地化主播头像
        import('ORG.Net.Http');
		$http = new Http();
        $avatar_file = $http->curlGet($anchor_user['avatar']);
        $avatar_path = $this->write_img($avatar_file);
        $liveid_path = './static/qrcode/wxapp/live_id.png';
        $file_handle = new file_handle();
        if($file_handle->check_open_oss()) {
            $file_handle->download($top_img_path);
            $top_img_path = $file_handle->get_path($top_img_path);

            $file_handle->download($avatar_path);
            $avatar_path = $file_handle->get_path($avatar_path);
        }
        $config = [
        	'image' => [
        		[
        			'url' => '.'.$top_img_path,//顶部大图
        			'stream' => 0,//字符串图像流
        			'left' => 0,
        			'top' => 0,
        			'right' => 0,
        			'bottom' => 0,
        			'width' => 460,
        			'height' => 460,
        			'opacity' => 100,
        			'radius' => 0,
        		],
        		[
        			'url' => $avatar_path,//主播头像
        			'stream' => 0,//字符串图像流
        			'left' => 27,
        			'top' => 493,
        			'right' => 0,
        			'bottom' => 0,
        			'width' => 38,
        			'height' => 38,
        			'opacity' => 100,
        			'radius' => 60,
        		],
        		[
        			'url' => $qrcode_path,//二维码
        			'stream' => 0,//字符串图像流
        			'left' => 319,
        			'top' => 511,
        			'right' => 0,
        			'bottom' => 0,
        			'width' => 110,
        			'height' => 110,
        			'opacity' => 100,
        			'radius' => 0,
        		]
        	],
        	'background'=> './static/qrcode/wxapp/live_share_bg.png',          //背景图
        	'text' => [
        		[
        			'text'=>$nickname,
        			'left'=>70,
                    'top'=>514,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>'14',
                    'fontColor'=>'102,102,102',
        		],
        		[
        			'text'=>'直播ID：'.$now_live['live_no'],
        			'left'=>27,
                    'top'=>564,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>'14',
                    'fontColor'=>'102,102,102',
        		],
        		[
        			'text'=>$title,
        			'left'=>27,
                    'top'=>600,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>'16',
                    'fontColor'=>'0,0,0',
        		]        		
        	],
        ];
        $poster = D("Village_group")->createPoster($config,'./runtime/tempimg/'.uniqid().'.jpg');
        if($poster){
        	M("Live_show")->where(['live_id'=>$now_live['live_id']])->data(['poster' => substr($poster,1)])->save();
        	return substr($poster,1);
        }
        else{
        	return false;
        }
	}

	//上下滑动屏幕（获得直播ID）
	public function slide(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '当前直播ID未填写');
		}
		$type = I("type");//1=上滑 2=下滑
		if(empty($type)){
			$this->returnCode(1003, [], '滑动方式未填写');	
		}
		$where = [
			'is_del' => 0,
			'status' => 1
		];
		if($type == '1'){
			$where['live_id'] = ['lt',$live_id];
		}
		else{
			$where['live_id'] = ['gt',$live_id];	
		}
		$live = M("Live_show")->where($where)->field("live_id")->find();
		if(empty($live)){
			$this->returnCode(0, ['live_id'=>0]);
		}

		$this->returnCode(0, ['live_id'=>$live['live_id']]);
	}

	//短视频列表
	public function video_list(){
		$page = I("page", 1);
		$mer_id = I("mer_id", 0);
		$limit = 10;//默认10条
		$id = I("id", 0);//分类id
		$return['slider'] = D("Adver")->get_adver_by_key("live_video_list_slider", 6);
		$return['live_video_list'] = [];
		$where = [
			's.is_del' => 0
		];
		$limit = $limit*($page-1).','.$limit;

		// 首页分类信息
		if($id){
			$return['slider'] = [];
			$whereCategory = ['id'=>$id];
			$category = D('Live_category')->where($whereCategory)->find();
			if(empty($category) || empty($category['categorys'])){
				$this->returnCode(0, $return);
			}
			
			// 绑定的直播分类id或短视频话题id
			$categorys = explode(',', trim($category['categorys'],','));
            $string="";
			foreach ($categorys as $key => $value) {
				if($key == 0){
					// $string = 'FIND_IN_SET("'.$value.'",s.categorys)';
					$string = 's.categorys like "%,'.$value.',%"';
				}
				else{
					// $string .= ' OR FIND_IN_SET("'.$value.'",s.categorys)';
					$string .= ' OR s.categorys like "%,'.$value.',%"';
				}
			}

			$where['_string'] = $string;
			
		}
		if($mer_id){//私域入口 即A商家私域入口进入后 只能浏览 包含A商家商品的视频（视频列表页，和 看视频时前后滑动视频去到上一个 下一个视频 ）

			$where['g.mer_id'] = $mer_id;
			$where['bg.is_del'] = 0;
			$video_list = D("Live_video")->alias("s")->field('s.*')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($where)->order("s.video_id desc")->limit($limit)->group('s.video_id')->select();
		}else{
			$video_list = D("Live_video")->alias("s")->where($where)->order("video_id desc")->limit($limit)->select();
		}

		$return['live_video_list'] = $this->deal_video_list($video_list, $mer_id);
		$this->returnCode(0, $return);
	}

	//我的短视频
	public function my_video_list(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$page = I("page", 1);
		$limit = 10;//默认10条
		$return['live_video_list'] = [];
		$where = [
			's.is_del' => 0,
			's.uid' => $this->_uid
		];
		
		$limit = $limit*($page-1).','.$limit;
		$video_list = D("Live_video")->alias("s")->where($where)->order("video_id desc")->limit($limit)->select();
		
		$return['live_video_list'] = $this->deal_video_list($video_list);
		$this->returnCode(0, $return);
	}

	//删除我的短视频
	public function del_my_live_video(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}

		$video_id = I("video_id");
		if(empty($video_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		$now_video = M("Live_video")->where(['video_id' => $video_id])->find();

		if($now_video['uid'] != $this->_uid || empty($now_video)){
			$this->returnCode(1003, [], '无权删除该短视频');
		}

		M("Live_video")->where(['video_id' => $video_id])->data(['is_del' => 1])->save();
		$this->returnCode(0, ['del' => 1]);
	}

	//发布短视频
	public function create_video(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$thumbimg = I("thumbimg");
		$video_url = I("video_url");
		$desc = I("desc");
		$goodids = I("goodids");
		$plat_video_id = I("video_id");
		$categorys = trim(I("categorys"));
		$cover_image = I("cover_image");//上传封面图

		if(empty($video_url)){
			$this->returnCode(1003, [], '短视频不存在！');
		}

		if(C('config.video_bind_goods') == 1 && empty($goodids)){
			$this->returnCode(1003, [], '请选择绑定商品！');
		}

		$data = [
			'uid' => $this->_uid,
			'play_url' => $video_url,
			'title' => $desc,
            'lng' => I("lng",0),
            'lat' => I("lat",0),
			'img' => '',
			'zan' => 0,
			'comments' => 0,
			'is_del' => 0,
			'addtime' => date('Y-m-d H:i:s'),
			'views' => 0,
			'plat_video_id' => $plat_video_id
		];
		if($categorys){
			$data['categorys'] = ','.$categorys.',';
		}
		
        // 封面图
        if($cover_image){
            $data['img'] = $cover_image;
        }elseif($plat_video_id){
			$tmp = M("Live_video_plat_notify")->where(['file_id'=>$plat_video_id])->find();
			if($tmp){
				$data['img'] = $tmp['img']?:'';
			}
		}
		if(empty($data['img'])) {
			$data['img'] = $thumbimg;
		}
		fdump_api([$data, $_POST, $_GET], 'video/create_video_data', 1);
		$insert_id = M("Live_video")->data($data)->add();
		if($insert_id){
			$goods_arr = explode(',', $goodids);
			for($i = 0 ; $i < count($goods_arr); $i++){
				$add = [
					'video_id' => $insert_id,
					'goods_id' => $goods_arr[$i],
					'is_del' => 0,
					'no' => $i+1,
					'sale_nums' => 0
				];
				M("Live_video_goods")->data($add)->add();
			}
			$this->returnCode(0, ['submit' => 1, 'video_id' => $insert_id]);
		}
		else{
			$this->returnCode(1004, [], '发布失败!');
		}
	}


	//领取短视频优惠券
	public function get_live_video_coupon(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}

		$coupon_id = I("coupon_id");//优惠券id
	
		if(empty($coupon_id)){
			$this->returnCode(1003, [], 'coupon ID未填写');
		}
		$get = D("Card_new_coupon")->had_pull($coupon_id, $this->_uid);
		if($get['error_code'] > 0){
			switch ($get['error_code']) {
				case 1:
					$this->returnCode(1001, [], '优惠券不存在');
					break;
				case 2:
					$this->returnCode(1001, [], '优惠券已过期');
					break;
				case 3:
					// $this->returnCode(1001, [], '当前优惠券领完了');
					$this->returnCode(0, ['submit' => 2]);
					break;
				case 4:
					$this->returnCode(1001, [], '当前优惠券只允许新用户');
					break;
				case 5:
					$this->returnCode(1001, [], '您已经领取过不能在领取了');
					break;
				
				default:
					$this->returnCode(1001, [], '未知错误');
					break;
			}
		}
		$this->returnCode(0, ['submit' => 1]);
	}

	//添加短视频关联商品
	public function video_add_goods(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}

		$goodids = I("goodids");
		$plat_video_id = I("video_id");
	

		if(empty($plat_video_id) || empty($goodids)){
			$this->returnCode(1003, [], '必填参数未填完整！');
		}

		$goods_arr = explode(',', $goodids);
		$add = [];

		$already_goods = D("Live_video_goods")->get_goods_by_liveid($plat_video_id);
		$no = 0;
		foreach ($already_goods as $key => $value) {
			$no = $value['no'];
		}
		for($i = 0 ; $i < count($goods_arr); $i++){
			if(!in_array($goods_arr[$i], array_column($already_goods, 'goods_id')) && !empty($goods_arr[$i])){
				$add[] = [
					'video_id' => $plat_video_id,
					'goods_id' => $goods_arr[$i],
					'is_del' => 0,
					'no' => $no+$i+1,
					'sale_nums' => 0
				];
			}
		}

		if(empty($add)){//没有要添加的商品
			$goods = D('Live_video_goods')->get_goods_by_liveid($plat_video_id);
			$goods = D('Live_video_goods')->formatGoods($plat_video_id,$goods);
			$this->returnCode(0,[],'添加成功');
		}

		$res = M("Live_video_goods")->addAll($add);
		$goods = D('Live_video_goods')->get_goods_by_liveid($plat_video_id);
		$goods = D('Live_video_goods')->formatGoods($plat_video_id,$goods);
		
		if($res){
			$this->returnCode(0,[],'添加成功');
		}else{
			$this->returnCode(1, [], '添加失败!');
		}
	}


	//获得短视频关联商品
	public function get_video_goods(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}

		$plat_video_id = I("video_id");
		if(empty($plat_video_id)){
			$this->returnCode(1003, [], '必填参数未填完整！');
		}

		$returnArr = [
			'on_sale_list' => [],//上架商品列表
			'off_sale_list' => [],//下架商品列表
			'off_sale_count' => 0,//下架商品数量
		];

		$goods = D('Live_video_goods')->get_goods_by_liveid($plat_video_id);
		$goods = D('Live_video_goods')->formatGoods($plat_video_id,$goods);
		foreach ($goods as $key => $value) {
			if($value['status'] == 1){
				$returnArr['on_sale_list'][] = $value;
			}else{
				$returnArr['off_sale_list'][] = $value;
				$returnArr['off_sale_count']++;
			}
		}
		$this->returnCode(0, $returnArr);
		
	}

	//短视频商品上下架
	public function video_edit_goods_status(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}

		$goodid = I("goodid");
		$plat_video_id = I("video_id");
	

		if(empty($plat_video_id) || empty($goodid)){
			$this->returnCode(1003, [], '必填参数未填完整！');
		}

		// 获得商品详情
		$where = [
			'goods_id' => $goodid,
			'video_id' => $plat_video_id
		];
		$goods = M("Live_video_goods")->where($where)->find();
		if(empty($goods)){
			$this->returnCode(1, [], L_('商品不存在或已删除'));
		}

		// 修改状态
		$saveData = [];
		$msg = '';
		if($goods['status'] == 1){
			$saveData['status'] = 0;
			$msg = L_('下架成功');
		}else{
			$saveData['status'] = 1;
			$msg = L_('上架成功');
		}
		
		$res = M("Live_video_goods")->where($where)->data($saveData)->save();
		if($res){
			$this->returnCode(0, [],$msg);
		}else{
			$this->returnCode(1, [], L_('修改失败!'));
		}
	}

	//短视频详情页
	public function livevideo_detail(){
        $lat = I("lat",0);
        $lng = I("lng",0);
		$video_id = I("video_id");
		$mer_id = I("mer_id",0);// 商家私域入口 只看该商家商品包含该商家商品的视频
		if(empty($video_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		$author_id = I("author_id");
  
		$now_live = M("Live_video")->where(['video_id' => $video_id])->find();
		$author_user = D("User")->get_user($now_live['uid']);
		$output = [
			'basic_info' => D("Live_video")->getDetail($video_id, $this->_uid, $mer_id,$lat,$lng),
			'slider' =>[//上下滑动
				'pre_video' => [//上一个
					
				],
				'next_video' => [//下一个
					
				]
			], 
			'share' => [],
            'address'=>[],//地址信息
		];

		if(empty($now_live['title'])){
			$output['share']['share_title'] = '我正在看"'.$author_user['nickname'].'"的精彩短视频，速来围观~';
			$output['share']['share_content'] = '点击进入，查看更多有趣信息';
		}
		else{
			$output['share']['share_title'] = msubstr($now_live['title'], 0, 20);
			$output['share']['share_content'] = '点击进入，一起参与围观~';
		}

        if (empty($this->config['zhibo_version'])) {
            $miniPath = "pages/live_and_video/video/Audience/videoDetails?video_id=" . $now_live['video_id'] . '&mer_id=' . $mer_id;
            $output['share']['share_url'] = C('config.site_url').D('House_village')->base_url . $miniPath;
        } else {
            $miniPath = "pages/live_and_video/mainPage?type=1&video_id=" . $now_live['video_id'] . '&mer_id=' . $mer_id;
            $output['share']['share_url'] = C('config.site_url').D('House_village')->base_url . $miniPath;
        }
		$output['share']['share_image'] =replace_file_domain($now_live['img']);
		$output['share']['share_wx'] = ($this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username']) ? 'wxapp' : 'h5';
		$output['share']['share_wx_info'] = [];
		if($output['share']['share_wx'] == 'wxapp'){
			$output['share']['share_wx_info'] = [
				'userName' 	=> $this->config['pay_wxapp_username'],
				'path' 		=> $output['share']['share_url'],
				'path_mini' =>  $miniPath,
				'image' 	=> replace_file_domain($now_live['img']),
				'title' 	=> ''
			];
			if(empty($now_live['title'])){
				$output['share']['share_wx_info']['title'] = '我正在看"'.$author_user['nickname'].'"的精彩短视频，速来围观~';
			}
			else{
				$output['share']['share_wx_info']['title'] = msubstr($now_live['title'], 0, 20);
			}
		}
		if(empty($output['share']['share_wx_info'])){
			$output['share']['share_wx_info'] = (object) $output['share']['share_wx_info'];
		}

        $condition = [
            's.is_del' => 0
        ];
        $condition['s.video_id'] = ['gt',$video_id];

        if($author_id){
            $condition['s.uid'] = $author_id;
        }

        $zhibo_version = empty($this->config['zhibo_version'])?0:$this->config['zhibo_version']*1;//直播样式，1新版样式
        if($zhibo_version){
            $type = I("type",1);
            $output['address']=[
                'area_name'=>'',
                'distance'=>'',
                'lng'=>$lng,
                'lat'=>$lat
            ];
            if($lat && $lng){
                   if(empty($now_live['lng']*1) || empty($now_live['lat']*1)){
                       $output['address']='';
                   }else{
                       $now_address = D('Area')->cityMatching($now_live['lat'], $now_live['lng']);
                       $output['address']['area_name']=$now_address['area_info']['area_name'];
                       $output['address']['lng']=$now_live['lng'];
                       $output['address']['lat']=$now_live['lat'];
                       $distance = getDistance($lat,$lng,$now_live['lat'],$now_live['lng']);
                       $distance = $distance / 1000;
                       if($distance){
                           $output['address']['distance']=$distance.'km';
                       }else{
                           $output['address']['distance']=0;
                       }
                   }
            }
            switch ($type){
                case 1://推荐
                    if($mer_id){
                        $condition['g.mer_id'] = $mer_id;
                        $condition['bg.is_del'] = 0;
                    }
                    // 首页分类信息
                    //上一个
                    $condition['s.video_id'] = ['gt',$video_id];
                    $pre_live = D("Live_video")->alias("s")->field('s.video_id,s.img,s.play_url')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($condition)->order("s.video_id desc")->limit(5)->group('s.video_id')->select();
                    if($pre_live){
                        krsort($pre_live);
                        foreach ($pre_live as $key => $value) {
                            $output['slider']['pre_video'][] = D("Live_video")->getDetail($value['video_id'], $this->_uid,0,$lat,$lng);
                        }
                    }
                    //下一个
                    $condition['s.video_id'] = ['lt',$video_id];
                    $next_live = D("Live_video")->alias("s")->field('s.video_id,s.img,s.play_url')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($condition)->order("s.video_id desc")->limit(5)->group('s.video_id')->select();
                    if($next_live){
                        foreach ($next_live as $key => $value) {
                            $output['slider']['next_video'][] = D("Live_video")->getDetail($value['video_id'], $this->_uid,0,$lat,$lng);
                        }
                    }
                    break;
                case 2://关注
                    $where['f.is_del'] = 0;
                    $where['f.uid'] = $this->_uid;
                    $where['s.video_id'] = ['lt',$video_id];
                    if($mer_id){
                        $where['g.mer_id'] = $mer_id;
                        $where['bg.is_del'] = 0;
                        $pre_live = M("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_video as s on s.uid=f.follow_uid  LEFT JOIN '.C('DB_PREFIX').'live_show_goods as bg on bg.live_id = s.live_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id")->order("s.video_id asc")->limit(5)->group('s.video_id')->select();
                    }else{
                        $pre_live = M("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_video as s on s.uid=f.follow_uid ")->where($where)->order("s.video_id asc")->limit(5)->group('s.video_id')->select();
                    }
                    if($pre_live){
                        krsort($pre_live);
                        foreach ($pre_live as $key => $value) {
                            $output['slider']['pre_video'][] = D("Live_video")->getDetail($value['video_id'], $this->_uid,0,$lat,$lng);
                        }
                    }

                    $where['s.video_id'] = ['gt',$video_id];
                    if($mer_id){
                        $where['g.mer_id'] = $mer_id;
                        $where['bg.is_del'] = 0;
                        $next_live = M("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_video as s on s.uid=f.follow_uid  LEFT JOIN '.C('DB_PREFIX').'live_show_goods as bg on bg.live_id = s.live_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id")->order("s.video_id asc")->limit(5)->group('s.video_id')->select();
                    }else{
                        $next_live = M("Live_follow")->alias("f")->field('s.*')->join("left join ".C("DB_PREFIX")."live_video as s on s.uid=f.follow_uid ")->where($where)->order("s.video_id asc")->limit(5)->group('s.video_id')->select();
                    }
                    if($next_live){
                        foreach ($next_live as $key => $value) {
                            $output['slider']['next_video'][] = D("Live_video")->getDetail($value['video_id'], $this->_uid,0,$lat,$lng);
                        }
                    }
                    if(empty($output['slider']['next_video'])){
                        $output['slider']['next_video']=$output['slider']['pre_video'];
                    }
                    break;
                case 3://附近
                    if($mer_id){
                        $condition['g.mer_id'] = $mer_id;
                        $condition['bg.is_del'] = 0;
                    }
                    $condition['s.video_id'] = ['lt',$video_id];
                    // 首页分类信息
                    $pre_live = D("Live_video")->alias("s")->field('s.video_id,s.img,s.play_url')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($condition)->order("s.video_id asc")->limit(5)->group('s.video_id')->select();
                    if($pre_live){
                        krsort($pre_live);
                        foreach ($pre_live as $key => $value) {
                            $output['slider']['pre_video'][] = D("Live_video")->getDetail($value['video_id'], $this->_uid,0,$lat,$lng);
                        }
                    }
                    $condition['s.video_id'] = ['gt',$video_id];
                    $next_live = D("Live_video")->alias("s")->field('s.video_id,s.img,s.play_url')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($condition)->order("s.video_id asc")->limit(5)->group('s.video_id')->select();
                    if($next_live){
                        foreach ($next_live as $key => $value) {
                            $output['slider']['next_video'][] = D("Live_video")->getDetail($value['video_id'], $this->_uid,0,$lat,$lng);
                        }
                    }
                    if(empty($output['slider']['next_video'])){
                        $output['slider']['next_video']=$output['slider']['pre_video'];
                    }
                    break;
            }
        }
        else{
            if($mer_id){
                $condition['g.mer_id'] = $mer_id;
                $condition['bg.is_del'] = 0;
            }
            $pre_live = D("Live_video")->alias("s")->field('s.video_id,s.img,s.play_url')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($condition)->order("s.video_id asc")->limit(5)->group('s.video_id')->select();
            if($pre_live){
                krsort($pre_live);
                foreach ($pre_live as $key => $value) {
                    $output['slider']['pre_video'][] = D("Live_video")->getDetail($value['video_id'], $this->_uid);
                }
            }
            $condition['s.video_id'] = ['lt',$video_id];
            $next_live = D("Live_video")->alias("s")->field('s.video_id,s.img,s.play_url')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($condition)->order("s.video_id desc")->limit(5)->group('s.video_id')->select();
            if($next_live){
                foreach ($next_live as $key => $value) {
                    $output['slider']['next_video'][] = D("Live_video")->getDetail($value['video_id'], $this->_uid);
                }
            }
        }
		D("Live_video")->where(['video_id'=>$video_id])->setInc("views", 1);

		$this->returnCode(0, $output);
	}

	//短视频-分享接口（独立增加的）
	public function livevideo_share(){
		$video_id = I("video_id");
		if(empty($video_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		$now_live = M("Live_video")->where(['video_id' => $video_id])->find();
		$author_user = D("User")->get_user($now_live['uid']);
		if(empty($now_live['title'])){
			$output['share_title'] = '我正在看"'.$author_user['nickname'].'"的精彩短视频，速来围观~';
			$output['share_content'] = '点击进入，查看更多有趣信息';
		}
		else{
			$output['share_title'] = msubstr($now_live['title'], 0, 20);
			$output['share_content'] = '点击进入，一起参与围观~';
		}
		
		$output['share_url'] = $this->config['site_url']."/packapp/plat/pages/live_and_video/mainPage?type=1&video_id=".$now_live['video_id'];
		$output['share_image'] =replace_file_domain($now_live['img']);
		$output['share_wx'] = ($this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username']) ? 'wxapp' : 'h5';
		$output['share_wx_info'] = [];
		if($output['share_wx'] == 'wxapp'){
			$output['share_wx_info'] = [
				'userName' 	=> $this->config['pay_wxapp_username'],
				'path' 		=> $this->config['site_url']."/packapp/plat/pages/live_and_video/mainPage?type=1&video_id=".$now_live['video_id'],
				'path_mini' => "/pages/live_and_video/mainPage?type=1&video_id=".$now_live['video_id'],
				'image' 	=> replace_file_domain($now_live['img']),
				'title' 	=> ''
			];
			if(empty($now_live['title'])){
				$output['share_wx_info']['title'] = '我正在看"'.$author_user['nickname'].'"的精彩短视频，速来围观~';
			}
			else{
				$output['share_wx_info']['title'] = msubstr($now_live['title'], 0, 20);
			}
		}
		$this->returnCode(0, $output);
	}

	//短视频-点赞
	public function livevideo_zan(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("video_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		$now_live = M("Live_video")->where(['video_id' => $live_id])->find();
		$output = [
			'zan' => $now_live['zan']
		];
		$type = 0;
		$data = ['video_id' => $live_id, 'uid' => $this->_uid];
		if($has = M("Live_video_zan")->where($data)->find()){
			if($has['is_del'] == '1'){
				$data['is_del'] = 0;
				$type = 1;
			}
			else{
				$data['is_del'] = 1;
			}
			M("Live_video_zan")->where(['id'=>$has['id']])->data($data)->save();
		}
		else{
			$data['is_del'] = 0;
			$data['addtime'] = date('Y-m-d H:i:s');
			M("Live_video_zan")->data($data)->add();
			$type = 1;
		}
		$zan = 1;
		if($type == 1){
			if(D("Live_video")->where(['video_id'=>$live_id])->setInc("zan", $zan)){
				$output['zan']++;
				$this->returnCode(0, $output);
			}
			else{
				$this->returnCode(1004, [], '操作失败');
			}
		}
		else{
			if(D("Live_video")->where(['video_id'=>$live_id])->setDec("zan", $zan)){
				$output['zan']--;
				$this->returnCode(0, $output);
			}
			else{
				$this->returnCode(1004, [], '操作失败');
			}
		}
		$this->returnCode(0, $output);
	}

	//短视频--评论
	public function comment_submit(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("video_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		$comment = trim(I("comment"));
		$now_live = M("Live_video")->where(['video_id' => $live_id])->find();
		$data = [
			'uid' => $this->_uid,
			'video_id' => $live_id,
			'comment' => $comment,
			'addtime' => date('Y-m-d H:i:s'),
			'is_del' => 0,
			'first' => intval(I("first")),
			'ait' => intval(I("ait"))
		];
		$insert_id = M("Live_video_comment")->data($data)->add();
		if($insert_id){
			D("Live_video")->where(['video_id'=>$live_id])->setInc("comments", 1);
			$this->returnCode(0, ['submit' => 1]);
		}
		else{
			$this->returnCode(1004, [], '评论失败!');
		}
	}

	//短视频评论列表
	public function livevideo_comments(){
		$live_id = I("video_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		$page = I("page", '1');
		$limit = 10;//默认10条

		$where = [];
		$where['c.is_del'] = 0;
		$where['c.video_id'] = $live_id;
		$where['c.first'] = 0;
		$where['c.ait'] = 0;
		$limit = $limit*($page-1).','.$limit;
		$data = M("Live_video_comment")->alias("c")->join("left join ".C("DB_PREFIX")."user as u on u.uid=c.uid")->field("c.id,c.comment,c.addtime as date,u.nickname,u.avatar,c.id as comment_id,c.uid")->where($where)->limit($limit)->order("c.id desc")->select();
		foreach ($data as $key => $value) {
			$data[$key]['date'] = date('m-d', strtotime($value['date']));
			$data[$key]['avatar'] = $value['avatar'] ? : $this->config['site_url'].'/static/images/tx.png';
			$son_list = M("Live_video_comment")->alias("c")->join("left join ".C("DB_PREFIX")."user as u on u.uid=c.uid")->field("c.id,c.comment,c.addtime as date,u.nickname,u.avatar,c.uid,c.ait")->where(['c.first'=>$value['id'], 'c.is_del'=>0, 'c.ait'=>['gt',0]])->order("c.id asc")->select();
			foreach ($son_list as $son) {
				$temp = [
					'id' => $son['id'],
					'uid' => $son['uid'],
					'avatar' => $son['avatar'] ? : $this->config['site_url'].'/static/images/tx.png',
					'nickname' => $son['nickname'],
					'comment' => $son['comment'],
					'date' => date('m-d', strtotime($son['date'])),
				];
				$ait = M("Live_video_comment")->where(['id'=> $son['ait']])->find();
				$temp['ait_uid'] = $ait['uid'];
				$temp['ait_nickname'] = D('User')->get_user($ait['uid'])['nickname'];
				$data[$key]['son_list'][] = $temp;
			}
		}
		unset($where['c.first'], $where['c.ait']);
		$comments_nums = M("Live_video_comment")->alias("c")->join("left join ".C("DB_PREFIX")."user as u on u.uid=c.uid")->where($where)->count();
		$this->returnCode(0, ['comments_list' => $data, 'comments_nums' => $comments_nums]);
	}

	//生成上传视频的签名
	public function get_sign(){
		$secret_id = $this->config['live_secret_id'];
		$secret_key = $this->config['live_secret_key'];

		// 确定签名的当前时间和失效时间
		$current = time();
		$expired = $current + 86400;  // 签名有效期：1天

		// 向参数列表填入参数
		$arg_list = array(
		    "secretId" => $secret_id,
		    "currentTimeStamp" => $current,
		    "expireTime" => $expired,
		    "random" => rand(),
		    "procedure" => "livevideo_jietu"
		);

		// 计算签名
		$original = http_build_query($arg_list);
		$signature = base64_encode(hash_hmac('SHA1', $original, $secret_key, true).$original);

		$this->returnCode(0, ['sign' => $signature]);
	}

	//分享
	public function video_share(){
		$live_id = I("video_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		D("Live_video")->where(['video_id'=>$live_id])->setInc("share", 1);
		$this->returnCode(0, ['submit' => 1]);
	}

	public function livevideo_author_index(){
		$author_id = I("author_id");
		if(empty($author_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		$page = I("page", 1);
		$limit = 10;//默认10条
		$return['live_video_list'] = [];
		$where = [
			's.is_del' => 0,
			's.uid' => $author_id
		];
		
		$limit = $limit*($page-1).','.$limit;
		$video_list = D("Live_video")->alias("s")->where($where)->order("video_id desc")->limit($limit)->select();
		
		
		$return['live_video_list'] = $this->deal_video_list($video_list);
		$this->returnCode(0, $return);
	}

	//IM历史消息列表
	public function im_history_list(){
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$page = I("page", 1);
		$limit = 20;//默认20条
		$where = [
			'live_id' => $live_id
		];
		$limit = $limit*($page-1).','.$limit;
		$data = M("Live_show_comment")->where($where)->limit($limit)->order("id desc")->select();
		foreach ($data as $key => $value) {
			$temp = explode('{pigcms}', $value['content']);
			$data[$key]['nickname'] = $temp[0] ? : '未知';
			$data[$key]['content'] = $temp[1] ? : '';
		}
		$return['history'] = $data;
		$this->returnCode(0, $return);
	}

	//
	public function update_show(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$live_id = I("live_id");
		if(empty($live_id)){
			$this->returnCode(1003, [], '直播ID未填写');
		}
		$thumbimg_1 = I("thumbimg_1");
		$thumbimg_2 = I("thumbimg_2");
		$nickname = trim(I("nickname"));
		$title = trim(I("title"));
		$desc = trim(I("desc"));
		$estimate_time = I("estimate_time");
		$category_1 = I("category_1");
		$category_2 = I("category_2");
		$area_id = I("area_id");
		$Model = D("Live_show");
		$lng = I("lng");
		$lat = I("lat");
		$address = I("address");
		$data = [];
		if($nickname){
			$data['live_nickname'] = $nickname;
		}
		if($thumbimg_1){
			$data['thumbimg_1'] = $thumbimg_1;
		}
		if($thumbimg_2){
			$data['thumbimg_2'] = $thumbimg_2;
		}
		if($title){
			$data['title'] = $title;
		}
		if($desc){
			$data['desc'] = $desc;
		}
		if($estimate_time){
			$data['estimate_time'] = $estimate_time;
		}
		if($category_1){
			$data['category_1'] = $category_1;
		}
		if($category_2){
			$data['category_2'] = $category_2;
		}
		if($area_id){
			$data['area_id'] = $area_id;
		}
		if($lng && $lat){
			$data['lng'] = $lng;
			$data['lat'] = $lat;
			$data['address'] = $address;
		}
        $data['poster'] = '';
        $live = $Model->where(['live_id'=>$live_id])->find();
        if(file_exists('.'.$live['poster'])){
            unlink('.'.$live['poster']);
        }
		$save_id = $Model->where(['live_id'=>$live_id])->data($data)->save();
		if($save_id){
			$this->returnCode(0, ['submit' => 1]);
		}
		else{
			$this->returnCode(1004, [], '修改失败!');
		}
	}

	//短视频商品佣金等
	public function video_goods_list(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$video_id = I("video_id");
		if(empty($video_id)){
			$this->returnCode(1003, [], '短视频ID未填写');
		}
		$order = "no desc";
		$data = D("Live_video_goods")->get_goods_by_liveid($video_id, $order);
		$goods = D("Live_goods")->get_by_ids(array_column($data, 'goods_id'), ['img','name','price','stock'], false);
		$output = [
			'goods_list' => []
		];
		$sales = array_column($data, 'sale_nums', 'goods_id');
		foreach ($data as $key => $value) {
			if(empty($goods[$value['goods_id']])){
				continue;
			}
			$temp = [
				'no' => $value['no'],
				'id' => $value['goods_id'],
				'img' => $goods[$value['goods_id']]['img'],
				'name' => $goods[$value['goods_id']]['name'],
				'price' => $goods[$value['goods_id']]['price'],
				'stock' => $goods[$value['goods_id']]['stock'],
				'commission' => 0,
                'offline' => $goods[$value['goods_id']]['offline'],
				'sales' => $sales[$value['goods_id']]['sale_nums']
			];
			//统计佣金
			//外卖+商城
			if($goods[$value['goods_id']]['business'] == 'shop' || $goods[$value['goods_id']]['business'] == 'mall'){
				$temp['commission'] = M("Shop_order_detail")->where(['goods_id'=>$goods[$value['goods_id']]['real_goods_id'], 'is_livevideo'=>1, 'livevideo_id'=>$video_id])->sum("liveshow_money");
			}
			elseif($goods[$value['goods_id']]['business'] == 'group'){
				$temp['commission'] = M("Group_order")->where(['group_id'=>$goods[$value['goods_id']]['real_goods_id'], 'is_livevideo'=>1, 'livevideo_id'=>$video_id])->sum("live_author_money");
			}
			elseif($goods[$value['goods_id']]['business'] == 'mall3'){
				$temp['commission'] = M("Mall_order_detail")->where(['goods_id'=>$goods[$value['goods_id']]['real_goods_id'], 'is_livevideo'=>1, 'livevideo_id'=>$video_id])->sum("live_author_money");
			}
			$output['goods_list'][] = $temp;
		}
		$output['goods_nums'] = count($output['goods_list']);
		$output['commission'] = array_sum(array_column($output['goods_list'], 'commission'));
		$output['sales_nums'] = array_sum(array_column($output['goods_list'], 'sales'));
		$this->returnCode(0, $output);
	}

	//我喜欢的短视频列表
	public function my_fav_video_list(){
		if(empty($this->_uid)){
			$this->returnCode(1002, [], '未登录状态不可访问该接口');
		}
		$page = I("page", 1);
		$mer_id = I("mer_id", 0);
		$limit = 10;//默认10条
		$return['video_list'] = [];
		$where = [
			'zan.is_del' => 0,
			'zan.uid' => $this->_uid,
		];
		
		$limit = $limit*($page-1).','.$limit;
		
		if($mer_id){//私域入口 即A商家私域入口进入后 只能浏览 包含A商家商品的视频（视频列表页，和 看视频时前后滑动视频去到上一个 下一个视频 ）
			$where['g.mer_id'] = $mer_id;
			$where['bg.is_del'] = 0;
			$video_list = D("Live_video_zan")->alias("zan")->field('s.*')->join(' left join '.C("DB_PREFIX").'live_video as s on s.video_id=zan.video_id LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($where)->order("s.video_id desc")->limit($limit)->group('s.video_id')->select();
		}else{
			$video_list = M("Live_video_zan")->alias("zan")->join("left join ".C("DB_PREFIX")."live_video as s on s.video_id=zan.video_id")->where($where)->order("zan.addtime desc")->limit($limit)->select();
		}

		$return['video_list'] = $this->deal_video_list($video_list, $mer_id);
		$this->returnCode(0, $return);
	}

	public function video_category_list(){
		$page = I("page", 1);
		$limit = 10;//默认10条
		$return['data_list'] = [];
		$keyword = trim(I("keyword"));
		$where = [
			'is_del' => 0
		];
		if($keyword){
			$where['name'] = ['like', '%'.$keyword.'%'];
		}
		
		$limit = $limit*($page-1).','.$limit;
		$data = M("Live_video_category")->where($where)->order("orderby desc")->limit($limit)->select();
		
		foreach ($data as $key => $value) {
			$data[$key]['canyu'] = M("Live_video")->where(['categorys'=>['like','%,'.$value['id'].',%'], 'is_del'=>0])->count();
		}
		$return['data_list'] = $data;
		$this->returnCode(0, $return);
	}

	public function category_detail(){
		$cate_id = I("category_id");
		if(empty($cate_id)){
			$this->returnCode(1003, [], '话题ID未填写');
		}
		$detail = M("Live_video_category")->where(['id'=>$cate_id])->find();
		$detail['head_img'] = C("config.site_url").'/static/images/live_video_category_default.png';

		$detail['share']['share_title'] = '#'.$detail['name'].'#话题';
		$detail['share']['share_content'] = $detail['des'];		
		
		$detail['share']['share_url'] = $this->config['site_url']."/packapp/plat/pages/live_and_video/video/Audience/talkingDetails?cateId=".$cate_id;
		$detail['share']['share_image'] = $detail['head_img'];
		$detail['share']['share_wx'] = ($this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username']) ? 'wxapp' : 'h5';
		$detail['share']['share_wx_info'] = [];
		if($detail['share']['share_wx'] == 'wxapp'){
			$detail['share']['share_wx_info'] = [
				'userName' 	=> $this->config['pay_wxapp_username'],
				'path' 		=> $this->config['site_url']."/packapp/plat/pages/live_and_video/video/Audience/talkingDetails?cateId=".$cate_id,
				'path_mini' => "/packapp/plat/pages/live_and_video/video/Audience/talkingDetails?cateId=".$cate_id,
				'image' 	=> replace_file_domain($detail['head_img']), 
				'title' 	=> ''
			];
			$detail['share']['share_wx_info']['title'] = '#'.$detail['name'].'#话题';
		}
		$this->returnCode(0, $detail);
	}

	public function category_video_list(){
		$cate_id = I("category_id");
		if(empty($cate_id)){
			$this->returnCode(1003, [], '话题ID未填写');
		}
		$page = I("page", 1);
		$mer_id = I("mer_id", 0);
		$limit = 10;//默认10条
		$return['video_list'] = [];
		$where = [
			's.is_del' => 0,
			's.categorys' => ['like', '%,'.$cate_id.',%']
		];
		
		$limit = $limit*($page-1).','.$limit;

		if($mer_id){//私域入口 即A商家私域入口进入后 只能浏览 包含A商家商品的视频（视频列表页，和 看视频时前后滑动视频去到上一个 下一个视频 ）

			$where['g.mer_id'] = $mer_id;
			$where['bg.is_del'] = 0;
			$video_list = D("Live_video")->alias("s")->field('s.*')->join(' LEFT JOIN '.C('DB_PREFIX').'live_video_goods as bg on bg.video_id = s.video_id LEFT JOIN '.C('DB_PREFIX').'live_goods as g on bg.goods_id = g.id')->where($where)->order("s.video_id desc")->limit($limit)->group('s.video_id')->select();
		}else{
			$video_list = D("Live_video")->alias("s")->where($where)->order("video_id desc")->limit($limit)->select();
		}
		
		
		$return['video_list'] = $this->deal_video_list($video_list, $mer_id);
		$this->returnCode(0, $return);
	}

	private function deal_video_list($video_list,$mer_id=0){
		if(empty($video_list)) return [];
		foreach ($video_list as $key => $value) {
			$video_list[$key]['img'] = replace_file_domain($value['img']);
			$video_list[$key]['zan'] = $value['zan'] > 9999 ? ($value['zan']/10000)."万" : $value['zan'];
			$zhubo = D("User")->get_user($value['uid']);
			$video_list[$key]['avatar'] = $zhubo['avatar'];
			$video_list[$key]['author_id'] = $zhubo['uid'];
			$video_list[$key]['is_zan'] = $this->_uid ? (M("Live_video_zan")->where(['video_id'=>$value['video_id'], 'is_del'=>0, 'uid' => $this->_uid])->find() ? 1 : 0) : 0;
			$video_list[$key]['nickname'] = $zhubo['nickname'];
			$video_list[$key]['commonts'] = M("Live_video_comment")->where(['is_del'=>0,'video_id'=>$value['video_id']])->count();
			$video_list[$key]['play_url'] = $value['play_url'];
			$video_list[$key]['cate_list'] = D("Live_video_category")->getCategory($value['categorys']);
			$goodids = implode(',',array_column(M("Live_video_goods")->where(['video_id'=>$value['video_id'], 'is_del'=>0])->field("goods_id")->select(), 'goods_id'));
			$param = [];
			if($mer_id){
				$param['mer_id'] = $mer_id;
			}
			$video_list[$key]['goods_list'] = D("Live_goods")->get_by_ids($goodids, ['img', 'price', 'name'], true, $param);
			if ($video_list[$key]['goods_list']) {
                foreach ($video_list[$key]['goods_list'] as $gk => $gv) {
			        if ($gv['offline'] == 1) {
			            unset($video_list[$key]['goods_list'][$gk]);
                    }
                }
                $video_list[$key]['goods_list'] = array_values($video_list[$key]['goods_list']);
            }
			$video_list[$key]['goods_nums'] = count($video_list[$key]['goods_list']);
			$video_list[$key]['share'] = $value['share'] ? : 0;
            $video_list[$key]['follow']= D("Live_follow")->where(['uid'=>$this->_uid, 'is_del'=>0, 'follow_uid'=>$zhubo['uid']])->find() ? 1 : 0;
		}
		return $video_list;
	}

	public function get_record_info(){
		import("@.ORG.TencentLive");
		$live = new TencentLive();
		$type = $_GET['type'];
		if($type == 'tpl'){
			$live->get_record_template();
		}
		elseif($type == 'rule'){
			$live->get_record_rule();
		}
	}

    //直播间商品新订单通知轮询接口
    public function live_new_order_notification(){
        $live_id = I("live_id");
        $goods_id = I("goods_id");
        $return_data = [
            'total' => 0,
        ];
        if($goods_id&&$live_id){
            $data = D("Live_show_goods")->where(['live_id'=>$live_id,'goods_id'=>$goods_id,'is_del'=>0])->find();
            if($data&&$data['sale_nums']>0){
                $return_data['total'] = (int)$data['sale_nums'];
            }
        }
        $this->returnCode(0, $return_data);
    }
}