<?php
/**
 * 个人消息中心
 */
namespace app\common\controller\api;

use app\common\model\service\user\UserMsgService;
use app\common\model\service\msg\MsgService;
use app\merchant\model\service\store\MerchantCategoryService;

class MsgController extends ApiBaseController
{    
	public function index(){
		$this->checkLogin();
		$uid = $this->request->log_uid;
		// $uid = 1358738;
		$output = [];

		//1、获取站内信
		$where = [
			['uid', '=', $uid],
			['is_del', '=', 0],
			['type', '=', 3]
		];
		$mailMsg = (new MsgService)->getData($where);
		if(!empty($mailMsg)){
			$mailMsg = $mailMsg->toArray();
			$mails = array_unique(array_column($mailMsg, 'mail_id'));
			$where = [
				['id', 'in', $mails],
			];
			$mailsData = (new MsgService)->mailGetData($where, 'id, category_type, category_id, title, content_type, content, is_system');
			if(!empty($mailsData)){
				$mailsData = $mailsData->toArray();
				$categoryIds = [];
				$categoryData = [];
				$malls = [];
				$shops = [];
				$stores = [];
				$groups = [];
				foreach ($mailsData as $key => $value) {
					if($value['category_type'] == '1'){
						if(!in_array($value['category_id'], $categoryIds)){
							$categoryIds[] = $value['category_id'];
							$categoryData[$value['category_id']] = [$value['id']];
						}
						else{
							$categoryData[$value['category_id']][] = $value['id'];
						}
					}
					if($value['category_type'] == '2'){
						$malls[] = $value['id'];
					}
					if($value['category_type'] == '3'){
						$stores[] = $value['id'];
					}
					if($value['category_type'] == '4'){
						$groups[] = $value['id'];
					}
					if($value['category_type'] == '5'){
						$shops[] = $value['id'];
					}
				}
				if($categoryIds){
					$where = [
						['cat_id','in',$categoryIds]
					];
					$cates = (new MerchantCategoryService)->getSome($where, 'cat_id,cat_name,cat_pic');
					if($cates){
						$cates = $cates->toArray();
						foreach ($cates as $key => $value) {
							$temp = [
								'type' => 'category',
								'type_id' => $value['cat_id'],
								'icon' => $value['cat_pic'] ? file_domain().$value['cat_pic'] : '',
								'cate' => $value['cat_name'],
								'unread' => 0,
								'time' => '',
								'title' => '',
								'img' => '',
								'is_link' => 0,
								'img' => '',
								'link_url' => '',
								'content' => ''
							];
							$msg = $this->getMsg($uid, $categoryData[$value['cat_id']]);
							$temp['unread'] = $msg['unread'];
							$temp['time'] = $msg['time'];
							$temp['title'] = $msg['title'];
							$temp['is_link'] = $msg['is_link'];
							$temp['link_url'] = $msg['link_url'];
							$temp['img'] = $msg['img'] ? file_domain().$msg['img'] : '';
							$temp['content'] = $msg['content'];
							$output[] = $temp;
						}
					}
				}
				if(!empty($malls)){
					$temp = [
						'type' => 'mall',
						'type_id' => 0,
						'icon' => cfg("site_url")."/static/msg/icon/mall.png",//产品提供，说是写死
						'cate' => cfg("mall_alias_name") ?? '商城',
						'unread' => 0,
						'time' => '',
						'title' => '',
						'is_link' => 0,
						'img' => '',
						'link_url' => '',
						'content' => ''
					];
					$msg = $this->getMsg($uid, $malls);
					$temp['unread'] = $msg['unread'];
					$temp['time'] = $msg['time'];
					$temp['title'] = $msg['title'];
					$temp['is_link'] = $msg['is_link'];
					$temp['link_url'] = $msg['link_url'];
					$temp['img'] = $msg['img'] ? file_domain().$msg['img'] : '';
					$temp['content'] = $msg['content'];
					$output[] = $temp;
				}
				if(!empty($shops)){
					$temp = [
						'type' => 'shop',
						'type_id' => 0,
						'icon' => cfg("site_url")."/static/msg/icon/shop.png",//产品提供，说是写死
						'cate' => cfg("shop_alias_name") ?? '外卖',
						'unread' => 0,
						'time' => '',
						'title' => '',
						'is_link' => 0,
						'img' => '',
						'link_url' => '',
						'content' => ''
					];
					$msg = $this->getMsg($uid, $shops);
					$temp['unread'] = $msg['unread'];
					$temp['time'] = $msg['time'];
					$temp['title'] = $msg['title'];
					$temp['is_link'] = $msg['is_link'];
					$temp['link_url'] = $msg['link_url'];
					$temp['img'] = $msg['img'] ? file_domain().$msg['img'] : '';
					$temp['content'] = $msg['content'];
					$output[] = $temp;
				}
				if(!empty($stores)){
					$temp = [
						'type' => 'store',
						'type_id' => 0,
						'icon' => cfg("site_url")."/static/msg/icon/store.png",//产品提供，说是写死
						'cate' => cfg("cash_alias_name") ?? '买单',
						'unread' => 0,
						'time' => '',
						'title' => '',
						'is_link' => 0,
						'img' => '',
						'link_url' => '',
						'content' => ''
					];
					$msg = $this->getMsg($uid, $stores);
					$temp['unread'] = $msg['unread'];
					$temp['time'] = $msg['time'];
					$temp['title'] = $msg['title'];
					$temp['is_link'] = $msg['is_link'];
					$temp['link_url'] = $msg['link_url'];
					$temp['img'] = $msg['img'] ? file_domain().$msg['img'] : '';
					$temp['content'] = $msg['content'];
					$output[] = $temp;
				}
				if(!empty($groups)){
					$temp = [
						'type' => 'group',
						'type_id' => 0,
						'icon' => cfg("site_url")."/static/msg/icon/group.png",//产品提供，说是写死
						'cate' => cfg("group_alias_name") ?? '团购',
						'unread' => 0,
						'time' => '',
						'title' => '',
						'is_link' => 0,
						'img' => '',
						'link_url' => '',
						'content' => ''
					];
					$msg = $this->getMsg($uid, $groups);
					$temp['unread'] = $msg['unread'];
					$temp['time'] = $msg['time'];
					$temp['title'] = $msg['title'];
					$temp['is_link'] = $msg['is_link'];
					$temp['link_url'] = $msg['link_url'];
					$temp['img'] = $msg['img'] ? file_domain().$msg['img'] : '';
					$temp['content'] = $msg['content'];
					$output[] = $temp;
				}
			}
		}

		//客服
		// $temp = [
		// 	'type' => 'im',
		// 	'type_id' => 0,
		// 	'icon' => '',//产品提供，说是写死
		// 	'cate' => '客服',
		// 	'unread' => 0,
		// 	'time' => '',
		// 	'title' => '',
		// 	'is_link' => 0,
		// 	'img' => '',
		// 	'link_url' => '',
		// 	'content' => ''
		// ];
		// $msg = $this->getMsg($uid, [], 1);
		// if($msg){
		// 	$temp['unread'] = $msg['unread'];
		// 	$temp['time'] = $msg['time'];
		// 	$temp['title'] = $msg['title'];
		// 	$temp['is_link'] = $msg['is_link'];
		// 	$temp['link_url'] = $msg['link_url'];
		// 	$temp['img'] = $msg['img'] ? file_domain().$msg['img'] : '';
		// 	$temp['content'] = $msg['content'];
		// 	$output[] = $temp;
		// }
		//互动消息
		$temp = [
			'type' => 'remind',
			'type_id' => 0,
			'icon' => cfg("site_url")."/static/msg/icon/remind.png",//产品提供，说是写死
			'cate' => '互动消息',
			'unread' => 0,
			'time' => '',
			'title' => '',
			'is_link' => 0,
			'img' => '',
			'link_url' => '',
			'content' => ''
		];
		$msg = $this->getMsg($uid, [], 2);
		if($msg){
			$temp['unread'] = $msg['unread'];
			$temp['time'] = $msg['time'];
			$temp['title'] = $msg['title'];
			$temp['is_link'] = $msg['is_link'];
			$temp['link_url'] = $msg['link_url'];
			$temp['img'] = $msg['img'] ? file_domain().$msg['img'] : '';
			$temp['content'] = $msg['content'];
			$output[] = $temp;
		}
		return api_output(0, $output);
	}

