<?php
class User_score_listModel extends Model{
	/*增加记录行数*/
	public function add_row($uid,$type,$score,$msg,$record_ip = true,$clean=false,$time = 0,$param=array(),$is_erp_callback=false){
		if(!$time){
			$time=  time();
		}
        $data_user_score_list['uid']   = $uid;
        $data_user_score_list['type']  = $type;
        $data_user_score_list['score'] = $score;
        $data_user_score_list['desc']  = $msg;
        $data_user_score_list['time']  = $time;
		$param['mer_id']  &&  $data_user_score_list['mer_id']  = $param['mer_id'];
		$param['store_id']  &&  $data_user_score_list['store_id']  = $param['store_id'];
		$param['score_type']  &&  $data_user_score_list['score_type']  = $param['score_type'];
		$param['order_id']  &&  $data_user_score_list['order_id']  = $param['order_id'];
		$param['order_type']  &&  $data_user_score_list['order_type']  = $param['order_type'];
		if(strpos($msg,L_('返现'))!==false){
			$data_user_score_list['is_pay_back'] = 1;
		}
		if($type==1) {
			if( C('config.score_end_days')!=0){
				$data_user_score_list['end_time']     = $_SERVER['REQUEST_TIME'] + C('config.score_end_days') * 86400;
			}
			$data_user_score_list['used_count']   = 0;
			$data_user_score_list['clean_time']   = 0;
			$data_user_score_list['clean_count']  = 0;
			$data_user_score_list['clean_status'] = 0;
		}else if($type==2 && !$clean){
			$where['type'] = 1;
			$where['uid'] = $uid;
			$where['clean_status'] = 0;
			$where['_string'] = 'used_count < score';
			$score_list = $this->where($where)->select();
			foreach ($score_list as $index => $item) {
				$date=array();
				$item['score_have'] = $item['score']-$item['used_count'];
				if($score>$item['score_have']){
					$date['used_count'] = $item['score'];
					$date['clean_status'] = 3;
					$score-=$item['score_have'];
					$this->where(array('id'=>$item['id']))->save($date);

				}else if($score<=$item['score_have']){
					$date['used_count'] = $item['used_count']+$score;
					if($score==$item['score_have']){
						$date['clean_status'] = 3;
					}
					$this->where(array('id'=>$item['id']))->save($date);

					break;
				}
			}
		}
		if($record_ip){
			$data_user_score_list['ip'] = get_client_ip(0);
		}
		if($this->data($data_user_score_list)->add()){
            //积分变动同步到淼迈云ERP
            $miaomaiyun_member_id = M('User')->where(['uid' => $uid])->getField('miaomaiyun_member_id');
            if (!$is_erp_callback && $miaomaiyun_member_id) {
                $data = [
                    'params' => json_encode(['table' => 'user_score_list', 'uid' => $uid, 'desc' => $msg, 'score' => $type == 1 ? $score : $score * -1]),
                    'status' => 0,
                    'create_time' => time()
                ];
                M('Miaomaiyun_erp_sync_task')->add($data);
            }

			return true;
		}else{
			return false;
		}
	}
	/*获取列表*/
	public function get_list($uid){
		$condition_user_score_list['uid'] = $uid;
		$_GET['page'] = isset($_POST['page'])?$_POST['page']:$_GET['page'];
		import('@.ORG.user_page');
		$count = $this->where($condition_user_score_list)->count();
		$p = new Page($count,10);
		$return['score_list'] = $this->field(true)->where($condition_user_score_list)->order('`time` DESC')->limit($p->firstRow,$p->listRows)->select();
		if($_GET['page'] >  $p->totalPage &&  $p->totalPage>0){
			$return['score_list'] = array();
		}
		$return['pagebar'] = $p->show();
		return $return;
	}

	//清理score
	public function clean_user_score(){
		$where['type'] = 1;
		$where['clean_status'] = 0;
		$where['clean_time'] = 0;
		//$where['end_time'] = array('elt',time());
		$time = time();
		$where['_string'] = "user_score>0 AND used_count < score AND end_time > 0 AND end_time <={$time}";
		$clean_score_list = $this->field('id,uid,score,clean_count,used_count')->where($where)->select();
		$user_score =array();

		foreach ($clean_score_list as $c) {
			//if($c['uid']!=1358674) continue;
			$date['clean_count'] = $c['score']-$c['used_count'];
			$date['clean_time'] =$_SERVER['REQUEST_TIME'];
			$date['clean_status'] = 1;
			$user_score[$c['uid']]+=$date['clean_count'];
			$this->where(array('id'=>$c['id']))->save($date);
		}
		foreach ($user_score as $key=>$v) {
			D('User')->use_score($key,$v,L_('积分清零'),1);
		}

	}

