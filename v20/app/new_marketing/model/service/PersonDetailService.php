<?php


namespace app\new_marketing\model\service;


use app\community\model\db\PackageOrder;
use app\new_marketing\model\db\NewMarketingArtisan;
use app\new_marketing\model\db\NewMarketingOrder;
use app\new_marketing\model\db\NewMarketingOrderType;
use app\new_marketing\model\db\NewMarketingOrderTypeArtisan;
use app\new_marketing\model\db\NewMarketingOrderTypePerson;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\new_marketing\model\db\NewMarketingTeam;
use app\new_marketing\model\db\NewMarketingTeamArtisan;

class PersonDetailService
{
    /**
     * 个人详情
     */
    public function getPersonDetail($param)
    {
        $out['base_msg'] = [//基本信息
            'image' => cfg('site_url') . "/static/images/user_avatar.jpg",//头像
            'name' => '',//名字
            'tec_name' => '',//身份职位
            'phone' => '',//手机号
            'team_name' => '',//所属团队
            'join_team_type_txt' => '系统添加',//入团方式
            'uid'=>'',
        ];
        $out['is_error']=0;
        $out['achievement'] = [//业务信息
            'merchant_nums' => 0,//商家数量
            'trade_num' => 0,//总订单量
            'achievement' => 0.00,//总业绩
            'achievement_percentage' => 0.00,//总业绩提成
            'team_achievement' => 0.00,//团队总业绩
            'team_achievement_percentage' => 0.00,//团队总提成
        ];

        if($param['identity']==0 || $param['identity']==1){
            $identity=0;
            $business = (new NewMarketingPerson())->getPersonMsg(['p.id' => $param['person_id'], 'p.is_del' => 0], 'p.*,u.avatar');//营销人员信息
            if(empty($business)){
                $out1['is_error']=1;
                $out1['msg']="业务人员身份异常";
                return $out1;
            }
            //业务数据
            $where_business_msg_shop = [['person_id', '=', $business['id']], ['type', '=', 0], ['status', '=', 0]];
            $out['achievement']['merchant_nums'] =$out['business_msg']['merchant_nums'] = (new NewMarketingPersonMer())->getCount($where_business_msg_shop);//商家数量
            if ($param['identity']==0) {
                $sale_man=(new NewMarketingPerson())->getPerson2(['p.id' => $param['person_id'], 'p.is_del' => 0],'bp.*',1);
                if(!empty($sale_man)){
                    $per_team=(new NewMarketingTeam())->getOne([['id','=',$sale_man['team_id']]]);
                    if(!empty($per_team)){
                        $out['base_msg']['team_name']=$per_team['name'];//所属团队
                    }

                    if($sale_man['join_team']){
                        $join_man = (new NewMarketingPerson())->getPersonMsg(['p.id' => $sale_man['join_uid'], 'p.is_del' => 0], 'p.*');//营销人员信息
                        if(!empty($join_man)){
                            $out['base_msg']['join_team_type_txt']=$join_man['name']."邀请";
                        }
                    }
                }
                $identity = 0;//业务员

                $where[] = ['person_id', '=', $business['id']];
                $where[] = ['status', '=', 0];
                $business_man = (new NewMarketingPerson())->getBusinessMsg(['p.uid' => $business['uid'], 'p.is_del' => 0, 'ns.is_del' => 0], 'ns.*,nt.personnel_percent,nt.village_personnel_percent');//业务人员信息
                if (empty($business_man)) {
                    //业务员状态异常,请联系管理员
                    $out1['is_error']=1;
                    $out1['msg']="业务员状态异常,请联系管理员！";
                    return $out1;
                }

                $total_money = 0;//初始化本月订单总金额量
                $total_percent_money = 0;//初始化本月业绩抽成
                $list=(new NewMarketingOrderType())->getBusinessListAndCount([['bp.person_id','=',$business['id']]])['list'];
                if (!empty($list)) {
                    foreach ($list as $key => $val) {
                        $total_percent_money+=$val['person_price'];
                        $total_money+= get_format_number($val['person_price']*100/$val['person_proportion']);
                    }
                }
                $out['achievement']['trade_num'] = (new NewMarketingOrderType())->getBusinessListAndCount(['bp.person_id'=>$business['id']])['count'];//订单总数量
                $out['achievement']['achievement'] = $total_money;//总业绩
                $out['achievement']['achievement_percentage'] = $total_percent_money;//业绩提成
            }

            $per_team=(new NewMarketingTeam())->getOne([['area_uid|manager_uid','=',$business['id']]]);
            if ($param['identity']==1) {
                if(!empty($per_team)){
                    $out['base_msg']['team_name']=$per_team['name'];//所属团队
                }

                $sale_man=(new NewMarketingPerson())->getPerson2(['p.id' => $param['person_id'], 'p.is_del' => 0],'bp.*',2);
                if(!empty($sale_man)){
                    if($sale_man['join_team']){
                        $join_man = (new NewMarketingPerson())->getPersonMsg(['p.id' => $sale_man['join_uid'], 'p.is_del' => 0], 'p.*');//营销人员信息
                        if(!empty($join_man)){
                            $out['base_msg']['join_team_type_txt']=$join_man['name']."邀请";
                        }
                    }
                }


                $business_manager = (new NewMarketingPerson())->getPerson2(['p.uid' => $business['uid'], 'bp.is_del' => 0], 'bp.*', 2);
                if (empty($business_manager)) {//业务经理身份异常
                    $out1['is_error']=1;
                    $out1['msg']="业务经理身份异常";
                    return $out1;
                }

                $team_achievement = 0;//团队总业绩
                $b_list = (new NewMarketingOrderType())->getBusinessList([['p.team_id', '=', $business_manager['team_id']]],'p.*','p.id asc');
                if (!empty($b_list)) {
                    foreach ($b_list as $b_k => $b_v) {
                        if ($b_v['order_type']) {//社区
                            $team_achievement += (new PackageOrder())->getSum(['order_id' => $b_v['order_id']], 'pay_money');
                        } else {
                            $team_achievement += (new NewMarketingOrder())->getSum(['order_id' => $b_v['order_id']], 'total_price');
                        }
                    }
                }

                //个人业绩
                $person_achievement=0;
                $b_person_list = (new NewMarketingOrderType())->getBusinessList([['bp.person_id', '=', $business['id']]],'p.*','p.id asc');
                if (!empty($b_person_list)) {
                    foreach ($b_person_list as $b_k1 => $b_v1) {
                        if ($b_v1['order_type']) {//社区
                            $person_achievement += (new PackageOrder())->getSum(['order_id' => $b_v1['order_id']], 'pay_money');
                        } else {
                            $person_achievement += (new NewMarketingOrder())->getSum(['order_id' => $b_v1['order_id']], 'total_price');
                        }
                    }
                }

                $out['achievement']['trade_num'] = count($b_person_list);//个人订单量
                $out['achievement']['team_achievement'] = get_format_number($team_achievement, 2);//团队总业绩
                $out['achievement']['achievement'] = get_format_number($person_achievement, 2);//个人总业绩
                $out['achievement']['achievement_percentage'] =$person_percentage = (new NewMarketingOrderTypePerson())->getSum([['person_id', '=', $business['id']]], 'person_price');//个人提成
                $achievement_percentage = (new NewMarketingOrderType())->getSumBusiness([['p.team_id', '=', $business_manager['team_id']]], 'manager_price');
                $out['achievement']['team_achievement_percentage'] = get_format_number($person_percentage + $achievement_percentage, 2);//团队总业绩提成
                $identity = 1;//业务经理
            }

            $out['base_msg']['image']=empty($business['avatar']) ? cfg('site_url')."/static/images/user_avatar.jpg" : replace_file_domain($business['avatar']);//头像
            $out['base_msg']['name']=$business['name'];//名字
            $out['base_msg']['tec_name']=(new PersonCenterService())->job_status[$identity];//身份职位
            $out['base_msg']['phone']=$business['phone'];//手机号
            $out['base_msg']['uid']=$business['uid'];//用户id
        }
        //业务信息
        if ($param['identity'] == 3 || $param['identity'] == 4) {//技术部分
            $technology = (new NewMarketingArtisan())->getPersonMsg(['p.id' => $param['person_id'], 'p.status' => 0], 'p.*,u.avatar,u.phone');//技术人员信息
            if(empty($technology)){
                $out1['is_error']=1;
                $out1['msg']="技术员状态异常,请联系管理员！";
                return $out1;
            }
            $identity=3;
            if($param['identity'] == 3){//技术员
                //业务信息
                $team_trade_all_num=0;//总订单量
                $serve_merchant_nums = 0;
                $list_serve = (new NewMarketingTeamArtisan())->getMarketingArtisanTeamList(['g.artisan_id' => $technology['id']], 'g.team_id,a.name')->toArray();
                if (!empty($list_serve)) {
                    foreach ($list_serve as $s_key => $s_value) {
                        $serve_merchant_nums += count((new NewMarketingPersonMer())->getList(['team_id' => $s_value['team_id'], 'type' => 0, 'status' => 0], 'mer_id'));
                        $team_trade_all_num += (new NewMarketingOrderType())->getCount(['team_id' => $s_value['team_id']]);
                    }
                }
                $where_total = [['artisan_id', '=', $technology['id']], ['status', '=', 0]];
                $out['achievement']['achievement_percentage'] = (new NewMarketingOrderTypeArtisan())->getSum($where_total, 'price');//本月订单总提成
                $out['achievement']['merchant_nums'] = $serve_merchant_nums;//服务商家数量
                $out['achievement']['trade_num'] = $team_trade_all_num;//订单数量
                $identity=3;
            }

            if($param['identity'] == 4){//技术主管
                $serve_merchant_nums=0;
                $artisanList0 = (new NewMarketingArtisan())->getMarketingArtisanTeamNoOrderList(['s.id' => $technology['id'], 's.status' => 0], 'mt.achievement,s.team_percent,ms.team_id');//他自己服务的
                $all_team_order_num = 0;
                if (!empty($artisanList0)) {
                    foreach ($artisanList0 as $ks => $vs) {
                        $serve_merchant_nums += (new NewMarketingPersonMer())->getCountMer(['m.team_id' => $vs['team_id'], 'm.type' => 0]);
                        $all_team_order_num += (new NewMarketingOrderType())->getCount(['team_id' => $vs['team_id']]);
                    }
                }
                $out['achievement']['merchant_nums'] = $serve_merchant_nums;//服务总商家
                $out['achievement']['trade_num'] = $all_team_order_num;//总订单量
                $month_achievement=0;
                $self_percent = (new NewMarketingOrderTypeArtisan())->getSum(['artisan_id' => $technology['id']], 'price');//作为技术员的提成
                $artisanList = (new NewMarketingArtisan())->getMarketingArtisanTeamList([['s.director_id', '=', $technology['id']]], 'mt.achievement,s.team_percent');
                if (!empty($artisanList)) {
                    foreach ($artisanList as $k => $v) {
                        $month_achievement += $v['achievement'] * $v['team_percent'];
                    }
                }
                $out['achievement']['achievement_percentage'] = get_format_number($self_percent + $month_achievement, 2);//总业绩提成
                $identity=4;
            }

            $get_team=(new NewMarketingArtisan())->getMarketingArtisanTeamNoOrderList(['s.id'=>$param['person_id']],'mt.name');
            $out['base_msg']['image'] =empty($technology['avatar']) ? '' : replace_file_domain($technology['avatar']);
            $out['base_msg']['name'] = $technology['name'];//名字
            $out['base_msg']['tec_name'] = (new PersonCenterService())->job_status[$identity];//职位
            $out['base_msg']['phone'] = empty($technology['phone'])?'':$technology['phone'];//手机号
            $out['base_msg']['team_name'] = empty($get_team)?'':$get_team[0]['name'];//所属团队
            $out['base_msg']['uid']=$technology['uid'];//用户id
        }

        return $out;
    }
}