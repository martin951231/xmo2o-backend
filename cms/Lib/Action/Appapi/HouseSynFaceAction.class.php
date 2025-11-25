<?php
/**
 * 社区app  人脸录入，同步人脸
 * Class HouseSynFace
 * @author weili
 * @date 2020/9/1
 */

class HouseSynFaceAction extends BaseAction
{
    public $login_id;//社区id
    public $login_roles;
    public function __construct()
    {
        parent::__construct();
        import("@.ORG.Token");
        $v2o_ticket = Token::getToken();
        $ticket = Token::checkToken($v2o_ticket);
        if($ticket && array_key_exists('memberId',$ticket) && $ticket['memberId']>0 && $ticket['extends']>0)
        {
            $this->login_id = $ticket['memberId'];
            $this->login_roles = $ticket['extends'];
        }else{
            $this->login_id = 0;
            $this->login_roles = 0;
        }
    }

    /**
     * Notes: 给用户绑定人脸成功后同步人脸设备
     * @author: weili
     * @datetime: 2020/9/1 14:25
     */
    public function synFace()
    {
        $is_v20 = I('is_v20', true);//默认v20访问
        if($is_v20 === 'v20'){
            if(!I('ticket')){
                $this->api_output_error(1002, '登录失效');
            }
        }else {
            //判断是否登录成功，
            if ($this->login_id <= 0 && $this->login_roles <= 0) {
                if ($is_v20) {
                    $this->api_output_error(1002, '登录失效');
                }
            }
        }
        $village_id = I('village_id');//物业id
        $pigcms_id = I('pigcms_id','', 'intval');
        $face_img = $_POST['face_img'];
        if(!is_array($face_img)){
            $face_img = explode(';',$face_img);
        }
        if (empty($face_img)) {
            if($is_v20) {
                $this->api_output_error(1001, '请上传人脸识别图片!');
            }
        }
        if (count($face_img)<1 || count($face_img)>5) {
            if($is_v20) {
                $this->api_output_error(1001, '请上传1张图片!');
            }
        }
        if(!$village_id && !$pigcms_id)
        {
            if($is_v20) {
                $this->api_output_error(1001, '参数异常!');
            }
        }
        $m_house_face_device = M('House_face_device');
        $condition_face_device['village_id'] = $village_id;
        $aDoor_count = $m_house_face_device->field(true)->where($condition_face_device)->count();
        //百度AI人脸
        $m_house_camera_device = M('House_camera_device');
        $condition_camera =[];
        $condition_camera['village_id'] = $village_id;
        $condition_camera['camera_status'] = 0;
        $camera_count = $m_house_camera_device->field(true)->where($condition_camera)->count();
//        $now_village_bind= D('House_village_user_bind')->get_one_by_bindId($pigcms_id);

        $where = ['pigcms_id' => $pigcms_id];
        $d_house_village_user_bind = M('House_village_user_bind');
        $village_info = $d_house_village_user_bind->where($where)->find();
//        exit(json_encode($village_info));
        if (empty($village_info['uid'])) {
            if($is_v20) {
                $this->api_output_error(1001, '请先绑定为平台用户!');
            }
        }
        $d_house_face_img = M('House_face_img');
        $data = array();
        $img = array();
        $img['uid'] = $village_info['uid'];
        $img['add_time'] = time();
        $img['status'] = 0;
        $img['bind_pigcms_id'] = $village_info['pigcms_id'];
        foreach ($face_img as $val) {
            if ($val) {
                $img['img_url'] = $val;
                $data[] = $img;
            }
        }
        if (empty($data)) {
            if($is_v20) {
                $this->api_output_error(1001, '请上传人脸识别图片!');
            }
        }
        $otherParam = [
            'log_name' => 'MobileAdminUserToDevicePlan',
            'logType' => 'mobileAdmin'
        ];
        import("@.ORG.Token");
        $ticket = Token::checkToken(I('ticket'));
        if ($ticket && $ticket['memberId'] && $ticket['extends']) {
            $user_id = $ticket['memberId'];
            $login_role = $ticket['extends'];
            $loginMsg = D('CommunityAdmin')->v20Login($user_id, $login_role,[],true);
            if (isset($loginMsg['sLoginType'])&&$loginMsg['sLoginType']) {
                $otherParam['operator_type'] = $loginMsg['sLoginType'].'_'.$loginMsg['sLoginRole'];
            }
            if (isset($loginMsg['show_account'])&&$loginMsg['show_account']) {
                $otherParam['log_operator'] = $loginMsg['show_account'];
            }
            if (isset($loginMsg['sLoginUid'])&&$loginMsg['sLoginUid']) {
                $otherParam['operator_id'] = $loginMsg['sLoginUid'];
            }
            if (isset($loginMsg['phone'])&&$loginMsg['phone']) {
                $otherParam['log_phone'] = $loginMsg['phone'];
            }
        }
        $save = array(
            'status' => 1,
            'del_time' => time()
        );
        // 删除现有图片 添加新图片
        $d_house_face_img->where(array('uid' => $village_info['uid'],'status' => array('in',[0,3])))->data($save)->save();
        $tip = $d_house_face_img->addAll($data);
        if($tip){
            if ($village_info['uid']) {
                // 计划任务
                if ($aDoor_count>0) {
                    D('House_face_img')->house_user_face_device_info($village_info['uid'],$pigcms_id,false,$otherParam);
                }
                // 上传百度AI 人脸库
                if ($camera_count>0) {
                    // 计划任务
                    D('House_face_img')->create_baidu_ai_face_plan($village_info['village_id'], 'baidu_face_edit', $village_info['uid']);
                }
            }
            $d_house_village_user_bind->where($where)->setField('add_face_time', time());
            if($is_v20) {
                $data['pigcms_id'] = $village_info['pigcms_id'];
                $this->api_output(0, $data, '成功');
            }
        }else{
            if($is_v20) {
                $this->api_output_error(1001, '绑定失败!');
            }
        }
    }
    /*     * *图片上传** */
    public function ajax_face_img(){
        $is_v20 = I('is_v20', true);//默认v20访问
        $app_type = I('app_type', '','trim');//默认v20访问
        $errors = [
            UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
            UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败'
        ];
        if ($_FILES['imgFile']['error'] === UPLOAD_ERR_OK) {
            $image = D('Image')->handle(0, 'houseface', 2, array('size' => 10), false);
            if ($image['error']) {
                if($is_v20) {
                    $this->api_output_error(1001, $image['message']);
                }
            } else {
                try {
                    empty($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
                    $savePath = $_SERVER['DOCUMENT_ROOT']. $image['url']['imgFile'];
                    $arr = getimagesize('.'.$image['url']['imgFile']);
                    $models = '\app\common\model\service\image\ImageService/encodePngToJpg';
                    $param = [
                        'params'     => [
                            'imgPath'      => $savePath,
                            'savePath'     => $savePath,
                            'format'       => 'jpg',  //统一默认转 jpg
                            'heiht'        => $arr[1] ,
                            'with'         => $arr[0]
                        ]
                    ];
                    $res = invoke_v20_service($models,$param);
                }catch (Exception $e) {
                    fdump_api([$e->getMessage(), '图片压缩失败', $image], 'houseApi/encodeImgToDataUrl');
                }
                
                if (!isset($image['extension']) || (!in_array($image['extension'], ['jpg', 'jpeg']) && !in_array($image['type'], ['image/jpg', 'image/jpeg']))) {
                    $img_url = D('Image')->transform_image($image['url']['imgFile']);
                }else if (function_exists('exif_read_data') && function_exists('imagecreatefromjpeg') && function_exists('imagerotate')) {
                    //处理图片旋转问题
                    $image_path=$image['url']['imgFile'];
                    if(strpos($image['url']['imgFile'], '/') === 0){
                        $image_path = '.' . $image_path;
                    }
                    $exif = exif_read_data($image_path);
                    $imgHandle = imagecreatefromjpeg($image_path);
                    fdump_api(['image_path'=>$image_path,'exif'=>$exif], '000Orientation',1);
                    if (isset($exif['Orientation']) && !empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 8:
                                $img = imagerotate($imgHandle, 90, 0);
                                break;
                            case 3:
                                $img = imagerotate($imgHandle, 180, 0);
                                break;
                            case 6:
                                $img = imagerotate($imgHandle, -90, 0);
                                break;
                            case 5:		//一般不用处理5的，发生特殊情况处理下
                                $img = imagerotate($imgHandle, -90, 0);
                                break;
                        }
                        imagejpeg($img, $image_path, 100);
                        if(file_exists($image_path)){
                            $image['url']['imgFile']=ltrim($image_path,'.');
                            $img_url=$image['url']['imgFile'];
                        }
                        fdump_api(['image_path'=>$image_path,'imgFile'=>$image['url']['imgFile']], '000Orientation',1);
                    }
                }
                if(!isset($img_url) || !$img_url) {
                    $img_url = $image['url']['imgFile'];
                }
                $imageUrl = replace_file_domain($img_url);
                if($app_type == 'packapp') {
                    $data['url'] = thumb_img($imageUrl, 200, 200, 'fill');
                    $data['imageUrl_path'] = $img_url;
                    $data['imageUrl']      = $imageUrl;
                }else{
                    $data['img_info']['url'] = thumb_img($imageUrl, 200, 200, 'fill');
                    $data['img_info']['imageUrl_path'] = $img_url;
                    $data['img_info']['imageUrl']      = $imageUrl;
                }
                if($is_v20) {
                    $this->api_output(0, $data, '成功');
                }
            }
        } else {
            $error_msg = isset($errors[$_FILES['imgFile']['error']]) ? $errors[$_FILES['imgFile']['error']] : '上传失败';
            if($is_v20) {
                $this->api_output_error(1001, $error_msg);
            }
        }
    }

