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
        $pigcms_id=isset($_POST['pigcms_id']) ? intval($_POST['pigcms_id']) : 0;
        $from_id=isset($_POST['area_street_id']) ? intval($_POST['area_street_id']) : 0;
        $this->from = $from;
        switch($from){
            case 'merchant':
                break;
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
                    $village_id = $_POST['village_id'];
                    if ($_POST['pigcms_id']) {
                        $bind_village_info = M('House_village_user_bind')->where(array('pigcms_id'=>$_POST['pigcms_id']))->order('`pigcms_id` DESC')->find();
                        if ($bind_village_info) {
                            $this->village_bind = $bind_village_info;
                        }
                    } elseif ($_SESSION['now_village_bind']) {
                        $this->village_bind = $_SESSION['now_village_bind'];
                    }
                    if (!$village_id && $this->village_bind) {
                        $village_id = $this->village_bind['village_id'];
                    }
                    $this->user_session = session('user');
                    $ticket =   I('ticket');
                    $device_id = trim($_POST['Device-Id']);
                    $info = ticket::get($ticket,$device_id,true);
                    if (empty($this->user_session) && $info['uid']) {
                        $user = PD('User')->get_user($info['uid']);
                        session('user', $user);
                        $this->user_session = $user;
                    }
                    if(empty($this->user_session)){
                        if ($this->village_bind) {
                            $return_url = $this->config['site_url'] . '/wap.php?g=Wap&c=House&a=village_my&village_id='.$this->village_bind['village_id'];
                            $redirect = $this->config['site_url'] . '/wap.php?g=Wap&c=Login&a=index&referer='.urlencode($return_url);
                        } else {
                            $redirect = $this->config['site_url'] . '/wap.php?g=Wap&c=Login&a=index';
                        }
                        $data = array(
                            'errorCode'=>10001,
                            'errorMsg'=>'请先进行登录',
                            'result'=>array('redirect'=>$redirect)
                        );
                        echo json_encode($data);
                        exit();
                    }elseif(empty($village_id)){
                        $data = array(
                            'errorCode'=>10001,
                            'errorMsg'=>'请先选择小区',
                            'result'=>array('redirect'=>$this->config['site_url'] . '/wap.php?g=Wap&c=House&a=village_list')
                        );
                        echo json_encode($data);
                        exit();
                    } else {
                        $this->from_id = $village_id;
                        if (0==$this->village_bind['type'] || 3==$this->village_bind['type']) {
                            $this->from_limit_type = 1;// 就是业主身份 不受限制投票
                        } else {
                            $this->from_limit_type = 0;// 就是非业主身份 受限制投票
                        }
                        if($this->village_bind && $this->village_bind['uid']>0) {
                            $this->_uid = $this->village_bind['uid'];
                        }
                        $this->from_limit_msg = '您不能进行投票,仅业主可进行投票';
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
            case 'street':
                $this->house_session = session('house_street');
                if('api'==trim($_GET['type'])){
                    // 暂未对接前端
                    $this->from_id = $from_id;
                    if ($pigcms_id) {
                        $bind_village_info = M('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->order('`pigcms_id` DESC')->find();
                        if ($bind_village_info) {
                            $this->village_bind = $bind_village_info;
                        }
                    } elseif ($_SESSION['now_village_bind']) {
                        $this->village_bind = $_SESSION['now_village_bind'];
                    }
                    if ( $this->from_id) {
                        $area_street_info = M('Area_street')->where(array('area_id'=> $this->from_id))->order('`area_id` DESC')->find();
                        if (0==$area_street_info['area_type']) {
                            $this->from = 'street';
                        } elseif (1==$area_street_info['area_type']) {
                            $this->from = 'community';
                        }
                    }
                    $this->user_session = session('user');
                    $ticket =   I('ticket');
                    $device_id = trim($_POST['Device-Id']);
                    $info = ticket::get($ticket,$device_id,true);
                    if (empty($this->user_session) && $info['uid']) {
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
                    }
                    $this->_uid = $info['uid'];
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
            case 'community':
                $this->house_session = session('house_street');
                if('api'==trim($_GET['type'])){
                    // 暂未对接前端
                    $this->from_id = $from_id;
                    if ($pigcms_id) {
                        $bind_village_info = M('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->order('`pigcms_id` DESC')->find();
                        if ($bind_village_info) {
                            $this->village_bind = $bind_village_info;
                        }
                    } elseif ($_SESSION['now_village_bind']) {
                        $this->village_bind = $_SESSION['now_village_bind'];
                    }
                    if ($from_id) {
                        $area_street_info = M('Area_street')->where(array('area_id'=>$_POST['area_id']))->order('`area_id` DESC')->find();
                        if (0==$area_street_info['area_type']) {
                            $this->from = 'street';
                        } elseif (1==$area_street_info['area_type']) {
                            $this->from = 'community';
                        }
                    }
                    $this->user_session = session('user');
                    $ticket =   I('ticket');
                    $device_id = trim($_POST['Device-Id']);
                    $info = ticket::get($ticket,$device_id,true);
                    if (empty($this->user_session) && $info['uid']) {
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