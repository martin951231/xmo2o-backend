<?php


namespace app\common\model\service\plan\file;


use app\shop\model\db\ShopRebateUser;

class ShopRebateUserChangeStatus
{
    /**
     * 集单返券无效记录修改状态
     */
    public function runTask(){
        $where[] = ['a.status','=',0];
        $list = (new ShopRebateUser())->getUserList($where);
        $id_arr = [];
        foreach ($list as $item){
            if($item['end_time']<time()){
                $id_arr[] = $item['id'];
            }elseif($item['reset_day']>0){
                $time_string = $item['reset_day']*24*3600;
                if($time_string<(time()-$item['create_time'])){
                    $id_arr[] = $item['id'];
                }
            }
        }
        if($id_arr){
            (new ShopRebateUser())->where([['id','in',$id_arr]])->save(['status'=>2]);
        }
    }
}