    /**
     * Notes:获取用户绑定人脸
     * @author: weili
     * @datetime: 2020/9/3 13:15
     */
    public function user_bind_faces() {
        $is_v20 = I('is_v20', true);//默认v20访问
        if($is_v20 === 'v20'){
            if(!I('ticket')){
                $this->api_output_error(1002, '登录失效1');
            }
        }else {

            //判断是否登录成功，
            if ($this->login_id <= 0 && $this->login_roles <= 0) {
                if ($is_v20) {
                    $this->api_output_error(1002, '登录失效');
                }
            }
        }
        $village_id = I('village_id');
        if (empty($village_id)) {
            $this->api_output_error(1001,'参数异常');
        }
        $pigcms_id = I('pigcms_id', '');
        if (empty($pigcms_id)) {
            $this->api_output_error(1001,'参数异常');
        }
        $now_village_bind= D('House_village_user_bind')->get_one_by_bindId($pigcms_id);
        $d_house_village_user_bind = M('House_village_user_bind');
        $d_house_face_img = M('House_face_img');

        $where['a.pigcms_id'] = $now_village_bind['pigcms_id'];
//        $now_village_bind = $d_house_village_user_bind->where(array('pigcms_id' => $now_village_bind['pigcms_id']))->find();
        if (empty($now_village_bind['uid'])) {
//            $this->api_output_error(1001,'请先绑定为平台用户');
            if(empty($now_village_bind['phone'])){
                $data_user = array(
                    'nickname' 	=> $now_village_bind['name'],
                    'source' 	=> 'houseautoreg',
                );
                $reg_result = D('User')->autoreg($data_user);
                $uid = $reg_result['uid'];
            }else{
                $where['phone'] = $now_village_bind['phone'];
                $where['status'] = array('in','0,1');
                $res = D('User')->where($where)->field('uid')->find();
                if(empty($res['uid'])){
                    $data_user = array(
                        'phone' 	=> $now_village_bind['phone'],
                        'nickname' 	=> $now_village_bind['name'],
                        'source' 	=> 'houseautoreg',
                    );
                    $reg_result = D('User')->autoreg($data_user);
                    $uid = $reg_result['uid'];
                }else{
                    $uid = $res['uid'];
                }
            }
            if ($uid) {
                D('house_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data(['uid'=>$uid])->save();
            }
        }

        $m_house_face_device = M('House_face_device');
        $condition_face_device['village_id'] = $village_id;
        $aDoor_count = $m_house_face_device->field(true)->where($condition_face_device)->count();

        $m_house_camera_device = M('House_camera_device');
        $condition_camera =[];
        $condition_camera['village_id'] = $village_id;
        $condition_camera['camera_status'] = 0;
        $camera_count = $m_house_camera_device->field(true)->where($condition_camera)->count();

        if ((!$aDoor_count || $aDoor_count<=0) && (!$camera_count || $camera_count<=0)) {
            $this->api_output_error(1001,'当前小区没有人脸门禁或者摄像机设备');
        }
        $where = array(
                'pigcms_id' => $pigcms_id,
            );
        $village_info = $d_house_village_user_bind->where($where)->find();
        if (!empty($village_info)) {
            if ($village_id != $village_info['village_id']) {
                $this->api_output_error(1001,'所选操作对象错误');
            }
            if ($village_info['pigcms_id'] != $now_village_bind['pigcms_id'] && $village_info['parent_id'] != $now_village_bind['pigcms_id']) {
                $this->api_output_error(1001,'所选操作对象错误');
            }
            $now_village_bind = $village_info;
        }
        $now_village_bind['is_rdirect'] = false;
        if ($now_village_bind['uid']>0) {
            $now_village_bind['face_num'] = $d_house_face_img->where(array('uid' => $now_village_bind['uid'], 'status'=>0, 'img_url' => array('neq', '')))->limit(3)->count();
        } else {
            $now_village_bind['face_num'] = 0;
        }
        if ($now_village_bind['face_num'] > 0) {
            $now_village_bind['is_bind_face'] = true;
        } else {
            $now_village_bind['is_bind_face'] = false;
        }
        $uid = $now_village_bind['uid'];
        $add_face_time = $now_village_bind['add_face_time'];
        if ($uid>0) {
            $face_img_list = $d_house_face_img->where(array('uid' => $uid, 'status'=>array('in',[0,3]), 'img_url' => array('neq', '')))->limit(5)->select();
            if($face_img_list){
                import('@.ORG.face.word_encryption');
                $word_encryption = new word_encryption();
                foreach ($face_img_list as &$v){
                    if (3==$v['status']) {
                        // 针对加密图片解密
                        $v['img_url'] = $word_encryption->text_decrypt($v['img_url']);
                    }
                }
                unset($v);
            }
        } else {
            $face_img_list = [];
        }
        if($face_img_list){
            foreach($face_img_list as $kk=>&$vv){
                $vv['face_img_url'] = thumb_img($vv['img_url'], 200, 200, 'fill');
            }
        }else{
            $face_img_list = [];
        }

        $now_village_bind['face_img'] =$face_img_list;
        if ($now_village_bind['add_face_time'] && 0==$now_village_bind['face_img_status'] && time() > ($add_face_time + 60*10)) {
            $now_village_bind['is_new_face'] = true;
        } else {
            $now_village_bind['is_new_face'] = false;
        }
        //查询图片上传配置
        $face_img_set=D('House_village_info')->where(array('village_id'=>$village_id))->field('face_door_set')->find();
        if (!empty($face_img_set)){
            $now_village_bind['face_img_set']=$face_img_set['face_door_set'];
        }
        // 1标识 前端开启人脸识别  2标识 关闭用户端人脸
        $now_village_bind['my_face_door_switch'] = $this->config['my_face_door_switch'] ? 2 : 1;
        // 提示由后台提供
        $type = isset($_POST['type']) ? trim($_POST['type']) : 'tip1';
        if ('tip1' == $type && 1 == $now_village_bind['my_face_door_switch']) {
            $now_village_bind['navigationBarTitleText']  = '上传人脸门禁';
            $now_village_bind['tip_title']               = '请上传【1】张脸部轮廓清晰的照片，并确保为【本人】照片，否则影响开门';
            $now_village_bind['reload_tip']              = '上次保存的照片已成功同步至设备中，为保护您的个人信息，上传设备成功后删除了照片信息，如需更换照片，重新上传即可。';
            $now_village_bind['wait_tip']                = '上次保存的照片正在同步至设备中，请稍等。';
            $now_village_bind['success_tip']             = '上次保存的照片已成功同步至设备。';
            $now_village_bind['err_tip']                 = "上次保存的照片同步失败。原因：{$now_village_bind['face_img_reason']}";
            $now_village_bind['note_arr']                = [
                '1.为提高人脸识别的速度，请尽量正脸面对手机进行拍照',
                '2.为保证人脸识别的真实性，添加图片时只能相机拍照上传',
                '3.不同手机型号上传方式可能会不同',
                '4.确保照片中均为本人照片，不能上传其他人的照片，否则会影响开门',
            ];
            $now_village_bind['img_tip_arr']                = [
                [
                    'img' => '../static/images/selfie1.png',
                    'tip' => '正对手机',
                ],
                [
                    'img' => '../static/images/selfie3.png',
                    'tip' => '光线充足',
                ],
                [
                    'img' => '../static/images/selfie2.png',
                    'tip' => '放慢动作',
                ],
            ];
        } elseif('tip1' == $type) {
            $now_village_bind['navigationBarTitleText']  = '';
            $now_village_bind['tip_title']               = '';
            $now_village_bind['reload_tip']              = '';
            $now_village_bind['wait_tip']                = '';
            $now_village_bind['success_tip']             = '';
            $now_village_bind['err_tip']                 = '';
            $now_village_bind['note_arr']                = [];
            $now_village_bind['img_tip_arr']             = [];
        }
        
        if($is_v20) {
            $this->api_output(0, $now_village_bind, '成功');
        }else{
            exit(json_encode(['code'=>5000,'msg'=>'接口异常']));
        }
    }


    /**
     * Notes:获取用户绑定人脸提示
     */
    public function userFaceTip() {
        $arr = [];
        // 1标识 前端开启人脸识别  2标识 关闭用户端人脸
        $arr['my_face_door_switch'] = $this->config['my_face_door_switch'] ? 2 : 1;
        // 提示由后台提供
        $type = isset($_POST['type']) ? trim($_POST['type']) : 'tip2';
        if ('tip2' == $type && 1 == $arr['my_face_door_switch']) {
            $arr['navigationBarTitleText']  = '上传人脸';
            $arr['bg_img']                  = '../static/images/face_bg.png';
            $arr['tip1']                    = '无戴帽/无墨镜/面部清晰/光线均匀~';
            $arr['tip2']                    = '请将人像对准取景框';
        } elseif('tip2' == $type) {
            $arr['navigationBarTitleText']  = '';
            $arr['bg_img']                  = '';
            $arr['tip1']                    = '';
            $arr['tip2']                    = '';
        }
        $this->api_output(0, $arr, '成功');
    }
    
    /**
     * Notes: 获取用户绑定人脸 （暂废弃）
     * @author: weili
     * @datetime: 2020/9/1 14:30
     */
    public function user_bind_face()
    {
        $is_v20 = I('is_v20', true);//默认v20访问
        //判断是否登录成功，
        if($this->login_id<=0 && $this->login_roles<=0)
        {
            if($is_v20)
            {
                $this->api_output_error(1002,'登录失效');
            }
        }
        $pigcmsId = I('pigcms_id','', 'intval');
        if ($pigcmsId) {
            $msg = D('Face_door_service')->get_bind_user_device_img($pigcmsId);
            $info = D('House_village_user_bind')->where(['pigcms_id'=>$pigcmsId])->field('face_img_status,face_img_reason')->find();
        } else {
            $msg = [];
            $info = [];
        }
        $new_img_info = [];
        if($msg['img_info']){
            foreach ($msg['img_info'] as $key=>$val){
                $new_img_info[$key]['img_url'] = $val['img_url'];
                $new_img_info[$key]['img_id'] = $val['id'];
            }
        }
        if($info){
            switch ($info['face_img_status']){
                case '0':
                    $info['face_img_status_msg'] = '同步中';
                    break;
                case '1':
                    $info['face_img_status_msg'] = '同步成功';
                    break;
                case '2':
                    $info['face_img_status_msg'] = '同步失败';
                    break;
            }
        }
        $data['info'] = $info;
        $data['dataImg'] = $new_img_info;
        $data['pigcms_id'] = $pigcmsId;
        if($is_v20) {
            $this->api_output(0, $data, '成功');
        }else{
            exit(json_encode(['code'=>5000,'msg'=>'接口异常']));
        }
    }
    /*重新同步数据到设备*/
    public function new_bind_face() {
        $is_v20 = I('is_v20', true);//默认v20访问
        if($is_v20 === 'v20'){
            if(!I('ticket')){
                $this->api_output_error(1002, '登录失效');
            }
        }else {
            //判断是否登录成功，
            if ($this->login_id <= 0 && $this->login_roles <= 0) {
                if ($is_v20) {
                    $this->api_output_error(1002, '登录失效');
                }
            }
        }
        $otherParam = [
            'log_name' => 'MobileAdminUserToDevicePlan',
            'logType' => 'mobileAdmin'
        ];
        import("@.ORG.Token");
        $ticket = Token::checkToken(I('ticket'));
        if ($ticket && $ticket['memberId'] && $ticket['extends']) {
            $user_id = $ticket['memberId'];
            $login_role = $ticket['extends'];
            $loginMsg = D('CommunityAdmin')->v20Login($user_id, $login_role,[],true);
            if (isset($loginMsg['sLoginType'])&&$loginMsg['sLoginType']) {
                $otherParam['operator_type'] = $loginMsg['sLoginType'].'_'.$loginMsg['sLoginRole'];
            }
            if (isset($loginMsg['show_account'])&&$loginMsg['show_account']) {
                $otherParam['log_operator'] = $loginMsg['show_account'];
            }
            if (isset($loginMsg['sLoginUid'])&&$loginMsg['sLoginUid']) {
                $otherParam['operator_id'] = $loginMsg['sLoginUid'];
            }
            if (isset($loginMsg['phone'])&&$loginMsg['phone']) {
                $otherParam['log_phone'] = $loginMsg['phone'];
            }
        }
        $village_id = I('village_id');
        if (empty($village_id)) {
            $this->api_output_error(1001,'参数异常');
        }
        $pigcms_id = I('pigcms_id', '');// 操作对象

        $d_house_village_user_bind = M('House_village_user_bind');
        $where = array(
            'pigcms_id' => $pigcms_id,
        );
        $village_info = $d_house_village_user_bind->where($where)->find();
        if (empty($village_info['uid'])) {
            $this->api_output_error(1001,'请先绑定为平台用户');
        }

//      D('House_village_face_door')->create_village_plan($village_info['village_id'],'house_user_face_device_info', $village_info['uid']);
        $info = D('House_face_img')->house_user_face_device_info($village_info['uid'],$pigcms_id,false,$otherParam);
        if($info && (!isset($info['err_msg']) || !$info['err_msg'])){
            $d_house_village_user_bind->where($where)->setField('add_face_time', time());
            $this->api_output(0, [], '成功');
        } elseif (isset($info['err_msg']) && $info['err_msg']) {
            $this->api_output_error(1001,$info['err_msg']);
        } else{
            $this->api_output_error(1001,'没有可同步设备');
        }
    }
    /**
     * Notes: 设备一键同步
     * @author: weili
     * @datetime: 2020/9/2 11:00
     */
    public function aKeySynFace()
    {
        $is_v20 = I('is_v20', true);//默认v20访问
        if($is_v20 === 'v20'){
            if(!I('ticket')){
                $this->api_output_error(1002, '登录失效');
            }
        }else {
            //判断是否登录成功，
            if ($this->login_id <= 0 && $this->login_roles <= 0) {
                if ($is_v20) {
                    $this->api_output_error(1002, '登录失效');
                }
            }
        }
        $device_id = I('device_id','','intval');
        $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (empty($face_device_data)) {
            $this->api_output_error(1001,'对不起，当前设备不存在或者已经被删除');
        }
        //a5 a6 处理方法一样
        if($face_device_data['device_type'] == 5 || $face_device_data['device_type'] == 6)
        {
            $face_device_data['device_type'] = 5;
        }
        switch ($face_device_data['device_type']){
            case '1':
                //a1
                if($is_v20) {
                    $this->api_output_error(1001, 'A1设备异常');
                }
                break;
            case '2':
                //a2
                if($is_v20) {
                    $this->api_output_error(1001, 'A2设备异常');
                }
                break;
            case '3':
                //a3
                if (D('House_village_face_door')->base_face_a3_url && D('House_village_face_door')->a3_token && D('House_village_face_door')->a3_key) {
                    D('House_village_face_door')->face_a3_door_add($device_id,$face_device_data);
                } else {
                    if($is_v20) {
                        $this->api_output_error(1001, '同步失败，未进行配置，请前往 智慧社区-社区配置-A3人脸识别门禁 配置');
                    }
                }
                break;
            case '4':
                //a4
                $info  = D('Face_door_a4_service')->faceDeviceSynchro($device_id, $face_device_data, 1);
//                if (!$info && $is_v20) {
//                    $this->api_output_error(1001, 'A4设备异常');
//                }
                break;
            case '5':
                //a5 a6
                $a5Service = D('Face_door_a5_service');
                if (!empty($face_device_data['cloud_device_id'])) {
                    // 添加一键同步功能
                    D('Face_door_service')->face_device_check($device_id, $face_device_data);
                } else {
                    $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
                    $otherParam = [
                        'orderGroupId' => $orderGroupId,
                        'operatorType' => 'userAdmin',
                    ];
                    $a5Service->addEquipment($device_id, $face_device_data,$otherParam);
                }
                break;
            case '7':
                //a7
                $info = D('Face_door_a7_service')->face_a7_door_add($device_id, $face_device_data);
                if (!$info && $is_v20) {
                    $this->api_output_error(1001, 'A7设备异常');
                }
                break;
            case '23':
                //todo d3移动端一键同步
                D('Face_d3_request_record')->d3_get_status($device_id,$face_device_data['village_id'], $face_device_data);
                break;
        }
        if($is_v20) {
            $this->api_output_error(1001, '同步成功,任务已下发，等待任务执行');
//            $this->api_output(0, [], '同步成功,任务已下发，等待任务执行');
        }else{
            exit(json_encode(['code'=>5000,'msg'=>'接口异常']));
        }
    }
    /**
     * Notes: 上传图片绑定用户（弃用）
     * @author: weili
     * @datetime: 2020/9/1 14:00
     *
     */
    public function uploadFace()
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
            UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败'
        ];
        if(!isset($_POST['pigcms_id']))
        {
            $this->api_output_error(1001,'参数异常');
        }
        if ($_FILES['imgFile']['error'] === UPLOAD_ERR_OK) {
            $user = M('house_village_user_bind')->where(['pigcms_id'=>$_POST['pigcms_id']])->field('uid,phone,name')->find();
            if(empty($user['uid'])){
                if(empty($user['phone'])){
                    $data_user = array(
                        'nickname' 	=> $user['name'],
                        'source' 	=> 'houseautoreg',
                    );
                    $reg_result = D('User')->autoreg($data_user);
                    $user['uid'] = $reg_result['uid'];
                }else{
                    $where['phone'] = $user['phone'];
                    $where['status'] = array('in','0,1');
                    $res = D('User')->where($where)->field('uid')->find();
                    if(empty($res['uid'])){
                        $data_user = array(
                            'phone' 	=> $user['phone'],
                            'nickname' 	=> $user['name'],
                            'source' 	=> 'houseautoreg',
                        );
                        $reg_result = D('User')->autoreg($data_user);
                        $user['uid'] = $reg_result['uid'];
                    }else{
                        $user['uid'] = $res['uid'];
                    }
                }
                D('house_village_user_bind')->where(['pigcms_id'=>$_POST['pigcms_id']])->save(['uid'=>$user['uid']]);
            }

            $image = D('Image')->handle(0, 'houseface', 4, array('size' => 10), false);
            if ($image['error']) {
                $this->api_output_error(1001,$image['message']);
            } else {
                if (!isset($image['extension']) || (!in_array($image['extension'], ['jpg', 'jpeg']) && !in_array($image['type'], ['image/jpg', 'image/jpeg']))) {
                    $imgUrl = D('Image')->transform_image($image['url']['imgFile']);
                }
                if(!isset($imgUrl) || !$imgUrl) {
                    $imgUrl = $image['url']['imgFile'];
                }
                $id = $this->add_face($user['uid'], $imgUrl);
                $imageUrl = replace_file_domain($imgUrl);
                $data['url']           = thumb_img($imageUrl, 200, 200, 'fill');
                $data['imageUrl_path'] = $imgUrl;
                $data['imageUrl']      = $imageUrl;
                $data['id']            = $id;
                $this->api_output(0, $data, '成功');
            }
        } else {
            $error_msg = isset($errors[$_FILES['imgFile']['error']]) ? $errors[$_FILES['imgFile']['error']] : '上传失败';
            $this->api_output_error(1001,$error_msg);
        }
    }
    //弃用
    public function  add_face($uid,$img_url)
    {
        $face_img = D('House_face_img');
        $data['uid'] = $uid;
        $data['img_url'] = $img_url;
        $data['add_time'] = time();
        $data['status'] = 0;
        $id = $face_img->add_face_img($data);
        return $id;
    }
    /**
     * Notes: 兼容v20返回方式
     * @param int $status
     * @param array $data
     * @param string $msg
     * @author: weili
     * @datetime: 2020/9/1 11:10
     */
    public function api_output($status = 0, $data = [], $msg = ''){
        $output = [
            'status' => $status  == '0' ? 1000 : $status,
            'msg' 	 => $msg,
            'data' 	 => $data
        ];
        exit(json_encode($output));
    }

    /**
     * Notes:兼容v20返回方式
     * @param int $status
     * @param string $msg
     * @author: weili
     * @datetime: 2020/9/1 11:11
     */
    public function api_output_error($status = 1001, $msg = ''){
        return $this->api_output($status, [], $msg);
    }
}