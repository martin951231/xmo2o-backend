<?php


class HouseInterfaceAction extends BaseAction
{
    public function dahua()
    {
        $dahua_service = D('Dahua_service');
        $dahua_service->add_department('亚夏汽车大厦2','添加设备',1);
    }
    public function time_quan_tum()
    {
        $dahua_service = D('Dahua_service');
        $detail = "{\"monday\":[\"00:00-23:59\",\"00:00-00:00\",\"00:00-00:00\",\"00:00-00:00\"],\"tuesday\":[\"00:00-23:59\",\"00:00-00:00\",\"00:00-00:00\",\"00:00-00:00\"],\"wednesday\":[\"00:00-23:59\",\"00:00-00:00\",\"00:00-00:00\",\"00:00-00:00\"],\"thursday\":[\"00:00-23:59\",\"00:00-00:00\",\"00:00-00:00\",\"00:00-00:00\"],\"friday\":[\"00:00-23:59\",\"00:00-00:00\",\"00:00-00:00\",\"00:00-00:00\"],\"saturday\":[\"00:00-23:59\",\"00:00-00:00\",\"00:00-00:00\",\"00:00-00:00\"],\"sunday\":[\"00:00-23:59\",\"00:00-00:00\",\"00:00-00:00\",\"00:00-00:00\"]}";
        $dahua_service->time_quan_tum($detail,'全天候可以开门','24小时开门',1);
    }
    public function card_info()
    {
        $dahua_service = D('Dahua_service');
        $res = $dahua_service->card_info('','89898989');
        var_dump($res);
    }
    public function update_card()
    {
        $dahua_service = D('Dahua_service');
        $res = $dahua_service->update_card('89898989','1234567676','2021-12-14','2023-01-01',6);
        var_dump($res);
    }
    public function addPerson()
    {
        $dahua_service = D('Dahua_service');
        $dahua_service->addPerson('亚夏汽车大厦2',0,1,1,'住户');
    }

    public function integrate_record()
    {
        $dahua_service = D('Dahua_service');
        $data = $dahua_service->swing_card_record(1,500);
        $list = $data['data']['pageData'];
        var_dump($list);exit;
    }

    public function add()
    {
        $dahua_service = D('Dahua_service');
        $res = $dahua_service->get_door_device();var_dump($res);
        //$res = $dahua_service->add_department('亚夏汽车大厦Test6','添加设备',1); //添加部门
        /*$res = $dahua_service->time_quan_tum();
        fdump_api($res,'1207',1);*/
        $res = ['status'=>1,'department_id'=>12];
        fdump_api($res,'1207',1);
        if($res['status']){
            $department_id = $res['department_id'];
            //$res = $dahua_service->add_identity('员工6',0,1,1,'员工');  //添加身份
            $res = $res = ['status'=>1,'identity_id'=>16];
            fdump_api($res,'1207',1);
            if($res['status']){
                $identity_id = $res['identity_id'];
                $code = $dahua_service->create_code();
                //$res = $dahua_service->addPerson('身份证','341281199004249201',$name='小明',$code,$department_id,$sex='男',$birthday='2018-11-03',$phone='18895319187',$status='在职',$identity_id);  //添加人员
                $code = 'sg3h7GTbmw';
                $res = array (
                    'status' => 1,
                    'person_id' => 9,
                );
                fdump_api([$res,$code],'1207',1);
                if($res['status']){
                    import('@.ORG.face.word_encryption');
                    $word_encryption = new word_encryption();
                    // 针对加密图片解密
                    $imgUrl = $word_encryption->text_decrypt('W8n5JJHQ35tHpsUbQNk2yn++p2nnS6isew51PRfKRRO1IAMFxxpnKWHLiqPReeLGtaIpan37n/LveQ');
                    $imgUrl = D('Image')->transform_image($imgUrl);
                    $imgUrl = thumb_img($imgUrl, 640, 480, '', true);
                    $img = $dahua_service->imgToBase64($imgUrl);
                    $res = $dahua_service->save_mobile_base64_image_to_byte($code,$img); //上传人员头像
                    fdump_api([$res,$img],'1207',1);
                    $res = $dahua_service->open_batch(9,'89898989','0','0','ACTIVE','2021-12-8','2022-12-8',123456,'1');  //开卡
                    fdump_api([$res],'1207',1);
                    $res = $dahua_service->door_authority_update('89898989',4,[
                        ['privilegeType'=>1,'resouceCode'=>'1000005$7$0$0'],
                    ]);
                    fdump_api([$res],'1207',1);
                }
            }
        }
    }




