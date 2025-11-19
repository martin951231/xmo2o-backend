<?php


namespace app\common\model\service\plan\file;


use app\mall\model\db\MallActivity;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewBargainTeam;
use app\mall\model\service\activity\MallNewBargainTeamService;
use app\mall\model\service\MallOrderService;

class MallNewBargainTeamCheckLoseService
{
    public function runTask()
    {
        //取到5天的超时数据，改变成失效状态，释放有效活动的库存
        $list= (new MallNewBargainTeamService())->getFiveDaysList();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $where=[['id','=',$v['id']]];
                $data['status']=4;
                //订单信息
                $li=(new MallOrderService())->getOrderDetail($v['order_id']);
                $sku_id=$li['detail']['sku_id'];

                $where_act=[['act_id','=',$v['act_id']],['type','=','bargain'],['start_time','<',time()],['end_time','>=',time()]];
                $where_sku=[['act_id','=',$v['act_id']],['sku_id','=',$sku_id]];
                //查看活动是否进行
                $msg=(new MallActivity())->getOne($where_act);
                if(!empty($msg)){
                    $sku=(new MallNewBargainSku())->getBySkuId($where_sku,$field="*");
                    if($sku['act_stock_num']!=-1 && $sku['act_stock_num']>=0){
                        $sku['act_stock_num']=$sku['act_stock_num']+1;
                        $data1['act_stock_num']=$sku['act_stock_num'];
                        //更新库存
                        (new MallNewBargainSku())->saveData($where_sku,$data1);
                    }
                }

                //更新队伍状态
                (new MallNewBargainTeam())->saveData($where,$data);
            }
        }

        //失效的队伍
        $where_list=[['end_time','<',time()],['status','=',0]];
        $listes=(new MallNewBargainTeam())->getAll($where_list,$field="*");
        if(!empty($listes)){
            foreach ($listes as $ks=>$vs){
                $where=[['id','=',$vs['id']]];
                $data['status']=4;
                //订单信息
                $li=(new MallOrderService())->getOrderDetail($vs['order_id']);
                $sku_id=$li['detail']['sku_id'];

                $where_act=[['act_id','=',$vs['act_id']],['type','=','bargain'],['start_time','<',time()],['end_time','>=',time()]];
                $where_sku=[['act_id','=',$vs['act_id']],['sku_id','=',$sku_id]];
                //查看活动是否进行
                $msg=(new MallActivity())->getOne($where_act);
                if(!empty($msg)){
                    $sku=(new MallNewBargainSku())->getBySkuId($where_sku,$field="*");
                    if($sku['act_stock_num']!=-1 && $sku['act_stock_num']>=0){
                        $sku['act_stock_num']=$sku['act_stock_num']+1;
                        $data1['act_stock_num']=$sku['act_stock_num'];
                        //更新库存
                        (new MallNewBargainSku())->saveData($where_sku,$data1);
                    }
                }
                //更新队伍状态
                (new MallNewBargainTeam())->saveData($where,$data);
            }
        }
    }
}