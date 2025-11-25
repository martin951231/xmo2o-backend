<?php
/*
 * 街道社区新闻
 *
 */
class NewsSreetAction extends BaseAction{
	protected $area_info;
	protected $area_id;

	public function _initialize(){
		parent::_initialize();
        $this->area_info = session('house_street');
        $this->area_id = $this->area_info['area_id'];
        $this->assign('area_info', $this->area_info);

	}

	// 所有新闻列表
	public function index(){

		$condition['area_id'] = $this->area_id;
		$news_list = D('Area_street_news')->getlist($condition);

		$this->assign('news_list',$news_list['news_list']);
		$this->assign('pagebar',$news_list['pagebar']);
		$this->display();
	}

	public function news_edit(){
		$news_id = $_GET['news_id'];

		// 所有新闻的分类
		$condition['area_id'] = $this->area_id;
		$condition['cat_status'] = 1;
		$news_categorys = D('Area_street_news_category')->order('cat_sort DESC')->where($condition)->select();

		if(count($news_categorys) < 1){
			$this->error('您还未添加新闻分类，请先去添加新闻分类',U('NewsSreet/cate_edit',['iframe'=> $_GET['iframe']]));
		}
		$this->assign('news_categorys',$news_categorys);

		if($news_id){
			$condition_village['area_id'] = $this->area_id;
			$condition_village['news_id'] = $news_id;
			//$condition_village['status'] = 1;
			$news_info = D('Area_street_news')->where($condition_village)->find();
			if(empty($news_info)){
				$this->error('暂无此新闻');
			}
			$this->assign('news_info',$news_info);
		}
		$source=(isset($_GET['source']) && $_GET['source']) ? 1 : 0;
        $this->assign('is_edit',$source);
		$this->display();
	}


	public function news_del(){
        //新闻列表-删除 权限

		$news_id = $_GET['news_id'] + 0;
		if(!$news_id){
			$this->error('传递参数有误！~~~');
		}

		$database_house_village_news = D('Area_street_news');

		$news_where['news_id'] = $news_id;
		$insert_id = $database_house_village_news->where($news_where)->delete();
		if($insert_id){
			$this->success('删除成功！');
		}else{
			$this->error('删除失败！');
		}
	}

