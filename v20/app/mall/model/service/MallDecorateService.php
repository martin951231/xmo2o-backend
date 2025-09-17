<?php
/**
 * MallDecorateService.php
 * 商城装修service
 * Create on 2020/9/25 10:48
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\common\model\db\CardNewCoupon;
use app\common\model\service\address\AutoAddressService;
use app\common\model\service\GoodsScoreService;
use app\common\model\service\MerchantUserRelationService;
use app\mall\model\db\CustomerService;
use app\mall\model\db\MallActivity;
use app\mall\model\db\MallGoodReply;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsSort;
use app\mall\model\db\MallLimitedAct;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallMerchantStoreDecorate;
use app\mall\model\db\MallMerchantStoreDecorateField;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallNewGroupTeam;
use app\mall\model\db\MallNewPeriodicPurchase;
use app\mall\model\db\MallPrepareActSku;
use app\mall\model\db\MerchantStoreKefu;
use app\mall\model\db\MerchantStoreMall;
use map\longLat;
use think\facade\Db;

class MallDecorateService
{
    public function __construct()
    {
        $this->decorateModel = new MallMerchantStoreDecorate();
        $this->decorateFieldModel = new MallMerchantStoreDecorateField();
    }

    /**
     * 获取装修商品
     * @param $param
     * @return array
     */
    public function getGoodsList($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception('缺少store_id参数');
        }
        $where = [['store_id', '=', $param['store_id']], ['status', '=', 1], ['is_del', '=', 0]];
        if (!empty($param['keyword'])) {
            array_push($where, ['name', 'like', '%' . $param['keyword'] . '%']);
        }
        $field = 'goods_id,name as goods_name,update_time';
        $order = 'update_time DESC';
        $mallGoodsService = new MallGoodsService();
        $list = $mallGoodsService->getGoodsByCondition($field, $order, $where, $param['page'], $param['pageSize']);
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                if (in_array($val['goods_id'], explode(',', $param['selectedItems']))) {
                    $list[$key]['selected'] = 1;
                } else {
                    $list[$key]['selected'] = 0;
                }
            }
            $list['count'] = $mallGoodsService->getCountByCondition($where);
            return $list;
        } else {
            return [];
        }
    }

    /**
     * 获取装修分类
     * @param $param
     * @return string
     */
    public function getStoreSort($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception('缺少store_id参数');
        }
        $where = [['store_id', '=', $param['store_id']], ['status', '=', 1]];
        if (!empty($param['keyword'])) {
            array_push($where, ['name', 'like', '%' . $param['keyword'] . '%']);
        }
        $field = 'id,name as sort_name';
        $order = 'id DESC';
        $mallGoodsSortService = new MallGoodsSortService();
        $list = $mallGoodsSortService->getSortByCondition($field, $order, $where, $param);
        return $list;
    }

    /**
     * 获取某个活动下的所有商品
     * @param $param
     * @return array
     */
    public function getActGoodsList($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception('缺少store_id参数');
        }
        if (empty($param['act_type'])) {
            throw new \think\Exception('缺少act_type参数');
        }
        //获取活动表id
        $where = [['store_id', '=', $param['store_id']], ['status', '=', 1], ['is_del', '=', 0], ['type', '=', $param['act_type']]];
        $field = 'id,start_time,end_time';
        $mallActivityService = new MallActivityService();
        $arr = $mallActivityService->getActInfo($where, $field);
        $gfield = 'g.goods_id,g.name as goods_name,g.image';
        $mallGoodsService = new MallGoodsService();
        $count = 0;
        if (!empty($arr)) {
            foreach ($arr as $val) {
                $gwhere = ['g.status' => 1, 'g.is_del' => 0, 'd.activity_id' => $val['id']];
                $tmp_list = $mallGoodsService->getActGoods($gwhere, $gfield);
                if (!empty($tmp_list)) {
                    foreach ($tmp_list as $kk => $vv) {
                        if (in_array($vv['goods_id'], explode(',', $param['selectedItems']))) {
                            $tmp_list[$kk]['selected'] = 1;
                        } else {
                            $tmp_list[$kk]['selected'] = 0;
                        }
                    }
                }
                $list[] = $tmp_list;
                $count += $mallGoodsService->getActGoodsCount($gwhere);
            }
        }
        if (!empty($list)) {
            $list['count'] = $count;
            return $list;
        } else {
            return [];
        }
    }

    public function addOrEditPage($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception('缺少store_id参数');
        }
        $data_page_field['mer_id'] = $param['mer_id'];
        $data_page_field['store_id'] = $param['store_id'];
        $data_page_field['page_id'] = $param['page_id'];
        $data_page['page_name'] = $param['page_name'];
        $data_page['page_desc'] = $param['page_desc'];
        $data_page['bgcolor'] = $param['bgcolor'];
        $data_page['create_time'] = time();
        $data_page['mer_id'] = $param['mer_id'];
        $data_page['store_id'] = $param['store_id'];
        // 启动事务
        Db::startTrans();
        try {
            if (empty($param['page_id'])) {
                $result1 = $this->decorateModel->addPage($data_page);
                $param['page_id'] = $result1;
                $result2 = true;
            } else {
                $pageWhere = ['page_id' => $param['page_id']];
                $result1 = $this->decorateModel->updatePage($pageWhere, $data_page);
                //先删除之前的所有装修
                $pageFieldWhere = ['page_id' => $param['page_id']];
                $data_diypage_field['page_id'] = $param['page_id'];
                $result2 = $this->decorateFieldModel->delFieldPage($pageFieldWhere);
            }
            //添加新的装修
            foreach ($param['custom'] as $key => $val) {
                $data_page_field['field_type'] = $val['type'];
                $data_page_field['page_id'] = $param['page_id'];
                unset($param['custom'][$key]['type']);
                $data_page_field['content'] = serialize($param['custom']);
                $result3 = $this->decorateFieldModel->addFieldPage($data_page_field);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        if ($result1 === false || $result2 === false || $result3 === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 获取店铺装修信息
     * @param $param
     */
    public function createPage($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception('缺少store_id参数');
        }
        //首次装修显示标题（没装修过的店铺需要给出初始样式）
        $page_id = 0;
        //通过store_id 获取 page_id
        $pageArr = $this->decorateModel->getPage($param['store_id']);
        if (!empty($pageArr['page_id'])) {
            $fieldWhere = ['page_id' => $pageArr['page_id']];
            $now_page_custom = $this->decorateFieldModel->getFieldPage($fieldWhere);
            if (!empty($now_page_custom)) {
                foreach ($now_page_custom as $key => $value) {
                    $content_arr = unserialize($value['content']);
                    $new_content_arr = $this->reg_image($content_arr);
                    $now_page_custom[$key]['content'] = $new_content_arr;
                    if ($value['field_type'] == 'rich_text') {
                        $now_page_custom[$key]['content'] = str_replace("font-family:'Microsoft YaHei';", '', $now_page_custom[$key]['content']);
                        $now_page_custom[$key]['content'] = str_replace("'allowfullscreen'", '"allowfullscreen"', $now_page_custom[$key]['content']);
                        $now_page_custom[$key]['content'] = str_replace("font-family:&quot;", '', $now_page_custom[$key]['content']);
                        $now_page_custom[$key]['content'] = str_replace("&quot;", '', $now_page_custom[$key]['content']);
                    }
                }
                $pageArr = array_merge($pageArr, $now_page_custom);
            }
            return $pageArr;
        }
        return [];
    }

    //格式化图片格式
    public function reg_image($content_arr)
    {
        if (is_array($content_arr)) {
            foreach ($content_arr as $k => $v) {
                if (is_array($v)) {
                    $content_arr[$k] = $this->reg_image($v);
                } elseif ($k == 'image' || $k == 'img') {
                    $content_arr[$k] = replace_file_domain($v);
                } elseif ($k == 'content') {
                    $content_arr[$k] = replace_file_domain_content($v);
                }
            }
        }
        return $content_arr;
    }

    /**
     * @param $store_id
     * @return array
     * @throws \think\Exception
     * 首页装修（用户端接口）
     */
    public function getStoreDecorate($store_id, $uid,$app_type='')
    {
        if (empty($store_id)) {
            throw new \think\Exception('缺少store_id参数');
        }
        $now_store = (new MerchantStoreService())->getOne($store_id);
        if (empty($now_store)) {
            throw new \think\Exception('店铺不存在');
        }
        $basic_info = $this->getStoreBaseInfo($store_id, $uid);
        $return = $basic_info; //返回数据

        //商城店铺公告和资质
        $storeMall = (new \app\mall\model\db\MerchantStoreMall())->where('store_id', $store_id)->find();
        $return['store_notice'] = $storeMall ? $storeMall->store_notice : '';
        $return['business_qualifications'] = $now_store['id_image'] ? get_base_url('pages/store/v1/businessLicense/index?store_id=' . $store_id, true) : '';

        //店铺自定义主页
        $return['set_diypage'] = false;
        $return['diypage_bgcolor'] = '';
        $return['diypage_content'] = array();
        $where = ['store_id' => $store_id];
        $return['wxapp_share_title'] = $now_store['share_title'] ?: '';
        $return['wxapp_share_subtitle'] = $now_store['share_subtitle'] ?: '';
        $return['wxapp_share_pic'] = $now_store['share_image'] ? thumb($now_store['share_image'], 500, 400, 'fill') : '';
        //店铺经纬度
        $longlat = new longlat();
        $tmpGcj02 = $longlat->baiduToGcj02($now_store['lat'], $now_store['long']);
        $now_store['gcj02_long'] = $tmpGcj02['lng'];
        $now_store['gcj02_lat'] = $tmpGcj02['lat'];
        $return['gcj02_long'] = $tmpGcj02['lng'];
        $return['gcj02_lat'] = $tmpGcj02['lat'];
        $diy_info = $this->decorateModel->getStoreDecorate($where);
        if (!empty($diy_info)) {
            $return['set_diypage'] = true;
            $return['diypage_bgcolor'] = $diy_info['bgcolor'];
            $return['diypage_content'] = $this->getStoreDiypage($diy_info['page_id'], $now_store, $diy_info);
            
            //如果自定义页面内容为空数组，也返回未设置首页。
            if(!$return['diypage_content']){
                $return['set_diypage'] = false;
            }
        } else {
            $return['set_diypage'] = false;
        }
        
        //等新版自定义页面处理好，再来处理店铺首页自定义部分。避免走到老版商城的逻辑
        $return['set_diypage'] = false;

        $return['share_data'] = [
            'title'=>$now_store['share_title']?:($now_store['name']?:'分享店铺'),
            'content'=>$now_store['share_subtitle']?:'店铺:'.$now_store['name'],
            'image'=>empty($now_store['share_image'])?(empty($now_store['logo'])?cfg('site_url')."/v20/public/static/mall/mall_store_share.png":replace_file_domain($now_store['logo'])):replace_file_domain($now_store['share_image']),
            'url'=>cfg('site_url')."/packapp/plat/pages/shopmall_third/store_home/index?store_id=".$store_id.'&type=share',
        ];
        if(!empty($app_type) && $app_type=='wxapp'){
            $return['share_data']['url']="/pages/shopmall_third/store_home/index?store_id=".$store_id."&type=share";
        }
        //增加店铺浏览量
        (new MerchantStoreMall())->where(['store_id'=>$store_id])->inc('hits',1)->update();
        return $return;
    }

    /**
     * @param $param
     * @return mixed
     * @throws \think\Exception
     * 获取城市名称id
     */
    public function getLocationCity($param){
        $autoAddr = new AutoAddressService();
        $now_city = $autoAddr->cityMatching($param['lat'], $param['long']);
        $arr['area_id'] = $now_city['area_info']['city_id'];
        $arr['area_name'] = $now_city['area_info']['city_name'];
        return $arr;
    }
    /**
     * @desc获取店铺基础信息
     * @param $store_id
     * @return array
     */
    private function getStoreBaseInfo($store_id, $uid)
    {
        $where = ['store_id' => $store_id];
        $now_store = (new MerchantStoreService())->getOne($store_id);
        if (empty($now_store)) {
            throw new \think\Exception('该店铺暂未完善信息，无法使用');
        }
        //收藏状态获取
        $store_collect = false;
        $_collect = (new MerchantUserRelationService())->getDataMerUserRel(['store_id' => $store_id, 'uid' => $uid, 'type' => 'mall']);
        if ($_collect) {
            $store_collect = true;
        }
        //获取背景图
        $images = $now_store['pic_info'];
        if (!empty($images)) {
            $image = explode(';', $images) ? explode(';', $images)[0] : '';
        }
        $return = array();
        $return['store_id'] = $store_id;
        $return['name'] = $now_store['name'];
        $service_score=(new MallGoodReply())->getAvg(['store_id'=>$store_id],'service_score');
        $goods_score=(new MallGoodReply())->getAvg(['store_id'=>$store_id],'goods_score');
        $logistics_score=(new MallGoodReply())->getAvg(['store_id'=>$store_id],'logistics_score');
        $avg=get_number_format(($service_score+$goods_score+$logistics_score)/3);
        $return['score_mean'] = (empty($avg) || $avg == '0.00') ? 5 : $avg;
        $return['is_own'] = $now_store['mer_id'] == cfg('kefu_mer_id') ? true : false;//是否自营
        $return['fans'] = $this->getMerchantFans($now_store['mer_id']);
        $return['image'] = isset($image) ? replace_file_domain($image) : '';
        $return['logo'] = $now_store['logo'] ? replace_file_domain($now_store['logo']) : '';
        $return['phone'] = $now_store['phone'];
        $return['store_collect'] = $store_collect;
        $kefu = $this->getKefu($now_store, $uid);
        $return['kefu'] = $kefu ? $kefu : '';
        return $return;
    }

    /**
     * @param $now_store
     * @return int|string
     * @throws \Exception
     * 获取客服
     */
    public function getKefu($now_store, $uid)
    {
        if (empty($uid)) {
            return '';
        }
        //调整客服判断
        $kf = (new MerchantStoreKefu())->getOne($now_store['store_id'],'store');
        if ($kf && $uid > 0) {
            $kf_url = build_im_chat_url('user_' . $uid, $kf['username'], 'user2store');
            return $kf_url;
        }
        return '';
    }

    public function getEncryptKey($array, $app_key)
    {
        $new_arr = array();
        ksort($array);
        foreach ($array as $key => $value) {
            $new_arr[] = $key . '=' . $value;
        }
        $new_arr[] = 'app_key=' . $app_key;
        $string = implode('&', $new_arr);
        return md5($string);
    }

    function build_im_chat_url($fromUser, $toUser, $relation, $params = [])
    {
        if (empty($fromUser) || empty($relation)) {
            throw new \Exception('参数有误');
        }
        $url = cfg('site_url') . '/packapp/im/index.html#/chatInterface?from_user=' . $fromUser . '&to_user=' . $toUser . '&relation=' . $relation;
        foreach ($params as $k => $v) {
            $url .= '&' . $k . '=' . $v;
        }
        return $url;
    }

    /**
     * @param $store_id
     * @param $uid
     * @return mixed|string|string[]|null
     * @throws \think\Exception
     * 收藏、取消收藏(用户端接口)
     */
    public function storeCollection($store_id, $uid)
    {
        if (empty($store_id) || empty($uid)) {
            throw new \think\Exception('缺少参数');
        }
        $relationService = new MerchantUserRelationService();
        $info = $relationService->getDataMerUserRel(['store_id' => $store_id, 'uid' => $uid, 'type' => 'mall']);
        if (!empty($info)) {
            if ($relationService->delMerUserReal(['store_id' => $store_id, 'uid' => $uid, 'type' => 'mall']) !== false) {
                return true;
            } else {
                throw new \think\Exception('取消收藏失败请重试');
            }
        }
        $res = $relationService->addMerUserRel(['store_id' => $store_id, 'uid' => $uid, 'type' => 'mall']);
        if ($res !== false) {
            return true;
        } else {
            throw new \think\Exception('收藏失败请重试');
        }
    }

    /**
     * @desc获取商户粉丝数
     * @param $mer_id
     * @return int
     */
    private function getMerchantFans($mer_id)
    {
        $where = "`m`.`mer_id`='$mer_id'  AND `m`.`openid`!=''  AND `u`.`openid`!=''";
        $count = (new MerchantUserRelationService())->get_merchant_fans($where);
        return $count;
    }

    /**
     * @desc获取店铺自定义页面的信息
     * @param $page_id
     * @return array
     */
    private function getStoreDiypage($page_id, $now_store, $now_page)
    {
        $field_where = ['page_id' => $page_id];
        $field_list = $this->decorateFieldModel->getFieldPage($field_where);//return $field_list;
        $image_ad_index = 0;
        $show_sort = (new MallGoodsSortService())->getSort($now_store['store_id'],$now_store['mer_id']);
        $mallGoodsModel = new MallGoods();
        $limitedSku = new MallLimitedSku();
        $bargainSku = new MallNewBargainSku();
        $groupSku = new MallNewGroupSku();
        $groupTeam = new MallNewGroupTeam();
        foreach ($field_list as &$value) {
            $value['content'] = unserialize($value['content']);
            if ($value['field_type'] == 'notice') {
                $value['content']['length'] = $this->dstrlen($value['content']['content']) / 2;
            }
            if ($value['field_type'] == 'rich_text') {
                $value['content']['content'] = str_replace('<img src="/upload/', '<img src="' . file_domain() . '/upload/', $value['content']['content']);
            }
            if ($value['field_type'] == 'image_nav' && $value['content']) { 
                foreach ($value['content'] as $k => $v) {
                    $value['content'][$k]['image'] = $this->replaceImg($v['image']);
                }
            }
            if ($value['field_type'] == 'image_ad') {
                if($value['content']['nav_list']){
                    foreach ($value['content']['nav_list'] as $k => $v) {
                        $value['content']['nav_list'][$k]['index'] = $image_ad_index;
                        $value['content']['nav_list'][$k]['image'] = $this->replaceImg($v['image']);
                        $image_ad_index++;
                    }
                    $value['content']['nav_list'] = array_values($value['content']['nav_list']);
                }else{
                    unset($field_list[$key]);
                    continue;
                }
            }
            if ($value['field_type'] == 'coupons' && $value['content']['coupon_arr']) {
                $tmpArr = [];
                foreach ($value['content']['coupon_arr'] as $k => $v) {
                    $tmpArr[] = $v['id'];
                }
                $res = (new CardNewCoupon())->get_coupon_list_by_ids($tmpArr);
                if (!empty($res)) {
                    foreach ($res as $key => &$v) {
                        if ($v['start_time'] > time() || $v['end_time'] < time() || $v['status'] == 0) {
                            unset($res[$key]);
                        }
                        $v['order_money_txt'] = $this->getFormatNumber($v['order_money']);
                        $v['discount_txt'] = $this->getFormatNumber($v['discount']);
                        $v['img'] = replace_file_domain($v['img']);
                        $v['start_time_txt'] = date('Y.m.d', $v['start_time']);
                        $v['end_time_txt'] = date('Y.m.d', $v['end_time']);
                    }
                    $value['content']['coupon_arr'] = array_values($res);
                } else {
                    throw new \think\Exception('没查到优惠券信息');
                }
            }
            if ($value['field_type'] == 'map') {
                $value['content']['lng'] = $now_store['gcj02_long'];
                $value['content']['lat'] = $now_store['gcj02_lat'];
                $value['content']['markers'] = array(
                    array(
                        'latitude' => $value['content']['lat'],
                        'longitude' => $value['content']['lng'],
                        'title' => $now_store['name'],
                        'iconPath' => '../../images/my_pos.png',
                        'callout' => array(
                            'content' => $now_store['name'],
                            'color' => '#000000',
                            'fontSize' => 14,
                            'borderRadius' => 8,
                            'bgColor' => '#FFFFFF',
                            'padding' => 14,
                            'display' => 'ALWAYS',
                        ),
                    ),
                );
            }
            if ($value['field_type'] == 'goods') {
                $goodsService = new MallGoodsService();
                if ($value['content']['size'] == 1 && $value['content']['size_type'] == 2) {
                    $value['content']['show_title'] = 0;
                }
                if (!empty($value['content']['goods'])) {
                    foreach ($value['content']['goods'] as $k => $v) {
                        $product = $goodsService->getOne($v['id']);
                        if (empty($product) || !in_array($product['sort_id'], $show_sort)) {
                            unset($value['content']['goods'][$k]);
                        } else {
                            $value['content']['goods'][$k]['price'] = $product['price'];
                            $value['content']['goods'][$k]['goods_type'] = $product['goods_type'];
                            $value['content']['goods'][$k]['title'] = $product['name'];
                            $value['content']['goods'][$k]['image'] = $this->replaceImg($value['content']['goods'][$k]['image']);
                            $value['content']['goods'][$k]['url'] = cfg('site_url') . '/wap.php?c=Mall&a=detail&goods_id=' . $v['id'] . '&otherLink=1';

                        }
                    }
                }
            }
            if ($value['field_type'] == 'group_module') {
                if (!empty($value['content']['activities'])) {
                    foreach ($value['content']['activities'] as $kk => $vv) {
                        $goods_id = $vv['goods_id'];//商品id
                        $act_id = $vv['act_id'];//活动id
                        $id = $vv['id'];//活动总表id
                        //sku基础信息查询
                        $sku_info = $groupSku->getSkuByActId($act_id);
                        if (!empty($sku_info)) {
                            $value['content']['activities'][$kk]['image'] = replace_file_domain($sku_info['image']);
                            $value['content']['activities'][$kk]['goods_name'] = $sku_info['name'];
                            $value['content']['activities'][$kk]['goods_type'] = $sku_info['goods_type'];
                            $value['content']['activities'][$kk]['orgin_price'] = $sku_info['price'];
                            $value['content']['activities'][$kk]['act_price'] = $sku_info['act_price'];
                            //成团信息
                            $group_where = ['act_id' => $act_id, 'status' => 1];
                            $value['content']['activities'][$kk]['team_num'] = $sku_info['complete_num'];//每个团需要多少人数
                            $value['content']['activities'][$kk]['nums'] = $groupTeam->getGroupNumSucess($group_where);
                        }
                    }
                }
            }
            if ($value['field_type'] == 'bargain_module') {
                if (!empty($value['content']['activities'])) {
                    foreach ($value['content']['activities'] as $kk => $vv) {
                        $goods_id = $vv['goods_id'];//商品id
                        $act_id = $vv['act_id'];//活动id
                        $id = $vv['id'];//活动总表id
                        //sku基础信息查询
                        $sku_info = $bargainSku->getSkuByActId($act_id);
                        if (!empty($sku_info)) {
                            $value['content']['activities'][$kk]['image'] = replace_file_domain($sku_info['image']);
                            $value['content']['activities'][$kk]['goods_name'] = $sku_info['name'];
                            //$value['content']['activities'][$kk]['goods_type'] = $sku_info['goods_type'];
                            $value['content']['activities'][$kk]['stk_str'] = $sku_info['stk_str'];
                            $value['content']['activities'][$kk]['orgin_price'] = $sku_info['price'];
                            $value['content']['activities'][$kk]['act_price'] = $sku_info['act_price'];
                            $value['content']['activities'][$kk]['bar_num'] = $sku_info['bar_num'];
                        }
                    }
                }
            }
            if ($value['field_type'] == 'seckill_module') {
                if (!empty($value['content']['activities'])) {
                    foreach ($value['content']['activities'] as $kk => $vv) {
                        $goods_id = $vv['goods_id'];//商品id
                        $act_id = $vv['act_id'];//活动id
                        $id = $vv['id'];//活动总表id
                        //sku基础信息查询
                        $sku_info = $limitedSku->getSkuByActId($act_id);
                        if (!empty($sku_info)) {
                            $value['content']['activities'][$kk]['image'] = replace_file_domain($sku_info['image']);
                            $value['content']['activities'][$kk]['goods_name'] = $sku_info['name'];
                            $value['content']['activities'][$kk]['goods_type'] = $sku_info['goods_type'];
                            $value['content']['activities'][$kk]['orgin_price'] = $sku_info['price'];
                            $value['content']['activities'][$kk]['act_price'] = $sku_info['act_price'];
                            $value['content']['activities'][$kk]['reduce_money'] = $sku_info['reduce_money'];
                            //计算总剩余库存
                            $value['content']['activities'][$kk]['act_stock_num'] = $limitedSku->getRestActStock($act_id,$goods_id);//库存是商品库存 不是单个sku库存
                        }
                    }
                }
            }
            if ($value['field_type'] == 'periodic_module') {
                if (!empty($value['content']['activities'])) {
                    foreach ($value['content']['activities'] as $kk => $vv) {
                        $goods_id = $vv['goods_id'];//商品id
                        $act_id = $vv['act_id'];//活动id
                        $id = $vv['id'];//活动总表id
                        //基础信息查询
                        $goods_info = $mallGoodsModel->getOne($goods_id);
                        if (!empty($goods_info)) {
                            $value['content']['activities'][$kk]['image'] = replace_file_domain($goods_info['image']);
                            $value['content']['activities'][$kk]['goods_name'] = $sku_info['name'];
                            $value['content']['activities'][$kk]['goods_type'] = $sku_info['goods_type'];
                            $value['content']['activities'][$kk]['price'] = $goods_info['price'];
                        }
                    }
                }
            }
        }
        return $field_list;
    }

    public static function dstrlen($string)
    {
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
        }
        return $noc;
    }

    /**
     * @param $url
     * @return string|string[]
     * 返回图片处理
     */
    private function replaceImg($url)
    {
        $url = str_replace('http://', 'https://', $url);
        if (substr($url, 0, 8) == '/upload/') {
            $url = replace_file_domain($url);
        } else if (substr($url, 0, 9) == './static/') {
            $url = cfg('site_url') . ltrim($url, '.');
        } else {
            $url = replace_file_domain($url);
        }

        return $url;
    }

    /**
     * @param $number
     * @return string|string[]
     * 格式化数字
     */
    public function getFormatNumber($number)
    {
        $number = number_format($number, 2);
        if (strpos($number, '.') !== false) {
            $number = rtrim($number, '0');
            $number = rtrim($number, '0');
            $number = rtrim($number, '.');
        }
        $number = str_replace(',', '', $number);
        if ($number === '') {
            $number = '0';
        }
        return $number;
    }

    /**
     * @param $store_id
     * @param $order
     * @param $page
     * @param $pageSize
     * 获取全部宝贝
     */
    public function getStoreGoodsList($store_id, $order, $page, $pageSize, $keyword, $sort_id, $sort_level,$arr1=[])
    {
        if (empty($store_id)) {
            throw new \think\Exception('缺少store_id 参数');
        }
        $mallGoodsModel = new MallGoods();
        $where = [['s.store_id', '=', $store_id], ['s.status', '=', 1], ['s.is_del', '=', '0'], ['s.audit_status', '=', '1']];
        //排序
        $order = $this->getOrder($order);
        $area_id=(new MallGoodsAreaService())->getAreaId($arr1);
        $whereor = [];
        if(cfg('open_mall_order_area')==1){
            $whereor[] = ['ma.area_id', 'in', $area_id];
        }
        $field = 's.*,IF(s.virtual_set>0,s.plat_virtual_sales+s.sale_num,s.virtual_sales+s.sale_num) as sale_count_total,IF(s.virtual_set>0,s.plat_virtual_sales+s.sale_num+(s.browse_num*0.2),s.virtual_sales+s.sale_num+(s.browse_num*0.2)) as all_count_total';
        //分类商品获取
        if (!empty($sort_id) && !empty($sort_level)) {
            $listIds = $this->dealSortGoods($store_id, $sort_id, $sort_level, $order, $page, $pageSize);
            array_push($where, ['s.sort_id', 'in', $listIds]);
            $list = $mallGoodsModel->getGoodsByCondition($field, $order, $where, $page, $pageSize,$whereor);
            $total = $mallGoodsModel->getGoodsByConditionCount($where);

        } else {
            if (!empty($keyword)) {
                array_push($where, ['s.name', 'like', '%' . $keyword . '%']);
            }
            $list = $mallGoodsModel->getGoodsByCondition($field, $order, $where, $page, $pageSize,$whereor);
            $total = $mallGoodsModel->getGoodsByConditionCount($where);
        }
        $list = $this->dealGoodsList($list);// 暂不处理活动商品
        $pageSize = $pageSize ?: 10;
        $totalPage = ceil($total / $pageSize);
        $return['next_page'] = $totalPage > $page ? intval($page + 1) : 0;
        $return['total_page'] = $totalPage;
        $return['total'] = $total;
        $return['goods_list'] = $list ? : [];
        return $return;

    }

    /**
     * @param $list
     * 处理商品
     */
    public function dealGoodsList($list)
    {
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $list[$key]['image'] = replace_file_domain($val['image']);
                $list[$key]['url'] = get_base_url('pages/shopmall_third/commodity_details?goods_id=' . $val['goods_id']);
                $list[$key]['price'] = get_format_number($val['price']);

                //增加虚拟销量
                if (isset($val['virtual_set'])) {
                    $virtualSales = $val['virtual_set'] == 1 ? ($val['plat_virtual_sales'] ?? 0) : ($val['virtual_sales'] ?? 0);
                    $list[$key]['sale_num'] = $val['sale_num'] + $virtualSales;
                }
                
                //可能还有其他要处理的  暂时只有这么多，先预留着
                $list[$key]['activity_type_txt']="";
                $act_id=0;
                $condition3=[//条件
                    ['s.store_id','=',$val['store_id']],
                    ['m.goods_id','=',$val['goods_id']],
                    ['s.status','=',1],
                    ['s.start_time','<',time()],
                    ['s.end_time','>=',time()],
                    ['s.is_del','=',0],
                    ['s.type','in',['bargain','group','limited','prepare','periodic']],
                ];
                $ret=(new MallActivity())->getActByGoodsID($condition3,$field='*');
                if(empty($ret)){//周期购不能时间判断
                    $list[$key]['activity_type']='normal';
                    $condition4=[//条件
                        ['s.store_id','=',$val['store_id']],
                        ['m.goods_id','=',$val['goods_id']],
                        ['s.status','=',1],
                        ['s.type','=','periodic'],
                        ['s.is_del','=',0],
                    ];
                    $ret1=(new MallActivity())->getActByGoodsID($condition4,$field='*');
                    if(!empty($ret1)){
                        $act_id=$ret1['act_id'];
                        $list[$key]['activity_type']=$ret1['type'];

                        $where3=[['s.id','=',$act_id],['m.goods_id','=',$val['goods_id']]];
                        $li=(new MallNewPeriodicPurchase())->getGoodsAndPeriodic($where3,$field="s.periodic_count,m.min_price");
                        $list[$key]['act_price']=$li['periodic_count']*$li['min_price'];
                        $list[$key]['activity_type_txt']="周期购";
                    }
                }else{
                    $act_id=$ret['act_id'];
                    $list[$key]['activity_type']=$ret['type'];
                }
                $list[$key]['act_price']="";
                if($list[$key]['activity_type'] !='normal'){//活动商品给价格
                    if ($list[$key]['activity_type']=='bargain') {
                        $where=[['act_id','=',$act_id]];
                        $arr1=(new MallNewBargainSku())->getBySkuId($where,$field="act_price");
                        $list[$key]['act_price']=get_format_number($arr1['act_price']);
                        $list[$key]['activity_type_txt']="砍价";
                    }elseif ($list[$key]['activity_type']=='group'){
                        $where=[['act_id','=',$act_id]];
                        $price=(new MallNewGroupSku())->getPice($where);
                        $list[$key]['act_price']=get_format_number($price);
                        $list[$key]['activity_type_txt']="拼团";
                    }elseif ($list[$key]['activity_type']=='limited'){
                        //未开始的活动不要活动标签
                        $rets = (new MallLimitedAct())->getLimitedByActID($act_id, $val['goods_id']);
                        if(!empty($rets) && $rets[0]['limited_status']==0){
                            $list[$key]['activity_type'] = 'normal';
                        }else{
                            $list[$key]['activity_type_txt']="秒杀";
                        }
                        $price=(new MallLimitedSku())->limitMinPrice($act_id,$val['goods_id']);
                        $list[$key]['act_price']=get_format_number($price);
//                        $list[$key]['price'] =$list[$key]['act_price']=get_format_number($price);
                        $stock=(new MallLimitedSku())->getRestActMinStock($act_id,$val['goods_id']);
                        if($stock<0){
                            $list[$key]['stock_num'] =-1;
                        }else{
                            $list[$key]['stock_num'] =(new MallLimitedSku())->getRestActStock($act_id,$val['goods_id']);
                        }
                    }elseif ($list[$key]['activity_type']!='periodic'){
                        $price=(new MallPrepareActSku())->prepareMinPrice($act_id,$val['goods_id']);
                        $list[$key]['act_price']=get_format_number($price);
                        $list[$key]['activity_type_txt']="预售";
                    }
                }
                //消费一元获得积分
                $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($val['price'],'mall',($val['goods_type']=='sku'?true:false),$val['score_percent']);
                $list[$key]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                $list[$key]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            }
            return $list;
        }else{
            return [];
        }
    }

    /**
     * @param $order
     * @return string
     * 排序处理
     */
    private function getOrder($order)
    {
        switch ($order) {
            case '1'://1销量倒序
                $order = 'sale_count_total DESC,goods_id DESC';
                break;
            case '2'://2销量正序
                $order = 'sale_count_total ASC,goods_id DESC';
                break;
            case '3'://3价格倒序
                $order = 'price DESC,goods_id DESC';
                break;
            case '4'://4价格正序
                $order = 'price ASC,goods_id DESC';
                break;
            default:
                $order = 'sort_store DESC,sale_num DESC,goods_id DESC';
                break;
        }
        return $order;
    }

    /**
     * @param $sort_id
     * @param $sort_level
     * @param $order
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\Exception
     * 获取分类下的商品
     */
    public function dealSortGoods($store_id, $sort_id, $sort_level, $order, $page, $pageSize)
    {
        $list = array();
        if ($sort_level == 1) {
            //一级分类找出一级 二级和三级商品
            $carr = (new MallGoodsSort())->getCategoryByCondition(['fid' => $sort_id, 'status' => 1, 'store_id' => $store_id], 'sort DESC');
            if (!empty($carr)) {
                foreach ($carr as $val) {
                    $garr = (new MallGoodsSort())->getCategoryByCondition(['fid' => $val['id'], 'status' => 1, 'store_id' => $store_id], 'sort DESC');
                    if (!empty($garr)) {
                        //存在三级分类并找出该三级分类下的商品
                        foreach ($garr as $v) {
                            $list[] = $v['id'];
                        }
                    } else {
                        //不存在三级 当前二级是最后一级
                        $list[] = $val['id'];
                    }
                }
            } else {
                //不存在二级 三级，当前一级是最后一级
                $list[] = $sort_id;
            }
        } elseif ($sort_level == 2) {
            //二级分类找出二级 三级商品
            $garr = (new MallGoodsSort())->getCategoryByCondition(['fid' => $sort_id, 'status' => 1, 'store_id' => $store_id], 'sort DESC');
            if (!empty($garr)) {
                //存在三级分类并找出该三级分类下的商品
                foreach ($garr as $v) {
                    $list[] = $v['id'];
                }
            } else {
                //不存在三级 当前二级是最后一级
                $list[] = $sort_id;
            }
        } else {
            throw new \think\Exception('分类参数等级有误');
        }
        return $list;
    }

    /**
     * @param $store_id
     * 获取一级二级分类
     */
    public function getSortsList($store_id)
    {
        if (empty($store_id)) {
            throw new \think\Exception('缺少store_id 参数');
        }
        $farr = [];
        $carr = [];
        $sortService = new MallGoodsSortService();
        $list = $sortService->getSort($store_id);
        if (!empty($list['list'])) {
            foreach ($list['list'] as $key => $val) {
                if ($val['level'] == 1) {
                    $farr[] = $val;
                } elseif ($val['level'] == 2) {
                    $carr[] = $val;
                }
            }
        }
        foreach ($farr as $k => $v) {
            $farr[$k]['sort_id'] = $v['id'];
            unset($farr[$k]['id']);
            $farr[$k]['sort_name'] = $v['name'];
            unset($farr[$k]['name']);
            foreach ($carr as $kk => $vv) {
                //转换成和老版相同的数据结构
                $vv['sort_id'] = $vv['id'];
                unset($vv['id']);
                $vv['sort_name'] = $vv['name'];
                unset($vv['name']);
                if ($v['id'] == $vv['fid']) {
                    $farr[$k]['son_list'][] = $vv;
                }
            }
        }
        return $farr;
    }

    /**
     * @param $store_id
     * 新品
     */
    public function getStoreNewList($store_id, $order)
    {
        if (empty($store_id)) {
            throw new \think\Exception('缺少store_id 参数');
        }
        $goods_order = $this->getOrder($order);
        //更新日期小于15天且销量小于3 的才是新品
        $time = time() - 60 * 60 * 24 * 15;
        $where = [['store_id', '=', $store_id], ['status', '=', 1], ['is_del', '=', '0'], ['update_time', '>', $time], ['sale_num', '<', '4']];
        $field = '*,IF(virtual_set>0,plat_virtual_sales+sale_num,virtual_sales+sale_num) as sale_count_total,IF(virtual_set>0,plat_virtual_sales+sale_num+(browse_num*0.2),virtual_sales+sale_num+(browse_num*0.2)) as all_count_total';
        $list = (new MallGoods())->getGoodsByCondition($field, $goods_order, $where, 1, 30);
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $list[$key]['image'] = replace_file_domain($val['image']);
                //消费一元获得积分
                $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($val['price'],'mall',($val['goods_type']=='sku'?true:false),$val['score_percent']);
                $list[$key]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                $list[$key]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            }
        }
        return $list;
    }
}