<?php
/**
 * 地址改版service
 * Author: zhumengqun
 * Date Time: 2020/08/27
 */

namespace app\common\model\service\address;


use app\common\model\db\Area;
use app\common\model\db\MerchantStore;
use app\common\model\db\UserScoreList;
use app\common\model\service\address\NationalPhoneService;
use app\common\model\db\UserAdress;
use app\common\model\service\AreaService;
use app\common\model\service\UserService as UserService;
use app\mall\model\db\MallDelivery;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsArea;
use app\mall\model\service\MallDeliveryService;
use map\Coordinate;

class AddressService
{
    /**
     * 展示地址列表
     * @param $uid
     * @return array
     */
    public function getAllList($log_uid,$goods_id=[],$is_get_mall_area=0)
    {
        //验证登录
        if (empty($log_uid)) {
            throw new \think\Exception("请传递用户UID参数", 1002);
        }
        /*if(cfg('open_mall_order_area')==1&&$is_get_mall_area&&(!$goods_id||!is_array($goods_id))){
            throw new \think\Exception(L_("商品信息有误！"));
        }*/
        //获取省市区名称
        $area = new AreaService();
        //获取当前登录的用户地址信息
        $userAddress = new UserAdress();
        $addrList = $userAddress->getAdress(true, ['uid' => $log_uid]);
        if (!empty($addrList)) {
            foreach ($addrList as $key => $val) {
                //通过区域id 获取区域名称
                $province_name = $area->getAreaByAreaId($val['province'])['area_name'] ?? '';
                $city_name = $area->getAreaByAreaId($val['city'])['area_name'] ?? '';
                $area_name = $area->getAreaByAreaId($val['area'])['area_name'] ?? '';
                //判断地址显示格式
                if (!cfg('open_multilingual')) {
                    if($city_name&&strpos($val['adress'], $city_name)!==false){
                        $val['address_txt'] = $val['adress'] . $val['detail'];
                    }else{
                        $val['address_txt'] = $province_name . $city_name . $area_name . $val['adress'] . $val['detail'];
                    }
                } else if (cfg('country_code') == 'au') {
                    $val['address_txt'] = trim(($val['detail'] ? $val['detail'] : '') . $val['adress'] . $area_name . $province_name . $val['zip_code']);
                } else {
                    if($city_name&&strpos($val['adress'], $city_name)!==false){
                        $val['address_txt'] = trim(($val['detail'] ? $val['detail'] : '') . $val['adress']);
                    }else {
                        $val['address_txt'] = trim(($val['detail'] ? $val['detail'] : '') . $val['adress'] . $area_name . $city_name);
                    }
                }
                $arr[] = array(
                    'name' => $val['name'],
                    'address_id' => $val['adress_id'],
                    'address_txt' => $val['address_txt'],
                    'detail' => $val['detail'],
                    'defaults' => $val['default'],
                    'tag' => $val['tag'] ?: '',
                    'lng' => $val['longitude'],
                    'lat' => $val['latitude'],
                    'phone' => $val['phone'],
                    'phone_country_type' => $val['phone_country_type'] ? $val['phone_country_type'] : '',
                    'phone_txt' => $val['phone_country_type'] ? '+' . $val['phone_country_type'] . ' ' . $val['phone'] : $val['phone'],
                    'sex' => $val['sex'],
                    'province' => $val['province'],
                    'city' => $val['city'],
                    'area' => $val['area'],
                    'is_out_range' => 0,
                    'note' => '',
                );
            }
            if(cfg('open_mall_order_area')==1&&$is_get_mall_area&&$arr){
                $range_arr = $out_range = [];
                foreach ($arr as $item){
                    $is_out_range = 0;
                    $goods_area_id = [];
                    $goods_area_id[] = 0;
                    if($item['area']>0){
                        $goods_area_id[] = $item['area'];
                    }
                    if($item['city']>0){
                        $goods_area_id[] = $item['city'];
                        if($item['area']<=0){
                            $c_area_ids = (new Area())->where(['area_pid'=>$item['city'],'is_open'=>1])->column('area_id');
                            $goods_area_id = array_merge($goods_area_id,$c_area_ids);
                        }
                    }
                    if($item['province']>0){
                        $goods_area_id[] = $item['province'];
                        if($item['city']<=0){
                            $c_city_ids = (new Area())->where(['area_pid'=>$item['province'],'is_open'=>1])->column('area_id');
                            $goods_area_id = array_merge($goods_area_id,$c_city_ids);
                            foreach ($c_city_ids as $c_city_ids_val){
                                $c_city_child_ids = (new Area())->where(['area_pid'=>$c_city_ids_val,'is_open'=>1])->column('area_id');
                                $goods_area_id = array_merge($goods_area_id,$c_city_child_ids);
                            }
                        }
                    }
                    if($goods_id){
                        foreach ($goods_id as $val){
                            //获取商品服务范围
                            $goods_area = (new MallGoodsArea())->where([['goods_id','=',$val],['area_id','in',$goods_area_id]])->findOrEmpty()->toArray();
                            if(!$goods_area){
                                $is_out_range = 1;
                                $output['address']['note'] = '超出可服务范围';
                                break;
                            }
                        }
                    }else{
                        $goods_area = (new MallGoodsArea())->where([['area_id','in',$goods_area_id]])->findOrEmpty()->toArray();
                        if(!$goods_area){
                            $is_out_range = 1;
                            $output['address']['note'] = '超出可服务范围';
                        }
                    }
                    
                    if($is_out_range){
                        $item['is_out_range'] = 1;
                        $item['note'] = '超出可服务范围';
                        $out_range[] = $item;
                    }else{
                        $range_arr[] = $item;
                    }
                }
                $arr = array_merge($range_arr,$out_range);
            }elseif(cfg('open_mall_order_area')==2&&$is_get_mall_area&&$arr){
                $in_range = $out_range = [];
                if($goods_id){
                    $store_ids = (new MallGoods())->alias('g')
                        ->join('merchant_store s','g.store_id = s.store_id and s.status = 1')
                        ->join('merchant_store_mall ms','s.store_id = ms.store_id')
                        ->where([['g.goods_id','in',$goods_id],['g.status','=',1]])
                        ->group('g.store_id')->column('g.store_id,s.long,s.lat,ms.is_zt,ms.is_delivery');
                }else{
                    $store_ids = (new MallDeliveryService())->getStoreIds();
                }
                foreach ($arr as $item) {
                    $range_res = (new MallDeliveryService())->checkRange($item,$store_ids);
                    if($goods_id){
                        if($range_res['is_out_range']==1){
                            $item['is_out_range'] = 1;
                            $item['note'] = '超出可服务范围';
                            $out_range[] = $item;
                        }else{
                            $in_range[] = $item;
                        }
                    }else{
                        if($range_res['is_in_range']==1){
                            $in_range[] = $item;
                        }else{
                            $item['is_out_range'] = 1;
                            $item['note'] = '超出可服务范围';
                            $out_range[] = $item;
                        }
                    }
                }
                $arr = array_merge($in_range,$out_range);
            }
            if (!empty($arr)) {
                return $arr;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }


    /**
     * 编辑地址
     * @param $address_id
     * @return array
     */
    public function getEditAddr($log_uid, $address_id = '')
    {
        //验证登录
        if (empty($log_uid)) {
            throw new \think\Exception("请传递用户UID参数", 1002);
        }
        if (!empty($address_id)) {
            $userAddress = new UserAdress();
            $addrNow = $userAddress->getAdress(true, ['uid' => $log_uid, 'adress_id' => $address_id]);
            if (!empty($addrNow)) {
                $arr = [
                    'name' => $addrNow[0]['name'],
                    'address_id' => $addrNow[0]['adress_id'],
                    'detail' => $addrNow[0]['detail'],
                    'address' => $addrNow[0]['adress'],
                    'defaults' => $addrNow[0]['default'],
                    'tag' => $addrNow[0]['tag'] ?: '',
                    'lng' => $addrNow[0]['longitude'],
                    'lat' => $addrNow[0]['latitude'],
                    'phone' => $addrNow[0]['phone'],
                    'phone_country_type' => $addrNow[0]['phone_country_type'] ? $addrNow[0]['phone_country_type'] : '',
                    'sex' => $addrNow[0]['sex'],
                ];
                if(request()->param('coordinate_type') == 'wgs84'){
                    list($arr['lng'], $arr['lat']) = Coordinate::bd09ToWgs84($arr['lng'], $arr['lat']);
                }
                if (!empty($arr)) {
                    return $arr;
                } else {
                    throw new \think\Exception("未查到相关信息", 1003);
                }
            } else {
                throw new \think\Exception("未查到相关信息", 1003);
            }

        } else {
            throw new \think\Exception("参数 address_id 有误", 1003);
        }
    }

    /**
     * 获取手机区号
     * @param
     * @return array
     */
    public function getAreaCode()
    {
        $areaCode = new NationalPhoneService();
        $code = $areaCode->getAreaCode();
        return $code;

    }

    /**
     * 获取搜索地址
     * @param uid
     * @return array
     */
    /* public function getMapAddr()
    {

    }*/


    /**
     * 编辑保存或新增保存地址
     * @param
     * @return array
     */
    public function addOrEditAddr($arr)
    {
        //验证登录
        if (empty($arr['uid'])) {
            throw new \think\Exception("请传递用户UID参数", 1002);
        }
        if (request()->param('coordinate_type') == 'wgs84') {
            list($arr['longitude'], $arr['latitude']) = Coordinate::wgs84ToBd09($arr['longitude'], $arr['latitude']);
        }

        $addOredit = new UserAdress();
        if ($arr['name'] || $arr['adress'] || $arr['latitude'] || $arr['longitude'] || $arr['phone'] || $arr['sex']) {
            //根据经纬度解析出省市区id
            $autoAddr = new AutoAddressService();
            $now_city = $autoAddr->cityMatching($arr['latitude'], $arr['longitude']);
            fdump_api(['addOrEditAddr' => $now_city],'address/cityMatching',true);
            if (!cfg('open_multilingual')) {
                if (empty($now_city['area_info']['city_id'])) {
                    throw new \think\Exception('城市未查找到');
                }
                if (empty($now_city['area_info']['province_id'])) {
                    throw new \think\Exception('省/州未查找到');
                }
            }
            $arr['province'] = $now_city['area_info']['province_id'];
            $arr['city'] = $now_city['area_info']['city_id'];
            $arr['area'] = $now_city['area_info']['area_id'];
            $arr['last_time'] = time();
            if ($arr['default'] == 1) {
                //当前设为默认时取消用户其他默认
                $res = $addOredit->updateDefault($arr['uid']);
                if ($res === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
            }
            
            $arr['province'] = $arr['province'] ?? 0;
            $arr['city'] = $arr['city'] ?? 0;
            $arr['area'] = $arr['area'] ?? 0;

            if (!empty($arr['adress_id'])) {
                //编辑
                $where = [
                    'adress_id' => $arr['adress_id'],
                    'uid' => $arr['uid'],
                ];
                $check_addr = $addOredit->getAdress(true, $where);
                if(!$check_addr){
                    throw new \think\Exception('地址不存在！');
                }
                $result = $addOredit->edit1($where, $arr);
            } else {
                //保存
                $result = $addOredit->add1($arr);
            }
            if ($result !== false) {
                if(empty($arr['adress_id'])){
                    $score = (new UserScoreList())->where(['score_type'=>3,'uid'=>$arr['uid']])->findOrEmpty()->toArray();
                    if (!$score&&cfg('score_bind_adderss')>0) { // 填写地址只能获一次积分
                        $res = (new UserService())->addScore($arr['uid'], cfg('score_bind_adderss'),'填写地址获得'.cfg('score_name'),0,['score_type'=>3]);
                        fdump_api([$res,$arr['uid'], cfg('score_bind_adderss'),'填写地址获得'.cfg('score_name'),0,['score_type'=>3]],'score_bind_adderss',1);
                    }
                }
                return ['address_id' => $addOredit->adress_id];
            } else {
                throw new \think\Exception('操作失败，请重试');
            }
        } else {
            throw new \think\Exception("参数缺失", 1003);
        }

    }

    /**
     * 删除地址
     * @param $address_id
     * @return array
     */
    public function delAddr($log_uid, $address_id)
    {
        //验证登录
        if (empty($log_uid)) {
            throw new \think\Exception("请传递用户UID参数", 1002);
        }
        if (!empty($address_id)) {
            $where = ['adress_id' => $address_id, 'uid' => $log_uid];
            $del = new UserAdress();
            $result = $del->del1($where);
            if ($result !== false) {
                return true;
            } else {
                throw new \think\Exception('操作失败，请重试');
            }
        } else {
            throw new \think\Exception('参数缺失', 1002);
        }

    }
}
