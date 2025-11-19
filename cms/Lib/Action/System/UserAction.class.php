<?php

/*
 * 用户中心
 *
 */

class UserAction extends BaseAction
{
    public function index()
    {
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition_user['uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['phone'] = trim($_GET['keyword']);
            } else if ($_GET['searchtype'] == 'school_no') {
                $condition_user['school_no'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'school_sfz') {
                $condition_user['school_sfz'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        //排序
        $order_string = '`uid` DESC';
        if ($_GET['sort']) {
            switch ($_GET['sort']) {
                case 'uid':
                    $order_string = '`uid` DESC';
                    break;
                case 'lastTime':
                    $order_string = '`last_time` DESC';
                    break;
                case 'money':
                    $order_string = '`now_money` DESC';
                    break;
                case 'score':
                    $order_string = '`score_count` DESC';
                    break;
            }
        }
        $levelDb = M(_view('User_level'));
        $tmparr = $levelDb->field(true)->order('id ASC')->select();
        $levelarr = array();
        if ($tmparr) {
            foreach ($tmparr as $vv) {
                $levelarr[$vv['level']] = $vv;
            }
        }
		
		if ($this->system_session['area_id']) {
			$now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
			if($now_area['area_type']==3){
				die('区域管理员无法管理用户');
			}elseif($now_area['area_type']==2){
				$area_index = 'city_id';
			}elseif($now_area['area_type']==1){
				$area_index = 'province_id';
			}
			$this->assign('admin_area',$now_area['area_type']);
			$condition_user[$area_index] = $this->system_session['area_id'];
		}else{
			if($_GET['province_idss']){
				$condition_user['province_id'] = $_GET['province_idss'];
			}
			if($_GET['city_idss']){
				$condition_user['city_id'] = $_GET['city_idss'];
			}
		}
		

//        $condition_user['openid'] = array('notlike', '%no_use%');
        $condition_user['_string'] = '(openid NOT LIKE "%no_use%" OR openid IS NULL)';

		//是否超级管理员
        $isRoot  = $this->system_session['level'] == 2;
        $this->assign('isRoot',$isRoot);

        //状态
        //$condition_user['status'] = array('neq', 4);
        if ($_GET['status'] != '') {
            $condition_user['status'] = $_GET['status'];
        }
        if ($_GET['level'] > 0) {
            $condition_user['level'] = $_GET['level'];
        }
        if ($_GET['label_id'] > 0) {
            $label_list = M('User_label_bind')->where(array('label_id'=>$_GET['label_id']))->select();
            if($label_list){
                $condition_user['uid'] = ['IN',array_column($label_list,'uid')];
            }else{
                $condition_user['uid'] = 0;
            }
        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            if ($condition_user['_string']) {
                $condition_user['_string'] .= " AND (add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }else{
                $condition_user['_string'] = " (add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }
        }

        //是否具有分佣功能
        if ($_GET['can_spread'] != '') {
            $condition_user['can_spread'] = $_GET['can_spread'];
        }
        if(C('config.open_school') == '1'){
            $condition_user['school_type'] = $_GET['school_type'] == '1' ? 0 : 1;
        }

        $database_user = D('User');
        $count_user = $database_user->where($condition_user)->count();
        import('@.ORG.system_page');
        $p = new Page($count_user, 30);
        $user_list = $database_user->field(true)->where($condition_user)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        $discuzx_url = $this->config['discuzx_url']?$this->config['discuzx_url']:'';
        if (!empty($user_list)) {
			$user_city_ids = array_filter(array_unique(array_column($user_list,'city_id')));
			$user_city_arr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$user_city_ids]])->select(),'area_name','area_id');
			$user_set = M('User_set');
			foreach ($user_list as &$value) {
				$value['city_name'] = $user_city_arr[$value['city_id']] ?: $value['city'];
                $value['last_ip_txt'] = is_numeric($value['last_ip']) ? long2ip($value['last_ip']) : $value['last_ip'];
                if ($discuzx_url) {
                    $value['discuzx_name'] = $user_set->where(['uid'=>$value['uid']])->getField('discuzx_name');
                }
            }
        }

        $this->assign('discuzx_url', $discuzx_url);
        $this->assign('user_list', $user_list);

        $user_balance = array(
            'count' => $database_user->sum('now_money'),
            'open' => $database_user->where(array('status' => 1))->sum('now_money'),
            'close' => $database_user->where(array('status' => 0))->sum('now_money'),
            'score' => $database_user->where(array('status' => 1))->sum('score_count'),
            'recharge_spread_money' => M('User_spread_list')->where(array('order_type' => 'recharge', 'status' => 1))->sum('money'),
        );
        $this->assign('levelarr', $levelarr);
        $this->assign('user_balance', $user_balance);
        //用户标签
        $labelarr = M('user_label')->where(array('is_del' => 0))->order('id asc')->getField('id,name,is_default');
        $this->assign('labelarr', $labelarr);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('client', array(0 => 'WAP端', 1 => '苹果', 2 => '安卓', 3 => '电脑', 4 => '小程序', 5 => '微信', 6 => '支付宝', '7' => '微信扫描商家二维码'));

        $export = 1;//导出列表的权限,只给超级管理员
        if ($this->system_session['level'] < 2) {
            $export = 0;
            
        }
        $this->assign('export',$export);
        $this->display();
    }

    public function user_money_verify(){
        if($this->system_session['level'] != '2' && $this->system_session['verify_user_money_change'] != '1'){
            $this->error('您没有权限审核用户余额变动。');
            exit;
        }
        if(C("config.open_user_money_verify") != '1'){
            $this->error('平台未开启此功能。');
            exit;   
        }
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition['u.uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        if(!empty($_GET['status'])){
            $condition['umv.status'] = array('eq', $_GET['status']-1);
        }

        $database = M('User_money_verify');
        $count_user = $database->alias("umv")->join("left join ".C("DB_PREFIX")."user as u on u.uid = umv.uid")->join("left join ".C("DB_PREFIX")."admin as a on a.id=umv.admin")->where($condition)->count();
        import('@.ORG.system_page');
        $p = new Page($count_user, 30);
        $data = $database->alias("umv")->join("left join ".C("DB_PREFIX")."user as u on u.uid = umv.uid")->join("left join ".C("DB_PREFIX")."admin as a on a.id=umv.admin")->field("umv.*,u.nickname,u.phone,a.realname as admin_name,a.openid as admin_openid,u.openid as user_openid")->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();

        $this->assign("data", $data);
        $this->assign("pagebar", $p->show());
        $this->display();
    }

