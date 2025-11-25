<?php
/*平台E卡控制器*/
class EcardAction extends BaseAction{
	public function __construct(){
		parent::__construct();
	}

	//配置
	public function ecard_config(){
		$this->redirect('Config/index',array('galias'=>'ecard','header'=>'Ecard/ecard_header'));
	}

	//e卡列表
	public function ecard_list(){
		$where=array();
		$searchtype=I('searchtype','','trim');
		$keyword=I('keyword','','trim');
		if(!empty($searchtype) && !empty($keyword)){
			switch ($searchtype) {
				case 'cardid':
					$where['id']=$keyword;
					break;
				case 'card_name':
					$where['card_name']=$keyword;
					break;
				case 'card_price':
					$where['card_price']=$keyword;
					break;
				default:
					break;
			}
		}
		$where['is_del']=0;
		$count=M('ecard')->where($where)->count(1);
        import('@.ORG.system_page');
		$page=new Page($count,20);
		$list=M('ecard')->where($where)->limit($page->firstRow,$page->listRows)->select();
		foreach ($list as $k => $v) {
			//制卡数量
			$make_num=M('ecard_coupon')->where(array('card_id'=>$v['id'],'make_status'=>1))->count(1);
			$v['make_num']=$make_num;
			$v['card_bg']=replace_file_domain($v['card_bg']);
			if($v['bind_goods_id']>0){
				$goods_name=M('Shop_goods')->where(array('goods_id'=>$v['bind_goods_id']))->getField('name');
				if(empty($goods_name)){
					$v['bind_goods_id']=0;
				}else{
					$v['goods_name']=$goods_name;
				}
			}
			$card_num=M('ecard_coupon')->where(array('ecard_id'=>$v['id']))->count(1);
			if($card_num>0){
				$ecard_sku=M('ecard_coupon')->where(array('ecard_id'=>$v['id'],'is_del'=>0))->count(1);
				$v['ecard_sku']=$ecard_sku;
			}else{
				$v['ecard_sku']=$v['card_num'];
			}
			$list[$k]=$v;
		}
		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->display();
	}

	//修改e卡状态
	public function save_ecard_status(){
		$id=I('id','','intval');
		if(empty($id)){
			$this->error('缺少参数！');
		}
		$info=M('ecard')->where(array('id'=>$id))->find();
		if(empty($info)){
			$this->error('该平台e卡不存在！');
		}
		$status=I('status',0,'intval');
		$data=array();
		$data['id']=$id;
		$data['status']=$status;
		$res=M('Ecard')->data($data)->save();	
		if($res){
			if($info['bind_goods_id']>0){
				$goods_data=array();
				$goods_data['id']=$info['bind_goods_id'];
				$goods_data['status']=$status;
				M('Shop_goods')->data($goods_data)->save();
			}
			$this->success('操作成功！');
		}else{
			$this->error('抱歉，操作失败，请重试！');
		}
	}

	//创建e卡
	public function ecard_edit(){
		$model=M('Ecard');
		$id=I('id','','intval');
		if(IS_POST){
			$data=$_POST;			
			if(empty($id)){
				if($data['card_num']<=0){
					$this->frame_submit_tips(0,'至少制作一张'.$this->config['ecard_alias_name']);
				}
				if($data['card_price']<=0 || !is_numeric($data['card_price'])){
					$this->frame_submit_tips(0,'请填写大于0的面值');
				}
				$qr_arr=$model->getField('qrcode',true);
				if(is_array($qr_arr) && in_array($data['qrcode'], $qr_arr)){
					$this->frame_submit_tips(0,'抱歉，该前缀已使用！');
				}
			}
			if($_FILES['card_bg']['error']!=4){
				$img_arr=D('Image')->handle($this->system_session['id'],'ecard',0,array('size'=>10),false);
				if($img_arr['error']){
					$this->error($img_arr['message']);
				}else{
					$data['card_bg']=$img_arr['url']['card_bg'];
				}
			}
			if($id>0){
				$res=$model->data($data)->save();
				if($res!==false){
					$this->frame_submit_tips(1,'编辑成功！');
				}else{
					$this->frame_submit_tips(0,'抱歉，编辑失败，请重试！');
				}
			}else{
				$data['addtime']=time();
				$res=$model->data($data)->add();
				if($res){
					$this->frame_submit_tips(1,'添加成功！');
				}else{
					$this->frame_submit_tips(0,'抱歉，添加失败，请重试！');
				}	
			}			
		}else{
			if($id>0){
				$info=$model->where(array('id'=>$id))->find();
				$info['card_bg']=replace_file_domain($info['card_bg']);
				$this->assign('info',$info);
			}
			$this->display();
		}
	}

