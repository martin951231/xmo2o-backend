<?php
/*
 * 好友瓜分券
 *
 * @  BuildTime  2019/06/28 09:37
 * @  auth  heng
 * 
 */
class Partition_couponAction extends BaseAction{
    public function _initialize() {
        parent::_initialize();
    }

    public function check_login(){
        if(empty($this->user_session['uid'])){
            if($this->is_app_browser){
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！',U('Login/index',$location_param));
            }else{
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index',$location_param));
            }
        }else{
            $now_user = D("User")->get_user($this->user_session['uid']);
            if(empty($now_user)){
                if($this->is_app_browser){
                    $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                    $this->error_tips('请先进行登录！',U('Login/index',$location_param));
                }else{
                    $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                    redirect(U('Login/index',$location_param));
                }
            }
        }
    }

    //平台瓜分券列表
    public function partition_coupon_list(){
        $type = $_GET['type'] ? $_GET['type'] : 'system';
        if ($type=='system') {
            $database_coupon = D('System_partition_coupon');
        }else{
            $database_coupon = D('Merchant_partition_coupon');
        }

        $this->display();
    }

    /**ajax
     * [activity_list 活动列表]
     * @return [type] [description]
     */
    public function partition_coupon_detail(){
        $type = $_GET['type'] ? $_GET['type'] : 'system';
        if ($type=='system') {
            $database_coupon = D('System_partition_coupon');
            $database_coupon_hadpull = D('System_partition_coupon_hadpull');
        }else{
            $database_coupon = D('Merchant_partition_coupon');
            $database_coupon_hadpull = D('Merchant_partition_coupon_hadpull');
        }
        // 瓜分券详情
        $now_detail = $database_coupon->where(array('pigcms_id'=> $_GET['id']))->find();

        // 查看是否已加入
        $has_hadpull = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>0,'uid'=>$this->user_session['uid']))->find();
        $this->assign('has_hadpull',$has_hadpull);
        $this->assign('now_detail',$now_detail);
        $this->assign('user_session',$this->user_session);
        $this->display();
        
    }  

    public function ajax_partition_coupon_detail(){
        $type = $_POST['type'] ? $_POST['type'] : 'system';
        if ($type=='system') {
            $database_coupon = D('System_partition_coupon');
            $database_coupon_hadpull = D('System_partition_coupon_hadpull');
        }else{
            $database_coupon = D('Merchant_partition_coupon');
            $database_coupon_hadpull = D('Merchant_partition_coupon_hadpull');
        }
        // 瓜分券详情
        $now_detail = $database_coupon->where(array('pigcms_id'=> $_POST['id']))->find();
        if (!$now_detail) {
            // 瓜分券不存在或已删除
            $this->returnCode('75000001');
        }
                    
        $return['is_expire'] = 0;
        // 查看是否已加入
        $has_hadpull = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>0,'uid'=>$this->user_session['uid']))->order('receive_time DESC')->find();
        if (!$has_hadpull) { 
            // 分享人id
            if ($_POST['openid']) {
                $share_user = D('User')->get_user($_POST['openid'],'openid');
                $share_uid = intval($share_user['uid']);
                $return['share_name'] = $share_user['nickname'];
            }
			if ($_POST['share_uid']) {
                $share_user = D('User')->get_user($_POST['share_uid']);
                $share_uid = intval($share_user['uid']);
                $return['share_name'] = $share_user['nickname'];
            }

            if ($share_uid) {
                // 查询分享的瓜分券
                $share_coupon = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>0,'uid'=>$share_uid))->order('id desc')->find();
                if ($share_coupon['pid']) {
                    $share_coupon = $database_coupon_hadpull->where(array('id'=>$share_coupon['pid'],'status'=>0))->find();
                }

                if($share_coupon){
                     // 查看是否已过期
                    if ($now_detail['end_time']>time() && $share_coupon['receive_time']+$now_detail['effective_time']>time()) {
                        $where['_string'] = '(h.id='.$share_coupon['id'].' OR h.pid='.$share_coupon['id'].')';
                        // $where['h.status'] = 0;
                        $join_coupon_list = $database_coupon_hadpull->field('h.*,u.avatar')->join(' as h left join '.C('DB_PREFIX').'user as u on u.uid=h.uid')->where($where)->order('h.pid ASC')->select();
                        // 拼团剩余时间
                        $return['remaining_time'] = $now_detail['effective_time'] - (time()-$join_coupon_list[0]['receive_time']);
                    }else{
                        if ($share_coupon['receive_time']+$now_detail['effective_time']<time()){
                            // 是否到期
                            $return['is_expire'] = 2;
                        }
                    }
                }
            }

            if ($now_detail['end_time']<time()){
                // 是否到期
                $return['is_expire'] = 1;
            }

            // 领取数量到达上限
            $return['toplimit'] = 0;
            $has_hadpull_count = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>1,'uid'=>$this->user_session['uid']))->count();
            if ($has_hadpull_count>=$now_detail['partition_times']&&$now_detail['partition_times']>0) {
                $return['toplimit'] = 1;
            }
        }else{
            if ($has_hadpull['pid']) {
                $has_hadpull_join = $has_hadpull;
                $has_hadpull = $database_coupon_hadpull->where(array('id'=>$has_hadpull['pid']))->find();
            }
             // 查看是否已过期
            if ($now_detail['end_time']>time() && $has_hadpull['receive_time']+$now_detail['effective_time']>time()) {
                $where['_string'] = '(h.id='.$has_hadpull['id'].' OR h.pid='.$has_hadpull['id'].')';
                // $where['h.status'] = 0;
                $join_coupon_list = $database_coupon_hadpull->field('h.*,u.avatar')->join(' as h left join '.C('DB_PREFIX').'user as u on u.uid=h.uid')->where($where)->order('h.pid ASC')->select();
                // 拼团剩余时间
                $return['remaining_time'] = $now_detail['effective_time'] - (time()-$has_hadpull['receive_time']);
            }else{
                if ($has_hadpull['receive_time']+$now_detail['effective_time']<time()){
                    // 是否到期
                    $return['is_expire'] = 2;
                }
                if ($now_detail['end_time']<time()){
                    // 是否到期
                    $return['is_expire'] = 1;
                }
                // var_dump($return['is_expire']);
            }

        }

        // 是否人数达到
        $return['is_complete'] = 0;
        if (count($join_coupon_list)==$now_detail['user_num']) {
            $return['is_complete'] = 1;
        }
        
        if ($return['remaining_time']) { 
            $return['remaining_day'] = floor($return['remaining_time']/86400);
            $remaining_time = $return['remaining_time'] - $return['remaining_day']*86400;
            $return['remaining_hour'] = floor($remaining_time/3600);
            $remaining_time = $remaining_time - $return['remaining_hour']*3600;
            $return['remaining_min'] = floor($remaining_time/60);
            $remaining_time = $remaining_time - $return['remaining_min']*60;
            $return['remaining_sec'] = $remaining_time;
        }

        // 瓜分券有效期
        $timeStr = '';
        if ($now_detail['day']) {
            $timeStr .= $now_detail['day'].'天';
        }
        if ($now_detail['hour']) {
            $timeStr .= $now_detail['hour'].'小时';
        }
        if ($now_detail['minute']) {
            $timeStr .= $now_detail['minute'].'分钟';
        }
        $now_detail['timeStr'] = $timeStr;

        // 瓜分券可使用期
        $useTimeStr = '';
        if ($now_detail['time_type']==1) {
            $useTimeStr .= $now_detail['use_time'].'天内';
        }else{
            $useTimeStr .= date('Y-m-d H:i',$now_detail['use_end_time']).'之前';
        }
        $now_detail['useTimeStr'] = $useTimeStr;

        // 读取最近10条领取记录
        $coupon_hadpull_list = $database_coupon_hadpull->field('h.*,u.nickname')->join(' as h left join '.C('DB_PREFIX').'user as u on u.uid=h.uid')->where(array('h.status'=>1))->order('h.partition_time DESC')->limit(10)->select();

        $return['now_detail'] = $now_detail;
        $return['has_hadpull'] = $has_hadpull_join ? $has_hadpull_join :$has_hadpull;
        $return['join_list'] = $join_coupon_list ? $join_coupon_list : array();
        $return['number'] = count($join_coupon_list);
        $return['diff_num'] = $now_detail['user_num']-count($join_coupon_list);
        $return['share_uid'] = intval($share_uid);
        $return['share_coupon'] = $share_coupon;
        $return['coupon_hadpull_list'] = $coupon_hadpull_list ? $coupon_hadpull_list : array();
        $this->returnCode('0',$return);
        
    }

    // 参与瓜分
    public function partition_coupon_join()
    { 
        if(empty($this->user_session['uid'])){
            $this->returnCode('10044010');
        }
        $type = $_POST['type'] ? $_POST['type'] : 'system';
        // 瓜分类型 1瓜分新券，2参与瓜分
        $partition_type = $_POST['partition_type'] ? $_POST['partition_type'] : 1;
        if ($type=='system') {
            $database_coupon = D('System_partition_coupon');
            $database_coupon_hadpull = D('System_partition_coupon_hadpull');
        }else{
            $database_coupon = D('Merchant_partition_coupon');
            $database_coupon_hadpull = D('Merchant_partition_coupon_hadpull');
        }
        // 瓜分券详情
        $now_detail = $database_coupon->where(array('pigcms_id'=> $_POST['id'],'delete'=>0))->find();
        if (!$now_detail) {
            // 瓜分券不存在或已删除
            $this->returnCode('75000001');
        }
        
        //瓜分券限流，同一个瓜分活动一秒钟只能一人加入，其他人提示重新操作
        $uniqueKey = md5($_POST['id'] . '_' . 'partition_coupon_join_' . time());
        $lockRs = setLock($uniqueKey, 10);
        if ($lockRs['error_no']) {
            $this->returnCode('10000001', '系统繁忙，请重新再试');
        }

        if ($partition_type==2) {
            // 加入瓜分

            // 分享人id
            if ($_POST['share_uid']) {
                $share_user = D('User')->get_user($_POST['share_uid']);
                $share_uid = intval($share_user['uid']);
            }

            if (!$share_uid) {
                // 分享者有误
                $this->returnCode('75000012');
            }

            // 查询分享的瓜分券
            $share_coupon = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>0,'uid'=>$share_uid))->order('id desc')->find();
            if ($share_coupon['pid']) {
                $share_coupon = $database_coupon_hadpull->where(array('id'=>$share_coupon['pid'],'status'=>0))->find();
            }

            // 查看是否已过期
            if ($now_detail['end_time']<time() || $share_coupon['receive_time']+$now_detail['effective_time']<time()) {
                $database_coupon_hadpull->where(array('coupon'=> $_POST['id'],'status'=>0,'pid'=>0))->setField('status',2);
                $database_coupon_hadpull->where(array('coupon'=> $_POST['id'],'status'=>0,'pid'=>$share_coupon['id']))->setField('status',2);
                $this->returnCode('75000003');
            }

            // 查看是否已加入
            $has_hadpull = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>0,'uid'=>$this->user_session['uid']))->order('receive_time DESC')->find();
            if ($has_hadpull) {
                // 已参与瓜分此券，无需再次加入
                $this->returnCode('75000008');
            }

            // 已瓜分总数
            $has_partition_count = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>1,'uid'=>$this->user_session['uid']))->count();
            if ($has_partition_count >= $now_detail['partition_times'] && $now_detail['partition_times']) {
                // 瓜分次数已达上限
                $this->returnCode('75000007');
            }

            // 加入数据
            $data['uid'] = $this->user_session['uid'];
            $data['share_uid'] = $share_uid;
            $data['coupon_id'] = $now_detail['pigcms_id'];
            $data['num'] = 1;
            $data['pid'] = $share_coupon['id'];
            $data['receive_time'] = time();

            // 处理并发
            $check_db = M('partition_coupon_hadpull_check');
            $check_data = [];
            $check_data['coupon'] = json_encode($share_coupon);
            $check_data['data'] = json_encode($data);
            $check_data['type'] = $type;
            $check_id = $check_db->add($check_data);
            $arr= array();
            $arr['param'] = serialize(array());
            $arr['plan_time'] = -1 * time();
            $arr['space_time'] = 1;
            $arr['add_time'] = time();
            $arr['file'] = 'partition_coupon_hadpull_check';
            $arr['time_type'] = 0;
            $arr['unique_id'] = 'partition_coupon_hadpull_check';
            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            //M('process_sub_plan')->data($arr)->add();

            $process = true;
            //$i=0;
            //不要循环遍历了，直接去查询
            $i = 50;
            while($process){
                $check_info = $check_db->where(array('id'=>$check_id))->find();
                if($check_info['status']==1){//成功
                    $process = false;
                    $check_return = true;

                    $return_data = $check_info['msg'] ?  json_decode($check_info['msg'],true) : [];
                    $insert_id = $return_data['id'] ?? 0;
                    $is_complete = $return_data['is_complete'] ?? 0;
                }elseif($check_info['status']==2){//失败
                    $process = false;
                    $check_goods = json_decode($check_info['msg']);
                    $check_goods_str = implode(",", $check_goods);
                    $this->returnCode('75000009');
                }
                if ($i >= 50) {
                    //计划任务未开启，手动执行

                    // 查看参与瓜分总数
                    $where['_string'] = '(id='.$share_coupon['id'].' OR pid='.$share_coupon['id'].')';
                    $where['status'] = 0;
                    $where['coupon_id'] =$_POST['id'];
                    $has_hadpull_count = $database_coupon_hadpull->where($where)->count();
                    if ($has_hadpull_count>=$now_detail['user_num'] || $share_coupon['is_complete']) {
                        // 人数已满
                        $this->returnCode('75000009');
                    }

                    $insert_id = $database_coupon_hadpull->add($data);
                    $process = false;
                    if ($insert_id && $now_detail['user_num']<=($has_hadpull_count+1)) {
                        $is_complete = 1;
                        $database_coupon_hadpull->where(array('id'=> $share_coupon['id']))->setField('is_complete', 1);
                    }
                }
                $i++;
                usleep(300000);
            }

            if($insert_id){
                if ($is_complete) {
                    $this->returnCode('0',array('msg'=>'已集齐','complete'=>1,'id'=>$insert_id),'已成功瓜分金额');
                }

                // 瓜分剩余人数
                $surplus_num = max(0, $now_detail['user_num']-$has_hadpull_count-1);
                $this->returnCode('0',array('msg'=>'加入成功','complete'=>0,'money'=>$now_detail['money'],'surplus_num'=>$surplus_num));
                $this->returnCode('0',array('msg'=>'加入成功','complete'=>0),'加入成功');
            }else{
                // 操作失败
                $this->returnCode('75000011');
            }
        }else{
            // 发起瓜分
            if ($now_detail['status']==0) {
                // 瓜分券已停止瓜分，或已瓜分完
                $this->returnCode('75000002');
            }elseif ($now_detail['status']==2 || $now_detail['end_time']<time()) {
                // 该券已过期，不能领取
                $this->returnCode('75000003');
            }elseif ($now_detail['status']==3 || $now_detail['num'] <= $now_detail['had_pull']) {
                // 该券已领完，不能领取
                $this->returnCode('75000004');
            }
            if ($now_detail['start_time']>time()) {
                // 该券未开始，请活动开始后再来领取
                $this->returnCode('75000005');
            }

            $has_hadpull = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>0,'uid'=>$this->user_session['uid']))->order('receive_time DESC')->find();
            if ($has_hadpull['receive_time']+$now_detail['effective_time']>time()) {
                // 已参与瓜分此券，不能再发起新的瓜分
                $this->returnCode('75000006');
            }

            $has_partition_count = $database_coupon_hadpull->where(array('coupon_id'=> $_POST['id'],'status'=>1,'uid'=>$this->user_session['uid']))->count();
            if ($has_partition_count >= $now_detail['partition_times'] && $now_detail['partition_times']) {
                // 瓜分次数已达上限
                $this->returnCode('75000007');
            }

            $data['uid'] = $this->user_session['uid'];
            $data['coupon_id'] = $now_detail['pigcms_id'];
            $data['num'] = 1;
            $data['is_leader'] = 1;
            $data['receive_time'] = time();
            if($insert_id = $database_coupon_hadpull->add($data)){
                $database_coupon->where(array('pigcms_id'=> $_POST['id']))->setInc('had_pull',1);

                if ($now_detail['user_num']==1) {
                    $this->returnCode('0',array('msg'=>'已集齐','complete'=>1,'id'=>$insert_id),'已成功瓜分金额');
                }
                // 瓜分剩余人数
                $surplus_num = $now_detail['user_num']-1;
                $this->returnCode('0',array('msg'=>'领取成功','complete'=>0,'money'=>$now_detail['money'],'surplus_num'=>$surplus_num),'领取成功');
            }else{
                // 操作失败
                $this->returnCode('75000010');
            }
        }
    }

    // 领取瓜分金额
    public function partition_coupon_receive()
    { 
        
        $type = $_POST['type'] ? $_POST['type'] : 'system';
        $id = intval($_POST['id']);

        if ($type=='system') {
            $database_coupon = D('System_partition_coupon');
            $database_coupon_hadpull = D('System_partition_coupon_hadpull');
        }else{
            $database_coupon = D('Merchant_partition_coupon');
            $database_coupon_hadpull = D('Merchant_partition_coupon_hadpull');
        }

        // 查询瓜分券
        $coupon_hadpull = $database_coupon_hadpull->where(array('id'=> $_POST['id'],$this->user_session['uid']))->find();
        if (!$coupon_hadpull) {
            // 该券不存在或已删除
            $this->returnCode('75000014');
        }        

        if ($coupon_hadpull['status'] == 1) {
            // 该券已领取，不能再重复领取
            $this->returnCode('75000013');
        }

        if ($type=='system') {
            $res = D('System_coupon')->partition_coupon_get_coupon($id);
        }else{
            $res = D('Card_new_coupon')->partition_coupon_get_coupon($id);
        }
        if (!$res['error_code']) {
            // 查询已瓜分列表
            $where_had = array();
            $where_had['h.status'] = 1;
            if ($coupon_hadpull['pid']) {
                $where_had['_string'] = '(h.id='.$coupon_hadpull['pid'].' OR h.pid ='.$coupon_hadpull['pid'].')';   
            }else{
                $where_had['_string'] = '(h.id='.$id.' OR h.pid ='.$id.')';  
            }
            $hadpull_list = $database_coupon_hadpull->field('h.*,u.avatar,u.nickname')->join(' as h left join '.C('DB_PREFIX').'user as u on u.uid=h.uid')->where($where_had)->select();

            $this->returnCode('0',array('msg'=>'领取成功','money'=>$res['discount_money'],'hadpull_list'=>$hadpull_list));
        }else{
            $this->returnCode('75000015');
        }
    }
}


