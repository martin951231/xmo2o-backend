<?php
/*
 * 营销系统对接
 *
 */

class WxappAction extends BaseAction{
	public $apiUrl;
	public $SALT;
	public $synType 	= 'weidian';
	public $_accessListAction;
	public $pigcmsToken = '';
	public $mer_id;
	public $pigcmsRegUrl;
	public $pigcmsLoginUrl;
	public $pigcmsAccessUrl;
	public $actionName;
	protected function _initialize(){
		parent::_initialize();
		$this->apiUrl  = $this->config['wxapp_url'];
		if(empty($this->apiUrl) && ACTION_NAME != 'wxapp_list' && ACTION_NAME != 'wxapp_edit' && ACTION_NAME != 'wxapp_amend' && ACTION_NAME != 'wxapp_del' && ACTION_NAME != 'web_list' && ACTION_NAME != 'web_edit' && ACTION_NAME != 'web_amend' && ACTION_NAME != 'web_del' && ACTION_NAME != 'nfcScheme' && ACTION_NAME!= 'generateNfcScheme'){
			$this->error('请联系管理员在后台配置营销功能');
		}
		$this->pigcmsRegUrl = $this->apiUrl.'/index.php?g=Home&m=Auth&a=signup';
		$this->pigcmsLoginUrl = $this->apiUrl.'/index.php?g=Home&m=Auth&a=signin';
		$this->pigcmsAccessUrl = $this->apiUrl.'/index.php?g=Home&m=Auth&a=access';
		
		$this->SALT 	= $this->config['wxapp_encrypt'] ? $this->config['wxapp_encrypt'] : 'pigcms';
		
		$this->synType 	= 'o2o';
		$this->_accessListAction = array(
			'bargain'       => '砍价', 
			'seckill'       =>'秒杀', 
			'crowdfunding'  =>'众筹',
			'unitary'       =>$this->config['activity_score_alias'],
			'cutprice'      =>'降价拍',
			'lottery'		=>'大转盘',
			'red_packet'	=>'微信红包',
			'guajiang'		=>'刮刮卡',
			'jiugong'		=>'九宫格',
			'luckyFruit'	=>'幸运水果机',
			'goldenEgg'		=>'砸金蛋',
			'voteimg'		=>'图文投票',
			'custom'		=>'万能表单',
			'card'			=>'微贺卡',		//暂时不显示渠道二维码
			'game'			=>'微游戏',
			'live'			=>'微场景',
			'research'		=>'微调研',
			'forum'			=>'讨论小区',
			'autumn'		=>'中秋吃月饼活动',
			'helping'		=>'微助力',
			'donation'		=>'募捐',
			'coinTree'		=>'摇钱树',
			'collectword'	=>'集字游戏',
			'sentiment'		=>'谁是情圣',
			'frontPage'		=>'我要上头条',
			'test'			=>'趣味测试',
			'punish'		=>'惩罚台',
			'shakeLottery'	=>'摇一摇',
			'youSetDiscount'=>'优惠接力',
			'popularity'	=>'人气冲榜',
			'problem'		=>'一战到底',
			'seniorScene'	=>'微场景',
			'auction'		=>'微拍卖',
			'whitelist'		=>'城市白名单',
			'person_card'	=>'微名片',
		);
		$this->mer_id = -1;//系统账号
		$token_result = D('Wxpp_token')->field('`pigcms_token`')->where(array('mer_id'=>$this->mer_id))->find();
		if($token_result){
			$this->pigcmsToken = $token_result['pigcms_token'];
		}
	}
	/*所有活动作为空操作*/
	public function _empty($actionName){
		if(empty($this->apiUrl)){
			$this->error('请联系管理员在后台配置营销功能');
		}
		if(empty($this->_accessListAction[$actionName])){
			$this->error('您访问的活动不存在！');
		}
		$this->actionName = $actionName;
		
		$_SESSION['wxapp_now_active'] = 'system';
		
		//如果第一次对接，则注册
		if(empty($this->pigcmsToken)){
			$this->regToken();
		}
		$accessUrl = $this->loginToken();
		$this->assign('accessUrl',$accessUrl);
		$this->assign('accessName',$this->_accessListAction[$actionName]);
		$this->display('wxapp');
	}
	/*登录操作*/
	public function loginToken($isFirst=true){
		if(empty($this->apiUrl)){
			$this->error('请联系管理员在后台配置营销功能');
		}
		$session_data = array(
			'username' => $this->getUserName($this->mer_id), 
			'userid' => $this->mer_id, 
			'type' => $this->synType
		);
		$session_data['sign']	= $this->getSign($session_data);
		$pigcmsReturn  				= $this->curl_post($this->pigcmsLoginUrl,$session_data);
		if(!empty($pigcmsReturn)){
			$returnObj = json_decode($pigcmsReturn);
			if($returnObj->status != 0){
				if($isFirst == true && ($returnObj->status == 40201 || $returnObj->status == 40202)){
					$this->regToken();
					return $this->loginToken(false);
				}
				$this->error('请联系网站管理员解决！对接返回错误：'.$returnObj->message);
			}else{
				$accessData = array(
					'token' 		=> $this->pigcmsToken,
					'action' 		=> $this->actionName,
					'session_id' 	=> $returnObj->session_id
				);
				$accessData['sign'] = $this->getSign($accessData);
				return $this->pigcmsAccessUrl.'&'.http_build_query($accessData);
			}
		}
	}
	/*注册操作*/
	public function regToken(){
		if(empty($this->apiUrl)){
			$this->error('请联系管理员在后台配置营销功能');
		}
		$site_url=str_replace('https','http',$this->config['site_url']);
		$post_data 	= array(
			'userid' 	=> $this->mer_id,
			'username' 	=> $this->getUserName($this->mer_id),
			'type' 		=> $this->synType,
			'time' 		=> $_SERVER['REQUEST_TIME'],
			'randstr' 	=> createRandomStr(4),
			'wxuserid' 	=> $this->mer_id,
			'domain' 	=> $site_url,
			'wxtype' 	=> -1,
		);

		$post_data['sign'] 	= $this->getSign($post_data);
		$pigcmsReturn 			= $this->curl_post($this->pigcmsRegUrl,$post_data);
		if(!empty($pigcmsReturn)){
			$returnObj = json_decode($pigcmsReturn);
			if($returnObj->status != 0){
				$this->error('请联系网站管理员解决！对接返回错误：'.$returnObj->message);
			}
			$this->pigcmsToken = $this->getToken($this->mer_id);
			if(!D('Wxpp_token')->data(array('mer_id'=>$this->mer_id,'pigcms_token'=>$this->pigcmsToken))->add()){				
				$this->error('请联系网站管理员解决！对接返回错误：无法保存对接信息！');
			}
		}else{
			$this->error('请联系网站管理员解决！对接注册没有返回内容');
		}
	}
	/*创建账号名称*/
	public function getUserName($id){
		return $this->synType.'_'.substr(md5($id),8,10);
	}
	/*模拟公众号token*/
	public function getToken($id){
		$site_url=str_replace('https','http',$this->config['site_url']);
		return substr(md5($site_url.$id.$this->synType),8,16);
		
	}
	/*Pigcms规定的密钥*/
	private function getSign($data){
		foreach ($data as $key => $value) {
			$validate[$key] = is_array($value) ? $this->getSign($value) : $value;
		}
		$validate['salt'] = $this->SALT;
		sort($validate, SORT_STRING);
		return sha1(implode($validate));
	}
	protected function curl_post($url,$data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
		return curl_exec($ch);
	}
	
	
	public function index()
	{
		if(empty($this->apiUrl)){
			$this->error('请联系管理员在后台配置营销功能');
		}
	    $activity_type = $_GET['cat_url'] ? $_GET['cat_url'] : 'all';
// 	    $where = array('status' => 1);
	    if($_GET['mer_id']){
	        $where['mer_id'] = $_GET['mer_id'];
	    }
// 	    if($activity_type != 'all'){
// 	        $where['type'] = $activity_type;
// 	        $now_category['cat_url'] = $activity_type;
// 	        $now_category['cat_name'] = $this->_accessListAction[$activity_type];
// 	        $this->assign('now_category', $now_category);
// 	    }
// 	    $this->assign('now_cat_url', $activity_type);
	    $tp_count = D('Wxapp_list')->where($where)->count();
	    import('@.ORG.system_page');
	    $P = new Page($tp_count, 20);
	    $wxapp_list = D('Wxapp_list')->where($where)->order('status DESC, pigcms_id DESC')->limit($P->firstRow.','.$P->listRows)->select();
	    
	    foreach ($wxapp_list as &$wx) {
	        $wx['url'] = U('Wxapp/location_href', array('id' => $wx['pigcms_id']));
	        $wx['type_name'] = $this->_accessListAction[$wx['type']];
	    }
	    
// 	    echo '<pre/>';
// 	    print_r($wxapp_list);die;
	    $this->assign('wxapp_list', $wxapp_list);
	    
// 	    foreach ($this->_accessListAction as $key => $val) {
// 	        $all_category_list[] = array('cat_name' => $val,'cat_url' => $key);
// 	    }
// 	    $this->assign('all_category_list', $all_category_list);
	    $this->assign('pagebar', $P->show());
	    $this->display();
	}
	