	public function getImList(){
		// $uid = 1358738;
		$this->checkLogin();
		$uid = $this->request->log_uid;
		$page = $this->request->param('page', 1, 'intval');
		$pageSize = 10;
		$data = [];
		$where = [
				['uid', '=', $uid],
				['is_del', '=', 0],
				['type', '=', 1],
			];
		$data = $this->dealMsg((new MsgService)->getData($where, true, 'add_time desc', ($page-1)*$pageSize, $pageSize));
		return api_output(0, ['list'=>$data]);
	}

	public function getUnread(){
		$this->checkLogin();
		$uid = $this->request->log_uid;
		// $uid = 1358738;
		$where = [
			['uid', '=', $uid],
			['is_del', '=', 0],
			['is_read', '=', 0]
		];
		return api_output(0, ['unread'=>(new MsgService)->getCount($where)]);
	}

	private function getMsg($uid, $mailids = [], $type = 0){
		$where1 = [['uid','=',$uid], ['is_read','=',0]];
		if($mailids){
			$where1[] = ['mail_id','in',$mailids];
		}
		$where2 = [['uid','=',$uid]];
		if($mailids){
			$where2[] = ['mail_id','in',$mailids];
		}
		if($type > 0){
			$where1[] = ['type','=',$type];	
			$where2[] = ['type','=',$type];
		}
		$new = (new MsgService)->getInfo($where2, true, 'add_time desc');
		if($new){
			$new = $new->toArray();
			$new['unread'] = (new MsgService)->getCount($where1);
			if($new['add_time'] > strtotime(date('Y-m-d'))){
				$new['time'] = date('H:i', $new['add_time']);
			}
			elseif($new['add_time'] > (strtotime(date('Y-m-d')) - 86400)){
				$new['time'] = '昨天';
			}
			else{
				$new['time'] = date('m-d', $new['add_time']);
			}
			return $new;
		}
		else{
			return [];
		}
	}

