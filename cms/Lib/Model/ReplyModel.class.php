<?php
/**
 * order_type 0:代表团购，1：订餐，2：预约，3：快店，4：新的餐饮
 *
 */
class ReplyModel extends Model{
	/*得到带分页的评论列表*/
	public function get_page_reply_list($parent_id,$order_type,$tab='good',$order='',$store_count=0,$show_count=false, $from_type = 0,$goods_id=0){
		switch($tab){
			case 'high':
				$condition_reply = '`score`>3 AND ';
				break;
			case 'mid':
				$condition_reply = '`score`=3 AND ';
				break;
			case 'low':
				$condition_reply = '`score`<3 AND ';
				break;
			case 'withpic':
				$condition_reply = "( `pic`<>'' OR `reply_pic`<>'') AND ";
				break;
			case 'good':
				$condition_reply = '`score`>=3 AND ';
				break;
			case 'wrong':
				$condition_reply = '`score`<3 AND ';
				break;
		}

		if ($order == 'time') {
			$condition_order = '`r`.`add_time` DESC';
		} elseif($order == 'score') {
			$condition_order = '`r`.`score` DESC';
		}elseif($order_type == '3'){
			if ($order == 'custom') {
				$condition_order = '`r`.`score` DESC,`r`.`add_time` DESC';
			}else{
				$condition_order = '`r`.`pigcms_id` DESC';	
			}			
		}elseif ($order == 'custom') {
			$condition_order = '`r`.`score` DESC,`r`.`add_time` DESC';
		}else{
			$condition_order = '`r`.`pigcms_id` ASC';
		}
		if ($from_type == 0) {
			import('@.ORG.new_reply_ajax_page');
		} elseif ($from_type == 1) {
		    import('@.ORG.shop_pc_comment_page');
		} elseif ($from_type == 2) {
		    import('@.ORG.mall_pc_comment_page');
		}

		if(empty($parent_id)){
			$condition_reply_page = $condition_reply."`order_type`='$order_type' AND `status`<=1";
		}else{
			//是否获取商品评论
			$get_goods_reply=false;
			if($order_type == '3'){				
				if($goods_id>0){
					$where=array();
					$where['r.order_type']=$order_type;
					$where['r.parent_id']=$parent_id;
					$where['r.status']=array('lt',2);
					$where['g.goods_id']=$goods_id;
					//所有评论数
					$all_count=$this->alias('r')->join('inner join '.C('DB_PREFIX').'shop_order_detail g on g.id=r.order_detail_id')->where($where)->count(1);
					//if($all_count>0){//这里以前的逻辑是商品详情页的评论如果该商品没有评论则获取店铺评论
                    //    $get_goods_reply=true;
                    //}
                    $get_goods_reply=true;//现在的需求是，商品的评论只获取商品评论 2019-10-25/JJC/TAPD story 1001057

				}
				//显示总数的才查询
				if($show_count){
					if($get_goods_reply){
						//好评
						$where['r.score']=array('gt',3);
						$good_count=$this->alias('r')->join('inner join '.C('DB_PREFIX').'shop_order_detail g on g.id=r.order_detail_id')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where($where)->count(1);
						$good_percent='100%';
						if($all_count>0){
							$good_percent=round(($good_count/$all_count)*100).'%';	
						}
						//中评
						$where['r.score']=3;
						$common_count=$this->alias('r')->join('inner join '.C('DB_PREFIX').'shop_order_detail g on g.id=r.order_detail_id')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where($where)->count(1);
						//差评
						$where['r.score']=array('lt',3);
						$bad_count=$this->alias('r')->join('inner join '.C('DB_PREFIX').'shop_order_detail g on g.id=r.order_detail_id')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where($where)->count(1);
						$return['all_count']=intval($all_count);
						$return['good_count']=intval($good_count);
						$return['good_percent']=intval($good_percent);
						$return['common_count']=intval($common_count);
						$return['bad_count']=intval($bad_count);
					}elseif($goods_id>0){
						$where2=array();
						$where2['_string']="r.order_type='$order_type' AND r.parent_id='$parent_id' AND r.status<2";
						$where2['r.order_detail_id']=0;
						$return['all_count'] = $this->alias('r')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where($where2)->count();
						//好评
						$where2['r.score']=array('gt',3);
						$return['good_count'] = $this->alias('r')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where($where2)->count();
						//中评
						$where2['r.score']=3;
						$return['common_count'] = $this->alias('r')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where($where2)->count();
						//差评
						$where2['r.score']=array('lt',3);
						$return['bad_count'] = $this->alias('r')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where($where2)->count();
						//好评率
						$return['good_percent']='100%';
						if($return['all_count']>0){
							$return['good_percent']=round(($return['good_count']/$return['all_count'])*100).'%';
						}
					}else{
                        $check_condition = '';
                        if ($order_type == 3 && C('config.open_shop_reply_check')) {//开启快店评论审核功能
                            $check_condition = ' and r.`check`=2';
                        }
                        if ($order_type == 6 && C('config.open_mall_reply_check')) {//开启商城评论审核功能
                            $check_condition = ' and r.`check`=2';
                        }

						// if($order_type == '3'){
						$return['all_count'] = $this->alias('r')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where("r.order_type='$order_type' AND r.parent_id='$parent_id' AND r.status<2".$check_condition)->count();
						$return['good_count'] = $this->alias('r')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where("r.score >= 3 AND r.order_type='$order_type' AND r.parent_id='$parent_id' AND r.status<2".$check_condition)->count();
						$return['wrong_count'] = $this->alias('r')->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')->where("r.score < 3 AND r.order_type='$order_type' AND r.parent_id='$parent_id' AND r.status<2".$check_condition)->count();

                        $return['withpic_count'] = $this->alias('r')
                            ->join('inner join '.C('DB_PREFIX').'user u on r.uid=u.uid')
                            ->where("(r.`pic`<> '' OR r.`reply_pic`<> '') AND r.order_type='$order_type' AND r.parent_id='$parent_id' AND r.status<2".$check_condition)
                            ->count();
					}
				}				
			}
			$condition_reply_page = $condition_reply."`order_type`='$order_type' AND `parent_id`='$parent_id' AND `status`<=1";
		}
		if($get_goods_reply){
			$condition_reply_page.=' and de.goods_id='.$goods_id;
			$reply_count = $this->alias('r')->join('inner join '.C('DB_PREFIX').'shop_order_detail de on de.id=r.order_detail_id')->where($condition_reply_page)->count();
		}else{
			//避免查询商品评价不存在时获取其他商品的评价
			if($goods_id>0){
				$condition_reply_page.=' and order_detail_id=0';
			}
			$reply_count = $this->where($condition_reply_page)->count();

		}
		$page_size = 10;
		$p = new Page($reply_count, $page_size);

		if(($order_type == 0) && $store_count > 1){
			$reply_list = D('')->field('`s`.`name` `store_name`,`u`.`nickname`,`u`.`avatar`,`r`.*')->table(array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'group_order'=>'o',C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'user'=>'u'))->where((!empty($condition_reply) ? '`r`.'.$condition_reply : '')."`r`.`parent_id`='$parent_id' AND `r`.`status`<=1 AND `r`.`order_type`='$order_type' AND `r`.`order_id`=`o`.`order_id` AND `o`.`store_id`=`s`.`store_id` AND `r`.`uid`=`u`.`uid`")->order($condition_order)->limit($p->firstRow . ',' . $page_size)->select();
		}else{
			$parseCondition = $condition_reply ? str_replace(" `", " `r`.`", $condition_reply) : '';
			if(empty($parent_id)){
				$reply_condition = $parseCondition."  `r`.`order_type`='$order_type' AND `r`.`uid`=`u`.`uid`";
			}else{
				$reply_condition = $parseCondition."`r`.`parent_id`='$parent_id' AND `r`.`order_type`='$order_type' AND `r`.`uid`=`u`.`uid`";

			}
			//字段
			$field_str='`u`.`nickname`,`u`.`avatar`,`r`.*';
			//表
			$table_arr=array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'user'=>'u');
			if($order_type == 2){
				$reply_condition .= ' AND `r`.`status`=1';
			} elseif($order_type==3 && $get_goods_reply){	//商城
				$reply_condition.=' and de.goods_id='.$goods_id.' and de.id=r.order_detail_id';
				if(C('config.open_mall_reply_check')){
					$reply_condition .= ' and r.`check`=2';
				}
				$table_arr[C('DB_PREFIX').'shop_order_detail']='de';
				$field_str.=',de.spec';
			}else {
				$reply_condition .= ' AND `r`.`status`<=1';
				//避免查询商品评价不存在时获取其他商品的评价
				if($goods_id>0){
					$reply_condition.=' and r.order_detail_id=0';
				}
			}
			$reply_condition .= ' and r.status<2';//2是删除的
			if($order_type==3 && C('config.open_shop_reply_check')){//开启快店评论审核功能
				$reply_condition .= ' and r.`check`=2';
			}
			if($order_type==6 && C('config.open_mall_reply_check')){//开启商城评论审核功能
				$reply_condition .= ' and r.`check`=2';
			}
			$reply_list = D('')->field($field_str)->table($table_arr)->where($reply_condition)->order($condition_order)->limit($p->firstRow . ',' . $page_size)->select();


		}

		if($reply_list){
			$pic_arr = array();
			$new_pic_arr = array();
			if ($order_type == 3) {
				$order_ids = array_column($reply_list, 'order_id');
				$order_list = M('Shop_order')->where(array('order_id'=>array('in',$order_ids)))->select();
				$order_arr = array();
				foreach ($order_list as $key => $value) {
					$order_arr[$value['order_id']] = $value['is_pick_in_store'];
				}
			}
			foreach($reply_list as $key=>$value){
				if (isset($value['goods']) && $value['goods']) {
					$reply_list[$key]['goods'] = explode('#@#', $value['goods']);
				}
				$reply_list[$key]['add_time'] = date('Y-m-d',$value['add_time']);
				$reply_list[$key]['add_time_hi'] = date('Y-m-d H:i',$value['add_time']);
				if($value['merchant_reply_time']){
					$reply_list[$key]['merchant_reply_time_hi'] = date('Y-m-d H:i',$value['merchant_reply_time']);
				}
				if(!$value['avatar']){
					$reply_list[$key]['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
				}else{
					$reply_list[$key]['avatar'] = replace_file_domain($reply_list[$key]['avatar']);
				}
				if ($value['anonymous']) {
					$reply_list[$key]['nickname'] = L_('匿名用户');
					$reply_list[$key]['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
				}

				if(C('config.open_filter_word')==1){
					$filter_words = S('Filter_words');
					if(empty($filter_words)){
						$filter_words = M('Filter_word')->field('word')->select();
						$filter_words = array_values($filter_words);
						S('Filter_words',$filter_words);
					}
					$reply_list[$key]['comment'] = str_replace($filter_words,'***',$reply_list[$key]['comment']);
				}

				if(!empty($value['pic'])){
					$tmp_arr = explode(',',$value['pic']);
					foreach($tmp_arr as $v){
						$new_pic_arr[$v] = $key;
					}
					$pic_arr = array_merge($pic_arr,$tmp_arr);
				}
				// 自提订单，不计算配送评分
				$reply_list[$key]['hide_dscore'] = 0;
				if ($order_arr[$value['order_id']]==2) {
					$reply_list[$key]['hide_dscore'] = 1;
				}
			}
			if ($order_type == 0) {
				$pic_filepath = 'group';
			} elseif($order_type == 1) {
				$pic_filepath = 'meal';
			} elseif($order_type == 3) {
				$pic_filepath = 'shop';
			} elseif($order_type == 4) {
				$pic_filepath = 'foodshop';
			} else {
				$pic_filepath = 'appoint';
			}
			if($pic_arr){
				$condition_reply_pic['pigcms_id'] = array('in',implode(',',$pic_arr));
				$reply_pic_list = D('Reply_pic')->field('`pigcms_id`,`pic`')->where($condition_reply_pic)->select();
				$reply_image_class = new reply_image();
				foreach($reply_pic_list as $key=>$value){
					$tmp_value = $reply_image_class->get_image_by_path($value['pic'],$pic_filepath);
					if($tmp_value['s_image']){
					    $tmp_value['s_image'] = thumb($tmp_value['s_image'],100,100,'fill');
                    }
					$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'][] = $tmp_value;
				}
			}
		}
		$reply_page = $p->show();
		$return['count'] = $reply_count;
		$return['list']  = $reply_list ? $reply_list : array();
		$return['page']  = $reply_page;
		$return['now']  = $p->nowPage;
		$return['total']  = $p->totalPage;
		return $return;
	}
	/*得到规定条数的评论列表,不带评论*/
	public function get_reply_list($parent_id,$order_type,$store_count,$limit){

		$condition_order = '`r`.`add_time` DESC';
		if($order_type == 0 && $store_count > 1){
			$reply_list = D('')->field('`s`.`name` `store_name`,`u`.`nickname`,`u`.`avatar`,`r`.*')->table(array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'group_order'=>'o',C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'user'=>'u'))->where("`r`.`parent_id`='$parent_id' AND `r`.`status`<=1 AND `r`.`order_type`='$order_type' AND `r`.`order_id`=`o`.`order_id` AND `o`.`store_id`=`s`.`store_id` AND `r`.`uid`=`u`.`uid`")->order($condition_order)->limit($limit)->select();
		}else{
			$reply_list = D('')->field('`u`.`nickname`,`u`.`avatar`,`r`.*')->table(array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'user'=>'u'))->where("`r`.`parent_id`='$parent_id' AND `r`.`status`<=1 AND `r`.`order_type`='$order_type' AND `r`.`uid`=`u`.`uid`")->order($condition_order)->limit($limit)->select();
		}

		if($reply_list){
			$pic_arr = array();
			$new_pic_arr = array();
			foreach($reply_list as $key=>$value){
				if($value['anonymous']){
					if(msubstr($value['nickname'],0,2,false) == $value['nickname']){
						$reply_list[$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**';
					}else{
						$reply_list[$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**'.msubstr($value['nickname'],-1,1,false);
					}
				}

                if(!$value['avatar']){
                    $reply_list[$key]['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
                }else{
                    $reply_list[$key]['avatar'] = replace_file_domain($reply_list[$key]['avatar']);
                }

				$reply_list[$key]['add_time'] = date('Y-m-d',$value['add_time']);

				if(!empty($value['pic'])){
					$tmp_arr = explode(',',$value['pic']);
					foreach($tmp_arr as $v){
						$new_pic_arr[$v] = $key;
					}
					$pic_arr = array_merge($pic_arr,$tmp_arr);
				}
			}
			if($order_type == 0){
				$pic_filepath = 'group';
			}elseif($order_type == 1){
				$pic_filepath = 'meal';
			}elseif($order_type == 4){
				$pic_filepath = 'foodshop';
			} else {
				$pic_filepath = 'appoint';
			}
			if($pic_arr){
				$condition_reply_pic['pigcms_id'] = array('in',implode(',',$pic_arr));
				$reply_pic_list = D('Reply_pic')->field('`pigcms_id`,`pic`')->where($condition_reply_pic)->select();
				$reply_image_class = new reply_image();
				foreach($reply_pic_list as $key=>$value){
					$tmp_value = $reply_image_class->get_image_by_path($value['pic'],$pic_filepath);
					$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'][] = $tmp_value;
				}
			}
		}
		return $reply_list;
	}
	/**
	 * @desc 获取店铺所有类型的评论条数
	 * @param $store_id
	 * @param $limit
	 * @return mixed
	 */
	public function get_store_reply_list_count($store_id){
        $condition = "`r`.`store_id`='$store_id' AND `r`.`status`<=1  AND `r`.`uid`=`u`.`uid`";
        if (C('config.open_shop_reply_check')) {//开启快店评论审核功能
            $condition .= ' and (r.`order_type` <> 3 OR (r.`order_type`=3 AND r.`check`=2 AND o.order_from <> 1))';
        }
        if (C('config.open_mall_reply_check')) {//开启商城评论审核功能
            $condition .= ' and (r.`order_type` <> 3 OR (r.`order_type`=3 AND r.`check`=2 AND o.order_from = 1))';
        }
        $reply_list_count = $this->alias('r')
            ->join(C('DB_PREFIX') . 'user AS u ON r.uid = u.uid')
            ->join(C('DB_PREFIX') . 'shop_order AS o ON r.order_id = o.order_id AND r.order_type = 3')
            ->where($condition)
            ->count();
		return $reply_list_count;
	}
	/**
	 * @desc 获取店铺所有类型的评论
	 * @param $store_id
	 * @param $limit
	 * @return mixed
	 */
	public function get_store_reply_list($store_id,$limit){
		$condition_order = '`r`.`score` DESC,`r`.`add_time` DESC';//好评优先
        $condition = "`r`.`store_id`='$store_id' AND `r`.`status`<=1  AND `r`.`uid`=`u`.`uid`";
        if (C('config.open_shop_reply_check')) {//开启快店评论审核功能
            $condition .= ' and (r.`order_type` <> 3 OR (r.`order_type`=3 AND r.`check`=2 AND o.order_from <> 1))';
        }
        if (C('config.open_mall_reply_check')) {//开启商城评论审核功能
            $condition .= ' and (r.`order_type` <> 3 OR (r.`order_type`=3 AND r.`check`=2 AND o.order_from = 1))';
        }
        $reply_list = $this->alias('r')
            ->join(C('DB_PREFIX') . 'user AS u ON r.uid = u.uid')
            ->join(C('DB_PREFIX') . 'shop_order AS o ON r.order_id = o.order_id AND r.order_type = 3')
	    ->field('`u`.`nickname`,`u`.`avatar`,`r`.*')
            ->where($condition)
            ->order($condition_order)
            ->limit($limit)
            ->select();

		if($reply_list){
			$pic_arr = array();
			$new_pic_arr = array();
			foreach($reply_list as $key=>$value){
				if($value['anonymous']){
					if(msubstr($value['nickname'],0,2,false) == $value['nickname']){
						$reply_list[$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**';
					}else{
						$reply_list[$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**'.msubstr($value['nickname'],-1,1,false);
					}
				}
				if(!$value['avatar']){
					$reply_list[$key]['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
				}else{
					$reply_list[$key]['avatar'] = replace_file_domain($reply_list[$key]['avatar']);
				}
				$reply_list[$key]['add_time'] = date('Y-m-d',$value['add_time']);
				if(!empty($value['pic'])){
					$tmp_arr = explode(',',$value['pic']);
					foreach($tmp_arr as $v){
						$new_pic_arr[$v] = $key;
					}
					$pic_arr = array_merge($pic_arr,$tmp_arr);
				}
				$reply_list[$key]['goods_arr'] = [];
				if (isset($value['goods']) && $value['goods']) {
					$reply_list[$key]['goods_arr'] = explode('#@#', $value['goods']);
				}
			}
			
			if($reply_list[$new_pic_arr[$value['pigcms_id']]]){
				$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'] = [];
				$reply_list[$new_pic_arr[$value['pigcms_id']]]['pic'] = [];
			}
			if($pic_arr){
				$condition_reply_pic['pigcms_id'] = array('in',implode(',',$pic_arr));
				$reply_pic_list = D('Reply_pic')->field('`order_type`,`pigcms_id`,`pic`')->where($condition_reply_pic)->select();
				$reply_image_class = new reply_image();
				foreach($reply_pic_list as $key=>$value){
					switch($value['order_type'])
					{
						case '0':$pic_filepath = 'group';break;
						case '1':$pic_filepath = 'meal';break;
						case '4':$pic_filepath = 'foodshop';break;
						default:$pic_filepath  = 'appoint';
					}
					$tmp_value = $reply_image_class->get_image_by_path($value['pic'],$pic_filepath);

					if(!$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics']){
						$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'] = [];
						$reply_list[$new_pic_arr[$value['pigcms_id']]]['pic'] = [];
					}
					$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'][] = $tmp_value;
                    $reply_list[$new_pic_arr[$value['pigcms_id']]]['pic'][] = thumb_img($tmp_value['s_image'],'120','120','fill');
				}
			}
			
		}
		return $reply_list;
	}


	/*得到规定条数的评论列表*/
	public function ajax_reply_list($parent_id, $order_type, $store_count, $limit, $start)
	{
		$condition_order = '`r`.`add_time` DESC';
		if($order_type == 0 && $store_count > 1){
			$reply_list = D('')->field('`s`.`name` `store_name`,`u`.`nickname`,`r`.*')->table(array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'group_order'=>'o',C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'user'=>'u'))->where("`r`.`parent_id`='$parent_id' AND `r`.`status`<=1 AND `r`.`order_id`=`o`.`order_id` AND `o`.`store_id`=`s`.`store_id` AND `r`.`uid`=`u`.`uid`")->order($condition_order)->limit($start, $limit)->select();
		}else{
			$reply_list = D('')->field('`u`.`nickname`,`r`.*')->table(array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'user'=>'u'))->where("`r`.`parent_id`='$parent_id' AND `r`.`uid`=`u`.`uid` AND `r`.`status`<=1")->order($condition_order)->limit($start, $limit)->select();
// 			echo D('')->_sql();die;
		}

		if($reply_list){
			$pic_arr = array();
			$new_pic_arr = array();
			foreach($reply_list as $key=>$value){
				$reply_list[$key]['add_time'] = date('Y-m-d',$value['add_time']);

				if(!empty($value['pic'])){
					$tmp_arr = explode(',',$value['pic']);
					foreach($tmp_arr as $v){
						$new_pic_arr[$v] = $key;
					}
					$pic_arr = array_merge($pic_arr,$tmp_arr);
				}
			}
			if($order_type == 0){
				$pic_filepath = 'group';
			} elseif($order_type == 4) {
				$pic_filepath = 'foodshop';
			}else{
				$pic_filepath = 'meal';
			}
			if($pic_arr){
				$condition_reply_pic['pigcms_id'] = array('in',implode(',',$pic_arr));
				$reply_pic_list = D('Reply_pic')->field('`pigcms_id`,`pic`')->where($condition_reply_pic)->select();
				$reply_image_class = new reply_image();
				foreach($reply_pic_list as $key=>$value){
					$tmp_value = $reply_image_class->get_image_by_path($value['pic'],$pic_filepath);
					$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'][] = $tmp_value;
				}
			}
		}
		return $reply_list;
	}


	/*得到带分页的评论列表*/
	public function get_reply_listByid($mer_id,$store_id=0,$tab='all',$order,$pagesize=0){
		$condition_reply_page=$condition_reply='';
		switch($tab){
			case 'high':
				$condition_reply = '`score`>3 AND ';
				break;
			case 'mid':
				$condition_reply = '`score`=3 AND ';
				break;
			case 'low':
				$condition_reply = '`score`<3 AND ';
				break;
			case 'withpic':
				$condition_reply = "`pic`<>'' AND ";
				break;
		}
		if ($order == 'time') {
			$condition_order = '`r`.`add_time` DESC';
		} elseif($order == 'score') {
			$condition_order = '`r`.`score` DESC';
		} else {
			$condition_order = '`r`.`pigcms_id` ASC';
		}
		import('@.ORG.common_page');

		$condition_reply_page = !empty($condition_reply) ? " `r`." . $condition_reply . " `r`.`mer_id`='$mer_id' AND `r`.`status`<2" : " `r`.`mer_id`='$mer_id' AND `r`.`status`<2";
		$condition_reply_count = $condition_reply . " `mer_id`='$mer_id' AND `status`<2";
		
		if($store_id>0){
			$condition_reply_page = $condition_reply_page." AND `r`.`store_id`='$store_id'";
			$condition_reply_count=$condition_reply_count." AND `store_id`='$store_id'";
		}
		$reply_count = $this->where($condition_reply_count)->count();

		$p = new Page($reply_count,$pagesize);
		$reply_list = D('')->field('`s`.`name` as 	store_name,`u`.`nickname`,`u`.`avatar`,`r`.*')->table(array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'user'=>'u'))->where($condition_reply_page." AND `r`.`store_id`=`s`.`store_id` AND `r`.`uid`=`u`.`uid`")->order($condition_order)->limit($p->firstRow.','.$pagesize)->select();
		if($reply_list){
			$pic_arr = array();
			$new_pic_arr = array();
			foreach($reply_list as $key=>$value){
				$reply_list[$key]['add_time'] = date('Y-m-d',$value['add_time']);
				if($value['anonymous']){
					if(msubstr($value['nickname'],0,2,false) == $value['nickname']){
						$reply_list[$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**';
					}else{
						$reply_list[$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**'.msubstr($value['nickname'],-1,1,false);
					}
				}
				if(!empty($value['pic'])){
					$tmp_arr = explode(',',$value['pic']);
					foreach($tmp_arr as $v){
						$new_pic_arr[$v] = $key;
					}
					$pic_arr = array_merge($pic_arr,$tmp_arr);
				}
			}
			if($pic_arr){
				$condition_reply_pic['pigcms_id'] = array('in',implode(',',$pic_arr));
				$reply_pic_list = D('Reply_pic')->field('`pigcms_id`,`pic`,order_type')->where($condition_reply_pic)->select();
				$reply_image_class = new reply_image();
				foreach($reply_pic_list as $key=>$value){
					$order_type=intval($value['order_type']);
					if($order_type == 0){
						$pic_filepath = 'group';
					} elseif($order_type == 4) {
						$pic_filepath = 'foodshop';
					}else{
						$pic_filepath = 'meal';
					}
					$tmp_value = $reply_image_class->get_image_by_path($value['pic'],$pic_filepath);
					$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'][] = $tmp_value;
				}
			}
		}
		$reply_page = $p->show();
		$return['count'] = $reply_count;
		$return['list']  = $reply_list;
		$return['page']  = $reply_page;
		$return['now']  = $p->nowPage;
		$return['total']  = $p->totalPage;
		return $return;
	}


	public function get_appoint_reply_list($tab='high',$order = 'score'){
		switch($tab){
			case 'high':
				$condition_reply = '`score`>3 AND ';
				break;
			case 'mid':
				$condition_reply = '`score`=3 AND ';
				break;
			case 'low':
				$condition_reply = '`score`<3 AND ';
				break;
			case 'withpic':
				$condition_reply = "`pic`<>'' AND ";
				break;
		}
		if ($order == 'time') {
			$condition_order = '`r`.`add_time` DESC';
		} elseif($order == 'score') {
			$condition_order = '`r`.`score` DESC';
		} else {
			$condition_order = '`r`.`pigcms_id` ASC';
		}
		$reply_list = D('')->field('`s`.`name` as 	store_name,`u`.`nickname`,`u`.`avatar`,`r`.*')->table(array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'user'=>'u'))->where($condition_reply ."`r`.`store_id`=`s`.`store_id` AND `r`.`uid`=`u`.`uid` AND `r`.`status` = 1 AND `r`.`order_type` = 2")->order($condition_order)->select();
		if($reply_list){
			$pic_arr = array();
			$new_pic_arr = array();
			$database_user = D('User');
			foreach($reply_list as $key=>$value){
				$reply_list[$key]['add_time'] = date('Y-m-d',$value['add_time']);
				if($value['anonymous']){
					if(msubstr($value['nickname'],0,2,false) == $value['nickname']){
						$reply_list[$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**';
					}else{
						$reply_list[$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**'.msubstr($value['nickname'],-1,1,false);
					}
				}
				if(!empty($value['pic'])){
					$tmp_arr = explode(',',$value['pic']);
					foreach($tmp_arr as $v){
						$new_pic_arr[$v] = $key;
					}
					$pic_arr = array_merge($pic_arr,$tmp_arr);
				}

				$where['uid'] = $value['uid'];
				$reply_list[$key]['sex'] = $database_user->where($where)->getField('sex');

			}
			if($pic_arr){
				$condition_reply_pic['pigcms_id'] = array('in',implode(',',$pic_arr));
				$reply_pic_list = D('Reply_pic')->field('`pigcms_id`,`pic`,order_type')->where($condition_reply_pic)->select();
				$reply_image_class = new reply_image();
				foreach($reply_pic_list as $key=>$value){
					$order_type=intval($value['order_type']);
					if($order_type == 0){
						$pic_filepath = 'group';
					} elseif($order_type == 4) {
						$pic_filepath = 'foodshop';
					}else{
						$pic_filepath = 'meal';
					}
					$tmp_value = $reply_image_class->get_image_by_path($value['pic'],$pic_filepath);
					$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'][] = $tmp_value;
				}
			}
		}
		return $reply_list;
	}



	//预约不带分页列表
	public function get_appointReply_list($appoint_id,$Map=array()){
		if(!$appoint_id){
			return false;
		}

		$database_appoint = D('Appoint');
		$where['_string']=C('DB_PREFIX').'appoint_order.appoint_id='.$appoint_id;
		$field=C('DB_PREFIX').'appoint_order.order_id,'.C('DB_PREFIX').'appoint_order.appoint_id';
		$order_id_arr = $database_appoint->join('__APPOINT_ORDER__ ON __APPOINT__.appoint_id=__APPOINT_ORDER__.appoint_id')->where($where)->getField($field);
		$reply_list=array();
		if($order_id_arr){
			$database_reply = D('Reply');
			$Map['order_id'] = array('in',array_keys($order_id_arr));
			$Map['status'] = 1;
			$reply_list = $database_reply->where($Map)->select();
			if($reply_list){
				$pic_arr = array();
				$new_pic_arr = array();
				foreach($reply_list as $key=>$value){
					$reply_list[$key]['add_time'] = date('Y-m-d',$value['add_time']);
					if(!empty($value['pic'])){
						$tmp_arr = explode(',',$value['pic']);
						foreach($tmp_arr as $v){
							$new_pic_arr[$v] = $key;
						}
						$pic_arr = array_merge($pic_arr,$tmp_arr);
					}

					$user_info=D('User')->where(array('uid'=>$value['uid']))->field('nickname,avatar')->find();
					$reply_list[$key]['nickname']= $user_info['nickname'];
					$reply_list[$key]['avatar']= $user_info['avatar'];
				}

				if($pic_arr){
					$condition_reply_pic['pigcms_id'] = array('in',implode(',',$pic_arr));
					$reply_pic_list = D('Reply_pic')->field('`pigcms_id`,`pic`')->where($condition_reply_pic)->select();
					$reply_image_class = new reply_image();
					foreach($reply_pic_list as $key=>$value){
						$tmp_value = $reply_image_class->get_image_by_path($value['pic'],'appoint');
						$reply_list[$new_pic_arr[$value['pigcms_id']]]['pics'][] = $tmp_value;
					}
				}


			}
		}
		return $reply_list;
	}
}

?>