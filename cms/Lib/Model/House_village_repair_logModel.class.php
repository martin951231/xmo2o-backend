<?php
class House_village_repair_logModel extends Model
{
	
	/**
	 * status
	 * 0:业主发起
	 * 1:物业接受分配给工作人员
	 * 2:工作人员接受
	 * 3:工作人员处理
	 * 4:用户评价
	 */
	
	public function add_log($param)
	{
		if (empty($param['repair_id'])) return false;
		if ($order = D('House_village_repair_list')->field(true)->where(array('pigcms_id' => $param['repair_id']))->find()) {
			$data = array('dateline' => time());
			$data['repair_id'] = intval($param['repair_id']);
			$data['status'] = isset($param['status']) ? intval($param['status']) : 0;
			$data['worker_phone'] = isset($param['phone']) ? $param['phone'] : '';
			$data['worker_name'] = isset($param['name']) ? $param['name'] : '';

			if ($data['status'] == 0 || $data['status'] == 4) {
				$this->send_work($order, $data['status']);
				$this->send_system($order, $data['status']);
			} elseif ($data['status'] == 1) {
				$this->send_work($order, $data['status']);
				$this->send_user($order, $data['status']);
			} else {
				$this->send_user($order, $data['status']);
				$this->send_system($order, $data['status']);
			}
            fdump_api(['line:'.__LINE__,$data['status']],'send_work_log',1);
			return $this->add($data);
		}
		
	}
	
	private function send_work($order, $status)
	{
		if ($village = D('House_village')->field(true)->where(array('village_id' => $order['village_id']))->find()) {
            if ($village['status']!=1) {
                // 如果小区状态不是正常的不予以发送模板消息
                fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_send/send_work_log',true);
                fdump($order,'api/log/'.date('Ymd').'/house_send/send_work_log',true);
                fdump($status,'api/log/'.date('Ymd').'/house_send/send_work_log',true);
                fdump($village,'api/log/'.date('Ymd').'/house_send/send_work_log',true);
                return true;
            }
			//$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			
			if ($order['type'] == 1) {
				$first = '物业报修工单';
				$type = 1;
			} elseif ($order['type'] == 2) {
				$first = '水电煤工单';
				$type = 0;
			}  elseif ($order['type'] == 3) {
				$first = '投诉建议工单';
				$type = 0;
			} elseif ($order['type'] == 4) {
				$first = '废品回收工单';
				$type = 1;
			}

            $token_info = array();
            fdump_api(['line:'.__LINE__,$order,$status,$first,$type,$village],'send_work_log',1);
			if ($status == 0) {
				if ($village['handle_type']) {
					$href = C('config.site_url').'/wap.php?c=Worker&a=detail&wxscan=1&pigcms_id=' . $order['pigcms_id'];
					$workers = D('House_worker')->field(true)->where(array('status' => 1, 'village_id' => $order['village_id'],'is_del'=>0, 'type' => $type))->select();

                    fdump_api(['line:'.__LINE__,$workers],'send_work_log',1);
					foreach ($workers as $worker) {
                        $data = array(
                            'tempKey' => 'TM00017',
                            'dataArr' => array(
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '您有一个' . $first . '需处理',
                                'keyword1' => $first,
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                'keyword4' => '物业',
                                'OrderSn' => $order['pigcms_id'],
                                'OrderStatus' => '已发送',
                                'remark' => '\n请点击查看详细信息！'
                            )
                        );
//                        if($order['type'] != 4 ){
//                            //todo app 极光推送
//                            $this->send_app(['village_id'=>$village['village_id'],'phone'=>$worker['phone']],'您有一个' . $first . '需处理','请点击查看详细信息',$href);
//                        }
                        $token_info = D('Property_group')->send_msg('work', $worker['wid'], $data, $token_info);
				fdump_api($token_info,'send_work',1);

					}
				}
			} else {
				$status_all = array('下单成功', '物业已受理', '处理人员已受理', '处理人员完成', '业主已评价');
				$href = C('config.site_url').'/wap.php?c=Worker&a=detail&wxscan=1&pigcms_id=' . $order['pigcms_id'];
				$worker = D('House_worker')->field(true)->where(array('status' => 1, 'village_id' => $order['village_id'],'is_del'=>0, 'wid' => $order['wid']))->find();
                $data = array(
                    'tempKey' => 'TM00017',
                    'dataArr' => array(
                        'href' => $href,
                        'wecha_id' => $worker['openid'],
                        'first' => $status_all[$status],
                        'keyword1' => $first,
                        'keyword2' =>'已发送',
                        'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                        'keyword4' => '物业',
                        'OrderSn' => $order['pigcms_id'],
                        'OrderStatus' => '已发送',
                        'remark' => '\n请点击查看详细信息！'
                    )
                );
//                if($order['type'] != 4 ){
//                    //todo app 极光推送
//                    $this->send_app(['village_id'=>$village['village_id'],'phone'=>$worker['phone']],'您有一个' . $first . '需处理','请点击查看详细信息',$href);
//                }
                D('Property_group')->send_msg('work', $worker['wid'], $data, $token_info);
			}
		}
	}
	
