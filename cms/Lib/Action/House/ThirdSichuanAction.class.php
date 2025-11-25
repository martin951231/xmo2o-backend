<?php
/**
 * 四川人口信息服务网功能对接
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/23 14:06
 */

class ThirdSichuanAction extends BaseAction {

    /**
     * Notes: 配置的基本信息
     * @author: wanzy
     * @date_time: 2020/9/23 14:55
     */
    public function index() {
        $village_id = $this->house_session['village_id'];
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        $type = 'chinapopin_sichuan';
        if($chinapopinOpen){
            $type = 'ezc_chinapopin';
        }
        $where = [
            'type' => $type,
            'village_id' => $village_id,
        ];
        $house_village_third_config = M('House_village_third_config')->where($where)->find();

        $ip = GetHostByName($_SERVER['SERVER_NAME']);
        $house_village_third_config['ip'] = $ip;
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        if($chinapopinOpen){
            $house_village_third_config['addr'] = $this->config['site_url'] . '/appapi.php?g=Appapi&c=Face_door&a=third_chinapopin&village_id='.$village_id;
        }else{
            $house_village_third_config['addr'] = $this->config['site_url'] . '/appapi.php?g=Appapi&c=Face_door&a=third_sichuan_img&village_id='.$village_id;
        }

        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        $this->assign('chinapopinOpen',$chinapopinOpen);
        $this->assign('third_config',$house_village_third_config);
        $this->display();
    }

    /**
     * Notes: 添加编辑配置项
     * @author: wanzy
     * @date_time: 2020/9/23 15:38
     */
    public function edit() {
        $village_id = $this->house_session['village_id'];

        $third_id = I('third_id', 0, 'intval');
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        $type = 'chinapopin_sichuan';
        if($chinapopinOpen){
            $type = 'ezc_chinapopin';
        }
        if ($third_id<=0) {
            $where = [
                'type' => $type,
                'village_id' => $village_id,
            ];
            $house_village_third_config = M('House_village_third_config')->where($where)->find();
            if ($house_village_third_config) {
                $third_id = $house_village_third_config['third_id'];
            }
        }
        $status = I('status', 0, 'intval');
        $village_num = I('village_num', '', 'trim');
        $city_village_num = I('city_village_num', '', 'trim');
        $username = I('username', '', 'trim');
        $password = I('password', '', 'trim');
        $clientId = I('clientId', '', 'trim');
        $clientSecret = I('clientSecret', '', 'trim');
        $encrypted_public_key = I('encrypted_public_key', '', 'trim');
        $face_url = I('face_url', '', 'trim');
        if (!$village_num) {
            $this->error('请填写小区编码！');
        }
        if (!$username && !$chinapopinOpen) {
            $this->error('请填写账号！');
        }
        if (!$password && !$chinapopinOpen) {
            $this->error('请填写密码！');
        }
        if (!$clientId) {
            $this->error('请填写clientId！');
        }
        if (!$clientSecret) {
            $this->error('请填写clientSecret！');
        }
        if (!$encrypted_public_key) {
            $this->error('请填写RSA加密公钥！');
        }
        if (!$face_url && !$chinapopinOpen) {
            $this->error('请复制四川人口网的登记网址到人脸登记网址！');
        }
        if ($third_id) {
            $set = [
                'type' => $type,
                'village_id' => $village_id,
                'status' => $status,
                'village_num' => $village_num,
                'city_village_num' => $city_village_num,
                'username' => $username,
                'password' => $password,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'encrypted_public_key' => $encrypted_public_key,
                'face_url' => $face_url,
                'last_time' => time(),
            ];
            $set = M('House_village_third_config')->where(['third_id' =>$third_id])->save($set);
            if (!$set) {
                $this->error('配置编辑失败！');
            }
        } else {
            $set = [
                'type' => $type,
                'village_id' => $village_id,
                'status' => $status,
                'village_num' => $village_num,
                'city_village_num' => $city_village_num,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'encrypted_public_key' => $encrypted_public_key,
                'face_url' => $face_url,
                'add_time' => time(),
            ];
            $set = M('House_village_third_config')->add($set);
            if (!$set) {
                $this->error('配置添加失败！');
            }
        }
        $this->success('配置成功！',U('index'));
    }

    // 需要进行获取内容的文件类型
    private $txt_type = [
        'text/plain'
    ];
    // 上传文件允许后缀
    private $file_allow_exts = [
        'rar'
    ];
    // 上传文件允许类型
    private $file_allow_types = [
        'application/x-rar', 'application/octet-stream'
    ];
    // 上传文件最大值  默认100M
    private $max_size = 100;

