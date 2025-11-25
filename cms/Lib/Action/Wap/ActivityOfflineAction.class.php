<?php
/*
 * 线下活动
 *
 * @  BuildTime  2019/04/08 14:25
 * 
 */
class ActivityOfflineAction extends BaseAction{
    public function _initialize() {
        parent::_initialize();
    }

    public function check_login(){

        if(empty($this->user_session)){
            $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
            $redirect_url = U('Login/index',$location_param);
            redirect($redirect_url);
            // $this->assign('redirect_url',$redirect_url);
            // $this->display('my_login');
            die;
        }
    }

    //活动列表
    public function index(){
        //判断是否是微信浏览器，如果是读取微信提供的位置信息。
        if($_SESSION['openid']) $this->assign('user_long_lat',D('User_long_lat')->getLocation($_SESSION['openid']));
        $adver = D('Adver')->get_adver_by_key('wap_activity_list', 5);
        $this->assign('wap_activity_list',$adver);
        $this->assign('config',$this->config);
        $this->display();
    }

    /**ajax
     * [activity_list 活动列表]
     * @return [type] [description]
     */
    public function activity_list(){
        $activity_model = M('Activity_offline');
        $activity_where = array();
        $lat = $long =0;
        if($_POST['cate_id'] == 'recent'){
            $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
            if (!empty($long_lat['long']) && !empty($long_lat['lat'])) {
                import('@.ORG.longlat');
                $longlat_class = new longlat();
                $location2 = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);//转换腾讯坐标到百度坐标
                $lat = $location2['lat'];
                $long = $location2['lng'];
            }
        }else{
            if($_POST['title']){
                $activity_where['title'] = array('like','%'.$_POST['title'].'%');
            }
            if($_POST['province_id']){
                $activity_where['province_id'] = $_POST['province_id'];
            }

            if($_POST['cate_id']){
                $activity_where['fid'] = $_POST['cate_id'];
            }
        }
        if($_POST['city_id']){
            $activity_where['city_id'] = $_POST['city_id'];
        }
        $activity_where['status'] =1;//进行中
        $activity_where['examine_status'] =2;//已审核
        $activity_where['is_del'] =0;//未删除
        $activity_where['end_time'] =array('gt',time());//活动时间未结束
        $page = $_POST['page'];
        $pageSize=10;
        $total =$activity_model->where($activity_where)->count();
        // var_dump($activity_model->_sql());
        $page = isset($page) ? intval($page) : 1;
        $totalPage = ceil($total / $pageSize);
        $firstRow = $pageSize * ($page - 1);

        if($_POST['cate_id']=='recent' && $lat && $long){
            $field = '*,ROUND(6378.137 * 2 * ASIN(SQRT(POW(SIN(('.$lat.'*PI()/180-`latitude`*PI()/180)/2),2)+COS('.$lat.'*PI()/180)*COS(`latitude`*PI()/180)*POW(SIN(('.$long.'*PI()/180-`longitude`*PI()/180)/2),2)))*1000) AS juli';
            $order = array('juli'=>'asc');
        }else{
            $field = true;
            $order = 'is_top desc,update_time desc,add_time desc';
        }

        $list = $activity_model->field($field)->where($activity_where)->limit($firstRow.','.$pageSize)->order($order)->select();//查询后台开启的分类的活动
        foreach($list as $key =>$value){
            if ($value['image']) {
                $value['image'] = explode(';', $value['image']);
                foreach ($value['image'] as &$_img) {
                    $_img = file_domain() . '/upload/activity_offline/' . $_img;
                }
            }
            $list[$key]['image'] = $value['image'][0];
            $list[$key]['start_time'] = date('Y-m-d H:i', $value['start_time']);
            $list[$key]['end_time'] = date('Y-m-d H:i', $value['end_time']);
            $list[$key]['add_time'] = date('Y-m-d H:i', $value['add_time']);
            $list[$key]['position'] = array('lng' => $value['longitude'], 'lat' => $value['latitude']);
            $list[$key]['distjuli'] = isset($value['juli']) ? intval($value['juli']) : 0;
            $list[$key]['distance'] = ($value['longitude'] !=0 && $value['latitude']!=0) ? getRange($value['juli']) : '';
            if(time() < ($value['add_time']+259200) && $value['status'] == 1){
                $list[$key]['status'] = '4'; 
            }
        }
        
        $info_list = array(
            'total' => $total,
            'pageTotal' => $totalPage,
            'has_more' => $totalPage > $page ? true : false,
            'list' => $list,
        );
        
