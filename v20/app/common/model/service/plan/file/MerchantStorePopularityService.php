<?php


namespace app\common\model\service\plan\file;


use app\common\model\db\MerchantUserRelation;
use app\common\model\db\Reply;
use app\common\model\db\SystemOrder;
use app\merchant\model\db\MerchantStore;

class MerchantStorePopularityService
{
    public function runTask()
    {
        $list=(new MerchantStore())->getSome()->toArray();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $where=[['r.store_id','=',$val['store_id']]];
                //评分
                $score=(new Reply())->getScore($where);
                $data=array();
                if(!empty($score)){
                    $data['score']= ceil($score[0]['r_score']);
                }

                //15天评论量
                $start=time()-15*86400;
                $where=[['m.store_id','=',$val['store_id']],['m.dateline','between',[$start,time()]]];
                $nums=(new MerchantUserRelation())->get_merchant_fans($where);
                if($nums>0){
                    $data['fifteen_day_reply_num']=$nums;
                }
                //15天销量
                $where=[['store_id','=',$val['store_id']],['pay_time','between',[$start,time()]],['paid','=',1]];
                $sale_num=(new SystemOrder())->getNums($where,'num');
                if($sale_num>0){
                    $data['fifteen_day_sale_num']=$sale_num;
                }

                if(!empty($data)){
                    $where=[['store_id','=',$val['store_id']]];
                    (new MerchantStore())->updateThis($where,$data);
                }
            }
        }
    }
}