	public function edit()
	{
		if(empty($this->apiUrl)){
			$this->error('请联系管理员在后台配置营销功能');
		}
	    $this->assign('bg_color','#F3F3F3');
	    
//         $where = array('mer_id' => -1);
	    $where['pigcms_id'] = intval($_GET['id']);
	    $now_wxapp = D('Wxapp_list')->field(true)->where($where)->find();
	    if (empty($now_wxapp)) {
	        $this->frame_error_tips('该活动不存在！');
	    }
	    $this->assign('now_wxapp', $now_wxapp);
	    $this->display();
	}
	
	public function amend()
	{
	    if (IS_POST) {
// 	        $where = array('mer_id' => -1);
	        $where['pigcms_id'] = intval($_POST['id']);
	        unset($_POST['id']);
	        if (D('Wxapp_list')->where($where)->save($_POST)) {
	            $this->success('编辑成功！');
	        } else {
	            $this->error('编辑失败！请重试~');
	        }
	    } else {
	        $this->error('非法提交,请重新提交~');
	    }
	}

	//小程序列表
	public function wxapp_list()
    { 
        $database_wxapp_other = D('Wxapp_other');
        $wxapp_list = $database_wxapp_other->field(true)->select();       
        $this->assign('wxapp_list', $wxapp_list);
        $this->display();
    }