	//删除创建的e卡
	public function ecard_del(){
		$id=I('id','','intval');
		if(empty($id)){
			$this->error('缺少参数！');
		}
		$info=M('ecard')->where(array('id'=>$id))->find();
		if(empty($info)){
			$this->success('卡片不存在！');
		}
		if($info['bind_goods_id']>0){
			$bind_count=M('Shop_goods')->where(array('goods_id'=>$info['bind_goods_id']))->count(1);
			if($bind_count>0){
				$this->error('该'.$this->config['ecard_alias_name'].'已绑定商品，不能删除！');
			}
		}
		$data=array();
		$data['id']=$id;
		$data['is_del']=1;
		$res=M('Ecard')->data($data)->save();
		if($res){
			$this->success('删除成功！');
		}else{
			$this->error('抱歉，删除失败，请重试！');
		}
	}

	//e卡卡券列表
	public function ecard_coupon_list(){
		$model=M('Ecard_coupon');
		$card_id=I('card_id','','intval');
		if(empty($card_id)){
			$this->error('缺少参数！');
		}
		//创建e卡
		$this->ecard_add_coupon($card_id);

		//制卡状态
		$make_status=I('make_status',0,'intval');
		$where=array();
		$where['ecard_id']=$card_id;
		$where['make_status']=$make_status;
		//销售状态
		$sales_status=I('get.sales_status');
		if($sales_status!='' && $sales_status==0){
			$where['sales_type']=0;
		}elseif($sales_status==1){
			$where['sales_type']=array('gt',0);
		}
		$where['is_del']=0;
		$count=$model->where($where)->count(1);
		import('@.ORG.system_page');
		$page=new page($count,20);
		$list=$model->where($where)->limit($page->firstRow,$page->listRows)->select();

		$this->assign('list',$list);
		$this->assign('pagebar',$page->show());
		$this->assign('card_id',$card_id);
		$this->assign('make_status',$make_status);
		$this->display();
	}

	//删除未制卡的e卡
	public function ecard_coupon_del(){
		$id=I('get.id','','intval');
		if(empty($id)){
			$this->error('缺少参数！');
		}
		$data=array();
		$data['id']=$id;
		$data['is_del']=1;
		$res=M('ecard_coupon')->data($data)->save();
		if($res){
			$this->success('删除成功！');
		}else{
			$this->error('抱歉，删除失败，请重试！');
		}
	}

