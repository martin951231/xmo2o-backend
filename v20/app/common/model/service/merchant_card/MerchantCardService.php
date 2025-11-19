<?php
/**
 * 商家会员卡
 * User: lumin
 * Date: 2020/06/06 13:40
 */

namespace app\common\model\service\merchant_card;

use app\common\model\db\CardNew;
use app\common\model\db\CardUserlist;

class MerchantCardService
{
	/**
	 * 获取用户的商家会员卡
	 * @param  [type] $uid    用户ID
	 * @param  [type] $mer_id 商家ID
	 * @return [type]         用户会员卡信息
	 */
	public function getUserCard($uid, $mer_id){
		if(empty($uid) || empty($mer_id)){
			throw new \Exception("uid或mer_id为空");
		}
		$where = [
			['u.mer_id', '=', $mer_id],
			['u.uid', '=', $uid],
			['u.status', '>', 0],
			['c.status', '>', 0]
		];
        $now_user_card = (new CardUserlist)->getUserCard($where);
        return $now_user_card;
	}

	public function getCard($mer_id){
		$where = [
			['mer_id', '=', $mer_id],
		];
        $card = (new CardNew)->getOne($where);
        if($card){
        	return $card->toArray();
        }
        return [];
	}
}
