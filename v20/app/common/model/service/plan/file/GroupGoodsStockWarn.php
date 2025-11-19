<?php


namespace app\common\model\service\plan\file;

use app\common\model\db\Merchant;
use app\common\model\db\PlanPaging;
use app\common\model\db\StockWarn;
use app\common\model\db\WarnList;
use app\common\model\service\weixin\TemplateNewsService;
use app\group\model\db\Group;
use app\warn\model\db\WarnNotice;
use think\facade\Db;

class GroupGoodsStockWarn
{
    /**
     * 团购库存不足预警,废弃
     */
    public function runTask()
    {
        //获取计划任务执行记录
        $record = (new PlanPaging())->where(['file'=>'GroupGoodsStockWarn'])->findOrEmpty()->toArray();
        if(empty($record)||!$record){
            $record = [
                'file' => 'GroupGoodsStockWarn',
                'mer_id' => 0,
            ];
        }
        $record['last_time'] = time();
        //获取当前商家列表
        $mer_list = (new Merchant())->field('mer_id,name')->where([['status','=',1],['mer_id','>',$record['mer_id']]])->order('mer_id asc')->limit(15)->select()->toArray();
        if(!$mer_list){
            return false;
        }
        $record['mer_id'] = $mer_list[count($mer_list)-1]['mer_id'];
        //商家名称
        $mer_arr = array_column($mer_list,'name','mer_id');
        Db::startTrans();
        try {
            foreach ($mer_list as $vMerInfo){
                //获取管理人员
                $setInfo = (new StockWarn())->getGroupSet([['a.business','=','group'],['a.mer_id','=',$vMerInfo['mer_id']],['a.status','=',1]],'a.mer_id,a.min_num,a.apply_after_sales_time,a.is_warn_only_once,b.work_time,c.openid');
                if(!$setInfo||empty($setInfo)){
                    continue;
                }
                $workUserAry = [];
                foreach ($setInfo as $kSet=>$vSet){
                    if(!$vSet['work_time']){
                        unset($setInfo[$kSet]);
                        continue;
                    }
                    $setInfo[$kSet]['work_time'] = json_decode($vSet['work_time'],true);
                    $workOpenid = 0;//工作人员id
                    foreach ($setInfo[$kSet]['work_time'] as $week){
                        if($week['week']==date("w") && $week['is_work']==1){
                            $workOpenid = $vSet['openid'];
                        }
                    }
                    if(!$workOpenid){//只获取今天有人工作接收提醒的商家
                        continue;
                    }
                    //获取今天工作的工作人员信息
                    $workUserAry[$vSet['mer_id']][] = $workOpenid;
                }
                $merMinNumAry = array_column($setInfo,'min_num','mer_id');
                $merWarnOnceAry = array_column($setInfo,'is_warn_only_once','mer_id');
                $where = [
                    ['a.mer_id','=',$vMerInfo['mer_id']],
                    ['a.status','=',1],
                    ['','exp',Db::raw('((a.is_sku = 0 and a.count_num > 0 and ((a.count_num-a.sale_count) <= '.$merMinNumAry[$vMerInfo['mer_id']].')) or (a.is_sku = 1 and b.count_num > 0 and b.count_num <= '.$merMinNumAry[$vMerInfo['mer_id']].'))')],
                ];
                $goods_count = (new Group())->getStockWarnGoodsCount($where);
                $page_total = ceil($goods_count/20);
                $field = 'a.mer_id,a.group_id,a.name,b.count_num,b.specifications_id as sku_id,b.specifications_name';
                $addData = $msgDataWx = $noticeData = [];
                for ($i=1;$i<=$page_total;$i++){
                    $goods = (new Group())->getStockWarnGoodsInfo($where,$i,20,$field);//查询多规格库存不足的商品
                    foreach ($goods as $vG){
                        $msg = L_('您的【X1】里【X2】X3库存小于“库存阈值”【X4】,请及时补充。',array('X1' => $mer_arr[$vG['mer_id']], 'X2' => $vG['name'], 'X3' => $vG['specifications_name']?:'', 'X4' => $merMinNumAry[$vMerInfo['mer_id']]));
                        //是否通知商家
                        $where_notice = [
                            ['business','=','group'],
                            ['type','=',0],
                            ['mer_id','=',$vG['mer_id']],
                            ['goods_id','=',$vG['group_id']],
                            ['goods_sku_id','=',$vG['sku_id']?:0],
                            ['is_warn_only_once','=',$merWarnOnceAry[$vG['mer_id']]],
                            ['status','=',1],
                        ];
                        if(!$merWarnOnceAry[$vG['mer_id']]){//每日提醒
                            $where_notice[] = ['create_time','>=',strtotime(date('Ymd'))];
                            $where_notice[] = ['create_time','<=',time()];
                        }
                        $log = (new WarnNotice())->where($where_notice)->find();
                        if(!$log&&!isset($noticeData[$vMerInfo['mer_id'].'_'.$vG['group_id']])){//未提醒过
                            $noticeData[$vMerInfo['mer_id'].'_'.$vG['group_id']] = [
                                'business'=>'group',
                                'type'=>0,
                                'mer_id'=>$vG['mer_id'],
                                'store_id'=>0,
                                'goods_id'=>$vG['group_id'],
                                'goods_sku_id'=>$vG['sku_id']?:0,
                                'is_warn_only_once'=>$merWarnOnceAry[$vG['mer_id']],
                                'create_time'=>time(),
                                'title' => L_('【团购】商品库存不足'),
                                'content' => $msg,
                            ];
                        }
                        if(!isset($workUserAry[$vG['mer_id']])){
                            continue;
                        }
                        //管理员提醒
                        //记录库存不足的商品信息
                        foreach ($workUserAry[$vG['mer_id']] as $vv){
                            $where_notice[] = ['openid','=',$vv];
                            $log_warn = (new WarnList())->where($where_notice)->find();
                            if(empty($log_warn)||!$log_warn){
                                $addData[] = [
                                    'openid'=>$vv,
                                    'business'=>'group',
                                    'type'=>0,
                                    'mer_id'=>$vG['mer_id'],
                                    'store_id'=>0,
                                    'goods_id'=>$vG['group_id'],
                                    'goods_sku_id'=>$vG['sku_id']?:0,
                                    'is_warn_only_once'=>$merWarnOnceAry[$vG['mer_id']],
                                    'create_time'=>time(),
                                    'title' => L_('【团购】商品库存不足'),
                                    'content' => $msg,
                                ];
                                $msgDataWx[] = [
                                    'openid'=>$vv,
                                    'type'=>0,
                                    'store_id'=>0,
                                    'goods_name'=>$vG['name'],
                                    'mer_id'=>$vG['mer_id'],
                                    'msg' => $msg
                                ];
                            }
                        }
                    }
                }
                if($noticeData||$addData){
                    //写入总表
                    if($noticeData){
                        $noticeData = array_values($noticeData);
                        (new WarnNotice())->insertAll($noticeData);
                    }
                    //写入分表
                    if($addData){
                        (new WarnList())->insertAll($addData);
                    }
                    //发送公众号信息
                    foreach ($msgDataWx as $vWx){
                        $title = L_('【团购】商品库存不足');
                        $msgDataWx = [
                            'href' => '',
                            'wecha_id' => $vWx['openid'],
                            'first' => $vWx['msg'],
                            'keyword1' => $title,
                            'keyword3' => date("Y-m-d H:i"),

                        ];
                        (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
                    }
                }
            }
            if(isset($record['pigcms_id'])){
                (new PlanPaging())->where([['pigcms_id','=',$record['pigcms_id']]])->save($record);
            }else{
                (new PlanPaging())->save($record);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
    }
}