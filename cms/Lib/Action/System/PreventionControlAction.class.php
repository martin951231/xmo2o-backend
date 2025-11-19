<?php
/*
 * 城市区域管理
 *
 */

class PreventionControlAction extends BaseAction{
	public $font_url = './static/fonts/apple_lihei_bold.otf';
    public $type_info = array(
        '0' => '出租车',
        '2' => '客运车',
		'3' => '餐饮',
        '1' => '其他',
    );


	public function index(){
        $database_prevention_control_info = D('Prevention_control_info');
        $type = isset($_GET['type']) ? intval($_GET['type']) : -1;
        $name = isset($_GET['name']) ? trim($_GET['name']) : '';
        $phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
        $where = array();
        if ($type != -1) {
			$where['type'] = $type;
        }
        if ($name) {
			$where['name'] = array('like', "%".$name."%");
        }
        if ($phone) {
			$where['phone'] = array('like', "%".$phone."%");
        }

        $system_id = $this->system_session['id'];
        $system_level = $this->system_session['level'];
        if(0==$system_level) {
            // 普通账号
            $where['bind_admin'] = $system_id;
            $count_level = $database_prevention_control_info->where($where)->count();
            if (empty($count_level)) {
                unset($where['bind_admin']);
            }
        } elseif(2!=$system_level) {
            // 非超级账号
            $where['bind_admin'] = $system_id;
        }


        $count = $database_prevention_control_info->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count,25);
        $list = $database_prevention_control_info->field(true)->where($where)->order('`id` DESC')->limit($p->firstRow.','.$p->listRows)->select();

