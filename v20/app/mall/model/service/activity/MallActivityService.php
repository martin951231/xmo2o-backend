<?php


namespace app\mall\model\service\activity;


use app\common\model\service\weixin\TemplateNewsService;
use app\mall\model\db\MallActivity;
use app\mall\model\db\MallActivity as MallActivityModel;
use app\mall\model\db\MallActivityDetail;
use app\mall\model\db\MallFullGiveAct;
use app\mall\model\db\MallFullGiveGiftSku;
use app\mall\model\db\MallFullGiveLevel;
use app\mall\model\db\MallFullMinusDiscountAct;
use app\mall\model\db\MallFullMinusDiscountLevel;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsSku;
use app\mall\model\db\MallLimitedAct;
use app\mall\model\db\MallLimitedActNotice;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallNewBargainAct;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewBargainTeam;
use app\mall\model\db\MallNewBargainTeamUser;
use app\mall\model\db\MallNewGroupAct;
use app\mall\model\db\MallNewGroupOrder;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallNewGroupTeam;
use app\mall\model\db\MallNewGroupTeamUser;
use app\mall\model\db\MallNewPeriodicDeliver;
use app\mall\model\db\MallNewPeriodicPurchase;
use app\mall\model\db\MallNewPeriodicPurchaseOrder;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\MallPrepareAct;
use app\mall\model\db\MallPrepareActSku;
use app\mall\model\db\MallPrepareOrder;
use app\mall\model\db\MallReachedAct;
use app\mall\model\db\MallShippingAct;
use app\mall\model\service\MallGoodsService as MallGoodsService;
use app\mall\model\service\MallOrderDetailService;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\SendTemplateMsgService;
use Exception;

class MallActivityService
{

    public $MallActivityModel = null;

    public function __construct()
    {
        $this->MallActivityModel = new MallActivityModel();
    }

    /**
     * @param $goods_id
     * @param string $activity_id
     * @param string $uid
     * @param string $team_id
     * @param string $skuid
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 砍价：bargain；拼团：group；限时：limited；预售：prepare；周期购：periodic；
     */
  public function getActivity($goods_id,$activity_id='',$uid='',$team_id='',$skuid=0,$source=''){
      //在主活动表查询有效活动时间内的商品活动
      $result=(new MallActivity())->getActivity($goods_id,$activity_id,$uid,$team_id,$skuid,$source);
      $return=array();
      if(!empty($result)){
          switch ($result[0]['type'])
          {
              // style:'normal',//枚举值 normal=正常详情 砍价：bargain；拼团：group；限时：limited；预售：prepare；周期购：periodic
              case 'bargain':
                      $return['style']='bargain';
                      $return['surplus']=$result[0]['end_time']-$result[0]['start_time'];//剩余时间
                      $conditionb[]=['s.id','=',$result[0]['act_id']];
                      $conditionb[]=['m.goods_id','=',$goods_id];
                      $conditionb[]=['s.affect_time','>',0];
                      if($skuid>0){
                          $conditionb[]=['m.sku_id','=',$skuid];
                      }
                      $arr=(new MallNewBargainAct())->getDetail($conditionb,'',$uid,$result[0]['act_id']);
                      //已砍金额
                      if($team_id) {
                          $bargain_ready_money = (new MallNewBargainTeam())->where([['act_id', '=', $result[0]['act_id']], ['user_id', '=', $uid], ['status', 'in', [0, 1, 2]], ['id', '=', $team_id]])->value('bar_total_price');
                          $act_price = $arr['list']['price'] - $bargain_ready_money;
                      }else{
                          $act_price = $arr['list']['price'];
                      }
                   /*   if(!empty($arr) && $arr['list']['act_stock_num']!=0){*/
                          if(!empty($arr)){
                          $return['act_stock_num']=$arr['list']['act_stock_num'];//活动库存
                          $return['sku_id']=$arr['list']['sku_id'];//活动SKU
                          $return['limit']=$arr['list']['buy_limit'];//活动限参与次数
                          $return['act_id']=$arr['list']['act_id'];//砍价活动id
                          $return['id']=$result[0]['act_id'];//活动ID（用作跳转专题页，主活动）
                          $return['price']=$act_price>0?$act_price:0;//砍成后的价格（砍价后实际需要支付的）
                          $return['act_price']=$arr['list']['act_price'];//活动砍成后的价格
                          //$return['surplus']= ($arr['bargain_rate']['end_time']-time())>0 ? ($arr['bargain_rate']['end_time']-time()):0;//剩余时间（单位：秒）
                          $return['surplus']= $result[0]['end_time']-time();//剩余时间（单位：秒）
                          $return['success']= $arr['bargain_num'];//多少人已砍成
                          $return['palyer']= "发起砍价-邀请砍价-砍到低价下单购买";
                          $return['pre']= empty($arr['bargain_rate']['bar_total_price'])?0:$arr['bargain_rate']['bar_total_price'];//已砍
                          $return['help']=$arr['help_list'];//好友助力数据
                          $return['name'] = $result[0]['name'];
                          $return['buy_limit'] = $arr['list']['buy_limit'];//限购数量，0不限购
                          $return['next'] =number_format($arr['list']['price']-$arr['list']['act_price']-$arr['bargain_rate']['bar_total_price'],2);
                          $return['user_bargain_status'] =0;//用户砍价状态，0默认未砍价，可以找人砍价，1已参与未结束
                          $return['bargain_can_pay']=$arr['list']['bargain_can_pay'];//砍价过程是否可以支付
                         /* if(!empty($uid)){
                              (new MallNewBargainTeamUser())->getBargainUser();
                          }*/
                          //是否参与团队
                          $mans=(new MallNewBargainTeam())->isManInBarginTeam($arr['list']['act_id'],$uid);
                          if(empty($mans)){
                              $return['already']=0;//我要砍价
                          }else{
                              $return['already']=1;//找人砍价
                          }
                          //$return['sku_list']=(new MallNewBargainAct())->getSkuList($conditionb);
                          if(!empty($arr['bargain_rate']['team_id'])){
                              $return['team_id']=$arr['bargain_rate']['team_id'];
                              $return['url']=cfg('site_url')."/packapp/plat/pages/shopmall_third/bargainingDetails?teamId=".$arr['bargain_rate']['team_id']."&sku_id=".$return['sku_id']."&act_id=".$return['act_id'];//分享地址
                          }else{
                              $return['team_id']=0;
                              $return['url']="";
                          }
                          if($arr['list']['is_discount_share']==1){
                              $return['discount_card']=$arr['list']['discount_card'];
                              $return['discount_coupon']=$arr['list']['discount_coupon'];
                          }else{
                              $return['discount_card']=0;
                              $return['discount_coupon']=0;
                          }
                      }
                      else{
                          $return['style']='normal';
                      }
                  break;
              case 'group':
                  $return['style']='group';
                  //条件
                  $conditiong[]=['s.id','=',$result[0]['act_id']];//活动id
                  $conditiong[]=['s.goods_id','=',$goods_id];//商品id
                  if($skuid>0){
                      $conditiong[]=['m.sku_id','=',$skuid];//活动商品规格id
                  }else{
                      $skuid=0;
                  }
                  $condition1[]=['s.status','=',0];//0-未成团；1-成团成功；2-超过时间，团购作废
                  $condition1[]=['g.goods_id','=',$goods_id];
                  $condition1[]=['s.act_id','=',$result[0]['act_id']];
                  $condition1[]=['s.start_time','<',time()];
                  $condition1[]=['s.end_time','>=',time()];
                  $condition1[]=['s.num','>',0];
                  $condition2[]=['status','=',0];//0-未成团；1-成团成功；2-超过时间，团购作废
                  $arr=(new MallNewGroupAct())->getDetail($conditiong,'',$condition1,$result[0]['act_id'],$skuid);
                  if(empty($arr) || $arr['list']['act_stock_num']==0){//如果获取不到数据或者库存不足
                      $return['style']='normal';
                  }
                  else{
                      $where1=[['act_id','=',$result[0]['act_id']]];
                      $min_act_stock_num=(new MallNewGroupSku())->getMin($where1,$field='act_stock_num');
                      if($min_act_stock_num==-1){
                          $return['act_stock_num']=-1;
                      }else{
                          $sum=(new MallNewGroupSku())->getSum($where1,$field='act_stock_num');
                          $return['act_stock_num']=$sum<0?-1:$sum;
                      }
                      $return['sku_list']=(new MallNewGroupAct())->getSkuList($conditiong);
                      $return['limit']=$arr['list']['limit_num'];//活动限参与次数
                      $return['act_id']=$result[0]['act_id'];
                      $return['buy_limit']=$arr['list']['limit_num'];//活动限购数
                      $return['id']=$result[0]['act_id'];//活动ID（用作跳转专题页）
                      $return['price']=$arr['list']['act_price'];//拼团价
                      $return['teamer_price']=get_format_number($arr['list']['team_price']);//团长价
                      if($arr['list']['team_price']==0){
                          $return['teamer_price']= $return['price'];
                      }

                      //$return['team_discount_price']=$return['price']-$return['teamer_price'];
                      $return['team_discount_price']=get_format_number($arr['list']['team_discount_price'])<0?0:get_format_number($arr['list']['team_discount_price']);//团长优惠价
                      $return['surplus']=$result[0]['end_time']-time();//剩余时间（单位：秒）
                      $return['palyer']="开团/参团-邀请好友-满员发货";//玩法
                      $return['name'] = $result[0]['name'];
                      if($arr['list']['is_discount_share']==1){
                          $return['discount_card']=$arr['list']['discount_card'];
                          $return['discount_coupon']=$arr['list']['discount_coupon'];
                      }else{
                          $return['discount_card']=0;
                          $return['discount_coupon']=0;
                      }
                      //成团数量
                      $return['nums']=$arr['list']['complete_num'];//成团人数
                      $condition2[]=['act_id','=',$result[0]['act_id']];
                      $condition2[]=['start_time','<',time()];
                      $condition2[]=['end_time','>=',time()];
                      $team_num=(new MallNewGroupTeam())->getGroupNumSucess($condition2);
                      //$return['ingroup']['title']=$team_num."人正在拼团，团长价再减".$return['team_discount_price'];
                      $return['ingroup']['title']=$team_num."人正在拼团";
                      $return['ingroup']['team_discount_price']=get_format_number($return['team_discount_price'])<0?0:get_format_number($return['team_discount_price']);//参团列表上标题显示的团长价
                      $group_list=$arr['group_list'];
                      if(!empty($group_list)){//正在拼团团队列表
                          foreach ($group_list as $key=>$val){
                              $group_list[$key]['surplus']=$val['end_time']-time();
                              if($group_list[$key]['surplus']<0){
                                  $group_list[$key]['surplus']=0;
                              }
                              $group_list[$key]['short']= $return['nums']-$val['num'];
                          }
                      }
                      $return['ingroup']['data']=$group_list;
                  }
                  break;
              case 'limited':
                  $return['style']='limited';
                  $arr=$this->getLimitedActivity($goods_id,$result[0]['id'],'','','',$skuid,$source);
                 /* if(!empty($arr) &&  $arr['act_stock_num']!=0){//活动存在且商品库存大于0*/
                  if(!empty($arr)){
                      if ($arr['limited_status'] == 1 || $source == 'limited') {
                          $return['limited_status'] = $arr['limited_status'];
                          $return['id'] = $result[0]['act_id'];
                          $return['act_id'] = $arr['act_id'];
                          $return['buy_limit'] = $return['limit'] = $arr['buy_limit'];//限购数量，0不限购
                          $return['price'] = get_format_number($arr['limited_price']);
                          $return['surplus'] = $arr['left_time'];
                          $return['name'] = $result[0]['name'];
                          if ($arr['is_discount_share'] == 1) {
                              $return['discount_card'] = $arr['discount_card'];
                              $return['discount_coupon'] = $arr['discount_coupon'];
                          } else {
                              $return['discount_card'] = 0;
                              $return['discount_coupon'] = 0;
                          }
                          $conditiong[] = ['s.id', '=', $result[0]['act_id']];//活动id
                          $conditiong[] = ['m.goods_id', '=', $goods_id];//商品id
                          if ($skuid > 0) {
                              $conditiong[] = ['m.sku_id', '=', $skuid];//活动商品规格id
                          }
                          $return['start_time'] = $arr['start_time'];
                          $return['min_stock_num'] =(new MallLimitedSku())->getRestActMinStockByGoodsId($result[0]['act_id'],$goods_id);
                          $return['act_stock_num'] = (new MallLimitedSku())->getRestActStockBySkuId($result[0]['act_id'],$goods_id,$skuid);
                          $return['sku_list']=(new MallLimitedAct())->getSkuList($conditiong);//规格
                      }else{
                          $return['style']='normal';
                      }
                  }else{
                      //获取活动限购失败或者活动没开始
                      $return['style']='normal';
                  }
                  break;
              case 'prepare':
                  $return['id']=$result[0]['act_id'];
                  $return['style']='prepare';
                  $return['name'] = $result[0]['name'];
                  $condition3[]=['m.goods_id','=',$goods_id];
                  $condition3[]=['s.id','=',$result[0]['act_id']];
                  if(!empty($skuid)){
                      $condition3[]=['m.sku_id','=',$skuid];//商品规格id
                  }
                  $return['act_id']=$result[0]['act_id'];
                  $arr=(new MallPrepareAct())->getDetail($condition3);
                  if(empty($arr)){
                      $return['style']='normal';
                  }else{
                      $where1=[['act_id','=',$result[0]['act_id']]];
                  $return['act_stock_num'] = (new MallPrepareActSku())->getSum($where1,$field='act_stock_num');
                  $return['buy_limit'] =$return['limit']=empty($arr['limit_num'])?0:$arr['limit_num'];
                  $return['deposit']=empty($arr['bargain_price'])?0:get_format_number($arr['bargain_price']);//定金
                 // $return['surplus']=$arr['end_time']-$arr['start_time'];//距离结束还是时间
                  $return['surplus']=$result[0]['end_time']-time();
                  //优惠同享
                  if($arr['is_discount_share']==1){
                      $return['discount_card']=$arr['discount_card'];
                      $return['discount_coupon']=$arr['discount_coupon'];
                  }else{
                      $return['discount_card']=0;
                      $return['discount_coupon']=0;
                  }
                  if($arr['rest_type']==0){
                      $datesa=date('Y-m-d',$arr['rest_start_time']);
                      $datesb=explode('-',$datesa);

                      $datesc=date('Y-m-d',$arr['rest_end_time']);
                      $datesd=explode('-',$datesc);
                      $return['date']=$datesb[0].'.'.$datesb[1].'.'.$datesb[2]."-".$datesd[0].'.'.$datesd[1].'.'.$datesd[2];//支付尾款时间段
                      if(empty($arr['rest_start_time']) || empty($arr['rest_end_time'])){
                          $return['date']="";
                      }
                  }else{
                      if($arr['rest_start_time']/3600==0 && $arr['rest_end_time']/60==0){
                          $return['date']="定金支付后支付尾款";
                      }elseif ($arr['rest_start_time']/3600!=0 && $arr['rest_end_time']/60==0){
                          $return['date']="定金支付后".($arr['rest_start_time']/3600)."时内支付尾款";
                      }elseif ($arr['rest_start_time']/3600==0 && $arr['rest_end_time']/60!=0){
                          $return['date']="定金支付后".($arr['rest_end_time']/60)."分内支付尾款";
                      }else{
                          $return['date']="定金支付后".($arr['rest_start_time']/3600)."时".($arr['rest_end_time']/60)."分内支付尾款";
                      }
                  }

                  $conditiong[]=['s.id','=',$result[0]['act_id']];//活动id
                  $conditiong[]=['m.goods_id','=',$goods_id];//商品id
                  if($skuid>0){
                      $conditiong[]=['m.sku_id','=',$skuid];//活动商品规格id
                  }
                  $return['sku_list']=(new MallPrepareAct())->getSkuList($conditiong);//规格

                  $return['price']=get_format_number($arr['bargain_price']+$arr['rest_price']);//预售价
                  $return['discount_price']=empty($arr['discount_price'])? 0 : get_format_number($arr['discount_price']);//优惠价格
                  $return['balance']=empty($arr['rest_price'])? 0 : get_format_number($arr['rest_price']);//尾款
                      //已支付定金用户再次进入商品详情展示尾款金额
                  $where[]=['m.goods_id','=',$goods_id];//商品id
                  $where[]=['s.act_id','=',$result[0]['act_id']];//活动ID
                  $where[]=['s.uid','=',$uid];//用户id
                  $where[]=['s.pay_status','=',1];
                  //$where[]=['s.second_pay','=',0];//第二次支付状态
                  $msg=(new MallPrepareOrder())->getInfo($where,$field="*");
                  if($msg){
                      $return['balance']=get_format_number($msg['rest_price']);
                  }
                  if($arr['send_goods_type']==1){//发货说明
                      $dates1=date('Y-m-d',$arr['send_goods_date']);
                      $dates2=explode('-',$dates1);
                      $return['send']="付尾款后".$dates2[0].'.'.$dates2[1].'.'.$dates2[2]."发货";
                  }else{
                      $return['send']="付尾款后".$arr['send_goods_days']."天内发货";
                  }
                  $return['step']="1、付定金----2、交尾款----3、发货";//流程说明
                  }
                  break;
              case 'periodic':
                  $return['id']=$result[0]['act_id'];
                  $return['style']='periodic';
                  $return['name'] = $result[0]['name'];
                  $return['buy_limit'] =0;
                  $where2[]=['s.id','=',$result[0]['act_id']];
                  $arr=(new MallNewPeriodicPurchase())->getInfo($where2);
                  //优惠同享
                  if($arr['is_discount_share']==1){
                      $return['discount_card']=$arr['discount_card'];
                      $return['discount_coupon']=$arr['discount_coupon'];
                  }else{
                      $return['discount_card']=0;
                      $return['discount_coupon']=0;
                  }

                  if($arr['periodic_type']==1){//周期购类型
                      $msg="每日一期,共".$arr['periodic_count'].'期';
                  }elseif ($arr['periodic_type']==2){
                      $msg="每周一期,共".$arr['periodic_count'].'期';
                  }else{
                      $msg="每月一期,共".$arr['periodic_count'].'期';
                  }

                  $conditiong[]=['s.id','=',$result[0]['act_id']];//活动id
                  $conditiong[]=['s.goods_id','=',$goods_id];//商品id
                  if($skuid>0){
                      $conditiong[]=['m.sku_id','=',$skuid];//活动商品规格id
                  }
                  $return['explain']=$msg;//配送说明
                  $return['limit_txt']=$arr['delay_limit'];//顺延限制
                  $return['nums']=$arr['periodic_count'];//多少期
                  $return['buy_limit'] =$return['limit']=$arr['buy_limit'];
                  $return['freight_type']=$arr['freight_type'];//是否包邮  0读商品的配置 是否包邮 1包邮 2不包邮',

                  if($arr['forward_day']*1==0){
                      $return['near_date_msg']="在最近配送 ".$arr['forward_hour'].'点前下单支付';
                  }else{
                      $return['near_date_msg']="在最近配送日期前".$arr['forward_day'].'天,'.$arr['forward_hour'].'点前下单支付';
                  }
                  /*$near_dates=(new MallNewPeriodicPurchase())->getOrderInfo($where2);
                  if(empty($near_dates)){
                      $return['near_date']="无配送数据";
                  }else{
                      $return['near_date']=$near_dates[0]['deliver_time'];//最近配送日期
                  }*/
                  break;
              default:
                  $return['style']='normal';
          }
      }else{
          $result=(new MallActivity())->getActivityPeriodic($goods_id,$activity_id,$uid,$team_id,$skuid);
          $goods=(new MallGoods())->getOne($goods_id);
          if(empty($result)){
              $return['price']=$goods['price'];
              $return['style']='normal';
          }else{
              $return['id']=$result[0]['act_id'];
              $return['style']='periodic';
              $return['name'] = $result[0]['name'];
              $where2[]=['s.id','=',$result[0]['act_id']];
              $arr=(new MallNewPeriodicPurchase())->getInfo($where2);
              //优惠同享
              if($arr['is_discount_share']==1){
                  $return['discount_card']=$arr['discount_card'];
                  $return['discount_coupon']=$arr['discount_coupon'];
              }else{
                  $return['discount_card']=0;
                  $return['discount_coupon']=0;
              }

              if($arr['periodic_type']==1){//周期购类型
                  $msg="每日一期,共".$arr['periodic_count'].'期';
              }elseif ($arr['periodic_type']==2){
                  $msg="每周一期,共".$arr['periodic_count'].'期';
              }else{
                  $msg="每月一期,共".$arr['periodic_count'].'期';
              }

              $return['price']=$goods['price'];
              $return['explain']=$msg;//配送说明
              $return['limit_txt']=$arr['delay_limit'];//顺延限制
              $return['nums']=$arr['periodic_count'];//多少期
              $return['buy_limit'] =$return['limit']=$arr['buy_limit'];
              $return['freight_type']=$arr['freight_type'];//是否包邮  0读商品的配置 是否包邮 1包邮 2不包邮',
              //$return['near_date_msg']="在最近配送日期前".$arr['forward_day'].'天,'.$arr['forward_hour'].'点前下单支付';
              if($arr['forward_day']*1==0){
                  $return['near_date_msg']="在最近配送 ".$arr['forward_hour'].'点前下单支付';
              }else{
                  $return['near_date_msg']="在最近配送日期前".$arr['forward_day'].'天,'.$arr['forward_hour'].'点前下单支付';
              }
          }
      }
      return $return;
  }

