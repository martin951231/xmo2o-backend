<?php

class UserSpreadMerAction extends BaseAction{
	public function __construct()
	{
		parent::__construct();
		if (empty($this->user_session)) {
			if ($this->is_app_browser) {
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$this->error_tips('请先进行登录！', U('Login/index', $location_param));
			} else {
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				redirect(U('Login/index', $location_param));
			}
		}
		$this->user_session = D('User')->get_user($this->user_session['uid']);
	}
	public function index(){
		if($this->config['open_c2b_spread_check']==1&&$this->user_session['can_c2b_spread']<>2){
            redirect(U('check'));
        }
        $uid = $this->user_session['uid'];

        $spread_count = M('Merchant')->where(array('spread_code'=>$this->user_session['spread_code']))->count();
        $spread_zhengchang_count = M('Merchant')->where(array('spread_code'=>$this->user_session['spread_code'],'status'=>1))->count();
        $where['uid'] = $this->user_session['uid'];
        $where['spread_mer_id'] = array('gt',0);
        $where['order_type'] = 'user_s_mer';
        $all_money = M('User_spread_list')->where($where)->sum('money');

        $this->assign('spread_count',$spread_count);
        $this->assign('all_money',$all_money);
        $this->assign('spread_zhengchang_count',$spread_zhengchang_count);
        $this->display();
	}

    public function spread_user_list(){
        if(!empty($_GET['uid'])){
            $user = M('User')->where(array('uid'=>$_GET['uid']))->find();
        }else{
            $user = $_SESSION['user'];
        }

        $res = D('UserSpreadMer')->get_spread_user($user['openid'],$this->user_session['uid']);
        $this->assign('res',$res);
        $this->assign('user',$user);
        $this->display();
    }

	public function mer_log(){
		$this->display();
	}

	public function mer_money_log(){
		$this->display();
	}

	//	交易记录json
	public function mer_money_json(){
		$page	=	$_POST['page'];
		$transaction	=	D('UserSpreadMer')->get_spread_list($this->user_session['uid'],$page,20);
		$transaction['count'] = count($transaction['money_list']);
		foreach($transaction['money_list'] as $k=>&$v){
			$v['time_s']	=	date('Y/m/d H:i',$v['add_time']);
			$v['money']	=	floatval($v['money']);
		}
		echo json_encode($transaction);
	}

	public function mer_spread_json(){
		$page	=	$_POST['page'];
		$transaction	=	D('UserSpreadMer')->get_list($this->user_session['uid'],$page,20);
		$where['uid'] = $this->user_session['uid'];
		$transaction['count'] = count($transaction['mer_list']);
		foreach($transaction['mer_list'] as $k=>$v){
		    $where['spread_mer_id'] = $v['mer_id'];
		    $money = floatval(M('User_spread_list')->where($where)->sum('money'));
			$transaction['mer_list'][$k]['time_s']	=	date('Y/m/d H:i',$v['reg_time']);
			$transaction['mer_list'][$k]['money']	=	$money;
		}
		echo json_encode($transaction);
	}
	
	    //审核页面
    public function check(){
        if(C('config.open_c2b_spread_check')){//开启审核权限
            if($this->user_session['can_c2b_spread']==2){//审核通过
                redirect(U('index'));
            }else{
                $this->assign('check_html',C('config.c2b_user_check_text'));
                $this->assign('can_c2b_spread',$this->user_session['can_c2b_spread']);
                $this->display();
            }
        }else{
            redirect(U('index'));
        }
    }

    //申请审核
    public function reply(){
        if(!C('config.open_c2b_spread_check')){
            $this->error_tips('暂未开启代理审核功能！');
        }
        if($this->user_session['can_c2b_spread']==1){
            $this->error_tips('您已经申请！');
        }
        if($this->user_session['can_c2b_spread']==2){
            $this->error_tips('您已经申请通过！');
        }
        $r = M('User')->where(array('uid'=>$this->user_session['uid']))->save(array('can_c2b_spread'=>1,'can_c2b_spread_reply_time'=>time()));
        if($r){
            $this->success_tips('申请成功',U('index'));
        }else{
            $this->error_tips('申请失败，请稍后再试');
        }
    }
}