	//导出e卡卡券
	public function ecard_coupon_export(){
		$model=M('Ecard_coupon');
		//制卡状态
		$make_status=I('make_status',0,'intval');
		$card_id=I('card_id','','intval');
		if(empty($card_id)){
			$this->error('缺少参数！');
		}
		//平台e卡名称
		$card_name=M('Ecard')->where(array('id'=>$card_id))->getField('card_name');

		$ids=I('ids','','trim');
		$where=array();
		if(!empty($ids)){
			$where['id']=array('in',$ids);
		}
		$where['ecard_id']=$card_id;
		$where['make_status']=$make_status;
		$where['is_del']=0;
		$count=$model->where($where)->count(1);
		$list=$model->where($where)->order('id asc')->select();

		$title=C('config.ecard_alias_name');

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $len=ceil($count/10000);

        for ($i=0; $i <$len ; $i++) { 
        	if($i>0){
        		$objExcel->createSheet();
        	}
        	$objExcel->setActiveSheetIndex($i);

        	$objExcel->getActiveSheet()->setTitle('第'.($i+1).'个'.C('config.ecard_alias_name').'未制卡信息');
        	$objExcel->getActiveSheet()->mergeCellsByColumnAndRow('0', '1',4, '1');
            $objExcel->getActiveSheet()->setCellValueByColumnAndRow('0', '1', $card_name);

            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A2', '编号');
            $objActSheet->setCellValue('B2', '卡号');
            $objActSheet->setCellValue('C2', '卡密');
            $objActSheet->setCellValue('D2', '面值');

            $list2=array_slice($list, $i*10000,10000);
            asort($list2);
            fdump($list2,1,1);
            foreach($list2 as $k=>$v){
            	$k+=3;
            	$objActSheet->setCellValueExplicit('A' . $k, $v['id']);
            	$objActSheet->setCellValueExplicit('B' . $k, $v['card_code']);
            	$objActSheet->setCellValueExplicit('C' . $k, $v['card_password']);
            	$objActSheet->setCellValueExplicit('D' . $k, $v['now_price']);
            }
        }
        sleep(2);
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
	}

	//创建e卡卡券
	public function ecard_add_coupon($ecard_id=null){
		if(empty($ecard_id)){
			$this->error('缺少参数！');
		}
		$info=M('ecard')->where(array('id'=>$ecard_id))->find();
		if(empty($info)){
			$this->error('该e卡不存在！');
		}
		$add_num=M('ecard_coupon')->where(array('ecard_id'=>$ecard_id))->count(1);
		if($add_num>=$info['card_num']){
			return false;
		}
		set_time_limit(0);
		$i=$add_num+1;
		$model=M('Ecard_coupon');
		//获取当前的卡券密码列表，防止重复
		$card_pass_arr=$model->getField('card_password',true);
		$card_pass_arr=(array)$card_pass_arr;


		$data=array();
		$data['ecard_id']=$ecard_id;
		$data['now_price']=$info['card_price'];
		$data['addtime']=time();
		$add_arr=array();
		$flag=true;
		while ($i<=$info['card_num']) {
			$arr=$data;
			$arr['card_code']=$info['qrcode'].'1'.sprintf('%05d', $i);
			$card_password=D('Ecard_coupon')->cretate_ecard_password();
			$arr['card_password']=$card_password;
			$card_pass_arr[]=$card_password;
			$add_arr[]=$arr;
			if($i%10000==0){
				$res=$model->addAll($add_arr);
				if(!$res){
					$flag=false;
					break;
				}
				$add_arr=array();	
			}elseif($i==$info['card_num']){
				$res=$model->addAll($add_arr);
				if(!$res){
					$flag=false;
					break;
				}
				$add_arr=array();
			}
			$i++;
		}
		if(!$flag){
			$this->error('E卡创建失败！');
		}
	}

	

	//标记卡券为制卡完成
	public function save_ecard_coupon(){
		$ids=I('ids','','trim');
		$ecard_id=I('ecard_id','','intval');
		if(empty($ids) || empty($ecard_id)){
			$this->error('缺少参数！');
		}
		$res=M('Ecard_coupon')->where(array('id'=>array('in',$ids)))->save(array('make_status'=>1));
		if($res){
			//添加绑定商品的库存
			$bind_goods_id=M('ecard')->where(array('id'=>$ecard_id))->getField('bind_goods_id');
			if($bind_goods_id>0){
				$num=count($ids);
				$data=array();
				$data['goods_id']=$bind_goods_id;
				$data['stock_num']=array('exp','stock_num+'.$num);
				$data['original_stock']=array('exp','original_stock+'.$num);
				M('Shop_goods')->data($data)->save();
			}
			$this->success('操作成功！');
		}else{
			$this->error('抱歉，操作失败，请重试！');
		}
	}

