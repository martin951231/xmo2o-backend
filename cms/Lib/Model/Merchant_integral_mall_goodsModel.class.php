<?php
class Merchant_integral_mall_goodsModel extends Model{
    public function merchant_integral_mall_goods_add($data){
        if(!$data){
            return false;
        }
        
        if(empty($data['goods_name'])){
             return array('status'=>0,'msg'=>L_('商品名称不能为空！'));
        }
        if($data['exchange_type'] == 0){
            if (!$data['spec_val']) {
                if(empty($data['payment_pure_integral'])){

                    return array('status'=>0,'msg'=>L_("所需纯X1不能为空！",array("X1" => C('config.score_name'))));
                }
                if(!is_numeric($data['payment_pure_integral'])){

                    return array('status'=>0,'msg'=>L_("所需纯X1必须为数字！",array("X1" => C('config.score_name'))));
                }
            }
             unset($data['payment_integral'],$data['payment_money']);
		}else if($data['exchange_type'] == 1){
			if(empty($data['payment_integral'])){
                
                 return array('status'=>0,'msg'=>L_("所需X1不能为空！",array("X1" => C('config.score_name')))); 
             }
             
             if(empty($data['payment_money'])){
                 return array('status'=>0,'msg'=>L_('所需余额不能为空！'));
             }
             
             if(!is_numeric($data['payment_integral'])){
                
                 return array('status'=>0,'msg'=>L_("所需X1必须为数字！",array("X1" => C('config.score_name')))); 
             }
             
             if(!is_numeric($data['payment_money'])){
                 return array('status'=>0,'msg'=>L_('所需余额必须为数字！'));
             }
		}else{
            if(empty($data['payment_pure_integral'])){
                 
                 return array('status'=>0,'msg'=>L_("所需纯X1不能为空！",array("X1" => C('config.score_name')))); 
             }
             
             if(!is_numeric($data['payment_pure_integral'])){
              
                 return array('status'=>0,'msg'=>L_("所需纯X1必须为数字！",array("X1" => C('config.score_name')))); 
             }
			 
			 if(empty($data['payment_integral'])){
              
                 return array('status'=>0,'msg'=>L_("所需X1不能为空！",array("X1" => C('config.score_name')))); 
             }
             
             if(empty($data['payment_money'])){
                 return array('status'=>0,'msg'=>L_('所需余额不能为空！'));
             }
             
             if(!is_numeric($data['payment_integral'])){
               
                 return array('status'=>0,'msg'=>L_("所需X1必须为数字！",array("X1" => C('config.score_name')))); 
             }
             
             if(!is_numeric($data['payment_money'])){
                 return array('status'=>0,'msg'=>L_('所需余额必须为数字！'));
             }
        }
		
		if(!is_numeric($data['exchange_limit_num'])  && (!$data['spec_val'])){
			return array('status'=>0,'msg'=>L_('每人限制兑换数量必须为数字！'));
		}
		
		if(!is_numeric($data['sku'])){
			return array('status'=>0,'msg'=>L_('库存数量必须为数字！'));
		}
		if($data['exchanged_num'] && !is_numeric($data['exchanged_num'])  && (!$data['spec_val'])){
			return array('status'=>0,'msg'=>L_('已兑换人数必须为数字！'));
		}
		if (!$data['exchanged_num'] || $data['exchanged_num'] <= 0) {
            $data['exchanged_num'] = 0;
        }
		
		$data['pc_pic'] = implode(';',$data['pc_pic']);
		if(empty($data['pc_pic'])){
			return array('status'=>0,'msg'=>L_('电脑端图片不能为空！'));
		}

        $data['wap_pic'] = implode(';',$data['wap_pic']);
        if(empty($data['wap_pic'])){
            return array('status'=>0,'msg'=>L_('手机端图片不能为空！'));
        }
		
        
        if(empty($data['cat_fid']) && empty($data['cat_id'])){
			return array('status'=>0,'msg'=>L_('分类不能为空！'));
        }

//        if(empty($data['intro'])){
//            return array('status'=>0 , 'msg'=>L_('简述不能为空！'));
//        }
//
		// $data['specification'] = nl2br($data['specification']);
//        $data['specification'] = str_replace("\r\n","^#",$data['specification']);
//		if(empty($data['specification'])){
//			return array('status'=>0,'msg'=>L_('规格不能为空！'));
//		}
//
//        if(empty($data['invoice_content'])){
//            return array('status'=>0,'msg'=>L_('发货清单不能为空！'));
//        }
//        if(empty($data['goods_content'])){
//            return array('status'=>0,'msg'=>L_('商品描述不能为空！'));
//        }
//        if(!isset($data['sort'])){
//            return array('status'=>0,'msg'=>L_('排序不能为空！'));
//        }
//
         if($data['sort'] && !is_numeric($data['sort'])){
             return array('status'=>0,'msg'=>L_('排序值必须为数字！'));
         }

        $data['invoice_content'] = nl2br($data['invoice_content']);
        $data['goods_content'] = htmlspecialchars_decode($data['goods_content']);
        $data['add_time'] = time();
        $insert_id = $this->data($data)->add();
        if($insert_id){
            if ($data['market_order_id']>0 && $data['mer_id']) {
                $m_where['order_id'] = $data['market_order_id'];
                $m_where['mer_id'] = $data['mer_id'];
                $m_where['status'] = 3;
                M('Market_order')->where($m_where)->setField('integral_mall_goods_id', $insert_id);
            }
            return array('status'=>1,'msg'=>L_('添加成功！'));
        }else{
            return array('status'=>0,'msg'=>L_('添加失败！'));
        }
    }