	private function send_system($order, $status)
	{
		if ($village = D('House_village')->field(true)->where(array('village_id' => $order['village_id']))->find()) {
            if ($village['status']!=1) {
                // 如果小区状态不是正常的不予以发送模板消息
                fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_send/send_system_log',true);
                fdump($order,'api/log/'.date('Ymd').'/house_send/send_system_log',true);
                fdump($status,'api/log/'.date('Ymd').'/house_send/send_system_log',true);
                fdump($village,'api/log/'.date('Ymd').'/house_send/send_system_log',true);
                return true;
            }
			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

			if ($order['type'] == 1) {
				$first = '物业报修工单';
				$type = 1;
			} elseif ($order['type'] == 2) {
				$first = '水电煤工单';
				$type = 0;
			}  elseif ($order['type'] == 3) {
				$first = '投诉建议工单';
				$type = 0;
			} elseif ($order['type'] == 4) {
				$first = '废品回收工单';
				$type = 1;
			}

			if ($status == 0) {
				if ($village['handle_type'] == 0) {
					$href = C('config.site_url').'/wap.php?c=Customer&a=detail&pigcms_id=' . $order['pigcms_id'];

					$model->sendTempMsg('OPENTM405462911', 
						array(
							'href' => $href, 
							'wecha_id' => $village['openid'], 
							'first' => '您有一个' . $first . '需处理', 
							'keyword1' => $first, 
							'keyword2' =>'已发送',
							'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
							'keyword4' => '物业', 
							'remark' => '\n请点击查看详细信息！'
						),0,$village['property_id'],1
					);
				}
			} else {
				$status_all = array('下单成功', '物业已受理', '处理人员已受理', '处理人员完成', '业主已评价');
				$href = C('config.site_url').'/wap.php?c=Customer&a=detail&pigcms_id=' . $order['pigcms_id'];
				$model->sendTempMsg('OPENTM405462911', 
					array(
						'href' => $href, 
						'wecha_id' => $village['openid'], 
						'first' => $status_all[$status], 
						'keyword1' => $first, 
						'keyword2' =>'已发送',
						'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
						'keyword4' => '物业', 
						'remark' => '\n请点击查看详细信息！'
					),0,$village['property_id'],1
				);
			}
		}
	}
	private function send_user($order, $status)
	{
        $village = D('House_village')->field(true)->where(array('village_id' => $order['village_id']))->find();
        if ($village['status']!=1) {
            // 如果小区状态不是正常的不予以发送模板消息
            fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_send/send_user_log',true);
            fdump($order,'api/log/'.date('Ymd').'/house_send/send_user_log',true);
            fdump($status,'api/log/'.date('Ymd').'/house_send/send_user_log',true);
            fdump($village,'api/log/'.date('Ymd').'/house_send/send_user_log',true);
            return true;
        }
		$now_user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
		$property_id = $village['property_id'];
		if ($now_user['openid']) {
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'waterReportlist',
                'isAll' => true,
                'urlParam' => '',
            ];
            $waterReportlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
			$status_all = array('下单成功', '物业已受理', '处理人员已受理', '处理完成', '已评价');
			if ($order['type'] == 1) {
				$first = '物业报修';
                $href = $waterReportlistUrl.'?type=1&title='.urlencode('物业报修'). '&id=' . $order['pigcms_id'].'&village_id=' . $order['village_id'];
                if (isset($order['bind_id']) && $order['bind_id']) {
                    $href .= '&pigcms_id='.$order['bind_id'];
                }
			} elseif ($order['type'] == 2) {
				$first = '水电煤上报';
                $href = $waterReportlistUrl . '?type=2&title='.urlencode('水电煤上报'). '&id=' . $order['pigcms_id'].'&village_id=' . $order['village_id'];
                if (isset($order['bind_id']) && $order['bind_id']) {
                    $href .= '&pigcms_id='.$order['bind_id'];
                }
			} elseif ($order['type'] == 3) {
				$first = '投诉建议';
                $href = $waterReportlistUrl . '?type=3&title='.urlencode('投诉建议'). '&id=' . $order['pigcms_id'].'&village_id=' . $order['village_id'];
                if (isset($order['bind_id']) && $order['bind_id']) {
                    $href .= '&pigcms_id='.$order['bind_id'];
                }
			}elseif ($order['type'] == 4) {
				$first = '废品回收';
				$href = C('config.site_url').'/wap.php?c=House&a=village_my_waste_recovery_detail&village_id='. $order['village_id'] . '&id=' . $order['pigcms_id'];
			}

			$worker = D('House_worker')->field(true)->where(array('status' => 1, 'village_id' => $order['village_id'],'is_del'=>0, 'wid' => $order['wid']))->find();
			if ($status == 1) {
				$remark = '已经分配给:' . $worker['name'] . ',' . $worker['phone'] . '请您耐心等待！';
			} elseif ($status == 2) {
				$remark = '工作人员:' . $worker['name'] . ',' . $worker['phone'] . '已经接单，'. $order['msg'] .'请您耐心等待！';
			} elseif ($status == 3) {
				$remark = '工作人员:' . $worker['name'] . ',' . $worker['phone'] . '已处理，期待您对本次任务的评价！';
			}
			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            //todo app 极光推送
            $this->send_app(['uid'=>$now_user['uid']],$first,$remark,$href);
			$model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $first . '订单', 'OrderSn' => $order['pigcms_id'], 'OrderStatus' => $status_all[$status], 'remark' => $remark),0,$property_id,1);
		}
	}


    /**
     * app 极光推送
     * @author: liukezhu
     * @date : 2021/7/2
     * @param $data
     * @param $title
     * @param $msg
     * @param $href
     * @return bool
     */
	public function send_app($data,$title,$msg,$href){
        $data['status']=1;
        $bind_user = D('House_village_user_bind')->where($data)->find();
        fdump_api(['line:'.__LINE__,$data,$bind_user],'send_work_app_log',1);
        if(empty($bind_user)){
            return false;
        }
        $arr=[
            'title'=>$title,
            'msg'=>$msg,
            'url'=>$href,
            'pigcms_id'=>$bind_user['pigcms_id'],
            'village_id'=>$bind_user['village_id'],
        ];
        $rel=D('User')->AppPush($bind_user,$arr);
        return $rel;
    }
}