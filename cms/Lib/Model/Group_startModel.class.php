<?php
    class Group_startModel extends Model{
        //发起团购
        public function start_group($uid,$now_group){
            $data['uid'] = $uid;    //发起团购的用户id
            $data['group_id'] = $now_group['group_id'];
            $data['mer_id'] = $now_group['mer_id'];
            $data['num'] = 0;
            $data['complete_num'] = $now_group['pin_num'];
            $data['status'] = 4; //暂定 还未支付
            $data['start_time'] = $_SERVER['REQUEST_TIME'];
            $data['last_time'] = $data['start_time'];
            $data['discount'] = $now_group['start_discount'];
            $fid = $this->add($data);
            if(!$fid){
                return array('error_code'=>1,'msg'=>L_('发起团购失败'));
            }else{
                return array('error_code'=>0,'msg'=>$fid);
            }
        }

        public function fastest_group($fid,$num=3){
            $res = $this->where(array('s.group_id'=>$fid,'s.status'=>array('lt',2)))->join('as s LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid =s.uid')->field('s.*,u.nickname,u.avatar')->order('s.status ASC,s.num DESC ,s.start_time DESC')->limit($num)->select();
            return $res;
        }

        //参加团购 按人数算
        public function add_group($fid,$uid,$now_group,$order_id,$gid=0){
            $where['group_id']=$fid;
            //$where['status']='0';
            $where['start_time']=array('egt',time()-$now_group['pin_effective_time']*3600);
            $where['uid']=array('neq',$uid);
            //团购排序
            if(empty($gid)){
                //$where['status']=0;
                $the_fastest = $this->where(array('group_id'=>$fid,'status'=>array('lt',2)))->order('status ASC,num DESC ,start_time DESC')->find();
            }else{
                $where['id']=$gid;
                $the_fastest = $this->where($where)->find();
            }
            $already_buy = M('Group_buyer_list')->where(array('fid'=>$the_fastest['id'],'uid'=>$uid))->find();
            $this->add_buyer_list($the_fastest['id'],$uid,$order_id);
           
            if($the_fastest && empty($already_buy)){
                //参团人数加1
                $where['id'] = $the_fastest['id'];
                $this->where($where)->setInc('num',1);
                //用户参团后参团人数大于等于设置的成团人数，团购成功
                if($the_fastest['complete_num']<=$the_fastest['num']+1){
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    //获取参团用户信息，推送成团消息
                    $buyer = $this->get_buyerer_by_order_id('',$where['id']);
                    foreach($buyer as $v){
                        if($v['type']==1){
                            continue;
                        }
                        $sms_data = array('mer_id' => $v['mer_id'], 'store_id' => $v['store_id'], 'type' => 'group');
                        $href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$v['order_id'];

                        $remark = L_("X1成功",array("X1" => C('config.group_alias_name')));
                        // 实物没有消费码
                        if ($now_group['tuan_type']!=2) {
                             $remark .= L_("，您的消费码：X1。",array("X1" => $v['group_pass']));
                        }

                        $model->sendTempMsg('TM00017',
                            array('href' => $href,
                                'wecha_id' => $v['openid'],
                                'first' => C('config.group_alias_name').L_('提醒'),
                                'OrderSn' => $v['real_orderid'],
                                'OrderStatus' => L_('您购买的拼团x1已于 x2 成团',array('x1'=>$v['order_name'],'x2'=>date('Y-m-d H:i:s'))),
                                'remark' => $remark), $v['mer_id']); 

                        if(C('config.sms_pin_success_order')==1) {
                            $content = L_("您购买的拼团x1已于 x2 成团",array('x1'=>$v['order_name'],'x2'=>date('Y-m-d H:i:s')));
                            // 实物没有消费码
                            if ($now_group['tuan_type']!=2) {
                                $content .= L_("，您的消费码：X1。",array("X1" => $v['group_pass']));
                            }

                            $sms_data['uid'] = $uid;
                            $sms_data['mobile'] = $v['phone'];
                            $sms_data['sendto'] = 'user';
                            $sms_data['content'] = $content;
                            Sms::sendSms($sms_data);
                        }
                    }
                    //更新团的状态
                    return $this->where($where)->save(array('status'=>1));
                }else{
                    return $the_fastest;
                }
            }else{
                //不存在可参与的团或已参与过该团，自动发起新的团
                return $this->start_group($uid,$now_group);
            }
        }

        //记录参团用户信息
        public function add_buyer_list($fid,$uid,$order_id){
            $buyer_info['fid']=$fid;
            $buyer_info['order_id']=$order_id;
            $buyer_info['uid']=$uid;
            return M('Group_buyer_list')->add($buyer_info);
        }


        //支付成功后更新
        public function update_buy_list($now_order){
            $data['status']=1;
            return M('Group_buyer_list')->where(array('order_id'=>$now_order['order_id']))->save($data);
        }

        public function update_start_group($fid,$status){
            if($status==3){
                if(is_array($fid)){
                    $where['id'] = array('in',$fid);
                }else{
                    $where['id'] =$fid;
                }
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                $buyer = $this->get_buyerer_by_order_id('',$where['id']);
                foreach($buyer as $v){
                    if($v['type']==1){
                        continue;
                    }
                    $sms_data = array('mer_id' => $v['mer_id'], 'store_id' => $v['store_id'], 'type' => 'group');
                    $href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$v['order_id'];
                    $model->sendTempMsg('TM00017',
                        array('href' => $href,
                            'wecha_id' => $v['openid'],
                            'first' => C('config.group_alias_name').L_('提醒'),
                            'OrderSn' => $v['real_orderid'],
                            'OrderStatus' => L_('您购买的拼团x1已于 x2 成团',array('x1'=>$v['order_name'],'x2'=>date('Y-m-d H:i:s'))),
                            'remark' => L_("X1成功，您的消费码：X2",array("X1" => C('config.group_alias_name'),"X2" => $v['group_pass']))), $v['mer_id']); 
                    if(C('config.sms_pin_success_order')==1) {
                        $sms_data['uid'] = $v['uid'];
                        $sms_data['mobile'] = $v['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['content'] = L_('您购买的拼团x1已于 x2 成团，您的消费码：x3。',array('x1'=>$v['order_name'],'x2'=>date('Y-m-d H:i:s'),'x3'=>$v['group_pass']));
                        Sms::sendSms($sms_data);
                    }
                }
                return $this->where($where)->save(array('status'=>1));

            }
            if(is_array($fid)){
                return $this->where(array('id'=>array('in',$fid)))->save(array('status'=>$status));
            }else{
                return $this->where(array('id'=>$fid))->save(array('status'=>$status));
            }
        }

        //校验团状态为“未成团”的团人数是否满足成团的条件
        public function check_start_group($fid,$now_group){
            $where['id']=$fid;
            $res = $this->where($where)->find();
            if($res['status'] == 0){
                if($res['complete_num']<=$res['num']){
                    $this->update_start_group($fid,1);
                }
            }
        }

        //根据订单id获取拼团组信息
        public function get_group_start_by_order_id($order_id){
            $res = M('Group_buyer_list')->where(array('order_id'=>$order_id))->find();
            return $this->where(array('id'=>$res['fid']))->find();
        }

        //未成团用户退款拼团人数减少
        public function buyer_refund_dec_by_orderid($order_id,$gid){
            $this->where(array('id'=>$gid))->setDec('num',1);
            M('Group_buyer_list')->where(array('order_id'=>$order_id))->delete();

        }

        public function timeout($order_id){
            $consumer = M('Group_buyer_list')->where(array('order_id'=>$order_id))->find();
            $this->where(array('id'=>$consumer['fid']))->setField('status',2);
        }


        //获取拼团小组成员 包括机器人
        public function get_buyerer_by_order_id($order_id,$gid=0){
            if(!empty($order_id)){
                $res = $this->get_group_start_by_order_id($order_id);
            }else{
                $res['id']=$gid;
            }
            return M('Group_buyer_list')->field('b.*,o.is_head,o.pay_time,o.status,o.group_pass,o.real_orderid,u.phone,u.nickname,u.avatar,u.openid')->join('as b left join '.C('DB_PREFIX').'user u ON b.uid = u.uid')->join(C('DB_PREFIX').'group_order o ON b.order_id = o.order_id')->where(array('b.fid'=>$res['id']))->group('b.uid,b.type')->order('is_head DESC')->select();
        }
		
		public function get_buyer_list_by_order_id($order_id,$gid=0){
            if(!empty($order_id)){
                $res = $this->get_group_start_by_order_id($order_id);
            }else{
                $res['id']=$gid;
            }
            return M('Group_buyer_list')->field('b.*,o.is_head,o.pay_time,o.status,o.group_pass,u.phone,u.nickname,u.avatar,u.openid')->join('as b left join '.C('DB_PREFIX').'user u ON b.uid = u.uid')->join(C('DB_PREFIX').'group_order o ON b.order_id = o.order_id')->where(array('b.fid'=>$res['id']))->order('is_head DESC')->select();
        }

        //根据商家id 获取团购小组列表
        public function get_start_group_list_by_merid($mer_id){
             $res = M('Group_start')
                        ->field('gs.id,gs.status,gs.num ,gs.complete_num,gs.start_time,gs.last_time,gs.discount,g.name as group_name,u.nickname,g.pin_effective_time,g.tuan_type')
                        ->join('as gs left join '.C('DB_PREFIX').'group g  ON gs.group_id = g.group_id')
                        ->join(C('DB_PREFIX').'user u ON gs.uid = u.uid')
                        ->where(array('gs.mer_id'=>$mer_id))->order('id DESC')->select();
             $now_time = $_SERVER['REQUEST_TIME'];
             $start_list = array();
             foreach($res as $v){
                 if($v['start_time']<$now_time-$v['pin_effective_time']*3600&&$v['status']==0){
                     $this->where(array('id'=>$v['id']))->setField('status',2);
                 }
                 if($v['status']!=4){
                     $start_list[] = $v;
                 }
             }
            return $start_list;
        }

        //根据分享gid 获取拼团小组 跟团长信息
        public function get_group_start_user_by_gid($gid){
            $share_group_info = M('Group_start')->field('s.* ,u.nickname,u.avatar,u.phone,u.uid')->join('as s left join '.C('DB_PREFIX').'user u ON s.uid = u.uid')->where(array('id'=>$gid))->find();
            return $share_group_info;
        }

        //获取可退款的拼团小组
        public function group_refund(){
            //$where['s.status'] = 0;
            $where['o.status'] = 0;
            $where['o.pay_type'] = array('neq','offline');
            $where['o.paid'] =1;
            $where['g.no_refund'] =0;
            $where['s.status'] =array('in','0,2');
            $where['_string'] = 'g.pin_effective_time<('.time().'-s.start_time)/3600';
            $refund_gorup_start = $this->field('o.*')
                ->join('AS s LEFT JOIN '.C('DB_PREFIX').'group_buyer_list AS l ON l.fid = s.id LEFT JOIN '.C('DB_PREFIX').'group_order AS o ON l.order_id = o.order_id LEFT JOIN '.C('DB_PREFIX').'group g ON s.group_id = g.group_id')
                ->where($where)->limit(5)->select();
            return $refund_gorup_start;
        }

        public function check_join_pin($group_id,$uid,$effective_time,$limit=3){
            $effective_time_ = time()-$effective_time*3600;
            $where=array(
                's.group_id' =>$group_id,
                's.status' => 0,
                's.start_time'=>array('gt',$effective_time_),
            );
            if(!empty($uid)){
                $where['s.uid']= array('neq',$uid);
               // $start_head = $this->where(array('group_id' => $group_id, 'status' => 0,'uid'=>$uid))->find();
            }else{
                //$start_head = false;
            }

            //筛选出自己可以参加的拼团
            $start = $this->join('as s LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid =s.uid')->field('s.*,u.nickname,u.avatar')->where($where)->order('s.num DESC,s.start_time ASC')->limit($limit)->select();
            $now_time =time();
            $d_group_order = M('Group_order');
            $d_group_buyer_list = M('Group_buyer_list');
            foreach ($start as $k => &$vv) {
                $order_id = $d_group_buyer_list->where(array('fid' => $vv['id'], 'uid' => $vv['uid'],'order_id' => array('gt', 0)))->order('id ASC')->getField('order_id');
                if ($order_id && $order_id>0) {
                    $status = $d_group_order->where(array('order_id' => $order_id))->getField('status');
                    if ($status==3) {
                        // 拼团团长已经退款，取消拼团
                        $this->timeout($order_id);
                        unset($start[$k]);
                    }
                }
                $vv['end_time'] = $vv['start_time'] + $effective_time * 3600;
//                if(preg_match('/\d{3}\*{4}\d{4}/',$vv['nickname'],$m)|| preg_match('/\d{11}/',$vv['nickname'],$m)){
//                    $vv['nickname'] = L_('游客');
//                }
                if (preg_match('/\d{11}/',$vv['nickname'],$m)) {
                    $vv['nickname'] = mb_substr($vv['nickname'],0,10);
                } else if(preg_match('/\d{3}\*{4}\d{4}/',$vv['nickname'],$m)){
                    $vv['nickname'] = L_('游客');
                }
                $vv['surplus_time'] = $vv['end_time'] - $now_time;
            }
            $start = array_values($start);

            if($start){
                return $start;
            }else{
                return false;
            }
        }

        /*
         * 生成拼团机器人
         * */

        public function create_robot($start_info){
            $num = $start_info['complete_num']-$start_info['num'];
            $img[1]=C('config.site_url').'/upload/robot_avator/user_avatar.jpg';
            $img[2]=C('config.site_url').'/upload/robot_avator/tx.png';
            $robot_count = M('Robot_list')->where(array('mer_id'=>$start_info['mer_id']))->count();
            if($robot_count<$num){
                import('@.ORG.randName');
                $name = new randName();
                for($i=0;$i<$num-$robot_count;$i++){
                    $tmp['robot_name'] = $name->getName();
                    $tmp['avatar'] = $img[rand(1,2)];
                    $tmp['mer_id'] = $start_info['mer_id'];
                    $tmp['add_time'] = time();
                    $robot_name_arr[] = $tmp;
                }
                M('Robot_list')->addAll($robot_name_arr);
            }
            $robot_list = M('Robot_list')->where(array('mer_id'=>$start_info['mer_id']))->select();
            shuffle($robot_list);
            for($i=0;$i<$num;$i++){
                $tmp_buyer['fid'] = $start_info['id'];
                $tmp_buyer['order_id'] = 0;
                $tmp_buyer['uid'] = $robot_list[$i]['id'];
                $tmp_buyer['type'] = 1;
                $robot_buyer[] = $tmp_buyer;
            }

            M('Group_buyer_list')->addAll($robot_buyer);
            $this->where(array('id'=>$start_info['id']))->setField('num',$start_info['complete_num']);
            return ;
        }


        /**
         * 获取对应用户团购
         * @param $group_id
         * @param $uid
         * @param $effective_time
         * @param $limit
         * @return bool
         */
        public function check_user_pin($group_id,$uid,$effective_time, $limit = 3){
            $effective_time_ = time()-$effective_time*3600;
            $where=array(
                's.group_id' =>$group_id,
                's.status' => 0,
                's.start_time'=>array('gt',$effective_time_),
            );
            if(!empty($uid)){
                $where['s.uid']= array('eq',$uid);
                // $start_head = $this->where(array('group_id' => $group_id, 'status' => 0,'uid'=>$uid))->find();
            }else{
                //$start_head = false;
            }

            //筛选出自己可以参加的拼团
            $start = $this->join('as s LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid =s.uid')->field('s.*,u.nickname,u.avatar')->where($where)->order('s.num DESC,s.start_time ASC')->limit($limit)->select();
            $now_time =time();

            foreach ($start as &$vv) {
                $vv['end_time'] = $vv['start_time'] + $effective_time * 3600;
//                if(preg_match('/\d{3}\*{4}\d{4}/',$vv['nickname'],$m) || preg_match('/\d{11}/',$vv['nickname'],$m)){
//                    $vv['nickname'] = L_('游客');
//                }
                if (preg_match('/\d{11}/',$vv['nickname'],$m)) {
                    $vv['nickname'] = mb_substr($vv['nickname'],0,10);
                } else if(preg_match('/\d{3}\*{4}\d{4}/',$vv['nickname'],$m)){
                    $vv['nickname'] = L_('游客');
                }
                $vv['surplus_time'] = $vv['end_time'] - $now_time;
            }

            if($start){
                return $start;
            }else{
                return false;
            }
        }

    }