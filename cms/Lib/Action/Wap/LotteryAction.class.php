<?php
class LotteryAction extends LotteryBaseAction
{
    public function index()
    {

        $token		= $this->token;
        $wecha_id	= $this->wecha_id;
        $id 		= $this->_get('id');

        $redata		= M('Lottery_record');
        $where 		= array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id);
        $record 	= $redata->where(array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id,'islottery'=>1))->find();
        if (!$record){
            $record = $redata->where($where)->order('id DESC')->find();
        }

        $Lottery = M('Lottery')->where(array('id' => $id,'token' => $token,'type' => 1,'status' => 1))->find();
// 		if ($this->wecha_id && !$this->fans && $Lottery['needreg']){
// 			$this->error('请先完善个人资料再参加活动',U('Userinfo/index',array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'redirect'=>MODULE_NAME.'/index|id:'.intval($id))));
// 		}
        $Lottery['renametel'] = $Lottery['renametel'] ? $Lottery['renametel'] : '手机号';
        $Lottery['renamesn'] = $Lottery['renamesn'] ? $Lottery['renamesn'] : 'SN码';
        $data = $Lottery;
        $data['fxinfo']=str_replace(array("\r\n","\n","\r"),' ',$data['info']);
        $data['fxinfo']=str_replace('"','“',$data['fxinfo']);
        //1.活动过期,显示结束
        //4.显示奖项,说明,时间
        if ($Lottery['statdate'] < time() && $Lottery['status'] == 0) {
            $data['end'] = 1;
            $data['endinfo'] = $Lottery['endinfo'];
            $this->assign('Dazpan',$data);
            $this->display();
            exit();
        }
        if ($Lottery['enddate'] < time()) {
            $data['end'] = 1;
            $data['endinfo'] = $Lottery['endinfo'];
            $this->assign('Dazpan', $data);
            $this->display();
            exit();
        }
        // 1. 中过奖金
        if ($record['islottery'] == 1) {
            $data['end'] = 5;
            $data['sn']	 	 = $record['sn'];
            $data['uname']	 = $record['wecha_name'];
            $data['prize']	 = $record['prize'];
            $data['tel'] 	 = $record['phone'];
        }
        //抽取次数
        $data['On'] 		= 1;
        $data['token'] 		= $token;
        $data['wecha_id']	= $wecha_id;
        $data['lid']		= $Lottery['id'];
        $data['rid']		= intval($record['id']);
        $data['usenums'] 	= $record['usenums'];
        $data['info']		= str_replace('&lt;br&gt;', '<br>', $data['info']);
        $data['endinfo']	= str_replace('&lt;br&gt;', '<br>', $data['endinfo']);
        !empty($data['starpicurl']) && $data['starpicurl'] = C('config.site_url') . $data['starpicurl'];
        $this->assign('Dazpan',$data);
        $record['id']=intval($record['id']);
        $this->assign('record', $record);
        //var_dump($data);exit();

        $now_merchant = M('Merchant')->where(array('mer_id'=>$token))->find();
        $this->assign('now_merchant', $now_merchant);
        $now_user = M('User')->where(array('uid'=>$this->wecha_id))->find();
        $this->assign('now_user',$now_user);
        $this->display();
    }


    public function winning_info()
    {

        $token		= $this->token;
        $wecha_id	= $this->wecha_id;
        $id 		= $this->_get('id');

        $redata		= M('Lottery_record');
        $record 	= $redata->where(array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id,'islottery'=>1))->select();

        $Lottery = M('Lottery')->where(array('id' => $id,'token' => $token,'type' => 1,'status' => 1))->find();

        if ($record) {
            // 处理复制到另一个数组
            $winning = array();
            $Lottery['renametel'] = $Lottery['renametel'] ? $Lottery['renametel'] : '手机号';
            $Lottery['renamesn'] = $Lottery['renamesn'] ? $Lottery['renamesn'] : 'SN码';
            $info = $Lottery;
            $Lottery['info'] = $info['info'];
            if ($Lottery['statdate'] < time() && $Lottery['status'] == 0) {
                $Lottery['end'] = 1;
            } elseif ($Lottery['enddate'] < time()) {
                $Lottery['end'] = 1;
            }
            $info['wecha_id']	= $wecha_id;
            $info['lid']		= $Lottery['id'];
            $info['info']		= str_replace('&lt;br&gt;', '<br>', $info['info']);
            $info['endinfo']	= str_replace('&lt;br&gt;', '<br>', $info['endinfo']);
            $prize_type = array(
                '1' => '一',
                '2' => '二',
                '3' => '三',
                '4' => '四',
                '5' => '五',
                '6' => '六',
                '7' => '七',
                '8' => '八',
                '9' => '九',
                '10' => '十',
                '11' => '十一',
                '12' => '十二',
            );
            foreach ($record as $val) {
                $info['sn']	 	   = $val['sn'];
                $info['uname']	   = $val['wecha_name'];
                $info['prize']	   = $val['prize'];
                $info['tel'] 	   = $val['phone'];
                $info['rid']       = intval($val['id']);
                $info['usenums']   = $val['usenums'];
                $info['sendstutas']   = $val['sendstutas'];
                $info['sendtime']   = $val['sendtime'];
                $info['prize_info']= $prize_type[$val['prize']];
                $winning[] = $info;
            }
            $this->assign('winning',$winning);
        } else {
            $this->assign('winning',array());
        }
        $this->assign('lottery',$Lottery);

        $now_merchant = M('Merchant')->where(array('mer_id'=>$token))->find();
        $this->assign('now_merchant', $now_merchant);
        $now_user = M('User')->where(array('uid'=>$this->wecha_id))->find();
        $this->assign('now_user',$now_user);
        $this->display();
    }
	
	public function getajax()
	{	
		$token 		=	$this->_get('token');
		$wecha_id	=	$this->_get('oneid');
		$id 		=	$this->_get('id');
		$rid 		= 	$this->_get('rid');
		$Lottery	=	M('Lottery')->where(array('id' => $id))->find();
		$data		=	$this->prizeHandle($token, $wecha_id, $Lottery);
		if ($data['end']==3) {
			$sn	 	 = $data['sn'];
			$uname	 = $data['wecha_name'];
			$prize	 = $data['prize'];
			$tel 	 = $data['phone'];
			$msg = "您已经中过了";
			echo '{"error":1,"msg":"'.$msg.'"}';
			exit;
		}
		if ($data['end'] == -1) {
			$msg = $data['winprize'];
			echo '{"error":1,"msg":"'.$msg.'"}';
			exit;
		}
		if ($data['end']==-2) {
			$msg = $data['winprize'];
			echo '{"error":1,"msg":"'.$msg.'"}';
			exit;
		}
		//
        if($Lottery['lottery_type'] == '1'){
            if ($data['prizetype'] >= 1 && $data['prizetype'] <= 12) {
                echo '{"success":1,"sn":"'.$data['sncode'].'","prizetype":"'.$data['prizetype'].'","usenums":"'.$data['usenums'].'"}';
            } else {
                echo '{"success":0,"prizetype":"","usenums":"'.$data['usenums'].'"}';
            }
        }
        else{
    		if ($data['prizetype'] >= 1 && $data['prizetype'] <= 6) {
    			echo '{"success":1,"sn":"'.$data['sncode'].'","prizetype":"'.$data['prizetype'].'","usenums":"'.$data['usenums'].'"}';
    		} else {
    			echo '{"success":0,"prizetype":"","usenums":"'.$data['usenums'].'"}';
    		}
        }
		exit();
	}
}
	
?>