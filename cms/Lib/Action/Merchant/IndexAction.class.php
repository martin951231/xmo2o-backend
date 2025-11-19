<?php
/*
 * 商户中心管理首页
 *
 */

class IndexAction extends BaseAction{
	public $time_type = array('year'=>'年','month'=>'月','day'=>'日');
    public function index(){
		//商家公告
		$database_merchant_news = D('Merchant_news');
		$news_list = $database_merchant_news->field(true)->order('`is_top` DESC,`add_time` DESC')->limit(10)->select();
		$this->assign('news_list',$news_list);
		
		//平台快报
		$news = M('System_news');
		$where['n.status'] = 1;
		$plat_news_list = $news->field('n.id,n.title,n.add_time,c.name')->join('as n left join '.C('DB_PREFIX').'system_news_category c ON c.id = n.category_id ')->where($where)->order('n.sort DESC,n.id DESC')->limit(10)->select();
		$this->assign('plat_news_list',$plat_news_list);
		
		/**  商家数据统计 **/
		$mer_id = $this->merchant_session['mer_id'];
		$mer_money = M('Merchant')->field(true)->where(array('mer_id'=>$mer_id))->find();

		if(C('customization.life_tools')){// 城投生活通定制 
			// 显示订单总额
			$where = [
				'mer_id' => $mer_id,
                'paid'   => 1,
				'system_status' => ['not in',['4', '5']]
			];
			$all_money = M('System_order')->field(true)->where($where)->sum('total_price');
			$this->assign('all_money',$all_money);
		}else{
			$this->assign('all_money',$mer_money['money']);
		}
		
		$this->assign('life_tools',C('customization.life_tools'));

		//粉丝数量
		$where_fans =array('mer_id'=>$mer_id);
		$where_fans['_string'] = "u.openid<>'' AND m.openid<> ''";
		$pigcms_data['fans_count'] = M('Merchant_user_relation')->join('as m LEFT JOIN '.C('DB_PREFIX').'user u ON u.openid = m.openid')->where($where_fans)->count();
		//会员卡数量
		$pigcms_data['card_count'] = M('Card_userlist')->where(array('mer_id'=>$mer_id,'status'=>1,'uid'=>array('neq',0)))->count();
		//微活动数量
		$pigcms_data['lottery_count'] = M('Lottery')->where(array('mer_id'=>$mer_id))->count();
		//店铺数量
		$pigcms_data['store_count'] = M('Merchant_store')->where(array('mer_id'=>$mer_id,'status'=>array('neq',4)))->count();

		//今日收入
		$condition['mer_id'] = $mer_id;
		$today_zero = strtotime(date('Y-m-d'));
        $condition['_string'] = 'use_time>'.$today_zero.' AND use_time <='.($today_zero+86400)." AND type <> 'withdraw'";
		$pigcms_data['today_order_money']  = M('Merchant_money_list')->where($condition)->sum('case income when 1 then money else money*-1 end');
        $pigcms_data['today_order_money'] = $pigcms_data['today_order_money'] > 0 ? $pigcms_data['today_order_money'] : 0;
		$pigcms_data['today_order_count'] =  intval(M('Merchant_money_list')->where($condition)->count('distinct(order_id)'));
        //一卡通
        $pigcms_data['today_ecard_money'] = D('InternalMembershipCard')->getDayIncome($mer_id, $today_zero)['pay_money'];
        $pigcms_data['today_ecard_order_count'] = D('InternalMembershipCard')->getDayOrderCount($mer_id, $today_zero);
        $pigcms_data['yesterday_ecard_money'] = D('InternalMembershipCard')->getDayIncome($mer_id, $today_zero-86400)['pay_money'];
        $pigcms_data['yesterday_ecard_order_count'] =  D('InternalMembershipCard')->getDayOrderCount($mer_id, $today_zero-86400);
		//昨日收入
		$condition['_string'] = 'use_time>'.($today_zero-86400).' AND use_time <='.($today_zero)." AND type <> 'withdraw'";
		$pigcms_data['yesterday_order_money']  = M('Merchant_money_list')->where($condition)->sum('case income when 1 then money else money*-1 end');
        $pigcms_data['yesterday_order_money'] = $pigcms_data['yesterday_order_money'] > 0 ? $pigcms_data['yesterday_order_money'] : 0;
		$pigcms_data['yesterday_order_count'] =  intval(M('Merchant_money_list')->where($condition)->count('distinct(order_id)'));
    
		//新增会员数
		$condition['_string'] = 'add_time>'.$today_zero;
		$pigcms_data['today_card_user']  = intval(M('Card_userlist')->where($condition)->count());
		$condition['_string'] = 'add_time>'.($today_zero-86400).' and add_time<'.$today_zero;
		$pigcms_data['yesterday_card_user']  = intval(M('Card_userlist')->where($condition)->count());
		
		//下单用户数
		$condition['_string'] = 'create_time>'.$today_zero;
		$today_new_order_user = M('System_order')->where($condition)->group('uid')->select();
		$pigcms_data['today_new_order_user']  = $today_new_order_user ? count($today_new_order_user) : 0;
		$condition['_string'] = 'create_time>'.($today_zero-86400).' and create_time <'.$today_zero;
		$yesterday_new_order_user = M('System_order')->where($condition)->group('uid')->select();
		$pigcms_data['yesterday_new_order_user']  = $yesterday_new_order_user ? count($yesterday_new_order_user) : 0;
		
		//判断是否有移动客户端
		$pigcms_data['appConfig']  = D('Appapi_app_config')->get();
		
		$this->assign($pigcms_data);

		$this->assign('group', D('Merchant_auth')->get_auth_group_list());
		$this->assign('time_type', $this->time_type);
		$now_group  =  M('Authority_group')->where(array('id' => $mer_money['authority_group_id']))->find();

		if($mer_money['is_try']){
			$mer_money['merchant_end_time'] = $mer_money['try_end_time'];
		}
		$mer_money['authority_day'] =ceil(($mer_money['merchant_end_time']-time())/86400);

		// 获取相关权限
        /****实时查找商家的权限****/
        $tmerch = D("Merchant")->field('menus,is_sign_contract,contract_end_time')->where(array('mer_id' => $mer_id))->find();
        $is_merchant_money = true;
        $is_card_new = true;
        $is_lottery = true;
        $is_store = true;
        if ($tmerch && $tmerch['menus']) {
            $menus = explode(",", $tmerch['menus']);
            $m_new_merchant_menus = M('New_merchant_menu');
            $money_menus_id = $m_new_merchant_menus->where(array('module' => 'Merchant_money', 'action' => 'index'))->getField('id');
            if (!in_array($money_menus_id, $menus)) {
                $is_merchant_money = false;
            }
            $card_new_menus_id = $m_new_merchant_menus->where(array('module' => 'Card_new', 'action' => 'index'))->getField('id');
            if (!in_array($card_new_menus_id, $menus)) {
                $is_card_new = false;
            }
            $lottery_menus_id = $m_new_merchant_menus->where(array('module' => 'Lottery', 'action' => 'index'))->getField('id');
            if (!in_array($lottery_menus_id, $menus)) {
                $is_lottery = false;
            }
            $store_menus_id = $m_new_merchant_menus->where(array('module' => 'Config', 'action' => 'store'))->getField('id');
            if (!in_array($store_menus_id, $menus)) {
                $is_store = false;
            }
        }
        //判断商家是否需要弹出签署合同
        $contract = [];
        $contract['contract_sign_tip'] = '';
        $contract['contract_sign_alert'] = 0;
        $mustSign = D('Config_data')->where(['name' => 'contract_must_sign'])->getField('value');
        if($mustSign){
            //看下是否开启续费
            $canRenewed = D('Config_data')->where(['name' => 'contract_can_renewed'])->getField('value');
            if($tmerch['is_sign_contract']==0){
                $contract['contract_sign_tip'] = L_('您好，您已成功入驻平台，现需在线签署合同后才可正常使用商家功能哦~');
                $contract['contract_sign_alert'] = 1;
            }elseif($tmerch['is_sign_contract']==2){
                $resignTip = D('Config_data')->where(['name' => 'contract_resign_tip'])->getField('value');
                $contract['contract_sign_tip'] = $resignTip;
                $contract['contract_sign_alert'] = 1;
            }elseif($tmerch['is_sign_contract']==1&&$canRenewed!=1&&$tmerch['contract_end_time']&&$tmerch['contract_end_time']<time()){
                $contract['contract_sign_tip'] = L_('合同已到期请联系管理员~');
                $contract['contract_sign_alert'] = 2;
            }elseif($canRenewed&&$tmerch['contract_end_time']){
                $fewDayNotice = D('Config_data')->where(['name' => 'contract_few_days_notice'])->getField('value');
                if(($tmerch['contract_end_time']-time())<$fewDayNotice*24*3600){
                    $last_day = ceil(($tmerch['contract_end_time']-time())/86400);
                    $contract['contract_sign_tip'] = L_('您好，您的合同还有'.$last_day.'天到期，请提前签署新合同，避免到期后功能无法使用，影响正常经营。');
                    $contract['contract_sign_alert'] = 1;
                }
            }
        }
        
        $this->assign('contract',$contract);

        $mer_money['is_merchant_money'] = $is_merchant_money;
        $mer_money['is_card_new'] = $is_card_new;
        $mer_money['is_lottery'] = $is_lottery;
        $mer_money['is_store'] = $is_store;



        // 统计锁定积分
        $lock_score = D('Merchant_get_score')->field('sum(score_count) as score_count,sum(score_get) as score_get')->where(['mer_id' => $mer_id])->find();
        $this->assign('lock_score', $lock_score['score_count'] - $lock_score['score_get']);
        $this->assign('show_merchant_score', is_null(C('config.open_add_score_by_system_commission')) ? 0 : 1);


        $this->assign('merchant', $mer_money);
		$this->assign('now_group', $now_group);

		// 查找需要续费的店铺
		$where = [];
		$where['_string'] = 'mer_id='.$mer_id.' AND status <> 4 AND end_time > 0 AND end_time < '.(time()+2592000);
		$storeList = M('Merchant_store')->where($where)->order('store_id DESC')->select();
		foreach($storeList as $key => $value){

			$param = [
				'catId' => $value['cat_id'],
				'catFid' => $value['cat_fid'],
				'proviceId' => $value['province_id'],
			];
			$res = invoke_v20_service('app\new_marketing\model\service\ClassPriceService/getStoreCategoryPriceDetail', $param);

			if(empty($res['data']['retval'])){
				unset($storeList[$key]);
			}
		}
		$storeCount = M('New_marketing_store')->where(['mer_id'=>$mer_id])->sum('store_count');
		$this->assign('buy_store_count', $storeCount);
		$this->assign('store_list', $storeList);

		$this->display();
    }
	//近七日交易走势
	public function get_week_order_list(){
		$return['order_money_list'] = array();
		$return['order_count_list'] = array();
		$condition['mer_id'] = $this->merchant_session['mer_id'];
		for($i=6;$i>=0;$i--){
			if($i == 0){
				$today_zero = strtotime(date('Y-m-d'));
			}else{
				$today_zero = strtotime(date('Y-m-d 00:00:00',strtotime('-'.$i.' day')));
			}
			$condition['_string'] = 'use_time>'.$today_zero.' AND use_time <='.($today_zero+86400)." AND type <> 'withdraw'";
			$return['order_money_list'][] = floatval(M('Merchant_money_list')->where($condition)->sum('money'));
			$return['order_count_list'][] =  intval(M('Merchant_money_list')->where($condition)->count());
			$return['xAxis_txt'][] =  date('Y-m-d',$today_zero);
		}
		$this->success($return);
	}
	public function news($id){
		$database_merchant_news = D('Merchant_news');
		$condition_merchant_news['id'] = $id;
		$now_news = $database_merchant_news->field(true)->where($condition_merchant_news)->find();
		if(empty($now_news)){
			$this->error(L_('当前内容不存在！'));
		}
		$this->assign('now_news',$now_news);
		
		$this->display();
	}