    /**
     * 获取访客和主人信息
     * @author lijie
     * @date_time 2020/09/02 9:47
     * @return \json
     */
    public function visitorInfo()
    {
        $visitor_id = I('visitor_id',0);
        $village_id = I('village_id',0);
        if(!$visitor_id || !$village_id)
            $this->returnCode('20090005');
        $uid = $this->_uid;
        if(!$uid)
            $this->returnCode('20000009');
        if(!M('House_village_user_bind')->where(['uid'=>$uid,'village_id'=>$village_id,'status'=>1,'type'=>4])->find()){
				$this->returnCode('20090248');
			}
        $data = M('House_village_visitor')
            ->join('as v LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as hvb ON v.owner_uid = hvb.uid ')
            ->field('v.single_id,v.floor_id,v.layer_id,v.vacancy_id,v.visitor_name,v.visitor_phone,v.owner_name,v.owner_phone,v.owner_address,hvb.type,v.add_time')
            ->where(['v.id'=>$visitor_id])
            ->find();
        if(!empty($data)){
            $todaydate=date('Y-m-d');
            $todaytime=strtotime($todaydate);
            $todaytime_end=$todaytime+86400;
            $data['is_ewm_expire']=0;
            $data['ewm_expire_tips']='';
            if($data['add_time'] < $todaytime){
                $data['is_ewm_expire']=1;
                $data['ewm_expire_tips']='访客二维码已过期，前重新申请';
            }
            if($data['add_time'] >= $todaytime_end){
                $data['is_ewm_expire']=1;
                $data['ewm_expire_tips']='您的到访时间是'.date('Y-m-d',$data['add_time']).'，请访客重新登记生成二维码';
            }
            $data['add_time'] = date('Y-m-d',$data['add_time']);
            if($data['single_id']>0){
                $data['owner_address'] = D('House_village')->get_single_floor_room($data['single_id'],$data['floor_id'],$data['layer_id'],$data['vacancy_id'],$village_id);
            }
        }else{
            $data=array();
        }

        $this->returnCode(0, $data);
    }

    //确认通行
    public function chk_visitor_pass()
    {
        $visitor_id = I('visitor_id',0);
        $visitor_id=intval($visitor_id);
        $village_id = I('village_id',0);
        $village_id=intval($village_id);
        if (!$visitor_id) {
            $this->returnCode('20090171');
        }
        $uid = $this->_uid;
        if($uid<1){
            $this->returnCode('20000009');
        }
        $user_bind=M('House_village_user_bind')->where(['uid'=>$uid,'village_id'=>$village_id,'status'=>1,'type'=>4])->find();
        if(empty($user_bind)){
            $this->returnCode('20090248');
        }
        $database_house_village_visitor = D('House_village_visitor');
        $visitordata=$database_house_village_visitor->where(array('id'=>$visitor_id))->find();
        if(empty($visitordata)){
            $this->returnCode('20090198');
        }
        $nowtime= time();
        $todaydate=date('Y-m-d');
        $todaytime=strtotime($todaydate);
        $todaytime_end=$todaytime+86400;
        if($visitordata['add_time'] < $todaytime){
            $this->returnCodeError('访客二维码已过期，前重新申请');
        }
        if($visitordata['add_time'] >= $todaytime_end){
            $this->returnCodeError('您的到访时间是'.date('Y-m-d',$visitordata['add_time']).'，请访客重新登记生成二维码');
        }

        $database_house_village_visitor = D('House_village_visitor');
        $where=array();
        $where['id'] = $visitor_id;
        $data['status'] = 2;
        $data['pass_time'] = $nowtime;
        $result = $database_house_village_visitor->where($where)->save($data);
        if (!$result) {
            $this->returnCode('20120040');
        } else {
            $house_village_visitor_pass=M('House_village_visitor_pass');
            $addArr=array('visitor_id'=>$visitor_id);
            $addArr['village_id']=$village_id;
            $addArr['uid']=$uid;
            $addArr['user_bind_id']=$user_bind['pigcms_id'];
            $addArr['phone']=$user_bind['phone'];
            $addArr['uname']=$user_bind['name'];
            $addArr['addtime']=$nowtime;
            $house_village_visitor_pass->data($addArr)->add();
            $this->returnCodeOk('放行成功');
        }
    }

