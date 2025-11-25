<?php
/*商家版积分商城
* @author jy 
* @create_time 2018/11/27 10:00:00
*/
class GiftAction extends BaseAction{
	public function index(){	//礼品列表
		$keyword=I('get.keyword','','trim');
		$searchtype=I('get.searchtype','','trim');
		$where=array();
		$mer_id=$_SESSION['merchant']['mer_id'];
		$where['mer_id']=$mer_id;
		if(!empty($keyword) && !empty($searchtype)){
			switch ($searchtype) {
				case 'gift_id':
					$where['gift_id']=intval($keyword);
					break;
				case 'gift_name':
					$where['gift_name']=array('like','%'.$keyword.'%');
					break;
			}
		}
		$searchstatus=I('searchstatus',0,'intval');
		if($searchstatus>0){
			switch ($searchstatus) {
				case '1':
					$where['status']=1;
					break;
				case '2':
					$where['status']=0;
					break;
			}
		}
		$where['is_del']=0;
		$data=D('Gift')->gift_page_list($where,true,'sort desc');
		$cat_list=D('Gift_category')->where($where)->getfield('cat_id,cat_name',true);

		$info=$data['list'];
		foreach ($info['list'] as $k => $v) {
			$cat_name='';
			if($v['cat_fid']>0){
				$cat_name.=$cat_list[$v['cat_fid']];
			}
			if($v['cat_id']){
				$cat_name.='-'.$cat_list[$v['cat_id']];
			}
			$info['list'][$k]['cat_name']=$cat_name;
		}

		$this->assign('info',$info);
		$this->display();
	}

	//编辑礼品
	public function gift_save(){
		$gift_id=I('gift_id',0,'intval');
		$model=D('Gift');		
		if(IS_POST){
			$data=$_POST;
			$mer_id=$_SESSION['merchant']['mer_id'];
			$data['deliver_type']=1;
			$data['mer_id']=$mer_id;
			if($gift_id>0){
				$where=array();
				$where['gift_id']=$gift_id;
				$res=$model->gift_edit($where,$data);
			}else{
				$data['status']=2;
				$res=$model->gift_add($data);
			}
			if(!$res){
				$this->error(L_('数据处理有误！'));
			}else{
				if($res['status']){
					$this->success($res['msg'],U('Gift/index'));
				}else{
					$this->error($res['msg']);
				}
			}
		}else{
			$cate_list = $this->_get_giftCategory(0);
			$this->assign('cate_list',$cate_list);
			if($gift_id>0){
				$where=array();
				$where['gift_id']=$gift_id;
				$data=$model->gift_detail($where);
				if(!$data){
					$this->error(L_('数据处理有误！'));
				}else{
					$info=$data['detail'];
					$cate_list2 = $this->_get_giftCategory($info['cat_fid']);
					$this->assign('info',$info);
					$this->assign('cate_list2',$cate_list2);
				}
			}			
			$this->display();
		}
	}

	//更新积分礼品状态
	public function save_gift_status(){
		$gift_id=I('gift_id','','intval');
		$status=I('status','','intval');
		if(empty($gift_id)){
			$this->error(L_('网络出错，请重试！'));
		}
		$data=array();
		$data['gift_id']=$gift_id;
		$data['status']=$status;
		$res=M('gift')->data($data)->save();
		if($res){
			$this->success(L_('操作成功！'));
		}else{
			$this->error(L_('抱歉，操作失败，请重试！'));
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
			exit(json_encode(array('error' => 1, 'message' => L_('没有选择图片'))));
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
            $this->error(L_('访问页面有误！~~~'));
        }
    }

	//删除礼品
	public function gift_del(){
		if(IS_POST){
			$gift_id=I('get.gift_id',0,'intval');
			if(empty($gift_id)){
				$this->error(L_('缺少参数！'));
			}
			$where=array();
			$where['gift_id']=$gift_id;
			$res=D('Gift')->gift_del($where);
			if(!$res){
				$this->error(L_('数据处理有误！'));
			}else{
				if($res['status']){
					$this->success($res['msg']);
				}else{
					$this->error($res['msg']);
				}
			}
		}else{
			$this->error(L_('访问页面有误！'));
		}		
	}

