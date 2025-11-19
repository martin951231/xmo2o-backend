<?php


namespace app\common\model\service\plan\file;

use app\common\model\db\Admin;
use app\common\model\db\Config;
use app\common\model\db\VillageGroupGoods;
use app\common\model\db\WarnList;
use app\common\model\service\weixin\TemplateNewsService;
use app\warn\model\db\WarnNotice;
use think\facade\Db;

class VillageGroupGoodsStockWarn
{
    /**
     * 社区团购库存不足预警
     */
    public function runTask()
    {
        return false;
        $nowTime = time();
        //查询是否配置商品缺货提醒
        $village_group_set = (new Config())->where([['name','in',['village_group_threshold_warning','village_group_threshold_warning_member','village_group_threshold_warning_times']]])->select();
        $village_group_set = $village_group_set->toArray();
        if(empty($village_group_set)||!$village_group_set){
            return false;
        }
        $v_set = array_column($village_group_set,'value','name');
        $village_group_threshold_warning = isset($v_set['village_group_threshold_warning'])&&$v_set['village_group_threshold_warning']?$v_set['village_group_threshold_warning']:0; //商品阈值
        $village_group_threshold_warning_member = isset($v_set['village_group_threshold_warning_member'])&&$v_set['village_group_threshold_warning_member']?$v_set['village_group_threshold_warning_member']:''; //推送管理员
        $village_group_threshold_warning_times = isset($v_set['village_group_threshold_warning_times'])&&$v_set['village_group_threshold_warning_times']?1:0; //0-提醒一次，1-每天提醒一次
        
        $where_warn = [];
        $where_warn[] = ['status','=',1];
        $where_warn[] = ['business','=','village_group'];
        $where_warn[] = ['type','=',0];
        $is_warn_only_once = 0;
        if($village_group_threshold_warning_times==1){
            $where_warn[] = ['create_time','>=',strtotime(date('Ymd'))];
            $where_warn[] = ['create_time','<',strtotime(date('Ymd',strtotime('+1 day')))];
            $where_warn[] = ['is_warn_only_once','=',0];
        }else{
            $where_warn[] = ['is_warn_only_once','=',1];
            $is_warn_only_once = 1;
        }
        
        //获取发送人列表
        $where = [['status', '=', 1],['id','in',explode(',',$village_group_threshold_warning_member)]];
        $admin_arr = (new Admin())->where($where)->select();
        //获取库存提醒商品
        $goods_where = 'stock_num != -1 and status in (1,2) and (stock_num-sell_count)<'.$village_group_threshold_warning;
        $goods_list = (new VillageGroupGoods())->where($goods_where)->order('goods_id asc')->select();

        $model = new TemplateNewsService();
        $warn_mode = new WarnList();
        $warn_nitice_mode = new WarnNotice();
        $logData = $sendMsg = $noticeData = [];
        foreach ($goods_list as $item){
            $where_warn[] = ['goods_id','=',$item['goods_id']];
            $msg = L_('您的【X1】库存小于“库存阈值”【X2】,请及时补充！',['X1' => $item['name'],'X2' => $village_group_threshold_warning]);
            //主表提醒
            $notice_log = $warn_nitice_mode->where($where_warn)->find();
            if(!$notice_log){
                $noticeData[] = [
                    'business'=>'village_group',
                    'type'=>0,
                    'mer_id'=>$item['mer_id'],
                    'store_id'=>$item['store_id']?:0,
                    'goods_id'=>$item['goods_id'],
                    'is_warn_only_once'=>$is_warn_only_once,
                    'create_time'=>time(),
                    'title' => L_('【社区团购】商品库存不足'),
                    'content' => $msg,
                    ];
            }
            foreach ($admin_arr as $val) {
                //子表提醒
                if($val['openid']){
                    $where_warn[] = ['openid','=',$item['openid']];
                    $log = $warn_mode->where($where_warn)->find();
                    if($log){//已经提醒过
                        continue;
                    }
                    $logData[] = [
                        'openid'=>$item['openid'],
                        'business'=>'village_group',
                        'type'=>0,
                        'mer_id'=>$item['mer_id'],
                        'store_id'=>$item['store_id']?:0,
                        'goods_id'=>$item['goods_id'],
                        'is_warn_only_once'=>$is_warn_only_once,
                        'create_time'=>time(),
                        'title' => L_('【社区团购】商品库存不足'),
                        'content' => $msg,
                    ];
                    $sendMsg[] = [
                        'href' => '',
                        'wecha_id' => $val['openid'],
                        'first' => $msg,
                        'keyword1' => L_('【社区团购】商品库存不足'),
                        'keyword3' => date("Y-m-d H:i")
                    ];
                    unset($where_warn[count($where_warn)-1]);
                }
            }
            unset($where_warn[count($where_warn)-1]);
        }
        if ($logData||$noticeData) {
            
            //启动事务
            Db::startTrans();
            try {
                //写入主表
                if($noticeData){
                    (new WarnNotice())->insertAll($noticeData);
                }
                //写入子表
                if($logData){
                    (new WarnList())->insertAll($logData);
                }
                
                foreach ($sendMsg as $v){
                    $model->sendTempMsg('OPENTM400166399', $v);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
    }
}