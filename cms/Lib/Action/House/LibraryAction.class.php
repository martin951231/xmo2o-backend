<?php
class LibraryAction extends BaseAction{
    protected $village_id;
    protected $huizhisq = false;
    protected $jsdhwy_new_wap_index = 0;
    protected $guess_you_like_goods = 0;
    public function _initialize(){
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
        C('customization', D('Config_customization')->get_config());
        // 营山定制开关
        $this->huizhisq = !empty(C('customization.huizhisq')) ? true : false;
        $this->assign('yingshan',$this->huizhisq);
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        $this->jsdhwy_new_wap_index=$jsdhwy_new_wap_index;
        $this->assign('jsdhwy_new_wap_index',$jsdhwy_new_wap_index);
        $this->guess_you_like_goods=D('Config_customization')->getGuessYouLikeGoodsCustomized();
        $this->assign('guess_you_like_goods',$this->guess_you_like_goods);
    }
    public function express_service_list(){
        //快递代收-查看 权限
        if (!in_array(207, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->house_session['village_id'];
        $database_house_village_express = D('House_village_express');

        $has_express_service = $this->getHasConfig($village_id, 'has_express_service');
        $this->assign('has_express_service',$has_express_service);

        if($has_express_service){
            $where['village_id'] = $village_id;
			//搜索
			if($_GET['find_type']){
				$find_type = intval($_GET['find_type']);
				$this->assign('find_type', $find_type);
			}
			if($_GET['find_value']){
				$find_value = trim($_GET['find_value']);
				$this->assign('find_value', $find_value);
				
				if($find_type == 1){	//取单号
					$where['ep.fetch_code'] = $find_value;
				}else if($find_type == 2){	//手机号
					$where['ep.phone'] = $find_value;
				}else if($find_type == 3){	//快递单号
					$where['ep.express_no'] = $find_value;
				}
				
			}
			
            $list = $database_house_village_express->express_service_page_list($where);
            if(!$list){
                $this->error('处理数据有误！');
            }else{
                $this->assign('list',$list['list']);
            }
        }

        $this->display();
    }


    public function fetch_code(){
        $village_id = $this->house_session['village_id'];
		//找物业有没有绑定公众号
		if(D('Weixin_bind')->where(['mer_id'=>$$this->house_session['property_id'], 'service_type_info'=>2, 'type'=>2])->find()){
			$qrcode = $this->config['site_url'] . '/index.php?c=Recognition&a=get_weixin_qrcode&qrcode_id=' . ($village_id + 1100000000) . '&village_id=' . $village_id;
		}else{
			$qrcode = $this->config['site_url'] . '/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id=' . ($village_id + 1100000000) . '&village_id=' . $village_id;
		}
        $this->assign('qrcode',$qrcode);
        $this->display();
    }

    public function express_analysis(){
        //快递统计 权限
        if (!in_array(212, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->house_session['village_id'];
        $database_house_village_express = D('House_village_express');
        $_GET['express_id'] && $where['express_type'] = $_GET['express_id'];
        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }

            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));

            $where['_string']= "(ep.add_time BETWEEN ".$period[0].' AND '.$period[1].")";
        }
        $has_express_service = $this->getHasConfig($village_id, 'has_express_service');
        $this->assign('has_express_service',$has_express_service);
        $express_list = D('Express')->get_express_list();
        $this->assign('express_list',$express_list);
        if($has_express_service){
            $where['village_id'] = $village_id;
            $list = $database_house_village_express->express_service_page_list($where);
            if($_GET['export']){
                $this->express_export($where);exit;
            }
            if(!$list){
                $this->error('处理数据有误！');
            }else{
                $this->assign('list',$list['list']);
            }
        }
        $this->display();
    }

