<?php
/**
 * 营销管理service
 * User: wangchen
 * Date: 2021/8/19
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingOrderTypePerson;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingArtisan;
use app\new_marketing\model\db\NewMarketingPersonAgency;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\new_marketing\model\db\NewMarketingTeam;
use app\common\model\db\User;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use app\new_marketing\model\db\NewMarketingPersonManager;
use app\new_marketing\model\db\NewMarketingTeamArtisan;
use think\route\Rule;

class MarketingPersonService
{
	// 营销人员列表（业务经理列表、区域代理列表、业务人员列表）
	public function serviceManagerList($where, $field){
		$list = (new NewMarketingPerson())->serviceManagerList($where, $field);
		foreach($list as $k=>$v){
			$list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
		}
		return $list;
	}

	// 业务经理列表（未绑定团队）
	public function serviceManagerNoList($where, $params){
		// 所有业务经理
//		$allList = (new NewMarketingPerson())->where($where)->field('id')->select();
//		$all = [];
//		foreach($allList as $v){
//			$all[] = $v['id'];
//		}
        $all = (new NewMarketingPersonManager())->where($where)->column('person_id');
//		if($all){
//			// 已绑定团队的业务经理
//			$yesList = (new NewMarketingTeam())->where([['is_del','=',0],['manager_uid','in',$all]])->field('manager_uid')->select();
//			$yes = [];
//			foreach($yesList as $v){
//				$yes[] = $v['manager_uid'];
//			}
//			if($yes){
//				$lis = array_diff($all,$yes);
//				$where[] =['id','in',$lis];
//			}else{
//				$where[] =['id','in',$all];
//			}
//		}
        $where1 = [
            ['id', 'in', $all],
            ['is_del', '=', 0]
        ];
		// 未绑定团队的业务经理列表数据
		$list = (new NewMarketingPerson())->where($where1)->select();
		foreach($list as $k=>$v){
			$list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
		}
		if($params['area_uid']){
			$perCount = (new NewMarketingPerson())->where(['id'=>$params['area_uid'],'is_del'=>0])->count();
			if($perCount > 0){
				$areaCount = (new NewMarketingPersonManager())->where(['person_id'=>$params['area_uid'],'is_del'=>0])->count();
				if($areaCount < 1){
					$areaList = (new NewMarketingPerson())->where(['id'=>$params['area_uid'],'is_del'=>0])->select();
					// print_r($areaList->toArray());
					if($areaList){
						$areaList = $areaList->toArray();
					}
					if($list){
						$list = $list->toArray();
					}
					$lists = array_merge($areaList, $list);
				}else{
					$lists = $list;
				}
			}else{
				$lists = $list;
			}
		}else{
			$lists = $list;
		}
		return $lists;
	}

	// 技术人员列表
	public function artisanList($where){
		$list = (new NewMarketingArtisan())->where($where)->select();
		foreach($list as $k=>$v){
			$list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			$list[$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
		}
		return $list;
	}

	// 团队基本信息提成编辑批量
	public function teamMemberEdit($params){
		$where = ['team_id'=>$params['id']];
		$data = array(
			'shop_percent' => $params['personnel_percent'],
		);
		(new NewMarketingPersonSalesman())->where($where)->update($data);
		return $params;
	}

	// 团队基本信息提成编辑单条
	public function teamMemberFindEdit($params){
		$where = ['person_id'=>$params['id']];
		$data = array(
			'shop_percent' => $params['personnel_percent'],
		);
		(new NewMarketingPersonSalesman())->where($where)->update($data);
		return $params;
	}

	// 团队基本信息用户验证
	public function teamMemberCode($params){
		// 判断用户是否存在
		if(preg_match("/^1[345678]{1}\d{9}$/",$params['uid'])){
			// 手机
			$phoneFind = (new User())->where(['phone'=>$params['uid']])->find();
			if(empty($phoneFind)){
				throw new \think\Exception(L_('用户不存在'), 1001);
			}
			$params['uid'] = $phoneFind['uid'];
		}else{
			// uid
			$uidFind = (new User())->where(['uid'=>$params['uid']])->find();
			if(empty($uidFind)){
				throw new \think\Exception(L_('用户不存在'), 1001);
			}
		}
		// 判断是否已存在业务员
		$personCount = (new NewMarketingPerson())->where(['uid'=>$params['uid'],'is_del'=>0])->count();
		if($personCount > 0){
			throw new \think\Exception(L_('该用户账号已有身份，不可重复绑定'), 1001);
		}
		// 判断是否已存在技术员
		$artisanCount = (new NewMarketingArtisan())->where(['uid'=>$params['uid'],'status'=>0])->count();
		if($artisanCount > 0){
			throw new \think\Exception(L_('该用户账号已有身份，不可重复绑定'), 1001);
		}
		return $params;
	}

	// 团队基本信息成员操作
	public function teamMemberAdd($params){
		if($params['id'] > 0){
			// 编辑
			$where = ['id'=>$params['id']];
			$data = array(
				'name' => $params['name'],
				'uid' => $params['uid'],
				'is_salesman' => 1,
			);
			$where2 = ['person_id'=>$params['id']];
			$data2 = array(
				'shop_percent' => $params['shop_percent'],
				'village_percent' => $params['village_percent'],
				'note' => $params['note'],
				'update_time' => time(),
			);
			(new NewMarketingPerson())->where($where)->update($data);
			(new NewMarketingPersonSalesman())->where($where2)->update($data2);
			$list['id'] = $params['id'];
		}else{
			// 新增
			$data = array(
				'name' => $params['name'],
				'uid' => $params['uid'],
				'phone' => $params['phone'],
				'is_salesman' => 1,
			);
			$data2 = array(
				'team_id' => $params['team_id'],
				'invitation_code' => $this->invitation_code($params['uid']),
				'shop_percent' => $params['shop_percent'],
				'village_percent' => $params['village_percent'],
				'note' => $params['note'],
				'add_time' => time(),
				'update_time' => time(),
			);
			$list['id'] = (new NewMarketingPerson())->add($data);
			$data2['person_id'] = $list['id'];
			(new NewMarketingPersonSalesman())->add($data2);
		}
		return $list;
	}

	// 团队基本信息成员信息
	public function teamMembeInfo($params){
		$info = (new NewMarketingPerson())->where(['id'=>$params['id']])->find();
		$salesman = (new NewMarketingPersonSalesman())->where(['person_id'=>$params['id']])->find();
		$info['invitation_code'] = $salesman['invitation_code'];
		$info['team_id'] = $salesman['team_id'];
		$info['shop_percent'] = $salesman['shop_percent'];
		$info['village_percent'] = $salesman['village_percent'];
		$info['total_performance'] = $salesman['total_performance'];
		$info['total_percentage'] = $salesman['total_percentage'];
		$info['join_team'] = $salesman['join_team'];
		$join_team = $salesman['join_team'];
		if($join_team == 0){
			$info['join_team_name'] = '平台添加';
		}else{
			$user = (new NewMarketingPerson())->where(['uid'=>$salesman['join_uid']])->find();
			$info['join_team_name'] = $user['name'].'邀请';
		}
		$info['join_uid'] = $salesman['join_uid'];
		$info['note'] = $salesman['note'];
		return $info;
	}

	// 团队基本信息更换团队（当前业务归零，重新计算）
	public function teamManagementReplace($params){
		$info = (new NewMarketingPerson())->where(['id'=>$params['id']])->find();
		if(!empty($info)){
			if($info['team_id'] != $params['team_id']){
				(new NewMarketingPersonSalesman())->where(['person_id'=>$params['id']])->update(['team_id'=>$params['team_id'],'update_time'=>time()]);
			}
		}
		return [];
	}

	// 团队基本信息升级（业务员升级业务经理）
	public function teamManagementUpgrade($params){
		$personFind = (new NewMarketingPerson())->where(['id'=>$params['id'],'is_del'=>0])->find();
		if(empty($personFind)){
			throw new \think\Exception(L_('业务员不存在'), 1001);
		}else{
			if($personFind['is_manager'] == 1){
				throw new \think\Exception(L_('该成员已是业务经理'), 1001);
			}
			$where = ['id'=>$params['id']];
			$data['is_manager'] = 1;
			(new NewMarketingPerson())->where($where)->update($data);
			$count2 = (new NewMarketingPersonManager())->where(['person_id'=>$params['id']])->count();
			// 区域
			$where2 = ['person_id'=>$params['id']];
			if(!empty($params['area'])){
				if(!empty($params['area'][0])){
					$data2['province_id'] = $params['area'][0];
				}
				if(!empty($params['area'][1])){
					$data2['city_id'] = $params['area'][1];
				}
				if(!empty($params['area'][2])){
					$data2['area_id'] = $params['area'][2];
				}
			}
			$data2['note'] = $params['note'];
			$data2['update_time'] = time();
			$uidFind = (new NewMarketingPersonSalesman())->where(['person_id'=>$params['id']])->find();
			// if($uidFind){
			// 	$data2['team_id'] = $uidFind['team_id'];
			// }else{
			$data2['team_id'] = 0;
			// }
			if($count2 > 0){
				$data2['add_time'] = $data2['update_time'] = time();
				(new NewMarketingPersonManager())->where($where2)->update($data2);
			}else{
				$data2['person_id'] = $params['id'];
				$data2['invitation_code'] = $this->invitation_code($uidFind['uid']);
				$data2['add_time'] = time();
				(new NewMarketingPersonManager())->add($data2);
			}
			// 刪除业务员
			(new NewMarketingPersonSalesman())->where(['person_id'=>$params['id']])->update(['is_del'=>1,'team_id'=>0]);
			// 更改业务员、业务经理
			(new NewMarketingPerson())->where(['id'=>$params['id']])->update(['is_manager'=>1,'is_salesman'=>0]);
		}
		return true;
	}

	// 团队基本信息移除
	public function teamManagementDel($params){
		// 判断是否需要业务转移
		$count = (new NewMarketingPersonMer())->where(['person_id'=>$params['id']])->count();
		if($count > 0){
			throw new \think\Exception(L_('该成员移除前 需要先将成员下的商家业务转移'), 1001);
		}
		// 移除
		(new NewMarketingPersonSalesman())->where(['person_id'=>$params['id']])->update(['is_del'=>1,'team_id'=>0]);
		$find = (new NewMarketingPerson())->where(['id'=>$params['id']])->find();
		if($find && $find['is_manager']==0 && $find['is_agency']==0){
			(new NewMarketingPerson())->where(['id'=>$params['id']])->update(['is_salesman'=>0,'is_del'=>1]);
		}else{
			(new NewMarketingPerson())->where(['id'=>$params['id']])->update(['is_salesman'=>0]);
		}
		// (new NewMarketingPersonSalesman())->where(['person_id'=>$params['id']])->update(['team_id'=>0]);
		return [];
	}

	// 团队基本信息业务转移
	public function teamManagementTransfer($params){
		// 业务转移
		$count = (new NewMarketingPersonMer())->where(['person_id'=>$params['id']])->count();
		if($count > 0){
			(new NewMarketingPersonMer())->where(['person_id'=>$params['id']])->update(['person_id'=>$params['person_id']]);
		}
		return [];
	}

    // 人员提成结算列表搜索所需数据
    public function getSearchData(){
        $data = [];
        $data['select_person'][0] = [
            'name' => '区域代理',
            'value' => (new NewMarketingPerson())->where(['is_agency' => 1, 'is_del' => 0])->field('id as value,name as label')->select()
        ];;
        $data['select_person'][1] = [
            'name' => '业务经理',
            'value' => (new NewMarketingPerson())->where(['is_manager' => 1, 'is_del' => 0])->field('id as value,name as label')->select()
        ];
        $data['select_person'][2] = [
            'name' => '团队',
            'value' => (new NewMarketingTeam())->where(['is_del' => 0])->field('id as value,name as label')->select()
        ];
        if ($data['select_person'][2]['value']) {
            foreach ($data['select_person'][2]['value'] as $k => $v) {
                $data['select_person'][2]['value'][$k]['children'] = [];
                $per_id = (new NewMarketingPersonSalesman())->where(['team_id' => $v['value'], 'is_del' => 0])->column('person_id');
                if ($per_id) {
                    $data['select_person'][2]['value'][$k]['children'] = (new NewMarketingPerson())->where([['id', 'in', $per_id], ['is_del', '=', 0]])->field('id as value,name as label')->select();
                }
            }
        }
        $data['select_person'][3] = [
            'name' => '技术人员',
            'value' => (new NewMarketingArtisan())->where(['status' => 0, 'is_director' => 0])->field('id as value,name as label')->select()
        ];
        $data['select_person'][4] = [
            'name' => '技术主管',
            'value' => (new NewMarketingArtisan())->where(['status' => 0, 'is_director' => 1])->field('id as value,name as label')->select()
        ];
        $data['quit_person'][0] = [
            'name' => '区域代理',
            'value' => (new NewMarketingPerson())->where(['is_agency' => 1, 'is_del' => 1])->field('id as value,name as label')->select()
        ];
        $data['quit_person'][1] = [
            'name' => '业务经理',
            'value' => (new NewMarketingPerson())->where(['is_manager' => 1, 'is_del' => 1])->field('id as value,name as label')->select()
        ];
        $data['quit_person'][2] = [
            'name' => '业务员',
            'value' => (new NewMarketingPerson())->where(['is_salesman' => 1, 'is_del' => 1])->field('id as value,name as label')->select()
        ];
        $data['quit_person'][3] = [
            'name' => '技术人员',
            'value' => (new NewMarketingArtisan())->where(['status' => 1, 'is_director' => 0])->field('id as value,name as label')->select()
        ];
        $data['quit_person'][4] = [
            'name' => '技术主管',
            'value' => (new NewMarketingArtisan())->where(['status' => 1, 'is_director' => 1])->field('id as value,name as label')->select()
        ];

        $data['order_type'] = [//订单类型:-1=全部,0=新订单,1=续费订单
            [
                'label' => '全部',
                'value' => -1
            ],
            [
                'label' => '新订单',
                'value' => 0
            ],
            [
                'label' => '续费订单',
                'value' => 1
            ],
        ];
        $data['order_business'] = [//订单业务:-1=全部,0=店铺,1=社区
            [
                'label' => '全部',
                'value' => -1
            ],
            [
                'label' => '店铺',
                'value' => 0
            ],
            [
                'label' => '社区',
                'value' => 1
            ],
        ];
        return $data;
    }

	/**
	 * @param $uid
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 * 0啥也不是 1业务员 2业务经理 3区域代理 4区域代理和业务经理双重身份 5 技术员 6技术主管
	 */
	public function checkPersonIdentity($uid,$params)
	{
		$where = [];
		$identity = 0;
		$person_info = (new NewMarketingPerson())->where(['uid'=>$uid])->find();
		if (!empty($person_info)) {
            $person_info=$person_info->toArray();
			if ($person_info['is_salesman'] == 1) {//业务员需要person_id
				$identity = 1;
			}

			if ($person_info['is_manager'] == 1) {//业务经理需要team_id 或者person_id
				$identity = 2;
				if ($person_info['is_agency'] == 1) {//双身份需要team_id 或者person_id
					$identity = 4;
				}
			} else {
				if ($person_info['is_agency'] == 1) {//区域代理需要team_id 和person_id
					$identity = 3;
				}
			}
            //团队
            if(isset($params['team_id']) && $params['team_id']){
                $where[] = ['ps.team_id', '=', $params['team_id']];
            }
			//业务员id
			if(isset($params['person_id']) && $params['person_id']){
                $where[] = ['p.id', '=', $params['person_id']];
			}else{
			    if($identity!=3 && $identity!=4 && $identity!=2){
                    $where[] = ['p.id', '=', $person_info['id']];
                }else{
			        if(empty($params['team_id'])){
			            if ($identity == 2) {
                            $team_list = (new NewMarketingTeam())->getSome(['manager_uid' => $person_info['id'],'is_del' => 0])->toArray();
                        } else {
                            $team_list = (new NewMarketingTeam())->getSome(['area_uid' => $person_info['id'],'is_del' => 0])->toArray();
                        }
                        $arr=array();
                        if(!empty($team_list)){
                            foreach ($team_list as $team_k => $team_v) {
                                $arr[]=$team_v['id'];
                            }
                            $where[] = ['ps.team_id', 'in', $arr];
                        }else{
                            //如果是区域代理，有没有团队，只能把他当做业务人员处理了
                            $where[] = ['p.id', '=', $person_info['id']];
                        }
                    }
                }
            }


		} else {
			//查询技术身份
			$artisan_info = (new NewMarketingArtisan())->where(['uid' => $uid])->find();

			if ($artisan_info) {//技术员需要team_id 通过自己的id 查询出自己负责的团队
                $team_ids = (new NewMarketingTeamArtisan())->where(['artisan_id' => $artisan_info['id']])->column('team_id');
//				$team_ids = array_values($team_id);
				//团队
				if ($team_ids) {
					$where[] = ['ps.team_id', 'in', $team_ids];
				}
				$identity = 5;
				if ($artisan_info['is_director'] == 1) {//技术主管需要person_id
					$identity = 6;
				}
			}

		}

		return ['where'=>$where,'identity'=>$identity];
	}

	//获取一条数据
    public function getOneData($where) {
	    $data = (new NewMarketingPerson())->where($where)->find();
	    return $data;
    }




	// 邀请码生成
	public function invitation_code($uid){
		$code = 'Ea5bFdCfDeG3gHyQhAi4Bj1NzkwOPmIJn2RpSTUqV67rMWsX8t9KuLYvZ';
		$rand = $code[rand(0,25)]
			.strtoupper(dechex(date('m')))
			.date('d')
			.substr(time(),-5)
			.substr(microtime(),2,5)
			.sprintf('%02d',rand(0,99));
		for(
			$a = md5( $rand, true ),
			$s = 'Ea5bFdCfDeG3gHyQhAi4Bj1NzkwOPmIJn2RpSTUqV67rMWsX8t9KuLYvZ',
			$d = '',
			$f = 0;
			$f < 6;
			$g = ord( $a[ $f ] ),
			$d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
			$f++
		);
		return $d;
	}


    /**
     * 获得一条数据
     * @return array
     */
    public function getOne($where = [], $field = true, $order = []){
        $res = (new NewMarketingPerson())->getOne($where,$field,$order);
        if(!$res) {
            return [];
        }
        return $res->toArray();
    }

	/**
	 * @param $where
	 */
	public function getPersonTotal($where)
	{
		$achievement = (new NewMarketingOrderTypePerson())->getPersonAchievement($where, 'o.total_price');//时间筛选的订单该业务员的总业绩
		return $achievement;
	}

	public function getTime($config_list,$index){
		$str = $config_list[$index]['list'][0]['typeValue'][$config_list[$index]['list'][0]['value'] - 1]['label'];

		$count1 = strpos($str, '年');
		$count2 = strpos($str, '月');

		if ($count1) {//年
			$num                = substr($str, 0, strrpos($str, "年"));
			$nums               = $this->checkNatInt($num);
			$data['start_time'] = strtotime("-$nums year");
			$data['end_time']   = time();
		} elseif ($count2) {//月
			$num                = substr($str, 0, strrpos($str, "个"));
			$nums               = $this->checkNatInt($num);
			$data['start_time'] = strtotime("-$nums months");
			$data['end_time']   = time();
		} else {
			throw_exception('时间错误');
		}

		return $data;
	}

	/**
	 * @param $str
	 * @return float|int
	 * 汉字数字转换阿拉伯数字
	 */
	public function checkNatInt($str)
	{
		$map = [
			'一'  => '1', '二' => '2', '三' => '3', '四' => '4', '五' => '5', '六' => '6', '七' => '7', '八' => '8', '九' => '9',
			'壹'  => '1', '贰' => '2', '叁' => '3', '肆' => '4', '伍' => '5', '陆' => '6', '柒' => '7', '捌' => '8', '玖' => '9',
			'零'  => '0', '两' => '2',
			'仟'  => '千', '佰' => '百', '拾' => '十',
			'万万' => '亿',
		];

		$str = str_replace(array_keys($map), array_values($map), $str);
		$str = $this->checkString($str, '/([\d亿万千百十]+)/u');

		$func_c2i = function ($str, $plus = false) use (&$func_c2i) {
			if (false === $plus) {
				$plus = ['亿' => 100000000, '万' => 10000, '千' => 1000, '百' => 100, '十' => 10,];
			}

			$i = 0;
			if ($plus)
				foreach ($plus as $k => $v) {
					$i++;
					if (strpos($str, $k) !== false) {
						$ex       = explode($k, $str, 2);
						$new_plus = array_slice($plus, $i, null, true);
						$l        = $func_c2i($ex[0], $new_plus);
						$r        = $func_c2i($ex[1], $new_plus);
						if ($l == 0) $l = 1;
						return $l * $v + $r;
					}
				}

			return (int)$str;
		};
		return $func_c2i($str);
	}

	//来自uct php微信开发框架，其中的checkString函数如下
	function checkString($var, $check = '', $default = '')
	{
		if (!is_string($var)) {
			if (is_numeric($var)) {
				$var = (string)$var;
			} else {
				return $default;
			}
		}
		if ($check) {
			return (preg_match($check, $var, $ret) ? $ret[1] : $default);
		}

		return $var;
	}

	//删除没有身份的业务人员
	public function delPer() {
        $list = (new NewMarketingPerson())->where([['is_agency', '=', 0], ['is_manager', '=', 0], ['is_salesman', '=', 0], ['is_del', '=', 0]])->select();
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                (new NewMarketingPerson())->where(['id' => $v['id']])->update(['is_del' => 1]);
                (new NewMarketingPersonAgency())->where(['person_id' => $v['id']])->update(['is_del' => 1]);
                (new NewMarketingPersonManager())->where(['person_id' => $v['id']])->update(['is_del' => 1]);
                (new NewMarketingPersonSalesman())->where(['person_id' => $v['id']])->update(['is_del' => 1]);
            }
        }
        return true;
    }

}