	//public function next_score_clean_count($conditon){
    //
	//}
	public function get_all_score(){
		$where['status'] = 1;
		return M('User')->where($where)->sum('score_count');
	}


	//获取一条记录
	public function get_one_score($where){		
		$score = $this->where($where)->find();		
		return $score;
	}
	
	//用户退款时退掉消费的积分
    public function back_score($business,$business_oid)
    {
        if(!C('customization.back_order_user_score') || $business == 'shop'){
            return false;
        }
        fdump('--start--',123123,1);
        $type = $business;
        $business = $business == 'mall3' ? 'mall' : $business;
        //查询订单
        $systemOrder = M('System_order')->where(['order_id'=>$business_oid,'type'=>$type])->field('uid,mer_id,store_id')->find();
        if(!$systemOrder){
            return false;
        }
        //查询用户此订单是否获取的积分数量
        $add = $this->where(['order_id'=>$business_oid,'order_type'=>$business,'type'=>1])->sum('score');
        $subtract = $this->where(['order_id'=>$business_oid,'order_type'=>$business,'type'=>2])->sum('score');
        $setScore = $add - $subtract;
        if($setScore <= 0){//不用操作
            return true;
        }
        //2020/10/9 start 业主房屋名下所有住客所得积分 归业主所有
        //小区积分开关 （1开启 0关闭）
        $house_score_switch = C('config.house_score_switch');
        $score_count_res = 0;
        $uid = $systemOrder['uid'];
        $score = $setScore;
        //小区积分开关开启则执行
        if($house_score_switch == 1) {
            $score_count = D('User')->houseIntegral($uid, $score, 0);//给业主减少积分
            $score_count_res = $score_count['owner_uid'];
            fdump(['$score_count'=>$score_count],123123,1);
        }
        if(!$score_count_res)
        {
            fdump([M('User')->where(['uid'=>$uid])->field('score_count')->find(),$score],123123,1);
            $condition_user['uid'] = $uid;
            $score_count_res = M('User')->where($condition_user)->setDec('score_count',$score);
            fdump([11,M('User')->getLastSql(),M('User')->where(['uid'=>$uid])->field('score_count')->find()],123123,1);
        }
        if($score_count_res){
			$score_desc = L_('用户退单，扣除X1', ['X1' => C('config.score_name')]);
			$business_alias = [
				'shop' => C('config.shop_alias_name'),
				'mall' => C('config.mall_alias_name_new'),
				'group' => C('config.group_alias_name'),
				'appoint' => C('config.appoint_alias_name'),
				'village_group' => C('config.village_group_alias'),
				'dining' => C('config.meal_alias_name'),
			];
			if ($business && isset($business_alias[$business])) {
				$score_desc = L_('X1订单退款,扣除X2', ['X1' => $business_alias[$business], 'X2' => C('config.score_name')]);
			}
			
            $this->add_row($systemOrder['uid'],2,$setScore,$score_desc,true,false,0,[
                'mer_id' => $systemOrder['mer_id'],
                'store_id' => $systemOrder['store_id'],
                'order_id' => $business_oid,
                'order_type' => $business
            ]);
            //执行减少用户积分后的钩子
            empty($now_user) && $now_user = M('User')->where(['uid'=>$uid])->find();
            $desc = '用户退单，扣除积分';
            $hook_result = hook::hook_exec('user.use_user_score_after',['uid'=>$now_user['uid'],'user'=>$now_user,'score'=>$score,'desc'=>$desc]);
            if(C('config.nmgzhcs_appid')){
                $now_user = D('User')->get_user($uid);
                if($now_user['nmgzhcs_openid']){
                    D('User')->nmgzhcs_score($now_user['nmgzhcs_openid'],-1 * $score,$desc);
                }
            }
            fdump('--end--',123123,1);
            return true;
        }else{
            fdump_sql(['business'=>$business,'business_oid'=>$type,'msg' => L_("添加X1失败！请联系管理员协助解决。",array("X1" => C('config.score_name')))],'useScore');
        }
    }
}
?>