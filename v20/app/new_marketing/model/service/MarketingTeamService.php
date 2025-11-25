<?php
/**
 * 营销管理service
 * User: wangchen
 * Date: 2021/8/19
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingArtisan;
use app\new_marketing\model\db\NewMarketingOrderType;
use app\new_marketing\model\db\NewMarketingOrderTypePerson;
use app\new_marketing\model\db\NewMarketingTeam;
use app\new_marketing\model\db\NewMarketingTeamArtisan;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingLog;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use app\new_marketing\model\db\NewMarketingPersonManager;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\new_marketing\model\service\MarketingPersonService;
use app\common\model\db\area;
use think\db\Where;
use think\route\Rule;

class MarketingTeamService
{
	// 团队列表
	public function getMarketingManagementList($where, $field, $order, $page, $pageSize){
		$list['list'] = (new NewMarketingTeam())->getMarketingManagementList($where, $field, $order, $page, $pageSize);
		$list['count'] = (new NewMarketingTeam())->getMarketingManagementCount($where, $field, $order);
		foreach($list['list'] as $k=>$v){
			// 时间
			$list['list'][$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			// 业务经理名称
			if(empty($v['manager_name'])){
				$list['list'][$k]['manager_name'] = '-';
			}
			// 区域代理名称
			if(empty($v['area_name'])){
				$list['list'][$k]['area_name'] = '-';
			}
			// 团队技术人员列表
			$teamArtisanList = (new NewMarketingTeamArtisan())->getMarketingTeamArtisanList(['g.team_id'=>$v['id'],'a.status'=>0], 'a.name');
			$teamArtisanList = $teamArtisanList->toArray();
			$teamArtisan = '';
			foreach($teamArtisanList as $ks=>$vs){
				if($ks == 0){
					$teamArtisan = $vs['name'];
				}else{
					$teamArtisan = $teamArtisan.'、'.$vs['name'];
				}
			}
			$list['list'][$k]['team_artisan_list'] = $teamArtisanList;
			$list['list'][$k]['team_artisan'] = $teamArtisan;
			// 团队人数
			$team_count = (new NewMarketingPersonSalesman())->where(['team_id'=>$v['id'],'is_del'=>0])->count();
			$list['list'][$k]['team_count'] = $team_count;
		}
		return $list;
	}

	// 总业绩
	public function teamPerformance($ids){

		$where[] = ['id','in',$ids];
		$list = (new NewMarketingTeam())->where($where)->select();
		$total_achievement = 0;
		foreach($list as $v){
			$total_achievement = $total_achievement + $v['achievement'];
		}
		return $total_achievement;
	}

	// 团队已有区域列表
	public function serviceAreaList(){
		// 团队区域
		$list = (new NewMarketingTeam())->where(['is_del'=>0])->field('province_id, city_id, area_id')->select();
		$province_id = [];
		$city_id = [];
		$area_id = [];
		foreach($list as $v){
			if($v['province_id'] != 0){
				$province_id[] = $v['province_id'];
			}
			if($v['city_id'] != 0){
				$city_id[] = $v['city_id'];
			}
			if($v['area_id'] != 0){
				$area_id[] = $v['area_id'];
			}
		}
		$province_ids = array_unique($province_id);
		$city_ids = array_unique($city_id);
		$area_ids = array_unique($area_id);
		$region = array();
		// 所有省
		$list = (new area())->where(['area_type'=>1])->field('area_id, area_name')->select()->toArray();
		$provinceAll = [];
		foreach($list as $v){
			$provinceAll[] = $v['area_id'];
		}
		$province = array_intersect($province_ids,$provinceAll);
		if($province){
			$region = (new area())->where([['area_id','in',$province]])->field('area_id, area_name')->select()->toArray();
			foreach($region as $k=>$v){
				$citys = (new area())->where(['area_type'=>2,'area_pid'=>$v['area_id']])->field('area_id, area_name')->select()->toArray();
				$cityAll = [];
				foreach($citys as $ks=>$vs){
					$cityAll[] = $vs['area_id'];
				}
				$city = array_intersect($city_ids,$cityAll);
				if($city){
					$region[$k]['list'] = (new area())->where([['area_id','in',$city]])->field('area_id, area_name')->select()->toArray();
					foreach($region[$k]['list'] as $kt=>$vt){
						$areas = (new area())->where(['area_type'=>3,'area_pid'=>$vt['area_id']])->field('area_id, area_name')->select()->toArray();
						$areaAll = [];
						foreach($areas as $ks=>$vs){
							$areaAll[] = $vs['area_id'];
						}
						$area = array_intersect($area_ids,$areaAll);
						if($area){
							$region[$k]['list'][$kt]['list'] = (new area())->where([['area_id','in',$area]])->field('area_id, area_name')->select()->toArray();
						}
					}
				}else{
					$region[$k]['list'] = [];
				}
			}
		}
		return $region;
	}

	// 团队列表
	public function teamList($where,$filed=true){
		$list = (new NewMarketingTeam())->where($where)->field($filed)->select();
		foreach($list as $k=>$v){
			$list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
		}
		return $list;
	}

	// 创建团队
	public function teamManagementAdd($params){
		// 区域
		if(!empty($params['area'])){
			$province_id = !empty($params['area'][0]) ? $params['area'][0] : 0;
			$city_id = !empty($params['area'][1]) ? $params['area'][1] : 0;
			$area_id = !empty($params['area'][2]) ? $params['area'][2] : 0;
		}else{
			$province_id = 0;
			$city_id = 0;
			$area_id = 0;
		}
		$data = array(
			'name' => $params['name'],
			'province_id' => $province_id,
			'city_id' => $city_id,
			'area_id' => $area_id,
			'area_uid' => $params['area_uid'],
			'manager_uid' => $params['manager_uid'],
			'manager_percent' => $params['manager_percent'],
			'personnel_percent' => $params['personnel_percent'],
			'village_manager_percent' => $params['village_manager_percent'],
			'village_personnel_percent' => $params['village_personnel_percent'],
			'technology_percent' => $params['technology_percent'],
			'add_time' => time(),
		);
		$id = (new NewMarketingTeam())->add($data);
		$list['id'] = $id;
		// 技术人员绑定团队
		if($params['artisan']){
			$datas = [];
			foreach($params['artisan'] as $k=>$v){
				$datas[$k]['artisan_id'] = $v;
				$datas[$k]['team_id'] = $id;
				$datas[$k]['join_time'] = time();
			}
			(new NewMarketingTeamArtisan())->addAll($datas);
		}
		// 获取uid
		$personFind = (new NewMarketingPerson())->where(['id'=>$params['manager_uid'],'is_del'=>0])->find();
		// 团队绑定业务经理并添加业务员
		$salesCount = (new NewMarketingPersonSalesman())->where(['person_id'=>$params['manager_uid'],'is_del'=>0])->count();
		if($salesCount < 1){
			$salesmanData = array(
				'team_id' => $id,
				'invitation_code' => (new MarketingPersonService())->invitation_code($personFind['uid']),
				'person_id' => $params['manager_uid'],
				'shop_percent' =>  $params['personnel_percent'],
				'village_percent' => $params['village_personnel_percent'],
				'add_time' => time(),
				'update_time' => time(),
			);
			(new NewMarketingPersonSalesman())->add($salesmanData);
			(new NewMarketingPerson())->where(['id'=>$params['manager_uid'],'is_del'=>0])->update(['is_salesman'=>1]);
		}
		// 团队绑定业务经理添加业务经理
		$managerCount = (new NewMarketingPersonManager())->where(['person_id'=>$params['manager_uid'],'is_del'=>0])->count();
		if($managerCount < 1){
			$managerData = array(
				'person_id' => $params['manager_uid'],
				'invitation_code' => (new MarketingPersonService())->invitation_code($personFind['uid']),
				'province_id' => $province_id,
				'city_id' => $city_id,
				'area_id' => $area_id,
				'team_id' => $id,
				'add_time' => time(),
				'update_time' => time(),
			);
			(new NewMarketingPersonManager())->add($managerData);
			(new NewMarketingPerson())->where(['id'=>$params['manager_uid'],'is_del'=>0])->update(['is_manager'=>1]);
		}else{
			// 业务经理绑定团队
			(new NewMarketingPersonManager())->where(['person_id'=>$params['manager_uid'],'is_del'=>0])->update(['team_id'=>$id]);
		}
		return $list;
	}

	// 解散团队（需为成员选择新团队）
	public function teamManagementDiss($params){
		// 解散
		$find = (new NewMarketingTeam())->where(['id'=>$params['id']])->find();
		$diss = (new NewMarketingTeam())->where(['id'=>$params['id']])->update(['is_del'=>1]);
		if($diss){
			if($params['team_id'] > 0){
				// 为成员选择新团队
				$where = ['team_id'=>$params['id']];
				$data['team_id'] = $params['team_id'];
				$data['update_time'] = time();
				(new NewMarketingPersonSalesman())->where($where)->update($data);
			}
			// 解绑业务经理
			(new NewMarketingPersonManager())->where(['person_id'=>$find['manager_uid'],'is_del'=>0])->update(['team_id'=>0]);
		}
		return $diss;
	}

	// 团队基本信息
	public function teamManagementBasic($params){
		// 基本信息
		$basic = (new NewMarketingTeam())->where(['id'=>$params['id']])->find();
		$basic['area'] = [$basic['province_id'], $basic['city_id'], $basic['area_id']];
		if(empty($basic)){
			throw new \think\Exception(L_('团队信息不存在'), 1001);
		}
		// 区域代理
		$area = (new NewMarketingPerson())->where(['id'=>$basic['area_uid'],'is_del'=>0])->find();
		if($area){
			$basic['area_find'] = array(
				'id' => $area['id'] ? $area['id'] : '',
				'uid' => $area['uid'] ? $area['uid'] : '',
				'name' => $area['name'] ? $area['name'] : '',
			);
		}else{
			$basic['area_find'] = [];
		}
		// 业务经理
		$manager = (new NewMarketingPerson())->where(['id'=>$basic['manager_uid'],'is_del'=>0])->find();
		if($manager){
			$basic['manager_find'] = array(
				'id' => $manager['id'] ? $manager['id'] : '',
				'uid' => $manager['uid'] ? $manager['uid'] : '',
				'name' => $manager['name'] ? $manager['name'] : '',
			);
		}else{
			$basic['manager_find'] = [];
		}
		// 技术人员
		$artisan = (new NewMarketingTeamArtisan())->getMarketingTeamArtisanList(['g.team_id'=>$basic['id'],'a.status'=>0], 'a.id, a.uid, a.name, a.is_director, a.team_percent,a.director_id');
		$basic['artisan_list'] = $artisan ? $artisan : [];
		$list['basic'] = $basic;
		// 成员列表
		$person_list = (new NewMarketingPerson())->serviceManagerList(['a.team_id'=>$params['id'],'g.is_salesman'=>1,'g.is_del'=>0,'a.is_del'=>0], 'g.*, a.add_time, a.join_team, a.join_uid, a.total_performance, a.total_percentage, a.village_percent, a.shop_percent');
		foreach($person_list as $k=>$v){
			if($v['join_team']==1){
				$join_uid = (new NewMarketingPerson())->where(['uid'=>$v['join_uid'],'is_del'=>0])->find();
				$person_list[$k]['join_team_name'] = $join_uid['name'].'邀请';
			}else{
				$person_list[$k]['join_team_name'] = '平台添加';
			}
			$person_list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			// 是否可以移除
			$count = (new NewMarketingPersonMer())->where(['person_id'=>$v['id']])->count();
			if($count > 0){
				$is_remove = 0;
			}else{
				$is_remove = 1;
			}
			$person_list[$k]['is_remove'] = $is_remove;
		}
		$list['person_list'] = $person_list;
		return $list;
	}

	// 编辑团队
	public function teamManagementEdit($params){
		$where = ['is_del'=>0,'id'=>$params['id']];
		$province_id = $params['area'][0] ?? 0;
		$city_id = $params['area'][1] ?? 0;
		$area_id = $params['area'][2] ?? 0;
		
		$data = array(
			'name' => $params['name'],
			'province_id' => $province_id,
			'city_id' => $city_id,
			'area_id' => $area_id,
			'area_uid' => $params['area_uid'],
			'manager_uid' => $params['manager_uid'],
			'personnel_percent' => $params['personnel_percent'],
			'village_personnel_percent' => $params['village_personnel_percent'],
			'manager_percent' => $params['manager_percent'],
			'village_manager_percent' => $params['village_manager_percent'],
			'technology_percent' => $params['technology_percent'],
		);

		// 修改团队信息
		(new NewMarketingTeam())->where($where)->update($data);
		// 技术人员绑定团队
		if($params['artisan']){
			$teamArtisanMod = new NewMarketingTeamArtisan();
			$artisan = $teamArtisanMod->where(['team_id' => $params['id']])->field('artisan_id')->select()->toArray();
			$oldArtisanIds = array_column($artisan, 'artisan_id');

			//删除去掉的技术员，增加新技术员
			$teamArtisanMod->where('team_id', $params['id'])->whereNotIn('artisan_id', $params['artisan'])->delete();
			$addData = array_diff($params['artisan'], $oldArtisanIds);
			$addData = array_map(function ($r) use ($params) {
				return [
					'artisan_id' => $r,
					'team_id' => $params['id'],
					'join_time' => time()
				];
			}, $addData);
			$addData && (new NewMarketingTeamArtisan())->addAll($addData);
		}else{
			(new NewMarketingTeamArtisan())->where(['team_id'=>$params['id']])->delete();
		}
		// 团队绑定业务经理并添加业务员
		$personCount = (new NewMarketingPersonManager())->where(['person_id'=>$params['manager_uid'],'is_del'=>0])->count();
		if($personCount > 0){
			// 添加业务员
			$salesCount = (new NewMarketingPersonSalesman())->where(['person_id'=>$params['manager_uid'],'is_del'=>0])->count();
			if($salesCount < 1){
				$personFind = (new NewMarketingPerson())->where(['id'=>$params['manager_uid'],'is_del'=>0])->find();
				$salesmanData = array(
					'team_id' => $params['id'],
					'invitation_code' => (new MarketingPersonService())->invitation_code($personFind['uid']),
					'person_id' => $params['manager_uid'],
					'shop_percent' =>  $params['personnel_percent'],
					'village_percent' => $params['village_personnel_percent'],
					'add_time' => time(),
					'update_time' => time(),
				);
				(new NewMarketingPersonSalesman())->add($salesmanData);
			}
			// 清除原业务经理绑定团队(防止一个团队多个业务经理)
			(new NewMarketingPersonManager())->where(['team_id'=>$params['id'],'is_del'=>0])->update(['team_id'=>0]);
			// 业务经理绑定团队
			(new NewMarketingPersonManager())->where(['person_id'=>$params['manager_uid'],'is_del'=>0])->update(['team_id'=>$params['id']]);
		}else{
			$personFind = (new NewMarketingPerson())->where(['id'=>$params['manager_uid'],'is_del'=>0])->find();
			$managerData = array(
				'person_id' => $params['manager_uid'],
				'invitation_code' => (new MarketingPersonService())->invitation_code($personFind['uid']),
				'province_id' => $province_id,
				'city_id' => $city_id,
				'area_id' => $area_id,
				'team_id' => $params['id'],
				'add_time' => time(),
				'update_time' => time(),
			);
			(new NewMarketingPersonManager())->add($managerData);
			(new NewMarketingPerson())->where('id', $params['manager_uid'])->where('is_manager', 0)->update(['is_manager' => 1]);
		}
		return $params;
	}

	public function getTeamListByArtisan($where,$filed){
		return (new NewMarketingTeamArtisan())->getMarketingArtisanTeamList($where,$filed);
	}

	//团队详情
    public function getTeamDetail($team_id){
	    $teamData = (new NewMarketingTeam())->where([['id', '=', $team_id], ['is_del', '=', 0]])->find();
	    if (!$teamData) {
            throw new \think\Exception(L_("该团队已解散"), 1001);
        }
	    $start_month = strtotime(date('Y-m-1', time()));//本月开始时间戳
        $data = [
            'team_id' => $team_id,//团队ID
            'team_name' => $teamData['name'],//团队名称
            'personnel_percent' => $teamData['personnel_percent'],//技术人员提成比例
            'technology_msg' => [],//团队技术人员列表
            'team_business_members' => [],//团队成员
            'business_msg' => [//团队业务
                'merchant_nums' => 0,//商家数量
                'village_nums' => 0,//物业数量
                'trade_nums' => 0,//订单数量
            ],
            'manager_id' => '',//业务经理ID
            'manager_uid' => '',//业务经理UID
            'manager_name' => '',//业务经理名称
            'manager_image' => '',//业务经理头像
            'achievement' => [//团队业绩
                'month_achievement' => 0.00,//该团队本月总业绩
                'month_trade_num' => 0,//该团队本月订单量
                'month_agency_percentage' => 0,//该团队本月代理提成
                'month_person_percentage' => 0.00,//该团队本月个人提成（业务经理作为业务员的提成）
                'month_team_percentage' => 0.00,//该团队本月团队提成
            ],
        ];
        //团队技术人员
        $where_team_artisan = [['ms.team_id', '=', $team_id], ['g.status', '=', 0]];
        $team_artisan_list = (new NewMarketingArtisan())->getMarketingTeamArtisanList($where_team_artisan, 'g.id,u.phone,g.name,u.avatar,g.is_director', 'g.id asc');//技术人员列表
        if (!empty($team_artisan_list)) {
            $arr = [];
            $artisanArr = [];
            foreach ($team_artisan_list as $v) {
                $arr['id'] = $v['id'];
                $arr['name'] = $v['name'];
                $arr['phone'] = $v['phone'];
                $arr['identity'] = $v['is_director'] == 1 ? 4 : 3;
                $arr['image'] = !empty($v['avatar']) ? replace_file_domain($v['avatar']) :cfg('site_url')."/static/images/user_avatar.jpg";
                $artisanArr[] = $arr;
            }
            $data['technology_msg'] = $artisanArr;//团队技术人员信息
        }
        //团队成员
        $where_team_person = [['bp.team_id', '=', $team_id], ['bp.is_del', '=', 0]];
//        $team_manager = (new NewMarketingPerson())->getSomePerson($where_team_person, 'u.uid,u.avatar,u.phone,p.*,1 as iden', 'id asc', 2);//团队成员区域代理
        $team_man_list = (new NewMarketingPerson())->getSomePerson($where_team_person, 'u.uid,u.avatar,u.phone,p.*,0 as iden', 'id asc', 1);//团队成员列表
//        $team_man_list = array_merge($team_manager, $team_man_list);
        if (!empty($team_man_list)) {
            $arr = [];
            $personArr = [];
            foreach ($team_man_list as $v1) {
                $arr['id'] = $v1['id'];
                $arr['name'] = $v1['name'];
                $arr['phone'] = $v1['phone'];
                $arr['image'] = !empty($v1['avatar']) ? replace_file_domain($v1['avatar']) : cfg('site_url')."/static/images/user_avatar.jpg";
                if ($v1['is_manager'] == 1) {// && $v1['iden'] == 1
                    $arr['identity'] = 1;//业务经理
                    $data['manager_id'] = $arr['id'];
                    $data['manager_uid'] = $v1['uid'];
                    $data['manager_name'] = $arr['name'];
                    $data['manager_image'] = $arr['image'];
                } else {
                    $arr['identity'] = 0;//业务员
                }
                $personArr[] = $arr;
            }
            $data['team_business_members'] = $personArr;//团队成员信息
        }
        //团队业务
        $data['business_msg']['merchant_nums'] = (new NewMarketingPersonMer())->getCount([['team_id', '=', $team_id], ['type', '=', 0], ['status', '=', 0]]);
        $data['business_msg']['village_nums'] = (new NewMarketingPersonMer())->getCount([['team_id', '=', $team_id], ['type', '=', 1], ['status', '=', 0]]);
        $data['business_msg']['trade_nums'] = (new NewMarketingOrderType())->getCount(['team_id' => $team_id]);
        //团队业绩
        $data['achievement']['month_achievement'] = (new NewMarketingOrderTypePerson())->getPersonAchievement([['b.team_id', '=', $team_id], ['o.pay_time', '>=', $start_month]], 'o.total_price');
        $data['achievement']['month_trade_num'] = (new NewMarketingOrderType())->getOrderCount([['b.team_id', '=', $team_id], ['o.pay_time', '>=', $start_month]]);
        $data['achievement']['month_agency_percentage'] = (new NewMarketingOrderTypePerson())->getPersonAchievement([['b.team_id', '=', $team_id], ['o.pay_time', '>=', $start_month]], 'a.agent_price');
        $data['achievement']['month_person_percentage'] = (new NewMarketingOrderTypePerson())->getPersonAchievement([['a.person_id', '=', $data['manager_id']], ['o.pay_time', '>=', $start_month]], 'a.person_price');
        $data['achievement']['month_team_percentage'] = (new NewMarketingOrderTypePerson())->getPersonAchievement([['b.team_id', '=', $team_id], ['o.pay_time', '>=', $start_month]], 'a.manager_price');
        return $data;
    }

    public function getManagerMsg($where,$field){
		return (new NewMarketingTeam())->getManagerMsg($where,$field);
	}

    /**
     * 增加团队总业绩
     * @return bool
     */
    public function incCommission($where, $money, $field){
		if(empty($money) || empty($where) || empty($field)){
			return false;
		}
        $res = (new NewMarketingTeam())->where($where)->inc($field, $money)->update();
        if(!$res) {
            return false;
        }
        return $res;
    }
}