	public function wxapp_edit(){
        $database_wxapp_other = D('Wxapp_other');
		$id = intval($_GET['id']);
		if ($id) {
			$condition['pigcms_id'] = $id;
			$detail = $database_wxapp_other->field(true)->where($condition)->find();
			$this->assign('detail',$detail);
			if(empty($detail)){
				$this->frame_error_tips('该小程序不存在！');
			}
		}
		$this->display();
	}

	public function wxapp_amend(){
        $database_wxapp_other = D('Wxapp_other');
		$pigcms_id = intval($_POST['id']);

		$data = array();
		if(!$_POST['name']){
			$this->frame_submit_tips(0,'小程序名称不能为空');
		}

		if(!$_POST['appid']){
			$this->frame_submit_tips(0,'小程序APPID不能为空');
		}
		$data['name'] = trim($_POST['name']);
		$data['appid'] = trim($_POST['appid']);
		$data['username'] = trim($_POST['username']);
		if ($pigcms_id) {
			$condition['pigcms_id'] = $pigcms_id;
			if($database_wxapp_other->where($condition)->data($data)->save() !== false){
            	S('wxapp_other_list',null);
				$this->frame_submit_tips(1,'编辑成功！');
			}else{
				$this->frame_submit_tips(0,'编辑失败！请重试~');
			}
		}else{
			$data['time'] = $_SERVER['REQUEST_TIME'];
			// 只能添加10个
			$count = $database_wxapp_other->count();
			if ($count >= 10) {
				//$this->frame_submit_tips(0,'最多只能添加10个！');
			}
			if($database_wxapp_other->data($data)->add()){
            	S('wxapp_other_list',null);
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}
		
	}

	public function wxapp_del(){
        $database_wxapp_other = D('Wxapp_other');
		$condition['pigcms_id'] = intval($_POST['id']);
		if($database_wxapp_other->where($condition)->delete()){
            S('wxapp_other_list',null);
			$this->success('删除成功');
		}else{
			$this->error('删除失败！请重试~');
		}
	}

	//小程序打开其他网页列表
	public function web_list()
    { 
        $database_wxapp_other_web = D('Wxapp_other_web');
        $wxapp_list = $database_wxapp_other_web->field(true)->select();
        $this->assign('wxapp_list', $wxapp_list);
        $this->display();
    }

	public function web_edit(){
        $database_wxapp_other_web = D('Wxapp_other_web');
		$id = intval($_GET['id']);
		if ($id) {
			$condition['pigcms_id'] = $id;
			$detail = $database_wxapp_other_web->field(true)->where($condition)->find();
			$this->assign('detail',$detail);
			if(empty($detail)){
				$this->frame_error_tips('该网页不存在！');
			}
		}
		$this->display();
	}

	public function web_amend(){
        $database_wxapp_other_web = D('Wxapp_other_web');
		$pigcms_id = intval($_POST['id']);

		$data = array();
		if(!$_POST['domain']){
			$this->frame_submit_tips(0,'域名不能为空');
		}
		$data['name'] = $_POST['name'];
		$data['domain'] = $_POST['domain'];
		if ($pigcms_id) {
			$condition['pigcms_id'] = $pigcms_id;
			if($database_wxapp_other_web->where($condition)->data($data)->save()){
				S('wxapp_domain_list',null);
				$this->frame_submit_tips(1,'编辑成功！');
			}else{
				$this->frame_submit_tips(0,'编辑失败！请重试~');
			}
		}else{
			$data['time'] = $_SERVER['REQUEST_TIME'];
			// 只能添加10个
			$count = $database_wxapp_other_web->count();
			if ($count >= 20) {
				$this->frame_submit_tips(0,'最多只能添加20个！');
			}
			if($database_wxapp_other_web->data($data)->add()){
				S('wxapp_domain_list',null);
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}
		
	}

	public function web_del(){
        $database_wxapp_other_web = D('Wxapp_other_web');
		$condition['pigcms_id'] = intval($_POST['id']);
		if($database_wxapp_other_web->where($condition)->delete()){
			S('wxapp_domain_list',null);
			$this->success('删除成功');
		}else{
			$this->error('删除失败！请重试~');
		}
	}

	//nfc标签生成记录
	public function nfcScheme()
	{
		$mod = M('Wxapp_nfc_scheme_records');
		$where = [];
		$count_user = $mod->alias('r')->join(C('DB_PREFIX') . 'merchant_store s ON r.store_id = s.store_id')->where($where)->count();
		import('@.ORG.system_page');
		$p = new Page($count_user, 30);
		$records = $mod->alias('r')->join(C('DB_PREFIX') . 'merchant_store s ON r.store_id = s.store_id')->field('r.*,s.name AS store_name')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
		$pagebar = $p->show();
		$this->assign('pagebar', $pagebar);
		$this->assign('records', $records);
		$this->display();
	}

	//生成nfc标签
	public function generateNfcScheme()
	{
		if (IS_POST) {
			$storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
			$modelId = isset($_POST['model_id']) ? trim($_POST['model_id']) : '';
			$business = 'store_pay';
			if ($storeId <= 0 || empty($modelId)) {
				$this->error('请填写完整信息！');
			}

			//获取短链生成，后续可以方便灵活变动
			$url = C('config.site_url') . '/wap.php?c=My&a=pay&store_id=' . $storeId;
			$linkId = M('Short_link')->add(['link_url' => $url, 'link_txt' => '店铺优惠买单']);

			$accessTokenArray = D('Access_token_wxapp_expires')->get_access_token();
			if ($accessTokenArray['errcode']) {
				$this->error('获取access_token发生错误：错误代码' . $accessTokenArray['errcode'] . ',微信返回错误信息：' . $accessTokenArray['errmsg']);
			}

			$sn = $business . '_' . $storeId;

			if (M('Wxapp_nfc_scheme_records')->where(['sn' => $sn])->find()) {
				$this->error('该店铺已生成过标签，请勿重复生成！');
			}

			$qrcodeUrl = 'https://api.weixin.qq.com/wxa/generatenfcscheme?access_token=' . $accessTokenArray['access_token'];
			$postData = array(
				'jump_wxa' => [
					'path' => D('House_village')->home_url,
					'query' => 'redirect=webview&webview_url=' . urlencode('/short_' . $linkId)
				],
				'model_id' => $modelId,
				'sn' => $sn
			);
			import('ORG.Net.Http');
			$ret = Http::curlPostOwnWithHeader($qrcodeUrl, json_encode($postData, JSON_UNESCAPED_UNICODE), []);
			$ret = json_decode($ret, true);
			if ($ret['errcode'] == 0) {
				//成功
				$data = [
					'store_id' => $storeId,
					'path' => $postData['jump_wxa']['path'],
					'query' => $postData['jump_wxa']['query'],
					'model_id' => $modelId,
					'sn' => $sn,
					'openlink' => $ret['openlink']
				];
				M('Wxapp_nfc_scheme_records')->add($data);
				$this->success("生成成功");
			} else {
				$this->error($ret['errmsg']);
			}
		} else {
			$stores = M('Merchant_store')->alias('s')
				->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant as m on m.mer_id = s.mer_id')
				->where(['s.status' => 1, 'm.status' => 1])
				->field('s.store_id,s.name')
				->select();
			$this->assign('stores', $stores);
			$this->display();
		}
	}
}
