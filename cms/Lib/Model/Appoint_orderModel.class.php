<?php
class Appoint_orderModel extends Model{
	/*获取订单详情*/
	public function get_order_detail_by_id($uid, $order_id, $is_wap=false, $check_user=true){
		$database_appoint = D('Appoint');
		$database_appoint_visit_order_info = D('Appoint_visit_order_info');
		$database_appoint_product = D('Appoint_product');
		$database_user = D('User');
		if($check_user){
			$condition_appoint['uid'] = $uid;
		} else {
			$condition_appoint['uid'] = '';
		}
		$condition_appoint['_string'] = "order_id='{$order_id}' OR orderid='{$order_id}'";
		$now_order = $this->field(true)->where($condition_appoint)->find();

		if(empty($now_order)){
			return null;
		}
		$where['appoint_id'] = $now_order['appoint_id'];
		$appoint_info = $database_appoint->field(true)->where($where)->find();

		$tmp_pic_arr = explode(';', $appoint_info['pic']);
		$appoint_image_class = new appoint_image();
		$now_order['list_pic'] = $appoint_image_class->get_image_by_path($tmp_pic_arr[0],'s');
		$now_order['appoint_name'] = $appoint_info['appoint_name'];
		// $now_order['appoint_price'] = $appoint_info['appoint_price'];
		$now_order['appoint_type'] = $appoint_info['appoint_type'];
		$now_order['start_time'] = $appoint_info['start_time'];
		$now_order['end_time'] = $appoint_info['end_time'];

		$now_order['reply_count'] = $appoint_info['reply_count'];
		$now_order['score_all'] = $appoint_info['score_all'];
		$now_order['score_mean'] = $appoint_info['score_mean'];
//		$now_order['payment_status'] = $appoint_info['payment_status'];
		$now_order['is_appoint_price'] = $appoint_info['is_appoint_price'];
		$now_order['pay_type_txt'] = D('Pay')->get_pay_name($now_order['pay_type'], $now_order['is_mobile_pay']);
		$now_order['product_pay_type_txt'] = D('Pay')->get_pay_name($now_order['product_pay_type'], $now_order['is_mobile_pay']);

		if($now_order['pay_type_str']!='' && $now_order['is_own']>0){
			$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
			if($now_order['is_own']==2){
				$now_order['pay_type_txt'] .= '(平台服务商子商家：'.$now_merchant['name'].')';
				$now_order['product_pay_type_txt'] .= '(平台服务商子商家：'.$now_merchant['name'].')';
			}else{
				$now_order['pay_type_txt'] .= '(商家：'.$now_merchant['name'].')';
				$now_order['product_pay_type_txt'] .= '(商家：'.$now_merchant['name'].')';
			}
		}


		$now_order['url'] = $database_appoint->get_appoint_url($now_order['appoint_id'], $is_wap);
		$condition_user['uid'] = $uid;
		$user_info = $database_user->field('`phone`,nickname')->where($condition_user)->find();
		$now_order['phone'] = $user_info['phone'];
        $now_order['user_name'] = $user_info['nickname'];

		if(!empty($now_order['product_id'])){
			$product_detail = $database_appoint_product->get_product_info($now_order['product_id']);
			if($product_detail['status']){
				$now_order['product_detail'] = $product_detail['detail'];
			}
		}
		$Map['appoint_order_id'] = $now_order['order_id'];
		$appoint_visit_order_info = $database_appoint_visit_order_info->appoint_visit_order_detail($Map);
		if($appoint_visit_order_info){
			$now_order['worker_detail'] = $appoint_visit_order_info['detail'];
		}
		return $now_order;
	}

	/**获取订单分类**/
	public function get_order_cate($order_id){
		$appoint_id = $this->field('appoint_id')->where(array('order_id'=>$order_id))->find();
		$cat_id = D('Appoint')->field('cat_fid')->where(array('appoint_id'=>$appoint_id['appoint_id']))->find();
		$appoint_cate = D('Appoint_category')->field('cat_id,cat_name')->where(array('cat_id'=>$cat_id['cat_fid']))->find();
		return $appoint_cate;
	}

