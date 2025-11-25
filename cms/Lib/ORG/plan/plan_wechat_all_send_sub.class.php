<?php
class plan_wechat_all_send_sub extends plan_base{
	public function runTask($param, $plan_time){

	    $lockParam = $param;
        if (!$this->lock($lockParam)) {
            return true;
        }


		if(empty($param['openid']) || empty($param['log_id']) || empty($param['c_id'])) return true;
		$this->keepThread();
		$send_id = $param['log_id'];
		$openid = $param['openid'];
		$c_id = $param['c_id'];
		$uid = $param['uid']??0;

		
		$source = D('Source_material')->where(array('pigcms_id' => $c_id))->find();
		if (empty($source)) return true;
		$ids = unserialize($source['it_ids']);
		$image_text = D('Image_text')->field(true)->where(array('pigcms_id' => array('in', $ids)))->select();
		$result = array();
		foreach ($image_text as $txt) {
			$result[$txt['pigcms_id']] = $txt;
		}
		$image_text = array();
		foreach ($ids as $id) {
			$image_text[] = isset($result[$id]) ? $result[$id] : array();
		}

		$access_token_array = D('Access_token_expires')->get_access_token();
		if ($access_token_array['errcode']) {
			return true;
		}
		$access_token = $access_token_array['access_token'];
			
		$send_to_url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
		$send_obj = M('Send_user');
		import('ORG.Net.Http');
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		if ($send_obj->field(true)->where(array('log_id' => $send_id, 'openid' => $openid))->find()) {
			return true;
		}
		$str = '{"touser":"'.$openid.'","msgtype":"news","news":{"articles": [';
		$pre = '';
		foreach ($image_text as $txt) {
			$url = C('config.site_url') . '/wap.php?g=Wap&c=Article&a=index&imid=' . $txt['pigcms_id'].'&lid='.$send_id;
			$str .= $pre . '{"title":"'.$txt['title'].'", "description":"'.$txt['digest'].'", "url":"'. $url .'", "picurl":"'.replace_file_domain($txt['cover_pic']).'"}';
			$pre = ',';
		}
		$str .= ']}}';
		$this->keepThread();
		$rt = Http::curlPost($send_to_url, $str);
		$user = D('User')->get_user($openid,'openid');
		if ($rt['errcode']) {
			$send_user_id = $send_obj->add(array('uid'=>$uid,'log_id' => $send_id, 'openid' => $openid, 'status' =>0, 'is_template' => 1, 'sendtime' => time()));
			$model->sendTempMsg('OPENTM405462911',
					array('href' => C('config.site_url') . '/wap.php?g=Wap&c=Article&a=article_list&id=' . $c_id,
							'wecha_id' => $openid,
							'first' => '您好，您有新消息查看\n',
							'keyword1' => '平台通知',
							'keyword2' => '成功',
							'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
							'keyword4' => $user['nickname'],
							'remark' => '点击查看消息详情！'),
						0,
						0,
						0,
						[],
						function ($result) use ($send_user_id) {  //增加模板消息回调结果通知
							M('Send_user')->where(array('id' => $send_user_id))->save(['status' => $result == 1 ? 1 : 2]);
						}
					);
		} else {
			$send_obj->add(array('uid'=>$uid,'log_id' => $send_id, 'openid' => $openid, 'status' => 1, 'sendtime' => time()));
		}
		
		$this->unlock($lockParam);

		return true;
	}
}
?>