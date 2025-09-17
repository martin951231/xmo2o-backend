<?php
class GiftAction extends BaseAction{
	
	public function config(){
		redirect(U('Config/index',array('galias'=>'gift','header'=>'Gift/header')));
	}
	
    public function category_list(){
        $database_gift_category = D('Gift_category');
        $cat_fid = $_GET['cat_fid'] + 0;
        
        $where['is_del'] = 0;
        if(!$cat_fid){
            $where['cat_fid'] = 0;
        }else{
            $where['cat_fid'] = $Map['cat_id'] = $cat_fid;
            
            $now_category = $database_gift_category->gift_category_detail($Map);
            if(empty($now_category['detail'])){
                    $this->error_tips('没有找到该分类信息！', 3, U('category_list'));
            }elseif(empty($now_category['cat_status'])){

            }
            $this->assign('now_category', $now_category['detail']);
        }
        
        $category_list = $database_gift_category->gift_category_page_list($where);
        $this->assign('category_list',$category_list['list']);
        $this->display();
    }
    
    public function category_add(){
        if(IS_POST){
            $database_gift_category = D('Gift_category');
            $database_image = D('Image');
            
            $image = $database_image->handle($this->system_session['id'], 'system', 0, array('size' => 10));
            if (!$image['error']) {
                    $_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
            }

            $result = $database_gift_category->gift_category_add($_POST);
            
            if(!$result){
                $this->frame_submit_tips(0, '数据处理有误！');
            }else{
                if($result['status']){
                    $this->frame_submit_tips(1,$result['msg']);
                }else{
                    $this->frame_submit_tips(0,$result['msg']);
                }
            }
        }else{
            $this->assign('bg_color','#F3F3F3');
            $this->display(); 
        }
    }
    