    /**
     * @param $actid 活动id
     * @param $uid 用户id
     * @param $order_id  订单id
     * @param $mer_id 商家id
     * @param $store_id 门店id
     * @param int $tid 团队id
     * @param int $type 操作类型 0saveorder操作 1支付之后操作
     * @return int|string
     * @author mrdeng
     * 拼团活动订单表更新
     */
  public function saveOrderUpdateGroupTeam($act_id,$uid,$order_id,$tid=0){
      if($tid>0){
          $data['tid']=$tid;
          $data1['tid']=$tid;
      }
      $data['order_id']=$order_id;
      //saveorder在拼团活动订单表加入一条数据
      $data['act_id']=$act_id;
      $data['uid']=$uid;
      $data['create_time']=time();
      if($tid==0){
          $data2['user_id']=$uid;
          $data2['act_id']=$act_id;
          $data2['status']=0;
          $data2['num']=0;
          $arrs=(new MallNewGroupTeam())->insertGroupGetStoreMsg($data2);
          if(!$arrs){
              return false;//添加失败返回false
          }else{
              $data1['tid']=$data['tid']=$arrs;
          }
      }

      $data1['order_id']=$order_id;
      $data1['user_id']=$uid;
      $data1['type']=0;
      $t_user_id=(new MallNewGroupTeamUser())->addTeamUser($data1);//加入团队成员表
      $order_id=(new MallNewGroupOrder())->addOne($data);
      return $order_id;
  }