    /***收银台返回处理****/
	public function cashierBack(){
	   $lgcode=isset($_GET['lgcode']) ? trim($_GET['lgcode']) :'';
	   if($lgcode){
	      $merInfo=D("Merchant")->field('mer_id,account,name,phone,email')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
		  if(!empty($merInfo)){
		    $tmplgcode=$merInfo['mer_id'].'_'.md5($merInfo['account']);
		    //echo $tmplgcode;die;
			$tmplgcode=md5($tmplgcode);
			if($lgcode==$tmplgcode){
			  Header('Location:/merchant.php');
			  exit();
			}
		  }
	   }
	   session('merchant',null);
	   $this->error(L_('非法访问登录！'));
	}
	


	public function ajax_help($group, $module, $action) 
	{
		$url = strtolower($group . '_' . $module . '_' . $action);
		$url = 'http://o2o-service.pigcms.com/workorder/serviceAnswerApi.php?url=' . $url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$content = curl_exec($ch);
		curl_close($ch);

		$content = json_decode($content,true);
		foreach ($content as $value) {
			$class = $value['is_video'] == 1 ? 'class="video"' : 'class="writing"';
			$html .= '<p class="lianjie zuoce_clear "><a ' . $class . ' href="javascript:openwin(' . "'" . U('Index/help', array('answer_id' => $value['answer_id'])) . "'" .',768,960)">'.$value['title'].'</a></p>';
		}
		if (empty($html)) {
			$html = '<p class="lianjie zuoce_clear">没有帮助教程！</a>';
		}
		echo $html;
	}
	
