<?php
class BaseAction extends Action{
    protected $village_bind;
    protected $house_session;
    protected $from; // 来源 例如社区的 house
    protected $from_id; // 来源相关id 如果from为house 这个值就是社区id
    protected $from_limit_type; // 是否受限制投票  1 不受限制  0 受限制
    protected $from_limit_msg; // 报错提示
    protected $_uid, $DEVICE_ID;
    protected $user_session; // 登录用户信息
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
		
		if(session_status() !== PHP_SESSION_ACTIVE) session_start();
        $from = trim($_GET['from']);
        if(empty($from)){
            die('访问形式不正确');
        }
        $this->from = $from;
        switch($from){
            case 'merchant':
            case 'system':
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
                if (empty($this->_uid)) {
                    returnCode(10001, [], '当前用户未登录');
                }
                break;
            case 'house':
                $this->house_session = session('house');
                
                if('api'==trim($_GET['type'])){
                    if (isset($_POST['record_uid']) && intval($_POST['record_uid'])>0) {
                        $user = PD('User')->get_user($_POST['record_uid']);
                        $this->_uid = $user['uid'];
                    } else {
                        $this->user_session = session('user');
                        $ticket =   I('ticket');
                        $device_id = trim($_POST['Device-Id']);
                        if(empty($device_id)){
                            $device_id=trim($_POST['app_type']);
                        }
                        $info = ticket::get($ticket,$device_id,true);
                        if ($info['uid']) {
                            $user = PD('User')->get_user($info['uid']);
                            session('user', $user);
                            $this->user_session = $user;
                        }
                        if(empty($this->user_session)){
                            $redirect = $this->config['site_url'] . '/wap.php?g=Wap&c=Login&a=index';
                            $data = array(
                                'errorCode'=>10001,
                                'errorMsg'=>'请先进行登录',
                                'result'=>array('redirect'=>$redirect)
                            );
                            echo json_encode($data);
                            exit();
                        } else {
                            $this->_uid = $this->user_session['uid'];
                        }
                    }
                } elseif (empty($this->house_session)) {
                    $data = array(
                        'errorCode'=>10001,
                        'errorMsg'=>'当前用户未登录',
                        'result'=>array('redirect'=>$this->config['site_url'] . '/shequ.php?g=House&c=Login&a=index')
                    );
                    echo json_encode($data);
                    exit();
                }
                break;
            default:
                die('访问形式不正确');
        }

	}
}