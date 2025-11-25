<?php
class BaseAction extends Action{
    protected $_uid, $DEVICE_ID;


	protected function _initialize(){
		//基类
		$plugin_base = include(PLUGIN_PATH.'../plugin_public/plugin_base.php');
		//基类公共参数
		foreach($plugin_base['action'] as $key => $value){
			$this->$key = $value;
		}
		foreach($plugin_base['tpl'] as $key => $value){
			$this->assign($key,$value);
		}
        if(empty($_POST)){
            $input_post = file_get_contents('php://input');
            $_POST = json_decode($input_post,true);
            if(!empty($_POST)){
                $_REQUEST = array_merge($_REQUEST,$_POST);
            }
        }

        $this->DEVICE_ID = trim($_POST['Device-Id']);
        if(empty($this->DEVICE_ID)){
            $this->DEVICE_ID	=	trim($_POST['Device']);
        }
        if(empty($this->DEVICE_ID)){
            $this->DEVICE_ID	=	trim(I('Device-Id'));
        }

        // 处理登录信息
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $this->_uid = $info['uid'];
        }

    }

    public function get_encrypt_key($array,$app_key){
        $new_arr = array();
        ksort($array);
        foreach($array as $key=>$value){
            $new_arr[] = $key.'='.$value;
        }
        $new_arr[] = 'app_key='.$app_key;

        $string = implode('&',$new_arr);
        return md5($string);
    }
}