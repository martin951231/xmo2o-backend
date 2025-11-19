<?php
class ExportFileAction extends BaseAction
{
	public function download_export_file(){
	    set_time_limit(0);
		if(empty($_SESSION['staff']) && empty($_SESSION['merchant']) && empty($_SESSION['system']) && empty($_SESSION['house'])){
			exit;
		}
        $where=array();
		$where['export_id']=intval($_GET['id']);
		if($where['export_id']<=0){
            $where['export_id']=intval($_POST['id']);
        }
		$export  = M('Export_log')->where($where)->find();
        $is_export_ing=S('house_room_export_out'. $where['export_id']);
        if($is_export_ing){
            $tmpcount=$is_export_ing+1;
            S('house_room_export_out'. $where['export_id'],$tmpcount);
        }else{
            $is_export_ing=1;
            S('house_room_export_out'. $where['export_id'],1);
        }
		if(!empty($export) && !empty($export['file_name']) && file_exists('./runtime/'.$export['file_name'])){
			if(IS_AJAX || (isset($_GET['ajax'])&&($_GET['ajax']=='village_ajax'))){
			    
                if(isset($_GET['export_from']) && in_array($_GET['export_from'],array('unitRental','buildingRoom'))){
                    $output = [
                        'status' =>  1000 ,
                        'msg' 	 => '',
                        'data' 	 => array('error_code' => 0),
                        'refresh_ticket'=>''
                    ];
                    echo json_encode($output,JSON_UNESCAPED_UNICODE);
                }else{
                    echo json_encode(array('error_code'=>0));
                }
			}else{
				$filename = $export['title'].'_'.$export['file_name'];
				$ua = $_SERVER['HTTP_USER_AGENT'];
				$ua = strtolower($ua);
                S('house_room_export_out'. $where['export_id'],null);
				if(preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
					$filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
				}
				header('Content-Disposition: attachment; filename="'.$filename.'"');


				header("Content-type:application/vnd.ms-excel");

				readfile('./runtime/'.$export['file_name']) ;

			}
		}else{
            if(function_exists("fastcgi_finish_request")){
                if(isset($_GET['export_from']) && in_array($_GET['export_from'],array('unitRental','buildingRoom'))){
                    $output = [
                        'status' =>  1000 ,
                        'msg' 	 => '',
                        'data' 	 => array('error_code' => 1),
                        'refresh_ticket'=>''
                    ];
                    echo json_encode($output,JSON_UNESCAPED_UNICODE);
                }else{
                    echo json_encode(array('error_code'=>1));
                }
                fastcgi_finish_request();
                if($is_export_ing==2 && $export['type']=='room_export'){
                    $this->exeplanhandle($where['export_id']);
                }
            }else{
                if($is_export_ing==10){
                    $this->exeplanhandle($where['export_id']);
                }
                if(isset($_GET['export_from']) && in_array($_GET['export_from'],array('unitRental','buildingRoom'))){
                    $output = [
                        'status' =>  1000 ,
                        'msg' 	 => '',
                        'data' 	 => array('error_code' => 1),
                        'refresh_ticket'=>''
                    ];
                    echo json_encode($output,JSON_UNESCAPED_UNICODE);
                }else{
                    echo json_encode(array('error_code'=>1));
                }
            }
		}
		exit;
	}

    public function exeplanhandle($export_id = 0,$current='')
    {
        if ($export_id > 0) {
            $unique_id = 'house_room_export_out' . $export_id;
            $taskPlan = M('Process_plan')->field(true)->where(array('unique_id' => $unique_id))->find();
            if ($taskPlan) {
                $eventClassName = 'plan_' . $taskPlan['file'];
                if($current && file_exists($current) && getcwd ()!==$current){
                    chdir($current);
                }
                if (file_exists(LIB_PATH . 'ORG/plan/' . $eventClassName . '.class.php')) {
                    import('@.ORG.plan.' . $eventClassName);
                    if (class_exists($eventClassName, false)) {
                        $eventClass = new $eventClassName;
                        if (method_exists($eventClass, 'runTask')) {
                            $param = unserialize($taskPlan['param']);
                            $param['plan_task_id'] = $taskPlan['id'];
                            if ($eventClass->runTask($param) !== true) {
                                return true;
                            }
                            
                        }
                    }
                }
            }
        }
        return false;
    }

    public function download_house_export_file(){
        set_time_limit(0);
        $where=array();
        $export_id=intval($_GET['id']);
        if($export_id<1){
            $export_id=intval($_POST['id']);
        }
        $output = [
            'status' =>  1000 ,
            'msg' 	 => '',
            'data' 	 => array('error_code' => 1,'error_msg'=>''),
            'refresh_ticket'=>''
        ];
        if($export_id<1){
            $output['data']['error_code']=404;
            $output['data']['error_msg']='导出数据，请重试！';
            echo json_encode($output,JSON_UNESCAPED_UNICODE);
            exit();
        }
        $where=array('export_id'=>$export_id);
        $export  = M('Export_log')->where($where)->find();
        if(empty($export)){
            $output['data']['error_code']=404;
            $output['data']['error_msg']='导出数据，请重试！';
            echo json_encode($output,JSON_UNESCAPED_UNICODE);
            exit();
        }
        $export_from = $_GET['export_from'];
        $exportFromArray = ['village_yearly_bill_export', 'village_monthly_bill_export'];
        if(!empty($export['file_name']) && (in_array($export_from, $exportFromArray) || file_exists('./v20/runtime/'.$export['file_name']))){
            if (IS_AJAX || (isset($_GET['ajax']) && ($_GET['ajax'] == 'village_ajax'))) {
                $output['data']['error_code'] = 0;
                echo json_encode($output, JSON_UNESCAPED_UNICODE);
                exit();
            } else {
                $filename = $export['title'].'_'.$export['file_name'];
                $ua = $_SERVER['HTTP_USER_AGENT'];
                $ua = strtolower($ua);
                S('house_room_export_out'. $where['export_id'],null);
                if(preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
                    $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
                }
                header('Content-Disposition: attachment; filename="'.$filename.'"');
                
                header("Content-type:application/vnd.ms-excel");

                readfile('./v20/runtime/'.$export['file_name']) ;

            }
        }else{
            echo json_encode($output,JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    public function testexport(){
        $where=array();
        $where['export_id']=intval($_GET['id']);
        if($where['export_id']<=0){
            $where['export_id']=intval($_POST['id']);
        }
        $export  = M('Export_log')->where($where)->find();
        $param= unserialize($export['param']);
        $ret=D('Order')->order_export_runtask($param);
        if($ret){
            M('Export_log')->where($where)->setField('status',1);
        }
    } 
    public function curlGet($url,$timeout=3){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 	//不需要等待返回结果，
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $result = curl_exec($ch);
        curl_close($ch);

    }
}