    public function Merchant_integral_mall_goods_page_list($where, $field = true, $order = 'goods_id desc', $pageSize = 20)
    {
        if (! $where) {
            return false;
        }
        if(!isset($_GET['page'] )){
            $_GET['page'] = $_POST['page'];
            //dump($_GET['page']);
        }
        import('@.ORG.system_page');
        $count = $this->where($where)->count();
        $page = new Page($count,$pageSize,'page');
        
        $list['list'] = $this->field($field)
            ->where($where)
            ->order($order)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $list['list'] = $this->deal_data($list['list']);
        
        $list['pagebar'] = $page->show();
        if ($list['list']) {
            $return['status'] = 1;
            $return['count'] = $count;
            $return['list'] = $list;
            $return['now'] = $page->nowPage;
            $return['total'] = $page->totalPage;
            return $return;
        } else {
            $list['list'] = array();
            return array(
                'status' => 0,
                'list' => $list
            );
        }
    }

    protected function deal_data($data, $is_goods_img=true){
        if($data){
            foreach ($data as $Key => $Gift) {
                $gift_pc_pic = str_replace(',', '/', $Gift['pc_pic']);
                $gift_wap_pic = str_replace(',', '/', $Gift['wap_pic']);
                $data[$Key]['pc_pic_list'] = explode(';', $gift_pc_pic);
                $data[$Key]['wap_pic_list'] = explode(';', $gift_wap_pic);//以前的逻辑，不要动，以免影响以前的业务

                if (substr($data[$Key]['payment_money'], - 3) == '.00') {
                    $data[$Key]['payment_money'] = reset(explode('.', $data[$Key]['payment_money']));
                } else {
                    $data[$Key]['payment_money'] = rtrim($data[$Key]['payment_money'], '0');
                }

                if(!empty($Gift['wap_pic'])){
                    $merchant_image_class = new merchant_image();
                    $goods_image_class    = new goods_image();
                    $wap_pic_list = array();
                    foreach($data[$Key]['wap_pic_list'] as $key=>$pic){
                        if (stripos($pic,'upload/') !== false || $is_goods_img) {

                            $wap_pic_list[$key]['title'] = $pic;
                            $tmp_pic = $goods_image_class->get_image_by_path($pic);
                            $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                        } else {

                            $wap_pic_list[$key]['title'] = $pic;
                            $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                            $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                        }
                    }
                    $data[$Key]['wap_pic_arr'] = $wap_pic_list;
                    $data[$Key]['wap_pic_first'] = trim($wap_pic_list[0]['url'],'/');//只需要一张图片
                    $data[$Key]['detail_url'] = U('goodsDetail',array('goods_id'=>$Gift['goods_id']));//只需要一张图片
                }
            }
        }
        return $data;
    }
	
