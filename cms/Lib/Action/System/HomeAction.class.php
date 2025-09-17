<?php

/*
 * 首页回复管理
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/06 16:47
 *
 */

class HomeAction extends BaseAction {

    protected function _initialize() {
	parent::_initialize();
	$this->database_home_menu = D('Home_menu');
	$this->database_image = D('Image');
	$this->database_home_menu_category = D('Home_menu_category');
    }
	public function config(){
		redirect(U('Config/index',array('galias'=>'home','header'=>'Home/config')));
	}


	public function index() {
	$paltform = D("Platform")->where(array('key' => '首页'))->find();
	if (IS_POST) {
	    $data = array();
	    $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
	    $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
	    $data['key'] = '首页';
	    $data['url'] = $this->config['site_url'] . '/wap.php';
// 			$images = $this->upload();
// 			if (empty($images['error'])) {
// 				foreach ($images['msg'] as $image) {
// 					$data[$image['key']] = substr($image['savepath'] . $image['savename'], 1);
// 				}
// 			}

	    $image = D('Image')->handle($this->system_session['id'], 'platform');
	    if (!$image['error']) {
		$data = array_merge($data, $image['url']);
	    }
	    if ($paltform == false) {
		$id = D("Platform")->add($data);
		D('Image')->update_table_id($_POST['pic'], $id, 'platform');
	    } else {
		D("Platform")->where(array('id' => $paltform['id']))->save($data);
		D('Image')->update_table_id($_POST['pic'], $paltform['id'], 'platform');
	    }
	    $this->success("设置成功");
	} else {
	    if (isset($paltform['pic']) && $paltform['pic'])
		$paltform['pic'] = $this->config['site_url'] . $paltform['pic'];
	    $this->assign('info', $paltform);
	    $this->display();
	}
    }

