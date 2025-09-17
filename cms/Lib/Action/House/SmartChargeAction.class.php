<?php
/*
 * 广告管理
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/06 16:47
 *
 */
class SmartChargeAction extends BaseAction{

	public function index(){
		$database_house_smart_charging  = D('House_smart_charging');
        $name = isset($_GET['name'])?$_GET['name']:'';
        // 打开方式 0 链接(使用-2进行查询) 1 小程序
        $open_type  = isset($_GET['open_type'])?$_GET['open_type']:'-1';
		$where = [];
		$where['village_id'] = $this->house_session['village_id'];
		if($name){
			$where['name'] = array('like','%'.$name.'%');
		}
		if($open_type){
            if (-2==$open_type) {
                $open_type = 0;
            } elseif (-1!=$open_type) {
                $where['open_type'] = $open_type;
            }
		}
        $list = $database_house_smart_charging->field(true)->where($where)->order('`id` ASC')->select();
        if ($list) {
            $site_url = $this->config['site_url'];
            $look = $site_url . '/shequ.php?c=SmartCharge&a=get_own_qrcode&id=';
            foreach($list as &$val) {
                $val['look_url'] = $look .$val['id'];
            }
        }
		$this->assign('list',$list);
		$this->display();
    }

    public $font_url = './static/fonts/apple_lihei_bold.otf';
	
	public function get_own_qrcode(){
        header('Content-Type: image/png');
        ob_clean();
		$id = $_GET['id'];
		$size = $_GET['size'] ? $_GET['size']: 10;
        $site_url = $this->config['site_url'];
        $qrcode_url =  $site_url. '/wap.php?c=HouseSmart&a=index&id='.$id;
        if(!file_exists('./runtime/smartCharge')){
            mkdir('./runtime/smartCharge',0777,true);
        }
        $path1 = '/runtime/smartCharge/qrcode_smart_charge_'.$id.'.png';
        $qrcode_filePath = $_SERVER['DOCUMENT_ROOT'] . $path1;
        ob_clean();
        import('@.ORG.phpqrcode');
        QRcode::png($qrcode_url,$qrcode_filePath ,0,$size,1,true);
        // // 获取信息
        $database_house_smart_charging  = D('House_smart_charging');
        $info = $database_house_smart_charging->where(['id'=>$id])->find();

        $backgroundHeight = 450;
        $backgroundWidth = 400;
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 255, 255, 255);
        imagefill($imageRes, 0, 0, $color);

	     $val = array(
             'left'=>100,
             'top'=>50,
             'right'=>0,
             'bottom'=>0,
             'width'=>355,
             'height'=>355,
             'opacity'=>100
         );
        $name_bbox = imagettfbbox(14, 0, $this->font_url, $info['name']);
        $name_text_width = $name_bbox[2];
        $left_name = ($backgroundWidth-$name_text_width)/2;

        $id_bbox = imagettfbbox(14, 0, $this->font_url, $info['id']);
        $id_text_width = $id_bbox[2];
        $left_id = ($backgroundWidth-$id_text_width)/2;
        if ($info['name']) {
            $text = array (
                'name' => array (
                    'text' => $info['name'],
                    'left'=>$left_name,
                    'top'=>$backgroundHeight - 50,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>14,
                    'fontColor'=>'0,0,0',
                ),
                'id' => array (
                    'text' => $info['id'],
                    'left'=>$left_id,
                    'top'=>$backgroundHeight - 20,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>14,
                    'fontColor'=>'0,0,0',
                )
            );
        } else {
            $text = array (
                'id' => array (
                    'text' => $info['id'],
                    'left'=>$left_id,
                    'top'=>$backgroundHeight - 40,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>14,
                    'fontColor'=>'0,0,0',
                )
            );
        }
         //填充背景
         imagefill($imageRes,0,0, $color );//填充背景
         $info = getimagesize($qrcode_filePath);
		
         $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
         $res = $function($qrcode_filePath);
		
		
         $resWidth = $info[0];
         $resHeight = $info[1];
        
         $val['height'] = 355;
         $val['width'] = 355;
         $val['bottom'] = 0;
         $val['top'] = $backgroundHeight - 525;
         $val['left'] = 50;

         $canvas = imagecreatetruecolor($val['width'], $val['height']);
         $c = imagecolorallocate($canvas, 255, 255, 255);
         $color = imagecolortransparent($canvas,$c);

         imagefill($canvas, 0, 0, $color);