	public function goods_del($where){
		if(!$where){
			return false;
		}
		
		$data['del_time'] = time();
		$data['is_del'] = 1;
		$insert_id = $this->where($where)->data($data)->save();
		if($insert_id){
			return array('status'=>1,'msg'=>L_('删除成功！'));
		}else{
			return array('status'=>0,'msg'=>L_('删除失败！'));
		}
	}
	
	public function goods_detail($where,$field = true, $is_goods_img=false){
		if(!$where){
			return false;
		}
		
		$detail = $this->where($where)->field($field)->find();
		if($detail){
            $merchant_image_class = new merchant_image();
            $goods_image_class = new goods_image();
			if(!empty($detail['pc_pic'])){
				$pc_pic_arr = explode(';',$detail['pc_pic']);
				$pc_pic_list = array();
				foreach($pc_pic_arr as $key=>$pic){
                    if (stripos($pic,'upload/') !== false || $is_goods_img) {
                        $pc_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $goods_image_class->get_image_by_path($pic);
                        $pc_pic_list[$key]['url'] = $tmp_pic['image'];
                        $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
                    } else {
                        $pc_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                        $pc_pic_list[$key]['url'] = $tmp_pic['image'];
                        $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
                    }
				}
				$detail['pc_pic_list'] = $pc_pic_list;
                $image_pic = reset($pc_pic_list);
                $detail['pic_image'] = $image_pic['url'];
            }


            if(!empty($detail['wap_pic'])){
                $wap_pic_arr = explode(';',$detail['wap_pic']);
                $wap_pic_list = array();
                foreach($wap_pic_arr as $key=>$pic){
                    if (stripos($pic,'upload/') !== false || $is_goods_img) {
                        $wap_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $goods_image_class->get_image_by_path($pic);
                        $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                    } else {
                        $wap_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $merchant_image_class->get_integral_mall_image_by_path($pic);
                        $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                    }
                }
                $detail['wap_pic_list'] = $wap_pic_list;
                $image_wap = reset($wap_pic_list);
                $detail['wap_image'] = $image_wap['url'];
            }

            if(!empty($detail['coupon_id'])){
                $coupon = D('System_coupon')->where(array('coupon_id'=>$detail['coupon_id']))->find();
                $detail['coupon'] = $coupon;
            }


            if(substr($detail['payment_money'] , -3)=='.00'){
                $detail['payment_money'] = reset(explode( '.' , $detail['payment_money']));
            }else{
                $detail['payment_money'] = rtrim($detail['payment_money'] , '0');
            }
            $detail['intro'] = replace_file_domain_content($detail['intro']);

            $detail['specification'] = trim(str_replace('^#',"\r\n", $detail['specification']));
            $detail['spec_val'] = unserialize($detail['spec_val']);
			$detail['invoice_content'] = strip_tags($detail['invoice_content']);


            $detail['goods_content'] = replace_file_domain_content($detail['goods_content']);

			return array('status'=>1,'detail'=>$detail);
		}else{
			return array('status'=>0,'detail'=>$detail);
		}
	}
	
	
	public function merchant_integral_mall_goods_edit($where,$data){
		if(!$where || !$data){
			return false;
		}
		
		if(empty($data['goods_name'])){
             return array('status'=>0,'msg'=>L_('商品名称不能为空！'));
        }
        if($data['exchange_type'] == 0){
            if (!$data['spec_val']) {
                if(empty($data['payment_pure_integral'])){
                    return array('status'=>0,'msg'=>L_("所需纯X1不能为空！",array("X1" => C('config.score_name'))));
                }
                if(!is_numeric($data['payment_pure_integral'])){
                    return array('status'=>0,'msg'=>L_("所需纯X1必须为数字！",array("X1" => C('config.score_name'))));
                }
            }
            $data['payment_integral'] = $data['payment_money'] = 0;
		}else if($data['exchange_type'] == 1){
			if(empty($data['payment_integral'])){
                 return array('status'=>0,'msg'=>L_("所需X1不能为空！",array("X1" => C('config.score_name')))); 
             }
             
             if(empty($data['payment_money'])){
                 return array('status'=>0,'msg'=>L_('所需余额不能为空！'));
             }
             
             if(!is_numeric($data['payment_integral'])){
                 return array('status'=>0,'msg'=>L_("所需X1必须为数字！",array("X1" => C('config.score_name')))); 
             }
             
             if(!is_numeric($data['payment_money'])){
                 return array('status'=>0,'msg'=>L_('所需余额必须为数字！'));
             }
            $data['payment_pure_integral'] = 0;
		}else{
            if(empty($data['payment_pure_integral'])){
                 return array('status'=>0,'msg'=>L_("所需纯X1不能为空！",array("X1" => C('config.score_name')))); 
             }
             
             if(!is_numeric($data['payment_pure_integral'])){
                 return array('status'=>0,'msg'=>L_("所需纯X1必须为数字！",array("X1" => C('config.score_name')))); 
             }
			 
			 if(empty($data['payment_integral'])){
                 return array('status'=>0,'msg'=>L_("所需X1不能为空！",array("X1" => C('config.score_name')))); 
             }
             
             if(empty($data['payment_money'])){
                 return array('status'=>0,'msg'=>L_('所需余额不能为空！'));
             }
             
             if(!is_numeric($data['payment_integral'])){
                 return array('status'=>0,'msg'=>L_("所需X1必须为数字！",array("X1" => C('config.score_name')))); 
             }
             
             if(!is_numeric($data['payment_money'])){
                 return array('status'=>0,'msg'=>L_('所需余额必须为数字！'));
             }
        }
		
		if(!is_numeric($data['exchange_limit_num']) && (!$data['spec_val'])){
			return array('status'=>0,'msg'=>L_('每人限制兑换数量必须为数字！'));
		}
		
		if(!is_numeric($data['sku']) && (!$data['spec_val'])){
			return array('status'=>0,'msg'=>L_('库存数量必须为数字！'));
		}
        if($data['exchanged_num'] && !is_numeric($data['exchanged_num'])  && (!$data['spec_val'])){
            return array('status'=>0,'msg'=>L_('已兑换人数必须为数字！'));
        }
		
		$data['pc_pic'] = implode(';',$data['pc_pic']);
		if(empty($data['pc_pic'])){
			return array('status'=>0,'msg'=>L_('图片不能为空！'));
		}

        $data['wap_pic'] = implode(';',$data['wap_pic']);
        if(empty($data['wap_pic'])){
            return array('status'=>0,'msg'=>L_('手机端图片不能为空！'));
        }

        if(empty($data['cat_fid']) && empty($data['cat_id'])){
			return array('status'=>0,'msg'=>L_('分类不能为空！'));
        }

//        if(empty($data['intro'])){
//            return array('status'=>0 , 'msg'=>L_('简述不能为空！'));
//        }

        //$data['specification'] = nl2br($data['specification']);
//		$data['specification'] = str_replace("\r\n","^#",$data['specification']);
//		if(empty($data['specification'])){
//			return array('status'=>0,'msg'=>L_('规格不能为空！'));
//		}
//
//        if(empty($data['invoice_content'])){
//            return array('status'=>0,'msg'=>L_('发货清单不能为空！'));
//        }
//        if(empty($data['goods_content'])){
//            return array('status'=>0,'msg'=>L_('商品描述不能为空！'));
//        }
        if($data['sort'] && !is_numeric($data['sort'])){
            return array('status'=>0,'msg'=>L_('排序值必须为数字！'));
        }
        
         if(!is_numeric($data['sort'])){
             return array('status'=>0,'msg'=>L_('排序值必须为数字！'));
         }

        $data['invoice_content'] = nl2br($data['invoice_content']);
        $data['goods_content'] = htmlspecialchars_decode($data['goods_content']);
        $data['last_time'] = time();
		$insert_id = $this->where($where)->data($data)->save();
		if($insert_id){
		    if ($data['market_order_id']>0 && $data['mer_id']) {
                $m_where['order_id'] = $data['market_order_id'];
                $m_where['mer_id'] = $data['mer_id'];
                $m_where['status'] = 3;
                M('Market_order')->where($m_where)->setField('integral_mall_goods_id', $where['goods_id']);
            }
			return array('status'=>1,'msg'=>L_('保存成功！'));
		}else{
			return array('status'=>0,'msg'=>L_('保存失败！'));
		}
	}


