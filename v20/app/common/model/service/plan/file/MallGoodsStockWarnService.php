<?php
/**
 * 新版商城商品库存不足提醒以及订单申请售后提现
 */

namespace app\common\model\service\plan\file;

use app\common\model\db\PlanPaging;
use app\common\model\db\StockWarn;
use app\common\model\db\WarnList;
use app\common\model\db\WarnUser;
use app\common\model\service\weixin\TemplateNewsService;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderRefund;
use app\mall\model\db\MerchantStore;
use app\warn\model\db\WarnNotice;
use think\facade\Db;

class MallGoodsStockWarnService
{
    //废弃
    public function runTask()
    {
        //查询上次的最后一个商家id
        $lastMer = (new PlanPaging())->where('file','MallGoodsStockWarnService')->column('mer_id');
        $lastId = $lastMer[0] ?? 0;
        //查询设置新版商城预警的商家
        $wheres = [['business','=','mall'],['mer_id','>',$lastId],['status','=',1]];
        $merCount = (new StockWarn())
            ->where($wheres)->count();
        if(!$merCount){//查不到，从0开始
            $wheres = [['business','=','mall'],['mer_id','>',0],['status','=',1]];
        }
        $merAry = (new StockWarn())
            ->where($wheres)
            ->field('mer_id,min_num,apply_after_sales_time,is_warn_only_once')->group('mer_id')
            ->order('mer_id')->limit(15)->select()->toArray();
        
        $addData = [];//公众号消息发送记录数组
        $addMerchantData = [];//商家信息发送数组
        
        foreach ($merAry as $vMerInfo){
            //查询需要预警的商品
            $goodsData = $this->getGoodsData($vMerInfo);
            
            //查询需要预警的售后订单
            $ordersData = $this->getOrdersData($vMerInfo);
            
            if($goodsData || $ordersData){
                //查询消息提醒人员
                $usersData = (new WarnUser())->getUsersData([
                    ['a.mer_id','=',$vMerInfo['mer_id']],
                    ['a.is_warn_mall','=',1],
                    ['a.is_del','=',0],
                    ['a.status','=',1],
                    ['b.openid','<>','']
                    ],'a.work_time,b.openid');
                foreach ($usersData as $kk=>$vv){
                    if(!$this->isWork($vv['work_time'])){
                        unset($usersData[$kk]);
                    }
                }
                //获取发给商家的信息
                $merchantNoticeData = $this->getAddMerchantData($goodsData,$ordersData,$vMerInfo);
                if($merchantNoticeData){
                    $addMerchantData = array_merge($addMerchantData,$merchantNoticeData);
                }

                //获取发给公众号的信息
                if($usersData){
                    $warnData = $this->getAddData($goodsData,$ordersData,$vMerInfo,$usersData);
                    if($warnData){
                        $addData = array_merge($addData,$warnData);
                    }
                }
            }
        }

        //查询店铺名称
        if($addData || $addMerchantData){
            $storeInfo = (new MerchantStore())->where([['store_id','IN',array_column(array_merge($addData,$addMerchantData),'store_id')]])->column('name','store_id');
        }
        
        
        Db::startTrans();
        try {
            //记录本次查询的最后一个商家id
            $merIdAry = array_column($merAry,'mer_id');
            if(!$lastMer){//新增记录
                (new PlanPaging)->insert([
                    'file'=>'MallGoodsStockWarnService',
                    'mer_id'=>max($merIdAry),
                    'last_time'=>time()
                ]);
            }else{
                (new PlanPaging)->where('file','MallGoodsStockWarnService')->update([
                    'mer_id'=>max($merIdAry),
                    'last_time'=>time()
                ]);
            }
            
            if($addMerchantData){
                //记录写入
                foreach($addMerchantData as &$vMerchantData){
                    $vMerchantData['title'] = $vMerchantData['type'] ? L_('【新版商城】用户申请退款') : (L_('【新版商城】库存不足'));
                    $vMerchantData['content'] = $vMerchantData['type'] ? L_('【X1】有买家申请售后，订单编号【'.$vMerchantData['order_no'].'】，请及时处理。',array('X1' => $storeInfo[$vMerchantData['store_id']])) : L_('您的【X1】里【X2】库存小于【X3】,请及时补充。',array('X1' => $storeInfo[$vMerchantData['store_id']], 'X2' => $vMerchantData['goods_name'], 'X3' => $vMerchantData['min_num']));
                    unset($vMerchantData['goods_name']);
                    unset($vMerchantData['min_num']);
                    if(isset($vMerchantData['order_no'])){
                        unset($vMerchantData['order_no']);
                    }
                }
                (new WarnNotice())->insertAll($addMerchantData);
            }
            
            if($addData){
                //消息发送
                foreach($addData as &$vData){
                    $title = $vData['type'] ? L_('【新版商城】用户申请退款') : (L_('【新版商城】库存不足'));
                    $msg = $vData['type'] ? L_('【X1】有买家申请售后，订单编号【'.$vData['order_no'].'】，请及时处理。',array('X1' => $storeInfo[$vData['store_id']])) : L_('您的【X1】里【X2】库存小于【X3】,请及时补充。',array('X1' => $storeInfo[$vData['store_id']], 'X2' => $vData['goods_name'], 'X3' => $vData['min_num']));
                    $msgDataWx = [
                        'href' => '',
                        'wecha_id' => $vData['openid'],
                        'first' => $msg,
                        'keyword1' => $title,
                        'keyword3' => date("Y-m-d H:i"),
                    ];
                    $result = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
                    fdump_api(['发送公众号消息'=>$result],'mall_goods_stock_warn',1);
                }
                //记录写入
                foreach($addData as &$vData){
                    $vData['title'] = $vData['type'] ? L_('【新版商城】用户申请退款') : (L_('【新版商城】库存不足'));
                    $vData['content'] = $vData['type'] ? L_('【X1】有买家申请售后，请及时处理。',array('X1' => $storeInfo[$vData['store_id']])) : L_('您的【X1】里【X2】库存小于【X3】,请及时补充。',array('X1' => $storeInfo[$vData['store_id']], 'X2' => $vData['goods_name'], 'X3' => $vData['min_num']));
                    unset($vData['goods_name']);
                    unset($vData['min_num']);
                    if(isset($vData['order_no'])){
                        unset($vData['order_no']);
                    }
                }
                (new WarnList())->insertAll($addData);
                
                
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();dd($e->getMessage());
            fdump_api([$e->getMessage()],'mall_goods_stock_warn_error',1);
        }
   
        return true;
    }
    
    /**
     * 查询需要预警的商品
     */
    public function getGoodsData($merInfo)
    {
        $where = [
            ['a.mer_id','=',$merInfo['mer_id']],
            ['a.status','=',1],
            ['a.audit_status','=',1],
            ['a.is_del','=',0],
            ['a.stock_num','>',-1],
            ['a.goods_type','=','spu'],
            ['a.stock_num','<=',$merInfo['min_num']],
        ];
        $field = 'a.mer_id,a.store_id,a.goods_id,a.name,a.stock_num,0 as sku_id';
        $goodsSpu = (new MallGoods())->getStockWarnGoodsInfo($where,$field);//查询单规格库存不足的商品
        $where = [
            ['a.mer_id','=',$merInfo['mer_id']],
            ['a.status','=',1],
            ['a.audit_status','=',1],
            ['a.is_del','=',0],
            ['b.stock_num','>',-1],
            ['a.goods_type','=','sku'],
            ['b.stock_num','<=',$merInfo['min_num']],
        ];
        $field = 'a.mer_id,a.store_id,a.goods_id,a.name,b.stock_num,b.sku_id';
        $goodsSku = (new MallGoods())->getStockWarnGoodsInfo($where,$field);//查询多规格库存不足的商品
        $goods = array_merge($goodsSpu,$goodsSku);
        return $goods;
    }
    
    /**
     * 查询需要预警的售后订单
     */
    public function getOrdersData($merInfo)
    {
        $order = (new MallOrderRefund())->getOrder([
            ['a.status','=',0],
            ['b.status','=',60],
            ['b.mer_id','=',$merInfo['mer_id']],
            ['a.create_time','<',time()-$merInfo['apply_after_sales_time']*60]
        ],'b.mer_id,b.store_id,a.order_id,a.create_time,b.order_no');
        return $order;
    }
    
    /**
     * 获取发给商家的信息
     */
    public function getAddMerchantData($goods,$order,$merInfo)
    {
        $addMerchantData = [];
        //商品分析
        foreach ($goods as $vG){
            $logMerchantWhere = [
                ['business','=','mall'],
                ['type','=',0],
                ['mer_id','=',$vG['mer_id']],
                ['store_id','=',$vG['store_id']],
                ['goods_id','=',$vG['goods_id']],
                ['goods_sku_id','=',''],
                ['is_warn_only_once','=',$merInfo['is_warn_only_once']]
            ];
            if(!$merInfo['is_warn_only_once']){//每日提醒
                $logMerchantWhere[] = ['create_time','>=',strtotime(date('Ymd'))];
                $logMerchantWhere[] = ['create_time','<=',time()];
            }else{//只提醒一次
                $logMerchantWhere[] = ['status','=',1];
            }
            $logMerchant = (new WarnNotice())->where($logMerchantWhere)->find();
            if(!$logMerchant){//未提醒过商家
                $keyName = $vG['mer_id'].'_'.$vG['store_id'].'_'.$vG['goods_id'].'_'.$vG['sku_id'];
                $addMerchantData[$keyName] = [
                    'business'=>'mall',
                    'type'=>0,
                    'mer_id'=>$vG['mer_id'],
                    'store_id'=>$vG['store_id'],
                    'goods_id'=>$vG['goods_id'],
                    'goods_sku_id'=>'',
                    'order_id'=>'',
                    'is_warn_only_once'=>$merInfo['is_warn_only_once'],
                    'goods_name'=>$vG['name'],
                    'min_num'=>$merInfo['min_num'],
                    'create_time'=>time()
                ];
            }
        }
        
        //售后订单分析
        foreach ($order as $vO){
            $logMerchantWhere = [
                ['business','=','mall'],
                ['type','=',1],
                ['mer_id','=',$vO['mer_id']],
                ['order_id','=',$vO['order_id']],
                ['is_warn_only_once','=',$merInfo['is_warn_only_once']]
            ];
            if(!$merInfo['is_warn_only_once']){//每日提醒
                $logMerchantWhere[] = ['create_time','>=',strtotime(date('Ymd'))];
                $logMerchantWhere[] = ['create_time','<=',time()];
            }
            $logMerchant = (new WarnNotice())->where($logMerchantWhere)->find();
            if(!$logMerchant){//未提醒过商家
                $keyName = $vO['order_id'];
                $addMerchantData[$keyName] = [
                    'business'=>'mall',
                    'type'=>1,
                    'mer_id'=>$vO['mer_id'],
                    'store_id'=>$vO['store_id'],
                    'goods_id'=>'',
                    'goods_sku_id'=>'',
                    'order_id'=>$vO['order_id'],
                    'is_warn_only_once'=>$merInfo['is_warn_only_once'],
                    'goods_name'=>'',
                    'min_num'=>$merInfo['min_num'],
                    'order_no'=>$vO['order_no'],
                    'create_time'=>time()
                ];
            }
        }
        return array_values($addMerchantData);
    }

    /**
     * 获取发给公众号的信息
     */
    public function getAddData($goods,$order,$merInfo,$usersData)
    {
        $addData = [];//记录公众号信息发送
        //商品分析
        foreach ($goods as $vG){
            foreach ($usersData as $vWUserOpenid){
                $logWarnUserWhere = [
                    ['openid','=',$vWUserOpenid['openid']],
                    ['business','=','mall'],
                    ['type','=',0],
                    ['mer_id','=',$vG['mer_id']],
                    ['store_id','=',$vG['store_id']],
                    ['goods_id','=',$vG['goods_id']],
                    ['goods_sku_id','=',0],
                    ['is_warn_only_once','=',$merInfo['is_warn_only_once']]
                ];
                if(!$merInfo['is_warn_only_once']) {//每日提醒
                    $logWarnUserWhere[] = ['create_time','>=',strtotime(date('Ymd'))];
                    $logWarnUserWhere[] = ['create_time','<=',time()];
                }else{//只提醒一次
                    $logWarnUserWhere[] = ['status','=',1];
                }
                $logWarnUser = (new WarnList())->where($logWarnUserWhere)->find();
                if(!$logWarnUser){//未提醒过商家管理员并且今天有人工作
                    $addData[] = [
                        'openid'=>$vWUserOpenid['openid'],
                        'business'=>'mall',
                        'type'=>0,
                        'mer_id'=>$vG['mer_id'],
                        'store_id'=>$vG['store_id'],
                        'goods_id'=>$vG['goods_id'],
                        'goods_sku_id'=>0,
                        'order_id'=>'',
                        'is_warn_only_once'=>$merInfo['is_warn_only_once'],
                        'goods_name'=>$vG['name'],
                        'min_num'=>$merInfo['min_num'],
                        'create_time'=>time()
                    ];
                }
            }
        }
        
        //售后订单分析
        foreach ($order as $vO){
            foreach ($usersData as $vWUserOrderOpenid){
                $logWarnUserWhere = [
                    ['openid','=',$vWUserOrderOpenid['openid']],
                    ['business','=','mall'],
                    ['type','=',1],
                    ['mer_id','=',$vO['mer_id']],
                    ['order_id','=',$vO['order_id']],
                    ['is_warn_only_once','=',$merInfo['is_warn_only_once']]
                ];
                if(!$merInfo['is_warn_only_once']){//每日提醒
                    $logWarnUserWhere[] = ['create_time','>=',strtotime(date('Ymd'))];
                    $logWarnUserWhere[] = ['create_time','<=',time()];
                }
                $logWarnUser = (new WarnList())->where($logWarnUserWhere)->find();
                if(!$logWarnUser){
                    $addData[] = [
                        'openid'=>$vWUserOrderOpenid['openid'],
                        'business'=>'mall',
                        'type'=>1,
                        'mer_id'=>$vO['mer_id'],
                        'store_id'=>$vO['store_id'],
                        'goods_id'=>'',
                        'goods_sku_id'=>'',
                        'order_id'=>$vO['order_id'],
                        'is_warn_only_once'=>$merInfo['is_warn_only_once'],
                        'goods_name'=>'',
                        'min_num'=>$merInfo['min_num'],
                        'order_no'=>$vO['order_no'],
                        'create_time'=>time()
                    ];
                }
            }
        }
        return $addData;
    }
    
    /**
     * 检测今天是否工作
     */
    public function isWork($info)
    {
        if(!$info){
            return false;
        }
        $ary = json_decode($info,true);
        foreach ($ary as $v){
            if($v['week'] == date('w') && $v['is_work']){
                return true;
            }
        }
        return false;
    }
}