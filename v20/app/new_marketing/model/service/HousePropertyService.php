<?php


namespace app\new_marketing\model\service;


use app\community\model\db\PackageOrder;
use app\new_marketing\model\db\NewMarketingPerson;

class HousePropertyService
{
    //物业详情
    public function housePropertyDetail($houseId)
    {
        $list = (new NewMarketingPerson())->getHourse(['mer.mer_id' => $houseId, 'type' => 1], 'p.name,h.*');
        if (empty($list)) {
            throw new \think\Exception("没有此物业信息！");
        } else {
            $out['code'] = $list['id'];//物业编号
            $out['name'] = $list['property_name'];//物业名称
            $out['phone'] = $list['property_phone'];//物业手机号
            $out['property_address'] = $list['property_address'];//物业地址
            $out['create_time'] = empty($list['create_time']) ? '' : date("Y-m-d", $list['create_time']);//注册时间
            $out['sale_man'] = $list['name'];//业务员
            return $out;
        }
    }

    /**
     * 业务经理/业务员我的物业列表
     */
    public function personManagerHousePropertyList($uid, $identity, $person_id, $page = 1, $pageSize = 10)
    {
        $out['list'] = [];
        $out['total'] = 0;
        //$out['saleman_select'] = [];
        if ($identity == 0 && empty($person_uid)) {//业务员
            $sale_man = (new NewMarketingPerson())->getPerson2(['p.uid' => $uid, 'p.is_salesman' => 1, 'p.is_del' => 0], 'p.id', 1);
            if (!empty($sale_man)) {
                $list = (new NewMarketingPerson())->getHouseList(['mer.person_id' => $sale_man['id'], 'mer.type' => 1], 'hp.id,hp.property_name,hp.create_time', 'mer.mer_id asc', $page, $pageSize);
                $mer_list = array();
                if (!empty($list['list'])) {
                    foreach ($list['list'] as $k => $v) {
                        $arr['property_name'] = $v['property_name'];
                        $arr['houseId'] = $v['id'];
                        $arr['create_time'] = empty($v['create_time']) ? '' : date("Y-m-d H:i:s", $v['create_time']);
                        $arr['order_num'] = (new PackageOrder())->getCount(['property_id' => $v['id'], 'status' => 1]);
                        $mer_list[] = $arr;
                    }
                    $out['list'] = $mer_list;
                } else {
                    $out['list'] = [];
                }
                $out['total'] = $list['count'];
            }
        } elseif ($identity == 1 && empty($person_uid)) {//业务经理
            $sale_man = (new NewMarketingPerson())->getPerson2(['p.uid' => $uid, 'p.is_manager' => 1, 'p.is_del' => 0], 'bp.team_id', 2);
            if (!empty($sale_man)) {
                /*$sale_man_sel = (new NewMarketingPerson())->getSomePerson(['p.is_del' => 0, 'bp.is_del' => 0, 'bp.team_id' => $sale_man['team_id']], 'p.uid,p.id,p.name', true,1);
                if (!empty($sale_man_sel)) {
                    $sale_man1 = array();
                    foreach ($sale_man_sel as $key => $value) {
                        $sel['person_id'] = $value['id'];
                        $sel['person_uid'] = $value['uid'];
                        $sel['name'] = $value['name'];
                        $sale_man1[] = $sel;
                    }
                    $out['saleman_select']=$sale_man1;
                }*/
                $list = (new NewMarketingPerson())->getHouseList(['mer.team_id' => $sale_man['team_id'], 'mer.type' => 1], 'hp.id,hp.property_name,hp.create_time', 'mer.mer_id asc', $page, $pageSize);
                $mer_list = array();
                if (!empty($list['list'])) {
                    foreach ($list['list'] as $k => $v) {
                        $arr['property_name'] = $v['property_name'];
                        $arr['houseId'] = $v['id'];
                        $arr['create_time'] = empty($v['create_time']) ? '' : date("Y-m-d H:i:s", $v['create_time']);
                        $arr['order_num'] = (new PackageOrder())->getCount(['property_id' => $v['id'], 'status' => 1]);
                        $mer_list[] = $arr;
                    }
                    $out['list'] = $mer_list;
                } else {
                    $out['list'] = [];
                }
                $out['total'] = $list['count'];
            }
        } elseif (!empty($person_uid)) {//选择成员
            $sale_man = (new NewMarketingPerson())->getPerson2(['p.id' => $person_id, 'p.is_salesman' => 1, 'p.is_del' => 0], 'p.id', 1);
            if (!empty($sale_man)) {
                $list = (new NewMarketingPerson())->getHouseList(['mer.person_id' => $sale_man['id'], 'mer.type' => 1], 'hp.id,hp.property_name,hp.create_time', 'mer.mer_id asc', $page, $pageSize);
                $mer_list = array();
                if (!empty($list['list'])) {
                    foreach ($list['list'] as $k => $v) {
                        $arr['property_name'] = $v['property_name'];
                        $arr['houseId'] = $v['id'];
                        $arr['create_time'] = empty($v['create_time']) ? '' : date("Y-m-d H:i:s", $v['create_time']);
                        $arr['order_num'] = (new PackageOrder())->getCount(['property_id' => $v['id'], 'status' => 1]);
                        $mer_list[] = $arr;
                    }
                    $out['list'] = $mer_list;
                } else {
                    $out['list'] = [];
                }
                $out['total'] = $list['count'];
            }
        }
        /*else{//啥条件都不符合
            $out['total']=0;
            $out['list'] = [];
        }

        if (empty($sale_man)) {
            $out['total']=0;
            $out['list'] = [];
        }*/
        return $out;
    }
}