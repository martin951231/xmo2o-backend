<?php

/**
 * DecorateService.php
 * 装修service
 * Create on 2021/2/19 15:22
 * Created by zhumengqun
 */

namespace app\common\model\service\decorate;

use app\common\model\db\CardNewCoupon;
use app\common\model\db\CardNewCouponHadpull;
use app\common\model\db\CardUserlist;
use app\common\model\db\DecorateControl;
use app\common\model\db\GoodsCategory;
use app\common\model\db\ImageDecorate;
use app\common\model\db\MallBargainAct;
use app\common\model\db\MallGroupAct;
use app\common\model\db\Merchant;
use app\common\model\db\MicroPageDecorate;
use app\common\model\db\MicroPageDecorateField;
use app\common\model\db\NavBottomDecorate;
use app\common\model\db\PersonalDecorate;
use app\common\model\db\ShopGoodsGroup;
use app\common\model\db\ShopGoodsGroupList;
use app\common\model\db\SuspendedWindowDecorate;
use app\common\model\db\SystemCoupon;
use app\common\model\db\SystemCouponHadpull;
use app\common\model\db\SystemOrder;
use app\common\model\db\User;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\foodshop\model\db\FoodshopGoodsLibrary;
use app\group\model\db\Group;
use app\group\model\db\GroupStore;
use app\mall\model\db\MallActivity;
use app\mall\model\db\MallActivityDetail;
use app\mall\model\db\MallCategory;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsSort;
use app\mall\model\db\MallLimitedAct;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallNewBargainAct;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewGroupAct;
use app\mall\model\db\MallNewGroupOrder;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallNewGroupTeam;
use app\mall\model\db\MallNewPeriodicPurchase;
use app\mall\model\db\MallOrder;
use app\mall\model\service\MallGoodsSkuService;
use app\mall\model\service\MallGoodsSpecService;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\storeImageService;
use app\shop\model\db\MerchantStoreShop;
use app\shop\model\db\ShopGoods;
use app\shop\model\service\goods\GoodsImageService;
use map\Coordinate;
use think\facade\Db;
use think\helper\Arr;

class DecorateService
{
    public function __construct()
    {
        $this->personalDecModel = new PersonalDecorate();
        $this->navBottomDecModel = new NavBottomDecorate();
        $this->suspndedWinDecModel = new SuspendedWindowDecorate();
        $this->configDecModel = new DecorateControl();
        $this->microPageDecModel = new MicroPageDecorate();
        $this->microPageDecFieldModel = new MicroPageDecorateField();
        $this->imageDecModel = new ImageDecorate();
        $this->userModel = new User();
        $this->cardNewCouponHadpull = new CardNewCouponHadpull();
        $this->systemCouponHadpull = new SystemCouponHadpull();
        $this->merchantStore = new MerchantStore();

        $this->mallGoods = new MallGoods();
        $this->foodShopGoods=new FoodshopGoodsLibrary();
        $this->groupStore=new GroupStore();
        $this->shopGoods=new ShopGoods();

        $this->systemOrder = new SystemOrder();
    }

    /**
     * @param $param
     * @return array
     * @throws \think\Exception
     * @author mrdeng
     * 4.6.4店铺头部（店铺所属组件)
     */
    public function getMerchantStoreMsg($param)
    {
        if (empty($param['source_id'])) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['store_id' => $param['source_id']];
        $merchantStore = $this->merchantStore->getOne($where,'name,logo,adress,phone,long,lat,pic_info,mer_id')->toArray();
        $arr['name'] = $merchantStore['name'];
        $arr['logo'] = $merchantStore['logo'] ? replace_file_domain($merchantStore['logo']) : '';

        
        // 店铺图片
        if(empty($arr['logo'])){$images = (new storeImageService())->getAllImageByPath($merchantStore['pic_info']);
            $arr['logo'] = $images ? thumb(array_shift($images), 180) : '';
        }
        
        $where_shopgoods=[['store_id', '=', $param['source_id']],['status','=',1]];
        $list=$this->shopGoods->getSome($where_shopgoods,'goods_id')->toArray();
        $count=count($list);
        $arr['product_nums'] = $count;//商品数量
        $list1=$this->shopGoods->getList($where_shopgoods,'month');
        $arr['new_product_nums'] = $list1;//新品数量
        $where1 = [
            ['store_id','=',$param['source_id']],
            ['is_del','=',0],
            ['type','in',['mall', 'shop', 'meal', 'group', 'appoint', 'service', 'store', 'mobile_recharge','gift','village_group', 'mall3']]
        ];
        $list2=$this->systemOrder->getCount($where1);
        $arr['order_nums'] = $list2;//订单数量
        $arr['adress'] =$merchantStore['adress'];
        $arr['phone'] =$merchantStore['phone'];
        $arr['long'] =$merchantStore['long'];
        $arr['lat'] =$merchantStore['lat'];
        $arr['member_card_url'] =cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id='.$merchantStore['mer_id'];
        $merchantStore['logo'] = $arr['logo'];
        return ['list' => $arr,'store'=>$merchantStore];
    }

    /**
     * @param $coupon_data
     * @param $coupon_style
     * @return array
     * @throws \think\Exception
     * 领取优惠券
     */
    public function addCoupons($coupon_data,$coupon_style){
        if (empty($coupon_data['uid']) || empty($coupon_data['coupon_id'])) {
            throw new \think\Exception('参数缺失');
        }
        if($coupon_style*1==0){
           /* $where=[['uid','=',$coupon_data['uid']],['coupon_id','=',$coupon_data['coupon_id']]];
            $arr=$this->systemCouponHadpull->getOne($where);
            if(empty($arr)){
                $data['id']=$this->systemCouponHadpull->add($coupon_data);
            }else{
                throw new \think\Exception('已领取优惠券');
            }*/
            $data = (new SystemCouponService())->receiveCoupon($coupon_data['uid'], $coupon_data['coupon_id']);
        }else{
            /*$CardNewCoupon = new CardNewCoupon();
            $coupon = $CardNewCoupon->where('coupon_id', $coupon_data['coupon_id'])->find();
            $where=[['uid','=',$coupon_data['uid']],['coupon_id','=',$coupon_data['coupon_id']]];
            $arr=$this->cardNewCouponHadpull->where($where)->count();//getOne($where);
            if($arr < $coupon['limit']){
                // $conpon['limit'] =  min($coupon['num'] - $coupon['had_pull'], $coupon['limit']);
                // $coupon_data['num'] = min($coupon_data['num'], $coupon['limit']);
                $coupon_data['num'] = 1;
                $data['id']=$this->cardNewCouponHadpull->add($coupon_data);
                $CardNewCoupon->where('coupon_id', $coupon_data['coupon_id'])->update([
                    'had_pull'  =>  $coupon['had_pull'] + $coupon_data['num']
                ]);
            }else{
                throw new \think\Exception('已达领取上限');
            }*/
            $data = (new MerchantCouponService())->receiveCoupon($coupon_data['uid'], $coupon_data['coupon_id']);
        }
        return ['list' => ['id'=>1]];
    }