	public function help()
	{
		$this->assign('answer_id', $_GET['answer_id']);
		$this->display();
	}

	public function demoapp(){
		$this->display();
	}

	public function ping(){
		
	}

    public function contact()
    {
        $merId = $this->merchant_session['mer_id'];
        $contract = (new Merchant_contractModel())->getContractContentByMerid($merId);
        $merchant = M('Merchant')->where(['mer_id' => $merId])->find();
        if($contract&&$contract['partner_a']){
            $contract['partner_a']['address'] .= $merchant?'  '.$merchant['address']:'';
        }
        if($contract&&$contract['partner_a']['sign_pic']){
            //是否可以续约
            $canRenewed = D('Config_data')->where(['name' => 'contract_can_renewed'])->getField('value');
            if($canRenewed&&($merchant['contract_end_time']-time())<=0) {
                $merchant['is_sign_contract'] = 0;
            }elseif($canRenewed&&($merchant['contract_end_time']-time())>0){
                $fewDayNotice = D('Config_data')->where(['name' => 'contract_few_days_notice'])->getField('value');
                if(($merchant['contract_end_time']-time())<=$fewDayNotice*24*3600){
                    $merchant['is_sign_contract'] = 0;
                }
            }
        }
        
        $merchant['is_sign_contract'] = $merchant['is_sign_contract']==2?0:$merchant['is_sign_contract'];
        $this->assign('merchant', $merchant);
        $this->assign('contract', $contract);
        $this->display();
    }

