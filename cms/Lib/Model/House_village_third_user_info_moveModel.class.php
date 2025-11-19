<?php


class House_village_third_user_info_moveModel extends Model{
    public function userDataProvincialToMunicipal($village_id,$isThirdRYID=true, $third_config=[]) {
        if (!$third_config) {
            $where = [
                'type' => 'chinapopin_sichuan',
                'village_id' => $village_id,
            ];
            $third_config = M('House_village_third_config')->where($where)->find();
        }
        if (empty($third_config)) {
            fdump_api(['未配置配置项>'.__LINE__,$village_id],'third/errUserDataProvincialToMunicipalLog',true);
            return true;
        }
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $db_house_village_third_user_info = M('House_village_third_user_info');
        $provincialStandardAddress = isset($third_config['provincialStandardAddress'])&&$third_config['provincialStandardAddress']?trim($third_config['provincialStandardAddress']):'';
        $judgeStandardAddress = isset($third_config['judgeStandardAddress'])&&$third_config['judgeStandardAddress']?trim($third_config['judgeStandardAddress']):'';
        if (!$provincialStandardAddress) {
            // 人员类型，01 住户，02 租户，03 物业人员，04 服务人员， 05 来访人员
            $whereAddress = [];
            $whereAddress['type'] = ['in', ['03','04']];
            $whereAddress['village_id'] = $village_id;
            $provincialStandardAddress = $db_house_village_third_user_info->where($whereAddress)->order('third_user_id DESC')->getField('third_jzd_dzxz');
        }
        // 省级数据迁移市级
        if ($isThirdRYID) {
            $where = [
                'village_id' => $village_id,
                'third_type' => 'chinapopin_sichuan',
                'img_url' => ['neq', ''],
                'ownerId' => ['eq', ''],
                'check_status' => 1,
            ];
        } else {
            $where = [
                'village_id' => $village_id,
                'third_type' => 'chinapopin_sichuan',
                'img_url' => ['neq', ''],
                'ywlsh' => ['neq', ''],
                'ownerId' => ['eq', ''],
                'check_status' => 1,
            ];
        }
        if ($judgeStandardAddress) {
            $where['third_jzd_dzxz'] = ['like', '%' . $judgeStandardAddress . '%'];
        }
        $msg = $db_house_village_third_user_info->where($where)->order('third_user_id DESC')->select();
        $zip_file_name = $provincialStandardAddress. '.zip';
        $txt_file_name = $provincialStandardAddress. '.txt';
        if (!empty($msg)) {
            $file_path = rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/runtime/third/move_file/'.$village_id.'/'.$provincialStandardAddress.'/';
            $downBaseUrl = C('config.site_url') . '/runtime/third/move_file/'.$village_id.'/'.$provincialStandardAddress.'/';
            $txt_file_name_path = $file_path . $txt_file_name;
            $dirName = dirname($txt_file_name_path);
            if(!file_exists($dirName)){
                mkdir($dirName,0777,true);
            }
            $txtData = [];
            $isFileHandle = false;
            $file_handle = new file_handle();
            if($file_handle->check_open_oss()) {
                $isFileHandle = true;
            }
            foreach ($msg as $item) {
                $address_arr = explode('号', $item['third_jzd_dzxz']);
                $address = array_pop($address_arr);
                if (!$address) {
                    $address = array_pop($address_arr);
                }
                if (!empty($address_arr) && $address) {
                    $third_jzd_dzxz = $provincialStandardAddress . $address;
                } else {
                    $third_jzd_dzxz = $item['third_jzd_dzxz'];
                }
                if ($isThirdRYID&&!$item['ywlsh']&&$item['third_ryid']) {
                    $data_desc = [
                        'RYID' => strtolower($item['third_ryid']),
                        'XM' => $item['third_xm'],
                        'ZJHM' => $item['third_zjhm'],
                        'TYPE' => '01',
                        'JZD_DZXZ' => $third_jzd_dzxz,
                        'ID' => $item['third_user_id'],
                    ];
                } elseif ($item['ywlsh']) {
                    $data_desc = [
                        'RYID' => $item['ywlsh'],
                        'XM' => $item['third_xm'],
                        'ZJHM' => $item['third_zjhm'],
                        'PHONE' => $item['phone'],
                        'TYPE' => $item['type'],
                        'JZD_DZXZ' => $third_jzd_dzxz,
                        'ID' => $item['third_user_id'],
                    ];
                } else {
                    continue;
                }
                $txtData[] = $data_desc;
                $img_filename = replace_file_domain($item['img_url']);
                $fileExistJudge = true;
                if ($isFileHandle && !$file_handle->file_exists($img_filename)) {
                    $fileExistJudge = false;
                } elseif (!file_exists('.'.$item['img_url'])) {
                    $fileExistJudge = false;
                }
                if (false && !$fileExistJudge && $item['photo']) {
                    $photo = str_replace('data:image/jpeg;base64,','',$item['photo']);
                    $img_url = $this->base64_to_img($photo, 'houseThird');
                    $img_filename = replace_file_domain($img_url);
                }
                $new_file = $file_path.$data_desc['RYID'].'.jpg';
                copy($img_filename,$new_file);
            }
            $txtDataJson = json_encode($txtData,JSON_UNESCAPED_UNICODE);
            file_put_contents($txt_file_name_path,$txtDataJson);
            $zipPath = rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/runtime/third/images_zip/';//压缩文件保存目录
            $path = rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/runtime/third/move_file/'.$village_id.'/';
            $this->zipFile($path, $zipPath,$zip_file_name, $provincialStandardAddress);
            return [
                'txt_url' => $downBaseUrl.$txt_file_name,
                'zip_url' => C('config.site_url') . '/runtime/third/images_zip/'.$zip_file_name
            ];
        }
    }

