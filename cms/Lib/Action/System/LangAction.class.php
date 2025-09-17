<?php
/**
 * 多语言管理
 */
class LangAction extends BaseAction{

	

	/**
	 * 语言列表
	 * @return [type] [description]
	 */
	public function index(){
		$keyword = I("keyword");
		$from = I("from");
		$database = D('Lang');
		$where = array(
			'is_del' => 0
		);
		if($keyword){
			$where['key'] = array('like', "%".$keyword."%");
			$this->assign("keyword", $keyword);
		}
		if(($from || $from === '0') && $from != -1){
			$where['from'] = array('eq', $from);
			$this->assign("from", $from);
		}
		$count = $database->where($where)->count();

		import('@.ORG.system_page');
		$p = new Page($count,100);
		$data = $database->field(true)->where($where)->order('`id` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$data = $database->deal_data($data);
		$this->assign('data',$data);
		$this->assign('pagebar', $p->show());
		$this->assign('service_lang', $database->service_lang);

		$this->assign('from',$database->from);
		$this->display();
	}

	/**
	 * 系统多语言导入
	 */
	public function data_import(){
		$this->display();
	}

	public function del(){
		$id = I('get.id');
		if(empty($id)){
			$this->error("参数错误",U("Lang/index"));
		}
		$database = M('Lang');
		$data = array(
			'is_del' => 1
		);
		$database->where(array('id' => $id))->data($data)->save();
		$this->success("删除成功",U("Lang/index"));
	}

	/**
	 * 批量修改
	 */
	public function update_batch(){
		$ids = I('ids');
		$field = trim(I('field'));
		$val = trim(I('val'));
		if(empty($ids) || empty($field) || empty($val)){
			$output = array(
				'status' => 3,
				'msg' => '参数错误'
			);
			echo json_encode($output);exit;
		}
		$database = M('Lang');
		$data = array(
			$field => $val
		);
		$database->where(array('id' => array('in',$ids)))->data($data)->save();
		
		D('Config')->clearDataCacheDir('lang');
		
		$output = array(
			'status' => 1,
			'msg' => '操作成功'
		);
		echo json_encode($output);exit;	
	}

	/**
	 * ajax修改词语翻译
	 */
	public function update(){
		$id = intval(I('id'));
		$field = trim(I('field'));
		$val = trim(htmlspecialchars_decode($_POST['val']));
		if(empty($id) || empty($field)){
			$output = array(
				'status' => 3,
				'msg' => '参数错误'
			);
			echo json_encode($output);exit;
		}
		$database = D('Lang');
		$data = $database->find($id);
		if(empty($data)){
			$output = array(
				'status' => 3,
				'msg' => '参数错误'
			);
			echo json_encode($output);exit;	
		}
		$pattern = '/x[\d]|X[\d]/';
		preg_match_all($pattern,$data['key'],$matchs);
		$matchs = $matchs[0];
		if(!empty($matchs)){
			preg_match_all($pattern,$val,$matchs_val);
			$matchs_val = $matchs_val[0];
			asort($matchs);
			asort($matchs_val);
			$diff = array_diff($matchs, $matchs_val);
			if(!empty($diff)){
				$output = array(
					'status' => 2,
					'msg' => '您输入的翻译与目标词语中的变量匹配不一致（变量个数或者大小写），请携带好一致变量再提交'
				);
				echo json_encode($output);exit;
			}
		}
		$update = array(
			$field => $val
		);
		if($database->where(['id'=>$id])->data($update)->save()){
			D('Config')->clearDataCacheDir('lang');
			
			$condition = array(
				'key' => $data['key'],
				'id'  => array('neq',$id)
			);
			$other = $database->where($condition)->select();
			if($other){
				$result = array();
				$other = $database->deal_data($other);
				foreach ($other as $key => $value) {
					$temp = array();
					$temp['id'] = $value['id'];
					$temp['key'] = $value['key'];
					$temp['val'] = $value[$field];
					$temp['module'] = $value['module'];
					$result[] = $temp;
				}
				$output = array(
					'status' => 4,
					'msg' => '有以下词语与当前翻译词语相同，是否替换？',
					'words' => $result
				);
				echo json_encode($output);exit;	
			}
			else{
				$output = array(
					'status' => 1,
					'msg' => '操作成功'
				);
				echo json_encode($output);exit;	
			}
		}
		else{
			$output = array(
				'status' => 3,
				'msg' => '操作失败'
			);
			echo json_encode($output);exit;		
		}
	}


	//上传表格
	public function data_import_upload(){
		set_time_limit(0);
		$type 				= strtolower(I('type'));

		import('ORG.Util.Dir');
        Dir::delDirnotself('./upload/excel/language');
        $upload_dir = './upload/excel/language/' . date('Ymd') . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $lang_model = D('Lang');
        if(empty($lang_model->type_fields[$type])){
			$this->ajaxReturn(array(),L_('数据错误'),2);
		}//1.02
		$default_language = C("config.default_language");
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
				        $data[$rowIndex][$colIndex] = $cell;
				    }
				}
				else{
					break;
				}
			}
			$title = $data[1];//表格标题
			//验证标题的正确性
			if(!$this->checkTitle($title,$type)){
				$this->ajaxReturn(array(),L_('表格标题不正确！无法执行导入'),2);
			}

			$table_model = M($type);
			for($i = 2; $i <= count($data) ; $i++){
				$lang_data = array();
				if($data[$i] && $data[$i]['A']){
					$tf = $lang_model->type_fields[$type];
					$has = true;
					$loop = 0;
					foreach ($data[$i] as $key => $value) {
						$value = empty(trim($value)) ? '' : trim($value);
						if($has){
							if($key == 'A' && is_numeric($value)){
								foreach ($lang_model->service_lang as $service) {
									$lang_data[$service['val']][key($tf)] = $value;
								}
								$has = false;
								array_shift($tf);
								continue;
							}
							if($loop != count($lang_model->service_lang)-1){
								$lang_data[$lang_model->service_lang[$loop]['val']][key($tf)] = $value;
								$loop++;
							}
							else{
								$lang_data[$lang_model->service_lang[$loop]['val']][key($tf)] = $value;
								$loop = 0;
								$has = false;
								array_shift($tf);
							}
						}
						else{
							$has = true;
						}
					}
				}
				if($lang_data){
					foreach ($lang_data as $key => $value) {
						$del_lang = false;
						if(isset($value[$lang_model->lang_table_important_field[$type]]) && $value[$lang_model->lang_table_important_field[$type]] == '') $del_lang = true;
						foreach ($value as $k => $v) {
							$value[$k."_".$key] = $v;
							unset($value[$k]);
						}
						$model = M($type."_".$key);
						$f = key($value);
						$pk = array_shift($value);
						$where = array(
							$f => $pk
						);
						if($del_lang){
							$model->where($where)->delete();
						} else{
							if($model->where($where)->find()){
								$model->data($value)->where($where)->save();
							} else{
								$value[$f] = $pk;
								$model->data($value)->add();	
							}
						}
					}
				}
			}
			
			D('Config')->clearCache();
			
			$this->ajaxReturn(array(),L_("导入成功"),1);
        }
        else{
        	$this->ajaxReturn(array(),$upload->getErrorMsg(),2);
        }
	}

	//判断表格的每一列标题，必须一致
	public function checkTitle($title,$type){
		$lang_model = D('Lang');
		foreach ($title as $key => $value) {
			if (empty($value)) {
				unset($title[$key]);
			}
		}
		$s = 'A';
		$data = array();
		foreach ($lang_model->type_fields[$type] as $key => $value) {
			if($s != 'A'){
				$data[$s] = $value['name'];
				$s++;
				foreach($lang_model->service_lang as $l){
					$data[$s] = $value['name']."(".$l['display'].")";
					$s++;
				}
			}
			else{
				$data[$s] = $value['name'];
				$s++;
			}			
		}
		return empty(array_diff_assoc($title,$data)) ? true : false;
	}

	//下载表格
	public function data_import_download(){
		set_time_limit(0);
		$type 				= strtolower(I('type'));
		$db 				= M($type);
		$lang_model 		= D('Lang');
		// 导航分类先行查询条件
		if ($type=='slider') {
		    $category = strtolower(I('category'));
            $database_slider_category  = D('Slider_category');
            $condition_slider_category['cat_key'] = $category;
            $now_slider_category = $database_slider_category->field('`cat_id`')->where($condition_slider_category)->find();
            $cat_id = $now_slider_category['cat_id'];
        } elseif ($type=='home_menu') {
            // wap底部导航先行查询条件
            $category = strtolower(I('category'));
            $database_slider_category  = D('Home_menu_category');
            $condition_slider_category['cat_key'] = $category;
            $now_slider_category = $database_slider_category->field('`cat_id`')->where($condition_slider_category)->find();
            $cat_id = $now_slider_category['cat_id'];
        }

		$type_conditions = array(
			//这里增加条件
			'new_merchant_menu' => array(
				'status' => 1,
				'show' => 1
			),
            'slider' => array(
                'cat_id' => $cat_id,
                'status' => 1,
            ),
            'home_menu' => array(
                'cat_id' => $cat_id,
                'status' => 1,
            ),

			'merchant_category' => array(
					'cat_status' => 1,
			),

            'system_coupon' => array(
                'cate_name' => array('in', array('all','shop')),
                'status' => array('neq',3),
            ),
            'national_phone' => array(
                'status' => 1,
            ),
            'merchant' => array(
            	'status' => array('neq',4)
            ),
            'meal_store_category' => array(
                'cat_status' => 1,
            ),
		);
		if(empty($lang_model->type_fields[$type])){
			$this->error(L_("参数错误"),U('data_import'));
		}

		$fields = array_keys($lang_model->type_fields[$type]);
		$type_conditions[$type] = $type_conditions[$type] ? $type_conditions[$type] : array();
		//查询所有主表数据
		$table_data = $db->where($type_conditions[$type])->field(implode(',', $fields))->select();
		//查询所有语言表数据
		$lang_data = array();
		foreach ($lang_model->service_lang as $value) {
			$model = M($type."_".$value['val']."_view");
			$lang_data[$value['val']] = $model->where($type_conditions[$type])->field(implode('_'.$value['val'].',', $fields).'_'.$value['val'])->select();
			$_lang = array();
			foreach ($lang_data[$value['val']] as $k => $v) {
				$temp = array_keys($v);
				if($v[$temp[0]]){
					$_lang[$v[$temp[0]]] = $v;
				}
			}
			$lang_data[$value['val']] = $_lang;
		}

		//生成csv表格
		import('@.ORG.phpexcel.PHPExcel','','.php');
		$objPHPExcel = new PHPExcel();
		$e = $objPHPExcel->setActiveSheetIndex(0);
		// 所有单元格默认高度
		$array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
		//设置第一行
		$i = 0;
		$line = 1;
		foreach ($lang_model->type_fields[$type] as $key => $value) {
			if($i != 0){
				$e->setCellValue($array[$i].$line, $value['name']);
				$e->getColumnDimension($array[$i])->setWidth($value['width']);
				$e->getstyle($array[$i].$line)->getFont()->setBold(true);
				$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
				$i++;
				foreach($lang_model->service_lang as $l){
					$e->setCellValue($array[$i].$line, $value['name']."(".$l['display'].")");
					$e->getColumnDimension($array[$i])->setWidth($value['width']+10);
					$e->getstyle($array[$i].$line)->getFont()->setBold(true);
					$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
					$i++;
				}
			}
			else{
				$e->setCellValue($array[$i].$line, $value['name']);
				$e->getColumnDimension($array[$i])->setWidth($value['width']);
				$e->getstyle($array[$i].$line)->getFont()->setBold(true);
				$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
				$i++;
			}
		}
		$line++;//行数+1
		foreach($table_data as $tdata){
			$pk = '';
			$i = 0;
			foreach ($tdata as $key => $value) {
				if($i != 0){
					$e->setCellValue($array[$i].$line, $value);
					$e->getStyle($array[$i].$line)->getAlignment()->setWrapText(TRUE); 
					$i++;
					foreach($lang_model->service_lang as $l){
						$text = $lang_data[$l['val']][$tdata[$pk]][$key.'_'.$l['val']];
						if($text){
							$e->setCellValue($array[$i].$line, $text);
							$e->getStyle($array[$i].$line)->getAlignment()->setWrapText(TRUE); 
						}
						else{
							$e->setCellValue($array[$i].$line, '');
						}
						$i++;
					}
				}
				else{
					$e->setCellValue($array[$i].$line, $value);
					$i++;
					$pk = $key;
				}
			}
			$line++;
		}
		
		//$objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(25);
		$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(25);
	    // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true); //保护工作表
	    $objPHPExcel->getActiveSheet()->setTitle($type);
	    $objPHPExcel->setActiveSheetIndex(0);
	    ob_end_clean();//清除缓冲区,避免乱码 
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$type.'.xls"');
		header('Cache-Control: max-age=0');
	    $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter->save('php://output');
        exit;   
	}

	//配置项多语言下载表格
	public function config_import_download(){
		set_time_limit(0);
		$type 				= 'config';
		$db 				= M($type);
		$lang_model 		= D('Lang');

		$type_conditions = array(
			//这里增加条件
			'config' => array(
				'is_lang' => 1
			),
		);
		if(empty($lang_model->type_fields[$type])){
			$this->error(L_("参数错误"),U('data_import'));
		}

		$fields = array_keys($lang_model->type_fields[$type]);
		$type_conditions[$type] = $type_conditions[$type] ? $type_conditions[$type] : array();
		foreach ($lang_model->service_lang as $value) {
			if($value['val'] != 'chinese'){
				$fields[] = 'value_'.$value['val'];
			}
		}
		//查询所有主表数据
		$table_data = $db->where($type_conditions[$type])->field(implode(',', $fields))->select();

		//生成csv表格
		import('@.ORG.phpexcel.PHPExcel','','.php');
		$objPHPExcel = new PHPExcel();
		$e = $objPHPExcel->setActiveSheetIndex(0);
		// 所有单元格默认高度
		$array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
		//设置第一行
		$i = 0;
		$line = 1;
		$value_chinese = '';
		$width_chinese = 0;
		foreach ($lang_model->type_fields[$type] as $key => $value) {
			$e->setCellValue($array[$i].$line, $value['name']);
			$e->getColumnDimension($array[$i])->setWidth($value['width']);
			$e->getstyle($array[$i].$line)->getFont()->setBold(true);
			$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
			$value_chinese = $value['name'];
			$width_chinese = $value['width']+10;
			$i++;
		}
		foreach ($lang_model->service_lang as $value) {
			if($value['val'] != 'chinese'){
				$e->setCellValue($array[$i].$line, $value_chinese."(".$value['display'].")");
				$e->getColumnDimension($array[$i])->setWidth($width_chinese);
				$e->getstyle($array[$i].$line)->getFont()->setBold(true);
				$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
				$i++;
			}
		}
		$line++;//行数+1
		foreach($table_data as $tdata){
			$i = 0;
			foreach ($tdata as $key => $value) {
				$e->setCellValue($array[$i].$line, $value);
				$i++;
			}
			$line++;
		}
		
		//$objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(25);
		$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(25);
	    // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true); //保护工作表
	    $objPHPExcel->getActiveSheet()->setTitle($type);
	    $objPHPExcel->setActiveSheetIndex(0);
	    ob_end_clean();//清除缓冲区,避免乱码 
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$type.'.xls"');
		header('Cache-Control: max-age=0');
	    $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter->save('php://output');
        exit;   
	}

	//判断配置项表格的每一列标题，必须一致
	public function checkConfigTitle($title,$type){
		$lang_model = D('Lang');
		$s = 'A';
		$data = array();
		$value_chinese = '';
		foreach ($lang_model->type_fields[$type] as $key => $value) {
			$data[$s] = $value['name'];
			$value_chinese = $value['name'];
			$s++;				
		}
		foreach ($lang_model->service_lang as $value) {
			if($value['val'] != 'chinese'){
				$data[$s] = $value_chinese."(".$value['display'].")";
				$s++;
			}
		}
		return empty(array_diff_assoc($title,$data)) ? true : false;
	}

	public function config_import_upload(){
		set_time_limit(0);
		$type 				= 'config';

		import('ORG.Util.Dir');
        Dir::delDirnotself('./upload/excel/language');
        $upload_dir = './upload/excel/language/' . date('Ymd') . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $lang_model = D('Lang');
        if(empty($lang_model->type_fields[$type])){
			$this->ajaxReturn(array(),L_('数据错误'),2);
		}//1.02
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
				        $data[$rowIndex][$colIndex] = $cell;
				    }
				}
				else{
					break;
				}
			}
			$title = $data[1];//表格标题
			//验证标题的正确性
			if(!$this->checkConfigTitle($title,$type)){
				$this->ajaxReturn(array(),L_('表格标题不正确！无法执行导入'),2);
			}

			$table_model = M($type);
			for($i = 2; $i <= count($data) ; $i++){
				$A = trim($data[$i]['A']);
				if(!empty($A)){
					$where = array();
					$where['name'] = $A;
					$save = array();
					$B = 'B';
					$save['value'] = $data[$i][$B];
					foreach ($lang_model->service_lang as $key => $value) {
						if($value['val'] != 'chinese'){
							$save['value_'.$value['val']] = $data[$i][++$B];
						}
					}
					$table_model->where($where)->data($save)->save();
				}
			}
			$this->ajaxReturn(array(),L_("导入成功"),1);
        }
        else{
        	$this->ajaxReturn(array(),$upload->getErrorMsg(),2);
        }
	}

	//修改系统语言配置
	public function service_lang(){
		$db = D("Lang");
		$this->assign('lang_list',$db->service_lang);
		$this->display();
	}

	//删除一种语言
	public function service_lang_del(){
		$id = I("id");
		if(empty($id)){
			$this->error("非法操作", U("Lang/service_lang"));
		}
		$db = M('lang_service');
		if($db->where('id='.$id)->delete()){
			$this->success("删除成功", U("Lang/service_lang"));
		}
		else{
			$this->error("删除失败", U("Lang/service_lang"));
		}
	}

	public function create_table_field(){
		if(IS_POST){
			$table = trim(I("table"));
			$add_fields = str_replace("，",",",trim(I("add_fields")));
			$del_fields = str_replace("，",",",trim(I("del_fields")));
			if(empty($table) || (empty($add_fields) && empty($del_fields))) $this->error("非法操作", U("Lang/service_lang"));
			set_time_limit(0);
			import('ORG.Util.Dir');
			Dir::delDirnotself('./runtime');
			$db = D('Lang');
			$add_fields = explode(',', $add_fields);
			$del_fields = explode(',', $del_fields);
			foreach ($db->service_lang as $key => $value) {
				$table_db = M($table."_".$value['val']);
				$db_pk = $table_db->getPk();
				$table_master = M($table);
				$db_pk_master = $table_master->getPk();
				$fields_arr = $table_master->getDbFields();
				$db_fields_type = $table_master->getFieldType()['_type'];
				if($add_fields){
					foreach ($add_fields as $v) {
						if(in_array($v, $fields_arr)){
							$sql = "ALTER TABLE  `pigcms_".$table."_".$value['val']."` ADD  `".$v."_".$value['val']."` ".$db_fields_type[$v];
							$db->execute($sql);
						}
					}
				}
				if($del_fields){
					foreach ($del_fields as $v) {
						if(in_array($v, $fields_arr)){
							$sql = "ALTER TABLE  `pigcms_".$table."_".$value['val']."` DROP  `".$v."_".$value['val']."` ";
							$db->execute($sql);
						}
					}
				}
				if($db_pk && $db_pk_master){
					//创建并更新视图
					$sql = "CREATE OR REPLACE algorithm = MERGE ";
					$sql .= "view pigcms_".$table."_".$value['val']."_view as ( ";
					$sql .= "select a.*,b.*	from pigcms_".$table." as a inner join pigcms_".$table."_".$value['val']." as b on a.".$db_pk_master."=b.".$db_pk.")";
					$db->execute($sql);
				}
			}
			$this->success("字段更新成功");			
		}
		$db = D('Lang');
		$this->assign('tables',$db->service_lang_table);
		$this->display();
	}

	//只重新生成所有视图表
	public function create_view(){
		if(IS_POST){
			$table = trim(I("table"));
			if(empty($table)) $this->error("非法操作", U("Lang/service_lang"));
			set_time_limit(0);
			import('ORG.Util.Dir');
			Dir::delDirnotself('./runtime');
			$db = D('Lang');
			foreach ($db->service_lang as $key => $value) {
				$table_db = M($table."_".$value['val']);
				$db_pk = $table_db->getPk();
				$table_master = M($table);
				$db_pk_master = $table_master->getPk();
				if($db_pk && $db_pk_master){
					//创建并更新视图
					$sql = "CREATE OR REPLACE algorithm = MERGE ";
					$sql .= "view pigcms_".$table."_".$value['val']."_view as ( ";
					$sql .= "select a.*,b.*	from pigcms_".$table." as a inner join pigcms_".$table."_".$value['val']." as b on a.".$db_pk_master."=b.".$db_pk.")";
					$db->execute($sql);
				}
			}
			$this->success("视图刷新成功");			
		}
		$db = D('Lang');
		$this->assign('tables',$db->service_lang_table);
		$this->display();
	}

	//添加一种语言
	public function service_lang_add(){
		if(IS_POST){
			set_time_limit(0);
			$db = D('Lang');
			$service_lang_db = M("lang_service");
			$_POST['val'] = trim($_POST['val']);
			if(empty($_POST['val']) || empty($_POST['display'])){
				$this->error('非法操作~');
			}
			if($service_lang_db->data($_POST)->add()){
				import('ORG.Util.Dir');
				Dir::delDirnotself('./runtime');
				//添加视图  获取所有支持多语言的表
				foreach ($db->service_lang_table as $table) {
					$table_db = M($table."_chinese");//以chinese做对照
					$fields_arr = $table_db->getDbFields();
					$db_fields_type = $table_db->getFieldType()['_type'];
					$db_pk = $table_db->getPk();
					if($fields_arr){
						$sql = "DROP TABLE IF EXISTS `pigcms_".$table."_".$_POST['val']."` ; ";
						$db->execute($sql);
						$sql = "CREATE TABLE IF NOT EXISTS `pigcms_".$table."_".$_POST['val']."` (";
						$temp = array();
						foreach ($fields_arr as $field) {
							$temp[] = "`".str_replace('chinese', $_POST['val'], $field)."` ".$db_fields_type[$field];	
						}	
						$sql .= implode(',', $temp)." , PRIMARY KEY (`".str_replace('chinese', $_POST['val'], $db_pk)."`) ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
						$db->execute($sql);

						//创建视图
						$sql = "CREATE OR REPLACE algorithm = MERGE ";
						$sql .= "view pigcms_".$table."_".$_POST['val']."_view as ( ";
						$sql .= "select a.*,b.*	from pigcms_".$table." as a inner join pigcms_".$table."_".$_POST['val']." as b on a.".str_replace('_chinese', '', $db_pk)."=b.".str_replace('chinese', $_POST['val'], $db_pk).")";
						$db->execute($sql);
					}
				}

				//pigcms_config表特殊，添加一个字段
				$sql = 'ALTER TABLE  `pigcms_config` ADD  `value_'.$_POST['val'].'` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
				$db->execute($sql);

				//为配置中选择语言增加一个选择项
				if(C("config.default_language")){
					$default_language = explode('|', C("config.default_language"));
					$zuhe = $_POST['val'].':'.$_POST['display'];
					if(!in_array($zuhe, $default_language)){
						$default_language[] = $zuhe;
					}
					C("config.default_language", implode('|', $default_language));
					M("Config")->where(['name'=> "default_language"])->data(['type'=> C("config.default_language")])->save();
				}
				$this->success('添加成功');
			}
		}
		$this->display();
	}


	//导出界面多语言
	public function download_translate(){
		set_time_limit(0);
		$lang_model 		= D('Lang');

		//查询所有词语
		$table_data = $lang_model->where('is_del = 0')->field(true)->select();
		$table_data = $lang_model->deal_data($table_data);

		//生成csv表格
		import('@.ORG.phpexcel.PHPExcel','','.php');
		$objPHPExcel = new PHPExcel();
		$e = $objPHPExcel->setActiveSheetIndex(0);
		// 所有单元格默认高度
		$array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
		//设置第一行
		$i = 0;
		$line = 1;
		$title = array(
			array(
				'name' => '编号',
				'width' => 20,
			),
			array(
				'name' => '目标词语',
				'width' => 50,
			),
		);
		foreach ($lang_model->service_lang as $value) {
			$title[] = array(
				'name' => $value['display'],
				'width' => 50,
			);
		}
		$title[] = array(
			'name' => '所属板块',
			'width' => 50,
		);
		foreach ($title as $key => $value) {
			if($i != 0){
				$e->setCellValue($array[$i].$line, $value['name']);
				$e->getColumnDimension($array[$i])->setWidth($value['width']);
				$e->getstyle($array[$i].$line)->getFont()->setBold(true);
				$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
				$i++;
			}
			else{
				$e->setCellValue($array[$i].$line, $value['name']);
				$e->getColumnDimension($array[$i])->setWidth($value['width']);
				$e->getstyle($array[$i].$line)->getFont()->setBold(true);
				$e->getstyle($array[$i].$line)->getAlignment()->setHorizontal(PHPExcel_style_Alignment::VERTICAL_CENTER);
				$i++;
			}
		}
		$line++;//行数+1
		foreach($table_data as $tdata){
			$i = 0;
			$e->setCellValue($array[$i++].$line, $tdata['id']);
			$e->setCellValue($array[$i++].$line, $tdata['key']);
			foreach ($lang_model->service_lang as $service_l) {
				$e->setCellValue($array[$i++].$line, $tdata[$service_l['val']]);
			}
			$e->setCellValue($array[$i++].$line, $tdata['module']);
			$line++;
		}
		
		//$objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(25);
		$objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(25);
	    // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true); //保护工作表
	    $objPHPExcel->getActiveSheet()->setTitle('界面多语言翻译');
	    $objPHPExcel->setActiveSheetIndex(0);
	    ob_end_clean();//清除缓冲区,避免乱码 
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="界面翻译.xls"');
		header('Cache-Control: max-age=0');
	    $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter->save('php://output');
        exit; 
	}

	//导入界面多语言
	public function translate_import_upload(){
		set_time_limit(0);

		import('ORG.Util.Dir');
        Dir::delDirnotself('./upload/excel/language');
        $upload_dir = './upload/excel/language/' . date('Ymd') . '/';
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
				        if(($colIndex == 'A' && empty($cell)) || ($colIndex == 'B' && empty($cell))){
				        	$stop = true;
				        	break;
				        }
				        if ($cell instanceof PHPExcel_RichText) { //富文本转换字符串
				            $cell = $cell->__toString();
				        }
				        $data[$rowIndex][$colIndex] = $cell;
				    }
				}
				else{
					break;
				}
			}
			$title = $data[1];//表格标题
			//验证标题的正确性
			if(!$this->checkTranslateTitle($title,$type)){
				$this->ajaxReturn(array(),L_('表格标题不正确！无法执行导入'),2);
			}

			for($i = 2; $i <= count($data) ; $i++){
				$A = trim($data[$i]['A']);
				if(!empty($A)){
					$where = array();
					$where['id'] = $A;
					$save = array();
					$B = 'B';
					foreach ($lang_model->service_lang as $key => $value) {
						$save[$value['val']] = $data[$i][++$B];
					}
					$lang_model->where($where)->data($save)->save();
				}
			}
			$this->ajaxReturn(array(),L_("导入成功"),1);
        }
        else{
        	$this->ajaxReturn(array(),$upload->getErrorMsg(),2);
        }
	}

	//判断翻译项表格的每一列标题，必须一致
	public function checkTranslateTitle($title,$type){
		if(empty($title)) return false;
		$lang_model = D('Lang');
		$s = 'A';
		$data = array();
		$value_chinese = '';
		$data[$s++] = '编号';		
		$data[$s++] = '目标词语';
		foreach ($lang_model->service_lang as $value) {
			$data[$s] = $value['display'];
			$s++;
		}
		$data[$s++] = '所属板块';
		return empty(array_diff_assoc($title,$data)) ? true : false;
	}

	

	public function liluhao(){

		import('@.ORG.pay.Sande');
		$order_info['pay_type'] = 'wxapp';
		$order_info['openid'] = '';
		$order_info['payment_money'] =  1.2;
		$order_info['orderNo'] =   'gwegawgweagwgwea3323423'.rand(10000,99999);
		$pay = new Sande();
		$go_pay_param = $pay->pay($order_info);

		/*import('@.ORG.pay.ChinaumsYsf');
		$order_info['payment_money'] =  1.2;
		$order_info['orderNo'] =   '10Y92000133151245'.rand(10000,99999);
		$order_info['order_content'] = [
			[
				'goods_id' => 11,
				'name' => '测试商品',
				'num' => 3,
				'price' => 0.4
			]
		];
		$pay = new ChinaumsYsf();
		$go_pay_param = $pay->pay($order_info, '89852275813010A');*/

	}
}