    /**
     * @param $param
     * 编辑或添加个人中心装修
     */
    public function addOrEditpersonalDec($param)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        $merId = 0;
        if($param['source'] == 'store'){
            $store = (new MerchantStoreService())->getOne(['store_id' => $param['source_id']]);
            $merId = $store['mer_id'];
        }else if($param['source'] == 'merchant'){
            $merId = $param['source_id'];
        }
        //处理特殊信息
        //广告位
        if (isset($param['adver'])) {
            $param['adver'] = json_encode($param['adver']);
        }
        //活动中心
        if (isset($param['activity'])) {
            if($merId && is_array($param['activity'])){
                foreach ($param['activity'] as $key => $val) {
                    if($val['type'] == 'coupon'){
                        if($param['source'] == 'store'){
                            $param['activity'][$key]['link_url'] = get_base_url() . 'pages/coupon/index';
                        }else if($param['source'] == 'merchant'){
                            $param['activity'][$key]['link_url'] = get_base_url() . 'pages/coupon/storeCoupon?mer_id=' . $merId;
                        }
                    }
                }
            }
            $param['activity'] = json_encode($param['activity']);
        }
        //业务配置
        if (isset($param['business'])) {
            $param['business'] = json_encode($param['business']);
        }
        if ($param['type']*1 == 1) {
            if (!empty($param['id'])) {
                //编辑
                $where = ['id' => $param['id'], 'source' => $param['source'], 'type' => $param['type']];
                $res = $this->personalDecModel->updateThis($where, $param);
            } else {
                //添加
                $res = $this->personalDecModel->add($param);
            }
            //删除预览信息
            $preWhere = ['source_id' => $param['source_id'], 'source' => $param['source'], 'type' => 2];
            $this->personalDecModel->delOne($preWhere);
            if ($res === false) {
                throw new \think\Exception('操作失败，请重试');
            } else {
                return true;
            }
        } else {

            //删除之前的预览 重新保存一份最新的预览信息
            $preWhere = ['source_id' => $param['source_id'], 'source' => $param['source'], 'type' => 2];
            $this->personalDecModel->delOne($preWhere);
            //添加
            unset($param['id']);
            $this->personalDecModel->add($param);
            //返回预览链接的二维码路径待给出
            $url = get_base_url('pages/customPage/my?pageType=2&source=' . $param['source'] . "&source_id=" . $param['source_id']);
            $image = $this->createGoodsQrcode($url);
            return ['image' => $image, 'url' => $url];
        }
    }

    /**
     * @param $param
     * @return mixed
     * 获取个人中心装修信息
     */
    public function getpersonalDec($param, $uid, $type = 0,$view_type=1)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        $merId = 0;
        if($param['source'] == 'merchant'){
            $merId = $param['source_id'];
        }elseif($param['source'] == 'store'){
            $store = (new MerchantStoreService())->getOne(['store_id' => $param['source_id']]);
            $merId = $store['mer_id'];
        }
        $where = ['source' => $param['source'], 'source_id' => $param['source_id'],'type'=>$view_type];
        $arr = $this->personalDecModel->getOne($where);
        if (!empty($arr)) {
            $arr = $arr->toArray();
            $arr['mer_id'] = $merId;
            $where=[['u.uid','=',$uid],['u.mer_id','=',$param['source_id']]];
            $card_arr=(new CardUserlist())->getUserCard($where);
            if(empty($card_arr)){
                $arr['card_status']=0;
                $arr['card_name']="";
                $arr['card_discount']="";
            }else{
                $card_arr=$card_arr->toArray();
                $arr['card_status']=1;
                $arr['card_name']=$card_arr["cardname"] ?? '';
                $arr['card_discount']=$card_arr["discount"];
            }
            $arr['card_url']=cfg('site_url') ."/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id=".$merId;
            $arr['center_all'][0]['nums'] = 0;
            $arr['center_all'][0]['name'] = "余额";
            $arr['center_all'][0]['link_url'] = "";
            $arr['center_all'][1]['nums'] = 0;
            $arr['center_all'][1]['name'] = "积分";
            $arr['center_all'][1]['link_url'] = "";
            $arr['center_all'][2]['nums'] = 0;
            $arr['center_all'][2]['name'] = "优惠券";
            $arr['center_all'][2]['link_url'] = "";
            if (!empty($uid) ) {
                $where_user = [
                    ['uid', '=', $uid]
                ];
                $user = $this->userModel->getOne($where_user, 'score_count,now_money');
                $arr['center_all'][0]['nums'] = $user['now_money'];
                $arr['center_all'][0]['name'] = "余额";
                $arr['center_all'][0]['link_url'] = cfg('site_url') . "/wap.php?g=Wap&c=My&a=my_money";
                $arr['center_all'][1]['nums'] = $user['score_count'];
                $arr['center_all'][1]['name'] = "积分";
                $arr['center_all'][1]['link_url'] = cfg('site_url') . "/wap.php?g=Wap&c=My&a=integral";
                // $arr['center_all'][1]['link_url'] = cfg('site_url') . "/wap.php?g=Wap&c=My&a=integral";
                $where_user1 = [
                    ['uid', '=', $uid],
                    ['is_use', '=', 0],
                ];
                if ($type == 0) {//商家券
                    $merchantCoupon = (new MerchantCouponService())->getAvailableCoupon($uid, $merId, [], false);
                    
                    $arr['center_all'][2]['nums'] = count($merchantCoupon);
                    $arr['center_all'][2]['name'] = "优惠券";
                    $arr['center_all'][2]['link_url'] = cfg('site_url') . "/packapp/plat/pages/coupon/storeCoupon?mer_id=" . $merId;
                } else {
                    //平台券暂时没有用上
                    $arr_mer1 = array();
                    $systemCouponHadpull = $this->systemCouponHadpull->getSome($where_user1, 'num')->toArray();
                    foreach ($systemCouponHadpull as $key1 => $val1) {
                        $arr_mer1[] = $val1['num'];
                    }
                    $arr['center_all'][2]['nums'] = array_sum($arr_mer1);
                    $arr['center_all'][2]['name'] = "优惠券";
                    $arr['center_all'][2]['link_url'] = cfg('site_url') . "/packapp/plat/pages/coupon/storeCoupon?mer_id=" . $param['source_id'];
                }
            }
            //用户信息

            //广告位
            if (!empty($arr['adver'])) {
                $arr['adver'] = json_decode($arr['adver'], true);
                if (!empty($arr['adver'])) {
                    foreach ($arr['adver'] as $k => $v) {
                        $arr['adver'][$k]['image_url'] = replace_file_domain($v['image_url']);
                    }
                }
            }
            //活动中心
            if (!empty($arr['activity'])) {
                $arr['activity'] = json_decode($arr['activity'], true);
                if (!empty($arr['activity'])) {
                    foreach ($arr['activity'] as $k => $v) {
                        $arr['activity'][$k]['icon_url'] = replace_file_domain($v['icon_url']);
                        //特殊链接处理
                        if(isset($v['type'])){
                            if($v['type'] == 'coupon'){
                                // $arr['activity'][$k]['link_url'] = get_base_url() . 'pages/coupon/storeCoupon';
                            }
                        }
                    }
                }
            }
            //业务配置
            if (!empty($arr['business'])) {
                $arr['business'] = json_decode($arr['business'], true);
                if (!empty($arr['business'])) {
                    foreach ($arr['business'] as $k => $v) {
                        $arr['business'][$k]['icon_url'] = replace_file_domain($v['icon_url']);
                        //特殊链接处理
                        if(isset($v['type'])){
                            if($v['type'] == 'customerService'){ //在线客服
                                if ($param['source'] == 'merchant') {
                                    //优先获取主店铺
                                    $mainStore = Db::name('merchant_store')->where('status', 1)->where('ismain', 1)->where('mer_id', $param['source_id'])->find();
                                    if ($mainStore) {
                                        $arr['business'][$k]['link_url'] = build_im_chat_url("user_" . $uid, 'store_' . $mainStore['store_id'], 'user2store');
                                    } else {
                                        $arr['business'][$k]['link_url'] = '';
                                    }
                                } else {
                                    $arr['business'][$k]['link_url'] = build_im_chat_url("user_" . $uid, 'store_' . $param['source_id'], 'user2store');
                                }
                            }else if($v['type'] == 'order'){ //我的订单
                                $arr['business'][$k]['link_url'] = get_base_url() . 'pages/my/my_order?state=0';
                            }
                        }
                    }
                }
            }
            if ($arr['head_style'] == 2) {
                $arr['head_style_value'] = replace_file_domain($arr['head_style_value']);
            }
        }
        return $arr;
    }

    /**
     * @param $param
     * @return bool
     * 编辑或添加底部导航装修
     */
    public function addOrEditNavBottom($param)
    {
        //导航内容json化
        if (!empty($param['content'])) {
            $param['content'] = json_encode($param['content']);
        }
        $res = false;
        $is_open = $param['is_open'];
        unset($param['is_open']);
        if (!empty($param['id'])) {
            //编辑
            $where = ['id' => $param['id'], 'source' => $param['source']];
            $res = $this->navBottomDecModel->updateThis($where, $param);
            if ($res !== false) {
                $this->configDecModel->updateThis(['type_id' => $param['id'], 'type' => 1], ['is_open' => $is_open]);
            }
        } else {
            //添加
            $id = $this->navBottomDecModel->add($param);
            if ($id) {
                $res = $this->configDecModel->add(['type_id' => $id, 'type' => 1, 'is_open' => $is_open]);
            }
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * @param $param
     * @return mixed
     * 获取底部导航装修信息
     */
    public function getNavBottomDec1($param)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['source' => $param['source'], 'source_id' => $param['source_id']];
        $con = $this->configDecModel->getOne(['type_id' => $param['id'], 'type' => 1]) ? $this->configDecModel->getOne(['type_id' => $param['id'], 'type' => 1])['is_open'] : '';
        if ($con) {
            $arr = $this->navBottomDecModel->getOne($where);
            if (!empty($arr)) {
                $arr = $arr->toArray();
                //内容
                if (!empty($arr['content'])) {
                    $arr['content'] = json_decode($arr['content'], true);
                    if (!empty($arr['content'])) {
                        foreach ($arr['content'] as $k => $v) {
                            $arr['content'][$k]['common_image'] = replace_file_domain($v['common_image']);
                            $arr['content'][$k]['focus_image'] = replace_file_domain($v['focus_image']);
                        }
                    }
                }
            }
            $arr['is_open'] = 1;
            return $arr;
        } else {
            return ['is_open' => 0];
        }
    }

    /**
     * @param $param
     * @return mixed
     * 获取底部导航装修信息
     */
    public function getNavBottomDec($param)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['source' => $param['source'], 'source_id' => $param['source_id']];
        $arr = $this->navBottomDecModel->getOne($where);
        if (!empty($arr)) {
            $arr = $arr->toArray();
            //内容
            if (!empty($arr['content'])) {
                $arr['content'] = json_decode($arr['content'], true);
                if (!empty($arr['content'])) {
                    foreach ($arr['content'] as $k => $v) {
                        $arr['content'][$k]['common_image'] = replace_file_domain($v['common_image']);
                        $arr['content'][$k]['focus_image'] = replace_file_domain($v['focus_image']);
                        if(isset($arr['content'][$k]['is_default'])){
                            $arr['content'][$k]['link_url'] = cfg('site_url')."/packapp/plat/pages/customPage/index?source=".$param['source']."&source_id=".$param['source_id'];
                        }
                    }
                }
            }
            $con = $this->configDecModel->getOne(['type_id' => $arr['id'], 'type' => 1]) ? $this->configDecModel->getOne(['type_id' => $arr['id'], 'type' => 1])['is_open'] : '';
            $arr['is_open'] = $con;
        }
        return $arr;
    }

    /**
     * @param $param
     * @return bool
     * 编辑或添加悬浮窗装修
     */
    public function addOrEditSuspendedWindow($param)
    {
        //内容json化
        if (!empty($param['content'])) {
            $param['content'] = json_encode($param['content']);
        }
        $res = false;
        $is_open = $param['is_open'];
        unset($param['is_open']);
        if (!empty($param['id'])) {
            //编辑
            $where = ['id' => $param['id'], 'source' => $param['source']];
            $res = $this->suspndedWinDecModel->updateThis($where, $param);
            if ($res !== false) {
                $this->configDecModel->updateThis(['type_id' => $param['id'], 'type' => 2], ['is_open' => $is_open]);
            }
        } else {
            //添加
            $id = $this->suspndedWinDecModel->add($param);
            if ($id) {
                $res = $this->configDecModel->add(['type_id' => $id, 'type' => 2, 'is_open' => $is_open]);
            }
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * @param $param
     * @return mixed
     * 获取悬浮窗装修信息
     */
    public function getSuspendedWindow1($param)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['source' => $param['source'], 'source_id' => $param['source_id']];
        $con = $this->configDecModel->getOne(['type_id' => $param['id'], 'type' => 2]) ? $this->configDecModel->getOne(['type_id' => $param['id'], 'type' => 2])['is_open'] : '';
        if ($con) {
            $arr = $this->suspndedWinDecModel->getOne($where);
            if (!empty($arr)) {
                $arr = $arr->toArray();
                //内容
                if (!empty($arr['content'])) {
                    $arr['content'] = json_decode($arr['content'], true);
                    if (!empty($arr['content'])) {
                        foreach ($arr['content'] as $k => $v) {
                            $arr['content'][$k]['image'] = replace_file_domain($v['image']);
                        }
                    }
                }
            }
            $arr['is_open'] = 1;
            return $arr;
        } else {
            return ['is_open' => 0];
        }
    }

    /**
     * @param $param
     * @return mixed
     * 获取悬浮窗装修信息
     */
    public function getSuspendedWindow($param)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['source' => $param['source'], 'source_id' => $param['source_id']];
        $arr = $this->suspndedWinDecModel->getOne($where);
        if (!empty($arr)) {
            $arr = $arr->toArray();
            //内容
            if (!empty($arr['content'])) {
                $arr['content'] = json_decode($arr['content'], true);
                if (!empty($arr['content'])) {
                    foreach ($arr['content'] as $k => $v) {
                        $arr['content'][$k]['image'] = replace_file_domain($v['image']);
                    }
                }
            }
            $con = $this->configDecModel->getOne(['type_id' => $arr['id'], 'type' => 2]) ? $this->configDecModel->getOne(['type_id' => $arr['id'], 'type' => 2])['is_open'] : '';
            $arr['is_open'] = $con;
            if($param['source']=="merchant"){
                $msg=(new Merchant())->getInfo($param['source_id']);
                if(empty($msg)){
                    $arr['phone'] = "";
                }else{
                    $arr['phone'] = $msg['phone'];
                }
            }elseif ($param['source']=="store"){
                $msg=$this->merchantStore->getOne(['store_id' => $param['source_id']]);
                if(empty($msg)){
                    $arr['phone'] = "";
                }else{
                    $arr['phone'] = $msg['phone'];
                }
            }else{
                $arr['phone'] = "";
            }
        }
        return $arr;
    }

    /**
     * @param $param
     * 编辑或添加微页面装修
     */
    public function addOrEditMicroPage($param)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        $data = [
            'source' => $param['source'],
            'source_id' => $param['source_id'],
            'type' => $param['type'],
            'page_title' => $param['page_title'],
            'title_color' => $param['title_color'],
            'bg_color_style' => $param['bg_color_style'],
            'bg_color' => $param['bg_color'],
            'bg_color_nav_style' => $param['bg_color_nav_style'],
            'bg_color_nav' => $param['bg_color_nav'],
            'nav_bottom_display' => $param['nav_bottom_display'],
            'share_title' => $param['share_title'],
            'share_desc' => $param['share_desc'],
            'share_image_wechat' => $param['share_image_wechat'],
            'share_image_h5' => $param['share_image_h5'],
            'create_time' => time()
        ];
        
        $data = array_merge($data, Arr::only($param,[
            'bg_image', 'bg_image_nav', 'bg_color_nav_opacity'
        ]));
        if ($param['type'] == 1) {
            if (!empty($param['id'])) {
                //编辑
                $id=$param['id'];
                $where = ['id' => $param['id'], 'source' => $param['source']];
                $res = $this->microPageDecModel->updateThis($where, $data);
                //更新装修内容，先删除再添加
                if ($res !== false) {
                    $fieldWhere = ['page_id' => $param['id']];
                    $res2 = $this->microPageDecFieldModel->delOne($fieldWhere);
                    if ($param['custom']) {
                        //添加装修内容
                        foreach ($param['custom'] as $item) {
                            if ($item['type'] == 'storeMap' && cfg('tianditu_web_key')) {
                                list($item['content']['list']['lng'], $item['content']['list']['lat']) = Coordinate::wgs84ToBd09($item['content']['list']['lng'], $item['content']['list']['lat']);
                            }
                            $data = ['page_id' => $param['id'], 'field_type' => $item['type'], 'content' => json_encode($item), 'source' => $param['source'], 'source_id' => $param['source_id']];
                            $this->microPageDecFieldModel->add($data);
                        }
                    }
                }
            } else {
                //添加
                $data['create_time'] = time();
                $id = $this->microPageDecModel->add($data);
                if ($id && $param['custom']) {
                    //添加装修内容
                    foreach ($param['custom'] as $item) {
                        $data = ['page_id' => $id, 'field_type' => $item['type'], 'content' => json_encode($item), 'source' => $param['source'], 'source_id' => $param['source_id']];
                        $res = $this->microPageDecFieldModel->add($data);
                    }
                }
            }
            //删除预览信息
            $pWhere = ['source_id' => $param['source_id'], 'source' => $param['source'], 'type' => 2];
            $this->microPageDecModel->delOne($pWhere);
            return ['id' => $id];
        } else {
            //删除之前的预览 重新保存一份最新的预览信息
            $preWhere = ['source_id' => $param['source_id'], 'source' => $param['source'], 'type' => 2];
            $this->microPageDecModel->delOne($preWhere);
            //添加
            $id = $this->microPageDecModel->add($data);
            if ($id && $param['custom']) {
                //添加装修内容
                foreach ($param['custom'] as $item) {
                    $data = ['page_id' => $id, 'field_type' => $item['type'], 'content' => json_encode($item), 'source' => $param['source'], 'source_id' => $param['source_id']];
                    $this->microPageDecFieldModel->add($data);
                }
            }

            //返回预览链接的二维码路径待给出
            $url = get_base_url('pages/customPage/customPage?pageType=2&pageId=' . $id . "&source=" . $param['source'] . "&source_id=" . $param['source_id']);
            $image = $this->createGoodsQrcode($url);
           
            return ['image' => $image, 'url' => $url,'id'=>$id];
        }
    }

    /**
     * @param $param
     * @return bool
     * 首页装修展示
     */
    public function getIndexPage($param)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        //编辑
        if(empty($param['id'])){
            $where = ['source_id' => $param['source_id'], 'source' => $param['source'], 'is_home_page' => 1];
        }else{
            $where = ['source_id' => $param['source_id'], 'source' => $param['source'], 'is_home_page' => 1, 'id' => $param['id']*1];
        }
        $arr = $this->microPageDecModel->getOne($where);
        if (!empty($arr)) {
            $arr = $arr->toArray();
            //用户端访问更新浏览次数
            if ($param['from'] == 1) {
                $brows_times = $arr['brows_times'] + 1;
                $this->microPageDecModel->updateThis(['id' => $arr['id']], ['brows_times' => $brows_times]);
            }
            //格式化一下
            $arr['create_time'] = date('Y-m-d', $arr['create_time']);
            $fieldWhere = ['page_id' => $arr['id']];
            $fieldArr = $this->microPageDecFieldModel->getSome($fieldWhere, true, 'page_id DESC');
            $content = array();
            if (!empty($fieldArr)) {
                $fieldArr = $fieldArr->toArray();
                foreach ($fieldArr as &$value) {
                    $value['type']=$value['field_type'];
                    if ($value['field_type'] == 'richText') {
                        $value['content'] = json_decode($value['content'], true);
                        if(is_array($value['content']['content'])){
                            $value['content'] = replace_file_domain_arr($value['content']['content']);
                        }else{
                            $value['content'] = replace_file_domain_content(($value['content']['content']));
                        }
                    } else {
                        $value['content'] = json_decode($value['content'], true);
                        if(isset($value['content']['content'])){
                            $value['content'] = replace_file_domain_arr($value['content']['content']);
                        }else{
                            $value['content'] = replace_file_domain_arr($value['content']);
                        }
                    }

                    if(in_array($value['type'], ['imgAdver', 'picNav'])){
                        if(!empty($value['content']['list'])) {
                            foreach ($value['content']['list'] as $k1 => $v1) {
                                if(isset($v1['pic'])){
                                    $value['content']['list'][$k1]['pic'] = replace_file_domain($v1['pic']);
                                }else if(isset($v1['nav_icon'])){
                                    $value['content']['list'][$k1]['nav_icon'] = replace_file_domain($v1['nav_icon']);
                                }
                            }
                        }
                    }

                    if($value['type'] == 'customVideo'){
                        if(!empty($value['content']['choose_surface_list'])) {
                            foreach ($value['content']['choose_surface_list'] as $k1 => $v1) {
                                $value['content']['choose_surface_list'][$k1] = replace_file_domain($v1);
                            }
                        }
                        if(!empty($value['content']['choose_video_list'])) {
                            if(isset($value['content']['choose_video_list'][0])){
                                $value['content']['choose_video_list'][0] =  replace_file_domain($value['content']['choose_video_list'][0]);
                            }
                            if(isset($value['content']['choose_video_list'][1])){
                                $value['content']['choose_video_list'][1] =  replace_file_domain($value['content']['choose_video_list'][1]);
                            }
                        }
    
                    }

                    if($value['type'] == 'onlineService'){ //在线客服
                        if ($param['source'] == 'merchant') {
                            //优先获取主店铺
                            $mainStore = Db::name('merchant_store')->where('status', 1)->where('ismain', 1)->where('mer_id', $param['source_id'])->find();
                            if ($mainStore) {
                                $value['url'] = cfg('site_url') . '/wap.php?c=Kefu&a=contactStore&store_id=' . $mainStore['store_id'];;
                            } else {
                                $value['url'] = '';
                            }
                        } else {
                            $value['url'] = cfg('site_url') . '/wap.php?c=Kefu&a=contactStore&store_id=' . $param['source_id'];
                        }
                    }
                      
                    $content[] = $value;
                }
            }
            $arr['custom'] = $content;
        }
        return $arr;
    }
    
    public function getPageId($param)
    {
        $previewPage = MicroPageDecorate::where([
            'source' => $param['source'],
            'source_id' => $param['source_id'],
            'type' => 2
        ])->order('id DESC')->value('id');

        $page = MicroPageDecorate::where([
            'source' => $param['source'],
            'source_id' => $param['source_id'],
            'type' => 1
        ])->order('id DESC')->value('id');
        
        return [
            'page_id' => $page,
            'pre_page_id' => $previewPage
        ];
    }

    /**
     * @param $param
     * @return array
     * 首页装修展示
     */
    public function getEditMicoPage($param)
    {
        if (empty($param['source'])) {
            throw new \think\Exception('参数缺失');
        }
        $type = $param['type'] ?: 1;
        //编辑
        $where = ['source_id' => $param['source_id'], 'source' => $param['source'], 'id' => $param['id']*1, 'type' => $type];
        $arr = $this->microPageDecModel->getOne($where);
        if (!empty($arr)) {
            $data['brows_times']=$arr['brows_times']+1;
            (new MicroPageDecorate())->updateThis($where,$data);//浏览增加1
            $arr = $arr->toArray();
            //格式化一下
            $arr['create_time'] = date('Y-m-d', $arr['create_time']);
            $arr['bg_image_nav'] = replace_file_domain($arr['bg_image_nav']);
            $arr['bg_image'] = $arr['bg_image'] ? array_map('replace_file_domain', $arr['bg_image']) : [];
            $arr['share_image_h5'] = replace_file_domain($arr['share_image_h5']);
            $arr['share_image_wechat'] = replace_file_domain($arr['share_image_wechat']);
            $fieldWhere = ['page_id' => $arr['id']];
            $fieldArr = $this->microPageDecFieldModel->getSome($fieldWhere, true, 'page_id DESC');
            $content = array();
            if (!empty($fieldArr)) {
                foreach ($fieldArr as &$value) {
                    $json_val = json_decode($value['content'],true);
                    $json_val['content']['richTextCode'] = !empty($json_val['content']['richTextCode'])?replace_file_domain_content($json_val['content']['richTextCode']):'';
                    $content[] = $json_val;
                }
            }
            
            if(!empty($content)){
              foreach ($content as $key=>$val){
                  $json = json_encode($val);
                  $contentArr=json_decode($json,true);
                 // $jsonp = $this->object_array($json);
                  if($contentArr['type']=="mallLimited"){
                      if(!empty($contentArr['content']['list'])){
                          foreach ($contentArr['content']['list'] as $k=>$v){
                              $where=[['act_id','=',$v['act_id']],['type','=','limited']];
                              $arr1=(new MallActivity())->getOne($where);
                              if(!empty($arr1)){
                                  $contentArr['content']['list'][$k]['start_time']=date("Y-m-d H:i:s",$arr1['start_time']);
                                  $contentArr['content']['list'][$k]['end_time']=date("Y-m-d H:i:s",$arr1['end_time']);
                              }
                              //多规格商品显示最低价
                              $skuLowest = (new \app\mall\model\db\MallLimitedSku())->where('act_id', $v['act_id'])->where('goods_id', $v['goods_id'])->order('act_price', 'asc')->find();
                              if ($skuLowest) {
                                  $contentArr['content']['list'][$k]['act_price'] = get_format_number($skuLowest->act_price);
                                  $contentArr['content']['list'][$k]['orgin_price'] = get_format_number($skuLowest->act_price + $skuLowest->reduce_money);
                              } 
                          }
                      }
                      $content[$key]=json_decode(json_encode($contentArr));
                  }

                    //外卖，重置下商品价格和图片
                    if ($contentArr['type'] == "shopModules" && !empty($contentArr['content']['list'])) {
                        $data = $contentArr['content']['list'];
                        foreach ($data as $k1 => $v1) {
                            if ($v1['children']) {
                                $goodsIds = array_column($v1['children'], 'goods_id');
                                $goods = \think\facade\Db::name('shop_goods')
                                    ->field('goods_id,price,image')
                                    ->where('goods_id', 'in', $goodsIds)
                                    ->select()
                                    ->each(function ($item) {
                                        $imgs = array_filter(explode(';', $item['image']));
                                        $item['main_image'] = $imgs ? replace_file_domain($imgs[0]) : '';
                                        $item['price'] = get_format_number($item['price']);
                                        return $item;
                                    })->toArray();
                                $goods = array_column($goods, null, 'goods_id');

                                foreach ($v1['children'] as $k2 => $v2) {
                                    if(isset($goods[$v2['goods_id']])){
                                        $v1['children'][$k2]['price'] = $goods[$v2['goods_id']]['price'];
                                        $v1['children'][$k2]['image'] = $goods[$v2['goods_id']]['main_image'];
                                    }
                                }
                                $data[$k1]['children'] = $v1['children'];
                            }
                        }
                        $contentArr['content']['list'] = $data;
                        $content[$key]=json_decode(json_encode($contentArr));
                    }


                  if(in_array($contentArr['type'], ['imgAdver', 'picNav'])){
                    if(!empty($contentArr['content']['list'])) {
                        foreach ($contentArr['content']['list'] as $k1 => $v1) {
                            if(isset($v1['pic'])){
                                $contentArr['content']['list'][$k1]['pic'] = replace_file_domain($v1['pic']);
                            }else if(isset($v1['nav_icon'])){
                                $contentArr['content']['list'][$k1]['nav_icon'] = replace_file_domain($v1['nav_icon']);
                            }
                        }
                    }
                    $content[$key]=json_decode(json_encode($contentArr));
                  }

                  if($contentArr['type'] == 'customVideo'){
                    if(!empty($contentArr['content']['choose_surface_list'])) {
                        foreach ($contentArr['content']['choose_surface_list'] as $k1 => $v1) {
                            $contentArr['content']['choose_surface_list'][$k1] = replace_file_domain($v1);
                        }
                    }
                    if(!empty($contentArr['content']['choose_video_list'])) {
                        if(isset($contentArr['content']['choose_video_list'][0])){
                            $contentArr['content']['choose_video_list'][0] =  replace_file_domain($contentArr['content']['choose_video_list'][0]);
                        }
                        if(isset($contentArr['content']['choose_video_list'][1])){
                            $contentArr['content']['choose_video_list'][1] =  replace_file_domain($contentArr['content']['choose_video_list'][1]);
                        }
                    }
                    $content[$key]=json_decode(json_encode($contentArr));

                  }

                  if($contentArr['type']=="coupon") {
                      if(!empty($contentArr['content']['list'])) {
                          if($contentArr['content']['is_show']){
                              foreach ($contentArr['content']['list'] as $k1 => $v1) {
                                  if($param['source']=="platform"){
                                      $where=[['coupon_id','=',$v1['coupon_id']],['status','=',1],['delete','=',0]];
                                      $SystemCoupon=(new SystemCoupon())->getOne($where);
                                      if(empty($SystemCoupon)){
                                          unset($contentArr['content']['list'][$k1]);
                                      }else{
                                          $SystemCoupon=$SystemCoupon->toArray();
                                          $count=(new SystemCoupon())->getSum(['coupon_id'=>$v1['coupon_id']],'num');
                                          if($v1['num']<=$v1['had_pull'] || $count>=$SystemCoupon['num']){
                                              unset($contentArr['content']['list'][$k1]);
                                          }
                                      }
                                      $arr = array_merge($arr, $SystemCoupon);
                                  }else{
                                      $where=[['coupon_id','=',$v1['coupon_id']],['status','=',1]];
                                      $CardNewCoupon=(new CardNewCoupon())->getOne($where);
                                      if(empty($CardNewCoupon)){
                                            $CardNewCoupon = [];
                                          unset($contentArr['content']['list'][$k1]);
                                      }else{
                                          $CardNewCoupon=$CardNewCoupon->toArray();
                                          $count=(new CardNewCouponHadpull())->getSum(['coupon_id'=>$v1['coupon_id']],'num');
                                          if($v1['num']<=$v1['had_pull'] || $count>=$CardNewCoupon['num']){
                                              unset($contentArr['content']['list'][$k1]);
                                          }else{
                                              $contentArr['content']['list'][$k1]['had_pull']=$count;
                                          }
                                      }
                                      $arr = array_merge($arr, $CardNewCoupon);
                                  }
                              }
                              $contentArr['content']['list']=array_values($contentArr['content']['list']);
                              if(!empty($contentArr['content']['list'])){
                                  $content[$key]=json_decode(json_encode($contentArr));
                              }
                          }
                      }
                  }


                if ($contentArr['type'] == "onlineService") {
                    if ($param['source'] == 'merchant') {
                        //优先获取主店铺
                        $mainStore = Db::name('merchant_store')->where('status', 1)->where('ismain', 1)->where('mer_id', $param['source_id'])->find();
                        if ($mainStore) {
                            $contentArr['url'] = cfg('site_url') . '/wap.php?c=Kefu&a=contactStore&store_id=' . $mainStore['store_id'];
                        } else {
                            $contentArr['url'] = '';
                        }
                    } else {
                        $contentArr['url'] = cfg('site_url') . '/wap.php?c=Kefu&a=contactStore&store_id=' . $param['source_id'];
                    }
                    $content[$key] = json_decode(json_encode($contentArr));
                }

                if ($contentArr['type'] == "magicSquare") {
                    $contentArr['content']['list'] = array_map(function ($r) {
                        $r['image'] = replace_file_domain($r['image']);
                        return $r;
                    }, $contentArr['content']['list']?:[]);
                    $content[$key] = json_decode(json_encode($contentArr));
                }
                //地图
                if($contentArr['type'] == 'storeMap' && cfg('tianditu_web_key')){
                    list($contentArr['content']['list']['lng'],$contentArr['content']['list']['lat']) = Coordinate::bd09ToWgs84($contentArr['content']['list']['lng'],$contentArr['content']['list']['lat']);
                    $content[$key] = json_decode(json_encode($contentArr));
                }
              }
            }
            $arr['custom'] = $content;
            $arr['site_url']=cfg('site_url');
        }
        return $arr;
    }

    function object_array($array)
    {
        if(is_object($array))
        {
            $array = (array)$array;
        }
        if(is_array($array))
        {
            foreach($array as $key=>$value)
            {
                $array[$key] = object_array($value);
            }
        }
        return $array;
    }