	public function msgList(){
		$this->checkLogin();
		$uid = $this->request->log_uid;
		// $uid = 1358738;
		$type = $this->request->param('type');
		$type_id = $this->request->param('type_id', 0, 'intval');

		if(empty($type)){
			return api_output_error(1003, "参数错误");
		}
		if($type == 'category' && empty($type_id)){
			return api_output_error(1003, "参数错误");	
		}
		$page = $this->request->param('page', 1, 'intval');
		$pageSize = 10;
		$data = [];

		if($type == 'category'){//链表查询
			$cateInfo = (new MerchantCategoryService)->getOne([['cat_id', '=', $type_id]]);
			$where = [
				['a.uid', '=', $uid],
				['a.is_del', '=', 0],
				['m.category_id', '=', $type_id]
			];
			$data = $this->dealMsg((new MsgService)->getPageData($where, 'a.*,m.category_id', $page, $pageSize), 'category', $cateInfo['cat_pic'] ?? '');
		}
		elseif(in_array($type, ['shop', 'mall', 'group', 'store'])){//链表查询
			$where = [
				['a.uid', '=', $uid],
				['a.is_del', '=', 0],
				['a.type', '=', 3],
			];
			switch ($type) {
				case 'shop':
					$where[] = ['m.category_type', '=', 5];
					break;
				case 'mall':
					$where[] = ['m.category_type', '=', 2];
					break;
				case 'store':
					$where[] = ['m.category_type', '=', 3];
					break;
				case 'group':
					$where[] = ['m.category_type', '=', 4];
					break;
			}
			$data = $this->dealMsg((new MsgService)->getPageData($where, 'a.*', $page, $pageSize), $type);
		}
		// elseif($type == 'im'){
		// 	$where = [
		// 		['uid', '=', $uid],
		// 		['is_del', '=', 0],
		// 		['type', '=', 1],
		// 	];
		// 	$data = $this->dealMsg((new MsgService)->getData($where, true, 'add_time desc', $page, $pageSize));
		// }
		elseif($type == 'remind'){
			$where = [
				['uid', '=', $uid],
				['is_del', '=', 0],
				['type', '=', 2],
			];
			$data = $this->dealMsg((new MsgService)->getData($where, true, 'add_time desc', ($page-1)*$pageSize, $pageSize), $type);
		}
		if($data){
			//更新为已读
			(new MsgService)->update([['id', 'in', array_column($data, 'id')]], ['is_read'=> 1]);
		}
		return api_output(0, $data);
	}

	public function msgDetail(){
		$this->checkLogin();
		$uid = $this->request->log_uid;
		// $uid = 1358738;
		$id = $this->request->param('id', 0, 'intval');

		if(empty($id)){
			return api_output_error(1003, "参数错误");
		}
		$info = (new MsgService)->getData([['id', '=', $id]]);
		$info = $this->dealMsg($info)[0];
		(new MsgService)->update([['id', '=', $id]], ['is_read'=> 1]);
		return api_output(0, $info);
	}

	private function dealMsg($data, $type = '', $iconPath = ''){
		$return  = [];
		if($data){
			$data = $data->toArray();
			$icon = '';
			if(!empty($type) && in_array($type, ['shop', 'group', 'store', 'mall', 'remind'])){
				$icon = cfg("site_url")."/static/msg/icon/".$type.".png";
			}
			if($type == 'category'){
				$iconPath = file_domain().$iconPath;
				$icon = $iconPath;
			}
			foreach ($data as $key => $value) {
				if($value['add_time'] > strtotime(date('Y-m-d'))){
					$value['time'] = date('H:i', $value['add_time']);
				}
				elseif($value['add_time'] > (strtotime(date('Y-m-d')) - 86400)){
					$value['time'] = '昨天'.date('H:i', $value['add_time']);
				}
				else{
					$value['time'] = date('Y-m-d H:i', $value['add_time']);
				}
				$value['img'] = $value['img'] ? file_domain().$value['img'] : '';
				$value['icon'] = $icon ? $icon : replace_file_domain($value['icon']);
				$value['desc'] = $value['desc'];
				unset($value['uid'], $value['is_del'], $value['type'], $value['mail_id']);
				$return[] = $value;
			}
		}
		return $return;
	}

	public function test(){
		(new UserMsgService)->addMsg(1358738, 'im', '亲，商品无货', '/upload/merchant/000/000/811/5f55c764dff81792.png');
	}
}