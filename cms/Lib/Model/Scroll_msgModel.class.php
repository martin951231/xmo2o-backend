<?php
/*
 * 循环消息 goi
 * */
class Scroll_msgModel extends Model
{
	public function add_msg($type,$fid,$content){
		if(C('config.show_scroll_msg')) {
			$date['type'] = $type;
			$date['fid'] = $fid;
			$date['content'] = $content;
			$date['add_time'] = $_SERVER['REQUEST_TIME'];
			$this->add($date);
		}
	}

	public function get_msg(){
		$msg_list = S('scroll_msg');
		if(empty($msg_list)){
			$msg_list = $this->order('add_time DESC')->limit(10)->select();
			S('scroll_msg',$msg_list,300);
		}
		return $msg_list;
	}

	// 组合单纯用户的行为，提供方法，方便做用户操作的轮播
	public function get_scroll_msg_user(){
		$where = array(
			's.type' => array(array('neq','village_withdraw'),array('neq','mer_reg'),array('neq','mer_withdraw'),array('neq','mer_recharge'),array('neq','auth'),array('neq','village_recharge'),array('neq','user_withdraw'))
		);
        $res = M('Scroll_msg')
            ->join('as s inner join '.C('DB_PREFIX').'user u ON s.fid=u.uid')
            ->field('`s`.`type`,`s`.`add_time`,`u`.`nickname`,`u`.`avatar`')
            ->where($where)->limit(10)->order('`id` DESC')->select();
		if(!$res){
			return array();
		}
		$returnArr = array();
		foreach($res as $res_val){
			if(empty($res_val['avatar'])){
				$res_val['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
			}else{
				$res_val['avatar'] = replace_file_domain($res_val['avatar']);
			}
			
			switch($res_val['type']){
				case 'shop':
					$res_val['type_txt'] = L_("购买了X1商品",array("X1" => C('config.shop_alias_name'))); 
					break;
				case 'group':
					$res_val['type_txt'] = L_("购买了X1商品",array("X1" => C('config.group_alias_name')));
					break;
				case 'sign':
					$res_val['type_txt'] = L_('进行了签到');
					break;
				case 'yydb':
					$res_val['type_txt'] = L_('参与了X1',C('config.activity_score_alias'));
					break;
				case 'appoint':
					$res_val['type_txt'] = L_("下了X1订单",array("X1" => C('config.appoint_alias_name'))); 
					break;
				case 'foodshop':
				case 'meal':
					$res_val['type_txt'] = L_("购买了X1商品",array("X1" => C('config.meal_alias_name'))); 
					break;
				case 'spread_reg':
					$res_val['type_txt'] = L_('推广了用户');
					break;
				case 'spread':
					$res_val['type_txt'] = L_('推广用户消费获得佣金');
					break;
				case 'classify':
					$res_val['type_txt'] = L_('发布了').C('config.classify_name');
					break;
				case 'mall':
					$res_val['type_txt'] = L_("购买了X1商品",array("X1" => C('config.mall_alias_name'))); 
					break;
				case 'village_group':
					$res_val['type_txt'] = L_('下了X1订单',array('X1'=>C('config.village_group_alias')));
					break;
				case 'store':
					$res_val['type_txt'] = L_('到店购物买单了');
					break;
				case 'feedback':
					$res_val['type_txt'] = L_('填写了评论');
					break;
				case 'score_recharge':
					$res_val['type_txt'] = L_('使用积分兑换余额');
					break;
				case 'level':
					$res_val['type_txt'] = L_('开通会员等级');
					break;
				default:
					$res_val['type_txt'] = '';
					continue;
			}
			$returnArr[] = $res_val;
		}
		return $returnArr;
	}
}
?>