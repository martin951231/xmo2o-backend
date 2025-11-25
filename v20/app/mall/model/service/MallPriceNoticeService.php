<?php

namespace app\mall\model\service;

use app\mall\model\db\MallPriceNotice;

class MallPriceNoticeService
{
	//获取是否设置降价提醒
	public function isSetNotice($goods_id, $uid){
		if(empty($goods_id) || empty($uid)) return false;
		$where = [
			['uid', '=', $uid],
			['is_del', '=', 0],
			['goods_id', '=', $goods_id],
		];
		$data = (new MallPriceNotice)->getOne($where);
		if($data){
			return true;
		}
		else{
			return false;
		}
	}
}