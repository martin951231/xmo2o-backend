<?php
//粉丝群发
class plan_wechat_mass_send extends plan_base{
	public function runTask($param, $plan_time){
    fdump($param, "lumin_gg5",1);
		if(empty($param['openid']) || empty($param['send_id'])) return true;
		$send_obj = M('Send_user');
		$send_id = $param['send_id'];
		
		if ($send_obj->field(true)->where(array('log_id' => $send_id, 'openid' => $param['openid']))->find()) {
			return true;
		}
		$log = D('Send_log')->where(array('pigcms_id' => $send_id))->find();
		

		
		$source = D('Source_material')->where(array('pigcms_id' => $log['c_id']))->find();
		
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
		import('ORG.Net.Http');
		$this->keepThread();
		$str = '{"touser":"'.$param['openid'].'","msgtype":"news","news":{"articles": [';
		$pre = '';
		foreach ($image_text as $txt) {
			$url = C('config.site_url') . '/wap.php?g=Wap&c=Article&a=index&imid=' . $txt['pigcms_id'].'&lid='.$send_id;
			$str .= $pre . '{"title":"'.$txt['title'].'", "description":"'.$txt['digest'].'", "url":"'. $url .'", "picurl":"'.replace_file_domain($txt['cover_pic']).'"}';
			$pre = ',';
		}
		$str .= ']}}';
		
		$rt = Http::curlPost($send_to_url, $str);
		fdump($rt, "lumin_gg5",1);
		$user = D('User')->get_user($param['openid'],'openid');
		if ($rt['errcode']) {
			$send_obj->add(array('log_id' => $send_id, 'openid' => $param['openid'], 'status' => 2, 'sendtime' => time()));
			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			$model->sendTempMsg('OPENTM405462911',
					array('href' => C('config.site_url') . '/wap.php?g=Wap&c=Article&a=article_list&id=' . $log['c_id'],
							'wecha_id' => $param['openid'],
							'first' => '您好，您有新消息查看\n',
							'keyword1' => '商家粉丝群发',
							'keyword2' => '成功',
							'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
							'keyword4' => $user['nickname'],
							'remark' => '点击查看群发消息详情！'));
		} else {
			$send_obj->add(array('log_id' => $send_id, 'openid' => $param['openid'], 'status' => 1, 'sendtime' => time()));
		}
		
		return true;
	}
}
?>