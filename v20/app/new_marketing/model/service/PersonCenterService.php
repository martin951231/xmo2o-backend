<?php


namespace app\new_marketing\model\service;


use app\common\model\db\Area;
use app\common\model\service\AreaService;
use app\community\model\db\PackageOrder;
use app\new_marketing\model\db\NewMarketingArtisan;
use app\new_marketing\model\db\NewMarketingJoinLog;
use app\new_marketing\model\db\NewMarketingOrder;
use app\new_marketing\model\db\NewMarketingOrderType;
use app\new_marketing\model\db\NewMarketingOrderTypeArtisan;
use app\new_marketing\model\db\NewMarketingOrderTypePerson;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingPersonAgency;
use app\new_marketing\model\db\NewMarketingPersonManager;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\new_marketing\model\db\NewMarketingTeam;
use app\new_marketing\model\db\NewMarketingTeamArtisan;
use app\new_marketing\model\db\NewMarketingPersonSalesman;

class PersonCenterService
{
    public $job_status = [
        '0' => '业务员',
        '1' => '业务经理',
        '2' => '区域代理',
        '3' => '技术员',
        '4' => '技术主管',
    ];

    public function center($uid)
    {
        //0是业务员 1是业务经理 2是区域代理不是业务经理 3技术员 4技术主管 5是区域代理兼业务经理
        $out['is_error'] = 0;//是否报错
        $out['msg'] = "";//报错信息
        $out['identity'] = '';//身份
        $out['is_team'] = 0;//是否有团队，0无 1有
        $out['base_msg'] = [
            'image' => cfg('site_url')."/static/images/user_avatar.jpg",//头像
            'name' => '',//名字
            'tec_name' => '',//职位
            'qr_code' => '',//二维码
            'area'=>'',//区域代理市区
            'team_name'=>'',//业务员加入的团队
        ];
        $out['achievement'] = [//业绩数据
            'month_achievement' => 0.00,//本月总业绩
            'month_trade_num' => 0,//本月订单量
            'month_achievement_percentage' => 0.00,//本月业绩提成
            'month_person_percentage' => 0.00,//本月个人提成（业务经理作为业务员的提成）
            'bind_team_nums' => 0,//绑定团队数量
            'serve_merchant_nums' => 0,//服务商家数量
            'team_nums' => 0,//团队数量
        ];
        $out['business_msg'] = [//业务数据
            'merchant_nums' => 0,//商家数量
            'village_nums' => 0,//物业数量
            'trade_nums' => 0,//订单数量
        ];
        $out['merchant_register_code'] = '';//商家注册码
        $out['invitation_code'] = '';//成员邀请码
        $out['invite_man'] = '';//业务邀请人
        $out['area_teams'] = [];//区域团队/技术员绑定团队
        $out['team_business_members'] = [];//团队成员员信息
        $out['technology_manage_name'] = '';//技术主管名称
        $out['technology_msg'] = [];//团队技术人员信息
        $start = strtotime(date('Y-m-01 00:00:00'));
        $end = strtotime(date('Y-m-d H:i:s'));

        $business = (new NewMarketingPerson())->getPersonMsg(['p.uid' => $uid, 'p.is_del' => 0], 'p.*,u.avatar');//营销人员信息
        $technology = (new NewMarketingArtisan())->getPersonMsg(['p.uid' => $uid, 'p.status' => 0], 'p.*,u.avatar');//技术人员信息
        if (!empty($business) && empty($technology)) {//是营销人员
            $out['person_id'] =$business['id'];
            $per_team=(new NewMarketingTeam())->getOne([['area_uid|manager_uid','=',$business['id']],['is_del','=',0]]);
            if ($business['is_salesman'] == 1) {
                $get_person=(new NewMarketingPersonSalesman())->getOne(['person_id'=>$business['id'],'is_del'=>0]);
                $out['identity'] = 0;//业务员
                if(!empty($per_team)){
                    $out['is_team'] = 1;
                }
            }

            if ($business['is_manager'] == 1) {
                $out['identity'] = 1;//业务经理
                $get_person=(new NewMarketingPersonManager())->getOne(['person_id'=>$business['id'],'is_del'=>0]);
                if(!empty($per_team)){
                    $out['is_team'] = 1;
                }
            }

            if ($business['is_agency'] == 1) {
                $get_person=(new NewMarketingPersonAgency())->getOne(['person_id'=>$business['id'],'is_del'=>0]);
                $out['identity'] = 2;//区域代理
                if(!empty($per_team)){
                    $out['is_team'] = 1;
                }
            }
            if(empty($get_person)){
                $out['is_error']=1;
                $out['msg']="身份已经被移除,不可查看业务";
                return $out;
            }
            $out['base_msg']['image']=empty($business['avatar']) ? cfg('site_url')."/static/images/user_avatar.jpg" : replace_file_domain($business['avatar']);//头像
            $out['base_msg']['name']=$business['name'];//名字
            $out['base_msg']['tec_name']=$this->job_status[$out['identity']];
            $out['base_msg']['qr_code']='';
            $out['base_msg']['area']='';
            $out['base_msg']['team_name']='';
            if ($business['is_agency'] == 1) {//区域代理不是业务经理
                $business_manager = (new NewMarketingPerson())->getPerson2(['p.uid' => $uid, 'bp.is_del' => 0], 'bp.*', 3);
                if (empty($business_manager)) {//区域代理身份异常
                    $out['is_error']=1;
                    $out['msg']="区域代理身份异常";
                    return $out;
                }
                $city=(new Area())->getNowCityTimezone('area_name',['area_id'=>$business_manager['city_id']]);
                $area_name=(new Area())->getNowCityTimezone('area_name',['area_id'=>$business_manager['area_id']]);
                $out['base_msg']['area']=$city.$area_name;
                $team_month_achievement = 0;//本月区域总业绩
                $month_trade_num = 0;//本月订单量
                $merchant_nums = 0;
                $trade_nums = 0;
                $team_arr = array();
                $team_list = (new NewMarketingTeam())->getSome(['area_uid' => $business['id'],'is_del' => 0])->toArray();
                if (!empty($team_list)) {
                    foreach ($team_list as $team_k => $team_v) {
                        $t_arr['team_id'] = $team_v['id'];
                        $t_arr['name'] = $team_v['name'];
                        if ($team_v['manager_uid'] == $business['id']) {
                            $t_arr['is_your_team'] = 1;
                        } else {
                            $t_arr['is_your_team'] = 0;
                        }
                        $team_arr[] = $t_arr;
                        $merchant_nums += (new NewMarketingPersonMer())->getCountMer(['m.team_id' => $team_v['id'],'m.type'=>0,'t.is_del'=>0]);
                        $trade_nums += (new NewMarketingOrderType())->getCount(['team_id' => $team_v['id']]);//总订单量
                        $b_list = (new NewMarketingOrderType())->getBusinessList([['p.team_id', '=', $team_v['id']], ['bp.add_time', 'between', [$start, $end]]],'p.*','p.id asc');
                        $month_trade_num += count($b_list);
                        if (!empty($b_list)) {
                            foreach ($b_list as $b_k => $b_v) {
                                if ($b_v['order_type']) {//社区
                                    $team_month_achievement += (new PackageOrder())->getSum(['order_id' => $b_v['order_id']], 'pay_money');
                                } else {
                                    $team_month_achievement += (new NewMarketingOrder())->getSum(['order_id' => $b_v['order_id']], 'total_price');
                                }
                            }
                        }
                    }
                }
                $out['achievement']['month_achievement'] = get_format_number($team_month_achievement, 2);//本月总业绩
                $out['achievement']['month_trade_num'] = $month_trade_num;//本月订单量
                if ($business['is_manager'] == 0) {//是区域代理不是业务经理
                    $out['achievement']['month_achievement_percentage'] = (new NewMarketingOrderTypePerson())->getSum(['agent_id' => $business['id']], 'agent_price');//本月代理总提成
                } else {
                    //是区域代理也是业务经理
                    $out['identity'] = 5;
                    $agent_proportion_month_achievement_percentage = (new NewMarketingOrderTypePerson())->getSum(['agent_id' => $business['id']], 'agent_price');//本月代理总提成
                    $manager_proportion_month_achievement_percentage = (new NewMarketingOrderTypePerson())->getSum(['agent_id' => $business['id']], 'manager_price');//本月代理总提成
                    $person_proportion_month_achievement_percentage = (new NewMarketingOrderTypePerson())->getSum(['agent_id' => $business['id']], 'person_price');//本月代理总提成

                    $out['achievement']['month_achievement_percentage'] = get_format_number($agent_proportion_month_achievement_percentage + $manager_proportion_month_achievement_percentage + $person_proportion_month_achievement_percentage);
                }
                $out['business_msg']['merchant_nums'] = $merchant_nums;//区域商家数量
                $out['business_msg']['trade_nums'] = $trade_nums;//总订单量
                $out['area_teams'] = $team_arr;//区域团队
                $out['invitation_code'] = $business_manager['invitation_code'];//业务经理邀请码
            }

            if ($business['is_agency'] == 0 && $business['is_manager'] == 1) {//不是区域代理是业务经理
                $business_manager = (new NewMarketingPerson())->getPerson2(['p.uid' => $uid, 'p.is_del' => 0, 'bp.is_del' => 0], 'bp.*', 2);
                if (empty($business_manager)) {//业务经理身份异常
                    $out['is_error']=1;
                    $out['msg']="业务经理身份异常";
                    return $out;
                }
                if(!empty($business_manager['team_id'])){
                    $y_team=(new NewMarketingTeam())->getOne(['id'=>$business_manager['team_id']]);
                    if(!empty($y_team)){
                        $out['base_msg']['team_name']=$y_team['name'];
                    }
                }

                $team_month_achievement = 0;
                $b_list = (new NewMarketingOrderType())->getBusinessList([['p.team_id', '=', $business_manager['team_id']], ['bp.add_time', 'between', [$start, $end]]],'p.*','p.id asc');
                if (!empty($b_list)) {
                    foreach ($b_list as $b_k => $b_v) {
                        if ($b_v['order_type']) {//社区
                            $team_month_achievement += (new PackageOrder())->getSum(['order_id' => $b_v['order_id']], 'pay_money');
                        } else {
                            $team_month_achievement += (new NewMarketingOrder())->getSum(['order_id' => $b_v['order_id']], 'total_price');
                        }
                    }
                }
                $out['achievement']['month_achievement'] = get_format_number($team_month_achievement, 2);//本月总业绩
                $out['achievement']['month_trade_num'] = count($b_list);//本月订单量
                $out['achievement']['month_person_percentage'] = (new NewMarketingOrderTypePerson())->getSum([['person_id', '=', $business['id']], ['add_time', 'between', [$start, $end]]], 'person_price');//本月个人提成
                $month_achievement_percentage = (new NewMarketingOrderType())->getSumBusiness([['p.team_id', '=', $business_manager['team_id']], ['bp.add_time', 'between', [$start, $end]]], 'manager_price');
                $out['achievement']['month_achievement_percentage'] = get_format_number($out['achievement']['month_person_percentage'] + $month_achievement_percentage, 2);//本月业绩提成

                $where_business_mer = [['m.team_id', '=', $business_manager['team_id']], ['m.type', '=', 0], ['m.status', '=', 0], ['t.is_del', '=', 0]];//团队商家数量条件
                $out['business_msg']['merchant_nums'] = (new NewMarketingPersonMer())->getCountMer($where_business_mer);//团队商家数量
                $out['business_msg']['trade_nums'] = (new NewMarketingOrderType())->getCount([['team_id', '=', $business_manager['team_id']]]);//总订单量
                $out['business_msg']['village_nums'] = (new NewMarketingPersonMer())->getCount([['team_id', '=', $business_manager['team_id']], ['type', '=', 1], ['status', '=', 0]]);//物业数量

                //团队成员
                if($business_manager['team_id']){
                    $where_team_artisan = [['bp.team_id', '=', $business_manager['team_id']], ['bp.is_del', '=', 0]];
                    $team_man_list = (new NewMarketingPerson())->getSomePerson($where_team_artisan, 'u.uid,u.avatar,p.*', 'id asc', 1);//技术人员列表
                    $teamer = array();
                    if (!empty($team_man_list)) {
                        foreach ($team_man_list as $k1 => $v1) {
                            $arr['id'] = $v1['id'];
                            $arr['name'] = $v1['name'];
                            if ($uid == $v1['uid']) {
                                $arr['identity'] = 1;//业务经理
                            } else {
                                $arr['identity'] = 0;//业务员
                            }
                            $arr['image'] = empty($v1['avatar']) ? cfg('site_url')."/static/images/user_avatar.jpg" : replace_file_domain($v1['avatar']);
                            $teamer[] = $arr;
                        }
                        $out['team_business_members'] = $teamer;//团队成员信息
                    }
                }

                $where_team_artisan = [['ms.team_id', '=', $business_manager['team_id']]];
                $team_artisan_list = (new NewMarketingArtisan())->getMarketingTeamArtisanList($where_team_artisan, 'g.id,u.phone,g.name,u.avatar', 'g.id asc');//技术人员列表
                $teamer1 = array();
                if (!empty($team_artisan_list)) {
                    foreach ($team_artisan_list as $k11 => $v11) {
                        $arr['id'] = $v11['id'];
                        $arr['identity'] = 3;
                        $arr['is_self'] = 0;
                        $arr['name'] = $v11['name'];
                        $arr['phone'] = $v11['phone'];
                        $arr['image'] = empty($v11['avatar']) ? cfg('site_url')."/static/images/user_avatar.jpg" : replace_file_domain($v11['avatar']);
                        $teamer1[] = $arr;
                    }
                    $out['technology_msg'] = $teamer1;//团队技术人员信息
                }
                $out['merchant_register_code'] = $business_manager['invitation_code'];//商家注册码
                $out['invitation_code'] = $business_manager['invitation_code'];//成员邀请码
                if ($business_manager['join_team'] == 1) {
                    $where_arri_man = [['p.id', '=', $business_manager['join_uid']]];
                    $out['invite_man'] = ((new NewMarketingPerson())->getPersonMsg($where_arri_man, 'p.name'))['name'];//营销人员信息
                }
            }
            if ($business['is_agency'] == 0 && $business['is_manager'] == 0 && $business['is_salesman'] == 1) {//业务员
                //业务数据
                $where_business_msg_shop = [['person_id', '=', $business['id']], ['type', '=', 0], ['status', '=', 0]];
                $out['business_msg']['merchant_nums'] = (new NewMarketingPersonMer())->getCount($where_business_msg_shop);//商家数量
                $where_business_msg_shop = [['person_id', '=', $business['id']], ['type', '=', 1], ['status', '=', 0]];
                $out['business_msg']['village_nums'] = (new NewMarketingPersonMer())->getCount($where_business_msg_shop);//物业数量
                $business_man = (new NewMarketingPerson())->getBusinessMsg(['p.uid' => $uid, 'p.is_del' => 0, 'ns.is_del' => 0], 'ns.*,nt.personnel_percent,nt.village_personnel_percent');//业务人员信息
                if (empty($business_man)) {
                    //业务员状态异常,请联系管理员
                    $out['is_error']=1;
                    $out['msg']="业务员状态异常,请联系管理员！";
                    return $out;
                }
                $y_team=(new NewMarketingTeam())->getOne(['id'=>$business_man['team_id']]);
                if(!empty($y_team)){
                    $out['base_msg']['team_name']=$y_team['name'];
                }
                $out['merchant_register_code'] = $business_man['invitation_code'];//商家注册码
                $out['invitation_code'] = $business_man['invitation_code'];//成员邀请码
                if ($business_man['join_team'] == 1) {
                    $where_arri_man = [['p.id', '=', $business_man['join_uid']]];
                    $out['invite_man'] = ((new NewMarketingPerson())->getPersonMsg($where_arri_man, 'p.name'))['name'];//营销人员信息
                }
                $where_team_artisan = [['ms.team_id', '=', $business_man['team_id']]];
                $team_artisan_list = (new NewMarketingArtisan())->getMarketingTeamArtisanList($where_team_artisan, 'g.id,u.phone,g.name,u.avatar', 'g.id asc');//技术人员列表
                $teamer = array();
                if (!empty($team_artisan_list)) {
                    foreach ($team_artisan_list as $k1 => $v1) {
                        $arr['id'] = $v1['id'];
                        $arr['identity'] = 3;
                        $arr['is_self'] = 0;
                        $arr['name'] = $v1['name'];
                        $arr['phone'] = $v1['phone'];
                        $arr['image'] = empty($v1['avatar']) ? cfg('site_url')."/static/images/user_avatar.jpg" : replace_file_domain($v1['avatar']);
                        $teamer[] = $arr;
                    }
                    $out['technology_msg'] = $teamer;//团队技术人员信息
                }
                $total_money = 0;//初始化本月订单总金额量
                $total_percent_money = 0;//初始化本月业绩抽成
                $list=(new NewMarketingOrderType())->getBusinessListAndCount([['bp.person_id','=',$business['id']],['add_time','between',[$start, $end]]])['list'];
                if (!empty($list)) {
                    foreach ($list as $key => $val) {
                        $total_percent_money+=$val['person_price'];
                        $total_money+= get_format_number($val['person_price']*100/$val['person_proportion']);
                    }
                }
                $out['business_msg']['trade_nums'] = (new NewMarketingOrderType())->getBusinessListAndCount(['bp.person_id'=>$business['id']])['count'];//订单总数量
                $out['achievement']['month_achievement'] = get_format_number($total_money);//本月总业绩
                $out['achievement']['month_trade_num'] = (new NewMarketingOrderType())->getBusinessListAndCount([['bp.person_id','=',$business['id']],['add_time','between',[$start, $end]]],'bp.id')['count'];//本月订单量
                $out['achievement']['month_achievement_percentage'] = get_format_number($total_percent_money);//本月业绩提成
            }//业务人员
        }
        elseif (!empty($technology) && empty($business)) {//是技术人员身份
            if ($technology['is_director'] == 0) {//技术员
                $out['identity'] = 3;
                //业绩
                $team_trade_all_num = 0;//团队总量
                $out['achievement']['bind_team_nums'] = (new NewMarketingTeamArtisan())->getCount(['artisan_id' => $technology['id']]);//绑定团队数量
                $out['achievement']['serve_merchant_nums'] = 0;//服务商家数量
                $where_total = [['artisan_id', '=', $technology['id']], ['add_time', 'between', [$start, $end]], ['status', '=', 0]];
                $out['achievement']['month_achievement'] = (new NewMarketingOrderTypeArtisan())->getSum($where_total, 'price');//本月订单总提成
                $serve_merchant_nums = 0;
                $list_serve = (new NewMarketingTeamArtisan())->getMarketingArtisanTeamList(['g.artisan_id' => $technology['id']], 'g.team_id,a.name')->toArray();
                $team = array();
                if (!empty($list_serve)) {
                    foreach ($list_serve as $s_key => $s_value) {
                        $serve_merchant_nums += count((new NewMarketingPersonMer())->getList(['team_id' => $s_value['team_id'], 'type' => 0, 'status' => 0], 'mer_id'));
                        $team_trade_all_num += (new NewMarketingOrderType())->getCount(['team_id' => $s_value['team_id']]);
                        $team_arr['team_id'] = $s_value['team_id'];
                        $team_arr['name'] = $s_value['name'];
                        $team[] = $team_arr;
                    }
                    $out['area_teams'] = $team;//绑定团队
                }
                $out['technology_manage_name'] = (new NewMarketingArtisan())->getValues(['id' => $technology['director_id']], 'name');//技术主管名称
                $out['achievement']['serve_merchant_nums'] = $serve_merchant_nums;//服务商家
                $out['business_msg']['merchant_nums'] = $serve_merchant_nums;//商家数量
                $out['business_msg']['trade_nums'] = $team_trade_all_num;//订单数量
            } else {//技术主管
                $out['identity'] = 4;
                //业绩
                $tec_manager = (new NewMarketingArtisan())->getOne(['uid' => $uid, 'is_director' => 1]);
                if (!empty($tec_manager)) {
                    $serve_merchant_nums = 0;
                    $tec_manager = $tec_manager->toArray();
                    $month_achievement = 0;
                    $self_percent = (new NewMarketingOrderTypeArtisan())->getSum(['artisan_id' => $tec_manager['id']], 'price');//作为技术员的提成
                    $artisanList = (new NewMarketingArtisan())->getMarketingArtisanTeamList([['s.director_id', '=', $tec_manager['id']], ['or.add_time', 'between', [$start, $end]]], 'mt.achievement,s.team_percent');
                    if (!empty($artisanList)) {
                        foreach ($artisanList as $k => $v) {
                            $month_achievement += $v['achievement'] * $v['team_percent'];
                        }
                    }
                    $out['achievement']['month_achievement'] = get_format_number($self_percent + $month_achievement, 2);//本月总提成
                    $artisanList0 = (new NewMarketingArtisan())->getMarketingArtisanTeamNoOrderList(['s.id|s.director_id' => $tec_manager['id'], 's.status' => 0], 'mt.achievement,s.team_percent,ms.team_id');//他自己服务的
                    $all_team_order_num = 0;
                    if (!empty($artisanList0)) {
                        foreach ($artisanList0 as $ks => $vs) {
                            $serve_merchant_nums += (new NewMarketingPersonMer())->getCountMer(['m.team_id' => $vs['team_id'], 'm.type' => 0]);
                            $all_team_order_num += (new NewMarketingOrderType())->getCount(['team_id' => $vs['team_id']]);
                        }
                    }
                    $out['achievement']['team_nums'] = count($artisanList0);//团队数
                    $out['achievement']['bind_team_nums'] = (new NewMarketingTeamArtisan())->getCount(['artisan_id' => $tec_manager['id']]);//绑定团队数量
                    $out['achievement']['serve_merchant_nums'] = $serve_merchant_nums;//服务商家数

                    $out['business_msg']['merchant_nums'] = $out['achievement']['serve_merchant_nums'];//服务总商家
                    $out['business_msg']['trade_nums'] = $all_team_order_num;//团队总订单
                    $teamer_list=(new NewMarketingArtisan())->getPersonMsgList([['p.id|p.director_id','=',$tec_manager['id']],['p.status','=',0]],'p.id,p.name,u.avatar');

                    $teamer_arr=array();
                    if(!empty($teamer_list)){
                        foreach ($teamer_list as $t_k=>$t_v){
                            $team_a['id']=$t_v['id'];
                            $team_a['name']=$t_v['name'];
                            $team_a['phone'] = 0;
                            $team_a['image']=empty($t_v['avatar'])?cfg('site_url')."/static/images/user_avatar.jpg":replace_file_domain($t_v['avatar']);
                            if($t_v['id']==$tec_manager['id']){
                                $team_a['identity'] = 4;
                                $team_a['is_self']=1;
                            }else{
                                $team_a['identity'] = 3;
                                $team_a['is_self']=0;
                            }
                            $teamer_arr[]=$team_a;
                        }
                        $out['technology_msg'] =$teamer_arr;//团队技术人员
                    }

                    $teamer_list1=(new NewMarketingArtisan())->getMarketingArtisanTeamNoOrderList([['s.id|s.director_id','=',$tec_manager['id']],['s.status','=',0],['mt.is_del','=',0]],'mt.*');
                    $team = array();
                    if (!empty($teamer_list1)) {
                        foreach ($teamer_list1 as $s_key => $s_value) {
                            $team_arr['team_id'] = $s_value['id'];
                            $team_arr['name'] = $s_value['name'];
                            $team[] = $team_arr;
                        }
                        $out['area_teams'] = $team;//绑定团队
                    }
                } else {
                    $out['is_error']=1;
                    $out['msg']="技术主管身份异常";
                    return $out;
                }
            }

            $out['base_msg'] = [
                'image' => empty($technology['avatar']) ? cfg('site_url')."/static/images/user_avatar.jpg" : replace_file_domain($technology['avatar']),//头像
                'name' => $technology['name'],//名字
                'tec_name' => $this->job_status[$out['identity']],//职位
                'qr_code' => '',//二维码
            ];
        } elseif (!empty($technology) && !empty($business)) {//即是业务又是技术不合理
            $out['is_error']=1;
            $out['msg']="即是业务又是技术不合理";
            return $out;
        } else {//都不是不合理
            $out['is_error']=1;
            $out['msg']="人员既不是业务员也不是技术员";
            return $out;
        }
        return $out;
    }

