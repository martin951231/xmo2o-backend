<?php
class LotterynewAction extends LotteryBaseMoreAction{
	public function index(){
		$token=$this->token;
		$wecha_id	= $this->wecha_id;
		$id 		= $this->_get('id');
		if($id == ''){
			$this->error("不存在的活动");
		}
		$redata		= M('Lottery_record');
		$where 		= array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id);
		$record 	= $redata->where(array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id,'islottery'=>1))->order('time desc')->select();
		$record2 	= $redata->where($where)->order('id DESC')->find();
		
		$Lottery 	= M('Lottery')->where(array('id'=>$id,'token'=>$token,'type'=>1,'status'=>1))->find();
		if(!($Lottery)){
			$this->error("不存在的活动");
		}

		$Lottery['renametel']=$Lottery['renametel']?$Lottery['renametel']:'手机号';
		$Lottery['renamesn']=$Lottery['renamesn']?$Lottery['renamesn']:'SN码';
		if($Lottery['daynums'] > 0){
			$Lottery['numtype'] = 1;
		}
		$data=$Lottery;
		//显示奖项,说明,时间
		if ($Lottery['enddate'] < time()) {
			 $data['end'] = 1;
			 $data['endinfo'] 	= $Lottery['endinfo'];
			 $data['lid']		= $Lottery['id'];
			 $data['token'] 	= $token;
			 $data['wecha_id']	= $wecha_id;	
			 $this->assign('Dazpan',$data);
			 $this->assign('record',$record);
			 $this->display();
			 exit();
		}
		//抽取次数
		$data['On'] 		= 1;
		$data['token'] 		= $token;
		$data['wecha_id']	= $wecha_id;		
		$data['lid']		= $Lottery['id'];
		$data['usenums'] 	= $record2['usenums'];
		$data['sharecount'] = $record2['sharecount'];
		$data['sharecount_new'] = $record2['sharecount_new'];
		$data['info']=str_replace('&lt;br&gt;','<br>',$data['info']);
		$data['endinfo']=str_replace('&lt;br&gt;','<br>',$data['endinfo']);


		$result = $this->prizeHandle($token,$wecha_id,$Lottery,true);
		
		if($result == true){
			$state = array(
				'error' => 0,
				'msg' => ''
			);
		}
		if ($result['end'] == 3 || $result['end'] == 4){
			$state = array(
				'error' => 1,
				'msg' => $result['msg']
			);
		}
		if (in_array($result['end'],array(-1,-2,-3))){
			$state = array(
				'error' => 1,
				'msg' => $result['winprize']
			);
		}
		
	
		$this->assign('state',$state);
		$this->assign('Dazpan',$data);
		$this->assign('record',$record);
		$this->assign('sharekey',$this->wecha_id);
		$this->assign('siteUrl',$this->siteUrl);
        $now_user = M('User')->where(array('uid'=>$this->wecha_id))->find();
        $this->assign('now_user',$now_user);
        if($_GET['adress_id'] && $_GET['adress_id']>0){
           $address_id = $_GET['adress_id'];
        }elseif($record[count($record)-1]['address_id']>0){
            $address_id = $record[count($record)-1]['address_id'];
        }
        if($address_id){
            $user_address = D('User_adress')->field(true)->where(array('adress_id' => $address_id, 'uid' => $this->user_session['uid']))->find();
            if ($user_address) {
                $province = D('Area')->get_area_by_areaId($user_address['province'],false);
                $user_address['province_txt'] = $province['area_name'];

                $city = D('Area')->get_area_by_areaId($user_address['city'],false);
                $user_address['city_txt'] = $city['area_name'];

                $area = D('Area')->get_area_by_areaId($user_address['area'],false);
                $user_address['area_txt'] = $area['area_name'];

                $address = $user_address['province_txt'] . $user_address['city_txt'] . $user_address['area_txt'] . $user_address['adress'] . $user_address['detail'];

                if($_GET['adress_id'] && $_GET['adress_id']!=$record[count($record)-1]['address_id']){
                    $where['token'] = $_GET['token'];
                    $where['lid'] = $_GET['id'];
                    $where['islottery'] = 1;
                    $where['wecha_id'] = $this->user_session['uid'];
                    M('Lottery_record')->where($where)->save(array('address_id'=>$address_id,'address'=>$address));
                }
            }
            $this->assign('user_address',$user_address);
        }
		$this->display();
	}
	
	
	
	public function getajax(){
		
		$token 		=	$_POST['token'];
		$wecha_id	=	$_POST['oneid'];
		$id 		=	intval($_POST['id']);
		$fwy = md5($token.$wecha_id.$id.'PIGCMS'.$this->siteUrl);
		if($fwy == $_POST['fwy']){
			$Lottery=M('Lottery')->where(array('id'=>$id))->find();
			//微现场对接活动
			if($Lottery['other_source'] == 'scene' && $Lottery['scene_state'] == 0)
			{
				exit('{"error":"1","msg":"活动还没开始，感谢关注"}');
			}
				if ($Lottery['statdate']>time()){
					echo '{"error":1,"msg":"活动还没开始，感谢关注"}';
					exit;
				}
				if ($Lottery['enddate']<time()){
					echo '{"error":1,"msg":"活动已经结束，感谢关注"}';
					exit;
				}

			$data=$this->prizeHandle($token,$wecha_id,$Lottery);
			if ($data['end']==3){
				$sn	 	 = $data['sn'];
				$uname	 = $data['wecha_name'];
				$prize	 = $data['prize'];
				$tel 	 = $data['phone'];
				$msg = $data['msg'];
				echo '{"error":1,"msg":"'.$msg.'"}';
				exit;
			}
			if($data['end'] == 4){
				$msg = $data['msg'];
				echo '{"error":1,"msg":"'.$msg.'"}';
				exit;
			}
			if ($data['end']==-1){
				$msg = $data['winprize'];
				echo '{"error":1,"msg":"'.$msg.'"}';
				exit;
			}
			if ($data['end']==-2){
				$msg = $data['winprize'];
				echo '{"error":1,"msg":"'.$msg.'"}';
				exit;
			}
			if ($data['end']==-3){
				$msg = $data['winprize'];
				echo '{"error":1,"msg":"'.$msg.'"}';
				exit;
			}
			//
			if ($data['prizetype'] >= 1 && $data['prizetype'] <= 6) {
				echo '{"success":1,"sn":"'.$data['sncode'].'","prizetype":"'.$data['prizetype'].'","usenums":"'.$data['usenums'].'","rid":"'.$data['rid'].'"}';
			}else{
				echo '{"success":0,"prizetype":"","usenums":"'.$data['usenums'].'"}';
			}
			exit();
		}else{
			echo '{"error":1,"msg":"你真调皮"}';
		}
	}
}
	
?>