<?php
// 推广分佣
class User_spreadModel extends Model{
    public function get_spread_user($now_user_openid,$uid, $firstRow = 0, $listRows = 0){
        $where = array(
            '_string' => "us.spread_uid='$uid'"
        );
        if($listRows){
            $now_user_spread_user_list =$this->field('`u`.*,`us`.`uid` AS `user_uid`,`us`.`pigcms_id` AS `spread_id`,u.add_time AS register_time')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where($where)->group('us.uid')->limit($firstRow . ',' . $listRows)->select();
        }
        else{
            $now_user_spread_user_list =$this->field('`u`.*,`us`.`uid` AS `user_uid`,`us`.`pigcms_id` AS `spread_id`,u.add_time AS register_time')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where($where)->group('us.uid')->select();
            // $sql = "select `u`.*,`us`.`uid` AS `user_uid`,`us`.`pigcms_id` AS `spread_id` from ".C("DB_PREFIX")."user_spread as us inner join ".C('DB_PREFIX')."user as u on us.openid  = u.openid where us.spread_openid='$now_user_openid' group by us.openid union select `u`.*,`us`.`uid` AS `user_uid`,`us`.`pigcms_id` AS `spread_id` from ".C("DB_PREFIX")."user_spread as us inner join ".C('DB_PREFIX')."user as u on us.openid  = u.wxapp_openid where us.spread_openid='$now_user_openid' group by us.openid";
            // $now_user_spread_user_list = $this->query($sql);
        }
        $spreads = array_column($now_user_spread_user_list, 'uid');
        $where = ['us.spread_uid'=>['in', $spreads]];
        $all = $this->field('`us`.`uid`,us.spread_uid')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where($where)->group('us.uid')->select();       

        $spreaduids = array_column($now_user_spread_user_list, 'uid');
        $all_list = M('User_spread_list')->where(array('get_uid'=>['in', $spreaduids],'uid'=>$uid))->select();

        foreach($now_user_spread_user_list as &$v){
            // if($v['uid'] && !$v['user_uid']){
            //     $this->where(array('pigcms_id'=>$v['spread_id']))->data(array('uid'=>$v['uid']))->save();
            // }
            foreach ($all as $key => $value) {
                if($value['spread_uid'] == $v['uid']){
                    $v['spread_count']++;
                }
            }
            foreach ($all_list as $key => $value) {
                if($value['get_uid'] == $v['uid']){
                    $v['spread_money'] += $value['money'];
                }
            }
            $now_uid = $v['uid'];
            
            $v['spread_count'] = empty($v['spread_count'])?0:$v['spread_count'];
            $v['spread_money'] = empty($v['spread_money'])?0:$v['spread_money'];
        }
        return array('spread_user_list'=>$now_user_spread_user_list);
    }
    
    public function get_spread_higher_user($now_user_openid,$uid,$now_user_wxapp_openid = ''){
        if($now_user_openid || $uid){
            $now_user_spread_user =$this->where(array('uid'=>$uid))->find();
        }
        if(empty($now_user_spread_user) && $now_user_wxapp_openid){
            $now_user_spread_user =$this->where(array('openid'=>$now_user_wxapp_openid,'is_wxapp'=>1))->find();
        }
        $where['uid'] = $now_user_spread_user['spread_uid'];
        $now_user_spread_user = M('User')->where($where)->find();
        $now_user_spread_user_higher_user = $this->where(array('uid'=>$now_user_spread_user['uid']))->find();
        if($now_user_spread_user_higher_user) $now_user_spread_user['spread_count'] = 1;
        $now_user_spread_user['spread_count'] = empty($now_user_spread_user['spread_count'])?0:$now_user_spread_user['spread_count'];
        $now_user_spread_user['spread_money'] = M('User_spread_list')->where(array('get_uid'=>$uid,'uid'=>$now_user_spread_user['uid']))->sum('money');
        $now_user_spread_user['spread_money'] = empty($now_user_spread_user['spread_money'])?0:$now_user_spread_user['spread_money'];
        return $now_user_spread_user;
    }

    //过户用户列表
    public function get_spread_change_user($uid){
        $change_user = M('User')->where(array('spread_change_uid'=>$uid))->select();
        foreach($change_user as &$v){
            $v['spread_count'] = $this->where(array('spread_uid'=>$v['uid']))->count();
            $v['spread_money'] = M('User_spread_list')->where(array('uid'=>$v['uid'],'change_uid'=>$uid))->sum('money');
        }
        return array('spread_change_user_list'=>$change_user);
    }

