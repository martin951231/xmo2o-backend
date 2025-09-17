<?php

namespace app\merchant\controller\merchant;

/**
 * 商家后台---店铺管理
 */

use app\common\model\service\AreaService;
use app\common\model\service\ConfigService;
use app\group\model\service\GroupService;
use app\http\exceptions\ParametersException;
use app\merchant\model\db\MerchantScore;
use app\merchant\model\db\MerchantStoreGiveCouponSet;
use app\merchant\model\db\MerchantStoreShop;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreOpenTimeService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\pick\PickAddressService;
use app\merchant\model\service\print_order\HookService;
use app\merchant\model\service\store\StorePayService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use app\merchant\validate\AddressSetting as AddressSettingValidate;
use map\Coordinate;
use think\facade\Db;
use think\helper\Str;
use token\Token;

class MerchantShopManagementController extends AuthBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常

    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    private function validateParameter($method = 'post', array $params = [], string $scenario = ''): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate(AddressSettingValidate::class);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw new ParametersException(L_($validate->getError()));
        }

        return $params;
    }

    //endregion

    public $staff_type = [];

    public function initialize()
    {
        parent::initialize();
        $this->merId = $this->merchantUser['mer_id'] ?? 0;
        //$this->merId = 901;
        $this->staff_type[0] = L_('店小二');
        $this->staff_type[1] = L_('核销员');
        $this->staff_type[2] = L_('店长');
    }

    /* 检测店铺存在，并检测是不是归属于商家 */
    protected function check_store($store_id)
    {
        $mer_id = $this->merId;
        //$mer_id = 1;
        //$mer_id = 1;
        $database_merchant_store = new MerchantStoreService();
        $condition_merchant_store = [['store_id', '=', $store_id]];
        $now_store = $database_merchant_store->getOne($condition_merchant_store);
        return $now_store;
    }

    /* 店铺管理 */
    public function storeList()
    {
        $mer_id = $this->merId;
        $database_merchant_store = new MerchantStoreService();
        $condition_merchant_store['mer_id'] = $mer_id;
        $status = $this->request->param('status', '');
        $name = $this->request->param('name', '', 'trim');
        if ($mer_id < 1) {
            return api_output(1001, [], '商家ID不存在');
        }

        $pageSize = $this->request->param('pageSize', 0, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $where = [['s.mer_id', '=', $mer_id], ['s.status', '<>', 4]];
        if ($status !== '' && $status != -1) {
            array_push($where, ['s.status', '=', $status]);
        }
        if (!empty($name)) {
            array_push($where, ['s.name', 'like', '%'.$name.'%']);
        }
        $field = "*";
        $order = "s.sort DESC,s.store_id ASC";
        try {
            $list = $database_merchant_store->getStoreByWhereList($where, $field, $order, ($page - 1) * $pageSize, $pageSize);
            $list['ret_total']=$database_merchant_store->getStoreSum($mer_id);
            $list['new_diypage'] = intval(customization('open_new_merchant_diypage'));
            $list['show_robot'] = intval(customization('property_robot_jsdhwy'));
            //是否有微页面权限
            $menus = explode(",", $this->merchantUser['menus']);
            $list['diypage_auth'] = in_array(10612, $menus) ? 1 : 0;
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    public function miaoMaiGoodsSync()
    {
        $storeId = $this->request->param('store_id', '', 'intval');
        if (empty($storeId)) {
            return api_output(1001, [], '店铺ID不存在');
        }
        $syncResult = invoke_cms_model('Shop_goods/miaoMaiGoodsSync', [$this->merId, $storeId]);

        return api_output(1000, $syncResult, 'success');
    }
    /**
     *店铺信息
     */
    public function storeMsg()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $database_merchant_store = new MerchantStoreService();
        $store_pay = new StorePayService();
        if (empty($store_id)) {
            return api_output(1001, [], '店铺ID不存在');
        }
        try {
            $where = [['store_id', '=', $store_id]];
            $list['list'] = $database_merchant_store->getStoreByStoreId($store_id);
            $list['list']['pay_name'] = $store_pay->getPayName($where, "name");
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 店铺编辑
     */
    public function storeEdit()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $city_id = $this->merchantUser['area_id'] ?? ($this->merchantUser['city_id'] ?? 0);
        try {
            $systemInfoToken = Token::checkToken($_COOKIE['platform_access_token']??'');
            $assign['store_marketing'] = empty(customization('store_marketing'))?0:1;
            if (empty($store_id)) {
                //return api_output(1001, [], '店铺ID不存在');
                //使用场景使用场景配置
                $now_store['lng']=0;
                $now_store['lat']=0;
                if(!empty($city_id)){
                    $mer_msg=(new AreaService())->getAreaByAreaId($city_id);
                    $now_store['lng']=empty($mer_msg)?0:$mer_msg['area_lng'];
                    $now_store['lat']=empty($mer_msg)?0:$mer_msg['area_lat'];
                }

                $now_store['store_ticket_have'] = cfg('store_ticket_have');
                $now_store['pay_in_store'] = cfg('pay_in_store');
                $now_store['store_ticket_have_insure'] = cfg('store_ticket_have_insure');
                $now_store['merchant_edit_circle'] = cfg('merchant_edit_circle');//是否允许商家修改店铺所属商圈
                $now_store['options'] = (new MerchantStoreService())->ajax_cat_fid();
                $now_store['areas'] = (new AreaService())->getAllAreaAndStreet("area_type,area_id,area_pid,area_id as value,area_name as label",'children');
                $arr1=(new ConfigService())->getDataOne(['name'=>'open_store_vr']);
                if(empty($arr1)){
                    $now_store['open_store_vr'] = 0;
                }else{
                    $now_store['open_store_vr'] = $arr1['value'];
                }
                $now_store['dindin_is_open'] = cfg('dindin_is_open') ?? "";//开启了叮叮配送店员接单后，平台配送员是看不到这个订单的
                $now_store['disabled_status'] = (isset($systemInfoToken['memberId']) && cfg('discount_controler')==0) || (!isset($systemInfoToken['memberId']) && cfg('discount_controler')==1) ? true : false;//买单优惠是否禁用编辑
                $assign['now_store'] = $now_store;
                $assign['default_area']=[$this->merchantUser['province_id']];
            }
            else {
                $condition_merchant_store = [['store_id', '=', $store_id]];
                $where = [['a.store_id', '=', $store_id]];
                $field = "g.*";
                $assign['group_list'] = (new GroupService())->getStoreGroupList($where, $field);

                $where1 = [['a.store_id', '=', $store_id], ['is_rec', '=', 1]];
                $group_list = (new GroupService())->getStoreGroupList($where1, $field);
                if (!empty($group_list)) {
                    $groupList = array();
                    foreach ($group_list as $key => $val) {
                        $groupList[] = $val['group_id'];
                    }
                    $assign['group_ids'] = $groupList;
                }
                $now_store = (new MerchantStoreService())->getOne($condition_merchant_store);
                if (empty($now_store)) {
                    return api_output(1001, [], '店铺不存在');
                }
                $mer_id = $this->merId;
                if ($now_store['mer_id'] != $mer_id) {
                    return api_output_error(1002, '请商家与店铺信息不匹配,请重新登录');
                }
                /*else {
                    $now_store = $now_store->toArray();
                }*/
                if (!empty($now_store['logo'])) {
                    $now_store['logo'] =  $now_store['image_logo'] = replace_file_domain($now_store['logo']);
                }
                if (!empty($now_store['video_url'])) {
                    $now_store['video_url'] = replace_file_domain($now_store['video_url']);
                }
                if (!empty($now_store['share_image'])) {
                    $now_store['share_image'] = replace_file_domain($now_store['share_image']);
                }
                if (!empty($now_store['id_image'])) {
                    $now_store['id_image'] = replace_file_domain($now_store['id_image']);
                }
                $now_store['supplier_info'] = replace_file_domain_content(htmlspecialchars_decode($now_store['supplier_info']));
                if (!empty($now_store['pic_info'])) {
                    $tmp_pic_arr = explode(';', $now_store['pic_info']);
                    foreach ($tmp_pic_arr as $key => $value) {
                        $now_store['pic'][] = replace_file_domain($value);
                    }
                }
                $recommendPic = MerchantStoreShop::where(['store_id' => $now_store['store_id']])->value('recommend_pic');
                if (!empty($recommendPic)) {
                    $now_store['index_left_image'] = replace_file_domain($recommendPic);
                }
                if (!empty($now_store['head_bg_image'])) {
                    $now_store['head_bg_image'] = replace_file_domain($now_store['head_bg_image']);
                }
                
                if (!empty($now_store['discount_txt'])) {
                    $arr = unserialize($now_store['discount_txt']);
                    $now_store['discount_type'] = $arr['discount_type'];
                    $now_store['discount_percent'] = isset($arr['discount_percent']) ? $arr['discount_percent'] : "";
                    $now_store['discount_limit'] = isset($arr['discount_limit']) ? $arr['discount_limit'] : "";
                    $now_store['discount_limit_percent'] = isset($arr['discount_limit_percent']) ? $arr['discount_limit_percent'] : "";
                }
                /* if(!empty($now_store['plat_recommend_info'])){
                     $now_store['plat_recommend_type_group']=
                 }*/
                $where_keywords = [['third_type', '=', 'Merchant_store'], ['third_id', '=', $store_id]];

                $keywords = (new MerchantStoreService())->getKeyWordSome($where_keywords);
                $str = "";
                foreach ($keywords as $key) {
                    $str .= $key['keyword'] . " ";
                }
                $qcode = cfg('site_url') . "/wap.php?c=My&a=pay&store_id=" . $store_id . "&spread=1";            
                $wxapp_qrcode = '/pages/plat_menu/index?redirect=webview&webview_url='.urlencode(cfg('site_url').'/wap.php?c=My&a=pay&store_id='.$store_id);   
                if(strlen($wxapp_qrcode) > 128){
                    //小程序码路径不能大于128，如果大于转成成短链处理
                    $linkId = Db::name('short_link')->insertGetId(['link_url'=>cfg('site_url').'/wap.php?c=My&a=pay&store_id='.$store_id]);
                    $wxapp_qrcode = get_home_url() . '?redirect=webview&webview_url='.urlencode('/short_'.$linkId);
                }
                
                $now_store['qcode'] = (new MerchantStoreService())->createQrcode($qcode, $store_id);//二维码
                $now_store['wxapp_qcode'] = cfg('site_url').'/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode' . '&page=' . urlencode($wxapp_qrcode);//小程序二维码
                $now_store['keywords'] = $str;
                $now_store['map_config'] = cfg('map_config');//地图配置
                $now_store['google_map_ak'] = cfg('google_map_ak');//谷歌配置
                $now_store['discount_txt'] = unserialize($now_store['discount_txt']);
                //使用场景使用场景配置
                $now_store['store_ticket_have'] = cfg('store_ticket_have');
                $now_store['pay_in_store'] = cfg('pay_in_store');
                $now_store['store_ticket_have_insure'] = cfg('store_ticket_have_insure');
                $now_store['merchant_edit_circle'] = cfg('merchant_edit_circle');//是否允许商家修改店铺所属商圈
                $now_store['options'] = (new MerchantStoreService())->ajax_cat_fid();
                $now_store['areas'] = (new AreaService())->getAllAreaAndStreet("area_type,area_id,area_pid,area_id as value,area_name as label",'children');
                $where_pay = [['store_id', '=', $store_id]];
                $pay_list = (new MerchantStoreService())->getStorePaySome($where_pay);
                $now_store['store_pay'] = $pay_list;
                //判断票务插件
                $condition_store_trade_ticket = [['store_id', '=', $store_id]];
                $store_trade_ticket = (new MerchantStoreService())->getTradeTicketOne($condition_store_trade_ticket);
                if (!empty($store_trade_ticket)) {
                    $store_trade_ticket = $store_trade_ticket->toArray();
                } else {
                    $store_trade_ticket['default_money'] = 0;
                    $store_trade_ticket['have_insure'] = 0;
                    $store_trade_ticket['insure_1'] = 0;
                    $store_trade_ticket['insure_2'] = 0;
                    $store_trade_ticket['insure_3'] = 0;
                    $store_trade_ticket['insure_info'] = "";
                    $store_trade_ticket['insure_money_1'] = 0;
                    $store_trade_ticket['insure_money_2'] = 0;
                    $store_trade_ticket['insure_money_3'] = 0;
                    $store_trade_ticket['insure_mustby'] = 0;
                    $store_trade_ticket['insure_name'] = "";
                    $store_trade_ticket['insure_tikcet_1'] = 0;
                    $store_trade_ticket['insure_tikcet_2'] = 0;
                    $store_trade_ticket['insure_tikcet_3'] = 0;
                    $store_trade_ticket['limit_num'] = 1;
                    $store_trade_ticket['use_scene'] = 0;
                }
                $now_store['store_trade_ticket'] = $store_trade_ticket;

                //判断平台推荐

                if ($now_store['plat_recommend_info']) {
                    $tmp_plat_recommend_info = explode('_', $now_store['plat_recommend_info']);
                    if (count($tmp_plat_recommend_info) > 1) {
                        $now_store['plat_recommend_info_arr']['type'] = $tmp_plat_recommend_info[0];
                        $now_store['plat_recommend_info_arr']['value'] = $tmp_plat_recommend_info[1];
                    }
                }

                //得到店铺下的团购
                $store_id = $now_store['store_id'];
                $now_time = $_SERVER['REQUEST_TIME'];
                $condition_where_plat_recommend_group = [
                    ['gs.store_id', '=', $store_id],
                    ['g.status', '=', 1],
                    ['g.type', '=', 1],
                    ['g.begin_time', '<', $now_time],
                    ['g.end_time', '>', $now_time],
                ];
                $plat_field = "g.group_id,g.name AS group_name";
                $plat_order = "g.sort DESC,g.group_id DESC";
                $plat_recommend_group_list = (new GroupService())->platRecommendGroupList($condition_where_plat_recommend_group, $plat_field, $plat_order);
                $assign['plat_recommend_group_list'] = $plat_recommend_group_list;

                //得到店铺下的拼团
                $condition_where_plat_recommend_group = [
                    ['gs.store_id', '=', $store_id],
                    ['g.status', '=', 1],
                    ['g.type', '=', 1],
                    ['g.pin_num', '>', 1],
                    ['g.begin_time', '<', $now_time],
                    ['g.end_time', '>', $now_time],
                ];
                $plat_field = "g.group_id,g.name AS group_name";
                $plat_order = "g.sort DESC,g.group_id DESC";
                $plat_recommend_pin_group_list = (new GroupService())->platRecommendGroupList($condition_where_plat_recommend_group, $plat_field, $plat_order);
                $assign['plat_recommend_pin_group_list'] = $plat_recommend_pin_group_list;
                $arr1=(new ConfigService())->getDataOne(['name'=>'open_store_vr']);
                   if(empty($arr1)){
                       $now_store['open_store_vr'] = 0;
                   }else{
                       $now_store['open_store_vr'] = $arr1['value'];
                   }
                $now_store['dindin_is_open'] = cfg('dindin_is_open') ?? "";//开启了叮叮配送店员接单后，平台配送员是看不到这个订单的


                if($now_store['tw_branch_no']){
                    $now_store['tw_shop_id'] = $now_store['tw_shop_id'].'_'.$now_store['tw_branch_no'];
                }

                //店铺支付返现
                $now_store['is_cash_back'] = customization('cash_back') ? true : false;
                
                $now_store['disabled_status'] = (isset($systemInfoToken['memberId']) && cfg('discount_controler')==0) || (!isset($systemInfoToken['memberId']) && cfg('discount_controler')==1) ? true : false;//买单优惠是否禁用编辑
                if (cfg('tianditu_web_key')) {
                    list($now_store['long'], $now_store['lat']) = Coordinate::bd09ToWgs84($now_store['long'], $now_store['lat']);
                }
                $assign['now_store'] = $now_store;

                $leveloff = !empty($now_store['leveloff']) ? unserialize($now_store['leveloff']) : false;
                $tmparr = (new MerchantStoreService())->userLevelSome();

                $levelarr = array();
                if ($tmparr && $this->config['level_onoff']) {
                    foreach ($tmparr as $vv) {
                        if (!empty($leveloff) && isset($leveloff[$vv['level']])) {
                            $vv['vv'] = $leveloff[$vv['level']]['vv'];
                            $vv['type'] = $leveloff[$vv['level']]['type'];
                        } else {
                            $vv['vv'] = '';
                            $vv['type'] = '';
                        }
                        $levelarr[$vv['level']] = $vv;
                    }
                }
                unset($tmparr);
                $assign['levelarr'] = $levelarr;
                $time_date['retval'] =(new MerchantStoreService())->get_store_open_time($now_store['mer_id'], $now_store['store_id']);
                $assign['time_date'] = $time_date;
            }
            
            //获取送优惠券配置
            $assign['now_store']['is_discount'] = 0;
            $assign['now_store']['discount'] = 0;
            $assign['now_store']['order_money'] = 0;
            $assign['now_store']['coupon_num'] = 0;
            $assign['now_store']['use_day'] = 0;
            $assign['now_store']['expire_day'] = 0;
            $assign['now_store']['coupon_name'] = '';
            $assign['now_store']['coupon_img'] = '';
            $assign['now_store']['use_with_card'] = 0;
            if(isset($now_store['store_id'])&&$now_store['store_id']){
                $store_give_coupon = (new MerchantStoreGiveCouponSet())->where(['store_id'=>$now_store['store_id']])->findOrEmpty()->toArray();
                if($store_give_coupon){
                    $assign['now_store']['is_discount'] = $store_give_coupon['is_discount'];
                    $assign['now_store']['discount'] = $store_give_coupon['discount'];
                    $assign['now_store']['order_money'] = $store_give_coupon['order_money'];
                    $assign['now_store']['coupon_num'] = $store_give_coupon['coupon_num'];
                    $assign['now_store']['use_day'] = $store_give_coupon['use_day'];
                    $assign['now_store']['expire_day'] = $store_give_coupon['expire_day'];
                    $assign['now_store']['coupon_name'] = $store_give_coupon['coupon_name'];
                    $assign['now_store']['coupon_img'] = $store_give_coupon['coupon_img']?replace_file_domain($store_give_coupon['coupon_img']):'';
                    $assign['now_store']['use_with_card'] = $store_give_coupon['use_with_card'];
                }
            }
            
            //第三方配送开关
            $assign['plat_config'] = [
                'shunfeng_is_open' => intval(cfg('shunfeng_is_open')),
                'ele_is_open' => intval(cfg('ele_is_open')),
                'open_merchant_wxapp_score_plugin'=>customization('open_merchant_wxapp_score_plugin') ? true : false, //微信商圈积分兑换比例
                'open_tuwei_sync' => intval(customization('open_tuwei_sync')),
            ];
            
            return api_output(1000, $assign, 'success');

        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * 店铺新增保存
     */
    public function addStoreEdit()
    {
        try{
        $postData = $this->request->param();
        $mer_id = $this->merId;
        if (empty($postData['name'])) {
            return api_output(1001, [], L_('店铺名称必填'));
        }
        if (empty($postData['phone'])) {
            return api_output(1001, [], L_('联系电话必填'));
        }
        if (empty($postData['long']) || empty($postData['lat'])) {
            return api_output(1001, [], L_('店铺经纬度必填'));
        }
        if (empty($postData['adress'])) {
            return api_output(1001, [], L_('店铺地址必填'));
        }

        $postData['age_limit_buy'] = intval($postData['age_limit_buy']);
        if ($postData['age_limit_buy_switch'] == 1 && intval($postData['age_limit_buy']) > 99) {
            return api_output(1001, [], L_('年龄限制不能超过100岁'));
        }
        if (empty($postData['pic_info'])) {
            return api_output(1001, [], L_('请至少上传一张图片'));
        }
        if (!empty($postData['video_url'])) {
            $postData['video_url'] = $postData['video_url'][0];
        }
        $buy_id=0;
        if(!empty($postData['buy_id'])){
            $buy_id=$postData['buy_id'];
            $msg = (new MerchantStoreService())->getStoreMerMsg($postData['buy_id']);
            if (empty($msg) || ($msg['store_count']<=$msg['used_count'])) {
                return api_output(1001, [], L_('开店使用次数已经用完，请重新购买'));
            }
            $postData['end_time']=(new MerchantStoreService())->getStoreYear($postData['buy_id']);
        }else{
            $postData['end_time']=time();
        }
        $postData['add_time']=time();
        $postData['supplier_nav_first_name'] = isset($postData['supplier_nav_first_name']) ? $postData['supplier_nav_first_name'] : '';
        $postData['supplier_nav_first_link'] = isset($postData['supplier_nav_first_link']) ? $postData['supplier_nav_first_link'] : '';
        $postData['supplier_nav_second_name'] = isset($postData['supplier_nav_second_name']) ? $postData['supplier_nav_second_name'] : '';
        $postData['supplier_nav_second_link'] = isset($postData['supplier_nav_second_link']) ? $postData['supplier_nav_second_link'] : '';

        $postData['office_time'] = '';

        $postData['sort'] = intval($postData['sort']);
        $postData['last_time'] = $_SERVER['REQUEST_TIME'];
        $postData['add_from'] = '0';
        $postData['mer_id'] = $mer_id;
        $ismain = intval($postData['ismain']);
        if (cfg('store_verify')) {
            $postData['status'] = $this->merchantUser['issign'] ? '1' : '2';
        } else {
            $postData['status'] = '1';
        }

        $postData['discount_txt'] = '';
        $discount_type = isset($postData['discount_type']) ? intval($postData['discount_type']) : 0;
        if ($discount_type == 1) {
            $discount_percent = isset($postData['discount_percent']) ? (intval($postData['discount_percent'] * 10) / 10) : 0;
            if ($discount_percent > 0 && $discount_percent < 10) {
                if ($this->config['open_extra_price'] == 1) {
                    $postData['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent, 'discount_limit' => isset($postData['discount_limit']) ? $postData['discount_limit'] : "", 'discount_limit_percent' => isset($postData['discount_limit_percent']) ? $postData['discount_limit_percent'] : ""));
                } else {
                    $postData['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent));
                }
            } elseif ($discount_percent <= 0 || $discount_percent >= 10) {
                return api_output(1001, [], L_('折扣率必须在0~10之间的数'));
            }
        } elseif ($discount_type == 2) {
            $condition_price = isset($postData['condition_price']) ? (intval($postData['condition_price'] * 100) / 100) : 0;
            $minus_price = isset($postData['minus_price']) ? (intval($postData['minus_price'] * 100) / 100) : 0;
            if ($condition_price < 0 || $minus_price < 0 || $minus_price > $condition_price) {
                return api_output(1001, [], L_('满减的填写不正确，必须都是大于0且满足的金额要大于减免金额'));
            }
            if ($condition_price > 0 && $minus_price > 0 && $minus_price < $condition_price) {
                $postData['discount_txt'] = serialize(array('discount_type' => $discount_type, 'condition_price' => $condition_price, 'minus_price' => $minus_price));
            }
        }

        $store_give_coupon_set_data = [];
        //送优惠券配置
        if(!empty($postData['open_give_coupon'])){
            if(!in_array($postData['is_discount'],[0,1])){
                return api_output(1001, [], L_('优惠金额类型错误！'));
            }
            if($postData['discount']<0){
                return api_output(1001, [], L_('优惠金额错误！'));
            }
            if($postData['order_money']<0){
                return api_output(1001, [], L_('最小订单金额不能小于0！'));
            }
            if($postData['coupon_num']<1){
                return api_output(1001, [], L_('优惠券数量不能小于1！'));
            }
            if($postData['use_day']<0){
                return api_output(1001, [], L_('领取后使用天数不能小于0！'));
            }
            if($postData['expire_day']<1){
                return api_output(1001, [], L_('过期天数不能小于1！'));
            }
            if(!$postData['coupon_name']){
                return api_output(1001, [], L_('优惠券名称不能为空！'));
            }
            $store_give_coupon_set_data = [
                'is_discount' => $postData['is_discount'],
                'discount' => $postData['discount'],
                'order_money' => $postData['order_money'],
                'coupon_num' => $postData['coupon_num'],
                'use_day' => $postData['use_day'],
                'expire_day' => $postData['expire_day'],
                'coupon_name' => $postData['coupon_name'],
                'coupon_img' => $postData['coupon_img']??'',
                'use_with_card' => $postData['use_with_card']??0
            ];
        }
        
        if ($ismain == 1) {
            $where_update = [['mer_id', '=', $mer_id]];
            $updata['ismain'] = 0;
            (new MerchantStoreService())->updateThis($where_update, $updata);
        }

        // 设置是否开通业务员权限
        $issalesman = intval($postData['issalesman']);
        $where_update = [['store_id', '=', $postData['store_id']]];
        $updata['issalesman'] = $issalesman;
        (new MerchantStoreService())->updateThis($where_update, $updata);

        $postData['store_type'] = isset($postData['store_type']) ? intval($postData['store_type']) : 1;
        $leveloff = isset($postData['leveloff']) ? $postData['leveloff'] : false;
        $newleveloff = array();
        if (!empty($leveloff)) {
            foreach ($leveloff as $kk => $vv) {
                $vv['type'] = intval($vv['type']);
                $vv['vv'] = intval($vv['vv']);
                if (($vv['type'] > 0) && ($vv['vv'] > 0)) {
                    $vv['level'] = $kk;
                    $newleveloff[$kk] = $vv;
                }
            }
        }

        $postData['leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';
        $week_status = array();
        $open_time = array();
        $close_time = array();
        foreach ($postData['retval'] as $key => $val) {
            $week_status[] = $val['week_status'];
            foreach ($val['time_list'] as $ke => $va) {
                $open[$ke + 1] = $va['open_time'];
                $close[$ke + 1] = $va['close_time'];
            }
            $open_time[] = $open;
            $close_time[] = $close;
        }
        $store_trade_ticket = $postData['store_trade_ticket'];
        unset($postData['dindin_is_open']);
        unset($postData['buy_id']);
        unset($postData['open_store_vr']);
        unset($postData['check_list']);
        unset($postData['discount_limit']);
        unset($postData['discount_limit_percent']);
        unset($postData['week_status']);
        unset($postData['open_time']);
        unset($postData['close_time']);
        unset($postData['image_logo']);
        unset($postData['pic']);
        unset($postData['qcode']);
        unset($postData['wxapp_qcode']);
        unset($postData['keywords']);
        unset($postData['map_config']);
        unset($postData['google_map_ak']);
        unset($postData['store_ticket_have']);
        unset($postData['pay_in_store']);
        unset($postData['store_ticket_have_insure']);
        unset($postData['merchant_edit_circle']);
        unset($postData['options']);
        unset($postData['areas']);
        unset($postData['store_pay']);
        unset($postData['store_trade_ticket']);
        unset($postData['retval']);
        unset($postData['age_limit_buy_switch']);
        unset($postData['plat_recommend_info_arr']);
        unset($postData['is_cash_back']);
        if ($postData['plat_recommend_type'] == 'group') {
            $postData['plat_recommend_info'] = 'group_' . $postData['plat_recommend_type_group'];
        } else {
            $postData['plat_recommend_info'] = $postData['plat_recommend_type_store'] . '_' . ($postData['plat_recommend_type_store']=='description'?$postData['plat_recommend_info']:0);
        }
        unset($postData['plat_recommend_type_store']);
        unset($postData['plat_recommend_type_group']);
        unset($postData['condition_price']);
        unset($postData['minus_price']);
        unset($postData['discount_percent']);
        unset($postData['plat_recommend_pin_group_list']);
        unset($postData['system_type']);
        unset($postData['is_discount']);
        unset($postData['discount']);
        unset($postData['order_money']);
        unset($postData['coupon_num']);
        unset($postData['use_day']);
        unset($postData['expire_day']);
        unset($postData['coupon_name']);
        unset($postData['coupon_img']);
        if(isset($postData['use_with_card'])){
            unset($postData['use_with_card']);
        }
            
        if(isset($postData['list'])){
            unset($postData['list']);
        }
        unset($postData['is_cash_back']);
        if ($merchant_ststore_addore_id = (new MerchantStoreService())->merchantStoreAdd($postData)) {
            if(!empty($buy_id)) {
                (new MerchantStoreService())->setInc(['id'=>$buy_id],'used_count');
            }

            if (isset($store_pay) && !empty($store_pay)) {
                foreach ($store_pay as $key => $val) {
                    if ($val['id']) {
                        $where_pay = [['id', '=', $val['id']], ['store_id', '=', $val['store_id']]];
                        $data_pay['name'] = $val['name'];
                        (new MerchantStoreService())->getStorePayUpdate($where_pay, $data_pay);
                    } else {
                        $data_pay['name'] = $val['name'];
                        $data_pay['store_id'] = $merchant_ststore_addore_id;
                        (new MerchantStoreService())->storePayadd($data_pay);
                    }
                }
            }
            //绑定叮叮配送
            if (cfg('dindin_is_open') == 1) {
                if ($postData['is_open_dingding'] == 1) {
                    //$dingDing = new Dingding('','','','','','','','');
                    $where_pro = [['area_id', '=', $postData['province_id']]];
                    $province = (new MerchantStoreService())->getAreaOne($where_pro);
                    if (!empty($province)) {
                        $province = $province->toArray();
                    }
                    $where_city = [['area_id', '=', $postData['city_id']]];
                    $city = (new MerchantStoreService())->getAreaOne($where_city);
                    if (!empty($city)) {
                        $city = $city->toArray();
                    }
                    $where_area = [['area_id', '=', $postData['area_id']]];
                    $area = (new MerchantStoreService())->getAreaOne($where_area);
                    if (!empty($area)) {
                        $area = $area->toArray();
                    }
                    //$long_lat = explode(',',$postData['long_lat']);
                    $postDataNew = array(
                        'isadd' => 1,
                        'cargotype_code' => 1,
                        'appid' => cfg('dingding_appid'),
                        'username' => $postData['dingding_user_name'],
                        'password' => $postData['dingding_pass'],
                        'station_name' => $postData['name'],
                        'city_name' => $city['area_name'] ?? "",
                        'area_name' => $area['area_name'] ?? "",
                        'station_address' => $province['area_name'] . $city['area_name'] . $area['area_name'] . $postData['adress'],
                        'lng' => $postData['long'],
                        'lat' => $postData['lat'],
                        'contact_name' => $postData['name'],
                        'mobile' => $postData['phone'],
                        'per_userid' => $merchant_ststore_addore_id,
                    );
                    $res = (new MerchantStoreService())->createUpdateN($postDataNew);
                    $res = json_decode($res, true);
                    $where_store_id = [['store_id', '=', $merchant_ststore_addore_id]];
                    $data_up['dingding_per_userid'] = $merchant_ststore_addore_id;
                    (new MerchantStoreService())->updateThis($where_store_id, $data_up);
                }
            }


            //营业时间
            //invoke_cms_model('Merchant_store_open_time/add_edit_data', [$postData['mer_id'], $merchant_ststore_addore_id, $week_status, $open_time, $close_time, 1]);
            (new MerchantStoreOpenTimeService())->add_edit_data($postData['mer_id'], $merchant_ststore_addore_id, $week_status, $open_time, $close_time, 1);
            $addScore['parent_id'] = $merchant_ststore_addore_id;
            $addScore['type'] = 2;
            //invoke_cms_model('Merchant_score/add', [$addScore]);
            (new MerchantScore())->add($addScore);
            //判断关键词
            if (!empty($key_arr)) {
                $data_keywords['third_id'] = $merchant_ststore_addore_id;
                $data_keywords['third_type'] = 'Merchant_store';
                foreach ($key_arr as $value) {
                    $data_keywords['keyword'] = $value;
                    (new MerchantStoreService())->keywordsAdd($data_keywords);
                }
            }

            //多语言表增加数据
            $postData['store_id'] = $merchant_ststore_addore_id;
            invoke_cms_model('Lang/add_lang_data', ['Merchant_store', $postData]);
            // 自动添加线下支付方式
            $store_pay_data['store_id'] = $merchant_ststore_addore_id;
            $store_pay_data['name'] = L_('线下支付');
            (new MerchantStoreService())->storePayadd($store_pay_data);

            //店铺支付送优惠券配置
            if(!empty($postData['open_give_coupon'])&&$store_give_coupon_set_data){
                $store_give_coupon_set_data['store_id'] = $merchant_ststore_addore_id;
                $store_give_coupon_set_data['mer_id'] = $mer_id;
                (new MerchantStoreGiveCouponSet())->save($store_give_coupon_set_data);
            }
            
            return api_output(1000, [], L_('添加成功！'));
        } else {
            return api_output(1001, [], L_('添加失败！请重试'));
        }
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
    }

    /**
     * 获取街道
     */
    public function getStreet(){
        $param['area_id'] = $this->request->param('area_id', '', 'intval');
        if(empty($param['area_id'])){
            return api_output(1000, []);
        }else{
            $ret=(new AreaService())->getStreet($param);
            return api_output(1000, $ret, L_('查询成功！'));
        }
    }
    /**
     * 店铺编辑保存
     */
    public function saveStoreEdit()
    {
        $postData = $this->request->param();
        $mer_id = $this->merId;
        try {
            if (empty($postData['name'])) {
                return api_output(1001, [], L_('店铺名称必填'));
            }
            if (empty($postData['phone'])) {
                return api_output(1001, [], L_('联系电话必填'));
            }
            if (empty($postData['long']) || empty($postData['lat'])) {
                return api_output(1001, [], L_('店铺经纬度必填'));
            }
            if (empty($postData['adress'])) {
                return api_output(1001, [], L_('店铺地址必填'));
            }

            if (empty($postData['pic_info'])) {
                return api_output(1001, [], L_('请上传一张商家图片'));
            }
            if (!empty($postData['video_url']) && !empty($postData['video_url'][0])) {
                $postData['video_url'] = $postData['video_url'][0];
                $postData['video_url_preview'] = (new \app\mall\model\db\MallImage())->findVideoPreview($postData['video_url']);
            }else{
                $postData['video_url_preview'] = '';
		$postData['video_url'] = '';
            }

            if ($postData['logo']) {
                $postData['logo'] = str_replace(file_domain(), '', $postData['logo']);
            }
            
            !empty($postData['index_left_image']) && $postData['index_left_image'] = str_replace(file_domain(), '', $postData['index_left_image']);
            !empty($postData['head_bg_image']) && $postData['head_bg_image'] = str_replace(file_domain(), '', $postData['head_bg_image']);
            
            //小程序分享图若未设置，则保存空字符串
            if (!$postData['wxapp_share_pic']) {
                $postData['wxapp_share_pic'] = '';
            }

            //判断关键词
            $keywords = trim($postData['keywords']);
            if (!empty($keywords)) {
                $tmp_key_arr = explode(' ', $keywords);
                $key_arr = array();
                foreach ($tmp_key_arr as $value) {
                    if (!empty($value)) {
                        array_push($key_arr, $value);
                    }
                }
                if (count($key_arr) > 5) {
                    return api_output(1001, [], L_('关键词最多5个'));
                }
            }
            $postData['age_limit_buy'] = intval($postData['age_limit_buy']);
            if ($postData['age_limit_buy_switch'] == 1 && intval($postData['age_limit_buy']) > 99) {
                return api_output(1001, [], L_('年龄限制不能超过100岁'));
            }

            $postData['supplier_nav_first_name'] = isset($postData['supplier_nav_first_name']) ? $postData['supplier_nav_first_name'] : '';
            $postData['supplier_nav_first_link'] = isset($postData['supplier_nav_first_link']) ? $postData['supplier_nav_first_link'] : '';
            $postData['supplier_nav_second_name'] = isset($postData['supplier_nav_second_name']) ? $postData['supplier_nav_second_name'] : '';
            $postData['supplier_nav_second_link'] = isset($postData['supplier_nav_second_link']) ? $postData['supplier_nav_second_link'] : '';

            $postData['office_time'] = '';

            $postData['sort'] = intval($postData['sort']);
            $postData['last_time'] = $_SERVER['REQUEST_TIME'];
            $postData['supplier_info'] = isset($postData['supplier_info'])?htmlspecialchars($postData['supplier_info']):'';

            $postData['discount_txt'] = '';
            $discount_type = isset($postData['discount_type']) ? intval($postData['discount_type']) : 0;
            if ($discount_type == 1) {
                $discount_percent = isset($postData['discount_percent']) && $postData['discount_percent'] ? (intval($postData['discount_percent'] * 10) / 10) : 0;
                if ($discount_percent >= 0 && $discount_percent <= 10) {
//                    if ($this->config['open_extra_price'] == 1) {
                        $postData['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent, 'discount_limit' => isset($postData['discount_limit']) ? $postData['discount_limit'] : "", 'discount_limit_percent' => isset($postData['discount_limit_percent']) ? $postData['discount_limit_percent'] : ""));
//                    } else {
//                        $postData['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent));
//                    }
                } elseif ($discount_percent < 0 || $discount_percent > 10) {
                    return api_output(1001, [], L_('折扣率必须在0~10之间的数'));
                }
            } elseif ($discount_type == 2) {
                $condition_price = isset($postData['condition_price']) && $postData['condition_price'] ? (intval($postData['condition_price'] * 100) / 100) : 0;
                $minus_price = isset($postData['minus_price']) && $postData['minus_price'] ? (intval($postData['minus_price'] * 100) / 100) : 0;
                if ($condition_price < 0 || $minus_price < 0 || $minus_price > $condition_price) {
                    return api_output(1001, [], L_('满减的填写不正确，必须都是大于0且满足的金额要大于减免金额'));
                }
                if ($condition_price > 0 && $minus_price > 0 && $minus_price < $condition_price) {
                    $postData['discount_txt'] = serialize(array('discount_type' => $discount_type, 'condition_price' => $condition_price, 'minus_price' => $minus_price));
                }
            }
            
            //返现百分比
            if(customization('cash_back')){
                if(!empty($postData['cash_back_rate']) && $postData['cash_back_rate'] < 0){
                    return api_output(1001, [], L_('返现百分比的填写不正确，必须是大于等于0'));
                }
            }
            $store_give_coupon_set_data = [];
            //送优惠券配置
            if($postData['open_give_coupon']){
                if(!in_array($postData['is_discount'],[0,1])){
                    return api_output(1001, [], L_('优惠金额类型错误！'));
                }
                if($postData['discount']<0){
                    return api_output(1001, [], L_('优惠金额错误！'));
                }
                if($postData['order_money']<0){
                    return api_output(1001, [], L_('最小订单金额不能小于0！'));
                }
                if($postData['coupon_num']<1){
                    return api_output(1001, [], L_('优惠券数量不能小于1！'));
                }
                if($postData['use_day']<0){
                    return api_output(1001, [], L_('领取后使用天数不能小于0！'));
                }
                if($postData['expire_day']<1){
                    return api_output(1001, [], L_('过期天数不能小于1！'));
                }
                if(!$postData['coupon_name']){
                    return api_output(1001, [], L_('优惠券名称不能为空！'));
                }
                $store_give_coupon_set_data = [
                    'is_discount' => $postData['is_discount'],
                    'discount' => $postData['discount'],
                    'order_money' => $postData['order_money'],
                    'coupon_num' => $postData['coupon_num'],
                    'use_day' => $postData['use_day'],
                    'expire_day' => $postData['expire_day'],
                    'coupon_name' => $postData['coupon_name'],
                    'coupon_img' => $postData['coupon_img']??'',
                    'use_with_card' => $postData['use_with_card']??0
                ];
            }

            $condition_merchant_store = [['store_id', '=', $postData['store_id']]];
            $now_store = (new MerchantStoreService())->getOne($condition_merchant_store);
            /*if ($now_store) {
                $now_store = $now_store->toArray();
            }*/
            $postData['store_type'] = isset($postData['store_type']) ? intval($postData['store_type']) : 1;
            // unset($postData['store_id']);
            $ismain = intval($postData['ismain']);
            if ($ismain == 1) {
                $where_update = [['mer_id', '=', $now_store['mer_id']]];
                $updata['ismain'] = 0;
                (new MerchantStoreService())->updateThis($where_update, $updata);
            }

            // 设置是否开通业务员权限
            $issalesman = intval($postData['issalesman']);
            $where_update = [['store_id', '=', $postData['store_id']]];
            $updata['issalesman'] = $issalesman;
            (new MerchantStoreService())->updateThis($where_update, $updata);

            $leveloff1 = isset($postData['leveloff']) ? $postData['leveloff'] : false;
            $leveloff = !empty($leveloff1) ? unserialize($leveloff1) : '';
            $newleveloff = array();
            if (!empty($leveloff)) {
                foreach ($leveloff as $kk => $vv) {
                    $vv['type'] = intval($vv['type']);
                    $vv['vv'] = intval($vv['vv']);
                    if (($vv['type'] > 0) && ($vv['vv'] > 0)) {
                        $vv['level'] = $kk;
                        $newleveloff[$kk] = $vv;
                    }
                }
            }

            //$postData['leveloff'] = !empty($newleveloff) ? serialize($newleveloff) : '';
            $week_status = array();
            $open_time = array();
            $close_time = array();
            foreach ($postData['retval'] as $key => $val) {
                $week_status[] = $val['week_status'];
                foreach ($val['time_list'] as $ke => $va) {
                    $open[$ke + 1] = $va['open_time'];
                    $close[$ke + 1] = $va['close_time'];
                }
                $open_time[] = $open;
                $close_time[] = $close;
            }

            $wheres = [['store_id', '=', $postData['store_id']]];
            $datas['is_rec'] = 0;
            (new GroupService())->getStoreUpdate($wheres, $datas);
            if (!empty($postData['check_list'])) {
                $wheres[] = ['group_id', 'in', $postData['check_list']];
                $datas['is_rec'] = 1;
                (new GroupService())->getStoreUpdate($wheres, $datas);
            }

            if (isset($postData['store_pay']) && !empty($postData['store_pay'])) {
                $where_pay = [['store_id', '=', $postData['store_id']]];
                (new MerchantStoreService())->delStorePay($where_pay);
                foreach ($postData['store_pay'] as $key => $val) {
                        $data_pay = [];
                        $data_pay['name'] = $val['name'];
                        $data_pay['store_id'] = $postData['store_id'];
                        !empty($val['id']) && $data_pay['id'] = $val['id'];
                        (new MerchantStoreService())->storePayadd($data_pay);
                }
            }

            //营业时间
            //invoke_cms_model('Merchant_store_open_time/add_edit_data', [$postData['mer_id'], $postData['store_id'], $week_status, $open_time, $close_time, 0]);
            (new MerchantStoreOpenTimeService())->add_edit_data($postData['mer_id'], $postData['store_id'], $week_status, $open_time, $close_time, 0);
            $store_trade_ticket = $postData['store_trade_ticket'];
            $store_trade_ticket['store_id'] = $postData['store_id'];
            $postData['logo'] = $postData['image_logo'] ? $postData['logo'] : '';
            unset($postData['dindin_is_open']);
            unset($postData['open_store_vr']);
            unset($postData['check_list']);
            unset($postData['week_status']);
            unset($postData['open_time']);
            unset($postData['close_time']);
            unset($postData['image_logo']);
            unset($postData['pic']);
            unset($postData['qcode']);
            unset($postData['wxapp_qcode']);
            unset($postData['keywords']);
            unset($postData['map_config']);
            unset($postData['google_map_ak']);
            unset($postData['store_ticket_have']);
            unset($postData['pay_in_store']);
            unset($postData['store_ticket_have_insure']);
            unset($postData['merchant_edit_circle']);
            unset($postData['options']);
            unset($postData['areas']);
            unset($postData['store_pay']);
            unset($postData['store_trade_ticket']);
            unset($postData['retval']);
            unset($postData['age_limit_buy_switch']);
            unset($postData['plat_recommend_info_arr']);
            unset($postData['system_type']);
            unset($postData['is_cash_back']);
            if ($postData['plat_recommend_type'] == 'group') {
                $postData['plat_recommend_info'] = 'group_' . $postData['plat_recommend_type_group'];
            } else {
                $ms = isset($postData['plat_recommend_type_store']) ? $postData['plat_recommend_type_store'] : "";
                $postData['plat_recommend_info'] = $ms . '_' . ($postData['plat_recommend_type_store']=='description'?$postData['plat_recommend_info']:0);
            }
            unset($postData['plat_recommend_type_store']);
            unset($postData['plat_recommend_type_group']);
            unset($postData['condition_price']);
            unset($postData['minus_price']);
            unset($postData['discount_limit']);
            unset($postData['system_type']);
            unset($postData['discount_percent']);
            unset($postData['discount_limit_percent']);
            unset($postData['disabled_status']);
            unset($postData['is_discount']);
            unset($postData['discount']);
            unset($postData['order_money']);
            unset($postData['coupon_num']);
            unset($postData['use_day']);
            unset($postData['expire_day']);
            unset($postData['coupon_name']);
            unset($postData['coupon_img']);
            if(isset($postData['use_with_card'])){
                unset($postData['use_with_card']);
            }
            
            if(isset($postData['list'])){
                unset($postData['list']);
            }
            if (isset($postData['tw_shop_id']) && $postData['tw_shop_id']) {
                $twArr = explode('_', $postData['tw_shop_id']);
                $postData['tw_shop_id'] = $twArr[0] ?? '';
                $postData['tw_branch_no'] = $twArr[1] ?? '';
            }

            if (abs($postData['long']) > 0 && abs($postData['lat']) && cfg('tianditu_web_key')) {
                list($postData['long'], $postData['lat']) = Coordinate::wgs84ToBd09($postData['long'], $postData['lat']);
            }
            if ((new MerchantStoreService())->updateThis($condition_merchant_store, $postData)) {
                MerchantStoreShop::where(['store_id' => $postData['store_id']])->save(['recommend_pic' => $postData['index_left_image']]);
                //更新IM店铺客服头像和昵称
                (new HookService())->hookExec('merchant_store.after_update', array('type' => 'store', 'store_id' => $postData['store_id']));
                //加入叮叮配送处理
                //绑定叮叮配送
                if (cfg('dindin_is_open') == 1) {
                    if ($postData['is_open_dingding'] == 1) {
                        //$dingDing = new Dingding('','','','','','','','');
                        $where_pro = [['area_id', '=', $postData['province_id']]];
                        $province = (new MerchantStoreService())->getAreaOne($where_pro);
                        if (!empty($province)) {
                            $province = $province->toArray();
                        }
                        $where_city = [['area_id', '=', $postData['city_id']]];
                        $city = (new MerchantStoreService())->getAreaOne($where_city);
                        if (!empty($city)) {
                            $city = $city->toArray();
                        }
                        $where_area = [['area_id', '=', $postData['area_id']]];
                        $area = (new MerchantStoreService())->getAreaOne($where_area);
                        if (!empty($area)) {
                            $area = $area->toArray();
                        }
                        $postDataNew = array(
                            'isadd' => 0,
                            'cargotype_code' => 1,
                            'appid' => cfg('dingding_appid'),
                            'username' => $postData['dingding_user_name'],
                            'password' => $postData['dingding_pass'],
                            'station_name' => $postData['name'],
                            'city_name' => $city['area_name'],
                            'area_name' => $area['area_name'],
                            'station_address' => $city['area_name'] . $area['area_name'] . $postData['adress'],
                            'lng' => $postData['long'],
                            'lat' => $postData['lat'],
                            'contact_name' => $postData['name'],
                            'mobile' => $postData['phone'],
                            'per_userid' => isset($postData['dingding_per_userid']) ? $postData['store_id'] : $postData['dingding_per_userid'],
                        );

                        $res = (new MerchantStoreService())->createUpdateN($postDataNew);
                        $res = json_decode($res, true);
                    }
                }
                $data_keywords = [['third_id', '=', $postData['store_id']], ['third_type', '=', 'Merchant_store']];
                (new MerchantStoreService())->keywordsDel($data_keywords);
                //判断关键词
                if (!empty($key_arr)) {
                    foreach ($key_arr as $value) {
                        $data_keywords1['third_id'] = $postData['store_id'];
                        $data_keywords1['third_type'] = 'Merchant_store';
                        $data_keywords1['keyword'] = $value;
                        (new MerchantStoreService())->keywordsAdd($data_keywords1);
                    }
                }

                //多语言表增加数据
                invoke_cms_model('Lang/add_lang_data', ['Merchant_store', $postData]);
                //判断票务插件
                if (cfg('store_ticket_have') && $postData['bind_store_trade'] == 'ticket') {
                    $condition_store_trade_ticket = [['store_id', '=', $postData['store_id']]];
                    if ((new MerchantStoreService())->getTradeTicketOne($condition_store_trade_ticket)) {
                        (new MerchantStoreService())->updateTradeTicket($condition_store_trade_ticket, $store_trade_ticket);
                    } else {
                        $data_store_trade_ticket['store_id'] = $postData['store_id'];
                        (new MerchantStoreService())->addTradeTicket($store_trade_ticket);
                    }
                }

                //最后更新下营业状态吧
                (new MerchantStoreOpenTimeService())->checkBusinessTime($postData['store_id']);
                
                //店铺支付送优惠券配置
                if($postData['open_give_coupon']&&$store_give_coupon_set_data){
                    //查询是否已经存在
                    $store_give_set = (new MerchantStoreGiveCouponSet())->where(['store_id'=>$postData['store_id']])->findOrEmpty()->toArray();
                    if($store_give_set){
                        (new MerchantStoreGiveCouponSet())->where(['store_id'=>$postData['store_id']])->save($store_give_coupon_set_data);
                    }else{
                        $store_give_coupon_set_data['store_id'] = $postData['store_id'];
                        $store_give_coupon_set_data['mer_id'] = $mer_id;
                        (new MerchantStoreGiveCouponSet())->save($store_give_coupon_set_data);
                    }
                }
                
                return api_output(1000, [], L_('保存成功！'));
            } else {
                return api_output(1001, [], L_('保存失败！！您是不是没做过修改？请重试~'));
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * @return mixed
     * base64转化
     */
    public function getUrlencode()
    {
        $image_dir = $this->request->param('image_dir', '', 'trim');
        $token = '24.81995d28d166700b6f7c569c0f01169b.2592000.1623378642.282335-24151589';
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/general_basic?access_token=' . $token;
        $dir = request()->server('DOCUMENT_ROOT') . $image_dir;
        $img = file_get_contents($dir);
        $img = base64_encode($img);
        $bodys = array(
            'image' => $img
        );
        $res = $this->request_post($url, $bodys);

        return $res;
    }

    /**
     * api文档：https://cloud.baidu.com/doc/OCR/s/zk3h7xz52
     * 发起http post请求(REST API), 并获取REST请求的结果
     * @param string $url
     * @param string $param
     * @return - http response body if succeeds, else false.
     */
    function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        // 初始化curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // post提交方式
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        // 运行curl
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    /**
     * 编辑店铺资质审核资料
     */
    public function authEdit()
    {
        $database_merchant_store = new MerchantStoreService();
        $store_id = $this->request->param('store_id', '', 'intval');
        $pic = $this->request->param('pic', '', 'trim');
        $now_store = $this->check_store($store_id);
        if (empty($pic)) {
            return api_output(1001, [], L_('请至少上传一张图片'));
        }
        $data['auth_files'] = $pic;
        $where = [['store_id', '=', $store_id]];
        if ($now_store['auth'] < 3) {
            if ($authfile_row = $database_merchant_store->authfileGetOne($where)) {
                $data['dateline'] = time();
                $result = $database_merchant_store->authfileUpdateThis($where, $data);
            } else {
                $data['dateline'] = time();
                $data['store_id'] = $store_id;
                $result = $database_merchant_store->authfileAdd($data);
            }
            if ($result) {
                $store_data = array('auth_files' => $data['auth_files'], 'auth' => 1, 'auth_time' => time());
                $database_merchant_store->updateThis($where, $store_data);
                return api_output(1000, [], L_('保存成功！'));
            } else {
                return api_output(1001, [], L_('保存失败！！您是不是没做过修改？请重试~'));
            }
        } else {
            $data['dateline'] = time();
            if ($database_merchant_store->authfileUpdateThis($where, $data)) {
                if($now_store['auth']==4){
                    $store_data = array('auth' => 1, 'auth_time' => time());
                }else{
                    $store_data = array('auth' => 4, 'auth_time' => time());
                }
                $database_merchant_store->updateThis($where, $store_data);
                return api_output(1000, [], L_('保存成功！'));
            } else {
                return api_output(1001, [], L_('保存失败！！您是不是没做过修改？请重试~'));
            }
        }
    }

    public function authMsg()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $database_merchant_store = new MerchantStoreService();
        //$store_id =2;
        $now_store = $this->check_store($store_id);

        if (empty($now_store)) {
            return api_output(1001, [], '店铺不存在');
        }
        $now_store['reason'] = '';
        try {
            $auth_files = array();
            $where = [['store_id', '=', $store_id]];
            $auth_msg = $database_merchant_store->authfileGetOne($where);
            if (!empty($auth_msg)) {
                $store_authfile = $auth_msg->toArray();
                if (!empty($store_authfile['auth_files'])) {
                    $tmp_pic_arr = explode(';', $store_authfile['auth_files']);
                    foreach ($tmp_pic_arr as $key => $value) {
                        /*$images = $this->get_image_by_path($value, '-1');*/
                        /*$auth_files[] = array('title' => $value, 'url' => $images['image']);*/
                        if (strpos($value, ',') !== false) {
                            $value = strtr($value, ',', '/');
                        }
                        if (stripos($value, 'upload') === false) {
                            $value = substr($value, 0, 1) == '/' ? '/upload/authfile' . $value : '/upload/authfile/' . $value;
                        }
                        $auth_files[] = replace_file_domain($value);
                    }
                }
                $now_store['reason'] = $store_authfile['reason'];
            }
            $now_store['auth_files'] = $auth_files;
            return api_output(1000, $now_store, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**根据店铺数据表的图片字段的一段来得到图片*/
    public function get_image_by_path($path, $image_type = '-1')
    {
        if (strstr($path, 'upload/') !== false) {
            //含有upload
            $dir = '';
        } else {
            $dir = '/upload/authfile/';
        }

        if (!empty($path)) {
            $image_tmp = explode(',', $path);
            if (count($image_tmp) == 1) {
                //新格式
                $path = str_replace(',', '/', $path);
                $pathinfo = pathinfo($path);
                $image_tmp = [
                    0 => $pathinfo['dirname'],
                    1 => $pathinfo['basename']
                ];
            }

            if ($image_type == '-1') {
                $return['image'] = file_domain() . $dir . $image_tmp[0] . '/' . $image_tmp['1'];
                $return['m_image'] = file_domain() . $dir . $image_tmp[0] . '/m_' . $image_tmp['1'];
                $return['s_image'] = file_domain() . $dir . $image_tmp[0] . '/s_' . $image_tmp['1'];
            } else {
                $return = file_domain() . $dir . $image_tmp[0] . '/' . $image_type . '_' . $image_tmp['1'];
            }
            return $return;
        } else {
            return false;
        }
    }

    /**
     * 店员管理
     */
    public function staffManagement()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $mer_id = $this->merId;
        if (empty($store_id)) {
            return api_output(1001, [], '店铺ID不存在');
        }
        $database_merchant_store = new MerchantStoreService();
        try {
            //获取店铺信息
            $now_store = $database_merchant_store->getStoreByStoreId($store_id);
            if (empty($now_store)) {
                return api_output(1001, [], '店铺不存在');
            }
            //获取店员列表
            $condition_store_staff = [['token', '=', $mer_id], ['store_id', '=', $store_id]];
            $database_merchant_store_staff = new MerchantStoreStaffService();
            $field = "*";
            $order = "id desc";
            $pageSize = $this->request->param('pageSize', 20, 'intval');
            $page = $this->request->param('page', 1, 'intval');
            $list = $staff_list = $database_merchant_store_staff->getStaffManagementList($condition_store_staff, $field, $order, $page, $pageSize);
            $list['now_store'] = $now_store;
            $list['site_url'] = cfg('site_url');
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //设置店铺店员信息
    public function staffSet()
    {
        $database_merchant_store = new MerchantStoreService();
        $staff_id = $this->request->param('id', '', 'intval');//店员id
        $mer_id = $this->merId;
        $company_staff_db = new MerchantStoreStaffService();
        if (!empty($staff_id)) {
            $where = [['id', '=', $staff_id]];
            $thisItem = $company_staff_db->getOne($where);
            if($thisItem&&$thisItem['pick_addr_ids']){
                $thisItem['pick_addr_ids'] = array_map('intval',explode(',',$thisItem['pick_addr_ids']));
            }else{
                $thisItem['pick_addr_ids'] = [];
            }
        } else {
            $thisItem['companyid'] = 0;
        }
        $thisItem['password'] = '';
        //获取商户是否开区景区功能
        $merchant_msg = $database_merchant_store->getMerchantInfo($mer_id);
        if (!$merchant_msg) {
            $merchant_msg['is_open_scenic'] = 0;
        }
        $assign['staff_item'] = $thisItem;
        $assign['is_open_scenic'] = $merchant_msg['is_open_scenic'];
        return api_output(1000, $assign, 'success');

    }

    //设置店铺店员信息--新增编辑
    public function staffEdit()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $staff_id = $this->request->param('id', '', 'intval');//店员id
        $pick_addr_ids = $this->request->param('pick_addr_ids', '', 'trim,string');
        $mer_id = $this->merId;
        $now_store = $this->check_store($store_id);
        if (empty($now_store)) {
            return api_output(1001, [], '店铺不存在');
        }
        $list['now_store'] = $now_store;
        $list['staff_type'] = $this->staff_type;
        if($pick_addr_ids&&is_array($pick_addr_ids)){
            $data['pick_addr_ids'] = implode(',',$pick_addr_ids);
        }else{
            $data['pick_addr_ids'] = '';
        }
        $data['store_id'] = $now_store['store_id'];
        $company_staff_db = new MerchantStoreStaffService();
        $data['name'] = $this->request->param('name', '', 'trim');
        $data['username'] = $this->request->param('username', '', 'trim');
        $data['password'] = $this->request->param('password', '', 'trim');
        $data['is_change'] = $this->request->param('is_change', 0, 'intval');
        $data['can_refund_dinging_order'] = $this->request->param('can_refund_dinging_order', 0, 'intval');
        $data['show_scenic_order'] = $this->request->param('show_scenic_order', 0, 'intval');
        $data['tel'] = $this->request->param('tel', '', 'trim');
        $data['type'] = $this->request->param('type', '', 'trim');
        $data['time'] = time();
        $data['last_time'] = time();
        $data['last_print_time'] = 0;
        $data['token'] = $mer_id;

        $data['can_verify_activity_appoint'] = $this->request->param('can_verify_activity_appoint', 0, 'intval');

        $data['openid'] = 0;
        $data['device_id'] = 0;
        $data['is_notice'] = 0;
        $data['app_version_name'] ='';
        $data['device_token'] ='';
        $data['jpush_device_token'] ='';
        $data['phone_country_type'] ='';
        $data['web_session'] ='';
        $data['phone_brand'] ='';

        if (empty($data['name']) || empty($data['username'])) {
            return api_output(1001, [], '姓名、帐号都不能为空');
        }
        if (empty($staff_id)) {
            $condition_store_staff_username = [['username', '=', $data['username']]];
            if (!empty($company_staff_db->getOne($condition_store_staff_username))) {
                return api_output(1001, [], '帐号已经存在!请换一个。');
            }
            if (empty($data['password'])) {
                return api_output(1001, [], '密码不能为空');
            }
            //校验密码
            $check_password = verifyPassword($data['password']);
            if($check_password['code']!=0){
                return api_output(1001, [], $check_password['msg']);
            }
            $data['password'] = md5($data['password']);

            if (!$company_staff_db->add($data)) {
                return api_output(1001, [], '添加失败,请重试');
            }
        } else {
            /* 检测帐号 */
            $condition_store_staff_username = [['username', '=', $data['username']]];
            $username_staff = $company_staff_db->getOne($condition_store_staff_username);
            if ($username_staff && $username_staff['id'] != $staff_id) {
                return api_output(1001, [], '帐号已经存在!请换一个');
            }
            if (!trim($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = md5($data['password']);
                $data['update_pwd_time'] = time();
            }

            //优化设置店员管理风景区门票功能
            $is_verify_ticket = $this->request->param('is_verify_ticket', '', 'intval');
            /*if($staff_id==$_SESSION['staff']['id'] && is_numeric($is_verify_ticket)){
                $_SESSION['staff']['is_verify_ticket']=$is_verify_ticket;
            }*/

            $where = [['id', '=', $staff_id]];
            if (!$company_staff_db->updateThis($where, $data)) {
                return api_output(1001, [], '修改失败,请重试');
            }
        }
        return api_output(1000, [], '操作成功');
    }

    /**
     * @return \json
     * @throws \Exception
     * 删除店铺
     */
    public function storeDel()
    {
        $database_merchant_store = new MerchantStoreService();
        $store_id = $this->request->param('store_id', '', 'intval');
        if (empty($store_id)) {
            return api_output(1003, [], '店铺不存在');
        }
        $where = [['store_id', '=', $store_id]];
        try {
            $ret = $database_merchant_store->storeDel($where);
            if ($ret) {
                //删除该店铺下面店员信息
                (new MerchantStoreStaffService())->getDel($where);
                $assign['status'] = 1;
                return api_output(1000, $assign, '操作成功');
            } else {
                return api_output(1003, [], '删除失败');
            }
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }

    }

    /**
     * @return \json
     * 删除店员
     */
    public function staffDelete()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $staff_id = $this->request->param('id', '', 'intval');//店员id
        $mer_id = $this->merId;
        $now_store = $this->check_store($store_id);
        if (empty($now_store)) {
            return api_output(1001, [], '店铺不存在');
        }
        $data['store_id'] = $now_store['store_id'];

        $company_staff_db = new MerchantStoreStaffService();

        $condition_store_staff = [['token', '=', $mer_id], ['id', '=', $staff_id]];
        if ($company_staff_db->getDel($condition_store_staff)) {
            return api_output(1000, $data, 'success');
        } else {
            return api_output(1001, [], '操作失败,请重试');
        }

    }

    /**
     * 店铺优惠
     */
    public function discount()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $now_store = $this->check_store($store_id);
        $assign['store'] = $now_store;
        $where = [['store_id', '=', $now_store['store_id']], ['source', '=', 1], ['shop_type', '=', 1]];
        $pageSize = $this->request->param('pageSize', 20, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $discount = (new MerchantStoreService())->merchantDiscountSome($where, true, 'id DESC', ($page - 1) * $pageSize, $pageSize);
        //$discount = (new ShopDiscount())->getSome($where, true, true, ($page - 1) * $pageSize, $pageSize)->toArray();
        $assign['list'] = $discount;
        $assign['pageSize'] = $pageSize;
        $assign['count'] = (new MerchantStoreService())->getShopDiscountCount($where);
        $assign['page'] = $page;
        $assign['site_url'] = cfg('site_url');
        return api_output(1000, $assign, 'success');
    }


    /**
     * 新增店铺优惠
     */
    public function discountAdd()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $id = $this->request->param('id', '', 'intval');//主键
        $now_store = $this->check_store($store_id);
        $assign['store_id'] = $now_store['store_id'];

        $data_discount['full_money'] = $full_money = $this->request->param('full_money', '', 'trim');
        $data_discount['reduce_money'] = $reduce_money = $this->request->param('reduce_money', '', 'trim');
        $data_discount['type'] = $type = $this->request->param('type', 0, 'intval');
        $data_discount['status'] = $status = $this->request->param('status', 0, 'intval');
        $data_discount['is_share'] = $is_share = $this->request->param('is_share', 0, 'intval');
        $data_discount['use_limit'] = $use_limit = $this->request->param('use_limit', 1, 'intval');
        $data_discount['source'] = 1;

        $database_discount = (new MerchantStoreService());
        $data_discount['store_id'] = $now_store['store_id'];
        $data_discount['mer_id'] = $now_store['mer_id'];
        if ($data_discount['type'] == 0) {
            //新单order_count置为1
            $data_discount['order_count'] = 1;
        } else if ($data_discount['type'] == 1) {
            $data_discount['order_count'] = 0;
        }
        if (empty($id)) {//新增
            if ($database_discount->getShopDiscountAdd($data_discount)) {
                return api_output(1000, $assign, 'success');
            } else {
                return api_output(1001, $assign, '添加失败,请重试');
            }
        } else {//修改
            /* $data_discount['id'] = $id;*/
            $where = [['store_id', '=', $now_store['store_id']], ['id', '=', $id]];
            if (empty($discount = $database_discount->getShopDiscountOne($where))) {
                return api_output(1001, $assign, '不存在的优惠,请查证后修改');
            }

            if ($database_discount->shopDiscountUpdateThis($where, $data_discount)) {
                return api_output(1000, $assign, '修改成功!');
            } else {
                return api_output(1000, $assign, '修改失败!请重试');
            }
        }
    }

    /**
     * @return \json
     * 删除店铺优惠
     */
    public function discountDelete()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $id = $this->request->param('id', '', 'intval');//店员id
        $now_store = $this->check_store($store_id);
        if (empty($now_store)) {
            return api_output(1001, [], '店铺不存在');
        }
        $database_discount = (new MerchantStoreService());
        $condition_store_discount = [['id', '=', $id]];
        if ($database_discount->shopDiscountDel($condition_store_discount)) {
            return api_output(1000, [], 'success');
        } else {
            return api_output(1001, [], '操作失败,请重试');
        }

    }

    /**
     * 店铺优惠信息
     */
    public function discountMsg()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $id = $this->request->param('id', '', 'intval');//主键
        if (empty($store_id)) {
            return api_output(1001, [], '店铺ID不存在');
        }
        if (empty($id)) {
            return api_output(1001, [], '优惠信息ID不存在');
        }
        try {
            //获取店铺优惠信息
            $database_discount = (new MerchantStoreService());
            $where = [['store_id', '=', $store_id], ['id', '=', $id]];
            $discount = $database_discount->getShopDiscountOne($where);
            if (!empty($discount)) {
                $list['list'] = $discount;
            } else {
                return api_output(1001, [], '优惠信息不存在');
            }
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导航列表
     */

    public function storeSlider()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $pageSize = $this->request->param('pageSize', 20, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $order = 'sort DESC';
        $where = [['store_id', '=', $store_id]];
        $list = (new MerchantStoreService())->getStoreSliderSome($where, $field = '*', $order, ($page - 1) * $pageSize, $pageSize);
        foreach ($list as $key => $value) {
            $list[$key]['pic'] = replace_file_domain($value['pic']);
            $list[$key]['last_time'] =date("Y-m-d H:i:s",$value['last_time']);
        }
        $assign['list'] = $list;
        $assign['pageSize'] = $pageSize;
        $assign['count'] = (new MerchantStoreService())->getStoreSliderCount($where);
        $assign['page'] = $page;
        $assign['site_url'] = cfg('site_url');
        return api_output(1000, $assign, 'success');
    }

    /**
     * 店铺导航信息
     */
    public function storeSliderMsg()
    {
        $id = $this->request->param('id', '', 'intval');//主键
        if (empty($id)) {
            return api_output(1001, [], '店铺导航信息ID不存在');
        }
        try {
            //获取店铺优惠信息
            $database_slider = (new MerchantStoreService());
            $where = [['id', '=', $id]];
            $list['list'] = [];
            $slider = $database_slider->getStoreSliderOne($where);
            if (!empty($slider)) {
                $list['list'] = $slider->toArray();
                foreach ($list['list'] as $key => $value) {
                    if (!empty($value['pic'])) {
                        $list['list']['pic_info'] = replace_file_domain($value['pic']);
                    } else {
                        $list['list']['pic_info'] = "";
                    }
                }
            } else {
                return api_output(1001, [], '店铺导航信息不存在');
            }
            return api_output(1000, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 导航编辑
     */
    public function sliderDel()
    {
        $id = $this->request->param('id', '', 'intval');
        if (empty($id)) {
            return api_output(1001, [], '没有删除的id');
        }
        $where = [['id', '=', $id]];
        $ret = (new MerchantStoreService())->getStoreSliderDel($where);
        if ($ret) {
            return api_output(1000, [], '删除成功');
        } else {
            return api_output(1001, [], '删除失败');
        }
    }

    /**
     * 导航编辑
     */
    public function storeSliderAdd()
    {
        $db = new MerchantStoreService();
        //$system_session = session('system');
        $id = $this->request->param('id', '', 'intval');
        $data['name'] = $this->request->param('name', '', 'trim');
        $data['url'] = $this->request->param('url', '', 'trim');
        $data['pic'] = $this->request->param('pic', '', 'trim');
        $data['store_id'] = $this->request->param('store_id', '', 'trim');
        $data['status'] = $this->request->param('status', '', 'intval');
        $data['sort'] = $this->request->param('sort', '', 'intval');
        if (empty($data['name'])) {
            return api_output(1001, [], '导航名称未填写');
        }
        if (empty($data['url'])) {
            return api_output(1001, [], '导航链接未选择');
        }
        $data['url'] = htmlspecialchars_decode($data['url']);
        $data['last_time'] = $_SERVER['REQUEST_TIME'];

        $where = [['id', '=', $id]];
        if ($db->getStoreSliderOne($where) && !empty($id)) {//编辑
            $result = $db->StoreSliderUpdateThis($where, $data);
            if ($result) {
                return api_output(1000, [], '编辑成功');
            } else {
                return api_output(1001, [], '编辑失败');
            }
        } else {//新增
            if (empty($data['pic'])) {
                return api_output(1001, [], '导航图片未上传');
            }
            if ($db->getStoreSliderAdd($data)) {
                return api_output(1000, [], '新增成功');
            } else {
                return api_output(1001, [], '新增失败');
            }
        }
    }

    /**
     * 获取商圈列表
     */
    public function getCircleList()
    {
        $param['area_id'] = $this->request->param('area_id', '0', 'intval');

        $return['list'] = (new AreaService())->getAreaListByCondition(['is_open'=>1,'area_pid'=>$param['area_id']], [], 'area_id,area_name');
            
        return api_output(1000, $return);
            
    }

    /**
     * 获取自提点列表
     * @return  \think\response\Json
     */
    public function getPickAddress(){
        $param['mer_id'] = $this->merId;
        try {
            $pick_address = new PickAddressService();
            $data = $pick_address->getPickAddress($param);
            return api_output(1000, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 订单地址修改设置
     * @return \think\response\Json
     */
    public function addressSetting()
    {
        $data = app(MerchantService::class)->addressSetting($this->merId);

        return api_output(0, $data);
    }

    /**
     * 订单地址设置修改
     * @return \think\response\Json
     */
    public function addressSettingEdit()
    {
        $params = $this->validateParameter();
        $params['merchant_id'] = $this->merId;
            
        $data = app(MerchantService::class)->addressSettingEdit($params);

        return api_output(0, $data);
    }

}