    public function get_gift_url($gift_id,$is_wap){
        if($is_wap){
            return str_replace('appapi.php', 'wap.php',U('Wap/Gift/detail',array('gift_id'=>$gift_id)));
        }else{
            $url = U('Index/Gift/gift_detail',array('gift_id'=>$gift_id));
            return $url;
        }
    }


    /*得到订单列表*/
    public function get_order_list($uid , $status , $is_wap=false , $page = 10 , $user_type = 1, $is_system = false){
        if ($user_type == 1) {
            $condition_where = "`o`.`uid`='$uid' AND `o`.`gift_id`=`g`.`gift_id`";
        }else{
            $condition_where = "`o`.`mer_id`='$uid' AND `o`.`gift_id`=`g`.`gift_id`";
        }

        if($status == -1){
            $condition_where .= " AND `o`.`paid` = 0 ";
        }else if($status == 1){
            $condition_where .= " AND `o`.`paid` = 1 ";
        }

        $condition_table = array(C('DB_PREFIX').'gift'=>'g',C('DB_PREFIX').'gift_order'=>'o');
        if ($is_system) {
            import('@.ORG.merchant_page');
        }else{
            import('@.ORG.user_page');
        }
        $count = $this->where($condition_where)->table($condition_table)->count();
        $p = new Page($count,$page);
        $order_list = $this->field('`o`.*,`g`.`exchange_type` `gift_exchange_type`')->where($condition_where)->table($condition_table)->order('`o`.`order_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();

        if(!empty($order_list)){
            $gift_image_class = new gift_image();
            foreach($order_list as $k=>$v){
                $tmp_pic_arr = explode(';',$v['pic']);
                $order_list[$k]['list_pic'] = $gift_image_class->get_image_by_path($tmp_pic_arr[0]);
                $order_list[$k]['url'] = $this->get_gift_url($v['gift_id'],$is_wap);
                $order_list[$k]['price'] = floatval($v['price']);
                $order_list[$k]['total_money'] = floatval($v['total_money']);


                if(!empty($v['pc_pic'])){
                    $gift_image_class = new gift_image();
                    $pc_pic_arr = explode(';',$v['pc_pic']);
                    $pc_pic_list = array();
                    foreach($pc_pic_arr as $key=>$pic){
                        $pc_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $gift_image_class->get_image_by_path($pic);
                        $order_list[$key]['image_url'] = $tmp_pic['image'];
                    }
                    $order_list[$k]['pc_pic_list'] = $pc_pic_list;
                }


                if(!empty($v['wap_pic'])){
                    $gift_image_class = new gift_image();
                    $wap_pic_arr = explode(';',$v['wap_pic']);
                    $wap_pic_list = array();
                    foreach($wap_pic_arr as $key=>$pic){
                        $wap_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $gift_image_class->get_image_by_path($pic);
                        $order_list[$k]['image_url'] = $tmp_pic['image'];
                    }
                    $order_list[$k]['wap_pic_list'] = $wap_pic_list;
                }
            }
        }

        $return['pagebar'] = $p->show();
        $return['order_list'] = $order_list;

        return $return;
    }

    public function getIndexGiftList(){
        $database_gift_category = D('Gift_category');

        $gift_category_condition['is_del'] = 0;
        $gift_category_condition['cat_status'] = 1;
        $gift_category_condition['cat_fid'] = 0;
        $gift_category_list = $database_gift_category->where($gift_category_condition)->order('cat_sort desc')->select();

        $gift_condition['is_del'] = 0;
        $gift_condition['status'] = 1;
        $gift_list = $this->where($gift_condition)->order('sort desc')->select();

        if(!empty($gift_list)){
            foreach($gift_list as $Key=>$Gift){
                $gift_pc_pic = str_replace(',' , '/' , $Gift['pc_pic']);
                $gift_wap_pic = str_replace(',' , '/' , $Gift['wap_pic']);
                $gift_list[$Key]['pc_pic_list'] = explode(';' , $gift_pc_pic);
                $gift_list[$Key]['wap_pic_list'] = explode(';' , $gift_wap_pic);
            }
        }

        if(!empty($gift_category_list)){
            foreach($gift_category_list as $Key=>$gift_category){
                foreach($gift_list as $k=>$gift){
                    if($gift_category['cat_id'] == $gift['cat_fid']){
                        $gift_category_list[$Key]['gift_list'][] = $gift;
                    }
                }
            }
            foreach($gift_category_list as $Key=>$gift_category){
                if(empty($gift_category['gift_list'])){
                    unset($gift_category_list[$Key]);
                }
            }
        }else{
            $gift_category_list = array();
        }

        return $gift_category_list;
    }


    public function get_qrcode($id){
        $condition_gift['gift_id'] = $id;
        $now_gift = $this->field('`gift_id`,`qrcode_id`')->where($condition_gift)->find();
        if(empty($now_gift)){
            return false;
        }
        return $now_gift;
    }

    public function save_qrcode($id,$qrcode_id){
        $condition_gift['gift_id'] = $id;
        $data_gift['qrcode_id'] = $qrcode_id;
        if($this->where($condition_gift)->data($data_gift)->save()){
            return(array('error_code'=>false));
        }else{
            return(array('error_code'=>true,'msg'=>L_("保存二维码至X1失败！请重试。",array("X1" => C('config.gift_alias_name'))))); 
        }
    }

    /**
     * @desc 分页获取数据-jjc
     * @param $where
     * @param int $page 需要获取的页数
     * @param int $pageSize 每页几条
     * @return array
     */
    public function page_list($where, $page=1,$pageSize = 10){
        import('@.ORG.system_page');
        $count = $this->where($where)->count();
        $page = new Page($count,$pageSize,'page');

        $list['list'] = $this->field(true)
            ->where($where)
            ->order('goods_id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach ($list['list'] as $Key => $Gift) {
            $gift_pc_pic = str_replace(',', '/', $Gift['pc_pic']);
            $gift_wap_pic = str_replace(',', '/', $Gift['wap_pic']);
            $list['list'][$Key]['pc_pic_list'] = explode(';', $gift_pc_pic);
            $list['list'][$Key]['wap_pic_list'] = explode(';', $gift_wap_pic);

            if (substr($list['list'][$Key]['payment_money'], - 3) == '.00') {
                $list['list'][$Key]['payment_money'] = reset(explode('.', $list['list'][$Key]['payment_money']));
            } else {
                $list['list'][$Key]['payment_money'] = rtrim($list['list'][$Key]['payment_money'], '0');
            }
        }

        $list['pagebar'] = $page->show();
        if ($list['list']) {
            $return['status'] = 1;
            $return['count'] = $count;
            $return['list'] = $list;
            $return['now'] = $page->nowPage;
            $return['total'] = $page->totalPage;
            return $return;
        } else {
            $list['list'] = array();
            return array(
                'status' => 0,
                'list' => $list
            );
        }
    }
}
?>