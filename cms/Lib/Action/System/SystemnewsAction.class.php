<?php
	class SystemnewsAction extends BaseAction{
		public function index(){
			$news = D('System_news')->select();
			$this->assign("news",$news[0]);
			$category = D('System_news_category')->order('sort DESC')->select();
			$this->assign("category",$category);
			$this->display();
		}
		public function news(){
			$province_id = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
        	$city_id = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
        	if ($this->system_session['area_id']) {
				$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
				if($now_area['area_type']==3){
					die("区域管理员没有权限");
				}elseif($now_area['area_type']==2){
					$area_index = 'city_id';
				}elseif($now_area['area_type']==1){
					$area_index = 'province_id';
				}
				$this->assign('admin_area', $now_area['area_type']);
				$condition_news[$area_index] = $this->system_session['area_id'];
			}
			if ($province_id) {
	            $condition_news['province_id'] = $province_id;
	        }
	        if ($city_id) {
	            $condition_news['city_id'] = $city_id;
	        }
			if (!empty($_GET['keyword'])) {
				if ($_GET['searchtype'] == 'id') {
					$condition_news['id'] = $_GET['keyword'];
				} else if ($_GET['searchtype'] == 'title') {
					$condition_news['title'] = array('like', '%' . $_GET['keyword'] . '%');
				}
			}
			$condition_news['category_id'] = $_GET['category_id'];
			//排序 /*/
			$order_string = '`sort` DESC,`add_time` DESC';
			if($_GET['sort']){
				switch($_GET['sort']){
					case 'uid':
						$order_string = '`uid` DESC';
						break;
					case 'lastTime':
						$order_string = '`last_time` DESC';
						break;
					case 'money':
						$order_string = '`now_money` DESC';
						break;
					case 'score':
						$order_string = '`score_count` DESC';
						break;
				}
			}
			$news = M('System_news');
			$count_news = $news->where($condition_news)->count();
			import('@.ORG.system_page');
			$p = new Page($count_news, 15);
			$news_list = $news->field('id,title,add_time,last_time,sort,status,city_id')->where($condition_news)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
			$citys = array_filter(array_column($news_list, 'city_id'));
			$citynames = array_column(M("Area")->where(['area_id'=>['in',$citys]])->select(),'area_name','area_id');
			foreach($news_list as &$val){
				$val['cityname'] = $val['city_id'] ? $citynames[$val['city_id']] : '通用';
			}
			$this->assign('news_list',$news_list);
			$pagebar = $p->show();
			$category_name = D('System_news_category')->where('id='.$_GET['category_id'])->getField('name');
			$this->assign('category_name',$category_name);
			$this->assign('pagebar', $pagebar);
			$this->display();
		}

		public function add_news(){
			if(IS_POST){

				$data['title'] = $_POST['title'];
				if(empty($_POST['content'])){
					$this->error('内容不能为空！');
				}
				$data['content'] = htmlspecialchars_decode($_POST['content']);
				$data['sort'] = $_POST['sort'];
				$data['status'] = $_POST['status'];
				$currency = $_POST['currency'];
				if($currency == '1'){
					$data['province_id'] = 0;
					$data['city_id'] = 0;	
				}
				else{
					$data['province_id'] = intval($_POST['province_idss']);
					$data['city_id'] = intval($_POST['city_idss']);
				}
				$data['category_id'] = $_POST['category_id'];
				$data['add_time'] = $data['last_time']=time();
				if($res = D('System_news')->add($data)){
					D("System_log")->add_log("添加了平台快报",'系统配置项-平台快报',$res);
					
					D('Config')->clearDataCacheDir('plat_news');
					
					$this->success('添加公告成功！');
				}else{
					$this->error('添加失败！');
				}
			}else {
				$many_city = $this->config['many_city'];
        		$this->assign('many_city', $many_city);
				$category = D('System_news_category')->select();
				$this->assign("category",$category);
				$this->display();
			}
		}

		public function edit_news(){
			if(IS_POST){
				$data['title'] = $_POST['title'];
				$data['content'] = htmlspecialchars_decode($_POST['content']);
				$data['sort'] = $_POST['sort'];
				$data['category_id'] = $_POST['category_id'];
				$data['status'] = $_POST['status'];
				$currency = $_POST['currency'];
				if($currency == '1'){
					$data['province_id'] = 0;
					$data['city_id'] = 0;	
				}
				else{
					$data['province_id'] = $_POST['province_idss'];
					$data['city_id'] = $_POST['city_idss'];
				}
				if(isset($_POST['add_time'])){
					$data['add_time']=strtotime($_POST['add_time']);
				}
				$data['last_time'] = time();
				if(D('System_news')->where('id='.$_POST['id'])->save($data) !== false){
					D("System_log")->add_log("编辑了平台快报",'系统配置项-平台快报',$_POST['id']);
					
					D('Config')->clearDataCacheDir('plat_news');
					$this->success('保存成功！');
				}else{
					$this->error('保存失败！');
				}
			}else {
				$many_city = $this->config['many_city'];
        		$this->assign('many_city', $many_city);
				$news = D('System_news')->where(array('id'=>$_GET['id']))->find();
				$news['content'] = replace_file_domain_content($news['content']);
				$this->assign("news",$news);
				$category = D('System_news_category')->select();
				$this->assign("category",$category);
				$this->display();
			}
		}
		
		public function add_category(){
			if(IS_POST){
				$data['name'] = $_POST['name'];
				$data['sort'] = $_POST['sort'];
				$data['status'] = $_POST['status'];
				if($res = D('System_news_category')->add($data)){
					D("System_log")->add_log("添加了平台快报分类",'系统配置项-平台快报',$res);
					$this->success('添加分类成功！');
				}else{
					$this->error('添加分类失败！');
				}
			}else {
				$this->display();
			}
		}
		
		public function edit_category(){
			if(IS_POST){
				$data['name']=$_POST['name'];
				$data['sort']=$_POST['sort'];
				$data['status']=$_POST['status'];
				if(D('System_news_category')->where(array('id'=>$_POST['id']))->save($data) !== false){
					D("System_log")->add_log("编辑了平台快报分类",'系统配置项-平台快报',$res);
					
					D('Config')->clearDataCacheDir('plat_news');
					$this->success('更新成功！');
				}else{
					$this->error('更新失败！');
				}
			}else {
				$category = D('System_news_category')->where(array('id'=>$_GET['id']))->find();
				$this->assign("category",$category);
				$this->display();
			}
		}

		public function del(){
			if(IS_POST){

				if(!empty($_POST['id'])){
					if(D('System_news')->where(array('id'=>$_POST['id']))->delete()){
						D("System_log")->add_log("删除了平台快报",'系统配置项-平台快报',$_POST['id']);
						
						D('Config')->clearDataCacheDir('plat_news');
						$this->success('删除成功');
					}else{
						$this->error('删除失败！');
					}
				}
				if(!empty($_POST['category_id'])){
					if(!D('System_news_category')->where(array('id'=>$_POST['category_id']))->delete()){
						
						D('Config')->clearDataCacheDir('plat_news');
						
						$this->error('删除失败！');
					}else{
						D('System_news')->where(array('category_id'=>$_POST['category_id']))->delete();
						D("System_log")->add_log("删除了平台快报分类",'系统配置项-平台快报',$_POST['category_id']);
						$this->success('删除成功');
					}
				}
			}
		}
	}
?>