    /**
     * 获取小区用户信息
     * @author lijie
     * @date_time 2020/09/01 14:35
     * @return \json
     */
    public function userBindInfo()
    {
        $village_id = I('village_id',0);
        $pigcms_id = I('pigcms_id',0);
        if(!$pigcms_id || !$village_id)
            $this->returnCode('20090005');
        $uid = $this->_uid;
        if(!$uid)
            $this->returnCode('20000009');
        if(!M('House_village_user_bind')->where(['uid'=>$uid,'village_id'=>$village_id,'status'=>1,'type'=>4])->find())
            $this->returnCode('20090248');
        $data = M('House_village_user_bind')
            ->where(['pigcms_id'=>$pigcms_id])
            ->field('name,phone,uid,single_id,floor_id,layer_id,layer_num,room_addrss,address,vacancy_id,id_card as cardid,village_id')
            ->find();
        $data['address'] = D('House_village')->get_single_floor_room($data['single_id'],$data['floor_id'],$data['layer_id'],$data['vacancy_id'],$data['village_id']);
        $this->returnCode(0, $data);
    }

    /**
     * 未绑定社区提示语
     * @author lijie
     * @date_time 2020/12/09
     */
    public function getTip()
    {
        $village_id = $_POST['village_id'];
        if(!$village_id)
            $this->returnCode('20090005');
        $info = D('House_village')->get_village_info($village_id,'village_id,village_name,property_id,property_phone');
        $data = [];
        $village_name='';
        if($info){
            $village_name=$info['village_name'];
            // 三方楼宇只有是 业主家属租客显示
            if (empty($this->thirdConfig)) {
                import('@.ORG.ThirdAccess.thirdConfig');
                $this->thirdConfig = new thirdConfig();
            }
            // 存在三方对应物业id 查询下是否已经存在了对应物业
            $whereThirdConfig = [
                'businessType' => 'village',
                'businessId' => $village_id,
                'thirdType' => 'linghui',
            ];
            try {
                $thirdConfigInfo = $this->thirdConfig->getHouseThirdConfig($whereThirdConfig);
            } catch (\Exception $e) {
                fdump_api(['House-linkModel-replace_village_parameter',$whereThirdConfig,$e->getMessage()],'thirdConfigLog');
            }
            $data['content']='';
            $data['btnText']='';
            $now_user = D('User')->get_user($this->_uid);
            if ($thirdConfigInfo && $thirdConfigInfo['thirdSite'] && $thirdConfigInfo['thirdId']) {
                $thirdBaseUrl = 'https://'.$thirdConfigInfo['thirdSite'];
                $appid = trim($this->config['third_building_appid']);
                $key = md5($appid.$now_user['phone']);
                $referer = $thirdBaseUrl . "/h5/#/pages/community/buildingBindApply?key={$key}&phone={$now_user['phone']}&build_id={$thirdConfigInfo['thirdId']}&third_type=wechat";
                $house_name = $this->config['house_name']?$this->config['house_name']:'小区';
                if (!$now_user['phone']) {
                    $content = "您不属于当前{$village_name}{$house_name}，需要先绑定手机号码然后去绑定{$village_name}{$house_name}？";
                    $btnText = '先绑定手机号';
                    $btnUrl = get_base_url("pages/my/newnumber?back=". urlencode($referer));
                } else {
                    $content = "您不属于当前{$village_name}{$house_name}，是否去绑定{$village_name}{$house_name}？";
                    $btnText = '去绑定';
                    $btnUrl = $referer;
                }
                $data['content'] = $content;
                $data['btnText'] = $btnText;
                $data['btnUrl'] = $btnUrl;
            }

            $where['uid'] = $this->_uid;
            $where['village_id'] = $village_id;
            $where['status'] = 2;
            $res = M('House_village_user_bind')->where($where)->find();
            if($res){
                $data['pigcms_id'] = intval($res['pigcms_id']);
                $data['tip'] = '您有一间房间申请入住正在审核中，可联系物业咨询进度,物业电话：'.$info['property_phone'];
            }else{
                $data['tip'] = '';
            }
        }else{
            $data['tip'] = '';
        }
        $data['house_name']='小区';
        $house_name=$this->config['house_name'];
        $house_name=trim($house_name);
        if($house_name){
            $data['house_name']=$house_name;
            if(empty($data['tip'])){
                $data['content'] = '您不属于当前的'.$village_name.$house_name.'，是否去绑定'.$village_name.$house_name.'？';
                $data['btnText']='去绑定';
            }
        }
        $this->returnCode(0, $data);
    }