    private function base64_to_img($base_img, $path='face_log') {
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = "./upload/{$path}/".$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'jpg';
            $new_file = $up_dir . date('YmdHis_') . uniqid() . '.' . $type;
            if(file_put_contents($new_file, base64_decode($base_img))){
                //判断是否需要上传至云存储
                $file_handle = new file_handle();
                $file_handle->upload($new_file);
                $img_path = trim($new_file,'.');
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }

    public function zipFile($path, $zipPath, $zipPathName, $zipParentPathName){
        !is_dir($zipPath) ? mkdir($zipPath, 0755, true) : '';
        $zip = new ZipArchive();
        $dir_list = scandir($path);
        if (empty($dir_list)) {
            return false;
        }
        foreach ($dir_list as $dir_name) {
            if ($dir_name == '.' || $dir_name == '..') {
                continue;
            }
            $dir_path = $path . $dir_name; //待压缩目录
            if (!is_dir($dir_path)) {
                continue;
            }
            $zipFile = $zipPath . $zipPathName; //压缩包名称
            $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $file_list = scandir($dir_path); //文件名
            foreach ($file_list as $file_name) {
                if ($file_name == '.' || $file_name == '..') {
                    continue;
                }
                $zip->addFile($dir_path . '/' . $file_name, $zipParentPathName.'/'.$file_name); //第二个参数使压缩文件名为待压缩文件原名(否则压缩包里会有多级目录)
            }
            $zip->close();
        }
    }


    public function downExcel($village_id) {
        $where = [
            'type' => 'chinapopin_sichuan',
            'village_id' => $village_id,
        ];
        $third_config = M('House_village_third_config')->where($where)->find();
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title  = '小区数据';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $where = [
            'village_id' => $village_id,
            'status'     => 1,
        ];
        $msg = M('House_village_user_bind')->where($where)->order('third_xqryid DESC')->select();
        $user_list = [];
        foreach ($msg as $item) {
            if ($item['uid'] == 0 && !$item['name'] && !$item['phone']) {
                continue;
            }
            $user_list[] = $item;
        }

        $length = ceil(count($user_list) / 1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G',
            'H','I',
        ];
        $pValue_arr = [
            'id(人员ID)',
            'gmsfhm(公民身份证号码)',
            'xm(姓名)',
            'xb(性别)',
            'lxdh(联系电话)',
            'djsj(登记时间)',
            'rylb(身份)',
            'djdz(登记地址)',
            'xqid(小区编号)',
        ];
        $key_length = count($pValue_arr) - 1;

        $provincialStandardAddress = isset($third_config['provincialStandardAddress'])&&$third_config['provincialStandardAddress']?trim($third_config['provincialStandardAddress']):'';
        $db_house_village = D('House_village');
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($user_list) . '个用户');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }
            $index = 2;
            $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
            foreach ($cell_list as $cell) {
                $objActSheet->getColumnDimension($cell)->setWidth(25);
            }
            foreach ($user_list as $value) {
                $pCoordinateKey = 0;
                $pigcms_id = $value['third_xqryid'] ? $value['third_xqryid'] : $value['pigcms_id'];
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $pigcms_id);
                $pCoordinateKey = $pCoordinateKey + 1;
                $id_card = $value['id_card'] ? $value['id_card'] : '';
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $id_card);
                $pCoordinateKey = $pCoordinateKey + 1;
                $name = $value['name'] ? $value['name'] : '';
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $name);
                $pCoordinateKey = $pCoordinateKey + 1;
                if ($value['authentication_field'] && unserialize($value['authentication_field'])) {
                    $authentication_field = unserialize($value['authentication_field']);
                    $sex = isset($authentication_field['sex']['value']) && $authentication_field['sex']['value'] == '女' ? 2 : 1;
                } else {
                    $sex = 1;
                }
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $sex);
                $pCoordinateKey = $pCoordinateKey + 1;
                $phone = $value['phone'] ? $value['phone'] : '';
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $phone);
                $pCoordinateKey = $pCoordinateKey + 1;
                $add_time = $value['add_time'] ? $value['add_time'] : '';
                if ($add_time) {
                    $add_time_txt = date('Y-m-d H:i:s', $add_time);
                } else {
                    $add_time_txt = date('Y-m-d H:i:s');
                }
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $add_time_txt);
                // 0房主  1，家人  2租客  3，更新房主  4工作人员 5访客 6出入证
                $pCoordinateKey = $pCoordinateKey + 1;
                $type = $value['type'] ? $value['type'] : 0;
                if ($type == 0 || $type == 3 || $type == 1) {
                    $type_txt = '01';
                } elseif ($type == 2) {
                    $type_txt = '02';
                } elseif ($type == 4) {
                    $type_txt = '03';
                } elseif ($type == 5 || $type == 6) {
                    $type_txt = '05';
                } else {
                    $type_txt = '01';
                }
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $type_txt);
                $pCoordinateKey = $pCoordinateKey + 1;
                if ($provincialStandardAddress && $type == 4) {
                    $address = $provincialStandardAddress;
                } else {
                    $address = $db_house_village->get_single_floor_room($value['single_id'],$value['floor_id'],$value['layer_id'],$value['vacancy_id'],$village_id);
                    $address = $provincialStandardAddress . $address;
                    $address = str_replace('层#', '楼', $address);
                    $address.='号';
                }
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $address);
                $pCoordinateKey = $pCoordinateKey + 1;
                $village_num = $third_config['village_num'] ? $third_config['village_num'] : $third_config['city_village_num'];
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $village_num);
                $index++;
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
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }
}