    /**
     * Notes: 文件上传
     * @author: wanzy
     * @date_time: 2020/9/24 10:15
     */
    public function upload_file() {
        if (IS_POST) {
            $village_id = $this->house_session['village_id'];
            $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
            $type = 'chinapopin_sichuan';
            if($chinapopinOpen){
                $type = 'ezc_chinapopin';
            }
            $where = [
                'type' => $type,
                'village_id' => $village_id,
            ];
            $house_village_third_config = M('House_village_third_config')->where($where)->find();
            if ($house_village_third_config['status']!=1) {
                $this->error('请先行配置对应参数');
            }
            if ($_FILES['imgFile']['error'] != 4) {
                set_time_limit(0);
                ini_set('memory_limit','-1');
                $upload_dir = './runtime/third/source_file/';
                // 记录绝对路径
                $dir = $_SERVER['DOCUMENT_ROOT'] . '/runtime/third/source_file/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                // 默认100M
                $upload->maxSize = 100 * 1024 * 1024;
                $upload->allowExts = $this->file_allow_exts;
                $upload->allowTypes = $this->file_allow_types;
                $upload->savePath = $upload_dir;
                $upload->saveRule = 'uniqid';
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    $title = $uploadList[0]['savename'];
                    $url = $dir.$title;
                    $this->file_info($url);
                    $param = [];
                    $param['village_id'] = $village_id;
                    $param['type'] = 'handle_third_user_info';
                    D('House_village_third_user_info')->add_plan($param);
                    $this->success('同步记录成功，后续数据执行中！',U('index'));
                } else {
                    $this->error($upload->getErrorMsg());
                }
            } else {
                $this->error('没有选择文件');
            }
        } else {
            $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
            $this->assign('chinapopinOpen',$chinapopinOpen);
            $this->display();
        }
    }

    public function handle_third_user_list() {
        $village_id =  $this->house_session['village_id'];
        $status = I('status','0','intval');
        $where = [
            'village_id' => $village_id
        ];
        if ($status) {
            if (-1==$status) {
                $where['status'] = 0;
            } else {
                $where['status'] = $status;
            }
        }
        $from = I('from','0','intval');
        if ($from) {
            if (-1==$from) {
                $where['from'] = 0;
            } else {
                $where['from'] = $from;
            }
        }
        $check_status= I('check_status','0','intval');
        if ($check_status) {
            if (-1==$check_status) {
                $where['check_status'] = 0;
            } else {
                $where['check_status'] = $check_status;
            }
        }
        $info = D('House_village_third_user_info')->handle_third_user_list($where,10);
        $this->assign($info);
        $this->display();
    }
    
    public function chinapopinCheckSingle() {
        $village_id =  $this->house_session['village_id'];
        $id         =  I('id');
        $info = D('House_village_third_user_info')->chinapopinCheck($village_id, $id);
        if (isset($info['msg']) && $info['msg']['success']=='true' || $info['msg']['success']) {
            $this->success('审核成功！');
        } else {
            $this->error('审核失败，请重试~');
        }
    }

    /**
     * Notes: 文件处理
     * @param $url
     * @author: wanzy
     * @date_time: 2020/9/24 10:30
     */
    private function file_info($url) {
        $write_url =  '/runtime/third/write_file';
        if (!is_dir('.'.$write_url)) {
            mkdir('.'.$write_url, 0777, true);
        }
        $extract = $_SERVER['DOCUMENT_ROOT'] . $write_url;
        $rar_file = rar_open($url);
        $entries = rar_list($rar_file);
        foreach ($entries as $entry) {
            $entry->extract($extract);
        }
        rar_close($rar_file);
        $img_info = $_SERVER['DOCUMENT_ROOT'] . $write_url;
        $filenames = $this->getDir($img_info);

        if ($filenames) {
            $last_filenames = array_pop($filenames);
            $last_file_type = mime_content_type($last_filenames);
            if (in_array($last_file_type, $this->txt_type)) {
                $this->readNLP($last_filenames);
            } else {
                set_time_limit(0);
                ini_set('memory_limit','-1');
                foreach ($filenames as $value){
                    echo $value.'<br/>';
                    echo mime_content_type($value).'<br/>';
                    $file_type = mime_content_type($value);
                    if (in_array($file_type, $this->txt_type)) {
                        $this->readNLP($value);
                    }
                }
            }
        }
        return true;
    }

    /**
     * Notes: 查询出底层文件
     * @param $path
     * @param $files
     * @author: wanzy
     * @date_time: 2020/9/24 10:30
     */
    private function searchDir($path,&$files){
        if(is_dir($path)){
            $opendir = opendir($path);
            while ($file = readdir($opendir)){
                if($file != '.' && $file != '..'){
                    $this->searchDir($path.'/'.$file, $files);
                }
            }
            closedir($opendir);
        }
        if(!is_dir($path)){
            $files[] = $path;
        }
    }

    /**
     * Notes: 获取地址
     * @param $dir
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/24 11:05
     */
    private function getDir($dir){
        $files = array();
        $this->searchDir($dir, $files);
        return $files;
    }

    /**
     * Notes: 读取文件内容
     * @param $filename
     * @author: wanzy
     * @date_time: 2020/9/24 11:06
     */
    private function readNLP($filename)
    {
        $filepath = $filename;
        $handle = fopen($filename, "r");//读取二进制文件时，需要将第二个参数设置成'rb'

        //通过filesize获得文件大小，将整个文件一下子读到一个字符串中
        $contents = fread($handle, filesize ($filename));
        fdump('>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/third/read_nlp_log',true);
        fdump($contents,'api/log/'.date('Ymd').'/third/read_nlp_log',true);
        fclose($handle);
        $contents_arr = json_decode($contents, true);
        // 处理下数据然后存到数据库
        // 对文件进行处理
        $file_arr = explode('/', $filepath);
        $file_name = array_pop($file_arr);
        $address_total = str_replace('.txt', '', $file_name);
        $filename_new = implode('/', $file_arr);
        $img_dir = '/upload/house/village/'.date('Ymd').'/'.date('H').'/';
        $upload_dir = '.'.$img_dir;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $village_id =  $this->house_session['village_id'];
        $db_house_village_third_user_info = M('House_village_third_user_info');
        set_time_limit(0);
        ini_set('memory_limit','-1');
        foreach ($contents_arr as $val) {
            $where = [
                'village_id' => $village_id,
                'third_ryid' => $val['RYID']
            ];
            $third_user_info = $db_house_village_third_user_info->field('third_user_id, img_url')->where($where)->find();
            if ($third_user_info && $third_user_info['img_url']) {
                // 同一小区处理过不在做记录
                continue;
            }
            $img_url = $filename_new .'/'. $val['RYID'] . '.jpg';
            $new_file = $_SERVER['DOCUMENT_ROOT'] . $img_dir . $val['RYID'] . '.jpg';
            $file_img = $this->copyImgFile($img_url, $new_file);
            if ($file_img) {
                $img_url = $img_dir . $val['RYID'] . '.jpg';
            } else {
                $img_url = '';
            }
            if ($third_user_info && !$img_url) {
                continue;
            }
            $third_ryid = $val['RYID'];
            $xqryid = $val['XQRYID']?$val['XQRYID']:'';
            $third_xm = $val['XM'];
            $third_xbdm = $val['XBDM'];
            $third_zjhm = $val['ZJHM'];
            $third_jzd_dzxz = $val['JZD_DZXZ'];
            $address = str_replace($address_total, '', $val['JZD_DZXZ']);
            $data = [
                'village_id' => $village_id,
                'third_ryid' => $third_ryid,
                'third_xqryid' => $xqryid,
                'third_xm' => $third_xm,
                'third_xbdm' => $third_xbdm,
                'third_zjhm' => $third_zjhm,
                'third_jzd_dzxz' => $third_jzd_dzxz,
                'address' => $address,
                'img_url' => $img_url,
                'status' => 0,
                'add_time' => time(),
            ];
            if ($third_user_info) {
                $db_house_village_third_user_info->where(['third_user_id' => $third_user_info['third_user_id']])->data($data)->save();
            } else {
                $db_house_village_third_user_info->data($data)->add();
            }
        }
//        D('House_village_third_user_info')->handle_third_user_info($village_id);
        return true;
    }

    /**
     * Notes: 复制文件
     * @param $file
     * @param $new_file
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/24 14:13
     */
    private function copyImgFile($file, $new_file) {
        if( !file_exists($file) || !$new_file){
            return false;
        }
        return copy($file, $new_file);
    }


    public function handle_third_user_info_resynchronize() {
        $village_id =  $this->house_session['village_id'];
        $info = D('House_village_third_user_info')->where(['village_id' => $village_id,'third_type' => 'chinapopin_sichuan'])->field('MIN(times) as times_min')->find();
        $times_min = intval($info['times_min']) ? intval($info['times_min']) : 0;
        D('House_village_third_user_info')->handle_third_user_info_resynchronize($village_id,$times_min);
        $this->success('命令已经下发，后续执行中！',U('index'));
    }


    // 调试用
    public function txt_info() {
        $contents_arr = array (
            0 =>
                array (
                    'RYID' => '41CCAF6F7DE1A266E0539B64400AFD39',
                    'XM' => '罗**',
                    'XBDM' => '2',
                    'ZJHM' => '5107**********0580',
                    'JZD_DZXZ' => '四川省绵阳市三台县北坝镇樟树路80号1栋1单元2楼1号',
                ),
            1 =>
                array (
                    'RYID' => '41CCAF60DE3CA266E0539B64400AFD39',
                    'XM' => '万**',
                    'XBDM' => '1',
                    'ZJHM' => '5107**********0572',
                    'JZD_DZXZ' => '四川省绵阳市三台县北坝镇樟树路80号1栋1单元2楼1号',
                )
        );
        $filepath = 'D:/work/phpstudy_pro/WWW/pigo2o/runtime/third/write_file/彩虹城/四川省绵阳市三台县北坝镇樟树路80号/四川省绵阳市三台县北坝镇樟树路80号.txt';
        $file_arr = explode('/', $filepath);
        $file_name = array_pop($file_arr);
        $address_total = str_replace('.txt', '', $file_name);
        dump($file_name);
        dump($address_total);
        $filename_new = implode('/', $file_arr);
        $db_house_village_user_bind = M('House_village_user_bind');
        $info = [];


        $img_dir = '/upload/house/village/'.date('Ymd').'/'.date('H').'/';
        $upload_dir = '.'.$img_dir;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $village_id =  $this->house_session['village_id'];
        foreach ($contents_arr as $val) {
            $name = $val['XM'];
            $id_card = $val['ZJHM'];
            $surname = mb_substr($name,0,1);
//            $where = [];
//            $where['_string'] = "substring(`name`,1,1)='{$surname}'";
//            $where['_string'] .= " AND  replace(`id_card`,substring(`id_card`,5,char_length(`id_card`)-8),'**********')='{$id_card}'";
//            $info = $db_house_village_user_bind->where($where)->find();
            $img_url = $filename_new .'/'. $val['RYID'] . '.jpg';
            $new_file = $_SERVER['DOCUMENT_ROOT'] . $img_dir . $val['RYID'] . '.jpg';
            $file_img = $this->copyImgFile($img_url, $new_file);
            if ($file_img) {
                $img_url = $img_dir . $val['RYID'] . '.jpg';
            }
            $third_ryid = $val['RYID'];
            $xqryid = $val['XQRYID']?$val['XQRYID']:'';
            $third_xm = $val['XM'];
            $third_xbdm = $val['XBDM'];
            $third_zjhm = $val['ZJHM'];
            $third_jzd_dzxz = $val['JZD_DZXZ'];
            $address = str_replace($address_total, '', $val['JZD_DZXZ']);
            $data = [
                'village_id' => $village_id,
                'third_ryid' => $third_ryid,
                'xqryid' => $xqryid,
                'third_xm' => $third_xm,
                'third_xbdm' => $third_xbdm,
                'third_zjhm' => $third_zjhm,
                'third_jzd_dzxz' => $third_jzd_dzxz,
                'address' => $address,
                'img_url' => $img_url,
                'add_time' => time(),
            ];
            dump($data);
        }
        dump($filename_new);
        dump($info);
    }

    public function test() {
//       $msg = D('House_village_third_user_info')->handle_third_user_info(50);
        $db_house_village_user_bind = M('House_village_user_bind');
        $where = [];
        $where['_string'] = "`name` LIKE '万%' AND village_id=50 AND vacancy_id=8752";
        $where['_string'] .= " AND  `id_card` LIKE '5107%' AND `id_card` LIKE '%0572'";
        $info = $db_house_village_user_bind->where($where)->find();
       dump($info);
    }

    public function test1() {
        $txt_msg = D('House_village_third_user_info')->personInoutInfo(50);
        dump($txt_msg);
    }

    public function moveConfig() {
        $village_id = $this->house_session['village_id'];
        if (IS_POST) {
            $third_id = I('third_id', 0, 'intval');
            if ($third_id<=0) {
                $where = [
                    'type' => 'chinapopin_sichuan',
                    'village_id' => $village_id,
                ];
                $house_village_third_config = M('House_village_third_config')->where($where)->find();
                if ($house_village_third_config) {
                    $third_id = $house_village_third_config['third_id'];
                }
            }
            $provincialStandardAddress = I('provincialStandardAddress', '', 'trim');
            if (!$provincialStandardAddress) {
                $this->error('请填写省厅标准5段式地址！');
            }
            $judgeStandardAddress = I('judgeStandardAddress', '', 'trim');
            if ($third_id) {
                $set = [
                    'type' => 'chinapopin_sichuan',
                    'village_id' => $village_id,
                    'provincialStandardAddress' => $provincialStandardAddress,
                    'judgeStandardAddress' => $judgeStandardAddress,
                    'last_time' => time(),
                ];

                $set = M('House_village_third_config')->where(['third_id' =>$third_id])->save($set);
                if (!$set) {
                    $this->error('配置编辑失败！');
                }
            } else {
                $this->error('无前置配置不需要迁移！');
            }
            $this->success('配置成功！',U('index'));
        } else {
            $where = [
                'type' => 'chinapopin_sichuan',
                'village_id' => $village_id,
            ];
            $house_village_third_config = M('House_village_third_config')->where($where)->find();
            $this->assign('third_config',$house_village_third_config);
            $this->display();
        }
    }

    public function userDataProvincialToMunicipal() {
        $village_id = $this->house_session['village_id'];
        $data = D('House_village_third_user_info_move')->userDataProvincialToMunicipal($village_id);
        $this->success($data);
    }

    // 上传文件允许后缀
    private $move_file_allow_exts = [
        'json'
    ];
    // 上传文件允许类型
    private $move_file_allow_types = [
        'application/json'
    ];
    public function moveUploadFile() {
        if (IS_POST) {
            $village_id = $this->house_session['village_id'];
            $where = [
                'type' => 'chinapopin_sichuan',
                'village_id' => $village_id,
            ];
            $house_village_third_config = M('House_village_third_config')->where($where)->find();
//            if ($house_village_third_config['status']!=1) {
//                $this->error('请先行配置对应参数');
//            }
            if ($_FILES['imgFile']['error'] != 4) {
                set_time_limit(0);
                ini_set('memory_limit','-1');
                $upload_dir = './runtime/third/source_json_file/';
                // 记录绝对路径
                $dir = $_SERVER['DOCUMENT_ROOT'] . '/runtime/third/source_json_file/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                // 默认100M
                $upload->maxSize = 100 * 1024 * 1024;
                $upload->allowExts = $this->move_file_allow_exts;
                $upload->allowTypes = $this->move_file_allow_types;
                $upload->savePath = $upload_dir;
                $upload->saveRule = 'uniqid';
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    $title = $uploadList[0]['savename'];
                    $url = $dir.$title;
                    $filename = $url;
                    $handle = fopen($filename, "r");//读取二进制文件时，需要将第二个参数设置成'rb'

                    //通过filesize获得文件大小，将整个文件一下子读到一个字符串中
                    $contents = fread($handle, filesize ($filename));
                    fclose($handle);
                    $contents_arr = json_decode($contents, true);
                    $db_house_village_third_user_info = D('House_village_third_user_info');
                    $errMoveUploadFile = [];
                    foreach ($contents_arr as $item) {
                        $where = [];
                        $where['village_id'] = $village_id;
                        if (isset($item['ywlsh'])) {
                            $third_ryid = strtoupper($item['ywlsh']);
                            $where['_string'] = "ywlsh='{$item['ywlsh']}' OR third_ryid='{$item['ywlsh']}' OR third_ryid='$third_ryid'";
                            $updateParam = [];
                            if (isset($item['ownerId'])&&$item['ownerId']) {
                                $updateParam['ownerId'] = $item['ownerId'];
                            }
                            if (isset($item['address'])&&$item['address']) {
                                $updateParam['third_jzd_dzxz'] = $item['address'];
                            }
                            if (!empty($updateParam)){
                                $updateParam['last_time'] = time();
                                $save = $db_house_village_third_user_info->where($where)->save($updateParam);
                                if ($save==false) {
                                    $errMoveUploadFile[] = ['village_id' => $village_id, 'item' => $item];
                                }
                            }
                        }
                    }
                    fdump_api([$contents_arr,$db_house_village_third_user_info->_sql()],"third/{$village_id}/moveUploadFileLog",true);
                    if (!$errMoveUploadFile) {
                        fdump_api($errMoveUploadFile,"third/{$village_id}/errMoveUploadFileLog",true);
                    }
                    $this->success('同步记录成功！',U('moveUploadFile'));
                } else {
                    $this->error($upload->getErrorMsg());
                }
            } else {
                $this->error('没有选择文件');
            }
        } else {
            $this->display();
        }
    }
}