    public function get_spread_num($now_user_openid,$uid){
        return $this->join('as us left join '.C('DB_PREFIX').'user u ON us.spread_uid  = u.uid')->where(array('us.spread_uid'=>$uid,'us.uid'=>array('neq','')))->count();

    }

    public function send_spread_msg($openid,$spread_openid,$is_wxapp=false){
        //增加一个缓存key，防止重复发送
        $preventRepeatKey = md5(var_export(func_get_args(), true));
        if (S($preventRepeatKey)) {
            return;
        }
        S($preventRepeatKey, '1', 10);

        if($is_wxapp){
            $now_user = D('User')->get_user($openid,'wxapp_openid');
        }else{
            $now_user = D('User')->get_user($openid,'openid');
        }
        if(empty($now_user)){
            if($is_wxapp){
                $now_user_weixin = D('User')->get_userinfo_wxapp($_POST['code']);
            }else{
                $now_user_weixin = D('User')->get_userinfo_weixin($openid);
            }
            fdump_sql($now_user_weixin,'[ID1006699]');
            if (!isset($now_user_weixin['nickname'])) {
                S($preventRepeatKey, null);
                return false;
            }
            $nickname = $now_user_weixin['nickname'];
        }else{
            $nickname = $now_user['nickname'];
        }
        $nickname = $nickname ?: '用户';
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $href = C('config.site_url').'/wap.php?g=Wap&c=My&a=spread_user_list';
        $where['_string'] = ' (content like "%'.$spread_openid.'%") AND ( content like "%'.$nickname.'通过您的分享成为了您的下级推广用户%") ';
		
        // 2020/06/25 不校验，SQL太慢
        //$alsend = M("Process_plan_msg")->where($where)->find();
		$alsend = null;
        if(!$alsend){
            $model->sendTempMsg('OPENTM405462911', 
                array(
                    'href' => $href, 
                    'wecha_id' => $spread_openid, 
                    'first' => $nickname.L_('通过您的分享成为了您的下级推广用户。时间：').date("Y年m月d日 H:i").'\n', 
                    'keyword1' => L_('推广用户信息'), 
                    'keyword2' =>L_('已发送'),
                    'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
                    'keyword4' => L_('推广用户'), 
                    'remark' => '\n请点击查看详细信息！'
                )
            );
        }
        S($preventRepeatKey, null);
    }

    public  function get_spread_list($uid,$level=1,$user_spread=array()){
        $where['uid'] = $uid;
        $res = $this->where($where)->find();

        if(!$res['spread_uid']){
            return $user_spread;
        }

        if( $level<4){
            $user_spread[$level] = $res['spread_uid'];
            $level++;
            return  $this->get_spread_list($res['spread_uid'],$level,$user_spread);
        }else{
            return $user_spread;
        }
    }

    // 获得用户一级二级三级分佣用户总数
    public function get_spread_user_count($now_user_openid,$uid){
        $where = array(
            '_string' => "us.spread_uid='$uid'"
        );
        $count = 0;
        $now_user_spread_user_list =$this->field('`u`.*,`us`.`uid` AS `user_uid`')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where($where)->group('us.uid')->select();
        foreach($now_user_spread_user_list as &$v){
            $count++;
            $now_openid = $v['uid'];
            $now_uid = $v['uid'];
            $where = array(
                '_string' => "us.spread_uid='$now_openid'"
            );
            $openid_arr =$this->field('`us`.`uid`')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where($where)->group('us.openid')->select();
            foreach ($openid_arr as $_k => $_second) { 
                $count++;
                $second_openid = $_second['openid'];
                $second_uid = $_second['uid'];
                $where = array(
                    '_string' => "us.spread_uid='$second_uid'"
                );
                $third =$this->field('`us`.`openid`')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid = u.uid')->where($where)->group('us.uid')->count();
                if ($third) { 
                    $count += $third;
                }
            }
        }
        return $count;
    }