    public function category_edit(){
        $cat_id = $_GET['cat_id'] + 0;
        if(!$cat_id){
            $this->error('传递参数有误！');
        }
        $where['cat_id'] = $cat_id;
        $database_gift_category = D('Gift_category');
        
        if(IS_POST){
            $database_image = D('Image');
            $image = $database_image->handle($this->system_session['id'], 'system', 0, array('size' => 10));
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
            }
            $result = $database_gift_category->gift_category_edit($where,$_POST);
            if(!$result){
                $this->error('数据处理有误！');
            }else{
                if($result['status']){
                    $this->frame_submit_tips(1,$result['msg']);
                }else{
                    $this->frame_submit_tips(0,$result['msg']);
                }
            }
        }else{
            $now_category = $database_gift_category->gift_category_detail($where);
            if(!$now_category){
                $this->error('数据处理有误！');
            }

            $this->assign('now_category',$now_category['detail']);
            $this->display();
        }
        
    }
    
    public function category_del(){
        if(IS_POST){
            $cat_id = $this->_post('cat_id');
            if(!$cat_id){
                $this->error(0, '传递参数有误！');
            }

            $database_gift_category = D('Gift_category');
            $where['cat_id'] = $cat_id;
            $result = $database_gift_category->gift_category_del($where);
            if(!$result){
                $this->error(0, '数据处理有误！');
            }else{
                if($result['status']){
                    $this->success($result['msg']);
                }else{
                    $this->error($result['msg']);
                }
            }
        }else{
            $this->error('非法提交,请重新提交~');
	}
        
    }
    
    
    public function index(){
        if(!empty($_GET['keyword'])){
            switch($_GET['searchtype']){
                case 'gift_id':
                    $where['gift_id'] = $_GET['keyword'] + 0;
                    break;
                case 'gift_name':
                    $where['gift_name'] = array('like','%'.$_GET['keyword'].'%');
                    break;
            }
        }

        if(!empty($_GET['searchstatus'])){
            switch($_GET['searchstatus']){
                case '1':
                    $where['status'] = 1;
                    break;
                case '2':
                    $where['status'] = 0;
                    break;
            }
        }
        $province_idss=I('get.province_idss',0,'intval');
        $city_idss=I('get.city_idss',0,'intval');
        $area_id=I('get.area_id',0,'intval');
        $where2=array();
        if($province_idss>0){
            $where2['province_id']=$province_idss;
        }
        if($city_idss>0){
            $where2['city_id']=$city_idss;
        }
        if($area_id>0){
            $where2['area_id']=$area_id;
        }
        if(!empty($where2)){
            $mer_arr=M("Merchant")->where($where2)->getField('mer_id',true);
            if (!$mer_arr) {
                $mer_arr = [];
            }
            $mer_arr[] = 0;
            $where['mer_id']=array('in',$mer_arr);
        }
		$database_gift = D('Gift');
        $database_gift_category = D('Gift_category');
		$where['is_del'] = 0;
        //查找待审核的礼品列表
        $is_check=I('is_check',0,'intval');
        if($is_check==1){
            $where['status']=array('in',array(2,4));
        }else{
            if(isset($where['status']) && $where['status']==0){
                $where['status']=array('in',array(0,3));
            }elseif($where['status']!=1){
                $where['status']=array('not in',array(2,4));
            }
        }
        $this->assign('is_check',$is_check);

        $list = $database_gift->gift_page_list($where,true,'sort desc');
        $mer_arr=M('Merchant')->getField('mer_id,name',true);
        foreach ($list['list']['list'] as $k => $v) {
            if($v['mer_id']>0){
                $list['list']['list'][$k]['mer_name']=$mer_arr[$v['mer_id']];
            }
        }
        $cat_list = $database_gift_category->where($where)->getField('cat_id,cat_name');

        $this->assign('cat_list',$cat_list);
		$this->assign('list',$list['list']);
        $this->display();
    }

    //审核商家添加的积分礼品
    public function save_merchant_gift(){
        $gift_id=I('get.gift_id','','intval');
        $status=I('get.status','','intval');
        if(empty($gift_id) || ($status!=3 && $status!=4)){
            $this->error('网络出错，请重试！');
        }
        $info=M('gift')->where(array('gift_id'=>$gift_id))->find();
        if(empty($info)){
            $this->error('积分礼品不存在！');
        }
        if(empty($info['mer_id'])){
            $this->error('商家积分礼品不存在！');
        }
        if($info['status']!=2){
            $this->error('礼品已审核，请勿重复操作！');
        }
        $status=$status==3?1:$status;
        $data=array();
        $data['gift_id']=$gift_id;
        $data['status']=$status;
        $res=M('gift')->data($data)->save();
        if($res){
            $this->success('操作成功！');
        }else{
            $this->error('抱歉，操作失败，请重试！');
        }
    }

    public function order(){
        $type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
        $sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
        if ($type != 'price' && $type != 'order_time') $type = '';
        $order_sort = '';
        if ($type && $sort) {
            $order_sort .= $type . ' ' . $sort . ',';
            $order_sort .= 'order_id DESC';
        } else {
            $order_sort .= 'order_time DESC';
        }

        $condition_where = "`o`.`gift_id`=`g`.`gift_id` and `o`.`is_pick_in_store`<2";

        if ($status >=1 ) {
            $s = $status-1;
            $condition_where .= ' AND `o`.`status`='. $s;
        }

        if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'order_id') {
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($_GET['keyword'])."'";
            } elseif ($_GET['searchtype'] == 'order_name') {
                $condition_where .= " AND `o`.`order_name` like '%" . htmlspecialchars($_GET['keyword'])."%'";
            }
        }
        $condition_table = array(C('DB_PREFIX').'gift'=>'g', C('DB_PREFIX').'gift_order'=>'o');
        $left_join = 'LEFT JOIN '.C('DB_PREFIX').'user as u on `o`.`uid`=`u`.`uid` LEFT JOIN '.C('DB_PREFIX').'merchant as m on `o`.`mer_id`=`m`.`mer_id`';
        $order_count = D('')->join($left_join)->where($condition_where)->table($condition_table)->count();
        import('@.ORG.system_page');
        $p = new Page($order_count,20);

        $order_list = D('')->field('`o`.`phone` AS `gift_phone`,`o`.*,`g`.`gift_name`,`u`.`uid`,`u`.`nickname`,`u`.`phone`,`g`.`gift_id`,`g`.`mer_id` as g_mer_id,`m`.`name`')->join($left_join)->where($condition_where)->table($condition_table)->order($order_sort)->limit($p->firstRow.','.$p->listRows)->select();

        if(empty($order_list)){
            //$this->error_tips('当前'.$this->config['gift_alias_name'].'并未产生订单！');
        }
        $mer_arr=M('Merchant')->getField('mer_id,name');
        foreach($order_list as $k=>$v){
            $order_list[$k]['mer_name']=$mer_arr[$v['g_mer_id']];
        }

        $this->assign('order_list',$order_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $this->assign(array('type' => $type, 'sort' => $sort,'status'=>$status));
        $this->display();
    }

    //平台配送的订单
    public function plat_order(){
        $type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
        $sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
        if ($type != 'price' && $type != 'order_time') $type = '';
        $order_sort = '';
        if ($type && $sort) {
            $order_sort .= $type . ' ' . $sort . ',';
            $order_sort .= 'order_id DESC';
        } else {
            $order_sort .= 'order_time DESC';
        }

        $condition_where = "`o`.`gift_id`=`g`.`gift_id` and `o`.`is_pick_in_store`=2";

        if ($status == 1) {
            $condition_where .= 'AND `o`.`status`=0';
        }elseif($status == 2){
            $condition_where .= 'AND `o`.`status`>0 and `o`.`status`<5';
        }elseif($status == 5){
            $condition_where .= 'AND `o`.`status`=5';

        }

        if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'order_id') {
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($_GET['keyword'])."'";
            } elseif ($_GET['searchtype'] == 'order_name') {
                $condition_where .= " AND `o`.`order_name` like '%" . htmlspecialchars($_GET['keyword'])."%'";
            }
        }
        $condition_table = array(C('DB_PREFIX').'gift'=>'g', C('DB_PREFIX').'gift_order'=>'o');
        $left_join = 'LEFT JOIN '.C('DB_PREFIX').'user as u on `o`.`uid`=`u`.`uid` LEFT JOIN '.C('DB_PREFIX').'merchant as m on `o`.`mer_id`=`m`.`mer_id`';
        $order_count = D('')->join($left_join)->where($condition_where)->table($condition_table)->count();
        import('@.ORG.system_page');
        $p = new Page($order_count,20);

        $order_list = D('')->field('`o`.`phone` AS `gift_phone`,`o`.*,`g`.`gift_name`,`u`.`uid`,`u`.`nickname`,`u`.`phone`,`g`.`gift_id`,`g`.`mer_id` as g_mer_id,`m`.`name`')->join($left_join)->where($condition_where)->table($condition_table)->order($order_sort)->limit($p->firstRow.','.$p->listRows)->select();
        if(empty($order_list)){
            //$this->error_tips('当前'.$this->config['gift_alias_name'].'并未产生订单！');
        }
        $mer_arr=M('Merchant')->getField('mer_id,name');
        foreach($order_list as $k=>$v){
            $order_list[$k]['mer_name']=$mer_arr[$v['g_mer_id']];
        }

        $this->assign('order_list',$order_list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $this->assign(array('type' => $type, 'sort' => $sort,'status'=>$status));
        $this->display();
    }
    
    public function gift_add(){
        if(IS_POST){
            if(empty($_POST['coupon_id']) && empty($_POST['deliver_type'])){
                $this->error('请至少选择一种配送方式');
            }

            $database_gift = D('Gift');
            $result = $database_gift->gift_add($_POST);
            
            if(!$result){
                $this->error('数据处理有有误！');
            }else{
                if($result['status']){
                    $this->success($result['msg']);
                }else{
                    $this->error($result['msg']);
                }
            }
        }else{
            $f_category_list = $this->_get_giftCategory(0);
            $this->assign('f_category_list',$f_category_list);
            $this->display();
        }
    }
	
	public function gift_del(){
		
		if(IS_POST){
			$gift_id = $this->_post('gift_id');
			if(!$gift_id){
				$this->error('传递参数有误！');
			}

			$database_gift = D('Gift');
			$where['gift_id'] = $gift_id;
			$result = $database_gift->gift_del($where);

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
			$this->error('访问页面有误！');
		}
	}
	
	
	public function gift_edit(){
		$gift_id = $_GET['gift_id'] + 0;
		$database_gift = D('Gift');
		$where['gift_id'] = $gift_id;
		if(IS_POST){
			//dump($_POST);exit;
            if(empty($_POST['coupon_id']) && empty($_POST['deliver_type'])){
                $this->error('请至少选择一种配送方式');
            }

            $gift_status=$database_gift->where(array('gift_id'=>$gift_id))->getField('status');
            if($gift_status==2 || $gift_status==4){
                $this->error('抱歉，当前礼品不支持编辑！');
            }
			$result = $database_gift->gift_edit($where,$_POST);
			
			if(!$result){
			}else{
				if($result['status']){
					$this->success($result['msg']);
				}else{
					$this->error($result['msg']);
				}
			}
		}else{
			$detail = $database_gift->gift_detail($where);
            if(!$detail){
				$this->error('数据处理有误！');
			}else{
				
				$this->assign('detail',$detail['detail']);
				$f_category_list = $this->_get_giftCategory(0);
				$this->assign('f_category_list',$f_category_list);
				$s_category_list = $this->_get_giftCategory($detail['detail']['cat_fid']);
				$this->assign('s_category_list',$s_category_list);
				$this->display();
			}
		}
		
	}
    
    public function ajax_category(){
        if(IS_POST){
            $cat_fid = $_POST['cat_fid'] + 0;
            $cat_list = $this->_get_giftCategory($cat_fid);
            if($cat_list){
                exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
            }else{
                exit(json_encode(array('status'=>0,'cat_list'=>$cat_list)));
            }
        }else{
            $this->error('访问页面有误！~~~');
        }
    }
	
	public function ajax_upload_pic(){
		if ($_FILES['imgFile']['error'] != 4) {
			$img_mer_id = sprintf("%09d", $this->system_session['id']);
			$rand_num = mt_rand(10, 99) . '/' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

			$upload_dir = './upload/system/gift/' . $rand_num . '/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0777, true);
			}
			import('ORG.Net.UploadFile');
			$upload = new UploadFile();
			$upload->maxSize = $this->config['gift_pic_size'] * 1024 * 1024;
			$upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
			$upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif');
            $upload->thumb = true;
            $upload->imageClassPath = 'ORG.Util.Image';
            $upload->thumbPrefix = 'm_,s_';
            $upload->thumbMaxWidth = 90;
            $upload->thumbMaxHeight = 90;
            $upload->thumbRemoveOrigin = false;
			$upload->savePath = $upload_dir;
			$upload->saveRule = 'uniqid';
			if ($upload->upload()) {
				$uploadList = $upload->getUploadFileInfo();
				$gift_image_class = new gift_image();
				$title = $rand_num . ',' . $uploadList[0]['savename'];
				$url = $gift_image_class->get_image_by_path($title);
				exit(json_encode(array('error' => 0, 'url' => $url['image'], 'title' => $title)));
			} else {
				exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
			}
			} else {
			exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
		}
    }

    public function order_detail(){
        $this->assign('bg_color','#F3F3F3');

        $order_id =  $_GET['order_id'] + 0;
        $database_gift_order = D('Gift_order');
        $condition_gift_order['order_id'] = $order_id;
        $now_order = $database_gift_order->get_order_detail_by_id_and_merId($order_id , $order_id ,false);
        $express_list = D('Express')->get_express_list();
        $this->assign('express_list',$express_list);
        $this->assign('now_order',$now_order);
        $this->display();
    }

    public function gift_express(){
        if(IS_AJAX){
            $database_gift_order = D('Gift_order');
            $now_order = $database_gift_order->get_order_detail_by_id_and_merId(intval($_GET['order_id']));

            if(empty($now_order)){
                $this->error('此订单不存在！');
            }
            if(empty($now_order['paid'])){
                $this->error('此订单尚未支付！');
            }
            if($now_order['status']==5){
                $this->error('此订单已取消！');
            }
            $condition_gift_order['order_id'] = $now_order['order_id'];
            $data_gift_order['express_type'] = $_POST['express_type'];
            $data_gift_order['express_id'] = $_POST['express_id'];
            $data_gift_order['status'] = 1;

            $insert_id = $database_gift_order->where($condition_gift_order)->data($data_gift_order)->save();

            if($insert_id){
                D("System_order")->save_order_status('gift', $now_order['order_id'], 1);
                $database_user = D('User');
                $userInfo = $database_user->get_user($now_order['uid']);
                //发送微信模板消息start
                if($userInfo['openid']){
                    $href = $this->config['site_url'] . '/wap.php?g=Wap&c=My&a=gift_order&order_id='.$now_order['order_id'];
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                    $express_info = D('Express')->get_express($_POST['express_type']);

                    $express_info ='\n快递号：'.$_POST['express_id'].'的'.$express_info['name'].'已寄出，请及时查收。';

                    $model->sendTempMsg('TM00017', array('href' => $href,
                        'wecha_id' => $userInfo['openid'],
                        'first' => $express_info,
                        'OrderSn' => $now_order['order_id'],
                        'OrderStatus' =>"已发货",
                        'remark' =>'点击查看详情')
					);
                }

                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试。');
            }
        }else{
            $this->error_tips('访问页面有误！~~~');
        }
    }

    // 门店自取发货
    public function gift_pick(){
        if(IS_AJAX){
            $database_gift_order = D('Gift_order');
            $now_order = $database_gift_order->get_order_detail_by_id_and_merId(intval($_GET['order_id']));

            if(empty($now_order)){
                $this->error('此订单不存在！');
            }
            if(empty($now_order['paid'])){
                $this->error('此订单尚未支付！');
            }
            if($now_order['status']==5){
                $this->error('此订单已取消！');
            }
            $condition_gift_order['order_id'] = $now_order['order_id'];
            $data_gift_order['status'] = 1;

            $res1 = $database_gift_order->where($condition_gift_order)->data($data_gift_order)->save();

            $data_gift_order['dateline'] = time();
            $res2 = D('Gift_pick_order')->where($condition_gift_order)->data($data_gift_order)->save();
            if($res1&&$res2){
                D("System_order")->save_order_status('gift', $now_order['order_id'], 1);
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试。');
            }
        }else{
            $this->error_tips('访问页面有误！~~~');
        }
    }


    public function ajax_del_pic(){
        $group_image_class =  new gift_image();
        $group_image_class->del_image_by_path($_POST['path']);
    }

    // 自提点列表
    public function pick_list(){
        $keyword = trim($_GET['keyword']);
        if($keyword){
            $where['pick_addr'] = ['like', '%'.$keyword.'%'];
        }
        $list = D('Gift_pick_address')->get_pick_address($where);
        $this->assign('list',$list);
        $this->display();
    }

    // 自提点添加
    public function pick_add(){
        if (IS_POST) {
            if (empty($_POST['long_lat'])) {
                $this->error('店铺经纬度必填！');
            }
            if (empty($_POST['pick_addr'])||empty($_POST['phone'])) {
                $this->error("信息不全，请检查！");
            }
            $long_lat = explode(',',$_POST['long_lat']);
            $_POST['long'] = $long_lat[0];
            $_POST['lat'] = $long_lat[1];
            
            if(M('Gift_pick_address')->add($_POST)){ 
                $this->success("保存成功！");
            }else{
                $this->error("保存失败！");
            }
        }else{
            $this->display();
        }
    }

    // 自提点编辑
    public function pick_edit(){
        if(IS_POST){
            if(empty($_POST['long_lat'])){
                $this->error('店铺经纬度必填！');
            }
            if(empty($_POST['pick_addr'])||empty($_POST['phone'])){
                $this->error("信息不全，请检查！");
            }
            $long_lat = explode(',',$_POST['long_lat']);
            $_POST['long'] = $long_lat[0];
            $_POST['lat'] = $long_lat[1];
            if(M('Gift_pick_address')->where(array('pick_id'=>$_POST['pick_id']))->save($_POST)){
                $this->success("保存成功！");
            }else{
                $this->error("保存失败！");
            }

        }else{
            if(!empty($_GET['pick_id'])){
                $pick_addr = M('Gift_pick_address')->where(array('pick_id'=>$_GET['pick_id']))->find();
                $this->assign('pick_addr',$pick_addr);
                $this->display();
            }else{
                $this->error("访问失败！");
            }
        }
    }

    // 自提点删除
    public function pick_del()
    {
        if(M('Gift_pick_address')->where(array('pick_id'=>$_POST['pick_id']))->delete()){
            $this->success("删除成功！");
        }else{
            $this->error("删除失败！");
        }
        
    }

    // 自提点更换秘钥
    public function pick_pwd()
    {
        $pick_id = isset($_GET['pick_id']) ? intval($_GET['pick_id']) : '';
        $pick_address = M('Gift_pick_address')->where(array('pick_id' => $pick_id))->find();
        if ($pick_address) {
            if (IS_POST) {
                $now_time = time();
                $pick_address['pwd'] = $pwd = md5($pick_id . $now_time . rand(10000, 99999));
                D('Gift_pick_address')->where(array('pick_id' => $pick_id))->save(array('last_time' => $now_time, 'pwd' => $pwd));
                $this->success('秘钥更新成功！');
            }
            if (empty($pick_address['pwd'])) {
                $now_time = time();
                $pick_address['pwd'] = $pwd = md5($pick_id . $now_time . rand(10000, 99999));
                D('Gift_pick_address')->where(array('pick_id' => $pick_id))->save(array('last_time' => $now_time, 'pwd' => $pwd));
            }
        } else {
            $this->error('配送点不存在！');
            exit;
        }
        $this->assign('pick_address', $pick_address);
        $this->display();
    }
    private function _get_giftCategory($fid){
        $database_gift_category = D('Gift_category');
        $where['is_del'] = 0;
        //$where['cat_status'] = 1;
        $where['cat_fid'] = $fid;
        
        $category_list = $database_gift_category->where($where)->getField('cat_id,cat_name');
        return $category_list;
    }

    //平台配送设置
    public function plat_deliver(){
        $this->assign('bg_color','#F3F3F3');
        $database = D('Merchant_store_shop');
        
        $where['id'] = 2;
        $now_shop = D('Gift_plat_deliver')->field(true)->where($where)->find();
        if (empty($now_shop)) {
            $this->frame_error_tips('没有找到该店铺信息！');
        }
        if ($now_shop['delivery_range_polygon']) {
            $now_shop['delivery_range_polygon'] = substr($now_shop['delivery_range_polygon'], 9, strlen($now_shop['delivery_range_polygon']) - 11);
            $lngLatData = explode(',', $now_shop['delivery_range_polygon']);
            array_pop($lngLatData);
            $lngLats = array();
            foreach ($lngLatData as $lnglat) {
                $lng_lat = explode(' ', $lnglat);
                $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
            }
            $now_shop['delivery_range_polygon'] = json_encode(array($lngLats));
        }
        
        $is_have_two_time = 0;
        if ($this->config['delivery_time2']) {
            $delivery_times2 = explode('-', $this->config['delivery_time2']);
            $start_time2 = $delivery_times2[0];
            $stop_time2 = $delivery_times2[1];
            if ($start_time2 != $stop_time2) {
                $is_have_two_time = 1;
            }
        }
        
        $is_have_three_time = 0;
        if ($this->config['delivery_time2']) {
            $delivery_times3 = explode('-', $this->config['delivery_time3']);
            $start_time3 = $delivery_times3[0];
            $stop_time3 = $delivery_times3[1];
            if ($start_time2 != $stop_time2) {
                $is_have_three_time = 1;
            }
        }
        
        $this->assign('is_have_two_time', $is_have_two_time);
        $this->assign('is_have_three_time', $is_have_three_time);
        $this->assign('now_store', $now_shop);
        $this->assign('now_shop', $now_shop);
        $this->display();
    }

    public function plat_deliver_amend(){
        if (IS_POST) {
            $where = array('id' =>2);
            $long_lat = explode(',', $_POST['long_lat']);
            $_POST['long'] = $long_lat[0];
            $_POST['lat'] = $long_lat[1];
            unset($_POST['long_lat']);
            if ($shop = D('Gift_plat_deliver')->field(true)->where($where)->find()) {
                if ($_POST['delivery_range_type'] == 1) {
                    if ($_POST['delivery_range_polygon']) {
                        $latLngArray = explode('|', $_POST['delivery_range_polygon']);
                        if (count($latLngArray) < 3) {
                            $this->error('请绘制一个合理的服务范围！');
                        } else {
                            $latLngData = array();
                            foreach ($latLngArray as $row) {
                                $latLng = explode('-', $row);
                                if(count($latLng) == 3){
                                    if(empty($latLng[0])){
                                        $latLng[0] = '-' . $latLng[1];
                                        $latLng[1] = $latLng[2];
                                    }
                                }
    //                          $latLngData[] = array('lat' => $latLng[0], 'lng' => $latLng[1]);
                                $latLngData[] = $latLng[1] . ' ' . $latLng[0];//array('lat' => $latLng[0], 'lng' => $latLng[1]);
                            }
                            $latLngData[] = $latLngData[0];
                            $_POST['delivery_range_polygon'] = 'POLYGON((' . implode(',', $latLngData) . '))';
                        }
                    } else {
                        $this->error('请绘制您的服务范围！');
                    }
                    unset($_POST['delivery_radius']);
                } 
                if ($_POST['s_send_time']  < 0) {
                    $_POST['s_send_time'] = $this->config['deliver_send_time'];
                } 
                unset($_POST['id']);
  
                //$_POST['sort_time'] = $shop['work_time'] * $diffTime + $_POST['send_time'];
                if (D('Gift_plat_deliver')->where($where)->save($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！请重试~');
                }
            } else {
                $this->error('数据不正确');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

}