	// 下单
	public function save_post_form($appoint,$uid,$order_id,$merchant_workers_id = 0){
        fdump('下单>>'.__LINE__,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
        fdump($appoint,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
		fdump($uid,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
		fdump($order_id,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
		$data_appoint_order['uid'] = $uid;
		$data_appoint_order['appoint_id'] = $appoint['appoint_id'];
		$data_appoint_order['paid'] = $appoint['paid'] ?? 0;
		$data_appoint_order['mer_id'] = $appoint['mer_id'];
		$data_appoint_order['order_time'] = $_SERVER['REQUEST_TIME'];
		$data_appoint_order['payment_money'] = $appoint['payment_money'];
		$data_appoint_order['appoint_price'] = $appoint['appoint_price'] ?: 0;
		$data_appoint_order['payment_status'] = $appoint['payment_status'];
		$data_appoint_order['content'] = $appoint['content'] ? $appoint['content'] : '';
		$data_appoint_order['appoint_type'] = $appoint['appoint_type'];
		$data_appoint_order['product_id'] = $appoint['product_id'];
		$data_appoint_order['cue_field'] = $appoint['cue_field'];
		$appoint['distributor_uid'] && $data_appoint_order['distributor_uid'] = $appoint['distributor_uid'];
		$data_appoint_order['real_orderid']=date('Ymdhis') . substr(microtime(), 2, 8 - strlen($this->user_session['uid'])) . $this->user_session['uid'];
//		if(!$appoint['appoint_type']){//因为之前有上门到店区分,现在不需要了
			$data_appoint_order['appoint_time'] = $appoint['appoint_time'];
			$data_appoint_order['appoint_date'] = $appoint['appoint_date'];
//		}
        if($appoint['place_order_worker']){
            $data_appoint_order['place_order_worker'] = $appoint['place_order_worker'];
        }
		if($appoint['payment_status']==0){
			$pass_arr = array(
					date('y',$_SERVER['REQUEST_TIME']),
					date('m',$_SERVER['REQUEST_TIME']),
					date('d',$_SERVER['REQUEST_TIME']),
					date('H',$_SERVER['REQUEST_TIME']),
					date('i',$_SERVER['REQUEST_TIME']),
					date('s',$_SERVER['REQUEST_TIME']),
					mt_rand(10,99),
			);
			$data_appoint_order['appoint_pass'] = implode('',$pass_arr);
		}
		$data_appoint_order['store_id'] = $appoint['store_id'];
        $data_appoint_order['cat_id'] = $appoint['cat_id'];
        $data_appoint_order['cat_fid'] = $appoint['cat_fid'];
		$data_appoint_order['merchant_worker_id'] = $merchant_workers_id ? $merchant_workers_id  : 0;
		$data_appoint_order['order_name'] = $appoint['appoint_name'];
		$data_appoint_order['merchant_assign_time'] =  time();

		if($_SESSION['now_village_bind']['village_id']>0&&M('House_village_appoint')->where(array('village_id'=>$_SESSION['now_village_bind']['village_id'],'appoint_id'=>$appoint['appoint_id']))->find()){
			$data_appoint_order['village_id'] = $_SESSION['now_village_bind']['village_id'];
		}
		if($appoint['extra_pay_price']>0){
			$data_appoint_order['extra_price'] = $appoint['extra_pay_price'];
		}

		if(!$appoint['is_store']){
			$data_appoint_order['type'] = 2;
		}

		if($appoint['product_id'] > 0){
			$database_appoint_product = D('Appoint_product');
			$product_where['id'] = $appoint['product_id'];
			$now_product = $database_appoint_product->where($product_where)->find();
			if($now_product){
				$data_appoint_order['product_name'] = $now_product['name'];
				$data_appoint_order['product_content'] = $now_product['content'];
				$data_appoint_order['product_payment_price'] = $now_product['payment_price'];
				$data_appoint_order['product_price'] = $now_product['price'];
				$data_appoint_order['product_use_time'] = $now_product['use_time'];
			}
            $data_appoint_order['appoint_price'] = 0;
            $data_appoint_order['payment_money'] = 0;
            $data_appoint_order['product_real_payment_price'] = $appoint['payment_money'] ?: 0;
		}

		$now_user = D('User')->get_user($uid);
		if(empty($now_user)){
			return array('error'=>1,'msg'=>L_('未获取到您的帐号信息，请重试！'),'url'=>C('config.site_url').'/index.php?c=Login&index');
		}
		if($order_id){
			$condition_group_order['order_id'] = $order_id;
			$condition_group_order['uid'] = $uid;

			$save_result = $this->where($condition_group_order)->data($data_appoint_order)->save();
			if($save_result){
                fdump('添加系统订单列表>>'.__LINE__,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
                fdump($data_appoint_order,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
                fdump($order_id,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
                D('System_order')->add_order('appoint',$data_appoint_order,$order_id);
                if (floatval($data_appoint_order['appoint_price'])==0 && (!$data_appoint_order['product_price'] || floatval($data_appoint_order['product_price'])==0)) {
                    D('System_order')->reg_group_pay_order($order_id,$data_appoint_order, 'appoint');
                }

			    //小票打印start
			    $printHaddle = new PrintHaddle();
			    $printHaddle->printit($order_id, 'appoint_order', 1);
				//小票打印end


			    /*----记录系统订单----*/

                //增加搜索关键词  店铺名称 和 商品名称
                $store_info = M('Merchant_store')->field('name')->where(['store_id'=>$data_appoint_order['store_id']])->find();
                $data_appoint_order['keywords'] = rtrim($store_info['name'].','.$appoint['appoint_name'], ',');


				return array('error'=>0,'msg'=>L_('订单修改成功！'),'order_id'=>$order_id);
			}else{
				return array('error'=>1,'msg'=>L_('订单修改失败！请重试'),'order_id'=>$order_id);
			}
		}else{
			//上门预约服务工作人员处理start
			$databaes_appoint_visit_order_info = D('Appoint_visit_order_info');
			$database_merchant_workers = D('Merchant_workers');
			$order_id = $this->data($data_appoint_order)->add();
			$appoint_visit_order_info['merchant_worker_id'] = $merchant_workers_id ? $merchant_workers_id : 0 ;
			$appoint_visit_order_info['uid'] = $uid;
			$appoint_visit_order_info['add_time'] = time();
			$appoint_visit_order_info['appoint_order_id'] = $order_id;
			$appoint_visit_order_info['service_address'] = $data_appoint_order['appoint_type'] == 1 ? ($appoint['cue_field'] ? $appoint['cue_field'] : '') : '';
			$appoint_visit_order_info_insert_id = $databaes_appoint_visit_order_info->data($appoint_visit_order_info)->add();
			if(!$appoint_visit_order_info_insert_id){
				return array('error'=>1,'msg'=>L_('预约订单产生失败！'));
			}else{
				$worker_where['merchant_worker_id'] = $merchant_workers_id;
				$database_merchant_workers->where($worker_where)->setInc('appoint_num');
			}
			//上门预约服务工作人员处理end

			if($order_id){
                fdump('同步添加系统订单列表>>'.__LINE__,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
                fdump($data_appoint_order,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
                fdump($order_id,'api/log/'.date('Ymd').'/appoint/appoint_order',true);
                D('System_order')->add_order('appoint',$data_appoint_order,$order_id);
                if (floatval($data_appoint_order['appoint_price'])==0 && (!$data_appoint_order['product_price'] || floatval($data_appoint_order['product_price'])==0)) {
                    D('System_order')->reg_group_pay_order($order_id,$data_appoint_order, 'appoint');
                }
                if(!$data_appoint_order['payment_status']){
                    // 如果不需要支付定金 则未支付时候不通知相关对象
                    return array('error'=>0,'msg'=>L_('订单产生成功！'),'order_id'=>$order_id);
                }

				if ($_SESSION['openid']) {
					$href = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$order_id;
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $_SESSION['openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $order_id, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $appoint['appoint_name'], 'remark' => L_('您的该次预约下单成功，感谢您的使用！')));
				}

				$sms_data = array('mer_id' => $appoint['mer_id'], 'store_id' => 0, 'type' => 'appoint');
				if (C('config.sms_place_order') == 1 || C('config.sms_place_order') == 3) {
					$sms_data['uid'] = $uid;
					$sms_data['mobile'] = $now_user['phone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('您在X1时，成功预约了X2，已成功生成订单，订单号：X3',array('X1'=>date("Y-m-d H:i:s"),'X2'=>$appoint['appoint_name'],'X3'=>$order_id));
					Sms::sendSms($sms_data);
				}
				if (C('config.sms_place_order') == 2 || C('config.sms_place_order') == 3) {
					$merchant = D('Merchant')->where(array('mer_id' => $appoint['mer_id']))->find();
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $merchant['phone'];
					$sms_data['sendto'] = 'merchant';
					// 处理 预约信息
                    $appoint_remark = L_('服务名称：').$appoint['appoint_name'];
                    if ($appoint['product_id']) {
                        if ($appoint['product_name']) {
                            $product_name = $appoint['product_name'];
                        } else {
                            $product_name = M('Appoint_product')->where(array('id' => $appoint['product_id']))->getField('name');
                        }
                        if ($product_name) {
                            $appoint_remark .= $product_name;
                        }
                    }
                    if ($appoint['cue_field']) {
                        $cue_field = unserialize($appoint['cue_field']);
                        if ($cue_field['address'] || $cue_field['value']) {
                            $appoint_remark .= ';预约地址：' . $cue_field['address'].$cue_field['value'];
                        }
                    }
                    if ($appoint['appoint_time']) {
                        $appoint_remark .= ';预约时间：'.$appoint['appoint_time'];
                    }

					$sms_data['content'] = L_('有份新的X1 被预约，订单号：X2请您注意查看并处理!',array('X1'=>$appoint_remark,'X2'=>$order_id));
					Sms::sendSms($sms_data);
				}


				$sms_data = array('mer_id' => $appoint['mer_id'], 'store_id' => $appoint['store_id'], 'type' => 'appoint');
				if((C('config.worker_sms_place_order') == 1) && !empty($merchant_workers_id)){
					$worker_where['merchant_worker_id'] = $merchant_workers_id;
					$now_worker = $database_merchant_workers->where($worker_where)->find();
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $now_worker['mobile'];
					$sms_data['sendto'] = 'worker';
					$sms_data['content'] = L_('用户在X1时，购买了预约服务，已生成订单，订单号：X2',array('X1'=>date('Y-m-d H:i',time()),'X2'=>$order_id));
					Sms::sendSms($sms_data);
				}

				$staff_where['store_id'] = $appoint['store_id'];
				$staff_arr = D('Merchant_store_staff')->where($staff_where)->field('tel,openid')->select();

				if($staff_arr && (C('config.sms_success_order') == 2 || C('config.sms_success_order') == 3)){
					foreach ($staff_arr as $v) {
						$sms_data = array('mer_id' => $appoint['mer_id'], 'store_id' => $appoint['store_id'], 'type' => 'appoint');
						$sms_data['uid'] = 0;
						$sms_data['mobile'] = $v['tel'];
						$sms_data['sendto'] = 'merchant';
						$sms_data['content'] = L_('有份新的预约订单，订单号：X1 请您注意查看并处理!',$order_id);
						Sms::sendSms($sms_data);

						if ($v['openid']) {
							$href = C('config.site_url') . '/wap.php?g=Wap&c=Storestaff&a=appoint_edit&order_id='.$order_id;
							$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
							$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $v['openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $order_id, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $appoint['appoint_name'], 'remark' => L_('您有新的预约，您注意查看并处理')));
						}
					}
				}
				// 处理推送给对应的预约工作人员
                if(!empty($merchant_workers_id)){
				    // 存在工作人员id 直接推送
                    $worker_where_info = array();
                    $worker_where_info['merchant_worker_id'] = $merchant_workers_id;
                    $now_worker = $database_merchant_workers->where($worker_where)->find();
                    if ($now_worker['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $first = L_('用户在X1时，购买了预约服务，请及时查看，订单号：X2',array('X1'=>date('Y-m-d H:i',time()),'X2'=>$order_id));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                        $model->sendTempMsg('TM00017',
                            array(
                                'href' => $href,
                                'wecha_id' => $now_worker['openid'],
                                'first' => $first,
                                'OrderSn' => $order_id,
                                'OrderStatus' => L_('支付成功'),
                                'remark' => '\n'.L_('请点击查看详细信息！')
                            )
                        );
                    }
                } else {
                    // 查询下商家店铺下所有工作人员
                    $worker_where_info = array();
                    $worker_where_info['mer_id'] = $appoint['mer_id'];
                    $worker_where_info['merchant_store_id'] = $appoint['store_id'];
                    $worker_where_info['status'] = 1;
                    $worker_where_info['openid'] = array('neq', '');
                    $workers = $database_merchant_workers->where($worker_where_info)->select();
                    if ($workers) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $first = L_('用户在X1时，购买了预约服务，请及时抢单，订单号：X2',array('X1'=>date('Y-m-d H:i',time()),'X2'=>$order_id));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=grab';
                        foreach ($workers as $val) {
                            $model->sendTempMsg('TM00017',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $val['openid'],
                                    'first' => $first,
                                    'OrderSn' => $order_id,
                                    'OrderStatus' => L_('支付成功'),
                                    'remark' => '\n'.L_('请点击查看详细信息！')
                                )
                            );
                        }
                    }
                    /*-----记录系统订单-------*/
                    //增加搜索关键词  店铺名称 和 商品名称
                    $store_info = M('Merchant_store')->field('name')->where(['store_id'=>$data_appoint_order['store_id']])->find();
                    $data_appoint_order['keywords'] = rtrim($store_info['name'].','.$appoint['appoint_name'], ',');

                }

                // 成功付款推送消息给 商家
                if ($data_appoint_order['paid'] && $appoint['mer_id']) {
				    // 获取对应商家user账号openid
                    $uid = D('Merchant')->where(array('mer_id' => $appoint['mer_id']))->getField('uid');
                    $merchant_user_openid = M('User')->where(array('uid' => $uid))->getField('openid');

                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    // 处理备注
                    $remark = L_('服务名称：').$appoint['appoint_name'];
                    if ($appoint['product_id'] > 0 && $data_appoint_order['product_price']) {
                        $remark .= ';服务全价：'.$data_appoint_order['product_price'];
                    } elseif ($appoint['appoint_price']) {
                        $remark .= ';服务全价：'.$appoint['appoint_price'];
                    }
                    if ($appoint['appoint_time']) {
                        $remark .= ';预约时间：'.$appoint['appoint_time'];
                    }
                    $model->sendTempMsg('TM00017',
                        array(
                            'wecha_id' => $merchant_user_openid,
                            'first' => L_('用户在X1时，购买了预约服务，成功支付，订单号：X1',array('X1'=>date('Y-m-d H:i',time()),'X2'=>$order_id)),
                            'OrderSn' => $order_id,
                            'OrderStatus' => L_('支付成功'),
                            'remark' => $remark
                        )
                    );
                }


				//小票打印start
				if(!$appoint['payment_money']){
				    $printHaddle = new PrintHaddle();
				    $printHaddle->printit($order_id, 'appoint_order', 1);
				}
				//小票打印end

				return array('error'=>0,'msg'=>L_('订单产生成功！'),'order_id'=>$order_id);
			}else{
				return array('error'=>1,'msg'=>L_('订单产生失败！请重试'));
			}
		}
	}

	//平台自营下单
	public function platform_save_post_form($data){
		fdump($data,'api/log/'.date('Ymd').'/appoint_order',true);

		$database_appoint_visit_order_info = D('Appoint_visit_order_info');
		$database_appoint_category = D('Appoint_category');
		$data['order_time'] = $_SERVER['REQUEST_TIME'];
		$data['payment_money'] = 0;
		$data['real_orderid']=date('Ymdhis') . substr(microtime(), 2, 8 - strlen($this->user_session['uid'])) . $this->user_session['uid'];
		$insert_id = $this->data($data)->add();
		if($insert_id){
			$appoint_visit_order_info['appoint_order_id'] = $insert_id;
			$appoint_visit_order_info['merchant_worker_id'] =  0 ;
			$appoint_visit_order_info['uid'] = $data['uid'];
			$appoint_visit_order_info['add_time'] = time();
			$appoint_visit_order_info['type'] = 2;
			$appoint_visit_order_info['service_address'] = $data['cue_field'] ? $data['cue_field'] : '';
			$appoint_visit_order_info_insert_id = $database_appoint_visit_order_info->data($appoint_visit_order_info)->add();
			if(!$appoint_visit_order_info_insert_id){
				return array('error'=>1,'msg'=>L_('预约订单产生失败！'));
			}
            fdump_api(['添加系统订单列表>>'.__LINE__,$data, $insert_id],'appoint/appoint_order',true);
            if (!isset($data['mer_id'])) {
                $data['mer_id'] = 0;
            }
            D('System_order')->add_order('appoint',$data,$insert_id);
            if (floatval($data['appoint_price'])==0 && (!$data['product_price'] || floatval($data['product_price'])==0)) {
                D('System_order')->reg_group_pay_order($insert_id,$data, 'appoint');
                /*----系统订单信息----*/
                D('System_order')->save_reg_order_info('appoint',$insert_id,$data);
            }

			$cat_info = $database_appoint_category->get_category_by_id($data['cat_id']);
			$sms_key = C('config.sms_key');
			if(isset($sms_key) && !empty($sms_key)){
				if ($_SESSION['user']['openid']) {
					$href = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$insert_id;
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $_SESSION['user']['openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $insert_id, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $cat_info['cat_name'], 'remark' => L_('您的该次预约下单成功，感谢您的使用！')));
				}

				$database_user = D('User');
				$now_user = $database_user->get_user($data['uid']);
				$sms_data = array('mer_id' => 0, 'store_id' => 0, 'type' => 'appoint');
				$sms_data['uid'] = $now_user['uid'];
				$sms_data['mobile'] = $now_user['phone'];
				$sms_data['sendto'] = 'user';
				$sms_data['content'] = L_('您在X1时，成功预约了X2，已成功生成订单，订单号：X3',array('X1'=>date("Y-m-d H:i:s"),'X2'=>$cat_info['cat_name'],'X3'=>$insert_id));
				Sms::sendSms($sms_data);
			}
			$recordContent['content'] = [[
				'title' => '下单时间',
				'value' => date('Y-m-d H:i:s'),
				'type' => 'text'
			]];
			D('Appoint_order_records')->addOrderRecord(0, $insert_id, '用户下单', $recordContent);
			return array('status'=>1,'msg'=>L_('订单产生成功！'),'order_id'=>$insert_id);
		}else{
			return array('status'=>0,'msg'=>L_('订单产生失败！请重试'));
		}
	}

	public function get_order_by_id($uid,$order_id){
		$condition_group_order['order_id'] = $order_id;
		$condition_group_order['uid'] = $uid;
		$order = $this->field(true)->where($condition_group_order)->find();
		return $order;
	}

	public function get_order_by_prepare_by_id($order_id){
		$condition_group_order['order_id'] = $order_id;

		$order = $this->field(true)->where($condition_group_order)->find();
		return $order;
	}

    public function get_pay_order($uid, $order_id, $is_web = false)
    {
        $now_order = $this->get_order_by_id($uid, $order_id);
        if (empty($now_order)) {
            return array('error' => 1, 'msg' => L_('当前订单不存在！'));
        }

        if (!empty($now_order['paid'])) {
            if ($is_web) {
                return array('error' => 1, 'msg' => L_('您已经支付过此订单！'), 'url' => U('User/Index/appoint_order_view', array('order_id' => $now_order['order_id'])));
            } else {
                return array('error' => 1, 'msg' => L_('您已经支付过此订单！'), 'url' => U('My/appoint_order', array('order_id' => $now_order['order_id'])));
            }
        }

		$now_group = D('Appoint')->get_appoint_info($now_order['appoint_id']);
		if(empty($now_group)){
			return array('error'=>1,'msg'=>L_('当前预约不存在或已过期！'));
		}
		$imgs =explode(';', $now_group['pic']);
		foreach($imgs as &$v){
			$v = preg_replace('/,/','/',$v);
		}

		if($is_web){
			if($now_order['product_id']) {
				$order_info = array(
						'order_id'			=>	$now_order['order_id'],
						'mer_id'			=>	$now_order['mer_id'],
						'store_id'			=>	$now_order['store_id'],
						'order_type'		=>	'appoint',
                        'product_id'        => $now_order['product_id'],
						'order_name'		=>	$now_order['product_name'] ? $now_group['appoint_name'] . ' - ' . $now_order['product_name'] : $now_group['appoint_name'],
						'order_num'			=>	$now_order['num'] ? $now_order['num'] : 0,
						'order_total_money'	=>	$now_order['product_payment_price'] ? floatval($now_order['product_payment_price']) : floatval($now_order['payment_money']),
						'order_content'    =>  array(
								0=>array(
										'name'  		=> $now_group['merchant_name'].L_('：').($now_order['product_name'] ? $now_group['appoint_name'] . ' - ' . $now_order['product_name'] : $now_group['appoint_name']),
										'num'   		=> 1,
										'price' 		=> $now_order['product_payment_price'] ? floatval($now_order['product_payment_price']) : floatval($now_order['payment_money']),
										'money' 	=> $now_order['product_payment_price'] ? floatval($now_order['product_payment_price']) : floatval($now_order['payment_money']),
								)
						),
						'extra_price' =>$now_order['extra_price'],
						'extra_pay_price' =>$now_group['extra_pay_price'],
				);
			}else{
				$order_info = array(
						'order_id'			=>	$now_order['order_id'],
						'mer_id'			=>	$now_order['mer_id'],
						'store_id'			=>	$now_order['store_id'],
						'order_type'		=>	'appoint',
						'order_name'		=>	$now_group['appoint_name'],
						'order_num'			=>	$now_order['num'],
						'order_total_money'	=>	floatval($now_order['payment_money']),
						'order_content'    =>  array(
								0=>array(
										'name'  		=> $now_group['merchant_name'].L_('：').$now_group['appoint_name'],
										'num'   		=> 1,
										'price' 		=> floatval($now_order['payment_money']),
										'money' 	=> floatval($now_order['payment_money']),
								)
						),
						'extra_price' =>$now_order['extra_price'],
						'extra_pay_price' =>$now_group['extra_pay_price'],
				);
			}
		}else{
			if($now_order['product_id']) {
				$order_info = array(
						'order_id' => $now_order['order_id'],
						'appoint_id' => $now_order['appoint_id'],
						'mer_id' => $now_order['mer_id'],
						'store_id'			=>	$now_order['store_id'],
						'order_type' => 'appoint',
                        'product_id'        => $now_order['product_id'],
						'order_name' => $now_order['product_name'] ? $now_group['appoint_name'] . ' - ' . $now_order['product_name'] : $now_group['appoint_name'],
						'order_num' => $now_order['num'] ? $now_order['num'] : 0,
						'order_price' => $now_order['product_payment_price'] ? floatval($now_order['product_payment_price']) : floatval($now_order['payment_money']),
						'order_total_money' => $now_order['product_payment_price'] ? floatval($now_order['product_payment_price']) : floatval($now_order['payment_price']),
						'img' => file_domain() . '/upload/appoint/' . $imgs[0],
						'discount_status' => false,
						'extra_price' =>$now_order['extra_price'],
						'extra_pay_price' =>$now_group['extra_pay_price'],
				);
			}else{
				$order_info = array(
						'order_id' => $now_order['order_id'],
						'appoint_id' => $now_order['appoint_id'],
						'mer_id' => $now_order['mer_id'],
						'store_id'			=>	$now_order['store_id'],
						'order_type' => 'appoint',
						'order_name' => $now_order['product_name'] ? $now_group['appoint_name'] . ' - ' . $now_order['product_name'] : $now_group['appoint_name'],
						'order_num' => $now_order['num'] ? $now_order['num'] : 0,
						'order_price' => $now_order['payment_money'],
						'order_total_money' =>  $now_order['payment_money'],
						'img' => file_domain() . '/upload/appoint/' . $imgs[0],
						'discount_status' => false,
						'extra_price' =>$now_order['extra_price'],
						'extra_pay_price' =>$now_group['extra_pay_price'],
				);
			}
		}
		$order_info['pay_merchant_coupon'] = false;
		$order_info['pay_system_coupon'] = false;
        
        return array('error' => 0, 'order_info' => $order_info);
    }


	public function get_pay_balace_order($uid,$order_id,$is_web=false){
		$now_order = $this->get_order_by_id($uid,$order_id);
		if(empty($now_order)){
			$now_order = $this->get_order_by_prepare_by_id($order_id);
			if(empty($now_order)){
				return array('error'=>1,'msg'=>L_('当前订单不存在！'));
			}else{
              $now_order['is_prepare'] = 1;//代付
			}
		}else{
			$now_order['is_prepare'] = 2;//自己支付
		}

		if(($now_order['paid'] != 1) && ($now_order['payment_status']==1)){
			return array('error'=>1,'msg'=>L_('当前订单未支付！'),'url'=>U('My/appoint_order',array('order_id'=>$now_order['order_id'])));
		}

		if(($now_order['paid'] == 1) && ($now_order['service_status'] == 1) && ($now_order['is_initiative'] == 1)){
			if($now_order['is_prepare'] == 2){
				return array('error'=>1,'msg'=>L_('当前订单已支付完成！'),'url'=>U('My/appoint_order',array('order_id'=>$now_order['order_id'])));
			}else{
				return array('error'=>1,'msg'=>L_('当前订单已支付完成！'),'url'=>U('Wap/Home/index'));
			}

		}


		$now_group = D('Appoint')->get_appoint_info($now_order['appoint_id']);
		if(empty($now_group)){
			return array('error'=>1,'msg'=>L_('当前预约不存在或已过期！'));
		}
		$imgs =explode(';', $now_group['pic']);
		foreach($imgs as &$v){
			$v = preg_replace('/,/','/',$v);
		}

		if($is_web){
			$order_info = array(
					'order_id'			=>	$now_order['order_id'],
					'mer_id'			=>	$now_order['mer_id'],
                    'store_id'			=>	$now_order['store_id'],
					'order_type'		=>	'balance-appoint',
					'order_name'		=>	$now_group['appoint_name'],
					'order_num'			=>	$now_order['num'],
					'order_total_money'	=>	$now_order['product_price'] - $now_order['product_payment_price'],
					'order_content'    =>  array(
							0=>array(
									'name'  		=> $now_group['merchant_name'].L_('：').$now_group['appoint_name'],
									'num'   		=> 1,
									'price' 		=> floatval($now_order['payment_money']),
									'money' 	=> floatval($now_order['payment_money']),
							)
					),
					'extra_price' =>$now_order['extra_price'],
					'extra_pay_price' =>$now_group['extra_pay_price'],
			);
		}else{
			if($now_order['product_id']){
				if(($now_order['type'] == 2) || ($now_order['payment_status'] == 0)){
                    $order_price = $now_order['product_price'];
                }else{
					$order_price = $now_order['product_price'] - $now_order['product_payment_price'];
				}
				$order_info = array(
						'order_id'			=>	$now_order['order_id'],
						'appoint_id'		=>	$now_order['appoint_id'],
						'mer_id'			=>	$now_order['mer_id'],
                        'store_id'			=>	$now_order['store_id'],
						'order_type'		=>	'balance-appoint',
						'order_name'		=>	$now_order['product_name'] ? $now_group['appoint_name'] . ' - ' . $now_order['product_name'] : $now_group['appoint_name'],
						'order_num'			=>	$now_order['num'] ? $now_order['num'] : 0,
						'order_price'		=>	$order_price,
						'order_total_money'	=>	$order_price,
						'img'				=> file_domain().'/upload/appoint/'.$imgs[0],
						'extra_price' =>$now_order['extra_price'],
						'extra_pay_price' =>$now_group['extra_pay_price'],
				);
			}else{
				if($now_order['payment_status'] == 0){
					$order_price = $now_order['appoint_price'];
				}else{
					$order_price = $now_order['appoint_price'] - $now_order['payment_money'];
				}

				if($now_order['product_price']>0){
					$order_price = $now_order['product_price'] - $now_order['payment_money'];
				}

				$order_info = array(
						'order_id'			=>	$now_order['order_id'],
						'appoint_id'		=>	$now_order['appoint_id'],
						'mer_id'			=>	$now_order['mer_id'],
                        'store_id'			=>	$now_order['store_id'],
						'order_type'		=>	'balance-appoint',
						'order_name'		=>	$now_order['product_name'] ? $now_group['appoint_name'] . ' - ' . $now_order['product_name'] : $now_group['appoint_name'],
						'order_num'			=>	$now_order['num'] ? $now_order['num'] : 0,
						'order_price'		=>	$order_price,
						'order_total_money'	=>	$order_price,
						'img'				=> file_domain().'/upload/appoint/'.$imgs[0],
						'extra_price' =>$now_order['extra_price'],
						'extra_pay_price' =>$now_group['extra_pay_price'],
					    'is_prepare'=>$now_order['is_prepare'],
				);
			}
		}
		return array('error'=>0,'order_info'=>$order_info);

	}

	// 已经约满的时间点
	public function get_appoint_num($appoint_id,$sum=1){
		$time = date('Y-m-d');
		$sql = "SELECT count(*) as appointNum,appoint_date,appoint_time from pigcms_appoint_order
				where appoint_date >='".$time."' AND paid NOT IN (2,3) AND service_status=0 AND is_del=0 AND appoint_id = ".$appoint_id."
				group by appoint_date,appoint_time
				having appointNum>=".$sum;
		$result = D('')->query($sql);
		return $result;
	}

	//计算预约人数
	public function get_appoint_remain_num($appoint_id){
		$time = date('Y-m-d');
		$sql = "SELECT count(*) as appointNum,appoint_date,appoint_time from pigcms_appoint_order
				where appoint_date >='".$time."' AND paid NOT IN (2,3) AND service_status=0 AND is_del=0 AND merchant_worker_id = 0 AND appoint_id = ".$appoint_id."
				group by appoint_date,appoint_time";
		$result = D('')->query($sql);
		return $result;
	}

	//工作人员约满时间点
	public function get_worker_appoint_num($appoint_id , $merchant_worker_id){
		$database_appoint_visit_order_info = D('Appoint_visit_order_info');
		$where['merchant_worker_id'] = $merchant_worker_id;
		$appoint_order_id_arr = $database_appoint_visit_order_info->where($where)->getField('id,appoint_order_id');
		$result = array();
		if($appoint_order_id_arr){
			$time = date('Y-m-d');

			if($appoint_id){
				$sql = "SELECT count(*) as appointNum,appoint_date,appoint_time from pigcms_appoint_order
				where type = 0 AND appoint_date >='".$time."' AND paid NOT IN (2,3) AND service_status=0 AND is_del=0 AND order_id IN (".implode(',',$appoint_order_id_arr).")  AND appoint_id = ".$appoint_id."
				group by appoint_date,appoint_time";
			}else{
				$sql = "SELECT count(*) as appointNum,appoint_date,appoint_time from pigcms_appoint_order
				where type = 0 AND appoint_date >='".$time."' AND paid NOT IN (2,3) AND service_status=0 AND is_del=0 AND order_id IN (".implode(',',$appoint_order_id_arr).")
				group by appoint_date,appoint_time";
			}

			$result = D('')->query($sql);

		}

		return $result;
	}

	/*获得订单链接*/
	public function get_order_url($order_id,$is_wap=false){
		if($is_wap){
			return U('My/appoint_order',array('order_id'=>$order_id));
		}else{
			return U('User/Index/appoint_order_view',array('order_id'=>$order_id));
		}
	}
	public function wap_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$ecard_info=null){
		//去除微信优惠的金额
		$pay_money = $order_info['order_total_money'];

		if(C('config.open_extra_price')==1){
			$user_score_use_percent=(float)C('config.user_score_use_percent');
			$order_info['order_extra_price'] = bcdiv($order_info['extra_price'],$user_score_use_percent,2);
			$pay_money +=$order_info['order_extra_price'];
		}
		if($pay_money == 0){
			return $this->wap_after_pay_before($order_info);
		}
		if($merchant_balance['card_discount']>0){
			$pay_money = sprintf("%.2f",$pay_money*$merchant_balance['card_discount']/10);
			//$pay_money = floor($pay_money*$merchant_balance['card_discount']*10)/100;
		}
		$merchant_balance['card_money'] = round($merchant_balance['card_money'] * 100) / 100;			//用户在商家所拥有的金额
		$merchant_balance['card_give_money'] = round($merchant_balance['card_give_money'] * 100) / 100;			//用户在商家所拥有的金额
		$data_group_order['pay_money'] = $pay_money;
		//判断优惠券

		//商家优惠券
		if($now_coupon['card_price']>0) {
			$data_group_order['card_id'] = $now_coupon['merc_id'];
			$data_group_order['card_price'] = $now_coupon['card_price'];
			if ($now_coupon['card_price'] >= $pay_money) {
				$order_result = $this->wap_pay_save_order($order_info, $data_group_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $now_coupon['card_price'];
			$data_group_order['pay_money'] = $pay_money;
		}

		//系统优惠券
		if($now_coupon['coupon_price']>0){
			$data_group_order['coupon_id'] = $now_coupon['sysc_id'];
			$data_group_order['coupon_price'] = $now_coupon['coupon_price'];
			if ($now_coupon['coupon_price'] >= $pay_money) {
				$order_result = $this->wap_pay_save_order($order_info, $data_group_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $now_coupon['coupon_price'];
			$data_group_order['pay_money'] = $pay_money;
		}
		//使用平台E卡
		if(!empty($ecard_info) && $ecard_info['now_price']>0){
			$data_group_order['ecard_password'] = $ecard_info['card_password'];
			$data_group_order['use_ecard_price'] = $ecard_info['now_price'];
			if($ecard_info['now_price']>=$pay_money){
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money-=$ecard_info['now_price'];
			$data_group_order['pay_money'] = $pay_money;			
		}else{
			$data_group_order['ecard_password'] ='';
			$data_group_order['use_ecard_price'] =0;
		}
		
		$data_group_order['card_discount'] = $merchant_balance['card_discount'];
		
		if(!empty($order_info['score_deducte'])&&$order_info['use_score']){
			$data_group_order['score_used_count']  = $order_info['score_used_count'];
			$data_group_order['score_deducte']     = (float)$order_info['score_deducte'];
			if($order_info['score_deducte'] >= $pay_money){
				$data_group_order['score_deducte'] = $pay_money;
				//扣除积分
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $order_info['score_deducte'];
			$data_group_order['pay_money'] = $pay_money;
			//$data_group_order['system_pay'] += $data_group_order['score_deducte'];
		}

		//判断商家余额
		if(!empty($merchant_balance['card_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_money'] >= $pay_money){
				$data_group_order['merchant_balance'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_group_order['merchant_balance'] = $merchant_balance['card_money'];
			}
			$pay_money -= $merchant_balance['card_money'];
		}

		if(!empty($merchant_balance['card_give_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_give_money'] >= $pay_money){
				$data_group_order['card_give_money'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_group_order['card_give_money'] = $merchant_balance['card_give_money'];
			}
			$pay_money -= $merchant_balance['card_give_money'];
		}

		//判断帐户余额
		if(!empty($now_user['now_money'])&&$order_info['use_balance']){
			if($now_user['now_money'] >= $pay_money){
				$data_group_order['balance_pay'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_group_order['balance_pay'] = $now_user['now_money'];
			}
			$pay_money -= $now_user['now_money'];
		}
        //================================一卡通支付金额================================
        if(!empty($_POST['internal_card_ids'])){
            if(is_string($_POST['internal_card_ids'])){//兼容下字符串
                $_POST['internal_card_ids'] = explode(',', $_POST['internal_card_ids']);
            }
            try {
                $cardPayInfo = D('InternalMembershipCard')->getInternalCardPay(
                    $_POST['internal_card_ids'],
                    $pay_money,
                    $order_info['store_id'],
                    $now_user['uid']
                );
                //校验卡余额
                $pay_money = $cardPayInfo['pay_money'];
                $save_data['internal_card_pay'] = $cardPayInfo['internal_card_pay'];
                M('Appoint_order')->where(array('order_id' => $_POST['order_id']))->save($save_data);
            } catch (\Exception $e) {
                return array('error_code' => true, 'msg' => $e->getMessage());
            }
        }
        if($pay_money < 0){
            $pay_money = 0;
        }
        //================================一卡通支付金额结束================================

        $data_group_order['pay_money'] = $pay_money;
        if($pay_money == 0){
            $this->wap_pay_save_order($order_info, $data_group_order);
            return $this->wap_after_pay_before($order_info);
        }
		//在线支付

		$order_result = $this->wap_pay_save_order($order_info,$data_group_order);
		if($order_result['error_code']){
			return $order_result;
		}

		return array('error_code'=>false,'pay_money'=>$pay_money);
	}


	//电脑站支付前订单处理
	public function web_befor_pay($order_info,$now_user){
		//判断是否需要在线支付
		if(!$order_info['use_balance']){
			$now_user['now_money']=0;
		}
		if($now_user['now_money']+$order_info['score_deducte']< $order_info['order_total_money']){
			$online_pay = true;
		}else{
			$online_pay = false;
		}
		//不使用在线支付，直接使用用户余额。
		if(empty($online_pay)){
			$order_pay['balance_pay'] = $order_info['order_total_money']-$order_info['score_deducte'];
		}else{
			if(!empty($now_user['now_money'])){
				$order_pay['balance_pay'] = $now_user['now_money'];
			}
		}

		if(!empty($order_info['score_deducte'])){
			$data_group_order['score_used_count']	= $order_info['score_used_count'];
			$data_group_order['score_deducte']      = (float)$order_info['score_deducte'];
		}

		//将已支付用户余额等信息记录到订单信息里
		if(!empty($order_pay['balance_pay'])){
			$data_group_order['balance_pay'] = $order_pay['balance_pay'];
		}
		if(!empty($data_group_order)){
//			$data_group_order['wx_cheap'] 			= 0;
			$data_group_order['card_id'] 			= 0;
			$data_group_order['merchant_balance'] 	= 0;
			$data_group_order['pay_money'] 	= 0;
			$condition_group_order['order_id'] = $order_info['order_id'];
			if(!$this->where($condition_group_order)->data($data_group_order)->save()){
				return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}
		}

		if($online_pay){
			return array('error_code'=>false,'pay_money'=>$order_info['order_total_money'] - $now_user['now_money']-(float)$order_info['score_deducte']);
		}else{
			$order_param = array(
					'order_id' => $order_info['order_id'],
					'pay_type' => '',
					'third_id' => '',
					'is_mobile' => 0,
			);
			$result_after_pay = $this->after_pay($order_param);
			if($result_after_pay['error']){
				return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
			}
			return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>U('User/Index/appoint_order_view',array('order_id'=>$order_info['order_id'])));
		}
	}
	//手机端支付前订单处理



	//手机端余额支付
	public function wap_balace_befor_pay($order_info,$now_coupon,$merchant_balance,$now_user,$ecard_info=null)
    {
		//去除微信优惠的金额
		$pay_money = $order_info['order_total_money'];
        empty(floatval($merchant_balance['card_discount'])) && $merchant_balance['card_discount'] = 10;
		$pay_money = sprintf("%.2f",$pay_money*$merchant_balance['card_discount']/10);
		$merchant_balance['card_money'] = round($merchant_balance['card_money'] * 100) / 100;			//用户在商家所拥有的金额
		$merchant_balance['card_give_money'] = round($merchant_balance['card_give_money'] * 100) / 100;			//用户在商家所拥有的金额
		$data_group_order['user_pay_money'] = $pay_money;
		$data_group_order['product_card_discount'] = $merchant_balance['card_discount'];
		if(C('config.open_extra_price')==1){
			$user_score_use_percent=(float)C('config.user_score_use_percent');
			$order_info['order_extra_price'] = bcdiv($order_info['extra_price'],$user_score_use_percent,2);
			$pay_money +=$order_info['order_extra_price'];
		}
		//判断优惠券

		//商家优惠券
		if($now_coupon['card_price']>0) {
			$data_group_order['product_card_id'] = $now_coupon['merc_id'];
			$data_group_order['product_card_price'] = $now_coupon['card_price'];
			if ($now_coupon['card_price'] >= $pay_money) {
				$order_result = $this->wap_pay_balace_save_order($order_info, $data_group_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info,true);
			}
			$pay_money -= $now_coupon['card_price'];
			$data_group_order['user_pay_money'] = $pay_money;
		}

		//系统优惠券
		if($now_coupon['coupon_price']>0){
			$data_group_order['product_coupon_id'] = $now_coupon['sysc_id'];
			$data_group_order['product_coupon_price'] = $now_coupon['coupon_price'];
			if ($now_coupon['coupon_price'] >= $pay_money) {
				$order_result = $this->wap_pay_balace_save_order($order_info, $data_group_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info,true);
			}
			$pay_money -= $now_coupon['coupon_price'];
			$data_group_order['user_pay_money'] = $pay_money;
		}

		//使用平台E卡
		if(!empty($ecard_info) && $ecard_info['now_price']>0){
			$data_group_order['ecard_password'] = $ecard_info['card_password'];
			$data_group_order['use_ecard_price'] = $ecard_info['now_price'];
			if($ecard_info['now_price']>=$pay_money){
				$order_result = $this->wap_pay_balace_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info,true);
			}
			$pay_money-=$ecard_info['now_price'];
			$data_group_order['user_pay_money'] = $pay_money;
		}else{
			$data_group_order['ecard_password'] ='';
			$data_group_order['use_ecard_price'] =0;
		}
		
		if(!empty($order_info['score_deducte'])&&$order_info['use_score']){
			$data_group_order['product_score_used_count']  = $order_info['score_used_count'];
			$data_group_order['product_score_deducte']     = (float)$order_info['score_deducte'];
			if($order_info['score_deducte'] >= $pay_money){
                $data_group_order['product_score_deducte'] = $pay_money;
				//扣除积分
				$order_result = $this->wap_pay_balace_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info,true);
			}
			$pay_money -= $order_info['score_deducte'];
			$data_group_order['user_pay_money'] = $pay_money;
		}

		//判断商家余额
		if(!empty($merchant_balance['card_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_money'] >= $pay_money){
				$data_group_order['product_merchant_balance'] = $pay_money;
				$order_result = $this->wap_pay_balace_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info,true);
			}else{
				$data_group_order['product_merchant_balance'] = $merchant_balance['card_money'];
			}
			$pay_money -= $merchant_balance['card_money'];
		}

		if(!empty($merchant_balance['card_give_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_give_money'] >= $pay_money){
				$data_group_order['product_card_give_money'] = $pay_money;
				$order_result = $this->wap_pay_balace_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info,true);
			}else{
				$data_group_order['product_card_give_money'] = $merchant_balance['card_give_money'];
			}
			$pay_money -= $merchant_balance['card_give_money'];
		}
        fdump_api([$pay_money, $order_info, $now_user], 'appoint/wap_balace_befor_pay', 1);
		//判断帐户余额
		if(!empty($now_user['now_money'])&&$order_info['use_balance']){
			if($now_user['now_money'] >= $pay_money){
				$data_group_order['product_balance_pay'] = $pay_money;
				$order_result = $this->wap_pay_balace_save_order($order_info,$data_group_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info,true);
			}else{
				$data_group_order['product_balance_pay'] = $now_user['now_money'];
			}
			$pay_money -= $now_user['now_money'];
		}
		$data_group_order['user_pay_money'] = $pay_money;
		$order_result = $this->wap_pay_balace_save_order($order_info,$data_group_order);
		if($order_result['error_code']){
			return $order_result;
		}
        //================================一卡通支付金额================================
        if(!empty($_POST['internal_card_ids'])){
            if(is_string($_POST['internal_card_ids'])){//兼容下字符串
                $_POST['internal_card_ids'] = explode(',', $_POST['internal_card_ids']);
            }
            try {
                $cardPayInfo = D('InternalMembershipCard')->getInternalCardPay(
                    $_POST['internal_card_ids'],
                    $pay_money,
                    $order_info['store_id'],
                    $now_user['uid']
                );
                //校验卡余额
                $pay_money = $cardPayInfo['pay_money'];
                $save_data['internal_card_pay'] = $cardPayInfo['internal_card_pay'];
                M('Appoint_order')->where(array('order_id' => $_POST['order_id']))->save($save_data);
            } catch (\Exception $e) {
                return array('error_code' => true, 'msg' => $e->getMessage());
            }
        }
        $data_group_order['user_pay_money'] = $pay_money;
        $order_result = $this->wap_pay_balace_save_order($order_info,$data_group_order);
        if($order_result['error_code']){
            return $order_result;
        }
        if(empty($pay_money)){
            return $this->wap_after_pay_before($order_info,true);
        }
        //================================一卡通支付金额结束================================

		return array('error_code'=>false,'pay_money'=>$pay_money);
	}

	//手机端支付前保存各种支付信息
	public function wap_pay_save_order($order_info,$data_group_order){
		fdump($order_info,'api/log/'.date('Ymd').'/appoint_order',true);
		fdump($data_group_order,'api/log/'.date('Ymd').'/appoint_order',true);
		$data_group_order['coupon_id'] 			= !empty($data_group_order['coupon_id']) ? $data_group_order['coupon_id'] : 0;
		$data_group_order['coupon_price'] 			= !empty($data_group_order['coupon_price']) ? $data_group_order['coupon_price'] : 0;
		$data_group_order['card_id'] 			= !empty($data_group_order['card_id']) ? $data_group_order['card_id'] : 0;
		$data_group_order['card_price'] 			= !empty($data_group_order['card_price']) ? $data_group_order['card_price'] : 0;
		$data_group_order['merchant_balance'] 	= !empty($data_group_order['merchant_balance']) ? $data_group_order['merchant_balance'] : 0;
		$data_group_order['card_give_money'] 	= !empty($data_group_order['card_give_money']) ? $data_group_order['card_give_money'] : 0;
		$data_group_order['card_discount'] 	= !empty($data_group_order['card_discount']) ? $data_group_order['card_discount'] : 0;
		$data_group_order['balance_pay']	 	= !empty($data_group_order['balance_pay']) ? $data_group_order['balance_pay'] : 0;
		$data_group_order['score_used_count']  = !empty($data_group_order['score_used_count']) ? $data_group_order['score_used_count'] : 0;
		$data_group_order['score_deducte']     = !empty($data_group_order['score_deducte']) ? $data_group_order['score_deducte'] : 0;
		$data_group_order['pay_money']	 	    = !empty($data_group_order['pay_money']) ? (float)$data_group_order['pay_money'] : 0;
		$data_group_order['product_real_payment_price']	 	    = !empty($data_group_order['product_real_payment_price']) ? (float)$data_group_order['product_real_payment_price'] : 0;
		$data_group_order['score_discount_type']      = !empty($order_info['score_discount_type']) ? $order_info['score_discount_type'] : 0;
		$data_group_order['submit_order_time'] = $_SERVER['REQUEST_TIME'];
		$condition_group_order['order_id'] = $order_info['order_id'];

		if($this->where($condition_group_order)->data($data_group_order)->save()){
            /*---更新系统订单信息 19/1/24--*/
            D('System_order')->reg_group_pay_order($order_info['order_id'],$data_group_order, 'appoint');
			return array('error_code'=>false,'msg'=>L_('保存订单成功！'));
		}else{
			return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
		}
	}


	public function wap_pay_balace_save_order($order_info,$data_group_order){
		fdump($order_info,'api/log/'.date('Ymd').'/appoint_order',true);
		fdump($data_group_order,'api/log/'.date('Ymd').'/appoint_order',true);
		$data_group_order['product_coupon_id'] 			= !empty($data_group_order['product_coupon_id']) ? $data_group_order['product_coupon_id'] : 0;
		$data_group_order['product_coupon_price'] 			= !empty($data_group_order['product_coupon_price']) ? $data_group_order['product_coupon_price'] : 0;
		$data_group_order['product_card_id'] 			= !empty($data_group_order['product_card_id']) ? $data_group_order['product_card_id'] : 0;
		$data_group_order['product_card_price'] 			= !empty($data_group_order['product_card_price']) ? $data_group_order['product_card_price'] : 0;
		$data_group_order['product_merchant_balance'] 	= !empty($data_group_order['product_merchant_balance']) ? $data_group_order['product_merchant_balance'] : 0;
		$data_group_order['product_card_give_money'] 	= !empty($data_group_order['product_card_give_money']) ? $data_group_order['product_card_give_money'] : 0;
		$data_group_order['product_card_discount'] 	= !empty($data_group_order['product_card_discount']) ? $data_group_order['product_card_discount'] : 0;
		$data_group_order['product_balance_pay']	 	= !empty($data_group_order['product_balance_pay']) ? $data_group_order['product_balance_pay'] : 0;
		$data_group_order['product_score_used_count']  = !empty($data_group_order['product_score_used_count']) ? $data_group_order['product_score_used_count'] : 0;
		$data_group_order['product_score_deducte']     = !empty($data_group_order['product_score_deducte']) ? $data_group_order['product_score_deducte'] : 0;
		$data_group_order['user_pay_money']	 	    = !empty($data_group_order['user_pay_money']) ? (float)$data_group_order['user_pay_money'] : 0;
		$data_group_order['product_real_balace_price']	 	    = !empty($data_group_order['product_real_balace_price']) ? (float)$data_group_order['product_real_balace_price'] : 0;
		$data_group_order['score_discount_type']      = !empty($order_info['score_discount_type']) ? $order_info['score_discount_type'] : 0;
		$data_group_order['user_pay_time'] = $_SERVER['REQUEST_TIME'];

		$condition_group_order['order_id'] = $order_info['order_id'];
		if($this->where($condition_group_order)->data($data_group_order)->save() !== false){
			return array('error_code'=>false,'msg'=>L_('保存订单成功！'));
		}else{
			return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
		}
	}

	//如果无需调用在线支付，使用此方法即可。
	public function wap_after_pay_before($order_info , $is_balace = false){
		$order_param = array(
				'order_id' => $order_info['order_id'],
				'pay_type' => '',
				'third_id' => '',
				'is_mobile' => $order_info['is_mobile'],
		);

		if($is_balace){
			$result_after_pay = $this->balance_after_pay($order_param);
		}else{
			$result_after_pay = $this->after_pay($order_param);
		}

		if($result_after_pay['error']){
			return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
		}
		return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>str_replace('/source/','/',U('My/appoint_order',array('order_id'=>$order_info['order_id']))));
	}
	//支付前订单处理
	public function befor_pay($order_info,$now_coupon,$now_user){
		//判断是否需要在线支付
		if($now_coupon['price']+$now_user['now_money'] < $order_info['order_total_money']){
			$online_pay = true;
		}else{
			$online_pay = false;
		}
		//不使用在线支付，直接使用会员卡和用户余额。
		if(empty($online_pay)){
			if(!empty($now_coupon)){
				$coupon_pay_result = D('Member_card_coupon')->user_card($now_coupon['record_id'],$order_info['mer_id'],$now_user['uid']);
				if($coupon_pay_result['error_code']){
					return $coupon_pay_result;
				}
				$order_pay['car_id'] = $now_coupon['record_id'];
			}
			if(!empty($now_user['now_money']) && $now_coupon['price'] < $order_info['order_total_money']){
				$money_pay_result = D('User')->user_money($now_user['uid'],$order_info['order_total_money']-$now_coupon['price']);
				if($money_pay_result['error_code']){
					return $money_pay_result;
				}
				$order_pay['balance_pay'] = $now_user['now_money'];
			}
		}else{
			//校验会员卡
			if(!empty($now_coupon)){
				$coupon_pay_result = D('Member_card_coupon')->check_card($now_coupon['record_id'],$order_info['mer_id'],$now_user['uid']);
				if($coupon_pay_result['error_code']){
					return $coupon_pay_result;
				}
				$order_pay['car_id'] = $now_coupon['record_id'];
			}
			if(!empty($now_user['now_money'])){
				$order_pay['balance_pay'] = $now_user['now_money'];
			}
		}

		//将会员卡ID，已支付用户余额等信息记录到订单信息里
		if(!empty($order_pay['car_id'])){
			$data_group_order['card_id'] = $order_pay['record_id'];
		}
		if(!empty($order_pay['balance_pay'])){
			$data_group_order['balance_pay'] = $order_pay['balance_pay'];
		}
		if(!empty($data_group_order)){
			$condition_group_order['order_id'] = $order_info['order_id'];
			if(!$this->where($condition_group_order)->data($data_group_order)->save()){
				return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}
            /*---更新系统订单信息 19/1/21--*/
            D('System_order')->reg_group_pay_order($order_info['order_id'],$data_group_order,'appoint');
		}

		if($online_pay){
			return array('error_code'=>false,'pay_money'=>$order_info['order_total_money'] - $now_coupon['price'] - $now_user['now_money']);
		}else{
			$order_param = array(
					'order_id' => $order_info['order_id'],
					'pay_type' => '',
					'third_id' => '',
					'is_mobile' => 0,
					'pay_money' => 0,
			);
			$result_after_pay = $this->after_pay($order_param);
			if($result_after_pay['error']){
				return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
			}
			return array('error_code'=>false,'url'=>U('My/appoint_order',array('order_id'=>$order_info['order_id'])));
		}
	}

    public function cancelPaidOrder($orderId)
    {
        //copy from cms/Lib/Action/Appapi/Appoint_newAction.class.php 2668
        $now_order = $this->where(['order_id' => $orderId, 'service_status' => 0])->find();
        if(empty($now_order)){
            return false;
        }
        $now_order['order_type'] = 'appoint';
        
        $merchantMoney = D('Merchant_money_list')
            ->where([
                'mer_id'   => $now_order['mer_id'],
                'order_id' => $now_order['order_id'],
                'type'     => 'appoint',
            ])->find();

        if(!empty($merchantMoney)){
            return false;
        }
        
        $now_order['money'] =
            $now_order['balance_pay'] +
            $now_order['score_deducte'] +
            ($now_order['is_own'] ? 0 : $now_order['pay_money']) +
            $now_order['merchant_balance'];

        if ($now_order['product_id'] > 0) {
            $now_order['total_money'] = $now_order['product_price'];
        } else {
            $now_order['total_money'] = $now_order['appoint_price'];
        }
        $now_appoint = M('Appoint')
            ->field('appoint_name')
            ->where(['appoint_id' => $now_order['appoint_id']])
            ->find();

        $now_order['desc'] = '用户预约 ' . $now_appoint['appoint_name'] . '*1 取消预约，定金' . $now_order['money'] . '记入收入';

        return D('SystemBill')->bill_method($now_order['is_own'], $now_order);
    }
	//支付之后
	/**
	 * @param $order_param
	 * @return array
	 */
	public function after_pay($order_param){
		fdump_api($order_param,'appoint_order/after_pay',true);
        if($order_param['pay_type'] != ''){
			$condition_appoint_order['orderid'] = $order_param['order_id'];
		}else{
			$condition_appoint_order['order_id'] = $order_param['order_id'];
		}
		$now_order = $this->field(true)->where($condition_appoint_order)->find();
		$appoint_info  =D('Appoint')->where(array('appoint_id'=>$now_order['appoint_id']))->find();

        fdump_api(['预约订单' => $now_order],'appoint_order/after_pay',1);
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在！'));
		}else if($now_order['paid'] == 1){
			fdump($now_order['paid'],'now_order',1);
			if(empty($order_param['is_initiative'])){
				if($order_param['is_mobile']){
					return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>U('My/appoint_order',array('order_id'=>$now_order['order_id'])));
				}else{
					return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>U('User/Index/appoint_order_view',array('order_id'=>$now_order['order_id'])));
				}
			}else{
				//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
				$now_user = D('User')->get_user($now_order['uid']);
				if(empty($now_user)){
					return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
				}

				//如果使用了平台E卡
				if(!empty($now_order['ecard_password'])){
					D('Ecard_coupon')->add_ecard_log($now_order,'appoint');
				}

				//D('Action_relation')->add_user_action($order_param['order_id'],'appoint');

				$group_pass_array = array(
					date('y',$_SERVER['REQUEST_TIME']),
					date('m',$_SERVER['REQUEST_TIME']),
					date('d',$_SERVER['REQUEST_TIME']),
					date('H',$_SERVER['REQUEST_TIME']),
					date('i',$_SERVER['REQUEST_TIME']),
					date('s',$_SERVER['REQUEST_TIME']),
					mt_rand(10,99),
				);
				shuffle($group_pass_array);
				$data_group_order['appoint_pass'] = implode('',$group_pass_array);
			
				$data_group_order['pay_money'] = floatval($order_param['pay_money']);
				$data_group_order['user_pay_money'] = $order_param['user_pay_money'];
				$data_group_order['user_pay_time'] = $order_param['user_pay_time'];
				$data_group_order['last_time'] = $_SERVER['REQUEST_TIME'];
				$data_group_order['user_recharge_order_id'] = $order_param['user_recharge_order_id'];
                $data_group_order['is_initiative'] = 1;
                if ($data_group_order['payment_status']) {
                    $data_group_order['service_status'] = 1;
                }
				$data_group_order['paid'] = 1;
                $data_group_order['product_pay_type'] = $order_param['pay_type'];
                $data_group_order['product_third_id'] = $order_param['third_id'];
                $data_group_order['product_is_mobile_pay'] = $order_param['is_mobile'];
                $data_group_order['product_is_own'] = $order_param['is_own'];
                $data_group_order['product_real_balace_pay_time'] = $_SERVER['REQUEST_TIME'];
                $data_group_order['product_real_balace_price'] = floatval($order_param['pay_money']);

                $appointSupply = M('Appoint_supply')->where(['order_id' => $now_order['order_id']])->find();
                if($appointSupply['status'] == 3){//技师已服务
                    $data_group_order['service_status'] = 1;//订单已完成
                }
				if($this->where($condition_appoint_order)->data($data_group_order)->save()){
                    if($now_order['merchant_worker_id']){//服务员工服务费
                        $orderPrice = floatval($now_order['product_price']) > 0 ? $now_order['product_price'] : $now_order['appoint_price'];
                        $supplyData['commission'] = D('Merchant_workers')->appointServiceFee($now_order['merchant_worker_id'], $orderPrice);
                        if($appointSupply['status'] < 2) {
                            $supplyData['status'] = 2;//订单待服务
                        }
                    }
                    $supplyData['paid'] = 1;
                    M('Appoint_supply')->where(['order_id' => $now_order['order_id']])->save($supplyData);
				    if($data_group_order['service_status'] == 1){
                        M('Appoint_supply')->where(['order_id' => $now_order['order_id']])->save(['status' => 3]);
                    }
					/*----系统订单信息----*/
			    	D('System_order')->save_reg_order_info('appoint',$now_order['order_id'],$data_group_order);

					/*分析粉丝行为*/

					D('Scroll_msg')->add_msg('appoint',$now_user['uid'],L_('用户X1于X2购买X3成功',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>C('config.appoint_alias_name'))));

					D('Merchant_request')->add_request($now_order['mer_id'],array('appoint_buy_count'=>1,'appoint_buy_money'=>$now_order['payment_money']));

                    //小票打印start
                    $printHaddle = new PrintHaddle();
                    $printHaddle->printit($now_order['order_id'], 'appoint_order', 1);
                    //小票打印end

					$condition_group['appoint_id'] = $now_order['appoint_id'];
					//D('Appoint')->where($condition_group)->setInc('appoint_sum',1);
					$now_order = $this->field(true)->where($condition_appoint_order)->find();
					if($now_order["is_initiative"] && $now_order["service_status"]){
						$payment_money = $now_order['pay_money'] + $now_order['user_pay_money'];
					}else if($now_order["is_initiative"] && !$now_order["service_status"]){
						$payment_money = $now_order['user_pay_money'];
					}else{
						$payment_money = $now_order['pay_money'];
					}
					$payment_money = $now_order['pay_money'] + $now_order['score_deducte']+ $now_order['merchant_balance']+$now_order['balance_pay'];
					if ($now_user['openid'] && $order_param['is_mobile']) {
						$href = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
						$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => L_('预约提醒'), 'keyword1' => $appoint_info['appoint_name'], 'keyword2' => $now_order['order_id'], 'keyword3' => $payment_money, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('预约成功，您的消费码：').$data_group_order['appoint_pass']));
					}
					$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'appoint');
					if (C('config.sms_success_order') == 1 || C('config.sms_success_order') == 3) {
						$sms_data['uid'] = $now_order['uid'];
						$sms_data['mobile'] = $now_user['phone'];
						$sms_data['sendto'] = 'user';
						$sms_data['content'] = L_('您预约 X1的订单(订单号：X2)已经完成支付,您的消费码：X3',array('X1'=>$now_order['order_name'],'X2'=>$now_order['order_id'],'X3'=>$data_group_order['appoint_pass']));
						Sms::sendSms($sms_data);
					}
					if (C('config.sms_success_order') == 2 || C('config.sms_success_order') == 3) {
						$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
						$sms_data['uid'] = 0;
						$sms_data['mobile'] = $merchant['phone'];
						$sms_data['sendto'] = 'merchant';
						$sms_data['content'] = L_('顾客预约的X1的订单(订单号：X2),在X3时已经完成了支付！',array('X1'=>$now_order['order_name'],'X2'=>$now_order['order_id'],'X3'=>date('Y-m-d H:i:s')));
						Sms::sendSms($sms_data);
					}

					$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'], 'type' => 'appoint');

					$database_appoint_visit_order_info = D('Appoint_visit_order_info');
					$worker_where['appoint_order_id'] = $now_order['order_id'];
					$now_worker = $database_appoint_visit_order_info->appoint_visit_order_detail($worker_where);
					$now_worker = $now_worker['detail'];
					if((C('config.worker_sms_success_order') == 1) && !empty($now_worker)){
						$sms_data['uid'] = 0;
						$sms_data['mobile'] = $now_worker['mobile'];
						$sms_data['sendto'] = 'user';
						$sms_data['content'] = L_('用户在X1时，购买了预约服务，已成功支付，订单号：X2',array('X1'=>date('Y-m-d H:i',time()),'X2'=>$now_order['order_id']));
						Sms::sendSms($sms_data);
					}
                    // 成功付款推送消息给 商家
                    if ($now_order['mer_id']) {
                        // 获取对应商家user账号openid
                        $uid = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->getField('uid');
                        $merchant_user_openid = M('User')->where(array('uid' => $uid))->getField('openid');

                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        // 处理备注
                        $remark = L_('服务名称：').$appoint_info['appoint_name'];
                        if ($now_order['product_id'] > 0 && $now_order['product_price']) {
                            $remark .= ';服务全价：'.$now_order['product_price'];
                        } elseif ($now_order['appoint_price']) {
                            $remark .= ';服务全价：'.$now_order['appoint_price'];
                        }
                        if ($now_order['appoint_time']) {
                            $remark .= ';预约时间：'.$now_order['appoint_time'];
                        }
                        $model->sendTempMsg('TM00017',
                            array(
                                'wecha_id' => $merchant_user_openid,
                                'first' => L_('用户在X1时，购买了预约服务，成功支付，订单号：X2',array('X1'=>date('Y-m-d H:i',time()),'X2'=>$order_param['order_id'])),
                                'OrderSn' => $order_param['order_id'],
                                'OrderStatus' => L_('支付成功'),
                                'remark' => $remark
                            )
                        );
                    }

					/* 计算用户分销获得佣金 通过分享链接直接购买才有分佣一般只有一个商品*/
					if (C('config.open_single_spread')==1&&$now_order['distributor_uid']&&$appoint_info['distributor_percent']) {
						$distributor_spread_data = array();
						$spread_total_money = $now_order['product_balance_pay']+$now_order['user_pay_money'];
						$distributor_spread_money = round($spread_total_money*$appoint_info['distributor_percent']/100,2);
						if ($distributor_spread_money) {
							$distributor_spread_data =array(
								'spread_uid' => $now_order['distributor_uid'], 
								'order_uid' => $now_order['uid'],  
								'money' => $distributor_spread_money, 
								'order_type' => 'appoint',
								'order_id' => $now_order['order_id'],
								'real_orderid' => $now_order['real_orderid'],
								'third_id' => $now_order['appoint_id'],
								'add_time' => $_SERVER['REQUEST_TIME']
							);
							M('Distributor_spread_list')->data($distributor_spread_data)->add();
						}
					}

                    $merchant_workers_id = $now_order['merchant_workers_id'];
                    // 处理推送给对应的预约工作人员
                    if(!empty($merchant_workers_id)){
                        // 存在工作人员id 直接推送
                        $database_merchant_workers = M('Merchant_workers');
                        $worker_where_info = array();
                        $worker_where_info['merchant_worker_id'] = $merchant_workers_id;
                        $now_worker = $database_merchant_workers->where($worker_where)->find();
                        if ($now_worker['openid']) {
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $first = L_('用户在X1时，购买了预约服务，请及时查看，订单号：X2',array('X1'=>date('Y-m-d H:i',time()),'X2'=>$now_order['order_id']));
                            $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                            $model->sendTempMsg('TM00017',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $now_worker['openid'],
                                    'first' => $first,
                                    'OrderSn' => $now_order['order_id'],
                                    'OrderStatus' => L_('支付成功'),
                                    'remark' => '\n'.L_('请点击查看详细信息！')
                                )
                            );
                        }
                    } else {
                        // 查询下商家店铺下所有工作人员
                        $database_merchant_workers = M('Merchant_workers');
                        $worker_where_info = array();
                        $worker_where_info['mer_id'] = $now_order['mer_id'];
                        $worker_where_info['merchant_store_id'] = $now_order['store_id'];
                        $worker_where_info['status'] = 1;
                        $worker_where_info['openid'] = array('neq', '');
                        $workers = $database_merchant_workers->where($worker_where_info)->select();
                        if ($workers) {
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $first = L_('用户在X1时，购买了预约服务，请及时抢单，订单号：X2',array('X1'=>date('Y-m-d H:i',time()),'X2'=>$now_order['order_id']));
                            $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=grab';
                            foreach ($workers as $val) {
                                $model->sendTempMsg('TM00017',
                                    array(
                                        'href' => $href,
                                        'wecha_id' => $val['openid'],
                                        'first' => $first,
                                        'OrderSn' => $now_order['order_id'],
                                        'OrderStatus' => L_('支付成功'),
                                        'remark' => '\n'.L_('请点击查看详细信息！')
                                    )
                                );
                            }
                        }
                    }
                    if($appointSupply['status'] == 3 && $data_group_order['service_status'] == 1){//技师已服务,订单已完成
                        $this->appoint_verify($now_order['order_id']);
                    }
					if($order_param['is_mobile']){
						return array('error'=>0,'url'=>str_replace('/source/','/',U('My/appoint_order',array('order_id'=>$now_order['order_id']))));
					}else{
						return array('error'=>0,'url'=>U('User/Index/appoint_order_view',array('order_id'=>$now_order['order_id'])));
					}
				}else{
					return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
				}
			}
		}else{
			fdump($now_order['paid'],'now_order',1);
			//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
			$now_user = D('User')->get_user($now_order['uid']);
			if(empty($now_user)){
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
			}

			if($now_order['card_id']){
				$now_coupon = D('Card_new_coupon')->get_coupon_by_id($now_order['card_id']);
				if(empty($now_coupon)){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//判断平台优惠券
			if($now_order['coupon_id']){
				$now_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
				if(empty($now_coupon)){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//判断帐户余额
			$merchant_balance = floatval($now_order['merchant_balance']);
			if($merchant_balance){
				$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				if($user_merchant_balance['card_money'] < $merchant_balance){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}

			$card_give_money = floatval($now_order['card_give_money']);
			if($card_give_money){
				$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				if($user_merchant_balance['card_money_give'] < $card_give_money){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}

			$balance_pay = floatval($now_order['balance_pay']);
			if($balance_pay){
				if($now_user['now_money'] < $balance_pay){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的帐户余额不够此次支付！'));
				}
			}

			if($now_order['card_id']){
				$use_result = D('Card_new_coupon')->user_coupon($now_order['card_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果使用了平台优惠券
			if($now_order['coupon_id']){
				$use_result = D('System_coupon')->user_coupon($now_order['coupon_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果用户使用了积分抵扣，则扣除相应的积分
			//判断积分数量是否正确
			$score_used_count=$now_order['score_used_count'];
			if($score_used_count*1 > 0 && $now_user['score_count']<$score_used_count){
				return array('error'=>1,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}
			if($score_used_count>0){
				$use_result = D('User')->user_score($now_order['uid'],$score_used_count,L_('购买 X1 扣除X2',array('X1'=>$now_order['order_name'],'X2'=>C('config.score_name'))));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}


			if($merchant_balance){
				$use_result = D('Card_new')->use_money($now_order['uid'],$now_order['mer_id'],$merchant_balance,L_('购买 X1 扣除会员卡余额',$now_order['order_name']));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			if($card_give_money){
				$use_result = D('Card_new')->use_give_money($now_order['uid'],$now_order['mer_id'],$card_give_money,L_('购买 X1 扣除会员卡赠送余额',$now_order['order_name']));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果用户使用了余额支付，则扣除相应的金额。
			if(!empty($balance_pay)){
				if($now_order['product_name']){
					$tmp_order_name = $appoint_info['appoint_name'] . ' - ' . $now_order['product_name'];
				}else{
					$tmp_order_name = $appoint_info['appoint_name'];
				}

				$use_result = D('User')->user_money($now_order['uid'],$balance_pay,L_('预约 X1 扣除余额',$tmp_order_name));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

            //判断一卡通余额, 扣除余额
            $card_pay_info = json_decode($now_order['internal_card_pay'], true);

            $card = M('internal_membership_card');
            $internal_card_log = M('internal_membership_card_log');
            $time = time();
            $internal_card_pay = 0;
            if (!empty($card_pay_info)){
                foreach($card_pay_info as $card_id => $card_pay_money){
                    $card_info = $card->where(array('card_id' => $card_id))->find();
                    $card_info['balance_money'] = round($card_info['balance_money'] * 100) / 100;
                    if($card_info['balance_money'] < $card_pay_money){
                        return array('error'=>1,'msg'=>L_('您的会员卡'. $card_id .'余额不足'));
                    }
                    $save_data['balance_money'] = $card_info['balance_money'] - $card_pay_money;
                    $save_data['last_time'] = $time;
                    if(!$card->where(array('card_id' => $card_id))->save($save_data)){
                        return array('error'=>1,'msg'=>L_('会员卡'. $card_id .'支付失败!'));
                    }
                    $condition_card_log['card_id'] = $card_id;
                    $condition_card_log['operation_type'] = 2; //交易
                    $condition_card_log['before_balance'] = $card_info['balance_money'];
                    $condition_card_log['after_balance'] = $save_data['balance_money'];
                    $condition_card_log['money'] = $card_pay_money;
                    $condition_card_log['business_type'] = 'appoint';
                    $condition_card_log['order_id'] = $now_order['orderid'];
                    $condition_card_log['uid'] = $now_order['uid'];
                    $condition_card_log['describe'] = '店铺预约, 扣除余额'. $card_pay_money .'元, 订单编号'.$now_order['orderid'];
                    $condition_card_log['mer_id'] = $now_order['mer_id'];
                    $condition_card_log['store_id'] = $now_order['store_id'];
                    $condition_card_log['create_time'] = $time;
                    $internal_card_log->add($condition_card_log);
                    $internal_card_pay += $card_pay_money;
                    D('Internal_card')->send_wx_msg($card_id, '-'.getFormatNumber($card_pay_money), '店铺预约');
                }
            }
			$group_pass_array = array(
					date('y',$_SERVER['REQUEST_TIME']),
					date('m',$_SERVER['REQUEST_TIME']),
					date('d',$_SERVER['REQUEST_TIME']),
					date('H',$_SERVER['REQUEST_TIME']),
					date('i',$_SERVER['REQUEST_TIME']),
					date('s',$_SERVER['REQUEST_TIME']),
					mt_rand(10,99),
			);
			shuffle($group_pass_array);
			$data_group_order['appoint_pass'] = implode('',$group_pass_array);

			$data_group_order['pay_time'] = isset($order_param['pay_time']) && $order_param['pay_time'] ? strtotime($order_param['pay_time']) : $_SERVER['REQUEST_TIME'];
			$data_group_order['pay_money'] = floatval($order_param['pay_money']);
			$data_group_order['pay_type'] = $order_param['pay_type'];
			$data_group_order['third_id'] = $order_param['third_id'];
			$data_group_order['is_mobile_pay'] = $order_param['is_mobile'];
			$data_group_order['product_real_pay_payment_time'] = $_SERVER['REQUEST_TIME'];
			$data_group_order['product_real_payment_price'] = floatval($order_param['pay_money']);
			$data_group_order['is_own'] = $order_param['sub_mch_id'] ? 2 : $order_param['is_own'];
			$data_group_order['paid'] = 1;
			$data_group_order['last_staff'] = '';

			if($this->where($condition_appoint_order)->data($data_group_order)->save()){
				/*分析粉丝行为*/
				D('Merchant_request')->add_request($now_order['mer_id'],array('appoint_buy_count'=>1,'appoint_buy_money'=>$now_order['payment_money']));

				D('Scroll_msg')->add_msg('appoint',$now_user['uid'],L_('用户X1于X2购买X3成功',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>C('config.appoint_alias_name'))));

				$condition_group['appoint_id'] = $now_order['appoint_id'];
				//D('Appoint')->where($condition_group)->setInc('appoint_sum',1);

				$now_order = $this->field(true)->where($condition_appoint_order)->find();
				if($now_order['store_id']){
					D('Merchant_store_staff')->sendMsgAppointOrder($now_order);
				}

				//增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store
				D('Merchant')->saverelation($now_user['openid'], $now_order['mer_id'], 8);

                //小票打印start
                $printHaddle = new PrintHaddle();
                $printHaddle->printit($now_order['order_id'], 'appoint_order', 1);
                //小票打印end


				if($now_order["is_initiative"] && $now_order["service_status"]){
					$payment_money = $now_order['pay_money'] + $now_order['user_pay_money'];
				}else if($now_order["is_initiative"] && !$now_order["service_status"]){
					$payment_money = $now_order['user_pay_money'];
				}else{
					$payment_money = $now_order['pay_money'];
				}
				$payment_money = $now_order['pay_money'] + $now_order['score_deducte']+ $now_order['merchant_balance']+$now_order['balance_pay']+$now_order['product_score_deducte'];

				if ($now_user['openid'] && $order_param['is_mobile']) {
					$href = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => L_('预约提醒'), 'keyword1' => $appoint_info['appoint_name'], 'keyword2' => $now_order['order_id'], 'keyword3' => $payment_money, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('预约成功，您的消费码：').$data_group_order['appoint_pass']));
				}
				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'appoint');
				if (C('config.sms_success_order') == 1 || C('config.sms_success_order') == 3) {
					$sms_data['uid'] = $now_order['uid'];
					$sms_data['mobile'] = $now_user['phone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('您预约 X1的订单(订单号：X2)已经完成支付,您的消费码：X3',array('X1'=>$now_order['order_name'],'X2'=>$now_order['order_id'],'X3'=>$data_group_order['appoint_pass']));
					Sms::sendSms($sms_data);
				}
				if (C('config.sms_success_order') == 2 || C('config.sms_success_order') == 3) {
					$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $merchant['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_('顾客预约的X1的订单(订单号：X2),在X3时已经完成了支付！',array('X1'=>$now_order['order_name'],'X2'=>$now_order['order_id'],'X3'=>date('Y-m-d H:i:s')));
					Sms::sendSms($sms_data);
				}

				$database_appoint_visit_order_info = D('Appoint_visit_order_info');
				$worker_where['appoint_order_id'] = $now_order['order_id'];
				$now_worker = $database_appoint_visit_order_info->appoint_visit_order_detail($worker_where);
				$now_worker = $now_worker['detail'];

				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'], 'type' => 'appoint');
				if((C('config.worker_sms_success_order') == 1) && !empty($now_worker)){
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $now_worker['mobile'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('用户在x1时，购买了预约服务，已成功支付，订单号：x2',array('x1'=>date('Y-m-d H:i',time()),'x2'=>$now_order['order_id']));
					Sms::sendSms($sms_data);
				}

                // 成功付款推送消息给 商家
                if ($now_order['mer_id']) {
                    // 获取对应商家user账号openid
                    $uid = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->getField('uid');
                    $merchant_user_openid = M('User')->where(array('uid' => $uid))->getField('openid');

                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('TM00017',
                        array(
                            'wecha_id' => $merchant_user_openid,
                            'first' => L_('用户在x1时，购买了预约服务，成功支付，订单号：x2',array('x1'=>date('Y-m-d H:i',time()),'x2'=>$now_order['order_id'])),
                            'OrderSn' => $now_order['order_id'],
                            'OrderStatus' => L_('支付成功'),
                            'remark' => date('Y-m-d H:i',time())
                        )
                    );
                }

				/* 计算用户分销获得佣金 通过分享链接直接购买才有分佣一般只有一个商品*/
				if (C('config.open_single_spread')==1&&$now_order['distributor_uid']&&$appoint_info['distributor_percent']) {
					$distributor_spread_data = array();
					$spread_total_money = $now_order['balance_pay']+$now_order['pay_money'];
					fdump($now_order['balance_pay'],'spread');
					fdump($now_order['payment_money'],'spread',1);
					$distributor_spread_money = round($spread_total_money*$appoint_info['distributor_percent']/100,2);
					fdump($distributor_spread_money,'spread',1);
					if ($distributor_spread_money) {
						$distributor_spread_data =array(
							'spread_uid' => $now_order['distributor_uid'], 
							'order_uid' => $now_order['uid'],  
							'money' => $distributor_spread_money, 
							'order_type' => 'appoint',
							'order_id' => $now_order['order_id'],
							'real_orderid' => $now_order['real_orderid'],
							'third_id' => $now_order['appoint_id'],
							'add_time' => $_SERVER['REQUEST_TIME']
						);
						M('Distributor_spread_list')->data($distributor_spread_data)->add();
					}
				}
                $now_order = $this->field(true)->where($condition_appoint_order)->find();
				$database_appoint_supply = D('Appoint_supply');
				$supply_data['appoint_id'] = $now_order['appoint_id'];
				$supply_data['mer_id'] = $now_order['mer_id'];
				$supply_data['store_id'] = $now_order['store_id'];
				$supply_data['create_time'] = time();
				$supply_data['worker_id'] = $now_order['merchant_worker_id'];
				$supply_data['start_time'] = $_SERVER['REQUEST_TIME'];
				$supply_data['paid'] = $now_order['paid'];
                $supply_data['uid'] = $now_order['uid'];
                if($now_order['merchant_worker_id']){
                    $supply_data['status'] =  2;
                }else{
                    $supply_data['status'] =  1;
                }
				$supply_data['pay_type'] = $now_order['pay_type'];
				$supply_data['order_time'] = $now_order['order_time'];
				$supply_data['deliver_cash'] = floatval($now_order['product_price'] - $now_order['product_card_price'] - $now_order['product_merchant_balance'] - $now_order['product_balance_pay'] - $now_order['product_payment_money'] - $now_order['product_score_deducte'] - $now_order['product_coupon_price']);
				$supply_data['order_id'] = $now_order['order_id'];
                if($now_order['merchant_worker_id']){//服务员工服务费
                    $orderPrice = floatval($now_order['product_price']) > 0 ? $now_order['product_price'] : $now_order['appoint_price'];
                    $supply_data['commission'] = D('Merchant_workers')->appointServiceFee($now_order['merchant_worker_id'], $orderPrice);
                    $supply_data['status'] = 2;
                }
                fdump_api([
                    'supply_data' => $supply_data,
                    'order_price' => $orderPrice
                ],'appoint_order/after_pay',1);
                
				$database_appoint_supply->data($supply_data)->add();
				/*----系统订单信息----*/
			    D('System_order')->save_reg_order_info('appoint',$now_order['order_id'],$data_group_order);
                $merchant_workers_id = $now_order['merchant_workers_id'];
                // 处理推送给对应的预约工作人员
                if(!empty($merchant_workers_id)){
                    // 存在工作人员id 直接推送
                    $database_merchant_workers = M('Merchant_workers');
                    $worker_where_info = array();
                    $worker_where_info['merchant_worker_id'] = $merchant_workers_id;
                    $now_worker = $database_merchant_workers->where($worker_where_info)->find();
                    if ($now_worker['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $first = L_('用户在x1时，购买了预约服务，请及时查看，订单号：x2',array('x1'=>date('Y-m-d H:i',time()),'x2'=>$now_order['order_id']));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                        $model->sendTempMsg('TM00017',
                            array(
                                'href' => $href,
                                'wecha_id' => $now_worker['openid'],
                                'first' => $first,
                                'OrderSn' => $now_order['order_id'],
                                'OrderStatus' => L_('支付成功'),
                                'remark' => '\n'.L_('请点击查看详细信息！')
                            )
                        );
                    }
                } else {
                    // 查询下商家店铺下所有工作人员
                    $database_merchant_workers = M('Merchant_workers');
                    $worker_where_info = array();
                    $worker_where_info['mer_id'] = $now_order['mer_id'];
                    $worker_where_info['merchant_store_id'] = $now_order['store_id'];
                    $worker_where_info['status'] = 1;
                    $worker_where_info['openid'] = array('neq', '');
                    $workers = $database_merchant_workers->where($worker_where_info)->select();
                    if ($workers) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $first = L_('用户在x1时，购买了预约服务，请及时抢单，订单号：x2',array('x1'=>date('Y-m-d H:i',time()),'x2'=>$now_order['order_id']));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=grab';
                        foreach ($workers as $val) {
                            $model->sendTempMsg('TM00017',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $val['openid'],
                                    'first' => $first,
                                    'OrderSn' => $now_order['order_id'],
                                    'OrderStatus' => L_('支付成功'),
                                    'remark' => '\n'.L_('请点击查看详细信息！')
                                )
                            );
                        }
                    }
                }
                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'appoint';
                $params['order_id'] = $now_order['order_id'];
                $params['mer_id'] = $now_order['mer_id'];
                $params['store_id'] = $now_order['store_id'];
                $params['uid'] = $now_order['uid'];
                $params['title'] = '商品下单成功';
                $params['content'] = '下单成功，查看订单详情';
                invoke_v20_service($url, [$params]);
				
				if($order_param['is_mobile']){
					return array('error'=>0,'url'=>str_replace('/source/','/',U('My/appoint_order',array('order_id'=>$now_order['order_id']))));
				}else{
					return array('error'=>0,'url'=>U('User/Index/appoint_order_view',array('order_id'=>$now_order['order_id'])));
				}
			}else{
				return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}
    /**
     * 预约核销
     * copy form cms/Lib/Action/Merchant/StoreAction.class.php appoint_verify
     * @param $orderId
     */
    public function appoint_verify($orderId, $orderParams = []){
        $database_order = D('Appoint_order');
        $database_appoint_visit_order_info = D('Appoint_visit_order_info');
        $database_merchant_workers = D('Merchant_workers');

        $where['order_id'] = $orderId;
        $now_order = $database_order->field(true)->where($where)->find();
        
        $condition_group['appoint_id'] = $now_order['appoint_id'];
        D('Appoint')->where($condition_group)->setInc('appoint_sum',1);
        $fields['store_id'] = $now_order['store_id'];
        if(isset($orderParams['worker_id'])){
            $supply = D('Appoint_supply')->where([
                'order_id' => $orderId,
                'worker_id' => $orderParams['worker_id']
            ])->find();
            if(empty($supply)){
                return ['error'=>1,'msg'=>L_('当前订单服务异常！')];
            }
            $fields['last_staff'] = '非店员操作，技师确认，自动结算';
        }else{
            $fields['last_staff'] = '非店员操作，用户付款，自动结算';
        }
        $fields['last_time'] = time();
        $fields['verify_time'] = time();
        $fields['service_status'] = 1;
        $fields['paid'] = 1;
        fdump_api(['appoint_auto_verify' => [$where, $fields]], 'appoint_pay/appoint_order', 1);
        if($database_order->where($where)->data($fields)->save()){
            $Map['appoint_order_id'] = $orderId;
            $appoint_visit_order_info = $database_appoint_visit_order_info->where($Map)->find();

            $worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'];
            $pay_money_count = $database_appoint_visit_order_info->order_appoint_price_sum($worker_where);
            $database_merchant_workers->where($worker_where)->setField('appoint_price',$pay_money_count);
            $database_merchant_workers->where($worker_where)->setInc('order_num');
            
            //验证增加商家余额
            $order_info['order_type']='appoint';
            $order_info['order_id'] = $now_order['order_id'];
            $order_info['mer_id'] = $now_order['mer_id'];
            $order_info['store_id'] = $now_order['store_id'];
            $order_info['score_deducte'] = $now_order['score_deducte'] + $now_order['product_score_deducte'];
            $order_info['is_own'] = $now_order['is_own'];
            $order_info['score_used_count'] = $now_order['score_used_count'];
            $order_info['money'] = $now_order['score_deducte']
                + $now_order['balance_pay']
                + ($order_info['is_own']>0 ? 0 : (float)$now_order['pay_money'])
                + $now_order['merchant_balance']
                + $now_order['product_score_deducte']
                + $now_order['product_balance_pay']
                + $now_order['product_coupon_price']
                + ($order_info['is_own']>0 ? 0 : (float)$now_order['user_pay_money'])
                + $now_order['product_merchant_balance'];

            if($now_order['product_id'] > 0){
                $order_info['total_money'] = $now_order['product_price'];
            }else{
                $order_info['total_money'] = $now_order['appoint_price'];
            }
            if ($order_info['money']>$order_info['total_money']) {
                $order_info['money'] = $order_info['total_money'];
            }

            $order_info['payment_money'] = $now_order['pay_money'] + $now_order['user_pay_money'];
            $order_info['balance_pay'] = $now_order['balance_pay'] + $now_order['product_balance_pay'];
            $order_info['merchant_balance'] = $now_order['merchant_balance'] + $now_order['product_merchant_balance'];
            $order_info['card_give_money'] = $now_order['card_give_money'] + $now_order['product_card_give_money'];
            $order_info['uid'] = $now_order['uid'];
            $order_info['distributor_uid'] = $now_order['distributor_uid'];

            $appoint_name = M('Appoint')->field('appoint_name,score_percent')->where(array('appoint_id'=>$now_order['appoint_id']))->find();
            $order_info['desc']=L_('用户预约X1记入收入',$appoint_name['appoint_name']);
            $order_info['score_discount_type']=$now_order['score_discount_type'];
            D('SystemBill')->bill_method($order_info['is_own'],$order_info);

            $now_user = D('User')->get_user($order_info['uid']);
            D('Merchant_spread')->add_spread_list($order_info,$now_user,'appoint',$now_user['nickname'].L_("用户购买预约获得佣金"));
            if(!isset($orderParams['worker_id'])) {
                $where['status'] = array('eq', 3);
            }
            $database_appoint_supply = D('Appoint_supply');
            $now_supply = $database_appoint_supply->where($where)->find();
            if($now_supply){
                $supply_data['end_time'] = time();
                $supply_data['status'] = 3;
                if(isset($orderParams['worker_id'])){
                    $supply_data['check_source'] = 0;
                }else{
                    $supply_data['check_source'] = 3;
                }
                $supply_data['check_time'] = time();
                if($now_order['merchant_worker_id']){//服务员工服务费
                    $orderPrice = floatval($now_order['product_price']) > 0 ? $now_order['product_price'] : $now_order['appoint_price'];
                    $supply_data['commission'] = D('Merchant_workers')->appointServiceFee($now_order['merchant_worker_id'], $orderPrice);
                }
                $database_appoint_supply->where($where)->data($supply_data)->save();
            }
            if(C('config.open_extra_price')==1){
                $score = D('Percent_rate')->get_extra_money($order_info);
                if($score>0){
                    D('User')->add_score($order_info['uid'], floor($score), L_('用户预约X1 获得X2',array('X1'=>$appoint_name['appoint_name'],'X2'=>C('config.extra_price_alias_name'))),0,[
                        'order_id'=>$now_order['order_id'],
                        'order_type'=>'appoint'
                    ]);
                }
            }else if(C('config.add_score_by_system_commission')==0 && C('config.add_score_by_percent')==0 && (C('config.open_score_discount')==0 || $now_order['score_discount_type']!=2)&& (C('config.score_get_times')==0)){
                if($order_info['is_own']&& $this->config['user_own_pay_get_score']!=1){
                    $order_info['payment_money'] = 0;
                }
                if($appoint_name['score_percent'] > 0){
                    $score_percent = $appoint_name['score_percent'];
                }
                else{
					if(C('config.open_score_get_percent')==1){
						$score_percent = C('config.score_get_percent')/100;
					}else{
						$score_percent = C('config.user_score_get');
					}
                    $score_percent = D('Percent_rate')->get_user_add_score_times($score_percent,$order_info['uid']);
                }

                D('User')->add_score($now_order['uid'], ($order_info['balance_pay']+$order_info['payment_money']) * $score_percent , L_('购买预约商品获得').$this->config['score_name'],0,[
                    'order_id'=>$now_order['order_id'],
                    'order_type'=>'appoint'
                ]);
            }
            //三级分佣处理
            $spread_total_money = $order_info['total_money'];

            if(C('config.add_spread_by_system_commission')){// 根据平台抽成获得佣金	
                // 获取平台抽成金额							
                $order_info['get_system_take_money'] = true;
                $spread_total_money = D('SystemBill')->bill_method($order_info['is_own'],$order_info);;
            }

            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            if(($now_user['can_spread']==1 && !C('config.open_spread_only_once')) &&!empty($now_user['openid'])&&$now_order['pay_type']!='offline' && C('config.open_user_spread')&& (C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)){
                //上级分享佣金
                $spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],'appoint','',$now_order['appoint_id'],$now_order);
                $spread_users[]=$now_user['uid'];
                $spread_where['_string'] = "uid = '{$now_user['uid']}'";
                $now_user_spread = D('User_spread')->where($spread_where)->find();
                $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
                if(!empty($now_user_spread)){
                    $spread_user = D('User')->get_user($now_user_spread['spread_uid']);
                    $user_spread_rate = $spread_rate['first_rate'];//一级分佣金额
                    if($spread_user && $user_spread_rate &&!in_array($spread_user['uid'],$spread_users)){
                        if ($user_spread_rate ) {
                            $spread_money = round($spread_total_money * $user_spread_rate / 100, 2);

                            $spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_money,'order_type'=>'appoint','order_id'=>$now_order['order_id'],'third_id'=>$now_order['appoint_id'],'add_time'=>$_SERVER['REQUEST_TIME']);
                            if($spread_user['spread_change_uid']!=0){
                                $spread_data['change_uid'] = 	$spread_user['spread_change_uid'];
                            }
                            // 处理长订单id
                            if ($now_order['real_orderid']) {
                                $spread_data['real_orderid'] = $now_order['real_orderid'];
                            }
                            D('User_spread_list')->data($spread_data)->add();

                            $buy_user = D('User')->get_user($now_user_spread['uid'],'uid');
                            if($spread_money>0){
                                $money_name = L_('佣金');
                                $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享购买商品，验证消费后您将获得X2。",array("X1" => $buy_user['nickname'],"X2" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
                            }
                        }
                        $spread_users[]=$spread_user['uid'];
                        D('User')->add_money($spread_user['uid'],$spread_money,'推广用户 '.$now_user['nickname'].' 购买 ID为 '.$now_order['appoint_id'].' 的商品 获得的佣金');
                    }
                    //第二级分享佣金
                    $spread_where['_string'] = "uid = '{$spread_user['uid']}' ";
                    $second_user_spread = D('User_spread')->where($spread_where )->find();
                    if(!empty($second_user_spread)) {
                        $second_user = D('User')->get_user($second_user_spread['spread_uid']);
                        $sub_user_spread_rate = $spread_rate['second_rate'];//二级分佣的钱
                        if ($second_user && ($sub_user_spread_rate)&&!in_array($second_user['uid'],$spread_users)) {
                            if ($sub_user_spread_rate) {
                                $spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);

                                $spread_data =array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'appoint', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['appoint_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
                                if($spread_user['spread_change_uid']!=0){
                                    $spread_data['change_uid'] = 	$second_user['spread_change_uid'];
                                }
                                // 处理长订单id
                                if ($now_order['real_orderid']) {
                                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                                }
                                D('User_spread_list')->data($spread_data)->add();

                                $sec_where['_string'] = "uid = '{$second_user_spread['uid']}' ";
                                $sec_user = M('User')->where($sec_where)->find();

                                if($spread_money>0) {
                                    $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_("X1的子用户X2通过您的分享购买商品，验证消费后您将获得佣金。",array("X1" => $sec_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
                                }
                            }

                            $spread_users[]=$second_user['uid'];
                            D('User')->add_money($second_user['uid'],$spread_money,'子用户推广用户 '.$now_user['nickname'].' 购买ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
                        }

                        //顶级分佣
                        $spread_where['_string'] = "uid = '{$second_user['uid']}' ";
                        $first_user_spread = D('User_spread')->where($spread_where)->find();
                        if (!empty($first_user_spread)) {
                            $first_spread_user = D('User')->get_user($first_user_spread['spread_uid']);
                            $sub_user_spread_rate = $spread_rate['third_rate'];
                            if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
                                $spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);
                                $spread_data =array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'appoint', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['appoint_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
                                if($spread_user['spread_change_uid']!=0){
                                    $spread_data['change_uid'] = 	$first_spread_user['spread_change_uid'];
                                }
                                // 处理长订单id
                                if ($now_order['real_orderid']) {
                                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                                }

                                D('User_spread_list')->data($spread_data)->add();
                                $fir_where['_string'] = "uid = '{$first_user_spread['uid']}'";
                                $fir_user = M('User')->where($fir_where)->find();

                                if($spread_money>0) {
                                    $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>L_("X1的子用户的子用户X2通过您的分享购买了商品，验证消费后您将获得佣金。",array("X1" => $fir_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $now_order['mer_id']);
                                }
                                D('User')->add_money($first_spread_user['uid'],$spread_money,'子用户推广用户 '.$now_user['nickname'].' 购买ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
                            }
                        }
                    }
                }
            }

            $sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'appoint');
            if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
                if (empty($now_order['phone'])) {
                    $user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
                }
                $sms_data['uid'] = $now_order['uid'];
                $sms_data['mobile'] = $user['phone'];
                $sms_data['sendto'] = 'user';
                $sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$this->store['name'],'X2'=>$now_order['order_id']));
                Sms::sendSms($sms_data);
            }
        } else {
            fdump_api(['appoint_order_id' => $orderId], 'appoint_pay/appoint_order', 1);
        }
        
        return true;
    }

	//余额支付
	public function balance_after_pay($order_param){
        fdump_api(['order_params'=>$order_param],'appoint_order/balance_after_pay',true);
		if($order_param['pay_type']!=''){
			$condition_appoint_order['orderid'] = $order_param['order_id'];
		}else{
			$condition_appoint_order['order_id'] = $order_param['order_id'];
		}
		$now_order = $this->field(true)->where($condition_appoint_order)->find();
		$appoint_info  =D('Appoint')->where(array('appoint_id'=>$now_order['appoint_id']))->find();

		if(empty($now_order)){
            fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
			return array('error'=>1,'msg'=>L_('当前订单不存在！'));
		}else if(!empty($now_order['product_pay_type'])){
            fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
			return array('error'=>1,'msg'=>L_('当前订单已经支付！'));
		}else if((($now_order['paid'] == 1) && ($now_order['service_status'] == 0|| $now_order['is_initiative']<=0)) || ($now_order['payment_status'] == 0&& $now_order['service_status'] == 0)){
			//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
			$now_user = D('User')->get_user($now_order['uid']);
			if(empty($now_user)){
                fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
			}

			if($now_order['product_card_id']){
				$now_coupon = D('Card_new_coupon')->get_coupon_by_id($now_order['product_card_id']);
				if(empty($now_coupon)){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//判断平台优惠券
			if($now_order['product_coupon_id']){
				$now_coupon = D('System_coupon')->get_coupon_by_id($now_order['product_coupon_id']);
				if(empty($now_coupon)){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//如果用户使用了积分抵扣，则扣除相应的积分
			//判断积分数量是否正确
			$score_used_count=$now_order['product_score_used_count'];
			if($score_used_count*1 > 0 && $now_user['score_count']<$score_used_count){
                fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
                fdump($now_user['score_count'],'api/log/'.date('Ymd').'/appoint_order',true);
                fdump($score_used_count,'api/log/'.date('Ymd').'/appoint_order',true);
                return array('error'=>1,'msg'=>L_('用户积分不足以抵扣!'));
			}
			if($score_used_count>0){
				$use_result = D('User')->user_score($now_order['uid'],$score_used_count,L_('购买 x1 扣除x2',array('x1'=>$now_order['order_name'],'x2'=>C('config.score_name'))));
				if($use_result['error_code']){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
                    fdump($use_result,'api/log/'.date('Ymd').'/appoint_order',true);
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//判断帐户余额
			$merchant_balance = floatval($now_order['product_merchant_balance']);
			if($merchant_balance){
				$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				if($user_merchant_balance['card_money'] < $merchant_balance){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}

			$card_give_money = floatval($now_order['product_card_give_money']);
			if($card_give_money){
				$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				if($user_merchant_balance['card_money_give'] < $card_give_money){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}

			$balance_pay = floatval($now_order['product_balance_pay']);
			if($balance_pay){
				if($now_user['now_money'] < $balance_pay){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的帐户余额不够此次支付！'));
				}
			}

			if($now_order['product_card_id']){
				$use_result = D('Card_new_coupon')->user_coupon($now_order['product_card_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
                    fdump($use_result,'api/log/'.date('Ymd').'/appoint_order',true);
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果使用了平台优惠券
			if($now_order['product_coupon_id']){
				$use_result = D('System_coupon')->user_coupon($now_order['product_coupon_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
                    fdump($use_result,'api/log/'.date('Ymd').'/appoint_order',true);
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}


			if($merchant_balance){
				$use_result = D('Card_new')->use_money($now_order['uid'],$now_order['mer_id'],$merchant_balance,L_('购买 x1 扣除会员卡余额',$now_order['order_name']));
				if($use_result['error_code']){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
                    fdump($use_result,'api/log/'.date('Ymd').'/appoint_order',true);
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			if($card_give_money){
				$use_result = D('Card_new')->use_give_money($now_order['uid'],$now_order['mer_id'],$card_give_money,L_('购买 x1 扣除会员卡赠送余额',$now_order['order_name']));
				if($use_result['error_code']){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
                    fdump($use_result,'api/log/'.date('Ymd').'/appoint_order',true);
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果用户使用了余额支付，则扣除相应的金额。
			if(!empty($balance_pay)){
				if($now_order['product_name']){
					$tmp_order_name = $appoint_info['appoint_name'] . ' - ' . $now_order['product_name'];
				}else{
					$tmp_order_name = $appoint_info['appoint_name'];
				}

				$use_result = D('User')->user_money($now_order['uid'],$balance_pay,L_('预约 x1 扣除余额',$tmp_order_name));
				if($use_result['error_code']){
                    fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/appoint_order',true);
                    fdump($use_result,'api/log/'.date('Ymd').'/appoint_order',true);
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

            //判断一卡通余额, 扣除余额
            $card_pay_info = json_decode($now_order['internal_card_pay'], true);

            $card = M('internal_membership_card');
            $internal_card_log = M('internal_membership_card_log');
            $time = time();
            $internal_card_pay = 0;
            if (!empty($card_pay_info)){
                foreach($card_pay_info as $card_id => $card_pay_money){
                    $card_info = $card->where(array('card_id' => $card_id))->find();
                    $card_info['balance_money'] = round($card_info['balance_money'] * 100) / 100;
                    if($card_info['balance_money'] < $card_pay_money){
                        return array('error'=>1,'msg'=>L_('您的会员卡'. $card_id .'余额不足'));
                    }
                    $save_data['balance_money'] = $card_info['balance_money'] - $card_pay_money;
                    $save_data['last_time'] = $time;
                    if(!$card->where(array('card_id' => $card_id))->save($save_data)){
                        return array('error'=>1,'msg'=>L_('会员卡'. $card_id .'支付失败!'));
                    }
                    $condition_card_log['card_id'] = $card_id;
                    $condition_card_log['operation_type'] = 2; //交易
                    $condition_card_log['before_balance'] = $card_info['balance_money'];
                    $condition_card_log['after_balance'] = $save_data['balance_money'];
                    $condition_card_log['money'] = $card_pay_money;
                    $condition_card_log['business_type'] = 'appoint';
                    $condition_card_log['order_id'] = $now_order['orderid'];
                    $condition_card_log['uid'] = $now_order['uid'];
                    $condition_card_log['describe'] = '店铺预约, 扣除余额'. $card_pay_money .'元, 订单编号'.$now_order['orderid'];
                    $condition_card_log['mer_id'] = $now_order['mer_id'];
                    $condition_card_log['store_id'] = $now_order['store_id'];
                    $condition_card_log['create_time'] = $time;
                    $internal_card_log->add($condition_card_log);
                    $internal_card_pay += $card_pay_money;
                    D('Internal_card')->send_wx_msg($card_id, '-'.getFormatNumber($card_pay_money), '店铺预约');
                }
            }

			if(empty($now_order['appoint_pass'])){
				$group_pass_array = array(
						date('y',$_SERVER['REQUEST_TIME']),
						date('m',$_SERVER['REQUEST_TIME']),
						date('d',$_SERVER['REQUEST_TIME']),
						date('H',$_SERVER['REQUEST_TIME']),
						date('i',$_SERVER['REQUEST_TIME']),
						date('s',$_SERVER['REQUEST_TIME']),
						mt_rand(10,99),
				);
				shuffle($group_pass_array);
				$data_group_order['appoint_pass'] = implode('',$group_pass_array);
			}

//            $data_group_order['pay_time'] = isset($order_param['pay_time']) && $order_param['pay_time'] ? strtotime($order_param['pay_time']) : $_SERVER['REQUEST_TIME'];
			$data_group_order['user_pay_time'] = isset($order_param['pay_time']) && $order_param['pay_time'] ? strtotime($order_param['pay_time']) : $_SERVER['REQUEST_TIME'];
			$data_group_order['user_pay_money'] = floatval($order_param['pay_money']);
			$data_group_order['product_pay_type'] = $order_param['pay_type'];
			$data_group_order['product_third_id'] = $order_param['third_id'];
			$data_group_order['product_is_mobile_pay'] = $order_param['is_mobile'];
			$data_group_order['product_real_balace_pay_time'] = $_SERVER['REQUEST_TIME'];
			$data_group_order['last_time'] = $_SERVER['REQUEST_TIME'];
			$data_group_order['product_real_balace_price'] = floatval($order_param['pay_money']);
			$data_group_order['product_is_own'] = $order_param['is_own'];
			$data_group_order['is_initiative'] = 1;
            $supplyData = [];
            $appointSupply = M('Appoint_supply')->where(['order_id' => $now_order['order_id']])->find();
            if($appointSupply['status'] == 3){//技师已服务
                $data_group_order['service_status'] = 1;//订单已完成
            }
            if($now_order['merchant_worker_id']){//服务员工服务费
                $orderPrice = floatval($now_order['product_price']) > 0 ? $now_order['product_price'] : $now_order['appoint_price'];
                $supplyData['commission'] = D('Merchant_workers')->appointServiceFee($now_order['merchant_worker_id'], $orderPrice);
                if($appointSupply['status'] < 2) {
                    $supplyData['status'] = 2;//订单待服务
                }
            }
            if ($now_order['payment_status'] || !empty($order_param['settlement'])) {
                $data_group_order['service_status'] = 1;//订单已完成
                $supplyData['status'] = 3;//订单已服务
            }
			$data_group_order['paid'] = 1;
			if($this->where($condition_appoint_order)->data($data_group_order)->save()){
                $supplyData['paid'] = 1;
                fdump_api(['supply' => $supplyData],'appoint_order/balance_after_pay',1);
                M('Appoint_supply')->where(['order_id' => $now_order['order_id']])->save($supplyData);
				/*分析粉丝行为*/
				D('Merchant_request')->add_request($now_order['mer_id'],array('appoint_buy_count'=>1,'appoint_buy_money'=>$now_order['payment_money']));

				D('Scroll_msg')->add_msg('appoint',$now_user['uid'],L_('用户x1于x2购买x3成功',array('x1'=>str_replace_name($now_user['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>C('config.appoint_alias_name'))));

				$condition_group['appoint_id'] = $now_order['appoint_id'];
				D('Appoint')->where($condition_group)->setInc('appoint_sum',1);
				$now_order = $this->field(true)->where($condition_appoint_order)->find();
				if($now_order["is_initiative"] && $now_order["service_status"]){
					$payment_money = $now_order['user_pay_money'] + $now_order['pay_money'];
				}else if($now_order["is_initiative"] && !$now_order["service_status"]){
					$payment_money = $now_order['user_pay_money'];
				}else{
					$payment_money = $now_order['pay_money'];
				}

				$payment_money = $now_order['product_balance_pay']+$now_order['product_merchant_balance']+$now_order['product_card_give_money']+$now_order['user_pay_money']+$now_order['product_coupon_price']+$now_order['product_card_price']+$now_order['product_score_deducte'];

				if ($now_user['openid'] && $order_param['is_mobile']) {
					$href = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => L_('预约余款支付提醒'), 'keyword1' => $appoint_info['appoint_name'], 'keyword2' => $now_order['order_id'], 'keyword3' => $payment_money, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('预约成功，您的消费码：').$now_order['appoint_pass']));
				}
				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'appoint');
				if (C('config.sms_success_order') == 1 || C('config.sms_success_order') == 3) {
					$sms_data['uid'] = $now_order['uid'];
					$sms_data['mobile'] = $now_user['phone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('您预约 x1的订单(订单号：x2)已经完成支付,您的消费码：x3',array('x1'=>$now_order['order_name'],'x2'=>$now_order['order_id'],'x3'=>$now_order['appoint_pass']));
					Sms::sendSms($sms_data);
				}

				if (C('config.sms_success_order') == 2 || C('config.sms_success_order') == 3) {
					$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $merchant['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_('顾客预约的x1的订单(订单号：x2),在x3时已经完成了支付！',array('x1'=>$now_order['order_name'],'x2'=>$now_order['order_id'],'x3'=>date('Y-m-d H:i:s')));
					Sms::sendSms($sms_data);
				}

				$database_appoint_visit_order_info = D('Appoint_visit_order_info');
				$worker_where['appoint_order_id'] = $now_order['order_id'];
				$now_worker = $database_appoint_visit_order_info->appoint_visit_order_detail($worker_where);
				$now_worker = $now_worker['detail'];

				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'], 'type' => 'appoint');
				if((C('config.worker_sms_success_order') == 1) && !empty($now_worker)){
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $now_worker['mobile'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('用户在x1时，购买了预约服务，已成功支付，订单号：x2',array('x1'=>date('Y-m-d H:i',time()),'x2'=>$now_order['order_id']));
					Sms::sendSms($sms_data);
				}
                $merchant_workers_id = $now_order['merchant_workers_id'];
                // 处理推送给对应的预约工作人员
                if(!empty($merchant_workers_id)){
                    // 存在工作人员id 直接推送
                    $database_merchant_workers = M('Merchant_workers');
                    $worker_where_info = array();
                    $worker_where_info['merchant_worker_id'] = $merchant_workers_id;
                    $now_worker = $database_merchant_workers->where($worker_where_info)->find();
                    if ($now_worker['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $first = L_('用户在x1时，购买了预约服务，请及时查看，订单号：x2',array('x1'=>date('Y-m-d H:i',time()),'x2'=>$now_order['order_id']));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                        $model->sendTempMsg('TM00017',
                            array(
                                'href' => $href,
                                'wecha_id' => $now_worker['openid'],
                                'first' => $first,
                                'OrderSn' => $now_order['order_id'],
                                'OrderStatus' => L_('支付成功'),
                                'remark' => '\n'.L_('请点击查看详细信息！')
                            )
                        );
                    }
                } else {
                    // 查询下商家店铺下所有工作人员
                    $database_merchant_workers = M('Merchant_workers');
                    $worker_where_info = array();
                    $worker_where_info['mer_id'] = $now_order['mer_id'];
                    $worker_where_info['merchant_store_id'] = $now_order['store_id'];
                    $worker_where_info['status'] = 1;
                    $worker_where_info['openid'] = array('neq', '');
                    $workers = $database_merchant_workers->where($worker_where_info)->select();
                    if ($workers) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $first = L_('用户在x1时，购买了预约服务，请及时抢单，订单号：x2',array('x1'=>date('Y-m-d H:i',time()),'x2'=>$now_order['order_id']));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=grab';
                        foreach ($workers as $val) {
                            $model->sendTempMsg('TM00017',
                                array(
                                    'href' => $href,
                                    'wecha_id' => $val['openid'],
                                    'first' => $first,
                                    'OrderSn' => $now_order['order_id'],
                                    'OrderStatus' => L_('支付成功'),
                                    'remark' => '\n'.L_('请点击查看详细信息！')
                                )
                            );
                        }
                    }
                }
				//小票打印start
				$printHaddle = new PrintHaddle();
				$printHaddle->printit($now_order['order_id'], 'appoint_order', 1);
				//小票打印end

				$now_order = $this->field(true)->where($condition_appoint_order)->find();
				$order_info['order_type'] = 'appoint';
				$order_info['mer_id'] = $now_order['mer_id'];
				$order_info['order_id'] = $now_order['order_id'];
				$order_info['store_id'] = $now_order['store_id'];
				$order_info['balance_pay'] = $now_order['balance_pay'];
				$order_info['score_deducte'] = $now_order['score_deducte'];
				$order_info['payment_money'] = $now_order['pay_money'];
				$order_info['is_own'] = $now_order['product_is_own'];
				$order_info['merchant_balance'] = $now_order['merchant_balance'];
				$order_info['score_used_count'] = $now_order['score_used_count'];
				$order_info['user_pay_money'] = $now_order['user_pay_money'];
				$order_info['product_score_deducte'] = $now_order['product_score_deducte'];
				$order_info['product_balance_pay'] = $now_order['product_balance_pay'];
				$order_info['product_score_used_count'] = $now_order['product_score_used_count'];
				$order_info['product_coupon_price'] = $now_order['product_coupon_price'];
				$order_info['product_merchant_balance'] = $now_order['product_merchant_balance'];
				if($order_info['is_own']>0){
					$order_info['payment_money'] = 0;
					$order_info['user_pay_money'] = 0;
				}
				$order_info['total_money'] = $order_info['money'] = $order_info['balance_pay']
						+ $order_info['score_deducte']
						+ $order_info['payment_money']
						+ $order_info['merchant_balance']
						+ $order_info['product_score_deducte']
						+ $order_info['product_balance_pay']
						+ $order_info['product_coupon_price']
						+ $order_info['user_pay_money']
						+ $order_info['product_merchant_balance'];

				$order_info['payment_money'] = $now_order['pay_money'] + $now_order['user_pay_money'];
				$order_info['balance_pay'] = $now_order['balance_pay'] + $now_order['product_balance_pay'];
				$order_info['merchant_balance'] = $now_order['merchant_balance'] + $now_order['product_merchant_balance'];
				$order_info['card_give_money'] = $now_order['card_give_money'] + $now_order['product_card_give_money'];
				$order_info['uid'] = $now_order['uid'];
				$order_info['desc'] = L_('用户预约X1记入收入',$appoint_info['appoint_name']);
				$order_info['score_discount_type']=$now_order['score_discount_type'];
				$order_info['distributor_uid']=$now_order['distributor_uid'];

				/* 计算用户分销获得佣金 通过分享链接直接购买才有分佣一般只有一个商品*/
				if (C('config.open_single_spread')==1&&$now_order['distributor_uid']&&$appoint_info['distributor_percent']) {
					$distributor_spread_data = array();
					$spread_total_money = $now_order['product_balance_pay']+$now_order['user_pay_money'];
					fdump($now_order['balance_pay'],'spread');
					fdump($now_order['payment_money'],'spread',1);
					$distributor_spread_money = round($spread_total_money*$appoint_info['distributor_percent']/100,2);
					fdump($distributor_spread_money,'spread',1);
					if ($distributor_spread_money) {
						$distributor_spread_data =array(
							'spread_uid' => $now_order['distributor_uid'], 
							'order_uid' => $now_order['uid'],  
							'money' => $distributor_spread_money, 
							'order_type' => 'appoint',
							'order_id' => $now_order['order_id'],
							'real_orderid' => $now_order['real_orderid'],
							'third_id' => $now_order['appoint_id'],
							'add_time' => $_SERVER['REQUEST_TIME']
						);
						M('Distributor_spread_list')->data($distributor_spread_data)->add();
					}
				}
                fdump_api($order_info,'appoint/balance_after_pay',1);
				if ($now_order['payment_status'] || !empty($order_param['settlement'])) {
                    D('SystemBill')->bill_method($order_info['is_own'],$order_info);
                    $this->change_status($now_order['order_id'],1);//跟产品沟通后,确定尾款支付后,状态改成已服务2020/1/2 15:16修改为1
                } else {
                    /*----系统订单信息----*/
                    D('System_order')->save_reg_order_info('appoint',$now_order['order_id'],$data_group_order);
                }
				//增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store
				D('Merchant')->saverelation($now_user['openid'], $now_order['mer_id'], 8);

				//微信派发优惠券 支付到平台 微信支付
				if($order_param['is_own']==0 && $order_param['pay_type']=='weixin' && $order_param['pay_money']>=C('config.weixin_send_money')){
					D('System_coupon')->weixin_send($order_param['pay_money'],$order_info['uid']);
				}

                $score_param = [
                    'mer_id' => $now_order['mer_id'],
                    'store_id' => $now_order['store_id'],
                    'order_id' => $now_order['order_id'],
                    'order_type' => 'appoint'
                ];
				//验证服务才给用户送积分
				if($data_group_order['service_status']  == 1){
					if(C('config.open_extra_price')==1){
						$score = D('Percent_rate')->get_extra_money($now_order);
						if($score>0){
							D('User')->add_score($now_order['uid'], $score,L_('购买x1商品获得x2',array('x1'=>C('config.appoint_alias_name'),'x2'=>C('config.score_name'))),0,$score_param);
						}
					}
					if ($appoint_info['score_percent'] > 0) {
						$score_get = $appoint_info['score_percent'];
					} else if ($this->config['add_score_by_system_commission'] == 0 && C('config.add_score_by_percent') == 0 && (C('config.open_score_discount') == 0 || $now_order['score_discount_type'] != 2)) {
						if (C('config.open_score_get_percent') == 1) {
							$score_get = C('config.score_get_percent') / 100;
						} else {
							$score_get = C('config.user_score_get');
						}
						$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
						if ($now_merchant['score_get_percent'] >= 0) {
							$score_get = $now_merchant['score_get_percent'] / 100;
						}
						if ($order_info['is_own'] && C('config.user_own_pay_get_score') != 1) {
							$score_get = 0;
						}

						$score_get = D('Percent_rate')->get_user_add_score_times($score_get, $order_info['uid']);
					}
					$score_get > 0 && D('User')->add_score($now_order['uid'], (($now_order['balance_pay'] + $now_order['product_balance_pay'] + $now_order['pay_money'] + $now_order['user_pay_money']) * $score_get), L_('购买预约商品获得X1', C('config.score_name')), 0, $score_param);
				}

				D('Merchant_spread')->add_spread_list($order_info,$now_user,'appoint',$now_user['nickname'].L_("用户购买预约获得佣金"));

                if ($now_order['payment_status'] || !empty($order_param['settlement'])) {
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'appoint';
                    $params['order_id'] = $now_order['order_id'];
                    $params['mer_id'] = $now_order['mer_id'];
                    $params['store_id'] = $now_order['store_id'];
                    $params['uid'] = $now_order['uid'];
                    $params['title'] = '核销成功,已完成服务';
                    $params['content'] = '您预约的服务已经完成，请查看订单';
                    invoke_v20_service($url, [$params]);
                }else{
                    //增加消息通知
                    $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                    $params['type'] = 0;
                    $params['business'] = 'appoint';
                    $params['order_id'] = $now_order['order_id'];
                    $params['mer_id'] = $now_order['mer_id'];
                    $params['store_id'] = $now_order['store_id'];
                    $params['uid'] = $now_order['uid'];
                    $params['title'] = '商品下单成功';
                    $params['content'] = '下单成功，查看订单详情';
                    invoke_v20_service($url, [$params]);
                }

				if($order_param['is_mobile']){
					return array('error'=>0,'url'=>str_replace('/source/','/',U('My/appoint_order',array('order_id'=>$now_order['order_id']))));
				}else{
					return array('error'=>0,'url'=>U('User/Index/appoint_order_view',array('order_id'=>$now_order['order_id'])));
				}
			}else{
				return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
			}
		}else{
			if($now_order['service_status'] == 1){
				return array('error'=>0,'url'=>U('User/Index/appoint_order_view',array('order_id'=>$now_order['order_id'])));
			}
			return array('error'=>1,'msg'=>L_('修改订单失败，请联系系统管理员！'));
		}
	}
	//支付时，金额不够，记录到帐号
	public function wap_after_pay_error($now_order,$order_param,$error_tips){
		//记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。
        $user_result['error_code'] = 0;
        if($order_param['pay_money'] > 0){
            $alreadyMoney = M("User_money_list")
                ->where(['money'=>$order_param['pay_money'],'uid'=>$now_order['uid'],'ask'=> 10, 'ask_id'=>$now_order['order_id']])//10 appoint_order
                ->order('pigcms_id desc')
                ->find();
            if(empty($alreadyMoney)){
                //$user_result = D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_('支付订单：x1发生错误！原因是：x2',array('x1'=>$now_order['order_id'],'x2'=>$error_tips)),10,$now_order['order_id']);
            }
        }
		if($order_param['pay_type']=='weixin'&&ACTION_NAME=='return_url'){
			exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
		}
		if($user_result['error_code']){
			return array('error'=>1,'msg'=>$user_result['msg']);
		}else{
			if($order_param['is_mobile']){
				$return_url = str_replace('/source/','/',U('My/appoint_order',array('order_id'=>$now_order['order_id'])));
			}else{
				$return_url = U('User/Index/appoint_order_view',array('order_id'=>$now_order['order_id']));
			}
			return array('error'=>1,'msg'=>$error_tips.L_('已将您充值的金额添加到您的余额内。'),'url'=>$return_url);
		}
	}

	// 不需要付款
	public function no_pay_after($order_id,$appoint_info,$is_mobile=1){
		fdump($order_id,'api/log/'.date('Ymd').'/appoint_order',true);
		fdump($appoint_info,'api/log/'.date('Ymd').'/appoint_order',true);
		$condition_group_order['order_id'] = $order_id;
		$now_order = $this->field(true)->where($condition_group_order)->find();
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在！'));
		}else{
			//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
			$now_user = D('User')->get_user($now_order['uid']);
			if(empty($now_user)){
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
			}

			$condition_group_order['order_id'] = $order_id;

			$group_pass_array = array(
					date('y',$_SERVER['REQUEST_TIME']),
					date('m',$_SERVER['REQUEST_TIME']),
					date('d',$_SERVER['REQUEST_TIME']),
					date('H',$_SERVER['REQUEST_TIME']),
					date('i',$_SERVER['REQUEST_TIME']),
					date('s',$_SERVER['REQUEST_TIME']),
					mt_rand(10,99),
			);
			shuffle($group_pass_array);
			$data_group_order['appoint_pass'] = implode('',$group_pass_array);
			if($this->where($condition_group_order)->data($data_group_order)->save()){

				/*分析粉丝行为*/
				D('Merchant_request')->add_request($now_order['mer_id'],array('appoint_buy_count'=>1));

				$condition_group['appoint_id'] = $now_order['appoint_id'];
				//D('Appoint')->where($condition_group)->setInc('appoint_sum',1);

				if ($now_user['openid'] && $is_mobile) {
					$href = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => L_('预约提醒'), 'keyword1' => $appoint_info['appoint_name'], 'keyword2' => $now_order['order_id'], 'keyword3' => date('Y-m-d H:i:s'), 'remark' => L_('预约成功，您的消费码：').$data_group_order['appoint_pass']));
				}
				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'appoint');
				if (C('config.sms_success_order') == 1 || C('config.sms_success_order') == 3) {
					$sms_data['uid'] = $now_order['uid'];
					$sms_data['mobile'] = $now_order['phone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('您预约 x1的订单(订单号：x2)已经完成支付,您的消费码：x3',array('x1'=>$appoint_info['appoint_name'],'x2'=>$now_order['order_id'],'x3'=>$data_group_order['appoint_pass']));
					Sms::sendSms($sms_data);
				}
				if (C('config.sms_success_order') == 2 || C('config.sms_success_order') == 3) {
					$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $merchant['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_('顾客预约的x1的订单(订单号：x2),在x3时已经完成！',array('x1'=>$now_order['order_name'],'x2'=>$now_order['order_id'],'x3'=>date('Y-m-d H:i:s')));
					Sms::sendSms($sms_data);
				}

				if($is_mobile){
					return array('error'=>0,'url'=>str_replace('/source/','/',U('My/appoint_order',array('order_id'=>$now_order['order_id']))));
				}else{
					return array('error'=>0,'url'=>U('User/Index/appoint_order_view',array('order_id'=>$now_order['order_id'])));
				}
			}else{
				return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}


	//修改订单状态
	public function change_status($order_id, $status)
	{
		$condition_appoint_order['order_id'] = $order_id;
		$data_appoint_order['service_status'] = $status;//2已评价
		if($this->where($condition_appoint_order)->data($data_appoint_order)->save()){
			return true;
		}else{
			return false;
		}
	}

	public function get_rate_order_list($uid, $is_rate = false, $is_wap = false)
	{
		$mod = new Model();
		if ($is_rate) {
			$sql = "SELECT `a`.`pic` as list_pic, `a`.*, `o`.*, `r`.* FROM  " . C('DB_PREFIX') . "appoint AS a INNER JOIN " . C('DB_PREFIX') . "appoint_order AS o ON `a`.`appoint_id`=`o`.`appoint_id` INNER JOIN " . C('DB_PREFIX') . "reply AS r ON r.order_id=o.order_id WHERE `o`.`uid`='{$uid}' AND `o`.`paid`<>4 AND `o`.`service_status`=2 ORDER BY `o`.`order_id` DESC";
		} else {
			$sql = "SELECT `a`.`pic` as list_pic, `a`.*, `o`.* FROM  " . C('DB_PREFIX') . "appoint AS a INNER JOIN " . C('DB_PREFIX') . "appoint_order AS o ON `a`.`appoint_id`=`o`.`appoint_id` WHERE `o`.`uid`='{$uid}' AND `o`.`paid`<>4 AND `o`.`service_status`=1 ORDER BY `o`.`order_id` DESC";
		}

		$order_list = $mod->query($sql);
		$appoint_image_class = new appoint_image();
		foreach ($order_list as &$row) {
			$tmp_pic_arr = explode(';', $row['list_pic']);
			$row['list_pic'] = $appoint_image_class->get_image_by_path($tmp_pic_arr[0], 's');
			$row['url'] = $this->get_appoint_url($row['appoint_id'], $is_wap);

			if($is_rate){
				$row['comment'] = stripslashes($row['comment']);
				if($row['pic']){
					$tmp_array = explode(',', $row['pic']);
					$row['pic_count'] = count($tmp_array);
				}
			}
		}
		return $order_list;
	}

	public function get_appoint_url($appoint_id, $is_wap)
	{
		if ($is_wap) {
			return U('Wap/Appoint/detail', array('appoint_id' => $appoint_id));
		} else {
			return C('config.site_url') . '/appoint/' . $appoint_id . '.html';
		}
	}
	
	//copy from cms/Lib/Action/Merchant/StoreAction.class.php  appoint_reply_refund
	public function appoint_reply_refund($store)
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
        $type = isset($_POST['type']) ? htmlspecialchars(trim($_POST['type'])) : 'agree';
        $reply_content = isset($_POST['reply_content']) ? htmlspecialchars(trim($_POST['reply_content'])) : '';
        $order = D('Appoint_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $store['store_id']))->find();
        if (empty($order)) {
            exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('订单信息错误,不是该店铺订单'))));
        }
        if (!in_array($type, array('agree', 'disagree'))) {
            exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('请选择正确的回复形式'))));
        }

        $refund = D('Appoint_order_refund')->field(true)->where(array('order_id' => $order_id, 'id' => $refund_id,'status' => array('neq',4)))->find();
        if (empty($refund)) {
            exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('订单的预约信息错误'))));
        }
        if ($refund['status'] != 0) {
            exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('当前的预约信息无需修改'))));
        }

        if($type == 'agree'){//同意退款
            $refundMoney = $refund;
            $score_used_count=0;
            $score_deducte=0;

            if ($refundMoney['score_deducte']>0 ) {
                $score_used_count = $refundMoney['score_used_count'];
                $score_deducte = $refundMoney['score_deducte'];
            }

            $merchant_balance = 0;
            $card_give_money = 0;

            if ($refundMoney['card_give_money']>0 ) {
                $card_give_money = $refundMoney['card_give_money'];
            }
            if ($refundMoney['merchant_balance']>0 ) {
                $merchant_balance = $refundMoney['merchant_balance'];
            }

            $balance_pay = 0;
            if ($refundMoney['balance_pay'] > 0) {
                $balance_pay = $refundMoney['balance_pay'];
            }

            $balance_payment_money = 0; //余额支付定金
            if ($refundMoney['balance_payment_money'] > 0) {
                $balance_payment_money = $refundMoney['balance_payment_money'];
            }

            /*退还的平台E卡的金额*/
            if($refundMoney['ecard_pay']>0){
                $ecard_pay=$refundMoney['ecard_pay'];
            }
            $payment_money = 0;

            if ($refundMoney['payment_money'] > 0) {

                $payment_money = $refundMoney['payment_money'];
                $pay_type = $order['product_pay_type'] ? $order['product_pay_type']: $order['pay_type'];
                $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['orderid'],'order_type'=>'balance-appoint','pay_type'=>$pay_type,'paid_money'=>array('gt',0)))->find();
                if ($order['is_own']) {
                    $pay_method = array();
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
                    foreach ($merchant_ownpay as $ownKey => $ownValue) {
                        $ownValueArr = unserialize($ownValue);
                        if ($ownValueArr['open']) {
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
                        }
                    }
                } else {
                    if ($order['product_pay_type'] && $order['product_pay_type']=='alipayh5') {
                        $is_wap = 1;
                    } else {
                        $is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
                    }
                    $pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0);
                }

                if (empty($pay_method)) {
                    exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('支付方式不存在或已关闭'))));
                }
                if(empty($order['pay_type'])){
                    if ($order['product_pay_type'] && empty($pay_method[$order['product_pay_type']])) {
                        exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('支付方式不存在或已关闭'))));
                    }
                }else{
                    if ($order['pay_type'] && empty($pay_method[$order['pay_type']])) {
                        exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('支付方式不存在或已关闭'))));
                    }
                }

                if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba')){
                    if($payment_money_order['pay_method'] == 'wft'){
                        $pay_method = 'wftpay';
                    }
                    if($payment_money_order['pay_method'] == 'paylinx'){
                        $pay_method = 'paylinx';
                    }
                    if($payment_money_order['pay_method'] == 'weikebaba'){
                        $pay_method = 'weikebaba';
                    }
                    import('@.ORG.LowFeePay');
                    $lowfeepay = new LowFeePay($pay_method);
                    $order['order_type'] = 'appoint';
                    $order['orderNo'] = $payment_money_order['orderNo'];
                    if($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $order['is_own'] =1 ;
                            $order['orderNo'] =$order['orderNo'];
                        }

                    }
                    $go_refund_param= $lowfeepay->refund($order);
                    fdump($go_refund_param,'lumin_refund',true);
                } else if ($order['product_pay_type'] || $order['pay_type']) {
                    if($order['pay_type'] == ''){
                        $pay_class_name = ucfirst($order['product_pay_type']);
                    }else{
                        $pay_class_name = ucfirst($order['pay_type']);
                    }
                    $import_result = import('@.ORG.pay.' . $pay_class_name);
                    if (empty($import_result)) {
                        exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('支付方式不存在或已关闭'))));
                    }

                    $order['order_type'] = 'appoint';
                    $order['order_id'] = $order['orderid'];
                    if ($order['order_from'] == 4) {
                        $pay_method['weixin']['config'] = array(
                            'pay_weixin_appid' => C('config.pay_wxapp_appid'),
                            'pay_weixin_key' => C('config.pay_wxapp_key'),
                            'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
                            'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
                        );
                        if ($order['is_own'] == 1) {
                            $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
                            $pay_method['weixin']['config'] = array(
                                'pay_weixin_appid' => $wxapp_own_pay['appid'],
                                'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
                                'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
                                'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
                                'is_own' => 1
                            );
                        }
                    }

                    $now_user = D('User')->get_user($order['uid']);
                    if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
                        $pay_class_name = 'AlipayApp';
                        $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
                        $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
                        $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
                    }
                    $order['order_type'] = 'balance-appoint';
                    $order['order_id'] = $order['orderid'];
                    $order['third_id'] = '';
                    $order['payment_money'] = $payment_money;

                    $import_result = import('@.ORG.pay.' . $pay_class_name);
                    if($order['pay_type'] == ''){
                        $pay_class = new $pay_class_name($order, $payment_money, $order['product_pay_type'], $pay_method[$order['product_pay_type']]['config'], $now_user, 1);
                    }else{
                        $pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
                    }
                    fdump($pay_class,'weixin',1);
                    $go_refund_param = $pay_class->refund();
                    fdump($go_refund_param,'weixin',1);
                }

                if ($go_refund_param && !(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
                    exit(json_encode(array('errorCode' => 1, 'errorMsg' => $go_refund_param['msg'])));
                } else {
                    $third_refund_id = $go_refund_param['refund_param']['refund_id'];
                }
            }
            $mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();

            $order_type='appoint';
            if ($score_used_count != 0) {
                $result = D('User')->add_score($order['uid'], $score_used_count,'预约退款, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
                $param = array('refund_time' => time());
                if ($result['error_code']) {
                    $score_used_count = 0;
                }
            }


            //定金
            if ($refundMoney['online_payment_money'] > 0) {

                $payment_money = $refundMoney['online_payment_money'];
                $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['order_id'],'order_type'=>'appoint','pay_type'=>$order['pay_type'],'paid_money'=>array('gt',0)))->find();
                if ($order['is_own']) {
                    $pay_method = array();
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
                    foreach ($merchant_ownpay as $ownKey => $ownValue) {
                        $ownValueArr = unserialize($ownValue);
                        if ($ownValueArr['open']) {
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
                        }
                    }
                } else {
                    $is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
                    $pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0);
                }

                if (empty($pay_method)) {
                    exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('支付方式不存在或已关闭'))));
                }
                if (empty($pay_method[$order['pay_type']])) {
                    exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('支付方式不存在或已关闭'))));
                }

                if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba')){
                    if($payment_money_order['pay_method'] == 'wft'){
                        $pay_method = 'wftpay';
                    }
                    if($payment_money_order['pay_method'] == 'paylinx'){
                        $pay_method = 'paylinx';
                    }
                    if($payment_money_order['pay_method'] == 'weikebaba'){
                        $pay_method = 'weikebaba';
                    }
                    import('@.ORG.LowFeePay');
                    $lowfeepay = new LowFeePay($pay_method);
                    $order['order_type'] = 'appoint';
                    $order['orderNo'] = $payment_money_order['orderNo'];
                    if($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $order['is_own'] =1 ;
                            $order['orderNo'] =$order['orderNo'];
                        }

                    }
                    $go_refund_param= $lowfeepay->refund($order);
                    fdump($go_refund_param,'lumin_refund',true);
                } else {
                    fdump($order['pay_type'],'weixin',1);
                    $pay_class_name = ucfirst($order['pay_type']);
                    $import_result = import('@.ORG.pay.' . $pay_class_name);
                    if (empty($import_result)) {
                        exit(json_encode(array('errorCode' => 1, 'errorMsg' => L_('支付方式不存在或已关闭'))));
                    }

                    $order['order_type'] = 'appoint';
                    $order['order_id'] = $order['orderid'];
                    if ($order['order_from'] == 4) {
                        $pay_method['weixin']['config'] = array(
                            'pay_weixin_appid' => C('config.pay_wxapp_appid'),
                            'pay_weixin_key' => C('config.pay_wxapp_key'),
                            'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
                            'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
                        );
                        if ($order['is_own'] == 1) {
                            $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
                            $pay_method['weixin']['config'] = array(
                                'pay_weixin_appid' => $wxapp_own_pay['appid'],
                                'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
                                'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
                                'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
                                'is_own' => 1
                            );
                        }
                    }

                    $now_user = D('User')->get_user($order['uid']);
                    if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
                        $pay_class_name = 'AlipayApp';
                        $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
                        $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
                        $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
                    }
                    $order['order_type'] = 'appoint';
                    if($order['pay_type'] == 'alipayh5'){
                        $order['order_id'] = $order['orderid'];
                    }else{
                        if(empty($order['orderids'])){
                            $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order_id,'order_type'=>'appoint'))->find();
                            $order['order_id'] = $payment_money_order['orderid'];
                        }else{
                            $order['order_id'] = $order['orderids'];
                        }
                    }
                    $order['third_id'] = '';
                    $order['payment_money'] = $payment_money;

                    $import_result = import('@.ORG.pay.' . $pay_class_name);
                    $pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
                    fdump($pay_class,'weixins',1);
                    $go_refund_param = $pay_class->refund();
                    fdump($go_refund_param,'weixins',1);
                }

                if (!(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
                    exit(json_encode(array('errorCode' => 1, 'errorMsg' => $go_refund_param['msg'])));
                } else {
                    $third_refund_id = $go_refund_param['refund_param']['refund_id'];
                }
            }
            $mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();

            $order_type='appoint';
            if ($score_used_count != 0) {
                $result = D('User')->add_score($order['uid'], $score_used_count,'预约退款, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
                $param = array('refund_time' => time());
                if ($result['error_code']) {
                    $score_used_count = 0;
                }
            }

            //平台余额退款
            if ($balance_pay > 0) {
                $result = D('User')->add_money($order['uid'], $balance_pay, '预约退款,增加余额,订单编号' . $order['real_orderid']);
                fdump("22222",'test1',1);
                if($result['error_code']){
                    $balance_pay = 0;
                }
            }

            if ($balance_payment_money > 0) {
                $result = D('User')->add_money($order['uid'], $balance_payment_money, '预约定金退款,增加余额,订单编号' . $order['real_orderid']);
                if($result['error_code']){
                    $balance_pay = 0;
                }
            }
            //平台E卡退款返回余额
            if($ecard_pay>0){
                $result=D('User')->add_money($order['uid'],$ecard_pay,'预约退款，'.C('config.ecard_alias_name').'退还增加余额'.$order['real_orderid']);
                if($result['error_code']){
                    $ecard_pay=0;
                }
            }

            //商家会员卡余额退款
            if ($merchant_balance > 0 || $card_give_money > 0) {
                $result = D('Card_new')->add_user_money($order['mer_id'], $order['uid'],  $merchant_balance, $card_give_money, 0,  '预约退款,增加会员卡赠送余额,订单编号' . $order['real_orderid'],'预约退款,增加会员卡赠送余额,订单编号' . $order['real_orderid']);
                if ($result['error_code']) {
                    $merchant_balance = 0;
                    $card_give_money = 0;
                }
            }
            $data = array();
            $data['reply_content'] = $reply_content;
            $data['reply_time'] = time();
            $data['status'] = 1;
            $data['score_deducte'] = $score_deducte;
            $data['score_used_count'] = $score_used_count;
            $data['balance_pay'] = $balance_pay;
            $data['ecard_pay']=$ecard_pay;
            $data['merchant_balance'] = $merchant_balance;
            $data['card_give_money'] = $card_give_money;
            $data['payment_money'] = $payment_money;
            //$data['third_refund_id'] = $third_refund_id;

            D('Appoint_refund_log')->add(array('refund_id' => $refund_id, 'status' => 1, 'dateline' => time(), 'note' => $reply_content));
            D('Appoint_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save($data);

	        $recordContent['content'] = [
		        [
			        'title' => '退款理由',
			        'value' => $refund['reason'],
			        'type'  => 'text'
		        ],[
			        'title' => '退款时间',
			        'value' => date('Y-m-d H:i:s'),
			        'type'  => 'text'
		        ],
	        ];
	        D('Appoint_order_records')->addOrderRecord($order['mer_id'], $order['order_id'], '订单退款', $recordContent);

            $saveData = array('status' => 4);
            $mdata = D('Merchant_money_list')->field('money,total_money,percent,type,system_take')->where(array('_string' => "type='appoint'",'income'=>1, 'order_id' => $order_id))->find();
            if ($mdata) {
                $totalMoney = floatval(round($mdata['money'], 2));
                if ($totalMoney<0) {
                    $totalMoney=0;
                }
                $returnSystemTake = $returnTakePercent = 0;
                if($mdata['system_take'] > 0){
                    $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                    $returnTakePercent = $mdata['percent'];
                }
                D('Merchant_money_list')->use_money(
					$order['mer_id'], 
					$totalMoney, 
					$mdata['type'],
					'预约退款,减少余额,订单编号' . $order['real_orderid'], 
					$order_id, 
					$returnTakePercent, 
					$returnSystemTake,
					[],
					$order['store_id'],
					$go_refund_param['refund_param']??[]
				);
            }
            D('Appoint_order')->where(array('order_id' => $order_id))->save(array('is_apply_refund'=>0,'paid'=>2));
            /*更新平台订单状态*/
            D('System_order')->save_order_info($order_type,$order_id,$saveData);
            exit(json_encode(array('errorCode' => 0, 'msg' => 'ok')));

        }else{
            //不同意
            M('Appoint_refund_log')->add(array('refund_id'=>$refund_id,'status'=>2,'dateline' => time(),'note'=>$reply_content));
            M('Appoint_order_refund')->where(array('order_id'=>$order_id,'id'=>$refund_id))->save(array('reply_time'=>time(),'reply_content'=>$reply_content,'status'=>2));
            D('Appoint_order')->where(array('order_id' => $order_id))->save(array('is_apply_refund'=>0));
            //D('Appoint_order')->where(array('order_id' => $order_id, 'id' => $refund_id))->save(array('reply_time' => time(), 'reply_content' => $reply_content, 'status' => 2));
            exit(json_encode(array('errorCode' => 0, 'msg' => 'ok')));
        }
    }

    public function getGoodsDetail($orderId)
    {
        return self::where(['order_id'=> $orderId])
            ->field('appoint_id goods_id, order_name as goods_name, product_price as price, 1 as goods_num')
            ->select();
    }
    
    public function changeOrderServiceFee($now_order)
    {
        $orderPrice = floatval($now_order['product_price']) > 0 ? $now_order['product_price'] : $now_order['appoint_price'];
        $commission = D('Merchant_workers')->appointServiceFee($now_order['merchant_worker_id'], $orderPrice);
        return D('AppointSupply')
            ->where(['order_id' => $now_order['order_id']])
            ->setField('commission',$commission);
    }

    /**
     * 赠送优惠券
     */
    public function giveUserCoupon($order_id)
    {
        try {
            $appoint_order = self::where(['order_id' => $order_id])->find();
            if (!$appoint_order) {
                throw new \think\Exception('订单不存在');
            }
            $appoint_goods = D('Appoint')->where(['appoint_id' => $appoint_order['appoint_id']])->find();
            if (!$appoint_goods || !$appoint_goods['buy_give_coupon_id']) {
                throw new \Exception('未配置赠送优惠券');
            }
            $res = D('Card_new_coupon')->had_pull($appoint_goods['buy_give_coupon_id'], $appoint_order['uid'], '', true, false, 0, true, 0, true);
            if ($res['error_code'] != 0) {
                throw new \Exception(json_encode($res));
            }
            if (!empty($res['hadpull_id'])) {
                self::where(['order_id' => $order_id])->save([
                    'buy_give_mer_coupon_id' => $appoint_goods['buy_give_coupon_id']
                    , 'buy_give_mer_coupon_hadpull_id' => $res['hadpull_id']
                ]);
            } else {
                fdump_api([$order_id, $res], 'appoint_order/giveUserCoupon', 1);
            }
        } catch (\Exception $e) {
            fdump_api([$order_id, $e->getMessage()], 'appoint_order/giveUserCoupon', 1);
        }
    }

    /**
     * 退回优惠券
     * @param $order_id
     */
    public function returnGiveUserCoupon($order_id)
    {
        try {
            $appoint_order = self::where(['order_id' => $order_id])->find();
            if (!$appoint_order) {
                throw new \think\Exception('订单不存在');
            }
            if (!$appoint_order['buy_give_mer_coupon_hadpull_id']) {
                throw new \Exception('未配置赠送优惠券');
            }
            $res = D('Card_new_coupon_hadpull')->where(['id' => $appoint_order['buy_give_mer_coupon_hadpull_id']])->find();
            if (!$res) {
                throw new \Exception('领取的优惠券不见了');
            }
            if ($res['is_use'] != 1) {
                $save_data = [
                    'note' => L_('X1订单编号为：X2退款回收优惠券', ['X1' => C('config.appoint_alias_name'), 'X2' => $appoint_order['real_orderid']]),
                    'recycle_time' => time(),  //回收时间
                    'is_use' => 2,  //不能使用
                ];
                D('Card_new_coupon_hadpull')->where(['id' => $appoint_order['buy_give_mer_coupon_hadpull_id']])->save($save_data);
            } else {
                fdump_api([$order_id, '优惠券已使用无法删除', json_encode($res)], 'appoint_order/returnGiveUserCoupon', 1);
            }
        } catch (\Exception $e) {
            fdump_api([$order_id, $e->getMessage()], 'appoint_order/returnGiveUserCoupon', 1);
        }
    }
}
