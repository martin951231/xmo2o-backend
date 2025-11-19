<?php

/*
    小区单元控制器
 */

class UnitAction extends BaseAction
{

    //D3定制停车参数
    public $AppID = '平台测试';
    public $AppSecret = '12345678';
    public $url = 'http://admin.scxhwtkj.com/parking/api';
    public $url_v2 = 'http://admin.scxhwtkj.com/parking/api/v2';

    protected $village_id;
    public $relationship = ['1' => '车主', '2' => '父母', '3' => '配偶', '4' => '子女', '5' => '亲戚', '6' => '朋友', '7' => '其他'];
    public $car_color = array(
        array(
            'id' => 'A',
            'lable' => '白色'
        ),
        array(
            'id' => 'B',
            'lable' => '灰色'
        ),
        array(
            'id' => 'C',
            'lable' => '黄色'
        ),
        array(
            'id' => 'D',
            'lable' => '粉色'
        ),
        array(
            'id' => 'E',
            'lable' => '红色'
        ),
        array(
            'id' => 'F',
            'lable' => '紫色'
        ),
        array(
            'id' => 'G',
            'lable' => '绿色'
        ),
        array(
            'id' => 'H',
            'lable' => '蓝色'
        ),
        array(
            'id' => 'I',
            'lable' => '棕色'
        ),
        array(
            'id' => 'J',
            'lable' => '黑色'
        ),
        array(
            'id' => 'Z',
            'lable' => '其他'
        ),

    );

    public $car_type_arr=[
        ['value'=>'0','name'=>'汽车'],
        ['value'=>'1','name'=>'电瓶车'],
    ];


    public function _initialize()
    {
        $this->thirdHouseIntercept = [
            'Unit#single_edit','Unit#single_add','Unit#single_delete','Unit#single_import',// 楼栋 添加 编辑 删除 导入
            'Unit#unit_add','Unit#unit_edit','Unit#unit_delete','Unit#unit_import',// 单元 添加 编辑 删除 导入
            'Unit#layer_add','Unit#layer_edit','Unit#layer_delete','Unit#layer_import',// 楼层 添加 编辑 删除 导入
            'Unit#import_village_add','Unit#import_village_edit','Unit#import_village_del','Unit#roomExportImport', // 房屋 导入 编辑  删除
        ];
        parent::_initialize();
//        if (empty($this->house_session['is_open_estate'])) {
//            redirect($this->config['site_url'].'/shequ.php?g=House&c=Index&a=index');
//            exit;
//        }
        $this->village_id = $this->house_session['village_id'];
        $this->village = D('House_village')->where(array('village_id' => $this->village_id))->find();
        $this->role_id = $this->house_session['role_id']; //角色id
    }

    public $pay_list_type = array(
        'property' => '物业费',
        'water' => '水费',
        'electric' => '电费',
        'gas' => '燃气费',
        'park' => '停车费',
        'custom' => '其他缴费',
        'custom_payment' => '自定义缴费',
    );

    public function index()
    {
        //单元列表-查看 权限
        $database_house_village_floor = D('House_village_floor');
        $where=array('is_public_rental'=>0);
        $where['village_id'] = $this->house_session['village_id'];
        if ($_GET['single_id']) {
            $where['single_id'] = $_GET['single_id'];
        }
        $where['status'] = ['neq', 4];
        $list = $database_house_village_floor->house_village_floor_page_list($where);

        if ($list['list']['list']) {
            foreach ($list['list']['list'] as $k => &$v) {

                if ($v['single_id']) {
                    $single = M('House_village_single')->where(array('id' => $v['single_id'],'status'=>['neq', 4]))->getField('single_name');

                    $v['single_name'] = $single;
                } else {
                    $v['single_name'] = '';
                }

            }
        }
        if (!$list) {
            $this->error('数据处理有误！');
        } else {
            $this->assign($list);

        }

        $this->display();
    }

    public function unit_add()
    {
        //单元列表-添加 权限
        if (!in_array(21, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->house_session['village_id'];
        $property_id = $this->house_session['property_id'];
        // 查询小区物业服务管家信息
        $house_services_housekeeper = M('House_services_housekeeper');
        $house_worker = M('House_worker');
        if (IS_POST) {
            $database_house_village_floor = D('House_village_floor');
            $result = $database_house_village_floor->house_village_floor_add($_POST);
            if (!$result) {
                $this->error('数据处理有误！');
            } else {
                if ($result['status']) {
                    $floor_id = $result['floor_id'];
                    // 单元添加成功
                    $data[] = '';
                    $data['village_id'] = $village_id;
                    $data['property_id'] = $property_id;
                    $data['single_id'] = intval($_POST['single_id']) ? intval($_POST['single_id']) : 0;
                    $data['floor_id'] = $floor_id;
                    if ($_POST['work_arr']) {
                        $data['work_arr'] = implode(',', $_POST['work_arr']);
                    }
                    if ($_POST['welcome_tip']) {
                        $data['welcome_tip'] = $_POST['welcome_tip'];
                    }
                    if ($_POST['qy_qrcode']) {
                        $data['qy_qrcode'] = $_POST['qy_qrcode'];
                    }
                    if (isset($_POST['template_type'])) {
                        $data['template_type'] = intval($_POST['template_type']);
                    }
                    if ($_POST['template_url']) {
                        $data['template_url'] = $_POST['template_url'];
                    }
                    if ($_POST['effect_img']) {
                        $data['effect_img'] = $_POST['effect_img'];
                    }
                    $data['add_time'] = time();
                    $house_services_housekeeper->data($data)->add();
                    $this->success($result['msg'], U('index', array('single_id' => $_POST['single_id'])));
                } else {
                    $this->error($result['msg']);
                }
            }
        } else {
            $this->assign('single_id', $_GET['single_id']);
            $this->_get_floor_type_list();
            $this->_get_property_list();
            $third_where = [
                'type' => 'chinapopin_sichuan',
                'village_id' => $this->house_session['village_id'],
            ];
            $third_config = M('House_village_third_config')->where($third_where)->find();
            $this->assign('third_config', $third_config);


            $qyhelp_url = $this->config['site_url'] . '/static/file/village/information/qyhelp.pdf';
            $this->assign('qyhelp_url', $qyhelp_url);

            $enterprise_wx_corpid = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->getField('corpid');
            // 查询该小区小所有工作人员
            if ($enterprise_wx_corpid) {
                $where_worker = [
                    'village_id' => $village_id,
                    'qy_status' => 1,
                    'qy_id' => ['neq', ''],
                    'status' => 1
                ];
            } else {
                $where_worker = [
                    'village_id' => $village_id,
                    'status' => 1
                ];
            }
            $worker_list = $house_worker->where($where_worker)->order('wid ASC')->select();
            if (!$worker_list) {
                $worker_list = [];
            }
            $this->assign('worker_list', $worker_list);
            $this->assign('enterprise_wx_corpid', $enterprise_wx_corpid);

            $this->display();
        }
    }


    public function unit_import()
    {
        //单元-导入 权限
        if (!in_array(465, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');

                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
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
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);  //删除文件
                    $error_arr = array();

                    // 字符串替换去除数字以外的
                    $pattern_number = "/[^0-9]/";
                    // 正则检查是否为纯数字
                    $check_number = "/^[0-9]+$/";
                    // 正则检查是否为字母或数字
                    $pattern = '/^[a-zA-Z0-9]+$/u';
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        if (count($result) == 0) {
                            $this->assign('waitSecond', 10);
                            $this->error('表格未成功识别。请使用 excel软件 将表格更换为 xlsx格式 重试一下');
                        }
                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            //if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['H'] === null && $vv['I'] === null && $vv['J'] === null && $vv['K'] === null) continue;
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null) continue;

                            $floor_number = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            if (empty($floor_number)) {
                                $vv['F'] = '请填写单元编号！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            if (!preg_match($check_number, trim($floor_number))) {
                                $vv['F'] = '单元编号只允许数字';
                                $error_arr[] = $vv;
                                continue;
                            }
                            if ($floor_number < 0 || $floor_number > 99) {
                                $vv['F'] = "该单元编号不符合规则，请填写其他1-99的单元编号！";
                                $error_arr[] = $vv;
                                continue;
                            }
                            $floor_name = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            if (empty($floor_name)) {
                                $floor_name = $floor_number;
                            }
                            if (!$floor_name) {
                                $vv['F'] = '单元不存在,请重新选择或者填写！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            if (empty($vv['D'])) {
                                $vv['F'] = '请填写单元面积！';
                                $error_arr[] = $vv;
                                continue;
                            }

                            $single_name = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            if (!$single_name) {
                                $vv['F'] = '该楼栋不存在,请重新选择！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $single_data['single_name'] = $single_name;
                            $single_data['village_id'] = $this->village_id;
                            $single_data['status'] = 1;
                            $single = M('House_village_single')->where($single_data)->find();
                            if (!$single) {
                                $vv['F'] = '该楼栋不存在,请重新选择！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $w = array(
                                'floor_name' => $floor_name,
                                'village_id' => $this->village_id
                            );
                            if ($single) {
                                $w['single_id'] = $single['id'];
                                $w['status'] = ['neq', 4];
                                $msg = D('House_village_floor')->where($w)->find();
                                if ($msg) {
                                    $vv['F'] = '该单元已经添加不可重复添加！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }

                            $tmpdata = array();
                            $tmpdata['floor_name'] = $floor_name;
                            $tmpdata['floor_layer'] = $single_name;
                            $tmpdata['floor_area'] = floatval($vv['D']);
                            $tmpdata['single_id'] = $single['id'];
                            $tmpdata['status'] = 1;
                            $tmpdata['village_id'] = $this->village_id;
                            $tmpdata['add_time'] = time();
                            if (!$floor_number) {
                                $floor_number = preg_replace($pattern_number, '', $tmpdata['floor_name']);
                                // 单元编号 暂时同一个楼栋下仅限1-99数字编号
                                if ($floor_number) {
                                    if (strlen($floor_number) > 2) {
                                        // 超过2位 截取后2位
                                        $floor_number = substr($floor_number, -2, 2);
                                    } elseif (strlen($floor_number) < 2) {
                                        // 不足2位 补足2位
                                        $floor_number = str_pad($floor_number, 2, "0", STR_PAD_LEFT);
                                    }
                                    $tmpdata['floor_number'] = $floor_number;

                                    // 去除重复
                                    $where_repeat = [
                                        'village_id' => $this->village_id,
                                        'single_id' => $single['id'],
                                        'floor_number' => $floor_number,
                                        'status' => ['neq', 4]
                                    ];
                                    $repeat_id = D('House_village_floor')->where($where_repeat)->getField('floor_id');
                                    if ($repeat_id) {
                                        $vv['F'] = '该单元编号【取单元名称后2位】已经存在，请填写其他1-99的单元名称！';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                }
                            } else {
                                $tmpdata['floor_number'] = $floor_number;
                                // 去除重复
                                $where_repeat = [
                                    'village_id' => $this->village_id,
                                    'single_id' => $single['id'],
                                    'floor_number' => $floor_number,
                                    'status' => ['neq', 4]
                                ];
                                $repeat_id = D('House_village_floor')->where($where_repeat)->getField('floor_id');
                                if ($repeat_id) {
                                    $vv['F'] = "该单元编号【{$floor_number}】已经存在，请填写其他1-99的单元名称！";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }

                            $last_user_id = D('House_village_floor')->data($tmpdata)->add();
                            if (!$last_user_id) {
                                $vv['F'] = '保存失败。';
                                $error_arr[] = $vv;
                            }
                        }
                        if (empty($error_arr)) {
                            $this->success('导入成功');
                            exit;
                        } else {
                            $num = count($error_arr);
                            echo '<javascript>alert("失败' . $num . '条，点击下载)";</javascript>';
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);
                            date_default_timezone_set('Europe/London');
                            $objExcel = new PHPExcel();

                            $title = $this->village['village_name'] . '社区-单元导入失败列表';
                            /*以下是一些设置 ，什么作者  标题之类的*/
                            $objExcel->getProperties()->setCreator($title)
                                ->setLastModifiedBy($title)
                                ->setTitle($title)
                                ->setSubject("失败信息导出")
                                ->setDescription("备份数据")
                                ->setKeywords("excel")
                                ->setCategory("result file");

                            $i = 0;
                            $objExcel->createSheet();
                            $objExcel->setActiveSheetIndex($i);
                            $objExcel->getActiveSheet()->setTitle('单元列表');
                            $objActSheet = $objExcel->getActiveSheet();
                            $objActSheet->setCellValue('A1', '单元名称');
                            $objActSheet->setCellValue('B1', '楼号');
                            $objActSheet->setCellValue('C1', '单元面积');
                            $objActSheet->setCellValue('F1', '失败原因');
                            $index = 2;

                            $where['village_id'] = $this->village_id;
                            $where['status'] = 1;
                            $single = D('House_village_single')->where($where)->select();
                            if ($single) {
                                $single_data = [];
                                foreach ($single as $k => $v) {
                                    $single_data[] = $v['single_name'];
                                }
                                $single_data = implode(',', $single_data);
                            } else {
                                $single_data = '';
                            }
                            foreach ($error_arr as $value) {
                                $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                $objExcel->setActiveSheetIndex(0);
                                $objActSheet = $objExcel->getActiveSheet();
                                $objValidation = $objActSheet->getCell("B2")->getDataValidation(); //这一句为要设置数据有效性的单元格
                                $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                                    ->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                                    ->setAllowBlank(false)
                                    ->setShowInputMessage(true)
                                    ->setShowErrorMessage(true)
                                    ->setShowDropDown(true)
                                    ->setErrorTitle('输入的值有误')
                                    ->setError('您输入的值不在下拉框列表内.')
                                    ->setPromptTitle('楼号')
                                    ->setFormula1('"' . $single_data . '"');

                                $index++;
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
                            header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $tip = "单元编号必须是与当前现有同一楼栋下单元编号不重复的1-99的数字，单元名称如有特殊命名，可通过编辑修改";
            $this->assign('tip', $tip);
            $this->display();
        }
    }

    public function import_village()
    {
        //房间管理-查看 权限
        if (!in_array(405, $this->house_session['menus']) && !in_array(467, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $where['village_id'] = $this->village_id;
        if ($_GET['status'] != '') {
            $where['status'] = $_GET['status'];
        }

        $find_type = $_GET['find_type'];
        $find_value = isset($_GET['find_value']) && !empty($_GET['find_value']) ? trim($_GET['find_value']):'';
        if ($find_value) {
            $find_value=htmlspecialchars($find_value,ENT_QUOTES);
            if ($find_type == 1) {
//                $where['usernum'] = array('like', '%' . $find_value . '%');
                $where['_string'] = "usernum LIKE '%{$find_value}%' OR property_number LIKE '%{$find_value}%'";
            } else if ($find_type == 2) {
//                $where['name'] = array('like', '%' . $find_value . '%');
                $where['pigcms_id']=-1;
                $user_bind_where['name'] = array('like', '%' . $find_value . '%');
                $user_bind_where['village_id'] = $this->village_id;
                $user_bind = D('House_village_user_bind')->field('vacancy_id')->where($user_bind_where)->select();
                if ($user_bind) {
                    $uids = [];
                    foreach ($user_bind as $k => $v) {
                        $uids[] = $v['vacancy_id'];
                    }
                    $where['pigcms_id'] = array('in', $uids);
                }

            } else if ($find_type == 3) {
//                $where['phone'] = array('like', '%' . $find_value . '%');
                $where['pigcms_id']=-1;
                $user_bind_where['phone'] = array('like', '%' . $find_value . '%');
                $user_bind_where['village_id'] = $this->village_id;
                $user_bind = D('House_village_user_bind')->field('vacancy_id')->where($user_bind_where)->select();
                if ($user_bind) {
                    $uids = [];
                    foreach ($user_bind as $k => $v) {
                        $uids[] = $v['vacancy_id'];
                    }
                    $where['pigcms_id'] = array('in', $uids);

                }
//                var_dump($uids);
            } else if ($find_type == 4) {
                $where['address'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 5) {
                $where['card_no'] = array('like', '%' . $find_value . '%');
            }
        }
        if ($_GET['single_id'] != 0) {
            $where['single_id'] = $_GET['single_id'];
        }
        if ($_GET['floor_id'] != 0) {
            $where['floor_id'] = $_GET['floor_id'];
        }
        if ($_GET['layer_id'] != 0) {
            $where['layer_id'] = $_GET['layer_id'];
        }
        if ($_GET['vacancy_id'] != 0) {
            $where['pigcms_id'] = $_GET['vacancy_id'];
        }

        $where['is_del'] = 0;
        $where['is_public_rental']=0;
        $property_id = $this->house_session['property_id'];
        $result = $database_house_village_user_vacancy->house_village_user_vacancy_page_list($where, true, 'sort desc,pigcms_id desc',20,$property_id);

        if (!$result) {
            $this->error('数据处理有误！');
        }
        $limit_info = D('House_property')->get_room_limit_num($this->house_session['property_id']);
        $this->assign('limit_info', $limit_info);

        $this->assign('result', $result['result']);


        //楼栋
        $single_list = M('House_village_single')->where(array('village_id' => $this->village_id, 'status' => 1,'is_public_rental'=>0))->order('sort desc, id asc')->select();
        if ($single_list) {
            $this->assign('single_list', $single_list);
        } else {
            $this->assign('single_list', []);
        }
        if ($_GET['vacancy_id']) {
            $this->assign('vacancy_id', $_GET['vacancy_id']);
        } else {
            $this->assign('vacancy_id', '');
        }
        if ($_GET['page']) {
            $this->assign('page', $_GET['page']);
        } else {
            $this->assign('page', 1);
        }
        $this->display();
    }


    public function record_list()
    {
        $count_user = M('House_village_user_record')->where(array('vacancy_id' => $_GET['vacancy_id']))->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $user_record = M('House_village_user_record')->where(array('vacancy_id' => $_GET['vacancy_id']))->limit($p->firstRow . ',' . $p->listRows)->select();
//        var_dump($user_record);
        $this->assign('dataList', $user_record);
        $this->assign('pagebar', $p->show());
        $this->display();
    }
    public function card_list()
    {
        $count_user = M('House_village_vacancy_iccard')->where(array('vacancy_id' => $_GET['vacancy_id'],'status'=>1))->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $user_record = M('House_village_vacancy_iccard')->where(array('vacancy_id' => $_GET['vacancy_id'],'status'=>1))->limit($p->firstRow . ',' . $p->listRows)->select();
       // var_dump($user_record);exit;
        $this->assign('dataList', $user_record);
        $this->assign('pagebar', $p->show());
        $this->display();
    }
    public function iccard_del(){
        $id=$_GET['id'];
        if (!empty($id)){
            $card_info=M('House_village_vacancy_iccard')->where(array('id' => $id,'status'=>1))->find();
            $res=M('House_village_vacancy_iccard')->where(array('id' => $id,'status'=>1))->data(['status'=>4])->save();
        if ($res>0){
            $user_info=D('House_village_user_bind')->where(['village_id'=>$this->village_id,'vacancy_id'=>$card_info['vacancy_id'],'type'=>0,'status'=>1])->field('single_id,floor_id,layer_id,vacancy_id,type,parent_id,pigcms_id,uid,phone,status,property_starttime,property_endtime')->find();
            $device_list=D('House_face_device')->where(['village_id'=>$this->village_id,'is_del'=>0])->field('device_type,device_id,device_name,device_sn,floor_id')->select();
            if (!empty($device_list)&&!empty($user_info)){
                foreach ($device_list as $kk => &$vv) {
                    if (in_array($vv['device_type'],[26])){
                        D('Face_d6_request_record')->del_card_record($card_info['ic_card'],$vv['device_id'],$user_info['pigcms_id'],$card_info['village_id']);
                    }
                }
            }
            exit(json_encode(array('status'=>1,'msg'=>'删除成功！')));
        }
        }
        exit(json_encode(array('status'=>0,'msg'=>'删除失败！')));
    }

    //提取老用户的房间信息
    public function updata_old_village_room_info()
    {

        set_time_limit(0);
        $database_house_village_user_bind = D('House_village_user_bind');
        //$database_house_village_user_bind_test = D('House_village_user_bind_test');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');

        $is_true_old_user = $_GET['$is_true_old_user'] + 0;

        if (empty($is_true_old_user)) {
            $old_condition['status'] = 1;
            $old_condition['parent_id'] = 0;
            $old_condition['type'] = 0;
            $old_condition['vacancy_id'] = 0;
            $old_condition['layer_num'] = array('neq', "");
            $old_condition['room_address'] = array('neq', "");
            $is_true_old_user = $database_house_village_user_bind->where($old_condition)->count();
        }
        //$is_true_old_user = $_GET['is_true_old_user'] + 0;
        $limit = $_GET['limit'] + 0;
        if (!$limit) $limit = 1;

        if (empty($is_true_old_user) || empty($this->village_id)) $this->error('参数错误！');


        $old_condition['status'] = 1;
        $old_condition['parent_id'] = 0;
        $old_condition['type'] = 0;
        $old_condition['vacancy_id'] = 0;
        $old_condition['layer_num'] = array('neq', "");
        $old_condition['room_address'] = array('neq', "");
        //$old_condition['village_id'] = $this->village_id; //需要修改成动态小区ID

        $old_data = $database_house_village_user_bind->field('pigcms_id,village_id,usernum,floor_id,layer_num,room_addrss,uid,name,phone,housesize,park_flag,add_time')->where($old_condition)->limit($limit)->select();
        if ($old_data) {
            foreach ($old_data as $k => $v) {

                $insert_id = $update_id = 0;

                $data = array();
                $data['usernum'] = $v['usernum'];
                $data['floor_id'] = $v['floor_id'];
                $data['layer'] = $v['layer_num'];
                $data['room'] = $v['room_addrss'];
                $data['status'] = 3;
                $data['village_id'] = $v['village_id'];
                $data['add_time'] = $v['add_time'] ? $v['add_time'] : time();
                $data['uid'] = $v['uid'];
                $data['name'] = $v['name'];
                $data['phone'] = $v['phone'];
                $data['type'] = 0;
                $data['memo'] = $v['memo'] ? $v['memo'] : 'null';
                $data['is_del'] = 0;
                $data['del_time'] = 0;
                $data['housesize'] = $v['housesize'];
                $data['park_flag'] = $v['park_flag'];

                //生成房间信息
                $insert_id = $database_house_village_user_vacancy->data($data)->add();

                if ($insert_id) {
                    $now_data = array();
                    $now_data['vacancy_id'] = $insert_id;
                    $now_data['pass_time'] = time();
                    //更新业主绑定数据的房间ID
                    $update_id = $database_house_village_user_bind->where(array('pigcms_id' => $v['pigcms_id']))->data($now_data)->save();
                    if ($update_id) {
                        //查询业主是否绑定亲属 如果绑定 那么也更新信息
                        $relatives = array();
                        $relatives['housesize'] = $v['housesize'];
                        $relatives['park_flag'] = $v['park_flag'];
                        $relatives['address'] = $v['address'];
                        $relatives['layer_num'] = $v['layer_num'];
                        $relatives['room_address'] = $v['room_address'];
                        $relatives['floor_id'] = $v['floor_id'];
                        $relatives['type'] = $v['type'];
                        $relatives['vacancy_id'] = $v['vacancy_id'];
                        $database_house_village_user_bind->where(array('parent_id' => $v['pigcms_id']))->data($relatives)->save();

                    }
                }

            }
            $is_true_old_user = $is_true_old_user < $limit ? $is_true_old_user : $is_true_old_user - $limit;
            $this->success('剩余' . ($is_true_old_user) . '个房间正在导入，请勿关闭页面，耐心等待！', U('updata_old_village_room_info', array('is_true_old_user' => $is_true_old_user, 'limit' => 50)), 0);
        } else {
            $this->success('导入成功，正在跳转', U('import_village'));
        }
    }

    public function import_village_adds()
    {
        //房间管理-导入 权限
        if (!in_array(38, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        set_time_limit(0);
        if (IS_POST) {
            //购买的功能套餐和房间套餐的总房间数量 start 2020/8/31
            $property_id = $this->house_session['property_id'];
            $package_where['property_id'] = $property_id;
            $package_where['status'] = 1;
            $package_where['order_type'] = ['neq', 3];
            $package_where['package_end_time|package_try_end_time'] = ['gt', time()];
            $package_order = D('Package_order')->where($package_where)->order('order_id desc')->select();
            $pr_where['property_id'] = $property_id;
            $pr_where['pay_status'] = 1;
            $room_total_num = D('Package_room_parent_order')->where($pr_where)->field('sum(total_num) as total_num')->find();
            $room_num = 0;
            $order_idArr=array();
            if ($package_order) {
                foreach($package_order as $pov){
                    $order_idArr[]=$pov['order_id'];
                }
                $room_where=array();
                $room_where['package_order_id'] = array('in',$order_idArr);
                $room_where['property_id'] = $property_id;
                $room_where['status'] = 1;
                $room_num = D('Package_room_order')->where($room_where)->field('sum(room_num*num) as room_num')->find();
                $room_num = $room_num['room_num'];
                $package_order=$package_order['0'];
            } else {
                $this->error('对不起，您需要购买功能套餐');
            }
            $total_room_num = $package_order['room_num'] + $room_total_num['total_num'] + $room_num;
            //购买的功能套餐和房间套餐的总房间数量 end 2020/8/31
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');

                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
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
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);    //删除文件
                    $error_arr = array();
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        if (count($result) == 0) {
                            $this->assign('waitSecond', 10);
                            $this->error('表格未成功识别。请使用 excel软件 将表格更换为 xlsx格式 重试一下');
                        }
                        $limit_info = D('House_property')->get_room_limit_num($this->house_session['property_id']);
                        if ($limit_info['room_limit_num'] > 0) {
                            $surplus_room_num = $limit_info['surplus_room_num'];
                            $import_room_num = $surplus_room_num;
                        } else {
                            $surplus_room_num = -1;
                        }
                        //2020/8/31 判断房间导入的房间数量不得大于购买的房间数量 start
                        $map_vacancy['village_id'] = $this->village_id;
                        $map_vacancy['is_del'] = 0;
                        $map_vacancy['status'] = ['in', [1, 0, 3]];
                        $exist_room_count = D('House_village_user_vacancy')->where($map_vacancy)->count();
                        $surplus_count = $total_room_num - $exist_room_count;//剩余房间数量
                        if ($surplus_count <= 0) {
                            $this->error('对不起，您已经超出房间导入总数量，您可以去购买房间套餐');
                        }
                        //2020/8/31 判断房间导入的房间数量不得大于购买的房间数量 end
                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            //功能套餐 更改 2020/9/1 start
//                            if ($surplus_room_num!=-1 && $surplus_room_num<=0) {
//                                $vv['H'] = "成功导入{$import_room_num}房间数据,剩下的房间可联系物业增加可导入房间数量后继续导入！";
//                                $error_arr[] = $vv;
//                                break;
//                            }
                            if ($kk - 1 > $surplus_count) {
                                $vv['H'] = "成功导入{$surplus_count}房间数据,剩下的房间可通过购买房间套餐或者升级功能套餐后继续导入！";
                                $error_arr[] = $vv;
                                break;
                            }
                            //功能套餐 更改 2020/9/1 end
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null) continue;

                            if (empty($vv['A'])) {
                                $vv['H'] = '请填写物业编号！';
                                $error_arr[] = $vv;
                                // $err_msg .= '请填写物业编号！'.PHP_EOL;
                                continue;
                            }
                            if (empty($vv['B'])) {
                                $vv['H'] = '请填写楼栋名称！';
                                $error_arr[] = $vv;
                                // $err_msg .= '请填写单元号！'.PHP_EOL;
                                continue;
                            }

                            if (empty($vv['C'])) {
                                $vv['H'] = '请填写单元名称！';
                                $error_arr[] = $vv;
                                // $err_msg .= '请填写单元号！'.PHP_EOL;
                                continue;
                            }


                            if (empty($vv['D'])) {
                                $vv['H'] = '请填写楼层号！';
                                $error_arr[] = $vv;
                                // $err_msg .= '请填写层号！'.PHP_EOL;
                                continue;
                            }

                            if (empty($vv['E'])) {
                                $vv['H'] = '请填写房号！';
                                $error_arr[] = $vv;
                                // $err_msg .= '请填写房号！'.PHP_EOL;
                                continue;
                            }

                            if (empty($vv['F'])) {
                                $vv['H'] = '请填写房间面积！';
                                $error_arr[] = $vv;
                                // $err_msg .= '请填写房间面积！'.PHP_EOL;
                                continue;
                            }
                            $single = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            //处理楼号
                            $single_data['single_name'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            $single_data['village_id'] = $this->village_id;
                            $single_data['status'] = 1;
                            $single = M('House_village_single')->where($single_data)->find();
                            if (!$single) {
                                $vv['H'] = '楼栋不存在！';
                                $error_arr[] = $vv;
                                continue;
                            }

                            //$floor_name =  htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            //$floor_layer = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $where['floor_name'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
//                            $where['floor_layer'] = $floor_layer;
                            $where['status'] = 1;
                            $where['village_id'] = $this->village_id;
                            $where['single_id'] = $single['id'];
                            $database_house_village_floor = D('House_village_floor');
                            $house_village_floor_info = $database_house_village_floor->where($where)->find();
                            if (!$house_village_floor_info) {
                                $vv['H'] = '单元不存在，请查看社区中心，单元管理-单元列表！';
                                $error_arr[] = $vv;
                                $err_msg .= '单元不存在，请查看社区中心，单元管理-单元列表！(ERROR_' . ($kk + 1) . ')' . PHP_EOL;
                                continue;
                            }

                            $tmpdata = array();
                            $tmpdata['usernum'] = $this->village_id . '-' . htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            //检测用户是否已存在
                            if (D('House_village_user_vacancy')->field('`usernum`')->where(array('usernum' => $tmpdata['usernum']))->find()) {
                                $vv['H'] = '物业编号已存在。';
                                $error_arr[] = $vv;
                                $err_msg .= '物业编号已存在。' . PHP_EOL;
                                continue;
                            }

                            $tmpdata['layer'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            $tmpdata['room'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            $tmpdata['floor_id'] = $house_village_floor_info['floor_id'];
                            //检测房间号是否重复
                            if (D('House_village_user_vacancy')->field('`room`')->where(array('floor_id' => $tmpdata['floor_id'], 'layer' => $tmpdata['layer'], 'room' => $tmpdata['room']))->find()) {
                                $vv['H'] = '房间号已存在。';
                                $error_arr[] = $vv;
                                $err_msg .= '房间号已存在。' . PHP_EOL;
                                continue;
                            }

                            $house_village_layer = M('House_village_layer')->where(array('layer_name' => $vv['D'], 'village_id' => $this->village_id, 'floor_id' => $house_village_floor_info['floor_id']))->find();
                            if (empty($house_village_layer)) {
                                $vv['H'] = '楼层名称不存在。';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $tmpdata['layer_id'] = $house_village_layer['id'];
                            if (D('House_village_user_bind')->field('`usernum`')->where(array('usernum' => $tmpdata['usernum']))->find()) {
                                $vv['H'] = '业主已存在。';
                                $error_arr[] = $vv;
                                $err_msg .= '业主已存在。' . PHP_EOL;
                                continue;
                            }
                            if (trim($vv['G']) == '住宅') {
                                $tmpdata['house_type'] = 1;
                            } elseif (trim($vv['G']) == '商铺') {
                                $tmpdata['house_type'] = 2;
                            } elseif (trim($vv['G']) == '办公') {
                                $tmpdata['house_type'] = 3;
                            }
                            $tmpdata['housesize'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                            $tmpdata['village_id'] = $this->village_id;
                            $tmpdata['status'] = 1;
                            $tmpdata['single_id'] = $single['id'];
                            $tmpdata['add_time'] = time();
                            $last_user_id = D('House_village_user_vacancy')->data($tmpdata)->add();
                            if (!$last_user_id) {
                                $vv['H'] = '保存失败。';
                                $error_arr[] = $vv;
                                $err_msg .= '业主编号为' . $vv['A'] . ' 导入失败！' . PHP_EOL;
                            }
                            $arr = array();
                            $arr['param'] = serialize(array('vacancy_id' =>$last_user_id,'type'=>'house'));
                            $arr['plan_time'] = time()+60;
                            $arr['space_time'] = 0;
                            $arr['add_time'] = time();
                            $arr['file'] = 'sub_house_village_third';
                            $arr['time_type'] = 1;
                            $arr['unique_id'] = 'sub_house_village_third_house_'.$last_user_id;
                            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                            M('process_sub_plan')->data($arr)->add();
                            // 导入成功数量减一
                            if ($surplus_room_num > 0) {
                                $surplus_room_num--;
                            }
                        }
                        if (empty($error_arr)) {
                            $this->success('导入成功');
                            exit;
                        } else {
                            $num = count($error_arr);
                            echo '<javascript>alert("失败' . $num . '条，点击下载)";</javascript>';
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);
                            date_default_timezone_set('Europe/London');
                            $objExcel = new PHPExcel();

                            $title = $this->village['village_name'] . '社区-房间导入失败列表';
                            /*以下是一些设置 ，什么作者  标题之类的*/
                            $objExcel->getProperties()->setCreator($title)
                                ->setLastModifiedBy($title)
                                ->setTitle($title)
                                ->setSubject("失败信息导出")
                                ->setDescription("备份数据")
                                ->setKeywords("excel")
                                ->setCategory("result file");

                            $i = 0;
                            $objExcel->createSheet();
                            $objExcel->setActiveSheetIndex($i);
                            $objExcel->getActiveSheet()->setTitle('房间列表');
                            $objActSheet = $objExcel->getActiveSheet();
                            $objActSheet->setCellValue('A1', '物业编号');
                            $objActSheet->setCellValue('B1', '所属楼栋（请与后台添加的楼栋名称保持一致）');
                            $objActSheet->setCellValue('C1', '所属单元（请与后台添加的单元名称保持一致）');
                            $objActSheet->setCellValue('D1', '楼层名称（请与后台添加的楼层名称保持一致）');
                            $objActSheet->setCellValue('E1', '房号');
                            $objActSheet->setCellValue('F1', '房子平方(必填/计算物业费使用');
                            $objActSheet->setCellValue('G1', '房屋使用类型(住宅/商铺/办公)');
                            $objActSheet->setCellValue('H1', '失败原因');

                            $index = 2;
                            foreach ($error_arr as $value) {
                                $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                                $objActSheet->setCellValueExplicit('H' . $index, $value['H']);

                                $index++;
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
                            header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $limit_info = D('House_property')->get_room_limit_num($this->house_session['property_id']);
            $this->assign('limit_info', $limit_info);
            $this->display();
        }
    }

    //导入楼栋、单元、楼层、房间 2020/9/3  import_data
    public function import_village_add()
    {
        set_time_limit(0);
        if (IS_POST) {
            //购买的功能套餐和房间套餐的总房间数量 start 2020/8/31
            $property_id = $this->house_session['property_id'];
            $package_where=array();
            $package_where['property_id'] = $property_id;
            $package_where['status'] = 1;
            $package_where['order_type'] = ['neq', 3];
            $package_where['package_end_time|package_try_end_time'] = ['gt', time()];
            $package_order = D('Package_order')->where($package_where)->order('order_id desc')->select();
            $order_idArr=array();
            if (empty($package_order)){
                $package_order = '';
                $map['property_id'] = $property_id;
                $map['status'] = 0;
                $map['package_try_end_time'] = ['gt', time()];
                $package_order = D('Package_order')->where($map)->order('order_id DESC')->find();
                $order_idArr[]=$package_order['order_id'];
            }else{
                foreach($package_order as $pov){
                    $order_idArr[]=$pov['order_id'];
                }
                $package_order=$package_order['0'];
            }
            $pr_where['property_id'] = $property_id;
            $pr_where['pay_status'] = 1;
            $room_num = 0;
            if ($package_order) {
                $room_where=array();
                $room_where['package_order_id'] = array('in',$order_idArr);
                $room_where['property_id'] = $property_id;
                $room_where['status'] = 1;
                $room_num = D('Package_room_order')->where($room_where)->field('sum(room_num*num) as room_num')->find();
                $room_num = $room_num['room_num'];

            } else {
                $this->error('对不起，您需要购买功能套餐');
            }
            $export_from=isset($_GET['export_from']) ? trim($_GET['export_from']):'';
            $total_room_num = $package_order['room_num'] + $room_num;
            //购买的功能套餐和房间套餐的总房间数量 end 2020/8/31
            if ($_FILES['file']['error'] != 4) 
            {
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');
                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
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
                fdump_api(['total_room_num' => $total_room_num], 'room/uploadLog');
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);  //删除文件
                    $error_arr = [];
                    $floor_temporary=[];
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        unset($result[2]);
                        if (count($result) == 0) {
                            $this->assign('waitSecond', 10);
                            $this->error('表格未成功识别。请使用 excel软件 将表格更换为 xlsx格式 重试一下');
                        }
                        $single_arr = [];
                        $floor_arr = [];
                        // 字符串替换去除数字以外的
                        $pattern_number = "/[^0-9]/";
                        // 正则检查是否为纯数字
                        $check_number = "/^[0-9]+$/";
                        // 正则检查是否为字母或数字
                        $pattern = '/^[a-zA-Z0-9]+$/u';
                        //2020/8/31 判断房间导入的房间数量不得大于购买的房间数量 start
                        $map_vacancy['village_id'] = $this->village_id;
                        $map_vacancy['is_del'] = 0;
                        $map_vacancy['status'] = ['in', [1, 0, 3]];
                        $exist_room_count = D('House_village_user_vacancy')->where($map_vacancy)->count();
                        $surplus_count = $total_room_num - $exist_room_count;//剩余房间数量
                        $total_count = $surplus_count;
                        if ($surplus_count <= 0) {
                            $this->error('对不起，您已经超出房间导入总数量，您可以去购买房间套餐');
                        }
                        $database_house_village_config = D('House_village_config');
                        $village_config = $database_house_village_config->where(array('village_id'=>$this->village_id))->find();
                        $village_single_support_digit = intval($village_config['village_single_support_digit']) ? intval($village_config['village_single_support_digit']) : 2;
                        if (!$village_single_support_digit || $village_single_support_digit!=3) {
                            $village_single_support_digit = 2;
                        }
                        $max_num = 99;
                        if ($village_single_support_digit==3) {
                            $max_num = 999;
                        }
                        //2020/8/31 判断房间导入的房间数量不得大于购买的房间数量 end
                        // 去除非数字部分
                        $pattern_number = "/[^0-9]/";
                        $db_house_village_single = M('House_village_single');
                        $db_house_village_floor = M('House_village_floor');
                        $db_house_village_layer = M('House_village_layer');
                        $db_house_village_user_vacancy = D('House_village_user_vacancy');
                        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
                        if($is_new_charge==1){
                            $contract_time_start=1;
                            $contract_time_end=1;
                            //小区的合同时间
                            $village_time=D('House_village_info')->field('contract_time_start,contract_time_end')->where(array('village_id' => $this->village_id))->find();
                            if (!empty($village_time)&&$village_time['contract_time_start']>1){
                                $contract_time_start=$village_time['contract_time_start'];
                                $contract_time_end=$village_time['contract_time_end'];
                            }
                            foreach ($result as $kk => $vv) {
                                if (array_sum($vv) == 0) {
                                    continue;
                                }
                                if ($contract_time_end<=1||$contract_time_start<=1){
                                    $vv['P'] = "小区合同时间未设置，请先到小区基本信息中设置合同起始时间";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                //套餐 start
                                if ($surplus_count <= 0) {
                                    $vv['P'] = "成功导入{$total_count}房间数据,剩下的房间可通过购买房间套餐或者升级功能套餐后继续导入！";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                //套餐 end
                                if ($vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null) continue;
                                $single_name = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                                $single_number = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                                if (empty($single_number) && empty($single_name)) {
                                    $vv['P'] = '请填写楼栋名称和楼栋编号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($single_number < 0 || $single_number > $max_num) {
                                    $vv['P'] = "楼栋编号不符合规则，请填写其他1-{$max_num}的楼栋编号";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $floor_name = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                                $floor_number = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                                if (empty($floor_name) && empty($floor_number)) {
                                    $vv['P'] = '请填写单元名称和单元编号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($floor_number < 0 || $floor_number > 99) {
                                    $vv['P'] = "单元编号不符合规则，请填写其他1-99的单元编号";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $layer_name = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                                $layer_number = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                                if (empty($layer_name) && empty($layer_number)) {
                                    $vv['P'] = '请填写楼层和楼层编号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($layer_number < 0 || $layer_number > 99) {
                                    $vv['P'] = "楼层编号不符合规则，请填写其他1-99的楼层编号";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $room_name = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                                $room_number = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                                if (empty($room_name) && empty($room_number)) {
                                    $vv['P'] = '请填写房间号和房间编号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($room_number < 0 || $room_number > 9999) {
                                    $vv['P'] = "房间编号不符合规则，请填写其他1-9999的房间编号";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if (!$room_name && $room_number) {
                                    $room_name = $room_number;
                                }
                                if (empty($vv['K'])) {
                                    $vv['P'] = '请填写房间使用类型！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if(empty($vv['L'])&&!empty($vv['M'])){
                                    $vv['P'] = '请填写物业开始时间！';
                                    $error_arr[] = $vv;
                                    continue;
                                }else if(!empty($vv['L'])&&empty($vv['M'])){
                                    $vv['P'] = '请填写物业结束时间！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $room_alias_id=htmlspecialchars(trim($vv['N']), ENT_QUOTES);
                                /*
                                if (!empty($room_alias_id) && !is_numeric($room_alias_id)){
                                    $vv['P'] = '房间别名编号必须填写数字！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                */
                                $sort=trim($vv['O']); //排序
                                $sort=!empty($sort) ? intval($sort):0;
                                $sort=$sort>0 ?$sort:0;
                                if ($single_number && strlen($single_number) < $village_single_support_digit) {
                                    // 不足位 补足对应位
                                    $single_number = str_pad($single_number, $village_single_support_digit, "0", STR_PAD_LEFT);
                                }
                                if ($floor_number && strlen($floor_number) < 2) {
                                    // 不足2位 补足2位
                                    $floor_number = str_pad($floor_number, 2, "0", STR_PAD_LEFT);
                                }
                                if ($layer_number && strlen($layer_number) < 2) {
                                    // 不足2位 补足2位
                                    $layer_number = str_pad($layer_number, 2, "0", STR_PAD_LEFT);
                                }
                                if ($room_number && strlen($room_number) < 2) {
                                    // 不足2位 补足2位
                                    $room_number = str_pad($room_number, 2, "0", STR_PAD_LEFT);
                                }
                                fdump_api([
                                    'vv' => $vv,'single_number' => $single_number,'floor_number' => $floor_number,
                                    'layer_number' => $layer_number,'room_number' => $room_number
                                ], 'room/uploadLog',1);
                                $single_data = [];
                                $single_data['village_id'] = $this->village_id;
                                $single_data['status'] = 1;
                                $where_single = $single_data;
                                if ($single_number) {
                                    $single_data['single_number'] = $single_number;
                                    $single_number_int = intval($single_number);
                                    $where_single['_string'] = "(single_number={$single_number} OR single_number={$single_number_int})";
                                } elseif (!empty($single_name)) {
                                    $single_data['single_name'] = $single_name;
                                    $where_single['single_name'] = $single_name;
                                }
                                $is_public_rental=0;
                                $single = $db_house_village_single->where($where_single)->find();
                                fdump_api([
                                    'where_single' => $where_single,'single' => $single
                                ], 'room/uploadLog',1);
                                if(isset($single['is_public_rental'])){
                                    $is_public_rental=$single['is_public_rental'];
                                }
                                $single_id = $single['id'] ? $single['id'] : 0;
                                if ($single) {
                                    $single_arr[] = [
                                        'id' => $single_id,
                                        'update_time' => $single['update_time']
                                    ];
                                }
                                $singe_set = [];
                                if (!$single) {
                                    // 如果楼栋不存在则必须填写对应楼栋编号
                                    if (!$single_number) {
                                        $vv['P'] = '导入房屋时候自动创建楼栋的时候楼栋编号必填';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'msg' => "导入房屋时候自动创建楼栋的时候楼栋编号必填"
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }

                                    if (!preg_match($check_number, $single_number)) {
                                        $vv['P'] = '导入房屋时候自动创建创建楼栋的时候楼栋编号只允许数字';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'single_number' => $single_number,
                                            'msg' => "导入房屋时候自动创建创建楼栋的时候楼栋编号只允许数字"
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    // 楼栋编号 暂时同一个小区下仅限1-99或1-999数字编号
                                    if ($single_number) {
                                        $single_data['single_name'] = $single_name ? $single_name : $single_number;
                                        $single_data['single_number'] = $single_number;
                                        // 去除重复
                                        $where_repeat = [
                                            'village_id' => $this->village_id,
                                            'single_number' => $single_number,
                                            'status' => ['neq', 4]
                                        ];
                                        $repeat_id = $db_house_village_single->where($where_repeat)->getField('id');
                                        if ($repeat_id) {
                                            fdump_api([
                                                'where_repeat' => $where_repeat,'repeat_id' => $repeat_id,'single_number' => $single_number,
                                                'msg' => "该楼栋编号【{$single_number}】已经存在，请填写其他1-{$max_num}的楼栋编号！"
                                            ], 'room/uploadLog',1);
                                            $vv['P'] = "该楼栋编号【{$single_number}】已经存在，请填写其他1-{$max_num}的楼栋编号！";
                                            $error_arr[] = $vv;
                                            continue;
                                        }
                                    }
                                    if($export_from=='unitRental'){
                                        $is_public_rental=1;
                                        $single_data['is_public_rental']=$is_public_rental;
                                    }
                                    $single_id = $db_house_village_single->data($single_data)->add();
                                    if (!$single_id) {
                                        $vv['P'] = '对应楼栋添加失败！';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'single_data' => $single_data,
                                            'msg' => "对应楼栋添加失败"
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    $single_arr[] = [
                                        'id' => $single_id,
                                        'update_time' => 0
                                    ];
                                } elseif (!$single['single_number']) {
                                    // 楼栋编号 暂时同一个小区下仅限1-99或1-999数字编号
                                    if ($single_number) {
                                        $singe_set = [
                                            'single_number' => $single_number
                                        ];

                                    }
                                }
                                if ($singe_set && $single_id) {
                                    if ($singe_set['single_number']) {
                                        // 去除重复
                                        $where_repeat = [
                                            'village_id' => $this->village_id,
                                            'single_number' => $singe_set['single_number'],
                                            'id' => ['neq', $single_id],
                                            'status' => ['neq', 4]
                                        ];
                                        $repeat_id = $db_house_village_single->where($where_repeat)->getField('id');
                                        if ($repeat_id) {
                                            fdump_api([
                                                'where_repeat' => $where_repeat,'repeat_id' => $repeat_id,'singe_set' => $singe_set,
                                                'msg' =>  "该楼栋【ID:".$single_id."】的编号【{$singe_set['single_number']}】已经存在，请填写其他1-99的楼栋编号。"
                                            ], 'room/uploadLog',1);
                                            $vv['P'] = "该楼栋【ID:".$single_id."】的编号【{$singe_set['single_number']}】已经存在，请填写其他1-99的楼栋编号。";
                                            $error_arr[] = $vv;
                                            continue;
                                        }
                                    }
                                    // 如果需要变动 直接对应变动
                                    $db_house_village_single->where(['id' => $single_id])->data($singe_set)->save();
                                }

                                //楼栋的合同时间
                                $single_time=D('House_village_single')->field('contract_time_start,contract_time_end')->where(array('id' => $single_id))->find();
                                if (!empty($single_time)&&$single_time['contract_time_start']>1){
                                    $contract_time_start=$single_time['contract_time_start'];
                                    $contract_time_end=$single_time['contract_time_end'];
                                }
                                $property_starttime=1;
                                $property_endtime=1;
                                if ($vv['L']) {
                                    if(preg_match('/[\x7f-\xff]/',$vv['L'])){
                                        $arr = date_parse_from_format('Y年m月d日', $vv['L']);
                                        $property_starttime= mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                                    }else{
                                        $arr= explode("-",$vv['L']);
                                        $property_starttime = mktime(0,0,0,$arr[1],$arr[2],$arr[0]);
                                    }
                                }
                                if ($vv['M']) {
                                    if(preg_match('/[\x7f-\xff]/',$vv['M'])){
                                        $arr = date_parse_from_format('Y年m月d日', $vv['M']);
                                        $property_endtime= mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                                    }else{
                                        $arr= explode("-",$vv['M']);
                                        $property_endtime = mktime(0,0,0,$arr[1],$arr[2],$arr[0]);
                                    }
                                }
                                if ($property_starttime>1&&$property_endtime>1){
                                    if ($property_starttime>=$property_endtime){
                                        $vv['P'] = '物业服务结束时间不能小于物业服务开始时间！';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'property_starttime' => $property_starttime,'property_endtime' => $property_endtime,'single_time' => $single_time,
                                            'msg' =>  "物业服务结束时间不能小于物业服务开始时间"
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    if ($contract_time_start>1&&$contract_time_end>1){
                                        if ($property_starttime<$contract_time_start||$property_endtime>$contract_time_end){
                                            $vv['P'] = '物业服务起止时间不能超出【'.date('Y-m-d',$contract_time_start).'至'.date('Y-m-d',$contract_time_end).'】的合同时间范围！';
                                            $error_arr[] = $vv;
                                            fdump_api([
                                                'property_starttime' => $property_starttime,'contract_time_start' => $contract_time_start,
                                                'property_endtime' => $property_endtime,'contract_time_end' => $contract_time_end,
                                                'msg' =>  $vv['P']
                                            ], 'room/uploadLog',1);
                                            continue;
                                        }
                                    }

                                }

                                //单元
                                $floor_data = [];
                                $floor_data['status'] = 1;
                                $floor_data['village_id'] = $this->village_id;
                                $floor_data['single_id'] = $single_id;
                                $where_floor = $floor_data;
                                if ($floor_number) {
                                    $floor_data['floor_number'] = $floor_number;
                                    $floor_number_int = intval($floor_number);
                                    $where_floor['_string'] = "(floor_number={$floor_number} OR floor_number={$floor_number_int})";
                                } elseif (!empty($floor_name)) {
                                    $floor_data['floor_name'] = (string)$floor_name;
                                    $where_floor['floor_name'] = (string)$floor_name;
                                }
                                //todo 过滤重复写入单元数据
                                $floor_str=md5($this->village_id.$single_id.$floor_number);
                                if(isset($floor_temporary[$floor_str]) && !empty($floor_temporary[$floor_str])){
                                    $house_village_floor_info=$floor_temporary[$floor_str];
                                }else{
                                    $house_village_floor_info = $db_house_village_floor->where($where_floor)->find();
                                }
                                fdump_api([
                                    'where_floor' => $where_floor,'house_village_floor_info' => $house_village_floor_info,
                                ], 'room/uploadLog',1);

                                $floor_id = $house_village_floor_info['floor_id'] ? $house_village_floor_info['floor_id'] : 0;
                                $floor_arr = [];
                                if ($house_village_floor_info) {
                                    $floor_arr[] = [
                                        'floor_id' => $floor_id,
                                        'update_time' => $house_village_floor_info['update_time']
                                    ];
                                    $floor_temporary[$floor_str]=$house_village_floor_info;
                                }
                                $floor_set = [];
                                if (!$house_village_floor_info) {
                                    // 如果单元不存在则必须填写对应单元编号
                                    if (!$floor_number) {
                                        $vv['P'] = '导入房屋时候自动创建单元的时候单元编号必填';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'msg' => '导入房屋时候自动创建单元的时候单元编号必填'
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    if (!preg_match($check_number, $floor_number)) {
                                        $vv['P'] = '导入房屋时候自动创建单元的时候单元编号只允许数字';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'floor_number' => $floor_number,
                                            'msg' => '导入房屋时候自动创建单元的时候单元编号只允许数字'
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    $floor_data['add_time'] = time();
                                    $floor_data['floor_name'] = $floor_name ? $floor_name : $floor_number;
                                    $floor_data['floor_number'] = $floor_number;
                                    // 去除重复
                                    $where_repeat = [
                                        'village_id' => $this->village_id,
                                        'single_id' => $single_id,
                                        'floor_number' => $floor_number,
                                        'status' => ['neq', 4]
                                    ];
                                    $repeat_id = $db_house_village_floor->where($where_repeat)->getField('floor_id');
                                    if ($repeat_id) {
                                        $vv['P'] = "该单元编号【{$floor_number}】已经存在，请填写其他1-99的单元编号！";
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'where_repeat' => $where_repeat,'repeat_id' => $repeat_id,'floor_number' => $floor_number,
                                            'msg' => $vv['P']
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    if($is_public_rental>0){
                                        $floor_data['is_public_rental']=1;
                                    }
                                    $floor_id = $db_house_village_floor->data($floor_data)->add();
                                    if (!$floor_id) {
                                        $vv['P'] = '对应单元添加失败！';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'floor_data' => $floor_data,
                                            'msg' => $vv['P']
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    $floor_data22=[];
                                    $floor_data22=$floor_data;
                                    $floor_data22['floor_id']=$floor_id;
                                    $floor_temporary[$floor_str]=$floor_data22;
                                    $floor_arr[] = [
                                        'floor_id' => $floor_id,
                                        'update_time' => 0
                                    ];
                                } elseif (!$house_village_floor_info['floor_number']) {
                                    // 单元编号 暂时同一个楼栋下仅限1-99数字编号
                                    if ($floor_number) {
                                        $floor_set = [
                                            'floor_number' => $floor_number
                                        ];
                                    }
                                }

                                if ($floor_set && $floor_id) {
                                    if ($floor_set['floor_number']) {
                                        // 去除重复
                                        $where_repeat = [
                                            'village_id' => $this->village_id,
                                            'single_id' => $single_id,
                                            'floor_number' => $floor_set['floor_number'],
                                            'floor_id' => ['neq', $floor_id],
                                            'status' => ['neq', 4]
                                        ];
                                        $repeat_id = $db_house_village_floor->where($where_repeat)->getField('floor_id');
                                        if ($repeat_id) {
                                            $vv['P'] = "该单元编号【{$floor_set['floor_number']}】已经存在，请填写其他1-99的单元编号！";
                                            $error_arr[] = $vv;
                                            fdump_api([
                                                'where_repeat' => $where_repeat,'repeat_id' => $repeat_id,'floor_set' => $floor_set,
                                                'msg' => $vv['P']
                                            ], 'room/uploadLog',1);
                                            continue;
                                        }
                                    }
                                    // 如果需要变动 直接对应变动
                                    $db_house_village_floor->where(['floor_id' => $floor_id])->data($floor_set)->save();
                                }

                                //楼层
                                $layer_data = [];
                                $layer_data['village_id'] = $this->village_id;
                                $layer_data['floor_id'] = $floor_id;
                                $layer_data['single_id'] = $single_id;
                                $layer_data['status'] = 1;
                                $where_layer = [];
                                if ($layer_number) {
                                    $layer_data['layer_number'] = $floor_number;
                                    $layer_number_int = intval($layer_number);
                                    $where_layer['_string'] = "(layer_number={$layer_number} OR layer_number={$layer_number_int})";
                                } elseif (!empty($layer_name)) {
                                    $layer_data['layer_name'] = $layer_name;
                                    $where_layer['layer_name'] = $layer_name;
                                }
                                $where_layer['status'] = 1;
                                $where_layer['village_id'] = $this->village_id;
                                $where_layer['single_id'] = $single_id;
                                $where_layer['floor_id'] = $floor_id;
                                $house_village_layer = $db_house_village_layer->where($where_layer)->find();
                                $layer_id = $house_village_layer['id'] ? $house_village_layer['id'] : 0;
                                $layer_set = [];
                                if (empty($house_village_layer)) {
                                    // 如果楼层不存在则必须填写对应楼层编号
                                    if (!$layer_number) {
                                        $vv['P'] = '导入房屋时候自动创建楼层的时候楼层编号必填';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'msg' => $vv['P']
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    if (!preg_match($check_number, $layer_number)) {
                                        $vv['P'] = '导入房屋时候自动创建楼层的时候楼层编号只允许数字';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'layer_number' => $layer_number,
                                            'msg' => $vv['P']
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    $layer_data['layer_number'] = $layer_number;
                                    $layer_data['layer_name'] = $layer_name ? $layer_name : $layer_number;
                                    // 去除重复
                                    $where_repeat = [
                                        'village_id' => $this->village_id,
                                        'single_id' => $single_id,
                                        'floor_id' => $floor_id,
                                        'layer_number' => $layer_number,
                                        'status' => ['neq', 4]
                                    ];
                                    $repeat_id = $db_house_village_layer->where($where_repeat)->getField('id');
                                    if ($repeat_id) {
                                        $vv['P'] = "该楼层编号【{$layer_number}】已经存在，请填写其他1-99的楼层编号！";
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'where_repeat' => $where_repeat,'repeat_id' => $repeat_id,'layer_number' => $layer_number,
                                            'msg' => $vv['P']
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                    if($is_public_rental>0){
                                        $layer_data['is_public_rental']=1;
                                    }
                                    $layer_id = $db_house_village_layer->data($layer_data)->add();
                                    if (!$layer_id) {
                                        $vv['P'] = '对应添楼层加失败！';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'layer_data' => $layer_data,
                                            'msg' => $vv['P']
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                } elseif (!$house_village_layer['layer_number']) {
                                    // 楼层编号 暂时同一个单元下仅限1-99数字编号
                                    if ($layer_number) {
                                        $layer_set = [
                                            'layer_number' => $layer_number
                                        ];
                                    }
                                }
                                if ($layer_set && $layer_id) {
                                    if ($layer_set['layer_number']) {
                                        // 去除重复
                                        $where_repeat = [
                                            'village_id' => $this->village_id,
                                            'single_id' => $single_id,
                                            'floor_id' => $floor_id,
                                            'layer_number' => $layer_set['layer_number'],
                                            'id' => ['neq', $layer_id],
                                            'status' => ['neq', 4]
                                        ];
                                        $repeat_id = $db_house_village_layer->where($where_repeat)->getField('id');
                                        if ($repeat_id) {
                                            $vv['P'] = "该楼层编号【{$layer_set['layer_number']}】已经存在，请填写其他1-99的楼层编号！";
                                            $error_arr[] = $vv;
                                            fdump_api([
                                                'where_repeat' => $where_repeat,'repeat_id' => $repeat_id,'layer_set' => $layer_set,
                                                'msg' => $vv['P']
                                            ], 'room/uploadLog',1);
                                            continue;
                                        }
                                    }
                                    // 如果需要变动 直接对应变动
                                    $db_house_village_layer->where(['id' => $layer_id])->data($layer_set)->save();
                                }

                                //房间
                                if ($layer_id) {
                                    $vacancy_data = [];
                                    $vacancy_data['single_id'] = $single_id;
                                    $vacancy_data['floor_id'] = $floor_id;
                                    $vacancy_data['layer_id'] = $layer_id;
                                    $vacancy_data['village_id'] = $this->village_id;
                                    $vacancy_data['is_del'] = 0;//未删除的
                                    if(!empty($room_alias_id)){
                                        $room_alias_where=array('village_id'=>$this->village_id,'is_del'=>0,'room_alias_id'=>$room_alias_id);
                                        $village_user_vacancy_tmp=$db_house_village_user_vacancy->get_one_village_user_vacancy($room_alias_where,'pigcms_id,room_alias_id,name,phone');
                                        if(!empty($village_user_vacancy_tmp)){
                                            $vv['P'] = '该房间别名编号【'.$room_alias_id.'】已经存在，请确保唯一性！';
                                            $error_arr[] = $vv;
                                            fdump_api([
                                                'room_alias_where' => $room_alias_where,'village_user_vacancy_tmp' => $village_user_vacancy_tmp,'room_alias_id' => $room_alias_id,
                                                'msg' => $vv['P']
                                            ], 'room/uploadLog',1);
                                            continue;
                                        }
                                    }
                                    $where_vacancy = $vacancy_data;
                                    if ($room_number) {
                                        $room_number_int = intval($room_number);
                                    }
                                    if ($room_number) {
                                        $vacancy_data['room_number'] = $room_number;
                                        $where_vacancy['_string'] = "(room_number={$room_number} OR room_number=$room_number_int})";
                                    } elseif (!empty($room_name)) {
                                        $where_vacancy['room'] = $room_name;
                                    }
                                    $vacancy_data['room'] = $room_name;
                                    $house_village_user_vacancy = $db_house_village_user_vacancy->where($where_vacancy)->find();
                                    if (empty($house_village_user_vacancy)) {
                                        //住房面积
                                        if (empty($vv['J'])) {
                                            $vv['J'] = 0;
                                        }
                                        $vacancy_data['housesize'] = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                                        //使用类型 1住宅 2商铺 3办公
                                        if (trim($vv['K']) == '住宅') {
                                            $vacancy_data['house_type'] = 1;
                                        } elseif (trim($vv['K']) == '商铺') {
                                            $vacancy_data['house_type'] = 2;
                                        } elseif (trim($vv['K']) == '办公') {
                                            $vacancy_data['house_type'] = 3;
                                        }
                                        $vacancy_data['add_time'] = time();
                                        $vacancy_data['status'] = 1;
                                        if ($vv['A'] === null) {
                                            //物业编号非必填，没有填的默认生成，按照小区ID+楼栋ID+单元ID+楼层ID+房号ID生成
                                            $vacancy_data['usernum'] = $this->village_id . '-' . $single_id . '-' . $floor_id . '-' . $layer_id;
                                        } else {
                                            $vacancy_data['usernum'] = trim($vv['A']);
                                        }
                                        $vacancy_data['layer'] = $layer_name;
                                        if (!$room_number) {
                                            $room_number = preg_replace($pattern_number, '', $vacancy_data['room']);
                                            // 房屋编号 同一个楼层下仅限1-9999 此处处理为4位 截取后4位
                                            if ($room_number) {
                                                if (strlen($room_number) > 4) {
                                                    // 超过4位 截取后4位
                                                    $room_number = substr($room_number, -4, 4);
                                                } elseif (strlen($room_number) < 2) {
                                                    // 不足2位 补足2位
                                                    $room_number = str_pad($room_number, 2, "0", STR_PAD_LEFT);
                                                } elseif (strlen($room_number) > 2 && strlen($room_number) < 4) {
                                                    // 大于2位不足4位 补足4位
                                                    $room_number = str_pad($room_number, 4, "0", STR_PAD_LEFT);
                                                }
                                                $vacancy_data['room_number'] = $room_number;
                                            }
                                        } else {
                                            if ($room_number_int<100 && $layer_number) {
                                                $room_number = $layer_number . $room_number;
                                            }
                                            if (strlen($room_number) > 2 && strlen($room_number) < 4) {
                                                // 大于2位不足4位 补足4位
                                                $room_number = str_pad($room_number, 4, "0", STR_PAD_LEFT);
                                            }
                                            $vacancy_data['room_number'] = $room_number;
                                        }
                                        if ($single_number && $floor_number && $layer_number && $room_number) {
                                            if (strlen($room_number) == 2) {
                                                $property_number = $single_number . $floor_number . $layer_number . $layer_number . $room_number;
                                            } elseif (strlen($room_number) == 4) {
                                                $property_number = $single_number . $floor_number . $layer_number . $room_number;
                                            } elseif (strlen($room_number) > 4) {
                                                // 超过4位 截取后4位
                                                $room_number = substr($room_number, -4, 4);
                                                $vacancy_data['room_number'] = $room_number;
                                                $property_number = $single_number . $floor_number . $layer_number . $room_number;
                                            }
                                            $vacancy_data['property_number'] = $property_number;
                                        }
                                        if (isset($vacancy_data['room_number'])) {
                                            $where_repeat = [
                                                'village_id' => $this->village_id,
                                                'single_id' => $single_id,
                                                'floor_id' => $floor_id,
                                                'layer_id' => $layer_id,
                                                'room_number' => $vacancy_data['room_number'],
                                                'status' => ['neq', 4],
                                                'is_del' => 0
                                            ];
                                            $repeat_id = $db_house_village_user_vacancy->where($where_repeat)->getField('pigcms_id');
                                            if ($repeat_id) {
                                                $vv['P'] = '该房屋编号【取房号名称后4位】已经存在，请填写其他1-9999的房号名称！';
                                                $error_arr[] = $vv;
                                                fdump_api([
                                                    'where_repeat' => $where_repeat,'repeat_id' => $repeat_id,
                                                    'msg' => $vv['P']
                                                ], 'room/uploadLog',1);
                                                continue;
                                            }
                                        }
                                        if($property_endtime>100){
                                            $property_endtime +=86399;
                                        }
                                        $vacancy_data['property_endtime']=$property_endtime;
                                        $vacancy_data['property_starttime']=$property_starttime;

                                        if($is_public_rental>0){
                                            $vacancy_data['is_public_rental']=1;
                                        }

                                        $vacancy_data['room_alias_id']=$room_alias_id;
                                        $vacancy_data['sort']=$sort;
                                        $room_id = $db_house_village_user_vacancy->data($vacancy_data)->add();
                                        if($room_id){
                                            if ($property_starttime>1&&$property_endtime>1){
                                                //todo order_log表需写入物业服务时间
                                                $arr = [];
                                                $arr['order_type'] = 'property';
                                                $arr['order_name'] = '物业费';
                                                $arr['room_id'] = $room_id;
                                                $arr['property_id'] = $property_id;
                                                $arr['village_id'] = $this->village_id;
                                                $arr['service_start_time'] = $property_starttime;
                                                $arr['service_end_time'] = $property_endtime;
                                                $arr['desc'] = '导入房间物业服务时间';
                                                $arr['add_time'] = time();
                                                D('House_new_order_log')->data($arr)->add();
                                            }
                                            $vacancy_usernum = $db_house_village_user_vacancy->where(['pigcms_id'=>$room_id])->getField('usernum');
                                            $vacancy_datas['usernum'] = $vacancy_usernum.'-'.$room_id;
                                            $db_house_village_user_vacancy->where(['pigcms_id'=>$room_id])->data($vacancy_datas)->save();
                                            $arr = array();
                                            $arr['param'] = serialize(array('vacancy_id' =>$room_id,'type'=>'house'));
                                            $arr['plan_time'] = time()+60;
                                            $arr['space_time'] = 0;
                                            $arr['add_time'] = time();
                                            $arr['file'] = 'sub_house_village_third';
                                            $arr['time_type'] = 1;
                                            $arr['unique_id'] = 'sub_house_village_third_house_'.$room_id;
                                            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                                            M('process_sub_plan')->data($arr)->add();
                                        }
                                    } else {
                                        $vv['P'] = '当前房屋已经存在 请确认后再进行导入！';
                                        $error_arr[] = $vv;
                                        fdump_api([
                                            'where_vacancy' => $where_vacancy,'house_village_user_vacancy' => $house_village_user_vacancy,
                                            'msg' => $vv['P']
                                        ], 'room/uploadLog',1);
                                        continue;
                                    }
                                } elseif (!$single_id) {
                                    $vv['P'] = '当前楼栋不存在或者添加失败！';
                                    $error_arr[] = $vv;
                                    fdump_api([
                                        'vv' => $vv,
                                        'msg' => $vv['P']
                                    ], 'room/uploadLog',1);
                                    continue;
                                } elseif (!$floor_id) {
                                    $vv['P'] = '当前单元不存在或者添加失败！';
                                    $error_arr[] = $vv;
                                    fdump_api([
                                        'vv' => $vv,
                                        'msg' => $vv['P']
                                    ], 'room/uploadLog',1);
                                    continue;
                                } elseif (!$layer_id) {
                                    $vv['P'] = '当前楼层不存在或者添加失败！';
                                    $error_arr[] = $vv;
                                    fdump_api([
                                        'vv' => $vv,
                                        'msg' => $vv['P']
                                    ], 'room/uploadLog',1);
                                    continue;
                                }
                                if ($surplus_count > 0) {
                                    $surplus_count--;
                                }
                            }
                            //楼栋相关数据
                            if ($single_arr) {
                                $single_arr = $this->assoc_unique($single_arr, 'id');
                                foreach ($single_arr as $key => $val) {
                                    $map['single_id'] = $val['id'];
                                    $map['village_id'] = $this->village_id;
                                    $map['is_del'] = 0;
                                    if ($val['update_time'] == 0) {
                                        //计算所有房间总面积
                                        $house_size = $db_house_village_user_vacancy->where($map)->sum('housesize');
                                        //计算所有房间
                                        $room_count = $db_house_village_user_vacancy->where($map)->count();
                                        //地上建筑层数
                                        unset($map['is_del']);
                                        $map['layer_name'] = ['gt', 0];
                                        $layer_ground_count = $db_house_village_layer->where($map)->count();
                                        //地下建筑层
                                        unset($map['layer_name']);
                                        $map['layer_name'] = ['lt', 0];
                                        $layer_underground_count = $db_house_village_layer->where($map)->count();
                                        //单元数量
                                        unset($map['layer_name']);
                                        $floor_count = $db_house_village_floor->where($map)->count();
                                        $where = [];
                                        $where['id'] = $val['id'];
                                        $single_data = [
                                            'measure_area' => $house_size,
                                            'floor_num' => $floor_count,
                                            'vacancy_num' => $room_count,
                                            'upper_layer_num' => $layer_ground_count,
                                            'lower_layer_num' => $layer_underground_count,
                                        ];
                                        $db_house_village_single->where($where)->data($single_data)->save();
                                    }
                                }
                            }
                            //单元相关数据
                            if ($floor_arr) {
                                $floor_arr = $this->assoc_unique($floor_arr, 'floor_id');
                                foreach ($floor_arr as $key => $val) {
                                    $floor_map['floor_id'] = $val['floor_id'];
                                    $floor_map['village_id'] = $this->village_id;
                                    $floor_map['is_del'] = 0;
                                    if ($val['update_time'] == 0) {
                                        //计算单元下房间总面积
                                        $house_size = $db_house_village_user_vacancy->where($floor_map)->sum('housesize');
                                        //计算单元下所有房间
                                        $room_count = $db_house_village_user_vacancy->where($floor_map)->count();
                                        //地上建筑层数
                                        unset($floor_map['is_del']);
                                        $floor_map['layer_name'] = ['gt', 0];
                                        $layer_ground_count = $db_house_village_layer->where($floor_map)->count();
                                        //最高住人层
                                        $max_layer = $db_house_village_layer->where($floor_map)->order('layer_name desc')->getField('layer_name');
                                        //地下建筑层
                                        unset($floor_map['layer_name']);
                                        $floor_map['layer_name'] = ['lt', 0];
                                        $layer_underground_count = $db_house_village_layer->where($floor_map)->count();
                                        //最低住人层
                                        $min_layer = $db_house_village_layer->where($floor_map)->order('layer_name asc')->getField('layer_name');
                                        $floor_data = [
                                            'floor_area' => $house_size,
                                            'house_num' => $room_count,
                                            'floor_upper_layer_num' => $layer_ground_count,
                                            'floor_lower_layer_num' => $layer_underground_count,
                                            'start_layer_num' => $min_layer ? $min_layer : 1,
                                            'end_layer_num' => $max_layer,
                                        ];
                                        $db_house_village_floor->where(['floor_id' => $val['floor_id']])->data($floor_data)->save();
                                    }
                                }
                            }
                            //错误信息导出表格
                            if ($error_arr && count($error_arr) > 0) {
                                $num = count($error_arr);
                                echo '<javascript>alert("失败' . $num . '条，点击下载)";</javascript>';
                                //导出失败信息
                                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                                error_reporting(E_ALL);
                                date_default_timezone_set('Europe/London');
                                $objExcel = new PHPExcel();

                                $prompt = '注意：楼栋编号/单元编号/楼层编号/房间编号必须填写阿拉伯数字（1,2,3）。若楼栋/单元/楼层不存在，系统会自动创建，无需人工进行添加/导入';
                                $title = $this->village['village_name'] . '社区-房间导入失败列表';
                                /*以下是一些设置 ，什么作者  标题之类的*/
                                $objExcel->getProperties()->setCreator($title)
                                    ->setLastModifiedBy($title)
                                    ->setTitle($title)
                                    ->setSubject("失败信息导出")
                                    ->setDescription("备份数据")
                                    ->setKeywords("excel")
                                    ->setCategory("result file");

                                $i = 0;
                                $objExcel->createSheet();
                                $objExcel->setActiveSheetIndex($i);
                                $objExcel->getActiveSheet()->setTitle('房间列表');
                                $objExcel->getActiveSheet()->mergeCells('A1:P1');
                                $objActSheet = $objExcel->getActiveSheet();

                                $objActSheet->setCellValue('A1', $prompt);
                                $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(12)->getColor()->setRGB('FF0000');
                                //$objExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(23);
                                $objExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
                                $objActSheet->getColumnDimension('A')->setWidth(20);
                                $objActSheet->setCellValue('A2', '物业编号(非必填)');
                                $objActSheet->getColumnDimension('B')->setWidth(20);
                                $objActSheet->setCellValue('B2', '楼栋');
                                $objActSheet->getColumnDimension('C')->setWidth(20);
                                $objActSheet->setCellValue('C2', '楼栋编号');
                                $objActSheet->getColumnDimension('D')->setWidth(20);
                                $objActSheet->setCellValue('D2', '单元');
                                $objActSheet->getColumnDimension('E')->setWidth(20);
                                $objActSheet->setCellValue('E2', '单元编号');
                                $objActSheet->getColumnDimension('F')->setWidth(20);
                                $objActSheet->setCellValue('F2', '楼层');
                                $objActSheet->getColumnDimension('G')->setWidth(20);
                                $objActSheet->setCellValue('G2', '楼层编号');
                                $objActSheet->getColumnDimension('H')->setWidth(20);
                                $objActSheet->setCellValue('H2', '房房间号');
                                $objActSheet->getColumnDimension('I')->setWidth(20);
                                $objActSheet->setCellValue('I2', '房间编号');
                                $objActSheet->getColumnDimension('J')->setWidth(20);
                                $objActSheet->setCellValue('J2', '面积（㎡）');
                                $objActSheet->getColumnDimension('K')->setWidth(20);
                                $objActSheet->setCellValue('K2', '使用类型(住宅/商铺/办公)');
                                $objActSheet->getColumnDimension('L')->setWidth(20);
                                $objActSheet->setCellValue('L2', '物业服务开始时间');
                                $objActSheet->getColumnDimension('M')->setWidth(20);
                                $objActSheet->setCellValue('M2', '物业服务结束时间');
                                $objActSheet->getColumnDimension('N')->setWidth(20);
                                $objActSheet->setCellValue('N2', '房间别名编号');
                                $objActSheet->getColumnDimension('O')->setWidth(20);
                                $objActSheet->setCellValue('O2', '排序');
                                $objActSheet->getColumnDimension('P')->setWidth(100);
                                $objActSheet->setCellValue('P2', '失败原因');
                                $index = 3;
                                foreach ($error_arr as $value) {
                                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                    $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                    $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                                    $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                                    $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                                    $objActSheet->setCellValueExplicit('J' . $index, $value['J']);
                                    $objActSheet->setCellValueExplicit('K' . $index, $value['K']);
                                    $objActSheet->setCellValueExplicit('L' . $index, $value['L']);
                                    $objActSheet->setCellValueExplicit('M' . $index, $value['M']);
                                    $objActSheet->setCellValueExplicit('N' . $index, $value['N']);
                                    $objActSheet->setCellValueExplicit('O' . $index, $value['O']);
                                    $objActSheet->setCellValueExplicit('P' . $index, $value['P']);
                                    $index++;
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
                                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                                header("Content-Transfer-Encoding:binary");
                                $objWriter->save('php://output');
                                exit();
                            } else {
                                $this->success('导入成功');
                                exit;
                            }
                        }
                        else{
                            foreach ($result as $kk => $vv) {
                                if (array_sum($vv) == 0) {
                                    continue;
                                }
                                //套餐 start
                                if ($surplus_count <= 0) {
                                    $vv['M'] = "成功导入{$total_count}房间数据,剩下的房间可通过购买房间套餐或者升级功能套餐后继续导入！";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                //套餐 end
                                if ($vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null) continue;
                                $single_name = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                                $single_number = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                                if (empty($single_number) && empty($single_name)) {
                                    $vv['M'] = '请填写楼栋名称和楼栋编号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($single_number < 0 || $single_number > $max_num) {
                                    $vv['M'] = "楼栋编号不符合规则，请填写其他1-{$max_num}的楼栋编号";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $floor_name = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                                $floor_number = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                                if (empty($floor_name) && empty($floor_number)) {
                                    $vv['M'] = '请填写单元名称和单元编号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($floor_number < 0 || $floor_number > 99) {
                                    $vv['M'] = "单元编号不符合规则，请填写其他1-99的单元编号";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $layer_name = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                                $layer_number = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                                if (empty($layer_name) && empty($layer_number)) {
                                    $vv['M'] = '请填写楼层和楼层编号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($layer_number < 0 || $layer_number > 99) {
                                    $vv['M'] = "楼层编号不符合规则，请填写其他1-99的楼层编号";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $room_name = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                                $room_number = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                                if (empty($room_name) && empty($room_number)) {
                                    $vv['M'] = '请填写房间号和房间编号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($room_number < 0 || $room_number > 9999) {
                                    $vv['M'] = "房间编号不符合规则，请填写其他1-9999的房间编号";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if (!$room_name && $room_number) {
                                    $room_name = $room_number;
                                }
                                if (empty($vv['K'])) {
                                    $vv['M'] = '请填写房间使用类型！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $sort=trim($vv['L']); //排序
                                $sort=!empty($sort) ? intval($sort):0;
                                $sort=$sort>0 ?$sort:0;
                                if ($single_number && strlen($single_number) < $village_single_support_digit) {
                                    // 不足位 补足对应位
                                    $single_number = str_pad($single_number, $village_single_support_digit, "0", STR_PAD_LEFT);
                                }
                                if ($floor_number && strlen($floor_number) < 2) {
                                    // 不足2位 补足2位
                                    $floor_number = str_pad($floor_number, 2, "0", STR_PAD_LEFT);
                                }
                                if ($layer_number && strlen($layer_number) < 2) {
                                    // 不足2位 补足2位
                                    $layer_number = str_pad($layer_number, 2, "0", STR_PAD_LEFT);
                                }
                                if ($room_number && strlen($room_number) < 2) {
                                    // 不足2位 补足2位
                                    $room_number = str_pad($room_number, 2, "0", STR_PAD_LEFT);
                                }
                                $single_data = [];
                                $single_data['village_id'] = $this->village_id;
                                $single_data['status'] = 1;
                                $where_single = $single_data;
                                if (!empty($single_name)) {
                                    $single_data['single_name'] = $single_name;
                                    $where_single['single_name'] = $single_name;
                                } elseif ($single_number) {
                                    $single_data['single_number'] = $single_number;
                                    $single_number_int = intval($single_number);
                                    $where_single['_string'] = "(single_number={$single_number} OR single_number={$single_number_int})";
                                }
                                $single = $db_house_village_single->where($where_single)->find();
                                $single_id = $single['id'] ? $single['id'] : 0;
                                if ($single) {
                                    $single_arr[] = [
                                        'id' => $single_id,
                                        'update_time' => $single['update_time']
                                    ];
                                }
                                $singe_set = [];
                                if (!$single) {
                                    // 如果楼栋不存在则必须填写对应楼栋编号
                                    if (!$single_number) {
                                        $vv['M'] = '导入房屋时候自动创建楼栋的时候楼栋编号必填';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    if (!preg_match($check_number, $single_number)) {
                                        $vv['M'] = '导入房屋时候自动创建创建楼栋的时候楼栋编号只允许数字';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    // 楼栋编号 暂时同一个小区下仅限1-99或1-999数字编号
                                    if ($single_number) {
                                        $single_data['single_name'] = $single_name ? $single_name : $single_number;
                                        $single_data['single_number'] = $single_number;
                                        // 去除重复
                                        $where_repeat = [
                                            'village_id' => $this->village_id,
                                            'single_number' => $single_number,
                                            'status' => ['neq', 4]
                                        ];
                                        $repeat_id = $db_house_village_single->where($where_repeat)->getField('id');
                                        if ($repeat_id) {
                                            $vv['M'] = "该楼栋编号【{$single_number}】已经存在，请填写其他1-{$max_num}的楼栋编号！";
                                            $error_arr[] = $vv;
                                            continue;
                                        }
                                    }
                                    $single_id = $db_house_village_single->data($single_data)->add();
                                    if (!$single_id) {
                                        $vv['M'] = '对应楼栋添加失败！';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    $single_arr[] = [
                                        'id' => $single_id,
                                        'update_time' => 0
                                    ];
                                } elseif (!$single['single_number']) {
                                    // 楼栋编号 暂时同一个小区下仅限1-99或1-999数字编号
                                    if ($single_number) {
                                        $singe_set = [
                                            'single_number' => $single_number
                                        ];

                                    }
                                } else {
                                    $single_number = $single['single_number'];
                                    if (strlen($single_number) < $village_single_support_digit) {
                                        // 不足2位 补足2位
                                        $single_number = str_pad($single_number, $village_single_support_digit, "0", STR_PAD_LEFT);
                                        $singe_set = [
                                            'single_number' => $single_number
                                        ];
                                    }
                                }
                                if ($singe_set && $single_id) {
                                    if ($singe_set['single_number']) {
                                        // 去除重复
                                        $where_repeat = [
                                            'village_id' => $this->village_id,
                                            'single_number' => $singe_set['single_number'],
                                            'id' => ['neq', $single_id],
                                            'status' => ['neq', 4]
                                        ];
                                        $repeat_id = $db_house_village_single->where($where_repeat)->getField('id');
                                        if ($repeat_id) {
                                            $vv['M'] = "该楼栋编号【{$singe_set['single_number']}】已经存在，请填写其他1-99的楼栋编号！";
                                            $error_arr[] = $vv;
                                            continue;
                                        }
                                    }
                                    // 如果需要变动 直接对应变动
                                    $db_house_village_single->where(['id' => $single_id])->data($singe_set)->save();
                                }

                                //单元
                                $floor_data = [];
                                $floor_data['status'] = 1;
                                $floor_data['village_id'] = $this->village_id;
                                $floor_data['single_id'] = $single_id;
                                $where_floor = $floor_data;
                                if (!empty($floor_name)) {
                                    $floor_data['floor_name'] = (string)$floor_name;
                                    $where_floor['floor_name'] = (string)$floor_name;
                                } elseif ($floor_number) {
                                    $floor_data['floor_number'] = $floor_number;
                                    $floor_number_int = intval($floor_number);
                                    $where_floor['_string'] = "(floor_number={$floor_number} OR floor_number={$floor_number_int})";
                                }
                                //todo 过滤重复写入单元数据
                                $floor_str=md5($this->village_id.$single_id.$floor_number);
                                if(isset($floor_temporary[$floor_str]) && !empty($floor_temporary[$floor_str])){
                                    $house_village_floor_info=$floor_temporary[$floor_str];
                                }else{
                                    $house_village_floor_info = $db_house_village_floor->where($where_floor)->find();
                                }
                                $floor_id = $house_village_floor_info['floor_id'] ? $house_village_floor_info['floor_id'] : 0;
                                $floor_arr = [];
                                if ($house_village_floor_info) {
                                    $floor_arr[] = [
                                        'floor_id' => $floor_id,
                                        'update_time' => $house_village_floor_info['update_time']
                                    ];
                                    $floor_temporary[$floor_str]=$house_village_floor_info;
                                }
                                $floor_set = [];
                                if (!$house_village_floor_info) {
                                    // 如果单元不存在则必须填写对应单元编号
                                    if (!$floor_number) {
                                        $vv['M'] = '导入房屋时候自动创建单元的时候单元编号必填';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    if (!preg_match($check_number, $floor_number)) {
                                        $vv['M'] = '导入房屋时候自动创建单元的时候单元编号只允许数字';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    $floor_data['add_time'] = time();
                                    $floor_data['floor_name'] = $floor_name ? $floor_name : $floor_number;
                                    $floor_data['floor_number'] = $floor_number;
                                    // 去除重复
                                    $where_repeat = [
                                        'village_id' => $this->village_id,
                                        'single_id' => $single_id,
                                        'floor_number' => $floor_number,
                                        'status' => ['neq', 4]
                                    ];
                                    $repeat_id = $db_house_village_floor->where($where_repeat)->getField('floor_id');
                                    if ($repeat_id) {
                                        $vv['M'] = "该单元编号【{$floor_number}】已经存在，请填写其他1-99的单元编号！";
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    $floor_id = $db_house_village_floor->data($floor_data)->add();
                                    if (!$floor_id) {
                                        $vv['M'] = '对应单元添加失败！';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    $floor_data22=[];
                                    $floor_data22=$floor_data;
                                    $floor_data22['floor_id']=$floor_id;
                                    $floor_temporary[$floor_str]=$floor_data22;
                                    $floor_arr[] = [
                                        'floor_id' => $floor_id,
                                        'update_time' => 0
                                    ];
                                } elseif (!$house_village_floor_info['floor_number']) {
                                    // 单元编号 暂时同一个楼栋下仅限1-99数字编号
                                    if ($floor_number) {
                                        $floor_set = [
                                            'floor_number' => $floor_number
                                        ];
                                    }
                                } else {
                                    $floor_number = $house_village_floor_info['floor_number'];
                                    if (strlen($floor_number) < 2) {
                                        // 不足2位 补足2位
                                        $floor_number = str_pad($floor_number, 2, "0", STR_PAD_LEFT);
                                        $floor_set = [
                                            'floor_number' => $floor_number
                                        ];
                                    }
                                }

                                if ($floor_set && $floor_id) {
                                    if ($floor_set['floor_number']) {
                                        // 去除重复
                                        $where_repeat = [
                                            'village_id' => $this->village_id,
                                            'single_id' => $single_id,
                                            'floor_number' => $floor_set['floor_number'],
                                            'floor_id' => ['neq', $floor_id],
                                            'status' => ['neq', 4]
                                        ];
                                        $repeat_id = $db_house_village_floor->where($where_repeat)->getField('floor_id');
                                        if ($repeat_id) {
                                            $vv['M'] = "该单元编号【{$floor_set['floor_number']}】已经存在，请填写其他1-99的单元编号！";
                                            $error_arr[] = $vv;
                                            continue;
                                        }
                                    }
                                    // 如果需要变动 直接对应变动
                                    $db_house_village_floor->where(['floor_id' => $floor_id])->data($floor_set)->save();
                                }

                                //楼层
                                $layer_data = [];
                                $layer_data['village_id'] = $this->village_id;
                                $layer_data['floor_id'] = $floor_id;
                                $layer_data['single_id'] = $single_id;
                                $layer_data['status'] = 1;
                                $where_layer = [];
                                if (!empty($layer_name)) {
                                    $layer_data['layer_name'] = $layer_name;
                                    $where_layer['layer_name'] = $layer_name;
                                } elseif ($layer_number) {
                                    $layer_data['layer_number'] = $floor_number;
                                    $layer_number_int = intval($layer_number);
                                    $where_layer['_string'] = "(layer_number={$layer_number} OR layer_number={$layer_number_int})";
                                }
                                $where_layer['status'] = 1;
                                $where_layer['village_id'] = $this->village_id;
                                $where_layer['single_id'] = $single_id;
                                $where_layer['floor_id'] = $floor_id;
                                $house_village_layer = $db_house_village_layer->where($where_layer)->find();
                                $layer_id = $house_village_layer['id'] ? $house_village_layer['id'] : 0;
                                $layer_set = [];
                                if (empty($house_village_layer)) {
                                    // 如果楼层不存在则必须填写对应楼层编号
                                    if (!$layer_number) {
                                        $vv['M'] = '导入房屋时候自动创建楼层的时候楼层编号必填';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    if (!preg_match($check_number, $layer_number)) {
                                        $vv['M'] = '导入房屋时候自动创建楼层的时候楼层编号只允许数字';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    $layer_data['layer_number'] = $layer_number;
                                    $layer_data['layer_name'] = $layer_name ? $layer_name : $layer_number;
                                    // 去除重复
                                    $where_repeat = [
                                        'village_id' => $this->village_id,
                                        'single_id' => $single_id,
                                        'floor_id' => $floor_id,
                                        'layer_number' => $layer_number,
                                        'status' => ['neq', 4]
                                    ];
                                    $repeat_id = $db_house_village_layer->where($where_repeat)->getField('id');
                                    if ($repeat_id) {
                                        $vv['M'] = "该楼层编号【{$layer_number}】已经存在，请填写其他1-99的楼层编号！";
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    $layer_id = $db_house_village_layer->data($layer_data)->add();
                                    if (!$layer_id) {
                                        $vv['M'] = '对应添楼层加失败！';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                } elseif (!$house_village_layer['layer_number']) {
                                    // 楼层编号 暂时同一个单元下仅限1-99数字编号
                                    if ($layer_number) {
                                        $layer_set = [
                                            'layer_number' => $layer_number
                                        ];
                                    }
                                } else {
                                    $layer_number = $house_village_layer['layer_number'];
                                    if (strlen($layer_number) < 2) {
                                        // 不足2位 补足2位
                                        $layer_number = str_pad($layer_number, 2, "0", STR_PAD_LEFT);
                                        $layer_set = [
                                            'layer_number' => $layer_number
                                        ];
                                    }
                                }
                                if ($layer_set && $layer_id) {
                                    if ($layer_set['layer_number']) {
                                        // 去除重复
                                        $where_repeat = [
                                            'village_id' => $this->village_id,
                                            'single_id' => $single_id,
                                            'floor_id' => $floor_id,
                                            'layer_number' => $layer_set['layer_number'],
                                            'id' => ['neq', $layer_id],
                                            'status' => ['neq', 4]
                                        ];
                                        $repeat_id = $db_house_village_layer->where($where_repeat)->getField('id');
                                        if ($repeat_id) {
                                            $vv['M'] = "该楼层编号【{$layer_set['layer_number']}】已经存在，请填写其他1-99的楼层编号！";
                                            $error_arr[] = $vv;
                                            continue;
                                        }
                                    }
                                    // 如果需要变动 直接对应变动
                                    $db_house_village_layer->where(['id' => $layer_id])->data($layer_set)->save();
                                }

                                //房间
                                if ($layer_id) {
                                    $vacancy_data = [];
                                    $vacancy_data['single_id'] = $single_id;
                                    $vacancy_data['floor_id'] = $floor_id;
                                    $vacancy_data['layer_id'] = $layer_id;
                                    $vacancy_data['village_id'] = $this->village_id;
                                    $vacancy_data['is_del'] = 0;//未删除的
                                    $where_vacancy = $vacancy_data;
                                    if ($room_number) {
                                        $room_number_int = intval($room_number);
                                    }
                                    if (!empty($room_name)) {
                                        $vacancy_data['room'] = $room_name;
                                        $where_vacancy['room'] = $room_name;
                                    } elseif ($room_number) {
                                        $vacancy_data['room_number'] = $room_number;
                                        $where_vacancy['_string'] = "(room_number={$room_number} OR room_number=$room_number_int})";
                                    }
                                    $house_village_user_vacancy = $db_house_village_user_vacancy->where($where_vacancy)->find();
                                    if (empty($house_village_user_vacancy)) {
                                        //住房面积
                                        if (empty($vv['J'])) {
                                            $vv['J'] = 0;
                                        }
                                        $vacancy_data['housesize'] = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                                        //使用类型 1住宅 2商铺 3办公
                                        if (trim($vv['K']) == '住宅') {
                                            $vacancy_data['house_type'] = 1;
                                        } elseif (trim($vv['K']) == '商铺') {
                                            $vacancy_data['house_type'] = 2;
                                        } elseif (trim($vv['K']) == '办公') {
                                            $vacancy_data['house_type'] = 3;
                                        }
                                        $vacancy_data['add_time'] = time();
                                        $vacancy_data['status'] = 1;
                                        if ($vv['A'] === null) {
                                            //物业编号非必填，没有填的默认生成，按照小区ID+楼栋ID+单元ID+楼层ID+房号ID生成
                                            $vacancy_data['usernum'] = $this->village_id . '-' . $single_id . '-' . $floor_id . '-' . $layer_id;
                                        } else {
                                            $vacancy_data['usernum'] = trim($vv['A']);
                                        }
                                        $vacancy_data['layer'] = $layer_name;
                                        if (!$room_number) {
                                            $room_number = preg_replace($pattern_number, '', $vacancy_data['room']);
                                            // 房屋编号 同一个楼层下仅限1-9999 此处处理为4位 截取后4位
                                            if ($room_number) {
                                                if (strlen($room_number) > 4) {
                                                    // 超过4位 截取后4位
                                                    $room_number = substr($room_number, -4, 4);
                                                } elseif (strlen($room_number) < 2) {
                                                    // 不足2位 补足2位
                                                    $room_number = str_pad($room_number, 2, "0", STR_PAD_LEFT);
                                                } elseif (strlen($room_number) > 2 && strlen($room_number) < 4) {
                                                    // 大于2位不足4位 补足4位
                                                    $room_number = str_pad($room_number, 4, "0", STR_PAD_LEFT);
                                                }
                                                $vacancy_data['room_number'] = $room_number;
                                            }
                                        } else {
                                            if ($room_number_int<100 && $layer_number) {
                                                $room_number = $layer_number . $room_number;
                                            }
                                            if (strlen($room_number) > 2 && strlen($room_number) < 4) {
                                                // 大于2位不足4位 补足4位
                                                $room_number = str_pad($room_number, 4, "0", STR_PAD_LEFT);
                                            }
                                            $vacancy_data['room_number'] = $room_number;
                                        }
                                        if ($single_number && $floor_number && $layer_number && $room_number) {
                                            if (strlen($room_number) == 2) {
                                                $property_number = $single_number . $floor_number . $layer_number . $layer_number . $room_number;
                                            } elseif (strlen($room_number) == 4) {
                                                $property_number = $single_number . $floor_number . $layer_number . $room_number;
                                            } elseif (strlen($room_number) > 4) {
                                                // 超过4位 截取后4位
                                                $room_number = substr($room_number, -4, 4);
                                                $vacancy_data['room_number'] = $room_number;
                                                $property_number = $single_number . $floor_number . $layer_number . $room_number;
                                            }
                                            $vacancy_data['property_number'] = $property_number;
                                        }
                                        if (isset($vacancy_data['room_number'])) {
                                            $where_repeat = [
                                                'village_id' => $this->village_id,
                                                'single_id' => $single_id,
                                                'floor_id' => $floor_id,
                                                'layer_id' => $layer_id,
                                                'room_number' => $vacancy_data['room_number'],
                                                'status' => ['neq', 4],
                                                'is_del' => 0
                                            ];
                                            $repeat_id = $db_house_village_user_vacancy->where($where_repeat)->getField('pigcms_id');
                                            if ($repeat_id) {
                                                $vv['M'] = '该房屋编号【取房号名称后4位】已经存在，请填写其他1-9999的房号名称！';
                                                $error_arr[] = $vv;
                                                continue;
                                            }
                                        }
                                        $vacancy_data['sort']=$sort;
                                        $room_id = $db_house_village_user_vacancy->data($vacancy_data)->add();
                                        if($room_id){
                                            $vacancy_usernum = $db_house_village_user_vacancy->where(['pigcms_id'=>$room_id])->getField('usernum');
                                            $vacancy_datas['usernum'] = $vacancy_usernum.'-'.$room_id;
                                            $db_house_village_user_vacancy->where(['pigcms_id'=>$room_id])->data($vacancy_datas)->save();
                                            $arr = array();
                                            $arr['param'] = serialize(array('vacancy_id' =>$room_id,'type'=>'house'));
                                            $arr['plan_time'] = time()+60;
                                            $arr['space_time'] = 0;
                                            $arr['add_time'] = time();
                                            $arr['file'] = 'sub_house_village_third';
                                            $arr['time_type'] = 1;
                                            $arr['unique_id'] = 'sub_house_village_third_house_'.$room_id;
                                            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                                            M('process_sub_plan')->data($arr)->add();
                                        }
                                    } else {
                                        $vv['M'] = '当前房屋已经存在 请确认后再进行导入！';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                } elseif (!$single_id) {
                                    $vv['M'] = '当前楼栋不存在或者添加失败！';
                                    $error_arr[] = $vv;
                                    continue;
                                } elseif (!$floor_id) {
                                    $vv['M'] = '当前单元不存在或者添加失败！';
                                    $error_arr[] = $vv;
                                    continue;
                                } elseif (!$layer_id) {
                                    $vv['M'] = '当前楼层不存在或者添加失败！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($surplus_count > 0) {
                                    $surplus_count--;
                                }
                            }
                            //楼栋相关数据
                            if ($single_arr) {
                                $single_arr = $this->assoc_unique($single_arr, 'id');
                                foreach ($single_arr as $key => $val) {
                                    $map['single_id'] = $val['id'];
                                    $map['village_id'] = $this->village_id;
                                    $map['is_del'] = 0;
                                    if ($val['update_time'] == 0) {
                                        //计算所有房间总面积
                                        $house_size = $db_house_village_user_vacancy->where($map)->sum('housesize');
                                        //计算所有房间
                                        $room_count = $db_house_village_user_vacancy->where($map)->count();
                                        //地上建筑层数
                                        unset($map['is_del']);
                                        $map['layer_name'] = ['gt', 0];
                                        $layer_ground_count = $db_house_village_layer->where($map)->count();
                                        //地下建筑层
                                        unset($map['layer_name']);
                                        $map['layer_name'] = ['lt', 0];
                                        $layer_underground_count = $db_house_village_layer->where($map)->count();
                                        //单元数量
                                        unset($map['layer_name']);
                                        $floor_count = $db_house_village_floor->where($map)->count();
                                        $where = [];
                                        $where['id'] = $val['id'];
                                        $single_data = [
                                            'measure_area' => $house_size,
                                            'floor_num' => $floor_count,
                                            'vacancy_num' => $room_count,
                                            'upper_layer_num' => $layer_ground_count,
                                            'lower_layer_num' => $layer_underground_count,
                                        ];
                                        $db_house_village_single->where($where)->data($single_data)->save();
                                    }
                                }
                            }
                            //单元相关数据
                            if ($floor_arr) {
                                $floor_arr = $this->assoc_unique($floor_arr, 'floor_id');
                                foreach ($floor_arr as $key => $val) {
                                    $floor_map['floor_id'] = $val['floor_id'];
                                    $floor_map['village_id'] = $this->village_id;
                                    $floor_map['is_del'] = 0;
                                    if ($val['update_time'] == 0) {
                                        //计算单元下房间总面积
                                        $house_size = $db_house_village_user_vacancy->where($floor_map)->sum('housesize');
                                        //计算单元下所有房间
                                        $room_count = $db_house_village_user_vacancy->where($floor_map)->count();
                                        //地上建筑层数
                                        unset($floor_map['is_del']);
                                        $floor_map['layer_name'] = ['gt', 0];
                                        $layer_ground_count = $db_house_village_layer->where($floor_map)->count();
                                        //最高住人层
                                        $max_layer = $db_house_village_layer->where($floor_map)->order('layer_name desc')->getField('layer_name');
                                        //地下建筑层
                                        unset($floor_map['layer_name']);
                                        $floor_map['layer_name'] = ['lt', 0];
                                        $layer_underground_count = $db_house_village_layer->where($floor_map)->count();
                                        //最低住人层
                                        $min_layer = $db_house_village_layer->where($floor_map)->order('layer_name asc')->getField('layer_name');
                                        $floor_data = [
                                            'floor_area' => $house_size,
                                            'house_num' => $room_count,
                                            'floor_upper_layer_num' => $layer_ground_count,
                                            'floor_lower_layer_num' => $layer_underground_count,
                                            'start_layer_num' => $min_layer ? $min_layer : 1,
                                            'end_layer_num' => $max_layer,
                                        ];
                                        $db_house_village_floor->where(['floor_id' => $val['floor_id']])->data($floor_data)->save();
                                    }
                                }
                            }
                            //错误信息导出表格
                            if ($error_arr && count($error_arr) > 0) {
                                $num = count($error_arr);
                                echo '<javascript>alert("失败' . $num . '条，点击下载)";</javascript>';
                                //导出失败信息
                                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                                error_reporting(E_ALL);
                                date_default_timezone_set('Europe/London');
                                $objExcel = new PHPExcel();

                                $prompt = '注意：楼栋编号/单元编号/楼层编号/房间编号必须填写阿拉伯数字（1,2,3）。若楼栋/单元/楼层不存在，系统会自动创建，无需人工进行添加/导入';
                                $title = $this->village['village_name'] . '社区-房间导入失败列表';
                                /*以下是一些设置 ，什么作者  标题之类的*/
                                $objExcel->getProperties()->setCreator($title)
                                    ->setLastModifiedBy($title)
                                    ->setTitle($title)
                                    ->setSubject("失败信息导出")
                                    ->setDescription("备份数据")
                                    ->setKeywords("excel")
                                    ->setCategory("result file");

                                $i = 0;
                                $objExcel->createSheet();
                                $objExcel->setActiveSheetIndex($i);
                                $objExcel->getActiveSheet()->setTitle('房间列表');
                                $objExcel->getActiveSheet()->mergeCells('A1:M1');
                                $objActSheet = $objExcel->getActiveSheet();

                                $objActSheet->setCellValue('A1', $prompt);
                                $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(12)->getColor()->setRGB('FF0000');
                                $objExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(23);

                                $objActSheet->setCellValue('A2', '物业编号(非必填)');
                                $objActSheet->setCellValue('B2', '楼栋');
                                $objActSheet->setCellValue('C2', '楼栋编号');
                                $objActSheet->setCellValue('D2', '单元');
                                $objActSheet->setCellValue('E2', '单元编号');
                                $objActSheet->setCellValue('F2', '楼层');
                                $objActSheet->setCellValue('G2', '楼层编号');
                                $objActSheet->setCellValue('H2', '房房间号');
                                $objActSheet->setCellValue('I2', '房间编号');
                                $objActSheet->setCellValue('J2', '面积（㎡）');
                                $objActSheet->setCellValue('K2', '使用类型(住宅/商铺/办公)');
                                $objActSheet->setCellValue('L2', '排序');
                                $objActSheet->setCellValue('M2', '失败原因');

                                $index = 3;
                                foreach ($error_arr as $value) {
                                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                    $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                    $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                                    $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                                    $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                                    $objActSheet->setCellValueExplicit('J' . $index, $value['J']);
                                    $objActSheet->setCellValueExplicit('K' . $index, $value['K']);
                                    $objActSheet->setCellValueExplicit('L' . $index, $value['L']);
                                    $objActSheet->setCellValueExplicit('M' . $index, $value['M']);
                                    $index++;
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
                                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                                header("Content-Transfer-Encoding:binary");
                                $objWriter->save('php://output');
                                exit();
                            } else {
                                $this->success('导入成功');
                                exit;
                            }
                        }

                    } else {
                        $this->error($upload->getErrorMsg());
                        exit;
                    }
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $database_house_village_config = D('House_village_config');
            $village_config = $database_house_village_config->where(array('village_id' => $this->village_id))->find();
            $village_single_support_digit = intval($village_config['village_single_support_digit']) ? intval($village_config['village_single_support_digit']) : 2;
            if (!$village_single_support_digit || $village_single_support_digit != 3) {
                $village_single_support_digit = 2;
            }
            $max_num = 99;
            if ($village_single_support_digit == 3) {
                $max_num = 999;
            }
            $this->assign('max_num', $max_num);
            $limit_info = D('House_property')->get_room_limit_num($this->house_session['property_id']);
            $this->assign('limit_info', $limit_info);
            $this->display();
        }
    }
 
    public function import_village_num(){
        //    $address = D('House_village')->get_single_floor_room(80,202,1261,8869,50);
        //    dump($address);die;
        if(IS_POST) {
            //购买的功能套餐和房间套餐的总房间数量 start 2020/8/31
            $property_id = $this->house_session['property_id'];
            $package_where['property_id'] = $property_id;
            $package_where['status'] = 1;
            $package_where['order_type'] = ['neq',3];
            $package_where['package_end_time|package_try_end_time'] = ['gt',time()];
            $package_order = D('Package_order')->where($package_where)->order('order_id desc')->select();
            $pr_where['property_id'] = $property_id;
            $pr_where['pay_status'] = 1;
            //$room_total_num = D('Package_room_parent_order')->where($pr_where)->field('sum(total_num) as total_num')->find();
            $room_num = 0;
            $order_idArr=array();
            if($package_order) {
                foreach($package_order as $pov){
                    $order_idArr[]=$pov['order_id'];
                }
                $package_order=$package_order['0'];
		$room_where=array();
                $room_where['package_order_id'] = array('in',$order_idArr);;
                $room_where['status'] = 1;
                $room_num = D('Package_room_order')->where($room_where)->field('sum(room_num*num) as room_num')->find();
                $room_num = $room_num['room_num'];
            }else{
                $this->error('对不起，您需要购买功能套餐');
            }
//            $total_room_num = $package_order['room_num']+$room_total_num['total_num']+$room_num;
            $total_room_num = $package_order['room_num']+$room_num;
            //购买的功能套餐和房间套餐的总房间数量 end 2020/8/31
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');
                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
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
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);  //删除文件
                    $error_arr = [];
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        unset($result[2]);
                        if(count($result) == 0){
                            $this->assign('waitSecond',10);
                            $this->error('表格未成功识别。请使用 excel软件 将表格更换为 xlsx格式 重试一下');
                        }
                        $single_arr = [];
                        $floor_arr = [];
                        // 字符串替换去除数字以外的
                        $pattern_number = "/[^0-9]/";
                        // 正则检查是否为纯数字
                        $check_number = "/^[0-9]+$/";
                        // 正则检查是否为字母或数字
                        $pattern = '/^[a-zA-Z0-9]+$/u';
                        //2020/8/31 判断房间导入的房间数量不得大于购买的房间数量 start
                        $map_vacancy['village_id'] = $this->village_id;
                        $map_vacancy['is_del'] = 0;
                        $map_vacancy['status'] = ['in',[1,0,3]];
                        $exist_room_count = D('House_village_user_vacancy')->where($map_vacancy)->count();
                        $surplus_count = $total_room_num-$exist_room_count;//剩余房间数量
                        $total_count = $surplus_count;
                        if($surplus_count<=0)
                        {
                            $this->error('对不起，您已经超出房间导入总数量，您可以去购买房间套餐');
                        }
                        $database_house_village_config = D('House_village_config');
                        $village_config = $database_house_village_config->where(array('village_id'=>$this->village_id))->find();
                        $village_single_support_digit = intval($village_config['village_single_support_digit']) ? intval($village_config['village_single_support_digit']) : 2;
                        if (!$village_single_support_digit || $village_single_support_digit!=3) {
                            $village_single_support_digit = 2;
                        }

                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            if ($kk - 1 > $surplus_count) {
                                $vv['I'] = "成功导入{$surplus_count}房间数据,剩下的房间可通过购买房间套餐或者升级功能套餐后继续导入！";
                                $error_arr[] = $vv;
                                break;
                            }
                            //套餐 start
                            /*
                            if($surplus_count<=0)
                            {
                                $vv['I'] = "成功导入{$total_count}房间数据,剩下的房间可通过购买房间套餐或者升级功能套餐后继续导入！";
                                $error_arr[] = $vv;
                                continue;
                            }
                            */
                            //套餐 end
                            if ($vv['A'] === null && $vv['C'] === null && $vv['D'] === null && $vv['B'] === null && $vv['F'] === null && $vv['G'] === null && $vv['E'] === null) continue;
                            $single_name = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            if (empty($single_name)) {
                                $vv['I'] = '请填写楼栋名称！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $floor_name = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            if (empty($floor_name)) {
                                $vv['I'] = '请填写单元名称！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $layer_name = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            if (empty($layer_name)) {
                                $vv['I'] = '请填写楼层号！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $room_name = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            if (empty($room_name)) {
                                $vv['I'] = '请填写房号！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            if (empty($vv['E']) && empty($vv['F']) && empty($vv['G']) && empty($vv['H'])) {
                                $vv['I'] = '请填写三表表号！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $where_single = [
                                'village_id' => $this->village_id,
                                'single_name' => $single_name,
                                'status' => 1
                            ];
                            $db_house_village_single = D('House_village_single');
                            $single_info = $db_house_village_single->where($where_single)->find();
                            if(empty($single_info)){
                                $vv['I'] = '楼栋不存在！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $where_floor = [
                                'village_id' => $this->village_id,
                                'floor_name' => $floor_name,
                                'status' => 1,
                                'single_id'=>$single_info['id']
                            ];
                            $db_house_village_floor = D('House_village_floor');
                            $floor_info = $db_house_village_floor->where($where_floor)->find();
                            if(empty($floor_info)){
                                $vv['I'] = '单元不存在！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $where_layer = [
                                'village_id' => $this->village_id,
                                'layer_name' => $layer_name,
                                'status' => 1,
                                'single_id'=>$single_info['id'],
                                'floor_id'=>$floor_info['floor_id']
                            ];
                            $db_house_village_layer = D('House_village_layer');
                            $layer_info = $db_house_village_layer->where($where_layer)->find();
                            if(empty($layer_info)){
                                $vv['I'] = '层号不存在！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $db_house_village_user_vacancy = D('House_village_user_vacancy');
                            $where_room = [
                                'village_id' => $this->village_id,
                                'room' => $room_name,
                                'single_id' => $single_info['id'],
                                'floor_id'=>$floor_info['floor_id'],
                                'layer_id'=>$layer_info['id'],
                            ];
                            $room_info = $db_house_village_user_vacancy->where($where_room)->find();
                            if(empty($room_info)){
                                $vv['I'] = '房间不存在！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            //todo 定制三表
                            $vacancy_num_arr=[
                                'ele_number'=>$vv['E'] ? $vv['E'] : '',
                                'water_number'=>$vv['F'] ? $vv['F'] : '',
                                'heat_water_number'=>$vv['G'] ? $vv['G'] : '',
                                'gas_number'=>$vv['H'] ? $vv['H'] : '',
                            ];
                            $res= D('House_village_user_vacancy')->checkVacancyNum($this->village_id,$room_info['pigcms_id'],$vacancy_num_arr);
                            if(!$res['error']){
                                $vv['I'] = $res['msg'];
                                $error_arr[] = $vv;
                                continue;
                            }

                        }
                        //错误信息导出表格
                        if($error_arr && count($error_arr)>0)
                        {
                            $num = count($error_arr);
                            echo '<javascript>alert("失败'. $num .'条，点击下载)";</javascript>';
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);
                            date_default_timezone_set('Europe/London');
                            $objExcel = new PHPExcel();

                            $prompt = '注意：楼栋/单元/楼层按小区内实际情况填写，无需与后台添加的楼栋/单元/楼层保持一致，添加后会统一生成对应的楼栋/单元/楼层数据，必须填写阿拉伯数字（1,2,3）或字母（a,b,c），对应展示会按照系统默认的xx号楼xx单元xx层展示';
                            $title = $this->village['village_name'] . '社区-房间三表导入失败列表';
                            /*以下是一些设置 ，什么作者  标题之类的*/
                            $objExcel->getProperties()->setCreator($title)
                                ->setLastModifiedBy($title)
                                ->setTitle($title)
                                ->setSubject("失败信息导出")
                                ->setDescription("备份数据")
                                ->setKeywords("excel")
                                ->setCategory("result file");

                            $i = 0;
                            $objExcel->createSheet();
                            $objExcel->setActiveSheetIndex($i);
                            $objExcel->getActiveSheet()->setTitle('房间列表');
                            $objExcel->getActiveSheet()->mergeCells('A1:K1');
                            $objActSheet = $objExcel->getActiveSheet();

                            $objActSheet->setCellValue('A1',$prompt);
                            $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(12)->getColor()->setRGB('FF0000');
                            $objExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(23);

                            $objActSheet->setCellValue('A2', '楼号');
                            $objActSheet->setCellValue('B2', '单元名称');
                            $objActSheet->setCellValue('C2', '层号');
                            $objActSheet->setCellValue('D2', '房间号');
                            $objActSheet->setCellValue('E2', '电表编号');
                            $objActSheet->setCellValue('F2', '冷水表编号');
                            $objActSheet->setCellValue('G2', '热水表编号');
                            $objActSheet->setCellValue('H2', '燃气表编号');
                            $objActSheet->setCellValue('I2', '失败原因');
                            $cell_list = range('A','I');
                            foreach ($cell_list as $cell) {
                                $objActSheet->getColumnDimension($cell)->setWidth(30);
                            }
                            $index = 3;
                            foreach ($error_arr as  $value) {
                                $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                                $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                                $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                                $index++;
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
                            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }else{
                            $this->success('导入成功');
                            exit;
                        }
                    }else {
                        $this->error($upload->getErrorMsg());
                        exit;
                    }
                }
            }
            $this->error('文件上传失败');
            exit;
        }else{
            $limit_info = D('House_property')->get_room_limit_num($this->house_session['property_id']);
            $this->assign('limit_info',$limit_info);
            $this->display();
        }
    }
 
 
	public function roomExport() {
		$param = $_POST;
		$param['type'] = 'room_export';
		$param['rand_number'] = time();
		$property_id = $this->house_session['property_id'];
		$param['property_id'] = $property_id;
		$param['village_id'] = $this->village_id;
		if(isset($_GET['export_from']) && in_array($_GET['export_from'],array('unitRental','buildingRoom'))){
			$inputparam=file_get_contents('php://input');
			$inputparam=json_decode($inputparam,1);
			if(!empty($inputparam)){
				$param=array_merge($inputparam,$param);
			}
			$param['export_from']='unitRental';
			if($_GET['export_from']=='buildingRoom'){
                $param['export_from']='buildingRoom';
            }
		}
		if ($res = D('Order')->order_export($param)) {
			$data=array('error_code' => 0, 'msg' => L_('添加导出计划成功'), 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']);
			if(isset($_GET['export_from']) && in_array($_GET['export_from'],array('unitRental','buildingRoom'))){
				$output = [
					'status' =>  1000 ,
					'msg' 	 => '',
					'data' 	 => $data,
					'refresh_ticket'=>''
				];
				echo json_encode($output,JSON_UNESCAPED_UNICODE);
				exit();
			}else{
				echo json_encode($data);
			}

		} else {
			if(isset($_GET['export_from']) && in_array($_GET['export_from'],array('unitRental','buildingRoom'))){
				$output = [
					'status' =>  1000 ,
					'msg' 	 => '',
					'data' 	 => array('error_code' => 1, 'msg' => L_('导出失败')),
					'refresh_ticket'=>''
				];
				echo json_encode($output,JSON_UNESCAPED_UNICODE);
				exit();
			}else{
				echo json_encode(array('error_code' => 1, 'msg' => L_('导出失败')));
			}

		}
		die;
	}

	public function roomExportImport() {
		if ($this->config['houseRoomExportImportSwitch']!=1) {
			$this->error('对不起，您没有权限执行此操作');
		}
		if (IS_POST) {
			if ($_FILES['file']['error'] != 4) {
				set_time_limit(0);
				ini_set('memory_limit', '2048M');
				import('ORG.Util.Dir');
				Dir::delDirnotself('./upload/excel/villageuser');
				$upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
				if (!is_dir($upload_dir)) {
					mkdir($upload_dir, 0777, true);
				}
				$param = [];
				$property_id = $this->house_session['property_id'];
				$param['property_id'] = $property_id;
				$param['village_id'] = $this->village_id;
				try {
					D('House_village_user_vacancy')->roomExportImport($upload_dir,$param);
				} catch (\Exception $e) {
					$this->assign('waitSecond', 10);
					$this->error($e->getMessage());
				}
				$this->success('导入成功');
				exit();
			}
			$this->error('文件上传失败');
			exit();
		} else {
			$this->display();
		}
	}

	
 
    public function assoc_unique($arr, $key) {
        $tmp_arr = [];
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {//判断$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }

    public function import_village_edit()
    {
        //房间管理-查看 权限
        if (!in_array(37, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = $_GET['id'] + 0;
        if (!$id) {
            $this->error('传递参数有误！');
        }

        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $database_Face_a185_indoor = D('Face_a185_indoor');
        $database_House_face_device = D('House_face_device');
        $where['pigcms_id'] = $id;
        $where['village_id'] = $this->village_id;
        //todo 室内机设备号
        $a185_indoor = $database_Face_a185_indoor->where(['village_id' => $this->village_id, 'device_type' => 2, 'room_id' => $id])->field('id,device_sn,status')->find();

        $a185_device = $database_House_face_device->where(['village_id'=>$this->village_id,'device_type'=>61,'is_del'=>0])->count();
        if (IS_POST) {
            //房间管理-编辑 权限
            if (!in_array(39, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
 
            $vacancy_num_arr=[];
            if(isset($_POST['water_number'])){
                $vacancy_num_arr['water_number'] = $_POST['water_number'];
                unset($_POST['water_number']);
            }
            if(isset($_POST['ele_number'])){
                $vacancy_num_arr['ele_number'] = $_POST['ele_number'];
                unset($_POST['ele_number']);
            }
            if(isset($_POST['gas_number'])){
                $vacancy_num_arr['gas_number'] = $_POST['gas_number'];
                unset($_POST['gas_number']);
            }
            if(isset($_POST['heat_water_number'])){
                $vacancy_num_arr['heat_water_number'] = $_POST['heat_water_number'];
                unset($_POST['heat_water_number']);
            }
 
            if (isset($_POST['contract_time_start'])&&!empty($_POST['contract_time_start'])) {
                $_POST['contract_time_start'] =strtotime($_POST['contract_time_start'].' 00:00:00');
            }
            $detail = $database_house_village_user_vacancy->house_village_user_vacancy_detail($where);
            $single = D('House_village_single')->where(['id'=>$detail['detail']['single_id']])->find();
            if (!empty($single)){
                if ($single['contract_time_end']>1){
                    $_POST['contract_time_end']=$single['contract_time_end'];
                }else{
                    $_POST['contract_time_end'] = '';
                }
            }else{
                $_POST['contract_time_end'] = '';
            }
            if(empty($_POST['contract_time_end']) && !empty($_POST['contract_time_start'])){
                $this->error('请先去楼栋设置合同时间');
            }
            if(!empty($_POST['contract_time_start'])&&!empty($_POST['contract_time_end'])&&$_POST['contract_time_start']>=$_POST['contract_time_end']){
                $this->error('合同开始时间不能大于合同结束时间');
            }
            if(!empty($_POST['contract_time_start']) && $_POST['contract_time_start'] < $single['contract_time_start']){
                $this->error('房间合同开始时间不能大于楼栋合同开始时间');
            }
            if(!empty($_POST['contract_time_start'])&&!empty($_POST['contract_time_end'])){
                $where_time=[];
                $where_time['village_id']=$this->village_id;
                $where_time['order_type']='property';
                $where_time['room_id']=$id;
                $time_info1=D('House_new_order_log')->where($where_time)->order('id DESC')->find();
                //合同结束时间不能小于最大的物业服务结束时间
                if (!empty($time_info1)&&$time_info1['service_end_time']>$_POST['contract_time_end']){
                    $msg='合同结束时间不能小于'.date('Y-m-d',$time_info1['service_end_time']);
                    $this->error($msg);
                }
                //合同开始时间不能大于最小的物业服务结束时间
                if (!empty($time_info1)&&$time_info1['service_end_time']<$_POST['contract_time_start']){
                    $msg='合同开始时间不能大于'.date('Y-m-d',$time_info1['service_end_time']);
                    $this->error($msg);
                }
            }
            if(isset($_POST['room_alias_id']) && !empty($_POST['room_alias_id'])){
                if(!is_numeric($_POST['room_alias_id'])){
                    $this->error('房间别名编号必须填写数字！');
                }
                $_POST['room_alias_id']=trim($_POST['room_alias_id']);
                $whereArr=array('village_id'=>$this->village_id,'is_del'=>0,'room_alias_id'=>$_POST['room_alias_id']);
                $whereArr['_string']='pigcms_id !='.$id;
                $village_user_vacancy=$database_house_village_user_vacancy->get_one_village_user_vacancy($whereArr,'pigcms_id,room_alias_id,name,phone');
                if(!empty($village_user_vacancy)){
                    $this->error('房间别名编号 '.$_POST['room_alias_id'].' 已被使用了，请换其他的填写！');
                }
            }
            $result = $database_house_village_user_vacancy->house_village_user_vacancy_edit_find($where, $_POST);
            if (!$result) {
                $this->error('数据处理有误！');
            }else{
                if($result['status']){
 
                    //todo 定制三表
                    $res=$database_house_village_user_vacancy->checkVacancyNum($this->village_id,$id,$vacancy_num_arr);
                    if(!$res['error']){
                        $this->error($res['msg']);
					}
					
                    if(isset($water_number) && isset($ele_number) && isset($gas_number)){
                        if(M('House_village_vacancy_num')->where(['vacancy_id'=>$id])->find()){
                            M('House_village_vacancy_num')->where(['vacancy_id'=>$id])->save(['water_number'=>$water_number,'ele_number'=>$ele_number,'gas_number'=>$gas_number,'village_id'=>$this->village_id]);
                        }else{
                            M('House_village_vacancy_num')->add(['water_number'=>$water_number,'ele_number'=>$ele_number,'gas_number'=>$gas_number,'vacancy_id'=>$id,'village_id'=>$this->village_id]);
                        }
                    }
                    if(isset($_POST['indoor_device_sn']) && !empty($_POST['indoor_device_sn'])){
                        $rr=  D('Face_a185_indoor')->a185_indoor_room($a185_indoor,[
                            'village_id'=>$this->village_id,
                            'device_sn'=>$_POST['indoor_device_sn'],
                            'status'=>$_POST['indoor_status'],
                            'room_id'=>$id
                        ]);
                        if(!$rr['error']){
                            $this->error($rr['msg']);
                        }
                    }
                    $arr = array();
                    $arr['param'] = serialize(array('vacancy_id' =>$id,'type'=>'house'));
                    $arr['plan_time'] = time()+60;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = time();
                    $arr['file'] = 'sub_house_village_third';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = 'sub_house_village_third_house_'.$id;
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    M('process_sub_plan')->data($arr)->add();
                    $this->success($result['msg'],U('import_village',array('page'=>$_GET['page'])));
                }else{
                    $this->error($result['msg']);
                }
            }
        } else {
            $detail = $database_house_village_user_vacancy->house_village_user_vacancy_detail($where);
            $num_info = M('House_village_vacancy_num')->where(['vacancy_id'=>$detail['detail']['pigcms_id']])->find();
            if($num_info){
                $detail['detail']['water_number'] = $num_info['water_number'];
                $detail['detail']['ele_number'] = $num_info['ele_number'];
                $detail['detail']['gas_number'] = $num_info['gas_number'];
                $detail['detail']['heat_water_number'] = $num_info['heat_water_number'];
 
            }
            if (!$a185_indoor) {
                $a185_indoor = array(
                    'device_sn' => '',
                    'status' => 1
                );
            }
            $this->assign('indoor', $a185_indoor);
            $this->assign('a185_num', $a185_device);
            if (!$detail) {
                $this->error('数据处理有误！');
            } else {
                $show_sky_cockpit_entrance=C('config.show_sky_cockpit_entrance');
                $show_sky_cockpit_entrance=$show_sky_cockpit_entrance ? intval($show_sky_cockpit_entrance):0;
                $this->assign('show_sky_cockpit_entrance',$show_sky_cockpit_entrance);
                if ($detail['detail']['contract_time_start']>1){
                    $detail['detail']['contract_time_start']=date('Y-m-d',$detail['detail']['contract_time_start']);
                    $detail['detail']['contract_time_end']=date('Y-m-d',$detail['detail']['contract_time_end']);
                }else{
                    $single = D('House_village_single')->where(['id'=>$detail['detail']['single_id']])->find();
                    if (!empty($single)){
                        if ($single['contract_time_end']>1){
                            $detail['detail']['contract_time_end']=date('Y-m-d',$single['contract_time_end']);
                            $detail['detail']['contract_time_start']=date('Y-m-d',$single['contract_time_start']);
                        }else{
                            $detail['detail']['contract_time_end']='';
                            $detail['detail']['contract_time_start'] = '';
                        }
                    }else{
                        $detail['detail']['contract_time_end']='';
                        $detail['detail']['contract_time_start'] = '';
                    }
                }

                $third_import_data_switch_judge = D('Config_customization')->getThirdImportDataSwitch();
                if ($third_import_data_switch_judge && isset($detail['detail']['vacancy_third_id']) && $detail['detail']['vacancy_third_id']) {
                    $info = D('Third_import_data')->getThirdImportRoomInfo($this->village_id, $id);
//                    if (!$info) {
//                        $result = invoke_v20_service('\app\community\model\service\third\ImportExcelService/getThirdImportRoomInfo',['village_id'=>$this->village_id,'room_id'=>$id]);
//                        $info = $result['data']['retval'];
//                    }
                    if (isset($info['showArr']) && $info['showArr']) {
                        $detail['detail']['showArr'] = $info['showArr'];
                    } else {
                        $detail['detail']['showArr'] = [];
                    }
                }
                
                if ($detail['status']) {
                    $this->assign('detail', $detail['detail']);
                } else {
                    $this->error('信息不存在！');
                }
                $this->display();
            }
        }
    }

    public function import_village_del()
    {
        //房间管理-删除 权限
        if (!in_array(40, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = $_GET['id'] + 0;
        if (!$id) {
            $this->error('传递参数有误！');
        }

        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $database_house_village_user_bind = D('House_village_user_bind');
        $where=array();
        $where['pigcms_id'] = $id;
        $where['village_id'] = $this->house_session['village_id'];
        $yz_info = $database_house_village_user_vacancy->where(array('pigcms_id' => $id))->find();
        $yz_bind_num = $database_house_village_user_bind->where(array('vacancy_id' => $id, 'type' => array('in', '0,3')))->count();
        //要多一个判断 是否存在这条绑定信息  如果不存在也可以删除

        if (($yz_info['uid'] || $yz_info['name'] || $yz_info['phone']) && $yz_bind_num > 0) {
            $this->error('已绑定业主，无法删除！');
        }

        $result = $database_house_village_user_vacancy->import_village_del($where);

        if ($result['status']) {
            $this->success($result['msg']);
        } else {
            $this->error($result['msg']);
        }

    }

    public function import_village_del_many()
    {
        //房间管理-删除 权限
        if (!in_array(40, $this->house_session['menus'])) {
            exit(json_encode(array('status' => 0, 'msg' => '对不起，您没有权限执行此操作')));
        }

        $yz_bind_num = D('House_village_user_bind')->where(array(
            'vacancy_id' => array('in', explode(',', $_POST['pigcms_id'])),
            'type' => array('in', '0,3'),
            'status' => array('in', '1,2')
        ))->count();

        //要多一个判断 是否存在这条绑定信息  如果不存在也可以删除
        if ($yz_bind_num > 0) {
            exit(json_encode(['status' => 0, 'msg' => '有房间已绑定业主，无法删除！请先删除业主。']));
        }
        $where=array();
        $where['pigcms_id'] = array('in', $_POST['pigcms_id']);
        $where['village_id'] = $this->house_session['village_id'];
        $result = D('House_village_user_vacancy')->import_village_del($where);
        if (!$result) {
            exit(json_encode(['status' => 0, 'msg' => '数据处理有误！']));
        } else {
            exit(json_encode($result));
        }

    }

    public function unit_del()
    {
        //单元列表-删除 权限
        if (!in_array(23, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $floor_id = $_GET['floor_id'] + 0;
        if (!$floor_id) {
            $this->error('传递参数有误！');
        }

        $database_house_village_floor = D('House_village_floor');
        $where['floor_id'] = $floor_id;
        $where['village_id'] = $this->house_session['village_id'];
        $where['parent_id'] = 0;
        $result = $database_house_village_floor->house_village_floor_del($where);

        if (!$result) {
            $this->error('数据处理有误！');
        } else {
            if ($result['status']) {
                $this->success($result['msg']);
            } else {
                $this->error($result['msg']);
            }
        }
    }


    public function unit_edit()
    {
        //单元列表-查看 权限
        if (!in_array(22, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $floor_id = $_GET['floor_id'] + 0;
        if (!$floor_id) {
            $this->error('传递参数有误！');
        }

        $database_house_village_floor = D('House_village_floor');
        $where['floor_id'] = $floor_id;
        $village_id = $this->village_id;
        $property_id = $this->house_session['property_id'];
        // 查询小区物业服务管家信息
        $house_services_housekeeper = M('House_services_housekeeper');
        $house_worker = M('House_worker');
        $where['village_id'] = $village_id;
        if (IS_POST) {
            //单元列表-编辑 权限
            if (!in_array(22, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $result = $database_house_village_floor->house_village_floor_edit($where, $_POST);
            if (!$result) {
                $this->error('数据处理有误！');
            } else {
                if ($result['status']) {
                    $housekeeper_id = I('housekeeper_id', 0, 'intval');
                    if ($housekeeper_id) {
                        $where_services_housekeeper = [
                            'housekeeper_id' => $housekeeper_id,
                        ];
                        $services_housekeeper = $house_services_housekeeper->where($where_services_housekeeper)->find();
                        if (!$services_housekeeper) {
                            $this->error('对应操作对象不存在！');
                        }
                    }
                    $data[] = '';
                    $data['village_id'] = $village_id;
                    $data['property_id'] = $property_id;
                    $data['single_id'] = intval($_POST['single_id']) ? intval($_POST['single_id']) : 0;
                    $data['floor_id'] = $floor_id;
                    if ($_POST['work_arr']) {
                        $data['work_arr'] = implode(',', $_POST['work_arr']);
                    }
                    if ($_POST['welcome_tip']) {
                        $data['welcome_tip'] = $_POST['welcome_tip'];
                    }
                    if ($_POST['qy_qrcode']) {
                        $data['qy_qrcode'] = $_POST['qy_qrcode'];
                    }
                    if (isset($_POST['template_type'])) {
                        $data['template_type'] = intval($_POST['template_type']);
                    }
                    if ($_POST['template_url']) {
                        $data['template_url'] = $_POST['template_url'];
                    }
                    if ($_POST['effect_img']) {
                        $data['effect_img'] = $_POST['effect_img'];
                    }
                    if ($housekeeper_id) {
                        $data['last_time'] = time();
                        $set = $house_services_housekeeper->where($where_services_housekeeper)->data($data)->save();
                    } else {
                        $data['add_time'] = time();
                        $set = $house_services_housekeeper->data($data)->add();
                    }
                    if (!$set) {
                        $this->success('单元管家更改或添加失败');
                        exit;
                    }


                    $this->success($result['msg'], U('index', array('single_id' => $_POST['single_id'])));
                } else {
                    $this->error($result['msg']);
                }
            }
        } else {
            $detail = $database_house_village_floor->house_village_floor_detail($where);
            if (!$detail) {
                $this->error('数据处理有误！');
            } else {
                $this->assign('detail', $detail['detail']);
            }
            $this->assign('single_id', $_GET['single_id']);
            $this->_get_floor_type_list();
            $third_where = [
                'type' => 'chinapopin_sichuan',
                'village_id' => $this->house_session['village_id'],
            ];
            $third_config = M('House_village_third_config')->where($third_where)->find();
            $this->assign('third_config', $third_config);


            $where_services_housekeeper = [
                'village_id' => $village_id,
                'floor_id' => $floor_id,
            ];
            $services_housekeeper = $house_services_housekeeper->where($where_services_housekeeper)->find();
            if ($services_housekeeper && $services_housekeeper['work_arr']) {
                $work_arr = explode(',', $services_housekeeper['work_arr']);
                if ($work_arr) {
                    $where_workers = [
                        'wid' => ['in', $work_arr],
                        'status' => 1
                    ];
                    $work_arr_info = $house_worker->where($where_workers)->field('wid,name')->select();
                    $this->assign('work_arr_info', $work_arr_info);
                }
            } else {
                $work_arr = [];
            }
            $this->assign('work_arr', $work_arr);
            $this->assign('services_housekeeper', $services_housekeeper);
            $qyhelp_url = $this->config['site_url'] . '/static/file/village/information/qyhelp.pdf';
            $this->assign('qyhelp_url', $qyhelp_url);

            $enterprise_wx_corpid = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->getField('corpid');
            // 查询该小区小所有工作人员
            if ($enterprise_wx_corpid) {
                $where_worker = [
                    'village_id' => $village_id,
                    'qy_status' => 1,
                    'qy_id' => ['neq', ''],
                    'status' => 1
                ];
            } else {
                $where_worker = [
                    'village_id' => $village_id,
                    'status' => 1
                ];
            }
            $worker_list = $house_worker->where($where_worker)->order('wid ASC')->select();
            if (!$worker_list) {
                $worker_list = [];
            }
            $new_charge_time=D('House_new_charge_time')->where(array('property_id'=>$property_id))->find();
            $is_new_effect_time=false;
            if (!empty($new_charge_time) && isset($new_charge_time['take_effect_time']) && $new_charge_time['take_effect_time']){
                $take_effect_time=$new_charge_time['take_effect_time'];
                $nowtime=time();
                if ($nowtime>$take_effect_time) {
                    // 设置了生效时间 并且 当前时间大于生效时间 返回true 其他返回false
                    $is_new_effect_time= true;
                }
            }
            $this->assign('is_new_effect_time', $is_new_effect_time);
            $this->assign('worker_list', $worker_list);
            $this->assign('enterprise_wx_corpid', $enterprise_wx_corpid);

            $this->display();
        }
    }

    public function unit_delete()
    {
        $id = $_GET['id'];
        $where = [
            'floor_id' => intval($_GET['floor_id']),
            'village_id' => $this->village_id,
        ];
        $pigcms_id=M('House_village_layer')->where(['village_id'=>$where['village_id'],'floor_id'=>$where['floor_id'],'status'=>['neq', 4]])->getField('id');
        if($pigcms_id){
            $this->error('该单元下存在楼层不可删除，请先删除楼层。');
        }
        if (M('House_village_floor')->where($where)->data(['status' => 4])->save()) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    //单元类型列表
    public function unittype_list()
    {
        //单元类型列表-查看 权限
        if (!in_array(24, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_floor_type = D('House_village_floor_type');

        $where['village_id'] = $_SESSION['house']['village_id'];
        $list = $database_house_village_floor_type->house_village_floor_type_page_list($where);

        if (!$list) {
            $this->error('数据处理有误！~~~');
        } else {
            $this->assign('list', $list['list']);
        }

        $this->display();
    }


    public function unittype_add()
    {
        //单元类型列表-添加 权限
        if (!in_array(25, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            $database_house_village_floor_type = D('House_village_floor_type');
            $result = $database_house_village_floor_type->house_village_floor_type_add($_POST);

            if ($result['status']) {
                $this->success($result['msg']);
            } else {
                $this->error($result['msg']);
            }
        } else {
            $this->display();
        }
    }


    public function unittype_edit()
    {
        //单元类型列表-查看 权限
        if (!in_array(24, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_floor_type = D('House_village_floor_type');
        $id = $_GET['id'] + 0;
        if (empty($id)) {
            $this->error('传递参数有误！~~~');
        }

        $where['id'] = $id;
        if (IS_POST) {
            //单元类型列表-编辑 权限
            if (!in_array(26, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $result = $database_house_village_floor_type->house_village_floor_type_edit($where, $_POST);
            if (!$result) {
                $this->error('数据处理有误！~~~~');
            } else {
                if ($result['status'] == 0) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg']);
                }
            }

        } else {
            $detail = $database_house_village_floor_type->house_village_floor_type_detail($where);

            if (!$detail) {
                $this->error('数据处理有误！~~~');
            } else {
                if ($detail['status'] == 0) {
                    $this->error('该信息不存在！');
                } else {
                    $this->assign('detail', $detail['detail']);
                }
            }
            $this->display();
        }
    }

    public function unittype_del()
    {
        //单元类型列表-删除 权限
        if (!in_array(27, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = $_GET['id'] + 0;

        $database_house_village_floor_type = D('House_village_floor_type');
        $where['id'] = $id;
        $where['village_id'] = $_SESSION['house']['village_id'];
        $result = $database_house_village_floor_type->village_floor_type_delete($where);

        if (!$result) {
            $this->error('数据处理有误！');
        } else {
            if ($result['status']) {
                $this->success($result['msg']);
            } else {
                $this->error($result['msg']);
            }
        }
    }


    public function pay_order()
    {
        //物业对账-查看 权限
        if (!in_array(32, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'order_name') {
                $condition['order_name'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'name') {
                $condition['name'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'usernum') {
                $condition['usernum'] = $_GET['keyword'];
            }else if($_GET['searchtype'] == 'order_id'){
                $condition['order_id'] = intval($_GET['keyword']);
            }
        }

        //       if(isset($_GET['searchstatus'])){
        //           $condition['is_pay_bill'] = $_GET['searchstatus'];
        //       }
        // if(($_GET['searchstatus']) == 0){
        // 	$condition['pay_type'] = 1;
        // }

        if (($_GET['searchstatus']) == 1) {
            $condition['pay_type'] = 0;
        }

        if (($_GET['searchstatus']) == 2) {
            $condition['pay_type'] = 1;
        }

        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['pay_time_str'] = $time_condition;
        }

        $village_id = $_SESSION['house']['village_id'];
        if ($village_id) {
            $condition['village_id'] = $village_id;
            $condition['paid'] = 1;
            $result = D('House_village_pay_order')->get_limit_list_page($condition, 20);

            $finshtotal = $total = $totalPayMoney = 0;
            $refund_total = 0;
            if ($result) {
                $house_village_pay_cashier_order=D('House_village_pay_cashier_order');
                $house_village_pay_order=D('House_village_pay_order');
                foreach ($result['order_list'] as $k => &$v) {
                    if ($v['order_status'] == 1) {
                        $total += $v['money'];                                //本页的总额
                    } else {
                        $refund_total += $v['money'];
                    }
                    $result['order_list'][$k]['paymoney']=$v['money'];
                    if($v['cashier_id']>0){
                        $whereTotalMoney = array('village_id'=>$village_id,'cashier_id'=>$v['cashier_id'],'paid'=>1);
                        $total_money = $house_village_pay_cashier_order->field(' SUM(`money` ) AS totalMoney ')->where($whereTotalMoney)->find();
                        $sum_money = $house_village_pay_order->field(' SUM(`money` ) AS sumMoney ')->where($whereTotalMoney)->find();
                        if(!empty($total_money)){
                            if ($sum_money==$v['money']){
                                $result['order_list'][$k]['paymoney']=$total_money['totalMoney'];
                            }else{
                                if ($sum_money['sumMoney']==$total_money['totalMoney']){
                                    $result['order_list'][$k]['paymoney']=$v['money'];
                                } else{
                                    $result['order_list'][$k]['paymoney']=round_number(($total_money['totalMoney']*($v['money']/$sum_money['sumMoney'])),2);
                                }  
                            }
                            
                        }
                    }
                    if ($v['order_status'] == 1) {
                        $totalPayMoney += $v['paymoney']; // 本业应缴总金额
                    }

                    $v['is_pay_bill'] && $v['is_own'] != 5 && $finshtotal += $v['money'];    //本页已对账的总额 查询线下支付方式
                    if ($v['cashier_id'] && $v['pay_type']) {
                        $condition_table = array(C('DB_PREFIX') . 'house_village_pay_cashier_order' => 'o', C('DB_PREFIX') . 'house_village_pay_type' => 't');
                        $condition_where = " `o`.`village_id` = `t`.`village_id`  AND `o`.`pay_type` = `t`.`id`  AND `o`.`cashier_id` =" . $v['cashier_id'];
                        $condition_field = '`t`.`name`';
                        $pay_type = D('')->field($condition_field)->table($condition_table)->where($condition_where)->find();
                        $v['pay_type_name'] = $pay_type['name'] ? $pay_type['name'] : '';
                    }
                    //查询线上支付方式
                    if ($v['pay_type'] == 0) {
                        if ($v['fid']) {
                            $business_id = $v['fid'];
                        } else {
                            $business_id = $v['cashier_id'] ? $v['cashier_id'] : $v['order_id'];
                        }
                        $online_pay_type = D('Plat_order')->field('pay_type,is_mobile_pay,paid')->where(array('business_type' => 'house_village_pay', 'business_id' => $business_id))->find();
                        $v['online_pay_type'] = D('Plat_order')->get_pay_name($online_pay_type['pay_type'], $online_pay_type['is_mobile_pay'], $online_pay_type['paid']);
                    }
                    //判断是否开发票
                    $order_id = M('House_village_detail_record')->where(['order_id' => $v['order_id']])->find();
                    if ($order_id)
                        $result['order_list'][$k]['is_kai'] = 1;
                    else
                        $result['order_list'][$k]['is_kai'] = 0;
                }
            }
            $this->assign('finshtotal', $finshtotal);
            $this->assign('total', $total);
            $this->assign('totalPayMoney', $totalPayMoney);
            $this->assign('refund_total', $refund_total);
            $this->assign('order_list', $result);
        }
        $this->assign('village_id', $village_id);
        $this->display();
    }

    public function pay_order_all()
    {
        //物业对账-查看全部数据 权限
        if (!in_array(33, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (!empty($_GET['keyword'])) {
            $condition['order_name'] = $_GET['keyword'];
        }

        if ($_GET['searchstatus'] == 1) {
            $condition['is_pay_bill'] = array('in', array(1, 2));
        } else if ($_GET['searchstatus'] == 2) {
            $condition['is_pay_bill'] = 0;
        }

        // if(isset($_GET['searchstatus'])){
        //     $condition['is_pay_bill'] = $_GET['searchstatus'];
        // }

        // if(($_GET['searchstatus']) == 0){
        //     $condition['pay_type'] = 1;
        // }

        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['pay_time_str'] = $time_condition;
        }

        $village_id = $_SESSION['house']['village_id'];

        if ($village_id) {
            $condition['village_id'] = $village_id;
            $condition['paid'] = 1;
            // $result = D('House_village_pay_order')->get_limit_list_page($condition,20);


            import('@.ORG.merchant_page');

            $count_order = D('House_village_pay_order')->where($condition)->count();
            $p = new Page($count_order, 20, 'page');


            $order_list = D('House_village_pay_order')->where($condition)->limit($p->firstRow . ',' . $p->listRows)->order('order_id desc')->select();

            $database_house_village_property_paylist = D('House_village_property_paylist');
            $pay_list = $database_house_village_property_paylist->where(array('village_id' => $condition['village_id']))->select();

            // 自定义缴费周期时间
            $custom_paylist = D('House_village_payment_paylist')->where(array('village_id' => $this->village_id))->select();

            foreach ($order_list as $Key => $order) {
                if (!empty($pay_list)) {
                    foreach ($pay_list as $pay_info) {
                        if ($order['order_id'] == $pay_info['order_id']) {
                            $order_list[$Key]['property_time_str'] = date('Y-m-d', $pay_info['start_time']) . '至' . date('Y-m-d', $pay_info['end_time']);
                        }
                    }
                }

                // 自定义缴费周期时间
                if (!empty($custom_paylist)) {
                    foreach ($custom_paylist as $custom_pay) {
                        if ($order['order_id'] == $custom_pay['order_id']) {
                            $order_list[$Key]['custom_time_str'] = date('Y-m-d', $custom_pay['start_time']) . '至' . date('Y-m-d', $custom_pay['end_time']);
                        }
                    }
                }

                //查询线下支付方式
                if ($order['cashier_id'] && $order['pay_type']) {
                    $condition_table = array(C('DB_PREFIX') . 'house_village_pay_cashier_order' => 'o', C('DB_PREFIX') . 'house_village_pay_type' => 't');
                    $condition_where = " `o`.`village_id` = `t`.`village_id`  AND `o`.`pay_type` = `t`.`id`  AND `o`.`cashier_id` =" . $order['cashier_id'];
                    $condition_field = '`t`.`name`';
                    $pay_type = D('')->field($condition_field)->table($condition_table)->where($condition_where)->find();
                    $order_list[$Key]['pay_type_name'] = $pay_type['name'] ? $pay_type['name'] : '';
                }
                //查询线上支付方式
                if ($order['pay_type'] == 0) {
                    if ($order['fid'])
                        $business_id = $order['fid'];
                    else
                        $business_id = $order['cashier_id'] ? $order['cashier_id'] : $order['order_id'];
                    $online_pay_type = D('Plat_order')->field('pay_type,is_mobile_pay,paid')->where(array('business_id' => $business_id))->find();
                    $order_list[$Key]['online_pay_type'] = D('Plat_order')->get_pay_name($online_pay_type['pay_type'], $online_pay_type['is_mobile_pay'], $online_pay_type['paid']);
                }
            }

            $condition['order_status'] = 1;
            $total1 = D('House_village_pay_order')->field(' SUM(`money` ) AS totalMoney ')->where($condition)->find();
            $condition['order_status'] = 2;
            $refund_total1 = D('House_village_pay_order')->field(' SUM(`money` ) AS totalMoney ')->where($condition)->find();
            $already = D('House_village_pay_order')->field(' SUM(`money` ) AS readyMoney ')->where($condition . " AND `is_pay_bill`=1 ")->find();

            $return['pagebar'] = $p->show();
            $return['order_list'] = $order_list;
            $return['totalMoney'] = $total1;
            $return['refund_total'] = $refund_total1;
            $return['readyMoney'] = $already;

            $result = $return;

            $finshtotal = $total = 0;
            $refund_total = 0;
            if ($result) {
                foreach ($result['order_list'] as $v) {
                    if (1 == $v['order_status']) {
                        $total += $v['money'];                              //本页的总额
                        $v['is_pay_bill'] && $finshtotal += $v['money'];    //本页已对账的总额
                    } else {
                        $refund_total += $v['money'];
                    }
                }
            }
            $this->assign('finshtotal', $finshtotal);
            $this->assign('total', $total);
            $this->assign('refund_total', $refund_total);
            $this->assign('order_list', $result);
        }
        $this->assign('village_id', $village_id);
        $this->display();
    }


    public function export()
    {
        //物业对账-导出 权限
        if (!in_array(34, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '社区账单';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'order_name') {
                $condition['order_name'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'name') {
                $condition['name'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'usernum') {
                $condition['usernum'] = $_GET['keyword'];
            }
        }

        // if(isset($_GET['searchstatus'])){
        //     $condition['is_pay_bill'] = $_GET['searchstatus'];
        // }


        if (($_GET['searchstatus']) == 1) {
            $condition['pay_type'] = 0;
        }

        if (($_GET['searchstatus']) == 2) {
            $condition['pay_type'] = 1;
        }

        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['pay_time_str'] = $time_condition;
            $where_condition['pay_time'] = array('between', array($period[0], $period[1]));
        }

        $village_id = $_SESSION['house']['village_id'];
        if ($village_id) {
            $now_village = D('House_village')->get_one($village_id);
            $where_condition['village_id'] = $condition['village_id'] = $village_id;
            $where_condition['paid'] = $condition['paid'] = 1;
            isset($condition['pay_type']) && $where_condition['pay_type'] = $condition['pay_type'];

            if ($condition['phone']) {
                $where_condition['phone'] = ['like', '%' . $condition['phone'] . '%'];
            }
            if ($condition['name']) {
                $where_condition['name'] = ['like', '%' . str_replace(' ', '', $condition['name']) . '%'];
            }
            if ($condition['usernum']) {
                $where_condition['usernum'] = ['like', '%' . $condition['usernum'] . '%'];
            }
            if ($condition['order_name']) {
                $where_condition['order_name'] = ['like', '%' . $condition['order_name'] . '%'];
            }
            // $where_condition['is_pay_bill'] = $condition['is_pay_bill']==1?2:0;
            // $where_condition['is_pay_bill'] = array('egt',1);
            $count = D('House_village_pay_order')->where($where_condition)->count();
            $length = ceil($count / 1000);
            $_GET['page'] = $_POST['page'] = 1;
            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);

                $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千订单');
                $objActSheet = $objExcel->getActiveSheet();
                $objActSheet->setCellValue('A1', '缴费项');
                $objActSheet->setCellValue('B1', '已缴金额');
                $objActSheet->setCellValue('C1', '支付时间');
                $objActSheet->setCellValue('D1', '支付方式');
                $objActSheet->setCellValue('E1', '积分抵扣金额');
                $objActSheet->setCellValue('F1', '积分使用数量');
                $objActSheet->setCellValue('G1', '业主名');
                $objActSheet->setCellValue('H1', '联系方式');
                $objActSheet->setCellValue('I1', '住址');
                $objActSheet->setCellValue('J1', '编号');
                $objActSheet->setCellValue('K1', '物业服务周期');
                $objActSheet->setCellValue('L1', '自定义内容/赠送物业服务时间');
                $objActSheet->setCellValue('M1', '计费开始时间');
                $objActSheet->setCellValue('N1', '计费截止时间');
                $objActSheet->setCellValue('O1', '自定义缴费周期');
                $objActSheet->setCellValue('P1', '订单编号');
                $objActSheet->setCellValue('Q1', '订单状态');
                $objActSheet->setCellValue('R1', '备注');
                // $objActSheet->setCellValue('L1', '对账状态');

                // $condition['is_pay_bill'] = 1;
                $result = D('House_village_pay_order')->get_limit_list_page($condition, 0, true,'',$i*1000);
                if (!empty($result['order_list'])) {
                    $index = 2;
                    foreach ($result['order_list'] as $value) {
                        //查询线上支付方式
                        if ($value['pay_type'] == 0) {
                            if ($value['fid']) {
                                $business_id = $value['fid'];
                            } else {
                                $business_id = $value['cashier_id'] ? $value['cashier_id'] : $value['order_id'];
                            }
                            $online_pay_type = D('Plat_order')->field('pay_type,is_mobile_pay,paid')->where(array('business_type' => 'house_village_pay', 'business_id' => $business_id))->find();
                            $value['online_pay_type'] = D('Plat_order')->get_pay_name($online_pay_type['pay_type'], $online_pay_type['is_mobile_pay'], $online_pay_type['paid']);
                        }
                        if ($value['pay_type'] == 0 && !$value['cashier_id']) {
                            $value['pay_name'] = '在线支付' . "({$value['online_pay_type']})";
                        } elseif ($value['pay_type'] == 0 && $value['cashier_id'] > 0) {
                            $value['pay_name'] = '在线支付' . "(收银台扫码)";
                        }

                        if ($value['order_status'] == 1)
                            $value['order_status'] = '正常';
                        else
                            $value['order_status'] = '已退款';
                        $objActSheet->setCellValueExplicit('A' . $index, $value['order_name']);
                        $objActSheet->setCellValueExplicit('B' . $index, getFormatNumber($value['money']));
                        $objActSheet->setCellValueExplicit('C' . $index, date('Y-m-d H:i:s', $value['pay_time']));

                        $objActSheet->setCellValueExplicit('D' . $index, $value['pay_name']);
                        $objActSheet->setCellValueExplicit('E' . $index, $value['score_deducte']);
                        $objActSheet->setCellValueExplicit('F' . $index, $value['score_used_count']);

                        $objActSheet->setCellValueExplicit('G' . $index, $value['username']);
                        $objActSheet->setCellValueExplicit('H' . $index, $value['phone']);
                        $objActSheet->setCellValueExplicit('I' . $index, $value['address']);
                        $objActSheet->setCellValueExplicit('J' . $index, $value['usernum']);
                        if ($value['property_month_num']) {
                            $objActSheet->setCellValueExplicit('K' . $index, $value['property_month_num'] . '个月');
                        } else {
                            $objActSheet->setCellValueExplicit('K' . $index, '暂无');
                        }

                        if (!empty($value["presented_property_month_num"]) and ($value["diy_type"] == 0)) {
                            $objActSheet->setCellValueExplicit('L' . $index, $value['presented_property_month_num'] . '个月');
                        } elseif ($value["diy_type"] == 1) {
                            $objActSheet->setCellValueExplicit('L' . $index, $value['diy_content']);
                        } else {
                            $objActSheet->setCellValueExplicit('L' . $index, '暂无');
                        }

                        if ($value['service_start_time']) {
                            $objActSheet->setCellValueExplicit('M' . $index, $value['service_start_time']);
                        } else {
                            $objActSheet->setCellValueExplicit('M' . $index, '暂无');
                        }
                        if ($value['service_end_time']) {
                            $objActSheet->setCellValueExplicit('N' . $index, $value['service_end_time']);
                        } else {
                            $objActSheet->setCellValueExplicit('N' . $index, '暂无');
                        }

                        if ($value['order_type'] == 'custom_payment') {
                            $objActSheet->setCellValueExplicit('O' . $index, $value['payment_paid_cycle'] . '/周期');
                        } else {
                            $objActSheet->setCellValueExplicit('O' . $index, '暂无');
                        }
                        $objActSheet->setCellValueExplicit('P' . $index, $value['order_id']);
                        $objActSheet->setCellValueExplicit('Q' . $index, $value['order_status']);
                        $objActSheet->setCellValueExplicit('R' . $index, $value['remarks']);
                        // if($value['is_pay_bill'] == 0){
                        //     $objActSheet->setCellValueExplicit('L' . $index, '未对账');
                        // }else{
                        //     $objActSheet->setCellValueExplicit('L' . $index, '已对账');
                        // }
                        $index++;
                    }
                }
                sleep(2);
            }
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
        header('Content-Disposition:attachment;filename="' . $now_village['village_name'] . '_' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    public function merchant_order()
    {
        //物业商家流水-查看 权限
        if (!in_array(36, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $type = isset($_GET['type']) && $_GET['type'] ? trim($_GET['type']):'group';
        $village_id = $_SESSION['house']['village_id'];
        $time_condition = '';
        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (o.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }

        // 社区业务返点
        $village_info = D('House_village')->where(['village_id' => $village_id])->field('property_id,rebate_percent')->find();
        //物业返点
        $property_info=D('House_property')->where(['id' => $village_info['property_id']])->field('rebate_percent')->find();
        $cfrom='平台返点';
        if ($village_info['rebate_percent'] > 0) {
            $rebate_percent = '小区返点（返点比例'.$village_info['rebate_percent'].'%）';
            $cfrom='小区返点';
            $rebate=$village_info['rebate_percent'];
        } elseif ($property_info['rebate_percent'] > 0) {
            $cfrom='物业返点';
            $rebate_percent = '物业返点（返点比例'.$property_info['rebate_percent'].'%）';
            $rebate=$property_info['rebate_percent'];
        } else {
            $rebate_percent = '平台返点（返点比例'.C('config.house_money_percent').'%）';
            $rebate=C('config.house_money_percent');
        }
        $rebate=$rebate>0 ? $rebate:0;
        $this->assign('rebate_percent', ['rebate_str'=>$rebate_percent,'rebate'=>$rebate,'cfrom'=>$cfrom]);

        $order_list = D('House_village_group')->get_order_list($type, $village_id, $time_condition);
        $total_fact_commission=0;
        if($rebate>0 && isset($order_list['total_fact']) && ($order_list['total_fact']>0)){
            $total_fact_commission=($rebate*$order_list['total_fact'])/100;
            $total_fact_commission=round($total_fact_commission,2);
        }
        $total_fact_commission=$total_fact_commission>0 ? $total_fact_commission:0;
        $this->assign('total_fact_commission',$total_fact_commission);
        $this->assign($order_list);
        $this->display();
    }
    //导出
    public function merchant_order_export(){
        $type = isset($_GET['type']) && $_GET['type'] ? trim($_GET['type']):'group';
        $village_id = $_SESSION['house']['village_id'];
        $time_condition = '';
        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (o.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        // 社区业务返点
        $village_info = D('House_village')->where(['village_id' => $village_id])->field('property_id,rebate_percent')->find();
        //物业返点
        $property_info=D('House_property')->where(['id' => $village_info['property_id']])->field('rebate_percent')->find();
        $cfrom='平台返点';
        if ($village_info['rebate_percent'] > 0) {
            $rebate_percent = '小区返点（返点比例'.$village_info['rebate_percent'].'%）';
            $cfrom='小区返点';
            $rebate=$village_info['rebate_percent'];
        } elseif ($property_info['rebate_percent'] > 0) {
            $cfrom='物业返点';
            $rebate_percent = '物业返点（返点比例'.$property_info['rebate_percent'].'%）';
            $rebate=$property_info['rebate_percent'];
        } else {
            $rebate_percent = '平台返点（返点比例'.C('config.house_money_percent').'%）';
            $rebate=C('config.house_money_percent');
        }
        $rebate=$rebate>0 ? $rebate:0;
        $order_list = D('House_village_group')->get_order_export_list($type, $village_id, $time_condition);

        if (empty($order_list) || empty($order_list['order_list'])) {
            $this->error('无数据导出！');
        }
        $typeArr=array('group'=>'团购流水','meal'=>'餐饮流水','shop'=>'外卖流水','appoint'=>'预约流水');
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = $this->village['village_name'] . $typeArr[$type];
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $objExcel->setActiveSheetIndex(0);
        $order_list_count=count($order_list['order_list']);
        $objExcel->getActiveSheet()->setTitle('共' .$order_list_count . '条数据');
        $objActSheet = $objExcel->getActiveSheet();

        $objActSheet->getColumnDimension('A')->setWidth(35);
        $objActSheet->getColumnDimension('B')->setWidth(35);
        $objActSheet->getColumnDimension('C')->setWidth(50);
        $objActSheet->getColumnDimension('D')->setWidth(20);
        $objActSheet->getColumnDimension('E')->setWidth(20);
        $objActSheet->getColumnDimension('F')->setWidth(20);
        $objActSheet->getColumnDimension('G')->setWidth(20);
        $objActSheet->getColumnDimension('H')->setWidth(35);
        
        $objActSheet->setCellValue('A1', '商家名称');
        $objActSheet->setCellValue('B1', '订单ID');
        $objActSheet->setCellValue('C1', '订单描述');
        $objActSheet->setCellValue('D1', '联系方式');
        $objActSheet->setCellValue('E1', '订单总价');
        $objActSheet->setCellValue('F1', '实际支付');
        $objActSheet->setCellValue('G1', '佣金');
        $objActSheet->setCellValue('H1', '支付时间');
        $keyIndex=2;
        if($order_list && $order_list['order_list']){
            foreach ($order_list['order_list'] as $vv){
                $objActSheet->setCellValueExplicit('A'.$keyIndex, $vv['mer_name']);
                $objActSheet->setCellValueExplicit('B'.$keyIndex, $vv['order_id']);
                $objActSheet->setCellValueExplicit('C'.$keyIndex, $vv['des']);
                $objActSheet->setCellValueExplicit('D'.$keyIndex, $vv['phone']);
                $total_money=round($vv['total_money'],2);
                $total_money=strval($total_money);
                $objActSheet->setCellValueExplicit('E'.$keyIndex, $total_money,PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $pay_in_fact=round($vv['pay_in_fact'],2);
                $objActSheet->setCellValueExplicit('F'.$keyIndex, $pay_in_fact,PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $commission=0;
                if($rebate>0){
                    $commission=($vv['pay_in_fact']*$rebate)/100;
                    $commission=round($commission,2);
                    if($commission<=0){
                        $commission=0;
                    }
                }
                $objActSheet->setCellValueExplicit('G'.$keyIndex, $commission,PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $pay_time=$vv['pay_time']>100 ? date('Y-m-d H:i:s',$vv['pay_time']):'';
                $objActSheet->setCellValueExplicit('H'.$keyIndex, $pay_time);
                $keyIndex++;
            }
        }
        sleep(1);
        //输出
        /*
       $objWriter = new PHPExcel_Writer_Excel5($objExcel);
       ob_end_clean();
       header("Pragma: public");
       header("Expires: 0");
       header("Content-type:text/html;charset=utf-8");
       header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
       header("Content-Type:application/force-download");
       header("Content-Type:application/vnd.ms-execl");
       header("Content-Type:application/octet-stream");
       header("Content-Type:application/download");
       header('Content-Disposition:attachment;filename='.urlencode($title).'_' . date("Y-m-d h:i:sa", time()) . '.xls');
       header("Content-Transfer-Encoding:binary");
       $objWriter->save('php://output');
       */
        $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $filename = $title.date("Y-m-d") . '-' . time() . '.xls';
        $objWriter->save('./runtime/' . $filename);
        $url=C('config.site_url').'/runtime/'.$filename;
        $retArr= array('error'=>0,'url'=>$url);
        echo json_encode($retArr,JSON_UNESCAPED_UNICODE);
        exit();
    }
    //缴费优惠列表
    public function preferential_list()
    {
        //缴费优惠列表-查看 权限
        if (!in_array(28, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_property = D('House_village_property');

        $where['village_id'] = $_SESSION['house']['village_id'];
        $list = $database_house_village_property->house_village_proerty_page_list($where, true, 'id desc', 20);

        if (!$list) {
            $this->error('数据处理有误！');
        } else {
            $this->assign('list', $list['list']);
        }

        $this->display();
    }

    public function preferential_add()
    {
        //缴费优惠列表-添加 权限
        if (!in_array(29, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            $database_house_village_property = D('House_village_property');

            $_POST['village_id'] = $_SESSION['house']['village_id'];
            $result = $database_house_village_property->house_village_property_add($_POST);

            if (!$result) {
                $this->error('数据处理有误！');
            } else {
                if ($result['status']) {
                    $this->success($result['msg']);
                } else {
                    $this->error($result['msg']);
                }
            }
        } else {
            $this->display();
        }
    }

    public function preferential_del()
    {
        //缴费优惠列表-删除 权限
        if (!in_array(31, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = $_GET['id'] + 0;
        if (!$id) {
            $this->error('传递参数有误！');
        }

        $database_house_village_property = D('House_village_property');
        $where['id'] = $id;
        $result = $database_house_village_property->house_village_property_del($where);

        if (!$result) {
            $this->error('数据处理有误！');
        } else {
            if ($result['status']) {
                $this->success($result['msg']);
            } else {
                $this->error($result['msg']);
            }
        }
    }

    public function preferential_edit()
    {
        //缴费优惠列表-查看 权限
        if (!in_array(28, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = $_GET['id'] + 0;
        if (!$id) {
            $this->error('传递参数有误！');
        }

        $database_house_village_property = D('House_village_property');
        $where['id'] = $id;

        if (IS_POST) {
            //缴费优惠列表-编辑 权限
            if (!in_array(30, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $result = $database_house_village_property->house_village_property_edit($where, $_POST);

            if (!$result) {
                $this->error('数据处理有误！');
            } else {
                if ($result['status']) {
                    $this->success($result['msg']);
                } else {
                    $this->error($result['msg']);
                }
            }
        } else {
            $detail = $database_house_village_property->house_village_property_detail($where, true);

            if (!$detail) {
                $this->error('数据处理有误！');
            } else {
                $this->assign('detail', $detail['detail']);
            }
            $this->display();
        }
    }


    private function _get_floor_type_list()
    {
        $database_house_village_floor_type = D('House_village_floor_type');
        $house_village_floor_type_condition['status'] = 1;
        $house_village_floor_type_condition['village_id'] = $_SESSION['house']['village_id'];
        $house_village_floor_type_num = $database_house_village_floor_type->where($house_village_floor_type_condition)->count();

//        if($house_village_floor_type_num <= 0){
//            $this->error('请先添加单元类型！',U('unittype_add'));
//        }

        $house_village_floor_type_list = $database_house_village_floor_type->house_village_floor_type_page_list($house_village_floor_type_condition, true, 'id desc', 9999);
        $this->assign('house_village_floor_type_list', $house_village_floor_type_list['list']);
    }

    private function _get_property_list()
    {
        $database_house_village_property = D('House_village_property');
        $house_village_property_condition['status'] = 1;
        $house_village_property_condition['village_id'] = $_SESSION['house']['village_id'];

        $house_village_property_list = $database_house_village_property->house_village_proerty_page_list($house_village_property_condition, true, 'id desc', 9999);
        $this->assign('house_village_property_list', $house_village_property_list['list']);
    }

    //缴费提醒
    public function payment_reminder()
    {
        //物业对账-查看 权限
        if (!in_array(32, $this->house_session['menus'])) {
            exit(json_encode(array('error' => 0, 'msg' => '您没有权限查看缴费信息')));
        }
        $sum = M('House_village')->where(array('village_id' => $this->village_id))->getField('payment_reminder');
        if ($sum > 0) {
            M('House_village')->where(array('village_id' => $this->village_id))->setField('payment_reminder', 0);
            exit(json_encode(array('error' => 1, 'msg' => '您有 (' . $sum . ') 条缴费提醒！')));
        } else {
            exit(json_encode(array('error' => 0, 'msg' => '暂无缴费信息')));
        }
    }

    // 上传图片
    public function ajax_upload_pic()
    {
        if ($_FILES['imgFile']['error'] != 4) {
            $upload_dir = './upload/house/village/' . date('Ymd') . '/' . date('H') . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = $this->config['group_pic_size'] * 1024 * 1024;
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
            $upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'application/octet-stream');
            $upload->savePath = $upload_dir;
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                $title = $uploadList[0]['savename'];
                $url = $upload_dir . $title;
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            } else {
                exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
        }
    }

    /**
     * [parking_management 车位管理]
     * @return [type] [description]
     */
    public function parking_management()
    {
        if (IS_POST) {
            //车位管理-绑定用户 权限
            if (!in_array(253, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $position_bind_user_ids = array_unique(json_decode($_POST['position_bind_user_ids']));//需要绑定的用户ID

            $position_id = trim($_POST['position_id']);//车位ID
            if (!$position_bind_user_ids || !$position_id) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '数据获取错误!'));
            }
            if ($position_id) {
                $where['position_id'] = $position_id;
                $where['village_id'] = $this->village_id;
            }
            $field = 'user_id';
            $result = D('House_village_bind_position')->get_position_bind_list($position_bind_user_ids, $field, $where, $position_id);
            if ($result) {
                if ($result['code'] == 1) { // 发送微信通知
                    // 获取车位缴费项
                    $payment_list = D('House_village_payment_standard_bind')->where($where)->select();
                    $unpaid = false;
                    foreach ($payment_list as $key => $value) {
                        if ($value['paid_cycle'] < $value['cycle_sum']) {
                            $unpaid = true;
                        }
                    }
                    if ($unpaid) {
                        $bind_condition['pigcms_id'] = implode(',', $position_bind_user_ids);
                        $bind_condition['village_id'] = $this->village_id;
                        $user_bind_list = D('House_village_user_bind')->get_cashier_unpaid_list($bind_condition, 0, 0);
                        if ($user_bind_list['list']) {
                            D('House_village_user_bind')->send_weixin_pay($this->village_id, $user_bind_list['list']);
                        }
                    }

                }
                $this->ajaxReturn($result);
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => '数据获取错误!'));
            }

        } else {
            //车位管理-查看 权限
            if (!in_array(45, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            //todo 【新版收费-隐藏批量添加缴费项】
            $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
            if ($_GET['position_num'] && $_GET['position_num'] !== '') {//车位编号
                $where['position_num'] = trim($_GET['position_num']);
            }

            if ($_GET['position_status'] && $_GET['position_status'] !== '') {//租售状态
                $where['position_status'] = trim($_GET['position_status']);
            }
            if ($_GET['car_status'] && $_GET['car_status'] !== '') {//车位状态
                $where['car_status'] = trim($_GET['car_status']);
            }

            if ($_GET['garage']) {//车库
                $where['garage_id'] = trim($_GET['garage']);
            }

            if ($is_new_charge) {
                $village_park_config['park_versions'] = 2;
                $where['is_temporary'] = 9999;
            }
            $where['village_id'] = $this->village_id;
            $info_list = D('House_village_parking_position')->get_parking_list($where);

            // 查询车库
            $garage_list = D('House_village_parking_garage')->get_garage_list('garage_id,garage_num', array('village_id' => $this->village_id));
            $this->assign('garage_list', $garage_list);
            $this->assign('info_list', $info_list);
            $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();



            $this->assign('village_park_config', $village_park_config);
        }
        $this->display();
    }

    // 获得车位列表
    public function ajax_get_parking_list()
    {
        $where['village_id'] = $this->village_id;
        if ($_POST['position_num'] && $_POST['position_num'] !== '') {//车位编号
            $where['position_num'] = trim($_POST['position_num']);
        }
        if ($_POST['garage_id'] && $_POST['garage_id'] !== '') {//车位编号
            $where['garage_id'] = trim($_POST['garage_id']);
        }
        $info_list = D('House_village_parking_position')->get_parking_list($where, 0);
        exit(json_encode($info_list['info_list']));
    }

    /**
     * [parking_detail 车位详情]
     * @return [type] [description]
     */
    public function parking_detail()
    {
        //绑定用户-查看 权限
        if (!in_array(49, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if ($_GET['position_id'] && $_GET['position_id'] !== '') {//车位id
            $where['position_id'] = trim($_GET['position_id']);
        }
        $where['village_id'] = $this->village_id;
        // $info_list = D('House_village_parking_position')->get_parking_one($where);//停车位信息
        $bind_list = D('House_village_bind_position')->get_position_bind_select($where);//车位住户绑定关系
        $village_id = $this->village_id;
        $field = 'a.pigcms_id,a.name,a.phone,a.room_addrss,b.floor_name,b.floor_layer';
        $join = 'join ' . C('DB_PREFIX') . 'house_village_floor as b on a.floor_id=b.floor_id';
        foreach ($bind_list as $key => $value) {
            $where = array('a.pigcms_id' => $value['user_id'], 'a.village_id' => $village_id);
            $data_list[$key] = D('House_village_user_bind')->get_user_bind_info($field, $join, $where);
//            $data_list[$key]['position_id'] = $info_list['position_id'];
            $data_list[$key]['bind_id'] = $value['bind_id'];
        }
        // $this->assign('info_list',$info_list);
        $this->assign('data_list', $data_list);
        $this->display();
    }

    /**
     * [parking_edit 车位管理修改页面]
     * @return [type] [description]
     */
    public function parking_edit()
    {
        //todo 【新版收费-隐藏车位模式】
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        if (IS_POST) {
            //车位管理-编辑 权限
            if (!in_array(47, $this->house_session['menus'])) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '对不起，您没有权限执行此操作'));
            }
            $arr['position_note'] = htmlspecialchars(trim($_POST['position_note']));//车主备注
            $arr['position_id'] = htmlspecialchars(intval(trim($_POST['position_id'])));//主键
            $arr['position_area'] = htmlspecialchars(trim($_POST['position_area']));//车位面积
            $arr['position_num'] = htmlspecialchars(trim($_POST['position_num']));//车位号
            if (preg_match('/[\x7f-\xff]/', $arr['position_num'])) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '对不起，车位号不能使用中文'));
            }
            // $arr['position_status'] = htmlspecialchars(trim($_POST['position_status']));//车位状态
            // $arr['position_type'] = htmlspecialchars(trim($_POST['position_type']));//车位类型
            $arr['garage_id'] = htmlspecialchars(trim($_POST['garage_id']));//车库ID
            $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
            if ($house_village_park_config['is_park_month_type'] == 1 && $house_village_park_config['park_versions'] == 2) {

                $arr['position_pattern'] = $_POST['position_pattern'];
                $arr['month_charge_type'] = $_POST['month_charge_type'];
//                $arr['park_num'] = $_POST['park_num'];
            }
            //验证车位是否存在
            $where = array();
            $where['garage_id'] = $arr['garage_id'];
            $where['position_num'] = $arr['position_num'];
            $where['position_id'] = array('neq', $arr['position_id']);
            $where['village_id'] = $this->village_id;
            $is_exist = D('House_village_parking_position')->get_parking_one($where);
            if ($is_exist) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '该车位号已存在!'));
            }
            $result = D('House_village_parking_position')->parking_position_save($arr);
            if ($result !== false) {
                if ($_POST['user_id']) {
                    $user_id = $_POST['user_id'];
                    $where_data['village_id'] = $this->village_id;
                    $where_data['position_id'] = $_POST['position_id'];
                    $house_village_bind_position = D('House_village_bind_position')->where($where_data)->find();
                    if ($house_village_bind_position) {
                        D('House_village_bind_position')->where($where_data)->data(array('user_id' => $_POST['user_id']))->save();
                    } else {
                        $this->position_add($_POST['position_id'], $_POST['user_id']);
                    }
                    M('House_village_parking_position')->where(['position_id' => $_POST['position_id']])->setField('position_status', 2);
                } else {
                    $where_data['village_id'] = $this->village_id;
                    $where_data['position_id'] = $_POST['position_id'];
                    $house_village_bind_position = D('House_village_bind_position')->where($where_data)->find();
                    if ($house_village_bind_position['user_id']) {
                        $user_id = $house_village_bind_position['user_id'];
                    }
                }
                $third_import_data_switch_judge = D('Config_customization')->getThirdImportDataSwitch();
                if ($third_import_data_switch_judge && isset($user_id) && $user_id) {
                    $userBind = M('House_village_user_bind')->where(['pigcms_id' => $user_id])->field('pigcms_id, vacancy_id, name, phone')->find();
                    if (!empty($userBind)) {
                        $whereOrderInfo = [
                            'village_id'  => $this->village_id,
                            'room_id'     => 0,
                            'position_id' => $_POST['position_id'],
                        ];
                        $db_house_new_pay_order = M('House_new_pay_order');
                        $orderList = $db_house_new_pay_order->where($whereOrderInfo)->field('order_id, pigcms_id, name, phone')->select();
                        $saveOrder1 = [
                            'room_id'   => $userBind['vacancy_id'],
                        ];
                        foreach ($orderList as $order) {
                            $saveOrder = $saveOrder1;
                            if (!$order['pigcms_id']) {
                                $saveOrder['pigcms_id'] = $user_id;
                            }
                            if (!$order['name'] && $userBind['name']) {
                                $saveOrder['name'] = $userBind['name'];
                            }
                            if (!$order['phone'] && $userBind['phone']) {
                                $saveOrder['phone'] = $userBind['phone'];
                            }
                            $db_house_new_pay_order->where(['order_id' => $order['order_id']])->save($saveOrder);
                        }
                        $db_house_new_pay_order_summary = M('House_new_pay_order_summary');
                        $whereSummary = [
                            'village_id'  => $this->village_id,
                            'room_id'     => 0,
                            'position_id' => $_POST['position_id'],
                        ];
                        $saveSummary = [
                            'room_id'   => $userBind['vacancy_id'],
                            'pigcms_id' => $user_id,
                        ];
                        $db_house_new_pay_order_summary->where($whereSummary)->save($saveSummary);
                    }
                }
                
                $this->ajaxReturn(array('code' => 1, 'msg' => '修改车位信息成功!'));
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => '修改车位信息失败!'));
            }
        } else {
            //车位管理-查看 权限
            if (!in_array(45, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $position_id = intval(trim($_GET['position_id']));
            if (!$position_id && $position_id = '') {
                $this->error('错误的用户信息');
            }
            $where['village_id'] = $this->village_id;
            $garage_list = D('House_village_parking_garage')->get_garage_list('', $where);
            $arr['village_id'] = $this->village_id;
            $arr['position_id'] = $position_id;
            $position_list = D('House_village_parking_position')->get_parking_one($arr);
            if ($position_list) {
                $where_data['village_id'] = $this->village_id;
                $where_data['position_id'] = $position_id;
                $house_village_bind_position = D('House_village_bind_position')->where($where_data)->find();
                if ($house_village_bind_position['user_id']) {
                    $user_bind = D('House_village_user_bind')
                        ->where(['pigcms_id' => $house_village_bind_position['user_id']])
                        ->field('pigcms_id,name,phone')->find();
                    $this->assign('user_bind', $user_bind);
                }
            }


            $this->assign('garage_list', $garage_list);
            $this->assign('position_list', $position_list);

            $payment_list = D('')->table(array(C('DB_PREFIX') . 'house_village_payment_standard_bind' => 'psb', C('DB_PREFIX') . 'house_village_payment_standard' => 'ps', C('DB_PREFIX') . 'house_village_payment' => 'p'))->where("psb.position_id= '" . $position_id . "' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();
            $cycle_type = array(
                'Y' => '年',
                'M' => '月',
                'D' => '日',
            );
            $this->assign('cycle_type', $cycle_type);
            $this->assign('payment_list', $payment_list);
            $dataList = M('House_village_park_month')->where(array('village_id' => $this->village_id, 'park_cycle_type' => 1))->select();
            $this->assign('dataList', $dataList);
            $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
            $this->assign('house_village_park_config', $house_village_park_config);
            $this->assign('new_charge_time', $is_new_charge);
        }
        $this->display();
    }

    /**
     * [parking_position_addall 批量录入车位信息]
     * @return [type] [description]
     */
    public function parking_position_addall()
    {
        //车位管理-添加 权限
        if (!in_array(46, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $data['start_num'] = htmlspecialchars(intval(trim($_POST['start_num'])));//开始编号
            $data['end_num'] = htmlspecialchars(intval(trim($_POST['end_num'])));//结束编号
            $data['garage_id'] = htmlspecialchars(trim($_POST['garage_id']));
            // $data['position_type'] = htmlspecialchars(trim($_POST['position_type']));
            $data['position_status'] = htmlspecialchars(trim($_POST['position_status']));
            $data['position_area'] = htmlspecialchars(trim($_POST['position_area']));
            $data['position_note'] = htmlspecialchars(trim($_POST['position_note']));
            // $data['prefix'] = htmlspecialchars(trim($_POST['prefix'])) ;

            if ($data['start_num'] >= $data['end_num']) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '请输入有效编号'));
            }
            $str = '';
            for ($i = $data['start_num']; $i <= $data['end_num']; $i++) {
                // $str .= $data['prefix'].$i.',';
                $str .= $i . ',';
            }
            $num = explode(',', substr($str, 0, -1));
            foreach ($num as $key => $value) {
                // $arr[$key]['position_type']=$data['position_type'];
                $arr[$key]['garage_id'] = $data['garage_id'];
                $arr[$key]['position_num'] = $value;
                $arr[$key]['position_status'] = $data['position_status'];
                $arr[$key]['position_area'] = $data['position_area'];
                $arr[$key]['position_note'] = $data['position_note'];
                $arr[$key]['village_id'] = $this->village_id;
            }
            $result = D('House_village_parking_position')->parking_position_addAll($arr);
            if ($result !== false) {
                $this->ajaxReturn(array('code' => 1, 'msg' => '批量录入车位信息系成功!'));
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => '批量录入车位信息系失败!'));
            }
        } else {
            $where['village_id'] = $this->village_id;
            $garage_list = D('House_village_parking_garage')->get_garage_list('', $where);
            $this->assign('garage_list', $garage_list);
        }
        $this->display();
    }

    public function getParkingApi($data, $url)
    {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $token = $this->getToken();
        $headersArr = [];
        $headersArr[] = "token:" . $token;
        $res = $this->http->curlPostOwnWithHeader($url, http_build_query($data), $headersArr);
        $res = json_decode($res, true);
        $dataList = [];
        if ($res['code'] == 200) {
            $dataList = $res['data'];
        }
        // print_r($dataList);exit;
        return $dataList;
    }

    public function editParkingApi($data, $url)
    {
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $token = $this->getToken();
        $headersArr = [];
        $headersArr[] = "token:" . $token;
        $res = $this->http->curlPostOwnWithHeader($url, http_build_query($data), $headersArr);
        $res = json_decode($res, true);

        return $res;
    }

    /**
     * [parking_position_add 单个车位信息添加]
     * @return [type] [description]
     */
    public function parking_position_add()
    {
        //车位管理-添加 权限
        if (!in_array(46, $this->house_session['menus'])) {
            $this->ajaxReturn(array('code' => 2, 'msg' => '对不起，您没有权限执行此操作!'));
        }

        //todo 【新版收费-隐藏车位模式】
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        if (IS_POST) {
//            var_dump($_POST);die;parking_position_add
            // $data['prefix'] = htmlspecialchars(trim($_POST['prefix']));
            $data['garage_id'] = htmlspecialchars(intval(trim($_POST['garage_id'])));
            // $data['position_num'] =  $data['prefix'].htmlspecialchars(trim($_POST['position_num']));
            $data['position_num'] = htmlspecialchars(trim($_POST['position_num']));

            if (preg_match('/[\x7f-\xff]/', $data['position_num'])) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '对不起，车位号不能使用中文!'));
            }

            // $data['position_type'] = htmlspecialchars(trim($_POST['position_type']));
            $data['position_area'] = htmlspecialchars(trim($_POST['position_area']));
            // $data['position_status'] = htmlspecialchars(trim($_POST['position_status']));
            $data['position_note'] = htmlspecialchars(trim($_POST['position_note']));
            $data['village_id'] = $this->village_id;
            if ($is_new_charge == 1) {
                $_POST['position_pattern'] = 1;
            }
            $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
            if ($house_village_park_config['is_park_month_type'] == 1 && $house_village_park_config['park_versions'] == 2) {
                $data['position_pattern'] = $_POST['position_pattern'];
                $data['month_charge_type'] = $_POST['month_charge_type'];
//                $data['park_num'] = $_POST['park_num'];
            }

            //验证车位是否存在
            $where = array();
            $where['garage_id'] = $data['garage_id'];
            $where['position_num'] = $data['position_num'];
            $where['village_id'] = $this->village_id;
            $is_exist = D('House_village_parking_position')->get_parking_one($where);
            if ($is_exist) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '该车位号已存在!'));
            }
            $result = D('House_village_parking_position')->parking_position_add($data);
            if ($result !== false) {
                if (!empty($house_village_park_config['comid']) && $house_village_park_config['park_sys_type'] == 'D3') {
                    $park_data = [
                        'method' => 'ParkingDetails',
                        'park_id' => $house_village_park_config['comid']
                    ];
                    $parkDetail = $this->getParkingApi($park_data, $this->url);
                    if (!empty($parkDetail)) {
                        $num = $parkDetail['num'] + 1;
                    } else {
                        $num = 1;
                    }
                    $set_park_data = [
                        'method' => 'setParkingPlaceNum',
                        'park_id' => $house_village_park_config['comid'],
                        'num' => $num
                    ];
                    $this->editParkingApi($set_park_data, $this->url_v2);
                }
                if ($_POST['user_id']) {
                    M('House_village_parking_position')->where(['position_id' => $result])->setField('position_status', 2);
                    $this->position_add($result, $_POST['user_id']);
                }
                $this->ajaxReturn(array('code' => 1, 'msg' => '增加车位成功!'));

            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => '增加车位失败!'));

            }
        } else {
            $where['village_id'] = $this->village_id;
            $garage_list = D('House_village_parking_garage')->get_garage_list('', $where);
            $this->assign('garage_list', $garage_list);
            $dataList = M('House_village_park_month')->where(array('village_id' => $this->village_id, 'park_cycle_type' => 1))->select();
            $this->assign('dataList', $dataList);
            $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
            $this->assign('house_village_park_config', $house_village_park_config);
            $this->assign('new_charge_time', $is_new_charge);
        }
        $this->display();
    }


    public function position_add($id, $user_id)
    {
        $data['position_id'] = $id;
        $data['user_id'] = $user_id;
        $data['village_id'] = $this->village_id;
        M('House_village_bind_position')->data($data)->add();
    }


    /**
     * [vehicle_import_add 车位信息导入]
     * @return [type] [description]
     */
    public function parking_import_add()
    {
        //车位管理-添加 权限
        if (!in_array(46, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageposition');

                $upload_dir = './upload/excel/villageposition/' . date('Ymd') . '/';
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
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);    //删除文件
                    $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
                    if ($house_village_park_config['park_versions'] == 2) { //新版
                        $error_arr = array();
                        if (!empty($result) && is_array($result)) {
                            unset($result[1]);
                            $last_user_id = 0;
                            $err_msg = '';
                            $car_model = D('House_village_parking_position');

                            foreach ($result as $kk => $vv) {
                                if (empty($vv)) {
                                    continue;
                                }
                                if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null) continue;

                                if (empty($vv['A'])) {
                                    $vv['G'] = '车库名为空！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车库名为空！'.PHP_EOL;
                                    continue;
                                }
                                if (empty($vv['B'])) {
                                    $vv['G'] = '车位号为空！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车位号为空！'.PHP_EOL;
                                    continue;
                                }
                                if (preg_match('/[\x7f-\xff]/', $vv['B'])) {
                                    $vv['F'] = '车位号不能含汉字！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车位号为空！'.PHP_EOL;
                                    continue;
                                }
                                if (empty($vv['C'])) {
                                    $vv['G'] = '车位模式！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车位面积为空！'.PHP_EOL;
                                    continue;
                                }

                                if (empty($vv['D'])) {
                                    $vv['G'] = '月租车收费类型不存在！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车位面积为空！'.PHP_EOL;
                                    continue;
                                }

                                $where = array();
                                $where['village_id'] = $this->village_id;
                                $where['garage_num'] = $vv['A'];
                                $garaga_info = D('House_village_parking_garage')->get_one($where);
                                if (!$garaga_info) {
                                    $vv['G'] = $vv['A'].'车库不存在！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车库不存在！'.PHP_EOL;
                                    continue;
                                }

                                $res = M('House_village_park_month')->where(array('month_name' => $vv['D'], 'village_id' => $this->village_id, 'park_cycle_type' => 1))->find();
                                if (empty($res)) {

                                    $vv['G'] = '月租车收费类型错误,请重新选择！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if (trim($vv['C']) == '真实车位') {
                                    $position_pattern = 1;
                                } else {
                                    $position_pattern = 2;
                                }
                                //验证车位是否存在
                                $where = array();
                                $where['garage_id'] = $garaga_info['garage_id'];
                                $where['position_num'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                                $where['village_id'] = $this->village_id;
                                $is_exist = D('House_village_parking_position')->get_parking_one($where);
                                if ($is_exist) {
                                    $vv['G'] = '车位号已存在！';
                                    $error_arr[] = $vv;
                                    continue;
                                }

                                $tmpdata = array();
                                $tmpdata['garage_id'] = $garaga_info['garage_id'];
                                $tmpdata['position_num'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                                $tmpdata['position_pattern'] = $position_pattern;


                                $tmpdata['month_charge_type'] = $res['id'];
                                $tmpdata['position_note'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                                $tmpdata['park_num'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                                $tmpdata['village_id'] = $this->village_id;


                                $res = D('House_village_parking_position')->parking_position_add($tmpdata);

                            }
                            if (empty($error_arr)) {
                                $this->success('导入成功');
                                exit;
                            } else {
                                $num = count($error_arr);
                                // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                                //导出失败信息
                                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                                error_reporting(E_ALL);
                                date_default_timezone_set('Europe/London');
                                $objExcel = new PHPExcel();

                                $title = $this->village['village_name'] . '社区-车位导入失败列表';
                                /*以下是一些设置 ，什么作者  标题之类的*/
                                $objExcel->getProperties()->setCreator($title)
                                    ->setLastModifiedBy($title)
                                    ->setTitle($title)
                                    ->setSubject("失败信息导出")
                                    ->setDescription("备份数据")
                                    ->setKeywords("excel")
                                    ->setCategory("result file");

                                $i = 0;
                                $objExcel->createSheet();
                                $objExcel->setActiveSheetIndex($i);
                                $objExcel->getActiveSheet()->setTitle($title);
                                $objActSheet = $objExcel->getActiveSheet();
                                $objActSheet->setCellValue('A1', '车库名');
                                $objActSheet->setCellValue('B1', '车位号(真实车位可根据小区车位编号填写,虚拟车位则随意填写)');
                                $objActSheet->setCellValue('C1', '车位模式(需选中第二列,向下复制,可下拉选择车位模式)');

                                $objActSheet->setCellValue('D1', '月租车收费类型（请与后台智能硬件>智慧停车>月租车费用类型 保持一致）');
//                                $objActSheet->setCellValue('E1', '可绑定车辆(默认可添加一辆)');
                                $objActSheet->setCellValue('E1', '备注');
                                $objActSheet->setCellValue('F1', '失败信息');
                                $index = 2;
                                foreach ($error_arr as $value) {
                                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                    $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                    $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                                    $index++;
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
                                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                                header("Content-Transfer-Encoding:binary");
                                $objWriter->save('php://output');
                                exit();
                            }


                        }
                    } else {
                        $error_arr = array();
                        if (!empty($result) && is_array($result)) {
                            unset($result[1]);
                            $last_user_id = 0;
                            $err_msg = '';
                            $car_model = D('House_village_parking_position');

                            foreach ($result as $kk => $vv) {
                                if (array_sum($vv) == 0) {
                                    continue;
                                }
                                if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null) continue;

                                if (empty($vv['A'])) {
                                    $vv['E'] = '车库名为空！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车库名为空！'.PHP_EOL;
                                    continue;
                                }
                                if (empty($vv['B'])) {
                                    $vv['E'] = '车位号为空！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车位号为空！'.PHP_EOL;
                                    continue;
                                }
                                if (empty($vv['C'])) {
                                    $vv['E'] = '车位面积为空！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车位面积为空！'.PHP_EOL;
                                    continue;
                                }

                                $where = array();
                                $where['village_id'] = $this->village_id;
                                $where['garage_num'] = $vv['A'];
                                $garaga_info = D('House_village_parking_garage')->get_one($where);
                                if (!$garaga_info) {
                                    $vv['E'] = $vv['A'].'车库不存在！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车库不存在！'.PHP_EOL;
                                    continue;
                                }

                                $tmpdata = array();
                                $tmpdata['garage_id'] = $garaga_info['garage_id'];
                                $tmpdata['position_num'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                                $tmpdata['position_area'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                                $tmpdata['position_note'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                                $tmpdata['village_id'] = $this->village_id;

                                //验证车位是否存在
                                $where = array();
                                $where['garage_id'] = $tmpdata['garage_id'];
                                $where['position_num'] = $tmpdata['position_num'];
                                $where['village_id'] = $this->village_id;
                                $is_exist = D('House_village_parking_position')->get_parking_one($where);
                                if ($is_exist) {
                                    $vv['E'] = '车位号已存在！';
                                    $error_arr[] = $vv;
                                    continue;
                                }

                                $res = D('House_village_parking_position')->parking_position_add($tmpdata);

                            }
                            if (empty($error_arr)) {
                                $this->success('导入成功');
                                exit;
                            } else {
                                $num = count($error_arr);
                                // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                                //导出失败信息
                                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                                error_reporting(E_ALL);
                                date_default_timezone_set('Europe/London');
                                $objExcel = new PHPExcel();

                                $title = $this->village['village_name'] . '社区-车位导入失败列表';
                                /*以下是一些设置 ，什么作者  标题之类的*/
                                $objExcel->getProperties()->setCreator($title)
                                    ->setLastModifiedBy($title)
                                    ->setTitle($title)
                                    ->setSubject("失败信息导出")
                                    ->setDescription("备份数据")
                                    ->setKeywords("excel")
                                    ->setCategory("result file");

                                $i = 0;
                                $objExcel->createSheet();
                                $objExcel->setActiveSheetIndex($i);
                                $objExcel->getActiveSheet()->setTitle($title);
                                $objActSheet = $objExcel->getActiveSheet();
                                $objActSheet->setCellValue('A1', '车库名');
                                $objActSheet->setCellValue('B1', '车位号');
                                $objActSheet->setCellValue('C1', '车位面积');
                                $objActSheet->setCellValue('D1', '备注');
                                $objActSheet->setCellValue('E1', '失败信息');
                                $index = 2;
                                foreach ($error_arr as $value) {
                                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                    $index++;
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
                                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                                header("Content-Transfer-Encoding:binary");
                                $objWriter->save('php://output');
                                exit();
                            }

                            // if (!empty($res)) {
                            //     $this->success('导入成功');
                            //     exit;
                            // } else {
                            //     $this->error('导入失败！原因：' . $err_msg);
                            //     exit;
                            // }
                        }
                    }

                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {

            $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
            $this->assign('house_village_park_config', $house_village_park_config);
            $this->display();
        }
    }


    public function parking_download_add()
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $user_list[] = array('东一区(必须是已录入的车库)', '12456', '虚拟车位', '类型一', '1', '备注内容',);

        $single_data = "虚拟车位,真实车位";
        $title = '导入车位模板';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->getStyle('B1')->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->setTitle('导入房间模板');
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '车库名');
            $objActSheet->setCellValue('B1', '车位号(真实车位可根据小区车位编号填写,虚拟车位则随意填写)');
            $objActSheet->setCellValue('C1', '车位模式(需选中第二列,向下复制,可下拉选择车位模式)');
            $objActSheet->setCellValue('D1', '月租车收费类型（请与后台智能硬件>智慧停车>月租车费用类型 保持一致）');
//            $objActSheet->setCellValue('E1', '可绑定车辆(默认可添加一辆)');
            $objActSheet->setCellValue('E1', '备注');


            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range('A', 'F');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($user_list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                    $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
//                    $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                    $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                    $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                    $objActSheet->setCellValueExplicit('F' . $index, $value[5]);


                    $objExcel->setActiveSheetIndex(0);
                    $objActSheet = $objExcel->getActiveSheet();
                    $objValidation = $objActSheet->getCell("C2")->getDataValidation(); //这一句为要设置数据有效性的单元格

                    $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                        ->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(false)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('输入的值有误')
                        ->setError('您输入的值不在下拉框列表内.')
                        ->setPromptTitle('车位')
                        ->setFormula1('"' . $single_data . '"');
                    $index++;
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
            header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }


    public function vehicle_download_add()
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $user_list[] = array('皖', 'A12345', '东1区', 'A001', '234345', '小叮当', '13212345678', '2035年12月31日', '');

        $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
        $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if ($house_village_park_config['is_park_month_type'] != 1) {
            unset($parking_car_type_arr[1], $parking_car_type_arr[2], $parking_car_type_arr[3], $parking_car_type_arr[4]);
        }
        if ($house_village_park_config['is_temporary_park_type'] != 1) {
            unset($parking_car_type_arr[13], $parking_car_type_arr[14], $parking_car_type_arr[15], $parking_car_type_arr[16]);
        }
        $parking_car_type_data = implode(',', $parking_car_type_arr);;

//        $single_data = "储值车A,储值车B,储值车C,储值车D";
//        $temp_data = "临时车A,临时车B,临时车C,临时车D";
        $title = '导入车辆模板';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->getStyle('B1')->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->setTitle('导入房间模板');
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '省份');
            $objActSheet->setCellValue('B1', '车牌号码');
            $objActSheet->setCellValue('C1', '车库名');
            $objActSheet->setCellValue('D1', '车位号');
            $objActSheet->setCellValue('E1', '停车卡号');
            $objActSheet->setCellValue('F1', '车主姓名');
            $objActSheet->setCellValue('G1', '车主手机号');
            if (2 == $house_village_park_config['park_versions']) {
                $objActSheet->setCellValue('H1', '停车到期时间');
                $objActSheet->setCellValue('I1', '停车卡类(需选择第二行的右下角向下复制,可下拉选择停车卡类)');
            }


            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range('A', 'I');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($user_list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                    $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                    $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                    $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                    $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                    $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                    $objActSheet->setCellValueExplicit('G' . $index, $value[6]);


                    if (2 == $house_village_park_config['park_versions']) {
                        $objActSheet->setCellValueExplicit('H' . $index, $value[7]);

                        $objExcel->setActiveSheetIndex(0);
                        $objActSheet = $objExcel->getActiveSheet();
                        $objValidation = $objActSheet->getCell("I2")->getDataValidation(); //这一句为要设置数据有效性的单元格

                        $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                            ->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(false)
                            ->setShowInputMessage(true)
                            ->setShowErrorMessage(true)
                            ->setShowDropDown(true)
                            ->setErrorTitle('输入的值有误')
                            ->setError('您输入的值不在下拉框列表内.')
                            ->setPromptTitle('停车卡类')
                            ->setFormula1('"' . $parking_car_type_data . '"');
                    }

                    $index++;
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
            header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }


    /**
     * [bind_position_del 解除车位绑定]
     * @return [type] [description]
     */
    public function unbind_position()
    {
        $bind_id = trim($_POST['bind_id']);

        if (!$bind_id || $bind_id == '') {
            $this->ajaxReturn(array('code' => 2, 'msg' => '参数传递错误!'));
        } else {
            $where['bind_id'] = $bind_id;
        }

        $result = D('House_village_bind_position')->del_bind_position($where);//解除

        if ($result !== false) {
            $this->ajaxReturn(array('code' => 1, 'msg' => '解绑成功!'));
        } else {
            $this->ajaxReturn(array('code' => 2, 'msg' => '解绑失败!'));
        }
    }

    /**
     * [parking_del 车位信息删除]
     * @return [type] [description]
     */
    public function parking_del()
    {
        //车位管理-删除 权限
        if (!in_array(48, $this->house_session['menus'])) {
            $this->ajaxReturn(array('code' => 2, 'msg' => '对不起，您没有权限执行此操作'));
        }

        $id = htmlspecialchars(trim($_POST['position_id']));

        if (!$id && $id = '') {
            $this->ajaxReturn(array('code' => 2, 'msg' => '获取删除对象失败!'));
        }
        $id_arr = explode(',', $id);
        if ($id_arr) {
            foreach ($id_arr as $key => $value) {
//                $res = D('House_village_parking_car')->get_parking_car_one(array('car_position_id'=>$value));//判断该车位是否存在绑定车辆
//                if($res){
//                    $this->ajaxReturn(array('code'=>2,'msg'=>'请先删除已绑定该车位的车辆!'));
//                }

                $ress = D('House_village_bind_position')->get_bind_position_one(array('position_id'=>$value));
                if($ress){
                    D('House_village_bind_position')->del_bind_position(array('position_id'=>$value));
                 //   $this->ajaxReturn(array('code'=>2,'msg'=>'请先解绑该车位的绑定用户!'));
                }
            }
        }

        $where['position_id'] = array('in', $_POST['position_id']);
        $result = D('House_village_parking_position')->del_prking_position($where);
        if ($result !== false) {
            $this->ajaxReturn(array('code' => 1, 'msg' => '删除成功!'));
        } else {
            $this->ajaxReturn(array('code' => 2, 'msg' => '删除失败!'));
        }
    }



    /**
     * 车位绑定消费标准
     */

    //添加缴费项
    public function payment_add()
    {
        $payment_list = D('House_village_payment')->where(array('village_id' => $this->village_id))->select();
        $this->assign('payment_list', $payment_list);
        $this->display();
    }

    // 车位签订缴费项合同
    public function position_payment_add()
    {
        // 车位-添加缴费项 权限
        if (!in_array(261, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $payment_info = D('House_village_payment')->where(array('village_id' => $this->village_id, 'payment_id' => $_POST['payment_id']))->find();
        $standard_info = D('House_village_payment_standard')->where(array('payment_id' => $_POST['payment_id'], 'standard_id' => $_POST['standard_id']))->find();

        $cycle_type = array('Y' => 'year', 'M' => 'month', 'D' => 'day',);
        $data['payment_id'] = $_POST['payment_id'];
        $data['standard_id'] = $_POST['standard_id'];

        $data['village_id'] = $this->village_id;
        if ($_POST['start_time'] == '') {
            $_POST['start_time'] = date('Y-m-d', time());
        }

        if ($_POST['cycle_sum'] == '') {
            $_POST['cycle_sum'] = $standard_info['max_cycle'];
        }
        $data['start_time'] = strtotime($_POST['start_time']);
        $data['end_time'] = strtotime($_POST['start_time'] . '+' . $_POST['cycle_sum'] * $standard_info['pay_cycle'] . $cycle_type[$standard_info['cycle_type']]);
        $data['cycle_sum'] = $_POST['cycle_sum'];
        $data['pay_cycle'] = 0;
        $data['remarks'] = $_POST['remarks'];
        $data['bind_type'] = 2; //绑定类型


        //todo 通过业主绑定车位写入缴费pigcms_id
        $where_data['village_id'] = $this->village_id;
        $where_data['position_id'] = $_POST['position_id'];
        $user_id_ = D('House_village_bind_position')->where($where_data)->getField('user_id');
        if ($user_id_) {
            $data['pigcms_id'] = $user_id_;
        }

        if (empty($_POST['uid'])) {
            $data['position_id'] = $_POST['position_id'];
            if ($_POST['metering_mode_type'] == 3) { //车位面积
                $position_info = D('House_village_parking_position')->where(array('position_id' => $_POST['position_id']))->get_one();
                $data['metering_mode_val'] = $position_info['position_area'] ? $position_info['position_area'] : 0;
            } else {
                $data['metering_mode_val'] = $_POST['metering_mode_val'];
            }
            if ($standard_info['pay_type'] == 1) {
                $data['metering_mode_val'] = '';
            }
            $res = D('House_village_payment_standard_bind')->data($data)->add();

            // 发送微信通知
            if ($data['start_time'] < time()) {
                $bind_user = D('house_village_bind_position')->where(array('position_id' => $data['position_id'], 'village_id' => $this->village_id));
            }
        } else {
            // 批量添加
            $position_ids = explode(',', trim($_POST['uid'], ','));
            foreach ($position_ids as $key => $value) {
                if ($value > 0) {
                    $data['position_id'] = $value;
                    $where_data['village_id'] = $this->village_id;
                    $where_data['position_id'] = $value;
                    $user_id_ = D('House_village_bind_position')->where($where_data)->getField('user_id');
                    if ($user_id_) {
                        $data['pigcms_id'] = $user_id_;
                    }
                    if ($_POST['metering_mode_type'] == 3) { //车位面积
                        $position_info = D('House_village_parking_position')->where(array('position_id' => $value))->get_one();
                        $data['metering_mode_val'] = $position_info['position_area'] ? $position_info['position_area'] : 0;
                    } else {
                        $data['metering_mode_val'] = $_POST['metering_mode_val'];
                    }
                    if ($standard_info['pay_type'] == 1) {
                        $data['metering_mode_val'] = '';
                    }
                    $res = D('House_village_payment_standard_bind')->data($data)->add();
                }
            }

            // 发送微信通知
            if ($data['start_time'] < time()) {
                $bind_user = D('house_village_bind_position')->where(array('position_id' => array('in', $position_ids), 'village_id' => $this->village_id))->select();
            }
        }
        if ($bind_user) {
            // 发送微信通知
            $pigcms_ids = array();
            foreach ($bind_user as $key => $value) {
                $pigcms_ids[] = $value['user_id'];
            }

            $database_house_village_user_bind = D('House_village_user_bind');
            $pigcms_ids = array_unique($pigcms_ids);
            $bind_condition['pigcms_id'] = implode(',', $pigcms_ids);
            $bind_condition['village_id'] = $this->village_id;
            $user_bind_list = $database_house_village_user_bind->get_cashier_unpaid_list($bind_condition, 0, 0);

            if ($user_bind_list['list']) {
                $database_house_village_user_bind->send_weixin_pay($this->village_id, $user_bind_list['list']);
            }
        }
        if ($res) {
            $this->success('保存成功！');
        } else {
            $this->error('保存失败！');
        }
    }

    /**
     * [garage_management 车库管理]
     * @return [type] [description]
     */
    public function garage_management()
    {
        //车库管理-查看 权限
        if (!in_array(51, $this->house_session['menus'])) {
            $this->ajaxReturn('对不起，您没有权限执行此操作');
        }

        $where['village_id'] = $this->village_id;
        $field = 'garage_id,garage_position,garage_num,garage_remark';
        $info_list = D('House_village_parking_garage')->get_garage_select($field, $where);
        if ($info_list) {
            $this->assign('info_list', $info_list);
        } else {
            $this->error('信息错误!');
        }
        $this->display();
    }


    /**
     * [garage_del 车库删除]
     * @return [type] [description]
     */
    public function garage_del()
    {
        //车库管理-删除 权限
        if (!in_array(54, $this->house_session['menus'])) {
            $this->ajaxReturn(array('code' => 2, 'msg' => '对不起，您没有权限执行此操作'));
        }
        $id = trim($_POST['garage_id']);

        if (!$id && $id = '') {
            $this->ajaxReturn(array('code' => 2, 'msg' => '获取删除对象失败'));
        }

        $where['garage_id'] = array('in', $id);
        $where['village_id'] = $this->village_id;

        //先查看是否绑定车位
        $position_list = D('House_village_parking_position')->get_parking_select($where);
        if ($position_list) {
            $this->ajaxReturn(array('code' => 1, 'msg' => '删除车库时请先删除该车库下所有车位!'));
        }

        $result = D('House_village_parking_garage')->del_prking_garage($where);
        if ($result !== false) {
            $this->ajaxReturn(array('code' => 1, 'msg' => '删除车库信息成功!'));
        } else {
            $this->ajaxReturn(array('code' => 2, 'msg' => '删除车库信息成功!'));
        }
    }

    /**
     * [garage_add 车库添加]
     * @return [type] [description]
     */
    public function garage_add()
    {
        //车库管理-添加 权限
        if (!in_array(52, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            $data['garage_num'] = htmlspecialchars(trim($_POST['garage_num']));
            $data['garage_position'] = htmlspecialchars(trim($_POST['garage_position']));
            $data['garage_remark'] = htmlspecialchars(trim($_POST['garage_remark']));
            $data['garage_addtime'] = time();
            $data['village_id'] = $this->village_id;
            $garage_info = D('House_village_parking_garage')->get_garage_one(true, array('garage_num' => $data['garage_num'], 'village_id' => $this->village_id));
            if ($garage_info) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '车库已存在!'));
            }
            $result = D('House_village_parking_garage')->add_parking_garage($data);
            if ($result !== false) {
                $this->ajaxReturn(array('code' => 1, 'msg' => '添加车库信息成功!'));
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => '添加车库信息失败!'));
            }
        } else {

        }
        $this->display();
    }


    /* 导出excel函数*/
    public function garage_push($name = 'Excel')
    {
        //车位管理-导出 权限
        if (!in_array(50, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        error_reporting(E_ALL);
        date_default_timezone_set('Europe/London');
        $objExcel = new PHPExcel();

        $title = $this->village['village_name'] . '社区-车位列表';
        /*以下是一些设置 ，什么作者  标题之类的*/
        $objExcel->getProperties()->setCreator($title)
            ->setLastModifiedBy($title)
            ->setTitle($title)
            ->setSubject("数据EXCEL导出")
            ->setDescription("备份数据")
            ->setKeywords("excel")
            ->setCategory("result file");

        /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        if ($_GET['position_num'] && $_GET['position_num'] !== '') {//车位编号
            $where['position_num'] = trim($_GET['position_num']);
        }

        if ($_GET['position_status'] && $_GET['position_status'] !== '') {//车位状态
            $where['position_status'] = trim($_GET['position_status']);
        }

        if ($_GET['garage']) {//车库
            $where['garage_id'] = trim($_GET['garage']);
        }
        $where['village_id'] = $this->village_id;
        $count = D('House_village_parking_position')->parking_position_count($where);
        $data = D('House_village_parking_position')->get_parking_list($where, 9999999);
        $length = ceil($count / 1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle('车位管理');
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '车位编号');
            $objActSheet->setCellValue('B1', '所属' . C('config.house_name'));
            $objActSheet->setCellValue('C1', '车库名称');
            $objActSheet->setCellValue('D1', '车位号');
            $objActSheet->setCellValue('E1', '车位状态');
            $objActSheet->setCellValue('F1', '车位面积(㎡)');
            $objActSheet->setCellValue('G1', '备注');
            $field = 'village_name';
            $village_id = $this->village_id;
            $village_name = D('House_village')->get_village_info($village_id, $field);
            if (!empty($data)) {
                $index = 2;
                for ($k = $i * 1000; $k < ($i + 1) * 1000; $k++) {
                    if (!$data['info_list'][$k]['position_id']) {
                        break;
                    }
                    $objActSheet->setCellValueExplicit('A' . $index, $data['info_list'][$k]['position_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $village_name['village_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $data['info_list'][$k]['garage_num']);
                    $objActSheet->setCellValueExplicit('D' . $index, $data['info_list'][$k]['position_num']);
                    if ($data['info_list'][$k]['position_status'] == 1) {
                        $objActSheet->setCellValueExplicit('E' . $index, '未使用');
                    } else {
                        $objActSheet->setCellValueExplicit('E' . $index, '已使用');
                    }
                    $objActSheet->setCellValueExplicit('F' . $index, $data['info_list'][$k]['position_area']);
                    $objActSheet->setCellValueExplicit('G' . $index, $data['info_list'][$k]['position_note']);
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
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }


    /**
     * [garage_edit 车库信息修改]
     * @return [type] [description]
     */
    public function garage_edit()
    {
        if (IS_POST) {
            //车库管理-编辑 权限
            if (!in_array(53, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $data['garage_num'] = htmlspecialchars(trim($_POST['garage_num']));
            $data['garage_position'] = htmlspecialchars(trim($_POST['garage_position']));
            $data['garage_remark'] = htmlspecialchars(trim($_POST['garage_remark']));
            $data['garage_id'] = htmlspecialchars(trim($_POST['garage_id']));
            $result = D('House_village_parking_garage')->save_parking_garage($data);
            if ($result !== false) {
                $this->ajaxReturn(array('code' => 1, 'msg' => '修改车库信息成功!'));
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => '修改车库信息失败!'));
            }
        } else {
            $garage_id = trim($_GET['garage_id']);

            //车库管理-查看 权限
            if (!in_array(51, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            if (!$garage_id && $garage_id = '') {
                $this->error('错误的车库信息');
            }

            $field = 'garage_id,garage_num,garage_position,garage_remark';
            $where['garage_id'] = $garage_id;
            $info_list = D('House_village_parking_garage')->get_garage_one($field, $where);
            $this->assign('info_list', $info_list);
        }
        $this->display();
    }


    /**
     * [vehicle_management 车辆管理]
     * @return [type] [description]
     */
    public function vehicle_management()
    {
        if (IS_POST) {
            //车辆管理-绑定 权限
            if (!in_array(255, $this->house_session['menus'])) {
                $this->ajaxReturn(array('code' => 3, 'msg' => '对不起，您没有权限执行此操作'));
            }

            $position_bind_user_ids = array_unique(json_decode($_POST['position_bind_user_ids']));//需要绑定的用户ID
            $car_id = htmlspecialchars(trim($_POST['car_id']));//车位ID
            if (!$position_bind_user_ids || !$car_id) {
                $this->ajaxReturn(array('code' => 2, 'msg' => '数据获取错误!'));
            }
            $field = 'user_id';
            $where['car_id'] = $car_id;
            $where['user_id'] = ['gt', 0];
            $data = D('House_village_bind_car')->get_bind_car_select($field, $where);//已绑定的用户ID
            $userArr = D('House_village_user_bind')->where(array('pigcms_id' => ['in',$position_bind_user_ids]))->getField('pigcms_id,uid');
            if (!$data || $data == '') {//数据库不存在数据时
                foreach ($position_bind_user_ids as $key => $value) {
                    if ($userArr&&isset($userArr[$value])) {
                        $arr[$key]['uid'] = intval($userArr[$value]);
                    }
                    $arr[$key]['user_id'] = $value;
                    $arr[$key]['car_id'] = $car_id;
                    $arr[$key]['village_id'] = $this->village_id;
                }
            } else {
                foreach ($position_bind_user_ids as $key => $value) {
                    foreach ($data as $k => $v) {

                        if ($value == $v['user_id']) {
                            $msg = D('House_village_user_bind')->field('phone,name')->where(array('pigcms_id' => $value))->find();
                            $this->ajaxReturn(array('code' => 2, 'msg' => '用户' . $msg['name'] . '--' . $msg['phone'] . '已绑定!'));
                            exit;
                        } else {
                            if ($userArr&&isset($userArr[$value])) {
                                $arr[$key]['uid'] = intval($userArr[$value]);
                            }
                            $arr[$key]['user_id'] = $value;
                            $arr[$key]['car_id'] = $car_id;
                            $arr[$key]['village_id'] = $this->village_id;
                        }
                    }
                }
            }
            $result = D('House_village_bind_car')->bind_car_addAll($arr);

            if ($result !== false) {
                $this->ajaxReturn(array('code' => 1, 'msg' => '绑定住户成功!'));
            } else {
                $this->ajaxReturn(array('code' => 2, 'msg' => '绑定住户失败!'));
            }
        } else {
            //车辆管理-查看 权限
            if (!in_array(55, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
            $this->assign('house_village_park_config', $house_village_park_config);

            import('@.ORG.merchant_page');
            $where = array();
            if ($_GET['search_type'] && $_GET['search_value']) {
                switch ($_GET['search_type']) {
                    case '1'://车牌号
                        $car_number_1 = preg_replace("/[\x{4e00}-\x{9fa5}]/iu", "", trim($_GET['search_value'])); //这样是去掉汉字
                        $where['a.car_number'] = array('like', '%' . $car_number_1 . '%');
                        break;
                    case '2': //停车卡号
                        $where['a.car_stop_num'] = array('like', '%' . $_GET['search_value'] . '%');
                        break;
                    case '3'://车位号
                        $where['b.position_num'] = array('like', '%' . $_GET['search_value'] . '%');
                        break;
                    case '4'://车主姓名
                        $where['a.car_user_name'] = array('like', '%' . $_GET['search_value'] . '%');
                        break;
                    case '5'://车主手机号
                        $where['a.car_user_phone'] = array('like', '%' . $_GET['search_value'] . '%');
                        break;
                }
            }

            $where['a.village_id'] = $this->village_id;
            $join = ' left join pigcms_house_village_parking_position as b on a.car_position_id=b.position_id left join pigcms_house_village_parking_garage as g on b.garage_id=g.garage_id';
            $field = 'a.*,b.position_num,g.garage_num';
            $order = 'a.car_addtime desc';
            $info_list = D('House_village_parking_car')->get_parking_car_list($field, $where, $join, $order);
            if ($info_list['info_list']) {
                foreach ($info_list['info_list'] as $k => &$v) {
                    //增加储值卡判断
                    $bind_car = D('House_village_bind_car')->where(array('car_id' => $v['car_id'], 'village_id' => $v['village_id'], 'user_id' => ['gt', 0]))->find();
                    if ($bind_car) {
                        $v['bind_car_status'] = 1;
                        $v['bind_car_s'] = 1;
                    } else {
                        $v['bind_car_status'] = 2;
                        $v['bind_car_s'] = 0;
                    }
                    $v['relationship']=$this->relationship[$bind_car['relationship']];
                    $v['car_addtime']=date('Y-m-d H:i:s',$v['car_addtime']);
                    $v['examine_time']=$v['examine_time']<=1?'--':date('Y-m-d H:i:s',$v['examine_time']);
                    $v['examine_status']=$v['examine_status']==1?'已审核':($v['examine_status']==2?'拒绝':'待审核');
                    $v['end_time'] = $v['end_time'] ? date('Y-m-d', $v['end_time']) : '--';
                }
            }
            $this->assign('info_list', $info_list);
        }
        $this->display();
    }

    /**
     * 根据车辆ID获取列表 POST请求
     * @return [type] [description]
     */
    public function vehicle_management_list(){
        if(IS_POST){
            //车辆管理-查看 权限
            if (!in_array(55, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $car_id = $_POST['car_id'];
            if(empty($car_id)){
                $info_list['info_list'] = [];
                $this->ajaxReturn(array('status'=>1,'list'=>$info_list));
            }
            $where = array();
            $where['a.village_id'] = $this->village_id;
            $where['a.car_id'] = array('in',$car_id);
            $join=' left join pigcms_house_village_parking_position as b on a.car_position_id=b.position_id left join pigcms_house_village_parking_garage as g on b.garage_id=g.garage_id';
            $field='a.*,b.position_num,g.garage_num';
            $order='a.car_addtime desc';
            $info_list = D('House_village_parking_car')->get_parking_car_list($field,$where,$join,$order);
            $this->ajaxReturn(array('status'=>1,'list'=>$info_list));
        }
    }

    /**
     * 根据小区绑定用户ID house_village_user_bind表 pigcms_id 获取绑定车辆ID POST请求
     * @return [type] [description]
     */
    public function vehicle_management_user_list(){
        if(IS_POST){
            $park_new_switch=D('House_village_info')->where(array('village_id'=>$this->village_id))->getField('park_new_switch');
            $menu_id = 55;
            if($park_new_switch == 1){//启用新版停车
                $menu_id = 112025;
            }
            //车辆管理-查看 权限
            if (!in_array($menu_id, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $pigcms_id = $_POST['pigcms_id'];
            if(empty($pigcms_id)){
                $this->ajaxReturn(array('status'=>2,'msg'=>'缺少关键参数'));
            }

            $where = array();
            $where['village_id'] = $this->village_id;
            $where['user_id'] = $pigcms_id;
            $field='car_id';
            $info_list = D('House_village_bind_car')->get_bind_car_select($field,$where);
            $data = array_column($info_list,'car_id');
            $where_user_car = array();
            $where_user_car['pigcms_id'] = $pigcms_id;
            $list = D('House_village_bind_car')->get_user_car_bind_list($where_user_car);
            $this->ajaxReturn(array('status'=>1,'list'=>$data,'data' => $list));
        }
    }

    /**
     * [vehicle_import_add 车辆管理信息导入]
     * @return [type] [description]
     */
    public function vehicle_import_add()
    {
        //车辆管理-添加 权限
        if (!in_array(56, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
        $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if ($house_village_park_config['is_park_month_type'] != 1) {
            unset($parking_car_type_arr[1], $parking_car_type_arr[2], $parking_car_type_arr[3], $parking_car_type_arr[4]);
        }
        if ($house_village_park_config['is_temporary_park_type'] != 1) {
            unset($parking_car_type_arr[13], $parking_car_type_arr[14], $parking_car_type_arr[15], $parking_car_type_arr[16]);
        }
        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villagevehicle');

                $upload_dir = './upload/excel/villagevehicle/' . date('Ymd') . '/';
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
                set_time_limit(0);
                ini_set('memory_limit', '1024M');
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);

                    unlink($path);    //删除文件
                    $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();

                    $park_versions = $house_village_park_config['park_versions'];
                    if ($park_versions == 2) { //新版
                        $error_arr = array();
                        $parking_car_type_value = D('House_village_parking_car')->parking_car_type_value;
                        if (!empty($result) && is_array($result)) {
                            unset($result[1]);
                            $last_user_id = 0;
                            $err_msg = '';
                            $car_model = D('House_village_parking_car');

                            $garage_model = D('House_village_parking_garage');
                            $position_model = D('House_village_parking_position');
                            $park_system = M('Park_system')->where(array('park_id' => $this->village_id))->find();
                            foreach ($result as $kk => $vv) {
                                $position_info=[];
                                if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null) continue;
                                if (empty($vv['B'])) {
                                    $vv['J'] = '请填写车牌号码！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if (empty($vv['F'])) {
                                    $vv['J'] = '请填写车主姓名！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if (empty($vv['G'])) {
                                    $vv['J'] = '请填写车主手机号！';
                                    $error_arr[] = $vv;
                                    continue;
                                }

                                if (trim($vv['C']) && trim($vv['D'])) {
                                    $where_garage['garage_num'] = trim($vv['C']);
                                    $where_garage['village_id'] = $this->village_id;
                                    $garage_info = $garage_model->get_garage_one(true, $where_garage);
                                    if (!$garage_info) {
                                        $vv['J'] = '车库不存在，请查看数据中心，车位管理-车库管理';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                    $where['garage_id'] = $garage_info['garage_id'];
                                    $where['position_num'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                                    $where['village_id'] = $this->village_id;
                                    $position_info = $position_model->get_parking_one($where);
                                    if (!$position_info) {
                                        $vv['J'] = '车位号不存在，请查看数据中心-车位管理';
                                        $error_arr[] = $vv;
                                        continue;
                                    }

                                }
                                $province = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                                $car_number = strtoupper(htmlspecialchars(trim($vv['B']), ENT_QUOTES));
                                $car_stop_num = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                                $tmpdata = array();
                                $tmpdata['province'] = $province;
                                $tmpdata['car_number'] = $car_number;
                                $tmpdata['car_stop_num'] = $car_stop_num;
                                //检测用户是否已存在
                                if ($car_model->field('`car_number`')->where(array('car_number' => $car_number, 'province' => $province, 'village_id' => $this->village_id))->find()) {
                                    $vv['J'] = '车牌号已存在。';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if (strlen($car_number) > 8) {
                                    $vv['J'] = '车牌号最多8位。';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                if ($car_stop_num) {
                                    if ($car_model->field('`car_stop_num`')->where(array('car_stop_num' => $car_stop_num, 'village_id' => $this->village_id))->find()) {
                                        $vv['J'] = '停车卡号已存在。';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                }

                                $tmpdata['car_position_id'] = $position_info['position_id'] ? $position_info['position_id'] : 0;
                                $tmpdata['car_user_name'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                                $tmpdata['car_user_phone'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                                $tmpdata['village_id'] = $this->village_id;
                                $tmpdata['car_addtime'] = time();
                                $tmpdata['temporary_car_type'] = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                                $parking_car_type = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                                if ($parking_car_type && $parking_car_type_value[$parking_car_type]) {
                                    $tmpdata['parking_car_type'] = $parking_car_type_value[$parking_car_type];
                                }
                                $end_time = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                                if ($end_time) {
                                    if (preg_match('/[\x7f-\xff]/', $end_time)) {
                                        $arr = date_parse_from_format('Y年m月d日', $end_time);
                                        $end_time = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                                    } else {
                                        $arr = explode("-", $end_time);
                                        $end_time = mktime(0, 0, 0, $arr[1], $arr[2], $arr[0]);
                                    }
                                    $end_time = strtotime(date('Y-m-d 23:59:59', $end_time));
                                    $tmpdata['end_time'] = $end_time;
                                }
                                $res = $car_model->parking_car_add($tmpdata);
                                if (!$res) {
                                    $vv['J'] = '车牌号码为' . $vv['A'] . $vv['B'] . ' 导入失败！';
                                    $error_arr[] = $vv;
                                } else {
                                    // 对应车位改为已使用
//                                    if ($tmpdata['car_position_id']) {
//                                        M('House_village_parking_position')->where(['position_id' => $tmpdata['car_position_id']])->setField('position_status', 2);
//                                    }
                                    if ($park_versions && 2 == $park_versions && $position_info) {
                                        try {
                                            D('House_village_parking_car')->carToDevice($result, $tmpdata, $this->village_id, $park_versions, $park_system, $position_info,$house_village_park_config);
                                        } catch (\Exception $e) {
                                            $vv['J'] = $e->getMessage();
                                            $error_arr[] = $vv;
                                        }
                                    }
                                }
                            }
                            if (empty($error_arr)) {
                                $this->success('导入成功');
                                exit;
                            } else {
                                $num = count($error_arr);
                                // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                                //导出失败信息
                                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                                error_reporting(E_ALL);
                                date_default_timezone_set('Europe/London');
                                $objExcel = new PHPExcel();

                                $title = $this->village['village_name'] . '社区-车辆导入失败列表';
                                /*以下是一些设置 ，什么作者  标题之类的*/
                                $objExcel->getProperties()->setCreator($title)
                                    ->setLastModifiedBy($title)
                                    ->setTitle($title)
                                    ->setSubject("失败信息导出")
                                    ->setDescription("备份数据")
                                    ->setKeywords("excel")
                                    ->setCategory("result file");

                                $i = 0;
                                $objExcel->createSheet();
                                $objExcel->setActiveSheetIndex($i);
                                $objExcel->getActiveSheet()->setTitle($title);
                                $objActSheet = $objExcel->getActiveSheet();
                                $objActSheet->setCellValue('A1', '省份');
                                $objActSheet->setCellValue('B1', '车牌号码');
                                $objActSheet->setCellValue('C1', '车库名');
                                $objActSheet->setCellValue('D1', '车位号');
                                $objActSheet->setCellValue('E1', '停车卡号');
                                $objActSheet->setCellValue('F1', '车主姓名');
                                $objActSheet->setCellValue('G1', '车主手机号');
                                if (2 == $park_versions) {
                                    $objActSheet->setCellValue('H1', '停车到期时间');
                                    $objActSheet->setCellValue('I1', '停车卡类(需选择第二行的右下角向下复制,可下拉选择停车卡类)');
                                }
                                $objActSheet->setCellValue('J1', '失败信息');
                                $index = 2;
                                foreach ($error_arr as $value) {
                                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                    $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                    $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                                    if (2 == $house_village_park_config['park_versions']) {
                                        $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                                        $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                                    }
                                    $objActSheet->setCellValueExplicit('J' . $index, $value['J']);
                                    $index++;
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
                                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                                header("Content-Transfer-Encoding:binary");
                                $objWriter->save('php://output');
                                exit();
                            }
                        }
                    } else {
                        $error_arr = array();
                        if (!empty($result) && is_array($result)) {
                            unset($result[1]);
                            $last_user_id = 0;
                            $err_msg = '';
                            $car_model = D('House_village_parking_car');

                            foreach ($result as $kk => $vv) {
                                /*if (array_sum($vv) == 0) {
                                    continue;
                                }*/
                                if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null) continue;

                                if (empty($vv['B'])) {
                                    $vv['I'] = '请填写车牌号码！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '请填写车牌号码！'.PHP_EOL;
                                    continue;
                                }

                                if (empty($vv['F'])) {
                                    $vv['I'] = '请填写车主姓名！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '请填写车主姓名！'.PHP_EOL;
                                    continue;
                                }

                                if (empty($vv['G'])) {
                                    $vv['I'] = '请填写车主手机号！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '请填写车主手机号！'.PHP_EOL;
                                    continue;
                                }

                                // if(!preg_match('/^[0-9]{11}$/',$vv['G'])){
                                //     $vv['H'] = '请填写正确的手机号！';
                                //     $error_arr[] = $vv;
                                //     continue;
                                // }

                                $where_garage['garage_num'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                                $where_garage['village_id'] = $this->village_id;
                                $garage_model = D('House_village_parking_garage');
                                $garage_info = $garage_model->get_garage_one(true, $where_garage);
                                if (!$garage_info) {
                                    $vv['I'] = '车库不存在，请查看社区中心，车位管理-车位管理';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $where['garage_id'] = $garage_info['garage_id'];
                                $where['position_num'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                                $where['village_id'] = $this->village_id;
                                $position_model = D('House_village_parking_position');
                                $position_info = $position_model->get_parking_one($where);
                                if (!$position_info) {
                                    // $err_msg .= '车位编号不存在，请查看社区中心，车位管理-单元列表！'.PHP_EOL;
                                    $vv['I'] = '车位号不存在，请查看社区中心，车位管理-单元列表！';
                                    $error_arr[] = $vv;
                                    continue;
                                }

                                $tmpdata = array();
                                $tmpdata['province'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                                $tmpdata['car_number'] = strtoupper(htmlspecialchars(trim($vv['B']), ENT_QUOTES));
                                $tmpdata['car_stop_num'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                                //检测用户是否已存在
                                if ($car_model->field('`car_number`')->where(array('car_number' => $tmpdata['car_number'], 'province' => $tmpdata['province'], 'village_id' => $this->village_id))->find()) {
                                    $vv['I'] = '车牌号已存在。';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车牌号已存在。'.PHP_EOL;
                                    continue;
                                }

                                if (strlen($tmpdata['car_number']) > 8) {
                                    $vv['I'] = '车牌号最多8位。';
                                    $error_arr[] = $vv;
                                    continue;
                                }

                                if ($tmpdata['car_stop_num']) {
                                    if ($car_model->field('`car_stop_num`')->where(array('car_stop_num' => $tmpdata['car_stop_num'], 'village_id' => $this->village_id))->find()) {

                                        $vv['I'] = '停车卡号已存在。';
                                        $error_arr[] = $vv;
                                        // $err_msg .= '停车卡号已存在。'.PHP_EOL;
                                        continue;
                                    }
                                }

                                $tmpdata['car_position_id'] = $position_info['position_id'];
                                $tmpdata['car_user_name'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                                $tmpdata['car_user_phone'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                                $tmpdata['car_displacement'] = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                                $tmpdata['village_id'] = $this->village_id;
                                $tmpdata['car_addtime'] = time();
                                $res = $car_model->parking_car_add($tmpdata);
                                // fdump($car_model->getLastSql(),'house_import_error_'.$this->village_id,true);
                                if (!$res) {
                                    $vv['I'] = '车牌号码为' . $vv['A'] . $vv['B'] . ' 导入失败！';
                                    $error_arr[] = $vv;
                                    // $err_msg .= '车牌号码为' . $vv['A'] .$vv['B']. ' 导入失败！'.PHP_EOL;
                                }else{
//                                    if ($tmpdata['car_position_id']) {
//                                        M('House_village_parking_position')->where(['position_id' => $tmpdata['car_position_id']])->setField('position_status', 2);
//                                    }
                                }
                            }
                            if (empty($error_arr)) {
                                $this->success('导入成功');
                                exit;
                            } else {
                                $num = count($error_arr);
                                // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                                //导出失败信息
                                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                                error_reporting(E_ALL);
                                date_default_timezone_set('Europe/London');
                                $objExcel = new PHPExcel();

                                $title = $this->village['village_name'] . '社区-车辆导入失败列表';
                                /*以下是一些设置 ，什么作者  标题之类的*/
                                $objExcel->getProperties()->setCreator($title)
                                    ->setLastModifiedBy($title)
                                    ->setTitle($title)
                                    ->setSubject("失败信息导出")
                                    ->setDescription("备份数据")
                                    ->setKeywords("excel")
                                    ->setCategory("result file");

                                $i = 0;
                                $objExcel->createSheet();
                                $objExcel->setActiveSheetIndex($i);
                                $objExcel->getActiveSheet()->setTitle($title);
                                $objActSheet = $objExcel->getActiveSheet();
                                $objActSheet->setCellValue('A1', '省份');
                                $objActSheet->setCellValue('B1', '车牌号码');
                                $objActSheet->setCellValue('C1', '车库名');
                                $objActSheet->setCellValue('D1', '车位号');
                                $objActSheet->setCellValue('E1', '停车卡号');
                                $objActSheet->setCellValue('F1', '车主姓名');
                                $objActSheet->setCellValue('G1', '车主手机号');
//                                $objActSheet->setCellValue('H1', '车辆排量');
                                $objActSheet->setCellValue('I1', '失败信息');
                                $index = 2;
                                foreach ($error_arr as $value) {
                                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                    $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                    $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
//                                    $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                                    $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                                    $index++;
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
                                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                                header("Content-Transfer-Encoding:binary");
                                $objWriter->save('php://output');
                                exit();
                            }

                            // fdump($err_msg,'house_import_error_'.$this->village_id,true);
                            // if (!empty($res)) {
                            //     $this->success('导入成功');
                            //     exit;
                            // } else {
                            //     $this->error('导入失败！原因：' . $err_msg);
                            //     exit;
                            // }
                        }
                    }


                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $this->assign('house_village_park_config', $house_village_park_config);
            $this->assign('parking_car_type_arr', $parking_car_type_arr);
            $this->display();
        }
    }



    //todo 联动查询房间数据
    public function get_village_single()
    {
        if (IS_POST) {
            if (!$_GET['pid']) {
                $this->ajaxReturn(array('code' => 1, 'data' => [], 'level' => 1));
            }
            $d = explode('-', $_GET['pid']);
            $level = $d[1];
            $list = [];
            switch ($d[1]) {
                case 0:
                    $where = array(
                        'village_id' => $this->village_id,
                        'status' => 1
                    );
                    $list = D('House_village_single')->field('single_name as name,id')->order('sort desc,id asc')->where($where)->select();
                    if (empty($list)) {
                        $level = 1;
                    } else {
                        $level++;
                    }
                    break;
                case 1:
                    $where = array(
                        'single_id' => $d[0],
                        'status' => 1,
                        'village_id' => $this->village_id
                    );
                    $list = D('House_village_floor')->field('floor_id as id,floor_name as name')->order('sort desc,floor_id asc')->where($where)->select();
                    if ($list) {
                        $level++;
                    }
                    break;
                case 2:
                    $where = array(
                        'floor_id' => $d[0],
                        'status' => 1,
                        'village_id' => $this->village_id
                    );
                    $list = M('House_village_layer')->where($where)->field('id,layer_name as name')->order('sort desc,id asc')->select();
                    if ($list) {
                        $level++;
                    }
                    break;
                case 3:
                    $where = array(
                        'layer_id' => $d[0],
                        'status' => ['in', [1, 3]],
                        'village_id' => $this->village_id,
                        'is_del'=>0
                    );
                    $where['village_id'] = $this->house_session['village_id'];
                    $list = D('House_village_user_vacancy')->field('pigcms_id as id,room as name')->order('sort desc,pigcms_id asc')->where($where)->select();
                    if (empty($list)) {
                        $level = 1;
                    }
                    break;
            }
            $this->ajaxReturn(array('code' => 0, 'data' => $list, 'level' => $level));
        }
    }

    /**
     * Notes: 智慧停车场批量同步
     * @author: wanzy
     * @date_time: 2021/10/27 13:24
     */
    public function sysParkCarDevice()
    {
        if (!in_array(56, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        $park_versions = $house_village_park_config['park_versions'];
        $park_system = M('Park_system')->where(array('park_id' => $this->village_id))->find();
        $where_car = [
            'village_id' => $this->village_id,
        ];
        $cars = D('House_village_parking_car')->where($where_car)->field('car_id,province,car_number,village_id,car_position_id,end_time')->select();
        if (!empty($cars) && $park_versions && 2 == $park_versions) {
            foreach ($cars as $item) {
                try {
                    D('House_village_parking_car')->carToDevice($item['car_id'], $item, $this->village_id, $park_versions, $park_system, [], $house_village_park_config);
                } catch (\Exception $e) {
                    $this->ajaxReturn(array('code'=>0,'msg'=>$e->getMessage()));
                }
            }
            $this->ajaxReturn(array('code' => 1, 'msg' => '已经同步!'));
        } elseif (!empty($cars)) {
            $this->ajaxReturn(array('code' => 0, 'msg' => '没有可同步车辆!'));
        } else {
            $this->ajaxReturn(array('code' => 0, 'msg' => '智慧停车场已经关闭!'));
        }
    }

    //todo 查询房间人员
    public function get_room_user()
    {
        if (IS_POST) {
            if (!$_POST['vacancy_id']) {
                $this->ajaxReturn(array('code' => 1, 'data' => [], 'level' => 1));
            }
            $list = D('House_village_user_bind')->where([
                'village_id' => $this->village_id,
                'vacancy_id' => $_POST['vacancy_id'],
                'type' => ['in', [0, 1, 2, 3]],
                'status' => 1
            ])->field('pigcms_id,name,phone')->order('pigcms_id asc')->select();
            $this->ajaxReturn(array('code' => 0, 'data' => $list));
        }
    }

    /**
     * [vehicle_add 车辆添加]
     * @return [type] [description]
     */
    public function vehicle_add()
    {
        $park_new_switch=D('House_village_info')->where(array('village_id'=>$this->village_id))->getField('park_new_switch');
        $menu_id = 56;
        if($park_new_switch == 1){//启用新版停车
            $menu_id = 112129;
        }
        //车辆管理-添加 权限
        if (!in_array($menu_id, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if ($house_village_park_config['park_sys_type'] == 'D3') {
            $parking_car_type_arr = D('House_village_parking_car')->parking_d3_car_type_arr;
        } else {
            $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
        }

        if ($house_village_park_config['is_park_month_type'] != 1) {
            unset($parking_car_type_arr[1], $parking_car_type_arr[2], $parking_car_type_arr[3], $parking_car_type_arr[4]);
        }
        if ($house_village_park_config['is_temporary_park_type'] != 1) {
            unset($parking_car_type_arr[13], $parking_car_type_arr[14], $parking_car_type_arr[15], $parking_car_type_arr[16]);
        }

        //todo 【新版收费-车辆】
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        if (IS_POST) {
            $data['car_type'] = intval($_POST['car_type']);
            $data['car_number'] = strtoupper(htmlspecialchars(trim($_POST['car_number'])));
            $data['car_position_id'] = htmlspecialchars(trim($_POST['car_position_id']));
            $data['car_position_id_path'] = htmlspecialchars(trim($_POST['car_position_id_path']));
            $data['car_stop_num'] = htmlspecialchars(trim($_POST['car_stop_num']));
            $data['car_user_name'] = htmlspecialchars(trim($_POST['car_user_name']));
            $data['car_user_phone'] = htmlspecialchars(trim($_POST['car_user_phone']));
            $data['car_displacement'] = htmlspecialchars(trim($_POST['car_displacement']));
            $data['province'] = htmlspecialchars(trim($_POST['province']));
            $data['store_value_car_type'] = htmlspecialchars(trim($_POST['store_value_car_type']));
            $data['temporary_car_type'] = htmlspecialchars(trim($_POST['temporary_car_type']));
            $data['car_addtime'] = time();
            $data['village_id'] = $this->village_id;
            $data['charge_type_id'] = $_POST['charge_type_id'];
            $data['car_color'] = htmlspecialchars(trim($_POST['car_color']));
            $data['equipment_no'] = htmlspecialchars(trim($_POST['equipment_no']));
            $data['car_brands'] = htmlspecialchars(trim($_POST['car_brands']));

            if ($_POST['end_time'] && $_POST['end_time'] != '') {
                $data['end_time'] = strtotime($_POST['end_time'] . '23:59:59');
            }else{
                $data['end_time']=0;
            }
            if ($_POST['parking_car_type'] && $_POST['parking_car_type'] > 0) {
                $data['parking_car_type'] = $_POST['parking_car_type'];
            }

            if (!$data['car_number'] && $data['car_number'] == '') {
                $this->ajaxReturn(array('code' => '2', 'msg' => '车牌号不能为空'));
            }

            if (strlen($data['car_number']) > 8) {
                $this->ajaxReturn(array('code' => '2', 'msg' => '车牌号最多输入8位'));
            }
            $village_user_bind = [];
            $order=[
                'village_id'=>$this->village_id,
                'property_id'=>$this->house_session['property_id'],
                'end_time'=>$data['end_time'],
                'position_id'=>intval($data['car_position_id'])
            ];
            if ($is_new_charge == 1) {
                $binding_type = intval($_POST['binding_type']);
                //todo 绑定对象
                if (!$binding_type) {
                    $this->ajaxReturn(array('code' => '2', 'msg' => '请选择绑定对象'));
                }
                //todo 绑定对象
                if ($binding_type == 1) {
                    //绑定房间
                    if (!isset($_POST['room_data'][3]) || empty($_POST['room_data'][3])) {
                        $this->ajaxReturn(array('code' => '2', 'msg' => '请选择绑定精确到房间'));
                    }
                    $village_user_bind=D('House_village_user_bind')->where([
                        'village_id'=>$this->village_id,
                        'vacancy_id'=>$_POST['room_data'][3],
                        'type'=>['in',[0,1,2,3]],
                        'status'=>1
                    ])->getField('pigcms_id',true);
                    if(empty($village_user_bind)){
                        $this->ajaxReturn(array('code'=>'2','msg'=>'该房间暂无人员'));

                    }
                    $user_name=D('House_village_user_bind')->getUserBindData($village_user_bind);
                    if($user_name['error']){
                        $data['car_user_name'] = $user_name['data']['name'];
                        $data['car_user_phone']=$user_name['data']['phone'];
                    }
                    
                    $data['room_id'] = $_POST['room_data'][3];
                    $order['room_id']=$data['room_id'];
                } else {
                    //绑定业主
                    if (empty($data['car_user_name'])) {
                        $this->ajaxReturn(array('code' => '2', 'msg' => '请输入车主姓名！'));
                    }
                    if (empty($data['car_user_phone'])) {
                        $this->ajaxReturn(array('code' => '2', 'msg' => '请输入车主手机号！'));
                    }
                    if (!preg_match('/^[0-9]{11}$/', $data['car_user_phone'])) {
                        $this->ajaxReturn(array('code' => '2', 'msg' => '请输入正确的手机号！'));
                    }
                    if (empty($_POST['obj_id'])) {
                        $this->ajaxReturn(array('code' => '2', 'msg' => '业主手机号不存在！'));
                    }
                    $user_bind= D('House_village_user_bind')->where([
                        'village_id' => $this->village_id,
                        'pigcms_id' => intval($_POST['obj_id']),
                        'type' => ['in', [0, 1, 2, 3]],
                        'status' => 1
                    ])->field('pigcms_id,vacancy_id')->find();
                    if (empty($user_bind)) {
                        $this->ajaxReturn(array('code' => '2', 'msg' => '该房间暂无业主'));
                    }
                    $village_user_bind[]=$user_bind['pigcms_id'];
                    $order['room_id']=$user_bind['vacancy_id'];
                }
                //todo 车位号为空是 为临时车
                if (empty($data['car_position_id'])) {
                    $postData['garage_id'] = 9999;
//                    $postData['position_status'] = 2;
                    $postData['position_num'] = $data['car_number'];
                    $postData['position_type'] = 3;
                    $postData['village_id'] = $this->village_id;
                    $postData['start_time'] = time();
                    $postData['end_time'] = $data['end_time'];
                    $position_id = D('House_village_parking_position')->add($postData);
                    $data['car_position_id'] = $position_id;
                }
            } else {
                if (empty($data['car_user_name'])) {
                    $this->ajaxReturn(array('code' => '2', 'msg' => '请输入车主姓名！'));
                }

                if (empty($data['car_user_phone'])) {
                    $this->ajaxReturn(array('code' => '2', 'msg' => '请输入车主手机号！'));
                }

                if (!preg_match('/^[0-9]{11}$/', $data['car_user_phone'])) {
                    $this->ajaxReturn(array('code' => '2', 'msg' => '请输入正确的手机号！'));
                }
            }

            $res = D('House_village_parking_car')->get_parking_car_one(array('car_number' => $data['car_number'], 'province' => $data['province'], 'village_id' => $this->village_id));
            if ($res) {
                if ($_POST['user_id']) {
                    $bind_car_info=D('House_village_bind_car')->where(array('village_id' => $this->village_id,'car_id' => $res['car_id']))->find();
                    if (!empty($bind_car_info)){
                        $this->ajaxReturn(array('code' => '2', 'msg' => '车牌号已绑定'));
                    }else{
                        D('House_village_bind_car')->data(array('village_id' => $this->village_id, 'user_id' => $_POST['user_id'], 'car_id' => $res['car_id'], 'relationship' => $_POST['relationship']))->add();
                        $this->ajaxReturn(array('code'=>'1','msg'=>'添加车辆信息成功1!','car_id' => $res['car_id']));
                    }
                }else{
                    $this->ajaxReturn(array('code' => '2', 'msg' => '车牌号已存在'));
                }
            }

            if ($data['car_stop_num']) {
                $res = D('House_village_parking_car')->get_parking_car_one(array('car_stop_num' => $data['car_stop_num'], 'village_id' => $this->village_id));
                if ($res) {
                    $this->ajaxReturn(array('code' => '2', 'msg' => '停车卡号已存在'));
                }
            }
            // 如果添加了到期时间 且 到期时间大于当前时间 且开启了智慧停车功能  同步至设备
            $park_versions = $house_village_park_config['park_versions'];
            $is_park_month_type = $house_village_park_config['is_park_month_type'];

            if ($data['car_position_id'] > 0) {
                $parking_position = D('House_village_parking_position')->where(array('position_id' => $data['car_position_id']))->find();
                //todo 停车费版本开启新版
                $park_versions = M('House_village_park_config')->where(array('village_id' => $this->village_id))->getField('park_versions');
                if ($parking_position && 2 != $parking_position['position_pattern'] && !$data['end_time'] && $parking_position['end_time']) {
                    $data['end_time'] = $parking_position['end_time'];
                } elseif ($parking_position && 2 != $parking_position['position_pattern'] && $data['end_time'] && $parking_position['end_time'] && intval($data['end_time']) < $parking_position['end_time'] && $park_versions == 2 && $is_new_charge == 0) {
                    $this->ajaxReturn(array('code' => '2', 'msg' => '到期时间不得小于当前停车位到期时间，到期时间：' . date('Y-m-d', $parking_position['end_time'])));
                }
                $park_num = D('House_village_parking_car')->where(['car_position_id' => $data['car_position_id']])->count();

                /* if (1==$is_park_month_type && 2==$park_versions && $parking_position && 2!=$parking_position['position_pattern'] && intval($parking_position['park_num'])>0 && intval($park_num)>=intval($parking_position['park_num'])) {
                    $this->ajaxReturn(array('code'=>'2','msg'=>'当前对应停车位绑定车辆数量已满，限制：'.intval($parking_position['park_num']).'(辆)'));
                } */
            }

            $result = D('House_village_parking_car')->parking_car_add($data);

            if ($result !== false) {

                //todo 写入绑定关系
                if ($is_new_charge == 1) {
                    if (!empty($village_user_bind)) {
                        $userArr = D('House_village_user_bind')->where(array('pigcms_id' => ['in',$village_user_bind]))->getField('pigcms_id,uid');
                        foreach ($village_user_bind as $v) {
                            $dataBinds = array('village_id'=>$this->village_id,'user_id'=>$v,'car_id'=>$result);
                            if (isset($userArr[$v])) {
                                $dataBinds['uid'] = intval($userArr[$v]);
                            }
                            D('House_village_bind_car')->data($dataBinds)->add();
                        }
                    }
                } else {
                    if ($_POST['user_id']) {
                        $userCarUid = D('House_village_user_bind')->where(array('pigcms_id' => $_POST['user_id']))->getField('uid');
                        $dataUserCar = array(
                            'village_id' => $this->village_id,
                            'user_id' => $_POST['user_id'],
                            'car_id' => $result,
                            'relationship' => $_POST['relationship'],
                            'uid' => intval($userCarUid),
                        );
                        D('House_village_bind_car')->data($dataUserCar)->add();
                    }
                }
                $order['car_id']=$result;
                $order['is_new_charge']=$is_new_charge;
                D('House_village_parking_car')->bindOrderLog($order);
                // 对应车位改为已使用
                if ($data['car_position_id']) {
                    M('House_village_parking_position')->where(['position_id' => $data['car_position_id']])->setField('position_status', 2);
                }
                $now_time = time();
                if ($park_versions && 2 == $park_versions && $parking_position) {

                    if ($house_village_park_config['park_sys_type'] && 'D1' == $house_village_park_config['park_sys_type']) {
                        // 进行同步
                        $house_village = D('House_village')->where(array('village_id' => $this->village_id))->field('village_name')->find();
                        if (2 != $parking_position['position_pattern']) {
                            $parking_car = D('House_village_parking_car')->where(array('car_position_id' => $parking_position['position_id']))->select();
                            // 固定车位-共同使用一个月卡会员编号
                            if ($parking_position['card_id']) {
                                $now_car_id = $parking_position['card_id'];
                            } else {
                                $now_car_id = '1' . sprintf("%09d", $parking_position['position_id']);//月卡会员编号（收费系统唯一编号，必传）
                                $park_position_set['card_id'] = $now_car_id;
                            }
                            $address = $house_village['village_name'] . '车位：' . $parking_position['position_num'];
                            if ($parking_position['end_time'] && intval($parking_position['end_time']) > intval($data['end_time'])) {
                                // 绑定真实停车位以停车位时间为底线
                                $data['end_time'] = $parking_position['end_time'];
                            }
                        } else {
                            // 虚拟停车位 每个车都是独立的
                            $parking_car = D('House_village_parking_car')->where(array('car_id' => $_POST['car_id']))->select();
                            $address = $house_village['village_name'];
                        }
                        foreach ($parking_car as $k1 => $v1) {
                            $park_set = [];
                            $park_data['begin_time'] = $v1['start_time'] ? $v1['start_time'] : $now_time;
                            $park_data['end_time'] = $data['end_time'];
                            if (intval($park_data['end_time']) > intval($now_time)) {
                                $park_data['begin_time'] = $now_time;
                            }
                            $park_set['start_time'] = $park_data['begin_time'];
                            $park_set['end_time'] = $park_data['end_time'];
                            $village_id = $v1['village_id'];
                            $park_data['car_number'] = $v1['province'] . $v1['car_number'];
                            if ($now_car_id) {
                                $park_position_set['start_time'] = $park_data['begin_time'];
                                $park_position_set['end_time'] = $park_data['end_time'];
                            }
                            if ($parking_car_type_arr && $v1['parking_car_type'] && $parking_car_type_arr[$v1['parking_car_type']]) {
                                $car_type_id = $parking_car_type_arr[$v1['parking_car_type']];
                            } else if ($v1['temporary_car_type']) {
                                $car_type_id = $v1['temporary_car_type'];
                            } else {
                                $car_type_id = '月租车A';
                            }
                            if (in_array($v1['parking_car_type'], [13, 14, 15, 16])) {
                                fdump('>>>>>>>>>' . __LINE__, 'api/log/' . date('Ymd') . '/park/month_pay_sync_log', true);
                                fdump($_POST, 'api/log/' . date('Ymd') . '/park/month_pay_sync_log', true);
                                fdump($v1, 'api/log/' . date('Ymd') . '/park/month_pay_sync_log', true);
                                continue;
                            }
                            $park_data['remark'] = '后台同步';
                            if ($now_car_id) {
                                $card_id = $now_car_id;
                            } else {
                                $card_id = '2' . sprintf("%09d", $v1['car_id']);//月卡会员编号（收费系统唯一编号）
                            }
                            $park_set['card_id'] = $card_id;
                            $getCar = D('ParkService')->getMonthCar($village_id, $park_data['car_number']);
                            if ($getCar['error']) {
                                if ($data['car_number'] == $park_data['car_number']) {
                                    // 本次添加编辑车辆直接报错
                                    $this->ajaxReturn(array('code' => '2', 'msg' => $getCar['msg']));
                                    break;
                                } else {
                                    // 不是本次编辑车辆直接跳过
                                    continue;
                                }
                            } elseif ($getCar['car_info'] && $getCar['car_info']['car_number'] == $park_data['car_number']) {
                                if ($getCar['car_info']['card_id']) {
                                    $card_id = $getCar['car_info']['card_id'];
                                }
                                $park_data['is_edit'] = 1;
                            }
//                            if ($v1['card_id']) {
//                                $park_data['is_edit'] = 1;
//                            }
                            $park_data['name'] = $v1['car_user_name'];
                            $park_data['car_type_id'] = $car_type_id;
                            $park_data['amount_receivable'] = 0;//应收金额
//                                $park_data['channel_id'] = '';//月卡允许通行的通道编号
                            $park_data['tel'] = $v1['car_user_phone'] ? $v1['car_user_phone'] : '';
                            $park_data['address'] = $address;
                            $park_data['p_lot'] = strval($parking_position['position_num']);
                            $park_data['card_id'] = $card_id;
                            if ($park_data['end_time']) {
                                $res = D('ParkService')->handleMonthCard($village_id, $v1['car_id'], $park_data, $v1);
                                if ($res && !$res['error']) {
                                    D('House_village_parking_car')->where(array('car_id' => $v1['car_id']))->data($park_set)->save();
                                    if ($park_position_set) {
                                        D('House_village_parking_position')->where(array('position_id' => $parking_position['position_id']))->data($park_position_set)->save();
                                    }
                                }
                            }
                        }
                    } else {
                        $park_system = M('park_system')->where(array('park_id' => $this->village_id))->find();
                        if ($park_system) {
                            if (!$data['end_time']) {
                                $data['end_time'] = 0;
                            }
                            $house_village = D('House_village')->where(array('village_id' => $this->village_id))->field('village_name')->find();
                            if (2 != $parking_position['position_pattern']) {
                                $parking_car = D('House_village_parking_car')->where(array('car_position_id' => $parking_position['position_id']))->select();
                                // 固定车位-共同使用一个月卡会员编号
                                if ($parking_position['card_id']) {
                                    $now_car_id = $parking_position['card_id'];
                                } else {
                                    $now_car_id = '1' . sprintf("%09d", $parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                                    $park_position_set['card_id'] = $now_car_id;
                                }
                                $car_group = '3' . sprintf("%06d", $parking_position['position_id']);
                                $address = $house_village['village_name'] . '车位：' . $parking_position['position_num'];
                                if ($parking_position['end_time'] && intval($parking_position['end_time']) > intval($data['end_time'])) {
                                    // 绑定真实停车位以停车位时间为底线
                                    $data['end_time'] = $parking_position['end_time'];
                                }
                            } else {
                                $parking_car = D('House_village_parking_car')->where(array('car_id' => $result))->select();
                                $car_group = '';
                                $address = $house_village['village_name'];
                            }
                            $nowtime = date("mdHis");
                            $trade_no = $nowtime . sprintf("%04d", $now_car_id);
                            // 如果是虚拟停车位 对应每一个车生成唯一收费编号
                            $p_lot_number = 1;
                            foreach ($parking_car as $k => $v) {
                                $park_set = [];
                                $park_data['begin_time'] = $v['start_time'] ? $v['start_time'] : $now_time;
                                $park_data['end_time'] = $data['end_time'];
                                if (intval($park_data['end_time']) > intval($now_time)) {
                                    $park_data['begin_time'] = $now_time;
                                }
                                $park_set['start_time'] = $park_data['begin_time'];
                                $park_set['end_time'] = $park_data['end_time'];
                                $park_data['park_id'] = $v['village_id'];
                                $park_data['car_number'] = $v['province'] . $v['car_number'];
                                if ($now_car_id) {
                                    $park_position_set['start_time'] = $park_data['begin_time'];
                                    $park_position_set['end_time'] = $park_data['end_time'];
                                }
                                if ($v['card_id']) {
                                    // 3.16月卡续费记录下发(停车云)
                                    $service_name = 'month_pay_sync';// 续费操作
                                    if ($now_car_id) {
                                        $card_id = $now_car_id;
                                    } else {
                                        $card_id = $v['card_id'];
                                    }
                                    $park_data['trade_no'] = $trade_no;
                                    $park_data['pay_time'] = $now_time;
                                    $park_data['amount_receivable'] = 0;
                                    $park_data['amount_pay'] = 0;
                                    $park_data['pay_type'] = '后台同步';
                                } else {
                                    if ($parking_car_type_arr && $v['parking_car_type'] && $parking_car_type_arr[$v['parking_car_type']]) {
                                        $car_type_id = $parking_car_type_arr[$v['parking_car_type']];
                                    } else if ($v['temporary_car_type']) {
                                        $car_type_id = $v['temporary_car_type'];
                                    } else {
                                        $car_type_id = '月租车A';
                                    }
                                    if (in_array($v['parking_car_type'], [13, 14, 15, 16])) {
                                        fdump('>>>>>>>>>' . __LINE__, 'api/log/' . date('Ymd') . '/park/month_pay_sync_log', true);
                                        fdump($_POST, 'api/log/' . date('Ymd') . '/park/month_pay_sync_log', true);
                                        fdump($v, 'api/log/' . date('Ymd') . '/park/month_pay_sync_log', true);
                                        continue;
                                    }
                                    // 3.7月卡会员同步（停车云）
                                    $park_data['remark'] = '后台同步';
                                    $service_name = 'month_member_sync';// 添加会员卡操作
                                    if ($now_car_id) {
                                        $card_id = $now_car_id;
                                    } else {
                                        $card_id = '2' . sprintf("%09d", $v['car_id']);//月卡会员编号（收费系统唯一编号  是
                                    }
                                    $park_set['card_id'] = $card_id;
                                    $park_data['operate_type'] = $v['card_id'] ? '2' : '1';    //1 添加，2 编辑
                                    if ($park_data['operate_type'] == 1) {
                                        $park_data['create_time'] = $now_time;
                                    } else {
                                        $park_data['update_time'] = $now_time;
                                    }
                                    $park_data['price'] = 0;//实收金额
                                    $park_data['name'] = $v['car_user_name'];
                                    $park_data['car_type_id'] = $car_type_id;
                                    $park_data['amount_receivable'] = 0;//应收金额
//                                $park_data['channel_id'] = '';//月卡允许通行的通道编号
                                    $park_data['tel'] = $v['car_user_phone'] ? $v['car_user_phone'] : '';
                                    $park_data['address'] = $address;
                                    $park_data['p_lot'] = strval($parking_position['position_num']);
                                }
                                $park_data['card_id'] = $card_id;
                                //2021/2/1 多车多位  修改 start
//                            if ($car_group) {
//                                $park_data['car_group'] = intval($car_group);
//                                $park_data['p_lot_number'] = intval($p_lot_number);
//                            }
                                $infos = D('House_village_parking_car')->bind_parking_position($card_id);
                                if ($infos) {
                                    $park_data['car_group'] = intval($infos['car_group']);
                                    $park_data['p_lot_number'] = intval($infos['p_lot_number']);
                                }
                                //2021/2/1 多车多位  修改 end
                                $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                                $json_data['data'] = $park_data;
                                $json_data['service_name'] = $service_name;
                                $post_data['unserialize_desc'] = serialize($json_data);
                                $post_data['status'] = 2;
                                $post_data['card_id'] = $card_id;
                                $post_data['msg_id'] = $json_data['msg_id'];
                                $post_data['village_id'] = $v['village_id'];
                                $post_data['park_id'] = $park_system['park_id'];
                                $post_data['token'] = $park_system['token'];
                                $post_data['car_number'] = $park_data['car_number'] ? $park_data['car_number'] : '';
                                $post_data['service_name'] = $service_name;
                                $res = M('park_total_record')->data($post_data)->add();//月卡会员下发
                                if ($res) {
                                    D('House_village_parking_car')->where(array('car_id' => $v['car_id']))->data($park_set)->save();
                                    if ($park_position_set) {
                                        D('House_village_parking_position')->where(array('position_id' => $parking_position['position_id']))->data($park_position_set)->save();
                                    }
                                }
                            }
                            D('House_village_bind_car')->data($dataBinds)->add();
                        }
                    }
                } else {
                    if ($_POST['user_id']) {
                        $userCarUid = D('House_village_user_bind')->where(array('pigcms_id' => $_POST['user_id']))->getField('uid');
                        $dataUserCar = array(
                            'village_id' => $this->village_id,
                            'user_id' => $_POST['user_id'],
                            'car_id' => $result,
                            'relationship' => $_POST['relationship'],
                            'uid' => intval($userCarUid),
                        );
                        D('House_village_bind_car')->data($dataUserCar)->add();
                    }
                }
                $order['car_id']=$result;
                $order['is_new_charge']=$is_new_charge;
                D('House_village_parking_car')->bindOrderLog($order);
                // 对应车位改为已使用
                if ($data['car_position_id']) {
//                    M('House_village_parking_position')->where(['position_id' => $data['car_position_id']])->setField('position_status', 2);
                }
                if ($park_versions && 2 == $park_versions && $parking_position) {
                    $park_system = M('Park_system')->where(array('park_id' => $this->village_id))->find();
                    try {
                        D('House_village_parking_car')->carToDevice($result, $data, $this->village_id, $park_versions, $park_system, $parking_position,$house_village_park_config);
                    } catch (\Exception $e) {
                        $this->ajaxReturn(array('code'=>'2','msg'=>$e->getMessage()));
                    }
                }
                if($data['car_position_id'] && $data['end_time']) {
                    $park_position_set = [
                        'end_time' => $data['end_time']
                    ];
                    D('House_village_parking_position')->where(array('position_id'=>$data['car_position_id']))->data($park_position_set)->save();
                }
                if($house_village_park_config['park_sys_type'] == 'D6'){
                    //todo D6智慧停车 触发下发到设备
                    invoke_v20_service('\app\community\model\service\Park\D6Service/d6synVehicle',[
                        'village_id'=>$this->village_id,
                        'car_id'=>$result
                    ]);
                }
                $this->ajaxReturn(array('code'=>'1','msg'=>'添加车辆信息成功!','car_id' => $result));
            }else{
                $this->ajaxReturn(array('code'=>'2','msg'=>'添加车辆信息失败'));
            }
        }
        else{
            $source = 'car';
            $single = 0;
            $floor_id = 0;
            $layer_id = 0;
            $vacancy_id = 0;
            $user_id=0;
            $user_bind=[
                'name'=>'',
                'phone'=>''
            ];
            if(isset($_GET['source']) && $_GET['source']){
                $source = $_GET['source'];
                $single = $_GET['single'];
                $floor_id = $_GET['floor_id'];
                $layer_id = $_GET['layer_id'];
                $vacancy_id = $_GET['vacancy_id'];
                $user_id = $_GET['user_id'];
                $user_bind=M('House_village_user_bind')->where(['pigcms_id'=>$user_id])->field('name,phone')->find();
            }
            $this->assign('source',$source);
            $this->assign('single',$single);
            $this->assign('floor_id',$floor_id);
            $this->assign('layer_id',$layer_id);
            $this->assign('vacancy_id',$vacancy_id);
            $this->assign('user_id',$user_id);
            $this->assign('user_bind',$user_bind);

            $where['village_id'] = $this->village_id;
            $info_list = D('House_village_parking_position')->get_parking_select($where);
            $this->assign('info_list', $info_list);
            $this->assign('parking_car_type_arr', $parking_car_type_arr);

        }
        $city_arr = array('京', '津', '冀', '晋', '蒙', '辽', '吉', '黑', '沪', '苏', '浙', '皖', '闽', '赣', '鲁', '豫', '鄂', '湘', '粤', '桂', '琼', '渝', '川', '贵', '云', '藏', '陕', '甘', '青', '宁', '新');

        $garage_list = D('House_village_parking_garage')->get_garage_list(true, array('village_id' => $this->village_id));
        $this->assign('garage_list', $garage_list);
        $this->assign('city_arr', $city_arr);

        $village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        $this->assign('village_park_config', $village_park_config);
        $this->assign('new_charge_time', $is_new_charge);
        $this->assign('car_color_list', $this->car_color);
        $this->assign('relationship', $this->relationship);
        $this->assign('car_type_arr', $this->car_type_arr);
        $this->display();
    }

    /**
     * [vehice_edit 车辆信息修改]
     * @return [type] [description]
     */
    public function vehicle_edit()
    {
        $house_village_park_config = M('House_village_park_config')->where(array('village_id' => $this->village_id))->find();
        if ($house_village_park_config['park_sys_type'] && $house_village_park_config['park_sys_type'] == 'D1') {
            $parking_car_type_arr = D('House_village_parking_car')->parking_d1_car_type_arr;
        } else {
            $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
        }
        if ($house_village_park_config['is_park_month_type'] != 1) {
            unset($parking_car_type_arr[1], $parking_car_type_arr[2], $parking_car_type_arr[3], $parking_car_type_arr[4]);
        }
        if ($house_village_park_config['is_temporary_park_type'] != 1) {
            unset($parking_car_type_arr[13], $parking_car_type_arr[14], $parking_car_type_arr[15], $parking_car_type_arr[16]);
        }

        //todo 【新版收费-车辆】
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        if (IS_POST) {
            //车辆管理-编辑 权限
            if (!in_array(57, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

//            $data['car_number'] = strtoupper(htmlspecialchars(trim($_POST['car_number'])));

            $binding_type=intval($_POST['binding_type']);
			$data['car_type'] = intval($_POST['car_type']);

            $data['car_position_id'] = htmlspecialchars(trim($_POST['car_position_id']));
            $data['car_position_id_path'] = htmlspecialchars(trim($_POST['car_position_id_path']));
            $data['car_stop_num'] = htmlspecialchars(trim($_POST['car_stop_num']));
            $data['car_user_name'] = htmlspecialchars(trim($_POST['car_user_name']));
            $data['car_user_phone'] = htmlspecialchars(trim($_POST['car_user_phone']));
            $data['car_displacement'] = htmlspecialchars(trim($_POST['car_displacement']));
            $data['car_id'] = intval($_POST['car_id']);
//            $data['province'] = htmlspecialchars(trim($_POST['province']));
            $data['village_id'] = $this->village_id;
            $data['charge_type_id'] = $_POST['charge_type_id'];
//            $data['store_value_car_type'] = htmlspecialchars(trim($_POST['store_value_car_type']));
//            $data['temporary_car_type'] = htmlspecialchars(trim($_POST['temporary_car_type']));
            $data['car_color'] = htmlspecialchars(trim($_POST['car_color']));
            $data['equipment_no'] = htmlspecialchars(trim($_POST['equipment_no']));
            if ($_POST['end_time'] && $_POST['end_time'] != '') {
                $data['end_time'] = strtotime($_POST['end_time'].'23:59:59');
            }
            if ($_POST['parking_car_type'] && $_POST['parking_car_type'] > 0) {
                $data['parking_car_type'] = $_POST['parking_car_type'];
            }

            $order=[
                'village_id'=>$this->village_id,
                'property_id'=>$this->house_session['property_id'],
                'end_time'=>$data['end_time'],
                'position_id'=>intval($data['car_position_id'])
            ];
            if(!$data['car_id'] && $data['car_id'] == ''){
                $this->ajaxReturn(array('code'=>'2','msg'=>'车牌id不能为空'));
            }

//            if(strlen($data['car_number']) >8){
//                $this->ajaxReturn(array('code'=>'2','msg'=>'车牌号最多输入8位'));
//            }
            if($is_new_charge == 0){
                if (empty($data['car_user_name'])) {
                    $this->ajaxReturn(array('code'=>'2','msg'=>'请输入车主姓名！'));
                }

                if (empty($data['car_user_phone'])) {
                    $this->ajaxReturn(array('code'=>'2','msg'=>'请输入车主手机号！'));
                }

                if(!preg_match('/^[0-9]{11}$/',$data['car_user_phone'])){
                    $this->ajaxReturn(array('code'=>'2','msg'=>'请输入正确的手机号！'));
                }
            }



//            $res = D('House_village_parking_car')->get_parking_car_one(array('car_number'=>$data['car_number'],'province'=>$data['province'],'village_id'=> $this->village_id));
//            if($res && $res['car_id'] != $data['car_id']){
//                $this->ajaxReturn(array('code'=>'2','msg'=>'车牌号已存在'));
//            }
            $car_info = D('House_village_parking_car')->where(['car_id' => $data['car_id']])->field('car_id,car_number,room_id,end_time,room_id')->find();
//            if ($car_info && $data['end_time'] && intval($data['end_time'])<$car_info['end_time']) {
//                $this->ajaxReturn(array('code'=>'2','msg'=>'到期时间不得小于当前到期时间'));
//            }
            if ($data['end_time'] && intval($data['end_time'])<time()) {
                $this->ajaxReturn(array('code'=>'2','msg'=>'到期时间不得小于当前时间'));
            }

            $order['car_id']=$data['car_id'];
            $village_user_bind=[];
            if($is_new_charge == 1){
                $user_bind= D('House_village_bind_car')->alias('c')
                    ->join('LEFT JOIN pigcms_house_village_user_bind as b on b.pigcms_id=c.user_id')
                    ->field('b.pigcms_id,b.name,b.phone')
                    ->where([
                        'c.village_id'=>$this->village_id,
                        'c.car_id'=>$car_info['car_id'],
                        'c.user_id' => ['gt', 0]
                    ])
                    ->order('c.id asc')
                    ->select();
                if(!$user_bind){
                    //todo 不存在绑定关系
                    if(!$binding_type){
                        $this->ajaxReturn(array('code'=>'2','msg'=>'请选择绑定对象'));
                    }
                    if($binding_type == 1){ //todo 绑定对象是房间
                        if(!isset($_POST['room_data'][3]) || empty($_POST['room_data'][3])){
                            $this->ajaxReturn(array('code'=>'2','msg'=>'请选择绑定精确到房间'));
                        }
                        $village_user_bind=D('House_village_user_bind')->where([
                            'village_id'=>$this->village_id,
                            'vacancy_id'=>$_POST['room_data'][3],
                            'type'=>['in',[0,1,2,3]],
                            'status'=>1
                        ])->getField('pigcms_id',true);
                        if(empty($village_user_bind)){
                            $this->ajaxReturn(array('code'=>'2','msg'=>'该房间暂无人员'));
                        }
                        $user_name=D('House_village_user_bind')->getUserBindData($village_user_bind);
                        if($user_name['error']){
                            $data['car_user_name'] = $user_name['data']['name'];
                            $data['car_user_phone']=$user_name['data']['phone'];
                        }
                        $data['room_id']=$_POST['room_data'][3];
                        $order['room_id']= $data['room_id'];
                    }else{ //todo 绑定对象是业主
                        if(empty($_POST['obj_id'])){
                            $this->ajaxReturn(array('code'=>'2','msg'=>'业主手机号不存在！'));
                        }
                        if (empty($data['car_user_name'])) {
                            $this->ajaxReturn(array('code'=>'2','msg'=>'请输入车主姓名！'));
                        }
                        if (empty($data['car_user_phone'])) {
                            $this->ajaxReturn(array('code'=>'2','msg'=>'请输入车主手机号！'));
                        }
                        if(!preg_match('/^[0-9]{11}$/',$data['car_user_phone'])){
                            $this->ajaxReturn(array('code'=>'2','msg'=>'请输入正确的手机号！'));
                        }
                        $user_bind=D('House_village_user_bind')->where([
                            'village_id'=>$this->village_id,
                            'pigcms_id'=>intval($_POST['obj_id']),
                            'type'=>['in',[0,1,2,3]],
                            'status'=>1
                        ])->field('pigcms_id,vacancy_id')->find();
                        if(empty($user_bind)){
                            $this->ajaxReturn(array('code'=>'2','msg'=>'该房间暂无业主'));
                        }
                        $village_user_bind[]=$user_bind['pigcms_id'];
                        $order['room_id']=$user_bind['vacancy_id'];
                    }

                }
                else{
                    //todo 存在绑定关系
                    if($binding_type == 1){ //todo 绑定对象是房间
                        if(isset($_POST['room_data'][3]) && !empty($_POST['room_data'][3])){
                            $village_user_bind=D('House_village_user_bind')->where([
                                'village_id'=>$this->village_id,
                                'vacancy_id'=>$_POST['room_data'][3],
                                'type'=>['in',[0,1,2,3]],
                                'status'=>1
                            ])->getField('pigcms_id',true);
                            if(empty($village_user_bind)){
                                $this->ajaxReturn(array('code'=>'2','msg'=>'该房间暂无人员'));
                            }
                            $user_name=D('House_village_user_bind')->getUserBindData($village_user_bind);
                            if($user_name['error']){
                                $data['car_user_name'] = $user_name['data']['name'];
                                $data['car_user_phone']=$user_name['data']['phone'];
                            }
                            $data['room_id']=$_POST['room_data'][3];
                            $order['room_id']= $data['room_id'];
                        }
                    }else{ //todo 绑定对象是业主
                        if($_POST['obj_id']){
                            if(preg_match('/^[0-9]{11}$/',$data['car_user_phone'])){
                                $user_bind=D('House_village_user_bind')->where([
                                    'village_id'=>$this->village_id,
                                    'pigcms_id'=>intval($_POST['obj_id']),
                                    'type'=>['in',[0,1,2,3]],
                                    'status'=>1
                                ])->field('pigcms_id,vacancy_id')->find();

                                if(empty($user_bind)){
                                    $this->ajaxReturn(array('code'=>'2','msg'=>'该房间暂无业主'));
                                }
                                if(empty($user_bind)){
                                    $this->ajaxReturn(array('code'=>'2','msg'=>'该房间暂无业主'));
                                }
                                $village_user_bind[]=$user_bind['pigcms_id'];
                                $order['room_id']=$user_bind['vacancy_id'];
                                $data['room_id']=0;
                            }
                        }
                    }
                }
                //todo 车位号为空是 为临时车
              if(empty($data['car_position_id']) && empty($car_info['car_position_id'])){
                  $postData['garage_id'] = 9999;
//                  $postData['position_status'] = 2;
                  $postData['position_num'] = $data['car_number'];
                  $postData['position_type'] = 3;
                  $postData['village_id'] = $this->village_id;
                  $postData['start_time'] = time();
                  $postData['end_time'] = $data['end_time'];
                  $position_id=D('House_village_parking_position')->add($postData);
                  $data['car_position_id']=$position_id;
              }
                if(empty($order['room_id'])){
                    if(!empty($car_info['room_id'])){
                        $order['room_id']=$car_info['room_id'];
                    }else{
                        $bind_car=D('house_village_bind_car')->alias('c')
                            ->join('LEFT JOIN pigcms_house_village_user_bind as b on b.pigcms_id=c.user_id')
                            ->field('b.vacancy_id')
                            ->where([
                                'c.village_id'=>$this->village_id,
                                'c.car_id'=>$car_info['car_id'],
                                'c.user_id' => ['gt', 0]
                            ])->find();
                        if($bind_car){
                            $order['room_id']=$bind_car['vacancy_id'];
                        }
                    }
                }

            }

            if($data['car_stop_num']){
                $res = D('House_village_parking_car')->get_parking_car_one(array('car_stop_num'=>$data['car_stop_num'],'village_id'=> $this->village_id));
                if ($res && $res['car_id'] != $data['car_id']) {
                    $this->ajaxReturn(array('code'=>'2','msg'=>'停车卡号已存在'));
                }
            }
            // 如果添加了到期时间 且 到期时间大于当前时间 且开启了智慧停车功能  同步至设备
            $park_versions = $house_village_park_config['park_versions'];
            $is_park_month_type = $house_village_park_config['is_park_month_type'];
            $car_msg = D('House_village_parking_car')->where(array('car_id'=>$_POST['car_id']))->find();
            if ($data['car_position_id']>0 && $data['car_position_id']!=$car_msg['car_position_id']) {
                $parking_position = D('House_village_parking_position')->where(array('position_id'=>$data['car_position_id']))->find();


                //todo 停车费版本开启新版
                $park_versions = M('House_village_park_config')->where(array('village_id'=>$this->village_id))->getField('park_versions');
                $park_num = D('House_village_parking_car')->where(['car_position_id' => $data['car_position_id'],'car_id'=>['neq', $data['car_id']]])->count();
                if ($park_num>1 && $parking_position && 2!=$parking_position['position_pattern'] && $parking_position['end_time'] && intval($data['end_time'])<$parking_position['end_time'] && $park_versions == 2 && $is_new_charge == 0) {

                    $this->ajaxReturn(array('code'=>'2','msg'=>'到期时间不得小于当前停车位到期时间，到期时间：'.date('Y-m-d', $parking_position['end_time'])));
                } elseif ($data['end_time']) {
                    $parking_position['end_time'] = $data['end_time'];
                }

//                $park_num = D('House_village_parking_car')->where(['car_position_id' => $data['car_position_id'],'car_id'=>['neq', $data['car_id']]])->count();
//                if (1==$is_park_month_type && 2==$park_versions && $parking_position && 2!=$parking_position['position_pattern'] && intval($parking_position['park_num'])>0 && intval($park_num)>=intval($parking_position['park_num'])) {
//                    $this->ajaxReturn(array('code'=>'2','msg'=>'当前对应停车位绑定车辆数量已满，限制：'.intval($parking_position['park_num']).'(辆)'));
//                }
            } elseif ($data['car_position_id']) {
                $parking_position = D('House_village_parking_position')->where(array('position_id'=>$data['car_position_id']))->find();

            }
                $data['car_brands']=$_POST['car_brands'];
                $data['examine_status']=$_POST['examine_status'];
                $data['examine_response']=$_POST['examine_response'];
                $data['examine_time']=time();

            $result = D('House_village_parking_car')->parking_car_save($data);
            if($result !== false){

                if($is_new_charge == 1){
                    //todo 写入绑定关系
                    if($village_user_bind){
                        D('House_village_bind_car')->where(array('village_id'=>$this->village_id,'car_id'=>$_POST['car_id']))->delete();
                        $userArr = D('House_village_user_bind')->where(array('pigcms_id' => ['in',$village_user_bind]))->getField('pigcms_id,uid');
                        foreach ($village_user_bind as $v){
                            $dataBinds = array('village_id'=>$this->village_id,'user_id'=>$v,'car_id'=>$_POST['car_id']);
                            if (isset($userArr[$v])) {
                                $dataBinds['uid'] = intval($userArr[$v]);
                            }
                            D('House_village_bind_car')->data($dataBinds)->add();

                        }
                    }
                    if ($park_versions && 2==$park_versions && $parking_position) {
                        $park_system = M('Park_system')->where(array('park_id' => $this->village_id))->find();
                        try {
                            D('House_village_parking_car')->carToDevice($data['car_id'], $data, $this->village_id, $park_versions, $park_system, $parking_position,$house_village_park_config);
                        } catch (\Exception $e) {
                            $this->ajaxReturn(array('code'=>'2','msg'=>$e->getMessage()));
                        }
                    }
                    if($data['car_position_id'] && $data['end_time']){
                        $park_position_set = [
                            'end_time' => $data['end_time']
                        ];
                        D('House_village_parking_position')->where(array('position_id'=>$data['car_position_id']))->data($park_position_set)->save();
                    }
                }else{
                    if ($_POST['user_id']) {
                        $whereCars = array(
                            'village_id'=>$this->village_id,
                            'car_id'=>$_POST['car_id'],
                        );
                        $bind_car = D('House_village_bind_car')->where($whereCars)->find();
                        $userCarUid = D('House_village_user_bind')->where(array('pigcms_id' => $_POST['user_id']))->getField('uid');
                        if($bind_car && $bind_car['user_id'] != $_POST['user_id']){
                            // 如果绑定住户不一致以最新的为准
                            $dataUserCarSaves = array(
                                'uid'=>intval($userCarUid),
                                'user_id'=>$_POST['user_id'],
                                'relationship'=>$_POST['relationship']
                            );
                            D('House_village_bind_car')->where($whereCars)->data($dataUserCarSaves)->save();
                        } elseif (empty($bind_car)){
                            $dataUserCars = array(
                                'village_id'=>$this->village_id,
                                'user_id'=>$_POST['user_id'],
                                'car_id'=>$_POST['car_id'],
                                'relationship'=>$_POST['relationship']
                            );
                            D('House_village_bind_car')->data($dataUserCars)->add();
                        }
                    }
                    // 绑定了车辆和车位 查询下车位上是否关联了收费项 关联了  自动对应关系
                    if ($data['car_position_id']) {
                        $now_time = intval(time());
                        $where_standard_bind = [
                            'position_id' => $data['car_position_id'],
                            'end_time' => ['gt', $now_time],
                        ];
                        $payment_standard_bind = M('House_village_payment_standard_bind')->where($where_standard_bind)->find();
                        if ($payment_standard_bind) {
                            // 该车位绑定了收费 添加了车辆绑定业主后自动管理对应业主
                            $save_set = [
                                'pigcms_id' => $_POST['user_id']
                            ];
                            M('House_village_payment_standard_bind')->where($where_standard_bind)->save($save_set);
                        }
                    }
                }
                $order['is_new_charge']=$is_new_charge;
                D('House_village_parking_car')->bindOrderLog($order);
                // 对应车位改为已使用
                if ( $data['car_position_id']) {
//                    M('House_village_parking_position')->where(['position_id' => $data['car_position_id']])->setField('position_status',2);
                }
                if ($park_versions && 2==$park_versions && $parking_position &&$data['examine_status']==1) {
                    $park_system = M('Park_system')->where(array('park_id' => $this->village_id))->find();
                    try {
                        D('House_village_parking_car')->carToDevice($data['car_id'], $data, $this->village_id, $park_versions, $park_system, $parking_position,$house_village_park_config);
                    } catch (\Exception $e) {
                        $this->ajaxReturn(array('code'=>'2','msg'=>$e->getMessage()));
                    }
                }
                if($data['car_position_id'] && $data['end_time']) {
                    $park_position_set = [
                        'end_time' => $data['end_time']
                    ];
                    D('House_village_parking_position')->where(array('position_id'=>$data['car_position_id']))->data($park_position_set)->save();
                }

                if($house_village_park_config['park_sys_type'] == 'D6'){
                    //todo D6智慧停车 触发下发到设备
                     invoke_v20_service('\app\community\model\service\Park\D6Service/d6synVehicle',[
                        'village_id'=>$this->village_id,
                        'car_id'=>$data['car_id']
                    ]);
                }
                $this->ajaxReturn(array('code'=>'1','msg'=>'修改成功!'));
            }else{
                $this->ajaxReturn(array('code'=>'2','msg'=>'修改失败!&nbsp;&nbsp;请检查是否车牌号码或停车卡号已存在!'));
            }
        }
        else{
            //车辆管理-查看 权限
            if (!in_array(55, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $car_id = trim($_GET['car_id']);
            if (!$car_id && $car_id='') {
                $this->error('错误的车辆信息');
            }
            $info_list = D('House_village_parking_car')->get_parking_car_one(array('car_id'=>$car_id,'village_id'=>$this->village_id));
            $position_info = D('House_village_parking_position')->get_one(array('position_id'=>$info_list['car_position_id']));
            $car_position_id_arr = explode(',',$info_list['car_position_id_path']);
            $position_info_path = D('House_village_parking_position')->where(array('position_id'=>['in',$car_position_id_arr]))->field('position_id,position_num')->select();
            $position_num = '';
            $position_id = '';
            if($position_info_path) {
                $position_num_arr = array_column($position_info_path,'position_num');
                $position_id_arr  = array_column($position_info_path,'position_id');
                $position_num = implode(';', $position_num_arr);
                $position_id = implode(',', $position_id_arr);
            }
            $position_arr_info['position_num'] =$position_num;
            $position_arr_info['position_id'] =$position_id;
            $this->assign('position_arr_info',$position_arr_info);

            $this->assign('position_info',$position_info);
            $this->assign('info_list',$info_list);
            $bind_car = D('House_village_bind_car')->where(array('village_id'=>$this->village_id,'car_id'=>$car_id, 'user_id' => ['gt', 0]))->order('id desc')->find();
            $this->assign('bind_car',$bind_car);


            if($is_new_charge == 1){
                //todo 查询该车辆下所有用户
                $user_bind= D('House_village_bind_car')->alias('c')
                    ->join('LEFT JOIN pigcms_house_village_user_bind as b on b.pigcms_id=c.user_id')
                    ->field('b.pigcms_id,b.name,b.phone')
                    ->where([
                        'c.village_id'=>$this->village_id,
                        'c.car_id'=>$car_id,
                        'c.user_id' => ['gt', 0]
                    ])
                    ->order('c.id asc')
                    ->select();
                $this->assign('user_bind',$user_bind);
            }elseif ($bind_car['user_id']) {
                    $user_bind = D('House_village_user_bind')->where(['pigcms_id' => $bind_car['user_id']])->field('pigcms_id,name,phone,status')->find();
                    // 0 禁止 1正常 2审核中 3/4删除
                    $status_arr = [
                        '0' => [
                            'txt' => '该业主已禁止，请重新绑定',
                            'color' => 'orange',
                        ],
                        '1' => [
                            'txt' => '正常',
                            'color' => 'green',
                        ],
                        '2' => [
                            'txt' => '审核中',
                            'color' => '#D1DF86',
                        ],
                        '3' => [
                            'txt' => '该业主已删除，请重新绑定',
                            'color' => 'red',
                        ],
                        '4' => [
                            'txt' => '该业主已删除，请重新绑定',
                            'color' => 'red',
                        ],
                    ];
                    $this->assign('status_txt',$status_arr[$user_bind['status']]);
                    $this->assign('user_bind',$user_bind);
                }
            $this->assign('parking_car_type_arr',$parking_car_type_arr);
        }

        $garage_list = D('House_village_parking_garage')->get_garage_list(true,array('village_id'=>$this->village_id));
        $this->assign('garage_list',$garage_list);

        $village_park_config = M('House_village_park_config')->where(array('village_id'=>$this->village_id))->find();
        $this->assign('village_park_config',$village_park_config);

        $city_arr = array('京','津','冀','晋','蒙','辽','吉','黑','沪','苏','浙','皖','闽','赣','鲁','豫','鄂','湘','粤','桂','琼','渝','川','贵','云','藏','陕','甘','青','宁','新');
        $this->assign('city_arr',$city_arr);
        $this->assign('car_color_list',$this->car_color);

        $this->assign('relationship',$this->relationship);

        $this->assign('new_charge_time',$is_new_charge);
        $this->assign('car_type_arr', $this->car_type_arr);
        $this->display();
    }

    /**
     * [vehicle_detail 车辆详情]
     * @return [type] [description]
     */
    public function vehicle_detail(){
        //车辆管理-查看绑定用户 权限
        if (!in_array(59, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $where=array();
        $car_id = intval(trim($_GET['car_id']));
        if ($car_id  &&  $car_id!=='') {
            $where['car_id'] = $car_id;
        }
        $where['user_id'] = ['gt', 0];
        $bind_list = D('House_village_bind_car')->get_bind_car_select('',$where);//车辆住户绑定关系
        // $field='a.*,b.position_num';
        // $join='join pigcms_house_village_parking_position as b on a.car_position_id=b.position_id';
        // $info_list = D('House_village_parking_car')->get_parking_car_info($field,$where,$join);
        // if($info_list == false){
        //     $this->error('获取信息错误!');
        // }
        $model = D('House_village_user_bind');
        $field='a.pigcms_id,a.name,a.phone,a.room_addrss,b.floor_name,b.floor_layer';
        $join='join '.C('DB_PREFIX').'house_village_floor as b on a.floor_id=b.floor_id';

        foreach ($bind_list as $key => $value) {
            $condition['a.pigcms_id'] = $value['user_id'];
            $condition['a.village_id'] = $this->village_id;
            $data_list[$key] = $model->get_user_bind_info($field,$join,$condition);
//            $data_list[$key]['car_id'] = $info_list['car_id'];
            $data_list[$key]['id'] = $value['id'];
        }
        // $this->assign('info_list',$info_list);
        $this->assign('data_list',$data_list);
        $this->display();

    }

    /**
     * [bind_position_del 解除车辆绑定]
     * @return [type] [description]
     */
    public function unbind_car(){
        //车辆管理-解绑 权限
        if (!in_array(256, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $bind_id = intval(trim($_POST['bind_id']));

        if(!$bind_id || $bind_id ==''){
            $this->ajaxReturn(array('code'=>2,'msg'=>'参数传递错误!'));
        }else{
            $where['id'] = $bind_id;
        }
        $result = D('House_village_bind_car')->del_bind_car($where);//解除
        if($result !== false){
            $this->ajaxReturn(array('code'=>1,'msg'=>'解绑成功!'));
        }else{
            $this->ajaxReturn(array('code'=>2,'msg'=>'解绑失败!'));
        }
    }

     /* 导出车辆excel函数*/
    public function vehicle_export($name='Excel'){
        //车辆管理-导出 权限
        if (!in_array(263, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        error_reporting(E_ALL);
//        date_default_timezone_set('Europe/London');
        date_default_timezone_set('asia/shanghai');
        $objExcel = new PHPExcel();

        $title = $this->village['village_name'] . '社区-车辆列表';
        /*以下是一些设置 ，什么作者  标题之类的*/
         $objExcel->getProperties()->setCreator($title)
           ->setLastModifiedBy($title)
           ->setTitle($title)
           ->setSubject("数据EXCEL导出")
           ->setDescription("备份数据")
           ->setKeywords("excel")
           ->setCategory("result file");

         /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
         $where=array();
        if ($_GET['search_type'] && $_GET['search_value']) {
            switch ($_GET['search_type']) {
                case '1'://车牌号
                    $where['a.car_number'] = array('like','%'.$_GET['search_value'].'%');
                    break;
                case '2': //停车卡号
                    $where['a.car_stop_num'] =array('like','%'.$_GET['search_value'].'%');
                    break;
                case '3'://车位号
                    $where['b.position_num'] = array('like','%'.$_GET['search_value'].'%');
                    break;
                case '4'://车主姓名
                    $where['a.car_user_name'] = array('like','%'.$_GET['search_value'].'%');
                    break;
                case '5'://车主手机号
                    $where['a.car_user_phone'] = array('like','%'.$_GET['search_value'].'%');
                    break;
            }
        }

        $where['a.village_id'] = $this->village_id;
        $join=' left join pigcms_house_village_parking_position as b on a.car_position_id=b.position_id';
        $field='a.*,b.position_num';
        $order='a.car_addtime desc';

        // import('@.ORG.merchant_page');
        $count = D('House_village_parking_car')->alias('a')->where($where)->join($join)->count();
        $data = D('House_village_parking_car')->get_parking_car_list($field,$where,$join,$order,9999999);
        $length = ceil($count / 1000);
        $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
        $house_village_park_config = M('House_village_park_config')->where(array('village_id'=>$this->village_id))->find();

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle($title);
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '所属'.C('config.house_name'));
            $objActSheet->setCellValue('B1', '车位编号');
            $objActSheet->setCellValue('C1', '车牌号');
            $objActSheet->setCellValue('D1', '停车卡号');
            $objActSheet->setCellValue('E1', '车主姓名');
            $objActSheet->setCellValue('F1', '车主手机号');
            if (2==$house_village_park_config['park_versions']) {
                $objActSheet->setCellValue('G1', '停车到期时间');
                $objActSheet->setCellValue('H1', '停车卡类');
            }
            $field='village_name';
            $village_id = $this->village_id;
            $village_name = D('House_village')->get_village_info($village_id,$field);
            if (!empty($data)) {
                $index = 2;
                for ($k = $i*1000; $k < ($i+1)*1000; $k++) {
                    if (!$data['info_list'][$k]['car_id']) {
                       break;
                    }
                    $objActSheet->setCellValueExplicit('A' . $index, $village_name['village_name']);
                    $objActSheet->setCellValueExplicit('B' . $index, $data['info_list'][$k]['position_num']);
                    $objActSheet->setCellValueExplicit('C' . $index, $data['info_list'][$k]['province'].$data['info_list'][$k]['car_number']);
                    $objActSheet->setCellValueExplicit('D' . $index, $data['info_list'][$k]['car_stop_num']);
                    $objActSheet->setCellValueExplicit('E' . $index, $data['info_list'][$k]['car_user_name']);
                    $objActSheet->setCellValueExplicit('F' . $index, $data['info_list'][$k]['car_user_phone']);
                    if (2==$house_village_park_config['park_versions']) {
                        if ($data['info_list'][$k]['end_time']) {
                            $end_time = date('Y-m-d H:i:s', $data['info_list'][$k]['end_time']);
                            $objActSheet->setCellValueExplicit('G' . $index, $end_time);
                        } else {
                            $objActSheet->setCellValueExplicit('G' . $index, '-');
                        }
                        if ($data['info_list'][$k]['parking_car_type']) {
                            $parking_car_type = $parking_car_type_arr[$data['info_list'][$k]['parking_car_type']]?$parking_car_type_arr[$data['info_list'][$k]['parking_car_type']]:'-';
                            $objActSheet->setCellValueExplicit('H' . $index, $parking_car_type);
                        } else {
                            $objActSheet->setCellValueExplicit('H' . $index, '-');
                        }
                    }
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
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }


    /**
     * [car_del 车辆信息删除]
     * @return [type] [description]
     */
    public function car_del(){
        //车辆管理-删除 权限
        if (!in_array(58, $this->house_session['menus'])) {
            $this->ajaxReturn(array('code'=>2,'msg'=>'对不起，您没有权限执行此操作'));
        }

        $id = trim($_POST['car_id']);
        if(!$id && $id=''){
            $this->ajaxReturn(array('code'=>2,'msg'=>'获取删除对象失败!'));
        }
        $id_arr = explode(',',$id);
        $village_id = $this->village_id;
        $park_config_info = M('House_village_park_config')->where(array('village_id' => $this->village_id))->field('park_versions,park_sys_type')->find();
        foreach ($id_arr as $val){
            if($park_config_info['park_versions'] == 2 && $park_config_info['park_sys_type'] == 'D5'){
                $result=invoke_v20_service('\app\community\model\service\HouseVillageParkingService/del_d5',['village_id'=>$village_id,'car_id'=>$val]);
                if(isset($result['data']['retval']['error']) || !$result['data']['retval']['error']){
                    $this->ajaxReturn(array('code'=>2,'msg'=>$result['data']['retval']['msg']));
                    continue;
                }
            }elseif ($park_config_info['park_versions'] == 2 && $park_config_info['park_sys_type'] == 'D6'){
                //todo D6智慧停车 删除下发到设备
                invoke_v20_service('\app\community\model\service\Park\D6Service/d6synVehicle',[
                    'village_id'=>$this->village_id,
                    'car_id'=>$val,
                    'is_del'=>1
                ]);
            }
            $res = D('House_village_bind_car')->get_bind_car_one(array('car_id'=>$val,'user_id' => ['gt', 0]));
            /*if($res){
                continue;
                //$this->ajaxReturn(array('code'=>2,'msg'=>'请先解绑该车辆绑定的住户!'));
            }*/
            $car_info = D('House_village_parking_car')->get_parking_car_one(['car_id'=>$val]);

            $result = D('House_village_parking_car')->parking_car_del(['car_id'=>$val], $village_id);

            //todo 同步删除临时车位
            if(!empty($car_info['car_position_id'])){
                $parking_position=D('House_village_parking_position')->where(['position_id'=>$car_info['car_position_id']])->find();
                if($parking_position){
                    if(intval($parking_position['garage_id']) == 9999){
                        D('House_village_parking_position')->where(['position_id'=>$parking_position['position_id']])->delete();
                    }
                }
            }

            if($result){
                if ($res){
                    $zz=D('House_village_bind_car')->where(array('car_id'=>$val,'user_id' => ['gt', 0]))->delete();
                }
                $info = D('House_village_parking_car')->get_parking_car_one(['car_position_id'=>$car_info['car_position_id']]);
                if(empty($info)){
                    D('House_village_parking_position')->parking_position_save(['position_id'=>$car_info['car_position_id'],'position_status'=>1]);
                }
            }

        }
        $this->ajaxReturn(array('code'=>1,'msg'=>'删除成功,未删除掉的请先解绑该车辆绑定的住户!'));
    }


    public function one_bind_del(){


        $id = trim($_POST['car_id']);
        if(!$id && $id=''){
            $this->ajaxReturn(array('code'=>2,'msg'=>'获取删除对象失败!'));
        }
        $where['car_id'] = array('in',$id);
        $res = D('House_village_bind_car')->where(array('car_id'=>$id))->delete();
        if($res !== false){
            $this->ajaxReturn(array('code'=>1,'msg'=>'删除绑定成功!'));
        }else{
            $this->ajaxReturn(array('code'=>2,'msg'=>'删除绑定失败!'));
        }
    }





    /**
     * [deposit_management 押金管理]
     * @return [type] [description]
     */
    public function deposit_management(){
        //押金管理-查看 权限
        if (!in_array(41, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if ($_GET['is_refund'] && $_GET['is_refund'] !=='') {//押金状态
            $where['is_refund'] = trim($_GET['is_refund']);
        }

        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
        $name = $_GET['name'];
        $vacancy_id = intval($_GET['vacancy_id']);

        if ($begin_time && !$end_time) {
            $where['a.pay_time'] = array('gt', strtotime($begin_time));
        }

        if (!$begin_time && $end_time) {
            $where['a.pay_time'] = array('lt', strtotime($end_time.' 23:59:59'));
        }

        if ($begin_time && $end_time) {
            $where['a.pay_time'] = array('between', array(strtotime($begin_time), strtotime($end_time.' 23:59:59')));
        }
        if ($_GET['single_id']) {
            $where['b.single_id'] = intval($_GET['single_id']);
        }
        if ($_GET['floor_id']) {
            $where['b.floor_id'] = intval($_GET['floor_id']);
        }
        if ($_GET['layer_id']) {
            $where['b.layer_id'] = intval($_GET['layer_id']);
        }
        if ($vacancy_id) {
            $where['b.vacancy_id'] = $vacancy_id;
//            $where['_string'] = "a.room_num LIKE '%{$room_num}%' OR b.room LIKE '%{$room_num}%' OR b.room_number LIKE '%{$room_num}%'";
        }
        if ($name) {
            $where['b.name'] = array('like','%'.$name.'%');
        }
        $where['a.village_id'] = $this->village_id;
        $where['a.is_del'] = 1;

        $field='a.*,b.name,b.room_addrss,b.name,b.room_number,c.name as pay_name,d.realname,d.account,b.single_id,b.floor_id,b.floor_id,b.layer_id,b.vacancy_id';
        $join='left join '.C('DB_PREFIX').'house_village_user_bind as b on a.pigcms_id=b.pigcms_id left join '.C('DB_PREFIX').'house_village_pay_type as c on a.pay_type=c.id left join '.C('DB_PREFIX').'house_admin as d on a.role_id=d.id';
        $order='a.pay_time desc';
        $info_list = D('House_village_deposit')->get_deposit_list($field,$join,$where,$order);
        if ($info_list['info_list']) {
            $db_house_village = D('House_village');
            foreach ($info_list['info_list'] as &$item) {
                if ($item['single_id'] && $item['floor_id'] && $item['layer_id'] && $item['vacancy_id']) {
                    $address = $db_house_village->get_single_floor_room($item['single_id'],$item['floor_id'],$item['layer_id'],$item['vacancy_id'],$this->village_id);
                } else {
                    $address = $item['room_num'];
                }
                $item['address'] = $address?$address:'';
            }
        }
        $info_list['total'] = getFormatNumber($info_list['total']);
        //打印模板
        $print_template = D('House_village_print_template')->get_select(array('village_id'=>$this->village_id));
        $this->assign('print_template',$print_template);
        $this->assign('info_list',$info_list);


        //楼栋
        $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->order('sort desc, id asc')->select();
        if($single_list){
            $this->assign('single_list',$single_list);
        }else{
            $this->assign('single_list',[]);
        }
        if($_GET['vacancy_id']){
            $this->assign('vacancy_id',$_GET['vacancy_id']);
        }else{
            $this->assign('vacancy_id','');
        }
        $this->assign('village_id',$this->village_id);


        $this->display();
    }

    /**
     * [print_deposit 押金打印]
     * @return [type] [description]
     */
    public function print_deposit(){
        //押金管理-打印 权限
        if (!in_array(44, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $deposit_id = $_GET['deposit_id'] + 0;
        $template_id = $_GET['template_id'] + 0;
        $where = array(
            'a.deposit_id' => $deposit_id,
            'a.village_id' => $this->village_id
        );
        //获取详情
        $field='a.*,b.name,c.name as pay_name,b.usernum,b.phone,b.room_addrss';
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        if ($is_new_charge) {
            $join='left join '.C('DB_PREFIX').'house_village_user_bind as b on a.pigcms_id=b.pigcms_id left join '.C('DB_PREFIX').'house_new_offline_pay as c on a.pay_type=c.id';
        }else{
            $join='left join '.C('DB_PREFIX').'house_village_user_bind as b on a.pigcms_id=b.pigcms_id left join '.C('DB_PREFIX').'house_village_pay_type as c on a.pay_type=c.id';
        }
        $info_list = D('House_village_deposit')->get_deposit_one($field,$join,$where);
        //获取模板详情
        $house_village_print_template = D('House_village_print_template');

        $print_template = $house_village_print_template->get_one($template_id);
        $info_list['username'] = $info_list['name'];
        $info_list['order_name'] = $info_list['deposit_name'];
        $info_list['money'] = $info_list['payment_money'];
        $info_list['real_money'] = $info_list['actual_money'];
        $info_list['remarks'] = $info_list['deposit_note'];
        $info_list['pay_time'] = date("Y-m-d",$info_list['pay_time']);
        $info_list['totalMoney'] = C('config.Currency_symbol').$info_list['actual_money'].'（人民币大写：'.cny($info_list['actual_money']).'）';
        $info_list['case'] = cny($info_list['actual_money']);
        $info_list['desc'] = $print_template['desc'];
        $info_list['print_time'] = date('Y-m-d H:i:s');
        $info_list['payer'] = $info_list['name'];
        $info_list['room_num'] = isset($info_list['address'])?$info_list['address']:$info_list['room'];
        $info_list['village_name'] = $this->house_session['village_name'];
         //收款人
        if ($_SESSION['house']['role_id']) {
            $role_info = D('House_admin')->get_info_one(array('id'=>$_SESSION['house']['role_id']));
            $info_list['payee'] = $role_info['realname'] ? $role_info['realname'] : $role_info['account'];
        }else{
            $info_list['payee'] =  $_SESSION['house']['account'];
        }
        $info_list['payeeType']=$info_list['payee'];
        $info_list['pay_type_name']=$info_list['pay_name'];
        $info_list['price']=$info_list['deposit_balance'];

        $this->assign('info_list',$info_list);
        $this->assign('print_template',$print_template);
        $this->display();
    }



    /**
     * [deposit_add 添加押金]
     * @return [type] [description]
     */
    public function deposit_add(){
        //押金管理-添加 权限
        if (!in_array(42, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            $data['room_num'] = htmlspecialchars(trim($_POST['room_num']));
            $data['pay_type'] = htmlspecialchars(trim($_POST['pay_type']));
            $data['pigcms_id'] = htmlspecialchars(trim($_POST['pigcms_id']));
            if($_POST['bind_pigcms_id']){
                $data['pigcms_id']=$_POST['bind_pigcms_id'];
            }
            $data['payment_money'] = htmlspecialchars(trim($_POST['payment_money']));
            $data['actual_money'] = htmlspecialchars(trim($_POST['actual_money']));
            $data['deposit_balance'] = htmlspecialchars(trim($_POST['actual_money']));
            $data['deposit_note'] = htmlspecialchars(trim($_POST['deposit_note']));
            $data['deposit_name'] = htmlspecialchars(trim($_POST['deposit_name']));
            $data['pay_time'] = time();
            $data['role_id'] = !empty($_SESSION['house']['role_id']) ? $_SESSION['house']['role_id'] : 0 ;

            $data['village_id'] = $this->village_id;
            $result = D('House_village_deposit')->house_deposit_add($data);
            if($result !=false){
                $this->ajaxReturn(array('code'=>1,'msg'=>'添加成功!'));
            }else{
                $this->ajaxReturn(array('code'=>2,'msg'=>'添加失败!'));
            }
        }else{
            $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
            if ($is_new_charge) {
                $pay_type_list = M('House_new_offline_pay')->where(array('property_id'=>$this->house_session['property_id'],'status'=>1))->order('id desc')->select();
            }else{
                $pay_type_list = D('House_village_pay_type')->get_pay_type_select(array('village_id'=>$this->village_id));
            }
            $this->assign('pay_type_list',$pay_type_list);
        }
        $this->display();
    }

    /**
     * [deposit_del 押金删除]
     * @return [type] [description]
     */
    public function deposit_del(){
        //押金管理-删除 权限
        if (!in_array(43, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = trim($_POST['deposit_id']);
        if(!$id && $id=''){
            $this->ajaxReturn(array('code'=>2,'msg'=>'获取删除对象失败!'));
        }

        $where['deposit_id'] = array('in',$id);
        $result = D('House_village_deposit')->deposit_delete($where);
        if($result !== false){
            $this->ajaxReturn(array('code'=>1,'msg'=>'删除成功!'));
        }else{
            $this->ajaxReturn(array('code'=>1,'msg'=>'删除失败!'));
        }
    }

    /**
     * [deposit_refund 押金退款]
     * @return [type] [description]
     */
    public function deposit_refund(){
        //押金管理-退款 权限
        if (!in_array(252, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            $data['deposit_id'] = htmlspecialchars(trim($_POST['deposit_id']));
            $arr['refund_money'] = htmlspecialchars(trim($_POST['refund_money']));
            $data['refund_note'] = htmlspecialchars(trim($_POST['refund_note']));
            $data['refund_time'] = time();

            if($arr['refund_money'] < 0 || !is_numeric($arr['refund_money'])){
                $this->ajaxReturn(array('code'=>2,'msg'=>'请输入正确的退款金额!'));
            }

            $deposit_model = D('House_village_deposit');
            $res = $deposit_model->get_deposit_one('','',array('deposit_id'=>$data['deposit_id']));


            $data['deposit_balance'] = $res['actual_money'] - $res['refund_money'] - $arr['refund_money'];
            if($data['deposit_balance'] < 0){
                $this->ajaxReturn(array('code'=>2,'msg'=>'退款金额大于押金余额!'));
            }else{
                $data['is_refund'] = 2;
            }
            $data['refund_money'] = $arr['refund_money'] + $res['refund_money'];

            $result = $deposit_model->save($data);
            if($result !== false){
                $this->ajaxReturn(array('code'=>1,'msg'=>'退款成功!'));
            }else{
                $this->ajaxReturn(array('code'=>2,'msg'=>'退款失败!'));
            }

        }else{
            $where['deposit_id'] = trim($_GET['deposit_id']);
            $field='a.*,b.name';
            $join='join '.C('DB_PREFIX').'house_village_user_vacancy as b on a.pigcms_id=b.pigcms_id';
            $info_list = D('House_village_deposit')->get_deposit_one($field,$join,$where);
            $this->assign('info_list',$info_list);
        }
        $this->display();
    }

    /**
     * [count_management 统计管理]
     * @return [type] [description]
     */
    public function count_management(){
        //统计管理-查看 权限
        if (!in_array(64, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $user_bind_model = D('House_village_user_bind');
        //空置
        $room_count['total_count'] = D('House_village_user_vacancy')->get_user_vacancy_count(array('type'=>'0','is_del'=>'0','status'=>array('neq',3),'village_id'=>$this->village_id),'pigcms_id');
        $room_count['bind_count'] = D('House_village_user_vacancy')->get_user_vacancy_count(array('type'=>'0','is_del'=>'0','status'=>array('eq',3),'village_id'=>$this->village_id),'pigcms_id');
        //车位统计
       //  $position_count['yes_bind'] = D('House_village_bind_position')->get_bind_position_count('distinct(position_id)',array('village_id'=>$this->village_id));
        //$position_count['yes_bind'] = D('House_village_parking_position')->get_parking_position_count('distinct(position_id)',array('village_id'=>$this->village_id,'position_pattern'=>1,'position_status'=>2,'position_type'=>array('neq',3)));//车位总量
        $whereArr=array('pp.village_id'=>$this->village_id,'pp.position_pattern'=>1,'pp.position_id'=>array('gt',0),'pp.position_type'=>array('neq',3),'bp.village_id'=>$this->village_id);
        $position_count['yes_bind'] =M('House_village_bind_position')->alias('bp')->join('LEFT JOIN '.C('DB_PREFIX').'house_village_parking_position as pp on bp.position_id=pp.position_id')->where($whereArr)->count('distinct(bp.position_id)');
        //未绑定业主的车位
        $position_count['no_bind'] = D('House_village_parking_position')->get_parking_position_count('distinct(position_id)',array('village_id'=>$this->village_id,'position_pattern'=>1,'position_type'=>array('neq',3)))-$position_count['yes_bind'];//车位总量

        //欠费统计
        $unpaid = $user_bind_model->get_cashier_unpaid_list(array('village_id'=>$this->village_id),0,0);
         $pay_count['water_price'] = $unpaid['water_money'];
         $pay_count['electric_price'] = $unpaid['electric_money'];
         $pay_count['gas_price'] = $unpaid['gas_money'];
         $pay_count['park_price'] = $unpaid['park_money'];
         $pay_count['property_price'] = $unpaid['property_money'];
         $pay_count['cunstom_money'] = $unpaid['cunstom_money'];

        //收入统计
       /* $pay_order_model = D('House_village_pay_order');
        $pay_order['property'] = $pay_order_model->get_pay_order_sum('money',array('paid'=>'1','order_type'=>'property','village_id'=>$this->village_id));//已支付的物业费
        $pay_order['water'] = $pay_order_model->get_pay_order_sum('money',array('paid'=>'1','order_type'=>'water','village_id'=>$this->village_id));//已支付的水费
        $pay_order['electric'] = $pay_order_model->get_pay_order_sum('money',array('paid'=>'1','order_type'=>'electric','village_id'=>$this->village_id));//已支付的电费
        $pay_order['gas'] = $pay_order_model->get_pay_order_sum('money',array('paid'=>'1','order_type'=>'gas','village_id'=>$this->village_id));//已支付的燃气费
        $pay_order['custom'] = $pay_order_model->get_pay_order_sum('money',array('paid'=>'1','order_type'=>'custom','village_id'=>$this->village_id));//社区自定义缴费
        */
        //业主统计
        $user_bind_model = D('House_village_user_bind');

       /* $bind_where=[
            ['type','=','0'],
            ['status','=','1'],
            ['village_id','=',$this->village_id],
            ['parent_id','=',0],
            ['uid','<>',0],
            ['name','<>',''],
            ['phone','<>','']];*/

        $bind_where="type=0 and status=1 and vacancy_id!=0 and parent_id=0 and (uid!=0 or name!='' or phone!='') and village_id=".$this->village_id;
       // $user_bind['owner'] = $user_bind_model->get_user_bind_count(array('type'=>'0','status'=>'1','village_id'=>$this->village_id,'parent_id'=>0),'');//房主
        $user_bind['owner'] = $user_bind_model->get_user_bind_count($bind_where,'');//房主

        $user_bind['family'] = $user_bind_model->get_user_bind_count('type =1 and status =1 and village_id='.$this->village_id,'');//家人
        $user_bind['tenant'] = $user_bind_model->get_user_bind_count('type =2 and status =1 and village_id='.$this->village_id,'');//租客
        $user_bind['new_owner'] = $user_bind_model->get_user_bind_count('type =3 and status =1 and village_id='.$this->village_id,'');//更新房主

//        $user_bind['family'] = $user_bind_model->get_user_bind_count(array('type'=>'1','status'=>1,'village_id'=>$this->village_id),'');//家人
//        $user_bind['tenant'] = $user_bind_model->get_user_bind_count(array('type'=>'2','status'=>1,'village_id'=>$this->village_id),'');//租客
//        $user_bind['new_owner'] = $user_bind_model->get_user_bind_count(array('type'=>'3','status'=>1,'village_id'=>$this->village_id),'');//更新房主

        $this->assign('room_count',$room_count);
        $this->assign('position_count',$position_count);
        $this->assign('pay_count',$pay_count);
//        $this->assign('pay_order',$pay_order);
        $this->assign('user_bind',$user_bind);
        $this->display();
    }


    //未缴账单
    public function cashier_unpaid(){
        //未缴 权限
        if (!in_array(85, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_pay_order = D('House_village_pay_order');
        $village_id = $this->village_id;

        if($village_id){
            $where = array(
                'village_id' => $village_id,
            );
            if (!empty($_GET['keyword'])) {
                if ($_GET['searchtype'] == 'name') {
                    $where['name'] = trim($_GET['keyword']);
                } else if ($_GET['searchtype'] == 'phone') {
                    $where['phone'] =  trim($_GET['keyword']);
                } else if ($_GET['searchtype'] == 'usernum') {
                    $where['usernum'] =  trim($_GET['keyword']);
                }
            }

            if($_GET['is_bind_weixin']){
                $where['is_bind_weixin'] = $_GET['is_bind_weixin'];
            }

            if($_GET['single_id'] !=0){
                $where['single_id'] = $_GET['single_id'];
            }
            if($_GET['floor_id'] !=0){
                $where['floor_id'] = $_GET['floor_id'];
            }
            if($_GET['vacancy_id'] !=0){
                $where['vacancy_id'] = $_GET['vacancy_id'];
            }
            if($_GET['layer_id'] !=0){
                $where['layer_id'] = $_GET['layer_id'];
            }
            $where['status'] = 1;

            // 查询总账单
            $total = D('House_village_user_bind')->get_cashier_unpaid_list_new($where,0,0);
            unset($total['pagebar'],$total['list']);
            $list = D('House_village_user_bind')->get_cashier_unpaid_list_new($where,0,20);
            $this->assign('total',$total);
            $this->assign('list',$list);
            //楼栋
            $single_list = M('House_village_single')->where(array('village_id'=>$village_id,'status'=>1))->select();
            if($single_list){
                $this->assign('single_list',$single_list);
            }else{
                $this->assign('single_list',[]);
            }
        }
        $this->assign('village_id',$village_id);
        $this->display();
    }

    //导出未缴账单
    public function cashier_unpaid_export(){
        set_time_limit(0);
        //未缴账单-导出 权限
        if (!in_array(267, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        error_reporting(E_ALL);
        date_default_timezone_set('Europe/London');
        $objExcel = new PHPExcel();

        $title = $this->village['village_name'] . '社区-物业未缴总账单列表';
        /*以下是一些设置 ，什么作者  标题之类的*/
         $objExcel->getProperties()->setCreator($title)
           ->setLastModifiedBy($title)
           ->setTitle($title)
           ->setSubject("数据EXCEL导出")
           ->setDescription("备份数据")
           ->setKeywords("excel")
           ->setCategory("result file");

         /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        //  $where=array();
        // if ($_GET['search_type'] && $_GET['search_value']) {
        //     switch ($_GET['search_type']) {
        //         case '1'://车牌号
        //             $where['a.car_number'] = array('like','%'.$_GET['search_value'].'%');
        //             break;
        //         case '2': //停车卡号
        //             $where['a.car_stop_num'] =array('like','%'.$_GET['search_value'].'%');
        //             break;
        //         case '3'://车位号
        //             $where['b.position_num'] = array('like','%'.$_GET['search_value'].'%');
        //             break;
        //         case '4'://车主姓名
        //             $where['a.car_user_name'] = array('like','%'.$_GET['search_value'].'%');
        //             break;
        //         case '5'://车主手机号
        //             $where['a.car_user_phone'] = array('like','%'.$_GET['search_value'].'%');
        //             break;
        //     }
        // }



        $where = array(
            'village_id' => $this->village_id,
        );
        $where['status'] = 1;


        $data = D('House_village_user_bind')->get_cashier_unpaid_list_new($where,0,0);
        $length = ceil(count($data['list']) / 1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G',
            'H','I','J','K','L','M','N',
            'O','P','Q','R','S','T',
            'U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
        ];
        $pValue_arr = [
            '所属'.C('config.house_name'),
            '物业编号',
            '业主姓名',
            '手机号',
            '地址',
            '物业服务开始时间',
            '物业服务结束时间',
            '水费',
            '电费',
            '燃气费',
            '停车费',
            '物业费'
        ];
        if (isset($data['cunstom_arr']) && $data['cunstom_arr']) {
            foreach ($data['cunstom_arr'] as $vs) {
                $pValue_arr[] = $vs['payment_name'];
            }
        } else {
            $pValue_arr[] = '自定义项费';
        }
        $pValue_arr[] = '合计';

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle('未缴账单列表');
            $objActSheet = $objExcel->getActiveSheet();
            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }
//            $objActSheet->setCellValue('B1', '物业编号');
//            $objActSheet->setCellValue('C1', '业主姓名');
//            $objActSheet->setCellValue('D1', '手机号');
//            $objActSheet->setCellValue('E1', '地址');
//            $objActSheet->setCellValue('F1', '水费');
//            $objActSheet->setCellValue('G1', '电费');
//            $objActSheet->setCellValue('H1', '燃气费');
//            $objActSheet->setCellValue('I1', '停车费');
//            $objActSheet->setCellValue('J1', '物业费');
//            $objActSheet->setCellValue('K1', '自定义项费');
//            $objActSheet->setCellValue('L1', '合计');
            $field='village_name';
            $village_id = $this->village_id;
            $village_name = D('House_village')->get_village_info($village_id,$field);
            if (!empty($data)) {
                $index = 2;
                for ($k = $i*1000; $k < ($i+1)*1000; $k++) {
                    if (!$data['list'][$k]['usernum'] && empty($data['list'][$k]['name']) && empty($data['list'][$k]['phone'])) {
                        continue;
                    }
                    $cunstom_pay_arr = $data['list'][$k]['cunstom_pay_arr'];
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, $village_name['village_name']);
                    $usernum = $data['list'][$k]['bind_number'] ? $data['list'][$k]['bind_number'] : $data['list'][$k]['usernum'];
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, $usernum);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, $data['list'][$k]['name']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, $data['list'][$k]['phone']);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, $data['list'][$k]['address']);

                    $property_endtime = '-';
                    if ($data['list'][$k]['property_endtime']) {
                        $property_endtime = date('Y-m-d', $data['list'][$k]['property_endtime']);
                    }
                    $property_starttime = '-';
                    if ($data['list'][$k]['property_starttime']) {
                        $property_starttime = date('Y-m-d', $data['list'][$k]['property_starttime']);
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, $property_starttime);
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, $property_endtime);


                    $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, getFormatNumber($data['list'][$k]['water_price']));
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[8] . $index, getFormatNumber($data['list'][$k]['electric_price']));
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[9] . $index, getFormatNumber($data['list'][$k]['gas_price']));
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[10] . $index, getFormatNumber($data['list'][$k]['park_price']));
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[11] . $index, getFormatNumber($data['list'][$k]['property_price']));
                    if (isset($data['cunstom_arr']) && $data['cunstom_arr']) {
                        $ks0 = 1;
                        foreach ($data['cunstom_arr'] as $ks1=>$vs1) {
                            $indexs = $ks0+11;
                            $total1 = $cunstom_pay_arr[$ks1] ? $cunstom_pay_arr[$ks1]['total_money'] : '-';
                            $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, getFormatNumber($total1));
                            $ks0++;
                        }
                    } else {
                        $indexs = 12;
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, getFormatNumber($data['list'][$k]['cunstom_money']));
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs+1] . $index, getFormatNumber($data['list'][$k]['total']));
                    $index++;

                }
                $objActSheet->setCellValueExplicit($pCoordinate_arr[0] . $index, '合计');
                $objActSheet->setCellValueExplicit($pCoordinate_arr[1] . $index, '');
                $objActSheet->setCellValueExplicit($pCoordinate_arr[2] . $index, '');
                $objActSheet->setCellValueExplicit($pCoordinate_arr[3] . $index, '');
                $objActSheet->setCellValueExplicit($pCoordinate_arr[4] . $index, '');
                $objActSheet->setCellValueExplicit($pCoordinate_arr[5] . $index, '');
                $objActSheet->setCellValueExplicit($pCoordinate_arr[6] . $index, '');
                $objActSheet->mergeCells($pCoordinate_arr[0] . $index.':'.$pCoordinate_arr[6] . $index);
                $objActSheet->setCellValueExplicit($pCoordinate_arr[7] . $index, getFormatNumber($data['water_money']));
                $objActSheet->setCellValueExplicit($pCoordinate_arr[8] . $index, getFormatNumber($data['electric_money']));
                $objActSheet->setCellValueExplicit($pCoordinate_arr[9] . $index, getFormatNumber($data['gas_money']));
                $objActSheet->setCellValueExplicit($pCoordinate_arr[10] . $index, getFormatNumber($data['park_money']));
                $objActSheet->setCellValueExplicit($pCoordinate_arr[11] . $index, getFormatNumber($data['property_money']));
                if (isset($data['cunstom_arr']) && $data['cunstom_arr']) {
                    $ks0 = 1;
                    foreach ($data['cunstom_arr'] as $ks1=>$vs1) {
                        $indexs = $ks0+11;
                        $total1 = $vs1['total_money'] ? $vs1['total_money'] : 0;
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, getFormatNumber($total1));
                        $ks0++;
                    }
                } else {
                    $indexs = 12;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, getFormatNumber($data['cunstom_money']));
                }
                $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs+1] . $index, getFormatNumber($data['total_money']));
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
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    public function deposit_user_info(){
        $this->display();
    }



    public function ajax_batch_bind()
    {
        if (!in_array(437, $this->house_session['menus'])) {
            if ($_GET['data_type'] == 'json' ) {
                exit(json_encode(array('status'=>1,'msg'=>'对不起，您没有权限执行此操作')));
            }
            $this->error('对不起，您没有权限执行此操作');
        }

        $arr_pigcms_id = $_POST['arr_pigcms_id'];
        $arr_pigcms_id = explode(',',$arr_pigcms_id);

        if (!$arr_pigcms_id) {
            exit(json_encode(array('status'=>0,'msg'=>'参数传递错误')));
        }
        $database_house_village_user_bind = D('House_village_user_bind');
        foreach($arr_pigcms_id as $k=>$v){
            $now_user = $database_house_village_user_bind->get_one_by_bindId($v);
            if($now_user['status'] != 1){
                // 平台用户
                $user_info = D('User')->get_user($now_user['phone'], 'phone');

                if($_GET['no_bind']){
                    $reason = trim($_GET['reason']) ? htmlspecialchars(trim($_GET['reason'])) : '无';
                    $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$v))->data(array('pass_time'=>time(),'status'=>0,'reason'=>$reason))->save();

                    if($now_user['type'] != 3){
                        if ($now_user['status'] == 1 ) {
                            $first = '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'入住申请已解绑。原因：'.$reason.'\n';
                            $keyword1 = ''.(C('config.open_house_user_name')?'亲友':'家属').'入住申请';
                        }else{
                            $first = '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'入住申请审核未通过。原因：'.$reason.'\n';
                            $keyword1 = ''.(C('config.open_house_user_name')?'亲友':'家属').'入住申请';
                        }
                    }else{
                        $first = '您在【'.$this->village['village_name'].'】的替换房主申请审核未通过。原因：'.$reason.'\n';
                        $keyword1 = '替换房主申请';
                    }
                }else{
                    if($now_user['type'] != 3){
                        // 房屋信息
                        $database_house_village_user_vacancy = D('House_village_user_vacancy');
                        $vacancy_condition['pigcms_id'] = $now_user['vacancy_id'];
                        $bind_info = $database_house_village_user_vacancy->where($vacancy_condition)->find();
                        if($now_user['phone'] == $bind_info['phone']){
                            if ($bind_info['status']==2) {
                                if ($_GET['data_type'] == 'json' ) {
                                    exit(json_encode(array('status'=>1,'msg'=>'该手机号已提交申请')));
                                }
                                $this->error('该手机号已提交申请');
                            }else{
                                if ($_GET['data_type'] == 'json' ) {
                                    exit(json_encode(array('status'=>1,'msg'=>'手机号不能与业主一致')));
                                }
                                $this->error('手机号不能与业主一致');
                            }
                        }
                        $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$v))->data(array('pass_time'=>time(),'status'=>1))->save();

                        $first = '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'入住申请审核已通过\n';
                        $keyword1 = ''.(C('config.open_house_user_name')?'亲友':'家属').'入住申请';
                        D('House_face_img')->house_user_face_device_info($now_user['uid'],$v);
                    }else{
                        // 更新原房主信息
                        $user_data['uid'] = $now_user['uid'];
                        $user_data['name'] = $now_user['name'];
                        $user_data['phone'] = $now_user['phone'];
                        $user_data['pass_time'] = time();
                        $user_data['parent_id'] = 0;
                        $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$now_user['parent_id']))->data($user_data)->save();

                        #要修改 房间house_village_user_vacancy 表信息
                        $vacancy_data['uid'] = $now_user['uid'];
                        $vacancy_data['name'] = $now_user['name'];
                        $vacancy_data['phone'] = $now_user['phone'];
                        $vacancy_data['status'] = 3;

                        $vacancy_where['pigcms_id'] = $now_user['vacancy_id'];
                        $vacancy_where['floor_id']  = $now_user['floor_id'];
                        $vacancy_where['village_id'] = $now_user['village_id'];
                        $database_house_village_user_vacancy = D('House_village_user_vacancy');
                        $database_house_village_user_vacancy->where($vacancy_where)->data($vacancy_data)->save();

                        // 删除申请记录
                        fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                        fdump($now_user,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                        $database_house_village_user_bind->where(array('pigcms_id'=>$v))->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();
                       /* fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);*/
                        fdump_api(['line:---'.__LINE__,$database_house_village_user_bind->_sql()],'house_user_bind/user_bind_delete_log',1);

                        #禁止之前房主ID
                        // $database_house_village_user_bind->where(array('pigcms_id'=>$now_user['parent_id']))->data(array('status'=>0))->save();
                        #修改之前房主下面的绑定亲属/租客 替换到现在房主下面
                        // $database_house_village_user_bind->where(array('parent_id'=>$now_user['parent_id']))->data(array('parent_id'=>$now_user['pigcms_id']))->save();

                        $reason = '';
                        $first = '您在【'.$this->village['village_name'].'】的替换房主申请审核已通过'.$reason.'\n';
                        $keyword1 = '替换房主申请';
                        D('House_face_img')->house_user_face_device_info($now_user['uid'],$v);
                        D('House_village_user_bind')->checkUserPass($v, $now_user['uid'], $this->village_id, $this->village['property_id']);
                    }
                }
                //发送模板消息
                if ($user_info['openid']) {
                    $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => $first, 'keyword1' =>  $keyword1,  'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                }

            }
        }
        echo json_encode(array('status'=>1,'msg'=>'修改成功！'));die;
//        if($insert_id){
//            // 修改信息，进行同步
//            D('House_village_face_door')->modify_user($insert_id);
//            if ($_GET['data_type'] == 'json' ) {
//                exit(json_encode(array('status'=>0,'msg'=>'修改成功！')));
//            }
//            $this->success('修改成功！');
//        }else{
//            if ($_GET['data_type'] == 'json' ) {
//                exit(json_encode(array('status'=>1,'msg'=>'修改失败！')));
//            }
//            $this->error('修改失败！');
//        }
    }



    public function un_bind_edit(){
        //家属租客-绑定解绑 权限
        if (!in_array(111, $this->house_session['menus']) && !in_array(105, $this->house_session['menus'])) {
            if ($_GET['data_type'] == 'json' ) {
                exit(json_encode(array('status'=>1,'msg'=>'对不起，您没有权限执行此操作')));
            }
            $this->error('对不起，您没有权限执行此操作');
        }

        $pigcms_id = $_GET['pigcms_id'] + 0;
        $arr_pigcms_id = explode(',',$pigcms_id);


        if(!$pigcms_id){
            if ($_GET['data_type'] == 'json' ) {
                exit(json_encode(array('status'=>1,'msg'=>'传递参数有误！')));
            }
            $this->error('传递参数有误！~~~');
        }
        $database_house_village_user_bind = D('House_village_user_bind');
        foreach($arr_pigcms_id as $k=>$v){
            $now_user = $database_house_village_user_bind->get_one_by_bindId($v);
            if(!$now_user){
                if ($_GET['data_type'] == 'json' ) {
                    exit(json_encode(array('status'=>1,'msg'=>'信息暂时不存在！')));
                }
            }
            if($now_user['status'] != 1){
                $user_info = D('User')->get_user($now_user['phone'], 'phone');

                $reason = trim($_GET['reason']) ? htmlspecialchars(trim($_GET['reason'])) : '无';
                $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$v))->data(array('pass_time'=>time(),'status'=>0,'reason'=>$reason))->save();

                if($now_user['type'] != 3){
                    if ($now_user['status'] == 1 ) {
                        $first = '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'入住申请已解绑。原因：'.$reason.'\n';
                        $keyword1 = ''.(C('config.open_house_user_name')?'亲友':'家属').'入住申请';
                    }else{
                        $first = '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'入住申请审核未通过。原因：'.$reason.'\n';
                        $keyword1 = ''.(C('config.open_house_user_name')?'亲友':'家属').'入住申请';
                    }
                }else{
                    $first = '您在【'.$this->village['village_name'].'】的替换房主申请审核未通过。原因：'.$reason.'\n';
                    $keyword1 = '替换房主申请';
                }
                //发送模板消息
                if ($user_info['openid']) {
                    $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => $first, 'keyword1' =>  $keyword1,  'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                }
                D('House_village_face_door')->modify_user($insert_id);

            }
        }
        exit(json_encode(array('status'=>0,'msg'=>'修改成功！')));
    }


    public function ajax_audit_unbind_edit(){

        $database_house_village_user_unbind = D('House_village_user_unbind');
        $database_house_village_user_bind = D('House_village_user_bind');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');


        $itemids = $_POST['arr_itemid'];
        $arr_itemid = explode(',',$itemids);
        $status = $_POST['status'] + 0;
        if($arr_itemid){
            foreach($arr_itemid as $k=>$v){
                $where['itemid'] = $v;

                $data['status']   = $status;
                $data['edittime'] = time();
                $data['reason'] = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                $save_id = $database_house_village_user_unbind -> where($where) -> data($data)->save();
                $info_where['itemid']     = $v;
                $info_where['village_id'] = $this->village_id;
                $info = $database_house_village_user_unbind->where($info_where)->find();
                // 平台用户
                $user_info = D('User')->get_user($info['phone'], 'phone');

                #审核不通过/审核中 不删除绑定信息
                if($status==1 || $status==2){
                    if ($status==2) {
                        //发送模板消息
                        if ($user_info['openid']) {
                            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            if ($info['type'] == 0 || $info['type'] == 3) {
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主解绑申请审核未通过。原因：'.$data['reason'].'\n', 'keyword1' => '业主解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }else{
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请审核未通过。原因：'.$data['reason'].'\n', 'keyword1' => ''.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }

                        }
                    }
                    # 审核通过 删除绑定（要判断是否是管理员 如果是 那么删除房间绑定信息和管理员绑定信息 以及 该房间绑定的亲属/租客）
                }elseif($status==3){
                    //发送模板消息

                    if($save_id){

                        #如果是管理员/替换管理员 先清空房间绑定信息 vacancy 在删除绑定信息/亲属/租客 bind
                        if($info['type']==0 || $info['type']==3){
                            //发送模板消息
                            if ($user_info['openid']) {
                                $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主解绑申请审核已通过\n', 'keyword1' => '业主解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }

                            $room['status'] = 1;
                            $room['uid'] = $room['type'] = $room['park_flag']= 0;
                            $room['name'] = $room['phone'] = $room['memo'] = "";
                            $room['housesize'] = 0.00;

                            $room_where['pigcms_id'] = $info['room_id'];
                            $room_where['village_id'] = $info['village_id'];
                            $reset_id = $database_house_village_user_vacancy->where($room_where)->data($room)->save();

                            #房间清除完成 删除绑定信息
                            if($reset_id){

                                #先删除房主信息

                                $bind_info['uid']        = $info['uid'];
                                $bind_info['phone']      = $info['phone'];
                                $bind_info['vacancy_id'] = $info['room_id'];
                                $bind_info['village_id'] = $info['village_id'];
                                $bind_info['type']       = $info['type'];
                                $bind_info['pigcms_id']  = $info['bind_id'];

                                //增加记录
                                $user_bind = $database_house_village_user_bind->where($bind_info)->find();
                                $data['village_id'] = $user_bind['village_id'];
                                $data['vacancy_id'] = $user_bind['vacancy_id'];
                                $data['floor_id'] = $user_bind['floor_id'];
                                $data['uid'] = $user_bind['uid'];
                                $data['name'] = $user_bind['name'];
                                $data['phone'] = $user_bind['phone'];
                                $data['type'] = $user_bind['type'];
                                $data['check_in_time'] = $user_bind['add_time'];
                                $data['user_bind_id'] = $user_bind['bind_id'];
                                $data['add_time'] = time();
                                M('House_village_user_record')->data($data)->add();

                                fdump(__FUNCTION__.'>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                fdump($_POST,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                fdump($info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                fdump($user_bind,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                                fdump($bind_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                                fdump_api([__FUNCTION__.'>>>>>>'.__LINE__,$_POST,$info,$user_bind,$bind_info],'house_user_bind/user_bind_delete_log',1);

                                $del_0_id = $database_house_village_user_bind->where($bind_info)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();
                               /* fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);*/

                                fdump_api(['line:---'.__LINE__,$database_house_village_user_bind->getDbError()],'house_user_bind/user_bind_delete_log',1);

                                #再删除亲属/租客信息
                                if($del_0_id){
                                    // 同步删除钥匙
                                    D('House_village_face_door')->del_a3_user_bind($bind_info['pigcms_id'], $bind_info['village_id'], $user_bind);
                                    $bind_info_1['parent_id'] = $info['bind_id'];
                                    $bind_info_1['type']      = array('in' , '1,2');
                                    $del_info = $database_house_village_user_bind->where($bind_info_1)->select();

                                  /*  fdump('>>同步删除家属>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                    fdump($del_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);*/

                                    //fdump_api(['>>同步删除家属>>>>'.__LINE__,$del_info],'house_user_bind/user_bind_delete_log',1);

                                    $del_1_id = $database_house_village_user_bind->where($bind_info_1)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();
                                    fdump_api(['>>同步删除家属>>>>'.__LINE__,$del_info,$database_house_village_user_bind->getDbError()],'house_user_bind/user_bind_delete_log',1);

                                   // fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                                    if ($del_info && $del_1_id) {
                                        foreach ($del_info as $valss) {
                                            // 同步删除钥匙
                                            D('House_village_face_door')->del_a3_user_bind($valss['pigcms_id'], $valss['village_id'], $valss);
                                        }
                                    }
                                }
                            }

                            #如果是亲属/租客	删除绑定信息 bind
                        }elseif($info['type']==1 || $info['type']==2){
                            //发送模板消息
                            if ($user_info['openid']) {
                                $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请审核已通过\n', 'keyword1' => ''.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }

                            $bind_info['uid']        = $info['uid'];
                            $bind_info['phone']      = $info['phone'];
                            $bind_info['vacancy_id'] = $info['room_id'];
                            $bind_info['village_id'] = $info['village_id'];
                            $bind_info['type']       = $info['type'];
                            $bind_info['pigcms_id']  = $info['bind_id'];

                            //增加记录
                            $user_bind = $database_house_village_user_bind->where($bind_info)->find();
                            $data['village_id'] = $user_bind['village_id'];
                            $data['vacancy_id'] = $user_bind['vacancy_id'];
                            $data['floor_id'] = $user_bind['floor_id'];
                            $data['uid'] = $user_bind['uid'];
                            $data['name'] = $user_bind['name'];
                            $data['phone'] = $user_bind['phone'];
                            $data['type'] = $user_bind['type'];
                            $data['check_in_time'] = $user_bind['add_time'];
                            $data['user_bind_id'] = $user_bind['bind_id'];
                            $data['add_time'] = time();
                            M('House_village_user_record')->data($data)->add();

                            fdump(__FUNCTION__.'>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump($_POST,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);


                            $del_0_id = $database_house_village_user_bind->where($bind_info)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();

                            fdump_api([__FUNCTION__.'>>>>>>'.__LINE__,$_POST,'line--'.__LINE__,$database_house_village_user_bind->getDbError()],'house_user_bind/user_bind_delete_log',1);

                            /*fdump('line--'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);*/
                            if($del_0_id) {
                                D('House_village_face_door')->del_a3_user_bind($bind_info['pigcms_id'], $bind_info['village_id'], $user_bind);
                            }
                        }

                    }

                }
            }
        }
        exit(json_encode(array('status'=>1,'msg'=>'操作成功！')));
    }

    public function ajax_audit_unbind(){

        $database_house_village_user_unbind = D('House_village_user_unbind');
        $database_house_village_user_bind = D('House_village_user_bind');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');


        $itemids = $_GET['arr_itemid'];
        $arr_itemid = explode(',',$itemids);
        $status = $_GET['status'] + 0;
//        var_dump($_GET);
//        var_dump($arr_itemid);
//        die;
        if($arr_itemid){
            foreach($arr_itemid as $k=>$v){
                $where['itemid'] = $v;

                $data['status']   = $status;
                $data['edittime'] = time();
                $data['reason'] = trim($_GET['reason']) ? htmlspecialchars(trim($_GET['reason'])) : '无';
                $save_id = $database_house_village_user_unbind -> where($where) -> data($data)->save();
                $info_where['itemid']     = $v;
                $info_where['village_id'] = $this->village_id;
                $info = $database_house_village_user_unbind->where($info_where)->find();
                // 平台用户
                $user_info = D('User')->get_user($info['phone'], 'phone');

                #审核不通过/审核中 不删除绑定信息
                if($status==1 || $status==2){
                    if ($status==2) {
                        //发送模板消息
                        if ($user_info['openid']) {
                            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            if ($info['type'] == 0 || $info['type'] == 3) {
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主解绑申请审核未通过。原因：'.$data['reason'].'\n', 'keyword1' => '业主解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }else{
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请审核未通过。原因：'.$data['reason'].'\n', 'keyword1' => ''.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }

                        }
                    }
                    # 审核通过 删除绑定（要判断是否是管理员 如果是 那么删除房间绑定信息和管理员绑定信息 以及 该房间绑定的亲属/租客）
                }elseif($status==3){
                    //发送模板消息

                    if($save_id){

                        #如果是管理员/替换管理员 先清空房间绑定信息 vacancy 在删除绑定信息/亲属/租客 bind
                        if($info['type']==0 || $info['type']==3){
                            //发送模板消息
                            if ($user_info['openid']) {
                                $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主解绑申请审核已通过\n', 'keyword1' => '业主解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }

                            $room['status'] = 1;
                            $room['uid'] = $room['type'] = $room['park_flag']= 0;
                            $room['name'] = $room['phone'] = $room['memo'] = "";
                            $room['housesize'] = 0.00;

                            $room_where['pigcms_id'] = $info['room_id'];
                            $room_where['village_id'] = $info['village_id'];
                            $reset_id = $database_house_village_user_vacancy->where($room_where)->data($room)->save();

                            #房间清除完成 删除绑定信息
                            if($reset_id){

                                #先删除房主信息

                                $bind_info['uid']        = $info['uid'];
                                $bind_info['name']       = $info['name'];
                                $bind_info['phone']      = $info['phone'];
                                $bind_info['floor_id']   = $info['floor_id'];
                                $bind_info['vacancy_id'] = $info['room_id'];
                                $bind_info['village_id'] = $info['village_id'];
                                $bind_info['village_id'] = $info['village_id'];
                                $bind_info['type']       = $info['type'];
                                $bind_info['pigcms_id']  = $info['bind_id'];

                                //增加记录
                                $user_bind = $database_house_village_user_bind->where($bind_info)->find();
                                $data['village_id'] = $user_bind['village_id'];
                                $data['vacancy_id'] = $user_bind['vacancy_id'];
                                $data['floor_id'] = $user_bind['floor_id'];
                                $data['uid'] = $user_bind['uid'];
                                $data['name'] = $user_bind['name'];
                                $data['phone'] = $user_bind['phone'];
                                $data['type'] = $user_bind['type'];
                                $data['check_in_time'] = $user_bind['add_time'];
                                $data['user_bind_id'] = $user_bind['bind_id'];
                                $data['add_time'] = time();
                                M('house_village_user_record')->data($data)->add();

                                fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                                fdump($user_bind,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                                $del_0_id = $database_house_village_user_bind->where($bind_info)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();
                              /*  fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);*/
                                fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                                #再删除亲属/租客信息
                                if($del_0_id){
                                    $bind_info_1['parent_id'] = $info['bind_id'];
                                    $bind_info_1['type']      = array('in' , '1,2');
                                    fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                    fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                    fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                    $del_infos = $database_house_village_user_bind->where($bind_info_1)->select();

                                  //  fdump($del_infos,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                    $del_1_id = $database_house_village_user_bind->where($bind_info_1)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();
                                  /*  fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);*/
                                    fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                    fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                }
                                // 同步删除钥匙
                                D('House_village_face_door')->delete_user($info['bind_id']);
                            }

                            #如果是亲属/租客	删除绑定信息 bind
                        }elseif($info['type']==1 || $info['type']==2){
                            //发送模板消息
                            if ($user_info['openid']) {
                                $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请审核已通过\n', 'keyword1' => ''.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }

                            $bind_info['uid']        = $info['uid'];
                            $bind_info['name']       = $info['name'];
                            $bind_info['phone']      = $info['phone'];
                            $bind_info['floor_id']   = $info['floor_id'];
                            $bind_info['vacancy_id'] = $info['room_id'];
                            $bind_info['village_id'] = $info['village_id'];
                            $bind_info['village_id'] = $info['village_id'];
                            $bind_info['type']       = $info['type'];
                            $bind_info['pigcms_id']  = $info['bind_id'];

                            //增加记录
                            $user_bind = $database_house_village_user_bind->where($bind_info)->find();
                            $data['village_id'] = $user_bind['village_id'];
                            $data['vacancy_id'] = $user_bind['vacancy_id'];
                            $data['floor_id'] = $user_bind['floor_id'];
                            $data['uid'] = $user_bind['uid'];
                            $data['name'] = $user_bind['name'];
                            $data['phone'] = $user_bind['phone'];
                            $data['type'] = $user_bind['type'];
                            $data['check_in_time'] = $user_bind['add_time'];
                            $data['user_bind_id'] = $user_bind['bind_id'];
                            $data['add_time'] = time();
                            M('house_village_user_record')->data($data)->add();
                            fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                            fdump($user_bind,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            $database_house_village_user_bind->where($bind_info)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();
//                            fdump('line:---'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                            fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                            fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump_api(['>>>>>>'.__LINE__,$_SERVER,'houseapi--'.__FUNCTION__,$user_bind,'line:---'.__LINE__,$database_house_village_user_bind->_sql(),$database_house_village_user_bind->getDbError()],'house_user_bind/user_bind_delete_log',1);

                        }

                    }

                }
            }
        }
        exit(json_encode(array('status'=>0,'msg'=>'操作成功！')));
    }

    //未处理
    public function ajax_bind_audit(){
        $status = $_POST['status'] + 0;
        $pigcms_id_arr = $_POST['arr_pigcms_id'];
        $arr_pigcms_id = explode(',',$pigcms_id_arr);
        if($arr_pigcms_id){
            $database_house_village_user_vacancy = D('House_village_user_vacancy');
            $database_house_village_user_bind = D('House_village_user_bind');
            foreach($arr_pigcms_id as $k=>$v){
                if($v){
                    $v1 = explode('#',$v);
                    $bind_id = intval($v1[0]);
                    if (!$bind_id) {
                        exit(json_encode(array('status'=>0,'msg'=>'批量操作的对象不存在！')));
                    }
                    $bind_condition['pigcms_id'] = $bind_id;
                    // 查询对应操作对象
                    $bind_user = $database_house_village_user_bind->where($bind_condition)->find();
                    $vacancy_id = $bind_user['vacancy_id'];
                    $where_room = [
                        'pigcms_id' => $vacancy_id
                    ];
                    // 查询房屋
                    $info = $database_house_village_user_vacancy->house_village_user_vacancy_detail($where_room);
                    if($info['detail']){
                        $info = $info['detail'];
                        $data['usernum'] = $v1[2];
                        $database_house_village_floor = D('House_village_floor');
                        $database_house_village_user_bind = D('House_village_user_bind');
                        $database_user = D('User');

                        $where['floor_id'] = $bind_user['floor_id'];
                        $where['status'] = 1;
                        $where['village_id'] = $this->village_id;
                        $house_village_floor_info = $database_house_village_floor->where($where)->find();
                        if (!isset($house_village_floor_info)) {
                            exit(json_encode(array('status'=>0,'msg'=>'单元不存在，请查看社区中心，单元管理-单元列表！')));
                            break;
                        }

                        if (!empty(trim($bind_user['phone']))) {
                            $phone = trim($bind_user['phone']);
                        } else {
                            $phone = '';
                        }
                        $now_user = $database_user->get_user($phone, 'phone');
                        if($status == 1){

                            if ($now_user) {
                                $data['uid'] = $now_user['uid'];
                            }

                            // 验证手机号是否和家属一样 先有家属
                            // 手机号不能与家属手机号一样
                            if ($phone) {
                                $bind_condition2['vacancy_id'] = $vacancy_id;
                                $bind_condition2['pigcms_id'] = ['neq', $bind_user['pigcms_id']];
                                $bind_condition2['phone'] = $phone;
                                $bind_condition2['type'] = array('in' , '0,1,2,3');
                                $bind_condition2['status'] = array('in' , '1,2');

                                $family_bind_info = $database_house_village_user_bind->where($bind_condition2)->find();
                                if($family_bind_info){
                                    exit(json_encode(array('status'=>0,'msg'=>'该手机号已绑定或已申请绑定此房间！')));
                                    break;
                                }
                            }
                            $data['village_id'] = $this->village_id;

                            $data['housesize'] = $info['housesize'];

                            $data['park_flag'] = $info['park_flag'];
                            $data['add_time'] = time();
                            $data['vacancy_id'] = $vacancy_id;
                            $data['type'] = $bind_user['type'];
                            $data['attribute'] = $info['attribute'];
                            if ($bind_user['single_id']) {
                                $data['single_id'] = $bind_user['single_id'];
                            } elseif ($info['single_id']) {
                                $data['single_id'] = $info['single_id'];
                            }
                            if ($bind_user['layer_id']) {
                                $data['layer_id'] = $bind_user['layer_id'];
                            } elseif ($info['layer_id']) {
                                $data['layer_id'] = $info['layer_id'];
                            }
                            if($bind_user){
                                $data['status'] = 1;
                                // 如果是业主  将原来所有与房屋相同物业编号改为 编号+'-y' 然后当前审核通过的业主改为
                                $where_room = [];
                                $where_room['pigcms_id'] = $vacancy_id;
                                $room_usernum = $database_house_village_user_vacancy->where($where_room)->getField('usernum');
                                // 查询下是否存在虚拟业主  存在直接替换 然后删除现在的申请数据
                                if (0==$bind_user['type'] || 3==$bind_user['type']) {
                                    $where_virtual = [
                                        'vacancy_id' => $bind_user['vacancy_id'],
                                        'usernum' => $room_usernum,
                                        'pigcms_id' => ['neq', $bind_user['pigcms_id']],
                                        'type' => ['in',[0,3]],
                                        'status' => 1,
                                    ];
                                    $virtual_owner = $database_house_village_user_bind->where($where_virtual)->find();
                                    if ($virtual_owner && $virtual_owner['uid']==0 && !$virtual_owner['name'] && !$virtual_owner['phone']) {
                                        fdump('虚拟业主>>>'.__LINE__,'api/log/'.date('Ymd').'/house/all_virtual_owner_log',true);
                                        fdump($virtual_owner,'api/log/'.date('Ymd').'/house/all_virtual_owner_log',true);
                                        // 存在虚拟业主
                                        // 记录原来用户id
                                        $y_pigcms_id = $bind_user['pigcms_id'];
                                        $bind_id = $virtual_owner['pigcms_id'];
                                        $data['housesize'] = $info['add_time'];
                                        $data['add_time'] = $bind_user['add_time'];
                                        $data['id_card'] = $bind_user['id_card'];
                                        $data['floor_id'] = $bind_user['floor_id'];
                                        $data['address'] = $bind_user['address'] ? $bind_user['address'] : '';
                                        $data['authentication_field'] = $bind_user['authentication_field'] ? $bind_user['authentication_field'] : '';
                                    }
                                    if ($room_usernum) {
                                        // 直接将原有的物业编号给改为其他物业编号
                                        $where_other = [
                                            'usernum' => $room_usernum,
                                            'pigcms_id' => ['neq', $bind_id]
                                        ];
                                        $set = [
                                            'usernum' => $room_usernum.'-y'.time()
                                        ];
                                        $a = $database_house_village_user_bind->where($where_other)->save($set);
                                        fdump('>>>'.__LINE__,'api/log/'.date('Ymd').'/house/all_audit_edit_log',true);
                                        fdump($where_other,'api/log/'.date('Ymd').'/house/all_audit_edit_log',true);
                                        fdump($room_usernum.'-y','api/log/'.date('Ymd').'/house/all_audit_edit_log',true);
                                        if ($a!==false) {
                                            $data['usernum'] = $room_usernum;
                                            // 同样其他业主禁用
                                            $where_user_bind = [
                                                'vacancy_id' => $vacancy_id,
                                                'type' => ['in', [0,3]],
                                                'pigcms_id' => ['neq', $bind_id],
                                                'status' => 1
                                            ];
                                            $b = $database_house_village_user_bind->where($where_user_bind)->data(['status'=>0])->save();
                                            fdump($where_user_bind.'-y','api/log/'.date('Ymd').'/house/all_audit_edit_log',true);
                                        } else {
                                            fdump('>>>'.__LINE__,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                            fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house/all_audit_edit_err_log',true);
                                            fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house/all_audit_edit_err_log',true);
                                            exit(json_encode(array('status'=>0,'msg'=>'审核失败！')));
                                            break;
                                        }
                                    }
                                }
                                $where_bind = [
                                    'pigcms_id' => $bind_id
                                ];
                                if (!$data['single_id'] || !$data['layer_id']) {
                                    fdump('批量审核参数有误 参数有误>>'.__LINE__,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                    fdump($_POST,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                    fdump($info,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                    fdump($where_bind,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                    fdump($data,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                }
                                fdump_api(['批量审核数据>>'.__LINE__,$_POST,$info,$where_bind,$data],'user/admin_check_apply_log',1);
                                $insert_id = $database_house_village_user_bind->where($where_bind)->data($data)->save();
                                if ($insert_id!==false) {
                                    // 如果存在原数据id 删除申请数据
                                    if ($y_pigcms_id) {
                                        $where_bind_del = [
                                            'pigcms_id' => $y_pigcms_id
                                        ];


                                        $insert_id = $database_house_village_user_bind->where($where_bind_del)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();

                                        /*fdump('如果存在原数据id 删除申请数据>>'.__LINE__,'api/log/'.date('Ymd').'/house/all_check_delete_log',true);*/
                                        fdump($info,'api/log/'.date('Ymd').'/house/check_delete_log',true);
                                        fdump($where_bind_del,'api/log/'.date('Ymd').'/house/all_check_delete_log',true);
                                        fdump($data,'api/log/'.date('Ymd').'/house/all_check_delete_log',true);
                                    }
                                } else {
                                    fdump('>>>'.__LINE__,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                    fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house/all_audit_edit_err_log',true);
                                    fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house/all_audit_edit_err_log',true);
                                    exit(json_encode(array('status'=>0,'msg'=>'添加失败！')));
                                    break;
                                }
                                $face_pigcms_id = $bind_user['pigcms_id'];
                            }else{
                                exit(json_encode(array('status'=>0,'msg'=>'审核对象不存在！')));
                                break;
                                if ($bind_user['single_id']) {
                                    $data['single_id'] = $bind_user['single_id'];
                                } elseif ($info['single_id']) {
                                    $data['single_id'] = $info['single_id'];
                                }
                                if ($bind_user['layer_id']) {
                                    $data['layer_id'] = $bind_user['layer_id'];
                                } elseif ($info['layer_id']) {
                                    $data['layer_id'] = $info['layer_id'];
                                }
                                if (!$data['single_id'] || !$data['layer_id']) {
                                    fdump('批量审核参数有误 参数有误>>'.__LINE__,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                    fdump($_POST,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                    fdump($info,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                    fdump($data,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                                }
                                $insert_id = $database_house_village_user_bind->data($data)->add();
                                $face_pigcms_id = $insert_id;
                            }
                            if (!$insert_id) {
                                fdump('审核通过添加失败>'.__LINE__,'api/log/'.date('Ymd').'/house/all_add_err_log',true);
                                fdump($insert_id,'api/log/'.date('Ymd').'/house/all_add_err_log',true);
                                fdump($data,'api/log/'.date('Ymd').'/house/all_add_err_log',true);
                                fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house/all_add_err_log',true);
                                exit(json_encode(array('status'=>0,'msg'=>'添加失败！')));
                                break;
                            }
                            if($insert_id!==false){

                                $data_room['status'] = 3;
                                if ($data['uid']) {
                                    $data_room['uid'] = $data['uid'];
                                } elseif($bind_user['uid']) {
                                    $data_room['uid'] = $bind_user['uid'];
                                }
                                if ($data['name']) {
                                    $data_room['name'] = $data['name'];
                                } elseif($bind_user['name']) {
                                    $data_room['name'] = $bind_user['name'];
                                }
                                if ($data['phone']) {
                                    $data_room['phone'] = $data['phone'];
                                } elseif($bind_user['phone']) {
                                    $data_room['phone'] = $bind_user['phone'];
                                }
                                $database_house_village_user_vacancy->where($where_room)->data($data_room)->save();

                                if (3==$bind_user['type']) {
                                    $where_con['pigcms_id'] = array('neq',$bind_id);
                                    $where_con['vacancy_id'] = $vacancy_id;
                                    // 将现有的家属租客等改为待审核状态
                                    D("House_village_user_bind")->where($where_con)->data(['status'=>0])->save();
                                }
                                //发送模板消息
                                if ($now_user['openid']) {
                                    $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                    $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主入住申请审核已通过\n', 'keyword1' => '业主入住申请',  'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $now_user['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                                }
                                if (intval($face_pigcms_id)>0) {
                                    $change_param = [];
                                    $change_param['pigcms_id'] = $face_pigcms_id;
                                    D('House_village_user_vacancy')->change_user_bind_number($change_param);
                                }
                                // 审核通过，进行同步
                                D('House_face_img')->house_user_face_device_info($data_room['uid'],$face_pigcms_id);
                                D('House_village_user_bind')->checkUserPass($face_pigcms_id, $now_user['uid'], $this->village_id, $this->village['property_id']);
                            }
                        }
                    }

//                    foreach($arr as $k1=>$v1){
//                        $bind_condition['pigcms_id'] = $v1[0];
//                        // $bind_condition['usernum'] = $usernum;
//                        $database_house_village_user_vacancy = D('House_village_user_vacancy');
//                        $info = $database_house_village_user_vacancy->house_village_user_vacancy_detail($bind_condition);
//                        if($info['detail']){
//                            $info = $info['detail'];
//                            $data['usernum'] = $v1[2];
//                            $database_house_village_floor = D('House_village_floor');
//                            $database_house_village_user_bind = D('House_village_user_bind');
//                            $database_user = D('User');
//
//                            $where['floor_id'] = $v1[3];
//                            $where['status'] = $status;
//                            $where['village_id'] = $this->village_id;
//                            $house_village_floor_info = $database_house_village_floor->where($where)->find();
//                            //检测用户是否已存在
//                            $Map['usernum'] =  $v1[2];
//                            $find_info = $database_house_village_user_bind->where($Map)->count();
//
//                            $now_user = $database_user->get_user($v1[1], 'phone');
//                            if($status == 1){
//                                if (!isset($house_village_floor_info)) {
//                                    fdump('--line---'.__LINE__,'ajax_bind_audit',1);
//                                    fdump($info,'ajax_bind_audit',1);
//                                    fdump($house_village_floor_info,'ajax_bind_audit',1);
//                                    exit(json_encode(array('status'=>0,'msg'=>'单元不存在，请查看社区中心，单元管理-单元列表！')));
//                                    break;
//                                }
//
//                                if ($now_user) {
//                                    $data['uid'] = $now_user['uid'];
//                                }
//
//                                // 验证手机号是否和家属一样 先有家属
//                                // 手机号不能与家属手机号一样
//                                $bind_condition2['vacancy_id'] = $v1[0];
//                                $bind_condition2['phone'] = $_POST['phone'];
//                                $bind_condition2['type'] = array('in' , '1,2');;
//                                $bind_condition2['status'] = array('in' , '1,2');
//
//                                $family_bind_info = $database_house_village_user_bind->where($bind_condition2)->find();
//                                if($family_bind_info){
//                                    fdump('--line---'.__LINE__,'ajax_bind_audit',1);
//                                    fdump($house_village_floor_info,'ajax_bind_audit',1);
//                                    exit(json_encode(array('status'=>0,'msg'=>'该手机号已绑定或已申请绑定此房间！')));
//                                    break;
//                                }
//                                $data['village_id'] = $this->village_id;
//
//                                $data['housesize'] = $info['housesize'];
//
//                                $data['park_flag'] = $info['park_flag'];
//                                $data['add_time'] = time();
//                                $data['vacancy_id'] = $info['pigcms_id'];
//                                $data['type'] = $info['type'];
//                                $data['attribute'] = $info['attribute'];
//                                if($find_info>0){
//                                    $data['status'] = 1;
//                                    $insert_id = $database_house_village_user_bind->where($Map)->data($data)->save();
//                                }else{
//                                    $insert_id = $database_house_village_user_bind->data($data)->add();
//                                }
//                                if($insert_id){
//                                    $data_room['status'] = 3;
//                                    $data_room['uid'] = $data['uid'];
//                                    $data_room['phone'] = $v1[1];
//                                    $database_house_village_user_vacancy->where($bind_condition)->data($data_room)->save();
//
//                                    //发送模板消息
//                                    if ($now_user['openid']) {
//                                        $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list';
//                                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
//                                        $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主入住申请审核已通过\n', 'keyword1' => '业主入住申请',  'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $now_user['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
//                                    }
//                                    // 审核通过，进行同步
//                                    D('House_village_face_door')->modify_user($insert_id);
//                                }
//                            }
//                        }
//                    }
                }
            }
            exit(json_encode(array('status'=>1,'msg'=>'操作成功！')));
        }
    }

    //批量未通过
    public function ajax_unbind_audit(){

        $pigcms_ids = $_POST['pigcms_id'];
        $arr_pigcms_id = explode(',',$pigcms_ids);

        if(count($arr_pigcms_id) > 1){
            //房间号#手机号#房间编号
            foreach($arr_pigcms_id as $k=>$v){
                if($v){
                    $arr = explode('#',$v);
                    $database_user = D('User');
                    $now_user = $database_user->get_user($arr[1], 'phone');
                    $bind_id = $arr[0];
                    $database_house_village_user_vacancy = D('House_village_user_vacancy');
                    $database_house_village_user_bind = D('House_village_user_bind');
                    $bind_condition['pigcms_id'] = $bind_id;
                    // 查询对应操作对象
                    $bind_user = $database_house_village_user_bind->where($bind_condition)->find();
                    $where_room = [
                        'pigcms_id' => $bind_user['vacancy_id']
                    ];
                    // 查询房屋
                    $info = $database_house_village_user_vacancy->house_village_user_vacancy_detail($where_room);
                    if($info['detail']){
                        $where = array();
                        $where['type']=array('in',array(0,3));
                        $where['vacancy_id'] = $bind_user['vacancy_id'];
                        $where['status'] = 1;
                        $where['pigcms_id'] = array('neq',$bind_id);
                        $res = $database_house_village_user_bind->where($where)->find();
                        if($res){
                            $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$bind_id))->save(['status'=>0,'reason'=>trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无']);
                        }else{
                            $bind_info = $database_house_village_user_bind->where(array('type'=>array('in',array(1,2)),'parent_id'=>$bind_id))->count();
                            if ($bind_info) { // 存在家属就更新为虚拟房主
                                $edit_data = array();
                                $edit_data['uid'] = 0;
                                $edit_data['phone'] = '';
                                $edit_data['name'] = '';
                                $edit_data['status'] = 1;
                                $edit_data['reason'] = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                                $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$bind_id))->data($edit_data)->save();
                                if (!$save_bind) {
                                    fdump('用户申请业主入住审核拒绝更改失败>>'.__LINE__,'api/log/'.date('Ymd').'/user/check_apply_log',true);
                                    fdump($edit_data,'api/log/'.date('Ymd').'/user/check_apply_log',true);
                                    fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/user/check_apply_log',true);
                                }
                            }else{
                                $edit_data = array();
                                $edit_data['uid'] = 0;
                                $edit_data['status'] = 1;
                                $edit_data['phone'] = '';
                                $edit_data['name'] = '';
                                $edit_data['reason'] = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                                $insert_id = $database_house_village_user_vacancy->where($where_room)->data($edit_data)->save();
                                $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$bind_id))->save(['status'=>0,'reason'=>trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无']);
                            }
                        }
                        if($save_bind){
                            //发送模板消息
                            if ($now_user['openid']) {
                                $reason = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                                $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主入住申请审核未通过。原因：'.$reason.'\n', 'keyword1' => '业主入住申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $now_user['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                            }
                            if ($_POST['pigcms_id']) {
                                $where_bind['pigcms_id'] = $_POST['pigcms_id'];
                                $ywlsh = D("House_village_user_bind")->where($where_bind)->getField('third_xqryid');
                                if ($ywlsh) {
                                    D('House_village_third_user_info')->apply_access_callback($this->village_id,$_POST['pigcms_id'], 0,'小区审核拒绝入住', $ywlsh);
                                }
                            }
                            $this->success('修改成功！');
                        }else{
                            $this->error('修改失败！');
                        }
                    }
                }
            }
        }else{
            $arr = explode('#',$pigcms_ids);
            $database_user = D('User');
            $now_user = $database_user->get_user($arr[1], 'phone');
            $bind_id = $arr[0];
            $database_house_village_user_vacancy = D('House_village_user_vacancy');
            $database_house_village_user_bind = D('House_village_user_bind');
            $bind_condition['pigcms_id'] = $bind_id;
            // 查询对应操作对象
            $bind_user = $database_house_village_user_bind->where($bind_condition)->find();
            $where_room = [
                'pigcms_id' => $bind_user['vacancy_id']
            ];
            // 查询房屋
            $info = $database_house_village_user_vacancy->house_village_user_vacancy_detail($where_room);
            if($info['detail']){
                $where = array();
                $where['type']=array('in',array(0,3));
                $where['vacancy_id'] = $bind_user['vacancy_id'];
                $where['status'] = 1;
                $where['pigcms_id'] = array('neq',$bind_id);
                $res = $database_house_village_user_bind->where($where)->find();
                if($res){
                    $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$bind_id))->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();

                }else{
                    $bind_info = $database_house_village_user_bind->where(array('type'=>array('in',array(1,2)),'parent_id'=>$bind_id))->count();
                    if ($bind_info) { // 存在家属就更新为虚拟房主
                        $edit_data = array();
                        $edit_data['uid'] = 0;
                        $edit_data['phone'] = '';
                        $edit_data['name'] = '';
                        $edit_data['status'] = 1;
                        $edit_data['reason'] = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                        $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$bind_id))->data($edit_data)->save();
                        if (!$save_bind) {
                            fdump('用户申请业主入住审核拒绝更改失败>>'.__LINE__,'api/log/'.date('Ymd').'/user/check_apply_log',true);
                            fdump($edit_data,'api/log/'.date('Ymd').'/user/check_apply_log',true);
                            fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/user/check_apply_log',true);
                        }
                    }else{
                        $edit_data = array();
                        $edit_data['uid'] = 0;
                        $edit_data['status'] = 1;
                        $edit_data['phone'] = '';
                        $edit_data['name'] = '';
                        $edit_data['reason'] = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                        $insert_id = $database_house_village_user_vacancy->where($where_room)->data($edit_data)->save();

                        $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$bind_id))->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();
                    }
                }
                if($save_bind){
                    //发送模板消息
                    if ($now_user['openid']) {
                        $reason = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                        $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主入住申请审核未通过。原因：'.$reason.'\n', 'keyword1' => '业主入住申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $now_user['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                    }
                    if ($_POST['pigcms_id']) {
                        $where_bind['pigcms_id'] = $_POST['pigcms_id'];
                        $ywlsh = D("House_village_user_bind")->where($where_bind)->getField('third_xqryid');
                        if ($ywlsh) {
                            D('House_village_third_user_info')->apply_access_callback($this->village_id,$_POST['pigcms_id'], 0,'小区审核拒绝入住', $ywlsh);
                        }
                    }
                    $this->success('修改成功！');
                }else{
                    $this->error('修改失败！');
                }
            }
        }
        exit(json_encode(array('status'=>0,'msg'=>'操作成功！')));

    }
    //楼栋管理
    public function single_list()
    {

        $village_id = $this->village_id;
        $whereArr=array('village_id'=>$village_id, 'status'=> ['neq', '4'],'is_public_rental'=>0);
        $count_user = M('House_village_single')->where($whereArr)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $village_single = M('House_village_single')->where($whereArr)->order('status desc,sort desc, id asc')->limit($p->firstRow . ',' . $p->listRows)->select();
        if($village_single){
            foreach($village_single as $k=>&$v){
                $v['layer_num'] = $v['upper_layer_num'] + $v['lower_layer_num'];
            }
        }
        $this->assign('is_cockpit',intval(C('config.cockpit')));
        $this->assign('dataList',$village_single);
        $this->assign('pagebar',$p->show());
        $this->display();
    }


    public function get_update_layer_user_bind()
    {
        $user_bind = D('House_village_user_bind')->select();
        if($user_bind){
            foreach($user_bind as $k=>$v){
                if($v['layer_num'] && $v['village_id'] && $v['floor_id'] && $v['single_id']){
                    $layer = M('House_village_layer')->where(array('village_id'=>$v['village_id'],'floor_id'=>$v['floor_id'],'single_id'=>$v['single_id'],'layer_name'=>$v['layer_num']))->find();
                    if($layer){
                        D('House_village_user_bind')->where(array('village_id'=>$v['village_id'],'floor_id'=>$v['floor_id'],'single_id'=>$v['single_id']))->data(array('layer_id'=>$layer['id']))->save();
                    }
                }
            }
        }
    }



    public function stored_value_card()
    {

        $this->display();
    }





    public function single_add()
    {
        if (!in_array(464, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->house_session['village_id'];
        $property_id = $this->house_session['property_id'];
        // 查询小区物业服务管家信息
        $house_services_housekeeper = M('House_services_housekeeper');
        $house_worker = M('House_worker');
        if(IS_POST){
            $long_lat = explode(',',$_POST['long_lat']);
            if ($long_lat && $long_lat[0] && $long_lat[1]) {
                $data['long'] = floatval($long_lat[0]);
                $data['lat'] = floatval($long_lat[1]);
            }
            if (isset($_POST['contract_time_start'])&&!empty($_POST['contract_time_start'])) {
                $_POST['contract_time_start'] =strtotime($_POST['contract_time_start'].' 00:00:00');
            }
            $village_about_info = M('House_village_info')->where(array('village_id'=>$village_id))->find();
            if (!empty($village_about_info)){
                if ($village_about_info['contract_time_end']>1){
                    $_POST['contract_time_end']=$village_about_info['contract_time_end'];
                }else{
                    $_POST['contract_time_end'] = '';
                }
            }else{
                $_POST['contract_time_end'] = '';
            }
            if(empty($_POST['contract_time_end']) && !empty($_POST['contract_time_start'])){
                $this->error('请先去小区设置合同时间');
            }
            if(!empty($_POST['contract_time_start'])&&!empty($_POST['contract_time_end'])&&$_POST['contract_time_start']>=$_POST['contract_time_end']){
                $this->error('合同开始时间不能大于合同结束时间');
            }
            if(!empty($_POST['contract_time_start']) && $_POST['contract_time_start'] < $village_about_info['contract_time_start']){
                $this->error('楼栋合同开始时间不能大于小区合同开始时间');
            }
            $data['single_name'] = trim($_POST['single_name']);
            $data['status'] = $_POST['status'];
            $data['sort'] = $_POST['sort'];
            $data['measure_area'] = $_POST['measure_area'];
            $data['floor_num'] = $_POST['floor_num'];
            $data['vacancy_num'] = $_POST['vacancy_num'];
            $data['upper_layer_num'] = $_POST['upper_layer_num'];
            $data['lower_layer_num'] = $_POST['lower_layer_num'];
            $data['single_keeper_name'] = $_POST['single_keeper_name'] ? $_POST['single_keeper_name'] : '';
            $data['single_keeper_phone'] = $_POST['single_keeper_phone'] ? $_POST['single_keeper_phone'] : '';
            $data['single_keeper_head'] = $_POST['single_keeper_head'] ? $_POST['single_keeper_head'] : '';
            $data['village_id'] = $this->village_id;
//            $pattern = '/^[a-zA-Z0-9]+$/u';
//            if(!preg_match($pattern,trim($_POST['single_name']))){
//                $this->error('楼栋名称只允许字母或数字');
//            }
            if(!trim($_POST['single_name'])){
                $this->error('请填写楼栋名称');
            }
            $where_single_repeat = [
                'village_id'=>$this->village_id,
                'single_name'=>$data['single_name'],
                'status' => ['neq', 4]
            ];
            $single = D('House_village_single')->where($where_single_repeat)->find();
            if($single){
                $this->error('楼栋名称已存在,请重新添加!');
            }
            if (isset($_POST['single_number']) && trim($_POST['single_number'])) {
                $check_number = "/^[0-9]+$/";
                $single_number = trim($_POST['single_number']);
                if(!preg_match($check_number, $single_number)){
                    $this->error('楼栋编号只允许数字');
                }
                if(intval($single_number)<=0 || intval($single_number)>99){
                    $this->error('楼栋编号必须为1-99的数字');
                }
                if (strlen($single_number)>2) {
                    $this->error('楼栋编号只允最多2位数字');
                } elseif (strlen($single_number)<2) {
                    // 不足2位 补足2位
                    $single_number = str_pad($single_number,2,"0",STR_PAD_LEFT);
                }
                $data['single_number'] = $single_number;
            }
            if (!$data['single_number']) {
                $pattern_number = "/[^0-9]/";
                $single_number = preg_replace($pattern_number,'',$data['single_name']);
                // 楼栋编号 暂时同一个小区下仅限1-99数字编号
                if ($single_number) {
                    if (strlen($single_number)>2) {
                        // 超过2位 截取后2位
                        $single_number = substr($single_number, -2,2);
                    } elseif (strlen($single_number)<2) {
                        // 不足2位 补足2位
                        $single_number = str_pad($single_number,2,"0",STR_PAD_LEFT);
                    }
                    $data['single_number'] = $single_number;
                }
            }
            if ($data['single_number']) {
                $where_repeat = [
                    'village_id' => $this->village_id,
                    'single_number' => $data['single_number'],
                    'status' => ['neq', 4]
                ];
                $repeat_id = D('House_village_single')->where($where_repeat)->getField('id');
                if ($repeat_id) {
                    $this->error('该楼栋编号已经存在，请填写1-99的楼栋编号');
                }
            } else {
                $this->error('请填写1-99的不重复楼栋编号');
            }

            $single_id = M('House_village_single')->data($data)->add();
            if($single_id){
                // 楼栋添加成功
                $data[] = '';
                $data['village_id'] = $village_id;
                $data['property_id'] = $property_id;
                $data['single_id'] = $single_id;
                if ($_POST['work_arr']) {
                    $data['work_arr'] = implode(',',$_POST['work_arr']);
                }
                if ($_POST['welcome_tip']) {
                    $data['welcome_tip'] = $_POST['welcome_tip'];
                }
                if ($_POST['qy_qrcode']) {
                    $data['qy_qrcode'] = $_POST['qy_qrcode'];
                }
                if (isset($_POST['template_type'])) {
                    $data['template_type'] = intval($_POST['template_type']);
                }
                if ($_POST['template_url']) {
                    $data['template_url'] = $_POST['template_url'];
                }
                if ($_POST['effect_img']) {
                    $data['effect_img'] = $_POST['effect_img'];
                }
                $data['add_time'] = time();
                $house_services_housekeeper->data($data)->add();
                $this->success('添加成功!',U('single_list'));
            }else{
                $this->error('添加失败!');
            }
        }else{
            $qyhelp_url = $this->config['site_url'].'/static/file/village/information/qyhelp.pdf';
            $this->assign('qyhelp_url', $qyhelp_url);

            // 获取
            $enterprise_wx_corpid = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->getField('corpid');
            // 查询该小区小所有工作人员
            if ($enterprise_wx_corpid) {
                $where_worker = [
                    'village_id' => $village_id,
                    'qy_status' => 1,
                    'qy_id' => ['neq', ''],
                    'status' => 1
                ];
            } else {
                $where_worker = [
                    'village_id' => $village_id,
                    'status' => 1
                ];
            }
            $worker_list = $house_worker->where($where_worker)->order('wid ASC')->select();
            if (!$worker_list) {
                $worker_list = [];
            }
            $this->assign('worker_list', $worker_list);
            $this->assign('enterprise_wx_corpid', $enterprise_wx_corpid);

            $this->display();
        }
    }

    public function add_stored_value_card(){

        if(IS_POST){


        }else{

            $this->display();
        }
    }


    public function single_edit()
    {
        $village_id = $this->house_session['village_id'];
        $property_id = $this->house_session['property_id'];
        // 查询小区物业服务管家信息
        $house_services_housekeeper = M('House_services_housekeeper');
        $house_worker = M('House_worker');
        if(IS_POST){
            $id = $_POST['id'];
            $long_lat = explode(',',$_POST['long_lat']);
            if ($long_lat && $long_lat[0] && $long_lat[1]) {
                $data['long'] = floatval($long_lat[0]);
                $data['lat'] = floatval($long_lat[1]);
            }
            if (isset($_POST['contract_time_start'])&&!empty($_POST['contract_time_start'])) {
                $_POST['contract_time_start'] =strtotime($_POST['contract_time_start'].' 00:00:00');
            }
            $database_house_village_config = D('House_village_config');
            $village_config = $database_house_village_config->where(array('village_id'=>$village_id))->find();
            $village_single_support_digit = intval($village_config['village_single_support_digit']) ? intval($village_config['village_single_support_digit']) : 2;
            if (!$village_single_support_digit || $village_single_support_digit!=3) {
                $village_single_support_digit = 2;
            }
            $village_about_info = M('House_village_info')->where(array('village_id'=>$village_id))->find();
            if (!empty($village_about_info)){
                if ($village_about_info['contract_time_end']>1){
                    $_POST['contract_time_end']=$village_about_info['contract_time_end'];
                }else{
                    $_POST['contract_time_end'] = '';
                }
            }else{
                $_POST['contract_time_end'] = '';
            }
            if(empty($_POST['contract_time_end']) && !empty($_POST['contract_time_start'])){
                $this->error('请先去小区设置合同时间');
            }
            if(!empty($_POST['contract_time_start'])&&!empty($_POST['contract_time_end'])&&$_POST['contract_time_start']>=$_POST['contract_time_end']){
                $this->error('合同开始时间不能大于合同结束时间');
            }
            if(!empty($_POST['contract_time_start']) && $_POST['contract_time_start'] < $village_about_info['contract_time_start']){
                $this->error('楼栋合同开始时间不能小于小区合同开始时间');
            }
            if (!empty($_POST['contract_time_start'])&&!empty($_POST['contract_time_end'])){
                $room_data=[];
                $room_data['village_id']=$village_id;
                $room_data['single_id']=$id;
                $room_data['contract_time_start']=$_POST['contract_time_start'];
                $room_data['contract_time_end']=$_POST['contract_time_end'];
                $res=D('House_village_user_vacancy')->getVacancyServiceTime($room_data);
                if ($res['status']!=1){
                    $this->error($res['msg']);
                }
            }
            $data['single_name'] = trim($_POST['single_name']);
            $data['status'] = $_POST['status'];
            $data['sort'] = $_POST['sort'];
            $data['measure_area'] = $_POST['measure_area'];
            $data['floor_num'] = $_POST['floor_num'];
            $data['vacancy_num'] = $_POST['vacancy_num'];
            $data['upper_layer_num'] = $_POST['upper_layer_num'];
            $data['lower_layer_num'] = $_POST['lower_layer_num'];
            $data['single_keeper_name'] = $_POST['single_keeper_name'] ? $_POST['single_keeper_name'] : '';
            $data['single_keeper_phone'] = $_POST['single_keeper_phone'] ? $_POST['single_keeper_phone']:'';
            $data['single_keeper_head'] = $_POST['single_keeper_head'] ? $_POST['single_keeper_head'] : '';
            $data['contract_time_start'] = $_POST['contract_time_start'] ? $_POST['contract_time_start'] : 1;
            $data['contract_time_end'] = $_POST['contract_time_end'] ? $_POST['contract_time_end'] : 1;
            // 小红马 楼栋修改时 不绑定员工
            /*if(empty($_POST['work_arr'])){
                $this->error('请绑定员工');
            }*/
//            $pattern = '/^[a-zA-Z0-9]+$/u';
//            if(!preg_match($pattern,trim($_POST['single_name']))){
//                $this->error('楼栋只允许字母或数字');
//            }
            if(!trim($_POST['single_name'])){
                $this->error('请填写楼栋名称');
            }
            $where_single_repeat = [
                'village_id'=>$this->village_id,
                'single_name'=>$data['single_name'],
                'id'=>['neq', $id],
                'status' => ['neq', 4]
            ];
            $single = D('House_village_single')->where($where_single_repeat)->find();
            if($single){
                $this->error('楼栋名称已存在,请重新添加!');
            }
            if (isset($_POST['single_number']) && trim($_POST['single_number'])) {
                $check_number = "/^[0-9]+$/";
                $single_number = trim($_POST['single_number']);
                if(!preg_match($check_number, $single_number)){
                    $this->error('楼栋编号只允许数字');
                }
                if((intval($single_number)<=0 || intval($single_number)>99) && $village_single_support_digit<3){
                    $this->error('楼栋编号必须为1-99的数字');
                }
                if (strlen($single_number)>2 && $village_single_support_digit<3) {
                    $this->error('楼栋编号只允最多2位数字');
                } elseif (strlen($single_number)<$village_single_support_digit) {
                    // 不足2位 补足2位
                    $single_number = str_pad($single_number,$village_single_support_digit,"0",STR_PAD_LEFT);
                }
                $data['single_number'] = $single_number;
            }
            if (!$data['single_number']) {
                $pattern_number = "/[^0-9]/";
                $single_number = preg_replace($pattern_number,'',$data['single_name']);
                // 楼栋编号 暂时同一个小区下仅限1-99数字编号
                if ($single_number) {
                    if (strlen($single_number)>2) {
                        // 超过2位 截取后2位
                        $single_number = substr($single_number, -2,2);
                    } elseif (strlen($single_number)<2) {
                        // 不足2位 补足2位
                        $single_number = str_pad($single_number,2,"0",STR_PAD_LEFT);
                    }
                    $data['single_number'] = $single_number;
                }
            }
            if ($data['single_number']) {
                $where_repeat = [
                    'village_id' => $this->village_id,
                    'single_number' => $data['single_number'],
                    'status' => ['neq', 4]
                ];
                $repeat_id = D('House_village_single')->where($where_repeat)->getField('id');
                if ($repeat_id && $repeat_id!=$id) {
                    $this->error('该楼栋编号已经存在！');
                }
            } else {
                $this->error('请填写不重复的楼栋编号');
            }
            $result = D('House_village_single')->where(array('id'=>$id))->data($data)->save();
            if($result !== false){
                // 修改成功且修改了楼栋编号触发编号其楼栋下房屋编号和对应房屋人员编号变动
                if ($data['single_number']) {
                    $change_param = [
                        'single_id' => $id,
                        'single_number' => $data['single_number'],
                    ];
                    D('House_village_user_vacancy')->change_number($change_param);
                }

                $housekeeper_id = I('housekeeper_id',0,'intval');
                if ($housekeeper_id) {
                    $where_services_housekeeper = [
                        'housekeeper_id' => $housekeeper_id,
                    ];
                    $services_housekeeper = $house_services_housekeeper->where($where_services_housekeeper)->find();
                    if (!$services_housekeeper){
                        $this->error('对应操作对象不存在！');
                    }
                }
                $data[] = '';
                $data['village_id'] = $village_id;
                $data['property_id'] = $property_id;
                $data['single_id'] = $id;
                $data['is_kefu'] = $_POST['is_kefu'];
                if ($_POST['work_arr']) {
                    $data['work_arr'] = implode(',',$_POST['work_arr']);
                }
                if ($_POST['welcome_tip']) {
                    $data['welcome_tip'] = $_POST['welcome_tip'];
                }
                if ($_POST['qy_qrcode']) {
                    $data['qy_qrcode'] = $_POST['qy_qrcode'];
                }
                if (isset($_POST['template_type'])) {
                    $data['template_type'] = intval($_POST['template_type']);
                }
                if ($_POST['template_url']) {
                    $data['template_url'] = $_POST['template_url'];
                }
                if ($_POST['effect_img']) {
                    $data['effect_img'] = $_POST['effect_img'];
                }
                if(isset($data['template_type']) && empty($data['template_type'])){
                    $data['effect_img'] ='';
                }
                if ($housekeeper_id) {
                    $data['last_time'] = time();
                    $set = $house_services_housekeeper->where($where_services_housekeeper)->data($data)->save();
                } else {
                    $data['add_time'] = time();
                    $set = $house_services_housekeeper->data($data)->add();
                }
                if (!$set) {
                    $this->success('楼栋管家更改或添加失败');exit;
                }
                $this->success('修改成功！',U('single_list'));
            }else{
                $this->error('修改失败！请重试。');
            }

        }else{
			$where = [
				'id' => intval($_GET['id']),
				'village_id' => $this->village_id,
			];
            $single = D('House_village_single')->where($where)->find();
            if ($single['contract_time_start']>1){
                $single['contract_time_start']=date('Y-m-d',$single['contract_time_start']);
                $single['contract_time_end']=date('Y-m-d',$single['contract_time_end']);
            }else{
                $village_about_info = M('House_village_info')->where(array('village_id'=>$this->village_id))->find();
                if (!empty($village_about_info)){
                    if ($village_about_info['contract_time_end']>1){
                        $single['contract_time_end']=date('Y-m-d',$village_about_info['contract_time_end']);
                        $single['contract_time_start']=date('Y-m-d',$village_about_info['contract_time_start']);
                    }else{
                        $single['contract_time_end']='';
                        $single['contract_time_start']='';
                    }
                }else{
                    $single['contract_time_end']='';
                    $single['contract_time_start']='';
                }
            }
            $this->assign('single',$single);

            $where_services_housekeeper = [
                'village_id' => $single['village_id'],
                'single_id' => $single['id'],
                'floor_id' => 0,
            ];
            $services_housekeeper = $house_services_housekeeper->where($where_services_housekeeper)->find();
            if ($services_housekeeper && $services_housekeeper['work_arr']) {
                $work_arr = explode(',',$services_housekeeper['work_arr']);
                if ($work_arr) {
                    $where_workers = [
                        'wid' => ['in', $work_arr],
                        'status' => 1
                    ];
                    $work_arr_info = $house_worker->where($where_workers)->field('wid,name')->select();
                    $this->assign('work_arr_info', $work_arr_info);
                }
            } else {
                $work_arr = [];
            }
            $this->assign('work_arr', $work_arr);
            $this->assign('detail', $services_housekeeper);
            $qyhelp_url = $this->config['site_url'].'/static/file/village/information/qyhelp.pdf';
            $this->assign('qyhelp_url', $qyhelp_url);

            $enterprise_wx_corpid = D('House_enterprise_wx_bind')->where(array('bind_id' => $property_id, 'bind_type' => 0))->getField('corpid');
            // 查询该小区小所有工作人员
            if ($enterprise_wx_corpid) {
                $where_worker = [
                    'village_id' => $village_id,
                    'qy_status' => 1,
                    'qy_id' => ['neq', ''],
                    'status' => 1,
                    'is_del'=>0,
                ];
            } else {
                $where_worker = [
                    'village_id' => $village_id,
                    'status' => 1,
                    'is_del'=>0,
                ];
            }
            $worker_list = $house_worker->where($where_worker)->order('wid ASC')->select();
            if (!$worker_list) {
                $worker_list = [];
            }
            $this->assign('worker_list', $worker_list);

            $this->assign('enterprise_wx_corpid', $enterprise_wx_corpid);
            $this->display();

        }
    }

	public function single_delete(){
		$id = $_GET['id'];
		$where = [
			'id' => intval($_GET['id']),
			'village_id' => $this->village_id,
		];
        $pigcms_id=M('House_village_floor')->where(['village_id'=>$where['village_id'],'single_id'=>$where['id'],'status'=>['neq', 4]])->getField('floor_id');
        if($pigcms_id){
            $this->error('该楼栋下存在单元不可删除，请先删除单元。');
        }
		if(M('House_village_single')->where($where)->data(['status' => 4])->save()){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

    //楼栋导入
    public function single_import()
    {
        if (!in_array(465, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $database_house_village_config = D('House_village_config');
        $village_config = $database_house_village_config->where(array('village_id'=>$this->village_id))->find();
        $village_single_support_digit = intval($village_config['village_single_support_digit']) ? intval($village_config['village_single_support_digit']) : 2;
        if (!$village_single_support_digit || $village_single_support_digit!=3) {
            $village_single_support_digit = 2;
        }
        $max_num = 99;
        if ($village_single_support_digit==3) {
            $max_num = 999;
        }
        if(IS_POST){
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');

                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
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
                // 去除非数字部分
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);  //删除文件
                    $error_arr = array();
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        if(count($result) == 0){
                            $this->assign('waitSecond',10);
                            $this->error('表格未成功识别。请使用 excel软件 将表格更换为 xlsx格式 重试一下');
                        }
                        // 字符串替换去除数字以外的
                        $pattern_number = "/[^0-9]/";
                        // 正则检查是否为纯数字
                        $check_number = "/^[0-9]+$/";
                        // 正则检查是否为字母或数字
                        $pattern = '/^[a-zA-Z0-9]+$/u';
                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            if ($vv['A'] === null  && $vv['B'] === null  && $vv['C'] === null ) continue;
                            $single_number =  htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            if (empty($single_number)) {
                                $vv['F'] = '请填写楼栋编号！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            if(!preg_match($check_number,$single_number)){
                                $vv['F'] = '楼栋编号只允许数字';
                                $error_arr[] = $vv;
                                continue;
                            }
                            if ($single_number<0 || $single_number>$max_num) {
                                $vv['F'] = "该楼栋编号不符合规则，请填写其他1-{
                $max_num}的楼栋名称！";
                                $error_arr[] = $vv;
                                continue;
                            }
                            $single_name =  htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            if (empty($single_name)) {
                                $single_name = $single_number;
                            }
                            // 查询一下不允许重复导入同一楼栋信息
                            $w = array(
                                'single_name' => $single_name,
                                'village_id' => $this->village_id,
                                'status' => ['neq', 4]
                            );
                            $msg = $single = D('House_village_single')->where($w)->find();
                            if ($msg) {
                                $vv['F'] = "楼栋【{
                $single_name}】已经添加不可重复添加！";
                                $error_arr[] = $vv;
                                continue;
                            }


                            $tmpdata = array();
                            $tmpdata['single_name'] = $single_name;
                            $tmpdata['measure_area'] = $vv['C'] ? $vv['C']:0;
                            $tmpdata['floor_num'] = $vv['D'] ? $vv['D']:0;
                            $tmpdata['vacancy_num'] = $vv['E'] ? $vv['E']:0;
                            $tmpdata['status'] = 1;
                            $tmpdata['sort'] = 0;
                            $tmpdata['village_id'] = $this->village_id;

                            if (!$single_number) {
                                $single_number = preg_replace($pattern_number,'',$tmpdata['single_name']);
                                // 楼栋编号 暂时同一个小区下仅限1-99数字编号
                                if ($single_number) {
                                    if (strlen($single_number)>$village_single_support_digit) {
                                        // 超过2位 截取后2位
                                        $single_number = substr($single_number, -$village_single_support_digit,$village_single_support_digit);
                                    } elseif (strlen($single_number)<$village_single_support_digit) {
                                        // 不足2位 补足2位
                                        $single_number = str_pad($single_number,$village_single_support_digit,"0",STR_PAD_LEFT);
                                    }
                                    $tmpdata['single_number'] = $single_number;
                                    // 去除重复
                                    $where_repeat = [
                                        'village_id' => $this->village_id,
                                        'single_number' => $single_number,
                                        'status' => ['neq', 4]
                                    ];
                                    $repeat_id = D('House_village_single')->where($where_repeat)->getField('id');
                                    if ($repeat_id) {
                                        $vv['F'] = "该楼栋编号【取楼栋名称后{ $village_single_support_digit}位】已经存在，请填写其他1-{$max_num}的楼栋名称！";
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                }
                            } else {
                                $tmpdata['single_number'] = $single_number;
                                // 去除重复
                                $where_repeat = [
                                    'village_id' => $this->village_id,
                                    'single_number' => $single_number,
                                    'status' => ['neq', 4]
                                ];
                                $repeat_id = D('House_village_single')->where($where_repeat)->getField('id');
                                if ($repeat_id) {

                                    $vv['F'] = "该楼栋编号已经存在，请填写其他1-{$max_num}的楼栋名称！";

                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }

                            $last_user_id = D('House_village_single')->data($tmpdata)->add();
                            if (!$last_user_id) {
                                $vv['F'] = '保存失败。';
                                $error_arr[] = $vv;
                            }
                        }
                        if (empty($error_arr)) {
                            $this->success('导入成功');
                            exit;
                        } else {
                            $num = count($error_arr);
                            echo '<javascript>alert("失败'. $num .'条，点击下载)";</javascript>';
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);
                            date_default_timezone_set('Europe/London');
                            $objExcel = new PHPExcel();

                            $title = $this->village['village_name'] . '社区-楼栋导入失败列表';
                            /*以下是一些设置 ，什么作者  标题之类的*/
                            $objExcel->getProperties()->setCreator($title)
                                ->setLastModifiedBy($title)
                                ->setTitle($title)
                                ->setSubject("失败信息导出")
                                ->setDescription("备份数据")
                                ->setKeywords("excel")
                                ->setCategory("result file");

                            $i = 0;
                            $objExcel->createSheet();
                            $objExcel->setActiveSheetIndex($i);
                            $objExcel->getActiveSheet()->setTitle('楼栋列表');
                            $objActSheet = $objExcel->getActiveSheet();
                            $objActSheet->setCellValue('A1', '楼栋名称');
                            $objActSheet->setCellValue('B1', '楼栋编号');
                            $objActSheet->setCellValue('C1', '楼栋面积(单位:m²)');
                            $objActSheet->setCellValue('D1', '所含单元数(个)');
                            $objActSheet->setCellValue('E1', '所含房屋数(个)');

                            $objActSheet->setCellValue('F1', '失败原因');
                            $index = 2;
                            foreach ($error_arr as  $value) {
                                $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                $objActSheet->setCellValueExplicit('F' . $index, $value['F']);

                                $index++;
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
                            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        }else{

            $tip = "楼栋编号必须是与当前现有楼栋编号不重复的1-{$max_num}的数字，楼栋名称如有特殊命名，可通过编辑修改";

            $this->assign('tip',$tip);
            $this->display();
        }

    }

    public function unit_download(){


        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $user_list[] = array('','示例','01','1.1','2','1120.1','1','1120.1','101.63	','20','12','3','1','10');


        $title = '示例单元模板';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = 1;

        $where['village_id'] = $this->village_id;
        $where['status'] = 1;
        $single = D('House_village_single')->where($where)->order('sort desc')->select();
        if($single){
            $single_data = [];
            foreach($single as $k=>$v){
                $single_data[] = $v['single_name'];
            }
            $single_data = implode(',', $single_data);
        }else{
            $single_data = '';
        }

        $floor_type['village_id'] = $this->village_id;
        $floor_type['status'] = 1;
        $floor_type_list = D('House_village_floor_type')->where($where)->select();
        if($floor_type_list){
            $floor_type_data = [];
            foreach($floor_type_list as $k=>$v){
                $floor_type_data[] = $v['name'];
            }
            $floor_type_data = implode(',', $floor_type_data);
        }else{
            $floor_type_data = '';
        }

//        var_dump($single_data);die;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('导入单元模板');
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '单元名称（选填）');
            $objActSheet->setCellValue('B1', '单元编号（必填）');

            $objActSheet->setCellValue('C1', '所属楼栋（请与后台添加的楼栋名称保持一致，可下拉选择）');
            $objActSheet->setCellValue('D1', '单元面积');


            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range('A','L');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($user_list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value[1]);
                    $objActSheet->setCellValueExplicit('B' . $index, $value[2]);
                    $objActSheet->setCellValueExplicit('C' . $index, $value[0]);
                    $objActSheet->setCellValueExplicit('D' . $index, $value[3]);

                    $objExcel->setActiveSheetIndex(0);
                    $objActSheet = $objExcel->getActiveSheet();
                    $objValidation = $objActSheet->getCell("C2")->getDataValidation(); //这一句为要设置数据有效性的单元格

                    $objValidation -> setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                        -> setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                        -> setAllowBlank(false)
                        -> setShowInputMessage(true)
                        -> setShowErrorMessage(true)
                        -> setShowDropDown(true)
                        -> setErrorTitle('输入的值有误')
                        -> setError('您输入的值不在下拉框列表内.')
                        -> setPromptTitle('楼号')
                        -> setFormula1('"'.$single_data.'"');
                    $index++;
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
    }

    //老板本 生成导入房间试例 （暂不用）2020/9/3
    public function village_import_samples()
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $user_list[] = array('1-888','5号楼','三单元','12','1102','150','住宅');


        $title = '导入房间模板';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->getStyle('B1')->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->setTitle('导入房间模板');
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '物业编号（不能重复，保持独一性）');
            $objActSheet->setCellValue('B1', '所属楼栋（请与后台添加的楼栋名称保持一致）');
            $objActSheet->setCellValue('C1', '所属单元（请与后台添加的单元名称保持一致）');
            $objActSheet->setCellValue('D1', '楼层名称（请与后台添加的楼层名称保持一致）');
            $objActSheet->setCellValue('E1', '房号');
            $objActSheet->setCellValue('F1', '房子平方(必填/计算物业费使用)');
            $objActSheet->setCellValue('G1', '使用类型(填住宅/商铺/办公)');

            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range('A','G');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($user_list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                    $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                    $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                    $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                    $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                    $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                    $objActSheet->setCellValueExplicit('G' . $index, $value[6]);
                    $index++;
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
    }
    //生成导入房间试例 2020/9/3
    public function village_import_sample()
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        if ($is_new_charge==1){// 小区合同开始<楼栋开始<房屋开始<房屋结束<楼栋结束<小区合同結束
            $prompt = '注意：楼栋编号/单元编号/楼层编号/房间编号必须填写阿拉伯数字（1,2,3）。若楼栋/单元/楼层不存在，系统会自动创建，无需人工进行添加/导入。若当前小区有设置合同起始时间，则当前模板中的物业开始时间和物业结束时间为当前小区的合同开始时间和合同结束时间，房间别名编号是每个房间的唯一数字编号，请填大于1的数字，不可重复。排序字段填写数字，排序值数字越大越靠前。编号存在以编号优先匹配对象';
            $start_time='2020年2月22日';
            $end_time='2035年2月21日';
            $timeInfo=D('House_village_info')->where(['village_id'=>$this->village_id])->field('contract_time_start,contract_time_end,village_id')->find();
           if (!empty($timeInfo)){
               if ($timeInfo['contract_time_start']>1){
                   $start_time=date('Y年m月d日',$timeInfo['contract_time_start']);
               }
               if ($timeInfo['contract_time_end']>1){
                   $end_time=date('Y年m月d日',$timeInfo['contract_time_end']);
               }
           }
            $user_list[] = array('1-888','1栋','1','2单元','2','1层','1','01','01','156','住宅',$start_time,$end_time,'12101','0');

            $title = '导入房间模板';

            $objExcel = new PHPExcel();
            $objProps = $objExcel->getProperties();
            // 设置文档基本属性
            $objProps->setCreator($title);
            $objProps->setTitle($title);
            $objProps->setSubject($title);
            $objProps->setDescription($title);

            $length = 1;
            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);
                $objExcel->getActiveSheet()->mergeCells('A1:O1');

                $objExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setWrapText(true);
                $objExcel->getActiveSheet()->getStyle('B2')->getAlignment()->setWrapText(true);
                $objExcel->getActiveSheet()->setTitle('导入房间模板');
                $objActSheet = $objExcel->getActiveSheet();

                $objActSheet->setCellValue('A1',$prompt);
                $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(12)->getColor()->setRGB('FF0000');
                $objExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(23);
                $objActSheet->setCellValue('A2', '物业编号（非必填）');
                $objActSheet->setCellValue('B2', '楼栋');
                $objActSheet->setCellValue('C2', '楼栋编号');
                $objActSheet->setCellValue('D2', '单元');
                $objActSheet->setCellValue('E2', '单元编号');
                $objActSheet->setCellValue('F2', '楼层');
                $objActSheet->setCellValue('G2', '楼层编号');
                $objActSheet->setCellValue('H2', '房间号');
                $objActSheet->setCellValue('I2', '房间编号');
                $objActSheet->setCellValue('J2', '面积(m²)');
                $objActSheet->setCellValue('K2', '使用类型(填住宅/商铺/办公)');
                $objActSheet->setCellValue('L2', '物业开始时间');
                $objActSheet->setCellValue('M2', '物业结束时间');
                $objActSheet->setCellValue('N2', '房间别名编号');
                $objActSheet->setCellValue('O2', '排序');
                if (!empty($user_list)) {
                    $index = 3;
                    $cell_list = range('A','O');
                    foreach ($cell_list as $cell) {
                        $objActSheet->getColumnDimension($cell)->setWidth(30);
                    }
                    foreach ($user_list as $value) {
                        $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                        $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                        $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                        $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                        $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                        $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                        $objActSheet->setCellValueExplicit('G' . $index, $value[6]);
                        $objActSheet->setCellValueExplicit('H' . $index, $value[7]);
                        $objActSheet->setCellValueExplicit('I' . $index, $value[8]);
                        $objActSheet->setCellValueExplicit('J' . $index, $value[9]);
                        $objActSheet->setCellValueExplicit('K' . $index, $value[10]);
                        $objActSheet->setCellValueExplicit('L' . $index, $value[11]);
                        $objActSheet->setCellValueExplicit('M' . $index, $value[12]);
                        $objActSheet->setCellValueExplicit('N' . $index, $value[13]);
                        $objActSheet->setCellValueExplicit('O' . $index, $value[14]);
                        $index++;
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
        }else{
            $prompt = '注意：楼栋编号/单元编号/楼层编号/房间编号必须填写阿拉伯数字（1,2,3）。若楼栋/单元/楼层不存在，系统会自动创建，无需人工进行添加/导入。排序字段填写数字，排序值数字越大越靠前';
            $user_list[] = array('1-888','1栋','1','2单元','2','1层','1','01','01','156','住宅','0');

            $title = '导入房间模板';

            $objExcel = new PHPExcel();
            $objProps = $objExcel->getProperties();
            // 设置文档基本属性
            $objProps->setCreator($title);
            $objProps->setTitle($title);
            $objProps->setSubject($title);
            $objProps->setDescription($title);

            $length = 1;
            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);
                $objExcel->getActiveSheet()->mergeCells('A1:L1');

                $objExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setWrapText(true);
                $objExcel->getActiveSheet()->getStyle('B2')->getAlignment()->setWrapText(true);
                $objExcel->getActiveSheet()->setTitle('导入房间模板');
                $objActSheet = $objExcel->getActiveSheet();

                $objActSheet->setCellValue('A1',$prompt);
                $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(12)->getColor()->setRGB('FF0000');
                $objExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(23);
                $objActSheet->setCellValue('A2', '物业编号（非必填）');
                $objActSheet->setCellValue('B2', '楼栋');
                $objActSheet->setCellValue('C2', '楼栋编号');
                $objActSheet->setCellValue('D2', '单元');
                $objActSheet->setCellValue('E2', '单元编号');
                $objActSheet->setCellValue('F2', '楼层');
                $objActSheet->setCellValue('G2', '楼层编号');
                $objActSheet->setCellValue('H2', '房间号');
                $objActSheet->setCellValue('I2', '房间编号');
                $objActSheet->setCellValue('J2', '面积(m²)');
                $objActSheet->setCellValue('K2', '使用类型(填住宅/商铺/办公)');
                $objActSheet->setCellValue('L2', '排序');
                if (!empty($user_list)) {
                    $index = 3;
                    $cell_list = range('A','L');
                    foreach ($cell_list as $cell) {
                        $objActSheet->getColumnDimension($cell)->setWidth(30);
                    }
                    foreach ($user_list as $value) {
                        $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                        $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                        $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                        $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                        $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                        $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                        $objActSheet->setCellValueExplicit('G' . $index, $value[6]);
                        $objActSheet->setCellValueExplicit('H' . $index, $value[7]);
                        $objActSheet->setCellValueExplicit('I' . $index, $value[8]);
                        $objActSheet->setCellValueExplicit('J' . $index, $value[9]);
                        $objActSheet->setCellValueExplicit('K' . $index, $value[10]);
                        $objActSheet->setCellValueExplicit('L' . $index, $value[11]);
                        $index++;
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
        }

    }
    public function village_import_num()
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $prompt = '注意：楼号、单元名称、层号、房间号需要与小区后台中已有的基础资源保持名称一致';
        $user_list[] = [];

        $title = '导入三表模板';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->mergeCells('A1:K1');

            $objExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->getStyle('B2')->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->setTitle('导入三表模板');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1',$prompt);
            $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(12)->getColor()->setRGB('FF0000');
            $objExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(23);
            $objActSheet->setCellValue('A2', '楼号');
            $objActSheet->setCellValue('B2', '单元名称');
            $objActSheet->setCellValue('C2', '层号');
            $objActSheet->setCellValue('D2', '房间号');
            $objActSheet->setCellValue('E2', '电表编号');
            $objActSheet->setCellValue('F2', '冷水表编号');
            $objActSheet->setCellValue('G2', '热水表编号');
            $objActSheet->setCellValue('H2', '燃气表编号');

     

            if (!empty($user_list)) {
                $index = 3;
                $cell_list = range('A','G');
              
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }
                foreach ($user_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                    $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                    $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                    $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                    $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                    $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                    $objActSheet->setCellValueExplicit('G' . $index, $value[6]);
                    $objActSheet->setCellValueExplicit('H' . $index, $value[7]);
                    $index++;
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
    }
    public function edit_stored_value_card(){

        if(IS_POST){

        }else{

            $this->display();
        }
    }

    public function del_stored_value_card(){


    }

    public function layer_list()
    {
        if (!in_array(550, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->village_id;
        $whereArr=array('village_id'=>$village_id, 'floor_id'=>$_GET['floor_id'], 'status'=>['neq', 4],'is_public_rental'=>0);
        $count_user = M('House_village_layer')->where($whereArr)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $village_layer = M('House_village_layer')->where($whereArr)->order('status DESC,sort DESC, id ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if($village_layer){
            foreach($village_layer as $k=>&$v){
                $single = M('House_village_single')->field('single_name')->where(array('id'=>$v['single_id']))->find();
                $v['single_name'] = $single['single_name'];
                $floor = M('House_village_floor')->field('floor_name')->where(array('floor_id'=>$v['floor_id']))->find();
                $v['floor_name'] = $floor['floor_name'];
            }
        }
        $this->assign('dataList',$village_layer);
        $this->assign('pagebar',$p->show());
        $this->display();
    }

    public function layer_add()
    {
        if (!in_array(551, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
       if(IS_POST){
           if(empty($_POST['layer_name'])){
               $this->error('楼层名称不能为空');
           }
//           $pattern = '/^[a-zA-Z0-9]+$/u';
//           if(!preg_match($pattern,trim($_POST['layer_name']))){
//               $this->error('楼层名称只允许字母或数字');
//           }
           $data['layer_name'] = $_POST['layer_name'];
           $data['sort'] = $_POST['sort'];
           $data['status'] = $_POST['status'];
           $data['single_id'] = $_GET['single_id'];
           $data['floor_id'] = $_GET['floor_id'];
           $data['village_id'] = $this->village_id;
           $where_layer_repeat = [
               'village_id'=>$this->village_id,
               'floor_id'=>$_GET['floor_id'],
               'layer_name'=>$data['layer_name'],
               'status' => ['neq', 4]
           ];
           $layer = D('House_village_layer')->where($where_layer_repeat)->find();
           if($layer){
               $this->error('楼层名称已存在,请重新添加!');
           }

           if (isset($_POST['layer_number']) && trim($_POST['layer_number'])) {
               $check_number = "/^[0-9]+$/";
               $layer_number = trim($_POST['layer_number']);
               if(!preg_match($check_number, $layer_number)){
                   $this->error('楼层编号只允许数字');
               }
               if(intval($layer_number)<=0 || intval($layer_number)>99){
                   $this->error('楼层编号必须为1-99的数字');
               }
               if (strlen($layer_number)>2) {
                   $this->error('楼层编号只允最多2位数字');
               } elseif (strlen($layer_number)<2) {
                   // 不足2位 补足2位
                   $layer_number = str_pad($layer_number,2,"0",STR_PAD_LEFT);
               }
               $data['layer_number'] = $layer_number;
           }
           if (!$data['layer_number'] && $data['layer_name']) {
               $pattern_number = "/[^0-9]/";
               $layer_number = preg_replace($pattern_number,'',$data['layer_name']);
               // 楼层编号 暂时同一个小区下仅限1-99数字编号
               if ($layer_number) {
                   if (strlen($layer_number)>2) {
                       // 超过2位 截取后2位
                       $layer_number = substr($layer_number, -2,2);
                   } elseif (strlen($layer_number)<2) {
                       // 不足2位 补足2位
                       $layer_number = str_pad($layer_number,2,"0",STR_PAD_LEFT);
                   }
                   $data['layer_number'] = $layer_number;
               }
           }
           if ($data['layer_number']) {
               $where_repeat = [
                   'village_id' => $this->village_id,
                   'single_id' => $data['single_id'],
                   'floor_id' => $data['floor_id'],
                   'layer_number' => $data['layer_number'],
                   'status' => ['neq', 4]
               ];
               $repeat_id = D('House_village_layer')->where($where_repeat)->getField('id');
               if ($repeat_id) {
                   $this->error('该楼层编号已经存在，请填写1-99的楼层编号');
               }
           } else {
               $this->error('请填写1-99的不重复楼层编号');
           }

           $res = M('House_village_layer')->data($data)->add();
           if($res){
               $this->success('添加成功!',U('layer_list',array('single_id'=>$_GET['single_id'],'floor_id'=>$_GET['floor_id'])));
           }else{
               $this->error('添加失败!');
           }
       }else{

           $this->display();
       }
    }

    public function layer_edit()
    {
        if (!in_array(552, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if(IS_POST){
            $id = $_POST['id'];
            if(empty($_POST['layer_name'])){
                $this->error('楼层名称不能为空');
            }
//            $pattern = '/^[a-zA-Z0-9]+$/u';
//            if(!preg_match($pattern,trim($_POST['layer_name']))){
//                $this->error('楼层名称只允许字母或数字');
//            }
            $data['layer_name'] = $_POST['layer_name'];
            $data['sort'] = $_POST['sort'];
            $data['status'] = $_POST['status'];
            $layer_info = D('House_village_layer')->where(array('id'=>$id))->find();
            $where_repeat = [
                'village_id' => $this->village_id,
                'single_id' => $layer_info['single_id'],
                'floor_id' => $layer_info['floor_id'],
                'layer_name' => $data['layer_name'],
                'status' => ['neq', 4],
                'id' => ['neq', $id],
            ];
            $repeat_ids = D('House_village_layer')->where($where_repeat)->getField('id');
            if ($repeat_ids) {
                $this->error('该楼层已经存在');
            }

            if (isset($_POST['layer_number']) && trim($_POST['layer_number'])) {
                $check_number = "/^[0-9]+$/";
                $layer_number = trim($_POST['layer_number']);
                if(!preg_match($check_number, $layer_number)){
                    $this->error('楼层编号只允许数字');
                }
                if(intval($layer_number)<=0 || intval($layer_number)>99){
                    $this->error('楼层编号必须为1-99的数字');
                }
                if (strlen($layer_number)>2) {
                    $this->error('楼层编号只允最多2位数字');
                } elseif (strlen($layer_number)<2) {
                    // 不足2位 补足2位
                    $layer_number = str_pad($layer_number,2,"0",STR_PAD_LEFT);
                }
                $data['layer_number'] = $layer_number;
            }
            if (!$data['layer_number'] && $data['layer_name']) {
                $pattern_number = "/[^0-9]/";
                $layer_number = preg_replace($pattern_number,'',$data['layer_name']);
                // 楼层编号 暂时同一个小区下仅限1-99数字编号
                if ($layer_number) {
                    if (strlen($layer_number)>2) {
                        // 超过2位 截取后2位
                        $layer_number = substr($layer_number, -2,2);
                    } elseif (strlen($layer_number)<2) {
                        // 不足2位 补足2位
                        $layer_number = str_pad($layer_number,2,"0",STR_PAD_LEFT);
                    }
                    $data['layer_number'] = $layer_number;
                }
            }
            if ($data['layer_number']) {
                $where_repeat = [
                    'village_id' => $this->village_id,
                    'single_id' => $layer_info['single_id'],
                    'floor_id' => $layer_info['floor_id'],
                    'layer_number' => $data['layer_number'],
                    'status' => ['neq', 4]
                ];
                $repeat_id = D('House_village_layer')->where($where_repeat)->getField('id');
                if ($repeat_id && $repeat_id!=$layer_info['id']) {
                    $this->error('该楼层编号已经存在，请填写1-99的楼层编号');
                }
            } else {
                $this->error('请填写1-99的不重复楼层编号');
            }
            $result = D('House_village_layer')->where(array('id'=>$id))->data($data)->save();
            if($result !== false){
                // 修改成功且修改了楼层编号触发编号其单元下房屋编号和对应房屋人员编号变动
                if ($data['layer_number']) {
                    $change_param = [
                        'layer_id' => $layer_info['id'],
                        'layer_number' => $data['layer_number'],
                    ];
                    D('House_village_user_vacancy')->change_number($change_param);
                }
                $this->success('修改成功！',U('layer_list',array('single_id'=>$_GET['single_id'],'floor_id'=>$_GET['floor_id'])));
            }else{
                $this->error('修改失败！请重试。');
            }
        }else{

            $id = $_GET['id'];
			$where = [
				'id' => intval($_GET['id']),
				'village_id' => $this->village_id,
			];
            $single = D('House_village_layer')->where($where)->find();
            $this->assign('single',$single);
            $this->display();
        }
    }

	public function layer_delete(){
		$id = $_GET['id'];
		$where = [
			'id' => intval($_GET['id']),
			'village_id' => $this->village_id,
		];
        $pigcms_id=M('House_village_user_vacancy')->where(['village_id'=>$where['village_id'],'layer_id'=>$where['id'],'is_del'=>0])->getField('pigcms_id');
        if($pigcms_id){
            $this->error('该楼层下存在房间不可删除，请先删除房间。');
        }
		if(M('House_village_layer')->data(['status' => 4])->where($where)->save()){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

    public function layer_import()
    {
        if (!in_array(465, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if(IS_POST){
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');

                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
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
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);  //删除文件
                    $error_arr = array();
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        if(count($result) == 0){
                            $this->assign('waitSecond',10);
                            $this->error('表格未成功识别。请使用 excel软件 将表格更换为 xlsx格式 重试一下');
                        }
                        // 字符串替换去除数字以外的
                        $pattern_number = "/[^0-9]/";
                        // 正则检查是否为纯数字
                        $check_number = "/^[0-9]+$/";
                        // 正则检查是否为字母或数字
                        $pattern = '/^[a-zA-Z0-9]+$/u';
                        foreach ($result as $kk => $vv) {
                            /*if (array_sum($vv) == 0) {
                                continue;
                            }*/
                            if ($vv['A'] === null  && $vv['B'] === null && $vv['C'] === null ) continue;
                            $layer_number = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            if (empty($layer_number)) {
                                $vv['D'] = '楼层编号不能为空！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            if(!preg_match($check_number,$layer_number)){
                                $vv['D'] = '楼层编号只允许数字';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $layer_name = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            if (empty($layer_name)) {
                                $layer_name = $layer_number;
                            }
                            $single_name =  htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            if (empty($single_name)) {
                                $vv['D'] = '楼栋名称不能为空！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            $floor_name =  htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            if (empty($floor_name)) {
                                $vv['D'] = '单元名称不能为空！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            // 查询一下不允许重复导入同一单元信息
                            $w = array(
                                'single_name' => $single_name,
                                'village_id' => $this->village_id,
                                'status' => 1
                            );
                            $single = D('House_village_single')->where($w)->find();
                            if (!$single) {
                                $vv['D'] = '楼栋不存在！';
                                $error_arr[] = $vv;
                                continue;
                            }

                            // 查询一下不允许重复导入同一单元信息
                            $floor = array(
                                'floor_name' => $floor_name,
                                'village_id' => $this->village_id,
                                'single_id'=>$single['id'],
                                'status' => 1
                            );
                            $floor = D('House_village_floor')->where($floor)->find();
                            if (!$floor) {
                                $vv['D'] = '单元不存在！';
                                $error_arr[] = $vv;
                                continue;
                            }


                            $tmpdata = array();
                            $tmpdata['layer_name'] = $layer_name;
                            $tmpdata['single_id'] = $single['id'];
                            $tmpdata['floor_id'] = $floor['floor_id'];

                            $tmpdata['status'] = 1;
                            $tmpdata['sort'] = 0;
                            $tmpdata['village_id'] = $this->village_id;

                            $where_layer_repeat = [
                                'village_id'=>$this->village_id,
                                'single_id'=>$tmpdata['single_id'],
                                'floor_id'=>$tmpdata['floor_id'],
                                'layer_name'=>$tmpdata['layer_name'],
                                'status' => ['neq', 4]
                            ];
                            $layer_repeat = D('House_village_layer')->where($where_layer_repeat)->find();
                            if($layer_repeat){
                                $vv['D'] = '楼层名称已存在！';
                                $error_arr[] = $vv;
                                continue;
                            }
                            if (!$layer_number) {
                                $layer_number = preg_replace($pattern_number,'',$tmpdata['layer_name']);
                                // 楼层编号 暂时同一个单元下仅限于1-99数字编号
                                if ($layer_number) {
                                    if (strlen($layer_number)>2) {
                                        // 超过2位 截取后2位
                                        $layer_number = substr($layer_number, -2,2);
                                    } elseif (strlen($layer_number)<2) {
                                        // 不足2位 补足2位
                                        $layer_number = str_pad($layer_number,2,"0",STR_PAD_LEFT);
                                    }
                                    $tmpdata['layer_number'] = $layer_number;
                                    // 去除重复
                                    $where_repeat = [
                                        'village_id' => $this->village_id,
                                        'single_id' => $single['id'],
                                        'floor_id' => $floor['floor_id'],
                                        'layer_number' => $layer_number,
                                        'status' => ['neq', 4]
                                    ];
                                    $repeat_id = D('House_village_layer')->where($where_repeat)->getField('id');
                                    if ($repeat_id) {
                                        $vv['D'] = '该楼层编号【取楼层名称后2位】已经存在，请填写其他1-99的楼层名称！';
                                        $error_arr[] = $vv;
                                        continue;
                                    }
                                }
                            } else {
                                $tmpdata['layer_number'] = $layer_number;
                                // 去除重复
                                $where_repeat = [
                                    'village_id' => $this->village_id,
                                    'single_id' => $single['id'],
                                    'floor_id' => $floor['floor_id'],
                                    'layer_number' => $layer_number,
                                    'status' => ['neq', 4]
                                ];
                                $repeat_id = D('House_village_layer')->where($where_repeat)->getField('id');
                                if ($repeat_id) {
                                    $vv['D'] = "该楼层编号【{
                $layer_number}】已经存在，请填写其他1-99的楼层名称！";
                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }

                            $last_user_id = D('House_village_layer')->data($tmpdata)->add();
                            if (!$last_user_id) {
                                $vv['D'] = '保存失败。';
                                $error_arr[] = $vv;
                            }
                        }
                        if (empty($error_arr)) {
                            $this->success('导入成功');
                            exit;
                        } else {
                            $num = count($error_arr);
                            echo '<javascript>alert("失败'. $num .'条，点击下载)";</javascript>';
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);
                            date_default_timezone_set('Europe/London');
                            $objExcel = new PHPExcel();

                            $title = $this->village['village_name'] . '社区-楼层导入失败列表';
                            /*以下是一些设置 ，什么作者  标题之类的*/
                            $objExcel->getProperties()->setCreator($title)
                                ->setLastModifiedBy($title)
                                ->setTitle($title)
                                ->setSubject("失败信息导出")
                                ->setDescription("备份数据")
                                ->setKeywords("excel")
                                ->setCategory("result file");

                            $i = 0;
                            $objExcel->createSheet();
                            $objExcel->setActiveSheetIndex($i);
                            $objExcel->getActiveSheet()->setTitle('楼层列表');
                            $objActSheet = $objExcel->getActiveSheet();
                            $objActSheet->setCellValue('A1', '楼层名称');
                            $objActSheet->setCellValue('B1', '所属楼栋(请与后台添加的楼栋名称保持一致)');
                            $objActSheet->setCellValue('C1', '所属单元(请与后台添加的单元名称保持一致)');
                            $objActSheet->setCellValue('D1', '失败原因');
                            $index = 2;
                            foreach ($error_arr as  $value) {
                                $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                $index++;
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
                            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        }else{
            $tip = '楼层编号必须是与当前现有同一个单元下不重复的1-99的数字，楼层名称如有特殊命名，可通过编辑修改';
            $this->assign('tip',$tip);
            $this->display();
        }

    }

    /**
     * 财务统计
     */
    public function fee_tongji()
    {
        $this->display();
    }

    /**
     * 财务统计导出
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function export_fee()
    {
        $village_id = $this->village_id;
        $type = $_GET['type'];
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        if ($type == 'carry') {
            $time1 = $_GET['time1'];
            $count = M('House_village_fee_tongji')->where(['village_id' => $village_id, 'ydate' => date('Y', strtotime($time1)), 'mdate' => date('m', strtotime($time1))])->count();
            $title = date('m', strtotime($time1)) . '月物业收入结转统计';
            $objExcel = new PHPExcel();
            $objProps = $objExcel->getProperties();
            // 设置文档基本属性
            $objProps->setCreator($title);
            $objProps->setTitle($title);
            $objProps->setSubject($title);
            $objProps->setDescription($title);

            $length = ceil($count / 1000);
            $park_config = M('House_village_park_config')->where(array('village_id'=>$village_id))->find();
            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);
                $objExcel->getActiveSheet()->setTitle('共' . $count . '条记录');
                $objActSheet = $objExcel->getActiveSheet();
                $objExcel->getActiveSheet()->mergeCells('A1:N1');
                $objActSheet->setCellValue('A1', $title);
                $objActSheet->setCellValue('A2', '物业编号');
                $objActSheet->setCellValue('B2', '姓名');
                $objActSheet->setCellValue('C2', '面积');
                $objActSheet->setCellValue('D2', '物业费单价');
                $objActSheet->setCellValue('E2', '水费');
                $objActSheet->setCellValue('F2', '电费');
                $objActSheet->setCellValue('G2', '燃气费');
                $objActSheet->setCellValue('H2', '自定义缴费项目');
                $objActSheet->setCellValue('I2', '物业费实际缴费金额');
                $objActSheet->setCellValue('J2', '物业费结转金额');
                $objActSheet->setCellValue('K2', '物业费月末余额');
                $objActSheet->setCellValue('L2', '车位费当月缴费金额');
                if($park_config['is_park_month_type'] == 1 && $park_config['park_versions'] == 2){
                    $objActSheet->setCellValue('M2', '车位费结转金额');
                    $objActSheet->setCellValue('N2', '车位费月末余额');
                }

                $orderList = M('House_village_fee_tongji')->where(['village_id' => $village_id, 'ydate' => date('Y', strtotime($time1)), 'mdate' => date('m', strtotime($time1))])->select();

                if (!empty($orderList)) {
                    $index = 3;
                    foreach ($orderList as $value) {
                        $objActSheet->setCellValueExplicit('A' . $index, $value['usernum']);
                        $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                        $objActSheet->setCellValueExplicit('C' . $index, $value['housesize']);
                        $objActSheet->setCellValueExplicit('D' . $index, $value['property_unit_price']);
                        $objActSheet->setCellValueExplicit('E' . $index, $value['water_fee']);
                        $objActSheet->setCellValueExplicit('F' . $index, $value['ele_fee']);
                        $objActSheet->setCellValueExplicit('G' . $index, $value['gas_fee']);
                        $objActSheet->setCellValueExplicit('H' . $index, $value['custom_fee']);
                        $objActSheet->setCellValueExplicit('I' . $index, $value['property_fee']);
                        $objActSheet->setCellValueExplicit('J' . $index, $value['property_carry']);
                        $objActSheet->setCellValueExplicit('K' . $index, $value['property_remain']);
                        $objActSheet->setCellValueExplicit('L' . $index, $value['party_fee']);
                        if($park_config['is_park_month_type'] == 1 && $park_config['park_versions'] == 2){
                            $objActSheet->setCellValueExplicit('M' . $index, $value['party_carry']);
                            $objActSheet->setCellValueExplicit('N' . $index, $value['party_remain']);
                        }
                        $index++;
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
                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
                header("Content-Transfer-Encoding:binary");
                $objWriter->save('php://output');
                exit();
            }
        } else {
            $time2 = $_GET['time2'];
            $time3 = $_GET['time3'];
            $where['type'] = 0;
            $where['status'] = array('in', '1,3');
            $where['is_del'] = 0;
            $where['village_id'] = $village_id;
            $vacancy_list = M('House_village_user_vacancy')
                ->where($where)
                ->select();
            $he_all_fee = 0;
            $he_total_fee = 0;
            foreach ($vacancy_list as $k => $v) {
                $house_village_user = M('House_village_user_bind')->where(['vacancy_id'=>$v['pigcms_id'],'type'=>0,'status'=>1])->field('pigcms_id,property_endtime')->find();
                if(!$house_village_user['pigcms_id']){
                    unset($vacancy_list[$k]);
                    continue;
                }
                $start_time = $this->get_ctime($time2);
                $end_time = $this->get_ctime($time3);
                $map['update_time'] = array('between', array($start_time['firstday'], $end_time['lastday']));
                $map['village_id'] = $village_id;
                $map['usernum'] = $v['usernum'];
                $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$v['pigcms_id'],'is_del'=>1])->field('standard_id')->find();
                if(!$standard_id['standard_id']){
                    $all_fee = 0;
                }else{
                    $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                    $dateMonth = $this->dateMonths(date('Y-m-d H:i:s',$start_time['firstday']),date('Y-m-d H:i:s',$end_time['lastday']));
                    $all_fee = $this->property_price($dateMonth,unserialize($date['date_month']),$v['housesize']);
                }
                if($house_village_user['property_endtime'] >= $end_time['lastday']){
                    $total_fee = $all_fee;
                } elseif($start_time['firstday'] >= $house_village_user['property_endtime']){
                    $total_fee = 0;
                } else{
                    $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$v['pigcms_id'],'is_del'=>1])->field('standard_id')->find();
                    if(!$standard_id['standard_id']){
                        $total_fee = 0;
                    }else{
                        $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                        $dateMonth = $this->dateMonths(date('Y-m-d',$start_time['firstday']),date('Y-m-d H:i:s',$house_village_user['property_endtime']));
                        $total_fee = $this->property_price($dateMonth,unserialize($date['date_month']),$v['housesize']);
                    }
                }
                $vacancy_list[$k]['all_fee'] = $all_fee ? $all_fee : 0;
                $vacancy_list[$k]['total_fee'] = $total_fee ? $total_fee : 0;
                $he_all_fee += $vacancy_list[$k]['all_fee'];
                $he_total_fee += $vacancy_list[$k]['total_fee'];
            }
            $title = $time2 . '至' . $time3 . '物业费收缴率统计';
            $objExcel = new PHPExcel();
            $objProps = $objExcel->getProperties();
            // 设置文档基本属性
            $objProps->setCreator($title);
            $objProps->setTitle($title);
            $objProps->setSubject($title);
            $objProps->setDescription($title);

            $length = ceil(count($vacancy_list) / 1000);

            for ($i = 0; $i < $length; $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);
                $objExcel->getActiveSheet()->setTitle('共' . count($vacancy_list) . '条记录');
                $objActSheet = $objExcel->getActiveSheet();
                $objExcel->getActiveSheet()->mergeCells('A1:G1');
                $objActSheet->setCellValue('A1', $title);
                $objActSheet->setCellValue('A2', '物业编号');
                $objActSheet->setCellValue('B2', '姓名');
                $objActSheet->setCellValue('C2', '面积');
                $objActSheet->setCellValue('D2', '应收金额');
                $objActSheet->setCellValue('E2', '实收金额');
                $objActSheet->setCellValue('F2', '欠费金额');
                $objActSheet->setCellValue('G2', '收缴率');
                if (!empty($vacancy_list)) {
                    $index = 3;
                    foreach ($vacancy_list as $value) {
                        $objActSheet->setCellValueExplicit('A' . $index, $value['usernum']);
                        $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                        $objActSheet->setCellValueExplicit('C' . $index, $value['housesize']);
                        $objActSheet->setCellValueExplicit('D' . $index, $value['all_fee']);
                        $objActSheet->setCellValueExplicit('E' . $index, $value['total_fee']);
                        $objActSheet->setCellValueExplicit('F' . $index, $value['all_fee'] - $value['total_fee']>0?$value['all_fee'] - $value['total_fee']:0);
                        $objActSheet->setCellValueExplicit('G' . $index, $value['all_fee']>0?round($value['total_fee']/$value['all_fee'],2):0);
                        $index++;
                    }
                    $objActSheet->setCellValueExplicit('A' . $index, '合计');
                    $objActSheet->setCellValueExplicit('B' . $index, '');
                    $objActSheet->setCellValueExplicit('C' . $index,'');
                    $objActSheet->mergeCells('A' . $index . ':C' . $index);
                    $objActSheet->setCellValueExplicit('D' . $index, $he_all_fee);
                    $objActSheet->setCellValueExplicit('E' . $index, $he_total_fee);
                    $objActSheet->setCellValueExplicit('F' . $index, $he_all_fee - $he_total_fee>0?$he_all_fee - $he_total_fee:0);
                    $objActSheet->setCellValueExplicit('G' . $index, $he_all_fee>0?round($he_total_fee/$he_all_fee,2):0);

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
                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
                header("Content-Transfer-Encoding:binary");
                $objWriter->save('php://output');
                exit();
            }
        }
    }

    /**
     * 根据指定年月份获取月初月末的时间戳
     * @param $date
     * @return array
     */
    public function get_ctime($date){
        $month_start =strtotime($date) ;//指定年月份月初时间戳

        $BeginDate=date('Y-m-01', strtotime($date));

        $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));

        $month_end= strtotime("next day", strtotime($EndDate)) - 1;//指定年月份月末时间戳

        return array("firstday"=>$month_start,"lastday"=>$month_end);

    }

    /**
     * 计算出两个日期之间的月份
     * @author Eric
     * @param  [type] $start_date [开始日期，如2014-03]
     * @param  [type] $end_date   [结束日期，如2015-12]
     * @param  string $explode    [年份和月份之间分隔符，此例为 - ]
     * @param  boolean $addOne    [算取完之后最后是否加一月，用于算取时间戳用]
     * @return [type]             [返回是两个月份之间所有月份字符串]
     */
    public function dateMonths($start_date,$end_date,$explode='-',$addOne=false){
        //判断两个时间是不是需要调换顺序
        $start_int = strtotime($start_date);
        $end_int = strtotime($end_date);
        if($start_int > $end_int){
            $tmp = $start_date;
            $start_date = $end_date;
            $end_date = $tmp;
        }


        //结束时间月份+1，如果是13则为新年的一月份
        $start_arr = explode($explode,$start_date);
        $start_year = intval($start_arr[0]);
        $start_month = intval($start_arr[1]);


        $end_arr = explode($explode,$end_date);
        $end_year = intval($end_arr[0]);
        $end_month = intval($end_arr[1]);


        $data = array();
        $data[] = strtotime($start_date);


        $tmp_month = $start_month;
        $tmp_year = $start_year;


        //如果起止不相等，一直循环
        while (!(($tmp_month == $end_month) && ($tmp_year == $end_year))) {
            $tmp_month ++;
            //超过十二月份，到新年的一月份
            if($tmp_month > 12){
                $tmp_month = 1;
                $tmp_year++;
            }
            $data[] = strtotime($tmp_year.$explode.str_pad($tmp_month,2,'0',STR_PAD_LEFT));
        }


        if($addOne == true){
            $tmp_month ++;
            //超过十二月份，到新年的一月份
            if($tmp_month > 12){
                $tmp_month = 1;
                $tmp_year++;
            }
            $data[] = $tmp_year.$explode.str_pad($tmp_month,2,'0',STR_PAD_LEFT);
        }


        return $data;
    }

    /**
     * 计算物业费
     * @author lijie
     * @date_time 2020/07/20 15:48
     * @param $dateMonths
     * @param $chargingStandard
     * @param $housesize
     * @return float|int
     */
    public function property_price($dateMonths,$chargingStandard,$housesize)
    {
        $property_price = 0;
        foreach ($dateMonths as $value){
            foreach ($chargingStandard as $v){
                if($value >= strtotime($v['date_month'])){
                    $price = $v['price'];
                }
            }
            if(isset($price)){
                $property_price += $price*$housesize;
            }
            unset($price);
        }
        return $property_price;
    }

    /**
     * 物业费标准列表
     * @author lijie
     * @date_time 2020/07/22
     */
    public function propertyfee_list()
    {
        $where['village_id'] = $_SESSION['house']['village_id'];
        $list = D('House_village_property_standard')->getLists($where);
        if(!$list){
            $this->error('数据处理有误！');
        }else{
            $this->assign('list',$list['list']);
        }
        //2020/8/24 start 新增
//        $is_open_pay_management = D('House_menu_new')->getPayCostAppIsUsable($this->village_id);
//        if(!$is_open_pay_management){
//            //由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用
//            $this->error('由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用！');
//        }
        //2020/8/24 end 新增
        $village_id = $this->village_id;
        $e_config = D('House_e_config');
        $where['village_id'] = $village_id;
        $data = $e_config->getConfig($where);
        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 删除物业费标准
     * @author lijie
     * @date_time 2020/07/22
     */
    public function propertyfee_del()
    {
        $id = $_GET['id']+0;
        if(!$id){
            $this->error('传递参数有误！');
        }
        $result = M('House_village_property_standard')->where(['id'=>$id])->delete();
        M('House_village_property_standard_bind')->where(['standard_id'=>$id])->save(['is_del'=>2,'delete_time'=>time()]);
        if(!$result){
            $this->error('数据处理有误！');
        }else{
            $this->success('删除成功',U('propertyfee_list'));
        }
    }

    /**
     * 添加物业缴费规则
     * @author lijie
     * @date_time 2020/07/22
     */
    public function propertyfee_add()
    {
        if(IS_POST){
            $arr = $_POST['info'];
            foreach ($arr as $k=>$v){
                if(empty($v['date_month']) || empty($v['price'])){
                    unset($arr[$k]);
                }
            }
            $post_params['name'] = $_POST['name'];
            $post_params['status'] = $_POST['status'];
            $post_params['date_month'] = serialize($arr);
            $post_params['create_time'] = time();
            $database_house_village_property_standard = D('House_village_property_standard');
            $post_params['village_id'] = $_SESSION['house']['village_id'];
            $result = $database_house_village_property_standard->house_village_property_standard_add($post_params);

            if(!$result){
                $this->error('数据处理有误！');
            }else{
                if($result['status']){
                    $this->success($result['msg'],U('propertyfee_list'));
                }else{
                    $this->error($result['msg']);
                }
            }
        }else{
            $this->display();
        }
    }

    public function propertyfee_edit()
    {
        if(!IS_POST){
            $id = $_GET['id'] + 0;
            if(!$id){
                $this->error('传递参数有误！');
            }
        }
        if(IS_POST){
            $arr = $_POST['info'];
            foreach ($arr as $k=>$v){
                if(empty($v['date_month']) || empty($v['price'])){
                    unset($arr[$k]);
                }
            }
            $post_params['name'] = $_POST['name'];
            $post_params['status'] = $_POST['status'];
            $post_params['date_month'] = serialize($arr);
            $post_params['update_time'] = time();
            $database_house_village_property_standard = D('House_village_property_standard');
            $post_params['village_id'] = $_SESSION['house']['village_id'];
            $where['id'] = $_POST['id'];
            $result = $database_house_village_property_standard->house_village_property_standard_edit($where,$post_params);

            if(!$result){
                $this->error('数据处理有误！');
            }else{
                if($result['status']){
                    $this->success($result['msg'],U('propertyfee_list'));
                }else{
                    $this->error($result['msg']);
                }
            }
        }
        $data = M('House_village_property_standard')->where(['id'=>$id])->find();
        $data['date_month'] = unserialize($data['date_month']);
        $this->assign('list',$data);
        $this->display();
    }

    /**
     * 所以物业费标准下的房间列表
     * @author lijie
     * @date_time 2020/07/22
     */
    public function propertyfee_manage()
    {
        $id = $_GET['id'] + 0;
        if(!$id){
            $this->error('传递参数有误！');
        }
        $single_id = !empty($_GET['single_id'])?$_GET['single_id']:0;
        $floor_id = !empty($_GET['floor_id'])?$_GET['floor_id']:0;
        $layer_id = !empty($_GET['layer_id'])?$_GET['layer_id']:0;
        $vacancy_id = !empty($_GET['vacancy_id'])?$_GET['vacancy_id']:0;
        $house_village_property_standard_bind = D('House_village_property_standard_bind');
        $where['sb.standard_id'] = $id;
        $where['sb.is_del'] = 1;
        if($single_id)
            $where['sb.single_id'] = $single_id;
        if($floor_id)
            $where['sb.floor_id'] = $floor_id;
        if($layer_id)
            $where['sb.layer_id'] = $layer_id;
        if($vacancy_id)
            $where['sb.pigcms_id'] = $vacancy_id;
        $field = 'f.floor_name,s.single_name,l.layer_name,v.usernum,v.housesize,sb.pigcms_id,v.room';
        $data = $house_village_property_standard_bind->getLists($where,$field);
        $single_lists = M('House_village_single')->where(['village_id'=>$_SESSION['house']['village_id'],'status' =>1])->field('id,single_name')->select();
        $this->assign('single_list',$single_lists);
        if(!$data){
            $this->error('数据处理有误！');
        }else{
            $this->assign('list',$data['list']);
        }
        $this->display();
    }

    /**
     * 添加房间
     * @author lijie
     * @date_time 2020/07/22
     */
    public function propertyfee_addroom()
    {
        $single_id = !empty($_GET['single_id'])?$_GET['single_id']:0;
        $floor_id = !empty($_GET['floor_id'])?$_GET['floor_id']:0;
        $layer_id = !empty($_GET['layer_id'])?$_GET['layer_id']:0;
        $vacancy_id = !empty($_GET['vacancy_id'])?$_GET['vacancy_id']:0;
        $house_village_user_vacancy = D('House_village_user_vacancy');
        $where = array();
        if($single_id)
            $where['v.single_id'] = $single_id;
        if($floor_id)
            $where['v.floor_id'] = $floor_id;
        if($layer_id)
            $where['v.layer_id'] = $layer_id;
        if($vacancy_id)
            $where['v.pigcms_id'] = $vacancy_id;
        $where['v.is_del'] = 0;
        $where['v.village_id'] = $this->village_id;
        $field = 'f.floor_name,s.single_name,l.layer_name,v.usernum,v.housesize,b.id,v.pigcms_id,b.standard_id,v.room';
        $data = $house_village_user_vacancy->get_all_user_vacancy($where,$field);
        $single_lists = M('House_village_single')->where(['village_id'=>$_SESSION['house']['village_id'],'status'=>1])->field('id,single_name')->select();
        $this->assign('single_list',$single_lists);
        $this->assign('single_id',$single_id);
        $this->assign('floor_id',$floor_id);
        $this->assign('layer_id',$layer_id);
        $this->assign('vacancy_id',$vacancy_id);
        if(!$data){
            $this->error('数据处理有误！');
        }else{
            $this->assign('list',$data['list']);
        }
        $this->display();
    }

    /**
     * 移除房间
     * @author lijie
     * @date_time 2020/07/23
     */
    public function propertyfee_relieve()
    {
        $pigcms_id = $_POST['pigcms_id'] + 0;
        if(!$pigcms_id){
            $this->error('传递参数有误！');
        }
        $house_village_property_standard_bind = D('House_village_property_standard_bind');
        $where['pigcms_id'] = $pigcms_id;
        $data['delete_time'] = time();
        $data['is_del'] = 2;
        $res = $house_village_property_standard_bind->relieve($where,$data);
        if($res)
            echo json_encode(["status"=>1]);
        else
            echo json_encode(["status"=>0]);
    }

    /**
     * 绑定物业费标准
     * @author lijie
     * @date_time 2020/07/23
     */
    public function ajax_bang_propertyfee()
    {
        $standard_id = $_POST['standard_id'];
        $arr_pigcms_id = $_POST['arr_pigcms_id'];
        if(!$standard_id || empty($arr_pigcms_id)){
            $this->error('传递参数有误！');
        }
        $arr_pigcms_id = explode(',',$arr_pigcms_id);
        foreach ($arr_pigcms_id as $k=>$v){
            $info = M('House_village_user_vacancy')->where(['pigcms_id'=>$v])->field('single_id,floor_id,layer_id')->find();
            $data['pigcms_id'] = $v;
            $data['standard_id'] = $standard_id;
            $data['single_id'] = $info['single_id'];
            $data['floor_id'] = $info['floor_id'];
            $data['layer_id'] = $info['layer_id'];
            $data['create_time'] = time();
            M('House_village_property_standard_bind')->add($data);
        }
        echo json_encode(["status"=>1]);
    }

    public function e_invoice_management()
    {
        $village_id = $this->village_id;
        $e_config = D('House_e_config');
        $where['village_id'] = $village_id;
        $data = $e_config->getConfig($where);
        $this->assign('data',$data);
        $this->display();
    }
    public function e_edit()
    {
        $village_id = $this->village_id;
        if(IS_POST){
            $e_config = D('House_e_config');
            $postParams = array();
            if(empty($_POST['id'])){
                $postParams['village_id'] = $village_id;
                $postParams['is_open'] = $_POST['is_open'];
                $postParams['is_tui'] = $_POST['is_tui'];
                $postParams['seller_name'] = $_POST['seller_name'];
                $postParams['duty_paragraph'] = $_POST['duty_paragraph'];
                $postParams['seller_address'] = $_POST['seller_address'];
                $postParams['seller_phone'] = $_POST['seller_phone'];
                $postParams['bank_of_deposit'] = $_POST['bank_of_deposit'];
                $postParams['account_number'] = $_POST['account_number'];
                $postParams['create_time'] = time();
                $result = $e_config->addConfig($postParams);
            }else{
                $postParams['village_id'] = $village_id;
                $postParams['is_open'] = $_POST['is_open'];
                $postParams['is_tui'] = $_POST['is_tui'];
                $postParams['seller_name'] = $_POST['seller_name'];
                $postParams['duty_paragraph'] = $_POST['duty_paragraph'];
                $postParams['seller_address'] = $_POST['seller_address'];
                $postParams['seller_phone'] = $_POST['seller_phone'];
                $postParams['bank_of_deposit'] = $_POST['bank_of_deposit'];
                $postParams['account_number'] = $_POST['account_number'];
                $postParams['update_time'] = time();
                $where['id'] = $_POST['id'];
                $result = $e_config->editConfig($where,$postParams);
            }
            if(!$result){
                $this->error('数据处理有误！');
            }else{
                if($result['status']){
                    $this->success($result['msg'],U('e_invoice_management'));
                }else{
                    $this->error($result['msg']);
                }
            }
        }
    }
    public function position_protocol_set(){

        $house_village_position_setDb=D('House_village_position_set');
        if(IS_POST && isset($_POST['robbuy_protocol_title'])){
            $whereArr=array('village_id'=>$this->village_id);
            $saveArr=array();
            $saveArr['robbuy_protocol_title']=trim($_POST['robbuy_protocol_title']);
            $saveArr['is_protocol_open']=trim($_POST['is_protocol_open']);
            $saveArr['is_protocol_open']=intval($saveArr['is_protocol_open']);
            if(empty($saveArr['robbuy_protocol_title'])){
                $this->error('协议标题不能为空！');
            }
            $saveArr['robbuy_protocol_title']=$saveArr['robbuy_protocol_title'];
            $saveArr['robbuy_protocol']=trim($_POST['robbuy_protocol']);
            if(empty($saveArr['robbuy_protocol'])){
                $this->error('协议内容不能为空！');
            }
            $saveArr['robbuy_protocol']=$saveArr['robbuy_protocol'];
            $protocol=$house_village_position_setDb->where($whereArr)->find();
            if(!empty($protocol)){
                $house_village_position_setDb->where($whereArr)->data($saveArr)->save();
                $this->success('保存成功！');
            }else{
                $saveArr['village_id']=$this->village_id;
                $saveArr['update_time']=time();
                $house_village_position_setDb->data($saveArr)->add();
                $this->success('保存成功！');
            }
        }else{
            $whereArr=array('village_id'=>$this->village_id);
            $protocol=$house_village_position_setDb->where($whereArr)->field('id,robbuy_protocol_title,is_protocol_open,robbuy_protocol')->find();
            if(empty($protocol)){
                $protocol=array();
            }else{
                $protocol['robbuy_protocol']=htmlspecialchars_decode($protocol['robbuy_protocol'],ENT_QUOTES);
            }
            $this->assign('protocol',$protocol);
            $this->display();
        }
    }
}