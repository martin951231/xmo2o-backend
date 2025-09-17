<?php
/*
 * 用户推广
 *
 */
class SpreadAction extends BaseAction{
    
    public function spread(){
    	fdump_api($_REQUEST, 'spread', 1);
    	$spread_uid = I("spread_uid", 'intval');//推广人
    	$target_uid = $this->_uid;//被推广人
    	if($this->config['open_user_spread'] != '1'){
    		$this->returnCode(0, [], '未开启用户推广！');
    	}

    	if(!$spread_uid || !$target_uid || $spread_uid == $target_uid){
    		$this->returnCode(0, [], '参数错误！');
    	}

    	$spread_user = D("User")->where(['uid'=> $spread_uid])->find();
    	$target_user = D("User")->where(['uid'=> $target_uid])->find();
    	if(!$target_user || !$spread_user){
    		$this->returnCode(0, [], '没有找到用户信息！');
    	}

		$merchantSpread = M('Merchant_spread')->where(array('openid' => $target_user['openid'],'mer_id'=>array('gt',0)))->find();
		if($merchantSpread){
    		$this->returnCode(0, [], '已成为商家推广人不能被用户推广');
    	}

    	//清除之前的推广记录
    	if($spread_user['openid'] && $target_user['openid']){
	    	D("User_spread")->where(['spread_openid'=>$spread_user['openid'], 'openid' => $target_user['openid']])->delete();
	    }
	    if($spread_user['openid'] && $target_user['wxapp_openid']){
	    	D("User_spread")->where(['spread_openid'=>$spread_user['openid'], 'openid' => $target_user['wxapp_openid']])->delete();
	    }
	    if($spread_user['wxapp_openid'] && $target_user['openid']){
	    	D("User_spread")->where(['spread_openid'=>$spread_user['wxapp_openid'], 'openid' => $target_user['openid']])->delete();
	    }
	    if($spread_user['wxapp_openid'] && $target_user['wxapp_openid']){
	    	D("User_spread")->where(['spread_openid'=>$spread_user['wxapp_openid'], 'openid' => $target_user['wxapp_openid']])->delete();
	    }

	    //加入新的推广关系
	    // if($this->config['user_spread_replace'] == '1'){
	    // 	$condition = [
	    // 		'uid' => $target_uid,
	    // 		'openid' => $target_user['openid'],
	    // 	];
	    // 	$old = D("User_spread")->where()
	    // }
	    $_string = 'uid = '.$target_uid;
    	if($target_user['openid']){
    		$_string .= ' or openid = "'.$target_user['openid'].'"';
    	}
    	if($target_user['wxapp_openid']){
    		$_string .= ' or openid = "'.$target_user['wxapp_openid'].'"';
    	}
    	$already_string = 'spread_uid = '.$spread_uid.' and ('.$_string.')';
    	if(D("User_spread")->where($already_string)->find()){
    		$this->returnCode(0, [], '已经是上下级关系了');
    	}

	    // 被分享人不能已经是该分享者的推广人
		$has_spread = D('User_spread')->where(array('spread_uid' => $target_uid,'uid'=> $spread_uid))->find();
		if($has_spread){
    		$this->returnCode(0, [], '被推广人不能是该分享者的推广人');
    	}
    	
	    if($this->config['user_spread_replace'] == '1'){//开启了上下级替换	    	
	    	D("User_spread")->where($_string)->delete();
			D('User_spread')->where(array('spread_uid' => $target_uid,'uid'=> $spread_uid))->delete();
	    }
	    else{
	    	$has = D("User_spread")->where($_string)->find();
	    	if($has){
	    		$this->returnCode(0, [], '已有推广记录，不能替换上下级！');
	    	}
	    }

	    $data['spread_uid'] = $spread_uid;
	    $data['uid'] = $target_uid;
	    $data['add_time'] = time();
	    D("User_spread")->data($data)->add();
	    if($target_user['openid']){
	    	D('User_spread')->send_spread_msg($target_user['openid'],$spread_user['openid']);
	    }else{
	    	D('User_spread')->send_spread_msg($target_user['wxapp_openid'],$spread_user['openid'],true);
	    }

	    //推荐人赠送积分
	    $spread_user_give_type = C('config.spread_user_give_type');

		if($spread_user_give_type!=3){
			$now_user_spread = M('User_spread')->where(array('uid'=>$target_uid))->find();
			if($now_user_spread) {
				$spread_user = D('User')->get_user($now_user_spread['spread_uid']);
				$now_level = M('User_level')->where(array('level' => $spread_user['level']))->find();
				if ($spread_user_give_type == 0 ||$spread_user_give_type == 2) {
					$spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']: C('config.spread_give_money');
					if(C('config.open_score_fenrun')){
						D('User')->add_money($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'),'','',$target_uid);
					}else{
						D('User')->add_money($spread_user['uid'],  $spread_give_money, L_('推荐新用户注册平台赠送余额'));
					}
					D('Scroll_msg')->add_msg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠平台余额X3",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $spread_give_money.C('config.Currency_txt')))); 
				}

				if ($spread_user_give_type == 1 || $spread_user_give_type == 2) {
					$spread_give_score = $now_level['spread_user_give_score']>0?$now_level['spread_user_give_score']: C('config.spread_give_score');
					D('User')->add_score($spread_user['uid'], $spread_give_score, L_('推荐新用户注册平台赠送') . C('config.score_name'));
					if($spread_give_score>0){
						D('Scroll_msg')->add_msg('spread_reg',$spread_user['uid'],L_("用户X1于X2推荐新用户注册获赠X3个",array("X1" => str_replace_name($spread_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => C('config.score_name').$spread_give_score))); 
					}
				}

			}
		}
	    $this->returnCode(0);
    }

	// 定制 用户成为商家老板下级
    public function boss_spread(){
    	$spread_uid = I("boss_uid", 'intval');//推广人 老板uid
    	$target_uid = I("uid", 'intval');//被推广人
    	if($this->config['open_user_spread'] != '1'){
    		$this->returnCode(1, [], '未开启用户推广！');
    	}

    	if(!$spread_uid || !$target_uid || $spread_uid == $target_uid){
    		$this->returnCode(1, [], '参数错误！');
    	}

    	$spread_user = D("User")->where(['uid'=> $spread_uid])->find();
    	$target_user = D("User")->where(['uid'=> $target_uid])->find();
    	if(!$target_user || !$spread_user){
    		$this->returnCode(1, [], '没有找到用户信息！');
    	}

		
	    // 被分享人不能已经是该分享者的推广人
		$has_spread = D('User_spread')->where(array('spread_uid' => $target_uid,'uid'=> $spread_uid))->find();
		if($has_spread){
    		$this->returnCode(1, [], '被推广人不能是该分享者的推广人');
    	}

    	//清除之前的推广记录
    	if($spread_user['openid'] && $target_user['openid']){
	    	D("User_spread")->where(['spread_openid'=>$spread_user['openid'], 'openid' => $target_user['openid']])->delete();
	    }
	    if($spread_user['openid'] && $target_user['wxapp_openid']){
	    	D("User_spread")->where(['spread_openid'=>$spread_user['openid'], 'openid' => $target_user['wxapp_openid']])->delete();
	    }
	    if($spread_user['wxapp_openid'] && $target_user['openid']){
	    	D("User_spread")->where(['spread_openid'=>$spread_user['wxapp_openid'], 'openid' => $target_user['openid']])->delete();
	    }
	    if($spread_user['wxapp_openid'] && $target_user['wxapp_openid']){
	    	D("User_spread")->where(['spread_openid'=>$spread_user['wxapp_openid'], 'openid' => $target_user['wxapp_openid']])->delete();
	    }

	    //加入新的推广关系

	    $_string = 'uid = '.$target_uid;
    	if($target_user['openid']){
    		$_string .= ' or openid = "'.$target_user['openid'].'"';
    	}
    	if($target_user['wxapp_openid']){
    		$_string .= ' or openid = "'.$target_user['wxapp_openid'].'"';
    	}
    	$already_string = 'spread_uid = '.$spread_uid.' and ('.$_string.')';
    	if(D("User_spread")->where($already_string)->find()){
    		$this->returnCode(1, [], '已经是上下级关系了');
    	}

    	$has = D("User_spread")->where($_string)->find();
    	if($has){
    		$this->returnCode(1, [], '已有推广记录，不能替换上下级！');
    	} 	


	    D("User_spread")->where($_string)->delete();
	   

	    $data['spread_uid'] = $spread_uid;
	    $data['uid'] = $target_uid;
	    $data['add_time'] = time();
	    D("User_spread")->data($data)->add();
	    if($target_user['openid']){
	    	D('User_spread')->send_spread_msg($target_user['openid'],$spread_user['openid']);
	    }else{
	    	D('User_spread')->send_spread_msg($target_user['wxapp_openid'],$spread_user['openid'],true);
	    }

	   
	    $this->returnCode(0,[],'绑定成功');
    }
}