      	if($list) {
  				
  			foreach ($list as &$value) {
	            $qrcode_url = urlencode($this->config['site_url'] . '/wap.php?c=PreventionControl&a=add&control_id='.$value['id']);
	            $img_url = $this->config['site_url'].U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($qrcode_url)),'size'=>$_GET['size'],'down'=>$_GET['down']));
	            $value['qrcode'] = $img_url;
				
				$value['wxapp_qrcode'] = $this->config['site_url'].'/index.php?c=Recognition_wxapp&a=create_url_qrcode&webview_url=' . $qrcode_url . '&webview_title=' . urlencode($value['name']);
				
	            if ($value['bind_admin']) {
	            	$value['bind_admin_name'] = M('Admin')->where(array('id' => $value['bind_admin']))->getField('realname');
	            }
  			}
      	}
        $this->assign('list',$list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);

        $type_info = $this->type_info;
        $this->assign('type_info',$type_info);

		$this->display();
	}

    public function get_own_qrcode(){
        header('Content-Type: image/png');
        ob_clean();
        $qrCon = $_GET['qrCon'];
        import('@.ORG.phpqrcode');
        $size = $_GET['size'] ? $_GET['size']: 10;
        $is_down = $_GET['down'] ? true: false;

        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1,$is_down);
    }


	public function add(){
        $where['level'] = array('neq', '2');
        $database_prevention_control_info = D('Prevention_control_info');
        $system_id = $this->system_session['id'];
        $system_level = $this->system_session['level'];
        if(2!=$system_level) {
            // 普通账号
            $where_info['bind_admin'] = $system_id;
            $count_level = $database_prevention_control_info->where($where_info)->count();
            if (!empty($count_level)) {
                $where['id'] = $system_id;
            }
        }

        $admin_log = M('Admin')->where($where)->select();

        $this->assign('admin_log',$admin_log);
        $type_info = $this->type_info;
        $this->assign('type_info',$type_info);

		$this->assign('bg_color','#F3F3F3');


		$this->display();
	}
	public function modify(){
		if(IS_POST){
            $image = D('Image')->handle($this->system_session['id'], 'system', 0, array('size' => 10));
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
            }

        	$database_prevention_control_info = D('Prevention_control_info');
        	$_POST['add_time'] = time();
            if ($_POST['pay_img']) {
                $pay_img_txt = replace_file_domain('/upload/system/'.$_POST['pay_img']);
                import('@.ORG.phpqrcode_read.qrRead');
                $qrread = new qrRead();  //图片路径
                $text = $qrread->read_txt($pay_img_txt); //返回识别后的文本
                if (!$text) {
                    $this->frame_submit_tips(0,'上传的二维码图片无法识别，或者不是二维码图片~');
                }
                $_POST['pay_url'] = $text;
            }

			if($database_prevention_control_info->data($_POST)->add()){
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}else{
			$this->frame_submit_tips(0,'非法提交,请重新提交~');
		}
	}

    public function edit(){
        $id = I('id');
        $database_prevention_control_info = D('Prevention_control_info');
        $info = $database_prevention_control_info->where(array('id'=>$id))->find();
        if ($info['pay_img']) {
            $info['pay_img_txt'] = replace_file_domain('/upload/system/'.$info['pay_img']);
        }
        $this->assign('info',$info);

        $where['level'] = array('neq', '2');
        $database_prevention_control_info = D('Prevention_control_info');
        $system_id = $this->system_session['id'];
        $system_level = $this->system_session['level'];
        if(2!=$system_level) {
            // 普通账号
            $where_info['bind_admin'] = $system_id;
            $count_level = $database_prevention_control_info->where($where_info)->count();
            if (!empty($count_level)) {
                $where['id'] = $system_id;
            }
        }
        $admin_log = M('Admin')->where($where)->select();

        $this->assign('admin_log',$admin_log);
        $type_info = $this->type_info;
        $this->assign('type_info',$type_info);

        $this->assign('bg_color','#F3F3F3');
        $this->display();
    }
    public function edit_modify(){
        if(IS_POST){
        	$id = I('id');
        	unset($_POST['pay_img']);
            $image = D('Image')->handle($this->system_session['id'], 'system', 0, array('size' => 10));
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/system/', '', $image['url']));
            }
            $database_prevention_control_info = D('Prevention_control_info');
            $_POST['last_time'] = time();
            if ($_POST['pay_img']) {
                $pay_img_txt = replace_file_domain('/upload/system/'.$_POST['pay_img']);
                import('@.ORG.phpqrcode_read.qrRead');
                $qrread = new qrRead();  //图片路径
                $text = $qrread->read_txt($pay_img_txt); //返回识别后的文本
                if (!$text) {
                    $this->frame_submit_tips(0,'上传的二维码图片无法识别，或者不是二维码图片~');
                }
                $_POST['pay_url'] = $text;
            }
            if($database_prevention_control_info->where(array('id'=> $id))->data($_POST)->save()){
                $this->frame_submit_tips(1,'编辑成功！');
            }else{
                $this->frame_submit_tips(0,'编辑失败！请重试~');
            }
        }else{
            $this->frame_submit_tips(0,'非法提交,请重新提交~');
        }
    }
		
	public function user_list(){
        $database_prevention_control_user = D('Prevention_control_user');
        $phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
        if ($phone) {
			$where['u.phone'] = $phone;
        }
        $count = $database_prevention_control_user->join('as u inner join '.C('DB_PREFIX').'prevention_control_info i ON i.id=u.control_id')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count,25);
        $list = $database_prevention_control_user->field('`u`.*,`i`.`name` as info_name,`i`.`phone` as info_phone,`i`.`type` as type')->join('as u inner join '.C('DB_PREFIX').'prevention_control_info i ON i.id=u.control_id')->where($where)->order('`u`.`id` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		
        $this->assign('list',$list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
        $this->assign('count',$count);
		
		$where['u.add_time'] = ['egt', strtotime(date('Y-m-d'))];
		$today_count = $database_prevention_control_user->join('as u inner join '.C('DB_PREFIX').'prevention_control_info i ON i.id=u.control_id')->where($where)->count();

        $this->assign('today_count',$today_count);
		
        $this->assign('type_info',$this->type_info);
		$this->display();
	}

	public function user_index(){
    	$id = I('id');
        $database_prevention_control_info = D('Prevention_control_info');
        $info = $database_prevention_control_info->where(array('id'=>$id))->find();
        $this->assign('info',$info);

        $database_prevention_control_user = D('Prevention_control_user');
        $name = isset($_GET['name']) ? trim($_GET['name']) : '';
        $phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
        $where = array('control_id' => $id);
        if ($name) {
			$where['name'] = array('like', "%".$name."%");
        }
        if ($phone) {
			$where['phone'] = array('like', "%".$phone."%");
        }
        $count = $database_prevention_control_user->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count,25);
        $list = $database_prevention_control_user->field(true)->where($where)->order('`id` DESC')->limit($p->firstRow.','.$p->listRows)->select();

        $this->assign('list',$list);
        $pagebar = $p->show();
        $this->assign('pagebar',$pagebar);
		
		$this->display();
	}


    // 导出填写信息
    public function user_export(){

    	$id = I('id');
        $database_prevention_control_info = D('Prevention_control_info');
        $info = $database_prevention_control_info->where(array('id'=>$id))->find();

        $database_prevention_control_user = D('Prevention_control_user');
        $name = isset($_GET['name']) ? intval($_GET['name']) : '';
        $phone = isset($_GET['phone']) ? intval($_GET['phone']) : '';
        $where = array('control_id' => $id);

        if ($name) {
			$where['name'] = array('like', "%".$name."%");
        }
        if ($phone) {
			$where['phone'] = array('like', "%".$phone."%");
        }

        $count = $database_prevention_control_user->where($where)->count();

        if($count <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = '【'. $info['name'] . '】二维码填写记录';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil($count/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . $count . '个记录');
            $objActSheet = $objExcel->getActiveSheet();

            if($info['type'] == 0 || $info['type'] == 2){
				$objActSheet->setCellValue('A1', '记录编号');
				$objActSheet->setCellValue('B1', '姓名');
				$objActSheet->setCellValue('C1', '联系电话');
				$objActSheet->setCellValue('D1', '上车地点');
				$objActSheet->setCellValue('E1', '下车地点');
				$objActSheet->setCellValue('F1', '备注信息');
				$objActSheet->setCellValue('G1', '上车时间');
				$objActSheet->setCellValue('H1', '下车时间');
				$list = $database_prevention_control_user->field(true)->where($where)->order('`id` DESC')->limit($i * 1000 . ',1000')->select();
				if (!empty($list)) {
					$index = 2;

					$cell_list = range('A','I');
					foreach ($cell_list as $cell) {
						$objActSheet->getColumnDimension($cell)->setWidth(20);
					}

					foreach ($list as $value) {
						$objActSheet->setCellValueExplicit('A' . $index, $value['id']);
						$objActSheet->setCellValueExplicit('B' . $index, $value['name']);
						$objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
						$objActSheet->setCellValueExplicit('D' . $index, $value['start_address']);
						$objActSheet->setCellValueExplicit('E' . $index, $value['end_address']);
						$objActSheet->setCellValueExplicit('F' . $index, $value['remark']);
						$add_time = $value['add_time'] ? date("Y-m-d h:i:s", $value['add_time']):'';
						$objActSheet->setCellValueExplicit('G' . $index, $add_time);
						$last_time = $value['last_time'] ? date("Y-m-d h:i:s", $value['last_time']):'';
						$objActSheet->setCellValueExplicit('H' . $index, $last_time);
						$index++;
					}
				}
            }else{
				$objActSheet->setCellValue('A1', '记录编号');
				$objActSheet->setCellValue('B1', '姓名');
				$objActSheet->setCellValue('C1', '联系电话');
				$objActSheet->setCellValue('D1', '备注信息');
				$objActSheet->setCellValue('E1', '填写时间');
				$list = $database_prevention_control_user->field(true)->where($where)->order('`id` DESC')->limit($i * 1000 . ',1000')->select();
				if (!empty($list)) {
					$index = 2;

					$cell_list = range('A','I');
					foreach ($cell_list as $cell) {
						$objActSheet->getColumnDimension($cell)->setWidth(20);
					}

					foreach ($list as $value) {
						$objActSheet->setCellValueExplicit('A' . $index, $value['id']);
						$objActSheet->setCellValueExplicit('B' . $index, $value['name']);
						$objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
						$objActSheet->setCellValueExplicit('D' . $index, $value['remark']);
						$add_time = $value['add_time'] ? date("Y-m-d h:i:s", $value['add_time']) : '';
						$objActSheet->setCellValueExplicit('E' . $index, $add_time);
						$index++;
					}
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
	
	
	// 下载海报图片
	public function down_img() {
        $control_id = I('id');
		$txt_type = I('txt_type','0'); // 0 文字版 1 简洁版
        $database_prevention_control_info = D('Prevention_control_info');
        $info = $database_prevention_control_info->where(array('id'=>$control_id))->find();
		$name = $info['name'];
		$phone = $info['phone'];
		// 生成二维码
		$qrcode_url = $this->config['site_url'] . '/wap.php?c=PreventionControl&a=add&control_id='.$control_id;
		if($this->config['pay_wxapp_important']==1) {
            $wxapp_path = D('House_village')->home_url . '?redirect=webview&webview_url='.urlencode('/wap.php?c=PreventionControl&a=add&control_id='.$control_id);
			$qrcode = $this->get_wxapp_qrcode($wxapp_path,300);
			if(strpos($qrcode,'{"') === 0){
				$qrcodeArr = json_decode($qrcode,true);
				if($qrcodeArr && $qrcodeArr['errcode']){
					die('小程序二维码生成失败。'.var_export($qrcodeArr, true));
				}
			}
			if(!file_exists('./runtime/controlQrcode')){
				mkdir('./runtime/controlQrcode',0777,true);
			}
			$qrcode_filePath = './runtime/controlQrcode/'.uniqid().'.png';
			file_put_contents($qrcode_filePath,$qrcode);
		} else {
			header("Content-Type: text/html;charset=utf-8"); 
			if(!file_exists('./runtime/controlQrcode')){
				mkdir('./runtime/controlQrcode',0777,true);
			}
			$qrcode_filePath = './runtime/controlQrcode/'.uniqid().'.png';
			ob_clean();
			import('@.ORG.phpqrcode');
			QRcode::png($qrcode_url,$qrcode_filePath ,0,10,1,true);
		}
		// 对应处理
		if (0==$txt_type) {
			$background = './static/prevention-control/img/img1.png';//海报最底层得背景
		} else {
			$background = './static/prevention-control/img/img2.png';//海报最底层得背景
		}
		if ($info['bind_admin'] && $info['bind_admin']>0) {
			$where['id'] = $info['bind_admin'];
			$admin_log = M('Admin')->where($where)->find();
			if ($admin_log && $admin_log['realname']) {
				$company = $admin_log['realname'];
			}
			if ($admin_log && $admin_log['des']) {
				$desc = $admin_log['des'];
				$desc = mb_substr($desc, 0, 8);
			}
		}
		
        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 255, 255, 255);
        imagefill($imageRes, 0, 0, $color);
        // imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));

	    $val = array(
            'left'=>100,
            'top'=>50,
            'right'=>0,
            'bottom'=>0,
            'width'=>355,
            'height'=>355,
            'opacity'=>100
        );
		
		
		
		// 对应处理
		if (0==$txt_type) {
			$bbox = imagettfbbox(26, 0, $this->font_url, $desc);
			$textWidth = $bbox[2] + 50;
			$text = array (
				'company' => array (
					'text' => $company,
					'left'=>50,
					'top'=>$backgroundHeight - 78,
					'right'=>0,
					'bottom'=>0,
					'fontSize'=>16,
					'fontColor'=>'255,255,255',
				),
				'phone' => array (
					'text' => $name . ' ' . $phone,
					'left'=>50,
					'top'=>$backgroundHeight - 50,
					'right'=>0,
					'bottom'=>0,
					'fontSize'=>16,
					'fontColor'=>'255,255,255',
				),
				'desc' => array (
					'text' => $desc,
					'left'=>$backgroundWidth-$textWidth,
					'top'=>$backgroundHeight - 80,
					'right'=>10,
					'bottom'=>0,
					'fontSize'=>26,
					'fontColor'=>'255,255,255',
				),
			);
		} else {
			$bbox = imagettfbbox(26, 0, $this->font_url, $desc);
			$textWidth = $bbox[2] + 50;
			$text = array (
				'company' => array (
					'text' => $company,
					'left'=>50,
					'top'=>$backgroundHeight - 78,
					'right'=>0,
					'bottom'=>0,
					'fontSize'=>16,
					'fontColor'=>'255,255,255',
				),
				'phone' => array (
					'text' => $name . ' ' . $phone,
					'left'=>50,
					'top'=>$backgroundHeight - 50,
					'right'=>0,
					'bottom'=>0,
					'fontSize'=>16,
					'fontColor'=>'255,255,255',
				),
				'desc' => array (
					'text' => $desc,
					'left'=>$backgroundWidth-$textWidth,
					'top'=>$backgroundHeight - 80,
					'right'=>10,
					'bottom'=>0,
					'fontSize'=>26,
					'fontColor'=>'255,255,255',
				),
			);

		}
		
        //填充背景
        imagefill($imageRes,0,0, $color );//填充背景
        $info = getimagesize($qrcode_filePath);
		
        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $res = $function($qrcode_filePath);
		
		
        $resWidth = $info[0];
        $resHeight = $info[1];

        // 对应处理
		if (0==$txt_type) {
           $val['height'] = 355;
           $val['width'] = 355;
           $val['bottom'] = 0;
           $val['top'] = $backgroundHeight - 500;
           $val['left'] = 50;
        } else {
           $val['height'] = 445;
           $val['width'] = 445;
           $val['bottom'] = 0;
           $val['top'] = $backgroundHeight - 950;
           $val['left'] = 50;
		}

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

        if(!file_exists('./runtime/controlShareImg')){
            mkdir('./runtime/controlShareImg',0777,true);
        }
        $filename = './runtime/controlShareImg/preventioncontrol_'.$control_id.'.jpg';

        if(!empty($filename)){
            $res = imagejpeg ($imageRes,$filename,90); //保存到本地
            imagedestroy($imageRes);
            if(!$res) return false;
			$filename = str_replace('./','',$filename);
		    $file_path = $_SERVER['DOCUMENT_ROOT'].'/'.$filename;
		    //获取下载文件的大小
		    $file_size = filesize($file_path);
		    $file_temp = fopen ( $file_path, "r" );
		    //返回的文件
		    header("Content-type:application/octet-stream");
		    //按照字节大小返回
		    header("Accept-Ranges:bytes");
		    //返回文件大小
		    header("Accept-Length:".$file_size);
		    //这里客户端的弹出对话框
		    header("Content-Disposition:attachment;filename=".$name.'_'.time().'.png');

		    echo fread ( $file_temp, filesize ( $file_path ) );
		    fclose ( $file_temp );
		    exit ();
        }else{
            imagejpeg ($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }

	}

    protected function get_wxapp_qrcode($qr_path,$width){
		$access_token_array = D('Access_token_wxapp_expires')->get_access_token();
		if ($access_token_array['errcode']) {
			exit('获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
		}
        $wxapp_access_token = $access_token_array['access_token'];

		$qrcode_url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$access_token_array['access_token'];
		$post_data = array(
            'is_hyaline'=>false,
			'path'=>$qr_path,
			'width'=>$width
		);
		$qrcode = $this->curlPost($qrcode_url,json_encode($post_data));

        return $qrcode;
    }
	
	static public function curlPost($url,$data,$timeout=15){
		$ch = curl_init();
		$headers[] = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
		$result = curl_exec($ch);
		
		//关闭curl
		curl_close($ch);
		return $result;	
	}
}