        $this->returnCode(0,$info_list);
        
    }

    //搜索页面
    public function searchIndex(){
        $this->display();
    }

    public function activity(){
        // 判断用户是否登录
        $this->check_login();
        $this->display();
    }

    //活动详情页面
    public function indexDetail(){
        $this->display();
    }

    // 团长查看详情
    public function activityDetail(){
        // 判断用户是否登录
        $this->check_login();
         //活动id
        if (empty($_GET['activity_id'])) {
            $this->error_tips('活动ID不存在');
        }

        //查询活动
        $activity = M('Activity_offline')->where(array('activity_id'=>$_GET['activity_id'],'is_del'=>0))->find();
        if ($activity['uid'] != $this->user_session['uid']) {
            $this->error_tips('非法访问');
        }
        $this->display();
    }

    // ajax获得活动详情
    public function activity_detail()
    {

        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_option = M('Activity_offline_option');
            $database_user = D('User');
            $database_activity_offline_join = M('Activity_offline_join');
            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();

            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) {
                $this->returnCode('73000012');
            }

            if ($activity['examine_status'] != 2 && $this->user_session['uid'] != $activity['uid']) {
                $this->returnCode('73000033');
            }

            //查询用户
            $user_info = D('User')->get_user($activity['uid']);
            $activity['avatar'] = $user_info['avatar'] ? $user_info['avatar'] : '';
            $activity['activity_nickname'] = $user_info['nickname'];

            $activity_offline__reply_model = M('Activity_offline_reply');
            $is_reply = $activity_offline__reply_model->where(array('activity_id'=>$_POST['activity_id'],'uid'=>$this->user_session['uid']))->field('id')->find();
            if($is_reply){
                $activity['is_already_reply'] = true;
            } else {
                $activity['is_already_reply'] = false;
            }

            $now_time = time();
            if (intval($activity['deadline']) > intval($now_time)) {
                $activity['is_sign_end'] = false;
            } else {
                $activity['is_sign_end'] = true;
            }
            if (intval($activity['end_time']) > intval($now_time)) {
                $activity['is_end'] = false;
            } else {
                $activity['is_end'] = true;
            }
            if($activity['status'] == 3){//手动结束活动
                $activity['is_end'] = true;
            }
            // 判断是否在活动开始和结束时间之内
            if($activity['start_time']< time() && time() < $activity['end_time']){
                $activity['can_signup'] = true;
            }else{
                $activity['can_signup'] = false;
            }
            $activity['start_time'] = date('Y-m-d H:i' , $activity['start_time']);
            $activity['end_time'] = date('Y-m-d H:i' , $activity['end_time']);
            $activity['deadline'] = date('Y-m-d H:i' , $activity['deadline']);
            $activity['add_time'] = date('Y-m-d H:i' , $activity['add_time']);
            $cate_info1= M('Activity_offline_cate')->field('id,fid,cate_name')->where(array('id'=>$activity['cate_id']))->find();//二级分类信息
            $cate_info2= M('Activity_offline_cate')->field('id,fid,cate_name')->where(array('id'=>$cate_info1['fid']))->find();//一级分类信息
            $activity['first_cate_name'] = $cate_info2['cate_name'];
            $activity['second_cate_name'] = $cate_info1['cate_name'];


            //图片存放的路径
            $data_time =date('Y-m-d',time());
            $path = "./upload/user/".$data_time."/";
            
            //确保图片名唯一，防止重名产生覆盖
            $uniName ='wx_'.$activity['uid'].'.jpg';
         
            $activity['avatar'] = $this->get_curl_img($user_info['avatar'],$path,$uniName);

            // 参与者
            $field = array('`u`.`avatar`','`u`.`nickname`', '`aj`.*' );
            $table = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'activity_offline_join'=>'aj');
            $where['_string'] = '`u`.`uid` = `aj`.`uid` AND `aj`.`status` in(1,2,5) AND `aj`.`activity_id`='.intval($_POST['activity_id']);
            $join_list = D('')->field($field)->table($table)->where($where)->order('`aj`.`add_time` DESC')->limit(6)->select();
            if ($join_list) {
                foreach ($join_list as &$value) {
                    $value['content'] = $value['content'] ? json_decode($value['content']) : $value['content'];
                    $value['add_time'] = time_info($value['add_time']);
                }
            }
            $activity['join_list'] = $join_list ? $join_list : [];

            //报名人数
            $activity['number'] = count($join_list);

            // 是否拼团成功
            if ( $activity['number'] >= $activity['open_number']) {
                // 已成团
                $activity['open_status'] = 1;
            }else{
                // 未成团
                $activity['open_status'] = 2;
                $activity['diff_num'] = $activity['open_number'] - $activity['number'];
            }
            // 尾款

            $activity['final_money'] = round($activity['fees_all'] - $activity['fees'],2);
            // 拼团剩余时间
            $activity['remaining_time'] = strtotime($activity['deadline']) - time();

            if ($activity['is_sign_end']== 1 && !$activity['is_open'] && $activity['open_status'] == 2) {
                // 拼团失败 报名已截止 未成团活动自动取消
                $activity['open_status'] = 3;
                $activity['diff_num'] = $activity['open_number'] - $activity['number'];
            }

            $activity['remaining_day'] = floor($activity['remaining_time']/86400);

            $remaining_time = $activity['remaining_time'] - $activity['remaining_day']*86400;
            $activity['remaining_hour'] = floor($remaining_time/3600);
            $remaining_time = $remaining_time - $activity['remaining_hour']*3600;
            $activity['remaining_min'] = floor($remaining_time/60);
            $remaining_time = $remaining_time - $activity['remaining_min']*60;
            $activity['remaining_sec'] = $remaining_time;

            // 自定义报名项
            $custom_list = $database_activity_offline_option->where(array('activity_id'=>$_POST['activity_id'],'status'=>1))->select();
            if ($custom_list) {
                foreach ($custom_list as &$value) {
                    $value['content'] = $value['content'] ? json_decode($value['content']) : $value['content'];
                }
            }
            $activity['custom_list'] = $custom_list ? $custom_list : [];

            //活动图片
            if ($activity['image']) {
                $activity['image'] = explode(';', $activity['image']);
                foreach ($activity['image'] as &$_img) {
                    $_img = file_domain() . '/upload/activity_offline/' . $_img;
                }
            }
            // var_dump($activity['image']);
            //活动excel 文件
            unset($activity['excel_url']);

            // 查询订单
            $activity['order'] = [];
            $activity['is_join'] = 0;
            // 是否已经报名
            if ($this->user_session['uid']) {
                $is_join = $database_activity_offline_join->where(array('activity_id'=>$_POST['activity_id'],'uid'=>$this->user_session['uid']))->find();
                if ($is_join) {
                    if ($is_join['status'] == 3 || $is_join['status'] == 2 || $is_join['status'] == 1 || $is_join['status'] == 4 || $is_join['status'] == 5 || $is_join['status'] == 6) { // 已报名成功
                        $activity['is_join'] = 1;
                    }
                    $activity['join_status'] = $is_join['status'];
                    $activity['join_id'] = $is_join['join_id'];
                    $activity['final_pay_status'] = $is_join['final_pay_status'];
                    // 查询订单
                    if ($is_join['order_id']>0) {
                        $activity['order'] = D('Activity_offline_order')->where(array('order_id'=>$is_join['order_id']))->find();
                        $activity['order']['add_time'] = date('Y-m-d H:i',$activity['order']['add_time']);
                    }
                }
            }

            // 是否创建人
            $activity['is_owner'] = ($activity['uid']==$this->user_session['uid']) ? 1 : 0;


            // 判断收费的余额是否足够
            $user_info = D('User')->get_user($this->user_session['uid']);
            $activity['now_money'] = $user_info['now_money'];

            if ($activity['fees'] > $activity['now_money']) {
                $activity['pay_money'] = $activity['fees'] - $user_info['now_money'];
            }else{
                $activity['pay_money'] = 0;
            }

            $activity_model = M('Activity_offline_reply');
            $reply = $activity_model->field('SUM(reply_star) as total_star')->where(array('activity_id'=>$_POST['activity_id'], 'is_del' => 0, 'fid' => 0))->find();
            $reply_count = $activity_model->where(array('activity_id'=>$_POST['activity_id'], 'is_del' => 0, 'fid' => 0))->count();
            $reply_star = $reply['total_star'] ? $reply['total_star'] : 0;
            if ($reply_star > 0 && $reply_star>0) {
                $reply_star = sprintf("%.1f",($reply_star/$reply_count));
            }
            $owner_info = M('Activity_offline_refund_examine')->where(array('uid'=>$this->user_session ['uid'],'activity_id'=>$_POST['activity_id'],'join_id'=>'is_owner'))->find();
            $activity['owner_info'] = $owner_info;
            $activity['reply_star'] = $reply_star;
            $this->returnCode(0, $activity);
        }
    }

    //活动分类api
    public function activity_cate(){
        $where['status'] = 0;
        if($_POST['id'] && !empty($_POST['id'])){
            $where['id'] = intval($_POST['id']);
        }

        $where['fid'] = intval($_POST['fid']);
        if($_POST['fid'] && !empty($_POST['fid'])){
            unset($where['id']);
            $where['fid'] = intval($_POST['fid']);
        }
        $list['activity_offline_cate'] = M('Activity_offline_cate')->field(true)->where($where)->order('sort desc')->select();
        if($list){
            $this->returnCode(0,$list);
        }else{
            $this->returnCode(1,array(),'没有查询到数据!');
        }
    }


    //创建活动页面
    public function activityAdd(){
        // 判断用户是否登录
        $this->check_login();
        $this->display();
    }

    // 创建编辑群活动
    public function set_activity()
    {
        // 判断用户是否登录
        $this->check_login();

        $img_mer_id = sprintf("%09d", $this->user_session['uid']);
        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $up_dir = './upload/activity_offline/'.$rand_num.'/';

        if(!file_exists($up_dir)){
            $q = mkdir($up_dir,0777,true);
        }

        $base64_img = $_POST['fileImg'];
        $base64_img = explode('||',$base64_img);
        $img_path = array();
        foreach ($base64_img as $_base64_img) {
            if ($_base64_img) {
                if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $_base64_img, $result)){
                    $type = $result[2];
                    if(in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png'))){
                        $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
                        if(file_put_contents($new_file, base64_decode(str_replace($result[1], '', $_base64_img)))){
                            $img_path[] = str_replace('./upload/activity_offline/', '', $new_file);
                        }else{
                            $this->returnCode(73000048);
                        }
                    }else{
                        //文件类型错误
                        $this->returnCode(73000049);
                    }
                }
            }
            
        }

        $data = array();
        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_option = M('Activity_offline_option');
            //活动id
            $activity_id = $_POST['activity_id'];

            // 活动主题
            if (empty($_POST['title'])) {
                $this->returnCode('71000023');
            }

            $data['title'] = $_POST['title'];
            $data['cate_id'] = $_POST['cate_id'];
            $data['fid'] = $_POST['fid'];
            $data['latitude'] = $_POST['lat'];
            $data['longitude'] = $_POST['long'];
            $data['address_name'] = $_POST['new_address'];

            // 开始时间
            if (empty($_POST['start_time'])) {
                $this->returnCode('71000024');
            }
            $data['start_time'] = strtotime($_POST['start_time']);

            // 结束时间
            if (empty($_POST['end_time'])) {
                $this->returnCode('71000025');
            }
            $data['end_time'] = strtotime($_POST['end_time']);

            // 活动地址
            $data['address'] = $_POST['address'] ? $_POST['address'] : '';
            $data['provincename'] = $_POST['provincename'] ? $_POST['provincename'] : '';
            $data['cityname'] = $_POST['cityname'] ? $_POST['cityname'] : '';

            // 活动详情
            if (empty($_POST['desc'])) {
                $this->returnCode('71000027');
            }
            $data['desc'] = $_POST['desc'];

            //费用设置
            $data['fees_name'] = $_POST['fees_name'] ? $_POST['fees_name'] : ''; // 费用名称
            $data['fees'] = $_POST['fees'] ? $_POST['fees'] : ''; // 费用金额
            $data['limit'] = $_POST['limit'] ? $_POST['limit'] : 0; // 名额限制
            $option = json_decode(htmlspecialchars_decode($_POST['custom']), true);
            // 活动图片
            $data['image'] = $img_path ? implode(';',$img_path) : '';
            // 咨询电话
            $data['phone'] = $_POST['phone'] ? $_POST['phone'] : 0;
            //省份ID
            $data['province_id'] = $_POST['province_id'] ? $_POST['province_id'] : 0;
            //城市ID
            $data['city_id'] = $_POST['city_id'] ? $_POST['city_id'] : 0;
            //总费用
            $data['fees_all'] = $_POST['fees_all'] ? $_POST['fees_all'] : 0;
            //开团人数
            $data['open_number'] = intval($_POST['open_number']);

            if ($data['limit']<$data['open_number']) {
                $this->returnCode('71000073');
            }     

            if ($data['fees_all']<$data['fees']) {
                $this->returnCode('71000074');
            }

            // 报名截止时间
            $data['deadline'] = $_POST['deadline'] ? strtotime($_POST['deadline']) : strtotime($_POST['end_time']); //活动结束之前均可报名

            $data['uid'] = $this->user_session['uid'];
            D()->startTrans();

            if ($activity_id) {  // 验证是否可以编辑
                //查询活动
                $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id']))->find();
                if (!$activity) {
                    $this->returnCode('73000011');
                }
                if ($activity['examine_status'] == 2) { // 已通过
                    $this->returnCode('73000041');
                }
                if ($activity['uid'] != $this->user_session['uid']) {
                    $this->returnCode('71000055');
                }
                if ($activity['is_del'] == 1) { // 已删除
                    $this->returnCode('73000012');
                }
                if ($activity['status'] == 2) { // 已取消
                    $this->returnCode('73000013');
                }
                if ($activity['status'] == 3) { // 已结束
                    $this->returnCode('73000014');
                }

                //免费活动修改,改变支付状态
                if($activity['fees'] <=0 && $activity['pay_status'] == 2){
                   $status = $database_activity_offline->where(array('activity_id'=>$activity_id))->data(array('pay_status'=>1))->save();
                }
                if($activity['fees'] >0 && $activity['pay_status'] == 2){
                    // 如果活动已支付则不给修改费用
                    unset($data['fees']);
                    $this->returnCode('71000050');
                }

                if (empty($data['image'])) {
                    unset($data['image']);
                }

                $res = $database_activity_offline->where(array('activity_id'=>$activity_id))->data($data)->save();
                if ($res !== false) {
                    //查询报名设置
                    $option_detail = $database_activity_offline_option->where(array('activity_id'=>$activity_id,'status'=>1))->select();
                    
                    // 已有自定义名称
                    $old_title = array();

                    // 新的自定义项名称
                    $new_title = array();

                    $option_new = array();
                    if ($option) {
                        foreach ($option as $key => $value) {
                            $new_title[] = $value['title'];
                            $option_new[$value['title']] = array(
                                'title' => $value['title'], //选项名称
                                'type' => $value['type'], //类型1-单行文本，2-多行文本，3-单选，4-多选
                                'is_required' => $value['is_required'], //是否必填0-不必填，1-必填
                                'content' => $value['content'] ? json_encode($value['content']) : '',
                            );
                        }
                    }
                    if ($option_detail) {
                        //删除 编辑选项
                        foreach ($option_detail as $key => $value) {
                            $old_title[] = $value['title'];
                            if (in_array($value['title'], $new_title)) { // 编辑选项
                                $arr = $option_new[$value['title']];
                                $arr['add_time'] = time();
                                $opt = $database_activity_offline_option->where(array('option_id'=>$value['option_id'],'activity_id'=>$activity_id))->data($arr)->save();
                            } else { // 删除选项
                                $opt = $database_activity_offline_option->where(array('option_id'=>$value['option_id'],'activity_id'=>$activity_id))->data(array('status'=>0))->save();
                            }
                            if (!$opt) {
                                D()->rollback();
                                $this->returnCode(1,array(),'编辑失败！请重试');
                            }
                        }
                    }

                    // 新增选项
                    if ($option) {
                        foreach ($option as $key => $value) {
                            if (!in_array($value['title'], $old_title)) {
                                $option_data = array(
                                    'title' => $value['title'], //选项名称
                                    'type' => $value['type'], //类型1-单行文本，2-多行文本，3-单选，4-多选
                                    'is_required' => $value['is_required'], //是否必填0-不必填，1-必填
                                    'activity_id' => $activity_id,
                                    'content' => $value['content'] ? json_encode($value['content']) : '',
                                    'uid' => $this->user_session['uid'],
                                    'add_time' => time(),
                                );
                                $add = $database_activity_offline_option->data($option_data)->add();
                                if (!$add) {
                                    D()->rollback();
                                    $this->returnCode(1,array(),'编辑失败！请重试');
                                }
                            }
                        }
                    }
                    D()->commit();
                    $user_infoss = M('User')->field(true)->where(array('uid'=>$this->user_session['uid']))->find();

                    //活动修改成功时
                    $order_data = array(
                        'activity_id'   =>  $activity_id,
                        'uid'           =>  $this->user_session['uid'],
                        'money'         =>  $data['fees'],
                        'pay_type'      =>  $_POST['type'] ? $_POST['type'] : '',
                        'status'        =>  2,
                        'add_time'      =>  time()
                    );
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    // 活动详情页面路径
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=indexDetail&activity_id=' . $activity_id;

                    $user_info = M('User')->field(true)->where(array('uid'=>$this->user_session['uid']))->find();

                    if ($user_info['openid']) {
                        $model->sendTempMsg('TM00017', array(
                            'href' => $href,
                            'wecha_id' => $user_infoss['openid'],
                            'first' => '您已编辑活动【'.$activity['title'].'】',
                            'OrderSn' => $activity_id,
                            'OrderStatus' => '活动编辑成功,进入审核',
                            'remark' => date('Y-m-d H:i:s')
                        ));//活动创建成功模板推送
                    }
                    $this->returnCode(0,array('activity_id'=>$activity_id));
                } else {
                    D()->rollback();
                    $this->returnCode(1,array(),'编辑失败！请重试');
                }

            }else{
                // 新增
                // 添加时间
                $data['add_time'] = time();
                $data['enroll_status'] = 1; //报名状态:1-进行中，2-已结束
                $data['status'] = 1; //进行中
                if($data['fees']>0){
                    $data['pay_status'] = 1; //待付款
                }else{
                    $data['pay_status'] = 2; //已付款
                }
                $activity_id = $database_activity_offline->data($data)->add();
                $activity_infoss = M('Activity_offline')->alias('a')->field('a.*,b.nickname')->where(array('a.activity_id'=>$activity_id))->join('join pigcms_user as b on a.uid=b.uid')->find();
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                if($activity_id){
                    //添加选项
                    $option_data = [];
                    if ($option) {
                        foreach ($option as $key => $value) {
                            $option_data[$key]['activity_id'] = $activity_id;
                            $option_data[$key]['uid'] = $this->user_session['uid'];
                            $option_data[$key]['title'] = $value['title'];
                            $option_data[$key]['is_required'] = $value['is_required']; // 是否必填0-不必填，1-必填
                            $option_data[$key]['type'] =  $value['type']; // 默认单行文本（类型1-单行文本，2-多行文本，3-单选，4-多选）
                            $option_data[$key]['content'] = $value['content'] ? json_encode($value['content']) : '';
                            $option_data[$key]['add_time'] = time();
                        }
                        $option_data = array_values($option_data);

                        $addOption = $database_activity_offline_option->addAll($option_data);
                        if (!$addOption) {
                            D()->rollback();
                            $this->returnCode(1,array(),'发布失败！请重试');
                        }
                        D()->commit();
                        //活动创建成功模板推送
                        // 活动详情页面路径
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=indexDetail&activity_id=' . $activity_id;
                        $user_info = M('User')->field(true)->where(array('uid'=>$data['uid']))->find();
                        if ($user_info['openid']) {
                            $model->sendTempMsg('TM00017', array(
                                'href' => $href,
                                'wecha_id' => $user_info['openid'],
                                'first' => '您已发布活动【'.$activity_infoss['title'].'】',
                                'OrderSn' => $activity_infoss['order_id'],
                                'OrderStatus' => '活动发布成功,进入审核',
                                'remark' => date('Y-m-d H:i:s')
                            ));
                        }
                        $this->returnCode(0,array('url'=>U('ActivityOffline/indexDetail', array('activity_id' => $activity_id))));
                    }
                }else{
                    D()->rollback();
                    $this->returnCode(1,array(),'发布失败！请重试');
                }
            }
        }
    }


    //加入活动 活动报名
    public function join_activity(){
        // 判断用户是否登录
        $this->check_login();

        $uid = $this->user_session['uid'];
        $data = array();
        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_join = M('Activity_offline_join');
            $database_activity_offline_order= M('Activity_offline_order');
            $database_activity_offline_option = M('Activity_offline_option');
            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }
            $data['activity_id'] = $_POST['activity_id'];

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id']))->find();
            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) { // 已删除
                $this->returnCode('73000012');
            }
            if ($activity['status'] == 2) { // 已取消
                $this->returnCode('73000013');
            }
            if ($activity['status'] == 3 || $activity['end_time'] < time()) { // 已结束
                $this->returnCode('73000014');
            }


            // 验证报名是否截止
            if ($activity['enroll_status'] != 1 || $activity['deadline'] < time()) {
                $this->returnCode('71000051'); //报名已结束
            }

            // 验证报名人数是否已满
            $join_number = $database_activity_offline_join->where(array('activity_id'=>$_POST['activity_id'],'status'=>array('in',array(1,2,5))))->count();
            if ($activity['limit'] > 0 && $join_number>=$activity['limit']) {
                $this->returnCode('71000052'); //报名人数已满
            }

            //验证是否已报名
            $activity_where['activity_id'] = $data['activity_id'];
            $activity_where['uid'] = $uid;
            $is_jioned = $database_activity_offline_join->where($activity_where)->find();
            if ($is_jioned&&$is_jioned['status']==1) { // 已经报名成功
                $this->returnCode('71000047');
            } elseif ($is_jioned&&$is_jioned['status']==3) { // 已报名 未付款
                $this->returnCode('71000048');
            } elseif ($is_jioned&&$is_jioned['status']==2) { // 已报名 已完成
                $this->returnCode('71000047');
            } elseif($is_jioned&&$is_jioned['status']==4){//已报名 已退款
                $this->returnCode('73000044');
            } elseif($is_jioned&&$is_jioned['status']==5){//已报名 已签到
                $this->returnCode('73000052');
            }elseif($is_jioned&&$is_jioned['status']==6){//已报名 已取消
                $this->returnCode('73000053');
            }

            //姓名
            if (empty($_POST['name'])) {
                $this->returnCode('71000045');
            }
            $data['name'] = $_POST['name'];

            //手机号
            if (empty($_POST['phone'])) {
                $this->returnCode('71000046');
            }
            $data['phone'] = $_POST['phone'];
            $data['uid'] = $uid;
            $data['money'] = $activity['fees'];

            //查询自定义报名项
            $activity_option = $database_activity_offline_option->where(array('activity_id'=>$activity['activity_id'],'status'=>1))->select();
            if ($activity_option) {
                $option =  $_POST['custom'];
                foreach ($activity_option as $key => $value) {
                    if($value['title']=='姓名' || $value['title']=='手机'){
                          unset($activity_option[$key]);
                    }else{
                          if ($value['is_required'] == 1 && !$option[$value['title']]) {
                              exit(json_encode(array ('errorCode' => 1,'errorMsg' => $value['title'].'为必填项',)));
                          }
                    }

                }
                $data['content'] = json_encode($option);
            }

            $data['add_time'] = time();
            if ($activity['fees']>0) {
                if(!empty($activity['fees']) && $activity['fees']>0){
                    $order_data = array(
                        'activity_id'   =>  $_POST['activity_id'],
                        'uid'           =>  $this->user_session['uid'],
                        'money'         =>  $activity['fees'],
                        'pay_type'      =>  '',
                        'status'        =>  2,
                        'add_time'      =>  time()
                    );
                    $order_id = M('Activity_offline_order')->data($order_data)->add();
                    if (!$order_id) {
                        // 订单生成失败
                        $this->returnCode('20046014');
                    }

                    // 修改创建报名信息
                    $data['status'] = 3; // 待付款
                    $data['order_id'] = $order_id;
                    if ($is_jioned) {
                        $join_id = $is_jioned['join_id'];
                        $addInfo = $database_activity_offline_join->where(array('activity_id'=>$data['activity_id'],'uid'=>$uid))->data($data)->save();
                    }else{
                        $addInfo = $database_activity_offline_join->data($data)->add();
                        $join_id = $addInfo;
                    }
                    if (!$addInfo) {
                        $this->returnCode('73000035');
                    }
                    
                    // 支付订单
                    $pay_order_param = array(
                        'business_type' => 'activity_offline',
                        'business_id' => $res,
                        'order_name' => '线下活动',
                        'uid' => $this->user_session['uid'],
                        'total_money' => $activity['fees'],
                    );
                    $plat_order_result = D('Plat_order')->add_order($pay_order_param);
                    if ($plat_order_result['error_code']) {
                        $this->returnCode('1','',$plat_order_result['error_msg']);
                    } else {
                        $this->returnCode(0,array('url'=>U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat'))));
                    }
                   
                }
            } else {
                if ($is_jioned) {
                    $join_id = $is_jioned['join_id'];
                    $addInfo = $database_activity_offline_join->where(array('activity_id'=>$data['activity_id'],'uid'=>$uid))->data($data)->save();
                }else{
                    $addInfo = $database_activity_offline_join->data($data)->add();
                    $join_id = $addInfo;
                }
                if ($addInfo) {
                    // 报名人数加1
                    M('Activity_offline')->where(array('activity_id'=>$now_order['activity_id']))->setInc('number',1);

                    //活动报名成功模板推送
                    $user_info = M('User')->field(true)->where(array('uid'=>$uid))->find();
                    $owner_user_info = M('User')->field(true)->where(array('uid'=>$activity_info['uid']))->find();
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    if ($user_info['openid']) {
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=indexDetail&activity_id=' . $data['activity_id'];
                        $model->sendTempMsg('TM00017', array(
                            'href' => $href,
                            'wecha_id' => $user_info['openid'],
                            'first' => '您已报名活动【'.$activity['title'].'】',
                            'OrderSn' => $activity['activity_id'],
                            'OrderStatus' => '报名成功',
                            'remark' => date('Y-m-d H:i:s')
                        ));
                    }
                    if ($owner_user_info['openid']) {
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=activityParticPation&activity_id=' . $data['activity_id'];
                        $model->sendTempMsg('TM00017', array(
                            'href' => $href,
                            'wecha_id' => $owner_user_info['openid'],
                            'first' => $user_info['nickname'].'已报名活动【'.$activity['title'].'】',
                            'OrderSn' => $activity['activity_id'],
                            'OrderStatus' => '报名成功',
                            'remark' => date('Y-m-d H:i:s')
                        ));//活动报名成功模板推送
                    }
                    $this->returnCode(0,array('url'=>U('ActivityOffline/indexDetail', array('activity_id' => $data['activity_id']))));
                }else{
                    $this->returnCode(1, array(), '报名失败，请重试');
                }
            }
        }
    }

    /**
     * [activity_cate_api 活动分类]
     * @return [type] [description]
     */
    public function activity_cate_api(){
        $where['fid'] = 0;
        $where['status'] = 0;
        if($_GET['city_id']){
            $where['city_id'] = $_GET['city_id'];
        }

        $list = M('Activity_offline_cate')->where($where)->order('sort desc')->select();

        $array = array(
            'id'=>'recent',
            'cate_name'=>'附近活动'
        );
        array_unshift($list,$array);
        $list = $list ? $list : array();
        $this->returnCode(0,$list);
    }

    // 报名列表
    public function activityParticPation(){
        // 判断用户是否登录
        $this->check_login();

        $info['uid'] = $this->user_session['uid'];
        if (!empty($_GET)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_GET['activity_id'],'is_del'=>0))->find();

            $activity['is_owner'] = 0;
            //验证是否是创建者
            if ($activity['uid'] == $info['uid']) {
                $activity['is_owner'] = 1;
            }

            $where_all['_string'] = '`aj`.`activity_id`='.$_GET['activity_id'];
            $where1['_string'] = '`aj`.`activity_id`='.$_GET['activity_id'];
            $where2['_string'] = '`aj`.`activity_id`='.$_GET['activity_id'];
            $where4['_string'] = '`aj`.`activity_id`='.$_GET['activity_id'];
            $where5['_string'] = '`aj`.`activity_id`='.$_GET['activity_id'];
            $where6['_string'] = '`aj`.`activity_id`='.$_GET['activity_id'];
            $status_all = ' AND `aj`.`status` in (1,2,4,5,6)';
            $status1 = ' AND `aj`.`status` = 1';
            $status2 = ' AND `aj`.`status` = 2';
            $status4 = ' AND `aj`.`status` = 4';
            $status5 = ' AND `aj`.`status` = 5';
            $status6 = ' AND `aj`.`status` = 6';
            $where_all['_string'] .= $status_all;
            $where1['_string'] .= $status1;
            $where2['_string'] .= $status2;
            $where4['_string'] .= $status4;
            $where5['_string'] .= $status5;
            $where6['_string'] .= $status6;
            //判断是否为免费活动
            if($activity['fees']>0){
                $table = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'activity_join'=>'aj',C('DB_PREFIX').'activity_order'=>'ao');
                $where_all['_string'] .= ' AND `u`.`uid` = `aj`.`uid` AND `ao`.`order_id` = `aj`.`order_id`';
                $where1['_string'] .= ' AND `u`.`uid` = `aj`.`uid` AND `ao`.`order_id` = `aj`.`order_id`';
                $where2['_string'] .= ' AND `u`.`uid` = `aj`.`uid` AND `ao`.`order_id` = `aj`.`order_id`';
                $where4['_string'] .= ' AND `u`.`uid` = `aj`.`uid` AND `ao`.`order_id` = `aj`.`order_id`';
                $where5['_string'] .= ' AND `u`.`uid` = `aj`.`uid` AND `ao`.`order_id` = `aj`.`order_id`';
                $where6['_string'] .= ' AND `u`.`uid` = `aj`.`uid` AND `ao`.`order_id` = `aj`.`order_id`';
            }else{
                $table = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'activity_join'=>'aj');
                $where_all['_string'] .= ' AND `u`.`uid` = `aj`.`uid`';
                $where1['_string'] .= ' AND `u`.`uid` = `aj`.`uid`';
                $where2['_string'] .= ' AND `u`.`uid` = `aj`.`uid`';
                $where4['_string'] .= ' AND `u`.`uid` = `aj`.`uid`';
                $where5['_string'] .= ' AND `u`.`uid` = `aj`.`uid`';
                $where6['_string'] .= ' AND `u`.`uid` = `aj`.`uid`';
            }
            $activity_count['all'] = D()->table($table)->where($where_all)->count();
            $activity_count['1'] = D()->table($table)->where($where1)->count();
            $activity_count['2'] = D()->table($table)->where($where2)->count();
            $activity_count['4'] = D()->table($table)->where($where4)->count();
            $activity_count['5'] = D()->table($table)->where($where5)->count();
            $activity_count['6'] = D()->table($table)->where($where6)->count();
            $this->assign('activity_count',$activity_count);
            $this->display();
        }
    }
    public function activityInspect(){
        $where = array(
            'uid' => $this->user_session['uid']
        );
        $list = M('Classify_manager')->field(true)->where($where)->find();
        if ($list && $list['status']==1) {
            $this->display();
        } else {
            if (empty($list)) {
                redirect(U('My/activity_apply'));
            } else {
                redirect(U('My/activity_owner', array('id' => $list['id'])));
            }
        }
    }

    // 用户查看自己报名详情
    public function activityOrder(){
        // 判断用户是否登录
        $this->check_login();

        $this->display();
    }
    public function activityComment(){
        $this->display();
    }
    public function activityCommentList(){
        $this->display();
    }
    public function replyCommentList(){
        $this->display();
    }


    public function activityRefund(){
        $this->display();
    }

    // 获得活动列表 我创建的
    public function my_activity_list()
    {
        // 判断用户是否登录
        if ($this->user_session['uid']) {
           $info['uid'] = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_join = M('Activity_offline_join');


            $where = array();
            $where['uid'] = $info['uid'];
            $where['is_del'] = 0;
            $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
            $pageSize=10;
            $activity_count = $database_activity_offline->where($where)->count();
            $totalPage = ceil($activity_count / $pageSize);
            $firstRow = $pageSize * ($page - 1);
            $limit = $firstRow.','.$pageSize;

            $activity_list = $database_activity_offline->where($where)->order('`add_time` DESC')->limit($limit)->select();
            if ($activity_list) {
                $now_time = time();
                foreach ($activity_list as &$value) {
                    $value['start_time_format'] = time_info($value['start_time'], '', true);
                    $value['end_time_format'] = date('Y-m-d H:i' , $value['end_time']);
                    $value['deadline_format'] = date('Y-m-d H:i' , $value['deadline']);
                    $value['add_time_format'] = date('Y-m-d H:i' , $value['add_time']);
                    if (intval($value['deadline']) > intval($now_time)) {
                        $value['is_sign_end'] = false;
                    } else {
                        $value['is_sign_end'] = true;
                    }
                    if (intval($value['end_time']) > intval($now_time)) {
                        $value['is_end'] = false;
                    } else {
                        $value['is_end'] = true;
                    }

                    //是否最新发布
                    $value['is_new'] = 0;
                    if (time() - $value['add_time'] < 259200) { //最新发布 三天内
                        $value['is_new'] = 1;
                    }

                    //是否进行中
                    $value['is_going'] = 0;
                    if ($value['start_time'] <= time()) {
                        $value['is_going'] = 1;
                    }

                    // 已报名人数
                    $join_number = $database_activity_offline_join->where(array('activity_id'=>$value['activity_id'],'status'=>array('in',array(1,2,5))))->count();
                    $value['number'] = $join_number ? $join_number : 0;
                    //活动图片 
                    if ($value['image']) {
                        $value['image'] = explode(';', $value['image']);
                        foreach ($value['image'] as &$_img) {
                            $_img = file_domain() . '/upload/activity_offline/' . $_img;
                        }
                    }
                    $value['image'] = $value['image'][0];
                    //活动excel 文件
                    unset($value['excel_url']);
                }
            } else {
                $activity_list = [];
            }

            // //查询昵称
            // $user = $database_user->field('avatar,nickname')->where('uid='.$activity['uid'])->find();
            // $activity['avatar'] = $user['avatar'] ? $user['avatar'] : '';
            // $activity['nickname'] = $user['nickname'] ? $user['nickname'] : '';

            $activity_info = array(
                'total' => $activity_count,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $activity_list
            );
            $this->returnCode(0, $activity_info);
    }


    // 获得活动列表 我参与的
    public function join_activity_list()
    {
        // 判断用户是否登录
        if ($this->user_session['uid']) {
            $uid = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }

        // 处理添加的数据
        $database_user = D('User');

        //报名表查询参与的活动
        $field = array('`a`.*', '`aj`.`add_time` as join_time', '`aj`.`join_id`','`aj`.`uid` as join_uid', '`aj`.`status` as join_status');
        $table = array(C('DB_PREFIX').'activity_offline'=>'a',C('DB_PREFIX').'activity_offline_join'=>'aj');
        $where['_string'] = '`a`.`activity_id` = `aj`.`activity_id` AND `a`.`is_del`=0 AND `aj`.`uid`='.$uid.' AND `aj`.`status` in (1,2,3,5,6)'.'AND `a`.`examine_status`=2';
        $pageSize = 10;
        $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
        $activity_count = D('')->table($table)->where($where)->order('`aj`.`add_time` DESC,`a`.`status` ASC,`a`.`add_time` DESC')->count();
        $totalPage = ceil($activity_count / $pageSize);
        $firstRow = $pageSize * ($page - 1);
        $limit = $firstRow.','.$pageSize;

        $activity_list = D('')->field($field)->table($table)->where($where)->order('`aj`.`add_time` DESC,`a`.`status` ASC,`a`.`add_time` DESC')->limit($limit)->select();

        if ($activity_list) {
            $join_status_arr = array(0 => '报名失败', 1 => '报名成功', 2 => '已完成', 3 => '待付款',4 => '已退款',5 => '已参加',5 => '已取消');
            $activity_reply_model = M('Activity_offline_reply');
            foreach ($activity_list as &$value) {
                $user = $database_user->where(array('uid' => $value['join_uid']))->find();
                if ($user) {
                    $value['avatar'] = $user['avatar'];
                }
                $value['start_time_format'] = date('Y-m-d H:i' , $value['start_time']);
                $value['end_time_format'] = date('Y-m-d H:i' , $value['end_time']);
                $value['deadline_format'] = date('Y-m-d H:i' , $value['deadline']);
                $value['add_time_format'] = date('Y-m-d H:i' , $value['add_time']);
                $value['join_status_name'] = $join_status_arr[$value['join_status']];
                // 判断是否创建者
                $value['is_owner']  = ($value['uid'] == $uid) ? 1 : 0;
                //活动图片
                if ($value['image']) {
                    $value['image'] = explode(';', $value['image']);
                    foreach ($value['image'] as &$_img) {
                        $_img = file_domain() . '/upload/activity_offline/' . $_img;
                    }
                }
                $value['image'] =  $value['image'][0];
                //活动excel 文件
                unset($value['excel_url']);
                // 是否已经评论
                $is_reply = $activity_reply_model->where(array('activity_id'=>$value['activity_id'],'uid'=>$uid))->field('id')->find();
                if($is_reply){
                    $value['is_already_reply'] = true;
                } else {
                    $value['is_already_reply'] = false;
                }
                if($value['status'] == 2){//手动取消活动
                    $value['join_status'] = 6;
                }
            }
        } else {
            $activity_list = [];
        }

        $activity_info = array(
            'total' => $activity_count,
            'pageTotal' => $totalPage,
            'has_more' => $totalPage > $page ? true : false,
            'list' => $activity_list
        );

        $this->returnCode(0, $activity_info);
    }

    //全部活动列表
    public function activity_all()
    {
        if ($this->user_session['uid']) {
            $info['uid'] = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }

            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_join = M('Activity_offline_join');
            
            $where = array();
            $where['is_del'] = 0;
            $where['status'] = array('neq',2);
            $where['examine_status'] =2;

            $activity_count = $database_activity_offline->where($where)->count();

            $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
            $pageSize = 10;
            $totalPage = ceil($activity_count / $pageSize);
            $firstRow = $pageSize * ($page - 1);
            $limit = $firstRow.','.$pageSize;

            $activity_list = $database_activity_offline->where($where)->order('is_top desc,update_time desc,status ASC,add_time DESC')->limit($limit)->select();
            $now_time = time();
            if ($activity_list) {
                foreach ($activity_list as &$value) {
                    $value['start_time_format'] = time_info($value['start_time'], '', true);
                    $value['end_time_format'] = date('Y-m-d H:i' , $value['end_time']);
                    $value['deadline_format'] = date('Y-m-d H:i' , $value['deadline']);
                    $value['add_time_format'] = date('Y-m-d H:i' , $value['add_time']);
                    if (intval($value['deadline']) > intval($now_time)) {
                        $value['is_sign_end'] = false;
                    } else {
                        $value['is_sign_end'] = true;
                    }
                    if (intval($value['end_time']) > intval($now_time)) {
                        $value['is_end'] = false;
                    } else {
                        $value['is_end'] = true;
                    }

                    //是否最新发布
                    $value['is_new'] = 0;
                    if (time() - $value['add_time'] < 259200) { //最新发布 三天内
                        $value['is_new'] = 1;
                    }

                    //是否进行中
                    $value['is_going'] = 0;
                    if ($value['start_time'] <= time()) {
                        $value['is_going'] = 1;
                    }

                    // 已报名人数
                    $join_number = $database_activity_offline_join->where(array('activity_id'=>$value['activity_id'],'status'=>array('in',array(1,2,5))))->count();
                    $value['number'] = $join_number ? $join_number : 0;
                    //活动图片
                    $value['image'] = file_domain() . '/upload/activity/' . $value['image'];
                    //活动excel 文件
                    unset($value['excel_url']);

                    // 判断是否创建者
                    $value['is_owner']  = ($value['uid'] == $info['uid']) ? 1 : 0;


                    // 参与信息
                    $join_info = $database_activity_offline_join->where(array('activity_id'=>$value['activity_id'],'uid'=>$info['uid'],'status'=>array('in',array(1,2,4,5))))->find();
                    $value['join_id'] = $join_info ? $join_info['join_id'] : 0;
                    $value['join_status'] = $join_info ? $join_info['status'] : 0;
                }
            } else {
                $activity_list = [];
            }

            // //查询昵称
            // $user = $database_user->field('avatar,nickname')->where('uid='.$activity['uid'])->find();
            // $activity['avatar'] = $user['avatar'] ? $user['avatar'] : '';
            // $activity['nickname'] = $user['nickname'] ? $user['nickname'] : '';
           
            $activity_info = array(
                'total' => $activity_count,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $activity_list
            );
            $this->returnCode(0, $activity_info);
    }

    // 取消活动
    public function cancle_activity()
    {
        // 判断用户是否登录
        if (!$this->user_session['uid']) {
            $this->returnCode('73000008');
        }
        $uid = $this->user_session['uid'];

        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_join= M('Activity_offline_join');
            $database_activity_offline_order= M('Activity_offline_order');

            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();


            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) { // 已删除
                $this->returnCode('73000012');
            }
            if ($activity['status'] == 2) { // 已取消
                $this->returnCode('73000013');
            }
            if ($activity['status'] == 3) { // 已结束
            }

            if(time() >= $activity['start_time']){
                // 活动已开始不能取消
                $this->returnCode('71000077');
            }

            //验证是否是创建者
            if ($uid != $activity['uid']) {
                $this->returnCode('71000035');
            }

            //改变状态
            $res = $database_activity_offline->where('activity_id='.$_POST['activity_id'])->data(array('status'=>2))->save();

            if ($res) {
                //退还报名费
                if ($activity['fees'] >0 || $activity['fees_all'] >0) {
                    // 查询有效订单
                    $where['a.activity_id'] = $activity['activity_id'];
                    $where['a.is_del'] = 0;
                    $where['a.status'] = 1;
                    $where['b.status'] = array('in', array(1,5));
                    $order_list = $database_activity_offline_order
                    ->alias('a')
                    ->field('a.*,b.join_id')
                    ->distinct(true)
                    ->where($where)
                    ->join('join '.C('DB_PREFIX').'activity_offline_join as b on a.activity_id=b.activity_id')
                    ->select();
                    if ($order_list) {
                        foreach ($order_list as $key => $value) {
                            $return = $database_activity_offline_order->refund($value,$activity);
                        }
                    }
                    $this->returnCode(0,'取消成功');
                } else {
                    // 免费活动也对用户进行通知
                    $activity_join = M('Activity_offline_join')
                        ->alias('a')
                        ->join('join '.C('DB_PREFIX').'user as b on a.uid=b.uid')
                        ->field('a.join_id, a.uid, a.activity_id, b.openid')
                        ->distinct(true)
                        ->where(array('a.activity_id' => $activity['activity_id'], 'a.status' => array('in', array(1,5))))->select();

                    if ($activity_join) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        foreach ($activity_join as $val) {
                            // 处理成已取消状态
                            if ($val['join_id']) {
                                $database_activity_offline_join->where(array('join_id'=>$val['join_id']))->data(array('status'=>6))->save();
                            }

                            //活动退款模板推送
                            if ($val['openid']) {
                                $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=activityOrder&join_id='.$val['join_id'].'&activity_id=' . $activity['activity_id'];
                                $model->sendTempMsg('TM00017', array(
                                    'href' => $href,
                                    'wecha_id' => $val['openid'],
                                    'first' => '活动【'.$activity['title'].'】已经取消',
                                    'OrderSn' => $val['activity_id'],
                                    'OrderStatus' => '已取消',
                                    'remark' => date('Y-m-d H:i:s')
                                ));
                            }
                        }
                    }
                }
                $this->returnCode(0,'取消成功');
            } else {
                $this->returnCode(1,array(),'取消失败，请重试');
            }
        }
    }

    // 关闭报名
    public function close_enroll()
    {
        // 判断用户是否登录
        if ($this->user_session['uid']) {
            $info['uid'] = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }

        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');

            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();
            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) { // 已删除
                $this->returnCode('73000012');
            }
            if ($activity['status'] == 2) { // 已取消
                $this->returnCode('73000013');
            }
            if ($activity['status'] == 3 || $activity['end_time'] < time()) { // 已结束
                $this->returnCode('73000014');
            }

            if ($activity['enroll_status'] == 2) { // 已关闭
                $this->returnCode('71000038');
            }
            

            //验证是否是创建者
            if ($info['uid'] != $activity['uid']) {
                $this->returnCode('71000036');
            }

            //改变状态
            $res = $database_activity_offline->where('activity_id='.$_POST['activity_id'])->data(array('enroll_status'=>2))->save();
            if ($res) {
                $this->returnCode(0,'关闭成功');
            } else {
                $this->returnCode(1,array(),'关闭失败，请重试');
            }
        }
    }

    // 开启报名
    public function open_enroll(){
        // 判断用户是否登录
        if ($this->user_session['uid']) {
            $info['uid'] = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }

        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');

            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();
            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) { // 已删除
                $this->returnCode('73000012');
            }
            if ($activity['status'] == 2) { // 已取消
                $this->returnCode('73000013');
            }
            if ($activity['status'] == 3 || $activity['end_time'] < time()) { // 已结束
                $this->returnCode('73000014');
            }
           

            //验证是否是创建者
            if ($info['uid'] != $activity['uid']) {
                $this->returnCode('71000036');
            }

            if ($activity['enroll_status'] == 1) { // 已开启
                $this->returnCode('71000053');
            }

            if ($activity['deadline'] < time()) { // 报名截止时间小于当前时间
                $this->returnCode('71000054');
            }

            //改变状态
            $res = $database_activity_offline->where('activity_id='.$_POST['activity_id'])->data(array('enroll_status'=>1))->save();
            if ($res) {
                $this->returnCode(0,'开启成功');
            } else {
                $this->returnCode(1,array(),'开启失败，请重试');
            }
        }
    }

    //完成活动 活动结束之后方可点击完成活动
    public function finish_one(){
        // 判断用户是否登录
        if ($this->user_session['uid']) {
            $info['uid'] = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }

        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_join = M('Activity_offline_join');
            $database_order= M('Activity_offline_order');
            $database_user = M('User');

            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }

            // 参与者报名id
            if (empty($_POST['join_id'])) {
                $this->returnCode('71000059');
            }

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();
            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) { // 已删除
                $this->returnCode('73000012');
            }
            if ($activity['status'] == 2) { // 已取消
                $this->returnCode('73000013');
            }
            if ($activity['status'] == 3 ) { // 已完成
                $this->returnCode('71000057');
            }
            if ($activity['end_time'] > time()) { // 活动未结束，不可完成
                $this->returnCode('71000058');
            }

            

            //验证是否是创建者
            if ( $activity['uid'] != $info['uid'] ) {
                $this->returnCode('71000067');
            }

            // 查询报名信息
            $join_info = $database_activity_offline_join->where(array('join_id'=>$_POST['join_id']))->find();


            if (!$join_info) {
                $this->returnCode('71000060');
            }
            if ($join_info['status'] == 2) { // 已完成
                $this->returnCode('71000061');
            }
            if ($join_info['status'] == 4) { // 已退款
                $this->returnCode('71000062');
            }
            D()->startTrans();

            //保存报名状态
            $res = $database_activity_offline_join->where(array('join_id'=>$_POST['join_id']))->data(array('status'=>2))->save();
            if ($res) {
                // 查询已付款订单
                if ($join_info['order_id']) {
                    $order = $database_order->where(array('order_id'=>$join_info['order_id'],'status'=>1))->find();
                    if (!$order) {
                        D()->rollback();
                        $this->returnCode(1, array(), '订单信息不存在');
                        exit;
                    }
                   $res2['error_code'] = 0;
                    if($res2['error_code'] == 0){
                        D()->commit();
                        $this->returnCode(0, '完成成功');
                    }else{
                        D()->rollback();
                        $this->returnCode(1, array(), '操作失败，请重试~');
                    }
                } else {
                    D()->commit();
                    $this->returnCode(0, '完成成功');
                }
            } else {
                D()->rollback();
                $this->returnCode(1, array(), '操作失败，请重试~');
            }
        }
    }

    //报名管理
    public function join_list(){
        // 判断用户是否登录
        if ($this->user_session['uid']) {
            $info['uid'] = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }

        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');

            $database_order = M('Activity_offline_order');


            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();

            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) { // 已删除
                $this->returnCode('73000012');
            }

            $activity['is_owner'] = 0;
            //验证是否是创建者
            if ($activity['uid'] == $info['uid']) {
                $activity['is_owner'] = 1;
            }
            if($activity['start_time']< time() && time() < $activity['end_time']){
              $activity['can_signup'] = true;
            }else{
              $activity['can_signup'] = false;
            }
            if($activity['status'] == 3){//手动结束活动
                $activity['can_signup'] = false;
            }
            if ($activity['start_time']> time()) {
                $activity['is_start'] = false;
            } else {
                $activity['is_start'] = true;
            }
            $where = array();
            $where['_string'] = '`aj`.`activity_id`='.$_POST['activity_id'];
            //验证是否发起者
            if (!$activity['is_owner'] ) {
                // $this->returnCode('71000049');
                $status = ' AND `aj`.`status` in(1,2,5,6)';
            } else {
                $status = ' AND `aj`.`status` in(1,2,4,5,6)';
                if ($_POST['status']) {
                    $status = ' AND `aj`.`status` ='.$_POST['status'];
                }
                if ($_POST['phone']) {
                    $where['_string'] .= ' AND `aj`.`phone`='.$_POST['phone'];
                }
                if ($_POST['name']) {
                    $where['_string'] .= ' AND `aj`.`name` like "%'.$_POST['name'].'%"';
                }
            }
            $where['_string'] .= $status;

            $activity['start_time_format'] = time_info($activity['start_time'], '', true);

            //是否进行中
            $activity['is_going'] = 0;
            if ($activity['start_time'] <= time()) {
                $activity['is_going'] = 1;
            }
            //活动图片
            if ($activity['image']) {
                $activity['image'] = explode(';', $activity['image']);
                foreach ($activity['image'] as &$_img) {
                    $_img = file_domain() . '/upload/activity_offline/' . $_img;
                }
            }
            $activity['image'] = $activity['image'][0];
            $activity['final_money'] = round($activity['fees_all']-$activity['fees'],2);
            //判断是否为免费活动
            if($activity['fees']>0){
              $field = array('`u`.`avatar`', '`u`.`nickname`','`aj`.*','`aj`.`status` as aj_status','`ao`.*','`ao`.`order_id` as `ao_order_id`','`ao`.`activity_id` as `ao_activity_id`','`ao`.`uid` as `ao_uid`','`ao`.`money` as `ao_money`','`ao`.`status` as `ao_status`','`ao`.`add_time` as `ao_add_time`');
              $table = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'activity_offline_join'=>'aj',C('DB_PREFIX').'activity_offline_order'=>'ao');
              $where['_string'] .= ' AND `u`.`uid` = `aj`.`uid` AND `ao`.`order_id` = `aj`.`order_id`';
            }else{
              $field = array('`u`.`avatar`', '`u`.`nickname`','`aj`.*','`aj`.`status` as aj_status');
              $table = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'activity_offline_join'=>'aj');
              $where['_string'] .= ' AND `u`.`uid` = `aj`.`uid`';
            }

            $activity_count = D()->table($table)->field($field)->where($where)->count();
            $page = intval($_POST['page']) ? intval($_POST['page']) : 1;
            $pageSize = 10;
            $totalPage = ceil($activity_count / $pageSize);
            $firstRow = $pageSize * ($page - 1);
            $limit = $firstRow.','.$pageSize;
            $activity_list = D()->table($table)->field($field)->where($where)->order('`aj`.`add_time` DESC')->limit($limit)->select();
            if ($activity_list) {
                foreach ($activity_list as &$value) {
                    $value['add_time_format'] = time_info($value['add_time'], '', true);
                    $value['orderid'] = '';
                    if ($value['order_id']) {
                        $order = $database_order->where(array('order_id'=>$value['order_id']))->find();
                        $value['orderid'] = $order&&$order['orderid'] ? $order['orderid'] : '' ;
                    }
                    if($activity['status'] == 2){//手动取消活动
                        $value['aj_status'] = 6;
                    }
                    $activity['final_pay'] = 0;
                    // 查询尾款支付状态
                    if ($activity['final_money']>0) {
                        $where = array();
                        $where['uid'] = $value['uid'];
                        $where['activity_id'] = $value['activity_id'];
                        $where['status'] = 1;
                        $where['is_final_pay'] = 1;
                        $final_order = M('Activity_offline_order')->where($where)->find();
                        if ($final_order) {
                            $value['final_pay'] = 1;
                            $value['final_order'] = $final_order;
                        }
                    }
                }
            } else {
                $activity_list = [];
            }

            $activity_info = array(
                'total' => $activity_count,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $activity_list
            );
            $this->returnCode(0, array('activity'=>$activity,'lists'=>$activity_info));
        }
    }

    // 查看报名详情
    // public function join_detail(){
    //     // 判断用户是否登录
    //     if ($this->user_session['uid']) {
    //          $info['uid'] = $this->user_session['uid'];
    //     }else{
    //         $this->returnCode('73000008');
    //     }
    //     if (!empty($_POST)) {
    //         // 处理添加的数据
    //         $database_activity_offline = M('Activity_offline');
    //         $database_activity_offline_join = M('Activity_offline_join');
    //         $database_order= M('Activity_offline_order');

    //         //报名id
    //         if (empty($_POST['join_id'])) {
    //             $this->returnCode('71000059');
    //         }

    //         // 查询报名信息
    //         $join_info = $database_activity_offline_join->where(array('join_id'=>$_POST['join_id']))->find();
    //         if($_POST['join_id'] != 'is_owner'){
    //             if (!$join_info) {
    //                 $this->returnCode('71000060');
    //             }
    //         }


    //         //查询活动
    //         $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();

    //         if (!$activity) {
    //             $this->returnCode('73000011');
    //         }
    //         if ($activity['is_del'] == 1) { // 已删除
    //             $this->returnCode('73000012');
    //         }
    //         // 判断活动是否结束
    //         $now_time = time();
    //         if (intval($activity['end_time']) > intval($now_time)) {
    //             $activity['is_end'] = false;
    //         } else {
    //             $activity['is_end'] = true;
    //         }
    //         if($activity['status'] == 3){//手动结束活动
    //             $activity['is_end'] = true;
    //         }

    //         //查询昵称 活动所属者
    //         $user = D('User')->field('avatar,nickname')->where('uid='.$activity['uid'])->find();
    //         $activity['avatar'] = $user['avatar'] ? $user['avatar'] : '';

    //         // 时间格式
    //         $activity['start_time'] = date('Y-m-d H:i' , $activity['start_time']);
    //         $activity['end_time'] = date('Y-m-d H:i' , $activity['end_time']);
    //         $activity['deadline'] = date('Y-m-d H:i' , $activity['deadline']);
    //         $activity['add_time'] = date('Y-m-d H:i' , $activity['add_time']);

    //         // 是否创建人
    //         $activity['is_owner'] = ($activity['uid']==$info['uid']) ? 1 : 0;

    //          //活动图片
    //         if ($activity['image']) {
    //             $activity['image'] = explode(';', $activity['image']);
    //             foreach ($activity['image'] as &$_img) {
    //                 $_img = file_domain() . '/upload/activity_offline/' . $_img;
    //             }
    //         }
    //         $activity['image'] = $activity['image'][0];
    //         // 报名所属者
    //         if(!$join_info){
    //             $user_id = $activity['uid'];
    //         }else{
    //             $user_id = $join_info['uid'];
    //         }
    //         $user_info = D('User')->field('avatar,nickname,phone')->where(array('uid'=>$user_id))->find();
    //         $join_info['name'] = $join_info['name'] ? $join_info['name'] : $user_info['nickname'];
    //         $join_info['phone'] = $join_info['phone'] ? $join_info['phone'] : $user_info['phone'];
    //         $join_info['avatar'] = $user_info['avatar'] ? $user_info['avatar'] : '';
    //         $join_info['add_time_format'] = date('Y-m-d H:i' , $join_info['add_time']);
    //         $join_info['content'] = $join_info['content'] ? json_decode($join_info['content']) : '';
    //         if($join_info['money']>0 && in_array($join_info['status'],array(1,3))){
    //             $join_info['is_refund'] = true;
    //         }else{
    //             $join_info['is_refund'] = false;
    //         }
    //         if($join_info['uid'] == $info['uid']){
    //             $join_info['is_mine'] = true;
    //         }else{
    //             $join_info['is_mine'] = false;
    //         }

    //         if($activity['status'] == 2){//手动取消活动
    //             $join_info['status'] = 6;
    //         }
    //         $activity['join_info'] = $join_info;
    //         if(!$join_info['have_submit']){
    //             $join_info['have_submit']['status']='';
    //         }
    //         $activity['refund_examine'] = $join_info['have_submit'];

    //          // 查询订单
    //         $activity['order'] = [];
    //         if ($join_info['order_id']) {
    //             $activity['order'] = $database_order->where(array('order_id'=>$join_info['order_id']))->find();
    //             $activity['order']['add_time'] = date('Y-m-d H:i',$activity['order']['add_time']);
    //         }
    //         if(!$activity){
    //             $activity = array();
    //         }
    //         $this->returnCode(0, $activity);
    //     }
    // }

    //完成活动 活动结束之后方可点击完成活动
    public function finish_all(){
        // 判断用户是否登录
        $this->check_login();

        //活动id
        $_POST['activity_id'] = intval($_POST['activity_id']);
        if (empty($_POST['activity_id'])) {
            $this->returnCode('73000010');
        }

        // 处理添加的数据
        $database_activity_offline = D('Activity_offline');
        $database_activity_offline_join = D('Activity_offline_join');
        $database_offline_order = D('Activity_offline_order');

        //查询活动
        $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();
        if (!$activity) {
            $this->returnCode('73000011');
        }
        if ($activity['is_del'] == 1) { // 已删除
            $this->returnCode('73000012');
        }
        if ($activity['status'] == 2) { // 已取消
            $this->returnCode('73000013');
        }
        if ($activity['status'] == 3 ) { // 已完成
            $this->returnCode('71000057');
        }
        if ($activity['end_time'] > time()) { // 活动未结束，不可完成
            $this->returnCode('71000058');
        }
       
        //验证是否是创建者
        if ( $activity['uid'] != $this->user_session['uid'] ) {
            $this->returnCode('71000067');
        }

        //修改活动状态
        $res = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id']))->data(array('status'=>3))->save();
        if (!$res) {
            $this->returnCode('71000078');
        }

        // 获取已报名信息
        $join_list = $database_activity_offline_join->where(array('activity_id'=>$_POST['activity_id'],'status'=>array('in', array(1,5))))->select();
        if (!$join_list) {
            $this->returnCode(0,L('完成成功'));
        }

        // 修改参与状态
        $res1 = $database_activity_offline_join->where(array('activity_id'=>$_POST['activity_id'],'status'=>array('in', array(1,5))))->data(array('status'=>2))->save();
        if (!$res1) {
            $this->returnCode('71000078');
        }

        // 查询已付款订单
        $order_list = $database_offline_order->field(true)->where(array('status'=>1,'activity_id'=>$_POST['activity_id']))->select();
        if (!$order_list) {
            $this->returnCode(0, L('完成成功'));
        }
        $money = $database_offline_order->field('sum(money) as total_money')->where(array('status'=>1,'activity_id'=>$_POST['activity_id']))->find();
        $total_money = $money['total_money'];

        // 增加发布者余额
        $res = D('User')->add_money($activity['uid'],  $total_money, L_("完成活动【X1】，增加余额",array("X1" => $activity['title'])));
        if($res['error_code']){
            $this->returnCode(1, array(), $res['msg']);
        }

        // 获取抽成
        $res = $database_offline_order->take_percentage($activity['activity_id'],$order_list);
        $this->returnCode(0, L('完成成功'));
    }

    //签到  活动进行中方可签到
    public function sign_in_one(){
        // 判断用户是否登录
        if ($this->user_session['uid']) {
            $info['uid'] = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }

        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_join = M('Activity_offline_join');
            $database_order= M('Activity_offline_order');

            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }

            // 参与者报名id
            if (empty($_POST['join_id'])) {
                $this->returnCode('71000059');
            }

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id'],'is_del'=>0))->find();
            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) { // 已删除
                $this->returnCode('73000012');
            }
            if ($activity['status'] == 2) { // 已取消
                $this->returnCode('73000013');
            }
            if ($activity['status'] == 3 ) { // 已完成
                $this->returnCode('71000057');
            }
            if ($activity['start_time'] > time()) { // 活动未开始，不可签到
                $this->returnCode('71000069');
            }
            if ($activity['end_time'] < time()) { // 活动已经结束，不可签到
                $this->returnCode('71000068');
            }



            //验证是否是创建者
            if ( $activity['uid'] != $info['uid'] ) {
                $this->returnCode('71000067');
            }

            // 查询报名信息
            $join_info = $database_activity_offline_join->where(array('join_id'=>$_POST['join_id']))->find();


            if (!$join_info) {
                $this->returnCode('71000060');
            }
            if ($join_info['status'] == 2) { // 已完成
                $this->returnCode('71000071');
            }
            if ($join_info['status'] == 4) { // 已退款
                $this->returnCode('71000062');
            }
            if ($join_info['status'] == 5) { // 已签到
                $this->returnCode('71000072');
            }
            if ($join_info['status'] == 6) { // 已取消
                $this->returnCode('73000057');
            }
            D()->startTrans();

            //保存报名状态
            $res = $database_activity_offline_join->where(array('join_id'=>$_POST['join_id']))->data(array('status'=>5))->save();
            if ($res) {
                // 查询已付款订单
                if ($join_info['order_id']) {
                    $order = $database_order->where(array('order_id'=>$join_info['order_id'],'status'=>1))->find();
                    if (!$order) {
                        D()->rollback();
                        $this->returnCode(1, array(), '订单信息不存在');
                        exit;
                    }
                    D()->commit();
                    $this->returnCode(0, '完成成功');
                } else {
                    D()->commit();
                    $this->returnCode(0, '完成成功');
                }
            } else {
                D()->rollback();
                $this->returnCode(1, array(), '操作失败，请重试~');
            }
        }
    }

    //城市列表
    public function area(){
        $where['area_type'] = $_POST['area_type'] ? $_POST['area_type'] : 1;
        $where['is_open'] = 1;//开启
        $cate_where['status'] = 0;
        $cate_where['fid'] = array('neq',0);
        $has_area = M('Activity_offline_cate')->field('id,province_id,city_id')->where($cate_where)->select();
        
        if($has_area && $where['area_type'] == 1){//当查询省一级时
            foreach ($has_area as $key => $value) {
                $ids[] = $value['province_id'];
            }
            $res = array_unique($ids);
            $where['area_id'] = array('in',$res);
        }
        if($_POST['area_id'] && $where['area_type']==2){
            foreach ($has_area as $key => $value) {
                $idss[] = $value['city_id'];
            }
            $ress = array_unique($idss);
            $str = implode(',', $ress);
            $where['_string'] = '`area_id` IN ('.$str.') AND `area_pid` = '.$_POST['area_id'];
        }
        $area_list = M('Area')->field(true)->where($where)->select();
        $this->returnCode(0, $area_list);
        
    }

    //分类列表
    public function cate_list(){
        $where['status'] = 0;
        $where['fid'] = $_POST['id'] ? $_POST['id'] : 0;
        if($_POST['province_id']){
            $where['province_id'] = $_POST['province_id'];
        }
        if($_POST['city_id']){
            $where['city_id'] = $_POST['city_id'];
        }
        
        $cate_list = M('Activity_offline_cate')->field(true)->where($where)->order('sort desc')->select();

        $this->returnCode(0, $cate_list);
        
    }

    public function activity_list_api(){
        // 判断用户是否登录
        if ($this->user_session['uid']) {
            $uid = $this->user_session['uid'];
            $manager_info = M('Classify_manager')->field(true)->where(array('uid'=>$uid,'status'=>1))->find();
            $manager_area = M('Manager_area')->field('cate_id')->where(array('uid'=>$uid))->select();//审核区域
            $ids = array();
            if ($manager_area) {
                foreach($manager_area as $key=>$value){
                    if ($value['cate_id']) {
                        $ids[] = $value['cate_id'];
                    }
                }
            }
            if(!$manager_info){
                $this->returnCode(0,array());
            }
            $activity_where['province_id'] = $manager_info['province_id'];
            $activity_where['city_id'] = $manager_info['city_id'];
            $activity_where['cate_id'] = array('in',$ids);
            $activity_where['pay_status'] = 2;
        }else{
            $this->returnCode('73000008');
        }
        $activity_model = M('Activity_offline');
        $examine_status = intval($_POST['examine_status']) ? intval($_POST['examine_status']) : 1;
        $is_top = intval($_POST['is_top']) ? intval($_POST['is_top']) : 0;//是否置顶 0否 1是
        $activity_where = array();
        if($examine_status){//审核状态(1.待审核2.已通过3.已拒绝)
            $activity_where['examine_status'] = $examine_status;
            $activity_where['status'] = 1;
            if(!$is_top){
                $activity_where['end_time'] = array('egt',time());
            }
        }
        // 处理需要付费未付费的过滤
        $activity_where['_string'] = ' NOT (pay_status=1 AND fees!=0)';

        if($is_top){
            $activity_where['is_top'] = $is_top;
        }
        if($_POST['cate_id']){
            $fids = M('Activity_offline_cate')->field('fid')->where(array('fid'=>$_POST['cate_id'],'status'=>0))->select();
            $fid = array();
            if ($fids) {
                foreach($fids as $key=>$value){
                    if ($value['fid']) {
                        $fid[] = $value['fid'];
                    }
                }
            }
            $activity_where['cate_id'] = array('in',$fid);
        }
        $page = $_POST['page'] ? $_POST['page'] : 1;
        $pageSize=10;
        $total =$activity_model->where($activity_where)->count();
        $page = isset($page) ? intval($page) : 1;
        $totalPage = ceil($total / $pageSize);
        $firstRow = $pageSize * ($page - 1);
        $list = $activity_model->where($activity_where)->limit($firstRow.','.$pageSize)->order('add_time desc')->select();//查询后台开启的分类的活动

        foreach($list as $key =>$value){
            $list[$key]['image'] = file_domain().'/upload/activity/'.$value['image'];
            $list[$key]['start_time'] = date('Y-m-d H:i', $value['start_time']);
            $list[$key]['end_time'] = date('Y-m-d H:i', $value['end_time']);
            $list[$key]['add_time'] = date('Y-m-d H:i', $value['add_time']);
            if(time() < ($value['add_time']+259200) && $value['status'] == 1){
                $list[$key]['status'] = '4'; 
            }
        }
        $info_list = array(
            'total' => $total,
            'pageTotal' => $totalPage,
            'has_more' => $totalPage > $page ? true : false,
            'list' => $list
        );
        
        $this->returnCode(0,$info_list);
        
    }

    /**
     * [add_activity_reply  添加活动评论]
     */
    public function add_activity_reply(){
        if (!$this->user_session['uid']) {
            $this->returnCode('73000008');
        }
        $activity_id = intval($_POST['activity_id']);
        if(!$activity_id){
            $this->returnCode('73000001');
        }else{
            $activity_model = M('Activity_offline_reply');
            $is_reply = $activity_model->where(array('activity_id'=>$activity_id,'uid'=>$this->user_session['uid']))->find();
            if($is_reply && empty($_POST['fid'])){
                $this->returnCode('73000016');
            }
            if(!$_POST['content']){
                $this->returnCode('73000015');
            }

            if(!empty($_FILES) && $_FILES['file']['error'] != 4){//图片上传
                $image = D('Image')->handle($this->user_session['uid'], 'activity', 2, array('size' => 10), false, false);
                if (!$image['error']) {
                    $_POST = array_merge($_POST, $image['url']);
                } else {
                    $this->returnCode(1,array(), $image['message']);
                }
            }

            if (!empty($_POST['fid'])) {
                $reply_type = 2;
            } else {
                $reply_type = 1;
            }
            $data['reply_type'] = $reply_type;
            $data['image'] = $_POST['file'] ? serialize($_POST['file']) : '';
            $data['content'] = htmlspecialchars($_POST['content']);//评论内容
            $data['reply_star'] = $_POST['reply_star'] ? intval($_POST['reply_star']) : 5;//评论星级  默认为5星
            $data['uid'] = $this->user_session['uid'];
            $data['activity_id'] = intval($_POST['activity_id']);
            $data['is_anonymous'] = $_POST['is_anonymous'] ? intval($_POST['is_anonymous']) : 2;//是否匿名 1匿名 2不匿名 默认为2
            $data['fid'] = $_POST['fid'] ? intval($_POST['fid']) : 0;//父id  默认为一级回复
            $data['add_time'] = time();
            $res = $activity_model->add($data);
            if($res){
                if ($reply_type == 1) {
                    // 重新计算下活动平均分，记入活动
                    $reply = $activity_model->field('SUM(reply_star) as total_star')->where(array('activity_id'=>$_POST['activity_id'], 'is_del' => 0, 'fid' => 0))->find();
                    $reply_count = $activity_model->where(array('activity_id'=>$_POST['activity_id'], 'is_del' => 0, 'fid' => 0))->count();
                    $total_star = $reply['total_star'];
                    if ($reply_count > 0) {
                        $total_star = $total_star / $reply_count;
                        M('Activity_offline')->where(array('activity_id'=>intval($_POST['activity_id'])))->data(array('average_star' => $total_star))->save();
                    }
                } else {
                    // 添加讨论数
                    $activity_model->where(array('id' => intval($_POST['fid'])))->setInc('reply_num');
                }
                $this->returnCode(0,$res);
            }else{
                $this->returnCode('73000003');
            }
        }
    }

    public function reply_list(){
        //活动ID
        $activity_id = intval($_POST['activity_id']);
        if(!$activity_id){
            $this->returnCode('73000001');
        }else{
            $activity_model = M('Activity_offline_reply');
            $activity_where['a.activity_id'] = $activity_id;
            $activity_where['a.fid'] = 0;
            $page = $_POST['page'] ? $_POST['page'] : 1;
            $pageSize=10;
            $total =$activity_model->alias('a')->where($activity_where)->join('join pigcms_user as b on a.uid=b.uid')->count();
            $page = isset($page) ? intval($page) : 1;
            $totalPage = ceil($total / $pageSize);
            $firstRow = $pageSize * ($page - 1);
            $list = $activity_model->alias('a')->field('a.*,b.nickname')->where($activity_where)->join('join pigcms_user as b on a.uid=b.uid')->limit($firstRow.','.$pageSize)->order('add_time desc')->select();
            foreach($list as $key =>$value){
                $list[$key]['add_time'] = date('Y-m-d',$value['add_time']);
                $arr = unserialize($value['image']);
                if ($arr) {
                    if(is_array($arr)){
                        foreach($arr as $k=>$v){
                            $list[$key]['img'][] = $v;
                            $list[$key]['images'][] = replace_file_domain($v);
                        }
                    }else{
                        $list[$key]['img'] = $arr;
                        $list[$key]['images'][] = replace_file_domain($arr);
                    }
                }
                if($value['is_anonymous'] == 1){
                    $list[$key]['nickname'] = '匿名用户';
                }
            }
            $reply = $activity_model->field('SUM(reply_star) as total_star')->where(array('activity_id'=>$activity_id, 'is_del' => 0, 'fid' => 0))->find();
            $reply_count = $activity_model->where(array('activity_id'=>$activity_id, 'is_del' => 0, 'fid' => 0))->count();
            $total_star = $reply['total_star'] ? $reply['total_star'] : 0;
            if ($reply_count > 0 && $total_star>0) {
                $total_star = sprintf("%.1f",($total_star/$reply_count));
            }
            $info_list = array(
                'total_star'=>$total_star,
                'count' => $reply_count,
                'total' => $total,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $list
            );
            
            $this->returnCode(0,$info_list);
        }    
    }


    /**评论回复相关信息*/
    public function comment_reply_list(){
        //评论ID
        $reply_id = intval($_POST['id']);
        if(!$reply_id){
            $this->returnCode('73000001');
        }else{
            $activity_model = M('Activity_offline_reply');
            // 获取当前评论信息
            $now_comment = $activity_model->where(array('id' => $reply_id))->find();
            if (empty($now_comment)) {
                $this->returnCode('73000051');
            }
            $now_comment['nickname'] = M('User')->where(array('uid'=>$now_comment['uid']))->getField('nickname');

            $now_comment['add_time'] = date('Y-m-d',$now_comment['add_time']);
            $now_comment_img_arr = unserialize($now_comment['image']);
            if(is_array($now_comment_img_arr)){
                foreach($now_comment_img_arr as $k=>$v){
                    $now_comment['img'][] = $v;
                    $now_comment['images'][] = replace_file_domain($v);
                }
            }elseif($now_comment_img_arr){
                $now_comment['img'] = $now_comment_img_arr;
                $now_comment['images'][] = replace_file_domain($now_comment_img_arr);
            }
            if($now_comment['is_anonymous'] == 1){
                $now_comment['nickname'] = '匿名用户';
            }



            $activity_info = M('Activity_offline')->where(array('activity_id' => $now_comment['activity_id']))->find();

            $activity_where['a.activity_id'] = $now_comment['activity_id'];
            $activity_where['a.fid'] = $reply_id;
            $page = $_POST['page'] ? $_POST['page'] : 1;
            $pageSize=10;
            $total =$activity_model->alias('a')->where($activity_where)->join('join pigcms_user as b on a.uid=b.uid')->count();
            $page = isset($page) ? intval($page) : 1;
            $totalPage = ceil($total / $pageSize);
            $firstRow = $pageSize * ($page - 1);
            $list = $activity_model->alias('a')->field('a.*,b.nickname')->where($activity_where)->join('join pigcms_user as b on a.uid=b.uid')->limit($firstRow.','.$pageSize)->order('add_time desc')->select();
            foreach($list as $key =>$value){
                $list[$key]['add_time'] = date('Y-m-d',$value['add_time']);
                $arr = unserialize($value['image']);
                if(is_array($arr)){
                    foreach($arr as $k=>$v){
                        $list[$key]['img'][] = $v;
                        $list[$key]['images'][] = replace_file_domain($v);
                    }
                }elseif($arr){
                    $list[$key]['img'] = $arr;
                    $list[$key]['images'][] = replace_file_domain($arr);
                }
                if($value['is_anonymous'] == 1){
                    $list[$key]['nickname'] = '匿名用户';
                }
            }
            $info_list = array(
                'total' => $total,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $list,
                'activity_info' => $activity_info,
                'now_comment' => $now_comment
            );

            $this->returnCode(0,$info_list);
        }
    }

    /**
     * [go_pay_activity 去付款(报名未付款)]
     * @return [type] [description]
     */
    public function go_pay_join(){
        $activity_id = intval($_POST['activity_id']);
        // 判断用户是否登录
        if ($this->user_session['uid']) {
            $uid = $this->user_session['uid'];
        }else{
            $this->returnCode('73000008');
        }
        if($activity_id || $uid){
            $plat_order = D('Plat_order');
            $activity = M('Activity_offline');
            $activity_order = M('Activity_offline_order');
            $activity_info = $activity->field(true)->where(array('activity_id'=>$activity_id))->find();//活动信息
            $activity_order_info = $activity_order->field(true)->where(array('activity_id'=>$activity_id,'uid'=>$uid))->find();//报名订单信息
            if($activity_order_info && $activity_order_info['status']==2){
                if($activity_info){
                    $where['business_type'] = 'activity_offline';
                    $where['business_id'] = $activity_order_info['order_id'];
                    $where['order_name'] = '线下活动';
                    $where['uid'] = $uid;
                    $where['total_money'] = $activity_info['fees'];
                    $where['paid'] = 0;
                    $plat_order_result = $plat_order->field(true)->where($where)->find();
                    if($plat_order_result){
                        $this->returnCode(0,array('url'=>U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat'))));
                    }else{
                        $this->returnCode('73000043');
                    }

                }else{
                    $this->returnCode('73000002');
                }
            }else{
                $this->returnCode('73000042');
            }

        }else{
            $this->returnCode('73000010');
        }
    }    

    /**
     * [go_pay_final 去付款(已成团，支付尾款)]
     * @return [type] [description]
     */
    public function go_pay_final(){
       // 判断用户是否登录
        if (!$this->user_session['uid']) {
            $this->returnCode('73000008');
        }
        $uid = $this->user_session['uid'];
        $data = array();
        if (!empty($_POST)) {
            // 处理添加的数据
            $database_activity_offline = M('Activity_offline');
            $database_activity_offline_join = M('Activity_offline_join');
            $database_activity_offline_order= M('Activity_offline_order');
            //活动id
            if (empty($_POST['activity_id'])) {
                $this->returnCode('73000010');
            }
            $data['activity_id'] = $_POST['activity_id'];

            //查询活动
            $activity = $database_activity_offline->where(array('activity_id'=>$_POST['activity_id']))->find();
            if (!$activity) {
                $this->returnCode('73000011');
            }
            if ($activity['is_del'] == 1) { // 已删除
                $this->returnCode('73000012');
            }
            if ($activity['status'] == 2) { // 已取消
                $this->returnCode('73000013');
            }
            if ($activity['status'] == 3 || $activity['end_time'] < time()) { // 已结束
                $this->returnCode('73000014');
            }

            //验证是否已报名
            $activity_where['activity_id'] = $data['activity_id'];
            $activity_where['uid'] = $uid;
            $is_jioned = $database_activity_offline_join->where($activity_where)->find();
            if (!$is_jioned || $is_jioned['status']!=1) { // 已经报名成功
                $this->returnCode('71000075');
            } 

            $pay_money = round($activity['fees_all']-$activity['fees'],2);
            if ($pay_money>0) {
                $order_data = array(
                    'is_final_pay'  =>  1,
                    'activity_id'   =>  $_POST['activity_id'],
                    'uid'           =>  $this->user_session['uid'],
                    'money'         =>  $activity['fees'],
                    'pay_type'      =>  '',
                    'status'        =>  2,
                    'add_time'      =>  time()
                );
                $res = M('Activity_offline_order')->data($order_data)->add();
                if ($res) {
                    $data['final_pay_status'] = 1; // 待付款
                    
                    $join_id = $is_jioned['join_id'];
                    $addInfo = $database_activity_offline_join->where(array('activity_id'=>$activity['activity_id'],'uid'=>$uid))->data($data)->save();
                } else {
                    $this->returnCode(12, array(), L('尾款支付失败，请重试'));
                }

                if($res){
                    $pay_order_param = array(
                        'business_type' => 'activity_offline',
                        'business_id' => $res,
                        'order_name' => '线下活动',
                        'uid' => $this->user_session['uid'],
                        'total_money' => $pay_money,
                    );
                    $plat_order_result = D('Plat_order')->add_order($pay_order_param);
                    if ($plat_order_result['error_code']) {
                        $this->returnCode('1','',$plat_order_result['error_msg']);
                    } else {
                        $this->returnCode(0,array('url'=>U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat'))));
                    }
                }else{
                    $this->returnCode('71000076');
                }
            } else {
                $user_info = M('User')->field(true)->where(array('uid'=>$uid))->find();
                $owner_user_info = M('User')->field(true)->where(array('uid'=>$activity['uid']))->find();
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                if ($user_info['openid']) {
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=indexDetail&activity_id=' . $activity['activity_id'];
                    $model->sendTempMsg('TM00017', array(
                        'href' => $href,
                        'wecha_id' => $user_info['openid'],
                        'first' => '您已报名活动【'.$activity['title'].'】',
                        'OrderSn' => $activity['activity_id'],
                        'OrderStatus' => '报名成功',
                        'remark' => date('Y-m-d H:i:s')
                    ));//活动报名成功模板推送
                }
                if ($owner_user_info['openid']) {
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=activityParticPation&activity_id=' . $activity['activity_id'];
                    $model->sendTempMsg('TM00017', array(
                        'href' => $href,
                        'wecha_id' => $owner_user_info['openid'],
                        'first' => $user_info['nickname'].'已报名活动【'.$activity['title'].'】',
                        'OrderSn' => $activity['activity_id'],
                        'OrderStatus' => '报名成功',
                        'remark' => date('Y-m-d H:i:s')
                    ));//活动报名成功模板推送
                }
                $this->returnCode(0,array('url'=>U('ActivityOffline/indexDetail', array('activity_id' => $activity['activity_id']))));
              
            }
        }
    }

    private function get_curl_img($url,$path,$uniName){
        //图片存放的路径
        if(!file_exists($path)){
            mkdir($path,0777,true); //创建目录
            chmod($path,0777); //赋予权限
        }
        //确保图片名唯一，防止重名产生覆盖
        $res =put_file_from_url_content($url,$path,$uniName);
        if($res){
            $result = C('config.site_url').substr($res['save_path'],1);
            return $result;
        }
    }


}