    /**
     * 获取邀请码
     * @param int $uid 用户ID
     * @param int $type 1=商家注册码,2=成员邀请码,3=业务经理邀请码
     */
    public function getinvitecode($uid, $type)
    {
        $uData = (new MarketingPersonService())->getOneData([['uid', '=', $uid], ['is_del', '=', 0]]);
        if (empty($uData)) {
            throw new \think\Exception(L_("用户不存在"), 1001);
        }
        $data = [
            'share' => '',
            'qrcode' => '',
            'code' => '',
            'team_name' => '',
            'team_id' => 0,
            'area_info' => '',
            'from' => 0,
            'type' => $type,
            'user_type' => 0
        ];
        $merUrl = cfg('site_url') . '/packapp/merchant/reg.html?type=' . $type . '&code=';//商家注册码页面地址
        $perUrl = cfg('site_url') . '/packapp/plat/pages/marketing/join?type=' . $type . '&code=';//成员邀请码页面地址
        $manUrl = cfg('site_url') . '/packapp/plat/pages/marketing/join?type=' . $type . '&code=';//业务经理邀请码页面地址
        if ($uData['is_agency'] == 1 && $uData['is_manager'] == 1) {//区域代理兼业务经理
            $agencyData = (new NewMarketingPersonAgency())->where([['person_id', '=', $uData['id']], ['is_del', '=', 0]])->field('invitation_code,province_id,city_id,area_id')->find() ?? [];//区域代理邀请码
            $province_name = $agencyData['province_id'] ? (new AreaService())->getOne(['area_id' => $agencyData['province_id']])['area_name'] : '';
            $city_name = $agencyData['city_id'] ? (new AreaService())->getOne(['area_id' => $agencyData['city_id']])['area_name'] : '';
            $area_name = $agencyData['area_id'] ? (new AreaService())->getOne(['area_id' => $agencyData['area_id']])['area_name'] : '';
            $data['area_info'] = $province_name . $city_name . $area_name;
            $managerData = (new NewMarketingPersonManager())->where([['person_id', '=', $uData['id']], ['is_del', '=', 0]])->field('team_id,invitation_code')->find() ?? [];//业务经理邀请码
            $teamName = (new NewMarketingTeam())->where(['id' => $managerData['team_id']])->value('name') ?? '';
            switch ($type) {
                case 1:
                    $data['share'] = $merUrl . $managerData['invitation_code'];
                    $data['from'] = 2;
                    $data['code'] = $managerData['invitation_code'];
                    break;
                case 2:
                    $data['share'] = $perUrl . $managerData['invitation_code'] . '&user_type=2';//业务经理邀请成员
                    $data['team_id'] = $managerData['team_id'];
                    $data['team_name'] = $teamName;
                    $data['user_type'] = 2;
                    $data['code'] = $managerData['invitation_code'];
                    break;
                case 3:
                    $data['share'] = $manUrl . $agencyData['invitation_code'] . '&user_type=3';//区域代理邀请业务经理
                    $data['user_type'] = 3;
                    $data['code'] = $agencyData['invitation_code'];
                    break;
                default:
                    throw new \think\Exception(L_("邀请码类型有误"), 1001);
                    break;
            }
        } else if ($uData['is_agency'] == 1 && $uData['is_manager'] == 0) {//区域代理无其他身份
            $agencyData = (new NewMarketingPersonAgency())->where([['person_id', '=', $uData['id']], ['is_del', '=', 0]])->field('invitation_code,province_id,city_id,area_id')->find() ?? [];//区域代理邀请码
            $province_name = $agencyData['province_id'] ? (new AreaService())->getOne(['area_id' => $agencyData['province_id']])['area_name'] : '';
            $city_name = $agencyData['city_id'] ? (new AreaService())->getOne(['area_id' => $agencyData['city_id']])['area_name'] : '';
            $area_name = $agencyData['area_id'] ? (new AreaService())->getOne(['area_id' => $agencyData['area_id']])['area_name'] : '';
            $data['area_info'] = $province_name . $city_name . $area_name;
            switch ($type) {
                case 3:
                    $data['share'] = $manUrl . $agencyData['invitation_code'] . '&user_type=3';//区域代理邀请业务经理
                    $data['user_type'] = 3;
                    $data['code'] = $agencyData['invitation_code'];
                    break;
                case 1:
                case 2:
                default:
                    throw new \think\Exception(L_("邀请码类型有误"), 1001);
                    break;
            }
        } else if ($uData['is_agency'] == 0 && $uData['is_manager'] == 1) {//业务经理
            $managerData = (new NewMarketingPersonManager())->where([['person_id', '=', $uData['id']], ['is_del', '=', 0]])->field('team_id,invitation_code')->find() ?? '';//业务经理邀请码
            $teamName = (new NewMarketingTeam())->where(['id' => $managerData['team_id']])->value('name') ?? '';
            switch ($type) {
                case 1:
                    $data['share'] = $merUrl . $managerData['invitation_code'];
                    $data['from'] = 1;
                    $data['code'] = $managerData['invitation_code'];
                    break;
                case 2:
                    $data['share'] = $perUrl . $managerData['invitation_code'] . '&user_type=2';//业务经理邀请成员
                    $data['team_id'] = $managerData['team_id'];
                    $data['team_name'] = $teamName;
                    $data['user_type'] = 2;
                    $data['code'] = $managerData['invitation_code'];
                    break;
                case 3:
                default:
                    throw new \think\Exception(L_("邀请码类型有误"), 1001);
                    break;
            }
        } else if ($uData['is_agency'] == 0 && $uData['is_manager'] == 0 && $uData['is_salesman'] == 1) {//业务员
            $salesmanData = (new NewMarketingPersonSalesman())->where([['person_id', '=', $uData['id']], ['is_del', '=', 0]])->field('team_id,invitation_code')->find() ?? '';//业务员邀请码
            $teamName = (new NewMarketingTeam())->where(['id' => $salesmanData['team_id']])->value('name') ?? '';
            switch ($type) {
                case 1:
                    $data['share'] = $merUrl . $salesmanData['invitation_code'];
                    $data['from'] = 1;
                    $data['code'] = $salesmanData['invitation_code'];
                    break;
                case 2:
                    $data['share'] = $perUrl . $salesmanData['invitation_code'] . '&user_type=1';//业务员邀请成员
                    $data['team_id'] = $salesmanData['team_id'];
                    $data['team_name'] = $teamName;
                    $data['user_type'] = 1;
                    $data['code'] = $salesmanData['invitation_code'];
                    break;
                case 3:
                default:
                    throw new \think\Exception(L_("邀请码类型有误"), 1001);
                    break;
            }
        } else {
            throw new \think\Exception(L_("用户身份有误"), 1001);
        }
        $data['qrcode'] = cfg('site_url') . $this->createQrcode($data['share']);
        return $data;
    }