	//标记为已售出
	public function make_ecard_coupon(){
		$id=I('get.id','','trim');
		if(empty($id)){
			$this->error('缺少参数！');
		}
		$ecard_info=M('ecard_coupon')->where(array('id'=>$id))->find();
		if(empty($ecard_info)){
			$this->error('该e卡不存在！');
		}
		if($ecard_info['buy_type']>0){
			$this->error('该e卡已售出！');
		}
		$sales_type=I('sales_type',2,'intval');
		$orderid=I('orderid','','trim');
		$sales_order_id='';
		if($sales_type==1){
			if(empty($orderid)){
				$this->error('请填写线上售出的订单编号！');
			}
			$info=M('shop_order')->where(array('real_orderid'=>$orderid))->find();
			if(empty($info)){
				$this->error('填写的订单编号的订单中不存在，请重新填写！');
			}
			$sales_order_id=$info['order_id'];
		}elseif($sales_type==2){	//标记为线下购买把绑定卡券的商品的库存减1
			$bind_goods_id=M('ecard')->where(array('id'=>$ecard_info['ecard_id']))->getField('bind_goods_id');
			if($bind_goods_id>0){
				$stock_num=M('Shop_goods')->where(array('goods_id'=>$bind_goods_id))->getField('stock_num');
				if($stock_num>0){
					M('Shop_goods')->where(array('goods_id'=>$bind_goods_id))->setDec('stock_num',1);	
				}
			}
		}
		$data=array();
		$data['id']=$id;
		$data['sales_type']=$sales_type;
		$data['sales_order_id']=$sales_order_id;
		$data['sales_time']=time();
		$res=M('ecard_coupon')->data($data)->save();
		if($res){
			M('ecard')->where(array('id'=>$ecard_info['ecard_id']))->setInc('sales_num',1);
			$this->success('操作成功！');
		}else{
			$this->error('抱歉，操作失败，请重试！');
		}
	}

	//更改卡券状态
	public function save_ecard_coupon_status(){
		$id=I('id','','intval');
		if(empty($id)){
			$this->error('缺少参数！');
		}
		$status=I('status',0,'intval');
		$data=array();
		$data['id']=$id;
		$data['status']=$status;
		$res=M('Ecard_coupon')->data($data)->save();
		if($res){
			$this->success('操作成功！');
		}else{
			$this->error('抱歉，操作失败，请重试！');
		}
	}

	//查看e卡售出明细
	public function ecrad_sales_list(){
		$where=array();
		$where['eu.make_status']=1;
		$sales_type=I('sales_type',0,'intval');
		if($sales_type>0){
			$where['eu.sales_type']=$sales_type;
		}else{
			$where['eu.sales_type']=array('gt',0);
		}
		$search=I('search','','trim');
		if(!empty($search)){
			$search_arr=array();
			$search_arr['ord.real_orderid']=array('like','%'.$search.'%');
			$search_arr['ord.username']=array('like','%'.$search.'%');
			$search_arr['eu.card_code']=array('like','%'.$search.'%');
			$search_arr['_logic']='or';
			$where['_complex']=$search_arr;
		}
		$card_id=I('card_id','','intval');
		if($card_id>0){
			$where['eu.ecard_id']=$card_id;
		}
		$card_price=I('card_price','','floatval');
		if($card_price>0){
			$where['ec.card_price']=$card_price;
		}
		$order_status=I('order_status','','trim');
		if($order_status!='' && $order_status>=0){
			if($order_status==2){
				$where['_string']='eu.sales_type=2 or ord.status=2';
			}else{
				$where['ord.status']=$order_status;
			}
		}

		$count=M('Ecard_coupon')->alias('eu')->join('inner join '.C('DB_PREFIX').'ecard ec on ec.id=eu.ecard_id')->join('left join '.C('DB_PREFIX').'shop_order ord on eu.sales_order_id=ord.order_id')->where($where)->count(1);
		import('@.ORG.system_page');
		$page=new Page($count,20);

		$list=M('Ecard_coupon')->alias('eu')->join('inner join '.C('DB_PREFIX').'ecard ec on ec.id=eu.ecard_id')->join('left join '.C('DB_PREFIX').'shop_order ord on eu.sales_order_id=ord.order_id')->where($where)->limit($page->firstRow,$page->listRows)->field('eu.*,ec.card_bg,ec.card_name,ec.card_price,ord.real_orderid,ord.username,ord.userphone,ord.status ord_status')->order('eu.id desc')->select();
		$this->assign('list',$list);
		$this->assign('card_id',$card_id);
		$this->assign('pagebar',$page->show());
		//e卡列表
		$ecard_list=M('ecard')->getField('id,card_name,card_price',true);
		$this->assign('ecard_list',$ecard_list);
		$ecard_list2=M('ecard')->group('card_price')->getField('id,card_name,card_price',true);
		$this->assign('ecard_list2',$ecard_list2);

		$from_type=I('from_type','all','trim');
		$this->assign('from_type',$from_type);

		//订单状态
		$order_status_arr=array();
		$order_status_arr[]=array('status'=>0,'msg'=>'未确认');
		$order_status_arr[]=array('status'=>1,'msg'=>'已发货');
		$order_status_arr[]=array('status'=>2,'msg'=>'已收货');
		$this->assign('order_status_arr',$order_status_arr);

		$this->display();
	}

