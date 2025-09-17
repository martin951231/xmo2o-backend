<?php


namespace app\common\model\service\user;


use app\common\model\db\UserMsg;
use app\common\model\service\user\MailService;
use Model;

class UserMsgService
{
	/**
	 * 发布消息
	 * @param [type] $uid   发给哪个用户
	 * @param [type] $type  shop=外卖 mall=商城 store=买单 group=团购 im=客服 remind=互动消息
	 * @param [type] $content 发送的标题,当内容是图片地址时，请传入“[图片]”
	 * @param [type] $icon 消息的图标（目前用于客服业务，传入客服的头像或者店铺头像）
	 * @param [type] $desc 一段描述(一般用于种草业务)
	 * @param [type] $link_url 点击进入的链接，没有可以不传(客服必传)
	 */
	public function addMsg($uid, $type, $content,$icon = '', $desc = '', $link_url = ''){
		if(empty($uid) || empty($content) || empty($type)) throw new Exception("参数传递异常");
		$data = [
			'uid' => $uid,
			'title' => '',
			'desc' => $desc,
			'icon' => $icon,
			'content' => $content,
			'add_time' => time(),
			'is_del' => 0,
			'is_read' => 0,
			'is_link' => trim($link_url) ? 1 : 0,
			'link_url' => trim($link_url)
		];
		$MailService = new MailService();
		switch ($type) {
			case 'shop':
				$data['type'] = 3;
				$data['title'] = '外卖通知';
				$mail = [
					'category_type' => 5,
					'title' => '外卖通知',
					'content' => $content,
					'add_time' => time(),
					'send_status' => 1,
					'send_usernums' => 1,
					'is_system' => 0
				];
				$data['mail_id'] = $MailService->addData($mail);
				break;
			case 'mall':
				$data['type'] = 3;
				$data['title'] = '商城通知';
				$mail = [
					'category_type' => 2,
					'title' => '商城通知',
					'content' => $content,
					'add_time' => time(),
					'send_status' => 1,
					'send_usernums' => 1,
					'is_system' => 0
				];
				$data['mail_id'] = $MailService->addData($mail);
				break;
			case 'store':
				$data['type'] = 3;
				$data['title'] = '买单通知';
				$mail = [
					'category_type' => 3,
					'title' => '买单通知',
					'content' => $content,
					'add_time' => time(),
					'send_status' => 1,
					'send_usernums' => 1,
					'is_system' => 0
				];
				$data['mail_id'] = $MailService->addData($mail);
				break;
			case 'group':
				$data['type'] = 3;
				$data['title'] = '团购通知';
				$mail = [
					'category_type' => 4,
					'title' => '团购通知',
					'content' => $content,
					'add_time' => time(),
					'send_status' => 1,
					'send_usernums' => 1,
					'is_system' => 0
				];
				$data['mail_id'] = $MailService->addData($mail);
				break;
			case 'im':
				$data['type'] = 1;
				$data['mail_id'] = 0;
				break;
			case 'remind':
				$data['type'] = 2;
				$data['mail_id'] = 0;
				break;
			default:
				 throw new Exception("参数type传递异常");
				break;
		}
		(new UserMsg)->add($data);
		return true;
	}

	/**
	 * 标记已读
	 * @param  [type] $uid  用户
	 * @param  [type] $type 类型
	 * @return [type]       [description]
	 */
	public function readMsg($uid, $type){
		return true;
	}

    /**
     * @param $data
     * @return bool
     * 添加数据
     */
	public function addData($data){
        $insertId=(new UserMsg)->add($data);
        return $insertId;
    }

}