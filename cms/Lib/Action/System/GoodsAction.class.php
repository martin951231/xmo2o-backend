<?php

class GoodsAction extends BaseAction
{
	public function index()
	{
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_goods_category = D('Goods_category');
		$category = $database_goods_category->field(true)->where(array('id' => $parentid))->find();
		$category_list = $database_goods_category->field(true)->where(array('fid' => $parentid))->order('`sort` DESC, `id` ASC')->select();
		$this->assign('category', $category);
		$this->assign('category_list', $category_list);
		$this->assign('parentid', $parentid);
		$this->display();
	}

	public function wholesale_category(){
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_goods_category = D('Goods_wholesale_category');
		$category = $database_goods_category->field(true)->where(array('id' => $parentid))->find();
		$category_list = $database_goods_category->field(true)->where(array('fid' => $parentid))->order('`sort` DESC, `id` ASC')->select();
		$this->assign('category', $category);
		$this->assign('category_list', $category_list);
		$this->assign('parentid', $parentid);
		$this->display();
	}

	public function wholesale_cat_add()
	{
		$this->assign('bg_color','#F3F3F3');
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$this->assign('parentid', $parentid);
		$this->display();
	}

	public function wholesale_cat_modify()
	{
		if(IS_POST){
			$database_goods_category = D('Goods_wholesale_category');
            if (empty($_POST['fid'])) {
                $image = D('Image')->handle($this->system_session['id'], 'goodscategory', 0, array('size' => 10), false);
                if (!$image['error']) {
                    $_POST = array_merge($_POST, str_replace('/upload/goodscategory/', '', $image['url']));
                } else {
                    $this->frame_error_tips( $image['message']);
                }
            }else{
                if($_FILES['icon']['error'] != 4){
                    $image = D('Image')->handle($this->system_session['id'], 'goodscategory', 0, array('size' => 10), false);
                    if (!$image['error']) {
                        $_POST = array_merge($_POST, str_replace('/upload/goodscategory/', '', $image['url']));
                    } else {
                        $this->frame_error_tips( $image['message']);
                    }
                }
            }
            $_POST['name'] = trim($_POST['name']);
            $_POST['dateline'] = time();
			if ($database_goods_category->field(true)->where(array('name' => $_POST['name']))->find()) {
                $this->frame_error_tips( '该分类已存在！');
			}
			if($database_goods_category->data($_POST)->add()){
                $this->frame_submit_tips(1, '添加成功！');
			}else{
                $this->frame_error_tips( '添加失败！请重试~');
			}
		}else{
            $this->frame_error_tips( '非法提交,请重新提交~');
		}
	}
	public function wholesale_cat_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_goods_category = D('Goods_wholesale_category');
		$condition_now_goods_category['id'] = intval($_GET['cat_id']);
		$now_category = $database_goods_category->field(true)->where($condition_now_goods_category)->find();
		if (empty($now_category)) {
			$this->error('没有找到该分类信息！');
		}
		$this->assign('parentid', $parentid);
		$this->assign('now_category', $now_category);
		$this->display();
	}
	
	public function wholesale_cat_amend()
	{
		if (IS_POST) {
			$database_goods_category = D('Goods_wholesale_category');
			$where = array('id' => intval($_POST['id']));
			$now_cat = $database_goods_category->field(true)->where(array('name' => $_POST['name']))->find();
			if ($now_cat && $now_cat['id'] != intval($_POST['id'])) {
                $this->frame_error_tips( '该分类已存在！');
			}
            if (empty($_POST['fid'])) {
                if($_FILES['image']['error'] != 4){
                    $image = D('Image')->handle($this->system_session['id'], 'goodscategory', 0, array('size' => 10), false);
                    if (!$image['error']) {
                        $_POST = array_merge($_POST, str_replace('/upload/goodscategory/', '', $image['url']));
                    } else {
                        $this->frame_error_tips( $image['message']);
                    }
                }
            }else{
                if($_FILES['icon']['error'] != 4){
                    $image = D('Image')->handle($this->system_session['id'], 'goodscategory', 0, array('size' => 10), false);;
                    if (!$image['error']) {
                        $_POST = array_merge($_POST, str_replace('/upload/goodscategory/', '', $image['url']));
                    } else {
                        $this->frame_error_tips( $image['message']);
                    }
                }
            }
            $_POST['name'] = trim($_POST['name']);
            $_POST['dateline'] = time();
            unset($_POST['id']);
			if ($database_goods_category->where($where)->save($_POST)) {
                $this->frame_submit_tips(1, '编辑成功！');
			} else {
                $this->frame_error_tips('编辑失败！请重试~');
			}
		} else {
            $this->frame_error_tips('非法提交,请重新提交~');
		}
	}
	
	public function wholesale_cat_del()
	{
		if (IS_POST) {
			$database_goods_category = D('Goods_wholesale_category');
			$condition_now_goods_category['id'] = intval($_POST['cat_id']);
			if ($obj = $database_goods_category->field(true)->where($condition_now_goods_category)->find()) {
				$t_list = $database_goods_category->field(true)->where(array('fid' => $obj['id']))->select();
				if ($t_list) {
					$this->error('该分类下有子分类，先清空子分类，再删除该分类');
				}
			}
			if ($database_goods_category->where($condition_now_goods_category)->delete()) {
				$this->success('删除成功！');
			} else {
				$this->error('删除失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}



	

	public function cat_add()
	{
		$this->assign('bg_color','#F3F3F3');
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$this->assign('parentid', $parentid);
		$this->display();
	}
	public function cat_modify()
	{
		if(IS_POST){
		    $database_goods_category = D('Goods_category');
		    if (empty($_POST['fid'])) {
		    	$image = D('Image')->handle($this->system_session['id'], 'goodscategory', 0, array('size' => 10), false);
    		    if (!$image['error']) {
    		        $_POST = array_merge($_POST, str_replace('/upload/goodscategory/', '', $image['url']));
    		    } else {
    		        $this->frame_submit_tips(0, $image['message']);
    		    }
		    }else{
				if($_FILES['icon']['error'] != 4){
    			    $image = D('Image')->handle($this->system_session['id'], 'goodscategory', 0, array('size' => 10), false);
    			    if (!$image['error']) {
    			        $_POST = array_merge($_POST, str_replace('/upload/goodscategory/', '', $image['url']));
    			    } else {
    			        $this->frame_submit_tips(0, $image['message']);
    			    }
    			}
			}
			$_POST['name'] = trim($_POST['name']);
			$_POST['dateline'] = time();
			if ($database_goods_category->field(true)->where(array('name' => $_POST['name']))->find()) {
			    $this->frame_submit_tips(0, '该分类已存在！');
			}
			if($res = $database_goods_category->data($_POST)->add()){
				D("System_log")->add_log("新增了平台商品分类:".$_POST['name'],'平台配置项',$res);
			    $this->frame_submit_tips(1, '添加成功！');
			}else{
			    $this->frame_submit_tips(0, '添加失败！请重试~');
			}
		}else{
		    $this->frame_submit_tips(0, '非法提交,请重新提交~');
		}
	}
	public function cat_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		
		$parentid = isset($_GET['parentid']) ? intval($_GET['parentid']) : 0;
		$database_goods_category = D('Goods_category');
		$condition_now_goods_category['id'] = intval($_GET['cat_id']);
		$now_category = $database_goods_category->field(true)->where($condition_now_goods_category)->find();
		if (empty($now_category)) {
			$this->error('没有找到该分类信息！');
		}
		$this->assign('parentid', $parentid);
		$this->assign('now_category', $now_category);
		$this->display();
	}
	
	public function cat_amend()
	{
		if (IS_POST) {
			$database_goods_category = D('Goods_category');
			$where = array('id' => intval($_POST['id']));
			$now_cat = $database_goods_category->field(true)->where(array('name' => $_POST['name']))->find();
			if ($now_cat && $now_cat['id'] != intval($_POST['id'])) {
				$this->error('该分类已存在！');
			}
			if (empty($_POST['fid'])) {
    			if($_FILES['image']['error'] != 4){
    			    $image = D('Image')->handle($this->system_session['id'], 'goodscategory', 0, array('size' => 10), false);
    			    if (!$image['error']) {
    			        $_POST = array_merge($_POST, str_replace('/upload/goodscategory/', '', $image['url']));
    			    } else {
    			        $this->frame_submit_tips(0, $image['message']);
    			    }
    			}
			}else{
				if($_FILES['icon']['error'] != 4){
    			    $image = D('Image')->handle($this->system_session['id'], 'goodscategory', 0, array('size' => 10), false);;
    			    if (!$image['error']) {
    			        $_POST = array_merge($_POST, str_replace('/upload/goodscategory/', '', $image['url']));
    			    } else {
    			        $this->frame_submit_tips(0, $image['message']);
    			    }
    			}
			}
			$_POST['name'] = trim($_POST['name']);
			$_POST['dateline'] = time();
			unset($_POST['id']);
			if ($database_goods_category->where($where)->save($_POST)) {
				D("System_log")->add_log("编辑了平台商品分类:".$_POST['name'],'平台配置项',$where['id']);
			    $this->frame_submit_tips(1, '编辑成功！');
			} else {
			    $this->frame_submit_tips(0, '编辑失败！请重试~');
			}
		} else {
		    $this->frame_submit_tips(0, '非法提交,请重新提交~');
		}
	}
	
	public function cat_del()
	{
		if (IS_POST) {
			$database_goods_category = D('Goods_category');
			$condition_now_goods_category['id'] = intval($_POST['cat_id']);
			if ($obj = $database_goods_category->field(true)->where($condition_now_goods_category)->find()) {
				$t_list = $database_goods_category->field(true)->where(array('fid' => $obj['id']))->select();
				if ($t_list) {
					$this->error('该分类下有子分类，先清空子分类，再删除该分类');
				}else{
					$count=M('shop_goods')->where(array('cat_id'=>$obj['id']))->count(1);
					if($count>0){
						$this->error('该子分类下有商品，请先删除商品，在删除该子分类！');
					}
				}
			}
			if ($database_goods_category->where($condition_now_goods_category)->delete()) {
				D("System_log")->add_log("删除了平台商品分类,分类id:".$condition_now_goods_category['id'],'平台配置项',$_POST['cat_id']);
				$this->success('删除成功！');
			} else {
				$this->error('删除失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}
	
	public function property()
	{
		$cat_id = intval($_GET['cat_id']);
		$now_category = M('Goods_category')->field(true)->where(array('id' => $cat_id))->find();
		if (empty($now_category)) {
			$this->error('没有找到该分类信息！');
		}
		$properties_list = M('Goods_properties')->field(true)->where(array('cat_id' => $cat_id))->order('`id` ASC')->select();
		$this->assign('category', $now_category);
		$this->assign('properties_list', $properties_list);
		$this->display();
	}
    
	public function property_add()
	{
		$this->assign('cat_id', intval($_GET['cat_id']));
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}
	
	public function property_modify()
	{
		if (IS_POST) {
			$database_goods_properties = D('Goods_properties');
			$cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
			$name = isset($_POST['name']) ? trim(htmlspecialchars($_POST['name'])) : '';
			$status = isset($_POST['status']) ? intval($_POST['status']) : 1;
			if ($database_goods_properties->where(array('cat_id' => $cat_id, 'name' => $name))->find()) {
				$this->error('属性名称已存在！');
			}
			$count = $database_goods_properties->where(array('cat_id' => $cat_id, 'status' => 1))->count();
			if ($count >= 4) {
				$this->error('一个分类下最多四种属性~');
			}
			$_POST['dateline'] = time();
			if ($database_goods_properties->data($_POST)->add()) {
				$this->success('添加成功！');
			} else {
				$this->error('添加失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}
	
	public function property_edit()
	{
		$this->assign('bg_color','#F3F3F3');
		
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$database_goods_properties = D('Goods_properties');
		$condition_now_goods_properties['id'] = $id;
		$now_property = $database_goods_properties->field(true)->where($condition_now_goods_properties)->find();
		if (empty($now_property)) {
			$this->error('没有找到该属性信息！');
		}
		$this->assign('now_property', $now_property);
		$this->display();
	}
	
	public function property_amend()
	{
		if (IS_POST) {
			$database_goods_properties = D('Goods_properties');
			
			$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
			$name = isset($_POST['name']) ? trim(htmlspecialchars($_POST['name'])) : '';
			$status = isset($_POST['status']) ? intval($_POST['status']) : 1;
			$now_pro = $database_goods_properties->where(array('cat_id' => $cat_id, 'name' => $name))->find();
			if ($now_pro && $now_pro['id'] != $id) {
				$this->error('属性名称已存在！');
			}
			$now_pro = $database_goods_properties->where(array('id' => $id))->find();
			if ($status == 1 && $now_pro['status'] == 0) {
				$count = $database_goods_properties->where(array('cat_id' => $cat_id, 'status' => 1))->count();
				if ($count >= 4) {
					$this->error('一个分类下最多四种属性~');
				}
			}
			$where = array('id' => $id);
			$_POST['dateline'] = time();
			unset($_POST['id']);
			if ($database_goods_properties->where($where)->save($_POST)) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}
	
	
	// 预约自定义表单所有字段展示
	public function properties_value()
	{
		$pid = intval($_GET['pid']);
		$now_properties = M('Goods_properties')->field(true)->where(array('id' => $pid))->find();
		if (empty($now_properties)) {
			$this->error('属性信息有误！');
		}
		$value_list = M('Goods_properties_value')->field(true)->where(array('pid' => $pid))->select();
		$this->assign('now_properties', $now_properties);
		$this->assign('value_list', $value_list);
		$this->display();
	}
	
	public function value_add()
	{
		$pid = intval($_GET['pid']);
		$now_properties = M('Goods_properties')->field(true)->where(array('id' => $pid))->find();
		if (empty($now_properties)) {
			$this->error('属性信息有误！');
		}
		$this->assign('bg_color','#F3F3F3');
		$this->assign('pid', $pid);
		$this->assign('now_properties', $now_properties);
		$this->display();
	}
	
	public function value_edit()
	{
		$id = intval($_GET['id']);
		$now_value = M('Goods_properties_value')->field(true)->where(array('id' => $id))->find();
		if (empty($now_value)) {
			$this->error('属性值有误！');
		}
		
		$now_properties = M('Goods_properties')->field(true)->where(array('id' => $now_value['pid']))->find();
		if (empty($now_properties)) {
			$this->error('属性信息有误！');
		}
		
		$this->assign('bg_color','#F3F3F3');
		$this->assign('now_properties', $now_properties);
		$this->assign('now_value', $now_value);
		$this->display();
	}
	
	// 预约自定义表单添加字段 操作
	public function value_modify()
	{
		if (IS_POST) {
			$pid = intval($_POST['pid']);
			$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$now_properties = M('Goods_properties')->field(true)->where(array('id' => $pid))->find();
			if (empty($now_properties)) $this->error('属性信息有误！');
			
			$name = trim(htmlspecialchars($_POST['name']));
			if (empty($name)) $this->error('属性值不能为空！');
			$value = M('Goods_properties_value')->field(true)->where(array('pid' => $pid, 'name' => $name))->find();
			if ($value && $value['id'] != $id) {
				$this->error('该属性值已存在！');
			}
			if ($id) {
				if (M('Goods_properties_value')->where(array('id' => $id))->save(array('pid' => $pid, 'name' => $name))) {
					$this->success('修改成功！');
				} else {
					$this->error('修改失败！请重试~');
				}
			} else {
				if (M('Goods_properties_value')->add(array('pid' => $pid, 'name' => $name))) {
					$this->success('添加成功！');
				} else {
					$this->error('添加失败！请重试~');
				}
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}
	
	public function value_del()
	{
		if (IS_POST) {
			if (M('Goods_properties_value')->where(array('id' => intval($_POST['id'])))->delete()) {
				$this->success('删除成功！');
			} else {
				$this->error('删除失败！请重试~');
			}
		} else {
			$this->error('非法提交,请重新提交~');
		}
	}
    
    public function banner()
    {
        $cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        $category = D('Goods_category')->field(true)->where(array('id' => $cat_id))->find();
        if (empty($category)) {
            $this->error('分类信息不存在');
        }
        if (!empty($category['fid'])) {
            $this->error('子分类不能添加banner图');
        }
        $type=I('type',1,'intval');
        $where=array();
        $where['cat_id']=$cat_id;
        $where['type']=$type;
        $category_banners = D('Goods_category_banner')->field(true)->where($where)->select();
        $this->assign('banners', $category_banners);
        $this->assign('category', $category);
        $this->assign('type', $type);
        $this->display();
    }

    protected function frame_check_get_category($cat_id)
    {
        $now_category= D('Goods_category')->field(true)->where(array('id' => $cat_id))->find();
        if (empty($now_category)) {
            $this->frame_error_tips('分类不存在！');
        } else {
            return $now_category;
        }
    }
    public function banner_add()
    {
        $this->assign('bg_color', '#F3F3F3');
        $now_category = $this->frame_check_get_category($_GET['cat_id']);
        $this->assign('now_category', $now_category);
        $type=I('type',1,'intval');
        $this->assign('type', $type);

        $this->display();
    }

    public function banner_modify()
    {
        $image = D('Image')->handle($this->system_session['id'], 'goodsbanner', 0, array('size' => 10), false);
        if (!$image['error']) {
            $_POST = array_merge($_POST, str_replace('/upload/goodsbanner/', '', $image['url']));
        } else {
            $this->frame_submit_tips(0, $image['message']);
        }
        $_POST['dateline'] = $_SERVER['REQUEST_TIME'];
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        $database = D('Goods_category_banner');
        if ($id = $database->data($_POST)->add()) {
            D('Image')->update_table_id('/upload/goodsbanner/' . $_POST['image'], $id, 'goodsbanner');
            $this->frame_submit_tips(1, '添加成功！');
        } else {
            $this->frame_submit_tips(0, '添加失败！请重试~');
        }
    }

    public function banner_edit()
    {
        $this->assign('bg_color', '#F3F3F3');
        
        $database = D('Goods_category_banner');
        $condition['id'] = intval($_GET['id']);
        $now_banner = $database->field(true)->where($condition)->find();
        if (empty($now_banner)) {
            $this->frame_error_tips('该广告不存在！');
        }
        $this->assign('now_banner', $now_banner);
        
        $now_category = $this->frame_check_get_category($now_banner['cat_id']);
        $this->assign('now_category', $now_category);
        $this->display();
    }

    public function banner_amend()
    {
        $database = D('Goods_category_banner');
        $condition['id'] = intval($_POST['id']);
        $now_banner = $database->field(true)->where($condition)->find();
        
        if ($_FILES['image']['error'] != 4) {
            $image = D('Image')->handle($this->system_session['id'], 'goodsbanner', 0, array('size' => 10), false);
            if (! $image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/goodsbanner/', '', $image['url']));
            } else {
                $this->frame_submit_tips(0, $image['message']);
            }
        }
        $_POST['dateline'] = $_SERVER['REQUEST_TIME'];
        $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        if ($database->data($_POST)->save()) {
            D('Image')->update_table_id('/upload/goodsbanner/' . $_POST['image'], $_POST['id'], 'goodsbanner');
            if ($_POST['image']) {
                unlink('./upload/goodsbanner/' . $now_banner['image']);
            }
            $this->frame_submit_tips(1, '编辑成功！');
        } else {
            $this->frame_submit_tips(0, '编辑失败！请重试~');
        }
    }
    
    public function bannerDel()
    {
        if (IS_POST) {
            $database = D('Goods_category_banner');
            $condition['id'] = intval($_POST['id']);
            $now_banner = $database->field(true)->where($condition)->find();
            if ($database->where($where)->delete()) {
//                 unlink('./upload/goodsbanner/' . $now_banner['image']);
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }



    /**
     * 批发定制
     */
    //批发热门搜索词
    public function wholesale_search_hot(){
        $database_search_hot = D('Wholesale_search_hot');
        $page_size = 10;
        import('@.ORG.system_page');
        $count=$database_search_hot->count(1);
        $page=new page($count,$page_size);
        $search_hot_list = $database_search_hot->field(true)->order('`sort` DESC,`hottest` DESC,`id` ASC')->limit($page->firstRow,$page->listRows)->select();
        $this->assign('search_hot_list',$search_hot_list);
        $this->assign('pagebar',$page->show());
        $this->display();
    }

    //添加页面
    public function wholesale_search_hot_add(){
        $this->assign('bg_color','#F3F3F3');
        $this->display();
    }
    //添加处理
    public function wholesale_search_hot_modify(){
        if(IS_POST){
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            $database_search_hot = D('Wholesale_search_hot');
            if($database_search_hot->data($_POST)->add()){
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }
    //编辑页面
    public function wholesale_search_hot_edit(){
        $this->assign('bg_color','#F3F3F3');
        $database_search_hot = D('Wholesale_search_hot');
        $condition_search_hot['id'] = intval($_GET['id']);
        $search_hot = $database_search_hot->field(true)->where($condition_search_hot)->find();
        $this->assign('search_hot',$search_hot);
        $this->display();
    }
    //编辑处理
    public function wholesale_search_hot_amend(){
        if(IS_POST){
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            $database_search_hot = D('Wholesale_search_hot');
            if($database_search_hot->data($_POST)->save()){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }
    //删除热词
    public function wholesale_search_hot_del(){
        if(IS_POST){
            $database_search_hot = D('Wholesale_search_hot');
            $condition_search_hot['id'] = intval($_POST['id']);
            if($database_search_hot->where($condition_search_hot)->delete()){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    // 相关配置项
    public function config(){
        redirect(U('Config/index',array('galias'=>'wholesale_goods','header'=>'Goods/config')));
    }


}