	public function order(){	//订单列表
		$type=I('get.type','','trim');
		$sort=I('get.sort','','trim');
		$status=I('get.status',0,'intval');
		if($sort!='DESC' && $sort!='ASC'){
			$sort='';
		}
		if($type!='price' && $type!='order_time'){
			$type='';
		}
		$order_sort='';
		if($type && $sort){
			$order_sort.=$type.' '.$sort.' ';
			$order_sort.='order_id desc';
		}else{
			$order_sort.='order_time desc';
		}
		$where=array();
		if($status==1){
			$where['o.status']=0;
		}elseif($status==2){
			$where['o.status']=array('gt',0);
		}
		$keyword=I('keyword','','trim');
		$searchtype=I('searchtype','','trim');
		if(!empty($keyword) && !empty($searchtype)){
			switch ($searchtype) {
				case 'order_id':
					$where['o.order_id']=$keyword;
					break;
				case 'order_name':
					$where['o.order_name']=array('like','%'.$keyword.'%');
					break;
			}
		}
		$where['g.mer_id']=array('eq',$_SESSION['merchant']['mer_id']);
		$count=M('gift')->alias('g')->join('inner join '.C('DB_PREFIX').'gift_order o on g.gift_id=o.gift_id')->join('inner join '.C('DB_PREFIX').'user u on o.uid=u.uid')->where($where)->count(1);
		import('@.ORG.system_page');
        $page= new Page($count,20);
        $field_str='`o`.`phone` AS `gift_phone`,`o`.*,`g`.`gift_name`,`u`.`uid`,`u`.`nickname`,`u`.`phone`,`g`.`gift_id`';
        $order_list=M('gift')->alias('g')->join('inner join '.C('DB_PREFIX').'gift_order o on g.gift_id=o.gift_id')->join('inner join '.C('DB_PREFIX').'user u on o.uid=u.uid')->where($where)->field($field_str)->order($order_sort)->limit($page->firstRow,$page->listRows)->select();
        $this->assign('order_list',$order_list);
        $this->assign('pagebar',$page->show());

        $this->assign(array('type'=>$type,'sort'=>$sort,'status'=>$status));
		$this->display();
	}

	/*订单详情页*/
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

    //商家核销订单
    public function gift_receive(){
    	$database_gift_order = D('Gift_order');
    	$order_id=I('get.order_id','','intval');
    	if(empty($order_id)){
    		$this->error(L_('网络出错，请重试！'));
    	}
        $receive_code=I('receive_code','','trim');
        if(empty($receive_code)){
        	$this->error(L_('请填写核销码！'));
        }
        $now_order = $database_gift_order->get_order_detail_by_id_and_merId($order_id);

        if(empty($now_order)){
            $this->error(L_('此订单不存在！'));
        }
        if(empty($now_order['paid'])){
            $this->error(L_('此订单尚未支付！'));
        }
        if($now_order['receive_code']!=$receive_code){
        	$this->error(L_('抱歉，核销码错误，核销失败！'));
        }
        $data=array();
        $data['order_id']=$order_id;
        $data['status']=2;
        $res=$database_gift_order->data($data)->save();
        if($res){
        	$this->success(L_('核销成功！'));
        }else{
        	$this->error(L_('抱歉，核销失败，请重试！'));
        }
    }

	private function _get_giftCategory($fid){
        $database_gift_category = D('Gift_category');
        $where['is_del'] = 0;
        //$where['cat_status'] = 1;
        $where['cat_fid'] = $fid;
        
        $category_list = $database_gift_category->where($where)->getField('cat_id,cat_name');
        return $category_list;
    }

}
?>