    /**
     * @param $tid
     * @throws \think\Exception
     * 获取拼团的可参与状态
     */
    public function getGroupTeamOrderStatus($tid){
        if(empty($tid)){
            throw new \think\Exception(L_('团队号缺失'), 1003);
        }
        $tids=(new MallNewGroupTeam())->getOne($tid);
        $where=[['m.tid','=',$tid],['s.status','<=',2]];
        $count=(new MallNewGroupOrder())->getCount($where);
        if($tids['complete_num']<=$count){
         $return['part_status']=0;//不可参与拼团
        }else{
            $return['part_status']=1;//可参与拼团
        }
    }
    /**
     * @param $act_id
     * @param $uid
     * @param $order_id
     * @param int $tid
     * 拼团(取消)失败
     */
    public function saveOrderUserUpdate($order_id)
    {
        if(empty($order_id)){
            throw new \think\Exception(L_('订单号缺失'), 1003);
        }
        $condition[]=['order_id','=',$order_id];
        $data['status']=2;
        //查询活动所有参与的用户id
        $tid = (new MallNewGroupOrder())->where($condition)->value('tid');
        $allUser = (new MallNewGroupOrder())->getUserInfo(['a.tid'=>$tid,'a.status'=>1],'b.uid,b.openid');
        
        $arr=(new MallNewGroupOrder())->updateOne($condition,$data);
        if($arr===false){
            return false;
        }else{
            $where=[['order_id','=',$order_id]];
            $msg=(new MallNewGroupOrder())->getOneOrder($where,"*");
            $where1=[['user_id','=',$msg['uid']],['id','=',$msg['tid']]];
            $ret=(new MallNewGroupTeam())->getTeamList($where1,"*");
            if(!empty($ret)){
                //取消的是团长，则团队取消
                $data['status']=2;
                (new MallNewGroupTeam())->updateGroupStatus($where1,$data);
                
                //查询商品名称
                $goodsInfo = (new MallNewGroupOrder())->getGoodsInfo(['a.order_id'=>$order_id],'c.name');
                $goodsName = $goodsInfo['name'] ?? '';
                if($goodsName){
                    //公众号通知团队的每一个参与者
                    foreach ($allUser as $vUser){
                        if(!$vUser['openid']){
                            continue;
                        }
                        $msgDataWx = [
                            'href' => '',
                            'wecha_id' => $vUser['openid'],
                            'first' => '您参与的【'.$goodsName.'】拼团失败，钱已退回',
                            'keyword1' => L_('商城拼团提醒'),
                            'keyword2' => L_('拼团失败'),
                            'keyword3' => date("Y-m-d H:i"),
                            'remark' => '',

                        ];
                        (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
                    }
                }
            }
            return true;
        }
    }

    /**
     * @param $act_id 活动id
     * @param $sku_id 规格id
     * @return bool
     * @throws \think\Exception
     * 限时优惠saveOrder更新库存
     */
    public function saveOrderLimitAct($act_id,$sku_id,$num){
        if(empty($act_id) || empty($sku_id)){
            throw new \think\Exception(L_('参数缺失'), 1003);
        }
        $condition[]=['act_id','=',$act_id];
        $condition[]=['sku_id','=',$sku_id];
        $arr=(new MallLimitedSku())->getBySkuId($condition);
        if(empty($arr)){//没有查询到数据
            return false;
        }else{
            if($arr['act_stock_num']>0){
                $arr['act_stock_num']=$arr['act_stock_num']-$num;
                if($arr['act_stock_num']<0){
                    return false;
                }
                $res=(new MallLimitedSku())->updateOne($condition,$arr);
                if($res!==false){
                    return true;
                }else{
                    return false;
                }
            }elseif ($arr['act_stock_num']==-1){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * @param $act_id
     * @param $sku_id
     * @return bool
     * @throws \think\Exception
     * 取消订单更新限时优惠库存
     */
    public function refundOrderLimitAct($act_id,$sku_id,$num){
        if(empty($act_id) || empty($sku_id)){
            throw new \think\Exception(L_('参数缺失'), 1003);
        }
        $condition[]=['act_id','=',$act_id];
        $condition[]=['sku_id','=',$sku_id];
        $arr=(new MallLimitedSku())->getBySkuId($condition);
        if(empty($arr)){//没有查询到数据
            return false;
        }else{
            if($arr['act_stock_num']!=-1 && $arr['act_stock_num']>=0){
                $arr['act_stock_num']=$arr['act_stock_num']+$num;
                $res=(new MallLimitedSku())->updateOne($condition,$arr);
                if($res!==false){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }
    }

    /**
     * @param $uid
     * @param $order_id
     * @param int $tid
     * @return bool|int|string
     * @author mrdeng
     * 支付之后插入一条数据到团队成员表以及判断并更新成团状态
     */
  public function afterPayUpdateGroupTeamUser($order_id){
      if(empty($order_id)){
          throw new \think\Exception(L_('订单号缺失'), 1003);
      }
      $condition[]=['order_id','=',$order_id];
      $data1['status']=1;//拼团订单支付状态
      $data['type']=0;
      $arr=(new MallNewGroupOrder())->getOne($order_id);
      if(empty($arr)){
         return false;
      }
      $condition[]=['uid','=',$arr['uid']];
      $data['user_id']=$arr['uid'];
      $data['tid']=$arr['tid'];
      $data['order_id']=$order_id;
      $tids=(new MallNewGroupTeam())->getOne($arr['tid']);
      if(empty($tids)){//查询不到团队数据
        return false;
      }
      $nums=$tids['complete_num']-$tids['num'];//团队成团数量缺口
      $condition1[]=['id','=',$arr['tid']];
      if($nums>0 && $tids['status']==0){
          if($nums==1){
              $dat['num']=$tids['complete_num'];
              $dat['status']=1;
              //(new MallOrderService())->changeOrderStatus($order_id,10,'拼团成功待发货');//待发货
              $where_od=[['s.tid','=',$arr['tid']],['s.order_id','=',$order_id]];
              $where_od_or=[['s.tid','=',$arr['tid']],['o.pay_time','>',0]];
              $list=(new MallNewGroupOrder())->getPayList([$where_od,$where_od_or],$field="*");
              //拼团成团，团队成员订单转态都改为待发货
              foreach ($list as $k=>$v){
                  (new MallOrderService())->changeOrderStatus($v['order_id'],10,'拼团成功待发货');//待发货
                  //拼团成功通知
                  (new SendTemplateMsgService())->sendWxappMessage(['type'=>'group_success','complete_num'=>$tids['complete_num'],'order_id'=>$v['order_id']]);
              }
          }else{
              $dat['num']=$tids['num']+1;
              $dat['status']=0;
              (new MallOrderService())->changeOrderStatus($order_id,13,'支付成功，待成团');//待成团
          }
          (new MallNewGroupTeam())->updateGroupStatus($condition1,$dat);//更新团队状态
      }else{
          return false;
      }
      $up_status=(new MallNewGroupOrder())->updateOne($condition,$data1);//更新拼团活动订单表
      if($up_status !== false){
          //$t_user_id=(new MallNewGroupTeamUser())->addTeamUser($data);//加入团队队伍
          //(new MallNewGroupTeamUser())->updatePrepare();
          $order_sku_msg=(new MallOrderDetail())->getOne($order_id);
          if(!empty($order_sku_msg)){
              $where=[['act_id','=',$arr['act_id']],['sku_id','=',$order_sku_msg['sku_id']],['goods_id','=',$order_sku_msg['goods_id']]];
              $act_sku_msg=(new MallNewGroupSku())->getBySkuId($where);
              if($act_sku_msg['act_stock_num']!=-1 && $act_sku_msg['act_stock_num']!=0){
                  $act_stock_num['act_stock_num']=$act_sku_msg['act_stock_num']-$order_sku_msg['num'];
                  $up_status1=(new MallNewGroupSku())->updateOne($where,$act_stock_num);
                  if($up_status1!==false){
                      return true;
                  }else{
                      return false;//活动库存更新失败
                  }
              }else{
                  return true;
              }
          }else{
              return false;
          }
      }
  }


  //拼团超时更新团队状态

    /**
     * @param $order_id
     * @return MallNewGroupTeam|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 拼团订单超时自动取消订单---更新团队状态
     */
    public function updateTeamOverTime($order_id){
        if(empty($order_id)){
            throw new \think\Exception(L_('订单号缺失'), 1003);
        }
        $arr=(new MallNewGroupOrder())->getOne($order_id);
        if(empty($arr)){
            return false;
        }
        $condition1[]=['id','=',$arr['tid']];
        $dat['status']=2;
        return (new MallNewGroupTeam())->updateGroupStatus($condition1,$dat);//更新团队状态
    }

    /**
     * @param $order_id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 拼团成员未提交订单，订单超时取消此订单
     */
    public function updateTeamUserOverTime($order_id)
    {
        if(empty($order_id)){
            throw new \think\Exception(L_('订单号缺失'), 1003);
        }
        $arr=(new MallNewGroupOrder())->getOne($order_id);
        if(empty($arr)){
            throw new \think\Exception(L_('订单信息不存在'), 1003);
        }

        $condition1[]=['id','=',$arr['tid']];
        $dat['status']=2;
        $u_status=(new MallNewGroupOrder())->updateOne($condition1,$dat);
        if($u_status!==false){
            return true;
        }else{
            throw new \think\Exception(L_('更新失败'), 1003);
        }
    }
    /**
     * 拼团团队状态
     * @param $tid 团队id
     * @throws \think\Exception
     * return status 10未满团 20 满团 30未超时 40 超时
     */
    public function getGroupTeamStatus($tid){
        if(empty($tid)){
            throw new \think\Exception(L_('团队id缺失'), 1003);
        }

        $res=(new MallNewGroupTeam())->getOne($tid);

        if(empty($res)){
            throw new \think\Exception(L_('团队不存在'), 1003);
        }else{
            //团队已满
          if($res['complete_num']==$res['num']){
                return 20;//满团
          }elseif ($res['end_time']<time()){
                return 40;//超时
          }elseif ($res['complete_num']>$res['num'] ){
                return 10;//未满团
          }else{
                 return 30;//未超时
          }
        }
    }
    /**
     * @param $tid
     * @param $order_id
     * @return mixed
     * 砍价完成之后下单更新砍价团队状态
     */
    public function saveOrderUpdateBargainTeamStatus($tid,$order_id){
        if(empty($tid) || empty($order_id)){
            throw new \think\Exception(L_('参数缺失'), 1003);
        }
        $where[]=['id','=',$tid];
        $data['status']=2;
        $data['order_id']=$order_id;
        $msg=(new MallOrderDetail())->getOne($order_id);
        if(!empty($msg)){
            $msg=$msg->toArray();
            $where1=[['act_id','=',$msg['activity_id']],['sku_id','=',$msg['sku_id']]];
            $bar_goods=(new MallNewBargainSku())->getBySkuId($where1,"*");
            if($bar_goods['act_stock_num']!=-1 && $bar_goods['act_stock_num']>0){//下单减库存
                $stock_num['act_stock_num']=$bar_goods['act_stock_num']-1;
                (new MallNewBargainSku())->saveData($where1,$stock_num);
            }
        }else{
            fdump("砍价成功下单，没有订单详情信息，订单号：".$order_id,"MallNewBargainTeamSaveOrder",1);
        }
        return (new MallNewBargainTeamService())->updateTeam($where,$data);
    }

    /**
     * @param $order_id 订单id
     * @return mixed
     * 下单未支付15分钟取消订单更新砍价团队订单的状态
     */
    public function fifteenMiniuteRefundBargainOrder($order_id){
        if(empty($order_id)){
            throw new \think\Exception(L_('参数缺失'), 1003);
        }
        $where[]=['order_id','=',$order_id];
        $team = (new MallNewBargainTeam())->where($where)->findOrEmpty()->toArray();
        if(!$team){
            fdump_api(['order_id'=>$order_id,'msg'=>"砍价团队不存在！"],'MallNewBargainTeamSaveOrder');
            return false;
        }
        $help_num = (new MallNewBargainAct())->where(['id'=>$team['act_id']])->value('help_bargain_people_num');
        if($help_num>$team['bar_num']){
            $data['status']=0;
        }else{
            $data['status']=1;
        }

        $msg=(new MallOrderDetail())->getOne($order_id);
        if(!empty($msg)){
            $msg=$msg->toArray();
            $where1=[['act_id','=',$msg['activity_id']],['sku_id','=',$msg['sku_id']]];
            $bar_goods=(new MallNewBargainSku())->getBySkuId($where1,"*");
            $stock_num['act_stock_num']=$bar_goods['act_stock_num']+1;
           (new MallNewBargainSku())->saveData($where1,$stock_num);
        }else{
            fdump_sql(['order_id'=>$order_id,'msg'=>"砍价成功下单未支付15分钟未下单，没有订单详情信息"],'MallNewBargainTeamSaveOrder');
            //fdump("砍价成功下单未支付15分钟未下单，没有订单详情信息，订单号：".$order_id,"MallNewBargainTeamSaveOrder",1);
        }

        return (new MallNewBargainTeamService())->updateTeam($where,$data);
    }
    /**
     * @param $tid
     * @param $order_id
     * @return mixed
     * 砍价完成之后支付完成更新砍价团队状态
     */
   public function afterPayUpdateBargainTeamStatus($order_id){
       $where[]=['order_id','=',$order_id];
       $data['status']=3;
       //$data['order_id']=$order_id;
      return (new MallNewBargainTeamService())->updateTeam($where,$data);
   }
    /**
     * @param $goods_id
     * @param $activity_id
     * @param $goods_name
     * @param $goods_image
     * @author mrdeng
     * 查找商品关联的限时活动列表
     * limited_status 0 未开始 1 进行中 2 结束
     */
  public function getLimitedActivity($goods_id,$activity_id,$goods_name,$goods_image,$price,$sku=0,$source='',$uid=0){
      $result=(new MallActivity())->getLimitedActivity($goods_id,$activity_id,$goods_name,$goods_image,$price,$sku,$source);
      fdump_sql($result,"limit_data_getLimitedActivity");//秒杀偶现bug日志
      if(!empty($result)){
          $left=array();
          $ret=array();
          if($result['time_type']==1){//'秒杀类型 1.按固定时间   2.按周期',
              //按固定时间计算秒数
              if(time()>$result['start_time'] && time()<=$result['end_time']){
                  $left['left_time']=$result['end_time']-time();
                  $left['limited_status']=1;
              }elseif(time()<$result['start_time']){
                  $left['left_time']=$result['end_time']-time();
                  $left['limited_status']=0;
              }elseif(time()>$result['end_time']){
                  //结束
                  //$left['limited_status']=2;
                  return $ret;
              }
          }
          else{
              //按周期
              //周期类型
              if($result['cycle_type']==1){//每日
                  $sec=(new MallActivityDetail())->sec();//计算当前时间秒数
                  if($sec<$result['cycle_start_time']){
                      $left['left_time']=$result['cycle_end_time']-$sec;
                      $left['limited_status']=0;
                  }else if($sec>$result['cycle_start_time'] && $sec<$result['cycle_end_time']){
                      $left['left_time']=$result['cycle_end_time']-$sec;
                      $left['limited_status']=1;
                  }else{
                      //$left['left_time']=0;//结束了
                      //$left['limited_status']=2;
                      return $ret;
                  }
              }elseif ($result['cycle_type']==2){//每周
                  $weekEnglish=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                  if(empty($result['cycle_date'])){
                      //数据不正常
                      $left['left_time']=0;//结束了
                      //$left['limited_status']=2;//结束了
                      return $ret;
                  }else{
                      $time=explode(',', $result['cycle_date']);
                      $week = date('w');
                      if(in_array($week,$time)){//本日期在活动中
                          $sec=(new MallActivityDetail())->sec();
                          if($sec<$result['cycle_start_time']){
                              $left['left_time']=$result['cycle_end_time']-$sec;
                              $left['limited_status']=0;
                          }else if($sec>$result['cycle_start_time'] && $sec<$result['cycle_end_time']){
                              $left['left_time']=$result['cycle_end_time']-$sec;
                              $left['limited_status']=1;
                          }else{
                              //$left['left_time']=0;//结束了
                              //$left['limited_status']=2;
                              return $ret;
                          }
                      }else{
                          $wk=(new MallActivityDetail())->timeDiff($week,$time);
                          //获取周几的时间戳
                          $time=strtotime($weekEnglish[$wk])*1+$result['cycle_end_time']*1;
                          $left['limited_status']=0;
                          $left['left_time']=$time*1-time();
                      }
                  }
              }else{//每月
                  //获取当前是几号
                  $date=date('Y-m-d');
                  $time=explode('-', $date);
                  if(empty($result['cycle_date'])){
                      //数据不正常
                      return $ret;
                  }else{
                      //判断是否是正常数据
                      $days=explode(",",$result['cycle_date']);
                      $dates_num=$time[2]*1;//取当前是几号
                      if(in_array($dates_num,$days)){//判断是否在这个时间内
                          //符合这个日期，当前执行
                          $sec=(new MallActivityDetail())->sec();
                          if($sec<$result['cycle_start_time']){
                              $left['left_time']=$result['cycle_end_time']-$sec;
                              $left['limited_status']=0;
                          }else if($sec>$result['cycle_start_time'] && $sec<$result['cycle_end_time']){
                              $left['left_time']=$result['cycle_end_time']-$sec;
                              $left['limited_status']=1;
                          }else{
                              //$left['left_time']=0;//结束了
                              //$left['limited_status']=2;
                              return $ret;
                          }
                      }else{
                          if($dates_num<max($days)){ //当前号小于后台定义的最大值
                              //在这个时间段内,判断当年2月份天数
                              if((new MallActivityDetail())->leapYear($time[0])){
                                  $maxday=29;
                              }else{
                                  $maxday=28;
                              }
                              //比较
                              if($time[1]*1==2 && max($days)>$maxday){
                                  //2月份，跳到下一月
                                  $left['left_time']=(new MallActivityDetail())->strNextMonth($time[0]*1,$time[1]*1,$days,$result['cycle_start_time']);
                                  if(!$left['left_time']){
                                      //数据错误
                                      return $ret;
                                  }else{
                                      $left['limited_status']=0;
                                  }
                              }else{
                                  //正常范围内正常执行
                                  $days= (new MallActivityDetail())->timeDiff($time[2]*1,$days);
                                  $left['left_time']=(new MallActivityDetail())->strThisMonth($time[0]*1,$time[1]*1,$days,$result['cycle_start_time']);
                                  if(!$left['left_time']){
                                      //数据错误
                                      return $ret;
                                  }else{
                                      $left['limited_status']=0;
                                  }
                              }
                          }
                          else{//当前号大于后台定义的最大值
                              //不在范围内取下个月的最小日期
                              $num=min($days);//活动开始最近日期
                              $mon=$time[1];//月份
                              $year=$time[0];
                              $left['left_time']=(new MallActivityDetail())->strNextMonth($year,$mon,$num,$result['cycle_start_time']);
                              $left['limited_status']=0;
                          }
                      }
                  }

              }
          }
          /*根据每个goods_id查找最低价*/
          $return1=array();
          $return1['goods_id']=$goods_id;
          $return1['buy_limit']=$result['buy_limit'];
          $return1['id']=$activity_id;
          $return1['goods_name']=$goods_name;
          $return1['goods_image']=$goods_image ? replace_file_domain($goods_image) : '';
          $return1['left_time']=$left['left_time'];////距离结束时间秒
          $return1['lowest_price']=$price;
          $return1['limited_status']=$left['limited_status'];
          $return1['act_stock_num']=$result['act_stock_num'];
          $return1['is_discount_share']=$result['is_discount_share'];
          $return1['start_time']=$result['start_time'];
          $return1['act_id']=$result['id'];
          if($uid){
              $where_notice_status=[
                  ['goods_id','=',$goods_id],
                  ['act_id','=',$return1['act_id']],
                  ['uid','=',$uid],
              ];
              $return1['notice_status']=(new MallLimitedActNotice())->getNoticeStatus($where_notice_status,"id")?1: 0;
          }else{
              $return1['notice_status']=0;
          }
          if($result['is_discount_share']==1){
              $return1['discount_card']=$result['discount_card'];
              $return1['discount_coupon']=$result['discount_coupon'];
          }else{
              $return1['discount_card']=0;
              $return1['discount_coupon']=0;
          }
          if($sku){
              $where[] = ['act_id', '=', $result['id']];
              $where[] = ['goods_id', '=', $goods_id];
              $where[] = ['sku_id', '=', $sku];
              $limited_price1=(new MallLimitedSku())->getBySkuId($where);
              $limited_price=$limited_price1['act_price'];
          }else{
              $limited_price=(new MallLimitedSku())->limitMinPrice($result['id'],$goods_id);
          }
          $return1['limited_price']=get_format_number($limited_price);
          return $return1;
      }
      return $result;
  }

    /**
     * @param $goods_id
     * @param $skuid
     * @param $type
     * @return \json
     * @throws Exception
     * 查询各个活动信息
     * @author mrdeng
     */
  public function getAllActivityDetail($goods_id,$skuid,$type){
      try{
          return (new MallActivity())->getAllActivityDetail($goods_id,$skuid,$type);
      }catch (Exception $e) {
        throw new Exception(L_($e->getMessage()));
      }
  }

    /**
     * @param $store_id
     * @return \json
     * 满减满折根据店铺id返回商品id集合
     */
  public function getMallFullMinusDiscountActGoods($store_id){

      $condition[] = ['store_id','=',$store_id];
      $condition[] = ['start_time','<',time()];
      $condition[] = ['end_time','>=',time()];
      $condition[] = ['type','=','minus_discount'];

      $arr=(new MallActivity())->getActInfo($condition,$field='*');
      if(!empty($arr)){
          $return=$arr[0];

          $res=(new MallFullMinusDiscountActService())->getMallFullMinusDiscountGoodsList($return['act_id']);

          $res1=(new MallFullMinusDiscountActService())->getGoodsID($return['act_id']);
          $return['rule']=$res['rule'];
          $return['goods_list']=$res1;
      }else{
          $return=[];
      }

      return $return;
  }

    /**
     * @author mrdeng
     * @param $where
     * @param $field
     * @return array
     * 根据条件获取活动总表的信息
     */
    public function getActInfo($where, $field)
    {
        $arr = $this->MallActivityModel->getActInfo($where, $field);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * @param $act_id
     * @param $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author merdeng
     * 根据活动id取参与活动的商品
     */
    public function getGoodsListByActid($act_id,$store_id,$uid=0){
        $arr=$this->MallActivityModel->getActivityDetail($act_id);
        if($arr['act_type']==1){
            $list=(new MallGoods())->getGoodsListByStoreId($store_id,$uid);
        }else{
            $list=(new MallFullGiveAct())->getGoodsListByActId($act_id,$uid);
        }
        return $list;
    }

    /**
     * @param $store_id
     * @param $goods_id
     * @return array|\json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 根据门店和商品id找出商品详情中商品参与活动的信息
     */
    public function getActiveGoodsMsg($store_id,$goods_id){
        //根据店铺id找出发起的活动
        $arr=(new MallActivity())->getActivityByStoreId($store_id);
        //循环找出商品参与的活动信息
        $goods=array();
        foreach ($arr as $key=>$val){
            $goods=(new MallActivity())->getAllActivityDetail($goods_id,'',$val['type']);
            if(!empty($goods)){
                break;
            }
        }
        return $goods;
    }


    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addActive($data) {
        return (new MallActivity())->addOne($data);
    }

    /** 更新数据
     * Date: 2020-10-16 15:42:29
     * @param array $data
     * @param array|mixed $where
     * @return boolean
     */
    public function updateActive($data,$where) {
        return (new MallActivity())->updateOne($data,$where);
    }

    public function getAllList(){
        $where = [
            ['is_del','=',0]
        ];
        $list = $this->MallActivityModel->getAll($where);
        if($list){
            $list = $this->dealList($list);
        }
        return $list;
    }

    public function dealList($list){
        if($list){
            $MallGoodsService = new MallGoodsService();
            foreach ($list as $key => $val) {
                if($val['goods_ids']){
                    $goods = explode(',', $val['goods_ids']);
                    $where = [
                        ['goods_id','in',$goods],
                    ];
                    $field = 'goods_id,name as goods_name,min_price as price,image';
                    $list[$key]['goods_list'] = $MallGoodsService->getSome($where,$field);
                }
            }
        }
        return $list;
    }

    /**获取正在活动中的商品
     * @param $where
     * @param $field
     * @return array
     */
    public function getGoodsInAct($where, $field)
    {
        $arr = $this->MallActivityModel->getGoodsInAct($where, $field);
        return $arr;
    }

    /**
     * @param $goods_id
     * @param int $activity_id
     * @param int $uid
     * @param int $team_id
     * @param int $skuid
     * @return array
     * 单独获取限时活动的详情信息
     */
    public function getLimitedActGoodsDetail($goods_id,$activity_id=0,$uid=0,$team_id=0,$skuid=0)
    {
        $result = (new MallActivity())->getActivity($goods_id, $activity_id, $uid, $team_id, $skuid);
        if(empty($result)){
          return false;
        }
        $return = array();
        $return['style'] = 'limited';
        $arr = $this->getLimitedActivity($goods_id, $result[0]['id'], '', '', 0, $skuid);
        if (!empty($arr)) {
            if ($arr['limited_status'] == 1) {
                $return['id'] = $result[0]['act_id'];
                $return['buy_limit'] =$return['limit'] = $arr['buy_limit'];
                $return['price'] = $arr['limited_price'];
                $return['surplus'] = $arr['left_time'];
                $return['name'] = $result[0]['name'];
                $return['act_stock_num'] = $arr['act_stock_num'];
                if($arr['is_discount_share']==1){
                    $return['discount_card']=$arr['discount_card'];
                    $return['discount_coupon']=$arr['discount_coupon'];
                }else{
                    $return['discount_card']=0;
                    $return['discount_coupon']=0;
                }
            } else {
                $return['style'] = 'normal';
            }
        } else {
            //获取活动限购失败或者活动没开始
            $return['style'] = 'normal';
        }
         return  $return;
    }

    /** 获取店铺活动 （满减/折、满包邮、N元N件、满赠）
     * @param store_id  门店ID
     * @param goods_data 格式如下
     *        [
     *          [
     *            'sku_id' => 1,
     *            'goods_id' => 1,
     *            'num' => 1,//数量
     *            'price' => 1,//单价
     *          ],...
     *        ]
     * @return [type] [description]
     */
    public function getStoreActivity($store_id, $goods_data){
        /* $return = [
             'type' => '',//活动标识
             'status' => 0,//是否满足 0=未满足  1=已满足
             'satisfied_money' => 0,//已满足条件时，优惠了多少钱(单位：元，保留两位小数)
             'satisfied_skuids' => [],//已满足条件时，规格ID数组
             'id' => 0,//活动ID
             'title' => '',//组装标题
             'give_goods' => [],//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
             'discount_card' => 0,//是否与商家会员卡同享
             'discount_coupon' => 0,//是否与优惠券同享
         ];*/
        if(empty($goods_data) || empty($store_id)){
            throw new \think\Exception(L_("传入的店铺信息和商品信息不完整"), 1001);
        }
        $goods_data=$this->getArrList($goods_data,$field='sku_id');
        $goodsIds=array();//商品id
        $skuIds=array();//规格id
        $goodsSum=0;//传如商品价格总和
        $goodsNum=0;//总共件数
        for ($i=0;$i<count($goods_data);$i++){
            $goodsIds[]=$goods_data[$i]['goods_id'];
            $skuIds[]=$goods_data[$i]['sku_id'];
            $goodsNum=$goodsNum+$goods_data[$i]['num'];
            $goodsSum+=$goods_data[$i]['num']*$goods_data[$i]['price'];
        }
        $where[]=[//条件
            ['store_id','=',$store_id],
            ['status','=',1],
            ['start_time','<',time()],
            ['end_time','>=',time()],
            ['type','in',['reached','minus_discount','give','shipping']],
        ];

        $arr_act=[];
        //先查询门店是否有活动，再根据活动找出商品，取出商品的交集看是否满足当前活动要求，返回信息
        $return1=(new MallActivity())->getActField($where,$order='sort asc',$field='id,act_id,type,act_type');
        $uid=request()->log_uid;
        $return=array();
        if(!empty($return1)){//查询出不为空
           foreach ($return1 as $key=>$val){
               $order_join_nums=(new MallOrderService())->getStoreActivityJoinNums($val['type'],$val['act_id'],$uid);//用户购买该活动商品次数
               if($val['type']=='reached'){//门店活动优先级第一,N元N件

                 $where_reach=[
                     ['r.id','=',$val['act_id']]
                 ];
                 $return['is_buy']=0;//默认是不满足限购条件
                 $arr=(new MallReachedAct())->getInfo($where_reach,$field='*');//查询N元N件活动信息
                 $return['buy_limit'] =$arr['buy_limit'];
                 if($return['buy_limit']==0){
                       $return['is_buy']=1;//不限购，可以下单
                 }else{
                       if($return['buy_limit']>$order_join_nums){
                           $return['is_buy']=1;//限购，但是满足要求可以下单
                       }else{
                           continue;
                       }
                 }
                 if($val['act_type']==0){//不是全店
                   $where_detail=[
                     ['activity_id','=',$val['id']]
                   ];
                   $act_goods=(new MallActivityDetail())->getActField($where_detail,$field='goods_id');//找出活动明细的商品信息
                   $inter_goods=$this->getArrayInter($act_goods,$goodsIds);//与活动商品有交集的商品
                   if(!empty($inter_goods)){//有活动商品
                    //有活动的商品
                      /* $where_goods=[
                           ['goods_id','in',$inter_goods]
                       ];*/
                       $s_nums=0;//满足条件选择的件数
                       $goods_all_price=0;
                       $my_get_price=0;//获取N件价格
                       $open=true;//开关
                       $arr_list=array();//N元N件活动的放入这个数组
                       for($i=0;$i<count($inter_goods);$i++){
                           foreach ($goods_data as $k1=>$v1){
                               if($inter_goods[$i]==$v1['goods_id']){
                                   $goods_all_price+=$v1['num']*$v1['price'];
                                   $s_nums+=$v1['num'];
                                   $arr_list[]=$v1;
                                   if($s_nums>=$arr['nums'] && $open){
                                       $open=false;
                                       $my_get_price=$goods_all_price-($s_nums-$arr['nums'])*$v1['price'];
                                   }
                               }
                           }
                       }
                       //$goods_all_price=(new MallGoods())->getSum($where_goods,'price');
                       if($goods_all_price<$arr['money'] || $my_get_price<$arr['money']){//不满足活动
                           if(in_array('reached',$arr_act)){//已经有一个不满足，不需要再走下一个
                              continue;
                           }
                           $return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                           $return['type']=$val['type'];//活动标识
                           $return['id']=$val['act_id'];//活动ID
                           //是否优惠同享
                           if($arr['is_discount_share']==1){
                               $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                               $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                           }else{
                               $return['discount_card']=0;
                               $return['discount_coupon']=0;
                           }
                           $return['status']=0;//是否满足 0=未满足  1=已满足
                           $return['satisfied_money'] = 0;//已满足条件时，优惠了多少钱(单位：元，保留两位小数)
                           $return['satisfied_skuids']=[];//已满足条件时，规格ID数组
                           $title=get_format_number($arr['money'])."元任选".get_format_number($arr['nums'])."件".' '.'还差'.get_format_number(($arr['money']-$goods_all_price)).'元';
                           //价格最高N件不满足价格，但是达标活动商品总价格价格达到N元
                           if($my_get_price<$arr['money'] && $goods_all_price>$arr['money']){
                               $title="不满足".get_format_number($arr['money'])."元任选".get_format_number($arr['nums'])."件";
                           }
                           $arr_act[]=$val['type'];
                           $return['title']=$title;//组装标题
                       }else{//满足活动
                           $return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                           $return['type']=$val['type'];//活动标识
                           $return['id']=$val['act_id'];//活动ID
                           //是否优惠同享
                           if($arr['is_discount_share']==1){
                               $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                               $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                           }else{
                               $return['discount_card']=0;
                               $return['discount_coupon']=0;
                           }
                           $return['status']=1;
                           $arr_price=0;//起始价格,最优选择的总价
                           if($s_nums>=$arr['nums']){//满足件数
                               $last_names = array_column($arr_list,'price');
                               array_multisort($last_names,SORT_DESC,$arr_list);//价格倒叙
                               $arr_num=0;//起始件数,剩余件数
                               $arr_num1=0;
                               foreach ($arr_list as $ks=>$vs){
                                   $arr_num=$arr_num+$vs['num'];
                                   if($arr_num1<=$arr['nums']){//从这控制循环
                                       if($arr_num>=$arr['nums']){
                                           if($arr_num1>0){//不是第一次判断
                                               if($arr['nums']-$arr_num1==0){//刚好满足件数时，直接加上最后一件价格
                                                   //$arr_price=$arr_price+$vs['price'];
                                               }else{//不满足件数时，直接求最后满足件数价格
                                                   $arr_price=$arr_price+($arr['nums']-$arr_num1)*$vs['price'];
                                               }
                                           }else{
                                               //第一次走这里
                                               $arr_price=$arr_price+$arr['nums']*$vs['price'];
                                           }
                                           //$arr_num1=$arr_num1+$arr_num;
                                           //$arr_num1=$arr['nums'];
                                       }else{
                                           //$arr_num1=$arr_num1+$arr_num;
                                           $arr_price=$arr_price+$vs['num']*$vs['price'];
                                       }
                                       $arr_num1=$arr_num1+$vs['num'];
                                   }
                               }
                           }
                           $return['satisfied_money'] = 0;
                           if($arr['money']<$arr_price || $arr['money']<$goods_all_price){
                               if($arr_price==0){
                                   $return['satisfied_money'] =$goods_all_price-$arr['money'];
                               }else{
                                   $return['satisfied_money'] =$arr_price-$arr['money'];
                               }
                           }
                           $return['satisfied_skuids']=$this->getSatisfyGoodsSku($inter_goods,$goods_data);//有交集的商品对应的sku
                           $title="已满足".get_format_number($arr['money'])."元任选".get_format_number($arr['nums'])."件";
                           $return['title']=$title;//组装标题
                           break;
                       }

                   }
                 }
                 else{//全店
                     if(empty($arr)){
                         throw new \think\Exception(L_("N元N件活动子表没有信息"), 1001);
                     }else{
                         if($arr['money']>$goodsSum){
                             $return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                             $return['type']=$val['type'];//活动标识
                             $return['id']=$val['act_id'];//活动ID
                             //是否优惠同享
                             if($arr['is_discount_share']==1){
                                 $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                 $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                             }else{
                                 $return['discount_card']=0;
                                 $return['discount_coupon']=0;
                             }

                             $return['status']=0;//是否满足 0=未满足  1=已满足
                             $return['satisfied_money'] = 0;//已满足条件时，优惠了多少钱(单位：元，保留两位小数)
                             $return['satisfied_skuids']=[];//已满足条件时，规格ID数组
                             $title=get_format_number($arr['money'])."元任选".get_format_number($arr['nums'])."件".' '.'还差'.get_format_number($arr['money']-$goodsSum).'元';
                             $return['title']=$title;//组装标题
                         }else{
                             $return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                             $return['type']=$val['type'];//活动标识
                             $return['id']=$val['act_id'];//活动ID
                             //是否优惠同享
                             if($arr['is_discount_share']==1){
                                 $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                 $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                             }else{
                                 $return['discount_card']=0;
                                 $return['discount_coupon']=0;
                             }
                             $open=true;
                             $arr_price=0;//起始价格,最优选择的总价
                             $my_get_price=0;
                             if($goodsNum>=$arr['nums']){//满足件数
                                 $last_names = array_column($goods_data,'price');
                                 array_multisort($last_names,SORT_DESC,$goods_data);
                                 $arr_num=0;//起始件数,剩余件数
                                 $arr_num1=0;
                                 foreach ($goods_data as $ks=>$vs){
                                     $arr_num=$arr_num+$vs['num'];
                                     if($arr_num1<=$arr['nums']){//从这控制循环
                                         /*if($arr_num>=$arr['nums']){
                                             if($arr_num1>0){//不是第一次判断
                                                 $arr_price=$arr_price+($arr['nums']-$arr_num1)*$vs['price'];
                                             }else{
                                                 //第一次走这里
                                                 $arr_price=$arr_price+$arr['nums']*$vs['price'];
                                             }
                                             $arr_num1=$arr['nums'];
                                         }else{
                                             $arr_num1=$arr_num1+$arr_num;
                                             $arr_price=$arr_price+$vs['num']*$vs['price'];
                                         }*/
                                         if($arr_num>=$arr['nums']){
                                             if($arr_num1>0){//不是第一次判断
                                                 if($arr['nums']-$arr_num1==0){//刚好满足件数时，直接加上最后一件价格
                                                     $arr_price=$arr_price+$vs['price'];
                                                 }else{//不满足件数时，直接求最后满足件数价格
                                                     $arr_price=$arr_price+($arr['nums']-$arr_num1)*$vs['price'];
                                                 }
                                             }else{
                                                 //第一次走这里
                                                 $arr_price=$arr_price+$arr['nums']*$vs['price'];
                                             }
                                             if($open){
                                                 $open=false;
                                                 $my_get_price=$arr_price-($arr_num-$arr['nums'])*$vs['price'];
                                             }
                                             //$arr_num1=$arr_num1+$arr_num;
                                             //$arr_num1=$arr['nums'];
                                         }else{
                                             //$arr_num1=$arr_num1+$arr_num;
                                             $arr_price=$arr_price+$vs['num']*$vs['price'];
                                         }
                                         $arr_num1=$arr_num1+$arr_num;
                                     }

                                 }
                             }
                             $return['satisfied_money'] = 0;
                             if($arr['money']<$arr_price){
                                 $return['satisfied_money'] =$arr_price-$arr['money'];
                             }
                             if($arr['money']<$arr_price || $arr['money']<$goodsSum){
                                 if($arr_price==0){
                                     $return['satisfied_money'] =$goodsSum-$arr['money'];
                                 }else{
                                     $return['satisfied_money'] =$arr_price-$arr['money'];
                                 }
                             }

                             $return['status']=1;
                             $return['satisfied_skuids']=$skuIds;
                             if($my_get_price>=$arr['money']){
                                 $title="已满足".get_format_number($arr['money'])."元任选".get_format_number($arr['nums'])."件";
                             }else{
                                 $title="不满足".get_format_number($arr['money'])."元任选".get_format_number($arr['nums'])."件";
                             }
                             $return['title']=$title;//组装标题
                             break;
                         }
                     }
                 }
             }
               elseif($val['type']=='minus_discount'){//门店活动优先级第二,满减折
                 $where_reach=[
                     ['s.id','=',$val['act_id']]
                 ];
                 $arr=(new MallFullMinusDiscountAct())->getInfo($where_reach,$field='*');//查询满减折活动信息

                 $return['buy_limit'] =$arr['max_num'];
                 if($return['buy_limit']==0){
                     $return['is_buy']=1;//不限购，可以下单
                 }else{
                     if($return['buy_limit']>=$order_join_nums){
                         $return['is_buy']=1;//限购，但是满足要求可以下单
                     }else{
                         continue;
                     }
                 }
                 if($val['act_type']==0){//不是全店
                     $where_detail=[
                         ['activity_id','=',$val['id']]
                     ];
                     $act_goods=(new MallActivityDetail())->getActField($where_detail,$field='goods_id');//找出活动明细的商品信息
                     $inter_goods=$this->getArrayInter($act_goods,$goodsIds);//与活动商品有交集的商品
                     if(!empty($inter_goods)){//有活动的商品
                         /*$where_goods=[
                             ['goods_id','in',$inter_goods]
                         ];*/
                         $goods_all_price=0;
                         for($i=0;$i<count($inter_goods);$i++){
                             foreach ($goods_data as $k1=>$v1){
                                 if($inter_goods[$i]==$v1['goods_id']){
                                     $goods_all_price+=$v1['num']*$v1['price'];
                                 }
                             }
                         }
                         $where_level=[
                             ['act_id','=',$val['act_id']],
                             ['level_money','<=',$goods_all_price]
                         ];
                         $field="*";
                         $order="level_money desc";
                         $goods_level=(new MallFullMinusDiscountLevel())->getInfo($where_level,$field,$order);
                         if(!empty($goods_level)){//满足
                             $return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                             $return['type']=$val['type'];//活动标识
                             $return['id']=$val['act_id'];//活动ID
                             //$title="不满足满减折活动";
                             //是否优惠同享
                             if($arr['is_discount_share']==1){
                                 $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                 $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                             }else{
                                 $return['discount_card']=0;
                                 $return['discount_coupon']=0;
                             }

                             $return['status']=1;
                             if($arr['is_discount']==0){//满减
                                 $return['satisfied_money'] = $goods_level['level_discount'];
                                 $title="满".get_format_number($goods_level['level_money'])."元减".get_format_number($goods_level['level_discount']).'元';
                             }else{//满折
                                 if($goods_level['level_discount']*1>10 || $goods_level['level_discount']<0){//大于10折，避免出现负数
                                     continue;
                                 }else{
                                     $level=(10-$goods_level['level_discount'])*10;
                                     $return['satisfied_money'] = get_format_number($goods_all_price*($level/100));
                                     $title="满".get_format_number($goods_level['level_money'])."元".get_format_number($goods_level['level_discount']).'折';
                                 }
                             }
                             $return['satisfied_skuids']=$this->getSatisfyGoodsSku($inter_goods,$goods_data);//有交集的商品对应的sku
                             if($return['satisfied_money']<=0){
                                 $return['satisfied_money']=0;
                             }
                             $return['title']=$title;//组装标题
                             break;
                         }else{
                             //不满足
                             if(in_array('reached',$arr_act) || in_array('minus_discount',$arr_act)){//已经有一个不满足，不需要再走下一个
                                 continue;
                             }
                             $return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                             $return['type']=$val['type'];//活动标识
                             $return['id']=$val['act_id'];//活动ID
                             //$title="不满足满减折活动";
                             //是否优惠同享
                             if($arr['is_discount_share']==1){
                                 $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                 $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                             }else{
                                 $return['discount_card']=0;
                                 $return['discount_coupon']=0;
                             }

                             $return['status']=0;
                             $where_level=[
                                 ['act_id','=',$val['act_id']],
                             ];
                             $field="*";
                             $order="level_money asc";
                             $goods_level=(new MallFullMinusDiscountLevel())->getInfo($where_level,$field,$order);
                             if($arr['is_discount']==0){//满减
                                 $title="满".get_format_number($goods_level['level_money'])."元减".get_format_number($goods_level['level_discount']).'元'.'  '.'还差'.get_format_number($goods_level['level_money']-$goods_all_price).'元';
                             }else{//满折
                                 $title="满".get_format_number($goods_level['level_money'])."元".get_format_number($goods_level['level_discount']).'折'.'  '.'还差'.get_format_number($goods_level['level_money']-$goods_all_price).'元';
                             }
                             $return['title']=$title;//组装标题
                             $arr_act[]=$val['type'];
                         }
                     }
                 }
                 else{//全店
                     if(empty($arr)){
                         throw new \think\Exception(L_("满减折活动子表没有信息"), 1001);
                     }else{
                         $where_level=[
                             ['act_id','=',$val['act_id']],
                             ['level_money','<=',$goodsSum]
                         ];
                         $field="*";
                         $order="level_money desc";
                         $goods_level=(new MallFullMinusDiscountLevel())->getInfo($where_level,$field,$order);
                        if(!empty($goods_level)){//满足
                            $return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                            $return['type']=$val['type'];//活动标识
                            $return['id']=$val['act_id'];//活动ID
                            //$title="不满足满减折活动";
                            //是否优惠同享
                            if($arr['is_discount_share']==1){
                                $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                            }else{
                                $return['discount_card']=0;
                                $return['discount_coupon']=0;
                            }

                            $return['status']=1;
                            $return['satisfied_money'] = $goods_level['level_discount'];
                            $return['satisfied_skuids']=$skuIds;//有交集的商品对应的sku
                            if($arr['is_discount']==0){//满减
                                $return['satisfied_money'] = $goods_level['level_discount'];
                                $title="满".get_format_number($goods_level['level_money'])."元减".get_format_number($goods_level['level_discount']).'元';

                            }else{//满折
                                $return['satisfied_money'] = $goodsSum*(1-$goods_level['level_discount']/10);
                                $title="满".get_format_number($goods_level['level_money'])."元".get_format_number($goods_level['level_discount']).'折';
                            }
                            if($return['satisfied_money']<=0){
                                $return['satisfied_money']=0;
                            }
                            $return['title']=$title;//组装标题
                            break;
                         }else{
                            //不满足
                            $return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                            $return['type']=$val['type'];//活动标识
                            $return['id']=$val['act_id'];//活动ID
                            //$title="不满足满减折活动";
                            //是否优惠同享
                            if($arr['is_discount_share']==1){
                                $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                            }else{
                                $return['discount_card']=0;
                                $return['discount_coupon']=0;
                            }

                            $return['status']=0;
                            $where_level=[
                                ['act_id','=',$val['act_id']],
                            ];
                            $field="*";
                            $order="level_money asc";
                            $goods_level=(new MallFullMinusDiscountLevel())->getInfo($where_level,$field,$order);
                            if($arr['is_discount']==0){//满减
                                $title="满".get_format_number($goods_level['level_money'])."元减".get_format_number($goods_level['level_discount']).'元'.'  '.'还差'.get_format_number($goods_level['level_money']-$goodsSum).'元';
                            }else{//满折
                                $title="满".get_format_number($goods_level['level_money'])."元".get_format_number($goods_level['level_discount']).'折'.'  '.'还差'.get_format_number($goods_level['level_money']-$goodsSum).'元';
                            }
                            $return['title']=$title;//组装标题
                        }
                     }
                 }
             }
               elseif ($val['type']=='give'){//门店活动优先级第三,满赠
                 $where_reach=[
                     ['s.id','=',$val['act_id']]
                 ];
                 $arr=(new MallFullGiveAct())->getInfo($where_reach,$field='*');//查询满赠活动信息
                 $return['buy_limit'] =$arr['join_max_num'];
                 if($return['buy_limit']==0){
                     $return['is_buy']=1;//不限购，可以下单
                 }else{
                     if($return['buy_limit']>$order_join_nums){
                         $return['is_buy']=1;//限购，但是满足要求可以下单
                     }else{
                         continue;
                     }
                 }
                 if($val['act_type']==0){//不是全店
                     $where_detail=[
                         ['activity_id','=',$val['id']]
                     ];

                     $act_goods=(new MallActivityDetail())->getActField($where_detail,$field='goods_id');//找出活动明细的商品信息

                     $inter_goods=$this->getArrayInter($act_goods,$goodsIds);//与活动商品有交集的商品

                     if(!empty($inter_goods)){//有活动的商品
                         if($arr['full_type']==1){//1满多少元
                             /*$where_goods=[
                                 ['goods_id','in',$inter_goods]
                             ];*/
                             $goods_all_price=0;
                             for($i=0;$i<count($inter_goods);$i++){
                                 foreach ($goods_data as $k1=>$v1){
                                     if($inter_goods[$i]==$v1['goods_id']){
                                         $goods_all_price+=$v1['num']*$v1['price'];
                                     }
                                 }
                             }
                             //$goods_all_price=(new MallGoods())->getSum($where_goods,'price');
                             $where_level=[
                                 ['act_id','=',$val['act_id']],
                                 ['level_money','<=',$goods_all_price]
                             ];
                             $field="*";
                             $order="level_money desc";
                             $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                             if(!empty($goods_level)){//满足
                                 $return['type']=$val['type'];//活动标识
                                 $return['id']=$val['act_id'];//活动ID
                                 //是否优惠同享
                                 if($arr['is_discount_share']==1){
                                     $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                                 }else{
                                     $return['discount_card']=0;
                                     $return['discount_coupon']=0;
                                 }

                                 $return['status']=1;
                                 $return['satisfied_money'] = 0;
                                 $satisfied_skuids=$this->getSatisfyGoodsSku($inter_goods,$goods_data);//有交集的商品对应的sku
                                 $return['satisfied_skuids']=$satisfied_skuids;
                                 $where_skuids=[
                                     ['level_num','=',$goods_level['id']]
                                 ];
                                 $sku_list=(new MallFullGiveGiftSku())->getMsgList($where_skuids);
                                 if(empty($sku_list)){
                                     throw new \think\Exception(L_("满赠活动的赠送商品规格表没有信息"), 1001);
                                 }
                                 $mylist=array();
                                 foreach ($sku_list as $k=>$v){
                                     if($v['act_stock_num']==0){//庫存為0
                                       continue;
                                     }
                                     $arrt[$v['sku_id']]=$v['gift_num'];
                                     $mylist[]=$arrt;
                                 }
                                 $return['give_goods']=end($mylist);//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $title="满".get_format_number($goods_level['level_money'])."元送赠品";
                                 $return['title']=$title;//组装标题
                                 break;
                             }else{//不满足
                                 if(in_array('reached',$arr_act) || in_array('minus_discount',$arr_act) || in_array('give',$arr_act)){//已经有一个不满足，不需要再走下一个
                                     continue;
                                 }
                                 $return['type']=$val['type'];//活动标识
                                 $return['id']=$val['act_id'];//活动ID
                                 //是否优惠同享
                                 if($arr['is_discount_share']==1){
                                     $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                                 }else{
                                     $return['discount_card']=0;
                                     $return['discount_coupon']=0;
                                 }

                                 $return['status']=0;
                                 $where_level=[
                                     ['act_id','=',$val['act_id']],
                                 ];
                                 $field="*";
                                 $order="level_money asc";
                                 $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                                 $title="满".get_format_number($goods_level['level_money'])."元送赠品".' '.'还差'.get_format_number($goods_level['level_money']-$goods_all_price)."元";
                                 $return['title']=$title;//组装标题
                                 $arr_act[]=$val['type'];
                             }

                         }
                         else{//2满多少件
                             $goods_all_price=0;
                             for($i=0;$i<count($inter_goods);$i++){
                                 foreach ($goods_data as $k1=>$v1){
                                     if($inter_goods[$i]==$v1['goods_id']){
                                         $goods_all_price+=$v1['num'];
                                     }
                                 }
                             }
                             $where_level=[
                                 ['act_id','=',$val['act_id']],
                                 ['level_money','<=',$goods_all_price]
                             ];
                             $field="*";
                             $order="level_money desc";
                             $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                             if(!empty($goods_level)){//满足
                                 $return['type']=$val['type'];//活动标识
                                 $return['id']=$val['act_id'];//活动ID
                                 //是否优惠同享
                                 if($arr['is_discount_share']==1){
                                     $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                                 }else{
                                     $return['discount_card']=0;
                                     $return['discount_coupon']=0;
                                 }

                                 $return['status']=1;
                                 $return['satisfied_money'] = 0;
                                 $satisfied_skuids=$this->getSatisfyGoodsSku($inter_goods,$goods_data);//有交集的商品对应的sku
                                 $return['satisfied_skuids']=$satisfied_skuids;
                                 $where_skuids=[
                                     ['level_num','=',$goods_level['id']]
                                 ];
                                 $sku_list=(new MallFullGiveGiftSku())->getMsgList($where_skuids);
                                 if(empty($sku_list)){
                                     throw new \think\Exception(L_("满赠活动的赠送商品规格表没有信息"), 1001);
                                 }
                                 //$return['give_goods']=[$sku_list['sku_id']=>$sku_list['gift_num']];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $mylist=array();
                                 foreach ($sku_list as $k=>$v){
                                     if($v['act_stock_num']==0){//庫存為0
                                         continue;
                                     }
                                     $arrt[$v['sku_id']]=$v['gift_num'];
                                     $mylist[]=$arrt;
                                 }
                                 $return['give_goods']=end($mylist);//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]

                                 $title="满".get_format_number($goods_level['level_money'])."件送赠品";
                                 $return['title']=$title;//组装标题
                                 break;
                             }else{//不满足
                                 if(in_array('reached',$arr_act) || in_array('minus_discount',$arr_act) || in_array('give',$arr_act)){//已经有一个不满足，不需要再走下一个
                                     continue;
                                 }

                                 $return['type']=$val['type'];//活动标识
                                 $return['id']=$val['act_id'];//活动ID
                                 //是否优惠同享
                                 if($arr['is_discount_share']==1){
                                     $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                                 }else{
                                     $return['discount_card']=0;
                                     $return['discount_coupon']=0;
                                 }

                                 $return['status']=0;
                                 $field="*";
                                 $where_level=[
                                     ['act_id','=',$val['act_id']],
                                 ];
                                 $order="level_money asc";
                                 $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                                 $title="满".get_format_number($goods_level['level_money'])."件送赠品".' '.'还差'.get_format_number($goods_level['level_money']-count($inter_goods))."件";
                                 $return['title']=$title;//组装标题
                                 $arr_act[]=$val['type'];
                             }
                         }
                     }
                 }else{//全店
                     if(empty($arr)){
                         throw new \think\Exception(L_("满赠活动子表没有信息"), 1001);
                     }else{
                         if($arr['full_type']==1){//1满多少元
                             /*$where_goods=[
                                 ['goods_id','in',$goodsIds]
                             ];
                             $goods_all_price=(new MallGoods())->getSum($where_goods,'price');*/
                             $goods_all_price=0;
                                 foreach ($goods_data as $k1=>$v1){
                                      $goods_all_price+=$v1['num']*$v1['price'];
                                 }
                             $where_level=[
                                 ['act_id','=',$val['act_id']],
                                 ['level_money','<=',$goods_all_price]
                             ];
                             $field="*";
                             $order="level_money desc";
                             $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                             if(!empty($goods_level)){//满足
                                 $return['type']=$val['type'];//活动标识
                                 $return['id']=$val['act_id'];//活动ID
                                 //是否优惠同享
                                 if($arr['is_discount_share']==1){
                                     $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                                 }else{
                                     $return['discount_card']=0;
                                     $return['discount_coupon']=0;
                                 }

                                 $return['status']=1;
                                 $return['satisfied_money'] = 0;
                                 $satisfied_skuids=$skuIds;//有交集的商品对应的sku
                                 $return['satisfied_skuids']=$satisfied_skuids;
                                 $where_skuids=[
                                     ['level_num','=',$goods_level['id']]
                                 ];
//                                 $sku_list=(new MallFullGiveGiftSku())->getMsg($where_skuids);
                                 $sku_list=(new MallFullGiveGiftSku())->getMsgList($where_skuids);
                                 if(empty($sku_list)){
                                     throw new \think\Exception(L_("满赠活动的赠送商品规格表没有信息"), 1001);
                                 }
                                 //$return['give_goods']=[$sku_list['sku_id']=>$sku_list['gift_num']];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $mylist=array();
                                 foreach ($sku_list as $k=>$v){
                                     if($v['act_stock_num']==0){//庫存為0
                                         continue;
                                     }
                                     $arrt[$v['sku_id']]=$v['gift_num'];
                                     $mylist[]=$arrt;
                                 }
                                 $return['give_goods']=end($mylist);//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]

                                 $title="满".get_format_number($goods_level['level_money'])."元送赠品";
                                 $return['title']=$title;//组装标题
                                 break;
                             }else{//不满足
                                 $return['type']=$val['type'];//活动标识
                                 $return['id']=$val['act_id'];//活动ID
                                 //是否优惠同享
                                 if($arr['is_discount_share']==1){
                                     $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                                 }else{
                                     $return['discount_card']=0;
                                     $return['discount_coupon']=0;
                                 }

                                 $return['status']=0;
                                 $field="*";
                                 $where_level=[
                                     ['act_id','=',$val['act_id']],
                                 ];
                                 $order="level_money asc";
                                 $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                                 $title="满".get_format_number($goods_level['level_money'])."元送赠品".' '.'还差'.get_format_number($goods_level['level_money']-$goods_all_price)."元";
                                 $return['title']=$title;//组装标题
                             }
                         }
                         else{//2满多少件
                             $goods_all_price=0;
                             foreach ($goods_data as $k1=>$v1){
                                 $goods_all_price+=$v1['num'];
                             }
                             $where_level=[
                                 ['act_id','=',$val['act_id']],
                                 ['level_money','<=',$goods_all_price]
                             ];
                             $field="*";
                             $order="level_money desc";
                             $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                             if(!empty($goods_level)){//满足
                                 $return['type']=$val['type'];//活动标识
                                 $return['id']=$val['act_id'];//活动ID
                                 //是否优惠同享
                                 if($arr['is_discount_share']==1){
                                     $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                                 }else{
                                     $return['discount_card']=0;
                                     $return['discount_coupon']=0;
                                 }

                                 $return['status']=1;
                                 $return['satisfied_money'] = 0;
                                 $satisfied_skuids=$skuIds;//有交集的商品对应的sku
                                 $return['satisfied_skuids']=$satisfied_skuids;
                                 $where_skuids=[
                                     ['level_num','=',$goods_level['id']]
                                 ];
//                                 $sku_list=(new MallFullGiveGiftSku())->getMsg($where_skuids);
                                 $sku_list=(new MallFullGiveGiftSku())->getMsgList($where_skuids);
                                 if(empty($sku_list)){
                                     throw new \think\Exception(L_("满赠活动的赠送商品规格表没有信息"), 1001);
                                 }
                                 //$return['give_goods']=[$sku_list['sku_id']=>$sku_list['gift_num']];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $mylist=array();
                                 foreach ($sku_list as $k=>$v){
                                     if($v['act_stock_num']==0){//庫存為0
                                         continue;
                                     }
                                     $arrt[$v['sku_id']]=$v['gift_num'];
                                     $mylist[]=$arrt;
                                 }
                                 $return['give_goods']=end($mylist);//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]

                                 $title="满".get_format_number($goods_level['level_money'])."件送赠品";
                                 $return['title']=$title;//组装标题
                                 break;
                             }else{
                                 $return['type']=$val['type'];//活动标识
                                 $return['id']=$val['act_id'];//活动ID
                                 //是否优惠同享
                                 if($arr['is_discount_share']==1){
                                     $return['discount_card']=$arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon']=$arr['discount_coupon'];//是否与优惠券同享
                                 }else{
                                     $return['discount_card']=0;
                                     $return['discount_coupon']=0;
                                 }

                                 $return['status']=0;
                                 $field="*";
                                 $where_level=[
                                     ['act_id','=',$val['act_id']],
                                 ];
                                 $order="level_money asc";
                                 $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                                 $title="满".get_format_number($goods_level['level_money'])."件送赠品".' '.'还差'.get_format_number($goods_level['level_money']-count($goodsIds))."件";
                                 $return['title']=$title;//组装标题
                             }
                         }
                     }
                 }
             }
               else {//门店活动优先级第四,满包邮
                 $where_reach = [
                     ['s.id', '=', $val['act_id']]
                 ];
                 $arr = (new MallShippingAct())->getInfo($where_reach, $field = '*');//满包邮活动
                 $return['buy_limit'] =$arr['join_max_num'];

                 if($return['buy_limit']==0){
                     $return['is_buy']=1;//不限购，可以下单
                 }else{
                     if($return['buy_limit']>$order_join_nums){
                         $return['is_buy']=1;//限购，但是满足要求可以下单
                     }else{
                         continue;
                     }
                 }

                 if ($val['act_type'] == 0) {//不是全店
                     $where_detail = [
                         ['activity_id', '=', $val['id']]
                     ];
                     $act_goods = (new MallActivityDetail())->getActField($where_detail, $field = 'goods_id');//找出活动明细的商品信息
                     $inter_goods = $this->getArrayInter($act_goods, $goodsIds);//与活动商品有交集的商品
                     if (!empty($inter_goods)){//有活动的商品
                         if ($arr['full_type'] == 0) {//满XX元包邮
                             /*$where_goods = [
                                 ['goods_id', 'in', $inter_goods]
                             ];*/
                             $goods_all_price=0;
                             for($i=0;$i<count($inter_goods);$i++){
                                 foreach ($goods_data as $k1=>$v1){
                                     if($inter_goods[$i]==$v1['goods_id']){
                                         $goods_all_price+=$v1['num']*$v1['price'];
                                     }
                                 }
                             }
                             //$goods_all_price = (new MallGoods())->getSum($where_goods, 'price');
                             if ($arr['nums'] <= $goods_all_price) {
                                 $return['give_goods'] = [];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $return['type'] = $val['type'];//活动标识
                                 $return['id'] = $val['act_id'];//活动ID
                                 //是否优惠同享
                                 if ($arr['is_discount_share'] == 1) {
                                     $return['discount_card'] = $arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon'] = $arr['discount_coupon'];//是否与优惠券同享
                                 } else {
                                     $return['discount_card'] = 0;
                                     $return['discount_coupon'] = 0;
                                 }

                                 $return['status'] = 1;
                                 $return['satisfied_money'] = 0;
                                 $return['satisfied_skuids'] = $this->getSatisfyGoodsSku($inter_goods, $goods_data);//有交集的商品对应的sku
                                 $title = "满足".get_format_number($arr['nums']) ."元包邮";
                                 $return['title'] = $title;//组装标题
                                 break;
                             } else {
                                 if(in_array('reached',$arr_act) || in_array('minus_discount',$arr_act) || in_array('give',$arr_act) || in_array('shipping',$arr_act)){//已经有一个不满足，不需要再走下一个
                                     continue;
                                 }
                                 $return['give_goods'] = [];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $return['type'] = $val['type'];//活动标识
                                 $return['id'] = $val['act_id'];//活动ID
                                 //是否优惠同享
                                 if ($arr['is_discount_share'] == 1) {
                                     $return['discount_card'] = $arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon'] = $arr['discount_coupon'];//是否与优惠券同享
                                 } else {
                                     $return['discount_card'] = 0;
                                     $return['discount_coupon'] = 0;
                                 }

                                 $return['status'] = 0;
                                 $return['satisfied_money'] = 0;
                                 $title = "满足" . get_format_number($arr['nums']) . "元包邮" . ' ' . '还差' . get_format_number($arr['nums'] - $goods_all_price) . '元';
                                 $return['title'] = $title;//组装标题
                                 $arr_act[]=$val['type'];
                             }
                         } else {//满XX件包邮
                             $goods_all_price=0;
                             for($i=0;$i<count($inter_goods);$i++){
                                 foreach ($goods_data as $k1=>$v1){
                                     if($inter_goods[$i]==$v1['goods_id']){
                                         $goods_all_price+=$v1['num'];
                                     }
                                 }
                             }
                             if ($arr['nums'] <= $goods_all_price) {
                                 $return['give_goods'] = [];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $return['type'] = $val['type'];//活动标识
                                 $return['id'] = $val['act_id'];//活动ID
                                 //是否优惠同享
                                 if ($arr['is_discount_share'] == 1) {
                                     $return['discount_card'] = $arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon'] = $arr['discount_coupon'];//是否与优惠券同享
                                 } else {
                                     $return['discount_card'] = 0;
                                     $return['discount_coupon'] = 0;
                                 }

                                 $return['status'] = 1;
                                 $return['satisfied_money'] = 0;
                                 $return['satisfied_skuids'] = $this->getSatisfyGoodsSku($inter_goods, $goods_data);//有交集的商品对应的sku
                                 $title = "满足" . get_format_number($arr['nums']) . "件包邮";
                                 $return['title'] = $title;//组装标题
                                 break;
                             } else {
                                 if(in_array('reached',$arr_act) || in_array('minus_discount',$arr_act) || in_array('give',$arr_act) || in_array('shipping',$arr_act)){//已经有一个不满足，不需要再走下一个
                                     continue;
                                 }
                                 $return['give_goods'] = [];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $return['type'] = $val['type'];//活动标识
                                 $return['id'] = $val['act_id'];//活动ID
                                 //是否优惠同享
                                 if ($arr['is_discount_share'] == 1) {
                                     $return['discount_card'] = $arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon'] = $arr['discount_coupon'];//是否与优惠券同享
                                 } else {
                                     $return['discount_card'] = 0;
                                     $return['discount_coupon'] = 0;
                                 }

                                 $return['status'] = 0;
                                 $return['satisfied_money'] = 0;
                                 $title = "满足" . get_format_number($arr['nums']) . "件包邮" . ' ' . '还差' . get_format_number($arr['nums'] - $goods_all_price) . '件';
                                 $return['title'] = $title;//组装标题
                                 $arr_act[]=$val['type'];
                             }
                         }
                     }
                 } else {//全店
                     if (empty($arr)) {
                         throw new \think\Exception(L_("满包邮活动子表没有信息"), 1001);
                     } else {
                         if ($arr['full_type'] == 0) {//满XX元包邮
                             /*$where_goods = [
                                 ['goods_id', 'in', $goodsIds]
                             ];
                             $goods_all_price = (new MallGoods())->getSum($where_goods, 'price');*/
                             if ($arr['nums'] <= $goodsSum) {
                                 $return['give_goods'] = [];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $return['type'] = $val['type'];//活动标识
                                 $return['id'] = $val['act_id'];//活动ID
                                 //是否优惠同享
                                 if ($arr['is_discount_share'] == 1) {
                                     $return['discount_card'] = $arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon'] = $arr['discount_coupon'];//是否与优惠券同享
                                 } else {
                                     $return['discount_card'] = 0;
                                     $return['discount_coupon'] = 0;
                                 }

                                 $return['status'] = 1;
                                 $return['satisfied_money'] = 0;
                                 $return['satisfied_skuids'] = $skuIds;//商品对应的sku
                                 $title = "满足" . get_format_number($arr['nums']) . "元包邮";
                                 $return['title'] = $title;//组装标题
                                 break;
                             } else {
                                 $return['give_goods'] = [];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $return['type'] = $val['type'];//活动标识
                                 $return['id'] = $val['act_id'];//活动ID
                                 //是否优惠同享
                                 if ($arr['is_discount_share'] == 1) {
                                     $return['discount_card'] = $arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon'] = $arr['discount_coupon'];//是否与优惠券同享
                                 } else {
                                     $return['discount_card'] = 0;
                                     $return['discount_coupon'] = 0;
                                 }

                                 $return['status'] = 0;
                                 $return['satisfied_money'] = 0;
                                 $title = "满足" . get_format_number($arr['nums']) . "元包邮" . ' ' . '还差' . get_format_number($arr['nums'] - $goodsSum) . '元';
                                 $return['title'] = $title;//组装标题
                             }
                         } else {//满XX件包邮
                             if ($arr['nums'] <= $goodsNum) {
                                 $return['give_goods'] = [];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $return['type'] = $val['type'];//活动标识
                                 $return['id'] = $val['act_id'];//活动ID
                                 //是否优惠同享
                                 if ($arr['is_discount_share'] == 1) {
                                     $return['discount_card'] = $arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon'] = $arr['discount_coupon'];//是否与优惠券同享
                                 } else {
                                     $return['discount_card'] = 0;
                                     $return['discount_coupon'] = 0;
                                 }

                                 $return['status'] = 1;
                                 $return['satisfied_money'] = 0;
                                 $return['satisfied_skuids'] = $skuIds;//商品对应的sku
                                 $title = "满足" . get_format_number($arr['nums']) . "件包邮";
                                 $return['title'] = $title;//组装标题
                                 break;
                             } else {
                                 $return['give_goods'] = [];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                                 $return['type'] = $val['type'];//活动标识
                                 $return['id'] = $val['act_id'];//活动ID
                                 //是否优惠同享
                                 if ($arr['is_discount_share'] == 1) {
                                     $return['discount_card'] = $arr['discount_card'];//是否与商家会员卡同享
                                     $return['discount_coupon'] = $arr['discount_coupon'];//是否与优惠券同享
                                 } else {
                                     $return['discount_card'] = 0;
                                     $return['discount_coupon'] = 0;
                                 }
                                 $return['status'] = 0;
                                 $return['satisfied_money'] = 0;
                                 $title = "满足" . get_format_number($arr['nums']) . "件包邮" . ' ' . '还差' . get_format_number($arr['nums'] - $goodsNum) . '件';
                                 $return['title'] = $title;//组装标题
                             }
                         }
                     }
                 }
             }
           }
        }
        if(isset($return['satisfied_money'])){
            $return['satisfied_money'] = abs($return['satisfied_money']);//这里应该是正数，你有时候会返回一个负数。。。
        }
        return $return;
    }

    /**
     * @param $arr
     * @param $field
     * @return array
     * 二维数组根据某个值排序
     */
    public function getArrList($arr,$field){
        $b = $arr;
        $a = array();
        foreach($b as $key=>$val){
            $a[] = $val[$field];//这里要注意$val['nums']不能为空，不然后面会出问题
        }
        //$a先排序
        rsort($a);
        $a = array_flip($a);
        $result = array();
        foreach($b as $k=>$v){
            $temp1 = $v[$field];
            $temp2 = $a[$temp1];
            $result[$temp2] = $v;
        }
        //这里还要把$result进行排序，健的位置不对
        ksort($result);
        $result=array_values($result);
        //然后就是你想看到的结果了
        return $result;
    }

    /**
     * @param $store_id 门店id
     * @param $goods_id 商品id
     * @return array
     * title 活动标题
     * type 活动标识
     * id 活动ID
     * @throws \think\Exception
     *
     */
    public function checkGoodsBelongAct($store_id,$goods_id){
        if(empty($goods_id) || empty($store_id)){
            throw new \think\Exception(L_("传入的店铺信息和商品信息不完整"), 1001);
        }
        $goodsIds=array();//商品id
        $goodsIds[]=$goods_id;
        $where[]=[//条件
            ['store_id','=',$store_id],
            ['status','=',1],
            ['start_time','<',time()],
            ['end_time','>=',time()],
            ['type','in',['reached','minus_discount','give','shipping']],
        ];
        //先查询门店是否有活动，再根据活动找出商品，取出商品的交集看是否满足当前活动要求，返回信息
        $return1=(new MallActivity())->getActField($where,$order='sort asc',$field='id,name,act_id,type,act_type');
        $return=array();
        if(!empty($return1)){//查询出不为空
            foreach ($return1 as $key=>$val){
                $where_detail=[
                    ['activity_id','=',$val['id']]
                ];
                $act_goods=(new MallActivityDetail())->getActField($where_detail,$field='goods_id');//找出活动明细的商品信息
                $inter_goods=$this->getArrayInter($act_goods,$goodsIds);//与活动商品有交集的商品
                if(!empty($inter_goods)){//有活动商品
                    //$return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                    $return['type']=$val['type'];//活动标识
                    $return['id']=$val['act_id'];//活动ID
                    $return['title']=$this->getRuleStoreAct($val['type'],$val['act_id']);//组装标题
                    break;
                }else{
                    if($val['act_type']==1) {//是全店
                        //$return['give_goods']=[];//满赠活动时需要   格式如： sku_id:num  例：[1=>1,2=>3]
                        $return['type']=$val['type'];//活动标识
                        $return['id']=$val['act_id'];//活动ID
                        $return['title']=$this->getRuleStoreAct($val['type'],$val['act_id']);//组装标题
                        break;
                    }else{
                        $return=[];
                    }
                }
            }
        }
        return $return;
    }

    /**
     * @param $type 活动类型
     * @param $act_id 活动id
     *根据条件返回活动规则
     */
    public function getRuleStoreAct($type,$act_id){
        $return="";
        switch ($type){
            case 'reached'://N元N件
                //forward_hour
                $where=[['m.type','=','reached'],['r.id','=',$act_id],['m.act_id','=',$act_id]];
                $msg=(new MallReachedAct())->getInfo($where,$field="*");
                $return=get_format_number($msg['money'])."元任选".$msg['nums']."件";
                break;
            case 'shipping'://满包邮
                $where_reach=[['m.type','=','shipping'],['s.id','=',$act_id],['m.act_id','=',$act_id]];
                $arr = (new MallShippingAct())->getInfo($where_reach, $field = '*');//满包邮活动
                if ($arr['full_type'] == 0) {//满XX元包邮
                    $return = "满足" . get_format_number($arr['nums']) . "元包邮";
                }
                else {//满XX件包邮
                    $return = "满足" . get_format_number($arr['nums']) . "件包邮";
                }
                break;
            case 'give'://满赠
                $where_reach=[
                    ['s.id','=',$act_id]
                ];
                $arr=(new MallFullGiveAct())->getInfo($where_reach,$field='*');//查询满赠活动信息
                if($arr['full_type']==1){//1满多少元
                    //$goods_all_price=(new MallGoods())->getSum($where_goods,'price');
                    $where_level=[
                        ['act_id','=',$act_id],
                    ];
                    $field="*";
                    $order="level_money asc";
                    $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                    if(!empty($goods_level)){//满足
                        $title="满".get_format_number($goods_level['level_money'])."送赠品";
                        $return=$title;//组装标题
                    }
                }
                else{//2满多少件
                    $where_level=[
                        ['act_id','=',$act_id],
                    ];
                    $field="*";
                    $order="level_money asc";
                    $goods_level=(new MallFullGiveLevel())->getInfo($where_level,$field,$order);
                    if(!empty($goods_level)){//满足
                        $title="满".get_format_number($goods_level['level_money'])."件送赠品";
                        $return=$title;//组装标题
                    }
                }
                break;
            case 'minus_discount'://满减折
                $where_reach=[['m.type','=','minus_discount'],['s.id','=',$act_id],['m.act_id','=',$act_id]];
                $arr=(new MallFullMinusDiscountAct())->getInfo($where_reach,$field='*');//查询满减折活动信息
                $where_level=[
                    ['act_id','=',$act_id],
                ];
                $field="*";
                $order="level_money asc";
                $goods_level=(new MallFullMinusDiscountLevel())->getInfo($where_level,$field,$order);
                if(!empty($goods_level)){//满足
                    if($arr['is_discount']==0){//满减
                        $title="满".get_format_number($goods_level['level_money'])."元减".get_format_number($goods_level['level_discount']).'元';
                    }else{//满折
                        $title="满".get_format_number($goods_level['level_money'])."元".get_format_number($goods_level['level_discount']).'折';
                    }
                    $return=$title;//组装标题
                }
                break;
            default://每月一次//2月份没有处理
                break;
        }
        return $return;
    }

    /**
     * @param $nowgoods
     * @param $goods_data
     * @return array
     * 取符合活动商品的skuid
     */
    public function getSatisfyGoodsSku($nowgoods,$goods_data){
        $goodskuIds=array();
        for ($j=0;$j<count($nowgoods);$j++){
            for ($i=0;$i<count($goods_data);$i++){
                if($nowgoods[$j]==$goods_data[$i]['goods_id']){
                    $goodskuIds[]=$goods_data[$i]['sku_id'];
                }
            }
        }
        return $goodskuIds;
    }

    public function getGoodsId($goods_data){
        //取传入的商品id
        $goodsIds=array();
        for ($i=0;$i<count($goods_data);$i++){
            $goodsIds[]=$goods_data[$i]['goods_id'];
        }
        return $goodsIds;
    }

    /**
     * @param $arr1
     * @param $arr2
     * @return array
     * 取两数组交集返回
     */
    public function getArrayInter($arr1,$arr2){
        $arr= array_intersect($arr1,$arr2);
        $arr=array_values($arr);
        return $arr;
    }
    /**
     * @param $act_id
     * @param $tid
     * @return bool
     * 获取拼团连接时效状态
     */
    public function checkGroupTimeMag($act_id,$tid){
        $arr= (new MallActivity())->checkGroupTimeMag($act_id,$tid);
        if(empty($arr)){
            $status=1;//超过时间，团购作废
        }else{
//         if($arr['act_status']==0){//未开始
         if($arr['act_start_time'] > time() && $arr['act_end_time'] > time()){//未开始(修改为根据开始结束时间判断)
            return false;//不合理
         }else{//进行中
            if($arr['tid_status']==0){//未成团
                $status= 3;
            }elseif ($arr['tid_status']==1){//成团成功
                $status= 2;
            }else{//超过时间，团购作废
                $status= 1;
            }
         }
        }
        return $status;
    }

    /**
     * @param $act_id 拼团活动id
     * @return mixed
     * 这个活动要多少人成团  2、已经有多少个队伍成团
     */
    //1、这个活动要多少人成团  2、已经有多少个队伍成团
    public function getGroupTeamMasg($act_id){
        if(empty($act_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where = [
            ['s.id','=',$act_id],
            ['t.status','=',1]
        ];
       $res= (new MallNewGroupAct())->getGroupTeamMasg($where);
       $actMsg=(new MallNewGroupAct())->getBase($act_id);
       $result['team_num']=count($res);//成团队伍
       $result['nums']=count($res)*$actMsg['complete_num'];//多少人成团
        return $result;
    }

    /**
     * @param $act_id  周期购活动id
     * @param $order_id 订单id
     * * @param $date 用户选择的配送时间
     * @return bool
     * 支付成功之后周期购生成期数
     */
    public function createPeriodic($act_id,$order_id,$date){
        if(empty($act_id) || empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where = [
            ['s.id','=',$act_id],
        ];
        $msg=(new MallNewPeriodicPurchase())->getInfo($where,$field='s.*');//找出活动内的周期购信息
        if(empty($msg)){
            return false;//活动已经不存在
        }else{
            $data['act_id']=$act_id;
            $data['order_id']=$order_id;
            $data['periodic_type']=1;
            $data['pre_delivery_time']=$msg['forward_hour']+$msg['forward_day']*24;
            //$data['delay_limit']=$msg['delay_limit'];
            $data['freight_type']=$msg['freight_type'];
            $data['buy_limit']=$msg['buy_limit'];
            $data['is_complete']=5;
            switch ($msg['periodic_type']){
                case 1://每日一次
                    //forward_hour,送达时间从当前时间加上备货时间，计算出的时间加一天
                    $time=time()+$msg['forward_hour']*3600+$msg['forward_day']*86400;
                    $time1=strtotime(date('Y-m-d 12:00:00',$time));
                    $add_day=intval((date($msg['forward_hour']*3600+$msg['forward_day']*86400)/86400));
                    $id=0;
                    //$nowhour=date( "H");
                    //$periodic_hour=$nowhour*1+$msg['forward_hour'];
                    if($time>$time1){//大于12点从第二天开始算
                        for($i=1;$i<=$msg['periodic_count'];$i++){//添加活动期数订单
                            $data['periodic_count']=$i;
                            $add=$add_day+$i;
                            $data['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime('+'.$add.' day')));
                            $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                        }
                    }else{//今天开始算
                        for($i=1;$i<=$msg['periodic_count'];$i++){//添加活动期数订单
                            $data['periodic_count']=$i;
                            $add=$add_day+$i-1;
                            $data['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime('+'.$add.' day')));//从当天开始
                            $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                        }
                    }
                    return $id;
                    break;
                case 2://每周一次
                    $weeks=$date;//用户选择的周期
                    $date_time=date('Y-m-d H:i:s',strtotime("+".$msg['forward_day']." day +".$msg['forward_hour']." hour"));//系统设置的周期时间与当前下单时间开始算
                    $week = date('w');
                    if($weeks==0) {
                        $weeks=7;
                    }
                    if(($weeks*1-$week*1)>=0){//当前下单在周期之前或就在当前日期
                        $date_day=date('Y-m-d H:i:s',strtotime("+".($weeks-$week)." day"));//计算最近周期的时间
                        $gap=strtotime($date_day)-strtotime($date_time);//最近周期的时间与设置的备货时间对比
                        if($gap>=0){//在备货时间内，则最近一期算起
                            for($i=1;$i<=$msg['periodic_count'];$i++) {//添加活动期数订单
                                if($i==1){
                                    $data['periodic_date']= strtotime($date_day);
                                }else{
                                    $data['periodic_date']= strtotime(date('Y-m-d H:i:s',strtotime("+".(($weeks-$week)+7*($i-1))." day")));//往后计算周期
                                }
                                $data['periodic_count']=$i;//当前期数
                                $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                            }
                        }
                        else{//在备货时间外，则延长
                            $over=$gap*-1;//与备货时长之差
                                $wek=intval($over/(86400*7));//时间相差几周
                                $wek_mod=($over/86400)%7;//时间相差几周
                                for($i=1;$i<=$msg['periodic_count'];$i++) {//添加活动期数订单
                                    if($wek==0){//在一个星期内则推下个星期开始
                                        $data['periodic_date']= strtotime(date('Y-m-d H:i:s',strtotime("+".(($weeks-$week)+7*$i)." day")));//往后计算周期
                                    }
                                    else{
                                        if($wek_mod==0){//在整周期内
                                            $data['periodic_date']= strtotime(date('Y-m-d H:i:s',strtotime("+".(($weeks-$week)+7*($wek+$i-1))." day")));//往后计算周期
                                        }else{//在整周期之外余几天
                                            $data['periodic_date']= strtotime(date('Y-m-d H:i:s',strtotime("+".(($weeks-$week)+7*($wek+$i))." day")));//往后计算周期
                                        }
                                    }
                                    $data['periodic_count']=$i;//当前期数
                                    $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                                }
                        }
                    }
                    else{//当前下单在周期之后
                        $date_day=date('Y-m-d H:i:s',strtotime("+".(7-($week-$weeks))." day"));//计算最近周期的时间
                        $date_time=date('Y-m-d H:i:s',strtotime("+".$msg['forward_day']." day +".$msg['forward_hour']." hour"));//系统设置的周期时间与当前下单时间开始算
                        $over_time=strtotime($date_time)-strtotime($date_day);
                        if($over_time>0){
                            $wek=intval($over_time/(86400*7));//时间相差几周
                            $wek_mod=($over_time/86400)%7;//时间相差几周零几天
                            for($i=1;$i<=$msg['periodic_count'];$i++) {//添加活动期数订单
                                if($wek==0){//差距在一个星期内则推下个星期开始
                                    $data['periodic_date']= strtotime(date('Y-m-d H:i:s',strtotime("+".((7-($week-$weeks))+7*($wek+$i))." day")));//往后计算周期
                                }
                                else{
                                    if($wek_mod==0){//在整周期内
                                        $data['periodic_date']= strtotime(date('Y-m-d H:i:s',strtotime("+".((7-($week-$weeks))+7*($wek+$i))." day")));//往后计算周期
                                    }else{//在整周期之外余几天
                                        $data['periodic_date']= strtotime(date('Y-m-d H:i:s',strtotime("+".((7-($week-$weeks))+7*($wek+$i))." day")));//往后计算周期
                                    }
                                }
                                $data['periodic_count']=$i;//当前期数
                                $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                            }

                        }else{
                            for($i=1;$i<=$msg['periodic_count'];$i++) {//添加活动期数订单
                                $data['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+".(($week*1-$weeks*1)+7*$i)." day")));
                                $data['periodic_count']=$i;//当前期数
                                $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                            }
                        }
                    }
                    return $id;
                    break;
                default://每月一次//2月份没有处理
                    $nowDay=date( "d");
                    //$nowMon=date( "m");
                    $over_periodic_date=$date-$nowDay*1;
                   /* if($nowMon*1==2){//2月份
                        $firstday = date('Y-m-01', strtotime($date));
                        $lastday = date('d', strtotime("$firstday +1 month -1 day"))*1;
                        if($lastday){

                        }
                    }*/
                    if($over_periodic_date>0){//则最新周期号在定义的周期内
                        //还要考虑备货时间
                        //如果备货时间大于相差的天数，则要推到下周
                        $over_time=strtotime(date('Y-m-d H:i:s',strtotime("+".$over_periodic_date." day")))-time();//相差的天数时间戳
                        $repare_goods_time=$msg['forward_day']*86400+$msg['forward_hour']*3600;//备货时长
                        if($repare_goods_time>$over_time){//备货时间更长推到下个月算
                            for($i=1;$i<=$msg['periodic_count'];$i++) {//添加活动期数订单
                                $data['periodic_date']=strtotime(date('Y-m-'.$date.' H:i:s',strtotime("+".($i)." month")));
                                $data['periodic_count']=$i;//当前期数
                                $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                            }
                        }else{//从本月开始算
                            for($i=1;$i<=$msg['periodic_count'];$i++) {//添加活动期数订单
                                $data['periodic_date']=strtotime(date('Y-m-'.$date.' H:i:s',strtotime("+".($i-1)." month")));
                                $data['periodic_count']=$i;//当前期数
                                $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                            }
                        }
                    }
                    else{//则最新周期号在下个月
                        for($i=1;$i<=$msg['periodic_count'];$i++) {//添加活动期数订单
                            if($nowDay==31){
                                $data['periodic_date']=strtotime(date('Y-m-'.$date.' H:i:s',strtotime("first day of +".($i)." month")));
                            }else{
                                $data['periodic_date']=strtotime(date('Y-m-'.$date.' H:i:s',strtotime("+".($i)." month",1690707656)));
                            }
                            $data['periodic_count']=$i;//当前期数
                            $id=(new MallNewPeriodicPurchaseOrder())->addOne($data);
                        }
                        //2月份递推，30号29天就3月1号送达
                    }
                    return $id;
            }
        }
    }

    /**
     * @param $order_id
     * @throws \think\Exception
     * 支付之后更新周期购订单
     */
    public function afterPayUpdatePeriodic($order_id){
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where=[['order_id','=',$order_id]];
        $data['is_complete']=0;
        (new MallNewPeriodicPurchaseOrder())->_updateData($where,$data);
    }

    /**
     * @param $order_id
     * @throws \think\Exception
     * 取消周期购订单
     */
    public function cancelUpdatePeriodic($order_id){
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where=[['order_id','=',$order_id],['is_complete','in',[0,1]]];
        $data['is_complete']=6;
        (new MallNewPeriodicPurchaseOrder())->_updateData($where,$data);
    }
    /**
     * @param $act_id  周期购活动id
     * @param $nums 购买数量
     * @return bool|int|string
     * 更新周期购销量
     */
    public function updatePeriodicSaleNum($act_id,$nums){
        if(empty($act_id) || empty($nums)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where = [
            ['id','=',$act_id],
        ];
        $arr=(new MallNewPeriodicPurchase())->getInfo($where,$field='*');//取活动信息
        if(empty($arr)){
            return false;//获取信息失败
        }else{
            $data['sale_num']=$arr['sale_num']+$nums;
            $status=(new MallNewPeriodicPurchase())->updatePeriodic($data,$where);
        }
        return $status;
    }
    //周期购订单详情service
    /**
     * @param $order_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * deliver_status 0-待发货 1-备货中 2-已顺延 3-待收货 4-已收货 5已取消
     * period_date 每期执行日期
     *arrive_time 配送完成时间
     * nums 期数
     * now_deliver_status 当期状态 0待发货 1备货中 2已顺延 3待收货 4 已收货 5已取消
     * act_id 周期购活动id
     * purchase_order_id 期数id
     * express_num 快递单号（配送或者完成的才要）
     * express_id 快递公司id
     * express_code 快递公司编码
     * express_name 快递名称
     * 周期购的订单详情展示
     */
    public function getPeriodicOrderDetail($order_id){
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $trade_del=array();
        $trade_del1=array();
        $trade_dels=array();
        $where = [
            ['order_id','=',$order_id],
        ];
        $where1 = [
            ['order_id','=',$order_id],
            ['is_complete','=',2],
        ];
        $where_order = [
            ['order_id','=',$order_id],
            ['periodic_date','>=',time()],
           /* ['is_complete','<',4],//3已完成*/
        ];
        $where5 = [
            ['order_id','=',$order_id],
            ['is_complete','<>',2],
        ];
        $res=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderList($where);//返回周期购期数记录
        $res1=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderList($where1);//返回延期周期购期数记录
        $dates=(new MallNewPeriodicPurchaseOrder())->getNowPeriodicOrder($where_order);//返回最近一期周期购期数记录(送达时间大于或等于当前时间的第一期作为最近的一期)
        $my_last_status=(new MallNewPeriodicPurchaseOrder())->getNowPeriodicOrder($where5,"periodic_date desc");

        $where2 = [
            ['order_id','=',$order_id],
        ];
        $where3 = [
            ['order_id','=',$order_id],
            ['is_complete','in',[3,4]],
        ];

        $where4 = [
            ['order_id','=',$order_id],
            ['is_complete','in',[0,1,3,4,5,6]],
        ];

        $res_all_count=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderList($where4);//已发货周期购记录数
        $res_count=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderList($where3);//已发货周期购记录数
        $delivers1=(new MallNewPeriodicDeliver())->getPeriodicDeliver1($where2);//配送表信息
        $trade_del1['nums']=count($res_count);
        $return['all_nums']=count($res_all_count);
        $return['nums']=$trade_del1['nums'];
        $return['send_time']="";//发货时间
        if(empty($dates)){
            foreach ($res as $key=>$val){
                $trade_del['purchase_order_id']=$val['id'];//每期的主键
                $trade_del['act_id']=$val['act_id'];

                $trade_del['period_date']=date("Y-m-d",$val['periodic_date']);//每期执行日期
                $where1 = [
                    ['purchase_order_id','=',$val['id']],
                ];
                $delivers=(new MallNewPeriodicDeliver())->getPeriodicDeliver($where1);//配送表信息
                if(empty($delivers)){//没有配送信息
                    if($val['is_complete']==0){
                        $trade_del['deliver_status']=0;//待发货
                    }elseif($val['is_complete']==1){
                        $trade_del['deliver_status']=1;//备货中
                    }elseif($val['is_complete']==5){
                        $trade_del['deliver_status']=0;//待发货
                    }elseif($val['is_complete']==2){
                        $trade_del['deliver_status']=2;//已顺延
                    }elseif($val['is_complete']==6){
                        $trade_del['deliver_status']=5;//已取消
                    }elseif($val['is_complete']==3){
                        $trade_del['deliver_status']=3;//店员已发货，未安排骑手
                    }elseif($val['is_complete']==4){
                        $trade_del['deliver_status']=4;//已完成
                    }
                    $trade_del['express_id']='';
                    $trade_del['express_num']='';
                    $trade_del['express_code']='';
                    $trade_del['express_name']='';
                }else{
                    $trade_del['express_id']=$delivers['express_id'];
                    $trade_del['express_num']=$delivers['express_num'];
                    $trade_del['express_code']=$delivers['express_code'];
                    $trade_del['express_name']=$delivers['express_name'];
                    if($delivers['status']==0){//配送中
                        $trade_del['deliver_status']=3;//待收货（确认收货）
                    }else{
                        $trade_del['arrive_time']=date("Y-m-d H:i:s",$delivers['arrive_time']);//每期执行日期
                        $trade_del['deliver_status']=4;//配送完成（已收货）
                        /*$where_deliver=[
                               ['purchase_order_id','=',$val['id']]
                            ];
                        $deliver=(new MallNewPeriodicDeliver())->getPeriodicDeliver($where_deliver);*/
                        $trade_del['deliver_code']=isset($delivers['express_number'])?$delivers['express_number']:'';
                    }
                }
                $trade_del['order_id']=$order_id;
                $trade_dels[]=$trade_del;
            }

            if($my_last_status['is_complete']==0){
                $trade_del1['now_deliver_status']=0;//当期状态 0待发货 1备货中 2已顺延 3待收货 4 已收货 5已退款
            }elseif($my_last_status['is_complete']==1){
                $trade_del1['now_deliver_status']=1;
            }elseif($my_last_status['is_complete']==2){
                $trade_del1['now_deliver_status']=2;
            }elseif($my_last_status['is_complete']==3){
                $trade_del1['now_deliver_status']=3;
            }elseif($my_last_status['is_complete']==4){
                $trade_del1['now_deliver_status']=4;
            }elseif($my_last_status['is_complete']==6){
                $trade_del1['now_deliver_status']=5;//已取消
            }

            $return['list']=$trade_dels;
            if(isset($trade_del1['now_deliver_status'])){
                $return['now_deliver_status']=$trade_del1['now_deliver_status'];
            }
            return $return;//数据获取失败，无数据
        }else{
            if(empty($delivers1)){//配送表没信息
                /*if($dates['is_complete']==0){
                    $trade_del1['now_deliver_status']=0;//当期状态 0待发货 1备货中 2已顺延 3待收货 4 已收货 5已退款
                }elseif($dates['is_complete']==1){
                    $trade_del1['now_deliver_status']=1;
                }elseif($dates['is_complete']==2){
                    $trade_del1['now_deliver_status']=2;
                }elseif($dates['is_complete']==6){
                    $trade_del1['now_deliver_status']=5;//已取消
                }*/
                if($dates['is_complete']==0){
                    $trade_del1['now_deliver_status']=0;//当期状态 0待发货 1备货中 2已顺延 3待收货 4 已收货 5已退款
                }elseif($dates['is_complete']==1){
                    $trade_del1['now_deliver_status']=1;
                }elseif($dates['is_complete']==2){
                    $trade_del1['now_deliver_status']=2;
                }elseif($dates['is_complete']==3){
                    $trade_del1['now_deliver_status']=3;
                }elseif($dates['is_complete']==4){
                    $trade_del1['now_deliver_status']=4;
                }elseif($dates['is_complete']==6){
                    $trade_del1['now_deliver_status']=5;//已取消
                }
            }else{//有信息走配送表状态
                //$trade_del1['nums']=$delivers1['deliver_num'];
                $return['send_time']=$delivers1['add_time'];
                if($delivers1['status']==0) {
                    $trade_del1['now_deliver_status']=3;
                }else{
                    $trade_del1['now_deliver_status']=4;
                }
            }
        }
        if(empty($res)){
            throw new \think\Exception(L_("数据获取失败，无数据"), 1001);
        }else{
            //每期状态
             foreach ($res as $key=>$val){
                 $trade_del['purchase_order_id']=$val['id'];//每期的主键
                 $trade_del['act_id']=$val['act_id'];

                 $trade_del['period_date']=date("Y-m-d",$val['periodic_date']);//每期执行日期
                 $where1 = [
                     ['purchase_order_id','=',$val['id']],
                 ];
                 $delivers=(new MallNewPeriodicDeliver())->getPeriodicDeliver($where1);//配送表信息
                 if(empty($delivers)){//没有配送信息
                       if($val['is_complete']==0){
                           $trade_del['deliver_status']=0;//待发货
                       }elseif($val['is_complete']==1){
                           $trade_del['deliver_status']=1;//备货中
                       }elseif($val['is_complete']==5){
                           $trade_del['deliver_status']=0;//待发货
                       }elseif($val['is_complete']==2){
                           $trade_del['deliver_status']=2;//已顺延
                       }elseif($val['is_complete']==6){
                           $trade_del['deliver_status']=5;//已取消
                       }elseif($val['is_complete']==3){
                           $trade_del['deliver_status']=3;//店员已发货，未安排骑手
                       }elseif($val['is_complete']==4){
                           $trade_del['deliver_status']=4;//已完成
                       }
                     $trade_del['express_id']='';
                     $trade_del['express_num']='';
                     $trade_del['express_code']='';
                     $trade_del['express_name']='';
                 }else{
                     $trade_del['express_id']=$delivers['express_id'];
                     $trade_del['express_num']=$delivers['express_num'];
                     $trade_del['express_code']=$delivers['express_code'];
                     $trade_del['express_name']=$delivers['express_name'];
                    if($delivers['status']==0){//配送中
                        $trade_del['deliver_status']=3;//待收货（确认收货）
                    }else{
                        $trade_del['arrive_time']=date("Y-m-d H:i:s",$delivers['arrive_time']);//每期执行日期
                        $trade_del['deliver_status']=4;//配送完成（已收货）
                        /*$where_deliver=[
                               ['purchase_order_id','=',$val['id']]
                            ];
                        $deliver=(new MallNewPeriodicDeliver())->getPeriodicDeliver($where_deliver);*/
                        $trade_del['deliver_code']=isset($delivers['express_number'])?$delivers['express_number']:'';
                    }
                 }
                 $trade_del['order_id']=$order_id;
                 $trade_dels[]=$trade_del;
             }

        }
        $return['list']=$trade_dels;
        if(isset($trade_del1['now_deliver_status'])){
            $return['now_deliver_status']=$trade_del1['now_deliver_status'];
        }
        return $return;
    }

    /**
     * @param $order_id
     * @return mixed
     * @throws \think\Exception
     * 返回拼团订单下的团队结束时间戳
     */
    public function getGroupOrderAndTime($order_id){
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $arrs=(new MallNewGroupOrderService())->getOrderMsg($order_id);
        return $arrs['end_time'];
    }

    /**
     * @param $order_id
     * @return bool
     * @author mrdeng
     * 返回除去退款订单的最后一期状态,false为全部是退款订单
     * 周期购子订单状态：0待发货 1备货中 3已发货 4已收货 5待支付 6已退款
     */
    public function getPeriodicOrderStatus($order_id,$arr=[]){
        /**
         * 1/ 获取该订单的所有子订单
         * 2/ 排除掉arr中的子订单
         * 3/ 余下的子订单，遍历状态
         * 4/   a.如果3中出现代发货，  return 当前这笔订单就应该改成改成代发货10
         *      b.如果3中没有代发货，但出现备货中， return 当前这笔订单应该改成备货中11
         *      c.如果ab都没出现，但出现了已发货， return 当前这笔订单应该改成已发货20
         *      d.如果abc都没出现，说明这步操作为当前母订单的最终操作 return 由业务调用者来改最终状态0
         */

        $where = [
            ['order_id','=',$order_id],
            ['id','not in',$arr],
        ];
        $order_status=0;
        $status=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderList($where);
        if(empty($status)){
           return false;//无记录
        }else{
            $my_status=array();
            foreach ($status as $key=>$val){
                $my_status[$val['is_complete']]=$val['is_complete'];
            }
            array_values($my_status);
            if(in_array(0,$my_status)){
                return  $order_status=10;//待发货
            }elseif (in_array(1,$my_status)){
                return $order_status=11;//备货中
            }elseif (in_array(3,$my_status)){
                return $order_status=20;//已发货
            }else{
                return $order_status=0;//都没有
            }
        }
    }
    /**
     * @param $order_id 订单id
     * @return array
     * 获取当前订单最近的一期及活动数据
     * $status 1全部 3待发货 4已发货 5已完成
     * is_complete 0待发货 1备货中 2已顺延 3已发货 4已完成
     */

    public function returnNowPeriodicOrderAndActBefore($order_id,$status,$source='wap'){
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $res1=array();
        $where1=array();
        $where_order[] = ['m.order_id','=',$order_id];
        $res=[];
        $where_del[]=['s.order_id','=',$order_id];
        $where_del[]= ['s.is_complete','<',5];//全部
        $deliver=(new MallNewPeriodicPurchaseOrder())->getPeriodicNowNoCompletePurchase($where_del);
        $new_time=time()+$deliver['forward_day']*86400+$deliver['forward_hour']*3600;
        if($status==1){
            $where_order[]= ['m.is_complete','<>',2];//全部
            $where_order[]= ['m.is_complete','<>',5];//全部
            $where_order[]= ['m.is_complete','<>',6];//全部
            $where_order[] = ['m.periodic_date','<=',$new_time];//待发货状态
        }
        elseif($status==3){//待发货
            if(!empty($deliver)){
                //当前时间加上备货时间<订单的期望到达时间,状态is_complete=0，
                $where_order[] = ['m.order_id','=',$order_id];
                $where_order[] = ['m.is_complete','<',2];
                $where_order[] = ['m.periodic_date','>=',$new_time];//待发货状态
                $where1[0]=time();
                $where1[1]=$new_time;

                //$new_time1=time()+$deliver['forward_day']*86400+$deliver['forward_hour']*3600;
                $where_order1[] = ['m.order_id','=',$order_id];
                $where_order1[] = ['m.is_complete','<',2];
               // $where[] = ['a.start_time','',array('between', "$startTime,$endTime")];
                if($source=='staff'){
                    $res=(new MallNewPeriodicPurchase())->getPeriodicPurchaseAndOrderMall($where_order1);//存在到达发货时间还没有发货的订单，合并一起返回
                }else{
                    $where_order1[] = ['m.periodic_date','<',$new_time];//待发货状态
                    $res1=(new MallNewPeriodicPurchase())->getPeriodicPurchaseAndOrderMall($where_order1);//存在到达发货时间还没有发货的订单，合并一起返回
                }
            }else{
                return [];
            }
        }
        elseif ($status==4){
            if($source!='staff') {
                $where_order[] = ['m.is_complete', '=', 3];//已发货
                $where_order[] = ['m.periodic_date', '<=', $new_time];//待发货状态
            }else{
                $where_order[] = ['m.is_complete', '=', 3];//已发货
            }
        }
        elseif($status==5){
            $where_order[] = ['m.is_complete','=',4];//已完成
        }
        else{
            return [];
        }

        if($source=='staff' && ($status==4 || $status==5)){
            $res = (new MallNewPeriodicPurchase())->getPeriodicPurchaseAndOrder($where_order, $where1, $status);//获取当前订单最近的一期及活动数据
        }

        if($source!='staff') {
            $res = (new MallNewPeriodicPurchase())->getPeriodicPurchaseAndOrder($where_order, $where1, $status);//获取当前订单最近的一期及活动数据
        }

        if(!empty($res1) && $source!='staff'){//存在到达发货时间还没有发货的订单，合并一起返回
             $res=array_merge($res,$res1);
        }
        if(empty($res)){
            $res=[];
        }
        else{
            foreach ($res as $key=>$val){
                if($val['is_complete']==0){//0待发货
                    $res[$key]['is_complete']=10;
                    $res[$key]['is_complete_txt']='待发货';
                }elseif($val['is_complete']==1){//1备货中
                    $res[$key]['is_complete']=11;
                    $res[$key]['is_complete_txt']='备货中';
                }elseif($val['is_complete']==3){//3已发货
                    $res[$key]['is_complete']=20;
                    $res[$key]['is_complete_txt']='已发货';
                }elseif($val['is_complete']==4){//4已收货
                    $res[$key]['is_complete']=30;
                    $res[$key]['is_complete_txt']='已收货';
                }else{//顺延期但是在待发货时间内给展示
                    $prepareGoodsTime=time()+ $val['forward_day']*86400+$val['forward_hour']*3600;
                    if($prepareGoodsTime>=$val['periodic_date']){//顺延后在备货时间内
                        $res[$key]['is_complete']=10;//顺延不展示
                    }else{
                        unset($res[$key]);
                    }
                }
            }
            $res=array_values($res);
        }
        return $res;
    }
    /**
     * @param $order_id 订单id
     * @return array
     * 获取当前订单最近的一期及活动数据
     * $status 1全部 3待发货 4已发货 5已完成
     * is_complete 0待发货 1备货中 2已顺延 3已发货 4已完成
     */

    public function returnNowPeriodicOrderAndAct($order_id){
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where_order[] = ['m.order_id','=',$order_id];
        $res=(new MallNewPeriodicPurchase())->getPeriodicPurchaseAndOrder($where_order);//获取当前订单最近的一期及活动数据
        if(empty($res)){
            $res=[];
        }else{
            foreach ($res as $key=>$val){
                $where_order1 = [
                    ['purchase_order_id','=', $val['purchase_order_id']],
                ];

                if($val['is_complete']==0){
                    $res[$key]['is_complete']=10;
                }elseif($val['is_complete']==1){
                    $res[$key]['is_complete']=11;
                }elseif($val['is_complete']==3){
                    $res[$key]['is_complete']=20;
                }elseif($val['is_complete']==4){
                    $res[$key]['is_complete']=40;
                }elseif($val['is_complete']==2){
                    $prepareGoodsTime=time()+ $val['forward_day']*86400+$val['forward_hour']*3600;
                    if($prepareGoodsTime>=$val['periodic_date']){//顺延后在备货时间内
                        $res[$key]['is_complete']=10;//顺延不展示
                    }else{
                        unset($res[$key]);
                    }
                }

                $myarr=(new MallNewPeriodicDeliver())->getPeriodicDeliver($where_order1);
                if(!empty($myarr)){
                    if($myarr['status']==0){
                        $res[$key]['is_complete']=20;//已发货
                    }else{
                        $res[$key]['is_complete']=40;//已完成
                    }
                }
            }
            $res=array_values($res);
        }
        return $res;
    }

    /**
     * @param $order_id 周期购每期订单详情。每期记录id
     * @return array
     * "purchase_order_id": 92, 当期记录的id
     * "act_id": 26, 周期购活动id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *
     */
    public function returnNowPeriodicOrderDetail($order_id){
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where_order = [
            [' m.id','=',$order_id],
            [' m.is_complete','<',5],
        ];
        $res=(new MallNewPeriodicPurchase())->getPeriodicPurchaseAndOrderDetail($where_order);//获取当前订单最近的一期及活动数据
        if(empty($res)){
            $res=[];
        }else{
            $where_order1 = [
                ['purchase_order_id','=', $res['purchase_order_id']],
            ];
            $myarr=(new MallNewPeriodicDeliver())->getPeriodicDeliver($where_order1);
            if(!empty($myarr)){
                $res['is_complete']=20;
            }

            if($res['is_complete']==0){
                $res['is_complete']=10;
                $res['is_complete_txt']='待发货';
            }elseif($res['is_complete']==1){
                $res['is_complete']=11;
                $res['is_complete_txt']='备货中';
            }elseif($res['is_complete']==2){
                $res['is_complete']=12;
                $res['is_complete_txt']='已顺延';
            }elseif($res['is_complete']==3){
                $res['is_complete']=20;
                $res['is_complete_txt']='已发货';
            }elseif($res['is_complete']==4){
                $res['is_complete']=30;
                $res['is_complete_txt']='已收货';
            }
        }
        $res['deliver'] = $myarr;
        return $res;
    }
    /**
     * 周期购增加配送记录
     * type 0 店员发货（配送中） 1配送员接单 2确认收货
     * deliver_type 配送类型 0-快递配送，1-平台配送
     * deliver_uid 配送员id
     * express_num 配送单号
     * periodic_id 当期的id
     * deliver_num期数
     * express_id 快递公司id
     * express_name 快递名称
     * express_code 快递公司编码
     */
    public function addPeriodicDeliver($order_id,$periodic_id,$type,$express_num,$deliver_num,$deliver_type,$deliver_uid=0,$express_name="",$express_id='',$express_code){
        if(empty($periodic_id) || empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $data['status']=$type;
        if($type==0){
            if(empty($deliver_num)){
                $where[]=['id','=',$periodic_id];
                $msg=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrder($where);
                $data['deliver_num']=$msg['periodic_count'];
            }else{
                $data['deliver_num']=$deliver_num;
            }
            $data['order_id']=$order_id;
            $data['add_time']=time();
            $data['purchase_order_id']=$periodic_id;
            $data['express_num']=$express_num;
            $data['express_id']=$express_id;
            $data['express_name']=$express_name;
            $data['deliver_type']=$deliver_type;
            $data['express_code']=$express_code;
            $id=(new MallNewPeriodicDeliver())->addPeriodicDeliver($data);//创建配送记录
        }
        elseif($type==1){
             $where=[
                 ['purchase_order_id','=',$periodic_id]
             ];
            $data['deliver_uid']=$deliver_uid;
            $data['accept_time']=time();
            $id=(new MallNewPeriodicDeliver())->updatePeriodicDeliver($where,$data);//更新配送信息
        }else{
            $where=[
                ['purchase_order_id','=',$periodic_id]
            ];
            $data['arrive_time']=time();
            $id=(new MallNewPeriodicDeliver())->updatePeriodicDeliver($where,$data);//更新配送信息
        }
       return $id;
    }

    /**
     * @param $order_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 更新周期购订单，用户确认收货或者计划任务确认收货
     */
    public function updateSurePeriodic($order_id){
        $where=[
            ['order_id','=',$order_id]
        ];
        $data_order['is_complete']=4;

        $data_dev['status']=1;
        $data_dev['arrive_time']=time();
        (new MallNewPeriodicPurchaseOrder())->_updateData($where,$data_order);
        (new MallNewPeriodicDeliver())->updatePeriodicDeliver($where,$data_dev);
        (new MallOrderService())->changeOrderStatus($order_id,30,'周期购订单确认收货');//确认收货日志
    }
    /**
     * @param $periodic_order_id  周期购期数记录id
     * @param $order_id 订单id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 周期购顺延service
     * $type操作标志 1为店员端 0默认用户端
     */

    public function periodicDelay($periodic_order_id,$order_id,$type=0){
       /* try{*/
        if(empty($periodic_order_id) || empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where1=[//周期购当期的信息
            ['id','=',$periodic_order_id],
        ];
        $res=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrder($where1);//当前期信息
        $where = [//周期购活动的信息
            ['s.id','=',$res['act_id']],
        ];
        $where_num=[
            ['order_id','=',$order_id],
            ['is_complete','=',2],
        ];
        $msg=(new MallNewPeriodicPurchase())->getInfo($where);//周期购活动的信息
        $forward_day=$msg['forward_day'];//备货天数
        $forward_hour=$msg['forward_hour'];//备货小时
        //$repare_goods_time=strtotime(date('Y-m-d H:i:s',strtotime('+'.$forward_day.'day '.$forward_hour.'hour')));//当前时间加上备货时间
        $repare_goods_time=$res['periodic_date']-$forward_day*86400-$forward_hour*3600;//送达时间减去备货时间跟当前时间对比
        $limit_num=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderNum($where_num);//周期购延期数

        if($type==1){//店员端延期
            if(time()>$res['periodic_date']){
                throw new \think\Exception(L_("当前时间不可延期"), 1001);
            }
        }else{
            if($msg['delay_limit']==$limit_num){
                throw new \think\Exception(L_("已达到最多限制期数,不可顺延"), 1001);
            }
            if(time()>$repare_goods_time){
                throw new \think\Exception(L_("当前时间不可延期"), 1001);
            }
        }

        $where2=[//周期购查询期数条件,排除当前期，当前期需要新增一条新数据
            ['periodic_date','>=',$res['periodic_date']],
            ['order_id','=',$order_id],
            ['is_complete','=',0],
        ];
        $res1=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderList($where2);//周期购查询符合条件期数

        if(empty($res) || empty($res1)){//数据异常错误
            throw new \think\Exception(L_("查询不到数据，异常"), 1001);
        }
        switch ($msg['periodic_type']) {
            case 1://每日
                       foreach ($res1 as $key=>$val){
                           $updateData['is_complete']=$val['is_complete'];
                           if($key==0){
                               $updateData['is_complete']=2;
                               //顺延期数新增一条新的记录，当前期是顺延状态
                               $res['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+".($key+1)." day",$val['periodic_date'])));//时间加一天
                               unset($res['id']);
                               array_values($res);
                               (new MallNewPeriodicPurchaseOrder())->addOne($res);

                               $where3=[//周期购查询期数条件
                                   ['id','=',$val['id']],
                               ];
                               //$updateData['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+".($key+1)." day",$val['periodic_date'])));//时间加一天
                               (new MallNewPeriodicPurchaseOrder())->getPeriodicOrderUpdate($where3,$updateData);
                           }else{
                               $where3=[//周期购查询期数条件
                                   ['id','=',$val['id']],
                               ];
                               $updateData['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+1"." day",$val['periodic_date'])));//时间加一天
                               (new MallNewPeriodicPurchaseOrder())->getPeriodicOrderUpdate($where3,$updateData);
                           }
                       }
                       $msg1['periodic_count']=$res['periodic_count'];
                       return $msg1;
                break;
            case 2://每周
                foreach ($res1 as $key=>$val){
                    $updateData['is_complete']=$val['is_complete'];
                    if($key==0){
                        $updateData['is_complete']=2;

                        $res['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+".($key+1)." week",$val['periodic_date'])));//时间加一周
                        unset($res['id']);
                        array_values($res);
                        (new MallNewPeriodicPurchaseOrder())->addOne($res);
                        //$updateData['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+1 week",$val['periodic_date'])));//时间加一周
                        $where3=[//周期购查询期数条件
                            ['id','=',$val['id']],
                        ];
                        (new MallNewPeriodicPurchaseOrder())->getPeriodicOrderUpdate($where3,$updateData);
                    }else{
                        $updateData['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+1 week",$val['periodic_date'])));//时间加一周
                        $where3=[//周期购查询期数条件
                            ['id','=',$val['id']],
                        ];
                        (new MallNewPeriodicPurchaseOrder())->getPeriodicOrderUpdate($where3,$updateData);
                    }
                    //$updateData['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+".($key+1)." week",$val['periodic_date'])));//时间加一周
                }
                $msg1['periodic_count']=$res['periodic_count'];
                return $msg1;
                break;
            default://每月
                foreach ($res1 as $key=>$val){
                    $updateData['is_complete']=$val['is_complete'];
                    if($key==0){
                        $updateData['is_complete']=2;

                        //$res['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+".($key+1)." month",$val['periodic_date'])));//时间加一月
                        $res['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+1 month",$val['periodic_date'])));//时间加一月
                        unset($res['id']);
                        array_values($res);
                        (new MallNewPeriodicPurchaseOrder())->addOne($res);

                        $where3=[//周期购查询期数条件
                            ['id','=',$val['id']],
                        ];
                        $arr=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderUpdate($where3,$updateData);
                    }else{
                        $updateData['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime("+1 month",$val['periodic_date'])));//时间加一月
                        $where3=[//周期购查询期数条件
                            ['id','=',$val['id']],
                        ];
                        $arr=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderUpdate($where3,$updateData);
                    }

                }
               /* if($arr===false){
                    $msg1=false;
                }else{*/
                    $msg1['periodic_count']=$res['periodic_count'];
                /*}*/
                return $msg1;
                break;
        }
      /*  }catch (\Exception $e){
           dd($e);
        }*/
    }

    /**
     * @param $periodic_order_id 每期的id
     * @param $order_id  订单id
     * @param $act_id
     * @return int
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 周期购确认收货--操作订单更新周期购记录
     */

    public function periodicGetGoods($periodic_order_id,$order_id){
        if(empty($periodic_order_id) || empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where = [//周期购当前期信息id
            ['id','=',$periodic_order_id],
        ];

        $updateData['is_complete']=4;

        $where_order_nums = [//完成周期购次数
            ['is_complete','=',4],
            ['order_id','=',$order_id],
        ];
        $msg=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrder($where);//查询当期信息

        $arr=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderUpdate($where,$updateData);//更新当期信息

        $where_nums = [//完成周期购次数
            ['id','=',$msg['act_id']],
        ];
        $num1=(new MallNewPeriodicPurchase())->getPeriodicCount($where_nums);//找到活动的总期数信息

        $num2=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderNum($where_order_nums);//找到完成配送的期数
        if($arr){
            if($num1==$num2){//如果完成则当前所有期数都完结
                $act_msg=(new MallNewPeriodicPurchase())->getInfo($where_nums,$field='*');
                $act['sale_num']=$act_msg['sale_num']+1;

                (new MallNewPeriodicPurchase())->updatePeriodic($act,$where_nums);
                return 1;//所有期数完结

            }else{
                return 0;//所有期数有未完结
            }
        }else{
            throw new \think\Exception(L_("确认收货失败,更新异常"), 1001);
        }
    }

    /**
     * @param $periodic_order_id 每期的id
     * @param $status 1店员接单 2 店员顺延 3店员发货
     * 店员端更新周期购当期配送记录信息
     */

    public function updatePeriodicOrder($periodic_order_id,$status){
        if(empty($periodic_order_id) || empty($status)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where=[
            ['id','=',$periodic_order_id]
        ];
        $data['is_complete']=$status;
        $msg=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderUpdate($where,$data);
        if($msg===false){
           return false;
        }else{
            return true;
        }
    }

    /**
     * @param $where
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 周期购--修改当期配送信息
     */
    public function updatePeriodicDeliver($where,$data){
        $msg=(new MallNewPeriodicDeliver())->updatePeriodicDeliver($where,$data);
        if($msg===false){
            return false;
        }else{
            return true;
        }
    }
    //预售新增预售订单记录
    /**
     * @param $tid
     * @param $order_id //订单id
     * @param $goods_id //商品id
     * @param $act_id //预售活动id
     * @param $sku_id //商品规格id
     * @param $bargain_price //定金
     * @param $rest_price //尾款
     * @return mixed
     * 预售下单新增
     */
    public function saveOrderUpdatePrepareMsg($order_id,$act_id,$goods_id,$sku_id,$bargain_price,$rest_price,$uid){
        if(empty($order_id) || empty($act_id) || empty($goods_id) || empty($sku_id)){
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $data['order_id']=$order_id;
        $data['act_id']=$act_id;
        $data['goods_id']=$goods_id;
        $data['sku_id']=$sku_id;
        $data['bargain_price']=$bargain_price;
        $data['rest_price']=$rest_price;
        $data['uid']=$uid;
        return (new MallPrepareOrder())->addPrepare($data);
    }
    /**
     * @param $order_id
     * @param $pay_status 支付类型 0未支付 1定金支付 2尾款支付
     * @return mixed
     * 支付完成更新预售状态
     */
    public function afterPayUpdatePrepareMsg($order_id){
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where[]=['order_id','=',$order_id];
        $msg=(new MallPrepareOrder())->getOne($where,'*');
        if($msg['pay_status']==0){
            $data['pay_status']=1;
            $order_sku_msg = (new MallOrderDetail())->getOne($order_id);
            if (!empty($order_sku_msg)) {
                $where1 = [['act_id', '=', $msg['act_id']], ['sku_id', '=', $order_sku_msg['sku_id']], ['goods_id', '=', $order_sku_msg['goods_id']]];
                $act_sku_msg = (new MallPrepareActSku())->getBySkuId($where1);
                $act_stock_num['bargain_sale_num'] = $act_sku_msg['bargain_sale_num'] + 1;//支付定金单数
                (new MallPrepareActSku())->updateOne($where1, $act_stock_num);
            }
            $actInfo = (new MallPrepareAct())->getInfo([['s.id', '=', $msg['act_id']]]);
            //定金支付成功后支付尾款的推送消息
            (new SendTemplateMsgService())->sendWxappMessage(['type' => 'prepare_rest_pay', 'order_id' => $order_id, 'rest_type' => $actInfo['rest_type'], 'rest_start_time' => $actInfo['rest_start_time'], 'rest_end_time' => $actInfo['rest_end_time'], 'goods_name' => $order_sku_msg['name'], 'rest_money' => $msg['rest_price']]);
        }elseif ($msg['pay_status']==1) {
                $data['pay_status'] = 2;
                $order_sku_msg = (new MallOrderDetail())->getOne($order_id);
                if (!empty($order_sku_msg)) {
                    $where1 = [['act_id', '=', $msg['act_id']], ['sku_id', '=', $order_sku_msg['sku_id']], ['goods_id', '=', $order_sku_msg['goods_id']]];
                    $act_sku_msg = (new MallPrepareActSku())->getBySkuId($where1);
                    if ($act_sku_msg['act_stock_num'] != -1 && $act_sku_msg['act_stock_num'] != 0) {
                        $act_stock_num['act_stock_num'] = $act_sku_msg['act_stock_num'] - 1;//更新库存
                    }
                    $act_stock_num['rest_sale_num'] = $act_sku_msg['rest_sale_num'] + 1;//支付尾款单数
                    (new MallPrepareActSku())->updateOne($where1, $act_stock_num);
                }
        }
        $data['pay_time']=time();
        return (new MallPrepareOrder())->updatePrepare($data,$where);
    }

    /**
     * @param $goods_id 商品id
     * @param $uid 用户id
     * @return array
     * order_id 订单id
     * act_end_time 尾款支付时间
     * pay_status 支付类型 0未支付 1定金支付 2尾款支付
     * pay_time支付时间
     * send_goods_date 发货时间
     * bargain_price 定金
     * rest_price 尾款
     * bargain_price_status 定金支付状态 0未支付 1已支付
     * rest_price_status  尾款支付状态 0未支付 1已支付
     * @throws \think\Exception
     * 用户查询当前预售商品处于什么预售状态（预售订单详情）
     */
    public function getPrepareOrderStatus($goods_id,$uid,$act_id,$order_id=0){
        if(empty($goods_id)|| empty($uid)|| empty($act_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where[]=['m.goods_id','=',$goods_id];//商品id
        if($order_id>0){
            $where[]=['s.order_id','=',$order_id];//商品id
        }
        $where[]=['s.act_id','=',$act_id];//活动ID
        $where[]=['s.uid','=',$uid];//用户id
        //$where[]=['s.second_pay','=',0];//第二次支付状态
        $msg=(new MallPrepareOrder())->getInfo($where,$field="*");
        $res=array();
        if(!empty($msg)){
                $res['order_id']=$msg['order_id'];//支付时间
                $res['pay_status']=$msg['pay_status'];//支付类型 0未支付 1定金支付 2尾款支付 3定金已支付但取消
                $res['start_time']=0;
                $res['end_time']=0;
                $where1[]=['s.id','=',$msg['act_id']];//活动id
                $datas=(new MallPrepareAct())->getInfo($where1,$field='*');//查询活动信息
                if ($res['pay_status']==1){//定金支付,返回尾款范围
                    if(!empty($datas)){
                        $res['rest_type']=$datas['rest_type'];//'尾款支付时间类型 0=固定时间 1=定金支付后',
                        //尾款支付时间
                        if($datas['rest_type']==0){//0=固定时间
                            $res['act_end_time']=$datas['rest_end_time']-time();//剩余秒自己换算,固定XX天XX时XX分支付
                            $res['start_time']=$datas['rest_start_time'];
                            $res['end_time']=$datas['rest_end_time'];
                            if($msg['second_pay']==1 && (($msg['second_pay_time']+15*60) > $datas['rest_end_time'])){
                                $res['end_time']=$datas['rest_end_time'] = $msg['second_pay_time']+15*60;
                            }
                        }else{
                            $res['act_end_time']=($msg['pay_time']+$datas['rest_start_time']+$datas['rest_end_time'])-time();//定金支付后xx天xx时xx分内支付尾款
                            $res['start_time']=$msg['pay_time'];
                            $stoptime = $res['end_time']=$msg['pay_time']+$datas['rest_start_time']+$datas['rest_end_time'];
                            if($msg['second_pay']==1 && (($msg['second_pay_time']+15*60) > $stoptime)) {
                               $res['end_time'] = $msg['second_pay_time']+15*60;
                            }
                        }
                    }else{
                        throw new \think\Exception(L_("活动已过期"), 1001);
                    }
                }
            $res['pay_time']=$msg['pay_time'];//支付时间
            //发货时间
            if($datas['send_goods_type']==1){//固定某一天发货,时间戳
                $res['send_goods_date']=$datas['send_goods_date'];
            }else{//尾款支付后多少天发货，时间戳
                $res['send_goods_date']=strtotime(date('Y-m-d H:i:s',strtotime('+'.$datas['send_goods_days'].' day')));
            }
            $res['bargain_price']=$msg['bargain_price'];//定金
            $res['rest_price']=$msg['rest_price'];//尾款

            $res['second_pay']=$msg['second_pay'];
            $res['sku_id']=$msg['sku_id'];

            if($res['pay_status']==0){//支付类型 0未支付 1定金支付 2尾款支付
                $res['bargain_price_status']=0;//定金支付状态 0未支付 1已支付
                $res['rest_price_status']=0;//尾款支付状态 0未支付 1已支付
            }elseif ($res['pay_status']==1 || $res['pay_status']==3){
                $res['bargain_price_status']=1;
                $res['rest_price_status']=0;
            }else{
                $res['bargain_price_status']=1;
                $res['rest_price_status']=1;
            }
        }
        return $res;
    }


    /**
     * @param $order_id 订单号
     * @return array
     * 订单号查看预售订单详情
     */
    public function getPrepareOrderDetail($order_id){
        $where[]=['order_id','=',$order_id];//商品id
        $res=(new MallPrepareOrder())->getOne($where,$field='*');
        if(!empty($res)){
           $res['prepare_status']=1; //是预售订单
        }else{
           $res['prepare_status']=0; //不是预售订单
        }
        return $res;
    }

    /**
     * @param $order_id 订单id
     * @throws \think\Exception
     * 预售订单定金支付后超时取消订单
     */
    public function getPrepareOrderRefund($order_id)
    {
        if(empty($order_id)){//数据异常错误
            throw new \think\Exception(L_("参数不完整"), 1001);
        }
        $where1[] = ['m.order_id', '=', $order_id];
        $result = (new MallPrepareAct())->getPrepare($where1);
        if(!empty($result)){
            foreach ($result as $key=>$val){
                if($val['second_pay']==0) {//没点击支付尾款
                    if($val['rest_type']==0){//0=固定时间
                        if($val['rest_end_time']<time()){
                            (new MallOrderService())->changeOrderStatus($val['order_id'],51,'预售未支付尾款超时取消（订单详情页取消）');
                            $data['pay_status']=3;
                            $where=[['order_id','=',$val['order_id']]];
                            (new MallPrepareOrder())->updatePrepare($data,$where);
                        }
                    }
                    else{
                        $act_end_time=($val['pay_time']+$val['rest_start_time']+$val['rest_end_time']);//定金支付后xx天xx时xx分内支付尾款
                        if($act_end_time<time()) {
                            (new MallOrderService())->changeOrderStatus($val['order_id'],51,'预售未支付尾款超时取消（订单详情页取消）');
                            $data['pay_status']=3;
                            $where=[['order_id','=',$val['order_id']]];
                            (new MallPrepareOrder())->updatePrepare($data,$where);
                        }
                    }
                }else{
                    if((time() - $val['second_pay_time'])/60>=15){
                        (new MallOrderService())->changeOrderStatus($val['order_id'],51,'预售未支付尾款超时取消(订单详情页取消)');
                        $data['pay_status']=3;
                        $where=[['order_id','=',$val['order_id']]];
                        (new MallPrepareOrder())->updatePrepare($data,$where);
                    }
                }
            }
        }
        else{
            throw new \think\Exception(L_("没有此订单数据"), 1001);
        }
    }

    /**
     * @param $good_id
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 周期购商品详情，商品规格查询活动
     */
    public function getGoodsPeriodic($good_id){
      /*  {
            "status": 1000,
    "msg": "",
    "data": {
            "periodic_type": 3,//周期购类型：1每日一次，2每周一次，3每月一次
            "periodic_count": 4,//总期数
            "dates": [//周期购每期配送日期（以英文逗号隔开的数字字符串）：每日类型为空，每周类型表示周几（周日用0表示），每月类型表示几号
                "15",
                "16",
                "17"
            ]
    }
   }*/
        $where[]=['s.goods_id','=',$good_id];
        $where[]=['m.status','=',1];
        $where[]=['m.is_del','=',0];
     /*   $where[]=['m.start_time','<',time()];
        $where[]=['m.end_time','>=',time()];*/
        $arr=(new MallNewPeriodicPurchase())->getInfo($where,$field='*');//根据商品id取出周期购活动的的周期类型
        if(!empty($arr)){
            $msg['periodic_type']=$arr['periodic_type'];
            $msg['periodic_count']=$arr['periodic_count'];
            $periodic_type=$arr['periodic_type'];
            switch ($periodic_type){
                case 1://每日一次
                    $msg['dates']='';
                    break;
                case 2://每周
                    $msg['dates']=explode(',',$arr['periodic_date']);
                    break;
                default://每月
                    $msg['dates']=explode(',',$arr['periodic_date']);
            }
            return $msg;
        }else{
            throw new \think\Exception(L_("商品没有参与周期购活动"), 1001);
        }
    }

}