         //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
         imagecopyresampled($canvas, $res, 0, 0, 0, 0,$val['width'], $val['height'],$resWidth,$resHeight);
         $val['left'] = ($backgroundWidth-$val['width'])/2;
         $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
         //放置图像
		
         imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度

         foreach ($text as $val) {
             $fontWidth = intval($val['fontSize']);//获取文字宽度
             $val['fontPath'] =$this->font_url;
             list($R,$G,$B) = explode(',', $val['fontColor']);
             $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
             $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
             $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];

             imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top'],$fontColor,$val['fontPath'],$val['text']);
         }
        
         if(!file_exists('./runtime/smartCharge')){
             mkdir('./runtime/smartCharge',0777,true);
         }
         $path = '/runtime/smartCharge/qrcode_smart_charge_new_'.$id.'.png';
         $filename = '.'.$path;
         $res = imagejpeg ($imageRes,$filename,90); //保存到本地
//         imagedestroy($imageRes);
//         imagejpeg ($imageRes);     //在浏览器上显示
//         imagedestroy($imageRes);
        $look_img = $site_url . $path . '?time='.time();
        exit(json_encode(array('error'=>'0','look_img'=>$look_img)));
	}

    
    public function smart_add()
    {
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list', $wxapp_list);
        $id = I('id','','intval');
        if ($id) {
            $database_house_smart_charging  = D('House_smart_charging');
            $info = $database_house_smart_charging->where(['id'=>$id])->find();
            if(!$info) {
                $this->error('编辑对象不存在！');
            }
            $this->assign('info', $info);
        }
        $this->display();
    }

    public function smart_modify() {
        $id = I('id','','intval');
        $name = I('name','','trim');
        $open_type = I('open_type','','intval');
        $open_url = I('open_url','','trim');
        $open_url = htmlspecialchars_decode($open_url);
        if (1==$open_type) {
            $wxapp_id = I('wxapp_id','','trim');
            $wxapp_info = M('Wxapp_other')->where(['wxapp_id'=> $wxapp_id])->find();
            if (!$wxapp_info) {
                $this->error('该小程序不存在！');
            }
        } elseif(0==$open_type && !$open_url) {
            $this->error('请填写链接！');
        }
        $wxapp_page = I('wxapp_page','','trim');
        $database_house_smart_charging  = D('House_smart_charging');
        $data = [
            'name' => $name?$name:'',
            'open_type' => $open_type,
            'open_url' => $open_url,
            'wxapp_id' => $wxapp_id ? $wxapp_id : '',
            'wxapp_page' => $wxapp_page,
			'village_id' => $this->house_session['village_id'],
        ];
        if ($id) {
            $info = $database_house_smart_charging->where(['id'=>$id])->find();
            if(!$info) {
                $this->error('编辑对象不存在！');
            }
            $data['last_time'] = time();
            $set = $database_house_smart_charging->where(['id'=>$id])->data($data)->save();
            if ($set) {
                $this->success('编辑成功',U('index'));
            } else {
                $this->error('编辑失败！');
            }
        } else {
            $data['add_time'] = time();
            $id = $database_house_smart_charging->data($data)->add();
            if ($id) {
                $this->success('添加成功',U('index'));
            } else {
                $this->error('添加成功失败！');
            }
        }
    }

    public function import_smart_charge_add() {
        
        if(IS_POST){
            $open_type = I('open_type');
            if (!$open_type) {
                $open_type = 0;
            }
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);
                import('ORG.Util.Dir');
				Dir::delDirnotself('./upload/excel/villagesmart');
				
                $upload_dir = './upload/excel/villagesmart/' . date('Ymd') . '/';
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
                    $d_wxapp_other = M('Wxapp_other');
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
					unlink($path);	//删除文件
                    $error_arr = array();
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
						if(count($result) == 0){
							$this->assign('waitSecond',10);
							$this->error('表格未成功识别。请使用 excel软件 将表格更换为 xlsx格式 重试一下');
                        }
                        $database_house_smart_charging  = D('House_smart_charging');
                        foreach ($result as $kk => $vv) {
                            if (count($vv) == 0) {
                                continue;
                            }

                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null) continue;
                            
                            if (0==$open_type) {
                                if ($vv['C']) {
                                    $vv['D'] = '请检查是否上传错了文件，当前似乎上传的为小程序跳转模板';
                                }
                                $error_arr[] = $vv;
                                continue;
                            }

                            if (empty($vv['B'])) {
                                if (0==$open_type) {
                                    $vv['D'] = '请填写跳转链接！';
                                } else {
                                    $vv['D'] = '请填写或者选择小程序！';
                                }
                                $error_arr[] = $vv;
                                continue;
                            }
                            $a_str = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            $b_str = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            if (1==$open_type) {
                                $wxapp_other = $d_wxapp_other->where(['name' => $b_str])->find();
                                if(!$wxapp_other){
                                    $vv['D'] = '小程序不存在！';
                                    $error_arr[] = $vv;
                                    continue;
                                }
                                $open_url = '';
                                $wxapp_id = $wxapp_other['appid'];
                                $wxapp_page = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            } else {
                                $open_url = $b_str;
                                $wxapp_id = '';
                                $wxapp_page = '';
                            }
                            $data = [
                                'name' => $a_str?$a_str:'',
                                'open_type' => $open_type,
                                'open_url' => $open_url,
                                'wxapp_id' => $wxapp_id,
                                'wxapp_page' => $wxapp_page,
								'village_id' => $this->house_session['village_id'],
                            ];
                            $data['add_time'] = time();
                            $id = $database_house_smart_charging->data($data)->add();
                            if (!$id) {
                                if (0==$open_type) {
                                    $vv['D'] = '保存失败！';
                                } else {
                                    $vv['D'] = '保存失败！';
                                }
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
                      
                            if (0==$open_type) {
                                $title = '跳转链接导入失败列表';
                            } else {
                                $title = '打开小程序导入失败列表';
                            }
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
                            $objExcel->getActiveSheet()->setTitle('智能充电桩列表');
                            $objActSheet = $objExcel->getActiveSheet();
                            
                            if (0==$open_type) {
                                $objActSheet->setCellValue('A1', '二维码名称（选填）');
                                $objActSheet->setCellValue('B1', '跳转链接（填写链接已链接跳转为准）');
                                $objActSheet->setCellValue('D1', '失败原因');
                            } else {
                                $objActSheet->setCellValue('A1', '二维码名称（选填）');
                                $objActSheet->setCellValue('B1', '跳转小程序名称(需选中该列第二行右下角向下复制，可下拉选择单元类型)');
                                $objActSheet->setCellValue('C1', '小程序页面路径');
                                $objActSheet->setCellValue('D1', '失败原因');
                            }

                            $index = 2;
                            foreach ($error_arr as  $value) {  
                                if (0==$open_type) {
                                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                } else {
                                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                }
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
        } else {
            $this->display();
        }
    }

    
    public function smart_charge_import_sample()
    {

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $open_type = I('open_type');
        if (!$open_type) {
            $open_type = 0;
        }
        if (0==$open_type) {
            $list[] = array('智能充电',$this->config['site_url']);
            $title = '跳转链接示例模板';
        } else {
            $list[] = array('智能充电','','');
            $title = '打开小程序示例模板';
        }

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = 1;

        $wxapp_list = M('Wxapp_other')->field(true)->select();
        if($wxapp_list){
            $wxapp_data = [];
            foreach($wxapp_list as $k=>$v){
                $wxapp_data[] = $v['name'];
            }
            $wxapp_data_str = implode(',', $wxapp_data);
        }else{
            $wxapp_data_str = '';
        }
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('导入智能充电桩二维码');
            $objActSheet = $objExcel->getActiveSheet();
            if (0==$open_type) {
                $objActSheet->setCellValue('A1', '二维码名称（选填）');
                $objActSheet->setCellValue('B1', '跳转链接（填写链接已链接跳转为准）');
            } else {
                $objActSheet->setCellValue('A1', '二维码名称（选填）');
                $objActSheet->setCellValue('B1', '跳转小程序名称(需选中该列第二行右下角向下复制，可下拉选择单元类型)');
                $objActSheet->setCellValue('C1', '小程序页面路径');
            }

            if (!empty($list)) {
                $index = 2;
                $cell_list = range('A','L');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($list as $value) {

                    if (0==$open_type) {
                        $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                        $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                    } else {
                        $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                        $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                        $objExcel->setActiveSheetIndex(0);
                        $objActSheet = $objExcel->getActiveSheet();
                        $objValidation = $objActSheet->getCell("B2")->getDataValidation(); //这一句为要设置数据有效性的单元格
    
                        $objValidation -> setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                            -> setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                            -> setAllowBlank(false)
                            -> setShowInputMessage(true)
                            -> setShowErrorMessage(true)
                            -> setShowDropDown(true)
                            -> setErrorTitle('输入的值有误')
                            -> setError('您输入的值不在下拉框列表内.')
                            -> setPromptTitle('跳转小程序名称')
                            -> setFormula1('"'.$wxapp_data_str.'"');
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
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }
}