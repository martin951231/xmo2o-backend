<?php
/**
 * 商家列表
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/9/2
 * Time: 10:25
 */

namespace app\new_marketing\controller\api;


use app\merchant\model\service\MerchantStoreService;
use app\new_marketing\model\db\NewMarketingTeam;
use app\new_marketing\model\db\NewMarketingTeamArtisan;
use app\new_marketing\model\service\MarketingArtisanService;
use app\new_marketing\model\service\MarketingPersonManagerService;
use app\new_marketing\model\service\MarketingPersonMerService;
use app\new_marketing\model\service\MarketingPersonSalesmanService;
use app\new_marketing\model\service\MarketingPersonService;
use app\new_marketing\model\service\MarketingTeamService;

class MerchantController extends ApiBaseController
{
	/**
	 * @return \json
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 * 用户端商家列表
	 */
	public function merchantList()
	{
		$param = $this->request->param();
		$uid   = $this->uid;
		try {
			//获取用户id，判断用户的身份
			//根据用户的身份来判断用什么条件去筛选 商铺的列表
			$where = (new MarketingPersonService())->checkPersonIdentity($uid, $param)['where'];
			$list = (new MarketingPersonMerService())->getUserMerchantList($param, $where);
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
		return api_output(0, $list, 'success');
	}

	public function merchantInfo()
	{
		$merId = $this->request->param('merId', 0, 'intval');
		if (!$merId) {
			return api_output_error(1003, '商家ID不存在');
		}
		$where = [
			['mer_id', '=', $merId],
			['status', '=', 1],
            ['end_time', '>', 1]
		];
		$field = 'store_id,mer_id,cat_id,cat_fid,name,phone,adress as address,last_time as add_time,end_time as effect_time';
		try {
			$merchant_info = (new MarketingPersonMerService())->getUserMerchantInfo($merId);
			$list          = (new MerchantStoreService())->getMerStoreList($where, $field);
			$arr['merchant_info'] = $merchant_info;
			$arr['store_list'] = $list;
			return api_output(0, $arr, 'success');
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}
	}

	/**
	 * 获取团队的列表
	 */
	public function getTeamList(){
		$this->checkLogin();
		$uid = $this->uid;

		try {
			$person_info = (new MarketingPersonService())->getOne(['uid' => $uid, 'is_agency' => 1], 'id,is_agency,is_manager,is_salesman');
			$artisan     = (new MarketingArtisanService())->getOne(['uid' => $uid], 'id');
			if (empty($person_info)) {
				if (empty($artisan)) {
					$arr = [];
					array_unshift($arr, ['id' => 0, 'name' => '全部成员']);
					return api_output(0, $arr, '获取成功');
				} else {//是技术员
					$team_list = (new MarketingTeamService())->getTeamListByArtisan(['g.artisan_id' => $artisan['id']], 'a.id,a.name')->toarray();
				}
			} else {
				if ($person_info['is_agency'] == 1) {
					//是区域代理
					$team_list = (new MarketingTeamService())->teamList(['area_uid' => $person_info['id']], 'id,name')->toarray();
				} else {//如果不是区域代理 判断是不是 技术员
					if (empty($artisan)) {
						return api_output(0, [], '获取成功');
					} else {//是技术员
						$team_list = (new MarketingTeamService())->getTeamListByArtisan(['g.artisan_id' => $artisan['id']], 'a.id,a.name')->toarray();
					}
				}
			}

			array_unshift($team_list, ['id' => 0, 'name' => '全部团队']);
		} catch (\Exception $e) {
			return api_output_error(1003, $e->getMessage());
		}

		return api_output(0, $team_list, '获取成功');
	}

	/**
	 * 通过团队id，查找团队成员
	 */
	public function getPersonList(){
		$this->checkLogin();
		$uid = $this->uid;
		try {
			$team_id = $this->request->param('team_id', '', 'intval');//团队id

			$person_info = (new MarketingPersonService())->getOne(['uid' => $uid,'is_del'=>0], 'id,name,is_agency,is_manager,is_salesman');
			$artisan_info = (new MarketingArtisanService())->getOne(['uid' => $uid,'is_director'=>1,'status'=>0], 'id,name');

			if ($team_id) {
				if ($person_info) {//说明是业务员的
// 					if($person_info['is_agency'] == 1){//是区域代理，展示所有的业务经理
//                         $person_list = (new MarketingTeamService())->getManagerMsg([['t.id', '=', $team_id], ['p.is_del', '=', 0]],'p.id,p.name');
// //						$person_list = array_merge($person_list,$manager_list);
// 					} else {
                        $person_list = (new MarketingPersonSalesmanService())->getPersonSome([['bp.team_id', '=', $team_id], ['bp.is_del', '=', 0]], 'p.id,p.name');
                    // }
					array_unshift($person_list, ['id' => 0, 'name' => '全部成员']);
					return api_output(0, $person_list, '获取成功');
				}

				if($artisan_info){//技术员主管的
					$where[] = ['ms.team_id','=', $team_id];
					$where[] = ['g.status','=', 0];
					$res = (new MarketingArtisanService())->getMarketingTeamArtisanList($where,'g.id,g.name');
					array_unshift($res, ['id' => 0, 'name' => '全部成员']);
					return api_output(0, $res, '获取成功');
				}
			}
			$teamIds = [];

			if ($person_info) {
				if ($person_info['is_agency'] == 1) {//区域代理
					$teamIds = (new NewMarketingTeam())->where([['area_uid', '=', $person_info['id']], ['is_del', '=', 0]])->column('id') ?? [];
				} else {
					$teamid = (new MarketingPersonManagerService())->getOne([['person_id', '=', $person_info['id']], ['is_del', '=', 0]], 'team_id')['team_id'] ?? '';
					if ($teamid) {
						$teamIds[] = $teamid;
					}
				}
				if (empty($teamIds)) {
					$arr = [];
					if($person_info['is_agency'] == 1){//是区域代理，展示所有的业务经理
						$arr = (new MarketingTeamService())->getManagerMsg([['t.id', 'in', $teamIds], ['p.is_del', '=', 0]],'p.id,p.name');
					}
//					if ($person_info['is_manager'] == 1) {//是业务经理，展示自己
//						array_unshift($arr, ['id' => $person_info['id'], 'name' => $person_info['name']]);
//					}
					array_unshift($arr, ['id' => 0, 'name' => '全部成员']);
					return api_output(0, $arr, '获取成功');
				}

// 				if($person_info['is_agency'] == 1){//是区域代理，展示所有的业务经理
//                     $person_list = (new MarketingTeamService())->getManagerMsg([['t.id', 'in', $teamIds], ['p.is_del', '=', 0]],'p.id,p.name');
// //					$person_list = array_merge($person_list,$manager_list);
// 				} else {
                    $person_list = (new MarketingPersonSalesmanService())->getPersonSome([['bp.team_id', 'in', $teamIds], ['bp.is_del', '=', 0]],'p.id,p.name');
                // }
//				if ($person_info['is_manager'] == 1) {//是业务经理，展示自己
//					array_unshift($person_list, ['id' => $person_info['id'], 'name' => $person_info['name']]);
//				}

				array_unshift($person_list, ['id' => 0, 'name' => '全部成员']);
				return api_output(0, $person_list, '获取成功');
			}

			//技术人员
			if($artisan_info){
				//先查出下级的技术员
				$artisan_ids = (new MarketingArtisanService())->getSome(['director_id' => $artisan_info['id'],'status'=>0],'id,name');
				array_unshift($artisan_ids, ['id' => $artisan_info['id'], 'name' => $artisan_info['name']]);
				array_unshift($artisan_ids, ['id' => 0, 'name' => '全部成员']);
				return api_output(0, $artisan_ids, '获取成功');
			}

			return api_output_error(1003, '用户身份错误');
		}catch (\Exception $e){
			return api_output_error(1003, $e->getMessage());
		}
	}
}