    /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
    public function spread_user_only_once($spread_type,$spread_total_money,$now_user,$now_order=array()){
        //一级推广用户
        $return = array(
            'error_code' =>0,
            'msg' =>'添加成功',
        );
        // 查询推广用户
        $spread_where['_string'] = "uid = '{$now_user['uid']}'";
        $now_user_spread = D('User_spread')->where($spread_where)->find();

        if(empty($now_user_spread)){
            $return['error_code'] = 1;
            $return['msg'] = '没有推广用户';
            return $return;
        }

        // 查询推广用户已获得佣金信息
        $user_spread_list = D('User_spread_list')->where(array('uid'=>$now_user_spread['spread_uid'],'get_uid'=>$now_user['uid'],'status' => array('neq',2)))->find();
        if ($user_spread_list) {
            // 获得过奖励 直接返回
            $return['error_code'] = 1;
            $return['msg'] = '推广用户已获得过奖励，不能再获得';
            return $return;
        }

        // 推广用户详细信息
            $spread_user = D('User')->get_user($now_user_spread['spread_uid']);
        if(!$spread_user){
            // 推广用户不存在
            $return['error_code'] = 1;
            $return['msg'] = '推广用户不存在';
            return $return;
        }
        if($spread_user['uid'] == $now_user['uid']){
            // 推广用户是自己
            $return['error_code'] = 1;
            $return['msg'] = '推广用户是自己';
            return $return;
        }

        //一级分享佣金比例
        if ($spread_type=='activity') {
            $spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['activity_id'],'activity',$now_order['order_id']);
        }else{
            $spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],$spread_type);
        }
        $user_spread_rate = $spread_rate['first_rate'];

        // 一级分享积分
        if ($spread_type=='group') {
            $score_spread_rate = D('Percent_rate')->get_user_score_spread_rate($now_order['mer_id'],'group',$now_order['group_id']);
            $user_score_spread_rate = $score_spread_rate['first_rate'];
        }

        if(!$user_spread_rate||!$user_score_spread_rate){
            $return['error_code'] = 1;
            $return['msg'] = '推广佣金比例未设置';
            return $return;
        }

        if ($user_score_spread_rate ) {
            // 一级分享积分获得
            $spread_score = round($spread_total_money * $user_score_spread_rate / 100, 2);
        }

        $open_extra_price = C('config.open_extra_price');
        $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
        // 将获得的佣金
        $spread_money = round($spread_total_money * $user_spread_rate / 100, 2);
        // 添加数据
        $spread_data = array(
            'uid'=>$spread_user['uid'],
            'spread_uid'=>0,
            'get_uid'=>$now_user['uid'],
            'money'=>$spread_money,
            'order_type'=>$spread_type,
            'order_id'=>$now_order['order_id'],
            'add_time'=>$_SERVER['REQUEST_TIME']
        );
        switch ($spread_type) {
            case 'shop':
            case 'mall':
                if (C('config.shop_goods_spread_edit')==1) { 
                    // 开启每个商品单独佣金
                    $spread_money = $user_spread_rate;
                }

                $spread_data['third_id'] = $now_order['store_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                break;
            case 'meal':
                $spread_data['third_id'] = $now_order['store_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                break;
            case 'store':
            case 'cash':
                $spread_data['third_id'] = $now_order['store_id'];
                // 处理长订单id
                if ($now_order['orderid']) {
                    $spread_data['real_orderid'] = $now_order['orderid'];
                }
                break;
            case 'group':
                $spread_data['third_id'] = $now_order['group_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }

                if ($user_score_spread_rate) {
                    // 一级分享积分获
                    $spread_data_score = array(
                        'uid'=>$spread_user['uid'],
                        'spread_uid'=>0,
                        'get_uid'=>$now_user['uid'],
                        'money'=>$spread_score,
                        'order_type'=>'group',
                        'spread_type'=>'1',
                        'order_id'=>$now_order['order_id'],
                        'third_id'=>$now_order['group_id'],
                        'add_time'=>$_SERVER['REQUEST_TIME']
                    );
                    if($spread_user['spread_change_uid']!=0){
                        $spread_data_score['change_uid'] =    $spread_user['spread_change_uid'];
                    }
                    // 处理长订单id
                    if ($now_order['real_orderid']) {
                        $spread_data_score['real_orderid'] = $now_order['real_orderid'];
                    }
                    D('User_spread_list')->data($spread_data_score)->add();
                }
                break;
            case 'sub_card':
                $spread_data['third_id'] = $now_order['store_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                if (!$spread_data['real_orderid'] && $plat_order_info['orderid']) {
                    $spread_data['real_orderid'] = $plat_order_info['orderid'];
                }
                break;
            case 'recharge':
                $spread_data['third_id'] = $now_order['store_id'];
                if($spread_user['spread_change_uid']!=0){
                    $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
                }
                // 处理长订单id
                if ($now_order['orderid']) {
                    $spread_data['real_orderid'] = $now_order['orderid'];
                }
                break;
            case 'classifynew':
                $spread_data = array(
                    'uid'=>$spread_user['uid'],
                    'spread_uid'=>0,
                    'get_uid'=>$now_user['uid'],
                    'money'=>$spread_money,
                    'order_type'=>$spread_type,
                    'order_id'=>0,
                    'third_id'=>0,
                    'status'=>1,
                    'add_time'=>$_SERVER['REQUEST_TIME']
                );
                break;
            case 'yuedan':
                $spread_data['third_id'] = $now_order['rid'];
                break;
            case 'activity':
                $spread_data['third_id'] = $now_order['activity_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                break;
        }

        if($spread_user['spread_change_uid']!=0){
            $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
        }

        if ($spread_money<=0) {
            $return['error_code'] = 1;
            $return['msg'] = '推广佣金小于等于0';
            return $return;
        }

        D('User_spread_list')->data($spread_data)->add();
        $buy_user = D('User')->get_user($now_user_spread['openid'],'openid');
        if($open_extra_price){
            $money_name = C('config.extra_price_alias_name');
        }else{
            $money_name = L_('佣金');
        }
        if ($spread_type=='activity') {
            $money_name = L_('活动佣金');
            $message =  $buy_user['nickname'] . '通过您的分享参与了活动，验证消费后您将获得'.$money_name.'。';
        }else{
            $message =  L_('x1通过您的分享购买商品，验证消费后您将获得x2。',array('x1'=>$buy_user['nickname'],'x2'=>$money_name));

        }
        
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $model->sendTempMsg(
            'OPENTM201812627', 
            array(
                'href' => $href, 
                'wecha_id' => $spread_user['openid'], 
                'first' => $message,
                'keyword1' => $spread_money, 
                'keyword2' => date("Y年m月d日 H:i"), 
                'remark' => L_('点击查看详情！'),
            ),
            $now_order['mer_id']
        );
        return $return;
    }


    //将所有的推广记录加上uid，以后就用uid了，不用openid了，因为用uid是规范的
    public function updateUid($openid, $uid){
        if(empty($openid) || empty($uid)) return ;
        $this->where(['spread_openid'=> $openid])->data(['spread_uid'=>$uid])->save();
        $this->where(['openid'=> $openid])->data(['uid'=>$uid])->save();
        return ;
    }

    /* 开启三级推广随机一级推广奖励功能，唯一次奖励 
        *a用户通过个人中心的推广海报推广下级的一级新用户b（必须为平台新用户），b用户在平台下单后（任意业务），a用户即可获得平台奖励的一次性随机佣金金额（只奖励一次性）。
     */
    public function spread_rand_only_once($spread_type,$now_user,$now_order=array()){
        if (C('config.open_spread_rand')!=1) {
            $return = array(
                'error_code' =>0,
                'msg' =>'一级推广随机奖励未开启',
            );
        }

        //一级推广用户
        $return = array(
            'error_code' =>0,
            'msg' =>'添加成功',
        );

        // 查询推广用户
        $spread_where['_string'] = "uid = '{$now_user['uid']}'";
        $now_user_spread = D('User_spread')->where($spread_where)->find();

        //判断推广用户是否可以获得佣金
        if ($now_user_spread && !((new UserModel())->canSpread($now_user_spread['spread_uid']))) {
            $now_user_spread = [];
        }

        if(empty($now_user_spread)){
            $return['error_code'] = 1;
            $return['msg'] = '没有推广用户';
            return $return;
        }

        // 查询推广用户已获得佣金信息
        $user_spread_list = D('User_spread_list')->where(array('uid'=>$now_user_spread['spread_uid'],'get_uid'=>$now_user['uid'],'is_rand'=>1,'status' => array('neq',2)))->find();
        if ($user_spread_list) {
            // 获得过奖励 直接返回
            $return['error_code'] = 1;
            $return['msg'] = '推广用户已获得过奖励，不能再获得';
            return $return;
        }

        // 推广用户详细信息
        $spread_user = D('User')->get_user($now_user_spread['spread_uid']);
        if(!$spread_user){
            // 推广用户不存在
            $return['error_code'] = 1;
            $return['msg'] = '推广用户不存在';
            return $return;
        }

        if($spread_user['uid'] == $now_user['uid']){
            // 推广用户是自己
            $return['error_code'] = 1;
            $return['msg'] = '推广用户是自己';
            return $return;
        }

        $min = C('config.spread_rand_min');
        $max = C('config.spread_rand_max');
        if (!is_numeric($min) || !is_numeric($max)) {
            $return['error_code'] = 1;
            $return['msg'] = '随机金额最小值或最大值未不正确';
            return $return;
        }

        if ($min>$max) {
            $return['error_code'] = 1;
            $return['msg'] = '随机金额最小值不能大于最大值';
            return $return;
        }

        // 将获得的佣金
        $spread_money = randomFloat($min ,$max);

        $open_extra_price = C('config.open_extra_price');
        $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
        // 添加数据
        $spread_data = array(
            'uid'=>$spread_user['uid'],
            'spread_uid'=>0,
            'is_rand'=>1,
            'get_uid'=>$now_user['uid'],
            'money'=>$spread_money,
            'order_type'=>$spread_type,
            'order_id'=>$now_order['order_id'],
            'add_time'=>$_SERVER['REQUEST_TIME']
        );
        switch ($spread_type) {
            case 'shop':
            case 'mall':
                $spread_data['third_id'] = $now_order['store_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                break;
            case 'meal':
                $spread_data['third_id'] = $now_order['store_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                break;
            case 'store':
            case 'cash':
                $spread_data['third_id'] = $now_order['store_id'];
                // 处理长订单id
                if ($now_order['orderid']) {
                    $spread_data['real_orderid'] = $now_order['orderid'];
                }
                break;
            case 'group':
                $spread_data['third_id'] = $now_order['group_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                break;
            case 'sub_card':
                $spread_data['third_id'] = $now_order['store_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                if (!$spread_data['real_orderid'] && $plat_order_info['orderid']) {
                    $spread_data['real_orderid'] = $plat_order_info['orderid'];
                }
                break;
            case 'recharge':
                $spread_data['third_id'] = $now_order['store_id'];
                if($spread_user['spread_change_uid']!=0){
                    $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
                }
                // 处理长订单id
                if ($now_order['orderid']) {
                    $spread_data['real_orderid'] = $now_order['orderid'];
                }
                break;
            case 'classifynew':
                $spread_data['order_id'] = 0;
                $spread_data['third_id'] = 0;
                $spread_data['status'] = 1;
                break;
            case 'yuedan':
                $spread_data['third_id'] = $now_order['rid'];
                break;
            case 'activity':
                $spread_data['third_id'] = $now_order['activity_id'];
                // 处理长订单id
                if ($now_order['real_orderid']) {
                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                }
                break;
        }

        if($spread_user['spread_change_uid']!=0){
            $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
        }

        if ($spread_money<=0) {
            $return['error_code'] = 1;
            $return['msg'] = '推广佣金小于等于0';
            return $return;
        }

        D('User_spread_list')->data($spread_data)->add();
        $buy_user = D('User')->get_user($now_user_spread['openid'],'openid');
        if($open_extra_price){
            $money_name = C('config.extra_price_alias_name');
        }else{
            $money_name = L_('佣金');
        }
        if ($spread_type=='activity') {
            $money_name = L_('活动佣金');
            $message =  $buy_user['nickname'] . '通过您的分享参与了活动，验证消费后您将获得'.$money_name.'。';
        }else{
            $message =  L_('x1通过您的分享购买商品，验证消费后您将获得x2。',array('x1'=>$buy_user['nickname'],'x2'=>$money_name));

        } 

        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $model->sendTempMsg(
            'OPENTM201812627', 
            array(
                'href' => $href, 
                'wecha_id' => $spread_user['openid'], 
                'first' => $message,
                'keyword1' => $spread_money, 
                'keyword2' => date("Y年m月d日 H:i"), 
                'remark' => L_('点击查看详情！'),
            ),
            $now_order['mer_id']
        );
        return $return;
    }

    public function get_spreaduser_count($openid,$uid){
        if(empty($openid) && empty($uid)) return 0;
        
        $where = 'spread_uid =' . $uid ;

        if($openid){
            $where .= ' OR spread_openid ="' . $openid . '"';
        }
        return $this->where($where)->count();
    }
}
