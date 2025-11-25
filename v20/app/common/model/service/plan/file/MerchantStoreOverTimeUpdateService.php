<?php


namespace app\common\model\service\plan\file;


use app\merchant\model\db\MerchantStore;

class MerchantStoreOverTimeUpdateService
{
    /**
     * @param
     * 自动更新店铺过期时间
     */
    public function runTask()
    {
        $time=time();
        $where=[['status','=',1],['end_time','<>',0],['end_time','<',$time]];
        $list=(new MerchantStore())->getSome($where)->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $update['status']=0;
                $where_up=[['store_id','=',$v['store_id']]];
                (new MerchantStore())->updateThis($where_up,$update);
            }
        }
    }
}