<?php


namespace app\new_marketing\model\service;


use app\community\model\db\PackageOrder;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use app\new_marketing\model\db\NewMarketingTeam;

class MarketingPropertyService
{
    /**
     * 获得商家列表
     */
    public function getPropertyList($params){
        $where = [];
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 10;
        $where[] = ['team.id', '=', $params['team_id']];
        // 注册开始时间
        if($params['begin_time']){
            $where[] = ['m.create_time', '>=', strtotime($params['begin_time'])];
        }

        // 注册结束时间
        if($params['end_time']){
            $where[] = ['m.create_time', '<=', strtotime($params['end_time'])+86400];
        }

        // 物业名
        if($params['merchant_name']){
            $where[] = ['m.property_name', 'like', '%'.$params['merchant_name'].'%'];
        }

        // 商家状态
        $where[] = ['m.status', '=', 1];
        $where[] = ['pm.status', '=', 0];
        $where[] = ['pm.type', '=',$params['type']];

        // 业务员
        if($params['user_id']){
            $where[] = ['ps.id', '=', $params['user_id']];
        }
        $field = 'p.id as person_id,pm.team_id,m.property_name as merchant_name,m.property_address,m.property_phone,m.create_time as reg_time,m.id as mer_id,p.name as user_name,pm.id,ps.id as user_id';
        $order = [
            'm.create_time' => 'DESC'
        ];
        $list1 = (new NewMarketingPersonMer())->getPropertyList($where, $field, $order, $page, $pageSize);
        $list=$list1['list'];
        $count = $list1['count'];
        foreach($list as $k=> &$_merchant){
            $_merchant['reg_time'] = date('Y-m-d H:i:s', $_merchant['reg_time']);
            $_merchant['total_money'] = 0;// 订单总金额
            $_merchant['order_num'] = 0;// 订单数量
            // 获得订单列表
            $where = [
                'property_id' => $_merchant['mer_id'],
                'status' => 1,
            ];
            $orderList = (new PackageOrder())->getSelect($where)->toArray();
            if(!empty($orderList)){
                foreach($orderList as $_order){
                    if($_order['order_type'] == 0){// 新订单
                        $_merchant['order_num'] = count($orderList);
                    }
                    $_merchant['total_money'] += $_order['pay_money'];
                }
            }
        }
        $returnArr['sel_saleman']=(new NewMarketingPersonSalesman())->getCanSalesmanFind(['g.team_id'=>$params['team_id'],'g.is_del'=>0],'g.id,b.name');
        $returnArr['list'] = $list;
        $returnArr['count'] = $count;
        return $returnArr;
    }

    /**
     *业务转移
     */
    public function transferBusiness($params){
        $where=[['id','=',$params['id']]];
        $data['person_id']=$params['person_id'];
        $data['team_id']=$params['team_id'];
        $ret=(new NewMarketingPersonMer())->updateThis($where,$data);
        if($ret!==false){
           return true;
        }else{
            return false;
        }
    }

    /**
     * 选择转移的业务员
     */
    public function selectBusinessMan($params){
        $data['person_id']=$params['person_id'];
        $data['team_id']=$params['team_id'];
        $list=(new NewMarketingTeam())->getSome(['is_del'=>0])->toArray();
        $out['list']=[
            'id'=>0,
            'name'=>'',
            'child'=>[]
        ];
        $item=array();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $arr['id']=$v['id'];
                $arr['name']=$v['name'];
                $arr['child']=(new NewMarketingPersonSalesman())->getCanSalesmanFind([['g.person_id','<>',$data['person_id']],['g.team_id','=',$data['team_id']]],'b.name,g.person_id');
                $item[]=$arr;
            }

            if(!empty($item)){
                $put=array();
                foreach ($item as $key=>$value){
                    if($value['id']==$data['team_id']){
                        $put=$value;
                        unset($item[$key]);
                    }
                }
                $item=array_values($item);
                if(!empty($put)){
                    array_unshift($item,$put);
                }
            }
        }
        return $item;
    }
}