<?php
/**
 * 统计分析
 */

namespace app\community\model\service;


use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseProperty;
use app\community\model\db\HouseVillage;
use app\community\model\db\WisdomQrcodeStatistics;

class StatisticsService
{
    /**
     * 获取缴费率
     */
    public function getContributionRateList($param)
    {
        $page = $param['page'] < 1 ? 0 : ($param['page']-1); 
        if(!in_array($param['type'],['village','charge_type'])){
            throw_exception('查询类型异常');
        }
        $where = [
            ['o.is_paid','=',1],
            ['o.village_id','>',0],
            ['o.pay_money','>',0]
        ];
        $field = ['o.order_id','o.village_id','o.property_id','o.order_type',
            "SUM(o.modify_money) as modify_money_all",//应收费用
            "SUM(o.modify_money+o.late_payment_money-o.pay_money) as discount_money_all",//免收费用
            "SUM(o.pay_money) as pay_money_all",//实收费用
            "SUM(o.refund_money) as refund_money_all",//退款费用
            "SUM(o.pay_money-o.refund_money) as now_pay_money_all",//实际费用
            "((SUM(o.pay_money)-SUM(o.refund_money))/SUM(o.pay_money)*100) as contribution_rate",//缴费率
        ];
        $order = 'contribution_rate desc,pay_money_all desc';
        if($param['type'] == 'village'){
            $group = 'o.village_id';
        }else 
        if($param['type'] == 'charge_type'){
            $group = 'o.village_id,o.order_type';
        }
        $list = (new HouseNewPayOrder())->getContributionRateList($where,$field,$order,$group,$param['pageSize']);
        foreach ($list['data'] as $k => $v){
            //获取排名
            $list['data'][$k]['sort'] = $page*$param['pageSize']+$k+1;
            //获取小区名称
            $list['data'][$k]['village_name'] = $v['village_id'] ? (new HouseVillage())->where('village_id',$v['village_id'])->value('village_name') : '';
            //获取物业名称
            $list['data'][$k]['property_name'] = $v['property_id'] ? (new HouseProperty())->where('id',$v['property_id'])->value('property_name') : '';
            //获取收费类别名称
            $list['data'][$k]['charge_type_name'] = (new HouseNewChargeService())->getType($v['order_type']);
                //处理返回数据
            $list['data'][$k]['modify_money_all'] = getFormatNumber($v['modify_money_all']);
            $list['data'][$k]['discount_money_all'] = getFormatNumber($v['discount_money_all']);
            $list['data'][$k]['pay_money_all'] = getFormatNumber($v['pay_money_all']);
            $list['data'][$k]['refund_money_all'] = getFormatNumber($v['refund_money_all']);
            $list['data'][$k]['now_pay_money_all'] = getFormatNumber($v['now_pay_money_all']);
            $list['data'][$k]['contribution_rate'] = getFormatNumber($v['contribution_rate']).'%';
        }
        return $list;
    }
    /**
     * 获取工单完成率
     */
    public function getRepairRateList($param)
    {
        $page = $param['page'] < 1 ? 0 : ($param['page']-1);
        $where = [
            ['village_id','>',0]
        ];
        $field = ['order_id','village_id','property_id',
            'count(order_id) as count',//工单总数
            'SUM(case when event_status>9 and event_status<20 then 1 else 0 end) as todo_num',//待处理
            'SUM(case when event_status>19 and event_status<40 then 1 else 0 end) as processing_num',//处理中
            'SUM(case when event_status>39 then 1 else 0 end) as processed_num',//已处理
            'SUM(case when event_status>39 then 1 else 0 end)/count(order_id)*100 as completion_rate',//完成率
            ];
        $order = 'completion_rate desc,count desc';
        $group = 'village_id';
        $list = (new HouseNewRepairWorksOrder())->getRepairRateList($where,$field,$order,$group,$param['pageSize']);
        foreach ($list['data'] as $k => $v){
            //获取排名
            $list['data'][$k]['sort'] = $page*$param['pageSize']+$k+1;
            //获取小区名称
            $list['data'][$k]['village_name'] = $v['village_id'] ? (new HouseVillage())->where('village_id',$v['village_id'])->value('village_name') : '';
            //获取物业名称
            $list['data'][$k]['property_name'] = $v['property_id'] ? (new HouseProperty())->where('id',$v['property_id'])->value('property_name') : '';
            //处理返回数据
            $list['data'][$k]['count'] = getFormatNumber($v['count']);
            $list['data'][$k]['todo_num'] = getFormatNumber($v['todo_num']);
            $list['data'][$k]['processing_num'] = getFormatNumber($v['processing_num']);
            $list['data'][$k]['processed_num'] = getFormatNumber($v['processed_num']);
            $list['data'][$k]['completion_rate'] = getFormatNumber($v['completion_rate']).'%';
        }
        return $list;
    }
    /**
     * 获取巡检完成率
     */
    public function getPatrolInspectionRateList($param)
    {
        $page = $param['page'] < 1 ? 0 : ($param['page']-1);
        $field = ['village_id','property_id',
            'SUM(patrol_inspection_num) as patrol_inspection_num_all',//巡检总次数
            'SUM(patrol_inspection_num-patrol_inspection_completion_num) as todo_num',//待巡检
            'SUM(patrol_inspection_completion_num) as patrol_inspection_completion_num_all',//已巡检
            'SUM(patrol_inspection_completion_num)/SUM(patrol_inspection_num) as completion_rate',//巡检完成率
        ];
        $order = 'completion_rate desc,patrol_inspection_num_all desc';
        $group = 'village_id';
        $list = (new WisdomQrcodeStatistics())->getPatrolInspectionRateList($field,$order,$group,$param['pageSize']);
        foreach ($list['data'] as $k => $v){
            //获取排名
            $list['data'][$k]['sort'] = $page*$param['pageSize']+$k+1;
            //获取小区名称
            $list['data'][$k]['village_name'] = $v['village_id'] ? (new HouseVillage())->where('village_id',$v['village_id'])->value('village_name') : '';
            //获取物业名称
            $list['data'][$k]['property_name'] = $v['property_id'] ? (new HouseProperty())->where('id',$v['property_id'])->value('property_name') : '';
            //处理返回数据
            $list['data'][$k]['patrol_inspection_num_all'] = getFormatNumber($v['patrol_inspection_num_all']);
            $list['data'][$k]['todo_num'] = getFormatNumber($v['todo_num']);
            $list['data'][$k]['patrol_inspection_completion_num_all'] = getFormatNumber($v['patrol_inspection_completion_num_all']);
            $list['data'][$k]['completion_rate'] = getFormatNumber($v['completion_rate']).'%';
        }
        return $list;
    }
}