    public function express_export($where){
        //快递导出 权限
        if (!in_array(213, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = "快递订单记录";
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);



        $database_express = D('Express');
        $database_house_village_user_bind = D('House_village_user_bind');

        $where['ep.village_id'] = $where['village_id'];
        $village_id =  $where['village_id'];
        unset($where['village_id']);
        $count = M('House_village_express')->join('as ep LEFT JOIN '.C('DB_PREFIX').'house_village_floor as f ON ep.floor_id = f.floor_id ')->where($where)->count();
        $order = 'id desc';




        $express_where['status'] = 1;
        $express_info = $database_express->where($express_where)->getField('id,name');
        $length = ceil($count / 1000);

        $db_house_village = D('House_village');
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千个订单信息');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);

            $objActSheet->setCellValue('A1', '编号');
            $objActSheet->setCellValue('B1', '快递类型');
            $objActSheet->setCellValue('C1', '快递单号');
            $objActSheet->setCellValue('D1', '收件人手机号');
            $objActSheet->setCellValue('E1', '收件人地址');
            $objActSheet->setCellValue('F1', '送件费用');
            $objActSheet->setCellValue('G1', '状态');
            $objActSheet->setCellValue('H1', '预约代送时间');
            $objActSheet->setCellValue('I1', '添加时间');


            $village_express_list = M('House_village_express')->join('as ep LEFT JOIN '.C('DB_PREFIX').'house_village_floor as f ON ep.floor_id = f.floor_id LEFT JOIN '.C('DB_PREFIX').'house_village_express_order o ON o.express_id = ep.id')->where($where)->field('ep.*,f.floor_name,f.single_id,o.send_time')->order($order)->limit($i * 1000,1000)->select();



            if (!empty($village_express_list)) {
                $index = 1;
                foreach ($village_express_list as $value) {
                    $var_address = "";
                    $all_address = D('House_village_user_bind')->field('address,single_id,floor_id,layer_id,vacancy_id')->where(array('village_id'=>$value['village_id'],'phone'=>$value['phone'],'floor_id'=>$value['floor_id'],'status'=>1))->select();
                    foreach($all_address as $m=>$n){
                        $address = $db_house_village->get_single_floor_room($n['single_id'],$n['floor_id'],$n['layer_id'],$n['vacancy_id'],$village_id);
                        $var_address .= $address."  ";
                    }
                    $value['address'] = $var_address;
                    $value['express_name'] = $express_info[$value['express_type']];
                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['express_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['express_no']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['phone']);
                    if($value['address']!=''){
                        $objActSheet->setCellValueExplicit('E' . $index, $value['address']);
                    }else{
                        if ($value['single_id']) {
                            $singe_name = M('House_village_single')->where(['id' => $value['single_id']])->getField('single_name');
                        } else {
                            $singe_name = '';
                        }
                        $objActSheet->setCellValueExplicit('E' . $index, $singe_name.' '.$value['floor_name']);
                    }

                    $objActSheet->setCellValueExplicit('F' . $index,$value['money'] );

                    if($value['status']==0){
                        $status_txt = '未取件';
                    }else if($value['status']==1){
                        $status_txt = '已取件（已取件（业主））';
                    }else{
                        $status_txt = '已取件（小区）';
                    }
                    $objActSheet->setCellValueExplicit('G' . $index, $status_txt);
                    $objActSheet->setCellValueExplicit('H' . $index, $value['send_time'] ? date('Y-m-d H:i:s', $value['send_time']) : '');
                    $objActSheet->setCellValueExplicit('I' . $index, $value['add_time'] ? date('Y-m-d H:i:s', $value['add_time']) : '');
                }
            }
            sleep(2);

        }


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

    public function express_add(){
        //快递-添加 权限
        if (!in_array(209, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->house_session['village_id'];
        $has_express_service = $this->getHasConfig($village_id, 'has_express_service');
        if($has_express_service){
            $database_house_village_express = D('House_village_express');
            $database_express = D('Express');

            if(IS_POST){
				$data = $_POST;
                $expressInfo = D('Express')->where(array('id'=>$data['express_type']))->find();

                if($expressInfo['kuaidiniao_code'] == 'YD'){//韵达快递
                    $res = $this->yunda($_POST['express_no'],date("Y-m-d H:i:s",time()));
                    if($res['success'] == true && $res['responseItems']['response']['status'] == true){

                    }
                }elseif ($expressInfo['kuaidiniao_code'] == 'HTKY') {//百世汇通
                    // echo '百世汇通';
                }elseif ($expressInfo['kuaidiniao_code'] == 'YTO') {//圆通快递
                    // echo '圆通快递';
                }

                $data['fetch_code'] = rand(100000,999999);
				$data['add_time'] = time();
				$data['village_id'] = $_SESSION['house']['village_id'];
                $result = $database_house_village_express->village_express_add($data);
                if(!$result){
                    $this->error('数据处理有误！');
                }else{
                    if($result['status']){
                        
                        $this->success($result['msg']);
                    }else{
                        $this->error($result['msg']);
                    }
                }
            }else{
                $express_list = $database_express->get_express_list();
                $express_config = M('House_village_express_config')->where(array('village_id'=>$village_id))->find();
                $express_money_status = $express_config['status'];
                $this->assign('express_config',$express_config);
                $this->assign('express_list',$express_list);
                $this->assign('express_money_status',$express_money_status);
                $this->display();
            }
        }else{
            $this->error('非法访问！');
        }
    }


    public function yunda($waybill,$time){
        $url = 'http://116.228.72.130:15137/cmn-web/getway/arrival_scan_info.do';
        $partnerid = "201101123456";
        $password = "123456";
        $appkey= "YUNDA123456";
        $xmldatatmp = '<?xml version="1.0" encoding="UTF-8"?><request><company>200230</company><scanList><scan><waybill>'.$waybill.'</waybill><scanTime>'.$time.'</scanTime></scan></scanList></request>'; 
        $xmldata =  base64_encode($xmldatatmp);
        $validation = md5($xmldata.$partnerid.$password.$appkey);
        $data = 'partnerid='.urlencode($partnerid).'&appkey='.urlencode($appkey).'&version=1.0&xmldata='.urlencode($xmldata).'&validation='.urlencode($validation);
        $result = $this->vpost($url,$data);
        //xml 转换成为数组
        $xml = simplexml_load_string($result);
        $arr = json_decode(json_encode($xml),TRUE);
        return $arr; // 返回数据
    }


    public function yundaSign($waybill,$status,$name,$time){
        $url = 'http://116.228.72.130:15137/cmn-web/getway/sign_scan_info.do';
        $partnerid = "201101123456";
        $password = "123456";
        $appkey= "YUNDA123456";
        $xmldatatmp = '<?xml version="1.0" encoding="UTF-8"?><request><company>200230</company><scanList><scan><waybill>'.$waybill.'</waybill><signStatus>'.$status.'</signStatus><signName>'.$name.'</signName><scanTime>'.$time.'</scanTime></scan></scanList></request>';
        $xmldata =  base64_encode($xmldatatmp);
        $validation = md5($xmldata.$partnerid.$password.$appkey);
        $data = 'partnerid='.urlencode($partnerid).'&appkey='.urlencode($appkey).'&version=1.0&xmldata='.urlencode($xmldata).'&validation='.urlencode($validation);
        $result = $this->vpost($url,$data);
        
        //xml 转换成为数组
        $xml = simplexml_load_string($result);
        $arr = json_decode(json_encode($xml),TRUE);
        return $arr; // 返回数据
    }



    public function yto(){
        $logistics_interface = '<request>
                    <waybillNo>1234567890</waybillNo>
                    <opCode>1310</opCode>
                    <opUserId>00011021</opUserId>
                    <opTime>2017-10-1815:48:53</opTime>
                    <weight>12.00</weight>
                    <frequencyNo>Z001</frequencyNo>
                    <logisticsCode>BLC0000001</logisticsCode>
                    <stationCode>210045000001</stationCode>
                    <orgCode>210045</orgCode>
                    <customerCode>KC000000001</customerCode>
                    <reserve1>reserve</reserve1>
                    <reserve2>reserve</reserve2>
                    <reserve3>reserve</reserve3>
                </request>';
        $data_digest = '';
        $costomer_code = '';
        $op_code = '';
        $station_type = '';
        $org_code = '';

    }

    public function ytoSign(){
 
    }

    public function baishi(){
        $url = "http://183.129.172.49/wuye/api/process";
        $partnerID = 'imxiaomai';
        $partnerKey = '123456';
        $express_no = '50004057408227';
        $serviceType = 'UpdateStatusByMailNo';
        $time = date("Y-m-d H:i:s",time()); 
        $datas['serviceProviderCode'] = $partnerID; //服务提供商代码
        $datas['requestTime'] = $time; //状态推送时间
        $datas['operator'] = 'BESTEXP'; //快递公司代码
        $datas['transactionCode'] = 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad'; //交易号
        $datas['billCode'] = $express_no;
        $datas['status'] = 'S01';
        $datas['remark'] = '快递员已投递包裹至快递柜';
        $datas['serviceSiteCode'] = '330106000001'; //服务点编号
        $bizData = json_encode($datas,JSON_UNESCAPED_UNICODE);
        $signString = base64_encode(md5($bizData.$partnerKey,32));
        $paramArray = 'serviceType='.$serviceType.'&partnerID='.$partnerID.'&digest='.$signString.'&bizData='.urlencode($bizData);
        $result = $this->vpost($url,$paramArray);
        dump(json_decode($result,true));
    }

    public function baishiSign(){
        $url = "http://183.129.172.49/wuye/api/process";
        $partnerID = 'imxiaomai';
        $partnerKey = '123456';
        $express_no = '50004057408227';
        $serviceType = 'UpdateStatusByMailNo';
        $time = date("Y-m-d H:i:s",time()); 
        $datas['serviceProviderCode'] = $partnerID; //服务提供商代码
        $datas['requestTime'] = $time; //状态推送时间
        $datas['operator'] = 'BESTEXP'; //快递公司代码
        $datas['transactionCode'] = 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad'; //交易号
        $datas['billCode'] = $express_no;
        $datas['status'] = 'S02';
        $datas['remark'] = '收件人已取货';
        $datas['serviceSiteCode'] = '330106000001'; //服务点编号
        $bizData = json_encode($datas,JSON_UNESCAPED_UNICODE);
        $signString = base64_encode(md5($bizData.$partnerKey,16));
        $paramArray = 'serviceType='.$serviceType.'&partnerID='.$partnerID.'&digest='.$signString.'&bizData='.urlencode($bizData);
        $result = $this->vpost($url,$paramArray);
        dump(json_decode($result,true));
    }


    public function vpost($url,$data){ // 模拟提交数据函数、
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
           echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }


    //把数组转换成为xml数据
    public function arrayToXml($arr){ 
        $xml = "<responses>"; 
        foreach ($arr as $key=>$val){ 
            if(is_array($val)){ 
                $xml.="<".$key.">".arrayToXml($val)."</".$key.">"; 
            }else{ 
                $xml.="<".$key.">".$val."</".$key.">"; 
            } 
        } 
        $xml.="</responses>"; 
        return $xml; 
    }


    public function ajax_check_express_no(){
        header("Content-Type: text/html;charset=utf-8"); 
        $logisticResult = $this->getOrderTracesByJson($_POST['express_no']);
        $Result = json_decode ( $logisticResult, JSON_FORCE_OBJECT );
        $express_info = D('Express')->where(array('kuaidiniao_code'=>$Result['Shippers'][0]['ShipperCode']))->find();
        if($express_info){
            exit(json_encode($express_info));
        }else{
            exit(json_encode(array('error'=>1)));
        }
        
    }

    /**
     * Json方式 单号识别tracking number
     */
    function getOrderTracesByJson($kuaidihao){
        $requestData= "{'LogisticCode':'".$kuaidihao."'}";
        $datas = array(
            'EBusinessID' => '1305647',
            'RequestType' => '2002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, '6bfe8732-a74b-4589-a469-5e0bc633f14a');
        $result=$this->sendPost('http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx', $datas);   
        //根据公司业务处理返回的信息......
        return $result;
    }
     
    /**
     *  post提交数据 
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据 
     * @return url响应返回的html
     */
    function sendPost($url, $datas) {
        $temps = array();   
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);      
        }   
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;   
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);  
        
        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容   
     * @param appkey Appkey
     * @return DataSign签名
     */
    function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }

    
    public function ajax_get_unit(){
		if(IS_AJAX){
			$village_id = $this->house_session['village_id'];
			$phone = $_POST['phone'];
            $floor_list = M('House_village_user_bind')->field('u.pigcms_id,u.single_id,u.address,u.layer_id,u.vacancy_id,f.floor_id,f.village_id,f.floor_name')->join('as u LEFT JOIN '.C('DB_PREFIX').'house_village_floor f ON u.floor_id = f.floor_id')->where(array('u.phone'=>$phone,'u.status'=>1,'u.village_id'=>$village_id, 'type' => array('in', [0,1,2,3])))->select();
			if(!empty($floor_list)){
			    foreach ($floor_list as &$v) {
                    $v['address'] = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
                }
				echo json_encode(array('status'=>1,'floor_list'=>$floor_list));exit;
			}else{
				echo json_encode(array('status'=>0,'floor_list'=>$floor_list));exit;
			}
		}
    }

    public function express_edit(){
        $database_house_village_express = D('House_village_express');
        if(IS_POST){
            //快递-取件 权限
            if (!in_array(210, $this->house_session['menus'])) {
               exit(json_encode(array('status'=>0,'msg'=>'对不起，您没有权限执行此操作')));
            }

            $id = $_POST['id'] + 0;
            $status = $_POST['status'] + 0;
            $fetch_code = intval($_POST['fetch_code']);
            if(!$id || !$status || !$fetch_code){
                exit(json_encode(array('status'=>0,'msg'=>'参数传递有误')));
            }

            $info = $database_house_village_express->where(array('id'=>$id,'fetch_code'=>$_POST['fetch_code']))->find();

            if(!is_array($info)){
                exit(json_encode(array('status'=>0,'msg'=>'请输入正确的取件码')));
            }
            
            $express_info = D('Express')->where(array('id'=>$info['express_type']))->find();

            if($express_info['kuaidiniao_code'] == 'YD'){//韵达快递
                $res = $this->yundaSign($info['express_no'],1,'本人',date("Y-m-d H:i:s",time()));
                if($res['success'] == true && $res['responseItems']['response']['status'] == true){

                }
            }elseif ($express_info['kuaidiniao_code'] == 'HTKY') {//百世汇通
                // echo '百世汇通';
            }elseif ($express_info['kuaidiniao_code'] == 'YTO') {//圆通快递
                // echo '圆通快递';
            }

            $where['id'] = $order_where['express_id'] = $id;
            $data['status'] = $status;

            $database_house_village_express_order = D('House_village_express_order');
            $database_house_village_express_order->house_village_express_order_edit($order_where,$data);

            $data['delivery_time'] = time();

            $result = $database_house_village_express->house_village_express_edit($where,$data);
            if(!$result){
                exit(json_encode(array('status'=>0,'msg'=>'数据处理有误！')));
            }else{




                exit(json_encode(array('status'=>$result['status'],'msg'=>$result['msg'])));
            }
        }
    }

    public function express_del(){
        //快递-删除 权限
        if (!in_array(211, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id = $_GET['id'] + 0;
        if(!$id){
            $this->error('传递参数有误！');
        }

        $database_house_village_express = D('House_village_express');
        $where['id'] = $id;
        $result = $database_house_village_express->village_express_del($where);
        if(!$result){
            $this->error('数据处理有误！');
        }else{
            if($result['status']){
                $this->success($result['msg']);
            }else{
                $this->error($result['msg']);
            }
        }
    }


    public function express_detail(){
        $id = $_GET['id'] + 0;
        if(!$id){
            $this->error('传递参数有误！');
        }
        $village_id = $this->house_session['village_id'];
        $database_house_village_express = D('House_village_express');
        $where['id'] = $id;
        $detail = $database_house_village_express->house_village_express_detail($where);
        if(!$detail){
            $this->error('数据处理有误！');
        }else{
            $d_house_village=D('House_village');
            if(isset($detail['detail']['phone']) && !empty($detail['detail']['phone'])){
                $detail['detail']['phone']=$d_house_village->phone_desensitization_v($detail['detail']['phone'],$village_id);
            }
            $this->assign('detail',$detail['detail']);
        }
        $this->display();
    }

    public function express_search(){
        $village_id = $this->house_session['village_id'];
        $has_express_service = $this->getHasConfig($village_id, 'has_express_service');

        if($has_express_service){
            $database_house_village_express = D('House_village_express');
            $database_express = D('Express');
            if(IS_POST){
                $keyword = $_POST['keyword'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];

                if($keyword){
                    $where['phone|express_no'] = array('like','%'.$keyword.'%');
                }

                if($start_time && $end_time){
                    $start_time = strtotime($start_time);
                    $end_time = strtotime($end_time.'23:59:59');
                    $where['add_time'] = array('between',array($start_time,$end_time));
                }else if($start_time){
                    $start_time = strtotime($start_time);
                    $where['add_time'] = array('egt',$start_time);
                }else if($end_time){
                    $end_time = strtotime($end_time.'23:59:59');
                    $where['add_time'] = array('lt',$end_time);
                }
                $result = $database_house_village_express->ajax_vllage_express_search($where);
                //删除权限
                $result['power'] = in_array(211, $this->house_session['menus']) ? true : false;
                exit(json_encode($result));
            }else{
                $express_list = $database_express->get_express_list();
                $this->assign('express_list',$express_list);
                $this->display();
            }
        }else{
            $this->error('非法访问！');
        }
    }

    public function visitor_list(){
        //访客登记-查看 权限
        if (!in_array(216, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_visitor = D('House_village_visitor');
        $database_house_village = D('House_village');

        $village_id =  $this->house_session['village_id'];
        $has_visitor = $this->getHasConfig($village_id, 'has_visitor');
        $this->assign('has_visitor',$has_visitor);
        if($has_visitor){
            $where['village_id'] = $village_id;
            $list = $database_house_village_visitor->house_village_visitor_page_list($where);
            if(!$list){
                $this->error('数据处理有误！');
            }else{
                $this->assign('list',$list['list']);
                $this->assign('visitor_type',$database_house_village_visitor->visitor_type);
            }
        }
        $this->display();
    }
    public function visitor_set(){
        //访客登记-配置
        if (!in_array(112183, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $village_id = $this->house_session['village_id'];
        $has_visitor = $this->getHasConfig($village_id, 'has_visitor');
        if($has_visitor){
            if(IS_POST){
                $data['name'] = $_POST['name']??1;
                $data['id_card'] = $_POST['id_card']??1;
                $setJson = json_encode($data);
                M('House_village')->where(array('village_id'=>$village_id))->data(['visitor_set_data'=>$setJson])->save();
                $msg = array('message' => '设置成功！','status' => 1);
                $this->ajaxReturn($msg);
            }else{
                $set = $this->getHasConfig($village_id, 'visitor_set_data');
                //默认都是必填项
                $visitorSet = [
                    'name'=>1,//访客姓名
                    'id_card'=>1//身份证号
                ];
                if($set){
                    $set = json_decode($set,true);
                    $visitorSet['name'] = $set['name']??$visitorSet['name'];
                    $visitorSet['id_card'] = $set['id_card']??$visitorSet['id_card'];
                }
                $this->assign('visitor_set',$visitorSet);
                $this->display();
            }
        }else{
            $this->error('非法访问！');
        }
    }

    public function visitor_add(){
        //访客登记-添加 权限
        if (!in_array(243, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->house_session['village_id'];
        $has_visitor = $this->getHasConfig($village_id, 'has_visitor');
        if($has_visitor){
            $database_house_village_visitor = D('House_village_visitor');
            if(IS_POST){
                if (!$_POST['owner_bind_id'] && (!$_POST['single_id'] || !$_POST['floor_id'] || !$_POST['layer_id'] || !$_POST['vacancy_id'])) {
                    $this->error('请填写业主手机号或者选择业主住址！');
                }
                $whereOwner = [];
                $whereOwner['village_id'] = $village_id;
                if ($_POST['owner_bind_id']) {
                    $whereOwner['pigcms_id'] = $_POST['owner_bind_id'];
                } elseif ($_POST['vacancy_id']) {
                    $whereOwner['vacancy_id'] = $_POST['vacancy_id'];
                    $whereOwner['type']   = ['in', [0,3]];
                    $whereOwner['status'] = 1;
                }
                $user_bind = D('House_village_user_bind')->where($whereOwner)->field('pigcms_id,uid,village_id, single_id,floor_id,layer_id,vacancy_id')->find();
                if ($user_bind && !$_POST['uid']) {
                    $_POST['uid'] = $user_bind['uid'];
                }
                if ($user_bind && !$_POST['owner_bind_id']) {
                    $_POST['owner_bind_id'] = $user_bind['pigcms_id'];
                }
                if ($user_bind && (!$_POST['single_id'] || !$_POST['floor_id'] || !$_POST['layer_id'] || !$_POST['vacancy_id'])) {
                    $_POST['single_id']  = $user_bind['single_id'];
                    $_POST['floor_id']   = $user_bind['floor_id'];
                    $_POST['layer_id']   = $user_bind['layer_id'];
                    $_POST['vacancy_id'] = $user_bind['vacancy_id'];
                }
                
                $_POST['owner_address'] = D('House_village')->get_single_floor_room($_POST['single_id'],$_POST['floor_id'],$_POST['layer_id'],$_POST['vacancy_id'],$village_id);
                $_POST['village_id'] = $village_id;
                $_POST['add_time'] = strtotime(date('Y-m-d'));
                $result =$database_house_village_visitor->house_village_visitor_add($_POST);
                if(!$result){
                    $this->error('数据处理有误！');
                }else{
                    if($result['status']){
                        $this->success($result['msg']);
                    }else{
                        $this->error($result['msg']);
                    }
                }
            }else{
                $visitor_type = $database_house_village_visitor->visitor_type;
                //楼栋
                $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->select();
                if($single_list){
                    $this->assign('single_list',$single_list);
                }else{
                    $this->assign('single_list',[]);
                }
                $this->assign('visitor_type' , $visitor_type);
                $this->display();
            }
        }else{
            $this->error('非法访问！');
        }
    }
    //获取楼栋
    public function get_single()
    {
        $village_id = $this->house_session['village_id'];

        $phone = $_POST['owner_phone'];
        if($phone) {
            $user_bind = M('House_village_user_bind')->where(array('phone' => $phone, 'status' => 1, 'village_id' => $village_id, 'type' => array('neq', 4)))->select();
            if($user_bind) {
                $single_arr = [];
                foreach ($user_bind as $k => $value) {
                    $single_arr[] = $value['single_id'];
                }
                $where['id'] = array('in', $single_arr);
            }
        }
        $where['village_id'] = $this->house_session['village_id'];
        $where['status'] = 1;

        $single_list = M('House_village_single')->where($where)->select();
        if($single_list){
            echo json_encode(array('status'=>1,'single_list'=>$single_list));exit;
        }else{
            echo json_encode(array('status'=>0,'single_list'=>[]));exit;
        }
    }
    //获取单元
    public function ajax_get_floor(){
        $village_id = $this->house_session['village_id'];
        $single_id = $_POST['single_id'];
        if(!$single_id){
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }
        $phone = $_POST['phone'];
        if($phone){
            $map['phone'] = $phone;
            $map['status'] = 1;
            $map['village_id'] = $village_id;
            $map['type'] = ['in',[0,1,2,3]];
            $map['single_id'] = $single_id;
//            $map['floor_id'] = ['gt',0];
//            $map['layer_id'] = ['gt',0];
//            $map['vacancy_id'] = ['gt',0];
            $user_bind = M('House_village_user_bind')->where($map)->select();
            if(empty($user_bind)){
                echo json_encode(array('status'=>0,'cat_list'=>[]));exit;
            }
            $floor_arr = [];
            foreach($user_bind as $k=>$value){
                $floor_arr[] = $value['floor_id'];
            }
            $where['floor_id'] = array('in',$floor_arr);
        }
        $database_house_village_floor= D('House_village_floor');
        $where['single_id'] = $single_id;
		$where['status'] = 1;
        $where['village_id'] = $this->house_session['village_id'];
//        dump($where);
        $cat_list = $database_house_village_floor->field('floor_id,floor_name')->where($where)->select();
        if($cat_list){
            exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
        }else{
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }
    }
    //获取层
    public function ajax_get_layer()
    {
        $village_id = $this->house_session['village_id'];
        $floor_id = $_POST['floor_id'];
        $phone = $_POST['phone'];
        if($phone){
            $map['phone'] = $phone;
            $map['status'] = 1;
            $map['village_id'] = $village_id;
            $map['type'] = ['in',[0,1,2,3]];
//            $map['single_id'] = ['gt',0];
            $map['floor_id'] = $floor_id;
//            $map['layer_id'] = ['gt',0];
//            $map['vacancy_id'] = ['gt',0];
            $user_bind = M('House_village_user_bind')->where($map)->select();
            if(empty($user_bind)){
                echo json_encode(array('status'=>0,'cat_list'=>[]));exit;
            }
            $layer_arr = [];
            foreach($user_bind as $k=>$value){
                $layer_arr[] = $value['layer_id'];
            }
            $where['id'] = array('in',$layer_arr);
        }
        $where['floor_id'] = $floor_id;
        $where['village_id'] = $village_id;
        $where['status'] = 1;
        $result = M('House_village_layer')->where($where)->select();
        if(!$result){
            exit(json_encode(array('status'=>0,'msg'=>'数据处理有误！')));
        }else{
            exit(json_encode(array('status'=>1,'cat_list'=>$result)));
        }
    }
    //获取房间
    public function ajax_get_vacancy(){
        $layer_id = $_POST['layer_id'];
        $village_id = $this->house_session['village_id'];
        if(!$layer_id){
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }
        $phone = $_POST['phone'];
        if($phone){
            $map['phone'] = $phone;
            $map['status'] = 1;
            $map['village_id'] = $village_id;
            $map['type'] = ['in',[0,1,2,3]];
//            $map['single_id'] = ['gt',0];
//            $map['floor_id'] = ['gt',0];
            $map['layer_id'] = $layer_id;
//            $map['vacancy_id'] = ['gt',0];
            $user_bind = M('House_village_user_bind')->where($map)->select();
//            dump($user_bind);die;
            if(empty($user_bind)){
                echo json_encode(array('status'=>0,'cat_list'=>[]));exit;
            }
            $vacancy_arr = [];
            foreach($user_bind as $k=>$value){
                $vacancy_arr[] = $value['vacancy_id'];
            }

            $where['pigcms_id'] = array('in',$vacancy_arr);
        }
        $database_house_village_vacancy= D('House_village_user_vacancy');
        $where['layer_id'] = $layer_id;
//		$where['status'] = 1;
        $where['village_id'] = $this->house_session['village_id'];
        $cat_list = $database_house_village_vacancy->field('pigcms_id,room')->where($where)->select();
        if($cat_list){
            exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
        }else{
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }
    }
    //根据业主地址获取业主信息
    public function ajax_get_user_info(){
        if(IS_AJAX){
            $village_id = $this->house_session['village_id'];
            $single_id = $_POST['single_id'];
            $floor_id = $_POST['floor_id'];
            $layer_id = $_POST['layer_id'];
            $vacancy_id = $_POST['vacancy_id'];
            $where['single_id'] = $single_id;
            $where['floor_id'] = $floor_id;
            $where['layer_id'] = $layer_id;
            $where['vacancy_id'] = $vacancy_id;
            $where['status'] = 1;
            $where['village_id'] = $village_id;
            $where['type'] = ['in',[0,1,2,3]];
            $where['name'] = ['neq',''];
            $where['phone'] = ['neq',''];
            $where['uid'] = ['gt',0];
            $user_bind = M('House_village_user_bind')->where($where)->field('pigcms_id,uid,name,phone')->find();
            if(!empty($user_bind)){
                echo json_encode(['status'=>1,'data'=>$user_bind]);exit;
            }else{
                echo json_encode(array('status'=>0,'data'=>''));exit;
            }
        }
    }
    //根据电话获取业主信息
    public function ajax_get_user_info_address()
    {
        if(IS_AJAX){
            $village_id = $this->house_session['village_id'];
            $phone = $_POST['owner_phone'];
            $where['phone'] = $phone;
            $where['status'] = 1;
            $where['village_id'] = $village_id;
            $where['type'] = ['in',[0,1,2,3]];
//            $map['single_id'] = ['gt',0];
//            $map['floor_id'] = ['gt',0];
//            $map['layer_id'] = ['gt',0];
//            $map['layer_id'] = ['gt',0];
//            $map['vacancy_id'] = ['gt',0];
            $user_bind = M('House_village_user_bind')->where($where)->field('pigcms_id,uid,name,phone,single_id,floor_id,layer_id,vacancy_id')->find();

            $single = M('House_village_single')->field('single_name')->where(array('id'=>$user_bind['single_id']))->find();
            if($single){
                $user_bind['single_text'] = $single['single_name'];
            }
            $floor = M('House_village_floor')->field('floor_name')->where(array('floor_id'=>$user_bind['floor_id']))->find();
            if($floor) {
                $user_bind['floor_text'] = $floor['floor_name'];
            }
            $layer_info = M('House_village_layer')->field('layer_name')->where(array('id'=>$user_bind['layer_id']))->find();
            if($layer_info) {
                $user_bind['layer_text'] = $layer_info['layer_name'];
            }
            $vacancy = M('House_village_user_vacancy')->field('room')->where(array('pigcms_id'=>$user_bind['vacancy_id']))->find();
            if($vacancy) {
                $user_bind['room_text'] = $vacancy['room'];
            }
            if(!empty($user_bind)){
                echo json_encode(['status'=>1,'data'=>$user_bind]);exit;
            }else{
                echo json_encode(array('status'=>0,'data'=>''));exit;
            }
        }
    }
    public function visitor_del(){
        //访客登记-删除 权限
        if (!in_array(218, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $id =$_GET['id'] + 0;
        if(!$id){
            $this->error('传递参数有误！');
        }
        $database_house_village_visitor = D('House_village_visitor');
        $where = [];
        $where['id'] = $id;
        $result = $database_house_village_visitor->house_village_visitor_del($where);
        if(!$result){
            $this->error('数据处理有误！');
        }else{
            if($result['status']){
                $this->success($result['msg']);
            }else{
                $this->error($result['msg']);
            }
        }
    }

    public function chk_visitor_info(){
        //访客登记-放行 权限
        if (!in_array(217, $this->house_session['menus'])) {
           exit(json_encode(array('status'=>0,'msg'=>'对不起，您没有权限执行此操作')));
        }
        $id = $_POST['id'] + 0;
        $status = $_POST['status'] + 0;
        if(!$id){
            $this->error('传递参数有误！');
        }
        $database_house_village_visitor = D('House_village_visitor');
        $where['id'] = $id;
        $data['status'] = $status;
        $result = $database_house_village_visitor->house_village_visitor_edit($where,$data);
        if(!$result){
            exit(json_encode(array('status'=>0,'msg'=>'数据处理有误！')));
        }else{
            exit(json_encode(array('status'=>$result['status'],'msg'=>$result['msg'])));
        }
    }

    public function visitor_detail(){
        $id = $_GET['id'] + 0;

        if(!$id){
            $this->error('传递参数有误！');
        }
        $village_id = $this->house_session['village_id'];
        $database_house_village_visitor = D('House_village_visitor');
        $where['id'] = $id;
        $detail = $database_house_village_visitor->house_village_visitor_detail($where);
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        if(!$detail['status']){
            $this->error('该信息不存在！');
        }
        if($detail['detail'] && $detail['detail']['owner_phone']){
            $detail['detail']['owner_phone']=$d_house_village->phone_desensitization_v($detail['detail']['owner_phone'],$village_id,$village_info);
        }
        if($detail['detail'] && $detail['detail']['visitor_phone']){
            $detail['detail']['visitor_phone']=$d_house_village->phone_desensitization_v($detail['detail']['visitor_phone'],$village_id,$village_info);
        }
        $this->assign('detail',$detail['detail']);
        $house_village_visitor_pass=M('House_village_visitor_pass');
        $wherePass=array('visitor_id'=>$id);
        $passlog=$house_village_visitor_pass->where($wherePass)->field('*')->order('addtime desc')->select();
        $this->assign('passlog',$passlog);
        $this->display();
    }


    public function visitor_search(){
        $village_id = $this->house_session['village_id'];
        $has_visitor = $this->getHasConfig($village_id, 'has_visitor');

        //访客登记-查看 权限
        if (!in_array(216, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if($has_visitor){
            $database_house_village_visitor = D('House_village_visitor');
            if(IS_POST){
                $visitor_keyword = $_POST['visitor_keyword'];
                $owner_keyword = $_POST['owner_keyword'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $visitor_type = $_POST['visitor_type'];
                $where=array();
                $where['village_id']=$village_id;
                if($visitor_keyword){
                    $where['visitor_name|visitor_phone'] = array('like','%'.$visitor_keyword.'%');
                }

                if($owner_keyword){
                    $where['owner_name|owner_phone'] = array('like','%'.$owner_keyword.'%');
                }

                if($start_time && $end_time){
                    $start_time = strtotime($start_time);
                    $end_time = strtotime($end_time.'23:59:59');
                    $where['add_time'] = array('between',array($start_time,$end_time));
                }else if($start_time){
                    $start_time = strtotime($start_time);
                    $where['add_time'] = array('egt',$start_time);
                }else if($end_time){
                    $end_time = strtotime($end_time.'23:59:59');
                    $where['add_time'] = array('lt',$end_time);
                }

                if($visitor_type==='0'||$visitor_type===0){
                    $where['visitor_type'] = 0;
                }else if($visitor_type>0){
                    $where['visitor_type'] = $visitor_type;
                }
                $result = $database_house_village_visitor->ajax_house_village_visitor_search($where,1,50);
                if($result['list'] && isset($result['list']['list'])){
                    $result['list']=$result['list']['list'];
                }
                $result['power'] = in_array(218, $this->house_session['menus']) ? true : false;
                exit(json_encode($result));
            }else{
                $this->assign('visitor_type',$database_house_village_visitor->visitor_type);
                $this->display();
            }
        }else{
            $this->error('非法访问！');
        }

    }


    public function ajax_get_owner_info(){
        $database_house_village_user_bind = D('House_village_user_bind');
        $owner_phone = $_POST['owner_phone'];
        $village_id = $this->house_session['village_id'];
        $where['village_id'] = $village_id;
        $where['phone'] = $owner_phone;
        $where['parent_id'] = 0;
        $where['status'] = 1;
        $where['type'] = array('in', array(0, 3));

        $field = array('pigcms_id' , 'name' , 'address', 'floor_id', 'single_id', 'layer_num', 'room_addrss');
        $result = $database_house_village_user_bind->house_village_user_bind_detail($where , $field);
        if(!$result){
            exit(json_encode('数据处理有误！'));
        }else{
            exit(json_encode($result));
        }
    }


    public function owner_arrival(){
        //物业对账-创建订单 权限
        if (!in_array(35, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        
        $this->display();
    }

    // 添加缴费订单
    public function owner_arrival_add(){
        //物业对账-创建订单 权限
        if (!in_array(35, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){

            $type = $_POST['type'];
//            $bind_where['usernum'] = $_POST['usernum'];
            $bind_where['_string'] = "usernum='{$_POST['usernum']}' OR bind_number='{$_POST['usernum']}'";
            $database_house_village_user_bind = D('House_village_user_bind');
            $now_bind_info         = $database_house_village_user_bind->where($bind_where)->find();
            $now_village = D('House_village')->get_one($now_bind_info['village_id']);

            if (!$now_bind_info) {
                $this->error('该物业编号不存在！');
            }

            if($type=='property') {
                $database_house_village_property  = D('House_village_property');
                $database_house_village_floor     = D('House_village_floor');


                $now_floor_info = $database_house_village_floor->get_floor_info($now_bind_info['floor_id']);

                $property_where['id'] = $_POST['property_id'] + 0;
                $now_property_info    = $database_house_village_property->house_village_property_detail($property_where);
                $now_property_info    = $now_property_info['detail'];

                if (!$now_property_info) {
                    $this->error('物业缴费周期不存在！');
                }

                $data['order_name']         = '物业费';
                $data['order_type']         = 'property';
                $data['village_id']         = $this->house_session['village_id'];
                $data['time']               = time();
                $data['property_month_num'] = $now_property_info['property_month_num'];
                $data['floor_type_name']    = $now_floor_info['name'] ? $now_floor_info['name'] : '';
                $data['house_size']         = $now_bind_info['housesize'];
                $data['bind_id']            = $now_bind_info['pigcms_id'];
                $data['uid']                = $now_bind_info['uid'];
                $data['diy_type']           = $now_property_info['diy_type'];
                if ($now_property_info['diy_type'] > 0) {
                    $data['diy_content'] = $now_property_info['diy_content'];
                } else {
                    $data['presented_property_month_num'] = $now_property_info['presented_property_month_num'] ? $now_property_info['presented_property_month_num'] : 0;
                }

                if (($now_floor_info['property_fee'] != '0.00') && isset($now_floor_info['property_fee'])) {
                    $data['money']        = $now_floor_info['property_fee'] * $now_bind_info['housesize'] * $now_property_info['property_month_num'];
                    $data['property_fee'] = $now_floor_info['property_fee'];
                } else {
                    $data['money']        = $now_village['property_price'] * $now_bind_info['housesize'] * $now_property_info['property_month_num'];
                    $data['property_fee'] = $now_village['property_price'];
                }
                $data['remarks'] = $_POST['remarks'];
                $order_id = M("House_village_pay_order")->add($data);
                if ($order_id) {
                    $this->success('添加成功', U('owner_arrival_order', array('order_id' => $order_id)));
                } else {
                    $this->error('订单创建失败，请重试');
                }
            }else{

                switch($type){
                    case 'water':
                        if(empty($now_village['water_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳水费');
                        $pay_money = $now_bind_info['water_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_water` AS `use`,`water_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '用水 '.floatval($value['use']).' 立方米，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '水费';
                        break;
                    case 'electric':
                        if(empty($now_village['electric_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳电费');
                        $pay_money = $now_bind_info['electric_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_electric` AS `use`,`electric_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '用电 '.floatval($value['use']).' 千瓦时(度)，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '电费';
                        break;
                    case 'gas':
                        if(empty($now_village['gas_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳燃气费');
                        $pay_money = $now_bind_info['gas_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`use_gas` AS `use`,`gas_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '使用燃气 '.floatval($value['use']).' 立方米，总费用 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '燃气费';
                        break;
                    case 'park':
                        if(empty($now_village['park_price'])) $this->error('当前'.C('config.house_name').'不支持缴纳停车费');
                        $pay_money = $now_bind_info['park_price'];
                        $order_list = D('House_village_user_paylist')->field('`ydate`,`mdate`,`park_price` AS `price`')->where(array('usernum'=>$now_bind_info['usernum']))->order('`pigcms_id` DESC')->select();
                        foreach($order_list as $key=>$value){
                            $order_list[$key]['desc'] = '停车费 '.floatval($value['price']).C('config.Currency_txt').'';
                        }
                        $data_order['order_name'] = '停车费';
                        break;
                    case 'custom_payment':
                        $pay_money = $_POST['payment_price'];
                        $data_order['order_name'] = $_POST['payment_name'];
                        $data_order['payment_paid_cycle'] = $_POST['payment_paid_cycle'];
                        $data_order['payment_bind_id'] = $_POST['payment_bind_id'];
                        break;
                    case 'custom':
                        $pay_money = $_POST['custom_price'];
                        $data_order['order_name'] = '自定义缴费【'.$_POST['custom_remark'].'】';
                        break;
                }
         
                $data_order['money'] = $pay_money ;
                $data_order['uid'] = $now_bind_info['uid'];
                $data_order['bind_id'] = $now_bind_info['pigcms_id'];
                $data_order['village_id'] = $now_village['village_id'];
                $data_order['time'] = $_SERVER['REQUEST_TIME'];
                $data_order['paid'] = 0;
                $data_order['order_type'] = $type;
                $data_order['remarks'] = $_POST['remarks'];

                if($order_id = D('House_village_pay_order')->data($data_order)->add()){
                    $this->success('添加成功', U('owner_arrival_order', array('order_id' => $order_id)));
                }else{
                    $this->error('下单失败，请重试');
                }
            }
        }else{
            $database_house_village_property = D('House_village_property');
            $where['village_id'] = $_SESSION['house']['village_id'];
            $where['status'] = 1;
			
            $list = $database_house_village_property->house_village_proerty_page_list($where , true , 'property_month_num desc' , 99999);

            if(!$list){
                $this->error_tips('数据处理有误！');
            }else{
                if($list['status']){
                    $this->assign('list' , $list['list']);
                }else{
                    $this->error_tips('请先添加缴费优惠。',U('Unit/preferential_add'));
                }
            }
            $this->display();
        }
    }

    protected function check_ajax_error_tips($err_tips,$err_url=''){
        if(I('app_version') && IS_POST){
            if($err_url){
                $this->error_tips($err_tips,$err_url);
            }else{
                $this->error_tips($err_tips);
            }
        }else{
            if(IS_POST){
                $this->header_json();
                echo json_encode(array('err_code'=>-1,'err_msg'=>$err_tips,'err_url'=>$err_url));
                exit();
            }else{
                if($err_url){
                    $this->error_tips($err_tips,$err_url);
                }else{
                    $this->error_tips($err_tips);
                }
            }
        }

    }

    protected function header_json(){
        header('Content-type: application/json');
    }

    public function ajax_user_list(){
        if(IS_AJAX){
            $find_type = $_POST['find_type'];
            $find_value = $_POST['find_value'];

            if ($find_value) {
                if ($find_type == 1) {
                    $where['name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
                } else if ($find_type == 2) {
                    $where['phone'] = array('like', '%' . $find_value . '%');
                } else if ($find_type == 3) {
//                    $where['usernum'] = array('like', '%' . $find_value . '%');
                    $where['_string'] = "usernum LIKE '%{$find_value}%' OR bind_number LIKE '%{$find_value}%'";
                }
            }
            if($_POST['usernum']){
//                $where['usernum'] = $_POST['usernum'];
                $where['_string'] = "usernum='{$find_value}' OR bind_number='{$find_value}'";
            }

            $village_id = $this->village_id;
            if (empty($where)) {
                $user_list = D('House_village_user_bind')->get_limit_list_page($village_id);
            } else {
                $user_list = D('House_village_user_bind')->get_limit_list_page($village_id, 99999, $where);
            }

            $user_list = $user_list['user_list'];


            $cycle_type_china = array(
                    'Y'=>'年',
                    'M'=>'月',
                    'D'=>'日',
                );

            foreach ($user_list as $key => $value) {
                $payment_list = array();
                $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
                C('DB_PREFIX').'house_village_payment_standard'=>'ps',
                C('DB_PREFIX').'house_village_payment'=>'p'))
                ->where("psb.pigcms_id= '".$value['pigcms_id']."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id")->select();
                $payment_list = $payment_list ? $payment_list : array();

                // 车位缴费
                $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$value['pigcms_id']));
                $payment_list = array_merge($payment_list, $position_payment_list);

                $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
                foreach ($payment_list as $kk => $vv) {
                    if ($vv['cycle_sum'] - $vv['paid_cycle'] <= 0) {
                       unset($payment_list[$kk]);
                       continue;
                    }
                    
                    $payment_list[$kk]['start_time'] = date('Y-m-d',$vv['start_time']);

                    // 计算结束时间
                    $vv['end_time'] = strtotime(date('Y-m-d',$vv['start_time']).'+'.$vv['cycle_sum']*$vv['pay_cycle'].$cycle_type[$vv['cycle_type']]);
                    
                    $payment_list[$kk]['end_time'] = date('Y-m-d',$vv['end_time']);
                    $payment_list[$kk]['cycle_type'] = $cycle_type_china[$vv['cycle_type']];
                    if ($vv['garage_num']) {
                        $payment_list[$kk]['payment_name'] = $vv['payment_name'].'('.$vv['garage_num'].'-'.$vv['position_num'].')';
                    }
                }
                
                $user_list[$key]['payment_list'] = $payment_list;

            }

            if($user_list){
                exit(json_encode(array('status'=>1,'user_list'=>$user_list)));
            }else{
                exit(json_encode(array('status'=>0,'user_list'=>$user_list)));
            }
        }else{
            $this->error_tips('访问页面有误！');
        }
    }


    public function owner_arrival_order(){
        //物业对账-创建订单 权限
        if (!in_array(35, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        
        $order_id = $_GET['order_id'] + 0;
        if(!$order_id){
            $this->error('传递参数有误！');
        }

        $database_house_village_pay_order = D('House_village_pay_order');

        $now_order = $database_house_village_pay_order->get_one($order_id);
        if($now_order['paid'] > 0){
            $this->error('该订单已支付。',U('Unit/pay_order'));
        }

        $this->assign('now_order',$now_order);
        $this->assign('pay_name',$this->pay_name());
        $this->display();
    }

    public function pay_name(){
        return array(
            'water'=>'水费',
'electric'=>'电费',
'gas'=>'燃气费',
'park'=>'停车费',

        );
    }

    public function chk_cash(){
        $order_id = $_GET['order_id'] + 0;
        if(!$order_id){
            $this->error('传递参数有误！');
        }

        $database_house_village_pay_order = D('House_village_pay_order');
        $now_order = $database_house_village_pay_order->get_one($order_id);

        if($now_order['paid'] > 0){
            $this->error('该订单已经支付！');
        }

        $data['paid'] = 1;
        $data['pay_time'] = time();
        $data['pay_type'] = 1;
        $result = $database_house_village_pay_order->where(array('order_id'=>$order_id))->data($data)->save();

        if($result){
            $bind_field = $now_order['order_type'].'_price';
            if(!empty($bind_field)){
                $now_user_info = D('House_village_user_bind')->get_one($now_order['village_id'],$now_order['bind_id'],'pigcms_id');

                $data_bind['pigcms_id'] = $now_user_info['pigcms_id'];

                if($now_user_info[$bind_field] - $now_order['money'] >= 0){
                    $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
                }else{
                    $data_bind[$bind_field] = 0;
                }
                $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;

                D('House_village_user_bind')->data($data_bind)->save();

            }
            $database_house_village_property_paylist = D('House_village_property_paylist');

            $paylist_data['bind_id'] = $now_order['bind_id'];
            $paylist_data['uid'] = $now_order['uid'];
            $paylist_data['village_id'] = $now_order['village_id'];
            $paylist_data['property_month_num'] = $now_order['property_month_num'] + 0;
            $paylist_data['presented_property_month_num'] = $now_order['presented_property_month_num'] + 0;
            $paylist_data['house_size'] = $now_order['house_size'];
            $paylist_data['property_fee'] = $now_order['property_fee'];
            $paylist_data['floor_type_name'] = $now_order['floor_type_name'];

            $now_user_info = D('House_village_user_bind')->get_one_by_bindId($now_order['bind_id']);
            $now_pay_info = $database_house_village_property_paylist->where(array('bind_id'=>$now_order['bind_id']))->order('add_time desc')->find();

            
            if($now_user_info['property_endtime']){
                $paylist_data['start_time'] = $now_user_info['property_endtime'];
                $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_user_info['property_endtime']);
            }else{
                if($now_user_info['add_time'] > 0){
                    $paylist_data['start_time'] = $now_user_info['add_time'] ;
                    $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_user_info['add_time']);
                }else{
                    $paylist_data['start_time'] = time();
                    $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", time());
                }

            }
            
            $paylist_data['end_time'] = strtotime(date('Y-m-d 23:59:59',$paylist_data['end_time']));

            $paylist_data['add_time'] = time();
            $paylist_data['order_id'] = $order_id;

            //同步物业到期时间 
            $where['uid'] = $now_order['uid'];
            $where['village_id'] = $now_order['village_id'];
            $where['pigcms_id'] = $now_order['bind_id'];
            M('House_village_user_bind')->where($where)->save(array('property_endtime'=>$paylist_data['end_time']));

            

            $database_house_village_property_paylist->data($paylist_data)->add();


            //修改绑定缴费项目
            if($now_order['order_type'] == 'custom_payment'){
                M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->setInc('paid_cycle',$now_order['payment_paid_cycle']);
            }

            // 小票打印start
            $printHaddle = new PrintVillage();
            $printHaddle->printit($order_id);
            // 小票打印end

            $this->success('提交成功！',U('Unit/pay_order'));
        }else{
            $this->error('提交失败！');
        }
    }

    public function ajax_unit(){
        $database_house_village_floor = D('House_village_floor');
        $condition['village_id'] =  $this->village_id;
        $condition['status'] =  1;

        $unit_list = $database_house_village_floor->field(true)->group('floor_name')->where($condition)->select();
        if(count($unit_list) == 1){
            $return['error'] = 2;
            $return['id'] = $unit_list[0]['id'];
            $return['name'] = $unit_list[0]['floor_name'];
        }else if(!empty($unit_list)){
            $return['error'] = 0;
            $return['list'] = $unit_list;
        }else{
            $return['error'] = 1;
            $return['info'] = '没有已开启的单元！';
        }
        exit(json_encode($return));
    }


    public function ajax_floor(){
        $database_house_village_floor = D('House_village_floor');
        $condition['village_id'] =  $this->village_id;
        $condition['status'] =  1;
        $condition['floor_name'] =  $_POST['name'];
        $floor_list = $database_house_village_floor->where($condition)->select();

        if(count($floor_list) == 1 && !$_POST['type']){
            $return['error'] = 2;
            $return['id'] = $floor_list[0]['id'];
            $return['name'] = $floor_list[0]['name'];
        }else if(!empty($floor_list)){
            $return['error'] = 0;
            $return['list'] = $floor_list;
        }else{
            $return['error'] = 1;
            $return['info'] = $_POST['name'] .' 该单元下未有楼层！';
        }
        exit(json_encode($return));
    }

    public function ajax_layer(){
        $database_house_village_user_bind = D('House_village_user_bind');
        $condition['village_id'] =  $this->village_id;
        $condition['status'] =  1;
        $condition['floor_id'] = $_POST['id'] + 0;
        $condition['parent_id'] = 0;

        $layer_list = $database_house_village_user_bind->where($condition)->select();
        if(count($layer_list) == 1 && !$_POST['type']){
            $return['error'] = 2;
            $return['id'] = $layer_list[0]['id'];
            $return['name'] = $layer_list[0]['name'];
        }else if(!empty($layer_list)){
            $return['error'] = 0;
            $return['list'] = $layer_list;
        }else{
            $return['error'] = 1;
            $return['info'] = '未有业主';
        }
        exit(json_encode($return));
    }

    public function ajax_owner(){
        $database_house_village_user_bind = D('House_village_user_bind');
        $condition['village_id'] =  $this->village_id;
        $condition['pigcms_id'] = $_POST['id'] + 0;
        $condition['parent_id'] = 0;

        $owner_list = $database_house_village_user_bind->where($condition)->select();
        if(count($owner_list) == 1 && !$_POST['type']){
            $return['error'] = 2;
            $return['id'] = $owner_list[0]['id'];
            $return['name'] = $owner_list[0]['name'];
        }else if(!empty($owner_list)){
            $return['error'] = 0;
            $return['list'] = $owner_list;
        }else{
            $return['error'] = 1;
            $return['info'] = '未有业主';
        }
        exit(json_encode($return));
    }

    public function search_owner_info(){
        $pigcms_id = $_GET['owner_id'] + 0;

        if(!$pigcms_id){
            $this->error_tips('传递参数有误！');
        }

        $condition['pigcms_id'] = $pigcms_id;
        $database_house_village_user_bind = D('House_village_user_bind');
        $database_house_village_property_paylist = D('House_village_property_paylist');
        $now_bind_user = $database_house_village_user_bind->get_one($this->village_id,$pigcms_id,'pigcms_id');

        $condition_pay['bind_id'] = $pigcms_id;
        $now_bind_user['expire_time'] = $database_house_village_property_paylist->where($condition_pay)->order('add_time desc')->getField('end_time');
        if(!$now_bind_user){
            $this->error_tips('该业主不存在！');
        }else{
            $this->assign('now_bind_user' , $now_bind_user);
            $this->display();
        }
    }

    public function search_owner_pay_list(){
        $pigcms_id = $_GET['pigcms_id'];
        $where['bind_id'] = $pigcms_id;
        $where['village_id'] = $this->village_id;

        $database_house_village_property_paylist = D('House_village_property_paylist');
        $list = $database_house_village_property_paylist->where($where)->order('add_time asc')->select();
        $this->assign('list' , $list);
        $this->display();
    }


    public function ajax_property_info(){
        if(IS_AJAX){
            $property_id = $_POST['property_id'] + 0;

            if(!$property_id){
                exit(json_encode(array('status'=>0,'msg'=>'传递参数有误！')));
            }

            $database_house_village_property = D('House_village_property');

            $where['id'] = $property_id;
            $where['village_id'] = $this->village_id;
            $where['status'] = 1;
            $detail = $database_house_village_property->house_village_property_detail($where);

            if(!$detail){
                exit(json_encode(array('status'=>0,'msg'=>'数据处理有误！')));
            }

            if($detail['status']){
                exit(json_encode(array('status'=>1,'detail'=>$detail['detail'])));
            }else{
                exit(json_encode(array('status'=>0,'detail'=>$detail['detail'])));
            }
        }else{
            $this->error_tips('访问页面有误！');
        }
    }


    private function getHasConfig($village_id,$field){
        $database_house_village = D('House_village');
        $house_village_info = $database_house_village->get_one($village_id,$field);
        $config_info = $house_village_info[$field];
        return $config_info;
    }

    //代送配置
    public function express_config(){
        $village_id = $this->house_session['village_id'];
        $has_express_service = $this->getHasConfig($village_id, 'has_express_service');

        if($has_express_service){
            $mode = D('House_village_express_config');
            if(IS_POST){
                //代送配置-编辑 权限
                if (!in_array(208, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                $_POST['start_time'] = strtotime($_POST['start_time']);
                $_POST['end_time'] = strtotime($_POST['end_time']);
                if($_POST['start_time']>=$_POST['end_time']){
                    $this->error('起送时间不能比结束时间大');
                }
                if(!is_numeric($_POST['notice_phone'])){
                    $this->error('请输入正确手机号！');
                }
                if(!$mode->where(array('village_id'=>$village_id))->find()) {
                    $_POST['village_id'] = $village_id;
                    $result = $mode->add($_POST);
                }else {
                    $result = $mode->where(array('village_id'=>$village_id))->save($_POST);
                }

                if(!$result){
                    $this->error('数据处理有误！');
                }else{

                    $this->success('保存成功');

                }
            }else{
                $express_config = $mode->where(array('village_id'=>$village_id))->find();
                $this->assign('express_config',$express_config);
                $this->display();
            }
        }else{
            $this->error('非法访问！');
        }
    }
    public function index_nav_pop(){

        $village_id = $this->house_session['village_id'];
        $database_house_village_nav = D('House_village_nav');

        //todo 功能导航
        $assign_type=D('House_visualize_assign')->house_visualize_assign($this->house_session['village_id'],14);
        $where=array('type'=>20,'block_type_id'=>'house_wap_index_pop');
        $key_name=isset($_GET['key_name']) ? trim($_GET['key_name']):'';
        $where['_string'] =' (village_id = '.$village_id.') ';
        if($key_name){
            $where['_string']=$where['_string']." AND name LIKE '%{$key_name}%'";
        }
        $status=isset($_GET['status']) ? intval($_GET['status']):0;
        $status=$status-1;
        if($status>=0){
            $where['status'] = array('eq',$status);
        }else{
            $where['status'] = array('neq',4);
        }
        $start_time=isset($_GET['start_time']) ? trim($_GET['start_time']):'';
        $start_time=$start_time ? strtotime($start_time):0;
        if($start_time>0){
            $where['start_time']=array('egt',$start_time);
        }
        $end_time=isset($_GET['end_time']) ? trim($_GET['end_time']):'';
        $end_time=$end_time ? strtotime($end_time):0;
        if($end_time>0){
            $where['end_time']=array('elt',$end_time);
        }
        if(!$assign_type['type_14']){
            $where['_string'] .= ' or (village_id =0 and (filter_village_ids is null OR  !FIND_IN_SET('.$village_id.', filter_village_ids )))';
        }
        
        $result = $database_house_village_nav->house_village_nav_page_list($where , true , 'status DESC,id desc');
        if(!$result){
            $this->error('数据处理有误！');
        }
        $this->assign('result',$result['result']);
        $this->assign('village_id',$village_id);
        $this->assign('assign_type',$assign_type);
        $this->display();
    }
    public function nav_add_pop(){
        $idd=isset($_GET['id']) ? intval($_GET['id']):0;
        $database_house_village_nav = D('House_village_nav');
        $village_id = $this->house_session['village_id'];
        $detail=array();
        if($idd>0){
            $whereArr=array('id'=>$idd,'village_id'=>$village_id,'type'=>20,'block_type_id'=>'house_wap_index_pop');
            $editData=$database_house_village_nav->house_village_nav_detail($whereArr,'*');
            if(empty($editData['detail'])){
                $this->error('编辑的数据不存在！');
            }
            $detail=$editData['detail'];
            if(substr($detail['img'],1,6) == 'static'){
                $detail['img'] = C('config.site_url').$detail['img'];
            }elseif(substr($detail['img'],'0','4') == '000/'){
                $detail['img']= file_domain().'/upload/adver/' . $detail['img'];
            }else{
                $detail['img']= file_domain().'/upload/service/'.$detail['img'];
            }
            $detail['start_time']=$detail['start_time']>100 ? date('Y-m-d',$detail['start_time']):'';
            $detail['end_time']=$detail['end_time']>100 ? date('Y-m-d',$detail['end_time']):'';
        }
        $this->assign('detail',$detail);
        $this->assign('idd',$idd);
        $this->display();
    }
    public function nav_del_pop(){
        $idd=isset($_POST['id']) ? intval($_POST['id']):0;
        $village_id = $this->house_session['village_id'];
        if($idd>0){
            $database_house_village_nav = M('House_village_nav');
            $whereArr=array('id'=>$idd,'village_id'=>$village_id,'type'=>20,'block_type_id'=>'house_wap_index_pop');
            $delArr=array('status'=>4,'del_time'=>time());
            $database_house_village_nav->where($whereArr)->data($delArr)->save();
            $this->success('删除成功！');

        }
        $this->error('删除失败！请重试~！');
    }
    public function nav_amend_pop(){
        $_POST['block_type_id']='house_wap_index_pop';
        $_POST['type']=20;  //定制的 看 block_type_id字段来区分

        if( $_FILES['img']['error'] != 4){
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
            } else {
                $this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
            }
        }
        $village_id = $this->house_session['village_id'];
        $database_house_village_nav = D('House_village_nav');
        $idd = isset($_POST['id'])  ? $_POST['id'] :0;
        $start_time=isset($_POST['start_time']) ? strtotime($_POST['start_time']):0;
        $_POST['start_time']=$start_time;
        $end_time=isset($_POST['end_time']) ? strtotime($_POST['end_time']):0;
        $_POST['end_time']=$end_time>0 ? ($end_time+86399):0;
        unset($_POST['id']);
        $where=array();
        if($idd>0){
            $where['id']=$idd;
            $where['village_id']=$village_id;
            $where['block_type_id']='house_wap_index_pop';
            $where['type']=20;
        }
        $result  = $database_house_village_nav->house_village_nav_save($_POST,$village_id,$where);
        if(!$result){
            $this->frame_error_tips('数据处理有误！');
        }
        if($result['status']){
            exit('<html><head><script>window.top.msg(1,"保存成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
        }else{
            exit('<html><head><script>window.top.msg(0,"保存成功！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
        }
    }
    public function index_nav(){
        //首页自定义导航-查看 权限
        if (!in_array(239, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_nav = D('House_village_nav');

        $where['village_id'] = $this->house_session['village_id'];
        $where['type'] = 0;
        $result = $database_house_village_nav->house_village_nav_page_list($where , true , 'sort desc');

        if(!$result){
            $this->error('数据处理有误！');
        }

        $this->assign('result',$result['result']);
        $this->display();
    }

    public function nav_add(){
        //首页自定义导航-添加 权限
        if (!in_array(240, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            $_POST['add_time'] = time();
            $_POST['village_id'] = $this->house_session['village_id'];
            $database_house_village_nav = D('House_village_nav');
            $result = $database_house_village_nav->house_village_nav_add($_POST);

            if(!$result){
                $this->error('数据处理有误！');
            }

            if($result['status']){
                $this->success($result['msg']);
            }else{
                $this->error($result['msg']);
            }
        }else{
            $this->display();
        }
    }

    public function nav_edit(){
        $database_house_village_nav = D('House_village_nav');
        $where['id'] = $_GET['id'] + 0;

        if(IS_POST){
            //首页自定义导航编辑 权限
            if (!in_array(241, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $result  = $database_house_village_nav->house_village_nav_edit($where,$_POST);
            if(!$result){
                $this->error('信息处理有误！');
            }

            if($result['status']){
                $this->success($result['msg']);
            }else{
                $this->error($result['msg']);
            }
        }else{
            //首页自定义导航-查看 权限
            if (!in_array(239, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $result = $database_house_village_nav->house_village_nav_detail($where);
            if(!$result){
                $this->error('数据处理有误！');
            }

            $this->assign('detail',$result['detail']);
            $this->display();
        }
    }


    public function nav_del(){
        //首页自定义导航-删除 权限
        if (!in_array(242, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_nav = D('House_village_nav');
        $where['id'] = $_GET['id'] + 0;

        $result = $database_house_village_nav->house_village_nav_del($where);

        if(!$result){
            $this->error('数据处理有误！');
        }

        if($result['status']){
            $this->success($result['msg']);
        }else{
            $this->error($result['msg']);
        }
    }


    public function index_nav_new()
    {
        $village_id = $this->house_session['village_id'];
        $database_house_village_nav = D('House_village_nav');

        $type = 0;
        $show_nav_count=20;
        $type_assign = 2;
        // 单行功能导航
        $row_type = isset($_GET['row_type']) ? remove_xss($_GET['row_type']) : '';
        if($row_type == 'single'){
            $type = 1;
            $type_assign = 11;
        }
        if($row_type == 'middle'){
            $type = 2;
            $type_assign = 13;
            $show_nav_count=10;
        }
        //todo 功能导航
        $assign_type=D('House_visualize_assign')->house_visualize_assign($this->house_session['village_id'],$type_assign);
        $where['_string'] =' (village_id = '.$village_id.')';
        if(!$assign_type['type_0']){
            $where['_string'] .= '  or ((village_id =0) and (filter_village_ids is null OR  !FIND_IN_SET('.$village_id.', filter_village_ids )))';
        }
        $where['status'] = array('neq',4);

        $this->assign('row_type',$row_type);

        $where['type'] = $type;
        $result = $database_house_village_nav->house_village_nav_page_list($where , true , 'status DESC,sort desc');

        if(!$result){
            $this->error('数据处理有误！');
        }
         $now_village = D('House_village')->where(array('village_id'=>$this->house_session['village_id']))->find();

        $where_field = "(";
        if($now_village){
            if($now_village['province_id']){
                $where_field .= " province_id =".$now_village['province_id'];
            }
            if($now_village['city_id']){

                $where_field .= " AND city_id =".$now_village['city_id'];
            }
            if($now_village['area_id']){

                $where_field .= " AND area_id =".$now_village['area_id'];
            }
            if($now_village['street_id']){

                $where_field .= " AND street_id =".$now_village['street_id'];
            }
            if($now_village['community_id']){

                $where_field .= " AND community_id =".$now_village['community_id'];
            }
        }

        $where_field .= " AND status=1 AND type=".$type.") OR ( province_id =0 AND city_id = 0 AND area_id=0 AND street_id=0 AND community_id=0  AND village_id=0 AND status<>4 AND type=".$type.")";
        $adver_count = $database_house_village_nav->field(true)->where($where_field)->count();
        if($adver_count > 0){
            $this->assign('adver_count',$adver_count);
        }else{
            $this->assign('adver_count',0);
        }
        $village_nav = D('House_village_info')->where(array('village_id'=>$this->house_session['village_id']))->find();
        $this->assign('show_nav_count',$show_nav_count);
        $this->assign('village_nav',$village_nav);
        $this->assign('assign_type',$assign_type);
        $this->assign('result',$result['result']);
        $this->assign('village_id',$village_id);
        $this->display();
    }
    public function village_express_delivery(){
        $block_type_id = isset($_GET['block_type_id']) ? remove_xss($_GET['block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 0;
        $is_community_platform=0;
        $parent_block_type_id = isset($_GET['parent_block_type_id']) ? remove_xss($_GET['parent_block_type_id']) : '';
        $get_village_id= isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$get_village_id;
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $whereArr=array('block_type_id'=>$block_type_id,'type'=>50);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=1;
        }
        $is_assign= $house_village_nav_moduleDb->house_visualize_assign($village_id,$block_type_id);
        $whereArr['status'] = array('neq',4);
        $database_house_village_nav = D('House_village_nav');
        if($is_assign){
            $whereArr['_string'] = '(village_id='.$village_id.' or (village_id =0 AND is_community_platform=1))';
        }else{
            $whereArr['village_id']=$village_id;
        }
        $result = $database_house_village_nav->house_village_nav_page_list($whereArr , true , 'status DESC,sort desc');
        $house_visualize_switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        $house_visualize_switch=$house_visualize_switch ? intval($house_visualize_switch):0;
        
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=$is_community_platform;
        }
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        if($is_assign){
            $whereArr['village_id']=0;
            $whereArr['is_community_platform']=1;
        }
        if($block_type_id=='business_express_delivery'){
            $whereArr['block_type_id']='business_small_shop';
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if($nav_set){
            /*
            if(isset($nav_set['data_extra']) && $nav_set['data_extra']){
                $data_extra=json_decode($nav_set['data_extra'],true);
                if($data_extra && isset($data_extra['product_info'])){
                    $nav_set['product_info']=$data_extra['product_info'];
                    $nav_set['product_num']=count($nav_set['product_info']);
                }
            }
            */
        }else{
            $nav_set=array();
        }
        if($block_type_id=='business_express_delivery'){
            if(empty($nav_set)){
                $nav_set['fillet_radius']=0;
                $nav_set['xsort']=0;
            }
            $whereTmpArr=array('parent_block_type_id'=>'business_express_delivery','village_id'=>$village_id);
            if($is_community_platform>0){
                $whereTmpArr['is_community_platform']=$is_community_platform;
            }
            if($is_assign){
                $whereTmpArr['village_id']=0;
                $whereTmpArr['is_community_platform']=1;
            }
            $whereTmpArr['block_top_color']=array('neq','');
            $navTmpSet=$house_village_nav_moduleDb->getOneInfo($whereTmpArr);
            if($navTmpSet){
                $nav_set['fillet_radius']=$navTmpSet['fillet_radius'];
                $nav_set['block_top_color']=$navTmpSet['block_top_color'];
                $nav_set['block_bottom_color']=$navTmpSet['block_bottom_color'];
            }

            $list = D('Common')->businessProductList('shop');
            $nav_set['product_info']=!empty($list) && $list['list']? $list['list']:array();
        }
        if($block_type_id=='village_street_community'){
            $database_house_village_nav = D('House_village_nav');
            $whereArr=array('block_type_id'=>'village_street_community','type'=>50,'village_id'=>$village_id,'is_community_platform'=>$is_community_platform);
            if($is_assign){
                $whereArr['village_id']=0;
                $whereArr['is_community_platform']=1;
            }
            $nav_info_village=$database_house_village_nav->where($whereArr)->find();
            $nav_set['village_nav']=$nav_info_village;
        }
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list',$wxapp_list);
        $this->assign('nav_set',$nav_set);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('village_id',$village_id);
        $this->assign('is_community_platform',$is_community_platform);
        $this->assign('house_visualize_switch',$house_visualize_switch);
        $this->assign('result',$result['result']);
        $this->assign('house_visualize_assign',$is_assign); 
        $this->display();
    }
    public function  get_business_nav_set_info(){
        $sub_block_type_id = isset($_POST['sub_block_type_id']) ? remove_xss($_POST['sub_block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 0;
        $is_community_platform=0;
        $get_village_id= isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $parent_block_type_id = isset($_POST['parent_block_type_id']) ? remove_xss($_POST['parent_block_type_id']) : '';
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$get_village_id;
        if($is_community_platform>0){
            $village_id=0;
        }
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $is_assign= $house_village_nav_moduleDb->house_visualize_assign($village_id,$sub_block_type_id);
        
        $whereArr=array('block_type_id'=>$sub_block_type_id,'village_id'=>$village_id);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=$is_community_platform;
        }
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        if($is_assign){
            $whereArr['village_id']=0;
            $whereArr['is_community_platform']=1;
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if($nav_set){
            /*
            if(isset($nav_set['data_extra']) && $nav_set['data_extra']){
                $data_extra=json_decode($nav_set['data_extra'],true);
                if($data_extra && isset($data_extra['product_info'])){
                    $nav_set['product_info']=$data_extra['product_info'];
                    $nav_set['product_num']=count($nav_set['product_info']);
                }
            }
            */
            unset($nav_set['data_extra']);
        }else{
            $nav_set=array('block_type_id'=>$sub_block_type_id,'village_id'=>$village_id,'is_show'=>0,'title'=>'','xsort'=>0,'distance'=>0);
        }
        $business_type='shop';
        switch ($sub_block_type_id){
            case 'business_small_shop':
                $business_type='shop';
                break;
            case 'business_new_mall':
                $business_type='mall';
                break;
            case 'business_community_group':
                $business_type='group';
                break;
            case 'business_service_reservation':
                $business_type='appoint';
                break;
            default:
                $business_type='shop';
                break;
        }
        $list = D('Common')->businessProductList($business_type);
        $nav_set['product_info']=!empty($list) && $list['list']? $list['list']:array();
        
        $jsonArr=array('err_code'=>0,'data'=>$nav_set,'msg'=>'');
        echo json_encode($jsonArr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit();
    }
    public function business_product_list(){
        $xtype=isset($_GET['xtype']) ? trim($_GET['xtype']) : ''; //要获取的商品类型
        /**business_small_shop 快店***business_new_mall 新版商城**business_community_group 社区团购***business_service_reservation 预约服务*******/
        $business_type='shop';
        switch ($xtype){
            case 'business_small_shop':
                $business_type='shop';
                break;
            case 'business_new_mall':
                $business_type='mall';
                break;
            case 'business_community_group':
                $business_type='group';
                break;
            case 'business_service_reservation':
                $business_type='appoint';
                break;
            default:
                $business_type='shop';
                break;
        }
        $list = D('Common')->businessProductList($business_type);
        $datalist=$list['list'];
        $page=$list['pagebar'];
        $this->assign('list',$datalist);
        $this->assign('page', $page);
        $this->display();
    }
    public function  nav_add_guess_love(){

        $block_type_id='';
        $is_community_platform=0;
        if(isset($_GET['block_type_id'])){
            $block_type_id=trim($_GET['block_type_id']);
        }
        if(isset($_GET['is_community_platform'])){
            $is_community_platform=intval($_GET['is_community_platform']);
        }
        $classify_category_list=array();
        if($block_type_id=='business_classify_info'){
            $database_classify_category = D('Classify_category');
            $condition=array();
            $condition['cid'] = array('gt',0);
            $condition['fcid'] = 0;
            $condition['cat_status']=1;
            $classify_category_list = $database_classify_category->field('cid,cat_name,cat_url')->where($condition)->order('`cat_sort` DESC,`cid` ASC')->select();
        }
        if($block_type_id=='business_store_info'){
            $database_shop_category = D('Shop_category');
            $condition=array();
            $condition['cat_id'] = array('gt',0);
            $condition['cat_fid'] = 0;
            $condition['cat_status']=1;
            $classify_category_list = $database_shop_category->field('cat_id as cid,cat_name,cat_url')->where($condition)->order('`cat_sort` DESC,`cat_id` ASC')->select();
            $all_shop_category=array('cid'=>0,'cat_name'=>'全部分类','cat_url'=>'all');
            if($classify_category_list){
                array_unshift($classify_category_list, $all_shop_category);
            }else{
                $classify_category_list=$all_shop_category;
            }
        }
        
        $detail=array();
        if(isset($_GET['id']) && $_GET['id']){
            $idd=intval($_GET['id']);
            $database_house_village_nav = D('House_village_nav');
            $where= array('id'=>$idd);
            $result = $database_house_village_nav->house_village_nav_detail($where);
            if(!$result){
                $this->frame_error_tips('数据处理有误！');
            }
            if($result['detail']['img']){

                if(substr($result['detail']['img'],1,6) == 'static'){
                    $result['detail']['img'] = C('config.site_url').$result['detail']['img'];
                }elseif(substr($result['detail']['img'],'0','4') == '000/'){
                    $result['detail']['img']= file_domain().'/upload/adver/' . $result['detail']['img'];
                }else{
                    $result['detail']['img']= file_domain().'/upload/service/'.$result['detail']['img'];
                }
            }
            $detail=$result['detail'];
            unset($result);
        }
        if($block_type_id=='business_store_info' && empty($detail)){
            $detail['business_num']=10;
        }
        $this->assign('classify_category_list',$classify_category_list);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('is_community_platform',$is_community_platform);
        $this->assign('detail',$detail);
        $this->display();
    }
    public function index_nav_guess_love(){
        $block_type_id = isset($_GET['block_type_id']) ? remove_xss($_GET['block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 0;
        $is_community_platform=0;
        $parent_block_type_id = isset($_GET['parent_block_type_id']) ? remove_xss($_GET['parent_block_type_id']) : '';
        $block_type_id=trim($block_type_id);
        $get_village_id= isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$get_village_id;
        if($is_community_platform>0){
            $village_id=0;
        }
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $block_type_arr=$house_village_nav_moduleDb->block_type_arr;
        $block_type_str='';
        if($block_type_id && isset($block_type_arr[$block_type_id])){
            $block_type_str=$block_type_arr[$block_type_id];
        }
        $is_assign= $house_village_nav_moduleDb->house_visualize_assign($village_id,$block_type_id);
        $whereArr=array('block_type_id'=>$block_type_id,'type'=>50);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=1;
        }
        if($is_assign){
            $whereArr['_string'] = '(village_id='.$village_id.' or (village_id =0 AND is_community_platform=1))';
        }else{
            $whereArr['village_id']=$village_id;
        }
        $whereArr['status'] = array('neq',4);
        $database_house_village_nav = D('House_village_nav');
        $result = $database_house_village_nav->house_village_nav_page_list($whereArr , true , 'status DESC,sort desc');
        if($block_type_id=='business_classify_info' && $result['result'] && $result['result']['list']){
            $database_classify_category = D('Classify_category');
            foreach ($result['result']['list'] as $kk=>$vv){
                $result['result']['list'][$kk]['classify_name']='';
                $condition=array();
                $condition['cid'] = $vv['business_cat_id'];
                $classify_category = $database_classify_category->field('cid,cat_name,cat_url')->where($condition)->find();
                if($classify_category){
                    $result['result']['list'][$kk]['classify_name']=$classify_category['cat_name'];
                }
            }
        }else if($block_type_id=='business_store_info' && $result['result'] && $result['result']['list']){
            $database_shop_category = D('Shop_category');
            foreach ($result['result']['list'] as $kk=>$vv){
                $result['result']['list'][$kk]['classify_name']='';
                $condition=array();
                $condition['cat_id'] = $vv['business_cat_id'];
                $classify_category = $database_shop_category->field('cat_id as cid,cat_name,cat_url')->where($condition)->find();
                if($classify_category){
                    $result['result']['list'][$kk]['classify_name']=$classify_category['cat_name'];
                }
            }
        }
        $house_visualize_switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        $house_visualize_switch=$house_visualize_switch ? intval($house_visualize_switch):0;
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=$is_community_platform;
        }
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        if($is_assign){
            $whereArr['village_id']=0;
            $whereArr['is_community_platform']=1;
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        $nav_set=$nav_set ? $nav_set:array();
        $this->assign('nav_set',$nav_set);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('village_id',$village_id);
        $this->assign('is_community_platform',$is_community_platform);
        $this->assign('block_type_str',$block_type_str);
        $this->assign('result',$result['result']);
        $this->assign('house_visualize_switch',$house_visualize_switch);
        $this->assign('house_visualize_assign',$is_assign);
        $getparam=$_GET;
        $this->assign('getparam',$getparam);
        $this->display();
    }
    /*****
      新版定制
     *****/
    public function index_nav_2new(){
        $block_type_id = isset($_GET['block_type_id']) ? remove_xss($_GET['block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 0;
        $is_community_platform=0;
        $parent_block_type_id = isset($_GET['parent_block_type_id']) ? remove_xss($_GET['parent_block_type_id']) : '';
        $block_type_id=trim($block_type_id);
        $get_village_id= isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$get_village_id;
        if($is_community_platform>0){
            $village_id=0;
        }
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $is_assign= $house_village_nav_moduleDb->house_visualize_assign($village_id,$block_type_id);
        $block_type_arr=$house_village_nav_moduleDb->block_type_arr;
        $block_type_str='';
        if($block_type_id && isset($block_type_arr[$block_type_id])){
            $block_type_str=$block_type_arr[$block_type_id];
        }
        
        $whereArr=array('block_type_id'=>$block_type_id,'type'=>50);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=1;
        }
        if($is_assign){
            $whereArr['_string'] = '(village_id='.$village_id.' or (village_id =0 AND is_community_platform=1))';
        }else{
            $whereArr['village_id']=$village_id;
        }
        $whereArr['status'] = array('neq',4);
        $database_house_village_nav = D('House_village_nav');
        $result = $database_house_village_nav->house_village_nav_page_list($whereArr , true , 'status DESC,sort desc');
        $house_visualize_switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        $house_visualize_switch=$house_visualize_switch ? intval($house_visualize_switch):0;
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=$is_community_platform;
        }
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        if($is_assign){
            $whereArr['village_id']=0;
            $whereArr['is_community_platform']=1;
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if($nav_set){

        }else{
            $nav_set=array();
        }
        $this->assign('house_visualize_assign',$is_assign);
        $this->assign('nav_set',$nav_set);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('village_id',$village_id);
        $this->assign('is_community_platform',$is_community_platform);
        $this->assign('block_type_str',$block_type_str);
        $this->assign('result',$result['result']);
        $this->assign('house_visualize_switch',$house_visualize_switch);
        $getparam=$_GET;
        $this->assign('getparam',$getparam);
        $this->display();
    }
    /**
    * 直播设置
     **/
    public function  index_nav_live(){
        $block_type_id = isset($_GET['block_type_id']) ? remove_xss($_GET['block_type_id']) : '';
        $is_community_platform = isset($_GET['is_community_platform']) ? intval($_GET['is_community_platform']) : 0;
        $is_community_platform=0;
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $block_type_id=trim($block_type_id);
        $get_village_id= isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$get_village_id;
        if($is_community_platform>0){
            $village_id=0;
        }
        $is_assign= $house_village_nav_moduleDb->house_visualize_assign($village_id,$block_type_id);
        
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        if($is_community_platform>0){
            $whereArr['is_community_platform']=$is_community_platform;
        }
        if($is_assign){
            $whereArr['village_id']=0;
            $whereArr['is_community_platform']=1;
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        $nav_set=$nav_set ? $nav_set:array();
        $this->assign('nav_set',$nav_set);
        $this->assign('block_type_id',$block_type_id);
        $this->assign('village_id',$village_id);
        $this->assign('house_visualize_assign',$is_assign);
        $this->display();
    }
    public function save_village_nav_set(){
        $block_type_id = isset($_POST['block_type_id']) ? remove_xss($_POST['block_type_id']) : '';
        $sub_block_type_id = isset($_POST['sub_block_type_id']) ? remove_xss($_POST['sub_block_type_id']) : '';
        $parent_block_type_id = isset($_POST['parent_block_type_id']) ? remove_xss($_POST['parent_block_type_id']) : '';
        $title = isset($_POST['title']) ? remove_xss($_POST['title']) : '';
        $sub_title = isset($_POST['sub_title']) ? remove_xss($_POST['sub_title']) : '';
        $label_title = isset($_POST['label_title']) ? remove_xss($_POST['label_title']) : '';
        $label_bg_color = isset($_POST['label_bg_color']) ? trim($_POST['label_bg_color']) : '';
        $show_community_platform = isset($_POST['show_community_platform']) ? remove_xss($_POST['show_community_platform']) : 0;
        $is_community_platform = isset($_POST['is_community_platform']) ? intval($_POST['is_community_platform']) : 0;
        $post_village_id = isset($_POST['village_id']) ? intval($_POST['village_id']) : 0;
        $is_show = isset($_POST['is_show']) ? intval($_POST['is_show']) : 1;  //1显示 0不显示
        
        $fillet_radius = isset($_POST['fillet_radius']) ? intval($_POST['fillet_radius']) : 0;  //圆角值
        $block_bg_color = isset($_POST['block_bg_color']) ? trim($_POST['block_bg_color']) : '';  //矩形背景颜色
        $block_border_color = isset($_POST['block_border_color']) ? trim($_POST['block_border_color']) : '';  //矩形边框颜色
        $label_img = isset($_POST['label_img']) ? trim($_POST['label_img']) : '';  //角标图片上传

        $block_top_color = isset($_POST['block_top_color']) ? trim($_POST['block_top_color']) : '';  //顶部颜色
        $block_bottom_color = isset($_POST['block_bottom_color']) ? trim($_POST['block_bottom_color']) : '';  //底部颜色
        $xsort = isset($_POST['xsort']) ? trim($_POST['xsort']) : 0;  //排序
        $product_info = isset($_POST['product_info']) ? $_POST['product_info'] :'';  //product_info
        $img=isset($_POST['img']) ? trim($_POST['img']) : '';  //图标
        $url=isset($_POST['url']) ? trim($_POST['url']) : '';  //跳转链接
        $wxapp_id=isset($_POST['wxapp_id']) ? trim($_POST['wxapp_id']) : '';  //小程序id
        $wxapp_page=isset($_POST['wxapp_page']) ? trim($_POST['wxapp_page']) : '';  //小程序链接
        $status_show=isset($_POST['status_show']) ? trim($_POST['status_show']) : '';  //1开启 0关闭
        $distance=isset($_POST['distance']) ? intval($_POST['distance']) : 0;  //显示商品的距离
        
        if(empty($block_type_id) && !empty($sub_block_type_id)){
            $block_type_id=$sub_block_type_id;
        }
        if(empty($block_type_id)){
            $this->error('类型参数错误，请重试！');
        }
        if($block_type_id!='business_live_broadcast' && $block_type_id!='business_store_info' && empty($title)){
            $this->error('请填写标题！');
        }
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$post_village_id;
        if($is_community_platform>0){
            $village_id=0;
        }
        $saveArr=array('title'=>$title,'sub_title'=>$sub_title);
        if($is_community_platform>0){
            $saveArr['is_community_platform']=$is_community_platform;
        }
        $saveArr['village_id']=$village_id;
        $saveArr['label_title']=$label_title;
        $saveArr['label_bg_color']=$label_bg_color;
        $saveArr['show_community_platform']=$show_community_platform;
        $haveShowBlock=array('business_live_broadcast','business_store_info','business_classify_info','left_bottom_rowgrid','right_bottom_rowgrid');
        if(in_array($block_type_id,$haveShowBlock)){
            $saveArr['is_show']=$is_show;
        }
        if($block_type_id=='village_street_community'){
            $saveArr['is_show']=$status_show;
            $saveArr['xsort']=$xsort;
        }
        $saveArr['fillet_radius']=$fillet_radius;
        if($block_type_id=='left_bottom_rowgrid' || $block_type_id=='right_bottom_rowgrid'){
            $saveArr['block_bg_color']=$block_bg_color;
            $saveArr['block_border_color']=$block_border_color;
            $saveArr['label_img']=$label_img;
        }
        if($parent_block_type_id && $parent_block_type_id=='business_express_delivery'){
            $saveArr['block_top_color']=$block_top_color;
            $saveArr['block_bottom_color']=$block_bottom_color;
            $saveArr['is_show']=$is_show;
            $saveArr['xsort']=$xsort;
            $saveArr['distance']=$distance;
            if(!empty($product_info)){
                $data_extra=array();
                $data_extra['product_info']=$product_info;
                $saveArr['data_extra']=json_encode($data_extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }else{
                $saveArr['data_extra']='';
            }
            
        }
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $whereArr=array('block_type_id'=>$block_type_id,'village_id'=>$village_id);
        if($parent_block_type_id){
            $whereArr['parent_block_type_id']=$parent_block_type_id;
        }
        if($is_community_platform>0){
            $whereArr['is_community_platform']=$is_community_platform;
        }
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if(!empty($nav_set)){
            $house_village_nav_moduleDb->saveData($whereArr,$saveArr);
        }else{
            $saveArr['block_type_id']=$block_type_id;
            $saveArr['parent_block_type_id']=$parent_block_type_id;
            $saveArr['add_time']=time();
            $house_village_nav_moduleDb->addData($saveArr);
        }
        if($parent_block_type_id && $parent_block_type_id=='business_express_delivery'){
            $whereTmpArr=array('parent_block_type_id'=>$parent_block_type_id,'village_id'=>$village_id);
            if($is_community_platform>0){
                $whereTmpArr['is_community_platform']=$is_community_platform;
            }
            $saveTmpArr=array('block_top_color'=>$block_top_color,'block_bottom_color'=>$block_bottom_color);
            $saveTmpArr['fillet_radius']=$fillet_radius;
            $house_village_nav_moduleDb->saveData($whereTmpArr,$saveTmpArr);
        }
        if($block_type_id=='village_street_community'){
            $village_nav_id=isset($_POST['village_nav_id']) ? trim($_POST['village_nav_id']) : 0; 
            $village_nav_info=array('name'=>$title,'village_id'=>$village_id);
            $village_nav_info['img']=$img;
            $village_nav_info['url']=$url ? htmlspecialchars_decode($url):'';
            $village_nav_info['status']=$status_show;
            $village_nav_info['sort']=$xsort;
            $village_nav_info['type']=50;
            $village_nav_info['wxapp_id']=$wxapp_id;
            $village_nav_info['wxapp_page']=$wxapp_page;
            $village_nav_info['block_type_id']='village_street_community';
            $village_nav_info['is_community_platform']=$is_community_platform;
            $database_house_village_nav = D('House_village_nav');
            $whereArr=array('block_type_id'=>'village_street_community','type'=>50,'village_id'=>$village_id,'is_community_platform'=>$is_community_platform);
            $nav_info_village=$database_house_village_nav->where($whereArr)->find();
            if(!empty($nav_info_village)){
                $where=array('id'=>$nav_info_village['id']);
                $result  = $database_house_village_nav->house_village_nav_edit($where,$village_nav_info,$village_id);
            }else{
                $village_nav_info['add_time'] = time();
                $result = $database_house_village_nav->house_village_nav_add($village_nav_info,$block_type_id);
            }
        }
        $this->success('保存成功');
        //exit('<html><head><script>window.top.msg(1,"保存成功",true,2);window.parent.closeiframe();</script></head></html>');

    }
    public function guess_you_like_goods(){
        $get_village_id= isset($_GET['village_id']) ? intval($_GET['village_id']) : 0;
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$get_village_id;
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $whereArr=array('block_type_id'=>'guess_you_like_goods','village_id'=>$village_id);
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        if(empty($nav_set)){
            $nav_set=array('is_show'=>0,'set_num'=>4);
        }
        $assign_type=D('House_visualize_assign')->house_visualize_assign($this->house_session['village_id'],40);
        $house_visualize_assign=$assign_type['type_40'];
        if(!$house_visualize_assign){
            //取平台的
            $whereArr=array('block_type_id'=>'guess_you_like_goods','village_id'=>'0','is_community_platform'=>'1');
            $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
            if(empty($nav_set)){
                $nav_set=array('is_show'=>0,'set_num'=>4);
            }
        }
        $this->assign('house_visualize_assign',$house_visualize_assign);
        $this->assign('nav_set',$nav_set);
        $this->assign('village_id',$village_id);
        $this->display();
    }
    public function save_guess_you_like_goods(){
        $post_village_id= isset($_POST['village_id']) ? intval($_POST['village_id']) : 0;
        $village_id =$this->house_session &&  isset($this->house_session['village_id']) ? $this->house_session['village_id']:$post_village_id;
        $house_village_nav_moduleDb = D('House_village_nav_module');
        $whereArr=array('block_type_id'=>'guess_you_like_goods','village_id'=>$village_id);
        $nav_set=$house_village_nav_moduleDb->getOneInfo($whereArr);
        $is_show= isset($_POST['is_show']) ? intval($_POST['is_show']) : 0;
        $set_num= isset($_POST['set_num']) ? intval($_POST['set_num']) : 4;
        if(!empty($nav_set)){
            $saveArr=array('is_show'=>$is_show,'set_num'=>$set_num);
            $house_village_nav_moduleDb->saveData(array('id'=>$nav_set['id']),$saveArr);
        }else{
            $saveArr=array('is_show'=>$is_show,'set_num'=>$set_num,'village_id'=>$village_id);
            $saveArr['block_type_id']='guess_you_like_goods';
            $saveArr['title']='猜你喜欢商品';
            $saveArr['add_time']=time();
            $house_village_nav_moduleDb->addData($saveArr);
        }
        $this->success('保存成功');
    }
    public function nav_add_new(){
        $row_type = 'normal';
        if(isset($_GET['row_type'])){
            $row_type = remove_xss($_GET['row_type']);
            $this->assign('row_type',$row_type);
        }
        $block_type_id='';
        $is_community_platform=0;
        if(isset($_GET['block_type_id'])){
            $block_type_id=trim($_GET['block_type_id']);
        }
        if(isset($_GET['is_community_platform'])){
            $is_community_platform=intval($_GET['is_community_platform']);
        }
        $this->assign('block_type_id',$block_type_id);
        $this->assign('is_community_platform',$is_community_platform);
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list',$wxapp_list);
        $this->assign('row_type',$row_type);
        $this->display();
    }

    public function nav_modify_new()
    {
        $row_type = isset($_POST['row_type']) ? remove_xss($_POST['row_type']) : '';
        unset($_POST['row_type']);
        if($row_type == 'single'){
            $_POST['type'] = 1;
        }else if($row_type == 'middle'){
            $_POST['type'] = 2;
        }
        
        $block_type_id='';
        $is_community_platform=0;
        if(isset($_POST['block_type_id']) && !empty($_POST['block_type_id'])){
            $block_type_id=trim($_POST['block_type_id']);
            $_POST['block_type_id']=$block_type_id;
            $_POST['type']=50;  //定制的 看 block_type_id字段来区分
        }
        if($block_type_id!='business_classify_info'){
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
            } else {
                $this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
            } 
        }

        if(isset($_POST['is_community_platform'])){
            $is_community_platform=intval($_POST['is_community_platform']);
            $_POST['is_community_platform']=$is_community_platform;
        }
        if(isset($_POST['url']) && $_POST['url']){
            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
        }
        $_POST['add_time'] = time();
        $_POST['village_id'] = $this->house_session['village_id'];
        if(isset($_POST['mark_bg']) && !empty($_POST['mark_bg'])){
            $_POST['mark_bg'] = RGBToHex($_POST['mark_bg']);
        }
        $database_house_village_nav = D('House_village_nav');
        $result = $database_house_village_nav->house_village_nav_add($_POST,$block_type_id);

        if(!$result){
            $this->error('数据处理有误！');
        }

        if($result['status']){
            exit('<html><head><script>window.top.msg(1,"保存成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
        }else{
            exit('<html><head><script>window.top.msg(0,"修改失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
        }
    }

    public function nav_edit_new()
    {
        $this->assign('bg_color','#F3F3F3');
        $database_house_village_nav = D('House_village_nav');
        $where['id'] = $_GET['id'] + 0;
        $result = $database_house_village_nav->house_village_nav_detail($where);

        if(!$result){
            $this->frame_error_tips('数据处理有误！');
        }
        if($result['detail']){

            if(substr($result['detail']['img'],1,6) == 'static'){
                $result['detail']['img'] = C('config.site_url').$result['detail']['img'];
            }elseif(substr($result['detail']['img'],'0','4') == '000/'){
                $result['detail']['img']= file_domain().'/upload/adver/' . $result['detail']['img'];
            }else{
                $result['detail']['img']= file_domain().'/upload/service/'.$result['detail']['img'];
            }
        }
        $block_type_id='';
        $is_community_platform=0;
        if(isset($_GET['block_type_id'])){
            $block_type_id=trim($_GET['block_type_id']);
        }
        if(isset($_GET['is_community_platform'])){
            $is_community_platform=intval($_GET['is_community_platform']);
        }
        $this->assign('block_type_id',$block_type_id);
        $this->assign('is_community_platform',$is_community_platform);
        //小程序列表
        $wxapp_list = M('Wxapp_other')->field(true)->select();
        $this->assign('wxapp_list',$wxapp_list);
        $this->assign('detail',$result['detail']);
        $this->display();


    }

    public function nav_amend_new()
    {
        $block_type_id='';
        if(isset($_POST['block_type_id']) && !empty($_POST['block_type_id'])){
            $block_type_id=trim($_POST['block_type_id']);
            $_POST['block_type_id']=$block_type_id;
            $_POST['type']=50;  //定制的 看 block_type_id字段来区分
        }
        
        if($block_type_id!='business_classify_info' && $_FILES['img']['error'] != 4){
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
            } else {
                $this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
            }
        }
        $village_id = $this->house_session['village_id'];
        $database_house_village_nav = D('House_village_nav');
        $where['id'] = $_POST['id'] + 0;
        if(isset($_POST['mark_bg']) && !empty($_POST['mark_bg']) && strpos($_POST['mark_bg'],'rgb') !== false){
            $_POST['mark_bg'] = RGBToHex($_POST['mark_bg']);
        }
        $result  = $database_house_village_nav->house_village_nav_edit($where,$_POST,$village_id);
        if(!$result){

            $this->frame_error_tips('数据处理有误！');
        }

        if($result['status']){
            exit('<html><head><script>window.top.msg(1,"修改成功",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
        }else{
            exit('<html><head><script>window.top.msg(0,"修改失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
        }
    }

    public function nav_del_new(){
        $database_house_village_nav = D('House_village_nav');
        $where['id'] = $_POST['id'] + 0;
        $village_id = $this->house_session['village_id'];
        $now_adver = $database_house_village_nav->field(true)->where($where)->find();
        if(!$now_adver){
            $this->error('数据不存在!');
        }

        //todo 小区复制平台广告 生成属于小区的广告
        $rel=D('House_link')->get_filter_village_id($village_id,$now_adver,'del');
        if(!empty($rel)){
            $database_house_village_nav->where(['id'=>$now_adver['id']])->data(['filter_village_ids'=>implode(',',$rel)])->save();
            $this->success('删除成功');
        }else{
            $result = $database_house_village_nav->house_village_nav_del($where);
            if($result){
                $result = $database_house_village_nav->house_village_nav_detail($where);
                if(substr($result['detail']['img'],'0','4') == '000/'){
                   // unlink('./upload/adver/'.$result['detail']['img']);
                    D('Config')->clearCache();
                }
                $this->success('删除成功');
            }else{
                $this->error('删除失败！请重试~');
            }
        }
    }



    public function express_send_list(){
        //快递代发-查看 权限
        if (!in_array(214, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $add_time_start = $_POST['add_time_start'];
        $add_time_end = $_POST['add_time_end'];
        $this->assign('add_time_start', $add_time_start);
        $this->assign('add_time_end', $add_time_end);
        
        if($add_time_start && $add_time_end){
            $start_time = strtotime($add_time_start);
            $end_time = strtotime($add_time_end.'23:59:59');
            $where['add_time'] = array('between',array($start_time,$end_time));
        }else if($add_time_start){
            $add_time_start = strtotime($add_time_start);
            $where['add_time'] = array('egt',$add_time_start);
        }else if($add_time_end){
            $add_time_end = strtotime($add_time_end.'23:59:59');
            $where['add_time'] = array('lt',$add_time_end);
        }

        $send_phone = $_POST['send_phone'];
        $this->assign('send_phone', $send_phone);
        if($send_phone){
            $where['send_phone'] = $send_phone;
        }

        $express = $_POST['express'];
        $this->assign('express', $express);
        if($express){
            $where['express'] = $express;
        }

        $village_id = $this->house_session['village_id'];
        $has_express_send = $this->getHasConfig($village_id, 'has_express_send');
        $this->assign('has_express_send',$has_express_send);
        if($has_express_send){

            $express_tmp = M('Express')->where(array('status'=>1))->select();
            $this->assign('express_tmp',$express_tmp);
            foreach ($express_tmp as $key => $value) {
                $express_list[$value['code']] = $value['name'];
            }
            $this->assign('express_list',$express_list);

            $goods_type = array('0'=>'文件','1'=>'数码产品','2'=>'生活用品','3'=>'服饰','4'=>'食品','5'=>'其他');
            $this->assign('goods_type',$goods_type);

            $where['village_id'] = $village_id;

            $count = M('House_village_express_send')->where($where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count, 15);
            $send_list= M('House_village_express_send')->where($where)->order('send_id DESC')->limit($p->firstRow,$p->listRows)->select();
            if($send_list){
                $d_house_village=D('House_village');
                $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
                foreach ($send_list as $kk=>$vvv){
                    if($vvv['send_phone']){
                        $send_list[$kk]['send_phone']= $d_house_village->phone_desensitization_v($vvv['send_phone'],$village_id,$village_info);
                    }
                    if($vvv['collect_phone']){
                        $send_list[$kk]['collect_phone']=$d_house_village->phone_desensitization_v($vvv['collect_phone'],$village_id,$village_info);
                    }
                }
            }
            $this->assign('pagebar',$p->show());
            $this->assign('send_list',$send_list);


        }
        $this->display();
    }


    public function express_send_export(){
        //快递代发-导出 权限
        if (!in_array(215, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

//        $goods_type = array('1'=>'文件','2'=>'数码产品','3'=>'生活用品','4'=>'服饰','5'=>'食品','6'=>'其他');
        $goods_type = array('0'=>'文件','1'=>'数码产品','2'=>'生活用品','3'=>'服饰','4'=>'食品','5'=>'其他');

        $express_tmp = M('Express')->where(array('status'=>1))->select();
        $this->assign('express_tmp',$express_tmp);
        foreach ($express_tmp as $key => $value) {
            $express_list[$value['code']] = $value['name'];
        }


        if($_GET['send_id']){
            $where['send_id'] = array('in',$_GET['send_id']);
        }

        $send_list = M('House_village_express_send')->where($where)->select();
       
        if(count($send_list) <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->village['village_name'] . '小区-快递代发列表';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($send_list)/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($send_list) . '条数据');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '快递公司');
            $objActSheet->setCellValue('B1', '寄件人信息');
            $objActSheet->setCellValue('C1', '收件人信息');
            $objActSheet->setCellValue('D1', '物品重量');
            $objActSheet->setCellValue('E1', '文件类型');
            $objActSheet->setCellValue('F1', '代发费用');
            $objActSheet->setCellValue('G1', '备注');
            $objActSheet->setCellValue('H1', '提交时间');

            if (!empty($send_list)) {
                $index = 2;

                $cell_list = range('A','H');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(40);
                }

                foreach ($send_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $express_list[$value['express']]);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['send_uname']." ".$value['send_phone']." ".$value['send_city']." ".$value['send_adress']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['collect_uname']." ".$value['collect_phone']." ".$value['collect_city']." ".$value['collect_adress']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['weight'].' (Kg)');
                    $objActSheet->setCellValueExplicit('E' . $index, $goods_type[$value['goods_type']]);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['send_price'].' ('.C('config.Currency_txt').')');
                    $objActSheet->setCellValueExplicit('G' . $index, $value['remarks']);
                    $objActSheet->setCellValueExplicit('H' . $index, date('Y-m-d H:i:s',$value['add_time']));
                    M('House_village_express_send')->where(array('send_id'=>$value['send_id']))->data(array('export_time'=>time()))->save();
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


    public function store_arrival_check(){
        $order_info = D('House_village_pay_order')->where(array('order_id'=>$_POST['order_id']))->find();
        if($order_info['paid'] > 0 ){
            exit(json_encode(array('status'=>1,'msg'=>'缴费成功')));
        }
    }
	
	public $nCov_2019_user_type = [
		1 => '业主',
		2 => '租客',
		3 => '亲友',
		4 => '快递',
		5 => '外卖',
		6 => '中介',
		99 => '其他',
	];
	
	public function nCoV_2019(){
		
		$where = ['village_id' => $this->village_id];
        $where_user_bind = [];
		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
                    $where_user_bind['_string'] = "usernum='{$_GET['find_value']}' OR bind_number='{$_GET['find_value']}'";
                    $where_user_bind['village_id'] = $this->village_id;
					$where['bind_id'] = M('House_village_user_bind')->where($where_user_bind)->getField('pigcms_id');
					break;
				case 2:
					$bind_list = M('House_village_user_bind')->field('`pigcms_id`')->where(['phone' => $_GET['find_value'], 'village_id' => $this->village_id])->select();
					$where['bind_id'] = ['in', array_column($bind_list, 'pigcms_id')];
					break;
				default:
					break;
			}
		}
		
		
		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['add_time'] = [['egt', $begin_time], ['elt',$end_time], 'and'];
		}elseif(isset($begin_time)){
			$where['add_time'] = ['egt', $begin_time];
		}elseif(isset($end_time)){
			$where['add_time'] = ['elt', $end_time];
		}
		
		import('@.ORG.merchant_page');
		$count = M('House_village_ncov_2019')->where($where)->count();
		$p = new Page($count, 20, 'page');
		$list = M('House_village_ncov_2019')->where($where)->order('`add_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$pagebar = $p->show();
		
		$this->assign('list', $list);
		$this->assign('pagebar', $pagebar);
		
		$this->assign('nCov_2019_user_type', $this->nCov_2019_user_type);
		
		$this->display();
	}
	
	public function nCoV_2019_white(){
		$where = ['village_id' => $this->village_id];
		if($_GET['find_value']){
			$where['phone'] = $_GET['find_value'];
		}
		
		import('@.ORG.merchant_page');
		$count = M('House_village_ncov_2019_white')->where($where)->count();
		$p = new Page($count, 20, 'page');
		$list = M('House_village_ncov_2019_white')->where($where)->order('`add_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		$pagebar = $p->show();
		
		$this->assign('list', $list);
		$this->assign('pagebar', $pagebar);
		
		$this->display();
		
	}
	
	public function nCoV_2019_white_add(){
		
		if(IS_POST){
			$_POST['phone'] = trim($_POST['phone']);
			
			$where = [
				'phone' 		=> $_POST['phone'],
				'village_id' 	=> $this->village_id,
			];
			if(M('House_village_ncov_2019_white')->where($where)->find()){
				$this->error('该手机号已经存在，不能再次添加。');
			}
			
			
			if($_POST['status'] == 2){
				$_POST['type'] = '';
			}
			$_POST['village_id'] = $this->village_id;
			$_POST['add_time'] = time();
			if(M('House_village_ncov_2019_white')->data($_POST)->add()){
				$this->success('添加成功',U('nCoV_2019_white'));
			}else{
				$this->error('添加失败，请重试');
			}
		}else{
			$this->display();
		}
	}
	
	public function nCoV_2019_white_del(){
		$where = [
			'id' 			=> $_GET['id'],
			'village_id' 	=> $this->village_id,
		];
		if(M('House_village_ncov_2019_white')->where($where)->delete()){
			$this->success('删除成功');
		}else{
			$this->error('删除失败，请重试');
		}
	}
	
	public function nCoV_2019_export(){
		$where = ['village_id' => $this->village_id];
        $where_user_bind = [];
		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
                    $where_user_bind['_string'] = "usernum='{$_GET['find_value']}' OR bind_number='{$_GET['find_value']}'";
                    $where_user_bind['village_id'] = $this->village_id;
					$where['bind_id'] = M('House_village_user_bind')->where($where_user_bind)->getField('pigcms_id');
					break;
				case 2:
					$bind_list = M('House_village_user_bind')->field('`pigcms_id`')->where(['phone' => $_GET['find_value'], 'village_id' => $this->village_id])->select();
					$where['bind_id'] = ['in', array_column($bind_list, 'pigcms_id')];
					break;
				default:
					break;
			}
		}
		
		
		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['add_time'] = [['egt', $begin_time], ['elt',$end_time], 'and'];
		}elseif(isset($begin_time)){
			$where['add_time'] = ['egt', $begin_time];
		}elseif(isset($end_time)){
			$where['add_time'] = ['elt', $end_time];
		}
		

		$count = M('House_village_ncov_2019')->where($where)->count();
		if($count <= 0 ){
			$this->error('无数据导出！');
		}

		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		
		$title = '新冠防护登记';
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);

		$len = ceil($count/1000);
		for ($i = 0; $i < $len; $i++) {
		    $_GET['page'] = $i + 1;
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);
			$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '千条信息');
			$objActSheet = $objExcel->getActiveSheet();

			$objActSheet->setCellValue('A1', '身份');
			$objActSheet->setCellValue('B1', '姓名');
			$objActSheet->setCellValue('C1', '楼号');
			$objActSheet->setCellValue('D1', '手机号');
			$objActSheet->setCellValue('E1', '进出类别');
			$objActSheet->setCellValue('F1', '身份证号');
			$objActSheet->setCellValue('G1', '体温');
			$objActSheet->setCellValue('H1', '登记时间');
			$objActSheet->setCellValue('I1', '进出事由');
			$objActSheet->setCellValue('J1', '备注');

			$list = M('House_village_ncov_2019')->where($where)->order('`add_time` DESC')->limit((($_GET['page'] - 1) * 1000) . ',1000')->select();
			if (!empty($list)) {
				$index = 2;

				$cell_list = range('A','M');
				foreach ($cell_list as $cell) {
					$objActSheet->getColumnDimension($cell)->setWidth(30);
				}

				foreach ($list as $value) {
					if($value['user_type']){
						$objActSheet->setCellValueExplicit('A' . $index, '匿名-'.$this->nCov_2019_user_type[$value['user_type']]);
					}else{
						$objActSheet->setCellValueExplicit('A' . $index, '已登记');
					}
					
					$objActSheet->setCellValueExplicit('B' . $index, $value['name']);
					$objActSheet->setCellValueExplicit('C' . $index, $value['address']);
					$objActSheet->setCellValueExplicit('D' . $index, $value['phone']);

					if($value['check_type'] == 1){
						$status_val = '进入';
					}elseif($value['check_type'] == 2){
						$status_val = '离开';
					}

					$objActSheet->setCellValueExplicit('E' . $index, $status_val);
					$objActSheet->setCellValueExplicit('F' . $index, $value['id_card']);
					
					if($value['temperature'] == -1 || ($value['temperature'] > 0 && $value['temperature'] < 37.3)){
						$objActSheet->setCellValueExplicit('G' . $index, '小于 37.3');
					}else if($value['temperature'] == -2 || ($value['temperature'] > 0 && $value['temperature'] >= 37.3)){
						$objActSheet->setCellValueExplicit('G' . $index, '大于 37.3');
					}else{
						$objActSheet->setCellValueExplicit('G' . $index, $value['temperature']);
					}
					
					$objActSheet->setCellValueExplicit('H' . $index, date('Y-m-d H:i:s', $value['add_time']));
					$objActSheet->setCellValueExplicit('I' . $index, $value['check_reason']);
					$objActSheet->setCellValueExplicit('J' . $index, $value['check_mark']);

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
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
		
	}
	
	public function nCoV_2019_qrcode(){
		$this->display();
	}
	
	public $nCov_2019_health_type = [
		0 => '健康',
		1 => '有发烧、咳嗽等症状',
		2 => '有其他症状（备注中说明）',
	];
	
	public function nCoV_2019_health(){
		
		$where = ['village_id' => $this->village_id];
        $where_user_bind = [];
		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
                    $where_user_bind['_string'] = "usernum='{$_GET['find_value']}' OR bind_number='{$_GET['find_value']}'";
                    $where_user_bind['village_id'] = $this->village_id;
					$where['bind_id'] = M('House_village_user_bind')->where($where_user_bind)->getField('pigcms_id');
					break;
				case 2:
					$bind_list = M('House_village_user_bind')->field('`pigcms_id`')->where(['phone' => $_GET['find_value'], 'village_id' => $this->village_id])->select();
					$where['bind_id'] = ['in', array_column($bind_list, 'pigcms_id')];
					break;
				default:
					break;
			}
		}
		
		
		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['add_time'] = [['egt', $begin_time], ['elt',$end_time], 'and'];
		}elseif(isset($begin_time)){
			$where['add_time'] = ['egt', $begin_time];
		}elseif(isset($end_time)){
			$where['add_time'] = ['elt', $end_time];
		}
		
		import('@.ORG.merchant_page');

		$count = M('House_village_ncov_2019_health')->where($where)->count();
		$p = new Page($count, 20, 'page');
		$list = M('House_village_ncov_2019_health')->where($where)->order('`add_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		
		$pagebar = $p->show();
		
		$this->assign('list', $list);
		$this->assign('pagebar', $pagebar);
		
		$this->assign('nCov_2019_health_type', $this->nCov_2019_health_type);
		
		$this->display();
	}
	
	public function nCoV_2019_health_export(){
		$where = ['village_id' => $this->village_id];
		
		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
					$where['bind_id'] = M('House_village_user_bind')->where(['usernum' => $_GET['find_value'], 'village_id' => $this->village_id])->getField('pigcms_id');
					break;
				case 2:
					$bind_list = M('House_village_user_bind')->field('`pigcms_id`')->where(['phone' => $_GET['find_value'], 'village_id' => $this->village_id])->select();
					$where['bind_id'] = ['in', array_column($bind_list, 'pigcms_id')];
					break;
				default:
					break;
			}
		}
		
		
		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['add_time'] = [['egt', $begin_time], ['elt',$end_time], 'and'];
		}elseif(isset($begin_time)){
			$where['add_time'] = ['egt', $begin_time];
		}elseif(isset($end_time)){
			$where['add_time'] = ['elt', $end_time];
		}
		

		$count = M('House_village_ncov_2019_health')->where($where)->count();
		if($count <= 0 ){
			$this->error('无数据导出！');
		}

		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = '新冠健康打卡';
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);

		$len = ceil($count/1000);
		for ($i = 0; $i < $len; $i++) {
		    $_GET['page'] = $i + 1;
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);
			$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '千条信息');
			$objActSheet = $objExcel->getActiveSheet();

			$objActSheet->setCellValue('A1', '姓名');
			$objActSheet->setCellValue('B1', '房屋');
			$objActSheet->setCellValue('C1', '手机号');
			$objActSheet->setCellValue('D1', '身份证号');
			$objActSheet->setCellValue('E1', '健康情况');
			$objActSheet->setCellValue('F1', '路过重疫区');
			$objActSheet->setCellValue('G1', '外出其他省市');
			$objActSheet->setCellValue('H1', '接触外省市人员');
			$objActSheet->setCellValue('I1', '体温');
			$objActSheet->setCellValue('J1', '登记时间');
			$objActSheet->setCellValue('K1', '备注');

			$list = M('House_village_ncov_2019_health')->where($where)->order('`add_time` DESC')->limit((($_GET['page'] - 1) * 1000) . ',1000')->select();
			if (!empty($list)) {
				$index = 2;

				$cell_list = range('A','M');
				foreach ($cell_list as $cell) {
					$objActSheet->getColumnDimension($cell)->setWidth(30);
				}

				foreach ($list as $value) {
					
					$objActSheet->setCellValueExplicit('A' . $index, $value['name']);
					$objActSheet->setCellValueExplicit('B' . $index, $value['address']);
					$objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
					$objActSheet->setCellValueExplicit('D' . $index, $value['id_card']);
					$objActSheet->setCellValueExplicit('E' . $index, $this->nCov_2019_health_type[$value['health_info']]);
					$objActSheet->setCellValueExplicit('F' . $index, $value['go_hubei'] ? '是' : '否');
					$objActSheet->setCellValueExplicit('G' . $index, $value['out_province'] ? '是' : '否');
					$objActSheet->setCellValueExplicit('H' . $index, $value['near_hubei_people'] ? '是' : '否');
					
					if($value['temperature'] == -1 || ($value['temperature'] > 0 && $value['temperature'] < 37.3)){
						$objActSheet->setCellValueExplicit('I' . $index, '小于 37.3');
					}else if($value['temperature'] == -2 || ($value['temperature'] > 0 && $value['temperature'] >= 37.3)){
						$objActSheet->setCellValueExplicit('I' . $index, '大于 37.3');
					}else{
						$objActSheet->setCellValueExplicit('I' . $index, $value['temperature']);
					}
					
					$objActSheet->setCellValueExplicit('J' . $index, date('Y-m-d H:i:s', $value['add_time']));
					$objActSheet->setCellValueExplicit('K' . $index, $value['check_mark']);

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
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
		
	}
	
	public function nCoV_2019_back(){
		
		$where = ['village_id' => $this->village_id];
		
		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
					$where['bind_id'] = M('House_village_user_bind')->where(['usernum' => $_GET['find_value'], 'village_id' => $this->village_id])->getField('pigcms_id');
					break;
				case 2:
					$bind_list = M('House_village_user_bind')->field('`pigcms_id`')->where(['phone' => $_GET['find_value'], 'village_id' => $this->village_id])->select();
					$where['bind_id'] = ['in', array_column($bind_list, 'pigcms_id')];
					break;
				default:
					break;
			}
		}
		
		
		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['add_time'] = [['egt', $begin_time], ['elt',$end_time], 'and'];
		}elseif(isset($begin_time)){
			$where['add_time'] = ['egt', $begin_time];
		}elseif(isset($end_time)){
			$where['add_time'] = ['elt', $end_time];
		}
		
		import('@.ORG.merchant_page');

		$count = M('House_village_ncov_2019_back')->where($where)->count();
		$p = new Page($count, 20, 'page');
		$list = M('House_village_ncov_2019_back')->where($where)->order('`add_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();
		
		$pagebar = $p->show();
		
		$this->assign('list', $list);
		$this->assign('pagebar', $pagebar);
		
		$this->assign('nCov_2019_health_type', $this->nCov_2019_health_type);
		$this->assign('nCov_2019_user_type', $this->nCov_2019_user_type);
		
		$this->display();
	}
	
	public function nCoV_2019_back_export(){
		$where = ['village_id' => $this->village_id];
		
		if($find_type = $_GET['find_type'] + 0){
			switch($find_type){
				case 1:
					$where['bind_id'] = M('House_village_user_bind')->where(['usernum' => $_GET['find_value'], 'village_id' => $this->village_id])->getField('pigcms_id');
					break;
				case 2:
					$bind_list = M('House_village_user_bind')->field('`pigcms_id`')->where(['phone' => $_GET['find_value'], 'village_id' => $this->village_id])->select();
					$where['bind_id'] = ['in', array_column($bind_list, 'pigcms_id')];
					break;
				default:
					break;
			}
		}
		
		
		$begin_time = 0;
		$end_time = 0;
		if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
			$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
		}

		if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
			$end_time = strtotime($_GET['end_time'] . '23:59:59');
		}

		if(($begin_time > 0) && ($end_time > 0)){
			if($begin_time > $end_time){
				$this->error("结束时间应大于开始时间");
			}

			$where['add_time'] = [['egt', $begin_time], ['elt',$end_time], 'and'];
		}elseif(isset($begin_time)){
			$where['add_time'] = ['egt', $begin_time];
		}elseif(isset($end_time)){
			$where['add_time'] = ['elt', $end_time];
		}
		

		$count = M('House_village_ncov_2019_back')->where($where)->count();
		if($count <= 0 ){
			$this->error('无数据导出！');
		}

		require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
		$title = '新冠返程登记';
		$objExcel = new PHPExcel();
		$objProps = $objExcel->getProperties();
		// 设置文档基本属性
		$objProps->setCreator($title);
		$objProps->setTitle($title);
		$objProps->setSubject($title);
		$objProps->setDescription($title);

		$len = ceil($count/1000);
		for ($i = 0; $i < $len; $i++) {
		    $_GET['page'] = $i + 1;
			$i && $objExcel->createSheet();
			$objExcel->setActiveSheetIndex($i);
			$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '千条信息');
			$objActSheet = $objExcel->getActiveSheet();

			$objActSheet->setCellValue('A1', '身份');
			$objActSheet->setCellValue('B1', '姓名');
			$objActSheet->setCellValue('C1', '房屋');
			$objActSheet->setCellValue('D1', '手机号');
			$objActSheet->setCellValue('E1', '身份证号');
			
			$objActSheet->setCellValue('F1', '单位名称');
			$objActSheet->setCellValue('G1', '单位电话');
			
			$objActSheet->setCellValue('H1', '健康情况');
			$objActSheet->setCellValue('I1', '体温');
			
			
			$objActSheet->setCellValue('J1', '返程出发地');
			$objActSheet->setCellValue('K1', '交通工具');
			$objActSheet->setCellValue('L1', '车牌号/车次/航班');
			$objActSheet->setCellValue('M1', '途经湖北');
			$objActSheet->setCellValue('N1', '接触疑似病例');
			$objActSheet->setCellValue('O1', '接触重点地区人员');
			
			$objActSheet->setCellValue('P1', '登记时间');
			$objActSheet->setCellValue('Q1', '备注');

			$list = M('House_village_ncov_2019_back')->where($where)->order('`add_time` DESC')->limit((($_GET['page'] - 1) * 1000) . ',1000')->select();
			if (!empty($list)) {
				$index = 2;

				$cell_list = range('A','M');
				foreach ($cell_list as $cell) {
					$objActSheet->getColumnDimension($cell)->setWidth(30);
				}

				foreach ($list as $value) {
					
					$objActSheet->setCellValueExplicit('A' . $index, $this->nCov_2019_user_type[$value['user_type']]);
					$objActSheet->setCellValueExplicit('B' . $index, $value['name']);
					$objActSheet->setCellValueExplicit('C' . $index, $value['address']);
					$objActSheet->setCellValueExplicit('D' . $index, $value['phone']);
					$objActSheet->setCellValueExplicit('E' . $index, $value['id_card']);
					
					$objActSheet->setCellValueExplicit('F' . $index, $value['work_name']);
					$objActSheet->setCellValueExplicit('G' . $index, $value['work_phone']);
					
					$objActSheet->setCellValueExplicit('H' . $index, $this->nCov_2019_health_type[$value['health_info']]);
					if($value['temperature'] == -1 || ($value['temperature'] > 0 && $value['temperature'] < 37.3)){
						$objActSheet->setCellValueExplicit('I' . $index, '小于 37.3');
					}else if($value['temperature'] == -2 || ($value['temperature'] > 0 && $value['temperature'] >= 37.3)){
						$objActSheet->setCellValueExplicit('I' . $index, '大于 37.3');
					}else{
						$objActSheet->setCellValueExplicit('I' . $index, $value['temperature']);
					}
					
					$objActSheet->setCellValueExplicit('J' . $index, $value['back_city']);
					$objActSheet->setCellValueExplicit('K' . $index, $value['back_traffic']);
					$objActSheet->setCellValueExplicit('L' . $index, $value['car_number']);
					
					$objActSheet->setCellValueExplicit('M' . $index, $value['pass_hubei']);
					$objActSheet->setCellValueExplicit('N' . $index, $value['near_case']);
					$objActSheet->setCellValueExplicit('O' . $index, $value['near_hubei']);
					
					
					
					$objActSheet->setCellValueExplicit('P' . $index, date('Y-m-d H:i:s', $value['add_time']));
					$objActSheet->setCellValueExplicit('Q' . $index, $value['check_mark']);

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
		header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
		header("Content-Transfer-Encoding:binary");
		$objWriter->save('php://output');
		exit();
		
	}

    public function nCoV_2019_config() {
        if (!in_array(396, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $village_id = $this->village_id;

        if(IS_POST){
            $condition_village['village_id'] = $village_id;
            $result = D('House_village')->where($condition_village)->data($_POST)->save();
            if($result !== false){
                $this->success('设置成功！', U('nCoV_2019_config'));
            }else{
                $this->error('设置失败！请重试。', U('nCoV_2019_config'));
            }
        }elseif($village_id){
            $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
            $property = M('House_property')->where(array('id'=>$village_info['property_id']))->find();
            $village_info['long'] = floatval($village_info['long']);
            $village_info['lat'] = floatval($village_info['lat']);
            $village_info['door_pwd'] = explode(',', $village_info['door_pwd']);
            $this->assign('village_info',$village_info);
            $this->assign('property',$property);
            $this->display();
        }
    }

    /**
     * 代送代收基本设置
     */
    public function set_send_price_config() {
        //基本设置-查看 权限
        if (!in_array(399, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $village_id = $this->village_id;
        if(IS_POST){
            //2020/8/24 start 新增
            $is_open_pay_management = D('House_menu_new')->getPayCostAppIsUsable($this->village_id);
            if(!$is_open_pay_management){
                //由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用
                $this->error('由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用！');
            }
            //2020/8/24 end 新增
            $condition_village['village_id'] = $village_id;
            $set = array('express_send_price' => $_POST['express_send_price']);
            $result = D('House_village')->where($condition_village)->data($set)->save();
            if($result !== false){
                $this->success('设置成功！', U('set_send_price_config'));
            }else{
                $this->error('设置失败！请重试。', U('set_send_price_config'));
            }
        }elseif($village_id){
            $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
            $property = M('House_property')->where(array('id'=>$village_info['property_id']))->find();
            $village_info['long'] = floatval($village_info['long']);
            $village_info['lat'] = floatval($village_info['lat']);
            $village_info['door_pwd'] = explode(',', $village_info['door_pwd']);
            $this->assign('village_info',$village_info);
            $this->assign('property',$property);
            $this->display();
        }
    }

    public function set_village_nav(){
        $village_id = $this->village_id;
           if(IS_POST){
               $condition_village['village_id'] = $village_id;
               $row_type = $_POST['row_type'];
               $set = [];
               // 单行
               if($row_type == 'single'){
                   if(isset($_POST['village_nav'])){
                       $set['village_nav_single'] = $_POST['village_nav'];
                   }
                   if(isset($_POST['multiline_effect'])){
                       $set['multiline_effect'] = $_POST['multiline_effect'];
                   }
                   if(isset($_POST['view_num'])){
                       $set['view_num'] = $_POST['view_num'];
                   }
               }else{
                   // 原有功能导航
                   if(isset($_POST['village_nav'])){
                       $set['village_nav'] = $_POST['village_nav'];
                   }
                   if(isset($_POST['multiline_effect'])){
                       $set['single_effect'] = $_POST['multiline_effect'];
                   }
               }
               if(isset($_POST['is_guess_like'])){
                   $set['is_guess_like'] = $_POST['is_guess_like'];
               }
               if(isset($_POST['fillet_radius'])){
                   $set['fillet_radius'] = $_POST['fillet_radius'];
               }
               if(empty($set)){
                   exit(json_encode(array('status'=>0,'msg'=>'设置失败！数据不能为空。')));
               }
               $result = D('House_village_info')->where($condition_village)->data($set)->save();
               if($result){
                   exit(json_encode(array('status'=>1,'msg'=>'设置成功！')));
               }else{
                   exit(json_encode(array('status'=>0,'msg'=>'设置失败！请重试。')));
               }
           }else{
               exit(json_encode(array('status'=>0,'msg'=>'设置失败！请重试。')));
           }
    }
    
    public function shop_goods_seckill_set(){
        $village_id = $this->house_session['village_id'];
        $database_house_village_nav = D('House_village_nav');
        $shop_goods_type=isset($_GET['shop_goods_type']) ? trim($_GET['shop_goods_type']):'';
        //todo 功能导航
        $assign_type=D('House_visualize_assign')->house_visualize_assign($this->house_session['village_id'],15);
        $where=array('type'=>21,'block_type_id'=>'shop_goods_seckill');
        $key_name=isset($_GET['key_name']) ? trim($_GET['key_name']):'';
        $goods_name=isset($_GET['goods_name']) ? trim($_GET['goods_name']):'';
        $where['_string'] =' (village_id = '.$village_id.') ';
        if($key_name){
            $where['_string']=$where['_string']." AND name LIKE '%{$key_name}%'";
        }
        if($goods_name){
            $where['_string']=$where['_string']." AND (shop_mer_name LIKE '%{$goods_name}%' OR shop_store_name LIKE '%{$goods_name}%')";
        }
        if($shop_goods_type){
            $where['shop_goods_type']=$shop_goods_type;
        }
        if(!$assign_type['type_15']){
            $where['_string'] .= ' or (village_id =0 and (filter_village_ids is null OR  !FIND_IN_SET('.$village_id.', filter_village_ids )))';
        }
        $where['status'] = array('neq',4);
        $result = $database_house_village_nav->house_village_nav_page_list($where , '*' , 'sort DESC,status DESC,id desc');
        if(!$result){
            $this->error('数据处理有误！');
        }
        $resultList=$result['result'];
        $newGoodsData=array();
        if(isset($result['shopGoodsList']) && $result['shopGoodsList']){
            $shopGoodsData=D('Admin')->getSeckillGoods($result['shopGoodsList'], false);
            if($shopGoodsData){
               foreach ($shopGoodsData as $gvv){
                   $newGoodsData[$gvv['nav_idd']]=$gvv;
               }
            }
        }
        $delArrId=array();
        foreach ($resultList['list'] as $kk=>$vv){
            if(isset($newGoodsData[$vv['id']]) && $newGoodsData[$vv['id']]['goods_name']){
                $gitrm=$newGoodsData[$vv['id']];
                $saveArr=array();
                $saveArr['name']=$gitrm['goods_name'];
                $saveArr['img']=isset($gitrm['img']) ? $gitrm['img']:'';
                $saveArr['url']=isset($gitrm['url']) ? $gitrm['url']:'';
                $saveArr['status']=isset($gitrm['goods_status']) ? $gitrm['goods_status']:0;  //0未上架 1上架 2过期
                $saveArr['url']=isset($gitrm['url']) ? $gitrm['url']:'';
                $saveArr['business_num']=isset($gitrm['stock_num']) ? $gitrm['stock_num']:0;  //库存
                $start_time=isset($gitrm['active_start_time']) ? trim($gitrm['active_start_time']):'';
                $saveArr['start_time']=!empty($start_time) ? strtotime($start_time):0;
                $end_time=isset($gitrm['active_end_time']) ? trim($gitrm['active_end_time']):'';
                $saveArr['end_time']=!empty($end_time) ? strtotime($end_time):0;
                $saveArr['shop_mer_name']=$gitrm['merchant_name'];
                $saveArr['shop_store_name']=$gitrm['store_name'];
                unset($gitrm['is_spec'],$gitrm['spec_value'],$gitrm['nav_idd']);
                $saveArr['data_extra']=json_encode($gitrm,JSON_UNESCAPED_UNICODE);
                $database_house_village_nav->where(array('id'=>$vv['id']))->data($saveArr)->save();
            }else{
                $delArrId[]=$vv['id'];
            }
        }
        $nowtime=time();
        if($delArrId){
            $database_house_village_nav->where(array('id'=>array('in',$delArrId)))->data(array('status'=>4,'del_time'=>$nowtime))->save();
        }
        $result = $database_house_village_nav->house_village_nav_page_list($where , '*' , 'sort DESC,status DESC,id desc');
        $resultList=$result['result'];
        $this->assign('nowtime',time());
        $this->assign('result',$resultList);
        $this->assign('shop_goods_type',$shop_goods_type);
        $this->assign('village_id',$village_id);
        $this->assign('assign_type',$assign_type);
        $this->display();
    }

    public function shop_goods_seckill_pop(){
        $goods_type=isset($_GET['goods_type']) ? trim($_GET['goods_type']) :'shop'; //shop 外卖  mall商城 group社区团购
        $goods_name=isset($_GET['goods_name']) ? trim($_GET['goods_name']) :'';
        $goods_list=array();
        $params=array('goods_name'=>$goods_name);
        $pagebar='';
        $shopData=D('Admin')->getSeckillGoodsList($goods_type, $params);
        if($shopData && isset($shopData['list'])){
            $goods_list=$shopData['list'];
            $pagebar=$shopData['page_bar'];
        }
        $this->assign('goods_list',$goods_list);
        $this->assign('pagebar',$pagebar);
        $this->assign('goods_type',$goods_type);
        $this->display();
    }
    public function shop_goods_seckill_save(){
        
        $goods_type=isset($_POST['goods_type']) ? trim($_POST['goods_type']):"shop";
        $goods_arr=isset($_POST['goods_arr']) ? $_POST['goods_arr']:"";
        $village_id = $this->house_session['village_id'];
        $database_house_village_nav = D('House_village_nav');
        if(empty($goods_arr)){
            exit(json_encode(array('err_code'=>1,'msg'=>'请至少选择一个商品！')));
        }
        
        $nowtime=time();
        foreach ($goods_arr as $gvv){
            if(isset($gvv['goods_id']) && $gvv['goods_id']){
                $saveArr=array();
                $saveArr['block_type_id']='shop_goods_seckill';
                $saveArr['type']=21;  //定制的 看 block_type_id字段来区分
                $saveArr['village_id']=$village_id;
                $saveArr['is_community_platform']=0;
                $tmpWhere=array('village_id'=>$village_id,'type'=>21,'shop_goods_type'=>$goods_type,'goods_id'=>$gvv['goods_id']);
                $navinfo=$database_house_village_nav->where($tmpWhere)->find();
                $saveArr['name']=$gvv['goods_name'];
                $saveArr['img']=isset($gvv['img']) ? $gvv['img']:'';
                $saveArr['url']=isset($gvv['url']) ? $gvv['url']:'';
                $saveArr['status']=isset($gvv['goods_status']) ? $gvv['goods_status']:0;  //0未上架 1上架 2过期
                $saveArr['url']=isset($gvv['url']) ? $gvv['url']:'';
                $saveArr['sort']=isset($gvv['sort']) ? $gvv['sort']:0;
                $saveArr['business_num']=isset($gvv['stock_num']) ? $gvv['stock_num']:0;  //库存
                $start_time=isset($gvv['active_start_time']) ? trim($gvv['active_start_time']):'';
                $saveArr['start_time']=!empty($start_time) ? strtotime($start_time):0;
                $end_time=isset($gvv['active_end_time']) ? trim($gvv['active_end_time']):'';
                $saveArr['end_time']=!empty($end_time) ? strtotime($end_time):0;
                $saveArr['shop_goods_type']=$goods_type;
                $saveArr['shop_mer_name']=$gvv['merchant_name'];
                $saveArr['shop_store_name']=$gvv['store_name'];
                $saveArr['goods_id']=$gvv['goods_id'];
                $saveArr['del_time']=0;
                $saveArr['data_extra']=json_encode($gvv,JSON_UNESCAPED_UNICODE);
                if($navinfo){
                    $database_house_village_nav->where(array('id'=>$navinfo['id']))->data($saveArr)->save();
                }else{
                    $saveArr['add_time']=$nowtime;
                    $database_house_village_nav->data($saveArr)->add();
                }
            }
        }
        exit(json_encode(array('err_code'=>0,'msg'=>'操作成功！')));
    }
    
     public function edit_shop_goods_seckill(){

         $idd=isset($_POST['idd']) ? intval($_POST['idd']):0;
         $goods_id=isset($_POST['goods_id']) ? intval($_POST['goods_id']):0;
         $sort=isset($_POST['xsort']) ? intval($_POST['xsort']):0;
         $database_house_village_nav = D('House_village_nav');
         $village_id = $this->house_session['village_id'];
         $tmpWhere=array('village_id'=>$village_id,'type'=>21,'id'=>$idd,'goods_id'=>$goods_id);
         $database_house_village_nav->where($tmpWhere)->data(array('sort'=>$sort))->save();
         exit(json_encode(array('err_code'=>0,'msg'=>'操作成功！')));
     }
    //删除
    public function del_shop_goods_seckill(){
        $idd_arr=isset($_POST['idd_arr']) ? $_POST['idd_arr']:"";
        $village_id = $this->house_session['village_id'];
        $idd_arr=$idd_arr ? array_unique($idd_arr):array();
        if(empty($idd_arr) || !is_array($idd_arr)){
            exit(json_encode(array('err_code'=>1,'msg'=>'没有可删除数据！')));
        }
        $nowtime=time();
        $database_house_village_nav = D('House_village_nav');
        $tmpWhere=array('village_id'=>$village_id,'type'=>21,'id'=>array('in',$idd_arr));
        $database_house_village_nav->where($tmpWhere)->data(array('del_time'=>$nowtime,'status'=>4))->save();
        exit(json_encode(array('err_code'=>0,'msg'=>'操作成功！')));
    }
}
?>