	/**
	 * @param $uid
	 * @param $type 1=邀请成员,2=邀请业务经理
	 * @param $user_type 1业务员，2=业务经理，3=区域代理
	 * @param $code
	 * 如果是 业务员分享的，成为当前业务员的团队的业务员
	 * 如果是 业务经理分享的，成为当前业务经理团队下的业务员
	 * 如果是 区域代理分享的，成为当前区域代理的 业务经理（无团队）
	 */
	public function doJoinInfo($uid, $type, $user_type, $code,$phone,$reasons,$name) {
		if($user_type == 1){
			$person_info = (new NewMarketingPersonSalesman())->where(['invitation_code' => $code])->find();
		}elseif ($user_type == 2){
			$person_info = (new NewMarketingPersonManager())->where(['invitation_code' => $code])->find();
		}elseif ($user_type == 3){
			$person_info = (new NewMarketingPersonAgency())->where(['invitation_code' => $code])->find();
		}else{
			throw_exception('类型错误');
		}
//判断当前用户是否有身份
        $user_info = (new NewMarketingPerson)->where(['uid' => $uid])->find();
        $errorMsg = '';
        switch ($user_type) {
            case 1:
                $user_info['is_salesman'] && $errorMsg = '你已是业务员,无法申请加入';
                break;
            case 2:
                $user_info['is_manager'] && $errorMsg = '你已是业务经理,无法申请加入';
                break;
            case 3:
                $user_info['is_agency'] && $errorMsg = '你已是区域代理,无法申请加入';
                break;
            default:
                break;
        }
        $errorMsg && throw_exception($errorMsg);

		if($user_info && $user_info['is_agency'] == 0 && $user_info['is_manager'] == 0 && $user_info['is_salesman'] == 0){
			if ($type == 2) {//成为成员
				(new NewMarketingPerson)->where(['id' => $user_info['id']])->save(['is_salesman' => 1]);
				//添加业务员
				$add['person_id']       = $user_info['id'];
				$add['invitation_code'] = (new RegionalAgencyService())->makeInvitationCode();
				$add['add_time']        = time();
				$add['team_id']         = $person_info['team_id'];
				$add['join_team']       = 1;
				$add['join_uid']        = $person_info['person_id'];

				(new NewMarketingPersonSalesman())->add($add);

			}elseif ($type == 3){//成为业务经理
				(new NewMarketingPerson)->where(['id' => $user_info['id']])->save(['is_manager' => 1]);//没团队不作为业务员
//				//添加业务员
//				$add['person_id']       = $user_info['id'];
//				$add['invitation_code'] = (new RegionalAgencyService())->makeInvitationCode();
//				$add['add_time']        = time();
//				$add['team_id']         = 0;
//				$add['join_team']       = 1;
//				$add['join_uid']        = $person_info['person_id'];
//
//				(new NewMarketingPersonSalesman())->add($add);

				//添加业务经理
				$arr['invitation_code'] = (new RegionalAgencyService())->makeInvitationCode();
				$arr['province_id']     = $person_info['province_id'];
				$arr['city_id']         = $person_info['city_id'];
				$arr['area_id']         = $person_info['area_id'];
				$arr['add_time']        = time();
				$arr['person_id']       = $user_info['id'];
				$arr['join_team']       = 1;
				$arr['join_uid']        = $person_info['person_id'];

				(new NewMarketingPersonManager())->add($arr);
			}else{
				throw_exception('类型错误');
			}
		} else {
			if ($type == 2) {//成为成员
				if (empty($user_info)) {//不存在，可以添加
					$data['name']        = $name;
					$data['is_salesman'] = 1;
					$data['uid']         = $uid;
					$data['phone']       = $phone;

					$ret = (new NewMarketingPerson)->add($data);

					//添加业务员
					$add['person_id']       = $ret;
					$add['invitation_code'] = (new RegionalAgencyService())->makeInvitationCode();
					$add['add_time']        = time();
					$add['team_id']         = $person_info['team_id'];
					$add['join_team']       = 1;
					$add['join_uid']        = $person_info['person_id'];

					(new NewMarketingPersonSalesman())->add($add);
				}
			} elseif ($type == 3) {//成为业务经理
				$data['name']        = $name;
				$data['is_manager']  = 1;
				$data['uid']         = $uid;
				$data['phone']       = $phone;

				$ret = (new NewMarketingPerson)->add($data);

//				//添加业务员
//				$add['person_id']       = $ret;
//				$add['invitation_code'] = (new RegionalAgencyService())->makeInvitationCode();
//				$add['add_time']        = time();
//				$add['team_id']         = 0;
//				$add['join_team']       = 1;
//				$add['join_uid']        = $person_info['person_id'];
//
//				(new NewMarketingPersonSalesman())->add($add);

				//添加业务经理
				$arr['invitation_code'] = (new RegionalAgencyService())->makeInvitationCode();
				$arr['province_id']     = $person_info['province_id'];
				$arr['city_id']         = $person_info['city_id'];
				$arr['area_id']         = $person_info['area_id'];
				$arr['add_time']        = time();
				$arr['person_id']       = $ret;
				$arr['join_team']       = 1;
				$arr['join_uid']        = $person_info['person_id'];

				(new NewMarketingPersonManager())->add($arr);
			} else {
				throw_exception('类型错误');
			}
		}
		//记录
		$log['person_id'] = $ret;
		$log['invite_id'] = $person_info['person_id'];
		$log['reasons']   = $reasons;
		$log['add_time']  = time();
		$log['type']      = 0;

		(new NewMarketingJoinLog())->add($log);

		if($type == 2){
			$team_name = (new NewMarketingTeam())->where(['id'=>$person_info['team_id']])->value('name');
			$msg = '你已成为' . $team_name . '团队业务员';
		}elseif($type == 3){
			$msg = '你已成为该区代业务经理 可联系平台创建团队';
		}
		return $msg;
	}

    /**
     * 生成以分享链接为参数的链接二维码，供营销系统邀请码
     * @param  [type] $qrCon 分享链接
     */
    public function createQrcode($qrCon)
    {
        require_once '../extend/phpqrcode/phpqrcode.php';
        $dir = '/upload/newmarketshare/' . date('Ymd');
        $path = '../..' . $dir;
        $filename = uniqid() . time() . '.png';
        if (file_exists($path . '/' . $filename)) {
            return $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return $dir . '/' . $filename;
    }

}