	//查看e卡消费明细
	public function ecard_use_list(){
		$where=array();
		//$where['log.status']=1;
		$card_id=I('card_id','','intval');
		if($card_id>0){
			$where['log.ecard_id']=$card_id;
		}
		$card_price=I('card_price','','floatval');
		if($card_price>0){
			$where['ec.card_price']=$card_price;
		}
		$store_id=I('store_id','','intval');
		if($store_id>0){
			$where['log.store_id']=$store_id;
		}
		//查找订单号、昵称、卡号
		$search=I('search','','trim');
		if(!empty($search)){
			$search_arr=array();
			$search_arr['log.orderid']=array('like','%'.$search.'%');
			$search_arr['log.username']=array('like','%'.$search.'%');
			$search_arr['eu.card_code']=array('like','%'.$search.'%');
			$search_arr['_logic']='or';
			$where['_complex']=$search_arr;
		}

		$count=M('ecard_use_log')->alias('log')->join('left join '.C('DB_PREFIX').'ecard ec on log.ecard_id=ec.id')->join('left join '.C('DB_PREFIX').'ecard_coupon eu on log.coupon_id=eu.id')->where($where)->count(1);
		fdump(M()->_sql(),1);
		import('@.ORG.system_page');
		$page=new Page($count,20);
		$list=M('ecard_use_log')->alias('log')->join('left join '.C('DB_PREFIX').'ecard ec on log.ecard_id=ec.id')->join('left join '.C('DB_PREFIX').'ecard_coupon eu on log.coupon_id=eu.id')->where($where)->limit($page->firstRow,$page->listRows)->field('log.*,ec.card_name,ec.card_price,card_code')->order('log.id desc')->select();
		fdump(M()->_sql(),1,1);

		//店铺列表
		$store_list=M('Merchant_store')->getField('store_id,name,phone',true);
		foreach ($list as $k => $v) {
			$v['store_name']=$store_list[$v['store_id']]['name'];
			$list[$k]=$v;
		}
		$from_type=I('from_type','all','trim');
		$this->assign('from_type',$from_type);

		//e卡列表
		$ecard_list=M('ecard')->getField('id,card_name,card_price',true);
		$this->assign('ecard_list',$ecard_list);
		$ecard_list2=M('ecard')->group('card_price')->getField('id,card_name,card_price',true);
		$this->assign('ecard_list2',$ecard_list2);

		$this->assign('list',$list);
		$this->assign('card_id',$card_id);
		$this->assign('store_list',$store_list);
		
		$this->assign('pagebar',$page->show());
		$this->display();
	}
}
?>