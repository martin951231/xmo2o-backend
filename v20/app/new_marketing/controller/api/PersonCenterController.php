<?php


namespace app\new_marketing\controller\api;


use app\common\model\service\ConfigService;
use app\mall\model\service\UserService;
use app\new_marketing\model\db\NewMarketingPositionReview;
use app\new_marketing\model\service\MarketingPersonService;
use app\new_marketing\model\service\PersonCenterService;
use app\new_marketing\model\service\PositionReviewService;
use think\facade\Db;

class PersonCenterController extends ApiBaseController
{
	public function center()
	{
		try {
			$uid = $this->request->log_uid ?? 0;
			if ($uid == 0) {
				return api_output_error(1002, "获取用户信息失败,请重新登录");
			}
			$msg = (new PersonCenterService())->center($uid);
			if ($msg['is_error']) {
				return api_output_error(1003, $msg['msg']);
			} else {
				return api_output(0, $msg, '获取成功');
			}
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	//获取邀请码
	public function getinvitecode()
	{
		if (empty($this->_uid)) {
			return api_output_error(1002, '当前接口需要登录');
		}
		$type = $this->request->param('type', 0, 'intval');//1=商家注册码,2=成员邀请码,3=业务经理邀请码
		try {
			$data             = (new PersonCenterService())->getinvitecode($this->_uid, $type);
			$data['nickname'] = $this->userInfo['nickname'];
			$data['avatar']   = $this->userInfo['avatar'] ?? $this->userInfo['user_logo'];
			return api_output(0, $data, '获取成功');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * @return \json
	 * 加入团队
	 */
	public function doJoinTeam()
	{
		$this->checkLogin();
//		$this->uid = 34;
		$userService = new UserService();
		$code        = $this->request->param('code', '', 'trim');//邀请码
		$type        = $this->request->param('type', 0, 'intval');//2=成员邀请码,3=业务经理邀请码
		$user_type   = $this->request->param('user_type', 0, 'intval');//分享者身份 1=业务员,2=业务经理,3区域代理
//		$phone = $this->request->param('phone', '', 'trim');//用户电话
		$reasons = $this->request->param('reasons', '', 'trim');//加入理由
		$name    = $this->request->param('name', '', 'trim');//加入理由
		$uid     = $this->uid;
		Db::startTrans();
		try {
			if (empty($type) || empty($code) || empty($name)) {
				throw_exception('参数不正确');
			}
			$phone = $userService->getUser($uid)['phone'];

//			if ($msg['status'] == 0 ) {
//				throw_exception('手机号码对应用户不存在，请先去注册');
//			}
//			if($msg['data']['uid'] !=$uid){
//				throw_exception('手机号码与登录用户不匹配');
//			}

			$msg = (new PersonCenterService())->doJoinInfo($uid, $type, $user_type, $code, $phone, $reasons, $name);
			Db::commit();
		} catch (\Exception $e) {
			Db::rollback();
			return api_output_error(1003, $e->getMessage());
		}

		return api_output(0, ['msg' => $msg], '加入成功');
	}

	/**
	 * 查看用户是否有资格升级
	 */
	public function isCanUpLevel()
	{
		$this->checkLogin();
		$uid                        = $this->uid;
//		$uid                        = 58;
		$configService              = new ConfigService();
		$condition_config['gid']    = 125;
		$condition_config['status'] = 1;
		$sort                       = 'sort DESC';

		try {
			$person_info = (new MarketingPersonService())->getOne(['uid' => $uid, 'is_del' => 0], 'id,is_agency,is_manager,is_salesman');

			//根据uid 获取当前用户等级，查询用户的 达成业绩总额 是否达到标准。
			if ($person_info['is_agency'] == 1) {
				throw_exception('该用户已经是区域代理，无需升级');
			}
			if ($person_info['is_manager'] == 1) {//查询是否可以升级成区域代理
				$index = 1;
			} elseif ($person_info['is_salesman'] == 1) {//查询是否可以升级成业务经理
				$index = 0;
			} else {
				throw_exception('身份不正确');
			}
			$config_list = $configService->getTmpConfigList($condition_config, $sort, $this->config);
			$where[]     = ['a.person_id', '=', $person_info['id']];
			$time        = (new MarketingPersonService)->getTime($config_list, $index);

			if (!empty($time['start_time'])) {
				$where[] = ['o.pay_time', '>=', $time['start_time']];
			}
			if (!empty($time['end_time'])) {
				$where[] = ['o.pay_time', '<', $time['end_time']];
			}

			$achievement = (new MarketingPersonService())->getPersonTotal($where);

			$status = 0;
			if ($achievement >= $config_list[$index]['list'][1]['value']) {//有资格
				$status = 1;
                $reviewData = (new NewMarketingPositionReview())->where([['uid', '=', $uid], ['status', '=', 0], ['identity', '=', $index + 2]])->find();
                if ($reviewData) {
                    $status = 2;
                }
			}
			$msg = $config_list[$index]['list'][0]['typeValue'][$config_list[$index]['list'][0]['value'] - 1]['label'].'总业绩达￥'.$config_list[$index]['list'][1]['value'];

		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}

		return api_output(0, ['status' => $status,'msg'=>$msg], '获取成功');
	}

	/**
	 * 申请操作
	 */
	public function doReview(){
		$this->checkLogin();
		$uid  = $this->uid;

		try {
			$person_info = (new MarketingPersonService())->getOne(['uid' => $uid], 'id,is_agency,is_manager,is_salesman');

			if($person_info['is_salesman'] == 1){
				$now_identity = 1;
			}
			if($person_info['is_manager'] == 1){
				$now_identity = 2;
			}
			if($person_info['is_agency'] == 1){
				throw_exception('该用户已经是区域代理，无需升级');
			}
			if(empty($now_identity)){
				throw_exception('身份不正确');
			}

			$data['uid'] = $uid;
			$data['identity'] = $now_identity+1;
			$data['add_time'] = time();
            $data['update_time'] = time();
			$data['status'] = 0;
			$data['reason'] = '';
			$data['pid'] = $person_info['id'];
			$data['now_identity'] = $now_identity;
			(new PositionReviewService())->addPositionReview($data);
		}catch (\Exception $e){
			return api_output_error(1003, $e->getMessage());
		}

		return api_output(0, [], '已申请，等待审核');
	}

	public function checkUserReview(){
		$this->checkLogin();
		$uid  = $this->uid;

		try {
			$person_info = (new MarketingPersonService())->getOne(['uid' => $uid], 'id,is_agency,is_manager,is_salesman');

			$is_status = (new PositionReviewService())->getPositionReviewUid(['uid' => $uid],'status');

		}catch (\Exception $e){

		}
	}
}