	public function news_edit_do(){
		$news_id = $_POST['news_id'];
		if(IS_POST){

			if(!trim($_POST['title'])){
				$this->error('标题必填！');
			}
			if(empty($_POST['cat_id'])){
				$this->error('分类必选！');
			}
			if(empty($_POST['description'])){
				$this->error('内容必填！');
			}
            $imgs = $this->extractImageUrls( $_POST['description'] );
            if( $_POST['show_mode'] == 2 && $_POST['title_img'] == '' ){
                $this->error('您选择的是单图模式，请上传图片！');
            }
            if( $_POST['show_mode'] == 3 && count( $imgs ) < 3 ){
                $this->error('您选择的是多图模式，请最少上传3张图片！');
            }
            $_POST['img_list'] = implode('|||', $imgs);
			$_POST['is_hot'] = intval($_POST['is_hot']);
			$_POST['status'] = intval($_POST['status']);
			$_POST['cat_id'] = intval($_POST['cat_id']);
			$_POST['content'] = $_POST['description'];
			$_POST['area_id'] = $this->area_id;
            $_POST['show_mode'] = intval($_POST['show_mode']);
			unset($_POST['description']);
			if($news_id){

				$condition_village['area_id'] = $this->area_id;
				$condition_village['news_id'] = $news_id;
				//$condition_village['status'] = 1;
				$news_info = D('Area_street_news')->where($condition_village)->find();
				if(empty($news_info)){
					$this->error('暂无此新闻');
				}
				$cate = D('Area_street_news_category')->where(array('status'=>1,'cat_id'=>$_POST['cat_id'],'area_id'=>$this->area_id))->find();
				if(empty($cate)){
					$this->error('暂无此分类');
				}

				$result = D('Area_street_news')->where($condition_village)->data($_POST)->save();
				if($result !== false){
					$this->success('修改成功！',U('NewsSreet/index',['iframe'=> $_GET['iframe']]));
				}else{
					$this->error('修改失败！请重试。');
				}
			}else{
				$_POST['add_time'] = $_SERVER['REQUEST_TIME'];

				$result = D('Area_street_news')->data($_POST)->add();
				if($result !== false){
					$this->success('添加成功！',U('NewsSreet/index',['iframe'=> $_GET['iframe']]));
				}else{
					$this->error('添加失败！请重试。');
				}
			}
		}
	}
    function extractImageUrls($htmlContent) {
        $htmlContent = htmlspecialchars_decode($htmlContent);
        // 正则表达式：匹配img标签中的src属性值
        // 支持 src="url"、src='url'、src=url 三种格式
        preg_match_all('/<img\b[^>]*\bsrc\s*=\s*(["\']?)(.*?)\1[^>]*>/is', $htmlContent, $matches);


        if (!empty($matches[2])) {
            $imgSrcs = $matches[2];
        }

        return $imgSrcs;
    }
	public function send_over(){
		$this->display();
	}
	// 微信群发
	public function send(){
		$news_id = $_GET['news_id'];
		$send_user_type = $_GET['send_user_type'];
        
		if($news_id){
			$condition_village['area_id'] = $this->area_id;
			$condition_village['news_id'] = $news_id;
			$condition_village['status'] = 1;
			$news_info = D('Area_street_news')->where($condition_village)->find();
			if(empty($news_info)){
				exit(json_encode(array('code'=>1,'msg'=>'暂无此新闻')));
				// $this->error('暂无此新闻');
				// $this->display();
			}

			// 小区下所有的业主
            $site_url = $this->config['site_url'];
            $base_url = D('House_village')->base_url;
            $href='';
            if ($this->area_info['area_type']==0) {
                $where_ower = array(
                    'h.status' => 1,
                    'h.street_id'=>$this->area_id,
                    'b.status'=> 1
                );
                $href = $site_url . $base_url . 'pages/village/street/villagecontent?news_id='.$news_id .'&street_id='.$this->area_id;
            } elseif ($this->area_info['area_type']==1) {
                $where_ower = array(
                    'h.status' => 1,
                    'h.community_id'=>$this->area_id,
                    'b.status'=> 1
                );
                $href = $site_url . $base_url . 'pages/village/communityNews/villagecontent?news_id='.$news_id .'&community_id='.$this->area_id;
            }
            if($send_user_type == 1){//仅业主
                $where_ower['b.type'] = ['neq',4];
            }
            if($send_user_type == 3){//仅工作人员
                $where_ower['b.type'] = 4;
            }
            $where_ower['_string'] = "u.openid !='' AND b.phone>0" ;
            $users = D('House_village')
                ->join("as h LEFT JOIN ".C('DB_PREFIX')."house_village_user_bind as b ON h.village_id=b.village_id LEFT JOIN  ".C('DB_PREFIX')."user as u ON u.uid=b.uid")
                ->where($where_ower)->field('b.uid,u.openid,u.nickname,b.type')->group('u.openid')->select();


            if(!$users){
				exit(json_encode(array('code'=>1,'msg'=>'暂无发送对象')));
			}

            //针对数据量大 造成接口响应超时 走计划任务执行
            D('Area_street_news')->where(array('news_id'=>$news_id))->save(array('is_notice'=>1));
            $data=[];
            foreach ($users as $v){
                $data[]=[
                    'param'=>serialize([
                        'type' =>'area_street_send_notice',
                        'userInfo'=>$v,
                        'param'=>[
                            'send_user_type'=>$send_user_type,
                            'href'=>$href,
                            'area_name'=>$this->house_session['area_name'],
                            'title'=>$news_info['title']
                        ]
                    ]),
                    'plan_time'     =>  -120,
                    'space_time'    =>  0,
                    'add_time'      =>  time(),
                    'file'          =>  'sub_d5_park',
                    'time_type'     =>  1,
                    'unique_id'     =>  'street_send_notice_'.$users['uid'].'_'.time().'_'.uniqid(),
                    'rand_number'   =>  mt_rand(1, max(C('config.sub_process_num'), 3)),
                ];
            }
            M('process_sub_plan')->addAll($data);
            exit(json_encode(array('code'=>0,'msg'=>'发送成功，等待队列执行')));

//            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_news&village_id='.$this->area_id.'&news_id='.$news_id;
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            foreach ($users as $userInfo){
                switch ($send_user_type) {
                    case '1': // 仅业主
                        if ($userInfo['type'] != 4) {
                            // 新增模板消息（消息发送状态提醒）
                            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $userInfo['openid'], 'first' => '您好，'.$this->house_session['area_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 'keyword1' => ''.C('config.house_name').'新闻', 'keyword2' =>'已发送','keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 'keyword4' => $userInfo['nickname'], 'remark' => '\n请点击查看详细信息！'));
                        }
                        break;
                    case '2': // 业主和工作人员
                        // 新增模板消息（消息发送状态提醒）
                        $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $userInfo['openid'], 'first' => '您好，'.$this->house_session['area_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 'keyword1' => ''.C('config.house_name').'新闻', 'keyword2' =>'已发送','keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 'keyword4' => $userInfo['nickname'], 'remark' => '\n请点击查看详细信息！'));
                        break;
                    case '3': // 仅工作人员
                        if ($userInfo['type'] == 4) {
                            // 新增模板消息（消息发送状态提醒）
                            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $userInfo['openid'], 'first' => '您好，'.$this->house_session['area_name'].'发布了一条新的消息【'.$news_info['title'].'】\n', 'keyword1' => ''.C('config.house_name').'新闻', 'keyword2' =>'已发送','keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 'keyword4' => $userInfo['nickname'], 'remark' => '\n请点击查看详细信息！'));
                        }
                        break;
                }
            }
            D('Area_street_news')->where(array('news_id'=>$news_id))->save(array('is_notice'=>1));
            exit(json_encode(array('code'=>0,'msg'=>'发送完毕')));
		}
	}

	// 新闻分类
	public function cate(){

		$condition['area_id'] = $this->area_id;
		$news_category = D('Area_street_news_category')->where($condition)->order('cat_sort DESC,cat_status DESC')->select();

		$this->assign('news_category',$news_category);
		$this->display();
	}

	// 新闻分类
	public function cate_edit(){
		$cate_id = intval($_GET['cat_id']);
		if($cate_id){

			$condition['area_id'] = $this->area_id;
			$condition['cat_id'] = $_GET['cat_id'];
			$news_category = D('Area_street_news_category')->where($condition)->find();
			if(!$news_category){
				$this->error('此分类不存在');
			}

			$this->assign('cate_id',$cate_id);
			$this->assign('cate_info',$news_category);

		}
		$this->display();
	}

	// 新闻分类
	public function cate_edit_do(){
		$cate_id = intval($_POST['c_id']);
		if($cate_id){
	        //新闻分类-编辑 权限

			$condition['area_id'] = $this->area_id;
			$condition['cat_id'] = $cate_id;
			$news_category = D('Area_street_news_category')->where($condition)->find();
			if(!$news_category){
				$this->error('此分类不存在');
			}			

			if(!trim($_POST['cat_name'])){
				$this->error('分类名称必填！');
			}

			$data['cat_name'] = $_POST['cat_name'];
			$data['cat_sort'] = $_POST['cat_sort'];
			$data['cat_status'] = intval($_POST['cat_status']);
			$result = D('Area_street_news_category')->where($condition)->save($data);

			if($result !== false){
				$this->success('修改成功！',U('NewsSreet/cate',['iframe'=> $_GET['iframe']]));
			}else{
				$this->error('修改失败！请重试。');
			}
		}else{
			// 添加

			if(!trim($_POST['cat_name'])){
				$this->error('分类名称必填！');
			}

			$data['cat_name'] = $_POST['cat_name'];
			$data['cat_sort'] = intval($_POST['cat_sort']);
			$data['cat_status'] = intval($_POST['cat_status']);
			$data['area_id'] = $this->area_id;

			$result = D('Area_street_news_category')->data($data)->add();

			if($result !== false){
				$this->success('添加成功！',U('NewsSreet/cate',['iframe'=> $_GET['iframe']]));
			}else{
				$this->error('添加失败！请重试。');
			}
		}

		$this->assign('news_category',$news_category);
	}

	//新闻评论列表
	public function reply(){
        //新闻评论列表-查看 权限

        $column['area_id'] = $this->area_id;
		//$news = D('Area_street_news_reply')->getlist($condition);

        $pagesize = 20;
        $condition_table  = array(C('DB_PREFIX').'area_street_news'=>'n',C('DB_PREFIX').'area_street_news_category'=>'c',C('DB_PREFIX').'area_street_news_reply'=>'r',C('DB_PREFIX').'user'=>'u');
        $condition_where = " n.area_id = r.area_id  AND n.area_id = c.area_id  AND n.news_id = r.news_id  AND r.uid = u.uid AND  n.cat_id = c.cat_id AND c.cat_status=1  AND n.area_id=".$column['area_id'];
        if($column['status']){
            $condition_where .= " AND n.status = ".intval($column['status']);
        }
        $condition_field = 'n.title,c.cat_name,r.*,u.nickname,u.avatar';

        $order = ' r.is_read ASC,r.pigcms_id DESC ';
        import('@.ORG.merchant_page');
        $count_news = D('')->table($condition_table)->where($condition_where)->count();
        $p = new Page($count_news,$pagesize,'page');
        $reply_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		foreach($reply_list as &$val) {
			$val['content'] = strip_tags(htmlspecialchars_decode($val['content']));
		}
        $news['count_reply'] = $count_news;
        $news['pagebar'] = $p->show();
        $news['reply_list'] = $reply_list;

        $is_check =  D('Area_street_config')->where($column)->getField('news_is_need_check');
		$is_check = $is_check ? $is_check : 1;
        $this->assign('is_check',$is_check);
		$this->assign('news',$news);
		$this->display();
	}

	public function read(){
        //已读 权限
		if(IS_AJAX && $_POST['cmsid']){
			$condition['area_id'] = $this->area_id;
			$condition['is_read'] = 0;
			$condition['pigcms_id'] = $_POST['cmsid'];

			$data['is_read'] = 1;
			$result = D('Area_street_news_reply')->where($condition)->data($data)->save();

			if($result){
				$this->ajaxReturn(array('status'=>1,'msg'=>'已读'));
			}

			$this->ajaxReturn(array('status'=>0,'msg'=>'失败'));
		}
	}
    //删除评论
    public function reply_del(){

        if(IS_AJAX && $_POST['cmsid']){
            $condition['area_id'] = $this->area_id;

            $condition['pigcms_id'] = $_POST['cmsid'];

            $result = D('Area_street_news_reply')->where($condition)->delete();

            if($result){
                $this->ajaxReturn(array('status'=>1,'msg'=>'已删除'));
            }

            $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败'));
        }
    }
    //评论显示隐藏
    public function change_status()
    {

        $status = $_POST['type'] == 'open' ? '1' : '2';
        if (D('Area_street_news_reply')->where(array('pigcms_id' => $_POST['id']))->save(array('status' => $status))) {
            exit('1');
        } else {
            exit;
        }
    }
    //是否需要审核
    public function is_check()
    {
        $status = $_POST['type'] == 'open' ? '1' : '2';
        $now_config = D('Area_street_config')->where(['area_id' => $this->area_id])->find();
		if($now_config){
			if (D('Area_street_config')->where(['area_id' => $this->area_id])->save(array('news_is_need_check' => $status))) {
				exit('1');
			} else {
				exit('操作失败');
			}
		}else{
			if (D('Area_street_config')->data(['area_id' => $this->area_id, 'news_is_need_check' => $status])->add()) {
				exit('1');
			} else {
				exit('操作失败');
			}
		}
    }



	/*图片上传*/
	public function ajax_upload_pic(){
		if($_FILES['imgFile']['error'] != 4){
			$image = D('Image')->handle($this->property_id, 'house', 1);
			if ($image['error']) {
				exit(json_encode($image));
			} else {
				$title = $image['title']['imgFile'];
//				$house_image_class = new house_image();
//				$url = $house_image_class->get_image_by_path($title);
				$url=file_domain().'/upload/house/'.$title;
				exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
			}
		} else {
			exit(json_encode(array('error' => 1,'message' =>'没有选择图片')));
		}
	}

}