    public function user_money_verify_handle(){
        $id = I("id");
        if(empty($id)){
            $this->error('非法操作');
            exit;
        }
        $database = M('User_money_verify');
        $info = $database->where(['id'=>$id])->find();
        if(empty($info)){
            $this->error('非法操作');
            exit;
        }
        if($info['status'] != '0'){
            $this->error('该数据已审核');
            exit;
        }
        if(IS_POST){
            if(I("status") == '2' && empty(trim(I('reason')))){
                $this->error('审核理由必填！');
                exit;       
            }
            $data_user = M("User")->where(['uid'=>$info['uid']])->find();
            if ($info['type'] == 1) {
                $data_user['now_money'] = $data_user['now_money'] + $info['money'];
            } else {
                $data_user['now_money'] = $data_user['now_money'] - $info['money'];
            }
            if ($data_user['now_money'] < 0) {
                $this->error('操作失败，修改后，余额不能小于0');
                exit;
            }
            if($database->data(['status'=> I("status"),'reason'=>I('reason'),'check_time'=>date('Y-m-d H:i:s'),'check_adminid'=>$this->system_session['id']])->where(['id'=>$id])->save()){
                if(I("status") == '1'){
                    M("User")->where(['uid'=>$info['uid']])->data(['now_money'=>$data_user['now_money']])->save();
                    if ($data_user['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?c=My&a=transaction';

                        if ($info['type'] == 1) {
                            //充值
                            $newInfo = [
                                'tempKey' => '45572',
                                'thing20' => '平台余额账户',
                                'amount3' => $info['money'],
                                'amount15' => getFormatNumber($data_user['now_money']),
                                'time2' => date('Y-m-d H:i:s')
                            ];
                        } else {
                            //扣除
                            $newInfo = [
                                'tempKey' => '55638',
                                'thing6' => '平台余额账户',
                                'amount2' => $info['money'],
                                'amount4' => getFormatNumber($data_user['now_money']),
                                'time1' => date('Y-m-d H:i:s')
                            ];
                        }
                        $model->sendTempMsg('OPENTM401833445', array('href' => $href,
                            'wecha_id' => $data_user['openid'],
                            'first' => '尊敬的' . $data_user['nickname'] . ',您的平台余额账户发生变动',
                            'keyword1' => date('Y-m-d H:i'),
                            'keyword2' => '管理员手动' . ($info['type'] == 1 ? '充值' : '减少') . '平台余额（备注：' . $info['remark'] . '）',
                            'keyword3' => ($info['type'] == 1 ? '+' : '-') . $info['money'],
                            'keyword4' => round_number($data_user['now_money'],2),
                            'remark' => '详情请点击此消息进入会员中心-余额记录进行查询!',
                            'new_info' => $newInfo),
                            0);
                    }

                    if (C('config.sms_place_order') == 1 || C('config.sms_place_order') == 3) {
                        $sms_data['uid'] = $data_user['uid'];
                        $sms_data['mobile'] = $data_user['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['content'] = "平台在" . date('Y-m-d H:i:s') . "为您账户手动" . ($info['type'] == 1 ? '充值' : '减少') . '余额' . abs($info['money']) . C('config.Currency_txt') . "，请及时查看";
                        Sms::sendSms($sms_data);
                    }

                    if (C('config.zbw_key')) {

                        if ($data_user['zbw_cardid']) {
                            if ($info['type'] == 1) {
                                $result = D('ZbwErp')->VipFullAmt($data_user, $info['money'], '管理员充值');
                            } else {
                                $result = D('ZbwErp')->VipPaySheet($data_user, -1 * $info['money'], '管理员减少');
                            }

                            D('ZbwErp')->sync_data($data_user['uid']);
                        }
                    } else {

                        D('User_money_list')->add_row($data_user['uid'], $info['type'], $info['money'], '管理员后台操作' . (empty($info['remark']) ? '' : ',备注：' . $info['remark']), false, 0, 1, true);
                    }
                    //发送通知给审核管理员
                    $accept = M("Admin")->where('id='.$info['admin'])->field("openid,nickname")->find();
                    if($accept && $accept['openid']){
                        if ($info['type'] == 1) {
                            //充值
                            $newInfo = [
                                'tempKey' => '45572',
                                'thing20' => '平台余额账户',
                                'amount3' => $info['money'],
                                'amount15' => getFormatNumber($data_user['now_money']),
                                'time2' => date('Y-m-d H:i:s')
                            ];
                        } else {
                            //扣除
                            $newInfo = [
                                'tempKey' => '55638',
                                'thing6' => '平台余额账户',
                                'amount2' => $info['money'],
                                'amount4' => getFormatNumber($data_user['now_money']),
                                'time1' => date('Y-m-d H:i:s')
                            ];
                        }

                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM401833445', array('href' => '',
                            'wecha_id' => $accept['openid'],
                            'first' => '尊敬的' . $accept['nickname'] . ',您提交的余额变更通过审核',
                            'keyword1' => date('Y-m-d H:i'),
                            'keyword2' => '管理员手动' . ($info['type'] == 1 ? '充值' : '减少') . '平台余额（备注：' . $info['remark'] . '）',
                            'keyword3' => ($info['type'] == 1 ? '+' : '-') . $info['money'],
                            'keyword4' => round_number($data_user['now_money'],2),
                            'remark' => '',
                            'new_info' => $newInfo),
                            0);
                    }
                }
                if(I("status") == '2'){
                    //发送通知给审核管理员
                    $accept = M("Admin")->where('id='.$info['admin'])->field("openid,nickname")->find();
                    if($accept && $accept['openid']){
                        if ($info['type'] == 1) {
                            //充值
                            $newInfo = [
                                'tempKey' => '45572',
                                'thing20' => '平台余额账户',
                                'amount3' => $info['money'],
                                'amount15' => getFormatNumber($data_user['now_money']),
                                'time2' => date('Y-m-d H:i:s')
                            ];
                        } else {
                            //扣除
                            $newInfo = [
                                'tempKey' => '55638',
                                'thing6' => '平台余额账户',
                                'amount2' => $info['money'],
                                'amount4' => getFormatNumber($data_user['now_money']),
                                'time1' => date('Y-m-d H:i:s')
                            ];
                        }
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM401833445', array('href' => '',
                            'wecha_id' => $accept['openid'],
                            'first' => '尊敬的' . $accept['nickname'] . ',您提交的余额变更被拒绝',
                            'keyword1' => date('Y-m-d H:i'),
                            'keyword2' => '管理员手动' . ($info['type'] == 1 ? '充值' : '减少') . '平台余额（备注：' . $info['remark'] . '）',
                            'keyword3' => ($info['type'] == 1 ? '+' : '-') . $info['money'],
                            'keyword4' => round_number($data_user['now_money'],2),
                            'remark' => '',
                            'new_info' => $newInfo),
                            0);
                    }
                }

                
                $this->success('操作成功');
                exit;
            }
            else{
                $this->error('操作失败');
                exit;
            }
        }
        $this->display();
    }

    //特权用户（哈客一期要做的需求）
    public function privileged_user(){
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition_user['u.uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

        $condition_user['u.openid'] = array('notlike', '%no_use');
        
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                die('区域管理员无法管理用户');
            }elseif($now_area['area_type']==2){
                $area_index = 'u.city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'u.province_id';
            }
            $this->assign('admin_area',$now_area['area_type']);
            $condition_user[$area_index] = $this->system_session['area_id'];
        }
        if($_GET['province_idss'] && $this->config['many_city']){
            $condition_user['u.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $condition_user['u.city_id'] = $_GET['city_idss'];
        }

        //状态
        $condition_user['u.status'] = array('neq', 4);
        $condition_user['up.status'] = array('eq', 1);

        $database_user = M('User_privilege');
        $count_user = $database_user->alias("up")->join("left join ".C("DB_PREFIX")."user as u on u.uid = up.uid")->where($condition_user)->count();
        import('@.ORG.system_page');
        $p = new Page($count_user, 30);
        $user_list = $database_user->alias("up")->join("left join ".C("DB_PREFIX")."user as u on u.uid = up.uid")->field("u.*,up.*")->where($condition_user)->limit($p->firstRow . ',' . $p->listRows)->select();
        
        if (!empty($user_list)) {
            $user_city_ids = array_filter(array_unique(array_column($user_list,'city_id')));
            $user_city_arr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$user_city_ids]])->select(),'area_name','area_id');
            $type_arr = [
                'week' => '周卡'
            ];
            foreach ($user_list as &$value) {
                $value['city_name'] = $user_city_arr[$value['city_id']];
                $value['type'] = $type_arr[$value['type']] ? : $value['type'];
            }
        }
        
        $this->assign('user_list', $user_list);

        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    //导入特权用户
    public function privileged_import(){
        $this->display();
    }

    //上传文件
    public function privileged_upload(){
        set_time_limit(0);

        import('ORG.Util.Dir');
        Dir::delDirnotself('./upload/excel/privileged');
        $upload_dir = './upload/excel/privileged/' . date('Ymd') . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $lang_model = D('Lang');
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();
        $upload->maxSize = 10 * 1024 * 1024;
        $upload->allowExts = array('xls');
        $upload->savePath = $upload_dir;
        $upload->saveRule = 'uniqid';
        if ($upload->upload()){
            $upload_info = $upload->getUploadFileInfo();
            import('@.ORG.phpexcel.PHPExcel','','.php');
            $objPHPExcel = new PHPExcel();
            $path = $upload_info[0]['savepath'].$upload_info[0]['savename'];
            $fileType = PHPExcel_IOFactory::identify($path);
            $objReader = PHPExcel_IOFactory::createReader($fileType);
            $objPHPExcel = $objReader->load($path);
            $currentSheet = $objPHPExcel->getSheet(0);

            $highestRow = $currentSheet->getHighestRow(); // 取得总行数
            $highestColumm = $currentSheet->getHighestColumn(); // 取得总列数
            $data = array();
            $stop = false;
            for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {        //循环读取每个单元格的内容。注意行从1开始，列从A开始
                if(!$stop){
                    for ($colIndex = 'A'; $colIndex <= $highestColumm; $colIndex++) {
                        $addr = $colIndex . $rowIndex;
                        $cell = trim($currentSheet->getCell($addr)->getValue());
                        if($colIndex == 'A' && empty($cell)){
                            $stop = true;
                            break;
                        }
                        if ($cell instanceof PHPExcel_RichText) { //富文本转换字符串
                            $cell = $cell->__toString();
                        }
                        $data[$rowIndex][$colIndex] = trim($cell);
                    }
                }
                else{
                    break;
                }
            }
            $title = array_filter($data[1]);//表格标题
            //验证标题的正确性
            $must = ['A'=>'省份', 'B'=>'城市', 'C'=>'手机号码'];
            if(!empty(array_diff_assoc($title,$must))){
                $this->ajaxReturn(array(),'表格标题不正确！无法执行导入',2);
            }
            $file_name = md5($path);
            array_shift($data);

            foreach($data as $key => $value){
                if(empty($value['A']) || empty($value['B']) || empty($value['C'])){
                    unset($data[$key]);
                }
            }
            $count = count(array_values($data));
            file_put_contents($upload->savePath.$file_name.".php", '<?php return \''.serialize($data).'\'; ?>');
            
            $this->ajaxReturn(array('count'=>$count,'filename'=>$upload->savePath.$file_name.".php"),"上传成功",1);
        }
        else{
            $this->ajaxReturn(array(),$upload->getErrorMsg(),2);
        }
    }

    //ajax 轮询导入
    public function ajaxPrivileged(){
        $filename = I("filename");
        $start = intval(I("start"));
        $data = require $filename;
        $data = unserialize($data);
        $loop = 100;
        $i = 0;
        $yes = 1;
        $end = $start;
        $area_db = D("Area");
        $user_db = D("User");
        $user_privilege_db = M("User_privilege");
        if(!empty($data)){
            $column = array_column($data, 'A');
            $column_data1 = $area_db->where(['area_name' => ['in', $column], 'area_type'=>1])->field("area_id,area_name")->select();
            $column = array_column($data, 'B');
            $column_data2 = $area_db->where(['area_name' => ['in', $column], 'area_type'=>2])->field("area_id,area_name")->select();
            $city = array_merge(array_column($column_data1, 'area_id', 'area_name'),array_column($column_data2, 'area_id', 'area_name'));

            $phone_column = array_column($data, 'C');
            $column_phone_data = $user_db->where(['status'=>1,'phone' => ['in', $phone_column]])->field("uid,phone")->select();
            $user = array_column($column_phone_data, 'uid', 'phone');
            $complete = 0;
            foreach ($data as $key => $value) {
                if($key >= $start){
                    if($i < $loop){
                        $i++;
                        $end++;
                        
                        $temp = [];
                        $temp['uid'] = $user[$value['C']];
                        $temp['province_id'] = $city[$value['A']];
                        $temp['city_id'] = $city[$value['B']];
                        if(empty($temp['uid']) || empty($temp['province_id']) || empty($temp['city_id'])){
                            continue;
                        }
                        $temp['type'] = 'week';
                        $temp['promotion_from'] = date('Y-m-d H:i:s');
                        $temp['promotion_to'] = date('Y-m-d H:i:s', strtotime('+7 days'));
                        $temp['add_time'] = date('Y-m-d H:i:s');
                        if($user_privilege_db->data($temp)->add()){
                            $complete++;    
                        }
                    }
                    else{
                        $end = $i-1;
                        $yes = $data[$i] ? 2 : 1;
                        break;
                    }
                }
                else{
                    continue;
                }
            }
            $this->ajaxReturn(array('start'=>$start,'end'=>$end,'complete'=>$complete,'yes'=>$yes),'',1);
        }
        else{
            $this->ajaxReturn(array(),'',2);
        }
    }

    public function set_privileged_rule(){
        if(IS_POST){
            $cityid = $_POST["cityid"];
            $city_time = $_POST["city_time"];
            $city_coupon = $_POST["city_coupon"];
            $db = M("User_privileged_rule");
            if(empty($cityid)){
                $this->error('编辑失败！');
                exit;
            }
            foreach ($cityid as $key => $value) {
                $find = $db->where("city_id=".$value)->find();
                if($find){
                    $db->where(['city_id'=>$value])->data(['timeint'=>$city_time[$key], 'coupon_ids'=>implode(',', $city_coupon[$key])])->save();
                }
                else{
                    $db->data(['city_id'=>$key, 'timeint'=>$city_time[$key], 'coupon_ids'=>implode(',', $city_coupon[$key])])->add();
                }
            }
            $this->success('编辑成功！');
            exit;
        }
        //搜索
        $where = array();
        if (!empty($_GET['keyword'])) {
            $where['a.area_name'] = ['like','%'.$_GET['keyword'].'%'];
        }
        $where['a.area_type'] = 2;
        $area_db = D("Area");
        $city_data = $area_db->alias("a")->join("left join ".C("DB_PREFIX")."user_privileged_rule as b on a.area_id=b.city_id")->where($where)->field("a.area_id,a.area_name,b.timeint,b.coupon_ids")->select();
        $citys = array_column($city_data, 'area_id');
        $citys[] = 0;//通用的优惠券

        $coupon_db = D("System_coupon");
        $coupons = $coupon_db->where(['city_id'=>['in',$citys],'is_privileged'=>1,'status'=>['neq',4],'end_time'=>['gt',time()]])->field('name,coupon_id,city_id')->select();

        foreach ($city_data as $key => $value) {
            $temp = array_filter(explode(',', $value['coupon_ids']));
            $city_data[$key]['timeint'] = is_null($value['timeint']) ? -1 : $value['timeint'];
            $city_data[$key]['coupon_arr'] = [];
            foreach($coupons as $c){
                if($value['area_id'] == $c['city_id'] || $c['city_id'] == '0'){
                    if(in_array($c['coupon_id'],$temp)){
                        $c['checked'] = 'checked="checked"';
                    }
                    $city_data[$key]['coupon_arr'][] = $c;
                }
            }

        }

        //时间
        $jg = 30;
        $timearr = [
            [
                'val' => -1,
                'text' => '不派发'
            ]
        ];
        for($i = 0 ; $i < 1440 ;){
            $timearr[] = [
                'val' => $i,
                'text' => $this->dealTime($i)
            ];
            $i += 30;
        }

        $this->assign('timearr', $timearr);
        $this->assign('city_data', $city_data);
        $this->display();
    }

    //传入数字返回时间， 如：传入210，返回 03:30;
    private function dealTime($num){
        $h = floor($num/60);
        $s = $num%60;
        return ($h < 10 ? '0'.$h : $h).":".($s < 10 ? '0'.$s : $s);
    }

    /*用户标签*/
    public function label_index()
    {
        $where = array();
        $name = I('name', '', 'trim');
        if (!empty($name)) {
            $where['name'] = array('like', '%' . $name . '%');
        }
        $where['is_del'] = 0;
        $count = M('user_label')->where($where)->count(1);
        import('@.ORG.system_page');
        $page = new Page($count, 30);
        $list = M('User_label')->where($where)->limit($page->firstRow, $page->listRows)->select();
        foreach ($list as &$value) {
            $value['user_num'] = M('User_label_bind')->join(' as l left join '.C('DB_PREFIX').'user as u on u.uid=l.uid')->where(array('l.label_id'=>$value['id'],'u.status'=>array('neq',4), '_string'=>'(openid NOT LIKE "%no_use%" OR openid IS NULL)'))->count();
        }
        $this->assign('list', $list);
        $this->assign('pagebar', $page->show());
        $this->display();
    }

    public function label_condition_detail()
    { 
        $id = I('get.id', '', 'intval');
        if (empty($id)) {
            $this->error('缺少参数！');
        }
        $where = array();
        $where['id'] = $id;
        $info = M('User_label')->where($where)->find();
        $info['buy_goods_list'] = unserialize($info['buy_goods_list']);
        $info['buy_goods_list_str'] = '';
        if(!empty($info['buy_goods_list'])){
            $condition = array();
            $database = null;
            $mallgoods_list = array();
            $groupgoods_list = array();
            $shopgoods_list = array();
            $result = array();
            $temp = array();
            if($info['buy_goods_list']['mall']){//商城商品
                $database = D(_view('Shop_goods'));
                $condition['goods_id']=array('in',$info['buy_goods_list']['mall']);
                $field = 'goods_id, name, last_time, price';
                $mallgoods_list = $database->field($field)->where($condition)->select();
                foreach ($mallgoods_list as $key => $value) {
                    $mallgoods_list[$key]['model'] = '商城';
                    $mallgoods_list[$key]['type'] = 'mall';
                    $result[] = $mallgoods_list[$key];
                    $temp[] = 'mall'.'-'.$value['goods_id'];
                }
            }
            if($info['buy_goods_list']['shop']){
                $database = D('Shop_goods');
                $condition['goods_id']=array('in',$info['buy_goods_list']['shop']);
                $field = 'goods_id, name, last_time, price';
                $shopgoods_list = $database->field($field)->where($condition)->select();
                foreach ($shopgoods_list as $key => $value) {
                    $shopgoods_list[$key]['model'] = '外卖';
                    $shopgoods_list[$key]['type'] = 'shop';
                    $result[] = $shopgoods_list[$key];
                    $temp[] = 'shop'.'-'.$value['goods_id'];
                }
            }
            if($info['buy_goods_list']['group']){
                $database = D('Group');
                $condition = array('group_id'=>array('in', $info['buy_goods_list']['group']));
                $field = 'group_id as goods_id, name, last_time, price';
                $groupgoods_list = $database->field($field)->where($condition)->select();
                foreach ($groupgoods_list as $key => $value) {
                    $groupgoods_list[$key]['model'] = '团购';
                    $groupgoods_list[$key]['type'] = 'group';
                    $result[] = $groupgoods_list[$key];
                    $temp[] = 'mall'.'-'.$value['goods_id'];
                    $temp[] = 'group'.'-'.$value['goods_id'];
                }
            }
            $info['buy_goods_list'] = $result;
            $info['buy_goods_list_str'] = implode(',', $temp);
        }
        $this->assign('detail', $info);
        $this->display();
    }

    // 初始化标签，将原来的标签写入新的数据库
    public function label_init()
    {
        $limit = 0;
        $user_list = M('User')->field('label_id,uid')->where(array('label_id'=>array('gt',0)))->limit($limit,1000)->select();
        while ( $user_list ) {
            $add = array();
            foreach ($user_list as $key => $value) {
                if (is_numeric($value['label_id'])) {
                    $add[] = array(
                        'uid' => $value['uid'],
                        'label_id' => $value['label_id'],
                    );
                }
            }
            M('User_label_bind')->addAll($add);
            $limit += 1000;
            $user_list = M('User')->field('label_id,uid')->where(array('label_id'=>array('gt',0)))->limit($limit,1000)->select();
            echo 'success';
        }
    }


    public function label_edit()
    {
        $id = I('id', '', 'intval');
        $model = M('user_label');
        if (IS_POST) {
            $data = $_POST;
            // 去除其他被设置为默认的标签
            if ($data['is_default'] == 1) {
                $model->where(array('is_default' => 1))->save(array('is_default' => 0));
            }

            $_POST['buy_goods_list'] = '';
            $buy_goods_list = array();
            $relation_goods_id = $_POST['relation_goods_id'];
            $relation_type = $_POST['relation_type'];
            foreach ($relation_goods_id as $key => $value) {
                $buy_goods_list[$relation_type[$key]][] = $value;
            }
            $data['buy_goods_list'] = serialize($buy_goods_list);

            if ($data['behavior_start_time']) {
                $data['behavior_start_time'] = strtotime($data['behavior_start_time']);
            }  if ($data['behavior_end_time']) {
                $data['behavior_end_time'] = strtotime($data['behavior_end_time']);
            }

            if ($id > 0) {
                $res = $model->data($data)->save();
                if ($res !== false) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('抱歉，编辑失败，请重试！');
                }
            } else {
                $data['addtime'] = time();
                $res = $model->data($data)->add();
                if ($res) {
                    $this->success('添加成功！');
                } else {
                    $this->error('抱歉，添加失败，请重试！');
                }
            }
        } else {
            if (!empty($id)) {
                $where = array();
                $where['id'] = $id;
                $info = $model->where($where)->find();
                $info['buy_goods_list'] = unserialize($info['buy_goods_list']);
                $info['buy_goods_list_str'] = '';
                if(!empty($info['buy_goods_list'])){
                    $condition = array();
                    $database = null;
                    $mallgoods_list = array();
                    $groupgoods_list = array();
                    $shopgoods_list = array();
                    $result = array();
                    $temp = array();
                    if($info['buy_goods_list']['mall']){//商城商品
                        $database = D(_view('Shop_goods'));
                        $condition['goods_id']=array('in',$info['buy_goods_list']['mall']);
                        $field = 'goods_id, name, last_time, price';
                        $mallgoods_list = $database->field($field)->where($condition)->select();
                        foreach ($mallgoods_list as $key => $value) {
                            $mallgoods_list[$key]['model'] = '商城';
                            $mallgoods_list[$key]['type'] = 'mall';
                            $result[] = $mallgoods_list[$key];
                            $temp[] = 'mall'.'-'.$value['goods_id'];
                        }
                    }
                    if($info['buy_goods_list']['shop']){
                        $database = D(_view('Shop_goods'));
                        $condition['goods_id']=array('in',$info['buy_goods_list']['shop']);
                        $field = 'goods_id, name, last_time, price';
                        $shopgoods_list = $database->field($field)->where($condition)->select();
                        foreach ($shopgoods_list as $key => $value) {
                            $shopgoods_list[$key]['model'] = '外卖';
                            $shopgoods_list[$key]['type'] = 'shop';
                            $result[] = $shopgoods_list[$key];
                            $temp[] = 'shop'.'-'.$value['goods_id'];
                        }
                    }
                    if($info['buy_goods_list']['group']){
                        $database = D('Group');
                        $condition = array('group_id'=>array('in', $info['buy_goods_list']['group']));
                        $field = 'group_id as goods_id, name, last_time, price';
                        $groupgoods_list = $database->field($field)->where($condition)->select();
                        foreach ($groupgoods_list as $key => $value) {
                            $groupgoods_list[$key]['model'] = '团购';
                            $groupgoods_list[$key]['type'] = 'group';
                            $result[] = $groupgoods_list[$key];
                            $temp[] = 'mall'.'-'.$value['goods_id'];
                            $temp[] = 'group'.'-'.$value['goods_id'];
                        }
                    }
                    $info['buy_goods_list'] = $result;
                    $info['buy_goods_list_str'] = implode(',', $temp);
                }

                $this->assign('info', $info);
            }
            $this->display();
        }
    }

    public function label_del()
    {
        $id = I('get.id', '', 'intval');
        if (empty($id)) {
            $this->error('缺少参数！');
        }
        $where = array();
        $where['label_id'] = $id;
        $count = M('user')->where($where)->count(1);
        if ($count > 0) {
            $this->error('该标签下存在用户，暂不可删除！');
        }
        $data = array();
        $data['id'] = $id;
        $data['is_del'] = 1;
        $res = M('user_label')->data($data)->save();
        if ($res) {
            $this->success('操作成功！');
        } else {
            $this->error('抱歉，操作失败，请重试！');
        }
    }

    public function label_bind_user_list()
    {
        $this->assign('bg_color', '#F3F3F3');
        $database_user_label_bind = D('User_label_bind');
        $condition_user['l.label_id'] = intval($_GET['id']);
         //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition_user['u.uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

        $condition_user['_string'] = '(u.openid NOT LIKE "%no_use%" OR u.openid IS NULL)';
        //排序
        $order_string = 'u.`uid` DESC';
        if ($_GET['sort']) {
            switch ($_GET['sort']) {
                case 'uid':
                    $order_string = 'u.`uid` DESC';
                    break;
                case 'lastTime':
                    $order_string = 'u.`last_time` DESC';
                    break;
                case 'money':
                    $order_string = 'u.`now_money` DESC';
                    break;
                case 'score':
                    $order_string = 'u.`score_count` DESC';
                    break;
            }
        }
        $levelDb = M(_view('User_level'));
        $tmparr = $levelDb->field(true)->order('id ASC')->select();
        $levelarr = array();
        if ($tmparr) {
            foreach ($tmparr as $vv) {
                $levelarr[$vv['level']] = $vv;
            }
        }
        $this->assign('levelarr', $levelarr);
        
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                die('区域管理员无法管理用户');
            }elseif($now_area['area_type']==2){
                $area_index = 'u.city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'u.province_id';
            }
            $this->assign('admin_area',$now_area['area_type']);
            $condition_user[$area_index] = $this->system_session['area_id'];
        }
        if($_GET['province_idss'] && $this->config['many_city']){
            $condition_user['u.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $condition_user['u.city_id'] = $_GET['city_idss'];
        }

        //状态
        $condition_user['status'] = array('neq', 4);
        if ($_GET['status'] != '') {
            $condition_user['u.status'] = $_GET['status'];
        }
        if ($_GET['level'] > 0) {
            $condition_user['u.level'] = $_GET['level'];
        }
        if ($_GET['label_id'] > 0) {
            $condition_user['u.label_id'] = $_GET['label_id'];
        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_user['_string'] = " (u.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }

        $count = $database_user_label_bind->join('as l left join '.C('DB_PREFIX').'user as u on l.uid = u.uid')->where($condition_user)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $user_list = $database_user_label_bind->field('l.*,u.*')->join('as l left join '.C('DB_PREFIX').'user as u on l.uid = u.uid')->where($condition_user)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

        if (!empty($user_list)) {
            $user_city_ids = array_filter(array_unique(array_column($user_list,'city_id')));
            $user_city_arr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$user_city_ids]])->select(),'area_name','area_id');
            foreach ($user_list as &$value) {
                $value['city_name'] = $user_city_arr[$value['city_id']];
                $value['last_ip_txt'] = $value['last_ip'];
            }
        }
        $this->assign('pagebar', $p->show());
        $this->assign('user_list', $user_list);
        $this->display();
    }

    public function select_goods(){
        $type = I("type") ? : 'mall';//默认商城
        $this->assign('type', $type);

        $selected = trim(I("selected"),',');
        $this->assign("selected", $selected);

        $type_arr = array();
        if($selected){
            $selected_arr = explode(',', $selected);
            foreach ($selected_arr as $value) {
                $temp = explode('-', $value);
                $type_arr[$temp[0]][] = $temp[1];
            }
        }

        import('@.ORG.diypage');
        $condition = array();
        $database = null;
        if($type == 'mall'){//商城商品
            $database = D(_view('Shop_goods'));
            $condition['g.cat_fid']=array('gt',0);
            if($_GET['keyword']){
                $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'g.goods_id, g.name, g.last_time, g.price';
            $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        elseif($type == 'shop'){
            $database = D(_view('Shop_goods'));
            $condition['g.goods_type']=array('neq',1);
            if($_GET['keyword']){
                $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'g.goods_id, g.name, g.last_time, g.price';
            $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        elseif($type == 'group'){
            $database = D('Group');
            if($_GET['keyword']){
                $condition['name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'group_id as goods_id, name, last_time, price';
            $count = $database->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }

        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['select'] = 0;
            if(in_array($value['goods_id'], $type_arr[$type])){
                $goods_list[$key]['select'] = 1;
            }
        }

        $this->assign("keyword", $_GET['keyword']);
        $this->assign("good_list", $goods_list);
        $this->assign("pagebar", $p->show());

        $this->display();
    }
    public function ajaxGetGoods(){
        $selected = trim(I("selected"),',');

        $type_arr = array();
        if($selected){
            $selected_arr = explode(',', $selected);
            foreach ($selected_arr as $value) {
                $temp = explode('-', $value);
                $type_arr[$temp[0]][] = $temp[1];
            }
        }

        $condition = array();
        $database = null;
        $mallgoods_list = array();
        $groupgoods_list = array();
        $shopgoods_list = array();
        $result = array();
        if($type_arr['mall']){//商城商品
            $database = D(_view('Shop_goods'));
            $condition['goods_id']=array('in',$type_arr['mall']);
            $field = 'goods_id, name, last_time, price';
            $mallgoods_list = $database->field($field)->where($condition)->select();
            foreach ($mallgoods_list as $key => $value) {
                $mallgoods_list[$key]['model'] = '商城';
                $mallgoods_list[$key]['type'] = 'mall';
                $result[] = $mallgoods_list[$key];
            }
        }
        if($type_arr['shop']){
            $database = D(_view('Shop_goods'));
            $condition['goods_id']=array('in',$type_arr['shop']);
            $field = 'goods_id, name, last_time, price';
            $shopgoods_list = $database->field($field)->where($condition)->select();
            foreach ($shopgoods_list as $key => $value) {
                $shopgoods_list[$key]['model'] = '外卖';
                $shopgoods_list[$key]['type'] = 'shop';
                $result[] = $shopgoods_list[$key];
            }
        }
        if($type_arr['group']){
            $database = D('Group');
            $condition = array('group_id'=>array('in', $type_arr['group']));
            $field = 'group_id as goods_id, name, last_time, price';
            $groupgoods_list = $database->field($field)->where($condition)->select();
            foreach ($groupgoods_list as $key => $value) {
                $groupgoods_list[$key]['model'] = '团购';
                $groupgoods_list[$key]['type'] = 'group';
                $result[] = $groupgoods_list[$key];
            }
        }
        echo json_encode($result);
        exit;
    }
    public function user_spread_list()
    {
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition_user['uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

        $condition_user['openid'] = array('notlike', '%no_use');
        //排序
        $order_string = '`uid` DESC';
        if ($_GET['sort']) {
            switch ($_GET['sort']) {
                case 'uid':
                    $order_string = '`uid` DESC';
                    break;
                case 'lastTime':
                    $order_string = '`last_time` DESC';
                    break;
                case 'money':
                    $order_string = '`now_money` DESC';
                    break;
                case 'score':
                    $order_string = '`score_count` DESC';
                    break;
            }
        }
        $levelDb = M('User_level');
        $tmparr = M(_view('User_level'))->field(true)->order('id ASC')->select();
        $levelarr = array();
        if ($tmparr) {
            foreach ($tmparr as $vv) {
                $levelarr[$vv['level']] = $vv;
            }
        }

        //状态
        $condition_user['openid'] = array('neq', '');
        $condition_user['status'] = array('neq', 4);
        if ($_GET['status'] != '') {
            $condition_user['status'] = $_GET['status'];
        }
        if ($_GET['level'] > 0) {
            $condition_user['level'] = $_GET['level'];
        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_user['_string'] = " (add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $database_user = D('User');
        $count_user = $database_user->where($condition_user)->count();
        import('@.ORG.system_page');
        $p = new Page($count_user, 30);
        $user_list = $database_user->field(true)->where($condition_user)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        if (!empty($user_list)) {
            // import('ORG.Net.IpLocation');
            // $IpLocation = new IpLocation();
            // foreach ($user_list as &$value) {
            //     $last_location = $IpLocation->getlocation($value['last_ip']);
            //     $value['last_ip_txt'] = mb_convert_encoding($last_location['country'], 'UTF-8', 'GBK');
            // }
        }
        $this->assign('user_list', $user_list);
        $database_user_spread_list = M('User_spread_list');
        $user_balance = array(
            'count' => $database_user_spread_list->where(array('order_type' => 'user_s_mer', 'spread_mer_id' => array('gt', 0)))->sum('money'),
            'open' => $database_user_spread_list->alias('us')->join('inner join ' . C('DB_PREFIX') . 'user as u on u.uid = us.uid')->where(array('u.status' => 1, 'us.order_type' => 'user_s_mer', 'us.spread_mer_id' => array('gt', 0)))->sum('us.money'),
            'close' => $database_user_spread_list->alias('us')->join('inner join ' . C('DB_PREFIX') . 'user as u on u.uid = us.uid')->where(array('u.status' => 0, 'us.order_type' => 'user_s_mer', 'us.spread_mer_id' => array('gt', 0)))->sum('us.money')
        );
        $this->assign('levelarr', $levelarr);
        $this->assign('user_balance', $user_balance);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('client', array(0 => 'WAP端', 1 => '苹果', 2 => '安卓', 3 => '电脑', 4 => '小程序', 5 => '微信', 6 => '支付宝', '7' => '微信扫描商家二维码'));
        $this->assign('spread_check', C('config.open_c2b_spread_check'));
		$this->display();
    }
	
	    public function user_check_list(){
        $model = D('User');
        $where = array();
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $where['uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $where['nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $where['phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        $where['openid'] = array('notlike', '%no_use');
        $where['can_c2b_spread'] = array('gt', 0);
        $field = 'uid,nickname,phone,can_c2b_spread,can_c2b_spread_reply_time';
        $order = 'can_c2b_spread_reply_time desc,uid desc';
        $count_user = $model->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count_user, 30);
        $user_list = $model->field(true)->where($where)->order($order)->field($field)->limit($p->firstRow . ',' . $p->listRows)->select();
        if(!empty($user_list)){
            foreach($user_list as $key => $val){
                $user_list[$key]['reply_time'] = $val['can_c2b_spread_reply_time']?date('Y-m-d H:i:s',$val['can_c2b_spread_reply_time']):'--';
            }
        }

        $this->assign('pagebar', $p->show());
        $this->assign('user_list', $user_list);
        $this->display();

    }


    public function can_spread(){
        $type=$_POST['type'];//1通过，2拒绝
        $uid =$_POST['uid'];
        $data = array();
        if($type==2){
            $data['can_c2b_spread'] = 3;
        }else{
            $data['can_c2b_spread'] = 2;
        }
        $r = D('User')->where(array('uid'=>$uid))->save($data);
        if($r){
            $this->success('操作成功!');
        } else {
            $this->error("操作失败！");
        }
    }

    public function spread_user()
    {
        $openid = $_GET['openid'];
        $uid = $_GET['uid'];
        if (!empty($_GET['keyword']) || !empty($_GET['searchtype']) || !empty($_GET['begin_time']) || !empty($_GET['end_time']) || isset($_GET['status'])) {
            if ($_GET['searchtype'] == 'uid') {
                if($_GET['keyword']>0){
                    $condition_user['u.uid'] = $_GET['keyword'];
                }
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'school_no') {
                $condition_user['u.school_no'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'school_sfz') {
                $condition_user['u.school_sfz'] = array('like', '%' . $_GET['keyword'] . '%');
            }
            $order_string = 'u.uid DESC';
            if ($_GET['sort']) {
                switch ($_GET['sort']) {
                    case 'uid':
                        $order_string = '`u.uid` DESC';
                        break;
                    case 'lastTime':
                        $order_string = '`u.last_time` DESC';
                        break;
                    case 'money':
                        $order_string = '`u.now_money` DESC';
                        break;
                    case 'score':
                        $order_string = '`u.score_count` DESC';
                        break;
                }
            }
            $levelDb = M(_view('User_level'));
            $tmparr = $levelDb->field(true)->order('id ASC')->select();
            $levelarr = array();
            if ($tmparr) {
                foreach ($tmparr as $vv) {
                    $levelarr[$vv['level']] = $vv;
                }
            }

            if ($this->system_session['area_id']) {
                $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if($now_area['area_type']==3){
                    die('区域管理员无法管理用户');
                }elseif($now_area['area_type']==2){
                    $area_index = 'u.city_id';
                }elseif($now_area['area_type']==1){
                    $area_index = 'u.province_id';
                }
                $this->assign('admin_area',$now_area['area_type']);
                $condition_user[$area_index] = $this->system_session['area_id'];
            }else{
                if($_GET['province_idss']){
                    $condition_user['u.province_id'] = $_GET['province_idss'];
                }
                if($_GET['city_idss']){
                    $condition_user['u.city_id'] = $_GET['city_idss'];
                }
            }


//        $condition_user['openid'] = array('notlike', '%no_use%');
            // $condition_user['_string'] = '(openid NOT LIKE "%no_use%" OR openid IS NULL)';

            //状态
            $condition_user['u.status'] = array('neq', 4);
            if ($_GET['status'] != '') {
                $condition_user['u.status'] = $_GET['status'];
            }
            if ($_GET['level'] > 0) {
                $condition_user['u.level'] = $_GET['level'];
            }
            if ($_GET['label_id'] > 0) {
                $label_list = M('User_label_bind')->where(array('label_id'=>$_GET['label_id']))->select();
                $uids = array();
                foreach ($label_list as $key => $value) {
                    $uids[] =  $value['uid'];
                }
                $condition_user['_string'] .= ' AND (u.uid in('.implode(',',$uids).') OR u.label_id='.$_GET['label_id'].')';
            }

            if(!empty($_GET['begin_time'])){
                $condition_user['u.add_time'][]=['egt',strtotime($_GET['begin_time'])];
            }
            if(!empty($_GET['end_time'])){
                $condition_user['u.add_time'][]=['lt',strtotime($_GET['end_time'])+86400];
            }
            //是否具有分佣功能
            if ($_GET['can_spread'] != '') {
                $condition_user['u.can_spread'] = $_GET['can_spread'];
            }
            if(C('config.open_school') == '1'){
                $condition_user['u.school_type'] = $_GET['school_type'] == '1' ? 0 : 1;
            }

            $condition_user['us.spread_uid'] =  array('eq',$uid);
            $count_user = count(D('User_spread')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where($condition_user)->group("us.uid")->select());

            /*$uid_arr = [];
            if($spred_list){
                foreach($spred_list as $item){
                    $uid_arr[] = $item['uid'];
                }
            }*/
            $database_user = D('User');
            //$count_user = $database_user->where($condition_user)->where([['uid','in',$uid_arr]])->count();
            import('@.ORG.system_page');
            $p = new Page($count_user, 20);

            //$user_list = $database_user->field(true)->where($condition_user)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
            //fdump($database_user->getLastSql(),"ddddddrrrrrfffffff8888888888888888888888888");
            //dump($order_string);exit();
            $user_list = D('User_spread')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where($condition_user)->field('*,u.add_time AS register_time')->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->group("us.uid")->select();
            if (!empty($user_list)) {
                $user_city_ids = array_filter(array_unique(array_column($user_list,'city_id')));
                $user_city_arr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$user_city_ids]])->select(),'area_name','area_id');
                foreach ($user_list as &$uv) {
                    $uv['city_name'] = $user_city_arr[$uv['city_id']];
                    $uv['last_ip_txt'] = is_numeric($uv['last_ip']) ? long2ip($uv['last_ip']) : $uv['last_ip'];
                }
                $spreads = array_column($user_list, 'uid');
                $where_s = ['us.spread_uid'=>['in', $spreads]];
                $all = D('User_spread')->field('`us`.`uid`,us.spread_uid')->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->where($where_s)->group('us.uid')->select();

                $spreaduids = array_column($user_list, 'uid');
                $all_list = M('User_spread_list')->where(array('get_uid'=>['in', $spreaduids],'uid'=>$uid))->select();

                foreach($user_list as &$v){
                    // if($v['uid'] && !$v['user_uid']){
                    //     $this->where(array('pigcms_id'=>$v['spread_id']))->data(array('uid'=>$v['uid']))->save();
                    // }
                    $spread_count = $spread_money = 0;
                    foreach ($all as $key => $value) {
                        if($value['spread_uid'] == $v['uid']){
                            $spread_count++;
                        }
                    }
                    foreach ($all_list as $kk => $vv) {
                        if($vv['get_uid'] == $v['uid']){
                            $spread_money += $vv['money'];
                        }
                    }

                    $v['spread_count'] = $spread_count;
                    $v['spread_money'] = $spread_money;
                }
            }

            $this->assign('user_list', $user_list);

            $user_balance = array(
                'count' => $database_user->sum('now_money'),
                'open' => $database_user->where(array('status' => 1))->sum('now_money'),
                'close' => $database_user->where(array('status' => 0))->sum('now_money'),
                'score' => $database_user->where(array('status' => 1))->sum('score_count'),
                'recharge_spread_money' => M('User_spread_list')->where(array('order_type' => 'recharge', 'status' => 1))->sum('money'),
            );
            $this->assign('levelarr', $levelarr);
            $this->assign('user_balance', $user_balance);
            //用户标签
            $labelarr = M('user_label')->where(array('is_del' => 0))->order('id asc')->getField('id,name,is_default');
            $this->assign('labelarr', $labelarr);
            $pagebar = $p->show();
            $this->assign('pagebar', $pagebar);
            $this->assign('client', array(0 => 'WAP端', 1 => '苹果', 2 => '安卓', 3 => '电脑', 4 => '小程序', 5 => '微信', 6 => '支付宝', '7' => '微信扫描商家二维码'));

            $export = 1;//导出列表的权限,只给超级管理员
            if ($this->system_session['level'] < 2) {
                $export = 0;

            }
            $this->assign('page', $p->show());
            $this->assign('uid',$uid);
            $this->assign('openid',$openid);
            $this->assign('export',$export);
            $this->display();
        }else{
            if (empty($_GET['from_uid'])) {
                $_GET['from_uid'] = $_GET['uid'];
            }
            !isset($_GET['lvl']) && $_GET['lvl'] = 0;
            $_GET['lvl'] += 1;
            if ($_GET['lvl'] > 3) {
                $this->error_tips('平台最多支持3级分佣！');
            }
            $where['_string'] =  "us.spread_uid='$uid'";
            $count = count(D('User_spread')->where($where)->join('as us inner join '.C('DB_PREFIX').'user u ON us.uid  = u.uid')->group("us.uid")->select());
            import('@.ORG.system_page');
            $p = new Page($count, 20);
            $now_user = D('User')->get_user($_GET['from_uid']);

            //修复当前用户的公众号扫码推广关系
            if($now_user['openid']){
                D('User_spread')->updateUid($now_user['openid'], $now_user['uid']);
            }
            $repair_where = ['spread_uid' => $now_user['uid']];
            $repair_where['_string'] = '`uid` IS NULL';
            $spred_list = D('User_spread')->where($repair_where)->select();
            if($spred_list){
                foreach($spred_list as $value){
                    $tmp_user = D('User')->get_user($value['openid'], 'openid');
                    if($tmp_user){
                        D('User_spread')->updateUid($tmp_user['openid'], $tmp_user['uid']);
                    }
                }
            }
            $user_list = D('User_spread')->get_spread_user($openid, $uid, $p->firstRow , $p->listRows);
            if (!empty($user_list)) {
                // import('ORG.Net.IpLocation');
                // $IpLocation = new IpLocation();
                foreach ($user_list['spread_user_list'] as $key => &$value) {
                    // $last_location = $IpLocation->getlocation($value['last_ip']);
                    // $value['last_ip_txt'] = iconv('GBK', 'UTF-8', $last_location['country']);
                    // if ($value['openid'] == '') {
                    //     unset($user_list['spread_user_list'][$key]);
                    // }
                }
            }
            $this->assign('openid',$openid);
            $this->assign('uid',$uid);
            $this->assign('user_list', $user_list['spread_user_list']);
            $this->assign('page', $p->show());
            $this->assign('client', array(0 => 'WAP端', 1 => '苹果', 2 => '安卓', 3 => '电脑/暂时未知', 4 => '小程序', 5 => '微信'));
            $this->assign('now_user', $now_user);
            $this->display();
        }
    }

    public function spread_higher_user()
    {
        $uid = $_GET['uid'];
        $openid = $_GET['openid'];
        if (empty($_GET['from_uid'])) {
            $_GET['from_uid'] = $_GET['uid'];
        }
        !isset($_GET['lvl']) && $_GET['lvl'] = 0;
        $_GET['lvl'] += 1;
        if ($_GET['lvl'] > 3) {
            $this->error_tips('平台最多支持3级分佣！');
        }
        $now_user = D('User')->get_user($_GET['from_uid']);

		//修复当前用户的公众号扫码推广关系
		if($now_user['openid']){
			D('User_spread')->updateUid($now_user['openid'], $now_user['uid']);
		}
		
        $user = D('User_spread')->get_spread_higher_user($openid, $uid, $now_user['wxapp_openid']);
        if (!empty($user) && ($user['openid'] || $user['wxapp_openid'])) {
            import('ORG.Net.IpLocation');
            $IpLocation = new IpLocation();
            $last_location = $IpLocation->getlocation($user['last_ip']);
            $user['last_ip_txt'] = iconv('GBK', 'UTF-8', $last_location['country']);
            $user_list[] = $user;
        } else {
            $user_list = [];
        }
        $this->assign('user_list', $user_list);
        $this->assign('client', array(0 => 'WAP端', 1 => '苹果', 2 => '安卓', 3 => '电脑/暂时未知', 4 => '小程序', 5 => '微信'));
        $this->assign('now_user', $now_user);
        $this->display();
    }

    public function spread_user_mer()
    {
        $uid = $_GET['uid'];
        $openid = $_GET['openid'];
        if (empty($_GET['from_uid'])) {
            $_GET['from_uid'] = $_GET['uid'];
        }
        !isset($_GET['lvl']) && $_GET['lvl'] = 0;
        $_GET['lvl'] += 1;
        if ($_GET['lvl'] > 2) {
            $this->error_tips('商家推广只支持3级分佣！');
        }
        $now_user = D('User')->get_user($_GET['from_uid']);
        if ($_GET['from_uid']) {
            $uid = $_GET['from_uid'];
        }
        $user_list = D('UserSpreadMer')->get_spread_user($openid, $uid);
        if (!empty($user_list)) {
            import('ORG.Net.IpLocation');
            $IpLocation = new IpLocation();
            foreach ($user_list['spread_user_list'] as $key => &$value) {
                $last_location = $IpLocation->getlocation($value['last_ip']);
                $value['last_ip_txt'] = iconv('GBK', 'UTF-8', $last_location['country']);
                if ($value['openid'] == '') {
                    unset($user_list['spread_user_list'][$key]);
                }
            }
        }
        $this->assign('user_list', $user_list['spread_user_list']);
        $this->assign('client', array(0 => 'WAP端', 1 => '苹果', 2 => '安卓', 3 => '电脑/暂时未知', 4 => '小程序', 5 => '微信'));
        $this->assign('now_user', $now_user);
        $this->display();
    }

    public function spread_mer_list()
    {
        $page = $_POST['page'];
        $uid = $_GET['uid'];
        $transaction = D('UserSpreadMer')->get_list($uid, $page, 10, 1);
        $where['uid'] = $uid;
        $this->assign('now_user', M('User')->where(array('uid' => $uid))->find());
        if (!empty($transaction)) {
            import('ORG.Net.IpLocation');
            $IpLocation = new IpLocation();
            foreach ($transaction['mer_list'] as &$value) {
                $last_location = $IpLocation->getlocation($value['last_ip']);
                $value['last_ip_txt'] = mb_convert_encoding($last_location['country'], 'UTF-8', 'GBK');
                $where['spread_mer_id'] = $value['mer_id'];
                $money = floatval(M('User_spread_list')->where($where)->sum('money'));
                $value['time_s'] = date('Y/m/d H:i', $value['reg_time']);
                $value['s_money'] = $money;
            }
        }
        $this->assign('mers', $transaction);
        $this->display();
    }

    public function edit()
    {
        $this->assign('bg_color', '#F3F3F3');
        if ($this->system_session['level'] != 2 && !in_array(198, $this->system_session['menus']) || $this->config['open_allinyun'] == 1) {
            $can_recharge = 0;
        } else {
            $can_recharge = 1;
        }
        $this->assign('can_recharge', $can_recharge);

        $database_user = D('User');
        $condition_user['uid'] = intval($_GET['uid']);
        $now_user = $database_user->field(true)->where($condition_user)->find();

        if (empty($now_user)) {
            $this->frame_error_tips('没有找到该用户信息！');
        }

        $levelDb = M('User_level');
        $tmparr = M(_view('User_level'))->field(true)->order('id ASC')->select();
        $levelarr = array();
        if ($tmparr) {
            foreach ($tmparr as $vv) {
                $levelarr[$vv['level']] = $vv;
            }
        }
        if (!empty($now_user['cardid'])) {
            $balance_money = D('Physical_card')->where(array('cardid' => $now_user['cardid']))->getField('balance_money');
            $this->assign('balance_money', $balance_money);
        }
        if ($now_user['free_time'] > $_SERVER['REQUEST_TIME']) {
            $now_user['frozen_time'] = $now_user['frozen_time'] > 0 ? date('Y-m-d', $now_user['frozen_time']) : 0;
            $now_user['free_time'] = $now_user['free_time'] > 0 ? date('Y-m-d', $now_user['free_time']) : 0;
        } else {
            $now_user['frozen_money'] = 0;
            $now_user['frozen_time'] = 0;
            $now_user['frozen_reason'] = '';
            $now_user['free_time'] = 0;
        }
        $now_user['spread'] = 0;
        if ($now_user['openid']) {
            if ($res = M('Merchant_spread')->where(array('openid' => $now_user['openid']))->find()) {
                $now_user['spread'] = 2;
                $now_user['spread_txt'] = '商家' . $res['mer_id'] . '的推广用户';

            }

            $condition_where['_string'] = "uid=" . $now_user['uid'];
            if ($res = M('User_spread')->where($condition_where)->find()) {
                $now_user['spread'] = 1;
                $now_user['spread_txt'] = '用户' . $res['spread_uid'] . '的推广用户';
            }
        }

        //用户标签已选择
        $label_select = M('User_label_bind')->field('l.id,l.name,l.is_default')->join(' as b left join '.C('DB_PREFIX').'user_label as l on b.label_id=l.id')->where(array('b.uid' => $now_user['uid']))->order('l.id asc')->select();
        if (!$label_select&&$now_user['label_id']) {
            $label_select = M('User_label')->where(array('id'=>array('in',$now_user['label_id'])))->select();
        }

        $card = M('internal_membership_card');
        $user_card_count = $card->where(array('uid' => $now_user['uid']))->count();
        $user_card_balance_count = $card->where(array('uid' => $now_user['uid']))->sum('balance_money');
        $now_user['user_card_count'] = $user_card_count;
        $now_user['user_card_balance_count'] = $user_card_balance_count;

        //用户标签
        $labelarr = M('user_label')->where(array('is_del' => 0))->order('id asc')->getField('id,name,is_default');
        $this->assign('label_select', $label_select);
        $this->assign('labelarr', $labelarr);
        $this->assign('levelarr', $levelarr);
        $this->assign('now_user', $now_user);

        // 获取用户激活码
        $d_user_level_activation_code = D('User_level_activation_code');
        $user_level_activation_code = $d_user_level_activation_code->where(array('uid' => $now_user['uid'], 'status' => array('neq', 4)))->find();
        $this->assign('user_level_activation_code', $user_level_activation_code);
        $this->assign('status_info', $d_user_level_activation_code->status_info);
		
		//修复当前用户的公众号扫码推广关系
		if($now_user['openid']){
			D('User_spread')->updateUid($now_user['openid'], $now_user['uid']);
		}
		$repair_where = ['spread_uid' => $now_user['uid']];
		$repair_where['_string'] = '`uid` IS NULL';
		$spred_list = D('User_spread')->where($repair_where)->select();
		if($spred_list){
			foreach($spred_list as $value){
				$tmp_user = D('User')->get_user($value['openid'], 'openid');
				if($tmp_user){
					D('User_spread')->updateUid($tmp_user['openid'], $tmp_user['uid']);
				}
			}
		}
		
        $this->display();
    }

    //管理员充值导出
    public function admin_recharge_export()
    {
        $param = $_POST;
        $param['type'] = 'admin_recharge';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        // $param['system_session']['area_id'] = $this->system_session['area_id'] ;

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
    }

    //充值订单导出
    public function recharge_export()
    {
        $param = $_POST;
        $param['type'] = 'recharge_order';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        // $param['system_session']['area_id'] = $this->system_session['area_id'] ;

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
    }

    public function export()
    {

        $param = $_POST;
        $param['type'] = 'user';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        // $param['system_session']['area_id'] = $this->system_session['area_id'] ;

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
        die;
        set_time_limit(0);
        import('@.ORG.csv');
        $csv = new csv();

        import('ORG.Net.IpLocation');
        $IpLocation = new IpLocation();

        $header_data = array('用户ID', '昵称', '真实姓名', '手机号', '性别 (男； 女； 其他)', '省份', '城市', 'QQ', '注册时间', '注册IP', '最后登录时间', '最后登录IP', $this->config['score_name'], '余额', '不可提现的余额', '是否手机认证', '是否关注公众号', '账号是否正常');

        $data = array();
        $user_list = D('User')->field(true)->select();
        foreach ($user_list as $value) {
            $last_location = $IpLocation->getlocation($value['add_ip']);
            $add_ip = iconv('GBK', 'UTF-8', $last_location['country']);

            $last_location = $IpLocation->getlocation($value['last_ip']);
            $last_ip = iconv('GBK', 'UTF-8', $last_location['country']);

            $data[] = array(
                'uid' => $value['uid'],
                'nickname' => $value['nickname'],
                'truename' => $value['truename'],
                'phone' => $value['phone'] . ' ',
                'sex' => $value['sex'] == 0 ? '未知' : ($value['sex'] == 1 ? '男' : '女'),
                'province' => $value['province'],
                'city' => $value['city'],
                'qq' => $value['qq'] . ' ',
                'add_time' => date('Y-m-d H:i:s', $value['add_time']),
                'add_ip' => $add_ip,
                'last_time' => date('Y-m-d H:i:s', $value['last_time']),
                'last_ip' => $last_ip,
                'score_count' => $value['score_count'] . ' ',
                'now_money' => $value['now_money'] . ' ',
                'score_recharge_money' => $value['score_recharge_money'] . ' ',
                'is_check_phone' => $value['is_check_phone'] == 0 ? '否' : '是',
                'is_follow' => $value['is_follow'] ? '是' : '否',
                'status' => $value['status'] ? '正常' : '禁用',
            );
        }

        $csv->export_csv_2($data, $header_data, '平台用户信息.csv');
    }

    public function export1()
    {
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '平台用户信息';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        // 设置当前的sheet

        $database_user = D('User');
        $count_user = $database_user->count();

        $length = ceil($count_user / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千个用户');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '用户ID');
            $objActSheet->setCellValue('B1', '昵称');
            $objActSheet->setCellValue('C1', '真实姓名');
            $objActSheet->setCellValue('D1', '手机号');
            $objActSheet->setCellValue('E1', '性别 (男； 女； 其他)');
            $objActSheet->setCellValue('F1', '省份');
            $objActSheet->setCellValue('G1', '城市');
            $objActSheet->setCellValue('H1', 'QQ');
            $objActSheet->setCellValue('I1', '注册时间');
            $objActSheet->setCellValue('J1', '注册IP');
            $objActSheet->setCellValue('K1', '最后登录时间');
            $objActSheet->setCellValue('L1', '最后登录IP');
            $objActSheet->setCellValue('M1', $this->config['score_name']);
            $objActSheet->setCellValue('N1', '余额');
            $objActSheet->setCellValue('O1', '不可提现的余额');
            $objActSheet->setCellValue('P1', '是否手机认证');
            $objActSheet->setCellValue('Q1', '是否关注公众号');
            $objActSheet->setCellValue('R1', '账号是否正常');


            $user_list = $database_user->field(true)->limit($i * 1000 . ',1000')->select();
            if (!empty($user_list)) {
                import('ORG.Net.IpLocation');
                $IpLocation = new IpLocation();
                $index = 2;
                foreach ($user_list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value['uid']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['nickname']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['truename']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['phone'] . ' ');
                    $sex = $value['sex'] == 0 ? '未知' : ($value['sex'] == 1 ? '男' : '女');
                    $objActSheet->setCellValueExplicit('E' . $index, $sex);

                    $objActSheet->setCellValueExplicit('F' . $index, $value['province']);
                    $objActSheet->setCellValueExplicit('G' . $index, $value['city']);
                    $objActSheet->setCellValueExplicit('H' . $index, $value['qq'] . ' ');
                    $objActSheet->setCellValueExplicit('I' . $index, date('Y-m-d H:i:s', $value['add_time']));

                    $last_location = $IpLocation->getlocation($value['add_ip']);
                    $add_ip = iconv('GBK', 'UTF-8', $last_location['country']);
                    $objActSheet->setCellValueExplicit('J' . $index, $add_ip);

                    $objActSheet->setCellValueExplicit('K' . $index, date('Y-m-d H:i:s', $value['last_time']));

                    $last_location = $IpLocation->getlocation($value['last_ip']);
                    $last_ip = iconv('GBK', 'UTF-8', $last_location['country']);
                    $objActSheet->setCellValueExplicit('L' . $index, $last_ip);

                    $objActSheet->setCellValueExplicit('M' . $index, $value['score_count'] . ' ');
                    $objActSheet->setCellValueExplicit('N' . $index, $value['now_money'] . ' ');
                    $objActSheet->setCellValueExplicit('O' . $index, $value['score_recharge_moeny'] . ' ');
                    $is_check_phone = $value['is_check_phone'] == 0 ? '否' : '是';
                    $objActSheet->setCellValueExplicit('P' . $index, $is_check_phone);
                    $is_follow = $value['is_follow'] ? '是' : '否';
                    $objActSheet->setCellValueExplicit('Q' . $index, $is_follow);
                    $status = $value['status'] ? '正常' : '禁用';
                    $objActSheet->setCellValueExplicit('R' . $index, $status);

                    $index++;
                }
            }
            sleep(2);
        }
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();

    }

    public function amend()
    {
        if (IS_POST) {
            $database_user = D('User');
            $condition_user['uid'] = intval($_POST['uid']);
            $now_user = $database_user->field(true)->where($condition_user)->find();
            if (empty($now_user)) {
                $this->error('没有找到该用户信息！');
            }
            $condition_user['uid'] = $now_user['uid'];
            $data_user['nickname'] = $_POST['nickname'];
            $data_user['phone'] = $_POST['phone'];
            $condition_phone['_string'] = "phone='{$_POST['phone']}'";
            $change_phone = M("User")->where($condition_phone)->find();
            if ($change_phone['phone'] && $change_phone && $change_phone['phone'] != $now_user['phone']) {
                $this->error('该手机号码已注册，请先解绑该手机！');
            }
            if ($_POST['pwd']) {
                //校验密码
                $check_password = verifyPassword($_POST['pwd']);
                if($check_password['code']!=0){
                    $this->error($check_password['msg']);
                }
                $data_user['pwd'] = md5($_POST['pwd']);
            }
            $data_user['sex'] = $_POST['sex'];
            $data_user['province'] = $_POST['province'];
            $data_user['city'] = $_POST['city'];
            $data_user['qq'] = $_POST['qq'];
            $data_user['birthday'] = $_POST['birthday'];
            $data_user['email'] = $_POST['email'];
            $data_user['status'] = $_POST['status'];
            $data_user['frozen_money'] = $_POST['frozen_money'];
            $data_user['frozen_reason'] = trim($_POST['frozen_reason']);
            $data_user['phone_country_type'] = trim($_POST['phone_country_type']);
			$data_user['phone'] = phone_format($data_user['phone_country_type'], $data_user['phone']);
		
            if ($_POST['frozen_money'] > 0 && $this->config['open_frozen_money'] == 1) {
                if (empty($_POST['frozen_time']) || empty($_POST['free_time'])) {
                    $this->error("设置冻结时间必须设置【冻结时间】");
                }
                if ($now_user['now_money'] < $_POST['frozen_money']) {
                    $this->error("冻结金额不能比当前金额大");
                }

                if (empty($_POST['frozen_reason'])) {
                    $this->error("设置冻结时间必须设置【冻结理由】");
                }
            }
            if (!empty($_POST['frozen_time']) && !empty($_POST['free_time'])) {

                if ($_POST['frozen_time'] > $_POST['free_time']) {
                    $this->error("结束时间应大于开始时间");
                }
                $data_user['frozen_time'] = strtotime($_POST['frozen_time'] . " 00:00:00");
                $data_user['free_time'] = strtotime($_POST['free_time'] . " 23:59:59");
            }

            $data_user['status'] = $_POST['status'];
            $data_user['youaddress'] = trim($_POST['youaddress']);
            $data_user['truename'] = trim($_POST['truename']);

            $_POST['set_money'] = floatval($_POST['set_money']);
            if (!empty($_POST['set_money'])) {
                if(C("config.open_user_money_verify") == '1' && $this->system_session['level'] == '0'){
                    //普通管理员触发  这里可以不写判断逻辑

                }
                else{
                    if ($_POST['set_money_type'] == 1) {
                        $data_user['now_money'] = $now_user['now_money'] + $_POST['set_money'];
                    } else {
                        $data_user['now_money'] = $now_user['now_money'] - $_POST['set_money'];
                    }

                    if ($data_user['now_money'] < 0) {
                        $this->error('修改后，余额不能小于0');
                    }
                    if ($_POST['money_remark'] == '') {
                        $this->error('修改余额请填写备注');
                    }
                }
            }

            $_POST['set_score'] = floatval($_POST['set_score']);
            // if (!empty($_POST['set_score'])) {
            //     if ($_POST['set_score_type'] == 1) {
            //         $data_user['score_count'] = $now_user['score_count'] + $_POST['set_score'];
            //     } else {
            //         $data_user['score_count'] = $now_user['score_count'] - $_POST['set_score'];
            //     }
            //     if ($data_user['score_count'] < 0) {
            //         $this->error('修改后，' . $this->config['score_name'] . '不能小于0');
            //     }
            //     if ($_POST['score_remark'] == '') {
            //         $this->error('修改积分请填写备注');
            //     }
            // }

            $cardid = $_POST['cardid'];
            $data_user['cardid'] = $cardid;
            $card = M('Physical_card');
            if (!empty($cardid)) {

                $condition_card['cardid'] = $cardid;
                $res = $card->where($condition_card)->getField('cardid,uid,status');
                if (!empty($res) && !empty($res[$cardid]['uid']) && $res[$cardid]['status'] != 0 && !empty($now_user['cardid'])) {
                    $this->error('实体卡ID不存在,或者实体卡已绑定用户，请检查');
                } else {
                    $card_data['uid'] = $now_user['uid'];
                    $card_data['status'] = 1;
                    $card_data['regtime'] = time();
                    $card_data['last_time'] = time();
                    $card_data['balance_money'] = $_POST['balance_money'];
                    if (!$card->where($condition_card)->save($card_data)) {
                        $this->error('保存实体卡时失败');
                    }
                }
            } else {
                $card->where(array('uid' => $now_user['uid']))->save(array('uid' => NULL, 'regtime' => NULL, 'last_time' => time()));
            }


            $data_user['level'] = intval($_POST['level']);
            //等级变化更新成为该等级的时间
            if ($now_user['level'] != $_POST['level']) {
                $data_user['level_time'] = time();
            }

            if(!M('User_label_bind')->where(array('uid'=>$condition_user['uid']))->find()) { //处理user_label_bin关联表数据缺失
                $data_user['label_id'] = $_POST['label_id'];
//                if ($now_user['label_id'] = $data_user['label_id']) {
                    $data_user['label_time'] = time();
                    if ($data_user['label_id']) {
                        $label_ids_arr = explode(',', $data_user['label_id']);
                        $label_add_arr = array();
                        foreach ($label_ids_arr as $key => $value) {
                            if ( $value ) {
                                $label_add_arr[] = array(
                                    'uid' => $condition_user['uid'],
                                    'label_id' => $value,
                                );
                            }
                        }
                        M('User_label_bind')->addAll($label_add_arr);
                    }
//                }
            } else {
                $data_user['label_id'] = $_POST['label_id'];
                if ($now_user['label_id'] != $data_user['label_id']) {
                    $data_user['label_time'] = time();
                    M('User_label_bind')->where(array('uid'=>$condition_user['uid']))->delete();
                    if ( $data_user['label_id']) {
                        $label_ids_arr = explode(',', $data_user['label_id']);
                        $label_add_arr = array();
                        foreach ($label_ids_arr as $key => $value) {
                            if ( $value ) {
                                $label_add_arr[] = array(
                                    'uid' => $condition_user['uid'],
                                    'label_id' => $value,
                                );
                            }
                        }
                        M('User_label_bind')->addAll($label_add_arr);
                    }
                }
            }

            $data_user['can_spread'] = intval($_POST['can_spread']);
            $data_user['forzen_score'] = $_POST['forzen_score'];
            $data_user['last_update_time'] = time();
            if($this->config['live_open'] == '1'){
                $data_user['open_live_show'] = $_POST['open_live_show'];
                $data_user['open_live_video'] = $_POST['open_live_video'];
            }
            $data_user['school_sfz'] = $_POST['school_sfz'];
            $data_user['school_no'] = $_POST['school_no'];
            $before = $database_user->where($condition_user)->find();
            if ($database_user->where($condition_user)->data($data_user)->save()) {
                $after = $database_user->where($condition_user)->find();
                D("System_log")->add_log("编辑了用户信息，用户名称：".$_POST['nickname'],'用户信息',$condition_user['uid'], $before, $after);
                if ($data_user['level_time'] && $data_user['level_time']>0) {
                    // 记录激活对应获取积分
                    $user_level = D('User_level');
                    $user_level_info = $user_level->where(array('level' => $data_user['level']))->find();
                    D('User_level_open_log')->add_reward($now_user['uid'], $user_level_info, $this->system_session['id'], 0, 2, $data_user['level_time'], 0, '管理员开通会员等级');
                }

                if (!empty($_POST['set_money'])) {
                    if(C("config.open_user_money_verify") == '1' && $this->system_session['level'] == '0'){
                        //普通管理员触发  
                        $insert = [
                            'type' => $_POST['set_money_type'] == '1' ? 1 : 0,
                            'money' => $_POST['set_money'],
                            'admin' => $this->system_session['id'],
                            'admin_openid' => $this->system_session['openid'],
                            'remark' => $_POST['money_remark'],
                            'addtime' => date('Y-m-d H:i:s'),
                            'status' => 0,
                            'uid' => $now_user['uid']
                        ];
                        if(M("User_money_verify")->data($insert)->add()){
                            //发送通知给审核管理员
                            $accept = M("Admin")->where('verify_user_money_change=1')->field("openid")->select();
                            if($accept){
                                if ($_POST['set_money_type'] == 1) {
                                    //充值
                                    $newInfo = [
                                        'tempKey' => '45572',
                                        'thing20' => '平台余额账户',
                                        'amount3' => $_POST['set_money'],
                                        'amount15' => getFormatNumber($data_user['now_money']),
                                        'time2' => date('Y-m-d H:i:s')
                                    ];
                                } else {
                                    //扣除
                                    $newInfo = [
                                        'tempKey' => '55638',
                                        'thing6' => '平台余额账户',
                                        'amount2' => $_POST['set_money'],
                                        'amount4' => getFormatNumber($data_user['now_money']),
                                        'time1' => date('Y-m-d H:i:s')
                                    ];
                                }
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                foreach ($accept as $key => $value) {
                                    $model->sendTempMsg('OPENTM401833445', array('href' => '',
                                        'wecha_id' => $value['openid'],
                                        'first' => '尊敬的' . $value['nickname'] . ',您有需要审核的用户余额变动数据',
                                        'keyword1' => date('Y-m-d H:i'),
                                        'keyword2' => '管理员手动' . ($_POST['set_money_type'] == 1 ? '充值' : '减少') . '平台余额（备注：' . $_POST['money_remark'] . '）',
                                        'keyword3' => ($_POST['set_money_type'] == 1 ? '+' : '-') . $_POST['set_money'],
                                        'keyword4' => round_number($data_user['now_money'],2),
                                        'remark' => '',
                                        'new_info' => $newInfo),
                                        0);
                                }
                            }
                        }
                    }
                    else{
                        if ($now_user['openid']) {
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $href = C('config.site_url') . '/wap.php?c=My&a=transaction';
                            if ($_POST['set_money_type'] == 1) {
                                //充值
                                $newInfo = [
                                    'tempKey' => '45572',
                                    'thing20' => '平台余额账户',
                                    'amount3' => $_POST['set_money'],
                                    'amount15' => getFormatNumber($data_user['now_money']),
                                    'time2' => date('Y-m-d H:i:s')
                                ];
                            } else {
                                //扣除
                                $newInfo = [
                                    'tempKey' => '55638',
                                    'thing6' => '平台余额账户',
                                    'amount2' => $_POST['set_money'],
                                    'amount4' => getFormatNumber($data_user['now_money']),
                                    'time1' => date('Y-m-d H:i:s')
                                ];
                            }
                            $model->sendTempMsg('OPENTM401833445', array('href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '尊敬的' . $now_user['nickname'] . ',您的平台余额账户发生变动',
                                'keyword1' => date('Y-m-d H:i'),
                                'keyword2' => '管理员手动' . ($_POST['set_money_type'] == 1 ? '充值' : '减少') . '平台余额（备注：' . $_POST['money_remark'] . '）',
                                'keyword3' => ($_POST['set_money_type'] == 1 ? '+' : '-') . $_POST['set_money'],
                                'keyword4' => round_number($data_user['now_money'],2),
                                'remark' => '详情请点击此消息进入会员中心-余额记录进行查询!',
                                'new_info' => $newInfo),
                                0);
                        }

                        if (C('config.sms_place_order') == 1 || C('config.sms_place_order') == 3) {
                            $sms_data['uid'] = $now_user['uid'];
                            $sms_data['mobile'] = $now_user['phone'];
                            $sms_data['sendto'] = 'user';
                            $sms_data['content'] = "平台在" . date('Y-m-d H:i:s') . "为您账户手动" . ($_POST['set_money_type'] == 1 ? '充值' : '减少') . '余额' . abs($_POST['set_money']) . C('config.Currency_txt') . "，请及时查看";
                            Sms::sendSms($sms_data);
                        }

                        if (C('config.zbw_key')) {

                            if ($now_user['zbw_cardid']) {
                                if ($_POST['set_money_type'] == 1) {
                                    $result = D('ZbwErp')->VipFullAmt($now_user, $_POST['set_money'], '管理员充值');
                                } else {
                                    $result = D('ZbwErp')->VipPaySheet($now_user, -1 * $_POST['set_money'], '管理员减少');
                                }

                                D('ZbwErp')->sync_data($now_user['uid']);
                            }
                        } else {

                            D('User_money_list')->add_row($now_user['uid'], $_POST['set_money_type'], $_POST['set_money'], '管理员后台操作' . (empty($_POST['money_remark']) ? '' : ',备注：' . $_POST['money_remark']), false, 0, 1, true);
                        }
                    }

                }
                if (!empty($_POST['set_score'])) {

                    if (C('config.zbw_key')) {

                        if ($now_user['zbw_cardid']) {
                            $result = D('ZbwErp')->VipSaleSheet($now_user, ($_POST['set_score_type'] == 1 ? $_POST['set_score'] : -1 * $_POST['set_score']), '管理员操作积分');
                            D('ZbwErp')->sync_data($now_user['uid']);
                        }
                    } else {
                        if($_POST['set_score_type'] == '1'){
                            D("User")->add_score($now_user['uid'],$_POST['set_score'], '管理员后台操作' . (empty($_POST['score_remark']) ? '' : ',备注：' . $_POST['score_remark']));
                        }
                        elseif($_POST['set_score_type'] == '2'){
                            D("User")->use_score($now_user['uid'],$_POST['set_score'], '管理员后台操作' . (empty($_POST['score_remark']) ? '' : ',备注：' . $_POST['score_remark']));
                        }
                        // D('User_score_list')->add_row($now_user['uid'], $_POST['set_score_type'], $_POST['set_score'], '管理员后台操作' . (empty($_POST['score_remark']) ? '' : ',备注：' . $_POST['score_remark']), false, 0, 0, true);
                    }
                }
                $this->success('修改成功！');
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $this->error('非法访问！');
        }
    }

    public function money_list()
    {
        $this->assign('bg_color', '#F3F3F3');
        $database_user_money_list = D('User_money_list');
        $condition_user_money_list['uid'] = intval($_GET['uid']);
        if ($_GET['ask']) {
            $condition_user_money_list['ask'] = intval($_GET['ask']);
        }
        if ($_GET['ask_id']) {
            $condition_user_money_list['ask_id'] = intval($_GET['ask_id']);
        }
        $count = $database_user_money_list->where($condition_user_money_list)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $condition_user_money_list['_string'] = "`desc` not like '%余额记录减扣%' AND  `desc` not like '%不可提现%' ";
        $money_list = $database_user_money_list->field(true)->where($condition_user_money_list)->order('`time` DESC,`pigcms_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $this->assign('pagebar', $p->show());
        $this->assign('money_list', $money_list);
        $this->display();
    }

    public function score_list()
    {
        $this->assign('bg_color', '#F3F3F3');
        $database_user_score_list = D('User_score_list');
        $condition_user_score_list['uid'] = intval($_GET['uid']);

        $count = $database_user_score_list->where($condition_user_score_list)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);

        $score_list = $database_user_score_list->field(true)->where($condition_user_score_list)->order('`time` DESC,`id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $this->assign('pagebar', $p->show());
        $this->assign('score_list', $score_list);
        $this->display();
    }

    /*     * *导入客户页**** */

    public function import()
    {
        $this->display();
    }

    /*     * *导入客户页**** */

    public function execimport()
    {
        if ($_FILES['file']['error'] != 4) {

            import('ORG.Util.Dir');
            Dir::delDirnotself('./upload/excel/user');

            $getupload_dir = "/upload/excel/user/" . date('Ymd') . '/';
            $upload_dir = "." . $getupload_dir;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = 10 * 1024 * 1024;
            $upload->allowExts = array('xls', 'xlsx');
            $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
            $upload->savePath = $upload_dir;
            $upload->thumb = false;
            $upload->thumbType = 0;
            $upload->imageClassPath = '';
            $upload->thumbPrefix = '';
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                //$reader = PHPExcel_IOFactory::createReader('Excel5');
                $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                $objReader = PHPExcel_IOFactory::createReader($fileType);
                $excelObj = $objReader->load($path);
                $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                unlink($path);    //删除文件
                if (!empty($result) && is_array($result)) {
                    unset($result[1]);
                    if(C('config.open_school') == 1){
                        foreach ($result as $kk => $vv) {
                            if (empty($vv['A']) || empty($vv['B']))
                                continue;
                            $tmpdata = array();
                            $tmpdata['nickname'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            $tmpdata['truename'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            $tmpdata['school_type'] = 1;
                            $tmpdata['school_sfz'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            $tmpdata['phone'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $tmpdata['school_no'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            $tmpdata['pwd'] = md5(substr($tmpdata['school_sfz'], -6));
                            $tmpdata['add_time'] = time();
                            $tmpdata['last_time'] = time();
                            $tmpdata['status'] = 1;
                            $tmpdata['source'] = 'PC';
                            $__money = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            $__score = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                            if(is_numeric($__money) && $__money > 0){
                                $tmpdata['now_money'] = $__money;
                            }
                            // if(count(array_unique([$tmpdata['school_sfz'], $tmpdata['phone'], $tmpdata['school_no']])) != 3){
                            //     continue;
                            // }
                            $_complex = [
                                'school_sfz' => ['eq', $tmpdata['school_sfz']],
                            ];
                            if($tmpdata['phone'] || $tmpdata['school_no']){
                                $_complex['_logic'] = 'or';
                                if($tmpdata['phone']){
                                    $_complex['phone'] = ['eq', $tmpdata['phone']];
                                }
                                if($tmpdata['school_no']){
                                    $_complex['school_no'] = ['eq', $tmpdata['school_no']];
                                }
                                $where = ['_complex'=>$_complex];
                            }
                            else{
                                $where = $_complex;
                            }
                            
                            if(D('User')->where($where)->find()){
                                continue;
                            }
                            $r = D('User')->data($tmpdata)->add();
                            if($r){
                                if(is_numeric($__money) && $__money > 0){
                                    D('User_money_list')->add_row($r, 1, $__money, '管理员后台导入校内人员操作', false, 0, 1, true);
                                }
                                if(is_numeric($__score) && $__score > 0){
                                    D("User")->add_score($r,$__score, '管理员后台导入校内人员操作');
                                }
                            }
                        }
                    }
                    else{
                        $user_importDb = D('User_import');
                        foreach ($result as $kk => $vv) {
                            if (empty($vv['A']) || empty($vv['B']) || empty($vv['C']))
                                continue;
                            $tmpdata = array();
                            $tmpdata['ppname'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            $tmpdata['telphone'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            $tmpdata['address'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            !empty($vv['D']) && $tmpdata['mer_id'] = intval(trim($vv['D']));
                            !empty($vv['E']) && $tmpdata['memberid'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            !empty($vv['F']) && $tmpdata['level'] = intval(trim($vv['F']));
                            !empty($vv['G']) && $tmpdata['qq'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                            !empty($vv['H']) && $tmpdata['email'] = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                            !empty($vv['I']) && $tmpdata['money'] = floatval(trim($vv['I']));
                            !empty($vv['J']) && $tmpdata['integral'] = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                            !empty($vv['K']) && $tmpdata['useraccount'] = htmlspecialchars(trim($vv['K']), ENT_QUOTES);
                            if (!empty($vv['L'])) {
                                $tmpdata['pwdmw'] = trim($vv['L']);
                                $tmpdata['pwd'] = md5($tmpdata['pwdmw']);
                            }
                            $tmpdata['isuse'] = 0;
                            $tmpdata['addtime'] = time();
                            $id = $user_importDb->add($tmpdata);
                            //开启客户导入生成真实用户
                            if (C('config.open_user_import_create_user') == 1) {
                                $tmpdata = $user_importDb->find($id);
                                $database_user = D('User');
                                $condition_user['phone'] = $tmpdata['telphone'];

                                if ($user = $database_user->field('`uid`,`now_money`')->where($condition_user)->find()) {
                                    // 手机号已存在 更新信息
                                    $data_user = array();
                                    $data_user['last_ip'] = get_client_ip(0);
                                    $data_user['truename'] = $tmpdata['ppname'];
                                    $data_user['importid'] = $id;
                                    $data_user['phone'] = $tmpdata['telphone'];
                                    $tmpdata['pwd'] && $data_user['pwd'] = $tmpdata['pwd'];
                                    $tmpdata['qq'] && $data_user['qq'] = $tmpdata['qq'];
                                    $tmpdata['email'] && $data_user['email'] = $tmpdata['email'];
                                    $tmpdata['level'] && $data_user['level'] = $tmpdata['level'];
                                    $tmpdata['integral'] > 0 && $data_user['score_count'] = floatval($tmpdata['integral']);
                                    $tmpdata['money'] > 0 && $data_user['now_money'] = floatval($tmpdata['money']);
                                    $tmpdata['address'] && $data_user['youaddress'] = str_replace('|', ' ', $tmpdata['address']);

                                    if ($database_user->where(array('uid' => $user['uid']))->data($data_user)->save()) {
                                        $user_importDb->where(array('id' => $id))->save(array('isuse' => 2));
                                        if ($tmpdata['money']) {
                                            $user['now_money'] && D('User_money_list')->add_row($user['uid'], 2, $user['now_money'], '线下实体卡余额同步减少', false, 0, 1, true);
                                            D('User_money_list')->add_row($user['uid'], 1, $data_user['now_money'], '线下实体卡余额同步增加', false, 0, 1, true);
                                        }
                                        if ($tmpdata['integral']) {
                                            $user['score_count'] && D('User_score_list')->add_row($user['uid'], 2, $user['score_count'], '线下实体卡积分同步减少');
                                            D('User_score_list')->add_row($user['uid'], 1, $data_user['score_count'], '线下实体卡积分同步增加');
                                        }
                                    } else {
                                        $this->dexit(array('error' => 1, 'msg' => '导入失败！'));
                                    }
                                } else {
                                    // 新增一个用户
                                    $tmpdata = $user_importDb->find($id);
                                    $data_user = array();
                                    $data_user['nickname'] = substr($data_user['telphone'], 0, 3) . '****' . substr($tmpdata['telphone'], 7);
                                    $data_user['add_time'] = $data_user['last_time'] = $_SERVER['REQUEST_TIME'];
                                    $data_user['add_ip'] = $data_user['last_ip'] = get_client_ip(0);
                                    $data_user['truename'] = $tmpdata['ppname'];
                                    $data_user['importid'] = $id;
                                    $data_user['phone'] = $tmpdata['telphone'];
                                    $tmpdata['pwd'] && $data_user['pwd'] = $tmpdata['pwd'];
                                    $tmpdata['qq'] && $data_user['qq'] = $tmpdata['qq'];
                                    $tmpdata['email'] && $data_user['email'] = $tmpdata['email'];
                                    $tmpdata['level'] && $data_user['level'] = $tmpdata['level'];
                                    $tmpdata['integral'] > 0 && $data_user['score_count'] = floatval($tmpdata['integral']);
                                    $tmpdata['money'] > 0 && $data_user['now_money'] = floatval($tmpdata['money']);
                                    $tmpdata['address'] && $data_user['youaddress'] = str_replace('|', ' ', $tmpdata['address']);
                                    $uid = $database_user->data($data_user)->add();
                                    if ($uid) {
                                        $user_importDb->where(array('id' => $id))->save(array('isuse' => 2));
                                        D('User_money_list')->add_row($uid, 1, $data_user['now_money'], '线下实体卡余额同步增加', false, 0, 1, true);
                                        D('User_score_list')->add_row($uid, 1, $data_user['score_count'], '线下实体卡积分同步增加');
                                    } else {
                                        $this->dexit(array('error' => 1, 'msg' => '导入失败！'));
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($tmpdata)) {
                        $this->dexit(array('error' => 0));
                    } else {
                        $this->dexit(array('error' => 1, 'msg' => '导入失败！'));
                    }
                }
            } else {
                $this->dexit(array('error' => 1, 'msg' => $upload->getErrorMsg()));
            }
        }
        $this->dexit(array('error' => 1, 'msg' => '文件上传失败！'));
    }

    /*     * *导入客户的列表页**** */

    public function importlist()
    {
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $where['_string'] = " (addtime BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

        }
        import('@.ORG.system_page');
        if(C('config.open_school') == 1){
            $where['school_type'] = 1;
            $db = D('User');
            $count = $db->where($where)->count();
            $p = new Page($count, 20);
            $pagebar = $p->show();
            $this->assign('pagebar', $pagebar);
            $tmpdatas = $db->where($where)->order('uid DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            $money_count = $db->where($where)->getField('sum(now_money)');
            $score_count = $db->where($where)->getField('sum(score_count)');

        }
        else{
            $user_importDb = D('User_import');

            $count_userimportDb = $user_importDb->where($where)->count();
            $p = new Page($count_userimportDb, 20);
            $pagebar = $p->show();
            $this->assign('pagebar', $pagebar);

            $tmpdatas = $user_importDb->where($where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

            $money_count = $user_importDb->where($where)->getField('sum(money)');
            $score_count = $user_importDb->where($where)->getField('sum(integral)');
            
        }
        $this->assign('money_count', $money_count ? $money_count : 0);
        $this->assign('score_count', $score_count ? $score_count : 0);
        $this->assign('userimport', $tmpdatas);
        $this->display();
    }


    /**
     *  导入客户余额页
     */
    public function importmoney()
    {
        $user_importDb = D('User_money_import');
        if (empty($_GET['import'])) {
            $user_importDb->where(['status' => 0])->delete(); //删除未执行导入的数据
        } else if ($_GET['import'] == 2) { //确认导入
            $user_importDb->where(['status' => 0])->data(['status' => 1])->save();
            $this->dexit(array('error' => 0));
        } else if ($_GET['import'] == 3) { //取消
            $user_importDb->where(['status' => 0])->delete(); //取消
            $this->dexit(array('error' => 0));
        }
        import('@.ORG.system_page');
        $count_userimportDb = $user_importDb->where(['status' => 0])->count();
        $p = new Page($count_userimportDb, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $tmpdatas = $user_importDb->where(['status' => 0])->order('id asc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('userimport', empty($tmpdatas) ? '' : $tmpdatas);
        $this->display();
    }

    /**
     *  执行导入
     */
    public function execimportmoney()
    {
        if ($_FILES['file']['error'] != 4) {
            import('ORG.Util.Dir');
            Dir::delDirnotself('./upload/excel/user');
            $getupload_dir = "/upload/excel/user/" . date('Ymd') . '/';
            $upload_dir    = "." . $getupload_dir;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize        = 10 * 1024 * 1024;
            $upload->allowExts      = array('xls', 'xlsx');
            $upload->allowTypes     = array(); // 允许上传的文件类型 留空不做检查
            $upload->savePath       = $upload_dir;
            $upload->thumb          = false;
            $upload->thumbType      = 0;
            $upload->imageClassPath = '';
            $upload->thumbPrefix    = '';
            $upload->saveRule       = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                $path      = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                $fileType  = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                $objReader = PHPExcel_IOFactory::createReader($fileType);
                $excelObj  = $objReader->load($path);
                $result    = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                unlink($path);    //删除文件
                if (!empty($result) && is_array($result)) {
                    unset($result[1]);
                    $user_importDb = D('User_money_import');
                    foreach ($result as $kk => $vv) {
                        if (empty($vv['A']) || (empty($vv['B']) && empty($vv['C']))) continue;
                        $tmpdata = array();
                        $tmpdata['phone']  = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                        $tmpdata['score']  = !empty($vv['B']) ? htmlspecialchars(trim($vv['B']), ENT_QUOTES) : 0;
                        $tmpdata['money']  = !empty($vv['C']) ? htmlspecialchars(trim($vv['C']), ENT_QUOTES) : 0;
                        $tmpdata['remark'] = !empty($vv['D']) ? htmlspecialchars(trim($vv['D']), ENT_QUOTES) : '';
                        $tmpdata['status'] = 0;
                        $tmpdata['create_time'] = time();
                        $user_importDb->add($tmpdata);
                    }
                    if (!empty($tmpdata)) {
                        $this->dexit(array('error' => 0));
                    } else {
                        $this->dexit(array('error' => 1, 'msg' => '导入失败！'));
                    }
                }
            } else {
                $this->dexit(array('error' => 1, 'msg' => $upload->getErrorMsg()));
            }
        }
        $this->dexit(array('error' => 1, 'msg' => '文件上传失败！'));
    }

    /**
     *  导入余额列表
     */
    public function importmoneylist()
    {
        $where = [];
        if (!empty($_GET['phone'])) {
            $where['phone'] = $_GET['phone'];
        }
        if (!empty($_GET['status'])) {
            $where['status'] = $_GET['status'];
        } else {
            $where['status'] = ['GT', 0];
        }
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $where['_string'] = " (create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        import('@.ORG.system_page');
        $user_importDb = D('User_money_import');
        $count_userimportDb = $user_importDb->where($where)->count();
        $p = new Page($count_userimportDb, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $tmpdatas = $user_importDb->where($where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach($tmpdatas as &$d){
            $user = D('User')->where(['phone' => $d['phone'],'status'=>['neq',4]])->find();
            $d['is_register'] = $user ? 1 : 0;
        }
        $this->assign('userimport', $tmpdatas);
        $this->display();
    }

    /**
     *  审核通过
     */
    public function verifyagree()
    {
        if (empty($_POST['ids'])) {
            $this->dexit(array('error' => 1, 'msg' => '未选择任何数据！'));
        }
        $database_Import = D('User_money_import');
        $database_user   = D('User');
        $list = $database_Import->where(['id' => ['in', $_POST['ids']], 'status' => 1])->select();
        foreach ($list as $v) {
            $user = $database_user->where(['phone' => $v['phone']])->find();
            if ($user) {
                $res = $database_user->where(['uid' => $user['uid']])->data(['now_money' => $user['now_money'] + $v['money'], 'score_count' => $user['score_count'] + $v['score']])->save();
                if ($res) {
                    $database_Import->where(array('id' => $v['id']))->data(['status' => 2, 'verify_by' => $this->system_session['id'], 'verify_name' => $this->system_session['realname'], 'verify_remark' => $_POST['verify_remark'] ?? '', 'verify_time' => time()])->save();
                    D('User_money_list')->add_row($user['uid'], $v['money'] > 0 ? 1 : 2, abs($v['money']), '批量导入余额审核通过', false, 0, 0, true);
                    D('User_score_list')->add_row($user['uid'], $v['money'] > 0 ? 1 : 2, abs($v['score']), '批量导入余额审核通过');
                }
            }
        }
        $this->dexit(array('error' => 0, 'msg' => '审核通过成功'));
    }

    /**
     *  审核驳回
     */
    public function verifyrefuse()
    {
        if (empty($_POST['ids'])) {
            $this->dexit(array('error' => 1, 'msg' => '未选择任何数据！'));
        }
        D('User_money_import')->where(['id' => ['in', $_POST['ids']], 'status' => 1])->data(['status' => 3, 'verify_by' => $this->system_session['id'], 'verify_name' => $this->system_session['realname'], 'verify_remark' => $_POST['verify_remark'] ?? '', 'verify_time' => time()])->save();
        $this->dexit(array('error' => 0, 'msg' => '审核驳回成功'));
    }


    /*     * *导入客户的列表页**** */

    public function levellist()
    {
        $user_levelDb = D('User_level');
        $count_userlevelDb = $user_levelDb->count();
        import('@.ORG.system_page');
        $p = new Page($count_userlevelDb, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $tmpdatas = D(_view('User_level'))->where('22=22')->order('id ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('userlevel', $tmpdatas);
        $this->display();
    }

    /*     * *添加等级**** */

    public function addlevel()
    {
        $levelDb = M('User_level');
        $tmparr = M(_view('User_level'))->order('level DESC')->find();
        $level = 0;
        if (!empty($tmparr)) {
            $level = $tmparr['level'];
        }
        $level = $level + 1;
        if (IS_POST) {
            $lid = intval($_POST['lid']);
            if (!($lid > 0)) {
                $newdata = array('level' => $level);
            }
            $lname = trim($_POST['lname']);
            if (empty($lname))
                $this->error('等级名称没有填写！');
            $newdata['lname'] = $lname;

            $integral = intval($_POST['integral']);
            if (!($integral > 0))
                $this->error('等级' . $this->config['score_name'] . '没有填写！');
            $newdata['integral'] = $integral;

            $use_money = intval($_POST['use_money']);
            if (!($use_money >= 0))
                $this->error('等级金额没有填写！');
            $newdata['use_money'] = $use_money;

            $newdata['icon'] = trim($_POST['icon']);
            $newdata['validity'] = trim($_POST['validity']);
            $newdata['type'] = trim($_POST['fltype']);
            $newdata['boon'] = trim($_POST['boon']);
            $newdata['description'] = trim($_POST['description']);
            $newdata['spread_user_give_score'] = empty($_POST['spread_user_give_score']) ? 0 : $_POST['spread_user_give_score'];
            $newdata['spread_user_give_moeny'] = empty($_POST['spread_user_give_moeny']) ? 0 : $_POST['spread_user_give_moeny'];
            $newdata['score_clean_time'] = empty($_POST['score_clean_time']) ? '' : $_POST['score_clean_time'];
            $newdata['score_clean_percent'] = empty($_POST['score_clean_percent']) ? 0 : $_POST['score_clean_percent'];
            $newdata['open_give_score'] = empty($_POST['open_give_score']) ? 0 : $_POST['open_give_score'];
            $newdata['coupon_ids'] = empty($_POST['coupon_ids_again']) ? '' : implode(',', $_POST['coupon_ids_again']);
            $newdata['sub_description'] = isset($_POST['sub_description'])?trim($_POST['sub_description']):'';
            $newdata['first_share_money'] = isset($_POST['first_share_money'])?trim($_POST['first_share_money']):0;
            $newdata['second_share_money'] = isset($_POST['second_share_money'])?trim($_POST['second_share_money']):0;
            $newdata['thild_share_money'] = isset($_POST['thild_share_money'])?trim($_POST['thild_share_money']):0;
            
            $newdata['can_spread'] = $_POST['can_spread'] ?? 0;
            if ($lid > 0) {
                $inser_id = $levelDb->where(array('id' => $lid))->save($newdata);
                //多语言表修改数据
                $lang_model = D('Lang');
                $_POST['id'] = $lid;
                $lang_model->edit_lang_data('User_level',$newdata);

            } else {
                $inser_id = $levelDb->add($newdata);
                //多语言表增加数据
                $lang_model = D('Lang');
                $_POST['id'] = $inser_id;
                $lang_model->add_lang_data('User_level',$newdata);

            }
            fdump_sql($newdata, 'User_level_work');
            if ($inser_id !== false) {

                //更新 店铺 快店 团
                if ($this->config['discount_sync'] == 1) {
                    $levelDb = M('User_level');
                    $tmparr = M(_view('User_level'))->order('id ASC')->select();
                    $newleveloff = [];
                    foreach ($tmparr as $kk => $vv) {
                        $vl['type'] = intval($vv['type']);
                        $vl['vv'] = intval($vv['boon']);
                        if (($vl['type'] > 0) && ($vl['vv'] > 0)) {
                            $vl['level'] = $vv['level'];
                            $vl['lname'] = $vv['lname'];
                            $vl['lid'] = $vv['id'];
                            $newleveloff[$vv['level']] = $vl;
                        }
                    }
                    $newleveloff = serialize($newleveloff);
                    //批量更新等级
                    M('')->execute('UPDATE ' . C('DB_PREFIX') . "merchant_store_shop SET leveloff='" . $newleveloff . "'");
                    M('')->execute('UPDATE ' . C('DB_PREFIX') . "merchant_store SET leveloff='" . $newleveloff . "'");
                    M('')->execute('UPDATE ' . C('DB_PREFIX') . "group SET leveloff='" . $newleveloff . "'");
                }
                $this->success('保存成功！');
            } else {
                $this->error('保存失败！');
            }
        } else {
            $lid = intval($_GET['lid']);
            $tmpdata = $levelDb->where(array('id' => $lid))->find();
            if (empty($tmpdata)) {
                $tmpdata = array('id' => 0, 'level' => $level, 'lname' => '', 'integral' => '', 'icon' => '', 'boon' => '', 'type' => 0, 'description' => '');
            }
            $tmpdata['coupon_ids_again'] = $tmpdata['coupon_ids'];
            if ($tmpdata['coupon_ids']) {
                $condition = array('coupon_id' => array('in', $tmpdata['coupon_ids']));
                $coupon_list_again = D('System_coupon')->where($condition)->select();

                foreach ($coupon_list_again as &$v) {
                    $v['platform'] = unserialize($v['platform']);
                    if ($v['cate_name'] != 'all' && !empty($v['cate_id'])) {
                        $tmp = unserialize($v['cate_id']);
                        $v['cate_id'] = $tmp['cat_name'];
                    }
                }
                $this->assign("coupon_list_again", $coupon_list_again);
                $return = D('System_coupon')->cate_platform();
                $this->assign("platform", $return['platform']);
            }

            $this->assign('leveldata', $tmpdata);
            $this->display();
        }
    }

    /*     * **删除一条导入的记录**** */

    function delimportuser()
    {
        $idx = (int)trim($_POST['id']);
        $user_importDb = D('User_import');
        if ($user_importDb->where(array('id' => $idx))->delete()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败' . $this->_get('id'));
        }
    }

    /*     * json 格式封装函数* */

    private function dexit($data = '')
    {
        if (is_array($data)) {
            echo json_encode($data);
        } else {
            echo $data;
        }
        exit();
    }

    //	用户实名认证列表
    public function authentication()
    {
        $status = $_GET['status'];
        if (empty($status)) {
            $where['authentication_status'] = 0;
            $order['authentication_time'] = 'desc';
        } else {
            $where['authentication_status'] = array('neq', 0);
            $order['examine_time'] = 'desc';
        }
        $card = M('User_authentication');
        $count_card = $card->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count_card, 20);
        $card_list = $card->field(true)->order($order)->limit($p->firstRow . ',' . $p->listRows)->where($where)->select();
        foreach ($card_list as &$v) {
            if (strstr($v['authentication_img'], ',')) {
                $merchant_image_class = new scenic_image();
                $v['authentication_img'] = $merchant_image_class->get_image_by_path($v['authentication_img'], $this->config['site_url'], 'aguide', '1');
            }elseif(count(explode('/upload',$v['authentication_img'])) == 2 && !explode('/upload',$v['authentication_img'])[0]){
                $v['authentication_img'] = file_domain()  . $v['authentication_img'];
            } elseif(count(explode('/upload',$v['authentication_img'])) == 1) {
                $v['authentication_img'] = file_domain() . '/upload/scenic/aguide/' . $v['authentication_img'];
//                $v['authentication_img'] = $this->config['site_url'] . $v['authentication_img'];
            }
        }
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('card_list', $card_list);
        $this->assign('status', $status);
        $this->display();
    }

    //	审核用户实名认证
    public function check()
    {
        if (IS_POST) {
            $where['authentication_id'] = $_POST['authentication_id'];
            $data = array(
                'examine_remarks' => $_POST['examine_remarks'],
                'examine_time' => time(),
            );
            if ($_POST['examine_remarks'] == 0) {
                $data['authentication_status'] = 1;
                $user_data['real_name'] = 1;
            } else {
                $data['authentication_status'] = 2;
                $user_data['real_name'] = 3;
            }
            $save = M('User_authentication')->where($where)->data($data)->save();
            if ($save) {
                $user_where['uid'] = $_POST['uid'];
                $sReal = M('User')->where($user_where)->data($user_data)->save();
                if ($sReal) {
                    // 用户实名认证获得积分
                    $score = D('User_score_list')->get_one_score(array('score_type' => 2, 'uid' => $_POST['uid']));
                    if (!$score && C('config.score_bind_certification') > 0) { // 用户实名认证只能获一次积分
                        D('User')->add_score($_POST['uid'], C('config.score_bind_certification'), '实名认证获得' . C('config.score_name'), 0, array('score_type' => 2));
                    }

                    $this->success('审核成功');
                } else {
                    $this->error('用户审核失败');
                }
            } else {
                $this->error('审核失败');
            }
        }
        $where['authentication_id'] = $_GET['authentication_id'];
        $status = $_GET['status'];
        $userAuth = M('User_authentication')->field(true)->where($where)->find();
        $merchant_image_class = new scenic_image();
        if (strstr($userAuth['authentication_img'], ',')) {
            $userAuth['authentication_img'] = $merchant_image_class->get_image_by_path($userAuth['authentication_img'], $this->config['site_url'], 'aguide', '1');
        }elseif(count(explode('/upload',$userAuth['authentication_img'])) == 2 && !explode('/upload',$userAuth['authentication_img'])[0]){
            $userAuth['authentication_img'] = file_domain()  . $userAuth['authentication_img'];
        }elseif(count(explode('/upload',$userAuth['authentication_img'])) == 1){
            $userAuth['authentication_img'] = file_domain() . '/upload/scenic/aguide/' . $userAuth['authentication_img'];
        }
        if (strstr($userAuth['authentication_back_img'], ',')) {
            $userAuth['authentication_back_img'] = $merchant_image_class->get_image_by_path($userAuth['authentication_back_img'], $this->config['site_url'], 'aguide', '1');
        }elseif(count(explode('/upload',$userAuth['authentication_back_img'])) == 2 && !explode('/upload',$userAuth['authentication_img'])[0]){
            $userAuth['authentication_back_img'] = file_domain()  . $userAuth['authentication_back_img'];
        }elseif(count(explode('/upload',$userAuth['authentication_back_img'])) == 1){
            $userAuth['authentication_back_img'] = file_domain() . '/upload/scenic/aguide/' . $userAuth['authentication_back_img'];
        }
        if (strstr($userAuth['hand_authentication'], ',')) {
            $userAuth['hand_authentication'] = $merchant_image_class->get_image_by_path($userAuth['hand_authentication'], $this->config['site_url'], 'aguide', '1');
        }elseif(count(explode('/upload',$userAuth['hand_authentication'])) == 2 && !explode('/upload',$userAuth['authentication_img'])[0]){
            $userAuth['hand_authentication'] = file_domain()  . $userAuth['hand_authentication'];
        }elseif(count(explode('/upload',$userAuth['hand_authentication'])) == 1){
            $userAuth['hand_authentication'] = file_domain() . '/upload/scenic/aguide/' . $userAuth['hand_authentication'];
        }
        $this->assign('userAuth', $userAuth);
        $this->assign('status', $status);
        $this->display();
    }

    # 车主认证
    public function authentication_car()
    {
        $status = $_GET['status'];
        if (empty($status)) {
            $where['status'] = 0;
            $order['add_time'] = 'desc';
        } else {
            $where['status'] = array('neq', 0);
            $order['examine_time'] = 'desc';
        }
        $card = M('User_authentication_car');
        $count_card = $card->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count_card, 20);
        $card_list = $card->field(true)->order($order)->limit($p->firstRow . ',' . $p->listRows)->where($where)->select();
        $merchant_image_class = new scenic_image();
        foreach ($card_list as &$v) {
            //$v['authentication_img'] = $merchant_image_class->get_car_by_path($v['authentication_img'],$this->config['site_url'],'authentication_car','s');
            $v['authentication_img'] = $merchant_image_class->get_car_by_path($v['drivers_license'], $this->config['site_url'], 'authentication_car', 's');
            //$v['driving_license'] = $merchant_image_class->get_car_by_path($v['driving_license'],$this->config['site_url'],'authentication_car','s');

        }

        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('card_list', $card_list);
        $this->assign('status', $status);
        $this->display();
    }

    #证件照片
    public function car_authority_img()
    {
        $where['car_id'] = $_GET['car_id'];
        $find = M('User_authentication_car')->where($where)->find();
        if ($find) {
            $image_class = new scenic_image();
            $find['authentication_img'] = $image_class->get_car_by_path($find['authentication_img'], $this->config['site_url'], 'authentication_car', 's');
            $find['authentication_back_img'] = $image_class->get_car_by_path($find['authentication_back_img'], $this->config['site_url'], 'authentication_car', 's');
            $find['drivers_license'] = $image_class->get_car_by_path($find['drivers_license'], $this->config['site_url'], 'authentication_car', 's');
            $find['driving_license'] = $image_class->get_car_by_path($find['driving_license'], $this->config['site_url'], 'authentication_car', 's');
        }
        $this->assign('userAuth', $find);

        $this->display();

    }

    # 车主审核
    public function car_check()
    {
        if (IS_POST) {
            $where['car_id'] = $_POST['car_id'];
            $data = array(
                'examine_remarks' => $_POST['examine_remarks'],
                'status' => $_POST['status'],
                'examine_time' => time(),
            );
            $save = M('User_authentication_car')->where($where)->data($data)->save();
            if ($save) {
                // 查询一下申请车主的信息 并将审核结果通知车主
                $userAuth = M('User_authentication_car')->field(true)->where($where)->find();
                $user_info = M('User')->where(array('uid' => $userAuth['uid']))->field('nickname, openid')->find();
                if ($user_info['openid']) {
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=car_owner';
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    if ($data['status'] == 1) {
                        $examine_info = '审核通过';
                    } else {
                        $examine_info = '审核拒绝';
                    }

                    $model->sendTempMsg('OPENTM405462911',
                        array(
                            'href' => $href,
                            'wecha_id' => $user_info['openid'],
                            'first' => '您提交车主认证已经审核，请及时查看',
                            'keyword1' => '车主认证审核提醒',
                            'keyword2' => $examine_info,
                            'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                            'keyword4' => $user_info['nickname'],
                            'remark' => '\n请点击查看详细信息！'
                        )
                    );
                }


                $this->success('审核成功');
            } else {
                $this->error('审核失败');
            }
        }
        $where['car_id'] = $_GET['car_id'];
        $statuss = $_GET['statuss'];
        $userAuth = M('User_authentication_car')->field(true)->where($where)->find();
        $merchant_image_class = new scenic_image();
        $userAuth['authentication_img'] = $merchant_image_class->get_car_by_path($userAuth['authentication_img'], $this->config['site_url'], 'authentication_car', '1');
        $userAuth['authentication_back_img'] = $merchant_image_class->get_car_by_path($userAuth['authentication_back_img'], $this->config['site_url'], 'authentication_car', '1');
        $userAuth['drivers_license'] = $merchant_image_class->get_car_by_path($userAuth['drivers_license'], $this->config['site_url'], 'authentication_car', '1');
        $userAuth['driving_license'] = $merchant_image_class->get_car_by_path($userAuth['driving_license'], $this->config['site_url'], 'authentication_car', '1');
        $this->assign('userAuth', $userAuth);
        $this->assign('statuss', $statuss);
        $this->display();
    }

    public function recharge_list()
    {

        $condition_where = "`o`.`uid`=`u`.`uid` ";
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid' => $_GET['keyword']))->find();
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($tmp_result['order_id']) . "'";
            } elseif ($_GET['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= " AND `u`.`phone` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'order_id') {
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($_GET['keyword']) . "'";
            } elseif ($_GET['searchtype'] == 'third_id') {
                $condition_where .= " AND `o`.`third_id`='" . htmlspecialchars($_GET['keyword']) . "'";
            }

        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_where .= " AND  (o.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

        }

        if ($_GET['pay_type']) {
            $condition_where .= " AND `o`.`pay_type`='" . $_GET['pay_type'] . "'";
        }

//        $status = isset($_GET['status']) ? intval($_GET['status']) : -1;
        $type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : '';
        $sort = isset($_GET['sort']) && $_GET['sort'] ? $_GET['sort'] : '';
        if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
        if ($type != 'price' && $type != 'pay_time') $type = '';
        $order_sort = '';
        if ($type && $sort) {
            $order_sort .= 'o.' . $type . ' ' . $sort . ',';
            $order_sort .= 'o.order_id DESC';
        } else {
            $order_sort .= 'o.order_id DESC';
        }

        //if ($status != -1) {
        $condition_where .= " AND `o`.`paid`=1";
//        }

        $condition_table = array(C('DB_PREFIX') . 'user_recharge_order' => 'o', C('DB_PREFIX') . 'user' => 'u');
        $order_count = D('')->where($condition_where)->table($condition_table)->count();
        import('@.ORG.system_page');
        $p = new Page($order_count, 30);
        $order_list = D('')->field('`o`.*,`u`.`uid`,`u`.`nickname`,`u`.`phone`')->where($condition_where)->table($condition_table)->order($order_sort)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('order_list', $order_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        $money_count = D('')->table($condition_table)->where($condition_where)->getField('sum(o.money)');
        $this->assign(array('type' => $type, 'sort' => $sort, 'money_count' => $money_count ? $money_count : 0));
        $this->assign('status_list', D('Group_order')->status_list);
        $this->display();
    }

    /**
     * @return    充值详情
     */
    public function order_detail()
    {
        $this->assign('bg_color', '#F3F3F3');
        $order = D('User_recharge_order');
        $condition_group_order['o.order_id'] = $_GET['order_id'];
        $order = $order->join('as o left join ' . C('DB_PREFIX') . 'user u ON u.uid = o.uid')->field('`o`.*,`u`.`uid`,`u`.`nickname`,`u`.`phone`')->where($condition_group_order)->find();

        if (empty($order)) {
            $this->frame_error_tips('此订单不存在！');
        }

        if ($order['pay_type']) {
            $order['pay_type_name'] = D('Pay')->get_pay_name($order['pay_type'], 0, 1, '');
        } else {
            $order['pay_type_name'] = '';
        }

        $this->assign('now_order', $order);
        $this->display();
    }

    /**
     * @return    商家线下充值的记录
     */
    public function card_recharge_list()
    {
        $this->assign(D('Card_new')->offline_recharge_list(1));
        $this->display();
    }
    
    public function card_recharge_export(){
        $param = $_POST;
        $param['type'] = 'card_recharge';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
        die;
    }

    /**
     * @return  在线充值的记录
     */
    public function online_recharge_list()
    {
        $list = D('Card_new')->online_recharge_list(1, 'DISTINCT c.order_id,us.cardid,c.money,c.add_time,m.name,u.uid,us.nickname,us.phone');
        $this->assign($list);
        $this->display();
    }

    public function online_recharge_export(){
        $param = $_POST;
        $param['type'] = 'online_recharge';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
        die;
    }

    /**
     * @return  分润转换记录
     */
    public function fenrun_list()
    {
        $condition_where = '22=22 ';
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= " AND  `u`.`phone` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            }
        }
        $condition_where .= $_GET['type'] ? ' AND o.type = ' . $_GET['type'] : ' AND o.type = 1 ';

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_where .= " AND (o.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $order_count = D('User_fenrun_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->count();
        $all_fenrun = D('User_fenrun_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->sum('o.fenrun_money');
        import('@.ORG.system_page');
        $p = new Page($order_count, 20);
        $order_list = D('User_fenrun_list')->field('o.*,u.nickname,u.phone')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->order('o.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $this->assign('order_list', $order_list);
        $this->assign('all_fenrun', $all_fenrun);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function system_fenrun_list()
    {
        $condition_where = '22=22 ';

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_where .= " AND (add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $order_count = D('System_fenrun_list')->where($condition_where)->count();

        import('@.ORG.system_page');
        $p = new Page($order_count, 20);
        $order_list = D('System_fenrun_list')->where($condition_where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $fenrun_yesterday = false;
        if (M('System_fenrun_list')->where(array('record_time' => strtotime(date("Y-m-d", strtotime("-1 day")))))->find()) {
            $fenrun_yesterday = true;
        }
        $all_date['fenrun_yesterday'] = $fenrun_yesterday;
        if ($fenrun_yesterday) {
            $all_date['all_score'] = D('Fenrun')->get_fenrun_score(false, 1);
            $all_date['all_money'] = D('Fenrun')->get_system_take(1);
        } else {
            $all_date['all_score'] = D('Fenrun')->get_fenrun_score(false);
            $all_date['all_money'] = D('Fenrun')->get_system_take();

        }
        $this->assign('all_date', $all_date);

        $this->assign('order_list', $order_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    //获取百分比
    public function ajax_get_fenrun_percent()
    {

        $this->success(sprintf("%.2f", $_SESSION['percent']));
        exit;
    }

    public function award_list()
    {
        $condition_where = '22=22 ';
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= " AND  `u`.`phone` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            }

        }
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_where .= " AND (o.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $order_count = D('Fenrun_recommend_award_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->count();

        import('@.ORG.system_page');
        $p = new Page($order_count, 20);
        $order_list = D('Fenrun_recommend_award_list')->field('o.*,u.nickname,u.phone')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->order('o.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($order_list as &$v) {
            if ($v['type'] == 1) {
                $v['spread_info'] = D('User')->get_user($v['type_id']);
            } elseif ($v['type'] == 2) {
                $v['spread_info'] = D('Merchant')->get_info($v['type_id']);
            }
        }

        $this->assign('order_list', $order_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function free_award_list()
    {
        $condition_where = '22=22 ';
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= " AND  `u`.`phone` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            }

        }
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_where .= " AND (o.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $order_count = D('Fenrun_free_award_money_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->count();

        import('@.ORG.system_page');
        $p = new Page($order_count, 20);
        $order_list = D('Fenrun_free_award_money_list')->field('o.*,u.nickname,u.phone')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->order('o.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($order_list as &$v) {
            if ($v['type'] == 1) {
                $v['spread_info'] = D('User')->get_user($v['type_id']);
            } elseif ($v['type'] == 2) {
                $v['spread_info'] = D('Merchant')->get_info($v['type_id']);
            }
        }

        $this->assign('order_list', $order_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function score_all_list()
    {
        $condition_where = '22=22 ';
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where .= " AND  `u`.`phone` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            }

        }
        $condition_where .= $_GET['type'] ? ' AND o.type = ' . $_GET['type'] : ' AND o.type = 1 ';
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_where .= " AND (o.time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $order_count = D('User_score_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->count();
        import('@.ORG.system_page');
        $p = new Page($order_count, 20);
        $order_list = D('User_score_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->order('o.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('order_list', $order_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }


    public function score_all_export()
    {
        $param = $_POST;
        if ($_POST['type']) {
            $param['type_info'] = $_POST['type'];
        }
        $param['type'] = 'score_all';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        $param['system_session']['area_id'] = $this->system_session['area_id'];

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
    }


    public function fenrun_by_admin()
    {
        if (M('System_fenrun_list')->where(array('record_time' => strtotime(date("Y-m-d", strtotime("-1 day")))))->find()) {
            $this->error("昨日已经分润成功，不能再进行分润了");
        }
        if (C('config.open_score_fenrun') && C('config.auto_fenrun')) {
            $this->error("您开启了自动分润，不能再手动分润了");
        }
        $Fenrun_model = D('Fenrun');
        $user_list = $Fenrun_model->get_fenrun_user();
        //$_SESSION['percent'] = 0;
        //$count = count($user_list);
        set_time_limit(0);
        if (!empty($user_list)) {
            foreach ($user_list as $key => $v) {
                //$_SESSION['percent']=($key+1)/$count*100;
                $Fenrun_model->fenrun($v['uid']);
            }
            $result = $Fenrun_model->save_today_fenrun_income_date(2);
        }
        if ($result)
            $this->success('分润成功');
        else
            $this->error('昨天没有可分润数据');
    }

    /**
     * @return  管理员充值列表
     */
    public function admin_recharge_list()
    {
//        $recharge_list = M('User_money_list')->where(array('admin_id'=>array('neq','')))->select();
        $admin_list = M('Admin')->where(array('status' => 1))->select();
        $this->assign('admin_list', $admin_list);
        $where['l.admin_id'] = array('neq', 0);
        if (!empty($_GET['admin_id'])) {
            if ($_GET['admin_id'] == '0') {
                $where['l.admin_id'] = array('neq', 0);
            } else {
                $where['l.admin_id'] = $_GET['admin_id'];
            }
        }
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $where['_string'] = " (l.time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

        }

        if ($_GET['searchtype'] && $_GET['keyword']) {
            switch ($_GET['searchtype']) {
                case 'nickname':
                    $where['u.nickname'] = ['LIKE', '%' . $_GET['keyword'] . '%'];
                    break;
                case 'phone':
                    $where['u.phone'] = trim($_GET['keyword']);
                    break;
                default:
                    break;
            }
        }
        $recharge_list = D('User_money_list')->get_admin_recharge_list($where, 1);

        $this->assign($recharge_list);
        $this->display();
    }

    public function dellevel()
    {

        if (IS_POST) {
            $user_level = M('User_level');
            /**改软删除*4禁用***/
//            if(M('User')->where(array('level'=>$_POST['lid']))->data(array('level'=>0))->save()){
            M('User')->where(array('level' => $_POST['lid']))->data(array('level' => 0))->save();

            if ($user_level->where(array('id' => $_POST['lid']))->delete()) {
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！请重试~');
            }
//            }else{
//                $this->error('用户等级删除失败，请重试');
//            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function select_user()
    {
        if (!empty($_POST['search'])) {
            $condition_user['_string'] = "nickname like '%" . $_POST['search'] . "%' OR phone like '%" . $_POST['search'] . "%'";
        }
        $condition_user['openid'] = array('neq', '');
//        $condition_user['openid'] = array('notlike','%no_use');
        //排序
        $order_string = '`uid` DESC';
        $database_user = D('User');
        $count_user = $database_user->where($condition_user)->count();
        import('@.ORG.system_page');
        $p = new Page($count_user, 5);
        $user_list = $database_user->field(true)->where($condition_user)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();

        $this->assign('list', $user_list);
        $this->assign('page', $p->show());

        if ($_POST['tpl']) {

            $this->assign('param', $_POST);
            $this->display($_POST['tpl']);

        } else {

            $this->display();
        }

    }

    public function ajax_get_user()
    {
        $_POST['search_val'] = htmlspecialchars($_POST['search_val']);
        if ($_POST['search_val']) {

            $where['openid'] = array('neq', '');
            $where['_string'] = "nickname like '%" . $_POST['search_val'] . "%' OR phone like '%" . $_POST['search_val'] . "%' OR uid = {$_POST['search_val']}";
            $res = M('User')->field('nickname,phone,openid,avatar')->where($where)->limit(10)->select();
//			$this->ajaxReturn($res);
            $this->assign('list', $res);
        }
        $this->display('select_user');

    }

    public function score_pay_back_list()
    {
        $model = D('Merchant_money_list');
        $now_time = time();
        $today_time_zero = strtotime(date('Y-m-d', $now_time));;
        $today_time_condition['_string'] = "use_time>{$today_time_zero} AND use_time<{$now_time}";
        $today_system_take = $model->get_system_take_by_condition($today_time_condition);
        $yesterday_time_zero = $today_time_zero - 86400;
        $yesterday_time_condition['_string'] = "use_time<{$today_time_zero} AND use_time>{$yesterday_time_zero}";
        $yesterday_system_take = $model->get_system_take_by_condition($yesterday_time_condition);
        $tomorrow = $today_time_zero + 86400;
        $yesterday_money_condition['_string'] = "time>{$today_time_zero} AND time<{$tomorrow}";
        $yesterday_money_condition['ask'] = 3;
        $yesterday_pay_back = M('User_money_list')->where($yesterday_money_condition)->sum('money');

        $condition_where['_string'] = '1=1 ';
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'name') {
                $condition_where['_string'] .= " AND `u`.`nickname` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            } elseif ($_GET['searchtype'] == 'phone') {
                $condition_where['_string'] .= " AND  `u`.`phone` like '%" . htmlspecialchars($_GET['keyword']) . "%'";
            }

        }

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_where['_string'] .= " AND (o.time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }


        $condition_where['is_pay_back'] = 1;
        $order_count = D('User_score_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->count();
        import('@.ORG.system_page');
        $p = new Page($order_count, 20);
        $order_list = D('User_score_list')->field('o.*,u.nickname,u.phone')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->order('o.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $this->assign('order_list', $order_list);
        $this->assign('today_system_take', floatval($today_system_take));
        $this->assign('yesterday_system_take', floatval($yesterday_system_take));
        $this->assign('yesterday_pay_back', floatval($yesterday_pay_back));
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        $this->display();
    }

    //代理商记录
    public function agent_log()
    {
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition_user['a.uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        $condition_user['type'] = 2;
        $agent_count = M('Distributor_agent')->join('as a inner JOIN ' . C('DB_PREFIX') . 'user as u ON u.uid = a.uid')->where($condition_user)->count();
        import('@.ORG.system_page');
        $p = new Page($agent_count, 20);
        $agent_list = M('Distributor_agent')->join('as a inner JOIN ' . C('DB_PREFIX') . 'user as u ON u.uid = a.uid')->where($condition_user)->group('a.uid')->field('a.uid,u.nickname,u.phone')->limit($p->firstRow, $p->listRows)->select();

        foreach ($agent_list as &$v) {
            $v['spread_money'] = D('Distributor_agent')->get_all_agent_money('uid', $v['uid']);
            $v['counts'] = M('Agent_spread_log')->where(array('uid' => $v['uid']))->count();
        }

        $pagebar = $p->show();

        $all_spread_money = D('Distributor_agent')->get_all_agent_money();
        $this->assign('all_spread_money', $all_spread_money);
        $this->assign('pagebar', $pagebar);
        $this->assign('agent_list', $agent_list);

        $this->display();
    }

    public function agent_merchant_list()
    {

        $condition_user['uid'] = $_GET['uid'];
        $agent_count = M('Agent_spread_log')->where($condition_user)->count();
        import('@.ORG.system_page');

        $p = new Page($agent_count, 20);
        unset($condition_user['uid']);
        $condition_user['a.uid'] = $_GET['uid'];
        $agent_list = M('Agent_spread_log')->join('as a LEFT JOIN ' . C('DB_PREFIX') . 'merchant as m ON m.mer_id = a.mer_id')->where($condition_user)->field('m.mer_id,m.name,m.phone')->limit($p->firstRow, $p->listRows)->select();

        foreach ($agent_list as &$v) {
            $v['spread_money'] = D('Distributor_agent')->get_all_agent_money('mer_id', $v['mer_id']);
        }

        $pagebar = $p->show();
        $user = D('User')->get_user($_GET['uid']);
        $this->assign('user', $user);
        $this->assign('pagebar', $pagebar);
        $this->assign('agent_list', $agent_list);
        $this->display();
    }

    public function agent_money_list()
    {
        $money_list = D('Distributor_agent')->get_agent_spread_list($_GET['uid']);
        foreach ($money_list['money_list'] as &$v) {
            $user = D('User')->get_user($v['uid']);
            $mer = D('Merchant')->get_info($v['mer_id']);
            $v['nickname'] = $user['nickname'];
            $v['name'] = $mer['name'];
        }
        $money_list['user'] = D('User')->get_user($_GET['uid']);
        $this->assign('money_list', $money_list);

        $this->display();
    }

    //分销员 代理商下一级
    public function agent_next_log()
    {
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $condition_user['uid'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'nickname') {
                $condition_user['nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_user['phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

        $condition_user['openid'] = array('notlike', '%no_use');
        //排序
        $order_string = '`uid` DESC';
        if ($_GET['sort']) {
            switch ($_GET['sort']) {
                case 'uid':
                    $order_string = '`uid` DESC';
                    break;
                case 'lastTime':
                    $order_string = '`last_time` DESC';
                    break;
                case 'money':
                    $order_string = '`now_money` DESC';
                    break;
                case 'score':
                    $order_string = '`score_count` DESC';
                    break;
            }
        }

        //状态
        $condition_user['u.status'] = 1;

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_user['_string'] = " (u.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $database_user = D('User');
        if ($_GET['action'] == 'agent') {
            $count_user = $database_user->join(' as u RIGHT JOIN ' . C('DB_PREFIX') . 'distributor_agent l ON l.uid=u.uid AND l.type=2')->where($condition_user)->group('l.uid')->count();
        } else {
            $count_user = $database_user->join(' as u RIGHT JOIN ' . C('DB_PREFIX') . 'distributor_agent l ON l.uid=u.uid AND l.type=1')->where($condition_user)->group('l.uid')->count();
        }
        import('@.ORG.system_page');
        $p = new Page($count_user, 20);
        if ($_GET['action'] == 'agent') {
            $user_list = $database_user->join(' as u RIGHT JOIN ' . C('DB_PREFIX') . 'distributor_agent l ON l.uid=u.uid AND l.type=2')->field('u.*')->where($condition_user)->order($order_string)->group('l.uid')->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $user_list = $database_user->join(' as u RIGHT JOIN ' . C('DB_PREFIX') . 'distributor_agent l ON l.uid=u.uid AND l.type=1')->field('u.*')->where($condition_user)->order($order_string)->group('l.uid')->limit($p->firstRow . ',' . $p->listRows)->select();
        }


        foreach ($user_list as &$v) {
            $spread_where['uid'] = $v['uid'];
            $spread_where['status'] = 1;
            $v['spread_money'] = M('User_spread_list')->where($spread_where)->sum('money');

        }

        $this->assign('user_list', $user_list);

        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function agent_user()
    {
        $uid = $_GET['uid'];
        $openid = $_GET['openid'];
        if (empty($_GET['from_uid'])) {
            $_GET['from_uid'] = $_GET['uid'];
        }

        $now_user = D('User')->get_user($_GET['from_uid']);
        $user_list = D('User_spread')->get_spread_user($openid, $uid);

        if (!empty($user_list)) {

            foreach ($user_list['spread_user_list'] as $key => &$value) {
                if ($value['openid'] == '') {
                    unset($user_list['spread_user_list'][$key]);
                }
            }
        }

        $this->assign('user_list', $user_list['spread_user_list']);

        $this->assign('now_user', $now_user);
        $this->display();
    }

    //充值获得等级
    public function recharge_level()
    {
        $list = M('Recharge_level')->order('status DESC,level DESC')->select();
        $tmparr = M(_view('User_level'))->field(true)->getField('level,lname');
        $this->assign('list', $list);
        $this->assign('level', $tmparr);
        $this->display();
    }

    public function add_recharge_level()
    {
        $id = I('id');
        $recharge_level = M('Recharge_level')->where(array('id' => $id))->find();
        $tmparr = M(_view('User_level'))->field(true)->order('`id` ASC')->select();

        $this->assign('level', $tmparr);
        if (IS_POST) {
            if ($recharge_level) {
                $res = M('Recharge_level')->where(array('id' => $_POST['id']))->save($_POST);
            } else {
                if (M('Recharge_level')->where(array('level' => $_POST['level']))->find()) {
                    $this->error('该等级已经存在 ，不能重复添加');
                }
                $res = M('Recharge_level')->add($_POST);
            }

            if ($res) {
                $this->success('保存成功！');
            } else {
                $this->error('保存失败！');
            }
        } else {

            $this->assign('recharge_level', $recharge_level);
            $this->display();
        }
    }

    public function del_recharge_level()
    {
        if (IS_POST) {
            $user_level = M('Recharge_level');
            if ($user_level->where(array('id' => $_POST['id']))->delete()) {
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！请重试~');
            }

        } else {
            $this->error('非法提交,请重新提交~');
        }
    }


    // 导出积分返现记录
    public function score_pay_back_export()
    {
        $param = $_POST;
        $param['type'] = 'score_pay_back';
        $param['rand_number'] = time();
        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
        die;
    }

    /*
     * type  1 上级 2 下级
     * */
    public function ajax_edit_spread()
    {
        if (IS_POST) {
            $change_user = D('User')->get_user($_POST['spread_uid']);
            $now_user = D('User')->get_user($_POST['uid']);
            if (empty($now_user)) {
                $this->error('当前用户不存在');
            }
            if (empty($change_user)) {
                $this->error('替换用户不存在');
            }

            if (empty($change_user['openid']) && empty($change_user['wxapp_openid'])) {
                $this->error('替换用户未绑定微信或小程序');
            }

            if (empty($now_user['openid']) && empty($now_user['wxapp_openid'])) {
                $this->error('当前用户未绑定微信或小程序');
            }


            fdump($_POST);


            if ($_POST['type'] == 1) {
                $where['_string'] = "spread_uid = '{$now_user['uid']}' and uid=".$_POST['spread_uid'];
                $next_spread = M('User_spread')->where($where)->find();

                if ($next_spread) {
                    $this->error('替换用户已经是该用户下级了，不能是在成为该用户上级');
                }
                $where['_string'] = "uid = '{$now_user['uid']}'";
                $date['spread_openid'] = $change_user['openid'] ? $change_user['openid'] : $change_user['wxapp_openid'];
                $date['spread_uid'] = $change_user['uid'];
                if (M('User_spread')->where($where)->find()) {
                    $res = M('User_spread')->where($where)->save($date);
                    D("System_log")->add_log("修改了用户上级",'用户信息',$_POST['uid']);
                } else {
                    $date['openid'] = $now_user['openid'] ? $now_user['openid'] : $now_user['wxapp_openid'];
                    $date['uid'] = $now_user['uid'];
                    $res = M('User_spread')->add($date);
                    D("System_log")->add_log("增加了用户上级",'用户信息',$_POST['uid']);
                }

            } else {

                $where['_string'] = "spread_uid = '{$change_user['uid']}' and uid=".$now_user['uid'];
                $pre_spread = M('User_spread')->where($where)->find();

                if ($pre_spread) {
                    $this->error('替换用户已经是该用户上级了，不能是在成为该用户下级');
                }

                
                $where['_string'] = "uid = '{$change_user['uid']}'";

                

                $date['spread_openid'] = $now_user['openid'] ? $now_user['openid'] : $now_user['wxapp_openid'];
                $date['spread_uid'] = $now_user['uid'];

                if (M('User_spread')->where($where)->find()) {

                    $res = M('User_spread')->where($where)->save($date);
                    D("System_log")->add_log("修改了用户下级",'用户信息',$_POST['uid']);
                } else {
                    $date['openid'] = $change_user['openid'] ? $change_user['openid'] : $change_user['wxapp_openid'];
                    $date['uid'] = $change_user['uid'];
                    $res = M('User_spread')->add($date);
                    D("System_log")->add_log("增加了用户下级",'用户信息',$_POST['uid']);
                }
                fdump($res);
            }

            if ($res) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！');
            }
        } else {
//            $now_user = D('User')->get_user($_GET['uid']);
//            if($now_user['wxapp_openid']){
//
//                $where['_string'] = "openid = '{$now_user['openid']}' OR (openid='{$now_user['wxapp_openid']}' AND is_wxapp = 1)";
//            }else{
//                $where['_string'] = "openid = '{$now_user['openid']}'";
//
//            }
//            $pre_spread = M('User_spread')->where($where)->find();
//
//            $pre_user = D('User')->get_user($pre_spread['spread_openid'],'openid');
//            $where['_string'] = "spread_openid = '{$now_user['openid']}'";
//            $next_spread = M('User_spread')->where($where)->find();
//
//            $next_user = D('User')->get_user($next_spread['openid'],'openid');
//
//            $this->assign('next_user',$next_user);
//            $this->assign('pre_user',$pre_user);
            $this->display();
        }
    }


    public function edit_spread()
    {
        if (!empty($_POST['search'])) {
            $condition_user['_string'] = "nickname like '%" . $_POST['search'] . "%' OR phone like '%" . $_POST['search'] . "%'";
        }
        // $condition_user['openid'] = array('neq', '');
//        $condition_user['openid'] = array('notlike','%no_use');
        //排序
        $order_string = '`uid` DESC';
        $database_user = D('User');
        $count_user = $database_user->where($condition_user)->count();
        import('@.ORG.system_page');
        $p = new Page($count_user, 5);
        $user_list = $database_user->field(true)->where($condition_user)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        if (!empty($_SESSION['spread_uid'])) {
            $_POST['uid'] = $_SESSION['spread_uid'];
        }
        if (I('uid')) {
            $_SESSION['spread_uid'] = I('uid');
            $_POST['uid'] = $_SESSION['spread_uid'];
        }
        $this->assign('param', $_POST);
        $now_user = D('User')->get_user($_POST['uid']);

        foreach ($user_list as &$v) {
            $where['_string'] = "spread_uid = '{$v['uid']}' AND uid='{$now_user['uid']}'";
            if (M('User_spread')->where($where)->find()) {
                $v['is_pre'] = true;
            }

            $where['_string'] = "spread_uid = '{$now_user['uid']}' AND uid='{$v['uid']}'";
            if (M('User_spread')->where($where)->find()) {
                $v['is_next'] = true;
            }
        }

        $this->assign('list', $user_list);
        $this->assign('page', $p->show());


        $this->display();
    }

    public function ajax_get_users()
    {
        $_POST['search_val'] = htmlspecialchars($_POST['search_val']);
        if ($_POST['search_val']) {
//            $where['openid'] = array('neq','');

            $where['_string'] = "uid = {$_POST['search_val']}";
            $res = M('User')->field('uid,nickname,phone,openid,avatar,status,wxapp_openid')->where($where)->find();
            fdump(M(), 'ressss');
            if (empty($res)) {
                $this->error('该用户不存在');
            }
            if ($res['uid'] == $_POST['uid']) {
                $this->error('推广用户上下级不能是修改用户自己');
            }
            if ($res['status'] != 1) {
                $this->error('该用户状态不正常');
            }
            if ($res['openid'] == '') {
                $this->error('该用户未绑定微信');
            }

            $this->success($res);
        }

    }

    public function config()
    {
        redirect(U('Config/index', array('galias' => 'user', 'header' => 'User/config')));
    }


    // 分销人员列表
    public function distributor()
    {
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'pigcms_id') {
                $condition_distributor['d.pigcms_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'name') {
                $condition_distributor['d.name'] = array('like', '%' . $_GET['keyword'] . '%');
            } else if ($_GET['searchtype'] == 'phone') {
                $condition_distributor['d.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }


        if($_GET['project_city_id']){
            $condition_distributor['d.project_city_id'] = $_GET['project_city_id'];
        }
        if($_GET['project_id']){
            $condition_distributor['d.project_id'] = $_GET['project_id'];
        }

        //状态
        if ($_GET['status'] != '') {
            $condition_distributor['d.status'] = $_GET['status'];
        }
       

        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $condition_distributor['_string'] = " (d.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $database_distributor = D('Distributor');
        $count = $database_distributor->join(' as d left join '.C('DB_PREFIX').'user as u on u.uid=d.uid left join '.C('DB_PREFIX').'appoint_project as p on p.id=d.project_id')->where($condition_distributor)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $list = $database_distributor->field('d.*,p.project_name,u.nickname')->join(' as d left join '.C('DB_PREFIX').'user as u on u.uid=d.uid left join '.C('DB_PREFIX').'appoint_project as p on p.id=d.project_id')->where($condition_distributor)->order('d.pigcms_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if (!empty($list)) {
            $user_city_ids = array_filter(array_unique(array_column($list,'city_id')));
            $user_city_arr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$user_city_ids]])->select(),'area_name','area_id');
            foreach ($list as &$value) {
                $value['city_name'] = $user_city_arr[$value['city_id']];
            }
        }

        // 所属项目城市
        $project_city_arr = D('Appoint_project_city')->select();
        if ($_GET['project_city_id']) {
            $project_city = D('Appoint_project_city')->where(array('id'=>$_GET['project_city_id']))->find();
            // 当前城市下项目
            $project_arr = M('Appoint_project')->where(array('city_name'=>$project_city['project_city_name']))->select();
            $this->assign('project_arr', $project_arr);
        }

        $this->assign('project_city_arr', $project_city_arr);
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    //根据城市得到符合的项目
    public function ajax_get_project(){
        $city_name = $_POST['city_name'];
        if(!empty($city_name)){
            $project = M('Appoint_project')->where(array('city_name'=>$city_name))->select();
        }
        if(!$project){
            $project = array();
        }
        echo json_encode($project);
    }

    // 分销人员列表
    public function distributor_edit()
    { 
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id'],'is_open'=>1))->find();
            $this->assign('now_area',$now_area);
            if ($now_area['area_type'] == 3) {
                // 区域管理员不能查看
                $this->frame_error_tips('您没有权限添加');
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
                $_GET['city_id']  = $now_area['area_id'];
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
                $_GET['province_id'] =$now_area['area_id'];
            }
        }

        if (IS_POST) {
            $pigcms_id = intval($_POST['pigcms_id']);
            if (!$pigcms_id) {
                $this->success('ID不存在');
            }

            $now_distributor = D('Distributor')->where(array('pigcms_id'=>$pigcms_id))->find();
            if (!$now_distributor) {
                $this->success('信息不存在');
            }

            $_POST['audit_time'] = time();
            if ($pigcms_id) {
                $save = D('Distributor')->where(array('pigcms_id'=>$pigcms_id))->save($_POST);
            }else{
                $save = D('Distributor')->add($_POST);
            }
            if ($save) {
                $this->success('审核成功！');
            }else{
                $this->error('审核失败！');
            }
        }


        if ($_GET['pigcms_id']) {

            $now_distributor = D('Distributor')->where(array('pigcms_id'=>$_GET['pigcms_id']))->find();
            $now_province = D('Area')->where(array('area_id'=>$now_distributor['province_id']))->find();
            $now_city = D('Area')->where(array('area_id'=>$now_distributor['city_id']))->find();
            $now_project = M('Appoint_project')->where(array('id'=>$now_distributor['project_id']))->find();
            $now_project_city = M('Appoint_project_city')->where(array('project_city_name'=>$now_project['city_name']))->find();
            $this->assign('now_province',$now_province);
            $this->assign('now_city',$now_city);
            $this->assign('now_project',$now_project);
            $this->assign('now_project_city',$now_project_city);
            $this->assign('now_distributor',$now_distributor);
            // 当前省份下城市
            $city_arr =  D('Area')->get_arealist_by_areaPid($now_distributor['province_id'],true);
            // 当前城市下项目
            $project_arr = M('Appoint_project')->where(array('city_name'=>$now_project['city_name']))->select();
        }
        $province_arr = D('Area')->where(array('is_open'=>1,'area_type'=>1))->select();
        $project_city_arr = D('Appoint_project_city')->select();
        $this->assign('province_arr',$province_arr);
        $this->assign('project_arr',$project_arr);
        $this->assign('city_arr',$city_arr);
        $this->assign('project_city_arr',$project_city_arr);
        $this->display();
    }

    public function distributor_spread_list()
    {

        if(!empty($_GET['keyword'])){
           if ($_GET['searchtype'] == 'name') {
                $condition_where['u.nickname']= array( 'like','%'.htmlspecialchars($_GET['keyword']) .'%');
            } elseif ($_GET['searchtype'] == 'buy_nickname') {
                $condition_where['u2.nickname']= array( 'like','%'.htmlspecialchars($_GET['keyword']) .'%');
            }elseif ($_GET['searchtype'] == 'phone') {
                $condition_where['u.phone']=  htmlspecialchars($_GET['keyword']);
            }
        }

        $status = isset($_GET['status']) ? intval($_GET['status']) : -1;
        $type = isset($_GET['order_type']) && $_GET['order_type'] ? $_GET['order_type'] : '';

        $order_sort = 'l.pigcms_id DESC';

        if ($status != -1) {
            $condition_where ['l.status']= $status;
        }

        if ($type) {
            $condition_where['l.order_type']= $type;
        }


        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $time_type = $_GET['timetype'] ? 'l.'.$_GET['timetype'] : 'l.check_time';

            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $condition_where['_string']= $time_type." BETWEEN ".$period[0].' AND '.$period[1];
            //$condition_where['_string']=$time_condition;
        }

        $condition_where['l.spread_uid'] = $_GET['uid'];

        $order_count = D('Distributor_spread_list')->join('as l LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid = l.spread_uid')->where($condition_where)->count();
        import('@.ORG.system_page');
        $p = new Page($order_count,30);
        $un_spread_list = D('Distributor_spread_list')->field('l.*,u.nickname,u.phone')->join('as l LEFT JOIN '.C('DB_PREFIX').'user u ON u.uid = l.spread_uid LEFT JOIN '.C('DB_PREFIX').'user u2 ON u2.uid = l.order_uid')->where($condition_where)->order($order_sort)->limit($p->firstRow.','.$p->listRows)->select();
        $uids = $orderids = $groupids = array();
        foreach ($un_spread_list as $row) {
            if (!in_array($row['spread_uid'], $uids)) $uids[] = $row['spread_uid'];
            if (!in_array($row['order_uid'], $uids)) $uids[] = $row['order_uid'];
            if ($row['order_type'] == 'group') {
                if (!in_array($row['order_id'], $orderids)) $orderids[] = $row['order_id'];
                if (!in_array($row['third_id'], $groupids)) $groupids[] = $row['third_id'];
            }
        }
        if ($uids) {
            $users = D('User')->where(array('uid' => array('in', $uids)))->select();
            $user_list = array();
            foreach ($users as $u) {
                $user_list[$u['uid']] = $u;
            }
        }
       
        $list = array();
        foreach ($un_spread_list as $un) {
            $str = '';
            if ($user_list[$un['spread_uid']]>0) {
                $un['get_nickname'] = $user_list[$un['spread_uid']]['nickname'];//佣金获得者
                $un['get_phone'] = $user_list[$un['spread_uid']]['phone'];//佣金获得者
                $str = '由<font color="green"> 【' . ($user_list[$un['spread_uid']]['nickname']?$user_list[$un['spread_uid']]['nickname']:$user_list[$un['spread_uid']]['phone']). '】 </font>分享出去后，';
            }
            if ($user_list[$un['order_uid']]>0) {
                $un['buy_nickname'] = $user_list[$un['order_uid']]['nickname'];//购买人
                $un['buy_phone'] = $user_list[$un['order_uid']]['phone'];//购买人
                $str .= '被 <font color="green"> 【' . ($user_list[$un['order_uid']]['nickname']?$user_list[$un['order_uid']]['nickname']:$user_list[$un['order_uid']]['phone']). '】 </font>通过分享购买';
            }

            $un['txt'] = $str;

            $list[] = $un;
        }
        $this->assign('list', $list);
        // var_dump($list);

        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign('type_name',$this->get_alias_c_name());
        $this->display();
    }
    public  function get_alias_c_name(){
        return array(
            'all'=>L_('选择分类'),
            'group'=>C('config.group_alias_name'),
            'mall'=>C('config.mall_alias_name'),
            'appoint'=>C('config.appoint_alias_name')
        );
    }

    //分销记录导出
    public function distributor_spread_export()
    {
        $param = $_POST;
        $param['type'] = 'distributor_spread';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        // $param['system_session']['area_id'] = $this->system_session['area_id'] ;

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
    }

    /**用户等级激活码管理-功能区块*/
    /**等级激活码列表*/
    public function activation_code_list() {
        $level_id = $_GET['level_id']>0 ? $_GET['level_id'] : '';
        $status = $_GET['status'] ? $_GET['status'] : -1;

        $where = array();

        if ($level_id) {
            $where['level_id'] = $level_id;
        }

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $where['uid'] = intval($_GET['keyword']);
            } else if ($_GET['searchtype'] == 'code_id') {
                $where['code_id'] = intval($_GET['keyword']);
            } else if ($_GET['searchtype'] == 'mer_id') {
                $where['mer_id'] = intval($_GET['keyword']);
            } else if ($_GET['searchtype'] == 'activation_code') {
                $where['activation_code'] = $_GET['keyword'];
            }
        }

        // 激活时间范围查询
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            $begin_time = strtotime($_GET['begin_time'] . " 00:00:00");
            $end_time = strtotime($_GET['end_time'] . " 23:59:59");
            if ($begin_time > $end_time) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array($begin_time, $end_time);
            $where['_string'] = " (a.last_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        // 查询全部 status -1 查询未激活 status -2  激活 1 禁用 2 失效 3
        if ($status) {
            $where['status'] = $status;
        }

        $code_listDb = D('User_level_activation_code');

        $activation_code_list = $code_listDb->activation_code_list($where);


        $this->assign($activation_code_list);


        $this->display();
    }

    /**导出数据*/
    public function activation_code_export()
    {
        $param = $_POST;
        $param['type'] = 'activation_code';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
    }


    /**批量生成等级激活码*/
    public function add_activation_code() {
        if(IS_POST){
            set_time_limit(0);
            $number =  $_POST['number']>0 ? $_POST['number'] : '';
            $mer_id =  $_POST['mer_id']>0 ? $_POST['mer_id'] : '';
            $level_id =  $_POST['level_id']>0 ? $_POST['level_id'] : '';
            if ($number<=0 || !$number) {
                $this->error("请填写大于0的正整数！");
            }
            if ($level_id<=0 || !$level_id) {
                $this->error("请选择关联的等级！");
            }
            if ($mer_id) {
                $merchantDb = D('Merchant');
                $merchant =  $merchantDb->where(array('status'=>1, 'mer_id' => $mer_id))->find();
                if (!$merchant) {
                    $this->error("绑定商户ID有误！");
                }
            }
            $code_listDb = D('User_level_activation_code');
            $rand_arr = array();
            $data = array();
            if ($mer_id) {
                $msg['mer_id'] = $mer_id;
            } else {
                $msg = array();
            }
            if ($level_id) {
                $msg['level_id'] = $level_id;
            }
            $msg['add_time'] = $msg['last_time'] = time();
            for ( $i = 0; $i < $number; $i++ )  {
                $rand_number_msg = $this->rand_number(6);
                if ($rand_number_msg) {
                    $rand_arr[] = $rand_number_msg;
                    $msg['activation_code'] = $rand_number_msg;
                    $data[] = $msg;
                }
            }
            if ($data) {
                $info = $code_listDb->addAll($data);
                if ($info) {
                    // 添加操作记录
                    $log_info = implode(',', $rand_arr);
                    $log = array(
                        'type' => 0,
                        'log_type' => 1,
                        'uid' => $this->system_session['id'],
                        'log_post' => serialize($_POST),
                        'log_info' => $log_info,
                        'log_time' => time()
                    );
                    M('User_level_activation_code_log')->add($log);

                    $this->success('批量操作成功!');
                } else {
                    $this->error("添加失败，请重试！");
                }
            } else {
                $this->error("生成失败，请重试！");
            }
        } else {
            // 获取 用户等级
            $user_level = M('User_level')->select();
            if (empty($user_level)) {
                $this->error("请先添加会员等级！");
            }
            $this->assign('user_level', $user_level);
            $this->display();
        }
    }

    /**单个编辑激活码信息*/
    public function edit_activation_code() {
        if(IS_POST){
            set_time_limit(0);
            $mer_id =  $_POST['mer_id']>0 ? $_POST['mer_id'] : '';
            $uid =  $_POST['uid']>0 ? $_POST['uid'] : '';
            $level_id =  $_POST['level_id']>0 ? $_POST['level_id'] : '';
            $code_id = $_POST['code_id']>0?$_POST['code_id']:'';
            $status = $_POST['status']?$_POST['status']:'';
            if ($code_id<=0) {
                $this->error("激活码ID错误！");
            }
            if ($status && !in_array($status, array(-1,1,2,3))) {
                $this->error("请选择正确状态！");
            }
            $code_listDb = D('User_level_activation_code');
            $user_level = D('User_level');
            $userDb = D('User');
            $merchantDb = D('Merchant');
            if ($uid > 0) {
                $now_user =  $userDb->where(array('status'=>1, 'uid' => $uid))->find();
                if (!$now_user) {
                    $this->error("绑定用户ID有误！");
                }
            } else {
                $now_user = array();
            }
            if ($mer_id) {
                $merchant =  $merchantDb->where(array('status'=>1, 'mer_id' => $mer_id))->find();
                if (!$merchant) {
                    $this->error("绑定商户ID有误！");
                }
            }
            $code_info = $code_listDb->where(array('code_id' => $code_id, 'status' => array('neq', 4)))->find();
            if (!$code_info) {
                $this->error("激活码不存在或者已经被删除！");
            }
            if ($status==1 && !$code_info['uid'] && !$uid) {
                $this->error("请填写关联用户ID！");
            }
            if (!$level_id) {
                $level_id = $code_info['level_id'];
            }
            if ($status==-1 && ($code_info['status']==0 || $code_info['status']==1) && $uid>0) {
                // 如果上传状态为 未激活且原状态为 未激活  + 上传了绑定用户uid  默认激活 激活码
                $status = 1;
            }
            $user_level_info = $user_level->where(array('id' => $level_id))->find();
            if (!$user_level_info) {
                $this->error("会员等级不存在或者已经被删除！");
            }
            $code_msg = array();
            if ($uid>0) {
                $code_msg = $code_listDb->where(array('uid' => $uid, 'status' => array('neq', 4), 'code_id' => array('neq',$code_id)))->find();
                if ($code_msg && $now_user['level']>0) {
                    if ($code_msg['status']==1 && $now_user['level']==$user_level_info['level']) {
                        $this->error("您已是该激活码对应等级，请勿重新激活！");
                    } elseif ($code_msg['status']==1 && $now_user['level']>$user_level_info['level']) {
                        $this->error("操作失败，当前等级高于激活码等级！");
                    }
                }
            }


            $data = array();
            if ($mer_id) {
                $data['mer_id'] = $mer_id;
            } else {
                $data['mer_id'] = 0;
            }
            if ($level_id>0) {
                $data['level_id'] = $level_id;
            }
            if ($status) {
                if ($status==-1) {
                    $status = 0;
                }
                if ($status == 1) {
                    $data['activation_time'] = time();
                }
                $data['status'] = $status;
            }
            if ($uid>0) {
                $data['uid'] = $uid;
                if (!$status && $code_info['status'] == 0) {
                    $data['status'] = 1;
                }
            } else {
                $data['uid'] = 0;
            }
            $data['last_time'] = time();
            if ($data) {
                $info = $code_listDb->where(array('code_id' => $code_id))->data($data)->save();
                if ($info) {
                    $now_time = time();
                    //等级变化更新成为该等级的时间
                    if ($status != 0 && $now_user && $now_user['level'] != $user_level_info['level']) {
                        $data_user['level'] = intval($user_level_info['level']);
                        $data_user['level_time'] = $now_time;
                        $userDb->where(array('uid' => $uid))->data($data_user)->save();
                        // 记录激活对应获取积分
                        D('User_level_open_log')->add_reward($uid, $user_level_info, $this->system_session['id'], 0, 2, $now_time, 3, '管理员使用激活码开通会员等级');
                    }
                    // 如果 2,3 禁用或者失效了 清空等级
                    if ($status==2 || $status==3) {
                        $user_level_old = $user_level->where(array('id' => $code_info['level_id']))->find();
                        $now_user_old =  $userDb->where(array('uid' => $code_info['uid']))->find();
                        // 禁用或者失效了激活码，清除对应等级
                        if ($now_user_old['level'] == $user_level_old['level']) {
                            $data_user['level'] = 0;
                            $data_user['level_time'] = $now_time;
                            $userDb->where(array('uid' => $code_info['uid']))->data($data_user)->save();
                        }
                    }

                    // 清除多余的
                    if ($code_msg) {
                        $code_listDb->where(array('code_id' => $code_msg['code_id']))->save(array('status' => 3, 'uid' => '', 'activation_time'=>'', 'last_time' => time()));
                    }
                    // 如果原来有用户ID,然后清空，去除用户等级
                    if ($code_info['uid']>0 && $code_info['uid']!=$uid) {
                        // 如果存在绑定的用户。清除绑定等级
                        $user_level_old = $user_level->where(array('id' => $code_info['level_id']))->find();
                        $now_user_old =  $userDb->where(array('uid' => $code_info['uid']))->find();
                        // 删除了激活码，清除对应等级
                        if ($now_user_old['level'] == $user_level_old['level']) {
                            $data_user['level'] = 0;
                            $data_user['level_time'] = $now_time;
                            $userDb->where(array('uid' => $code_info['uid']))->data($data_user)->save();
                            if ($uid<=0) {
                                $set = array(
                                    'status' => 0,
                                    'activation_time' => ''
                                );
                                $code_listDb->where(array('code_id' => $code_id))->data($set)->save();
                            }
                        }
                    }

                    // 添加操作记录
                    $log = array(
                        'type' => 0,
                        'log_type' => 2,
                        'code_id' => $code_id,
                        'uid' => $this->system_session['id'],
                        'log_post' => serialize($_POST),
                        'log_info' => '平台管理员编辑了激活码',
                        'log_time' => $now_time
                    );
                    M('User_level_activation_code_log')->add($log);

                    $this->success('操作成功!');
                } else {
                    $this->error("添加失败，请重试！");
                }
            } else {
                $this->error("请检查是否进行修改，请重试！");
            }
        } else {
            $code_id = $_GET['code_id']>0?$_GET['code_id']:0;
            if ($code_id<=0) {
                $this->error("激活码ID错误！");
            }
            $code_listDb = D('User_level_activation_code');
            $code_info = $code_listDb->where(array('code_id' => $code_id, 'status' => array('neq', 4)))->find();
            if (!$code_info) {
                $this->error("激活码不存在或者已经被删除！");
            }
            if ($code_info['uid'] <= 0) {
                $code_info['uid'] = '';
            }
            if ($code_info['mer_id'] <= 0) {
                $code_info['mer_id'] = '';
            }

            $this->assign('code_info', $code_info);

            // 获取 用户等级
            $user_level = M('User_level')->select();
            if (empty($user_level)) {
                $this->error("请先添加会员等级！");
            }
            $this->assign('user_level', $user_level);
            $this->display();
        }
    }

    /**批量操作激活码*/
    public function activation_mutil(){
        if(IS_POST){
            // 去除前后空格 以逗号替换换行
            $codeids = remove_nl(trim($_POST['codeids']), ',');
			
            // 去除所有空格
            $codeids = str_replace(' ', '', $codeids);
            $mer_id = empty($_POST['mer_id'])?NULL:(int)$_POST['mer_id'];
            $is_mer_id_set = empty($_POST['is_mer_id_set'])?0:(int)$_POST['is_mer_id_set'];
            $level_id = empty($_POST['level_id'])?NULL:(int)$_POST['level_id'];
            $is_level_id_set = empty($_POST['is_level_id_set'])?0:(int)$_POST['is_level_id_set'];
            $status = empty($_POST['status'])?NULL:(int)$_POST['status'];
            $is_status_set = empty($_POST['is_status_set'])?0:(int)$_POST['is_status_set'];
            if (1==$is_status_set && $status && !in_array($status, array(-1,1,2,3))) {
                $this->error("请选择正确状态！");
            }
            $user_level = D('User_level');
            $merchantDb = D('Merchant');
            $user_level_info = $user_level->where(array('id' => $level_id))->find();
            if (1==$is_level_id_set && !$user_level_info) {
                $this->error("会员等级不存在或者已经被删除！");
            }
            if (1==$is_mer_id_set && $mer_id) {
                $merchant =  $merchantDb->where(array('status'=>1, 'mer_id' => $mer_id))->find();
                if (!$merchant) {
                    $this->error("绑定商户ID有误！");
                }
            }
            $where['activation_code']=array('in',$codeids);
            $code_listDb = D('User_level_activation_code');
            $data = array();
            if (1==$is_mer_id_set && $mer_id) {
                $data['mer_id'] = $mer_id;
            } elseif(1==$is_mer_id_set) {
                $data['mer_id'] = 0;
            }
            if (1==$is_level_id_set && $level_id>0) {
                $data['level_id'] = $level_id;
            }
            if ($status && 1==$is_status_set) {
                if ($status==-1) {
                    $status = 0;
                }
                if ($status == 1) {
                    $data['activation_time'] = time();
                }
                $data['status'] = $status;
            }
            $data['last_time'] = time();

            if(!$code_listDb->where($where)->data($data)->save()){
                $this->error('批量更新失败，请检查数据是否正确');
            }else{
                // 如果 2,3 禁用或者失效了 清空等级
                if ($status==2 || $status==3) {
                    $userDb = M('User');
                    $list = $code_listDb->where($where)->select();
                    foreach ($list as $val) {
                        $user_level_old = $user_level->where(array('id' => $val['level_id']))->find();
                        $now_user_old =  $userDb->where(array('uid' => $val['uid']))->find();
                        // 禁用或者失效了激活码，清除对应等级
                        if ($now_user_old['level'] == $user_level_old['level']) {
                            $data_user['level'] = 0;
                            $data_user['level_time'] = 0;
                            $userDb->where(array('uid' => $val['uid']))->data($data_user)->save();
                        }
                    }
                }
                $log_info = $codeids;
                // 添加操作记录
                $log = array(
                    'type' => 0,
                    'log_type' => 6,
                    'uid' => $this->system_session['id'],
                    'log_post' => serialize($_POST),
                    'log_info' => $log_info,
                    'log_time' => time()
                );
                M('User_level_activation_code_log')->add($log);
                $this->success('批量操作成功！');
            }
        } else{

            // 获取 用户等级
            $user_level = M('User_level')->select();
            if (empty($user_level)) {
                $this->error("请先添加会员等级！");
            }
            $this->assign('user_level', $user_level);
            $this->display();
        }
    }

    /**删除激活码*/
    public function del_activation_code() {
        $code_id = $_POST['code_id']>0?$_POST['code_id']:0;
        if ($code_id<=0) {
            $this->error("激活码ID错误！");
        }
        $code_listDb = D('User_level_activation_code');
        $code_info = $code_listDb->where(array('code_id' => $code_id, 'status' => array('neq', 4)))->find();
        if (!$code_info) {
            $this->error("激活码不存在或者已经被删除！");
        }
        $user_level = D('User_level');
        $user_level_info = $user_level->where(array('id' => $code_info['level_id']))->find();
        if ($code_listDb->where(array('code_id' => $code_id, 'status' => array('neq', 4)))->save(array('status' => 4, 'uid' => ''))) {
            // 如果存在绑定的用户。清除绑定等级
            $database_user = D('User');
            $condition_user['uid'] = intval($code_info['uid']);
            $now_user = $database_user->field(true)->where($condition_user)->find();
            // 删除了激活码，清除对应等级
            if ($now_user['level'] == $user_level_info['level']) {
                $data_user['level'] = 0;
                $data_user['level_time'] = 0;
                $database_user->where($condition_user)->data($data_user)->save();
            }


            // 添加操作记录
            $_POST['old_uid'] = $code_info['uid'];
            $log = array(
                'type' => 0,
                'log_type' => 4,
                'code_id' => $code_id,
                'uid' => $this->system_session['id'],
                'log_post' => serialize($_POST),
                'log_info' => '平台管理员删除了激活码',
                'log_time' => time()
            );
            M('User_level_activation_code_log')->add($log);

            $this->success('操作成功!');
        } else {
            $this->error("操作失败！");
        }
    }

    /**操作记录*/
    public function activation_code_log() {
        $code_listDb = D('User_level_activation_code');
        $where = array();

        $activation_code_log_list = $code_listDb->activation_code_log($where);


        $this->assign($activation_code_log_list);


        $this->display();
    }

    /**平台会员开通记录*/
    public function open_user_level_log() {
        $code_listDb = D('User_level_open_log');
        $where = array();
        $where['log_type'] = 2; // 当前只查询开通记录
        $where['_string'] = 'level > 0'; // 当前只查询等级大于零的操作

        // 激活开通时间范围查询
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            $begin_time = strtotime($_GET['begin_time'] . " 00:00:00");
            $end_time = strtotime($_GET['end_time'] . " 23:59:59");
            if ($begin_time > $end_time) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array($begin_time, $end_time);
            $where['_string'] .= " AND (open_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        // 查询全部 open_type 0 管理员 1 用户积分 2 用户余额 3 激活码
        if ($_GET['open_type'] == -2) {
            $where['open_type'] = 0;
        } elseif ($_GET['open_type'] > 0) {
            $where['open_type'] = $_GET['open_type'];
        }
        if ($_GET['uid']) {
            $where['uid'] = $_GET['uid'];
        }
        if ($_GET['level']) {
            $where['level'] = $_GET['level'];
        }


        $activation_code_log_list = $code_listDb->open_user_level_log($where);

        $this->assign($activation_code_log_list);

        $this->display();
    }

    /**导出数据*/
    public function open_level_log_export(){
        $param = $_POST;
        $param['type'] = 'open_level_log';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
    }


    /**余额开通平台会员，领取礼包-功能*/
    /**礼包管理页面*/
    public function level_gift() {
        $level_id = $_GET['level_id']>0 ? $_GET['level_id'] : '';
        $status = $_GET['status'] ? $_GET['status'] : -1;

        $where = array();

        if ($level_id) {
            $where['level_id'] = $level_id;
        }
        // 查询全部 status -1 查询未激活 status -2  激活 1 禁用 2 失效 3
        if ($status) {
            $where['status'] = $status;
        }

        $level_gift_listDb = D('User_level_gift');

        $level_gift_list = $level_gift_listDb->level_gift_list($where);



        $this->assign($level_gift_list);


        $this->display();
    }

    /**添加编辑礼包页面*/
    public function level_gift_add() {
        // 获取 用户等级
        $user_level = M('User_level')->select();
        if (empty($user_level)) {
			$this->frame_submit_tips(0,'请先添加会员等级');
        }
        $this->assign('user_level', $user_level);

        $m_user_level_gift = M('User_level_gift');
        if(IS_POST){
            if (!$_POST['gift_id'] && empty($_FILES)) {
				$this->frame_submit_tips(0,'请上传图片');
            }
            if (empty($_POST['gift_name'])) {
				$this->frame_submit_tips(0,'请填写礼包名称');
            }
            $other_msg = $m_user_level_gift->where(array('gift_name' => $_POST['gift_name'], 'gift_status' => array('neq', 3)))->find();
            if ($_POST['gift_id']!=$other_msg['gift_id'] && !empty($other_msg)) {
				$this->frame_submit_tips(0,'当前礼包名称已经存在');
            }
            if (empty($_POST['level_id'])) {
				$this->frame_submit_tips(0,'选择会员等级');
            }
            $level_info = M('User_level')->where(array('level_id' => $_POST['level_id']))->find();
            if (empty($level_info)) {
				$this->frame_submit_tips(0,'所选会员等级不存在或者已经被删除');
            }
            $other = $m_user_level_gift->where(array('level_id' => $_POST['level_id'], 'gift_status' => array('neq', 3)))->find();
            if ($_POST['gift_id']!=$other['gift_id'] && !empty($other)) {
				$this->frame_submit_tips(0,'当前所选会员等级礼包已经存在');
            }

            if($_FILES && $_FILES['gift_img']['error'] != 4 && $_FILES['gift_img']['size'] > 0){
                $image = D('Image')->handle($this->system_session['id'], 'level_gift', 0, array('size' => 10), false);
                if (!$image['error']) {
                    $_POST['gift_img'] = $image['url']['gift_img'];
                } else {
                    $this->error($image['msg'] ? $image['msg'] : $image['message']);
                }
            }
            $_POST['last_time'] = time();
			
			$gift_id = false;
            if ($_POST['gift_id']) {
                $gift_id = $m_user_level_gift->where(array('gift_id' => intval($_POST['gift_id'])))->save($_POST);
            } else {
                $_POST['add_time'] = time();
                $gift_id = $m_user_level_gift->data($_POST)->add();
            }
            if ($gift_id !== false) {
                $this->frame_submit_tips(1,'操作成功');
            } else {
				$this->frame_submit_tips(0,'操作失败');
            }

        } else {
            if ($_GET['gift_id']) {
                $level_gift = $m_user_level_gift->where(array('gift_id' => intval($_GET['gift_id'])))->find();
                if (empty($level_gift)) {
					$this->frame_submit_tips(0,'所编辑的礼包不存在或者已经被删除');
                }
                $this->assign('level_gift', $level_gift);
            }
            $this->display();
        }
    }

    /**删除礼包*/
    public function del_level_gift(){
        $gift_id = $_POST['gift_id']>0?$_POST['gift_id']:0;
        if ($gift_id<=0) {
            $this->error("会员等级礼包ID错误！");
        }
        $level_gift_listDb = D('User_level_gift');
        $gift_info = $level_gift_listDb->where(array('gift_id' => $gift_id, 'gift_status' => array('neq', 3)))->find();
        if (!$gift_info) {
            $this->error("会员等级礼包不存在或者已经被删除！");
        }
        if ($level_gift_listDb->where(array('gift_id' => $gift_id, 'gift_status' => array('neq', 3)))->save(array('gift_status' => 3))) {
            $this->success('操作成功!');
        } else {
            $this->error("操作失败！");
        }
    }

    /**礼包领取记录*/
    public function level_gift_order(){
        $level_id = $_GET['level_id']>0 ? $_GET['level_id'] : '';
        $status = $_GET['status'] ? $_GET['status'] : -1;

        $where = array();
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'uid') {
                $where['uid'] = intval($_GET['keyword']);
            } else if ($_GET['searchtype'] == 'order_id') {
                $where['order_id'] = intval($_GET['keyword']);
            } else if ($_GET['searchtype'] == 'get_name') {
                $where['get_name'] = intval($_GET['keyword']);
            } else if ($_GET['searchtype'] == 'get_phone') {
                $where['get_phone'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'gift_name') {
                $where['gift_name'] = $_GET['keyword'];
            }
        }
        // 激活时间范围查询
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            $begin_time = strtotime($_GET['begin_time'] . " 00:00:00");
            $end_time = strtotime($_GET['end_time'] . " 23:59:59");
            if ($begin_time > $end_time) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array($begin_time, $end_time);
            $where['_string'] = " (a.get_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }

        if ($level_id) {
            $where['level_id'] = $level_id;
        }
        // 查询 全部 status -1 查询待领取 status -2  领取 1 发货 2
        if ($status) {
            $where['status'] = $status;
        }

        $level_gift_listDb = D('User_level_gift');

        $level_gift_list = $level_gift_listDb->level_gift_order_list($where);


        $user_level = M('User_level')->select();
        if (empty($user_level)) {
            $this->error("请先添加会员等级！");
        }
        $this->assign('user_level', $user_level);

        $this->assign($level_gift_list);

        $this->display();
    }

    /**礼包领取详情*/
    public function level_gift_order_detail() {
        $order_id = $_GET['order_id']>0 ? $_GET['order_id'] : '';
        $m_user_level_gift_order = M('User_level_gift_order');
        if ($order_id>0) {
            $order_info = $m_user_level_gift_order->where(array('order_id' => $order_id))->find();
            if (empty($order_info)) {
                $this->error("查看订单不存在或者已经被删除！");
            }
            if ($order_info['uid']>0) {
                $now_user = M('User')->where(array('uid' => $order_info['uid']))->field('nickname,phone')->find();
                $this->assign('now_user', $now_user);
            }
            $level_info = M('User_level')->where(array('id' => $order_info['level_id']))->field('lname,level')->find();
            $this->assign('level_info', $level_info);


            $express_list = D('Express')->get_express_list();
            $this->assign('express_list',$express_list);
            $this->assign('order_status', D('User_level_gift')->order_status);
            $this->assign('now_order', $order_info);
        } else {
            $this->error("查看订单不存在或者已经被删除！");
        }

        $this->display();
    }

    /**填写发货信息*/
    public function level_gift_express(){
        if(IS_AJAX){
            $m_user_level_gift_order = D('User_level_gift_order');
            $condition_order = array('order_id' => intval($_GET['order_id']));
            $now_order = $m_user_level_gift_order->where($condition_order)->find();

            if(empty($now_order)){
                $this->error('此订单不存在！');
            }
            if($now_order['status']<=0){
                $this->error('此礼包尚未领取！');
            }

            $condition_order['order_id'] = $now_order['order_id'];
            $data_order['express_number'] = $_POST['express_number'];
            $data_order['express_id'] = $_POST['express_id'];
            $data_order['status'] = 2;

            $insert_id = $m_user_level_gift_order->where($condition_order)->data($data_order)->save();

            if($insert_id){
                $database_user = D('User');
                $userInfo = $database_user->get_user($now_order['uid']);
                //发送微信模板消息start
                if($userInfo['openid']){
                    $remark = '';
                    if ($now_order['gift_name']) {
                        $remark .= '礼包：'.$now_order['gift_name'].';';
                    }
                    if ($now_order['get_name']) {
                        $remark .= '姓名：'.$now_order['get_name'].';';
                    }
                    if ($now_order['get_phone']) {
                        $remark .= '电话：'.$now_order['get_phone'].';';
                    }
                    if ($now_order['get_address']) {
                        $remark .= '地址：'.$now_order['get_address'].';';
                    }


                    $href = $this->config['site_url'] . '/wap.php?g=Wap&c=My&a=levelUpdate';
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                    $express_info = D('Express')->get_express($_POST['express_id']);

                    $express_info ='\n快递号：'.$_POST['express_number'].'的'.$express_info['name'].'已寄出，请及时查收。';

                    $model->sendTempMsg('TM00017', array('href' => $href,
                            'wecha_id' => $userInfo['openid'],
                            'first' => $express_info,
                            'OrderSn' => $now_order['order_id'],
                            'OrderStatus' =>"已发货",
                            'remark' =>$remark)
                    );
                }

                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试。');
            }
        }else{
            $this->error_tips('访问页面有误！~~~');
        }
    }

    /**添加用户充值赠送平台余额套餐*/
    public function recharge_discount_list() {
        $database_recharge_discount = D('Recharge_discount');
        $status = $_GET['status'] ? $_GET['status'] : -1;

        $where = array();
        if ($_GET['begin_time']&&$_GET['end_time']) {
            $where['add_time'] = array('between',array(strtotime($_GET['begin_time']),strtotime($_GET['end_time'])));
        }
      
        // 查询 全部 status -1 查询待领取 status -2  领取 1 发货 2
        if (isset($_GET['status'])&&$_GET['status']!=-1) {
            $where['status'] = $_GET['status'];
        }

        import('@.ORG.system_page');
        $count = $database_recharge_discount->where($where)->count();

        $p = new Page($count,20,'page');

        $list = $database_recharge_discount->field(true)->where($where)->order('add_time desc')->limit($p->firstRow,$p->listRows)->select();
        $pagebar = $p->show();
        $this->assign('list',$list);
        $this->assign('pagebar',$pagebar);
        $this->display();
    }

    /**添加用户充值赠送平台余额套餐*/
    public function recharge_discount_add() {
        $database_recharge_discount = D('Recharge_discount');
        if(IS_POST){
            if (empty($_POST['full_money'])) {
                $this->error("请填写满足金额！");
            }
            
            if (empty($_POST['money'])) {
                $this->error("请填写赠送金额！");
            }
           
            $_POST['add_time'] = time();
            if ($_POST['discount_id']) {
                $gift_id = $database_recharge_discount->where(array('discount_id' => intval($_POST['discount_id'])))->save($_POST);
            } else {
                $_POST['add_time'] = time();
                $gift_id = $database_recharge_discount->data($_POST)->add();
            }
            if ($gift_id) {
                $this->success('操作成功！');
            } else {
                $this->error("操作失败！");
            }

        } else {
            if ($_GET['discount_id']) {
                $recharge_discount = $database_recharge_discount->where(array('discount_id' => intval($_GET['discount_id'])))->find();
                if (empty($recharge_discount)) {
                    $this->error("所编辑的信息不存在或者已经被删除！");
                }
                $this->assign('recharge_discount', $recharge_discount);
            }
            $this->display();
        }
    }

    //充值卡列表
    public function recharge_card(){
        $where=array();
        $searchtype=I('searchtype','','trim');
        $keyword=I('keyword','','trim');
        if(!empty($searchtype) && !empty($keyword)){
            switch ($searchtype) {
                case 'card_id':
                    $where['c.card_id']=$keyword;
                    break;
                case 'card_name':
                    $where['c.card_name']=$keyword;
                    break;
                case 'card_price':
                    $where['c.card_price']=$keyword;
                    break;
                default:
                    break;
            }
        }
        $where['c.is_del']=0;
        $join = ' as c left join '.C('DB_PREFIX').'admin as a on c.admin_id = a.id';
        $count = M('Recharge_card')->where($where)->join($join)->count(1);
        import('@.ORG.system_page');
        $page = new Page($count,20);
        $list = M('Recharge_card')->field('c.*,a.realname')->where($where)->join($join)->limit($page->firstRow,$page->listRows)->select();
        foreach ($list as $k => $v) {
            //制卡数量
            $make_num=M('Recharge_card_coupon')->where(array('card_id'=>$v['card_id'],'make_status'=>1))->count(1);
            $v['make_num']=$make_num;
            $v['card_bg']=replace_file_domain($v['card_bg']);
            $card_num=M('Recharge_card_coupon')->where(array('card_id'=>$v['card_id']))->count(1);
            if($card_num>0){
                $card_sku=M('Recharge_card_coupon')->where(array('card_id'=>$v['card_id'],'is_del'=>0))->count(1);
                $v['card_sku']=$card_sku;
            }else{
                $v['card_sku']=$v['card_num'];
            }
            $list[$k]=$v;
        }
        $this->assign('list',$list);
        $this->assign('pagebar',$page->show());
        $this->display();
    }

    //创建充值卡
    public function recharge_card_edit(){
        $model=M('Recharge_card');
        $id=I('card_id','','intval');
        if(IS_POST){
            $data=$_POST;           
            if(empty($id)){
                if($data['card_num']<=0){
                    $this->frame_submit_tips(0,'至少制作一张'.$this->config['ecard_alias_name']);
                }
                if($data['card_price']<=0 || !is_numeric($data['card_price'])){
                    $this->frame_submit_tips(0,'请填写大于0的面值');
                }
                $qr_arr=$model->getField('qrcode',true);
                if(is_array($qr_arr) && in_array($data['qrcode'], $qr_arr)){
                    $this->frame_submit_tips(0,'抱歉，该前缀已使用！');
                }
            }
            if($_FILES['card_bg']['error']!=4){
                $img_arr=D('Image')->handle($this->system_session['id'],'ecard',0,array('size'=>10),false);
                if($img_arr['error']){
                    $this->error($img_arr['message']);
                }else{
                    $data['card_bg']=$img_arr['url']['card_bg'];
                }
            }
            if($id>0){
                $res=$model->data($data)->save();
                if($res!==false){
                    $this->frame_submit_tips(1,'编辑成功！');
                }else{
                    $this->frame_submit_tips(0,'抱歉，编辑失败，请重试！');
                }
            }else{
                $data['admin_id'] = $this->system_session['id'];
                $data['add_time']=time();
                $res=$model->data($data)->add();
                if($res){
                    $this->frame_submit_tips(1,'添加成功！');
                }else{
                    $this->frame_submit_tips(0,'抱歉，添加失败，请重试！');
                }   
            }           
        }else{
            if($id>0){
                $info=$model->where(array('card_id'=>$id))->find();
                $info['card_bg']=replace_file_domain($info['card_bg']);
                $this->assign('info',$info);
            }
            $this->display();
        }
    }

    //修改充值卡状态
    public function save_recharge_card_status(){
        $card_id = intval($_POST['card_id']);
        if(empty($card_id)){
            $this->error('缺少参数！');
        }
        $info = M('Recharge_card')->where(array('card_id'=>$card_id))->find();
        if(empty($info)){
            $this->error('该平台e卡不存在！');
        }
        $status = intval($_POST['status']);
        $data = array();
        $data['card_id'] = $card_id;
        $data['status'] = $status;
        $res = M('Recharge_card')->data($data)->save();   
        if($res){
            $this->success('操作成功！');
        }else{
            $this->error('抱歉，操作失败，请重试！');
        }
    }

    //删除充值卡
    public function recharge_card_del(){
        $card_id = intval($_GET['card_id']);
        if(empty($card_id)){
            $this->error('缺少参数！');
        }
        $info = M('Recharge_card')->where(array('card_id'=>$card_id))->find();
        if(empty($info)){
            $this->success('卡片不存在！');
        }
       
        $data=array();
        $data['card_id']=$card_id;
        $data['is_del']=1;
        $res = M('Recharge_card')->data($data)->save();
        if($res){
            M('Recharge_card_coupon')->data($data)->save();
            $this->success('删除成功！');
        }else{
            $this->error('抱歉，删除失败，请重试！');
        }
    }

    //充值卡卡券列表
    public function recharge_card_coupon_list(){
        $model=M('Recharge_card_coupon');
        $card_id=I('card_id','','intval');
        if(empty($card_id)){
            $this->error('缺少参数！');
        }
        //创建e卡
        $this->recharge_card_add_coupon($card_id);

        //制卡状态
        $make_status=I('make_status',0,'intval');
        $use_status=I('use_status',0,'intval');
        $where=array();
        $where['c.card_id']=$card_id;
        $where['c.make_status']=$make_status;
        $where['c.is_del']=0;
        $use_status && $where['c.use_status']=$use_status;
        $join = ' as c left join '.C('DB_PREFIX').'user as u on c.uid=u.uid';
        $count=$model->where($where)->join($join)->count(1);
        import('@.ORG.system_page');
        $page=new page($count,20);
        $list=$model->field('c.*,u.nickname')->where($where)->join($join)->limit($page->firstRow,$page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('pagebar',$page->show());
        $this->assign('card_id',$card_id);
        $this->assign('make_status',$make_status);
        $this->display();
    }

    //删除未制卡的e卡
    public function recharge_card_coupon_del(){
        $id = $_GET['id'];
        if(empty($id)){
            $this->error('缺少参数！');
        }
        $data = array();
        $data['id'] = array('in',$id);
        $data['is_del'] = 1;
        $res = M('Recharge_card_coupon')->data($data)->save();
        if($res){
            $this->success('删除成功！');
        }else{
            $this->error('抱歉，删除失败，请重试！');
        }
    }

    //导出e卡卡券
    public function recharge_card_coupon_export(){
        $model=M('Recharge_card_coupon');
        //制卡状态
        $make_status=I('make_status',0,'intval');
        $card_id=I('card_id','','intval');
        if(empty($card_id)){
            $this->error('缺少参数！');
        }
        //平台e卡名称
        $card_name=M('Recharge_card')->where(array('id'=>$card_id))->getField('card_name');

        $ids=I('ids','','trim');
        $where=array();
        if(!empty($ids)){
            $where['id']=array('in',$ids);
        }
        $where['card_id']=$card_id;
        $where['make_status']=$make_status;
        $where['is_del']=0;
        $count=$model->where($where)->count(1);
        $list=$model->where($where)->order('id asc')->select();

        $title=C('config.ecard_alias_name');

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $len=ceil($count/10000);

        for ($i=0; $i <$len ; $i++) { 
            if($i>0){
                $objExcel->createSheet();
            }
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('第'.($i+1).'个'.C('config.ecard_alias_name').'未制卡信息');
            $objExcel->getActiveSheet()->mergeCellsByColumnAndRow('0', '1',4, '1');
            $objExcel->getActiveSheet()->setCellValueByColumnAndRow('0', '1', $card_name);

            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A2', '编号');
            $objActSheet->setCellValue('B2', '卡号');
            $objActSheet->setCellValue('C2', '卡密');
            $objActSheet->setCellValue('D2', '面值');

            $list2=array_slice($list, $i*10000,10000);
            asort($list2);
            fdump($list2,1,1);
            foreach($list2 as $k=>$v){
                $k+=3;
                $objActSheet->setCellValueExplicit('A' . $k, $v['id']);
                $objActSheet->setCellValueExplicit('B' . $k, $v['card_code']);
                $objActSheet->setCellValueExplicit('C' . $k, $v['card_password']);
                $objActSheet->setCellValueExplicit('D' . $k, $v['money']);
            }
        }
        sleep(2);
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    //创建卡券
    public function recharge_card_add_coupon($card_id=null){
        if(empty($card_id)){
            $this->error('缺少参数！');
        }
        $info=M('Recharge_card')->where(array('card_id'=>$card_id))->find();
        if(empty($info)){
            $this->error('该充值卡不存在！');
        }
        $add_num = M('Recharge_card_coupon')->where(array('card_id'=>$card_id))->count(1);
        if($add_num >= $info['card_num']){
            return false;
        }
        set_time_limit(0);
        $i=$add_num+1;
        $model=M('Recharge_card_coupon');
        //获取当前的卡券密码列表，防止重复
        $card_pass_arr=$model->getField('card_password',true);
        $card_pass_arr=(array)$card_pass_arr;


        $data=array();
        $data['card_id']=$card_id;
        $data['money']=$info['card_price'];
        $data['addtime']=time();
        $add_arr=array();
        $flag=true;
        for ($i;$i<=$info['card_num'];$i++) {
            $arr=$data;
            $arr['card_code'] = $info['qrcode'].'1'.sprintf('%05d', $i);
            $card_password = $this->rand_card_password(16,$card_pass_arr);
            $arr['card_password']=$card_password;
            $card_pass_arr[]=$card_password;
            $add_arr[]=$arr;
        
        }
        $res=$model->addAll($add_arr);
        if(!$res){
            $this->error('充值卡创建失败！');
        }
    }

    //标记卡券为制卡完成
    public function save_recharge_card_coupon(){
        $ids=I('ids','','trim');
        $card_id=I('card_id','','intval');
        if(empty($ids) || empty($card_id)){
            $this->error('缺少参数！');
        }
        $res=M('Recharge_card_coupon')->where(array('id'=>array('in',$ids)))->save(array('make_status'=>1));
        if($res){
            $this->success('操作成功！');
        }else{
            $this->error('抱歉，操作失败，请重试！');
        }
    }

    //标记为已售出
    public function make_recharge_card_coupon(){
        $id=I('get.id','','trim');
        if(empty($id)){
            $this->error('缺少参数！');
        }
        $ecard_info=M('Recharge_card_coupon')->where(array('id'=>$id))->find();
        if(empty($ecard_info)){
            $this->error('该e卡不存在！');
        }
        if($ecard_info['buy_type']>0){
            $this->error('该e卡已售出！');
        }
        $sales_type=I('sales_type',2,'intval');
        $orderid=I('orderid','','trim');
        $sales_order_id='';
        if($sales_type==1){
            if(empty($orderid)){
                $this->error('请填写线上售出的订单编号！');
            }
            $info=M('shop_order')->where(array('real_orderid'=>$orderid))->find();
            if(empty($info)){
                $this->error('填写的订单编号的订单中不存在，请重新填写！');
            }
            $sales_order_id=$info['order_id'];
        }elseif($sales_type==2){    //标记为线下购买把绑定卡券的商品的库存减1
            $bind_goods_id=M('Recharge_card')->where(array('id'=>$ecard_info['ecard_id']))->getField('bind_goods_id');
        }
        $data=array();
        $data['id']=$id;
        $data['sales_type']=$sales_type;
        $data['sales_order_id']=$sales_order_id;
        $data['sales_time']=time();
        $res=M('Recharge_card_coupon')->data($data)->save();
        if($res){
            M('Recharge_card')->where(array('id'=>$ecard_info['ecard_id']))->setInc('sales_num',1);
            $this->success('操作成功！');
        }else{
            $this->error('抱歉，操作失败，请重试！');
        }
    }

    //更改卡券状态
    public function save_recharge_card_coupon_status(){
        $id=I('id','','intval');
        if(empty($id)){
            $this->error('缺少参数！');
        }
        $status=I('status',0,'intval');
        $data=array();
        $data['id']= array('in',$id);
        $data['status']=$status;
        $res=M('Recharge_card_coupon')->data($data)->save();
        if($res){
            $this->success('操作成功！');
        }else{
            $this->error('抱歉，操作失败，请重试！');
        }
    }


    /**
     * 	作用：产生随机字符串，不长于32位
     * @param int $length
     * @return string
     */
    private function rand_number($length = 6, $rand_arr = array()) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        if ($rand_arr && in_array($str, $rand_arr)) {
            return  $this->rand_number(6, $rand_arr);
        } else {
            if(!M('User_level_activation_code')->where(array('activation_code'=>$str))->find()){
                return $str;
            }else{
                return  $this->rand_number(6, $rand_arr);
            }
        }
    }

    /**
     *  作用：产生随机字符串，不长于32位
     * @param int $length
     * @return string
     */
    private function rand_card_password($length = 6, $rand_arr = array()) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        if ($rand_arr && in_array($str, $rand_arr)) {
            return  $this->rand_card_password(6, $rand_arr);
        } else {
            if(!M('Recharge_card_coupon')->where(array('card_password'=>$str))->find()){
                return $str;
            }else{
                return  $this->rand_card_password(6, $rand_arr);
            }
        }
    }


    public function select_coupon(){

        $selected = trim(I("selected"),',');
        $selected_arr = explode(',', $selected);
        $this->assign("selected", $selected);

        if (!empty($_GET['keyword'])) {
            $condition['name'] = array('like',"%" . $_GET['keyword'] . "%");
        }
        $condition['end_time'] = array('gt',time());
        $condition['status'] =  1;
        $condition['is_vip_level'] =  1;

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if($now_area['area_type']==3){
                die('区域管理员无法管理优惠券');
            }elseif($now_area['area_type']==2){
                $area_index = 'city_id';
            }elseif($now_area['area_type']==1){
                $area_index = 'province_id';
            }
            $condition[$area_index] = $this->system_session['area_id'];
        }

        $order_string = '`coupon_id` DESC';
        $count = D('System_coupon')->where($condition)->count();
        import('@.ORG.diypage');
        $p = new Page($count, 10);
        $coupon_list = D('System_coupon')->field(true)->where($condition)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($coupon_list as &$v) {
            $v['platform']=unserialize($v['platform']);
            if($v['cate_name']!='all'&&!empty($v['cate_id'])){
                $tmp = unserialize($v['cate_id']);
                $v['cate_id'] = $tmp['cat_name'];
            }
            $v['select'] = 0;
            if(in_array($v['coupon_id'], $selected_arr)){
                $v['select'] = 1;
            }
        }

        $this->assign("keyword", $_GET['keyword']);
        $return =  D('System_coupon')->cate_platform();
        $this->assign("category",$return['category']);
        $this->assign("platform",$return['platform']);
        $this->assign('list', $coupon_list);
        $this->assign('pagebar', $p->show());
        $this->display('Home/select_coupon');
    }

    // 初始化用户标签，将用户表里的标签写入到标签关系表中
    public function update(){
        $where = [
            'label_id' => ['neq',''],
            'status' => ['neq', 4]
        ];
        $limit = 1000;
        $start = 0;
        $userList = M('User')->where($where)->limit($start, $limit)->select();
        while($userList){

            foreach($userList as $now_user){
                M('User_label_bind')->where(array('uid'=>$now_user['uid']))->delete();
                if ( $now_user['label_id']) {
                    $label_ids_arr = explode(',', $now_user['label_id']);
                    $label_add_arr = array();
                    foreach ($label_ids_arr as $key => $value) {
                        if ( $value ) {
                            $label_add_arr[] = array(
                                'uid' => $now_user['uid'],
                                'label_id' => $value,
                            );
                        }
                    }
                    M('User_label_bind')->addAll($label_add_arr);
                }
            }
            $start++;
            $userList = M('User')->where($where)->limit($start, $limit)->select();
        }
        echo 'success';
    }


    public function changeUserStatus()
    {
        $uids = $_POST['uids'];
        $status = $_POST['status'];
        if(!is_array($uids) || !count($uids) || !in_array($status, [0, 1])){
            $this->ajaxReturn(array($uids, $status),'参数有误！', 0);
            die;
        }

        $condition = [];
        $condition['uid'] = ['in', $uids];
        M('User')->where($condition)->save(['status'=> $status]);
        $this->ajaxReturn([],'操作成功', 1);
    }	

    public function restore()
    {
        $uid = I('post.uid', 0, 'intval');
        $userMod = D('User');
        $retval = ['error_no' => 0, 'error_msg' => '用户恢复成功'];
        try {
            if ($this->system_session['level'] != 2) {
                throw new Exception('抱歉，只有超级管理员才能恢复用户');
            }
            $nowUser = $userMod->where(['uid' => $uid])->find();
            if (!$nowUser) {
                throw new Exception('用户不存在');
            }
            if ($nowUser['status'] != 4) {
                throw new Exception('用户非注销状态无需恢复');
            }
            $userMod->where(['uid' => $uid])->save(['status' => 1]);
        } catch (Exception $e) {
            $retval['error_no'] = 1;
            $retval['error_msg'] = $e->getMessage();
        }
        $this->ajaxReturn($retval);
    }
}
