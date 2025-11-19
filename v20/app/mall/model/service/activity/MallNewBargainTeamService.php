<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewBargainTeam;
use think\Exception;
use think\facade\Db;
class MallNewBargainTeamService
{
    /**
     * @param $uid
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *
     * 判断是否成团
     */
    public function isManInBarginTeam($arr,$goods_price)
    {
        $arr1=(new MallNewBargainTeam())->isManInBarginTeamGoodsAct($arr['user_id'],$arr['act_id'],$arr['sku_id']);
        Db::startTrans();
        if(empty($arr1)) {
            $bar_goods=(new MallNewBargainActService())->getBargainActSkuMsg($arr);//获取砍价活动及商品信息
            if($bar_goods['buy_limit']>0){
                $limit_nums=(new MallNewBargainTeam())->barginTeaNums($arr['user_id'],$arr['act_id'],$arr['sku_id']);
                if($bar_goods['buy_limit']<=$limit_nums){
                    $return['status']=0;
                    $return['msg']="参加砍价活动次数上限,欢迎关注下次砍价活动！";
                    return $return;
                }
            }
            if(empty($bar_goods)){
                $return['status']=0;
                $return['msg']="获取砍价活动商品信息失败";
            }
            //组建队伍需要的信息
            $bars_team['act_id']=$arr['act_id'];
            $bars_team['mer_id']=$arr['mer_id'];
            $bars_team['store_id']=$arr['store_id'];
            $bars_team['user_id']=$arr['user_id'];
            $bars_team['floor_price']=$bar_goods['act_price'];
            $bars_team['bar_num']=0;
            $bars_team['start_time']=time();
            $bars_team['status']=0;
            $bars_team['end_time']=$bar_goods['affect_time']+time();
            if($goods_price==$bar_goods['act_price']){
                $return['status']=0;
                $return['msg']="砍价异常，活动价不能跟商品价格一样";
                return $return;
            }
            $where=[['b.act_id','=', $arr['act_id']],['bg.user_id','=',$arr['user_id']],['bg.status','in',['1,3']]];
            $count=(new MallNewBargainSku())->getCount($where);
            if($count>0){
                if($count>=$bar_goods['buy_limit'] && $bar_goods['buy_limit']!=0){
                    $return['status']=0;
                    $return['msg']="此商品活动砍价次数上限，您可以参与其他商品活动的砍价";
                    return $return;
                }
            }
            if($bar_goods['act_stock_num']==-1 || $bar_goods['act_stock_num']>0){
                $arr2=(new MallNewBargainTeamService())->addData($bars_team);//组建一个砍价队伍
                if($arr2){
                    $return['status']=1;
                    $return['teamId']=$arr2;
                    $return['msg']="成队成功";
                    Db::commit();
                    return $return;
                }
                else{
                    Db::rollback();//回滚操作
                    $return['status']=0;
                    $return['msg']="成队失败";
                    return $return;
                }
            }else{
                $return['status']=0;
                $return['msg']="活动商品库存不足";
                return $return;
            }
        }else{
            $return['status']=0;
            $return['msg']="已有发起的砍价队伍";
            return $return;
        }
    }

    /**
     * @param $arr
     * @return int|string
     * 生成砍价团队
     */
    public function addData($arr)
    {
        return (new MallNewBargainTeam())->addOne($arr);
    }

    /**
     * @param $where
     * @param $data
     * @return mixed
     * 下单更细砍价团队表
     */
    public function updateTeam($where,$data){

        $result = (new MallNewBargainTeam())->saveData($where,$data);
        return $result;
    }

    /**
     * @return array
     * 返回5天前的团队超时数据
     */
    public function getFiveDaysList(){

       return (new MallNewBargainTeam())->getFiveDaysList();

    }
}