    public function group() {
	$paltform = D("Platform")->where(array('key' => $this->config['group_alias_name']))->find();
	if (IS_POST) {
	    $data = array();
	    $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
	    $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
	    $data['key'] = $this->config['group_alias_name'];
	    $data['url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Group&a=index';
// 			$images = $this->upload();
// 			if (empty($images['error'])) {
// 				foreach ($images['msg'] as $image) {
// 					$data[$image['key']] = substr($image['savepath'] . $image['savename'], 1);
// 				}
// 			}

	    $image = D('Image')->handle($this->system_session['id'], 'platform');
	    if (!$image['error']) {
		$data = array_merge($data, $image['url']);
	    }
	    if ($paltform == false) {
		D("Platform")->add($data);
		D('Image')->update_table_id($_POST['pic'], $id, 'platform');
	    } else {
		D("Platform")->where(array('id' => $paltform['id']))->save($data);
		D('Image')->update_table_id($_POST['pic'], $paltform['id'], 'platform');
	    }
	    $this->success("设置成功");
	} else {
	    if (isset($paltform['pic']) && $paltform['pic'])
		$paltform['pic'] = $this->config['site_url'] . $paltform['pic'];
	    $this->assign('info', $paltform);
	    $this->display();
	}
    }

    public function meal() {
	$paltform = D("Platform")->where(array('key' => $this->config['meal_alias_name']))->find();
	if (IS_POST) {
	    $data = array();
	    $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
	    $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
	    $data['key'] = $this->config['meal_alias_name'];
	    $data['url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Meal_list&a=index';
// 			$images = $this->upload();
// 			if (empty($images['error'])) {
// 				foreach ($images['msg'] as $image) {
// 					$data[$image['key']] = substr($image['savepath'] . $image['savename'], 1);;
// 				}
// 			}

	    $image = D('Image')->handle($this->system_session['id'], 'platform');
	    if (!$image['error']) {
		$data = array_merge($data, $image['url']);
	    }
	    if ($paltform == false) {
		D("Platform")->add($data);
		D('Image')->update_table_id($_POST['pic'], $id, 'platform');
	    } else {
		D("Platform")->where(array('id' => $paltform['id']))->save($data);
		D('Image')->update_table_id($_POST['pic'], $paltform['id'], 'platform');
	    }
	    $this->success("设置成功");
	} else {
	    if (isset($paltform['pic']) && $paltform['pic'])
		$paltform['pic'] = $this->config['site_url'] . $paltform['pic'];
	    $this->assign('info', $paltform);
	    $this->display();
	}
    }

    public function first() {
		$first = D("First")->field(true)->where(array('reply_type' => 0))->find();
		if (IS_POST) {
			$data = array();
			$data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
			$data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
			$data['content'] = isset($_POST['content']) ? htmlspecialchars_decode($_POST['content']) : '';
			$data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
			$data['type'] = isset($_POST['type']) ? intval($_POST['type']) : 0;
			$data['fromid'] = isset($_POST['fromid']) ? intval($_POST['fromid']) : 0;
			$data['reply_type'] = 0;
			$data['image_text_id'] = isset($_POST['image_text_id']) ? intval($_POST['image_text_id']) : 0;

			$data['reply_rule'] = intval($_POST['reply_rule']);
			$data['coupon_title'] = isset($_POST['coupon_title']) ? $_POST['coupon_title'] : '';
			$data['coupon_content'] = isset($_POST['coupon_content']) ? $_POST['coupon_content'] : '';
			$data['coupon_img'] = isset($_POST['coupon_img']) ? $_POST['coupon_img'] : '';
			$data['coupon_ids'] = isset($_POST['coupon_ids']) ? implode(',',$_POST['coupon_ids']) : '';
			$data['coupon_title_again'] = isset($_POST['coupon_title_again']) ? $_POST['coupon_title_again'] : '';
			$data['coupon_content_again'] = isset($_POST['coupon_content_again']) ? $_POST['coupon_content_again'] : '';
			$data['coupon_img_again'] = isset($_POST['coupon_img_again']) ? $_POST['coupon_img_again'] : '';
			$data['coupon_ids_again'] = isset($_POST['coupon_ids_again']) ? implode(',',$_POST['coupon_ids_again']) : '';



			$image = D('Image')->handle($this->system_session['id'], 'first');
			if (!$image['error']) {
			$data = array_merge($data, $image['url']);
			}

	// 			$images = $this->upload();
	// 			if (empty($images['error'])) {
	// 				foreach ($images['msg'] as $image) {
	// 					$data[$image['key']] = substr($image['savepath'] . $image['savename'], 1);
	// 				}
	// 			}
			if ($first == false) {
				$id = D("First")->add($data);
				D('Image')->update_table_id($_POST['pic'], $id, 'first');
			} else {
				D("First")->where(array('id' => $first['id']))->save($data);
				D('Image')->update_table_id($_POST['pic'], $first['id'], 'first');
			}
			$this->success("设置成功");
		} else {
			if (isset($first['pic']) && $first['pic'])
			$first['pic'] = $this->config['site_url'] . $first['pic'];
			$image_text = D('Image_text')->field('pigcms_id, title ')->where(array('mer_id' => 0))->order('pigcms_id asc')->select();

			$list = D('Source_material')
                ->where(array('mer_id' => 0, 'send_type'=>0))
                ->order('pigcms_id DESC')
                ->select();
			$it_ids = array();
			$temp = array();
			foreach ($list as $l) {
				foreach (unserialize($l['it_ids']) as $id) {
					if (!in_array($id, $it_ids)) $it_ids[] = $id;
				}
			}
			$result = array();
			$image_text = D('Image_text')
                ->field('pigcms_id, title ,read_quantity')
                ->where(array('pigcms_id' => array('in', $it_ids)))
                ->order('pigcms_id asc')
                ->select();
			foreach ($image_text as $txt) {
				$result[$txt['pigcms_id']] = $txt;
			}
			foreach ($list as &$l) {
				$l['dateline'] = date('Y-m-d H:i:s', $l['dateline']);
				foreach (unserialize($l['it_ids']) as $id) {
					$l['list'][] = isset($result[$id]) ? $result[$id] : array();
				}
			}

			$condition = array('coupon_id'=>array('in', $first['coupon_ids']));
			$order_string = '`coupon_id` DESC';
			$coupon_list = D('System_coupon')->field(true)->where($condition)->order($order_string)->select();
			foreach ($coupon_list as $key => $value) {
				$v['platform']=unserialize($v['platform']);
				if($v['cate_name']!='all'&&!empty($v['cate_id'])){
					$tmp = unserialize($v['cate_id']);
					$v['cate_id'] = $tmp['cat_name'];
				}
			}

			$condition = array('coupon_id'=>array('in', $first['coupon_ids_again']));
			$order_string = '`coupon_id` DESC';
			$coupon_list_again = D('System_coupon')->field(true)->where($condition)->order($order_string)->select();
			foreach ($coupon_list_again as $key => $value) {
				$v['platform']=unserialize($v['platform']);
				if($v['cate_name']!='all'&&!empty($v['cate_id'])){
					$tmp = unserialize($v['cate_id']);
					$v['cate_id'] = $tmp['cat_name'];
				}
			}

			$return =  D('System_coupon')->cate_platform();
			$this->assign("coupon_list",$coupon_list);
			$this->assign("coupon_list_again",$coupon_list_again);
			$this->assign("category",$return['category']);
			$this->assign("platform",$return['platform']);

			$this->assign('image_text_list', $list);
			$this->assign('first', $first);
			$this->display();
		}
    }

    public function invalid() {
		$invalid = D("First")->field(true)->where(array('reply_type' => 1))->find();
		if (IS_POST) {
		    $data = array();
		    $data['title'] = isset($_POST['title']) ? htmlspecialchars_decode($_POST['title']) : '';
		    $data['info'] = isset($_POST['info']) ? htmlspecialchars_decode($_POST['info']) : '';
		    $data['content'] = isset($_POST['content']) ? htmlspecialchars_decode($_POST['content']) : '';
		    $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
		    $data['type'] = isset($_POST['type']) ? intval($_POST['type']) : 0;
		    $data['fromid'] = isset($_POST['fromid']) ? intval($_POST['fromid']) : 0;
		    $data['reply_type'] = 1;
			$data['image_text_id'] = isset($_POST['image_text_id']) ? intval($_POST['image_text_id']) : 0;
	// 			$images = $this->upload();
	// 			if (empty($images['error'])) {
	// 				foreach ($images['msg'] as $image) {
	// 					$data[$image['key']] = substr($image['savepath'] . $image['savename'], 1);
	// 				}
	// 			}
		    $key = '';
		    if ($data['fromid'] == 1) {
		    	$key = '首页';
		    } elseif ($data['fromid'] == 2) {
		    	$key = $this->config['group_alias_name'];
		    } elseif ($data['fromid'] == 3) {
		    	$key = $this->config['meal_alias_name'];
		    }
		    if ($key && !($platform = D("Platform")->field(true)->where(array('key' => $key))->find())) {
		    	$this->error('您还未配置【'. $key .'回复配置】');
		    }
		    $image = D('Image')->handle($this->system_session['id'], 'first');
		    if (!$image['error']) {
				$data = array_merge($data, $image['url']);
		    }
		    if ($invalid == false) {
				$id = D("First")->add($data);
				D('Image')->update_table_id($_POST['pic'], $id, 'first');
		    } else {
				D("First")->where(array('id' => $invalid['id']))->save($data);
				D('Image')->update_table_id($_POST['pic'], $invalid['id'], 'first');
		    }
		    $this->success("设置成功");
		} else {
		    if (isset($invalid['pic']) && $invalid['pic'])
			$invalid['pic'] = $this->config['site_url'] . $invalid['pic'];
		    $this->assign('first', $invalid);

			$list = D('Source_material')
                ->where(array('mer_id' => 0, 'send_type'=>0))
                ->order('pigcms_id DESC')
                ->select();
			$it_ids = array();
			$temp = array();
			foreach ($list as $l) {
				foreach (unserialize($l['it_ids']) as $id) {
					if (!in_array($id, $it_ids)) $it_ids[] = $id;
				}
			}
			$result = array();
			$image_text = D('Image_text')
                ->field('pigcms_id, title ,read_quantity')
                ->where(array('pigcms_id' => array('in', $it_ids)))
                ->order('pigcms_id asc')
                ->select();
			foreach ($image_text as $txt) {
				$result[$txt['pigcms_id']] = $txt;
			}
			foreach ($list as &$l) {
				$l['dateline'] = date('Y-m-d H:i:s', $l['dateline']);
				foreach (unserialize($l['it_ids']) as $id) {
					$l['list'][] = isset($result[$id]) ? $result[$id] : array();
				}
			}

			$this->assign('image_text_list', $list);
		    $this->display();
		}
    }

    public function other()
    {
		$list = D("Platform")->field(true)->select();
		$this->assign('list', $list);
		$this->display();
    }

    public function other_add()
    {
		if (IS_POST) {
		    $data = array();
		    $data['key'] = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
		    $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
		    $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
		    $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
		    $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
		    $data['api_url'] = isset($_POST['api_url']) ? htmlspecialchars_decode($_POST['api_url']) : '';
		    $data['type'] = 0;
		    if (empty($data['key'])) {
				$this->error('关键词不能为空');
		    }
// 			$platform = M('Platform')->where(array('key' => $data['key']))->find();
// 			if ($platform) {
// 				$this->error('关键词已存在，不能重复添加！');
// 			}
// 			$images = $this->upload();
// 			if (empty($images['error'])) {
// 				foreach ($images['msg'] as $image) {
// 					$data[$image['key']] = substr($image['savepath'] . $image['savename'], 1);
// 				}
// 			}
		    $image = D('Image')->handle($this->system_session['id'], 'platform');
		    if (!$image['error']) {
				$data = array_merge($data, $image['url']);
		    }
		    if ($id = D("Platform")->add($data)) {
				D('Image')->update_table_id($_POST['pic'], $id, 'platform');
				$this->success('添加成功');
		    } else {
				$this->error('添加失败');
		    }
		} else {
		    $this->display();
		}
    }

    public function other_edit()
    {
		if (IS_POST) {
		    $data = array();
		    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		    $data['key'] = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
		    $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
		    $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
		    $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
		    $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
		    $data['api_url'] = isset($_POST['api_url']) ? htmlspecialchars_decode($_POST['api_url']) : '';
		    $data['type'] = 0;
		    if (empty($data['key'])) {
				$this->error('关键词不能为空');
		    }
// 				$platform = M('Platform')->where("`id`<>'$id' AND `key`='{$data['key']}'")->find();
// 				if ($platform) {
// 					$this->error('关键词已存在，不能重复添加！');
// 				}
// 				$images = $this->upload();
// 				if (empty($images['error'])) {
// 					foreach ($images['msg'] as $image) {
// 						$data[$image['key']] = substr($image['savepath'] . $image['savename'], 1);
// 					}
// 				}

		    $image = D('Image')->handle($this->system_session['id'], 'platform');
		    if (!$image['error']) {
				$data = array_merge($data, $image['url']);
		    }

		    D('Platform')->where(array('id' => $id))->save($data);
		    D('Image')->update_table_id($_POST['pic'], $id, 'platform');
		    $this->success('更新成功',U('Home/other'));
		} else {
		    $data = M('Platform')->where(array('id' => $this->_get('id'), 'type' => 0))->find();
		    if ($data == false) {
				$this->error('您所操作的数据对象不存在！');
		    }
		    $this->assign('info', $data);
		    $this->display();
		}
    }

    public function text_add()
    {
		if (IS_POST) {
		    $data = array();
		    $data['key'] = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
		    $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
		    $data['type'] = 1;
		    $data['pic'] = $_POST['pic'];
		    $data['media_id'] = trim($_POST['media_id'],'/');
		    if (empty($data['key'])) {
				$this->error('关键词不能为空');
		    }
		    if ($id = D("Platform")->add($data)) {
				$this->success('添加成功');
		    } else {
				$this->error('添加失败');
		    }
		} else {
		    $this->display();
		}
    }

	public function text_edit()
	{
		if (IS_POST) {
			$data = array();
			$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$data['key'] = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
			$data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
			$data['type'] = 1;
		    $data['pic'] = $_POST['pic'];
		    $data['media_id'] = trim($_POST['media_id'],'/');
			if (empty($data['key'])) {
				$this->error('关键词不能为空');
			}

			D('Platform')->where(array('id' => $id))->save($data);
			$this->success('更新成功',U('Home/other'));
		} else {
			$data = M('Platform')->where(array('id' => $this->_get('id'), 'type' => 1))->find();
			if ($data == false) {
				$this->error('您所操作的数据对象不存在！');
			}
			$this->assign('info', $data);
			$this->display();
		}
	}

	public function other_del()
	{
		D('Platform')->where(array('id' => intval($_POST['id'])))->delete();
		$this->success('删除成功');
	}

	public function diytool()
	{
		$this->display();
	}

	public function foot_menu_category()
	{
		$database_home_menu_category = D('Home_menu_category');
		$category_list = $database_home_menu_category->home_menu_category_list(true);
		if(!$category_list){
			$this->error('数据处理有误！');
		}else{
			$this->assign('category_list',$category_list['list']);
		}
		$this->display();
	}

	public function foot_menu_add() {
		if (IS_POST) {
			if (($_FILES['pic_path']['error'] != 4) || ($_FILES['hover_pic_path']['error'] != 4)) {
				$image = $this->database_image->handle($this->system_session['id'], 'slider');
				if (!$image['error']) {
					if($image['url']['pic_path']){
					    if (is_array($image['url']['pic_path'])) {
					        $pic_path = reset($image['url']['pic_path']);
                        } else {
                            $pic_path = $image['url']['pic_path'];
                        }
						$_POST['pic_path'] =str_replace('/upload/slider/', '', $pic_path);
					}

					if($image['url']['hover_pic_path']){
                        if (is_array($image['url']['hover_pic_path'])) {
                            $hover_pic_path = reset($image['url']['hover_pic_path']);
                        } else {
                            $hover_pic_path = $image['url']['hover_pic_path'];
                        }
						$_POST['hover_pic_path'] = str_replace('/upload/slider/', '', $hover_pic_path);
					}
				} else {
					$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
				}
			}


			// app打开其他小程序需要获取原始id
			if($_POST['app_wxapp_id']){
	        	$wxapp = M('Wxapp_other')->field(true)->where(['appid'=>$_POST['app_wxapp_id']])->find();
	        	$_POST['app_wxapp_username'] = $wxapp['username'];
			}

			$database_home_menu = D('Home_menu');
			$_POST['url'] = htmlspecialchars_decode($_POST['url']);
		    $result = $this->database_home_menu->home_menu_add($_POST);
		    if (!$result) {
				$this->frame_submit_tips(0,'数据有误！');
		    } else {
				if ($result['status']) {
                    $id = $result['id'];
				    $this->database_image->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');

                    // 多语言
                    $lang_model = D('Lang');
                    $_POST['id'] = $id;
                    $lang_model->add_lang_data('Home_menu',$_POST);

				    $this->frame_submit_tips($result['status'], $result['msg']);
				} else {
				    $this->frame_submit_tips($result['status'], $result['msg']);
				}
		    }
		} else {
		    $cat_id = $this->_get('cat_id');
		    if(!$cat_id){
				$this->error('传递参数有误！');
		    }
		    $this->assign('bg_color','#F3F3F3');
		    $now_category = $this->frame_check_get_category($cat_id);
		    $this->assign('now_category',$now_category['info']);
			//小程序列表
			$wxapp_list = M('Wxapp_other')->field(true)->select();
			$this->assign('wxapp_list',$wxapp_list);
	        //ios列表
	        $app_list = M('App_other')->field(true)->select();
	        $this->assign('app_list', $app_list);
		    $this->display();
		}
    }

    public function foot_menu_edit()
    {
		$this->assign('bg_color', '#F3F3F3');
		$id = $this->_get('id');
		$where['id'] = $id;
		if (IS_POST) {
		    if (($_FILES['pic_path']['error'] != 4) || ($_FILES['hover_pic_path']['error'] != 4)) {
				$image = $this->database_image->handle($this->system_session['id'], 'slider');
				if (!$image['error']) {
                    if($image['url']['pic_path']){
                        if (is_array($image['url']['pic_path'])) {
                            $pic_path = reset($image['url']['pic_path']);
                        } else {
                            $pic_path = $image['url']['pic_path'];
                        }
                        $_POST['pic_path'] =str_replace('/upload/slider/', '', $pic_path);
                    }

                    if($image['url']['hover_pic_path']){
                        if (is_array($image['url']['hover_pic_path'])) {
                            $hover_pic_path = reset($image['url']['hover_pic_path']);
                        } else {
                            $hover_pic_path = $image['url']['hover_pic_path'];
                        }
                        $_POST['hover_pic_path'] = str_replace('/upload/slider/', '', $hover_pic_path);
                    }
				} else {
					$this->frame_submit_tips(0, $image['msg']);
				}
		    }

			// app打开其他小程序需要获取原始id
			if($_POST['app_wxapp_id']){
	        	$wxapp = M('Wxapp_other')->field(true)->where(['appid'=>$_POST['app_wxapp_id']])->find();
	        	$_POST['app_wxapp_username'] = $wxapp['username'];
			}

			$_POST['url'] = htmlspecialchars_decode($_POST['url']);
		    $result = $this->database_home_menu->home_menu_edit($where, $_POST);
		    if(!$result){
				$this->error('传递数据有误！');
		    }else{
				if($result['status']){
                    // 多语言
                    $lang_model = D('Lang');
                    $_POST['id'] = $id;
                    $lang_model->edit_lang_data('Home_menu',$_POST);

				    $this->frame_submit_tips(1,$result['msg']);
				}else{
				    $this->frame_error_tips($result['msg']);
				}
		    }

		} else {
		    $foot_menu_info = $this->database_home_menu->home_menu_info($where);
		    if (!$foot_menu_info) {
				$this->frame_error_tips('数据有误！');
		    } else {
				if (!$foot_menu_info['status']) {
				    $this->frame_error_tips('该信息不存在！');
				} else {
				    $this->assign('foot_menu_info', $foot_menu_info['info']);
				}
		    }
			//小程序列表
			$wxapp_list = M('Wxapp_other')->field(true)->select();
			$this->assign('wxapp_list',$wxapp_list);
	        //ios列表
	        $app_list = M('App_other')->field(true)->select();
	        $this->assign('app_list', $app_list);
		    $this->display();
		}
    }

    public function foot_menu_del(){
		$id = $this->_post('id');
		$where['id'] = $id;
		$result = $this->database_home_menu->home_menu_del($where);
		if($result){
		    if($result['status']){
			$this->success($result['msg']);
		    }else{
			$this->error($result['msg']);
		    }
		}else{
		    $this->error($result['msg']);
		}
	}

    public function category_add(){
		if (IS_POST) {
			$result = $this->database_home_menu_category->home_menu_category_add($_POST);
			if(!$result){
			    $this->error('传递参数有误！');
			}else{
			    if($result['status']){
				$this->success($result['msg']);
			    }else{
				$this->error($result['msg']);
			    }
			}
		} else {
			$this->display();
		}
    }

    public function category_del(){
	$cat_id = $this->_post('cat_id');
	if(!$cat_id){
	    $this->error('传递参数有误！');
	}

	$where['cat_id'] = $cat_id;
	$result = $this->database_home_menu_category->home_menu_category_del($where);
	if(!$result){
	    $this->error('数据处理有误！');
	}else{
	    if($result['status']){
		$this->success($result['msg']);
	    }else{
		$this->error($result['msg']);
	    }
	}
    }

    public function category_edit(){
	$this->assign('bg_color','#F3F3F3');
	$cat_id = $this->_get('cat_id');

	if(!$cat_id){
	    $this->error('传递参数有误！');
	}
	$where['cat_id'] = $cat_id;


	if(IS_POST){
	    $result = $this->database_home_menu_category->home_menu_category_edit($where,$_POST);
	    if(!$result){
		$this->error('数据处理有误！');
	    }else{
		if($result['status']){
		    $this->success($result['msg']);
		}else{
		    $this->error($result['msg']);
		}
	    }
	}else{
	    $category_info = $this->database_home_menu_category->home_menu_category_info($where);
	    if(!$category_info){
		$this->error('数组处理有误！');
	    }else{
		$this->assign('category_info',$category_info['info']);
	    }
	    $this->display();
	}
    }

    public function foot_menu_list(){
		$this->assign('bg_color', 'white');

		if($_GET['cat_key']){
			$where['cat_key'] = $_GET['cat_key'];
			$now_category = $this->database_home_menu_category->home_menu_category_info($where);
			$cat_id = $now_category['info']['cat_id'];
		}else{
			$cat_id = $this->_get('cat_id');
			if(!$cat_id){
				$this->error('传递参数有误！');
			}
			$now_category = $this->check_get_category($cat_id);
		}
		$this->assign('now_category',$now_category['info']);



		$where_list['cat_id'] = $cat_id;
		$home_menu_list = $this->database_home_menu->home_menu_list($where_list);
		$this->assign('home_menu_list',$home_menu_list['list']);
		$this->display();
    }

    private function check_get_category($cat_id){
		$now_category = $this->get_category($cat_id);
		if(empty($now_category)){
			$this->error_tips('分类不存在！');
		}else{
			return $now_category;
		}
    }


    private function get_category($cat_id){
	    $where['cat_id'] = $cat_id;
	    $now_category = $this->database_home_menu_category->home_menu_category_info($where);
	    return $now_category;
    }

    private function frame_check_get_category($cat_id){
		$now_category = $this->get_category($cat_id);
		if(empty($now_category)){
			$this->frame_error_tips('分类不存在！');
		}else{
			return $now_category;
		}
	}

	public function first_by_time(){
		$type  = array(
			0=>'自定义文本',
			1=>'',
			2=>'网站功能',
			3=>'本站推荐的'.$this->config['group_alias_name'],
			4=>'单/多图文消息',
			5=>'优惠券',
		);
		$web_arr = array(
			1=>'网站首页',
			2=>$this->config['group_alias_name'].'首页',
			3=>$this->config['meal_alias_name'].'首页',
		);

		$first = D("First")->field(true)->where(array('reply_type' =>2))->order('start_time')->select();
		$this->assign('first',$first);
		$this->assign('type',$type);
		$this->assign('web_arr',$web_arr);

		$list = D('Source_material')->where(array('mer_id' => 0))->order('pigcms_id DESC')->select();
		$it_ids = array();
		$temp = array();
		foreach ($list as $l) {
			foreach (unserialize($l['it_ids']) as $id) {
				if (!in_array($id, $it_ids)) $it_ids[] = $id;
			}
		}
		$result = array();
		$image_text = D('Image_text')->field('pigcms_id, title ,read_quantity')->where(array('pigcms_id' => array('in', $it_ids)))->order('pigcms_id asc')->select();
		foreach ($image_text as $txt) {
			$result[$txt['pigcms_id']] = $txt;
		}
		foreach ($list as &$l) {
			$l['dateline'] = date('Y-m-d H:i:s', $l['dateline']);
			foreach (unserialize($l['it_ids']) as $id) {
				$l['list'][] = isset($result[$id]) ? $result[$id] : array();
			}
		}

		$this->assign('image_text_list', $list);
		$this->display();
	}

	public function add_first_by_time(){
		// reply_time 2
		//$first = D("First")->field(true)->where(array('reply_type' =>2))->select();
		if (IS_POST) {
			$data = array();
			$data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
			$data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
			$data['content'] = isset($_POST['content']) ? htmlspecialchars_decode($_POST['content']) : '';
			$data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
			$data['type'] = isset($_POST['type']) ? intval($_POST['type']) : 0;
			$data['fromid'] = isset($_POST['fromid']) ? intval($_POST['fromid']) : 0;
			$data['reply_type'] = 2;

			$data['reply_rule'] = intval($_POST['reply_rule']);
			$data['coupon_title'] = isset($_POST['coupon_title']) ? $_POST['coupon_title'] : '';
			$data['coupon_content'] = isset($_POST['coupon_content']) ? $_POST['coupon_content'] : '';
			$data['coupon_img'] = isset($_POST['coupon_img']) ? $_POST['coupon_img'] : '';
			$data['coupon_ids'] = isset($_POST['coupon_ids']) ? implode(',',$_POST['coupon_ids']) : '';
			$data['coupon_title_again'] = isset($_POST['coupon_title_again']) ? $_POST['coupon_title_again'] : '';
			$data['coupon_content_again'] = isset($_POST['coupon_content_again']) ? $_POST['coupon_content_again'] : '';
			$data['coupon_img_again'] = isset($_POST['coupon_img_again']) ? $_POST['coupon_img_again'] : '';
			$data['coupon_ids_again'] = isset($_POST['coupon_ids_again']) ? implode(',',$_POST['coupon_ids_again']) : '';

			$data['image_text_id'] = isset($_POST['image_text_id']) ? intval($_POST['image_text_id']) : 0;
			if($_POST['start_time'] && $_POST['end_time']){
				if($_POST['start_time']>$_POST['end_time']){
					$this->error_tips('开始时间不能比结束时间大');
				}
				if($_POST['end_time']=='00:00:00'){
					$this->error_tips('时间段设置错误');
				}
				if($_POST['end_time']==$_POST['start_time']){
					$this->error_tips('时间段不能相等');
				}
				$where['reply_type'] =2;
				$where['_string'] = "(start_time ='{$_POST['start_time']}' AND end_time ='{$_POST['end_time']}' ) OR (start_time >'{$_POST['start_time']}' AND start_time <'{$_POST['end_time']}' ) OR (end_time >'{$_POST['start_time']}' AND end_time <'{$_POST['end_time']}' ) OR (start_time >'{$_POST['start_time']}' AND end_time <'{$_POST['end_time']}')";

				if(empty($_POST['id']) && M("First")->where($where)->find()){
					$this->error_tips('该时间段已经存在或者该时间段与其他时间段有冲突');
				}

				$data['start_time'] = $_POST['start_time'];
				$data['end_time'] = $_POST['end_time'];
			}

			$image = D('Image')->handle($this->system_session['id'], 'first');
			if (!$image['error']) {
				$data = array_merge($data, $image['url']);
			}

			// 			$images = $this->upload();
			// 			if (empty($images['error'])) {
			// 				foreach ($images['msg'] as $image) {
			// 					$data[$image['key']] = substr($image['savepath'] . $image['savename'], 1);
			// 				}
			// 			}
			if (empty($_POST['id']) ) {
				$data['add_time'] = $_SERVER['REQUEST_TIME'];
				$id = D("First")->add($data);
				D('Image')->update_table_id($_POST['pic'], $id, 'first');
			} else {
				$data['last_time'] = $_SERVER['REQUEST_TIME'];
				D("First")->where(array('id' => $_POST['id']))->save($data);
				D('Image')->update_table_id($_POST['pic'], $_POST['id'], 'first');
			}

			$this->success("设置成功",U('Home/first_by_time'));
		} else {
			$_GET['id'] && $first = D("First")->field(true)->where(array('id' =>$_GET['id']))->find();
			if (isset($first['pic']) && $first['pic'])
				$first['pic'] = $this->config['site_url'] . $first['pic'];
			$image_text = D('Image_text')->field('pigcms_id, title ')->where(array('mer_id' => 0))->order('pigcms_id asc')->select();

			$list = D('Source_material')->where(array('mer_id' => 0))->order('pigcms_id DESC')->select();
			$it_ids = array();
			$temp = array();
			foreach ($list as $l) {
				foreach (unserialize($l['it_ids']) as $id) {
					if (!in_array($id, $it_ids)) $it_ids[] = $id;
				}
			}
			$result = array();
			$image_text = D('Image_text')->field('pigcms_id, title ,read_quantity')->where(array('pigcms_id' => array('in', $it_ids)))->order('pigcms_id asc')->select();
			foreach ($image_text as $txt) {
				$result[$txt['pigcms_id']] = $txt;
			}
			foreach ($list as &$l) {
				$l['dateline'] = date('Y-m-d H:i:s', $l['dateline']);
				foreach (unserialize($l['it_ids']) as $id) {
					$l['list'][] = isset($result[$id]) ? $result[$id] : array();
				}
			}

			// 优惠券
			$condition = array('coupon_id'=>array('in', $first['coupon_ids']));
			$order_string = '`coupon_id` DESC';
			$coupon_list = D('System_coupon')->field(true)->where($condition)->order($order_string)->select();
			foreach ($coupon_list as $key => $value) {
				$v['platform']=unserialize($v['platform']);
				if($v['cate_name']!='all'&&!empty($v['cate_id'])){
					$tmp = unserialize($v['cate_id']);
					$v['cate_id'] = $tmp['cat_name'];
				}
			}

			$condition = array('coupon_id'=>array('in', $first['coupon_ids_again']));
			$order_string = '`coupon_id` DESC';
			$coupon_list_again = D('System_coupon')->field(true)->where($condition)->order($order_string)->select();
			foreach ($coupon_list_again as $key => $value) {
				$v['platform']=unserialize($v['platform']);
				if($v['cate_name']!='all'&&!empty($v['cate_id'])){
					$tmp = unserialize($v['cate_id']);
					$v['cate_id'] = $tmp['cat_name'];
				}
			}

			$return =  D('System_coupon')->cate_platform();
			$this->assign("coupon_list",$coupon_list);
			$this->assign("coupon_list_again",$coupon_list_again);
			$this->assign("category",$return['category']);
			$this->assign("platform",$return['platform']);

			$this->assign('image_text_list', $list);
			$this->assign('first', $first);
			$this->display();
		}
	}

	public function del_first(){
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if ($data = D('First')->where(array('id' => $id))->find()) {

			D('First')->where(array('id' => $id))->delete();
			$this->success('删除成功');
		} else {
			$this->error('删除失败');
		}
	}

	public function select_coupon(){

        $selected = trim(I("selected"),',');
        $selected_arr = explode(',', $selected);
        $this->assign("selected", $selected);
		if (!empty($_GET['keyword'])) {
			$condition['name'] = array('like',"%" . $_GET['keyword'] . "%");
		}
		if ($_GET['is_hide']) {
        	$condition['is_hide'] =  1;
		}
		$condition['end_time'] = array('gt',time());
        $condition['status'] =  1;
        $condition['is_partition'] =  0;


		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				die('区域管理员无法管理优惠券');
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
				$city_id = $this->system_session['area_id'];
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
				$province_id = $this->system_session['area_id'];
			}
		}

		$condition['_string'] = 'num>had_pull';
        $city_id = intval(I("city_id"));

		$city_id && $condition['_string'] .= ' AND (city_id = '.$city_id.' OR city_id=0)';
		$province_id && $condition['_string'] .= ' AND (province_id = '.$province_id.' OR province_id=0)';

		$order_string = '`coupon_id` DESC';
		$count = D('System_coupon')->where($condition)->count();

        import('@.ORG.diypage');
		$p = new Page($count, 10);
		$coupon_list = D('System_coupon')->field(true)->where($condition)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
		foreach ($coupon_list as &$v) {
			$v['platform']=unserialize($v['platform']);
			if($v['cate_name']!='all'&&!empty($v['cate_id'])){
				$tmp = unserialize($v['cate_id']);
				$v['cate_id'] = $tmp['cat_name'];
			}
			$v['select'] = 0;
            if(in_array($v['coupon_id'], $selected_arr)){
                $v['select'] = 1;
            }
		}

        $this->assign("keyword", $_GET['keyword']);
		$return =  D('System_coupon')->cate_platform();
		$this->assign("category",$return['category']);
		$this->assign("platform",$return['platform']);
		$this->assign('list', $coupon_list);
		$this->assign('pagebar', $p->show());
		$this->display();
	}

	public function ajaxGetCoupon(){
        $selected = trim(I("selected"),',');
        if($selected){
            $selected_arr = explode(',', $selected);
        }

        $condition = array('coupon_id'=>array('in', $selected));
		$order_string = '`coupon_id` DESC';
		$coupon_list = D('System_coupon')->field(true)->where($condition)->order($order_string)->select();

		$return =  D('System_coupon')->cate_platform();
        foreach ($coupon_list as &$v) {
			$v['platform']=unserialize($v['platform']);
			if($v['cate_name']!='all'&&!empty($v['cate_id'])){
				$tmp = unserialize($v['cate_id']);
				$v['cate_id'] = $tmp['cat_name'];
				$v['cate_name'] = $return['category'][$v['cate_name']];
			}else{
				$v['cate_name'] = '全部类别';
				$v['cate_id'] = '全部分类';
			}
			$v['start_time'] = date('Y-m-d H:i',$v['start_time'] );
			$v['end_time'] = date('Y-m-d H:i',$v['end_time'] );

			$platform = '';
			foreach ($v['platform'] as $key => $value) {
				$platform .= $return['platform'][$value].' ';
			}
			$v['platform_str']=$platform;
		}
        echo json_encode($coupon_list);
        exit;
    }
}