//$jsonp = object_array($json);
//var_dump($jsonp);
    /**
     * @param $param
     * @return mixed
     * 获取微页面列表
     */
    public function getMicroPageList($param)
    {
        $where = [['source', '=', $param['source']], ['source_id', '=', $param['source_id']], ['type', '=', 1]];
        if (!empty($param['keyword'])) {
            $where[] = ['page_title', 'like', '%' . $param['keyword'] . '%'];
        }
        $list = $this->microPageDecModel->getSome($where, true, 'create_time DESC,is_home_page DESC', ($param['page']-1)*$param['pageSize'], $param['pageSize']);
        $count = $this->microPageDecModel->getCount($where);
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $key => $val) {
                $list[$key]['create_time'] = date('Y-m-d', $val['create_time']);
                $list[$key]['goods_nums'] = 0;
                $list[$key]['link_url'] = get_base_url("pages/customPage/customPage?source=" . $val['source'] . "&source_id=" . $val['source_id'] . "&pageId=" . $val['id']);
                $list[$key]['qrcode'] = $this->createGoodsQrcode($list[$key]['link_url']);

                // 查询商品数量
                $fieldWhere = ['page_id' => $val['id']];
                $fieldArr = $this->microPageDecFieldModel->getSome($fieldWhere, true, 'page_id DESC');
                $content = array();
                if (!empty($fieldArr)) {
                    foreach ($fieldArr as &$value) {
                        $content[] = json_decode(replace_file_domain_content($value['content']));
                    }
                }
                if(!empty($content)){
                    foreach ($content as $val){
                        $json = json_encode($val);
                        $contentArr = json_decode($json,true);

                        switch($contentArr['type']){
                            case 'mallGoods':
                            case 'shopGoods':
                            case 'marketingActivities':
                            case 'mallGroup':
                            case 'mallBargain':
                            case 'mallPeriod':
                            case 'shopModules':
                            case 'mallLimited':
                                if($contentArr['content'] && ($contentArr['content']['list'])){
                                    $list[$key]['goods_nums'] += count($contentArr['content']['list']);
                                }
                                break;
                        }
                    }
                }

            }
        }
        return ['list' => $list, 'total' => $count];
    }

    /**
     * @param $id
     * @return mixed
     * @throws \think\Exception
     * 获取编辑的微页面
     */
    public function getMicroPage($id)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['id' => $id];
        $arr = $this->microPageDecModel->getOne($where, true);
        if (!empty($arr)) {
            $arr = $arr->toArray();
        }
        //装修内容
        $where = ['page_id' => $id];
        $custom = $this->microPageDecFieldModel->getSome($where, true)->toArray();
        if (!empty($custom)) {
            foreach ($custom as $item) {
                $item['content'] = replace_file_domain_content($item['content']);
                $item['content'] = json_decode($item['content']);
            }
        }
        $arr['custom'] = $custom;
        return $arr;
    }

    /**
     * @param $id
     * @return bool
     * @throws \think\Exception
     * 删除微页面
     */
    public function delMicroPage($id)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['id' => $id];
        $res = $this->microPageDecModel->delOne($where);
        if ($res !== false) {
            //删除装修内容
            $where = ['page_id' => $id];
            $this->microPageDecFieldModel->delOne($where);
        } else {
            throw new \think\Exception('操作失败，请重试');
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws \think\Exception
     * 设为主页
     */
    public function setHomePage($id, $source, $source_id)
    {
        if (empty($id) || empty('source')) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['id' => $id];
        $data = ['is_home_page' => 1];
        $res = $this->microPageDecModel->updateThis($where, $data);
        if ($res !== false) {
            //取消其他的已为主页
            $where = [['id', '<>', $id], ['source', '=', $source],['source_id','=',$source_id]];
            $data = ['is_home_page' => 0];
            $this->microPageDecModel->updateThis($where, $data);
        } else {
            throw new \think\Exception('操作失败，请重试');
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws \think\Exception
     * 预览微页面
     */
    public function getPreview($id)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        return $this->createGoodsQrcode($id);
    }

    /**
     * @param $goods_id
     * @return string
     * 获取预览页面二维码
     */
    public function createGoodsQrcode($url)
    {
        require_once request()->server('DOCUMENT_ROOT') . "/v20/extend/phpqrcode/phpqrcode.php";
        $dir = '/runtime/qrcode/micropage/';
        $path = '../..' . $dir;
        $filename = md5($url) . '.png';
        if (file_exists($path . '/' . $filename)) {
            return cfg('site_url') . $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrCon = substr($url, 0, 4) == 'http' ? $url : cfg('site_url') . $url;
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return cfg('site_url') . $dir . '/' . $filename;
    }

    /**
     * @param $id
     * @return bool
     * @throws \think\Exception
     * 获取用过的页面
     */
    public function getImages($source, $source_id, $page, $pageSize)
    {
        if (empty($source)) {
            throw new \think\Exception('参数缺失');
        }
        $list = $this->imageDecModel->getSome(['source' => $source, 'source_id' => $source_id], 'id,url,type', 'dateline DESC', $page, $pageSize);
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['url'] = replace_file_domain($value['url']);
            }
        }
        return $list;
    }

    /**
     * @param $id
     * @return array
     * @throws \think\Exception
     * 获取优惠券
     */
    public function getCoupons($source, $source_id, $keyword)
    {
        if (empty($source)) {
            throw new \think\Exception('参数缺失');
        }
        if ($source == 'platform') {
            $where = [
                ['end_time', '>', time()],
                ['start_time', '<', time()],
                ['status', '=', 1],
                ['allow_im_get', '=', 0],
                ['is_partition', '=', 0],
                ['is_hide', '=', 0],
            ];
            if ($keyword !== '') {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            $count = (new SystemCoupon())->getCount($where);
            $couponList = (new SystemCoupon())->getSome($where, 'coupon_id,name,had_pull,num,des,discount,is_discount,order_money,status', 'status ASC,allow_new DESC,discount DESC,add_time DESC');
            if(!empty($couponList)){
                foreach ($couponList as $key=>$val){
                    $couponList[$key]['coupon_style']=0;
                }
            }
        } else {
            $where = [
                ['end_time', '>', time()],
                ['start_time', '<', time()],
                ['status', '=', 1],
                ['allow_im_get', '=', 0],
                ['is_live', '=', 0],
            ];
            if ($source == 'merchant') {
                $where[] = ['mer_id', '=', $source_id];
            } else {
                $store = (new MerchantStoreService())->getOne(['store_id'=>$source_id]);
                $where[] = ['', 'exp', Db::raw('find_in_set('.$source_id.', store_id)')];
                $where[] = ['mer_id', '=', $store['mer_id']];
            }

            if ($keyword !== '') {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            $count = (new CardNewCoupon())->getCount($where);
            $couponList = (new CardNewCoupon())->getSome($where, 'coupon_id,name,had_pull,num,des,discount,is_discount,order_money,discount_value,status', 'status ASC,allow_new DESC,discount DESC,add_time DESC');
            if(!empty($couponList)){
                foreach ($couponList as $key=>$val){
                    $couponList[$key]['coupon_style']=1;
                }
            }
        }
        if (!empty($couponList)) {
            foreach ($couponList as $k => $v) {
                if ($v['is_discount'] == 1) {
                    $couponList[$k]['discount_txt'] = sprintf('满%s打%s折', $v['order_money'], $v['discount_value']);
                } else {
                    $couponList[$k]['discount_txt'] = sprintf('满%s减%s', $v['order_money'], $v['discount']);
                }
            }
        }
        return ['list' => $couponList, 'total' => $count];
    }

    /**
     * @param $id
     * @return array
     * @throws \think\Exception
     * 获取营销活动列表
     */
    public function getActInfo($source, $source_id, $keyword, $hdType, $page, $pageSize)
    {
        $shop_type = 1;
        $list = array();
        $count = 0;
        if (empty($source)) {
            throw new \think\Exception('参数缺失');
        }
        try{
        switch ($hdType) {
            case 'group':
                $where = [
                    ['a.type','=','group'],
                    ['a.start_time','<',time()],
                    ['a.end_time','>=',time()],
                    ['a.status','=',1]
                ];
                if ($keyword !== '') {
                    array_push($where, ['gd.name', 'like', '%' . $keyword . '%']);
                }
                if ($source == 'merchant') {
                    $store_ids = (new MerchantStore())->getSome(['mer_id' => $source_id]) ? array_column((new MerchantStore())->getSome(['mer_id' => $source_id])->toArray(), 'store_id') : [];
                    array_push($where, ['a.store_id', 'in', $store_ids]);
                } elseif ($source == 'store') {
                    array_push($where, ['a.store_id', '=', $source_id]);
                }
                $count = (new MallNewGroupAct())->getInfoCount($where,$field=true,$order='s.id asc',0, 0);
                if ($count['count'] > 0) {
                    $list = (new MallNewGroupAct())->getInfoCount($where, 's.*,p.name,p.price,p.image,p.store_id,a.start_time,p.stock_num,p.sale_num,a.start_time,a.end_time', '', $page, $pageSize);
                    foreach ($list['list'] as &$value) {
                        $value['start_date'] = date('Y-m-d', $value['start_time']);
                        $value['end_date'] = date('Y-m-d', $value['end_time']);
                        $tmpPicArr = explode(';', $value['image']);
                        foreach ($tmpPicArr as $k => $v) {
                            $value['pic_arr'][$k]['title'] = $v;
                            $value['pic_arr'][$k]['url'] = (new GoodsImageService())->getImageByPath($v);
                            $value['image_url'] = isset($value['pic_arr'][$k]['url']['image'])?replace_file_domain($value['pic_arr'][$k]['url']['image']):"";
                        }
                       
                        // 商城
                        $url = cfg('site_url') . get_base_url().'pages/shopmall_third/commodity_details?goods_id=' . $value["goods_id"];
                        
                        $value['image'] = replace_file_domain($value['image']);
                        $value['link_url'] = $url;
                    }
                }else{
                    $list['list']=[];
                }
                $list=$list['list'];
                $count=$count['count'];
                break;
            //砍价
            case 'bargain':
                $where = [
                    ["a.type",'=','bargain'],
                    [ 'a.start_time','<',time()],
                    [ 'a.end_time','>=',time()],
                    [ 'a.is_del','=',0],
                ];

                if ($keyword !== '') {
                    array_push($where, ['p.name', 'like', '%' . $keyword . '%']);
                }
                if ($source == 'merchant') {
                    $store_ids = (new MerchantStore())->getSome(['mer_id' => $source_id]) ? array_column((new MerchantStore())->getSome(['mer_id' => $source_id])->toArray(), 'store_id') : [];
                    array_push($where, ['a.store_id', 'in', $store_ids]);
                } elseif ($source == 'store') {
                    array_push($where, ['a.store_id', '=', $source_id]);
                }
                $count = (new MallNewBargainAct())->getInfoCount($where,$field="s.id",$order='s.id asc',0, 0);
                if ($count['count'] > 0) {
                    $list = (new MallNewBargainAct())->getInfoCount($where, 's.*,p.name,p.price,p.image,p.store_id,a.start_time,p.stock_num,p.sale_num', '', $page, $pageSize);
                    foreach ($list['list'] as &$value) {
                        $tmpPicArr = explode(';', $value['image']);
                        foreach ($tmpPicArr as $k => $v) {
                            $value['pic_arr'][$k]['title'] = $v;
                            $value['pic_arr'][$k]['url'] = (new GoodsImageService())->getImageByPath($v);
                            $value['image_url'] = replace_file_domain($value['pic_arr'][$k]['url']['image']);
                        }
                     
                        $url = cfg('site_url') . get_base_url().'pages/shopmall_third/commodity_details?goods_id=' . $value["goods_id"];
                        
                        $value['image'] = replace_file_domain($value['image']);
                        $value['link_url'] = $url;
                    }
                }else{
                    $list['list']=[];
                }
                $list=$list['list'];
                $count=$count['count'];
                break;
            //秒杀
            case 'limited':
                // $where['gd.status'] = 1;
                // if ($shop_type == 1) {
                //     $where['gd.goods_type'] = array('neq', 1);
                // }
                // //获取商城商品
                // if ($shop_type == 2) {
                //     $where['gd.cat_fid'] = array('gt', 0);
                // }
                // if ($keyword !== '') {
                //     $where['gd.name'] = array('like', '%' . $keyword . '%');
                // }
                // if ($source == 'merchant') {
                //     $store_ids = (new MerchantStore())->getSome(['mer_id' => $source_id]) ? array_column((new MerchantStore())->getSome(['mer_id' => $source_id])->toArray(), 'store_id') : [];
                //     $where['gd.store_id'] = array('in', $store_ids);
                // } elseif ($source == 'store') {
                //     $where['gd.store_id'] = $source_id;
                // }
                // $params = ['shop_type' => $shop_type, 'where' => $where, 'page_size' => $pageSize];
                // $info = invoke_cms_model('Shop_goods/get_list_by_storeid_diypage_skill_new', $params);
                // $list = $info['retval']['good_list'];
                // $count = $info['retval']['total'] ?: 0;
                $data = $this->getMallActInfo($source, $source_id, $keyword, $hdType, $page, $pageSize);
                // dd($data);
                $list = $data['list'];
                foreach($list as $key => $val)
                {
                    $list[$key]['id'] = $val['act_id'];
                    $list[$key]['name'] = $val['goods_name'];
                    $list[$key]['start_time'] = strtotime($val['start_time']);
                }
                $count = $data['total'];
                break;
        }
        }catch (\Exception $e){
          dd($e);
        }
        return ['list' => $list, 'total' => $count];
    }

    /**
     * @param $id
     * @return array
     * @throws \think\Exception
     * 商城组件获取活动数据
     */
    public function getMallActInfo($source, $source_id, $keyword, $hdType, $page, $pageSize)
    {
        $list = array();
        if (empty($source)) {
            throw new \think\Exception('参数缺失');
        }
        if ($hdType != 'periodic') {
            $where = [['act.status', '<>', 2], ['act.start_time', '<', time()], ['act.end_time', '>', time()], ['act.is_del', '=', 0], ['act.type', '=', $hdType]];
        } else {
            $where = [['act.status', '<>', 2], ['act.is_del', '=', 0], ['act.type', '=', $hdType]];
        }
        if ($keyword !== '') {
            $where[] = ['gd.name', 'like', '%' . $keyword . '%'];
        }
        if ($source == 'merchant') {
            $where[] = ['act.mer_id', '=', $source_id];
        } elseif ($source == 'store') {
            $where[] = ['act.store_id', '=', $source_id];
        }
        $limitedSku = new MallLimitedSku();
        $bargainSku = new MallNewBargainSku();
        $groupSku = new MallNewGroupSku();
        $groupTeam = new MallNewGroupTeam();
        $count = (new MallActivityDetail())->getActGoodsCount($where);
        if ($count > 0) {
            $list = (new MallActivityDetail())->getActGoods($where, 'act.act_id,act.name as act_name,act.start_time,act.end_time,gd.goods_id,gd.name as goods_name,gd.image,gd.price', '', $page, $pageSize);
            foreach ($list as &$value) {
                $value['image'] = replace_file_domain($value['image']);
                $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
                $value['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
                $value['link_url'] = cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $value['goods_id'];
                switch ($hdType) {
                    case 'group':
                        $value['orgin_price'] = $value['price'];
                        $value['act_price'] = 0.00;
                        //成团信息
                        $value['team_num'] = 0;//每个团需要多少人数
                        $value['nums'] = 0;
                        $sku_info = $groupSku->getSkuByActId1($value['act_id']);
                        if (!empty($sku_info)) {
                            $value['orgin_price'] = $sku_info['price'];
                            $value['act_price'] = $sku_info['act_price'];
                            $value['act_stock_num'] = $sku_info['act_stock_num'];
                            //成团信息
                            $group_where = ['act_id' => $value['act_id'], 'status' => 1];
                            $value['team_num'] = $sku_info['complete_num'];//每个团需要多少人数
                            $value['nums'] = $groupTeam->getGroupNumSucess($group_where);
                        }
                        break;
                    case 'bargain':
                        $value['sku_str'] = '';
                        $value['orgin_price'] = $value['price'];
                        $value['act_price'] = 0.00;
                        $value['bar_num'] = '';
                        $sku_info = $bargainSku->getSkuByActId1($value['act_id']);
                        if (!empty($sku_info)) {
                            $value['sku_str'] = $sku_info[0]['sku_str'];
                            $value['orgin_price'] = $sku_info[0]['price'];
                            $value['act_price'] = $sku_info[0]['act_price'];
                            $value['bar_num'] = $sku_info[0]['help_bargain_people_num'];
                            $value['act_stock_num'] = $sku_info[0]['act_stock_num'];
                        }
                        break;
                    case 'limited':
                        $value['orgin_price'] = $value['price'];
                        $value['act_price'] = 0.00;
                        $value['reduce_money'] = 0.00;
                        $value['act_stock_num'] = 0;
                        //计算总剩余库存
                        $value['act_stock_num'] = $limitedSku->getRestActStock($value['act_id'], $value['goods_id']);//库存是商品库存 不是单个sku库存
                        $sku_info = $limitedSku->getSkuByActId($value['act_id']);
                        if (!empty($sku_info)) {
                            $value['orgin_price'] = $sku_info['price'];
                            $value['act_price'] = $sku_info['act_price'];
                            $value['reduce_money'] = $sku_info['reduce_money'];
                            //计算总剩余库存
                            $value['act_stock_num'] = $limitedSku->getRestActStock($value['act_id'], $value['goods_id']);//库存是商品库存 不是单个sku库存
                        }
                        break;
                    case 'periodic':
                        break;
                }
            }
        }
        return ['list' => $list, 'total' => $count];
    }

    /**
     * @param $id
     * @return array
     * @throws \think\Exception
     * 商城商品列表
     */
    public function getMallGoods($source, $source_id, $keyword, $pageSize = 10, $params = [])
    {
        if (empty($source)) {
            throw new \think\Exception('参数缺失');
        }
        $where = array();
        $where[] = ['mg.is_del', '=', 0];
        $where[] = ['mg.status', '=', 1];
        $where[] = ['mg.stock_num', '<>', 0];
        $where[] = ['ms.status','<>',4];
        $where[] = ['m.status','<>',4];
        if(!empty($params['ids']) && is_array($params['ids'])){
            $where[] = ['mg.goods_id', 'IN', $params['ids']];
        }
        if(!empty($params['cat_id']) && !empty($params['level'])){
            if ($source == 'store') {
                $level = [
                    1 => 'mg.sort_first',
                    2 => 'mg.sort_second',
                    3 => 'mg.sort_third',
                ];
            }else{
                $level = [
                    1 => 'mg.cate_first',
                    2 => 'mg.cate_second',
                    3 => 'mg.cate_three',
                ];
            }
            $where[] = [$level[$params['level']], '=', $params['cat_id']];
        }
        if ($keyword !== '') {
            $where[] = ['mg.name|ms.name|m.name', 'like', '%' . $keyword . '%'];
        }
        if ($source == 'merchant') {
            $where[] = ['mg.mer_id', '=', $source_id];
        } elseif ($source == 'store') {
            $where[] = ['mg.store_id', '=', $source_id];
        }
        $list = (new MallGoods())->alias('mg')
            ->join('merchant_store ms', 'ms.store_id=mg.store_id')
            ->join('merchant m', 'm.mer_id=ms.mer_id')
            ->where($where)
            ->field('mg.goods_id,mg.name as goods_name,mg.price,mg.image,mg.update_time,ms.name as store_name,m.name AS mer_name')
            ->order('mg.goods_id DESC')
            ->paginate(min(50, $pageSize))
            ->each(function ($value) {
                $value['image'] = replace_file_domain($value['image']);
                $value['link_url'] = get_base_url('pages/shopmall_third/commodity_details?goods_id=' . $value['goods_id']);
            });
//            ->each(function ($value){
//                $value['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
//                $value['image'] = replace_file_domain($value['image']);
//                $value['link_url'] = cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $value['goods_id'];
//                $output = array();
//                //sku信息
//                $sku = (new MallGoodsSkuService())->getSkuByGoodsId($value['goods_id']);
//                $sku_types = (new MallGoodsSpecService())->getList($value['goods_id']);
//                $select_sku = isset($sku_info['sku_info']) ? explode('|', $sku_info['sku_info']) : [];
//                $select_sku_option = [];
//                if ($select_sku) {
//                    foreach ($select_sku as $sku_option) {
//                        $select_sku_option[$sku_option] = 1;
//                    }
//                }
//                foreach ($sku_types as $k1 => $v1) {
//                    $temp = [
//                        'id' => $v1['spec_id'],
//                        'title' => $v1['name'],
//                        'sons' => []
//                    ];
//                    foreach ($v1['val_list'] as $spec_val) {
//                        $temp['sons'][] = [
//                            'id' => $spec_val['id'],
//                            'title' => $spec_val['name'],
//                            'display' => 1,
//                            'select' => isset($select_sku_option[$v1['spec_id'] . ':' . $spec_val['id']]) ? 1 : 0
//                        ];
//                    }
//                    $output['sku']['types'][] = $temp;
//                }
//                foreach ($sku as $k2 => $v2) {
//                    $output['sku']['combination'][$v2['sku_info']] = [
//                        'sku_id' => $v2['sku_id'],
//                        'is_bargain' => 0,
//                        'price' => get_format_number($v2['price']),
//                        'old_price' => get_format_number($v2['price']),
//                        'stock' => $v2['stock_num'],
//                        'old_stock' => $v2['stock_num'],
//                        'image' => replace_file_domain($v2['image']),
//                        'prepare_deposit' => 0,//预售活动时使用
//                        'prepare_discount' => 0,//预售活动时使用
//                    ];
//                }
//                array_merge($value->toArray(), $output);
//            });
        return $list;
    }

    /**
     * @param $id
     * @return \think\Paginator
     * @throws \think\Exception
     * 商城商品分组
     */
    public function getMallGoodsGroup($source, $source_id, $keyword, $pageSize=10, $params = [])
    {
        $list = array();
        if (empty($source)) {
            throw new \think\Exception('参数缺失');
        }
        $where = array();
        if ($source == 'platform') {
            $where[] = ['status', '=', '1'];
            if ($keyword !== '') {
                $where[] = ['cat_name', 'like', '%' . $keyword . '%'];
            }
            if (!empty($params['ids'] && is_array($params['ids']))) {
                $where[] = ['cat_id', 'IN', $params['ids']];
            }
            $list = (new MallCategory())
            ->where($where)
            ->order('sort DESC')
            ->paginate(min(50, $pageSize))
            ->each(function ($value){
                $value['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                $goods_where = [['cate_first', 'exp', Db::raw(' = ' . $value['cat_id'] . ' or cate_second = ' . $value['cat_id'] . ' or cate_three = ' . $value['cat_id'])]];
                $goods_where[] = ['mg.is_del', '=', 0];
                $goods_where[] = ['mg.status', '=', 1];
                $goods_where[] = ['mg.stock_num', '<>', 0];
                $goods_where[] = ['ms.status', '<', 4];
                $goods_where[] = ['m.status', '<', 4];
                $value['goods_total_num'] = (new MallGoods())->alias('mg')
                    ->join('merchant_store ms', 'ms.store_id=mg.store_id')
                    ->join('merchant m', 'm.mer_id=ms.mer_id')
                    ->where($goods_where)
                    ->count();
//                $value['children'] = (new MallGoods)->getSome($goods_where, 'goods_id,name as goods_name,price,image,price', 'sort_platform DESC');
//                if (!empty($value['children'])) {
//                    foreach ($value['children'] as $k => &$v) {
//                        $value['children'][$k]['image'] = replace_file_domain($v['image']);
//                        $value['children'][$k]['link_url'] = cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $v['goods_id'];
//                        $output = array();
//                        //sku信息
//                        $sku = (new MallGoodsSkuService())->getSkuByGoodsId($v['goods_id']);
//                        $sku_types = (new MallGoodsSpecService())->getList($v['goods_id']);
//                        $select_sku = isset($sku_info['sku_info']) ? explode('|', $sku_info['sku_info']) : [];
//                        $select_sku_option = [];
//                        if ($select_sku) {
//                            foreach ($select_sku as $sku_option) {
//                                $select_sku_option[$sku_option] = 1;
//                            }
//                        }
//                        foreach ($sku_types as $k1 => $v1) {
//                            $temp = [
//                                'id' => $v1['spec_id'],
//                                'title' => $v1['name'],
//                                'sons' => []
//                            ];
//                            foreach ($v1['val_list'] as $spec_val) {
//                                $temp['sons'][] = [
//                                    'id' => $spec_val['id'],
//                                    'title' => $spec_val['name'],
//                                    'display' => 1,
//                                    'select' => isset($select_sku_option[$v1['spec_id'] . ':' . $spec_val['id']]) ? 1 : 0
//                                ];
//                            }
//                            $output['sku']['types'][] = $temp;
//                        }
//                        foreach ($sku as $k2 => $v2) {
//                            $output['sku']['combination'][$v2['sku_info']] = [
//                                'sku_id' => $v2['sku_id'],
//                                'is_bargain' => 0,
//                                'price' => get_format_number($v2['price']),
//                                'old_price' => get_format_number($v2['price']),
//                                'stock' => $v2['stock_num'],
//                                'old_stock' => $v2['stock_num'],
//                                'image' => replace_file_domain($v2['image']),
//                                'prepare_deposit' => 0,//预售活动时使用
//                                'prepare_discount' => 0,//预售活动时使用
//                            ];
//                        }
//                        $v = array_merge($v->toArray(), $output);
//                    }
//                }
            });
        } else {
            $where[] = ['status', '=', '1'];
            if ($keyword !== '') {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            if ($source == 'merchant') {
                $where[] = ['mer_id', '=', $source_id];
            } elseif ($source == 'store') {
                $where[] = ['store_id', '=', $source_id];
            }
            $list = (new MallGoodsSort())
                ->where($where)
                ->with(['merchant' => function($query){
                    $query->field('mer_id, name');
                }, 'merchant_store' => function($query){
                    $query->field('store_id, name');
                }])
                ->order('sort DESC')
                ->paginate(min(50, $pageSize))
                ->each(function ($value){
                    $value->mer_name = $value->merchant['name'] ?? '';
                    $value->store_name = $value->merchant_store['name'] ?? '';
                    unset($value->merchant, $value->merchant_store);
                    $value['cat_id']=$value['id'];
                    $value['cat_name']=$value['name'];
                    $value['cat_fid']=$value['fid'];
                    $goods_where = [['is_del', '=', 0],['status', '=', 1],['stock_num', '<>', 0],['sort_first', 'exp', Db::raw(' = ' . $value['id'] . ' or sort_second = ' . $value['id'] . ' or sort_third = ' . $value['id'])]];
                    $value['goods_total_num'] = (new MallGoods)->where($goods_where)->count();
//                    $value['children'] = (new MallGoods)->getSome($goods_where, 'goods_id,name as goods_name,image,price', 'sort_store DESC');
//                    if (!empty($value['children'])) {
//                        foreach ($value['children'] as $k => $v) {
//                            $value['children'][$k]['image'] = replace_file_domain($v['image']);
//                            $value['children'][$k]['link_url'] = cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $v['goods_id'];
//                        }
//                    }
                });
        }
        return $list;
    }

    /**
     * @param $id
     * @return array
     * @throws \think\Exception
     * 商城商品分组
     */
    public function getMallGoodsGroup1($source, $source_id, $keyword)
    {
        $list = array();
        if (empty($source)) {
            throw new \think\Exception('参数缺失');
        }
        $where = array();
        $where[] = ['gd.is_del', '=', 0];
        $where[] = ['gd.status', '=', 1];
        $where[] = ['gd.stock_num', '<>', 0];
        if ($source == 'platform') {
            $db = 'mall_category';
            $col = 'cat_id';
            if ($keyword !== '') {
                $where[] = ['cs.cat_name', 'like', '%' . $keyword . '%'];
            }
        } else {
            $db = 'mall_goods_sort';
            $col = 'sort_id';
            if ($keyword !== '') {
                $where[] = ['cs.name', 'like', '%' . $keyword . '%'];
            }
            if ($source == 'merchant') {
                $where[] = ['gd.mer_id', '=', $source_id];
            } elseif ($source == 'store') {
                $where[] = ['gd.store_id', '=', $source_id];
            }
        }
        $count = (new MallGoods())->getGoodsGroupCount($where, $db, $col);
        if ($count > 0) {
            $list = (new MallGoods())->getGoodsGroup($where, $db, $col, 'gd.goods_id,gd.name as goods_name,gd.image,cs.*', 'sort DESC');
            foreach ($list as &$value) {
                $value['image'] = replace_file_domain($value['image']);
                $value['link_url'] = cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $value['goods_id'];
            }
        }
        return ['list' => $list, 'total' => $count];
    }

    /**
     * @param $id
     * @return array
     * @throws \think\Exception
     * 外卖商品列表
     */
    public function getShopGoods($source, $source_id, $keyword, $pageSize, $params = [])
    {
        if (empty($source)) {
            throw new \think\Exception('参数缺失');
        }
        $where = array();
        $where[] = ['sg.status', '=', 1];
        $where[] = ['sg.stock_num', '<>', 0];
        $where[] = ['ms.status','=',1];
        $where[] = ['m.status','=',1];
        if ($keyword !== '') {
            $where[] = ['sg.name|m.name|ms.name', 'like', '%' . $keyword . '%'];
        }
        if(!empty($params['ids']) && is_array($params['ids'])){
            $where[] = ['sg.goods_id', 'IN', $params['ids']];
        }
        if ($source == 'merchant') {
            //获取店铺id
            $store_ids = (new MerchantStore())->getSome(['mer_id' => $source_id,'have_shop'=>1]) ? array_column((new MerchantStore())->getSome(['mer_id' => $source_id])->toArray(), 'store_id') : [];
            $where[] = ['sg.store_id', 'in', $store_ids];
        } elseif ($source == 'store') {
            //查询店铺是否有外卖业务
            $haveShop = (new MerchantStore())->where(['store_id' => $source_id])->field('have_shop')->find();
            $haveShop['have_shop'] = $haveShop['have_shop']??0;
            if(!$haveShop['have_shop']){
                $source_id = -1;
            }
            $where[] = ['sg.store_id', '=', $source_id];
        }
        $list = (new ShopGoods())->alias('sg')
            ->join('merchant_store ms','ms.store_id=sg.store_id')
            ->join('merchant m','m.mer_id=ms.mer_id');
            if(!empty($params['cat_id'])){
                $list =$list->join('shop_goods_group_list g',' sg.goods_id = g.goods_id');
                $where[] = ['g.group_id', '=', $params['cat_id']];
            }
        $list =$list
            ->field('sg.goods_id,sg.name as goods_name,sg.image,sg.price,sg.last_time,sg.store_id,ms.name AS store_name,m.name AS mer_name')
            ->where($where)
            ->order('sg.goods_id DESC')
            ->paginate(min(50,$pageSize))
            ->each(function ($value){
                $value['last_time'] = date('Y-m-d H:i:s', $value['last_time']);
                $value['image'] = replace_file_domain(explode(';',$value['image'])[0]);
                $value['link_url'] = cfg('site_url') . '/wap.php?c=Shop&a=classic_good&shop_id=' . $value['store_id'] . '&good_id=' . $value["goods_id"];
            });
        
        return $list;
    }

    /**
     * @param $id
     * @return \think\Paginator
     * @throws \think\Exception
     * 外卖商品分组
     */
    public function getShopGoodsGroup($source, $source_id, $keyword, $pageSize, $params = [])
    {
        if ($source == 'platform') {
            $where = [];
        } elseif ($source == 'merchant') {
            //获取店铺id
            $store_ids = (new MerchantStore())->getSome(['mer_id' => $source_id]) ? array_column((new MerchantStore())->getSome(['mer_id' => $source_id])->toArray(), 'store_id') : [];
            $where[] = ['store_id', 'in', $store_ids];
        } elseif ($source == 'store') {
            $where[] = ['store_id', '=', $source_id];
        }

        if ($keyword !== '') {
            $where[] = ['name', 'like', '%' . $keyword . '%'];
        }
        if (!empty($params['ids']) && is_array($params['ids'])) {
            $where[] = ['id', 'in', $params['ids']];
        }
        $list = (new ShopGoodsGroup())
            ->where($where)
            ->order('id DESC')
            ->paginate($pageSize)
            ->each(function ($value){
                $where = [
                    ['l.group_id', '=', $value['id']],
                    ['g.goods_type', '<>', 1]
                ];
                $field = 'l.id,l.goods_id,g.name as goods_name,g.image,g.price,g.store_id';
                $value['goods_total_num'] = (new ShopGoodsGroupList())
                    ->alias('l')
                    ->join('shop_goods g', ' l.goods_id = g.goods_id')
                    ->where($where)
                    ->count();
                
               $goods_list = (new ShopGoodsGroupList())->getGoods($where, $field);
               foreach ($goods_list as &$_goods) {
                   $_goods['image'] = replace_file_domain(explode(';', $_goods['image'])[0]);
                   $_goods['link_url'] = cfg('site_url') . '/wap.php?c=Shop&a=classic_good&shop_id=' . $_goods['store_id'] . '&good_id=' . $_goods["goods_id"];
               }
               $value['children'] = $goods_list;
            });
        
        return $list;
    }

    /**
     * @param $source
     * @param $source_id
     * @return array[]
     * 商家或店铺链接库
     */
    public function getMerOrStoreLink($source, $source_id)
    {
        if ($source == 'platform') {
            $arr = [
                [
                    'name' => '微页面',
                    'children' => [
                        'type' => 'pop',
                        'list' => [
                            [
                                'name' => '微页面',
                                'url' => ''
                            ]
                        ]
                    ],
                ],
                [
                    'name' => '平台功能库',
                    'children' => [
                        'type' => 'pop',
                        'list' => [
                            [
                                'name' => '平台功能库',
                                'url' => ''
                            ]
                        ]
                    ],
                ],
                [
                    'name' => '其他',
                    'children' => [
                        'type' => 'pop',
                        'list' => [
                            [
                                'name' => '自定义链接',
                                'url' => ''
                            ]
                        ]
                    ],
                ]
            ];
        } elseif ($source == 'merchant') {
            $arr = [
                [
                    'name' => '微页面',
                    'children' => [
                        'type' => 'pop',
                        'list' => [
                            [
                                'name' => '微页面',
                                'url' => ''
                            ]
                        ]
                    ],
                ],
                [
                    'name' => '配套工具',
                    'children' => [
                        'type' => 'select',
                        'list' => [
                            [
                                'name' => '扫一扫',
                                'url' => 'scan'
                            ],
                            [
                                'name' => '餐饮-自取店铺列表',
                                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=selftake'
                            ],
                            [
                                'name' => '餐饮-店铺列表',
                                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList'
                            ],
                            [
                                'name' => '餐饮-在线预定店铺列表',
                                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=book'
                            ],
                            [
                                'name' => '餐饮-通用码店铺列表',
                                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=inhouse'
                            ],
                            [
                                'name' => '买单',
                                'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=pay&mer_id=' . $source_id
                            ],
                            [
                                'name' => '商家客服',
                                'url' => cfg('site_url') . '/packapp/im/index.html#/chatInterface?from_user=user_39353&to_user=store_2_51&relation=user2store&from=meal'
                            ]
                        ]
                    ],
                ],
                [
                    'name' => '营销工具',
                    'children' => [
                        'type' => 'select',
                        'list' => [
                            [
                                'name' => '会员卡',
                                'url' => cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id=' . $source_id
                            ],
                            [
                                'name' => '领券中心',
                                'url' => cfg('site_url') . '/packapp/plat/pages/coupon/storeCoupon?mer_id=' . $source_id
                            ],
                        ]
                    ],
                ],
                [
                    'name' => '功能页面',
                    'children' => [
                        'type' => 'select',
                        'list' => [
                            [
                                'name' => '商家主页',
                                'url' => '装修主页待定'
                            ],
                            [
                                'name' => '个人中心',
                                'url' => cfg('site_url') . '/wap.php?c=My&a=index'
                            ]
                        ]
                    ],
                ],

                [
                    'name' => '其他',
                    'children' => [
                        'type' => 'pop',
                        'list' => [
                            [
                                'name' => '自定义链接',
                                'url' => ''
                            ]
                        ]
                    ],
                ]
            ];

        } elseif ($source == 'store') {
            $arr = [
                [
                    'name' => '微页面',
                    'children' => [
                        'type' => 'pop',
                        'list' => [
                            [
                                'name' => '微页面',
                                'url' => ''
                            ]
                        ]
                    ],
                ],
                [
                    'name' => '配套工具',
                    'children' => [
                        'type' => 'select',
                        'list' => [
                            [
                                'name' => '扫一扫',
                                'url' => 'scan'
                            ],
                            [
                                'name' => '餐饮-自取店铺列表',
                                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=selftake'
                            ],
                            [
                                'name' => '餐饮-店铺列表',
                                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList'
                            ],
                            [
                                'name' => '餐饮-在线预定店铺列表',
                                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=book'
                            ],
                            [
                                'name' => '餐饮-通用码店铺列表',
                                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=inhouse'
                            ],
                            [
                                'name' => '买单',
                                'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=pay&mer_id=' . $source_id
                            ],
                            [
                                'name' => '商家客服',
                                'url' => cfg('site_url') . '/packapp/im/index.html#/chatInterface?from_user=user_39353&to_user=store_2_51&relation=user2store&from=meal'
                            ]
                        ]
                    ],
                ],
                [
                    'name' => '营销工具',
                    'children' => [
                        'type' => 'select',
                        'list' => [
                            [
                                'name' => '会员卡',
                                'url' => cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id=' . $source_id
                            ],
                            [
                                'name' => '领券中心',
                                'url' => cfg('site_url') . '/packapp/plat/pages/coupon/storeCoupon?mer_id=' . $source_id
                            ],
                        ]
                    ],
                ],
                [
                    'name' => '功能页面',
                    'children' => [
                        'type' => 'select',
                        'list' => [
                            [
                                'name' => '店铺首页',
                                'url' => '装修主页待定'
                            ],
                            [
                                'name' => '个人中心',
                                'url' => cfg('site_url') . '/wap.php?c=My&a=index'
                            ]
                        ]
                    ],
                ],

                [
                    'name' => '其他',
                    'children' => [
                        'type' => 'pop',
                        'list' => [
                            [
                                'name' => '自定义链接',
                                'url' => ''
                            ]
                        ]
                    ],
                ]
            ];
        } else {
            throw new \think\Exception('参数错误');
        }
        return $arr;
    }
    
    /**
     * 查询外卖店铺
     */
    public function getStore($source, $source_id)
    {
        if ($source == 'platform') {
            $where = [];
        } elseif ($source == 'merchant'){
            $where[] = ['s.mer_id', '=', $source_id];
        } elseif ($source == 'store') {
            $where[] = ['ms.store_id', '=', $source_id];
        }else{
            throw new \think\Exception('位置查询来源');
        }
        $where[] = ['s.status','=',1];
        $where[] = ['s.have_shop','=',1];
        $data = (new MerchantStore())->getStore('merchant_store_shop',$where,'s.store_id,s.name');
        return $data;
    }
}