    /**
     * 获取工作人员推广二维码
     * @author lijie
     * @date_time 2020/12/09
     */
    public function villageSpreadCode()
    {
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'villagelist',
            'isAll' => true,
            'urlParam' => 'spread_uid='.$this->_uid,
        ];
        $villageListUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);

        $qrcode_url = $villageListUrl;
        $res = $this->config['site_url'].U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($qrcode_url))));
        $data['qr_code'] = $res;
        $this->returnCode(0, $data);
    }

    public function get_own_qrcode($qrCon, $size = 8,$is_down = true, $outfile = false){
        header('Content-Type: image/png');
        ob_clean();
//        $qrCon = $_GET['qrCon'];
        import('@.ORG.phpqrcode');
//        $size = $_GET['size'] ? $_GET['size']: 10;
//        $is_down = $_GET['down'] ? true: false;
        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),$outfile,0,$size,1,$is_down);
    }

     /**
      * 根据UID获取人脸图片
      */
    public function face_img_info() {
        if (empty($this->_uid)) {
            $this->returnCode('20000009');
        }
        $d_house_face_img = M('House_face_img');
        $uid = $_POST['uid'];
        if($this->_uid>0){
            $uid = $this->_uid;
        }
        if ($uid>0) {
            $face_img_list = $d_house_face_img->where(array('uid' => $uid, 'status'=>['in', [0,3]], 'img_url' => array('neq', '')))->order('id asc')->limit(5)->select();
        } else {
            $face_img_list = [];
        }
        if($face_img_list){
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            foreach($face_img_list as $kk=>&$vv){
                if (3==$vv['status']) {
                    // 加密的图片解密
                    $vv['img_url'] = $word_encryption->text_decrypt($vv['img_url']);
                }
                $vv['face_img_url'] = thumb_img($vv['img_url'], 200, 200, 'fill');
            }
        }else{
            $face_img_list = [];
        }

        $now_village_bind['face_img'] = $face_img_list;
        // 1标识 前端开启人脸识别  2标识 关闭用户端人脸
        $now_village_bind['my_face_door_switch'] = $this->config['my_face_door_switch'] ? 2 : 1;
        
        $this->returnCode(0, $now_village_bind);
    }

    /**
     * 上传人脸图片
     */
    public function face_img_add() {
        $face_img = $_POST['face_img'];
        $uid = !empty($_POST['uid'])?$_POST['uid']:0;
        if($this->_uid>0){
            $uid = $this->_uid;
        }
        if (empty($face_img)) {
            $this->returnCodeError('请上传人脸识别图片！');
        }
        if ($face_img && is_string($face_img)) {
            $face_img = explode(';',$face_img);
        }
        if (count($face_img)<1) {
            $this->returnCodeError('请最少上传1张图片！');
        }
        $d_house_face_img = M('House_face_img');
        $data = array();
        $img = array();
        $img['uid'] = $uid;
        $img['add_time'] = time();
        $img['status'] = 3;
        import('@.ORG.face.word_encryption');
        $word_encryption = new word_encryption();
        foreach ($face_img as $val) {
            if ($val) {
                // 图片加密
                $val =  $word_encryption->text_encryption($val);
                $img['img_url'] = $val;
                $data[] = $img;
            }
        }
        if (empty($data)) {
            $this->returnCodeError('请上传人脸识别图片！');
        }
        $save = array(
            'status' => 1,
            'del_time' => time()
        );
        // 删除现有图片 添加新图片
        if($uid){
            $d_house_face_img->where(array('uid' => $uid,'status' => ['in', [0,3]]))->data($save)->save();
        }
        $face_img_id = [];
        foreach ($data as $v){
            $tip = $d_house_face_img->add($v);
            $face_img_id[] = $tip;
        }
        $res['info'] = $face_img_id;
        $this->returnCode(0, $res);
    }

    /**
     * 获取默认小区
     */
    public function get_default_village()
    {
        if (!empty($this->_uid)) {
            // 查询下客户登录最后一次进入的小区 如果记录不一致  新加记录
            $user_last_village_id = M('House_village_select_log')->where(['uid'=>$this->_uid,'type'=>1])->order('id DESC')->getField('village_id');
        }
        $arr = [];
        if (empty($user_last_village_id)){
            if($this->config['is_demo_domain']){
                $user_last_village_id = 1;
                $village_info = M('House_village')->where(['village_id'=>$user_last_village_id,'status'=>1])->field('village_id,village_name')->find();
                if(!empty($village_info)){
                    $arr['village_id'] = $village_info['village_id'];
                    $arr['village_name'] = $village_info['village_name'];
                }
            }
            if (empty($arr)) {
                $whereVillage = ['i.is_default'=>1,'v.status' => 1];
                $village_info = M('House_village_info')
                    ->join('AS i left join ' . C('DB_PREFIX') . 'house_village v on v.village_id =i.village_id')
                    ->where($whereVillage)->field('i.village_id,v.village_name')->find();
                if(!empty($village_info)){
                    $arr['village_id'] = $village_info['village_id'];
                    $arr['village_name'] = $village_info['village_name'];
                } else{
                    $arr['village_id'] = '';
                    $arr['village_name'] = '';
                }
            }
        }else{
            $village_info = M('House_village')->where(['village_id'=>$user_last_village_id])->field('village_id,village_name')->find();
            if(!empty($village_info)){
                $arr['village_id'] = $village_info['village_id'];
                $arr['village_name'] = $village_info['village_name'];
            } else{
                $arr['village_id'] = '';
                $arr['village_name'] = '';
            }
        }

        $this->returnCode(0,$arr);
    }

    /**
     * 接收电表终端数据推送
     * @author lijie
     * @date_time 2021/08/09
     */
    public function receiveTerminalData()
    {
        $receive_data = file_get_contents('php://input');
        $receive_data = json_decode($receive_data,true);
		
        invoke_v20_service('\app\community\model\service\CustomizeMeterWaterReadingService/getMeterEleCallback',['param'=>$receive_data]);
        return true;
    }

    /**
     * 生成订单
     * @author lijie
     * @date_time 2021/08/06
     * @param int $pigcms_id
     * @param int $village_id
     * @param float $money
     * @param int $uid
     * @param $type
     * @return bool
     */
    public function all_go_pay($pigcms_id=0,$village_id=0,$money=0.00,$uid=0,$type='electric')
    {
        $login_user_info = $this->get_user_village_info($pigcms_id, $village_id, $uid);//==>郭先森==>获取当前社区我的信息
        //家属 获取业主的欠费信息
        if ($login_user_info['parent_id']) {
            $now_user_info = $this->get_user_village_info($login_user_info['parent_id'], $village_id, $uid);
        } else {
            $now_user_info = $login_user_info;
        }
        $data_order['money'] = $money;
        // uid 和 bind_id 取业主
        $data_order['uid'] = $now_user_info['uid'];
        $data_order['bind_id'] = $now_user_info['pigcms_id'];
        // pay_uid 和 pay_bind_id 取当前支付人员信息
        $data_order['pay_uid'] = $uid;
        $data_order['pay_bind_id'] = $login_user_info['pigcms_id'];
        $data_order['village_id'] = $village_id;
        $data_order['time'] = time();
        $data_order['paid'] = 0;
        $data_order['order_type'] = $type;
        $data_order['order_name'] = '电费';
        $use_total_score_count = 0;
        $generate_integral = 0;
        $userinfo = D('User')->get_user($uid);
        $property_info = D('House_village_pay_order')->get_score($data_order, $userinfo);
        $use_total_score_count += $property_info['use_total_score_count'];
        $generate_integral += $property_info['generate_integral'];
        $data_order['score_can_use'] = $property_info['use_total_score_count'];
        $data_order['score_can_pay'] = $property_info['use_total_money'];
        $data_order['score_percent'] = $property_info['score_percent'];
        $data_order['score_can_get'] = $property_info['generate_integral'];
        $order_id = D('House_village_pay_order')->data($data_order)->add();
        fdump_api($order_id,'$order_id',true);
        $score_data['score'] = $use_total_score_count;
        $score_data['desc'] = "缴纳缴纳总费用" . $money . C('config.Currency_txt') . " 支出积分";
        $score_data['generate_integral'] = $generate_integral;
        $score_data['generate_integral_desc'] = "缴纳总费用" . I('money') . C('config.Currency_txt') . " 增加积分";
        $now_order = D('House_village_pay_order')->field(true)->where(array('order_id' => $order_id))->find();
        $pay_order_param = array(
            'business_type' => 'house_village_pay',
            'business_id' => $now_order['order_id'],
            'order_name' => $now_order['order_name'],
            'uid' => $now_order['uid'],
            'total_money' => $now_order['money'],
            'wx_cheap' => 0,
        );
        $pay_order_param['is_own'] = 4;
        $plat_order_result = D('Plat_order')->add_order($pay_order_param);
        fdump_api($plat_order_result,'$plat_order_result',true);
        if ($plat_order_result['error_code']) {
            return ['error'=>1];
        }
        return ['error'=>0,'order_id'=>$order_id,'plat_order_info'=>$pay_order_param];
    }

    public function after_pay($order_id,$plat_order_info,$uid,$money,$order_type)
    {
        $order_info = D('House_village_pay_order')->get_one($order_id);
        if($order_info['village_id']){
            $e_config = M('House_e_config')->where(array('village_id'=>$order_info['village_id']))->find();
            if(empty($e_config))
                $auto = false;
            elseif ($e_config['is_open'] == 1 && $e_config['is_tui'] == 1)
                $auto = true;
            else
                $auto = false;
        }else{
            $auto = false;
        }
        $now_order = D('House_village_pay_order')->get_one($order_id);
        $date_order['pay_time'] = time();
        $date_order['paid'] = 1;
        $date_order['score_used_count'] = 0;
        $date_order['score_deducte'] = 0;
        $user = D('User')->where(array('uid'=>$uid))->find();
        $scroe = D('House_village_pay_order')->get_score($now_order,$user,false);
        $score_can_get = $scroe['generate_integral'];
        $date_order['score_can_get'] = $score_can_get;
        D('User')->add_score($plat_order_info['uid'],$score_can_get,'缴纳'.$now_order['order_name'].'获得积分');
        $tmp_order = $now_order;
        $tmp_order['is_own'] = $plat_order_info['is_own'];
        $tmp_order['score_used_count'] = $date_order['score_used_count'] ? $date_order['score_used_count'] : 0;
        $tmp_order['score_can_get'] =$score_can_get ? $score_can_get : 0;
        $tmp_order['payment_money'] = 0;
        $tmp_order['score_deducte'] = 0;
        $tmp_order['balance_pay'] = $money;
        $rate = 0.05;
        $now_user_info = D('House_village_user_bind')->get_one_by_bindId($now_order['bind_id']);
        switch($order_type){
            case 'water':
                $bind_field = 'water_price';
                $tmp_order['desc'] = '用户交水费';
                $where['water_price'] = array('neq', 0);
                $where['order_id'] = array('eq', 0);
                M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                break;
            case 'electric':
                $bind_field = 'electric_price';
                $tmp_order['desc'] = '用户交电费';
                $where['electric_price'] = array('neq', 0);
                $where['order_id'] = array('eq', 0);
                M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                break;
            case 'gas':
                $bind_field = 'gas_price';
                $tmp_order['desc'] = '用户交燃气费';
                $where['gas_price'] = array('neq', 0);
                $where['order_id'] = array('eq', 0);
                M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                break;
            default:
                $bind_field = '';
                $tmp_order['desc']= $plat_order_info['order_name'];
        }

        M('House_village_pay_order')->where(array('order_id'=>$order_id))->save($date_order);

        if(!empty($bind_field)){
            $data_bind['pigcms_id'] = $now_user_info['pigcms_id'];
            if($now_user_info[$bind_field] - $now_order['money'] >= 0){
                $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
            }else{
                $data_bind[$bind_field] = 0;
            }
            $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;
            D('House_village_user_bind')->data($data_bind)->save();

        }
        $tmp_order['order_type'] = 'village_pay';
        if($plat_order_info['is_own']==0)
            $plat_order_info['is_own']+=4;
        //社区对账
        $res = D('SystemBill')->bill_method($plat_order_info['is_own'],$tmp_order);

        if(!$res['error_code']){
            M('House_village_pay_order')->where(array('order_id'=>$order_id))->setField('is_pay_bill',2);
            M('House_village_pay_order')->where(array('order_id'=>$now_order['order_id']))->setField('is_pay_bill',2);
        }
        $now_user = D('User')->get_user($now_order['uid']);
        $village_info = D('House_village')->where(array('village_id'=>$now_order['village_id']))->find();
        $order_type = invoke_v20_service('\app\community\model\service\HouseNewChargeService/getType',['type'=>$now_order['order_type']]);
        $order_type = $order_type['data']['retval']??'缴费';
        if($now_order['pay_type']==1){
            $thing13 = '现金支付';
        }else{
            $thing13 = D('Pay')->get_pay_name($now_order['order_pay_type'], 1, 1);
        }
        if(!empty($now_user['openid'])){
            if ($order_info['order_type']=='pile'){
                $href=get_base_url('pages/village/smartCharge/historyOrderDetails');
            }else{
                $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_my_paylists&village_id='.$now_order['village_id'];
            }
            $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
            $model->sendTempMsg('TM01008', //todo 类目模板TM01008
                array(
                    'href' => $href, 
                    'wecha_id' => $now_user['openid'], 
                    'first' =>  ' 缴费成功提醒', 
                    'keynote1' =>$now_order['order_name'], 
                    'keynote2' =>'物业号 '. $now_user_info['usernum'], 
                    'remark' => '缴费时间：'.date('Y年n月j日 H:i',$now_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$now_order['money'],
                    'new_info' => [//新版本发送需要的信息
                        'type'=>1,//缴费提醒类型（0：提醒缴费，1：提醒缴费成功）
                        'thing8'=>$now_user_info['name'],//户名
                        'thing17'=>$village_info['village_name'].$now_user_info['address'],//地址
                        'thing9'=>$order_type?:'缴费',//账单类型
                        'thing13'=>$thing13?:'无',//缴费方式
                        'thing10'=>$village_info['property_name'],//商家名称（物业名称）
                        'amount3'=>C('config.Currency_symbol').$now_order['money'],//缴费金额
                        'time12'=>date('Y年n月j日 H:i',$now_order['time']),//缴费时间
                    ],
                ),0,$village_info['property_id'],2);
        }
        $village_bind = D('House_village_user_bind')->where(array('pigcms_id'=>$now_order['bind_id']))->find();
        if ($village_bind['single_id'] && $village_bind['floor_id'] && $village_bind['layer_id'] && $village_bind['vacancy_id'] && $village_bind['village_id']) {
            $address = D('House_village')->get_single_floor_room($village_bind['single_id'],$village_bind['floor_id'],$village_bind['layer_id'],$village_bind['vacancy_id'],$village_bind['village_id']);
            if ($address) {
                $village_bind['address'] = $address;
            }
            $first =  ' 业主 '.$village_bind['name'].'( '.$village_bind['address'].' )缴费成功提醒';
            $keynote2 = ' 业主 '.$village_bind['name'].'( '.$village_bind['phone'].' )';
        } elseif ($now_order['car_number']) {
            $where_car = [];
            $where_car['_string'] = "concat(`province`,'',`car_number`)='{$now_order['car_number']}'";
            $infos_car_info = D('House_village_parking_car')->where($where_car)->field('car_position_id,car_user_name,car_user_phone,car_stop_num')->find();
            if ($infos_car_info) {
                $name = $infos_car_info['car_user_name'];
                $address = $infos_car_info['car_user_phone'];
                $first =  ' 业主 '.$name.'( '.$address.' )缴费成功提醒';
                $keynote2 = ' 业主 '.$name.'( '.$address.' )';
            } else {
                $first =  ' 车辆 '.$now_order['car_number'].'缴费成功提醒';
                $keynote2 = '车辆('.$now_order['car_number'].')';
            }
        } else {
            $first =  $now_order['order_name'].'缴费成功提醒';
            $keynote2 = $now_order['order_name'];
        }
        if($village_info['openid']){
            $property_name = M('House_property')->where(['id'=>$village_info['property_id']])->getField('property_name');
            $village_info['property_name'] = $property_name;
            $href = "";
            $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
            $model->sendTempMsg('TM01008', //todo 类目模板TM01008
                array(
                    'href' => $href, 
                    'wecha_id' => $now_user['openid'], 
                    'first' => $first, 
                    'keynote1' =>$village_info['property_name'], 
                    'keynote2' =>$keynote2, 
                    'remark' => '缴费周期： '.($now_order['property_month_num'] ? $now_order['property_month_num'] : ($now_order['payment_paid_cycle'] ? $now_order['payment_paid_cycle'] : 0)).' 个月\n缴费时间：'.date('Y年n月j日 H:i',$now_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$now_order['money'],
                    'new_info' => [//新版本发送需要的信息
                        'type'=>1,//缴费提醒类型（0：提醒缴费，1：提醒缴费成功）
                        'thing8'=>$now_user_info['name'],//户名
                        'thing17'=>$village_info['village_name'].$now_user_info['address'],//地址
                        'thing9'=>$order_type?:'缴费',//账单类型
                        'thing13'=>$thing13?:'无',//缴费方式
                        'thing10'=>$village_info['property_name'],//商家名称（物业名称）
                        'amount3'=>C('config.Currency_symbol').$now_order['money'],//缴费金额
                        'time12'=>date('Y年n月j日 H:i',$now_order['time']),//缴费时间
                    ],
                ),0,$village_info['property_id'],2);
        }
        //判断是否自动开具电子发票
        if($auto){
            $order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $name = !empty($village_bind['name'])?$village_bind['name']:$now_order['order_name'];
            $res = D('House_village_pay_order')->addInvoice($name,$user['phone'],$order_no,$e_config['duty_paragraph'],$now_order['order_name'],$now_order['money'],$rate,$village_bind['property_id']);
            fdump($res,1,true);
            if($res['status'] == '0000'){
                D('House_village_pay_order')->addERecord($e_config['duty_paragraph'],$name,$now_order['money'],$user['phone'],$res['fpqqlsh'],$order_no,$now_order['order_name'],$order_id,$now_order['bind_id']);
            }
        }
    }

    protected function get_user_village_info($bind_id,$village_id,$uid, $is_my=false){
        $now_user_info = D('House_village_user_bind')->get_one_by_bindId($bind_id);
        if(empty($now_user_info)){
            if ($is_my) {
                $this->returnCode('20120002');
            } else {
                $this->returnCode('20120004');
            }
        }
        $database_house_village_user_bind = D('House_village_user_bind');
        $where['parent_id|pigcms_id'] = $bind_id;
        $where['uid'] = $uid;
        $where['village_id'] = $village_id;
        $house_village_user_bind_count = $database_house_village_user_bind->where($where)->count();
        if(!$house_village_user_bind_count){
            if ($is_my) {
                $this->returnCode('20120002');
            } else {
                $this->returnCode('20120004');
            }
        }
        return $now_user_info;
    }

    public function test(){
        //echo 1;die;
        import('@.ORG.plan.plan_house_auto_meter_ele_reading');
        $model = new plan_house_auto_meter_ele_reading();
        $model->runTask();
    }


    public function ajax_face_img_new()
    {
        $base64Data = I('imageBase64');
        if (empty($base64Data)) {
            $this->returnCode(20140051);
        }

        $img_mer_id = sprintf("%09d", 0);
        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $up_dir = "./upload/house/".$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        $new_file = $up_dir.date('YmdHis_').uniqid().'.jpg';//统一默认转 jpg
        $savePath = rtrim(WEB_PATH,'/'). ltrim($new_file,'.');
        $arr = getimagesize($base64Data);
        $models = '\app\common\model\service\image\ImageService/encodePngToJpg';
        $param = [
            'params'     => [
                'imgPath'      => $base64Data,
//				'imgPath'      => rtrim(WEB_PATH,'/').ltrim($new_file,'.'),
                'savePath'      => $savePath,
                'format'       => 'jpg',  //统一默认转 jpg
                'heiht'        => $arr[1] ,
                'with'         => $arr[0]
            ]
        ];
        invoke_v20_service($models,$param);

        //判断是否需要上传至云存储
        $file_handle = new file_handle();
        $file_handle->upload($new_file);
        $img_path = trim($new_file,'.');
        $imageUrl = replace_file_domain($img_path);


        if($file_handle->check_open_oss()){
            @unlink($savePath);
        }

        $this->returnCode(0, ['url' => thumb_img($imageUrl, 200, 200, 'fill'),'imageUrl_path'=>ltrim($new_file,'.'), 'imageUrl'=>$imageUrl ]);
    }
}