    public function saveSign()
    {
        if (IS_POST) {
            $mod = new Merchant_contractModel();
            $signNameBase64 = $_POST['sign_name_base64'] ?? '';
            $merId = $this->merchant_session['mer_id'];

            //查看是否已签署
            $contract_info = $mod->where(['mer_id' => $merId,'status'=>1])->find();
            //是否可以续约
            $canRenewed = D('Config_data')->where(['name' => 'contract_can_renewed'])->getField('value');
            //到期前多少天可以续签
            $fewDayNotice = D('Config_data')->where(['name' => 'contract_few_days_notice'])->getField('value');
            if($contract_info){
                if($canRenewed) {
                    if(($contract_info['end_time']-time())>$fewDayNotice*24*3600){
                        $this->error(L_('您已签署，请勿重复操作！'));
                    }
                }elseif(!$canRenewed&&($contract_info['end_time']-time())<0){
                    $this->error(L_('合同到期后不能续签！'));
                }
            }
            
            $contentData = (new Merchant_contractModel())->getContractContentByMerid($merId);
            $data = [
                'mer_id' => $merId,
                'content_data' => serialize($contentData),
                'sign_name_base64' => $signNameBase64,
                'sign_time' => $contentData['sign_time'],
                'contract_number' => $contentData['contract_number'],
                'end_time' => $contentData['end_time'],
                'status' => 1
            ];
            D()->startTrans();
            try {
                //修改以前状态
                D('Merchant_contract')->where(['mer_id'=>$merId,'status'=>1])->data(['status'=>0])->save();
                D('Merchant_contract')->add($data);
                D('Merchant')->where(['mer_id' => $merId])->save(['is_sign_contract' => 1,'contract_end_time'=>$contentData['end_time']]);
                D()->commit();
                $this->success(L_('签署成功！'));
            }catch (\Exception $e){
                D()->rollback();
                $this->error(L_('签署失败'));
            }
        } else {
            $this->display();
        }
    }
}
?>