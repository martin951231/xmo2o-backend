<?php
class LotteryBaseMoreAction extends BaseAction{
	public function __construct(){
        parent::__construct();

        if(empty($this->user_session)){
            $this->error_tips('请先进行登录！',U('Login/index'));
        }
        $this->wecha_id = $this->user_session['uid'];
        $this->assign('wecha_id', $this->wecha_id);
		//判断是否玩过
		$share =  M('Lottery_record')->where(array('token'=>$this->token,'wecha_id'=>$_GET['sharekey'],'lid'=>(int)$_GET['id']))->order('id DESC')->find();
		//防止重复进入
		if($this->wecha_id != $_GET['sharekey'] && $_GET['sharekey'] != "" && !empty($share) && !M('lottery_share')->where(array('token'=>$this->token,'lottery_id'=>(int)$_GET['id'],'share_wechaid'=>$_GET['sharekey'],'opener_wechaid'=>$this->wecha_id))->find()){
			$newin = array();
			$newin['lottery_id'] = (int)$_GET['id'];
			$newin['share_wechaid'] = trim($_GET['sharekey']);
			$newin['opener_wechaid'] = $this->wecha_id;
			$newin['open_time'] = time();
			$newin['token'] = $this->token;
			M('lottery_share')->add($newin);
			M('lottery_record')->where(array('token'=>$this->token,'wecha_id'=>$_GET['sharekey'],'lid'=>(int)$_GET['id']))->save(array('sharecount_new'=>array('exp','sharecount_new+1')));
		}
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $proArr
	 * @param unknown_type $total 预计参与人数
	 * @return unknown
	 */
	protected function get_rand($proArr,$total) {
		    $result = 7;
			if(!($total>0)){
			    return $result;
			}
		    $randNum = mt_rand(1, $total);

		    foreach ($proArr as $k => $v) {

		    	if ($v['v']>0){//奖项存在或者奖项之外
		    		if ($randNum>$v['start']&&$randNum<=$v['end']){
		    			$result=$k;
		    			break;
		    		}
		    	}
		    }

		    return $result;
	}
	public function prizeHandle($token,$wecha_id,$Lottery,$check = false){

		$this->lottory_record_db=M('Lottery_record');
		$this->lottory_db=M('Lottery');
		$now=time();
		$id 		= $Lottery['id'];
		//
		$redata		= $this->lottory_record_db;
		$where 		= array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id);

		$record 	= $redata->where($where)->find();


		//0.判断是否有该用户记录,没有则记录
		if($record == Null){
			$record = $where;
			$record['usenums']=0;
		}
			if ($Lottery['enddate'] < $now) { //过期
				$data['end'] = 2;
				$data['endinfo'] = $Lottery['endinfo'];
				$data['endimg']  = empty($Lottery['endpicurl']) ? 1 : $Lottery['endpicurl'];
			}else{
				//最多抽奖次数
				$LotteryedRecordWhere=array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id,'islottery'=>1);
				$prizedCount=$redata->where($LotteryedRecordWhere)->count();
				$maxPrizeCount=intval($Lottery['maxgetprizenum']);
				//$maxPrizeCount=$maxPrizeCount?$maxPrizeCount:1;
				if ($prizedCount>=$maxPrizeCount){
					$data['end'] = 3;
					$data['msg'] = "您已经中过".$prizedCount."次奖了，不能再抽取了，谢谢";
					$data['wxname']=$record['wecha_name'];
					$data['wecha_name']=$record['wecha_name'];
					$data['sn']  = $record['sn'];
					$data['myprize'] 	= $this->getPrizeName($Lottery,$record['prize']);
					$data['prize'] 	= $record['prize'];
				}else {
					$year=date('Y',$now);
					$month=date('m',$now);
					$day=date('d',$now);
					$firstSecond=mktime(0,0,0,$month,$day,$year);
					$lastSecond=mktime(23,59,59,$month,$day,$year);
					//$lotteryDayWhere = 'token=\''.$token.'\' AND wecha_id=\''.$wecha_id.'\' AND lid='.$id.' AND time>'.$firstSecond.' AND time<'.$lastSecond.'islottery=1';
					$lotteryDayWhere['token'] = $token;
					$lotteryDayWhere['wecha_id'] = $wecha_id;
					$lotteryDayWhere['lid'] = $id;
					$lotteryDayWhere['islottery'] = 1;
					$lotteryDayWhere['time'] = array(array('gt',$firstSecond),array('lt',$lastSecond),'and');
					$thisDayLotteryNum = $redata->where($lotteryDayWhere)->count();
					$thisDayLottery = $redata->where($lotteryDayWhere)->order('time desc')->find();
					//限制每人每天只中一次奖
					if($Lottery['isdaylottery'] == 1 && $thisDayLotteryNum >= 1){
						$data['end'] = 4;
						$data['msg'] = "您在".date("Y年m月d日H点i分",$thisDayLottery['time'])."已经中过了，过24小时后再来吧！";
						$data['wxname']=$record['wecha_name'];
						$data['wecha_name']=$record['wecha_name'];
						$data['sn']  = $record['sn'];
						$data['myprize'] 	= $this->getPrizeName($Lottery,$record['prize']);
						$data['prize'] 	= $record['prize'];
					}else{
						//是否已经够次数
						if ($record['usenums'] >= $Lottery['canrqnums'] ) {
							$data['end'] = -1;
							$data['prizetype'] = 7; //啥都没了
							$data['zjl']	  = 0;
							$data['usenums']  = $record['usenums'];
							$data['winprize']	   = "抽奖次数已经用完";
							//exit;
						}else{
							//次数限制
							//numtype 抽奖次数限制：0没有限制  1每日限制daynums次 2分享邀请限制sharebeforenum   
							/*if($Lottery['daynums']){
								$Lottery['numtype'] = 1;
							}*/
							switch($Lottery['numtype']){
								case 1:
									$dayWhere='token=\''.$token.'\' AND wecha_id=\''.$wecha_id.'\' AND lid='.$id.' AND time>'.$firstSecond.' AND time<'.$lastSecond;
									$thisDayNums=$redata->where($dayWhere)->count();
									$thisDayNums=intval($thisDayNums);
									if ($Lottery['daynums']&&$thisDayNums>=$Lottery['daynums']){
										$isnum = 1;
									}else{
										$isnum = 0;
									}
								break;
								case 2:
									$shareWhere = 'token=\''.$token.'\' AND wecha_id=\''.$wecha_id.'\' AND lid='.$id.' AND sharecount_new>0';
									$thisShareCount = $redata->where($shareWhere)->getField('sharecount_new');
									$thisShareCount = $thisShareCount?$thisShareCount:0;
									$playcount = (floor($thisShareCount/$Lottery['shareupnum']))+$Lottery['sharebeforenum'];
									if($record['usenums'] >= $playcount && $Lottery['shareupnum']){
										$isnum = 2;
									}else{
										$isnum = 0;
									}
								break;
								case 0:
									$isnum = 0;
								break;
							}

							if($isnum){
								switch($isnum){
									case 1:
										$data['end'] = -2; //
										$data['zjl']	  = 0;
										$data['winprize']	   = "今天已经抽了".$thisDayNums."次了，没名额了，明天再来吧";
									break;
									case 2:
										$data['end'] = -3;
										$data['zjl'] = 0;
										$data['winprize'] = "还要成功增加".((($record['usenums']-$Lottery['sharebeforenum']+1)*$Lottery['shareupnum'])-$thisShareCount)."次才能抽奖哦~";
										$data['sharediff'] = ((($record['usenums']-$Lottery['sharebeforenum']+1)*$Lottery['shareupnum'])-$thisShareCount);
									break;
								}
							}else {
								if($check == true){
									return true;
								}
                                //如果设置了积分抽奖扣除相应的积分
                                //判断积分数量是否正确
                                $score_used_count = $Lottery['costscore'];
                                $now_user = M('User')->where(array('uid'=>$this->user_session['uid']))->find();
                                if($score_used_count && $now_user['score_count'] < $score_used_count){
                                    return array('end' => -2, 'winprize' => '您没有足够的积分抽奖了！');
                                }

                                if ($score_used_count>0) {
                                    $res = D('User')->user_score($this->user_session['uid'], $score_used_count, '参与幸运大转盘抽奖扣除积分');
                                    if ($res['error_code']) {
                                        return array('end' => -2, 'winprize' => $res['msg']);
                                    }
                                }
								//中奖时间间隔
								$thislotterylist = $redata->where($LotteryedRecordWhere)->order('time desc')->select();
								$lastthislottery = $thislotterylist[0];
								$timecha = time() - $lastthislottery['time'];

								//每人每次中奖时间间隔
								if(!empty($lastthislottery) && $Lottery['timespan'] > 0 && $timecha < ($Lottery['timespan'] * 60)){
									$prizeType=7;  
								}else{
									//3.没有领过,次数没达到,开始随机发放优惠券
									
									if($Lottery['fistlucknums']  == $Lottery['fistnums'] &&
									$Lottery['secondlucknums'] == $Lottery['secondnums'] &&
									$Lottery['thirdlucknums']  == $Lottery['thirdnums'] &&
									$Lottery['fourlucknums']  == $Lottery['fournums'] &&
									$Lottery['fivelucknums']  == $Lottery['fivenums'] &&
									$Lottery['sixlucknums']  == $Lottery['sixnums']
									){
										$prizeType=7;	//领完了吧

									}else{
										//cheat
										$cheat=M('Lottery_cheat')->where(array('lid'=>$id,'wecha_id'=>$wecha_id))->find();
										if ($cheat){
											$lottery_num = array(
												1 => 'fist',
												2 => 'second',
												3 => 'third',
												4 => 'four',
												5 => 'five',
												6 => 'six'
											);
											if($Lottery[''.$lottery_num[intval($cheat['prizetype'])].'lucknums'] >= $Lottery[''.$lottery_num[$cheat['prizetype']].'nums']){
												$prizeType=7;
											}else{
												$prizeType=intval($cheat['prizetype']);
											}
										}else {
											$prizeType=intval($this->get_prize($Lottery,$wecha_id));
										}
									}
									$this->lottory_db->where(array('id'=>$id))->setInc('joinnum');
								}
								//排除没有设置的优惠券
								//奖品数 != 已经领取该奖品数 => 还有奖品
								$prizeType=intval($prizeType);
								switch ($prizeType){
									case 1:
										$count_fist = M('lottery_record')->where(array('token'=>$token,'lid'=>$id,'prize'=>1,'islottery'=>1))->count;
										if($Lottery['fistnums'] <= $count_fist){
											$this->prizeHandle($token,$wecha_id,$Lottery);exit;
										}
									break;
									case 2:
										$count_second = M('lottery_record')->where(array('token'=>$token,'lid'=>$id,'prize'=>2,'islottery'=>1))->count;
										if($Lottery['secondnums'] <= $count_second){
											$this->prizeHandle($token,$wecha_id,$Lottery);exit;
										}
									break;
									case 3:
										$count_third = M('lottery_record')->where(array('token'=>$token,'lid'=>$id,'prize'=>3,'islottery'=>1))->count;
										if($Lottery['thirdnums'] <= $count_third){
											$this->prizeHandle($token,$wecha_id,$Lottery);exit;
										}
									break;
									case 4:
										$count_four = M('lottery_record')->where(array('token'=>$token,'lid'=>$id,'prize'=>4,'islottery'=>1))->count;
										if($Lottery['fournums'] <= $count_four){
											$this->prizeHandle($token,$wecha_id,$Lottery);exit;
										}
									break;
									case 5:
										$count_five = M('lottery_record')->where(array('token'=>$token,'lid'=>$id,'prize'=>5,'islottery'=>1))->count;
										if($Lottery['fivenums'] <= $count_five){
											$this->prizeHandle($token,$wecha_id,$Lottery);exit;
										}
									break;
									case 6:
										$count_six = M('lottery_record')->where(array('token'=>$token,'lid'=>$id,'prize'=>6,'islottery'=>1))->count;
										if($Lottery['sixnums'] <= $count_six){
											$this->prizeHandle($token,$wecha_id,$Lottery);exit;
										}
									break;
								}
								
								switch ($prizeType){
									default:
										$data['prizetype'] = 7; //啥都没了
										$data['zjl']	  = 0;
										$data['winprize']	   = "谢谢参与";
										$isLottery=0;
										$data['sncode']    = '';
										break;
									case 1:
										$data['prizetype'] = 1;
										$data['sncode'] = uniqid();
										$data['winprize']	   = $Lottery['fist'];
										$data['zjl']	   = 1;
										$this->lottory_db->where(array('id'=>$id))->setInc('fistlucknums');
										$isLottery=1;
										break;
									case 2:
										$data['prizetype'] = 2;
										$data['winprize']  = $Lottery['second'];
										$data['zjl']	   = 1;
										$data['sncode']    = uniqid();
										$this->lottory_db->where(array('id'=>$id))->setInc('secondlucknums');
										$isLottery=1;
										break;
									case 3:
										$data['prizetype'] = 3;
										$data['winprize']	   = $Lottery['third'];
										$data['zjl']	   = 1;
										$data['sncode'] = uniqid();
										$this->lottory_db->where(array('id'=>$id))->setInc('thirdlucknums');
										$isLottery=1;
										break;
									case 4:
										$data['prizetype'] = 4;
										$data['winprize']	   = $Lottery['four'];
										$data['zjl']	   = 1;
										$data['sncode'] = uniqid();
										$this->lottory_db->where(array('id'=>$id))->setInc('fourlucknums');
										$isLottery=1;
										break;
									case 5:
										$data['prizetype'] = 5;
										$data['winprize']	   = $Lottery['five'];
										$data['zjl']	   = 1;
										$data['sncode'] = uniqid();
										$this->lottory_db->where(array('id'=>$id))->setInc('fivelucknums');
										$isLottery=1;
										break;
									case 6:
										$data['prizetype'] = 6;
										$data['winprize']	   = $Lottery['six'];
										$data['zjl']	   = 1;
										$data['sncode'] = uniqid();
										$this->lottory_db->where(array('id'=>$id))->setInc('sixlucknums');
										$isLottery=1;
										break;
								}
								//
								$snwhere = array('token'=>$token,'lid'=>intval($id),'wecha_id'=>'','islottery'=>0,'time'=>0,'prize'=>intval($data['prizetype']));
								$check = $this->lottory_record_db->where($snwhere)->find();
								if ($isLottery && $check){
									$data['sncode']=$check['sn'];
									$this->lottory_record_db->where(array('sn'=>$data['sncode'],'lid'=>$id,'token'=>$token))->save(array('wecha_id'=>$wecha_id,'usenums'=>$record['usenums'],'islottery'=>$isLottery,'wecha_name'=>'','phone'=>'','sn'=>$data['sncode'],'time'=>$now,'sendtime'=>0));
									$rid = $check['id'];
								}else {
									$rid = $this->lottory_record_db->add(array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id,'usenums'=>$record['usenums'],'islottery'=>$isLottery,'wecha_name'=>'','phone'=>'','sn'=>$data['sncode'],'time'=>$now,'prize'=>intval($data['prizetype']),'sendtime'=>0,'sharecount' => intval($record['sharecount']),'sharecount_new'=>intval($record['sharecount_new'])));
								}
								//
								$this->lottory_record_db->where(array('lid'=>$id,'wecha_id'=>$wecha_id))->setInc('usenums');
								$record['usenums']=intval($record['usenums'])+1;

							}

						}//以上没领过
					}
				}
			}
		//}
		//
		$record 	= $redata->where(array('wecha_id'=>$wecha_id,'islottery'=>1,'lid'=>$id,'id'=>$rid))->find();
		if (!$record){
			$record 	= $redata->where(array('wecha_id'=>$wecha_id,'islottery'=>0,'lid'=>$id))->find();
		}
		$data['rid']		= intval($record['id']);
		$data['phone']		= $record['phone'];
		$data['sn']		= $record['sn'];
		$data['usenums']	= $record['usenums'];
		$data['sendtime']	= $record['sendtime'];

		return $data;
	}
	protected function get_prize($Lottery,$wecha_id){
		$id=intval($Lottery['id']);
		$lottery_db=M('Lottery');
		$Lottery=M('Lottery')->where(array('id'=>$id))->find();
		$joinNum=$Lottery['joinnum'];
		//
		$firstNum=intval($Lottery['fistnums'])-intval($Lottery['fistlucknums']);
		$secondNum=intval($Lottery['secondnums'])-intval($Lottery['secondlucknums']);
		$thirdNum=intval($Lottery['thirdnums'])-intval($Lottery['thirdlucknums']);
		$fourthNum=intval($Lottery['fournums'])-intval($Lottery['fourlucknums']);
		$fifthNum=intval($Lottery['fivenums'])-intval($Lottery['fivelucknums']);
		$sixthNum=intval($Lottery['sixnums'])-intval($Lottery['sixlucknums']);
		$multi=intval($Lottery['canrqnums']);//最多抽奖次数
		$prize_arr = array(
			'0' => array('id'=>1,'prize'=>'一等奖','v'=>$firstNum,'start'=>0,'end'=>$firstNum),
			'1' => array('id'=>2,'prize'=>'二等奖','v'=>$secondNum,'start'=>$firstNum,'end'=>$firstNum+$secondNum),
			'2' => array('id'=>3,'prize'=>'三等奖','v'=>$thirdNum,'start'=>$firstNum+$secondNum,'end'=>$firstNum+$secondNum+$thirdNum),
			'3' => array('id'=>4,'prize'=>'四等奖','v'=>$fourthNum,'start'=>$firstNum+$secondNum+$thirdNum,'end'=>$firstNum+$secondNum+$thirdNum+$fourthNum),
			'4' => array('id'=>5,'prize'=>'五等奖','v'=>$fifthNum,'start'=>$firstNum+$secondNum+$thirdNum+$fourthNum,'end'=>$firstNum+$secondNum+$thirdNum+$fourthNum+$fifthNum),
			'5' => array('id'=>6,'prize'=>'六等奖','v'=>$sixthNum,'start'=>$firstNum+$secondNum+$thirdNum+$fourthNum+$fifthNum,'end'=>$firstNum+$secondNum+$thirdNum+$fourthNum+$fifthNum+$sixthNum),
			'6' => array('id'=>7,'prize'=>'谢谢参与','v'=>(intval($Lottery['allpeople']))*$multi-($firstNum+$secondNum+$thirdNum+$fourthNum+$fifthNum+$sixthNum),'start'=>$firstNum+$secondNum+$thirdNum+$fourthNum+$fifthNum+$sixthNum,'end'=>intval($Lottery['allpeople'])*$multi)
		);
		//
		foreach ($prize_arr as $key => $val) {
			$arr[$val['id']] = $val;
		}
		//-------------------------------
		//随机抽奖[如果预计活动的人数为1为各个奖项100%中奖]
		//-------------------------------
		if ($Lottery['allpeople'] == 1) {

			if ($Lottery['fistlucknums'] < $Lottery['fistnums']) {
				$prizetype = 1;
			}else{
				$prizetype = 7;
			}

		}else{
			$prizetype = $this->get_rand($arr,(intval($Lottery['allpeople'])*$multi)-$joinNum);
		}

		//$winprize = $prize_arr[$rid-1]['prize'];
		switch($prizetype){
			case 1:

				if ($Lottery['fistlucknums'] >= $Lottery['fistnums']) {
					 $prizetype = '';
					 //$winprize = '谢谢参与';
				}else{

					$prizetype = 1;
				    //$lottery_db->where(array('id'=>$id))->setInc('fistlucknums');
				}
			break;

			case 2:
				if ($Lottery['secondlucknums'] >= $Lottery['secondnums']) {
						$prizetype = '';
						//$winprize = '谢谢参与';
				}else{
					//判断是否设置了2等奖&&数量
					if(empty($Lottery['second']) && empty($Lottery['secondnums'])){
						$prizetype = '';
						//$winprize = '谢谢参与';
					}else{ //输出中了二等奖
						$prizetype = 2;
						//$lottery_db->where(array('id'=>$id))->setInc('secondlucknums');
					}

				}
				break;

			case 3:
				if ($Lottery['thirdlucknums'] >= $Lottery['thirdnums']) {
					 $prizetype = '';
					// $winprize = '谢谢参与';
				}else{
					if(empty($Lottery['third']) && empty($Lottery['thirdnums'])){
						 $prizetype = '';
						// $winprize = '谢谢参与';
					}else{
						$prizetype = 3;
						//$lottery_db->where(array('id'=>$id))->setInc('thirdlucknums');
					}

				}
				break;

			case 4:
				if ($Lottery['fourlucknums'] >= $Lottery['fournums']) {
					  $prizetype =  '';
					// $winprize = '谢谢参与';
				}else{
					 if(empty($Lottery['four']) && empty($Lottery['fournums'])){
					   	$prizetype =  '';
					 	//$winprize = '谢谢参与';
					 }else{
					 	$prizetype = 4;
						//$lottery_db->where(array('id'=>$id))->setInc('fourlucknums');
					 }
				}
			break;

			case 5:
				if ($Lottery['fivelucknums'] >= $Lottery['fivenums']) {
					 $prizetype =  '';
					 //$winprize = '谢谢参与';
				}else{
					if(empty($Lottery['five']) && empty($Lottery['fivenums'])){
						$prizetype =  '';
					 	//$winprize = '谢谢参与';
					}else{
						$prizetype = 5;
						//$lottery_db->where(array('id'=>$id))->setInc('fivelucknums');
					}
				}
			break;

			case 6:
				if ($Lottery['sixlucknums'] >= $Lottery['sixnums']) {
					 $prizetype =  '';
					// $winprize = '谢谢参与';
				}else{
					 if(empty($Lottery['six']) && empty($Lottery['sixnums'])){
					 	$prizetype =  '';
					 	//$winprize = '谢谢参与';
					 }else{
					 	$prizetype = 6;
						//$lottery_db->where(array('id'=>$id))->setInc('sixlucknums');
					 }

				}
			break;

			default:
					$prizetype =  '';
					//$winprize = '谢谢参与';

					break;
		}
//		if (intval($prizetype) && $prizetype<7 && $prizetype>0){
//			//中奖了 - 模板消息通知
//
//			  $model = new templateNews();
//
//			  $tempKey = 'TM00695';
//			  foreach ($prize_arr as $kk => $vv){
//			  	if($vv['id'] == $prizetype){
//			  		$result = '恭喜您在本次活动中获得了'.$vv['prize'];
//			  	}
//			  }
//			 switch($Lottery['type']){
//				 case 10:
//					$from = "Jiugong";
//				 break;
//				 case 3:
//					$from = "Coupon";
//				 break;
//				 case 2:
//					$from = "Guajiang";
//				 break;
//				 case 4:
//					$from = "LuckyFruit";
//				 break;
//				 case 5:
//					$from = "GoldenEgg";
//				 break;
//				 default:
//					$from = "Lottery";
//				 break;
//			 }
//			 $href = str_replace(array('/wx/'), '/', $this->siteUrl).U('Wap/'.$from.'/index',array('token'=>$this->_get('token'),'wecha_id'=>$wecha_id,'id'=>$Lottery['id']));
//			  $dataArr = array(
//			  	'href'      => $href,
//                'wecha_id'  => $wecha_id,
//				'title' 	=> '中奖通知',
//				'headinfo' 	=> '恭喜您中奖了！',
//				'program' 	=> $Lottery['title'],
//				'result' 	=> $result,
//				'remark' 	=> '请您尽快来领奖！'
//			);
//
//
//			  $model->sendTempMsg($tempKey,$dataArr);
//
//
//
//		}


		return $prizetype;
	}
	//中奖后填写信息
	public function add(){
		$this->lottory_record_db=M('Lottery_record');
		$this->lottory_db=M('Lottery');
		 if($_POST['action'] ==  'add' ){
			$lid 				= intval($this->_post('lid'));
			$wechaid 			= $this->wecha_id;
			if (!$wechaid){
				$wechaid=$this->_post('wechaid');
			}
			$data['phone'] 		= $this->_post('tel');
			//$data['wecha_name'] = $this->_post('wxname');
			$data['wecha_name'] = M('userinfo')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->getField('wechaname');
			$rid=intval($this->_post('rid'));
			$address_id = $this->_post('address_id');
			if($address_id){
				 $user_address = D('User_adress')->field(true)->where(array('adress_id' => $address_id, 'uid' => $this->user_session['uid']))->find();
				if ($user_address) {
                    $province = D('Area')->get_area_by_areaId($user_address['province'], false);
                    $user_address['province_txt'] = $province['area_name'];

                    $city = D('Area')->get_area_by_areaId($user_address['city'], false);
                    $user_address['city_txt'] = $city['area_name'];

                    $area = D('Area')->get_area_by_areaId($user_address['area'], false);
                    $user_address['area_txt'] = $area['area_name'];

                    $address = $user_address['province_txt'] . $user_address['city_txt'] . $user_address['area_txt'] . $user_address['adress'] . $user_address['detail'];
                    $data['address'] = $address;
                    $data['address_id'] = $address_id;
                }
			}
			$rollback = $this->lottory_record_db->where(array('lid'=> $lid,'wecha_id'=>$wechaid,'id'=>$rid))->save($data);
			$thisRecord = $this->lottory_record_db->where(array('lid'=> $lid,'wecha_id'=>$wechaid,'id'=>$rid))->find();
			$record=$thisRecord;
			echo'{"success":1,"msg":"恭喜！尊敬的<font color=red>'.$data['wecha_name'].'</font>请您保持手机通畅！你的领奖序号:<font color=red>'.$record['sn'].'</font>"}';
			exit;
		}
	}
	public function exchange(){
		$this->lottory_record_db=M('Lottery_record');
		$this->lottory_db=M('Lottery');
		 if(IS_POST){
		 	$Lottery 	= $this->lottory_db->where(array('id'=>intval($_POST['id'])))->find();
		 	if ($Lottery['parssword']!=trim($this->_post('parssword'))){
		 		echo'{"success":0,"msg":"兑奖密码不正确"}';exit;
		 	}else {
		 		$data['sendtime']		= time();
		 		$data['sendstutas']	= 1;
		 		$this->lottory_record_db->where(array('id'=> intval($_POST['rid'])))->save($data);
		 		echo'{"success":1,"msg":"兑奖成功","changed":1}';exit;
		 	}
		}
	}
	function getPrizeName($Lottery,$prizetype){
		switch ($prizetype){
			default:
				return $prizetype;
				break;
			case 1:
				return $Lottery['fist'];
				break;
			case 2:
				return $Lottery['second'];
				break;
			case 3:
				return $Lottery['third'];
				break;
			case 4:
				return $Lottery['four'];
				break;
			case 5:
				return $Lottery['five'];
				break;
			case 6:
				return $Lottery['six'];
				break;
		}
	}
}

?>
